import { Play } from 'lucide-react';
import type { CarouselPreview } from '../../lib/testimonyMedia';

type TestimonyCarouselPreviewProps = {
  preview: CarouselPreview;
  title: string;
};

/**
 * Aperçu média compact pour une mini-carte du carrousel vertical.
 */
export default function TestimonyCarouselPreview({ preview, title }: TestimonyCarouselPreviewProps) {
  if (preview.mode === 'text') {
    return <p className="tw-card-mini-text">{preview.excerpt}</p>;
  }

  if (preview.mode === 'photo') {
    return (
      <div className="relative my-2 overflow-hidden rounded-md">
        <img src={preview.url} alt="" className="aspect-video w-full object-cover" />
      </div>
    );
  }

  if (preview.mode === 'video-file') {
    return (
      <div className="relative my-2 overflow-hidden rounded-md bg-black/20">
        <video
          src={preview.url}
          className="aspect-video w-full object-cover"
          muted
          playsInline
          preload="metadata"
          aria-label={title}
        />
        <span className="absolute inset-0 flex items-center justify-center bg-black/25">
          <span className="flex h-9 w-9 items-center justify-center rounded-full bg-[#950000] text-white">
            <Play className="h-4 w-4 fill-current" aria-hidden />
          </span>
        </span>
      </div>
    );
  }

  return (
    <div className="relative my-2 overflow-hidden rounded-md">
      <img src={preview.url} alt="" className="aspect-video w-full object-cover" />
      <span className="absolute inset-0 flex items-center justify-center bg-black/25">
        <span className="flex h-9 w-9 items-center justify-center rounded-full bg-[#950000] text-white">
          <Play className="h-4 w-4 fill-current" aria-hidden />
        </span>
      </span>
    </div>
  );
}
