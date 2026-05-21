import { useEffect, useRef, useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { Plus, X, Heart, HandHeart, Calendar, Sparkles } from 'lucide-react';
import { Link } from 'react-router-dom';
import { cn } from '../../lib/utils';
import { useFeaturedEvent } from '../../context/FeaturedEventContext';

/** Actions flottantes présentes sur tout le site public (offrande, prière, rendez-vous, événement à la une). */
export default function FloatingActionsMenu() {
  const [open, setOpen] = useState(false);
  const rootRef = useRef<HTMLDivElement | null>(null);
  const { spotlightEvent, showSpotlightFab, pulseMainFab, openSpotlightModal } = useFeaturedEvent();

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

  const items = [
    { to: '/offrandes', label: 'Offrande', Icon: Heart, className: 'bg-emerald-600 hover:bg-emerald-500' },
    { to: '/requete-de-priere', label: 'Requête de prière', Icon: HandHeart, className: 'bg-burgundy-700 hover:bg-burgundy-600' },
    { to: '/rendez-vous', label: 'Prendre rendez-vous', Icon: Calendar, className: 'bg-surface-900 hover:bg-surface-800 dark:bg-white dark:text-surface-900 dark:hover:bg-surface-100' },
  ];

  return (
    <div ref={rootRef} className="pointer-events-none fixed bottom-6 right-4 z-[120] flex flex-col items-end gap-3 sm:right-8">
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
        onClick={() => setOpen((previous) => !previous)}
        aria-expanded={open}
        aria-label={open ? 'Fermer le menu rapide' : 'Ouvrir le menu rapide'}
        className={cn(
          'pointer-events-auto flex h-14 w-14 items-center justify-center rounded-full bg-burgundy-800 text-white shadow-2xl shadow-burgundy-900/40 ring-4 ring-white/25 transition hover:bg-burgundy-700 dark:ring-surface-950/40',
          pulseMainFab && 'fab-blink-main',
        )}
      >
        {open ? <X className="h-7 w-7" strokeWidth={2.25} /> : <Plus className="h-7 w-7" strokeWidth={2.25} />}
      </motion.button>
    </div>
  );
}
