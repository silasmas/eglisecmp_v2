import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { Loader2, CreditCard, Heart, Smartphone, ShieldCheck } from 'lucide-react';
import PageHero from '../components/ui/PageHero';
import { cn } from '../lib/utils';
import {
  fetchOffrandesList,
  fetchOffrandePaymentStatus,
  initOffrandeTransaction,
  processOffrandePayment,
  type SiteOffrandeRow,
} from '../lib/siteApi';

const PHONE_HELPER = 'Utilisez le format international RD Congo : commence par 243, puis le numéro sans le 0 initial (ex. : 2438XXXXXXXX ou 2439XXXXXXXX selon votre opérateur).';

/** Intervalle entre deux vérifications FlexPay (ms). */
const PAYMENT_POLL_INTERVAL_MS = 2000;

/** Durée d’une phase de vérification du statut (ms). */
const PAYMENT_VERIFICATION_PHASE_MS = 20000;

/** Nombre de phases de vérification avant message final. */
const PAYMENT_VERIFICATION_MAX_PHASES = 3;

type MobileTreatmentStep =
  | 'idle'
  | 'sending'
  | 'await_device'
  | 'checking'
  | 'done'
  | 'error'
  | 'verification_exhausted';

/** Forme attendue : 243 suivi de 9 chiffres (format international RD Congo). */
function normalizePhoneRuanda(input: string): string | null {
  const digitsOnly = input.replace(/\D/g, '');

  if (digitsOnly.startsWith('243')) {
    return /^243\d{9}$/.test(digitsOnly) ? digitsOnly : null;
  }

  if (digitsOnly.startsWith('0')) {
    const rest = digitsOnly.slice(1);
    return /^\d{9}$/.test(rest) ? `243${rest}` : null;
  }

  if (/^\d{9}$/.test(digitsOnly)) {
    return `243${digitsOnly}`;
  }

  return null;
}

/**
 * Reformule le message opérateur pour le visiteur (solde, refus, etc.).
 *
 * @param raw Message brut FlexPay ou API.
 * @returns Texte clair en français.
 */
function formatPaymentErrorMessage(raw: string | undefined | null): string {
  const trimmed = (raw ?? '').trim();
  if (trimmed === '') {
    return 'Le paiement n’a pas pu être effectué. Vérifiez votre solde Mobile money ou réessayez avec un autre moyen de paiement.';
  }

  const lower = trimmed.toLowerCase();
  if (
    lower.includes('solde') ||
    lower.includes('insuffis') ||
    lower.includes('insufficient') ||
    lower.includes('balance')
  ) {
    return 'Solde insuffisant sur votre compte Mobile money. Rechargez votre compte ou réduisez le montant, puis réessayez.';
  }

  if (lower.includes('annul') || lower.includes('cancel')) {
    return 'Paiement annulé. Vous pouvez relancer une nouvelle tentative depuis l’étape 2.';
  }

  if (lower.includes('expir') || lower.includes('timeout')) {
    return 'La demande de paiement a expiré. Relancez le paiement et validez rapidement sur votre téléphone.';
  }

  if (lower.includes('refus') || lower.includes('declin') || lower.includes('reject')) {
    return 'Paiement refusé par l’opérateur. Vérifiez votre numéro et votre solde, puis réessayez.';
  }

  return trimmed;
}

/**
 * Page offrandes : parcours en 3 colonnes (détails → mode → suivi),
 * téléphone après choix Mobile money, polling avec bouton en chargement.
 */
