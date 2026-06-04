<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Site;

use App\Http\Controllers\Controller;
use App\Models\ContentReaction;
use App\Models\Testimony;
use App\Models\TestimonyImage;
use App\Models\TestimonyWallSetting;
use App\Support\SitePublicSerializer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Mur de témoignages : lecture publique et soumission modérée.
 */
final class PublicTestimonyController extends Controller
{
    /**
     * Liste paginée des témoignages approuvés pour le mur public.
     *
     * @param  Request  $request  Query : `page`, `per_page`, `category`, `kind`.
     * @return JsonResponse Tableau `data` + `meta` (pagination et options mur).
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->query('per_page', 12), 1), 48);
        $page = max((int) $request->query('page', 1), 1);

        $query = $this->approvedQuery()->with('images');

        $category = $request->query('category');
        if (is_string($category) && trim($category) !== '' && strtolower(trim($category)) !== 'tous') {
            if (strtolower(trim($category)) === 'vidéos' || strtolower(trim($category)) === 'videos') {
                $query->whereIn('kind', [Testimony::KIND_VIDEO, Testimony::KIND_MIX]);
            } else {
                $query->where('category', trim($category));
            }
        }

        $kind = $request->query('kind');
        if (is_string($kind) && in_array($kind, [Testimony::KIND_TEXT, Testimony::KIND_VIDEO, Testimony::KIND_MIX], true)) {
            $query->where('kind', $kind);
        }

        $paginator = $query->paginate(perPage: $perPage, page: $page);

        $items = $paginator->getCollection()->map(
            static fn (Testimony $testimony): array => SitePublicSerializer::testimonyToWallArray($testimony)
        )->values()->all();

        return response()->json([
            'data' => $items,
            'meta' => $this->metaEnvelope($paginator->currentPage(), $paginator->lastPage(), $paginator->perPage(), $paginator->total(), $paginator->hasMorePages()),
        ]);
    }

    /**
     * Lot pour le carrousel hero (défilement vertical).
     *
     * @return JsonResponse `{ data: WallTestimony[] }`
     */
    public function carousel(Request $request): JsonResponse
    {
        $limit = min(max((int) $request->query('limit', 24), 4), 48);

        $items = $this->approvedQuery()
            ->with('images')
            ->inRandomOrder()
            ->limit($limit)
            ->get()
            ->map(static fn (Testimony $testimony): array => SitePublicSerializer::testimonyToWallArray($testimony))
            ->values()
            ->all();

        return response()->json(['data' => $items]);
    }

    /**
     * Statistiques affichées en bas de page (témoignages, réactions, partages).
     *
     * @return JsonResponse `{ data: { testimonies, reactions, shares } }`
     */
    public function stats(): JsonResponse
    {
        $testimonyCount = Testimony::query()->where('status', Testimony::STATUS_APPROVED)->count();

        $reactionCount = ContentReaction::query()
            ->where('reactable_key', 'like', 'testimony:%')
            ->whereIn('reaction_key', array_keys($this->testimonyReactionKeys()))
            ->count();

        $shareCount = (int) Testimony::query()
            ->where('status', Testimony::STATUS_APPROVED)
            ->sum('share_count');

        return response()->json([
            'data' => [
                'testimonies' => $testimonyCount,
                'reactions' => $reactionCount,
                'shares' => $shareCount,
            ],
        ]);
    }

    /**
     * Aperçu court pour la page d’accueil (3 derniers approuvés).
     *
     * @return JsonResponse `{ data: TestimonyHomeQuote[] }`
     */
    public function featured(): JsonResponse
    {
        $items = $this->approvedQuery()
            ->limit(3)
            ->get()
            ->map(static fn (Testimony $testimony): array => SitePublicSerializer::testimonyToHomeQuoteArray($testimony))
            ->values()
            ->all();

        return response()->json(['data' => $items]);
    }

    /**
     * Options d’affichage du mur (catégories, couleurs, réglages).
     *
     * @return JsonResponse `{ data: { wall, allowPhotoUpload, reactionKeys } }`
     */
    public function wallConfig(): JsonResponse
    {
        $settings = TestimonyWallSetting::current();

        return response()->json([
            'data' => [
                'wall' => $this->wallConfigArray($settings),
                'wallSettings' => $settings->toPublicArray(),
                'allowPhotoUpload' => $settings->allow_photo_upload,
                'reactionKeys' => $this->testimonyReactionKeys(),
            ],
        ]);
    }

