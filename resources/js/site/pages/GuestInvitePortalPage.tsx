import { useEffect, useMemo, useState, type ReactNode } from 'react';
import { useParams } from 'react-router-dom';
import { CalendarDays, Loader2, Shirt, Users, BookOpen } from 'lucide-react';
import { fetchGuestPortal, type GuestPortalResponse } from '../lib/siteApi';

/**
 * Portail personnalisé du pasteur invité (après soumission de la fiche).
 */
export default function GuestInvitePortalPage() {
  const { portalToken = '' } = useParams();
  const [data, setData] = useState<GuestPortalResponse | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let cancelled = false;
    setLoading(true);
    setError(null);
    fetchGuestPortal(portalToken)
      .then((payload) => {
        if (!cancelled) {
          setData(payload);
        }
      })
      .catch((err: unknown) => {
        if (!cancelled) {
          setError(err instanceof Error ? err.message : 'Impossible de charger le portail.');
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
  }, [portalToken]);

  const calendarMonth = useMemo(() => {
    if (!data?.assignments?.length) {
      return null;
    }
    const first = data.assignments[0]?.day_date;
    if (!first) {
      return null;
    }
    const d = new Date(`${first}T12:00:00`);
    return { year: d.getFullYear(), month: d.getMonth() };
  }, [data]);

  const assignmentMap = useMemo(() => {
    const map = new Map<string, GuestPortalResponse['assignments'][number][]>();
    for (const a of data?.assignments ?? []) {
      if (!a.day_date) {
        continue;
      }
      const list = map.get(a.day_date) ?? [];
      list.push(a);
      map.set(a.day_date, list);
    }
    return map;
  }, [data]);

  if (loading) {
    return (
      <div className="flex min-h-[50vh] items-center justify-center text-surface-600">
        <Loader2 className="mr-2 h-5 w-5 animate-spin" />
        Chargement du portail…
      </div>
    );
  }

  if (error || !data) {
    return (
      <div className="mx-auto max-w-xl px-4 py-16 text-center">
        <h1 className="text-2xl font-semibold text-surface-900 dark:text-white">Portail indisponible</h1>
        <p className="mt-3 text-surface-600 dark:text-surface-400">{error ?? 'Lien invalide ou expiré.'}</p>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-surface-50 dark:bg-surface-950">
      <header className="border-b border-[#7b1d3e]/30 bg-[#2b1a12] px-4 py-8 text-white">
        <div className="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-4">
          <div>
            <p className="text-sm uppercase tracking-wide text-[#f5c542]">{data.project.title}</p>
            <h1 className="mt-1 text-2xl font-bold md:text-3xl">Bienvenue {data.pastor.full_name}</h1>
            {data.pastor.church_name ? (
              <p className="mt-1 text-sm text-white/70">{data.pastor.church_name}</p>
            ) : null}
          </div>
          {data.pastor.photo_url ? (
            <img
              src={data.pastor.photo_url}
              alt={data.pastor.full_name}
              className="h-20 w-20 rounded-full object-cover ring-2 ring-[#f5c542]"
            />
          ) : null}
        </div>
      </header>

      <main className="mx-auto max-w-5xl space-y-12 px-4 py-10">
        <section>
          <SectionTitle icon={<Shirt className="h-5 w-5" />} title="Détails des tenues" />
          {data.outfits.length === 0 ? (
            <EmptyHint text="Aucune tenue publiée pour le moment." />
          ) : (
            <div className="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
              {data.outfits.map((outfit) => (
                <article
                  key={`${outfit.session_key}-${outfit.title}`}
                  className="overflow-hidden rounded-xl border border-surface-200 bg-white dark:border-surface-700 dark:bg-surface-900"
                >
                  {outfit.image_url ? (
                    <img src={outfit.image_url} alt={outfit.title} className="aspect-[3/4] w-full object-cover" />
                  ) : (
                    <div className="flex aspect-[3/4] items-center justify-center bg-surface-100 text-surface-400 dark:bg-surface-800">
                      Tenue
                    </div>
                  )}
                  <div className="p-3">
                    <p className="text-xs font-semibold uppercase tracking-wide text-[#7b1d3e]">{outfit.session_label}</p>
                    <h3 className="mt-1 font-medium text-surface-900 dark:text-white">{outfit.title}</h3>
                    {outfit.description ? (
                      <p className="mt-1 text-xs text-surface-600 dark:text-surface-400">{outfit.description}</p>
                    ) : null}
                  </div>
                </article>
              ))}
            </div>
          )}
        </section>

        <section>
          <SectionTitle icon={<Users className="h-5 w-5" />} title="L’équipe dédiée à votre service" />
          {data.team.length === 0 ? (
            <EmptyHint text="Aucune équipe assignée pour le moment." />
          ) : (
            <div className="mt-4 space-y-8">
              {data.team.map((group) => (
                <div key={group.title}>
                  <h3 className="text-lg font-semibold text-[#7b1d3e]">{group.title}</h3>
                  <div className="mt-4 grid gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5">
                    {group.members.map((member) => (
                      <div key={`${group.title}-${member.name}`} className="text-center">
                        {member.photo_url ? (
                          <img
                            src={member.photo_url}
                            alt={member.name}
                            className="mx-auto h-20 w-20 rounded-full object-cover"
                          />
                        ) : (
                          <div className="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-surface-200 text-sm font-semibold text-surface-600 dark:bg-surface-700">
                            {member.honorific}
                          </div>
                        )}
                        <p className="mt-2 text-sm font-medium text-surface-900 dark:text-white">
                          {member.honorific} {member.name}
                        </p>
                        {member.phone ? (
                          <a href={`tel:${member.phone}`} className="text-xs text-surface-500 hover:text-[#7b1d3e]">
                            {member.phone}
                          </a>
                        ) : null}
                      </div>
                    ))}
                  </div>
                </div>
              ))}
            </div>
          )}
        </section>

        <section>
          <SectionTitle icon={<CalendarDays className="h-5 w-5" />} title="Vos jours d’intervention" />
          {data.assignments.length === 0 || !calendarMonth ? (
            <EmptyHint text="Aucun jour d’intervention planifié." />
          ) : (
            <div className="mt-4 overflow-hidden rounded-xl border border-surface-200 bg-white dark:border-surface-700 dark:bg-surface-900">
              <MonthGrid year={calendarMonth.year} month={calendarMonth.month} assignmentMap={assignmentMap} />
              <div className="border-t border-surface-100 px-4 py-3 text-xs text-surface-500 dark:border-surface-800">
                {data.assignments.map((a) => (
                  <span key={`${a.day_date}-${a.label}`} className="mr-3 inline-flex items-center gap-1">
                    <span className="inline-block h-2.5 w-2.5 rounded-sm" style={{ background: a.color }} />
                    {a.day_date} — {a.label}
                    {a.location ? ` (${a.location})` : ''}
                  </span>
                ))}
              </div>
            </div>
          )}
        </section>

        <section>
          <SectionTitle icon={<BookOpen className="h-5 w-5" />} title="Liturgie des cultes" />
          {data.liturgy.length === 0 ? (
            <EmptyHint text="Liturgie non publiée." />
          ) : (
            <div className="mt-4 grid gap-6 md:grid-cols-2">
              {data.liturgy.map((session) => (
                <article
                  key={session.session_key + session.title}
                  className="rounded-xl border border-surface-200 bg-white p-4 dark:border-surface-700 dark:bg-surface-900"
                >
                  <h3 className="text-lg font-semibold text-[#7b1d3e]">{session.title}</h3>
                  {(session.starts_at_time || session.ends_at_time) && (
                    <p className="mt-1 text-xs text-surface-500">
                      {formatTime(session.starts_at_time)} — {formatTime(session.ends_at_time)}
                    </p>
                  )}
                  <ul className="mt-3 space-y-2 text-sm text-surface-700 dark:text-surface-300">
                    {session.items.map((item, idx) => (
                      <li key={`${session.title}-${idx}`}>
                        <span className="font-medium text-surface-900 dark:text-white">
                          {formatTime(item.starts_at_time)}
                          {item.ends_at_time ? ` - ${formatTime(item.ends_at_time)}` : ''}
                          {item.duration_minutes ? ` (${item.duration_minutes} min)` : ''}
                        </span>
                        {': '}
                        {item.label}
                      </li>
                    ))}
                  </ul>
                </article>
              ))}
            </div>
          )}
        </section>
      </main>
    </div>
  );
}

function SectionTitle({ icon, title }: { icon: ReactNode; title: string }) {
  return (
    <h2 className="flex items-center gap-2 text-xl font-semibold text-surface-900 dark:text-white">
      <span className="text-[#7b1d3e]">{icon}</span>
      {title}
    </h2>
  );
}

function EmptyHint({ text }: { text: string }) {
  return <p className="mt-3 text-sm text-surface-500">{text}</p>;
}

function formatTime(value: string | null): string {
  if (!value) {
    return '';
  }
  return value.slice(0, 5).replace(':', 'h');
}

/**
 * Grille calendrier mensuelle avec jours d’intervention surlignés.
 */
function MonthGrid({
  year,
  month,
  assignmentMap,
}: {
  year: number;
  month: number;
  assignmentMap: Map<string, GuestPortalResponse['assignments'][number][]>;
}) {
  const first = new Date(year, month, 1);
  const startPad = (first.getDay() + 6) % 7;
  const daysInMonth = new Date(year, month + 1, 0).getDate();
  const cells: Array<{ day: number | null; key: string }> = [];
  for (let i = 0; i < startPad; i++) {
    cells.push({ day: null, key: `pad-${i}` });
  }
  for (let d = 1; d <= daysInMonth; d++) {
    cells.push({ day: d, key: `d-${d}` });
  }

  const monthLabel = first.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' });

  return (
    <div className="p-4">
      <p className="mb-3 text-center text-sm font-semibold capitalize text-surface-800 dark:text-surface-100">
        {monthLabel}
      </p>
      <div className="grid grid-cols-7 gap-1 text-center text-[11px] font-semibold uppercase text-surface-500">
        {['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'].map((d) => (
          <div key={d}>{d}</div>
        ))}
      </div>
      <div className="mt-1 grid grid-cols-7 gap-1">
        {cells.map((cell) => {
          if (cell.day === null) {
            return <div key={cell.key} className="min-h-14 rounded bg-transparent" />;
          }
          const iso = `${year}-${String(month + 1).padStart(2, '0')}-${String(cell.day).padStart(2, '0')}`;
          const items = assignmentMap.get(iso) ?? [];
          return (
            <div
              key={cell.key}
              className="min-h-14 rounded border border-surface-100 p-1 text-xs dark:border-surface-800"
            >
              <div className="font-medium text-surface-700 dark:text-surface-200">{cell.day}</div>
              {items.map((item) => (
                <div
                  key={`${iso}-${item.label}`}
                  className="mt-0.5 truncate rounded px-1 py-0.5 text-[10px] font-semibold text-white"
                  style={{ background: item.color || '#7b1d3e' }}
                >
                  {item.label}
                </div>
              ))}
            </div>
          );
        })}
      </div>
    </div>
  );
}
