import { useEffect, useRef } from 'react';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

export type RbnMapPoint = {
  call: string;
  lat: number;
  lon: number;
  bestSnr: number;
  label: string;
};

export type RbnMapHome = {
  lat: number;
  lon: number;
  label: string;
};

type RbnMapProps = {
  points: RbnMapPoint[];
  home: RbnMapHome | null;
  // Map re-fits its view when this changes (e.g. a different callsign was queried), not on every poll.
  fitKey: string;
};

export function snrColor(snr: number): string {
  if (snr >= 30) {
    return '#16a34a';
  }

  if (snr >= 20) {
    return '#65a30d';
  }

  if (snr >= 10) {
    return '#d97706';
  }

  return '#dc2626';
}

function escapeHtml(value: string): string {
  return value.replace(/[&<>"']/g, (char) => `&#${char.charCodeAt(0)};`);
}

export function RbnMap({ points, home, fitKey }: RbnMapProps) {
  const containerRef = useRef<HTMLDivElement | null>(null);
  const mapRef = useRef<L.Map | null>(null);
  const layerRef = useRef<L.LayerGroup | null>(null);
  const fittedKeyRef = useRef<string>('');
  const fittedCountRef = useRef(0);
  const userMovedRef = useRef(false);

  useEffect(() => {
    if (containerRef.current === null) {
      return undefined;
    }

    const map = L.map(containerRef.current, {
      center: [30, 10],
      zoom: 2,
      minZoom: 1,
      worldCopyJump: true,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 12,
      attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);

    // Remember manual pan/zoom so newly located skimmers do not yank the view away from the user.
    // DOM input events are used because Leaflet's move/zoom events also fire for our own fitBounds.
    const container = containerRef.current;
    const markUserMove = () => {
      userMovedRef.current = true;
    };
    container.addEventListener('pointerdown', markUserMove);
    container.addEventListener('wheel', markUserMove, { passive: true });

    mapRef.current = map;
    layerRef.current = L.layerGroup().addTo(map);

    // The map lives in a flex layout; recompute size once the container has its final dimensions.
    const resizeObserver = new ResizeObserver(() => map.invalidateSize());
    resizeObserver.observe(container);

    return () => {
      container.removeEventListener('pointerdown', markUserMove);
      container.removeEventListener('wheel', markUserMove);
      resizeObserver.disconnect();
      map.remove();
      mapRef.current = null;
      layerRef.current = null;
      fittedKeyRef.current = '';
    };
  }, []);

  useEffect(() => {
    const map = mapRef.current;
    const layer = layerRef.current;

    if (map === null || layer === null) {
      return;
    }

    layer.clearLayers();
    const bounds: L.LatLngExpression[] = [];

    if (home !== null) {
      for (const point of points) {
        L.polyline(
          [
            [home.lat, home.lon],
            [point.lat, point.lon],
          ],
          { color: snrColor(point.bestSnr), weight: 1.5, opacity: 0.45, interactive: false },
        ).addTo(layer);
      }

      L.circleMarker([home.lat, home.lon], {
        radius: 5,
        color: '#0b70e3',
        weight: 2.5,
        fillColor: '#ffffff',
        fillOpacity: 1,
      })
        .bindTooltip(escapeHtml(home.label))
        .addTo(layer);
      bounds.push([home.lat, home.lon]);
    }

    for (const point of points) {
      L.circleMarker([point.lat, point.lon], {
        // Kept small for the iPad screen: 3–6 px depending on SNR.
        radius: 3 + Math.min(Math.max(point.bestSnr, 0), 36) / 12,
        color: '#ffffff',
        weight: 1,
        fillColor: snrColor(point.bestSnr),
        fillOpacity: 0.9,
      })
        .bindTooltip(point.label.split('\n').map(escapeHtml).join('<br>'))
        .addTo(layer);
      bounds.push([point.lat, point.lon]);
    }

    if (fitKey !== fittedKeyRef.current) {
      fittedKeyRef.current = fitKey;
      fittedCountRef.current = 0;
      userMovedRef.current = false;
    }

    // Skimmer positions arrive gradually, so keep re-fitting while more appear, until the user moves the map.
    if (points.length > fittedCountRef.current && !userMovedRef.current) {
      fittedCountRef.current = points.length;
      map.fitBounds(L.latLngBounds(bounds), { padding: [28, 28], maxZoom: 6 });
    }
  }, [points, home, fitKey]);

  return <div ref={containerRef} className="rbn-map" />;
}
