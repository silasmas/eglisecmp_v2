import { motion } from 'framer-motion';
import { cn } from '../../lib/utils';
import { staggerContainer, staggerItem } from '../../lib/animations';

/** Bannière image par défaut pour toutes les pages hors accueil. */
export const DEFAULT_PAGE_BANNER =
  'https://images.unsplash.com/photo-1438232992991-995b7058bbb3?w=1600&h=700&fit=crop';

interface PageHeroProps {
  badge?: string;
  title: string;
  description?: string;
  backgroundImage?: string;
  compact?: boolean;
  className?: string;
}

/**
 * En-tête de page avec bannière image (toujours affichée hors accueil).
 */
export default function PageHero({
  badge,
  title,
  description,
  backgroundImage,
  compact = false,
  className,
}: PageHeroProps) {
  const imageSrc = (backgroundImage ?? '').trim() !== '' ? backgroundImage!.trim() : DEFAULT_PAGE_BANNER;

  return (
    <section
      className={cn(
        'relative overflow-hidden',
        compact ? 'pb-14 pt-24 sm:pb-16 sm:pt-28' : 'pb-20 pt-32 sm:pb-28 sm:pt-40',
        className,
      )}
    >
      <div className="absolute inset-0">
        <img src={imageSrc} alt="" className="h-full w-full object-cover" />
        <div className="absolute inset-0 bg-gradient-to-b from-surface-950/75 via-surface-950/55 to-white dark:to-surface-950" />
        <div className="absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-white to-transparent dark:from-surface-950" />
      </div>

      <motion.div
        className="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
        variants={staggerContainer}
        initial="hidden"
        animate="show"
      >
        {badge ? (
          <motion.span
            variants={staggerItem}
            className="mb-5 inline-block rounded-full border border-white/25 bg-white/15 px-4 py-1.5 text-[11px] font-semibold uppercase tracking-[0.15em] text-white backdrop-blur-sm"
          >
            {badge}
          </motion.span>
        ) : null}
        <motion.h1
          variants={staggerItem}
          className={cn(
            'max-w-3xl font-heading font-extrabold leading-[1.1] tracking-tight text-white',
            compact ? 'text-3xl sm:text-4xl lg:text-[2.75rem]' : 'text-4xl sm:text-5xl lg:text-[3.75rem]',
          )}
        >
          {title}
        </motion.h1>
        {description ? (
          <motion.p
            variants={staggerItem}
            className="mt-6 max-w-2xl text-lg leading-relaxed text-white/80 sm:text-xl"
          >
            {description}
          </motion.p>
        ) : null}
      </motion.div>
    </section>
  );
}
