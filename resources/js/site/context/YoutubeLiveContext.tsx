import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from 'react';
import { useLocation } from 'react-router-dom';
import { fetchSiteData } from '../lib/siteApi';
import type { YoutubeLivePayload } from '../data/types';

const DISMISS_KEY = 'cmp-youtube-live-popup-dismissed';
const POLL_MS = 90_000;

interface YoutubeLiveContextValue {
  live: YoutubeLivePayload | null;
  isYoutubeLive: boolean;
  modalOpen: boolean;
  pulseFab: boolean;
  openLiveModal: () => void;
  dismissLiveModal: () => void;
}

const YoutubeLiveContext = createContext<YoutubeLiveContextValue | null>(null);

/**
 * Surveille le live YouTube de la chaîne et pilote popup + clignotement du menu flottant.
 */
export function YoutubeLiveProvider({ children }: { children: ReactNode }) {
  const location = useLocation();
  const isHome = location.pathname === '/';
  const [live, setLive] = useState<YoutubeLivePayload | null>(null);
  const [modalOpen, setModalOpen] = useState(false);

  const load = useCallback(async () => {
    try {
      const data = await fetchSiteData<YoutubeLivePayload | null>('youtube/live');
      setLive(data);
    } catch {
      setLive(null);
    }
  }, []);

  useEffect(() => {
    void load();
    const interval = window.setInterval(() => {
      void load();
    }, POLL_MS);
    return () => window.clearInterval(interval);
  }, [load]);

  useEffect(() => {
    if (live === null) {
      setModalOpen(false);
      return;
    }

    const dismissed = sessionStorage.getItem(`${DISMISS_KEY}:${live.videoId}`) === '1';
    if (dismissed) {
      setModalOpen(false);
      return;
    }

    if (isHome) {
      setModalOpen(true);
    }
  }, [live, isHome]);

  const dismissLiveModal = useCallback(() => {
    if (live !== null) {
      sessionStorage.setItem(`${DISMISS_KEY}:${live.videoId}`, '1');
    }
    setModalOpen(false);
  }, [live]);

  const openLiveModal = useCallback(() => {
    if (live !== null) {
      setModalOpen(true);
    }
  }, [live]);

  const value = useMemo<YoutubeLiveContextValue>(
    () => ({
      live,
      isYoutubeLive: live !== null,
      modalOpen,
      pulseFab: live !== null,
      openLiveModal,
      dismissLiveModal,
    }),
    [dismissLiveModal, live, modalOpen, openLiveModal],
  );

  return <YoutubeLiveContext.Provider value={value}>{children}</YoutubeLiveContext.Provider>;
}

/**
 * Accès au statut live YouTube.
 */
export function useYoutubeLive(): YoutubeLiveContextValue {
  const ctx = useContext(YoutubeLiveContext);
  if (ctx === null) {
    throw new Error('useYoutubeLive doit être utilisé dans YoutubeLiveProvider.');
  }
  return ctx;
}
