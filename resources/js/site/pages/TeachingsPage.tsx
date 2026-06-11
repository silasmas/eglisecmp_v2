import { useSearchParams } from 'react-router-dom';
import PageHero from '../components/ui/PageHero';
import TabErrorBoundary from '../components/ui/TabErrorBoundary';
import MeditationsByThemeView from '../components/teachings/MeditationsByThemeView';
import PlaylistsStackedView from '../components/teachings/PlaylistsStackedView';
import TeachingsSermonsTab from '../components/teachings/TeachingsSermonsTab';
import TeachingsTabBar, { resolveTeachingsTab } from '../components/teachings/TeachingsTabBar';

/**
 * Page Enseignements : onglets Messages, Méditations, Playlists.
 */
export default function TeachingsPage() {
  const [searchParams] = useSearchParams();
  const tab = resolveTeachingsTab(searchParams);

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

          {tab === 'sermons' ? (
            <TabErrorBoundary tabLabel="les messages">
              <TeachingsSermonsTab />
            </TabErrorBoundary>
          ) : null}
          {tab === 'meditations' ? (
            <TabErrorBoundary tabLabel="les méditations">
              <MeditationsByThemeView />
            </TabErrorBoundary>
          ) : null}
          {tab === 'playlists' ? (
            <TabErrorBoundary tabLabel="les playlists">
              <PlaylistsStackedView />
            </TabErrorBoundary>
          ) : null}
        </div>
      </section>
    </>
  );
}
