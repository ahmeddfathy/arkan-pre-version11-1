<?php

namespace App\Services\Notifications;

use App\Models\Meeting;
use App\Models\User;
use App\Models\Notification;
use App\Services\Slack\MeetingSlackService;
use App\Services\Notifications\Traits\HasFirebaseNotification;
use Illuminate\Support\Facades\Log;

class MeetingNotificationService
{
    use HasFirebaseNotification;

    protected $meetingSlackService;

    public function __construct(MeetingSlackService $meetingSlackService)
    {
        $this->meetingSlackService = $meetingSlackService;
    }

    /**
     * إشعار المشاركين عند إنشاء اجتماع
     */
    public function notifyMeetingParticipants(Meeting $meeting, array $participantIds, User $creator): void
    {
        try {
            foreach ($participantIds as $participantId) {
                $participant = User::find($participantId);
                if (!$participant || $participant->id === $creator->id) {
                    continue; // تجاهل المنظم
                }

                $notificationData = [
                    'message' => "تم إضافتك لاجتماع جديد: {$meeting->title}",
                    'meeting_details' => [
                        'id' => $meeting->id,
                        'title' => $meeting->title,
                        'type' => $meeting->type,
                        'start_time' => $meeting->start_time->format('d/m/Y H:i'),
                        'end_time' => $meeting->end_time->format('d/m/Y H:i'),
                        'location' => $meeting->location,
                        'creator' => $creator->name
                    ]
                ];

                // إنشاء الإشعار في قاعدة البيانات
                Notification::create([
                    'user_id' => $participant->id,
                    'type' => 'meeting_participant',
                    'data' => $notificationData,
                    'related_id' => $meeting->id
                ]);

                // إرسال إشعار Firebase
                $this->sendAdditionalFirebaseNotification(
                    $participant,
                    $notificationData['message'],
                    'اجتماع جديد',
                    route('meetings.show', $meeting),
                    'meeting_participant'
                );

                // إرسال إشعار Slack
                if ($participant->slack_user_id) {
                    $this->meetingSlackService->sendMeetingCreatedNotification(
                        $meeting,
                        $participant,
                        $creator
                    );
                }
            }

            Log::info('Meeting participant notifications sent', [
                'meeting_id' => $meeting->id,
                'participants' => $participantIds,
                'creator' => $creator->id
            ]);

        } catch (\Exception $e) {
            Log::error('Error in MeetingNotificationService::notifyMeetingParticipants - ' . $e->getMessage());
        }
    }

    /**
     * إشعار المستخدمين المذكورين في وصف الاجتماع
     */
    public function notifyMentionedUsers(Meeting $meeting, User $creator): void
    {
        try {
            if (!$meeting->mentions || empty($meeting->mentions)) {
                return;
            }

            $mentionedUsers = User::whereIn('id', $meeting->mentions)->get();

            foreach ($mentionedUsers as $mentionedUser) {
                // لا نرسل إشعار للمنظم
                if ($mentionedUser->id === $creator->id) {
                    continue;
                }

                // تحديد نوع المنشن
                $isEveryoneMention = strpos($meeting->description, '@everyone') !== false || strpos($meeting->description, '@الجميع') !== false;
                $mentionType = $isEveryoneMention ? 'تم ذكر الجميع' : 'تم ذكرك';

                $notificationData = [
                    'message' => "{$mentionType} في اجتماع: {$meeting->title}",
                    'mention_details' => [
                        'mentioned_by' => $creator->name,
                        'meeting_id' => $meeting->id,
                        'meeting_title' => $meeting->title,
                        'meeting_type' => $meeting->type,
                        'start_time' => $meeting->start_time->format('d/m/Y H:i'),
                        'location' => $meeting->location,
                        'mention_type' => $isEveryoneMention ? 'everyone' : 'individual'
                    ]
                ];

                // إنشاء الإشعار في قاعدة البيانات
                Notification::create([
                    'user_id' => $mentionedUser->id,
                    'type' => 'meeting_mention',
                    'data' => $notificationData,
                    'related_id' => $meeting->id
                ]);

                // إرسال إشعار Firebase
                $this->sendAdditionalFirebaseNotification(
                    $mentionedUser,
                    $notificationData['message'],
                    'تم ذكرك في اجتماع',
                    route('meetings.show', $meeting),
                    'meeting_mention'
                );

                // إرسال إشعار Slack
                if ($mentionedUser->slack_user_id) {
                    $this->meetingSlackService->sendMeetingMentionNotification(
                        $meeting,
                        $mentionedUser,
                        $creator
                    );
                }
            }

            Log::info('Meeting mention notifications sent', [
                'meeting_id' => $meeting->id,
                'mentioned_users' => $meeting->mentions,
                'creator' => $creator->id
            ]);

        } catch (\Exception $e) {
            Log::error('Error in MeetingNotificationService::notifyMentionedUsers - ' . $e->getMessage());
        }
    }

