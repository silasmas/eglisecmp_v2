import clsx from 'clsx';
import { useEffect, useState } from 'react';
import { DEFAULT_PLACEHOLDER_IMAGE, resolvePublicImage } from '../../lib/placeholderImage';
import { Skeleton } from './Skeleton';

/**
 * Image avec skeleton en overlay jusqu'au premier chargement réussi (ou erreur puis placeholder).
 * Le parent doit avoir `position: relative` pour que le skeleton se positionne correctement.
 *
 * @param src URL ou chaîne vide (placeholder immédiat).
 * @param alt Texte alternatif.
 * @param className Classes Tailwind appliquées à l'`img` (`object-cover`, `rounded-*`).
 */
export default function ImageWithSkeleton({
  src,
  alt = '',
  className = '',
}: {
  src: string | null | undefined;
  alt?: string;
  className?: string;
}) {
  const trimmedSource = (src ?? '').trim();
  const tracksNetwork = trimmedSource !== '';

  const [currentSrc, setCurrentSrc] = useState(() => resolvePublicImage(src));
  const [loaded, setLoaded] = useState(!tracksNetwork);

  useEffect(() => {
    setCurrentSrc(resolvePublicImage(src));
    setLoaded(!(src ?? '').trim());
  }, [src]);

  return (
    <>
      {tracksNetwork && !loaded ? (
        <Skeleton className="pointer-events-none absolute inset-0 z-10 animate-pulse rounded-[inherit]" aria-hidden />
      ) : null}
      <img
        src={currentSrc}
        alt={alt}
        loading="lazy"
        decoding="async"
        className={clsx(
          tracksNetwork ? 'relative z-[1] transition-opacity duration-300' : '',
          tracksNetwork && loaded ? 'opacity-100' : tracksNetwork ? 'opacity-0' : 'opacity-100',
          className,
        )}
        onLoad={() => {
          setLoaded(true);
        }}
        onError={() => {
          if (currentSrc !== DEFAULT_PLACEHOLDER_IMAGE) {
            setCurrentSrc(DEFAULT_PLACEHOLDER_IMAGE);
          }
          setLoaded(true);
        }}
      />
    </>
  );
}
