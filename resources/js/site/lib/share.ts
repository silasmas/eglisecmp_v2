/**
 * Fabrique une URL absolue pour les boutons « partager » depuis un chemin relatif SPA.
 *
 * @param pathname Chemin commençant par `/` (ex. `/teachings`).
 */
export function buildShareUrl(pathname: string): string {
  if (typeof window === 'undefined') {
    return pathname.startsWith('/') ? pathname : `/${pathname}`;
  }

  const normalized = pathname.startsWith('/') ? pathname : `/${pathname}`;

  return `${window.location.origin}${normalized}`;
}
