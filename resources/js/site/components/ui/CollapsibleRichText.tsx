import { useLayoutEffect, useRef, useState } from 'react';
import { cn } from '../../lib/utils';

interface CollapsibleRichTextProps {
  /** HTML servi par l’API (corps de prédication). */
  html: string;
  /** Hauteur max (px) avant troncature. */
  collapsedMaxPx?: number;
  /** Classes du conteneur prose. */
  className?: string;
}

/**
 * Affiche un HTML riche avec limite de hauteur et bascule « Voir plus / Voir moins ».
 */
export default function CollapsibleRichText({ html, collapsedMaxPx = 280, className }: CollapsibleRichTextProps) {
  const innerRef = useRef<HTMLDivElement>(null);
  const [expanded, setExpanded] = useState(false);
  const [overflows, setOverflows] = useState(false);

  useLayoutEffect(() => {
    const el = innerRef.current;
    if (!el) {
      return;
    }

    const measure = () => {
      if (expanded) {
        setOverflows(false);

        return;
      }

      setOverflows(el.scrollHeight > collapsedMaxPx + 4);
    };

    measure();

    const observer = new ResizeObserver(measure);

    observer.observe(el);

    return () => observer.disconnect();
  }, [html, collapsedMaxPx, expanded]);

  const showToggle = overflows || expanded;

  return (
    <div className={cn('relative', className)}>
      <div
        className={cn(
          !expanded && overflows ? 'relative overflow-hidden' : '',
          'transition-[max-height] duration-300',
        )}
        style={
          !expanded && overflows ? { maxHeight: `${String(collapsedMaxPx)}px` } : undefined
        }
      >
        <div
          ref={innerRef}
          className={cn(
            'prose prose-neutral max-w-none text-surface-800 dark:prose-invert [&_a]:break-words [&_iframe]:aspect-video [&_iframe]:w-full [&_img]:rounded-xl',
          )}
          dangerouslySetInnerHTML={{ __html: html }}
        />
        {!expanded && overflows ? (
          <div
            className="pointer-events-none absolute inset-x-0 bottom-0 h-20 bg-gradient-to-t from-white to-transparent dark:from-surface-900"
            aria-hidden
          />
        ) : null}
      </div>

      {showToggle ? (
        <button
          type="button"
          onClick={() => setExpanded((value) => !value)}
          className="mt-4 text-sm font-semibold text-burgundy-800 hover:text-burgundy-900 dark:text-burgundy-300 dark:hover:text-burgundy-200"
        >
          {expanded ? 'Voir moins' : 'Voir plus'}
        </button>
      ) : null}
    </div>
  );
}
