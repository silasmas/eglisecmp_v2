import { useEffect, useRef, useState } from 'react';
import { fetchSiteData } from '../lib/siteApi';
import type { SiteHomeStatRow } from '../data/types';

/**
 * Charge les lignes « En chiffres » depuis l’API admin une seule fois au montage du composant parent.
 *
 * @param fallbackRows Valeurs de repli alignées sur `churchInfo` si l’API échoue ou est vide.
 * @returns Liste affichable et indicateur de chargement initial.
 */
export function useSiteHomeStatistics(fallbackRows: SiteHomeStatRow[]) {
  const fallbackRef = useRef<SiteHomeStatRow[]>(fallbackRows);
  fallbackRef.current = fallbackRows;

  const [rows, setRows] = useState<SiteHomeStatRow[]>(() => fallbackRows);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let cancelled = false;

    async function load() {
      try {
        const data = await fetchSiteData<SiteHomeStatRow[]>('statistics');
        if (cancelled) {
          return;
        }
        if (Array.isArray(data) && data.length > 0) {
          setRows(data);
        } else {
          setRows(fallbackRef.current);
        }
      } catch {
        if (!cancelled) {
          setRows(fallbackRef.current);
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
  }, []);

  return { stats: rows, loading };
}
