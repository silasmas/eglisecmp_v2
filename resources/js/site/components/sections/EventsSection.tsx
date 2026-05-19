import { useMemo, useState, useEffect } from 'react';
import { motion } from 'framer-motion';
import { ArrowLeft, ArrowRight, CalendarDays, MapPin } from 'lucide-react';
import CTAButton from '../ui/CTAButton';
import { events as fallbackEvents } from '../../data/content';
import SocialShareToolbar from '../ui/SocialShareToolbar';
import { useSiteEvents } from '../../hooks/useSiteEvents';
import ImageWithSkeleton from '../ui/ImageWithSkeleton';
import { EventCarouselSkeleton } from '../ui/Skeleton';

const AUTOPLAY_DELAY = 5000;

/**
 * Rubrique événements : données chargées une seule fois par le hook ; slides par translation CSS (sans remonter les cartes).
 */
export default function EventsSection() {
  const { events, loading } = useSiteEvents(fallbackEvents, 20);
  const featuredId = events.find((event) => event.featured)?.id;
  const orderedEvents = useMemo(() => {
    if (!featuredId) {
      return events;
    }
    const featured = events.find((event) => event.id === featuredId);
    const rest = events.filter((event) => event.id !== featuredId);
    return featured ? [featured, ...rest] : events;
  }, [events, featuredId]);

  const [activeIndex, setActiveIndex] = useState(0);
  const [isPaused, setIsPaused] = useState(false);

  const goTo = (index: number) => {
    setActiveIndex((index + orderedEvents.length) % Math.max(orderedEvents.length, 1));
  };

  useEffect(() => {
    if (orderedEvents.length <= 1) {
      return;
    }
    setActiveIndex((i) => (i >= orderedEvents.length ? 0 : i));
  }, [orderedEvents.length]);

  useEffect(() => {
    if (isPaused || orderedEvents.length <= 1) {
      return;
    }
    const timer = window.setInterval(() => {
      setActiveIndex((current) => (current + 1) % orderedEvents.length);
    }, AUTOPLAY_DELAY);

    return () => window.clearInterval(timer);
  }, [isPaused, orderedEvents.length]);

  const activeEvent = orderedEvents[activeIndex] ?? orderedEvents[0];

  return (
    <section className="relative overflow-hidden bg-surface-950 py-24 text-white">
      <div className="absolute top-0 left-1/2 h-[400px] w-[800px] -translate-x-1/2 rounded-full bg-surface-800/30 blur-[120px]" />

      <div className="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true, margin: '-60px' }}
          transition={{ duration: 0.55 }}
          className="mx-auto max-w-3xl text-center"
        >
          <span className="inline-block rounded-full border border-white/10 bg-white/6 px-4 py-1.5 text-[11px] font-semibold uppercase tracking-[0.15em] text-gold-300">
            Événements
          </span>
          <h2 className="mt-5 font-heading text-3xl font-extrabold leading-[1.05] tracking-tight text-white sm:text-4xl lg:text-[3.25rem]">
            À ne pas manquer
          </h2>
          <p className="mx-auto mt-4 max-w-2xl text-lg leading-relaxed text-white/58">
            Retrouvez les grands rendez-vous de la maison et préparez-vous à vivre des temps de prière, de communion et de transformation.
          </p>
        </motion.div>

        {loading ? (
          <EventCarouselSkeleton />
        ) : activeEvent ? (
          <div
            className="relative mt-14"
            onMouseEnter={() => setIsPaused(true)}
            onMouseLeave={() => setIsPaused(false)}
          >
            <div className="pointer-events-none absolute left-0 top-0 hidden h-full w-20 bg-gradient-to-r from-surface-950 to-transparent lg:block" />
            <div className="pointer-events-none absolute right-0 top-0 hidden h-full w-20 bg-gradient-to-l from-surface-950 to-transparent lg:block" />

            <div className="relative mx-auto flex max-w-6xl items-center justify-center gap-4 lg:gap-6">
              {orderedEvents.length > 1 && (
                <button
                  type="button"
                  onClick={() => goTo(activeIndex - 1)}
                  className="z-10 hidden h-12 w-12 shrink-0 items-center justify-center rounded-full border border-white/10 bg-white/6 text-white transition-colors duration-300 hover:bg-white/12 lg:inline-flex"
                  aria-label="Événement précédent"
                >
                  <ArrowLeft className="h-4 w-4" />
                </button>
              )}

              <div className="relative flex-1 overflow-hidden">
                <div className="pointer-events-none absolute left-0 top-1/2 z-[1] hidden h-[84%] w-[14%] -translate-y-1/2 rounded-[2rem] border border-white/8 bg-white/[0.03] opacity-80 blur-[1px] lg:block" />
                <div className="pointer-events-none absolute right-0 top-1/2 z-[1] hidden h-[84%] w-[14%] -translate-y-1/2 rounded-[2rem] border border-white/8 bg-white/[0.03] opacity-80 blur-[1px] lg:block" />

                <div className="relative mx-auto w-full max-w-5xl overflow-hidden">
                  <div
                    className="flex transition-transform duration-500 ease-[cubic-bezier(0.22,1,0.36,1)]"
                    style={{ transform: `translateX(-${activeIndex * 100}%)` }}
                  >
                    {orderedEvents.map((event) => (
                      <div key={event.id} className="w-full shrink-0">
                        <div className="relative mx-auto h-[25rem] overflow-hidden rounded-[2rem] border border-white/6 bg-surface-900 shadow-[0_24px_70px_rgba(9,9,11,0.28)] sm:h-[29rem]">
                          <ImageWithSkeleton
                            src={event.image}
                            alt={event.title}
                            className="absolute inset-0 h-full w-full object-cover"
                          />
                          <div className="absolute inset-0 bg-[linear-gradient(180deg,rgba(9,9,11,0.1)_0%,rgba(9,9,11,0.08)_18%,rgba(9,9,11,0.72)_100%)]" />
                          <div className="absolute inset-x-0 bottom-0 h-[58%] bg-gradient-to-t from-surface-950 via-surface-950/72 to-transparent" />
                          <div className="absolute inset-x-0 top-0 h-24 bg-[radial-gradient(circle_at_top,rgba(255,255,255,0.16),transparent_72%)]" />
                          <div className="absolute left-0 right-0 top-[44%] h-[22%] bg-[linear-gradient(180deg,transparent,rgba(81,168,178,0.22),transparent)] opacity-80" />

                          <div className="relative flex h-full flex-col justify-between p-6 sm:p-8">
                            <div className="flex items-start justify-between gap-4">
                              <span className="inline-flex items-center gap-2 rounded-full border border-white/12 bg-white/10 px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[0.16em] text-white/85 backdrop-blur-md">
                                <CalendarDays className="h-3.5 w-3.5" />
                                {new Date(event.date).toLocaleDateString('fr-FR', {
                                  day: 'numeric',
                                  month: 'long',
                                })}
                              </span>
                              <span className="hidden items-center gap-1.5 rounded-full border border-white/10 bg-black/18 px-3 py-1.5 text-[10px] font-medium text-white/70 backdrop-blur-md sm:inline-flex">
                                <MapPin className="h-3.5 w-3.5" />
                                {event.location}
                              </span>
                            </div>

                            <div className="max-w-xl">
                              <p className="text-sm font-medium text-white/72">{event.time}</p>
                              <h3 className="mt-2 font-heading text-[2rem] font-extrabold leading-[0.96] tracking-tight text-white sm:text-[3.3rem]">{event.title}</h3>
                              <p className="mt-3 max-w-lg text-sm leading-relaxed text-white/68 sm:text-[15px]">{event.description}</p>
                              <div className="mt-6 flex flex-wrap items-center gap-3">
                                <SocialShareToolbar
                                  title={event.title}
                                  description={event.description}
                                  sharePath={`/events?event=${encodeURIComponent(event.id)}`}
                                  compact
                                />
                                <CTAButton to="/events" variant="white" className="shadow-lg shadow-black/25">
                                  Voir l&apos;événement
                                </CTAButton>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              </div>

              {orderedEvents.length > 1 && (
                <button
                  type="button"
                  onClick={() => goTo(activeIndex + 1)}
                  className="z-10 hidden h-12 w-12 shrink-0 items-center justify-center rounded-full border border-white/10 bg-white/6 text-white transition-colors duration-300 hover:bg-white/12 lg:inline-flex"
                  aria-label="Événement suivant"
                >
                  <ArrowRight className="h-4 w-4" />
                </button>
              )}
            </div>

            <div className="mt-8 flex flex-col items-center gap-4">
              <div className="flex items-center gap-2">
                {orderedEvents.map((event, index) => (
                  <button
                    key={event.id}
                    type="button"
                    onClick={() => goTo(index)}
                    className={
                      index === activeIndex
                        ? 'h-2.5 w-8 rounded-full bg-white transition-all duration-300'
                        : 'h-2.5 w-2.5 rounded-full bg-white/28 transition-all duration-300 hover:bg-white/55'
                    }
                    aria-label={`Aller à l'événement ${index + 1}`}
                  />
                ))}
              </div>

              <div className="flex items-center gap-3 lg:hidden">
                <button
                  type="button"
                  onClick={() => goTo(activeIndex - 1)}
                  className="inline-flex h-11 w-11 items-center justify-center rounded-full border border-white/10 bg-white/6 text-white transition-colors duration-300 hover:bg-white/12"
                  aria-label="Événement précédent"
                >
                  <ArrowLeft className="h-4 w-4" />
                </button>
                <button
                  type="button"
                  onClick={() => goTo(activeIndex + 1)}
                  className="inline-flex h-11 w-11 items-center justify-center rounded-full border border-white/10 bg-white/6 text-white transition-colors duration-300 hover:bg-white/12"
                  aria-label="Événement suivant"
                >
                  <ArrowRight className="h-4 w-4" />
                </button>
              </div>
            </div>
          </div>
        ) : null}
      </div>
    </section>
  );
}