export default function OffrandesPage() {
  const [searchParams, setSearchParams] = useSearchParams();
  const [offrandes, setOffrandes] = useState<SiteOffrandeRow[]>([]);
  const [loadingList, setLoadingList] = useState(true);

  const [offrandeId, setOffrandeId] = useState<number | ''>('');
  const [montant, setMontant] = useState('');
  const [currency, setCurrency] = useState<'CDF' | 'USD'>('CDF');
  const [fullname, setFullname] = useState('');
  const [message, setMessage] = useState('');

  const [paymentPhone, setPaymentPhone] = useState('');
  const [reference, setReference] = useState<string | null>(null);
  const [channel, setChannel] = useState<'mobile_money' | 'card' | ''>('');
  const [busy, setBusy] = useState(false);
  const [errorBanner, setErrorBanner] = useState<string | null>(null);
  const [successBanner, setSuccessBanner] = useState<string | null>(null);
  const [mobileTreatment, setMobileTreatment] = useState<MobileTreatmentStep>('idle');
  const [verificationPhase, setVerificationPhase] = useState(0);
  const [step3Notice, setStep3Notice] = useState<string | null>(null);

  const pollRef = useRef<number | null>(null);
  const verificationPhaseRef = useRef(0);
  const phaseStartedAtRef = useRef(0);
  const errorBannerRef = useRef<HTMLParagraphElement | null>(null);

  /** Affiche un message d’échec visible (bandeau + étape 3 si Mobile money). */
  const reportPaymentError = useCallback((rawMessage: string | undefined | null, step3Fallback?: string) => {
    const label = formatPaymentErrorMessage(rawMessage);
    setErrorBanner(label);
    setSuccessBanner(null);
    setStep3Notice(step3Fallback !== undefined ? formatPaymentErrorMessage(step3Fallback) : label);
    setMobileTreatment('error');
    setBusy(false);
    window.requestAnimationFrame(() => {
      errorBannerRef.current?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    });
  }, []);

  const stopPolling = useCallback(() => {
    if (pollRef.current !== null) {
      window.clearTimeout(pollRef.current);
      pollRef.current = null;
    }
  }, []);

  /** Remet le parcours à zéro après un paiement confirmé (nouvelle offrande possible). */
  const resetAfterSuccessfulPayment = useCallback(() => {
    stopPolling();
    setMontant('');
    setFullname('');
    setMessage('');
    setPaymentPhone('');
    setReference(null);
    setChannel('');
    setBusy(false);
    setErrorBanner(null);
    setMobileTreatment('idle');
    setVerificationPhase(0);
    setStep3Notice(null);
    verificationPhaseRef.current = 0;
    phaseStartedAtRef.current = 0;
    setOffrandeId((current) => {
      if (offrandes.length === 0) {
        return current;
      }
      const firstId = offrandes[0]?.id;
      return firstId !== undefined ? firstId : '';
    });
  }, [offrandes, stopPolling]);

  const startMobilePaymentPolling = useCallback(
    (paymentReference: string) => {
      verificationPhaseRef.current = 0;
      phaseStartedAtRef.current = Date.now();
      setVerificationPhase(0);
      setStep3Notice(null);

      const pollOnce = async (): Promise<void> => {
        if (Date.now() - phaseStartedAtRef.current >= PAYMENT_VERIFICATION_PHASE_MS) {
          if (verificationPhaseRef.current < PAYMENT_VERIFICATION_MAX_PHASES - 1) {
            verificationPhaseRef.current += 1;
            phaseStartedAtRef.current = Date.now();
            setVerificationPhase(verificationPhaseRef.current);
            setStep3Notice('Nous poursuivons la vérification auprès de l’opérateur de paiement…');
          } else {
            stopPolling();
            setBusy(false);
            setMobileTreatment('verification_exhausted');
            setStep3Notice(
              'La confirmation prend plus de temps que prévu. Si le montant a bien été débité, conservez votre référence : notre équipe finalisera votre offrande sous peu.',
            );
            return;
          }
        }

        try {
          setMobileTreatment((previous) =>
            previous === 'await_device' || previous === 'sending' ? 'checking' : previous,
          );
          const stat = await fetchOffrandePaymentStatus(paymentReference);
          if (stat.paid) {
            setSuccessBanner('Merci — votre paiement Mobile money est confirmé !');
            resetAfterSuccessfulPayment();
            return;
          }
          if (stat.cancelled || stat.flexpay_status === 1) {
            stopPolling();
            const remoteMessage = stat.failure_message ?? stat.message;
            reportPaymentError(
              remoteMessage,
              'Le paiement n’a pas abouti. Consultez le message ci-dessus, puis réessayez depuis l’étape 2.',
            );
            return;
          }
        } catch {
          stopPolling();
          reportPaymentError(
            null,
            'Une erreur est survenue lors de la vérification. Réessayez ou contactez-nous avec votre référence.',
          );
          return;
        }

        pollRef.current = window.setTimeout(() => {
          void pollOnce();
        }, PAYMENT_POLL_INTERVAL_MS);
      };

      void pollOnce();
    },
    [reportPaymentError, resetAfterSuccessfulPayment, stopPolling],
  );

  const clearCarteQuery = useCallback(() => {
    const next = new URLSearchParams(searchParams);
    let changed = false;
    ['carte', 'ref', 'erreur'].forEach((key) => {
      if (next.has(key)) {
        next.delete(key);
        changed = true;
      }
    });
    if (changed) {
      setSearchParams(next, { replace: true });
    }
  }, [searchParams, setSearchParams]);

  useEffect(() => {
    let cancelled = false;
    async function load() {
      try {
        setLoadingList(true);
        const rows = await fetchOffrandesList();
        if (!cancelled) {
          setOffrandes(rows);
          setOffrandeId(rows[0]?.id ?? '');
        }
      } catch {
        if (!cancelled) {
          setOffrandes([]);
          setOffrandeId('');
        }
      } finally {
        if (!cancelled) {
          setLoadingList(false);
        }
      }
    }
    void load();
    return () => {
      cancelled = true;
    };
  }, []);

  useEffect(() => {
    const carte = searchParams.get('carte');
    const ref = searchParams.get('ref');

    if (carte === 'success') {
      setSuccessBanner(`Merci ! Votre paiement carte a bien été confirmé (réf. ${ref ?? '—'}).`);
      resetAfterSuccessfulPayment();
      clearCarteQuery();
    } else if (carte === 'cancel') {
      setErrorBanner('Paiement carte annulé par l\'opérateur.');
      clearCarteQuery();
    } else if (carte === 'decline') {
      setErrorBanner('Paiement carte refusé par l\'opérateur.');
      clearCarteQuery();
    } else if (searchParams.has('erreur')) {
      setErrorBanner('Impossible de finaliser cette offrande. Contactez-nous avec votre reçu si besoin.');
      clearCarteQuery();
    }
  }, [clearCarteQuery, resetAfterSuccessfulPayment, searchParams]);

  const selectedOffrande = useMemo(
    () => offrandes.find((row) => row.id === Number(offrandeId)),
    [offrandeId, offrandes],
  );

  const step1Done = Boolean(reference);

  const focusStep = useMemo(() => {
    if (!step1Done) {
      return 1;
    }
    if (channel === 'mobile_money') {
      if (
        mobileTreatment === 'done' ||
        mobileTreatment === 'verification_exhausted' ||
        mobileTreatment === 'error' ||
        (busy && mobileTreatment !== 'idle')
      ) {
        return 3;
      }
    }
    if (mobileTreatment === 'done') {
      return 3;
    }
    return 2;
  }, [busy, channel, mobileTreatment, step1Done]);

  const handleRetryVerification = useCallback(() => {
    if (reference === null || reference === '') {
      return;
    }
    setErrorBanner(null);
    setStep3Notice(null);
    setBusy(true);
    setMobileTreatment('await_device');
    startMobilePaymentPolling(reference);
  }, [reference, startMobilePaymentPolling]);

  const step1InputsLocked = step1Done;

  const handlePrepare = useCallback(async () => {
    setErrorBanner(null);
    setSuccessBanner(null);
    const value = Number(montant);
    if (offrandeId === '') {
      setErrorBanner('Choisissez un type d\'offrande.');
      return;
    }
    if (!Number.isFinite(value) || value < 1) {
      setErrorBanner('Montant invalide.');
      return;
    }

    setBusy(true);
    try {
      const data = await initOffrandeTransaction({
        offrande_id: Number(offrandeId),
        montant: value,
        currency,
        fullname: fullname.trim() !== '' ? fullname.trim() : undefined,
        message: message.trim() !== '' ? message.trim() : undefined,
      });
      setReference(data.reference);
      setChannel('');
      setPaymentPhone('');
      setMobileTreatment('idle');
    } catch (err) {
      setReference(null);
      setErrorBanner(formatPaymentErrorMessage(err instanceof Error ? err.message : null));
    } finally {
      setBusy(false);
    }
  }, [currency, fullname, message, montant, offrandeId]);

  /** Paiement : mobile (polling avec bouton chargé jusqu’à fin) ou carte (redirection). */
  const handlePay = useCallback(async () => {
    setErrorBanner(null);
    setSuccessBanner(null);
    if (reference === null || reference === '') {
      setErrorBanner('Référence introuvable : validez d\'abord l\'étape 1.');
      return;
    }
    if (channel === '') {
      setErrorBanner('Choisissez Mobile money ou Carte bancaire.');
      return;
    }

    if (channel === 'mobile_money') {
      const normalized = normalizePhoneRuanda(paymentPhone);
      if (normalized === null) {
        setErrorBanner(`Numéro invalide : ${PHONE_HELPER}`);
        return;
      }
    }

    setBusy(true);
    stopPolling();
    setMobileTreatment(channel === 'mobile_money' ? 'sending' : 'idle');

    try {
      const result = await processOffrandePayment({
        reference,
        channel,
        phone: channel === 'mobile_money' ? normalizePhoneRuanda(paymentPhone) ?? undefined : undefined,
      });

      if (channel === 'card') {
        setBusy(true);
        if (result.redirect_url !== undefined && result.redirect_url !== '') {
          window.location.assign(result.redirect_url);
          return;
        }
        setErrorBanner('Redirection carte indisponible. Réessayez plus tard.');
        setBusy(false);
        return;
      }

      if (channel === 'mobile_money') {
        if (!result.success) {
          reportPaymentError(result.message, 'Impossible d’envoyer la demande Mobile money. Corrigez le problème indiqué puis réessayez.');
          return;
        }

        setErrorBanner(null);
        setStep3Notice(null);
        setMobileTreatment('await_device');
        startMobilePaymentPolling(reference);
      }
    } catch (err) {
      if (channel === 'mobile_money') {
        reportPaymentError(err instanceof Error ? err.message : null, 'Erreur lors du paiement Mobile money.');
      } else {
        setMobileTreatment('idle');
        setErrorBanner(formatPaymentErrorMessage(err instanceof Error ? err.message : null));
        setBusy(false);
      }
    }
  }, [channel, paymentPhone, reference, reportPaymentError, startMobilePaymentPolling, stopPolling]);

  useEffect(() => {
    return () => stopPolling();
  }, [stopPolling]);

  const processingSubsteps = [
    { key: 'sending', label: 'Envoi sécurisé', reached: ['sending', 'await_device', 'checking', 'done'].includes(mobileTreatment) },
    {
      key: 'await_device',
      label: 'À valider sur le téléphone',
      reached:
        mobileTreatment === 'await_device' ||
        mobileTreatment === 'checking' ||
        mobileTreatment === 'done',
    },
    {
      key: 'checking',
      label: 'Vérification du statut',
      reached: mobileTreatment === 'checking' || mobileTreatment === 'done',
    },
    {
      key: 'done',
      label: 'Terminé',
      reached: mobileTreatment === 'done',
    },
  ] as const;

  return (
    <>
      <PageHero
        badge="Générosité"
        title="Offrandes"
        description="Donnez avec Mobile money ou carte bancaire via l’opérateur de paiement configuré pour le site."
        compact
      />

      <section className="mx-auto max-w-[1400px] px-4 pb-24 pt-10 sm:px-6 lg:px-8">
        <div className="mb-6 rounded-2xl border border-surface-200 bg-white p-5 shadow-inner dark:border-surface-700 dark:bg-surface-900">
          <div className="flex items-start gap-3">
            <ShieldCheck className="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" aria-hidden />
            <p className="text-xs text-surface-600 dark:text-surface-400">
              Les montants sont traités par l&apos;opérateur défini pour l&apos;église. Conservez la référence affichée à l&apos;étape suivante après validation de l&apos;étape&nbsp;1.
            </p>
          </div>
        </div>

        {loadingList ? <p className="mb-6 text-sm text-surface-500 dark:text-surface-400">Chargement...</p> : null}

        {errorBanner !== null ? (
          <p
            ref={errorBannerRef}
            role="alert"
            aria-live="assertive"
            className="mb-6 rounded-xl border border-burgundy-300 bg-burgundy-50 px-4 py-3 text-sm font-medium text-burgundy-900 dark:bg-burgundy-950/30 dark:text-burgundy-100"
          >
            {errorBanner}
          </p>
        ) : null}

        {successBanner !== null ? (
          <p className="mb-6 rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:bg-emerald-950/35 dark:text-emerald-100">
            {successBanner}
          </p>
        ) : null}

        {!loadingList && offrandes.length === 0 ? (
          <p className="text-center text-sm text-surface-500 dark:text-surface-400">
            Aucune offrande active pour le moment. Ajoutez un type d&apos;offrande depuis l&apos;administration.
          </p>
        ) : null}

        {offrandes.length > 0 ? (
          <div className="-mx-4 flex flex-row gap-4 overflow-x-auto scroll-smooth px-4 pb-2 lg:mx-0 lg:gap-4 lg:overflow-visible lg:pb-0">
            {/* Étape 1 — horizontalement à gauche */}
            <article
              className={cn(
                'flex min-h-[320px] w-[min(100%,340px)] shrink-0 flex-col rounded-3xl border p-6 transition-[box-shadow,border-color,opacity] duration-300 dark:bg-surface-950 lg:min-h-[340px] lg:w-0 lg:min-w-0 lg:flex-1',
                focusStep === 1
                  ? 'border-burgundy-400/60 shadow-lg shadow-burgundy-900/10 ring-2 ring-burgundy-500/25 dark:border-burgundy-600/50'
                  : 'border-surface-200 opacity-80 dark:border-surface-700',
                step1Done && 'opacity-100',
              )}
            >
              <div className="mb-4 flex items-center justify-between gap-2">
                <span className="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-burgundy-900 text-sm font-bold text-white">
                  1
                </span>
                <h2 className="font-heading text-base font-bold text-surface-950 dark:text-white">Votre don</h2>
              </div>
              <p className="mb-4 text-xs text-surface-500 dark:text-surface-400">Type, montant, devise, nom et un mot (facultatifs).</p>

              <div className="flex flex-1 flex-col gap-3">
                <div>
                  <label className="text-[10px] font-semibold uppercase tracking-wide text-surface-500 dark:text-surface-400" htmlFor="offrande">
                    Type d&apos;offrande *
                  </label>
                  <select
                    id="offrande"
                    value={offrandeId === '' ? '' : String(offrandeId)}
                    onChange={(event) => setOffrandeId(event.target.value === '' ? '' : Number(event.target.value))}
                    disabled={busy || step1InputsLocked}
                    className="mt-1 w-full rounded-xl border border-surface-200 px-3 py-2.5 text-sm dark:border-surface-600 dark:bg-surface-900 dark:text-white"
                  >
                    <option value="" disabled>
                      Choisir…
                    </option>
                    {offrandes.map((row) => (
                      <option key={row.id} value={row.id}>
                        {row.nom}
                      </option>
                    ))}
                  </select>
                  {selectedOffrande?.description ? (
                    <p className="mt-1 text-[11px] text-surface-500">{selectedOffrande.description}</p>
                  ) : null}
                </div>

                <div className="grid grid-cols-2 gap-3">
                  <div>
                    <label className="text-[10px] font-semibold uppercase tracking-wide text-surface-500" htmlFor="montant">
                      Montant *
                    </label>
                    <input
                      id="montant"
                      type="number"
                      inputMode="decimal"
                      min="1"
                      step="any"
                      value={montant}
                      onChange={(event) => setMontant(event.target.value)}
                      disabled={busy || step1InputsLocked}
                      className="mt-1 w-full rounded-xl border border-surface-200 px-3 py-2.5 text-sm dark:border-surface-600 dark:bg-surface-900 dark:text-white"
                    />
                  </div>
                  <div>
                    <label className="text-[10px] font-semibold uppercase tracking-wide text-surface-500" htmlFor="currency">
                      Devise *
                    </label>
                    <select
                      id="currency"
                      value={currency}
                      onChange={(event) => setCurrency(event.target.value as 'CDF' | 'USD')}
                      disabled={busy || step1InputsLocked}
                      className="mt-1 w-full rounded-xl border border-surface-200 px-3 py-2.5 text-sm dark:border-surface-600 dark:bg-surface-900 dark:text-white"
                    >
                      <option value="CDF">CDF</option>
                      <option value="USD">USD</option>
                    </select>
                  </div>
                </div>

                <div>
                  <label className="text-[10px] font-semibold uppercase tracking-wide text-surface-500" htmlFor="fullname">
                    Nom complet (facultatif)
                  </label>
                  <input
                    id="fullname"
                    type="text"
                    value={fullname}
                    onChange={(event) => setFullname(event.target.value)}
                    disabled={busy || step1InputsLocked}
                    className="mt-1 w-full rounded-xl border border-surface-200 px-3 py-2.5 text-sm dark:border-surface-600 dark:bg-surface-900 dark:text-white"
                  />
                </div>

                <div>
                  <label className="text-[10px] font-semibold uppercase tracking-wide text-surface-500" htmlFor="message">
                    Un mot (facultatif)
                  </label>
                  <textarea
                    id="message"
                    rows={2}
                    value={message}
                    onChange={(event) => setMessage(event.target.value)}
                    disabled={busy || step1InputsLocked}
                    placeholder="Remerciement, intention…"
                    className="mt-1 w-full rounded-xl border border-surface-200 px-3 py-2.5 text-sm dark:border-surface-600 dark:bg-surface-900 dark:text-white"
                  />
                </div>

                <button
                  type="button"
                  onClick={() => void handlePrepare()}
                  disabled={busy || step1Done}
                  className="mt-auto inline-flex w-full items-center justify-center gap-2 rounded-xl bg-burgundy-900 py-3 text-sm font-semibold text-white transition hover:bg-burgundy-800 disabled:opacity-40"
                >
                  {busy && !step1Done ? <Loader2 className="h-4 w-4 animate-spin shrink-0" aria-hidden /> : <Heart className="h-4 w-4 shrink-0" aria-hidden />}
                  {step1Done ? 'Étape enregistrée' : busy ? 'Enregistrement…' : 'Valider → étape paiement'}
                </button>

                {reference !== null ? (
                  <p className="rounded-xl bg-surface-100 px-3 py-2 text-[11px] text-surface-600 dark:bg-surface-800 dark:text-surface-300">
                    Réf.&nbsp;: <span className="font-mono font-semibold">{reference}</span>
                  </p>
                ) : null}
              </div>
            </article>

            {/* Étape 2 — centre */}
            <article
              className={cn(
                'flex min-h-[320px] w-[min(100%,340px)] shrink-0 flex-col rounded-3xl border p-6 transition-[box-shadow,border-color,opacity] duration-300 dark:bg-surface-950 lg:min-h-[340px] lg:w-0 lg:min-w-0 lg:flex-1',
                !step1Done && 'pointer-events-none opacity-55',
                focusStep === 2 && step1Done
                  ? 'border-emerald-500/55 shadow-lg shadow-emerald-900/10 ring-2 ring-emerald-400/25 dark:border-emerald-500/40'
                  : 'border-surface-200 dark:border-surface-700',
              )}
            >
              <div className="mb-4 flex items-center justify-between gap-2">
                <span className="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-surface-800 text-sm font-bold text-white dark:bg-surface-200 dark:text-surface-900">
                  2
                </span>
                <h2 className="font-heading text-base font-bold text-surface-950 dark:text-white">Mode de paiement</h2>
              </div>
              <p className="mb-4 text-xs text-surface-500 dark:text-surface-400">
                À activer après l&apos;étape&nbsp;1.
              </p>

              {!step1Done ? (
                <div className="flex flex-1 items-center justify-center rounded-2xl border border-dashed border-surface-200 p-8 text-center text-sm text-surface-400 dark:border-surface-600">
                  Validez d&apos;abord les informations du don pour continuer ici.
                </div>
              ) : (
                <>
                  <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <button
                      type="button"
                      aria-pressed={channel === 'mobile_money'}
                      onClick={() => {
                        setChannel('mobile_money');
                        setSuccessBanner(null);
                      }}
                      disabled={busy && mobileTreatment !== 'idle'}
                      className={cn(
                        'flex items-center gap-3 rounded-xl border px-4 py-3 text-left transition',
                        channel === 'mobile_money'
                          ? 'border-emerald-500 bg-emerald-50 dark:border-emerald-400 dark:bg-emerald-950/40'
                          : 'border-surface-200 hover:border-surface-300 dark:border-surface-600 dark:hover:border-surface-500',
                      )}
                    >
                      <Smartphone className="h-5 w-5 shrink-0 text-emerald-700 dark:text-emerald-400" aria-hidden />
                      <div>
                        <div className="text-sm font-semibold text-surface-900 dark:text-white">Mobile money</div>
                      </div>
                    </button>
                    <button
                      type="button"
                      aria-pressed={channel === 'card'}
                      onClick={() => {
                        setChannel('card');
                        setSuccessBanner(null);
                      }}
                      disabled={busy && mobileTreatment !== 'idle'}
                      className={cn(
                        'flex items-center gap-3 rounded-xl border px-4 py-3 text-left transition',
                        channel === 'card'
                          ? 'border-blue-600 bg-blue-50 dark:border-blue-400 dark:bg-blue-950/35'
                          : 'border-surface-200 hover:border-surface-300 dark:border-surface-600 dark:hover:border-surface-500',
                      )}
                    >
                      <CreditCard className="h-5 w-5 shrink-0 text-blue-800 dark:text-blue-300" aria-hidden />
                      <div>
                        <div className="text-sm font-semibold text-surface-900 dark:text-white">Carte bancaire</div>
                      </div>
                    </button>
                  </div>

                  {channel === 'mobile_money' ? (
                    <div className="mt-5 overflow-hidden rounded-2xl border border-emerald-200/70 bg-emerald-50/50 p-4 dark:border-emerald-900/60 dark:bg-emerald-950/20">
                      <label className="text-[10px] font-semibold uppercase tracking-wide text-emerald-900 dark:text-emerald-200" htmlFor="pay-phone">
                        Numéro Mobile money *
                      </label>
                      <input
                        id="pay-phone"
                        type="tel"
                        inputMode="tel"
                        autoComplete="tel"
                        placeholder="243XXXXXXXXX"
                        value={paymentPhone}
                        onChange={(event) => setPaymentPhone(event.target.value)}
                        disabled={busy && mobileTreatment !== 'idle'}
                        className="mt-2 w-full rounded-xl border border-emerald-300/70 bg-white px-3 py-2.5 text-sm font-mono dark:border-emerald-800 dark:bg-surface-900 dark:text-white"
                      />
                      <p className="mt-2 text-[11px] leading-relaxed text-emerald-900/85 dark:text-emerald-300/95">{PHONE_HELPER}</p>
                    </div>
                  ) : null}

                  {channel === 'card' ? (
                    <div className="mt-5 rounded-2xl border border-blue-200 bg-blue-50/80 p-4 text-sm text-blue-950 dark:border-blue-800 dark:bg-blue-950/35 dark:text-blue-100">
                      Vous allez être <strong>redirigé</strong> vers le <strong>formulaire de paiement par carte bancaire</strong> sécurisé de
                      l&apos;opérateur. Une fois votre saisie terminée sur leur page, vous reviendrez sur le site.
                    </div>
                  ) : null}

                  {channel === 'mobile_money' && mobileTreatment === 'error' && errorBanner !== null ? (
                    <p
                      role="alert"
                      className="mt-4 rounded-xl border border-burgundy-300 bg-burgundy-50 px-3 py-2.5 text-xs leading-relaxed text-burgundy-900 dark:border-burgundy-700 dark:bg-burgundy-950/40 dark:text-burgundy-100"
                    >
                      {errorBanner}
                    </p>
                  ) : null}

                  <button
                    type="button"
                    disabled={busy || channel === '' || mobileTreatment === 'done'}
                    onClick={() => void handlePay()}
                    className="mt-6 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-surface-900 py-3 text-sm font-semibold text-white transition hover:bg-surface-800 disabled:opacity-40 dark:bg-white dark:text-surface-900 dark:hover:bg-surface-200"
                  >
                    {busy ? <Loader2 className="h-4 w-4 animate-spin shrink-0" aria-hidden /> : null}
                    {channel === 'card' ? 'Ouvrir le paiement carte' : 'Lancer Mobile money'}
                  </button>
                </>
              )}
            </article>

            {/* Étape 3 — droite : suivi (toujours visible, s’anime selon progression) */}
            <article
              className={cn(
                'flex min-h-[320px] w-[min(100%,340px)] shrink-0 flex-col rounded-3xl border p-6 transition-[box-shadow,border-color] duration-300 dark:bg-surface-950 lg:min-h-[340px] lg:w-0 lg:min-w-0 lg:flex-1',
                focusStep === 3
                  ? 'border-gold-500/50 shadow-md ring-2 ring-gold-400/20 dark:border-gold-600/45'
                  : 'border-surface-200 opacity-95 dark:border-surface-700',
              )}
            >
              <div className="mb-4 flex items-center justify-between gap-2">
                <span className="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gold-500/85 text-sm font-bold text-surface-900">
                  3
                </span>
                <h2 className="font-heading text-base font-bold text-surface-950 dark:text-white">Traitement</h2>
              </div>
              {focusStep === 3 ? (
                <p className="mb-6 text-xs leading-relaxed text-surface-600 dark:text-surface-400">
                  {channel === 'card'
                    ? 'Après le paiement par carte, vous serez redirigé ici pour la confirmation.'
                    : 'Validez la demande sur votre téléphone lorsque votre opérateur vous l’indique. Le statut se met à jour automatiquement dès confirmation.'}
                </p>
              ) : null}

              <div className="flex flex-row flex-wrap items-stretch gap-2 sm:flex-nowrap sm:justify-between">
                {processingSubsteps.map((step) => (
                  <div
                    key={step.key}
                    className={cn(
                      'flex min-h-[76px] min-w-[calc(50%-6px)] flex-1 flex-col justify-center rounded-xl border px-2 py-3 text-center text-[11px] font-medium transition sm:min-w-0 sm:text-xs',
                      step.reached
                        ? 'border-gold-500/60 bg-gold-500/15 text-gold-950 dark:border-gold-500/35 dark:bg-gold-500/10 dark:text-gold-200'
                        : 'border-surface-100 bg-surface-50 text-surface-400 dark:border-surface-700 dark:bg-surface-900/70 dark:text-surface-500',
                    )}
                  >
                    <span>{step.label}</span>
                  </div>
                ))}
              </div>

              {(busy && mobileTreatment !== 'idle') || mobileTreatment === 'checking' ? (
                <div className="mt-8 flex justify-center gap-2 text-xs text-surface-500 dark:text-surface-400">
                  <Loader2 className="h-5 w-5 animate-spin text-burgundy-600 shrink-0" aria-hidden />
                  <span>Traitement en cours — le bouton de l&apos;étape&nbsp;2 reste indisponible jusqu&apos;à la fin.</span>
                </div>
              ) : null}

              {focusStep === 3 && mobileTreatment === 'idle' && !busy && step1Done ? (
                <p className="mt-8 rounded-xl bg-surface-100 px-3 py-2 text-[11px] text-surface-500 dark:bg-surface-800 dark:text-surface-400">
                  Les étapes ci-dessus s’activent lorsque vous lancez un paiement Mobile money.
                </p>
              ) : null}

              {focusStep === 3 && mobileTreatment === 'error' && errorBanner !== null ? (
                <p
                  role="alert"
                  className="mt-6 rounded-xl border border-burgundy-300 bg-burgundy-50 px-4 py-3 text-xs font-medium leading-relaxed text-burgundy-900 dark:border-burgundy-700 dark:bg-burgundy-950/40 dark:text-burgundy-100"
                >
                  {errorBanner}
                </p>
              ) : null}

              {focusStep === 3 && step3Notice !== null && mobileTreatment !== 'error' ? (
                <p
                  className={cn(
                    'mt-6 rounded-xl px-4 py-3 text-xs leading-relaxed',
                    mobileTreatment === 'verification_exhausted'
                      ? 'border border-amber-200/80 bg-amber-50 text-amber-950 dark:border-amber-800/50 dark:bg-amber-950/30 dark:text-amber-100'
                      : 'border border-surface-200 bg-surface-50 text-surface-600 dark:border-surface-600 dark:bg-surface-900/60 dark:text-surface-300',
                  )}
                  role="status"
                >
                  {step3Notice}
                </p>
              ) : null}

              {focusStep === 3 && step3Notice !== null && mobileTreatment === 'error' ? (
                <p className="mt-3 text-[11px] leading-relaxed text-surface-500 dark:text-surface-400" role="status">
                  {step3Notice}
                </p>
              ) : null}

              {focusStep === 3 && mobileTreatment === 'verification_exhausted' && reference !== null ? (
                <button
                  type="button"
                  onClick={handleRetryVerification}
                  className="mt-4 w-full rounded-xl bg-burgundy-900 py-3 text-sm font-semibold text-white transition hover:bg-burgundy-800"
                >
                  Relancer la vérification
                </button>
              ) : null}

              {focusStep === 3 && verificationPhase > 0 && mobileTreatment === 'checking' ? (
                <p className="mt-3 text-center text-[11px] text-surface-400 dark:text-surface-500">
                  Vérification en cours…
                </p>
              ) : null}
            </article>
          </div>
        ) : null}
      </section>
    </>
  );
}
