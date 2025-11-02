<?php

namespace App\Services\Notifications;

use App\Models\AdditionalTask;
use App\Models\AdditionalTaskUser;
use App\Models\User;
use App\Models\Notification;
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

    public function notifyUserAssigned(AdditionalTaskUser $taskUser): void
    {
        try {
            $user = $taskUser->user;
            $task = $taskUser->additionalTask;

            if (!$user || !$task) {
                return;
            }

            $message = "تم تعيين مهمة إضافية جديدة لك: {$task->title}";

            $this->sendTypedFirebaseNotification(
                $user,
                'additional-task',
                'assigned',
                $message,
                $task->id
            );

            $this->sendDatabaseNotification(
                $user,
                'additional-task-assigned',
                $message,
                $task->id,
                [
                    'additional_task_id' => $task->id,
                    'additional_task_title' => $task->title,
                    'points' => $task->points,
                ]
            );

            if ($user->slack_user_id) {
                $this->additionalTaskSlackService->sendAdditionalTaskNotification(
                    $task,
                    $user,
                    $task->creator,
                    'assigned'
                );
            }

            $this->sendSlackChannelNotification($task, $user, 'تعيين');
        } catch (\Exception $e) {
            Log::error('Error sending additional task assignment notifications', [
                'task_user_id' => $taskUser->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function notifyUserApproved(AdditionalTaskUser $taskUser, ?\App\Models\Task $regularTask = null): void
    {
        try {
            $user = $taskUser->user;
            $task = $taskUser->additionalTask;

            if (!$user || !$task) {
                return;
            }

            if ($regularTask) {
                $message = "✅ تمت الموافقة على طلبك للمهمة الإضافية: {$task->title}\n📋 تم إنشاء المهمة في قائمة التاسكات العادية الخاصة بك";
            } else {
                $message = "✅ تمت الموافقة على طلبك للمهمة الإضافية: {$task->title}";
            }

            $this->sendTypedFirebaseNotification(
                $user,
                'additional-task',
                'approved',
                $message,
                $task->id
            );

            $this->sendDatabaseNotification(
                $user,
                'additional-task-approved',
                $message,
                $task->id,
                [
                    'additional_task_id' => $task->id,
                    'additional_task_title' => $task->title,
                    'regular_task_id' => $regularTask?->id,
                    'regular_task_name' => $regularTask?->name,
                    'points' => $task->points,
                ]
            );

            if ($user->slack_user_id) {
                $this->additionalTaskSlackService->sendAdditionalTaskNotification(
                    $task,
                    $user,
                    \Illuminate\Support\Facades\Auth::user() ?? $task->creator,
                    'approved'
                );
            }

            $this->sendSlackChannelNotification($task, $user, 'موافقة');
        } catch (\Exception $e) {
            Log::error('Error sending additional task approval notifications', [
                'task_user_id' => $taskUser->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function notifyUserRejected(AdditionalTaskUser $taskUser): void
    {
        try {
            $user = $taskUser->user;
            $task = $taskUser->additionalTask;

            if (!$user || !$task) {
                return;
            }

            $message = "تم رفض طلبك للمهمة الإضافية: {$task->title}";

            $this->sendTypedFirebaseNotification(
                $user,
                'additional-task',
                'rejected',
                $message,
                $task->id
            );

            $this->sendDatabaseNotification(
                $user,
                'additional-task-rejected',
                $message,
                $task->id,
                [
                    'additional_task_id' => $task->id,
                    'additional_task_title' => $task->title,
                    'admin_notes' => $taskUser->admin_notes,
                ]
            );

            if ($user->slack_user_id) {
                $this->additionalTaskSlackService->sendAdditionalTaskNotification(
                    $task,
                    $user,
                    \Illuminate\Support\Facades\Auth::user() ?? $task->creator,
                    'rejected'
                );
            }

            $this->sendSlackChannelNotification($task, $user, 'رفض');
        } catch (\Exception $e) {
            Log::error('Error sending additional task rejection notifications', [
                'task_user_id' => $taskUser->id,
                'error' => $e->getMessage()
            ]);
        }
    }

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

            $this->sendTypedFirebaseNotification(
                $user,
                'additional-task',
                'completed',
                $message,
                $task->id
            );

            $this->sendDatabaseNotification(
                $user,
                'additional-task-completed',
                $message,
                $task->id,
                [
                    'additional_task_id' => $task->id,
                    'additional_task_title' => $task->title,
                    'points_earned' => $pointsEarned,
                ]
            );

            if ($user->slack_user_id) {
                $this->additionalTaskSlackService->sendAdditionalTaskNotification(
                    $task,
                    $user,
                    \Illuminate\Support\Facades\Auth::user() ?? $task->creator,
                    'completed'
                );
            }

            $this->sendSlackChannelNotification($task, $user, 'إكمال', $pointsEarned);
        } catch (\Exception $e) {
            Log::error('Error sending additional task completion notifications', [
                'task_user_id' => $taskUser->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function notifyUserApplied(AdditionalTaskUser $taskUser): void
    {
        try {
            $user = $taskUser->user;
            $task = $taskUser->additionalTask;

            if (!$user || !$task) {
                return;
            }

            $message = "تم تقديم طلبك للمهمة الإضافية: {$task->title}. في انتظار الموافقة.";

            $this->sendTypedFirebaseNotification(
                $user,
                'additional-task',
                'applied',
                $message,
                $task->id
            );

            $this->sendDatabaseNotification(
                $user,
                'additional-task-applied',
                $message,
                $task->id,
                [
                    'additional_task_id' => $task->id,
                    'additional_task_title' => $task->title,
                    'points' => $task->points,
                    'user_notes' => $taskUser->user_notes,
                ]
            );

            $this->sendSlackChannelNotification($task, $user, 'طلب جديد');
        } catch (\Exception $e) {
            Log::error('Error sending additional task application notifications', [
                'task_user_id' => $taskUser->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function notifyEligibleUsers(AdditionalTask $task): array
    {
        try {
            $eligibleUsers = $this->getEligibleUsers($task);

            if ($eligibleUsers->isEmpty()) {
                Log::info('No eligible users found for task notification', [
                    'task_id' => $task->id,
                    'task_title' => $task->title
                ]);

                return [
                    'success' => true,
                    'notified_count' => 0,
                    'message' => 'لا يوجد مستخدمين مؤهلين'
                ];
            }

            $notifiedCount = 0;

            foreach ($eligibleUsers as $user) {
                try {
                    $message = "مهمة إضافية جديدة متاحة للتقديم: {$task->title} ({$task->points} نقطة)";

                    $this->sendTypedFirebaseNotification(
                        $user,
                        'additional-task',
                        'available',
                        $message,
                        $task->id
                    );

                    $this->sendDatabaseNotification(
                        $user,
                        'additional-task-available',
                        $message,
                        $task->id,
                        [
                            'additional_task_id' => $task->id,
                            'additional_task_title' => $task->title,
                            'points' => $task->points,
                        ]
                    );

                    $notifiedCount++;
                } catch (\Exception $e) {
                    Log::error('Failed to notify user about new task', [
                        'user_id' => $user->id,
                        'task_id' => $task->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            if ($task->creator) {
                $this->sendSlackChannelNotification($task, $task->creator, 'إنشاء مهمة جديدة');
            }

            Log::info('Task notifications sent successfully', [
                'task_id' => $task->id,
                'task_title' => $task->title,
                'notified_count' => $notifiedCount
            ]);

            return [
                'success' => true,
                'notified_count' => $notifiedCount,
                'message' => "تم إرسال الإشعارات لـ {$notifiedCount} مستخدم"
            ];
        } catch (\Exception $e) {
            Log::error('Failed to send task notifications', [
                'task_id' => $task->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'notified_count' => 0,
                'message' => 'فشل إرسال الإشعارات: ' . $e->getMessage()
            ];
        }
    }

    private function getEligibleUsers(AdditionalTask $task)
    {
        if ($task->target_type === 'all') {
            return User::where('employee_status', 'active')->get();
        } elseif ($task->target_type === 'department') {
            return User::where('department', $task->target_department)
                ->where('employee_status', 'active')
                ->get();
        }

        return collect();
    }

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


    private function sendDatabaseNotification(User $user, string $type, string $message, int $relatedId, array $data = []): void
    {
        try {
            $notificationData = array_merge([
                'message' => $message,
                'notification_time' => now()->format('Y-m-d H:i:s'),
            ], $data);

            Notification::create([
                'user_id' => $user->id,
                'type' => $type,
                'data' => $notificationData,
                'related_id' => $relatedId,
                'read_at' => null,
            ]);

            Log::info('Database notification created for additional task', [
                'user_id' => $user->id,
                'type' => $type,
                'related_id' => $relatedId,
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating database notification', [
                'user_id' => $user->id,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
