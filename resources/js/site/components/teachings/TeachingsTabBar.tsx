import { Link, useSearchParams } from 'react-router-dom';
import clsx from 'clsx';
import type { TeachingsTab } from '../../data/types';

const tabs: { id: TeachingsTab; label: string }[] = [
  { id: 'sermons', label: 'Messages' },
  { id: 'meditations', label: 'Méditations' },
  { id: 'playlists', label: 'Playlists' },
];

/**
 * Onglets de la page Enseignements (synchronisés avec `?tab=`).
 */
export default function TeachingsTabBar() {
  const [searchParams] = useSearchParams();
  const active = (searchParams.get('tab') as TeachingsTab) || 'sermons';
  const resolved = tabs.some((tab) => tab.id === active) ? active : 'sermons';

  return (
    <nav
      className="mb-10 flex flex-wrap gap-2 border-b border-surface-200 pb-1"
      aria-label="Sections enseignements"
    >
      {tabs.map((tab) => {
        const isActive = tab.id === resolved;

        return (
          <Link
            key={tab.id}
            to={`/teachings?tab=${tab.id}`}
            className={clsx(
              'rounded-t-xl px-4 py-2.5 text-sm font-semibold transition-colors',
              isActive
                ? 'bg-burgundy-700 text-white shadow-sm'
                : 'text-surface-600 hover:bg-surface-100 hover:text-surface-900',
            )}
            aria-current={isActive ? 'page' : undefined}
          >
            {tab.label}
          </Link>
        );
      })}
    </nav>
  );
}

export function resolveTeachingsTab(searchParams: URLSearchParams): TeachingsTab {
  const raw = searchParams.get('tab');

  if (raw === 'meditations' || raw === 'playlists' || raw === 'sermons') {
    return raw;
  }

  return 'sermons';
}
