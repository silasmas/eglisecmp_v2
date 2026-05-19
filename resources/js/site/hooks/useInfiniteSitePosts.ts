import { useCallback, useEffect, useRef, useState } from 'react';
import type { PostsPageMeta, Sermon, TeachingsTab } from '../data/types';
import { fetchSitePostsPage } from '../lib/siteApi';

const PER_PAGE = 12;

/**
 * Charge les publications page par page avec détection de fin (scroll infini).
 *
 * @param tab Onglet actif (messages, méditations, playlists).
 * @param searchQuery Filtre texte transmis à l’API (`search`, optionnel).
 * @returns Liste cumulée, états de chargement et fonction `loadMore`.
 */
export function useInfiniteSitePosts(tab: TeachingsTab, searchQuery = '') {
  const [items, setItems] = useState<Sermon[]>([]);
  const [meta, setMeta] = useState<PostsPageMeta | null>(null);
  const [loading, setLoading] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const pageRef = useRef(1);

  const resetAndLoad = useCallback(async () => {
    pageRef.current = 1;
    setLoading(true);
    setError(null);

    try {
      const searchOpt = searchQuery.trim() !== '' ? { search: searchQuery.trim() } : undefined;
      const response = await fetchSitePostsPage(tab, 1, PER_PAGE, searchOpt);
      setItems(response.data ?? []);
      setMeta(response.meta ?? null);
    } catch (err) {
      setItems([]);
      setMeta(null);
      setError(err instanceof Error ? err.message : 'Erreur réseau');
    } finally {
      setLoading(false);
    }
  }, [tab, searchQuery]);

  useEffect(() => {
    void resetAndLoad();
  }, [resetAndLoad]);

  const loadMore = useCallback(async () => {
    if (loading || loadingMore || !meta?.has_more) {
      return;
    }

    const nextPage = pageRef.current + 1;
    setLoadingMore(true);

    try {
      const searchOpt = searchQuery.trim() !== '' ? { search: searchQuery.trim() } : undefined;
      const response = await fetchSitePostsPage(tab, nextPage, PER_PAGE, searchOpt);
      pageRef.current = nextPage;
      setItems((prev) => {
        const ids = new Set(prev.map((item) => item.id));
        const merged = [...prev];

        for (const row of response.data ?? []) {
          if (!ids.has(row.id)) {
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
  }, [loading, loadingMore, meta?.has_more, tab, searchQuery]);

  const hasMore = meta?.has_more ?? false;

  return {
    items,
    loading,
    loadingMore,
    error,
    hasMore,
    loadMore,
    total: meta?.total ?? 0,
  };
}
