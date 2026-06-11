import { useCallback, useEffect, useMemo, useState } from 'react';
import { ArrowLeft, ChevronRight, Clock, Search, Youtube } from 'lucide-react';
import { Link, useParams, useSearchParams } from 'react-router-dom';
import type { Sermon, TeachingsPlaylistGroup } from '../data/types';
import { fetchSitePlaylistDetail } from '../lib/siteApi';
import { readRememberedPlaylistOrigin } from '../lib/playlistOrigin';
import { formatPreachRowDate } from '../lib/preachRowDate';
import { resolvePlaylistBackNavigation } from '../lib/teachingsNavigation';
import CollapsibleRichText from '../components/ui/CollapsibleRichText';
import ReactionBar from '../components/ui/ReactionBar';
import ImageWithSkeleton from '../components/ui/ImageWithSkeleton';
import PageHero from '../components/ui/PageHero';
import InfiniteScrollFooter from '../components/teachings/InfiniteScrollFooter';
import LazyYoutubePlayer from '../components/teachings/LazyYoutubePlayer';
import { Skeleton } from '../components/ui/Skeleton';
import { useInfinitePlaylistPosts } from '../hooks/useInfinitePlaylistPosts';

/**
 * Page de lecture d'une playlist (lecteur différé, liste paginée légère).
 */
