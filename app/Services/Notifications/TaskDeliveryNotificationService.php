<?php

namespace App\Services\Notifications;

use App\Models\TaskUser;
use App\Models\TemplateTaskUser;
use App\Models\User;
use App\Models\Notification;
use App\Models\RoleApproval;
use App\Services\FirebaseNotificationService;
use App\Services\Slack\TaskSlackService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class TaskDeliveryNotificationService
{
    protected $firebaseService;
    protected $slackService;

    public function __construct(
        FirebaseNotificationService $firebaseService,
        TaskSlackService $slackService
    ) {
        $this->firebaseService = $firebaseService;
        $this->slackService = $slackService;
    }

    /**
     * إشعار عند اكتمال التاسك
     * - لو مرتبطة بمشروع: إشعار للمعتمدين حسب RoleApproval
     * - لو مش مرتبطة بمشروع: إشعار للـ created_by
     */
    public function notifyTaskCompleted($taskUser): void
    {
        try {
            // التحقق من نوع التاسك
            $isProjectTask = $this->isProjectTask($taskUser);

            if ($isProjectTask) {
                // تاسك مرتبطة بمشروع - نفس نظام تسليمات المشاريع
                $this->notifyProjectTaskApprovers($taskUser);
            } else {
                // تاسك عادية - إشعار للـ creator
                $this->notifyTaskCreator($taskUser);
            }

        } catch (\Exception $e) {
            Log::error('Failed to send task completion notifications', [
                'error' => $e->getMessage(),
                'task_id' => $taskUser->id,
                'task_type' => get_class($taskUser)
            ]);
        }
    }

    /**
     * إشعار للموظف عند الاعتماد الإداري/الفني
     */
    public function notifyTaskApproved($taskUser, User $approver, string $approvalType): void
    {
        try {
            $user = $taskUser->user;
            if (!$user) return;

            $typeArabic = $approvalType === 'administrative' ? 'إدارياً' : 'فنياً';
            $taskName = $this->getTaskName($taskUser);
            $message = "تم اعتماد تاسكك {$typeArabic}: {$taskName}";

            // إنشاء إشعار في قاعدة البيانات
            Notification::create([
                'user_id' => $user->id,
                'type' => 'task_approved',
                'data' => [
                    'message' => $message,
                    'approval_type' => $approvalType,
                    'task_id' => $taskUser->id,
                    'task_type' => get_class($taskUser),
                    'task_name' => $taskName,
                    'approved_by' => $approver->name,
                    'approved_at' => now()->format('Y-m-d H:i'),
                    'notes' => $approvalType === 'administrative'
                        ? $taskUser->administrative_notes
                        : $taskUser->technical_notes,
                ],
                'related_id' => $taskUser->id
            ]);

            // إرسال Firebase
            if ($user->fcm_token) {
                try {
                    $this->firebaseService->sendNotificationQueued(
                        $user->fcm_token,
                        "اعتماد تاسك {$typeArabic}",
                        $message,
                        route('task-deliveries.index')
                    );
                } catch (\Exception $e) {
                    Log::warning('Failed to send Firebase notification', [
                        'error' => $e->getMessage(),
                        'user_id' => $user->id
                    ]);
                }
            }

            // إرسال Slack
            try {
                $this->slackService->sendTaskApprovedNotification(
                    $taskUser,
                    $user,
                    $approver,
                    $approvalType
                );
            } catch (\Exception $e) {
                Log::warning('Failed to send Slack notification', [
                    'error' => $e->getMessage(),
                    'user_id' => $user->id
                ]);
            }

            // إذا تم الاعتماد الإداري، أرسل للمعتمدين الفنيين (إن وجد)
            if ($approvalType === 'administrative' && $this->isProjectTask($taskUser)) {
                $requiredApprovals = $taskUser->getRequiredApprovals();
                if ($requiredApprovals['needs_technical'] && !$taskUser->hasTechnicalApproval()) {
                    $this->sendApprovalNotifications($taskUser, 'technical');
                }
            }

            Log::info('Task approval notification sent', [
                'task_id' => $taskUser->id,
                'user_id' => $user->id,
                'approved_by' => $approver->id,
                'approval_type' => $approvalType
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send task approval notification', [
                'error' => $e->getMessage(),
                'task_id' => $taskUser->id,
                'approval_type' => $approvalType
            ]);
        }
    }

    /**
     * إشعار للمعتمدين (للتاسكات المرتبطة بمشاريع)
     */
    private function notifyProjectTaskApprovers($taskUser): void
    {
        $requiredApprovals = $taskUser->getRequiredApprovals();

        // جلب المعتمدين الإداريين والفنيين
        $administrativeApprovers = collect();
        $technicalApprovers = collect();

        if ($requiredApprovals['needs_administrative'] && !$taskUser->hasAdministrativeApproval()) {
            $administrativeApprovers = $this->getPotentialApprovers($taskUser, 'administrative');
        }

        if ($requiredApprovals['needs_technical'] && !$taskUser->hasTechnicalApproval()) {
            $technicalApprovers = $this->getPotentialApprovers($taskUser, 'technical');
        }

        // تحديد المعتمدين المشتركين بين الإداري والفني
        $commonApprovers = $administrativeApprovers->intersectByKeys($technicalApprovers->keyBy('id'));
        $uniqueAdministrativeApprovers = $administrativeApprovers->diffKeys($technicalApprovers->keyBy('id'));
        $uniqueTechnicalApprovers = $technicalApprovers->diffKeys($administrativeApprovers->keyBy('id'));

        // إرسال إشعارات للمعتمدين المشتركين (إشعار واحد فقط)
        foreach ($commonApprovers as $approver) {
            $this->sendCombinedTaskApprovalNotification($taskUser, $approver, ['administrative', 'technical']);
        }

        // إرسال إشعارات للمعتمدين الإداريين فقط
        foreach ($uniqueAdministrativeApprovers as $approver) {
            $this->sendTaskApprovalNotification($taskUser, $approver, 'administrative');
        }

        // إرسال إشعارات للمعتمدين الفنيين فقط
        foreach ($uniqueTechnicalApprovers as $approver) {
            $this->sendTaskApprovalNotification($taskUser, $approver, 'technical');
        }

        Log::info('Project task approval notifications sent', [
            'task_id' => $taskUser->id,
            'needs_administrative' => $requiredApprovals['needs_administrative'],
            'needs_technical' => $requiredApprovals['needs_technical'],
            'common_approvers_count' => $commonApprovers->count(),
            'unique_administrative_count' => $uniqueAdministrativeApprovers->count(),
            'unique_technical_count' => $uniqueTechnicalApprovers->count(),
            'total_notifications_sent' => $commonApprovers->count() + $uniqueAdministrativeApprovers->count() + $uniqueTechnicalApprovers->count()
        ]);
    }

    /**
     * إشعار للـ creator (للتاسكات العادية مش المرتبطة بمشاريع)
     */
    private function notifyTaskCreator($taskUser): void
    {
        $creator = $this->getTaskCreator($taskUser);
        if (!$creator || $creator->id === $taskUser->user_id) {
            return; // لا ترسل إشعار لو المنشئ هو نفس الموظف
        }

        $taskName = $this->getTaskName($taskUser);
        $message = "أكمل {$taskUser->user->name} التاسك: {$taskName}";

        // إنشاء إشعار
        Notification::create([
            'user_id' => $creator->id,
            'type' => 'task_completed',
            'data' => [
                'message' => $message,
                'task_id' => $taskUser->id,
                'task_type' => get_class($taskUser),
                'task_name' => $taskName,
                'user_name' => $taskUser->user->name,
                'completed_at' => $taskUser->completed_at ? $taskUser->completed_at->format('Y-m-d H:i') : null,
            ],
            'related_id' => $taskUser->id
        ]);

        // Firebase
        if ($creator->fcm_token) {
            try {
                $this->firebaseService->sendNotificationQueued(
                    $creator->fcm_token,
                    'تاسك مكتملة',
                    $message,
                    route('task-deliveries.index')
                );
            } catch (\Exception $e) {
                Log::warning('Failed to send Firebase notification to creator', [
                    'error' => $e->getMessage(),
                    'creator_id' => $creator->id
                ]);
            }
        }

        // Slack
        try {
            $this->slackService->sendTaskDeliveryNotification(
                $taskUser,
                $creator,
                'creator'
            );
        } catch (\Exception $e) {
            Log::warning('Failed to send Slack notification to creator', [
                'error' => $e->getMessage(),
                'creator_id' => $creator->id
            ]);
        }

        Log::info('Task creator notified', [
            'task_id' => $taskUser->id,
            'creator_id' => $creator->id
        ]);
    }

    /**
     * إرسال إشعار مشترك للمعتمدين الذين يمكنهم الاعتماد الإداري والفني للتاسكات
     */
    private function sendCombinedTaskApprovalNotification($taskUser, User $approver, array $approvalTypes): void
    {
        $typesArabic = [];
        foreach ($approvalTypes as $type) {
            $typesArabic[] = $type === 'administrative' ? 'الإداري' : 'الفني';
        }
        $typesText = implode(' و ', $typesArabic);

        $taskName = $this->getTaskName($taskUser);
        $message = "تاسك بانتظار اعتمادك {$typesText}: {$taskName} - {$taskUser->user->name}";

        // إنشاء إشعار في قاعدة البيانات
        Notification::create([
            'user_id' => $approver->id,
            'type' => 'task_awaiting_approval',
            'data' => [
                'message' => $message,
                'approval_types' => $approvalTypes,
                'approval_type' => 'combined', // للتوافق مع الكود الموجود
                'task_id' => $taskUser->id,
                'task_type' => get_class($taskUser),
                'task_name' => $taskName,
                'user_name' => $taskUser->user->name,
                'completed_at' => $taskUser->completed_at ? $taskUser->completed_at->format('Y-m-d H:i') : null,
            ],
            'related_id' => $taskUser->id
        ]);

        // إرسال Firebase
        if ($approver->fcm_token) {
            try {
                $this->firebaseService->sendNotificationQueued(
                    $approver->fcm_token,
                    "طلب اعتماد {$typesText}",
                    $message,
                    route('task-deliveries.index')
                );
            } catch (\Exception $e) {
                Log::warning('Failed to send Firebase notification', [
                    'error' => $e->getMessage(),
                    'approver_id' => $approver->id
                ]);
            }
        }

        // إرسال Slack
        try {
            $this->slackService->sendTaskDeliveryNotification(
                $taskUser,
                $approver,
                'combined_approver'
            );
        } catch (\Exception $e) {
            Log::warning('Failed to send Slack notification to approver', [
                'error' => $e->getMessage(),
                'approver_id' => $approver->id
            ]);
        }

        Log::info('Combined task approval notification sent', [
            'task_id' => $taskUser->id,
            'approver_id' => $approver->id,
            'approval_types' => $approvalTypes
        ]);
    }

    /**
     * إرسال إشعار اعتماد واحد لمعتمد واحد للتاسكات
     */
    private function sendTaskApprovalNotification($taskUser, User $approver, string $approvalType): void
    {
        $typeArabic = $approvalType === 'administrative' ? 'الإداري' : 'الفني';
        $taskName = $this->getTaskName($taskUser);
        $message = "تاسك بانتظار اعتمادك {$typeArabic}: {$taskName} - {$taskUser->user->name}";

        // إنشاء إشعار في قاعدة البيانات
        Notification::create([
            'user_id' => $approver->id,
            'type' => 'task_awaiting_approval',
            'data' => [
                'message' => $message,
                'approval_type' => $approvalType,
                'task_id' => $taskUser->id,
                'task_type' => get_class($taskUser),
                'task_name' => $taskName,
                'user_name' => $taskUser->user->name,
                'completed_at' => $taskUser->completed_at ? $taskUser->completed_at->format('Y-m-d H:i') : null,
            ],
            'related_id' => $taskUser->id
        ]);

        // إرسال Firebase
        if ($approver->fcm_token) {
            try {
                $this->firebaseService->sendNotificationQueued(
                    $approver->fcm_token,
                    "طلب اعتماد {$typeArabic}",
                    $message,
                    route('task-deliveries.index')
                );
            } catch (\Exception $e) {
                Log::warning('Failed to send Firebase notification', [
                    'error' => $e->getMessage(),
                    'approver_id' => $approver->id
                ]);
            }
        }

        // إرسال Slack
        try {
            $this->slackService->sendTaskDeliveryNotification(
                $taskUser,
                $approver,
                'approver'
            );
        } catch (\Exception $e) {
            Log::warning('Failed to send Slack notification to approver', [
                'error' => $e->getMessage(),
                'approver_id' => $approver->id
            ]);
        }

        Log::info('Single task approval notification sent', [
            'task_id' => $taskUser->id,
            'approver_id' => $approver->id,
            'approval_type' => $approvalType
        ]);
    }

    /**
     * إرسال إشعارات للمعتمدين (الدالة القديمة - للتوافق)
     */
    private function sendApprovalNotifications($taskUser, string $approvalType): void
    {
        $approvers = $this->getPotentialApprovers($taskUser, $approvalType);

        if ($approvers->isEmpty()) {
            Log::info('No approvers found for task', [
                'task_id' => $taskUser->id,
                'approval_type' => $approvalType
            ]);
            return;
        }

        $typeArabic = $approvalType === 'administrative' ? 'الإداري' : 'الفني';
        $taskName = $this->getTaskName($taskUser);
        $message = "تاسك بانتظار اعتمادك {$typeArabic}: {$taskName} - {$taskUser->user->name}";

        foreach ($approvers as $approver) {
            // إنشاء إشعار
            Notification::create([
                'user_id' => $approver->id,
                'type' => 'task_awaiting_approval',
                'data' => [
                    'message' => $message,
                    'approval_type' => $approvalType,
                    'task_id' => $taskUser->id,
                    'task_type' => get_class($taskUser),
                    'task_name' => $taskName,
                    'user_name' => $taskUser->user->name,
                    'completed_at' => $taskUser->completed_at ? $taskUser->completed_at->format('Y-m-d H:i') : null,
                ],
                'related_id' => $taskUser->id
            ]);

            // Firebase
            if ($approver->fcm_token) {
                try {
                    $this->firebaseService->sendNotificationQueued(
                        $approver->fcm_token,
                        "طلب اعتماد {$typeArabic}",
                        $message,
                        route('task-deliveries.index')
                    );
                } catch (\Exception $e) {
                    Log::warning('Failed to send Firebase notification', [
                        'error' => $e->getMessage(),
                        'approver_id' => $approver->id
                    ]);
                }
            }

            // Slack
            try {
                $this->slackService->sendTaskDeliveryNotification(
                    $taskUser,
                    $approver,
                    'approver'
                );
            } catch (\Exception $e) {
                Log::warning('Failed to send Slack notification to approver', [
                    'error' => $e->getMessage(),
                    'approver_id' => $approver->id
                ]);
            }
        }

        Log::info('Task approval notifications sent', [
            'task_id' => $taskUser->id,
            'approval_type' => $approvalType,
            'approvers_count' => $approvers->count(),
            'approvers_ids' => $approvers->pluck('id')->toArray()
        ]);
    }

    /**
     * جلب المعتمدين المحتملين (نفس منطق DeliveryNotificationService)
     */
    private function getPotentialApprovers($taskUser, string $approvalType): Collection
    {
        $approvers = collect();
        $taskUserRoles = $taskUser->user->roles;

        foreach ($taskUserRoles as $userRole) {
            $approvalRules = RoleApproval::where('role_id', $userRole->id)
                ->where('approval_type', $approvalType)
                ->where('is_active', true)
                ->with('approverRole')
                ->get();

            foreach ($approvalRules as $rule) {
                $potentialApprovers = User::role($rule->approverRole->name)
                    ->where('employee_status', 'active')
                    ->get();

                foreach ($potentialApprovers as $potentialApprover) {
                    // 1. requires_same_project
                    if ($rule->requires_same_project) {
                        $projectId = $this->getTaskProjectId($taskUser);
                        if (!$projectId) continue;

                        $isInSameProject = \App\Models\ProjectServiceUser::where('project_id', $projectId)
                            ->where('user_id', $potentialApprover->id)
                            ->exists();

                        if (!$isInSameProject) {
                            continue;
                        }
                    }

                    // 2. requires_team_owner
                    if ($rule->requires_team_owner) {
                        $teamId = $taskUser->user->current_team_id;

                        if ($teamId) {
                            $isTeamOwner = \App\Models\Team::where('id', $teamId)
                                ->where('user_id', $potentialApprover->id)
                                ->exists();

                            if (!$isTeamOwner) {
                                continue;
                            }
                        } else {
                            continue;
                        }
                    }

                    $approvers->push($potentialApprover);
                }
            }
        }

        return $approvers->unique('id');
    }

    /**
     * التحقق من أن التاسك مرتبطة بمشروع
     */
    private function isProjectTask($taskUser): bool
    {
        if ($taskUser instanceof TaskUser) {
            return $taskUser->task && $taskUser->task->project_id !== null;
        } elseif ($taskUser instanceof TemplateTaskUser) {
            return $taskUser->project_id !== null;
        }
        return false;
    }

    /**
     * جلب project_id للتاسك
     */
    private function getTaskProjectId($taskUser): ?int
    {
        if ($taskUser instanceof TaskUser) {
            return $taskUser->task->project_id ?? null;
        } elseif ($taskUser instanceof TemplateTaskUser) {
            return $taskUser->project_id;
        }
        return null;
    }

    /**
     * جلب اسم التاسك
     */
    private function getTaskName($taskUser): string
    {
        if ($taskUser instanceof TaskUser) {
            return $taskUser->task->name ?? 'غير محدد';
        } elseif ($taskUser instanceof TemplateTaskUser) {
            return $taskUser->templateTask->name ?? 'غير محدد';
        }
        return 'غير محدد';
    }

    /**
     * جلب منشئ التاسك
     */
    private function getTaskCreator($taskUser): ?User
    {
        if ($taskUser instanceof TaskUser) {
            return $taskUser->task->createdBy ?? null;
        } elseif ($taskUser instanceof TemplateTaskUser) {
            return User::find($taskUser->assigned_by);
        }
        return null;
    }
}

