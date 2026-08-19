import { useEffect, useMemo, useState, type FormEvent } from 'react';
import { useParams } from 'react-router-dom';
import { CheckCircle2, ChevronLeft, ChevronRight, Loader2, Send } from 'lucide-react';
import {
  fetchGuestInviteForm,
  submitGuestInviteForm,
  type GuestFormShowResponse,
  type GuestInfoFormPublic,
} from '../lib/siteApi';

type AnswersMap = Record<string, unknown>;

/**
 * Formate une valeur de case à cocher / grille pour l’état local.
 *
 * @param current Valeur actuelle.
 * @param item Élément à basculer.
 * @returns Nouvelle liste.
 */
function toggleListValue(current: unknown, item: string): string[] {
  const list = Array.isArray(current) ? (current as string[]) : [];
  return list.includes(item) ? list.filter((v) => v !== item) : [...list, item];
}

/**
 * Vérifie les champs obligatoires d’une rubrique.
 *
 * @param section Rubrique à valider.
 * @param answers Réponses courantes.
 * @returns Message d’erreur ou null.
 */
function validateSection(
  section: GuestInfoFormPublic['sections'][number],
  answers: AnswersMap,
): string | null {
  for (const field of section.fields) {
    if (!field.required) {
      continue;
    }
    const value = answers[field.key];
    const empty =
      value === null ||
      value === undefined ||
      value === '' ||
      (Array.isArray(value) && value.length === 0);
    if (empty) {
      return `Veuillez renseigner : ${field.label}`;
    }
  }
  return null;
}

/**
 * Page publique : fiche de renseignements pour un pasteur invité.
 */
