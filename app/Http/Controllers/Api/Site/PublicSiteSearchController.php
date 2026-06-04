<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Site;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Minister;
use App\Models\Post;
use App\Models\ScheduleProgram;
use App\Support\EventPublicContent;
use App\Support\SitePublicSerializer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Recherche instantanée sur le contenu public CMP (pasteurs, messages, événements, programmes).
 */
final class PublicSiteSearchController extends Controller
{
    /**
     * @return JsonResponse `{ data: SiteSearchHit[] }`
     */
    public function index(Request $request): JsonResponse
    {
        $locale = SitePublicSerializer::localeFromRequest($request);
        $fallback = SitePublicSerializer::fallbackLocale();
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json(['data' => []]);
        }

        $like = '%'.addcslashes($q, '%_\\').'%';
        $hits = [];
        $placeholder = (string) config('site_public.placeholder_image_url', '');

        $ministers = Minister::query()
            ->where('fullname', 'like', $like)
            ->limit(5)
            ->get();

        foreach ($ministers as $minister) {
            $hits[] = [
                'type' => 'minister',
                'id' => (string) $minister->id,
                'title' => (string) $minister->fullname,
                'subtitle' => 'Pasteur / ministre',
                'href' => '/rendez-vous',
            ];
        }

        $posts = Post::query()
            ->where('is_active', true)
            ->with('minister')
            ->where(function (Builder $sub) use ($like): void {
                $sub->where('title', 'like', $like)
                    ->orWhere('body', 'like', $like)
                    ->orWhere('author', 'like', $like)
                    ->orWhere('slug', 'like', $like);
            })
            ->orderByDesc('date_publication')
            ->limit(8)
            ->get();

        $defaultSpeaker = (string) config('site_public.default_speaker_name', 'Centre Missionnaire Philadelphie');

        foreach ($posts as $post) {
            $title = SitePublicSerializer::text($post->title, $locale, $fallback);
            $thumb = SitePublicSerializer::imageUrl($post->image_url, $locale, $fallback);
            if ($thumb === '') {
                $thumb = $placeholder;
            }
            $speaker = $post->getSpeakerName();
            $subtitle = $speaker !== '' && $speaker !== $defaultSpeaker
                ? 'Enseignement · '.$speaker
                : 'Enseignement';
            $hits[] = [
                'type' => 'message',
                'id' => (string) $post->id,
                'title' => $title,
                'subtitle' => $subtitle,
                'thumbnail' => $thumb,
                'href' => '/teachings/message/'.$post->id,
            ];
        }

        $events = Event::query()
            ->where('is_active', true)
            ->where(function (Builder $sub) use ($like): void {
                $sub->where('designation', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhere('theme', 'like', $like);
            })
            ->orderByDesc('date_debut')
            ->limit(5)
            ->get();

        foreach ($events as $event) {
            $title = SitePublicSerializer::text($event->designation, $locale, $fallback);
            $content = EventPublicContent::resolve($event);
            $thumb = SitePublicSerializer::imageUrl($event->image_url, $locale, $fallback);
            if ($thumb === '') {
                $thumb = $placeholder;
            }
            $subtitle = $content['contentCount'] > 0
                ? 'Événement · '.$content['contentCount'].' message'.($content['contentCount'] > 1 ? 's' : '')
                : 'Événement';
            $hits[] = [
                'type' => 'event',
                'id' => (string) $event->id,
                'title' => $title,
                'subtitle' => $subtitle,
                'thumbnail' => $thumb,
                'href' => $content['contentHref'] ?? '/events',
            ];
        }

        $programs = ScheduleProgram::query()
            ->where('is_active', true)
            ->where(function (Builder $sub) use ($like): void {
                $sub->where('title', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhere('day_label', 'like', $like);
            })
            ->limit(5)
            ->get();

        foreach ($programs as $program) {
            $row = SitePublicSerializer::scheduleProgramToPublicArray($program, $locale, $fallback);
            $hits[] = [
                'type' => 'program',
                'id' => (string) $program->id,
                'title' => (string) ($row['name'] ?? $row['title'] ?? 'Programme'),
                'subtitle' => 'Programme · '.(string) ($row['kind'] ?? ''),
                'href' => '/programs',
            ];
        }

        $staticPages = [
            ['title' => 'Nous rejoindre', 'href' => '/join', 'subtitle' => 'Contact & adhésion'],
            ['title' => 'Offrandes', 'href' => '/offrandes', 'subtitle' => 'Soutenir l\'œuvre'],
            ['title' => 'Requête de prière', 'href' => '/requete-de-priere', 'subtitle' => 'Prière'],
            ['title' => 'Prendre rendez-vous', 'href' => '/rendez-vous', 'subtitle' => 'Rendez-vous pasteur'],
            ['title' => 'Mur de témoignages', 'href' => '/temoignages', 'subtitle' => 'Témoignages'],
            ['title' => 'Enseignements', 'href' => '/teachings', 'subtitle' => 'Messages & méditations'],
        ];

        $qNorm = Str::lower($q);
        foreach ($staticPages as $page) {
            if (str_contains(Str::lower($page['title']), $qNorm) || str_contains(Str::lower($page['subtitle']), $qNorm)) {
                $hits[] = [
                    'type' => 'page',
                    'id' => $page['href'],
                    'title' => $page['title'],
                    'subtitle' => $page['subtitle'],
                    'href' => $page['href'],
                ];
            }
        }

        return response()->json(['data' => array_slice($hits, 0, 15)]);
    }
}
