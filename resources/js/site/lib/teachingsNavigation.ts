import type { TeachingsTab } from '../data/types';

export type PlaylistOriginTab = TeachingsTab | 'bunda';

const ORIGIN_LABELS: Record<PlaylistOriginTab, string> = {
  meditations: 'Retour aux méditations',
  playlists: 'Retour aux playlists',
  sermons: 'Retour aux messages',
  bunda: 'Retour à Bunda',
};

const ORIGIN_HREFS: Record<PlaylistOriginTab, string> = {
  meditations: '/teachings?tab=meditations',
  playlists: '/teachings?tab=playlists',
  sermons: '/teachings?tab=sermons',
  bunda: '/bunda',
};

/**
 * Ajoute le paramètre `from` pour mémoriser l’onglet d’origine vers la page playlist.
 *
 * @param href URL cible.
 * @param fromTab Onglet ou page source.
 */
export function appendPlaylistFromParam(href: string, fromTab?: PlaylistOriginTab): string {
  if (fromTab === undefined) {
    return href;
  }

  if (!href.includes('/teachings/playlist/')) {
    return href;
  }

  const separator = href.includes('?') ? '&' : '?';

  return `${href}${separator}from=${encodeURIComponent(fromTab)}`;
}

/**
 * Résout le libellé et l’URL du bouton retour depuis la page playlist.
 *
 * @param fromParam Valeur du query `from`.
 */
export function resolvePlaylistBackNavigation(fromParam: string | null): { href: string; label: string } {
  const normalized = (fromParam ?? '').trim().toLowerCase();

  if (normalized === 'meditations' || normalized === 'playlists' || normalized === 'sermons' || normalized === 'bunda') {
    return {
      href: ORIGIN_HREFS[normalized],
      label: ORIGIN_LABELS[normalized],
    };
  }

  return {
    href: ORIGIN_HREFS.playlists,
    label: ORIGIN_LABELS.playlists,
  };
}
