/**
 * Ajoute l’autoplay muet aux URLs embed YouTube (respect des politiques navigateur).
 *
 * @param embedUrl URL fournie par l’API (`youtubeEmbedUrl`).
 * @returns URL avec paramètres ou chaîne vide si absente.
 */
export function youtubeEmbedWithAutostart(embedUrl: string | null | undefined): string {
  if (!embedUrl) {
    return '';
  }

  const sep = embedUrl.includes('?') ? '&' : '?';

  return `${embedUrl}${sep}autoplay=1&mute=1&playsinline=1`;
}
