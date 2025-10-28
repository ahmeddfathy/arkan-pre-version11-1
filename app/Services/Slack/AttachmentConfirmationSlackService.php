<?php

namespace App\Services\Slack;

use App\Models\AttachmentConfirmation;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class AttachmentConfirmationSlackService extends BaseSlackService
{
    /**
     * إرسال إشعار للمسؤول عند طلب تأكيد المرفق
     */
    public function sendConfirmationRequest(AttachmentConfirmation $confirmation): bool
    {
        try {
            $confirmation->load(['manager', 'requester', 'attachment', 'project']);

            $manager = $confirmation->manager;

            if (!$manager) {
                return false;
            }

            $message = $this->buildConfirmationRequestMessage($confirmation);
            $context = 'طلب تأكيد مرفق';
            $this->setNotificationContext($context);

            return $this->sendSlackNotification($manager, $message, $context, true);

        } catch (\Exception $e) {
            Log::error('Failed to send attachment confirmation request Slack notification', [
                'confirmation_id' => $confirmation->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * إرسال إشعار لمقدم الطلب عند التأكيد أو الرفض
     */
    public function sendConfirmationResponse(AttachmentConfirmation $confirmation, string $action): bool
    {
        try {
            $confirmation->load(['manager', 'requester', 'attachment', 'project']);

            $requester = $confirmation->requester;

            if (!$requester) {
                return false;
            }

            $message = $this->buildConfirmationResponseMessage($confirmation, $action);
            $context = $action === 'confirmed' ? 'تأكيد مرفق' : 'رفض مرفق';
            $this->setNotificationContext($context);

            return $this->sendSlackNotification($requester, $message, $context, true);

        } catch (\Exception $e) {
            Log::error('Failed to send attachment confirmation response Slack notification', [
                'confirmation_id' => $confirmation->id,
                'action' => $action,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * بناء رسالة طلب التأكيد للمسؤول
     */
    private function buildConfirmationRequestMessage(AttachmentConfirmation $confirmation): array
    {
        $requester = $confirmation->requester;
        $attachment = $confirmation->attachment;
        $project = $confirmation->project;

        $projectInfo = $project->code
            ? "[{$project->code}] {$project->name}"
            : $project->name;

        $blocks = [
            $this->buildHeader('📎 طلب تأكيد مرفق'),
            $this->buildInfoSection([
                "*المشروع:*\n{$projectInfo}",
                "*طلبه:*\n{$requester->name}"
            ])
        ];

        // معلومات المرفق
        $attachmentInfo = [];

        if ($attachment->file_name) {
            $attachmentInfo[] = "*اسم الملف:*\n{$attachment->file_name}";
        }

        if ($attachment->title) {
            $attachmentInfo[] = "*العنوان:*\n{$attachment->title}";
        }

        if (!empty($attachmentInfo)) {
            $blocks[] = $this->buildInfoSection($attachmentInfo);
        }

        // الوصف
        if ($attachment->description) {
            $blocks[] = $this->buildTextSection("*الوصف:*\n{$attachment->description}");
        }

        // نوع المرفق
        $attachmentType = $attachment->file_path ? '📁 ملف' : '🔗 رابط';
        $blocks[] = $this->buildTextSection("*النوع:* {$attachmentType}");

        // أزرار الإجراءات
        $confirmationsUrl = url('/attachment-confirmations');
        $blocks[] = $this->buildActionsSection([
            $this->buildActionButton('✅ عرض الطلبات', $confirmationsUrl, 'primary')
        ]);

        $blocks[] = $this->buildContextSection();

        return [
            'text' => "طلب تأكيد مرفق في مشروع {$project->name}",
            'blocks' => $blocks
        ];
    }

    /**
     * بناء رسالة رد التأكيد لمقدم الطلب
     */
    private function buildConfirmationResponseMessage(AttachmentConfirmation $confirmation, string $action): array
    {
        $manager = $confirmation->manager;
        $attachment = $confirmation->attachment;
        $project = $confirmation->project;

        $projectInfo = $project->code
            ? "[{$project->code}] {$project->name}"
            : $project->name;

        // تحديد الأيقونة والنص حسب الإجراء
        if ($action === 'confirmed') {
            $header = '✅ تم تأكيد المرفق';
            $actionText = 'تأكيد';
            $actionIcon = '✅';
        } else {
            $header = '❌ تم رفض المرفق';
            $actionText = 'رفض';
            $actionIcon = '❌';
        }

        $blocks = [
            $this->buildHeader($header),
            $this->buildInfoSection([
                "*المشروع:*\n{$projectInfo}",
                "*المسؤول:*\n{$manager->name}"
            ])
        ];

        // معلومات المرفق
        $attachmentInfo = [];

        if ($attachment->file_name) {
            $attachmentInfo[] = "*اسم الملف:*\n{$attachment->file_name}";
        }

        if ($attachment->title) {
            $attachmentInfo[] = "*العنوان:*\n{$attachment->title}";
        }

        if (!empty($attachmentInfo)) {
            $blocks[] = $this->buildInfoSection($attachmentInfo);
        }

        // الحالة
        $blocks[] = $this->buildTextSection("*الحالة:* {$actionIcon} {$actionText}");

        // الملاحظات
        if ($confirmation->notes) {
            $blocks[] = $this->buildTextSection("📝 *ملاحظات:*\n{$confirmation->notes}");
        }

        // زر عرض الطلبات
        $myRequestsUrl = url('/attachment-confirmations/my-requests');
        $blocks[] = $this->buildActionsSection([
            $this->buildActionButton('📋 طلباتي', $myRequestsUrl)
        ]);

        $blocks[] = $this->buildContextSection();

        return [
            'text' => "{$actionText} المرفق في مشروع {$project->name}",
            'blocks' => $blocks
        ];
    }
}

