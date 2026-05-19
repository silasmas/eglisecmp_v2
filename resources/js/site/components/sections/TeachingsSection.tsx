import { useEffect, useState } from 'react';
import { motion } from 'framer-motion';
import { Link } from 'react-router-dom';
import { ArrowRight, Play, BookOpen, Radio, Clock } from 'lucide-react';
import CTAButton from '../ui/CTAButton';
import ReactionBar from '../ui/ReactionBar';
import SocialShareToolbar from '../ui/SocialShareToolbar';
import { useSiteSermons } from '../../hooks/useSiteSermons';
import ImageWithSkeleton from '../ui/ImageWithSkeleton';
import { TeachingsBlockSkeleton } from '../ui/Skeleton';
import { youtubeEmbedWithAutostart } from '../../lib/youtubeEmbed';

/**
 * Couleurs d’accent pour le point indicateur (reste cohérent avec la typologie de contenu).
 */
const categoryAccent: Record<string, string> = {
  'Culte dominical': 'bg-burgundy-500',
  'Enseignement': 'bg-gold-500',
  'Méditation': 'bg-emerald-500',
  Publication: 'bg-gold-500',
};

const revealContainer = {
  hidden: {},
  show: {
    transition: {
      staggerChildren: 0.08,
      delayChildren: 0.06,
    },
  },
};

const revealItem = {
  hidden: { opacity: 0, y: 24 },
  show: {
    opacity: 1,
    y: 0,
    transition: {
      duration: 0.55,
      ease: [0.22, 1, 0.36, 1] as const,
    },
  },
};

const revealSideItem = {
  hidden: { opacity: 0, y: 18, x: 10 },
  show: {
    opacity: 1,
    y: 0,
    x: 0,
    transition: {
      duration: 0.42,
      ease: [0.22, 1, 0.36, 1] as const,
    },
  },
};
/**
 * Format long pour la liste latérale (date de culte/prédication).
 *
 * @param iso Date courte `YYYY-MM-DD`.
 */
function formatPreachShort(iso: string): string {
  if (!iso) {
    return '—';
  }

  try {
    return new Intl.DateTimeFormat('fr-FR', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    }).format(new Date(iso));
  } catch {
    return iso;
  }
}

/** Icône du petit badge selon la catégorie (audio / sermon / méditation…). */
function CategoryGlyph({ category }: { category: string }) {
  let Icon = Play;
  if (category === 'Méditation') {
    Icon = Radio;
  } else if (category !== 'Culte dominical') {
    Icon = BookOpen;
  }

  return <Icon className="w-3.5 h-3.5 text-white" aria-hidden />;
}

/**
 * Bloc Messages récents : lecteur depuis les posts API ; lecture directe sur play ; titre vers la page message.
 */
