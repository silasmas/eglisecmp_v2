import type {
  PostsPageMeta,
  Sermon,
  Testimony,
  TestimonySubmitPayload,
  WallConfig,
  WallTestimony,
  WallTestimonyMeta,
} from '../data/types';

/**
 * Construit l'URL de base des endpoints `/api/site/*` consommés par la SPA.
 *
 * @returns Chaîne sans slash final (ex. `/api/site`).
 */
function siteApiBase(): string {
  const raw = import.meta.env.VITE_SITE_API_BASE as string | undefined;
  const trimmed = (raw ?? '/api/site').replace(/\/$/, '');

  return trimmed === '' ? '/api/site' : trimmed;
}

/**
 * Assemble l'URL complète d'un endpoint relatif au site public.
 *
 * @param route Chemin relatif commençant ou non par `/` (ex. `/events` ou `events`).
 * @returns URL absolue sur l'origine courante (ex. `https://exemple.test/api/site/events`).
 */
export function siteApiUrl(route: string): string {
  const path = route.startsWith('/') ? route : `/${route}`;

  return `${siteApiBase()}${path}`;
}

type SiteListResponse<T> = {
  data: T;
};

/**
 * Extrait un message lisible depuis une réponse JSON d’erreur Laravel / FlexPay.
 *
 * @param parsed Corps JSON déjà parsé (peut être null).
 * @returns Message utilisateur ou chaîne vide si introuvable.
 */
export function extractApiErrorMessage(parsed: unknown): string {
  if (parsed === null || typeof parsed !== 'object') {
    return '';
  }

  const root = parsed as Record<string, unknown>;

  if (typeof root.message === 'string' && root.message.trim() !== '') {
    return root.message.trim();
  }

  if (root.data !== null && typeof root.data === 'object') {
    const data = root.data as Record<string, unknown>;
    if (typeof data.message === 'string' && data.message.trim() !== '') {
      return data.message.trim();
    }
  }

  if (root.errors !== null && typeof root.errors === 'object') {
    const errors = root.errors as Record<string, unknown>;
    for (const value of Object.values(errors)) {
      if (Array.isArray(value) && value.length > 0 && typeof value[0] === 'string') {
        return value[0];
      }
      if (typeof value === 'string' && value.trim() !== '') {
        return value.trim();
      }
    }
  }

  return '';
}

/**
 * Effectue un GET JSON vers l'API site public.
 *
 * @param route Chemin relatif sous la base API (ex. `events?limit=10`).
 * @returns Corps JSON typé.
 * @throws Error si le statut HTTP n'est pas 2xx.
 */
export async function fetchSiteJson<T>(route: string): Promise<T> {
  const url = siteApiUrl(route.startsWith('/') ? route : `/${route}`);
  const response = await fetch(url, {
    headers: { Accept: 'application/json' },
  });

  if (!response.ok) {
    const parsed: unknown = await response.json().catch(() => null);
    const message = extractApiErrorMessage(parsed);
    throw new Error(message !== '' ? message : `Requête API échouée (${response.status})`);
  }

  return response.json() as Promise<T>;
}

/**
 * Lit la propriété `data` d'une enveloppe API `{ data: T }`.
 *
 * @param path Chemin sous la base API (ex. `hero-meta`).
 * @returns Valeur `data` typée.
 */
export async function fetchSiteData<T>(path: string): Promise<T> {
  const body = await fetchSiteJson<{ data: T }>(path);

  return body.data;
}

/**
 * Récupère un tableau `data` depuis une liste paginée simple `{ data: T[] }`.
 *
 * @param route Chemin relatif (ex. `events?limit=5`).
 * @returns Tableau extrait ou tableau vide si absent.
 */
export async function fetchSiteList<T>(route: string): Promise<T[]> {
  const body = await fetchSiteJson<SiteListResponse<T[]>>(route);

  return Array.isArray(body.data) ? body.data : [];
}

/**
 * Effectue un POST JSON vers l'API site public (réactions, etc.).
 *
 * @param route Chemin relatif (ex. `reactions`).
 * @param body Corps JSON sérialisable.
 * @returns Corps typé.
 */
export async function fetchSitePostJson<T>(route: string, body: Record<string, unknown>): Promise<T> {
  const url = siteApiUrl(route.startsWith('/') ? route : `/${route}`);
  const response = await fetch(url, {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(body),
  });

  if (!response.ok) {
    const parsed: unknown = await response.json().catch(() => null);
    const message = extractApiErrorMessage(parsed);
    throw new Error(message !== '' ? message : `Requête API échouée (${response.status})`);
  }

  return response.json() as Promise<T>;
}

