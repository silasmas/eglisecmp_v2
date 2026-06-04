import { useCallback, useEffect, useState } from 'react';
import { fetchSiteJson, fetchSitePostJson } from '../../lib/siteApi';
import { getVisitorToken } from '../../lib/visitorToken';
import { cn } from '../../lib/utils';

type Counts = Record<string, number>;

type TestimonyReactionBarProps = {
  reactableKey: string;
  labels: Record<string, string>;
  /** Une seule réaction active par visiteur (témoignages). */
  singleChoice?: boolean;
  compact?: boolean;
  className?: string;
};

/**
 * Réactions spécifiques témoignages (Amen, Gloire à Dieu, etc.) via l’API content_reactions.
 */
export default function TestimonyReactionBar({
  reactableKey,
  labels,
  singleChoice = false,
  compact = false,
  className = '',
}: TestimonyReactionBarProps) {
  const [counts, setCounts] = useState<Counts>({});
  const [active, setActive] = useState<string[]>([]);
  const [busy, setBusy] = useState(false);

  const refreshCounts = useCallback(async () => {
    const token = getVisitorToken();
    if (!token) {
      return;
    }
    try {
      const query = new URLSearchParams({ keys: reactableKey, visitor_token: token });
      const body = await fetchSiteJson<{
        data: { counts: Record<string, Counts>; mine: Record<string, string[]> };
      }>(`reactions?${query.toString()}`);
      setCounts(body.data?.counts?.[reactableKey] ?? {});
      setActive(body.data?.mine?.[reactableKey] ?? []);
    } catch {
      setCounts({});
      setActive([]);
    }
  }, [reactableKey]);

  useEffect(() => {
    void refreshCounts();
  }, [refreshCounts]);

  const toggle = async (reactionKey: string) => {
    const token = getVisitorToken();
    if (!token || busy) {
      return;
    }
    setBusy(true);
    try {
      const body = await fetchSitePostJson<{
        data: { counts: Counts; active: boolean };
      }>('reactions', {
        reactable_key: reactableKey,
        reaction_key: reactionKey,
        visitor_token: token,
      });
      setCounts(body.data?.counts ?? {});
      if (singleChoice) {
        setActive(body.data?.active ? [reactionKey] : []);
      } else {
        const nextActive = [...active];
        if (body.data?.active) {
          if (!nextActive.includes(reactionKey)) {
            nextActive.push(reactionKey);
          }
        } else {
          const idx = nextActive.indexOf(reactionKey);
          if (idx >= 0) {
            nextActive.splice(idx, 1);
          }
        }
        setActive(nextActive);
      }
    } finally {
      setBusy(false);
    }
  };

  const keys = Object.keys(labels);

  if (keys.length === 0) {
    return null;
  }

  return (
    <div className={cn('flex flex-wrap gap-2', className)}>
      {keys.map((key) => {
        const isOn = active.includes(key);
        const count = counts[key] ?? 0;
        return (
          <button
            key={key}
            type="button"
            disabled={busy}
            onClick={() => void toggle(key)}
            className={cn(
              'rounded-full border font-semibold transition',
              compact ? 'px-2 py-1 text-[10px]' : 'px-3 py-1.5 text-xs',
              isOn
                ? 'border-[#950000] bg-[#950000] text-white'
                : 'border-black/15 bg-white/80 text-surface-800 hover:border-[#950000]/40',
            )}
          >
            {labels[key]}
            {count > 0 ? ` (${count})` : ''}
          </button>
        );
      })}
    </div>
  );
}
