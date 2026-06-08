import { Bell } from 'lucide-react';
import { cn } from '../../lib/utils';

type BundaNotifyButtonProps = {
  onClick: () => void;
  className?: string;
  size?: 'md' | 'lg';
};

/**
 * Bouton rouge « Me prévenir » pour les alertes Bunda.
 *
 * @param onClick Ouvre la modale d’inscription.
 * @param className Classes CSS additionnelles.
 * @param size Taille visuelle (md ou lg imposant).
 */
export default function BundaNotifyButton({ onClick, className, size = 'lg' }: BundaNotifyButtonProps) {
  return (
    <button
      type="button"
      onClick={onClick}
      className={cn(
        'inline-flex items-center justify-center gap-2.5 rounded-2xl bg-red-700 font-bold text-white shadow-lg shadow-red-900/25 transition hover:bg-red-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-400 focus-visible:ring-offset-2',
        size === 'lg' ? 'px-8 py-4 text-base sm:text-lg' : 'px-6 py-3 text-sm',
        className,
      )}
    >
      <Bell className={size === 'lg' ? 'h-5 w-5' : 'h-4 w-4'} aria-hidden />
      Me prévenir
    </button>
  );
}
