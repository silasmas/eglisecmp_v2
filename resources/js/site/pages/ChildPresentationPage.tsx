import { useEffect, useState, type FormEvent } from 'react';
import { Baby, CheckCircle2, Send, ShieldCheck } from 'lucide-react';
import PageHero from '../components/ui/PageHero';
import FileDropZone from '../components/ui/FileDropZone';
import { BLESSED_FAMILY_PHOTOS } from '../data/blessedFamilies';
import { ageFromBirthDate, formatAgeLabel, todayIsoDate } from '../lib/childAge';
import {
  fetchChildPresentationMeta,
  getEcodimHint,
  sendChildPresentationOtp,
  submitChildPresentation,
  verifyChildPresentationOtp,
  type ChildPresentationMeta,
} from '../lib/siteApi';
import { cn } from '../lib/utils';

const INPUT_CLASS =
  'w-full rounded-lg border border-transparent bg-surface-100 px-4 py-3 text-sm text-surface-900 placeholder:text-surface-400 focus:border-burgundy-600 focus:bg-white focus:outline-none focus:ring-1 focus:ring-burgundy-600 dark:bg-surface-800 dark:text-white dark:placeholder:text-surface-500 dark:focus:bg-surface-900';

type ChildDraft = {
  full_name: string;
  gender: 'male' | 'female' | '';
  birth_date: string;
  age_years: string;
  age_months: string;
  ageLabel: string | null;
  ecodimMessage: string | null;
};

/**
 * Affiche un libellé de champ avec astérisque optionnel.
 *
 * @param label Texte du libellé.
 * @param htmlFor Id du champ associé.
 * @param required Affiche l’astérisque si vrai.
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

/**
 * Construit N fiches enfant vides.
 *
 * @param count Nombre de fiches.
 * @returns Liste de brouillons.
 */
function buildChildDrafts(count: number): ChildDraft[] {
  return Array.from({ length: count }, () => ({
    full_name: '',
    gender: '',
    birth_date: '',
    age_years: '',
    age_months: '0',
    ageLabel: null,
    ecodimMessage: null,
  }));
}

/**
 * Page publique : infos + formulaire de présentation d'enfants (2e / 4e dimanche).
 */
