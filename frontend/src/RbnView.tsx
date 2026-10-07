import { useEffect, useMemo, useState } from 'react';
import { getRbnSpots } from './api';
import type { HamqthRbnLocation, HamqthRbnSpot } from './types';
import { RbnMap, snrColor } from './RbnMap';
import type { RbnMapHome, RbnMapPoint } from './RbnMap';

// HamQTH asks not to poll /rbn more often than every 15 seconds.
const RBN_POLL_INTERVAL_MS = 30_000;
const CALLSIGN_INPUT_DEBOUNCE_MS = 700;
const MAX_QUERY_CALLS = 30;
const STORAGE_KEYS = {
  maxAge: 'cqrlog.rbn.maxAge.v1',
} as const;
// Positions used to be looked up per call via /dxcc and cached here; /rbn now returns them.
const LEGACY_LOCATIONS_STORAGE_KEY = 'cqrlog.rbn.locations.v1';
const MAX_AGE_OPTIONS = [
  { seconds: 300, label: '5 min' },
  { seconds: 600, label: '10 min' },
  { seconds: 1200, label: '20 min' },
  { seconds: 1800, label: '30 min' },
  { seconds: 3600, label: '60 min' },
];
const BAND_ORDER = ['160M', '80M', '60M', '40M', '30M', '20M', '17M', '15M', '12M', '10M', '6M', '2M'];

type RbnState = {
  // Query the spots belong to; spots of a previous callsign are ignored until the new ones load.
  queryKey: string;
  status: 'idle' | 'loading' | 'ready' | 'error';
  spots: HamqthRbnSpot[];
  locations: Record<string, HamqthRbnLocation>;
  message: string;
  lastLoadedAt: string;
};

type RbnRow = {
  id: string;
  skimmer: string;
  snr: number;
  dxcall: string;
  frequency: number;
  band: string;
  mode: string;
  age: number;
};

type RbnViewProps = {
  active: boolean;
  defaultCallsign: string;
  homeLocator: string | null;
};

function parseQueryCalls(value: string): string[] {
  const calls = value
    .toUpperCase()
    .split(/[\s,;]+/)
    .map((call) => call.trim())
    .filter((call) => /^[A-Z0-9/]{3,}$/.test(call));

  return [...new Set(calls)].slice(0, MAX_QUERY_CALLS);
}

function readStoredMaxAge(): number {
  try {
    const stored = Number.parseInt(window.localStorage.getItem(STORAGE_KEYS.maxAge) ?? '', 10);
    return MAX_AGE_OPTIONS.some((option) => option.seconds === stored) ? stored : 1200;
  } catch {
    return 1200;
  }
}

export function locatorToLatLon(locator: string): { lat: number; lon: number } | null {
  const grid = locator.trim().toUpperCase();

  if (!/^[A-R]{2}[0-9]{2}([A-X]{2})?/.test(grid)) {
    return null;
  }

  let lon = (grid.charCodeAt(0) - 65) * 20 - 180 + Number(grid[2]) * 2;
  let lat = (grid.charCodeAt(1) - 65) * 10 - 90 + Number(grid[3]);

  if (/^[A-X]{2}$/.test(grid.slice(4, 6))) {
    lon += (grid.charCodeAt(4) - 65) / 12 + 1 / 24;
    lat += (grid.charCodeAt(5) - 65) / 24 + 1 / 48;
  } else {
    lon += 1;
    lat += 0.5;
  }

  return { lat, lon };
}

