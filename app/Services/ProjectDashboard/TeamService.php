<?php

namespace App\Services\ProjectDashboard;

use App\Models\Team;
use Illuminate\Support\Facades\DB;

class TeamService
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


    public function getTeamDetails($teamId, $department = null, $dateFilters = null)
    {
        $team = Team::with(['owner', 'users'])->findOrFail($teamId);

        // التحقق من أن الفريق ينتمي للقسم المطلوب
        if ($department && $team->owner->department !== $department) {
            return null;
        }

        $teamMembers = $team->users;
        $teamUserIds = $teamMembers->pluck('id')->toArray();

        // إحصائيات المشاريع
        $teamProjects = $this->getTeamProjects($teamUserIds, $dateFilters);

        // إحصائيات المهام
        $taskStats = $this->getTeamTaskStats($teamUserIds, $dateFilters);

        // إحصائيات الوقت
        $timeStats = $this->getTeamTimeStats($teamUserIds, $dateFilters);

        // تفاصيل الأعضاء
        $membersWithStats = $this->addStatsToMembers($teamMembers, $dateFilters);

        return [
            'team' => $team,
            'team_members' => $membersWithStats,
            'team_projects' => $teamProjects,
            'task_stats' => $taskStats,
            'time_stats' => $timeStats
        ];
    }


    public function getTeamProjects($teamUserIds, $dateFilters = null)
    {
        $query = DB::table('project_service_user')
            ->join('projects', 'project_service_user.project_id', '=', 'projects.id')
            ->whereIn('project_service_user.user_id', $teamUserIds)
            ->select('projects.*')
            ->distinct('projects.id');

        // تطبيق فلاتر التاريخ (باستخدام مفاتيح DateFilterService)
        if ($dateFilters && isset($dateFilters['has_filter']) && $dateFilters['has_filter']) {
            $this->dateFilterService->applyDateFilter(
                $query,
                'projects.created_at',
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }

        return $query->get();
    }

    /**
     * الحصول على مجموع نسب مشاركة الفريق في المشاريع
     */
    public function getTeamTotalProjectShare($teamUserIds, $dateFilters = null)
    {
        $query = DB::table('project_service_user')
            ->join('projects', 'project_service_user.project_id', '=', 'projects.id')
            ->whereIn('project_service_user.user_id', $teamUserIds);

        // تطبيق فلاتر التاريخ
        if ($dateFilters && isset($dateFilters['has_filter']) && $dateFilters['has_filter']) {
            $this->dateFilterService->applyDateFilter(
                $query,
                'projects.created_at',
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }

        return $query->sum('project_service_user.project_share') ?? 0;
    }


    public function getTeamTaskStats($teamUserIds, $dateFilters = null)
    {
        // Regular Tasks
        $regularTaskStats = $this->taskStatsService->getRegularTaskStats($teamUserIds, null, $dateFilters);

        // Template Tasks
        $templateTaskStats = $this->taskStatsService->getTemplateTaskStats($teamUserIds, null, $dateFilters);

        return $this->taskStatsService->combineTaskStats($regularTaskStats, $templateTaskStats);
    }


    public function getTeamTimeStats($teamUserIds, $dateFilters = null)
    {
        $regularTimeStats = $this->timeCalculationService->calculateTimeStats($teamUserIds, false, $dateFilters);

        $templateTimeStats = $this->timeCalculationService->calculateTimeStats($teamUserIds, true, $dateFilters);

        $regularRealTime = $this->timeCalculationService->calculateRealTimeMinutes($teamUserIds, false);
        $templateRealTime = $this->timeCalculationService->calculateRealTimeMinutes($teamUserIds, true);

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
            // الوقت الفعلي الإضافي للفريق
            'real_time_minutes' => $regularRealTime + $templateRealTime,
            'real_time_formatted' => $this->timeCalculationService->formatMinutesToTime($regularRealTime + $templateRealTime),
            // كفاءة المهام المكتملة فقط
            'efficiency' => ($totalCompletedTimeEstimated > 0 && $totalCompletedTimeSpent > 0)
                ? round(($totalCompletedTimeEstimated / $totalCompletedTimeSpent) * 100)
                : 0
        ];
    }

    /**
     * إضافة الإحصائيات لأعضاء الفريق
     */
    public function addStatsToMembers($teamMembers, $dateFilters = null)
    {
        foreach ($teamMembers as $member) {
            // Get member's total project share (نسبة المشاركة الكلية)
            $projectQuery = DB::table('project_service_user')
                ->join('projects', 'project_service_user.project_id', '=', 'projects.id')
                ->where('project_service_user.user_id', $member->id);

            if ($dateFilters && isset($dateFilters['has_filter']) && $dateFilters['has_filter']) {
                $this->dateFilterService->applyDateFilter(
                    $projectQuery,
                    'projects.created_at',
                    $dateFilters['from_date'],
                    $dateFilters['to_date']
                );
            }

            $member->total_projects = $projectQuery->sum('project_service_user.project_share') ?? 0;

            // Get member's active projects share (نسبة المشاريع النشطة)
            $activeProjectQuery = DB::table('project_service_user')
                ->join('projects', 'project_service_user.project_id', '=', 'projects.id')
                ->where('project_service_user.user_id', $member->id)
                ->where('projects.status', 'جاري التنفيذ');

            if ($dateFilters && isset($dateFilters['has_filter']) && $dateFilters['has_filter']) {
                $this->dateFilterService->applyDateFilter(
                    $activeProjectQuery,
                    'projects.created_at',
                    $dateFilters['from_date'],
                    $dateFilters['to_date']
                );
            }

            $member->active_projects_count = $activeProjectQuery->sum('project_service_user.project_share') ?? 0;

            // Get member's task completion
            $regularTasksCompletedQuery = DB::table('task_users')
                ->where('user_id', $member->id)
                ->where('status', 'completed');

            if ($dateFilters && isset($dateFilters['has_filter']) && $dateFilters['has_filter']) {
                $this->dateFilterService->applyDateFilter(
                    $regularTasksCompletedQuery,
                    'updated_at',
                    $dateFilters['from_date'],
                    $dateFilters['to_date']
                );
            }

            $memberRegularTasksCompleted = $regularTasksCompletedQuery->count();

            $templateTasksCompletedQuery = DB::table('template_task_user')
                ->where('user_id', $member->id)
                ->where('status', 'completed');

            if ($dateFilters && isset($dateFilters['has_filter']) && $dateFilters['has_filter']) {
                $this->dateFilterService->applyDateFilter(
                    $templateTasksCompletedQuery,
                    'updated_at',
                    $dateFilters['from_date'],
                    $dateFilters['to_date']
                );
            }

            $memberTemplateTasksCompleted = $templateTasksCompletedQuery->count();

            $regularTasksTotalQuery = DB::table('task_users')
                ->where('user_id', $member->id);

            if ($dateFilters && isset($dateFilters['has_filter']) && $dateFilters['has_filter']) {
                $this->dateFilterService->applyDateFilter(
                    $regularTasksTotalQuery,
                    'created_at',
                    $dateFilters['from_date'],
                    $dateFilters['to_date']
                );
            }

            $memberRegularTasksTotal = $regularTasksTotalQuery->count();

            $templateTasksTotalQuery = DB::table('template_task_user')
                ->where('user_id', $member->id);

            if ($dateFilters && isset($dateFilters['has_filter']) && $dateFilters['has_filter']) {
                $this->dateFilterService->applyDateFilter(
                    $templateTasksTotalQuery,
                    'created_at',
                    $dateFilters['from_date'],
                    $dateFilters['to_date']
                );
            }

            $memberTemplateTasksTotal = $templateTasksTotalQuery->count();

            $member->completed_tasks = $memberRegularTasksCompleted + $memberTemplateTasksCompleted;
            $member->total_tasks = $memberRegularTasksTotal + $memberTemplateTasksTotal;

            $member->completion_rate = $member->total_tasks > 0
                ? round(($member->completed_tasks / $member->total_tasks) * 100)
                : 0;

            // Get last activity
            $member->last_activity = $this->taskStatsService->getLastActivity($member->id);
        }

        return $teamMembers;
    }

    /**
     * إضافة إحصائيات الأداء للفرق
     */
    public function addPerformanceStatsToTeams($teams)
    {
        foreach ($teams as $team) {
            $teamUserIds = $team->users->pluck('id')->toArray();

            // Team projects
            $teamProjectsCount = DB::table('project_service_user')
                ->join('projects', 'project_service_user.project_id', '=', 'projects.id')
                ->whereIn('project_service_user.user_id', $teamUserIds)
                ->where('projects.status', 'جاري التنفيذ')
                ->distinct('projects.id')
                ->count('projects.id');

            // Team tasks
            $teamTasksCompleted = DB::table('task_users')
                ->whereIn('user_id', $teamUserIds)
                ->where('status', 'completed')
                ->count();

            $teamTemplateTasksCompleted = DB::table('template_task_user')
                ->whereIn('user_id', $teamUserIds)
                ->where('status', 'completed')
                ->count();

            $team->active_projects_count = $teamProjectsCount;
            $team->completed_tasks_count = $teamTasksCompleted + $teamTemplateTasksCompleted;
        }

        return $teams;
    }


    public function calculateProjectStatsFromCollection($teamProjects, $teamUserIds = [], $dateFilters = null)
    {
        // حساب إحصائيات التيم من حالات الموظفين في الخدمات
        $stats = [
            'total' => 0,
            'in_progress' => 0,
            'waiting_form' => 0,
            'waiting_questions' => 0,
            'waiting_client' => 0,
            'waiting_call' => 0,
            'paused' => 0,
            'draft_delivery' => 0,
            'final_delivery' => 0,
        ];

        foreach ($teamProjects as $project) {
            // جلب حالات أعضاء التيم في خدمات هذا المشروع
            $teamMembersStatuses = DB::table('project_service_user')
                ->whereIn('user_id', $teamUserIds)
                ->where('project_id', $project->id)
                ->pluck('status')
                ->toArray();

            if (empty($teamMembersStatuses)) {
                continue;
            }

            // نعد المشروع مرة واحدة
            $stats['total'] += 1;

            // اختيار الحالة الغالبة (أو أول حالة) للمشروع
            // يمكن تعديل هذا المنطق حسب احتياجك
            $dominantStatus = $this->getDominantStatus($teamMembersStatuses);

            // تصنيف حسب الحالة
            switch ($dominantStatus) {
                case 'جاري':
                    $stats['in_progress'] += 1;
                    break;
                case 'واقف ع النموذج':
                    $stats['waiting_form'] += 1;
                    break;
                case 'واقف ع الأسئلة':
                    $stats['waiting_questions'] += 1;
                    break;
                case 'واقف ع العميل':
                    $stats['waiting_client'] += 1;
                    break;
                case 'واقف ع مكالمة':
                    $stats['waiting_call'] += 1;
                    break;
                case 'موقوف':
                    $stats['paused'] += 1;
                    break;
                case 'تسليم مسودة':
                    $stats['draft_delivery'] += 1;
                    break;
                case 'تم تسليم نهائي':
                    $stats['final_delivery'] += 1;
                    break;
                default:
                    $stats['in_progress'] += 1;
            }
        }

        return $stats;
    }

    /**
     * تحديد الحالة الغالبة من مجموعة حالات
     */
    private function getDominantStatus($statuses)
    {
        if (empty($statuses)) {
            return 'جاري';
        }

        // ترتيب الأولوية: الحالات الأكثر أهمية أولاً
        $priority = [
            'موقوف' => 1,
            'واقف ع العميل' => 2,
            'واقف ع النموذج' => 3,
            'واقف ع الأسئلة' => 4,
            'واقف ع مكالمة' => 5,
            'جاري' => 6,
            'تسليم مسودة' => 7,
            'تم تسليم نهائي' => 8,
        ];

        $statusCounts = array_count_values($statuses);

        // ترتيب الحالات حسب الأولوية
        uksort($statusCounts, function($a, $b) use ($priority) {
            $priorityA = $priority[$a] ?? 999;
            $priorityB = $priority[$b] ?? 999;
            return $priorityA - $priorityB;
        });

        // إرجاع الحالة ذات الأولوية الأعلى
        return array_key_first($statusCounts);
    }

    /**
     * الحصول على معرفات أعضاء الفريق
     */
    public function getTeamUserIds($teamId)
    {
        return Team::with('users')->findOrFail($teamId)->users->pluck('id')->toArray();
    }
}

