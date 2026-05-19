import { motion } from 'framer-motion';
import { Clock, MapPin, ArrowRight, HandHeart } from 'lucide-react';
import CTAButton from '../ui/CTAButton';
import { churchInfo } from '../../data/content';

export default function VisitCTASection() {
  return (
    <section className="py-24">
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <motion.div
          initial={{ opacity: 0, y: 40 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true, margin: '-80px' }}
          transition={{ duration: 0.7, ease: [0.22, 1, 0.36, 1] }}
          className="relative rounded-3xl overflow-hidden"
        >
          <div className="absolute inset-0">
            <img
              src="https://images.unsplash.com/photo-1519834785169-98be25ec3f84?w=1400&h=600&fit=crop"
              alt=""
              className="w-full h-full object-cover"
            />
            <div className="absolute inset-0 bg-gradient-to-r from-surface-950/95 via-burgundy-950/90 to-surface-950/85" />
          </div>

          <div className="relative px-8 py-16 sm:px-14 sm:py-20 lg:px-20 lg:py-24">
            <div className="grid lg:grid-cols-2 gap-12 lg:gap-20 items-center">
              <div>
                <h2 className="font-heading font-bold text-3xl sm:text-4xl lg:text-5xl text-white leading-tight">
                  Venez nous rendre visite
                </h2>
                <p className="mt-6 text-white/60 text-lg leading-relaxed">
                  Nous serions ravis de vous accueillir parmi nous. Chaque dimanche est une
                  occasion de vivre un moment unique de communion et d'adoration.
                </p>
                <div className="mt-10 flex flex-wrap items-center gap-3">
                  <CTAButton to="/rendez-vous" variant="white" className="shadow-lg shadow-black/20">
                    Prendre rendez-vous <ArrowRight className="w-4 h-4" />
                  </CTAButton>
                  <CTAButton to="/requete-de-priere" variant="ghost" className="text-white border border-white/20 hover:bg-white/10 hover:text-white">
                    <HandHeart className="w-4 h-4" /> Demande de prière
                  </CTAButton>
                  <CTAButton to="/contact" variant="ghost" className="text-white/70 hover:text-white hover:bg-white/10">
                    Nous contacter
                  </CTAButton>
                </div>
              </div>

              <div className="space-y-4">
                <div className="rounded-2xl bg-white/10 backdrop-blur-md border border-white/10 p-6">
                  <div className="flex items-center gap-2 mb-4">
                    <Clock className="w-4 h-4 text-gold-400" />
                    <p className="text-white font-semibold text-sm">Horaires des cultes</p>
                  </div>
                  <div className="space-y-3">
                    <div className="flex justify-between items-center">
                      <span className="text-white/60 text-sm">Culte dominical</span>
                      <span className="text-white font-semibold text-sm">{churchInfo.serviceTimes.sunday}</span>
                    </div>
                    <div className="flex justify-between items-center">
                      <span className="text-white/60 text-sm">Enseignement (Mercredi)</span>
                      <span className="text-white font-semibold text-sm">{churchInfo.serviceTimes.wednesday}</span>
                    </div>
                    <div className="flex justify-between items-center">
                      <span className="text-white/60 text-sm">Intercession (Jeudi)</span>
                      <span className="text-white font-semibold text-sm">{churchInfo.serviceTimes.thursday}</span>
                    </div>
                  </div>
                </div>

                <div className="rounded-2xl bg-white/10 backdrop-blur-md border border-white/10 p-6">
                  <div className="flex items-center gap-2 mb-3">
                    <MapPin className="w-4 h-4 text-gold-400" />
                    <p className="text-white font-semibold text-sm">Adresse</p>
                  </div>
                  <p className="text-white/60 text-sm">
                    {churchInfo.address}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </motion.div>
      </div>
    </section>
  );
}
