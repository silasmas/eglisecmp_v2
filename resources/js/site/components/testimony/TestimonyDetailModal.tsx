import { useEffect } from 'react';
import { ChevronLeft, ChevronRight, X } from 'lucide-react';
import type { WallTestimony } from '../../data/types';
import ExpandableText from '../ui/ExpandableText';
import SocialShareToolbar from '../ui/SocialShareToolbar';
import TestimonyImageSlider from './TestimonyImageSlider';
import TestimonyMediaBadges from './TestimonyMediaBadges';
import TestimonyPublishedAgo from './TestimonyPublishedAgo';
import { testimonyPublishedAt } from '../../lib/testimonyMedia';
import TestimonyReactionBar from './TestimonyReactionBar';
import { recordTestimonyShare } from '../../lib/siteApi';
import { testimonyHasVideo } from '../../lib/testimonyVideo';

type TestimonyDetailModalProps = {
  open: boolean;
  items: WallTestimony[];
  index: number;
  reactionLabels: Record<string, string>;
  onClose: () => void;
  onIndexChange: (next: number) => void;
};

/**
 * Modale plein écran : témoignage complet, navigation préc./suiv., partage et réactions.
 */
export default function TestimonyDetailModal({
  open,
  items,
  index,
  reactionLabels,
  onClose,
  onIndexChange,
}: TestimonyDetailModalProps) {
  const testimony = items[index];

  useEffect(() => {
    if (!open) {
      return;
    }
    const onKey = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        onClose();
      }
      if (event.key === 'ArrowLeft' && index > 0) {
        onIndexChange(index - 1);
      }
      if (event.key === 'ArrowRight' && index < items.length - 1) {
        onIndexChange(index + 1);
      }
    };
    document.addEventListener('keydown', onKey);
    document.body.style.overflow = 'hidden';
    return () => {
      document.removeEventListener('keydown', onKey);
      document.body.style.overflow = '';
    };
  }, [open, index, items.length, onClose, onIndexChange]);

  if (!open || testimony === undefined) {
    return null;
  }

  const hasVideo = testimonyHasVideo(testimony);
  const sharePath = testimony.sharePath ?? `/temoignages?open=${testimony.id}`;

  return (
    <div className="fixed inset-0 z-[200] flex items-center justify-center p-4" role="dialog" aria-modal="true">
      <button type="button" className="absolute inset-0 bg-black/70" aria-label="Fermer" onClick={onClose} />

      <div
        className="relative z-10 flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-surface-900"
        style={{
          backgroundColor: testimony.postitColor || '#FFF6D9',
          fontFamily: testimony.fontFamily,
        }}
      >
        <TestimonyMediaBadges testimony={testimony} className="right-4 top-4" />
        <div className="flex items-center justify-between border-b border-black/10 px-4 py-3">
          <span className="text-xs font-semibold uppercase tracking-wide text-surface-600">
            {testimony.category || 'Témoignage'}
          </span>
          <button
            type="button"
            onClick={onClose}
            className="rounded-lg p-1 hover:bg-black/5"
            aria-label="Fermer"
          >
            <X className="h-5 w-5" />
          </button>
        </div>

        <div className="flex-1 overflow-y-auto px-6 py-5">
          <h2 className="mb-3 text-2xl font-bold text-surface-900">{testimony.title}</h2>

          {testimony.text !== '' ? (
            <ExpandableText text={testimony.text} maxChars={400} className="mb-4 text-base" />
          ) : null}

          {hasVideo ? (
            <div className="mb-4 aspect-video overflow-hidden rounded-xl bg-black/10">
              {testimony.videoEmbedUrl !== '' ? (
                <iframe
                  title={testimony.title}
                  src={testimony.videoEmbedUrl}
                  className="h-full w-full"
                  allowFullScreen
                />
              ) : (
                <video src={testimony.videoFileUrl} controls className="h-full w-full" />
              )}
            </div>
          ) : null}

          {testimony.images.length > 0 ? (
            <TestimonyImageSlider images={testimony.images} className="mb-4" />
          ) : null}

          <div className="flex flex-wrap items-baseline justify-between gap-2">
            <p className="font-semibold text-surface-800">— {testimony.author}</p>
            <TestimonyPublishedAgo publishedAt={testimonyPublishedAt(testimony)} className="text-xs" />
          </div>

          <div className="mt-6 flex flex-wrap items-center gap-4">
            <TestimonyReactionBar
              reactableKey={testimony.reactableKey}
              labels={reactionLabels}
              singleChoice
            />
            <SocialShareToolbar
              title={testimony.title}
              description={testimony.text.slice(0, 120)}
              sharePath={sharePath}
              compact
              onShare={() => {
                void recordTestimonyShare(testimony.id);
              }}
            />
          </div>
        </div>

        <div className="flex items-center justify-between border-t border-black/10 px-4 py-3">
          <button
            type="button"
            disabled={index <= 0}
            onClick={() => onIndexChange(index - 1)}
            className="inline-flex items-center gap-1 rounded-lg px-3 py-2 text-sm font-medium disabled:opacity-40"
          >
            <ChevronLeft className="h-4 w-4" />
            Précédent
          </button>
          <span className="text-xs text-surface-500">
            {index + 1} / {items.length}
          </span>
          <button
            type="button"
            disabled={index >= items.length - 1}
            onClick={() => onIndexChange(index + 1)}
            className="inline-flex items-center gap-1 rounded-lg px-3 py-2 text-sm font-medium disabled:opacity-40"
          >
            Suivant
            <ChevronRight className="h-4 w-4" />
          </button>
        </div>
      </div>
    </div>
  );
}
