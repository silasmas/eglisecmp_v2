import { useEffect, useState } from 'react';
import { motion } from 'framer-motion';
import { Calendar, MapPin, ArrowRight, Star, Sparkles } from 'lucide-react';
import PageHero from '../components/ui/PageHero';
import CTAButton from '../components/ui/CTAButton';
import ImageWithSkeleton from '../components/ui/ImageWithSkeleton';
import { fetchSiteBunda } from '../lib/siteApi';
import type { BundaEdition, BundaPageData, TeachingsPlaylistGroup } from '../data/types';
import YoutubePlaylistGrid from '../components/teachings/YoutubePlaylistGrid';
import '../styles/youtube-playlist-grid.css';

const FALLBACK_HERO =
  'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=1400&h=600&fit=crop';

/**
 * Transforme une édition Bunda API en groupe playlist pour la grille empilée.
 *
 * @param edition Édition Bunda sérialisée.
 */
function editionToPlaylistGroup(edition: BundaEdition): TeachingsPlaylistGroup {
  return {
    eventId: edition.id,
    title: edition.title,
    description: edition.description,
    thumbnail: edition.image.trim() !== '' ? edition.image : FALLBACK_HERO,
    videoCount: edition.videoCount,
    visibility: 'Conférence',
    items: [],
  };
}

/**
 * Page publique Bunda : dernière édition, annonce à venir et archives.
 */
