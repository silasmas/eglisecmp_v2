import { useEffect, useState } from 'react';
import { motion } from 'framer-motion';
import { Play, MapPin, HandHeart, Sparkles, ArrowRight } from 'lucide-react';
import { Link } from 'react-router-dom';
import SectionHeader from '../ui/SectionHeader';
import CTAButton from '../ui/CTAButton';
import ReactionBar from '../ui/ReactionBar';
import SocialShareToolbar from '../ui/SocialShareToolbar';
import { useFeaturedPosts } from '../../hooks/useFeaturedPosts';
import ImageWithSkeleton from '../ui/ImageWithSkeleton';
import { FeaturedHeroSkeleton } from '../ui/Skeleton';
import type { FeaturedPostCard } from '../../data/types';
import { youtubeEmbedWithAutostart } from '../../lib/youtubeEmbed';

const smallActions = [
  {
    icon: MapPin,
    title: 'Devenir membre',
    description: "Faites le pas pour vous enraciner dans la vie de l'église.",
    href: '/join',
  },
  {
    icon: HandHeart,
    title: 'Demande de prière',
    description: 'Partagez vos sujets de prière avec notre équipe.',
    href: '/requete-de-priere',
  },
  {
    icon: Sparkles,
    title: 'Découvrir Bunda',
    description: 'Notre conférence annuelle qui rassemble des milliers de fidèles.',
    href: '/events/bunda',
  },
];

/**
 * Carte « À la une » avec lecture vidéo YouTube sur place lorsque l’API expose un embed.
 *
 * @param featured Données carte programmée depuis l’admin.
 */
function FeaturedHeroCard({ featured }: { featured: FeaturedPostCard }) {
  const [playing, setPlaying] = useState(false);
  const sharePath = featured.href.startsWith('/') ? featured.href : '/teachings';
  const embedUrl = featured.youtubeEmbedUrl ?? null;
  const hasVideo = Boolean(embedUrl && embedUrl.trim() !== '');

  useEffect(() => {
    setPlaying(false);
  }, [featured.id]);

  if (hasVideo) {
    return (
      <div className="relative flex min-h-[240px] flex-col overflow-hidden rounded-3xl shadow-md transition-shadow duration-500 hover:shadow-xl lg:min-h-[280px] lg:flex-row">
        <div className="relative w-full min-h-[220px] bg-black lg:w-[53%] lg:min-h-[280px]">
          {playing && embedUrl ? (
            <iframe
              src={youtubeEmbedWithAutostart(embedUrl)}
              title={featured.title}
              className="absolute inset-0 h-full w-full border-0"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
              referrerPolicy="strict-origin-when-cross-origin"
              allowFullScreen
            />
          ) : (
            <>
              <ImageWithSkeleton
                src={featured.image}
                alt=""
                className="absolute inset-0 h-full w-full object-cover"
              />
              <div className="absolute inset-0 bg-black/35" />
              <div className="absolute inset-0 flex items-center justify-center">
                <button
                  type="button"
                  onClick={() => setPlaying(true)}
                  className="flex h-16 w-16 items-center justify-center rounded-full bg-burgundy-700/92 shadow-2xl backdrop-blur-md transition hover:scale-105 focus:outline-none focus:ring-2 focus:ring-white/40"
                  aria-label={`Lire la vidéo sur la page : ${featured.title}`}
                >
                  <Play className="ml-1 h-7 w-7 text-white" fill="white" />
                </button>
              </div>
            </>
          )}
        </div>
        <div className="relative flex flex-1 flex-col justify-center bg-gradient-to-br from-surface-950 via-surface-900 to-surface-950 px-8 py-8 sm:p-10">
          <div className="inline-flex items-center gap-2 self-start rounded-full border border-white/10 bg-white/10 px-3 py-1.5 text-xs font-medium text-white/80 backdrop-blur-sm mb-4">
            <Play className="w-3 h-3 shrink-0" /> À la une
          </div>
          <h3 className="font-heading font-bold text-white text-2xl sm:text-3xl leading-tight">{featured.title}</h3>
          <p className="mt-1.5 text-white/55 text-sm max-w-lg line-clamp-3">{featured.excerpt}</p>
          <p className="mt-2 text-white/45 text-xs">{featured.speaker}</p>
          {featured.reactableKey ? (
            <div className="mt-4 max-w-md">
              <ReactionBar reactableKey={featured.reactableKey} />
            </div>
          ) : null}
          <div className="mt-5 flex flex-wrap items-center gap-3">
            <Link
              to={sharePath}
              className="inline-flex items-center gap-2 text-sm font-semibold text-gold-300 transition hover:text-gold-200"
            >
              Ouvrir la fiche message <ArrowRight className="h-4 w-4" />
            </Link>
          </div>
        </div>
      </div>
    );
  }

  return (
    <Link
      to={sharePath}
      className="group relative flex z-0 min-h-[220px] flex-col justify-end overflow-hidden rounded-3xl shadow-md transition-all duration-500 hover:shadow-xl sm:min-h-[260px]"
    >
      <div className="pointer-events-none absolute inset-0 overflow-hidden rounded-[inherit]">
        <ImageWithSkeleton
          src={featured.image}
          alt=""
          className="absolute inset-0 h-full w-full object-cover img-hover"
        />
      </div>
      <div className="absolute inset-0 bg-gradient-to-r from-surface-950/92 via-surface-950/60 to-surface-950/20" />
      <div className="relative p-8 sm:p-10 flex items-end justify-between gap-6">
        <div>
          <div className="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/10 backdrop-blur-sm border border-white/10 text-xs text-white/80 font-medium mb-4">
            <Play className="w-3 h-3" /> À la une
          </div>
          <h3 className="font-heading font-bold text-white text-2xl sm:text-3xl leading-tight">{featured.title}</h3>
          <p className="mt-1.5 text-white/55 text-sm max-w-md line-clamp-2">{featured.excerpt}</p>
          <p className="mt-2 text-white/45 text-xs">{featured.speaker}</p>
          {featured.reactableKey ? (
            <div className="mt-4 max-w-md">
              <ReactionBar reactableKey={featured.reactableKey} />
            </div>
          ) : null}
        </div>
        <div className="hidden sm:flex items-center gap-2 text-white/70 text-sm font-semibold group-hover:text-white group-hover:gap-3 transition-all duration-300 shrink-0 pointer-events-none">
          Ouvrir <ArrowRight className="w-4 h-4" />
        </div>
      </div>
    </Link>
  );
}

