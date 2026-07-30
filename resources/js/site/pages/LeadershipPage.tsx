import { useEffect, useMemo, useState } from 'react';
import { motion } from 'framer-motion';
import PageHero from '../components/ui/PageHero';
import { Skeleton } from '../components/ui/Skeleton';
import { cn } from '../lib/utils';
import { stripHtml } from '../lib/stripHtml';
import { fetchPublicMinisters, type LeadershipMinisterRow } from '../lib/siteApi';

const DEFAULT_PORTRAIT =
  'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&h=500&fit=crop&crop=faces';

/**
 * Page Leadership : tous les pasteurs actifs avec filtre par fonction.
 */
export default function LeadershipPage() {
  const [ministers, setMinisters] = useState<LeadershipMinisterRow[]>([]);
  const [loading, setLoading] = useState(true);
  const [roleFilter, setRoleFilter] = useState<string>('all');

  useEffect(() => {
    let cancelled = false;
    async function load() {
      try {
        setLoading(true);
        const rows = await fetchPublicMinisters();
        if (!cancelled) {
          setMinisters(rows);
        }
      } catch {
        if (!cancelled) {
          setMinisters([]);
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

  const roles = useMemo(() => {
    const unique = new Set<string>();
    ministers.forEach((row) => {
      if (row.role.trim() !== '') {
        unique.add(row.role);
      }
    });
    return Array.from(unique).sort((a, b) => a.localeCompare(b, 'fr'));
  }, [ministers]);

  const filtered = useMemo(() => {
    if (roleFilter === 'all') {
      return ministers;
    }
    return ministers.filter((row) => row.role === roleFilter);
  }, [ministers, roleFilter]);

  return (
    <>
      <PageHero
        badge="Leadership"
        title="Nos pasteurs"
        description="Rencontrez les leaders qui servent et guident notre communauté avec amour et dévouement."
        backgroundImage="https://images.unsplash.com/photo-1507692049790-de58290a4334?w=1600&h=700&fit=crop"
      />

      <section className="py-16 sm:py-20">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          {!loading && roles.length > 0 ? (
            <motion.div className="mb-10 flex flex-wrap justify-center gap-2">
              <button
                type="button"
                onClick={() => setRoleFilter('all')}
                className={cn(
                  'rounded-full px-4 py-2 text-xs font-semibold uppercase tracking-wide transition',
                  roleFilter === 'all'
                    ? 'bg-burgundy-900 text-white'
                    : 'bg-surface-100 text-surface-600 hover:bg-surface-200 dark:bg-surface-800 dark:text-surface-300',
                )}
              >
                Tous
              </button>
              {roles.map((role) => (
                <button
                  key={role}
                  type="button"
                  onClick={() => setRoleFilter(role)}
                  className={cn(
                    'rounded-full px-4 py-2 text-xs font-semibold transition',
                    roleFilter === role
                      ? 'bg-burgundy-900 text-white'
                      : 'bg-surface-100 text-surface-600 hover:bg-surface-200 dark:bg-surface-800 dark:text-surface-300',
                  )}
                >
                  {role}
                </button>
              ))}
            </motion.div>
          ) : null}

          {loading ? (
            <motion.div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
              {Array.from({ length: 10 }).map((_, index) => (
                <motion.div key={index} className="overflow-hidden rounded-xl border border-surface-200 bg-white">
                  <Skeleton className="aspect-[3/4] w-full rounded-none" />
                  <motion.div className="space-y-2 p-3">
                    <Skeleton className="h-3 w-16" />
                    <Skeleton className="h-4 w-full" />
                  </motion.div>
                </motion.div>
              ))}
            </motion.div>
          ) : filtered.length === 0 ? (
            <p className="text-center text-surface-500">Aucun pasteur à afficher pour ce filtre.</p>
          ) : (
            <motion.div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
              {filtered.map((leader, index) => {
                const bioText = stripHtml(leader.bio);

                return (
                  <motion.article
                    key={leader.id}
                    initial={{ opacity: 0, y: 12 }}
                    whileInView={{ opacity: 1, y: 0 }}
                    viewport={{ once: true }}
                    transition={{ duration: 0.35, delay: Math.min(index * 0.04, 0.3) }}
                    className="group flex h-full flex-col overflow-hidden rounded-xl border border-surface-200 bg-white shadow-sm transition hover:shadow-md dark:border-surface-700 dark:bg-surface-950"
                  >
                    <motion.div className="relative aspect-[4/5] overflow-hidden bg-gradient-to-b from-surface-100 to-surface-200">
                      <img
                        src={leader.image_url !== '' ? leader.image_url : DEFAULT_PORTRAIT}
                        alt={leader.fullname}
                        className="absolute inset-0 h-full w-full object-cover object-top transition duration-500 group-hover:scale-105"
                        onError={(event) => {
                          (event.target as HTMLImageElement).src = DEFAULT_PORTRAIT;
                        }}
                      />
                      <div className="pointer-events-none absolute inset-x-0 bottom-0 h-16 bg-gradient-to-t from-black/35 to-transparent" />
                    </motion.div>
                    <motion.div className="flex flex-1 flex-col p-3.5">
                      <p className="text-[10px] font-semibold uppercase tracking-wide text-burgundy-600">
                        {leader.role}
                      </p>
                      <h3 className="mt-1 font-heading text-sm font-bold leading-snug text-surface-900 dark:text-white">
                        {leader.fullname}
                      </h3>
                      {bioText !== '' ? (
                        <p className="mt-2 line-clamp-4 text-[11px] leading-relaxed text-surface-500">
                          {bioText}
                        </p>
                      ) : null}
                    </motion.div>
                  </motion.article>
                );
              })}
            </motion.div>
          )}
        </div>
      </section>
    </>
  );
}
