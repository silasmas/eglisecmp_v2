import { motion } from 'framer-motion';
import { Mail, MapPin, Phone, Send } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import PageHero from '../components/ui/PageHero';
import { churchInfo } from '../data/content';

export default function ContactPage() {
  const [submitted, setSubmitted] = useState(false);

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    setSubmitted(true);
  };

  return (
    <>
      <PageHero
        badge="Contact"
        title="Nous contacter"
        description="Une question, un besoin, une envie de nous rejoindre ? N'hésitez pas à nous écrire."
      />

      <section className="py-24">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div className="grid lg:grid-cols-5 gap-12">
            {/* Contact info */}
            <motion.div
              initial={{ opacity: 0, x: -20 }}
              whileInView={{ opacity: 1, x: 0 }}
              viewport={{ once: true }}
              transition={{ duration: 0.5 }}
              className="lg:col-span-2 space-y-8"
            >
              <div>
                <h2 className="font-heading font-semibold text-2xl text-surface-900 mb-6">
                  Informations de contact
                </h2>
                <div className="space-y-5">
                  <div className="flex items-start gap-4">
                    <div className="w-10 h-10 rounded-xl bg-burgundy-50 border border-burgundy-100 flex items-center justify-center shrink-0">
                      <MapPin className="w-5 h-5 text-burgundy-700" />
                    </div>
                    <div>
                      <p className="text-surface-900 font-medium text-sm">Adresse</p>
                      <p className="text-surface-500 text-sm mt-1">{churchInfo.address}</p>
                    </div>
                  </div>
                  <div className="flex items-start gap-4">
                    <div className="w-10 h-10 rounded-xl bg-burgundy-50 border border-burgundy-100 flex items-center justify-center shrink-0">
                      <Phone className="w-5 h-5 text-burgundy-700" />
                    </div>
                    <div>
                      <p className="text-surface-900 font-medium text-sm">Téléphone</p>
                      <p className="text-surface-500 text-sm mt-1">{churchInfo.phone}</p>
                    </div>
                  </div>
                  <div className="flex items-start gap-4">
                    <div className="w-10 h-10 rounded-xl bg-burgundy-50 border border-burgundy-100 flex items-center justify-center shrink-0">
                      <Mail className="w-5 h-5 text-burgundy-700" />
                    </div>
                    <div>
                      <p className="text-surface-900 font-medium text-sm">Email</p>
                      <p className="text-surface-500 text-sm mt-1">{churchInfo.email}</p>
                    </div>
                  </div>
                  <div className="flex items-start gap-4">
                    <div className="w-10 h-10 rounded-xl bg-burgundy-50 border border-burgundy-100 flex items-center justify-center shrink-0">
                      <MapPin className="w-5 h-5 text-burgundy-700" />
                    </div>
                    <div>
                      <p className="text-surface-900 font-medium text-sm">Boîte postale</p>
                      <p className="text-surface-500 text-sm mt-1">{churchInfo.postalBox}</p>
                    </div>
                  </div>
                </div>
              </div>

              <div className="rounded-2xl overflow-hidden aspect-[4/3]">
                <img
                  src="https://images.unsplash.com/photo-1511632765486-a01980e01a18?w=500&h=375&fit=crop"
                  alt="Communauté CMP"
                  className="w-full h-full object-cover"
                />
              </div>
            </motion.div>

            {/* Contact form */}
            <motion.div
              initial={{ opacity: 0, x: 20 }}
              whileInView={{ opacity: 1, x: 0 }}
              viewport={{ once: true }}
              transition={{ duration: 0.5, delay: 0.2 }}
              className="lg:col-span-3"
            >
              <div className="rounded-2xl bg-white border border-surface-200 shadow-sm p-8 sm:p-10">
                {submitted ? (
                  <div className="text-center py-12">
                    <div className="w-16 h-16 rounded-full bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center mx-auto mb-6">
                      <Send className="w-7 h-7 text-emerald-400" />
                    </div>
                    <h3 className="font-heading font-semibold text-2xl text-surface-900 mb-3">
                      Message envoyé !
                    </h3>
                    <p className="text-surface-500">
                      Merci de nous avoir contactés. Nous vous répondrons dans les plus brefs délais.
                    </p>
                  </div>
                ) : (
                  <>
                    <h3 className="font-heading font-semibold text-xl text-surface-900 mb-6">
                      Envoyez-nous un message
                    </h3>
                    <form onSubmit={handleSubmit} className="space-y-5">
                      <div className="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                          <label htmlFor="firstName" className="block text-sm font-medium text-surface-700 mb-2">
                            Prénom
                          </label>
                          <input
                            id="firstName"
                            name="firstName"
                            type="text"
                            required
                            className="w-full px-4 py-3 rounded-xl bg-surface-50 border border-surface-200 text-surface-900 text-sm placeholder:text-surface-400 focus:border-burgundy-600 focus:ring-1 focus:ring-burgundy-600 transition-colors"
                            placeholder="Votre prénom"
                          />
                        </div>
                        <div>
                          <label htmlFor="lastName" className="block text-sm font-medium text-surface-700 mb-2">
                            Nom
                          </label>
                          <input
                            id="lastName"
                            name="lastName"
                            type="text"
                            required
                            className="w-full px-4 py-3 rounded-xl bg-surface-50 border border-surface-200 text-surface-900 text-sm placeholder:text-surface-400 focus:border-burgundy-600 focus:ring-1 focus:ring-burgundy-600 transition-colors"
                            placeholder="Votre nom"
                          />
                        </div>
                      </div>
                      <div>
                        <label htmlFor="email" className="block text-sm font-medium text-surface-700 mb-2">
                          Email
                        </label>
                        <input
                          id="email"
                          name="email"
                          type="email"
                          required
                          className="w-full px-4 py-3 rounded-xl bg-surface-50 border border-surface-200 text-surface-900 text-sm placeholder:text-surface-400 focus:border-burgundy-600 focus:ring-1 focus:ring-burgundy-600 transition-colors"
                          placeholder="votre@email.com"
                        />
                      </div>
                      <div>
                        <label htmlFor="subject" className="block text-sm font-medium text-surface-700 mb-2">
                          Sujet
                        </label>
                        <select
                          id="subject"
                          name="subject"
                          className="w-full px-4 py-3 rounded-xl bg-surface-50 border border-surface-200 text-surface-900 text-sm focus:border-burgundy-600 focus:ring-1 focus:ring-burgundy-600 transition-colors"
                        >
                          <option value="general">Question générale</option>
                          <option value="visit">Prendre rendez-vous</option>
                          <option value="prayer">Demande de prière</option>
                          <option value="membership">Devenir membre</option>
                          <option value="other">Autre</option>
                        </select>
                      </div>
                      <div>
                        <label htmlFor="message" className="block text-sm font-medium text-surface-700 mb-2">
                          Message
                        </label>
                        <textarea
                          id="message"
                          name="message"
                          required
                          rows={5}
                          className="w-full px-4 py-3 rounded-xl bg-surface-50 border border-surface-200 text-surface-900 text-sm placeholder:text-surface-400 focus:border-burgundy-600 focus:ring-1 focus:ring-burgundy-600 transition-colors resize-none"
                          placeholder="Votre message..."
                        />
                      </div>
                      <button
                        type="submit"
                        className="w-full px-6 py-3.5 rounded-xl bg-burgundy-800 text-white font-semibold text-sm hover:bg-burgundy-700 transition-colors shadow-lg shadow-burgundy-900/30 flex items-center justify-center gap-2"
                      >
                        <Send className="w-4 h-4" />
                        Envoyer le message
                      </button>
                    </form>
                  </>
                )}
              </div>
            </motion.div>
          </div>
        </div>
      </section>
    </>
  );
}
