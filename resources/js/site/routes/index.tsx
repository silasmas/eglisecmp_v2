import { createBrowserRouter } from 'react-router-dom';
import Layout from '../components/layout/Layout';
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
import ContactPage from '../pages/ContactPage';
import OffrandesPage from '../pages/OffrandesPage';
import PrayerRequestPage from '../pages/PrayerRequestPage';
import AppointmentPage from '../pages/AppointmentPage';

export const router = createBrowserRouter([
  {
    element: <Layout />,
    children: [
      { index: true, element: <HomePage /> },
      { path: 'discover', element: <DiscoverPage /> },
      { path: 'discover/about', element: <AboutPage /> },
      { path: 'discover/vision', element: <VisionPage /> },
      { path: 'discover/leadership', element: <LeadershipPage /> },
      { path: 'teachings', element: <TeachingsPage /> },
      { path: 'teachings/playlist/:eventId', element: <PlaylistWatchPage /> },
      { path: 'teachings/message/:postId', element: <MessageWatchPage /> },
      { path: 'events', element: <EventsPage /> },
      { path: 'events/bunda', element: <BundaPage /> },
      { path: 'media', element: <MediaPage /> },
      { path: 'join', element: <JoinPage /> },
      { path: 'contact', element: <ContactPage /> },
      { path: 'offrandes', element: <OffrandesPage /> },
      { path: 'requete-de-priere', element: <PrayerRequestPage /> },
      { path: 'rendez-vous', element: <AppointmentPage /> },
    ],
  },
]);
