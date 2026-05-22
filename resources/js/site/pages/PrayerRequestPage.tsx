import { useState, type FormEvent } from 'react';
import { RotateCcw, Send } from 'lucide-react';
import PageHero from '../components/ui/PageHero';
import { PRAYER_REQUEST_COUNTRIES } from '../data/countries';
import { submitSiteInquiry } from '../lib/siteApi';
import { cn } from '../lib/utils';

const SCRIPTURE_REFERENCE = 'Jacques 5:16';
const SCRIPTURE_TEXT =
  'Confessez vos fautes les uns aux autres, et priez les uns pour les autres, afin que vous soyez guéris. La prière fervente du juste a une grande efficacité';

const INPUT_CLASS =
  'w-full rounded-lg border border-transparent bg-surface-100 px-4 py-3 text-sm text-surface-900 placeholder:text-surface-400 focus:border-burgundy-600 focus:bg-white focus:outline-none focus:ring-1 focus:ring-burgundy-600 dark:bg-surface-800 dark:text-white dark:placeholder:text-surface-500 dark:focus:bg-surface-900';

/**
 * Affiche un libellé de champ avec astérisque optionnel pour les champs obligatoires.
 *
 * @param props.label Texte du libellé.
 * @param props.htmlFor Identifiant du champ associé.
 * @param props.required Indique si le champ est obligatoire.
 * @returns Élément label formaté.
 */
function FieldLabel({
  label,
  htmlFor,
  required = false,
}: {
  label: string;
  htmlFor: string;
  required?: boolean;
}) {
  return (
    <label htmlFor={htmlFor} className="mb-2 block text-sm font-medium text-surface-800 dark:text-surface-200">
      {label}
      {required ? <span className="text-burgundy-700 dark:text-burgundy-400">*</span> : null}
    </label>
  );
}

