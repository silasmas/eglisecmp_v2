import { useMemo, useState } from 'react';
import { motion } from 'framer-motion';
import PageHero from '../components/ui/PageHero';
import EventCard from '../components/cards/EventCard';
import EventDetailModal from '../components/ui/EventDetailModal';
import { events as fallbackEvents } from '../data/content';
import type { Event } from '../data/types';
import { useSiteEvents } from '../hooks/useSiteEvents';

/**
 * Page publique listant les événements avec modale de détail.
 */
export default function EventsPage() {
  const { events, loading } = useSiteEvents(fallbackEvents, 40);
  const [selectedEvent, setSelectedEvent] = useState<Event | null>(null);

  const orderedEvents = useMemo(() => {
    const featured = events.filter((event) => event.featured);
    const regular = events.filter((event) => !event.featured);

    return [...featured, ...regular];
  }, [events]);

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
          {loading ? (
            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
              {[0, 1, 2, 3].map((index) => (
                <div key={index} className="min-h-[24rem] animate-pulse rounded-2xl bg-surface-100" />
              ))}
            </div>
          ) : orderedEvents.length === 0 ? (
            <p className="text-center text-surface-500">Aucun événement programmé pour le moment.</p>
          ) : (
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true }}
              transition={{ duration: 0.5 }}
              className="grid grid-cols-1 gap-6 sm:grid-cols-2"
            >
              {orderedEvents.map((event) => (
                <EventCard
                  key={event.id}
                  event={event}
                  featured={event.featured}
                  onOpenDetail={setSelectedEvent}
                />
              ))}
            </motion.div>
          )}
        </div>
      </section>

      <EventDetailModal
        open={selectedEvent !== null}
        onClose={() => setSelectedEvent(null)}
        event={selectedEvent}
      />
    </>
  );
}
