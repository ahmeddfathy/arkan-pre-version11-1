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

    public function notifyOnErrorCreated(EmployeeError $error): void
    {
        try {
            $this->notifyEmployee($error);

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

    private function notifyEmployee(EmployeeError $error): void
    {
        $employee = $error->user;

        if (!$employee) {
            return;
        }

        $this->slackService->sendErrorNotification($error);

        // $employee->notify(new EmployeeErrorNotification($error));

        Log::info('Employee notified about error', [
            'user_id' => $employee->id,
            'error_id' => $error->id
        ]);
    }

    private function notifyManagers(EmployeeError $error): void
    {
        $managers = User::role(['admin', 'super-admin', 'hr', 'project_manager'])->get();

        foreach ($managers as $manager) {
            $this->slackService->sendCriticalErrorNotification($error, $manager);
            Log::info('Manager notified about critical error', [
                'manager_id' => $manager->id,
                'error_id' => $error->id
            ]);
        }
    }

    public function notifyOnErrorUpdated(EmployeeError $error): void
    {
        try {
            $employee = $error->user;

            if ($employee) {
                $this->slackService->sendErrorUpdateNotification($error);

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

    public function notifyOnErrorDeleted(EmployeeError $error): void
    {
        try {
            $employee = $error->user;

            if ($employee) {
                $this->slackService->sendErrorDeletedNotification($error);

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

    public function notifyOnErrorThreshold(User $employee, int $errorCount, string $period = 'month'): void
    {
        try {
            $managers = User::role(['admin', 'super-admin', 'hr'])->get();
            foreach ($managers as $manager) {
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

    public function sendWeeklyReport(): void
    {
        try {
            $managers = User::role(['admin', 'super-admin', 'hr', 'project_manager'])->get();

            foreach ($managers as $manager) {
                $stats = $this->getWeeklyStats();

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

    public function sendErrorReminder(User $employee): void
    {
        try {
            $pendingErrors = EmployeeError::where('user_id', $employee->id)
                ->where('created_at', '>=', now()->subDays(30))
                ->get();

            if ($pendingErrors->isNotEmpty()) {

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

