import { useEffect, useState } from 'react';
import type { HeroMeta } from '../data/types';
import { fetchSiteData } from '../lib/siteApi';

const emptyMeta: HeroMeta = {
  verse: null,
  liveSlots: [],
  liveTiming: null,
  stripCards: undefined,
  reactionKeys: {},
};

/**
 * Charge le verset, les créneaux live, le timing du bandeau et les cartes modales du hero (`hero-meta`).
 *
 * @returns Objet `meta`, indicateur `loading` et `error` éventuel.
 */
export function useHeroMeta() {
  const [meta, setMeta] = useState<HeroMeta>(emptyMeta);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let cancelled = false;

    async function load() {
      try {
        setLoading(true);
        const data = await fetchSiteData<HeroMeta>('hero-meta');
        if (cancelled) {
          return;
        }
        setMeta({
          verse: data?.verse ?? null,
          liveSlots: Array.isArray(data?.liveSlots) ? data.liveSlots : [],
          liveTiming: data?.liveTiming ?? null,
          stripCards: data?.stripCards,
          reactionKeys: data?.reactionKeys ?? {},
        });
        setError(null);
      } catch (err) {
        if (!cancelled) {
          setMeta(emptyMeta);
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
  }, []);

  return { meta, loading, error };
}
