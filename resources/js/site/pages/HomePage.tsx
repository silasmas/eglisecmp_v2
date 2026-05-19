import HeroSection from '../components/sections/HeroSection';
import SocialShareToolbar from '../components/ui/SocialShareToolbar';
import QuickActionsSection from '../components/sections/QuickActionsSection';
import AboutPreviewSection from '../components/sections/AboutPreviewSection';
import TeachingsSection from '../components/sections/TeachingsSection';
import EventsSection from '../components/sections/EventsSection';
import StatsSection from '../components/sections/StatsSection';
import ProgramsSection from '../components/sections/ProgramsSection';
import TestimonySection from '../components/sections/TestimonySection';
import VisitCTASection from '../components/sections/VisitCTASection';

export default function HomePage() {
  return (
    <>
      <HeroSection />
      <div className="border-b border-surface-200 bg-white dark:border-surface-800 dark:bg-surface-950">
        <div className="mx-auto flex max-w-7xl justify-end px-4 py-5 sm:px-6 lg:px-8">
          <SocialShareToolbar
            title="Centre Missionnaire Philadelphie"
            description="Accueil — messages, événements et enseignements"
            sharePath="/"
            compact
          />
        </div>
      </div>
      <QuickActionsSection />
      <AboutPreviewSection />
      <TeachingsSection />
      <EventsSection />
      <ProgramsSection />
      <StatsSection />
      <TestimonySection />
      <VisitCTASection />
    </>
  );
}
