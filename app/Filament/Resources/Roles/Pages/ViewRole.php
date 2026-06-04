<?php

declare(strict_types=1);

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use BezhanSalleh\FilamentShield\Resources\Roles\Pages\ViewRole as ShieldViewRole;

/**
 * Consultation d’un rôle Shield.
 */
class ViewRole extends ShieldViewRole
{
    protected static string $resource = RoleResource::class;
}
