import { motion } from 'framer-motion';
import { Mail, MapPin, Phone, Send } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import { churchInfo } from '../../data/content';

/**
 * Bloc contact intégré à la page « Nous rejoindre » (ancienne page Contact).
 */
export default function JoinContactSection() {
  const [submitted, setSubmitted] = useState(false);

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    setSubmitted(true);
  };

  return (
    <section id="contact" className="border-t border-surface-200 bg-surface-50 py-24 scroll-mt-24">
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div className="mb-12 text-center">
          <p className="mb-2 text-sm font-semibold uppercase tracking-widest text-burgundy-700">Contact</p>
          <h2 className="font-heading text-3xl font-semibold text-surface-900">Nous contacter</h2>
          <p className="mx-auto mt-3 max-w-xl text-surface-600">
            Une question, un besoin, une envie de nous rejoindre ? Écrivez-nous.
          </p>
        </div>

        <div className="grid gap-12 lg:grid-cols-5">
          <motion.div
            initial={{ opacity: 0, x: -20 }}
            whileInView={{ opacity: 1, x: 0 }}
            viewport={{ once: true }}
            transition={{ duration: 0.5 }}
            className="space-y-8 lg:col-span-2"
          >
            <div className="space-y-5">
              {[
                { Icon: MapPin, label: 'Adresse', value: churchInfo.address },
                { Icon: Phone, label: 'Téléphone', value: churchInfo.phone },
                { Icon: Mail, label: 'Email', value: churchInfo.email },
                { Icon: MapPin, label: 'Boîte postale', value: churchInfo.postalBox },
              ].map(({ Icon, label, value }) => (
                <div key={label} className="flex items-start gap-4">
                  <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-burgundy-100 bg-burgundy-50">
                    <Icon className="h-5 w-5 text-burgundy-700" />
                  </div>
                  <div>
                    <p className="text-sm font-medium text-surface-900">{label}</p>
                    <p className="mt-1 text-sm text-surface-500">{value}</p>
                  </div>
                </div>
              ))}
            </div>
            <div className="aspect-[4/3] overflow-hidden rounded-2xl">
              <img
                src="https://images.unsplash.com/photo-1511632765486-a01980e01a18?w=500&h=375&fit=crop"
                alt="Communauté CMP"
                className="h-full w-full object-cover"
              />
            </div>
          </motion.div>

          <motion.div
            initial={{ opacity: 0, x: 20 }}
            whileInView={{ opacity: 1, x: 0 }}
            viewport={{ once: true }}
            transition={{ duration: 0.5, delay: 0.2 }}
            className="lg:col-span-3"
          >
            <div className="rounded-2xl border border-surface-200 bg-white p-8 shadow-sm sm:p-10">
              {submitted ? (
                <div className="py-12 text-center">
                  <div className="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full border border-emerald-500/20 bg-emerald-500/10">
                    <Send className="h-7 w-7 text-emerald-400" />
                  </div>
                  <h3 className="mb-3 font-heading text-2xl font-semibold text-surface-900">Message envoyé !</h3>
                  <p className="text-surface-500">Merci de nous avoir contactés. Nous vous répondrons rapidement.</p>
                </div>
              ) : (
                <>
                  <h3 className="mb-6 font-heading text-xl font-semibold text-surface-900">Envoyez-nous un message</h3>
                  <form onSubmit={handleSubmit} className="space-y-5">
                    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                      <div>
                        <label htmlFor="contactFirstName" className="mb-2 block text-sm font-medium text-surface-700">
                          Prénom
                        </label>
                        <input id="contactFirstName" required className="w-full rounded-xl border border-surface-200 bg-surface-50 px-4 py-3 text-sm" />
                      </div>
                      <div>
                        <label htmlFor="contactLastName" className="mb-2 block text-sm font-medium text-surface-700">
                          Nom
                        </label>
                        <input id="contactLastName" required className="w-full rounded-xl border border-surface-200 bg-surface-50 px-4 py-3 text-sm" />
                      </div>
                    </div>
                    <div>
                      <label htmlFor="contactEmail" className="mb-2 block text-sm font-medium text-surface-700">
                        Email
                      </label>
                      <input id="contactEmail" type="email" required className="w-full rounded-xl border border-surface-200 bg-surface-50 px-4 py-3 text-sm" />
                    </div>
                    <div>
                      <label htmlFor="contactSubject" className="mb-2 block text-sm font-medium text-surface-700">
                        Sujet
                      </label>
                      <select id="contactSubject" className="w-full rounded-xl border border-surface-200 bg-surface-50 px-4 py-3 text-sm">
                        <option value="general">Question générale</option>
                        <option value="visit">Prendre rendez-vous</option>
                        <option value="prayer">Demande de prière</option>
                        <option value="membership">Devenir membre</option>
                        <option value="other">Autre</option>
                      </select>
                    </div>
                    <div>
                      <label htmlFor="contactMessage" className="mb-2 block text-sm font-medium text-surface-700">
                        Message
                      </label>
                      <textarea id="contactMessage" required rows={5} className="w-full resize-none rounded-xl border border-surface-200 bg-surface-50 px-4 py-3 text-sm" />
                    </div>
                    <button
                      type="submit"
                      className="flex w-full items-center justify-center gap-2 rounded-xl bg-burgundy-800 px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-burgundy-900/30 hover:bg-burgundy-700"
                    >
                      <Send className="h-4 w-4" />
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
  );
}