/** Page formulaire public : transmission d’une requête de prière au pastoral. */
export default function PrayerRequestPage() {
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [country, setCountry] = useState('');
  const [isAnonymous, setIsAnonymous] = useState(false);
  const [message, setMessage] = useState('');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [done, setDone] = useState(false);

  /**
   * Active ou désactive le mode anonyme et efface les coordonnées masquées.
   *
   * @param checked État de la case « Envoyer dans l'anonymat ».
   */
  const handleAnonymousChange = (checked: boolean) => {
    setIsAnonymous(checked);
    if (checked) {
      setName('');
      setEmail('');
      setPhone('');
      setCountry('');
    }
  };

  /**
   * Réinitialise le formulaire pour permettre une nouvelle requête.
   */
  const resetForm = () => {
    setDone(false);
    setError(null);
    setName('');
    setEmail('');
    setPhone('');
    setCountry('');
    setIsAnonymous(false);
    setMessage('');
  };

  /**
   * Valide et envoie la requête de prière vers l’API publique.
   *
   * @param event Événement de soumission du formulaire.
   */
  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setError(null);

    if (!isAnonymous) {
      if (name.trim() === '') {
        setError('Le nom complet est obligatoire.');
        return;
      }
      if (phone.trim() === '') {
        setError('Le téléphone est obligatoire.');
        return;
      }
      if (country.trim() === '') {
        setError('Veuillez sélectionner un pays.');
        return;
      }
    }

    if (message.trim() === '') {
      setError('Votre requête est obligatoire.');
      return;
    }

    setBusy(true);
    try {
      await submitSiteInquiry({
        kind: 'prayer_request',
        name: isAnonymous ? 'Anonyme' : name.trim(),
        message: message.trim(),
        email: isAnonymous ? undefined : email.trim(),
        phone: isAnonymous ? undefined : phone.trim(),
        country: isAnonymous ? undefined : country.trim(),
        is_anonymous: isAnonymous,
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
        description={
          done
            ? 'Votre requête a bien été transmise. Notre équipe d’intercession en a été informée.'
            : 'Confiez-nous vos sujets de prière. Notre équipe portera votre intention devant Dieu.'
        }
        compact
      />

      <section className="mx-auto max-w-2xl px-4 pb-24 pt-8 sm:px-6 lg:px-8">
        <div className="overflow-hidden rounded-2xl border border-surface-200 bg-white shadow-md dark:border-surface-700 dark:bg-surface-950">
          <div className="bg-burgundy-900 px-6 py-8 text-white sm:px-8 sm:py-10">
            <h2 className="font-heading text-xl font-bold leading-tight sm:text-2xl">Requête de prière</h2>
            <p className="mt-5 text-sm font-semibold text-burgundy-100">{SCRIPTURE_REFERENCE}</p>
            <p className="mt-2 text-sm leading-relaxed text-white/90 sm:text-base">{SCRIPTURE_TEXT}</p>
          </div>

          {done ? (
            <div className="px-6 py-10 text-center text-emerald-900 dark:text-emerald-100 sm:px-8">
              <div className="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-full bg-emerald-500/15">
                <Send className="h-6 w-6" aria-hidden />
              </div>
              <p className="font-heading text-xl font-bold">
                Merci{isAnonymous ? '' : `, ${name}`}.
              </p>
              <p className="mt-3 text-sm leading-relaxed text-surface-600 dark:text-surface-300">
                Votre requête a bien été transmise à notre équipe pastorale. Que le Seigneur vous fortifie.
              </p>
              <button
                type="button"
                onClick={resetForm}
                className="mt-8 inline-flex items-center gap-2 rounded-xl bg-burgundy-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-burgundy-800"
              >
                <RotateCcw className="h-4 w-4" aria-hidden />
                Envoyer une autre requête
              </button>
            </div>
          ) : (
            <form
              onSubmit={(event) => void handleSubmit(event)}
              className="px-6 py-6 sm:px-8 sm:py-8"
            >
              {error !== null ? (
                <p className="mb-6 rounded-xl bg-burgundy-50 px-4 py-3 text-sm text-burgundy-900 dark:bg-burgundy-950/40 dark:text-burgundy-100">
                  {error}
                </p>
              ) : null}

              <div className="space-y-5">
                <label className="flex cursor-pointer items-center gap-3 rounded-xl border border-surface-200 bg-surface-50 px-4 py-3 dark:border-surface-700 dark:bg-surface-900/40">
                  <input
                    type="checkbox"
                    checked={isAnonymous}
                    onChange={(event) => handleAnonymousChange(event.target.checked)}
                    className="h-5 w-5 rounded border-2 border-burgundy-700 text-burgundy-800 focus:ring-burgundy-600 dark:border-burgundy-500"
                  />
                  <span className="text-sm font-medium text-surface-800 dark:text-surface-200">
                    Envoyer dans l&apos;anonymat
                  </span>
                </label>

                {isAnonymous ? (
                  <p className="text-sm leading-relaxed text-surface-500 dark:text-surface-400">
                    Seule votre requête sera transmise. Aucune coordonnée personnelle n&apos;est demandée.
                  </p>
                ) : null}

                {!isAnonymous ? (
                  <>
                    <div>
                      <FieldLabel label="Nom complet" htmlFor="rq-name" required />
                      <input
                        id="rq-name"
                        value={name}
                        onChange={(event) => setName(event.target.value)}
                        required
                        placeholder="Votre nom au complet"
                        className={INPUT_CLASS}
                      />
                    </div>

                    <div className="grid gap-5 sm:grid-cols-2">
                      <div>
                        <FieldLabel label="Email" htmlFor="rq-email" />
                        <input
                          id="rq-email"
                          type="email"
                          value={email}
                          onChange={(event) => setEmail(event.target.value)}
                          placeholder="Email (optionnel)"
                          className={INPUT_CLASS}
                        />
                      </div>
                      <div>
                        <FieldLabel label="Téléphone" htmlFor="rq-phone" required />
                        <input
                          id="rq-phone"
                          type="tel"
                          value={phone}
                          onChange={(event) => setPhone(event.target.value)}
                          required
                          placeholder="Téléphone (optionnel)"
                          className={INPUT_CLASS}
                        />
                      </div>
                    </div>

                    <div>
                      <FieldLabel label="Pays" htmlFor="rq-country" required />
                      <select
                        id="rq-country"
                        value={country}
                        onChange={(event) => setCountry(event.target.value)}
                        required
                        className={cn(INPUT_CLASS, 'appearance-none bg-size-[1rem] bg-position-[right_0.75rem_center] bg-no-repeat pr-10')}
                        style={{
                          backgroundImage:
                            "url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E\")",
                        }}
                      >
                        <option value="">Sélectionnez votre pays</option>
                        {PRAYER_REQUEST_COUNTRIES.map((countryName) => (
                          <option key={countryName} value={countryName}>
                            {countryName}
                          </option>
                        ))}
                      </select>
                    </div>
                  </>
                ) : null}

                <div>
                  <FieldLabel label="Votre requête" htmlFor="rq-msg" required />
                  <textarea
                    id="rq-msg"
                    required
                    rows={6}
                    value={message}
                    onChange={(event) => setMessage(event.target.value)}
                    className={cn(INPUT_CLASS, 'resize-y')}
                  />
                </div>
              </div>

              <button
                type="submit"
                disabled={busy}
                className="mt-8 flex w-full items-center justify-center gap-2 rounded-xl bg-burgundy-900 py-3.5 text-sm font-semibold text-white transition hover:bg-burgundy-800 disabled:opacity-40"
              >
                <Send className="h-4 w-4" aria-hidden />
                {busy ? 'Envoi…' : 'Envoyer la requête'}
              </button>
            </form>
          )}
        </div>
      </section>
    </>
  );
}
