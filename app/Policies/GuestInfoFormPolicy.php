<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\GuestInfoForm;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Autorisations Shield pour les formulaires d’accueil invités.
 */
class GuestInfoFormPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:GuestInfoForm');
    }

    public function view(AuthUser $authUser, GuestInfoForm $guestInfoForm): bool
    {
        return $authUser->can('View:GuestInfoForm');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:GuestInfoForm');
    }

    public function update(AuthUser $authUser, GuestInfoForm $guestInfoForm): bool
    {
        return $authUser->can('Update:GuestInfoForm');
    }

    public function delete(AuthUser $authUser, GuestInfoForm $guestInfoForm): bool
    {
        return $authUser->can('Delete:GuestInfoForm');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:GuestInfoForm');
    }
}
