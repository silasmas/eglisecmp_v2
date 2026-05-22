<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\DailyVerse;
use App\Models\Event;
use App\Models\Gallery;
use App\Models\Post;
use App\Models\ScheduleProgram;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Sérialise les modèles du site vers le format JSON attendu par la SPA publique.
 */
final class SitePublicSerializer
{
    /**
     * Déduit la locale à appliquer aux champs multilingues (JSON / tableaux).
     *
     * @param  Request  $request  Requête HTTP (priorité au query string `locale`).
     * @return string Code locale courte (ex. fr, en).
     */
    public static function localeFromRequest(Request $request): string
    {
        $fromQuery = $request->query('locale');

        if (is_string($fromQuery) && $fromQuery !== '' && strlen($fromQuery) <= 12 && preg_match('/^[a-zA-Z_-]+$/', $fromQuery) === 1) {
            return strtolower(str_replace('_', '-', $fromQuery));
        }

        return app()->getLocale();
    }

    /**
     * Retourne la locale de secours définie dans la configuration de l'application.
     *
     * @return string Code locale (ex. en).
     */
    public static function fallbackLocale(): string
    {
        return (string) config('app.fallback_locale', 'en');
    }

    /**
     * Extrait un texte affichable depuis une valeur multilingue ou une chaîne brute.
     *
     * @param  mixed  $value  Valeur du modèle (tableau associatif, JSON encodé, ou chaîne).
     * @param  string  $locale  Locale préférée.
     * @param  string  $fallbackLocale  Locale utilisée si la clé préférée est absente.
     * @return string Texte non null (chaîne vide si aucune donnée exploitable).
     */
    public static function text(mixed $value, string $locale, string $fallbackLocale): string
    {
        $decoded = self::normalizeToArray($value);

        if ($decoded === null) {
            return is_string($value) ? trim($value) : '';
        }

        $primary = $decoded[$locale] ?? null;
        if (is_string($primary) && $primary !== '') {
            return trim($primary);
        }

        $secondary = $decoded[$fallbackLocale] ?? null;
        if (is_string($secondary) && $secondary !== '') {
            return trim($secondary);
        }

        foreach ($decoded as $item) {
            if (is_string($item) && trim($item) !== '') {
                return trim($item);
            }
        }

        return '';
    }

    /**
     * Extrait une URL d'image depuis un champ image multilingue ou une URL simple.
     *
     * @param  mixed  $value  Valeur brute du champ image.
     * @param  string  $locale  Locale préférée.
     * @param  string  $fallbackLocale  Locale de repli.
     * @return string URL absolue exploitable par le navigateur, ou chaîne vide.
     */
    public static function imageUrl(mixed $value, string $locale, string $fallbackLocale): string
    {
        $raw = '';

        if (is_string($value)) {
            $trimmed = trim($value);

            if ($trimmed === '') {
                return '';
            }

            $decoded = json_decode($trimmed, true);

            if (! is_array($decoded)) {
                $raw = $trimmed;
            } else {
                $value = $decoded;
            }
        }

        if ($raw === '' && is_array($value)) {
            $candidate = $value[$locale] ?? $value[$fallbackLocale] ?? null;

            if (is_string($candidate) && $candidate !== '') {
                $raw = $candidate;
            } else {
                foreach ($value as $item) {
                    if (is_string($item) && $item !== '') {
                        $raw = $item;
                        break;
                    }
                }
            }
        }

        if ($raw === '') {
            return '';
        }

        return self::normalizePublicImageUrl($raw);
    }

