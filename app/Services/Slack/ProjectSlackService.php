<?php

namespace App\Services\Slack;

use App\Models\User;
use App\Models\Project;
use App\Models\ProjectNote;
use App\Models\AttachmentShare;
use App\Models\ProjectServiceUser;

class ProjectSlackService extends BaseSlackService
{
    public function sendProjectNoteMention(ProjectNote $note, User $mentionedUser, User $author): bool
    {
        $message = $this->buildProjectNoteMentionMessage($note, $mentionedUser, $author);
        $context = 'إشعار ذكر في ملاحظة مشروع';
        $this->setNotificationContext($context);

        return $this->sendSlackNotification($mentionedUser, $message, $context, true);
    }

    public function sendProjectAssignmentNotification(Project $project, User $assignedUser, User $author): bool
    {
        $message = $this->buildProjectAssignmentMessage($project, $assignedUser, $author);
        $context = 'إشعار تعيين مشروع';
        $this->setNotificationContext($context);

        return $this->sendSlackNotification($assignedUser, $message, $context, true);
    }

    public function sendProjectRemovalNotification(Project $project, User $removedUser, User $author): bool
    {
        $message = $this->buildProjectRemovalMessage($project, $removedUser, $author);
        $context = 'إشعار إزالة من مشروع';
        $this->setNotificationContext($context);

        return $this->sendSlackNotification($removedUser, $message, $context, true);
    }

    public function sendFileShareNotification(AttachmentShare $share, User $recipient, User $sharedBy): bool
    {
        $message = $this->buildFileShareMessage($share, $recipient, $sharedBy);
        $context = 'إشعار مشاركة ملف';
        $this->setNotificationContext($context);

        return $this->sendSlackNotification($recipient, $message, $context, true);
    }

    public function sendAttachmentUploadedNotification(Project $project, User $participant, User $uploadedBy, string $folderName, string $fileName): bool
    {
        $message = $this->buildAttachmentUploadedMessage($project, $uploadedBy, $folderName, $fileName);
        $context = 'إشعار رفع مرفق مشروع';
        $this->setNotificationContext($context);

        return $this->sendSlackNotification($participant, $message, $context, true);
    }

    private function buildProjectNoteMentionMessage(ProjectNote $note, User $mentionedUser, User $author): array
    {
        $note->load(['project']);

        $projectUrl = url("/projects/{$note->project_id}");
        $noteContent = strlen($note->content) > 100 ?
            substr($note->content, 0, 100) . '...' :
            $note->content;

        return [
            'text' => "تم ذكرك في ملاحظة مشروع جديدة",
            'blocks' => [
                $this->buildHeader('🔔 تم ذكرك في ملاحظة مشروع'),
                $this->buildInfoSection([
                    "*المشروع:*\n{$note->project->name}",
                    "*بواسطة:*\n{$author->name}"
                ]),
                $this->buildTextSection("*الملاحظة:*\n{$noteContent}"),
                $this->buildTextSection("*نوع الملاحظة:* {$note->note_type_arabic}"),
                $this->buildActionsSection([
                    $this->buildActionButton('🔗 عرض المشروع', $projectUrl)
                ]),
                $this->buildContextSection("📅 {$note->created_at->format('d/m/Y - H:i')}")
            ]
        ];
    }

    private function buildProjectAssignmentMessage(Project $project, User $assignedUser, User $author): array
    {
        $projectUrl = url("/projects/{$project->id}");

        return [
            'text' => "تم إضافتك لمشروع جديد",
            'blocks' => [
                $this->buildHeader('🎯 تم إضافتك لمشروع جديد'),
                $this->buildInfoSection([
                    "*اسم المشروع:*\n{$project->name}",
                    "*أضافك:*\n{$author->name}"
                ]),
                $this->buildTextSection("*الوصف:*\n" . ($project->description ?: 'لا يوجد وصف')),
                $this->buildActionsSection([
                    $this->buildActionButton('🔗 عرض المشروع', $projectUrl)
                ]),
                $this->buildContextSection()
            ]
        ];
    }

    private function buildProjectRemovalMessage(Project $project, User $removedUser, User $author): array
    {
        return [
            'text' => "تم إزالتك من مشروع",
            'blocks' => [
                $this->buildHeader('🚫 تم إزالتك من مشروع'),
                $this->buildInfoSection([
                    "*اسم المشروع:*\n{$project->name}",
                    "*أزالك:*\n{$author->name}"
                ]),
                $this->buildTextSection("*الوصف:*\n" . ($project->description ?: 'لا يوجد وصف')),
                $this->buildContextSection()
            ]
        ];
    }