type PostsPageResponse = {
  data: Sermon[];
  meta: PostsPageMeta;
};

/**
 * Charge une page de publications pour un onglet Enseignements.
 *
 * @param tab sermons | meditations | playlists.
 * @param page Numéro de page (1-based).
 * @param perPage Taille de page.
 */
export async function fetchSitePostsPage(
  tab: string,
  page: number,
  perPage = 12,
  options?: { search?: string; eventId?: string; weeklyServiceDay?: string },
): Promise<PostsPageResponse> {
  const query = new URLSearchParams({
    tab,
    page: String(page),
    per_page: String(perPage),
  });

  const trimmed = options?.search?.trim();
  if (trimmed !== undefined && trimmed !== '') {
    query.set('search', trimmed);
  }

  const eventId = options?.eventId?.trim();
  if (eventId !== undefined && eventId !== '') {
    query.set('event_id', eventId);
  }

  const weeklyDay = options?.weeklyServiceDay?.trim();
  if (weeklyDay !== undefined && weeklyDay !== '') {
    query.set('weekly_service_day', weeklyDay);
  }

  return fetchSiteJson<PostsPageResponse>(`posts?${query.toString()}`);
}

/**
 * Cultes hebdomadaires (playlists YouTube configurées).
 */
export async function fetchTeachingsMeditations(): Promise<import('../data/types').TeachingsPlaylistGroup[]> {
  const body = await fetchSiteJson<{ data: import('../data/types').TeachingsPlaylistGroup[] }>(
    'teachings/meditations',
  );
  return body.data ?? [];
}

type PlaylistsPageResponse = {
  data: import('../data/types').TeachingsPlaylistGroup[];
  meta?: import('../data/types').PlaylistsPageMeta;
};

/**
 * Playlists YouTube paginées (hors cultes hebdomadaires).
 *
 * @param page Numéro de page (1-based).
 * @param perPage Nombre de playlists par page (défaut 15).
 */
export async function fetchTeachingsPlaylistsPage(
  page = 1,
  perPage = 15,
): Promise<PlaylistsPageResponse> {
  const query = new URLSearchParams({
    page: String(page),
    per_page: String(perPage),
  });

  return fetchSiteJson<PlaylistsPageResponse>(`teachings/playlists?${query.toString()}`);
}

/**
 * Détail playlist (messages + identifiant YouTube).
 */
export async function fetchSitePlaylistDetail(
  eventId: string,
): Promise<import('../data/types').TeachingsPlaylistGroup> {
  const body = await fetchSiteJson<{ data: import('../data/types').TeachingsPlaylistGroup }>(
    `teachings/playlists/${encodeURIComponent(eventId)}`,
  );
  return body.data;
}

/**
 * Recherche globale site (pasteurs, messages, événements, pages).
 */
export async function fetchSiteSearch(q: string): Promise<import('../data/types').SiteSearchHit[]> {
  const query = new URLSearchParams({ q: q.trim() });
  const body = await fetchSiteJson<{ data: import('../data/types').SiteSearchHit[] }>(
    `search?${query.toString()}`,
  );
  return body.data ?? [];
}

/**
 * Données page Bunda (dernière édition, archives, annonce).
 */
export async function fetchSiteBunda(): Promise<import('../data/types').BundaPageData> {
  const body = await fetchSiteJson<{ data: import('../data/types').BundaPageData | null }>('bunda');
  const fallbackYear = new Date().getFullYear();
  const fallback: import('../data/types').BundaPageData = {
    intro: {
      title: 'Conférence Bunda',
      subtitle: '',
      body: '',
      heroImage: '',
      mealPlanUrl: null,
      mealPlanLabel: 'Plan alimentaire',
    },
    upcoming: {
      title: `Bunda ${fallbackYear}`,
      monthLabel: 'Novembre',
      year: fallbackYear,
      description: '',
    },
    editions: [],
    latestEdition: null,
    pastEditions: [],
  };

  if (body.data === null || body.data === undefined) {
    return fallback;
  }

  const raw = body.data;

  return {
    intro: {
      title: raw.intro?.title ?? fallback.intro.title,
      subtitle: raw.intro?.subtitle ?? '',
      body: raw.intro?.body ?? '',
      heroImage: raw.intro?.heroImage ?? '',
      mealPlanUrl: raw.intro?.mealPlanUrl ?? null,
      mealPlanLabel: raw.intro?.mealPlanLabel ?? fallback.intro.mealPlanLabel,
    },
    upcoming: {
      title: raw.upcoming?.title ?? fallback.upcoming.title,
      monthLabel: raw.upcoming?.monthLabel ?? fallback.upcoming.monthLabel,
      year: raw.upcoming?.year ?? fallback.upcoming.year,
      description: raw.upcoming?.description ?? '',
    },
    editions: Array.isArray(raw.editions) ? raw.editions : [],
    latestEdition: raw.latestEdition ?? null,
    pastEditions: Array.isArray(raw.pastEditions) ? raw.pastEditions : [],
  };
}

