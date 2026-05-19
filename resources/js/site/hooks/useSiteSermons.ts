import { useEffect, useState } from 'react';
import type { Sermon } from '../data/types';
import { fetchSiteJson } from '../lib/siteApi';

/**
 * Charge les publications (affichées comme sermons) depuis l'API Laravel.
 *
 * @param fallback Données de secours si l'API est vide ou inaccessible (désactivable).
 * @param limit Nombre maximum d'entrées demandées à l'API.
 * @param useFallback Si faux, liste vide en cas d'erreur ou de réponse vide (ex. accueil 100 % API).
 * @returns Objet `sermons`, indicateur `loading` et message d'`error` éventuel.
 */
export function useSiteSermons(fallback: Sermon[], limit = 36, useFallback = true) {
  const [sermons, setSermons] = useState<Sermon[]>(fallback);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let cancelled = false;

    async function load() {
      try {
        setLoading(true);
        const query = new URLSearchParams({
          tab: 'sermons',
          page: '1',
          per_page: String(limit),
        });
        const payload = await fetchSiteJson<{ data: Sermon[] }>(`posts?${query.toString()}`);
        const data = Array.isArray(payload.data) ? payload.data : [];
        if (cancelled) {
          return;
        }
        if (data.length > 0) {
          setSermons(data);
          setError(null);
        } else {
          setSermons(useFallback ? fallback : []);
          setError(null);
        }
      } catch (err) {
        if (!cancelled) {
          setSermons(useFallback ? fallback : []);
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
  }, [fallback, limit, useFallback]);

  return { sermons, loading, error };
}
