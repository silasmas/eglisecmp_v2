/**
 * Détecte le basename React Router (ex. `/public` si le site est servi dans un sous-dossier).
 */
export function detectSpaBasename(): string {
  if (typeof document !== 'undefined') {
    const meta = document.querySelector('meta[name="spa-base"]');
    const content = meta?.getAttribute('content')?.trim() ?? '';
    if (content !== '' && content !== '/') {
      return content.replace(/\/$/, '');
    }
  }

  if (typeof window !== 'undefined') {
    const { pathname } = window.location;
    if (pathname === '/public' || pathname.startsWith('/public/')) {
      return '/public';
    }
  }

  return '';
}
