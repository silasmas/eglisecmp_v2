import { useEffect, useRef, useState } from 'react';
import { useParams } from 'react-router-dom';
import { Download, IdCard, Printer, ShieldCheck } from 'lucide-react';
import html2canvas from 'html2canvas';
import WorkerBadgeCard from '../components/workers/WorkerBadgeCard';
import { fetchWorkerBadge, type WorkerBadgeData } from '../lib/siteApi';
import '../styles/worker-badge.css';

/**
 * Page badge ouvrier — design module badge (standalone), hors layout site.
 */
export default function WorkerBadgePage() {
  const { token = '' } = useParams();
  const [data, setData] = useState<WorkerBadgeData | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);
  const [zoom, setZoom] = useState(100);
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
    <div className="worker-badge-standalone">
      <header className="worker-badge-standalone__hero">
        <div className="worker-badge-standalone__hero-inner">
          <span className="worker-badge-standalone__pill">
            <IdCard className="h-4 w-4" aria-hidden />
            Badge ouvrier
          </span>
          <h1 className="worker-badge-standalone__title">
            Votre <span>badge</span>
          </h1>
          <p className="worker-badge-standalone__sub">
            <strong>Centre Missionnaire Philadelphie</strong>
            {data !== null ? ` · ${data.fullName}` : ' · Service ouvrier'}
          </p>
          <div className="worker-badge-standalone__divider" />
        </div>
      </header>

      <div className="worker-badge-standalone__banner">
        <span>
          Présentez ce badge lors des services. Le QR code renvoie vers cette page sécurisée par jeton.
        </span>
      </div>

      {error !== null && data === null ? (
        <p className="mx-auto mt-8 max-w-lg rounded-2xl bg-red-50 px-4 py-3 text-center text-sm text-red-700">
          {error}
        </p>
      ) : null}

      {data !== null ? (
        <>
          <div className="worker-badge-standalone__status">
            {data.badgeValidated ? (
              <span className="worker-badge-standalone__chip worker-badge-standalone__chip--ok">
                <ShieldCheck className="h-4 w-4" />
                Badge validé
              </span>
            ) : (
              <span className="worker-badge-standalone__chip worker-badge-standalone__chip--warn">
                Dossier {data.status === 'approved' ? 'validé — badge à générer' : 'en cours de validation'}
              </span>
            )}
          </div>

          <div className="worker-badge-standalone__actions">
            <button type="button" className="worker-badge-standalone__btn" onClick={() => window.print()}>
              <Printer className="h-4 w-4" />
              Imprimer
            </button>
            <button
              type="button"
              className="worker-badge-standalone__btn worker-badge-standalone__btn--primary"
              disabled={busy}
              onClick={() => void downloadBadge()}
            >
              <Download className="h-4 w-4" />
              {busy ? 'Génération…' : 'Télécharger'}
            </button>
          </div>

          <div className="worker-badge-standalone__zoom">
            <button
              type="button"
              className="worker-badge-standalone__zoom-btn"
              aria-label="Réduire"
              onClick={() => setZoom((z) => Math.max(60, z - 10))}
            >
              −
            </button>
            <span>{zoom}%</span>
            <button
              type="button"
              className="worker-badge-standalone__zoom-btn"
              aria-label="Agrandir"
              onClick={() => setZoom((z) => Math.min(140, z + 10))}
            >
              +
            </button>
          </div>

          <div className="worker-badge-standalone__stage">
            <div
              ref={badgeRef}
              className="inline-block bg-white p-2"
              style={{ transform: `scale(${zoom / 100})`, transformOrigin: 'top center' }}
            >
              <WorkerBadgeCard data={data} badgeUrl={badgeUrl} />
            </div>
          </div>

          <div className="worker-badge-standalone__meta">
            <p><strong>Département :</strong> {data.department}</p>
            {data.departmentRole !== '' ? <p><strong>Rôle :</strong> {data.departmentRole}</p> : null}
            <p><strong>Ville :</strong> {data.city} · {data.commune}</p>
          </div>
        </>
      ) : error === null ? (
        <p className="mt-10 text-center text-sm text-zinc-500">Chargement du badge…</p>
      ) : null}
    </div>
  );
}
