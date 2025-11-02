<?php

namespace App\Services\TaskController;

use App\Models\Task;
use App\Models\TaskUser;
use App\Models\User;
use App\Services\Tasks\TaskCompletionService;
use App\Services\Tasks\TaskTimeSplitService;
use App\Services\ProjectManagement\ProjectServiceStatusService;
use App\Traits\HasNTPTime;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TaskStatusService
{
    use HasNTPTime;

    protected $taskCompletionService;
    protected $taskTimeSplitService;
    protected $projectServiceStatusService;
    protected $taskDeliveryNotificationService;

    public function __construct(
        TaskCompletionService $taskCompletionService,
        TaskTimeSplitService $taskTimeSplitService,
        ProjectServiceStatusService $projectServiceStatusService,
        \App\Services\Notifications\TaskDeliveryNotificationService $taskDeliveryNotificationService
    ) {
        $this->taskCompletionService = $taskCompletionService;
        $this->taskTimeSplitService = $taskTimeSplitService;
        $this->projectServiceStatusService = $projectServiceStatusService;
        $this->taskDeliveryNotificationService = $taskDeliveryNotificationService;
    }

    public function changeTaskStatus(Task $task, string $newStatus): array
    {
        if (!$task->canUpdateStatus()) {
            $errorMessage = $task->getStatusUpdateErrorMessage();

            Log::warning('Blocked task status update due to cancelled project', [
                'task_id' => $task->id,
                'project_id' => $task->project_id,
                'project_status' => $task->project?->status,
                'attempted_status' => $newStatus,
                'user_id' => Auth::id()
            ]);

            return [
                'success' => false,
                'message' => $errorMessage ?: 'لا يمكن تحديث حالة المهمة لأن المشروع تم إلغاؤه',
                'code' => 403
            ];
        }

        $previousStatus = $task->status;
        $user = Auth::user();

        $task->status = $newStatus;
        $task->save();

        $result = ['success' => true, 'message' => 'تم تغيير حالة المهمة بنجاح'];

        if ($newStatus === 'completed' && $previousStatus !== 'completed') {
            $completionResult = $this->taskCompletionService->processTaskCompletion($task, $user);

            if (
                is_array($completionResult) && isset($completionResult['success']) && $completionResult['success'] &&
                isset($completionResult['data']) && is_array($completionResult['data']) &&
                !empty($completionResult['data']['is_promotion']) &&
                !empty($completionResult['data']['badge'])
            ) {

                $badge = $completionResult['data']['badge'] ?? null;
                $result['message'] = 'تم تغيير حالة المهمة وترقية الشارة إلى: ' . ($badge ? $badge->name : 'غير معروف');
                $result['badge'] = [
                    'name' => $badge ? $badge->name : 'غير معروف',
                    'icon' => $badge ? $badge->icon : '',
                    'color' => $badge ? $badge->color_code : ''
                ];
            }
        }

        Log::info('Task status changed', [
            'task_id' => $task->id,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'user_id' => $user->id
        ]);

        return $result;
    }

    public function updateTaskUserStatus(int $taskUserId, string $status): array
    {
        try {
            $taskUser = TaskUser::findOrFail($taskUserId);

            $task = $taskUser->task;
            if ($task && !$task->canUpdateStatus()) {
                $errorMessage = $task->getStatusUpdateErrorMessage();

                Log::warning('Blocked task user status update due to cancelled project', [
                    'task_user_id' => $taskUserId,
                    'task_id' => $task->id,
                    'project_id' => $task->project_id,
                    'project_status' => $task->project?->status,
                    'attempted_status' => $status,
                    'user_id' => Auth::id()
                ]);

                return [
                    'success' => false,
                    'message' => $errorMessage ?: 'لا يمكن تحديث حالة المهمة لأن المشروع تم إلغاؤه',
                    'no_change' => true,
                    'code' => 403
                ];
            }

            if ($taskUser->user_id != Auth::id()) {
                Log::warning('Unauthorized task status update attempt', [
                    'task_user_id' => $taskUserId,
                    'task_owner_id' => $taskUser->user_id,
                    'attempted_by_user_id' => Auth::id(),
                    'attempted_status' => $status
                ]);

                return [
                    'success' => false,
                    'message' => 'غير مصرح لك بتحديث حالة هذه المهمة - هذه المهمة مخصصة لمستخدم آخر',
                    'no_change' => true,
                    'code' => 403
                ];
            }

            if ($taskUser->is_transferred === true) {
                Log::info('Blocked status update on transferred-from record', [
                    'task_user_id' => $taskUserId,
                    'user_id' => Auth::id(),
                    'status_attempt' => $status
                ]);

                return [
                    'success' => false,
                    'message' => 'تم نقل هذه المهمة من حسابك - لا يمكنك تغيير حالتها',
                    'no_change' => true,
                    'code' => 403
                ];
            }

            if (!$taskUser->canChangeStatus()) {
                Log::info('Blocked status update on approved task', [
                    'task_user_id' => $taskUserId,
                    'user_id' => Auth::id(),
                    'status_attempt' => $status,
                    'has_administrative_approval' => $taskUser->hasAdministrativeApproval(),
                    'has_technical_approval' => $taskUser->hasTechnicalApproval()
                ]);

                return [
                    'success' => false,
                    'message' => 'لا يمكن تغيير حالة مهمة تم اعتمادها مسبقاً',
                    'no_change' => true,
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

            if ($status === 'completed') {
                $itemsValidation = $this->validateTaskItems($taskUser);
                if (!$itemsValidation['valid']) {
                    return [
                        'success' => false,
                        'message' => $itemsValidation['message'],
                        'pending_items' => $itemsValidation['pending_items'] ?? [],
                        'code' => 400
                    ];
                }
            }

            $previousStatus = $taskUser->status;
            $currentTime = $this->getCurrentCairoTime();
            $minutesSpent = 0;

            $minutesSpent = $this->processTaskUserTiming($taskUser, $previousStatus, $status, $currentTime);

            $taskUser->status = $status;
            $taskUser->save();

            if ($status === 'completed') {
                $this->checkAndUpdateTaskCompletion($taskUser);

                try {
                    $this->taskDeliveryNotificationService->notifyTaskCompleted($taskUser->fresh());
                } catch (\Exception $e) {
                    Log::warning('Failed to send task completion notifications', [
                        'task_user_id' => $taskUser->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $this->updateProjectServiceStatus($taskUser);

            return [
                'success' => true,
                'message' => 'تم تحديث حالة المهمة بنجاح',
                'task' => $taskUser,
                'minutesSpent' => ($taskUser->actual_hours * 60) + $taskUser->actual_minutes
            ];
        } catch (\Exception $e) {
            Log::error('Failed to update task user status', [
                'task_user_id' => $taskUserId,
                'status' => $status,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث حالة المهمة: ' . $e->getMessage()
            ];
        }
    }

    private function processTaskUserTiming(TaskUser $taskUser, string $previousStatus, string $newStatus, Carbon $currentTime): int
    {
        $minutesSpent = 0;

        if ($previousStatus === 'in_progress' && in_array($newStatus, ['paused', 'completed'])) {
            if ($taskUser->start_date) {
                $startTime = Carbon::parse($taskUser->start_date);

                $minutesSpent = $this->taskTimeSplitService->calculateAndUpdateCheckpoint(
                    $taskUser->id,
                    false,
                    $startTime,
                    $currentTime,
                    $taskUser->user_id
                );

                $totalMinutes = ($taskUser->actual_hours * 60) + $taskUser->actual_minutes + $minutesSpent;
                $hours = intdiv($totalMinutes, 60);
                $minutes = $totalMinutes % 60;

                $taskUser->actual_hours = $hours;
                $taskUser->actual_minutes = $minutes;

                if ($newStatus === 'completed') {
                    $taskUser->completed_date = $currentTime;
                }

                Log::info("Task time calculated with splitting", [
                    'task_user_id' => $taskUser->id,
                    'user_id' => $taskUser->user_id,
                    'original_minutes' => $startTime->diffInMinutes($currentTime),
                    'split_minutes' => $minutesSpent,
                    'total_minutes' => $totalMinutes
                ]);
            }
        } elseif (in_array($previousStatus, ['new', 'paused']) && $newStatus === 'in_progress') {
            $taskUser->start_date = $currentTime;
        }

        return $minutesSpent;
    }

    private function checkAndUpdateTaskCompletion(TaskUser $taskUser): void
    {
        $task = Task::find($taskUser->task_id);
        if (!$task) return;

        $totalAssignedUsers = $task->users()->count();
        $completedUsers = $task->users()
            ->wherePivot('status', 'completed')
            ->count();

        if ($totalAssignedUsers > 0 && $completedUsers === $totalAssignedUsers) {
            $task->status = 'completed';
            $task->completed_date = $this->getCurrentCairoTime();
            $task->save();

            Log::info('Task marked as completed automatically', [
                'task_id' => $task->id,
                'total_users' => $totalAssignedUsers,
                'completed_users' => $completedUsers
            ]);
        }
    }

    private function updateProjectServiceStatus(TaskUser $taskUser): void
    {
        $task = $taskUser->task;
        if ($task && $task->project && $task->service_id) {
            $this->projectServiceStatusService->updateServiceStatus($task->project, $task->service_id);
        }
    }

    public function getTaskStatusStatistics(?int $projectId = null, ?int $serviceId = null): array
    {
        $query = Task::query();

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        if ($serviceId) {
            $query->where('service_id', $serviceId);
        }

        $stats = $query->selectRaw('
            status,
            COUNT(*) as count,
            AVG(CASE
                WHEN estimated_hours IS NOT NULL AND estimated_minutes IS NOT NULL
                THEN (estimated_hours * 60 + estimated_minutes)
                ELSE NULL
            END) as avg_estimated_minutes
        ')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        return [
            'new' => $stats->get('new', (object)['count' => 0, 'avg_estimated_minutes' => 0]),
            'in_progress' => $stats->get('in_progress', (object)['count' => 0, 'avg_estimated_minutes' => 0]),
            'paused' => $stats->get('paused', (object)['count' => 0, 'avg_estimated_minutes' => 0]),
            'completed' => $stats->get('completed', (object)['count' => 0, 'avg_estimated_minutes' => 0]),
            'cancelled' => $stats->get('cancelled', (object)['count' => 0, 'avg_estimated_minutes' => 0]),
        ];
    }

    public function getUserTaskStatusStatistics(int $userId, ?int $projectId = null): array
    {
        $query = TaskUser::where('user_id', $userId);

        if ($projectId) {
            $query->whereHas('task', function ($q) use ($projectId) {
                $q->where('project_id', $projectId);
            });
        }

        $stats = $query->selectRaw('
            status,
            COUNT(*) as count,
            SUM(actual_hours * 60 + actual_minutes) as total_actual_minutes,
            AVG(estimated_hours * 60 + estimated_minutes) as avg_estimated_minutes
        ')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        return [
            'new' => $stats->get('new', (object)['count' => 0, 'total_actual_minutes' => 0, 'avg_estimated_minutes' => 0]),
            'in_progress' => $stats->get('in_progress', (object)['count' => 0, 'total_actual_minutes' => 0, 'avg_estimated_minutes' => 0]),
            'paused' => $stats->get('paused', (object)['count' => 0, 'total_actual_minutes' => 0, 'avg_estimated_minutes' => 0]),
            'completed' => $stats->get('completed', (object)['count' => 0, 'total_actual_minutes' => 0, 'avg_estimated_minutes' => 0]),
        ];
    }

    public function canChangeTaskStatus(Task $task, string $newStatus, ?User $user = null): array
    {
        $user = $user ?? Auth::user();

        $validTransitions = [
            'new' => ['in_progress', 'cancelled'],
            'in_progress' => ['paused', 'completed', 'cancelled'],
            'paused' => ['in_progress', 'completed', 'cancelled'],
            'completed' => [],
            'cancelled' => ['new']
        ];

        if (
            !isset($validTransitions[$task->status]) ||
            !in_array($newStatus, $validTransitions[$task->status])
        ) {
            return [
                'can_change' => false,
                'message' => 'لا يمكن تغيير الحالة من ' . $task->status . ' إلى ' . $newStatus
            ];
        }

        if (!method_exists($user, 'hasRole')) {
            $userTask = TaskUser::where('task_id', $task->id)
                ->where('user_id', $user->id)
                ->first();

            if (!$userTask) {
                return [
                    'can_change' => false,
                    'message' => 'غير مصرح لك بتغيير حالة هذه المهمة'
                ];
            }
        }

        return ['can_change' => true, 'message' => 'يمكن تغيير الحالة'];
    }

    public function getOverdueTasks(?int $userId = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = Task::with(['project', 'service', 'users'])
            ->where('due_date', '<', $this->getCurrentCairoTime())
            ->whereIn('status', ['new', 'in_progress', 'paused']);

        if ($userId) {
            $query->whereHas('users', function ($q) use ($userId) {
                $q->where('users.id', $userId);
            });
        }

        return $query->orderBy('due_date')->get();
    }

    public function getUpcomingTasks(int $days = 3, ?int $userId = null): \Illuminate\Database\Eloquent\Collection
    {
        $currentTime = $this->getCurrentCairoTime();
        $endDate = $currentTime->copy()->addDays($days);

        $query = Task::with(['project', 'service', 'users'])
            ->whereBetween('due_date', [$currentTime, $endDate])
            ->whereIn('status', ['new', 'in_progress', 'paused']);

        if ($userId) {
            $query->whereHas('users', function ($q) use ($userId) {
                $q->where('users.id', $userId);
            });
        }

        return $query->orderBy('due_date')->get();
    }

    private function validateTaskItems(TaskUser $taskUser): array
    {
        $items = $taskUser->items ?? [];

        if (empty($items)) {
            return ['valid' => true];
        }

        $pendingItems = [];

        foreach ($items as $item) {
            $status = $item['status'] ?? 'pending';

            if ($status === 'pending') {
                $pendingItems[] = [
                    'id' => $item['id'] ?? '',
                    'title' => $item['title'] ?? 'بند بدون عنوان',
                    'description' => $item['description'] ?? ''
                ];
            }
        }

        if (!empty($pendingItems)) {
            $count = count($pendingItems);
            $itemsList = implode('، ', array_column($pendingItems, 'title'));

            return [
                'valid' => false,
                'message' => "⚠️ لا يمكن إكمال المهمة! يجب تحديد حالة جميع البنود أولاً.\n\nالبنود المتبقية ({$count}): {$itemsList}",
                'pending_items' => $pendingItems
            ];
        }

        return ['valid' => true];
    }
}
