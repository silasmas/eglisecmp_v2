import { ChevronDown } from 'lucide-react';
import YoutubePlaylistGrid from './YoutubePlaylistGrid';
import { useTeachingsPlaylistsPaged } from '../../hooks/useTeachingsPlaylistsPaged';
import { PlaylistStackSkeleton } from '../ui/Skeleton';

const PLAYLISTS_PER_PAGE = 15;

/**
 * Playlists : chargement progressif (15 par page) avec bouton « Voir plus ».
 */
export default function PlaylistsStackedView() {
  const { groups, loading, loadingMore, error, hasMore, total, loadMore } =
    useTeachingsPlaylistsPaged(PLAYLISTS_PER_PAGE);

  if (loading) {
    return <PlaylistStackSkeleton />;
  }

  if (error && groups.length === 0) {
    return <p className="text-center text-burgundy-600">{error}</p>;
  }

  return (
    <div className="space-y-10">
      {error ? <p className="text-center text-sm text-burgundy-600">{error}</p> : null}

      <YoutubePlaylistGrid
        groups={groups}
        fromTab="playlists"
        emptyMessage="Aucune playlist synchronisée. La synchronisation YouTube crée automatiquement les playlists comme événements."
      />

      {groups.length > 0 && total > 0 ? (
        <p className="text-center text-sm text-surface-500">
          {groups.length} playlist{groups.length > 1 ? 's' : ''} affichée{groups.length > 1 ? 's' : ''}
          {total > groups.length ? ` sur ${total}` : ''}
        </p>
      ) : null}

      {hasMore ? (
        <div className="flex justify-center">
          <button
            type="button"
            onClick={() => void loadMore()}
            disabled={loadingMore}
            className="inline-flex items-center gap-2 rounded-xl border border-surface-300 bg-white px-6 py-3 text-sm font-semibold text-surface-800 shadow-sm transition hover:border-burgundy-200 hover:bg-burgundy-50 hover:text-burgundy-900 disabled:cursor-not-allowed disabled:opacity-60"
          >
            {loadingMore ? (
              'Chargement…'
            ) : (
              <>
                Voir plus de playlists
                <ChevronDown className="h-4 w-4" aria-hidden />
              </>
            )}
          </button>
        </div>
      ) : null}
    </div>
  );
}