    /**
     * Transforme un événement actif en tableau pour la SPA (section événements).
     *
     * @param  Event  $event  Modèle événement.
     * @param  string  $locale  Locale pour designation / description / image.
     * @param  string  $fallbackLocale  Locale de repli.
     * @return array<string, mixed> Objet compatible avec le type `Event` côté TypeScript.
     */
    public static function eventToPublicArray(Event $event, string $locale, string $fallbackLocale): array
    {
        $placeholder = self::normalizePublicImageUrl((string) config('site_public.placeholder_image_url', ''));

        $title = self::text($event->designation, $locale, $fallbackLocale);
        $description = self::text($event->description, $locale, $fallbackLocale);
        $image = self::imageUrl($event->image_url, $locale, $fallbackLocale);
        $hasPoster = false;
        if ($image === '') {
            $image = $placeholder;
        } else {
            $hasPoster = $image !== $placeholder;
        }

        $lieuRaw = $event->getAttribute('lieu');
        $location = is_string($lieuRaw) ? trim($lieuRaw) : self::text($lieuRaw, $locale, $fallbackLocale);

        $start = $event->date_debut;
        $end = $event->date_fin;

        $dateStr = $start ? $start->format('Y-m-d') : '';
        $timeStr = self::formatTimeRange($start, $end);

        return [
            'id' => (string) $event->getKey(),
            'title' => $title !== '' ? $title : 'Événement',
            'date' => $dateStr,
            'time' => $timeStr,
            'location' => $location !== '' ? $location : '—',
            'description' => $description !== '' ? $description : '',
            'image' => $image,
            'hasPoster' => $hasPoster,
            'theme' => self::text($event->theme, $locale, $fallbackLocale),
            'featured' => $event->isFeaturedSpotlightNow(),
            'featuredFrom' => $event->featured_from?->toIso8601String(),
            'featuredUntil' => $event->featured_until?->toIso8601String(),
            'reactableKey' => 'event:'.$event->getKey(),
        ];
    }

    /**
     * Transforme une publication en objet « sermon » pour la SPA.
     *
     * @param  Post  $post  Modèle publication (relations minister chargée si possible).
     * @param  string  $locale  Locale pour title / body / image.
     * @param  string  $fallbackLocale  Locale de repli.
     * @return array<string, mixed> Objet compatible avec le type `Sermon` côté TypeScript.
     */
    public static function postToSermonArray(Post $post, string $locale, string $fallbackLocale): array
    {
        $placeholder = self::normalizePublicImageUrl((string) config('site_public.placeholder_image_url', ''));

        $title = self::text($post->title, $locale, $fallbackLocale);
        $body = self::text($post->body, $locale, $fallbackLocale);
        $bodyHtml = is_string($body) ? trim($body) : '';

        $youtubeEmbedUrl = self::youtubeEmbedUrlFromLink($post->link_url);

        $thumb = self::imageUrl($post->image_url, $locale, $fallbackLocale);
        if ($thumb === '') {
            $speaker = $post->getSpeakerImageUrl();
            $thumb = is_string($speaker) && $speaker !== '' ? self::normalizePublicImageUrl($speaker) : '';
        }
        if ($thumb === '') {
            $thumb = self::youtubeThumbnailFromLink($post->link_url);
        }
        if ($thumb === '') {
            $thumb = $placeholder;
        }

        $typeKey = (int) $post->type;
        $labels = (array) config('site_public.post_type_labels', []);
        $category = is_string($labels[$typeKey] ?? null)
            ? (string) $labels[$typeKey]
            : (string) ($labels[0] ?? 'Publication');

        $observationText = self::text($post->observation, $locale, $fallbackLocale);
        $excerpt = Str::limit(strip_tags($body !== '' ? $body : $observationText), 220);

        $published = $post->date_publication;
        $theme = self::text($post->references, $locale, $fallbackLocale);
        if ($theme === '') {
            $theme = self::text($post->observation, $locale, $fallbackLocale);
        }
        if ($theme === '') {
            $theme = $category;
        }

        $eventTitle = '';
        $eventImage = '';
        if ($post->relationLoaded('event') && $post->event instanceof Event) {
            $eventTitle = self::text($post->event->designation, $locale, $fallbackLocale);
            $eventImage = self::imageUrl($post->event->image_url, $locale, $fallbackLocale);
            if ($eventImage === '') {
                $eventImage = $placeholder;
            }
        }

        $link = $post->link_url;
        $linkUrl = is_string($link) && trim($link) !== '' ? trim($link) : '';

        $durationSeconds = $post->youtube_duration_seconds !== null ? (int) $post->youtube_duration_seconds : null;
        $durationLabel = YoutubeDurationParser::formatFrench($durationSeconds > 0 ? $durationSeconds : null);
        $weeklyDay = $post->weekly_service_day;
        $weeklyServiceDay = is_string($weeklyDay) && trim($weeklyDay) !== '' ? trim($weeklyDay) : null;

        return [
            'id' => (string) $post->getKey(),
            'title' => $title !== '' ? $title : 'Message',
            'speaker' => $post->getSpeakerName(),
            'date' => $published ? $published->format('Y-m-d') : '',
            'category' => $category,
            'type' => $typeKey,
            'thumbnail' => $thumb,
            'duration' => $durationLabel,
            'youtubeDurationSeconds' => $durationSeconds !== null && $durationSeconds > 0 ? $durationSeconds : null,
            'weeklyServiceDay' => $weeklyServiceDay,
            'description' => $excerpt,
            'bodyHtml' => $bodyHtml,
            'youtubeEmbedUrl' => $youtubeEmbedUrl !== '' ? $youtubeEmbedUrl : null,
            'linkUrl' => $linkUrl,
            'theme' => $theme !== '' ? $theme : 'Général',
            'eventId' => $post->event_id ? (string) $post->event_id : null,
            'eventTitle' => $eventTitle,
            'eventImage' => $eventImage,
            'reactableKey' => 'post:'.$post->getKey(),
        ];
    }

