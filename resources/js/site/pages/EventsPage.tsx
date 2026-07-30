import { useEffect, useMemo, useState } from 'react';
import { Bell } from 'lucide-react';
import { useSearchParams } from 'react-router-dom';
import PageHero from '../components/ui/PageHero';
import EventCard from '../components/cards/EventCard';
import EventDetailModal from '../components/ui/EventDetailModal';
import AlertSubscribeModal from '../components/alerts/AlertSubscribeModal';
import { events as fallbackEvents } from '../data/content';
import type { Event } from '../data/types';
import { useSiteEvents } from '../hooks/useSiteEvents';
import { cn } from '../lib/utils';

type EventFilter = 'all' | 'upcoming' | 'ongoing' | 'past' | 'featured';

const FILTER_OPTIONS: { id: EventFilter; label: string }[] = [
  { id: 'all', label: 'Tous' },
  { id: 'upcoming', label: 'À venir' },
  { id: 'ongoing', label: 'En cours' },
  { id: 'past', label: 'Passés' },
  { id: 'featured', label: 'À la une' },
];

const THEME_LABELS: Record<string, string> = {
  'jeudi-dedicace': 'Jeudi dédicace',
  'mois-ouvrier': "Mois de l'ouvrier",
  seminaires: 'Séminaires',
  'mois-evangelique': 'Mois évangélique',
  'bunda-21': 'Bunda 21',
  'aksanti-mungu': 'Aksanti Mungu',
  nativite: 'Culte de nativité',
  reveillon: 'Réveillon',
};

/**
 * Indique si un événement correspond au slug de sous-menu demandé.
 */
function matchesMenuTheme(event: Event, themeSlug: string): boolean {
  if (event.menuSlug !== null && event.menuSlug !== undefined && event.menuSlug === themeSlug) {
    return true;
  }

  const themeText = (event.theme ?? '').toLowerCase();
  const title = event.title.toLowerCase();
  const needle = themeSlug.replace(/-/g, ' ');

  return themeText.includes(needle) || title.includes(needle);
}

/**
 * Page publique listant les événements avec filtres chronologiques et thème (?theme=).
 */
export default function EventsPage() {
  const [searchParams] = useSearchParams();
  const themeSlug = (searchParams.get('theme') ?? '').trim();
  const { events, loading } = useSiteEvents(fallbackEvents, 80, 'all');
  const [selectedEvent, setSelectedEvent] = useState<Event | null>(null);
  const [filter, setFilter] = useState<EventFilter>('all');
  const [alertModalOpen, setAlertModalOpen] = useState(false);

  useEffect(() => {
    if (themeSlug !== '') {
      setFilter('all');
    }
  }, [themeSlug]);

  const orderedEvents = useMemo(() => {
    return [...events]
      .filter((event) => event.hasPoster === true)
      .sort((a, b) => {
        const dateA = a.date.trim() !== '' ? new Date(a.date).getTime() : 0;
        const dateB = b.date.trim() !== '' ? new Date(b.date).getTime() : 0;
        return dateB - dateA;
      });
  }, [events]);

  const themedEvents = useMemo(() => {
    if (themeSlug === '') {
      return orderedEvents;
    }
    return orderedEvents.filter((event) => matchesMenuTheme(event, themeSlug));
  }, [orderedEvents, themeSlug]);

  const filteredEvents = useMemo(() => {
    if (filter === 'all') {
      return themedEvents;
    }
    if (filter === 'featured') {
      return themedEvents.filter((event) => event.featured === true);
    }
    return themedEvents.filter((event) => event.temporalStatus === filter);
  }, [themedEvents, filter]);

  const counts = useMemo(() => {
    return {
      all: themedEvents.length,
      upcoming: themedEvents.filter((e) => e.temporalStatus === 'upcoming').length,
      ongoing: themedEvents.filter((e) => e.temporalStatus === 'ongoing').length,
      past: themedEvents.filter((e) => e.temporalStatus === 'past').length,
      featured: themedEvents.filter((e) => e.featured === true).length,
    };
  }, [themedEvents]);

  const pageTitle = themeSlug !== '' && THEME_LABELS[themeSlug] !== undefined
    ? THEME_LABELS[themeSlug]
    : 'Nos événements';

  return (
    <>
      <PageHero
        badge="Événements"
        title={pageTitle}
        description={
          themeSlug !== ''
            ? `Événements synchronisés avec le sous-menu « ${pageTitle} ».`
            : 'Découvrez les prochains événements et célébrations de notre communauté.'
        }
        backgroundImage="https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=1400&h=600&fit=crop"
      />

      <section className="py-24">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div className="mb-10 flex flex-wrap items-center justify-between gap-4">
            <div className="flex flex-wrap gap-2">
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
            <button
              type="button"
              onClick={() => setAlertModalOpen(true)}
              className="inline-flex items-center gap-2 rounded-full border border-burgundy-200 bg-burgundy-50 px-4 py-2 text-sm font-semibold text-burgundy-900 hover:bg-burgundy-100"
            >
              <Bell className="h-4 w-4" />
              Alertes
            </button>
          </div>

          {loading ? (
            <p className="text-center text-surface-500">Chargement des événements…</p>
          ) : filteredEvents.length === 0 ? (
            <p className="rounded-2xl border border-dashed border-surface-300 bg-surface-50 px-6 py-12 text-center text-surface-600">
              Aucun événement pour ce filtre
              {themeSlug !== '' ? ` (« ${pageTitle} »)` : ''}.
              {themeSlug !== ''
                ? ' Assignez le sous-menu correspondant dans l’admin (champ « Sous-menu site »).'
                : ''}
            </p>
          ) : (
            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
              {filteredEvents.map((event) => (
                <EventCard
                  key={event.id}
                  event={event}
                  featured={event.featured}
                  onOpenDetail={setSelectedEvent}
                />
              ))}
            </div>
          )}
        </div>
      </section>

      <AlertSubscribeModal
        open={alertModalOpen}
        onClose={() => setAlertModalOpen(false)}
        source="events"
        title="Ne manquez plus nos événements"
        defaultNotifyLive={false}
        defaultNotifyEvents={true}
      />

      <EventDetailModal
        open={selectedEvent !== null}
        onClose={() => setSelectedEvent(null)}
        event={selectedEvent}
      />
    </>
  );
}
