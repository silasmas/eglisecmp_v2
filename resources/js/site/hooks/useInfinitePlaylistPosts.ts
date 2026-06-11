import { useCallback, useEffect, useRef, useState } from 'react';
import type { PostsPageMeta, Sermon } from '../data/types';
import { fetchSitePostsPage } from '../lib/siteApi';

const PER_PAGE = 12;

type PlaylistPostsMode = 'playlist' | 'meditation';

type UseInfinitePlaylistPostsOptions = {
  eventId: string | undefined;
  mode: PlaylistPostsMode;
  weeklyDay?: string;
  searchQuery?: string;
};

/**
 * Charge les messages d’une playlist ou d’un jour de méditation page par page (scroll infini).
 *
 * @param options Identifiant événement, mode et filtres.
 */
export function useInfinitePlaylistPosts({
  eventId,
  mode,
  weeklyDay = '',
  searchQuery = '',
}: UseInfinitePlaylistPostsOptions) {
  const [items, setItems] = useState<Sermon[]>([]);
  const [meta, setMeta] = useState<PostsPageMeta | null>(null);
  const [loading, setLoading] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const pageRef = useRef(1);

  const canLoad = mode === 'meditation' ? weeklyDay.trim() !== '' : typeof eventId === 'string' && eventId !== '';

  const resetAndLoad = useCallback(async () => {
    if (!canLoad) {
      setItems([]);
      setMeta(null);
      setLoading(false);
      return;
    }

    pageRef.current = 1;
    setLoading(true);
    setError(null);

    try {
      const tab = mode === 'meditation' ? 'meditations' : 'playlists';
      const response = await fetchSitePostsPage(tab, 1, PER_PAGE, {
        eventId: mode === 'playlist' ? eventId : undefined,
        weeklyServiceDay: mode === 'meditation' ? weeklyDay.trim() : undefined,
        search: searchQuery.trim() !== '' ? searchQuery.trim() : undefined,
      });
      setItems(response.data ?? []);
      setMeta(response.meta ?? null);
    } catch (err) {
      setItems([]);
      setMeta(null);
      setError(err instanceof Error ? err.message : 'Erreur réseau');
    } finally {
      setLoading(false);
    }
  }, [canLoad, eventId, mode, searchQuery, weeklyDay]);

  useEffect(() => {
    void resetAndLoad();
  }, [resetAndLoad]);

  const loadMore = useCallback(async () => {
    if (!canLoad || loading || loadingMore || !meta?.has_more) {
      return;
    }

    const nextPage = pageRef.current + 1;
    setLoadingMore(true);

    try {
      const tab = mode === 'meditation' ? 'meditations' : 'playlists';
      const response = await fetchSitePostsPage(tab, nextPage, PER_PAGE, {
        eventId: mode === 'playlist' ? eventId : undefined,
        weeklyServiceDay: mode === 'meditation' ? weeklyDay.trim() : undefined,
        search: searchQuery.trim() !== '' ? searchQuery.trim() : undefined,
      });
      pageRef.current = nextPage;
      setItems((previous) => {
        const seen = new Set(previous.map((item) => item.id));
        const merged = [...previous];

        for (const row of response.data ?? []) {
          if (!seen.has(row.id)) {
            merged.push(row);
          }
        }

        return merged;
      });
      setMeta(response.meta ?? null);
      setError(null);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Erreur réseau');
    } finally {
      setLoadingMore(false);
    }
  }, [canLoad, eventId, loading, loadingMore, meta?.has_more, mode, searchQuery, weeklyDay]);

  return {
    items,
    loading,
    loadingMore,
    error,
    hasMore: meta?.has_more ?? false,
    total: meta?.total ?? 0,
    loadMore,
    reload: resetAndLoad,
  };
}
