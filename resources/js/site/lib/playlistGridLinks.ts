/**
 * Utilitaires de liens pour la grille playlists (null-safe, internes vs externes).
 */

/**
 * Normalise une chaîne optionnelle en texte trimé.
 *
 * @param value Valeur brute (string, null, undefined).
 * @returns Texte trimé ou chaîne vide.
 */
export function safeTrimmedString(value: unknown): string {
  if (typeof value !== 'string') {
    return '';
  }

  return value.trim();
}

/**
 * Convertit un compteur API en entier positif.
 *
 * @param value Nombre ou chaîne numérique.
 * @returns Entier ≥ 0.
 */
export function resolvePlaylistVideoCount(value: unknown): number {
  if (typeof value === 'number' && Number.isFinite(value)) {
    return Math.max(0, Math.floor(value));
  }

  const parsed = Number(value);

  if (Number.isFinite(parsed)) {
    return Math.max(0, Math.floor(parsed));
  }

  return 0;
}

/**
 * Indique si l’URL doit sortir du routeur SPA (http(s), mailto, etc.).
 *
 * @param href URL candidate.
 */
export function isExternalHref(href: string): boolean {
  const normalized = href.trim().toLowerCase();

  return (
    normalized.startsWith('http://') ||
    normalized.startsWith('https://') ||
    normalized.startsWith('mailto:') ||
    normalized.startsWith('tel:')
  );
}

/**
 * Résout l’URL de navigation d’une carte playlist.
 *
 * @param groupHref Lien personnalisé API (peut être null).
 * @param eventId Identifiant événement interne.
 * @param fallbackHref Route par défaut si aucun identifiant.
 */
export function resolvePlaylistGroupHref(
  groupHref: unknown,
  eventId: string,
  fallbackHref = '/teachings?tab=playlists',
): string {
  const customHref = safeTrimmedString(groupHref);

  if (customHref !== '') {
    return customHref;
  }

  if (eventId !== '') {
    return `/teachings/playlist/${encodeURIComponent(eventId)}`;
  }

  return fallbackHref;
}
