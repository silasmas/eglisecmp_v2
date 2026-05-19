import { motion } from 'framer-motion';
import { Target, Eye, Heart } from 'lucide-react';
import PageHero from '../components/ui/PageHero';

const pillars = [
  {
    icon: Eye,
    title: 'La vision',
    description:
      "Être une église missionnaire qui forme des disciples de Christ capables d'impacter leur génération et les nations par la puissance de l'Évangile.",
  },
  {
    icon: Target,
    title: 'La mission',
    description:
      "Former des chrétiens solides, enracinés dans la Parole, engagés dans le service et actifs dans l'évangélisation. Nous croyons que chaque croyant est appelé à être un ambassadeur du Royaume.",
  },
  {
    icon: Heart,
    title: 'Les valeurs',
    description:
      "L'amour fraternel, la fidélité, le service, l'excellence et l'intégrité sont au cœur de tout ce que nous faisons. Philadelphie signifie « amour fraternel » et c'est notre ADN.",
  },
];

export default function VisionPage() {
  return (
    <>
      <PageHero
        badge="Vision & Mission"
        title="Notre vision pour les nations"
        description="Comprendre ce qui nous anime et la direction que Dieu donne à notre église."
      />

      <section className="py-24">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
            {pillars.map((pillar, i) => (
              <motion.div
                key={pillar.title}
                initial={{ opacity: 0, y: 20 }}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true }}
                transition={{ duration: 0.5, delay: i * 0.15 }}
                className="rounded-2xl bg-white border border-surface-200 shadow-sm p-8"
              >
                <div className="w-14 h-14 rounded-2xl bg-burgundy-50 border border-burgundy-100 flex items-center justify-center mb-6">
                  <pillar.icon className="w-6 h-6 text-burgundy-700" />
                </div>
                <h3 className="font-heading font-semibold text-surface-900 text-2xl mb-4">
                  {pillar.title}
                </h3>
                <p className="text-surface-500 leading-relaxed">
                  {pillar.description}
                </p>
              </motion.div>
            ))}
          </div>

          <motion.div
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ duration: 0.5, delay: 0.3 }}
            className="mt-16 rounded-3xl bg-burgundy-50 border border-burgundy-100 p-10 sm:p-14 text-center"
          >
            <blockquote className="font-heading text-2xl sm:text-3xl text-surface-800 italic leading-relaxed max-w-3xl mx-auto">
              « Je connais tes œuvres. Voici, parce que tu as peu de puissance, et que tu as 
              gardé ma parole, et que tu n'as pas renié mon nom, j'ai mis devant toi une porte 
              ouverte, que personne ne peut fermer. »
            </blockquote>
            <p className="mt-6 text-burgundy-700 font-medium">
              Apocalypse 3:8
            </p>
          </motion.div>
        </div>
      </section>
    </>
  );
}
