import { useEffect, useMemo, useState } from 'react';
import { motion } from 'framer-motion';
import { ArrowRight, Play, MapPin, BookOpen, CalendarDays, Radio } from 'lucide-react';
import CTAButton from '../ui/CTAButton';
import HeroStripModal from '../ui/HeroStripModal';
import { churchInfo } from '../../data/content';
import { useHeroMeta } from '../../hooks/useHeroMeta';
import { useYoutubeLive } from '../../context/YoutubeLiveContext';
import type { HeroStripCard, HeroStripCards, YoutubeLivePayload } from '../../data/types';
import { buildLiveCountdownInfo, buildYoutubeLiveHeroInfo } from '../../lib/liveCountdown';

type StripModalKey = keyof HeroStripCards;

const stripTileClass =
  'flex w-full min-w-0 cursor-pointer text-left items-center gap-3 px-4 py-3.5 rounded-2xl backdrop-blur-md border transition-colors hover:bg-white/[0.12] focus:outline-none focus-visible:ring-2 focus-visible:ring-gold-300/80';

/**
 * Lit le libellé principal d'une tuile (API prioritaire, repli local).
 */
function tilePrimary(card: HeroStripCard | undefined, fallback: string): string {
  if (card?.tilePrimary && card.tilePrimary.trim() !== '') {
    return card.tilePrimary;
  }

  return fallback;
}

/**
 * Lit le sous-titre d'une tuile (API prioritaire, repli local).
 */
function tileSecondary(card: HeroStripCard | undefined, fallback: string): string {
  if (card?.tileSecondary && card.tileSecondary.trim() !== '') {
    return card.tileSecondary;
  }

  return fallback;
}

/**
 * Enrichit la carte live avec les données YouTube en cours.
 */
function mergeLiveCardWithYoutube(
  card: HeroStripCard | undefined,
  youtubeLive: YoutubeLivePayload,
): HeroStripCard | undefined {
  if (card === undefined) {
    return undefined;
  }

  return {
    ...card,
    status: 'live',
    embedUrl: youtubeLive.embedUrl,
    embedKind: 'youtube',
    linkUrl: youtubeLive.watchUrl,
    title: youtubeLive.title.trim() !== '' ? youtubeLive.title : card.title,
    tilePrimary: 'En direct',
    tileSecondary: youtubeLive.title.trim() !== '' ? youtubeLive.title : 'Rejoignez le culte',
    modalBadge: 'En direct',
    modalBadgeTone: 'live',
  };
}

/**
 * Section hero de l'accueil avec bandeau dynamique (live, programme, lecture, localisation).
 */
