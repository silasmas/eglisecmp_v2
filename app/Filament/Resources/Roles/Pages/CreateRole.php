<?php

declare(strict_types=1);

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use BezhanSalleh\FilamentShield\Resources\Roles\Pages\CreateRole as ShieldCreateRole;

/**
 * Création d’un rôle Shield.
 */
class CreateRole extends ShieldCreateRole
{
    protected static string $resource = RoleResource::class;
}
