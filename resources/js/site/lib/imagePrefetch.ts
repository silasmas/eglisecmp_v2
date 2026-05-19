import { resolvePublicImage } from './placeholderImage';

const prefetched = new Set<string>();

/**
 * Précharge des images dans le cache du navigateur (évite une relecture réseau lors des navigations suivantes).
 *
 * @param urls URLs relatives ou absolues comme renvoyées par l'API.
 */
export function prefetchImageUrls(urls: (string | null | undefined)[], maxImages = 48): void {
  if (typeof window === 'undefined') {
    return;
  }

  const unique: string[] = [];
  for (const url of urls.slice(0, maxImages)) {
    const resolved = resolvePublicImage(url);
    if (!prefetched.has(resolved)) {
      prefetched.add(resolved);
      unique.push(resolved);
    }
  }

  for (const href of unique) {
    const img = new Image();
    img.src = href;
  }
}
