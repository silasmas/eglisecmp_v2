import { motion } from 'framer-motion';
import { Calendar, MapPin, ArrowRight, Star } from 'lucide-react';
import PageHero from '../components/ui/PageHero';
import CTAButton from '../components/ui/CTAButton';

export default function BundaPage() {
  return (
    <>
      <PageHero
        badge="Bunda"
        title="Conférence Bunda 2026"
        description="Notre conférence annuelle phare qui rassemble des milliers de fidèles autour de la Parole de Dieu."
        backgroundImage="https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=1400&h=600&fit=crop"
      />

      <section className="py-24">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div className="grid lg:grid-cols-2 gap-16 items-start">
            <motion.div
              initial={{ opacity: 0, x: -20 }}
              whileInView={{ opacity: 1, x: 0 }}
              viewport={{ once: true }}
              transition={{ duration: 0.5 }}
            >
              <h2 className="font-heading font-semibold text-3xl sm:text-4xl text-surface-900 leading-tight mb-6">
                Un moment unique de communion et de célébration
              </h2>
              <p className="text-surface-600 text-lg leading-relaxed mb-6">
                Bunda est la conférence annuelle du Centre Missionnaire Philadelphie. 
                C'est un événement majeur qui rassemble notre communauté et des invités de 
                marque pour des jours d'enseignement, de louange et de prière intenses.
              </p>
              <p className="text-surface-500 leading-relaxed mb-8">
                Chaque édition de Bunda est un tournant spirituel pour des milliers de participants. 
                Des orateurs puissants, une louange vibrante et la présence tangible de Dieu font 
                de cet événement un rendez-vous incontournable pour tout chrétien.
              </p>

              <div className="space-y-4 mb-10">
                <div className="flex items-center gap-4 text-surface-600">
                  <Calendar className="w-5 h-5 text-burgundy-600 shrink-0" />
                  <span>Juillet 2026 — Dates exactes à venir</span>
                </div>
                <div className="flex items-center gap-4 text-surface-600">
                  <MapPin className="w-5 h-5 text-burgundy-600 shrink-0" />
                  <span>Centre Missionnaire Philadelphie, Kintambo, Kinshasa</span>
                </div>
              </div>

              <CTAButton to="/join" size="lg">
                S'inscrire à Bunda <ArrowRight className="w-4 h-4" />
              </CTAButton>
            </motion.div>

            <motion.div
              initial={{ opacity: 0, x: 20 }}
              whileInView={{ opacity: 1, x: 0 }}
              viewport={{ once: true }}
              transition={{ duration: 0.5, delay: 0.2 }}
              className="space-y-6"
            >
              <div className="rounded-2xl overflow-hidden aspect-video">
                <img
                  src="https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=700&h=400&fit=crop"
                  alt="Conférence Bunda"
                  className="w-full h-full object-cover"
                />
              </div>

              <div className="grid grid-cols-2 gap-4">
                {[
                  { label: 'Participants attendus', value: '5 000+' },
                  { label: 'Jours de conférence', value: '3' },
                  { label: 'Orateurs invités', value: '8+' },
                  { label: 'Éditions réussies', value: '10+' },
                ].map((stat) => (
                  <div key={stat.label} className="rounded-xl bg-white border border-surface-200 shadow-sm p-5 text-center">
                    <p className="font-heading text-2xl font-bold text-surface-900">{stat.value}</p>
                    <p className="text-surface-500 text-xs mt-1">{stat.label}</p>
                  </div>
                ))}
              </div>

              <div className="rounded-2xl bg-burgundy-50 border border-burgundy-100 p-6">
                <Star className="w-5 h-5 text-gold-400 mb-3" />
                <p className="text-surface-700 text-sm leading-relaxed italic">
                  "Bunda a changé ma vie. En trois jours, j'ai reçu une nouvelle vision de Dieu 
                  pour mon avenir et mon ministère."
                </p>
                <p className="text-burgundy-700 text-xs mt-3 font-medium">— Grâce L., participante</p>
              </div>
            </motion.div>
          </div>
        </div>
      </section>
    </>
  );
}
