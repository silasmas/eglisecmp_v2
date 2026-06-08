import { LayoutGrid, List } from 'lucide-react';
import { cn } from '../../lib/utils';
import BundaEditionFilter from './BundaEditionFilter';

export type BundaArchiveViewMode = 'grid' | 'list';

type BundaArchivesToolbarProps = {
  years: number[];
  selectedYear: number | 'all';
  onSelectYear: (year: number | 'all') => void;
  viewMode: BundaArchiveViewMode;
  onViewModeChange: (mode: BundaArchiveViewMode) => void;
};

/**
 * Barre d’outils archives Bunda : mode grille/liste et filtres par année, centrés sur une ligne.
 *
 * @param years Années uniques triées (décroissant).
 * @param selectedYear Année active ou « toutes ».
 * @param onSelectYear Callback de changement de filtre.
 * @param viewMode Mode d’affichage courant.
 * @param onViewModeChange Callback de changement de mode.
 */
export default function BundaArchivesToolbar({
  years,
  selectedYear,
  onSelectYear,
  viewMode,
  onViewModeChange,
}: BundaArchivesToolbarProps) {
  return (
    <div className="flex flex-col items-center justify-center gap-4 sm:flex-row sm:flex-wrap">
      <div className="inline-flex items-center rounded-full border border-surface-200 bg-white p-1 shadow-sm">
        <button
          type="button"
          onClick={() => onViewModeChange('grid')}
          className={cn(
            'inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold transition',
            viewMode === 'grid'
              ? 'bg-burgundy-800 text-white shadow-sm'
              : 'text-surface-600 hover:text-surface-900',
          )}
          aria-pressed={viewMode === 'grid'}
        >
          <LayoutGrid className="h-4 w-4" aria-hidden />
          Grille
        </button>
        <button
          type="button"
          onClick={() => onViewModeChange('list')}
          className={cn(
            'inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold transition',
            viewMode === 'list'
              ? 'bg-burgundy-800 text-white shadow-sm'
              : 'text-surface-600 hover:text-surface-900',
          )}
          aria-pressed={viewMode === 'list'}
        >
          <List className="h-4 w-4" aria-hidden />
          Liste
        </button>
      </div>

      {years.length > 0 ? (
        <BundaEditionFilter years={years} selectedYear={selectedYear} onSelect={onSelectYear} />
      ) : null}
    </div>
  );
}