    private function buildFileShareMessage(AttachmentShare $share, User $recipient, User $sharedBy): array
    {
        $attachment = $share->attachment;
        $shareUrl = url("/shared-attachments/{$share->access_token}");

        $expiryText = $share->expires_at
            ? "\n*تنتهي في:* " . $share->expires_at->format('Y-m-d H:i')
            : "\n*صالحة:* بدون انتهاء";

        $infoSection = [
            "*اسم الملف:*\n{$attachment->original_name}",
            "*شارك بواسطة:*\n{$sharedBy->name}" . $expiryText
        ];

        if (!empty($share->description)) {
            $infoSection[] = "*ملاحظة:*\n{$share->description}";
        }

        return [
            'text' => "تم مشاركة ملف معك",
            'blocks' => [
                $this->buildHeader('📁 تم مشاركة ملف معك'),
                $this->buildInfoSection($infoSection),
                $this->buildActionsSection([
                    $this->buildActionButton('🔗 عرض الملف', $shareUrl, 'primary'),
                    $this->buildActionButton('💾 تحميل', route('shared-attachments.download', [$share->access_token, $attachment->id]))
                ]),
                $this->buildContextSection()
            ]
        ];
    }

    public function sendDeliveryAwaitingApprovalNotification(ProjectServiceUser $delivery, User $approver, string $approvalType): bool
    {
        $message = $this->buildDeliveryAwaitingApprovalMessage($delivery, $approver, $approvalType);
        $context = 'إشعار تسليمة بانتظار الاعتماد';
        $this->setNotificationContext($context);

        return $this->sendSlackNotification($approver, $message, $context, true);
    }

    /**
     * بناء رسالة تسليمة بانتظار الاعتماد
     */
    private function buildDeliveryAwaitingApprovalMessage(ProjectServiceUser $delivery, User $approver, string $approvalType): array
    {
        if ($approvalType === 'combined') {
            $typeArabic = 'الإداري والفني';
        } else {
            $typeArabic = $approvalType === 'administrative' ? 'الإداري' : 'الفني';
        }

        $projectUrl = url("/projects/{$delivery->project_id}");
        $deliveryUrl = route('deliveries.index');

        $serviceName = ($delivery->service && $delivery->service->name) ? $delivery->service->name : 'غير محدد';
        $projectName = ($delivery->project && $delivery->project->name) ? $delivery->project->name : 'غير محدد';
        $userName = ($delivery->user && $delivery->user->name) ? $delivery->user->name : 'غير محدد';

        $deliveredAt = $delivery->delivered_at ?
            $delivery->delivered_at->format('d/m/Y H:i') :
            now()->format('d/m/Y H:i');

        $buttons = [
            $this->buildActionButton('🔍 مراجعة التسليمات', $deliveryUrl, 'primary'),
            $this->buildActionButton('🔗 عرض المشروع', $projectUrl)
        ];

        $blocks = [
            $this->buildHeader("📋 تسليمة جديدة بانتظار الاعتماد {$typeArabic}"),
            $this->buildInfoSection([
                "*المشروع:*\n{$projectName}",
                "*الموظف:*\n{$userName}"
            ]),
            $this->buildInfoSection([
                "*الخدمة:*\n{$serviceName}",
                "*تاريخ التسليم:*\n{$deliveredAt}"
            ]),
            $this->buildTextSection("*نوع الاعتماد المطلوب:* {$typeArabic}\n*الحالة:* في انتظار الاعتماد"),
            $this->buildTextSection("⏰ *يرجى مراجعة التسليمة واتخاذ الإجراء المناسب في أقرب وقت*"),
            $this->buildTextSection("📝 *ملاحظة:* يمكنك مراجعة تفاصيل التسليمة والموافقة عليها أو طلب تعديلات"),
            $this->buildActionsSection($buttons),
            $this->buildContextSection()
        ];

        return [
            'text' => "تسليمة جديدة بانتظار اعتمادك {$typeArabic}",
            'blocks' => $blocks
        ];
    }

