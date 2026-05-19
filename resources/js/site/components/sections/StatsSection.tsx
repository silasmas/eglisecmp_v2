import { useEffect, useRef, useState } from 'react';
import { motion, useInView } from 'framer-motion';
import { Users, Network, Grid3x3, UserCheck, type LucideIcon } from 'lucide-react';
import { churchInfo } from '../../data/content';
import type { SiteHomeStatRow } from '../../data/types';
import { useSiteHomeStatistics } from '../../hooks/useSiteHomeStatistics';

const ICON_MAP: Record<string, LucideIcon> = {
  users: Users,
  network: Network,
  grid: Grid3x3,
  pastors: UserCheck,
};

const FALLBACK_HOME_STATS: SiteHomeStatRow[] = [
  { icon_key: 'users', label: 'Fidèles', value: churchInfo.stats.members, suffix: '' },
  { icon_key: 'network', label: 'Extensions', value: churchInfo.stats.extensions, suffix: '' },
  { icon_key: 'grid', label: 'Cellules', value: churchInfo.stats.cells, suffix: '' },
  { icon_key: 'pastors', label: 'Pastoraux', value: churchInfo.stats.pastors, suffix: '' },
];

/** Animation numérique affichée pour un indicateur avec suffixe facultatif (%…). */
function CountUp({
  value,
  suffix = '',
}: {
  value: number;
  suffix?: string;
}) {
  const ref = useRef<HTMLSpanElement | null>(null);
  const isInView = useInView(ref, { once: true, margin: '-80px' });
  const [displayValue, setDisplayValue] = useState(0);

  useEffect(() => {
    if (!isInView) {
      return;
    }

    let frame = 0;
    const duration = 1500;
    const startedAt = performance.now();

    const tick = (now: number) => {
      const progress = Math.min((now - startedAt) / duration, 1);
      const eased = 1 - Math.pow(1 - progress, 3);
      setDisplayValue(Math.round(value * eased));

      if (progress < 1) {
        frame = requestAnimationFrame(tick);
      }
    };

    frame = requestAnimationFrame(tick);
    return () => cancelAnimationFrame(frame);
  }, [isInView, value]);

  return (
    <span ref={ref}>
      {displayValue.toLocaleString('fr-FR')}
      {suffix}
    </span>
  );
}

/**
 * Bloc « En chiffres » : valeurs dynamiques depuis l’admin (« Statistiques accueil ») avec repli sur `churchInfo`.
 */
export default function StatsSection() {
  const { stats, loading } = useSiteHomeStatistics(FALLBACK_HOME_STATS);

  return (
    <section className="py-20">
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <motion.div
          initial={{ opacity: 0, y: 30 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true, margin: '-80px' }}
          transition={{ duration: 0.6, ease: [0.22, 1, 0.36, 1] }}
          className={`rounded-3xl bg-surface-900 p-10 sm:p-14 ${loading ? 'opacity-90' : ''}`}
        >
          <div className="mb-12 text-center">
            <span className="inline-block rounded-full border border-gold-500/20 bg-gold-500/10 px-4 py-1.5 text-[11px] font-semibold uppercase tracking-[0.15em] text-gold-300">
              En chiffres
            </span>
            <h2 className="font-heading mt-5 text-3xl font-extrabold tracking-tight text-white sm:text-4xl">
              Nos statistiques
            </h2>
            <p className="mt-3 text-surface-400">La croissance d&apos;une communauté vivante et engagée.</p>
          </div>

          <div className="grid grid-cols-2 gap-8 md:grid-cols-4">
            {stats.map((row, index) => {
              const Icon = ICON_MAP[row.icon_key] ?? Users;
              const suffixLabel = row.suffix ?? '';
              return (
                <motion.div
                  key={`${row.label}-${row.icon_key}`}
                  initial={{ opacity: 0, y: 30 }}
                  whileInView={{ opacity: 1, y: 0 }}
                  whileHover={{ y: -4, transition: { type: 'spring', stiffness: 400, damping: 25 } }}
                  viewport={{ once: true }}
                  transition={{ duration: 0.55, ease: [0.22, 1, 0.36, 1], delay: index * 0.1 }}
                  className="cursor-default text-center"
                >
                  <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl border border-white/10 bg-white/10">
                    <Icon className="h-6 w-6 text-gold-400" aria-hidden />
                  </div>
                  <p className="font-heading text-4xl font-bold text-white sm:text-5xl">
                    <CountUp value={row.value} suffix={suffixLabel} />
                  </p>
                  <p className="mt-2 text-sm uppercase tracking-wider text-surface-400">{row.label}</p>
                </motion.div>
              );
            })}
          </div>
        </motion.div>
      </div>
    </section>
  );
}
