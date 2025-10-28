<?php

namespace App\Policies;

use App\Models\AbsenceRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AbsenceRequestPolicy
{
    public function viewAny(User $user)
    {
        return $user->hasPermissionTo('view_absence');
    }

    public function view(User $user, AbsenceRequest $absenceRequest)
    {
        if ($user->hasRole('hr')) {
            return true;
        }

        if ($user->id === $absenceRequest->user_id) {
            return true;
        }

        return DB::table('team_user')
            ->where('user_id', $user->id)
            ->where(function ($query) {
                $query->where('role', 'admin')
                    ->orWhere('role', 'owner');
            })
            ->exists();
    }

    public function create(User $user)
    {
        return $user->hasPermissionTo('create_absence');
    }

    public function update(User $user, AbsenceRequest $absenceRequest)
    {
        if (!$user->hasPermissionTo('update_absence')) {
            return false;
        }
        return $user->id === $absenceRequest->user_id && $absenceRequest->status === 'pending';
    }

    public function delete(User $user, AbsenceRequest $absenceRequest)
    {
        if (!$user->hasPermissionTo('delete_absence')) {
            return false;
        }
        return $user->id === $absenceRequest->user_id && $absenceRequest->status === 'pending';
    }

    public function updateStatus(User $user, AbsenceRequest $absenceRequest)
    {
        if ($user->hasRole('hr') && $user->hasPermissionTo('hr_respond_absence_request')) {
            return true;
        }

        if ($user->hasPermissionTo('manager_respond_absence_request')) {
            return DB::table('team_user')
                ->where('user_id', $user->id)
                ->where(function ($query) {
                    $query->where('role', 'admin')
                        ->orWhere('role', 'owner');
                })
                ->exists();
        }

        return false;
    }

    public function modifyResponse(User $user, AbsenceRequest $absenceRequest)
    {
        return $this->updateStatus($user, $absenceRequest);
    }

    public function resetStatus(User $user, AbsenceRequest $absenceRequest)
    {
        return $this->updateStatus($user, $absenceRequest);
    }
}
