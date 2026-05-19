import { useCallback, useEffect, useMemo, useState } from 'react';
import { ArrowLeft, ChevronRight, Search, Youtube, Clock } from 'lucide-react';
import { Link, useNavigate, useParams, useSearchParams } from 'react-router-dom';
import type { Sermon } from '../data/types';
import { fetchSiteSermonById } from '../lib/siteApi';
import CollapsibleRichText from '../components/ui/CollapsibleRichText';
import SocialShareToolbar from '../components/ui/SocialShareToolbar';
import ReactionBar from '../components/ui/ReactionBar';
import ImageWithSkeleton from '../components/ui/ImageWithSkeleton';
import PageHero from '../components/ui/PageHero';
import InfiniteScrollFooter from '../components/teachings/InfiniteScrollFooter';
import { useInfiniteSitePosts } from '../hooks/useInfiniteSitePosts';

/**
 * Ajoute autoplay+mutes aux URLs d’iframe YouTube existantes renvoyées par l’API.
 *
 * @param embedUrl URL embed (avec query éventuelle).
 * @param autoplay Lance la lecture immédiate (navigateurs exigent souvent mute=1).
 * @returns URL prête pour l’iframe, ou chaîne vide.
 */
function withEmbedPlaybackParams(embedUrl: string | null | undefined, autoplay: boolean): string {
  if (!embedUrl) {
    return '';
  }

  const sep = embedUrl.includes('?') ? '&' : '?';

  return autoplay ? `${embedUrl}${sep}autoplay=1&mute=1&playsinline=1` : embedUrl;
}

/**
 * Formate une date de prédication (YYYY-MM-DD) pour une ligne de liste français.
 *
 * @param iso Chaîne courte date uniquement depuis l’API.
 */
function formatPreachRowDate(iso: string): string {
  if (!iso) {
    return '—';
  }

  try {
    return new Intl.DateTimeFormat('fr-FR', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    }).format(new Date(iso));
  } catch {
    return iso;
  }
}

/**
 * Page de lecture d’un message avec liste paginée infinie et recherche dans la colonne latérale.
 */
