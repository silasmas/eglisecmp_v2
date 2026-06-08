import { useState, type FormEvent } from 'react';
import { Bell } from 'lucide-react';
import { subscribeToAlerts } from '../../lib/siteApi';
import { cn } from '../../lib/utils';
import AlertSubscribeCheckboxes from './AlertSubscribeCheckboxes';

const INPUT_CLASS =
  'w-full rounded-lg border border-surface-200 bg-white px-3 py-2.5 text-sm text-surface-900 focus:border-[#950000] focus:outline-none focus:ring-1 focus:ring-[#950000] dark:border-surface-700 dark:bg-surface-900 dark:text-white';

export type AlertSubscribeSource = 'footer' | 'events' | 'live' | 'testimony' | 'bunda' | 'weekly';

type AlertSubscribeFormProps = {
  source: AlertSubscribeSource;
  className?: string;
  title?: string;
  /** `embedded` : contenu seul, sans carte (utilisé dans une modale). */
  variant?: 'card' | 'embedded';
  defaultNotifyLive?: boolean;
  defaultNotifyEvents?: boolean;
  /** Appelé après inscription réussie (ex. fermer la modale). */
  onSuccess?: () => void;
};

/**
 * Formulaire d’abonnement aux alertes live et événements (opt-in RGPD).
 *
 * @param source Origine de l’inscription transmise à l’API.
 * @param className Classes CSS additionnelles.
 * @param title Titre du bloc formulaire.
 * @param variant Présentation carte ou intégrée dans une modale.
 * @param defaultNotifyLive Valeur initiale de la case live.
 * @param defaultNotifyEvents Valeur initiale de la case événements.
 * @param onSuccess Callback après succès.
 */
export default function AlertSubscribeForm({
  source,
  className,
  title,
  variant = 'card',
  defaultNotifyLive = true,
  defaultNotifyEvents = true,
  onSuccess,
}: AlertSubscribeFormProps) {
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [notifyLive, setNotifyLive] = useState(defaultNotifyLive);
  const [notifyEvents, setNotifyEvents] = useState(defaultNotifyEvents);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  const handleSubmit = async (event: FormEvent) => {
    event.preventDefault();
    setBusy(true);
    setError(null);
    setSuccess(null);

    try {
      const result = await subscribeToAlerts({
        email: email.trim(),
        phone: phone.trim(),
        notify_live: notifyLive,
        notify_events: notifyEvents,
        source,
      });
      setSuccess(result.message);
      setEmail('');
      setPhone('');
      if (onSuccess) {
        window.setTimeout(() => {
          onSuccess();
        }, 1800);
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Inscription impossible.');
    } finally {
      setBusy(false);
    }
  };

  const wrapperClass =
    variant === 'embedded'
      ? 'rounded-2xl border border-surface-200 bg-white p-5 shadow-2xl dark:border-surface-700 dark:bg-surface-900'
      : 'rounded-xl border border-surface-200 bg-white p-5 shadow-sm dark:border-surface-700 dark:bg-surface-900';

  if (success !== null) {
    return (
      <div className={cn(wrapperClass, className)}>
        <div className="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-900">{success}</div>
        {onSuccess ? (
          <button type="button" className="tw-cta-primary mt-4 w-full" onClick={onSuccess}>
            Fermer
          </button>
        ) : null}
      </div>
    );
  }

  return (
    <form onSubmit={(e) => void handleSubmit(e)} className={cn(wrapperClass, className)}>
      <div className="mb-4 flex items-center gap-2">
        <Bell className="h-5 w-5 text-[#950000]" aria-hidden />
        <h3 className="text-base font-bold text-surface-900 dark:text-white">{title ?? 'Recevoir nos alertes'}</h3>
      </div>
      <p className="mb-4 text-sm text-surface-600 dark:text-surface-400">
        Inscription volontaire. Vous pourrez vous désabonner depuis chaque e-mail reçu.
      </p>
      <div className="mb-3 grid gap-3 sm:grid-cols-2">
        <div>
          <label className="mb-1 block text-xs font-medium">E-mail</label>
          <input
            type="email"
            className={INPUT_CLASS}
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            placeholder="vous@exemple.com"
          />
        </div>
        <div>
          <label className="mb-1 block text-xs font-medium">WhatsApp / SMS</label>
          <input
            type="tel"
            className={INPUT_CLASS}
            value={phone}
            onChange={(e) => setPhone(e.target.value)}
            placeholder="+243…"
          />
        </div>
      </div>
      <p className="mb-3 text-xs text-surface-500">Renseignez au moins l’e-mail ou le téléphone.</p>
      <AlertSubscribeCheckboxes
        notifyLive={notifyLive}
        notifyEvents={notifyEvents}
        onNotifyLiveChange={setNotifyLive}
        onNotifyEventsChange={setNotifyEvents}
        className="mb-4"
      />
      {error !== null ? <p className="mb-3 text-sm text-red-600">{error}</p> : null}
      <button type="submit" disabled={busy} className="tw-cta-primary w-full disabled:opacity-60">
        {busy ? 'Inscription…' : 'S’abonner aux alertes'}
      </button>
    </form>
  );
}
