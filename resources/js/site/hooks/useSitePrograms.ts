import { useEffect, useState } from 'react';
import type { Program } from '../data/types';
import { fetchSiteList } from '../lib/siteApi';

type ScheduleProgramRow = {
  id: string;
  kind: string;
  name: string;
  description: string;
  day: string;
  time: string;
  icon: string;
  gridWide: boolean;
};

/**
 * Mappe une ligne API vers le type `Program` utilisé par la grille d'accueil.
 *
 * @param row Objet renvoyé par `SitePublicSerializer::scheduleProgramToPublicArray`.
 * @returns Entrée compatible avec `ProgramsSection`.
 */
function mapProgramRow(row: ScheduleProgramRow): Program {
  return {
    id: row.id,
    name: row.name,
    day: row.day,
    time: row.time,
    description: row.description,
    icon: row.icon,
    kind: row.kind,
    gridWide: row.gridWide,
  };
}

/**
 * Charge les programmes publics depuis l'API Laravel.
 *
 * @param fallback Jeu de secours si l'API est vide ou en erreur.
 * @returns Objet `programs`, indicateur `loading` et message d'`error` éventuel.
 */
export function useSitePrograms(fallback: Program[]) {
  const [programs, setPrograms] = useState<Program[]>(fallback);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let cancelled = false;

    async function load() {
      try {
        setLoading(true);
        const rows = await fetchSiteList<ScheduleProgramRow>('programs');
        if (cancelled) {
          return;
        }
        if (rows.length > 0) {
          setPrograms(rows.map(mapProgramRow));
          setError(null);
        } else {
          setPrograms(fallback);
          setError(null);
        }
      } catch (err) {
        if (!cancelled) {
          setPrograms(fallback);
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
  }, [fallback]);

  return { programs, loading, error };
}
