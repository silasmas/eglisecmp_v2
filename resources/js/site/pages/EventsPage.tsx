import { motion } from 'framer-motion';
import PageHero from '../components/ui/PageHero';
import SocialShareToolbar from '../components/ui/SocialShareToolbar';
import EventCard from '../components/cards/EventCard';
import { events as fallbackEvents } from '../data/content';
import { useSiteEvents } from '../hooks/useSiteEvents';

export default function EventsPage() {
  const { events } = useSiteEvents(fallbackEvents, 40);
  return (
    <>
      <PageHero
        badge="Événements"
        title="Nos événements"
        description="Découvrez les prochains événements et célébrations de notre communauté."
        backgroundImage="https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=1400&h=600&fit=crop"
      />

      <section className="py-24">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div className="mb-8 flex justify-end">
            <SocialShareToolbar
              title="Événements — CMP"
              description="Centre Missionnaire Philadelphie"
              sharePath="/events"
              compact
            />
          </div>
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ duration: 0.5 }}
            className="grid grid-cols-1 sm:grid-cols-2 gap-6"
          >
            {events.map((event) => (
              <div key={event.id} className="relative">
                <div className="absolute right-4 top-4 z-20 pointer-events-auto">
                  <SocialShareToolbar
                    title={event.title}
                    description={event.description}
                    sharePath="/events"
                    compact
                  />
                </div>
                <EventCard event={event} featured={event.featured} />
              </div>
            ))}
          </motion.div>
        </div>
      </section>
    </>
  );
}
