import { X } from 'lucide-react';
import type { YoutubeLivePayload } from '../../data/types';

type YoutubeLiveModalProps = {
  open: boolean;
  live: YoutubeLivePayload | null;
  onClose: () => void;
};

/**
 * Popup d’accueil lorsque la chaîne YouTube est en direct.
 */
export default function YoutubeLiveModal({ open, live, onClose }: YoutubeLiveModalProps) {
  if (!open || live === null) {
    return null;
  }

  return (
    <div className="fixed inset-0 z-[190] flex items-center justify-center p-4" role="dialog" aria-modal="true">
      <button type="button" className="absolute inset-0 bg-black/70" aria-label="Fermer" onClick={onClose} />
      <div className="relative z-10 w-full max-w-3xl overflow-hidden rounded-2xl bg-black shadow-2xl">
        <div className="flex items-center justify-between gap-3 border-b border-white/10 px-4 py-3">
          <div className="flex items-center gap-2">
            <span className="inline-flex items-center gap-1.5 rounded-full bg-red-600 px-2.5 py-0.5 text-xs font-bold uppercase text-white">
              <span className="h-2 w-2 animate-pulse rounded-full bg-white" />
              Live
            </span>
            <p className="line-clamp-1 text-sm font-semibold text-white">{live.title}</p>
          </div>
          <button type="button" onClick={onClose} className="rounded-lg p-1 text-white/80 hover:bg-white/10" aria-label="Fermer">
            <X className="h-5 w-5" />
          </button>
        </div>
        <div className="aspect-video w-full bg-black">
          <iframe
            title={live.title}
            src={live.embedUrl}
            className="h-full w-full"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
            allowFullScreen
          />
        </div>
        <div className="flex flex-wrap items-center justify-between gap-3 bg-surface-900 px-4 py-3">
          <p className="text-sm text-white/80">Rejoignez le culte en direct sur YouTube</p>
          <a
            href={live.watchUrl}
            target="_blank"
            rel="noopener noreferrer"
            className="text-sm font-semibold text-gold-400 underline hover:text-gold-300"
          >
            Ouvrir sur YouTube
          </a>
        </div>
      </div>
    </div>
  );
}
