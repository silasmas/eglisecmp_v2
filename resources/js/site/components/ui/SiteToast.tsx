import { AnimatePresence, motion } from 'framer-motion';

type SiteToastVariant = 'error' | 'success' | 'info';

type SiteToastProps = {
  message: string;
  visible: boolean;
  variant?: SiteToastVariant;
};

const variantClasses: Record<SiteToastVariant, string> = {
  error: 'bg-burgundy-900 text-white ring-burgundy-700/40',
  success: 'bg-emerald-800 text-white ring-emerald-600/40',
  info: 'bg-surface-900 text-white ring-white/10',
};

/**
 * Notification éphémère (toast) en bas de l’écran pour le site public.
 *
 * @param message Texte affiché.
 * @param visible Affiche ou masque le toast.
 * @param variant Style visuel (erreur, succès, info).
 */
export default function SiteToast({ message, visible, variant = 'info' }: SiteToastProps) {
  return (
    <AnimatePresence>
      {visible ? (
        <motion.div
          role="alert"
          aria-live="assertive"
          initial={{ opacity: 0, y: 16, scale: 0.96 }}
          animate={{ opacity: 1, y: 0, scale: 1 }}
          exit={{ opacity: 0, y: 10, scale: 0.96 }}
          transition={{ duration: 0.22, ease: [0.22, 1, 0.36, 1] }}
          className={`pointer-events-none fixed bottom-8 left-1/2 z-[200] max-w-[min(92vw,28rem)] -translate-x-1/2 rounded-2xl px-5 py-3 text-center text-sm font-semibold shadow-xl ring-1 ${variantClasses[variant]}`}
        >
          {message}
        </motion.div>
      ) : null}
    </AnimatePresence>
  );
}
