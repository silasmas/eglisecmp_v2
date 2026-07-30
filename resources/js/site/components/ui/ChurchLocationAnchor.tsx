import { useEffect, useRef, useState } from 'react';
import { AnimatePresence, motion } from 'framer-motion';
import { Check, Copy, Navigation, Route, Share2, X } from 'lucide-react';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { churchInfo } from '../../data/content';
import { churchGeo } from '../../data/locations';
import { cn } from '../../lib/utils';

type RouteInfo = {
  distanceKm: number;
  durationMin: number;
};

/**
 * Icône pin style Google Maps (multicolore).
 */
function GoogleMapsPinIcon({ className }: { className?: string }) {
  return (
    <svg viewBox="0 0 48 48" className={className} aria-hidden>
      <defs>
        <linearGradient id="cmpMapsPinGrad" x1="8" y1="4" x2="40" y2="44" gradientUnits="userSpaceOnUse">
          <stop offset="0%" stopColor="#EA4335" />
          <stop offset="35%" stopColor="#FBBC04" />
          <stop offset="65%" stopColor="#34A853" />
          <stop offset="100%" stopColor="#4285F4" />
        </linearGradient>
      </defs>
      <path
        fill="url(#cmpMapsPinGrad)"
        d="M24 4c-8.3 0-15 6.5-15 14.5C9 28.2 24 44 24 44s15-15.8 15-25.5C39 10.5 32.3 4 24 4z"
      />
      <circle cx="24" cy="18" r="6.2" fill="#fff" />
    </svg>
  );
}

/**
 * Copie un texte dans le presse-papiers (API moderne + fallback).
 *
 * @param text Texte à copier.
 * @returns true si la copie a réussi.
 */
async function copyTextToClipboard(text: string): Promise<boolean> {
  if (text.trim() === '') {
    return false;
  }

  try {
    if (navigator.clipboard?.writeText !== undefined) {
      await navigator.clipboard.writeText(text);
      return true;
    }
  } catch {
    // fallback ci-dessous
  }

  try {
    const area = document.createElement('textarea');
    area.value = text;
    area.setAttribute('readonly', '');
    area.style.position = 'fixed';
    area.style.left = '-9999px';
    document.body.appendChild(area);
    area.select();
    const ok = document.execCommand('copy');
    document.body.removeChild(area);
    return ok;
  } catch {
    return false;
  }
}

/**
 * Bouton localisation (pill gris) + modale carte / itinéraire / partage.
 */
