import { Link } from 'react-router-dom';
import { ListVideo } from 'lucide-react';
import type { TeachingsPlaylistGroup } from '../../data/types';
import { rememberPlaylistOrigin } from '../../lib/playlistOrigin';
import { sortSermonsNewestFirst } from '../../lib/sermonSort';
import { appendPlaylistFromParam, type PlaylistOriginTab } from '../../lib/teachingsNavigation';
import ImageWithSkeleton from '../ui/ImageWithSkeleton';
import '../../styles/youtube-playlist-grid.css';

type YoutubePlaylistGridProps = {
  groups: TeachingsPlaylistGroup[];
  emptyMessage: string;
  /** Onglet ou page d’origine (bouton retour dynamique sur la lecture playlist). */
  fromTab?: PlaylistOriginTab;
};

/**
 * Grille de playlists style YouTube (vignettes empilées du plus récent au plus ancien).
 */
export default function YoutubePlaylistGrid({ groups, emptyMessage, fromTab }: YoutubePlaylistGridProps) {
  if (groups.length === 0) {
    return <p className="text-center text-surface-500">{emptyMessage}</p>;
  }

  const handleNavigate = (): void => {
    if (fromTab !== undefined) {
      rememberPlaylistOrigin(fromTab);
    }
  };

  return (
    <div className="yt-playlist-grid">
      {groups.map((group) => {
        const baseHref =
          group.href !== undefined && group.href.trim() !== ''
            ? group.href
            : group.eventId !== ''
              ? `/teachings/playlist/${encodeURIComponent(group.eventId)}`
              : '/teachings?tab=playlists';
        const href = appendPlaylistFromParam(baseHref, fromTab);
        const sortedItems = sortSermonsNewestFirst(group.items);
        const newest = sortedItems[0];
        const stackDepth = Math.min(sortedItems.length, 3);
        const previewThumbnail =
          newest?.thumbnail?.trim() !== '' ? newest.thumbnail : group.thumbnail;

        return (
          <article key={group.eventId || group.title} className="yt-playlist-card">
            <p className="yt-playlist-count-above">
              {group.videoCount} vidéo{group.videoCount > 1 ? 's' : ''}
            </p>
            <Link
              to={href}
              onClick={handleNavigate}
              className="yt-playlist-thumb-link"
              aria-label={`Voir la playlist ${group.title}`}
            >
              <div
                className="yt-playlist-stack"
                data-stack-depth={stackDepth > 1 ? String(stackDepth) : undefined}
              >
                <span className="yt-playlist-stack-layer yt-playlist-stack-layer--2" aria-hidden />
                <span className="yt-playlist-stack-layer yt-playlist-stack-layer--1" aria-hidden />
                <div className="yt-playlist-stack-front">
                  <ImageWithSkeleton
                    src={previewThumbnail}
                    alt=""
                    className="absolute inset-0 h-full w-full object-cover"
                  />
                  <span className="yt-playlist-count">
                    <ListVideo className="h-3.5 w-3.5" aria-hidden />
                    {group.videoCount} vidéo{group.videoCount > 1 ? 's' : ''}
                  </span>
                </div>
              </div>
            </Link>
            <div className="yt-playlist-meta">
              <Link to={href} onClick={handleNavigate} className="yt-playlist-title line-clamp-2">
                {group.title}
              </Link>
              <p className="yt-playlist-visibility">{group.visibility}</p>
              {newest?.title ? (
                <p className="yt-playlist-latest line-clamp-2">
                  Dernière vidéo&nbsp;: <span className="font-medium text-surface-800">{newest.title}</span>
                </p>
              ) : null}
              {group.description !== '' ? (
                <p className="yt-playlist-desc line-clamp-2">{group.description}</p>
              ) : null}
              <Link to={href} onClick={handleNavigate} className="yt-playlist-action">
                Afficher la playlist complète
              </Link>
            </div>
          </article>
        );
      })}
    </div>
  );
}
