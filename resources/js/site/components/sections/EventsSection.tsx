import { useMemo, useState, useEffect } from 'react';
import { motion } from 'framer-motion';
import { ArrowLeft, ArrowRight, Bell, CalendarDays, MapPin, Radio } from 'lucide-react';
import CTAButton from '../ui/CTAButton';
import AlertSubscribeModal from '../alerts/AlertSubscribeModal';
import { events as fallbackEvents, programs as fallbackPrograms } from '../../data/content';
import { useSiteEvents } from '../../hooks/useSiteEvents';
import { useSitePrograms } from '../../hooks/useSitePrograms';
import ImageWithSkeleton from '../ui/ImageWithSkeleton';
import { EventCarouselSkeleton } from '../ui/Skeleton';

const AUTOPLAY_DELAY = 5000;
const MAIN_WEEKLY_WEEKDAYS = [3, 4, 0];

/**
 * Résout le jour numérique d’un programme (API ou repli statique).
 *
 * @param program Programme site.
 * @returns Jour 0–6 (Carbon) ou undefined.
 */
function resolveProgramWeekday(program: { day: string; weekday?: number }): number | undefined {
  if (program.weekday !== undefined) {
    return program.weekday;
  }

  const dayMap: Record<string, number> = {
    Mercredi: 3,
    Jeudi: 4,
    Dimanche: 0,
  };

  return dayMap[program.day];
}

type HighlightSlide =
  | {
      kind: 'event';
      id: string;
      image: string;
      title: string;
      description: string;
      badge: string;
      time: string;
      location: string;
    }
  | {
      kind: 'program';
      id: string;
      image: string;
      title: string;
      description: string;
      badge: string;
      time: string;
    };

/**
 * Rubrique « À ne pas manquer » : événements à la une + programmes hebdomadaires (Mercredi, Jeudi, Dimanche).
 */
