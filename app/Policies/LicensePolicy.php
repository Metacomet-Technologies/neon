<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\License;
use App\Models\User;

class LicensePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, License $license): bool
    {
        return $user->id === $license->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false; // Licenses are typically created through purchase flow
    }

    /**
     * Determine whether the user can update the model.
     * This covers assign, park, and transfer operations.
     */
    public function update(User $user, License $license): bool
    {
        return $user->id === $license->user_id;
    }

    /**
     * Determine whether the user can assign the license to a guild.
     */
    public function assign(User $user, License $license): bool
    {
        return $user->id === $license->user_id;
    }

    /**
     * Determine whether the user can park the license.
     */
    public function park(User $user, License $license): bool
    {
        return $user->id === $license->user_id;
    }

    /**
     * Determine whether the user can transfer the license to another guild.
     */
    public function transfer(User $user, License $license): bool
    {
        return $user->id === $license->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, License $license): bool
    {
        return false; // Licenses should not be deletable by users
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, License $license): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, License $license): bool
    {
        return false;
    }
}
