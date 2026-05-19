import { useCallback, useState } from 'react';
import { Copy, Share2 } from 'lucide-react';
import { resolvePublicImage } from '../../lib/placeholderImage';

type DailyReadingShareProps = {
  reference: string;
  text: string;
  imageUrl?: string;
  className?: string;
};

/**
 * Actions de partage pour la lecture du jour (natif, WhatsApp, Facebook, copie du texte).
 */
export default function DailyReadingShare({
  reference,
  text,
  imageUrl,
  className = '',
}: DailyReadingShareProps) {
  const [copied, setCopied] = useState(false);

  const shareBody = [reference, text, typeof window !== 'undefined' ? window.location.origin : '']
    .filter((part) => part.trim() !== '')
    .join('\n\n');

  const encodedText = encodeURIComponent(shareBody);
  const pageUrl = typeof window !== 'undefined' ? window.location.href : '';
  const encodedUrl = encodeURIComponent(pageUrl);

  const handleNativeShare = useCallback(async () => {
    if (typeof navigator === 'undefined' || !navigator.share) {
      return;
    }

    try {
      await navigator.share({
        title: 'Lecture du jour — CMP Philadelphie',
        text: shareBody,
        url: pageUrl,
      });
    } catch {
      /* annulation utilisateur */
    }
  }, [shareBody, pageUrl]);

  const handleCopy = useCallback(async () => {
    try {
      await navigator.clipboard.writeText(shareBody);
      setCopied(true);
      window.setTimeout(() => setCopied(false), 2000);
    } catch {
      /* ignore */
    }
  }, [shareBody]);

  const whatsappHref = `https://wa.me/?text=${encodedText}`;
  const facebookHref = `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}&quote=${encodedText}`;

  return (
    <div className={`flex flex-wrap items-center gap-2 ${className}`.trim()}>
      {typeof navigator !== 'undefined' && navigator.share ? (
        <button
          type="button"
          onClick={() => void handleNativeShare()}
          className="inline-flex items-center gap-1.5 rounded-full border border-surface-200 bg-white px-3 py-1.5 text-[11px] font-semibold text-surface-700 transition hover:border-burgundy-300 hover:bg-burgundy-50"
        >
          <Share2 className="h-3.5 w-3.5" />
          Partager
        </button>
      ) : null}
      <a
        href={whatsappHref}
        target="_blank"
        rel="noopener noreferrer"
        className="inline-flex items-center rounded-full border border-surface-200 bg-white px-3 py-1.5 text-[11px] font-semibold text-surface-700 transition hover:border-emerald-400 hover:bg-emerald-50"
      >
        WhatsApp
      </a>
      <a
        href={facebookHref}
        target="_blank"
        rel="noopener noreferrer"
        className="inline-flex items-center rounded-full border border-surface-200 bg-white px-3 py-1.5 text-[11px] font-semibold text-surface-700 transition hover:border-blue-400 hover:bg-blue-50"
      >
        Facebook
      </a>
      <button
        type="button"
        onClick={() => void handleCopy()}
        className="inline-flex items-center gap-1.5 rounded-full border border-surface-200 bg-white px-3 py-1.5 text-[11px] font-semibold text-surface-700 transition hover:border-surface-400"
      >
        <Copy className="h-3.5 w-3.5" />
        {copied ? 'Copié' : 'Copier le texte'}
      </button>
      {imageUrl ? (
        <a
          href={resolvePublicImage(imageUrl)}
          target="_blank"
          rel="noopener noreferrer"
          className="inline-flex items-center rounded-full border border-surface-200 bg-white px-3 py-1.5 text-[11px] font-semibold text-surface-700 transition hover:border-gold-400"
        >
          Télécharger l&apos;image
        </a>
      ) : null}
    </div>
  );
}