    /**
     * Transforme une entrée de galerie pour la grille médias de la SPA.
     *
     * @param  Gallery  $gallery  Modèle galerie.
     * @param  string  $locale  Locale pour la description / légende.
     * @param  string  $fallbackLocale  Locale de repli.
     * @return array<string, mixed> Objet compatible avec le type `GalleryItem` côté TypeScript.
     */
    public static function galleryToPublicArray(Gallery $gallery, string $locale, string $fallbackLocale): array
    {
        $placeholder = self::normalizePublicImageUrl((string) config('site_public.placeholder_image_url', ''));

        $src = self::imageUrl($gallery->image_url, $locale, $fallbackLocale);
        if ($src === '') {
            $src = $placeholder;
        }
        $caption = self::text($gallery->description, $locale, $fallbackLocale);

        return [
            'id' => (string) $gallery->getKey(),
            'src' => $src,
            'alt' => $caption !== '' ? $caption : 'Photo',
            'category' => 'Galerie',
        ];
    }

    /**
     * Sérialise un programme d'antenne pour la section « Nos rendez-vous ».
     *
     * @param  ScheduleProgram  $program  Enregistrement programme.
     * @param  string  $locale  Locale pour les champs JSON.
     * @param  string  $fallbackLocale  Locale de repli.
     * @return array<string, mixed> Objet JSON pour la SPA.
     */
    public static function scheduleProgramToPublicArray(ScheduleProgram $program, string $locale, string $fallbackLocale): array
    {
        $title = self::text($program->title, $locale, $fallbackLocale);
        $description = self::text((array) ($program->description ?? []), $locale, $fallbackLocale);

        if ($program->kind === ScheduleProgram::KIND_SEMINAR && $program->relationLoaded('event') && $program->event instanceof Event) {
            $fromEvent = self::text($program->event->designation, $locale, $fallbackLocale);
            if ($title === '' && $fromEvent !== '') {
                $title = $fromEvent;
            }
        }

        $thumb = self::imageUrl($program->image_url ?? [], $locale, $fallbackLocale);
        $banner = self::imageUrl($program->banner_image ?? [], $locale, $fallbackLocale);
        if ($banner === '') {
            $banner = $thumb;
        }
        $placeholder = self::normalizePublicImageUrl((string) config('site_public.placeholder_image_url', ''));
        if ($thumb === '') {
            $thumb = $placeholder;
        }
        if ($banner === '') {
            $banner = $placeholder;
        }

        return [
            'id' => (string) $program->getKey(),
            'kind' => $program->kind,
            'name' => $title !== '' ? $title : 'Programme',
            'description' => $description,
            'day' => (string) ($program->day_label ?? ''),
            'time' => (string) ($program->time_label ?? ''),
            'icon' => $program->icon_key !== '' ? $program->icon_key : 'book-open',
            'gridWide' => (bool) $program->grid_wide,
            'weekday' => $program->weekday,
            'liveHour' => $program->live_hour,
            'liveMinute' => $program->live_minute,
            'linkUrl' => (string) ($program->link_url ?? ''),
            'thumbnail' => $thumb,
            'bannerImage' => $banner,
            'reactableKey' => 'schedule_program:'.$program->getKey(),
            'isRecurring' => (bool) $program->is_recurring,
            'streamsLive' => (bool) $program->streams_live,
            'showInHeroStrip' => (bool) $program->show_in_hero_strip,
        ];
    }

