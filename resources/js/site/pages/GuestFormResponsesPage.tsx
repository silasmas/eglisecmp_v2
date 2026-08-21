import { useMemo, useState, type FormEvent } from 'react';
import { useParams, useSearchParams } from 'react-router-dom';
import { CheckCircle2, Loader2, Lock } from 'lucide-react';
import {
  acknowledgeGuestFormResponses,
  unlockGuestFormResponses,
  type GuestFormUnlockResponse,
} from '../lib/siteApi';

/**
 * Portail département : consultation des réponses filtrées d’une fiche d’accueil.
 */
export default function GuestFormResponsesPage() {
  const { submissionToken = '' } = useParams();
  const [searchParams] = useSearchParams();
  const departmentId = Number(searchParams.get('dept') || '0');

  const [password, setPassword] = useState('');
  const [busy, setBusy] = useState(false);
  const [ackBusy, setAckBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [ackMessage, setAckMessage] = useState<string | null>(null);
  const [acknowledgerName, setAcknowledgerName] = useState('');
  const [data, setData] = useState<GuestFormUnlockResponse | null>(null);

  const canUnlock = useMemo(
    () => submissionToken !== '' && departmentId > 0 && password.trim() !== '',
    [submissionToken, departmentId, password],
  );

  const isAcknowledged = Boolean(data?.acknowledgment?.acknowledged);

  /**
   * Vérifie le mot de passe et charge les réponses du département.
   *
   * @param event Événement submit.
   */
  const onUnlock = async (event: FormEvent) => {
    event.preventDefault();
    if (!canUnlock || busy) {
      return;
    }
    setBusy(true);
    setError(null);
    setAckMessage(null);
    try {
      const res = await unlockGuestFormResponses(submissionToken, password.trim(), departmentId);
      setData(res);
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'Accès refusé.');
      setData(null);
    } finally {
      setBusy(false);
    }
  };

  /**
   * Enregistre l’accusé de réception pour ce département.
   */
  const onAcknowledge = async () => {
    if (!data || ackBusy || isAcknowledged || !canUnlock) {
      return;
    }
    setAckBusy(true);
    setAckMessage(null);
    try {
      const res = await acknowledgeGuestFormResponses(
        submissionToken,
        password.trim(),
        departmentId,
        acknowledgerName.trim() || undefined,
      );
      setAckMessage(res.message);
      setData({
        ...data,
        acknowledgment: {
          acknowledged: true,
          acknowledged_at: res.acknowledged_at,
          acknowledged_by_name: acknowledgerName.trim() || data.acknowledgment?.acknowledged_by_name || null,
          sent_count: data.acknowledgment?.sent_count ?? 0,
        },
      });
    } catch (err: unknown) {
      setAckMessage(err instanceof Error ? err.message : 'Impossible d’accuser réception.');
    } finally {
      setAckBusy(false);
    }
  };

  /**
   * Affiche une valeur de réponse de façon lisible.
   *
   * @param value Valeur brute.
   */
  const formatValue = (value: unknown): string => {
    if (typeof value === 'boolean') {
      return value ? 'Oui' : 'Non';
    }
    if (Array.isArray(value)) {
      return value.map((item) => (typeof item === 'string' ? item : JSON.stringify(item))).join(', ');
    }
    if (value === null || value === undefined) {
      return '—';
    }
    return String(value);
  };

  return (
    <div className="mx-auto max-w-2xl px-4 py-12">
      <h1 className="text-2xl font-bold text-surface-900 dark:text-white">Réponses — accueil pasteur invité</h1>
      <p className="mt-2 text-sm text-surface-600 dark:text-surface-400">
        Saisissez le mot de passe du formulaire reçu par e-mail pour consulter les informations de votre département.
      </p>

      {!data ? (
        <form onSubmit={onUnlock} className="mt-8 space-y-4 rounded-2xl border border-surface-200 bg-white p-6 dark:border-surface-700 dark:bg-surface-900">
          {departmentId <= 0 ? (
            <p className="text-sm text-amber-700 dark:text-amber-300">
              Lien incomplet : le paramètre département est manquant. Utilisez le bouton de l’e-mail reçu.
            </p>
          ) : null}
          <label className="block text-sm font-medium text-surface-800 dark:text-surface-200">
            Mot de passe
            <input
              type="password"
              className="mt-1.5 w-full rounded-lg border border-surface-200 bg-surface-50 px-3 py-2 text-sm dark:border-surface-700 dark:bg-surface-950"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
            />
          </label>
          {error ? <p className="text-sm text-red-600">{error}</p> : null}
          <button
            type="submit"
            disabled={!canUnlock || busy}
            className="inline-flex items-center gap-2 rounded-xl bg-burgundy-800 px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-50"
          >
            {busy ? <Loader2 className="h-4 w-4 animate-spin" /> : <Lock className="h-4 w-4" />}
            Afficher les réponses
          </button>
        </form>
      ) : (
        <div className="mt-8 space-y-4 rounded-2xl border border-surface-200 bg-white p-6 dark:border-surface-700 dark:bg-surface-900">
          <div>
            <div className="flex items-start gap-3">
              {data.pastor.photo_url ? (
                <img
                  src={data.pastor.photo_url}
                  alt={data.pastor.full_name ?? ''}
                  className="h-14 w-14 rounded-full object-cover ring-2 ring-orange-300"
                />
              ) : null}
              <div>
                <p className="text-xs uppercase tracking-wide text-surface-500">Pasteur</p>
                <p className="text-lg font-semibold text-surface-900 dark:text-white">{data.pastor.full_name}</p>
                {data.pastor.church_name ? (
                  <p className="text-sm text-surface-600">{data.pastor.church_name}</p>
                ) : null}
              </div>
            </div>
            <p className="mt-2 text-xs text-surface-500">
              Projet : {data.project_title ?? '—'}
              {data.submitted_at ? ` · Reçu le ${new Date(data.submitted_at).toLocaleString('fr-FR')}` : ''}
            </p>
          </div>
          <ul className="divide-y divide-surface-100 dark:divide-surface-800">
            {data.answers.length === 0 ? (
              <li className="py-4 text-sm text-surface-500">Aucune réponse pour votre département.</li>
            ) : (
              data.answers.map((answer) => (
                <li key={answer.key} className="py-3">
                  <p className="text-xs font-semibold uppercase tracking-wide text-surface-500">{answer.label}</p>
                  <p className="mt-1 text-sm text-surface-900 dark:text-surface-100">{formatValue(answer.value)}</p>
                </li>
              ))
            )}
          </ul>

          <div className="rounded-xl border border-surface-200 bg-surface-50 p-4 dark:border-surface-700 dark:bg-surface-950">
            {isAcknowledged ? (
              <p className="flex items-start gap-2 text-sm text-emerald-700 dark:text-emerald-300">
                <CheckCircle2 className="mt-0.5 h-4 w-4 shrink-0" />
                <span>
                  Réception accusée
                  {data.acknowledgment?.acknowledged_at
                    ? ` le ${new Date(data.acknowledgment.acknowledged_at).toLocaleString('fr-FR')}`
                    : ''}
                  {data.acknowledgment?.acknowledged_by_name
                    ? ` par ${data.acknowledgment.acknowledged_by_name}`
                    : ''}
                  .
                </span>
              </p>
            ) : (
              <div className="space-y-3">
                <p className="text-sm font-medium text-surface-800 dark:text-surface-200">
                  Confirmez que votre département a bien pris connaissance de ces informations.
                </p>
                <label className="block text-sm text-surface-700 dark:text-surface-300">
                  Votre nom (optionnel)
                  <input
                    type="text"
                    className="mt-1.5 w-full rounded-lg border border-surface-200 bg-white px-3 py-2 text-sm dark:border-surface-700 dark:bg-surface-900"
                    value={acknowledgerName}
                    onChange={(e) => setAcknowledgerName(e.target.value)}
                    placeholder="Ex. Responsable CREA"
                  />
                </label>
                <button
                  type="button"
                  disabled={ackBusy}
                  onClick={onAcknowledge}
                  className="inline-flex items-center gap-2 rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-50"
                >
                  {ackBusy ? <Loader2 className="h-4 w-4 animate-spin" /> : <CheckCircle2 className="h-4 w-4" />}
                  Accuser réception
                </button>
              </div>
            )}
            {ackMessage ? <p className="mt-2 text-sm text-surface-600 dark:text-surface-400">{ackMessage}</p> : null}
          </div>

          <button
            type="button"
            className="text-sm text-burgundy-700 underline dark:text-burgundy-300"
            onClick={() => {
              setData(null);
              setPassword('');
              setAckMessage(null);
            }}
          >
            Verrouiller à nouveau
          </button>
        </div>
      )}
    </div>
  );
}
