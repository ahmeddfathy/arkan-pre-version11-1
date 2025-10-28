<?php

namespace App\Services\ProjectDashboard;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Services\ProjectDashboard\DateFilterService;

class DepartmentService
{
    protected $taskStatsService;
    protected $timeCalculationService;
    protected $dateFilterService;

    public function __construct(
        TaskStatsService $taskStatsService,
        TimeCalculationService $timeCalculationService,
        DateFilterService $dateFilterService
    ) {
        $this->taskStatsService = $taskStatsService;
        $this->timeCalculationService = $timeCalculationService;
        $this->dateFilterService = $dateFilterService;
    }

    /**
     * الحصول على تفاصيل قسم معين مع الإحصائيات
     */
    public function getDepartmentDetails($department, $dateFilters = null)
    {
        $departmentData = User::select('department', DB::raw('COUNT(*) as employees_count'))
            ->where('department', $department)
            ->groupBy('department')
            ->first();

        if (!$departmentData) {
            return null;
        }

        $departmentUserIds = User::where('department', $department)->pluck('id')->toArray();

        // إحصائيات المشاريع
        $departmentProjects = $this->getDepartmentProjects($departmentUserIds, $dateFilters);

        // إحصائيات المهام
        $taskStats = $this->getDepartmentTaskStats($department, $dateFilters);

        // إحصائيات الوقت
        $timeStats = $this->getDepartmentTimeStats($department, $departmentUserIds, $dateFilters);

        // الفرق في القسم
        $teams = $this->getDepartmentTeams($department);

        return [
            'department_data' => $departmentData,
            'department_projects' => $departmentProjects,
            'task_stats' => $taskStats,
            'time_stats' => $timeStats,
            'teams' => $teams
        ];
    }

    /**
     * الحصول على مشاريع القسم
     */
    public function getDepartmentProjects($departmentUserIds, $dateFilters = null)
    {
        $query = DB::table('project_service_user')
            ->join('projects', 'project_service_user.project_id', '=', 'projects.id')
            ->whereIn('project_service_user.user_id', $departmentUserIds);

        // تطبيق فلترة التاريخ
        if ($dateFilters && $dateFilters['has_filter']) {
            if ($dateFilters['from_date']) {
                $query->where('projects.created_at', '>=', $dateFilters['from_date']);
            }
            if ($dateFilters['to_date']) {
                $query->where('projects.created_at', '<=', $dateFilters['to_date']);
            }
        }

        return $query->select('projects.*')
            ->distinct('projects.id')
            ->get();
    }

    /**
     * الحصول على مجموع نسب مشاركة القسم في المشاريع
     */
    public function getDepartmentTotalProjectShare($departmentUserIds, $dateFilters = null)
    {
        $query = DB::table('project_service_user')
            ->join('projects', 'project_service_user.project_id', '=', 'projects.id')
            ->whereIn('project_service_user.user_id', $departmentUserIds);

        // تطبيق فلترة التاريخ
        if ($dateFilters && $dateFilters['has_filter']) {
            if ($dateFilters['from_date']) {
                $query->where('projects.created_at', '>=', $dateFilters['from_date']);
            }
            if ($dateFilters['to_date']) {
                $query->where('projects.created_at', '<=', $dateFilters['to_date']);
            }
        }

        return $query->sum('project_service_user.project_share') ?? 0;
    }

    /**
     * الحصول على إحصائيات مهام القسم
     */
    public function getDepartmentTaskStats($department, $dateFilters = null)
    {
        // Regular Tasks
        $regularTaskStats = $this->taskStatsService->getRegularTaskStatsByDepartment($department, $dateFilters);

        // Template Tasks
        $templateTaskStats = $this->taskStatsService->getTemplateTaskStatsByDepartment($department, $dateFilters);

        return $this->taskStatsService->combineTaskStats($regularTaskStats, $templateTaskStats);
    }

