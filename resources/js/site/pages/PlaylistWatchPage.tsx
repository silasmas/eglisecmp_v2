import { useCallback, useEffect, useMemo, useState } from 'react';
import { ArrowLeft, ChevronRight, Youtube } from 'lucide-react';
import { Link, useLocation, useParams, useSearchParams } from 'react-router-dom';
import type { Sermon } from '../data/types';
import { fetchSitePlaylistPosts } from '../lib/siteApi';
import CollapsibleRichText from '../components/ui/CollapsibleRichText';
import ReactionBar from '../components/ui/ReactionBar';
import ImageWithSkeleton from '../components/ui/ImageWithSkeleton';
import PageHero from '../components/ui/PageHero';
import SocialShareToolbar from '../components/ui/SocialShareToolbar';

/**
 * Page de lecture d'une playlist sur le site (lecteur embed, texte du message, navigation).
 */
export default function PlaylistWatchPage() {
  const { eventId } = useParams<{ eventId: string }>();
  const location = useLocation();
  const [searchParams, setSearchParams] = useSearchParams();
  const [items, setItems] = useState<Sermon[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const selectedPostId = searchParams.get('post') ?? '';
  const autoplayRequested = searchParams.get('autoplay') === '1';

  useEffect(() => {
    if (!eventId) {
      return;
    }

    let cancelled = false;

    async function load() {
      try {
        setLoading(true);
        setError(null);
        const data = await fetchSitePlaylistPosts(eventId);
        if (cancelled) {
          return;
        }
        setItems(data);
      } catch (err) {
        if (!cancelled) {
          setItems([]);
          setError(err instanceof Error ? err.message : 'Impossible de charger la playlist.');
        }
      } finally {
        if (!cancelled) {
          setLoading(false);
        }
      }
    }

    void load();

    return () => {
      cancelled = true;
    };
  }, [eventId]);

  const currentIndex = useMemo(() => {
    if (items.length === 0) {
      return 0;
    }
    if (selectedPostId) {
      const found = items.findIndex((item) => item.id === selectedPostId);
      if (found >= 0) {
        return found;
      }
    }
    return 0;
  }, [items, selectedPostId]);

  const current = items[currentIndex] ?? null;

  const iframeSrc = useMemo(() => {
    const url = current?.youtubeEmbedUrl;
    if (!url) {
      return '';
    }

    if (!autoplayRequested) {
      return url;
    }

    const sep = url.includes('?') ? '&' : '?';

    return `${url}${sep}autoplay=1&mute=1&playsinline=1`;
  }, [current?.youtubeEmbedUrl, autoplayRequested]);

  const eventTitle = items[0]?.eventTitle?.trim() || 'Playlist';
  const playlistBackHref = '/teachings?tab=playlists';

  useEffect(() => {
    if (loading || items.length === 0) {
      return;
    }

    const firstId = items[0]?.id;
    const shouldSetInitial = selectedPostId === '' && typeof firstId === 'string';

    if (shouldSetInitial) {
      setSearchParams({ post: firstId }, { replace: true });
    }
  }, [items, loading, selectedPostId, setSearchParams]);

  const selectItem = useCallback(
    (index: number) => {
      const id = items[index]?.id;
      if (typeof id === 'string') {
        setSearchParams({ post: id }, { replace: false });
      }
    },
    [items, setSearchParams],
  );

  const goNext = useCallback(() => {
    if (items.length <= 1) {
      return;
    }
    const next = Math.min(items.length - 1, currentIndex + 1);
    selectItem(next);
  }, [items.length, currentIndex, selectItem]);

  return (
    <>
      <PageHero
        badge="Playlist"
        title={eventTitle}
        description="Regardez les messages sur le site, puis passez au suivant depuis la liste ou les boutons ci-dessous."
        compact
      />

      <section className="mx-auto max-w-7xl px-4 pb-24 pt-12 sm:px-6 lg:px-8">
        <Link
          to={playlistBackHref}
          className="mb-10 inline-flex items-center gap-2 rounded-full border border-surface-200 bg-white px-4 py-2 text-sm font-semibold text-surface-800 shadow-sm transition hover:border-burgundy-200 hover:bg-burgundy-50 hover:text-burgundy-900"
        >
          <ArrowLeft className="h-4 w-4" aria-hidden /> Retour aux playlists
        </Link>

        {loading ? (
          <div className="grid gap-8 lg:grid-cols-[minmax(0,1fr)_320px]" aria-busy="true">
            <div className="space-y-4">
              <div className="aspect-video animate-pulse rounded-2xl bg-surface-200" />
              <div className="h-8 w-2/3 animate-pulse rounded bg-surface-200" />
            </div>
            <div className="space-y-3">
              {Array.from({ length: 5 }).map((__, index) => (
                <div key={`playlist-skel-${String(index)}`} className="flex gap-3 rounded-2xl border border-surface-100 p-3">
                  <div className="h-16 w-16 shrink-0 animate-pulse rounded-xl bg-surface-200" />
                  <div className="flex-1 space-y-2">
                    <div className="h-4 animate-pulse rounded bg-surface-200" />
                    <div className="h-3 w-5/6 animate-pulse rounded bg-surface-200" />
                  </div>
                </div>
              ))}
            </div>
          </div>
        ) : null}

        {error ? <p className="text-center text-burgundy-600">{error}</p> : null}

        {!loading && items.length === 0 && !error ? (
          <p className="text-center text-surface-500">Cette playlist ne contient aucun message pour le moment.</p>
        ) : null}

        {!loading && current ? (
          <div className="grid gap-10 lg:grid-cols-[minmax(0,1fr)_320px] lg:gap-14">
            <div className="min-w-0 space-y-6">
              <div className="overflow-hidden rounded-2xl bg-black shadow-xl ring-1 ring-black/15">
                {current.youtubeEmbedUrl ? (
                  <div className="aspect-video">
                    <iframe
                      src={iframeSrc}
                      title={`Lecture vidéo : ${current.title}`}
                      className="h-full w-full border-0"
                      allowFullScreen
                      allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    />
                  </div>
                ) : (
                  <div className="relative aspect-video">
                    <ImageWithSkeleton
                      src={current.thumbnail}
                      alt=""
                      className="absolute inset-0 h-full w-full object-cover opacity-75"
                    />
                    <div className="absolute inset-0 flex flex-col items-center justify-center gap-2 bg-black/60 p-4 text-center">
                      <p className="text-sm font-semibold text-white">
                        Vidéo indisponible en lecture intégrée pour ce message (aucun lien YouTube valide renseigné).
                      </p>
                    </div>
                  </div>
                )}
              </div>

              <div className="flex flex-wrap items-center gap-2">
                <span className="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1 text-xs font-semibold text-surface-800 ring-1 ring-surface-200 dark:bg-surface-900 dark:text-white">
                  <Youtube className="h-3.5 w-3.5 text-red-600" aria-hidden />
                  YouTube
                </span>
                {current.date ? (
                  <span className="text-xs text-surface-500">{new Date(current.date).toLocaleDateString('fr-FR')}</span>
                ) : null}
              </div>

              <header>
                <h1 className="font-heading text-2xl font-bold text-surface-950 dark:text-white sm:text-3xl">{current.title}</h1>
                <p className="mt-2 text-sm text-surface-500 dark:text-surface-400">{current.speaker}</p>
                <div className="mt-4">
                  <SocialShareToolbar
                    title={current.title}
                    description={current.description}
                    sharePath={`${location.pathname}${location.search}`}
                    compact
                  />
                </div>
              </header>

              {current.reactableKey ? (
                <div className="rounded-2xl border border-surface-200 bg-surface-50 p-4">
                  <ReactionBar reactableKey={current.reactableKey} compact={false} />
                </div>
              ) : null}

              <div className="flex flex-wrap gap-3">
                <button
                  type="button"
                  onClick={() => selectItem(Math.max(0, currentIndex - 1))}
                  disabled={currentIndex <= 0}
                  className="rounded-xl border border-surface-200 px-5 py-2.5 text-sm font-semibold text-surface-800 transition hover:bg-surface-100 disabled:opacity-35"
                >
                  Précédent
                </button>
                <button
                  type="button"
                  onClick={() => goNext()}
                  disabled={currentIndex >= items.length - 1}
                  className="inline-flex items-center gap-2 rounded-xl bg-burgundy-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-burgundy-800 disabled:opacity-35"
                >
                  Message suivant <ChevronRight className="h-4 w-4" aria-hidden />
                </button>
              </div>

              {current.bodyHtml && current.bodyHtml.trim() !== '' ? (
                <div className="rounded-3xl border border-surface-200 bg-white p-6 shadow-inner dark:border-surface-700 dark:bg-surface-900">
                  <h2 className="mb-4 font-heading text-lg font-semibold text-surface-950 dark:text-white">
                    La prédication en texte
                  </h2>
                  <CollapsibleRichText html={current.bodyHtml} collapsedMaxPx={288} />
                </div>
              ) : null}
            </div>

            <aside className="min-w-0 space-y-5">
              <h2 className="font-heading text-sm font-bold uppercase tracking-wider text-surface-400">Liste</h2>
              <ul className="max-h-[min(70vh,720px)] space-y-2 overflow-auto pr-2">
                {items.map((item, index) => {
                  const selected = index === currentIndex;

                  return (
                    <li key={item.id}>
                      <button
                        type="button"
                        onClick={() => selectItem(index)}
                        className={`flex w-full gap-3 rounded-2xl border p-3 text-left transition ${
                          selected
                            ? 'border-burgundy-400 bg-burgundy-50/80 ring-1 ring-burgundy-400/35'
                            : 'border-surface-200 bg-white hover:border-surface-300 hover:bg-surface-50'
                        }`}
                      >
                        <span className="relative h-16 w-16 shrink-0 overflow-hidden rounded-xl">
                          <ImageWithSkeleton
                            src={item.thumbnail}
                            alt=""
                            className="absolute inset-0 h-full w-full object-cover"
                          />
                        </span>
                        <div className="min-w-0">
                          <p className="text-[10px] font-bold uppercase tracking-wide text-orange-700">Vidéo</p>
                          <p className="line-clamp-2 text-sm font-semibold text-surface-900">{item.title}</p>
                          <p className="truncate text-xs text-surface-500">{item.speaker}</p>
                        </div>
                      </button>
                    </li>
                  );
                })}
              </ul>
            </aside>
          </div>
        ) : null}
      </section>
    </>
  );
}