    /**
     * Sérialise le verset du jour actuellement visible (fenêtre 24 h).
     *
     * @param  DailyVerse  $verse  Modèle verset.
     * @param  string  $locale  Locale pour référence et texte.
     * @param  string  $fallbackLocale  Locale de repli.
     * @return array<string, mixed> Objet JSON pour la SPA.
     */
    public static function dailyVerseToPublicArray(DailyVerse $verse, string $locale, string $fallbackLocale): array
    {
        $ref = self::text($verse->reference, $locale, $fallbackLocale);
        $body = self::text($verse->body, $locale, $fallbackLocale);
        $plainBody = strip_tags($body);
        $img = self::imageUrl($verse->image_url ?? [], $locale, $fallbackLocale);

        return [
            'id' => (string) $verse->getKey(),
            'label' => 'Lecture du jour',
            'reference' => $ref !== '' ? $ref : 'Verset',
            'text' => $body,
            'excerpt' => Str::limit($plainBody, 100),
            'thumbnail' => $img,
            'publishAt' => $verse->publish_at?->toIso8601String(),
            'visibleUntil' => $verse->visible_until?->toIso8601String(),
            'reactableKey' => 'daily_verse:'.$verse->getKey(),
        ];
    }

    /**
     * Carte « à la une » sur la page d'accueil à partir d'un post mis en avant.
     *
     * @param  Post  $post  Publication.
     * @param  string  $locale  Locale.
     * @param  string  $fallbackLocale  Locale de repli.
     * @return array<string, mixed> Objet JSON (titre, extrait, image, lien).
     */
    public static function featuredPostToHomeCardArray(Post $post, string $locale, string $fallbackLocale): array
    {
        $sermon = self::postToSermonArray($post, $locale, $fallbackLocale);

        return [
            'id' => $sermon['id'],
            'slug' => (string) ($post->slug ?? ''),
            'title' => $sermon['title'],
            'excerpt' => $sermon['description'],
            'image' => $sermon['thumbnail'],
            'href' => '/teachings/message/'.$post->getKey(),
            'speaker' => $sermon['speaker'],
            'reactableKey' => (string) ($sermon['reactableKey'] ?? ''),
            'youtubeEmbedUrl' => $sermon['youtubeEmbedUrl'] ?? null,
        ];
    }

    /**
     * Formate une plage horaire lisible à partir des dates de début et de fin.
     *
     * @param  Carbon|null  $start  Début de l'événement.
     * @param  Carbon|null  $end  Fin de l'événement (optionnelle).
     * @return string Texte affiché (ex. « 09:00 - 18:00 » ou date seule).
     */
    private static function formatTimeRange(?Carbon $start, ?Carbon $end): string
    {
        if ($start === null) {
            return '';
        }

        if ($end === null) {
            return $start->format('H:i');
        }

        if ($start->format('H:i') === $end->format('H:i')) {
            return $start->format('H:i');
        }

        return $start->format('H:i').' - '.$end->format('H:i');
    }

