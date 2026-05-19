import { useMemo } from 'react';
import { motion } from 'framer-motion';
import { Radio } from 'lucide-react';
import { Link } from 'react-router-dom';
import type { Sermon } from '../../data/types';
import { groupMeditationsByWeeklyProgram } from '../../lib/teachingsGrouping';
import ReactionBar from '../ui/ReactionBar';
import ImageWithSkeleton from '../ui/ImageWithSkeleton';
import { MeditationThemesSkeleton } from '../ui/Skeleton';
import InfiniteScrollFooter from './InfiniteScrollFooter';

export default function MeditationsByThemeView({
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
  const grouped = useMemo(() => groupMeditationsByWeeklyProgram(items), [items]);

  if (loading) {
    return <MeditationThemesSkeleton />;
  }

  if (error) {
    return <p className="text-center text-burgundy-600">{error}</p>;
  }

  if (items.length === 0) {
    return (
      <p className="text-center text-surface-500">
        Aucune méditation publiée. Indiquez le jour concerné (« Mercredi », « Jeudi » ou « Dimanche ») dans le champ « Référence », le titre ou l’introduction pour classer vos messages dans le programme hebdomadaire.
      </p>
    );
  }

  return (
    <>
      <motion.div className="space-y-14">
        {[...grouped.entries()]
          .filter(([, themeItems]) => themeItems.length > 0)
          .map(([programLabel, themeItems], sectionIndex) => (
          <section key={programLabel}>
            <h2 className="mb-5 font-heading text-2xl font-bold text-surface-900">{programLabel}</h2>
            <motion.div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
              {themeItems.map((item, index) => (
                <motion.article
                  key={item.id}
                  initial={{ opacity: 0, y: 12 }}
                  animate={{ opacity: 1, y: 0 }}
                  transition={{ delay: Math.min(sectionIndex * 0.05 + index * 0.03, 0.4) }}
                  className="flex gap-4 rounded-2xl border border-surface-200 bg-white p-4 shadow-sm"
                >
                  <motion.div className="relative h-20 w-20 shrink-0 overflow-hidden rounded-xl">
                    <ImageWithSkeleton src={item.thumbnail} alt="" className="absolute inset-0 h-full w-full object-cover" />
                    <motion.div className="absolute inset-0 flex items-center justify-center bg-black/25">
                      <Radio className="h-5 w-5 text-white" />
                    </motion.div>
                  </motion.div>
                  <motion.div className="min-w-0 flex-1">
                    <Link
                      to={`/teachings/message/${item.id}`}
                      className="line-clamp-2 font-heading text-lg font-semibold text-surface-900 transition-colors hover:text-burgundy-700"
                    >
                      {item.title}
                    </Link>
                    <p className="mt-1 text-xs text-surface-500">
                      {item.speaker}
                      {item.date
                        ? ` · ${new Date(item.date).toLocaleDateString('fr-FR', {
                            day: 'numeric',
                            month: 'long',
                            year: 'numeric',
                          })}`
                        : ''}
                    </p>
                    <p className="mt-2 line-clamp-2 text-sm text-surface-600">{item.description}</p>
                    <ReactionBar reactableKey={item.reactableKey} compact className="mt-3" />
                  </motion.div>
                </motion.article>
              ))}
            </motion.div>
          </section>
        ))}
      </motion.div>
      <InfiniteScrollFooter hasMore={hasMore} loadingMore={loadingMore} onLoadMore={onLoadMore} />
    </>
  );
}