import { useCallback, useEffect, useMemo, useState } from 'react';
import { Calendar, ChevronLeft, ChevronRight, Loader2, RotateCcw, User } from 'lucide-react';
import PageHero from '../components/ui/PageHero';
import { Skeleton } from '../components/ui/Skeleton';
import { cn } from '../lib/utils';
import {
  fetchAppointmentDates,
  fetchAppointmentMinisters,
  fetchAppointmentSlots,
  submitSiteInquiry,
  type AppointmentMinisterRow,
  type AppointmentSlotRow,
} from '../lib/siteApi';

type WizardStep = 1 | 2 | 3 | 4 | 5;

const STEP_LABELS: Record<WizardStep, string> = {
  1: 'Pasteur',
  2: 'Date',
  3: 'Créneau',
  4: 'Motif',
  5: 'Coordonnées',
};

const WIZARD_STEPS: WizardStep[] = [1, 2, 3, 4, 5];

/**
 * Indicateur d’étapes relié par des tirets dont la couleur reflète la progression.
 */
function AppointmentStepper({ currentStep }: { currentStep: WizardStep }) {
  return (
    <div className="mb-10 w-full" aria-label="Progression du formulaire">
      <div className="flex items-start">
        {WIZARD_STEPS.map((stepKey, index) => {
          const isDone = currentStep > stepKey;
          const isCurrent = currentStep === stepKey;
          const connectorActive = currentStep > stepKey;

          return (
            <div key={stepKey} className={cn('flex items-center', index < WIZARD_STEPS.length - 1 ? 'flex-1' : '')}>
              <div className="flex min-w-[4.5rem] flex-col items-center gap-1.5 text-center sm:min-w-[5.5rem]">
                <span
                  className={cn(
                    'flex h-9 w-9 items-center justify-center rounded-full text-xs font-bold transition-colors',
                    isCurrent && 'bg-burgundy-900 text-white shadow-md ring-4 ring-burgundy-200/80 dark:ring-burgundy-900/50',
                    isDone && !isCurrent && 'bg-burgundy-700 text-white',
                    !isDone && !isCurrent && 'bg-surface-100 text-surface-400 dark:bg-surface-800 dark:text-surface-500',
                  )}
                >
                  {stepKey}
                </span>
                <span
                  className={cn(
                    'max-w-[5.5rem] text-[10px] font-semibold uppercase leading-tight tracking-wide',
                    isCurrent && 'text-burgundy-900 dark:text-burgundy-200',
                    isDone && !isCurrent && 'text-burgundy-700 dark:text-burgundy-300',
                    !isDone && !isCurrent && 'text-surface-400',
                  )}
                >
                  {STEP_LABELS[stepKey]}
                </span>
              </div>
              {index < WIZARD_STEPS.length - 1 ? (
                <div
                  className={cn(
                    'mx-1 mb-6 h-0 flex-1 border-t-2 border-dashed transition-colors sm:mx-2',
                    connectorActive
                      ? 'border-burgundy-600 dark:border-burgundy-500'
                      : isCurrent
                        ? 'border-burgundy-300 dark:border-burgundy-700'
                        : 'border-surface-200 dark:border-surface-700',
                  )}
                  aria-hidden
                />
              ) : null}
            </div>
          );
        })}
      </div>
    </div>
  );
}

/**
 * Formulaire public de rendez-vous pastoral en 5 étapes (pasteur → date → créneau → motif → contact).
 */