    public function sendDeliveryApprovedNotification(ProjectServiceUser $delivery, User $employee, User $approver, string $approvalType): bool
    {
        $message = $this->buildDeliveryApprovedMessage($delivery, $employee, $approver, $approvalType);
        $context = 'إشعار اعتماد تسليمة';
        $this->setNotificationContext($context);

        return $this->sendSlackNotification($employee, $message, $context, true);
    }

    private function buildDeliveryApprovedMessage(ProjectServiceUser $delivery, User $employee, User $approver, string $approvalType): array
    {
        $typeArabic = $approvalType === 'administrative' ? 'الإداري' : 'الفني';
        $projectUrl = url("/projects/{$delivery->project_id}");
        $serviceName = $delivery->service ? $delivery->service->name : 'غير محدد';

        $notes = $approvalType === 'administrative' ?
            $delivery->administrative_notes :
            $delivery->technical_notes;

        $blocks = [
            $this->buildHeader('🎉 تم اعتماد تسليمتك!'),
            $this->buildInfoSection([
                "*المشروع:*\n{$delivery->project->name}",
                "*الخدمة:*\n{$serviceName}",
                "*اعتمدها:*\n{$approver->name}"
            ]),
            $this->buildTextSection("✅ *تم الاعتماد {$typeArabic} بنجاح!*")
        ];

        if ($notes) {
            $blocks[] = $this->buildTextSection("📝 *ملاحظات المعتمد:*\n{$notes}");
        }

        $blocks[] = $this->buildActionsSection([
            $this->buildActionButton('🔗 عرض المشروع', $projectUrl)
        ]);

        $blocks[] = $this->buildContextSection();

        return [
            'text' => "تم اعتماد تسليمتك {$typeArabic}!",
            'blocks' => $blocks
        ];
    }

    public function sendDeliveryUndeliveredNotification(ProjectServiceUser $delivery, User $approver): bool
    {
        $message = $this->buildDeliveryUndeliveredMessage($delivery, $approver);
        $context = 'إشعار إلغاء تسليمة';
        $this->setNotificationContext($context);

        return $this->sendSlackNotification($approver, $message, $context, true);
    }

    private function buildDeliveryUndeliveredMessage(ProjectServiceUser $delivery, User $approver): array
    {
        $projectUrl = url("/projects/{$delivery->project_id}");
        $deliveryUrl = route('deliveries.index');

        $serviceName = $delivery->service ? $delivery->service->name : 'غير محدد';
        $undeliveredAt = now()->format('d/m/Y H:i');

        return [
            'text' => "تم إلغاء تسليمة كانت في انتظار اعتمادك",
            'blocks' => [
                $this->buildHeader("❌ تم إلغاء تسليمة"),
                $this->buildInfoSection([
                    "*المشروع:*\n{$delivery->project->name}",
                    "*الموظف:*\n{$delivery->user->name}",
                    "*الخدمة:*\n{$serviceName}"
                ]),
                $this->buildInfoSection([
                    "*تاريخ الإلغاء:*\n{$undeliveredAt}",
                    "*الحالة:*\nتم إلغاء التسليمة"
                ]),
                $this->buildTextSection("ℹ️ *لم تعد هذه التسليمة في انتظار اعتمادك*"),
                $this->buildActionsSection([
                    $this->buildActionButton('🔍 مراجعة التسليمات', $deliveryUrl, 'primary'),
                    $this->buildActionButton('🔗 عرض المشروع', $projectUrl)
                ]),
                $this->buildContextSection()
            ]
        ];
    }
        
    private function buildAttachmentUploadedMessage(Project $project, User $uploadedBy, string $folderName, string $fileName): array
    {
        $projectUrl = url("/projects/{$project->id}");
        $projectCode = $project->code ?? 'غير محدد';

        return [
            'text' => "تم رفع مرفق جديد في المشروع [{$projectCode}]",
            'blocks' => [
                $this->buildHeader('📎 تم رفع مرفق جديد'),
                $this->buildInfoSection([
                    "*المشروع:*\n{$project->name}",
                    "*الكود:*\n{$projectCode}",
                    "*المجلد:*\n{$folderName}",
                    "*اسم الملف:*\n{$fileName}",
                    "*رفعه:*\n{$uploadedBy->name}"
                ]),
                $this->buildActionsSection([
                    $this->buildActionButton('🔗 عرض المشروع', $projectUrl, 'primary')
                ]),
                $this->buildContextSection()
            ]
        ];
    }
}
