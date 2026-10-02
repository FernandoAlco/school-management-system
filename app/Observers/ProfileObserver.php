<?php

namespace App\Observers;

use App\Models\Guardian;
use App\Models\Student;
use App\Models\Teacher;

class ProfileObserver
{
    /**
     * Sync the profile's name to its linked user, since the profile is the source of truth.
     */
    public function saved(Teacher|Student|Guardian $profile): void
    {
        if ($profile->user_id === null) {
            return;
        }

        if (! $profile->wasRecentlyCreated && ! $profile->wasChanged(['first_name', 'last_name', 'user_id'])) {
            return;
        }

        $profile->user?->update([
            'first_name' => $profile->first_name,
            'last_name' => $profile->last_name,
        ]);
    }
}
