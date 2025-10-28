<?php

namespace App\Http\Controllers\Traits;

use App\Models\User;
use App\Models\PermissionRequest;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

trait PermissionRequestStatisticsTrait
{
    public function showAudits($id)
    {
        $user = Auth::user();

        // التحقق من صلاحيات المستخدم
        if (!$user->hasRole('hr')) {
            abort(403, 'غير مصرح لك بعرض سجل التغييرات');
        }

        $permissionRequest = PermissionRequest::findOrFail($id);

        return redirect()->route('audit-log.index', [
            'request_type' => PermissionRequest::class,
            'model_id' => $id
        ]);
    }

    protected function getStatistics($user, $dateStart, $dateEnd)
    {
        $statistics = [
            'personal' => [
                'total_requests' => 0,
                'approved_requests' => 0,
                'rejected_requests' => 0,
                'pending_requests' => 0,
                'total_minutes' => 0,
                'on_time_returns' => 0,
                'late_returns' => 0,
            ],
            'team' => [
                'total_requests' => 0,
                'approved_requests' => 0,
                'rejected_requests' => 0,
                'pending_requests' => 0,
                'total_minutes' => 0,
                'employees_exceeded_limit' => 0,
                'most_requested_employee' => null,
                'highest_minutes_employee' => null,
            ],
            'hr' => [
                'total_requests' => 0,
                'approved_requests' => 0,
                'rejected_requests' => 0,
                'pending_requests' => 0,
                'total_minutes' => 0,
                'employees_exceeded_limit' => 0,
                'most_requested_employee' => null,
                'highest_minutes_employee' => null,
                'departments_stats' => [],
                'daily_stats' => [],
                'weekly_stats' => [],
                'return_status_stats' => [],
                'busiest_days' => [],
                'busiest_hours' => [],
                'comparison_with_previous' => []
            ],
            'monthly_trend' => [],
        ];

        $personalStats = PermissionRequest::where('user_id', $user->id)
            ->whereBetween('departure_time', [$dateStart, $dateEnd])
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as rejected,
                SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = "approved" THEN minutes_used ELSE 0 END) as total_minutes,
                SUM(CASE WHEN returned_on_time = 1 THEN 1 ELSE 0 END) as on_time,
                SUM(CASE WHEN returned_on_time = 2 THEN 1 ELSE 0 END) as late
            ')
            ->first();

        $statistics['personal'] = [
            'total_requests' => $personalStats->total ?? 0,
            'approved_requests' => $personalStats->approved ?? 0,
            'rejected_requests' => $personalStats->rejected ?? 0,
            'pending_requests' => $personalStats->pending ?? 0,
            'total_minutes' => $personalStats->total_minutes ?? 0,
            'on_time_returns' => $personalStats->on_time ?? 0,
            'late_returns' => $personalStats->late ?? 0,
        ];

