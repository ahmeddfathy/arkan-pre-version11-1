<?php

namespace App\Services\ProjectDashboard;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class EmployeePerformanceService
{
    protected $taskStatsService;
    protected $timeCalculationService;
    protected $projectStatsService;
    protected $dateFilterService;

    public function __construct(
        TaskStatsService $taskStatsService,
        TimeCalculationService $timeCalculationService,
        ProjectStatsService $projectStatsService,
        DateFilterService $dateFilterService
    ) {
        $this->taskStatsService = $taskStatsService;
        $this->timeCalculationService = $timeCalculationService;
        $this->projectStatsService = $projectStatsService;
        $this->dateFilterService = $dateFilterService;
    }

    public function getEmployeePerformanceReport($userId, $dateFilters = null)
    {
        $employee = User::findOrFail($userId);

        $employeeProjects = $this->projectStatsService->getEmployeeProjects($userId, $dateFilters);

        $projectStats = $this->calculateEmployeeProjectStats($userId, $employeeProjects, $dateFilters);

        // حساب إجمالي نسبة المشاركة في المشاريع
        $totalProjectShare = $this->getEmployeeTotalProjectShare($userId, $dateFilters);

        $projectCompletionRate = $totalProjectShare > 0
            ? round(($projectStats['completion_points'] / $totalProjectShare) * 100)
            : 0;

        $overdueProjects = $this->projectStatsService->filterOverdueProjects($employeeProjects);

        $taskStats = $this->getEmployeeTaskStats($userId, $dateFilters);

        $timeStats = $this->getEmployeeTimeStats($userId, $dateFilters);

        $recentTasks = $this->taskStatsService->getRecentTasks($userId, 10, $dateFilters);

        $monthlyStats = $this->taskStatsService->getMonthlyTaskCompletion($userId, 6, $dateFilters);

        $transferStats = $this->getEmployeeTransferStatistics($userId, $dateFilters);

        return [
            'employee' => $employee,
            'employee_projects' => $employeeProjects,
            'project_stats' => $projectStats,
            'total_project_share' => $totalProjectShare,
            'project_completion_rate' => $projectCompletionRate,
            'overdue_projects' => $overdueProjects,
            'task_stats' => $taskStats,
            'time_stats' => $timeStats,
            'recent_tasks' => $recentTasks,
            'monthly_stats' => $monthlyStats,
            'transfer_stats' => $transferStats
        ];
    }

    public function calculateEmployeeProjectStats($userId, $employeeProjects, $dateFilters = null)
    {
        $projectStats = [
            'total' => 0,
            'in_progress' => 0,
            'waiting_form' => 0,
            'waiting_questions' => 0,
            'waiting_client' => 0,
            'waiting_call' => 0,
            'paused' => 0,
            'draft_delivery' => 0,
            'final_delivery' => 0,
            'completion_points' => 0,
            'effective_completed' => 0,
        ];

        foreach ($employeeProjects as $project) {

            $projectShare = $this->getProjectShareForUser($userId, $project->id);

            $projectStats['total'] += $projectShare;

            $employeeTasksInProject = $this->calculateEmployeeProjectCompletion($userId, $project->id, $dateFilters);

            $currentTasksTotal = $employeeTasksInProject['current_tasks_total'] ?? 0;
            $currentTasksCompleted = $employeeTasksInProject['current_tasks_completed'] ?? 0;
            $completionRate = $employeeTasksInProject['completion_rate'] ?? 0;

            // حساب نقاط الإكمال بناءً على نسبة المشاركة
            $projectStats['completion_points'] += (($completionRate / 100) * $projectShare);

            if ($completionRate >= 100) {
                $projectStats['effective_completed'] += $projectShare;
            }

            // الحصول على حالة الموظف في المشروع من project_service_user
            $employeeStatus = $this->getEmployeeProjectStatus($userId, $project->id);

            // تصنيف المشاريع حسب حالة الموظف
            switch ($employeeStatus) {
                case 'جاري':
                    $projectStats['in_progress'] += $projectShare;
                    break;
                case 'واقف ع النموذج':
                    $projectStats['waiting_form'] += $projectShare;
                    break;
                case 'واقف ع الأسئلة':
                    $projectStats['waiting_questions'] += $projectShare;
                    break;
                case 'واقف ع العميل':
                    $projectStats['waiting_client'] += $projectShare;
                    break;
                case 'واقف ع مكالمة':
                    $projectStats['waiting_call'] += $projectShare;
                    break;
                case 'موقوف':
                    $projectStats['paused'] += $projectShare;
                    break;
                case 'تسليم مسودة':
                    $projectStats['draft_delivery'] += $projectShare;
                    break;
                case 'تم تسليم نهائي':
                    $projectStats['final_delivery'] += $projectShare;
                    break;
                default:
                    // إذا كانت الحالة فارغة أو غير معروفة، صنفها كـ جاري
                    $projectStats['in_progress'] += $projectShare;
            }
        }

        return $projectStats;
    }

