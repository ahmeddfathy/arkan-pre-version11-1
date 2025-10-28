<?php

namespace App\Services\TimeTracking;

use App\Models\TaskTimeLog;
use App\Models\TaskUser;
use App\Models\TemplateTaskUser;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TimeTrackingService
{
    /**
     * بدء تسجيل الوقت لمهمة عادية
     */
    public function startTaskTracking(TaskUser $taskUser): TaskTimeLog
    {
        // إيقاف أي جلسة نشطة للمستخدم
        $this->stopAllActiveLogsForUser($taskUser->user_id);

        return $taskUser->startTimeLog();
    }

    /**
     * بدء تسجيل الوقت لمهمة قالب
     */
    public function startTemplateTaskTracking(TemplateTaskUser $templateTaskUser): TaskTimeLog
    {
        // إيقاف أي جلسة نشطة للمستخ
        $this->stopAllActiveLogsForUser($templateTaskUser->user_id);

        return $templateTaskUser->startTimeLog();
    }


    public function stopTaskTracking($task): ?TaskTimeLog
    {
        return $task->stopActiveTimeLog();
    }

    /**
     * إيقاف جميع الجلسات النشطة للمستخدم
     */
    public function stopAllActiveLogsForUser(int $userId): int
    {
        $activeLogs = TaskTimeLog::where('user_id', $userId)
                                ->whereNull('stopped_at')
                                ->get();

                foreach ($activeLogs as $log) {
            $log->stop();

            // لا نحدث actual_time في المهام - النظام منفصل
        }

        return $activeLogs->count();
    }

    /**
     * الحصول على الجلسة النشطة للمستخدم
     */
    public function getActiveLogForUser(int $userId): ?TaskTimeLog
    {
        return TaskTimeLog::where('user_id', $userId)
                         ->whereNull('stopped_at')
                         ->latest('started_at')
                         ->first();
    }

    /**
     * الحصول على تقرير يومي للمستخدم
     */
    public function getDailyReport(int $userId, string $date = null): array
    {
        $date = $date ?? now()->toDateString();

        $logs = TaskTimeLog::forUser($userId)
                          ->forDate($date)
                          ->completed()
                          ->with(['taskUser.task', 'templateTaskUser.templateTask'])
                          ->get();

        $totalMinutes = $logs->sum('duration_minutes');

        return [
            'date' => $date,
            'total_minutes' => $totalMinutes,
            'total_formatted' => $this->formatMinutes($totalMinutes),
            'sessions_count' => $logs->count(),
            'sessions' => $logs->map(function ($log) {
                return [
                    'id' => $log->id,
                    'task_type' => $log->task_type,
                    'task_name' => $this->getTaskName($log),
                    'started_at' => $log->started_at->format('H:i'),
                    'stopped_at' => $log->stopped_at?->format('H:i'),
                    'duration_minutes' => $log->duration_minutes,
                    'duration_formatted' => $log->formatted_duration,
                ];
            }),
        ];
    }

    /**
     * الحصول على تقرير أسبوعي للمستخدم
     */
    public function getWeeklyReport(int $userId, string $startDate = null): array
    {
        $startDate = $startDate ? Carbon::parse($startDate) : now()->startOfWeek();
        $endDate = $startDate->copy()->endOfWeek();

        $logs = TaskTimeLog::forUser($userId)
                          ->forDateRange($startDate->toDateString(), $endDate->toDateString())
                          ->completed()
                          ->with(['taskUser.task', 'templateTaskUser.templateTask'])
                          ->get()
                          ->groupBy('work_date');

        $dailyTotals = [];
        $totalMinutes = 0;

        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            $dateString = $date->toDateString();
            $dayLogs = $logs->get($dateString, collect());
            $dayMinutes = $dayLogs->sum('duration_minutes');

            $dailyTotals[] = [
                'date' => $dateString,
                'day_name' => $date->format('l'),
                'minutes' => $dayMinutes,
                'formatted' => $this->formatMinutes($dayMinutes),
                'sessions_count' => $dayLogs->count(),
            ];

            $totalMinutes += $dayMinutes;
        }

        return [
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'total_minutes' => $totalMinutes,
            'total_formatted' => $this->formatMinutes($totalMinutes),
            'daily_totals' => $dailyTotals,
        ];
    }

    /**
     * الحصول على تقرير شهري للمستخدم
     */
    public function getMonthlyReport(int $userId, int $year = null, int $month = null): array
    {
        $year = $year ?? now()->year;
        $month = $month ?? now()->month;

        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();

        $logs = TaskTimeLog::forUser($userId)
                          ->forDateRange($startDate->toDateString(), $endDate->toDateString())
                          ->completed()
                          ->with(['taskUser.task', 'templateTaskUser.templateTask'])
                          ->get();

        $totalMinutes = $logs->sum('duration_minutes');
        $sessionsCount = $logs->count();

        // تجميع حسب المهام
        $tasksSummary = $logs->groupBy(function ($log) {
            return $log->task_type . '_' . ($log->task_user_id ?? $log->template_task_user_id);
        })->map(function ($taskLogs) {
            $firstLog = $taskLogs->first();
            return [
                'task_name' => $this->getTaskName($firstLog),
                'task_type' => $firstLog->task_type,
                'total_minutes' => $taskLogs->sum('duration_minutes'),
                'total_formatted' => $this->formatMinutes($taskLogs->sum('duration_minutes')),
                'sessions_count' => $taskLogs->count(),
            ];
        })->values();

        return [
            'year' => $year,
            'month' => $month,
            'month_name' => $startDate->format('F'),
            'total_minutes' => $totalMinutes,
            'total_formatted' => $this->formatMinutes($totalMinutes),
            'sessions_count' => $sessionsCount,
            'tasks_summary' => $tasksSummary,
        ];
    }




    private function getTaskName(TaskTimeLog $log): string
    {
        if ($log->task_type === 'template' && $log->templateTaskUser) {
            return $log->templateTaskUser->templateTask->title ?? 'مهمة قالب';
        } elseif ($log->task_type === 'regular' && $log->taskUser) {
            return $log->taskUser->task->title ?? 'مهمة عادية';
        }

        return 'مهمة غير محددة';
    }

    /**
     * تنسيق الدقائق لعرض مقروء
     */
    private function formatMinutes(int $minutes): string
    {
        if ($minutes === 0) {
            return '0 دقيقة';
        }

        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        $parts = [];
        if ($hours > 0) {
            $parts[] = $hours . ' ساعة';
        }
        if ($remainingMinutes > 0) {
            $parts[] = $remainingMinutes . ' دقيقة';
        }

        return implode(' و ', $parts);
    }

    /**
     * التحقق من وجود جلسة نشطة للمستخدم
     */
    public function hasActiveSession(int $userId): bool
    {
        return TaskTimeLog::where('user_id', $userId)
                         ->whereNull('stopped_at')
                         ->exists();
    }

    /**
     * الحصول على إحصائيات سريعة للمستخدم
     */
    public function getUserStats(int $userId): array
    {
        $today = now()->toDateString();
        $thisWeek = now()->startOfWeek();
        $thisMonth = now()->startOfMonth();

        return [
            'today_minutes' => TaskTimeLog::forUser($userId)->forDate($today)->completed()->sum('duration_minutes') ?? 0,
            'week_minutes' => TaskTimeLog::forUser($userId)->forDateRange($thisWeek->toDateString(), now()->toDateString())->completed()->sum('duration_minutes') ?? 0,
            'month_minutes' => TaskTimeLog::forUser($userId)->forDateRange($thisMonth->toDateString(), now()->toDateString())->completed()->sum('duration_minutes') ?? 0,
            'has_active_session' => $this->hasActiveSession($userId),
            'active_session' => $this->getActiveLogForUser($userId),
        ];
    }
}
