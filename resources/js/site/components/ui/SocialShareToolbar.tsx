import { useCallback, useEffect, useRef, useState } from 'react';
import { AnimatePresence, motion } from 'framer-motion';
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
  /** Affichage compact (icône seule sur le déclencheur). */
  compact?: boolean;
  /** `spread` : les RS apparaissent en ligne avec animation (style menu flottant). */
  menuStyle?: 'popover' | 'spread';
  /** Contraste du bouton principal sur fond sombre. */
  tone?: 'light' | 'dark';
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
  'inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-white shadow-md transition hover:brightness-110 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2';

/**
 * Bouton de partage unique : au clic, affiche les réseaux avec animation type menu flottant.
 */
export default function SocialShareToolbar({
  title,
  description,
  sharePath,
  compact = false,
  menuStyle = 'spread',
  tone = 'light',
  className,
}: SocialShareToolbarProps) {
  const [open, setOpen] = useState(false);
  const [copied, setCopied] = useState(false);
  const rootRef = useRef<HTMLDivElement>(null);
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

  useEffect(() => {
    if (!open) {
      return undefined;
    }

    const handlePointerDown = (event: MouseEvent) => {
      if (rootRef.current !== null && !rootRef.current.contains(event.target as Node)) {
        setOpen(false);
      }
    };

    const handleEscape = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        setOpen(false);
      }
    };

    document.addEventListener('mousedown', handlePointerDown);
    document.addEventListener('keydown', handleEscape);

    return () => {
      document.removeEventListener('mousedown', handlePointerDown);
      document.removeEventListener('keydown', handleEscape);
    };
  }, [open]);

  const links = [
    {
      key: 'device',
      label: 'Partager via l’appareil',
      className: 'bg-surface-700 hover:bg-surface-600',
      onClick: () => void shareNative(),
      BrandIcon: Share2,
    },
    {
      key: 'facebook',
      label: 'Facebook',
      className: 'bg-[#1877F2] hover:bg-[#166FE5]',
      href: `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`,
      BrandIcon: Facebook,
    },
    {
      key: 'x',
      label: 'X',
      className: 'bg-black hover:bg-neutral-900',
      href: `https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(url)}`,
      BrandIcon: IconX,
    },
    {
      key: 'linkedin',
      label: 'LinkedIn',
      className: 'bg-[#0A66C2] hover:bg-[#095faa]',
      href: `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(url)}`,
      BrandIcon: Linkedin,
    },
    {
      key: 'whatsapp',
      label: 'WhatsApp',
      className: 'bg-[#25D366] hover:bg-[#20bd5a]',
      href: `https://wa.me/?text=${encodeURIComponent(`${text} ${url}`)}`,
      BrandIcon: IconWhatsApp,
    },
    {
      key: 'copy',
      label: copied ? 'Lien copié' : 'Copier le lien',
      className: 'bg-surface-600 hover:bg-surface-500',
      onClick: () => void copyLink(),
      BrandIcon: Copy,
    },
  ];

  const triggerClass =
    tone === 'dark'
      ? 'border-white/20 bg-white/10 text-white hover:bg-white/20 focus-visible:ring-white/50'
      : 'border-surface-300 bg-surface-800 text-white hover:bg-surface-700 focus-visible:ring-burgundy-500';

  const menuItems = (
    <AnimatePresence>
      {open ? (
        <motion.div
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          exit={{ opacity: 0 }}
          transition={{ duration: 0.15 }}
          className={cn(
            menuStyle === 'spread'
              ? 'flex flex-wrap items-center gap-2'
              : 'absolute z-30 left-0 top-full mt-2 flex flex-wrap items-center gap-2 rounded-2xl border border-surface-200 bg-white p-2 shadow-lg dark:border-surface-600 dark:bg-surface-900',
          )}
          role="menu"
        >
          {links.map((item, index) => {
            const content = (
              <item.BrandIcon className="h-4 w-4 shrink-0 text-white" aria-hidden />
            );

            const motionProps = {
              initial: { opacity: 0, scale: 0.6, x: menuStyle === 'spread' ? -12 : 0, y: menuStyle === 'spread' ? 0 : 8 },
              animate: { opacity: 1, scale: 1, x: 0, y: 0 },
              exit: { opacity: 0, scale: 0.6, x: menuStyle === 'spread' ? -8 : 0 },
              transition: { duration: 0.2, delay: index * 0.04 },
            };

            if (item.href !== undefined) {
              return (
                <motion.a
                  key={item.key}
                  {...motionProps}
                  role="menuitem"
                  href={item.href}
                  target="_blank"
                  rel="noopener noreferrer"
                  className={cn(BRAND_BTN, item.className)}
                  aria-label={`Partager sur ${item.label}`}
                  onClick={() => setOpen(false)}
                >
                  {content}
                </motion.a>
              );
            }

            return (
              <motion.button
                key={item.key}
                {...motionProps}
                type="button"
                role="menuitem"
                className={cn(BRAND_BTN, item.className)}
                aria-label={item.label}
                onClick={() => {
                  item.onClick?.();
                  if (item.key !== 'copy') {
                    setOpen(false);
                  }
                }}
              >
                {content}
              </motion.button>
            );
          })}
        </motion.div>
      ) : null}
    </AnimatePresence>
  );

  return (
    <motion.div
      ref={rootRef}
      className={cn(
        menuStyle === 'spread' ? 'flex flex-wrap items-center gap-2' : 'relative inline-flex',
        className,
      )}
    >
      <motion.button
        type="button"
        whileTap={{ scale: 0.94 }}
        onClick={() => setOpen((previous) => !previous)}
        className={cn(
          'inline-flex items-center justify-center rounded-full border shadow-sm transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2',
          triggerClass,
          compact ? 'h-10 w-10' : 'gap-1.5 px-3.5 py-2 text-xs font-semibold',
        )}
        aria-expanded={open}
        aria-haspopup="true"
        aria-label={open ? 'Fermer les options de partage' : 'Partager'}
      >
        <Share2 className="h-4 w-4 shrink-0" aria-hidden />
        {!compact ? <span>Partager</span> : null}
      </motion.button>

      {menuItems}
    </motion.div>
  );
}