export default function ChildPresentationPage() {
  const [meta, setMeta] = useState<ChildPresentationMeta | null>(null);
  const [metaError, setMetaError] = useState<string | null>(null);
  const [childrenCount, setChildrenCount] = useState(1);
  const [children, setChildren] = useState<ChildDraft[]>(buildChildDrafts(1));
  const [parentNames, setParentNames] = useState('');
  const [phone, setPhone] = useState('');
  const [otpCode, setOtpCode] = useState('');
  const [otpSent, setOtpSent] = useState(false);
  const [phoneVerified, setPhoneVerified] = useState(false);
  const [otpBusy, setOtpBusy] = useState(false);
  const [presentationDate, setPresentationDate] = useState('');
  const [birthFiles, setBirthFiles] = useState<File[]>([]);
  const [idFiles, setIdFiles] = useState<File[]>([]);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [done, setDone] = useState(false);
  const [successMessage, setSuccessMessage] = useState('');
  const maxBirthDate = todayIsoDate();

  useEffect(() => {
    let cancelled = false;

    fetchChildPresentationMeta()
      .then((data) => {
        if (!cancelled) {
          setMeta(data);
          if (data.dates.length > 0) {
            setPresentationDate(data.dates[0].date);
          }
        }
      })
      .catch((err: unknown) => {
        if (!cancelled) {
          setMetaError(err instanceof Error ? err.message : 'Impossible de charger les dates.');
        }
      });

    return () => {
      cancelled = true;
    };
  }, []);

  /**
   * Met à jour le nombre d'enfants et recalcule les fiches.
   *
   * @param value Nouveau nombre (1–10).
   */
  const handleChildrenCountChange = (value: number) => {
    const next = Math.min(10, Math.max(1, value));
    setChildrenCount(next);
    setChildren((previous) => {
      if (previous.length === next) {
        return previous;
      }
      if (previous.length < next) {
        return [...previous, ...buildChildDrafts(next - previous.length)];
      }
      return previous.slice(0, next);
    });
  };

  /**
   * Met à jour un champ d'une fiche enfant ; recalcule l’âge et le hint ECODIM.
   *
   * @param index Index de la fiche.
   * @param patch Champs partiels.
   */
  const updateChild = async (index: number, patch: Partial<ChildDraft>) => {
    if (patch.birth_date !== undefined) {
      const age = ageFromBirthDate(patch.birth_date);
      if (age === null) {
        setChildren((previous) => {
          const next = [...previous];
          next[index] = {
            ...next[index],
            birth_date: patch.birth_date ?? '',
            age_years: '',
            age_months: '0',
            ageLabel: null,
            ecodimMessage: null,
          };
          return next;
        });
        return;
      }

      const ageLabel = formatAgeLabel(age.years, age.months);
      setChildren((previous) => {
        const next = [...previous];
        next[index] = {
          ...next[index],
          birth_date: patch.birth_date ?? '',
          age_years: String(age.years),
          age_months: String(age.months),
          ageLabel,
        };
        return next;
      });

      try {
        const hint = await getEcodimHint(age.years, age.months);
        setChildren((previous) => {
          const next = [...previous];
          if (next[index] !== undefined) {
            next[index] = { ...next[index], ecodimMessage: hint.message };
          }
          return next;
        });
      } catch {
        // hint optionnel
      }
      return;
    }

    setChildren((previous) => {
      const next = [...previous];
      next[index] = { ...next[index], ...patch };
      return next;
    });
  };

  /**
   * Envoie le code OTP au numéro renseigné.
   */
  const handleSendOtp = async () => {
    setError(null);
    if (phone.trim() === '') {
      setError('Indiquez un numéro de téléphone.');
      return;
    }
    setOtpBusy(true);
    try {
      await sendChildPresentationOtp(phone.trim());
      setOtpSent(true);
      setPhoneVerified(false);
      setOtpCode('');
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'Envoi du code impossible.');
    } finally {
      setOtpBusy(false);
    }
  };

  /**
   * Vérifie le code OTP saisi.
   */
  const handleVerifyOtp = async () => {
    setError(null);
    if (otpCode.trim() === '') {
      setError('Saisissez le code reçu par SMS.');
      return;
    }
    setOtpBusy(true);
    try {
      await verifyChildPresentationOtp(phone.trim(), otpCode.trim());
      setPhoneVerified(true);
    } catch (err: unknown) {
      setPhoneVerified(false);
      setError(err instanceof Error ? err.message : 'Code incorrect.');
    } finally {
      setOtpBusy(false);
    }
  };

  /**
   * Réinitialise le formulaire après succès.
   */
  const resetForm = () => {
    setDone(false);
    setError(null);
    setChildrenCount(1);
    setChildren(buildChildDrafts(1));
    setParentNames('');
    setPhone('');
    setOtpCode('');
    setOtpSent(false);
    setPhoneVerified(false);
    setBirthFiles([]);
    setIdFiles([]);
    setPresentationDate(meta?.dates[0]?.date ?? '');
  };

  /**
   * Soumet la demande de présentation.
   *
   * @param event Événement de soumission du formulaire.
   */
  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setError(null);

    if (!phoneVerified) {
      setError('Vérifiez d’abord votre numéro de téléphone avec le code SMS.');
      return;
    }
    if (parentNames.trim() === '') {
      setError('Les noms des parents sont obligatoires.');
      return;
    }
    if (presentationDate === '') {
      setError('Choisissez une date de présentation.');
      return;
    }
    if (birthFiles[0] === undefined) {
      setError('Joignez l’acte de naissance.');
      return;
    }
    if (idFiles[0] === undefined) {
      setError('Joignez la pièce d’identité d’un parent.');
      return;
    }

    for (let i = 0; i < children.length; i++) {
      if (children[i].full_name.trim() === '') {
        setError(`Indiquez le nom complet de l’enfant ${i + 1}.`);
        return;
      }
      if (children[i].gender !== 'male' && children[i].gender !== 'female') {
        setError(`Indiquez le sexe de l’enfant ${i + 1}.`);
        return;
      }
      if (children[i].birth_date.trim() === '' || children[i].age_years.trim() === '') {
        setError(`Indiquez la date de naissance de l’enfant ${i + 1}.`);
        return;
      }
    }

    setBusy(true);
    try {
      const result = await submitChildPresentation({
        children_count: childrenCount,
        parent_names: parentNames.trim(),
        phone: phone.trim(),
        otp_code: otpCode.trim(),
        presentation_date: presentationDate,
        children: children.map((child) => ({
          full_name: child.full_name.trim(),
          gender: child.gender as 'male' | 'female',
          age_years: Number.parseInt(child.age_years, 10),
          age_months: Number.parseInt(child.age_months || '0', 10) || 0,
        })),
        birth_certificate: birthFiles[0],
        parent_id_document: idFiles[0],
      });
      setSuccessMessage(result.message);
      setDone(true);
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'Envoi impossible.');
    } finally {
      setBusy(false);
    }
  };

  const familyPhotos = BLESSED_FAMILY_PHOTOS.filter((photo) => photo.kind === 'family');
  const parentPhotos = BLESSED_FAMILY_PHOTOS.filter((photo) => photo.kind === 'parents');

  return (
    <>
      <PageHero
        compact
        badge="Famille"
        title="Présentation des enfants"
        description="Inscrivez votre enfant pour une présentation au culte (2e et 4e dimanches du mois)."
        backgroundImage="https://images.unsplash.com/photo-1511895426328-dc8714191300?w=1600&h=700&fit=crop"
      />

      <section className="bg-gradient-to-b from-burgundy-50/40 to-white pb-20 dark:from-surface-950 dark:to-surface-950">
        <div className="mx-auto grid max-w-6xl items-start gap-8 px-4 sm:px-6 lg:grid-cols-2 lg:px-8">
          <div className="space-y-6">
            <aside className="rounded-3xl border border-burgundy-100 bg-white p-6 shadow-sm dark:border-surface-700 dark:bg-surface-900 sm:p-8">
              <div className="mb-5 flex h-12 w-12 items-center justify-center rounded-2xl bg-burgundy-50 text-burgundy-800 dark:bg-burgundy-900/40 dark:text-burgundy-200">
                <Baby className="h-6 w-6" aria-hidden />
              </div>
              <h2 className="font-heading text-xl font-semibold text-surface-900 dark:text-white">
                Ce qu’il faut savoir
              </h2>
              <p className="mt-2 text-sm text-surface-600 dark:text-surface-300">
                La présentation se fait uniquement les <strong>2e et 4e dimanches</strong> du mois.
                Après validation par l’administration, vous recevrez un SMS de confirmation.
              </p>
              <ul className="mt-6 space-y-3">
                {(meta?.requirements ?? [
                  'La présentation des enfants a lieu uniquement les 2e et 4e dimanches du mois.',
                  'Prévoir l’acte de naissance et une pièce d’identité d’un parent.',
                  'Être présent au début du culte le jour de la présentation.',
                ]).map((item) => (
                  <li key={item} className="flex gap-3 text-sm text-surface-700 dark:text-surface-200">
                    <CheckCircle2 className="mt-0.5 h-4 w-4 shrink-0 text-burgundy-700" aria-hidden />
                    <span>{item}</span>
                  </li>
                ))}
              </ul>
            </aside>

            <div className="space-y-5">
              <div>
                <h3 className="font-heading text-lg font-semibold text-surface-900 dark:text-white">
                  Familles présentées
                </h3>
                <p className="mt-1 text-sm text-surface-600 dark:text-surface-300">
                  Des moments de bénédiction partagés au culte.
                </p>
                <div className="mt-3 grid grid-cols-2 gap-3">
                  {familyPhotos.map((photo) => (
                    <figure key={photo.id} className="overflow-hidden rounded-2xl">
                      <img
                        src={photo.src}
                        alt={photo.caption}
                        className="aspect-[4/3] h-full w-full object-cover transition duration-500 hover:scale-105"
                        loading="lazy"
                      />
                    </figure>
                  ))}
                </div>
              </div>

              <div>
                <h3 className="font-heading text-lg font-semibold text-surface-900 dark:text-white">
                  Parents bénis
                </h3>
                <p className="mt-1 text-sm text-surface-600 dark:text-surface-300">
                  Parents qui ont présenté leurs enfants devant Dieu.
                </p>
                <div className="mt-3 grid grid-cols-2 gap-3">
                  {parentPhotos.map((photo) => (
                    <figure key={photo.id} className="overflow-hidden rounded-2xl">
                      <img
                        src={photo.src}
                        alt={photo.caption}
                        className="aspect-[4/3] h-full w-full object-cover transition duration-500 hover:scale-105"
                        loading="lazy"
                      />
                    </figure>
                  ))}
                </div>
              </div>
            </div>
          </div>

          <div className="rounded-3xl border border-surface-200 bg-white p-6 shadow-sm dark:border-surface-700 dark:bg-surface-900 sm:p-8">
            {done ? (
              <div className="rounded-2xl border border-emerald-200 bg-emerald-50 p-6 text-center dark:border-emerald-900 dark:bg-emerald-950/40">
                <CheckCircle2 className="mx-auto mb-3 h-10 w-10 text-emerald-600" aria-hidden />
                <h3 className="font-heading text-lg font-semibold text-emerald-900 dark:text-emerald-200">
                  Demande envoyée
                </h3>
                <p className="mt-2 text-sm text-emerald-800 dark:text-emerald-300">{successMessage}</p>
                <button
                  type="button"
                  onClick={resetForm}
                  className="mt-5 rounded-xl bg-burgundy-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-burgundy-800"
                >
                  Nouvelle demande
                </button>
              </div>
            ) : (
              <form onSubmit={handleSubmit} className="space-y-5">
                <h2 className="font-heading text-xl font-semibold text-surface-900 dark:text-white">
                  Formulaire d’inscription
                </h2>

                {metaError !== null ? (
                  <p className="rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800">{metaError}</p>
                ) : null}

                <div>
                  <FieldLabel label="Nombre d’enfants à présenter" htmlFor="children_count" required />
                  <input
                    id="children_count"
                    type="number"
                    min={1}
                    max={10}
                    value={childrenCount}
                    onChange={(e) => handleChildrenCountChange(Number.parseInt(e.target.value, 10) || 1)}
                    className={INPUT_CLASS}
                  />
                </div>

                <div className="space-y-4">
                  {children.map((child, index) => (
                    <div
                      key={`child-${index}`}
                      className="rounded-2xl border border-surface-200 bg-surface-50/80 p-4 dark:border-surface-700 dark:bg-surface-800/50"
                    >
                      <p className="mb-3 text-sm font-semibold text-burgundy-800 dark:text-burgundy-300">
                        Enfant {index + 1}
                      </p>
                      <div className="space-y-3">
                        <div>
                          <FieldLabel label="Nom complet" htmlFor={`child_name_${index}`} required />
                          <input
                            id={`child_name_${index}`}
                            value={child.full_name}
                            onChange={(e) => void updateChild(index, { full_name: e.target.value })}
                            className={INPUT_CLASS}
                            placeholder="Prénom et nom"
                          />
                        </div>
                        <div>
                          <FieldLabel label="Sexe" htmlFor={`child_gender_${index}`} required />
                          <select
                            id={`child_gender_${index}`}
                            value={child.gender}
                            onChange={(e) =>
                              void updateChild(index, {
                                gender: e.target.value as 'male' | 'female' | '',
                              })
                            }
                            className={INPUT_CLASS}
                          >
                            <option value="">Sélectionner</option>
                            <option value="male">Garçon</option>
                            <option value="female">Fille</option>
                          </select>
                        </div>
                        <div>
                          <FieldLabel label="Date de naissance" htmlFor={`child_birth_${index}`} required />
                          <input
                            id={`child_birth_${index}`}
                            type="date"
                            max={maxBirthDate}
                            value={child.birth_date}
                            onChange={(e) => void updateChild(index, { birth_date: e.target.value })}
                            className={INPUT_CLASS}
                          />
                          {child.ageLabel !== null ? (
                            <p className="mt-2 text-xs font-medium text-surface-600 dark:text-surface-300">
                              Âge calculé : {child.ageLabel}
                            </p>
                          ) : null}
                        </div>
                        {child.ecodimMessage !== null ? (
                          <p className="rounded-lg bg-sky-50 px-3 py-2 text-xs text-sky-900 dark:bg-sky-950/50 dark:text-sky-200">
                            {child.ecodimMessage}
                          </p>
                        ) : null}
                      </div>
                    </div>
                  ))}
                </div>

                <div>
                  <FieldLabel label="Noms des parents" htmlFor="parent_names" required />
                  <input
                    id="parent_names"
                    value={parentNames}
                    onChange={(e) => setParentNames(e.target.value)}
                    className={INPUT_CLASS}
                    placeholder="Ex. Jean et Marie Kabongo"
                  />
                </div>

                <div>
                  <FieldLabel label="Téléphone d’un parent" htmlFor="phone" required />
                  <div className="flex flex-col gap-2 sm:flex-row">
                    <input
                      id="phone"
                      value={phone}
                      onChange={(e) => {
                        setPhone(e.target.value);
                        setPhoneVerified(false);
                        setOtpSent(false);
                      }}
                      className={cn(INPUT_CLASS, 'sm:flex-1')}
                      placeholder="08XX XXX XXX"
                    />
                    <button
                      type="button"
                      disabled={otpBusy}
                      onClick={() => void handleSendOtp()}
                      className="rounded-lg bg-surface-900 px-4 py-3 text-sm font-semibold text-white hover:bg-surface-800 disabled:opacity-60"
                    >
                      {otpSent ? 'Renvoyer le code' : 'Recevoir le code'}
                    </button>
                  </div>
                </div>

                {otpSent ? (
                  <div>
                    <FieldLabel label="Code OTP reçu par SMS" htmlFor="otp_code" required />
                    <div className="flex flex-col gap-2 sm:flex-row">
                      <input
                        id="otp_code"
                        value={otpCode}
                        onChange={(e) => {
                          setOtpCode(e.target.value);
                          setPhoneVerified(false);
                        }}
                        className={cn(INPUT_CLASS, 'sm:flex-1')}
                        placeholder="123456"
                        inputMode="numeric"
                      />
                      <button
                        type="button"
                        disabled={otpBusy || phoneVerified}
                        onClick={() => void handleVerifyOtp()}
                        className="inline-flex items-center justify-center gap-2 rounded-lg bg-burgundy-800 px-4 py-3 text-sm font-semibold text-white hover:bg-burgundy-700 disabled:opacity-60"
                      >
                        <ShieldCheck className="h-4 w-4" aria-hidden />
                        {phoneVerified ? 'Vérifié' : 'Vérifier'}
                      </button>
                    </div>
                    {phoneVerified ? (
                      <p className="mt-2 text-xs font-medium text-emerald-700">Numéro authentifié.</p>
                    ) : null}
                  </div>
                ) : null}

                <div>
                  <FieldLabel label="Date de présentation" htmlFor="presentation_date" required />
                  <select
                    id="presentation_date"
                    value={presentationDate}
                    onChange={(e) => setPresentationDate(e.target.value)}
                    className={INPUT_CLASS}
                  >
                    {(meta?.dates ?? []).map((date) => (
                      <option key={date.date} value={date.date}>
                        {date.label}
                      </option>
                    ))}
                  </select>
                </div>

                <FileDropZone
                  label="Acte de naissance *"
                  hint={`PDF ou image, max. ${meta?.max_document_mb ?? 5} Mo`}
                  accept=".pdf,image/jpeg,image/png"
                  files={birthFiles}
                  onFilesChange={setBirthFiles}
                  maxFiles={1}
                />

                <FileDropZone
                  label="Pièce d’identité des parents *"
                  hint={`PDF ou image, max. ${meta?.max_document_mb ?? 5} Mo`}
                  accept=".pdf,image/jpeg,image/png"
                  files={idFiles}
                  onFilesChange={setIdFiles}
                  maxFiles={1}
                />

                {error !== null ? (
                  <p className="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700 dark:bg-red-950/40 dark:text-red-300">
                    {error}
                  </p>
                ) : null}

                <button
                  type="submit"
                  disabled={busy || !phoneVerified}
                  className="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-burgundy-900 px-5 py-3.5 text-sm font-semibold text-white transition hover:bg-burgundy-800 disabled:cursor-not-allowed disabled:opacity-60"
                >
                  <Send className="h-4 w-4" aria-hidden />
                  {busy ? 'Envoi…' : 'Soumettre la demande'}
                </button>
              </form>
            )}
          </div>
        </div>
      </section>
    </>
  );
}
