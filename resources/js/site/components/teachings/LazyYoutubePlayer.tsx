import { useEffect, useMemo, useState } from 'react';
import { Play } from 'lucide-react';
import { withEmbedPlaybackParams } from '../../lib/youtubeEmbed';
import ImageWithSkeleton from '../ui/ImageWithSkeleton';
import { Skeleton } from '../ui/Skeleton';

type LazyYoutubePlayerProps = {
  /** Identifiant stable (post id) — force le remontage de l’iframe au changement. */
  videoKey: string;
  embedUrl: string | null | undefined;
  title: string;
  thumbnail?: string | null;
  linkUrl?: string | null;
  autoplay?: boolean;
};

/**
 * Lecteur YouTube différé : skeleton local pendant le montage et le chargement de l’iframe.
 */
export default function LazyYoutubePlayer({
  videoKey,
  embedUrl,
  title,
  thumbnail,
  linkUrl,
  autoplay = false,
}: LazyYoutubePlayerProps) {
  const [mountIframe, setMountIframe] = useState(false);
  const [iframeLoaded, setIframeLoaded] = useState(false);

  const resolvedSrc = useMemo(
    () => withEmbedPlaybackParams(embedUrl, autoplay),
    [embedUrl, autoplay],
  );

  useEffect(() => {
    setMountIframe(false);
    setIframeLoaded(false);

    if (resolvedSrc === '') {
      return undefined;
    }

    const frameId = window.requestAnimationFrame(() => {
      setMountIframe(true);
    });

    return () => {
      window.cancelAnimationFrame(frameId);
    };
  }, [videoKey, resolvedSrc]);

  if (resolvedSrc === '') {
    return (
      <div className="relative aspect-video bg-black">
        {thumbnail ? (
          <ImageWithSkeleton
            src={thumbnail}
            alt=""
            className="absolute inset-0 h-full w-full object-cover opacity-75"
          />
        ) : (
          <Skeleton className="absolute inset-0 rounded-none bg-surface-800" />
        )}
        <div className="absolute inset-0 flex flex-col items-center justify-center gap-2 bg-black/60 p-4 text-center">
          <p className="text-sm font-semibold text-white">
            Vidéo indisponible en lecture intégrée (aucun lien YouTube valide).
          </p>
          {linkUrl ? (
            <a
              href={linkUrl}
              target="_blank"
              rel="noopener noreferrer"
              className="mt-2 rounded-lg bg-white/90 px-4 py-2 text-sm font-semibold text-surface-900 hover:bg-white"
            >
              Ouvrir sur YouTube
            </a>
          ) : null}
        </div>
      </div>
    );
  }

  const showLoader = !mountIframe || !iframeLoaded;

  return (
    <div className="relative aspect-video bg-black">
      {thumbnail && showLoader ? (
        <ImageWithSkeleton
          src={thumbnail}
          alt=""
          className="absolute inset-0 z-[1] h-full w-full object-cover opacity-40"
        />
      ) : null}

      {showLoader ? (
        <div
          className="absolute inset-0 z-[2] flex flex-col items-center justify-center gap-3 bg-surface-950/75"
          aria-busy="true"
          aria-live="polite"
        >
          <Skeleton className="h-14 w-14 rounded-full bg-white/20" />
          <div className="flex items-center gap-2 text-sm font-medium text-white/90">
            <Play className="h-4 w-4 fill-white/90" aria-hidden />
            Chargement de la vidéo…
          </div>
        </div>
      ) : null}

      {mountIframe ? (
        <iframe
          key={videoKey}
          src={resolvedSrc}
          title={`Lecture vidéo : ${title}`}
          className={`absolute inset-0 z-[3] h-full w-full border-0 transition-opacity duration-300 ${
            iframeLoaded ? 'opacity-100' : 'opacity-0'
          }`}
          allowFullScreen
          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
          onLoad={() => {
            setIframeLoaded(true);
          }}
        />
      ) : null}
    </div>
  );
}
