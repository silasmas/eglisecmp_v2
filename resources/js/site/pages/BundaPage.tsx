import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { motion } from 'framer-motion';
import { ArrowRight, Calendar, Download, MapPin, Sparkles } from 'lucide-react';
import PageHero from '../components/ui/PageHero';
import ImageWithSkeleton from '../components/ui/ImageWithSkeleton';
import { fetchSiteBunda } from '../lib/siteApi';
import type { BundaEdition, BundaPageData, TeachingsPlaylistGroup } from '../data/types';
import YoutubePlaylistGrid from '../components/teachings/YoutubePlaylistGrid';
import AlertSubscribeModal from '../components/alerts/AlertSubscribeModal';
import BundaArchivesToolbar, { type BundaArchiveViewMode } from '../components/bunda/BundaArchivesToolbar';
import BundaNotifyButton from '../components/bunda/BundaNotifyButton';
import '../styles/youtube-playlist-grid.css';

const FALLBACK_HERO =
  'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=1400&h=600&fit=crop';

/**
 * Transforme une édition Bunda en carte playlist YouTube empilée.
 *
 * @param edition Édition renvoyée par l'API publique.
 * @returns Groupe compatible avec la grille playlists.
 */
function editionToPlaylistGroup(edition: BundaEdition): TeachingsPlaylistGroup {
  const href =
    edition.contentHref !== null && edition.contentHref.trim() !== ''
      ? edition.contentHref
      : `/teachings/playlist/${encodeURIComponent(edition.id)}`;

  return {
    eventId: edition.id,
    title: edition.title,
    description: edition.description,
    thumbnail: edition.image.trim() !== '' ? edition.image : FALLBACK_HERO,
    videoCount: edition.videoCount,
    visibility: edition.editionYear > 0 ? `Édition ${edition.editionYear}` : 'Conférence',
    items: [],
    href,
  };
}

/**
 * Page publique Bunda 21 : contenus admin, plan alimentaire, alertes et archives par édition.
 */
