export interface Sermon {
  id: string;
  title: string;
  speaker: string;
  date: string;
  /** Horodatage ISO (publication / synchro YouTube) pour tri fiable. */
  sortTimestamp?: string;
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

/** Playlist YouTube / événement pour la page Enseignements. */
export interface TeachingsPlaylistGroup {
  eventId: string;
  title: string;
  description: string;
  thumbnail: string;
  videoCount: number;
  syncedCount?: number;
  visibility: string;
  items: Sermon[];
  /** Dernière vidéo (présent sur les listes allégées API). */
  latestItem?: Sermon;
  /** Jour de culte (`mercredi` | `jeudi` | `dimanche`) pour l’onglet Méditations. */
  weeklyServiceDay?: string;
  youtubePlaylistId?: string | null;
  /** Lien personnalisé (ex. carte « à venir » Bunda). */
  href?: string;
}

/** Résultat de recherche globale site. */
export interface SiteSearchHit {
  type: string;
  id: string;
  title: string;
  subtitle: string;
  href: string;
  thumbnail?: string;
}

/** Métadonnées de pagination API playlists enseignements. */
export interface PlaylistsPageMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  has_more: boolean;
}

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
  contentHref?: string | null;
  contentType?: string | null;
  contentLabel?: string | null;
  contentCount?: number;
  temporalStatus?: 'past' | 'ongoing' | 'upcoming';
  temporalLabel?: string;
  dateEnd?: string;
  youtubePlaylistId?: string | null;
}

/** Édition Bunda (API /api/site/bunda). */
export interface BundaEdition {
  id: string;
  programId?: string;
  editionYear: number;
  title: string;
  date: string;
  image: string;
  description: string;
  body?: string;
  contentHref: string | null;
  contentLabel: string | null;
  buttonLabel: string;
  videoCount: number;
  mealPlanUrl?: string | null;
  mealPlanLabel?: string;
  hasPoster?: boolean;
}

export interface BundaPageData {
  intro: {
    title: string;
    subtitle: string;
    body: string;
    heroImage: string;
    mealPlanUrl: string | null;
    mealPlanLabel: string;
  };
  upcoming: {
    title: string;
    monthLabel: string;
    year: number;
    description: string;
  };
  editions: BundaEdition[];
  latestEdition: BundaEdition | null;
  pastEditions: BundaEdition[];
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
  weekday?: number;
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

/** Élément liste modale « programme de la semaine ». */
export interface HeroStripModalProgram {
  type: 'event' | 'program';
  title: string;
  subtitle: string;
  bannerImage: string;
  description: string;
  badge?: string | null;
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
  /** Programmes hebdomadaires affichés dans la modale (événement de la semaine en tête). */
  modalPrograms?: HeroStripModalProgram[];
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

/** Live YouTube détecté via l’API Data v3. */
export interface YoutubeLivePayload {
  isLive: boolean;
  videoId: string;
  title: string;
  embedUrl: string;
  thumbnailUrl: string;
  watchUrl: string;
}

/** Données agrégées pour le bandeau du hero. */
export interface HeroMeta {
  verse: DailyVerse | null;
  liveSlots: HeroLiveSlot[];
  liveTiming?: HeroLiveTiming | null;
  stripCards?: HeroStripCards;
  youtubeLive?: YoutubeLivePayload | null;
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

/** Citation courte pour la section d’accueil. */
export interface Testimony {
  id: string;
  name: string;
  quote: string;
  role?: string;
}

/** Témoignage complet affiché sur le mur public. */
export interface WallTestimony {
  id: string;
  kind: 'text' | 'video' | 'mix';
  author: string;
  title: string;
  text: string;
  video: string;
  videoFileUrl: string;
  videoEmbedUrl: string;
  videoThumbnailUrl?: string;
  postitColor: string;
  fontFamily: string;
  category: string;
  images: { id: string; url: string }[];
  shareCount?: number;
  reactableKey: string;
  sharePath?: string;
  createdAt: string;
  /** Date de validation / publication sur le mur (ISO 8601). */
  publishedAt?: string;
}

/** Statistiques globales du mur (pied de page). */
export interface WallStats {
  testimonies: number;
  reactions: number;
  shares: number;
}

/** Réglages dynamiques du mur (admin Filament). */
export interface TestimonyWallSettings {
  allowPhotoUpload: boolean;
  maxPhotosPerTestimony: number;
  allowYoutubeLink: boolean;
  allowVideoUpload: boolean;
  maxVideoUploadMb: number;
  allowAnonymous: boolean;
  requireFirstName: boolean;
  requireLastName: boolean;
}

export interface WallTestimonyMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  has_more: boolean;
  wall?: WallConfig;
  wallSettings?: TestimonyWallSettings;
  allowPhotoUpload?: boolean;
  reactionKeys?: Record<string, string>;
}

export interface WallConfig {
  categories: string[];
  postItColors: { name: string; value: string; border: string }[];
  fontStyles: { name: string; value: string }[];
  maxTitleLength?: number;
  maxTextLength: number;
  maxVideoUploadMb?: number;
  perPage: number;
}

export type TestimonySubmitPayload = {
  kind: 'text' | 'video' | 'mix';
  first_name: string;
  last_name?: string;
  title: string;
  text?: string;
  video?: string;
  video_source?: 'link' | 'upload';
  video_file?: File;
  postit_color?: string;
  font_family?: string;
  category?: string;
  email: string;
  phone?: string;
  is_anonymous?: boolean;
  verification_type?: 'email' | 'phone' | 'both';
  images?: File[];
  notify_live?: boolean;
  notify_events?: boolean;
};

export interface NavItem {
  label: string;
  href: string;
  children?: NavItem[];
}
