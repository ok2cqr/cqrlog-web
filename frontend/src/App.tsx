import { useDeferredValue, useEffect, useRef, useState } from 'react';
import type { Dispatch, KeyboardEvent as ReactKeyboardEvent, SetStateAction } from 'react';
import {
  createProfile,
  createLogEntry,
  createNote,
  deleteProfile,
  getAuthStatus,
  getCallsignContext,
  getDxClusterFeed,
  getDxcc,
  getFrontendConfig,
  getLogEntries,
  getLogEntry,
  getProfiles,
  getRadioState,
  getSolarData,
  login,
  logout,
  setOnUnauthorized,
  updateProfile,
  updateLogEntry,
  updateNote,
} from './api';
import type {
  ClubMembership,
  DxccData,
  LogEntryListItem,
  LogEntryPayload,
  LogEntryResponse,
  Profile,
  RecentQso,
} from './types';

type FormState = {
  band: string;
  mode: string;
  frequency: string;
  power: string;
  callsign: string;
  rstReceived: string;
  rstSent: string;
  name: string;
  qth: string;
  grid: string;
  state: string;
  county: string;
  qslSent: string;
  qslReceived: string;
  award: string;
  adif: string;
  continent: string;
  remarks: string;
  qslVia: string;
  callsignNote: string;
  waz: string;
  itu: string;
  iota: string;
  qsoDate: string;
  timeOn: string;
  timeOff: string;
  offline: boolean;
  clubNumber1: string;
  clubNumber2: string;
  clubNumber3: string;
  clubNumber4: string;
  clubNumber5: string;
};

type LookupState = {
  status: 'idle' | 'loading' | 'ready' | 'error';
  message: string;
};

type DxccState = {
  status: 'idle' | 'loading' | 'ready' | 'error';
  data: DxccData | null;
  message: string;
};

type RadioSyncState = 'idle' | 'online' | 'offline';
type ViewMode = 'entry' | 'list' | 'settings' | 'cluster' | 'contest';
type ThemePreference = 'auto' | 'light' | 'dark';
type ResolvedTheme = 'light' | 'dark';

type FrontendSettings = {
  theme: ThemePreference;
  defaultProfileId: number | null;
  showHiddenProfiles: boolean;
};

type RadioSyncConfig = {
  url: string;
  pollIntervalSeconds: number;
};

type EntryArrowElement = HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement;

type EntryArrowField =
  | 'callsign'
  | 'rstReceived'
  | 'rstSent'
  | 'name'
  | 'qth'
  | 'grid'
  | 'state'
  | 'county'
  | 'award'
  | 'remarks'
  | 'qslVia'
  | 'callsignNote'
  | 'waz'
  | 'itu'
  | 'iota'
  | 'qslSent'
  | 'qslReceived';

type ProfileState = {
  status: 'idle' | 'loading' | 'ready' | 'error';
  items: Profile[];
  message: string;
};

type ProfileFormState = {
  number: string;
  locator: string;
  qth: string;
  rig: string;
  remarks: string;
  visible: boolean;
};

type ProfileDialogState = {
  status: 'closed' | 'ready' | 'saving';
  mode: 'create' | 'edit';
  profileId: number | null;
  form: ProfileFormState | null;
  message: string;
};

type QsoListState = {
  status: 'idle' | 'loading' | 'ready' | 'error';
  items: LogEntryListItem[];
  totalCount: number;
  page: number;
  perPage: number;
  totalPages: number;
  message: string;
};

type ContestFormState = {
  callsign: string;
  rstSent: string;
  serialSent: string;
  msgSent: string;
  rstReceived: string;
  serialReceived: string;
  msgReceived: string;
};

type ContestLookupState = {
  status: 'idle' | 'loading' | 'ready' | 'error';
  message: string;
  recentQsos: RecentQso[];
  recentQsoCount: number;
};

type ContestSettings = {
  contestName: string;
  serialSent: string;
  autoIncrement: boolean;
};

type DxClusterItem = {
  id: string;
  spotter: string;
  frequency: string;
  dx: string;
  info: string;
  spottedAt: string;
  lotw: string;
  eqsl: string;
  continent: string;
  band: string;
  country: string;
  adif: string;
};

type DxClusterState = {
  status: 'idle' | 'loading' | 'ready' | 'error';
  items: DxClusterItem[];
  message: string;
  lastLoadedAt: string | null;
  solarSummary: string;
};

type EditLogEntryFormState = {
  qsoDate: string;
  timeOn: string;
  timeOff: string;
  callsign: string;
  frequency: string;
  band: string;
  mode: string;
  rstSent: string;
  rstReceived: string;
  name: string;
  qth: string;
  grid: string;
  state: string;
  county: string;
  award: string;
  remarks: string;
  qslSent: string;
  qslReceived: string;
  qslVia: string;
  callsignNote: string;
  iota: string;
  power: string;
  itu: string;
  waz: string;
  continent: string;
  adif: string;
  clubNumber1: string;
  clubNumber2: string;
  clubNumber3: string;
  clubNumber4: string;
  clubNumber5: string;
};

type EditDialogState = {
  status: 'closed' | 'loading' | 'ready' | 'saving' | 'error';
  entryId: number | null;
  originalCallsign: string;
  currentDxccRef: string | null;
  previewDxccRef: string | null;
  previewClubs: ClubMembership[];
  knownNoteId: number | null;
  callsignNoteDirty: boolean;
  previewMessage: string;
  form: EditLogEntryFormState | null;
  message: string;
};

const STORAGE_KEYS = {
  band: 'cqrlog.band',
  mode: 'cqrlog.mode',
  frequency: 'cqrlog.frequency',
  power: 'cqrlog.power',
  settings: 'cqrlog.settings',
  contest: 'cqrlog.contest',
} as const;

const DEFAULT_FRONTEND_SETTINGS: FrontendSettings = {
  theme: 'auto',
  defaultProfileId: null,
  showHiddenProfiles: false,
};

const bandOptions = ['160M', '80M', '60M', '40M', '30M', '20M', '17M', '15M', '12M', '10M', '6M', '2M'];
const modeOptions = ['CW', 'SSB', 'FT8', 'RTTY', 'AM', 'FM'];
const qslOptions = ['', 'Y', 'N', 'R'];
const MIN_RADIO_POLL_INTERVAL_SECONDS = 1;
const DEFAULT_RADIO_SYNC_CONFIG: RadioSyncConfig = {
  url: '',
  pollIntervalSeconds: 2,
};
const ENTRY_ARROW_NAV_ORDER: EntryArrowField[] = [
  'callsign',
  'rstReceived',
  'rstSent',
  'name',
  'qth',
  'grid',
  'state',
  'county',
  'award',
  'remarks',
  'qslVia',
  'callsignNote',
  'waz',
  'itu',
  'iota',
  'qslSent',
  'qslReceived',
];
const DX_CLUSTER_URL = 'https://www.hamqth.com/dxc_csv.php?limit=10';
const DX_CLUSTER_POLL_INTERVAL_MS = 20_000;
const defaultFrequencyByBand: Record<string, string> = {
  '160M': '1.8250',
  '80M': '3.5250',
  '60M': '5.5350',
  '40M': '7.0250',
  '30M': '10.1200',
  '20M': '14.0250',
  '17M': '18.0680',
  '15M': '21.0250',
  '12M': '24.8900',
  '10M': '28.0250',
  '6M': '50.3130',
  '2M': '144.1000',
};

const CZECH_NUMBER_ROW_MAP: Record<string, string> = {
  '+': '1',
  'ě': '2',
  'Ě': '2',
  'š': '3',
  'Š': '3',
  'č': '4',
  'Č': '4',
  'ř': '5',
  'Ř': '5',
  'ž': '6',
  'Ž': '6',
  'ý': '7',
  'Ý': '7',
  'á': '8',
  'Á': '8',
  'í': '9',
  'Í': '9',
  'é': '0',
  'É': '0',
};

type InitialFrontendSettingsState = {
  settings: FrontendSettings;
};

function pad(value: number): string {
  return value.toString().padStart(2, '0');
}

