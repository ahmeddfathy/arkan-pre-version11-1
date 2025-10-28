<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Auth;

class RoleCheckService
{
    public function userHasRole($roles)
    {
        $user = Auth::user();

        if (is_array($roles)) {
            return $user->roles->pluck('name')->intersect($roles)->isNotEmpty();
        }

        return $user->roles->contains('name', $roles);
    }

    /**
     * Check if the user has a specific permission
     *
     * @param \App\Models\User $user
     * @param string $permission
     * @return bool
     */
    public function hasPermission($user, $permission)
    {
        return $user->hasPermissionTo($permission);
    }
}