/**
 * Détail JSON d’un message par identifiant (page de lecture avec liste latérale).
 *
 * @param postId Identifiant numérique du post Laravel.
 */
export async function fetchSiteSermonById(postId: string): Promise<Sermon> {
  const body = await fetchSiteJson<{ data: Sermon }>(`/posts/${encodeURIComponent(postId)}`);

  return body.data;
}

/**
 * Charge toutes les pages d'une playlist donnée (filtrage serveur par `event_id`).
 *
 * @param eventId Identifiant événement lié aux messages.
 * @param options Options de filtrage (jour de culte, recherche).
 * @param perPage Taille de pagination (32–48 recommandé pour limiter les requêtes).
 */
export async function fetchSitePlaylistPosts(
  eventId: string,
  options: { weeklyServiceDay?: string; search?: string } = {},
  perPage = 48,
): Promise<Sermon[]> {
  const aggregated: Sermon[] = [];
  let page = 1;
  const maxPages = 30;
  const weeklyDay = (options.weeklyServiceDay ?? '').trim();
  const search = (options.search ?? '').trim();

  while (page <= maxPages) {
    const query = new URLSearchParams({
      tab: weeklyDay !== '' ? 'meditations' : 'playlists',
      page: String(page),
      per_page: String(perPage),
      event_id: eventId,
    });

    if (weeklyDay !== '') {
      query.set('weekly_service_day', weeklyDay);
    }

    if (search !== '') {
      query.set('search', search);
    }

    const chunk = await fetchSiteJson<PostsPageResponse>(`/posts?${query.toString()}`);
    aggregated.push(...chunk.data);

    if (!chunk.meta?.has_more) {
      break;
    }
    page += 1;
  }

  return aggregated;
}

/**
 * Charge tous les messages d’un jour de culte hebdomadaire (méditations).
 *
 * @param weeklyServiceDay Jour (`mercredi`, `jeudi`, `dimanche`).
 * @param search Filtre texte optionnel.
 * @param perPage Taille de page API.
 */
export async function fetchSiteMeditationPostsByDay(
  weeklyServiceDay: string,
  search = '',
  perPage = 48,
): Promise<Sermon[]> {
  const aggregated: Sermon[] = [];
  let page = 1;
  const maxPages = 30;
  const day = weeklyServiceDay.trim();
  const searchToken = search.trim();

  if (day === '') {
    return aggregated;
  }

  while (page <= maxPages) {
    const query = new URLSearchParams({
      tab: 'meditations',
      page: String(page),
      per_page: String(perPage),
      weekly_service_day: day,
    });

    if (searchToken !== '') {
      query.set('search', searchToken);
    }

    const chunk = await fetchSiteJson<PostsPageResponse>(`/posts?${query.toString()}`);
    aggregated.push(...chunk.data);

    if (!chunk.meta?.has_more) {
      break;
    }
    page += 1;
  }

  return aggregated;
}

export async function fetchReactionKeyLabels(): Promise<Record<string, string>> {
  const body = await fetchSiteJson<{ data: { reactionKeys: Record<string, string> } }>('reaction-keys');

  return body.data?.reactionKeys ?? {};
}

/** Ligne type d’offrande (Filament / table `offrandes`). */
export type SiteOffrandeRow = {
  id: number;
  nom: string;
  description: string | null;
};

/** Charge les types d’offrandes actifs pour la page « Offrandes ». */
export async function fetchOffrandesList(): Promise<SiteOffrandeRow[]> {
  return fetchSiteList<SiteOffrandeRow>('offrandes');
}

/** Opérateur Mobile Money (validation UI — le type API FlexPay reste "1"). */
export type SiteMobileMoneyProvider = {
  type: string;
  code: string;
  label: string;
  msisdn_regex: string;
};

/** Liste les opérateurs Mobile Money configurés (M-Pesa, Airtel, Orange, Afri…). */
export async function fetchOffrandeMobileProviders(): Promise<SiteMobileMoneyProvider[]> {
  const body = await fetchSiteJson<{ data: SiteMobileMoneyProvider[] }>('offrandes/mobile-providers');
  return body.data ?? [];
}

