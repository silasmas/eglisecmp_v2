import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from 'react';
import { useLocation } from 'react-router-dom';
import type { Event } from '../data/types';
import { fetchSiteData } from '../lib/siteApi';
import EventDetailModal from '../components/ui/EventDetailModal';

const DISMISS_STORAGE_PREFIX = 'cmp-event-spotlight-dismissed:';

interface FeaturedEventContextValue {
  /** Événement programmé en mise en avant, ou null. */
  spotlightEvent: Event | null;
  /** Modale d'accueil ouverte. */
  spotlightModalOpen: boolean;
  /** Bouton flottant événement visible (modale fermée). */
  showSpotlightFab: boolean;
  /** Le bouton principal du menu flottant doit clignoter. */
  pulseMainFab: boolean;
  /** Rouvre la modale depuis le bouton flottant. */
  openSpotlightModal: () => void;
  /** Ferme la modale et épingle l'événement dans le menu flottant. */
  dismissSpotlightModal: () => void;
}

const FeaturedEventContext = createContext<FeaturedEventContextValue | null>(null);

/**
 * Fournit l'événement mis en avant (modale accueil + bouton flottant clignotant).
 */
export function FeaturedEventProvider({ children }: { children: ReactNode }) {
  const location = useLocation();
  const isHome = location.pathname === '/';
  const [spotlightEvent, setSpotlightEvent] = useState<Event | null>(null);
  const [spotlightModalOpen, setSpotlightModalOpen] = useState(false);
  const [showSpotlightFab, setShowSpotlightFab] = useState(false);

  useEffect(() => {
    let cancelled = false;

    async function loadSpotlight() {
      try {
        const event = await fetchSiteData<Event | null>('events/spotlight');
        if (cancelled) {
          return;
        }

        if (event === null) {
          setSpotlightEvent(null);
          setSpotlightModalOpen(false);
          setShowSpotlightFab(false);
          return;
        }

        setSpotlightEvent(event);
      } catch {
        if (!cancelled) {
          setSpotlightEvent(null);
          setSpotlightModalOpen(false);
          setShowSpotlightFab(false);
        }
      }
    }

    void loadSpotlight();

    return () => {
      cancelled = true;
    };
  }, []);

  useEffect(() => {
    if (spotlightEvent === null) {
      return;
    }

    const dismissed = localStorage.getItem(`${DISMISS_STORAGE_PREFIX}${spotlightEvent.id}`) === '1';

    if (dismissed) {
      setShowSpotlightFab(true);
      setSpotlightModalOpen(false);
      return;
    }

    if (isHome) {
      setSpotlightModalOpen(true);
      setShowSpotlightFab(false);
    }
  }, [spotlightEvent, isHome]);

  const dismissSpotlightModal = useCallback(() => {
    if (spotlightEvent !== null) {
      localStorage.setItem(`${DISMISS_STORAGE_PREFIX}${spotlightEvent.id}`, '1');
    }

    setSpotlightModalOpen(false);
    setShowSpotlightFab(true);
  }, [spotlightEvent]);

  const openSpotlightModal = useCallback(() => {
    if (spotlightEvent === null) {
      return;
    }

    setSpotlightModalOpen(true);
  }, [spotlightEvent]);

  const value = useMemo<FeaturedEventContextValue>(
    () => ({
      spotlightEvent,
      spotlightModalOpen,
      showSpotlightFab,
      pulseMainFab: showSpotlightFab && spotlightEvent !== null,
      openSpotlightModal,
      dismissSpotlightModal,
    }),
    [dismissSpotlightModal, openSpotlightModal, showSpotlightFab, spotlightEvent, spotlightModalOpen],
  );

  return (
    <FeaturedEventContext.Provider value={value}>
      {children}
      <EventDetailModal
        open={spotlightModalOpen}
        onClose={dismissSpotlightModal}
        event={spotlightEvent}
        variant="spotlight"
      />
    </FeaturedEventContext.Provider>
  );
}

/**
 * Accès au contexte de l'événement mis en avant.
 */
export function useFeaturedEvent(): FeaturedEventContextValue {
  const context = useContext(FeaturedEventContext);

  if (context === null) {
    throw new Error('useFeaturedEvent doit être utilisé dans FeaturedEventProvider.');
  }

  return context;
}
