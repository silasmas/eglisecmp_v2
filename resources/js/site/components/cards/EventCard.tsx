import { useState } from 'react';
import { Calendar, MapPin, ArrowRight, Sparkles } from 'lucide-react';
import type { Event } from '../../data/types';
import SocialShareToolbar from '../ui/SocialShareToolbar';
import { cn } from '../../lib/utils';

const DEFAULT_EVENT_IMAGE =
  'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=1200&h=800&fit=crop';

function resolveEventImage(image: string | undefined): string {
  if (image !== undefined && image.trim() !== '') {
    return image;
  }

  return DEFAULT_EVENT_IMAGE;
}

interface EventCardProps {
  event: Event;
  featured?: boolean;
  banner?: boolean;
  /** Ouvre la modale de détail au clic sur « Voir en détail ». */
  onOpenDetail?: (event: Event) => void;
}

/**
 * Carte événement avec image, titre visible et action « Voir en détail ».
 */
export default function EventCard({ event, featured, banner, onOpenDetail }: EventCardProps) {
  const [imageSrc, setImageSrc] = useState(() => resolveEventImage(event.image));

  const formattedDate = new Date(event.date).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  });

  const handleOpenDetail = () => {
    onOpenDetail?.(event);
  };

  const shareToolbar = (
    <div
      className="relative z-20"
      onClick={(clickEvent) => clickEvent.stopPropagation()}
      onKeyDown={(keydownEvent) => keydownEvent.stopPropagation()}
      role="presentation"
    >
      <SocialShareToolbar
        title={event.title}
        description={event.description}
        sharePath="/events"
        compact
        menuStyle="popover"
        tone="dark"
        animateOnClick
      />
    </div>
  );

  if (banner) {
    return (
      <button
        type="button"
        onClick={handleOpenDetail}
        className={cn(
          'group relative block h-full min-h-[22rem] w-full overflow-hidden rounded-[2rem] border border-white/6 bg-surface-950 text-left shadow-[0_18px_52px_rgba(9,9,11,0.22)] transition-all duration-500 hover:-translate-y-1 hover:shadow-[0_24px_68px_rgba(9,9,11,0.28)] sm:min-h-[26rem]',
          featured && 'ring-1 ring-gold-300/30',
        )}
      >
        <img
          src={imageSrc}
          alt={event.title}
          className="absolute inset-0 h-full w-full object-cover img-hover"
          onError={() => setImageSrc(DEFAULT_EVENT_IMAGE)}
        />
        <div className="absolute inset-0 bg-[linear-gradient(90deg,rgba(9,9,11,0.88)_0%,rgba(9,9,11,0.52)_45%,rgba(9,9,11,0.24)_100%)]" />
        <div className="absolute inset-0 bg-gradient-to-t from-surface-950/80 via-transparent to-white/5" />

        <div className="relative flex h-full min-h-[22rem] flex-col justify-between p-6 sm:min-h-[26rem] sm:p-7">
          <div className="flex items-start justify-between gap-4">
            <span className="inline-flex w-fit items-center gap-2 rounded-full border border-white/12 bg-white/10 px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[0.16em] text-white/85 backdrop-blur-md">
              <Calendar className="h-3.5 w-3.5" />
              {formattedDate}
            </span>
            <div className="flex items-start gap-2">
              {featured ? (
                <span className="badge-blink inline-flex items-center gap-1.5 rounded-full border border-gold-300/30 bg-burgundy-800/80 px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[0.14em] text-gold-200">
                  <Sparkles className="h-3.5 w-3.5" />
                  À la une
                </span>
              ) : null}
              {shareToolbar}
            </div>
          </div>

          <div className="max-w-xl">
            <h3 className="font-heading text-2xl font-bold leading-tight text-white sm:text-[2rem]">{event.title}</h3>
            <p className="mt-3 max-w-lg text-sm leading-relaxed text-white/68 sm:text-[15px] line-clamp-3">{event.description}</p>
            <div className="mt-5 inline-flex items-center gap-1.5 text-sm font-semibold text-white/92">
              Voir en détail
              <ArrowRight className="h-4 w-4" />
            </div>
          </div>
        </div>
      </button>
    );
  }

  return (
    <article
      className={cn(
        'group relative flex min-h-[22rem] flex-col overflow-hidden rounded-2xl bg-surface-900 shadow-lg transition-all duration-500 hover:-translate-y-1 hover:shadow-xl sm:min-h-[24rem]',
        featured && 'ring-2 ring-gold-300/40',
      )}
    >
      <img
        src={imageSrc}
        alt={event.title}
        className="absolute inset-0 h-full w-full object-cover img-hover"
        onError={() => setImageSrc(DEFAULT_EVENT_IMAGE)}
      />
      <div className="absolute inset-0 bg-gradient-to-t from-surface-950/95 via-surface-950/45 to-surface-950/10" />

      <div className="absolute left-4 right-4 top-4 z-10 flex items-start justify-between gap-3">
        {featured ? (
          <span className="badge-blink inline-flex items-center gap-1.5 rounded-full border border-gold-300/30 bg-burgundy-800/90 px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-gold-200 backdrop-blur-md">
            <Sparkles className="h-3.5 w-3.5" />
            À la une
          </span>
        ) : (
          <span />
        )}
        {shareToolbar}
      </div>

      <div className="relative mt-auto flex flex-col p-5 sm:p-6">
        {event.theme && event.theme.trim() !== '' ? (
          <p className="mb-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-gold-300">{event.theme}</p>
        ) : null}

        <h3 className="font-heading text-xl font-bold leading-snug text-white sm:text-2xl">{event.title}</h3>

        <div className="mt-3 flex flex-wrap items-center gap-3 text-[12px] text-white/70">
          <span className="inline-flex items-center gap-1.5">
            <Calendar className="h-3.5 w-3.5" />
            {formattedDate}
          </span>
          {event.time.trim() !== '' ? (
            <span>{event.time}</span>
          ) : null}
          <span className="inline-flex items-center gap-1.5">
            <MapPin className="h-3.5 w-3.5" />
            {event.location}
          </span>
        </div>

        {event.description.trim() !== '' ? (
          <p className="mt-3 line-clamp-2 text-sm leading-relaxed text-white/60">{event.description}</p>
        ) : null}

        <button
          type="button"
          onClick={handleOpenDetail}
          className="mt-4 inline-flex w-fit items-center gap-1.5 rounded-full bg-white/95 px-4 py-2 text-sm font-semibold text-surface-900 transition hover:bg-white"
        >
          Voir en détail
          <ArrowRight className="h-4 w-4" />
        </button>
      </div>
    </article>
  );
}
