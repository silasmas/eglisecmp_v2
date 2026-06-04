import { useMemo, useState } from 'react';
import { motion } from 'framer-motion';
import PageHero from '../components/ui/PageHero';
import EventCard from '../components/cards/EventCard';
import EventDetailModal from '../components/ui/EventDetailModal';
import { events as fallbackEvents } from '../data/content';
import type { Event } from '../data/types';
import { useSiteEvents } from '../hooks/useSiteEvents';
import { cn } from '../lib/utils';

type EventFilter = 'all' | 'upcoming' | 'ongoing' | 'past';

const FILTER_OPTIONS: { id: EventFilter; label: string }[] = [
  { id: 'all', label: 'Tous' },
  { id: 'upcoming', label: 'À venir' },
  { id: 'ongoing', label: 'En cours' },
  { id: 'past', label: 'Passés' },
];

/**
 * Page publique listant les événements avec modale de détail et filtres chronologiques.
 */
export default function EventsPage() {
  const { events, loading } = useSiteEvents(fallbackEvents, 80);
  const [selectedEvent, setSelectedEvent] = useState<Event | null>(null);
  const [filter, setFilter] = useState<EventFilter>('all');

  const orderedEvents = useMemo(() => {
    return [...events]
      .filter((event) => event.hasPoster === true)
      .sort((a, b) => {
        const dateA = a.date.trim() !== '' ? new Date(a.date).getTime() : 0;
        const dateB = b.date.trim() !== '' ? new Date(b.date).getTime() : 0;
        return dateB - dateA;
      });
  }, [events]);

  const filteredEvents = useMemo(() => {
    if (filter === 'all') {
      return orderedEvents;
    }
    return orderedEvents.filter((event) => event.temporalStatus === filter);
  }, [orderedEvents, filter]);

  const counts = useMemo(() => {
    return {
      all: orderedEvents.length,
      upcoming: orderedEvents.filter((e) => e.temporalStatus === 'upcoming').length,
      ongoing: orderedEvents.filter((e) => e.temporalStatus === 'ongoing').length,
      past: orderedEvents.filter((e) => e.temporalStatus === 'past').length,
    };
  }, [orderedEvents]);

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
          <div className="mb-10 flex flex-wrap gap-2">
            {FILTER_OPTIONS.map((option) => (
              <button
                key={option.id}
                type="button"
                onClick={() => setFilter(option.id)}
                className={cn(
                  'inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm font-semibold transition',
                  filter === option.id
                    ? 'border-burgundy-700 bg-burgundy-800 text-white shadow-md shadow-burgundy-900/15'
                    : 'border-surface-200 bg-white text-surface-700 hover:border-burgundy-200 hover:bg-burgundy-50',
                )}
              >
                {option.label}
                <span
                  className={cn(
                    'rounded-full px-2 py-0.5 text-[11px]',
                    filter === option.id ? 'bg-white/15 text-white' : 'bg-surface-100 text-surface-500',
                  )}
                >
                  {counts[option.id]}
                </span>
              </button>
            ))}
          </div>

          {loading ? (
            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
              {[0, 1, 2, 3].map((index) => (
                <div key={index} className="min-h-[24rem] animate-pulse rounded-2xl bg-surface-100" />
              ))}
            </div>
          ) : filteredEvents.length === 0 ? (
            <p className="text-center text-surface-500">Aucun événement dans cette catégorie pour le moment.</p>
          ) : (
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true }}
              transition={{ duration: 0.5 }}
              className="grid grid-cols-1 gap-6 sm:grid-cols-2"
            >
              {filteredEvents.map((event) => (
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
