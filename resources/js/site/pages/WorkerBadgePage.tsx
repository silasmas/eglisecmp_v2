import { useEffect, useRef, useState } from 'react';
import { useParams } from 'react-router-dom';
import html2canvas from 'html2canvas';
import WorkerBadgeCard from '../components/workers/WorkerBadgeCard';
import { detectSpaBasename } from '../lib/routerBasename';
import { fetchWorkerBadge, type WorkerBadgeData } from '../lib/siteApi';
import '../styles/worker-badge.css';

/**
 * Construit l’URL publique du badge (tient compte du basename /public).
 *
 * @param token Jeton public du badge.
 * @returns URL absolue de la page badge.
 */
function buildBadgePublicUrl(token: string): string {
  if (typeof window !== 'undefined' && typeof window.CMP_BADGE_PUBLIC_URL === 'string' && window.CMP_BADGE_PUBLIC_URL !== '') {
    return window.CMP_BADGE_PUBLIC_URL;
  }
  const origin = typeof window !== 'undefined' ? window.location.origin : '';
  const base = detectSpaBasename();
  const prefix = base !== '' ? base : '';
  return `${origin}${prefix}/ouvriers/badge/${token}`;
}

declare global {
  interface Window {
    CMP_BADGE_PUBLIC_URL?: string;
    CMP_BADGE_TOKEN?: string;
  }
}

type WorkerBadgeViewProps = {
  token: string;
};

/**
 * Contenu page badge — même moule que retraite-jcmp-inscription/badge.html.
 *
 * @param props.token Jeton public de l’ouvrier.
 */
export function WorkerBadgeView({ token }: WorkerBadgeViewProps) {
  const [data, setData] = useState<WorkerBadgeData | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);
  const [zoom, setZoom] = useState(100);
  const badgeRef = useRef<HTMLDivElement | null>(null);

  const badgeUrl = buildBadgePublicUrl(token);

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
   * Télécharge le badge en JPEG (html2canvas).
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
    <div className="worker-badge-module-page">
      <header className="hero">
        <div className="hero-content">
          <div className="hero-badge">
            <i className="bi bi-person-badge" aria-hidden />
            Badge ouvrier
          </div>
          <h1>
            Votre <span>badge</span>
          </h1>
          <p className="hero-sub">
            <strong>Centre Missionnaire Philadelphie</strong>
            {data !== null ? ` · ${data.fullName}` : ' · Service ouvrier'}
          </p>
          <div className="hero-divider" />
        </div>
      </header>

      <div className="tpl-banner">
        <i className="bi bi-info-circle" aria-hidden />
        <span>
          Présentez ce badge lors des services. Le QR code en bas à droite ouvre cette page.
        </span>
      </div>

      {error !== null && data === null ? (
        <p className="tpl-banner" style={{ background: '#fef2f2', borderColor: '#fecaca', color: '#b91c1c' }}>
          {error}
        </p>
      ) : null}

      {data !== null ? (
        <>
          <div className="tpl-actions">
            <button type="button" className="btn btn-download" onClick={() => window.print()}>
              <i className="bi bi-printer" aria-hidden /> Imprimer
            </button>
            <button
              type="button"
              className="btn btn-outline"
              disabled={busy}
              onClick={() => void downloadBadge()}
            >
              <i className="bi bi-download" aria-hidden />
              {busy ? ' Génération…' : ' Télécharger'}
            </button>
          </div>

          <div className="badge-zoom-controls" style={{ marginTop: '1rem' }}>
            <button
              type="button"
              className="badge-zoom-btn"
              aria-label="Réduire l’aperçu"
              onClick={() => setZoom((z) => Math.max(60, z - 10))}
            >
              <i className="bi bi-dash-lg" aria-hidden />
            </button>
            <span className="badge-zoom-value">{zoom}%</span>
            <button
              type="button"
              className="badge-zoom-btn"
              aria-label="Agrandir l’aperçu"
              onClick={() => setZoom((z) => Math.min(140, z + 10))}
            >
              <i className="bi bi-plus-lg" aria-hidden />
            </button>
          </div>

          <div className="tpl-stage">
            <div className="badge-scene">
              <div
                ref={badgeRef}
                className="badge-zoom-wrap"
                style={{ transform: `scale(${zoom / 100})` }}
              >
                <WorkerBadgeCard data={data} badgeUrl={badgeUrl} />
              </div>
            </div>
          </div>
        </>
      ) : error === null ? (
        <p className="tpl-banner">Chargement du badge…</p>
      ) : null}
    </div>
  );
}

/**
 * Route SPA (secours) — lit le token dans l’URL React Router.
 */
export default function WorkerBadgePage() {
  const { token = '' } = useParams();
  return <WorkerBadgeView token={token} />;
}
