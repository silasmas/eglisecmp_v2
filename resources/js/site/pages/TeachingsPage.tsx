import { useEffect } from 'react';
import { useSearchParams } from 'react-router-dom';
import PageHero from '../components/ui/PageHero';
import SocialShareToolbar from '../components/ui/SocialShareToolbar';
import MeditationsByThemeView from '../components/teachings/MeditationsByThemeView';
import MessagesGridView from '../components/teachings/MessagesGridView';
import PlaylistsStackedView from '../components/teachings/PlaylistsStackedView';
import TeachingsTabBar, { resolveTeachingsTab } from '../components/teachings/TeachingsTabBar';
import { useInfiniteSitePosts } from '../hooks/useInfiniteSitePosts';
import { prefetchImageUrls } from '../lib/imagePrefetch';

/**
 * Page Enseignements : onglets Messages, Méditations, Playlists avec scroll infini.
 */
export default function TeachingsPage() {
  const [searchParams] = useSearchParams();
  const tab = resolveTeachingsTab(searchParams);
  const tabShareLabel = tab === 'sermons' ? 'Messages' : tab === 'meditations' ? 'Méditations' : 'Playlists';
  const { items, loading, loadingMore, error, hasMore, loadMore } = useInfiniteSitePosts(tab);

  useEffect(() => {
    prefetchImageUrls(
      items.flatMap((item) => [item.thumbnail, item.eventImage]),
      96,
    );
  }, [items]);

  const viewProps = {
    items,
    loading,
    loadingMore,
    hasMore,
    error,
    onLoadMore: loadMore,
  };

  return (
    <>
      <PageHero
        badge="Enseignements"
        title="Messages & Méditations"
        description="Retrouvez l'ensemble de nos prédications, enseignements et méditations pour nourrir votre foi."
        backgroundImage="https://images.unsplash.com/photo-1504052434569-70ad5836ab65?w=1400&h=600&fit=crop"
      />

      <section className="py-24">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <TeachingsTabBar />
          <div className="mb-8 flex justify-end">
            <SocialShareToolbar
              title={`CMP — ${tabShareLabel}`}
              description="Enseignements du Centre Missionnaire Philadelphie"
              sharePath={`/teachings?tab=${tab}`}
              compact
            />
          </div>

          {tab === 'sermons' ? <MessagesGridView {...viewProps} /> : null}
          {tab === 'meditations' ? <MeditationsByThemeView {...viewProps} /> : null}
          {tab === 'playlists' ? <PlaylistsStackedView {...viewProps} /> : null}
        </div>
      </section>
    </>
  );
}
