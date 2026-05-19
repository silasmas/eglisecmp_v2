import { useState, type FormEvent } from 'react';
import { HandHeart } from 'lucide-react';
import PageHero from '../components/ui/PageHero';
import { submitSiteInquiry } from '../lib/siteApi';

/** Page formulaire public : transmission d’une requête de prière au pastoral. */
export default function PrayerRequestPage() {
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [message, setMessage] = useState('');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [done, setDone] = useState(false);

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setError(null);
    if (name.trim() === '' || message.trim() === '') {
      setError('Le nom et le message sont obligatoires.');
      return;
    }

    setBusy(true);
    try {
      await submitSiteInquiry({
        kind: 'prayer_request',
        name: name.trim(),
        message: message.trim(),
        email: email.trim(),
        phone: phone.trim(),
      });
      setDone(true);
      setMessage('');
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Impossible d’envoyer pour le moment.');
    } finally {
      setBusy(false);
    }
  };

  return (
    <>
      <PageHero
        badge="Prière"
        title="Requête de prière"
        description="Confiez-nous vos sujets de prière. Notre équipe portera votre intention devant Dieu."
        compact
      />

      <section className="mx-auto max-w-2xl px-4 pb-24 pt-8 sm:px-6 lg:px-8">
        {done ? (
          <div className="rounded-3xl border border-emerald-200 bg-emerald-50 px-6 py-8 text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-100">
            <p className="font-heading text-lg font-bold">Merci, {name}.</p>
            <p className="mt-2 text-sm opacity-90">Votre requête a bien été transmise. Que le Seigneur vous fortifie.</p>
          </div>
        ) : (
          <form onSubmit={(event) => void handleSubmit(event)} className="space-y-6 rounded-3xl border border-surface-200 bg-white p-8 shadow-sm dark:border-surface-700 dark:bg-surface-950">
            <div className="flex items-center gap-3 text-burgundy-800 dark:text-burgundy-300">
              <HandHeart className="h-8 w-8" aria-hidden />
              <p className="text-sm text-surface-600 dark:text-surface-400">Les champs marqués * sont obligatoires.</p>
            </div>

            {error !== null ? <p className="rounded-2xl bg-burgundy-50 px-4 py-2 text-sm text-burgundy-900">{error}</p> : null}

            <div>
              <label className="text-xs font-semibold uppercase tracking-wide text-surface-500" htmlFor="rq-name">
                Nom *
              </label>
              <input
                id="rq-name"
                value={name}
                onChange={(event) => setName(event.target.value)}
                required
                className="mt-2 w-full rounded-2xl border border-surface-200 px-4 py-3 text-sm dark:border-surface-600 dark:bg-surface-900 dark:text-white"
              />
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
              <div>
                <label className="text-xs font-semibold uppercase tracking-wide text-surface-500" htmlFor="rq-email">
                  Courriel
                </label>
                <input
                  id="rq-email"
                  type="email"
                  value={email}
                  onChange={(event) => setEmail(event.target.value)}
                  className="mt-2 w-full rounded-2xl border border-surface-200 px-4 py-3 text-sm dark:border-surface-600 dark:bg-surface-900 dark:text-white"
                />
              </div>
              <div>
                <label className="text-xs font-semibold uppercase tracking-wide text-surface-500" htmlFor="rq-phone">
                  Téléphone
                </label>
                <input
                  id="rq-phone"
                  type="tel"
                  value={phone}
                  onChange={(event) => setPhone(event.target.value)}
                  className="mt-2 w-full rounded-2xl border border-surface-200 px-4 py-3 text-sm dark:border-surface-600 dark:bg-surface-900 dark:text-white"
                />
              </div>
            </div>

            <div>
              <label className="text-xs font-semibold uppercase tracking-wide text-surface-500" htmlFor="rq-msg">
                Votre requête *
              </label>
              <textarea
                id="rq-msg"
                required
                rows={6}
                value={message}
                onChange={(event) => setMessage(event.target.value)}
                className="mt-2 w-full rounded-2xl border border-surface-200 px-4 py-3 text-sm dark:border-surface-600 dark:bg-surface-900 dark:text-white"
                placeholder="Partagez ce pour quoi vous souhaitez que nous prions…"
              />
            </div>

            <button
              type="submit"
              disabled={busy}
              className="w-full rounded-2xl bg-burgundy-900 py-3.5 text-sm font-semibold text-white transition hover:bg-burgundy-800 disabled:opacity-40"
            >
              {busy ? 'Envoi…' : 'Envoyer la demande'}
            </button>
          </form>
        )}
      </section>
    </>
  );
}
