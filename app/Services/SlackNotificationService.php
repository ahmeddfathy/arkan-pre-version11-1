<?php

namespace App\Services;

use App\Models\User;
use App\Models\Project;
use App\Models\ProjectNote;
use App\Models\AdditionalTask;
use App\Services\Slack\ProjectSlackService;
use App\Services\Slack\TaskSlackService;
use App\Services\Slack\RequestSlackService;
use App\Services\Slack\AdditionalTaskSlackService;
use App\Services\Slack\TicketSlackService;
use App\Services\Slack\MeetingSlackService;

class SlackNotificationService
{
    protected $projectSlackService;
    protected $taskSlackService;
    protected $requestSlackService;
    protected $additionalTaskSlackService;
    protected $ticketSlackService;
    protected $meetingSlackService;

    public function __construct(
        ProjectSlackService $projectSlackService,
        TaskSlackService $taskSlackService,
        RequestSlackService $requestSlackService,
        AdditionalTaskSlackService $additionalTaskSlackService,
        TicketSlackService $ticketSlackService,
        MeetingSlackService $meetingSlackService
    ) {
        $this->projectSlackService = $projectSlackService;
        $this->taskSlackService = $taskSlackService;
        $this->requestSlackService = $requestSlackService;
        $this->additionalTaskSlackService = $additionalTaskSlackService;
        $this->ticketSlackService = $ticketSlackService;
        $this->meetingSlackService = $meetingSlackService;
    }

    /**
     * إرسال إشعار منشن في ملاحظة مشروع
     */
    public function sendProjectNoteMention(ProjectNote $note, User $mentionedUser, User $author): bool
    {
        return $this->projectSlackService->sendProjectNoteMention($note, $mentionedUser, $author);
    }

    /**
     * إرسال إشعار عند إضافة مستخدم للمشروع
     */
    public function sendProjectAssignmentNotification(Project $project, User $assignedUser, User $author): bool
    {
        return $this->projectSlackService->sendProjectAssignmentNotification($project, $assignedUser, $author);
    }

    /**
     * إرسال إشعار عند إزالة مستخدم من المشروع
     */
    public function sendProjectRemovalNotification(Project $project, User $removedUser, User $author): bool
    {
        return $this->projectSlackService->sendProjectRemovalNotification($project, $removedUser, $author);
    }

    /**
     * إرسال إشعار عند رفع مرفق جديد في المجلدات الثابتة
     */
    public function sendAttachmentUploadedNotification(Project $project, User $participant, User $uploadedBy, string $folderName, string $fileName): bool
    {
        return $this->projectSlackService->sendAttachmentUploadedNotification($project, $participant, $uploadedBy, $folderName, $fileName);
    }

    /**
     * إرسال إشعار عند تعيين مهمة للمستخدم
     */
    public function sendTaskAssignmentNotification($task, User $assignedUser, User $author): bool
    {
        return $this->taskSlackService->sendTaskAssignmentNotification($task, $assignedUser, $author);
    }

    /**
     * إرسال إشعار طلب عمل إضافي
     */
    public function sendOvertimeRequestNotification($request, User $targetUser, User $author, string $action): bool
    {
        return $this->requestSlackService->sendOvertimeRequestNotification($request, $targetUser, $author, $action);
    }

    /**
     * إرسال إشعار طلب إذن
     */
    public function sendPermissionRequestNotification($request, User $targetUser, User $author, string $action): bool
    {
        return $this->requestSlackService->sendPermissionRequestNotification($request, $targetUser, $author, $action);
    }

    /**
     * إرسال إشعار طلب غياب
     */
    public function sendAbsenceRequestNotification($request, User $targetUser, User $author, string $action): bool
    {
        return $this->requestSlackService->sendAbsenceRequestNotification($request, $targetUser, $author, $action);
    }

    /**
     * إرسال إشعار للمهام الإضافية
     */
    public function sendAdditionalTaskNotification(AdditionalTask $task, User $user, User $author, string $action): bool
    {
        return $this->additionalTaskSlackService->sendAdditionalTaskNotification($task, $user, $author, $action);
    }

    /**
     * إرسال إشعار تعيين تذكرة
     */
    public function sendTicketAssignmentNotification($ticket, User $user, User $assignedBy): bool
    {
        return $this->ticketSlackService->sendTicketAssignmentNotification($ticket, $user, $assignedBy);
    }

    /**
     * إرسال إشعار تعليق تذكرة
     */
    public function sendTicketCommentNotification($ticket, User $user, $comment): bool
    {
        return $this->ticketSlackService->sendTicketCommentNotification($ticket, $user, $comment);
    }

