<?php

namespace App\Services\Tasks;

use App\Models\User;
use App\Models\Task;
use App\Models\TaskUser;
use App\Models\TemplateTaskUser;
use App\Models\Team;
use App\Models\Notification;
use App\Services\FirebaseNotificationService;
use App\Services\Slack\TaskSlackService;
use App\Traits\HasNTPTime;
use Illuminate\Support\Facades\DB;

class TaskNotificationService
{
    use HasNTPTime;

    protected $firebaseService;
    protected $slackService;

    public function __construct(
        FirebaseNotificationService $firebaseService,
        TaskSlackService $slackService
    ) {
        $this->firebaseService = $firebaseService;
        $this->slackService = $slackService;
    }

    public function notifyTaskCompleted(TaskUser $taskUser): void
    {
        try {
            $task = $taskUser->task;
            $completedBy = $taskUser->user;

            if (!$task || !$completedBy) {
                return;
            }

            // إرسال الإشعار لمنشئ المهمة بدلاً من مالك الفريق
            $taskCreator = $task->createdBy;

            if (!$taskCreator) {
                return;
            }

            // لا ترسل إشعار إذا كان الشخص نفسه أكمل مهمته
            if ($taskCreator->id === $completedBy->id) {
                return;
            }

            $this->createTaskCompletedNotification($taskUser, $task, $completedBy, $taskCreator);

            $this->sendExternalNotifications($taskUser, $task, $taskCreator, $completedBy, 'task_completed');

        } catch (\Exception $e) {
        }
    }

    public function notifyTemplateTaskCompleted(TemplateTaskUser $templateTaskUser): void
    {
        try {
            $templateTask = $templateTaskUser->templateTask;
            $completedBy = $templateTaskUser->user;

            if (!$templateTask || !$completedBy) {
                return;
            }

            // إرسال الإشعار لمن أضاف المهمة بدلاً من مالك الفريق
            $taskAssigner = $templateTaskUser->assignedBy;

            if (!$taskAssigner) {
                return;
            }

            // لا ترسل إشعار إذا كان الشخص نفسه أكمل مهمته
            if ($taskAssigner->id === $completedBy->id) {
                return;
            }

            $this->createTemplateTaskCompletedNotification($templateTaskUser, $templateTask, $completedBy, $taskAssigner);

            $this->sendExternalNotificationsForTemplate($templateTaskUser, $templateTask, $taskAssigner, $completedBy, 'template_task_completed');

        } catch (\Exception $e) {
        }
    }

    public function notifyTemplateTaskPointsAwarded(TemplateTaskUser $templateTaskUser, User $awardedBy, int $points, string $note = null): void
    {
        try {
            $templateTask = $templateTaskUser->templateTask;
            $recipient = $templateTaskUser->user;

            if (!$templateTask || !$recipient) {
                return;
            }

            if ($awardedBy->id === $recipient->id) {
                return;
            }

            $this->createTemplateTaskPointsAwardedNotification($templateTaskUser, $templateTask, $recipient, $awardedBy, $points, $note);

            $this->sendExternalNotificationsForTemplatePoints($templateTaskUser, $templateTask, $recipient, $awardedBy, 'template_points_awarded', $points);

        } catch (\Exception $e) {
        }
    }

    public function notifyPointsAwarded(TaskUser $taskUser, User $awardedBy, int $points, string $note = null): void
    {
        try {
            $task = $taskUser->task;
            $recipient = $taskUser->user;

            if (!$task || !$recipient) {
                return;
            }

            if ($awardedBy->id === $recipient->id) {
                return;
            }

            $this->createPointsAwardedNotification($taskUser, $task, $recipient, $awardedBy, $points, $note);

            $this->sendExternalNotifications($taskUser, $task, $recipient, $awardedBy, 'points_awarded', $points);

        } catch (\Exception $e) {
        }
    }

    private function findTeamOwnerForUser(User $user): ?User
    {
        try {
            if ($user->current_team_id) {
                $team = Team::find($user->current_team_id);
                if ($team && $team->user_id) {
                    $owner = User::find($team->user_id);
                    if ($owner) {
                        return $owner;
                    }
                }
            }

            $teamMembership = DB::table('team_user')
                ->join('teams', 'team_user.team_id', '=', 'teams.id')
                ->join('users', 'teams.user_id', '=', 'users.id')
                ->where('team_user.user_id', $user->id)
                ->select('users.*')
                ->first();

            if ($teamMembership) {
                $owner = User::find($teamMembership->id);
                return $owner;
            }

            if ($user->department) {
                $departmentTeamOwner = DB::table('teams')
                    ->join('users', 'teams.user_id', '=', 'users.id')
                    ->where('users.department', $user->department)
                    ->select('users.*')
                    ->first();

                if ($departmentTeamOwner) {
                    $owner = User::find($departmentTeamOwner->id);
                    return $owner;
                }
            }

            return null;

        } catch (\Exception $e) {
            return null;
        }
    }

