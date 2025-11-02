<?php

namespace App\Services\Slack;

use App\Models\TaskUser;
use App\Models\TemplateTaskUser;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class TaskTransferSlackService extends BaseSlackService
{
    public function sendTaskTransferNotifications(
        TaskUser $originalTaskUser,
        ?TaskUser $newTaskUser,
        User $fromUser,
        User $toUser,
        string $transferType,
        int $transferPoints,
        ?string $reason = null
    ): bool {
        try {
            if ($transferType === 'negative') {
                $this->sendTransferFromNotification($originalTaskUser, $fromUser, $toUser, $transferPoints, $reason, 'task');
            }

            $taskUserToNotify = $newTaskUser ?? $originalTaskUser;
            $this->sendTransferToNotification($taskUserToNotify, $fromUser, $toUser, $transferType, $transferPoints, $reason, 'task');

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send task transfer Slack notifications', [
                'original_task_user_id' => $originalTaskUser->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function sendTemplateTaskTransferNotifications(
        TemplateTaskUser $originalTemplateTaskUser,
        ?TemplateTaskUser $newTemplateTaskUser,
        User $fromUser,
        User $toUser,
        string $transferType,
        int $transferPoints,
        ?string $reason = null
    ): bool {
        try {
            if ($transferType === 'negative') {
                $this->sendTransferFromNotification($originalTemplateTaskUser, $fromUser, $toUser, $transferPoints, $reason, 'template');
            }

            $templateTaskUserToNotify = $newTemplateTaskUser ?? $originalTemplateTaskUser;
            $this->sendTransferToNotification($templateTaskUserToNotify, $fromUser, $toUser, $transferType, $transferPoints, $reason, 'template');

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send template task transfer Slack notifications', [
                'original_template_task_user_id' => $originalTemplateTaskUser->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    private function sendTransferFromNotification($taskUser, User $fromUser, User $toUser, int $points, ?string $reason, string $taskType): void
    {
        $message = $this->buildTransferFromMessage($taskUser, $fromUser, $toUser, $points, $reason, $taskType);
        $context = 'نقل مهمة - خصم نقاط';
        $this->setNotificationContext($context);

        $this->sendSlackNotification($fromUser, $message, $context, true);
    }

    private function sendTransferToNotification($taskUser, User $fromUser, User $toUser, string $transferType, int $points, ?string $reason, string $taskType): void
    {
        $message = $this->buildTransferToMessage($taskUser, $fromUser, $toUser, $transferType, $points, $reason, $taskType);
        $context = 'نقل مهمة - استلام';
        $this->setNotificationContext($context);

        $this->sendSlackNotification($toUser, $message, $context, true);
    }

    private function buildTransferFromMessage($taskUser, User $fromUser, User $toUser, int $points, ?string $reason, string $taskType): array
    {
        $taskInfo = $this->getTaskInfo($taskUser, $taskType);

        $blocks = [
            $this->buildHeader('📤 تم نقل مهمة منك'),
            $this->buildInfoSection([
                "*المهمة:*\n{$taskInfo['name']}",
                "*نُقلت إلى:*\n{$toUser->name}"
            ])
        ];

        if ($taskInfo['project']) {
            $blocks[] = $this->buildTextSection("*المشروع:* {$taskInfo['project']}");
        }

        $blocks[] = $this->buildTextSection("⚠️ *تم خصم {$points} نقطة* بسبب نقل المهمة");

        if ($reason) {
            $blocks[] = $this->buildTextSection("📝 *السبب:*\n{$reason}");
        }

        $tasksUrl = $taskType === 'template'
            ? url('/projects/' . $taskInfo['project_id'])
            : url('/tasks/my-tasks');

        $blocks[] = $this->buildActionsSection([
            $this->buildActionButton('📋 مهامي', $tasksUrl)
        ]);

        $blocks[] = $this->buildContextSection();

        return [
            'text' => "تم نقل مهمة منك: {$taskInfo['name']}",
            'blocks' => $blocks
        ];
    }

    private function buildTransferToMessage($taskUser, User $fromUser, User $toUser, string $transferType, int $points, ?string $reason, string $taskType): array
    {
        $taskInfo = $this->getTaskInfo($taskUser, $taskType);

        $transferIcon = $transferType === 'positive' ? '➕' : '📤';
        $pointsText = $transferType === 'positive'
            ? "✨ *ستحصل على {$points} نقطة* عند إكمالها"
            : "تم نقل المهمة إليك";

        $blocks = [
            $this->buildHeader($transferIcon . ' مهمة منقولة إليك'),
            $this->buildInfoSection([
                "*المهمة:*\n{$taskInfo['name']}",
                "*من:*\n{$fromUser->name}"
            ])
        ];

        if ($taskInfo['project']) {
            $blocks[] = $this->buildTextSection("*المشروع:* {$taskInfo['project']}");
        }

        $blocks[] = $this->buildTextSection($pointsText);

        if ($taskInfo['description']) {
            $blocks[] = $this->buildTextSection("*الوصف:*\n{$taskInfo['description']}");
        }

        if ($reason) {
            $blocks[] = $this->buildTextSection("📝 *سبب النقل:*\n{$reason}");
        }

        if ($taskInfo['deadline']) {
            $blocks[] = $this->buildInfoSection([
                "*الموعد النهائي:*\n⏰ {$taskInfo['deadline']}"
            ]);
        }

        $tasksUrl = $taskType === 'template'
            ? url('/projects/' . $taskInfo['project_id'])
            : url('/tasks/my-tasks');

        $blocks[] = $this->buildActionsSection([
            $this->buildActionButton('📋 عرض المهمة', $tasksUrl, 'primary')
        ]);

        $blocks[] = $this->buildContextSection();

        return [
            'text' => "مهمة جديدة منقولة إليك: {$taskInfo['name']}",
            'blocks' => $blocks
        ];
    }

    private function getTaskInfo($taskUser, string $taskType): array
    {
        if ($taskType === 'template') {
            $task = $taskUser->templateTask;
            $project = $taskUser->project;

            return [
                'name' => $task ? $task->name : 'مهمة قالب',
                'description' => $task ? $task->description : null,
                'project' => $project ? ($project->code ? "[{$project->code}] {$project->name}" : $project->name) : null,
                'project_id' => $taskUser->project_id,
                'deadline' => $taskUser->due_date ? \Carbon\Carbon::parse($taskUser->due_date)->format('Y-m-d H:i') : null
            ];
        } else {
            $task = $taskUser->task;
            $project = $task ? $task->project : null;

            return [
                'name' => $task ? $task->name : 'مهمة',
                'description' => $task ? $task->description : null,
                'project' => $project ? ($project->code ? "[{$project->code}] {$project->name}" : $project->name) : null,
                'project_id' => $task ? $task->project_id : null,
                'deadline' => $taskUser->due_date ? \Carbon\Carbon::parse($taskUser->due_date)->format('Y-m-d H:i') : null
            ];
        }
    }
}

