<?php

namespace App\Services\PermissionRequest;

use App\Models\PermissionRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class RequestQueryService
{
    public function getAllRequests($filters = []): LengthAwarePaginator
    {
        $user = Auth::user();
        $query = PermissionRequest::with('user');

        if (!empty($filters['employee_name'])) {
            $query->whereHas('user', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['employee_name'] . '%');
            });
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if ($user->hasRole('hr')) {
            return $query->whereHas('user', function ($q) {
                $q->whereDoesntHave('teams');
            })->latest()->paginate(10);
        } elseif ($user->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager'])) {
            $team = $user->currentTeam;
            if ($team) {
                $teamMembers = $team->users->pluck('id')->toArray();
                return $query->whereIn('user_id', $teamMembers)->latest()->paginate(10);
            }
        }

        return $query->where('user_id', $user->id)
            ->latest()
            ->paginate(10);
    }

    public function getUserRequests(int $userId): LengthAwarePaginator
    {
        return PermissionRequest::where('user_id', $userId)
            ->latest()
            ->paginate(10);
    }

    public function getAllowedUsers($user)
    {
        if ($user->hasRole('hr')) {
            $query = User::whereDoesntHave('roles', function ($q) {
                $q->whereIn('name', ['hr', 'company_manager']);
            });

            // Include team members if HR user has a current team
            if ($user->currentTeam) {
                $teamMembersIds = $user->currentTeam->users->pluck('id')->toArray();
                $query->orWhereIn('id', $teamMembersIds);
            }

            return $query->get();
        }

        if (!$user->currentTeam) {
            return collect();
        }

        $allowedRoles = [];
        if ($user->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader'])) {
            $allowedRoles = ['employee'];
        } elseif ($user->hasRole(['department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager'])) {
            $allowedRoles = ['employee', 'team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader'];
        } elseif ($user->hasRole(['project_manager', 'operations_manager'])) {
            $allowedRoles = ['employee', 'team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager'];
        } elseif ($user->hasRole('company_manager')) {
            $allowedRoles = ['employee', 'team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager'];
        }

        return $user->currentTeam->users()
            ->whereHas('roles', function ($q) use ($allowedRoles) {
                $q->whereIn('name', $allowedRoles);
            })
            ->whereDoesntHave('teams', function ($q) use ($user) {
                $q->where('teams.id', $user->currentTeam->id)
                    ->where(function ($q) {
                        $q->where('team_user.role', 'owner')
                            ->orWhere('team_user.role', 'admin');
                    });
            })
            ->get();
    }
}
