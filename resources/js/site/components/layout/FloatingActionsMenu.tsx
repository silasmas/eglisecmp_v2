import { useEffect, useRef, useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { Plus, X, Heart, HandHeart, Calendar, Sparkles, MessageCircleHeart, Radio } from 'lucide-react';
import { Link, useLocation } from 'react-router-dom';
import { cn } from '../../lib/utils';
import { useFeaturedEvent } from '../../context/FeaturedEventContext';
import { useYoutubeLive } from '../../context/YoutubeLiveContext';

const FAB_HINT_DELAY_MS = 10_000;
const FAB_HINT_SESSION_PREFIX = 'cmp-fab-hint-seen:';

/** Actions flottantes présentes sur tout le site public (offrande, prière, rendez-vous, événement à la une). */
export default function FloatingActionsMenu() {
  const location = useLocation();
  const [open, setOpen] = useState(false);
  const [hintVisible, setHintVisible] = useState(false);
  const [attentionPulse, setAttentionPulse] = useState(false);
  const rootRef = useRef<HTMLDivElement | null>(null);
  const { spotlightEvent, showSpotlightFab, pulseMainFab, openSpotlightModal } = useFeaturedEvent();
  const { isYoutubeLive, live: youtubeLive, pulseFab: pulseYoutubeFab, openLiveModal } = useYoutubeLive();

  useEffect(() => {
    if (!open) {
      return;
    }

    const onDoc = (event: MouseEvent) => {
      if (rootRef.current !== null && !rootRef.current.contains(event.target as Node)) {
        setOpen(false);
      }
    };

    document.addEventListener('mousedown', onDoc);
    return () => document.removeEventListener('mousedown', onDoc);
  }, [open]);

  useEffect(() => {
    setHintVisible(false);
    setAttentionPulse(false);

    const sessionKey = `${FAB_HINT_SESSION_PREFIX}${location.pathname}`;
    if (sessionStorage.getItem(sessionKey) === '1') {
      return undefined;
    }

    const timer = window.setTimeout(() => {
      sessionStorage.setItem(sessionKey, '1');
      setAttentionPulse(true);
      setHintVisible(true);
    }, FAB_HINT_DELAY_MS);

    return () => window.clearTimeout(timer);
  }, [location.pathname]);

  useEffect(() => {
    if (open) {
      setHintVisible(false);
    }
  }, [open]);

  const items = [
    { to: '/offrandes', label: 'Offrande', Icon: Heart, className: 'bg-emerald-600 hover:bg-emerald-500' },
    { to: '/temoignages', label: 'Mur de témoignages', Icon: MessageCircleHeart, className: 'bg-amber-600 hover:bg-amber-500' },
    { to: '/requete-de-priere', label: 'Requête de prière', Icon: HandHeart, className: 'bg-burgundy-700 hover:bg-burgundy-600' },
    { to: '/rendez-vous', label: 'Prendre rendez-vous', Icon: Calendar, className: 'bg-surface-900 hover:bg-surface-800 dark:bg-white dark:text-surface-900 dark:hover:bg-surface-100' },
  ];

  const mainFabBlink = pulseMainFab || attentionPulse || (pulseYoutubeFab && !open);

  return (
    <div ref={rootRef} className="pointer-events-none fixed bottom-6 right-4 z-[120] flex flex-col items-end gap-3 sm:right-8">
      <AnimatePresence>
        {hintVisible && !open ? (
          <motion.div
            initial={{ opacity: 0, x: 16, scale: 0.95 }}
            animate={{ opacity: 1, x: 0, scale: 1 }}
            exit={{ opacity: 0, x: 12, scale: 0.95 }}
            transition={{ duration: 0.25 }}
            className="pointer-events-auto relative mr-1 max-w-[220px] rounded-2xl border border-burgundy-200 bg-white px-4 py-3 text-sm font-medium text-surface-800 shadow-xl dark:border-surface-600 dark:bg-surface-900 dark:text-white"
          >
            <span
              className="absolute -right-2 bottom-5 h-4 w-4 rotate-45 border-b border-r border-burgundy-200 bg-white dark:border-surface-600 dark:bg-surface-900"
              aria-hidden
            />
            Besoin d&apos;autre chose ? Prière, offrande, mur de témoignages…
            <button
              type="button"
              className="mt-2 block text-xs font-semibold text-burgundy-700 underline dark:text-burgundy-400"
              onClick={() => {
                setOpen(true);
                setHintVisible(false);
              }}
            >
              Voir les options
            </button>
          </motion.div>
        ) : null}
      </AnimatePresence>

      {isYoutubeLive && youtubeLive !== null && !open ? (
        <motion.button
          type="button"
          initial={{ opacity: 0, y: 8 }}
          animate={{ opacity: 1, y: 0 }}
          onClick={openLiveModal}
          className="fab-blink pointer-events-auto flex items-center gap-2 rounded-full bg-red-600 px-4 py-2 text-xs font-bold uppercase tracking-wide text-white shadow-lg ring-4 ring-red-400/40"
        >
          <Radio className="h-4 w-4" aria-hidden />
          Live YouTube
        </motion.button>
      ) : null}

      <AnimatePresence>
        {open ? (
          <motion.div
            initial={{ opacity: 0, y: 16 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: 16 }}
            transition={{ duration: 0.2 }}
            className="pointer-events-auto flex flex-col gap-2.5"
          >
            {showSpotlightFab && spotlightEvent !== null ? (
              <motion.div
                initial={{ opacity: 0, x: 28 }}
                animate={{ opacity: 1, x: 0 }}
                exit={{ opacity: 0, x: 28 }}
                transition={{ duration: 0.2 }}
              >
                <button
                  type="button"
                  onClick={() => {
                    openSpotlightModal();
                    setOpen(false);
                  }}
                  className={cn(
                    'fab-blink flex items-center gap-3 rounded-full bg-gold-500 px-4 py-2.5 text-sm font-semibold text-surface-950 shadow-lg transition hover:bg-gold-400',
                  )}
                >
                  <Sparkles className="h-5 w-5 shrink-0" aria-hidden />
                  {spotlightEvent.title}
                </button>
              </motion.div>
            ) : null}

            {items.map(({ to, label, Icon, className }, index) => (
              <motion.div
                key={to}
                initial={{ opacity: 0, x: 28 }}
                animate={{ opacity: 1, x: 0 }}
                exit={{ opacity: 0, x: 28 }}
                transition={{ duration: 0.2, delay: (showSpotlightFab ? index + 1 : index) * 0.04 }}
              >
                <Link
                  to={to}
                  className={cn(
                    'flex items-center gap-3 rounded-full px-4 py-2.5 text-sm font-semibold text-white shadow-lg transition',
                    className,
                  )}
                  onClick={() => setOpen(false)}
                >
                  <Icon className="h-5 w-5 shrink-0 opacity-95" aria-hidden />
                  {label}
                </Link>
              </motion.div>
            ))}
          </motion.div>
        ) : null}
      </AnimatePresence>

      {showSpotlightFab && spotlightEvent !== null && !open ? (
        <motion.button
          type="button"
          initial={{ opacity: 0, scale: 0.8 }}
          animate={{ opacity: 1, scale: 1 }}
          whileTap={{ scale: 0.94 }}
          onClick={openSpotlightModal}
          aria-label={`Voir l'événement : ${spotlightEvent.title}`}
          className="fab-blink pointer-events-auto flex h-12 w-12 items-center justify-center rounded-full bg-gold-500 text-surface-950 shadow-xl ring-4 ring-gold-300/35"
        >
          <Sparkles className="h-5 w-5" aria-hidden />
        </motion.button>
      ) : null}

      <motion.button
        type="button"
        whileTap={{ scale: 0.94 }}
        onClick={() => {
          setOpen((previous) => !previous);
          setAttentionPulse(false);
          setHintVisible(false);
        }}
        aria-expanded={open}
        aria-label={open ? 'Fermer le menu rapide' : 'Ouvrir le menu rapide'}
        className={cn(
          'pointer-events-auto flex h-14 w-14 items-center justify-center rounded-full bg-burgundy-800 text-white shadow-2xl shadow-burgundy-900/40 ring-4 ring-white/25 transition hover:bg-burgundy-700 dark:ring-surface-950/40',
          mainFabBlink && 'fab-blink-main',
        )}
      >
        {open ? <X className="h-7 w-7" strokeWidth={2.25} /> : <Plus className="h-7 w-7" strokeWidth={2.25} />}
      </motion.button>
    </div>
  );
}
