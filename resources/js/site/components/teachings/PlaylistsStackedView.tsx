import { useMemo } from 'react';
import { motion } from 'framer-motion';
import { Link } from 'react-router-dom';
import { Layers, Play } from 'lucide-react';
import type { Sermon } from '../../data/types';
import { groupPostsByEvent } from '../../lib/teachingsGrouping';
import ReactionBar from '../ui/ReactionBar';
import ImageWithSkeleton from '../ui/ImageWithSkeleton';
import { PlaylistStackSkeleton } from '../ui/Skeleton';
import InfiniteScrollFooter from './InfiniteScrollFooter';

const STACK_VISIBLE = 4;

/**
 * Carte empilée d'une publication : ouvre la lecture intégrée sur le site (pas d'onglet YouTube).
 */
function StackedPlaylistCard({
  item,
  cardIndex,
  stackSize,
  eventId,
}: {
  item: Sermon;
  cardIndex: number;
  stackSize: number;
  eventId: string;
}) {
  const tilt = (cardIndex - Math.floor(stackSize / 2)) * 2.5;
  const inner = (
    <>
      <ImageWithSkeleton
        src={item.thumbnail}
        alt={item.title}
        className="absolute inset-0 h-full w-full object-cover"
      />
      <motion.div className="absolute inset-0 bg-gradient-to-t from-black/75 via-black/20 to-transparent" />
      <motion.div className="absolute bottom-0 left-0 right-0 p-4">
        <p className="line-clamp-2 font-heading text-base font-bold text-white">{item.title}</p>
        <p className="mt-1 text-xs text-white/80">{item.speaker}</p>
      </motion.div>
      <motion.div className="absolute right-3 top-3 flex h-9 w-9 items-center justify-center rounded-full bg-white/20 backdrop-blur-sm">
        <Play className="ml-0.5 h-4 w-4 text-white" />
      </motion.div>
    </>
  );

  const className =
    'absolute inset-x-0 h-40 overflow-hidden rounded-2xl border border-white/20 bg-surface-900 shadow-lg ring-1 ring-black/10';

  const to = `/teachings/playlist/${eventId}?post=${encodeURIComponent(item.id)}`;

  return (
    <Link
      to={to}
      className={className}
      style={{
        top: cardIndex * 14,
        zIndex: stackSize - cardIndex,
        transform: `rotate(${tilt}deg)`,
      }}
      aria-label={`Lire : ${item.title}`}
    >
      {inner}
    </Link>
  );
}

/**
 * Playlists regroupées par événement avec cartes empilées.
 */
export default function PlaylistsStackedView({
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
  const groups = useMemo(() => groupPostsByEvent(items), [items]);

  if (loading) {
    return <PlaylistStackSkeleton />;
  }

  if (error) {
    return <p className="text-center text-burgundy-600">{error}</p>;
  }

  if (items.length === 0) {
    return (
      <p className="text-center text-surface-500">
        Aucune playlist publiée. Associez des messages à un événement dans l&apos;administration.
      </p>
    );
  }

  return (
    <>
      <motion.div className="space-y-16">
        {groups.map((group, groupIndex) => {
          const stackItems = group.items.slice(0, STACK_VISIBLE);
          const stackHeight = 160 + Math.max(0, stackItems.length - 1) * 14;

          return (
            <section key={group.eventId}>
              <motion.div className="mb-6 flex items-center gap-3">
                <motion.div className="flex h-11 w-11 items-center justify-center rounded-xl bg-burgundy-100 text-burgundy-700">
                  <Layers className="h-5 w-5" />
                </motion.div>
                <motion.div>
                  <h2 className="font-heading text-2xl font-bold text-surface-900">{group.eventTitle}</h2>
                  <p className="text-sm text-surface-500">
                    {group.items.length} message{group.items.length > 1 ? 's' : ''}
                  </p>
                </motion.div>
              </motion.div>

              {group.eventImage ? (
                <motion.div className="relative mb-6 max-h-36 overflow-hidden rounded-2xl border border-surface-200 sm:max-h-44">
                  <ImageWithSkeleton
                    src={group.eventImage}
                    alt={group.eventTitle}
                    className="h-full max-h-[11rem] w-full object-cover sm:max-h-[13rem]"
                  />
                </motion.div>
              ) : null}

              <motion.div
                className="relative mx-auto max-w-md"
                style={{ minHeight: stackHeight }}
                initial={{ opacity: 0, y: 16 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: Math.min(groupIndex * 0.08, 0.35) }}
              >
                {stackItems.map((item, cardIndex) => (
                  <StackedPlaylistCard
                    key={item.id}
                    item={item}
                    cardIndex={cardIndex}
                    stackSize={stackItems.length}
                    eventId={group.eventId !== 'sans-evenement' ? group.eventId : String(item.eventId ?? item.id)}
                  />
                ))}
              </motion.div>

              {groupIndex === 0 ? (
                <div className="mt-8 flex justify-center border-t border-surface-200 pt-6">
                  <Link
                    to={`/teachings/playlist/${encodeURIComponent(
                      group.eventId !== 'sans-evenement' ? group.eventId : String(group.items[0]?.eventId ?? group.items[0]?.id ?? ''),
                    )}`}
                    className="inline-flex items-center gap-2 rounded-full bg-burgundy-900 px-6 py-3 text-sm font-semibold text-white transition hover:bg-burgundy-800"
                  >
                    Voir tous les messages de la playlist
                  </Link>
                </div>
              ) : group.items.length > STACK_VISIBLE ? (
                <ul className="mt-8 space-y-3 border-t border-surface-200 pt-6">
                  {group.items.slice(STACK_VISIBLE).map((item) => (
                    <li
                      key={item.id}
                      className="flex flex-col gap-2 rounded-xl border border-surface-200 bg-white p-4 sm:flex-row sm:items-center sm:justify-between"
                    >
                      <motion.div className="min-w-0">
                        <Link
                          to={`/teachings/playlist/${encodeURIComponent(group.eventId)}?post=${encodeURIComponent(item.id)}`}
                          className="font-semibold text-burgundy-700 hover:underline"
                        >
                          {item.title}
                        </Link>
                        <p className="text-xs text-surface-500">{item.speaker}</p>
                      </motion.div>
                      {item.reactableKey ? (
                        <ReactionBar reactableKey={item.reactableKey} compact />
                      ) : null}
                    </li>
                  ))}
                </ul>
              ) : (
                <motion.div className="mt-6 flex flex-wrap gap-3">
                  {group.items.map((item) =>
                    item.reactableKey ? (
                      <ReactionBar key={item.id} reactableKey={item.reactableKey} compact />
                    ) : null,
                  )}
                </motion.div>
              )}
            </section>
          );
        })}
      </motion.div>
      <InfiniteScrollFooter hasMore={hasMore} loadingMore={loadingMore} onLoadMore={onLoadMore} />
    </>
  );
}