export default function ChurchLocationAnchor() {
  const [open, setOpen] = useState(false);
  const [hintVisible, setHintVisible] = useState(true);
  const [copied, setCopied] = useState(false);
  const [shared, setShared] = useState(false);
  const [routeInfo, setRouteInfo] = useState<RouteInfo | null>(null);
  const [routeError, setRouteError] = useState<string | null>(null);
  const [routing, setRouting] = useState(false);
  const mapRef = useRef<HTMLDivElement | null>(null);
  const mapInstance = useRef<L.Map | null>(null);
  const routeLayer = useRef<L.Polyline | null>(null);

  const mapsDirectionsUrl =
    `https://www.google.com/maps/dir/?api=1` +
    `&destination=${encodeURIComponent(`${churchGeo.lat},${churchGeo.lng}`)}` +
    `&destination_place_id=` +
    `&travelmode=driving`;

  const mapsPlaceUrl =
    `https://www.google.com/maps/search/?api=1` +
    `&query=${encodeURIComponent(`${churchGeo.lat},${churchGeo.lng}`)}`;

  useEffect(() => {
    const timer = window.setInterval(() => {
      setHintVisible((previous) => !previous);
    }, 3200);
    return () => window.clearInterval(timer);
  }, []);

  useEffect(() => {
    if (!open || mapRef.current === null) {
      return undefined;
    }

    const map = L.map(mapRef.current, {
      center: [churchGeo.lat, churchGeo.lng],
      zoom: 16,
      scrollWheelZoom: false,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap',
      maxZoom: 19,
    }).addTo(map);

    const icon = L.divIcon({
      className: '',
      html: `<div style="width:28px;height:28px;border-radius:9999px;background:#7b1d3e;border:3px solid #fff;box-shadow:0 4px 12px rgba(0,0,0,.35);"></div>`,
      iconSize: [28, 28],
      iconAnchor: [14, 14],
    });

    L.marker([churchGeo.lat, churchGeo.lng], { icon })
      .addTo(map)
      .bindPopup(`<strong>${churchGeo.mapLabel}</strong><br/>${churchInfo.address}`);

    mapInstance.current = map;

    const resizeTimer = window.setTimeout(() => {
      map.invalidateSize();
    }, 180);

    return () => {
      window.clearTimeout(resizeTimer);
      map.remove();
      mapInstance.current = null;
      routeLayer.current = null;
    };
  }, [open]);

  /**
   * Copie l'adresse de l'église.
   */
  const copyAddress = async () => {
    const ok = await copyTextToClipboard(churchInfo.address);
    setCopied(ok);
    if (ok) {
      window.setTimeout(() => setCopied(false), 2200);
    }
  };

  /**
   * Partage le lien d'itinéraire Google Maps (share natif ou copie).
   */
  const shareItinerary = async () => {
    const sharePayload = {
      title: `${churchInfo.name} — Itinéraire`,
      text: `Itinéraire vers ${churchInfo.name} : ${churchInfo.address}`,
      url: mapsDirectionsUrl,
    };

    try {
      if (navigator.share !== undefined) {
        await navigator.share(sharePayload);
        setShared(true);
        window.setTimeout(() => setShared(false), 2200);
        return;
      }
    } catch {
      // utilisateur a annulé ou share indisponible → copie
    }

    const ok = await copyTextToClipboard(mapsDirectionsUrl);
    setShared(ok);
    if (ok) {
      window.setTimeout(() => setShared(false), 2200);
    }
  };

  /**
   * Calcule un itinéraire OSRM depuis la position de l'utilisateur.
   */
  const traceRoute = () => {
    setRouteError(null);
    setRouteInfo(null);
    setRouting(true);

    if (!navigator.geolocation) {
      setRouting(false);
      setRouteError('La géolocalisation n’est pas disponible sur cet appareil.');
      return;
    }

    navigator.geolocation.getCurrentPosition(
      async (position) => {
        const originLat = position.coords.latitude;
        const originLng = position.coords.longitude;
        const url =
          `https://router.project-osrm.org/route/v1/driving/` +
          `${originLng},${originLat};${churchGeo.lng},${churchGeo.lat}` +
          `?overview=full&geometries=geojson`;

        try {
          const response = await fetch(url);
          const data = (await response.json()) as {
            code?: string;
            routes?: Array<{
              distance: number;
              duration: number;
              geometry: { coordinates: Array<[number, number]> };
            }>;
          };

          const route = data.routes?.[0];
          if (!response.ok || data.code !== 'Ok' || route === undefined) {
            throw new Error('Itinéraire indisponible');
          }

          const latLngs = route.geometry.coordinates.map(
            ([lng, lat]) => [lat, lng] as [number, number],
          );

          if (mapInstance.current !== null) {
            if (routeLayer.current !== null) {
              mapInstance.current.removeLayer(routeLayer.current);
            }
            routeLayer.current = L.polyline(latLngs, {
              color: '#7b1d3e',
              weight: 5,
              opacity: 0.9,
            }).addTo(mapInstance.current);

            const userIcon = L.divIcon({
              className: '',
              html: `<div style="width:16px;height:16px;border-radius:9999px;background:#2563eb;border:2px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.3);"></div>`,
              iconSize: [16, 16],
              iconAnchor: [8, 8],
            });
            L.marker([originLat, originLng], { icon: userIcon }).addTo(mapInstance.current);
            mapInstance.current.fitBounds(routeLayer.current.getBounds(), { padding: [36, 36] });
          }

          setRouteInfo({
            distanceKm: route.distance / 1000,
            durationMin: Math.round(route.duration / 60),
          });
        } catch {
          setRouteError('Impossible de calculer l’itinéraire. Réessayez ou ouvrez Google Maps.');
        } finally {
          setRouting(false);
        }
      },
      () => {
        setRouting(false);
        setRouteError('Autorisez la localisation pour tracer votre itinéraire.');
      },
      { enableHighAccuracy: true, timeout: 12000 },
    );
  };

  return (
    <>
      <div className="relative z-20 -mb-10 -translate-y-8 flex justify-center pointer-events-none sm:-translate-y-10">
        <motion.button
          type="button"
          initial={{ y: 0 }}
          animate={{ y: [0, -9, 0] }}
          transition={{ duration: 1.55, repeat: Infinity, ease: 'easeInOut' }}
          onClick={() => setOpen(true)}
          className="pointer-events-auto flex max-w-[min(92vw,340px)] items-center gap-2.5 rounded-full bg-[#4a4b53] px-3.5 py-2.5 text-white shadow-xl shadow-black/25 ring-4 ring-white/40"
          aria-label="Voir l’adresse de l’église sur la carte"
        >
          <GoogleMapsPinIcon className="h-8 w-8 shrink-0 drop-shadow" />
          <span className="min-w-0 flex-1 text-left">
            <AnimatePresence mode="wait">
              {hintVisible ? (
                <motion.span
                  key="hint"
                  initial={{ opacity: 0, y: 5 }}
                  animate={{ opacity: 1, y: 0 }}
                  exit={{ opacity: 0, y: -5 }}
                  className="block truncate text-sm font-medium leading-snug"
                >
                  Cliquez pour avoir l’adresse de l’église
                </motion.span>
              ) : (
                <motion.span
                  key="addr"
                  initial={{ opacity: 0, y: 5 }}
                  animate={{ opacity: 1, y: 0 }}
                  exit={{ opacity: 0, y: -5 }}
                  className="block truncate text-sm font-medium leading-snug text-white/90"
                >
                  Voir l’adresse · {churchInfo.shortAddress}
                </motion.span>
              )}
            </AnimatePresence>
          </span>
        </motion.button>
      </div>

      <AnimatePresence>
        {open ? (
          <motion.div
            className="fixed inset-0 z-[130] flex items-end justify-center bg-black/50 p-3 sm:items-center sm:p-6"
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            onClick={() => setOpen(false)}
          >
            <motion.div
              role="dialog"
              aria-modal="true"
              aria-label="Carte de l’église"
              initial={{ opacity: 0, y: 24, scale: 0.98 }}
              animate={{ opacity: 1, y: 0, scale: 1 }}
              exit={{ opacity: 0, y: 16, scale: 0.98 }}
              transition={{ duration: 0.22 }}
              className="relative max-h-[92vh] w-full max-w-3xl overflow-y-auto rounded-3xl bg-white shadow-2xl"
              onClick={(event) => event.stopPropagation()}
            >
              <div className="flex items-start justify-between gap-3 border-b border-surface-100 px-5 py-4">
                <div>
                  <p className="text-xs font-semibold uppercase tracking-wide text-burgundy-700">CMP</p>
                  <h2 className="font-heading text-lg font-bold text-surface-900">Nous trouver</h2>
                  <p className="mt-1 text-sm text-surface-600">{churchInfo.address}</p>
                </div>
                <button
                  type="button"
                  onClick={() => setOpen(false)}
                  className="rounded-full border border-surface-200 p-2 text-surface-600 hover:bg-surface-50"
                  aria-label="Fermer"
                >
                  <X className="h-5 w-5" />
                </button>
              </div>

              <div ref={mapRef} className="h-64 w-full bg-surface-100 sm:h-80" />

              <div className="space-y-3 px-5 py-4">
                {routeInfo !== null ? (
                  <div className="flex flex-wrap gap-3 rounded-2xl bg-burgundy-50 px-4 py-3 text-sm text-burgundy-900">
                    <span className="inline-flex items-center gap-1.5 font-semibold">
                      <Route className="h-4 w-4" />
                      {routeInfo.distanceKm.toFixed(1)} km
                    </span>
                    <span className="inline-flex items-center gap-1.5">
                      <Navigation className="h-4 w-4" />
                      environ {routeInfo.durationMin} min en voiture
                    </span>
                  </div>
                ) : null}

                {routeError !== null ? (
                  <p className="rounded-xl bg-amber-50 px-3 py-2 text-sm text-amber-900">{routeError}</p>
                ) : null}

                <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
                  <button
                    type="button"
                    onClick={() => void copyAddress()}
                    className={cn(
                      'inline-flex items-center justify-center gap-2 rounded-xl border px-4 py-3 text-sm font-semibold transition',
                      copied
                        ? 'border-emerald-300 bg-emerald-50 text-emerald-800'
                        : 'border-surface-200 bg-white text-surface-800 hover:bg-surface-50',
                    )}
                  >
                    {copied ? <Check className="h-4 w-4" /> : <Copy className="h-4 w-4" />}
                    {copied ? 'Adresse copiée' : 'Copier l’adresse'}
                  </button>
                  <button
                    type="button"
                    onClick={() => void shareItinerary()}
                    className={cn(
                      'inline-flex items-center justify-center gap-2 rounded-xl border px-4 py-3 text-sm font-semibold transition',
                      shared
                        ? 'border-emerald-300 bg-emerald-50 text-emerald-800'
                        : 'border-surface-200 bg-white text-surface-800 hover:bg-surface-50',
                    )}
                  >
                    {shared ? <Check className="h-4 w-4" /> : <Share2 className="h-4 w-4" />}
                    {shared ? 'Lien prêt' : 'Partager l’itinéraire'}
                  </button>
                  <button
                    type="button"
                    disabled={routing}
                    onClick={traceRoute}
                    className="inline-flex items-center justify-center gap-2 rounded-xl bg-burgundy-900 px-4 py-3 text-sm font-semibold text-white hover:bg-burgundy-800 disabled:opacity-60 sm:col-span-1"
                  >
                    <Navigation className="h-4 w-4" />
                    {routing ? 'Calcul…' : 'Tracer mon itinéraire'}
                  </button>
                  <a
                    href={mapsPlaceUrl}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="inline-flex items-center justify-center gap-2 rounded-xl border border-burgundy-200 bg-burgundy-50 px-4 py-3 text-sm font-semibold text-burgundy-900 hover:bg-burgundy-100"
                  >
                    <GoogleMapsPinIcon className="h-5 w-5" />
                    Ouvrir dans Maps
                  </a>
                </div>
              </div>
            </motion.div>
          </motion.div>
        ) : null}
      </AnimatePresence>
    </>
  );
}
