import type { WallTestimony } from '../data/types';

/**
 * Date de publication sur le mur (validation), avec repli sur la création.
 */
export function testimonyPublishedAt(testimony: WallTestimony): string {
  if (testimony.publishedAt !== undefined && testimony.publishedAt !== '') {
    return testimony.publishedAt;
  }
  return testimony.createdAt ?? '';
}
import { testimonyHasVideo, testimonyVideoThumbnail } from './testimonyVideo';

export type CarouselPreview =
  | { mode: 'video-thumb'; url: string }
  | { mode: 'video-file'; url: string }
  | { mode: 'photo'; url: string }
  | { mode: 'text'; excerpt: string };

/**
 * Indique si le témoignage comporte au moins une photo.
 */
export function testimonyHasImages(testimony: WallTestimony): boolean {
  return testimony.images.length > 0;
}

/**
 * Aperçu média pour les mini-cartes du carrousel hero.
 *
 * @param testimony Témoignage mur.
 * @returns Type d’aperçu et URL ou extrait texte.
 */
export function testimonyCarouselPreview(testimony: WallTestimony): CarouselPreview {
  const thumb = testimonyVideoThumbnail(testimony);

  if (testimonyHasVideo(testimony)) {
    if (thumb !== '') {
      return { mode: 'video-thumb', url: thumb };
    }
    if (testimony.videoFileUrl !== '') {
      return { mode: 'video-file', url: testimony.videoFileUrl };
    }
  }

  if (testimonyHasImages(testimony)) {
    return { mode: 'photo', url: testimony.images[0]?.url ?? '' };
  }

  const excerpt = testimony.text.trim();
  return { mode: 'text', excerpt: excerpt !== '' ? excerpt : 'Témoignage' };
}

/**
 * Fusionne le carrousel : ajoute les nouveaux témoignages sans recharger toute la liste.
 *
 * @param prev Liste affichée.
 * @param fresh Lot API frais.
 * @param maxItems Nombre max. d’éléments conservés.
 * @returns Liste fusionnée ou la même référence si aucun nouveau.
 */
export function mergeCarouselTestimonies(
  prev: WallTestimony[],
  fresh: WallTestimony[],
  maxItems = 24,
): WallTestimony[] {
  const prevIds = new Set(prev.map((item) => item.id));
  const newcomers = fresh.filter((item) => !prevIds.has(item.id));

  if (newcomers.length === 0) {
    return prev;
  }

  const seen = new Set<string>();
  const merged: WallTestimony[] = [];

  for (const item of [...newcomers, ...prev]) {
    if (seen.has(item.id)) {
      continue;
    }
    seen.add(item.id);
    merged.push(item);
    if (merged.length >= maxItems) {
      break;
    }
  }

  return merged;
}
