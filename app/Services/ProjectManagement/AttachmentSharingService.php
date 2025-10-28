<?php

namespace App\Services\ProjectManagement;

use App\Models\AttachmentShare;
use App\Models\ProjectAttachment;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttachmentSharingService
{
    protected $notificationService;

    public function __construct(AttachmentSharingNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    /**
     * مشاركة مرفقات متعددة مع مستخدمين متعددين
     */
    public function shareAttachments(array $attachmentIds, array $userIds, array $options = [])
    {
        try {
            DB::beginTransaction();

            $shares = [];
            $accessToken = AttachmentShare::generateAccessToken();

                        $expiresAt = null;
            if (isset($options['expires_in_hours']) && $options['expires_in_hours']) {
                $hours = (int) $options['expires_in_hours']; // تحويل إلى integer
                $expiresAt = Carbon::now()->addHours($hours);
            } elseif (isset($options['expires_at'])) {
                $expiresAt = Carbon::parse($options['expires_at']);
            }

            foreach ($attachmentIds as $attachmentId) {
                // التحقق من وجود المرفق وصلاحيات الوصول
                $attachment = ProjectAttachment::findOrFail($attachmentId);

                if (!$this->canUserShareAttachment(Auth::id(), $attachment)) {
                    throw new \Exception("غير مسموح لك بمشاركة المرفق: {$attachment->file_name}");
                }

                // إنشاء المشاركة
                $share = AttachmentShare::create([
                    'attachment_id' => $attachmentId,
                    'shared_by' => Auth::id(),
                    'shared_with' => $userIds,
                    'access_token' => $accessToken,
                    'expires_at' => $expiresAt,
                    'description' => $options['description'] ?? null,
                    'is_active' => true,
                    'view_count' => 0,
                ]);

                $shares[] = $share;
            }


            DB::commit();

        // إرسال إشعارات للمستخدمين المشارك معهم
        foreach ($shares as $share) {
            $this->notificationService->notifyFileShared($share);
        }

        return [
                'success' => true,
                'shares' => $shares,
                'access_token' => $accessToken,
                'shared_attachments_count' => count($attachmentIds),
                'shared_with_count' => count($userIds),
                'expires_at' => $expiresAt,
                'share_url' => $this->generateShareUrl($accessToken)
            ];

        } catch (\Exception $e) {
            DB::rollback();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * مشاركة مرفق واحد مع مستخدمين متعددين
     */
    public function shareAttachment($attachmentId, array $userIds, array $options = [])
    {
        return $this->shareAttachments([$attachmentId], $userIds, $options);
    }

    /**
     * الحصول على مشاركة بواسطة access token
     */
    public function getShareByToken($token, $userId = null)
    {
        $shares = AttachmentShare::where('access_token', $token)
                                ->valid()
                                ->with(['attachment', 'sharedBy'])
                                ->get();

        if ($shares->isEmpty()) {
            return [
                'success' => false,
                'message' => 'رابط المشاركة غير صالح أو منتهي الصلاحية'
            ];
        }

        // التحقق من صلاحية الوصول للمستخدم إذا تم تمرير userId
        if ($userId) {
            $hasAccess = $shares->some(function($share) use ($userId) {
                return $share->canUserAccess($userId);
            });

            if (!$hasAccess) {
                return [
                    'success' => false,
                    'message' => 'غير مسموح لك بالوصول لهذه المرفقات'
                ];
            }
        }

        // زيادة عدد المشاهدات
        foreach ($shares as $share) {
            $share->incrementViewCount();

        // إرسال إشعار الوصول للملف
        if ($userId && $userId !== $share->shared_by) {
            $accessedBy = User::find($userId);
            if ($accessedBy) {
                $this->notificationService->notifyFileAccessed($share, $accessedBy);
            }
        }
        }

        return [
            'success' => true,
            'shares' => $shares,
            'attachments' => $shares->pluck('attachment'),
            'shared_by' => $shares->first()->sharedBy,
            'expires_at' => $shares->first()->expires_at
        ];
    }

    /**
     * إلغاء مشاركة
     */
    public function cancelShare($shareId, $userId = null)
    {
        try {
            $share = AttachmentShare::findOrFail($shareId);

            // التحقق من الصلاحيات - المستخدم الذي شارك فقط يمكنه الإلغاء
            if ($userId && $share->shared_by !== $userId) {
                return [
                    'success' => false,
                    'message' => 'غير مسموح لك بإلغاء هذه المشاركة'
                ];
            }

            // إرسال إشعار إلغاء المشاركة
        $cancelledBy = $userId ? User::find($userId) : null;
        if ($cancelledBy) {
            $this->notificationService->notifyShareCancelled($share, $cancelledBy);
        }

        $share->deactivate();

            return [
                'success' => true,
                'message' => 'تم إلغاء المشاركة بنجاح'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء إلغاء المشاركة'
            ];
        }
    }

    /**
     * إلغاء جميع المشاركات لtoken معين
     */
    public function cancelSharesByToken($token, $userId = null)
    {
        try {
            $shares = AttachmentShare::where('access_token', $token)->get();

            if ($shares->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'لم يتم العثور على مشاركات لهذا الرابط'
                ];
            }

            // التحقق من الصلاحيات
            if ($userId && $shares->first()->shared_by !== $userId) {
                return [
                    'success' => false,
                    'message' => 'غير مسموح لك بإلغاء هذه المشاركة'
                ];
            }

            foreach ($shares as $share) {
                // إرسال إشعار إلغاء المشاركة
        $cancelledBy = $userId ? User::find($userId) : null;
        if ($cancelledBy) {
            $this->notificationService->notifyShareCancelled($share, $cancelledBy);
        }

        $share->deactivate();
            }

            return [
                'success' => true,
                'message' => 'تم إلغاء جميع المشاركات بنجاح',
                'cancelled_count' => $shares->count()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء إلغاء المشاركات'
            ];
        }
    }

    /**
     * جلب مشاركات المستخدم
     */
    public function getUserShares($userId, $type = 'sent')
    {
        $query = AttachmentShare::with(['attachment', 'sharedBy']);

        if ($type === 'sent') {
            $query->where('shared_by', $userId);
        } elseif ($type === 'received') {
            $query->whereJsonContains('shared_with', $userId);
        }

        return $query->orderBy('created_at', 'desc')->paginate(10);
    }

    /**
     * جلب مشاركات المرفق
     */
    public function getAttachmentShares($attachmentId)
    {
        return AttachmentShare::where('attachment_id', $attachmentId)
                              ->with(['sharedBy'])
                              ->orderBy('created_at', 'desc')
                              ->get();
    }

    /**
     * التحقق من صلاحية المستخدم لمشاركة المرفق
     */
    private function canUserShareAttachment($userId, ProjectAttachment $attachment)
    {
        // المستخدم الذي رفع الملف يمكنه مشاركته
        if ($attachment->uploaded_by === $userId) {
            return true;
        }

        // التحقق من كون المستخدم مشارك في المشروع
        $isParticipant = DB::table('project_service_user')
                          ->where('project_id', $attachment->project_id)
                          ->where('user_id', $userId)
                          ->exists();

        return $isParticipant;
    }

    /**
     * إرسال إشعارات للمستخدمين المشارك معهم
     */
    private function notifySharedUsers(array $userIds, array $shares, User $sharedBy)
    {
        // يمكن تطبيق نظام الإشعارات هنا
        // مثل إرسال إيميلات أو إشعارات داخل النظام

        foreach ($userIds as $userId) {
            // إرسال إشعار للمستخدم
            // Notification::send(User::find($userId), new FileSharedNotification($shares, $sharedBy));
        }
    }

    /**
     * إنشاء رابط المشاركة
     */
    private function generateShareUrl($token)
    {
        return url("/shared-attachments/{$token}");
    }

    /**
     * تنظيف المشاركات المنتهية الصلاحية
     */
    public function cleanupExpiredShares()
    {
        $expiredCount = AttachmentShare::expired()->update(['is_active' => false]);

        return [
            'success' => true,
            'cleaned_count' => $expiredCount
        ];
    }

    /**
     * إحصائيات المشاركات
     */
    public function getShareStatistics($userId = null)
    {
        if (!$userId) {
            return [
                'sent_count' => 0,
                'received_count' => 0,
                'total_views' => 0,
                'expired_count' => 0,
                'most_shared_attachments' => collect(),
            ];
        }

        // المشاركات المرسلة
        $sentQuery = AttachmentShare::where('shared_by', $userId);
        $sentCount = $sentQuery->count();
        $totalViews = $sentQuery->sum('view_count');
        $expiredSent = $sentQuery->expired()->count();

        // المشاركات المستقبلة
        $receivedCount = AttachmentShare::whereJsonContains('shared_with', $userId)->count();
        $expiredReceived = AttachmentShare::whereJsonContains('shared_with', $userId)->expired()->count();

        return [
            'sent_count' => $sentCount,
            'received_count' => $receivedCount,
            'total_views' => $totalViews,
            'expired_count' => $expiredSent + $expiredReceived,
            'most_shared_attachments' => $this->getMostSharedAttachments($userId),
        ];
    }

    /**
     * جلب المرفقات الأكثر مشاركة
     */
    private function getMostSharedAttachments($userId = null)
    {
        $query = AttachmentShare::select('attachment_id', DB::raw('count(*) as share_count'))
                                ->with('attachment')
                                ->groupBy('attachment_id');

        if ($userId) {
            $query->where('shared_by', $userId);
        }

        return $query->orderBy('share_count', 'desc')
                    ->limit(5)
                    ->get();
    }
}
