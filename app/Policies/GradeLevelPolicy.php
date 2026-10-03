<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\GradeLevel;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class GradeLevelPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:GradeLevel');
    }

    public function view(AuthUser $authUser, GradeLevel $gradeLevel): bool
    {
        return $authUser->can('View:GradeLevel');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:GradeLevel');
    }

    public function update(AuthUser $authUser, GradeLevel $gradeLevel): bool
    {
        return $authUser->can('Update:GradeLevel');
    }

    public function delete(AuthUser $authUser, GradeLevel $gradeLevel): bool
    {
        return $authUser->can('Delete:GradeLevel');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:GradeLevel');
    }

    public function restore(AuthUser $authUser, GradeLevel $gradeLevel): bool
    {
        return $authUser->can('Restore:GradeLevel');
    }

    public function forceDelete(AuthUser $authUser, GradeLevel $gradeLevel): bool
    {
        return $authUser->can('ForceDelete:GradeLevel');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:GradeLevel');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:GradeLevel');
    }

    public function replicate(AuthUser $authUser, GradeLevel $gradeLevel): bool
    {
        return $authUser->can('Replicate:GradeLevel');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:GradeLevel');
    }
}
