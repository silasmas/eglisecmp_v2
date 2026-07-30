import { useEffect, useRef, useState } from 'react';
import QRCode from 'qrcode';
import fondBadge from '../../assets/worker-badge/fond-badge.png';
import nomBadge from '../../assets/worker-badge/nom-badge.png';
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
 * @param content URL / payload du QR
 * @returns Data URL PNG
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
 * Carte badge ouvrier (visuel adapté du studio badge retraite).
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

  return (
    <div className={cn('worker-badge-shell', className)}>
      <div className="worker-badge" style={{ ['--badge-category-color' as string]: data.departmentColor }}>
        <img className="worker-badge-bg" src={fondBadge} alt="" />
        <div className="worker-badge-filter" />
        <div className="worker-badge-border" />
        <div className="worker-badge-photo" aria-label="Photo de l’ouvrier">
          {data.photoUrl !== '' ? (
            <img src={data.photoUrl} alt={data.fullName} />
          ) : (
            <span className="text-4xl text-white/50">👤</span>
          )}
        </div>
        <div className="worker-badge-name-banner">
          <img src={nomBadge} alt="" />
          <span ref={nameRef}>{data.fullName}</span>
        </div>
        <div className="worker-badge-category-banner">{data.department || 'Ouvrier CMP'}</div>
        {data.departmentRole !== '' ? (
          <div className="worker-badge-role">{data.departmentRole}</div>
        ) : null}
        {qrDataUrl !== '' ? (
          <div className="worker-badge-qr">
            <img src={qrDataUrl} alt="QR code du badge" />
          </div>
        ) : null}
        {data.badgeValidated ? (
          <div className="worker-badge-validated">Badge validé</div>
        ) : null}
      </div>
    </div>
  );
}
