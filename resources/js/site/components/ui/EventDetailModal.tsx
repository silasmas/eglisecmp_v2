import { useEffect } from 'react';
import { AnimatePresence, motion } from 'framer-motion';
import { Calendar, Clock, ExternalLink, MapPin, Sparkles, X } from 'lucide-react';
import type { Event } from '../../data/types';
import ImageWithSkeleton from './ImageWithSkeleton';
import ReactionBar from './ReactionBar';
import SocialShareToolbar from './SocialShareToolbar';

/**
 * Formate la date d'un événement pour l'affichage français.
 */
function formatEventDate(dateStr: string): string {
  if (dateStr.trim() === '') {
    return '';
  }

  return new Date(dateStr).toLocaleDateString('fr-FR', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  });
}

/**
 * Modale de détail d'un événement (liste ou mise en avant programmée).
 */
export default function EventDetailModal({
  open,
  onClose,
  event,
  variant = 'default',
}: {
  open: boolean;
  onClose: () => void;
  event: Event | null;
  variant?: 'default' | 'spotlight';
}) {
  useEffect(() => {
    if (!open) {
      return undefined;
    }

    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';

    return () => {
      document.body.style.overflow = previousOverflow;
    };
  }, [open]);

  const hasPoster = event?.hasPoster === true;
  const formattedDate = event !== null ? formatEventDate(event.date) : '';
  const isSpotlight = variant === 'spotlight';
  const showFeaturedBadge = event !== null && (event.featured === true || isSpotlight);

  return (
    <AnimatePresence>
      {open && event !== null ? (
        <motion.div
          key="event-detail-backdrop"
          className="fixed inset-0 z-[130] flex items-center justify-center p-4 sm:p-6"
          role="dialog"
          aria-modal="true"
          aria-labelledby="event-detail-modal-title"
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          exit={{ opacity: 0 }}
          transition={{ duration: 0.22 }}
          onClick={onClose}
          onKeyDown={(eventKey) => {
            if (eventKey.key === 'Escape') {
              onClose();
            }
          }}
        >
          <div className="absolute inset-0 bg-surface-950/80 backdrop-blur-sm" aria-hidden />

          <motion.div
            key="event-detail-panel"
            className={
              isSpotlight
                ? 'relative flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-[2rem] bg-white shadow-2xl ring-1 ring-gold-300/40'
                : 'relative flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-3xl bg-white shadow-2xl ring-1 ring-black/5'
            }
            initial={{ opacity: 0, scale: 0.9, y: 28 }}
            animate={{ opacity: 1, scale: 1, y: 0 }}
            exit={{ opacity: 0, scale: 0.94, y: 16 }}
            transition={{ duration: 0.32, ease: [0.22, 1, 0.36, 1] }}
            onClick={(clickEvent) => clickEvent.stopPropagation()}
          >
            {hasPoster ? (
              <div className="relative shrink-0 aspect-[21/10] w-full bg-surface-100">
                <ImageWithSkeleton src={event.image} alt={event.title} className="h-full w-full object-cover" />

                {showFeaturedBadge ? (
                  <div className="badge-blink absolute left-4 top-4 z-20 inline-flex items-center gap-2 rounded-full border border-gold-300/40 bg-burgundy-800/95 px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[0.16em] text-gold-100 shadow-lg backdrop-blur-md">
                    <Sparkles className="h-3.5 w-3.5" aria-hidden />
                    Événement à la une
                  </div>
                ) : null}

                <button
                  type="button"
                  className="absolute right-4 top-4 z-20 inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/20 bg-black/35 text-white backdrop-blur-md transition hover:bg-black/50"
                  aria-label="Fermer"
                  onClick={onClose}
                >
                  <X className="h-5 w-5" />
                </button>
              </div>
            ) : (
              <div className="relative shrink-0 border-b border-surface-100 px-6 pb-4 pt-6 sm:px-8">
                {showFeaturedBadge ? (
                  <div className="badge-blink mb-3 inline-flex items-center gap-2 rounded-full border border-gold-300/40 bg-burgundy-800 px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[0.16em] text-gold-100">
                    <Sparkles className="h-3.5 w-3.5" aria-hidden />
                    Événement à la une
                  </div>
                ) : null}
                <button
                  type="button"
                  className="absolute right-4 top-4 inline-flex h-10 w-10 items-center justify-center rounded-full border border-surface-200 bg-surface-50 text-surface-700 transition hover:bg-surface-100"
                  aria-label="Fermer"
                  onClick={onClose}
                >
                  <X className="h-5 w-5" />
                </button>
              </div>
            )}

            <div className="min-h-0 flex-1 overflow-y-auto overscroll-contain">
              <div className="p-6 sm:p-8">
                {event.theme && event.theme.trim() !== '' ? (
                  <p className="text-xs font-semibold uppercase tracking-[0.14em] text-burgundy-700">{event.theme}</p>
                ) : null}

                <h2
                  id="event-detail-modal-title"
                  className={
                    isSpotlight
                      ? 'mt-2 font-heading text-2xl font-extrabold leading-tight text-surface-900 sm:text-3xl'
                      : 'mt-1 font-heading text-xl font-bold text-surface-900 sm:text-2xl'
                  }
                >
                  {event.title}
                </h2>

                <div className="mt-4 flex flex-wrap gap-3 text-sm text-surface-600">
                  {formattedDate !== '' ? (
                    <span className="inline-flex items-center gap-1.5 rounded-full bg-surface-100 px-3 py-1.5">
                      <Calendar className="h-4 w-4 text-burgundy-700" aria-hidden />
                      {formattedDate}
                    </span>
                  ) : null}
                  {event.time.trim() !== '' ? (
                    <span className="inline-flex items-center gap-1.5 rounded-full bg-surface-100 px-3 py-1.5">
                      <Clock className="h-4 w-4 text-burgundy-700" aria-hidden />
                      {event.time}
                    </span>
                  ) : null}
                  <span className="inline-flex items-center gap-1.5 rounded-full bg-surface-100 px-3 py-1.5">
                    <MapPin className="h-4 w-4 text-burgundy-700" aria-hidden />
                    {event.location}
                  </span>
                </div>

                {event.description.trim() !== '' ? (
                  <p className="mt-5 whitespace-pre-line text-sm leading-relaxed text-surface-700">{event.description}</p>
                ) : (
                  <p className="mt-5 text-sm text-surface-500">Plus de détails seront communiqués prochainement.</p>
                )}

                <ReactionBar reactableKey={event.reactableKey} className="mt-5" />

                <div className="mt-5">
                  <p className="mb-2 text-xs font-semibold uppercase tracking-[0.12em] text-surface-500">Partager</p>
                  <SocialShareToolbar
                    title={event.title}
                    description={event.description}
                    sharePath="/events"
                    inline
                    animateOnClick
                  />
                </div>

                <button
                  type="button"
                  className="mt-6 w-full rounded-2xl bg-burgundy-800 py-3 text-sm font-semibold text-white transition hover:bg-burgundy-700"
                  onClick={onClose}
                >
                  {isSpotlight ? 'Continuer la visite' : 'Fermer'}
                </button>
              </div>
            </div>
          </motion.div>
        </motion.div>
      ) : null}
    </AnimatePresence>
  );
}
