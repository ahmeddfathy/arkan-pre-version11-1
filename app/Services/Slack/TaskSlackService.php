<?php

namespace App\Services\Slack;

use App\Models\User;
use App\Models\Task;

class TaskSlackService extends BaseSlackService
{
    /**
     * إرسال إشعار عند تعيين مهمة للمستخدم
     */
    public function sendTaskAssignmentNotification($task, User $assignedUser, User $author): bool
    {
        $message = $this->buildTaskAssignmentMessage($task, $assignedUser, $author);
        $context = 'إشعار تعيين مهمة';
        $this->setNotificationContext($context);

        // استخدام Queue لتقليل الضغط على النظام
        return $this->sendSlackNotification($assignedUser, $message, $context, true);
    }

    /**
     * بناء رسالة تعيين المهمة
     */
    private function buildTaskAssignmentMessage($task, User $assignedUser, User $author): array
    {
        // التعامل مع الكائنات المختلفة (Task model أو stdClass)
        if (is_object($task) && method_exists($task, 'load')) {
            $task->load(['project', 'service']);
        }

        $projectUrl = url("/projects/{$task->project_id}");

        return [
            'text' => "تم تعيين مهمة جديدة لك",
            'blocks' => [
                $this->buildHeader('📋 مهمة جديدة!'),
                $this->buildInfoSection([
                    "*اسم المهمة:*\n{$task->name}",
                    "*عينها:*\n{$author->name}"
                ]),
                $this->buildTextSection("*الوصف:*\n" . ($task->description ?: 'لا يوجد وصف')),
                $this->buildInfoSection([
                    "*المشروع:*\n" . $this->getProjectName($task),
                    "*تاريخ الاستحقاق:*\n" . $this->getDueDate($task)
                ]),
                $this->buildActionsSection([
                    $this->buildActionButton('🔗 عرض المشروع', $projectUrl)
                ]),
                $this->buildContextSection()
            ]
        ];
    }

    /**
     * الحصول على اسم المشروع
     */
    private function getProjectName($task): string
    {
        if (isset($task->project)) {
            return $task->project->name;
        } elseif (isset($task->project_name)) {
            return $task->project_name;
        }
        return 'غير محدد';
    }

    /**
     * إرسال إشعار عند اعتماد المهمة ومنح النقاط
     */
    public function sendTaskApprovalNotification($taskUser, int $awardedPoints, string $approverName, string $note = null): bool
    {
        $message = $this->buildTaskApprovalMessage($taskUser, $awardedPoints, $approverName, $note);
        $context = 'إشعار اعتماد مهمة';
        $this->setNotificationContext($context);

        // استخدام Queue لتقليل الضغط على النظام
        return $this->sendSlackNotification($taskUser->user, $message, $context, true);
    }

    /**
     * بناء رسالة اعتماد المهمة
     */
    private function buildTaskApprovalMessage($taskUser, int $awardedPoints, string $approverName, string $note = null): array
    {
        // التحقق من نوع المهمة
        $isTemplateTask = get_class($taskUser) === 'App\Models\TemplateTaskUser';
        $taskName = $isTemplateTask ? $taskUser->templateTask->name : $taskUser->task->name;
        $originalPoints = $isTemplateTask ? $taskUser->templateTask->points : $taskUser->task->points;

        $projectUrl = $isTemplateTask ?
            url("/projects/{$taskUser->project_id}") :
            url("/projects/{$taskUser->task->project_id}");

        $pointsMessage = $awardedPoints === $originalPoints ?
            "حصلت على النقاط الكاملة! 🎉" :
            "تم تعديل النقاط من {$originalPoints} إلى {$awardedPoints}";

        $blocks = [
            $this->buildHeader('🎉 تم اعتماد مهمتك!'),
            $this->buildInfoSection([
                "*المهمة:*\n{$taskName}",
                "*اعتمدها:*\n{$approverName}"
            ]),
            $this->buildTextSection("✨ *النقاط المحصل عليها:* {$awardedPoints} نقطة\n{$pointsMessage}")
        ];

        // إضافة الملاحظة إذا وجدت
        if ($note) {
            $blocks[] = $this->buildTextSection("📝 *ملاحظة المعتمد:*\n{$note}");
        }

        $blocks[] = $this->buildActionsSection([
            $this->buildActionButton('🔗 عرض المشروع', $projectUrl)
        ]);

        $blocks[] = $this->buildContextSection();

        return [
            'text' => "تم اعتماد مهمتك وحصلت على {$awardedPoints} نقطة!",
            'blocks' => $blocks
        ];
    }

