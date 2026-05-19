import { useEffect, useMemo, useState } from 'react';
import { motion } from 'framer-motion';
import { ArrowRight, Play, MapPin, BookOpen, CalendarDays } from 'lucide-react';
import CTAButton from '../ui/CTAButton';
import HeroStripModal from '../ui/HeroStripModal';
import { churchInfo, events as fallbackEvents } from '../../data/content';
import { useHeroMeta } from '../../hooks/useHeroMeta';
import { useSiteEvents } from '../../hooks/useSiteEvents';
import type { Event, HeroLiveSlot, HeroStripCard, HeroStripCards } from '../../data/types';
import { excerptText, formatLivePrimaryLabel } from '../../lib/placeholderImage';

const DEFAULT_LIVE_SLOTS: HeroLiveSlot[] = [
  { weekday: 3, hour: 17, minute: 30, label: 'Mercredi', subtitle: '' },
  { weekday: 4, hour: 17, minute: 30, label: 'Jeudi', subtitle: '' },
  { weekday: 0, hour: 8, minute: 0, label: 'Dimanche', subtitle: '' },
];

type NextLiveState = HeroLiveSlot & { start: Date };

type StripModalKey = keyof HeroStripCards;

/**
 * Calcule le prochain créneau live à partir des créneaux configurés (API ou valeurs par défaut).
 *
 * @param now Horloge de référence.
 * @param slots Liste des créneaux (jour de la semaine 0–6, heure, minute).
 * @returns Créneau avec date de prochaine occurrence, ou null si aucun créneau.
 */
function getNextLiveFromSlots(now: Date, slots: HeroLiveSlot[]): NextLiveState | null {
  if (slots.length === 0) {
    return null;
  }

  const candidates = slots.map((slot) => {
    const next = new Date(now);
    const diff = (slot.weekday - now.getDay() + 7) % 7;
    next.setDate(now.getDate() + diff);
    next.setHours(slot.hour, slot.minute, 0, 0);

    if (next <= now) {
      next.setDate(next.getDate() + 7);
    }

    return { ...slot, start: next };
  });

  const sorted = candidates.sort((a, b) => a.start.getTime() - b.start.getTime());

  return sorted[0] ?? null;
}

/**
 * Compte à rebours texte HH:MM:SS jusqu'à la date cible.
 *
 * @param target Date / heure cible.
 * @param now Instant présent.
 * @returns Chaîne formatée.
 */
function getCountdown(target: Date, now: Date): string {
  const diff = Math.max(0, target.getTime() - now.getTime());
  const totalSeconds = Math.floor(diff / 1000);
  const hours = Math.floor(totalSeconds / 3600);
  const minutes = Math.floor((totalSeconds % 3600) / 60);
  const seconds = totalSeconds % 60;

  return [hours, minutes, seconds].map((value) => String(value).padStart(2, '0')).join(':');
}

/**
 * Prochain événement futur dans une liste triée par date de début.
 *
 * @param now Référence temporelle.
 * @param eventsList Événements issus de l'API (même forme que le mock).
 * @returns Événement ou null.
 */
function getNextEvent(now: Date, eventsList: Event[]): Event | null {
  const upcomingEvents = eventsList
    .map((event) => {
      const firstSegment = event.time.split('-')[0]?.trim() ?? event.time;
      const timeParts = firstSegment.split(':');
      const startHour = Number.parseInt(timeParts[0] ?? '0', 10);
      const startMinute = Number.parseInt(timeParts[1] ?? '0', 10);
      const start = new Date(event.date);
      start.setHours(startHour, startMinute, 0, 0);

      return { ...event, start };
    })
    .filter((event) => event.start.getTime() > now.getTime())
    .sort((a, b) => a.start.getTime() - b.start.getTime());

  return upcomingEvents[0] ?? null;
}

const stripTileClass =
  'flex w-full min-w-0 cursor-pointer text-left items-center gap-3 px-4 py-3.5 rounded-2xl backdrop-blur-md border transition-colors hover:bg-white/[0.12] focus:outline-none focus-visible:ring-2 focus-visible:ring-gold-300/80';

