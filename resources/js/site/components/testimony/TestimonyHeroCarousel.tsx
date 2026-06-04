import { useCallback, useEffect, useMemo, useState } from 'react';
import type { WallTestimony } from '../../data/types';
import { fetchWallCarousel } from '../../lib/siteApi';
import { mergeCarouselTestimonies, testimonyCarouselPreview, testimonyPublishedAt } from '../../lib/testimonyMedia';
import TestimonyPublishedAgo from './TestimonyPublishedAgo';
import { TestimonyCarouselSkeleton } from '../ui/Skeleton';
import TestimonyCarouselPreview from './TestimonyCarouselPreview';
import TestimonyMediaBadges from './TestimonyMediaBadges';

const POLL_MS = 45_000;
const CAROUSEL_LIMIT = 24;

type TestimonyHeroCarouselProps = {
  onSelect: (testimony: WallTestimony, index: number, list: WallTestimony[]) => void;
};

/**
 * Carrousel vertical : défilement infini si plusieurs cartes, flottement seul si une seule.
 * Rafraîchissement silencieux : ajoute les nouveaux témoignages sans recharger toute la colonne.
 */
export default function TestimonyHeroCarousel({ onSelect }: TestimonyHeroCarouselProps) {
  const [items, setItems] = useState<WallTestimony[]>([]);
  const [loading, setLoading] = useState(true);

  const loadCarousel = useCallback(async (options?: { silent?: boolean }) => {
    const silent = options?.silent === true;
    if (!silent) {
      setLoading(true);
    }

    try {
      const fresh = await fetchWallCarousel(CAROUSEL_LIMIT);
      setItems((prev) => {
        if (silent && prev.length > 0) {
          return mergeCarouselTestimonies(prev, fresh, CAROUSEL_LIMIT);
        }
        return fresh;
      });
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
    void loadCarousel();
  }, [loadCarousel]);

  useEffect(() => {
    const interval = window.setInterval(() => {
      void loadCarousel({ silent: true });
    }, POLL_MS);

    return () => window.clearInterval(interval);
  }, [loadCarousel]);

  const { col1, col2 } = useMemo(() => {
    const half = Math.ceil(items.length / 2);
    return {
      col1: items.slice(0, half),
      col2: items.slice(half),
    };
  }, [items]);

  if (loading) {
    return <TestimonyCarouselSkeleton />;
  }

  if (items.length === 0) {
    return null;
  }

  const renderCard = (testimony: WallTestimony, keySuffix: string) => {
    const rotation = (Number(testimony.id) % 5) * 1.2 - 2.4;
    const preview = testimonyCarouselPreview(testimony);

    return (
      <button
        key={`${testimony.id}-${keySuffix}`}
        type="button"
        className="tw-card-mini relative text-left"
        style={{
          backgroundColor: testimony.postitColor || '#FFF6D9',
          fontFamily: testimony.fontFamily,
          transform: `rotate(${rotation}deg)`,
        }}
        onClick={() => onSelect(testimony, items.findIndex((t) => t.id === testimony.id), items)}
      >
        <TestimonyMediaBadges testimony={testimony} />
        <p className="tw-card-mini-title pr-14">{testimony.title}</p>
        <TestimonyCarouselPreview preview={preview} title={testimony.title} />
        <div className="mt-1 flex flex-wrap items-center justify-between gap-1">
          <p className="tw-card-mini-author mb-0">— {testimony.author}</p>
          <TestimonyPublishedAgo publishedAt={testimonyPublishedAt(testimony)} className="text-[10px]" />
        </div>
      </button>
    );
  };

  const renderColumn = (columnItems: WallTestimony[], colNum: '1' | '2') => {
    if (columnItems.length === 0) {
      return null;
    }

    const single = columnItems.length === 1;
    const trackItems = single ? columnItems : [...columnItems, ...columnItems];

    return (
      <div className={`tw-carousel-col tw-carousel-col--${colNum}`}>
        <div className={single ? 'tw-carousel-track tw-carousel-track--float' : 'tw-carousel-track'}>
          {trackItems.map((t, i) => renderCard(t, `c${colNum}-${i}`))}
        </div>
      </div>
    );
  };

  return (
    <div className="tw-carousel-wrap">
      {renderColumn(col1, '1')}
      {renderColumn(col2, '2')}
    </div>
  );
}