export default function AppointmentPage() {
  const [step, setStep] = useState<WizardStep>(1);
  const [ministers, setMinisters] = useState<AppointmentMinisterRow[]>([]);
  const [loadingMinisters, setLoadingMinisters] = useState(true);
  const [ministerId, setMinisterId] = useState<number | null>(null);
  const [dates, setDates] = useState<string[]>([]);
  const [loadingDates, setLoadingDates] = useState(false);
  const [selectedDate, setSelectedDate] = useState('');
  const [slots, setSlots] = useState<AppointmentSlotRow[]>([]);
  const [loadingSlots, setLoadingSlots] = useState(false);
  const [selectedSlot, setSelectedSlot] = useState<AppointmentSlotRow | null>(null);
  const [message, setMessage] = useState('');
  const [name, setName] = useState('');
  const [phone, setPhone] = useState('');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [done, setDone] = useState(false);

  const selectedMinister = useMemo(
    () => ministers.find((row) => row.id === ministerId) ?? null,
    [ministerId, ministers],
  );

  useEffect(() => {
    let cancelled = false;
    async function load() {
      try {
        setLoadingMinisters(true);
        const rows = await fetchAppointmentMinisters();
        if (!cancelled) {
          setMinisters(rows);
        }
      } catch {
        if (!cancelled) {
          setMinisters([]);
        }
      } finally {
        if (!cancelled) {
          setLoadingMinisters(false);
        }
      }
    }
    void load();
    return () => {
      cancelled = true;
    };
  }, []);

  useEffect(() => {
    if (ministerId === null) {
      setDates([]);
      setSelectedDate('');
      return;
    }

    let cancelled = false;
    async function loadDates() {
      try {
        setLoadingDates(true);
        const rows = await fetchAppointmentDates(ministerId);
        if (!cancelled) {
          setDates(rows);
          setSelectedDate(rows[0] ?? '');
        }
      } catch {
        if (!cancelled) {
          setDates([]);
          setSelectedDate('');
        }
      } finally {
        if (!cancelled) {
          setLoadingDates(false);
        }
      }
    }
    void loadDates();
    return () => {
      cancelled = true;
    };
  }, [ministerId]);

  useEffect(() => {
    if (ministerId === null || selectedDate === '') {
      setSlots([]);
      setSelectedSlot(null);
      return;
    }

    let cancelled = false;
    async function loadSlots() {
      try {
        setLoadingSlots(true);
        const rows = await fetchAppointmentSlots(ministerId, selectedDate);
        if (!cancelled) {
          setSlots(rows);
          setSelectedSlot(rows[0] ?? null);
        }
      } catch {
        if (!cancelled) {
          setSlots([]);
          setSelectedSlot(null);
        }
      } finally {
        if (!cancelled) {
          setLoadingSlots(false);
        }
      }
    }
    void loadSlots();
    return () => {
      cancelled = true;
    };
  }, [ministerId, selectedDate]);

  const canGoNext = useCallback((): boolean => {
    if (step === 1) {
      return ministerId !== null;
    }
    if (step === 2) {
      return selectedDate !== '';
    }
    if (step === 3) {
      return selectedSlot !== null;
    }
    if (step === 4) {
      return message.trim() !== '';
    }
    return name.trim() !== '' && phone.trim() !== '';
  }, [message, ministerId, name, phone, selectedDate, selectedSlot, step]);

  const goNext = () => {
    setError(null);
    if (!canGoNext()) {
      setError('Complétez cette étape avant de continuer.');
      return;
    }
    setStep((previous) => Math.min(5, previous + 1) as WizardStep);
  };

  const goBack = () => {
    setError(null);
    setStep((previous) => Math.max(1, previous - 1) as WizardStep);
  };

  const handleSubmit = async () => {
    setError(null);
    if (ministerId === null || selectedSlot === null || name.trim() === '' || phone.trim() === '' || message.trim() === '') {
      setError('Vérifiez toutes les étapes avant d’envoyer.');
      return;
    }

    setBusy(true);
    try {
      await submitSiteInquiry({
        kind: 'appointment',
        name: name.trim(),
        phone: phone.trim(),
        message: message.trim(),
        minister_id: ministerId,
        preferred_at: selectedSlot.starts_at,
      });
      setDone(true);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Impossible d’envoyer pour le moment.');
    } finally {
      setBusy(false);
    }
  };

  const formatDateLabel = (isoDate: string) =>
    new Date(`${isoDate}T12:00:00`).toLocaleDateString('fr-FR', {
      weekday: 'long',
      day: 'numeric',
      month: 'long',
    });

  const resetWizard = () => {
    setDone(false);
    setStep(1);
    setMinisterId(null);
    setSelectedDate('');
    setSelectedSlot(null);
    setMessage('');
    setName('');
    setPhone('');
    setError(null);
    setDates([]);
    setSlots([]);
  };

  if (done) {
    return (
      <>
        <PageHero
          badge="Visite"
          title="Prendre rendez-vous"
          description="Votre demande de rendez-vous a bien été enregistrée."
          compact
        />
        <section className="mx-auto max-w-2xl px-4 pb-24 pt-8 sm:px-6 lg:px-8">
          <div className="rounded-3xl border border-emerald-200 bg-emerald-50 px-6 py-8 text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-100">
            <p className="font-heading text-lg font-bold">Merci {name}.</p>
            <p className="mt-2 text-sm opacity-90">
              Votre demande de rendez-vous avec {selectedMinister?.fullname ?? 'le pasteur'} le{' '}
              {selectedSlot ? formatDateLabel(selectedSlot.starts_at.slice(0, 10)) : ''} à{' '}
              {selectedSlot?.label ?? ''} a été transmise. Vous serez contacté pour confirmer le créneau.
            </p>
            <button
              type="button"
              onClick={resetWizard}
              className="mt-6 inline-flex items-center gap-2 rounded-2xl bg-burgundy-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-burgundy-800"
            >
              <RotateCcw className="h-4 w-4" aria-hidden />
              Prendre un autre rendez-vous
            </button>
          </div>
        </section>
      </>
    );
  }

  return (
    <>
      <PageHero
        badge="Visite"
        title="Prendre rendez-vous"
        description="Choisissez un pasteur selon ses disponibilités, puis un créneau et laissez vos coordonnées."
        compact
      />

      <section className="mx-auto max-w-5xl px-4 pb-24 pt-8 sm:px-6 lg:px-8">
        <AppointmentStepper currentStep={step} />

        {error !== null ? (
          <p className="mb-4 rounded-2xl bg-burgundy-50 px-4 py-2 text-sm text-burgundy-900 dark:bg-burgundy-950/50 dark:text-burgundy-100">
            {error}
          </p>
        ) : null}

        <article className="rounded-3xl border border-surface-200 bg-white p-6 shadow-sm dark:border-surface-700 dark:bg-surface-950 sm:p-8">
          {step === 1 ? (
            <div>
              <h2 className="font-heading text-lg font-bold text-surface-950 dark:text-white">Choisissez un pasteur</h2>
              <p className="mt-1 text-sm text-surface-500">Seuls les pasteurs avec horaires de réception sont listés.</p>
              {loadingMinisters ? (
                <ul className="mt-6 grid gap-4 sm:grid-cols-2">
                  {Array.from({ length: 4 }).map((_, index) => (
                    <li key={index} className="flex gap-4 rounded-2xl border border-surface-200 p-4 dark:border-surface-700">
                      <Skeleton className="h-16 w-16 shrink-0 rounded-full" />
                      <div className="flex-1 space-y-2">
                        <Skeleton className="h-4 w-2/3" />
                        <Skeleton className="h-3 w-full" />
                      </div>
                    </li>
                  ))}
                </ul>
              ) : ministers.length === 0 ? (
                <p className="mt-6 text-sm text-surface-500">Aucun créneau n’est configuré pour le moment. Revenez plus tard.</p>
              ) : (
                <ul className="mt-6 grid gap-4 sm:grid-cols-2">
                  {ministers.map((minister) => {
                    const active = ministerId === minister.id;
                    return (
                      <li key={minister.id}>
                        <button
                          type="button"
                          onClick={() => setMinisterId(minister.id)}
                          className={cn(
                            'flex w-full items-center gap-4 rounded-2xl border p-4 text-left transition',
                            active
                              ? 'border-burgundy-600 bg-burgundy-50 ring-2 ring-burgundy-500/30 dark:bg-burgundy-950/40'
                              : 'border-surface-200 hover:border-burgundy-300 dark:border-surface-600',
                          )}
                        >
                          {minister.image_url !== '' ? (
                            <img
                              src={minister.image_url}
                              alt=""
                              className="h-16 w-16 rounded-full object-cover"
                            />
                          ) : (
                            <span className="flex h-16 w-16 items-center justify-center rounded-full bg-surface-100 dark:bg-surface-800">
                              <User className="h-8 w-8 text-surface-400" aria-hidden />
                            </span>
                          )}
                          <span>
                            <span className="block font-semibold text-surface-950 dark:text-white">{minister.fullname}</span>
                            {minister.bio !== '' ? (
                              <span className="mt-1 line-clamp-2 text-xs text-surface-500">{minister.bio}</span>
                            ) : null}
                          </span>
                        </button>
                      </li>
                    );
                  })}
                </ul>
              )}
            </div>
          ) : null}

          {step === 2 ? (
            <div>
              <h2 className="font-heading text-lg font-bold text-surface-950 dark:text-white">Choisissez une date</h2>
              <p className="mt-1 text-sm text-surface-500">
                Pasteur : <strong>{selectedMinister?.fullname}</strong>
              </p>
              {loadingDates ? (
                <div className="mt-6 flex flex-wrap gap-2">
                  {Array.from({ length: 6 }).map((_, index) => (
                    <Skeleton key={index} className="h-10 w-36 rounded-2xl" />
                  ))}
                </div>
              ) : dates.length === 0 ? (
                <p className="mt-6 text-sm text-surface-500">Aucune date disponible pour ce pasteur.</p>
              ) : (
                <ul className="mt-6 flex flex-wrap gap-2">
                  {dates.map((date) => (
                    <li key={date}>
                      <button
                        type="button"
                        onClick={() => setSelectedDate(date)}
                        className={cn(
                          'rounded-2xl border px-4 py-2 text-sm font-medium transition',
                          selectedDate === date
                            ? 'border-burgundy-600 bg-burgundy-900 text-white'
                            : 'border-surface-200 hover:border-burgundy-400 dark:border-surface-600',
                        )}
                      >
                        {formatDateLabel(date)}
                      </button>
                    </li>
                  ))}
                </ul>
              )}
            </div>
          ) : null}

          {step === 3 ? (
            <div>
              <h2 className="font-heading text-lg font-bold text-surface-950 dark:text-white">Choisissez un créneau</h2>
              <p className="mt-1 text-sm text-surface-500">
                {selectedMinister?.fullname} — {selectedDate !== '' ? formatDateLabel(selectedDate) : ''}
              </p>
              {loadingSlots ? (
                <div className="mt-6 flex flex-wrap gap-2">
                  {Array.from({ length: 8 }).map((_, index) => (
                    <Skeleton key={index} className="h-10 w-28 rounded-2xl" />
                  ))}
                </div>
              ) : slots.length === 0 ? (
                <p className="mt-6 text-sm text-surface-500">Aucun créneau libre ce jour-là.</p>
              ) : (
                <ul className="mt-6 flex flex-wrap gap-2">
                  {slots.map((slot) => (
                    <li key={slot.starts_at}>
                      <button
                        type="button"
                        onClick={() => setSelectedSlot(slot)}
                        className={cn(
                          'rounded-2xl border px-4 py-2 text-sm font-semibold transition',
                          selectedSlot?.starts_at === slot.starts_at
                            ? 'border-burgundy-600 bg-burgundy-900 text-white'
                            : 'border-surface-200 hover:border-burgundy-400 dark:border-surface-600',
                        )}
                      >
                        {slot.label}
                      </button>
                    </li>
                  ))}
                </ul>
              )}
            </div>
          ) : null}

          {step === 4 ? (
            <div>
              <h2 className="font-heading text-lg font-bold text-surface-950 dark:text-white">Motif du rendez-vous</h2>
              <textarea
                id="ap-msg"
                required
                rows={5}
                value={message}
                onChange={(event) => setMessage(event.target.value)}
                className="mt-4 w-full rounded-2xl border border-surface-200 px-4 py-3 text-sm dark:border-surface-600 dark:bg-surface-900 dark:text-white"
                placeholder="Ex. première visite, entretien pastoral, accompagnement…"
              />
            </div>
          ) : null}

          {step === 5 ? (
            <div>
              <h2 className="font-heading text-lg font-bold text-surface-950 dark:text-white">Vos coordonnées</h2>
              <p className="mt-1 text-sm text-surface-500">Nous vous contacterons pour confirmer le rendez-vous.</p>
              <div className="mt-4 space-y-4">
                <div>
                  <label className="text-xs font-semibold uppercase tracking-wide text-surface-500" htmlFor="ap-name">
                    Nom complet *
                  </label>
                  <input
                    id="ap-name"
                    value={name}
                    onChange={(event) => setName(event.target.value)}
                    required
                    className="mt-2 w-full rounded-2xl border border-surface-200 px-4 py-3 text-sm dark:border-surface-600 dark:bg-surface-900 dark:text-white"
                  />
                </div>
                <div>
                  <label className="text-xs font-semibold uppercase tracking-wide text-surface-500" htmlFor="ap-phone">
                    Téléphone *
                  </label>
                  <input
                    id="ap-phone"
                    type="tel"
                    value={phone}
                    onChange={(event) => setPhone(event.target.value)}
                    required
                    className="mt-2 w-full rounded-2xl border border-surface-200 px-4 py-3 text-sm dark:border-surface-600 dark:bg-surface-900 dark:text-white"
                  />
                </div>
              </div>
              <div className="mt-4 rounded-2xl border border-surface-200 bg-surface-50 p-4 text-sm dark:border-surface-700 dark:bg-surface-900/50">
                <p className="flex items-center gap-2 font-medium text-surface-800 dark:text-surface-200">
                  <Calendar className="h-4 w-4 text-burgundy-700" aria-hidden />
                  Récapitulatif
                </p>
                <ul className="mt-2 space-y-1 text-surface-600 dark:text-surface-400">
                  <li>{selectedMinister?.fullname}</li>
                  <li>{selectedDate !== '' ? formatDateLabel(selectedDate) : ''}</li>
                  <li>{selectedSlot?.label}</li>
                  <li className="line-clamp-2">{message}</li>
                </ul>
              </div>
            </div>
          ) : null}

          <div className="mt-8 flex flex-wrap justify-between gap-3">
            <button
              type="button"
              onClick={goBack}
              disabled={step === 1 || busy}
              className="inline-flex items-center gap-1 rounded-2xl border border-surface-300 px-4 py-2.5 text-sm font-semibold disabled:opacity-40 dark:border-surface-600"
            >
              <ChevronLeft className="h-4 w-4" aria-hidden />
              Retour
            </button>
            {step < 5 ? (
              <button
                type="button"
                onClick={goNext}
                disabled={!canGoNext()}
                className="inline-flex items-center gap-1 rounded-2xl bg-burgundy-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-burgundy-800 disabled:opacity-40"
              >
                Suivant
                <ChevronRight className="h-4 w-4" aria-hidden />
              </button>
            ) : (
              <button
                type="button"
                onClick={() => void handleSubmit()}
                disabled={busy || !canGoNext()}
                className="inline-flex items-center gap-2 rounded-2xl bg-burgundy-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-burgundy-800 disabled:opacity-40"
              >
                {busy ? <Loader2 className="h-4 w-4 animate-spin" aria-hidden /> : null}
                {busy ? 'Envoi…' : 'Envoyer la demande'}
              </button>
            )}
          </div>
        </article>
      </section>
    </>
  );
}
