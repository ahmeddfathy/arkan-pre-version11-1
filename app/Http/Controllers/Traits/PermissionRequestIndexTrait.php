<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Http\Request;
use App\Models\PermissionRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

trait PermissionRequestIndexTrait
{
    public function index(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view_permission')) {
            abort(403, 'ليس لديك صلاحية عرض طلبات الاستئذان');
        }

        $user = Auth::user();
        $employeeName = $request->input('employee_name');
        $status = $request->input('status');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $now = now();
        $currentMonthStart = $now->day >= 26
            ? $now->copy()->startOfDay()->setDay(26)
            : $now->copy()->subMonth()->startOfDay()->setDay(26);

        $currentMonthEnd = $now->day >= 26
            ? $now->copy()->addMonth()->startOfDay()->setDay(25)->endOfDay()
            : $now->copy()->startOfDay()->setDay(25)->endOfDay();

        $dateStart = $fromDate ? Carbon::parse($fromDate)->startOfDay() : $currentMonthStart;
        $dateEnd = $toDate ? Carbon::parse($toDate)->endOfDay() : $currentMonthEnd;

        $myRequestsQuery = PermissionRequest::with('user')
            ->where('user_id', $user->id);

        if ($status) {
            $myRequestsQuery->where('status', $status);
        }

        if ($fromDate && $toDate) {
            $myRequestsQuery->whereBetween('departure_time', [$dateStart, $dateEnd]);
        }

        $myRequests = $myRequestsQuery->latest()->paginate(10);

        $totalUsedMinutes = PermissionRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereBetween('departure_time', [$dateStart, $dateEnd])
            ->sum('minutes_used');

        $teamMembersMinutes = [];
        if ($user->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager', 'hr'])) {
            $teamMembers = $user->currentTeam ? $user->currentTeam->users->pluck('id') : collect();

            foreach ($teamMembers as $memberId) {
                $teamMembersMinutes[$memberId] = PermissionRequest::where('user_id', $memberId)
                    ->where('status', 'approved')
                    ->whereBetween('departure_time', [$currentMonthStart, $currentMonthEnd])
                    ->sum('minutes_used');
            }
        }

        $teamRequests = PermissionRequest::where('id', 0)->paginate(10);
        $noTeamRequests = PermissionRequest::where('id', 0)->paginate(10);
        $hrRequests = PermissionRequest::where('id', 0)->paginate(10);
        $remainingMinutes = [];

        if ($user->hasRole('hr')) {
            $hrQuery = PermissionRequest::with(['user', 'violations'])
                ->where(function ($query) use ($user) {
                    $query->whereHas('user', function ($q) use ($user) {
                        $q->whereDoesntHave('roles', function ($q) {
                            $q->whereIn('name', ['hr', 'company_manager']);
                        });
                    });
                });

            if ($employeeName) {
                $hrQuery->whereHas('user', function ($q) use ($employeeName) {
                    $q->where('name', 'like', "%{$employeeName}%");
                });
            }

            if ($status) {
                $hrQuery->where('status', $status);
            }

            if ($fromDate && $toDate) {
                $hrQuery->whereBetween('departure_time', [$dateStart, $dateEnd]);
            }

            $noTeamUserIds = User::whereDoesntHave('teams')->pluck('id');

            $teamMemberIds = collect([]);
            if ($user->currentTeam) {
                $teamMemberIds = $user->currentTeam->users->pluck('id');
            }

            $hrQuery->whereHas('user', function ($query) use ($teamMemberIds, $noTeamUserIds) {
                $query->whereNotIn('id', $teamMemberIds)
                    ->whereNotIn('id', $noTeamUserIds);
            });

            $hrRequests = $hrQuery->latest()->paginate(10, ['*'], 'hr_page');

            $teamMemberIds = collect([]);
            if ($user->currentTeam) {
                $teamMemberIds = $user->currentTeam->users->pluck('id');
            }

            $noTeamUserIds = User::whereDoesntHave('teams')->pluck('id');

            $teamQuery = PermissionRequest::with(['user', 'violations'])
                ->whereIn('user_id', $teamMemberIds);

            if ($employeeName) {
                $teamQuery->whereHas('user', function ($q) use ($employeeName) {
                    $q->where('name', 'like', "%{$employeeName}%");
                });
            }

            if ($status) {
                $teamQuery->where('status', $status);
            }

            if ($fromDate && $toDate) {
                $teamQuery->whereBetween('departure_time', [$dateStart, $dateEnd]);
            }

            $teamRequests = $teamQuery->latest()->paginate(10, ['*'], 'team_page');

            $teamUserIds = $teamRequests->pluck('user_id')->unique();
            foreach ($teamUserIds as $userId) {
                $remainingMinutes[$userId] = $this->permissionRequestService->getRemainingMinutes($userId);
            }

            $noTeamQuery = PermissionRequest::with(['user', 'violations'])
                ->whereHas('user', function ($q) {
                    $q->whereDoesntHave('teams');
                })
                ->whereBetween('departure_time', [$dateStart, $dateEnd]);

            if ($employeeName) {
                $noTeamQuery->whereHas('user', function ($q) use ($employeeName) {
                    $q->where('name', 'like', "%{$employeeName}%");
                });
            }

            if ($status) {
                $noTeamQuery->where('status', $status);
            }

            $noTeamRequests = $noTeamQuery->latest()->paginate(10);
        } elseif ($user->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager'])) {
            $team = $user->currentTeam;
            if ($team) {
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

                $teamMembers = $this->permissionRequestService->getAllowedUsers($user)
                    ->pluck('id')
                    ->toArray();

                $teamQuery = PermissionRequest::with(['user', 'violations'])
                    ->whereIn('user_id', $teamMembers);

                if ($employeeName) {
                    $teamQuery->whereHas('user', function ($q) use ($employeeName) {
                        $q->where('name', 'like', "%{$employeeName}%");
                    });
                }

                if ($status) {
                    $teamQuery->where('status', $status);
                }

                if ($fromDate && $toDate) {
                    $teamQuery->whereBetween('departure_time', [$dateStart, $dateEnd]);
                }

                $teamRequests = $teamQuery->latest()->paginate(10, ['*'], 'team_page');

                foreach ($teamMembers as $userId) {
                    $remainingMinutes[$userId] = $this->permissionRequestService->getRemainingMinutes($userId);
                }
            }
        }

        if (Auth::user()->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager', 'hr'])) {
            $users = $this->permissionRequestService->getAllowedUsers(Auth::user());
        } else {
            $users = User::when($user->hasRole('hr'), function ($query) {
                return $query->whereDoesntHave('teams');
            }, function ($query) use ($user) {
                if ($user->currentTeam) {
                    return $query->whereIn('id', $user->currentTeam->users->pluck('id'));
                }
                return $query->where('id', $user->id);
            })->get();
        }

        $statistics = $this->getStatistics($user, $dateStart, $dateEnd);

        return view('permission-requests.index', compact(
            'myRequests',
            'teamRequests',
            'noTeamRequests',
            'users',
            'remainingMinutes',
            'totalUsedMinutes',
            'teamMembersMinutes',
            'currentMonthStart',
            'currentMonthEnd',
            'dateStart',
            'dateEnd',
            'statistics',
            'hrRequests'
        ));
    }
}