    /**
     * الحصول على إحصائيات الوقت للقسم
     */
    public function getDepartmentTimeStats($department, $departmentUserIds, $dateFilters = null)
    {
        // احتساب الوقت للمهام العادية
        $regularTimeStats = $this->timeCalculationService->calculateTimeStats($departmentUserIds, false, $dateFilters);

        // احتساب الوقت لمهام القوالب
        $templateTimeStats = $this->timeCalculationService->calculateTimeStats($departmentUserIds, true, $dateFilters);

        // الوقت الفعلي الإضافي للمهام النشطة
        $regularRealTime = $this->timeCalculationService->calculateRealTimeMinutes($departmentUserIds, false);
        $templateRealTime = $this->timeCalculationService->calculateRealTimeMinutes($departmentUserIds, true);

        // إجمالي الوقت المستهلك مع الوقت الفعلي للمهام النشطة
        $totalTimeSpent = $regularTimeStats['time_spent'] + $templateTimeStats['time_spent'] + $regularRealTime + $templateRealTime;
        $totalTimeEstimated = $regularTimeStats['time_estimated'] + $templateTimeStats['time_estimated'];
        $totalFlexibleTimeSpent = $regularTimeStats['flexible_time_spent'] + $templateTimeStats['flexible_time_spent'];

        // للكفاءة: استخدم الوقت المقدر والمستهلك للمهام المكتملة فقط
        $totalCompletedTimeEstimated = $regularTimeStats['completed_time_estimated'] + $templateTimeStats['completed_time_estimated'];
        $totalCompletedTimeSpent = $regularTimeStats['completed_time_spent'] + $templateTimeStats['completed_time_spent'];

        return [
            'spent_minutes' => $totalTimeSpent,
            'estimated_minutes' => $totalTimeEstimated,
            'flexible_spent_minutes' => $totalFlexibleTimeSpent,
            'spent_formatted' => $this->timeCalculationService->formatMinutesToTime($totalTimeSpent),
            'estimated_formatted' => $this->timeCalculationService->formatMinutesToTime($totalTimeEstimated),
            'flexible_spent_formatted' => $this->timeCalculationService->formatMinutesToTime($totalFlexibleTimeSpent),
            // الوقت الفعلي الإضافي
            'real_time_minutes' => $regularRealTime + $templateRealTime,
            'real_time_formatted' => $this->timeCalculationService->formatMinutesToTime($regularRealTime + $templateRealTime),
            // كفاءة المهام المكتملة فقط
            'efficiency' => ($totalCompletedTimeEstimated > 0 && $totalCompletedTimeSpent > 0)
                ? round(($totalCompletedTimeEstimated / $totalCompletedTimeSpent) * 100)
                : 0
        ];
    }

    /**
     * الحصول على الفرق في القسم
     */
    public function getDepartmentTeams($department)
    {
        return \App\Models\Team::whereHas('owner', function($query) use ($department) {
            $query->where('department', $department);
        })->with(['owner', 'users'])->get();
    }

    /**
     * الحصول على أعضاء فريق القسم مع إحصائياتهم
     */
    public function getDepartmentTeamMembers($department)
    {
        $teamMembers = User::where('department', $department)
            ->select('id', 'name', 'email', 'phone_number', 'department', 'created_at')
            ->get();

        foreach ($teamMembers as $member) {
            $member->active_projects_count = DB::table('project_service_user')
                ->join('projects', 'project_service_user.project_id', '=', 'projects.id')
                ->where('project_service_user.user_id', $member->id)
                ->where('projects.status', 'جاري التنفيذ')
                ->distinct('projects.id')
                ->count('projects.id');

            $member->total_projects = DB::table('project_service_user')
                ->where('user_id', $member->id)
                ->distinct('project_id')
                ->count('project_id');

            $regularTasksCompleted = DB::table('task_users')
                ->where('user_id', $member->id)
                ->where('status', 'completed')
                ->count();

            $regularTasksTotal = DB::table('task_users')
                ->where('user_id', $member->id)
                ->count();

            $templateTasksCompleted = DB::table('template_task_user')
                ->where('user_id', $member->id)
                ->where('status', 'completed')
                ->count();

            $templateTasksTotal = DB::table('template_task_user')
                ->where('user_id', $member->id)
                ->count();

            $member->completed_tasks = $regularTasksCompleted + $templateTasksCompleted;
            $member->total_tasks = $regularTasksTotal + $templateTasksTotal;

            $member->completion_rate = $member->total_tasks > 0
                ? round(($member->completed_tasks / $member->total_tasks) * 100)
                : 0;

            $member->last_activity = $this->taskStatsService->getLastActivity($member->id);
            $member->phone = $member->phone_number;
        }

        return $teamMembers;
    }

