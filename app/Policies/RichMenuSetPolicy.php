<?php

namespace App\Policies;

use App\Models\RichMenuSet;
use App\Models\User;

class RichMenuSetPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        //
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, RichMenuSet $richMenuSet): bool
    {
        //zz
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        //
        return true;

    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, RichMenuSet $richMenuSet): bool
    {
        //
        if ($richMenuSet->richMenus()->count() > 0) {
            return false;
        }

        return true;

    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, RichMenuSet $richMenuSet): bool
    {
        //
        return true;

    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, RichMenuSet $richMenuSet): bool
    {
        //
        return true;

    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, RichMenuSet $richMenuSet): bool
    {
        //
        return true;

    }
}
