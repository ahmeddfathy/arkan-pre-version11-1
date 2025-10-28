<?php

namespace App\Services\ProjectManagement;

use App\Models\User;
use App\Models\AttachmentShare;
use App\Models\ProjectAttachment;
use App\Models\Notification;
use App\Services\FirebaseNotificationService;
use App\Services\Slack\ProjectSlackService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AttachmentSharingNotificationService
{
    protected $firebaseService;
    protected $slackService;

    public function __construct(
        FirebaseNotificationService $firebaseService,
        ProjectSlackService $slackService
    ) {
        $this->firebaseService = $firebaseService;
        $this->slackService = $slackService;
    }

    /**
     * إرسال إشعار عند مشاركة ملف جديد
     */
    public function notifyFileShared(AttachmentShare $share): void
    {
        try {
            Log::info('Sending file share notifications', [
                'share_id' => $share->id,
                'attachment_id' => $share->attachment_id,
                'shared_by' => $share->shared_by,
                'shared_with_count' => count($share->shared_with)
            ]);

            $sharedBy = $share->sharedBy;
            $attachment = $share->attachment;

            if (!$sharedBy || !$attachment) {
                Log::warning('Missing sharedBy user or attachment', [
                    'share_id' => $share->id,
                    'shared_by' => $share->shared_by,
                    'attachment_id' => $share->attachment_id
                ]);
                return;
            }

            // إرسال إشعار لكل شخص تم مشاركة الملف معه
            foreach ($share->shared_with as $userId) {
                $user = User::find($userId);
                if (!$user) {
                    Log::warning('User not found for file sharing notification', [
                        'user_id' => $userId,
                        'share_id' => $share->id
                    ]);
                    continue;
                }

                                // إنشاء إشعار في قاعدة البيانات
                $this->createFileSharedNotification($share, $user, $sharedBy, $attachment);

                // إرسال Firebase notification (منفصل وآمن)
                if ($user->fcm_token) {
                    try {
                        $this->sendFirebaseNotificationSafe($share, $user, $sharedBy, $attachment);
                    } catch (\Exception $e) {
                        Log::error('Firebase notification failed for file share', [
                            'error' => $e->getMessage(),
                            'user_id' => $user->id,
                            'share_id' => $share->id
                        ]);
                    }
                } else {
                    Log::info('User does not have FCM token, skipping Firebase notification', [
                        'user_id' => $user->id,
                        'share_id' => $share->id
                    ]);
                }

                                // إرسال إشعار Slack (منفصل وآمن تماماً)
                if ($user->slack_user_id) {
                    try {
                        $this->sendSlackNotificationSafe($share, $user, $sharedBy);
                    } catch (\Exception $e) {
                        Log::error('Critical Slack notification error for file share', [
                            'error' => $e->getMessage(),
                            'user_id' => $user->id,
                            'share_id' => $share->id
                        ]);
                        // نكمل العملية حتى لو فشل Slack
                    }
                } else {
                    Log::info('User does not have Slack user ID, skipping Slack notification', [
                        'user_id' => $user->id,
                        'share_id' => $share->id
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('Error sending file share notifications', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'share_id' => $share->id ?? 'unknown'
            ]);
        }
    }

    /**
     * إرسال إشعار عند الوصول للملف المشارك
     */
    public function notifyFileAccessed(AttachmentShare $share, User $accessedBy): void
    {
        try {
            Log::info('Sending file access notification', [
                'share_id' => $share->id,
                'accessed_by' => $accessedBy->id,
                'shared_by' => $share->shared_by
            ]);

            $sharedBy = $share->sharedBy;
            $attachment = $share->attachment;

            if (!$sharedBy || !$attachment) {
                return;
            }

            // إرسال إشعار للشخص الذي شارك الملف أصلاً
            if ($sharedBy->id !== $accessedBy->id) {
                $this->createFileAccessedNotification($share, $sharedBy, $accessedBy, $attachment);
            }

        } catch (\Exception $e) {
            Log::error('Error sending file access notification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'share_id' => $share->id ?? 'unknown',
                'accessed_by' => $accessedBy->id ?? 'unknown'
            ]);
        }
    }

    /**
     * إرسال إشعار عند انتهاء صلاحية المشاركة
     */
    public function notifyShareExpired(AttachmentShare $share): void
    {
        try {
            Log::info('Sending share expiration notifications', [
                'share_id' => $share->id,
                'expired_at' => $share->expires_at
            ]);

            $sharedBy = $share->sharedBy;
            $attachment = $share->attachment;

            if (!$sharedBy || !$attachment) {
                return;
            }

            // إشعار للشخص الذي شارك الملف
            $this->createShareExpiredNotification($share, $sharedBy, $attachment, 'owner');

            // إشعار للأشخاص الذين تم مشاركة الملف معهم
            foreach ($share->shared_with as $userId) {
                $user = User::find($userId);
                if ($user && $user->id !== $sharedBy->id) {
                    $this->createShareExpiredNotification($share, $user, $attachment, 'recipient');
                }
            }

        } catch (\Exception $e) {
            Log::error('Error sending share expiration notifications', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'share_id' => $share->id ?? 'unknown'
            ]);
        }
    }

    /**
     * إرسال إشعار عند إلغاء المشاركة
     */
    public function notifyShareCancelled(AttachmentShare $share, User $cancelledBy): void
    {
        try {
            Log::info('Sending share cancellation notifications', [
                'share_id' => $share->id,
                'cancelled_by' => $cancelledBy->id
            ]);

            $sharedBy = $share->sharedBy;
            $attachment = $share->attachment;

            if (!$sharedBy || !$attachment) {
                return;
            }

            // إشعار للأشخاص الذين تم مشاركة الملف معهم (إذا لم يكن الملغي هو نفس الشخص الذي شارك)
            if ($cancelledBy->id !== $sharedBy->id) {
                foreach ($share->shared_with as $userId) {
                    $user = User::find($userId);
                    if ($user && $user->id !== $cancelledBy->id) {
                        $this->createShareCancelledNotification($share, $user, $cancelledBy, $attachment);
                    }
                }
            } else {
                // إذا الشخص الذي شارك الملف هو من ألغى المشاركة، إشعار للمستقبلين
                foreach ($share->shared_with as $userId) {
                    $user = User::find($userId);
                    if ($user) {
                        $this->createShareCancelledNotification($share, $user, $cancelledBy, $attachment);
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error('Error sending share cancellation notifications', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'share_id' => $share->id ?? 'unknown'
            ]);
        }
    }

    /**
     * إنشاء إشعار مشاركة ملف جديد
     */
    private function createFileSharedNotification(AttachmentShare $share, User $recipient, User $sharedBy, ProjectAttachment $attachment): void
    {
        try {
            $message = "{$sharedBy->name} شارك معك ملف: {$attachment->original_name}";

            $expirationText = '';
            if ($share->expires_at) {
                $expirationText = " (ينتهي في: " . $share->expires_at->format('Y-m-d H:i') . ")";
            }

            $notification = Notification::create([
                'user_id' => $recipient->id,
                'type' => 'file_shared',
                'data' => [
                    'message' => $message . $expirationText,
                    'share_id' => $share->id,
                    'attachment_id' => $attachment->id,
                    'attachment_name' => $attachment->original_name,
                    'shared_by_id' => $sharedBy->id,
                    'shared_by_name' => $sharedBy->name,
                    'project_id' => $attachment->project_id,
                    'expires_at' => $share->expires_at?->toISOString(),
                    'description' => $share->description,
                    'access_token' => $share->access_token
                ],
                'related_id' => $share->id
            ]);

            Log::info('Created file shared notification', [
                'notification_id' => $notification->id,
                'recipient_id' => $recipient->id,
                'share_id' => $share->id
            ]);

        } catch (\Exception $e) {
            Log::error('Error creating file shared notification', [
                'error' => $e->getMessage(),
                'recipient_id' => $recipient->id,
                'share_id' => $share->id
            ]);
        }
    }

    /**
     * إنشاء إشعار الوصول للملف
     */
    private function createFileAccessedNotification(AttachmentShare $share, User $owner, User $accessedBy, ProjectAttachment $attachment): void
    {
        try {
            $message = "{$accessedBy->name} وصل إلى الملف المشارك: {$attachment->original_name}";

            $notification = Notification::create([
                'user_id' => $owner->id,
                'type' => 'file_accessed',
                'data' => [
                    'message' => $message,
                    'share_id' => $share->id,
                    'attachment_id' => $attachment->id,
                    'attachment_name' => $attachment->original_name,
                    'accessed_by_id' => $accessedBy->id,
                    'accessed_by_name' => $accessedBy->name,
                    'project_id' => $attachment->project_id,
                    'access_time' => Carbon::now()->toISOString(),
                    'view_count' => $share->view_count
                ],
                'related_id' => $share->id
            ]);

            Log::info('Created file accessed notification', [
                'notification_id' => $notification->id,
                'owner_id' => $owner->id,
                'accessed_by_id' => $accessedBy->id,
                'share_id' => $share->id
            ]);

            // إرسال إشعار Firebase
            $this->sendFirebaseNotification(
                $owner,
                'تم الوصول للملف المشارك',
                $message,
                'file_accessed',
                $share->access_token
            );

        } catch (\Exception $e) {
            Log::error('Error creating file accessed notification', [
                'error' => $e->getMessage(),
                'owner_id' => $owner->id,
                'share_id' => $share->id
            ]);
        }
    }

    /**
     * إنشاء إشعار انتهاء صلاحية المشاركة
     */
    private function createShareExpiredNotification(AttachmentShare $share, User $user, ProjectAttachment $attachment, string $userType): void
    {
        try {
            $message = $userType === 'owner'
                ? "انتهت صلاحية مشاركة الملف: {$attachment->original_name}"
                : "انتهت صلاحية الملف المشارك معك: {$attachment->original_name}";

            $notification = Notification::create([
                'user_id' => $user->id,
                'type' => 'share_expired',
                'data' => [
                    'message' => $message,
                    'share_id' => $share->id,
                    'attachment_id' => $attachment->id,
                    'attachment_name' => $attachment->original_name,
                    'project_id' => $attachment->project_id,
                    'expired_at' => $share->expires_at?->toISOString(),
                    'user_type' => $userType,
                    'view_count' => $share->view_count
                ],
                'related_id' => $share->id
            ]);

            Log::info('Created share expired notification', [
                'notification_id' => $notification->id,
                'user_id' => $user->id,
                'user_type' => $userType,
                'share_id' => $share->id
            ]);

            // إرسال إشعار Firebase
            $this->sendFirebaseNotification(
                $user,
                'انتهت صلاحية المشاركة',
                $message,
                'share_expired'
            );

        } catch (\Exception $e) {
            Log::error('Error creating share expired notification', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'share_id' => $share->id
            ]);
        }
    }

    /**
     * إنشاء إشعار إلغاء المشاركة
     */
    private function createShareCancelledNotification(AttachmentShare $share, User $user, User $cancelledBy, ProjectAttachment $attachment): void
    {
        try {
            $message = "{$cancelledBy->name} ألغى مشاركة الملف: {$attachment->original_name}";

            $notification = Notification::create([
                'user_id' => $user->id,
                'type' => 'share_cancelled',
                'data' => [
                    'message' => $message,
                    'share_id' => $share->id,
                    'attachment_id' => $attachment->id,
                    'attachment_name' => $attachment->original_name,
                    'cancelled_by_id' => $cancelledBy->id,
                    'cancelled_by_name' => $cancelledBy->name,
                    'project_id' => $attachment->project_id,
                    'cancelled_at' => Carbon::now()->toISOString()
                ],
                'related_id' => $share->id
            ]);

            Log::info('Created share cancelled notification', [
                'notification_id' => $notification->id,
                'user_id' => $user->id,
                'cancelled_by_id' => $cancelledBy->id,
                'share_id' => $share->id
            ]);

            // إرسال إشعار Firebase
            $this->sendFirebaseNotification(
                $user,
                'تم إلغاء مشاركة الملف',
                $message,
                'share_cancelled'
            );

        } catch (\Exception $e) {
            Log::error('Error creating share cancelled notification', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'share_id' => $share->id
            ]);
        }
    }

    /**
     * إرسال إشعار Firebase
     */
    private function sendFirebaseNotification(User $user, string $title, string $message, string $type, string $token = null): void
    {
        try {
            if (!$user->fcm_token) {
                Log::info('User does not have FCM token', ['user_id' => $user->id]);
                return;
            }

            $link = $this->getNotificationLink($type, $token);

            Log::info('Sending Firebase notification for file sharing', [
                'user_id' => $user->id,
                'title' => $title,
                'type' => $type,
                'link' => $link
            ]);

            $this->firebaseService->sendNotification(
                $user->fcm_token,
                $title,
                $message,
                $link
            );

        } catch (\Exception $e) {
            Log::error('Error sending Firebase notification for file sharing', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'type' => $type
            ]);
        }
    }

    /**
     * الحصول على الرابط المناسب للإشعار
     */
    private function getNotificationLink(string $type, string $token = null): string
    {
        switch ($type) {
            case 'file_shared':
                return $token ? "/shared-attachments/{$token}" : "/dashboard";
            case 'file_accessed':
            case 'share_expired':
            case 'share_cancelled':
                return "/projects"; // أو رابط صفحة المشاريع
            default:
                return "/dashboard";
        }
    }

    /**
     * إرسال تذكير قبل انتهاء صلاحية المشاركة
     */
    public function sendExpirationReminders(): void
    {
        try {
            Log::info('Checking for shares expiring soon');

            // البحث عن المشاركات التي تنتهي في الـ 24 ساعة القادمة
            $expiringShares = AttachmentShare::where('is_active', true)
                ->where('expires_at', '>', Carbon::now())
                ->where('expires_at', '<=', Carbon::now()->addDay())
                ->whereDoesntHave('notifications', function($query) {
                    $query->where('type', 'share_expiration_reminder')
                          ->where('created_at', '>=', Carbon::now()->subDay());
                })
                ->with(['sharedBy', 'attachment'])
                ->get();

            Log::info('Found expiring shares', ['count' => $expiringShares->count()]);

            foreach ($expiringShares as $share) {
                $this->sendExpirationReminder($share);
            }

        } catch (\Exception $e) {
            Log::error('Error sending expiration reminders', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * إرسال تذكير انتهاء صلاحية لمشاركة واحدة
     */
    private function sendExpirationReminder(AttachmentShare $share): void
    {
        try {
            $sharedBy = $share->sharedBy;
            $attachment = $share->attachment;

            if (!$sharedBy || !$attachment) {
                return;
            }

            $timeLeft = $share->expires_at->diffForHumans();
            $message = "سينتهي الملف المشارك '{$attachment->original_name}' خلال {$timeLeft}";

            // إشعار للشخص الذي شارك الملف
            Notification::create([
                'user_id' => $sharedBy->id,
                'type' => 'share_expiration_reminder',
                'data' => [
                    'message' => $message,
                    'share_id' => $share->id,
                    'attachment_id' => $attachment->id,
                    'attachment_name' => $attachment->original_name,
                    'expires_at' => $share->expires_at->toISOString(),
                    'time_left' => $timeLeft
                ],
                'related_id' => $share->id
            ]);

            // إشعار للمستقبلين
            foreach ($share->shared_with as $userId) {
                $user = User::find($userId);
                if ($user && $user->id !== $sharedBy->id) {
                    Notification::create([
                        'user_id' => $user->id,
                        'type' => 'share_expiration_reminder',
                        'data' => [
                            'message' => $message,
                            'share_id' => $share->id,
                            'attachment_id' => $attachment->id,
                            'attachment_name' => $attachment->original_name,
                            'expires_at' => $share->expires_at->toISOString(),
                            'time_left' => $timeLeft
                        ],
                        'related_id' => $share->id
                    ]);

                    // إرسال Firebase للمستقبل
                    $this->sendFirebaseNotification(
                        $user,
                        'تذكير انتهاء المشاركة',
                        $message,
                        'share_expiration_reminder',
                        $share->access_token
                    );
                }
            }

            // إرسال Firebase للشخص الذي شارك
            $this->sendFirebaseNotification(
                $sharedBy,
                'تذكير انتهاء المشاركة',
                $message,
                'share_expiration_reminder'
            );

            Log::info('Sent expiration reminder', [
                'share_id' => $share->id,
                'expires_at' => $share->expires_at,
                'shared_with_count' => count($share->shared_with)
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending expiration reminder', [
                'error' => $e->getMessage(),
                'share_id' => $share->id
            ]);
        }
    }

    /**
     * إرسال إشعار Firebase بشكل آمن
     */
    private function sendFirebaseNotificationSafe(AttachmentShare $share, User $user, User $sharedBy, ProjectAttachment $attachment): void
    {
        try {
            $title = 'ملف مشارك جديد';
            $body = "{$sharedBy->name} شارك معك ملف: {$attachment->original_name}";
            $link = "/shared-attachments/{$share->access_token}";

            Log::info('Sending Firebase notification for file sharing', [
                'user_id' => $user->id,
                'title' => $title,
                'type' => 'file_shared',
                'link' => $link
            ]);

            $result = $this->firebaseService->sendNotification(
                $user->fcm_token,
                $title,
                $body,
                $link
            );

            if ($result && $result['success']) {
                Log::info('Firebase notification sent successfully for file share', [
                    'user_id' => $user->id,
                    'share_id' => $share->id,
                    'result' => $result['message']
                ]);
            } else {
                Log::warning('Firebase notification failed for file share', [
                    'user_id' => $user->id,
                    'share_id' => $share->id,
                    'result' => $result['message'] ?? 'Unknown error'
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Exception in Firebase notification for file share', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id,
                'share_id' => $share->id
            ]);
        }
    }

    /**
     * إرسال إشعار Slack بشكل آمن مع retry logic قوي
     */
    private function sendSlackNotificationSafe(AttachmentShare $share, User $user, User $sharedBy): void
    {
        try {
            Log::info('Starting safe Slack notification process', [
                'user_id' => $user->id,
                'slack_user_id' => $user->slack_user_id,
                'share_id' => $share->id,
                'bot_token_configured' => !empty(env('SLACK_BOT_TOKEN'))
            ]);

            // محاولة أولى: إرسال عادي
            $attempt = 1;
            $maxAttempts = 3;
            $result = false;

            for ($i = 0; $i < $maxAttempts; $i++) {
                try {
                    Log::info("Slack notification attempt {$attempt} of {$maxAttempts}", [
                        'user_id' => $user->id,
                        'share_id' => $share->id,
                        'attempt' => $attempt
                    ]);

                    $result = $this->slackService->sendFileShareNotification($share, $user, $sharedBy);

                    if ($result) {
                        Log::info('Slack notification sent successfully', [
                            'user_id' => $user->id,
                            'share_id' => $share->id,
                            'attempt' => $attempt
                        ]);
                        break; // نجح، اخرج من اللوب
                    } else {
                        Log::warning("Slack notification failed on attempt {$attempt}", [
                            'user_id' => $user->id,
                            'share_id' => $share->id,
                            'attempt' => $attempt,
                            'remaining_attempts' => $maxAttempts - $attempt
                        ]);
                    }

                } catch (\Illuminate\Http\Client\ConnectionException $e) {
                    Log::warning("Slack connection timeout on attempt {$attempt}", [
                        'error' => $e->getMessage(),
                        'user_id' => $user->id,
                        'share_id' => $share->id,
                        'attempt' => $attempt
                    ]);
                } catch (\Exception $e) {
                    Log::warning("Slack exception on attempt {$attempt}", [
                        'error' => $e->getMessage(),
                        'user_id' => $user->id,
                        'share_id' => $share->id,
                        'attempt' => $attempt
                    ]);
                }

                $attempt++;

                // انتظار قبل المحاولة التالية (exponential backoff)
                if ($i < $maxAttempts - 1) {
                    $waitTime = pow(2, $i) * 100000; // 0.1s, 0.2s, 0.4s
                    usleep($waitTime);
                }
            }

            // تسجيل النتيجة النهائية
            if (!$result) {
                Log::error('All Slack notification attempts failed', [
                    'user_id' => $user->id,
                    'share_id' => $share->id,
                    'total_attempts' => $maxAttempts
                ]);
            }

        } catch (\Exception $e) {
            // خطأ شامل - لا نريد أن نوقف العملية الأساسية
            Log::error('Critical error in safe Slack notification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id,
                'share_id' => $share->id
            ]);
        }
    }
}
