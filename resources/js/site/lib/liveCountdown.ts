import type { HeroLiveTiming } from '../data/types';

/** Décomposition du temps restant avant la cible. */
export interface LiveCountdownParts {
  days: number;
  hours: number;
  minutes: number;
  seconds: number;
  totalSeconds: number;
}

/** Infos de décompte live pour la tuile hero et la modale. */
export interface LiveCountdownInfo {
  isLiveNow: boolean;
  hasTarget: boolean;
  tileHeadline: string;
  tileContext: string;
  modalHeadline: string;
  modalDetail: string;
  modalScheduledAt: string;
  clock: string;
  parts: LiveCountdownParts;
}

/**
 * Parse une date ISO en objet Date valide.
 *
 * @param targetIso Date cible ISO8601.
 * @returns Date ou null si invalide.
 */
export function parseTargetDate(targetIso: string | null | undefined): Date | null {
  if (!targetIso) {
    return null;
  }

  const parsed = new Date(targetIso);

  if (Number.isNaN(parsed.getTime())) {
    return null;
  }

  return parsed;
}

/**
 * Calcule jours, heures, minutes et secondes restants.
 *
 * @param target Date cible.
 * @param now Horloge courante.
 * @returns Parts du décompte.
 */
export function getCountdownParts(target: Date, now: Date): LiveCountdownParts {
  const totalSeconds = Math.max(0, Math.floor((target.getTime() - now.getTime()) / 1000));
  const days = Math.floor(totalSeconds / 86400);
  const hours = Math.floor((totalSeconds % 86400) / 3600);
  const minutes = Math.floor((totalSeconds % 3600) / 60);
  const seconds = totalSeconds % 60;

  return {
    days,
    hours,
    minutes,
    seconds,
    totalSeconds,
  };
}

/**
 * Formate HH:MM:SS (ou J HH:MM:SS si >= 1 jour).
 *
 * @param parts Décomposition du décompte.
 * @returns Chaîne horloge.
 */
export function formatCountdownClock(parts: LiveCountdownParts): string {
  const pad = (value: number): string => String(value).padStart(2, '0');

  if (parts.days > 0) {
    return `${parts.days} j ${pad(parts.hours)}:${pad(parts.minutes)}:${pad(parts.seconds)}`;
  }

  return `${pad(parts.hours)}:${pad(parts.minutes)}:${pad(parts.seconds)}`;
}

/**
 * Libellé court « dans X jours » pour affichage tuile (> 24 h).
 *
 * @param totalSeconds Secondes restantes.
 * @returns Libellé jours ou null si moins de 24 h.
 */
function formatDaysHeadline(totalSeconds: number): string | null {
  if (totalSeconds < 86400) {
    return null;
  }

  const days = Math.max(1, Math.ceil(totalSeconds / 86400));

  return `Dans ${days} jour${days > 1 ? 's' : ''}`;
}

/**
 * Détail textuel complet du temps restant.
 *
 * @param parts Décomposition du décompte.
 * @param isLiveNow Live en cours (fin) ou prochain live (début).
 * @returns Phrase descriptive en français.
 */
export function formatDelayDetailed(parts: LiveCountdownParts, isLiveNow: boolean): string {
  if (parts.totalSeconds === 0) {
    return isLiveNow ? 'Le live se termine très bientôt.' : 'Le live commence très bientôt.';
  }

  const segments: string[] = [];

  if (parts.days > 0) {
    segments.push(`${parts.days} jour${parts.days > 1 ? 's' : ''}`);
  }

  if (parts.hours > 0) {
    segments.push(`${parts.hours} heure${parts.hours > 1 ? 's' : ''}`);
  }

  if (parts.minutes > 0) {
    segments.push(`${parts.minutes} minute${parts.minutes > 1 ? 's' : ''}`);
  }

  if (parts.seconds > 0 || segments.length === 0) {
    segments.push(`${parts.seconds} seconde${parts.seconds > 1 ? 's' : ''}`);
  }

  const last = segments.pop() ?? '';
  const prefix = isLiveNow ? 'Il reste ' : 'Encore ';
  const body = segments.length > 0 ? `${segments.join(', ')} et ${last}` : last;

  return `${prefix}${body} ${isLiveNow ? 'avant la fin du live.' : 'avant le prochain live.'}`;
}

/**
 * Formate la date/heure de début prévue pour la modale.
 *
 * @param targetIso Début du live.
 * @returns Libellé localisé ou chaîne vide.
 */
export function formatScheduledDateTime(targetIso: string | null | undefined): string {
  const target = parseTargetDate(targetIso);

  if (!target) {
    return '';
  }

  const datePart = target.toLocaleDateString('fr-FR', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  });

  const timePart = target.toLocaleTimeString('fr-FR', {
    hour: '2-digit',
    minute: '2-digit',
  });

  return `${datePart} à ${timePart}`;
}

/**
 * Construit les libellés live (tuile + modale) à partir du timing API.
 *
 * @param targetIso Fin du live en cours ou début du prochain live.
 * @param now Horloge courante.
 * @param isLiveNow Indique si le live est en cours.
 * @param context Métadonnées programme depuis l'API.
 * @returns Infos prêtes pour l'UI.
 */
export function buildLiveCountdownInfo(
  targetIso: string | null | undefined,
  now: Date,
  isLiveNow: boolean,
  context?: Pick<HeroLiveTiming, 'programName' | 'scheduledLabel' | 'timeLabel' | 'dayLabel' | 'startIso'>,
): LiveCountdownInfo {
  const target = parseTargetDate(targetIso);
  const emptyParts: LiveCountdownParts = {
    days: 0,
    hours: 0,
    minutes: 0,
    seconds: 0,
    totalSeconds: 0,
  };

  const programName = context?.programName?.trim() ?? '';
  const scheduledLabel = context?.scheduledLabel?.trim() ?? '';
  const timeLabel = context?.timeLabel?.trim() ?? '';
  const tileContext = scheduledLabel !== ''
    ? scheduledLabel
    : programName !== ''
      ? programName
      : 'Consultez l’horaire du prochain live';

  if (!target) {
    return {
      isLiveNow,
      hasTarget: false,
      tileHeadline: isLiveNow ? 'Live en cours' : 'Prochain live',
      tileContext,
      modalHeadline: isLiveNow ? 'Live en cours' : 'Prochain live',
      modalDetail: isLiveNow
        ? 'Rejoignez-nous en direct dès maintenant.'
        : 'L’horaire du prochain live sera bientôt disponible.',
      modalScheduledAt: '',
      clock: '00:00:00',
      parts: emptyParts,
    };
  }

  const parts = getCountdownParts(target, now);
  const clock = formatCountdownClock(parts);
  const daysHeadline = formatDaysHeadline(parts.totalSeconds);
  const tileHeadline = isLiveNow
    ? parts.totalSeconds > 0
      ? `Live · fin dans ${clock}`
      : 'Live en cours'
    : daysHeadline ?? `Dans ${clock}`;

  const modalHeadline = isLiveNow
    ? parts.totalSeconds > 0
      ? `Fin du live dans ${clock}`
      : 'Live en cours'
    : daysHeadline ?? `Début dans ${clock}`;

  const startIso = context?.startIso ?? (isLiveNow ? null : targetIso);
  const modalScheduledAt = isLiveNow
    ? ''
    : formatScheduledDateTime(startIso ?? targetIso);

  return {
    isLiveNow,
    hasTarget: true,
    tileHeadline,
    tileContext,
    modalHeadline,
    modalDetail: formatDelayDetailed(parts, isLiveNow),
    modalScheduledAt,
    clock,
    parts,
  };
}