    /**
     * إضافة الإحصائيات للأقسام
     */
    public function addStatsToDeepartmentsData($departmentsData)
    {
        foreach ($departmentsData as $dept) {
            // الحصول على معرفات المستخدمين في القسم
            $deptUserIds = User::where('department', $dept->department)->pluck('id')->toArray();

            $dept->active_projects = DB::table('project_service_user')
                ->join('users', 'project_service_user.user_id', '=', 'users.id')
                ->join('projects', 'project_service_user.project_id', '=', 'projects.id')
                ->where('users.department', $dept->department)
                ->where('projects.status', 'جاري التنفيذ')
                ->distinct('projects.id')
                ->count('projects.id');

            $regularTasksCompleted = DB::table('task_users')
                ->join('users', 'task_users.user_id', '=', 'users.id')
                ->where('users.department', $dept->department)
                ->where('task_users.status', 'completed')
                ->count();

            $templateTasksCompleted = DB::table('template_task_user')
                ->join('users', 'template_task_user.user_id', '=', 'users.id')
                ->where('users.department', $dept->department)
                ->where('template_task_user.status', 'completed')
                ->count();

            $dept->completed_tasks = $regularTasksCompleted + $templateTasksCompleted;

            $regularTasksTotal = DB::table('task_users')
                ->join('users', 'task_users.user_id', '=', 'users.id')
                ->where('users.department', $dept->department)
                ->count();

            $templateTasksTotal = DB::table('template_task_user')
                ->join('users', 'template_task_user.user_id', '=', 'users.id')
                ->where('users.department', $dept->department)
                ->count();

            $totalTasks = $regularTasksTotal + $templateTasksTotal;

            $dept->completion_rate = $totalTasks > 0 ? round(($dept->completed_tasks / $totalTasks) * 100) : 0;

            // === إضافة الوقت الفعلي للقسم ===
            // احتساب الوقت للمهام العادية
            $regularTimeStats = $this->timeCalculationService->calculateTimeStats($deptUserIds, false);

            // احتساب الوقت لمهام القوالب
            $templateTimeStats = $this->timeCalculationService->calculateTimeStats($deptUserIds, true);

            // الوقت الفعلي الإضافي للمهام النشطة
            $regularRealTime = $this->timeCalculationService->calculateRealTimeMinutes($deptUserIds, false);
            $templateRealTime = $this->timeCalculationService->calculateRealTimeMinutes($deptUserIds, true);

            // إجمالي الوقت المستهلك مع الوقت الفعلي
            $totalRealTimeSpent = $regularTimeStats['time_spent'] + $templateTimeStats['time_spent'] + $regularRealTime + $templateRealTime;

            $dept->total_time_spent = $totalRealTimeSpent;
            $dept->total_time_formatted = $this->timeCalculationService->formatMinutesToTime($totalRealTimeSpent);

            // عدد المهام النشطة الآن
            $activeTasks = DB::table('task_users')
                ->join('users', 'task_users.user_id', '=', 'users.id')
                ->where('users.department', $dept->department)
                ->where('task_users.status', 'in_progress')
                ->count();

            $activeTemplateTasks = DB::table('template_task_user')
                ->join('users', 'template_task_user.user_id', '=', 'users.id')
                ->where('users.department', $dept->department)
                ->where('template_task_user.status', 'in_progress')
                ->count();

            $dept->active_tasks_count = $activeTasks + $activeTemplateTasks;
        }

        return $departmentsData;
    }

    /**
     * الحصول على معرفات المستخدمين في القسم
     */
    public function getDepartmentUserIds($department)
    {
        return User::where('department', $department)->pluck('id')->toArray();
    }
}
