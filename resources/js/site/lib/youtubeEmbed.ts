/**
 * Paramètres de lecture pour les iframes YouTube embed.
 */

/**
 * Ajoute l’autoplay muet aux URLs embed YouTube (respect des politiques navigateur).
 *
 * @param embedUrl URL fournie par l’API (`youtubeEmbedUrl`).
 * @returns URL avec paramètres ou chaîne vide si absente.
 */
export function youtubeEmbedWithAutostart(embedUrl: string | null | undefined): string {
  return withEmbedPlaybackParams(embedUrl, true);
}

/**
 * Ajoute autoplay+mutes aux URLs d’iframe YouTube existantes renvoyées par l’API.
 *
 * @param embedUrl URL embed (avec query éventuelle).
 * @param autoplay Lance la lecture immédiate (navigateurs exigent souvent mute=1).
 * @returns URL prête pour l’iframe, ou chaîne vide.
 */
export function withEmbedPlaybackParams(embedUrl: string | null | undefined, autoplay: boolean): string {
  const trimmed = (embedUrl ?? '').trim();

  if (trimmed === '') {
    return '';
  }

  if (!autoplay) {
    return trimmed;
  }

  const sep = trimmed.includes('?') ? '&' : '?';

  return `${trimmed}${sep}autoplay=1&mute=1&playsinline=1`;
}
