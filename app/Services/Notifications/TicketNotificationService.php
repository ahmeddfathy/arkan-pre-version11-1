<?php

namespace App\Services\Notifications;

use App\Models\Notification;
use App\Models\ClientTicket;
use App\Models\User;
use App\Models\TicketComment;
use App\Services\Notifications\Traits\HasFirebaseNotification;
use App\Services\SlackNotificationService;
use Illuminate\Support\Facades\Log;

class TicketNotificationService
{
    use HasFirebaseNotification;

    protected $slackNotificationService;

    public function __construct(SlackNotificationService $slackNotificationService)
    {
        $this->slackNotificationService = $slackNotificationService;
    }

    /**
     * إشعار المستخدمين عند تعيينهم لتذكرة
     */
    public function notifyUsersAssigned(ClientTicket $ticket, array $userIds, User $assignedBy): void
    {
        try {
            foreach ($userIds as $userId) {
                $user = User::find($userId);
                if (!$user) continue;

                $notificationData = [
                    'message' => "تم تعيينك للتذكرة: {$ticket->title}",
                    'ticket_details' => [
                        'id' => $ticket->id,
                        'ticket_number' => $ticket->ticket_number,
                        'title' => $ticket->title,
                        'priority' => $ticket->priority_arabic,
                        'department' => $ticket->department_arabic,
                        'assigned_by' => $assignedBy->name
                    ]
                ];

                // إنشاء الإشعار في قاعدة البيانات
                Notification::create([
                    'user_id' => $userId,
                    'type' => 'ticket_assigned',
                    'data' => $notificationData,
                    'related_id' => $ticket->id
                ]);

                // إرسال إشعار Firebase
                $this->sendAdditionalFirebaseNotification(
                    $user,
                    $notificationData['message'],
                    'تعيين تذكرة جديدة',
                    route('client-tickets.show', $ticket),
                    'ticket_assigned'
                );

                // إرسال إشعار Slack
                if ($user->slack_user_id) {
                    $this->slackNotificationService->sendTicketAssignmentNotification(
                        $ticket,
                        $user,
                        $assignedBy
                    );
                }
            }

            Log::info('Ticket assignment notifications sent', [
                'ticket_id' => $ticket->id,
                'assigned_users' => $userIds,
                'assigned_by' => $assignedBy->id
            ]);

        } catch (\Exception $e) {
            Log::error('Error in TicketNotificationService::notifyUsersAssigned - ' . $e->getMessage());
        }
    }

    /**
     * إشعار المستخدمين المعينين عند إضافة تعليق - تم إلغاؤه لصالح إشعارات المنشن فقط
     * الآن يتم إرسال الإشعارات للمذكورين في المنشن فقط
     */
    public function notifyOnComment(ClientTicket $ticket, TicketComment $comment): void
    {
        // تم إلغاء الإشعارات العامة للتعليقات
        // الآن يتم الاعتماد فقط على notifyMentionedUsers() للمذكورين بالمنشن

        Log::info('Comment added - notifications handled via mentions only', [
            'ticket_id' => $ticket->id,
            'comment_id' => $comment->id,
            'comment_by' => $comment->user_id,
            'mentions_count' => count($comment->mentions ?? [])
        ]);
    }

    /**
     * إشعار عند إضافة مستخدم جديد للتذكرة
     */
    public function notifyUserAdded(ClientTicket $ticket, User $addedUser, User $addedBy): void
    {
        try {
            $notificationData = [
                'message' => "تم إضافتك لفريق التذكرة: {$ticket->title}",
                'ticket_details' => [
                    'id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'title' => $ticket->title,
                    'priority' => $ticket->priority_arabic,
                    'department' => $ticket->department_arabic,
                    'added_by' => $addedBy->name
                ]
            ];

            // إنشاء الإشعار في قاعدة البيانات
            Notification::create([
                'user_id' => $addedUser->id,
                'type' => 'ticket_user_added',
                'data' => $notificationData,
                'related_id' => $ticket->id
            ]);

            // إرسال إشعار Firebase
            $this->sendAdditionalFirebaseNotification(
                $addedUser,
                $notificationData['message'],
                'إضافة لفريق التذكرة',
                route('client-tickets.show', $ticket),
                'ticket_user_added'
            );

            // إرسال إشعار Slack
            if ($addedUser->slack_user_id) {
                $this->slackNotificationService->sendTicketUserAddedNotification(
                    $ticket,
                    $addedUser,
                    $addedBy
                );
            }

            Log::info('User added to ticket notification sent', [
                'ticket_id' => $ticket->id,
                'added_user' => $addedUser->id,
                'added_by' => $addedBy->id
            ]);

        } catch (\Exception $e) {
            Log::error('Error in TicketNotificationService::notifyUserAdded - ' . $e->getMessage());
        }
    }

