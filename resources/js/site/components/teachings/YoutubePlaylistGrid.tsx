import { Link } from 'react-router-dom';
import { ListVideo } from 'lucide-react';
import type { TeachingsPlaylistGroup } from '../../data/types';
import ImageWithSkeleton from '../ui/ImageWithSkeleton';
import '../../styles/youtube-playlist-grid.css';

type YoutubePlaylistGridProps = {
  groups: TeachingsPlaylistGroup[];
  emptyMessage: string;
};

/**
 * Grille de playlists style YouTube (vignette empilée, compteur, titre).
 */
export default function YoutubePlaylistGrid({ groups, emptyMessage }: YoutubePlaylistGridProps) {
  if (groups.length === 0) {
    return <p className="text-center text-surface-500">{emptyMessage}</p>;
  }

  return (
    <div className="yt-playlist-grid">
      {groups.map((group) => {
        const href =
          group.href !== undefined && group.href.trim() !== ''
            ? group.href
            : group.eventId !== ''
              ? `/teachings/playlist/${encodeURIComponent(group.eventId)}`
              : '/teachings?tab=playlists';

        return (
          <article key={group.eventId || group.title} className="yt-playlist-card">
            <p className="yt-playlist-count-above">
              {group.videoCount} vidéo{group.videoCount > 1 ? 's' : ''}
            </p>
            <Link to={href} className="yt-playlist-thumb-link" aria-label={`Voir la playlist ${group.title}`}>
              <div className="yt-playlist-stack">
                <span className="yt-playlist-stack-layer yt-playlist-stack-layer--2" aria-hidden />
                <span className="yt-playlist-stack-layer yt-playlist-stack-layer--1" aria-hidden />
                <div className="yt-playlist-stack-front">
                  <ImageWithSkeleton
                    src={group.thumbnail}
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
              <Link to={href} className="yt-playlist-title line-clamp-2">
                {group.title}
              </Link>
              <p className="yt-playlist-visibility">{group.visibility}</p>
              {group.description !== '' ? (
                <p className="yt-playlist-desc line-clamp-2">{group.description}</p>
              ) : null}
              <Link to={href} className="yt-playlist-action">
                Afficher la playlist complète
              </Link>
            </div>
          </article>
        );
      })}
    </div>
  );
}
