import { useEffect } from 'react';
import { AnimatePresence, motion } from 'framer-motion';
import { ExternalLink, MapPin } from 'lucide-react';
import type { HeroStripCard } from '../../data/types';
import DailyReadingShare from './DailyReadingShare';
import ReactionBar from './ReactionBar';
import ImageWithSkeleton from './ImageWithSkeleton';

/**
 * Modale plein écran légère : bannière, texte et réactions optionnelles pour une tuile du hero.
 */
export default function HeroStripModal({
  open,
  onClose,
  card,
  showReadingShare = false,
  onOpenMap,
  showLivePlayer = false,
}: {
  open: boolean;
  onClose: () => void;
  card: HeroStripCard | null;
  showReadingShare?: boolean;
  /** Ouvre Google Maps (tuile localisation). */
  onOpenMap?: () => void;
  /** Affiche le lecteur YouTube / Facebook pour un live en cours. */
  showLivePlayer?: boolean;
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

  const bannerVisualSrc = card !== null && card.bannerImage.trim() !== '' ? card.bannerImage : '';
  const mapUrl = card?.mapUrl?.trim() ?? '';
  const embedUrl = card?.embedUrl?.trim() ?? '';
  const linkUrl = card?.linkUrl?.trim() ?? '';
  const hasEmbed = showLivePlayer && embedUrl !== '';

  return (
    <AnimatePresence>
      {open && card !== null ? (
        <motion.div
          key="hero-strip-backdrop"
          className="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6"
          role="dialog"
          aria-modal="true"
          aria-labelledby="hero-strip-modal-title"
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          exit={{ opacity: 0 }}
          transition={{ duration: 0.22 }}
          onClick={onClose}
          onKeyDown={(event) => {
            if (event.key === 'Escape') {
              onClose();
            }
          }}
        >
          <div className="absolute inset-0 bg-surface-950/75 backdrop-blur-sm" aria-hidden />

          <motion.div
            key="hero-strip-panel"
            className="relative flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-3xl bg-white shadow-2xl ring-1 ring-black/5"
            initial={{ opacity: 0, scale: 0.92, y: 24 }}
            animate={{ opacity: 1, scale: 1, y: 0 }}
            exit={{ opacity: 0, scale: 0.94, y: 12 }}
            transition={{ duration: 0.3, ease: [0.22, 1, 0.36, 1] }}
            onClick={(event) => event.stopPropagation()}
          >
            {hasEmbed ? (
              <div className="relative shrink-0 w-full bg-black">
                <div className="relative aspect-video w-full">
                  <iframe
                    src={embedUrl}
                    title={card.title}
                    className="absolute inset-0 h-full w-full"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    allowFullScreen
                  />
                </div>
                {card.status === 'live' ? (
                  <span className="badge-blink absolute left-4 top-4 z-10 inline-flex items-center gap-2 rounded-full border border-red-300/40 bg-red-700/90 px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-white">
                    Live en cours
                  </span>
                ) : null}
              </div>
            ) : bannerVisualSrc !== '' ? (
              <div className="relative shrink-0 aspect-[21/9] w-full bg-surface-100">
                <ImageWithSkeleton src={bannerVisualSrc} alt="" className="h-full w-full object-cover" />
                {card.status === 'live' ? (
                  <span className="badge-blink absolute left-4 top-4 z-10 inline-flex items-center gap-2 rounded-full border border-red-300/40 bg-red-700/90 px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-white">
                    En cours
                  </span>
                ) : null}
              </div>
            ) : null}

            <div className="min-h-0 flex-1 overflow-y-auto overscroll-contain p-6 sm:p-8">
              <h2 id="hero-strip-modal-title" className="font-heading text-xl font-bold text-surface-900 sm:text-2xl">
                {card.title}
              </h2>
              {showReadingShare && card.reference && card.reference.trim() !== '' ? (
                <p className="mt-1 text-sm font-medium text-burgundy-700">{card.reference}</p>
              ) : null}
              {!showReadingShare && card.subtitle.trim() !== '' ? (
                <p className="mt-1 text-sm text-surface-500">{card.subtitle}</p>
              ) : null}

              {card.description.trim() !== '' ? (
                <p className="mt-4 whitespace-pre-line text-sm leading-relaxed text-surface-700">{card.description}</p>
              ) : null}

              {showLivePlayer && linkUrl !== '' && !hasEmbed ? (
                <a
                  href={linkUrl}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-red-700 px-4 py-3 text-sm font-semibold text-white transition hover:bg-red-600"
                >
                  <ExternalLink className="h-4 w-4" aria-hidden />
                  Rejoindre le live
                </a>
              ) : null}

              {mapUrl !== '' || onOpenMap ? (
                <button
                  type="button"
                  className="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-burgundy-800 px-4 py-3 text-sm font-semibold text-white transition hover:bg-burgundy-700"
                  onClick={() => {
                    if (onOpenMap) {
                      onOpenMap();
                      return;
                    }

                    if (mapUrl !== '') {
                      window.open(mapUrl, '_blank', 'noopener,noreferrer');
                    }
                  }}
                >
                  <MapPin className="h-4 w-4" aria-hidden />
                  Voir l&apos;église sur la carte
                  <ExternalLink className="h-4 w-4 opacity-80" aria-hidden />
                </button>
              ) : null}

              {showReadingShare ? (
                <DailyReadingShare
                  reference={card.reference ?? card.title}
                  text={card.description}
                  imageUrl={bannerVisualSrc !== '' ? card.bannerImage : undefined}
                  className="mt-5"
                />
              ) : null}
              <ReactionBar reactableKey={card.reactableKey || undefined} className="mt-5" />
              <button
                type="button"
                className="mt-6 w-full rounded-2xl border border-surface-200 bg-surface-50 py-3 text-sm font-semibold text-surface-800 transition hover:bg-surface-100"
                onClick={onClose}
              >
                Fermer
              </button>
            </div>
          </motion.div>
        </motion.div>
      ) : null}
    </AnimatePresence>
  );
}
