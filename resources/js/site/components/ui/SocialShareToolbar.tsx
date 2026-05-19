import { useCallback, useState } from 'react';
import { Copy, Facebook, Linkedin, Share2 } from 'lucide-react';
import { cn } from '../../lib/utils';
import { buildShareUrl } from '../../lib/share';

export interface SocialShareToolbarProps {
  /** Titre du contenu partagé. */
  title: string;
  /** Texte d’accompagnement (optionnel). */
  description?: string;
  /** Chemin relatif sur le site (ex. `/teachings/message/12`). */
  sharePath: string;
  /** Affichage compact (icônes seules). */
  compact?: boolean;
  className?: string;
}

/** Icône X (anciennement Twitter) pour la barre de partage. */
function IconX({ className }: { className?: string }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="currentColor" aria-hidden>
      <path d="M17.782 3h3.069l-6.738 8.069L21 21h-5.957l-4.734-7.069L6.086 21H3l7.086-9.069L3 3h6.069l4.391 7.069L17.782 3z" />
    </svg>
  );
}

/** Logo WhatsApp officiel stylisé. */
function IconWhatsApp({ className }: { className?: string }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="currentColor" aria-hidden>
      <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.032-1.378l-.361-.214-3.741.982.998-3.648-.235-.372a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.884 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
    </svg>
  );
}

const BRAND_BTN =
  'inline-flex items-center justify-center rounded-full p-2 text-white shadow-sm transition hover:brightness-110 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-white/70';

/** Barre de partage (réseaux aux couleurs dédiées, Web Share API, copier le lien). */
export default function SocialShareToolbar({
  title,
  description,
  sharePath,
  compact = false,
  className,
}: SocialShareToolbarProps) {
  const [copied, setCopied] = useState(false);
  const url = buildShareUrl(sharePath);
  const text = description && description.trim() !== '' ? `${title} — ${description}` : title;

  const shareNative = useCallback(async () => {
    if (typeof navigator === 'undefined' || !navigator.share) {
      return false;
    }

    try {
      await navigator.share({ title, text, url });
      return true;
    } catch {
      return false;
    }
  }, [title, text, url]);

  const copyLink = useCallback(async () => {
    try {
      await navigator.clipboard.writeText(url);
      setCopied(true);
      window.setTimeout(() => setCopied(false), 2000);
    } catch {
      setCopied(false);
    }
  }, [url]);

  const links = [
    {
      label: 'Facebook',
      BrandIcon: Facebook,
      href: `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`,
      className: 'bg-[#1877F2] hover:bg-[#166FE5]',
    },
    {
      label: 'X',
      BrandIcon: IconX,
      href: `https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(url)}`,
      className: 'bg-black hover:bg-neutral-900',
    },
    {
      label: 'LinkedIn',
      BrandIcon: Linkedin,
      href: `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(url)}`,
      className: 'bg-[#0A66C2] hover:bg-[#095faa]',
    },
    {
      label: 'WhatsApp',
      BrandIcon: IconWhatsApp,
      href: `https://wa.me/?text=${encodeURIComponent(`${text} ${url}`)}`,
      className: 'bg-[#25D366] hover:bg-[#20bd5a]',
    },
  ];

  return (
    <div
      className={cn(
        'flex flex-wrap items-center gap-2',
        compact ? '' : 'rounded-2xl border border-surface-200 bg-surface-50/80 px-3 py-2 dark:border-surface-700 dark:bg-surface-900/50',
        className,
      )}
    >
      <span
        className={cn(
          'text-[11px] font-semibold uppercase tracking-wide text-surface-500 dark:text-surface-400',
          compact && 'sr-only',
        )}
      >
        Partager
      </span>

      <button
        type="button"
        onClick={() => void shareNative()}
        className={cn(
          'inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs font-semibold transition hover:bg-surface-50 dark:hover:bg-surface-800',
          'border-surface-300 bg-surface-800 text-white dark:border-surface-600 dark:bg-surface-950',
        )}
        aria-label="Partager via votre appareil"
      >
        <Share2 className="h-3.5 w-3.5 shrink-0" aria-hidden />
        {!compact ? 'Partager…' : null}
      </button>

      {links.map((item) => (
        <a
          key={item.label}
          href={item.href}
          target="_blank"
          rel="noopener noreferrer"
          className={cn(
            BRAND_BTN,
            compact ? 'h-9 w-9 shrink-0' : 'gap-1.5 px-3 py-1.5 text-xs font-medium',
            item.className,
          )}
          aria-label={`Partager sur ${item.label}`}
        >
          <item.BrandIcon className="h-3.5 w-3.5 shrink-0 text-white" aria-hidden />
          {!compact ? <span>{item.label}</span> : null}
        </a>
      ))}

      <button
        type="button"
        onClick={() => void copyLink()}
        className="inline-flex items-center gap-1.5 rounded-full border border-surface-600 bg-surface-700 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-surface-600"
        aria-label="Copier le lien"
      >
        <Copy className="h-3.5 w-3.5" aria-hidden />
        {copied ? 'Copié !' : !compact ? 'Copier le lien' : null}
      </button>
    </div>
  );
}
