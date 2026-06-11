import { useCallback, useEffect, useRef, useState } from 'react';
import type { PlaylistsPageMeta, TeachingsPlaylistGroup } from '../data/types';
import { fetchTeachingsPlaylistsPage } from '../lib/siteApi';
import { resolvePlaylistVideoCount } from '../lib/playlistGridLinks';

const DEFAULT_PER_PAGE = 15;

/**
 * Filtre les groupes playlist valides.
 *
 * @param rows Données brutes API.
 */
function normalizeGroups(rows: unknown): TeachingsPlaylistGroup[] {
  if (!Array.isArray(rows)) {
    return [];
  }

  return rows.filter((group): group is TeachingsPlaylistGroup => {
    if (group == null || typeof group !== 'object') {
      return false;
    }

    return resolvePlaylistVideoCount((group as TeachingsPlaylistGroup).videoCount) > 0;
  });
}

/**
 * Découpe une liste complète en page (mode compatibilité ancienne API).
 *
 * @param fullList Liste complète.
 * @param page Numéro de page.
 * @param perPage Taille de page.
 */
function slicePlaylistsPage(
  fullList: TeachingsPlaylistGroup[],
  page: number,
  perPage: number,
): { groups: TeachingsPlaylistGroup[]; meta: PlaylistsPageMeta } {
  const total = fullList.length;
  const lastPage = Math.max(1, Math.ceil(total / perPage));
  const offset = (page - 1) * perPage;

  return {
    groups: fullList.slice(offset, offset + perPage),
    meta: {
      current_page: page,
      last_page: lastPage,
      per_page: perPage,
      total,
      has_more: page < lastPage,
    },
  };
}

/**
 * Charge les playlists YouTube page par page (évite de tout charger d’un coup).
 *
 * @param perPage Nombre de playlists par requête.
 */
export function useTeachingsPlaylistsPaged(perPage = DEFAULT_PER_PAGE) {
  const [groups, setGroups] = useState<TeachingsPlaylistGroup[]>([]);
  const [meta, setMeta] = useState<PlaylistsPageMeta | null>(null);
  const [loading, setLoading] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const pageRef = useRef(1);
  const legacyFullListRef = useRef<TeachingsPlaylistGroup[] | null>(null);

  const loadFirstPage = useCallback(async () => {
    pageRef.current = 1;
    legacyFullListRef.current = null;
    setLoading(true);
    setError(null);

    try {
      const response = await fetchTeachingsPlaylistsPage(1, perPage);
      const rows = normalizeGroups(response.data);

      if (response.meta != null && typeof response.meta.has_more === 'boolean') {
        setGroups(rows);
        setMeta(response.meta);
        return;
      }

      legacyFullListRef.current = rows;
      const sliced = slicePlaylistsPage(rows, 1, perPage);
      setGroups(sliced.groups);
      setMeta(sliced.meta);
    } catch (err) {
      setGroups([]);
      setMeta(null);
      setError(err instanceof Error ? err.message : 'Chargement impossible.');
      console.error('[teachings/playlists]', err);
    } finally {
      setLoading(false);
    }
  }, [perPage]);

  useEffect(() => {
    void loadFirstPage();
  }, [loadFirstPage]);

  const loadMore = useCallback(async () => {
    if (loading || loadingMore || !meta?.has_more) {
      return;
    }

    const nextPage = pageRef.current + 1;
    setLoadingMore(true);

    try {
      if (legacyFullListRef.current != null) {
        const sliced = slicePlaylistsPage(legacyFullListRef.current, nextPage, perPage);
        pageRef.current = nextPage;
        setGroups((previous) => [...previous, ...sliced.groups]);
        setMeta(sliced.meta);
        setError(null);
        return;
      }

      const response = await fetchTeachingsPlaylistsPage(nextPage, perPage);
      const rows = normalizeGroups(response.data);
      pageRef.current = nextPage;

      setGroups((previous) => {
        const seen = new Set(previous.map((group) => group.eventId));
        const merged = [...previous];

        for (const group of rows) {
          if (!seen.has(group.eventId)) {
            merged.push(group);
          }
        }

        return merged;
      });
      setMeta(response.meta ?? null);
      setError(null);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Chargement impossible.');
      console.error('[teachings/playlists:more]', err);
    } finally {
      setLoadingMore(false);
    }
  }, [loading, loadingMore, meta?.has_more, perPage]);

  return {
    groups,
    loading,
    loadingMore,
    error,
    hasMore: meta?.has_more ?? false,
    total: meta?.total ?? 0,
    loadMore,
    reload: loadFirstPage,
  };
}