export default function HeroSection() {
  const [now, setNow] = useState(() => new Date());
  const [stripModal, setStripModal] = useState<StripModalKey | null>(null);
  const { meta: heroMeta } = useHeroMeta();
  const { isYoutubeLive, live: youtubeLive, openLiveModal } = useYoutubeLive();

  useEffect(() => {
    const timer = window.setInterval(() => {
      setNow(new Date());
    }, 1000);

    return () => window.clearInterval(timer);
  }, []);

  const strip = heroMeta.stripCards;
  const liveCard = strip?.live;
  const eventCard = strip?.event;
  const readingCard = strip?.reading;
  const locationCard = strip?.location;

  const isLiveNow =
    isYoutubeLive ||
    heroMeta.youtubeLive != null ||
    liveCard?.status === 'live' ||
    heroMeta.liveTiming?.status === 'live';

  const activeYoutubeLive = youtubeLive ?? heroMeta.youtubeLive ?? null;

  const liveCountdown = useMemo(() => {
    if (activeYoutubeLive !== null) {
      return buildYoutubeLiveHeroInfo(activeYoutubeLive);
    }

    return buildLiveCountdownInfo(heroMeta.liveTiming?.targetIso, now, isLiveNow, {
      programName: heroMeta.liveTiming?.programName ?? liveCard?.title,
      scheduledLabel: heroMeta.liveTiming?.scheduledLabel ?? liveCard?.subtitle,
      timeLabel: heroMeta.liveTiming?.timeLabel,
      dayLabel: heroMeta.liveTiming?.dayLabel,
      startIso: heroMeta.liveTiming?.startIso,
    });
  }, [heroMeta.liveTiming, liveCard?.subtitle, liveCard?.title, isLiveNow, activeYoutubeLive, now]);

  const livePrimary = liveCountdown.tileHeadline;
  const liveSecondary = liveCountdown.tileContext;
  const eventTitle = tilePrimary(eventCard, 'Programme de la semaine');
  const eventSubtitle = tileSecondary(eventCard, 'Consultez nos rendez-vous');
  const readingTitle = tilePrimary(readingCard, 'Lecture du jour');
  const readingSubtitle = tileSecondary(
    readingCard,
    heroMeta.verse?.excerpt?.trim() || 'Cliquez ici pour découvrir la parole du jour ✨',
  );
  const locationTitle = tilePrimary(locationCard, 'Nous trouver');
  const locationSubtitle = tileSecondary(locationCard, churchInfo.shortAddress);

  const liveModalCard = useMemo(() => {
    if (stripModal !== 'live') {
      return null;
    }

    const base = strip?.live;
    if (base === undefined) {
      return null;
    }

    if (isYoutubeLive && youtubeLive !== null) {
      return mergeLiveCardWithYoutube(base, youtubeLive) ?? base;
    }

    if (heroMeta.youtubeLive != null) {
      return mergeLiveCardWithYoutube(base, heroMeta.youtubeLive) ?? base;
    }

    return base;
  }, [heroMeta.youtubeLive, isYoutubeLive, strip?.live, stripModal, youtubeLive]);

  const modalCard: HeroStripCard | null = stripModal === 'live' ? liveModalCard : stripModal && strip?.[stripModal] ? strip[stripModal] : null;

  const openLocationMap = () => {
    const mapUrl = locationCard?.mapUrl?.trim();

    if (mapUrl) {
      window.open(mapUrl, '_blank', 'noopener,noreferrer');
    }
  };

  const openLiveExperience = () => {
    if (isYoutubeLive && youtubeLive !== null) {
      openLiveModal();
      return;
    }

    if (heroMeta.youtubeLive != null) {
      setStripModal('live');
      return;
    }

    if (heroMeta.stripCards) {
      setStripModal('live');
    }
  };

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
            {isLiveNow ? (
              <motion.button
                type="button"
                initial={{ opacity: 0, y: 12 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.45 }}
                onClick={openLiveExperience}
                className="fab-blink mb-6 inline-flex items-center gap-2.5 rounded-full border border-red-400/50 bg-red-600/95 px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-white shadow-lg shadow-red-900/30"
              >
                <span className="relative flex h-2.5 w-2.5">
                  <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-white opacity-75" />
                  <span className="relative inline-flex h-2.5 w-2.5 rounded-full bg-white" />
                </span>
                <Radio className="h-4 w-4" aria-hidden />
                Culte en direct — Rejoignez-nous
              </motion.button>
            ) : null}

            <motion.h1
              initial={{ opacity: 0, y: 32 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.75, delay: 0.1, ease: [0.25, 0.46, 0.45, 0.94] }}
              className="font-heading font-extrabold text-[clamp(3rem,6.5vw,5.5rem)] text-white leading-[1.0] tracking-tight"
            >
              {isLiveNow ? (
                <>
                  Nous sommes <span className="text-red-400">en live</span>
                </>
              ) : (
                <>
                  Bienvenue à <span className="text-gold-300">Philadelphie</span>
                </>
              )}
            </motion.h1>

            <motion.p
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.6, delay: 0.25 }}
              className="mt-6 text-lg text-white/60 leading-relaxed max-w-xl mx-auto"
            >
              {isLiveNow
                ? (activeYoutubeLive?.title?.trim() !== ''
                    ? `${activeYoutubeLive.title} — Cliquez pour regarder la diffusion en direct.`
                    : 'Le culte est en cours sur YouTube. Rejoignez-nous dès maintenant en direct.')
                : "L'amour fraternel au service des nations. Rejoignez une communauté vivante, engagée dans la foi et porteuse d'espérance."}
            </motion.p>

            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.6, delay: 0.35 }}
              className="mt-10 flex flex-wrap justify-center gap-3"
            >
              {isLiveNow ? (
                <CTAButton
                  onClick={openLiveExperience}
                  variant="primary"
                  size="lg"
                  className="bg-red-700 hover:bg-red-600"
                >
                  <Radio className="w-4 h-4" /> Regarder le live
                </CTAButton>
              ) : null}
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
            <button
              type="button"
              className={`${stripTileClass} ${
                isLiveNow
                  ? 'bg-red-900/50 border-red-500/40 ring-1 ring-red-500/30'
                  : 'bg-burgundy-800/34 border-burgundy-600/25'
              }`}
              onClick={openLiveExperience}
            >
              <div className={`w-9 h-9 rounded-xl flex items-center justify-center shrink-0 ${isLiveNow ? 'bg-red-700/50' : 'bg-burgundy-700/35'}`}>
                <span className="relative flex h-2.5 w-2.5">
                  <span className={`absolute inline-flex h-full w-full rounded-full opacity-75 ${isLiveNow ? 'animate-ping bg-red-400' : 'animate-ping bg-red-500'}`} />
                  <span className="relative inline-flex h-2.5 w-2.5 rounded-full bg-red-600" />
                </span>
              </div>
              <div className="min-w-0">
                <p className={`text-sm font-semibold tabular-nums ${isLiveNow ? 'text-red-100' : 'text-white'}`}>
                  {livePrimary}
                </p>
                <p className="text-white/55 text-[12px] mt-0.5 line-clamp-2">{liveSecondary}</p>
              </div>
            </button>

            <button
              type="button"
              className={`${stripTileClass} ${
                eventCard?.status === 'live' ? 'bg-emerald-900/25 border-emerald-500/25' : 'bg-white/[0.08] border-white/10'
              }`}
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
                <p className="text-white text-sm font-semibold line-clamp-1">{eventTitle}</p>
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
        onOpenMap={stripModal === 'location' ? openLocationMap : undefined}
        showLivePlayer={stripModal === 'live' && isLiveNow}
        liveCountdownInfo={stripModal === 'live' ? liveCountdown : undefined}
        showNotifyButton={stripModal === 'event'}
      />
    </section>
  );
}