export default function PlaylistWatchPage() {
  const { eventId } = useParams<{ eventId: string }>();
  const [searchParams, setSearchParams] = useSearchParams();
  const [playlistMeta, setPlaylistMeta] = useState<TeachingsPlaylistGroup | null>(null);
  const [metaLoading, setMetaLoading] = useState(true);
  const [metaError, setMetaError] = useState<string | null>(null);
  const [searchInput, setSearchInput] = useState('');
  const [debouncedSearch, setDebouncedSearch] = useState('');

  const selectedPostId = searchParams.get('post') ?? '';
  const autoplayRequested = searchParams.get('autoplay') === '1';
  const fromMeditations = searchParams.get('from') === 'meditations';
  const weeklyDayParam = (searchParams.get('weeklyDay') ?? '').trim();

  useEffect(() => {
    const timer = window.setTimeout(() => {
      setDebouncedSearch(searchInput.trim());
    }, 350);

    return () => window.clearTimeout(timer);
  }, [searchInput]);

  useEffect(() => {
    if (!eventId) {
      return;
    }

    let cancelled = false;

    async function loadMeta() {
      try {
        setMetaLoading(true);
        setMetaError(null);
        const detail = await fetchSitePlaylistDetail(eventId);
        if (!cancelled) {
          setPlaylistMeta(detail);
        }
      } catch (err) {
        if (!cancelled) {
          setPlaylistMeta(null);
          setMetaError(err instanceof Error ? err.message : 'Impossible de charger la playlist.');
        }
      } finally {
        if (!cancelled) {
          setMetaLoading(false);
        }
      }
    }

    void loadMeta();

    return () => {
      cancelled = true;
    };
  }, [eventId]);

  const weeklyDay = weeklyDayParam || (playlistMeta?.weeklyServiceDay ?? '').trim();
  const postsMode = fromMeditations && weeklyDay !== '' ? 'meditation' : 'playlist';

  const { items, loading: listLoading, loadingMore, error: listError, hasMore, loadMore } =
    useInfinitePlaylistPosts({
      eventId,
      mode: postsMode,
      weeklyDay,
      searchQuery: debouncedSearch,
    });

  const sidebarItems = items;

  const currentIndex = useMemo(() => {
    if (sidebarItems.length === 0) {
      return 0;
    }

    if (selectedPostId) {
      const found = sidebarItems.findIndex((item) => item.id === selectedPostId);
      if (found >= 0) {
        return found;
      }
    }

    return 0;
  }, [sidebarItems, selectedPostId]);

  const current = sidebarItems[currentIndex] ?? null;

  const eventTitle = playlistMeta?.title?.trim() || current?.eventTitle?.trim() || 'Playlist';
  const youtubePlaylistId = playlistMeta?.youtubePlaylistId ?? null;
  const youtubePlaylistEmbed =
    youtubePlaylistId !== null && youtubePlaylistId.trim() !== ''
      ? `https://www.youtube.com/embed/videoseries?list=${encodeURIComponent(youtubePlaylistId)}`
      : '';

  const playlistBack = useMemo(() => {
    const fromQuery = searchParams.get('from');
    const fromStored = readRememberedPlaylistOrigin();
    return resolvePlaylistBackNavigation(fromQuery ?? fromStored);
  }, [searchParams]);

  useEffect(() => {
    if (listLoading || sidebarItems.length === 0) {
      return;
    }

    const firstId = sidebarItems[0]?.id;
    const currentVisible = sidebarItems.some((item) => item.id === selectedPostId);
    const shouldSetInitial = !currentVisible && typeof firstId === 'string';

    if (shouldSetInitial) {
      setSearchParams(
        (previous) => {
          const next = new URLSearchParams(previous);
          next.set('post', firstId);
          return next;
        },
        { replace: true },
      );
    }
  }, [sidebarItems, listLoading, selectedPostId, setSearchParams]);

  const selectItem = useCallback(
    (index: number) => {
      const id = sidebarItems[index]?.id;
      if (typeof id === 'string') {
        setSearchParams(
          (previous) => {
            const next = new URLSearchParams(previous);
            next.set('post', id);
            return next;
          },
          { replace: false },
        );
      }
    },
    [sidebarItems, setSearchParams],
  );

  const goNext = useCallback(() => {
    if (sidebarItems.length <= 1) {
      return;
    }
    const next = Math.min(sidebarItems.length - 1, currentIndex + 1);
    selectItem(next);
  }, [sidebarItems.length, currentIndex, selectItem]);

  const sidebarHeading = fromMeditations ? 'Autres méditations' : 'Liste';
  const pageBusy = metaLoading && listLoading;
  const showEmptyYoutubeFallback =
    !pageBusy && sidebarItems.length === 0 && !metaError && !listError && youtubePlaylistEmbed !== '';

  return (
    <>
      <PageHero
        badge="Playlist"
        title={metaLoading ? 'Chargement…' : eventTitle}
        description="Regardez les messages sur le site, puis passez au suivant depuis la liste ou les boutons ci-dessous."
        compact
      />

      <section className="mx-auto max-w-7xl px-4 pb-24 pt-12 sm:px-6 lg:px-8">
        <Link
          to={playlistBack.href}
          className="mb-10 inline-flex items-center gap-2 rounded-full border border-surface-200 bg-white px-4 py-2 text-sm font-semibold text-surface-800 shadow-sm transition hover:border-burgundy-200 hover:bg-burgundy-50 hover:text-burgundy-900"
        >
          <ArrowLeft className="h-4 w-4" aria-hidden /> {playlistBack.label}
        </Link>

        {metaError ? <p className="text-center text-burgundy-600">{metaError}</p> : null}

        {pageBusy ? (
          <div className="grid gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(280px,400px)]" aria-busy="true">
            <div className="space-y-4">
              <Skeleton className="aspect-video rounded-2xl" />
              <Skeleton className="h-8 w-2/3" />
            </div>
            <div className="space-y-3">
              <Skeleton className="h-10 rounded-xl" />
              {Array.from({ length: 5 }).map((__, index) => (
                <Skeleton key={`playlist-skel-${String(index)}`} className="h-20 rounded-2xl" />
              ))}
            </div>
          </div>
        ) : null}

        {showEmptyYoutubeFallback ? (
          <div className="space-y-6">
            <div className="overflow-hidden rounded-2xl bg-black shadow-xl ring-1 ring-black/15">
              <LazyYoutubePlayer
                videoKey={`playlist-${youtubePlaylistId ?? eventId ?? 'fallback'}`}
                embedUrl={youtubePlaylistEmbed}
                title={eventTitle}
                autoplay={autoplayRequested}
              />
            </div>
            <p className="text-center text-sm text-surface-500">
              Lecture de la playlist YouTube. Les messages seront disponibles sur le site après la prochaine synchronisation.
            </p>
          </div>
        ) : null}

        {!pageBusy && sidebarItems.length === 0 && !metaError && !listError && youtubePlaylistEmbed === '' ? (
          <p className="text-center text-surface-500">Cette playlist ne contient aucun message pour le moment.</p>
        ) : null}

        {!pageBusy && (current || sidebarItems.length > 0) ? (
          <div className="grid gap-10 lg:grid-cols-[minmax(0,1fr)_minmax(280px,400px)] lg:gap-14">
            <div className="min-w-0 space-y-6">
              <div className="overflow-hidden rounded-2xl bg-black shadow-xl ring-1 ring-black/15">
                {current ? (
                  <LazyYoutubePlayer
                    videoKey={current.id}
                    embedUrl={current.youtubeEmbedUrl}
                    title={current.title}
                    thumbnail={current.thumbnail}
                    linkUrl={current.linkUrl}
                    autoplay={autoplayRequested}
                  />
                ) : (
                  <Skeleton className="aspect-video rounded-none" />
                )}
              </div>

              {current ? (
                <>
                  <div className="flex flex-wrap items-center gap-2">
                    <span className="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1 text-xs font-semibold text-surface-800 ring-1 ring-surface-200 dark:bg-surface-900 dark:text-white">
                      <Youtube className="h-3.5 w-3.5 text-red-600" aria-hidden />
                      YouTube
                    </span>
                    {current.duration ? (
                      <span className="inline-flex items-center gap-1 text-xs text-surface-500">
                        <Clock className="h-3.5 w-3.5" aria-hidden />
                        {current.duration}
                      </span>
                    ) : null}
                    {current.date ? (
                      <span className="text-xs text-surface-500">{formatPreachRowDate(current.date)}</span>
                    ) : null}
                  </div>

                  <header>
                    <h1 className="font-heading text-2xl font-bold text-surface-950 dark:text-white sm:text-3xl">{current.title}</h1>
                    <p className="mt-2 text-sm text-surface-500 dark:text-surface-400">{current.speaker}</p>
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
                      disabled={currentIndex >= sidebarItems.length - 1}
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
                </>
              ) : (
                <div className="space-y-3">
                  <Skeleton className="h-8 w-2/3" />
                  <Skeleton className="h-4 w-1/2" />
                </div>
              )}
            </div>

            <aside className="min-w-0 space-y-4">
              <div className="relative">
                <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-surface-400" />
                <input
                  type="search"
                  value={searchInput}
                  onChange={(event) => setSearchInput(event.target.value)}
                  placeholder={fromMeditations ? 'Rechercher une méditation…' : 'Rechercher dans la playlist…'}
                  className="w-full rounded-2xl border border-surface-200 bg-white py-3 pl-10 pr-3 text-sm text-surface-900 shadow-sm outline-none ring-burgundy-400/35 placeholder:text-surface-400 focus:border-burgundy-300 focus:ring-2 dark:border-surface-600 dark:bg-surface-950 dark:text-white"
                  aria-label="Recherche dans la liste"
                />
              </div>
              <h2 className="font-heading text-sm font-bold uppercase tracking-wider text-surface-400">{sidebarHeading}</h2>
              <div className="max-h-[min(70vh,820px)] space-y-2 overflow-auto pr-2">
                {listLoading && sidebarItems.length === 0 ? (
                  <p className="text-sm text-surface-500">Chargement de la liste…</p>
                ) : null}
                {listError ? <p className="text-sm text-burgundy-600">{listError}</p> : null}
                <ul className="space-y-2 pb-4">
                  {sidebarItems.map((item, index) => {
                    const selected = current !== null && item.id === current.id;

                    return (
                      <li key={item.id}>
                        <button
                          type="button"
                          onClick={() => selectItem(index)}
                          className={`flex w-full gap-3 rounded-2xl border p-3 text-left transition ${
                            selected
                              ? 'border-burgundy-400 bg-burgundy-50/80 ring-1 ring-burgundy-400/35 dark:bg-burgundy-950/30'
                              : 'border-surface-200 bg-white hover:border-surface-300 hover:bg-surface-50 dark:border-surface-700 dark:bg-surface-900 dark:hover:bg-surface-800'
                          }`}
                        >
                          <span className="relative h-16 w-16 shrink-0 overflow-hidden rounded-xl">
                            <ImageWithSkeleton
                              src={item.thumbnail}
                              alt=""
                              className="absolute inset-0 h-full w-full object-cover"
                            />
                          </span>
                          <div className="min-w-0 flex-1">
                            <div className="mb-1 flex items-center gap-2">
                              <span className="inline-block h-1.5 w-1.5 rounded-full bg-gold-500" />
                              <span className="text-[11px] font-semibold uppercase tracking-wide text-orange-700 dark:text-orange-400">
                                {formatPreachRowDate(item.date)}
                              </span>
                            </div>
                            <p className="line-clamp-2 text-sm font-semibold text-surface-900 dark:text-white">{item.title}</p>
                            <div className="mt-1 flex flex-wrap items-center gap-2">
                              <p className="truncate text-xs text-surface-500 dark:text-surface-400">{item.speaker}</p>
                              {item.duration ? (
                                <span className="inline-flex shrink-0 items-center gap-0.5 text-[10px] text-surface-500">
                                  <Clock className="h-3 w-3" aria-hidden /> {item.duration}
                                </span>
                              ) : null}
                            </div>
                          </div>
                        </button>
                      </li>
                    );
                  })}
                </ul>
                {!listLoading && sidebarItems.length === 0 && !listError ? (
                  <p className="py-8 text-center text-sm text-surface-500">Aucun résultat. Essayez une autre requête.</p>
                ) : null}
                <InfiniteScrollFooter hasMore={hasMore} loadingMore={loadingMore} onLoadMore={loadMore} />
              </div>
            </aside>
          </div>
        ) : null}
      </section>
    </>
  );
}
