<?php

namespace App\Services\TaskController;

use App\Models\User;
use App\Models\CompanyService;
use App\Models\RoleHierarchy;
use App\Models\DepartmentRole;
use App\Services\Auth\RoleCheckService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class TaskHierarchyService
{
    protected $roleCheckService;

    public function __construct(RoleCheckService $roleCheckService)
    {
        $this->roleCheckService = $roleCheckService;
    }


    public function canUserAccessService(int $serviceId): bool
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->hasRole(['company_manager', 'hr', 'project_manager'])) {
            return true;
        }

        if (!$serviceId) {
            return true;
        }

        $service = CompanyService::find($serviceId);
        if (!$service) {
            return false;
        }

        if ($this->isGraphicService($service)) {
            return true;
        }

        if ($this->isGraphicOnlyUser()) {
            return $this->isGraphicService($service);
        }

        if ($user->department) {
            if (!$this->userHasValidDepartmentRole()) {
                return false;
            }

            return $service->department === $user->department;
        }

        return true;
    }


    public function getCurrentUserHierarchyLevel(User $user): ?int
    {
        return RoleHierarchy::getUserMaxHierarchyLevel($user);
    }


    public function getUserHierarchyLevel(User $user): ?int
    {
        return $this->getCurrentUserHierarchyLevel($user);
    }


    public function isGraphicOnlyUser(): bool
    {
        $graphicOnlyRoles = $this->getGraphicOnlyRoles();
        return $this->roleCheckService->userHasRole($graphicOnlyRoles);
    }


    public function isGraphicService(CompanyService $service): bool
    {
        $serviceName = strtolower($service->name);
        return (
            strpos($serviceName, 'جرافيك') !== false ||
            strpos($serviceName, 'تصميم') !== false ||
            strpos($serviceName, 'graphic') !== false ||
            strpos($serviceName, 'design') !== false
        );
    }


    public function userHasValidDepartmentRole(): bool
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->hasRole(['company_manager', 'hr', 'project_manager'])) {
            return true;
        }

        if (!$user->department) {
            return false;
        }

        $departmentRoles = $this->getCurrentUserDepartmentRoles()->pluck('name')->toArray();

        $userRoles = $user->roles->pluck('name')->toArray();

        return !empty(array_intersect($userRoles, $departmentRoles));
    }


    public function filterUsersByNewHierarchy($users, User $currentUser)
    {

        if ($currentUser->hasRole(['company_manager', 'hr', 'project_manager'])) {
            return $users;
        }


        $globalLevel = $this->getCurrentUserHierarchyLevel($currentUser);
        $departmentLevel = DepartmentRole::getUserDepartmentHierarchyLevel($currentUser);


        if (!$currentUser->department) {
            return collect();
        }


        if ($globalLevel && $globalLevel >= 5) {
            return $users->filter(function($user) use ($globalLevel) {
                $userGlobalLevel = $this->getUserHierarchyLevel($user);
                return $userGlobalLevel === null || $userGlobalLevel <= $globalLevel;
            });
        }

        if (($departmentLevel && $departmentLevel >= 4) || ($globalLevel && $globalLevel == 4)) {
            $effectiveLevel = $departmentLevel ?? $globalLevel;
            return $users->filter(function($user) use ($currentUser, $effectiveLevel) {
                if ($user->department !== $currentUser->department) {
                    return false;
                }

                $userDepartmentLevel = DepartmentRole::getUserDepartmentHierarchyLevel($user);
                return $userDepartmentLevel === null || $userDepartmentLevel <= $effectiveLevel;
            });
        }

        if (($departmentLevel && $departmentLevel == 3) || ($globalLevel && $globalLevel == 3)) {
            return $this->filterUsersByTeam($users, $currentUser);
        }


        return $users->filter(function($user) use ($currentUser) {
            return $user->department === $currentUser->department;
        });
    }


    public function filterUsersByHierarchy($users, User $currentUser)
    {
        if (!$currentUser->department) {
            return $users;
        }

        $currentUserLevel = DepartmentRole::getUserDepartmentHierarchyLevel($currentUser);

        return $users->filter(function($user) use ($currentUser, $currentUserLevel) {
            if ($user->department !== $currentUser->department) {
                return false;
            }

            $userLevel = DepartmentRole::getUserDepartmentHierarchyLevel($user);

            if ($currentUserLevel >= 4) {
                return true;
            } elseif ($currentUserLevel == 3) {
                return $userLevel <= 3;
            } else {
                return true;
            }
        });
    }


    public function filterUsersByTeam($users, User $currentUser)
    {
        $currentTeamId = $currentUser->current_team_id;

        if (!$currentTeamId) {
            $ownedTeam = DB::table('teams')
                ->where('user_id', $currentUser->id)
                ->first();
            $currentTeamId = $ownedTeam ? $ownedTeam->id : null;
        }

        if (!$currentTeamId) {
            return collect();
        }

        return $users->filter(function($user) use ($currentTeamId, $currentUser) {
            if ($user->id === $currentUser->id) {
                return true;
            }

            if ($user->current_team_id === $currentTeamId) {
                return true;
            }

            $isMember = DB::table('team_user')
                ->where('team_id', $currentTeamId)
                ->where('user_id', $user->id)
                ->exists();

            return $isMember;
        });
    }


    public function addTeamInfoToUsers($users, bool $includeInName = false)
    {
        foreach ($users as $user) {
            $userLevel = $this->getUserHierarchyLevel($user);

            $teamName = 'غير محدد';

            $currentTeam = DB::table('users')
                ->join('teams', 'users.current_team_id', '=', 'teams.id')
                ->where('users.id', $user->id)
                ->select('teams.name as team_name')
                ->first();

            if ($currentTeam) {
                $teamName = $currentTeam->team_name;
            } else {
                $userTeam = DB::table('team_user')
                    ->join('teams', 'team_user.team_id', '=', 'teams.id')
                    ->where('team_user.user_id', $user->id)
                    ->select('teams.name as team_name')
                    ->first();

                if ($userTeam) {
                    $teamName = $userTeam->team_name;
                }
            }


            $user->hierarchy_level = $userLevel;
            $user->actual_team_name = $teamName;

            if ($includeInName) {
                $user->display_name = $user->name . ' (' . $teamName . ')';
                $user->team_badge = $teamName;
            } else {
                $user->display_name = $user->name;
            }
        }

        return $users;
    }


    public function getCurrentUserDepartmentRoles()
    {
        $user = Auth::user();

        if (!$user->department) {
            return collect();
        }

        return DB::table('department_roles')
            ->join('roles', 'department_roles.role_id', '=', 'roles.id')
            ->where('department_roles.department_name', $user->department)
            ->select('roles.name', 'roles.id')
            ->get();
    }


    private function getGraphicOnlyRoles(): array
    {
        return [
            'employee',
            'sales_employee',
            'marketing_team_employee',
            'financial_team_employee',
            'technical_team_employee',
            'technical_reviewer',
            'marketing_reviewer',
            'financial_reviewer'
        ];
    }


    public function canViewRoleUsers(string $roleName): bool
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        if ($roleName === 'company_manager') {
            return false;
        }

        if ($currentUser->hasRole(['company_manager', 'hr', 'project_manager'])) {
            return true;
        }

        if (!$currentUser->department) {
            return false;
        }

        $currentUserLevel = $this->getCurrentUserHierarchyLevel($currentUser);

        $roleLevel = RoleHierarchy::getRoleHierarchyLevelByName($roleName);

        return $currentUserLevel !== null && $roleLevel !== null && $currentUserLevel >= $roleLevel;
    }
}
