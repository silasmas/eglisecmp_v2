import { useState, useEffect } from 'react';
import { Link, useLocation } from 'react-router-dom';
import { Menu, X, ChevronDown, Heart } from 'lucide-react';
import { motion, AnimatePresence, useScroll, useTransform } from 'framer-motion';
import { navigation } from '../../data/navigation';
import { cn } from '../../lib/utils';
import MobileMenu from './MobileMenu';
import cmpLogo from '../../assets/Logo-CMP-2023-new.png';

export default function Navbar() {
  const [scrolled, setScrolled] = useState(false);
  const [mobileOpen, setMobileOpen] = useState(false);
  const [openDropdown, setOpenDropdown] = useState<string | null>(null);
  const location = useLocation();
  const isHome = location.pathname === '/';
  const { scrollYProgress } = useScroll();
  const progressScaleX = useTransform(scrollYProgress, [0, 1], [0, 1]);

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 20);
    window.addEventListener('scroll', onScroll, { passive: true });
    return () => window.removeEventListener('scroll', onScroll);
  }, []);

  useEffect(() => {
    setMobileOpen(false);
    setOpenDropdown(null);
  }, [location.pathname]);

  // On homepage, unscrolled nav is over the dark hero
  const isTransparentDark = isHome && !scrolled;

  return (
    <>
      <header
        className={cn(
          'fixed top-0 left-0 right-0 z-50 transition-all duration-500',
          scrolled
            ? 'bg-white/80 backdrop-blur-2xl border-b border-surface-200/80 shadow-sm'
            : 'bg-transparent'
        )}
      >
        {/* Scroll progress indicator */}
        <motion.div
          className="absolute bottom-0 left-0 h-[2px] w-full origin-left bg-gradient-to-r from-burgundy-600 via-burgundy-500 to-gold-400 pointer-events-none"
          style={{ scaleX: progressScaleX }}
        />

        <nav className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div className="flex h-18 items-center justify-between">
            {/* Logo */}
            <Link to="/" className="flex items-center gap-3 group">
              <img
                src={cmpLogo}
                alt="Logo Centre Missionnaire Philadelphie"
                className={cn(
                  'h-11 w-11 shrink-0 object-contain transition-[transform,filter] duration-300 group-hover:scale-[1.02] sm:h-12 sm:w-12',
                  isTransparentDark
                    ? 'drop-shadow-[0_3px_12px_rgba(0,0,0,0.35)]'
                    : 'brightness-0 saturate-100'
                )}
              />
              <div className="hidden sm:block leading-none">
                <div
                  className={cn(
                    'flex flex-col transition-colors',
                    isTransparentDark ? 'text-white' : 'text-surface-900'
                  )}
                >
                  <span className="font-heading text-[8px] font-semibold uppercase tracking-[0.16em] text-current/70">
                    Centre Missionnaire
                  </span>
                  <span className="mt-1 font-heading text-[12px] font-bold tracking-[0.01em]">
                    Philadelphie
                  </span>
                </div>
              </div>
            </Link>

            {/* Desktop nav */}
            <div className="hidden lg:flex items-center gap-1">
              {navigation.map((item) => (
                <div
                  key={item.label}
                  className="relative"
                  onMouseEnter={() => item.children && setOpenDropdown(item.label)}
                  onMouseLeave={() => setOpenDropdown(null)}
                >
                  <Link
                    to={item.href}
                    className={cn(
                      'px-3.5 py-2 text-[13px] font-medium rounded-full transition-all duration-200 flex items-center gap-1',
                      location.pathname === item.href ||
                        location.pathname.startsWith(item.href + '/')
                        ? isTransparentDark
                          ? 'text-white bg-white/15'
                          : 'text-burgundy-800 bg-burgundy-50'
                        : isTransparentDark
                          ? 'text-white/70 hover:text-white hover:bg-white/10'
                          : 'text-surface-600 hover:text-surface-900 hover:bg-surface-100'
                    )}
                  >
                    {item.label}
                    {item.children && (
                      <motion.span
                        animate={{ rotate: openDropdown === item.label ? 180 : 0 }}
                        transition={{ duration: 0.2, ease: 'easeInOut' }}
                        className="inline-flex"
                      >
                        <ChevronDown className="w-3.5 h-3.5 opacity-50" />
                      </motion.span>
                    )}
                  </Link>

                  {/* Dropdown — animated */}
                  <AnimatePresence>
                    {item.children && openDropdown === item.label && (
                      <motion.div
                        initial={{ opacity: 0, y: -8, scale: 0.97 }}
                        animate={{ opacity: 1, y: 0, scale: 1 }}
                        exit={{ opacity: 0, y: -8, scale: 0.97 }}
                        transition={{ duration: 0.18, ease: [0.22, 1, 0.36, 1] }}
                        className="absolute top-full left-0 pt-2 w-56"
                      >
                        <div className="bg-white/95 backdrop-blur-2xl border border-surface-200 rounded-2xl shadow-xl shadow-black/5 py-2 overflow-hidden">
                          {item.children.map((child, i) => (
                            <motion.div
                              key={child.label}
                              initial={{ opacity: 0, x: -6 }}
                              animate={{ opacity: 1, x: 0 }}
                              transition={{ delay: i * 0.04, duration: 0.2 }}
                            >
                              <Link
                                to={child.href}
                                className="block px-4 py-2.5 text-sm text-surface-600 hover:text-burgundy-800 hover:bg-burgundy-50/60 transition-colors"
                              >
                                {child.label}
                              </Link>
                            </motion.div>
                          ))}
                        </div>
                      </motion.div>
                    )}
                  </AnimatePresence>
                </div>
              ))}
            </div>

            {/* CTA + Mobile toggle */}
            <div className="flex items-center gap-2.5">
              <Link
                to="/offrandes"
                className={cn(
                  'hidden sm:inline-flex items-center gap-1.5 px-4 py-2.5 text-[13px] font-semibold rounded-full border transition-all duration-300',
                  isTransparentDark
                    ? 'border-white/25 text-white hover:bg-white/10'
                    : 'border-burgundy-200 text-burgundy-800 hover:bg-burgundy-50'
                )}
              >
                <Heart className="w-3.5 h-3.5" />
                Offrande
              </Link>
              <Link
                to="/join"
                className="hidden sm:inline-flex items-center px-5 py-2.5 text-[13px] font-semibold rounded-full bg-burgundy-800 text-white hover:bg-burgundy-700 transition-all duration-300 shadow-md shadow-burgundy-900/20"
              >
                Nous rejoindre
              </Link>
              <motion.button
                onClick={() => setMobileOpen(!mobileOpen)}
                whileTap={{ scale: 0.9 }}
                transition={{ type: 'spring', stiffness: 500, damping: 30 }}
                className={cn(
                  'lg:hidden p-2 rounded-lg transition-colors',
                  isTransparentDark
                    ? 'text-white/80 hover:text-white hover:bg-white/10'
                    : 'text-surface-600 hover:text-surface-900 hover:bg-surface-100'
                )}
                aria-label="Menu"
              >
                <AnimatePresence mode="wait" initial={false}>
                  {mobileOpen ? (
                    <motion.span
                      key="close"
                      initial={{ rotate: -90, opacity: 0 }}
                      animate={{ rotate: 0, opacity: 1 }}
                      exit={{ rotate: 90, opacity: 0 }}
                      transition={{ duration: 0.18 }}
                      className="block"
                    >
                      <X className="w-6 h-6" />
                    </motion.span>
                  ) : (
                    <motion.span
                      key="menu"
                      initial={{ rotate: 90, opacity: 0 }}
                      animate={{ rotate: 0, opacity: 1 }}
                      exit={{ rotate: -90, opacity: 0 }}
                      transition={{ duration: 0.18 }}
                      className="block"
                    >
                      <Menu className="w-6 h-6" />
                    </motion.span>
                  )}
                </AnimatePresence>
              </motion.button>
            </div>
          </div>
        </nav>
      </header>

      <MobileMenu open={mobileOpen} onClose={() => setMobileOpen(false)} />
    </>
  );
}