export type InitOffrandePayload = {
  offrande_id: number;
  montant: number;
  currency: 'CDF' | 'USD';
  fullname?: string;
  phone?: string;
  email?: string;
  message?: string;
};

export type InitOffrandeResponse = {
  reference: string;
  montant: number;
  currency: string;
};

/** Crée une transaction locale avant paiement FlexPay. */
export async function initOffrandeTransaction(payload: InitOffrandePayload): Promise<InitOffrandeResponse> {
  const body = await fetchSitePostJson<{ data: InitOffrandeResponse }>(
    'offrandes/init',
    payload as unknown as Record<string, unknown>,
  );
  return body.data;
}

export type ProcessOffrandePayload = {
  reference: string;
  channel: 'mobile_money' | 'card';
  phone?: string;
  provider_code?: string;
};

/** Lance le paiement mobile ou carte (URL de redirection pour la carte si succès). */
export async function processOffrandePayment(payload: ProcessOffrandePayload): Promise<{
  channel: string;
  success: boolean;
  redirect_url?: string;
  reference: string;
  message?: string;
  orderNumber?: string | null;
}> {
  const body = await fetchSitePostJson<{ data: Record<string, unknown> }>(
    'offrandes/process',
    payload as unknown as Record<string, unknown>,
  );
  return body.data as {
    channel: string;
    success: boolean;
    redirect_url?: string;
    reference: string;
    message?: string;
    orderNumber?: string | null;
  };
}

/** Polling FlexPay après initiation mobile money. */
export async function fetchOffrandePaymentStatus(reference: string): Promise<{
  paid: boolean;
  pending: boolean;
  cancelled?: boolean;
  flexpay_status?: number;
  reference?: string;
  message?: string;
  failure_message?: string;
}> {
  const query = new URLSearchParams({ reference });
  const res = await fetchSiteJson<{ data: Record<string, unknown> }>(`offrandes/status?${query.toString()}`);
  return res.data as {
    paid: boolean;
    pending: boolean;
    cancelled?: boolean;
    flexpay_status?: number;
    reference?: string;
    message?: string;
    failure_message?: string;
  };
}

export type SiteInquiryKind = 'prayer_request' | 'appointment';

/**
 * Envoie une demande publique (prière ou rendez-vous) vers le serveur Laravel.
 *
 * @param payload Corps validé côté API (`kind`, `name`, `message`, champs optionnels).
 * @returns Confirmation `{ ok: true }` si l’enregistrement a réussi.
 */
export type AppointmentMinisterRow = {
  id: number;
  fullname: string;
  image_url: string;
  bio: string;
};

export type AppointmentSlotRow = {
  starts_at: string;
  ends_at: string;
  label: string;
};

export type LeadershipMinisterRow = {
  id: number;
  fullname: string;
  image_url: string;
  bio: string;
  role: string;
  is_titular: boolean;
};

/** Tous les pasteurs actifs (page Leadership). */
export async function fetchPublicMinisters(): Promise<LeadershipMinisterRow[]> {
  return fetchSiteList<LeadershipMinisterRow>('ministers');
}

/** Pasteurs avec horaires de réception pour les rendez-vous. */
export async function fetchAppointmentMinisters(): Promise<AppointmentMinisterRow[]> {
  return fetchSiteList<AppointmentMinisterRow>('appointments/ministers');
}

/** Dates disponibles (Y-m-d) pour un pasteur. */
export async function fetchAppointmentDates(ministerId: number): Promise<string[]> {
  const query = new URLSearchParams({ minister_id: String(ministerId) });
  const body = await fetchSiteData<{ dates: string[] }>(`appointments/dates?${query.toString()}`);
  return Array.isArray(body.dates) ? body.dates : [];
}

/** Créneaux disponibles pour un pasteur à une date. */
export async function fetchAppointmentSlots(ministerId: number, date: string): Promise<AppointmentSlotRow[]> {
  const query = new URLSearchParams({ minister_id: String(ministerId), date });
  const body = await fetchSiteData<{ slots: AppointmentSlotRow[] }>(`appointments/slots?${query.toString()}`);
  return Array.isArray(body.slots) ? body.slots : [];
}

