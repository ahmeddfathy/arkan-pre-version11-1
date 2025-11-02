<?php

namespace App\Services\Slack;

use App\Models\User;
use App\Models\ClientTicket;
use App\Models\TicketComment;

class TicketSlackService extends BaseSlackService
{
    public function sendTicketAssignmentNotification(ClientTicket $ticket, User $user, User $assignedBy): bool
    {
        $message = $this->buildTicketAssignmentMessage($ticket, $user, $assignedBy);
        $context = 'إشعار تعيين تذكرة';
        $this->setNotificationContext($context);

        return $this->sendSlackNotification($user, $message, $context, true);
    }

    public function sendTicketCommentNotification(ClientTicket $ticket, User $user, TicketComment $comment): bool
    {
        $message = $this->buildTicketCommentMessage($ticket, $user, $comment);
        $context = 'إشعار تعليق تذكرة';
        $this->setNotificationContext($context);

        return $this->sendSlackNotification($user, $message, $context, true);
    }

    public function sendTicketResolvedNotification(ClientTicket $ticket, User $user, User $resolvedBy): bool
    {
        $message = $this->buildTicketResolvedMessage($ticket, $user, $resolvedBy);
        $context = 'إشعار حل تذكرة';
        $this->setNotificationContext($context);

        return $this->sendSlackNotification($user, $message, $context, true);
    }

    public function sendTicketUserAddedNotification(ClientTicket $ticket, User $user, User $addedBy): bool
    {
        $message = $this->buildTicketUserAddedMessage($ticket, $user, $addedBy);
        $context = 'إشعار إضافة مستخدم لتذكرة';
        $this->setNotificationContext($context);

        return $this->sendSlackNotification($user, $message, $context, true);
    }

    public function sendTicketMentionNotification(ClientTicket $ticket, User $mentionedUser, TicketComment $comment): bool
    {
        $message = $this->buildTicketMentionMessage($ticket, $mentionedUser, $comment);
        $context = 'إشعار ذكر في تذكرة';
        $this->setNotificationContext($context);

        return $this->sendSlackNotification($mentionedUser, $message, $context, true);
    }

    private function buildTicketAssignmentMessage(ClientTicket $ticket, User $user, User $assignedBy): array
    {
        $ticketUrl = url("/client-tickets/{$ticket->id}");
        $clientName = $ticket->project && $ticket->project->client ? $ticket->project->client->name : 'غير محدد';

        return [
            'text' => "تم تعيين تذكرة جديدة لك",
            'blocks' => [
                $this->buildHeader('🎫 تم تعيين تذكرة جديدة لك'),
                $this->buildTextSection("*📋 التذكرة:*\n{$ticket->title}"),
                $this->buildInfoSection([
                    "*رقم التذكرة:*\n{$ticket->ticket_number}",
                    "*العميل:*\n{$clientName}",
                    "*الأولوية:*\n{$ticket->priority_arabic}",
                    "*القسم:*\n{$ticket->department_arabic}"
                ]),
                $this->buildInfoSection([
                    "*عينها:*\n{$assignedBy->name}",
                    "*الحالة:*\n{$ticket->status_arabic}"
                ]),
                $this->buildTextSection("*الوصف:*\n" . ($ticket->description ? \Str::limit($ticket->description, 200) : 'لا يوجد وصف')),
                $this->buildActionsSection([
                    $this->buildActionButton('🔗 عرض التذكرة', $ticketUrl)
                ]),
                $this->buildContextSection()
            ]
        ];
    }

    private function buildTicketCommentMessage(ClientTicket $ticket, User $user, TicketComment $comment): array
    {
        $ticketUrl = url("/client-tickets/{$ticket->id}");
        $commentPreview = \Str::limit($comment->comment, 150);

        return [
            'text' => "تعليق جديد على التذكرة",
            'blocks' => [
                $this->buildHeader('💬 تعليق جديد على التذكرة'),
                $this->buildTextSection("*📋 التذكرة:*\n{$ticket->title}"),
                $this->buildInfoSection([
                    "*رقم التذكرة:*\n{$ticket->ticket_number}",
                    "*بواسطة:*\n{$comment->user->name}"
                ]),
                $this->buildTextSection("*التعليق:*\n{$commentPreview}"),
                $this->buildActionsSection([
                    $this->buildActionButton('🔗 عرض التذكرة', $ticketUrl)
                ]),
                $this->buildContextSection()
            ]
        ];
    }