export default function TeachingsSection() {
  const { sermons: liveSermons, loading } = useSiteSermons([], 6, false);
  const featured = liveSermons[0];
  const rest = liveSermons.slice(1, 6);
  const [mainPlaying, setMainPlaying] = useState(false);

  useEffect(() => {
    setMainPlaying(false);
  }, [featured?.id]);

  if (loading && liveSermons.length === 0) {
    return (
      <section className="relative overflow-hidden bg-surface-950 py-24">
        <div className="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div className="mb-12 text-center">
            <span className="mb-5 inline-block rounded-full border border-white/10 bg-white/10 px-4 py-1.5 text-[11px] font-semibold uppercase tracking-[0.15em] text-gold-300">
              Enseignements
            </span>
            <h2 className="font-heading text-3xl font-extrabold tracking-tight text-white sm:text-4xl">
              Messages récents
            </h2>
          </div>
          <TeachingsBlockSkeleton />
        </div>
      </section>
    );
  }

  if (!loading && liveSermons.length === 0) {
    return (
      <section className="relative overflow-hidden bg-surface-950 py-24">
        <div className="relative mx-auto max-w-7xl px-4 py-20 text-center text-surface-400 sm:px-6 lg:px-8">
          Aucun message publié pour le moment. Les contenus proviennent des publications actives dans l&apos;administration.
        </div>
      </section>
    );
  }

  if (!featured) {
    return null;
  }

  return (
    <section className="relative overflow-hidden bg-surface-950 py-24">
      <div className="absolute top-0 left-1/2 h-[400px] w-[800px] -translate-x-1/2 rounded-full bg-surface-800/30 blur-[120px]" />

      <div className="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div className="mb-12 text-center">
          <div className="mx-auto max-w-2xl">
            <span className="mb-5 inline-block rounded-full border border-white/10 bg-white/10 px-4 py-1.5 text-[11px] font-semibold uppercase tracking-[0.15em] text-gold-300">
              Enseignements
            </span>
            <h2 className="font-heading text-3xl font-extrabold leading-[1.1] tracking-tight text-white sm:text-4xl lg:text-[3.25rem]">
              Messages récents
            </h2>
            <p className="mx-auto mt-4 max-w-xl text-lg leading-relaxed text-surface-400">
              Retrouvez les prédications, méditations et enseignements de nos cultes.
            </p>
          </div>
        </div>

        <motion.div
          variants={revealContainer}
          initial="hidden"
          whileInView="show"
          viewport={{ once: true, margin: '-50px' }}
          className="grid grid-cols-1 gap-6 lg:h-[32rem] lg:grid-cols-12"
        >
          <motion.div
            variants={revealItem}
            className="group relative flex h-full flex-col overflow-hidden rounded-2xl border border-white/[0.04] bg-surface-900 shadow-[0_18px_45px_rgba(9,9,11,0.16)] lg:col-span-7"
          >
            <div className="relative aspect-[16/10] overflow-hidden lg:min-h-0 lg:flex-1 lg:aspect-auto">
              {mainPlaying && featured.youtubeEmbedUrl ? (
                <iframe
                  src={youtubeEmbedWithAutostart(featured.youtubeEmbedUrl)}
                  title={featured.title}
                  className="h-full w-full border-0"
                  allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                  referrerPolicy="strict-origin-when-cross-origin"
                  allowFullScreen
                />
              ) : (
                <>
                  <ImageWithSkeleton
                    src={featured.thumbnail}
                    alt={featured.title}
                    className="absolute inset-0 h-full w-full object-cover img-hover"
                  />
                  <div className="absolute inset-0 bg-gradient-to-t from-surface-950 via-surface-950/50 to-transparent" />
                  {featured.youtubeEmbedUrl ? (
                    <div className="absolute inset-0 flex items-center justify-center">
                      <button
                        type="button"
                        onClick={() => setMainPlaying(true)}
                        className="flex h-16 w-16 items-center justify-center rounded-full bg-burgundy-700/90 shadow-2xl backdrop-blur-md transition hover:scale-105 focus:outline-none focus:ring-2 focus:ring-white/40"
                        aria-label={`Lire la vidéo : ${featured.title}`}
                      >
                        <Play className="ml-1 h-7 w-7 text-white" fill="white" />
                      </button>
                    </div>
                  ) : null}
                </>
              )}
            </div>
            <div className="p-5">
              <div className="mb-3 flex items-center gap-3">
                <span className="inline-flex items-center gap-1.5 rounded-full bg-burgundy-500/20 px-2.5 py-1 text-[11px] font-semibold text-burgundy-300">
                  <Play className="h-3 w-3" aria-hidden /> YouTube
                </span>
                <span className="flex items-center gap-1 text-[12px] text-surface-500">
                  <Clock className="h-3 w-3" aria-hidden /> {featured.duration}
                </span>
              </div>
              <h3 className="font-heading text-xl font-bold leading-snug text-white sm:text-2xl">
                <Link
                  to={`/teachings/message/${featured.id}`}
                  className="transition-colors hover:text-burgundy-300"
                >
                  {featured.title}
                </Link>
              </h3>
              <p className="mt-2 text-sm text-surface-400">
                {featured.speaker} ·{' '}
                {new Date(featured.date).toLocaleDateString('fr-FR', {
                  day: 'numeric',
                  month: 'long',
                  year: 'numeric',
                })}
              </p>
              {featured.reactableKey ? (
                <div className="mt-4">
                  <ReactionBar reactableKey={featured.reactableKey} />
                </div>
              ) : null}
              <div className="mt-4">
                <SocialShareToolbar
                  title={featured.title}
                  description={featured.description}
                  sharePath={`/teachings/message/${featured.id}`}
                  compact
                />
              </div>
            </div>
          </motion.div>

          <div className="flex h-full flex-col gap-2.5 lg:col-span-5">
            {rest.map((sermon) => {
              const accent = categoryAccent[sermon.category] ?? categoryAccent['Culte dominical'];
              return (
                <motion.div
                  key={sermon.id}
                  variants={revealSideItem}
                  className="group flex flex-1 gap-3 rounded-xl border border-white/[0.06] bg-white/[0.04] p-2.5 transition-all duration-300 hover:border-white/[0.1] hover:bg-white/[0.08]"
                >
                  <div className="relative w-20 shrink-0 overflow-hidden rounded-lg aspect-video sm:w-24">
                    <ImageWithSkeleton
                      src={sermon.thumbnail}
                      alt={sermon.title}
                      className="h-full w-full object-cover img-hover-fast"
                    />
                    {sermon.youtubeEmbedUrl ? (
                      <Link
                        to={`/teachings/message/${sermon.id}?autoplay=1`}
                        className="absolute inset-0 z-10 flex items-center justify-center bg-black/30"
                        aria-label={`Lire la vidéo : ${sermon.title}`}
                      >
                        <span className="flex h-8 w-8 items-center justify-center rounded-full bg-white/20 backdrop-blur-sm">
                          <Play className="ml-0.5 h-3.5 w-3.5 text-white" fill="white" />
                        </span>
                      </Link>
                    ) : (
                      <div className="pointer-events-none absolute inset-0 flex items-center justify-center bg-black/30">
                        <span className="flex h-8 w-8 items-center justify-center rounded-full bg-white/20 backdrop-blur-sm">
                          <CategoryGlyph category={sermon.category} />
                        </span>
                      </div>
                    )}
                  </div>
                  <div className="flex min-w-0 flex-1 flex-col justify-center">
                    <div className="mb-1 flex items-center gap-2">
                      <span className={`h-1.5 w-1.5 rounded-full ${accent}`} />
                      <span className="text-[11px] font-medium uppercase tracking-wider text-surface-500">
                        {formatPreachShort(sermon.date)}
                      </span>
                    </div>
                    <Link
                      to={`/teachings/message/${sermon.id}`}
                      className="font-heading text-sm font-semibold leading-snug text-white transition-colors group-hover:text-burgundy-300 line-clamp-2"
                    >
                      {sermon.title}
                    </Link>
                    <p className="mt-0.5 text-[12px] text-surface-500">
                      {sermon.speaker} · {sermon.duration}
                    </p>
                  </div>
                </motion.div>
              );
            })}
          </div>
        </motion.div>

        <div className="mt-10 text-center">
          <CTAButton to="/teachings" variant="ghost" className="border border-white/15 text-white hover:bg-white/10 hover:text-white">
            Tout voir <ArrowRight className="h-4 w-4" />
          </CTAButton>
        </div>
      </div>
    </section>
  );
}