    /**
     * الحصول على حالة الموظف في المشروع من project_service_user
     */
    private function getEmployeeProjectStatus($userId, $projectId)
    {
        return DB::table('project_service_user')
            ->where('user_id', $userId)
            ->where('project_id', $projectId)
            ->value('status') ?? 'جاري';
    }

    public function calculateEmployeeProjectCompletion($userId, $projectId, $dateFilters = null)
    {
        $currentTasksQuery = DB::table('task_users')
            ->join('tasks', 'task_users.task_id', '=', 'tasks.id')
            ->where('tasks.project_id', $projectId)
            ->where('task_users.user_id', $userId)
            ->where(function($query) {
                $query->where('task_users.is_transferred', false)
                      ->orWhereNull('task_users.is_transferred');
            });

        if ($dateFilters && isset($dateFilters['has_filter']) && $dateFilters['has_filter']) {
            $this->dateFilterService->applyDateFilter(
                $currentTasksQuery,
                'task_users.created_at',
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }

        $currentTasks = $currentTasksQuery->select('task_users.*')->get();

        $transferredFromTasksQuery = DB::table('task_users')
            ->join('tasks', 'task_users.task_id', '=', 'tasks.id')
            ->where('tasks.project_id', $projectId)
            ->where('task_users.original_user_id', $userId)
            ->where('task_users.is_transferred', true)
            ->where('task_users.user_id', '!=', $userId);

        if ($dateFilters && isset($dateFilters['has_filter']) && $dateFilters['has_filter']) {
            $this->dateFilterService->applyDateFilter(
                $transferredFromTasksQuery,
                'task_users.created_at',
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }

        $transferredFromTasks = $transferredFromTasksQuery->select('task_users.*')->get();

        $regularTasks = $currentTasks->merge($transferredFromTasks);

        $currentTemplateTasksQuery = DB::table('template_task_user')
            ->where('template_task_user.project_id', $projectId)
            ->where('template_task_user.user_id', $userId)
            ->where(function($query) {
                $query->where('template_task_user.is_transferred', false)
                      ->orWhereNull('template_task_user.is_transferred');
            });

        if ($dateFilters && isset($dateFilters['has_filter']) && $dateFilters['has_filter']) {
            $this->dateFilterService->applyDateFilter(
                $currentTemplateTasksQuery,
                'template_task_user.created_at',
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }

        $currentTemplateTasks = $currentTemplateTasksQuery->select('template_task_user.*')->get();

        $transferredFromTemplateTasksQuery = DB::table('template_task_user')
            ->where('template_task_user.project_id', $projectId)
            ->where('template_task_user.original_user_id', $userId)
            ->where('template_task_user.is_transferred', true)
            ->where('template_task_user.user_id', '!=', $userId);

        if ($dateFilters && isset($dateFilters['has_filter']) && $dateFilters['has_filter']) {
            $this->dateFilterService->applyDateFilter(
                $transferredFromTemplateTasksQuery,
                'template_task_user.created_at',
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }

        $transferredFromTemplateTasks = $transferredFromTemplateTasksQuery->select('template_task_user.*')->get();

        $totalOriginalTasks = $currentTasks->count() + $currentTemplateTasks->count() + $transferredFromTasks->count() + $transferredFromTemplateTasks->count();

        if ($totalOriginalTasks == 0) {
            return [
                'completion_rate' => 0,
                'completed_tasks' => 0,
                'total_tasks' => 0,
                'transferred_tasks' => 0,
                'current_tasks_total' => 0,
                'current_tasks_completed' => 0
            ];
        }

        $completedRegularTasks = $currentTasks->where('status', 'completed')->count();
        $completedTemplateTasks = $currentTemplateTasks->where('status', 'completed')->count();
        $completedTasks = $completedRegularTasks + $completedTemplateTasks;

        $transferredRegularTasks = $transferredFromTasks->count();
        $transferredTemplateTasks = $transferredFromTemplateTasks->count();

        $completionRate = round(($completedTasks / $totalOriginalTasks) * 100);

        $currentTasksTotal = $currentTasks->count() + $currentTemplateTasks->count();
        $currentTasksCompleted = $completedTasks;

        return [
            'completion_rate' => $completionRate,
            'completed_tasks' => $completedTasks,
            'total_tasks' => $totalOriginalTasks,
            'transferred_tasks' => $transferredRegularTasks + $transferredTemplateTasks,
            'current_tasks_total' => $currentTasksTotal,
            'current_tasks_completed' => $currentTasksCompleted
        ];
    }

    public function getEmployeeTaskStats($userId, $dateFilters = null)
    {
        $regularTaskStats = $this->taskStatsService->getRegularTaskStatsWithTransfers($userId, $dateFilters);

        $templateTaskStats = $this->taskStatsService->getTemplateTaskStatsWithTransfers($userId, $dateFilters);

        return $this->taskStatsService->combineTaskStats($regularTaskStats, $templateTaskStats);
    }

    public function getEmployeeTimeStats($userId, $dateFilters = null)
    {
        $regularTimeStats = $this->timeCalculationService->calculateTimeStats([$userId], false, $dateFilters);

        $templateTimeStats = $this->timeCalculationService->calculateTimeStats([$userId], true, $dateFilters);

        $regularRealTime = $this->timeCalculationService->calculateRealTimeMinutes($userId, false);
        $templateRealTime = $this->timeCalculationService->calculateRealTimeMinutes($userId, true);

        $totalTimeSpent = $regularTimeStats['time_spent'] + $templateTimeStats['time_spent'] + $regularRealTime + $templateRealTime;
        $totalTimeEstimated = $regularTimeStats['time_estimated'] + $templateTimeStats['time_estimated'];
        $totalFlexibleTimeSpent = $regularTimeStats['flexible_time_spent'] + $templateTimeStats['flexible_time_spent'];

        $totalCompletedTimeEstimated = $regularTimeStats['completed_time_estimated'] + $templateTimeStats['completed_time_estimated'];
        $totalCompletedTimeSpent = $regularTimeStats['completed_time_spent'] + $templateTimeStats['completed_time_spent'];

        $regularInProgressQuery = DB::table('task_users')
            ->where('user_id', $userId)
            ->where('status', 'in_progress');

        if ($dateFilters && isset($dateFilters['has_filter']) && $dateFilters['has_filter']) {
            $this->dateFilterService->applyDateFilter(
                $regularInProgressQuery,
                'task_users.created_at',
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }

        $regularInProgressCount = $regularInProgressQuery->count();

        $templateInProgressQuery = DB::table('template_task_user')
            ->where('user_id', $userId)
            ->where('status', 'in_progress');

        if ($dateFilters && isset($dateFilters['has_filter']) && $dateFilters['has_filter']) {
            $this->dateFilterService->applyDateFilter(
                $templateInProgressQuery,
                'template_task_user.created_at',
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }

        $templateInProgressCount = $templateInProgressQuery->count();

        return [
            'spent_minutes' => $totalTimeSpent,
            'estimated_minutes' => $totalTimeEstimated,
            'flexible_spent_minutes' => $totalFlexibleTimeSpent,
            'spent_formatted' => $this->timeCalculationService->formatMinutesToTime($totalTimeSpent),
            'estimated_formatted' => $this->timeCalculationService->formatMinutesToTime($totalTimeEstimated),
            'flexible_spent_formatted' => $this->timeCalculationService->formatMinutesToTime($totalFlexibleTimeSpent),
            'real_time_minutes' => $regularRealTime + $templateRealTime,
            'real_time_formatted' => $this->timeCalculationService->formatMinutesToTime($regularRealTime + $templateRealTime),
            'active_tasks_count' => $regularInProgressCount + $templateInProgressCount,
            'efficiency' => ($totalCompletedTimeEstimated > 0 && $totalCompletedTimeSpent > 0)
                ? round(($totalCompletedTimeEstimated / $totalCompletedTimeSpent) * 100)
                : 0
        ];
    }

    public function getEmployeeTransferStatistics($userId, $dateFilters = null)
    {
        // ✅ المهام العادية المنقولة إلى الموظف (المستقبلة)
        $regularToUserQuery = DB::table('task_users as received')
            ->leftJoin('task_users as original', 'received.original_task_user_id', '=', 'original.id')
            ->where('received.user_id', $userId)
            ->where('received.is_additional_task', 1)
            ->where('received.task_source', 'transferred')
            ->whereNotNull('original.transferred_at');

        if ($dateFilters && isset($dateFilters['has_filter']) && $dateFilters['has_filter']) {
            if ($dateFilters['from_date']) {
                $regularToUserQuery->where('original.transferred_at', '>=', $dateFilters['from_date']);
            }
            if ($dateFilters['to_date']) {
                $regularToUserQuery->where('original.transferred_at', '<=', $dateFilters['to_date']);
            }
        }

        $regularTransferredToUser = $regularToUserQuery->count();

        // ✅ مهام القوالب المنقولة إلى الموظف (المستقبلة)
        $templateToUserQuery = DB::table('template_task_user as received')
            ->leftJoin('template_task_user as original', 'received.original_template_task_user_id', '=', 'original.id')
            ->where('received.user_id', $userId)
            ->where('received.is_additional_task', 1)
            ->where('received.task_source', 'transferred')
            ->whereNotNull('original.transferred_at');

        if ($dateFilters && isset($dateFilters['has_filter']) && $dateFilters['has_filter']) {
            if ($dateFilters['from_date']) {
                $templateToUserQuery->where('original.transferred_at', '>=', $dateFilters['from_date']);
            }
            if ($dateFilters['to_date']) {
                $templateToUserQuery->where('original.transferred_at', '<=', $dateFilters['to_date']);
            }
        }

        $templateTransferredToUser = $templateToUserQuery->count();

        // ✅ المهام العادية المنقولة من الموظف (المرسلة)
        $regularFromUserQuery = DB::table('task_users as original')
            ->where('original.user_id', $userId)
            ->where('original.is_transferred', 1)
            ->whereNotNull('original.transferred_at');

        if ($dateFilters && isset($dateFilters['has_filter']) && $dateFilters['has_filter']) {
            if ($dateFilters['from_date']) {
                $regularFromUserQuery->where('original.transferred_at', '>=', $dateFilters['from_date']);
            }
            if ($dateFilters['to_date']) {
                $regularFromUserQuery->where('original.transferred_at', '<=', $dateFilters['to_date']);
            }
        }

        $regularTransferredFromUser = $regularFromUserQuery->count();

        // ✅ مهام القوالب المنقولة من الموظف (المرسلة)
        $templateFromUserQuery = DB::table('template_task_user as original')
            ->where('original.user_id', $userId)
            ->where('original.is_transferred', 1)
            ->whereNotNull('original.transferred_at');

        if ($dateFilters && isset($dateFilters['has_filter']) && $dateFilters['has_filter']) {
            if ($dateFilters['from_date']) {
                $templateFromUserQuery->where('original.transferred_at', '>=', $dateFilters['from_date']);
            }
            if ($dateFilters['to_date']) {
                $templateFromUserQuery->where('original.transferred_at', '<=', $dateFilters['to_date']);
            }
        }

        $templateTransferredFromUser = $templateFromUserQuery->count();

        // ✅ تفاصيل المهام المنقولة من الموظف
        $transferredFromDetails = $this->getTransferredFromDetails($userId, $dateFilters);

        return [
            'transferred_to_me' => $regularTransferredToUser + $templateTransferredToUser,
            'transferred_from_me' => $regularTransferredFromUser + $templateTransferredFromUser,
            'regular_transferred_to_me' => $regularTransferredToUser,
            'template_transferred_to_me' => $templateTransferredToUser,
            'regular_transferred_from_me' => $regularTransferredFromUser,
            'template_transferred_from_me' => $templateTransferredFromUser,
            'transferred_from_details' => $transferredFromDetails,
            'has_transfers' => ($regularTransferredToUser + $templateTransferredToUser + $regularTransferredFromUser + $templateTransferredFromUser) > 0
        ];
    }

    private function getTransferredFromDetails($userId, $dateFilters = null)
    {
        // ✅ تفاصيل المهام العادية المنقولة من الموظف (باستخدام النظام الجديد)
        $regularTransferredQuery = DB::table('task_users as original')
            ->join('tasks', 'original.task_id', '=', 'tasks.id')
            ->leftJoin('projects', 'tasks.project_id', '=', 'projects.id')
            ->leftJoin('task_users as received', 'original.transferred_record_id', '=', 'received.id')
            ->leftJoin('users as to_users', 'received.user_id', '=', 'to_users.id')
            ->where('original.user_id', $userId)
            ->where('original.is_transferred', 1)
            ->whereNotNull('original.transferred_at')
            ->select(
                'tasks.name as task_name',
                DB::raw('COALESCE(to_users.name, "غير محدد") as current_user_name'),
                DB::raw('COALESCE(received.status, "غير معروف") as status'),
                DB::raw('COALESCE(original.transfer_reason, "") as transfer_reason'),
                'original.transferred_at as transferred_from_at',
                'projects.name as project_name',
                DB::raw("'regular' as task_type")
            );

        if ($dateFilters && isset($dateFilters['has_filter']) && $dateFilters['has_filter']) {
            if ($dateFilters['from_date']) {
                $regularTransferredQuery->where('original.transferred_at', '>=', $dateFilters['from_date']);
            }
            if ($dateFilters['to_date']) {
                $regularTransferredQuery->where('original.transferred_at', '<=', $dateFilters['to_date']);
            }
        }

        $regularTransferredDetails = $regularTransferredQuery->get();

        // ✅ تفاصيل مهام القوالب المنقولة من الموظف (باستخدام النظام الجديد)
        $templateTransferredQuery = DB::table('template_task_user as original')
            ->join('template_tasks', 'original.template_task_id', '=', 'template_tasks.id')
            ->leftJoin('projects', 'original.project_id', '=', 'projects.id')
            ->leftJoin('template_task_user as received', 'original.transferred_record_id', '=', 'received.id')
            ->leftJoin('users as to_users', 'received.user_id', '=', 'to_users.id')
            ->where('original.user_id', $userId)
            ->where('original.is_transferred', 1)
            ->whereNotNull('original.transferred_at')
            ->select(
                'template_tasks.name as task_name',
                DB::raw('COALESCE(to_users.name, "غير محدد") as current_user_name'),
                DB::raw('COALESCE(received.status, "غير معروف") as status'),
                DB::raw('COALESCE(original.transfer_reason, "") as transfer_reason'),
                'original.transferred_at as transferred_from_at',
                'projects.name as project_name',
                DB::raw("'template' as task_type")
            );

        if ($dateFilters && isset($dateFilters['has_filter']) && $dateFilters['has_filter']) {
            if ($dateFilters['from_date']) {
                $templateTransferredQuery->where('original.transferred_at', '>=', $dateFilters['from_date']);
            }
            if ($dateFilters['to_date']) {
                $templateTransferredQuery->where('original.transferred_at', '<=', $dateFilters['to_date']);
            }
        }

        $templateTransferredDetails = $templateTransferredQuery->get();

        return $regularTransferredDetails->merge($templateTransferredDetails)
            ->sortByDesc('transferred_from_at')
            ->take(20)
            ->values()
            ->toArray();
    }

    /**
     * الحصول على مجموع نسب مشاركة الموظف في المشاريع
     */
    public function getEmployeeTotalProjectShare($userId, $dateFilters = null)
    {
        $query = DB::table('project_service_user')
            ->join('projects', 'project_service_user.project_id', '=', 'projects.id')
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

        return $query->sum('project_service_user.project_share') ?? 0;
    }

    /**
     * الحصول على نسبة مشاركة الموظف في مشروع معين
     */
    public function getProjectShareForUser($userId, $projectId)
    {
        return DB::table('project_service_user')
            ->where('user_id', $userId)
            ->where('project_id', $projectId)
            ->value('project_share') ?? 0;
    }
}
