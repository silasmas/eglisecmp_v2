import clsx from 'clsx';

/**
 * Placeholder animé (style shadcn/ui, sans dépendance externe).
 */
export function Skeleton({ className }: { className?: string }) {
  return <div className={clsx('animate-pulse rounded-md bg-surface-200', className)} aria-hidden />;
}

/** Skeleton carte « contenu à la une ». */
export function FeaturedHeroSkeleton() {
  return (
    <div className="relative h-[220px] overflow-hidden rounded-3xl sm:h-[260px]">
      <Skeleton className="absolute inset-0 rounded-3xl" />
      <div className="absolute inset-0 bg-gradient-to-r from-surface-950/40 to-transparent" />
      <div className="relative flex h-full flex-col justify-end p-8 sm:p-10">
        <Skeleton className="mb-4 h-7 w-28 rounded-full bg-white/20" />
        <Skeleton className="h-9 w-3/4 max-w-md bg-white/25" />
        <Skeleton className="mt-3 h-4 w-full max-w-md bg-white/20" />
      </div>
    </div>
  );
}

/** Skeleton bloc enseignements (grande carte + liste). */
export function TeachingsBlockSkeleton() {
  return (
    <div className="grid grid-cols-1 gap-6 lg:h-[32rem] lg:grid-cols-12">
      <Skeleton className="aspect-[16/10] rounded-2xl lg:col-span-7 lg:aspect-auto lg:min-h-0" />
      <div className="flex flex-col gap-2.5 lg:col-span-5">
        {Array.from({ length: 4 }).map((_, index) => (
          <Skeleton key={index} className="h-[72px] flex-1 rounded-xl" />
        ))}
      </div>
    </div>
  );
}

/** Skeleton carrousel événements. */
export function EventCarouselSkeleton() {
  return (
    <div className="mx-auto mt-14 max-w-5xl">
      <Skeleton className="h-[25rem] rounded-[2rem] sm:h-[29rem]" />
      <div className="mt-8 flex justify-center gap-2">
        {Array.from({ length: 4 }).map((_, index) => (
          <Skeleton key={index} className="h-2.5 w-2.5 rounded-full" />
        ))}
      </div>
    </div>
  );
}

/** Skeleton carte sermon (grille messages). */
export function SermonCardSkeleton() {
  return (
    <div className="overflow-hidden rounded-3xl border border-surface-200 bg-white">
      <Skeleton className="aspect-video w-full rounded-none" />
      <div className="space-y-3 p-5">
        <Skeleton className="h-5 w-20 rounded-full" />
        <Skeleton className="h-6 w-full" />
        <Skeleton className="h-4 w-2/3" />
      </div>
    </div>
  );
}

/** Skeleton grille messages (page Enseignements). */
export function SermonGridSkeleton({ count = 6 }: { count?: number }) {
  return (
    <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
      {Array.from({ length: count }).map((_, index) => (
        <SermonCardSkeleton key={index} />
      ))}
    </div>
  );
}

/** Skeleton méditations par thématique. */
export function MeditationThemesSkeleton() {
  return (
    <div className="space-y-12">
      {Array.from({ length: 3 }).map((_, sectionIndex) => (
        <div key={sectionIndex}>
          <Skeleton className="mb-5 h-8 w-48" />
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            {Array.from({ length: 2 }).map((__, cardIndex) => (
              <Skeleton key={cardIndex} className="h-28 rounded-2xl" />
            ))}
          </div>
        </div>
      ))}
    </div>
  );
}

/** Skeleton playlists empilées par événement. */
export function PlaylistStackSkeleton() {
  return (
    <div className="space-y-14">
      {Array.from({ length: 2 }).map((_, groupIndex) => (
        <div key={groupIndex}>
          <Skeleton className="mb-6 h-10 w-56" />
          <div className="relative mx-auto h-52 max-w-md">
            {Array.from({ length: 3 }).map((__, cardIndex) => (
              <Skeleton
                key={cardIndex}
                className="absolute inset-x-0 h-40 rounded-2xl"
                style={{
                  top: cardIndex * 14,
                  zIndex: 3 - cardIndex,
                  transform: `rotate(${(cardIndex - 1) * 3}deg)`,
                }}
              />
            ))}
          </div>
        </div>
      ))}
    </div>
  );
}

/** Skeleton bloc À propos (accueil). */
export function AboutPreviewSkeleton() {
  return (
    <div className="grid grid-cols-2 gap-4 lg:grid-cols-2">
      <Skeleton className="col-span-2 aspect-[16/10] rounded-[2rem]" />
      <Skeleton className="min-h-[140px] rounded-3xl" />
      <Skeleton className="min-h-[140px] rounded-3xl" />
    </div>
  );
}

/** Skeleton grille programmes. */
export function ProgramsGridSkeleton() {
  return (
    <div className="grid grid-cols-1 gap-5 md:grid-cols-6">
      {Array.from({ length: 5 }).map((_, index) => (
        <Skeleton
          key={index}
          className={`min-h-[240px] rounded-[1.9rem] ${index === 0 ? 'md:col-span-3' : 'md:col-span-2'}`}
        />
      ))}
    </div>
  );
}
