import { BookOpen, Radio, Sparkles, Tv } from 'lucide-react';
import type { HeroStripCard } from '../../data/types';

type BadgeTone = NonNullable<HeroStripCard['modalBadgeTone']>;

const toneClasses: Record<BadgeTone, string> = {
  live: 'border-red-300/40 bg-red-700/90 text-white',
  'upcoming-live': 'border-gold-300/40 bg-burgundy-800/95 text-gold-100',
  reading: 'border-gold-300/40 bg-burgundy-800/95 text-gold-100',
  program: 'border-emerald-300/40 bg-emerald-900/90 text-emerald-50',
  'program-live': 'border-emerald-300/40 bg-emerald-800/95 text-white',
  featured: 'border-gold-300/40 bg-burgundy-800/95 text-gold-100',
};

/**
 * Badge clignotant pour les modales du bandeau hero.
 */
export default function HeroStripBlinkBadge({
  label,
  tone,
  className = 'absolute left-4 top-4 z-20',
}: {
  label: string;
  tone: BadgeTone;
  className?: string;
}) {
  const Icon =
    tone === 'reading'
      ? BookOpen
      : tone === 'program' || tone === 'program-live'
        ? Tv
        : tone === 'live' || tone === 'upcoming-live'
          ? Radio
          : Sparkles;

  return (
    <div
      className={`badge-blink inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[0.16em] shadow-lg backdrop-blur-md ${toneClasses[tone]} ${className}`}
    >
      <Icon className="h-3.5 w-3.5" aria-hidden />
      {label}
    </div>
  );
}
