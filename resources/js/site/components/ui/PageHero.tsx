import { motion } from 'framer-motion';
import { cn } from '../../lib/utils';
import { staggerContainer, staggerItem } from '../../lib/animations';

interface PageHeroProps {
  badge?: string;
  title: string;
  description?: string;
  backgroundImage?: string;
  compact?: boolean;
  className?: string;
}

export default function PageHero({
  badge,
  title,
  description,
  backgroundImage,
  compact = false,
  className,
}: PageHeroProps) {
  return (
    <section
      className={cn(
        compact ? 'relative overflow-hidden pb-14 pt-24 sm:pb-16 sm:pt-28' : 'relative overflow-hidden pt-32 pb-20 sm:pt-40 sm:pb-28',
        className
      )}
    >
      {backgroundImage && (
        <div className="absolute inset-0">
          <img
            src={backgroundImage}
            alt=""
            className="w-full h-full object-cover opacity-15"
          />
          <div className="absolute inset-0 bg-gradient-to-b from-white/60 via-white/80 to-white" />
        </div>
      )}
      {!backgroundImage && (
        <div className="absolute inset-0 bg-gradient-to-b from-burgundy-50/50 to-white" />
      )}

      <motion.div
        className="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
        variants={staggerContainer}
        initial="hidden"
        animate="show"
      >
        {badge && (
          <motion.span
            variants={staggerItem}
            className="inline-block text-[11px] font-semibold uppercase tracking-[0.15em] mb-5 px-4 py-1.5 rounded-full bg-burgundy-50 text-burgundy-700 border border-burgundy-100"
          >
            {badge}
          </motion.span>
        )}
        <motion.h1
          variants={staggerItem}
          className={cn(
            'font-heading font-extrabold text-surface-900 leading-[1.1] tracking-tight max-w-3xl',
            compact ? 'text-3xl sm:text-4xl lg:text-[2.75rem]' : 'text-4xl sm:text-5xl lg:text-[3.75rem]'
          )}
        >
          {title}
        </motion.h1>
        {description && (
          <motion.p
            variants={staggerItem}
            className="mt-6 text-lg sm:text-xl text-surface-500 max-w-2xl leading-relaxed"
          >
            {description}
          </motion.p>
        )}
      </motion.div>
    </section>
  );
}