    /**
     * الحصول على تاريخ الاستحقاق
     */
    private function getDueDate($task): string
    {
        if (!isset($task->due_date) || !$task->due_date) {
            return 'غير محدد';
        }

        if (is_string($task->due_date)) {
            return $task->due_date;
        }

        return $task->due_date->format('d/m/Y');
    }

    /**
     * إرسال إشعار عند إكمال مهمة لمالك الفريق
     */
    public function sendTaskCompletedNotification(Task $task, User $teamOwner, User $completedBy): bool
    {
        $message = $this->buildTaskCompletedMessage($task, $teamOwner, $completedBy);
        $context = 'إشعار إكمال مهمة';
        $this->setNotificationContext($context);

        // استخدام Queue لتقليل الضغط على النظام
        return $this->sendSlackNotification($teamOwner, $message, $context, true);
    }

    /**
     * إرسال إشعار عند إعطاء نقاط للموظف
     */
    public function sendPointsAwardedNotification(Task $task, User $recipient, User $awardedBy, int $points): bool
    {
        $message = $this->buildPointsAwardedMessage($task, $recipient, $awardedBy, $points);
        $context = 'إشعار منح نقاط';
        $this->setNotificationContext($context);

        // استخدام Queue لتقليل الضغط على النظام
        return $this->sendSlackNotification($recipient, $message, $context, true);
    }

    /**
     * بناء رسالة إكمال المهمة
     */
    private function buildTaskCompletedMessage(Task $task, User $teamOwner, User $completedBy): array
    {
        $taskUrl = route('tasks.index') . '?task_id=' . $task->id;

        return [
            'text' => "تم إكمال مهمة من فريقك",
            'blocks' => [
                $this->buildHeader('✅ مهمة مكتملة!'),
                $this->buildInfoSection([
                    "*اسم المهمة:*\n{$task->title}",
                    "*أكملها:*\n{$completedBy->name}",
                    "*الوقت:*\n" . now()->format('d/m/Y H:i')
                ]),
                $this->buildTextSection("*الوصف:*\n" . ($task->description ?: 'لا يوجد وصف')),
                $this->buildInfoSection([
                    "*النقاط المستحقة:*\n{$task->points} نقطة",
                    "*الحالة:*\nبانتظار الموافقة"
                ]),
                $this->buildActionsSection([
                    $this->buildActionButton('🔍 مراجعة المهمة', $taskUrl, 'primary'),
                    $this->buildActionButton('✅ اعتماد سريع', $taskUrl)
                ]),
                $this->buildContextSection()
            ]
        ];
    }

    /**
     * بناء رسالة منح النقاط
     */
    private function buildPointsAwardedMessage(Task $task, User $recipient, User $awardedBy, int $points): array
    {
        $taskUrl = route('tasks.my-tasks') . '?task_id=' . $task->id;

        return [
            'text' => "حصلت على نقاط جديدة!",
            'blocks' => [
                $this->buildHeader('🎉 نقاط جديدة!'),
                $this->buildInfoSection([
                    "*المهمة:*\n{$task->title}",
                    "*منحها:*\n{$awardedBy->name}",
                    "*النقاط:*\n{$points} نقطة ⭐"
                ]),
                $this->buildTextSection("🏆 *تهانينا!* لقد حصلت على {$points} نقطة لإنجازك المتميز في هذه المهمة."),
                $this->buildInfoSection([
                    "*تاريخ الاعتماد:*\n" . now()->format('d/m/Y H:i'),
                    "*الحالة:*\nمعتمدة ✅"
                ]),
                $this->buildActionsSection([
                    $this->buildActionButton('📊 عرض إحصائياتي', route('dashboard'), 'primary'),
                    $this->buildActionButton('📋 مهامي', $taskUrl)
                ]),
                $this->buildContextSection()
            ]
        ];
    }

    /**
     * إرسال إشعار عند اكتمال تاسك (للمعتمدين أو creator)
     */
    public function sendTaskDeliveryNotification($taskUser, User $recipient, string $recipientType = 'approver'): bool
    {
        $message = $this->buildTaskDeliveryMessage($taskUser, $recipient, $recipientType);
        $context = $recipientType === 'approver' ? 'إشعار تاسك بانتظار الاعتماد' : 'إشعار تاسك مكتملة';
        $this->setNotificationContext($context);

        return $this->sendSlackNotification($recipient, $message, $context, true);
    }

