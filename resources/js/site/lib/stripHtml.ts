/**
 * Nettoie une chaîne HTML pour n'en garder que le texte lisible.
 *
 * @param value Chaîne pouvant contenir des balises HTML.
 * @returns Texte plat sans balises, espaces normalisés.
 */
export function stripHtml(value: string): string {
  if (value.trim() === '') {
    return '';
  }

  if (typeof document !== 'undefined') {
    const container = document.createElement('div');
    container.innerHTML = value;
    return (container.textContent ?? container.innerText ?? '')
      .replace(/\u00a0/g, ' ')
      .replace(/\s+/g, ' ')
      .trim();
  }

  return value
    .replace(/<[^>]*>/g, ' ')
    .replace(/&nbsp;/gi, ' ')
    .replace(/&amp;/gi, '&')
    .replace(/&quot;/gi, '"')
    .replace(/&#39;/gi, "'")
    .replace(/&lt;/gi, '<')
    .replace(/&gt;/gi, '>')
    .replace(/\s+/g, ' ')
    .trim();
}
