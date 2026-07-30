import { useEffect, useRef, useState } from 'react';
import QRCode from 'qrcode';
import fondBadge from '../../assets/worker-badge/fond-badge.png';
import nomBadge from '../../assets/worker-badge/nom-badge.png';
import type { WorkerBadgeData } from '../../lib/siteApi';
import { cn } from '../../lib/utils';

type WorkerBadgeCardProps = {
  data: WorkerBadgeData;
  badgeUrl: string;
  className?: string;
};

/**
 * Carte badge ouvrier (visuel adapté du studio badge retraite).
 */
export default function WorkerBadgeCard({ data, badgeUrl, className }: WorkerBadgeCardProps) {
  const [qrDataUrl, setQrDataUrl] = useState('');
  const nameRef = useRef<HTMLSpanElement | null>(null);

  useEffect(() => {
    let cancelled = false;
    QRCode.toDataURL(badgeUrl, {
      margin: 1,
      width: 256,
      color: { dark: '#18181b', light: '#ffffff' },
    })
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
