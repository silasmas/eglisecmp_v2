export interface Sermon {
  id: string;
  title: string;
  speaker: string;
  date: string;
  category: string;
  type?: number;
  thumbnail: string;
  duration: string;
  youtubeDurationSeconds?: number | null;
  weeklyServiceDay?: string | null;
  description: string;
  bodyHtml?: string;
  youtubeEmbedUrl?: string | null;
  linkUrl?: string;
  theme?: string;
  eventId?: string | null;
  eventTitle?: string;
  eventImage?: string;
  reactableKey?: string;
}

/** Onglets de la page Enseignements. */
export type TeachingsTab = 'sermons' | 'meditations' | 'playlists';

/** Métadonnées de pagination API posts. */
export interface PostsPageMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  has_more: boolean;
  tab: string;
  search?: string | null;
}

/** Groupe playlist par événement. */
export interface PlaylistEventGroup {
  eventId: string;
  eventTitle: string;
  eventImage: string;
  items: Sermon[];
}

export interface Event {
  id: string;
  title: string;
  date: string;
  time: string;
  location: string;
  description: string;
  image: string;
  /** True si une affiche a été uploadée (pas l'image par défaut). */
  hasPoster?: boolean;
  theme?: string;
  featured?: boolean;
  featuredFrom?: string | null;
  featuredUntil?: string | null;
  reactableKey?: string;
}

export interface Leader {
  id: string;
  name: string;
  role: string;
  image: string;
  bio: string;
}

export interface Program {
  id: string;
  name: string;
  day: string;
  time: string;
  description: string;
  icon: string;
  kind?: string;
  gridWide?: boolean;
  thumbnail?: string;
  bannerImage?: string;
  reactableKey?: string;
}

/** Verset / lecture du jour (API site). */
export interface DailyVerse {
  id: string;
  label?: string;
  reference: string;
  text: string;
  excerpt?: string;
  thumbnail?: string;
  publishAt?: string;
  visibleUntil?: string;
  reactableKey?: string;
}

/** Créneau live récurrent pour le hero (API). */
export interface HeroLiveSlot {
  weekday: number;
  hour: number;
  minute: number;
  label: string;
  subtitle: string;
}

/** Tuile du bandeau hero (données modale). */
export interface HeroStripCard {
  title: string;
  subtitle: string;
  bannerImage: string;
  description: string;
  reactableKey: string;
  reference?: string;
  /** Libellé principal affiché sur la tuile du hero. */
  tilePrimary?: string;
  /** Sous-titre affiché sur la tuile du hero. */
  tileSecondary?: string;
  /** État dynamique : live en cours, prochain créneau, etc. */
  status?: 'live' | 'upcoming' | 'idle';
  /** Lien Google Maps (tuile « Nous trouver »). */
  mapUrl?: string;
  /** Lien externe du live (YouTube, Facebook…). */
  linkUrl?: string;
  /** URL d'intégration iframe si disponible. */
  embedUrl?: string;
  /** Type de flux intégré. */
  embedKind?: 'youtube' | 'facebook' | 'none';
  /** Badge clignotant en tête de modale. */
  modalBadge?: string;
  /** Style du badge modale. */
  modalBadgeTone?: 'live' | 'upcoming-live' | 'reading' | 'program' | 'program-live' | 'featured';
  /** Programme récurrent (horaire fixe chaque semaine). */
  isRecurring?: boolean;
}

/** Les quatre tuiles cliquables sous le hero. */
export interface HeroStripCards {
  live: HeroStripCard;
  event: HeroStripCard;
  reading: HeroStripCard;
  location: HeroStripCard;
}

/** Timing du prochain live pour le bandeau. */
export interface HeroLiveTiming {
  /** Fin du live en cours ou début du prochain live (cible du décompte). */
  targetIso: string;
  /** Début du prochain live (affichage modale). */
  startIso?: string | null;
  /** Fin du live en cours. */
  endIso?: string | null;
  displayMode: 'countdown' | 'days' | 'live';
  daysUntil: number | null;
  status?: 'live' | 'upcoming';
  programName?: string;
  scheduledLabel?: string;
  timeLabel?: string;
  dayLabel?: string;
}

/** Données agrégées pour le bandeau du hero. */
export interface HeroMeta {
  verse: DailyVerse | null;
  liveSlots: HeroLiveSlot[];
  liveTiming?: HeroLiveTiming | null;
  stripCards?: HeroStripCards;
  reactionKeys?: Record<string, string>;
}

/** Carte « à la une » (post programmé). */
export interface FeaturedPostCard {
  id: string;
  slug: string;
  title: string;
  excerpt: string;
  image: string;
  href: string;
  speaker: string;
  reactableKey?: string;
  /** Embed YouTube public ; lecture inline sur l’accueil si défini. */
  youtubeEmbedUrl?: string | null;
}

/** Ligne « En chiffres » (API `/api/site/statistics`). */
export interface SiteHomeStatRow {
  icon_key: string;
  label: string;
  value: number;
  suffix: string;
}

export interface GalleryItem {
  id: string;
  src: string;
  alt: string;
  category: string;
}

export interface Testimony {
  id: string;
  name: string;
  quote: string;
  role?: string;
}

export interface NavItem {
  label: string;
  href: string;
  children?: NavItem[];
}
