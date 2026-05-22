/** Image par défaut lorsque la vignette est absente ou invalide (alignée sur `site_public.placeholder_image_url`). */
export const DEFAULT_PLACEHOLDER_IMAGE =
  'https://images.unsplash.com/photo-1507692049790-de58290a4334?w=1200&h=800&fit=crop';

/**
 * Retourne une URL d'image exploitable par le navigateur, ou le placeholder.
 *
 * @param src URL ou chemin renvoyé par l'API.
 * @returns URL absolue ou image de secours.
 */
export function resolvePublicImage(src: string | null | undefined): string {
  const trimmed = (src ?? '').trim();

  if (trimmed === '') {
    return DEFAULT_PLACEHOLDER_IMAGE;
  }

  if (/^https?:\/\//i.test(trimmed) || trimmed.startsWith('//')) {
    return trimmed.startsWith('//') ? `https:${trimmed}` : trimmed;
  }

  if (trimmed.startsWith('/')) {
    const origin =
      typeof window !== 'undefined' && window.location.origin
        ? window.location.origin
        : '';

    return origin !== '' ? `${origin}${trimmed}` : trimmed;
  }

  return DEFAULT_PLACEHOLDER_IMAGE;
}

/**
 * Nombre de jours avant une date (affichage « dans X jour(s) »), minimum 1 si > 24 h.
 *
 * @param targetIso Date cible ISO.
 * @param now Référence temporelle.
 * @returns Nombre de jours entiers à afficher, ou null si moins de 24 h.
 */
export function daysUntilTarget(targetIso: string, now: Date): number | null {
  const target = new Date(targetIso);

  if (Number.isNaN(target.getTime())) {
    return null;
  }

  const secondsUntil = Math.max(0, (target.getTime() - now.getTime()) / 1000);

  if (secondsUntil < 86400) {
    return null;
  }

  return Math.max(1, Math.ceil(secondsUntil / 86400));
}

/**
 * Libellé principal de la tuile live : délai restant en jours ou compte à rebours.
 *
 * @param targetIso Date cible ISO (début du prochain live ou fin du live en cours).
 * @param now Horloge courante.
 * @param countdown Chaîne HH:MM:SS déjà calculée.
 * @param isLiveNow Indique si un live est en cours.
 * @returns Texte affiché sur la tuile « Prochain live ».
 */
export function formatLiveTilePrimaryLabel(
  targetIso: string | null | undefined,
  now: Date,
  countdown: string,
  isLiveNow: boolean,
): string {
  if (isLiveNow) {
    if (countdown !== '00:00:00') {
      return `Live en cours · fin dans ${countdown}`;
    }

    return 'Live en cours';
  }

  if (targetIso) {
    const days = daysUntilTarget(targetIso, now);

    if (days != null && days > 0) {
      return `Prochain live dans ${days} jour${days > 1 ? 's' : ''}`;
    }
  }

  if (countdown !== '00:00:00') {
    return `Prochain live dans ${countdown}`;
  }

  return 'Prochain live';
}

/**
 * Libellé du prochain live : décompte HH:MM:SS ou nombre de jours.
 *
 * @param targetIso Date du prochain live.
 * @param now Horloge courante.
 * @param countdown Chaîne HH:MM:SS déjà calculée.
 * @param apiDays Jours fournis par l'API (`liveTiming.daysUntil`), optionnel.
 * @deprecated Préférer {@link formatLiveTilePrimaryLabel} pour la tuile hero.
 */
export function formatLivePrimaryLabel(
  targetIso: string | null | undefined,
  now: Date,
  countdown: string,
  apiDays?: number | null,
  liveStatus?: 'live' | 'upcoming' | null,
): string {
  return formatLiveTilePrimaryLabel(
    targetIso,
    now,
    countdown,
    liveStatus === 'live',
  );
}

/**
 * Extrait un extrait court pour le bandeau hero (lecture du jour).
 *
 * @param text Texte complet.
 * @param maxLength Longueur maximale.
 */
export function excerptText(text: string, maxLength = 100): string {
  const clean = text.replace(/\s+/g, ' ').trim();

  if (clean.length <= maxLength) {
    return clean;
  }

  return `${clean.slice(0, maxLength).trim()}…`;
}
