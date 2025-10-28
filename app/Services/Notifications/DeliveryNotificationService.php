<?php

namespace App\Services\Notifications;

use App\Models\ProjectServiceUser;
use App\Models\User;
use App\Models\Notification;
use App\Models\RoleApproval;
use App\Services\FirebaseNotificationService;
use App\Services\Slack\ProjectSlackService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class DeliveryNotificationService
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
     * إشعار للمعتمدين عند تسليم الموظف للشغل
     * هنا بيروح للكل اللي ينطبق عليهم الشروط
     */
    public function notifyApproversWhenDelivered(ProjectServiceUser $delivery): void
    {
        try {
            $requiredApprovals = $delivery->getRequiredApprovals();

            // جلب المعتمدين الإداريين والفنيين
            $administrativeApprovers = collect();
            $technicalApprovers = collect();

            if ($requiredApprovals['needs_administrative'] && !$delivery->hasAdministrativeApproval()) {
                $administrativeApprovers = $this->getPotentialApprovers($delivery, 'administrative');
            }

            if ($requiredApprovals['needs_technical'] && !$delivery->hasTechnicalApproval()) {
                $technicalApprovers = $this->getPotentialApprovers($delivery, 'technical');
            }

            // تحديد المعتمدين المشتركين بين الإداري والفني
            $commonApprovers = $administrativeApprovers->intersectByKeys($technicalApprovers->keyBy('id'));
            $uniqueAdministrativeApprovers = $administrativeApprovers->diffKeys($technicalApprovers->keyBy('id'));
            $uniqueTechnicalApprovers = $technicalApprovers->diffKeys($administrativeApprovers->keyBy('id'));

            // إرسال إشعارات للمعتمدين المشتركين (إشعار واحد فقط)
            foreach ($commonApprovers as $approver) {
                $this->sendCombinedApprovalNotification($delivery, $approver, ['administrative', 'technical']);
            }

            // إرسال إشعارات للمعتمدين الإداريين فقط
            foreach ($uniqueAdministrativeApprovers as $approver) {
                $this->sendApprovalNotification($delivery, $approver, 'administrative');
            }

            // إرسال إشعارات للمعتمدين الفنيين فقط
            foreach ($uniqueTechnicalApprovers as $approver) {
                $this->sendApprovalNotification($delivery, $approver, 'technical');
            }

            Log::info('Delivery approval notifications sent', [
                'delivery_id' => $delivery->id,
                'needs_administrative' => $requiredApprovals['needs_administrative'],
                'needs_technical' => $requiredApprovals['needs_technical'],
                'common_approvers_count' => $commonApprovers->count(),
                'unique_administrative_count' => $uniqueAdministrativeApprovers->count(),
                'unique_technical_count' => $uniqueTechnicalApprovers->count(),
                'total_notifications_sent' => $commonApprovers->count() + $uniqueAdministrativeApprovers->count() + $uniqueTechnicalApprovers->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send delivery approval notifications', [
                'error' => $e->getMessage(),
                'delivery_id' => $delivery->id
            ]);
        }
    }

    /**
     * إرسال إشعار مشترك للمعتمدين الذين يمكنهم الاعتماد الإداري والفني
     */
    private function sendCombinedApprovalNotification(ProjectServiceUser $delivery, User $approver, array $approvalTypes): void
    {
        $typesArabic = [];
        foreach ($approvalTypes as $type) {
            $typesArabic[] = $type === 'administrative' ? 'الإداري' : 'الفني';
        }
        $typesText = implode(' و ', $typesArabic);

        $message = "تسليمة بانتظار اعتمادك {$typesText}: {$delivery->project->name} - {$delivery->user->name}";

        // إنشاء إشعار في قاعدة البيانات
        Notification::create([
            'user_id' => $approver->id,
            'type' => 'delivery_awaiting_approval',
            'data' => [
                'message' => $message,
                'approval_types' => $approvalTypes,
                'approval_type' => 'combined', // للتوافق مع الكود الموجود
                'delivery_id' => $delivery->id,
                'project_id' => $delivery->project_id,
                'project_name' => $delivery->project->name ?? 'غير محدد',
                'service_name' => $delivery->service->name ?? 'غير محدد',
                'user_name' => $delivery->user->name,
                'delivered_at' => $delivery->delivered_at ? $delivery->delivered_at->format('Y-m-d H:i') : null,
            ],
            'related_id' => $delivery->id
        ]);

        // إرسال Firebase
        if ($approver->fcm_token) {
            try {
                $this->firebaseService->sendNotificationQueued(
                    $approver->fcm_token,
                    "طلب اعتماد {$typesText}",
                    $message,
                    route('deliveries.index')
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
            $this->slackService->sendDeliveryAwaitingApprovalNotification(
                $delivery,
                $approver,
                'combined'
            );
        } catch (\Exception $e) {
            Log::warning('Failed to send Slack notification to approver', [
                'error' => $e->getMessage(),
                'approver_id' => $approver->id
            ]);
        }

        Log::info('Combined approval notification sent', [
            'delivery_id' => $delivery->id,
            'approver_id' => $approver->id,
            'approval_types' => $approvalTypes
        ]);
    }

    /**
     * إرسال إشعار اعتماد واحد لمعتمد واحد
     */
    private function sendApprovalNotification(ProjectServiceUser $delivery, User $approver, string $approvalType): void
    {
        $typeArabic = $approvalType === 'administrative' ? 'الإداري' : 'الفني';
        $message = "📋 تسليمة جديدة بانتظار اعتمادك {$typeArabic}\n\nالمشروع: {$delivery->project->name}\nالموظف: {$delivery->user->name}\nالخدمة: " . ($delivery->service->name ?? 'غير محدد') . "\n\n⏰ يرجى المراجعة في أقرب وقت";

        // إنشاء إشعار في قاعدة البيانات
        Notification::create([
            'user_id' => $approver->id,
            'type' => 'delivery_awaiting_approval',
            'data' => [
                'message' => $message,
                'approval_type' => $approvalType,
                'delivery_id' => $delivery->id,
                'project_id' => $delivery->project_id,
                'project_name' => $delivery->project->name ?? 'غير محدد',
                'service_name' => $delivery->service->name ?? 'غير محدد',
                'user_name' => $delivery->user->name,
                'delivered_at' => $delivery->delivered_at ? $delivery->delivered_at->format('Y-m-d H:i') : null,
            ],
            'related_id' => $delivery->id
        ]);

        // إرسال Firebase
        if ($approver->fcm_token) {
            try {
                $this->firebaseService->sendNotificationQueued(
                    $approver->fcm_token,
                    "📋 تسليمة جديدة بانتظار الاعتماد {$typeArabic}",
                    $message,
                    route('deliveries.index')
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
            $this->slackService->sendDeliveryAwaitingApprovalNotification(
                $delivery,
                $approver,
                $approvalType
            );
        } catch (\Exception $e) {
            Log::warning('Failed to send Slack notification to approver', [
                'error' => $e->getMessage(),
                'approver_id' => $approver->id
            ]);
        }

        Log::info('Single approval notification sent', [
            'delivery_id' => $delivery->id,
            'approver_id' => $approver->id,
            'approval_type' => $approvalType
        ]);
    }

    /**
     * إرسال إشعارات الاعتماد لجميع المعتمدين المحتملين (الدالة القديمة - للتوافق)
     */
    private function sendApprovalNotifications(ProjectServiceUser $delivery, string $approvalType): void
    {
        $approvers = $this->getPotentialApprovers($delivery, $approvalType);

        if ($approvers->isEmpty()) {
            Log::info('No approvers found for delivery', [
                'delivery_id' => $delivery->id,
                'approval_type' => $approvalType
            ]);
            return;
        }

        $typeArabic = $approvalType === 'administrative' ? 'الإداري' : 'الفني';
        $message = "تسليمة بانتظار اعتمادك {$typeArabic}: {$delivery->project->name} - {$delivery->user->name}";

        foreach ($approvers as $approver) {
            // إنشاء إشعار في قاعدة البيانات
            Notification::create([
                'user_id' => $approver->id,
                'type' => 'delivery_awaiting_approval',
                'data' => [
                    'message' => $message,
                    'approval_type' => $approvalType,
                    'delivery_id' => $delivery->id,
                    'project_id' => $delivery->project_id,
                    'project_name' => $delivery->project->name ?? 'غير محدد',
                    'service_name' => $delivery->service->name ?? 'غير محدد',
                    'user_name' => $delivery->user->name,
                    'delivered_at' => $delivery->delivered_at ? $delivery->delivered_at->format('Y-m-d H:i') : null,
                ],
                'related_id' => $delivery->id
            ]);

            // إرسال Firebase
            if ($approver->fcm_token) {
                try {
                    $this->firebaseService->sendNotificationQueued(
                        $approver->fcm_token,
                        "طلب اعتماد {$typeArabic}",
                        $message,
                        route('deliveries.index')
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
                $this->slackService->sendDeliveryAwaitingApprovalNotification(
                    $delivery,
                    $approver,
                    $approvalType
                );
            } catch (\Exception $e) {
                Log::warning('Failed to send Slack notification to approver', [
                    'error' => $e->getMessage(),
                    'approver_id' => $approver->id
                ]);
            }
        }

        Log::info('Approval notifications sent', [
            'delivery_id' => $delivery->id,
            'approval_type' => $approvalType,
            'approvers_count' => $approvers->count(),
            'approvers_ids' => $approvers->pluck('id')->toArray()
        ]);
    }

    /**
     * إشعار للموظف عند اعتماد تسليمته
     */
    public function notifyEmployeeWhenApproved(ProjectServiceUser $delivery, User $approver, string $approvalType): void
    {
        try {
            $user = $delivery->user;
            if (!$user) return;

            $typeArabic = $approvalType === 'administrative' ? 'إدارياً' : 'فنياً';
            $message = "تم اعتماد تسليمتك {$typeArabic} في مشروع: {$delivery->project->name}";

            // إنشاء إشعار في قاعدة البيانات
            Notification::create([
                'user_id' => $user->id,
                'type' => 'delivery_approved',
                'data' => [
                    'message' => $message,
                    'approval_type' => $approvalType,
                    'delivery_id' => $delivery->id,
                    'project_id' => $delivery->project_id,
                    'project_name' => $delivery->project->name ?? 'غير محدد',
                    'approved_by' => $approver->name,
                    'approved_at' => now()->format('Y-m-d H:i'),
                    'notes' => $approvalType === 'administrative'
                        ? $delivery->administrative_notes
                        : $delivery->technical_notes,
                ],
                'related_id' => $delivery->id
            ]);

            // إرسال Firebase
            if ($user->fcm_token) {
                try {
                    $this->firebaseService->sendNotificationQueued(
                        $user->fcm_token,
                        "اعتماد تسليمة {$typeArabic}",
                        $message,
                        route('deliveries.index')
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
                $this->slackService->sendDeliveryApprovedNotification(
                    $delivery,
                    $user,
                    $approver,
                    $approvalType
                );
            } catch (\Exception $e) {
                Log::warning('Failed to send Slack notification to employee', [
                    'error' => $e->getMessage(),
                    'user_id' => $user->id
                ]);
            }

            // إذا تم الاعتماد الإداري، أرسل للمعتمدين الفنيين (إن وجد)
            if ($approvalType === 'administrative') {
                $requiredApprovals = $delivery->getRequiredApprovals();
                if ($requiredApprovals['needs_technical'] && !$delivery->hasTechnicalApproval()) {
                    $this->sendApprovalNotifications($delivery, 'technical');
                }
            }

            Log::info('Employee approval notification sent', [
                'delivery_id' => $delivery->id,
                'user_id' => $user->id,
                'approved_by' => $approver->id,
                'approval_type' => $approvalType
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send employee approval notification', [
                'error' => $e->getMessage(),
                'delivery_id' => $delivery->id,
                'approval_type' => $approvalType
            ]);
        }
    }

    /**
     * جلب جميع المعتمدين المحتملين
     * هنا بنطبق شروط RoleApproval
     */
    private function getPotentialApprovers(ProjectServiceUser $delivery, string $approvalType): Collection
    {
        $approvers = collect();
        $deliveryUserRoles = $delivery->user->roles;

        foreach ($deliveryUserRoles as $deliveryRole) {
            // جلب قواعد الاعتماد لهذا الرول
            $approvalRules = RoleApproval::where('role_id', $deliveryRole->id)
                ->where('approval_type', $approvalType)
                ->where('is_active', true)
                ->with('approverRole')
                ->get();

            foreach ($approvalRules as $rule) {
                // جلب جميع المستخدمين الذين لديهم الـ approver role
                $potentialApprovers = User::role($rule->approverRole->name)
                    ->where('employee_status', 'active')
                    ->get();

                foreach ($potentialApprovers as $potentialApprover) {
                    // فحص الشروط الإضافية

                    // 1. requires_same_project
                    if ($rule->requires_same_project) {
                        $isInSameProject = ProjectServiceUser::where('project_id', $delivery->project_id)
                            ->where('user_id', $potentialApprover->id)
                            ->exists();

                        if (!$isInSameProject) {
                            continue; // هذا المعتمد ليس في نفس المشروع
                        }
                    }

                    // 2. requires_team_owner
                    if ($rule->requires_team_owner) {
                        $deliveryUserTeamId = $delivery->user->current_team_id;

                        if ($deliveryUserTeamId) {
                            $isTeamOwner = \App\Models\Team::where('id', $deliveryUserTeamId)
                                ->where('user_id', $potentialApprover->id)
                                ->exists();

                            if (!$isTeamOwner) {
                                continue; // هذا المعتمد ليس مالك الفريق
                            }
                        } else {
                            continue; // الموظف ليس في فريق
                        }
                    }

                    // إذا وصلنا هنا، المعتمد ينطبق عليه الشروط
                    $approvers->push($potentialApprover);
                }
            }
        }

        // إرجاع معتمدين فريدين (لو واحد عنده أكتر من role ينطبق عليه)
        return $approvers->unique('id');
    }

    /**
     * إشعار للمعتمدين عند إلغاء تسليم الموظف
     */
    public function notifyApproversWhenUndelivered(ProjectServiceUser $delivery): void
    {
        try {
            $requiredApprovals = $delivery->getRequiredApprovals();

            // جلب المعتمدين الإداريين والفنيين الذين كانوا سيعتمدون التسليمة
            $administrativeApprovers = collect();
            $technicalApprovers = collect();

            if ($requiredApprovals['needs_administrative']) {
                $administrativeApprovers = $this->getPotentialApprovers($delivery, 'administrative');
            }

            if ($requiredApprovals['needs_technical']) {
                $technicalApprovers = $this->getPotentialApprovers($delivery, 'technical');
            }

            // دمج المعتمدين
            $allApprovers = $administrativeApprovers->merge($technicalApprovers)->unique('id');

            // إرسال إشعارات للمعتمدين
            foreach ($allApprovers as $approver) {
                $this->sendUndeliveryNotification($delivery, $approver);
            }

            Log::info('Delivery undelivery notifications sent', [
                'delivery_id' => $delivery->id,
                'approvers_count' => $allApprovers->count(),
                'approvers_ids' => $allApprovers->pluck('id')->toArray()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send undelivery notifications', [
                'delivery_id' => $delivery->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * إرسال إشعار إلغاء التسليمة للمعتمد
     */
    private function sendUndeliveryNotification(ProjectServiceUser $delivery, User $approver): void
    {
        $message = "❌ تم إلغاء تسليمة كانت في انتظار اعتمادك\n\nالمشروع: {$delivery->project->name}\nالموظف: {$delivery->user->name}\nالخدمة: " . ($delivery->service->name ?? 'غير محدد') . "\n\nℹ️ لم تعد هذه التسليمة في انتظار اعتمادك";

        // إنشاء إشعار في قاعدة البيانات
        Notification::create([
            'user_id' => $approver->id,
            'type' => 'delivery_undelivered',
            'data' => [
                'message' => $message,
                'delivery_id' => $delivery->id,
                'project_id' => $delivery->project_id,
                'project_name' => $delivery->project->name ?? 'غير محدد',
                'service_name' => $delivery->service->name ?? 'غير محدد',
                'user_name' => $delivery->user->name,
                'undelivered_at' => now()->format('Y-m-d H:i'),
            ],
            'related_id' => $delivery->id
        ]);

        // إرسال Firebase
        if ($approver->fcm_token) {
            try {
                $this->firebaseService->sendNotificationQueued(
                    $approver->fcm_token,
                    "❌ تم إلغاء تسليمة",
                    $message,
                    route('deliveries.index')
                );
            } catch (\Exception $e) {
                Log::warning('Failed to send Firebase undelivery notification', [
                    'error' => $e->getMessage(),
                    'approver_id' => $approver->id
                ]);
            }
        }

        // إرسال Slack
        try {
            $this->slackService->sendDeliveryUndeliveredNotification(
                $delivery,
                $approver
            );
        } catch (\Exception $e) {
            Log::warning('Failed to send Slack undelivery notification', [
                'error' => $e->getMessage(),
                'approver_id' => $approver->id
            ]);
        }
    }
}

