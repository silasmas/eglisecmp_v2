import type { PlaylistOriginTab } from './teachingsNavigation';

const STORAGE_KEY = 'cmp-playlist-from';

/**
 * Mémorise l’onglet d’origine avant navigation vers une playlist.
 *
 * @param fromTab Onglet ou page source.
 */
export function rememberPlaylistOrigin(fromTab: PlaylistOriginTab): void {
  if (typeof window === 'undefined') {
    return;
  }

  try {
    window.sessionStorage.setItem(STORAGE_KEY, fromTab);
  } catch {
    // sessionStorage indisponible (mode privé strict).
  }
}

/**
 * Lit l’onglet d’origine mémorisé (repli si le query `from` a été perdu).
 */
export function readRememberedPlaylistOrigin(): PlaylistOriginTab | null {
  if (typeof window === 'undefined') {
    return null;
  }

  try {
    const value = window.sessionStorage.getItem(STORAGE_KEY)?.trim().toLowerCase() ?? '';
    if (value === 'meditations' || value === 'playlists' || value === 'sermons' || value === 'bunda') {
      return value;
    }
  } catch {
    return null;
  }

  return null;
}