export async function submitSiteInquiry(payload: {
  kind: SiteInquiryKind;
  name: string;
  message: string;
  email?: string;
  phone?: string;
  country?: string;
  is_anonymous?: boolean;
  preferred_at?: string;
  minister_id?: number;
  appointment_reason?: string;
}): Promise<{ ok: boolean }> {
  const body: Record<string, unknown> = {
    kind: payload.kind,
    name: payload.name,
    message: payload.message,
  };
  if (payload.email !== undefined && payload.email.trim() !== '') {
    body.email = payload.email.trim();
  }
  if (payload.phone !== undefined && payload.phone.trim() !== '') {
    body.phone = payload.phone.trim();
  }
  if (payload.country !== undefined && payload.country.trim() !== '') {
    body.country = payload.country.trim();
  }
  if (payload.is_anonymous !== undefined) {
    body.is_anonymous = payload.is_anonymous;
  }
  if (payload.preferred_at !== undefined && payload.preferred_at.trim() !== '') {
    body.preferred_at = payload.preferred_at.trim();
  }
  if (payload.minister_id !== undefined) {
    body.minister_id = payload.minister_id;
  }
  if (payload.appointment_reason !== undefined && payload.appointment_reason.trim() !== '') {
    body.appointment_reason = payload.appointment_reason.trim();
  }

  const res = await fetchSitePostJson<{ data: { ok: boolean } }>('inquiries', body);
  return { ok: Boolean(res.data?.ok) };
}

type WallTestimoniesPageResponse = {
  data: WallTestimony[];
  meta: WallTestimonyMeta;
};

/**
 * Charge une page du mur de témoignages approuvés.
 */
export async function fetchWallTestimoniesPage(
  page: number,
  options?: { category?: string; kind?: string; perPage?: number },
): Promise<WallTestimoniesPageResponse> {
  const query = new URLSearchParams({
    page: String(page),
    per_page: String(options?.perPage ?? 12),
  });
  const category = options?.category?.trim();
  if (category !== undefined && category !== '' && category.toLowerCase() !== 'tous') {
    query.set('category', category);
  }
  if (options?.kind !== undefined && options.kind !== '') {
    query.set('kind', options.kind);
  }

  return fetchSiteJson<WallTestimoniesPageResponse>(`testimonies?${query.toString()}`);
}

/**
 * Témoignages mis en avant pour la page d’accueil.
 */
export async function fetchFeaturedTestimonies(): Promise<Testimony[]> {
  return fetchSiteList<Testimony>('testimonies/featured');
}

/**
 * Configuration du mur (catégories, couleurs, polices).
 */
export async function fetchWallConfig(): Promise<{
  wall: WallConfig;
  wallSettings: import('../data/types').TestimonyWallSettings;
  allowPhotoUpload: boolean;
  reactionKeys: Record<string, string>;
}> {
  const body = await fetchSiteData<{
    wall: WallConfig;
    wallSettings?: import('../data/types').TestimonyWallSettings;
    allowPhotoUpload: boolean;
    reactionKeys: Record<string, string>;
  }>('testimonies/wall-config');
  const wallSettings = body.wallSettings ?? {
    allowPhotoUpload: Boolean(body.allowPhotoUpload),
    maxPhotosPerTestimony: 5,
    allowYoutubeLink: true,
    allowVideoUpload: true,
    maxVideoUploadMb: body.wall?.maxVideoUploadMb ?? 5,
    allowAnonymous: true,
    requireFirstName: true,
    requireLastName: false,
  };
  return {
    wall: body.wall,
    wallSettings,
    allowPhotoUpload: wallSettings.allowPhotoUpload,
    reactionKeys: body.reactionKeys ?? {},
  };
}

/**
 * Témoignages pour le carrousel hero.
 */
export async function fetchWallCarousel(limit = 24): Promise<WallTestimony[]> {
  return fetchSiteList<WallTestimony>(`testimonies/carousel?limit=${limit}`);
}

/**
 * Statistiques du mur (compteurs pied de page).
 */
export async function fetchWallStats(): Promise<import('../data/types').WallStats> {
  return fetchSiteData<import('../data/types').WallStats>('testimonies/stats');
}

/**
 * Enregistre un partage social pour un témoignage.
 */
export async function recordTestimonyShare(testimonyId: string): Promise<void> {
  await fetchSitePostJson<{ data: { shareCount: number } }>(`testimonies/${testimonyId}/share`, {});
}

/**
 * Envoie un témoignage (multipart) en attente de modération.
 */
/**
 * Envoie un témoignage avec suivi de progression upload (XHR).
 */