    /**
     * بناء رسالة اكتمال التاسك
     */
    private function buildTaskDeliveryMessage($taskUser, User $recipient, string $recipientType): array
    {
        $isTemplateTask = get_class($taskUser) === 'App\Models\TemplateTaskUser';
        $taskName = $isTemplateTask ?
            ($taskUser->templateTask->name ?? 'غير محدد') :
            ($taskUser->task->name ?? 'غير محدد');

        $employeeName = $taskUser->user->name ?? 'غير محدد';
        $completedAt = $taskUser->completed_at ?
            $taskUser->completed_at->format('d/m/Y H:i') :
            now()->format('d/m/Y H:i');

        $projectUrl = $isTemplateTask ?
            url("/projects/{$taskUser->project_id}") :
            url("/projects/{$taskUser->task->project_id}");

        if ($recipientType === 'approver') {
            // رسالة للمعتمدين
            return [
                'text' => "تاسك بانتظار اعتمادك",
                'blocks' => [
                    $this->buildHeader('⏳ تاسك بانتظار الاعتماد'),
                    $this->buildInfoSection([
                        "*التاسك:*\n{$taskName}",
                        "*الموظف:*\n{$employeeName}",
                        "*تاريخ الإكمال:*\n{$completedAt}"
                    ]),
                    $this->buildTextSection("📋 *يرجى مراجعة واعتماد التاسك*"),
                    $this->buildActionsSection([
                        $this->buildActionButton('🔍 مراجعة التاسك', route('task-deliveries.index'), 'primary'),
                        $this->buildActionButton('🔗 عرض المشروع', $projectUrl)
                    ]),
                    $this->buildContextSection()
                ]
            ];
        } else {
            // رسالة للـ creator
            return [
                'text' => "تاسك مكتملة",
                'blocks' => [
                    $this->buildHeader('✅ تاسك مكتملة'),
                    $this->buildInfoSection([
                        "*التاسك:*\n{$taskName}",
                        "*أكملها:*\n{$employeeName}",
                        "*تاريخ الإكمال:*\n{$completedAt}"
                    ]),
                    $this->buildTextSection("🎉 *تم إكمال التاسك بنجاح!*"),
                    $this->buildActionsSection([
                        $this->buildActionButton('🔗 عرض التاسك', route('task-deliveries.index'))
                    ]),
                    $this->buildContextSection()
                ]
            ];
        }
    }

    /**
     * إرسال إشعار عند اعتماد تاسك (إداري/فني)
     */
    public function sendTaskApprovedNotification($taskUser, User $employee, User $approver, string $approvalType): bool
    {
        $message = $this->buildTaskApprovedMessage($taskUser, $employee, $approver, $approvalType);
        $context = 'إشعار اعتماد تاسك';
        $this->setNotificationContext($context);

        return $this->sendSlackNotification($employee, $message, $context, true);
    }

    /**
     * بناء رسالة اعتماد التاسك
     */
    private function buildTaskApprovedMessage($taskUser, User $employee, User $approver, string $approvalType): array
    {
        $isTemplateTask = get_class($taskUser) === 'App\Models\TemplateTaskUser';
        $taskName = $isTemplateTask ?
            ($taskUser->templateTask->name ?? 'غير محدد') :
            ($taskUser->task->name ?? 'غير محدد');

        $typeArabic = $approvalType === 'administrative' ? 'الإداري' : 'الفني';
        $notes = $approvalType === 'administrative' ?
            $taskUser->administrative_notes :
            $taskUser->technical_notes;

        $projectUrl = $isTemplateTask ?
            url("/projects/{$taskUser->project_id}") :
            url("/projects/{$taskUser->task->project_id}");

        $blocks = [
            $this->buildHeader('🎉 تم اعتماد تاسكك!'),
            $this->buildInfoSection([
                "*التاسك:*\n{$taskName}",
                "*اعتمدها:*\n{$approver->name}",
                "*نوع الاعتماد:*\n{$typeArabic}"
            ]),
            $this->buildTextSection("✅ *تم الاعتماد {$typeArabic} بنجاح!*")
        ];

        // إضافة الملاحظات إذا وجدت
        if ($notes) {
            $blocks[] = $this->buildTextSection("📝 *ملاحظات:*\n{$notes}");
        }

        $blocks[] = $this->buildActionsSection([
            $this->buildActionButton('🔗 عرض المشروع', $projectUrl)
        ]);

        $blocks[] = $this->buildContextSection();

        return [
            'text' => "تم اعتماد تاسكك {$typeArabic}!",
            'blocks' => $blocks
        ];
    }
}
