<?php

namespace App\Services\TaskController;

use App\Models\Task;
use App\Models\TaskUser;
use App\Models\Project;
use App\Models\User;
use App\Traits\SeasonAwareTrait;
use App\Traits\HasNTPTime;
use App\Services\SlackNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TaskUserAssignmentService
{
    use SeasonAwareTrait, HasNTPTime;

    protected $slackNotificationService;

    public function __construct(SlackNotificationService $slackNotificationService)
    {
        $this->slackNotificationService = $slackNotificationService;
    }

    public function assignUsersToTask(Task $task, array $assignedUsers, bool $isFlexible = false, bool $isAdditionalTask = false): array
    {
        $results = [];
        $seasonId = $this->getSeasonIdForTask($task);

        if (empty($assignedUsers)) {
            Log::warning('No assigned users provided for task', ['task_id' => $task->id]);
            return $results;
        }

        foreach ($assignedUsers as $index => $assignedUser) {
            try {
                // ✅ التحقق من أن المستخدم مشارك في المشروع (إذا كانت المهمة مرتبطة بمشروع)
                if ($task->project_id) {
                    $isParticipant = \App\Models\ProjectServiceUser::where('project_id', $task->project_id)
                        ->where('user_id', $assignedUser['user_id'])
                        ->exists();

                    if (!$isParticipant) {
                        $user = User::find($assignedUser['user_id']);
                        $userName = $user ? $user->name : 'المستخدم';

                        Log::warning('User not participant in project', [
                            'task_id' => $task->id,
                            'project_id' => $task->project_id,
                            'user_id' => $assignedUser['user_id']
                        ]);

                        throw new \Exception("المستخدم '{$userName}' غير مشارك في المشروع. يجب أن يكون المستخدم مشاركاً في المشروع لتعيينه للمهمة.");
                    }
                }

                $taskUserData = $this->prepareTaskUserData($task, $assignedUser, $seasonId, $isFlexible, $isAdditionalTask);

                Log::info("Creating TaskUser with data", [
                    'task_id' => $task->id,
                    'user_id' => $assignedUser['user_id'],
                    'data' => $taskUserData
                ]);

                $taskUser = TaskUser::create($taskUserData);

                // نسخ البنود من المهمة الأساسية إلى TaskUser
                $taskItemService = app(\App\Services\Tasks\TaskItemService::class);
                $taskItemService->copyItemsToTaskUser($task, $taskUser);

                $results[] = $taskUser;

                // إرسال إشعار Slack للمستخدم المعين للمهمة
                $user = User::find($assignedUser['user_id']);
                $currentUser = Auth::user();

                if ($user && $user->slack_user_id && $currentUser) {
                    $this->slackNotificationService->sendTaskAssignmentNotification(
                        $task,
                        $user,
                        $currentUser
                    );
                }

                Log::info("TaskUser created successfully", ['id' => $taskUser->id]);
            } catch (\Exception $e) {
                Log::error("Failed to create TaskUser for user {$assignedUser['user_id']}", [
                    'task_id' => $task->id,
                    'user_id' => $assignedUser['user_id'],
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }

        return $results;
    }

    public function updateTaskUserAssignments(Task $task, array $assignedUsers, array $taskEstimation, bool $isAdditionalTask = false): array
    {
        $seasonId = $this->getSeasonIdForTask($task);
        $results = [];

        $existingAssignments = TaskUser::where('task_id', $task->id)->get()->keyBy(function ($item) {
            return $item->user_id . '-' . $item->role;
        });

        foreach ($assignedUsers as $assignedUser) {
            // ✅ التحقق من أن المستخدم مشارك في المشروع (إذا كانت المهمة مرتبطة بمشروع)
            if ($task->project_id) {
                $isParticipant = \App\Models\ProjectServiceUser::where('project_id', $task->project_id)
                    ->where('user_id', $assignedUser['user_id'])
                    ->exists();

                if (!$isParticipant) {
                    $user = User::find($assignedUser['user_id']);
                    $userName = $user ? $user->name : 'المستخدم';

                    Log::warning('User not participant in project during update', [
                        'task_id' => $task->id,
                        'project_id' => $task->project_id,
                        'user_id' => $assignedUser['user_id']
                    ]);

                    throw new \Exception("المستخدم '{$userName}' غير مشارك في المشروع. يجب أن يكون المستخدم مشاركاً في المشروع لتعيينه للمهمة.");
                }
            }

            $key = $assignedUser['user_id'] . '-' . $assignedUser['role'];

            $estimationData = $this->calculateUserEstimation($assignedUser, $taskEstimation);

            if (isset($existingAssignments[$key])) {
                $existing = $existingAssignments[$key];
                $existing->update([
                    'estimated_hours' => $estimationData['hours'],
                    'estimated_minutes' => $estimationData['minutes'],
                    'due_date' => $task->due_date,
                    'season_id' => $existing->season_id ?? $seasonId,
                    'is_additional_task' => $isAdditionalTask,
                    'task_source' => $isAdditionalTask ? 'additional' : 'assigned',
                ]);

                $results[] = $existing;
                $existingAssignments->forget($key);
            } else {
                $taskUser = TaskUser::create([
                    'task_id' => $task->id,
                    'user_id' => $assignedUser['user_id'],
                    'season_id' => $seasonId,
                    'role' => $assignedUser['role'],
                    'status' => 'new',
                    'estimated_hours' => $estimationData['hours'],
                    'estimated_minutes' => $estimationData['minutes'],
                    'due_date' => $task->due_date,
                    'is_additional_task' => $isAdditionalTask,
                    'task_source' => $isAdditionalTask ? 'additional' : 'assigned',
                ]);

                // إرسال إشعار Slack للمستخدم الجديد المعين للمهمة
                $user = User::find($assignedUser['user_id']);
                $currentUser = Auth::user();

                if ($user && $user->slack_user_id && $currentUser) {
                    $this->slackNotificationService->sendTaskAssignmentNotification(
                        $task,
                        $user,
                        $currentUser
                    );
                }

                $results[] = $taskUser;
            }
        }

        foreach ($existingAssignments as $assignment) {
            $assignment->delete();
        }

        return $results;
    }

    public function updateTaskUserStatus(int $taskUserId, string $status, int $userId): array
    {
        $taskUser = TaskUser::findOrFail($taskUserId);

        if ($taskUser->user_id != $userId) {
            return [
                'success' => false,
                'message' => 'غير مصرح لك بتحديث حالة هذه المهمة',
                'code' => 403
            ];
        }

        // التحقق من أن المهمة لم يتم اعتمادها مسبقاً
        if (!$taskUser->canChangeStatus()) {
            return [
                'success' => false,
                'message' => 'لا يمكن تغيير حالة مهمة تم اعتمادها مسبقاً',
                'code' => 403
            ];
        }

        if ($taskUser->status === $status) {
            return [
                'success' => true,
                'message' => 'لم يتم إجراء أي تغيير',
                'no_change' => true
            ];
        }

        $previousStatus = $taskUser->status;
        $currentTime = $this->getCurrentCairoTime();

        $this->updateTaskUserTiming($taskUser, $previousStatus, $status, $currentTime);

        $taskUser->status = $status;
        $taskUser->save();

        if ($status === 'completed') {
            $this->checkTaskCompletion($taskUser);
        }

        return [
            'success' => true,
            'message' => 'تم تحديث حالة المهمة بنجاح',
            'task_user' => $taskUser,
            'minutes_spent' => ($taskUser->actual_hours * 60) + $taskUser->actual_minutes
        ];
    }

    private function prepareTaskUserData(Task $task, array $assignedUser, int $seasonId, bool $isFlexible, bool $isAdditionalTask = false): array
    {
        $taskUserData = [
            'task_id' => $task->id,
            'user_id' => $assignedUser['user_id'],
            'season_id' => $seasonId,
            'role' => $assignedUser['role'] ?? 'غير محدد',
            'status' => 'new',
            'due_date' => $task->due_date,
            'is_flexible_time' => $isFlexible,
            'is_additional_task' => $isAdditionalTask,
            'task_source' => $isAdditionalTask ? 'additional' : 'assigned',
        ];

        if (!$isFlexible) {
            $estimationData = $this->calculateUserEstimation($assignedUser, [
                'hours' => $task->estimated_hours ?? 0,
                'minutes' => $task->estimated_minutes ?? 0
            ]);

            $taskUserData['estimated_hours'] = $estimationData['hours'];
            $taskUserData['estimated_minutes'] = $estimationData['minutes'];
        } else {
            $taskUserData['estimated_hours'] = null;
            $taskUserData['estimated_minutes'] = null;
        }

        return $taskUserData;
    }

    private function calculateUserEstimation(array $assignedUser, array $taskEstimation): array
    {
        $taskLevelHours = (int) ($taskEstimation['hours'] ?? 0);
        $taskLevelMinutes = (int) ($taskEstimation['minutes'] ?? 0);

        $userHoursProvided = array_key_exists('estimated_hours', $assignedUser) ?
            (int) $assignedUser['estimated_hours'] : null;
        $userMinutesProvided = array_key_exists('estimated_minutes', $assignedUser) ?
            (int) $assignedUser['estimated_minutes'] : null;

        $userProvidedSum = (int) (($userHoursProvided ?? 0) + ($userMinutesProvided ?? 0));
        $taskLevelSum = (int) ($taskLevelHours + $taskLevelMinutes);

        if ($userHoursProvided === null && $userMinutesProvided === null) {
            return ['hours' => $taskLevelHours, 'minutes' => $taskLevelMinutes];
        } elseif ($userProvidedSum === 0 && $taskLevelSum > 0) {
            return ['hours' => $taskLevelHours, 'minutes' => $taskLevelMinutes];
        } else {
            return ['hours' => $userHoursProvided ?? 0, 'minutes' => $userMinutesProvided ?? 0];
        }
    }

    private function getSeasonIdForTask(Task $task): int
    {
        $project = Project::find($task->project_id);

        if ($project && $project->season_id) {
            return $project->season_id;
        }

        $currentSeasonId = $this->getCurrentSeasonId();

        // إذا لم يوجد موسم نشط، استخدم موسم افتراضي (أول موسم في النظام)
        if (!$currentSeasonId) {
            $firstSeason = \App\Models\Season::orderBy('id', 'asc')->first();
            return $firstSeason ? $firstSeason->id : 1; // قيمة افتراضية كحل أخير
        }

        return $currentSeasonId;
    }

    private function updateTaskUserTiming(TaskUser $taskUser, string $previousStatus, string $newStatus, Carbon $currentTime): void
    {
        if ($previousStatus === 'in_progress' && in_array($newStatus, ['paused', 'completed'])) {
            if ($taskUser->start_date) {
                $startTime = Carbon::parse($taskUser->start_date);
                $minutesSpent = $startTime->diffInMinutes($currentTime);

                $totalMinutes = ($taskUser->actual_hours * 60) + $taskUser->actual_minutes + $minutesSpent;
                $taskUser->actual_hours = intdiv($totalMinutes, 60);
                $taskUser->actual_minutes = $totalMinutes % 60;

                if ($newStatus === 'completed') {
                    $taskUser->completed_date = $currentTime;
                }

                Log::info("Task timing updated", [
                    'task_user_id' => $taskUser->id,
                    'minutes_spent' => $minutesSpent,
                    'total_minutes' => $totalMinutes
                ]);
            }
        } elseif (in_array($previousStatus, ['new', 'paused']) && $newStatus === 'in_progress') {
            $taskUser->start_date = $currentTime;
        }
    }

    private function checkTaskCompletion(TaskUser $taskUser): void
    {
        $task = Task::find($taskUser->task_id);
        if (!$task) return;

        $totalUsers = $task->users()->count();
        $completedUsers = $task->users()
            ->wherePivot('status', 'completed')
            ->count();

        if ($totalUsers === $completedUsers) {
            $task->status = 'completed';
            $task->completed_date = $this->getCurrentCairoTime();
            $task->save();

            Log::info('Task marked as completed', [
                'task_id' => $task->id,
                'total_users' => $totalUsers,
                'completed_users' => $completedUsers
            ]);
        }
    }

    public function removeAllUserAssignments(int $taskId): bool
    {
        try {
            TaskUser::where('task_id', $taskId)->delete();
            Log::info('All user assignments removed for task', ['task_id' => $taskId]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to remove user assignments', [
                'task_id' => $taskId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getTaskUserAssignments(int $taskId): \Illuminate\Database\Eloquent\Collection
    {
        return TaskUser::where('task_id', $taskId)
            ->with(['user', 'task'])
            ->get();
    }
}
