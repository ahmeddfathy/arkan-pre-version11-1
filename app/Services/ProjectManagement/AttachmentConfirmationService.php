<?php

namespace App\Services\ProjectManagement;

use App\Models\AttachmentConfirmation;
use App\Models\ProjectAttachment;
use App\Models\Project;
use App\Models\User;
use App\Models\Notification;
use App\Models\RoleHierarchy;
use App\Models\DepartmentRole;
use App\Services\FirebaseNotificationService;
use App\Services\Slack\AttachmentConfirmationSlackService;
use App\Services\TaskController\TaskHierarchyService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AttachmentConfirmationService
{
    protected $firebaseService;
    protected $slackService;
    protected $hierarchyService;

    public function __construct(
        FirebaseNotificationService $firebaseService,
        AttachmentConfirmationSlackService $slackService,
        TaskHierarchyService $hierarchyService
    ) {
        $this->firebaseService = $firebaseService;
        $this->slackService = $slackService;
        $this->hierarchyService = $hierarchyService;
    }

    /**
     * طلب تأكيد مرفق من مسؤول المشروع
     */
    public function requestConfirmation($attachmentId, $fileType = null)
    {
        try {
            DB::beginTransaction();

            $attachment = ProjectAttachment::with('project')->findOrFail($attachmentId);
            $project = $attachment->project;

            // البحث عن المسؤول بالاسم (لأن manager هو string وليس foreign key)
            $managerName = $project->manager;
            if (!$managerName) {
                return [
                    'success' => false,
                    'message' => 'لا يوجد مسؤول محدد لهذا المشروع'
                ];
            }

            // البحث عن المستخدم بالاسم
            $manager = User::where('name', $managerName)->first();
            if (!$manager) {
                return [
                    'success' => false,
                    'message' => 'لم يتم العثور على المسؤول في النظام'
                ];
            }

            // التحقق من عدم وجود طلب معلق بالفعل
            $existingRequest = AttachmentConfirmation::where('attachment_id', $attachmentId)
                ->where('status', 'pending')
                ->first();

            if ($existingRequest) {
                return [
                    'success' => false,
                    'message' => 'يوجد طلب تأكيد معلق بالفعل لهذا المرفق'
                ];
            }

            // إنشاء طلب التأكيد
            $confirmation = AttachmentConfirmation::create([
                'attachment_id' => $attachmentId,
                'project_id' => $project->id,
                'requested_by' => Auth::id(),
                'manager_id' => $manager->id,
                'status' => 'pending',
                'file_type' => $fileType,
            ]);

            // إرسال إشعار للمسؤول
            $this->notifyManager($confirmation);

            DB::commit();

            return [
                'success' => true,
                'message' => 'تم إرسال طلب التأكيد بنجاح',
                'confirmation' => $confirmation
            ];

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error requesting attachment confirmation', [
                'error' => $e->getMessage(),
                'attachment_id' => $attachmentId
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء إرسال طلب التأكيد'
            ];
        }
    }

    /**
     * تأكيد المرفق من قبل المسؤول
     */
    public function confirmAttachment($confirmationId, $notes = null)
    {
        try {
            DB::beginTransaction();

            $confirmation = AttachmentConfirmation::with(['attachment', 'requester', 'project'])
                ->findOrFail($confirmationId);

            // التحقق من الصلاحيات
            if ($confirmation->manager_id !== Auth::id()) {
                return [
                    'success' => false,
                    'message' => 'غير مسموح لك بتأكيد هذا الطلب'
                ];
            }

            if (!$confirmation->isPending()) {
                return [
                    'success' => false,
                    'message' => 'هذا الطلب تم معالجته مسبقاً'
                ];
            }

            // تأكيد الطلب
            $confirmation->confirm($notes, Auth::id());

            // إرسال إشعار لمقدم الطلب
            $this->notifyRequester($confirmation, 'confirmed');

            DB::commit();

            return [
                'success' => true,
                'message' => 'تم تأكيد المرفق بنجاح',
                'confirmation' => $confirmation
            ];

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error confirming attachment', [
                'error' => $e->getMessage(),
                'confirmation_id' => $confirmationId
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء تأكيد المرفق'
            ];
        }
    }

    /**
     * رفض المرفق من قبل المسؤول
     */
    public function rejectAttachment($confirmationId, $notes = null)
    {
        try {
            DB::beginTransaction();

            $confirmation = AttachmentConfirmation::with(['attachment', 'requester', 'project'])
                ->findOrFail($confirmationId);

            // التحقق من الصلاحيات
            if ($confirmation->manager_id !== Auth::id()) {
                return [
                    'success' => false,
                    'message' => 'غير مسموح لك برفض هذا الطلب'
                ];
            }

            if (!$confirmation->isPending()) {
                return [
                    'success' => false,
                    'message' => 'هذا الطلب تم معالجته مسبقاً'
                ];
            }

            // رفض الطلب
            $confirmation->reject($notes, Auth::id());

            // إرسال إشعار لمقدم الطلب
            $this->notifyRequester($confirmation, 'rejected');

            DB::commit();

            return [
                'success' => true,
                'message' => 'تم رفض المرفق',
                'confirmation' => $confirmation
            ];

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error rejecting attachment', [
                'error' => $e->getMessage(),
                'confirmation_id' => $confirmationId
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء رفض المرفق'
            ];
        }
    }

    /**
     * جلب طلبات التأكيد للمسؤول مع تطبيق النظام الهرمي
     */
    public function getManagerConfirmations($managerId = null, $status = null, $projectId = null, $month = null)
    {
        $managerId = $managerId ?? Auth::id();
        $user = User::find($managerId);

        // التحقق من الأدوار التي ترى جميع الطلبات
        $userRoles = $user->roles->pluck('name')->toArray();
        $viewAllRoles = ['company_manager', 'hr', 'project_manager', 'technical_support'];
        $hasViewAllAccess = !empty(array_intersect($userRoles, $viewAllRoles));

        if ($hasViewAllAccess) {
            $query = AttachmentConfirmation::with([
                'attachment.user',
                'project',
                'requester',
                'manager',
                'confirmedBy'
            ]);
        } else {
            // تطبيق النظام الهرمي
            $query = $this->getFilteredConfirmationsQuery($user);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($projectId) {
            $query->forProject($projectId);
        }

        // فلتر الشهر (يأتي بصيغة YYYY-MM من input type="month")
        if ($month) {
            $monthParts = explode('-', $month);
            if (count($monthParts) == 2) {
                $year = $monthParts[0];
                $monthNumber = $monthParts[1];
                $query->whereYear('created_at', $year)
                      ->whereMonth('created_at', $monthNumber);
            }
        }

        return $query->orderBy('created_at', 'desc')->paginate(20);
    }

    /**
     * فلترة طلبات التأكيد حسب النظام الهرمي
     */
    protected function getFilteredConfirmationsQuery(User $user)
    {
        $globalLevel = $this->hierarchyService->getCurrentUserHierarchyLevel($user);
        $departmentLevel = DepartmentRole::getUserDepartmentHierarchyLevel($user);

        $query = AttachmentConfirmation::with([
            'attachment.user',
            'project',
            'requester',
            'manager',
            'confirmedBy'
        ]);

        // المستوى العالي (5+) - جميع الطلبات
        if ($globalLevel && $globalLevel >= 5) {
            return $query;
        }

        // المستوى 4 (مدير القسم) - طلبات القسم
        if (($departmentLevel && $departmentLevel >= 4) || ($globalLevel && $globalLevel == 4)) {
            if (!$user->department) {
                return $query->where('id', null); // لا يوجد قسم = لا شيء
            }

            // جلب المشاريع التي تتبع القسم
            $departmentProjects = Project::where('department', $user->department)
                ->pluck('id')
                ->toArray();

            return $query->whereIn('project_id', $departmentProjects);
        }

        // المستوى 3 (قائد الفريق) - طلبات الفريق
        if (($departmentLevel && $departmentLevel == 3) || ($globalLevel && $globalLevel == 3)) {
            $teamUsers = $this->hierarchyService->filterUsersByTeam(
                User::where('employee_status', 'active')->get(),
                $user
            );

            $teamUserIds = $teamUsers->pluck('id')->toArray();

            // الطلبات التي تم طلبها من قبل أعضاء الفريق أو موجهة لهم
            return $query->where(function($q) use ($teamUserIds) {
                $q->whereIn('requested_by', $teamUserIds)
                  ->orWhereIn('manager_id', $teamUserIds);
            });
        }

        // المستويات الأقل - فقط الطلبات المرسلة للمستخدم كمسؤول
        return $query->forManager($user->id);
    }

    /**
     * جلب طلبات التأكيد التي أرسلها المستخدم
     */
    public function getUserConfirmations($userId = null, $status = null, $projectId = null, $month = null)
    {
        $userId = $userId ?? Auth::id();

        $query = AttachmentConfirmation::with([
            'attachment',
            'project',
            'manager'
        ])->where('requested_by', $userId);

        if ($status) {
            $query->where('status', $status);
        }

        if ($projectId) {
            $query->forProject($projectId);
        }

        // فلتر الشهر (يأتي بصيغة YYYY-MM من input type="month")
        if ($month) {
            $monthParts = explode('-', $month);
            if (count($monthParts) == 2) {
                $year = $monthParts[0];
                $monthNumber = $monthParts[1];
                $query->whereYear('created_at', $year)
                      ->whereMonth('created_at', $monthNumber);
            }
        }

        return $query->orderBy('created_at', 'desc')->paginate(20);
    }

    /**
     * إحصائيات طلبات التأكيد للمسؤول مع النظام الهرمي
     */
    public function getStatistics($managerId = null)
    {
        $managerId = $managerId ?? Auth::id();
        $user = User::find($managerId);

        // التحقق من الأدوار التي ترى جميع الطلبات
        $userRoles = $user->roles->pluck('name')->toArray();
        $viewAllRoles = ['company_manager', 'hr', 'project_manager', 'technical_support'];
        $hasViewAllAccess = !empty(array_intersect($userRoles, $viewAllRoles));

        if ($hasViewAllAccess) {
            return [
                'pending_count' => AttachmentConfirmation::pending()->count(),
                'confirmed_count' => AttachmentConfirmation::confirmed()->count(),
                'rejected_count' => AttachmentConfirmation::rejected()->count(),
                'total_count' => AttachmentConfirmation::count(),
            ];
        }

        // تطبيق النظام الهرمي
        $baseQuery = $this->getFilteredConfirmationsQuery($user);

        // حساب الإحصائيات من الـ query المفلتر
        return [
            'pending_count' => (clone $baseQuery)->pending()->count(),
            'confirmed_count' => (clone $baseQuery)->confirmed()->count(),
            'rejected_count' => (clone $baseQuery)->rejected()->count(),
            'total_count' => (clone $baseQuery)->count(),
        ];
    }

    /**
     * إحصائيات طلبات التأكيد المرسلة من المستخدم
     */
    public function getUserStatistics($userId = null)
    {
        $userId = $userId ?? Auth::id();

        return [
            'pending_count' => AttachmentConfirmation::where('requested_by', $userId)->pending()->count(),
            'confirmed_count' => AttachmentConfirmation::where('requested_by', $userId)->confirmed()->count(),
            'rejected_count' => AttachmentConfirmation::where('requested_by', $userId)->rejected()->count(),
            'total_count' => AttachmentConfirmation::where('requested_by', $userId)->count(),
        ];
    }

    /**
     * جلب المشاريع المفلترة للمستخدم حسب النظام الهرمي
     */
    public function getFilteredProjects(User $user = null)
    {
        $user = $user ?? Auth::user();

        // التحقق من الأدوار التي ترى جميع المشاريع
        $userRoles = $user->roles->pluck('name')->toArray();
        $viewAllRoles = ['company_manager', 'hr', 'project_manager', 'technical_support'];
        $hasViewAllAccess = !empty(array_intersect($userRoles, $viewAllRoles));

        if ($hasViewAllAccess) {
            return Project::orderBy('name')->get(['id', 'name', 'code']);
        }

        $globalLevel = $this->hierarchyService->getCurrentUserHierarchyLevel($user);
        $departmentLevel = DepartmentRole::getUserDepartmentHierarchyLevel($user);

        // المستوى العالي (5+) - جميع المشاريع
        if ($globalLevel && $globalLevel >= 5) {
            return Project::orderBy('name')->get(['id', 'name', 'code']);
        }

        // المستوى 4 (مدير القسم) - مشاريع القسم
        if (($departmentLevel && $departmentLevel >= 4) || ($globalLevel && $globalLevel == 4)) {
            if (!$user->department) {
                return collect();
            }

            return Project::where('department', $user->department)
                ->orderBy('name')
                ->get(['id', 'name', 'code']);
        }

        // المستوى 3 (قائد الفريق) - مشاريع الفريق
        if (($departmentLevel && $departmentLevel == 3) || ($globalLevel && $globalLevel == 3)) {
            $teamUsers = $this->hierarchyService->filterUsersByTeam(
                User::where('employee_status', 'active')->get(),
                $user
            );

            $teamUserIds = $teamUsers->pluck('id')->toArray();

            // المشاريع التي يكون المسؤول عنها من الفريق
            return Project::whereIn('manager', function($query) use ($teamUserIds) {
                $query->select('name')
                      ->from('users')
                      ->whereIn('id', $teamUserIds);
            })
            ->orWhere('manager', $user->name)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
        }

        // المستويات الأقل - المشاريع التي المستخدم مسؤول عنها
        return Project::where('manager', $user->name)
            ->orWhereHas('users', function($query) use ($user) {
                $query->where('users.id', $user->id);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
    }

    /**
     * إعادة تعيين طلب التأكيد (حذفه)
     * يمكن للمسؤول أو لصاحب الطلب إلغاؤه
     */
    public function resetConfirmation($confirmationId)
    {
        try {
            $confirmation = AttachmentConfirmation::findOrFail($confirmationId);

            // التحقق من الصلاحية: المسؤول أو صاحب الطلب
            if ($confirmation->manager_id !== Auth::id() && $confirmation->requested_by !== Auth::id()) {
                return [
                    'success' => false,
                    'message' => 'ليس لديك صلاحية لإلغاء هذا الطلب'
                ];
            }

            // التحقق من أنه لم يمر 24 ساعة على التأكيد
            if ($confirmation->confirmed_at && $confirmation->status != 'pending') {
                $hoursSinceConfirmation = now()->diffInHours($confirmation->confirmed_at);
                if ($hoursSinceConfirmation >= 24) {
                    return [
                        'success' => false,
                        'message' => 'لا يمكن إعادة التعيين بعد مرور 24 ساعة على التأكيد'
                    ];
                }
            }

            // إعادة الحالة إلى pending (قيد الانتظار)
            // سواء كان confirmed أو rejected
            $confirmation->update([
                'status' => 'pending',
                'notes' => null,
                'confirmed_by' => null,
                'confirmed_at' => null,
            ]);

            return [
                'success' => true,
                'message' => 'تم إعادة تعيين الطلب إلى حالة الانتظار بنجاح'
            ];

        } catch (\Exception $e) {
            Log::error('Error resetting confirmation: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء إلغاء الطلب'
            ];
        }
    }

    /**
     * التحقق من وجود طلب تأكيد معلق للمرفق
     */
    public function hasPendingConfirmation($attachmentId)
    {
        return AttachmentConfirmation::where('attachment_id', $attachmentId)
            ->where('status', 'pending')
            ->exists();
    }

    /**
     * الحصول على حالة تأكيد المرفق
     */
    public function getAttachmentConfirmationStatus($attachmentId)
    {
        $confirmation = AttachmentConfirmation::where('attachment_id', $attachmentId)
            ->latest()
            ->first();

        if (!$confirmation) {
            return null;
        }

        return [
            'status' => $confirmation->status,
            'confirmed_at' => $confirmation->confirmed_at,
            'notes' => $confirmation->notes,
            'manager' => $confirmation->manager,
        ];
    }

    /**
     * إرسال إشعار للمسؤول
     */
    private function notifyManager(AttachmentConfirmation $confirmation)
    {
        try {
            $manager = $confirmation->manager;
            $requester = $confirmation->requester;
            $attachment = $confirmation->attachment;
            $project = $confirmation->project;

            $message = "{$requester->name} يطلب تأكيد مرفق في مشروع {$project->name}";

            // إنشاء إشعار في قاعدة البيانات
            Notification::create([
                'user_id' => $manager->id,
                'type' => 'attachment_confirmation_request',
                'data' => [
                    'message' => $message,
                    'confirmation_id' => $confirmation->id,
                    'attachment_id' => $attachment->id,
                    'attachment_name' => $attachment->file_name,
                    'file_type' => $confirmation->file_type,
                    'project_id' => $project->id,
                    'project_name' => $project->name,
                    'requester_id' => $requester->id,
                    'requester_name' => $requester->name,
                ],
                'related_id' => $confirmation->id
            ]);

            // إرسال Firebase notification
            if ($manager->fcm_token) {
                $this->firebaseService->sendNotification(
                    $manager->fcm_token,
                    'طلب تأكيد مرفق',
                    $message,
                    "/attachment-confirmations"
                );
            }

            // إرسال Slack notification
            $this->slackService->sendConfirmationRequest($confirmation);

        } catch (\Exception $e) {
            Log::error('Error notifying manager', [
                'error' => $e->getMessage(),
                'confirmation_id' => $confirmation->id
            ]);
        }
    }

    /**
     * إرسال إشعار لمقدم الطلب
     */
    private function notifyRequester(AttachmentConfirmation $confirmation, $action)
    {
        try {
            $requester = $confirmation->requester;
            $manager = $confirmation->manager;
            $attachment = $confirmation->attachment;
            $project = $confirmation->project;

            $actionText = $action === 'confirmed' ? 'تأكيد' : 'رفض';
            $message = "{$manager->name} قام بـ{$actionText} المرفق في مشروع {$project->name}";

            // إنشاء إشعار في قاعدة البيانات
            Notification::create([
                'user_id' => $requester->id,
                'type' => 'attachment_confirmation_response',
                'data' => [
                    'message' => $message,
                    'confirmation_id' => $confirmation->id,
                    'attachment_id' => $attachment->id,
                    'attachment_name' => $attachment->file_name,
                    'file_type' => $confirmation->file_type,
                    'project_id' => $project->id,
                    'project_name' => $project->name,
                    'manager_id' => $manager->id,
                    'manager_name' => $manager->name,
                    'action' => $action,
                    'notes' => $confirmation->notes,
                ],
                'related_id' => $confirmation->id
            ]);

            // إرسال Firebase notification
            if ($requester->fcm_token) {
                $this->firebaseService->sendNotification(
                    $requester->fcm_token,
                    $actionText . ' المرفق',
                    $message,
                    "/projects/{$project->id}"
                );
            }

            // إرسال Slack notification
            $this->slackService->sendConfirmationResponse($confirmation, $action);

        } catch (\Exception $e) {
            Log::error('Error notifying requester', [
                'error' => $e->getMessage(),
                'confirmation_id' => $confirmation->id
            ]);
        }
    }
}
