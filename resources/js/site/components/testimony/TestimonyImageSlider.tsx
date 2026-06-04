import { useState } from 'react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { cn } from '../../lib/utils';

type SlideImage = { id: string; url: string };

type TestimonyImageSliderProps = {
  images: SlideImage[];
  onImageClick?: (index: number) => void;
  className?: string;
};

/**
 * Carrousel d’images avec compteur (ex. 1/5) pour les post-its.
 */
export default function TestimonyImageSlider({ images, onImageClick, className }: TestimonyImageSliderProps) {
  const [index, setIndex] = useState(0);

  if (images.length === 0) {
    return null;
  }

  const go = (delta: number, e?: React.MouseEvent) => {
    e?.stopPropagation();
    setIndex((i) => (i + delta + images.length) % images.length);
  };

  return (
    <div className={cn('relative mb-3', className)} onClick={(e) => e.stopPropagation()}>
      <button
        type="button"
        className="relative block w-full overflow-hidden rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-[#950000]"
        onClick={() => onImageClick?.(index)}
      >
        <img
          src={images[index].url}
          alt=""
          className="aspect-[4/3] w-full object-cover transition-transform hover:scale-[1.02]"
          loading="lazy"
        />
        <span className="absolute bottom-2 right-2 rounded-full bg-black/60 px-2 py-0.5 text-[11px] font-semibold text-white">
          {index + 1}/{images.length}
        </span>
      </button>
      {images.length > 1 ? (
        <>
          <button
            type="button"
            aria-label="Photo précédente"
            onClick={(e) => go(-1, e)}
            className="absolute left-1 top-1/2 -translate-y-1/2 rounded-full bg-white/90 p-1 shadow"
          >
            <ChevronLeft className="h-4 w-4" />
          </button>
          <button
            type="button"
            aria-label="Photo suivante"
            onClick={(e) => go(1, e)}
            className="absolute right-1 top-1/2 -translate-y-1/2 rounded-full bg-white/90 p-1 shadow"
          >
            <ChevronRight className="h-4 w-4" />
          </button>
        </>
      ) : null}
    </div>
  );
}