export default function QuickActionsSection() {
  const { posts, loading } = useFeaturedPosts(6);
  const featured = posts[0];

  return (
    <section className="py-24">
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <SectionHeader
          badge="Accès rapide"
          title="Contenu à la une"
          description="Prédications, vidéos, événements et ressources d'enseignement."
        />

        <div className="space-y-4">
          <motion.div
            initial={{ opacity: 0, y: 40 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true, margin: '-80px' }}
            transition={{ duration: 0.7, ease: [0.22, 1, 0.36, 1] }}
          >
            {loading ? (
              <FeaturedHeroSkeleton />
            ) : featured ? (
              <div className="relative">
                <FeaturedHeroCard featured={featured} />
                <div className="pointer-events-auto absolute top-6 right-6 z-20 max-w-[min(92vw,20rem)]">
                  <SocialShareToolbar
                    title={featured.title}
                    description={featured.excerpt}
                    sharePath={featured.href.startsWith('/') ? featured.href : '/teachings'}
                    compact
                  />
                </div>
              </div>
            ) : (
              <Link
                to="/teachings"
                className="group relative flex flex-col justify-end rounded-3xl overflow-hidden h-[220px] sm:h-[260px] shadow-md hover:shadow-xl transition-all duration-500"
              >
                <img
                  src="https://images.unsplash.com/photo-1507692049790-de58290a4334?w=1200&h=400&fit=crop"
                  alt=""
                  className="absolute inset-0 w-full h-full object-cover img-hover"
                />
                <div className="absolute inset-0 bg-gradient-to-r from-surface-950/92 via-surface-950/60 to-surface-950/20" />
                <div className="relative p-8 sm:p-10 flex items-end justify-between gap-6">
                  <div>
                    <div className="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/10 backdrop-blur-sm border border-white/10 text-xs text-white/80 font-medium mb-4">
                      <Play className="w-3 h-3" /> Messages & Enseignements
                    </div>
                    <h3 className="font-heading font-bold text-white text-2xl sm:text-3xl leading-tight">
                      Regarder les messages
                    </h3>
                    <p className="mt-1.5 text-white/55 text-sm max-w-md">
                      Accédez à nos enseignements, méditations et cultes en ligne.
                    </p>
                  </div>
                  <div className="hidden sm:flex items-center gap-2 text-white/70 text-sm font-semibold group-hover:text-white group-hover:gap-3 transition-all duration-300 shrink-0">
                    Accéder <ArrowRight className="w-4 h-4" />
                  </div>
                </div>
              </Link>
            )}
          </motion.div>

          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
            {smallActions.map((action, i) => (
              <motion.div
                key={action.title}
                initial={{ opacity: 0, y: 36 }}
                whileInView={{ opacity: 1, y: 0 }}
                whileHover={{ y: -4, transition: { type: 'spring', stiffness: 400, damping: 25 } }}
                viewport={{ once: true, margin: '-80px' }}
                transition={{ duration: 0.6, ease: [0.22, 1, 0.36, 1], delay: i * 0.1 }}
              >
                <Link
                  to={action.href}
                  className="group flex flex-col h-full rounded-3xl bg-white border border-surface-200 shadow-sm hover:shadow-md hover:border-surface-300 p-6 transition-shadow duration-300"
                >
                  <div className="w-11 h-11 rounded-2xl bg-surface-100 border border-surface-200 flex items-center justify-center mb-4 group-hover:bg-surface-200 transition-colors">
                    <action.icon className="w-5 h-5 text-surface-700" />
                  </div>
                  <h3 className="font-heading font-bold text-surface-900 text-lg mb-1.5">{action.title}</h3>
                  <p className="text-surface-500 text-[13px] leading-relaxed flex-1">{action.description}</p>
                  <span className="mt-4 inline-flex items-center gap-1.5 text-[13px] font-semibold text-surface-400 group-hover:text-surface-700 group-hover:gap-2 transition-all duration-300">
                    En savoir plus <ArrowRight className="w-3.5 h-3.5" />
                  </span>
                </Link>
              </motion.div>
            ))}
          </div>

          {posts.length > 1 && (
            <div className="flex flex-wrap justify-center gap-2 pt-2">
              {posts.slice(1, 5).map((p) => (
                <CTAButton key={p.id} href={p.href} variant="secondary" size="sm" className="max-w-[220px] truncate">
                  {p.title}
                </CTAButton>
              ))}
            </div>
          )}
        </div>
      </div>
    </section>
  );
}
