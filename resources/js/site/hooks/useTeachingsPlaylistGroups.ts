import { useCallback, useEffect, useState } from 'react';
import type { TeachingsPlaylistGroup } from '../data/types';
import { fetchTeachingsMeditations, fetchTeachingsPlaylists } from '../lib/siteApi';

/**
 * Charge les groupes playlist pour l’onglet Méditations ou Playlists.
 *
 * @param scope meditations | playlists
 */
export function useTeachingsPlaylistGroups(scope: 'meditations' | 'playlists') {
  const [groups, setGroups] = useState<TeachingsPlaylistGroup[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const data =
        scope === 'meditations' ? await fetchTeachingsMeditations() : await fetchTeachingsPlaylists();
      const rows = Array.isArray(data) ? data : [];
      setGroups(
        rows.filter(
          (group): group is TeachingsPlaylistGroup =>
            group != null && typeof group === 'object' && (group.videoCount ?? 0) > 0,
        ),
      );
    } catch (err) {
      setGroups([]);
      setError(err instanceof Error ? err.message : 'Chargement impossible.');
    } finally {
      setLoading(false);
    }
  }, [scope]);

  useEffect(() => {
    void load();
  }, [load]);

  return { groups, loading, error, reload: load };
}