    /**
     * إرسال إشعار حل تذكرة
     */
    public function sendTicketResolvedNotification($ticket, User $user, User $resolvedBy): bool
    {
        return $this->ticketSlackService->sendTicketResolvedNotification($ticket, $user, $resolvedBy);
    }

    /**
     * إرسال إشعار إضافة مستخدم للتذكرة
     */
    public function sendTicketUserAddedNotification($ticket, User $user, User $addedBy): bool
    {
        return $this->ticketSlackService->sendTicketUserAddedNotification($ticket, $user, $addedBy);
    }

    /**
     * إرسال إشعار منشن في تعليق التذكرة
     */
    public function sendTicketMentionNotification($ticket, User $mentionedUser, $comment): bool
    {
        return $this->ticketSlackService->sendTicketMentionNotification($ticket, $mentionedUser, $comment);
    }

    /**
     * إرسال إشعار منشن في الاجتماع
     */
    public function sendMeetingMentionNotification($meeting, User $mentionedUser, User $creator): bool
    {
        return $this->meetingSlackService->sendMeetingMentionNotification($meeting, $mentionedUser, $creator);
    }

    /**
     * إرسال إشعار إنشاء اجتماع
     */
    public function sendMeetingCreatedNotification($meeting, User $participant, User $creator): bool
    {
        return $this->meetingSlackService->sendMeetingCreatedNotification($meeting, $participant, $creator);
    }

    /**
     * إرسال إشعار منشن في ملاحظة الاجتماع
     */
    public function sendMeetingNoteMentionNotification($meeting, User $mentionedUser, User $author, string $noteContent): bool
    {
        return $this->meetingSlackService->sendMeetingNoteMentionNotification($meeting, $mentionedUser, $author, $noteContent);
    }

    /**
     * إرسال إشعار طلب موافقة اجتماع عميل
     */
    public function sendMeetingApprovalRequestNotification($meeting, User $approver, User $creator): bool
    {
        return $this->meetingSlackService->sendMeetingApprovalRequestNotification($meeting, $approver, $creator);
    }

    /**
     * إرسال إشعار نتيجة الموافقة
     */
    public function sendMeetingApprovalResultNotification($meeting, User $creator, User $approver, string $result): bool
    {
        return $this->meetingSlackService->sendMeetingApprovalResultNotification($meeting, $creator, $approver, $result);
    }

    /**
     * إرسال إشعار تحديث وقت الاجتماع
     */
    public function sendMeetingTimeUpdatedNotification($meeting, User $participant, User $updatedBy): bool
    {
        return $this->meetingSlackService->sendMeetingTimeUpdatedNotification($meeting, $participant, $updatedBy);
    }

    /**
     * إرسال إشعار إلغاء المهمة
     */
    public static function sendTaskCancelledNotification(string $slackUserId, string $taskName, string $cancelledBy): bool
    {
        try {
            $slackWebhookUrl = env('SLACK_WEBHOOK_URL');

            if (!$slackWebhookUrl) {
                \Log::warning('SLACK_WEBHOOK_URL not configured');
                return false;
            }

            $message = [
                'text' => "🚫 *تم إلغاء مهمة*",
                'blocks' => [
                    [
                        'type' => 'header',
                        'text' => [
                            'type' => 'plain_text',
                            'text' => '🚫 تم إلغاء مهمة',
                            'emoji' => true
                        ]
                    ],
                    [
                        'type' => 'section',
                        'text' => [
                            'type' => 'mrkdwn',
                            'text' => "تم إلغاء المهمة التالية من قبل منشئ المهمة:"
                        ]
                    ],
                    [
                        'type' => 'section',
                        'fields' => [
                            [
                                'type' => 'mrkdwn',
                                'text' => "*المهمة:*\n{$taskName}"
                            ],
                            [
                                'type' => 'mrkdwn',
                                'text' => "*تم الإلغاء بواسطة:*\n{$cancelledBy}"
                            ]
                        ]
                    ],
                    [
                        'type' => 'context',
                        'elements' => [
                            [
                                'type' => 'mrkdwn',
                                'text' => "⏰ " . now()->timezone('Africa/Cairo')->format('Y-m-d H:i:s')
                            ]
                        ]
                    ]
                ],
                'channel' => $slackUserId
            ];

            $ch = curl_init($slackWebhookUrl);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                \Log::info('Task cancelled notification sent successfully', [
                    'slack_user_id' => $slackUserId,
                    'task_name' => $taskName
                ]);
                return true;
            } else {
                \Log::error('Failed to send task cancelled notification', [
                    'http_code' => $httpCode,
                    'result' => $result
                ]);
                return false;
            }
        } catch (\Exception $e) {
            \Log::error('Error sending task cancelled notification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
}
