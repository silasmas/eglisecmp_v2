import { useCallback, useEffect, useMemo, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { ChevronDown, Plus } from 'lucide-react';
import '../styles/testimony-wall.css';
import TestimonyHeroCarousel from '../components/testimony/TestimonyHeroCarousel';
import TestimonyDetailModal from '../components/testimony/TestimonyDetailModal';
import TestimonySubmitModal from '../components/testimony/TestimonySubmitModal';
import TestimonyCtaFooter from '../components/testimony/TestimonyCtaFooter';
import TestimonyPostItCard from '../components/testimony/TestimonyPostItCard';
import { useSiteTestimonies } from '../hooks/useSiteTestimonies';
import type { WallTestimony } from '../data/types';
import { fetchWallStats } from '../lib/siteApi';
import type { WallStats } from '../data/types';
import {
  TestimonyFiltersSkeleton,
  TestimonyWallGridSkeleton,
} from '../components/ui/Skeleton';

const ROTATIONS = [-2.5, 1.5, -1, 2, -1.8, 1.2, -2, 0.8];

/**
 * Page mur de témoignages (design modèle Bunda21 : hero carrousel, mur, CTA).
 */
export default function TestimonyWallPage() {
  const [searchParams, setSearchParams] = useSearchParams();
  const [activeCategory, setActiveCategory] = useState('Tous');
  const [submitOpen, setSubmitOpen] = useState(false);
  const [modalOpen, setModalOpen] = useState(false);
  const [modalIndex, setModalIndex] = useState(0);
  const [modalList, setModalList] = useState<WallTestimony[]>([]);
  const [stats, setStats] = useState<WallStats | null>(null);
  const [statsLoading, setStatsLoading] = useState(true);
  const [heroCount, setHeroCount] = useState(0);
  const [imageZoom, setImageZoom] = useState<{ images: WallTestimony['images']; index: number } | null>(null);

  const { items, wall, loading, loadingMore, hasMore, error, loadMore, refresh, reactionKeys, wallSettings } =
    useSiteTestimonies(activeCategory);

  const categories = useMemo(() => {
    if (wall?.categories !== undefined && wall.categories.length > 0) {
      return wall.categories;
    }
    return ['Tous', 'Vidéos', 'Guérison', 'Provision', 'Famille', 'Délivrance', 'Éducation', 'Protection', 'Autre'];
  }, [wall]);

  const filteredItems = useMemo(() => {
    if (activeCategory === 'Vidéos') {
      return items.filter((t) => t.kind === 'video' || t.videoEmbedUrl !== '' || t.videoFileUrl !== '');
    }
    return items;
  }, [items, activeCategory]);

  const openDetail = useCallback((testimony: WallTestimony, index: number, list: WallTestimony[]) => {
    const idx = list.findIndex((t) => t.id === testimony.id);
    setModalList(list);
    setModalIndex(idx >= 0 ? idx : index);
    setModalOpen(true);
  }, []);

  useEffect(() => {
    void fetchWallStats()
      .then(setStats)
      .catch(() => setStats(null))
      .finally(() => setStatsLoading(false));
  }, []);

  useEffect(() => {
    if (stats !== null) {
      setHeroCount(stats.testimonies);
    }
  }, [stats]);

  useEffect(() => {
    const openId = searchParams.get('open');
    if (openId === null || loading || filteredItems.length === 0) {
      return;
    }
    const idx = filteredItems.findIndex((t) => t.id === openId);
    if (idx >= 0) {
      openDetail(filteredItems[idx], idx, filteredItems);
      setSearchParams({}, { replace: true });
    }
  }, [searchParams, loading, filteredItems, openDetail, setSearchParams]);

  const scrollToWall = () => {
    document.getElementById('tw-wall')?.scrollIntoView({ behavior: 'smooth' });
  };

  return (
    <div className="tw-page -mt-24 pt-24">
      <section className="tw-hero">
        <img
          src="https://images.unsplash.com/photo-1438232992991-995b7058bbb3?w=1600&h=900&fit=crop"
          alt=""
          className="absolute inset-0 h-full w-full object-cover"
          aria-hidden
        />
        <div className="absolute inset-0 bg-gradient-to-b from-black/75 via-black/65 to-[#0a0a0a]" aria-hidden />
        <div className="tw-hero-glow" aria-hidden />
        <div className="tw-hero-grid">
          <div>
            <p className="mb-2 text-sm font-semibold uppercase tracking-widest text-[#FFD53D]">Bunda21 · CMP</p>
            <h1 className="tw-hero-title">Ce que Dieu a fait pour nous !</h1>
            <h2 className="tw-hero-subtitle">Partagé, conservé, célébré.</h2>
            <p className="tw-hero-desc">
              Un espace vivant où chaque fidèle partage son action de grâce et proclame, comme il est écrit :{' '}
              <strong>« Ils l&apos;ont vaincu à cause de la parole de leur témoignage » (Apocalypse 12:11).</strong>
            </p>
            <button type="button" className="tw-cta-primary" onClick={() => setSubmitOpen(true)}>
              J&apos;ai un témoignage
            </button>
            <div className="tw-counter">
              <span className="tw-counter-num">{heroCount}</span>
              <span className="text-[#C0C0C0]">témoignages enregistrés</span>
            </div>
          </div>
          <TestimonyHeroCarousel onSelect={openDetail} />
        </div>
        <button
          type="button"
          className="tw-scroll-hint"
          onClick={scrollToWall}
          aria-label="Voir le mur"
        >
          <ChevronDown className="h-8 w-8" />
        </button>
      </section>

      <section id="tw-wall" className="tw-wall-section">
        <div className="tw-wall-inner">
          <div className="tw-wall-header">
            <h2 className="tw-wall-title">Mur en direct</h2>
            <p className="tw-wall-sub">Aperçu des actions de grâce publiées.</p>
            <button
              type="button"
              onClick={() => setSubmitOpen(true)}
              className="tw-cta-primary mt-6 inline-flex"
            >
              <Plus className="h-5 w-5" aria-hidden />
              Partager mon témoignage
            </button>
          </div>

          {loading ? (
            <TestimonyFiltersSkeleton />
          ) : (
            <div className="tw-filters">
              {categories.map((cat) => (
                <button
                  key={cat}
                  type="button"
                  onClick={() => setActiveCategory(cat)}
                  className={activeCategory === cat ? 'tw-filter-btn tw-filter-btn--active' : 'tw-filter-btn'}
                >
                  {cat}
                </button>
              ))}
            </div>
          )}

          {error !== null && !loading ? (
            <p className="mb-6 rounded-lg bg-red-50 p-4 text-center text-sm text-red-800">{error}</p>
          ) : null}

          {loading ? (
            <TestimonyWallGridSkeleton />
          ) : filteredItems.length === 0 ? (
            <div className="rounded-2xl border border-dashed border-surface-300 py-16 text-center">
              <p className="text-surface-600">Aucun témoignage publié dans cette catégorie.</p>
            </div>
          ) : (
            <div className="tw-grid">
              {filteredItems.map((testimony, index) => (
                <TestimonyPostItCard
                  key={testimony.id}
                  testimony={testimony}
                  rotation={ROTATIONS[index % ROTATIONS.length]}
                  reactionLabels={reactionKeys}
                  compact
                  onOpen={() => openDetail(testimony, index, filteredItems)}
                  onImageZoom={(imgIndex) =>
                    setImageZoom({ images: testimony.images, index: imgIndex })
                  }
                />
              ))}
            </div>
          )}

          {hasMore && !loading && activeCategory !== 'Vidéos' ? (
            <div className="mt-10 text-center">
              <button
                type="button"
                disabled={loadingMore}
                onClick={loadMore}
                className="rounded-lg border-2 border-[#950000] px-6 py-2.5 text-sm font-semibold text-[#950000] hover:bg-[#950000]/5 disabled:opacity-50"
              >
                {loadingMore ? 'Chargement…' : 'Voir plus de témoignages'}
              </button>
            </div>
          ) : null}
        </div>
      </section>

      <TestimonyCtaFooter
        stats={stats}
        statsLoading={statsLoading}
        onWriteClick={() => setSubmitOpen(true)}
      />

      <TestimonyDetailModal
        open={modalOpen}
        items={modalList}
        index={modalIndex}
        reactionLabels={reactionKeys}
        onClose={() => setModalOpen(false)}
        onIndexChange={setModalIndex}
      />

      <TestimonySubmitModal
        open={submitOpen}
        wall={wall}
        wallSettings={wallSettings}
        onClose={() => setSubmitOpen(false)}
        onSuccess={() => {
          refresh();
          void fetchWallStats().then(setStats);
        }}
      />

      {imageZoom !== null && imageZoom.images.length > 0 ? (
        <div
          className="fixed inset-0 z-[210] flex items-center justify-center bg-black/90 p-4"
          role="dialog"
          aria-modal="true"
          onClick={() => setImageZoom(null)}
        >
          <button
            type="button"
            className="absolute left-4 top-1/2 -translate-y-1/2 rounded-full bg-white/20 p-2 text-white"
            onClick={(e) => {
              e.stopPropagation();
              setImageZoom((z) =>
                z === null
                  ? null
                  : { ...z, index: (z.index - 1 + z.images.length) % z.images.length },
              );
            }}
          >
            ‹
          </button>
          <img
            src={imageZoom.images[imageZoom.index].url}
            alt=""
            className="max-h-[85vh] max-w-full rounded-lg object-contain shadow-2xl"
            onClick={(e) => e.stopPropagation()}
          />
          <span className="absolute bottom-6 text-sm font-semibold text-white">
            {imageZoom.index + 1}/{imageZoom.images.length}
          </span>
          <button
            type="button"
            className="absolute right-4 top-1/2 -translate-y-1/2 rounded-full bg-white/20 p-2 text-white"
            onClick={(e) => {
              e.stopPropagation();
              setImageZoom((z) =>
                z === null ? null : { ...z, index: (z.index + 1) % z.images.length },
              );
            }}
          >
            ›
          </button>
        </div>
      ) : null}
    </div>
  );
}
