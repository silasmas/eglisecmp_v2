import { useState } from 'react';
import { cn } from '../../lib/utils';

type ExpandableTextProps = {
  text: string;
  maxChars?: number;
  className?: string;
};

/**
 * Texte tronqué avec liens « Voir plus » / « Voir moins ».
 */
export default function ExpandableText({ text, maxChars = 220, className }: ExpandableTextProps) {
  const [expanded, setExpanded] = useState(false);
  const needsToggle = text.length > maxChars;
  const display = expanded || !needsToggle ? text : `${text.slice(0, maxChars).trim()}…`;

  if (text === '') {
    return null;
  }

  return (
    <div className={cn('text-sm leading-relaxed text-surface-800', className)}>
      <p className="whitespace-pre-wrap">{display}</p>
      {needsToggle ? (
        <button
          type="button"
          onClick={(e) => {
            e.stopPropagation();
            setExpanded((v) => !v);
          }}
          className="mt-1 text-xs font-semibold text-[#950000] underline"
        >
          {expanded ? 'Voir moins' : 'Voir plus'}
        </button>
      ) : null}
    </div>
  );
}