export default function GuestInviteFormPage() {
  const { token = '' } = useParams();
  const [data, setData] = useState<GuestFormShowResponse | null>(null);
  const [answers, setAnswers] = useState<AnswersMap>({});
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [done, setDone] = useState(false);
  const [step, setStep] = useState(0);

  useEffect(() => {
    let cancelled = false;
    setLoading(true);
    setError(null);
    fetchGuestInviteForm(token)
      .then((res) => {
        if (!cancelled) {
          setData(res);
          setDone(res.already_submitted);
          setStep(0);
        }
      })
      .catch((err: unknown) => {
        if (!cancelled) {
          setError(err instanceof Error ? err.message : 'Impossible de charger le formulaire.');
        }
      })
      .finally(() => {
        if (!cancelled) {
          setLoading(false);
        }
      });
    return () => {
      cancelled = true;
    };
  }, [token]);

  const cssVars = useMemo(() => {
    const design = data?.form.design;
    return {
      ['--guest-primary' as string]: design?.primary_color ?? '#7b1d3e',
      ['--guest-accent' as string]: design?.accent_color ?? '#ea7e2d',
      ['--guest-radius' as string]: `${design?.radius ?? 16}px`,
    };
  }, [data]);

  const isWizard = data?.form.layout_mode === 'wizard';
  const sections = data?.form.sections ?? [];
  const currentSection = sections[step] ?? null;
  const isLastStep = step >= sections.length - 1;

  /**
   * Met à jour une réponse.
   *
   * @param key Clé du champ.
   * @param value Nouvelle valeur.
   */
  const setAnswer = (key: string, value: unknown) => {
    setAnswers((prev) => ({ ...prev, [key]: value }));
  };

  /**
   * Passe à l’étape suivante après validation locale.
   */
  const goNext = () => {
    if (!currentSection) {
      return;
    }
    const sectionError = validateSection(currentSection, answers);
    if (sectionError) {
      setError(sectionError);
      return;
    }
    setError(null);
    setStep((prev) => Math.min(prev + 1, sections.length - 1));
  };

  /**
   * Revient à l’étape précédente.
   */
  const goPrev = () => {
    setError(null);
    setStep((prev) => Math.max(prev - 1, 0));
  };

  /**
   * Soumet le formulaire.
   *
   * @param event Événement submit.
   */
  const onSubmit = async (event: FormEvent) => {
    event.preventDefault();
    if (!token || busy || done) {
      return;
    }

    if (isWizard && currentSection) {
      const sectionError = validateSection(currentSection, answers);
      if (sectionError) {
        setError(sectionError);
        return;
      }
    }

    setBusy(true);
    setError(null);
    try {
      await submitGuestInviteForm(token, answers);
      setDone(true);
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'Envoi impossible.');
    } finally {
      setBusy(false);
    }
  };

  if (loading) {
    return (
      <div className="flex min-h-[50vh] items-center justify-center text-surface-600">
        <Loader2 className="h-6 w-6 animate-spin" />
      </div>
    );
  }

  if (error && !data) {
    return (
      <div className="mx-auto max-w-lg px-4 py-16 text-center">
        <p className="text-burgundy-800 dark:text-burgundy-300">{error}</p>
      </div>
    );
  }

  if (!data) {
    return null;
  }

  const form = data.form;

  return (
    <div style={cssVars} className="min-h-screen bg-surface-50 pb-16 dark:bg-surface-950">
      {form.design.banner_url ? (
        <div
          className="h-48 w-full bg-cover bg-center sm:h-64"
          style={{ backgroundImage: `url(${form.design.banner_url})` }}
        />
      ) : (
        <div
          className="h-32 w-full sm:h-40"
          style={{ background: `linear-gradient(135deg, var(--guest-primary), var(--guest-accent))` }}
        />
      )}

      <div className="mx-auto max-w-3xl px-4 -mt-10">
        <div
          className="bg-white p-6 shadow-lg dark:bg-surface-900 sm:p-8"
          style={{ borderRadius: 'var(--guest-radius)' }}
        >
          <p className="text-xs font-semibold uppercase tracking-wider" style={{ color: 'var(--guest-accent)' }}>
            {data.project.title ?? 'Accueil CMP'}
          </p>
          <div className="mt-3 flex items-start gap-4">
            {data.pastor.photo_url ? (
              <img
                src={data.pastor.photo_url}
                alt={data.pastor.full_name}
                className="h-16 w-16 rounded-full object-cover ring-2 ring-orange-300 sm:h-20 sm:w-20"
              />
            ) : null}
            <h1 className="text-2xl font-bold text-surface-900 dark:text-white sm:text-3xl">{data.headline}</h1>
          </div>
          {form.intro_html ? (
            <div
              className="prose prose-sm mt-4 max-w-none text-surface-700 dark:prose-invert"
              dangerouslySetInnerHTML={{ __html: form.intro_html }}
            />
          ) : null}

          {done ? (
            <div className="mt-8 flex items-start gap-3 rounded-xl bg-emerald-50 p-4 text-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200">
              <CheckCircle2 className="mt-0.5 h-5 w-5 shrink-0" />
              <p>Merci. Votre fiche a bien été enregistrée. Nos départements préparent votre accueil.</p>
            </div>
          ) : (
            <form onSubmit={onSubmit} className="mt-8 space-y-8">
              {isWizard && sections.length > 0 ? (
                <div className="space-y-2">
                  <div className="flex items-center justify-between text-xs text-surface-500">
                    <span>
                      Étape {step + 1} / {sections.length}
                    </span>
                    <span>{Math.round(((step + 1) / sections.length) * 100)} %</span>
                  </div>
                  <div className="h-2 overflow-hidden rounded-full bg-surface-200 dark:bg-surface-800">
                    <div
                      className="h-full transition-all"
                      style={{
                        width: `${((step + 1) / sections.length) * 100}%`,
                        background: 'var(--guest-accent)',
                      }}
                    />
                  </div>
                </div>
              ) : null}

              {(isWizard ? (currentSection ? [currentSection] : []) : sections).map((section) => (
                <section key={section.id} className="space-y-4 border-t border-surface-200 pt-6 dark:border-surface-700">
                  <div>
                    <h2 className="text-lg font-semibold text-surface-900 dark:text-white">{section.title}</h2>
                    {section.description ? (
                      <p className="mt-1 text-sm text-surface-600 dark:text-surface-400">{section.description}</p>
                    ) : null}
                  </div>
                  {section.fields.map((field) => (
                    <FieldRenderer
                      key={field.key}
                      field={field}
                      value={answers[field.key]}
                      onChange={(value) => setAnswer(field.key, value)}
                    />
                  ))}
                </section>
              ))}

              {(!isWizard || isLastStep) && form.cmp_info_html ? (
                <aside
                  className="border border-surface-200 bg-surface-50 p-4 text-sm dark:border-surface-700 dark:bg-surface-800/50"
                  style={{ borderRadius: 'var(--guest-radius)' }}
                >
                  <h3 className="mb-2 font-semibold text-surface-800 dark:text-surface-100">Informations CMP</h3>
                  <div
                    className="prose prose-sm max-w-none dark:prose-invert"
                    dangerouslySetInnerHTML={{ __html: form.cmp_info_html }}
                  />
                </aside>
              ) : null}

              {error ? <p className="text-sm text-red-600">{error}</p> : null}

              <div className="flex flex-wrap items-center gap-3">
                {isWizard && step > 0 ? (
                  <button
                    type="button"
                    onClick={goPrev}
                    className="inline-flex items-center gap-2 border border-surface-300 px-4 py-2.5 text-sm font-medium text-surface-800 dark:border-surface-600 dark:text-surface-100"
                    style={{ borderRadius: 'var(--guest-radius)' }}
                  >
                    <ChevronLeft className="h-4 w-4" />
                    Précédent
                  </button>
                ) : null}

                {isWizard && !isLastStep ? (
                  <button
                    type="button"
                    onClick={goNext}
                    className="inline-flex items-center gap-2 px-6 py-3 text-sm font-semibold text-white"
                    style={{ background: 'var(--guest-primary)', borderRadius: 'var(--guest-radius)' }}
                  >
                    Suivant
                    <ChevronRight className="h-4 w-4" />
                  </button>
                ) : (
                  <button
                    type="submit"
                    disabled={busy}
                    className="inline-flex items-center gap-2 px-6 py-3 text-sm font-semibold text-white disabled:opacity-60"
                    style={{ background: 'var(--guest-primary)', borderRadius: 'var(--guest-radius)' }}
                  >
                    {busy ? <Loader2 className="h-4 w-4 animate-spin" /> : <Send className="h-4 w-4" />}
                    Envoyer la fiche
                  </button>
                )}
              </div>
            </form>
          )}
        </div>
      </div>
    </div>
  );
}

