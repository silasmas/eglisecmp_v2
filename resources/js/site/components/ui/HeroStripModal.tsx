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
}: {
  open: boolean;
  onClose: () => void;
  card: HeroStripCard | null;
  showReadingShare?: boolean;
}) {
  if (!open || !card) {
    return null;
  }

  const bannerVisualSrc = card.bannerImage && card.bannerImage.trim() !== '' ? card.bannerImage : '';

  return (
    <div
      className="fixed inset-0 z-[100] flex items-center justify-center bg-surface-950/75 p-4 backdrop-blur-sm"
      role="dialog"
      aria-modal="true"
      aria-labelledby="hero-strip-modal-title"
      onClick={onClose}
      onKeyDown={(event) => {
        if (event.key === 'Escape') {
          onClose();
        }
      }}
    >
      <div
        className="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-3xl bg-white shadow-2xl ring-1 ring-black/5"
        onClick={(event) => event.stopPropagation()}
      >
        <div className="relative aspect-[21/9] w-full bg-surface-100">
          <ImageWithSkeleton src={bannerVisualSrc} alt="" className="h-full w-full object-cover" />
        </div>
        <div className="p-6 sm:p-8">
          <h2 id="hero-strip-modal-title" className="font-heading text-xl font-bold text-surface-900 sm:text-2xl">
            {card.title}
          </h2>
          {showReadingShare && card.reference && card.reference.trim() !== '' ? (
            <p className="mt-1 text-sm font-medium text-burgundy-700">{card.reference}</p>
          ) : null}
          {!showReadingShare && card.subtitle.trim() !== '' ? (
            <p className="mt-1 text-sm text-surface-500">{card.subtitle}</p>
          ) : null}
          <p className="mt-4 whitespace-pre-line text-sm leading-relaxed text-surface-700">{card.description}</p>
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
    </div>
  );
}
