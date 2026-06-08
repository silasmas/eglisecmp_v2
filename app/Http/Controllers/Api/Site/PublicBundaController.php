<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Site;

use App\Http\Controllers\Controller;
use App\Models\BundaProgram;
use App\Models\Event;
use App\Support\EventPostQuery;
use App\Support\EventPublicContent;
use App\Support\SitePublicSerializer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Données page Bunda : programmes admin, éditions (playlists) et annonce à venir.
 */
final class PublicBundaController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        $locale = SitePublicSerializer::localeFromRequest($request);
        $fallback = SitePublicSerializer::fallbackLocale();
        $placeholder = (string) config('site_public.placeholder_image_url', '');

        $programs = BundaProgram::query()
            ->with('event')
            ->where('is_active', true)
            ->orderByDesc('edition_year')
            ->orderByDesc('sort_order')
            ->get();

        if ($programs->isEmpty()) {
            return response()->json(['data' => $this->legacyPayload($locale, $fallback, $placeholder)]);
        }

        $upcomingProgram = $programs->firstWhere('is_upcoming_announcement', true);
        $latest = $programs->first();
        $archivePrograms = $programs->filter(
            static fn (BundaProgram $program): bool => ! $program->is_upcoming_announcement || $programs->count() === 1
        );

        $editions = $archivePrograms
            ->map(fn (BundaProgram $program): array => $this->editionFromProgram($program, $locale, $fallback, $placeholder))
            ->filter(static fn (array $row): bool => ($row['videoCount'] ?? 0) > 0 || ($row['hasPoster'] ?? false))
            ->values()
            ->all();

        $introProgram = $latest;

        return response()->json([
            'data' => [
                'intro' => [
                    'title' => $this->text($introProgram->title, $locale, $fallback, 'Conférence Bunda'),
                    'subtitle' => $this->text($introProgram->subtitle, $locale, $fallback, ''),
                    'body' => $this->text($introProgram->body, $locale, $fallback, ''),
                    'heroImage' => $this->imageUrl($introProgram->hero_image, $locale, $fallback, $placeholder),
                    'mealPlanUrl' => $this->mealPlanUrl($introProgram->meal_plan_path),
                    'mealPlanLabel' => $introProgram->meal_plan_label ?: 'Plan alimentaire',
                ],
                'upcoming' => $this->upcomingPayload($upcomingProgram, $programs, $locale, $fallback),
                'editions' => $editions,
                'latestEdition' => $editions[0] ?? null,
                'pastEditions' => array_slice($editions, 1),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function upcomingPayload(
        ?BundaProgram $upcomingProgram,
        \Illuminate\Support\Collection $programs,
        string $locale,
        string $fallback,
    ): array {
        if ($upcomingProgram !== null) {
            return [
                'title' => $this->text($upcomingProgram->title, $locale, $fallback, 'Bunda '.$upcomingProgram->edition_year),
                'monthLabel' => $upcomingProgram->upcoming_month_label ?: 'Novembre',
                'year' => (int) $upcomingProgram->edition_year,
                'description' => $this->text($upcomingProgram->upcoming_description, $locale, $fallback, ''),
            ];
        }

        $upcomingYear = (int) now()->format('Y');
        if (now()->month >= 11) {
            $upcomingYear += 1;
        }

        return [
            'title' => 'Bunda '.$upcomingYear,
            'monthLabel' => 'Novembre',
            'year' => $upcomingYear,
            'description' => 'La prochaine conférence Bunda aura lieu en novembre. Restez connectés pour le programme.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function editionFromProgram(
        BundaProgram $program,
        string $locale,
        string $fallback,
        string $placeholder,
    ): array {
        $event = $program->event;
        $base = $event !== null
            ? SitePublicSerializer::eventToPublicArray($event, $locale, $fallback)
            : [
                'id' => (string) $program->getKey(),
                'title' => $this->text($program->title, $locale, $fallback, 'Bunda '.$program->edition_year),
                'date' => '',
                'image' => $this->imageUrl($program->hero_image, $locale, $fallback, $placeholder),
                'description' => $this->text($program->description, $locale, $fallback, ''),
                'hasPoster' => $this->imageUrl($program->hero_image, $locale, $fallback, '') !== $placeholder,
            ];

        $content = $event !== null ? EventPublicContent::resolve($event) : [
            'contentHref' => null,
            'contentLabel' => null,
            'contentCount' => 0,
        ];

        if ($event !== null) {
            $synced = EventPostQuery::activeCountForEvent($event);
            $youtubeCount = (int) ($event->youtube_playlist_item_count ?? 0);
            $content['contentCount'] = max($synced, $youtubeCount, (int) ($content['contentCount'] ?? 0));
        }

        $title = $this->text($program->title, $locale, $fallback, (string) ($base['title'] ?? 'Bunda'));
        $year = (string) $program->edition_year;

        return array_merge($base, $content, [
            'programId' => (string) $program->getKey(),
            'editionYear' => (int) $program->edition_year,
            'title' => $title,
            'description' => $this->text($program->description, $locale, $fallback, (string) ($base['description'] ?? '')),
            'body' => $this->text($program->body, $locale, $fallback, ''),
            'image' => $this->imageUrl($program->hero_image, $locale, $fallback, (string) ($base['image'] ?? $placeholder)),
            'buttonLabel' => $this->bundaButtonLabel($title, $year),
            'videoCount' => (int) ($content['contentCount'] ?? 0),
            'mealPlanUrl' => $this->mealPlanUrl($program->meal_plan_path),
            'mealPlanLabel' => $program->meal_plan_label ?: 'Plan alimentaire',
        ]);
    }

    /**
     * Repli si aucun programme Bunda en base (anciennes playlists événements).
     *
     * @return array<string, mixed>
     */
    private function legacyPayload(string $locale, string $fallback, string $placeholder): array
    {
        $bundaEvents = Event::query()
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->where('designation', 'like', '%bunda%')
                    ->orWhere('theme', 'like', '%bunda%');
            })
            ->orderByDesc('date_debut')
            ->get()
            ->filter(static function (Event $event): bool {
                $synced = EventPostQuery::activeCountForEvent($event);
                $youtubeCount = (int) ($event->youtube_playlist_item_count ?? 0);

                return max($synced, $youtubeCount) > 0;
            })
            ->values();

        $editions = $bundaEvents->map(function (Event $event) use ($locale, $fallback, $placeholder): array {
            $base = SitePublicSerializer::eventToPublicArray($event, $locale, $fallback);
            $content = EventPublicContent::resolve($event);
            $year = $event->date_debut?->format('Y') ?? '';
            $title = (string) ($base['title'] ?? 'Bunda');

            return array_merge($base, $content, [
                'programId' => (string) $event->getKey(),
                'editionYear' => (int) ($year !== '' ? $year : 0),
                'buttonLabel' => $this->bundaButtonLabel($title, $year),
                'videoCount' => (int) ($content['contentCount'] ?? 0),
                'mealPlanUrl' => null,
                'mealPlanLabel' => 'Plan alimentaire',
                'body' => '',
            ]);
        })->all();

        $upcomingYear = (int) now()->format('Y');
        if (now()->month >= 11) {
            $upcomingYear += 1;
        }

        return [
            'intro' => [
                'title' => $editions[0]['title'] ?? 'Conférence Bunda',
                'subtitle' => '',
                'body' => '',
                'heroImage' => $editions[0]['image'] ?? $placeholder,
                'mealPlanUrl' => null,
                'mealPlanLabel' => 'Plan alimentaire',
            ],
            'upcoming' => [
                'title' => 'Bunda '.$upcomingYear,
                'monthLabel' => 'Novembre',
                'year' => $upcomingYear,
                'description' => 'La prochaine conférence Bunda aura lieu en novembre.',
            ],
            'editions' => $editions,
            'latestEdition' => $editions[0] ?? null,
            'pastEditions' => array_slice($editions, 1),
        ];
    }

    private function mealPlanUrl(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    /**
     * @param  array<string, string>|string|null  $field
     */
    private function text(mixed $field, string $locale, string $fallback, string $default = ''): string
    {
        if (is_array($field)) {
            $value = (string) ($field[$locale] ?? $field[$fallback] ?? reset($field) ?: '');

            return $value !== '' ? $value : $default;
        }

        return $default;
    }

    /**
     * @param  array<string, string>|string|null  $field
     */
    private function imageUrl(mixed $field, string $locale, string $fallback, string $placeholder): string
    {
        if (is_array($field)) {
            $url = (string) ($field[$locale] ?? $field[$fallback] ?? reset($field) ?: '');
            if ($url !== '') {
                return str_starts_with($url, 'http') ? $url : Storage::disk('public')->url($url);
            }
        }

        return $placeholder;
    }

    private function bundaButtonLabel(string $title, string $year): string
    {
        if (preg_match('/bunda\s*(\d{4})/i', $title, $matches) === 1) {
            return 'Bunda'.$matches[1];
        }

        if ($year !== '' && preg_match('/^\d{4}$/', $year) === 1) {
            return 'Bunda'.$year;
        }

        return 'Voir Bunda';
    }
}