export default function MessageWatchPage() {
  const { postId } = useParams<{ postId: string }>();
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const autoplayRequested = searchParams.get('autoplay') === '1';

  const [current, setCurrent] = useState<Sermon | null>(null);
  const [currentLoading, setCurrentLoading] = useState(true);
  const [currentError, setCurrentError] = useState<string | null>(null);
  const [searchInput, setSearchInput] = useState('');
  const [debouncedSearch, setDebouncedSearch] = useState('');
  const { items, loading: listLoading, loadingMore, error: listError, hasMore, loadMore } =
    useInfiniteSitePosts('sermons', debouncedSearch);

  useEffect(() => {
    const timer = window.setTimeout(() => {
      setDebouncedSearch(searchInput.trim());
    }, 350);

    return () => window.clearTimeout(timer);
  }, [searchInput]);

  useEffect(() => {
    if (!postId) {
      return;
    }

    let cancelled = false;

    async function loadCurrent() {
      try {
        setCurrentLoading(true);
        setCurrentError(null);
        const row = await fetchSiteSermonById(postId);
        if (cancelled) {
          return;
        }
        setCurrent(row);
      } catch {
        if (!cancelled) {
          setCurrent(null);
          setCurrentError('Impossible de charger ce message.');
        }
      } finally {
        if (!cancelled) {
          setCurrentLoading(false);
        }
      }
    }

    void loadCurrent();

    return () => {
      cancelled = true;
    };
  }, [postId]);

  const playlistBackHref = '/teachings?tab=sermons';

  const sidebarItems = items;

  /** Index du message actuel dans la liste chargée (-1 si filtre / recherche l’exclut). */
  const currentIndex = useMemo(() => {
    if (!current) {
      return -1;
    }
    const found = sidebarItems.findIndex((item) => item.id === current.id);
    return found >= 0 ? found : -1;
  }, [sidebarItems, current]);

  /** Passe au message suivant dans la liste téléchargée, ou aucun si fin de liste filtrée. */
  const goNext = useCallback(() => {
    if (sidebarItems.length <= 1 || currentIndex < 0) {
      return;
    }
    const nextId = sidebarItems[currentIndex + 1]?.id;
    if (typeof nextId === 'string' && nextId !== '') {
      navigate(`/teachings/message/${nextId}`);
    }
  }, [sidebarItems, currentIndex, navigate]);

  /** Passe au message précédent dans la liste téléchargée. */
  const goPrev = useCallback(() => {
    if (sidebarItems.length <= 1 || currentIndex <= 0) {
      return;
    }
    const prevId = sidebarItems[currentIndex - 1]?.id;
    if (typeof prevId === 'string' && prevId !== '') {
      navigate(`/teachings/message/${prevId}`);
    }
  }, [sidebarItems, currentIndex, navigate]);

  return (
    <>
      <PageHero
        badge="Message"
        title={currentLoading ? 'Chargement…' : current?.title ?? 'Message'}
        description="Visionnez ce message puis explorez les autres avec la recherche et le défilement infini."
        compact
      />

      <section className="mx-auto max-w-7xl px-4 pb-24 pt-12 sm:px-6 lg:px-8">
        <Link
          to={playlistBackHref}
          className="mb-10 inline-flex items-center gap-2 rounded-full border border-surface-200 bg-white px-4 py-2 text-sm font-semibold text-surface-800 shadow-sm transition hover:border-burgundy-200 hover:bg-burgundy-50 hover:text-burgundy-900 dark:border-surface-600 dark:bg-surface-900 dark:text-white dark:hover:bg-burgundy-950/40"
        >
          <ArrowLeft className="h-4 w-4" aria-hidden /> Retour aux messages
        </Link>

        {currentLoading ? (
          <div className="grid gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(280px,400px)]" aria-busy="true">
            <div className="space-y-4">
              <div className="aspect-video animate-pulse rounded-2xl bg-surface-200 dark:bg-surface-800" />
              <div className="h-8 w-2/3 animate-pulse rounded bg-surface-200 dark:bg-surface-800" />
            </div>
            <div className="space-y-3">
              <div className="h-10 animate-pulse rounded-xl bg-surface-200 dark:bg-surface-800" />
              {Array.from({ length: 5 }).map((__, index) => (
                <div
                  key={`message-skel-${String(index)}`}
                  className="flex gap-3 rounded-2xl border border-surface-100 p-3 dark:border-surface-700"
                >
                  <div className="h-16 w-16 shrink-0 animate-pulse rounded-xl bg-surface-200 dark:bg-surface-800" />
                  <div className="flex-1 space-y-2">
                    <div className="h-4 animate-pulse rounded bg-surface-200 dark:bg-surface-800" />
                    <div className="h-3 w-5/6 animate-pulse rounded bg-surface-200 dark:bg-surface-800" />
                  </div>
                </div>
              ))}
            </div>
          </div>
        ) : null}

        {currentError ? <p className="text-center text-burgundy-600">{currentError}</p> : null}

        {!currentLoading && current ? (
          <div className="grid gap-10 lg:grid-cols-[minmax(0,1fr)_minmax(280px,400px)] lg:gap-14">
            <div className="min-w-0 space-y-6">
              <div className="overflow-hidden rounded-2xl bg-black shadow-xl ring-1 ring-black/15">
                {current.youtubeEmbedUrl ? (
                  <div className="aspect-video">
                    <iframe
                      src={withEmbedPlaybackParams(current.youtubeEmbedUrl, autoplayRequested)}
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
                <span className="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1 text-xs font-semibold text-surface-800 ring-1 ring-surface-200 dark:bg-surface-900 dark:text-white dark:ring-surface-600">
                  <Youtube className="h-3.5 w-3.5 text-red-600" aria-hidden />
                  YouTube
                </span>
                <span className="inline-flex items-center gap-1 text-xs text-surface-500">
                  <Clock className="h-3 w-3" aria-hidden /> {current.duration}
                </span>
                {current.date ? (
                  <span className="text-xs text-surface-500">{formatPreachRowDate(current.date)}</span>
                ) : null}
              </div>

              <header>
                <h1 className="font-heading text-2xl font-bold text-surface-950 dark:text-white sm:text-3xl">
                  {current.title}
                </h1>
                <p className="mt-2 text-sm text-surface-500 dark:text-surface-400">{current.speaker}</p>
                <div className="mt-4 max-w-full">
                  <SocialShareToolbar
                    title={current.title}
                    description={current.description}
                    sharePath={`/teachings/message/${current.id}`}
                    compact
                    menuStyle="spread"
                  />
                </div>
              </header>

              {current.reactableKey ? (
                <div className="rounded-2xl border border-surface-200 bg-surface-50 p-4 dark:border-surface-700 dark:bg-surface-900/40">
                  <ReactionBar reactableKey={current.reactableKey} compact={false} />
                </div>
              ) : null}

              <div className="flex flex-wrap gap-3">
                <button
                  type="button"
                  onClick={() => goPrev()}
                  disabled={currentIndex <= 0}
                  className="rounded-xl border border-surface-200 px-5 py-2.5 text-sm font-semibold text-surface-800 transition hover:bg-surface-100 disabled:opacity-35 dark:border-surface-600 dark:text-white dark:hover:bg-surface-800"
                >
                  Précédent
                </button>
                <button
                  type="button"
                  onClick={() => goNext()}
                  disabled={currentIndex < 0 || currentIndex >= sidebarItems.length - 1}
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
                  placeholder="Rechercher un message…"
                  className="w-full rounded-2xl border border-surface-200 bg-white py-3 pl-10 pr-3 text-sm text-surface-900 shadow-sm outline-none ring-burgundy-400/35 placeholder:text-surface-400 focus:border-burgundy-300 focus:ring-2 dark:border-surface-600 dark:bg-surface-950 dark:text-white"
                  aria-label="Recherche parmi les messages"
                />
              </div>
              <h2 className="font-heading text-sm font-bold uppercase tracking-wider text-surface-400">Autres messages</h2>
              <div className="max-h-[min(70vh,820px)] space-y-2 overflow-auto pr-1">
                {listLoading && sidebarItems.length === 0 ? (
                  <p className="text-sm text-surface-500 dark:text-surface-400">Chargement de la liste…</p>
                ) : null}
                {listError ? <p className="text-sm text-burgundy-600">{listError}</p> : null}
                <ul className="space-y-2 pb-10">
                  {sidebarItems.map((item) => {
                    const selected = item.id === current.id;

                    return (
                      <li key={item.id}>
                        <Link
                          to={`/teachings/message/${item.id}`}
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
                        </Link>
                      </li>
                    );
                  })}
                </ul>
                {!listLoading && sidebarItems.length === 0 && !listError ? (
                  <p className="py-8 text-center text-sm text-surface-500 dark:text-surface-400">
                    Aucun résultat. Essayez une autre requête.
                  </p>
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
