import HeroSection from '../components/sections/HeroSection';
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
