<?php

namespace App\Services\Tasks;

use App\Models\Task;
use App\Models\TaskUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TaskService
{
    public function createTask(array $data, User $creator)
    {
        DB::beginTransaction();

        try {
            $task = new Task();
            $task->name = $data['name'];
            $task->description = $data['description'] ?? null;
            $task->status = $data['status'] ?? 'pending';
            $task->priority = $data['priority'] ?? 'medium';
            $task->due_date = $data['due_date'] ?? null;
            $task->project_id = $data['project_id'] ?? null;
            $task->creator_id = $creator->id;

            if (isset($data['season_id'])) {
                $task->season_id = $data['season_id'];
            }

            $task->save();

            if (!empty($data['user_ids'])) {
                foreach ($data['user_ids'] as $userId) {
                    TaskUser::create([
                        'task_id' => $task->id,
                        'user_id' => $userId,
                        'season_id' => $data['season_id'] ?? null,
                    ]);
                }
            }

            DB::commit();

            return $task;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateTask(Task $task, array $data)
    {
        DB::beginTransaction();

        try {
            if (isset($data['name'])) $task->name = $data['name'];
            if (isset($data['description'])) $task->description = $data['description'];
            if (isset($data['status'])) $task->status = $data['status'];
            if (isset($data['priority'])) $task->priority = $data['priority'];
            if (isset($data['due_date'])) $task->due_date = $data['due_date'];
            if (isset($data['project_id'])) $task->project_id = $data['project_id'];

            if (isset($data['season_id'])) {
                $task->season_id = $data['season_id'];
            }

            $task->save();

            if (isset($data['user_ids'])) {
                TaskUser::where('task_id', $task->id)->delete();

                foreach ($data['user_ids'] as $userId) {
                    TaskUser::create([
                        'task_id' => $task->id,
                        'user_id' => $userId,
                        'season_id' => $data['season_id'] ?? $task->season_id,
                    ]);
                }
            }

            DB::commit();

            return $task;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function changeTaskStatus(Task $task, string $status)
    {
        $task->status = $status;
        $task->save();

        return $task;
    }
}