type FieldProps = {
  field: GuestInfoFormPublic['sections'][number]['fields'][number];
  value: unknown;
  onChange: (value: unknown) => void;
};

/**
 * Rendu d’un champ dynamique du formulaire d’accueil.
 *
 * @param props.field Définition du champ.
 * @param props.value Valeur courante.
 * @param props.onChange Callback de mise à jour.
 */
function FieldRenderer({ field, value, onChange }: FieldProps) {
  const inputClass =
    'w-full rounded-lg border border-surface-200 bg-white px-3 py-2 text-sm text-surface-900 focus:outline-none focus:ring-2 dark:border-surface-700 dark:bg-surface-900 dark:text-white';

  return (
    <div>
      <label className="mb-1.5 block text-sm font-medium text-surface-800 dark:text-surface-200">
        {field.label}
        {field.required ? <span className="text-red-600"> *</span> : null}
      </label>
      {field.help_text ? <p className="mb-2 text-xs text-surface-500">{field.help_text}</p> : null}

      {field.type === 'textarea' ? (
        <textarea
          className={inputClass}
          rows={3}
          value={typeof value === 'string' ? value : ''}
          onChange={(e) => onChange(e.target.value)}
          required={field.required}
        />
      ) : null}

      {field.type === 'text' || field.type === 'email' || field.type === 'phone' ? (
        <input
          type={field.type === 'email' ? 'email' : field.type === 'phone' ? 'tel' : 'text'}
          className={inputClass}
          value={typeof value === 'string' ? value : ''}
          onChange={(e) => onChange(e.target.value)}
          required={field.required}
        />
      ) : null}

      {field.type === 'yes_no' ? (
        <div className="flex gap-4">
          {['Oui', 'Non'].map((label) => (
            <label key={label} className="inline-flex items-center gap-2 text-sm">
              <input
                type="radio"
                name={field.key}
                checked={value === label}
                onChange={() => onChange(label)}
                required={field.required}
              />
              {label}
            </label>
          ))}
        </div>
      ) : null}

      {field.type === 'checkbox_group' ? (
        <div className="grid gap-2 sm:grid-cols-2">
          {Object.entries((field.options?.choices as Record<string, string> | undefined) ?? {}).map(([k, label]) => (
            <label key={k} className="inline-flex items-center gap-2 text-sm">
              <input
                type="checkbox"
                checked={Array.isArray(value) && (value as string[]).includes(k)}
                onChange={() => onChange(toggleListValue(value, k))}
              />
              {label}
            </label>
          ))}
        </div>
      ) : null}

      {field.type === 'repeater_names' ? (
        <textarea
          className={inputClass}
          rows={4}
          placeholder="Un nom par ligne"
          value={Array.isArray(value) ? (value as string[]).join('\n') : typeof value === 'string' ? value : ''}
          onChange={(e) =>
            onChange(
              e.target.value
                .split('\n')
                .map((line) => line.trim())
                .filter(Boolean),
            )
          }
          required={field.required}
        />
      ) : null}

      {field.type === 'food_grid' ? (
        <div className="space-y-3">
          {((field.options?.rows as Array<{ type: string; items: string[] }> | undefined) ?? []).map((row) => (
            <div key={row.type}>
              <p className="mb-1 text-xs font-semibold uppercase tracking-wide text-surface-500">{row.type}</p>
              <div className="flex flex-wrap gap-2">
                {row.items.map((item) => {
                  const selected = Array.isArray(value) && (value as string[]).includes(`${row.type}:${item}`);
                  return (
                    <button
                      key={item}
                      type="button"
                      onClick={() => onChange(toggleListValue(value, `${row.type}:${item}`))}
                      className={`rounded-full border px-3 py-1 text-xs ${
                        selected
                          ? 'border-transparent text-white'
                          : 'border-surface-300 text-surface-700 dark:border-surface-600 dark:text-surface-200'
                      }`}
                      style={selected ? { background: 'var(--guest-accent)' } : undefined}
                    >
                      {item}
                    </button>
                  );
                })}
              </div>
            </div>
          ))}
        </div>
      ) : null}
    </div>
  );
}
