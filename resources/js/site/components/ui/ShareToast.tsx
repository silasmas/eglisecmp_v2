import { AnimatePresence, motion } from 'framer-motion';

/**
 * Toast éphémère pour confirmer une action (ex. lien copié).
 */
export default function ShareToast({ message, visible }: { message: string; visible: boolean }) {
  return (
    <AnimatePresence>
      {visible ? (
        <motion.div
          role="status"
          aria-live="polite"
          initial={{ opacity: 0, y: 16, scale: 0.96 }}
          animate={{ opacity: 1, y: 0, scale: 1 }}
          exit={{ opacity: 0, y: 10, scale: 0.96 }}
          transition={{ duration: 0.22, ease: [0.22, 1, 0.36, 1] }}
          className="pointer-events-none fixed bottom-8 left-1/2 z-[200] -translate-x-1/2 rounded-full bg-surface-900 px-5 py-2.5 text-sm font-semibold text-white shadow-xl ring-1 ring-white/10"
        >
          {message}
        </motion.div>
      ) : null}
    </AnimatePresence>
  );
}
