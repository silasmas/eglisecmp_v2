import { useEffect, useState } from 'react';
import type { GalleryItem } from '../data/types';
import { fetchSiteList } from '../lib/siteApi';

/**
 * Charge les médias de galerie depuis l'API Laravel.
 *
 * @param fallback Jeu de secours (contenu statique).
 * @param limit Nombre maximum d'éléments demandés à l'API.
 * @returns Objet `items`, indicateur `loading` et message d'`error` éventuel.
 */
export function useSiteGallery(fallback: GalleryItem[], limit = 48) {
  const [items, setItems] = useState<GalleryItem[]>(fallback);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let cancelled = false;

    async function load() {
      try {
        setLoading(true);
        const data = await fetchSiteList<GalleryItem>(
          `galleries?limit=${encodeURIComponent(String(limit))}`,
        );
        if (cancelled) {
          return;
        }
        if (data.length > 0) {
          setItems(data);
          setError(null);
        } else {
          setItems(fallback);
          setError(null);
        }
      } catch (err) {
        if (!cancelled) {
          setItems(fallback);
          setError(err instanceof Error ? err.message : 'Erreur réseau');
        }
      } finally {
        if (!cancelled) {
          setLoading(false);
        }
      }
    }

    void load();

    return () => {
      cancelled = true;
    };
  }, [fallback, limit]);

  return { items, loading, error };
}
