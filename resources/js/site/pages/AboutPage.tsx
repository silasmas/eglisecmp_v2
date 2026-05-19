import { motion } from 'framer-motion';
import PageHero from '../components/ui/PageHero';
import { churchInfo } from '../data/content';

export default function AboutPage() {
  return (
    <>
      <PageHero
        badge="À propos"
        title="Notre histoire"
        description="Présentation globale de la mission et de la vision de l'église."
        backgroundImage="https://images.unsplash.com/photo-1511632765486-a01980e01a18?w=1400&h=600&fit=crop"
      />

      <section className="py-24">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div className="grid lg:grid-cols-3 gap-12">
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true }}
              transition={{ duration: 0.5 }}
              className="lg:col-span-2 space-y-6"
            >
              <p className="text-surface-600 text-lg leading-relaxed">
                Le Centre Missionnaire Philadelphie est une assemblée chrétienne fondée 
                sur la conviction que l'amour fraternel et la fidélité à la Parole de Dieu 
                sont les fondements d'une communauté de foi vivante et impactante.
              </p>
              <p className="text-surface-400 leading-relaxed">
                Depuis sa fondation, CMP n'a cessé de croître, rassemblant aujourd'hui 
                plus de {churchInfo.stats.members.toLocaleString('fr-FR')} fidèles à travers {churchInfo.stats.extensions} extensions. 
                Notre église est un lieu où chacun peut trouver sa place, grandir dans la 
                foi et servir selon les dons que Dieu lui a confiés.
              </p>
              <p className="text-surface-400 leading-relaxed">
                Nous croyons en un enseignement biblique profond et accessible, en une louange 
                authentique et en une communion fraternelle qui reflète l'amour de Christ. 
                Chaque culte, chaque rencontre est une occasion de vivre la présence de Dieu 
                et de s'édifier mutuellement.
              </p>
              <p className="text-surface-400 leading-relaxed">
                Notre vision dépasse les murs de notre église. À travers nos programmes 
                missionnaires, nos cellules de maison et nos conférences comme Bunda, nous 
                touchons des vies et portons l'Évangile dans notre ville et au-delà.
              </p>
            </motion.div>

            <motion.div
              initial={{ opacity: 0, y: 20 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true }}
              transition={{ duration: 0.5, delay: 0.2 }}
              className="space-y-6"
            >
              <div className="rounded-2xl bg-white border border-surface-200 shadow-sm p-6">
                <h3 className="font-heading font-semibold text-surface-900 text-lg mb-4">Nos valeurs</h3>
                <ul className="space-y-3 text-surface-600 text-sm">
                  <li className="flex items-start gap-3">
                    <span className="w-1.5 h-1.5 rounded-full bg-burgundy-500 mt-2 shrink-0" />
                    L'amour fraternel (Philadelphie)
                  </li>
                  <li className="flex items-start gap-3">
                    <span className="w-1.5 h-1.5 rounded-full bg-burgundy-500 mt-2 shrink-0" />
                    La fidélité à la Parole de Dieu
                  </li>
                  <li className="flex items-start gap-3">
                    <span className="w-1.5 h-1.5 rounded-full bg-burgundy-500 mt-2 shrink-0" />
                    Le service désintéressé
                  </li>
                  <li className="flex items-start gap-3">
                    <span className="w-1.5 h-1.5 rounded-full bg-burgundy-500 mt-2 shrink-0" />
                    La mission et l'évangélisation
                  </li>
                  <li className="flex items-start gap-3">
                    <span className="w-1.5 h-1.5 rounded-full bg-burgundy-500 mt-2 shrink-0" />
                    La communion et l'unité
                  </li>
                </ul>
              </div>

              <div className="rounded-2xl overflow-hidden aspect-[4/3]">
                <img
                  src="https://images.unsplash.com/photo-1504052434569-70ad5836ab65?w=500&h=375&fit=crop"
                  alt="Moment de prière"
                  className="w-full h-full object-cover"
                />
              </div>
            </motion.div>
          </div>
        </div>
      </section>
    </>
  );
}
