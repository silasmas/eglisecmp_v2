import { useCallback, useEffect, useRef, useState } from 'react';
import type { HeroMeta } from '../data/types';
import { fetchSiteData } from '../lib/siteApi';

const emptyMeta: HeroMeta = {
  verse: null,
  liveSlots: [],
  liveTiming: null,
  stripCards: undefined,
  reactionKeys: {},
};

const POLL_MS = 60_000;

/**
 * Charge le verset, les créneaux live, le timing du bandeau et les cartes modales du hero (`hero-meta`).
 * Rafraîchit périodiquement pour synchroniser le statut live avec YouTube.
 *
 * @returns Objet `meta`, indicateur `loading` et `error` éventuel.
 */
export function useHeroMeta() {
  const [meta, setMeta] = useState<HeroMeta>(emptyMeta);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const mountedRef = useRef(true);

  const load = useCallback(async (silent = false) => {
    try {
      if (!silent) {
        setLoading(true);
      }
      const data = await fetchSiteData<HeroMeta>('hero-meta');
      if (!mountedRef.current) {
        return;
      }
      setMeta({
        verse: data?.verse ?? null,
        liveSlots: Array.isArray(data?.liveSlots) ? data.liveSlots : [],
        liveTiming: data?.liveTiming ?? null,
        stripCards: data?.stripCards,
        youtubeLive: data?.youtubeLive ?? null,
        reactionKeys: data?.reactionKeys ?? {},
      });
      setError(null);
    } catch (err) {
      if (!mountedRef.current) {
        return;
      }
      if (!silent) {
        setMeta(emptyMeta);
      }
      setError(err instanceof Error ? err.message : 'Erreur réseau');
    } finally {
      if (mountedRef.current && !silent) {
        setLoading(false);
      }
    }
  }, []);

  useEffect(() => {
    mountedRef.current = true;
    void load(false);

    const interval = window.setInterval(() => {
      void load(true);
    }, POLL_MS);

    return () => {
      mountedRef.current = false;
      window.clearInterval(interval);
    };
  }, [load]);

  return { meta, loading, error, reload: load };
}
