import { useEffect, useRef, useState } from 'react';
import { useParams } from 'react-router-dom';
import { Download, Printer, ShieldCheck } from 'lucide-react';
import html2canvas from 'html2canvas';
import PageHero from '../components/ui/PageHero';
import WorkerBadgeCard from '../components/workers/WorkerBadgeCard';
import { fetchWorkerBadge, type WorkerBadgeData } from '../lib/siteApi';
import '../styles/worker-badge.css';

/**
 * Page publique badge ouvrier (lien / QR code).
 */
export default function WorkerBadgePage() {
  const { token = '' } = useParams();
  const [data, setData] = useState<WorkerBadgeData | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);
  const badgeRef = useRef<HTMLDivElement | null>(null);

  const badgeUrl = typeof window !== 'undefined'
    ? `${window.location.origin}/ouvriers/badge/${token}`
    : `/ouvriers/badge/${token}`;

  useEffect(() => {
    if (token === '') {
      setError('Badge introuvable.');
      return;
    }
    let cancelled = false;
    fetchWorkerBadge(token)
      .then((badge) => {
        if (!cancelled) {
          setData(badge);
        }
      })
      .catch(() => {
        if (!cancelled) {
          setError('Ce badge est introuvable ou n’est plus disponible.');
        }
      });
    return () => {
      cancelled = true;
    };
  }, [token]);

  /**
   * Télécharge le badge en JPEG.
   */
  const downloadBadge = async () => {
    if (badgeRef.current === null || data === null) {
      return;
    }
    setBusy(true);
    try {
      const canvas = await html2canvas(badgeRef.current, {
        backgroundColor: '#ffffff',
        scale: 2,
        useCORS: true,
      });
      const link = document.createElement('a');
      link.download = `badge_ouvrier_${data.lastName}_${data.firstName}.jpg`;
      link.href = canvas.toDataURL('image/jpeg', 0.95);
      link.click();
    } catch {
      setError('Téléchargement impossible. Utilisez Imprimer ou une capture d’écran.');
    } finally {
      setBusy(false);
    }
  };

  return (
    <>
      <PageHero
        compact
        badge="Badge ouvrier"
        title={data?.fullName ?? 'Badge CMP'}
        description="Carte d’identité de service du Centre Missionnaire Philadelphie."
      />

      <section className="bg-gradient-to-b from-burgundy-50/30 to-white pb-20">
        <div className="mx-auto max-w-xl px-4 text-center sm:px-6">
          {error !== null && data === null ? (
            <p className="rounded-2xl bg-red-50 px-4 py-3 text-sm text-red-700">{error}</p>
          ) : null}

          {data !== null ? (
            <>
              {data.badgeValidated ? (
                <div className="mb-6 inline-flex items-center gap-2 rounded-full bg-emerald-100 px-4 py-2 text-sm font-semibold text-emerald-800">
                  <ShieldCheck className="h-4 w-4" />
                  Badge validé
                </div>
              ) : (
                <div className="mb-6 inline-flex items-center gap-2 rounded-full bg-amber-100 px-4 py-2 text-sm font-semibold text-amber-900">
                  Dossier {data.status === 'approved' ? 'validé — badge à générer' : 'en cours de validation'}
                </div>
              )}

              <div ref={badgeRef} className="inline-block bg-white p-2">
                <WorkerBadgeCard data={data} badgeUrl={badgeUrl} />
              </div>

              <div className="mt-6 flex flex-wrap justify-center gap-3">
                <button
                  type="button"
                  onClick={() => window.print()}
                  className="inline-flex items-center gap-2 rounded-xl border border-surface-200 bg-white px-4 py-2.5 text-sm font-semibold"
                >
                  <Printer className="h-4 w-4" />
                  Imprimer
                </button>
                <button
                  type="button"
                  disabled={busy}
                  onClick={() => void downloadBadge()}
                  className="inline-flex items-center gap-2 rounded-xl bg-burgundy-900 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-60"
                >
                  <Download className="h-4 w-4" />
                  {busy ? 'Génération…' : 'Télécharger'}
                </button>
              </div>

              <div className="mt-8 rounded-2xl border border-surface-200 bg-white p-5 text-left text-sm text-surface-700">
                <p><strong>Département :</strong> {data.department}</p>
                {data.departmentRole !== '' ? <p className="mt-1"><strong>Rôle :</strong> {data.departmentRole}</p> : null}
                <p className="mt-1"><strong>Ville :</strong> {data.city} · {data.commune}</p>
              </div>
            </>
          ) : error === null ? (
            <p className="text-sm text-surface-500">Chargement du badge…</p>
          ) : null}
        </div>
      </section>
    </>
  );
}
