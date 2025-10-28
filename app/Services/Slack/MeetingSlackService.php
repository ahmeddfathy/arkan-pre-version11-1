<?php

namespace App\Services\Slack;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Support\Str;

class MeetingSlackService extends BaseSlackService
{
    /**
     * إرسال إشعار منشن في الاجتماع
     */
    public function sendMeetingMentionNotification(Meeting $meeting, User $mentionedUser, User $creator): bool
    {
        $message = $this->buildMeetingMentionMessage($meeting, $mentionedUser, $creator);
        $context = 'إشعار ذكر في اجتماع';
        $this->setNotificationContext($context);

        // استخدام Queue لتقليل الضغط على النظام
        return $this->sendSlackNotification($mentionedUser, $message, $context, true);
    }

    /**
     * إرسال إشعار إنشاء اجتماع للمشاركين
     */
    public function sendMeetingCreatedNotification(Meeting $meeting, User $participant, User $creator): bool
    {
        $message = $this->buildMeetingCreatedMessage($meeting, $participant, $creator);
        $context = 'إشعار اجتماع جديد';
        $this->setNotificationContext($context);

        // استخدام Queue لتقليل الضغط على النظام
        return $this->sendSlackNotification($participant, $message, $context, true);
    }

    /**
     * بناء رسالة منشن الاجتماع
     */
    private function buildMeetingMentionMessage(Meeting $meeting, User $mentionedUser, User $creator): array
    {
        $meetingUrl = url("/meetings/{$meeting->id}");

        // تحديد نوع المنشن
        $isEveryoneMention = strpos($meeting->description, '@everyone') !== false || strpos($meeting->description, '@الجميع') !== false;
        $mentionIcon = $isEveryoneMention ? '👥' : '📢';
        $mentionText = $isEveryoneMention ? 'تم ذكر الجميع في اجتماع جديد' : 'تم ذكرك في اجتماع جديد';

        $meetingTypeText = $meeting->isClientMeeting() ? 'اجتماع عميل' : 'اجتماع داخلي';
        $clientName = $meeting->client ? $meeting->client->name : 'غير محدد';

        return [
            'text' => $mentionText,
            'blocks' => [
                $this->buildHeader("{$mentionIcon} {$mentionText}"),
                $this->buildTextSection("*📅 الاجتماع:*\n{$meeting->title}"),
                $this->buildInfoSection([
                    "*نوع الاجتماع:*\n{$meetingTypeText}",
                    "*المنظم:*\n{$creator->name}",
                    "*الموعد:*\n{$meeting->start_time->format('d/m/Y - H:i')}",
                    "*المدة:*\n{$meeting->start_time->diffInMinutes($meeting->end_time)} دقيقة"
                ]),
                $this->buildInfoSection([
                    "*المكان:*\n" . ($meeting->location ?: 'غير محدد'),
                    "*العميل:*\n{$clientName}",
                    "*نوع المنشن:*\n" . ($isEveryoneMention ? '👥 منشن جماعي' : '👤 منشن فردي')
                ]),
                $this->buildTextSection("*الوصف:*\n" . \Str::limit($meeting->description, 200)),
                $this->buildActionsSection([
                    $this->buildActionButton('🔗 عرض الاجتماع', $meetingUrl)
                ]),
                $this->buildContextSection()
            ]
        ];
    }

    /**
     * بناء رسالة إنشاء الاجتماع
     */
    private function buildMeetingCreatedMessage(Meeting $meeting, User $participant, User $creator): array
    {
        $meetingUrl = url("/meetings/{$meeting->id}");
        $meetingTypeText = $meeting->isClientMeeting() ? 'اجتماع عميل' : 'اجتماع داخلي';
        $clientName = $meeting->client ? $meeting->client->name : 'غير محدد';

        return [
            'text' => "تم إضافتك لاجتماع جديد",
            'blocks' => [
                $this->buildHeader('📅 تم إضافتك لاجتماع جديد'),
                $this->buildTextSection("*📋 الاجتماع:*\n{$meeting->title}"),
                $this->buildInfoSection([
                    "*نوع الاجتماع:*\n{$meetingTypeText}",
                    "*المنظم:*\n{$creator->name}",
                    "*الموعد:*\n{$meeting->start_time->format('d/m/Y - H:i')}",
                    "*ينتهي:*\n{$meeting->end_time->format('d/m/Y - H:i')}"
                ]),
                $this->buildInfoSection([
                    "*المكان:*\n" . ($meeting->location ?: 'غير محدد'),
                    "*العميل:*\n{$clientName}",
                    "*المدة:*\n{$meeting->start_time->diffInMinutes($meeting->end_time)} دقيقة"
                ]),
                $this->buildTextSection("*الوصف:*\n" . ($meeting->description ? \Str::limit($meeting->description, 200) : 'لا يوجد وصف')),
                $this->buildActionsSection([
                    $this->buildActionButton('🔗 عرض الاجتماع', $meetingUrl)
                ]),
                $this->buildContextSection()
            ]
        ];
    }

