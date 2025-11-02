<?php

namespace App\Services\Slack;

use App\Models\TaskRevision;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class RevisionSlackService extends BaseSlackService
{
    public function sendRevisionCreatedNotification(TaskRevision $revision): bool
    {
        try {
            $usersToNotify = $this->collectUniqueUsers($revision);

            foreach ($usersToNotify as $userId => $userData) {
                $user = User::find($userId);

                if (!$user) {
                    continue;
                }

                $message = $this->buildRevisionCreatedMessage($revision, $user, $userData['roles']);
                $context = 'إشعار تعديل جديد';
                $this->setNotificationContext($context);

                $this->sendSlackNotification($user, $message, $context, true);
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send revision Slack notifications', [
                'revision_id' => $revision->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function sendRevisionStatusUpdateNotification(TaskRevision $revision, string $oldStatus, User $updatedBy): bool
    {
        try {
            if ($revision->responsible_user_id && $revision->responsibleUser) {
                $message = $this->buildStatusUpdateMessage($revision, $oldStatus, $updatedBy);
                $context = 'تحديث حالة تعديل';
                $this->setNotificationContext($context);

                $this->sendSlackNotification($revision->responsibleUser, $message, $context, true);
            }

            return true;

        } catch (\Exception $e) {
            return false;
        }
    }

    public function sendRevisionCompletedNotification(TaskRevision $revision): bool
    {
        try {
            if ($revision->creator) {
                $message = $this->buildRevisionCompletedMessage($revision);
                $context = 'تعديل مكتمل';
                $this->setNotificationContext($context);

                $this->sendSlackNotification($revision->creator, $message, $context, true);
            }

            return true;

        } catch (\Exception $e) {
            return false;
        }
    }

    private function collectUniqueUsers(TaskRevision $revision): array
    {
        $revision->load([
            'creator',
            'responsibleUser',
            'executorUser',
            'assignedUser'
        ]);

        $users = [];

        if ($revision->responsible_user_id && $revision->responsibleUser) {
            $this->addUserRole($users, $revision->responsible_user_id, 'responsible', 'مسؤول عن الخطأ');
        }

        if ($revision->executor_user_id && $revision->executorUser) {
            $this->addUserRole($users, $revision->executor_user_id, 'executor', 'منفذ التعديل');
        }

        if ($revision->reviewers && is_array($revision->reviewers)) {
            foreach ($revision->reviewers as $index => $reviewerData) {
                $reviewerUser = \App\Models\User::find($reviewerData['reviewer_id']);
                if ($reviewerUser) {
                    $orderLabel = 'مراجع ' . ($index + 1);
                    $this->addUserRole($users, $reviewerData['reviewer_id'], 'reviewer', $orderLabel);
                }
            }
        }

        if ($revision->assigned_to && $revision->assignedUser) {
            $this->addUserRole($users, $revision->assigned_to, 'assigned', 'مكلف بالتعديل');
        }

        return $users;
    }

    private function addUserRole(array &$users, int $userId, string $roleKey, string $roleLabel): void
    {
        if (!isset($users[$userId])) {
            $users[$userId] = [
                'roles' => []
            ];
        }

        $users[$userId]['roles'][$roleKey] = $roleLabel;
    }

    private function buildRevisionCreatedMessage(TaskRevision $revision, User $user, array $roles): array
    {
        $typeIcon = '🔄';
        $typeText = match($revision->revision_type) {
            'project' => 'تعديل مشروع',
            'task' => 'تعديل مهمة',
            'general' => 'تعديل عام',
            default => 'تعديل'
        };

        $rolesText = $this->formatUserRoles($roles);

        $sourceInfo = $this->getRevisionSourceInfo($revision);

        $blocks = [
            $this->buildHeader($typeIcon . ' ' . $typeText . ' جديد'),
        ];

        if ($rolesText) {
            $blocks[] = $this->buildTextSection("*دورك:* {$rolesText}");
        }

        $blocks[] = $this->buildInfoSection([
            "*العنوان:*\n{$revision->title}",
            "*أنشأه:*\n{$revision->creator->name}"
        ]);

        $blocks[] = $this->buildTextSection("*الوصف:*\n{$revision->description}");

        $additionalInfo = [];

        if ($sourceInfo) {
            $additionalInfo[] = "*المصدر:*\n{$sourceInfo}";
        }

        if ($revision->revision_source) {
            $sourceLabel = match($revision->revision_source) {
                'internal' => '🏢 داخلي',
                'client' => '👤 عميل',
                'external' => '🌐 خارجي',
                default => $revision->revision_source
            };
            $additionalInfo[] = "*مصدر التعديل:*\n{$sourceLabel}";
        }

        if (!empty($additionalInfo)) {
            $blocks[] = $this->buildInfoSection($additionalInfo);
        }

        if ($revision->responsibility_notes) {
            $blocks[] = $this->buildTextSection("📝 *ملاحظات:*\n{$revision->responsibility_notes}");
        }

        $revisionUrl = url('/revisions/' . $revision->id);
        $blocks[] = $this->buildActionsSection([
            $this->buildActionButton('📋 عرض التعديل', $revisionUrl, 'primary')
        ]);

        $blocks[] = $this->buildContextSection();

        return [
            'text' => "تعديل جديد: {$revision->title}",
            'blocks' => $blocks
        ];
    }

    private function buildStatusUpdateMessage(TaskRevision $revision, string $oldStatus, User $updatedBy): array
    {
        $statusMap = [
            'pending' => ['label' => 'قيد الانتظار', 'icon' => '⏳'],
            'in_progress' => ['label' => 'قيد التنفيذ', 'icon' => '⚙️'],
            'under_review' => ['label' => 'تحت المراجعة', 'icon' => '👀'],
            'completed' => ['label' => 'مكتمل', 'icon' => '✅'],
            'cancelled' => ['label' => 'ملغي', 'icon' => '❌']
        ];

        $oldStatusInfo = $statusMap[$oldStatus] ?? ['label' => $oldStatus, 'icon' => '📌'];
        $newStatusInfo = $statusMap[$revision->status] ?? ['label' => $revision->status, 'icon' => '📌'];

        $revisionUrl = url('/revisions/' . $revision->id);

        return [
            'text' => "تحديث حالة التعديل: {$revision->title}",
            'blocks' => [
                $this->buildHeader('🔄 تحديث حالة التعديل'),
                $this->buildTextSection("*التعديل:* {$revision->title}"),
                $this->buildInfoSection([
                    "*الحالة السابقة:*\n{$oldStatusInfo['icon']} {$oldStatusInfo['label']}",
                    "*الحالة الجديدة:*\n{$newStatusInfo['icon']} {$newStatusInfo['label']}"
                ]),
                $this->buildTextSection("*حدّثها:* {$updatedBy->name}"),
                $this->buildActionsSection([
                    $this->buildActionButton('📋 عرض التعديل', $revisionUrl)
                ]),
                $this->buildContextSection()
            ]
        ];
    }

    private function buildRevisionCompletedMessage(TaskRevision $revision): array
    {
        $completedBy = $revision->executorUser ?? $revision->getCurrentReviewer();
        $completedByName = $completedBy ? $completedBy->name : 'غير محدد';

        $revisionUrl = url('/revisions/' . $revision->id);

        return [
            'text' => "تم اكتمال التعديل: {$revision->title}",
            'blocks' => [
                $this->buildHeader('✅ تعديل مكتمل!'),
                $this->buildTextSection("*التعديل:* {$revision->title}"),
                $this->buildInfoSection([
                    "*الحالة:*\n✅ مكتمل",
                    "*أكمله:*\n{$completedByName}"
                ]),
                $this->buildTextSection("🎉 تم إكمال التعديل بنجاح!"),
                $this->buildActionsSection([
                    $this->buildActionButton('📋 عرض التعديل', $revisionUrl)
                ]),
                $this->buildContextSection()
            ]
        ];
    }

    private function formatUserRoles(array $roles): string
    {
        if (empty($roles)) {
            return '';
        }

        $roleLabels = array_values($roles);

        if (count($roleLabels) === 1) {
            return $roleLabels[0];
        }

        return implode(' • ', $roleLabels);
    }

    private function getRevisionSourceInfo(TaskRevision $revision): ?string
    {
        if ($revision->revision_type === 'project' && $revision->project) {
            $projectCode = $revision->project->code ?? '';
            $projectName = $revision->project->name ?? 'مشروع';

            $info = $projectCode ? "[{$projectCode}] {$projectName}" : $projectName;

            if ($revision->service) {
                $info .= " - {$revision->service->name}";
            }

            return $info;
        }

        if ($revision->revision_type === 'task' && $revision->task) {
            $task = $revision->task;
            $project = $task->project;
            $projectCode = $project ? $project->code : '';
            $taskName = $task->name ?? 'مهمة';

            return $projectCode ? "مهمة: [{$projectCode}] {$taskName}" : "مهمة: {$taskName}";
        }

        if ($revision->revision_type === 'task' && $revision->templateTaskUser) {
            $templateTask = $revision->templateTaskUser->templateTask;
            $project = $revision->templateTaskUser->project;
            $projectCode = $project ? $project->code : '';
            $taskName = $templateTask ? $templateTask->name : 'مهمة قالب';

            return $projectCode ? "مهمة قالب: [{$projectCode}] {$taskName}" : "مهمة قالب: {$taskName}";
        }

        if ($revision->revision_type === 'general') {
            return 'تعديل عام';
        }

        return null;
    }

    public function sendRevisionExecutorTransferNotification(
        TaskRevision $revision,
        User $fromUser,
        User $toUser,
        User $transferredBy,
        ?string $reason = null
    ): bool {
        try {
            $messageToNew = $this->buildExecutorTransferMessage($revision, $fromUser, $toUser, $transferredBy, $reason, 'to');
            $this->setNotificationContext('نقل تنفيذ تعديل إليك');
            $this->sendSlackNotification($toUser, $messageToNew, 'نقل تنفيذ تعديل', true);

            $messageToOld = $this->buildExecutorTransferMessage($revision, $fromUser, $toUser, $transferredBy, $reason, 'from');
            $this->setNotificationContext('نقل تنفيذ تعديل منك');
            $this->sendSlackNotification($fromUser, $messageToOld, 'نقل تنفيذ تعديل', true);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send executor transfer Slack notification', [
                'revision_id' => $revision->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function sendRevisionReviewerTransferNotification(
        TaskRevision $revision,
        ?User $fromUser,
        User $toUser,
        User $transferredBy,
        int $reviewerOrder,
        ?string $reason = null
    ): bool {
        try {
            $messageToNew = $this->buildReviewerTransferMessage($revision, $fromUser, $toUser, $transferredBy, $reviewerOrder, $reason, 'to');
            $this->setNotificationContext('نقل مراجعة تعديل إليك');
            $this->sendSlackNotification($toUser, $messageToNew, 'نقل مراجعة تعديل', true);

            if ($fromUser) {
                $messageToOld = $this->buildReviewerTransferMessage($revision, $fromUser, $toUser, $transferredBy, $reviewerOrder, $reason, 'from');
                $this->setNotificationContext('نقل مراجعة تعديل منك');
                $this->sendSlackNotification($fromUser, $messageToOld, 'نقل مراجعة تعديل', true);
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send reviewer transfer Slack notification', [
                'revision_id' => $revision->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    private function buildExecutorTransferMessage(
        TaskRevision $revision,
        User $fromUser,
        User $toUser,
        User $transferredBy,
        ?string $reason,
        string $direction
    ): array {
        $title = $revision->title;
        $projectInfo = $this->getRevisionSourceInfo($revision);

        $header = $direction === 'to' ? '🔨 نقل تنفيذ تعديل إليك' : '🔨 نقل تنفيذ تعديل منك';

        $blocks = [
            $this->buildHeader($header),
            $this->buildTextSection("*التعديل:* {$title}"),
        ];

        $info = $direction === 'to'
            ? ["*من:*\n{$fromUser->name}", "*بواسطة:*\n{$transferredBy->name}"]
            : ["*إلى:*\n{$toUser->name}", "*بواسطة:*\n{$transferredBy->name}"];

        if ($projectInfo) {
            array_unshift($info, "*المشروع:*\n{$projectInfo}");
        }

        $blocks[] = $this->buildInfoSection($info);

        if ($reason) {
            $blocks[] = $this->buildTextSection("💬 *السبب:* {$reason}");
        }

        $blocks[] = $this->buildContextSection();

        return [
            'text' => $header,
            'blocks' => $blocks
        ];
    }

    private function buildReviewerTransferMessage(
        TaskRevision $revision,
        ?User $fromUser,
        User $toUser,
        User $transferredBy,
        int $reviewerOrder,
        ?string $reason,
        string $direction
    ): array {
        $title = $revision->title;
        $projectInfo = $this->getRevisionSourceInfo($revision);

        $header = $direction === 'to'
            ? "✅ نقل مراجعة تعديل إليك (المراجع رقم {$reviewerOrder})"
            : "✅ نقل مراجعة تعديل منك (المراجع رقم {$reviewerOrder})";

        $blocks = [
            $this->buildHeader($header),
            $this->buildTextSection("*التعديل:* {$title}"),
        ];

        $info = [];
        if ($direction === 'to') {
            if ($fromUser) {
                $info[] = "*من:*\n{$fromUser->name}";
            }
            $info[] = "*بواسطة:*\n{$transferredBy->name}";
        } else {
            $info[] = "*إلى:*\n{$toUser->name}";
            $info[] = "*بواسطة:*\n{$transferredBy->name}";
        }

        if ($projectInfo) {
            array_unshift($info, "*المشروع:*\n{$projectInfo}");
        }

        $blocks[] = $this->buildInfoSection($info);

        if ($reason) {
            $blocks[] = $this->buildTextSection("💬 *السبب:* {$reason}");
        }

        $blocks[] = $this->buildContextSection();

        return [
            'text' => $header,
            'blocks' => $blocks
        ];
    }
}

