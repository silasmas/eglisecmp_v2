import YoutubePlaylistGrid from './YoutubePlaylistGrid';
import { useTeachingsPlaylistGroups } from '../../hooks/useTeachingsPlaylistGroups';
import { PlaylistStackSkeleton } from '../ui/Skeleton';

/**
 * Playlists : toutes les playlists YouTube (hors cultes hebdomadaires) en grille type YouTube.
 */
export default function PlaylistsStackedView() {
  const { groups, loading, error } = useTeachingsPlaylistGroups('playlists');

  if (loading) {
    return <PlaylistStackSkeleton />;
  }

  if (error) {
    return <p className="text-center text-burgundy-600">{error}</p>;
  }

  return (
    <YoutubePlaylistGrid
      groups={groups}
      emptyMessage="Aucune playlist synchronisée. La synchronisation YouTube crée automatiquement les playlists comme événements."
    />
  );
}
