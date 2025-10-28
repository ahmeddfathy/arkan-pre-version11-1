<?php

namespace App\Services\ProjectManagement;

use App\Models\Project;
use App\Models\User;
use App\Services\ProjectManagement\AttachmentSharingService;
use App\Services\ProjectManagement\AttachmentService;
use App\Services\Auth\RoleCheckService;
use Illuminate\Support\Facades\Auth;

class ProjectAttachmentSharingHandlerService
{
    protected $attachmentSharingService;
    protected $attachmentService;
    protected $roleCheckService;

    public function __construct(
        AttachmentSharingService $attachmentSharingService,
        AttachmentService $attachmentService,
        RoleCheckService $roleCheckService
    ) {
        $this->attachmentSharingService = $attachmentSharingService;
        $this->attachmentService = $attachmentService;
        $this->roleCheckService = $roleCheckService;
    }

    /**
     * جلب المستخدمين المتاحين للمشاركة
     */
    public function getAvailableUsersForSharing(Project $project)
    {
        // جلب أعضاء المشروع
        $projectUsers = $project->participants()
            ->select('users.id', 'users.name', 'users.email', 'users.department')
            ->get();

        // جلب جميع المستخدمين (للمديرين)
        $user = Auth::user();
        $isAdmin = $this->roleCheckService->userHasRole(['hr', 'company_manager', 'project_manager']);

        if ($isAdmin) {
            $allUsers = User::select('id', 'name', 'email', 'department')
                          ->where('id', '!=', $user->id)
                          ->orderBy('name')
                          ->get();
        } else {
            $allUsers = $projectUsers;
        }

        return [
            'project_users' => $projectUsers,
            'all_users' => $allUsers,
            'can_share_with_all' => $isAdmin
        ];
    }

    /**
     * عرض المرفقات المشاركة بواسطة رمز الوصول
     */
    public function handleViewSharedAttachments($token, $isAjax = false)
    {
        $user = Auth::user();
        $result = $this->attachmentSharingService->getShareByToken($token, $user ? $user->id : null);

        if (!$result['success']) {
            if ($isAjax) {
                return [
                    'success' => false,
                    'message' => $result['message'],
                    'status_code' => 403
                ];
            }

            return [
                'success' => false,
                'view' => 'errors.403',
                'data' => ['message' => $result['message']]
            ];
        }

        if ($isAjax) {
            return [
                'success' => true,
                'data' => [
                    'attachments' => $result['attachments'],
                    'shared_by' => $result['shared_by'],
                    'expires_at' => $result['expires_at']
                ]
            ];
        }

        return [
            'success' => true,
            'view' => 'projects.shared-attachments',
            'data' => [
                'attachments' => $result['attachments'],
                'shared_by' => $result['shared_by'],
                'expires_at' => $result['expires_at'],
                'access_token' => $token
            ]
        ];
    }

    /**
     * تحميل مرفق مشارك
     */
    public function handleDownloadSharedAttachment($token, $attachmentId)
    {
        $user = Auth::user();
        $shareResult = $this->attachmentSharingService->getShareByToken($token, $user ? $user->id : null);

        if (!$shareResult['success']) {
            throw new \Exception($shareResult['message']);
        }

        // التحقق من أن المرفق جزء من المشاركة
        $attachment = $shareResult['attachments']->where('id', $attachmentId)->first();
        if (!$attachment) {
            throw new \Exception('المرفق غير موجود في هذه المشاركة');
        }

        // استخدام AttachmentService للحصول على رابط التحميل
        $presignedUrl = $this->attachmentService->getDownloadUrl($attachmentId);

        if (!$presignedUrl) {
            throw new \Exception('الملف غير موجود');
        }

        return $presignedUrl;
    }

    /**
     * عرض صفحة الملفات المشاركة
     */
    public function getAttachmentSharesIndex($type = 'received')
    {
        $user = Auth::user();

        // جلب المشاركات المرسلة أو المستقبلة
        $sentShares = $this->attachmentSharingService->getUserShares($user->id, 'sent');
        $receivedShares = $this->attachmentSharingService->getUserShares($user->id, 'received');

        // جلب الإحصائيات
        $statistics = $this->attachmentSharingService->getShareStatistics($user->id);

        return [
            'sentShares' => $sentShares,
            'receivedShares' => $receivedShares,
            'currentType' => $type,
            'statistics' => $statistics
        ];
    }

    /**
     * جلب مشاركات المستخدم
     */
    public function getUserShares($type = 'sent')
    {
        $shares = $this->attachmentSharingService->getUserShares(Auth::id(), $type);

        return [
            'success' => true,
            'shares' => $shares
        ];
    }

    /**
     * جلب مشاركات مرفق معين
     */
    public function getAttachmentShares($attachmentId)
    {
        $shares = $this->attachmentSharingService->getAttachmentShares($attachmentId);

        return [
            'success' => true,
            'shares' => $shares
        ];
    }

    /**
     * إحصائيات المشاركات
     */
    public function getShareStatistics()
    {
        $stats = $this->attachmentSharingService->getShareStatistics(Auth::id());

        return [
            'success' => true,
            'statistics' => $stats
        ];
    }

    /**
     * إلغاء مشاركة
     */
    public function cancelShare($shareId)
    {
        return $this->attachmentSharingService->cancelShare($shareId, Auth::id());
    }

    /**
     * إلغاء مشاركة بواسطة رمز الوصول
     */
    public function cancelShareByToken($token)
    {
        return $this->attachmentSharingService->cancelSharesByToken($token, Auth::id());
    }
}
