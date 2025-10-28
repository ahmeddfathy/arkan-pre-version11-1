<?php

namespace App\Services\Notifications;

use App\Models\AdditionalTask;
use App\Models\AdditionalTaskUser;
use App\Models\User;
use App\Services\Slack\AdditionalTaskSlackService;
use App\Services\Notifications\Traits\HasSlackNotification;
use App\Services\Notifications\Traits\HasFirebaseNotification;
use Illuminate\Support\Facades\Log;

class AdditionalTaskNotificationService
{
    use HasSlackNotification, HasFirebaseNotification;

    protected $additionalTaskSlackService;

    public function __construct(AdditionalTaskSlackService $additionalTaskSlackService)
    {
        $this->additionalTaskSlackService = $additionalTaskSlackService;
    }

    /**
     * إرسال إشعارات عند تعيين مهمة إضافية للمستخدم
     */
    public function notifyUserAssigned(AdditionalTaskUser $taskUser): void
    {
        try {
            $user = $taskUser->user;
            $task = $taskUser->additionalTask;

            if (!$user || !$task) {
                return;
            }

            $message = "تم تعيين مهمة إضافية جديدة لك: {$task->title}";

            // إرسال إشعار Firebase
            $this->sendTypedFirebaseNotification(
                $user,
                'additional-task',
                'assigned',
                $message,
                $task->id
            );

            // إرسال إشعار Slack للمستخدم (DM)
            if ($user->slack_user_id) {
                $this->additionalTaskSlackService->sendAdditionalTaskNotification(
                    $task,
                    $user,
                    $task->creator,
                    'assigned'
                );
            }

            // إرسال إشعار Slack للقناة (HR Channel)
            $this->sendSlackChannelNotification($task, $user, 'تعيين');

        } catch (\Exception $e) {
            Log::error('Error sending additional task assignment notifications', [
                'task_user_id' => $taskUser->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * إرسال إشعارات عند موافقة على طلب المهمة الإضافية
     */
    public function notifyUserApproved(AdditionalTaskUser $taskUser): void
    {
        try {
            $user = $taskUser->user;
            $task = $taskUser->additionalTask;

            if (!$user || !$task) {
                return;
            }

            $message = "تمت الموافقة على طلبك للمهمة الإضافية: {$task->title}";

            // إرسال إشعار Firebase
            $this->sendTypedFirebaseNotification(
                $user,
                'additional-task',
                'approved',
                $message,
                $task->id
            );

            // إرسال إشعار Slack للمستخدم (DM)
            if ($user->slack_user_id) {
                $this->additionalTaskSlackService->sendAdditionalTaskNotification(
                    $task,
                    $user,
                    \Illuminate\Support\Facades\Auth::user() ?? $task->creator,
                    'approved'
                );
            }

            // إرسال إشعار Slack للقناة (HR Channel)
            $this->sendSlackChannelNotification($task, $user, 'موافقة');

        } catch (\Exception $e) {
            Log::error('Error sending additional task approval notifications', [
                'task_user_id' => $taskUser->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * إرسال إشعارات عند رفض طلب المهمة الإضافية
     */
    public function notifyUserRejected(AdditionalTaskUser $taskUser): void
    {
        try {
            $user = $taskUser->user;
            $task = $taskUser->additionalTask;

            if (!$user || !$task) {
                return;
            }

            $message = "تم رفض طلبك للمهمة الإضافية: {$task->title}";

            // إرسال إشعار Firebase
            $this->sendTypedFirebaseNotification(
                $user,
                'additional-task',
                'rejected',
                $message,
                $task->id
            );

            // إرسال إشعار Slack للمستخدم (DM)
            if ($user->slack_user_id) {
                $this->additionalTaskSlackService->sendAdditionalTaskNotification(
                    $task,
                    $user,
                    \Illuminate\Support\Facades\Auth::user() ?? $task->creator,
                    'rejected'
                );
            }

            // إرسال إشعار Slack للقناة (HR Channel)
            $this->sendSlackChannelNotification($task, $user, 'رفض');

        } catch (\Exception $e) {
            Log::error('Error sending additional task rejection notifications', [
                'task_user_id' => $taskUser->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * إرسال إشعارات عند اكتمال المهمة الإضافية
     */
    public function notifyTaskCompleted(AdditionalTaskUser $taskUser): void
    {
        try {
            $user = $taskUser->user;
            $task = $taskUser->additionalTask;

            if (!$user || !$task) {
                return;
            }

            $pointsEarned = $taskUser->points_earned ?? $task->points ?? 0;
            $message = "تهانينا! لقد أكملت المهمة الإضافية: {$task->title} وحصلت على {$pointsEarned} نقطة";

            // إرسال إشعار Firebase
            $this->sendTypedFirebaseNotification(
                $user,
                'additional-task',
                'completed',
                $message,
                $task->id
            );

            // إرسال إشعار Slack للمستخدم (DM)
            if ($user->slack_user_id) {
                $this->additionalTaskSlackService->sendAdditionalTaskNotification(
                    $task,
                    $user,
                    \Illuminate\Support\Facades\Auth::user() ?? $task->creator,
                    'completed'
                );
            }

            // إرسال إشعار Slack للقناة (HR Channel)
            $this->sendSlackChannelNotification($task, $user, 'إكمال', $pointsEarned);

        } catch (\Exception $e) {
            Log::error('Error sending additional task completion notifications', [
                'task_user_id' => $taskUser->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * إرسال إشعارات عند تقديم طلب للمهمة الإضافية
     */
    public function notifyUserApplied(AdditionalTaskUser $taskUser): void
    {
        try {
            $user = $taskUser->user;
            $task = $taskUser->additionalTask;

            if (!$user || !$task) {
                return;
            }

            $message = "تم تقديم طلبك للمهمة الإضافية: {$task->title}. في انتظار الموافقة.";

            // إرسال إشعار Firebase
            $this->sendTypedFirebaseNotification(
                $user,
                'additional-task',
                'applied',
                $message,
                $task->id
            );

            // إرسال إشعار Slack للقناة (HR Channel) - للمراجعة
            $this->sendSlackChannelNotification($task, $user, 'طلب جديد');

        } catch (\Exception $e) {
            Log::error('Error sending additional task application notifications', [
                'task_user_id' => $taskUser->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * إرسال إشعار Slack للقناة (HR Channel)
     */
    private function sendSlackChannelNotification(AdditionalTask $task, User $user, string $operation, ?int $points = null): void
    {
        try {
            $message = "مهمة إضافية: {$user->name} - {$task->title}";

            if ($points !== null) {
                $message .= " ({$points} نقطة)";
            }

            $additionalData = [
                'link_url' => url("/additional-tasks/{$task->id}"),
                'link_text' => 'عرض المهمة'
            ];

            $this->setHRNotificationContext('إشعار المهام الإضافية');
            $this->sendSlackNotification($message, $operation, $additionalData);

        } catch (\Exception $e) {
            Log::error('Error sending Slack channel notification for additional task', [
                'task_id' => $task->id,
                'user_id' => $user->id,
                'operation' => $operation,
                'error' => $e->getMessage()
            ]);
        }
    }
}
