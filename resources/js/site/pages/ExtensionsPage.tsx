import { useEffect, useMemo, useRef, useState } from 'react';
import { motion } from 'framer-motion';
import { Globe2, MapPin } from 'lucide-react';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import PageHero from '../components/ui/PageHero';
import { churchExtensions as fallbackExtensions } from '../data/locations';
import { fetchChurchExtensions, type PublicChurchExtension } from '../lib/siteApi';
import { cn } from '../lib/utils';

/**
 * Convertit les données statiques de fallback au format API.
 */
function mapFallback(): PublicChurchExtension[] {
  return fallbackExtensions.map((item) => ({
    id: item.id,
    name: item.name,
    city: item.city,
    country: item.country,
    address: item.address,
    description: item.description,
    lat: item.lat,
    lng: item.lng,
    leaderName: '',
    leaderPhotoUrl: '',
  }));
}

/**
 * Page des extensions CMP : liste API + carte mondiale + photo du dirigeant.
 */
export default function ExtensionsPage() {
  const [extensions, setExtensions] = useState<PublicChurchExtension[]>(mapFallback());
  const [selectedId, setSelectedId] = useState<string>('');
  const [loading, setLoading] = useState(true);
  const mapRef = useRef<HTMLDivElement | null>(null);
  const mapInstance = useRef<L.Map | null>(null);
  const markersRef = useRef<Map<string, L.Marker>>(new Map());

  useEffect(() => {
    let cancelled = false;
    fetchChurchExtensions()
      .then((rows) => {
        if (!cancelled && rows.length > 0) {
          setExtensions(rows);
          setSelectedId(rows[0].id);
        } else if (!cancelled) {
          const fallback = mapFallback();
          setExtensions(fallback);
          setSelectedId(fallback[0]?.id ?? '');
        }
      })
      .catch(() => {
        if (!cancelled) {
          const fallback = mapFallback();
          setExtensions(fallback);
          setSelectedId(fallback[0]?.id ?? '');
        }
      })
      .finally(() => {
        if (!cancelled) {
          setLoading(false);
        }
      });

    return () => {
      cancelled = true;
    };
  }, []);

  const selected = useMemo(
    () => extensions.find((item) => item.id === selectedId) ?? extensions[0],
    [extensions, selectedId],
  );

  useEffect(() => {
    if (mapRef.current === null || extensions.length === 0) {
      return undefined;
    }

    if (mapInstance.current !== null) {
      mapInstance.current.remove();
      mapInstance.current = null;
      markersRef.current = new Map();
    }

    const map = L.map(mapRef.current, {
      center: [10, 20],
      zoom: 2,
      minZoom: 2,
      worldCopyJump: true,
      scrollWheelZoom: false,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap',
      maxZoom: 18,
    }).addTo(map);

    const markers = new Map<string, L.Marker>();

    extensions.forEach((extension) => {
      const icon = L.divIcon({
        className: '',
        html: `<div style="width:14px;height:14px;border-radius:9999px;background:#7b1d3e;border:2px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.35);"></div>`,
        iconSize: [14, 14],
        iconAnchor: [7, 7],
      });

      const marker = L.marker([extension.lat, extension.lng], { icon })
        .addTo(map)
        .bindPopup(`<strong>${extension.name}</strong><br/>${extension.city}, ${extension.country}`);

      marker.on('click', () => {
        setSelectedId(extension.id);
      });

      markers.set(extension.id, marker);
    });

    markersRef.current = markers;
    mapInstance.current = map;

    const timer = window.setTimeout(() => {
      map.invalidateSize();
    }, 200);

    return () => {
      window.clearTimeout(timer);
      map.remove();
      mapInstance.current = null;
      markersRef.current = new Map();
    };
  }, [extensions]);

  useEffect(() => {
    if (selected === undefined || mapInstance.current === null) {
      return;
    }

    mapInstance.current.flyTo([selected.lat, selected.lng], 5, { duration: 0.85 });
    markersRef.current.get(selected.id)?.openPopup();
  }, [selected]);

  return (
    <>
      <PageHero
        badge="Mission"
        title="Nos extensions"
        description="CMP rayonne au-delà de Kinshasa : découvrez nos assemblées et points de présence dans le monde."
        backgroundImage="https://images.unsplash.com/photo-1529156069898-49953e39b3ac?w=1400&h=600&fit=crop"
      />

      <section className="py-14 sm:py-20">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div className="mb-8 flex items-center gap-3">
            <span className="flex h-11 w-11 items-center justify-center rounded-2xl bg-burgundy-50 text-burgundy-800">
              <Globe2 className="h-5 w-5" aria-hidden />
            </span>
            <div>
              <h2 className="font-heading text-xl font-bold text-surface-900">Carte mondiale</h2>
              <p className="text-sm text-surface-600">
                {loading ? 'Chargement…' : 'Cliquez une extension dans la liste ou un point sur la carte.'}
              </p>
            </div>
          </div>

          <div className="grid gap-6 lg:grid-cols-[340px_minmax(0,1fr)]">
            <div className="max-h-[560px] space-y-2 overflow-y-auto rounded-2xl border border-surface-200 bg-white p-3 shadow-sm">
              {extensions.map((extension) => (
                <button
                  key={extension.id}
                  type="button"
                  onClick={() => setSelectedId(extension.id)}
                  className={cn(
                    'flex w-full items-center gap-3 rounded-xl px-3 py-3 text-left transition',
                    selectedId === extension.id
                      ? 'bg-burgundy-900 text-white'
                      : 'bg-surface-50 text-surface-800 hover:bg-burgundy-50',
                  )}
                >
                  {extension.leaderPhotoUrl !== '' ? (
                    <img
                      src={extension.leaderPhotoUrl}
                      alt={extension.leaderName || extension.name}
                      className="h-10 w-10 shrink-0 rounded-full object-cover ring-2 ring-white/40"
                    />
                  ) : (
                    <span
                      className={cn(
                        'flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-xs font-bold',
                        selectedId === extension.id ? 'bg-white/15 text-white' : 'bg-burgundy-100 text-burgundy-800',
                      )}
                    >
                      {(extension.leaderName || extension.name).slice(0, 1)}
                    </span>
                  )}
                  <span className="min-w-0">
                    <span className="block font-heading text-sm font-semibold">{extension.name}</span>
                    <span
                      className={cn(
                        'mt-0.5 block text-xs',
                        selectedId === extension.id ? 'text-white/80' : 'text-surface-500',
                      )}
                    >
                      {extension.city}, {extension.country}
                    </span>
                  </span>
                </button>
              ))}
            </div>

            <div className="overflow-hidden rounded-2xl border border-surface-200 bg-white shadow-sm">
              <div ref={mapRef} className="h-[320px] w-full bg-surface-100 sm:h-[420px] lg:h-[560px]" />
              {selected !== undefined ? (
                <div className="flex gap-4 border-t border-surface-100 px-5 py-4">
                  {selected.leaderPhotoUrl !== '' ? (
                    <img
                      src={selected.leaderPhotoUrl}
                      alt={selected.leaderName || selected.name}
                      className="h-16 w-16 shrink-0 rounded-full object-cover ring-2 ring-burgundy-100"
                    />
                  ) : null}
                  <div>
                    <p className="inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-burgundy-700">
                      <MapPin className="h-3.5 w-3.5" aria-hidden />
                      {selected.country}
                    </p>
                    <h3 className="mt-1 font-heading text-lg font-bold text-surface-900">{selected.name}</h3>
                    {selected.leaderName !== '' ? (
                      <p className="mt-0.5 text-sm font-medium text-surface-700">{selected.leaderName}</p>
                    ) : null}
                    <p className="mt-1 text-sm text-surface-600">{selected.address}</p>
                    <p className="mt-2 text-sm leading-relaxed text-surface-700">{selected.description}</p>
                  </div>
                </div>
              ) : null}
            </div>
          </div>

          <div className="mt-10 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {extensions.map((extension, index) => (
              <motion.article
                key={`card-${extension.id}`}
                initial={{ opacity: 0, y: 12 }}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true }}
                transition={{ duration: 0.3, delay: index * 0.03 }}
                className="rounded-2xl border border-surface-200 bg-white p-5 shadow-sm"
              >
                <div className="mb-3 flex items-center gap-3">
                  {extension.leaderPhotoUrl !== '' ? (
                    <img
                      src={extension.leaderPhotoUrl}
                      alt={extension.leaderName || extension.name}
                      className="h-12 w-12 rounded-full object-cover ring-2 ring-burgundy-100"
                    />
                  ) : (
                    <span className="flex h-12 w-12 items-center justify-center rounded-full bg-burgundy-50 text-sm font-bold text-burgundy-800">
                      {(extension.leaderName || extension.name).slice(0, 1)}
                    </span>
                  )}
                  <div>
                    <h3 className="font-heading text-base font-semibold text-surface-900">{extension.name}</h3>
                    <p className="text-sm text-burgundy-700">
                      {extension.city} · {extension.country}
                    </p>
                  </div>
                </div>
                {extension.leaderName !== '' ? (
                  <p className="text-xs font-medium text-surface-500">{extension.leaderName}</p>
                ) : null}
                <p className="mt-2 text-sm leading-relaxed text-surface-600">{extension.description}</p>
              </motion.article>
            ))}
          </div>
        </div>
      </section>
    </>
  );
}
