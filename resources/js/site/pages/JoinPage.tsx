import { motion } from 'framer-motion';
import { Clock, MapPin, Users, HandHeart, ArrowRight } from 'lucide-react';
import PageHero from '../components/ui/PageHero';
import CTAButton from '../components/ui/CTAButton';
import { churchInfo } from '../data/content';

export default function JoinPage() {
  return (
    <>
      <PageHero
        badge="Nous rejoindre"
        title="Faites partie de la famille"
        description="Prenez rendez-vous, devenez membre ou partagez vos sujets de prière."
        backgroundImage="https://images.unsplash.com/photo-1519834785169-98be25ec3f84?w=1400&h=600&fit=crop"
      />

      <section className="py-24">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          {/* Cards grid */}
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-20">
            {[
              {
                icon: MapPin,
                title: 'Prendre rendez-vous',
                description:
                  "Indiquez vos disponibilités pour une visite ou un entretien pastoral. Nous vous recontacterons rapidement.",
                to: '/rendez-vous',
                cta: 'Proposer une date',
              },
              {
                icon: Users,
                title: 'Devenir membre',
                description:
                  "Rejoignez officiellement notre communauté et engagez-vous dans la vie de l'église. Participez à nos classes de membres pour découvrir notre vision.",
                to: '/join',
                cta: "S'inscrire",
              },
              {
                icon: HandHeart,
                title: 'Demande de prière',
                description:
                  "Partagez vos sujets de prière avec notre équipe pastorale. Nous croyons en la puissance de la prière et nous nous tenons à vos côtés.",
                to: '/requete-de-priere',
                cta: 'Soumettre une demande',
              },
            ].map((card, i) => (
              <motion.div
                key={card.title}
                initial={{ opacity: 0, y: 20 }}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true }}
                transition={{ duration: 0.4, delay: i * 0.1 }}
                className="rounded-2xl bg-white border border-surface-200 shadow-sm p-8 flex flex-col"
              >
                <div className="w-14 h-14 rounded-2xl bg-burgundy-50 border border-burgundy-100 flex items-center justify-center mb-6">
                  <card.icon className="w-6 h-6 text-burgundy-700" />
                </div>
                <h3 className="font-heading font-semibold text-surface-900 text-xl mb-3">
                  {card.title}
                </h3>
                <p className="text-surface-500 text-sm leading-relaxed flex-1 mb-6">
                  {card.description}
                </p>
                <CTAButton to={card.to} size="sm">
                  {card.cta} <ArrowRight className="w-3.5 h-3.5" />
                </CTAButton>
              </motion.div>
            ))}
          </div>

          {/* Service times */}
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ duration: 0.5 }}
            className="rounded-3xl bg-burgundy-50 border border-burgundy-100 p-10 sm:p-14"
          >
            <div className="grid lg:grid-cols-2 gap-12 items-center">
              <div>
                <Clock className="w-8 h-8 text-burgundy-700 mb-4" />
                <h2 className="font-heading font-semibold text-3xl text-surface-900 mb-4">
                  Horaires des cultes
                </h2>
                <p className="text-surface-600 leading-relaxed">
                  Nous nous réunissons plusieurs fois par semaine pour la louange, 
                  l'enseignement et la prière. Tous nos cultes sont ouverts au public.
                </p>
              </div>
              <div className="space-y-4">
                {[
                  { day: 'Dimanche', name: 'Culte dominical', time: churchInfo.serviceTimes.sunday },
                  { day: 'Mercredi', name: "Culte d'enseignement", time: churchInfo.serviceTimes.wednesday },
                  { day: 'Jeudi', name: "Culte d'intercession", time: churchInfo.serviceTimes.thursday },
                  { day: 'Lundi à Vendredi', name: 'Matinées de Gloire', time: churchInfo.serviceTimes.morningGlory },
                ].map((service) => (
                  <div
                    key={service.day}
                    className="flex items-center justify-between rounded-xl bg-white border border-surface-200 shadow-sm px-6 py-4"
                  >
                    <div>
                      <p className="text-surface-900 font-medium">{service.name}</p>
                      <p className="text-surface-500 text-sm">{service.day}</p>
                    </div>
                    <span className="text-burgundy-700 font-semibold text-sm">{service.time}</span>
                  </div>
                ))}
              </div>
            </div>
          </motion.div>
        </div>
      </section>
    </>
  );
}
