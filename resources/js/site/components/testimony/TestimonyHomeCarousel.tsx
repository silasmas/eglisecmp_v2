import { useCallback, useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { ArrowRight, MessageCircleHeart } from 'lucide-react';
import type { WallTestimony } from '../../data/types';
import { fetchWallCarousel } from '../../lib/siteApi';
import { mergeCarouselTestimonies, testimonyCarouselPreview, testimonyPublishedAt } from '../../lib/testimonyMedia';
import TestimonyCarouselPreview from './TestimonyCarouselPreview';
import TestimonyMediaBadges from './TestimonyMediaBadges';
import TestimonyPublishedAgo from './TestimonyPublishedAgo';
import '../../styles/testimony-home-carousel.css';

const CAROUSEL_LIMIT = 16;
const POLL_MS = 45_000;

type TestimonyHomeCarouselProps = {
  onSelect?: (testimony: WallTestimony, index: number, list: WallTestimony[]) => void;
};

/**
 * Carte CTA vers le mur de témoignages (fin du bandeau horizontal).
 */
function MurCtaCard() {
  return (
    <Link to="/temoignages" className="tw-home-card tw-home-cta-card shrink-0">
      <MessageCircleHeart className="mb-2 h-8 w-8 text-[#950000]" aria-hidden />
      <p className="text-sm font-bold text-surface-900">Mur de témoignages</p>
      <p className="mt-1 text-xs text-surface-600">Découvrir et partager le fruit de la foi</p>
      <span className="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-[#950000]">
        Voir tout
        <ArrowRight className="h-3.5 w-3.5" aria-hidden />
      </span>
    </Link>
  );
}

/**
 * Bandeau horizontal de mini post-its (accueil), défilement automatique.
 */
export default function TestimonyHomeCarousel({ onSelect }: TestimonyHomeCarouselProps) {
  const [items, setItems] = useState<WallTestimony[]>([]);
  const [loading, setLoading] = useState(true);

  const load = useCallback(async (silent = false) => {
    if (!silent) {
      setLoading(true);
    }
    try {
      const fresh = await fetchWallCarousel(CAROUSEL_LIMIT);
      setItems((prev) => (silent && prev.length > 0 ? mergeCarouselTestimonies(prev, fresh, CAROUSEL_LIMIT) : fresh));
    } catch {
      if (!silent) {
        setItems([]);
      }
    } finally {
      if (!silent) {
        setLoading(false);
      }
    }
  }, []);

  useEffect(() => {
    void load(false);
  }, [load]);

  useEffect(() => {
    const interval = window.setInterval(() => {
      void load(true);
    }, POLL_MS);
    return () => window.clearInterval(interval);
  }, [load]);

  const renderCard = useCallback(
    (testimony: WallTestimony, keySuffix: string) => {
      const preview = testimonyCarouselPreview(testimony);
      const rotation = (Number(testimony.id) % 5) * 0.8 - 1.6;

      return (
        <button
          key={`${testimony.id}-${keySuffix}`}
          type="button"
          className="tw-home-card relative shrink-0 text-left"
          style={{
            backgroundColor: testimony.postitColor || '#FFF6D9',
            fontFamily: testimony.fontFamily,
            transform: `rotate(${rotation}deg)`,
          }}
          onClick={() => onSelect?.(testimony, items.findIndex((t) => t.id === testimony.id), items)}
        >
          <TestimonyMediaBadges testimony={testimony} />
          <p className="mb-1 line-clamp-2 pr-10 text-sm font-bold text-surface-900">{testimony.title}</p>
          <TestimonyCarouselPreview preview={preview} title={testimony.title} />
          <div className="mt-2 flex items-center justify-between gap-2">
            <p className="text-xs font-semibold text-surface-700">— {testimony.author}</p>
            <TestimonyPublishedAgo publishedAt={testimonyPublishedAt(testimony)} className="text-[10px]" />
          </div>
        </button>
      );
    },
    [items, onSelect],
  );

  const trackContent = useMemo(() => {
    const cards = items.map((t, i) => renderCard(t, `a-${i}`));
    const half = [...cards, <MurCtaCard key="cta" />];
    if (items.length <= 1) {
      return half;
    }
    return [...half, ...items.map((t, i) => renderCard(t, `b-${i}`)), <MurCtaCard key="cta-2" />];
  }, [items, renderCard]);

  if (loading) {
    return (
      <div className="tw-home-carousel-wrap">
        <div className="flex gap-4 overflow-hidden px-1">
          {[0, 1, 2, 3].map((i) => (
            <div key={i} className="tw-home-card tw-home-card--skeleton h-[200px] w-[260px] shrink-0 animate-pulse bg-surface-200" />
          ))}
        </div>
      </div>
    );
  }

  if (items.length === 0) {
    return (
      <div className="text-center">
        <MurCtaCard />
      </div>
    );
  }

  return (
    <div className="tw-home-carousel-wrap">
      <div
        className={
          items.length === 1 ? 'tw-home-carousel-track tw-home-carousel-track--static' : 'tw-home-carousel-track'
        }
      >
        {trackContent}
      </div>
    </div>
  );
}
