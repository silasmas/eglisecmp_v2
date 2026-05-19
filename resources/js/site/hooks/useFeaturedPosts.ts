import { useEffect, useState } from 'react';
import type { FeaturedPostCard } from '../data/types';
import { fetchSiteList } from '../lib/siteApi';

/**
 * Charge les posts mis en avant sur l'accueil (programmation Filament).
 *
 * @param limit Nombre maximum de cartes (1–24).
 * @returns Objet `posts`, indicateur `loading` et `error` éventuel.
 */
export function useFeaturedPosts(limit = 6) {
  const [posts, setPosts] = useState<FeaturedPostCard[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let cancelled = false;

    async function load() {
      try {
        setLoading(true);
        const rows = await fetchSiteList<FeaturedPostCard>(
          `featured-posts?limit=${encodeURIComponent(String(limit))}`,
        );
        if (cancelled) {
          return;
        }
        setPosts(rows);
        setError(null);
      } catch (err) {
        if (!cancelled) {
          setPosts([]);
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
  }, [limit]);

  return { posts, loading, error };
}