    /**
     * Convertit un chemin de fichier public (Filament, legacy) en URL absolue pour le client SPA.
     *
     * @param  string  $raw  Valeur brute (URL complète, chemin `/storage/...`, ou chemin relatif disque `public`).
     * @return string URL absolue ou chaîne vide si entrée vide.
     */
    public static function normalizePublicImageUrl(string $raw): string
    {
        $s = trim($raw);

        if ($s === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $s) === 1) {
            return $s;
        }

        if (str_starts_with($s, '//')) {
            $base = (string) config('app.url', '');
            $scheme = parse_url($base, PHP_URL_SCHEME);

            return (is_string($scheme) ? $scheme : 'https').':'.$s;
        }

        if (str_starts_with($s, '/')) {
            return url($s);
        }

        if (str_starts_with($s, 'storage/')) {
            return url('/'.ltrim($s, '/'));
        }

        return Storage::disk('public')->url($s);
    }

    /**
     * Retourne l'URL de la vignette YouTube standard à partir d'un lien ou d'un ID vidéo.
     *
     * @param  mixed  $linkUrl  Champ `link_url` du post (URL watch, youtu.be, ID seul, etc.).
     * @return string URL https vers i.ytimg.com, ou chaîne vide si aucun ID détecté.
     */
    private static function youtubeThumbnailFromLink(mixed $linkUrl): string
    {
        $id = self::resolveYoutubeVideoId($linkUrl);

        return $id === null ? '' : "https://i.ytimg.com/vi/{$id}/hqdefault.jpg";
    }

    /**
     * Extrait l'identifiant vidéo YouTube pour des appels externes (API Data, etc.).
     *
     * @param  mixed  $linkUrl  Champ `link_url`.
     */
    public static function youtubeVideoIdFromLink(mixed $linkUrl): ?string
    {
        return self::resolveYoutubeVideoId($linkUrl);
    }

    /**
     * Retourne l'ID vidéo YouTube (11 caractères) ou null à partir d'une URL ou d'un ID brut.
     *
     * @param  mixed  $linkUrl  Champ post `link_url` ou équivalent.
     */
    private static function resolveYoutubeVideoId(mixed $linkUrl): ?string
    {
        if (! is_string($linkUrl)) {
            return null;
        }

        $value = trim($linkUrl);

        if ($value === '') {
            return null;
        }

        if (preg_match('/^[A-Za-z0-9_-]{11}$/', $value) === 1) {
            return $value;
        }

        $patterns = [
            '/(?:youtube\.com\/watch\?v=)([A-Za-z0-9_-]{11})/i',
            '/(?:youtube\.com\/shorts\/)([A-Za-z0-9_-]{11})/i',
            '/(?:youtube\.com\/embed\/)([A-Za-z0-9_-]{11})/i',
            '/(?:youtu\.be\/)([A-Za-z0-9_-]{11})/i',
            '/(?:v=)([A-Za-z0-9_-]{11})/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value, $matches) === 1) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Construit l'URL embed YouTube (« https://www.youtube.com/embed/… ») à partir du champ `link_url`.
     *
     * @param  mixed  $linkUrl  Champ `link_url` du post (URL watch, youtu.be, ID seul, etc.).
     * @return string URL embed ou chaîne vide si aucun ID détecté.
     */
    public static function youtubeEmbedUrlFromLink(mixed $linkUrl): string
    {
        $id = self::resolveYoutubeVideoId($linkUrl);

        if ($id === null) {
            return '';
        }

        return 'https://www.youtube.com/embed/'.$id.'?rel=0&modestbranding=1';
    }

    /**
     * Normalise une valeur en tableau associatif (décodage JSON si nécessaire).
     *
     * @param  mixed  $value  Entrée à normaliser.
     * @return array<string, mixed>|null Tableau associatif, ou null si non convertible.
     */
    private static function normalizeToArray(mixed $value): ?array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || $value === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : null;
    }
}
