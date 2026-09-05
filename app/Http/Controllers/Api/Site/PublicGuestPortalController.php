<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Site;

use App\Http\Controllers\Controller;
use App\Models\GuestEventOutfit;
use App\Models\GuestInfoSubmission;
use App\Support\FilamentImageUrl;
use Illuminate\Http\JsonResponse;

/**
 * API publique du portail invité (après soumission de la fiche).
 */
final class PublicGuestPortalController extends Controller
{
    /**
     * Charge le contenu du portail pour un token valide et non expiré.
     */
    public function show(string $token): JsonResponse
    {
        $submission = GuestInfoSubmission::query()
            ->where('portal_token', $token)
            ->with([
                'guestPastor.project.outfits',
                'guestPastor.project.liturgySessions.items',
                'guestPastor.assignments',
                'guestPastor.assignedWorkers.worker.department',
                'guestPastor.assignedWorkers.department',
            ])
            ->first();

        if ($submission === null || $submission->guestPastor === null) {
            return response()->json(['message' => 'Lien portail invalide.'], 404);
        }

        $pastor = $submission->guestPastor;
        $project = $pastor->project;
        if ($project === null) {
            return response()->json(['message' => 'Projet introuvable.'], 404);
        }

        if (! $project->portalIsOpen()) {
            return response()->json([
                'message' => 'Ce portail a expiré à la fin de l’événement.',
                'expires_at' => $project->ends_at?->toIso8601String(),
            ], 403);
        }

        $outfits = $project->outfits->map(fn (GuestEventOutfit $o): array => [
            'session_key' => $o->session_key,
            'session_label' => GuestEventOutfit::sessionOptions()[$o->session_key] ?? $o->session_key,
            'title' => $o->title,
            'description' => $o->description,
            'image_url' => $o->imagePublicUrl(),
        ])->values();

        $teamGroups = [];
        foreach ($pastor->assignedWorkers as $row) {
            $title = $row->display_title
                ?: ($row->department?->name ?? $row->worker?->department?->name ?? 'Équipe');
            $worker = $row->worker;
            if ($worker === null) {
                continue;
            }
            $teamGroups[$title][] = [
                'name' => $worker->fullName(),
                'honorific' => $worker->gender === 'female' ? 'Sr' : 'Fr',
                'phone' => $worker->phone,
                'photo_url' => FilamentImageUrl::resolve($worker->photo_path),
                'department' => $worker->department?->name,
            ];
        }

        $team = [];
        foreach ($teamGroups as $title => $members) {
            $team[] = ['title' => $title, 'members' => $members];
        }

        $assignments = $pastor->assignments->map(fn ($a): array => [
            'day_date' => $a->day_date?->format('Y-m-d'),
            'session_key' => $a->session_key,
            'label' => $a->label,
            'color' => $a->color,
            'location' => $a->location,
        ])->values();

        $liturgy = $project->liturgySessions->map(fn ($session): array => [
            'session_key' => $session->session_key,
            'title' => $session->title,
            'starts_at_time' => $session->starts_at_time,
            'ends_at_time' => $session->ends_at_time,
            'items' => $session->items->map(fn ($item): array => [
                'starts_at_time' => $item->starts_at_time,
                'ends_at_time' => $item->ends_at_time,
                'duration_minutes' => $item->duration_minutes,
                'label' => $item->label,
            ])->values(),
        ])->values();

        return response()->json([
            'data' => [
                'pastor' => [
                    'full_name' => $pastor->full_name,
                    'photo_url' => $pastor->photoPublicUrl(),
                    'church_name' => $pastor->church_name,
                ],
                'project' => [
                    'title' => $project->title,
                    'starts_at' => $project->starts_at?->toIso8601String(),
                    'ends_at' => $project->ends_at?->toIso8601String(),
                ],
                'outfits' => $outfits,
                'team' => $team,
                'assignments' => $assignments,
                'liturgy' => $liturgy,
            ],
        ]);
    }
}
