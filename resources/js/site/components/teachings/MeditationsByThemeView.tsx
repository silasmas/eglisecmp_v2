import YoutubePlaylistGrid from './YoutubePlaylistGrid';
import { useTeachingsPlaylistGroups } from '../../hooks/useTeachingsPlaylistGroups';
import { PlaylistStackSkeleton } from '../ui/Skeleton';

/**
 * Méditations : cultes hebdomadaires (playlists YouTube configurées) en grille type YouTube.
 */
export default function MeditationsByThemeView() {
  const { groups, loading, error } = useTeachingsPlaylistGroups('meditations');

  if (loading) {
    return <PlaylistStackSkeleton />;
  }

  if (error) {
    return <p className="text-center text-burgundy-600">{error}</p>;
  }

  return (
    <YoutubePlaylistGrid
      groups={groups}
      fromTab="meditations"
      emptyMessage="Aucun culte hebdomadaire synchronisé. Lancez la synchronisation YouTube et vérifiez que les playlists « Culte d'enseignement », « Culte de jeudi etoko » et « Cultes dominicaux » existent sur la chaîne."
    />
  );
}
