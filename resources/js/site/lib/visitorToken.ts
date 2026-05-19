const STORAGE_KEY = 'cmp_site_visitor_token';

/**
 * Retourne un UUID persistant pour identifier anonymement le navigateur (réactions).
 *
 * @returns Jeton UUID ou chaîne vide si `localStorage` indisponible (SSR).
 */
export function getVisitorToken(): string {
  if (typeof window === 'undefined' || !window.localStorage) {
    return '';
  }

  let token = window.localStorage.getItem(STORAGE_KEY);

  if (!token) {
    token = crypto.randomUUID();
    window.localStorage.setItem(STORAGE_KEY, token);
  }

  return token;
}
