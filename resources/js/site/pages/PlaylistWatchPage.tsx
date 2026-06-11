import { useCallback, useEffect, useMemo, useState } from 'react';
import { ArrowLeft, ChevronRight, Clock, Search, Youtube } from 'lucide-react';
import { Link, useParams, useSearchParams } from 'react-router-dom';
import type { Sermon, TeachingsPlaylistGroup } from '../data/types';
import {
  fetchSiteMeditationPostsByDay,
  fetchSitePlaylistDetail,
  fetchSitePlaylistPosts,
} from '../lib/siteApi';
import { readRememberedPlaylistOrigin } from '../lib/playlistOrigin';
import { formatPreachRowDate } from '../lib/preachRowDate';
import { sortSermonsNewestFirst } from '../lib/sermonSort';
import { resolvePlaylistBackNavigation } from '../lib/teachingsNavigation';
import { withEmbedPlaybackParams } from '../lib/youtubeEmbed';
import CollapsibleRichText from '../components/ui/CollapsibleRichText';
import ReactionBar from '../components/ui/ReactionBar';
import ImageWithSkeleton from '../components/ui/ImageWithSkeleton';
import PageHero from '../components/ui/PageHero';

/**
 * Filtre une liste de messages selon une requête texte (titre, orateur, date).
 *
 * @param items Messages triés.
 * @param query Texte de recherche.
 */
function filterSermonsByQuery(items: Sermon[], query: string): Sermon[] {
  const token = query.trim().toLowerCase();

  if (token === '') {
    return items;
  }

  return items.filter((item) => {
    const haystack = [item.title, item.speaker, item.date, item.eventTitle ?? '']
      .join(' ')
      .toLowerCase();

    return haystack.includes(token);
  });
}

/**
 * Page de lecture d'une playlist sur le site (lecteur embed, texte du message, navigation).
 */
export default function PlaylistWatchPage() {
  const { eventId } = useParams<{ eventId: string }>();
  const [searchParams, setSearchParams] = useSearchParams();
  const [items, setItems] = useState<Sermon[]>([]);
  const [playlistMeta, setPlaylistMeta] = useState<TeachingsPlaylistGroup | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [searchInput, setSearchInput] = useState('');

  const selectedPostId = searchParams.get('post') ?? '';
  const autoplayRequested = searchParams.get('autoplay') === '1';
  const fromMeditations = searchParams.get('from') === 'meditations';
  const weeklyDayParam = (searchParams.get('weeklyDay') ?? '').trim();

  useEffect(() => {
    if (!eventId) {
      return;
    }

    let cancelled = false;

    async function load() {
      try {
        setLoading(true);
        setError(null);

        const [detail, data] = await (async () => {
          const loadedDetail = await fetchSitePlaylistDetail(eventId);
          const weeklyDay = weeklyDayParam || (loadedDetail.weeklyServiceDay ?? '').trim();
          const loadedPosts =
            fromMeditations && weeklyDay !== ''
              ? await fetchSiteMeditationPostsByDay(weeklyDay)
              : await fetchSitePlaylistPosts(eventId, {
                  weeklyServiceDay: weeklyDay !== '' ? weeklyDay : undefined,
                });

          return [loadedDetail, loadedPosts] as const;
        })();

        if (cancelled) {
          return;
        }

        setPlaylistMeta(detail);
        const merged = data.length > 0 ? data : detail.items ?? [];
        setItems(sortSermonsNewestFirst(merged));
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
  }, [eventId, fromMeditations, weeklyDayParam]);

  const sidebarItems = useMemo(() => filterSermonsByQuery(items, searchInput), [items, searchInput]);

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

  const current = sidebarItems[currentIndex] ?? items.find((item) => item.id === selectedPostId) ?? null;

  const iframeSrc = useMemo(
    () => withEmbedPlaybackParams(current?.youtubeEmbedUrl, autoplayRequested),
    [current?.youtubeEmbedUrl, autoplayRequested],
  );

  const eventTitle = playlistMeta?.title?.trim() || items[0]?.eventTitle?.trim() || 'Playlist';
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
    if (loading || sidebarItems.length === 0) {
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
  }, [sidebarItems, loading, selectedPostId, setSearchParams]);

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
          to={playlistBack.href}
          className="mb-10 inline-flex items-center gap-2 rounded-full border border-surface-200 bg-white px-4 py-2 text-sm font-semibold text-surface-800 shadow-sm transition hover:border-burgundy-200 hover:bg-burgundy-50 hover:text-burgundy-900"
        >
          <ArrowLeft className="h-4 w-4" aria-hidden /> {playlistBack.label}
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

        {!loading && items.length === 0 && !error && youtubePlaylistEmbed !== '' ? (
          <div className="space-y-6">
            <div className="overflow-hidden rounded-2xl bg-black shadow-xl ring-1 ring-black/15">
              <div className="aspect-video">
                <iframe
                  key={youtubePlaylistEmbed}
                  src={youtubePlaylistEmbed}
                  title={`Playlist YouTube : ${eventTitle}`}
                  className="h-full w-full border-0"
                  allowFullScreen
                  allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                />
              </div>
            </div>
            <p className="text-center text-sm text-surface-500">
              Lecture de la playlist YouTube. Les messages seront disponibles sur le site après la prochaine synchronisation.
            </p>
          </div>
        ) : null}

        {!loading && items.length === 0 && !error && youtubePlaylistEmbed === '' ? (
          <p className="text-center text-surface-500">Cette playlist ne contient aucun message pour le moment.</p>
        ) : null}

        {!loading && current ? (
          <div className="grid gap-10 lg:grid-cols-[minmax(0,1fr)_minmax(280px,400px)] lg:gap-14">
            <div className="min-w-0 space-y-6">
              <div className="overflow-hidden rounded-2xl bg-black shadow-xl ring-1 ring-black/15">
                {iframeSrc !== '' ? (
                  <div className="aspect-video">
                    <iframe
                      key={current.id}
                      src={iframeSrc}
                      title={`Lecture vidéo : ${current.title}`}
                      className="h-full w-full border-0"
                      allowFullScreen
                      allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
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
                      {current.linkUrl ? (
                        <a
                          href={current.linkUrl}
                          target="_blank"
                          rel="noopener noreferrer"
                          className="mt-2 rounded-lg bg-white/90 px-4 py-2 text-sm font-semibold text-surface-900 hover:bg-white"
                        >
                          Ouvrir sur YouTube
                        </a>
                      ) : null}
                    </div>
                  </div>
                )}
              </div>

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
              <ul className="max-h-[min(70vh,820px)] space-y-2 overflow-auto pr-2">
                {sidebarItems.map((item, index) => {
                  const selected = item.id === current.id;

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
              {!loading && sidebarItems.length === 0 ? (
                <p className="py-8 text-center text-sm text-surface-500 dark:text-surface-400">
                  Aucun résultat. Essayez une autre requête.
                </p>
              ) : null}
            </aside>
          </div>
        ) : null}
      </section>
    </>
  );
}
