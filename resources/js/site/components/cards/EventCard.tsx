import { Link } from 'react-router-dom';
import { Calendar, MapPin, ArrowRight } from 'lucide-react';
import type { Event } from '../../data/types';
import { cn } from '../../lib/utils';

interface EventCardProps {
  event: Event;
  featured?: boolean;
  banner?: boolean;
}

export default function EventCard({ event, featured, banner }: EventCardProps) {
  const formattedDate = new Date(event.date).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  });

  if (banner) {
    return (
      <Link
        to="/events"
        className={cn(
          'group relative block h-full overflow-hidden rounded-[2rem] border border-white/6 bg-surface-950 shadow-[0_18px_52px_rgba(9,9,11,0.22)] transition-all duration-500 hover:-translate-y-1 hover:shadow-[0_24px_68px_rgba(9,9,11,0.28)]',
          featured && 'ring-1 ring-white/8'
        )}
      >
        <img
          src={event.image}
          alt={event.title}
          className="absolute inset-0 h-full w-full object-cover img-hover"
        />
        <div className="absolute inset-0 bg-[linear-gradient(90deg,rgba(9,9,11,0.88)_0%,rgba(9,9,11,0.52)_45%,rgba(9,9,11,0.24)_100%)]" />
        <div className="absolute inset-0 bg-gradient-to-t from-surface-950/80 via-transparent to-white/5" />
        <div className="absolute inset-x-0 top-0 h-32 bg-[radial-gradient(circle_at_top_left,rgba(255,255,255,0.24),transparent_58%)] opacity-80" />

        <div className="relative flex h-full flex-col justify-between p-6 sm:p-7">
          <div className="flex items-start justify-between gap-4">
            <span className="inline-flex w-fit items-center gap-2 rounded-full border border-white/12 bg-white/10 px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[0.16em] text-white/85 backdrop-blur-md">
              <Calendar className="h-3.5 w-3.5" />
              {formattedDate}
            </span>
            <span className="hidden items-center gap-1.5 rounded-full border border-white/10 bg-black/20 px-3 py-1.5 text-[10px] font-medium text-white/65 backdrop-blur-md sm:inline-flex">
              <MapPin className="h-3.5 w-3.5" />
              {event.location}
            </span>
          </div>

          <div className="max-w-xl">
            <h3
              className={cn(
                'font-heading font-bold leading-tight text-white',
                featured ? 'text-2xl sm:text-[2rem]' : 'text-xl sm:text-2xl'
              )}
            >
              {event.title}
            </h3>
            <p className="mt-3 max-w-lg text-sm leading-relaxed text-white/68 sm:text-[15px]">
              {event.description}
            </p>
            <div className="mt-5 inline-flex items-center gap-1.5 text-sm font-semibold text-white/92 transition-all duration-300 group-hover:gap-2.5">
              Voir l'événement
              <ArrowRight className="h-4 w-4 transition-transform duration-300 group-hover:translate-x-0.5" />
            </div>
          </div>
        </div>
      </Link>
    );
  }

  return (
    <Link
      to="/events"
      className="group block rounded-2xl overflow-hidden relative h-full hover:shadow-xl transition-all duration-500"
    >
      <img
        src={event.image}
        alt={event.title}
        className="absolute inset-0 w-full h-full object-cover img-hover"
      />
      {/* Gradient — stronger at bottom for text readability */}
      <div className="absolute inset-0 bg-gradient-to-t from-surface-950/90 via-surface-950/40 to-transparent" />
      {/* Slight darkening on hover */}
      <div className="absolute inset-0 bg-surface-950/0 group-hover:bg-surface-950/15 transition-colors duration-500" />

      <div className="absolute bottom-0 left-0 right-0 p-5 sm:p-6">
        <h3 className={cn(
          'font-heading font-bold text-white leading-snug',
          featured ? 'text-xl sm:text-2xl' : 'text-lg'
        )}>
          {event.title}
        </h3>

        {featured && (
          <p className="mt-2 text-white/55 text-sm leading-relaxed line-clamp-2">
            {event.description}
          </p>
        )}

        <div className="mt-2.5 flex items-center gap-4 text-[12px] text-white/60">
          <span className="flex items-center gap-1.5">
            <Calendar className="w-3 h-3" />
            {formattedDate}
          </span>
          <span className="flex items-center gap-1.5">
            <MapPin className="w-3 h-3" />
            {event.location}
          </span>
        </div>

        {/* CTA — appears on hover for ALL cards */}
        <div className="mt-3 flex items-center gap-1.5 text-sm font-semibold text-white opacity-0 translate-y-2 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-300">
          En savoir plus <ArrowRight className="w-4 h-4 transition-transform group-hover:translate-x-0.5" />
        </div>
      </div>
    </Link>
  );
}