    private function buildTicketResolvedMessage(ClientTicket $ticket, User $user, User $resolvedBy): array
    {
        $ticketUrl = url("/client-tickets/{$ticket->id}");
        $resolutionNotes = $ticket->resolution_notes ? \Str::limit($ticket->resolution_notes, 200) : 'لا توجد ملاحظات';

        return [
            'text' => "تم حل التذكرة",
            'blocks' => [
                $this->buildHeader('✅ تم حل التذكرة'),
                $this->buildTextSection("*📋 التذكرة:*\n{$ticket->title}"),
                $this->buildInfoSection([
                    "*رقم التذكرة:*\n{$ticket->ticket_number}",
                    "*حلها:*\n{$resolvedBy->name}"
                ]),
                $this->buildTextSection("*ملاحظات الحل:*\n{$resolutionNotes}"),
                $this->buildActionsSection([
                    $this->buildActionButton('🔗 عرض التذكرة', $ticketUrl)
                ]),
                $this->buildContextSection()
            ]
        ];
    }

    private function buildTicketUserAddedMessage(ClientTicket $ticket, User $user, User $addedBy): array
    {
        $ticketUrl = url("/client-tickets/{$ticket->id}");
        $clientName = $ticket->project && $ticket->project->client ? $ticket->project->client->name : 'غير محدد';

        return [
            'text' => "تم إضافتك لفريق التذكرة",
            'blocks' => [
                $this->buildHeader('👥 تم إضافتك لفريق التذكرة'),
                $this->buildTextSection("*📋 التذكرة:*\n{$ticket->title}"),
                $this->buildInfoSection([
                    "*رقم التذكرة:*\n{$ticket->ticket_number}",
                    "*العميل:*\n{$clientName}",
                    "*أضافك:*\n{$addedBy->name}",
                    "*الأولوية:*\n{$ticket->priority_arabic}"
                ]),
                $this->buildTextSection("*الوصف:*\n" . ($ticket->description ? \Str::limit($ticket->description, 200) : 'لا يوجد وصف')),
                $this->buildActionsSection([
                    $this->buildActionButton('🔗 عرض التذكرة', $ticketUrl)
                ]),
                $this->buildContextSection()
            ]
        ];
    }

    private function buildTicketMentionMessage(ClientTicket $ticket, User $mentionedUser, TicketComment $comment): array
    {
        $ticketUrl = url("/client-tickets/{$ticket->id}");
        $commentPreview = \Str::limit($comment->comment, 150);
            
        $isEveryoneMention = strpos($comment->comment, '@everyone') !== false || strpos($comment->comment, '@الجميع') !== false;
        $mentionIcon = $isEveryoneMention ? '👥' : '📢';
        $mentionText = $isEveryoneMention ? 'تم ذكر الجميع في تعليق على التذكرة' : 'تم ذكرك في تعليق على التذكرة';

        return [
            'text' => $mentionText,
            'blocks' => [
                $this->buildHeader("{$mentionIcon} {$mentionText}"),
                $this->buildTextSection("*📋 التذكرة:*\n{$ticket->title}"),
                $this->buildInfoSection([
                    "*رقم التذكرة:*\n{$ticket->ticket_number}",
                    "*ذكركم:*\n{$comment->user->name}",
                    "*نوع التعليق:*\n{$comment->comment_type_arabic}",
                    "*نوع المنشن:*\n" . ($isEveryoneMention ? '👥 منشن جماعي' : '👤 منشن فردي')
                ]),
                $this->buildTextSection("*التعليق:*\n{$commentPreview}"),
                $this->buildActionsSection([
                    $this->buildActionButton('🔗 عرض التذكرة', $ticketUrl)
                ]),
                $this->buildContextSection()
            ]
        ];
    }
}
