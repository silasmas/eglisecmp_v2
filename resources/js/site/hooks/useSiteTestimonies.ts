import { useCallback, useEffect, useRef, useState } from 'react';
import type { TestimonyWallSettings, WallConfig, WallTestimony } from '../data/types';
import { fetchWallConfig, fetchWallTestimoniesPage } from '../lib/siteApi';

const POLL_MS = 45_000;

/**
 * Fusionne la page 1 avec la liste existante : ajoute uniquement les nouveaux témoignages en tête.
 *
 * @param prev Liste affichée (peut inclure des pages suivantes).
 * @param page1 Première page fraîchement chargée.
 * @returns Nouvelle liste ou la même référence si rien n’a changé.
 */
function mergeNewTestimonies(prev: WallTestimony[], page1: WallTestimony[]): WallTestimony[] {
  const existingIds = new Set(prev.map((item) => item.id));
  const newcomers = page1.filter((item) => !existingIds.has(item.id));

  if (newcomers.length === 0) {
    return prev;
  }

  const newcomerIds = new Set(newcomers.map((item) => item.id));
  const rest = prev.filter((item) => !newcomerIds.has(item.id));

  return [...newcomers, ...rest];
}

/**
 * Charge et pagine les témoignages approuvés du mur public.
 *
 * @param category Filtre catégorie (ou « Tous »).
 * @returns État de chargement, liste, pagination et configuration mur.
 */
export function useSiteTestimonies(category: string) {
  const [items, setItems] = useState<WallTestimony[]>([]);
  const [wall, setWall] = useState<WallConfig | null>(null);
  const [wallSettings, setWallSettings] = useState<TestimonyWallSettings | null>(null);
  const [reactionKeys, setReactionKeys] = useState<Record<string, string>>({});
  const [page, setPage] = useState(1);
  const [hasMore, setHasMore] = useState(false);
  const [loading, setLoading] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const categoryRef = useRef(category);

  const applyMeta = useCallback((meta: { wall?: WallConfig; wallSettings?: TestimonyWallSettings; reactionKeys?: Record<string, string> }) => {
    if (meta.wall !== undefined) {
      setWall(meta.wall);
    }
    if (meta.wallSettings !== undefined) {
      setWallSettings(meta.wallSettings);
    }
    if (meta.reactionKeys !== undefined) {
      setReactionKeys(meta.reactionKeys);
    }
  }, []);

  const loadPage = useCallback(
    async (targetPage: number, append: boolean, options?: { silent?: boolean }) => {
      const silent = options?.silent === true;

      if (!silent) {
        if (targetPage === 1) {
          setLoading(true);
        } else {
          setLoadingMore(true);
        }
      }
      setError(null);

      try {
        const res = await fetchWallTestimoniesPage(targetPage, { category });
        applyMeta({
          wall: res.meta.wall,
          wallSettings: res.meta.wallSettings,
          reactionKeys: res.meta.reactionKeys,
        });

        if (silent && targetPage === 1 && !append) {
          setItems((prev) => mergeNewTestimonies(prev, res.data));
        } else {
          setItems((prev) => (append ? [...prev, ...res.data] : res.data));
        }

        setHasMore(res.meta.has_more);
        setPage(targetPage);
      } catch (err) {
        if (!silent) {
          setError(err instanceof Error ? err.message : 'Chargement impossible.');
        }
      } finally {
        if (!silent) {
          setLoading(false);
          setLoadingMore(false);
        }
      }
    },
    [applyMeta, category],
  );

  useEffect(() => {
    categoryRef.current = category;
    setPage(1);
    void loadPage(1, false);
  }, [loadPage]);

  useEffect(() => {
    const interval = window.setInterval(() => {
      if (categoryRef.current !== category) {
        return;
      }
      void loadPage(1, false, { silent: true });
    }, POLL_MS);

    return () => window.clearInterval(interval);
  }, [category, loadPage]);

  useEffect(() => {
    if (wall !== null && wallSettings !== null) {
      return;
    }
    void fetchWallConfig()
      .then((cfg) => {
        setWall(cfg.wall);
        setWallSettings(cfg.wallSettings);
        setReactionKeys(cfg.reactionKeys);
      })
      .catch(() => {
        /* la config peut aussi arriver via meta de la première page */
      });
  }, [wall, wallSettings]);

  const loadMore = () => {
    if (!hasMore || loadingMore) {
      return;
    }
    void loadPage(page + 1, true);
  };

  const refresh = () => {
    void loadPage(1, false);
  };

  return {
    items,
    wall,
    wallSettings,
    allowPhotoUpload: wallSettings?.allowPhotoUpload ?? true,
    reactionKeys,
    loading,
    loadingMore,
    hasMore,
    error,
    loadMore,
    refresh,
  };
}
