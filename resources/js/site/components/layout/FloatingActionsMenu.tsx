import { useEffect, useRef, useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { Plus, X, Heart, HandHeart, Calendar } from 'lucide-react';
import { Link } from 'react-router-dom';
import { cn } from '../../lib/utils';

/** Actions flottantes présentes sur tout le site public (offrande, prière, rendez-vous). */
export default function FloatingActionsMenu() {
  const [open, setOpen] = useState(false);
  const rootRef = useRef<HTMLDivElement | null>(null);

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
            {items.map(({ to, label, Icon, className }, index) => (
              <motion.div
                key={to}
                initial={{ opacity: 0, x: 28 }}
                animate={{ opacity: 1, x: 0 }}
                exit={{ opacity: 0, x: 28 }}
                transition={{ duration: 0.2, delay: index * 0.04 }}
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

      <motion.button
        type="button"
        whileTap={{ scale: 0.94 }}
        onClick={() => setOpen((previous) => !previous)}
        aria-expanded={open}
        aria-label={open ? 'Fermer le menu rapide' : 'Ouvrir le menu rapide'}
        className="pointer-events-auto flex h-14 w-14 items-center justify-center rounded-full bg-burgundy-800 text-white shadow-2xl shadow-burgundy-900/40 ring-4 ring-white/25 transition hover:bg-burgundy-700 dark:ring-surface-950/40"
      >
        {open ? <X className="h-7 w-7" strokeWidth={2.25} /> : <Plus className="h-7 w-7" strokeWidth={2.25} />}
      </motion.button>
    </div>
  );
}
