import { useEffect, useRef, useState } from 'react';
import type { Event } from '../data/types';
import { fetchSiteList } from '../lib/siteApi';

/**
 * Charge les événements publics une seule fois (le carrousel ne relance pas ce chargement).
 *
 * @param fallback Jeu de données statique en cas d’erreur API ou liste vide.
 * @param limit Nombre maximum d’événements demandés (1–100).
 */
export type SiteEventsScope = 'highlight' | 'all';

/**
 * @param scope `highlight` = à venir, en cours ou à la une (défaut). `all` = tous les événements avec affiche.
 */
export function useSiteEvents(fallback: Event[], limit = 20, scope: SiteEventsScope = 'highlight') {
  const fallbackRef = useRef<Event[]>(fallback);
  fallbackRef.current = fallback;

  const [events, setEvents] = useState<Event[]>(fallbackRef.current);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let cancelled = false;

    async function load() {
      try {
        setLoading(true);
        const data = await fetchSiteList<Event>(
          `events?limit=${encodeURIComponent(String(limit))}&scope=${encodeURIComponent(scope)}`,
        );
        if (cancelled) {
          return;
        }
        if (data.length > 0) {
          setEvents(data);
          setError(null);
        } else {
          setEvents(fallbackRef.current);
          setError(null);
        }
      } catch (err) {
        if (!cancelled) {
          setEvents(fallbackRef.current);
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
  }, [limit, scope]);

  return { events, loading, error };
}
