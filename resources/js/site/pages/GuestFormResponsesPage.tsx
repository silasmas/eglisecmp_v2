import { useMemo, useRef, useState, type FormEvent } from 'react';
import { useParams, useSearchParams } from 'react-router-dom';
import html2canvas from 'html2canvas';
import { CheckCircle2, Download, FileImage, Loader2, Lock, Mail, Printer } from 'lucide-react';
import {
  acknowledgeGuestFormResponses,
  unlockGuestFormResponses,
  type GuestFormUnlockResponse,
} from '../lib/siteApi';

/**
 * Formate une valeur de réponse de façon lisible.
 *
 * @param value Valeur brute.
 */
function formatValue(value: unknown): string {
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
}

/**
 * Construit un texte plat des réponses pour e-mail / presse-papiers.
 *
 * @param data Réponses déverrouillées.
 */
function buildAnswersPlainText(data: GuestFormUnlockResponse): string {
  const lines = [
    `Pasteur : ${data.pastor.full_name ?? '—'}`,
    data.pastor.church_name ? `Église : ${data.pastor.church_name}` : null,
    `Projet : ${data.project_title ?? '—'}`,
    data.submitted_at
      ? `Reçu le : ${new Date(data.submitted_at).toLocaleString('fr-FR')}`
      : null,
    '',
    'Réponses :',
    ...data.answers.map((answer) => `- ${answer.label} : ${formatValue(answer.value)}`),
  ].filter((line): line is string => line !== null);

  return lines.join('\n');
}

/**
 * Portail département : consultation des réponses filtrées d’une fiche d’accueil.
 */
export default function GuestFormResponsesPage() {
  const { submissionToken = '' } = useParams();
  const [searchParams] = useSearchParams();
  const departmentId = Number(searchParams.get('dept') || '0');
  const exportRef = useRef<HTMLDivElement | null>(null);

  const [password, setPassword] = useState('');
  const [busy, setBusy] = useState(false);
  const [ackBusy, setAckBusy] = useState(false);
  const [exportBusy, setExportBusy] = useState(false);
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
   * Télécharge les réponses en image PNG.
   */
  const downloadImage = async () => {
    if (!exportRef.current || !data || exportBusy) {
      return;
    }
    setExportBusy(true);
    try {
      const canvas = await html2canvas(exportRef.current, {
        backgroundColor: '#ffffff',
        scale: 2,
        useCORS: true,
      });
      const link = document.createElement('a');
      const slug = (data.pastor.full_name ?? 'reponses').replace(/\s+/g, '_');
      link.download = `reponses_${slug}.png`;
      link.href = canvas.toDataURL('image/png');
      link.click();
    } catch {
      setAckMessage('Impossible de générer l’image.');
    } finally {
      setExportBusy(false);
    }
  };

  /**
   * Ouvre la boîte de dialogue d’impression / PDF du navigateur.
   */
  const downloadPdf = () => {
    window.print();
  };

  /**
   * Ouvre un brouillon e-mail avec les réponses en texte.
   */
  const shareByEmail = () => {
    if (!data) {
      return;
    }
    const subject = encodeURIComponent(
      `Réponses fiche — ${data.pastor.full_name ?? 'Pasteur invité'} (CMP)`,
    );
    const body = encodeURIComponent(buildAnswersPlainText(data));
    window.location.href = `mailto:?subject=${subject}&body=${body}`;
  };

  return (
    <div className="mx-auto max-w-2xl px-4 py-12">
      <h1 className="text-2xl font-bold text-surface-900 dark:text-white print:text-black">
        Réponses — accueil pasteur invité
      </h1>
      <p className="mt-2 text-sm text-surface-600 dark:text-surface-400 print:hidden">
        Saisissez le mot de passe du formulaire reçu par e-mail / SMS / WhatsApp pour consulter les informations de votre
        département.
      </p>

      {!data ? (
        <form
          onSubmit={onUnlock}
          className="mt-8 space-y-4 rounded-2xl border border-surface-200 bg-white p-6 dark:border-surface-700 dark:bg-surface-900 print:hidden"
        >
          {departmentId <= 0 ? (
            <p className="text-sm text-amber-700 dark:text-amber-300">
              Lien incomplet : le paramètre département est manquant. Utilisez le bouton du message reçu.
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
        <div className="mt-8 space-y-4">
          <div
            ref={exportRef}
            className="space-y-4 rounded-2xl border border-surface-200 bg-white p-6 dark:border-surface-700 dark:bg-surface-900 print:border-0 print:p-0 print:shadow-none"
          >
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
                  <p className="text-lg font-semibold text-surface-900 dark:text-white print:text-black">
                    {data.pastor.full_name}
                  </p>
                  {data.pastor.church_name ? (
                    <p className="text-sm text-surface-600 print:text-black">{data.pastor.church_name}</p>
                  ) : null}
                </div>
              </div>
              <p className="mt-2 text-xs text-surface-500 print:text-black">
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
                    <p className="text-xs font-semibold uppercase tracking-wide text-surface-500 print:text-black">
                      {answer.label}
                    </p>
                    <p className="mt-1 text-sm text-surface-900 dark:text-surface-100 print:text-black">
                      {formatValue(answer.value)}
                    </p>
                  </li>
                ))
              )}
            </ul>
          </div>

          <div className="flex flex-wrap gap-2 print:hidden">
            <button
              type="button"
              onClick={downloadPdf}
              className="inline-flex items-center gap-2 rounded-xl border border-surface-200 bg-white px-4 py-2 text-sm font-semibold text-surface-800 dark:border-surface-700 dark:bg-surface-900 dark:text-surface-100"
            >
              <Printer className="h-4 w-4" />
              PDF / Imprimer
            </button>
            <button
              type="button"
              disabled={exportBusy}
              onClick={() => void downloadImage()}
              className="inline-flex items-center gap-2 rounded-xl border border-surface-200 bg-white px-4 py-2 text-sm font-semibold text-surface-800 disabled:opacity-50 dark:border-surface-700 dark:bg-surface-900 dark:text-surface-100"
            >
              {exportBusy ? <Loader2 className="h-4 w-4 animate-spin" /> : <FileImage className="h-4 w-4" />}
              Image PNG
            </button>
            <button
              type="button"
              onClick={shareByEmail}
              className="inline-flex items-center gap-2 rounded-xl border border-surface-200 bg-white px-4 py-2 text-sm font-semibold text-surface-800 dark:border-surface-700 dark:bg-surface-900 dark:text-surface-100"
            >
              <Mail className="h-4 w-4" />
              Envoyer par e-mail
            </button>
            <button
              type="button"
              onClick={async () => {
                try {
                  await navigator.clipboard.writeText(buildAnswersPlainText(data));
                  setAckMessage('Réponses copiées dans le presse-papiers.');
                } catch {
                  setAckMessage('Copie impossible sur cet appareil.');
                }
              }}
              className="inline-flex items-center gap-2 rounded-xl border border-surface-200 bg-white px-4 py-2 text-sm font-semibold text-surface-800 dark:border-surface-700 dark:bg-surface-900 dark:text-surface-100"
            >
              <Download className="h-4 w-4" />
              Copier le texte
            </button>
          </div>

          <div className="rounded-xl border border-surface-200 bg-surface-50 p-4 dark:border-surface-700 dark:bg-surface-950 print:hidden">
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
            className="text-sm text-burgundy-700 underline dark:text-burgundy-300 print:hidden"
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
