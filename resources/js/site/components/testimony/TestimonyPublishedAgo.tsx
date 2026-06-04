import { useEffect, useState } from 'react';
import { formatTestimonyPublishedAgo } from '../../lib/formatRelativeTimeFr';
import { cn } from '../../lib/utils';

type TestimonyPublishedAgoProps = {
  publishedAt?: string;
  className?: string;
};

/**
 * Affiche le délai depuis la validation du témoignage (1 min → 1 jour).
 */
export default function TestimonyPublishedAgo({ publishedAt, className }: TestimonyPublishedAgoProps) {
  const [label, setLabel] = useState<string | null>(() =>
    publishedAt !== undefined && publishedAt !== '' ? formatTestimonyPublishedAgo(publishedAt) : null,
  );

  useEffect(() => {
    if (publishedAt === undefined || publishedAt === '') {
      setLabel(null);
      return undefined;
    }

    const refresh = () => {
      setLabel(formatTestimonyPublishedAgo(publishedAt));
    };

    refresh();
    const interval = window.setInterval(refresh, 60_000);

    return () => window.clearInterval(interval);
  }, [publishedAt]);

  if (label === null) {
    return null;
  }

  return (
    <time
      dateTime={publishedAt}
      className={cn('text-[11px] font-medium text-surface-500', className)}
      title="Publié sur le mur"
    >
      {label}
    </time>
  );
}