function distanceKm(from: { lat: number; lon: number }, to: { lat: number; lon: number }): number {
  const toRad = (deg: number) => (deg * Math.PI) / 180;
  const dLat = toRad(to.lat - from.lat);
  const dLon = toRad(to.lon - from.lon);
  const a =
    Math.sin(dLat / 2) ** 2 + Math.cos(toRad(from.lat)) * Math.cos(toRad(to.lat)) * Math.sin(dLon / 2) ** 2;
  return 6371 * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

function formatAge(seconds: number): string {
  return seconds < 60 ? '<1 min' : `${Math.floor(seconds / 60)} min`;
}

function bandRank(band: string): number {
  const index = BAND_ORDER.indexOf(band);
  return index === -1 ? BAND_ORDER.length : index;
}

export function RbnView({ active, defaultCallsign, homeLocator }: RbnViewProps) {
  const [callsignInput, setCallsignInput] = useState(defaultCallsign);
  const [queryCalls, setQueryCalls] = useState<string[]>(() => parseQueryCalls(defaultCallsign));
  const [maxAge, setMaxAge] = useState<number>(() => readStoredMaxAge());
  const [bandFilter, setBandFilter] = useState('');
  const [reloadKey, setReloadKey] = useState(0);
  const [rbnState, setRbn] = useState<RbnState>({ queryKey: '', status: 'idle', spots: [], locations: {}, message: '', lastLoadedAt: '' });
  const queryKey = queryCalls.join(',');
  const rbn: RbnState =
    rbnState.queryKey === queryKey
      ? rbnState
      : { queryKey, status: 'idle', spots: [], locations: {}, message: '', lastLoadedAt: '' };

  useEffect(() => {
    setCallsignInput(defaultCallsign);
    setQueryCalls(parseQueryCalls(defaultCallsign));
  }, [defaultCallsign]);

  useEffect(() => {
    const timeoutId = window.setTimeout(() => {
      const next = parseQueryCalls(callsignInput);
      setQueryCalls((current) => (current.join(',') === next.join(',') ? current : next));
    }, CALLSIGN_INPUT_DEBOUNCE_MS);

    return () => window.clearTimeout(timeoutId);
  }, [callsignInput]);

  useEffect(() => {
    try {
      window.localStorage.removeItem(LEGACY_LOCATIONS_STORAGE_KEY);
    } catch {
      // Ignore unavailable storage.
    }
  }, []);

  useEffect(() => {
    try {
      window.localStorage.setItem(STORAGE_KEYS.maxAge, String(maxAge));
    } catch {
      // Ignore unavailable storage.
    }
  }, [maxAge]);

  useEffect(() => {
    setBandFilter('');
  }, [queryKey]);

  useEffect(() => {
    if (!active || queryKey === '') {
      return undefined;
    }

    let cancelled = false;
    let requestInFlight = false;
    const calls = queryKey.split(',');

    const load = async () => {
      if (requestInFlight) {
        return;
      }

      requestInFlight = true;
      setRbn((current) =>
        current.queryKey === queryKey
          ? current
          : { queryKey, status: 'loading', spots: [], locations: {}, message: '', lastLoadedAt: '' },
      );

      try {
        const response = await getRbnSpots(calls, maxAge);

        if (!cancelled) {
          setRbn({
            queryKey,
            status: 'ready',
            spots: response.spots,
            locations: Object.fromEntries(response.locations.map((location) => [location.call, location])),
            message: '',
            lastLoadedAt: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' }),
          });
        }
      } catch (error) {
        if (!cancelled) {
          setRbn((current) => ({
            ...(current.queryKey === queryKey ? current : { spots: [], locations: {}, lastLoadedAt: '' }),
            queryKey,
            status: 'error',
            message: error instanceof Error ? error.message : 'Unable to load RBN spots.',
          }));
        }
      } finally {
        requestInFlight = false;
      }
    };

    void load();
    const intervalId = window.setInterval(() => void load(), RBN_POLL_INTERVAL_MS);

    return () => {
      cancelled = true;
      window.clearInterval(intervalId);
    };
  }, [active, queryKey, maxAge, reloadKey]);

  const rows = useMemo<RbnRow[]>(() => {
    const result: RbnRow[] = [];

    for (const spot of rbn.spots) {
      for (const skimmer of spot.skimmers) {
        result.push({
          id: `${spot.dxcall}|${spot.band}|${spot.mode}|${skimmer.call}`,
          skimmer: skimmer.call,
          snr: skimmer.snr,
          dxcall: spot.dxcall,
          frequency: spot.freq,
          band: spot.band ?? '',
          mode: spot.mode ?? '',
          age: spot.age,
        });
      }
    }

    return result.sort((a, b) => a.age - b.age || b.snr - a.snr);
  }, [rbn.spots]);
  const locations = rbn.locations;

  const bandSummary = useMemo(() => {
    const summary = new Map<string, { skimmers: Set<string>; bestSnr: number }>();

    for (const row of rows) {
      const entry = summary.get(row.band) ?? { skimmers: new Set<string>(), bestSnr: -Infinity };
      entry.skimmers.add(row.skimmer);
      entry.bestSnr = Math.max(entry.bestSnr, row.snr);
      summary.set(row.band, entry);
    }

    return [...summary.entries()]
      .map(([band, entry]) => ({ band, skimmerCount: entry.skimmers.size, bestSnr: entry.bestSnr }))
      .sort((a, b) => bandRank(a.band) - bandRank(b.band));
  }, [rows]);

  const visibleRows = bandFilter === '' ? rows : rows.filter((row) => row.band === bandFilter);

  const locatorPosition = useMemo(() => (homeLocator === null ? null : locatorToLatLon(homeLocator)), [homeLocator]);
  // Without a profile locator, fall back to where HamQTH places the heard station itself, but only when
  // there is a single one; lines from one station's position to another's skimmers would be misleading.
  const heardCalls = new Set(rows.map((row) => row.dxcall));
  const heardLocation =
    locatorPosition === null && heardCalls.size === 1 ? (locations[[...heardCalls][0]] ?? null) : null;
  const homePosition = locatorPosition ?? heardLocation;
  const home = useMemo<RbnMapHome | null>(
    () =>
      homePosition === null
        ? null
        : {
            lat: homePosition.lat,
            lon: homePosition.lon,
            label:
              locatorPosition !== null
                ? `My QTH (${homeLocator?.toUpperCase()})`
                : `${heardLocation?.call} (${heardLocation?.grid ?? 'approx.'})`,
          },
    [homePosition, locatorPosition, homeLocator, heardLocation],
  );

  const mapPoints = useMemo<RbnMapPoint[]>(() => {
    // Many skimmers share an approximate (entity-level) position, so group by position, not by call.
    const byPosition = new Map<string, { location: HamqthRbnLocation; rows: RbnRow[] }>();

    for (const row of visibleRows) {
      const location = locations[row.skimmer];

      if (!location) {
        continue;
      }

      const key = `${location.lat},${location.lon}`;
      const group = byPosition.get(key) ?? { location, rows: [] };
      group.rows.push(row);
      byPosition.set(key, group);
    }

    return [...byPosition.entries()].map(([key, { location, rows: groupRows }]) => {
      const distance = homePosition === null ? null : Math.round(distanceKm(homePosition, location));
      const header = [location.grid, distance === null ? null : `${distance} km`]
        .filter((part) => part !== null && part !== '')
        .join(' · ');
      const lines = [...groupRows]
        .sort((a, b) => b.snr - a.snr)
        .map((row) => `${row.skimmer}: ${row.dxcall} ${row.band} ${row.mode} ${row.snr} dB, ${formatAge(row.age)} ago`);

      return {
        call: key,
        lat: location.lat,
        lon: location.lon,
        bestSnr: Math.max(...groupRows.map((row) => row.snr)),
        label: [header, ...lines].join('\n'),
      };
    });
  }, [visibleRows, locations, homePosition]);

  // The heard call is only interesting when it can differ from what was typed (portable forms, several calls).
  const showHeardCall = queryCalls.length > 1 || visibleRows.some((row) => row.dxcall !== queryCalls[0]);
  const tableRowClass = showHeardCall ? 'rbn-table__row rbn-table__row--heard' : 'rbn-table__row';
  const skimmerCount = new Set(visibleRows.map((row) => row.skimmer)).size;
  const maxAgeLabel = MAX_AGE_OPTIONS.find((option) => option.seconds === maxAge)?.label ?? `${maxAge / 60} min`;

  let emptyMessage = '';

  if (queryCalls.length === 0) {
    emptyMessage = 'Enter a callsign above (or set your callsign in Settings).';
  } else if (rbn.status === 'loading' || rbn.status === 'idle') {
    emptyMessage = 'Loading RBN spots…';
  } else if (rows.length === 0) {
    emptyMessage = `No RBN reports for ${queryCalls.join(', ')} in the last ${maxAgeLabel}. Call CQ in CW/RTTY and give the skimmers a minute.`;
  }

  return (
    <section className="panel panel--list panel--rbn">
      <header className="list-header">
        <div>
          <h2 className="list-header__title">RBN — where am I heard</h2>
          <p className="list-header__count">
            {rows.length > 0
              ? `${skimmerCount} skimmer${skimmerCount === 1 ? '' : 's'} in the last ${maxAgeLabel}`
              : `Reverse Beacon Network via HamQTH, polling every ${RBN_POLL_INTERVAL_MS / 1000} s`}
            {rbn.lastLoadedAt !== '' ? ` · Last loaded ${rbn.lastLoadedAt}` : ''}
          </p>
        </div>
        <button
          className="button button--secondary button--settings-action"
          type="button"
          onClick={() => setReloadKey((current) => current + 1)}
          disabled={queryCalls.length === 0 || rbn.status === 'loading'}
        >
          Reload
        </button>
      </header>

      <div className="list-filters">
        <input
          className="list-filters__input list-filters__input--callsign rbn-callsign-input"
          type="text"
          value={callsignInput}
          onChange={(event) => setCallsignInput(event.target.value.toUpperCase())}
          onKeyDown={(event) => {
            if (event.key === 'Enter') {
              setQueryCalls(parseQueryCalls(callsignInput));
            }
          }}
          placeholder="Callsign, e.g. OK2CQR/P"
          aria-label="Callsign to look up on RBN"
          autoCapitalize="characters"
          autoCorrect="off"
          spellCheck={false}
        />
        <select
          className="list-filters__input"
          value={maxAge}
          onChange={(event) => setMaxAge(Number.parseInt(event.target.value, 10))}
          aria-label="Report age"
        >
          {MAX_AGE_OPTIONS.map((option) => (
            <option key={option.seconds} value={option.seconds}>
              Last {option.label}
            </option>
          ))}
        </select>
        {defaultCallsign !== '' && callsignInput !== defaultCallsign ? (
          <button
            className="button button--secondary button--list-action"
            type="button"
            onClick={() => setCallsignInput(defaultCallsign)}
          >
            My call
          </button>
        ) : null}
      </div>

      {bandSummary.length > 0 ? (
        <div className="rbn-bands" role="group" aria-label="Filter by band">
          <button
            type="button"
            className={bandFilter === '' ? 'rbn-band rbn-band--active' : 'rbn-band'}
            onClick={() => setBandFilter('')}
          >
            All bands
          </button>
          {bandSummary.map((entry) => (
            <button
              key={entry.band}
              type="button"
              className={bandFilter === entry.band ? 'rbn-band rbn-band--active' : 'rbn-band'}
              onClick={() => setBandFilter((current) => (current === entry.band ? '' : entry.band))}
            >
              <strong>{entry.band || '?'}</strong> {entry.skimmerCount}× · max {entry.bestSnr} dB
            </button>
          ))}
        </div>
      ) : null}

      {rbn.status === 'error' ? <p className="submission-message submission-message--error">{rbn.message}</p> : null}

      <RbnMap points={mapPoints} home={home} fitKey={`${queryKey}|${bandFilter}`} />
      <p className="rbn-map-note">
        Positions are 4-character grid squares (about 1° × 2°) from HamQTH.
        {homeLocator === null ? ' Set a QTH profile with a locator in Settings to show your exact position.' : ''}
      </p>

      <div className="cluster-table rbn-table">
        <div className={`cluster-table__head ${tableRowClass}`}>
          <span>Skimmer</span>
          <span>Grid</span>
          <span>Dist</span>
          <span>SNR</span>
          <span>Freq</span>
          <span>Band</span>
          {showHeardCall ? <span>Heard call</span> : null}
          <span>Age</span>
        </div>

        {emptyMessage !== '' ? (
          <div className="cluster-table__empty">{emptyMessage}</div>
        ) : (
          visibleRows.map((row) => {
            const location = locations[row.skimmer];
            const distance =
              location && homePosition !== null ? `${Math.round(distanceKm(homePosition, location))} km` : '-';

            return (
              <div key={row.id} className={`cluster-table__row ${tableRowClass}`}>
                <span className="rbn-table__call">{row.skimmer}</span>
                <span title={location ? `Position source: ${location.source}` : undefined}>
                  {location?.grid ?? '-'}
                  {location?.source === 'dxcc' ? ' ≈' : ''}
                </span>
                <span>{distance}</span>
                <span>
                  <span className="rbn-snr" style={{ background: snrColor(row.snr) }}>
                    {row.snr} dB
                  </span>
                </span>
                <span>{row.frequency.toFixed(1)}</span>
                <span>
                  {row.band} {row.mode}
                </span>
                {showHeardCall ? <span>{row.dxcall}</span> : null}
                <span>{formatAge(row.age)}</span>
              </div>
            );
          })
        )}
      </div>
    </section>
  );
}
