<?php

namespace App\Services\Slack;

use App\Models\User;
use App\Models\AdditionalTask;

class AdditionalTaskSlackService extends BaseSlackService
{
    /**
     * إرسال إشعار للمهام الإضافية
     */
    public function sendAdditionalTaskNotification(AdditionalTask $task, User $user, User $author, string $action): bool
    {
        $message = $this->buildAdditionalTaskMessage($task, $user, $author, $action);
        $context = 'إشعار مهمة إضافية';
        $this->setNotificationContext($context);

        // استخدام Queue لتقليل الضغط على النظام
        return $this->sendSlackNotification($user, $message, $context, true);
    }

    /**
     * بناء رسالة المهام الإضافية
     */
    private function buildAdditionalTaskMessage(AdditionalTask $task, User $user, User $author, string $action): array
    {
        $actionText = $this->getAdditionalTaskActionText($action);
        $actionEmoji = $this->getAdditionalTaskActionEmoji($action);

        $text = "🔔 {$actionText} في المهمة الإضافية: {$task->title}";

        $taskUrl = url("/additional-tasks/{$task->id}");
        $pointsText = $task->points ? "{$task->points} نقطة" : 'غير محدد';
        $durationText = $task->duration_hours ? "{$task->duration_hours} ساعة" : 'غير محدد';
        $endTime = $task->current_end_time ? $task->current_end_time->format('d/m/Y - H:i') : 'غير محدد';

        return [
            'text' => $text,
            'blocks' => [
                $this->buildHeader("🔔 {$actionText} في مهمة إضافية"),
                $this->buildTextSection("*{$actionEmoji} المهمة:*\n{$task->title}"),
                $this->buildInfoSection([
                    "*النقاط:*\n{$pointsText}",
                    "*المدة المقدرة:*\n{$durationText}",
                    "*وقت الانتهاء:*\n{$endTime}",
                    "*بواسطة:*\n{$author->name}"
                ]),
                $this->buildTextSection("*الوصف:*\n" . ($task->description ?: 'لا يوجد وصف')),
                $this->buildActionsSection([
                    $this->buildActionButton('🔗 عرض المهمة', $taskUrl)
                ]),
                $this->buildContextSection()
            ]
        ];
    }

    /**
     * الحصول على نص الإجراء للمهام الإضافية
     */
    private function getAdditionalTaskActionText(string $action): string
    {
        $actions = [
            'assigned' => 'تم تعيينك',
            'approved' => 'تمت الموافقة على طلبك',
            'rejected' => 'تم رفض طلبك',
            'completed' => 'تم اكتمال مهمتك',
            'applied' => 'تم تقديم طلبك',
            'started' => 'تم بدء المهمة',
            'failed' => 'فشل في إكمال المهمة'
        ];

        return $actions[$action] ?? 'تحديث المهمة';
    }

    /**
     * الحصول على رمز الإجراء للمهام الإضافية
     */
    private function getAdditionalTaskActionEmoji(string $action): string
    {
        $emojis = [
            'assigned' => '📋',
            'approved' => '✅',
            'rejected' => '❌',
            'completed' => '🎉',
            'applied' => '📝',
            'started' => '🚀',
            'failed' => '⚠️'
        ];

        return $emojis[$action] ?? '🔔';
    }
}
