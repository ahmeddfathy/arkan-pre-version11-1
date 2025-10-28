<?php

namespace App\Services\ProjectManagement;

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProjectEmployeeAnalyticsService
{
    protected $analyticsService;

    public function __construct(ProjectAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Helper method to get employee analytics for specific project
     */
    public function getEmployeeProjectAnalytics(Project $project, User $employee)
    {
        // Employee tasks in this project
        $regularTasks = DB::table('task_users')
            ->join('tasks', 'task_users.task_id', '=', 'tasks.id')
            ->where('task_users.user_id', $employee->id)
            ->where('tasks.project_id', $project->id)
            ->select('task_users.*', 'tasks.name as task_name', 'tasks.due_date')
            ->get();

        $templateTasks = DB::table('template_task_user')
            ->join('template_tasks', 'template_task_user.template_task_id', '=', 'template_tasks.id')
            ->where('template_task_user.user_id', $employee->id)
            ->where('template_task_user.project_id', $project->id)
            ->select('template_task_user.*', 'template_tasks.name as task_name')
            ->get();

        // Calculate task statistics
        $regularTaskStats = [
            'total' => $regularTasks->count(),
            'new' => $regularTasks->where('status', 'new')->count(),
            'in_progress' => $regularTasks->where('status', 'in_progress')->count(),
            'paused' => $regularTasks->where('status', 'paused')->count(),
            'completed' => $regularTasks->where('status', 'completed')->count(),
        ];

        $templateTaskStats = [
            'total' => $templateTasks->count(),
            'new' => $templateTasks->where('status', 'new')->count(),
            'in_progress' => $templateTasks->where('status', 'in_progress')->count(),
            'paused' => $templateTasks->where('status', 'paused')->count(),
            'completed' => $templateTasks->where('status', 'completed')->count(),
        ];

        $combinedStats = [
            'total' => $regularTaskStats['total'] + $templateTaskStats['total'],
            'new' => $regularTaskStats['new'] + $templateTaskStats['new'],
            'in_progress' => $regularTaskStats['in_progress'] + $templateTaskStats['in_progress'],
            'paused' => $regularTaskStats['paused'] + $templateTaskStats['paused'],
            'completed' => $regularTaskStats['completed'] + $templateTaskStats['completed'],
        ];

        // Calculate time spent
        $regularTimeSpent = $regularTasks->sum(function($task) {
            return ($task->actual_hours * 60) + $task->actual_minutes;
        });

        $templateTimeSpent = $templateTasks->sum('actual_minutes');

        // Calculate time spent for COMPLETED tasks only (for efficiency calculation)
        $regularCompletedTimeSpent = $regularTasks->where('status', 'completed')->sum(function($task) {
            return ($task->actual_hours * 60) + $task->actual_minutes;
        });

        $templateCompletedTimeSpent = $templateTasks->where('status', 'completed')->sum('actual_minutes');

        // الوقت الفعلي الإضافي للمهام النشطة
        $regularRealTime = $this->analyticsService->calculateRealTimeMinutes($employee->id, false, $project->id);
        $templateRealTime = $this->analyticsService->calculateRealTimeMinutes($employee->id, true, $project->id);

        // ✅ إحصائيات نقل المهام
        $transferStats = $this->getTransferStatisticsForEmployee($employee->id, $project->id);

        // إجمالي الوقت المستهلك مع الوقت الفعلي للمهام النشطة
        $totalTimeSpent = $regularTimeSpent + $templateTimeSpent + $regularRealTime + $templateRealTime;

        // Calculate estimated time (excluding flexible tasks)
        $regularTimeEstimated = $regularTasks->sum(function($task) {
            // Only count tasks that have both estimated_hours and estimated_minutes set (not flexible tasks)
            if ($task->estimated_hours !== null && $task->estimated_minutes !== null) {
                return ($task->estimated_hours * 60) + $task->estimated_minutes;
            }
            return 0;
        });

        $templateTimeEstimated = DB::table('template_task_user')
            ->join('template_tasks', 'template_task_user.template_task_id', '=', 'template_tasks.id')
            ->where('template_task_user.user_id', $employee->id)
            ->where('template_task_user.project_id', $project->id)
            ->whereNotNull('template_tasks.estimated_hours')
            ->whereNotNull('template_tasks.estimated_minutes')
            ->sum(DB::raw('(template_tasks.estimated_hours * 60) + template_tasks.estimated_minutes'));

        $totalTimeEstimated = $regularTimeEstimated + $templateTimeEstimated;

        // Calculate estimated time for COMPLETED tasks only (for efficiency calculation)
        $regularCompletedTimeEstimated = $regularTasks->where('status', 'completed')->sum(function($task) {
            if ($task->estimated_hours !== null && $task->estimated_minutes !== null) {
                return ($task->estimated_hours * 60) + $task->estimated_minutes;
            }
            return 0;
        });

        $templateCompletedTimeEstimated = DB::table('template_task_user')
            ->join('template_tasks', 'template_task_user.template_task_id', '=', 'template_tasks.id')
            ->where('template_task_user.user_id', $employee->id)
            ->where('template_task_user.project_id', $project->id)
            ->where('template_task_user.status', 'completed')
            ->whereNotNull('template_tasks.estimated_hours')
            ->whereNotNull('template_tasks.estimated_minutes')
            ->sum(DB::raw('(template_tasks.estimated_hours * 60) + template_tasks.estimated_minutes'));

        $totalCompletedTimeEstimated = $regularCompletedTimeEstimated + $templateCompletedTimeEstimated;
        $totalCompletedTimeSpent = $regularCompletedTimeSpent + $templateCompletedTimeSpent;

        // Find overdue tasks
        $overdueTasks = $regularTasks->filter(function($task) {
            return $task->due_date &&
                   Carbon::parse($task->due_date)->isPast() &&
                   in_array($task->status, ['new', 'in_progress']);
        });

        // Recent activities
        $recentActivities = $this->getRecentActivities($regularTasks, $templateTasks);

        return [
            'task_stats' => [
                'regular' => $regularTaskStats,
                'template' => $templateTaskStats,
                'combined' => $combinedStats,
                'completion_rate' => $combinedStats['total'] > 0 ?
                    round(($combinedStats['completed'] / $combinedStats['total']) * 100) : 0
            ],
            'time_stats' => [
                'spent_minutes' => $totalTimeSpent,
                'estimated_minutes' => $totalTimeEstimated,
                'spent_formatted' => $this->formatMinutesToTime($totalTimeSpent),
                'estimated_formatted' => $this->formatMinutesToTime($totalTimeEstimated),
                // الوقت الفعلي الإضافي للموظف
                'real_time_minutes' => $regularRealTime + $templateRealTime,
                'real_time_formatted' => $this->formatMinutesToTime($regularRealTime + $templateRealTime),
                // كفاءة المهام المكتملة فقط
                'efficiency' => ($totalCompletedTimeEstimated > 0 && $totalCompletedTimeSpent > 0)
                    ? round(($totalCompletedTimeEstimated / $totalCompletedTimeSpent) * 100)
                    : 0
            ],
            'overdue_tasks' => $overdueTasks->map(function($task) {
                return [
                    'id' => $task->id,
                    'name' => $task->task_name,
                    'due_date' => $task->due_date,
                    'days_overdue' => Carbon::parse($task->due_date)->diffInDays(Carbon::now()),
                    'status' => $task->status
                ];
            })->values()->toArray(),
            'recent_activities' => $recentActivities,
            'all_tasks' => [
                'regular' => $regularTasks->toArray(),
                'template' => $templateTasks->toArray()
            ],
            'transfer_stats' => $transferStats
        ];
    }

    /**
     * Get recent activities for employee
     */
    private function getRecentActivities($regularTasks, $templateTasks)
    {
        return collect()
            ->merge($regularTasks->map(function($task) {
                return [
                    'type' => 'regular_task',
                    'task_name' => $task->task_name,
                    'status' => $task->status,
                    'updated_at' => $task->updated_at
                ];
            }))
            ->merge($templateTasks->map(function($task) {
                return [
                    'type' => 'template_task',
                    'task_name' => $task->task_name,
                    'status' => $task->status,
                    'updated_at' => $task->updated_at
                ];
            }))
            ->sortByDesc('updated_at')
            ->take(10)
            ->values()
            ->toArray();
    }

    /**
     * ✅ إحصائيات نقل المهام للموظف (محدثة لتطابق منطق النقل الصحيح)
     *
     * المنطق:
     * - المهام المنقولة إلى المستخدم: is_additional_task=true && task_source='transferred'
     * - المهام المنقولة من المستخدم: is_transferred=true في السجل الأصلي
     */
    public function getTransferStatisticsForEmployee($userId, $projectId)
    {
        // ✅ المهام العادية المنقولة إلى المستخدم (بناءً على منطق النقل الصحيح)
        $regularTransferredToUser = DB::table('task_users')
            ->join('tasks', 'task_users.task_id', '=', 'tasks.id')
            ->where('task_users.user_id', $userId)
            ->where('tasks.project_id', $projectId)
            ->where('task_users.is_additional_task', true)
            ->where('task_users.task_source', 'transferred')
            ->count();

        // ✅ مهام القوالب المنقولة إلى المستخدم (بناءً على منطق النقل الصحيح)
        $templateTransferredToUser = DB::table('template_task_user')
            ->where('user_id', $userId)
            ->where('project_id', $projectId)
            ->where('is_additional_task', true)
            ->where('task_source', 'transferred')
            ->count();

        // ✅ المهام العادية المنقولة من المستخدم (السجل الأصلي منقول)
        $regularTransferredFromUser = DB::table('task_users')
            ->join('tasks', 'task_users.task_id', '=', 'tasks.id')
            ->where('task_users.user_id', $userId)
            ->where('tasks.project_id', $projectId)
            ->where('task_users.is_transferred', true)
            ->count();

        // ✅ مهام القوالب المنقولة من المستخدم (السجل الأصلي منقول)
        $templateTransferredFromUser = DB::table('template_task_user')
            ->where('user_id', $userId)
            ->where('project_id', $projectId)
            ->where('is_transferred', true)
            ->count();

        // تفاصيل المهام المنقولة من المستخدم
        $transferredFromDetails = $this->getTransferredFromDetails($userId, $projectId);

        // ✅ تفاصيل المهام المنقولة إلى المستخدم
        $transferredToDetails = $this->getTransferredToDetails($userId, $projectId);

        return [
            'transferred_to_me' => $regularTransferredToUser + $templateTransferredToUser,
            'transferred_from_me' => $regularTransferredFromUser + $templateTransferredFromUser,
            'regular_transferred_to_me' => $regularTransferredToUser,
            'template_transferred_to_me' => $templateTransferredToUser,
            'regular_transferred_from_me' => $regularTransferredFromUser,
            'template_transferred_from_me' => $templateTransferredFromUser,
            'transferred_from_details' => $transferredFromDetails,
            'transferred_to_details' => $transferredToDetails,
            'has_transfers' => ($regularTransferredToUser + $templateTransferredToUser + $regularTransferredFromUser + $templateTransferredFromUser) > 0
        ];
    }

    /**
     * ✅ تفاصيل المهام المنقولة من المستخدم (محدثة للمنطق الصحيح)
     */
    private function getTransferredFromDetails($userId, $projectId)
    {
        // ✅ المهام العادية المنقولة من المستخدم مع التفاصيل (السجلات المنقولة)
        $regularTransferredDetails = DB::table('task_users as original')
            ->join('tasks', 'original.task_id', '=', 'tasks.id')
            ->leftJoin('task_users as transferred', 'original.transferred_record_id', '=', 'transferred.id')
            ->leftJoin('users', 'transferred.user_id', '=', 'users.id')
            ->where('original.user_id', $userId)
            ->where('tasks.project_id', $projectId)
            ->where('original.is_transferred', true)
            ->select(
                'tasks.name as task_name',
                'users.name as current_user_name',
                'transferred.status',
                'original.transfer_reason',
                'original.transferred_at',
                DB::raw("'regular' as task_type")
            )
            ->get();

        // ✅ مهام القوالب المنقولة من المستخدم مع التفاصيل (السجلات المنقولة)
        $templateTransferredDetails = DB::table('template_task_user as original')
            ->join('template_tasks', 'original.template_task_id', '=', 'template_tasks.id')
            ->leftJoin('template_task_user as transferred', 'original.transferred_record_id', '=', 'transferred.id')
            ->leftJoin('users', 'transferred.user_id', '=', 'users.id')
            ->where('original.user_id', $userId)
            ->where('original.project_id', $projectId)
            ->where('original.is_transferred', true)
            ->select(
                'template_tasks.name as task_name',
                'users.name as current_user_name',
                'transferred.status',
                'original.transfer_reason',
                'original.transferred_at',
                DB::raw("'template' as task_type")
            )
            ->get();

        return $regularTransferredDetails->merge($templateTransferredDetails)
            ->sortByDesc('transferred_at')
            ->take(10) // أحدث 10 مهام منقولة
            ->values()
            ->map(function($item) {
                return (array) $item; // تحويل stdClass إلى array
            })
            ->toArray();
    }

    /**
     * ✅ تفاصيل المهام المنقولة إلى المستخدم
     */
    private function getTransferredToDetails($userId, $projectId)
    {
        // ✅ المهام العادية المنقولة إلى المستخدم مع التفاصيل
        $regularTransferredDetails = DB::table('task_users as received')
            ->join('tasks', 'received.task_id', '=', 'tasks.id')
            ->leftJoin('task_users as original', 'received.original_task_user_id', '=', 'original.id')
            ->leftJoin('users', 'original.user_id', '=', 'users.id')
            ->where('received.user_id', $userId)
            ->where('tasks.project_id', $projectId)
            ->where('received.is_additional_task', true)
            ->where('received.task_source', 'transferred')
            ->select(
                'tasks.name as task_name',
                'users.name as original_user_name',
                'received.status',
                'original.transfer_reason',
                'original.transferred_at',
                DB::raw("'regular' as task_type")
            )
            ->get();

        // ✅ مهام القوالب المنقولة إلى المستخدم مع التفاصيل
        $templateTransferredDetails = DB::table('template_task_user as received')
            ->join('template_tasks', 'received.template_task_id', '=', 'template_tasks.id')
            ->leftJoin('template_task_user as original', 'received.original_template_task_user_id', '=', 'original.id')
            ->leftJoin('users', 'original.user_id', '=', 'users.id')
            ->where('received.user_id', $userId)
            ->where('received.project_id', $projectId)
            ->where('received.is_additional_task', true)
            ->where('received.task_source', 'transferred')
            ->select(
                'template_tasks.name as task_name',
                'users.name as original_user_name',
                'received.status',
                'original.transfer_reason',
                'original.transferred_at',
                DB::raw("'template' as task_type")
            )
            ->get();

        return $regularTransferredDetails->merge($templateTransferredDetails)
            ->sortByDesc('transferred_at')
            ->take(10) // أحدث 10 مهام منقولة
            ->values()
            ->map(function($item) {
                return (array) $item; // تحويل stdClass إلى array
            })
            ->toArray();
    }

    /**
     * Helper method to format minutes to readable time
     */
    public function formatMinutesToTime($minutes)
    {
        if ($minutes == 0) return '0h';

        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        $parts = [];
        if ($hours > 0) $parts[] = $hours . 'h';
        if ($mins > 0) $parts[] = $mins . 'm';

        return implode(' ', $parts);
    }
}
