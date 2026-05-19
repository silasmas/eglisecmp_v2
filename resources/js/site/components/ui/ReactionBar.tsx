import { useCallback, useEffect, useState } from 'react';
import { fetchReactionKeyLabels, fetchSiteJson, fetchSitePostJson } from '../../lib/siteApi';
import { getVisitorToken } from '../../lib/visitorToken';

type Counts = Record<string, number>;

let labelsCache: Record<string, string> | null = null;

/**
 * Barre de réactions (Amen, Prière…) pour un contenu identifié par `reactableKey` (ex. post:12).
 *
 * @param props.reactableKey Clé API du contenu ; si absente, rien n'est rendu.
 * @param props.compact Style plus discret pour les listes denses.
 * @param props.className Classes CSS additionnelles sur le conteneur.
 */
export default function ReactionBar({
  reactableKey,
  compact = false,
  className = '',
}: {
  reactableKey?: string | null;
  compact?: boolean;
  className?: string;
}) {
  const [labels, setLabels] = useState<Record<string, string>>({});
  const [counts, setCounts] = useState<Counts>({});
  const [active, setActive] = useState<string[]>([]);
  const [busy, setBusy] = useState(false);

  const loadLabels = useCallback(async () => {
    if (labelsCache) {
      setLabels(labelsCache);

      return;
    }

    try {
      const loaded = await fetchReactionKeyLabels();
      labelsCache = loaded;
      setLabels(loaded);
    } catch {
      setLabels({});
    }
  }, []);

  const refreshCounts = useCallback(async () => {
    if (!reactableKey) {
      return;
    }

    const token = getVisitorToken();

    if (!token) {
      return;
    }

    try {
      const query = new URLSearchParams({
        keys: reactableKey,
        visitor_token: token,
      });
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
    void loadLabels();
  }, [loadLabels]);

  useEffect(() => {
    void refreshCounts();
  }, [refreshCounts]);

  const toggle = useCallback(
    async (reactionKey: string) => {
      if (!reactableKey || busy) {
        return;
      }

      const token = getVisitorToken();

      if (!token) {
        return;
      }

      setBusy(true);

      try {
        const body = await fetchSitePostJson<{
          data: { active: boolean; reaction_key: string; counts: Counts };
        }>('reactions', {
          reactable_key: reactableKey,
          reaction_key: reactionKey,
          visitor_token: token,
        });
        setCounts(body.data?.counts ?? {});
        setActive((prev) => {
          const next = new Set(prev);

          if (body.data?.active) {
            next.add(reactionKey);
          } else {
            next.delete(reactionKey);
          }

          return [...next];
        });
      } finally {
        setBusy(false);
      }
    },
    [busy, reactableKey],
  );

  if (!reactableKey) {
    return null;
  }

  const keys = Object.keys(labels);

  if (keys.length === 0) {
    return null;
  }

  return (
    <div
      className={`flex flex-wrap gap-1.5 ${compact ? 'opacity-90' : ''} ${className}`.trim()}
      onClick={(event) => event.stopPropagation()}
      onKeyDown={(event) => event.stopPropagation()}
    >
      {keys.map((key) => {
        const isOn = active.includes(key);
        const count = counts[key] ?? 0;

        return (
          <button
            key={key}
            type="button"
            disabled={busy}
            onClick={() => void toggle(key)}
            className={`inline-flex items-center gap-1 rounded-full border font-medium transition-colors ${
              compact ? 'px-2 py-0.5 text-[10px]' : 'px-2.5 py-1 text-[11px]'
            } ${
              isOn
                ? 'border-burgundy-500 bg-burgundy-50 text-burgundy-800'
                : 'border-surface-200 bg-white text-surface-600 hover:border-burgundy-300 hover:bg-surface-50'
            }`}
          >
            <span>{labels[key] ?? key}</span>
            {count > 0 ? <span className="tabular-nums text-surface-400">{count}</span> : null}
          </button>
        );
      })}
    </div>
  );
}
