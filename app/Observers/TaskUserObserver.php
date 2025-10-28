<?php

namespace App\Observers;

use App\Models\TaskUser;
use App\Services\BadgeService;
use App\Services\Tasks\TaskNotificationService;
use App\Services\TimeTracking\TimeTrackingService;
use App\Models\Season;
use App\Models\UserSeasonPoint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TaskUserObserver
{
    protected $badgeService;
    protected $taskNotificationService;
    protected $timeTrackingService;

    public function __construct(
        BadgeService $badgeService,
        TaskNotificationService $taskNotificationService,
        TimeTrackingService $timeTrackingService
    ) {
        $this->badgeService = $badgeService;
        $this->taskNotificationService = $taskNotificationService;
        $this->timeTrackingService = $timeTrackingService;
    }

    /**
     * Handle the TaskUser "updated" event.
     */
    public function updated(TaskUser $taskUser)
    {
        // التحقق من أن الحالة تغيرت
        if ($taskUser->wasChanged('status')) {
            $oldStatus = $taskUser->getOriginal('status');
            $newStatus = $taskUser->status;

            // إدارة time tracking حسب تغيير الحالة
            $this->handleTimeTracking($taskUser, $oldStatus, $newStatus);

            // إذا تغيرت من أي حالة إلى مكتملة
            if ($newStatus === 'completed' && $oldStatus !== 'completed') {
                $this->addPointsForCompletedTask($taskUser);
            }
            // إذا تغيرت من مكتملة إلى أي حالة أخرى
            elseif ($oldStatus === 'completed' && $newStatus !== 'completed') {
                $this->removePointsForUncompletedTask($taskUser);
            }
        }
    }

    /**
     * إدارة time tracking حسب تغيير حالة المهمة
     */
    private function handleTimeTracking(TaskUser $taskUser, $oldStatus, $newStatus)
    {
        try {

            if ($newStatus === 'in_progress' && $oldStatus !== 'in_progress') {
                $this->timeTrackingService->startTaskTracking($taskUser);
                Log::info('Time tracking started for task', [
                    'task_user_id' => $taskUser->id,
                    'user_id' => $taskUser->user_id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus
                ]);
            }
            elseif (in_array($newStatus, ['completed', 'paused', 'cancelled']) && $oldStatus === 'in_progress') {
                $this->timeTrackingService->stopTaskTracking($taskUser);
                Log::info('Time tracking stopped for task', [
                    'task_user_id' => $taskUser->id,
                    'user_id' => $taskUser->user_id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus
                ]);
            }
            elseif ($newStatus === 'cancelled' && $taskUser->hasActiveTimeLog()) {
                $this->timeTrackingService->stopTaskTracking($taskUser);
                Log::info('Time tracking stopped due to task cancellation', [
                    'task_user_id' => $taskUser->id,
                    'user_id' => $taskUser->user_id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error handling time tracking in TaskUserObserver', [
                'task_user_id' => $taskUser->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'error' => $e->getMessage()
            ]);
        }
    }


    private function addPointsForCompletedTask(TaskUser $taskUser)
    {
        Log::info('Task completed but points not added automatically - awaiting approval', [
            'task_user_id' => $taskUser->id,
            'task_id' => $taskUser->task_id,
            'user_id' => $taskUser->user_id,
            'status' => $taskUser->status
        ]);

        try {
            $taskUser->load(['task.createdBy', 'user']);
            $this->taskNotificationService->notifyTaskCompleted($taskUser);
        } catch (\Exception $e) {
            Log::error('Failed to send task completion notification', [
                'error' => $e->getMessage(),
                'task_user_id' => $taskUser->id
            ]);
        }

        return;
    }

    /**
     * إزالة النقاط من المستخدم عند إلغاء إكمال المهمة
     */
    private function removePointsForUncompletedTask(TaskUser $taskUser)
    {
        try {
            $task = $taskUser->task;
            $user = $taskUser->user;

            if (!$task || !$user) {
                Log::warning('Task or User not found for TaskUser removal', ['task_user_id' => $taskUser->id]);
                return;
            }

            // الحصول على الموسم النشط أو موسم المهمة
            $season = $taskUser->season ?: Season::where('is_active', true)->first();

            if (!$season) {
                Log::warning('No active season found for task uncompletion', [
                    'task_id' => $task->id,
                    'user_id' => $user->id
                ]);
                return;
            }

            $points = $task->points ?? 10; // النقاط من المهمة أو 10 افتراضي

            DB::transaction(function () use ($user, $season, $points, $task) {
                // العثور على نقاط المستخدم
                $userSeasonPoint = UserSeasonPoint::where('user_id', $user->id)
                                                 ->where('season_id', $season->id)
                                                 ->first();

                if ($userSeasonPoint) {
                    // التأكد من عدم جعل النقاط أو المهام سالبة
                    $newPoints = max(0, $userSeasonPoint->total_points - $points);
                    $newTasksCompleted = max(0, $userSeasonPoint->tasks_completed - 1);

                    $userSeasonPoint->update([
                        'total_points' => $newPoints,
                        'tasks_completed' => $newTasksCompleted,
                    ]);

                    Log::info('Points removed for uncompleted task', [
                        'user_id' => $user->id,
                        'task_id' => $task->id,
                        'points_removed' => $points,
                        'total_points' => $newPoints,
                        'season_id' => $season->id
                    ]);

                    // تحديث الشارة تلقائياً (سيتم عبر UserSeasonPointObserver)
                } else {
                    Log::warning('UserSeasonPoint not found for task uncompletion', [
                        'user_id' => $user->id,
                        'season_id' => $season->id,
                        'task_id' => $task->id
                    ]);
                }
            });

        } catch (\Exception $e) {
            Log::error('Error removing points for uncompleted task', [
                'task_user_id' => $taskUser->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
