import { Outlet, useLocation } from 'react-router-dom';
import { AnimatePresence, motion } from 'framer-motion';
import Navbar from './Navbar';
import Footer from './Footer';
import FloatingActionsMenu from './FloatingActionsMenu';
import { FeaturedEventProvider } from '../../context/FeaturedEventContext';
import { YoutubeLiveProvider } from '../../context/YoutubeLiveContext';
import YoutubeLiveModal from '../ui/YoutubeLiveModal';
import { useScrollToTop } from '../../hooks/useScrollToTop';
import { useYoutubeLive } from '../../context/YoutubeLiveContext';

function SiteChrome() {
  const { live, modalOpen, dismissLiveModal } = useYoutubeLive();

  return (
    <>
      <YoutubeLiveModal open={modalOpen} live={live} onClose={dismissLiveModal} />
      <FloatingActionsMenu />
    </>
  );
}

export default function Layout() {
  const location = useLocation();
  useScrollToTop();

  return (
    <FeaturedEventProvider>
      <YoutubeLiveProvider>
      <div className="min-h-screen flex flex-col">
        <Navbar />
        <main className="flex-1">
          <AnimatePresence mode="wait">
            <motion.div
              key={location.pathname}
              initial={{ opacity: 0, y: 8 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -6 }}
              transition={{ duration: 0.22, ease: [0.22, 1, 0.36, 1] }}
            >
              <Outlet />
            </motion.div>
          </AnimatePresence>
        </main>
        <Footer />
        <SiteChrome />
      </div>
      </YoutubeLiveProvider>
    </FeaturedEventProvider>
  );
}
