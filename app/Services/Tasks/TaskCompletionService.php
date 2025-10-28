<?php

namespace App\Services\Tasks;

use App\Models\Season;
use App\Models\Task;
use App\Models\TaskUser;
use App\Models\User;
use App\Services\BadgeService;

class TaskCompletionService
{
    protected $badgeService;

    public function __construct(BadgeService $badgeService)
    {
        $this->badgeService = $badgeService;
    }

    public function processTaskCompletion(Task $task, User $user)
    {
        $currentSeason = Season::getCurrentActiveSeason();

        if (!$currentSeason) {
            return [
                'success' => false,
                'message' => 'لا يوجد موسم نشط حاليًا',
                'data' => null
            ];
        }

        $taskUser = TaskUser::where('task_id', $task->id)
                           ->where('user_id', $user->id)
                           ->first();

        if (!$taskUser) {
            return [
                'success' => false,
                'message' => 'المهمة غير مخصصة لهذا المستخدم',
                'data' => null
            ];
        }

        $points = $this->calculatePointsForTask($task);

        $result = $this->badgeService->addPointsToUser(
            $user,
            $points,
            1,
            0,
            0
        );

        return $result;
    }

    protected function calculatePointsForTask(Task $task)
    {
        if ($task->isGraphicTask()) {
            $primaryGraphicTaskType = $task->primaryGraphicTaskType();
            if ($primaryGraphicTaskType) {
                return $primaryGraphicTaskType->points;
            }
        }

        if ($task->points && $task->points > 0) {
            return $task->points;
        }

        $priorityPoints = [
            'low' => 50,
            'medium' => 100,
            'high' => 150,
            'urgent' => 200,
        ];

        $priority = strtolower($task->priority ?? 'medium');

        return $priorityPoints[$priority] ?? 100;
    }
}
