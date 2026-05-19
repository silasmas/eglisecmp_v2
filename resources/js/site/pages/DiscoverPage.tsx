import { Link } from 'react-router-dom';
import { motion } from 'framer-motion';
import { ArrowRight, Heart, Globe, Users } from 'lucide-react';
import PageHero from '../components/ui/PageHero';
import CTAButton from '../components/ui/CTAButton';

const cards = [
  {
    icon: Heart,
    title: 'À propos',
    description: 'Découvrez notre histoire, nos valeurs et ce qui fait de CMP une communauté unique.',
    href: '/discover/about',
  },
  {
    icon: Globe,
    title: 'Vision & Mission',
    description: "Comprendre notre vision pour les nations et la mission que Dieu nous a confiée.",
    href: '/discover/vision',
  },
  {
    icon: Users,
    title: 'Leadership',
    description: 'Rencontrez les pasteurs et leaders qui servent notre communauté.',
    href: '/discover/leadership',
  },
];

export default function DiscoverPage() {
  return (
    <>
      <PageHero
        badge="Découvrir CMP"
        title="Bienvenue au Centre Missionnaire Philadelphie"
        description="Apprenez à connaître notre église, notre vision et les personnes qui font vivre cette communauté."
        backgroundImage="https://images.unsplash.com/photo-1511632765486-a01980e01a18?w=1400&h=600&fit=crop"
      />

      <section className="py-24">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            {cards.map((card, i) => (
              <motion.div
                key={card.title}
                initial={{ opacity: 0, y: 20 }}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true }}
                transition={{ duration: 0.4, delay: i * 0.1 }}
              >
                <Link
                  to={card.href}
                  className="group block h-full rounded-2xl bg-white border border-surface-200 shadow-sm p-8 hover:shadow-md hover:border-surface-300 transition-all duration-300"
                >
                  <div className="w-14 h-14 rounded-2xl bg-burgundy-50 border border-burgundy-100 flex items-center justify-center mb-6 group-hover:bg-burgundy-100 transition-colors">
                    <card.icon className="w-6 h-6 text-burgundy-700" />
                  </div>
                  <h3 className="font-heading font-semibold text-surface-900 text-xl mb-3 group-hover:text-burgundy-700 transition-colors">
                    {card.title}
                  </h3>
                  <p className="text-surface-500 text-sm leading-relaxed mb-6">
                    {card.description}
                  </p>
                  <span className="inline-flex items-center gap-1.5 text-sm font-medium text-burgundy-700 group-hover:text-burgundy-600 transition-colors">
                    En savoir plus <ArrowRight className="w-4 h-4" />
                  </span>
                </Link>
              </motion.div>
            ))}
          </div>

          <div className="mt-20 grid lg:grid-cols-2 gap-12 items-center">
            <motion.div
              initial={{ opacity: 0, x: -20 }}
              whileInView={{ opacity: 1, x: 0 }}
              viewport={{ once: true }}
              transition={{ duration: 0.5 }}
            >
              <div className="rounded-2xl overflow-hidden aspect-[4/3]">
                <img
                  src="https://images.unsplash.com/photo-1438232992991-995b7058bbb3?w=700&h=525&fit=crop"
                  alt="Culte à CMP"
                  className="w-full h-full object-cover"
                />
              </div>
            </motion.div>
            <motion.div
              initial={{ opacity: 0, x: 20 }}
              whileInView={{ opacity: 1, x: 0 }}
              viewport={{ once: true }}
              transition={{ duration: 0.5, delay: 0.2 }}
            >
              <span className="inline-block text-xs font-semibold uppercase tracking-widest mb-4 px-3 py-1 rounded-full bg-burgundy-50 text-burgundy-700 border border-burgundy-100">
                Notre identité
              </span>
              <h2 className="font-heading font-semibold text-3xl sm:text-4xl text-surface-900 leading-tight">
                Une église fondée sur l'amour fraternel
              </h2>
              <p className="mt-6 text-surface-600 leading-relaxed">
                CMP — Centre Missionnaire Philadelphie — tire son nom de l'église de 
                Philadelphie décrite dans le livre de l'Apocalypse. Philadelphie signifie 
                « amour fraternel », et c'est cette valeur qui guide chacune de nos actions.
              </p>
              <p className="mt-4 text-surface-500 leading-relaxed">
                Nous croyons que la force d'une communauté réside dans l'amour qu'elle partage 
                et dans sa fidélité à la Parole de Dieu. C'est pourquoi nous mettons un point 
                d'honneur à enseigner la Bible avec profondeur et à accueillir chaque personne 
                avec chaleur.
              </p>
              <div className="mt-8">
                <CTAButton to="/discover/about">
                  Lire notre histoire <ArrowRight className="w-4 h-4" />
                </CTAButton>
              </div>
            </motion.div>
          </div>
        </div>
      </section>
    </>
  );
}