export function submitTestimony(
  payload: TestimonySubmitPayload,
  onProgress?: (percent: number) => void,
): Promise<{ ok: boolean; message: string }> {
  const form = new FormData();
  form.append('kind', payload.kind);
  form.append('first_name', payload.first_name);
  if (payload.last_name !== undefined && payload.last_name.trim() !== '') {
    form.append('last_name', payload.last_name.trim());
  }
  form.append('title', payload.title);
  form.append('email', payload.email);
  if (payload.text !== undefined && payload.text.trim() !== '') {
    form.append('text', payload.text.trim());
  }
  if (payload.video !== undefined && payload.video.trim() !== '') {
    form.append('video', payload.video.trim());
  }
  if (payload.postit_color !== undefined) {
    form.append('postit_color', payload.postit_color);
  }
  if (payload.font_family !== undefined) {
    form.append('font_family', payload.font_family);
  }
  if (payload.category !== undefined && payload.category.trim() !== '') {
    form.append('category', payload.category.trim());
  }
  if (payload.phone !== undefined && payload.phone.trim() !== '') {
    form.append('phone', payload.phone.trim());
  }
  if (payload.verification_type !== undefined) {
    form.append('verification_type', payload.verification_type);
  }
  if (payload.is_anonymous !== undefined) {
    form.append('is_anonymous', payload.is_anonymous ? '1' : '0');
  }
  if (payload.notify_live === true) {
    form.append('notify_live', '1');
  }
  if (payload.notify_events === true) {
    form.append('notify_events', '1');
  }
  if (payload.video_source !== undefined) {
    form.append('video_source', payload.video_source);
  }
  if (payload.video_file !== undefined) {
    form.append('video_file', payload.video_file);
  }
  if (payload.images !== undefined) {
    payload.images.forEach((file) => {
      form.append('images[]', file);
    });
  }

  const url = siteApiUrl('/testimonies');

  return new Promise((resolve, reject) => {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', url);
    xhr.setRequestHeader('Accept', 'application/json');

    xhr.upload.addEventListener('progress', (event) => {
      if (event.lengthComputable && onProgress !== undefined) {
        onProgress(Math.min(100, Math.round((event.loaded / event.total) * 100)));
      }
    });

    xhr.addEventListener('load', () => {
      let parsed: unknown = null;
      try {
        parsed = JSON.parse(xhr.responseText);
      } catch {
        parsed = null;
      }

      if (xhr.status < 200 || xhr.status >= 300) {
        const message = extractApiErrorMessage(parsed);
        reject(new Error(message !== '' ? message : `Envoi impossible (${xhr.status})`));
        return;
      }

      const res = parsed as { data: { ok: boolean; message?: string } };
      onProgress?.(100);
      resolve({
        ok: Boolean(res.data?.ok),
        message: res.data?.message ?? 'Merci pour votre témoignage.',
      });
    });

    xhr.addEventListener('error', () => {
      reject(new Error('Erreur réseau lors de l’envoi.'));
    });

    xhr.send(form);
  });
}

export type AlertSubscribePayload = {
  email?: string;
  phone?: string;
  name?: string;
  notify_live: boolean;
  notify_events: boolean;
  source: 'footer' | 'events' | 'live' | 'testimony' | 'bunda' | 'weekly';
};

/**
 * Inscription opt-in aux alertes live et événements.
 */
export async function subscribeToAlerts(payload: AlertSubscribePayload): Promise<{ message: string }> {
  const response = await fetch(siteApiUrl('/alert-subscriptions'), {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      email: payload.email?.trim() || undefined,
      phone: payload.phone?.trim() || undefined,
      name: payload.name?.trim() || undefined,
      notify_live: payload.notify_live,
      notify_events: payload.notify_events,
      source: payload.source,
    }),
  });

  let parsed: unknown = null;
  try {
    parsed = await response.json();
  } catch {
    parsed = null;
  }

  if (!response.ok) {
    const message = extractApiErrorMessage(parsed);
    throw new Error(message !== '' ? message : `Inscription impossible (${response.status})`);
  }

  const res = parsed as { data?: { message?: string } };

  return {
    message: res.data?.message ?? 'Merci ! Vous recevrez nos alertes selon vos choix.',
  };
}

/**
 * Désabonnement via le jeton reçu par e-mail.
 */
export async function unsubscribeFromAlerts(token: string): Promise<{ message: string }> {
  const response = await fetch(siteApiUrl(`/alert-subscriptions/unsubscribe/${encodeURIComponent(token)}`), {
    method: 'POST',
    headers: { Accept: 'application/json' },
  });

  let parsed: unknown = null;
  try {
    parsed = await response.json();
  } catch {
    parsed = null;
  }

  if (!response.ok) {
    const message = extractApiErrorMessage(parsed);
    throw new Error(message !== '' ? message : 'Lien invalide ou expiré.');
  }

  const res = parsed as { data?: { message?: string } };

  return {
    message: res.data?.message ?? 'Vous êtes désabonné(e) des alertes.',
  };
}

