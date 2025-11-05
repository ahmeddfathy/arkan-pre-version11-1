<?php

namespace App\Services\ProjectDashboard;

use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Services\ProjectDashboard\DateFilterService;

class ProjectStatsService
{
    protected $dateFilterService;

    public function __construct(DateFilterService $dateFilterService)
    {
        $this->dateFilterService = $dateFilterService;
    }

    public function getGeneralProjectStats($isAdmin, $userId = null, $dateFilters = null)
    {
        $projectsQuery = Project::query();

        if (!$isAdmin && $userId) {
            $userProjectIds = DB::table('project_service_user')
                ->where('user_id', $userId)
                ->pluck('project_id')
                ->toArray();

            $projectsQuery->whereIn('id', $userProjectIds);
        }

        // تطبيق فلترة التاريخ
        if ($dateFilters && $dateFilters['has_filter']) {
            $this->dateFilterService->applyCreatedAtFilter(
                $projectsQuery,
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }

        $baseQuery = clone $projectsQuery;

        return [
            'totalProjects' => $projectsQuery->count(),
            'newProjects' => (clone $baseQuery)->where('status', 'جديد')->count(),
            'inProgressProjects' => (clone $baseQuery)->where('status', 'جاري التنفيذ')->count(),
            'completedProjects' => (clone $baseQuery)->where('status', 'مكتمل')->count(),
            'cancelledProjects' => (clone $baseQuery)->where('status', 'ملغي')->count(),
            'pausedProjects' => (clone $baseQuery)->where('status', 'موقوف')->count(),

            'projectsWithInternalDraft' => (clone $baseQuery)
                ->where('delivery_type', 'مسودة')
                ->whereNotNull('actual_delivery_date')
                ->count(),
            'projectsWithInternalFinal' => (clone $baseQuery)
                ->where('delivery_type', 'كامل')
                ->whereNotNull('actual_delivery_date')
                ->count(),

            'projectsWithDraft' => (clone $baseQuery)->whereHas('lastDraftDelivery')->count(),
            'projectsWithFinal' => (clone $baseQuery)->whereHas('lastFinalDelivery')->count(),
            'projectsWithoutDelivery' => (clone $baseQuery)->doesntHave('deliveries')->count(),
        ];
    }


    public function getLatestProjects($isAdmin, $userId = null, $limit = 5, $dateFilters = null)
    {
        $projectsQuery = Project::query();

        if (!$isAdmin && $userId) {
            $userProjectIds = DB::table('project_service_user')
                ->where('user_id', $userId)
                ->pluck('project_id')
                ->toArray();

            $projectsQuery->whereIn('id', $userProjectIds);
        }

        // تطبيق فلترة التاريخ
        if ($dateFilters && $dateFilters['has_filter']) {
            $this->dateFilterService->applyCreatedAtFilter(
                $projectsQuery,
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }

        return $projectsQuery->with(['client', 'services'])
            ->latest()
            ->take($limit)
            ->get();
    }


    public function getActiveProjects($isAdmin, $userId = null, $dateFilters = null)
    {
        $projectsQuery = Project::query();

        if (!$isAdmin && $userId) {
            $userProjectIds = DB::table('project_service_user')
                ->where('user_id', $userId)
                ->pluck('project_id')
                ->toArray();

            $projectsQuery->whereIn('id', $userProjectIds);
        }

        // تطبيق فلترة التاريخ
        if ($dateFilters && $dateFilters['has_filter']) {
            $this->dateFilterService->applyCreatedAtFilter(
                $projectsQuery,
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }

        return $projectsQuery->with(['client', 'services', 'tasks'])
            ->where('status', 'جاري التنفيذ')
            ->get();
    }


    public function getOverdueProjects($isAdmin, $userId = null, $dateFilters = null)
    {
        $projectsQuery = Project::query();

        if (!$isAdmin && $userId) {
            $userProjectIds = DB::table('project_service_user')
                ->where('user_id', $userId)
                ->pluck('project_id')
                ->toArray();

            $projectsQuery->whereIn('id', $userProjectIds);
        }

        // تطبيق فلترة التاريخ
        if ($dateFilters && $dateFilters['has_filter']) {
            $this->dateFilterService->applyCreatedAtFilter(
                $projectsQuery,
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }

        return $projectsQuery->with(['client'])
            ->where(function ($query) {
                $query->where(function ($subQuery) {
                    // إذا كان هناك تاريخ متفق عليه مع العميل، استخدمه
                    $subQuery->whereNotNull('client_agreed_delivery_date')
                        ->whereDate('client_agreed_delivery_date', '<', Carbon::today());
                })->orWhere(function ($subQuery) {
                    // إذا لم يكن هناك تاريخ متفق مع العميل، استخدم تاريخ الفريق
                    $subQuery->whereNull('client_agreed_delivery_date')
                        ->whereNotNull('team_delivery_date')
                        ->whereDate('team_delivery_date', '<', Carbon::today());
                });
            })
            ->whereIn('status', ['جديد', 'جاري التنفيذ'])
            ->get();
    }

    /**
     * الحصول على مشاريع كانبان مجمعة حسب الحالة
     */
    public function getKanbanProjects($isAdmin, $userId = null, $dateFilters = null)
    {
        $projectsQuery = Project::query();

        if (!$isAdmin && $userId) {
            $userProjectIds = DB::table('project_service_user')
                ->where('user_id', $userId)
                ->pluck('project_id')
                ->toArray();

            $projectsQuery->whereIn('id', $userProjectIds);
        }

        // تطبيق فلترة التاريخ
        if ($dateFilters && $dateFilters['has_filter']) {
            $this->dateFilterService->applyCreatedAtFilter(
                $projectsQuery,
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }

        return $projectsQuery->with(['client', 'services', 'tasks'])
            ->get()
            ->groupBy('status');
    }

    /**
     * الحصول على إحصائيات الخدمات
     */
    public function getServiceStats($isAdmin, $userId = null, $dateFilters = null)
    {
        $serviceStatsQuery = DB::table('company_services')
            ->leftJoin('project_service', 'company_services.id', '=', 'project_service.service_id')
            ->leftJoin('projects', 'project_service.project_id', '=', 'projects.id');

        if (!$isAdmin && $userId) {
            $serviceStatsQuery->join('project_service_user', function ($join) use ($userId) {
                $join->on('project_service.project_id', '=', 'project_service_user.project_id')
                    ->where('project_service_user.user_id', '=', $userId);
            });
        }

        // تطبيق فلترة التاريخ
        if ($dateFilters && $dateFilters['has_filter']) {
            if ($dateFilters['from_date']) {
                $serviceStatsQuery->where('projects.created_at', '>=', $dateFilters['from_date']);
            }
            if ($dateFilters['to_date']) {
                $serviceStatsQuery->where('projects.created_at', '<=', $dateFilters['to_date']);
            }
        }

        return $serviceStatsQuery
            ->select(
                'company_services.name',
                'company_services.department',
                DB::raw('COUNT(DISTINCT projects.id) as project_count')
            )
            ->groupBy('company_services.name', 'company_services.department')
            ->orderBy('project_count', 'desc')
            ->get();
    }

    /**
     * الحصول على أفضل الموظفين
     */
    public function getTopEmployees($isAdmin, $userId = null, $limit = 10, $dateFilters = null)
    {
        $topEmployeesQuery = DB::table('project_service_user')
            ->join('users', 'project_service_user.user_id', '=', 'users.id')
            ->join('projects', 'project_service_user.project_id', '=', 'projects.id');

        if (!$isAdmin && $userId) {
            $userProjects = DB::table('project_service_user')
                ->where('user_id', $userId)
                ->pluck('project_id')
                ->toArray();

            $topEmployeesQuery->whereIn('project_service_user.project_id', $userProjects);
        }

        // تطبيق فلترة التاريخ
        if ($dateFilters && $dateFilters['has_filter']) {
            if ($dateFilters['from_date']) {
                $topEmployeesQuery->where('projects.created_at', '>=', $dateFilters['from_date']);
            }
            if ($dateFilters['to_date']) {
                $topEmployeesQuery->where('projects.created_at', '<=', $dateFilters['to_date']);
            }
        }

        return $topEmployeesQuery
            ->select('users.name', 'users.id', DB::raw('COUNT(DISTINCT project_id) as project_count'))
            ->groupBy('users.id', 'users.name')
            ->orderBy('project_count', 'desc')
            ->take($limit)
            ->get();
    }

    /**
     * الحصول على إحصائيات الأقسام مع تفاصيلها
     */
    public function getDepartmentsData()
    {
        return User::select('department', DB::raw('COUNT(*) as employees_count'))
            ->whereNotNull('department')
            ->where('department', '!=', '')
            ->groupBy('department')
            ->orderBy('employees_count', 'desc')
            ->get();
    }

    /**
     * الحصول على مشاريع موظف معين
     */
    public function getEmployeeProjects($userId, $dateFilters = null)
    {
        $query = DB::table('project_service_user')
            ->join('projects', 'project_service_user.project_id', '=', 'projects.id')
            ->leftJoin('clients', 'projects.client_id', '=', 'clients.id')
            ->where('project_service_user.user_id', $userId);

        // تطبيق فلاتر التاريخ
        if ($dateFilters && isset($dateFilters['has_filter']) && $dateFilters['has_filter']) {
            $this->dateFilterService->applyDateFilter(
                $query,
                'projects.created_at',
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }

        return $query->select('projects.*', 'clients.name as client_name')
            ->distinct('projects.id')
            ->get();
    }

    /**
     * الحصول على المشاريع المتأخرة لمجموعة من المشاريع
     */
    public function filterOverdueProjects($projects)
    {
        return $projects->filter(function ($project) {
            $deliveryDate = $project->client_agreed_delivery_date ?? $project->team_delivery_date;
            return $deliveryDate &&
                Carbon::parse($deliveryDate)->isPast() &&
                in_array($project->status, ['جديد', 'جاري التنفيذ']);
        });
    }

    /**
     * حساب إحصائيات المشاريع بناءً على المشاريع المرسلة
     */
    public function calculateProjectStats($projects)
    {
        $stats = [
            'total' => 0,
            'new' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'cancelled' => 0,
        ];

        foreach ($projects as $project) {
            $stats['total']++;

            switch ($project->status) {
                case 'جديد':
                    $stats['new']++;
                    break;
                case 'جاري التنفيذ':
                    $stats['in_progress']++;
                    break;
                case 'مكتمل':
                    $stats['completed']++;
                    break;
                case 'ملغي':
                    $stats['cancelled']++;
                    break;
            }
        }

        return $stats;
    }

    /**
     * حساب المشاريع المكتملة بتأخير (completed_date بعد الـ delivery_date)
     */
    public function getCompletedLateProjects($projects)
    {
        return $projects->filter(function ($project) {
            // التحقق من أن المشروع مكتمل
            if ($project->status !== 'مكتمل') {
                return false;
            }

            // الحصول على تاريخ التسليم المتوقع
            $deliveryDate = $project->client_agreed_delivery_date ?? $project->team_delivery_date;

            // الحصول على تاريخ الإكمال الفعلي
            $actualDeliveryDate = $project->actual_delivery_date;

            // التحقق من وجود التواريخ ومقارنتها
            if ($deliveryDate && $actualDeliveryDate) {
                return Carbon::parse($actualDeliveryDate)->gt(Carbon::parse($deliveryDate));
            }

            return false;
        });
    }

    /**
     * حساب إحصائيات التأخير للمشاريع بناءً على deadline الموظفين في project_service_user
     */
    public function calculateOverdueProjectStats($projects, $userIds = null)
    {
        $now = Carbon::now();
        $overdueActive = 0; // متأخرة ولم تكتمل
        $completedLate = 0; // مكتملة بتأخير
        $overdueProjects = []; // تفاصيل المشاريع المتأخرة
        $completedLateProjects = []; // تفاصيل المشاريع المكتملة بتأخير

        if (!$userIds || !is_array($userIds)) {
            return [
                'overdue_active' => 0,
                'completed_late' => 0,
                'total_overdue' => 0,
                'overdue_projects' => [],
                'completed_late_projects' => [],
            ];
        }

        foreach ($projects as $project) {
            // الحصول على المشاركين من القسم في هذا المشروع باستخدام Eloquent
            $participants = \App\Models\ProjectServiceUser::with('user')
                ->where('project_id', $project->id)
                ->whereIn('user_id', $userIds)
                ->whereNotNull('deadline')
                ->get();

            if ($participants->isEmpty()) {
                continue; // لا يوجد أحد من القسم في هذا المشروع
            }

            // فحص حالة كل مشارك من القسم
            $overdueParticipants = [];
            $completedLateParticipants = [];

            foreach ($participants as $participant) {
                $deadline = Carbon::parse($participant->deadline);

                // تحديد حالة الإكمال بناءً على is_acknowledged
                // (نستخدم is_acknowledged لأن أعمدة الموافقات قد لا تكون موجودة في قاعدة البيانات)
                $isCompleted = $participant->is_acknowledged;
                $completedAt = $participant->acknowledged_at ? Carbon::parse($participant->acknowledged_at) : null;

                // المشاركين المتأخرين (لم يكملوا بعد وعدى الـ deadline)
                if (!$isCompleted && $deadline->isPast()) {
                    $overdueParticipants[] = [
                        'id' => $participant->user->id,
                        'name' => $participant->user->name,
                        'project_share' => $participant->project_share ?? 0,
                        'deadline' => $participant->deadline,
                        'days_overdue' => $deadline->diffInDays($now),
                    ];
                }

                // المشاركين اللي اكملوا بتأخير
                if ($isCompleted && $completedAt && $completedAt->gt($deadline)) {
                    $completedLateParticipants[] = [
                        'id' => $participant->user->id,
                        'name' => $participant->user->name,
                        'project_share' => $participant->project_share ?? 0,
                        'deadline' => $participant->deadline,
                        'completed_at' => $completedAt->format('Y-m-d H:i:s'),
                        'days_late' => $deadline->diffInDays($completedAt),
                    ];
                }
            }

            // إضافة المشروع للقائمة إذا كان فيه مشاركين متأخرين
            if (count($overdueParticipants) > 0) {
                $overdueActive += count($overdueParticipants);
                $overdueProjects[] = [
                    'project' => $project,
                    'participants' => $overdueParticipants,
                ];
            }

            // إضافة المشروع للقائمة إذا كان فيه مشاركين اكملوا بتأخير
            if (count($completedLateParticipants) > 0) {
                $completedLate += count($completedLateParticipants);
                $completedLateProjects[] = [
                    'project' => $project,
                    'participants' => $completedLateParticipants,
                ];
            }
        }

        return [
            'overdue_active' => $overdueActive, // عدد المشاركين المتأخرين
            'completed_late' => $completedLate, // عدد المشاركين اللي اكملوا بتأخير
            'total_overdue' => $overdueActive + $completedLate,
            'overdue_projects' => $overdueProjects, // تفاصيل المشاريع والمشاركين المتأخرين
            'completed_late_projects' => $completedLateProjects, // تفاصيل المشاريع والمشاركين اللي اكملوا بتأخير
        ];
    }

    /**
     * حساب إحصائيات المشاريع المتأخرة للداش بورد الرئيسي
     * يتم الحساب بناءً على تاريخين: client_agreed_delivery_date و team_delivery_date
     */
    public function calculateDashboardOverdueStats($isAdmin = true, $userId = null, $dateFilters = null)
    {
        $now = Carbon::now();

        // Query المشاريع
        $projectsQuery = Project::query();

        if (!$isAdmin && $userId) {
            $userProjectIds = DB::table('project_service_user')
                ->where('user_id', $userId)
                ->pluck('project_id')
                ->toArray();
            $projectsQuery->whereIn('id', $userProjectIds);
        }

        // تطبيق فلاتر التاريخ
        if ($dateFilters && $dateFilters['has_filter']) {
            $this->dateFilterService->applyCreatedAtFilter(
                $projectsQuery,
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }

        $projects = $projectsQuery->get();

        // متغيرات الإحصائيات
        $overdueByClientDate = 0;
        $overdueByTeamDate = 0;
        $completedLateByClientDate = 0;
        $completedLateByTeamDate = 0;

        $overdueProjectsByClientDate = [];
        $overdueProjectsByTeamDate = [];
        $completedLateProjectsByClientDate = [];
        $completedLateProjectsByTeamDate = [];

        foreach ($projects as $project) {
            $status = $project->status;
            $clientDate = $project->client_agreed_delivery_date;
            $teamDate = $project->team_delivery_date;
            $completedAt = $project->updated_at; // أو يمكن استخدام حقل آخر

            // === التحقق من التاريخ المتفق مع العميل ===
            if ($clientDate) {
                $clientDeadline = Carbon::parse($clientDate);

                // مشاريع متأخرة ولم تكتمل (بناءً على تاريخ العميل)
                if (in_array($status, ['جديد', 'جاري التنفيذ']) && $now->isAfter($clientDeadline)) {
                    $daysOverdue = $now->diffInDays($clientDeadline);
                    $overdueByClientDate++;
                    $overdueProjectsByClientDate[] = [
                        'project' => $project,
                        'days_overdue' => $daysOverdue,
                        'deadline_date' => $clientDeadline->format('Y-m-d'),
                        'deadline_type' => 'client'
                    ];
                }

                // مشاريع مكتملة بتأخير (بناءً على تاريخ العميل)
                if ($status == 'مكتمل' && $completedAt && $completedAt->isAfter($clientDeadline)) {
                    $daysLate = $completedAt->diffInDays($clientDeadline);
                    $completedLateByClientDate++;
                    $completedLateProjectsByClientDate[] = [
                        'project' => $project,
                        'days_late' => $daysLate,
                        'deadline_date' => $clientDeadline->format('Y-m-d'),
                        'completed_date' => $completedAt->format('Y-m-d'),
                        'deadline_type' => 'client'
                    ];
                }
            }

            // === التحقق من التاريخ المحدد من الفريق ===
            if ($teamDate) {
                $teamDeadline = Carbon::parse($teamDate);

                // مشاريع متأخرة ولم تكتمل (بناءً على تاريخ الفريق)
                if (in_array($status, ['جديد', 'جاري التنفيذ']) && $now->isAfter($teamDeadline)) {
                    $daysOverdue = $now->diffInDays($teamDeadline);
                    $overdueByTeamDate++;
                    $overdueProjectsByTeamDate[] = [
                        'project' => $project,
                        'days_overdue' => $daysOverdue,
                        'deadline_date' => $teamDeadline->format('Y-m-d'),
                        'deadline_type' => 'team'
                    ];
                }

                // مشاريع مكتملة بتأخير (بناءً على تاريخ الفريق)
                if ($status == 'مكتمل' && $completedAt && $completedAt->isAfter($teamDeadline)) {
                    $daysLate = $completedAt->diffInDays($teamDeadline);
                    $completedLateByTeamDate++;
                    $completedLateProjectsByTeamDate[] = [
                        'project' => $project,
                        'days_late' => $daysLate,
                        'deadline_date' => $teamDeadline->format('Y-m-d'),
                        'completed_date' => $completedAt->format('Y-m-d'),
                        'deadline_type' => 'team'
                    ];
                }
            }
        }

        return [
            // إحصائيات بناءً على تاريخ العميل
            'client_date' => [
                'overdue_active' => $overdueByClientDate,
                'completed_late' => $completedLateByClientDate,
                'total_overdue' => $overdueByClientDate + $completedLateByClientDate,
                'overdue_projects' => $overdueProjectsByClientDate,
                'completed_late_projects' => $completedLateProjectsByClientDate,
            ],
            // إحصائيات بناءً على تاريخ الفريق
            'team_date' => [
                'overdue_active' => $overdueByTeamDate,
                'completed_late' => $completedLateByTeamDate,
                'total_overdue' => $overdueByTeamDate + $completedLateByTeamDate,
                'overdue_projects' => $overdueProjectsByTeamDate,
                'completed_late_projects' => $completedLateProjectsByTeamDate,
            ],
            // الإحصائيات المجمعة
            'combined' => [
                'total_overdue' => $overdueByClientDate + $overdueByTeamDate + $completedLateByClientDate + $completedLateByTeamDate,
                'has_overdue' => ($overdueByClientDate + $overdueByTeamDate + $completedLateByClientDate + $completedLateByTeamDate) > 0,
            ]
        ];
    }
}
