/**
 * Formate une date de prédication (YYYY-MM-DD) pour une ligne de liste français.
 *
 * @param iso Chaîne courte date uniquement depuis l’API.
 * @returns Libellé localisé ou tiret si absent.
 */
export function formatPreachRowDate(iso: string): string {
  if (!iso || iso.trim() === '') {
    return '—';
  }

  try {
    return new Intl.DateTimeFormat('fr-FR', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    }).format(new Date(iso));
  } catch {
    return iso;
  }
}
