import { type ReactNode } from 'react';
import { Link } from 'react-router-dom';
import { ListVideo } from 'lucide-react';
import type { TeachingsPlaylistGroup } from '../../data/types';
import { rememberPlaylistOrigin } from '../../lib/playlistOrigin';
import {
  isExternalHref,
  resolvePlaylistGroupHref,
  resolvePlaylistVideoCount,
  safeTrimmedString,
} from '../../lib/playlistGridLinks';
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

type PlaylistGridLinkProps = {
  href: string;
  className: string;
  ariaLabel?: string;
  onNavigate: () => void;
  children: ReactNode;
};

/**
 * Lien interne (React Router) ou externe selon l’URL cible.
 */
function PlaylistGridLink({ href, className, ariaLabel, onNavigate, children }: PlaylistGridLinkProps) {
  if (isExternalHref(href)) {
    return (
      <a
        href={href}
        className={className}
        aria-label={ariaLabel}
        onClick={onNavigate}
        target="_blank"
        rel="noopener noreferrer"
      >
        {children}
      </a>
    );
  }

  return (
    <Link to={href} className={className} aria-label={ariaLabel} onClick={onNavigate}>
      {children}
    </Link>
  );
}

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
      {groups.map((group, index) => {
        if (group == null || typeof group !== 'object') {
          return null;
        }

        const eventId = safeTrimmedString(group.eventId);
        const groupTitle = safeTrimmedString(group.title) || 'Playlist';
        const videoCount = resolvePlaylistVideoCount(group.videoCount);
        const description = safeTrimmedString(group.description);
        const thumbnail = safeTrimmedString(group.thumbnail);

        const baseHref = resolvePlaylistGroupHref(group.href, eventId);
        const href = appendPlaylistFromParam(baseHref, fromTab);
        const previewSource =
          group.latestItem != null
            ? [group.latestItem]
            : Array.isArray(group.items) && group.items.length > 0
              ? group.items
              : [];
        const sortedItems = sortSermonsNewestFirst(previewSource);
        const newest = sortedItems[0];
        const stackDepth = Math.min(Math.max(videoCount, sortedItems.length), 3);
        const newestThumbnail = safeTrimmedString(newest?.thumbnail);
        const previewThumbnail = newestThumbnail !== '' ? newestThumbnail : thumbnail;

        return (
          <article key={eventId !== '' ? eventId : `${groupTitle}-${String(index)}`} className="yt-playlist-card">
            <p className="yt-playlist-count-above">
              {videoCount} vidéo{videoCount > 1 ? 's' : ''}
            </p>
            <PlaylistGridLink
              href={href}
              onNavigate={handleNavigate}
              className="yt-playlist-thumb-link"
              ariaLabel={`Voir la playlist ${groupTitle}`}
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
                    {videoCount} vidéo{videoCount > 1 ? 's' : ''}
                  </span>
                </div>
              </div>
            </PlaylistGridLink>
            <div className="yt-playlist-meta">
              <PlaylistGridLink href={href} onNavigate={handleNavigate} className="yt-playlist-title line-clamp-2">
                {groupTitle}
              </PlaylistGridLink>
              <p className="yt-playlist-visibility">{group.visibility ?? 'Publique'}</p>
              {newest?.title ? (
                <p className="yt-playlist-latest line-clamp-2">
                  Dernière vidéo&nbsp;: <span className="font-medium text-surface-800">{newest.title}</span>
                </p>
              ) : null}
              {description !== '' ? (
                <p className="yt-playlist-desc line-clamp-2">{description}</p>
              ) : null}
              <PlaylistGridLink href={href} onNavigate={handleNavigate} className="yt-playlist-action">
                Afficher la playlist complète
              </PlaylistGridLink>
            </div>
          </article>
        );
      })}
    </div>
  );
}
