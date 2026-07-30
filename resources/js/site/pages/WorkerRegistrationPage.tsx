import { useEffect, useMemo, useState, type FormEvent } from 'react';
import { CheckCircle2, ChevronLeft, ChevronRight, IdCard } from 'lucide-react';
import PageHero from '../components/ui/PageHero';
import PhotoCropField from '../components/workers/PhotoCropField';
import { cn } from '../lib/utils';
import {
  fetchWorkerRegistrationMeta,
  sendWorkerEmailOtp,
  submitWorkerRegistration,
  verifyWorkerEmailOtp,
  type WorkerDepartmentOption,
  type WorkerRegistrationMeta,
} from '../lib/siteApi';

const INPUT =
  'w-full rounded-xl border border-transparent bg-surface-100 px-4 py-3 text-sm text-surface-900 placeholder:text-surface-400 focus:border-burgundy-600 focus:bg-white focus:outline-none focus:ring-1 focus:ring-burgundy-600';

type Step = 1 | 2 | 3 | 4;

/**
 * Wizard public d'inscription ouvrier (QR / lien direct).
 */
export default function WorkerRegistrationPage() {
  const [meta, setMeta] = useState<WorkerRegistrationMeta | null>(null);
  const [step, setStep] = useState<Step>(1);
  const [departmentId, setDepartmentId] = useState<number | null>(null);
  const [lastName, setLastName] = useState('');
  const [firstName, setFirstName] = useState('');
  const [gender, setGender] = useState('male');
  const [birthDate, setBirthDate] = useState('');
  const [phone, setPhone] = useState('');
  const [email, setEmail] = useState('');
  const [city, setCity] = useState('Kinshasa');
  const [commune, setCommune] = useState('');
  const [quartier, setQuartier] = useState('');
  const [avenue, setAvenue] = useState('');
  const [addressReference, setAddressReference] = useState('');
  const [studies, setStudies] = useState('');
  const [educationLevel, setEducationLevel] = useState('');
  const [profession, setProfession] = useState('');
  const [skills, setSkills] = useState('');
  const [departmentRole, setDepartmentRole] = useState('');
  const [departmentJoinedAt, setDepartmentJoinedAt] = useState('');
  const [photoBlob, setPhotoBlob] = useState<Blob | null>(null);
  const [photoPreview, setPhotoPreview] = useState('');
  const [otpCode, setOtpCode] = useState('');
  const [emailVerified, setEmailVerified] = useState(false);
  const [otpSent, setOtpSent] = useState(false);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [info, setInfo] = useState<string | null>(null);
  const [done, setDone] = useState(false);
  const [successMessage, setSuccessMessage] = useState('');

  useEffect(() => {
    let cancelled = false;
    fetchWorkerRegistrationMeta()
      .then((data) => {
        if (!cancelled) {
          setMeta(data);
          if (data.genders[0] !== undefined) {
            setGender(data.genders[0].value);
          }
        }
      })
      .catch(() => {
        if (!cancelled) {
          setError('Impossible de charger le formulaire.');
        }
      });
    return () => {
      cancelled = true;
    };
  }, []);

  const selectedDepartment: WorkerDepartmentOption | null = useMemo(() => {
    if (meta === null || departmentId === null) {
      return null;
    }
    return meta.departments.find((d) => d.id === departmentId) ?? null;
  }, [departmentId, meta]);

  const canNext = (): boolean => {
    if (step === 1) {
      return departmentId !== null;
    }
    if (step === 2) {
      return (
        lastName.trim() !== ''
        && firstName.trim() !== ''
        && birthDate !== ''
        && phone.trim() !== ''
        && email.trim() !== ''
        && commune !== ''
        && quartier.trim() !== ''
        && avenue.trim() !== ''
      );
    }
    if (step === 3) {
      return true;
    }
    return photoBlob !== null && emailVerified;
  };

  /**
   * Envoie l'OTP e-mail.
   */
  const handleSendOtp = async () => {
    setError(null);
    setInfo(null);
    setBusy(true);
    try {
      const result = await sendWorkerEmailOtp(email.trim());
      setOtpSent(true);
      setEmailVerified(false);
      setInfo(result.message);
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'Envoi du code impossible.');
    } finally {
      setBusy(false);
    }
  };

  /**
   * Vérifie l'OTP e-mail.
   */
  const handleVerifyOtp = async () => {
    setError(null);
    setBusy(true);
    try {
      const result = await verifyWorkerEmailOtp(email.trim(), otpCode.trim());
      setEmailVerified(result.verified);
      setInfo(result.message);
    } catch (err: unknown) {
      setEmailVerified(false);
      setError(err instanceof Error ? err.message : 'Code incorrect.');
    } finally {
      setBusy(false);
    }
  };

  /**
   * Soumet le dossier.
   */
  const handleSubmit = async (event: FormEvent) => {
    event.preventDefault();
    if (!canNext() || departmentId === null || photoBlob === null) {
      setError('Complétez toutes les étapes et validez votre e-mail.');
      return;
    }

    setBusy(true);
    setError(null);
    try {
      const form = new FormData();
      form.append('department_id', String(departmentId));
      form.append('last_name', lastName.trim());
      form.append('first_name', firstName.trim());
      form.append('gender', gender);
      form.append('birth_date', birthDate);
      form.append('phone', phone.trim());
      form.append('email', email.trim());
      form.append('otp_code', otpCode.trim());
      form.append('city', city);
      form.append('commune', commune);
      form.append('quartier', quartier.trim());
      form.append('avenue', avenue.trim());
      if (addressReference.trim() !== '') {
        form.append('address_reference', addressReference.trim());
      }
      if (studies.trim() !== '') {
        form.append('studies', studies.trim());
      }
      if (educationLevel !== '') {
        form.append('education_level', educationLevel);
      }
      if (profession.trim() !== '') {
        form.append('profession', profession.trim());
      }
      if (skills.trim() !== '') {
        form.append('skills', skills.trim());
      }
      if (departmentRole.trim() !== '') {
        form.append('department_role', departmentRole.trim());
      }
      if (departmentJoinedAt !== '') {
        form.append('department_joined_at', departmentJoinedAt);
      }
      form.append('photo', photoBlob, 'photo.jpg');

      const result = await submitWorkerRegistration(form);
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
        badge="Ouvriers CMP"
        title="Inscription ouvrier"
        description="Scannez le QR ou ouvrez ce lien pour constituer votre dossier et recevoir votre badge."
      />

      <section className="bg-gradient-to-b from-burgundy-50/40 to-white pb-20">
        <div className="mx-auto max-w-2xl px-4 sm:px-6">
          <div className="rounded-3xl border border-surface-200 bg-white p-6 shadow-sm sm:p-8">
            {done ? (
              <div className="rounded-2xl border border-emerald-200 bg-emerald-50 p-6 text-center">
                <CheckCircle2 className="mx-auto mb-3 h-10 w-10 text-emerald-600" />
                <h2 className="font-heading text-lg font-semibold text-emerald-900">Dossier transmis</h2>
                <p className="mt-2 text-sm text-emerald-800">{successMessage}</p>
              </div>
            ) : (
              <form onSubmit={(e) => void handleSubmit(e)} className="space-y-6">
                <div className="flex items-center gap-3">
                  <span className="flex h-11 w-11 items-center justify-center rounded-2xl bg-burgundy-50 text-burgundy-800">
                    <IdCard className="h-5 w-5" />
                  </span>
                  <div>
                    <h2 className="font-heading text-lg font-semibold">Étape {step} / 4</h2>
                    <p className="text-xs text-surface-500">
                      {step === 1 && 'Choisissez votre département'}
                      {step === 2 && 'Informations personnelles'}
                      {step === 3 && 'Informations professionnelles'}
                      {step === 4 && 'Photo, e-mail OTP et validation'}
                    </p>
                  </div>
                </div>

                <div className="flex gap-1">
                  {([1, 2, 3, 4] as Step[]).map((s) => (
                    <div
                      key={s}
                      className={cn(
                        'h-1.5 flex-1 rounded-full',
                        s <= step ? 'bg-burgundy-700' : 'bg-surface-200',
                      )}
                    />
                  ))}
                </div>

                {step === 1 && meta !== null ? (
                  <div className="grid gap-3 sm:grid-cols-2">
                    {meta.departments.map((dept) => (
                      <button
                        key={dept.id}
                        type="button"
                        onClick={() => setDepartmentId(dept.id)}
                        className={cn(
                          'rounded-2xl border px-4 py-4 text-left transition',
                          departmentId === dept.id
                            ? 'border-burgundy-600 bg-burgundy-50 ring-2 ring-burgundy-200'
                            : 'border-surface-200 hover:border-burgundy-300',
                        )}
                      >
                        <span
                          className="mb-2 inline-block h-2.5 w-2.5 rounded-full"
                          style={{ background: dept.color }}
                        />
                        <div className="font-semibold text-surface-900">{dept.name}</div>
                        {dept.description !== '' ? (
                          <p className="mt-1 text-xs text-surface-500 line-clamp-2">{dept.description}</p>
                        ) : null}
                      </button>
                    ))}
                  </div>
                ) : null}

                {step === 2 && meta !== null ? (
                  <div className="grid gap-4 sm:grid-cols-2">
                    <div>
                      <label className="mb-1 block text-sm font-medium">Nom *</label>
                      <input className={INPUT} value={lastName} onChange={(e) => setLastName(e.target.value)} required />
                    </div>
                    <div>
                      <label className="mb-1 block text-sm font-medium">Prénom *</label>
                      <input className={INPUT} value={firstName} onChange={(e) => setFirstName(e.target.value)} required />
                    </div>
                    <div>
                      <label className="mb-1 block text-sm font-medium">Sexe *</label>
                      <select className={INPUT} value={gender} onChange={(e) => setGender(e.target.value)}>
                        {meta.genders.map((g) => (
                          <option key={g.value} value={g.value}>{g.label}</option>
                        ))}
                      </select>
                    </div>
                    <div>
                      <label className="mb-1 block text-sm font-medium">Date de naissance *</label>
                      <input type="date" className={INPUT} value={birthDate} onChange={(e) => setBirthDate(e.target.value)} required />
                    </div>
                    <div>
                      <label className="mb-1 block text-sm font-medium">Téléphone *</label>
                      <input className={INPUT} value={phone} onChange={(e) => setPhone(e.target.value)} required />
                    </div>
                    <div>
                      <label className="mb-1 block text-sm font-medium">E-mail *</label>
                      <input
                        type="email"
                        className={INPUT}
                        value={email}
                        onChange={(e) => {
                          setEmail(e.target.value);
                          setEmailVerified(false);
                          setOtpSent(false);
                          setOtpCode('');
                        }}
                        required
                        disabled={emailVerified}
                        readOnly={emailVerified}
                      />
                      {emailVerified ? (
                        <p className="mt-1 text-xs text-emerald-700">E-mail verrouillé après vérification OTP.</p>
                      ) : null}
                    </div>
                    <div>
                      <label className="mb-1 block text-sm font-medium">Ville *</label>
                      <select className={INPUT} value={city} onChange={(e) => setCity(e.target.value)}>
                        {meta.cities.map((c) => (
                          <option key={c} value={c}>{c}</option>
                        ))}
                      </select>
                    </div>
                    <div>
                      <label className="mb-1 block text-sm font-medium">Commune *</label>
                      <select
                        className={INPUT}
                        value={commune}
                        onChange={(e) => setCommune(e.target.value)}
                        disabled={city !== 'Kinshasa'}
                        required
                      >
                        <option value="">Choisir…</option>
                        {meta.communes.map((c) => (
                          <option key={c} value={c}>{c}</option>
                        ))}
                      </select>
                    </div>
                    <div>
                      <label className="mb-1 block text-sm font-medium">Quartier *</label>
                      <input className={INPUT} value={quartier} onChange={(e) => setQuartier(e.target.value)} required />
                    </div>
                    <div>
                      <label className="mb-1 block text-sm font-medium">Avenue *</label>
                      <input className={INPUT} value={avenue} onChange={(e) => setAvenue(e.target.value)} required />
                    </div>
                    <div className="sm:col-span-2">
                      <label className="mb-1 block text-sm font-medium">Référence (optionnel)</label>
                      <input className={INPUT} value={addressReference} onChange={(e) => setAddressReference(e.target.value)} />
                    </div>
                  </div>
                ) : null}

                {step === 3 && meta !== null ? (
                  <div className="grid gap-4 sm:grid-cols-2">
                    <div className="sm:col-span-2 rounded-xl bg-burgundy-50 px-4 py-3 text-sm text-burgundy-900">
                      Département : <strong>{selectedDepartment?.name ?? '—'}</strong>
                    </div>
                    <div>
                      <label className="mb-1 block text-sm font-medium">Études faites</label>
                      <input className={INPUT} value={studies} onChange={(e) => setStudies(e.target.value)} />
                    </div>
                    <div>
                      <label className="mb-1 block text-sm font-medium">Niveau d’étude</label>
                      <select className={INPUT} value={educationLevel} onChange={(e) => setEducationLevel(e.target.value)}>
                        <option value="">—</option>
                        {meta.education_levels.map((level) => (
                          <option key={level} value={level}>{level}</option>
                        ))}
                      </select>
                    </div>
                    <div>
                      <label className="mb-1 block text-sm font-medium">Profession actuelle</label>
                      <input className={INPUT} value={profession} onChange={(e) => setProfession(e.target.value)} />
                    </div>
                    <div>
                      <label className="mb-1 block text-sm font-medium">Rôle dans le département</label>
                      <input className={INPUT} value={departmentRole} onChange={(e) => setDepartmentRole(e.target.value)} />
                    </div>
                    <div>
                      <label className="mb-1 block text-sm font-medium">Date d’intégration</label>
                      <input type="date" className={INPUT} value={departmentJoinedAt} onChange={(e) => setDepartmentJoinedAt(e.target.value)} />
                    </div>
                    <div className="sm:col-span-2">
                      <label className="mb-1 block text-sm font-medium">Compétences</label>
                      <textarea className={INPUT} rows={3} value={skills} onChange={(e) => setSkills(e.target.value)} />
                    </div>
                  </div>
                ) : null}

                {step === 4 ? (
                  <div className="space-y-5">
                    <PhotoCropField
                      value={photoBlob}
                      previewUrl={photoPreview}
                      onChange={(blob, preview) => {
                        setPhotoBlob(blob);
                        setPhotoPreview(preview);
                      }}
                    />

                    <div className="rounded-2xl border border-surface-200 bg-surface-50 p-4 space-y-3">
                      <p className="text-sm font-semibold text-surface-800">Vérification e-mail ({email})</p>
                      {emailVerified ? (
                        <div className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                          <p className="font-semibold">E-mail confirmé</p>
                          <p className="mt-1 text-emerald-700">Les champs OTP sont verrouillés. Vous pouvez valider votre inscription.</p>
                          <p className="mt-2 font-mono tracking-widest text-emerald-900">{otpCode}</p>
                        </div>
                      ) : (
                        <>
                          <button
                            type="button"
                            disabled={busy || email.trim() === ''}
                            onClick={() => void handleSendOtp()}
                            className="rounded-xl border border-burgundy-200 bg-white px-4 py-2.5 text-sm font-semibold text-burgundy-900 disabled:opacity-60"
                          >
                            {otpSent ? 'Renvoyer le code' : 'Envoyer le code OTP'}
                          </button>
                          {otpSent ? (
                            <div className="grid gap-3 sm:grid-cols-[1fr_auto]">
                              <input
                                className={INPUT}
                                value={otpCode}
                                onChange={(e) => setOtpCode(e.target.value)}
                                placeholder="Code reçu par e-mail"
                                inputMode="numeric"
                                autoComplete="one-time-code"
                              />
                              <button
                                type="button"
                                disabled={busy || otpCode.trim() === ''}
                                onClick={() => void handleVerifyOtp()}
                                className="rounded-xl bg-burgundy-800 px-4 py-3 text-sm font-semibold text-white disabled:opacity-60"
                              >
                                Confirmer
                              </button>
                            </div>
                          ) : null}
                        </>
                      )}
                    </div>
                  </div>
                ) : null}

                {info !== null ? <p className="rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-800">{info}</p> : null}
                {error !== null ? <p className="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{error}</p> : null}

                <div className="flex flex-wrap gap-2">
                  {step > 1 ? (
                    <button
                      type="button"
                      onClick={() => setStep((s) => (s - 1) as Step)}
                      className="inline-flex items-center gap-2 rounded-xl border border-surface-200 px-4 py-3 text-sm font-semibold"
                    >
                      <ChevronLeft className="h-4 w-4" /> Retour
                    </button>
                  ) : null}
                  {step < 4 ? (
                    <button
                      type="button"
                      disabled={!canNext()}
                      onClick={() => {
                        if (!canNext()) {
                          setError('Complétez les champs obligatoires.');
                          return;
                        }
                        setError(null);
                        setStep((s) => (s + 1) as Step);
                      }}
                      className="ml-auto inline-flex items-center gap-2 rounded-xl bg-burgundy-900 px-5 py-3 text-sm font-semibold text-white disabled:opacity-60"
                    >
                      Suivant <ChevronRight className="h-4 w-4" />
                    </button>
                  ) : (
                    <button
                      type="submit"
                      disabled={busy || !canNext()}
                      className="ml-auto rounded-xl bg-burgundy-900 px-5 py-3 text-sm font-semibold text-white disabled:opacity-60"
                    >
                      {busy ? 'Envoi…' : 'Valider mon inscription'}
                    </button>
                  )}
                </div>
              </form>
            )}
          </div>
        </div>
      </section>
    </>
  );
}