    /**
     * إشعار المستخدمين المذكورين في ملاحظة اجتماع
     */
    public function notifyMentionedUsersInNote(Meeting $meeting, array $mentionIds, User $author, string $noteContent): void
    {
        try {
            if (empty($mentionIds)) {
                return;
            }

            $mentionedUsers = User::whereIn('id', $mentionIds)->get();

            foreach ($mentionedUsers as $mentionedUser) {
                // لا نرسل إشعار لكاتب الملاحظة
                if ($mentionedUser->id === $author->id) {
                    continue;
                }

                // تحديد نوع المنشن
                $isEveryoneMention = strpos($noteContent, '@everyone') !== false || strpos($noteContent, '@الجميع') !== false;
                $mentionType = $isEveryoneMention ? 'تم ذكر الجميع' : 'تم ذكرك';

                $notificationData = [
                    'message' => "{$mentionType} في ملاحظة على الاجتماع: {$meeting->title}",
                    'mention_details' => [
                        'mentioned_by' => $author->name,
                        'note_preview' => \Str::limit($noteContent, 100),
                        'meeting_id' => $meeting->id,
                        'meeting_title' => $meeting->title,
                        'meeting_type' => $meeting->type,
                        'start_time' => $meeting->start_time->format('d/m/Y H:i'),
                        'location' => $meeting->location,
                        'mention_type' => $isEveryoneMention ? 'everyone' : 'individual'
                    ]
                ];

                // إنشاء الإشعار في قاعدة البيانات
                Notification::create([
                    'user_id' => $mentionedUser->id,
                    'type' => 'meeting_note_mention',
                    'data' => $notificationData,
                    'related_id' => $meeting->id
                ]);

                // إرسال إشعار Firebase
                $this->sendAdditionalFirebaseNotification(
                    $mentionedUser,
                    $notificationData['message'],
                    'تم ذكرك في ملاحظة اجتماع',
                    route('meetings.show', $meeting),
                    'meeting_note_mention'
                );

                // إرسال إشعار Slack
                if ($mentionedUser->slack_user_id) {
                    $this->meetingSlackService->sendMeetingNoteMentionNotification(
                        $meeting,
                        $mentionedUser,
                        $author,
                        $noteContent
                    );
                }
            }

            Log::info('Meeting note mention notifications sent', [
                'meeting_id' => $meeting->id,
                'mentioned_users' => $mentionIds,
                'author' => $author->id
            ]);

        } catch (\Exception $e) {
            Log::error('Error in MeetingNotificationService::notifyMentionedUsersInNote - ' . $e->getMessage());
        }
    }

