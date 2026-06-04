import { Image as ImageIcon, Video } from 'lucide-react';
import type { WallTestimony } from '../../data/types';
import { testimonyHasImages } from '../../lib/testimonyMedia';
import { testimonyHasVideo } from '../../lib/testimonyVideo';
import { cn } from '../../lib/utils';

type TestimonyMediaBadgesProps = {
  testimony: WallTestimony;
  className?: string;
};

/**
 * Pastilles indiquant les types de médias attachés (vidéo, image).
 */
export default function TestimonyMediaBadges({ testimony, className }: TestimonyMediaBadgesProps) {
  const hasVideo = testimonyHasVideo(testimony);
  const hasImages = testimonyHasImages(testimony);

  if (!hasVideo && !hasImages) {
    return null;
  }

  return (
    <div
      className={cn('pointer-events-none absolute right-2 top-2 z-[2] flex gap-1', className)}
      aria-hidden
    >
      {hasVideo ? (
        <span
          className="inline-flex h-6 w-6 items-center justify-center rounded-full bg-black/55 text-white shadow-sm"
          title="Vidéo"
        >
          <Video className="h-3.5 w-3.5" strokeWidth={2.25} />
        </span>
      ) : null}
      {hasImages ? (
        <span
          className="inline-flex h-6 w-6 items-center justify-center rounded-full bg-black/55 text-white shadow-sm"
          title="Photo(s)"
        >
          <ImageIcon className="h-3.5 w-3.5" strokeWidth={2.25} />
        </span>
      ) : null}
    </div>
  );
}
