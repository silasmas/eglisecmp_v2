import { createBrowserRouter, Navigate } from 'react-router-dom';
import Layout from '../components/layout/Layout';
import { detectSpaBasename } from '../lib/routerBasename';
import SiteErrorPage from '../pages/SiteErrorPage';
import HomePage from '../pages/HomePage';
import DiscoverPage from '../pages/DiscoverPage';
import AboutPage from '../pages/AboutPage';
import VisionPage from '../pages/VisionPage';
import LeadershipPage from '../pages/LeadershipPage';
import TeachingsPage from '../pages/TeachingsPage';
import PlaylistWatchPage from '../pages/PlaylistWatchPage';
import MessageWatchPage from '../pages/MessageWatchPage';
import EventsPage from '../pages/EventsPage';
import BundaPage from '../pages/BundaPage';
import MediaPage from '../pages/MediaPage';
import JoinPage from '../pages/JoinPage';
import OffrandesPage from '../pages/OffrandesPage';
import PrayerRequestPage from '../pages/PrayerRequestPage';
import AppointmentPage from '../pages/AppointmentPage';
import TestimonyWallPage from '../pages/TestimonyWallPage';
import AlertUnsubscribePage from '../pages/AlertUnsubscribePage';
import QrShortcutsLandingPage from '../pages/QrShortcutsLandingPage';
import ChildPresentationPage from '../pages/ChildPresentationPage';
import CellsPage from '../pages/CellsPage';
import ExtensionsPage from '../pages/ExtensionsPage';
import WorshipStatsReportPage from '../pages/WorshipStatsReportPage';
import WorkerRegistrationPage from '../pages/WorkerRegistrationPage';
import WorkerBadgePage from '../pages/WorkerBadgePage';
import WorkerBadgeLayout from '../components/workers/WorkerBadgeLayout';

/**
 * Le badge est déclaré AVANT le Layout site pour éviter que le splat `*`
 * n’affiche la page dans le chrome (navbar / footer / FAB).
 */
export const router = createBrowserRouter(
  [
    {
      path: 'ouvriers/badge/:token',
      element: <WorkerBadgeLayout />,
      errorElement: <SiteErrorPage />,
      children: [
        { index: true, element: <WorkerBadgePage /> },
      ],
    },
    {
      element: <Layout />,
      errorElement: <SiteErrorPage />,
      children: [
        { index: true, element: <HomePage /> },
        { path: 'discover', element: <DiscoverPage /> },
        { path: 'discover/about', element: <AboutPage /> },
        { path: 'discover/vision', element: <VisionPage /> },
        { path: 'discover/leadership', element: <LeadershipPage /> },
        { path: 'discover/cellules', element: <CellsPage /> },
        { path: 'discover/extensions', element: <ExtensionsPage /> },
        { path: 'teachings', element: <TeachingsPage /> },
        { path: 'teachings/playlist/:eventId', element: <PlaylistWatchPage /> },
        { path: 'teachings/message/:postId', element: <MessageWatchPage /> },
        { path: 'events', element: <EventsPage /> },
        { path: 'events/bunda', element: <BundaPage /> },
        { path: 'media', element: <MediaPage /> },
        { path: 'join', element: <JoinPage /> },
        { path: 'contact', element: <Navigate to="/join#contact" replace /> },
        { path: 'offrandes', element: <OffrandesPage /> },
        { path: 'requete-de-priere', element: <PrayerRequestPage /> },
        { path: 'rendez-vous', element: <AppointmentPage /> },
        { path: 'temoignages', element: <TestimonyWallPage /> },
        { path: 'presentation-enfants', element: <ChildPresentationPage /> },
        { path: 'protocole/stats-culte', element: <WorshipStatsReportPage /> },
        { path: 'stats-culte', element: <Navigate to="/protocole/stats-culte" replace /> },
        { path: 'ouvriers/inscription', element: <WorkerRegistrationPage /> },
        { path: 'ouvriers/modifier/:editToken', element: <WorkerRegistrationPage /> },
        { path: 'raccourcis', element: <QrShortcutsLandingPage /> },
        { path: 'qr', element: <Navigate to="/raccourcis" replace /> },
        { path: 'alertes/desabonnement', element: <AlertUnsubscribePage /> },
        { path: '*', element: <SiteErrorPage statusCode={404} /> },
      ],
    },
  ],
  { basename: detectSpaBasename() },
);