export default function HeroSection() {
  const [now, setNow] = useState(() => new Date());
  const [stripModal, setStripModal] = useState<StripModalKey | null>(null);
  const { meta: heroMeta } = useHeroMeta();
  const { events: apiEvents } = useSiteEvents(fallbackEvents, 24);

  useEffect(() => {
    const timer = window.setInterval(() => {
      setNow(new Date());
    }, 1000);

    return () => window.clearInterval(timer);
  }, []);

  const liveSlots = heroMeta.liveSlots.length > 0 ? heroMeta.liveSlots : DEFAULT_LIVE_SLOTS;
  const nextLive = useMemo(() => getNextLiveFromSlots(now, liveSlots), [now, liveSlots]);
  const nextEvent = useMemo(() => getNextEvent(now, apiEvents), [now, apiEvents]);

  const countdownTarget = useMemo(() => {
    const iso = heroMeta.liveTiming?.targetIso;

    if (iso) {
      const parsed = new Date(iso);

      if (!Number.isNaN(parsed.getTime())) {
        return parsed;
      }
    }

    return nextLive?.start ?? null;
  }, [heroMeta.liveTiming?.targetIso, nextLive?.start]);

  const countdown = useMemo(() => {
    if (!countdownTarget) {
      return '00:00:00';
    }

    return getCountdown(countdownTarget, now);
  }, [countdownTarget, now]);

  const livePrimary = useMemo(() => {
    const targetIso = heroMeta.liveTiming?.targetIso ?? countdownTarget?.toISOString();

    return formatLivePrimaryLabel(
      targetIso,
      now,
      countdown,
      heroMeta.liveTiming?.displayMode === 'days' ? heroMeta.liveTiming.daysUntil : null,
    );
  }, [heroMeta.liveTiming, countdownTarget, countdown, now]);

  const verse = heroMeta.verse;
  const strip = heroMeta.stripCards;

  const liveSubtitle =
    nextLive && nextLive.subtitle && nextLive.subtitle.trim() !== ''
      ? nextLive.subtitle
      : nextLive?.label === 'Dimanche'
        ? 'Culte dominical'
        : "Culte d'enseignement";

  const modalCard: HeroStripCard | null =
    stripModal && strip?.[stripModal]
      ? strip[stripModal]
      : null;

  const eventCard = strip?.event;
  const eventTitle =
    eventCard && eventCard.title.trim() !== ''
      ? eventCard.title
      : nextEvent
        ? 'Prochain événement'
        : 'Événement à venir';
  const eventSubtitle =
    eventCard && eventCard.subtitle.trim() !== ''
      ? eventCard.subtitle
      : nextEvent
        ? `${nextEvent.title} · ${new Date(nextEvent.date).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })}`
        : 'Bientôt annoncé';

  const readingCard = strip?.reading;
  const readingTitle = 'Lecture du jour';
  const readingExcerpt =
    verse?.excerpt?.trim() ||
    (verse?.text?.trim() ? excerptText(verse.text, 100) : '') ||
    (readingCard?.subtitle?.trim() ?? '');
  const readingSubtitle =
    readingExcerpt !== ''
      ? readingExcerpt
      : 'Publiez une lecture du jour dans l’admin (fenêtre 24 h).';

  const locationCard = strip?.location;
  const locationTitle =
    locationCard && locationCard.title.trim() !== '' ? locationCard.title : 'Nous trouver';
  const locationSubtitle =
    locationCard && locationCard.subtitle.trim() !== '' ? locationCard.subtitle : churchInfo.shortAddress;

  return (
    <section className="relative min-h-screen flex flex-col overflow-hidden">
      <div className="absolute inset-0">
        <video autoPlay muted loop playsInline className="w-full h-full object-cover">
          <source src="https://videos.pexels.com/video-files/29403412/12663538_1920_1080_30fps.mp4" type="video/mp4" />
          <img
            src="https://images.unsplash.com/photo-1438232992991-995b7058bbb3?w=1800&h=1100&fit=crop"
            alt=""
            className="w-full h-full object-cover"
          />
        </video>
        <div className="absolute inset-0 bg-surface-950/60" />
        <div className="absolute inset-0 bg-gradient-to-t from-surface-950/90 via-transparent to-surface-950/30" />
        <div className="absolute -top-32 left-1/2 -translate-x-1/2 w-[900px] h-[700px] bg-burgundy-800/10 rounded-full blur-[200px] pointer-events-none" />
      </div>

      <div className="relative flex-1 flex items-center">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 w-full pt-32 pb-12">
          <div className="max-w-[760px] mx-auto text-center">
            <motion.h1
              initial={{ opacity: 0, y: 32 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.75, delay: 0.1, ease: [0.25, 0.46, 0.45, 0.94] }}
              className="font-heading font-extrabold text-[clamp(3rem,6.5vw,5.5rem)] text-white leading-[1.0] tracking-tight"
            >
              Bienvenue à <span className="text-gold-300">Philadelphie</span>
            </motion.h1>

            <motion.p
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.6, delay: 0.25 }}
              className="mt-6 text-lg text-white/60 leading-relaxed max-w-xl mx-auto"
            >
              L'amour fraternel au service des nations. Rejoignez une communauté
              vivante, engagée dans la foi et porteuse d'espérance.
            </motion.p>

            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.6, delay: 0.35 }}
              className="mt-10 flex flex-wrap justify-center gap-3"
            >
              <CTAButton to="/rendez-vous" variant="primary" size="lg">
                Prendre rendez-vous <ArrowRight className="w-4 h-4" />
              </CTAButton>
              <CTAButton
                to="/teachings"
                variant="ghost"
                size="lg"
                className="text-white border border-white/25 hover:bg-white/10 hover:text-white backdrop-blur-sm"
              >
                <Play className="w-4 h-4" /> Regarder un message
              </CTAButton>
            </motion.div>
          </div>
        </div>
      </div>

      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.6, delay: 0.5 }}
        className="relative pb-12 sm:pb-16"
      >
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
            {nextLive && (
              <button
                type="button"
                className={`${stripTileClass} bg-burgundy-800/34 border-burgundy-600/25`}
                onClick={() => {
                  if (heroMeta.stripCards) {
                    setStripModal('live');
                  }
                }}
              >
                <div className="w-9 h-9 rounded-xl bg-burgundy-700/35 flex items-center justify-center shrink-0">
                  <span className="relative flex h-2.5 w-2.5">
                    <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-500 opacity-75" />
                    <span className="relative inline-flex h-2.5 w-2.5 rounded-full bg-red-600" />
                  </span>
                </div>
                <div className="min-w-0">
                  <p className="text-white text-sm font-semibold">{livePrimary}</p>
                  <p className="text-white/55 text-[12px] mt-0.5">
                    {liveSubtitle} · {String(nextLive.hour).padStart(2, '0')}:{String(nextLive.minute).padStart(2, '0')}
                  </p>
                </div>
              </button>
            )}

            <button
              type="button"
              className={`${stripTileClass} bg-white/[0.08] border-white/10`}
              onClick={() => {
                if (heroMeta.stripCards) {
                  setStripModal('event');
                }
              }}
            >
              <div className="w-9 h-9 rounded-xl bg-white/10 flex items-center justify-center shrink-0">
                <CalendarDays className="w-4 h-4 text-white/50" />
              </div>
              <div className="min-w-0">
                <p className="text-white text-sm font-semibold">{eventTitle}</p>
                <p className="text-white/55 text-[12px] mt-0.5 line-clamp-2">{eventSubtitle}</p>
              </div>
            </button>

            <button
              type="button"
              className={`${stripTileClass} bg-white/[0.08] border-white/10`}
              onClick={() => {
                if (heroMeta.stripCards) {
                  setStripModal('reading');
                }
              }}
            >
              <div className="w-9 h-9 rounded-xl bg-white/10 flex items-center justify-center shrink-0">
                <BookOpen className="w-4 h-4 text-white/50" />
              </div>
              <div className="min-w-0">
                <p className="text-white text-sm font-semibold truncate">{readingTitle}</p>
                <p className="text-white/55 text-[12px] mt-0.5 line-clamp-2">{readingSubtitle}</p>
              </div>
            </button>

            <button
              type="button"
              className={`${stripTileClass} bg-white/[0.08] border-white/10`}
              onClick={() => {
                if (heroMeta.stripCards) {
                  setStripModal('location');
                }
              }}
            >
              <div className="w-9 h-9 rounded-xl bg-white/10 flex items-center justify-center shrink-0">
                <MapPin className="w-4 h-4 text-white/50" />
              </div>
              <div className="min-w-0">
                <p className="text-white text-sm font-semibold">{locationTitle}</p>
                <p className="mt-0.5 text-white/55 text-[12px] line-clamp-2">{locationSubtitle}</p>
              </div>
            </button>
          </div>
        </div>
      </motion.div>

      <HeroStripModal
        open={stripModal !== null}
        onClose={() => setStripModal(null)}
        card={modalCard}
        showReadingShare={stripModal === 'reading'}
      />
    </section>
  );
}
