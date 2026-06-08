import { useEffect } from 'react';
import { AnimatePresence, motion } from 'framer-motion';
import { X } from 'lucide-react';
import AlertSubscribeForm from './AlertSubscribeForm';
import type { AlertSubscribeSource } from './AlertSubscribeForm';

type AlertSubscribeModalProps = {
  open: boolean;
  onClose: () => void;
  source: AlertSubscribeSource;
  title: string;
  description?: string;
  defaultNotifyLive?: boolean;
  defaultNotifyEvents?: boolean;
};

/**
 * Modale d’abonnement aux alertes (événements, Bunda, programmes hebdomadaires, live).
 *
 * @param open Affiche ou masque la modale.
 * @param onClose Callback de fermeture.
 * @param source Origine de l’inscription (analytics / API).
 * @param title Titre affiché dans le formulaire.
 * @param description Sous-titre optionnel sous le titre de la modale.
 * @param defaultNotifyLive Case live cochée par défaut.
 * @param defaultNotifyEvents Case événements cochée par défaut.
 */
export default function AlertSubscribeModal({
  open,
  onClose,
  source,
  title,
  description,
  defaultNotifyLive = true,
  defaultNotifyEvents = true,
}: AlertSubscribeModalProps) {
  useEffect(() => {
    if (!open) {
      return undefined;
    }

    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';

    return () => {
      document.body.style.overflow = previousOverflow;
    };
  }, [open]);

  return (
    <AnimatePresence>
      {open ? (
        <motion.div
          key="alert-subscribe-backdrop"
          className="fixed inset-0 z-[160] flex items-center justify-center p-4 sm:p-6"
          role="dialog"
          aria-modal="true"
          aria-labelledby="alert-subscribe-modal-title"
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          exit={{ opacity: 0 }}
          transition={{ duration: 0.2 }}
          onClick={onClose}
          onKeyDown={(event) => {
            if (event.key === 'Escape') {
              onClose();
            }
          }}
        >
          <div className="absolute inset-0 bg-surface-950/70 backdrop-blur-sm" aria-hidden />

          <motion.div
            key="alert-subscribe-panel"
            className="relative z-10 w-full max-w-lg"
            initial={{ opacity: 0, scale: 0.94, y: 16 }}
            animate={{ opacity: 1, scale: 1, y: 0 }}
            exit={{ opacity: 0, scale: 0.96, y: 8 }}
            transition={{ duration: 0.28, ease: [0.22, 1, 0.36, 1] }}
            onClick={(event) => event.stopPropagation()}
          >
            <button
              type="button"
              onClick={onClose}
              className="absolute -top-3 -right-3 z-20 flex h-9 w-9 items-center justify-center rounded-full bg-white text-surface-700 shadow-lg ring-1 ring-black/5 transition hover:bg-surface-50"
              aria-label="Fermer"
            >
              <X className="h-4 w-4" />
            </button>

            {description !== undefined && description.trim() !== '' ? (
              <p id="alert-subscribe-modal-title" className="sr-only">
                {title} — {description}
              </p>
            ) : (
              <p id="alert-subscribe-modal-title" className="sr-only">
                {title}
              </p>
            )}

            <AlertSubscribeForm
              key={`${source}-${open ? 'open' : 'closed'}`}
              source={source}
              title={title}
              variant="embedded"
              defaultNotifyLive={defaultNotifyLive}
              defaultNotifyEvents={defaultNotifyEvents}
              onSuccess={onClose}
            />
          </motion.div>
        </motion.div>
      ) : null}
    </AnimatePresence>
  );
}