    /**
     * Incrémente le compteur de partages d’un témoignage publié.
     *
     * @return JsonResponse `{ data: { shareCount: int } }`
     */
    public function recordShare(Testimony $testimony): JsonResponse
    {
        if ($testimony->status !== Testimony::STATUS_APPROVED) {
            return response()->json(['message' => 'Témoignage introuvable.'], 404);
        }

        $testimony->increment('share_count');

        return response()->json([
            'data' => [
                'shareCount' => (int) $testimony->fresh()?->share_count,
            ],
        ]);
    }

    /**
     * Enregistre un témoignage en attente de modération.
     *
     * @param  Request  $request  Corps multipart.
     * @return JsonResponse `{ data: { ok: true, id: int } }`
     */
    public function store(Request $request): JsonResponse
    {
        $settings = TestimonyWallSetting::current();
        $publicSettings = $settings->toPublicArray();
        $maxPhotos = (int) $publicSettings['maxPhotosPerTestimony'];
        $maxVideoKb = (int) $publicSettings['maxVideoUploadMb'] * 1024;

        $validated = $request->validate([
            'kind' => 'required|string|in:'.Testimony::KIND_TEXT.','.Testimony::KIND_VIDEO.','.Testimony::KIND_MIX,
            'first_name' => ($settings->require_first_name ? 'required' : 'nullable').'|string|max:100',
            'last_name' => ($settings->require_last_name ? 'required' : 'nullable').'|string|max:100',
            'title' => 'required|string|max:50',
            'text' => 'nullable|string|max:8000',
            'video' => 'nullable|string|max:500',
            'video_file' => 'nullable|file|mimetypes:video/mp4,video/webm,video/quicktime|max:'.$maxVideoKb,
            'video_source' => 'nullable|string|in:link,upload',
            'postit_color' => 'nullable|string|max:20',
            'font_family' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:100',
            'email' => 'required|email|max:190',
            'phone' => 'nullable|string|max:30',
            'is_anonymous' => 'nullable|boolean',
            'verification_type' => 'nullable|string|in:'.Testimony::VERIFY_EMAIL.','.Testimony::VERIFY_PHONE.','.Testimony::VERIFY_BOTH,
            'images' => 'nullable|array|max:'.$maxPhotos,
            'images.*' => 'image|max:5120',
        ]);

        $isAnonymous = (bool) ($validated['is_anonymous'] ?? false);

        if ($isAnonymous && ! $settings->allow_anonymous) {
            return response()->json(['message' => 'La publication anonyme n’est pas autorisée.'], 422);
        }
        $kind = $validated['kind'];
        $text = isset($validated['text']) ? trim((string) $validated['text']) : '';
        $video = isset($validated['video']) ? trim((string) $validated['video']) : '';
        $videoSource = $validated['video_source'] ?? 'link';

        if (in_array($kind, [Testimony::KIND_TEXT, Testimony::KIND_MIX], true) && $text === '') {
            return response()->json(['message' => 'Le texte du témoignage est requis.'], 422);
        }

        if (in_array($kind, [Testimony::KIND_VIDEO, Testimony::KIND_MIX], true)) {
            $hasUpload = $request->hasFile('video_file');
            if ($videoSource === 'upload') {
                if (! $settings->allow_video_upload) {
                    return response()->json(['message' => 'L’envoi de fichier vidéo n’est pas autorisé.'], 422);
                }
                if (! $hasUpload) {
                    return response()->json([
                        'message' => 'Veuillez joindre une vidéo (max. '.(int) $settings->max_video_upload_mb.' Mo).',
                    ], 422);
                }
            } elseif ($video === '' && ! $hasUpload) {
                return response()->json(['message' => 'Une URL vidéo ou un fichier est requis.'], 422);
            } elseif ($video !== '' && ! $settings->allow_youtube_link) {
                return response()->json(['message' => 'Les liens YouTube ne sont pas acceptés pour le moment.'], 422);
            } elseif ($hasUpload && ! $settings->allow_video_upload) {
                return response()->json(['message' => 'L’envoi de fichier vidéo n’est pas autorisé.'], 422);
            }
        }

        if ($request->hasFile('images') && ! $settings->allow_photo_upload) {
            return response()->json(['message' => 'Les photos ne sont pas acceptées pour le moment.'], 422);
        }

        if (! $isAnonymous && $settings->require_first_name) {
            $firstName = trim((string) ($validated['first_name'] ?? ''));
            if ($firstName === '') {
                return response()->json(['message' => 'Le prénom est requis.'], 422);
            }
        }

        if (! $isAnonymous && $settings->require_last_name) {
            $lastName = trim((string) ($validated['last_name'] ?? ''));
            if ($lastName === '') {
                return response()->json(['message' => 'Le nom est requis.'], 422);
            }
        }

        $verificationType = $validated['verification_type'] ?? Testimony::VERIFY_EMAIL;
        if (in_array($verificationType, [Testimony::VERIFY_PHONE, Testimony::VERIFY_BOTH], true)) {
            $phone = isset($validated['phone']) ? trim((string) $validated['phone']) : '';
            if ($phone === '') {
                return response()->json(['message' => 'Le numéro de téléphone est requis.'], 422);
            }
        }

        $testimonyId = DB::transaction(function () use ($request, $validated, $kind, $text, $video, $verificationType, $isAnonymous): int {
            $videoFilePath = null;
            if ($request->hasFile('video_file')) {
                $videoFilePath = $request->file('video_file')?->store('testimonies/videos', 'public');
            }

            $testimony = Testimony::query()->create([
                'kind' => $kind,
                'first_name' => $isAnonymous ? 'Anonyme' : $validated['first_name'],
                'last_name' => $isAnonymous ? null : ($validated['last_name'] ?? null),
                'title' => $validated['title'],
                'text' => $text !== '' ? $text : null,
                'video' => $video !== '' ? $video : null,
                'video_file' => $videoFilePath,
                'postit_color' => $validated['postit_color'] ?? '#FFF6D9',
                'font_family' => $validated['font_family'] ?? 'Inter, sans-serif',
                'category' => $validated['category'] ?? null,
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'verification_type' => $verificationType,
                'is_anonymous' => $isAnonymous,
                'status' => Testimony::STATUS_PENDING,
                'share_count' => 0,
            ]);

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    if ($file === null) {
                        continue;
                    }
                    $path = $file->store('testimonies', 'public');
                    TestimonyImage::query()->create([
                        'testimony_id' => $testimony->id,
                        'image' => $path,
                    ]);
                }
            }

            return $testimony->id;
        });

        return response()->json([
            'data' => [
                'ok' => true,
                'id' => $testimonyId,
                'message' => 'Merci ! Votre témoignage sera publié après validation par l’équipe.',
            ],
        ], 201);
    }

    /**
     * @return Builder<Testimony>
     */
    private function approvedQuery()
    {
        return Testimony::query()
            ->where('status', Testimony::STATUS_APPROVED)
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    /**
     * @return array<string, mixed>
     */
    private function metaEnvelope(int $currentPage, int $lastPage, int $perPage, int $total, bool $hasMore): array
    {
        $settings = TestimonyWallSetting::current();

        return [
            'current_page' => $currentPage,
            'last_page' => $lastPage,
            'per_page' => $perPage,
            'total' => $total,
            'has_more' => $hasMore,
            'wall' => $this->wallConfigArray($settings),
            'wallSettings' => $settings->toPublicArray(),
            'allowPhotoUpload' => $settings->allow_photo_upload,
            'reactionKeys' => $this->testimonyReactionKeys(),
        ];
    }

    /**
     * Fusionne la config statique du mur avec les limites dynamiques (upload vidéo, photos).
     *
     * @return array<string, mixed>
     */
    private function wallConfigArray(TestimonyWallSetting $settings): array
    {
        $wall = (array) config('site_public.testimony_wall', []);
        $wall['maxVideoUploadMb'] = max(1, (int) $settings->max_video_upload_mb);

        return $wall;
    }

    /**
     * @return array<string, string>
     */
    private function testimonyReactionKeys(): array
    {
        return (array) config('site_public.testimony_reaction_keys', []);
    }
}
