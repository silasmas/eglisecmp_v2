<?php

declare(strict_types=1);

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use BezhanSalleh\FilamentShield\Resources\Roles\Pages\EditRole as ShieldEditRole;
use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Support\Arr;

/**
 * Édition d’un rôle : ne met à jour que les permissions (nom/guard inchangés).
 */
class EditRole extends ShieldEditRole
{
    protected static string $resource = RoleResource::class;

    /**
     * Conserve le nom et le guard d’origine ; extrait uniquement les permissions du formulaire.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    #[\Override]
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->permissions = collect($data)
            ->filter(fn (mixed $permission, string $key): bool => ! in_array($key, ['name', 'guard_name', 'select_all', Utils::getTenantModelForeignKey()], true))
            ->values()
            ->flatten()
            ->unique();

        $payload = [
            'name' => (string) $this->record->name,
            'guard_name' => (string) $this->record->guard_name,
        ];

        if (Utils::isTenancyEnabled() && Arr::has($data, Utils::getTenantModelForeignKey()) && filled($data[Utils::getTenantModelForeignKey()])) {
            $payload[Utils::getTenantModelForeignKey()] = $data[Utils::getTenantModelForeignKey()];
        }

        return $payload;
    }

    /**
     * Évite une mise à jour Eloquent inutile : seules les permissions changent.
     */
    #[\Override]
    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        return $record;
    }
}
