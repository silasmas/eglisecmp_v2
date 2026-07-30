import { useEffect, useState, type FormEvent } from 'react';
import { CheckCircle2, ClipboardList, Send, ShieldCheck } from 'lucide-react';
import PageHero from '../components/ui/PageHero';
import {
  fetchWorshipReportMeta,
  lookupWorshipReporterPhone,
  sendWorshipReportOtp,
  submitWorshipReport,
  verifyWorshipReportOtp,
  type WorshipServiceTypeOption,
} from '../lib/siteApi';

const INPUT_CLASS =
  'w-full rounded-lg border border-transparent bg-surface-100 px-4 py-3 text-sm text-surface-900 placeholder:text-surface-400 focus:border-burgundy-600 focus:bg-white focus:outline-none focus:ring-1 focus:ring-burgundy-600';

/**
 * Page protocole : stats de culte avec contrôle téléphone enregistré + OTP.
 */
export default function WorshipStatsReportPage() {
  const [types, setTypes] = useState<WorshipServiceTypeOption[]>([]);
  const [serviceDate, setServiceDate] = useState(() => new Date().toISOString().slice(0, 10));
  const [serviceType, setServiceType] = useState('');
  const [attendeesCount, setAttendeesCount] = useState('');
  const [reportText, setReportText] = useState('');
  const [submittedBy, setSubmittedBy] = useState('');
  const [phone, setPhone] = useState('');
  const [otpCode, setOtpCode] = useState('');
  const [phoneVerified, setPhoneVerified] = useState(false);
  const [reporterName, setReporterName] = useState('');
  const [otpSent, setOtpSent] = useState(false);
  const [busy, setBusy] = useState(false);
  const [otpBusy, setOtpBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [info, setInfo] = useState<string | null>(null);
  const [done, setDone] = useState(false);
  const [successMessage, setSuccessMessage] = useState('');

  useEffect(() => {
    let cancelled = false;
    fetchWorshipReportMeta()
      .then((meta) => {
        if (!cancelled) {
          setTypes(meta.service_types);
          if (meta.service_types[0] !== undefined) {
            setServiceType(meta.service_types[0].value);
          }
        }
      })
      .catch(() => {
        if (!cancelled) {
          setError('Impossible de charger les types de culte.');
        }
      });

    return () => {
      cancelled = true;
    };
  }, []);

  /**
   * Réinitialise la vérification quand le numéro change.
   */
  const onPhoneChange = (value: string) => {
    setPhone(value);
    setPhoneVerified(false);
    setOtpSent(false);
    setOtpCode('');
    setReporterName('');
    setInfo(null);
  };

  /**
   * Vérifie le numéro en base puis envoie l'OTP.
   */
  const handleSendOtp = async () => {
    setError(null);
    setInfo(null);
    setPhoneVerified(false);
    setOtpBusy(true);

    try {
      const lookup = await lookupWorshipReporterPhone(phone.trim());
      setReporterName(lookup.name);
      if (submittedBy.trim() === '') {
        setSubmittedBy(lookup.name);
      }
      const result = await sendWorshipReportOtp(phone.trim());
      setOtpSent(true);
      setInfo(result.message);
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'Vérification impossible.');
      setOtpSent(false);
    } finally {
      setOtpBusy(false);
    }
  };

  /**
   * Confirme le code OTP reçu par SMS.
   */
  const handleVerifyOtp = async () => {
    setError(null);
    setInfo(null);
    setOtpBusy(true);

    try {
      const result = await verifyWorshipReportOtp(phone.trim(), otpCode.trim());
      setPhoneVerified(result.verified === true);
      setInfo(result.message);
    } catch (err: unknown) {
      setPhoneVerified(false);
      setError(err instanceof Error ? err.message : 'Code incorrect.');
    } finally {
      setOtpBusy(false);
    }
  };

  /**
   * Envoie le rapport vers l'API.
   */
  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setError(null);

    if (!phoneVerified) {
      setError('Confirmez d’abord votre numéro avec le code SMS.');
      return;
    }

    const count = Number.parseInt(attendeesCount, 10);
    if (Number.isNaN(count) || count < 0) {
      setError('Indiquez un nombre de participants valide.');
      return;
    }
    if (reportText.trim() === '') {
      setError('Le rapport écrit est obligatoire.');
      return;
    }
    if (serviceType === '') {
      setError('Choisissez le type de culte.');
      return;
    }

    setBusy(true);
    try {
      const result = await submitWorshipReport({
        service_date: serviceDate,
        service_type: serviceType,
        attendees_count: count,
        report_text: reportText.trim(),
        submitted_by: submittedBy.trim() || undefined,
        phone: phone.trim(),
        otp_code: otpCode.trim(),
      });
      setSuccessMessage(result.message);
      setDone(true);
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'Envoi impossible.');
    } finally {
      setBusy(false);
    }
  };

  return (
    <>
      <PageHero
        compact
        badge="Protocole"
        title="Rapport de culte"
        description="Réservé aux numéros enregistrés. Un code SMS confirme votre identité avant l’envoi."
      />

      <section className="bg-gradient-to-b from-burgundy-50/40 to-white pb-20">
        <div className="mx-auto max-w-xl px-4 sm:px-6">
          <div className="rounded-3xl border border-surface-200 bg-white p-6 shadow-sm sm:p-8">
            {done ? (
              <div className="rounded-2xl border border-emerald-200 bg-emerald-50 p-6 text-center">
                <CheckCircle2 className="mx-auto mb-3 h-10 w-10 text-emerald-600" aria-hidden />
                <h2 className="font-heading text-lg font-semibold text-emerald-900">Rapport envoyé</h2>
                <p className="mt-2 text-sm text-emerald-800">{successMessage}</p>
                <button
                  type="button"
                  onClick={() => {
                    setDone(false);
                    setAttendeesCount('');
                    setReportText('');
                    setPhoneVerified(false);
                    setOtpSent(false);
                    setOtpCode('');
                  }}
                  className="mt-5 rounded-xl bg-burgundy-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-burgundy-800"
                >
                  Nouveau rapport
                </button>
              </div>
            ) : (
              <form onSubmit={(e) => void handleSubmit(e)} className="space-y-4">
                <div className="mb-2 flex items-center gap-3">
                  <span className="flex h-11 w-11 items-center justify-center rounded-2xl bg-burgundy-50 text-burgundy-800">
                    <ClipboardList className="h-5 w-5" aria-hidden />
                  </span>
                  <div>
                    <h2 className="font-heading text-lg font-semibold text-surface-900">Saisie protocole</h2>
                    <p className="text-xs text-surface-500">Accessible via QR code ou lien direct.</p>
                  </div>
                </div>

                <div className="rounded-2xl border border-surface-200 bg-surface-50 p-4 space-y-3">
                  <div className="flex items-center gap-2 text-sm font-semibold text-surface-800">
                    <ShieldCheck className="h-4 w-4 text-burgundy-700" aria-hidden />
                    Vérification du numéro
                  </div>
                  <div>
                    <label htmlFor="phone" className="mb-2 block text-sm font-medium">
                      Téléphone enregistré *
                    </label>
                    <input
                      id="phone"
                      value={phone}
                      onChange={(e) => onPhoneChange(e.target.value)}
                      className={INPUT_CLASS}
                      placeholder="Ex. 0812345678"
                      required
                    />
                    {reporterName !== '' ? (
                      <p className="mt-1 text-xs text-emerald-700">Identifié : {reporterName}</p>
                    ) : null}
                  </div>
                  <button
                    type="button"
                    disabled={otpBusy || phone.trim() === ''}
                    onClick={() => void handleSendOtp()}
                    className="rounded-xl border border-burgundy-200 bg-white px-4 py-2.5 text-sm font-semibold text-burgundy-900 hover:bg-burgundy-50 disabled:opacity-60"
                  >
                    {otpBusy && !otpSent ? 'Vérification…' : otpSent ? 'Renvoyer le code' : 'Vérifier et envoyer le code'}
                  </button>

                  {otpSent ? (
                    <div className="grid gap-3 sm:grid-cols-[1fr_auto]">
                      <div>
                        <label htmlFor="otp_code" className="mb-2 block text-sm font-medium">
                          Code SMS *
                        </label>
                        <input
                          id="otp_code"
                          value={otpCode}
                          onChange={(e) => {
                            setOtpCode(e.target.value);
                            setPhoneVerified(false);
                          }}
                          className={INPUT_CLASS}
                          placeholder="Code à 6 chiffres"
                          inputMode="numeric"
                        />
                      </div>
                      <div className="flex items-end">
                        <button
                          type="button"
                          disabled={otpBusy || otpCode.trim() === ''}
                          onClick={() => void handleVerifyOtp()}
                          className="w-full rounded-xl bg-burgundy-800 px-4 py-3 text-sm font-semibold text-white hover:bg-burgundy-700 disabled:opacity-60"
                        >
                          Confirmer
                        </button>
                      </div>
                    </div>
                  ) : null}

                  {phoneVerified ? (
                    <p className="text-sm font-medium text-emerald-700">Numéro confirmé — envoi du rapport autorisé.</p>
                  ) : null}
                </div>

                <div>
                  <label htmlFor="service_date" className="mb-2 block text-sm font-medium">
                    Date du culte *
                  </label>
                  <input
                    id="service_date"
                    type="date"
                    required
                    value={serviceDate}
                    onChange={(e) => setServiceDate(e.target.value)}
                    className={INPUT_CLASS}
                  />
                </div>

                <div>
                  <label htmlFor="service_type" className="mb-2 block text-sm font-medium">
                    Type de culte *
                  </label>
                  <select
                    id="service_type"
                    required
                    value={serviceType}
                    onChange={(e) => setServiceType(e.target.value)}
                    className={INPUT_CLASS}
                  >
                    {types.map((type) => (
                      <option key={type.value} value={type.value}>
                        {type.label}
                      </option>
                    ))}
                  </select>
                </div>

                <div>
                  <label htmlFor="attendees_count" className="mb-2 block text-sm font-medium">
                    Nombre de participants *
                  </label>
                  <input
                    id="attendees_count"
                    type="number"
                    min={0}
                    required
                    value={attendeesCount}
                    onChange={(e) => setAttendeesCount(e.target.value)}
                    className={INPUT_CLASS}
                    placeholder="Ex. 850"
                  />
                </div>

                <div>
                  <label htmlFor="report_text" className="mb-2 block text-sm font-medium">
                    Rapport écrit *
                  </label>
                  <textarea
                    id="report_text"
                    required
                    rows={5}
                    value={reportText}
                    onChange={(e) => setReportText(e.target.value)}
                    className={INPUT_CLASS}
                    placeholder="Déroulement, observations, points d’attention…"
                  />
                </div>

                <div>
                  <label htmlFor="submitted_by" className="mb-2 block text-sm font-medium">
                    Votre nom
                  </label>
                  <input
                    id="submitted_by"
                    value={submittedBy}
                    onChange={(e) => setSubmittedBy(e.target.value)}
                    className={INPUT_CLASS}
                  />
                </div>

                {info !== null ? (
                  <p className="rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-800">{info}</p>
                ) : null}
                {error !== null ? (
                  <p className="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{error}</p>
                ) : null}

                <button
                  type="submit"
                  disabled={busy || !phoneVerified}
                  className="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-burgundy-900 px-5 py-3.5 text-sm font-semibold text-white hover:bg-burgundy-800 disabled:opacity-60"
                >
                  <Send className="h-4 w-4" aria-hidden />
                  {busy ? 'Envoi…' : 'Envoyer le rapport'}
                </button>
              </form>
            )}
          </div>
        </div>
      </section>
    </>
  );
}