    private function createTaskCompletedNotification(TaskUser $taskUser, Task $task, User $completedBy, User $teamOwner): void
    {
        try {
            $message = "أكمل {$completedBy->name} المهمة: {$task->title}";

            $notification = Notification::create([
                'user_id' => $teamOwner->id,
                'type' => 'task_completed',
                'data' => [
                    'message' => $message,
                    'task_user_id' => $taskUser->id,
                    'task_id' => $task->id,
                    'task_title' => $task->title,
                    'completed_by_id' => $completedBy->id,
                    'completed_by_name' => $completedBy->name,
                    'completed_at' => $taskUser->updated_at->toISOString(),
                    'url' => route('tasks.index') . '?task_id=' . $task->id
                ],
                'related_id' => $taskUser->id
            ]);

        } catch (\Exception $e) {
        }
    }

    private function createPointsAwardedNotification(TaskUser $taskUser, Task $task, User $recipient, User $awardedBy, int $points, ?string $note): void
    {
        try {
            $message = "حصلت على {$points} نقطة من {$awardedBy->name} للمهمة: {$task->title}";
            if ($note) {
                $message .= " - ملاحظة: {$note}";
            }

            $notification = Notification::create([
                'user_id' => $recipient->id,
                'type' => 'points_awarded',
                'data' => [
                    'message' => $message,
                    'task_user_id' => $taskUser->id,
                    'task_id' => $task->id,
                    'task_title' => $task->title,
                    'awarded_by_id' => $awardedBy->id,
                    'awarded_by_name' => $awardedBy->name,
                    'points' => $points,
                    'note' => $note,
                    'awarded_at' => $this->getCurrentCairoTime()->toISOString(),
                    'url' => route('tasks.my-tasks') . '?task_id=' . $task->id
                ],
                'related_id' => $taskUser->id
            ]);

        } catch (\Exception $e) {
        }
    }

    private function sendExternalNotifications(TaskUser $taskUser, Task $task, User $recipient, User $sender, string $type, int $points = null): void
    {
        if ($recipient->fcm_token) {
            try {
                $this->sendFirebaseNotificationSafe($taskUser, $task, $recipient, $sender, $type, $points);
            } catch (\Exception $e) {
            }
        }

        if ($recipient->slack_user_id) {
            try {
                $this->sendSlackNotificationSafe($taskUser, $task, $recipient, $sender, $type, $points);
            } catch (\Exception $e) {
            }
        }
    }

    private function sendFirebaseNotificationSafe(TaskUser $taskUser, Task $task, User $recipient, User $sender, string $type, int $points = null): void
    {
        try {
            if ($type === 'task_completed') {
                $title = 'مهمة مكتملة';
                $body = "أكمل {$sender->name} المهمة: {$task->title}";
                $link = '/tasks?task_id=' . $task->id;
            } else {
                $title = 'نقاط جديدة';
                $body = "حصلت على {$points} نقطة من {$sender->name} للمهمة: {$task->title}";
                $link = '/tasks/my-tasks?task_id=' . $task->id;
            }

            $result = $this->firebaseService->sendNotification(
                $recipient->fcm_token,
                $title,
                $body,
                $link
            );

        } catch (\Exception $e) {
        }
    }

