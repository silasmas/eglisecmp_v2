import type { Sermon } from '../data/types';

type SortableSermon = Pick<Sermon, 'date' | 'id' | 'sortTimestamp'>;

/**
 * Retourne le timestamp de tri d’un message (ISO complet ou date affichée).
 *
 * @param item Message ou vignette playlist.
 */
function resolveSortTime(item: SortableSermon): number {
  const stamp = (item.sortTimestamp ?? item.date ?? '').trim();
  if (stamp === '') {
    return 0;
  }

  const time = new Date(stamp).getTime();

  return Number.isFinite(time) ? time : 0;
}

/**
 * Trie les messages du plus récent au plus ancien (sortTimestamp, date puis id).
 *
 * @param items Liste à trier.
 * @returns Nouvelle liste triée.
 */
export function sortSermonsNewestFirst<T extends SortableSermon>(items: T[] | null | undefined): T[] {
  const safeItems = Array.isArray(items) ? items : [];

  return [...safeItems].sort((left, right) => {
    const leftTime = resolveSortTime(left);
    const rightTime = resolveSortTime(right);

    if (rightTime !== leftTime) {
      return rightTime - leftTime;
    }

    const leftId = Number(left.id);
    const rightId = Number(right.id);

    if (Number.isFinite(leftId) && Number.isFinite(rightId)) {
      return rightId - leftId;
    }

    return 0;
  });
}
