import type { PlaylistEventGroup, Sermon } from '../data/types';

/**
 * Regroupe les méditations selon les rendez-vous du programme hebdomadaire (Mercredi, Jeudi, Dimanche).
 * La détection utilise d’abord le champ Filament « jour de culte », puis la référence / titre / extrait texte.
 *
 * @param items Publications de type méditation.
 * @returns Map jour → liste triée par date décroissante ; les messages non classés sont sous « Autres rendez-vous ».
 */
export function groupMeditationsByWeeklyProgram(items: Sermon[]): Map<string, Sermon[]> {
  const orderedLabels = ['Mercredi', 'Jeudi', 'Dimanche', 'Autres rendez-vous'] as const;

  const buckets = new Map<string, Sermon[]>();
  for (const label of orderedLabels) {
    buckets.set(label, []);
  }

  /**
   * Retourne un libellé de section (jour de culte) à partir du champ structuré `weeklyServiceDay` du post.
   *
   * @param day Valeur API (`mercredi`, `jeudi`, `dimanche`).
   */
  function sectionFromWeeklyField(day: string | null | undefined): string | null {
    if (!day) {
      return null;
    }

    const normalized = day.trim().toLowerCase();

    if (normalized === 'mercredi') {
      return 'Mercredi';
    }
    if (normalized === 'jeudi') {
      return 'Jeudi';
    }
    if (normalized === 'dimanche') {
      return 'Dimanche';
    }

    return null;
  }

  /**
   * Retourne un libellé de section (jour de culte) à partir du texte fourni.
   *
   * @param text Texte combiné titre / thème / description.
   */
  function sectionForText(text: string): string {
    const normalized = text
      .normalize('NFD')
      .replace(/\p{M}/gu, '')
      .toLowerCase();

    if (/\bmercredi\b/.test(normalized)) {
      return 'Mercredi';
    }
    if (/\bjeudi\b/.test(normalized)) {
      return 'Jeudi';
    }
    if (/\bdimanche\b/.test(normalized)) {
      return 'Dimanche';
    }

    return 'Autres rendez-vous';
  }

  for (const item of items) {
    const structured = sectionFromWeeklyField(item.weeklyServiceDay);
    const ctx = `${item.theme ?? ''} ${item.title} ${item.description}`;
    const key = structured ?? sectionForText(ctx);
    const list = buckets.get(key) ?? [];
    list.push(item);
    buckets.set(key, list);
  }

  for (const label of orderedLabels) {
    const list = buckets.get(label) ?? [];
    buckets.set(
      label,
      [...list].sort((a, b) => b.date.localeCompare(a.date)),
    );
  }

  return new Map(orderedLabels.map((label) => [label, buckets.get(label) ?? []]));
}

/**
 * Regroupe les publications liées à un événement pour l'onglet Playlists.
 *
 * @param items Publications avec `eventId`.
 * @returns Groupes ordonnés par titre d'événement.
 */
export function groupPostsByEvent(items: Sermon[]): PlaylistEventGroup[] {
  const map = new Map<string, PlaylistEventGroup>();

  for (const item of items) {
    const eventId = item.eventId ?? 'sans-evenement';
    const existing = map.get(eventId);

    if (existing) {
      existing.items.push(item);
      continue;
    }

    map.set(eventId, {
      eventId,
      eventTitle: item.eventTitle?.trim() !== '' ? item.eventTitle : 'Événement',
      eventImage: item.eventImage ?? '',
      items: [item],
    });
  }

  return [...map.values()]
    .map((group) => ({
      ...group,
      items: [...group.items].sort((a, b) => b.date.localeCompare(a.date)),
    }))
    .sort((a, b) => a.eventTitle.localeCompare(b.eventTitle, 'fr'));
}
