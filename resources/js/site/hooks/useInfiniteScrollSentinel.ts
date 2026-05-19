import { useEffect, useRef } from 'react';

/**
 * Déclenche `onLoadMore` lorsque l'élément sentinelle entre dans le viewport.
 *
 * @param onLoadMore Callback de chargement de la page suivante.
 * @param enabled Active l'observation (ex. `hasMore && !loading`).
 */
export function useInfiniteScrollSentinel(onLoadMore: () => void, enabled: boolean) {
  const sentinelRef = useRef<HTMLDivElement | null>(null);

  useEffect(() => {
    if (!enabled) {
      return;
    }

    const node = sentinelRef.current;

    if (!node) {
      return;
    }

    const observer = new IntersectionObserver(
      (entries) => {
        if (entries[0]?.isIntersecting) {
          onLoadMore();
        }
      },
      { rootMargin: '240px' },
    );

    observer.observe(node);

    return () => observer.disconnect();
  }, [enabled, onLoadMore]);

  return sentinelRef;
}
