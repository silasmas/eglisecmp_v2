<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Site;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Support\EventPostQuery;
use App\Support\EventPublicContent;
use App\Support\SitePublicSerializer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
/**
 * Données page Bunda : dernière édition, archives et annonce à venir.
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

        $latest = $bundaEvents->first();
        $past = $bundaEvents->slice(1)->values();

        $upcomingYear = (int) now()->format('Y');
        if (now()->month >= 11) {
            $upcomingYear += 1;
        }

        return response()->json([
            'data' => [
                'upcoming' => [
                    'title' => 'Bunda '.$upcomingYear,
                    'monthLabel' => 'Novembre',
                    'year' => $upcomingYear,
                    'description' => 'La prochaine conférence Bunda aura lieu en novembre. Restez connectés pour le programme et l’inscription.',
                ],
                'latestEdition' => $latest !== null
                    ? $this->editionPayload($latest, $locale, $fallback, $placeholder)
                    : null,
                'pastEditions' => $past->map(
                    fn (Event $event): array => $this->editionPayload($event, $locale, $fallback, $placeholder)
                )->all(),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function editionPayload(Event $event, string $locale, string $fallback, string $placeholder): array
    {
        $base = SitePublicSerializer::eventToPublicArray($event, $locale, $fallback);
        $content = EventPublicContent::resolve($event);
        $year = $event->date_debut?->format('Y') ?? '';
        $title = (string) ($base['title'] ?? 'Bunda');
        $buttonLabel = $this->bundaButtonLabel($title, $year);

        return array_merge($base, $content, [
            'buttonLabel' => $buttonLabel,
            'videoCount' => $content['contentCount'],
        ]);
    }

    /**
     * Libellé bouton type « Bunda2025 ».
     */
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
