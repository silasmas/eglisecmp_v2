import { useEffect } from 'react';
import { AnimatePresence, motion } from 'framer-motion';
import { CalendarClock, Clock3, ExternalLink, MapPin } from 'lucide-react';
import type { HeroStripCard } from '../../data/types';
import type { LiveCountdownInfo } from '../../lib/liveCountdown';
import DailyReadingShare from './DailyReadingShare';
import HeroStripBlinkBadge from './HeroStripBlinkBadge';
import ReactionBar from './ReactionBar';
import ImageWithSkeleton from './ImageWithSkeleton';

/**
 * Modale hero : image, vidéo et textes dans un seul conteneur scrollable.
 */
export default function HeroStripModal({
  open,
  onClose,
  card,
  showReadingShare = false,
  onOpenMap,
  showLivePlayer = false,
  liveCountdownInfo,
}: {
  open: boolean;
  onClose: () => void;
  card: HeroStripCard | null;
  showReadingShare?: boolean;
  /** Ouvre Google Maps (tuile localisation). */
  onOpenMap?: () => void;
  /** Affiche le lecteur YouTube / Facebook pour un live en cours. */
  showLivePlayer?: boolean;
  /** Décompte et infos du prochain live (tuile live). */
  liveCountdownInfo?: LiveCountdownInfo;
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
  const isLiveModal = liveCountdownInfo !== undefined;
  const isUpcomingLivePreview = isLiveModal && !showLivePlayer;
  const modalBadgeLabel = card?.modalBadge?.trim() ?? '';
  const modalBadgeTone = card?.modalBadgeTone;
  const showModalBadge = modalBadgeLabel !== '' && modalBadgeTone !== undefined;

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
            <div className="min-h-0 flex-1 overflow-y-auto overscroll-contain">
              {hasEmbed ? (
                <div className="relative w-full shrink-0 bg-black">
                  <div className="relative aspect-video w-full">
                    <iframe
                      src={embedUrl}
                      title={card.title}
                      className="absolute inset-0 h-full w-full"
                      allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                      allowFullScreen
                    />
                  </div>
                  {showModalBadge ? (
                    <HeroStripBlinkBadge label={modalBadgeLabel} tone={modalBadgeTone} />
                  ) : null}
                </div>
              ) : bannerVisualSrc !== '' ? (
                <div
                  className={`relative w-full shrink-0 bg-surface-100 ${
                    isUpcomingLivePreview ? 'aspect-[4/5] sm:aspect-[16/10]' : 'aspect-[21/9]'
                  }`}
                >
                  <ImageWithSkeleton src={bannerVisualSrc} alt="" className="h-full w-full object-cover" />
                  {showModalBadge ? (
                    <HeroStripBlinkBadge label={modalBadgeLabel} tone={modalBadgeTone} />
                  ) : null}
                  {isUpcomingLivePreview && liveCountdownInfo ? (
                    <div className="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/85 via-black/50 to-transparent px-4 pb-4 pt-20 sm:px-6 sm:pb-6">
                      <p className="text-[10px] font-semibold uppercase tracking-[0.16em] text-white/70">
                        Prochain live
                      </p>
                      <p className="mt-1 text-xl font-bold tabular-nums text-white sm:text-2xl">
                        {liveCountdownInfo.modalHeadline}
                      </p>
                      {liveCountdownInfo.modalScheduledAt !== '' ? (
                        <p className="mt-2 text-sm text-white/80">{liveCountdownInfo.modalScheduledAt}</p>
                      ) : null}
                    </div>
                  ) : null}
                </div>
              ) : isUpcomingLivePreview && liveCountdownInfo ? (
                <div className="relative shrink-0 border-b border-surface-100 bg-burgundy-900/95 px-6 py-5 text-white">
                  {showModalBadge ? (
                    <HeroStripBlinkBadge
                      label={modalBadgeLabel}
                      tone={modalBadgeTone}
                      className="relative mb-3 inline-flex"
                    />
                  ) : null}
                  <p className="text-[10px] font-semibold uppercase tracking-[0.16em] text-white/70">Prochain live</p>
                  <p className="mt-1 text-xl font-bold tabular-nums">{liveCountdownInfo.modalHeadline}</p>
                </div>
              ) : null}

              <div className="p-6 sm:p-8">
              {showModalBadge && bannerVisualSrc === '' && !hasEmbed && !isUpcomingLivePreview ? (
                <HeroStripBlinkBadge
                  label={modalBadgeLabel}
                  tone={modalBadgeTone}
                  className="relative mb-4 inline-flex"
                />
              ) : null}
              <h2 id="hero-strip-modal-title" className="font-heading text-xl font-bold text-surface-900 sm:text-2xl">
                {card.title}
              </h2>
              {showReadingShare && card.reference && card.reference.trim() !== '' ? (
                <p className="mt-1 text-sm font-medium text-burgundy-700">{card.reference}</p>
              ) : null}
              {!showReadingShare && card.subtitle.trim() !== '' ? (
                <p className="mt-1 text-sm text-surface-500">{card.subtitle}</p>
              ) : null}

              {isLiveModal && liveCountdownInfo ? (
                <div className="mt-4 space-y-3">
                  <div className="rounded-2xl border border-burgundy-100 bg-burgundy-50/80 p-4">
                    <div className="flex items-start gap-3">
                      <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-burgundy-800 text-white">
                        <Clock3 className="h-5 w-5" aria-hidden />
                      </div>
                      <div className="min-w-0">
                        <p className="text-[11px] font-semibold uppercase tracking-[0.14em] text-burgundy-700">
                          {liveCountdownInfo.isLiveNow ? 'Temps restant avant la fin' : 'Temps restant avant le live'}
                        </p>
                        <p className="mt-1 text-lg font-bold tabular-nums text-burgundy-950">
                          {liveCountdownInfo.modalHeadline}
                        </p>
                        <p className="mt-1 text-sm leading-relaxed text-burgundy-900/80">
                          {liveCountdownInfo.modalDetail}
                        </p>
                      </div>
                    </div>
                  </div>

                  {!liveCountdownInfo.isLiveNow && liveCountdownInfo.modalScheduledAt !== '' ? (
                    <div className="rounded-2xl border border-surface-200 bg-surface-50 p-4">
                      <div className="flex items-start gap-3">
                        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-burgundy-800 ring-1 ring-surface-200">
                          <CalendarClock className="h-5 w-5" aria-hidden />
                        </div>
                        <div className="min-w-0">
                          <p className="text-[11px] font-semibold uppercase tracking-[0.14em] text-surface-500">
                            Début prévu
                          </p>
                          <p className="mt-1 text-sm font-semibold capitalize text-surface-900">
                            {liveCountdownInfo.modalScheduledAt}
                          </p>
                          {liveCountdownInfo.tileContext !== '' ? (
                            <p className="mt-1 text-sm text-surface-600">{liveCountdownInfo.tileContext}</p>
                          ) : null}
                        </div>
                      </div>
                    </div>
                  ) : null}

                  {liveCountdownInfo.isLiveNow && showLivePlayer ? (
                    <p className="rounded-2xl bg-red-50 px-4 py-3 text-sm font-medium text-red-900">
                      {liveCountdownInfo.modalDetail}
                    </p>
                  ) : null}
                </div>
              ) : null}

              {card.description.trim() !== '' ? (
                <p className="mt-4 whitespace-pre-line text-sm leading-relaxed text-surface-700">{card.description}</p>
              ) : isUpcomingLivePreview ? (
                <p className="mt-4 text-sm leading-relaxed text-surface-500">
                  Le live n&apos;a pas encore commencé. Revenez à l&apos;heure indiquée pour nous rejoindre en direct.
                </p>
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
            </div>
          </motion.div>
        </motion.div>
      ) : null}
    </AnimatePresence>
  );
}