export default function BundaPage() {
  const [data, setData] = useState<BundaPageData | null>(null);
  const [loading, setLoading] = useState(true);
  const [informOpen, setInformOpen] = useState(false);
  const [selectedYear, setSelectedYear] = useState<number | 'all'>('all');
  const [viewMode, setViewMode] = useState<BundaArchiveViewMode>('grid');

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

  const editions = data?.editions ?? [];
  const latestEdition = data?.latestEdition ?? editions[0] ?? null;
  const intro = data?.intro;
  const upcoming = data?.upcoming ?? {
    title: 'Bunda',
    monthLabel: 'Novembre',
    year: new Date().getFullYear(),
    description: '',
  };

  const heroImage =
    latestEdition?.image && latestEdition.image.trim() !== ''
      ? latestEdition.image
      : intro?.heroImage && intro.heroImage.trim() !== ''
        ? intro.heroImage
        : FALLBACK_HERO;
  const heroTitle = intro?.title ?? latestEdition?.title ?? 'Conférence Bunda';

  const latestHref =
    latestEdition?.contentHref !== null && latestEdition?.contentHref !== undefined && latestEdition.contentHref.trim() !== ''
      ? latestEdition.contentHref
      : latestEdition?.id
        ? `/teachings/playlist/${encodeURIComponent(latestEdition.id)}`
        : '/teachings?tab=playlists';
  const latestButtonLabel = latestEdition?.buttonLabel ?? `Bunda ${latestEdition?.editionYear ?? upcoming.year}`;

  const editionYears = useMemo(() => {
    const years = editions
      .map((edition) => edition.editionYear)
      .filter((year) => year > 0);
    return [...new Set(years)].sort((a, b) => b - a);
  }, [editions]);

  const filteredEditions = useMemo(() => {
    if (selectedYear === 'all') {
      return editions;
    }
    return editions.filter((edition) => edition.editionYear === selectedYear);
  }, [editions, selectedYear]);

  const filteredGroups = useMemo(
    () => filteredEditions.map(editionToPlaylistGroup),
    [filteredEditions],
  );

  const mealPlanUrl = intro?.mealPlanUrl ?? editions[0]?.mealPlanUrl ?? null;
  const mealPlanLabel = intro?.mealPlanLabel ?? 'Plan alimentaire';
  const introSubtitle = intro?.subtitle?.trim() ?? '';
  const introBody = intro?.body?.trim() ?? '';

  return (
    <>
      <PageHero
        badge="Bunda"
        title={heroTitle}
        description={
          introSubtitle !== ''
            ? introSubtitle
            : 'Notre conférence annuelle phare qui rassemble des milliers de fidèles autour de la Parole de Dieu.'
        }
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
              {introBody !== '' ? (
                <p className="mb-8 whitespace-pre-wrap text-lg leading-relaxed text-surface-600">{introBody}</p>
              ) : (
                <>
                  <p className="mb-6 text-lg leading-relaxed text-surface-600">
                    Bunda est la conférence annuelle du Centre Missionnaire Philadelphie. C&apos;est un événement majeur
                    qui rassemble notre communauté pour des jours d&apos;enseignement, de louange et de prière intenses.
                  </p>
                  <p className="mb-8 leading-relaxed text-surface-500">
                    Combats, possède et jouis de ton héritage.
                  </p>
                </>
              )}

              <div className="mb-10 space-y-4">
                <div className="flex items-center gap-4 text-surface-600">
                  <Calendar className="h-5 w-5 shrink-0 text-burgundy-600" />
                  <span>
                    Prochaine édition : {upcoming.monthLabel} {upcoming.year}
                  </span>
                </div>
                <div className="flex items-center gap-4 text-surface-600">
                  <MapPin className="h-5 w-5 shrink-0 text-burgundy-600" />
                  <span>Centre Missionnaire Philadelphie, Kintambo, Kinshasa</span>
                </div>
              </div>

              <div className="flex flex-wrap gap-3">
                {latestEdition !== null ? (
                  <Link to={latestHref} className="tw-cta-primary inline-flex items-center gap-2">
                    {latestButtonLabel} <ArrowRight className="h-4 w-4" />
                  </Link>
                ) : null}
                {mealPlanUrl !== null ? (
                  <a
                    href={mealPlanUrl}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="inline-flex items-center gap-2 rounded-xl border border-burgundy-200 bg-burgundy-50 px-5 py-3 text-sm font-semibold text-burgundy-800 transition hover:bg-burgundy-100"
                  >
                    <Download className="h-4 w-4" />
                    {mealPlanLabel}
                  </a>
                ) : null}
                <BundaNotifyButton onClick={() => setInformOpen(true)} />
              </div>
            </motion.div>

            <motion.div
              initial={{ opacity: 0, x: 20 }}
              whileInView={{ opacity: 1, x: 0 }}
              viewport={{ once: true }}
              transition={{ duration: 0.5, delay: 0.2 }}
              className="space-y-6"
            >
              <div className="aspect-video overflow-hidden rounded-2xl">
                <ImageWithSkeleton src={heroImage} alt={heroTitle} className="h-full w-full object-cover" />
              </div>
            </motion.div>
          </div>
        </div>
      </section>

      <section id="bunda-upcoming" className="border-t border-surface-100 bg-surface-50 py-20">
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
            <div className="space-y-10">
              <article className="overflow-hidden rounded-2xl border border-burgundy-200 bg-gradient-to-br from-burgundy-50 to-white p-6 sm:p-8">
                <span className="inline-flex items-center gap-2 rounded-full border border-burgundy-200 bg-white px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-burgundy-800">
                  <Sparkles className="h-3.5 w-3.5" aria-hidden />
                  À venir
                </span>
                <h3 className="mt-4 font-heading text-2xl font-bold text-surface-900">{upcoming.title}</h3>
                <p className="mt-2 max-w-2xl text-surface-600">
                  Rendez-vous en <strong>{upcoming.monthLabel}</strong> pour la prochaine conférence.
                  {upcoming.description !== '' ? ` ${upcoming.description}` : ''}
                </p>
                <div className="mt-6">
                  <BundaNotifyButton onClick={() => setInformOpen(true)} />
                </div>
              </article>

              {editions.length > 0 ? (
                <>
                  <BundaArchivesToolbar
                    years={editionYears}
                    selectedYear={selectedYear}
                    onSelectYear={setSelectedYear}
                    viewMode={viewMode}
                    onViewModeChange={setViewMode}
                  />

                  {filteredGroups.length === 0 ? (
                    <p className="text-center text-surface-500">Aucune archive pour cette année.</p>
                  ) : viewMode === 'grid' ? (
                    <YoutubePlaylistGrid groups={filteredGroups} emptyMessage="" />
                  ) : (
                    <div className="space-y-12">
                      {filteredEditions.map((edition) => (
                        <article key={edition.programId ?? edition.id} className="space-y-5">
                          <div className="text-center sm:text-left">
                            <h3 className="font-heading text-2xl font-bold text-surface-900">{edition.title}</h3>
                            {edition.description !== '' ? (
                              <p className="mt-2 max-w-3xl text-surface-600">{edition.description}</p>
                            ) : null}
                            {edition.mealPlanUrl ? (
                              <a
                                href={edition.mealPlanUrl}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="mt-3 inline-flex items-center gap-2 text-sm font-semibold text-burgundy-700 hover:underline"
                              >
                                <Download className="h-4 w-4" />
                                {edition.mealPlanLabel ?? 'Plan alimentaire'}
                              </a>
                            ) : null}
                          </div>
                          <YoutubePlaylistGrid groups={[editionToPlaylistGroup(edition)]} emptyMessage="" />
                        </article>
                      ))}
                    </div>
                  )}
                </>
              ) : (
                <p className="text-center text-surface-500">Les archives Bunda seront bientôt disponibles.</p>
              )}
            </div>
          ) : (
            <p className="text-center text-surface-500">Impossible de charger les données Bunda.</p>
          )}
        </div>
      </section>

      <AlertSubscribeModal
        open={informOpen}
        onClose={() => setInformOpen(false)}
        source="bunda"
        title="Ne manquez pas la prochaine édition Bunda"
        defaultNotifyLive={false}
        defaultNotifyEvents={true}
      />
    </>
  );
}
