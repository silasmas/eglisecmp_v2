import { Play } from 'lucide-react';
import type { WallTestimony } from '../../data/types';
import { testimonyHasVideo, testimonyVideoThumbnail } from '../../lib/testimonyVideo';
import { cn } from '../../lib/utils';
import SocialShareToolbar from '../ui/SocialShareToolbar';
import { recordTestimonyShare } from '../../lib/siteApi';
import TestimonyImageSlider from './TestimonyImageSlider';
import TestimonyMediaBadges from './TestimonyMediaBadges';
import TestimonyPublishedAgo from './TestimonyPublishedAgo';
import { testimonyPublishedAt } from '../../lib/testimonyMedia';
import TestimonyReactionBar from './TestimonyReactionBar';

type TestimonyPostItCardProps = {
  testimony: WallTestimony;
  rotation?: number;
  reactionLabels: Record<string, string>;
  onOpen: () => void;
  onImageZoom?: (startIndex: number) => void;
  compact?: boolean;
};

/**
 * Carte post-it du mur avec réactions, partage et médias (hauteur fixe, défilement interne).
 */
export default function TestimonyPostItCard({
  testimony,
  rotation = 0,
  reactionLabels,
  onOpen,
  onImageZoom,
  compact = false,
}: TestimonyPostItCardProps) {
  const hasVideo = testimonyHasVideo(testimony);
  const thumb = testimonyVideoThumbnail(testimony);
  const sharePath = testimony.sharePath ?? `/temoignages?open=${testimony.id}`;

  return (
    <article
      role="button"
      tabIndex={0}
      onClick={onOpen}
      onKeyDown={(e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          onOpen();
        }
      }}
      className={cn(
        compact ? 'tw-postit relative' : 'group relative flex h-[420px] max-h-[420px] flex-col rounded-2xl p-5 shadow-md',
        'cursor-pointer transition-transform duration-300 hover:z-10 hover:scale-[1.01] hover:shadow-lg',
      )}
      style={{
        backgroundColor: testimony.postitColor || '#FFF6D9',
        fontFamily: testimony.fontFamily || 'Inter, sans-serif',
        transform: `rotate(${rotation}deg)`,
      }}
    >
      <TestimonyMediaBadges testimony={testimony} className="right-3 top-3" />

      {testimony.category !== '' ? (
        <span className="mb-2 inline-block shrink-0 self-start rounded-full bg-black/5 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-surface-700">
          {testimony.category}
        </span>
      ) : null}

      <h3 className="mb-2 shrink-0 pr-12 text-base font-bold text-surface-900">{testimony.title}</h3>

      <div className="tw-postit__body min-h-0 flex-1 overflow-y-auto overscroll-contain pr-1">
        {testimony.text !== '' ? (
          <p className="mb-3 whitespace-pre-wrap text-sm leading-relaxed text-surface-800">{testimony.text}</p>
        ) : null}

        {hasVideo && (testimony.videoEmbedUrl !== '' || thumb !== '') ? (
          <div className="relative mb-3 overflow-hidden rounded-lg bg-black/10" onClick={(e) => e.stopPropagation()}>
            {testimony.videoEmbedUrl !== '' ? (
              <iframe title={testimony.title} src={testimony.videoEmbedUrl} className="aspect-video w-full" allowFullScreen />
            ) : thumb !== '' ? (
              <div className="relative">
                <img src={thumb} alt="" className="aspect-video w-full object-cover" />
                <span className="absolute inset-0 flex items-center justify-center bg-black/20">
                  <Play className="h-10 w-10 text-white drop-shadow" />
                </span>
              </div>
            ) : null}
          </div>
        ) : hasVideo && testimony.videoFileUrl !== '' ? (
          <video
            src={testimony.videoFileUrl}
            className="mb-3 max-h-48 w-full rounded-lg"
            controls
            onClick={(e) => e.stopPropagation()}
          />
        ) : null}

        {testimony.images.length > 0 ? (
          <TestimonyImageSlider images={testimony.images} onImageClick={(i) => onImageZoom?.(i)} />
        ) : null}
      </div>

      <footer className="mt-2 shrink-0 border-t border-black/10 pt-3">
        <div className="mb-2 flex flex-wrap items-baseline justify-between gap-x-2 gap-y-0.5">
          <p className="text-sm font-semibold text-surface-800">— {testimony.author}</p>
          <TestimonyPublishedAgo publishedAt={testimonyPublishedAt(testimony)} />
        </div>
        <div
          className="flex flex-wrap items-center gap-2"
          onClick={(e) => e.stopPropagation()}
          onKeyDown={(e) => e.stopPropagation()}
          role="presentation"
        >
          <TestimonyReactionBar
            reactableKey={testimony.reactableKey}
            labels={reactionLabels}
            singleChoice
            compact
          />
          <SocialShareToolbar
            title={testimony.title}
            description={testimony.text.slice(0, 100)}
            sharePath={sharePath}
            compact
            onShare={() => {
              void recordTestimonyShare(testimony.id);
            }}
          />
        </div>
      </footer>
    </article>
  );
}