    /**
     * إرسال إشعار منشن في ملاحظة الاجتماع
     */
    public function sendMeetingNoteMentionNotification(Meeting $meeting, User $mentionedUser, User $author, string $noteContent): bool
    {
        $message = $this->buildMeetingNoteMentionMessage($meeting, $mentionedUser, $author, $noteContent);
        $result = $this->sendDirectMessage($mentionedUser, $message);
        $this->setNotificationContext('إشعار ذكر في ملاحظة اجتماع');
        return $result;
    }

    /**
     * بناء رسالة منشن ملاحظة الاجتماع
     */
    private function buildMeetingNoteMentionMessage(Meeting $meeting, User $mentionedUser, User $author, string $noteContent): array
    {
        $meetingUrl = url("/meetings/{$meeting->id}");
        $notePreview = Str::limit($noteContent, 150);

        // تحديد نوع المنشن
        $isEveryoneMention = strpos($noteContent, '@everyone') !== false || strpos($noteContent, '@الجميع') !== false;
        $mentionIcon = $isEveryoneMention ? '👥' : '📢';
        $mentionText = $isEveryoneMention ? 'تم ذكر الجميع في ملاحظة اجتماع' : 'تم ذكرك في ملاحظة اجتماع';

        $meetingTypeText = $meeting->isClientMeeting() ? 'اجتماع عميل' : 'اجتماع داخلي';
        $clientName = $meeting->client ? $meeting->client->name : 'غير محدد';

        return [
            'text' => $mentionText,
            'blocks' => [
                $this->buildHeader("{$mentionIcon} {$mentionText}"),
                $this->buildTextSection("*📅 الاجتماع:*\n{$meeting->title}"),
                $this->buildInfoSection([
                    "*نوع الاجتماع:*\n{$meetingTypeText}",
                    "*كاتب الملاحظة:*\n{$author->name}",
                    "*تاريخ الاجتماع:*\n{$meeting->start_time->format('d/m/Y - H:i')}",
                    "*المكان:*\n" . ($meeting->location ?: 'غير محدد')
                ]),
                $this->buildInfoSection([
                    "*العميل:*\n{$clientName}",
                    "*نوع المنشن:*\n" . ($isEveryoneMention ? '👥 منشن جماعي' : '👤 منشن فردي'),
                    "*وقت الملاحظة:*\n" . now()->format('d/m/Y - H:i')
                ]),
                $this->buildTextSection("*الملاحظة:*\n{$notePreview}"),
                $this->buildActionsSection([
                    $this->buildActionButton('🔗 عرض الاجتماع', $meetingUrl)
                ]),
                $this->buildContextSection()
            ]
        ];
    }

    /**
     * إرسال إشعار طلب موافقة اجتماع عميل
     */
    public function sendMeetingApprovalRequestNotification(Meeting $meeting, User $approver, User $creator): bool
    {
        $message = $this->buildMeetingApprovalRequestMessage($meeting, $approver, $creator);
        $result = $this->sendDirectMessage($approver, $message);
        $this->setNotificationContext('إشعار طلب موافقة اجتماع');
        return $result;
    }

    /**
     * إرسال إشعار نتيجة الموافقة
     */
    public function sendMeetingApprovalResultNotification(Meeting $meeting, User $creator, User $approver, string $result): bool
    {
        $message = $this->buildMeetingApprovalResultMessage($meeting, $creator, $approver, $result);
        $notificationResult = $this->sendDirectMessage($creator, $message);
        $this->setNotificationContext('إشعار رد على موافقة اجتماع');
        return $notificationResult;
    }

    /**
     * إرسال إشعار تحديث وقت الاجتماع
     */
    public function sendMeetingTimeUpdatedNotification(Meeting $meeting, User $participant, User $updatedBy): bool
    {
        $message = $this->buildMeetingTimeUpdatedMessage($meeting, $participant, $updatedBy);
        $result = $this->sendDirectMessage($participant, $message);
        $this->setNotificationContext('إشعار تحديث وقت اجتماع');
        return $result;
    }

