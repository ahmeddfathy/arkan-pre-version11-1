<?php

namespace App\Services\ProjectDashboard;

use App\Traits\HasNTPTime;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TimeCalculationService
{
    use HasNTPTime;

    protected $dateFilterService;

    public function __construct(DateFilterService $dateFilterService)
    {
        $this->dateFilterService = $dateFilterService;
    }

    public function calculateRealTimeMinutes($userIds = null, $isTemplate = false)
    {
        $now = $this->getCurrentCairoTime();

        if ($isTemplate) {
            $query = DB::table('template_task_user')
                ->where('template_task_user.status', 'in_progress')
                ->whereNotNull('template_task_user.started_at');

            if ($userIds) {
                if (is_array($userIds)) {
                    $query->whereIn('template_task_user.user_id', $userIds);
                } else {
                    $query->where('template_task_user.user_id', $userIds);
                }
            }

            $inProgressTasks = $query->get();

            // تجميع المهام حسب المستخدم لتجنب التداخل
            $userTimes = [];
            foreach ($inProgressTasks as $task) {
                $userId = $task->user_id;
                $startedAt = Carbon::parse($task->started_at);

                // إذا لم يكن المستخدم محفوظ من قبل، أو هذه المهمة بدأت قبل المحفوظة
                if (!isset($userTimes[$userId]) || $startedAt->lt(Carbon::parse($userTimes[$userId]['earliest_start']))) {
                    $userTimes[$userId] = [
                        'earliest_start' => $task->started_at,
                        'minutes_elapsed' => $startedAt->diffInMinutes($now)
                    ];
                }
            }

            return array_sum(array_column($userTimes, 'minutes_elapsed'));
        } else {
            // حساب الوقت الفعلي للمهام العادية
            $query = DB::table('task_users')
                ->where('task_users.status', 'in_progress')
                ->whereNotNull('task_users.start_date');

            if ($userIds) {
                if (is_array($userIds)) {
                    $query->whereIn('task_users.user_id', $userIds);
                } else {
                    $query->where('task_users.user_id', $userIds);
                }
            }

            $inProgressTasks = $query->get();

            // تجميع المهام حسب المستخدم لتجنب التداخل
            $userTimes = [];
            foreach ($inProgressTasks as $task) {
                $userId = $task->user_id;
                $startedAt = Carbon::parse($task->start_date);

                // إذا لم يكن المستخدم محفوظ من قبل، أو هذه المهمة بدأت قبل المحفوظة
                if (!isset($userTimes[$userId]) || $startedAt->lt(Carbon::parse($userTimes[$userId]['earliest_start']))) {
                    $userTimes[$userId] = [
                        'earliest_start' => $task->start_date,
                        'minutes_elapsed' => $startedAt->diffInMinutes($now)
                    ];
                }
            }

            return array_sum(array_column($userTimes, 'minutes_elapsed'));
        }
    }

    /**
     * تحويل الدقائق إلى صيغة مقروءة للوقت
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

    /**
     * حساب إحصائيات الوقت للمستخدمين
     */
    public function calculateTimeStats($userIds, $isTemplate = false, $dateFilters = null)
    {
        if ($isTemplate) {
            // الوقت المحفوظ للمهام المستندة للقوالب
            $timeSpentQuery = DB::table('template_task_user')
                ->whereIn('user_id', $userIds);

            if ($dateFilters && isset($dateFilters['has_filter']) && $dateFilters['has_filter']) {
                $this->dateFilterService->applyDateFilter(
                    $timeSpentQuery,
                    'template_task_user.created_at',
                    $dateFilters['from_date'],
                    $dateFilters['to_date']
                );
            }

            $timeSpent = $timeSpentQuery->sum('actual_minutes');

            // الوقت المحفوظ للمهام المكتملة فقط (لحساب الكفاءة)
            $completedTimeSpentQuery = DB::table('template_task_user')
                ->whereIn('user_id', $userIds)
                ->where('status', 'completed');

            if ($dateFilters && isset($dateFilters['has_filter']) && $dateFilters['has_filter']) {
                $this->dateFilterService->applyDateFilter(
                    $completedTimeSpentQuery,
                    'template_task_user.created_at',
                    $dateFilters['from_date'],
                    $dateFilters['to_date']
                );
            }

            $completedTimeSpent = $completedTimeSpentQuery->sum('actual_minutes');

            // احتساب الوقت المقدر مع تجاهل المهام المرنة
            $timeEstimatedQuery = DB::table('template_task_user')
                ->join('template_tasks', 'template_task_user.template_task_id', '=', 'template_tasks.id')
                ->whereIn('template_task_user.user_id', $userIds)
                ->whereNotNull('template_tasks.estimated_hours')
                ->whereNotNull('template_tasks.estimated_minutes');

            if ($dateFilters && isset($dateFilters['has_filter']) && $dateFilters['has_filter']) {
                $this->dateFilterService->applyDateFilter(
                    $timeEstimatedQuery,
                    'template_task_user.created_at',
                    $dateFilters['from_date'],
                    $dateFilters['to_date']
                );
            }

            $timeEstimated = $timeEstimatedQuery->sum(DB::raw('(template_tasks.estimated_hours * 60) + template_tasks.estimated_minutes'));

            // احتساب الوقت المقدر للمهام المكتملة فقط (لحساب الكفاءة)
            $completedTimeEstimatedQuery = DB::table('template_task_user')
                ->join('template_tasks', 'template_task_user.template_task_id', '=', 'template_tasks.id')
                ->whereIn('template_task_user.user_id', $userIds)
                ->where('template_task_user.status', 'completed')
                ->whereNotNull('template_tasks.estimated_hours')
                ->whereNotNull('template_tasks.estimated_minutes');

            if ($dateFilters && isset($dateFilters['has_filter']) && $dateFilters['has_filter']) {
                $this->dateFilterService->applyDateFilter(
                    $completedTimeEstimatedQuery,
                    'template_task_user.created_at',
                    $dateFilters['from_date'],
                    $dateFilters['to_date']
                );
            }

            $completedTimeEstimated = $completedTimeEstimatedQuery->sum(DB::raw('(template_tasks.estimated_hours * 60) + template_tasks.estimated_minutes'));

            // احتساب الوقت المستهلك في المهام المرنة
            $flexibleTimeSpentQuery = DB::table('template_task_user')
                ->join('template_tasks', 'template_task_user.template_task_id', '=', 'template_tasks.id')
                ->whereIn('template_task_user.user_id', $userIds)
                ->where(function($query) {
                    $query->whereNull('template_tasks.estimated_hours')
                          ->orWhereNull('template_tasks.estimated_minutes');
                });

            if ($dateFilters && isset($dateFilters['has_filter']) && $dateFilters['has_filter']) {
                $this->dateFilterService->applyDateFilter(
                    $flexibleTimeSpentQuery,
                    'template_task_user.created_at',
                    $dateFilters['from_date'],
                    $dateFilters['to_date']
                );
            }

            $flexibleTimeSpent = $flexibleTimeSpentQuery->sum('template_task_user.actual_minutes');

        } else {
            // الوقت المحفوظ للمهام العادية
            $timeSpentQuery = DB::table('task_users')
                ->whereIn('user_id', $userIds);

            if ($dateFilters && isset($dateFilters['has_filter']) && $dateFilters['has_filter']) {
                $this->dateFilterService->applyDateFilter(
                    $timeSpentQuery,
                    'task_users.created_at',
                    $dateFilters['from_date'],
                    $dateFilters['to_date']
                );
            }

            $timeSpent = $timeSpentQuery->sum(DB::raw('(actual_hours * 60) + actual_minutes'));

            // الوقت المحفوظ للمهام المكتملة فقط (لحساب الكفاءة)
            $completedTimeSpentQuery = DB::table('task_users')
                ->whereIn('user_id', $userIds)
                ->where('status', 'completed');

            if ($dateFilters && isset($dateFilters['has_filter']) && $dateFilters['has_filter']) {
                $this->dateFilterService->applyDateFilter(
                    $completedTimeSpentQuery,
                    'task_users.created_at',
                    $dateFilters['from_date'],
                    $dateFilters['to_date']
                );
            }

            $completedTimeSpent = $completedTimeSpentQuery->sum(DB::raw('(actual_hours * 60) + actual_minutes'));

            // احتساب الوقت المقدر مع تجاهل المهام المرنة
            $timeEstimatedQuery = DB::table('task_users')
                ->whereIn('user_id', $userIds)
                ->whereNotNull('estimated_hours')
                ->whereNotNull('estimated_minutes');

            if ($dateFilters && isset($dateFilters['has_filter']) && $dateFilters['has_filter']) {
                $this->dateFilterService->applyDateFilter(
                    $timeEstimatedQuery,
                    'task_users.created_at',
                    $dateFilters['from_date'],
                    $dateFilters['to_date']
                );
            }

            $timeEstimated = $timeEstimatedQuery->sum(DB::raw('(estimated_hours * 60) + estimated_minutes'));

            // احتساب الوقت المقدر للمهام المكتملة فقط (لحساب الكفاءة)
            $completedTimeEstimatedQuery = DB::table('task_users')
                ->whereIn('user_id', $userIds)
                ->where('status', 'completed')
                ->whereNotNull('estimated_hours')
                ->whereNotNull('estimated_minutes');

            if ($dateFilters && isset($dateFilters['has_filter']) && $dateFilters['has_filter']) {
                $this->dateFilterService->applyDateFilter(
                    $completedTimeEstimatedQuery,
                    'task_users.created_at',
                    $dateFilters['from_date'],
                    $dateFilters['to_date']
                );
            }

            $completedTimeEstimated = $completedTimeEstimatedQuery->sum(DB::raw('(estimated_hours * 60) + estimated_minutes'));

            // احتساب الوقت المستهلك في المهام المرنة
            $flexibleTimeSpentQuery = DB::table('task_users')
                ->whereIn('user_id', $userIds)
                ->where(function($query) {
                    $query->whereNull('estimated_hours')
                          ->orWhereNull('estimated_minutes');
                });

            if ($dateFilters && isset($dateFilters['has_filter']) && $dateFilters['has_filter']) {
                $this->dateFilterService->applyDateFilter(
                    $flexibleTimeSpentQuery,
                    'task_users.created_at',
                    $dateFilters['from_date'],
                    $dateFilters['to_date']
                );
            }

            $flexibleTimeSpent = $flexibleTimeSpentQuery->sum(DB::raw('(actual_hours * 60) + actual_minutes'));
        }

        return [
            'time_spent' => $timeSpent,
            'completed_time_spent' => $completedTimeSpent,
            'time_estimated' => $timeEstimated,
            'completed_time_estimated' => $completedTimeEstimated,
            'flexible_time_spent' => $flexibleTimeSpent,
        ];
    }
}
