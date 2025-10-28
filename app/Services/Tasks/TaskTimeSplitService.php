<?php

namespace App\Services\Tasks;
use App\Traits\HasNTPTime;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TaskTimeSplitService
{
    use HasNTPTime;
    public function calculateSplitTimeForTask($taskId, $isTemplate = false, Carbon $startTime, Carbon $endTime, $userId): int
    {
        try {
            if (!$taskId || !$userId || !$startTime || !$endTime) {
                return $startTime->diffInMinutes($endTime);
            }

            if ($startTime->gte($endTime)) {
                return 0;
            }

            $overlappingTasks = $this->getOverlappingTasks($userId, $startTime, $endTime, $taskId, $isTemplate);

            if (empty($overlappingTasks)) {
                return $startTime->diffInMinutes($endTime);
            }

            $timeline = $this->createTimeline($overlappingTasks, $startTime, $endTime, $taskId, $isTemplate);

            if (empty($timeline)) {
                return $startTime->diffInMinutes($endTime);
            }

            return $this->calculateAllocatedTime($timeline, $taskId, $isTemplate);

        } catch (\Exception $e) {
            return $startTime->diffInMinutes($endTime);
        }
    }

    private function getOverlappingTasks($userId, Carbon $startTime, Carbon $endTime, $excludeTaskId, $isTemplate): array
    {
        $overlappingTasks = [];

        $regularTasks = DB::table('task_users')
            ->join('tasks', 'task_users.task_id', '=', 'tasks.id')
            ->where('task_users.user_id', $userId)
            ->where('task_users.status', 'in_progress')
            ->whereNotNull('task_users.start_date')
            ->where(function($query) use ($startTime, $endTime) {
                $query->where('task_users.start_date', '<', $endTime)
                      ->where(function($q) use ($startTime) {
                          $q->where('task_users.completed_date', '>', $startTime)
                            ->orWhereNull('task_users.completed_date');
                      });
            })
            ->when(!$isTemplate && $excludeTaskId, function($query) use ($excludeTaskId) {
                $query->where('task_users.id', '!=', $excludeTaskId);
            })
            ->select(
                'task_users.id',
                'task_users.start_date as started_at',
                'task_users.completed_date',
                'tasks.name',
                DB::raw("'regular' as type")
            )
            ->get();

        $templateTasks = DB::table('template_task_user')
            ->join('template_tasks', 'template_task_user.template_task_id', '=', 'template_tasks.id')
            ->where('template_task_user.user_id', $userId)
            ->where('template_task_user.status', 'in_progress')
            ->whereNotNull('template_task_user.started_at')
            ->where(function($query) use ($startTime, $endTime) {
                $query->where('template_task_user.started_at', '<', $endTime)
                      ->where(function($q) use ($startTime) {
                          $q->where('template_task_user.completed_at', '>', $startTime)
                            ->orWhereNull('template_task_user.completed_at');
                      });
            })
            ->when($isTemplate && $excludeTaskId, function($query) use ($excludeTaskId) {
                $query->where('template_task_user.id', '!=', $excludeTaskId);
            })
            ->select(
                'template_task_user.id',
                'template_task_user.started_at',
                'template_task_user.completed_at',
                'template_tasks.name',
                DB::raw("'template' as type")
            )
            ->get();

        foreach ($regularTasks as $task) {
            $overlappingTasks[] = [
                'id' => $task->id,
                'started_at' => Carbon::parse($task->started_at),
                'completed_at' => $task->completed_date ? Carbon::parse($task->completed_date) : null,
                'name' => $task->name,
                'type' => $task->type
            ];
        }

        foreach ($templateTasks as $task) {
            $overlappingTasks[] = [
                'id' => $task->id,
                'started_at' => Carbon::parse($task->started_at),
                'completed_at' => $task->completed_at ? Carbon::parse($task->completed_at) : null,
                'name' => $task->name,
                'type' => $task->type
            ];
        }

        return $overlappingTasks;
    }

    private function createTimeline(array $overlappingTasks, Carbon $startTime, Carbon $endTime, $currentTaskId, $isTemplate): array
    {
        try {
            $timeline = [];

            $currentTask = [
                'id' => $currentTaskId,
                'started_at' => $startTime,
                'completed_at' => $endTime,
                'type' => $isTemplate ? 'template' : 'regular'
            ];

            $allTasks = array_merge([$currentTask], $overlappingTasks);

            usort($allTasks, function($a, $b) {
                return $a['started_at']->timestamp <=> $b['started_at']->timestamp;
            });

            $timePoints = collect([$startTime, $endTime]);

            foreach ($allTasks as $task) {
                if (isset($task['started_at']) && $task['started_at'] instanceof Carbon) {
                    if ($task['started_at']->between($startTime, $endTime)) {
                        $timePoints->push($task['started_at']);
                    }

                    if (isset($task['completed_at']) && $task['completed_at'] instanceof Carbon &&
                        $task['completed_at']->between($startTime, $endTime)) {
                        $timePoints->push($task['completed_at']);
                    }
                }
            }

            $timePoints = $timePoints->unique()->sort()->values();

            if ($timePoints->count() < 2) {
                return [];
            }

            for ($i = 0; $i < $timePoints->count() - 1; $i++) {
                $segmentStart = $timePoints->get($i);
                $segmentEnd = $timePoints->get($i + 1);

                if (!$segmentStart || !$segmentEnd || $segmentStart->gte($segmentEnd)) {
                    continue;
                }

                $activeTasks = [];
                foreach ($allTasks as $task) {
                    if (isset($task['started_at']) && $task['started_at'] instanceof Carbon) {
                        $taskStarted = $task['started_at']->lte($segmentStart);

                        $taskStillActive = true;
                        if (isset($task['completed_at']) && $task['completed_at'] instanceof Carbon) {
                            $taskStillActive = $task['completed_at']->gt($segmentStart);
                        }

                        if ($taskStarted && $taskStillActive) {
                            $activeTasks[] = [
                                'id' => $task['id'],
                                'type' => $task['type']
                            ];
                        }
                    }
                }

                if (!empty($activeTasks)) {
                    $timeline[] = [
                        'start' => $segmentStart,
                        'end' => $segmentEnd,
                        'duration_minutes' => $segmentStart->diffInMinutes($segmentEnd),
                        'active_tasks' => $activeTasks,
                        'tasks_count' => count($activeTasks)
                    ];
                }
            }

            return $timeline;

        } catch (\Exception $e) {
            return [];
        }
    }

    private function calculateAllocatedTime(array $timeline, $taskId, $isTemplate): int
    {
        $totalAllocatedMinutes = 0;
        $taskType = $isTemplate ? 'template' : 'regular';

        foreach ($timeline as $segment) {
            $taskFound = false;
            foreach ($segment['active_tasks'] as $activeTask) {
                if ($activeTask['id'] == $taskId && $activeTask['type'] == $taskType) {
                    $taskFound = true;
                    break;
                }
            }

            if ($taskFound) {
                $allocatedTime = $segment['duration_minutes'] / $segment['tasks_count'];
                $totalAllocatedMinutes += $allocatedTime;
            }
        }

        return round($totalAllocatedMinutes);
    }

    public function recalculateOverlappingTasksTime($userId, Carbon $startTime, Carbon $endTime): array
    {
        $results = [];

        $overlappingTasks = $this->getOverlappingTasks($userId, $startTime, $endTime, null, false);
        $overlappingTemplateTasks = $this->getOverlappingTasks($userId, $startTime, $endTime, null, true);

        foreach ($overlappingTasks as $task) {
            if ($task['type'] === 'regular') {
                $allocatedTime = $this->calculateSplitTimeForTask(
                    $task['id'],
                    false,
                    $task['started_at'],
                    $endTime,
                    $userId
                );
                $results[] = [
                    'task_id' => $task['id'],
                    'type' => 'regular',
                    'allocated_minutes' => $allocatedTime
                ];
            }
        }

        foreach ($overlappingTemplateTasks as $task) {
            if ($task['type'] === 'template') {
                $allocatedTime = $this->calculateSplitTimeForTask(
                    $task['id'],
                    true,
                    $task['started_at'],
                    $endTime,
                    $userId
                );
                $results[] = [
                    'task_id' => $task['id'],
                    'type' => 'template',
                    'allocated_minutes' => $allocatedTime
                ];
            }
        }

        return $results;
    }

    public function calculateAndUpdateCheckpoint($taskId, $isTemplate, Carbon $startTime, Carbon $endTime, $userId): int
    {
        $allocatedMinutes = $this->calculateSplitTimeForTask($taskId, $isTemplate, $startTime, $endTime, $userId);
        $this->updateActiveTasksCheckpoint($userId, $endTime, $taskId, $isTemplate);
        return $allocatedMinutes;
    }

    public function updateActiveTasksCheckpoint($userId, Carbon $currentTime, $excludeTaskId, $excludeIsTemplate): void
    {
        try {
            $activeTasks = DB::table('task_users')
                ->where('user_id', $userId)
                ->where('status', 'in_progress')
                ->whereNotNull('start_date')
                ->when(!$excludeIsTemplate, function($query) use ($excludeTaskId) {
                    $query->where('id', '!=', $excludeTaskId);
                })
                ->select('id', 'start_date', 'actual_hours', 'actual_minutes')
                ->get();

            foreach ($activeTasks as $task) {
                $startTime = Carbon::parse($task->start_date);

                $allocatedMinutes = $this->calculateSplitTimeForTask(
                    $task->id,
                    false,
                    $startTime,
                    $currentTime,
                    $userId
                );

                $totalMinutes = ($task->actual_hours * 60) + $task->actual_minutes + $allocatedMinutes;
                $hours = intdiv($totalMinutes, 60);
                $minutes = $totalMinutes % 60;

                DB::table('task_users')
                    ->where('id', $task->id)
                    ->update([
                        'actual_hours' => $hours,
                        'actual_minutes' => $minutes,
                        'start_date' => $currentTime,
                        'updated_at' => $this->getCurrentCairoTime()
                    ]);
            }

            $activeTemplateTasks = DB::table('template_task_user')
                ->where('user_id', $userId)
                ->where('status', 'in_progress')
                ->whereNotNull('started_at')
                ->when($excludeIsTemplate, function($query) use ($excludeTaskId) {
                    $query->where('id', '!=', $excludeTaskId);
                })
                ->select('id', 'started_at', 'actual_minutes')
                ->get();

            foreach ($activeTemplateTasks as $task) {
                $startTime = Carbon::parse($task->started_at);

                $allocatedMinutes = $this->calculateSplitTimeForTask(
                    $task->id,
                    true,
                    $startTime,
                    $currentTime,
                    $userId
                );

                $totalMinutes = ($task->actual_minutes ?? 0) + $allocatedMinutes;

                DB::table('template_task_user')
                    ->where('id', $task->id)
                    ->update([
                        'actual_minutes' => $totalMinutes,
                        'started_at' => $currentTime,
                        'updated_at' => $this->getCurrentCairoTime()
                    ]);
            }

        } catch (\Exception $e) {
            // Silent fail
        }
    }

    public function updateAllActiveTasksTime($userId, Carbon $currentTime): void
    {
        try {
            $searchStart = $currentTime->copy()->subHours(24);
            $searchEnd = $currentTime->copy();

            $activeTasks = $this->getAllActiveTasks($userId, $searchStart, $searchEnd);

            foreach ($activeTasks as $task) {
                $this->recalculateAndUpdateTaskTime($task, $userId, $currentTime);
            }

        } catch (\Exception $e) {
            // Silent fail
        }
    }

    private function getAllActiveTasks($userId, Carbon $searchStart, Carbon $searchEnd): array
    {
        $activeTasks = [];

        $regularTasks = DB::table('task_users')
            ->join('tasks', 'task_users.task_id', '=', 'tasks.id')
            ->where('task_users.user_id', $userId)
            ->where('task_users.status', 'in_progress')
            ->whereNotNull('task_users.start_date')
            ->where('task_users.start_date', '>=', $searchStart)
            ->select(
                'task_users.id',
                'task_users.start_date as started_at',
                'task_users.completed_date',
                'tasks.name',
                DB::raw("'regular' as type")
            )
            ->get();

        $templateTasks = DB::table('template_task_user')
            ->join('template_tasks', 'template_task_user.template_task_id', '=', 'template_tasks.id')
            ->where('template_task_user.user_id', $userId)
            ->where('template_task_user.status', 'in_progress')
            ->whereNotNull('template_task_user.started_at')
            ->where('template_task_user.started_at', '>=', $searchStart)
            ->select(
                'template_task_user.id',
                'template_task_user.started_at',
                'template_task_user.completed_at',
                'template_tasks.name',
                DB::raw("'template' as type")
            )
            ->get();

        foreach ($regularTasks as $task) {
            $activeTasks[] = [
                'id' => $task->id,
                'started_at' => Carbon::parse($task->started_at),
                'completed_at' => $task->completed_date ? Carbon::parse($task->completed_date) : null,
                'name' => $task->name,
                'type' => $task->type
            ];
        }

        foreach ($templateTasks as $task) {
            $activeTasks[] = [
                'id' => $task->id,
                'started_at' => Carbon::parse($task->started_at),
                'completed_at' => $task->completed_at ? Carbon::parse($task->completed_at) : null,
                'name' => $task->name,
                'type' => $task->type
            ];
        }

        return $activeTasks;
    }

    private function recalculateAndUpdateTaskTime(array $task, $userId, Carbon $currentTime): void
    {
        try {
            $isTemplate = $task['type'] === 'template';

            $allocatedMinutes = $this->calculateSplitTimeForTask(
                $task['id'],
                $isTemplate,
                $task['started_at'],
                $currentTime,
                $userId
            );

            if ($isTemplate) {
                DB::table('template_task_user')
                    ->where('id', $task['id'])
                    ->update([
                        'calculated_time' => $allocatedMinutes,
                        'updated_at' => $this->getCurrentCairoTime()
                    ]);
            } else {
                DB::table('task_users')
                    ->where('id', $task['id'])
                    ->update([
                        'calculated_time' => $allocatedMinutes,
                        'updated_at' => $this->getCurrentCairoTime()
                    ]);
            }

        } catch (\Exception $e) {
            // Silent fail
        }
    }
}
