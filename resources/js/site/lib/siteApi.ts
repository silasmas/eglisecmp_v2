import type { PostsPageMeta, Sermon } from '../data/types';

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
    throw new Error(`Requête API échouée (${response.status})`);
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
    const message =
      parsed !== null &&
      typeof parsed === 'object' &&
      'message' in parsed &&
      typeof (parsed as { message?: unknown }).message === 'string'
        ? (parsed as { message: string }).message
        : '';

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
  options?: { search?: string },
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

  return fetchSiteJson<PostsPageResponse>(`posts?${query.toString()}`);
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
 * @param perPage Taille de pagination (32–48 recommandé pour limiter les requêtes).
 */
export async function fetchSitePlaylistPosts(eventId: string, perPage = 48): Promise<Sermon[]> {
  const aggregated: Sermon[] = [];
  let page = 1;
  const maxPages = 30;

  while (page <= maxPages) {
    const query = new URLSearchParams({
      tab: 'playlists',
      page: String(page),
      per_page: String(perPage),
      event_id: eventId,
    });
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
  preferred_at?: string;
  minister_id?: number;
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
  if (payload.preferred_at !== undefined && payload.preferred_at.trim() !== '') {
    body.preferred_at = payload.preferred_at.trim();
  }
  if (payload.minister_id !== undefined) {
    body.minister_id = payload.minister_id;
  }

  const res = await fetchSitePostJson<{ data: { ok: boolean } }>('inquiries', body);
  return { ok: Boolean(res.data?.ok) };
}