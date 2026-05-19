import { motion } from 'framer-motion';
import type { ReactNode } from 'react';
import { cn } from '../../lib/utils';
import { staggerContainer, staggerItem } from '../../lib/animations';

interface SectionHeaderProps {
  badge?: string;
  title: string;
  description?: string;
  align?: 'left' | 'center';
  dark?: boolean;
  children?: ReactNode;
}

export default function SectionHeader({
  badge,
  title,
  description,
  align = 'center',
  dark = false,
  children,
}: SectionHeaderProps) {
  return (
    <motion.div
      className={cn('mb-12', align === 'center' && 'text-center')}
      variants={staggerContainer}
      initial="hidden"
      whileInView="show"
      viewport={{ once: true, margin: '-60px' }}
    >
      {badge && (
        <motion.span
          variants={staggerItem}
          className={cn(
            'inline-block text-[11px] font-semibold uppercase tracking-[0.15em] mb-5 px-4 py-1.5 rounded-full',
            dark
              ? 'bg-white/10 text-gold-300 border border-white/10'
              : 'bg-surface-100 text-surface-600 border border-surface-200'
          )}
        >
          {badge}
        </motion.span>
      )}
      <motion.h2
        variants={staggerItem}
        className={cn(
          'font-heading font-extrabold leading-[1.1] tracking-tight',
          dark ? 'text-white' : 'text-surface-900',
          'text-3xl sm:text-4xl lg:text-[3.25rem]'
        )}
      >
        {title}
      </motion.h2>
      {description && (
        <motion.p
          variants={staggerItem}
          className={cn(
            'mt-4 text-lg max-w-2xl leading-relaxed',
            align === 'center' && 'mx-auto',
            dark ? 'text-surface-400' : 'text-surface-500'
          )}
        >
          {description}
        </motion.p>
      )}
      {children && <motion.div variants={staggerItem}>{children}</motion.div>}
    </motion.div>
  );
}
