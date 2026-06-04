const MINUTE_MS = 60_000;
const HOUR_MS = 3_600_000;
const DAY_MS = 86_400_000;

/**
 * Formate une date en français relatif, de « il y a 1 minute » jusqu’à « il y a 1 jour ».
 *
 * @param isoDate Date ISO 8601.
 * @param nowMs Horodatage de référence (tests).
 * @returns Libellé relatif ou null si moins d’une minute.
 */
export function formatTestimonyPublishedAgo(isoDate: string, nowMs: number = Date.now()): string | null {
  if (isoDate.trim() === '') {
    return null;
  }

  const timestamp = new Date(isoDate).getTime();
  if (Number.isNaN(timestamp)) {
    return null;
  }

  const diffMs = Math.max(0, nowMs - timestamp);

  if (diffMs < MINUTE_MS) {
    return null;
  }

  if (diffMs < HOUR_MS) {
    const minutes = Math.floor(diffMs / MINUTE_MS);
    return minutes <= 1 ? 'il y a 1 minute' : `il y a ${minutes} minutes`;
  }

  if (diffMs < DAY_MS) {
    const hours = Math.floor(diffMs / HOUR_MS);
    return hours <= 1 ? 'il y a 1 heure' : `il y a ${hours} heures`;
  }

  return 'il y a 1 jour';
}