        if (
            $user->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager', 'hr']) &&
            ($user->currentTeam || $user->ownedTeams->count() > 0)
        ) {
            $teams = collect();

            if ($user->hasRole('hr')) {
                $teams = $user->ownedTeams;
            } else {
                $teams = $user->currentTeam ? collect([$user->currentTeam]) : collect();
            }

            foreach ($teams as $team) {
                $allowedRoles = $this->getAllowedRoles($user);
                $teamMembers = $this->getTeamMembers($team, $allowedRoles);

                $teamStats = PermissionRequest::whereIn('user_id', $teamMembers)
                    ->whereBetween('departure_time', [$dateStart, $dateEnd])
                    ->selectRaw('
                        COUNT(*) as total,
                        SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved,
                        SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as rejected,
                        SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN status = "approved" THEN minutes_used ELSE 0 END) as total_minutes
                    ')
                    ->first();

                $exceededLimit = DB::table(function ($query) use ($teamMembers, $dateStart, $dateEnd) {
                    $query->from('permission_requests')
                        ->select('user_id', DB::raw('SUM(minutes_used) as total_minutes'))
                        ->whereIn('user_id', $teamMembers)
                        ->where('status', 'approved')
                        ->whereBetween('departure_time', [$dateStart, $dateEnd])
                        ->groupBy('user_id');
                }, 'exceeded_users')
                    ->where('total_minutes', '>', 180)
                    ->count();

                $mostRequested = DB::table(function ($query) use ($teamMembers, $dateStart, $dateEnd) {
                    $query->from('permission_requests')
                        ->select('user_id', DB::raw('COUNT(*) as request_count'))
                        ->whereIn('user_id', $teamMembers)
                        ->whereBetween('departure_time', [$dateStart, $dateEnd])
                        ->groupBy('user_id');
                }, 'request_counts')
                    ->join('users', 'users.id', '=', 'request_counts.user_id')
                    ->select('users.name', 'request_counts.request_count')
                    ->orderByDesc('request_count')
                    ->first();

                $highestMinutes = DB::table(function ($query) use ($teamMembers, $dateStart, $dateEnd) {
                    $query->from('permission_requests')
                        ->select('user_id', DB::raw('SUM(minutes_used) as total_minutes'))
                        ->whereIn('user_id', $teamMembers)
                        ->where('status', 'approved')
                        ->whereBetween('departure_time', [$dateStart, $dateEnd])
                        ->groupBy('user_id');
                }, 'minute_totals')
                    ->join('users', 'users.id', '=', 'minute_totals.user_id')
                    ->select('users.name', 'minute_totals.total_minutes')
                    ->orderByDesc('total_minutes')
                    ->first();

                $exceededEmployees = DB::table(function ($query) use ($teamMembers, $dateStart, $dateEnd) {
                    $query->from('permission_requests')
                        ->select('user_id', DB::raw('SUM(minutes_used) as total_minutes'))
                        ->whereIn('user_id', $teamMembers)
                        ->where('status', 'approved')
                        ->whereBetween('departure_time', [$dateStart, $dateEnd])
                        ->groupBy('user_id')
                        ->having('total_minutes', '>', 180);
                }, 'exceeded_users')
                    ->join('users', 'users.id', '=', 'exceeded_users.user_id')
                    ->select('users.name', 'exceeded_users.total_minutes')
                    ->orderByDesc('total_minutes')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'name' => $item->name,
                            'total_minutes' => $item->total_minutes
                        ];
                    });

                $statistics['team'] = [
                    'total_requests' => $teamStats->total ?? 0,
                    'approved_requests' => $teamStats->approved ?? 0,
                    'rejected_requests' => $teamStats->rejected ?? 0,
                    'pending_requests' => $teamStats->pending ?? 0,
                    'total_minutes' => $teamStats->total_minutes ?? 0,
                    'employees_exceeded_limit' => $exceededLimit,
                    'most_requested_employee' => $mostRequested ? [
                        'name' => $mostRequested->name,
                        'count' => $mostRequested->request_count
                    ] : null,
                    'highest_minutes_employee' => $highestMinutes ? [
                        'name' => $highestMinutes->name,
                        'minutes' => $highestMinutes->total_minutes
                    ] : null,
                    'exceeded_employees' => $exceededEmployees,
                    'team_name' => $team->name
                ];

                $monthlyTrend = PermissionRequest::whereIn('user_id', $teamMembers)
                    ->whereBetween('departure_time', [$dateStart, $dateEnd])
                    ->selectRaw('
                        DATE_FORMAT(departure_time, "%Y-%m") as month,
                        COUNT(*) as total_requests,
                        SUM(CASE WHEN status = "approved" THEN minutes_used ELSE 0 END) as total_minutes
                    ')
                    ->groupBy('month')
                    ->orderBy('month')
                    ->get();

                $statistics['monthly_trend'] = $monthlyTrend->map(function ($item) {
                    return [
                        'month' => $item->month,
                        'total_requests' => $item->total_requests,
                        'total_minutes' => $item->total_minutes,
                    ];
                });
            }
        }

        if ($user->hasRole('hr')) {
            $excludedRoles = ['company_manager', 'hr'];

            $allEmployees = User::whereDoesntHave('roles', function ($q) use ($excludedRoles) {
                $q->whereIn('name', $excludedRoles);
            })->pluck('id')->toArray();

            $hrStats = PermissionRequest::whereIn('user_id', $allEmployees)
                ->whereBetween('departure_time', [$dateStart, $dateEnd])
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as rejected,
                    SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = "approved" THEN minutes_used ELSE 0 END) as total_minutes
                ')
                ->first();

            $exceededLimit = DB::table(function ($query) use ($allEmployees, $dateStart, $dateEnd) {
                $query->from('permission_requests')
                    ->select('user_id', DB::raw('SUM(minutes_used) as total_minutes'))
                    ->whereIn('user_id', $allEmployees)
                    ->where('status', 'approved')
                    ->whereBetween('departure_time', [$dateStart, $dateEnd])
                    ->groupBy('user_id');
            }, 'exceeded_users')
                ->where('total_minutes', '>', 180)
                ->count();

            $mostRequested = DB::table(function ($query) use ($allEmployees, $dateStart, $dateEnd) {
                $query->from('permission_requests')
                    ->select('user_id', DB::raw('COUNT(*) as request_count'))
                    ->whereIn('user_id', $allEmployees)
                    ->whereBetween('departure_time', [$dateStart, $dateEnd])
                    ->groupBy('user_id');
            }, 'request_counts')
                ->join('users', 'users.id', '=', 'request_counts.user_id')
                ->select('users.name', 'request_counts.request_count')
                ->orderByDesc('request_count')
                ->first();

            $highestMinutes = DB::table(function ($query) use ($allEmployees, $dateStart, $dateEnd) {
                $query->from('permission_requests')
                    ->select('user_id', DB::raw('SUM(minutes_used) as total_minutes'))
                    ->whereIn('user_id', $allEmployees)
                    ->where('status', 'approved')
                    ->whereBetween('departure_time', [$dateStart, $dateEnd])
                    ->groupBy('user_id');
            }, 'minute_totals')
                ->join('users', 'users.id', '=', 'minute_totals.user_id')
                ->select('users.name', 'minute_totals.total_minutes')
                ->orderByDesc('total_minutes')
                ->first();

            $exceededEmployees = DB::table(function ($query) use ($allEmployees, $dateStart, $dateEnd) {
                $query->from('permission_requests')
                    ->select('user_id', DB::raw('SUM(minutes_used) as total_minutes'))
                    ->whereIn('user_id', $allEmployees)
                    ->where('status', 'approved')
                    ->whereBetween('departure_time', [$dateStart, $dateEnd])
                    ->groupBy('user_id')
                    ->having('total_minutes', '>', 180);
            }, 'exceeded_users')
                ->join('users', 'users.id', '=', 'exceeded_users.user_id')
                ->select('users.name', 'exceeded_users.total_minutes')
                ->orderByDesc('total_minutes')
                ->get()
                ->map(function ($item) {
                    return [
                        'name' => $item->name,
                        'total_minutes' => $item->total_minutes
                    ];
                });

            $departmentStats = DB::table('users')
                ->leftJoin('permission_requests', function ($join) use ($dateStart, $dateEnd) {
                    $join->on('users.id', '=', 'permission_requests.user_id')
                        ->whereBetween('permission_requests.departure_time', [$dateStart, $dateEnd]);
                })
                ->whereIn('users.id', $allEmployees)
                ->whereNotNull('users.department')
                ->select(
                    'users.department as dept_name',
                    DB::raw('COUNT(DISTINCT users.id) as employee_count'),
                    DB::raw('COUNT(permission_requests.id) as request_count'),
                    DB::raw('SUM(CASE WHEN permission_requests.status = "approved" THEN permission_requests.minutes_used ELSE 0 END) as total_minutes'),
                    DB::raw('SUM(CASE WHEN permission_requests.returned_on_time = 1 THEN 1 ELSE 0 END) as on_time_returns'),
                    DB::raw('SUM(CASE WHEN permission_requests.returned_on_time = 2 THEN 1 ELSE 0 END) as late_returns')
                )
                ->groupBy('users.department')
                ->orderByDesc('request_count')
                ->get()
                ->map(function ($item) {
                    return [
                        'name' => $item->dept_name ?: 'غير محدد',
                        'employee_count' => $item->employee_count,
                        'request_count' => $item->request_count,
                        'total_minutes' => $item->total_minutes ?? 0,
                        'avg_minutes' => $item->employee_count > 0 ? round(($item->total_minutes ?? 0) / $item->employee_count) : 0,
                        'on_time_returns' => $item->on_time_returns ?? 0,
                        'late_returns' => $item->late_returns ?? 0
                    ];
                });

            $dailyStats = PermissionRequest::whereIn('user_id', $allEmployees)
                ->whereBetween('departure_time', [$dateStart, $dateEnd])
                ->selectRaw('
                    DATE(departure_time) as date,
                    COUNT(*) as total_requests,
                    SUM(CASE WHEN status = "approved" THEN minutes_used ELSE 0 END) as total_minutes,
                    SUM(CASE WHEN returned_on_time = 1 THEN 1 ELSE 0 END) as on_time_returns,
                    SUM(CASE WHEN returned_on_time = 2 THEN 1 ELSE 0 END) as late_returns
                ')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(function ($item) {
                    return [
                        'date' => $item->date,
                        'total_requests' => $item->total_requests,
                        'total_minutes' => $item->total_minutes,
                        'on_time_returns' => $item->on_time_returns,
                        'late_returns' => $item->late_returns
                    ];
                });

            $weeklyStats = PermissionRequest::whereIn('user_id', $allEmployees)
                ->whereBetween('departure_time', [$dateStart, $dateEnd])
                ->selectRaw('
                    YEAR(departure_time) as year,
                    WEEK(departure_time) as week,
                    COUNT(*) as total_requests,
                    SUM(CASE WHEN status = "approved" THEN minutes_used ELSE 0 END) as total_minutes,
                    SUM(CASE WHEN returned_on_time = 1 THEN 1 ELSE 0 END) as on_time_returns,
                    SUM(CASE WHEN returned_on_time = 2 THEN 1 ELSE 0 END) as late_returns
                ')
                ->groupBy('year', 'week')
                ->orderBy('year')
                ->orderBy('week')
                ->get()
                ->map(function ($item) {
                    $date = new \DateTime();
                    $date->setISODate($item->year, $item->week);
                    return [
                        'week_start' => $date->format('Y-m-d'),
                        'year_week' => $item->year . '-' . str_pad($item->week, 2, '0', STR_PAD_LEFT),
                        'total_requests' => $item->total_requests,
                        'total_minutes' => $item->total_minutes,
                        'on_time_returns' => $item->on_time_returns,
                        'late_returns' => $item->late_returns
                    ];
                });

            $busiestDays = PermissionRequest::whereIn('user_id', $allEmployees)
                ->whereBetween('departure_time', [$dateStart, $dateEnd])
                ->selectRaw('
                    DAYNAME(departure_time) as day_name,
                    COUNT(*) as total_requests,
                    SUM(CASE WHEN status = "approved" THEN minutes_used ELSE 0 END) as total_minutes
                ')
                ->groupBy('day_name')
                ->orderByDesc('total_requests')
                ->get()
                ->map(function ($item) {
                    return [
                        'day_name' => $item->day_name,
                        'total_requests' => $item->total_requests,
                        'total_minutes' => $item->total_minutes
                    ];
                });

            $busiestHours = PermissionRequest::whereIn('user_id', $allEmployees)
                ->whereBetween('departure_time', [$dateStart, $dateEnd])
                ->selectRaw('
                    HOUR(departure_time) as hour,
                    COUNT(*) as total_requests,
                    SUM(CASE WHEN status = "approved" THEN minutes_used ELSE 0 END) as total_minutes
                ')
                ->groupBy('hour')
                ->orderByDesc('total_requests')
                ->get()
                ->map(function ($item) {
                    return [
                        'hour' => $item->hour,
                        'hour_formatted' => sprintf('%02d:00', $item->hour),
                        'total_requests' => $item->total_requests,
                        'total_minutes' => $item->total_minutes
                    ];
                });

            $returnStatusStats = PermissionRequest::whereIn('user_id', $allEmployees)
                ->whereBetween('departure_time', [$dateStart, $dateEnd])
                ->where('status', 'approved')
                ->selectRaw('
                    SUM(CASE WHEN returned_on_time = 1 THEN 1 ELSE 0 END) as on_time_returns,
                    SUM(CASE WHEN returned_on_time = 2 THEN 1 ELSE 0 END) as late_returns,
                    SUM(CASE WHEN returned_on_time IS NULL THEN 1 ELSE 0 END) as undefined_returns,
                    COUNT(*) as total_returns
                ')
                ->first();

            $previousStart = (clone $dateStart)->subDays($dateEnd->diffInDays($dateStart) + 1);
            $previousEnd = (clone $dateStart)->subDay();

            $previousStats = PermissionRequest::whereIn('user_id', $allEmployees)
                ->whereBetween('departure_time', [$previousStart, $previousEnd])
                ->selectRaw('
                    COUNT(*) as total_requests,
                    SUM(CASE WHEN status = "approved" THEN minutes_used ELSE 0 END) as total_minutes,
                    SUM(CASE WHEN returned_on_time = 1 THEN 1 ELSE 0 END) as on_time_returns,
                    SUM(CASE WHEN returned_on_time = 2 THEN 1 ELSE 0 END) as late_returns
                ')
                ->first();

            $comparisonStats = [
                'current_period' => [
                    'start' => $dateStart->format('Y-m-d'),
                    'end' => $dateEnd->format('Y-m-d'),
                    'total_requests' => $hrStats->total ?? 0,
                    'total_minutes' => $hrStats->total_minutes ?? 0,
                    'on_time_returns' => $returnStatusStats->on_time_returns ?? 0,
                    'late_returns' => $returnStatusStats->late_returns ?? 0
                ],
                'previous_period' => [
                    'start' => $previousStart->format('Y-m-d'),
                    'end' => $previousEnd->format('Y-m-d'),
                    'total_requests' => $previousStats->total_requests ?? 0,
                    'total_minutes' => $previousStats->total_minutes ?? 0,
                    'on_time_returns' => $previousStats->on_time_returns ?? 0,
                    'late_returns' => $previousStats->late_returns ?? 0
                ],
                'percentage_change' => [
                    'total_requests' => $previousStats->total_requests > 0
                        ? round((($hrStats->total - $previousStats->total_requests) / $previousStats->total_requests) * 100, 2)
                        : 100,
                    'total_minutes' => $previousStats->total_minutes > 0
                        ? round((($hrStats->total_minutes - $previousStats->total_minutes) / $previousStats->total_minutes) * 100, 2)
                        : 100,
                    'on_time_returns' => $previousStats->on_time_returns > 0
                        ? round((($returnStatusStats->on_time_returns - $previousStats->on_time_returns) / $previousStats->on_time_returns) * 100, 2)
                        : 100,
                    'late_returns' => $previousStats->late_returns > 0
                        ? round((($returnStatusStats->late_returns - $previousStats->late_returns) / $previousStats->late_returns) * 100, 2)
                        : 100
                ]
            ];

            $statistics['hr'] = [
                'total_requests' => $hrStats->total ?? 0,
                'approved_requests' => $hrStats->approved ?? 0,
                'rejected_requests' => $hrStats->rejected ?? 0,
                'pending_requests' => $hrStats->pending ?? 0,
                'total_minutes' => $hrStats->total_minutes ?? 0,
                'employees_exceeded_limit' => $exceededLimit,
                'most_requested_employee' => $mostRequested ? [
                    'name' => $mostRequested->name,
                    'count' => $mostRequested->request_count
                ] : null,
                'highest_minutes_employee' => $highestMinutes ? [
                    'name' => $highestMinutes->name,
                    'minutes' => $highestMinutes->total_minutes
                ] : null,
                'exceeded_employees' => $exceededEmployees,
                'departments' => $departmentStats,
                'daily_stats' => $dailyStats,
                'weekly_stats' => $weeklyStats,
                'return_status_stats' => [
                    'on_time_returns' => $returnStatusStats->on_time_returns ?? 0,
                    'late_returns' => $returnStatusStats->late_returns ?? 0,
                    'undefined_returns' => $returnStatusStats->undefined_returns ?? 0,
                    'total_returns' => $returnStatusStats->total_returns ?? 0
                ],
                'busiest_days' => $busiestDays,
                'busiest_hours' => $busiestHours,
                'comparison_with_previous' => $comparisonStats
            ];

            $monthlyTrend = PermissionRequest::whereIn('user_id', $allEmployees)
                ->whereBetween('departure_time', [$dateStart, $dateEnd])
                ->selectRaw('
                    DATE_FORMAT(departure_time, "%Y-%m") as month,
                    COUNT(*) as total_requests,
                    SUM(CASE WHEN status = "approved" THEN minutes_used ELSE 0 END) as total_minutes
                ')
                ->groupBy('month')
                ->orderBy('month')
                ->get();

            $statistics['monthly_trend'] = $monthlyTrend->map(function ($item) {
                return [
                    'month' => $item->month,
                    'total_requests' => $item->total_requests,
                    'total_minutes' => $item->total_minutes,
                ];
            });
        }

        return $statistics;
    }

    private function getAllowedRoles($user)
    {
        if ($user->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader'])) {
            return ['employee'];
        } elseif ($user->hasRole(['department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager'])) {
            return ['employee', 'team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader'];
        } elseif ($user->hasRole(['project_manager', 'operations_manager'])) {
            return ['employee', 'team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager'];
        } elseif ($user->hasRole('company_manager')) {
            return ['employee', 'team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager'];
        } elseif ($user->hasRole('hr')) {
            return ['employee', 'team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager'];
        }
        return [];
    }

    private function getTeamMembers($team, $allowedRoles)
    {
        if (!$team) {
            return [];
        }

        return $team->users()
            ->whereHas('roles', function ($q) use ($allowedRoles) {
                $q->whereIn('name', $allowedRoles);
            })
            ->pluck('users.id')
            ->toArray();
    }
}
