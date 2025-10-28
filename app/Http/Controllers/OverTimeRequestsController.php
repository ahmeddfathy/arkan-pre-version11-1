<?php

namespace App\Http\Controllers;

use App\Models\OverTimeRequests;
use App\Services\OverTimeRequestService;
use App\Services\NotificationOvertimeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OverTimeRequestsController extends Controller
{
    protected $overTimeRequestService;
    protected $notificationService;

    public function __construct(
        OverTimeRequestService $overTimeRequestService,
        NotificationOvertimeService $notificationService
    ) {
        $this->overTimeRequestService = $overTimeRequestService;
        $this->notificationService = $notificationService;
    }

    public function index(Request $request)
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        if (!$authUser->hasPermissionTo('view_overtime')) {
            abort(403, 'ليس لديك صلاحية عرض طلبات العمل الإضافي');
        }

        // تسجيل النشاط - دخول صفحة طلبات العمل الإضافي
        if (\Illuminate\Support\Facades\Auth::check()) {
            activity()
                ->causedBy(\Illuminate\Support\Facades\Auth::user())
                ->withProperties([
                    'action_type' => 'view_index',
                    'page' => 'overtime_requests_index',
                    'filters' => $request->all(),
                    'viewed_at' => now()->toDateTimeString(),
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip()
                ])
                ->log('دخل على صفحة طلبات العمل الإضافي');
        }

        /** @var \App\Models\User $user */
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

        $filters = [
            'employeeName' => $employeeName,
            'status' => $status,
            'startDate' => $dateStart,
            'endDate' => $dateEnd
        ];

        $canCreateOvertime = $user->hasPermissionTo('create_overtime');
        $canUpdateOvertime = $user->hasPermissionTo('update_overtime');
        $canDeleteOvertime = $user->hasPermissionTo('delete_overtime');
        $canRespondAsManager = $user->hasPermissionTo('manager_respond_overtime_request') && !$user->hasRole('hr');
        $canRespondAsHR = $user->hasPermissionTo('hr_respond_overtime_request');

        $myRequests = $this->overTimeRequestService->getUserRequests(
            $user->id,
            $dateStart,
            $dateEnd,
            $status
        );

        $teamRequests = collect([]);
        $noTeamRequests = collect([]);
        $hrRequests = collect([]);
        $users = collect([]);
        $pendingCount = 0;
        $overtimeHoursCount = [];
        $noTeamOvertimeHoursCount = [];
        $teamStatistics = [];

        if ($user->hasRole('hr')) {
            $noTeamRequests = $this->overTimeRequestService->getNoTeamRequests(
                $employeeName,
                $status,
                $dateStart,
                $dateEnd
            );

            $hrQuery = OverTimeRequests::with('user')
                ->whereHas('user', function ($query) {
                    $query->whereDoesntHave('roles', function ($q) {
                        $q->whereIn('name', ['hr', 'company_manager']);
                    });
                })
                ->whereBetween('overtime_date', [$dateStart, $dateEnd])
                ->when($employeeName, function ($query) use ($employeeName) {
                    $query->whereHas('user', function ($q) use ($employeeName) {
                        $q->where('name', 'like', "%{$employeeName}%");
                    });
                })
                ->when($status, function ($query) use ($status) {
                    $query->where('status', $status);
                });

            // Exclude users who are in team requests and users without teams
            $teamMemberIds = collect([]);
            if ($user->currentTeam) {
                $teamMemberIds = $user->currentTeam->users->pluck('id');
            }

            // Get IDs of users without teams
            $noTeamUserIds = User::whereDoesntHave('teams')->pluck('id');

            // Exclude both team members and users without teams from HR company requests
            $hrQuery->whereHas('user', function ($query) use ($teamMemberIds, $noTeamUserIds) {
                $query->whereNotIn('id', $teamMemberIds)
                    ->whereNotIn('id', $noTeamUserIds);
            });

            $hrRequests = $hrQuery->latest()->paginate(10, ['*'], 'hr_page');

            $users = User::select('id', 'name')->get();

            if ($user->currentTeam) {
                $teamMembers = $user->currentTeam->users->pluck('id')->toArray();

                $teamQuery = OverTimeRequests::with('user')
                    ->whereIn('user_id', $teamMembers)
                    ->whereBetween('overtime_date', [$dateStart, $dateEnd])
                    ->when($employeeName, function ($query) use ($employeeName) {
                        $query->whereHas('user', function ($q) use ($employeeName) {
                            $q->where('name', 'like', "%{$employeeName}%");
                        });
                    })
                    ->when($status, function ($query) use ($status) {
                        $query->where('status', $status);
                    });

                $pendingCount = $teamQuery->clone()->where('status', 'pending')->count();
                $teamRequests = $teamQuery->latest()->paginate(10, ['*'], 'team_page');

                $teamStatistics = [
                    'total_requests' => OverTimeRequests::whereIn('user_id', $teamMembers)
                        ->whereBetween('overtime_date', [$dateStart, $dateEnd])
                        ->count(),
                    'approved_requests' => OverTimeRequests::whereIn('user_id', $teamMembers)
                        ->where('status', 'approved')
                        ->whereBetween('overtime_date', [$dateStart, $dateEnd])
                        ->count(),
                    'pending_requests' => $pendingCount,
                    'total_hours' => OverTimeRequests::whereIn('user_id', $teamMembers)
                        ->where('status', 'approved')
                        ->whereBetween('overtime_date', [$dateStart, $dateEnd])
                        ->selectRaw('COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(end_time, start_time))/3600), 0) as total_hours')
                        ->value('total_hours'),
                    'team_employees' => User::select('users.id', 'users.name')
                        ->selectRaw('COUNT(DISTINCT over_time_requests.id) as total_requests')
                        ->selectRaw('COUNT(CASE WHEN over_time_requests.status = "approved" THEN 1 END) as approved_requests')
                        ->selectRaw('COUNT(CASE WHEN over_time_requests.status = "rejected" THEN 1 END) as rejected_requests')
                        ->selectRaw('COUNT(CASE WHEN over_time_requests.status = "pending" THEN 1 END) as pending_requests')
                        ->selectRaw('COALESCE(SUM(CASE WHEN over_time_requests.status = "approved" THEN TIME_TO_SEC(TIMEDIFF(over_time_requests.end_time, over_time_requests.start_time))/3600 ELSE 0 END), 0) as approved_hours')
                        ->leftJoin('over_time_requests', function ($join) use ($dateStart, $dateEnd) {
                            $join->on('users.id', '=', 'over_time_requests.user_id')
                                ->whereBetween('over_time_requests.overtime_date', [$dateStart, $dateEnd]);
                        })
                        ->whereIn('users.id', $teamMembers)
                        ->groupBy('users.id', 'users.name')
                        ->get(),
                    'most_active_employee' => User::whereIn('id', $teamMembers)
                        ->withCount(['overtimeRequests' => function ($query) use ($dateStart, $dateEnd) {
                            $query->whereBetween('overtime_date', [$dateStart, $dateEnd]);
                        }])
                        ->orderByDesc('overtime_requests_count')
                        ->first()
                ];
            }
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

                $teamMembers = $team->users()
                    ->whereHas('roles', function ($q) use ($allowedRoles) {
                        $q->whereIn('name', $allowedRoles);
                    })
                    ->whereDoesntHave('teams', function ($q) use ($team) {
                        $q->where('teams.id', $team->id)
                            ->where(function ($q) {
                                $q->where('team_user.role', 'owner')
                                    ->orWhere('team_user.role', 'admin');
                            });
                    })
                    ->pluck('users.id')
                    ->toArray();

                $teamRequests = OverTimeRequests::query()
                    ->with('user')
                    ->whereIn('user_id', $teamMembers)
                    ->whereBetween('overtime_date', [$dateStart, $dateEnd])
                    ->whereHas('user', function ($query) use ($team) {
                        $query->whereDoesntHave('teams', function ($q) use ($team) {
                            $q->where('teams.id', $team->id)
                                ->whereRaw('team_user.role = ?', ['admin']);
                        });
                    })
                    ->when($employeeName, function ($query) use ($employeeName) {
                        $query->whereHas('user', function ($q) use ($employeeName) {
                            $q->where('name', 'like', "%{$employeeName}%");
                        });
                    })
                    ->when($status, function ($query) use ($status) {
                        $query->where('status', $status);
                    });

                $pendingCount = $teamRequests->clone()->where('status', 'pending')->count();
                $teamRequests = $teamRequests->latest()->paginate(10);
                $users = $this->overTimeRequestService->getAllowedUsers(Auth::user());
            } else {
                // Return empty paginated collection when no team exists
                $teamRequests = OverTimeRequests::where('id', 0)->paginate(10);
                $users = collect();
            }
        }

        $myOvertimeHours = $this->overTimeRequestService->calculateOvertimeHours(
            $user->id,
            $dateStart,
            $dateEnd,
            $status
        );

        $statistics = [];
        $personalStatistics = [];
        $hrStatistics = [];

        $personalStatistics = [
            'total_requests' => OverTimeRequests::where('user_id', $user->id)
                ->whereBetween('overtime_date', [$dateStart, $dateEnd])
                ->count(),
            'approved_requests' => OverTimeRequests::where('user_id', $user->id)
                ->where('status', 'approved')
                ->whereBetween('overtime_date', [$dateStart, $dateEnd])
                ->count(),
            'pending_requests' => OverTimeRequests::where('user_id', $user->id)
                ->where('status', 'pending')
                ->whereBetween('overtime_date', [$dateStart, $dateEnd])
                ->count(),
            'rejected_requests' => OverTimeRequests::where('user_id', $user->id)
                ->where('status', 'rejected')
                ->whereBetween('overtime_date', [$dateStart, $dateEnd])
                ->count(),
            'total_hours' => OverTimeRequests::where('user_id', $user->id)
                ->where('status', 'approved')
                ->whereBetween('overtime_date', [$dateStart, $dateEnd])
                ->selectRaw('COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(end_time, start_time))/3600), 0) as total_hours')
                ->value('total_hours')
        ];

        if ($user->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager'])) {
            $teamStatistics = [
                'total_requests' => OverTimeRequests::whereIn('user_id', $users->pluck('id'))
                    ->whereBetween('overtime_date', [$dateStart, $dateEnd])
                    ->when($status, function ($query) use ($status) {
                        return $query->where('status', $status);
                    })
                    ->count(),
                'approved_requests' => OverTimeRequests::whereIn('user_id', $users->pluck('id'))
                    ->where('status', 'approved')
                    ->whereBetween('overtime_date', [$dateStart, $dateEnd])
                    ->count(),
                'pending_requests' => OverTimeRequests::whereIn('user_id', $users->pluck('id'))
                    ->where('status', 'pending')
                    ->whereBetween('overtime_date', [$dateStart, $dateEnd])
                    ->count(),
                'total_hours' => OverTimeRequests::whereIn('user_id', $users->pluck('id'))
                    ->where('status', 'approved')
                    ->whereBetween('overtime_date', [$dateStart, $dateEnd])
                    ->selectRaw('COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(end_time, start_time))/3600), 0) as total_hours')
                    ->value('total_hours'),
                'team_employees' => User::select('users.id', 'users.name')
                    ->selectRaw('COUNT(DISTINCT over_time_requests.id) as total_requests')
                    ->selectRaw('COUNT(CASE WHEN over_time_requests.status = "approved" THEN 1 END) as approved_requests')
                    ->selectRaw('COUNT(CASE WHEN over_time_requests.status = "rejected" THEN 1 END) as rejected_requests')
                    ->selectRaw('COUNT(CASE WHEN over_time_requests.status = "pending" THEN 1 END) as pending_requests')
                    ->selectRaw('COALESCE(SUM(CASE WHEN over_time_requests.status = "approved" THEN TIME_TO_SEC(TIMEDIFF(over_time_requests.end_time, over_time_requests.start_time))/3600 ELSE 0 END), 0) as approved_hours')
                    ->selectRaw('COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(over_time_requests.end_time, over_time_requests.start_time))/3600), 0) as total_requested_hours')
                    ->leftJoin('over_time_requests', function ($join) use ($dateStart, $dateEnd) {
                        $join->on('users.id', '=', 'over_time_requests.user_id')
                            ->whereBetween('over_time_requests.overtime_date', [$dateStart, $dateEnd]);
                    })
                    ->whereIn('users.id', $users->pluck('id'))
                    ->groupBy('users.id', 'users.name')
                    ->get(),
                'most_active_employee' => User::whereIn('id', $users->pluck('id'))
                    ->withCount(['overtimeRequests' => function ($query) use ($dateStart, $dateEnd) {
                        $query->whereBetween('overtime_date', [$dateStart, $dateEnd]);
                    }])
                    ->orderByDesc('overtime_requests_count')
                    ->first()
            ];
        }

        if ($user->hasRole('hr')) {
            $hrStatistics = [
                'total_company_requests' => OverTimeRequests::whereBetween('overtime_date', [$dateStart, $dateEnd])
                    ->when($status, function ($query) use ($status) {
                        return $query->where('status', $status);
                    })
                    ->count(),
                'total_approved_hours' => OverTimeRequests::where('status', 'approved')
                    ->whereBetween('overtime_date', [$dateStart, $dateEnd])
                    ->selectRaw('COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(end_time, start_time))/3600), 0) as total_hours')
                    ->value('total_hours'),
                'pending_requests' => OverTimeRequests::where('status', 'pending')
                    ->whereBetween('overtime_date', [$dateStart, $dateEnd])
                    ->count(),
                'rejected_requests' => OverTimeRequests::where('status', 'rejected')
                    ->whereBetween('overtime_date', [$dateStart, $dateEnd])
                    ->count(),
                'approval_rate' => OverTimeRequests::whereBetween('overtime_date', [$dateStart, $dateEnd])
                    ->whereIn('status', ['approved', 'rejected'])
                    ->count() > 0
                    ? (OverTimeRequests::where('status', 'approved')
                        ->whereBetween('overtime_date', [$dateStart, $dateEnd])
                        ->count() /
                        OverTimeRequests::whereIn('status', ['approved', 'rejected'])
                        ->whereBetween('overtime_date', [$dateStart, $dateEnd])
                        ->count()) * 100
                    : 0,
                'average_hours_per_request' => OverTimeRequests::where('status', 'approved')
                    ->whereBetween('overtime_date', [$dateStart, $dateEnd])
                    ->count() > 0
                    ? OverTimeRequests::where('status', 'approved')
                    ->whereBetween('overtime_date', [$dateStart, $dateEnd])
                    ->selectRaw('COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(end_time, start_time))/3600), 0) / COUNT(*) as avg_hours')
                    ->value('avg_hours')
                    : 0,
                'monthly_trends' => DB::table('over_time_requests')
                    ->select(DB::raw('YEAR(overtime_date) as year, MONTH(overtime_date) as month'))
                    ->selectRaw('COUNT(*) as total_requests')
                    ->selectRaw('COUNT(CASE WHEN status = "approved" THEN 1 END) as approved_requests')
                    ->selectRaw('COUNT(CASE WHEN status = "rejected" THEN 1 END) as rejected_requests')
                    ->selectRaw('COUNT(CASE WHEN status = "pending" THEN 1 END) as pending_requests')
                    ->selectRaw('COALESCE(SUM(CASE WHEN status = "approved" THEN TIME_TO_SEC(TIMEDIFF(end_time, start_time))/3600 ELSE 0 END), 0) as total_hours')
                    ->whereBetween('overtime_date', [Carbon::parse($dateStart)->subMonths(6), $dateEnd])
                    ->groupBy('year', 'month')
                    ->orderBy('year', 'asc')
                    ->orderBy('month', 'asc')
                    ->get(),
                'day_of_week_stats' => DB::table('over_time_requests')
                    ->select(DB::raw('DAYOFWEEK(overtime_date) as day_of_week'))
                    ->selectRaw('COUNT(*) as total_requests')
                    ->selectRaw('COALESCE(SUM(CASE WHEN status = "approved" THEN TIME_TO_SEC(TIMEDIFF(end_time, start_time))/3600 ELSE 0 END), 0) as total_hours')
                    ->whereBetween('overtime_date', [$dateStart, $dateEnd])
                    ->groupBy('day_of_week')
                    ->get(),
                'top_employees' => User::select('users.id', 'users.name', 'users.department')
                    ->selectRaw('COUNT(DISTINCT over_time_requests.id) as total_requests')
                    ->selectRaw('COALESCE(SUM(CASE WHEN over_time_requests.status = "approved" THEN TIME_TO_SEC(TIMEDIFF(over_time_requests.end_time, over_time_requests.start_time))/3600 ELSE 0 END), 0) as approved_hours')
                    ->leftJoin('over_time_requests', function ($join) use ($dateStart, $dateEnd) {
                        $join->on('users.id', '=', 'over_time_requests.user_id')
                            ->whereBetween('over_time_requests.overtime_date', [$dateStart, $dateEnd]);
                    })
                    ->whereNotNull('department')
                    ->havingRaw('approved_hours > 0')
                    ->groupBy('users.id', 'users.name', 'users.department')
                    ->orderByDesc('approved_hours')
                    ->limit(10)
                    ->get(),
                'department_efficiency' => User::select('department')
                    ->selectRaw('COALESCE(SUM(CASE WHEN over_time_requests.status = "approved" THEN TIME_TO_SEC(TIMEDIFF(over_time_requests.end_time, over_time_requests.start_time))/3600 ELSE 0 END), 0) as approved_hours')
                    ->selectRaw('COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(over_time_requests.end_time, over_time_requests.start_time))/3600), 0) as total_requested_hours')
                    ->selectRaw('CASE WHEN COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(over_time_requests.end_time, over_time_requests.start_time))/3600), 0) > 0
                        THEN COALESCE(SUM(CASE WHEN over_time_requests.status = "approved" THEN TIME_TO_SEC(TIMEDIFF(over_time_requests.end_time, over_time_requests.start_time))/3600 ELSE 0 END), 0) /
                             COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(over_time_requests.end_time, over_time_requests.start_time))/3600), 0) * 100
                        ELSE 0 END as efficiency_rate')
                    ->leftJoin('over_time_requests', function ($join) use ($dateStart, $dateEnd) {
                        $join->on('users.id', '=', 'over_time_requests.user_id')
                            ->whereBetween('over_time_requests.overtime_date', [$dateStart, $dateEnd]);
                    })
                    ->whereNotNull('department')
                    ->havingRaw('total_requested_hours > 0')
                    ->groupBy('department')
                    ->get(),
                'comparative_analysis' => [
                    'current_period' => [
                        'total_requests' => OverTimeRequests::whereBetween('overtime_date', [$dateStart, $dateEnd])->count(),
                        'approved_hours' => OverTimeRequests::where('status', 'approved')
                            ->whereBetween('overtime_date', [$dateStart, $dateEnd])
                            ->selectRaw('COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(end_time, start_time))/3600), 0) as total_hours')
                            ->value('total_hours')
                    ],
                    'previous_period' => [
                        'total_requests' => OverTimeRequests::whereBetween(
                            'overtime_date',
                            [
                                Carbon::parse($dateStart)->subDays(Carbon::parse($dateEnd)->diffInDays(Carbon::parse($dateStart))),
                                Carbon::parse($dateStart)->subDay()
                            ]
                        )
                            ->count(),
                        'approved_hours' => OverTimeRequests::where('status', 'approved')
                            ->whereBetween(
                                'overtime_date',
                                [
                                    Carbon::parse($dateStart)->subDays(Carbon::parse($dateEnd)->diffInDays(Carbon::parse($dateStart))),
                                    Carbon::parse($dateStart)->subDay()
                                ]
                            )
                            ->selectRaw('COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(end_time, start_time))/3600), 0) as total_hours')
                            ->value('total_hours')
                    ]
                ],
                'departments_stats' => User::select('department')
                    ->selectRaw('COUNT(DISTINCT users.id) as total_employees')
                    ->selectRaw('COUNT(DISTINCT over_time_requests.id) as total_requests')
                    ->selectRaw('COALESCE(SUM(CASE WHEN over_time_requests.status = "approved" THEN TIME_TO_SEC(TIMEDIFF(over_time_requests.end_time, over_time_requests.start_time))/3600 ELSE 0 END), 0) as total_hours')
                    ->leftJoin('over_time_requests', function ($join) use ($dateStart, $dateEnd, $status) {
                        $join->on('users.id', '=', 'over_time_requests.user_id')
                            ->whereBetween('over_time_requests.overtime_date', [$dateStart, $dateEnd])
                            ->when($status, function ($query) use ($status) {
                                return $query->where('over_time_requests.status', $status);
                            });
                    })
                    ->whereNotNull('department')
                    ->groupBy('department')
                    ->get(),
                'department_employees' => User::select('users.id', 'users.name', 'users.department')
                    ->selectRaw('COUNT(DISTINCT over_time_requests.id) as total_requests')
                    ->selectRaw('COUNT(CASE WHEN over_time_requests.status = "approved" THEN 1 END) as approved_requests')
                    ->selectRaw('COUNT(CASE WHEN over_time_requests.status = "rejected" THEN 1 END) as rejected_requests')
                    ->selectRaw('COUNT(CASE WHEN over_time_requests.status = "pending" THEN 1 END) as pending_requests')
                    ->selectRaw('COALESCE(SUM(CASE WHEN over_time_requests.status = "approved" THEN TIME_TO_SEC(TIMEDIFF(over_time_requests.end_time, over_time_requests.start_time))/3600 ELSE 0 END), 0) as approved_hours')
                    ->selectRaw('COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(over_time_requests.end_time, over_time_requests.start_time))/3600), 0) as total_requested_hours')
                    ->leftJoin('over_time_requests', function ($join) use ($dateStart, $dateEnd) {
                        $join->on('users.id', '=', 'over_time_requests.user_id')
                            ->whereBetween('over_time_requests.overtime_date', [$dateStart, $dateEnd]);
                    })
                    ->whereNotNull('department')
                    ->groupBy('users.id', 'users.name', 'users.department')
                    ->get()
                    ->groupBy('department')
            ];
        } else {
            $hrStatistics = [];
        }

        return view('overtime-requests.index', compact(
            'myRequests',
            'teamRequests',
            'noTeamRequests',
            'hrRequests',
            'users',
            'canCreateOvertime',
            'canUpdateOvertime',
            'canDeleteOvertime',
            'canRespondAsManager',
            'canRespondAsHR',
            'myOvertimeHours',
            'overtimeHoursCount',
            'noTeamOvertimeHoursCount',
            'pendingCount',
            'filters',
            'currentMonthStart',
            'currentMonthEnd',
            'dateStart',
            'dateEnd',
            'personalStatistics',
            'teamStatistics',
            'hrStatistics'
        ));
    }

    /**
     * Display a specific overtime request.
     */
    public function show(OverTimeRequests $overtimeRequest)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // تسجيل النشاط - عرض تفاصيل طلب العمل الإضافي
        if (\Illuminate\Support\Facades\Auth::check()) {
            activity()
                ->performedOn($overtimeRequest)
                ->causedBy(\Illuminate\Support\Facades\Auth::user())
                ->withProperties([
                    'overtime_request_id' => $overtimeRequest->id,
                    'request_user_id' => $overtimeRequest->user_id,
                    'request_user_name' => $overtimeRequest->user->name ?? null,
                    'overtime_date' => $overtimeRequest->overtime_date,
                    'start_time' => $overtimeRequest->start_time,
                    'end_time' => $overtimeRequest->end_time,
                    'status' => $overtimeRequest->status,
                    'action_type' => 'view',
                    'viewed_at' => now()->toDateTimeString(),
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip()
                ])
                ->log('شاهد تفاصيل طلب العمل الإضافي');
        }

        // التحقق من الصلاحيات
        $canView = false;

        // صاحب الطلب يمكنه رؤية طلبه
        if ($user->id === $overtimeRequest->user_id) {
            $canView = true;
        }
        // HR يمكنهم رؤية جميع الطلبات
        elseif ($user->hasRole('hr')) {
            $canView = true;
        }
        // مالكي الفرق يمكنهم رؤية طلبات أعضاء فريقهم
        elseif ($user->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager'])) {
            // التحقق من أن الموظف في فريق المستخدم الحالي
            if ($user->currentTeam && $user->currentTeam->users->contains('id', $overtimeRequest->user_id)) {
                $canView = true;
            }
        }

        if (!$canView) {
            abort(403, 'غير مصرح لك بعرض هذا الطلب');
        }

        // تحميل العلاقات المطلوبة
        $overtimeRequest->load('user');

        // التحقق من الصلاحيات للإجراءات

        // صاحب الطلب فقط يستطيع تعديل الطلب نفسه وفقط إذا لم يرد المدير أو HR
        $canUpdateRequest = $user->hasPermissionTo('update_overtime') &&
                           ($user->id === $overtimeRequest->user_id) &&
                           ($overtimeRequest->manager_status === 'pending' && $overtimeRequest->hr_status === 'pending');

                // صاحب الطلب فقط يستطيع حذف الطلب وفقط إذا لم يرد المدير أو HR
        $canDelete = $user->hasPermissionTo('delete_overtime') &&
                    ($user->id === $overtimeRequest->user_id) &&
                    ($overtimeRequest->manager_status === 'pending' && $overtimeRequest->hr_status === 'pending');

        // المدير يستطيع الرد الأولي أو تعديل رده
        $canRespondAsManager = $user->hasPermissionTo('manager_respond_overtime_request') &&
                              $user->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager']) &&
                              $user->currentTeam && $user->currentTeam->users->contains('id', $overtimeRequest->user_id);

        // المدير يستطيع تعديل رده إذا كان قد رد من قبل
        $canModifyManagerResponse = $canRespondAsManager && $overtimeRequest->manager_status !== 'pending';

        // HR يستطيع الرد الأولي أو تعديل رده
        $canRespondAsHR = $user->hasPermissionTo('hr_respond_overtime_request') && $user->hasRole('hr');

        // HR يستطيع تعديل رده إذا كان قد رد من قبل
        $canModifyHRResponse = $canRespondAsHR && $overtimeRequest->hr_status !== 'pending';

        // الحصول على قائمة المستخدمين للـ modals
        $users = collect();
        if ($user->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager'])) {
            if ($user->currentTeam) {
                $users = $user->currentTeam->users;
            }
        } elseif ($user->hasRole('hr')) {
            $users = User::whereDoesntHave('roles', function ($q) {
                $q->whereIn('name', ['company_manager', 'hr']);
            });

            if ($user->currentTeam) {
                $teamMembersIds = $user->currentTeam->users->pluck('id')->toArray();
                $users = $users->orWhereIn('id', $teamMembersIds);
            }

            $users = $users->get();
        } else {
            $users = collect([$user]);
        }

        // Get HR statistics for modals (needed for department employees data)
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if ($user->hasRole('hr')) {
            $hrStatistics = [
                'department_employees' => User::select('users.id', 'users.name', 'users.department')
                    ->selectRaw('COUNT(DISTINCT over_time_requests.id) as total_requests')
                    ->selectRaw('COUNT(CASE WHEN over_time_requests.status = "approved" THEN 1 END) as approved_requests')
                    ->selectRaw('COUNT(CASE WHEN over_time_requests.status = "rejected" THEN 1 END) as rejected_requests')
                    ->selectRaw('COUNT(CASE WHEN over_time_requests.status = "pending" THEN 1 END) as pending_requests')
                    ->selectRaw('COALESCE(SUM(CASE WHEN over_time_requests.status = "approved" THEN TIME_TO_SEC(TIMEDIFF(over_time_requests.end_time, over_time_requests.start_time))/3600 ELSE 0 END), 0) as approved_hours')
                    ->selectRaw('COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(over_time_requests.end_time, over_time_requests.start_time))/3600), 0) as total_requested_hours')
                    ->leftJoin('over_time_requests', function ($join) {
                        $join->on('users.id', '=', 'over_time_requests.user_id');
                    })
                    ->whereNotNull('department')
                    ->groupBy('users.id', 'users.name', 'users.department')
                    ->get()
                    ->groupBy('department')
            ];
        } else {
            $hrStatistics = ['department_employees' => collect()];
        }

        return view('overtime-requests.show', compact(
            'overtimeRequest',
            'canUpdateRequest',
            'canDelete',
            'canRespondAsManager',
            'canModifyManagerResponse',
            'canRespondAsHR',
            'canModifyHRResponse',
            'users',
            'hrStatistics'
        ));
    }

    public function store(Request $request)
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        if (!$authUser->hasPermissionTo('create_overtime')) {
            abort(403, 'ليس لديك صلاحية إنشاء طلب عمل إضافي');
        }

        $request->validate([
            'overtime_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'reason' => 'required|string|max:255',
            'user_id' => 'sometimes|exists:users,id'
        ]);

        try {
            $this->overTimeRequestService->createRequest($request->all());
            return redirect()->route('overtime-requests.index')->with('success', 'Overtime request created successfully.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        if (!$authUser->hasPermissionTo('update_overtime')) {
            abort(403, 'ليس لديك صلاحية تعديل طلب العمل الإضافي');
        }

        $request->validate([
            'overtime_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'reason' => 'required|string|max:255'
        ]);

        try {
            $overtimeRequest = OverTimeRequests::findOrFail($id);
            $this->overTimeRequestService->update($overtimeRequest, $request->all());
            return redirect()->route('overtime-requests.index')->with('success', 'Overtime request updated successfully.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function destroy($id)
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        if (!$authUser->hasPermissionTo('delete_overtime')) {
            abort(403, 'ليس لديك صلاحية حذف طلب العمل الإضافي');
        }

        try {
            $overtimeRequest = OverTimeRequests::findOrFail($id);
            $this->overTimeRequestService->deleteRequest($overtimeRequest);

            if (request()->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Overtime request deleted successfully.']);
            }
            return redirect()->route('overtime-requests.index')->with('success', 'Overtime request deleted successfully.');
        } catch (\Exception $e) {
            if (request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }
    }

    public function updateManagerStatus(Request $request, $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $overtimeRequest = OverTimeRequests::findOrFail($id);

        if ($user->id === $overtimeRequest->user_id) {
            return redirect()->back()->with('error', 'لا يمكنك الرد على طلب العمل الإضافي الخاص بك');
        }

        if (!$user->hasPermissionTo('manager_respond_overtime_request')) {
            return redirect()->back()->with('error', 'ليس لديك صلاحية الرد على طلبات العمل الإضافي كمدير');
        }

        if (
            !$user->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager']) &&
            !($user->hasRole('hr') && $user->hasPermissionTo('manager_respond_overtime_request'))
        ) {
            return redirect()->back()->with('error', 'ليس لديك صلاحية الرد على طلبات العمل الإضافي كمدير');
        }

        $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected'
        ]);

        try {
            $this->overTimeRequestService->updateManagerStatus($overtimeRequest, $request->all());
            return back()->with('success', 'تم الرد على الطلب بنجاح');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function updateHrStatus(Request $request, $id)
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        if (!$authUser->hasPermissionTo('hr_respond_overtime_request')) {
            abort(403, 'ليس لديك صلاحية الرد على طلبات العمل الإضافي كموارد بشرية');
        }

        $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected'
        ]);

        try {
            $overtimeRequest = OverTimeRequests::findOrFail($id);
            $this->overTimeRequestService->updateHrStatus($overtimeRequest, $request->all());
            return back()->with('success', 'Response submitted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function resetManagerStatus($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $overtimeRequest = OverTimeRequests::findOrFail($id);

        if ($user->id === $overtimeRequest->user_id) {
            return redirect()->back()->with('error', 'لا يمكنك إعادة تعيين الرد على طلب العمل الإضافي الخاص بك');
        }

        if (!$user->hasPermissionTo('manager_respond_overtime_request')) {
            return redirect()->back()->with('error', 'ليس لديك صلاحية إعادة تعيين الرد على طلبات العمل الإضافي كمدير');
        }

        if (
            !$user->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager']) &&
            !($user->hasRole('hr') && $user->hasPermissionTo('manager_respond_overtime_request'))
        ) {
            return redirect()->back()->with('error', 'ليس لديك صلاحية إعادة تعيين الرد على طلبات العمل الإضافي كمدير');
        }

        try {
            $overtimeRequest->manager_status = 'pending';
            $overtimeRequest->manager_rejection_reason = null;
            $overtimeRequest->updateFinalStatus();
            $overtimeRequest->save();

            $this->notificationService->notifyStatusReset($overtimeRequest, 'manager');

            return back()->with('success', 'تم إعادة تعيين الرد بنجاح');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function resetHrStatus($id)
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        if (!$authUser->hasPermissionTo('hr_respond_overtime_request')) {
            abort(403, 'ليس لديك صلاحية إعادة تعيين الرد على طلبات العمل الإضافي كموارد بشرية');
        }

        try {
            $overtimeRequest = OverTimeRequests::findOrFail($id);
            $overtimeRequest->hr_status = 'pending';
            $overtimeRequest->hr_rejection_reason = null;
            $overtimeRequest->updateFinalStatus();
            $overtimeRequest->save();

            $this->notificationService->notifyStatusReset($overtimeRequest, 'hr');

            return back()->with('success', 'Status reset successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function modifyManagerStatus(Request $request, $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $overtimeRequest = OverTimeRequests::findOrFail($id);

        if ($user->id === $overtimeRequest->user_id) {
            return redirect()->back()->with('error', 'لا يمكنك تعديل الرد على طلب العمل الإضافي الخاص بك');
        }

        if (!$user->hasPermissionTo('manager_respond_overtime_request')) {
            return redirect()->back()->with('error', 'ليس لديك صلاحية تعديل الرد على طلبات العمل الإضافي كمدير');
        }

        if (
            !$user->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager']) &&
            !($user->hasRole('hr') && $user->hasPermissionTo('manager_respond_overtime_request'))
        ) {
            return redirect()->back()->with('error', 'ليس لديك صلاحية تعديل الرد على طلبات العمل الإضافي كمدير');
        }

        $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected'
        ]);

        try {
            $overtimeRequest->manager_status = $request->status;
            $overtimeRequest->manager_rejection_reason = $request->status === 'rejected' ? $request->rejection_reason : null;
            $overtimeRequest->updateFinalStatus();
            $overtimeRequest->save();

            $this->notificationService->notifyResponseModified($overtimeRequest, 'manager');

            return back()->with('success', 'تم تعديل الرد بنجاح');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function modifyHrStatus(Request $request, $id)
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        if (!$authUser->hasPermissionTo('hr_respond_overtime_request')) {
            abort(403, 'ليس لديك صلاحية تعديل الرد على طلبات العمل الإضافي كموارد بشرية');
        }

        $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected'
        ]);

        try {
            $overtimeRequest = OverTimeRequests::findOrFail($id);

            /** @var \App\Models\User $authUserCheck */
            $authUserCheck = Auth::user();
            if (!$authUserCheck->hasPermissionTo('hr_respond_overtime_request')) {
                return back()->with('error', 'Unauthorized action.');
            }

            $overtimeRequest->hr_status = $request->status;
            $overtimeRequest->hr_rejection_reason = $request->status === 'rejected' ? $request->rejection_reason : null;
            $overtimeRequest->updateFinalStatus();
            $overtimeRequest->save();

            $this->notificationService->notifyResponseModified($overtimeRequest, 'hr');

            return back()->with('success', 'HR response updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function updateStatus(Request $request, OverTimeRequests $overtimeRequest)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->id === $overtimeRequest->user_id) {
            return redirect()->back()->with('error', 'لا يمكنك الرد على طلب العمل الإضافي الخاص بك');
        }

        if ($request->input('response_type') === 'manager' && !$user->hasPermissionTo('manager_respond_overtime_request')) {
            return redirect()->back()->with('error', 'ليس لديك صلاحية الرد على طلبات العمل الإضافي كمدير');
        }

        if ($request->input('response_type') === 'hr' && !$user->hasPermissionTo('hr_respond_overtime_request')) {
            return redirect()->back()->with('error', 'ليس لديك صلاحية الرد على طلبات العمل الإضافي كموارد بشرية');
        }

        if (
            $request->input('response_type') === 'manager' &&
            !$user->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager']) &&
            !($user->hasRole('hr') && $user->hasPermissionTo('manager_respond_overtime_request'))
        ) {
            return redirect()->back()->with('error', 'ليس لديك صلاحية الرد على طلبات العمل الإضافي كمدير');
        }

        if ($request->input('response_type') === 'hr' && !$user->hasRole('hr')) {
            return redirect()->back()->with('error', 'ليس لديك صلاحية الرد على طلبات العمل الإضافي كموارد بشرية');
        }

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected',
            'response_type' => 'required|in:manager,hr'
        ]);

        try {
            $this->overTimeRequestService->updateStatus($overtimeRequest, $validated);
            return back()->with('success', 'تم تحديث حالة الطلب بنجاح');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * عرض سجل تغييرات طلب الساعات الإضافية
     */
    public function showAudits($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // التحقق من صلاحيات المستخدم
        if (!$user->hasRole('hr')) {
            abort(403, 'غير مصرح لك بعرض سجل التغييرات');
        }

        $overtimeRequest = OverTimeRequests::findOrFail($id);

        return redirect()->route('audit-log.index', [
            'request_type' => OverTimeRequests::class,
            'model_id' => $id
        ]);
    }
}
