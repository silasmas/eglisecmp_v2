import { useEffect, useState } from 'react';
import { motion } from 'framer-motion';
import { Clock, Home, MapPin, Users } from 'lucide-react';
import PageHero from '../components/ui/PageHero';
import { cellGroups as fallbackCells } from '../data/locations';
import { fetchChurchCells, type PublicChurchCell } from '../lib/siteApi';

/**
 * Convertit les cellules statiques au format API.
 */
function mapFallback(): PublicChurchCell[] {
  return fallbackCells.map((cell) => ({
    id: cell.id,
    name: cell.name,
    commune: cell.commune,
    day: cell.day,
    time: cell.time,
    host: cell.host,
    description: cell.description,
    address: '',
    lat: null,
    lng: null,
  }));
}

/**
 * Page publique listant les cellules de maison CMP (API + fallback).
 */
export default function CellsPage() {
  const [cells, setCells] = useState<PublicChurchCell[]>(mapFallback());
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let cancelled = false;

    fetchChurchCells()
      .then((rows) => {
        if (!cancelled && rows.length > 0) {
          setCells(rows);
        }
      })
      .catch(() => {
        // Conserve le fallback local.
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

  return (
    <>
      <PageHero
        badge="Communauté"
        title="Nos cellules"
        description="Des foyers ouverts pour prier, partager la Parole et grandir ensemble pendant la semaine."
        backgroundImage="https://images.unsplash.com/photo-1511632765486-a01980e01a18?w=1400&h=600&fit=crop"
      />

      <section className="bg-gradient-to-b from-burgundy-50/40 to-white py-16 sm:py-20">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div className="mb-10 max-w-2xl">
            <h2 className="font-heading text-2xl font-bold text-surface-900">Rejoindre une cellule</h2>
            <p className="mt-2 text-sm leading-relaxed text-surface-600">
              Les cellules sont le cœur de la vie fraternelle à CMP. Contactez l’accueil pour être orienté
              vers la cellule la plus proche de chez vous.
              {loading ? ' Chargement…' : ''}
            </p>
          </div>

          <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
            {cells.map((cell, index) => (
              <motion.article
                key={cell.id}
                initial={{ opacity: 0, y: 16 }}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true }}
                transition={{ duration: 0.35, delay: index * 0.05 }}
                className="rounded-2xl border border-surface-200 bg-white p-6 shadow-sm"
              >
                <div className="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-burgundy-50 text-burgundy-800">
                  <Home className="h-5 w-5" aria-hidden />
                </div>
                <h3 className="font-heading text-lg font-semibold text-surface-900">{cell.name}</h3>
                <p className="mt-2 text-sm leading-relaxed text-surface-600">{cell.description}</p>
                <ul className="mt-4 space-y-2 text-sm text-surface-700">
                  <li className="flex items-center gap-2">
                    <MapPin className="h-4 w-4 text-burgundy-600" aria-hidden />
                    {cell.commune}
                  </li>
                  <li className="flex items-center gap-2">
                    <Clock className="h-4 w-4 text-burgundy-600" aria-hidden />
                    {cell.day} · {cell.time}
                  </li>
                  <li className="flex items-center gap-2">
                    <Users className="h-4 w-4 text-burgundy-600" aria-hidden />
                    Accueil : {cell.host}
                  </li>
                </ul>
              </motion.article>
            ))}
          </div>
        </div>
      </section>
    </>
  );
}