    private function sendSlackNotificationSafe(TaskUser $taskUser, Task $task, User $recipient, User $sender, string $type, int $points = null): void
    {
        try {
            $maxAttempts = 3;
            $result = false;

            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                try {
                    if ($type === 'task_completed') {
                        $result = $this->slackService->sendTaskCompletedNotification($task, $recipient, $sender);
                    } else {
                        $result = $this->slackService->sendPointsAwardedNotification($task, $recipient, $sender, $points);
                    }

                    if ($result) {
                        break;
                    }

                } catch (\Exception $e) {
                }

                if ($attempt < $maxAttempts) {
                    usleep(pow(2, $attempt - 1) * 100000);
                }
            }

        } catch (\Exception $e) {
        }
    }

    private function createTemplateTaskCompletedNotification(TemplateTaskUser $templateTaskUser, $templateTask, User $completedBy, User $teamOwner): void
    {
        try {
            $message = "أكمل {$completedBy->name} مهمة القالب: {$templateTask->name}";

            $notification = Notification::create([
                'user_id' => $teamOwner->id,
                'type' => 'template_task_completed',
                'data' => [
                    'message' => $message,
                    'template_task_user_id' => $templateTaskUser->id,
                    'template_task_id' => $templateTask->id,
                    'template_task_name' => $templateTask->name,
                    'completed_by_id' => $completedBy->id,
                    'completed_by_name' => $completedBy->name,
                    'completed_at' => $templateTaskUser->updated_at->toISOString(),
                    'project_id' => $templateTaskUser->project_id,
                    'url' => route('tasks.index') . '?template_task_id=' . $templateTask->id
                ],
                'related_id' => $templateTaskUser->id
            ]);

        } catch (\Exception $e) {
        }
    }

    private function sendExternalNotificationsForTemplate(TemplateTaskUser $templateTaskUser, $templateTask, User $recipient, User $sender, string $type): void
    {
        if ($recipient->fcm_token) {
            try {
                $this->sendFirebaseNotificationForTemplate($templateTaskUser, $templateTask, $recipient, $sender, $type);
            } catch (\Exception $e) {
            }
        }

        if ($recipient->slack_user_id) {
            try {
                $this->sendSlackNotificationForTemplate($templateTaskUser, $templateTask, $recipient, $sender, $type);
            } catch (\Exception $e) {
            }
        }
    }

    private function sendFirebaseNotificationForTemplate(TemplateTaskUser $templateTaskUser, $templateTask, User $recipient, User $sender, string $type): void
    {
        try {
            $title = 'مهمة قالب مكتملة';
            $body = "أكمل {$sender->name} مهمة القالب: {$templateTask->name}";
            $link = '/tasks?template_task_id=' . $templateTask->id;

            $result = $this->firebaseService->sendNotification(
                $recipient->fcm_token,
                $title,
                $body,
                $link
            );

        } catch (\Exception $e) {
        }
    }

    private function sendSlackNotificationForTemplate(TemplateTaskUser $templateTaskUser, $templateTask, User $recipient, User $sender, string $type): void
    {
        try {
            $maxAttempts = 3;
            $result = false;

            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                try {
                    $tempTask = new Task();
                    $tempTask->id = $templateTask->id;
                    $tempTask->title = $templateTask->name;
                    $tempTask->description = $templateTask->description ?? 'مهمة من قالب';
                    $tempTask->points = $templateTask->points ?? 0;

                    $result = $this->slackService->sendTaskCompletedNotification($tempTask, $recipient, $sender);

                    if ($result) {
                        break;
                    }

                } catch (\Exception $e) {
                }

                if ($attempt < $maxAttempts) {
                    usleep(pow(2, $attempt - 1) * 100000);
                }
            }

        } catch (\Exception $e) {
        }
    }

    private function createTemplateTaskPointsAwardedNotification(TemplateTaskUser $templateTaskUser, $templateTask, User $recipient, User $awardedBy, int $points, ?string $note): void
    {
        try {
            $message = "حصلت على {$points} نقطة من {$awardedBy->name} لمهمة القالب: {$templateTask->name}";
            if ($note) {
                $message .= " - ملاحظة: {$note}";
            }

            $notification = Notification::create([
                'user_id' => $recipient->id,
                'type' => 'template_points_awarded',
                'data' => [
                    'message' => $message,
                    'template_task_user_id' => $templateTaskUser->id,
                    'template_task_id' => $templateTask->id,
                    'template_task_name' => $templateTask->name,
                    'awarded_by_id' => $awardedBy->id,
                    'awarded_by_name' => $awardedBy->name,
                    'points' => $points,
                    'note' => $note,
                    'awarded_at' => $this->getCurrentCairoTime()->toISOString(),
                    'project_id' => $templateTaskUser->project_id,
                    'url' => route('tasks.my-tasks') . '?template_task_id=' . $templateTask->id
                ],
                'related_id' => $templateTaskUser->id
            ]);

        } catch (\Exception $e) {
        }
    }

    private function sendExternalNotificationsForTemplatePoints(TemplateTaskUser $templateTaskUser, $templateTask, User $recipient, User $sender, string $type, int $points): void
    {
        if ($recipient->fcm_token) {
            try {
                $this->sendFirebaseNotificationForTemplatePoints($templateTaskUser, $templateTask, $recipient, $sender, $type, $points);
            } catch (\Exception $e) {
            }
        }

        if ($recipient->slack_user_id) {
            try {
                $this->sendSlackNotificationForTemplatePoints($templateTaskUser, $templateTask, $recipient, $sender, $type, $points);
            } catch (\Exception $e) {
            }
        }
    }

    private function sendFirebaseNotificationForTemplatePoints(TemplateTaskUser $templateTaskUser, $templateTask, User $recipient, User $sender, string $type, int $points): void
    {
        try {
            $title = 'نقاط جديدة من مهمة قالب';
            $body = "حصلت على {$points} نقطة من {$sender->name} لمهمة القالب: {$templateTask->name}";
            $link = '/tasks/my-tasks?template_task_id=' . $templateTask->id;

            $result = $this->firebaseService->sendNotification(
                $recipient->fcm_token,
                $title,
                $body,
                $link
            );

        } catch (\Exception $e) {
        }
    }

    private function sendSlackNotificationForTemplatePoints(TemplateTaskUser $templateTaskUser, $templateTask, User $recipient, User $sender, string $type, int $points): void
    {
        try {
            $maxAttempts = 3;
            $result = false;

            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                try {
                    $tempTask = new Task();
                    $tempTask->id = $templateTask->id;
                    $tempTask->title = $templateTask->name;
                    $tempTask->description = $templateTask->description ?? 'مهمة من قالب';
                    $tempTask->points = $templateTask->points ?? 0;

                    $result = $this->slackService->sendPointsAwardedNotification($tempTask, $recipient, $sender, $points);

                    if ($result) {
                        break;
                    }

                } catch (\Exception $e) {
                }

                if ($attempt < $maxAttempts) {
                    usleep(pow(2, $attempt - 1) * 100000);
                }
            }

        } catch (\Exception $e) {
        }
    }
}