export default function BundaPage() {
  const [data, setData] = useState<BundaPageData | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let cancelled = false;
    async function load() {
      try {
        const payload = await fetchSiteBunda();
        if (!cancelled) {
          setData(payload);
        }
      } catch {
        if (!cancelled) {
          setData(null);
        }
      } finally {
        if (!cancelled) {
          setLoading(false);
        }
      }
    }
    void load();
    return () => {
      cancelled = true;
    };
  }, []);

  const latest = data?.latestEdition ?? null;
  const heroImage =
    latest !== null && latest.image.trim() !== '' ? latest.image : FALLBACK_HERO;
  const heroTitle = latest?.title ?? 'Conférence Bunda';
  const ctaHref = latest?.contentHref ?? '/join';
  const ctaLabel = latest?.buttonLabel ?? "S'inscrire";
  const upcoming = data?.upcoming ?? {
    title: 'Bunda',
    monthLabel: 'Novembre',
    year: new Date().getFullYear(),
    description: '',
  };

  return (
    <>
      <PageHero
        badge="Bunda"
        title={heroTitle}
        description="Notre conférence annuelle phare qui rassemble des milliers de fidèles autour de la Parole de Dieu."
        backgroundImage={heroImage}
      />

      <section className="py-24">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div className="grid items-start gap-16 lg:grid-cols-2">
            <motion.div
              initial={{ opacity: 0, x: -20 }}
              whileInView={{ opacity: 1, x: 0 }}
              viewport={{ once: true }}
              transition={{ duration: 0.5 }}
            >
              <h2 className="mb-6 font-heading text-3xl font-semibold leading-tight text-surface-900 sm:text-4xl">
                Un moment unique de communion et de célébration
              </h2>
              <p className="mb-6 text-lg leading-relaxed text-surface-600">
                Bunda est la conférence annuelle du Centre Missionnaire Philadelphie. C'est un événement majeur qui
                rassemble notre communauté et des invités de marque pour des jours d'enseignement, de louange et de
                prière intenses.
              </p>
              <p className="mb-8 leading-relaxed text-surface-500">
                Chaque édition de Bunda est un tournant spirituel pour des milliers de participants. Des orateurs
                puissants, une louange vibrante et la présence tangible de Dieu font de cet événement un rendez-vous
                incontournable pour tout chrétien.
              </p>

              <div className="mb-10 space-y-4">
                <div className="flex items-center gap-4 text-surface-600">
                  <Calendar className="h-5 w-5 shrink-0 text-burgundy-600" />
                  <span>
                    {data !== null
                      ? `Prochaine édition : ${upcoming.monthLabel} ${upcoming.year}`
                      : 'Prochaine édition en novembre'}
                  </span>
                </div>
                <div className="flex items-center gap-4 text-surface-600">
                  <MapPin className="h-5 w-5 shrink-0 text-burgundy-600" />
                  <span>Centre Missionnaire Philadelphie, Kintambo, Kinshasa</span>
                </div>
              </div>

              {latest?.contentHref ? (
                <CTAButton to={latest.contentHref} size="lg">
                  {ctaLabel} <ArrowRight className="h-4 w-4" />
                </CTAButton>
              ) : (
                <CTAButton to={ctaHref} size="lg">
                  {ctaLabel} <ArrowRight className="h-4 w-4" />
                </CTAButton>
              )}
            </motion.div>

            <motion.div
              initial={{ opacity: 0, x: 20 }}
              whileInView={{ opacity: 1, x: 0 }}
              viewport={{ once: true }}
              transition={{ duration: 0.5, delay: 0.2 }}
              className="space-y-6"
            >
              <div className="aspect-video overflow-hidden rounded-2xl">
                <ImageWithSkeleton
                  src={heroImage}
                  alt={heroTitle}
                  className="h-full w-full object-cover"
                />
              </div>

              <div className="grid grid-cols-2 gap-4">
                {[
                  { label: 'Participants attendus', value: '5 000+' },
                  { label: 'Jours de conférence', value: '3' },
                  { label: 'Orateurs invités', value: '8+' },
                  { label: 'Éditions réussies', value: '10+' },
                ].map((stat) => (
                  <div
                    key={stat.label}
                    className="rounded-xl border border-surface-200 bg-white p-5 text-center shadow-sm"
                  >
                    <p className="font-heading text-2xl font-bold text-surface-900">{stat.value}</p>
                    <p className="mt-1 text-xs text-surface-500">{stat.label}</p>
                  </div>
                ))}
              </div>

              <div className="rounded-2xl border border-burgundy-100 bg-burgundy-50 p-6">
                <Star className="mb-3 h-5 w-5 text-gold-400" />
                <p className="text-sm italic leading-relaxed text-surface-700">
                  "Bunda a changé ma vie. En trois jours, j'ai reçu une nouvelle vision de Dieu pour mon avenir et mon
                  ministère."
                </p>
                <p className="mt-3 text-xs font-medium text-burgundy-700">— Grâce L., participante</p>
              </div>
            </motion.div>
          </div>
        </div>
      </section>

      <section className="border-t border-surface-100 bg-surface-50 py-20">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div className="mb-10 flex flex-wrap items-end justify-between gap-4">
            <div>
              <p className="text-xs font-semibold uppercase tracking-[0.14em] text-burgundy-700">Archives</p>
              <h2 className="mt-2 font-heading text-2xl font-bold text-surface-900 sm:text-3xl">
                Éditions Bunda
              </h2>
            </div>
          </div>

          {loading ? (
            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
              {[0, 1, 2].map((index) => (
                <div key={index} className="aspect-video animate-pulse rounded-2xl bg-surface-200" />
              ))}
            </div>
          ) : data !== null ? (
            <div className="space-y-8">
              <article className="yt-playlist-card overflow-hidden rounded-2xl border border-burgundy-200 bg-gradient-to-br from-burgundy-50 to-white p-6 sm:p-8">
                <div className="flex flex-wrap items-start gap-4">
                  <span className="inline-flex items-center gap-2 rounded-full border border-burgundy-200 bg-white px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-burgundy-800">
                    <Sparkles className="h-3.5 w-3.5" aria-hidden />
                    À venir
                  </span>
                </div>
                <h3 className="mt-4 font-heading text-2xl font-bold text-surface-900">
                  {upcoming.title}
                </h3>
                <p className="mt-2 max-w-2xl text-surface-600">
                  Rendez-vous en <strong>{upcoming.monthLabel}</strong> pour la prochaine conférence.
                  {upcoming.description !== '' ? ` ${upcoming.description}` : ''}
                </p>
                <CTAButton to="/join" className="mt-6" size="md">
                  Être informé <ArrowRight className="h-4 w-4" />
                </CTAButton>
              </article>

              {data !== null && data.pastEditions.length > 0 ? (
                <YoutubePlaylistGrid
                  groups={data.pastEditions.map(editionToPlaylistGroup)}
                  emptyMessage=""
                />
              ) : null}
            </div>
          ) : (
            <p className="text-center text-surface-500">Les archives Bunda seront bientôt disponibles.</p>
          )}
        </div>
      </section>
    </>
  );
}