export type ChildPresentationMeta = {
  dates: Array<{ date: string; label: string }>;
  ecodim_entry_age_years: number;
  max_document_mb: number;
  requirements: string[];
};

export type ChildPresentationPayload = {
  children_count: number;
  parent_names: string;
  phone: string;
  otp_code: string;
  presentation_date: string;
  children: Array<{ full_name: string; gender: 'male' | 'female'; age_years: number; age_months: number }>;
  birth_certificate: File;
  parent_id_document: File;
};

/**
 * Charge les dates disponibles et les consignes de présentation d'enfants.
 */
export async function fetchChildPresentationMeta(): Promise<ChildPresentationMeta> {
  return fetchSiteData<ChildPresentationMeta>('child-presentations/meta');
}

/**
 * Message ECODIM selon l'âge saisi (temps restant avant l'école du dimanche).
 */
export async function getEcodimHint(
  ageYears: number,
  ageMonths = 0,
): Promise<{ eligible: boolean; message: string; months_remaining: number }> {
  return fetchSiteData<{ eligible: boolean; message: string; months_remaining: number }>(
    `child-presentations/ecodim-hint?age_years=${encodeURIComponent(String(ageYears))}&age_months=${encodeURIComponent(String(ageMonths))}`,
  );
}

/**
 * Envoie un code OTP SMS pour authentifier le numéro du parent.
 */
export async function sendChildPresentationOtp(phone: string): Promise<{ ok: boolean; message: string }> {
  const body = await fetchSitePostJson<{ data: { ok: boolean; message: string } }>(
    'child-presentations/otp/send',
    { phone },
  );

  return body.data;
}

/**
 * Vérifie le code OTP reçu par SMS.
 */
export async function verifyChildPresentationOtp(
  phone: string,
  otpCode: string,
): Promise<{ ok: boolean; verified: boolean; message: string }> {
  const body = await fetchSitePostJson<{ data: { ok: boolean; verified: boolean; message: string } }>(
    'child-presentations/otp/verify',
    { phone, otp_code: otpCode },
  );

  return body.data;
}

/**
 * Soumet une demande de présentation d'enfants (multipart).
 */
export async function submitChildPresentation(
  payload: ChildPresentationPayload,
): Promise<{ ok: boolean; id: number; message: string }> {
  const form = new FormData();
  form.append('children_count', String(payload.children_count));
  form.append('parent_names', payload.parent_names);
  form.append('phone', payload.phone);
  form.append('otp_code', payload.otp_code);
  form.append('presentation_date', payload.presentation_date);
  payload.children.forEach((child, index) => {
    form.append(`children[${index}][full_name]`, child.full_name);
    form.append(`children[${index}][gender]`, child.gender);
    form.append(`children[${index}][age_years]`, String(child.age_years));
    form.append(`children[${index}][age_months]`, String(child.age_months));
  });
  form.append('birth_certificate', payload.birth_certificate);
  form.append('parent_id_document', payload.parent_id_document);

  const response = await fetch(siteApiUrl('/child-presentations'), {
    method: 'POST',
    headers: { Accept: 'application/json' },
    body: form,
  });

  let parsed: unknown = null;
  try {
    parsed = await response.json();
  } catch {
    parsed = null;
  }

  if (!response.ok) {
    const message = extractApiErrorMessage(parsed);
    throw new Error(message !== '' ? message : `Envoi impossible (${response.status})`);
  }

  const res = parsed as { data?: { ok?: boolean; id?: number; message?: string } };

  return {
    ok: res.data?.ok === true,
    id: res.data?.id ?? 0,
    message: res.data?.message ?? 'Demande envoyée.',
  };
}

export type PublicChurchExtension = {
  id: string;
  name: string;
  city: string;
  country: string;
  address: string;
  description: string;
  lat: number;
  lng: number;
  leaderName: string;
  leaderPhotoUrl: string;
};

/**
 * Liste des extensions CMP actives (carte mondiale).
 */
export async function fetchChurchExtensions(): Promise<PublicChurchExtension[]> {
  return fetchSiteList<PublicChurchExtension>('extensions');
}

export type WorshipServiceTypeOption = {
  value: string;
  label: string;
};

/**
 * Types de culte disponibles pour le formulaire protocole.
 */
export async function fetchWorshipReportMeta(): Promise<{ service_types: WorshipServiceTypeOption[] }> {
  return fetchSiteData<{ service_types: WorshipServiceTypeOption[] }>('worship-reports/meta');
}

