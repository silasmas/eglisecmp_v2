import { cn } from '../../lib/utils';

type AlertSubscribeCheckboxesProps = {
  notifyLive: boolean;
  notifyEvents: boolean;
  onNotifyLiveChange: (value: boolean) => void;
  onNotifyEventsChange: (value: boolean) => void;
  className?: string;
  compact?: boolean;
};

/**
 * Cases à cocher opt-in pour les alertes live YouTube et événements CMP.
 */
export default function AlertSubscribeCheckboxes({
  notifyLive,
  notifyEvents,
  onNotifyLiveChange,
  onNotifyEventsChange,
  className,
  compact = false,
}: AlertSubscribeCheckboxesProps) {
  return (
    <fieldset className={cn('space-y-2 rounded-lg border border-surface-200 bg-surface-50/80 p-3 dark:border-surface-700 dark:bg-surface-800/50', className)}>
      <legend className={cn('font-semibold text-surface-800 dark:text-surface-200', compact ? 'text-xs' : 'text-sm')}>
        Alertes (optionnel)
      </legend>
      <p className={cn('text-surface-600 dark:text-surface-400', compact ? 'text-[11px]' : 'text-xs')}>
        Prévenez-moi par e-mail ou WhatsApp/SMS lorsque la chaîne est en direct ou qu’un événement approche.
      </p>
      <label className="flex items-start gap-2 text-sm">
        <input
          type="checkbox"
          checked={notifyLive}
          onChange={(e) => onNotifyLiveChange(e.target.checked)}
          className="mt-0.5 rounded border-surface-300"
        />
        <span>Live YouTube de la chaîne CMP</span>
      </label>
      <label className="flex items-start gap-2 text-sm">
        <input
          type="checkbox"
          checked={notifyEvents}
          onChange={(e) => onNotifyEventsChange(e.target.checked)}
          className="mt-0.5 rounded border-surface-300"
        />
        <span>Événements et célébrations (rappel, début, mise en avant)</span>
      </label>
    </fieldset>
  );
}
