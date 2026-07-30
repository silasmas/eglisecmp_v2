import { useEffect, useRef, useState } from 'react';
import QRCode from 'qrcode';
import fondBadge from '../../assets/worker-badge/fond-badge.png';
import nomBadge from '../../assets/worker-badge/nom-badge.png';
import chambreBadge from '../../assets/worker-badge/Chambre.png';
import logoCmp from '../../assets/Logo-CMP-2023-new.png';
import type { WorkerBadgeData } from '../../lib/siteApi';
import { cn } from '../../lib/utils';

type WorkerBadgeCardProps = {
  data: WorkerBadgeData;
  badgeUrl: string;
  className?: string;
};

/**
 * Charge une image pour dessin canvas.
 *
 * @param src URL ou data-URI de l’image.
 * @returns Promesse résolue avec l’élément Image chargé.
 */
function loadImage(src: string): Promise<HTMLImageElement> {
  return new Promise((resolve, reject) => {
    const img = new Image();
    img.crossOrigin = 'anonymous';
    img.onload = () => resolve(img);
    img.onerror = () => reject(new Error('image_load_failed'));
    img.src = src;
  });
}

/**
 * Génère un QR code avec le logo CMP au centre (niveau de correction H).
 *
 * @param content Contenu encodé (URL du badge).
 * @returns Data-URL PNG du QR.
 */
async function buildQrWithLogo(content: string): Promise<string> {
  const size = 512;
  const canvas = document.createElement('canvas');
  await QRCode.toCanvas(canvas, content, {
    errorCorrectionLevel: 'H',
    margin: 1,
    width: size,
    color: { dark: '#18181b', light: '#ffffff' },
  });

  const ctx = canvas.getContext('2d');
  if (ctx === null) {
    return canvas.toDataURL('image/png');
  }

  try {
    const logo = await loadImage(logoCmp);
    const logoSize = size * 0.22;
    const x = (size - logoSize) / 2;
    const y = (size - logoSize) / 2;
    const pad = logoSize * 0.12;

    ctx.fillStyle = '#ffffff';
    ctx.beginPath();
    const r = 10;
    const bx = x - pad;
    const by = y - pad;
    const bw = logoSize + pad * 2;
    const bh = logoSize + pad * 2;
    ctx.moveTo(bx + r, by);
    ctx.arcTo(bx + bw, by, bx + bw, by + bh, r);
    ctx.arcTo(bx + bw, by + bh, bx, by + bh, r);
    ctx.arcTo(bx, by + bh, bx, by, r);
    ctx.arcTo(bx, by, bx + bw, by, r);
    ctx.closePath();
    ctx.fill();
    ctx.drawImage(logo, x, y, logoSize, logoSize);
  } catch {
    // Logo indisponible : QR sans logo.
  }

  return canvas.toDataURL('image/png');
}

/**
 * Carte badge ouvrier — classes compatibles studio retraite (retreat-badge*).
 *
 * @param props.data Données publiques de l’ouvrier.
 * @param props.badgeUrl URL encodée dans le QR.
 * @param props.className Classes CSS optionnelles.
 */
export default function WorkerBadgeCard({ data, badgeUrl, className }: WorkerBadgeCardProps) {
  const [qrDataUrl, setQrDataUrl] = useState('');
  const nameRef = useRef<HTMLSpanElement | null>(null);

  useEffect(() => {
    let cancelled = false;
    buildQrWithLogo(badgeUrl)
      .then((url) => {
        if (!cancelled) {
          setQrDataUrl(url);
        }
      })
      .catch(() => {
        if (!cancelled) {
          setQrDataUrl('');
        }
      });

    return () => {
      cancelled = true;
    };
  }, [badgeUrl]);

  useEffect(() => {
    const el = nameRef.current;
    if (el === null) {
      return;
    }
    let size = 18;
    el.style.fontSize = `${size}px`;
    while (el.scrollWidth > el.clientWidth && size > 10) {
      size -= 1;
      el.style.fontSize = `${size}px`;
    }
  }, [data.fullName]);

  const role = data.departmentRole.trim();
  const color = data.departmentColor || '#7b1d3e';

  return (
    <div
      className={cn('retreat-badge-shell worker-badge-shell', className)}
      style={{ ['--badge-category-color' as string]: color }}
    >
      <div
        className={cn(
          'retreat-badge worker-badge',
          role === '' ? 'retreat-badge-no-assignments' : 'retreat-badge-single-assignment',
        )}
        style={{ ['--badge-category-color' as string]: color }}
      >
        <img className="retreat-badge-bg worker-badge-bg" src={fondBadge} alt="" draggable={false} />
        <div className="retreat-badge-filter worker-badge-filter" aria-hidden />
        <div className="retreat-badge-border worker-badge-border" aria-hidden />
        <div className="retreat-badge-photo worker-badge-photo" aria-label="Photo de l’ouvrier">
          {data.photoUrl !== '' ? (
            <img src={data.photoUrl} alt={data.fullName} />
          ) : (
            <i className="bi bi-person-fill" aria-hidden />
          )}
        </div>
        <div className="retreat-badge-name-banner worker-badge-name-banner">
          <img src={nomBadge} alt="" draggable={false} />
          <span ref={nameRef}>{data.fullName}</span>
        </div>
        <div className="retreat-badge-category-banner worker-badge-category-banner">
          {data.department || 'Ouvrier CMP'}
        </div>
        {role !== '' ? (
          <div
            className="retreat-badge-assignment retreat-badge-chambre worker-badge-role-box"
            data-assignment-label="Rôle"
          >
            <img src={chambreBadge} alt="" draggable={false} />
            <span className="retreat-badge-assignment-caption">Rôle</span>
            <strong>{role}</strong>
          </div>
        ) : null}
        {qrDataUrl !== '' ? (
          <div className="retreat-badge-qr worker-badge-qr" title="Scanner pour ouvrir le badge">
            <img src={qrDataUrl} alt="QR code du badge" />
          </div>
        ) : null}
      </div>
    </div>
  );
}
