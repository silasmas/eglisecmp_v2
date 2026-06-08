import { cn } from '../../lib/utils';

const MAX_VISIBLE = 5;

type BundaEditionFilterProps = {
  /** Années uniques, tri décroissant recommandé. */
  years: number[];
  selectedYear: number | 'all';
  onSelect: (year: number | 'all') => void;
};

/**
 * Filtre par année : 5 dernières en pastilles, le reste dans une liste déroulante (sans doublon).
 *
 * @param years Liste d’années distinctes.
 * @param selectedYear Année sélectionnée ou « all ».
 * @param onSelect Callback au changement de filtre.
 */
export default function BundaEditionFilter({ years, selectedYear, onSelect }: BundaEditionFilterProps) {
  if (years.length === 0) {
    return null;
  }

  const sorted = [...years].sort((a, b) => b - a);
  const visible = sorted.slice(0, MAX_VISIBLE);
  const older = sorted.slice(MAX_VISIBLE);
  const dropdownValue =
    selectedYear === 'all' || visible.includes(selectedYear as number) ? '' : String(selectedYear);

  return (
    <div className="flex flex-wrap items-center justify-center gap-2">
      <button
        type="button"
        onClick={() => onSelect('all')}
        className={cn(
          'rounded-full border px-4 py-2 text-sm font-semibold transition',
          selectedYear === 'all'
            ? 'border-burgundy-700 bg-burgundy-800 text-white'
            : 'border-surface-200 bg-white text-surface-700 hover:border-burgundy-200',
        )}
      >
        Toutes
      </button>
      {visible.map((year) => (
        <button
          key={year}
          type="button"
          onClick={() => onSelect(year)}
          className={cn(
            'rounded-full border px-4 py-2 text-sm font-semibold transition',
            selectedYear === year
              ? 'border-burgundy-700 bg-burgundy-800 text-white'
              : 'border-surface-200 bg-white text-surface-700 hover:border-burgundy-200',
          )}
        >
          {year}
        </button>
      ))}
      {older.length > 0 ? (
        <label className="inline-flex items-center gap-2 rounded-full border border-surface-200 bg-white px-3 py-1.5 text-sm">
          <span className="text-surface-500">Plus anciennes</span>
          <select
            className="bg-transparent font-semibold text-surface-800 outline-none"
            value={dropdownValue}
            onChange={(e) => {
              const value = e.target.value;
              if (value !== '') {
                onSelect(Number(value));
              }
            }}
          >
            <option value="">Choisir…</option>
            {older.map((year) => (
              <option key={year} value={year}>
                {year}
              </option>
            ))}
          </select>
        </label>
      ) : null}
    </div>
  );
}
