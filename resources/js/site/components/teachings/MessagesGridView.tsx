import { motion } from 'framer-motion';
import SermonCard from '../cards/SermonCard';
import type { Sermon } from '../../data/types';
import { SermonGridSkeleton } from '../ui/Skeleton';
import InfiniteScrollFooter from './InfiniteScrollFooter';

/**
 * Grille de messages avec chargement infini.
 */
export default function MessagesGridView({
  items,
  loading,
  loadingMore,
  hasMore,
  error,
  onLoadMore,
}: {
  items: Sermon[];
  loading: boolean;
  loadingMore: boolean;
  hasMore: boolean;
  error: string | null;
  onLoadMore: () => void;
}) {
  if (loading) {
    return <SermonGridSkeleton count={9} />;
  }

  if (error) {
    return <p className="text-center text-burgundy-600">{error}</p>;
  }

  if (items.length === 0) {
    return (
      <p className="text-center text-surface-500">
        Aucun message publié pour le moment.
      </p>
    );
  }

  return (
    <>
      <motion.div
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3"
      >
        {items.map((sermon) => (
          <SermonCard key={sermon.id} sermon={sermon} />
        ))}
      </motion.div>
      <InfiniteScrollFooter hasMore={hasMore} loadingMore={loadingMore} onLoadMore={onLoadMore} />
    </>
  );
}