/**
 * Vérifie qu'un numéro est enregistré dans l'équipe protocole.
 */
export async function lookupWorshipReporterPhone(
  phone: string,
): Promise<{ ok: boolean; name: string; phone: string }> {
  const body = await fetchSitePostJson<{ data: { ok: boolean; name: string; phone: string } }>(
    'worship-reports/lookup-phone',
    { phone },
  );

  return body.data;
}

/**
 * Envoie un OTP SMS au rapporteur protocole.
 */
export async function sendWorshipReportOtp(phone: string): Promise<{ ok: boolean; message: string }> {
  const body = await fetchSitePostJson<{ data: { ok: boolean; message: string } }>(
    'worship-reports/otp/send',
    { phone },
  );

  return body.data;
}

/**
 * Vérifie le code OTP du rapporteur protocole.
 */
export async function verifyWorshipReportOtp(
  phone: string,
  otpCode: string,
): Promise<{ ok: boolean; verified: boolean; message: string }> {
  const body = await fetchSitePostJson<{ data: { ok: boolean; verified: boolean; message: string } }>(
    'worship-reports/otp/verify',
    { phone, otp_code: otpCode },
  );

  return body.data;
}

/**
 * Envoie un rapport de présence de culte (OTP déjà vérifié).
 */
export async function submitWorshipReport(payload: {
  service_date: string;
  service_type: string;
  attendees_count: number;
  report_text: string;
  submitted_by?: string;
  phone: string;
  otp_code: string;
}): Promise<{ ok: boolean; id: number; message: string }> {
  const body = await fetchSitePostJson<{ data: { ok: boolean; id: number; message: string } }>(
    'worship-reports',
    payload,
  );

  return body.data;
}
export type WorkerDepartmentOption = {
  id: number;
  name: string;
  slug: string;
  description: string;
  color: string;
};

export type WorkerRegistrationMeta = {
  departments: WorkerDepartmentOption[];
  communes: string[];
  cities: string[];
  genders: Array<{ value: string; label: string }>;
  education_levels: string[];
};

export type WorkerBadgeData = {
  token: string;
  fullName: string;
  firstName: string;
  lastName: string;
  gender: string;
  department: string;
  departmentColor: string;
  departmentRole: string;
  photoUrl: string;
  status: string;
  badgeValidated: boolean;
  badgeGenerated: boolean;
  phone: string | null;
  commune: string;
  city: string;
};

/** M�tadonn�es wizard inscription ouvrier. */
export async function fetchWorkerRegistrationMeta(): Promise<WorkerRegistrationMeta> {
  return fetchSiteData<WorkerRegistrationMeta>('workers/meta');
}

/** Envoie un OTP e-mail pour l inscription ouvrier. */
export async function sendWorkerEmailOtp(email: string): Promise<{ ok: boolean; message: string }> {
  const body = await fetchSitePostJson<{ data: { ok: boolean; message: string } }>('workers/otp/send', { email });
  return body.data;
}

/** V�rifie l OTP e-mail ouvrier. */
export async function verifyWorkerEmailOtp(
  email: string,
  otpCode: string,
): Promise<{ ok: boolean; verified: boolean; message: string }> {
  const body = await fetchSitePostJson<{ data: { ok: boolean; verified: boolean; message: string } }>(
    'workers/otp/verify',
    { email, otp_code: otpCode },
  );
  return body.data;
}

/** Soumet l inscription ouvrier (multipart). */
export async function submitWorkerRegistration(form: FormData): Promise<{ ok: boolean; id: number; message: string }> {
  const response = await fetch(siteApiUrl('/workers'), {
    method: 'POST',
    headers: { Accept: 'application/json' },
    body: form,
  });

  let parsed: unknown = null;
  try {
    parsed = await response.json();
  } catch {
    parsed = null;
  }

  if (!response.ok) {
    const message = extractApiErrorMessage(parsed);
    throw new Error(message !== '' ? message : `Envoi impossible (${response.status})`);
  }

  const res = parsed as { data?: { ok?: boolean; id?: number; message?: string } };
  return {
    ok: res.data?.ok === true,
    id: res.data?.id ?? 0,
    message: res.data?.message ?? 'Inscription envoy�e.',
  };
}

/** Donn�es publiques du badge ouvrier. */
export async function fetchWorkerBadge(token: string): Promise<WorkerBadgeData> {
  return fetchSiteData<WorkerBadgeData>(`workers/badge/${encodeURIComponent(token)}`);
}
