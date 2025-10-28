<?php

namespace App\Policies;

use App\Models\PermissionRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PermissionRequestPolicy
{
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_permission');
    }

    public function update(User $user, PermissionRequest $permissionRequest): bool
    {
        if (!$user->hasPermissionTo('update_permission')) {
            return false;
        }
        return $user->id === $permissionRequest->user_id && $permissionRequest->status === 'pending';
    }

    public function delete(User $user, PermissionRequest $permissionRequest): bool
    {
        if (!$user->hasPermissionTo('delete_permission')) {
            return false;
        }
        return $user->id === $permissionRequest->user_id && $permissionRequest->status === 'pending';
    }

    public function respond(User $user, PermissionRequest $permissionRequest): bool
    {
        if ($user->hasRole('hr') && $user->hasPermissionTo('hr_respond_permission_request')) {
            return true;
        }

        if ($user->hasPermissionTo('manager_respond_permission_request')) {
            if ($permissionRequest->user && $permissionRequest->user->teams()->exists()) {
                return DB::table('team_user')
                    ->where('user_id', $user->id)
                    ->where('team_id', $permissionRequest->user->currentTeam->id)
                    ->where(function ($query) {
                        $query->where('role', 'admin')
                            ->orWhere('role', 'owner');
                    })
                    ->exists();
            }
        }

        return false;
    }

    public function modifyResponse(User $user, PermissionRequest $permissionRequest): bool
    {
        if ($user->hasRole('hr') && $user->hasPermissionTo('hr_respond_permission_request')) {
            return true;
        }

        if (!$user->hasPermissionTo('manager_respond_permission_request')) {
            return false;
        }

        if ($user->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'company_manager'])) {
            return DB::table('team_user')
                ->where('user_id', $user->id)
                ->where('team_id', $permissionRequest->user->currentTeam->id)
                ->where(function ($query) {
                    $query->where('role', 'admin')
                        ->orWhere('role', 'owner');
                })
                ->exists();
        }

        return false;
    }
}
