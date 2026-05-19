import { useInfiniteScrollSentinel } from '../../hooks/useInfiniteScrollSentinel';

/**
 * Sentinelle de scroll infini + indicateur de chargement.
 */
export default function InfiniteScrollFooter({
  hasMore,
  loadingMore,
  onLoadMore,
}: {
  hasMore: boolean;
  loadingMore: boolean;
  onLoadMore: () => void;
}) {
  const sentinelRef = useInfiniteScrollSentinel(onLoadMore, hasMore && !loadingMore);

  return (
    <div ref={sentinelRef} className="mt-10 flex flex-col items-center gap-3 py-6">
      {loadingMore ? (
        <p className="text-sm text-surface-500">Chargement…</p>
      ) : null}
      {!hasMore && !loadingMore ? (
        <p className="text-sm text-surface-400">Vous avez tout parcouru.</p>
      ) : null}
    </div>
  );
}
