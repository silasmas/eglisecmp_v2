import { useCallback, useEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { Link, useLocation } from 'react-router-dom';
import { AnimatePresence, motion } from 'framer-motion';
import { Search, X } from 'lucide-react';
import { cn } from '../../lib/utils';
import { fetchSiteSearch } from '../../lib/siteApi';
import type { SiteSearchHit } from '../../data/types';

type NavbarInstantSearchProps = {
  isTransparentDark: boolean;
};

/**
 * Recherche site : icône loupe seule par défaut ; panneau centré au clic uniquement.
 */
export default function NavbarInstantSearch({ isTransparentDark }: NavbarInstantSearchProps) {
  const location = useLocation();
  const [open, setOpen] = useState(false);
  const [mounted, setMounted] = useState(false);
  const [query, setQuery] = useState('');
  const [hits, setHits] = useState<SiteSearchHit[]>([]);
  const [loading, setLoading] = useState(false);
  const [focused, setFocused] = useState(false);
  const inputRef = useRef<HTMLInputElement>(null);
  const debounceRef = useRef<number | null>(null);

  useEffect(() => {
    setMounted(true);
  }, []);

  const closeSearch = useCallback(() => {
    setOpen(false);
    setFocused(false);
    setQuery('');
    setHits([]);
  }, []);

  useEffect(() => {
    closeSearch();
  }, [location.pathname, closeSearch]);

  const runSearch = useCallback(async (term: string) => {
    if (term.trim().length < 2) {
      setHits([]);
      setLoading(false);
      return;
    }
    setLoading(true);
    try {
      const results = await fetchSiteSearch(term);
      setHits(results);
    } catch {
      setHits([]);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    if (debounceRef.current !== null) {
      window.clearTimeout(debounceRef.current);
    }
    debounceRef.current = window.setTimeout(() => {
      void runSearch(query);
    }, 280);
    return () => {
      if (debounceRef.current !== null) {
        window.clearTimeout(debounceRef.current);
      }
    };
  }, [query, runSearch]);

  useEffect(() => {
    if (!open) {
      return undefined;
    }
    const timer = window.setTimeout(() => inputRef.current?.focus(), 80);
    return () => window.clearTimeout(timer);
  }, [open]);

  useEffect(() => {
    if (!open) {
      return undefined;
    }
    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        closeSearch();
      }
    };
    document.addEventListener('keydown', onKeyDown);
    return () => document.removeEventListener('keydown', onKeyDown);
  }, [open, closeSearch]);

  useEffect(() => {
    if (!open) {
      return undefined;
    }
    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    return () => {
      document.body.style.overflow = previousOverflow;
    };
  }, [open]);

  const showDropdown = open && focused && (query.trim().length >= 2 || loading);

  const inputClasses = cn(
    'site-search-input w-full rounded-full border-2 bg-white px-5 py-3.5 pl-12 pr-12 text-base text-surface-900 outline-none transition-all duration-300 placeholder:text-surface-400',
    focused
      ? 'border-burgundy-500 shadow-[0_0_0_4px_rgba(127,29,29,0.14),0_12px_32px_rgba(127,29,29,0.22)]'
      : 'border-surface-200 shadow-[0_4px_16px_rgba(15,23,42,0.06)] hover:border-burgundy-200',
  );

  const searchPanel =
    open && mounted
      ? createPortal(
          <AnimatePresence>
            <motion.div
              key="search-backdrop"
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              transition={{ duration: 0.2 }}
              className="fixed inset-0 z-[200] bg-black/30 backdrop-blur-[2px]"
              aria-hidden
              onClick={closeSearch}
            />
            <motion.div
              key="search-panel"
              initial={{ opacity: 0, y: -12 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -12 }}
              transition={{ duration: 0.22, ease: [0.22, 1, 0.36, 1] }}
              className="fixed left-0 right-0 top-[4.75rem] z-[210] px-4 sm:top-[5rem] sm:px-6"
              role="dialog"
              aria-modal="true"
              aria-label="Recherche sur le site"
            >
              <div className="mx-auto grid max-w-7xl grid-cols-12 gap-4">
                <div className="col-span-12 lg:col-start-4 lg:col-span-6">
                  <div className="relative">
                    <Search
                      className={cn(
                        'pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2',
                        focused ? 'text-burgundy-600' : 'text-surface-400',
                      )}
                      aria-hidden
                    />
                    <input
                      ref={inputRef}
                      type="search"
                      value={query}
                      onChange={(e) => setQuery(e.target.value)}
                      onFocus={() => setFocused(true)}
                      onBlur={() => {
                        window.setTimeout(() => setFocused(false), 150);
                      }}
                      placeholder="Rechercher un message, une playlist, un événement…"
                      className={inputClasses}
                      aria-label="Rechercher sur le site"
                      aria-expanded={showDropdown}
                      aria-controls="site-search-results"
                    />
                    <button
                      type="button"
                      onClick={() => {
                        if (query !== '') {
                          setQuery('');
                          setHits([]);
                          inputRef.current?.focus();
                          return;
                        }
                        closeSearch();
                      }}
                      className="absolute right-3 top-1/2 -translate-y-1/2 rounded-full p-1.5 text-surface-500 transition hover:bg-surface-100"
                      aria-label={query !== '' ? 'Effacer la recherche' : 'Fermer la recherche'}
                    >
                      <X className="h-4 w-4" />
                    </button>

                    {showDropdown ? (
                      <ul
                        id="site-search-results"
                        className="absolute left-0 right-0 top-[calc(100%+0.65rem)] max-h-[min(24rem,60vh)] overflow-y-auto rounded-2xl border border-surface-200 bg-white py-2 shadow-[0_20px_48px_rgba(15,23,42,0.14)]"
                        role="listbox"
                      >
                        {loading ? (
                          <li className="px-5 py-4 text-sm text-surface-500">Recherche…</li>
                        ) : hits.length === 0 ? (
                          <li className="px-5 py-4 text-sm text-surface-500">Aucun résultat.</li>
                        ) : (
                          hits.map((hit) => (
                            <li key={`${hit.type}-${hit.id}`}>
                              <Link
                                to={hit.href}
                                onClick={closeSearch}
                                className="flex items-center gap-4 px-4 py-3 transition hover:bg-burgundy-50"
                                role="option"
                              >
                                {hit.thumbnail ? (
                                  <img
                                    src={hit.thumbnail}
                                    alt=""
                                    className="h-14 w-[4.5rem] shrink-0 rounded-xl object-cover ring-1 ring-black/5"
                                    loading="lazy"
                                  />
                                ) : (
                                  <span className="flex h-14 w-[4.5rem] shrink-0 items-center justify-center rounded-xl bg-surface-100 text-surface-400">
                                    <Search className="h-5 w-5" aria-hidden />
                                  </span>
                                )}
                                <span className="min-w-0">
                                  <p className="truncate text-sm font-semibold text-surface-900">{hit.title}</p>
                                  <p className="truncate text-xs text-surface-500">{hit.subtitle}</p>
                                </span>
                              </Link>
                            </li>
                          ))
                        )}
                      </ul>
                    ) : null}
                  </div>
                </div>
              </div>
            </motion.div>
          </AnimatePresence>,
          document.body,
        )
      : null;

  return (
    <>
      <button
        type="button"
        onClick={() => setOpen(true)}
        className={cn(
          'inline-flex h-10 w-10 items-center justify-center rounded-full border transition',
          isTransparentDark
            ? 'border-white/25 text-white hover:bg-white/10'
            : 'border-surface-200 text-surface-700 hover:bg-surface-100',
        )}
        aria-label="Ouvrir la recherche"
        aria-expanded={open}
        aria-haspopup="dialog"
      >
        <Search className="h-4 w-4" aria-hidden />
      </button>
      {searchPanel}
    </>
  );
}
