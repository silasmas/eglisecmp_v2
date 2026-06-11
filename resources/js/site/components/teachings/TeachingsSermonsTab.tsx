import { useEffect } from 'react';
import MessagesGridView from './MessagesGridView';
import { useInfiniteSitePosts } from '../../hooks/useInfiniteSitePosts';
import { prefetchImageUrls } from '../../lib/imagePrefetch';

/**
 * Onglet Messages : chargement paginé des publications (sermons uniquement).
 */
export default function TeachingsSermonsTab() {
  const { items, loading, loadingMore, error, hasMore, loadMore } = useInfiniteSitePosts('sermons');

  useEffect(() => {
    prefetchImageUrls(
      items.flatMap((item) => [item.thumbnail, item.eventImage]),
      96,
    );
  }, [items]);

  return (
    <MessagesGridView
      items={items}
      loading={loading}
      loadingMore={loadingMore}
      hasMore={hasMore}
      error={error}
      onLoadMore={loadMore}
    />
  );
}