    /**
     * إشعار عند حل التذكرة
     */
    public function notifyTicketResolved(ClientTicket $ticket, User $resolvedBy): void
    {
        try {
            // إشعار جميع المستخدمين المعينين
            $assignedUsers = $ticket->activeAssignments()->with('user')->get();

            foreach ($assignedUsers as $assignment) {
                $user = $assignment->user;

                // لا نرسل إشعار للشخص الذي حل التذكرة
                if ($user->id === $resolvedBy->id) {
                    continue;
                }

                $notificationData = [
                    'message' => "تم حل التذكرة: {$ticket->title}",
                    'resolution_details' => [
                        'resolved_by' => $resolvedBy->name,
                        'ticket_id' => $ticket->id,
                        'ticket_number' => $ticket->ticket_number,
                        'ticket_title' => $ticket->title,
                        'resolution_notes' => $ticket->resolution_notes
                    ]
                ];

                Notification::create([
                    'user_id' => $user->id,
                    'type' => 'ticket_resolved',
                    'data' => $notificationData,
                    'related_id' => $ticket->id
                ]);

                $this->sendAdditionalFirebaseNotification(
                    $user,
                    $notificationData['message'],
                    'تم حل التذكرة',
                    route('client-tickets.show', $ticket),
                    'ticket_resolved'
                );

                // إرسال إشعار Slack
                if ($user->slack_user_id) {
                    $this->slackNotificationService->sendTicketResolvedNotification(
                        $ticket,
                        $user,
                        $resolvedBy
                    );
                }
            }

            // إشعار منشئ التذكرة
            if ($ticket->created_by !== $resolvedBy->id) {
                $creator = User::find($ticket->created_by);
                if ($creator) {
                    $notificationData = [
                        'message' => "تم حل تذكرتك: {$ticket->title}",
                        'resolution_details' => [
                            'resolved_by' => $resolvedBy->name,
                            'ticket_id' => $ticket->id,
                            'ticket_number' => $ticket->ticket_number,
                            'ticket_title' => $ticket->title,
                            'resolution_notes' => $ticket->resolution_notes
                        ]
                    ];

                    Notification::create([
                        'user_id' => $creator->id,
                        'type' => 'ticket_resolved',
                        'data' => $notificationData,
                        'related_id' => $ticket->id
                    ]);

                    $this->sendAdditionalFirebaseNotification(
                        $creator,
                        $notificationData['message'],
                        'تم حل تذكرتك',
                        route('client-tickets.show', $ticket),
                        'ticket_resolved'
                    );

                    // إرسال إشعار Slack للمنشئ
                    if ($creator->slack_user_id) {
                        $this->slackNotificationService->sendTicketResolvedNotification(
                            $ticket,
                            $creator,
                            $resolvedBy
                        );
                    }
                }
            }

            Log::info('Ticket resolved notifications sent', [
                'ticket_id' => $ticket->id,
                'resolved_by' => $resolvedBy->id
            ]);

        } catch (\Exception $e) {
            Log::error('Error in TicketNotificationService::notifyTicketResolved - ' . $e->getMessage());
        }
    }

    /**
     * إشعار المستخدمين المذكورين في تعليق
     */
    public function notifyMentionedUsers(ClientTicket $ticket, TicketComment $comment): void
    {
        try {
            if (!$comment->mentions || empty($comment->mentions)) {
                return;
            }

            $mentionedUsers = User::whereIn('id', $comment->mentions)->get();

            foreach ($mentionedUsers as $mentionedUser) {
                // لا نرسل إشعار للشخص الذي كتب التعليق
                if ($mentionedUser->id === $comment->user_id) {
                    continue;
                }

                // تحديد نوع المنشن
                $isEveryoneMention = strpos($comment->comment, '@everyone') !== false || strpos($comment->comment, '@الجميع') !== false;
                $mentionType = $isEveryoneMention ? 'تم ذكر الجميع' : 'تم ذكرك';

                $notificationData = [
                    'message' => "{$mentionType} في تعليق على التذكرة: {$ticket->title}",
                    'mention_details' => [
                        'mentioned_by' => $comment->user->name,
                        'comment_preview' => \Str::limit($comment->comment, 100),
                        'ticket_id' => $ticket->id,
                        'ticket_number' => $ticket->ticket_number,
                        'ticket_title' => $ticket->title,
                        'comment_type' => $comment->comment_type_arabic,
                        'mention_type' => $isEveryoneMention ? 'everyone' : 'individual'
                    ]
                ];

                // إنشاء الإشعار في قاعدة البيانات
                Notification::create([
                    'user_id' => $mentionedUser->id,
                    'type' => 'ticket_mention',
                    'data' => $notificationData,
                    'related_id' => $ticket->id
                ]);

                // إرسال إشعار Firebase
                $this->sendAdditionalFirebaseNotification(
                    $mentionedUser,
                    $notificationData['message'],
                    'تم ذكرك في تعليق',
                    route('client-tickets.show', $ticket),
                    'ticket_mention'
                );

                // إرسال إشعار Slack
                if ($mentionedUser->slack_user_id) {
                    $this->slackNotificationService->sendTicketMentionNotification(
                        $ticket,
                        $mentionedUser,
                        $comment
                    );
                }
            }

            Log::info('Ticket mention notifications sent', [
                'ticket_id' => $ticket->id,
                'comment_id' => $comment->id,
                'mentioned_users' => $comment->mentions,
                'mentioned_by' => $comment->user_id
            ]);

        } catch (\Exception $e) {
            Log::error('Error in TicketNotificationService::notifyMentionedUsers - ' . $e->getMessage());
        }
    }
}
