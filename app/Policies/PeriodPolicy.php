<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Period;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class PeriodPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Period');
    }

    public function view(AuthUser $authUser, Period $period): bool
    {
        return $authUser->can('View:Period');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Period');
    }

    public function update(AuthUser $authUser, Period $period): bool
    {
        return $authUser->can('Update:Period');
    }

    public function delete(AuthUser $authUser, Period $period): bool
    {
        return $authUser->can('Delete:Period');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Period');
    }

    public function restore(AuthUser $authUser, Period $period): bool
    {
        return $authUser->can('Restore:Period');
    }

    public function forceDelete(AuthUser $authUser, Period $period): bool
    {
        return $authUser->can('ForceDelete:Period');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Period');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Period');
    }

    public function replicate(AuthUser $authUser, Period $period): bool
    {
        return $authUser->can('Replicate:Period');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Period');
    }
}