function formatDateForInput(date: Date): string {
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

function formatTimeForInput(date: Date): string {
  return `${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

function formatDateTimeLabel(date: Date): string {
  return `${formatDateForInput(date)} ${formatTimeForInput(date)}`;
}

function createInitialFormState(): FormState {
  const now = new Date();
  const date = formatDateForInput(now);
  const time = formatTimeForInput(now);
  const storedBand = readStoredValue(STORAGE_KEYS.band, '40M');
  const storedMode = readStoredValue(STORAGE_KEYS.mode, 'CW');
  const initialBand = bandOptions.includes(storedBand) ? storedBand : '40M';
  const initialMode = modeOptions.includes(storedMode) ? storedMode : 'CW';
  const initialFrequency = formatFrequencyInputValue(readStoredValue(
    STORAGE_KEYS.frequency,
    defaultFrequencyByBand[initialBand] ?? '7.0250',
  ));
  const initialPower = readStoredValue(STORAGE_KEYS.power, '100');

  return {
    band: initialBand,
    mode: initialMode,
    frequency: initialFrequency,
    power: initialPower,
    callsign: '',
    rstReceived: '599',
    rstSent: '599',
    name: '',
    qth: '',
    grid: '',
    state: '',
    county: '',
    qslSent: '',
    qslReceived: '',
    award: '',
    adif: '',
    continent: '',
    remarks: '',
    qslVia: '',
    callsignNote: '',
    waz: '',
    itu: '',
    iota: '',
    qsoDate: date,
    timeOn: time,
    timeOff: time,
    offline: false,
    clubNumber1: '',
    clubNumber2: '',
    clubNumber3: '',
    clubNumber4: '',
    clubNumber5: '',
  };
}

function normalizeOptionalString(value: string): string | null {
  const trimmed = value.trim();
  return trimmed === '' ? null : trimmed;
}

function normalizeOptionalInteger(value: string): number | null {
  const trimmed = value.trim();

  if (trimmed === '') {
    return null;
  }

  const parsed = Number.parseInt(trimmed, 10);

  if (Number.isNaN(parsed)) {
    throw new Error(`"${value}" is not a valid integer.`);
  }

  return parsed;
}

function normalizeRequiredFrequency(value: string): number {
  const parsed = Number.parseFloat(value.trim());

  if (!Number.isFinite(parsed) || parsed <= 0) {
    throw new Error('Frequency must be a positive number.');
  }

  return parsed;
}

function formatFrequencyInputValue(value: string): string {
  const parsed = Number.parseFloat(value.trim());

  return Number.isFinite(parsed) && parsed > 0 ? parsed.toFixed(4) : value;
}

function readStoredValue(key: string, fallback: string): string {
  if (typeof window === 'undefined') {
    return fallback;
  }

  const value = window.localStorage.getItem(key);
  return value && value.trim() !== '' ? value : fallback;
}

function readInitialFrontendSettingsState(): InitialFrontendSettingsState {
  if (typeof window === 'undefined') {
    return {
      settings: DEFAULT_FRONTEND_SETTINGS,
    };
  }

  const rawValue = window.localStorage.getItem(STORAGE_KEYS.settings);

  if (rawValue === null) {
    return {
      settings: DEFAULT_FRONTEND_SETTINGS,
    };
  }

  try {
    const parsed = JSON.parse(rawValue);
    const parsedProfileId =
      typeof parsed.defaultProfileId === 'number' && Number.isInteger(parsed.defaultProfileId)
        ? parsed.defaultProfileId
        : null;
    return {
      settings: {
        theme: parsed.theme === 'dark' || parsed.theme === 'light' || parsed.theme === 'auto'
          ? parsed.theme
          : DEFAULT_FRONTEND_SETTINGS.theme,
        defaultProfileId: parsedProfileId,
        showHiddenProfiles:
          typeof parsed.showHiddenProfiles === 'boolean'
            ? parsed.showHiddenProfiles
            : DEFAULT_FRONTEND_SETTINGS.showHiddenProfiles,
      },
    };
  } catch {
    return {
      settings: DEFAULT_FRONTEND_SETTINGS,
    };
  }
}

const DEFAULT_CONTEST_SETTINGS: ContestSettings = {
  contestName: '',
  serialSent: '001',
  autoIncrement: true,
};

function readInitialContestSettings(): ContestSettings {
  if (typeof window === 'undefined') {
    return DEFAULT_CONTEST_SETTINGS;
  }

  const rawValue = window.localStorage.getItem(STORAGE_KEYS.contest);

  if (rawValue === null) {
    return DEFAULT_CONTEST_SETTINGS;
  }

  try {
    const parsed = JSON.parse(rawValue);

    return {
      contestName: typeof parsed.contestName === 'string' ? parsed.contestName : DEFAULT_CONTEST_SETTINGS.contestName,
      serialSent: typeof parsed.serialSent === 'string' && parsed.serialSent.trim() !== ''
        ? parsed.serialSent
        : DEFAULT_CONTEST_SETTINGS.serialSent,
      autoIncrement: typeof parsed.autoIncrement === 'boolean'
        ? parsed.autoIncrement
        : DEFAULT_CONTEST_SETTINGS.autoIncrement,
    };
  } catch {
    return DEFAULT_CONTEST_SETTINGS;
  }
}

function defaultRstForMode(mode: string): string {
  return mode === 'CW' ? '599' : '59';
}

function incrementSerial(value: string): string {
  const parsed = Number.parseInt(value.replace(/\D+/g, ''), 10);
  const next = (Number.isNaN(parsed) ? 0 : parsed) + 1;

  return next.toString().padStart(Math.max(value.trim().length, 3), '0');
}

function normalizeCzechNumberRow(value: string): string {
  return Array.from(value)
    .map((character) => CZECH_NUMBER_ROW_MAP[character] ?? character)
    .join('');
}

function normalizeDigitsOnly(value: string): string {
  return normalizeCzechNumberRow(value).replace(/\D+/g, '');
}

function normalizeCallsignInput(value: string): string {
  return normalizeCzechNumberRow(value).replace(/\./g, '/').toUpperCase();
}

function uppercaseFirstCharacter(value: string): string {
  if (value === '') {
    return value;
  }

  return value.charAt(0).toLocaleUpperCase() + value.slice(1);
}

function selectRstEditableCharacter(input: HTMLInputElement): void {
  const valueLength = input.value.length;

  if (valueLength <= 0) {
    return;
  }

  const selectionIndex = valueLength >= 2 ? 1 : 0;
  input.setSelectionRange(selectionIndex, Math.min(selectionIndex + 1, valueLength));
}

function getBandFromFrequency(value: string): string | null {
  const freq = Number.parseFloat(value.trim());

  if (!Number.isFinite(freq) || freq <= 0) {
    return null;
  }

  if (freq > 1.7 && freq < 2) return '160M';
  if (freq > 3.4 && freq < 4) return '80M';
  if (freq > 5.0 && freq < 6) return '60M';
  if (freq > 6.9 && freq < 7.3) return '40M';
  if (freq > 10 && freq < 11) return '30M';
  if (freq > 13 && freq < 15) return '20M';
  if (freq > 18 && freq < 19) return '17M';
  if (freq > 20 && freq < 22) return '15M';
  if (freq > 24 && freq < 25) return '12M';
  if (freq > 27 && freq < 30) return '10M';
  if (freq > 50 && freq < 55) return '6M';
  if (freq > 144 && freq < 149) return '2M';

  return null;
}

function applyClubMemberships(setForm: Dispatch<SetStateAction<FormState>>, clubs: ClubMembership[]): void {
  const slots = new Map(clubs.map((club) => [club.slot, club.number]));

  setForm((current) => ({
    ...current,
    clubNumber1: slots.get(1) ?? '',
    clubNumber2: slots.get(2) ?? '',
    clubNumber3: slots.get(3) ?? '',
    clubNumber4: slots.get(4) ?? '',
    clubNumber5: slots.get(5) ?? '',
  }));
}

function formatQsoDuration(startedAt: Date | null): string | null {
  if (startedAt === null) {
    return null;
  }

  const diffSeconds = Math.max(0, Math.floor((Date.now() - startedAt.getTime()) / 1000));
  const hours = Math.floor(diffSeconds / 3600);
  const minutes = Math.floor((diffSeconds % 3600) / 60);
  const seconds = diffSeconds % 60;

  return `${hours}h ${minutes}m ${seconds}s`;
}

function formatFrequency(value: number): string {
  return value.toFixed(4);
}

function formatRadioFrequency(value: number): string {
  return value.toFixed(4);
}

function formatOptionalNumber(value: number | null | undefined): string {
  return value == null ? '' : value.toString();
}

function normalizeRadioMode(value: string | null | undefined): string | null {
  if (typeof value !== 'string') {
    return null;
  }

  const normalized = value.trim().toUpperCase();

  if (normalized === '') {
    return null;
  }

  if (modeOptions.includes(normalized)) {
    return normalized;
  }

  if (normalized === 'USB' || normalized === 'LSB') return 'SSB';
  if (normalized === 'CWL' || normalized === 'CWU') return 'CW';
  if (normalized === 'DIGI' || normalized === 'DIGL' || normalized === 'DIGU' || normalized === 'FT4') return 'FT8';
  if (normalized.startsWith('RTTY')) return 'RTTY';
  if (normalized.endsWith('FM')) return 'FM';
  if (normalized.endsWith('AM')) return 'AM';

  return null;
}

function parseRadioLastSeen(value: number | string | null | undefined): number | null {
  if (typeof value === 'number' && Number.isFinite(value)) {
    return value < 1_000_000_000_000 ? value * 1000 : value;
  }

  if (typeof value !== 'string') {
    return null;
  }

  const trimmed = value.trim();

  if (trimmed === '') {
    return null;
  }

  const numericValue = Number.parseFloat(trimmed);

  if (Number.isFinite(numericValue)) {
    return numericValue < 1_000_000_000_000 ? numericValue * 1000 : numericValue;
  }

  const parsedDate = Date.parse(trimmed);
  return Number.isFinite(parsedDate) ? parsedDate : null;
}

function isRadioOnline(
  radioState: { lastseen?: number | string | null; last_seen?: number | string | null },
  pollIntervalSeconds: number,
): boolean {
  const lastSeenMs = parseRadioLastSeen(radioState.lastseen ?? radioState.last_seen);

  if (lastSeenMs === null) {
    return false;
  }

  const maxAgeMs = Math.max(MIN_RADIO_POLL_INTERVAL_SECONDS, pollIntervalSeconds) * 2000;
  return Date.now() - lastSeenMs <= maxAgeMs;
}

function applyRadioState(
  current: FormState,
  radioState: { freq?: number | string | null; mode?: string | null },
): FormState {
  const rawFrequency =
    typeof radioState.freq === 'number'
      ? radioState.freq
      : typeof radioState.freq === 'string'
        ? Number.parseFloat(radioState.freq.trim())
        : Number.NaN;

  if (!Number.isFinite(rawFrequency) || rawFrequency <= 0) {
    return current;
  }

  const nextFrequency = formatRadioFrequency(rawFrequency / 1000);
  const derivedBand = getBandFromFrequency(nextFrequency);
  const nextBand = derivedBand ?? current.band;
  const nextMode = normalizeRadioMode(radioState.mode) ?? current.mode;

  if (current.frequency === nextFrequency && current.band === nextBand && current.mode === nextMode) {
    return current;
  }

  return {
    ...current,
    frequency: nextFrequency,
    band: nextBand,
    mode: nextMode,
  };
}

function createEditLogEntryFormState(entry: LogEntryResponse): EditLogEntryFormState {
  return {
    qsoDate: entry.qsoDate,
    timeOn: entry.timeOn,
    timeOff: entry.timeOff ?? '',
    callsign: entry.callsign,
    frequency: formatFrequency(entry.frequency),
    band: entry.band ?? '',
    mode: entry.mode,
    rstSent: entry.rstSent ?? '',
    rstReceived: entry.rstReceived ?? '',
    name: entry.name ?? '',
    qth: entry.qth ?? '',
    grid: entry.grid ?? '',
    state: entry.state ?? '',
    county: entry.county ?? '',
    award: entry.award ?? '',
    remarks: entry.remarks ?? '',
    qslSent: entry.qslSent ?? '',
    qslReceived: entry.qslReceived ?? '',
    qslVia: entry.qslVia ?? '',
    callsignNote: '',
    iota: entry.iota ?? '',
    power: entry.power ?? '',
    itu: formatOptionalNumber(entry.itu),
    waz: formatOptionalNumber(entry.waz),
    continent: entry.continent ?? '',
    adif: formatOptionalNumber(entry.adif),
    clubNumber1: entry.clubNumber1 ?? '',
    clubNumber2: entry.clubNumber2 ?? '',
    clubNumber3: entry.clubNumber3 ?? '',
    clubNumber4: entry.clubNumber4 ?? '',
    clubNumber5: entry.clubNumber5 ?? '',
  };
}

function createProfileFormState(profile?: Profile): ProfileFormState {
  return {
    number: profile?.number.toString() ?? '',
    locator: profile?.locator ?? '',
    qth: profile?.qth ?? '',
    rig: profile?.rig ?? '',
    remarks: profile?.remarks ?? '',
    visible: profile?.visible ?? true,
  };
}

function getNextProfileNumber(profiles: Profile[]): number {
  return profiles.reduce((maxNumber, profile) => Math.max(maxNumber, profile.number), 0) + 1;
}

function parseDxClusterFeed(responseText: string): DxClusterItem[] {
  return responseText
    .split(/\r?\n/)
    .map((line) => line.trim())
    .filter((line) => line !== '')
    .map((line, index) => {
      const [
        spotter = '',
        frequency = '',
        dx = '',
        info = '',
        spottedAt = '',
        lotw = '',
        eqsl = '',
        continent = '',
        band = '',
        country = '',
        adif = '',
      ] = line.split('^');

      return {
        id: `${index}-${spotter}-${frequency}-${dx}-${spottedAt}`,
        spotter,
        frequency,
        dx,
        info,
        spottedAt,
        lotw,
        eqsl,
        continent,
        band,
        country,
        adif,
      };
    });
}

function parseSolarDataSummary(responseText: string): string {
  const [sfi = '-', a = '-', k = '-', gf = '-', ssn = '-', updatedAt = '-'] = responseText.trim().split('|');
  return `Solar data: A: ${a} | K: ${k} | SFI: ${sfi} | SSN: ${ssn} | GF: ${gf} | Updated: ${updatedAt}`;
}

export default function App() {
  const [authState, setAuthState] = useState<'checking' | 'logged-in' | 'logged-out' | 'session-expired'>('checking');
  const [authRequired, setAuthRequired] = useState(true);
  const [loginError, setLoginError] = useState('');
  const [loginForm, setLoginForm] = useState({ username: '', password: '' });

  useEffect(() => {
    getAuthStatus()
      .then((res) => {
        setAuthState(res.authenticated ? 'logged-in' : 'logged-out');
        setAuthRequired(res.authRequired);
      })
      .catch(() => setAuthState('logged-out'));
  }, []);

  useEffect(() => {
    setOnUnauthorized(() => {
      setAuthState((prev) => prev === 'logged-out' ? 'logged-out' : 'session-expired');
      setLoginError('');
      setLoginForm({ username: '', password: '' });
    });
    return () => setOnUnauthorized(null);
  }, []);

  async function handleLogin(event: React.FormEvent): Promise<void> {
    event.preventDefault();
    setLoginError('');
    try {
      const result = await login(loginForm.username, loginForm.password);
      if (result.authenticated) {
        setAuthState((prev) => {
          if (prev === 'session-expired') {
            setSubmitState({ status: 'idle', message: '' });
          }

          return 'logged-in';
        });
        setLoginForm({ username: '', password: '' });
      }
    } catch (err) {
      setLoginError(err instanceof Error ? err.message : 'Login failed.');
    }
  }

  async function handleLogout(): Promise<void> {
    try {
      await logout();
    } finally {
      setAuthState('logged-out');
    }
  }

  const DOUBLE_SHORTCUT_CLEAR_WINDOW_MS = 500;
  const initialFrontendSettingsStateRef = useRef<InitialFrontendSettingsState | null>(null);

  if (initialFrontendSettingsStateRef.current === null) {
    initialFrontendSettingsStateRef.current = readInitialFrontendSettingsState();
  }

  const [settings, setSettings] = useState<FrontendSettings>(() => initialFrontendSettingsStateRef.current?.settings ?? DEFAULT_FRONTEND_SETTINGS);
  const [systemTheme, setSystemTheme] = useState<ResolvedTheme>(() => {
    if (typeof window === 'undefined') {
      return 'light';
    }

    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  });
  const [radioSyncConfig, setRadioSyncConfig] = useState<RadioSyncConfig | null>(null);
  const [radioSyncState, setRadioSyncState] = useState<RadioSyncState>('idle');
  const [form, setForm] = useState<FormState>(() => createInitialFormState());
  const [lookup, setLookup] = useState<LookupState>({
    status: 'idle',
    message: 'Enter a callsign to load note, recent QSOs and club memberships.',
  });
  const [clubMemberships, setClubMemberships] = useState<ClubMembership[]>([]);
  const [recentQsos, setRecentQsos] = useState<RecentQso[]>([]);
  const [recentQsoCount, setRecentQsoCount] = useState(0);
  const [dxcc, setDxcc] = useState<DxccState>({
    status: 'idle',
    data: null,
    message: 'DXCC lookup idle.',
  });
  const [profiles, setProfiles] = useState<ProfileState>({
    status: 'idle',
    items: [],
    message: 'Open Settings to load QTH profiles.',
  });
  const [profilesReloadKey, setProfilesReloadKey] = useState(0);
  const [profileDialog, setProfileDialog] = useState<ProfileDialogState>({
    status: 'closed',
    mode: 'create',
    profileId: null,
    form: null,
    message: '',
  });
  const [viewMode, setViewMode] = useState<ViewMode>('entry');
  const [qsoList, setQsoList] = useState<QsoListState>({
    status: 'idle',
    items: [],
    totalCount: 0,
    page: 1,
    perPage: 50,
    totalPages: 1,
    message: 'Open QSO list to load records.',
  });
  const [dxCluster, setDxCluster] = useState<DxClusterState>({
    status: 'idle',
    items: [],
    message: 'Open DX Cluster to load spots.',
    lastLoadedAt: null,
    solarSummary: '',
  });
  const [dxClusterReloadKey, setDxClusterReloadKey] = useState(0);
  const [qsoListReloadKey, setQsoListReloadKey] = useState(0);
  const [qsoListFeedback, setQsoListFeedback] = useState<{ status: 'idle' | 'saved' | 'error'; message: string }>({
    status: 'idle',
    message: '',
  });
  const initialContestSettingsRef = useRef<ContestSettings | null>(null);

  if (initialContestSettingsRef.current === null) {
    initialContestSettingsRef.current = readInitialContestSettings();
  }

  const [contestName, setContestName] = useState(() => initialContestSettingsRef.current?.contestName ?? '');
  const [contestAutoIncrement, setContestAutoIncrement] = useState(
    () => initialContestSettingsRef.current?.autoIncrement ?? true,
  );
  const [contestForm, setContestForm] = useState<ContestFormState>(() => {
    const storedMode = readStoredValue(STORAGE_KEYS.mode, 'CW');

    return {
      callsign: '',
      rstSent: defaultRstForMode(storedMode),
      serialSent: initialContestSettingsRef.current?.serialSent ?? '001',
      msgSent: '',
      rstReceived: defaultRstForMode(storedMode),
      serialReceived: '',
      msgReceived: '',
    };
  });
  const [contestLookup, setContestLookup] = useState<ContestLookupState>({
    status: 'idle',
    message: 'Leave the Call field to check previous QSOs.',
    recentQsos: [],
    recentQsoCount: 0,
  });
  const [contestLookupCallsign, setContestLookupCallsign] = useState('');
  const [contestQsoList, setContestQsoList] = useState<QsoListState>({
    status: 'idle',
    items: [],
    totalCount: 0,
    page: 1,
    perPage: 50,
    totalPages: 1,
    message: 'Set a contest name to list its QSOs.',
  });
  const [contestQsoReloadKey, setContestQsoReloadKey] = useState(0);
  const [contestSubmitState, setContestSubmitState] = useState<{
    status: 'idle' | 'saving' | 'saved' | 'error';
    message: string;
  }>({
    status: 'idle',
    message: '',
  });
  const [editDialog, setEditDialog] = useState<EditDialogState>({
    status: 'closed',
    entryId: null,
    originalCallsign: '',
    currentDxccRef: null,
    previewDxccRef: null,
    previewClubs: [],
    knownNoteId: null,
    callsignNoteDirty: false,
    previewMessage: '',
    form: null,
    message: '',
  });
  const [submitState, setSubmitState] = useState<{ status: 'idle' | 'saving' | 'saved' | 'error'; message: string }>({
    status: 'idle',
    message: '',
  });
  const [knownNoteId, setKnownNoteId] = useState<number | null>(null);
  const [callsignNoteDirty, setCallsignNoteDirty] = useState(false);
  const [qsoStarted, setQsoStarted] = useState(false);
  const [qsoStartedAt, setQsoStartedAt] = useState<Date | null>(null);
  const normalizedCallsign = form.callsign.trim().toUpperCase();
  const deferredEditCallsign = useDeferredValue(editDialog.form?.callsign.trim().toUpperCase() ?? '');
  const [lookupCallsign, setLookupCallsign] = useState('');
  const deferredLookupCallsign = useDeferredValue(lookupCallsign);
  const callsignInputRef = useRef<HTMLInputElement | null>(null);
  const entryArrowFieldRefs = useRef<Partial<Record<EntryArrowField, EntryArrowElement | null>>>({});
  const lastEscapeAtRef = useRef(0);
  const lastBackslashAtRef = useRef(0);
  const lookupKeyRef = useRef<string>('');
  const dxccLookupKeyRef = useRef<string>('');
  const editDxccLookupKeyRef = useRef<string>('');
  const contestCallsignInputRef = useRef<HTMLInputElement | null>(null);
  const contestLookupKeyRef = useRef<string>('');
  const selectableProfiles = settings.showHiddenProfiles
    ? profiles.items
    : profiles.items.filter((profile) => profile.visible);
  const selectedProfile =
    settings.defaultProfileId === null
      ? null
      : profiles.items.find((profile) => profile.id === settings.defaultProfileId) ?? null;

  useEffect(() => {
    callsignInputRef.current?.focus();
  }, []);

  useEffect(() => {
    if (viewMode !== 'entry') {
      return undefined;
    }

    const timeoutId = window.setTimeout(() => {
      callsignInputRef.current?.focus();
    }, 0);

    return () => window.clearTimeout(timeoutId);
  }, [viewMode]);

  useEffect(() => {
    if (viewMode !== 'contest') {
      return undefined;
    }

    const timeoutId = window.setTimeout(() => {
      contestCallsignInputRef.current?.focus();
    }, 0);

    return () => window.clearTimeout(timeoutId);
  }, [viewMode]);

  useEffect(() => {
    if (viewMode !== 'entry' || profileDialog.status !== 'closed' || editDialog.status !== 'closed') {
      return undefined;
    }

    const handleWindowKeyDown = (event: KeyboardEvent) => {
      if (event.altKey || event.ctrlKey || event.metaKey || event.shiftKey || event.repeat) {
        return;
      }

      const now = Date.now();

      if (event.key === 'Escape') {
        if (now - lastEscapeAtRef.current <= DOUBLE_SHORTCUT_CLEAR_WINDOW_MS) {
          lastEscapeAtRef.current = 0;
          lastBackslashAtRef.current = 0;
          event.preventDefault();
          resetEntryForm();
          return;
        }

        lastEscapeAtRef.current = now;
        lastBackslashAtRef.current = 0;
        return;
      }

      if (event.key === '\\') {
        if (now - lastBackslashAtRef.current <= DOUBLE_SHORTCUT_CLEAR_WINDOW_MS) {
          lastBackslashAtRef.current = 0;
          lastEscapeAtRef.current = 0;
          event.preventDefault();
          resetEntryForm();
          return;
        }

        lastBackslashAtRef.current = now;
        lastEscapeAtRef.current = 0;
        return;
      }

      if (lastEscapeAtRef.current !== 0 || lastBackslashAtRef.current !== 0) {
        lastEscapeAtRef.current = 0;
        lastBackslashAtRef.current = 0;
      }
    };

    window.addEventListener('keydown', handleWindowKeyDown);

    return () => {
      window.removeEventListener('keydown', handleWindowKeyDown);
    };
  }, [editDialog.status, profileDialog.status, viewMode]);

  useEffect(() => {
    if (form.offline) {
      return undefined;
    }

    const syncDateTime = () => {
      const now = new Date();
      const date = formatDateForInput(now);
      const time = formatTimeForInput(now);

      setForm((current) => {
        if (qsoStarted) {
          return {
            ...current,
            timeOff: time,
          };
        }

        return {
          ...current,
          qsoDate: date,
          timeOn: time,
          timeOff: time,
        };
      });
    };

    syncDateTime();

    const intervalId = window.setInterval(syncDateTime, 1000);

    return () => window.clearInterval(intervalId);
  }, [form.offline, qsoStarted]);

  useEffect(() => {
    window.localStorage.setItem(STORAGE_KEYS.band, form.band);
  }, [form.band]);

  useEffect(() => {
    window.localStorage.setItem(STORAGE_KEYS.mode, form.mode);
  }, [form.mode]);

  useEffect(() => {
    window.localStorage.setItem(STORAGE_KEYS.frequency, form.frequency);
  }, [form.frequency]);

  useEffect(() => {
    window.localStorage.setItem(STORAGE_KEYS.power, form.power);
  }, [form.power]);

  useEffect(() => {
    window.localStorage.setItem(STORAGE_KEYS.settings, JSON.stringify(settings));
  }, [settings]);

  useEffect(() => {
    window.localStorage.setItem(
      STORAGE_KEYS.contest,
      JSON.stringify({
        contestName,
        serialSent: contestForm.serialSent,
        autoIncrement: contestAutoIncrement,
      }),
    );
  }, [contestAutoIncrement, contestForm.serialSent, contestName]);

  useEffect(() => {
    if (authState !== 'logged-in') {
      return;
    }

    let cancelled = false;

    void getFrontendConfig()
      .then((config) => {
        if (cancelled) {
          return;
        }

        const nextConfig: RadioSyncConfig = {
          url:
            typeof config.radioSyncDefaultUrl === 'string' && config.radioSyncDefaultUrl.trim() !== ''
              ? config.radioSyncDefaultUrl.trim()
              : DEFAULT_RADIO_SYNC_CONFIG.url,
          pollIntervalSeconds:
            typeof config.radioSyncDefaultPollIntervalSeconds === 'number'
              ? Math.max(MIN_RADIO_POLL_INTERVAL_SECONDS, Math.trunc(config.radioSyncDefaultPollIntervalSeconds))
              : DEFAULT_RADIO_SYNC_CONFIG.pollIntervalSeconds,
        };

        setRadioSyncConfig(nextConfig);
      })
      .catch((error) => {
        if (!cancelled) {
          setRadioSyncConfig(DEFAULT_RADIO_SYNC_CONFIG);
        }

        console.error('Unable to load frontend config.', error);
      });

    return () => {
      cancelled = true;
    };
  }, [authState]);

  useEffect(() => {
    if (radioSyncConfig === null) {
      setRadioSyncState('idle');
      return undefined;
    }

    const radioUrl = radioSyncConfig.url.trim();

    if (radioUrl === '') {
      setRadioSyncState('idle');
      return undefined;
    }

    const intervalMs = Math.max(MIN_RADIO_POLL_INTERVAL_SECONDS, radioSyncConfig.pollIntervalSeconds) * 1000;
    let cancelled = false;
    let requestInFlight = false;

    const syncRadioState = async () => {
      if (requestInFlight) {
        return;
      }

      requestInFlight = true;

      try {
        const radioState = await getRadioState(radioUrl);

        if (cancelled) {
          return;
        }

        const radioOnline = isRadioOnline(radioState, radioSyncConfig.pollIntervalSeconds);

        setRadioSyncState(radioOnline ? 'online' : 'offline');

        if (radioOnline) {
          setForm((current) => applyRadioState(current, radioState));
        }
      } catch (error) {
        if (!cancelled) {
          setRadioSyncState('offline');
          console.error('Unable to load radio state.', error);
        }
      } finally {
        requestInFlight = false;
      }
    };

    void syncRadioState();

    const intervalId = window.setInterval(() => {
      void syncRadioState();
    }, intervalMs);

    return () => {
      cancelled = true;
      window.clearInterval(intervalId);
    };
  }, [radioSyncConfig]);

  useEffect(() => {
    setForm((current) => ({
      ...current,
      rstSent: form.mode === 'CW' ? '599' : '59',
      rstReceived: form.mode === 'CW' ? '599' : '59',
    }));
  }, [form.mode]);

  useEffect(() => {
    setContestForm((current) => ({
      ...current,
      rstSent: defaultRstForMode(form.mode),
      rstReceived: defaultRstForMode(form.mode),
    }));
  }, [form.mode]);

  useEffect(() => {
    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    const updateTheme = (event?: MediaQueryList | MediaQueryListEvent) => {
      setSystemTheme(event?.matches ?? mediaQuery.matches ? 'dark' : 'light');
    };

    updateTheme(mediaQuery);
    mediaQuery.addEventListener('change', updateTheme);

    return () => {
      mediaQuery.removeEventListener('change', updateTheme);
    };
  }, []);

  useEffect(() => {
    const resolvedTheme = settings.theme === 'auto' ? systemTheme : settings.theme;

    document.documentElement.dataset.theme = resolvedTheme;

    return () => {
      delete document.documentElement.dataset.theme;
    };
  }, [settings.theme, systemTheme]);

  useEffect(() => {
    if (authState !== 'logged-in') {
      return;
    }

    let cancelled = false;

    setProfiles((current) => ({
      ...current,
      status: 'loading',
      message: 'Loading QTH profiles…',
    }));

    void getProfiles()
      .then((response) => {
        if (cancelled) {
          return;
        }

        setProfiles({
          status: 'ready',
          items: response.items,
          message: response.items.length === 0 ? 'No QTH profiles found.' : '',
        });
      })
      .catch((error) => {
        if (cancelled) {
          return;
        }

        setProfiles({
          status: 'error',
          items: [],
          message: error instanceof Error ? error.message : 'Unable to load QTH profiles.',
        });
      });

    return () => {
      cancelled = true;
    };
  }, [authState, profilesReloadKey]);

  useEffect(() => {
    if (profiles.status !== 'ready') {
      return;
    }

    if (settings.defaultProfileId === null) {
      return;
    }

    if (selectedProfile !== null) {
      return;
    }

    setSettings((current) => ({
      ...current,
      defaultProfileId: null,
    }));
  }, [profiles.status, selectedProfile, settings.defaultProfileId]);

  useEffect(() => {
    if (settings.showHiddenProfiles || selectedProfile === null || selectedProfile.visible) {
      return;
    }

    setSettings((current) => ({
      ...current,
      defaultProfileId: null,
    }));
  }, [selectedProfile, settings.showHiddenProfiles]);

  useEffect(() => {
    const uppercaseCallsign = lookupCallsign;

    if (uppercaseCallsign === '') {
      setLookup({
        status: 'idle',
        message: 'Enter a callsign to load note, recent QSOs and club memberships.',
      });
      setClubMemberships([]);
      setRecentQsos([]);
      setRecentQsoCount(0);
      setKnownNoteId(null);
      applyClubMemberships(setForm, []);

      if (!callsignNoteDirty) {
        setForm((current) => ({
          ...current,
          callsignNote: '',
        }));
      }

      return undefined;
    }

    const currentLookupKey = `${uppercaseCallsign}|${form.qsoDate}`;
    lookupKeyRef.current = currentLookupKey;

    setLookup({
      status: 'loading',
      message: 'Loading callsign context…',
    });

    const timeoutId = window.setTimeout(async () => {
      try {
        const context = await getCallsignContext(uppercaseCallsign, form.qsoDate || undefined);

        if (lookupKeyRef.current !== currentLookupKey) {
          return;
        }

        setClubMemberships(context.clubs);
        setRecentQsos(context.recentQsos);
        setRecentQsoCount(context.recentQsoCount);
        setKnownNoteId(context.note?.id ?? null);
        applyClubMemberships(setForm, context.clubs);
        setLookup({
          status: 'ready',
          message:
            context.recentQsoCount > 0
              ? `Found ${context.recentQsoCount} previous QSO(s) and ${context.clubs.length} club record(s).`
              : `No previous QSO found. Club records: ${context.clubs.length}.`,
        });

        setForm((current) => ({
          ...current,
          name: current.name || context.autofill.name || '',
          qth: current.qth || context.autofill.qth || '',
          award: current.award || context.autofill.award || '',
          qslVia: current.qslVia || context.autofill.qslVia || '',
          state: current.state || context.autofill.state || '',
          county: current.county || context.autofill.county || '',
          grid: current.grid || context.autofill.grid || '',
          iota: current.iota || context.autofill.iota || '',
          waz: current.waz || (context.autofill.waz?.toString() ?? ''),
          itu: current.itu || (context.autofill.itu?.toString() ?? ''),
        }));

        if (!callsignNoteDirty) {
          setForm((current) => ({
            ...current,
            callsignNote: context.note?.remarks ?? '',
          }));
        }
      } catch (error) {
        if (lookupKeyRef.current !== currentLookupKey) {
          return;
        }

        setClubMemberships([]);
        setRecentQsos([]);
        setRecentQsoCount(0);
        setKnownNoteId(null);
        applyClubMemberships(setForm, []);
        setLookup({
          status: 'error',
          message: error instanceof Error ? error.message : 'Unable to load callsign context.',
        });
      }
    }, 250);

    return () => window.clearTimeout(timeoutId);
  }, [callsignNoteDirty, form.qsoDate, lookupCallsign]);

  useEffect(() => {
    if (contestLookupCallsign === '') {
      setContestLookup({
        status: 'idle',
        message: 'Leave the Call field to check previous QSOs.',
        recentQsos: [],
        recentQsoCount: 0,
      });

      return undefined;
    }

    const currentLookupKey = `${contestLookupCallsign}|${form.qsoDate}`;
    contestLookupKeyRef.current = currentLookupKey;

    setContestLookup((current) => ({
      ...current,
      status: 'loading',
      message: 'Checking previous QSOs…',
    }));

    const timeoutId = window.setTimeout(async () => {
      try {
        const context = await getCallsignContext(contestLookupCallsign, form.qsoDate || undefined);

        if (contestLookupKeyRef.current !== currentLookupKey) {
          return;
        }

        setContestLookup({
          status: 'ready',
          message:
            context.recentQsoCount > 0
              ? `Found ${context.recentQsoCount} previous QSO(s) with ${context.callsign}.`
              : `No previous QSO with ${context.callsign}.`,
          recentQsos: context.recentQsos,
          recentQsoCount: context.recentQsoCount,
        });
      } catch (error) {
        if (contestLookupKeyRef.current !== currentLookupKey) {
          return;
        }

        setContestLookup({
          status: 'error',
          message: error instanceof Error ? error.message : 'Unable to check previous QSOs.',
          recentQsos: [],
          recentQsoCount: 0,
        });
      }
    }, 250);

    return () => window.clearTimeout(timeoutId);
  }, [contestLookupCallsign, form.qsoDate]);

  useEffect(() => {
    const uppercaseCallsign = deferredLookupCallsign;

    if (uppercaseCallsign === '') {
      setDxcc({
        status: 'idle',
        data: null,
        message: 'DXCC lookup idle.',
      });
      setForm((current) => ({
        ...current,
        adif: '',
        continent: '',
      }));

      return undefined;
    }

    dxccLookupKeyRef.current = uppercaseCallsign;
    setDxcc({
      status: 'loading',
      data: null,
      message: 'Loading DXCC…',
    });

    const timeoutId = window.setTimeout(async () => {
      try {
        const data = await getDxcc(uppercaseCallsign);

        if (dxccLookupKeyRef.current !== uppercaseCallsign) {
          return;
        }

        setDxcc({
          status: 'ready',
          data,
          message: data.details ?? data.name ?? 'DXCC resolved.',
        });
        setForm((current) => ({
          ...current,
          waz: data.waz?.toString() ?? current.waz,
          itu: data.itu?.toString() ?? current.itu,
          adif: data.adif?.toString() ?? '',
          continent: data.continent ?? '',
        }));
      } catch (error) {
        if (dxccLookupKeyRef.current !== uppercaseCallsign) {
          return;
        }

        setDxcc({
          status: 'error',
          data: null,
          message: error instanceof Error ? error.message : 'DXCC lookup failed.',
        });
      }
    }, 250);

    return () => window.clearTimeout(timeoutId);
  }, [deferredLookupCallsign]);

  useEffect(() => {
    if (editDialog.form === null) {
      editDxccLookupKeyRef.current = '';
      return undefined;
    }

    const uppercaseCallsign = deferredEditCallsign;

    if (uppercaseCallsign === '') {
      setEditDialog((current) => {
        if (current.form === null) {
          return current;
        }

        return {
          ...current,
          previewDxccRef: null,
          previewClubs: [],
          knownNoteId: null,
          previewMessage: 'Enter a callsign to preview DXCC.',
        };
      });

      return undefined;
    }

    editDxccLookupKeyRef.current = uppercaseCallsign;

    const timeoutId = window.setTimeout(async () => {
      try {
        const [data, context] = await Promise.all([
          getDxcc(uppercaseCallsign),
          getCallsignContext(uppercaseCallsign, editDialog.form?.qsoDate || undefined),
        ]);

        if (editDxccLookupKeyRef.current !== uppercaseCallsign) {
          return;
        }

        setEditDialog((current) => {
          if (current.form === null) {
            return current;
          }

          return {
            ...current,
            previewDxccRef: data.dxccRef ?? null,
            previewClubs: context.clubs,
            knownNoteId: context.note?.id ?? null,
            previewMessage: data.dxccRef
              ? `Preview Pfx ${data.dxccRef}.`
              : data.details ?? data.name ?? 'DXCC resolved.',
            form: {
              ...current.form,
              adif: data.adif?.toString() ?? '',
              continent: data.continent ?? '',
              waz: data.waz?.toString() ?? '',
              itu: data.itu?.toString() ?? '',
              clubNumber1: context.clubs.find((club) => club.slot === 1)?.number ?? '',
              clubNumber2: context.clubs.find((club) => club.slot === 2)?.number ?? '',
              clubNumber3: context.clubs.find((club) => club.slot === 3)?.number ?? '',
              clubNumber4: context.clubs.find((club) => club.slot === 4)?.number ?? '',
              clubNumber5: context.clubs.find((club) => club.slot === 5)?.number ?? '',
              callsignNote: current.callsignNoteDirty ? current.form.callsignNote : (context.note?.remarks ?? ''),
            },
          };
        });
      } catch (error) {
        if (editDxccLookupKeyRef.current !== uppercaseCallsign) {
          return;
        }

        setEditDialog((current) => {
          if (current.form === null) {
            return current;
          }

          return {
            ...current,
            previewDxccRef: null,
            previewClubs: [],
            knownNoteId: null,
            previewMessage: error instanceof Error ? error.message : 'DXCC lookup failed.',
            form: {
              ...current.form,
              adif: '',
              continent: '',
              waz: '',
              itu: '',
              clubNumber1: '',
              clubNumber2: '',
              clubNumber3: '',
              clubNumber4: '',
              clubNumber5: '',
              callsignNote: current.callsignNoteDirty ? current.form.callsignNote : '',
            },
          };
        });
      }
    }, 250);

    return () => window.clearTimeout(timeoutId);
  }, [deferredEditCallsign, editDialog.form?.qsoDate]);

  useEffect(() => {
    if (viewMode !== 'list') {
      return undefined;
    }

    let cancelled = false;

    setQsoList((current) => ({
      ...current,
      status: 'loading',
      message: 'Loading QSO list…',
    }));

    void getLogEntries(qsoList.page, qsoList.perPage)
      .then((response) => {
        if (cancelled) {
          return;
        }

        setQsoList({
          status: 'ready',
          items: response.items,
          totalCount: response.totalCount,
          page: response.page,
          perPage: response.perPage,
          totalPages: response.totalPages,
          message: response.totalCount === 0 ? 'No QSOs found.' : '',
        });
      })
      .catch((error) => {
        if (cancelled) {
          return;
        }

        setQsoList((current) => ({
          ...current,
          status: 'error',
          items: [],
          totalCount: 0,
          totalPages: 1,
          message: error instanceof Error ? error.message : 'Unable to load QSO list.',
        }));
      });

    return () => {
      cancelled = true;
    };
  }, [qsoList.page, qsoList.perPage, qsoListReloadKey, viewMode]);

  useEffect(() => {
    if (viewMode !== 'contest') {
      return undefined;
    }

    const trimmedContestName = contestName.trim();

    if (trimmedContestName === '') {
      setContestQsoList((current) => ({
        ...current,
        status: 'idle',
        items: [],
        totalCount: 0,
        totalPages: 1,
        message: 'Set a contest name to list its QSOs.',
      }));

      return undefined;
    }

    let cancelled = false;

    setContestQsoList((current) => ({
      ...current,
      status: 'loading',
      message: 'Loading contest QSOs…',
    }));

    const timeoutId = window.setTimeout(() => {
      void getLogEntries(1, 50, { contestName: trimmedContestName })
        .then((response) => {
          if (cancelled) {
            return;
          }

          setContestQsoList({
            status: 'ready',
            items: response.items,
            totalCount: response.totalCount,
            page: response.page,
            perPage: response.perPage,
            totalPages: response.totalPages,
            message: response.totalCount === 0 ? 'No QSOs logged in this contest yet.' : '',
          });
        })
        .catch((error) => {
          if (cancelled) {
            return;
          }

          setContestQsoList((current) => ({
            ...current,
            status: 'error',
            items: [],
            totalCount: 0,
            totalPages: 1,
            message: error instanceof Error ? error.message : 'Unable to load contest QSOs.',
          }));
        });
    }, 400);

    return () => {
      cancelled = true;
      window.clearTimeout(timeoutId);
    };
  }, [contestName, contestQsoReloadKey, viewMode]);

  useEffect(() => {
    if (viewMode !== 'cluster') {
      return undefined;
    }

    let cancelled = false;
    let requestInFlight = false;

    const loadDxCluster = async () => {
      if (requestInFlight) {
        return;
      }

      requestInFlight = true;

      setDxCluster((current) => ({
        ...current,
        status: current.items.length > 0 ? 'ready' : 'loading',
        message: current.items.length > 0 ? current.message : 'Loading DX Cluster…',
      }));

      try {
        const [responseText, solarResponseText] = await Promise.all([
          getDxClusterFeed(DX_CLUSTER_URL),
          getSolarData(),
        ]);

        if (cancelled) {
          return;
        }

        const items = parseDxClusterFeed(responseText);
        const solarSummary = parseSolarDataSummary(solarResponseText);

        setDxCluster({
          status: 'ready',
          items,
          message: items.length === 0 ? 'DX Cluster returned no spots.' : '',
          lastLoadedAt: formatDateTimeLabel(new Date()),
          solarSummary,
        });
      } catch (error) {
        if (cancelled) {
          return;
        }

        setDxCluster((current) => ({
          ...current,
          status: 'error',
          message: error instanceof Error ? error.message : 'Unable to load DX Cluster.',
        }));
      } finally {
        requestInFlight = false;
      }
    };

    void loadDxCluster();

    const intervalId = window.setInterval(() => {
      void loadDxCluster();
    }, DX_CLUSTER_POLL_INTERVAL_MS);

    return () => {
      cancelled = true;
      window.clearInterval(intervalId);
    };
  }, [dxClusterReloadKey, viewMode]);

  function updateField<K extends keyof FormState>(field: K, value: FormState[K]): void {
    setForm((current) => ({
      ...current,
      [field]: value,
    }));
  }

  function normalizeEntryFieldCapitalization(field: 'name' | 'qth'): void {
    setForm((current) => ({
      ...current,
      [field]: uppercaseFirstCharacter(current[field]),
    }));
  }

  function openEntryView(): void {
    setViewMode('entry');
  }

  function openListView(): void {
    setViewMode('list');
  }

  function openSettingsView(): void {
    setViewMode('settings');
  }

  function openClusterView(): void {
    setViewMode('cluster');
  }

  function openContestView(): void {
    setViewMode('contest');
  }

  function updateContestField<K extends keyof ContestFormState>(field: K, value: ContestFormState[K]): void {
    setContestForm((current) => ({
      ...current,
      [field]: value,
    }));
  }

  function updateSetting<K extends keyof FrontendSettings>(key: K, value: FrontendSettings[K]): void {
    setSettings((current) => ({
      ...current,
      [key]: value,
    }));
  }

  function setEntryArrowFieldRef(
    field: EntryArrowField,
    element: EntryArrowElement | null,
  ): void {
    entryArrowFieldRefs.current[field] = element;
  }

  function focusEntryArrowField(field: EntryArrowField, element: EntryArrowElement): void {
    element.focus();

    if (element instanceof HTMLInputElement) {
      if (field === 'rstReceived' || field === 'rstSent') {
        selectRstEditableCharacter(element);
        return;
      }

      element.select();
    }
  }

  function handleEntryArrowNavigation(
    field: EntryArrowField,
  ): (event: ReactKeyboardEvent<HTMLInputElement | HTMLTextAreaElement>) => void {
    return (event) => {
      if (event.altKey || event.ctrlKey || event.metaKey || event.shiftKey) {
        return;
      }

      if (event.key !== 'ArrowDown' && event.key !== 'ArrowUp') {
        return;
      }

      const currentIndex = ENTRY_ARROW_NAV_ORDER.indexOf(field);

      if (currentIndex === -1) {
        return;
      }

      const nextIndex = event.key === 'ArrowDown' ? currentIndex + 1 : currentIndex - 1;

      if (nextIndex < 0 || nextIndex >= ENTRY_ARROW_NAV_ORDER.length) {
        return;
      }

      const nextField = ENTRY_ARROW_NAV_ORDER[nextIndex];
      const nextElement = entryArrowFieldRefs.current[nextField];

      if (!nextElement) {
        return;
      }

      event.preventDefault();
      window.requestAnimationFrame(() => {
        focusEntryArrowField(nextField, nextElement);
      });
    };
  }

  function handleCallsignKeyDown(event: ReactKeyboardEvent<HTMLInputElement>): void {
    if (event.key === ' ') {
      event.preventDefault();
      const nextElement = entryArrowFieldRefs.current.rstReceived;

      if (nextElement) {
        window.requestAnimationFrame(() => {
          focusEntryArrowField('rstReceived', nextElement);
        });
      }

      return;
    }

    handleEntryArrowNavigation('callsign')(event);
  }

  function resetFrontendSettings(): void {
    setSettings({ ...DEFAULT_FRONTEND_SETTINGS });
  }

  function closeProfileDialog(): void {
    setProfileDialog({
      status: 'closed',
      mode: 'create',
      profileId: null,
      form: null,
      message: '',
    });
  }

  function openCreateProfileDialog(): void {
    setProfileDialog({
      status: 'ready',
      mode: 'create',
      profileId: null,
      form: {
        ...createProfileFormState(),
        number: getNextProfileNumber(profiles.items).toString(),
      },
      message: '',
    });
  }

  function openEditProfileDialog(): void {
    if (selectedProfile === null) {
      return;
    }

    setProfileDialog({
      status: 'ready',
      mode: 'edit',
      profileId: selectedProfile.id,
      form: createProfileFormState(selectedProfile),
      message: '',
    });
  }

  function updateProfileFormField<K extends keyof ProfileFormState>(field: K, value: ProfileFormState[K]): void {
    setProfileDialog((current) => {
      if (current.form === null) {
        return current;
      }

      return {
        ...current,
        message: '',
        form: {
          ...current.form,
          [field]: value,
        },
      };
    });
  }

  async function handleProfileSubmit(event: React.FormEvent<HTMLFormElement>): Promise<void> {
    event.preventDefault();

    if (profileDialog.form === null) {
      return;
    }

    const parsedNumber = Number.parseInt(profileDialog.form.number.trim(), 10);

    if (!Number.isInteger(parsedNumber) || parsedNumber < 1) {
      setProfileDialog((current) => ({
        ...current,
        message: 'Profile number must be an integer greater than 0.',
      }));
      return;
    }

    setProfileDialog((current) => ({
      ...current,
      status: 'saving',
      message: current.mode === 'create' ? 'Creating profile…' : 'Saving profile…',
    }));

    const payload = {
      number: parsedNumber,
      locator: normalizeOptionalString(profileDialog.form.locator),
      qth: normalizeOptionalString(profileDialog.form.qth),
      rig: normalizeOptionalString(profileDialog.form.rig),
      remarks: normalizeOptionalString(profileDialog.form.remarks),
      visible: profileDialog.form.visible,
    };

    try {
      const savedProfile =
        profileDialog.mode === 'create'
          ? await createProfile(payload)
          : await updateProfile(profileDialog.profileId as number, payload);

      setProfilesReloadKey((current) => current + 1);
      setSettings((current) => ({
        ...current,
        defaultProfileId: savedProfile.id,
      }));
      closeProfileDialog();
    } catch (error) {
      setProfileDialog((current) => ({
        ...current,
        status: 'ready',
        message: error instanceof Error ? error.message : 'Unable to save profile.',
      }));
    }
  }

  async function handleDeleteSelectedProfile(): Promise<void> {
    if (selectedProfile === null) {
      return;
    }

    const shouldDelete = window.confirm(`Delete profile #${selectedProfile.number}?`);

    if (!shouldDelete) {
      return;
    }

    try {
      await deleteProfile(selectedProfile.id);
      setProfilesReloadKey((current) => current + 1);
      setSettings((current) => ({
        ...current,
        defaultProfileId: current.defaultProfileId === selectedProfile.id ? null : current.defaultProfileId,
      }));
    } catch (error) {
      setProfiles((current) => ({
        ...current,
        status: 'error',
        message: error instanceof Error ? error.message : 'Unable to delete profile.',
      }));
    }
  }

  function closeEditDialog(): void {
    setEditDialog({
      status: 'closed',
      entryId: null,
      originalCallsign: '',
      currentDxccRef: null,
      previewDxccRef: null,
      previewClubs: [],
      knownNoteId: null,
      callsignNoteDirty: false,
      previewMessage: '',
      form: null,
      message: '',
    });
  }

  function changeQsoListPage(nextPage: number): void {
    setQsoList((current) => ({
      ...current,
      page: Math.min(Math.max(1, nextPage), Math.max(1, current.totalPages)),
    }));
  }

  async function openEditDialog(entryId: number): Promise<void> {
    setQsoListFeedback({
      status: 'idle',
      message: '',
    });
    setEditDialog({
      status: 'loading',
      entryId,
      originalCallsign: '',
      currentDxccRef: null,
      previewDxccRef: null,
      previewClubs: [],
      knownNoteId: null,
      callsignNoteDirty: false,
      previewMessage: 'Loading QSO detail…',
      form: null,
      message: 'Loading QSO detail…',
    });

    try {
      const entry = await getLogEntry(entryId);

      setEditDialog({
        status: 'ready',
        entryId,
        originalCallsign: entry.callsign.trim().toUpperCase(),
        currentDxccRef: entry.dxccRef ?? null,
        previewDxccRef: entry.dxccRef ?? null,
        previewClubs: [],
        knownNoteId: null,
        callsignNoteDirty: false,
        previewMessage: entry.dxccRef ? `Current Pfx ${entry.dxccRef}.` : 'Current QSO has no resolved Pfx.',
        form: createEditLogEntryFormState(entry),
        message: '',
      });
    } catch (error) {
      setEditDialog({
        status: 'error',
        entryId,
        originalCallsign: '',
        currentDxccRef: null,
        previewDxccRef: null,
        previewClubs: [],
        knownNoteId: null,
        callsignNoteDirty: false,
        previewMessage: '',
        form: null,
        message: error instanceof Error ? error.message : 'Unable to load QSO detail.',
      });
    }
  }

  function updateEditField<K extends keyof EditLogEntryFormState>(field: K, value: EditLogEntryFormState[K]): void {
    setEditDialog((current) => {
      if (current.form === null) {
        return current;
      }

      return {
        ...current,
        message: '',
        callsignNoteDirty: field === 'callsignNote' ? true : current.callsignNoteDirty,
        form: {
          ...current.form,
          [field]: value,
        },
      };
    });
  }

  function normalizeEditFieldCapitalization(field: 'name' | 'qth'): void {
    setEditDialog((current) => {
      if (current.form === null) {
        return current;
      }

      return {
        ...current,
        form: {
          ...current.form,
          [field]: uppercaseFirstCharacter(current.form[field]),
        },
      };
    });
  }

  function resetEntryForm(nextSubmitState?: { status: 'idle' | 'saved' | 'error'; message: string }): void {
    const cleared = createInitialFormState();

    setForm({
      ...cleared,
      band: form.band,
      mode: form.mode,
      frequency: formatFrequencyInputValue(form.frequency),
      power: form.power,
    });
    setLookup({
      status: 'idle',
      message: 'Enter a callsign to load note, recent QSOs and club memberships.',
    });
    setClubMemberships([]);
    setRecentQsos([]);
    setRecentQsoCount(0);
    setKnownNoteId(null);
    setLookupCallsign('');
    setCallsignNoteDirty(false);
    setDxcc({
      status: 'idle',
      data: null,
      message: 'DXCC lookup idle.',
    });
    setQsoStarted(false);
    setQsoStartedAt(null);
    setSubmitState(nextSubmitState ?? {
      status: 'idle',
      message: '',
    });

    window.requestAnimationFrame(() => {
      callsignInputRef.current?.focus();
    });
  }

  async function handleSubmit(event: React.FormEvent<HTMLFormElement>): Promise<void> {
    event.preventDefault();
    setSubmitState({
      status: 'saving',
      message: 'Saving QSO…',
    });

    try {
      const payload: LogEntryPayload = {
        qsoDate: form.qsoDate,
        timeOn: form.timeOn,
        timeOff: normalizeOptionalString(form.timeOff),
        callsign: form.callsign.trim(),
        frequency: normalizeRequiredFrequency(form.frequency),
        mode: form.mode.trim(),
        rstSent: normalizeOptionalString(form.rstSent),
        rstReceived: normalizeOptionalString(form.rstReceived),
        name: normalizeOptionalString(form.name),
        qth: normalizeOptionalString(form.qth),
        grid: normalizeOptionalString(form.grid),
        state: normalizeOptionalString(form.state),
        county: normalizeOptionalString(form.county),
        award: normalizeOptionalString(form.award),
        adif: normalizeOptionalInteger(form.adif),
        band: normalizeOptionalString(form.band),
        remarks: normalizeOptionalString(form.remarks),
        qslSent: normalizeOptionalString(form.qslSent),
        qslReceived: normalizeOptionalString(form.qslReceived),
        qslVia: normalizeOptionalString(form.qslVia),
        iota: normalizeOptionalString(form.iota),
        power: normalizeOptionalString(form.power),
        itu: normalizeOptionalInteger(form.itu),
        waz: normalizeOptionalInteger(form.waz),
        continent: normalizeOptionalString(form.continent),
        clubNumber1: normalizeOptionalString(form.clubNumber1),
        clubNumber2: normalizeOptionalString(form.clubNumber2),
        clubNumber3: normalizeOptionalString(form.clubNumber3),
        clubNumber4: normalizeOptionalString(form.clubNumber4),
        clubNumber5: normalizeOptionalString(form.clubNumber5),
        profileId: settings.defaultProfileId,
      };

      const savedLogEntry = await createLogEntry(payload);
      const trimmedNote = form.callsignNote.trim();
      let noteMessage = '';

      if (form.callsign.trim() !== '') {
        if (knownNoteId !== null) {
          await updateNote(knownNoteId, trimmedNote === '' ? null : trimmedNote);
          noteMessage = ' Callsign note updated.';
        } else if (trimmedNote !== '') {
          const note = await createNote(form.callsign.trim(), trimmedNote);
          setKnownNoteId(note.id);
          noteMessage = ' Callsign note created.';
        }
      }

      resetEntryForm({
        status: 'saved',
        message: `Saved QSO #${savedLogEntry.id}.${noteMessage}`,
      });
    } catch (error) {
      setSubmitState({
        status: 'error',
        message: error instanceof Error ? error.message : 'Saving failed.',
      });
    }
  }

  async function handleContestSubmit(event: React.FormEvent<HTMLFormElement>): Promise<void> {
    event.preventDefault();
    setContestSubmitState({
      status: 'saving',
      message: 'Saving QSO…',
    });

    try {
      const payload: LogEntryPayload = {
        qsoDate: form.qsoDate,
        timeOn: form.timeOn,
        timeOff: normalizeOptionalString(form.timeOff),
        callsign: contestForm.callsign.trim(),
        frequency: normalizeRequiredFrequency(form.frequency),
        mode: form.mode.trim(),
        band: normalizeOptionalString(form.band),
        power: normalizeOptionalString(form.power),
        rstSent: normalizeOptionalString(contestForm.rstSent),
        rstReceived: normalizeOptionalString(contestForm.rstReceived),
        stx: normalizeOptionalString(contestForm.serialSent),
        srx: normalizeOptionalString(contestForm.serialReceived),
        stxString: normalizeOptionalString(contestForm.msgSent),
        srxString: normalizeOptionalString(contestForm.msgReceived),
        contestName: normalizeOptionalString(contestName),
        profileId: settings.defaultProfileId,
      };

      const savedLogEntry = await createLogEntry(payload);

      setContestForm((current) => ({
        ...current,
        callsign: '',
        rstSent: defaultRstForMode(form.mode),
        rstReceived: defaultRstForMode(form.mode),
        serialSent: contestAutoIncrement ? incrementSerial(current.serialSent) : current.serialSent,
        serialReceived: '',
        msgReceived: '',
      }));
      setContestLookup({
        status: 'idle',
        message: 'Leave the Call field to check previous QSOs.',
        recentQsos: [],
        recentQsoCount: 0,
      });
      setContestLookupCallsign('');
      setContestQsoReloadKey((current) => current + 1);
      setContestSubmitState({
        status: 'saved',
        message: `Saved QSO #${savedLogEntry.id}.`,
      });

      window.requestAnimationFrame(() => {
        contestCallsignInputRef.current?.focus();
      });
    } catch (error) {
      setContestSubmitState({
        status: 'error',
        message: error instanceof Error ? error.message : 'Saving failed.',
      });
    }
  }

  function handleClear(): void {
    const shouldClear = window.confirm('Clear all entered QSO fields?');

    if (!shouldClear) {
      return;
    }

    resetEntryForm();
  }

  async function handleEditSubmit(event: React.FormEvent<HTMLFormElement>): Promise<void> {
    event.preventDefault();

    if (editDialog.entryId === null || editDialog.form === null) {
      return;
    }

    setEditDialog((current) => ({
      ...current,
      status: 'saving',
      message: 'Saving QSO changes…',
    }));

    try {
      let payload: LogEntryPayload = {
        qsoDate: editDialog.form.qsoDate,
        timeOn: editDialog.form.timeOn,
        timeOff: normalizeOptionalString(editDialog.form.timeOff),
        callsign: editDialog.form.callsign.trim(),
        frequency: normalizeRequiredFrequency(editDialog.form.frequency),
        mode: editDialog.form.mode.trim(),
        rstSent: normalizeOptionalString(editDialog.form.rstSent),
        rstReceived: normalizeOptionalString(editDialog.form.rstReceived),
        name: normalizeOptionalString(editDialog.form.name),
        qth: normalizeOptionalString(editDialog.form.qth),
        grid: normalizeOptionalString(editDialog.form.grid),
        state: normalizeOptionalString(editDialog.form.state),
        county: normalizeOptionalString(editDialog.form.county),
        award: normalizeOptionalString(editDialog.form.award),
        adif: normalizeOptionalInteger(editDialog.form.adif),
        band: normalizeOptionalString(editDialog.form.band),
        remarks: normalizeOptionalString(editDialog.form.remarks),
        qslSent: normalizeOptionalString(editDialog.form.qslSent),
        qslReceived: normalizeOptionalString(editDialog.form.qslReceived),
        qslVia: normalizeOptionalString(editDialog.form.qslVia),
        iota: normalizeOptionalString(editDialog.form.iota),
        power: normalizeOptionalString(editDialog.form.power),
        itu: normalizeOptionalInteger(editDialog.form.itu),
        waz: normalizeOptionalInteger(editDialog.form.waz),
        continent: normalizeOptionalString(editDialog.form.continent),
        clubNumber1: normalizeOptionalString(editDialog.form.clubNumber1),
        clubNumber2: normalizeOptionalString(editDialog.form.clubNumber2),
        clubNumber3: normalizeOptionalString(editDialog.form.clubNumber3),
        clubNumber4: normalizeOptionalString(editDialog.form.clubNumber4),
        clubNumber5: normalizeOptionalString(editDialog.form.clubNumber5),
      };

      const normalizedEditedCallsign = editDialog.form.callsign.trim().toUpperCase();

      if (normalizedEditedCallsign !== editDialog.originalCallsign) {
        const [dxccData, callsignContext] = await Promise.all([
          getDxcc(normalizedEditedCallsign),
          getCallsignContext(normalizedEditedCallsign, editDialog.form.qsoDate),
        ]);
        const clubNumbers = new Map(callsignContext.clubs.map((club) => [club.slot, club.number]));

        payload = {
          ...payload,
          adif: dxccData.adif,
          continent: dxccData.continent,
          waz: dxccData.waz,
          itu: dxccData.itu,
          clubNumber1: clubNumbers.get(1) ?? null,
          clubNumber2: clubNumbers.get(2) ?? null,
          clubNumber3: clubNumbers.get(3) ?? null,
          clubNumber4: clubNumbers.get(4) ?? null,
          clubNumber5: clubNumbers.get(5) ?? null,
        };
      }

      const updatedEntry = await updateLogEntry(editDialog.entryId, payload);
      const trimmedCallsignNote = editDialog.form.callsignNote.trim();

      if (normalizedEditedCallsign !== '') {
        if (editDialog.knownNoteId !== null) {
          await updateNote(editDialog.knownNoteId, trimmedCallsignNote === '' ? null : trimmedCallsignNote);
        } else if (trimmedCallsignNote !== '') {
          await createNote(normalizedEditedCallsign, trimmedCallsignNote);
        }
      }

      closeEditDialog();
      setQsoListFeedback({
        status: 'saved',
        message: `Updated QSO #${updatedEntry.id}.`,
      });
      setQsoListReloadKey((current) => current + 1);
    } catch (error) {
      setEditDialog((current) => ({
        ...current,
        status: current.form === null ? 'error' : 'ready',
        message: error instanceof Error ? error.message : 'Unable to save QSO changes.',
      }));
    }
  }

  const qsoDuration = formatQsoDuration(qsoStartedAt);
  const isEntryView = viewMode === 'entry';
  const isContestView = viewMode === 'contest';
  const isListView = viewMode === 'list';
  const isSettingsView = viewMode === 'settings';
  const isClusterView = viewMode === 'cluster';
  const canGoToPreviousQsoPage = qsoList.page > 1;
  const canGoToNextQsoPage = qsoList.page < qsoList.totalPages;
  const currentQsoNumber = lookup.status === 'ready' && lookupCallsign !== '' ? recentQsoCount + 1 : null;

  if (authState === 'checking') {
    return (
      <div className="login-screen">
        <p className="login-loading">Loading…</p>
      </div>
    );
  }

  if (authState === 'logged-out') {
    return (
      <div className="login-screen">
        <form className="login-form" onSubmit={handleLogin}>
          <h1 className="login-title">CQRLOG Web</h1>
          {loginError && <p className="login-error">{loginError}</p>}
          <label className="field">
            <span>Username</span>
            <input
              type="text"
              value={loginForm.username}
              onChange={(e) => setLoginForm((prev) => ({ ...prev, username: e.target.value }))}
              autoFocus
              autoComplete="username"
              autoCapitalize="none"
            />
          </label>
          <label className="field">
            <span>Password</span>
            <input
              type="password"
              value={loginForm.password}
              onChange={(e) => setLoginForm((prev) => ({ ...prev, password: e.target.value }))}
              autoComplete="current-password"
            />
          </label>
          <button className="button button--primary" type="submit">Log in</button>
        </form>
      </div>
    );
  }

  return (
    <div className="shell">
      <aside className="sidebar">
        <button
          className={isEntryView ? 'sidebar__accent sidebar__button--active' : 'sidebar__accent'}
          type="button"
          aria-label="New QSO"
          title="New QSO"
          onClick={openEntryView}
        >
          +
        </button>
        <button
          className={isContestView ? 'sidebar__menu sidebar__button--active' : 'sidebar__menu'}
          type="button"
          aria-label="Contest"
          title="Contest"
          onClick={openContestView}
        >
          <svg className="sidebar__icon" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M7 4h10v3a5 5 0 0 1-10 0V4Z" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinejoin="round" />
            <path d="M7 5H4.5v1.5A3.5 3.5 0 0 0 8 10" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
            <path d="M17 5h2.5v1.5A3.5 3.5 0 0 1 16 10" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
            <path d="M12 12v4" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" />
            <path d="M8.5 19.5h7M10 16.5h4" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" />
          </svg>
        </button>
        <button
          className={isListView ? 'sidebar__menu sidebar__button--active' : 'sidebar__menu'}
          type="button"
          aria-label="QSO list"
          title="QSO list"
          onClick={openListView}
        >
          ≣
        </button>
        <button
          className={isClusterView ? 'sidebar__menu sidebar__button--active' : 'sidebar__menu'}
          type="button"
          aria-label="DX Cluster"
          title="DX Cluster"
          onClick={openClusterView}
        >
          <svg className="sidebar__icon" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="12" r="8.5" fill="none" stroke="currentColor" strokeWidth="1.7" />
            <path d="M3.5 12h17" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" />
            <path d="M12 3.5c2.3 2.2 3.7 5.2 3.7 8.5S14.3 18.3 12 20.5C9.7 18.3 8.3 15.3 8.3 12S9.7 5.7 12 3.5Z" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinejoin="round" />
            <path d="M6.1 7.8c1.6.7 3.8 1.1 5.9 1.1s4.3-.4 5.9-1.1" fill="none" stroke="currentColor" strokeWidth="1.3" strokeLinecap="round" />
            <path d="M6.1 16.2c1.6-.7 3.8-1.1 5.9-1.1s4.3.4 5.9 1.1" fill="none" stroke="currentColor" strokeWidth="1.3" strokeLinecap="round" />
          </svg>
        </button>
        <button
          className={isSettingsView ? 'sidebar__menu sidebar__button--active' : 'sidebar__menu'}
          type="button"
          aria-label="Settings"
          title="Settings"
          onClick={openSettingsView}
        >
          ⚙
        </button>
        {authRequired ? (
          <>
            <div className="sidebar__spacer" />
            <button
              className="sidebar__menu sidebar__logout"
              type="button"
              aria-label="Log out"
              title="Log out"
              onClick={handleLogout}
            >
              <svg className="sidebar__icon" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                <polyline points="16 17 21 12 16 7" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                <line x1="21" y1="12" x2="9" y2="12" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
              </svg>
            </button>
          </>
        ) : null}
      </aside>

      <main className="workspace">
        {isEntryView ? (
        <form className="panel" onSubmit={handleSubmit}>
          <section className="topbar">
            <div className="topbar__dxcc">
              <span className="meta-strip__label">DXCC</span>
              <p className="topbar__line">{dxcc.message}</p>
              {dxcc.data ? (
                <p className="topbar__subtle">
                  {dxcc.data.details ?? dxcc.data.name}
                  {dxcc.data.continent ? ` (${dxcc.data.continent})` : ''}
                  {dxcc.data.waz ? ` | WAZ ${dxcc.data.waz}` : ''}
                  {dxcc.data.itu ? ` | ITU ${dxcc.data.itu}` : ''}
                </p>
              ) : null}
            </div>

            <div className="topbar__actions">
              <button
                className="button button--primary button--icon"
                type="submit"
                disabled={submitState.status === 'saving'}
                aria-label={submitState.status === 'saving' ? 'Saving QSO' : 'Save QSO'}
                title={submitState.status === 'saving' ? 'Saving QSO' : 'Save QSO'}
              >
                <span aria-hidden="true">{submitState.status === 'saving' ? '…' : '✓'}</span>
                <span className="visually-hidden">{submitState.status === 'saving' ? 'Saving QSO' : 'Save QSO'}</span>
              </button>
              <button
                className="button button--secondary button--icon"
                type="button"
                onClick={handleClear}
                aria-label="Clear form"
                title="Clear form"
              >
                <span aria-hidden="true">×</span>
                <span className="visually-hidden">Clear</span>
              </button>
            </div>
          </section>

          <div className="panel-layout">
            <div className="panel-main">
              <section className="grid grid--top">
                <label className="field field--wide">
                  <span className="field__label">
                    <span>Freq</span>
                    {radioSyncState !== 'idle' ? (
                      <span
                        className={`field__status field__status--${radioSyncState}`}
                        aria-hidden="true"
                        title={radioSyncState === 'online' ? 'Radio sync online' : 'Radio sync offline'}
                      />
                    ) : null}
                  </span>
                  <input
                    value={form.frequency}
                    onChange={(event) => {
                      const nextValue = normalizeCzechNumberRow(event.target.value);
                      const derivedBand = getBandFromFrequency(nextValue);

                      setForm((current) => ({
                        ...current,
                        frequency: nextValue,
                        band: derivedBand ?? current.band,
                      }));
                    }}
                    onBlur={() => updateField('frequency', formatFrequencyInputValue(form.frequency))}
                    inputMode="decimal"
                    required
                  />
                </label>

                <label className="field">
                  <span>Band</span>
                  <select
                    value={form.band}
                    onChange={(event) => {
                      const nextBand = event.target.value;

                      setForm((current) => ({
                        ...current,
                        band: nextBand,
                        frequency: defaultFrequencyByBand[nextBand] ?? current.frequency,
                      }));
                    }}
                  >
                    {bandOptions.map((option) => (
                      <option key={option} value={option}>
                        {option}
                      </option>
                    ))}
                  </select>
                </label>

                <label className="field">
                  <span>Mode</span>
                  <select value={form.mode} onChange={(event) => updateField('mode', event.target.value)}>
                    {modeOptions.map((option) => (
                      <option key={option} value={option}>
                        {option}
                      </option>
                    ))}
                  </select>
                </label>

                <label className="field">
                  <span>Pwr(W)</span>
                  <input
                    value={form.power}
                    onChange={(event) => updateField('power', normalizeDigitsOnly(event.target.value))}
                    inputMode="numeric"
                  />
                </label>
              </section>

              <section className="grid grid--callsign">
                <label className="field">
                  <span className="field__label">
                    <span>Callsign</span>
                    {currentQsoNumber !== null ? <span className="field__meta">(QSO nr. {currentQsoNumber})</span> : null}
                  </span>
                  <input
                    ref={(element) => {
                      callsignInputRef.current = element;
                      setEntryArrowFieldRef('callsign', element);
                    }}
                    value={form.callsign}
                    onChange={(event) => {
                      const nextCallsign = normalizeCallsignInput(event.target.value);

                      setForm((current) => ({
                        ...current,
                        callsign: nextCallsign,
                        callsignNote: '',
                      }));
                      setCallsignNoteDirty(false);
                      setLookup({
                        status: 'idle',
                        message: 'Leave Callsign field to load note, recent QSOs and club memberships.',
                      });
                      setClubMemberships([]);
                      setRecentQsos([]);
                      setRecentQsoCount(0);
                      setKnownNoteId(null);
                      setLookupCallsign('');
                      applyClubMemberships(setForm, []);
                    }}
                    onBlur={() => setLookupCallsign(normalizedCallsign)}
                    onKeyDown={handleCallsignKeyDown}
                    required
                  />
                </label>

                <label className="field field--rst">
                  <span>His RST</span>
                  <input
                    ref={(element) => setEntryArrowFieldRef('rstReceived', element)}
                    value={form.rstReceived}
                    onChange={(event) => updateField('rstReceived', normalizeCzechNumberRow(event.target.value))}
                    onFocus={(event) => selectRstEditableCharacter(event.currentTarget)}
                    maxLength={5}
                    onKeyDown={handleEntryArrowNavigation('rstReceived')}
                    onBlur={() => {
                      if (form.callsign.trim() === '' || qsoStarted) {
                        return;
                      }

                      setQsoStarted(true);
                      setQsoStartedAt(new Date());
                    }}
                  />
                </label>

                <label className="field field--rst">
                  <span>My RST</span>
                  <input
                    ref={(element) => setEntryArrowFieldRef('rstSent', element)}
                    value={form.rstSent}
                    onChange={(event) => updateField('rstSent', normalizeCzechNumberRow(event.target.value))}
                    onFocus={(event) => selectRstEditableCharacter(event.currentTarget)}
                    maxLength={5}
                    onKeyDown={handleEntryArrowNavigation('rstSent')}
                  />
                </label>

                <label className="field field--wide field--callsign-name">
                  <span>Name</span>
                  <input
                    ref={(element) => setEntryArrowFieldRef('name', element)}
                    value={form.name}
                    onChange={(event) => updateField('name', event.target.value)}
                    onBlur={() => normalizeEntryFieldCapitalization('name')}
                    onKeyDown={handleEntryArrowNavigation('name')}
                    autoComplete="off"
                    data-1p-ignore="true"
                    data-lpignore="true"
                  />
                </label>
              </section>

              <section className="grid grid--geo">
                <label className="field field--wide">
                  <span>QTH</span>
                  <input
                    ref={(element) => setEntryArrowFieldRef('qth', element)}
                    value={form.qth}
                    onChange={(event) => updateField('qth', event.target.value)}
                    onBlur={() => normalizeEntryFieldCapitalization('qth')}
                    onKeyDown={handleEntryArrowNavigation('qth')}
                  />
                </label>

                <label className="field">
                  <span>Grid</span>
                  <input
                    ref={(element) => setEntryArrowFieldRef('grid', element)}
                    value={form.grid}
                    onChange={(event) => updateField('grid', normalizeCzechNumberRow(event.target.value).toUpperCase())}
                    onKeyDown={handleEntryArrowNavigation('grid')}
                  />
                </label>

                <label className="field">
                  <span>State</span>
                  <input
                    ref={(element) => setEntryArrowFieldRef('state', element)}
                    value={form.state}
                    onChange={(event) => updateField('state', event.target.value.toUpperCase())}
                    autoComplete="off"
                    data-1p-ignore="true"
                    data-lpignore="true"
                    onKeyDown={handleEntryArrowNavigation('state')}
                  />
                </label>

                <label className="field field--wide">
                  <span>County</span>
                  <input
                    ref={(element) => setEntryArrowFieldRef('county', element)}
                    value={form.county}
                    onChange={(event) => updateField('county', event.target.value)}
                    onKeyDown={handleEntryArrowNavigation('county')}
                  />
                </label>
              </section>

              <section className="grid grid--mid">
                <label className="field">
                  <span>Award</span>
                  <input
                    ref={(element) => setEntryArrowFieldRef('award', element)}
                    value={form.award}
                    onChange={(event) => updateField('award', event.target.value)}
                    onKeyDown={handleEntryArrowNavigation('award')}
                  />
                </label>

                <label className="field field--wide">
                  <span>Comment to QSO</span>
                  <input
                    ref={(element) => setEntryArrowFieldRef('remarks', element)}
                    value={form.remarks}
                    onChange={(event) => updateField('remarks', event.target.value)}
                    onKeyDown={handleEntryArrowNavigation('remarks')}
                  />
                </label>

                <label className="field">
                  <span>QSL Via</span>
                  <input
                    ref={(element) => setEntryArrowFieldRef('qslVia', element)}
                    value={form.qslVia}
                    onChange={(event) => updateField('qslVia', normalizeCzechNumberRow(event.target.value).toUpperCase())}
                    onKeyDown={handleEntryArrowNavigation('qslVia')}
                  />
                </label>
              </section>

              <label className="field field--textarea">
                <span>Comment to callsign</span>
                <textarea
                  ref={(element) => setEntryArrowFieldRef('callsignNote', element)}
                  value={form.callsignNote}
                  onChange={(event) => {
                    updateField('callsignNote', event.target.value);
                    setCallsignNoteDirty(true);
                  }}
                  rows={4}
                />
              </label>

              <section className="grid grid--zones">
                <label className="field">
                  <span>WAZ</span>
                  <input
                    ref={(element) => setEntryArrowFieldRef('waz', element)}
                    value={form.waz}
                    onChange={(event) => updateField('waz', normalizeCzechNumberRow(event.target.value))}
                    inputMode="numeric"
                    onKeyDown={handleEntryArrowNavigation('waz')}
                  />
                </label>

                <label className="field">
                  <span>ITU</span>
                  <input
                    ref={(element) => setEntryArrowFieldRef('itu', element)}
                    value={form.itu}
                    onChange={(event) => updateField('itu', normalizeCzechNumberRow(event.target.value))}
                    inputMode="numeric"
                    onKeyDown={handleEntryArrowNavigation('itu')}
                  />
                </label>

                <label className="field">
                  <span>IOTA</span>
                  <input
                    ref={(element) => setEntryArrowFieldRef('iota', element)}
                    value={form.iota}
                    onChange={(event) => updateField('iota', normalizeCzechNumberRow(event.target.value).toUpperCase())}
                    onKeyDown={handleEntryArrowNavigation('iota')}
                  />
                </label>

                <label className="field field--compact">
                  <span>QS</span>
                  <select
                    ref={(element) => setEntryArrowFieldRef('qslSent', element)}
                    value={form.qslSent}
                    onChange={(event) => updateField('qslSent', event.target.value)}
                  >
                    {qslOptions.map((option) => (
                      <option key={option} value={option}>
                        {option || '-'}
                      </option>
                    ))}
                  </select>
                </label>

                <label className="field field--compact">
                  <span>QR</span>
                  <select
                    ref={(element) => setEntryArrowFieldRef('qslReceived', element)}
                    value={form.qslReceived}
                    onChange={(event) => updateField('qslReceived', event.target.value)}
                  >
                    {qslOptions.map((option) => (
                      <option key={option} value={option}>
                        {option || '-'}
                      </option>
                    ))}
                  </select>
                </label>
              </section>

              <section className="grid grid--time">
                <label className="field field--wide">
                  <span className="field__label">
                    <span>Date</span>
                    {qsoDuration && !form.offline ? <span className="field__meta">(QSO takes {qsoDuration})</span> : null}
                  </span>
                  <input
                    type="date"
                    value={form.qsoDate}
                    onChange={(event) => updateField('qsoDate', event.target.value)}
                    required
                  />
                </label>

                <label className="field">
                  <span>Time on</span>
                  <input
                    type="time"
                    value={form.timeOn}
                    onChange={(event) => updateField('timeOn', event.target.value)}
                    required
                  />
                </label>

                <label className="field">
                  <span>Time off</span>
                  <input type="time" value={form.timeOff} onChange={(event) => updateField('timeOff', event.target.value)} />
                </label>

                <label className="offline-toggle">
                  <input
                    type="checkbox"
                    checked={form.offline}
                    onChange={(event) => updateField('offline', event.target.checked)}
                  />
                  <span>Offline</span>
                </label>
              </section>
            </div>

            <aside className="panel-side">
              <article className="info-card info-card--club">
                <span className="meta-strip__label">Club</span>
                {clubMemberships.length > 0 ? (
                  <div className="club-list">
                    {clubMemberships.map((club) => (
                      <span key={`${club.slot}-${club.number}`} className="club-pill">
                        {club.name}: {club.number}
                      </span>
                    ))}
                  </div>
                ) : (
                  <p className="meta-strip__empty">No club memberships resolved yet.</p>
                )}
              </article>

              <section className="history-panel">
                <div className="history-panel__header">
                  <span className="meta-strip__label">Previous QSO</span>
                  <span className="history-panel__count">
                    {recentQsoCount > 0 ? `${recentQsoCount}. QSO` : 'No QSO history'}
                  </span>
                </div>

                <div className="history-table">
                  <div className="history-table__head">
                    <span>Date</span>
                    <span>Time</span>
                    <span>Callsign</span>
                    <span>Band</span>
                    <span>Mode</span>
                  </div>

                  {recentQsos.length > 0 ? (
                    recentQsos.map((qso) => (
                      <div key={qso.id} className="history-table__row">
                        <span>{qso.qsoDate}</span>
                        <span>
                          {qso.timeOn}
                          {qso.timeOff ? ` / ${qso.timeOff}` : ''}
                        </span>
                        <span>{qso.callsign}</span>
                        <span>{qso.band ?? '-'}</span>
                        <span>{qso.mode}</span>
                      </div>
                    ))
                  ) : (
                    <div className="history-table__empty">Recent QSOs for this callsign will appear here.</div>
                  )}
                </div>
              </section>

              {submitState.message !== '' ? (
                <p
                  className={
                    submitState.status === 'error' ? 'submission-message submission-message--error' : 'submission-message'
                  }
                >
                  {submitState.message}
                </p>
              ) : null}
            </aside>
          </div>
        </form>
        ) : isContestView ? (
        <form className="panel" onSubmit={handleContestSubmit}>
          <section className="topbar">
            <div className="topbar__dxcc">
              <span className="meta-strip__label">
                <span>Contest</span>
                {radioSyncState !== 'idle' ? (
                  <span
                    className={`field__status field__status--${radioSyncState}`}
                    aria-hidden="true"
                    title={radioSyncState === 'online' ? 'Radio sync online' : 'Radio sync offline'}
                  />
                ) : null}
              </span>
              <p className="topbar__line">
                {form.frequency} MHz · {form.band} · {form.mode} · {form.power} W
              </p>
              <p className="topbar__subtle">
                {form.qsoDate} {form.timeOn} — band, mode and frequency follow the QSO entry form.
              </p>
            </div>

            <div className="topbar__actions">
              <button
                className="button button--primary button--icon"
                type="submit"
                disabled={contestSubmitState.status === 'saving'}
                aria-label={contestSubmitState.status === 'saving' ? 'Saving QSO' : 'Save QSO'}
                title={contestSubmitState.status === 'saving' ? 'Saving QSO' : 'Save QSO'}
              >
                <span aria-hidden="true">{contestSubmitState.status === 'saving' ? '…' : '✓'}</span>
                <span className="visually-hidden">{contestSubmitState.status === 'saving' ? 'Saving QSO' : 'Save QSO'}</span>
              </button>
            </div>
          </section>

          <section className="grid grid--contest">
            <label className="field field--wide">
              <span>Contest name</span>
              <input
                value={contestName}
                maxLength={40}
                onChange={(event) => setContestName(event.target.value)}
                required
              />
            </label>

            <label className="offline-toggle">
              <input
                type="checkbox"
                checked={contestAutoIncrement}
                onChange={(event) => setContestAutoIncrement(event.target.checked)}
              />
              <span>NR s auto +1</span>
            </label>
          </section>

          <section className="grid grid--contest-entry">
            <label className="field">
              <span>Call</span>
              <input
                ref={contestCallsignInputRef}
                value={contestForm.callsign}
                onChange={(event) => {
                  updateContestField('callsign', normalizeCallsignInput(event.target.value));
                  setContestLookup({
                    status: 'idle',
                    message: 'Leave the Call field to check previous QSOs.',
                    recentQsos: [],
                    recentQsoCount: 0,
                  });
                  setContestLookupCallsign('');
                }}
                onBlur={() => setContestLookupCallsign(contestForm.callsign.trim().toUpperCase())}
                required
              />
            </label>

            <label className="field field--rst">
              <span>RST s</span>
              <input
                value={contestForm.rstSent}
                onChange={(event) => updateContestField('rstSent', normalizeCzechNumberRow(event.target.value))}
                onFocus={(event) => selectRstEditableCharacter(event.currentTarget)}
                maxLength={5}
              />
            </label>

            <label className="field">
              <span>NR s</span>
              <input
                value={contestForm.serialSent}
                onChange={(event) => updateContestField('serialSent', normalizeCzechNumberRow(event.target.value))}
                maxLength={6}
              />
            </label>

            <label className="field">
              <span>MSG s</span>
              <input
                value={contestForm.msgSent}
                onChange={(event) => updateContestField('msgSent', event.target.value)}
                maxLength={50}
              />
            </label>

            <label className="field field--rst">
              <span>RST r</span>
              <input
                value={contestForm.rstReceived}
                onChange={(event) => updateContestField('rstReceived', normalizeCzechNumberRow(event.target.value))}
                onFocus={(event) => selectRstEditableCharacter(event.currentTarget)}
                maxLength={5}
              />
            </label>

            <label className="field">
              <span>NR r</span>
              <input
                value={contestForm.serialReceived}
                onChange={(event) => updateContestField('serialReceived', normalizeCzechNumberRow(event.target.value))}
                maxLength={6}
              />
            </label>

            <label className="field">
              <span>MSG r</span>
              <input
                value={contestForm.msgReceived}
                onChange={(event) => updateContestField('msgReceived', event.target.value)}
                maxLength={50}
              />
            </label>
          </section>

          {contestSubmitState.message !== '' ? (
            <p
              className={
                contestSubmitState.status === 'error'
                  ? 'submission-message submission-message--error'
                  : 'submission-message'
              }
            >
              {contestSubmitState.message}
            </p>
          ) : null}

          <section className="history-panel">
            <div className="history-panel__header">
              <span className="meta-strip__label">Previous QSO</span>
              <span className="history-panel__count">{contestLookup.message}</span>
            </div>

            <div className="history-table">
              <div className="history-table__head">
                <span>Date</span>
                <span>Time</span>
                <span>Callsign</span>
                <span>Band</span>
                <span>Mode</span>
              </div>

              {contestLookup.recentQsos.length > 0 ? (
                contestLookup.recentQsos.map((qso) => (
                  <div key={qso.id} className="history-table__row">
                    <span>{qso.qsoDate}</span>
                    <span>
                      {qso.timeOn}
                      {qso.timeOff ? ` / ${qso.timeOff}` : ''}
                    </span>
                    <span>{qso.callsign}</span>
                    <span>{qso.band ?? '-'}</span>
                    <span>{qso.mode}</span>
                  </div>
                ))
              ) : (
                <div className="history-table__empty">Recent QSOs for this callsign will appear here.</div>
              )}
            </div>
          </section>

          <section className="contest-log">
            <header className="list-header">
              <div>
                <h2 className="list-header__title">Contest QSOs</h2>
                <p className="list-header__count">QSO count: {contestQsoList.totalCount}</p>
              </div>
            </header>

            {contestQsoList.status === 'error' ? (
              <p className="submission-message submission-message--error">{contestQsoList.message}</p>
            ) : null}

            <div className="qso-list-table qso-list-table--contest">
              <div className="qso-list-table__head">
                <span>QSO Date</span>
                <span>Time</span>
                <span>Callsign</span>
                <span>RST s</span>
                <span>NR s</span>
                <span>MSG s</span>
                <span>RST r</span>
                <span>NR r</span>
                <span>MSG r</span>
                <span>Band</span>
                <span>Mode</span>
              </div>

              {contestQsoList.items.length > 0 ? (
                contestQsoList.items.map((item) => (
                  <div key={item.id} className="qso-list-table__row">
                    <span>{item.qsoDate}</span>
                    <span>{item.timeOn}</span>
                    <span>{item.callsign}</span>
                    <span>{item.rstSent ?? '-'}</span>
                    <span>{item.stx ?? '-'}</span>
                    <span>{item.stxString ?? '-'}</span>
                    <span>{item.rstReceived ?? '-'}</span>
                    <span>{item.srx ?? '-'}</span>
                    <span>{item.srxString ?? '-'}</span>
                    <span>{item.band ?? '-'}</span>
                    <span>{item.mode}</span>
                  </div>
                ))
              ) : (
                <div className="qso-list-table__empty">
                  {contestQsoList.status === 'loading' ? 'Loading…' : contestQsoList.message || 'No QSOs found.'}
                </div>
              )}
            </div>
          </section>
        </form>
        ) : isListView ? (
          <section className="panel panel--list">
            <header className="list-header">
              <div>
                <h2 className="list-header__title">QSO list</h2>
                <p className="list-header__count">QSO count: {qsoList.totalCount}</p>
              </div>
              <div className="list-pagination">
                <button
                  className="button button--secondary button--pager"
                  type="button"
                  onClick={() => changeQsoListPage(qsoList.page - 1)}
                  disabled={!canGoToPreviousQsoPage || qsoList.status === 'loading'}
                >
                  &laquo;
                </button>
                <span className="list-pagination__page">
                  page {qsoList.page} / {qsoList.totalPages}
                </span>
                <button
                  className="button button--secondary button--pager"
                  type="button"
                  onClick={() => changeQsoListPage(qsoList.page + 1)}
                  disabled={!canGoToNextQsoPage || qsoList.status === 'loading'}
                >
                  &raquo;
                </button>
              </div>
            </header>

            {qsoListFeedback.message !== '' ? (
              <p
                className={
                  qsoListFeedback.status === 'error'
                    ? 'submission-message submission-message--error'
                    : 'submission-message'
                }
              >
                {qsoListFeedback.message}
              </p>
            ) : null}
            {qsoList.status === 'error' ? <p className="submission-message submission-message--error">{qsoList.message}</p> : null}
            {qsoList.status === 'loading' ? <p className="list-status">Loading QSO list…</p> : null}

            <div className="qso-list-table">
              <div className="qso-list-table__head">
                <span>QSO Date</span>
                <span>Time on/off</span>
                <span>Callsign</span>
                <span>RST_S</span>
                <span>RST_R</span>
                <span>Band</span>
                <span>Freq</span>
                <span>Mode</span>
                <span>Name</span>
                <span>QTH</span>
                <span>Award</span>
                <span>Pfx</span>
                <span>Edit</span>
              </div>

              {qsoList.items.length > 0 ? (
                qsoList.items.map((item) => (
                  <div key={item.id} className="qso-list-table__row">
                    <span>{item.qsoDate}</span>
                    <span>
                      {item.timeOn}
                      {item.timeOff ? ` / ${item.timeOff}` : ''}
                    </span>
                    <span>{item.callsign}</span>
                    <span>{item.rstSent ?? '-'}</span>
                    <span>{item.rstReceived ?? '-'}</span>
                    <span>{item.band ?? '-'}</span>
                    <span>{formatFrequency(item.frequency)}</span>
                    <span>{item.mode}</span>
                    <span>{item.name ?? '-'}</span>
                    <span>{item.qth ?? '-'}</span>
                    <span>{item.award ?? '-'}</span>
                    <span>{item.dxccRef ?? '-'}</span>
                    <button
                      className="button button--secondary button--list-action"
                      type="button"
                      onClick={() => void openEditDialog(item.id)}
                    >
                      Edit
                    </button>
                  </div>
                ))
              ) : (
                <div className="qso-list-table__empty">
                  {qsoList.status === 'loading' ? 'Loading…' : qsoList.message || 'No QSOs found.'}
                </div>
              )}
            </div>

            <footer className="list-footer">
              <div className="list-pagination">
                <button
                  className="button button--secondary button--pager"
                  type="button"
                  onClick={() => changeQsoListPage(qsoList.page - 1)}
                  disabled={!canGoToPreviousQsoPage || qsoList.status === 'loading'}
                >
                  &laquo;
                </button>
                <span className="list-pagination__page">
                  page {qsoList.page} / {qsoList.totalPages}
                </span>
                <button
                  className="button button--secondary button--pager"
                  type="button"
                  onClick={() => changeQsoListPage(qsoList.page + 1)}
                  disabled={!canGoToNextQsoPage || qsoList.status === 'loading'}
                >
                  &raquo;
                </button>
              </div>
            </footer>

            {editDialog.status !== 'closed' ? (
              <div
                className="dialog-backdrop"
                role="presentation"
                onClick={(event) => {
                  if (event.target === event.currentTarget && editDialog.status !== 'saving') {
                    closeEditDialog();
                  }
                }}
              >
                <div className="dialog" role="dialog" aria-modal="true" aria-labelledby="edit-qso-title">
                  {editDialog.form !== null ? (
                    <form className="dialog__body dialog__body--entry" onSubmit={handleEditSubmit}>
                      <div className="dialog__header">
                        <div>
                          <h3 id="edit-qso-title" className="dialog__title">
                            Edit QSO
                          </h3>
                        </div>
                        <button
                          className="button button--secondary button--dialog-close"
                          type="button"
                          onClick={closeEditDialog}
                          disabled={editDialog.status === 'saving'}
                        >
                          Close
                        </button>
                      </div>

                      {editDialog.message !== '' ? (
                        <p
                          className={
                            editDialog.status === 'saving'
                              ? 'list-status'
                              : 'submission-message submission-message--error'
                          }
                        >
                          {editDialog.message}
                        </p>
                      ) : null}

                      <section className="grid grid--top">
                        <label className="field field--wide">
                          <span>Freq</span>
                          <input
                            value={editDialog.form.frequency}
                            onChange={(event) => {
                              const nextValue = normalizeCzechNumberRow(event.target.value);
                              const derivedBand = getBandFromFrequency(nextValue);

                              setEditDialog((current) => {
                                if (current.form === null) {
                                  return current;
                                }

                                return {
                                  ...current,
                                  message: '',
                                  form: {
                                    ...current.form,
                                    frequency: nextValue,
                                    band: derivedBand ?? current.form.band,
                                  },
                                };
                              });
                            }}
                            onBlur={() => {
                              setEditDialog((current) => {
                                if (current.form === null) {
                                  return current;
                                }

                                return {
                                  ...current,
                                  form: {
                                    ...current.form,
                                    frequency: formatFrequencyInputValue(current.form.frequency),
                                  },
                                };
                              });
                            }}
                            inputMode="decimal"
                            required
                          />
                        </label>

                        <label className="field">
                          <span>Band</span>
                          <select
                            value={editDialog.form.band}
                            onChange={(event) => updateEditField('band', event.target.value)}
                          >
                            <option value="">-</option>
                            {bandOptions.map((option) => (
                              <option key={option} value={option}>
                                {option}
                              </option>
                            ))}
                          </select>
                        </label>

                        <label className="field">
                          <span>Mode</span>
                          <select
                            value={editDialog.form.mode}
                            onChange={(event) => updateEditField('mode', event.target.value)}
                          >
                            {modeOptions.map((option) => (
                              <option key={option} value={option}>
                                {option}
                              </option>
                            ))}
                          </select>
                        </label>

                        <label className="field">
                          <span>Pwr(W)</span>
                          <input
                            value={editDialog.form.power}
                            onChange={(event) => updateEditField('power', normalizeDigitsOnly(event.target.value))}
                            inputMode="numeric"
                          />
                        </label>
                      </section>

                      <section className="grid grid--callsign">
                        <label className="field">
                          <span>Callsign</span>
                          <input
                            value={editDialog.form.callsign}
                            onChange={(event) => {
                              const nextCallsign = normalizeCallsignInput(event.target.value);

                              setEditDialog((current) => {
                                if (current.form === null) {
                                  return current;
                                }

                                return {
                                  ...current,
                                  message: '',
                                  knownNoteId: null,
                                  callsignNoteDirty: false,
                                  form: {
                                    ...current.form,
                                    callsign: nextCallsign,
                                    callsignNote: '',
                                  },
                                };
                              });
                            }}
                            required
                          />
                        </label>

                        <label className="field field--rst">
                          <span>His RST</span>
                          <input
                            value={editDialog.form.rstReceived}
                            onChange={(event) =>
                              updateEditField('rstReceived', normalizeCzechNumberRow(event.target.value))
                            }
                            maxLength={5}
                          />
                        </label>

                        <label className="field field--rst">
                          <span>My RST</span>
                          <input
                            value={editDialog.form.rstSent}
                            onChange={(event) =>
                              updateEditField('rstSent', normalizeCzechNumberRow(event.target.value))
                            }
                            maxLength={5}
                          />
                        </label>

                        <label className="field field--wide field--callsign-name">
                          <span>Name</span>
                          <input
                            value={editDialog.form.name}
                            onChange={(event) => updateEditField('name', event.target.value)}
                            onBlur={() => normalizeEditFieldCapitalization('name')}
                            autoComplete="off"
                            data-1p-ignore="true"
                            data-lpignore="true"
                          />
                        </label>
                      </section>

                      <section className="grid grid--geo">
                        <label className="field field--wide">
                          <span>QTH</span>
                          <input
                            value={editDialog.form.qth}
                            onChange={(event) => updateEditField('qth', event.target.value)}
                            onBlur={() => normalizeEditFieldCapitalization('qth')}
                          />
                        </label>

                        <label className="field">
                          <span>Grid</span>
                          <input
                            value={editDialog.form.grid}
                            onChange={(event) =>
                              updateEditField('grid', normalizeCzechNumberRow(event.target.value).toUpperCase())
                            }
                          />
                        </label>

                        <label className="field">
                          <span>State</span>
                          <input
                            value={editDialog.form.state}
                            onChange={(event) => updateEditField('state', event.target.value.toUpperCase())}
                          />
                        </label>

                        <label className="field field--wide">
                          <span>County</span>
                          <input
                            value={editDialog.form.county}
                            onChange={(event) => updateEditField('county', event.target.value)}
                          />
                        </label>
                      </section>

                      <section className="grid grid--mid">
                        <label className="field">
                          <span>Award</span>
                          <input
                            value={editDialog.form.award}
                            onChange={(event) => updateEditField('award', event.target.value)}
                          />
                        </label>

                        <label className="field field--wide">
                          <span>Comment to QSO</span>
                          <input
                            value={editDialog.form.remarks}
                            onChange={(event) => updateEditField('remarks', event.target.value)}
                          />
                        </label>

                        <label className="field">
                          <span>QSL Via</span>
                          <input
                            value={editDialog.form.qslVia}
                            onChange={(event) =>
                              updateEditField('qslVia', normalizeCzechNumberRow(event.target.value).toUpperCase())
                            }
                          />
                        </label>
                      </section>

                      <label className="field field--textarea">
                        <span>Comment to callsign</span>
                        <textarea
                          value={editDialog.form.callsignNote}
                          onChange={(event) => updateEditField('callsignNote', event.target.value)}
                        />
                      </label>

                      <section className="grid grid--zones">
                        <label className="field">
                          <span>WAZ</span>
                          <input
                            value={editDialog.form.waz}
                            onChange={(event) => updateEditField('waz', normalizeCzechNumberRow(event.target.value))}
                            inputMode="numeric"
                          />
                        </label>

                        <label className="field">
                          <span>ITU</span>
                          <input
                            value={editDialog.form.itu}
                            onChange={(event) => updateEditField('itu', normalizeCzechNumberRow(event.target.value))}
                            inputMode="numeric"
                          />
                        </label>

                        <label className="field">
                          <span>IOTA</span>
                          <input
                            value={editDialog.form.iota}
                            onChange={(event) =>
                              updateEditField('iota', normalizeCzechNumberRow(event.target.value).toUpperCase())
                            }
                          />
                        </label>

                        <label className="field field--compact">
                          <span>QS</span>
                          <select
                            value={editDialog.form.qslSent}
                            onChange={(event) => updateEditField('qslSent', event.target.value)}
                          >
                            {qslOptions.map((option) => (
                              <option key={option} value={option}>
                                {option || '-'}
                              </option>
                            ))}
                          </select>
                        </label>

                        <label className="field field--compact">
                          <span>QR</span>
                          <select
                            value={editDialog.form.qslReceived}
                            onChange={(event) => updateEditField('qslReceived', event.target.value)}
                          >
                            {qslOptions.map((option) => (
                              <option key={option} value={option}>
                                {option || '-'}
                              </option>
                            ))}
                          </select>
                        </label>
                      </section>

                      <section className="grid grid--time">
                        <label className="field field--wide">
                          <span>Date</span>
                          <input
                            type="date"
                            value={editDialog.form.qsoDate}
                            onChange={(event) => updateEditField('qsoDate', event.target.value)}
                            required
                          />
                        </label>

                        <label className="field">
                          <span>Time on</span>
                          <input
                            type="time"
                            value={editDialog.form.timeOn}
                            onChange={(event) => updateEditField('timeOn', event.target.value)}
                            required
                          />
                        </label>

                        <label className="field">
                          <span>Time off</span>
                          <input
                            type="time"
                            value={editDialog.form.timeOff}
                            onChange={(event) => updateEditField('timeOff', event.target.value)}
                          />
                        </label>
                      </section>

                      <div className="dialog__actions">
                        <button
                          className="button button--secondary"
                          type="button"
                          onClick={closeEditDialog}
                          disabled={editDialog.status === 'saving'}
                        >
                          Cancel
                        </button>
                        <button className="button button--primary" type="submit" disabled={editDialog.status === 'saving'}>
                          {editDialog.status === 'saving' ? 'Saving…' : 'Save changes'}
                        </button>
                      </div>
                    </form>
                  ) : (
                    <div className="dialog__body">
                      <p className={editDialog.status === 'error' ? 'submission-message submission-message--error' : 'list-status'}>
                        {editDialog.message}
                      </p>
                    </div>
                  )}
                </div>
              </div>
            ) : null}
          </section>
        ) : isClusterView ? (
          <section className="panel panel--list">
            <header className="list-header">
              <div>
                <h2 className="list-header__title">DX Cluster</h2>
                {dxCluster.solarSummary !== '' ? (
                  <p className="list-header__count">{dxCluster.solarSummary}</p>
                ) : null}
                <p className="list-header__count">
                  {dxCluster.lastLoadedAt ? `Last loaded: ${dxCluster.lastLoadedAt}` : 'Polling every 20 seconds'}
                </p>
              </div>
              <button
                className="button button--secondary button--settings-action"
                type="button"
                onClick={() => setDxClusterReloadKey((current) => current + 1)}
                disabled={dxCluster.status === 'loading'}
              >
                Reload
              </button>
            </header>

            {dxCluster.status === 'error' ? (
              <p className="submission-message submission-message--error">{dxCluster.message}</p>
            ) : null}

            <div className="cluster-table cluster-table--full">
              <div className="cluster-table__head cluster-table__head--full">
                <span>Spotter</span>
                <span>Freq</span>
                <span>DX</span>
                <span>Info</span>
                <span>Spotdate</span>
                <span>Country</span>
                <span>Continent</span>
              </div>

              {dxCluster.items.length > 0 ? (
                dxCluster.items.map((item) => (
                  <div key={item.id} className="cluster-table__row cluster-table__row--full">
                    <span>{item.spotter}</span>
                    <span>{item.frequency}</span>
                    <span>{item.dx}</span>
                    <span>{item.info || '-'}</span>
                    <span>{item.spottedAt}</span>
                    <span>{item.country || '-'}</span>
                    <span>{item.continent || '-'}</span>
                  </div>
                ))
              ) : (
                <div className="cluster-table__empty cluster-table__empty--full">
                  {dxCluster.status === 'loading' ? 'Loading DX Cluster…' : dxCluster.message}
                </div>
              )}
            </div>
          </section>
        ) : (
          <section className="panel panel--settings">
            <header className="settings-header">
              <div>
                <span className="meta-strip__label">Frontend</span>
                <h2 className="settings-header__title">Settings</h2>
                <p className="settings-header__subtle">
                  These preferences are stored only in this browser on this device.
                </p>
              </div>
              <button className="button button--secondary button--settings-reset" type="button" onClick={resetFrontendSettings}>
                Reset defaults
              </button>
            </header>

            <div className="settings-grid">
              <section className="settings-card">
                <div className="settings-card__header">
                  <h3 className="settings-card__title">Appearance</h3>
                  <p className="settings-card__subtle">Visual preferences for this browser.</p>
                </div>

                <label className="setting-row">
                  <div>
                    <span className="setting-row__title">Theme</span>
                    <p className="setting-row__description">Choose light, dark, or follow the system appearance automatically.</p>
                  </div>
                  <select
                    className="settings-select"
                    value={settings.theme}
                    onChange={(event) => updateSetting('theme', event.target.value as ThemePreference)}
                  >
                    <option value="auto">Auto</option>
                    <option value="light">Light</option>
                    <option value="dark">Dark</option>
                  </select>
                </label>
              </section>

              <section className="settings-card">
                <div className="settings-card__header">
                  <h3 className="settings-card__title">QTH Profile</h3>
                  <p className="settings-card__subtle">Default station profile used for newly saved QSOs.</p>
                </div>

                <label className="setting-row">
                  <div>
                    <span className="setting-row__title">Active profile</span>
                  </div>
                </label>

                <label className="setting-row">
                  <div>
                    <span className="setting-row__title">Show hidden profiles</span>
                    <p className="setting-row__description">Include profiles with <code>visible = false</code> in the selector.</p>
                  </div>
                  <span className="setting-toggle">
                    <input
                      type="checkbox"
                      checked={settings.showHiddenProfiles}
                      onChange={(event) => updateSetting('showHiddenProfiles', event.target.checked)}
                    />
                    <span>{settings.showHiddenProfiles ? 'On' : 'Off'}</span>
                  </span>
                </label>

                <select
                  className="settings-select settings-select--full"
                  value={settings.defaultProfileId?.toString() ?? ''}
                  onChange={(event) =>
                    updateSetting(
                      'defaultProfileId',
                      event.target.value === '' ? null : Number.parseInt(event.target.value, 10),
                    )
                  }
                  disabled={profiles.status !== 'ready' || selectableProfiles.length === 0}
                >
                  <option value="">
                    {profiles.status === 'loading'
                      ? 'Loading profiles…'
                      : selectableProfiles.length === 0
                        ? 'No profiles'
                        : 'No profile'}
                  </option>
                  {selectableProfiles.map((profile) => (
                    <option key={profile.id} value={profile.id}>
                      #{profile.number}
                      {profile.qth ? ` | ${profile.qth}` : ''}
                      {profile.locator ? ` | ${profile.locator}` : ''}
                      {profile.rig ? ` | ${profile.rig}` : ''}
                      {!profile.visible ? ' | hidden' : ''}
                    </option>
                  ))}
                </select>

                <div className="settings-action-row">
                  <button className="button button--secondary button--settings-action" type="button" onClick={openCreateProfileDialog}>
                    Add
                  </button>
                  <button
                    className="button button--secondary button--settings-action"
                    type="button"
                    onClick={openEditProfileDialog}
                    disabled={selectedProfile === null}
                  >
                    Edit
                  </button>
                  <button
                    className="button button--secondary button--settings-action"
                    type="button"
                    onClick={() => void handleDeleteSelectedProfile()}
                    disabled={selectedProfile === null}
                  >
                    Delete
                  </button>
                </div>

                {profiles.status === 'error' ? <p className="settings-status settings-status--error">{profiles.message}</p> : null}
                {profiles.status === 'ready' && selectedProfile !== null ? (
                  <div className="settings-profile-summary">
                    <span className="meta-strip__label">Selected Profile</span>
                    <p className="settings-profile-summary__line">
                      #{selectedProfile.number}
                      {selectedProfile.qth ? ` | ${selectedProfile.qth}` : ''}
                      {selectedProfile.locator ? ` | ${selectedProfile.locator}` : ''}
                    </p>
                    {selectedProfile.rig ? <p className="settings-profile-summary__subtle">Rig: {selectedProfile.rig}</p> : null}
                    {selectedProfile.remarks ? (
                      <p className="settings-profile-summary__subtle">{selectedProfile.remarks}</p>
                    ) : null}
                  </div>
                ) : null}
              </section>

              <section className="settings-card">
                <div className="settings-card__header">
                  <h3 className="settings-card__title">Radio Sync</h3>
                  <p className="settings-card__subtle">Loads live frequency and mode from the JSON endpoint configured on the server.</p>
                </div>

                <label className="setting-row setting-row--stacked">
                  <div className="setting-row__content">
                    <span className="setting-row__title">JSON URL</span>
                    <p className="settings-profile-summary__line">
                      {radioSyncConfig === null ? 'Loading…' : (radioSyncConfig.url || 'Disabled')}
                    </p>
                    <p className="setting-row__description">
                      Configure this in <code>.env.local</code> or <code>.env</code> for local development, or in <code>.env.prod</code> for Docker production.
                    </p>
                  </div>
                </label>

                <label className="setting-row setting-row--stacked">
                  <div className="setting-row__content">
                    <span className="setting-row__title">Refresh interval</span>
                    <p className="settings-profile-summary__line">
                      {radioSyncConfig === null ? 'Loading…' : `${radioSyncConfig.pollIntervalSeconds} s`}
                    </p>
                    <p className="setting-row__description">
                      Configure this in <code>.env.local</code> or <code>.env</code> for local development, or in <code>.env.prod</code> for Docker production.
                    </p>
                  </div>
                </label>
              </section>
            </div>
          </section>
        )}

        {profileDialog.status !== 'closed' ? (
          <div
            className="dialog-backdrop"
            role="presentation"
            onClick={(event) => {
              if (event.target === event.currentTarget && profileDialog.status !== 'saving') {
                closeProfileDialog();
              }
            }}
          >
            <div className="dialog dialog--profile" role="dialog" aria-modal="true" aria-labelledby="profile-dialog-title">
              {profileDialog.form !== null ? (
                <form className="dialog__body" onSubmit={handleProfileSubmit}>
                  <div className="dialog__header">
                    <div>
                      <h3 id="profile-dialog-title" className="dialog__title">
                        {profileDialog.mode === 'create' ? 'Add profile' : 'Edit profile'}
                      </h3>
                      <p className="dialog__subtle">QTH profile settings used for new QSO entries.</p>
                    </div>
                    <button
                      className="button button--secondary button--dialog-close"
                      type="button"
                      onClick={closeProfileDialog}
                      disabled={profileDialog.status === 'saving'}
                    >
                      Close
                    </button>
                  </div>

                  {profileDialog.message !== '' ? (
                    <p
                      className={
                        profileDialog.status === 'saving'
                          ? 'list-status'
                          : 'submission-message submission-message--error'
                      }
                    >
                      {profileDialog.message}
                    </p>
                  ) : null}

                  <section className="dialog-grid dialog-grid--profile-top">
                    <label className="field">
                      <span>Profile nr.</span>
                      <input
                        value={profileDialog.form.number}
                        onChange={(event) => updateProfileFormField('number', normalizeCzechNumberRow(event.target.value))}
                        inputMode="numeric"
                        required
                      />
                    </label>

                    <label className="field">
                      <span>Locator</span>
                      <input
                        value={profileDialog.form.locator}
                        onChange={(event) => updateProfileFormField('locator', event.target.value.toUpperCase())}
                      />
                    </label>

                    <label className="offline-toggle offline-toggle--dialog">
                      <input
                        type="checkbox"
                        checked={profileDialog.form.visible}
                        onChange={(event) => updateProfileFormField('visible', event.target.checked)}
                      />
                      <span>Visible</span>
                    </label>
                  </section>

                  <label className="field">
                    <span>QTH</span>
                    <input value={profileDialog.form.qth} onChange={(event) => updateProfileFormField('qth', event.target.value)} />
                  </label>

                  <label className="field">
                    <span>Rig</span>
                    <input value={profileDialog.form.rig} onChange={(event) => updateProfileFormField('rig', event.target.value)} />
                  </label>

                  <label className="field field--textarea">
                    <span>Remarks</span>
                    <textarea
                      value={profileDialog.form.remarks}
                      onChange={(event) => updateProfileFormField('remarks', event.target.value)}
                      rows={4}
                    />
                  </label>

                  <div className="dialog__actions">
                    <button
                      className="button button--secondary"
                      type="button"
                      onClick={closeProfileDialog}
                      disabled={profileDialog.status === 'saving'}
                    >
                      Cancel
                    </button>
                    <button className="button button--primary" type="submit" disabled={profileDialog.status === 'saving'}>
                      {profileDialog.status === 'saving'
                        ? 'Saving…'
                        : profileDialog.mode === 'create'
                          ? 'Create profile'
                          : 'Save profile'}
                    </button>
                  </div>
                </form>
              ) : null}
            </div>
          </div>
        ) : null}
      </main>
      {authState === 'session-expired' ? (
        <div className="login-overlay">
          <form className="login-form" onSubmit={handleLogin}>
            <h1 className="login-title">Session expired</h1>
            <p className="login-subtitle">Please log in again to continue.</p>
            {loginError && <p className="login-error">{loginError}</p>}
            <label className="field">
              <span>Username</span>
              <input
                type="text"
                value={loginForm.username}
                onChange={(e) => setLoginForm((prev) => ({ ...prev, username: e.target.value }))}
                autoFocus
                autoComplete="username"
                autoCapitalize="none"
              />
            </label>
            <label className="field">
              <span>Password</span>
              <input
                type="password"
                value={loginForm.password}
                onChange={(e) => setLoginForm((prev) => ({ ...prev, password: e.target.value }))}
                autoComplete="current-password"
              />
            </label>
            <button className="button button--primary" type="submit">Log in</button>
          </form>
        </div>
      ) : null}
    </div>
  );
}