    /**
     * إشعار المسؤولين عن اجتماعات العملاء للموافقة
     */
    public function notifyClientMeetingApprovers(Meeting $meeting, User $creator): void
    {
        try {
            // جلب المستخدمين الذين لديهم صلاحية schedule_client_meetings
            $approvers = User::permission('schedule_client_meetings')->get();

            foreach ($approvers as $approver) {
                // لا نرسل إشعار لمنشئ الاجتماع
                if ($approver->id === $creator->id) {
                    continue;
                }

                $notificationData = [
                    'message' => "طلب موافقة على اجتماع عميل: {$meeting->title}",
                    'approval_details' => [
                        'meeting_id' => $meeting->id,
                        'meeting_title' => $meeting->title,
                        'creator' => $creator->name,
                        'client_name' => $meeting->client ? $meeting->client->name : 'غير محدد',
                        'project_name' => $meeting->project ? $meeting->project->name : 'غير محدد',
                        'start_time' => $meeting->start_time->format('d/m/Y H:i'),
                        'end_time' => $meeting->end_time->format('d/m/Y H:i'),
                        'location' => $meeting->location
                    ]
                ];

                // إنشاء الإشعار في قاعدة البيانات
                Notification::create([
                    'user_id' => $approver->id,
                    'type' => 'meeting_approval_request',
                    'data' => $notificationData,
                    'related_id' => $meeting->id
                ]);

                // إرسال إشعار Firebase
                $this->sendAdditionalFirebaseNotification(
                    $approver,
                    $notificationData['message'],
                    'طلب موافقة اجتماع',
                    route('meetings.show', $meeting),
                    'meeting_approval_request'
                );

                // إرسال إشعار Slack
                if ($approver->slack_user_id) {
                    $this->meetingSlackService->sendMeetingApprovalRequestNotification(
                        $meeting,
                        $approver,
                        $creator
                    );
                }
            }

            Log::info('Meeting approval request notifications sent', [
                'meeting_id' => $meeting->id,
                'creator' => $creator->id,
                'approvers_count' => $approvers->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Error in MeetingNotificationService::notifyClientMeetingApprovers - ' . $e->getMessage());
        }
    }

    /**
     * إشعار منشئ الاجتماع بنتيجة الموافقة
     */
    public function notifyMeetingApprovalResult(Meeting $meeting, string $result, User $approver): void
    {
        try {
            $creator = User::find($meeting->created_by);
            if (!$creator) return;

            $statusText = match($result) {
                'approved' => 'تم الموافقة على',
                'rejected' => 'تم رفض',
                'time_updated' => 'تم تحديث وقت وقبول',
                default => 'تم تحديث'
            };

            $notificationData = [
                'message' => "{$statusText} اجتماعك: {$meeting->title}",
                'approval_result' => [
                    'meeting_id' => $meeting->id,
                    'meeting_title' => $meeting->title,
                    'result' => $result,
                    'approved_by' => $approver->name,
                    'approval_notes' => $meeting->approval_notes,
                    'new_start_time' => $meeting->start_time->format('d/m/Y H:i'),
                    'new_end_time' => $meeting->end_time->format('d/m/Y H:i')
                ]
            ];

            // إنشاء الإشعار في قاعدة البيانات
            Notification::create([
                'user_id' => $creator->id,
                'type' => 'meeting_approval_result',
                'data' => $notificationData,
                'related_id' => $meeting->id
            ]);

            // إرسال إشعار Firebase
            $this->sendAdditionalFirebaseNotification(
                $creator,
                $notificationData['message'],
                'نتيجة طلب الاجتماع',
                route('meetings.show', $meeting),
                'meeting_approval_result'
            );

            // إرسال إشعار Slack
            if ($creator->slack_user_id) {
                $this->meetingSlackService->sendMeetingApprovalResultNotification(
                    $meeting,
                    $creator,
                    $approver,
                    $result
                );
            }

            Log::info('Meeting approval result notification sent', [
                'meeting_id' => $meeting->id,
                'creator' => $creator->id,
                'approver' => $approver->id,
                'result' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Error in MeetingNotificationService::notifyMeetingApprovalResult - ' . $e->getMessage());
        }
    }

    /**
     * إشعار المشاركين بتحديث وقت الاجتماع
     */
    public function notifyMeetingTimeUpdated(Meeting $meeting, array $participantIds, User $updatedBy): void
    {
        try {
            foreach ($participantIds as $participantId) {
                $participant = User::find($participantId);
                if (!$participant || $participant->id === $updatedBy->id) {
                    continue;
                }

                $notificationData = [
                    'message' => "تم تحديث وقت الاجتماع: {$meeting->title}",
                    'time_update_details' => [
                        'meeting_id' => $meeting->id,
                        'meeting_title' => $meeting->title,
                        'updated_by' => $updatedBy->name,
                        'new_start_time' => $meeting->start_time->format('d/m/Y H:i'),
                        'new_end_time' => $meeting->end_time->format('d/m/Y H:i'),
                        'location' => $meeting->location,
                        'update_notes' => $meeting->approval_notes
                    ]
                ];

                // إنشاء الإشعار في قاعدة البيانات
                Notification::create([
                    'user_id' => $participant->id,
                    'type' => 'meeting_time_updated',
                    'data' => $notificationData,
                    'related_id' => $meeting->id
                ]);

                // إرسال إشعار Firebase
                $this->sendAdditionalFirebaseNotification(
                    $participant,
                    $notificationData['message'],
                    'تحديث وقت الاجتماع',
                    route('meetings.show', $meeting),
                    'meeting_time_updated'
                );

                // إرسال إشعار Slack
                if ($participant->slack_user_id) {
                    $this->meetingSlackService->sendMeetingTimeUpdatedNotification(
                        $meeting,
                        $participant,
                        $updatedBy
                    );
                }
            }

            Log::info('Meeting time update notifications sent', [
                'meeting_id' => $meeting->id,
                'participants' => $participantIds,
                'updated_by' => $updatedBy->id
            ]);

        } catch (\Exception $e) {
            Log::error('Error in MeetingNotificationService::notifyMeetingTimeUpdated - ' . $e->getMessage());
        }
    }

    /**
     * إشعار المشاركين بإلغاء الاجتماع
     */
    public function notifyMeetingCancelled(Meeting $meeting, array $participantIds, User $cancelledBy): void
    {
        try {
            foreach ($participantIds as $participantId) {
                $participant = User::find($participantId);
                if (!$participant || $participant->id === $cancelledBy->id) {
                    continue;
                }

                $notificationData = [
                    'message' => "تم إلغاء الاجتماع: {$meeting->title}",
                    'cancellation_details' => [
                        'meeting_id' => $meeting->id,
                        'meeting_title' => $meeting->title,
                        'cancelled_by' => $cancelledBy->name,
                        'original_start_time' => $meeting->start_time->format('d/m/Y H:i'),
                        'original_end_time' => $meeting->end_time->format('d/m/Y H:i'),
                        'location' => $meeting->location,
                        'type' => $meeting->type
                    ]
                ];

                // إنشاء الإشعار في قاعدة البيانات
                Notification::create([
                    'user_id' => $participant->id,
                    'type' => 'meeting_cancelled',
                    'data' => $notificationData,
                    'related_id' => $meeting->id
                ]);

                // إرسال إشعار Firebase
                $this->sendAdditionalFirebaseNotification(
                    $participant,
                    $notificationData['message'],
                    'إلغاء اجتماع',
                    route('meetings.show', $meeting),
                    'meeting_cancelled'
                );

                // إرسال إشعار Slack
                if ($participant->slack_user_id) {
                    $this->meetingSlackService->sendMeetingCancelledNotification(
                        $meeting,
                        $participant,
                        $cancelledBy
                    );
                }
            }

            Log::info('Meeting cancellation notifications sent', [
                'meeting_id' => $meeting->id,
                'participants' => $participantIds,
                'cancelled_by' => $cancelledBy->id
            ]);

        } catch (\Exception $e) {
            Log::error('Error in MeetingNotificationService::notifyMeetingCancelled - ' . $e->getMessage());
        }
    }
}
