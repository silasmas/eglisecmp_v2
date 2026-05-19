import { motion } from 'framer-motion';
import { ArrowRight, Heart, BookOpen, Globe } from 'lucide-react';
import CTAButton from '../ui/CTAButton';
import ImageWithSkeleton from '../ui/ImageWithSkeleton';
import { DEFAULT_PLACEHOLDER_IMAGE } from '../../lib/placeholderImage';

export default function AboutPreviewSection() {
  return (
    <section className="py-24 relative overflow-hidden">
      {/* Subtle background accent */}
      <div className="absolute top-0 right-0 w-[500px] h-[500px] bg-surface-200/40 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2" />

      <div className="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div className="grid lg:grid-cols-2 gap-16 items-center">
          {/* Left: visual composition with framed images */}
          <motion.div
            initial={{ opacity: 0, x: -40 }}
            whileInView={{ opacity: 1, x: 0 }}
            viewport={{ once: true, margin: '-80px' }}
            transition={{ duration: 0.7, ease: [0.22, 1, 0.36, 1] }}
            className="grid grid-cols-2 gap-4"
          >
            <div className="col-span-2">
              <div className="group relative overflow-hidden rounded-[2rem] border border-surface-200/80 bg-surface-950 shadow-[0_24px_70px_rgba(9,9,11,0.14)]">
                <div className="aspect-[16/10] overflow-hidden bg-surface-950">
                  <ImageWithSkeleton
                    src={DEFAULT_PLACEHOLDER_IMAGE}
                    alt="Communion fraternelle à CMP"
                    className="h-full w-full object-cover img-hover"
                  />
                </div>

                <div className="absolute inset-0 bg-[linear-gradient(180deg,rgba(24,24,27,0.02)_0%,rgba(24,24,27,0.18)_42%,rgba(9,9,11,0.72)_100%)]" />
                <div className="absolute inset-x-0 top-0 h-24 bg-[radial-gradient(circle_at_top,rgba(255,255,255,0.26),transparent_70%)] opacity-70" />
                <div className="absolute inset-x-0 bottom-0 p-5 sm:p-6">
                  <div className="inline-flex items-center rounded-full border border-white/15 bg-white/10 px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.16em] text-white/90 backdrop-blur-sm">
                    Vie d'eglise
                  </div>
                  <p className="mt-3 max-w-sm font-heading text-xl font-bold leading-tight text-white sm:text-[1.65rem]">
                    Communion fraternelle au coeur de la maison
                  </p>
                  <p className="mt-2 text-sm text-white/72">
                    Accueil, chaleur humaine et presence de Dieu dans chaque rassemblement.
                  </p>
                </div>
              </div>
            </div>
            <div className="rounded-3xl bg-surface-50 border border-surface-200 p-6 shadow-sm hover:shadow-md hover:border-surface-300 transition-all duration-300 cursor-default">
              <Heart className="w-6 h-6 text-burgundy-600 mb-3" />
              <p className="font-heading font-bold text-surface-900 text-lg">Notre histoire</p>
              <p className="text-surface-500 text-[13px] mt-2">
                Fondée sur l'amour fraternel et la mission, CMP grandit depuis des années au service des nations.
              </p>
            </div>
            <div className="rounded-3xl bg-white border border-surface-200 shadow-sm p-6 hover:shadow-md hover:border-surface-300 transition-all duration-300 cursor-default">
              <Globe className="w-6 h-6 text-gold-600 mb-3" />
              <p className="font-heading font-bold text-surface-900 text-lg">Notre mission</p>
              <p className="text-surface-500 text-[13px] mt-2">
                Former des disciples, impacter les villes et les nations par l'Évangile de Jésus-Christ.
              </p>
            </div>
          </motion.div>

          {/* Right: text */}
          <motion.div
            initial={{ opacity: 0, x: 40 }}
            whileInView={{ opacity: 1, x: 0 }}
            viewport={{ once: true, margin: '-80px' }}
            transition={{ duration: 0.7, ease: [0.22, 1, 0.36, 1], delay: 0.15 }}
          >
            <span className="inline-block text-[11px] font-semibold uppercase tracking-[0.15em] mb-5 px-4 py-1.5 rounded-full bg-surface-100 text-surface-600 border border-surface-200">
              À propos
            </span>

            <h2 className="font-heading font-extrabold text-3xl sm:text-4xl lg:text-[3.25rem] text-surface-900 leading-[1.1] tracking-tight">
              Ce que nous sommes
            </h2>

            <p className="mt-6 text-surface-600 text-lg leading-relaxed">
              Le Centre Missionnaire Philadelphie est une église dynamique et engagée, 
              fondée sur la vision de l'amour fraternel au service des nations.
            </p>

            <p className="mt-4 text-surface-500 leading-relaxed">
              Nous sommes une communauté de foi qui croit en la puissance de la Parole de Dieu 
              pour transformer les vies et impacter les communautés. Sous la direction du Pasteur 
              Ken Luamba, notre église croît en nombre et en profondeur spirituelle.
            </p>

            <div className="mt-8 flex items-center gap-6">
              <div className="flex items-center gap-2">
                <BookOpen className="w-5 h-5 text-surface-500" />
                <span className="text-surface-600 text-sm">Enseignement solide</span>
              </div>
              <div className="flex items-center gap-2">
                <Heart className="w-5 h-5 text-surface-500" />
                <span className="text-surface-600 text-sm">Communauté vivante</span>
              </div>
            </div>

            <div className="mt-10">
              <CTAButton to="/discover/about">
                Découvrir CMP <ArrowRight className="w-4 h-4" />
              </CTAButton>
            </div>
          </motion.div>
        </div>
      </div>
    </section>
  );
}
