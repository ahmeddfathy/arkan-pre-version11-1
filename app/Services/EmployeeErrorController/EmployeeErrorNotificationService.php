<?php

namespace App\Services\EmployeeErrorController;

use App\Models\EmployeeError;
use App\Models\User;
use App\Services\Slack\EmployeeErrorSlackService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;

class EmployeeErrorNotificationService
{
    protected $slackService;

    public function __construct(EmployeeErrorSlackService $slackService)
    {
        $this->slackService = $slackService;
    }

    /**
     * إرسال إشعار عند تسجيل خطأ جديد
     */
    public function notifyOnErrorCreated(EmployeeError $error): void
    {
        try {
            // إشعار للموظف صاحب الخطأ
            $this->notifyEmployee($error);

            // إشعار للمديرين إذا كان الخطأ جوهري
            if ($error->error_type === 'critical') {
                $this->notifyManagers($error);
            }

            Log::info('Error notifications sent', [
                'error_id' => $error->id,
                'user_id' => $error->user_id
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send error notifications', [
                'error_id' => $error->id,
                'exception' => $e->getMessage()
            ]);
        }
    }

    /**
     * إشعار الموظف بالخطأ المسجل عليه
     */
    private function notifyEmployee(EmployeeError $error): void
    {
        $employee = $error->user;

        if (!$employee) {
            return;
        }

        // إرسال إشعار Slack
        $this->slackService->sendErrorNotification($error);

        // يمكن استخدام Firebase أو Database Notification
        // $employee->notify(new EmployeeErrorNotification($error));

        Log::info('Employee notified about error', [
            'user_id' => $employee->id,
            'error_id' => $error->id
        ]);
    }

    /**
     * إشعار المديرين بالأخطاء الجوهرية
     */
    private function notifyManagers(EmployeeError $error): void
    {
        $managers = User::role(['admin', 'super-admin', 'hr', 'project_manager'])->get();

        foreach ($managers as $manager) {
            // إرسال إشعار Slack للمديرين
            $this->slackService->sendCriticalErrorNotification($error, $manager);

            // $manager->notify(new CriticalErrorNotification($error));

            Log::info('Manager notified about critical error', [
                'manager_id' => $manager->id,
                'error_id' => $error->id
            ]);
        }
    }

    /**
     * إشعار عند تحديث خطأ
     */
    public function notifyOnErrorUpdated(EmployeeError $error): void
    {
        try {
            $employee = $error->user;

            if ($employee) {
                // إرسال إشعار Slack
                $this->slackService->sendErrorUpdateNotification($error);

                // $employee->notify(new EmployeeErrorUpdatedNotification($error));

                Log::info('Employee notified about error update', [
                    'user_id' => $employee->id,
                    'error_id' => $error->id
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send error update notification', [
                'error_id' => $error->id,
                'exception' => $e->getMessage()
            ]);
        }
    }

    /**
     * إشعار عند حذف خطأ
     */
    public function notifyOnErrorDeleted(EmployeeError $error): void
    {
        try {
            $employee = $error->user;

            if ($employee) {
                // إرسال إشعار Slack
                $this->slackService->sendErrorDeletedNotification($error);

                // $employee->notify(new EmployeeErrorDeletedNotification($error));

                Log::info('Employee notified about error deletion', [
                    'user_id' => $employee->id,
                    'error_id' => $error->id
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send error deletion notification', [
                'error_id' => $error->id,
                'exception' => $e->getMessage()
            ]);
        }
    }

    /**
     * إشعار عند تجاوز حد معين من الأخطاء
     */
    public function notifyOnErrorThreshold(User $employee, int $errorCount, string $period = 'month'): void
    {
        try {
            // إشعار الموظف
            // $employee->notify(new ErrorThresholdNotification($errorCount, $period));

            // إشعار المديرين
            $managers = User::role(['admin', 'super-admin', 'hr'])->get();
            foreach ($managers as $manager) {
                // $manager->notify(new EmployeeErrorThresholdNotification($employee, $errorCount, $period));
            }

            Log::info('Error threshold notification sent', [
                'user_id' => $employee->id,
                'error_count' => $errorCount,
                'period' => $period
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send error threshold notification', [
                'user_id' => $employee->id,
                'exception' => $e->getMessage()
            ]);
        }
    }

    /**
     * إشعار تقرير أسبوعي بالأخطاء
     */
    public function sendWeeklyReport(): void
    {
        try {
            $managers = User::role(['admin', 'super-admin', 'hr', 'project_manager'])->get();

            foreach ($managers as $manager) {
                $stats = $this->getWeeklyStats();
                // $manager->notify(new WeeklyErrorReportNotification($stats));

                Log::info('Weekly error report sent', [
                    'manager_id' => $manager->id
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send weekly error report', [
                'exception' => $e->getMessage()
            ]);
        }
    }

    /**
     * الحصول على إحصائيات الأسبوع
     */
    private function getWeeklyStats(): array
    {
        return [
            'total_errors' => EmployeeError::whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])->count(),
            'critical_errors' => EmployeeError::where('error_type', 'critical')
                ->whereBetween('created_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ])->count(),
        ];
    }

    /**
     * إشعار تذكير للموظف بأخطائه
     */
    public function sendErrorReminder(User $employee): void
    {
        try {
            $pendingErrors = EmployeeError::where('user_id', $employee->id)
                ->where('created_at', '>=', now()->subDays(30))
                ->get();

            if ($pendingErrors->isNotEmpty()) {
                // $employee->notify(new ErrorReminderNotification($pendingErrors));

                Log::info('Error reminder sent', [
                    'user_id' => $employee->id,
                    'errors_count' => $pendingErrors->count()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send error reminder', [
                'user_id' => $employee->id,
                'exception' => $e->getMessage()
            ]);
        }
    }
}

