<?php

namespace App\Services\Notifications\Traits;

use App\Models\User;
use App\Services\FirebaseNotificationService;
use Illuminate\Support\Facades\Log;

trait HasFirebaseNotification
{
    protected function sendAdditionalFirebaseNotification(User $user, string $message, string $title = 'إشعار جديد', ?string $link = null, ?string $type = null): ?array
    {
        try {
            // 🚀 Smart Check: تحقق سريع من FCM token قبل أي معالجة
            if (!$user || empty($user->fcm_token)) {
                return [
                    'success' => true,
                    'message' => 'No FCM token - notification skipped',
                    'queued' => false
                ];
            }

            $firebaseService = app(FirebaseNotificationService::class);

            if (!$link) {
                $link = '/dashboard';
                if ($user->role === 'employee') {
                    $link = '/employee/dashboard';
                } elseif ($user->role === 'admin' || $user->role === 'manager') {
                    $link = '/admin/dashboard';
                }

                if ($type) {
                    $link .= "?notification_type={$type}";
                }
            }

            // استخدام Firebase queue بدلاً من الإرسال المباشر
            $result = $firebaseService->sendNotificationQueued(
                $user->fcm_token,
                $title,
                $message,
                $link
            );

            if ($result['success']) {
                Log::info('Firebase notification queued successfully via trait', [
                    'user_id' => $user->id,
                    'title' => $title,
                    'queued' => $result['queued']
                ]);
            } else {
                Log::warning('Firebase notification queue failed in trait, trying direct send', [
                    'user_id' => $user->id,
                    'title' => $title,
                    'error' => $result['message']
                ]);

                // Fallback للإرسال المباشر في حالة فشل الـ queue
                $result = $firebaseService->sendNotification(
                    $user->fcm_token,
                    $title,
                    $message,
                    $link
                );
            }

            if (method_exists($this, 'storeFirebaseNotificationLog')) {
                $this->storeFirebaseNotificationLog($user, $title, $message, $result, $type);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Firebase notification error in trait', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user?->id,
                'title' => $title,
                'message' => $message
            ]);

            // Fallback للإرسال المباشر في حالة الأخطاء
            try {
                $firebaseService = app(FirebaseNotificationService::class);
                return $firebaseService->sendNotification(
                    $user->fcm_token,
                    $title,
                    $message,
                    $link ?? '/dashboard'
                );
            } catch (\Exception $fallbackError) {
                Log::error('Firebase fallback notification also failed in trait', [
                    'error' => $fallbackError->getMessage(),
                    'user_id' => $user?->id,
                    'title' => $title
                ]);

                return [
                    'success' => false,
                    'message' => 'Firebase error - notification failed: ' . $e->getMessage()
                ];
            }
        }
    }

    protected function sendTypedFirebaseNotification(User $user, string $requestType, string $actionType, string $message, int $requestId): ?array
    {
        try {
            $title = 'إشعار جديد';

            switch ($actionType) {
                case 'created': $title = 'طلب جديد'; break;
                case 'updated': $title = 'تم تحديث الطلب'; break;
                case 'approved': $title = 'تمت الموافقة'; break;
                case 'rejected': $title = 'تم الرفض'; break;
                case 'deleted': $title = 'تم حذف الطلب'; break;
                case 'reset': $title = 'تم إعادة تعيين الطلب'; break;
            }

            // استخدام الروابط الموحدة الجديدة
            $link = '/dashboard';
            switch ($requestType) {
                case 'absence':
                case 'absence-requests':
                    $link = "/absence-requests/{$requestId}";
                    break;
                case 'overtime':
                case 'overtime-requests':
                    $link = "/overtime-requests/{$requestId}";
                    break;
                case 'permission':
                case 'permission-requests':
                    $link = "/permission-requests/{$requestId}";
                    break;
                case 'additional-task':
                case 'additional-tasks':
                    $link = "/additional-tasks/{$requestId}";
                    break;
                case 'ticket':
                case 'client-tickets':
                    $link = "/client-tickets/{$requestId}";
                    break;
                case 'meeting':
                case 'meeting_created':
                case 'meeting_mention':
                case 'meeting_note_mention':
                case 'meeting_approval_request':
                case 'meeting_approval_result':
                case 'meeting_time_updated':
                    $link = "/meetings/{$requestId}";
                    break;
                default:
                    $link = "/dashboard";
            }

            $type = "{$requestType}_{$actionType}";

            return $this->sendAdditionalFirebaseNotification($user, $message, $title, $link, $type);
        } catch (\Exception $e) {
            return null;
        }
    }
}
