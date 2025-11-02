<?php

namespace App\Services\TaskController;

use App\Models\Task;
use App\Models\TaskUser;
use App\Models\TemplateTaskUser;
use Carbon\Carbon;

class TaskDataTransformationService
{
    public function transformTaskToArray(Task $task): array
    {
        $taskData = $task->toArray();

        if (isset($taskData['users']) && is_array($taskData['users'])) {
            foreach ($taskData['users'] as $key => $user) {
                $taskData['users'][$key] = $this->transformUserPivotData($user, $task);
            }
        }

        if (isset($taskData['due_date']) && $taskData['due_date']) {
            $taskData['due_date'] = Carbon::parse($taskData['due_date'])->format('Y-m-d');
        }

        return $taskData;
    }

    public function transformTemplateTaskToArray(TemplateTaskUser $templateTaskUser): array
    {
        $templateTask = $templateTaskUser->templateTask;
        $template = $templateTask->template ?? null;

        return [
            'id' => $templateTaskUser->id,
            'name' => ($templateTask->name ?? 'مهمة قالب') . ' (قالب)',
            'description' => $templateTask->description ?? 'مهمة من قالب',
            'is_template' => true,
            'template_name' => $template->name ?? 'قالب غير محدد',

            'project' => $templateTaskUser->project ? [
                'id' => $templateTaskUser->project->id,
                'name' => $templateTaskUser->project->name
            ] : null,
            'project_id' => $templateTaskUser->project_id,

            'service' => $template && $template->service ? [
                'id' => $template->service->id,
                'name' => $template->service->name
            ] : null,
            'service_id' => $template->service_id ?? null,

            'estimated_hours' => $templateTask->estimated_hours ?? 0,
            'estimated_minutes' => $templateTask->estimated_minutes ?? 0,
            'actual_hours' => $templateTaskUser->actual_minutes ? intval($templateTaskUser->actual_minutes / 60) : 0,
            'actual_minutes' => $templateTaskUser->actual_minutes ? ($templateTaskUser->actual_minutes % 60) : 0,

            'status' => $templateTaskUser->status ?? 'new',
            'due_date' => null,
            'created_at' => $templateTaskUser->created_at,
            'updated_at' => $templateTaskUser->updated_at,

            'users' => $templateTaskUser->user ? [$this->transformTemplateTaskUser($templateTaskUser, $templateTask)] : [],

            'createdBy' => null,
            'created_by' => null,
        ];
    }

    public function transformTemplateTaskForEdit(TemplateTaskUser $templateTaskUser): array
    {
        $templateTask = $templateTaskUser->templateTask;
        $template = $templateTask->template ?? null;

        $baseData = $this->transformTemplateTaskToArray($templateTaskUser);

        $baseData['due_date'] = $templateTaskUser->due_date;
        $baseData['points'] = $templateTask->points ?? 10;
        $baseData['template_task_id'] = $templateTask->id;
        $baseData['template_id'] = $template->id ?? null;
        $baseData['season_id'] = $templateTaskUser->season_id;

        if ($templateTaskUser->due_date) {
            $baseData['users'][0]['pivot']['due_date'] = Carbon::parse($templateTaskUser->due_date)->format('Y-m-d');
        }

        return $baseData;
    }

    private function transformUserPivotData(array $user, Task $task): array
    {
        if (!isset($user['pivot'])) {
            $taskUser = TaskUser::where('task_id', $task->id)
                              ->where('user_id', $user['id'])
                              ->first();

            if ($taskUser) {
                $user['pivot'] = [
                    'id' => $taskUser->id,
                    'task_id' => $task->id,
                    'user_id' => $user['id'],
                    'role' => $taskUser->role ?? 'غير محدد',
                    'status' => $taskUser->status ?? 'new',
                    'estimated_hours' => $taskUser->estimated_hours ?? 0,
                    'estimated_minutes' => $taskUser->estimated_minutes ?? 0,
                    'actual_hours' => $taskUser->actual_hours ?? 0,
                    'actual_minutes' => $taskUser->actual_minutes ?? 0,
                    'due_date' => $taskUser->due_date ? $taskUser->due_date->format('Y-m-d') : null,
                    'completed_date' => $taskUser->completed_date ? $taskUser->completed_date->format('Y-m-d') : null
                ];
            } else {
                $user['pivot'] = $this->getDefaultPivotData($task->id, $user['id']);
            }
        } else {
            if (isset($user['pivot']['due_date']) && $user['pivot']['due_date']) {
                $dueDate = Carbon::parse($user['pivot']['due_date']);
                $user['pivot']['due_date'] = $dueDate->format('Y-m-d');
            }
        }

        return $user;
    }

    private function transformTemplateTaskUser(TemplateTaskUser $templateTaskUser, $templateTask): array
    {
        return [
            'id' => $templateTaskUser->user->id,
            'name' => $templateTaskUser->user->name,
            'email' => $templateTaskUser->user->email,
            'pivot' => [
                'id' => $templateTaskUser->id,
                'task_id' => $templateTaskUser->id,
                'user_id' => $templateTaskUser->user->id,
                'role' => 'منفذ قالب',
                'status' => $templateTaskUser->status ?? 'new',
                'estimated_hours' => $templateTask->estimated_hours ?? 0,
                'estimated_minutes' => $templateTask->estimated_minutes ?? 0,
                'actual_hours' => $templateTaskUser->actual_minutes ? intval($templateTaskUser->actual_minutes / 60) : 0,
                'actual_minutes' => $templateTaskUser->actual_minutes ? ($templateTaskUser->actual_minutes % 60) : 0,
                'due_date' => $templateTaskUser->due_date ? Carbon::parse($templateTaskUser->due_date)->format('Y-m-d') : null,
            ]
        ];
    }

    private function getDefaultPivotData(int $taskId, int $userId): array
    {
        return [
            'task_id' => $taskId,
            'user_id' => $userId,
            'role' => 'غير محدد',
            'status' => 'new',
            'estimated_hours' => 0,
            'estimated_minutes' => 0,
            'actual_hours' => 0,
            'actual_minutes' => 0,
            'due_date' => null,
            'completed_date' => null
        ];
    }
}