export default function EventsSection() {
  const { events, loading: eventsLoading } = useSiteEvents(fallbackEvents, 20);
  const { programs, loading: programsLoading } = useSitePrograms(fallbackPrograms);
  const loading = eventsLoading || programsLoading;

  const featuredId = events.find((event) => event.featured)?.id;
  const orderedEvents = useMemo(() => {
    const highlight = events.filter(
      (event) =>
        event.temporalStatus === 'upcoming' ||
        event.temporalStatus === 'ongoing' ||
        event.featured === true,
    );
    const pool = highlight.length > 0 ? highlight : events;
    if (!featuredId) {
      return pool;
    }
    const featured = pool.find((event) => event.id === featuredId);
    const rest = pool.filter((event) => event.id !== featuredId);
    return featured ? [featured, ...rest] : pool;
  }, [events, featuredId]);

  const slides = useMemo((): HighlightSlide[] => {
    const eventSlides: HighlightSlide[] = orderedEvents.map((event) => ({
      kind: 'event',
      id: event.id,
      image: event.image,
      title: event.title,
      description: event.description,
      badge:
        event.date.trim() !== ''
          ? new Date(event.date).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long' })
          : 'Événement',
      time: event.time,
      location: event.location,
    }));

    const weeklyPrograms = programs
      .filter((program) => {
        const weekday = resolveProgramWeekday(program);
        return weekday !== undefined && MAIN_WEEKLY_WEEKDAYS.includes(weekday);
      })
      .sort(
        (a, b) =>
          MAIN_WEEKLY_WEEKDAYS.indexOf(resolveProgramWeekday(a) as number) -
          MAIN_WEEKLY_WEEKDAYS.indexOf(resolveProgramWeekday(b) as number),
      );

    const programSlides: HighlightSlide[] = weeklyPrograms.map((program) => ({
      kind: 'program',
      id: program.id,
      image: program.bannerImage ?? program.thumbnail ?? '',
      title: program.name,
      description: program.description,
      badge: program.day !== '' ? program.day : 'Programme hebdomadaire',
      time: program.time,
    }));

    return [...eventSlides, ...programSlides];
  }, [orderedEvents, programs]);

  const [activeIndex, setActiveIndex] = useState(0);
  const [isPaused, setIsPaused] = useState(false);
  const [alertModalOpen, setAlertModalOpen] = useState(false);

  const goTo = (index: number) => {
    setActiveIndex((index + slides.length) % Math.max(slides.length, 1));
  };

  useEffect(() => {
    if (slides.length <= 1) {
      return;
    }
    setActiveIndex((i) => (i >= slides.length ? 0 : i));
  }, [slides.length]);

  useEffect(() => {
    if (isPaused || slides.length <= 1) {
      return;
    }
    const timer = window.setInterval(() => {
      setActiveIndex((current) => (current + 1) % slides.length);
    }, AUTOPLAY_DELAY);

    return () => window.clearInterval(timer);
  }, [isPaused, slides.length]);

  const activeSlide = slides[activeIndex] ?? slides[0];
  const alertSource = activeSlide?.kind === 'program' ? 'weekly' : 'events';
  const alertTitle =
    activeSlide?.kind === 'program'
      ? 'Programmes et événements de la semaine'
      : 'Ne manquez plus nos événements';

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
        ) : activeSlide ? (
          <div
            className="relative mt-14"
            onMouseEnter={() => setIsPaused(true)}
            onMouseLeave={() => setIsPaused(false)}
          >
            <div className="pointer-events-none absolute left-0 top-0 hidden h-full w-20 bg-gradient-to-r from-surface-950 to-transparent lg:block" />
            <div className="pointer-events-none absolute right-0 top-0 hidden h-full w-20 bg-gradient-to-l from-surface-950 to-transparent lg:block" />

            <div className="relative mx-auto flex max-w-6xl items-center justify-center gap-4 lg:gap-6">
              {slides.length > 1 ? (
                <button
                  type="button"
                  onClick={() => goTo(activeIndex - 1)}
                  className="z-10 hidden h-12 w-12 shrink-0 items-center justify-center rounded-full border border-white/10 bg-white/6 text-white transition-colors duration-300 hover:bg-white/12 lg:inline-flex"
                  aria-label="Slide précédent"
                >
                  <ArrowLeft className="h-4 w-4" />
                </button>
              ) : null}

              <div className="relative flex-1 overflow-hidden">
                <div className="pointer-events-none absolute left-0 top-1/2 z-[1] hidden h-[84%] w-[14%] -translate-y-1/2 rounded-[2rem] border border-white/8 bg-white/[0.03] opacity-80 blur-[1px] lg:block" />
                <div className="pointer-events-none absolute right-0 top-1/2 z-[1] hidden h-[84%] w-[14%] -translate-y-1/2 rounded-[2rem] border border-white/8 bg-white/[0.03] opacity-80 blur-[1px] lg:block" />

                <div className="relative mx-auto w-full max-w-5xl overflow-hidden">
                  <div
                    className="flex transition-transform duration-500 ease-[cubic-bezier(0.22,1,0.36,1)]"
                    style={{ transform: `translateX(-${activeIndex * 100}%)` }}
                  >
                    {slides.map((slide) => (
                      <div key={`${slide.kind}-${slide.id}`} className="w-full shrink-0">
                        <div className="relative mx-auto h-[25rem] overflow-hidden rounded-[2rem] border border-white/6 bg-surface-900 shadow-[0_24px_70px_rgba(9,9,11,0.28)] sm:h-[29rem]">
                          <div className="absolute inset-0 z-0">
                            <ImageWithSkeleton
                              src={slide.image}
                              alt={slide.title}
                              className="!relative !z-0 h-full w-full object-cover"
                            />
                          </div>
                          <div className="pointer-events-none absolute inset-0 z-[1] bg-[linear-gradient(180deg,rgba(9,9,11,0.1)_0%,rgba(9,9,11,0.08)_18%,rgba(9,9,11,0.72)_100%)]" />
                          <div className="pointer-events-none absolute inset-x-0 bottom-0 z-[1] h-[58%] bg-gradient-to-t from-surface-950 via-surface-950/72 to-transparent" />
                          <div className="pointer-events-none absolute inset-x-0 top-0 z-[1] h-24 bg-[radial-gradient(circle_at_top,rgba(255,255,255,0.16),transparent_72%)]" />

                          <div className="relative z-10 flex h-full flex-col justify-between p-6 sm:p-8">
                            <div className="flex items-start justify-between gap-4">
                              <span className="inline-flex items-center gap-2 rounded-full border border-white/12 bg-white/10 px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[0.16em] text-white/85 backdrop-blur-md">
                                {slide.kind === 'program' ? (
                                  <Radio className="h-3.5 w-3.5" />
                                ) : (
                                  <CalendarDays className="h-3.5 w-3.5" />
                                )}
                                {slide.badge}
                              </span>
                              {slide.kind === 'event' && slide.location.trim() !== '' ? (
                                <span className="hidden items-center gap-1.5 rounded-full border border-white/10 bg-black/18 px-3 py-1.5 text-[10px] font-medium text-white/70 backdrop-blur-md sm:inline-flex">
                                  <MapPin className="h-3.5 w-3.5" />
                                  {slide.location}
                                </span>
                              ) : null}
                            </div>

                            <div className="max-w-xl">
                              {slide.kind === 'program' ? (
                                <p className="text-[11px] font-semibold uppercase tracking-[0.14em] text-gold-300">
                                  Programme hebdomadaire
                                </p>
                              ) : null}
                              {slide.time.trim() !== '' ? (
                                <p className="text-sm font-medium text-white/72">{slide.time}</p>
                              ) : null}
                              <h3 className="mt-2 font-heading text-[2rem] font-extrabold leading-[0.96] tracking-tight text-white sm:text-[3.3rem]">
                                {slide.title}
                              </h3>
                              {slide.description.trim() !== '' ? (
                                <p className="mt-3 max-w-lg text-sm leading-relaxed text-white/68 sm:text-[15px]">
                                  {slide.description}
                                </p>
                              ) : null}
                              <div className="mt-6 flex flex-wrap items-center gap-3">
                                {slide.kind === 'event' ? (
                                  <CTAButton to="/events" variant="white" className="shadow-lg shadow-black/25">
                                    Voir l&apos;événement
                                  </CTAButton>
                                ) : (
                                  <CTAButton to="/events" variant="white" className="shadow-lg shadow-black/25">
                                    Nos programmes
                                  </CTAButton>
                                )}
                                <button
                                  type="button"
                                  onClick={() => setAlertModalOpen(true)}
                                  className="inline-flex items-center gap-2 rounded-2xl bg-red-700 px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-red-900/40 transition hover:bg-red-600"
                                >
                                  <Bell className="h-4 w-4" aria-hidden />
                                  Me prévenir
                                </button>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              </div>

              {slides.length > 1 ? (
                <button
                  type="button"
                  onClick={() => goTo(activeIndex + 1)}
                  className="z-10 hidden h-12 w-12 shrink-0 items-center justify-center rounded-full border border-white/10 bg-white/6 text-white transition-colors duration-300 hover:bg-white/12 lg:inline-flex"
                  aria-label="Slide suivant"
                >
                  <ArrowRight className="h-4 w-4" />
                </button>
              ) : null}
            </div>

            <div className="mt-8 flex flex-col items-center gap-4">
              <div className="flex items-center gap-2">
                {slides.map((slide, index) => (
                  <button
                    key={`${slide.kind}-${slide.id}-dot`}
                    type="button"
                    onClick={() => goTo(index)}
                    className={
                      index === activeIndex
                        ? 'h-2.5 w-8 rounded-full bg-white transition-all duration-300'
                        : 'h-2.5 w-2.5 rounded-full bg-white/28 transition-all duration-300 hover:bg-white/55'
                    }
                    aria-label={`Aller au slide ${index + 1}`}
                  />
                ))}
              </div>

              <div className="flex items-center gap-3 lg:hidden">
                <button
                  type="button"
                  onClick={() => goTo(activeIndex - 1)}
                  className="inline-flex h-11 w-11 items-center justify-center rounded-full border border-white/10 bg-white/6 text-white transition-colors duration-300 hover:bg-white/12"
                  aria-label="Slide précédent"
                >
                  <ArrowLeft className="h-4 w-4" />
                </button>
                <button
                  type="button"
                  onClick={() => goTo(activeIndex + 1)}
                  className="inline-flex h-11 w-11 items-center justify-center rounded-full border border-white/10 bg-white/6 text-white transition-colors duration-300 hover:bg-white/12"
                  aria-label="Slide suivant"
                >
                  <ArrowRight className="h-4 w-4" />
                </button>
              </div>
            </div>
          </div>
        ) : null}
      </div>

      <AlertSubscribeModal
        open={alertModalOpen}
        onClose={() => setAlertModalOpen(false)}
        source={alertSource}
        title={alertTitle}
        defaultNotifyLive={false}
        defaultNotifyEvents={true}
      />
    </section>
  );
}
