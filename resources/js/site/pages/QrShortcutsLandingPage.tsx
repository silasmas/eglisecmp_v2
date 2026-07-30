import { motion } from 'framer-motion';
import { ArrowRight, QrCode } from 'lucide-react';
import { Link } from 'react-router-dom';
import PageHero from '../components/ui/PageHero';
import { cn } from '../lib/utils';
import { QR_LANDING_ITEMS } from '../data/quickActions';

/**
 * Landing page destinée au scan QR code : raccourcis vers les actions rapides du site.
 */
export default function QrShortcutsLandingPage() {
  return (
    <>
      <PageHero
        compact
        badge="CMP"
        title="Bienvenue"
        description="Accédez rapidement aux services du CMP : offrande, prière, rendez-vous, témoignages et présentation des enfants."
        backgroundImage="https://images.unsplash.com/photo-1507692049790-de58290a4334?w=1600&h=700&fit=crop"
      />

      <section className="relative overflow-hidden bg-gradient-to-b from-burgundy-950 via-burgundy-900 to-surface-950 pb-20 pt-10">
        <div
          className="pointer-events-none absolute inset-0 opacity-40"
          style={{
            backgroundImage:
              'radial-gradient(circle at 20% 20%, rgba(212,175,55,0.25), transparent 45%), radial-gradient(circle at 80% 0%, rgba(255,255,255,0.08), transparent 40%)',
          }}
        />

        <div className="relative mx-auto max-w-3xl px-4 sm:px-6">
          <div className="mb-8 flex justify-center">
            <div className="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/10 text-gold-400 ring-1 ring-white/15">
              <QrCode className="h-7 w-7" aria-hidden />
            </div>
          </div>

          <div className="grid gap-3 sm:gap-4">
            {QR_LANDING_ITEMS.map((item, index) => (
              <motion.div
                key={item.to}
                initial={{ opacity: 0, y: 18 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.3, delay: 0.06 * index }}
              >
                <Link
                  to={item.to}
                  className={cn(
                    'group flex items-center gap-4 rounded-2xl bg-gradient-to-r p-4 text-white shadow-lg transition hover:brightness-110 sm:p-5',
                    item.landingClassName,
                  )}
                >
                  <span className="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/20">
                    <item.Icon className="h-6 w-6" aria-hidden />
                  </span>
                  <span className="min-w-0 flex-1 text-left">
                    <span className="block font-heading text-lg font-semibold">{item.label}</span>
                    <span className="mt-0.5 block text-sm text-white/80">{item.description}</span>
                  </span>
                  <ArrowRight className="h-5 w-5 shrink-0 opacity-80 transition group-hover:translate-x-0.5" aria-hidden />
                </Link>
              </motion.div>
            ))}
          </div>

          <p className="mt-10 text-center text-xs text-white/50">
            <Link to="/" className="underline decoration-white/30 underline-offset-2 hover:text-white/80">
              Retour à l’accueil du site
            </Link>
          </p>
        </div>
      </section>
    </>
  );
}
