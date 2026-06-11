import type { Sermon } from '../data/types';

type SortableSermon = Pick<Sermon, 'date' | 'id'>;

/**
 * Trie les messages du plus récent au plus ancien (date puis id décroissant).
 *
 * @param items Liste à trier.
 * @returns Nouvelle liste triée.
 */
export function sortSermonsNewestFirst<T extends SortableSermon>(items: T[]): T[] {
  return [...items].sort((left, right) => {
    const leftTime = left.date ? new Date(left.date).getTime() : 0;
    const rightTime = right.date ? new Date(right.date).getTime() : 0;

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
