import { useEffect, useState } from 'react';
import { DEFAULT_PLACEHOLDER_IMAGE, resolvePublicImage } from '../../lib/placeholderImage';

/**
 * Image du site avec URL normalisée et repli sur le placeholder en cas d'erreur réseau.
 *
 * Pour un skeleton pendant le chargement, utilisez le composant `ImageWithSkeleton` du même dossier.
 */
export default function SiteImage({
  src,
  alt = '',
  className = '',
}: {
  src: string | null | undefined;
  alt?: string;
  className?: string;
}) {
  const [currentSrc, setCurrentSrc] = useState(() => resolvePublicImage(src));

  useEffect(() => {
    setCurrentSrc(resolvePublicImage(src));
  }, [src]);

  return (
    <img
      src={currentSrc}
      alt={alt}
      className={className}
      loading="lazy"
      decoding="async"
      onError={() => {
        if (currentSrc !== DEFAULT_PLACEHOLDER_IMAGE) {
          setCurrentSrc(DEFAULT_PLACEHOLDER_IMAGE);
        }
      }}
    />
  );
}
