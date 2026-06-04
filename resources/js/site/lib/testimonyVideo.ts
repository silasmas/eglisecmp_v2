import type { WallTestimony } from '../data/types';

/**
 * Indique si le témoignage comporte une vidéo affichable.
 */
export function testimonyHasVideo(testimony: WallTestimony): boolean {
  return (
    testimony.kind === 'video' ||
    testimony.kind === 'mix' ||
    testimony.videoEmbedUrl !== '' ||
    testimony.videoFileUrl !== ''
  );
}

/**
 * URL de vignette pour le carrousel / mini-cartes.
 */
export function testimonyVideoThumbnail(testimony: WallTestimony): string {
  if (testimony.videoThumbnailUrl !== undefined && testimony.videoThumbnailUrl !== '') {
    return testimony.videoThumbnailUrl;
  }
  if (testimony.videoEmbedUrl !== '') {
    const match = testimony.videoEmbedUrl.match(/\/embed\/([A-Za-z0-9_-]{11})/);
    if (match?.[1]) {
      return `https://i.ytimg.com/vi/${match[1]}/hqdefault.jpg`;
    }
  }
  return '';
}