    /**
     * بناء رسالة طلب موافقة اجتماع
     */
    private function buildMeetingApprovalRequestMessage(Meeting $meeting, User $approver, User $creator): array
    {
        $meetingUrl = url("/meetings/{$meeting->id}");
        $clientName = $meeting->client ? $meeting->client->name : 'غير محدد';
        $projectName = $meeting->project ? $meeting->project->name : 'غير محدد';

        return [
            'text' => 'طلب موافقة على اجتماع عميل',
            'blocks' => [
                $this->buildHeader('🔔 طلب موافقة على اجتماع عميل'),
                $this->buildTextSection("*📋 الاجتماع:*\n{$meeting->title}"),
                $this->buildInfoSection([
                    "*طالب الموافقة:*\n{$creator->name}",
                    "*العميل:*\n{$clientName}",
                    "*المشروع:*\n{$projectName}",
                    "*الموعد:*\n{$meeting->start_time->format('d/m/Y - H:i')}"
                ]),
                $this->buildInfoSection([
                    "*ينتهي:*\n{$meeting->end_time->format('d/m/Y - H:i')}",
                    "*المكان:*\n" . ($meeting->location ?: 'غير محدد'),
                    "*الحالة:*\n⏳ في انتظار الموافقة"
                ]),
                $this->buildTextSection("*الوصف:*\n" . ($meeting->description ? Str::limit($meeting->description, 200) : 'لا يوجد وصف')),
                $this->buildActionsSection([
                    $this->buildActionButton('✅ عرض الطلب', $meetingUrl)
                ]),
                $this->buildContextSection()
            ]
        ];
    }

    /**
     * بناء رسالة نتيجة الموافقة
     */
    private function buildMeetingApprovalResultMessage(Meeting $meeting, User $creator, User $approver, string $result): array
    {
        $meetingUrl = url("/meetings/{$meeting->id}");

        $statusIcon = match($result) {
            'approved' => '✅',
            'rejected' => '❌',
            'time_updated' => '⏰',
            default => '📅'
        };

        $statusText = match($result) {
            'approved' => 'تم الموافقة على اجتماعك',
            'rejected' => 'تم رفض اجتماعك',
            'time_updated' => 'تم تحديث وقت اجتماعك',
            default => 'تم تحديث اجتماعك'
        };

        return [
            'text' => $statusText,
            'blocks' => [
                $this->buildHeader("{$statusIcon} {$statusText}"),
                $this->buildTextSection("*📋 الاجتماع:*\n{$meeting->title}"),
                $this->buildInfoSection([
                    "*المسؤول:*\n{$approver->name}",
                    "*الموعد الجديد:*\n{$meeting->start_time->format('d/m/Y - H:i')}",
                    "*ينتهي:*\n{$meeting->end_time->format('d/m/Y - H:i')}",
                    "*الحالة:*\n{$meeting->approval_status_arabic}"
                ]),
                $meeting->approval_notes ? $this->buildTextSection("*ملاحظات:*\n{$meeting->approval_notes}") : null,
                $this->buildActionsSection([
                    $this->buildActionButton('🔗 عرض الاجتماع', $meetingUrl)
                ]),
                $this->buildContextSection()
            ]
        ];
    }

    /**
     * بناء رسالة تحديث وقت الاجتماع
     */
    private function buildMeetingTimeUpdatedMessage(Meeting $meeting, User $participant, User $updatedBy): array
    {
        $meetingUrl = url("/meetings/{$meeting->id}");

        return [
            'text' => 'تم تحديث وقت الاجتماع',
            'blocks' => [
                $this->buildHeader('⏰ تم تحديث وقت الاجتماع'),
                $this->buildTextSection("*📋 الاجتماع:*\n{$meeting->title}"),
                $this->buildInfoSection([
                    "*تم التحديث بواسطة:*\n{$updatedBy->name}",
                    "*الموعد الجديد:*\n{$meeting->start_time->format('d/m/Y - H:i')}",
                    "*ينتهي:*\n{$meeting->end_time->format('d/m/Y - H:i')}",
                    "*المكان:*\n" . ($meeting->location ?: 'غير محدد')
                ]),
                $meeting->approval_notes ? $this->buildTextSection("*ملاحظات التحديث:*\n{$meeting->approval_notes}") : null,
                $this->buildActionsSection([
                    $this->buildActionButton('🔗 عرض الاجتماع', $meetingUrl)
                ]),
                $this->buildContextSection()
            ]
        ];
    }
}
