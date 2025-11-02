<?php

namespace App\Observers;

use App\Models\AdditionalTaskUser;
use App\Models\Season;
use App\Models\UserSeasonPoint;
use App\Services\Notifications\AdditionalTaskNotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AdditionalTaskUserObserver
{
    protected $notificationService;

    public function __construct(AdditionalTaskNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    /**
     * Handle the AdditionalTaskUser "updated" event.
     */
    public function updated(AdditionalTaskUser $additionalTaskUser)
    {
        // التحقق من أن الحالة تغيرت إلى مكتملة
        if ($additionalTaskUser->wasChanged('status') && $additionalTaskUser->status === 'completed') {
            $this->addPointsForCompletedAdditionalTask($additionalTaskUser);
            $this->notificationService->notifyTaskCompleted($additionalTaskUser);
        }
        // التحقق من إلغاء الإكمال
        elseif (
            $additionalTaskUser->wasChanged('status') &&
            $additionalTaskUser->getOriginal('status') === 'completed' &&
            $additionalTaskUser->status !== 'completed'
        ) {
            $this->removePointsForUncompletedAdditionalTask($additionalTaskUser);
        }

        // إرسال إشعارات للحالات الأخرى
        if ($additionalTaskUser->wasChanged('status')) {
            $oldStatus = $additionalTaskUser->getOriginal('status');
            $newStatus = $additionalTaskUser->status;

            // إشعارات للحالات المختلفة
            switch ($newStatus) {
                case 'approved':
                    if ($oldStatus === 'applied') {
                        // في حالة الموافقة عبر Observer (نادر)، إرسال بدون معلومات المهمة العادية
                        $this->notificationService->notifyUserApproved($additionalTaskUser, null);
                    }
                    break;
                case 'rejected':
                    if ($oldStatus === 'applied') {
                        $this->notificationService->notifyUserRejected($additionalTaskUser);
                    }
                    break;
                case 'assigned':
                    $this->notificationService->notifyUserAssigned($additionalTaskUser);
                    break;
            }

            // تسجيل تغيير الحالة (للتتبع)
            Log::info('Additional task user status changed', [
                'additional_task_user_id' => $additionalTaskUser->id,
                'user_id' => $additionalTaskUser->user_id,
                'additional_task_id' => $additionalTaskUser->additional_task_id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ]);
        }
    }

    /**
     * Handle the AdditionalTaskUser "created" event.
     */
    public function created(AdditionalTaskUser $additionalTaskUser)
    {
        // إرسال إشعار بناء على الحالة الأولية
        switch ($additionalTaskUser->status) {
            case 'applied':
                $this->notificationService->notifyUserApplied($additionalTaskUser);
                break;
            case 'assigned':
                $this->notificationService->notifyUserAssigned($additionalTaskUser);
                break;
        }
    }

    /**
     * إضافة النقاط للمستخدم عند إكمال المهمة الإضافية
     */
    private function addPointsForCompletedAdditionalTask(AdditionalTaskUser $additionalTaskUser)
    {
        try {
            $additionalTask = $additionalTaskUser->additionalTask;
            $user = $additionalTaskUser->user;

            if (!$additionalTask || !$user) {
                Log::warning('AdditionalTask or User not found for AdditionalTaskUser', ['additional_task_user_id' => $additionalTaskUser->id]);
                return;
            }

            // الحصول على الموسم النشط أو موسم المهمة
            $season = $additionalTask->season ?: Season::where('is_active', true)->first();

            if (!$season) {
                Log::warning('No active season found for additional task completion', [
                    'additional_task_id' => $additionalTask->id,
                    'user_id' => $user->id
                ]);
                return;
            }

            $points = $additionalTaskUser->points_earned ?? $additionalTask->points ?? 10;

            DB::transaction(function () use ($user, $season, $points, $additionalTask, $additionalTaskUser) {
                // إضافة أو تحديث نقاط المستخدم
                $userSeasonPoint = UserSeasonPoint::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'season_id' => $season->id,
                    ],
                    [
                        'total_points' => DB::raw("total_points + {$points}"),
                        'tasks_completed' => DB::raw("tasks_completed + 1"),
                    ]
                );

                // إعادة تحميل البيانات
                $userSeasonPoint->refresh();

                Log::info('Points added for completed additional task', [
                    'user_id' => $user->id,
                    'additional_task_id' => $additionalTask->id,
                    'task_title' => $additionalTask->title,
                    'points_added' => $points,
                    'total_points' => $userSeasonPoint->total_points,
                    'season_id' => $season->id
                ]);

                // تحديث الشارة تلقائياً (سيتم عبر UserSeasonPointObserver)
            });
        } catch (\Exception $e) {
            Log::error('Error adding points for completed additional task', [
                'additional_task_user_id' => $additionalTaskUser->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * إزالة النقاط من المستخدم عند إلغاء إكمال المهمة الإضافية
     */
    private function removePointsForUncompletedAdditionalTask(AdditionalTaskUser $additionalTaskUser)
    {
        try {
            $additionalTask = $additionalTaskUser->additionalTask;
            $user = $additionalTaskUser->user;

            if (!$additionalTask || !$user) {
                Log::warning('AdditionalTask or User not found for AdditionalTaskUser removal', ['additional_task_user_id' => $additionalTaskUser->id]);
                return;
            }

            // الحصول على الموسم النشط أو موسم المهمة
            $season = $additionalTask->season ?: Season::where('is_active', true)->first();

            if (!$season) {
                Log::warning('No active season found for additional task uncompletion', [
                    'additional_task_id' => $additionalTask->id,
                    'user_id' => $user->id
                ]);
                return;
            }

            $pointsToRemove = $additionalTaskUser->getOriginal('points_earned') ?? $additionalTask->points ?? 10;

            DB::transaction(function () use ($user, $season, $pointsToRemove, $additionalTask) {
                // العثور على نقاط المستخدم
                $userSeasonPoint = UserSeasonPoint::where('user_id', $user->id)
                    ->where('season_id', $season->id)
                    ->first();

                if ($userSeasonPoint) {
                    // التأكد من عدم جعل النقاط أو المهام سالبة
                    $newPoints = max(0, $userSeasonPoint->total_points - $pointsToRemove);
                    $newTasksCompleted = max(0, $userSeasonPoint->tasks_completed - 1);

                    $userSeasonPoint->update([
                        'total_points' => $newPoints,
                        'tasks_completed' => $newTasksCompleted,
                    ]);

                    Log::info('Points removed for uncompleted additional task', [
                        'user_id' => $user->id,
                        'additional_task_id' => $additionalTask->id,
                        'task_title' => $additionalTask->title,
                        'points_removed' => $pointsToRemove,
                        'total_points' => $newPoints,
                        'season_id' => $season->id
                    ]);

                    // تحديث الشارة تلقائياً (سيتم عبر UserSeasonPointObserver)
                } else {
                    Log::warning('UserSeasonPoint not found for additional task uncompletion', [
                        'user_id' => $user->id,
                        'season_id' => $season->id,
                        'additional_task_id' => $additionalTask->id
                    ]);
                }
            });
        } catch (\Exception $e) {
            Log::error('Error removing points for uncompleted additional task', [
                'additional_task_user_id' => $additionalTaskUser->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
