import { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import PageHero from '../components/ui/PageHero';
import { unsubscribeFromAlerts } from '../lib/siteApi';

/**
 * Page de désabonnement aux alertes live / événements (lien dans les e-mails).
 */
export default function AlertUnsubscribePage() {
  const [searchParams] = useSearchParams();
  const token = searchParams.get('token') ?? '';
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (token.trim() === '') {
      setError('Lien de désabonnement invalide.');
      setLoading(false);
      return;
    }

    let cancelled = false;

    void (async () => {
      try {
        const result = await unsubscribeFromAlerts(token);
        if (!cancelled) {
          setMessage(result.message);
        }
      } catch (err) {
        if (!cancelled) {
          setError(err instanceof Error ? err.message : 'Désabonnement impossible.');
        }
      } finally {
        if (!cancelled) {
          setLoading(false);
        }
      }
    })();

    return () => {
      cancelled = true;
    };
  }, [token]);

  return (
    <>
      <PageHero
        badge="Alertes"
        title="Désabonnement"
        description="Gérez vos préférences de notification."
        backgroundImage="https://images.unsplash.com/photo-1516321318423-f06f868e38bd?w=1400&h=600&fit=crop"
      />
      <section className="py-16">
        <div className="mx-auto max-w-lg px-4 text-center">
          {loading ? (
            <p className="text-surface-600">Traitement en cours…</p>
          ) : error !== null ? (
            <p className="rounded-lg bg-red-50 p-4 text-sm text-red-800">{error}</p>
          ) : (
            <p className="rounded-lg bg-green-50 p-4 text-sm text-green-900">{message}</p>
          )}
        </div>
      </section>
    </>
  );
}
