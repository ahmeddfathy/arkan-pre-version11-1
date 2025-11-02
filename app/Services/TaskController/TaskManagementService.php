<?php

namespace App\Services\TaskController;

use App\Models\Task;
use App\Models\Project;
use App\Models\TaskUser;
use App\Models\TemplateTaskUser;
use App\Models\TemplateTask;
use App\Traits\HasNTPTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TaskManagementService
{
    use HasNTPTime;
    public function createTask(array $taskData, ?int $graphicTaskTypeId = null): Task
    {
        $nextOrder = Task::where('project_id', $taskData['project_id'])->max('order') + 1;
        $taskData['order'] = $nextOrder;
        $taskData['status'] = 'new';

        // إذا كانت مهمة جرافيكية، استخدم الوقت من GraphicTaskType
        if ($graphicTaskTypeId) {
            $graphicTaskType = \App\Models\GraphicTaskType::find($graphicTaskTypeId);
            if ($graphicTaskType && !($taskData['is_flexible_time'] ?? false)) {
                // استخدم max_minutes كوقت مقدر للمهمة الجرافيكية
                $totalMinutes = $graphicTaskType->max_minutes;
                $taskData['estimated_hours'] = intval($totalMinutes / 60);
                $taskData['estimated_minutes'] = $totalMinutes % 60;

                Log::info('Graphic task time auto-set', [
                    'graphic_task_type_id' => $graphicTaskTypeId,
                    'max_minutes' => $totalMinutes,
                    'estimated_hours' => $taskData['estimated_hours'],
                    'estimated_minutes' => $taskData['estimated_minutes']
                ]);
            }
        }

        $task = Task::create($taskData);

        if ($graphicTaskTypeId) {
            $task->graphicTaskTypes()->attach($graphicTaskTypeId);
        }

        Log::info('Task created successfully', [
            'task_id' => $task->id,
            'project_id' => $task->project_id,
            'service_id' => $task->service_id
        ]);

        return $task;
    }

    public function updateTask(Task $task, array $updateData, ?int $graphicTaskTypeId = null): Task
    {
        $oldStatus = $task->status;
        $oldProjectId = $task->project_id;

        if ($oldProjectId != $updateData['project_id']) {
            $nextOrder = Task::where('project_id', $updateData['project_id'])->max('order') + 1;
            $updateData['order'] = $nextOrder;
        }

        // إذا كانت مهمة جرافيكية، استخدم الوقت من GraphicTaskType
        if ($graphicTaskTypeId) {
            $graphicTaskType = \App\Models\GraphicTaskType::find($graphicTaskTypeId);
            if ($graphicTaskType && !($updateData['is_flexible_time'] ?? false)) {
                // استخدم max_minutes كوقت مقدر للمهمة الجرافيكية
                $totalMinutes = $graphicTaskType->max_minutes;
                $updateData['estimated_hours'] = intval($totalMinutes / 60);
                $updateData['estimated_minutes'] = $totalMinutes % 60;

                Log::info('Graphic task time auto-updated', [
                    'task_id' => $task->id,
                    'graphic_task_type_id' => $graphicTaskTypeId,
                    'max_minutes' => $totalMinutes,
                    'estimated_hours' => $updateData['estimated_hours'],
                    'estimated_minutes' => $updateData['estimated_minutes']
                ]);
            }
        }

        $task->update($updateData);

        if ($oldStatus != 'completed' && $updateData['status'] == 'completed') {
            $task->update(['completed_date' => $this->getCurrentCairoTime()]);
        }

        $task->graphicTaskTypes()->detach();
        if ($graphicTaskTypeId) {
            $task->graphicTaskTypes()->attach($graphicTaskTypeId);
        }

        Log::info('Task updated successfully', [
            'task_id' => $task->id,
            'old_status' => $oldStatus,
            'new_status' => $updateData['status']
        ]);

        return $task;
    }

    public function deleteTask(Task $task): bool
    {
        try {
            $taskId = $task->id;
            $task->delete();

            Log::info('Task deleted successfully', ['task_id' => $taskId]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete task', [
                'task_id' => $task->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getProjectTasks(int $projectId): array
    {
        $project = Project::findOrFail($projectId);
        $tasks = Task::with(['service', 'users'])
            ->withCount(['revisions', 'revisions as pending_revisions_count' => function ($query) {
                $query->where('status', 'pending');
            }])
            ->where('project_id', $projectId)
            ->orderBy('created_at', 'desc')
            ->get();

        return [
            'project' => $project,
            'tasks' => $tasks
        ];
    }

    public function getUserTasks(int $userId, array $filters = [], $paginate = true)
    {
        try {
            $query = Task::with(['project', 'service', 'users' => function ($q) use ($userId) {
                $q->where('users.id', $userId);
            }])
                ->withCount([
                    'revisions',
                    'revisions as pending_revisions_count' => function ($query) {
                        $query->where('status', 'pending');
                    },
                    'revisions as approved_revisions_count' => function ($query) {
                        $query->where('status', 'approved');
                    },
                    'revisions as rejected_revisions_count' => function ($query) {
                        $query->where('status', 'rejected');
                    }
                ])
                ->whereHas('users', function ($q) use ($userId) {
                    $q->where('users.id', $userId);
                });

            // ✅ لا نطبق applyHierarchicalTaskFiltering هنا لأن:
            // 1. getUserTasks يستخدم لصفحة "مهامي" التي يجب أن تعرض مهام المستخدم فقط
            // 2. نحن بالفعل نفلتر حسب userId في whereHas أعلاه
            // 3. applyHierarchicalTaskFiltering قد يسبب مشاكل في الفلترة للمستخدمين العاديين

            // فقط نطبق الفلترة الهرمية إذا كان المستخدم الحالي مختلف عن المستخدم المطلوب
            // (في حالة HR أو Admin يشاهد مهام مستخدم آخر)
            $taskFilterService = app(\App\Services\TaskController\TaskFilterService::class);
            $currentUser = \Illuminate\Support\Facades\Auth::user();

            if ($currentUser && $currentUser->id != $userId) {
                /** @var \App\Models\User $currentUser */
                $hasPermission = false;
                try {
                    $hasPermission = $currentUser->hasRole(['company_manager', 'hr', 'project_manager']);
                } catch (\Exception $e) {
                    if (method_exists($currentUser, 'roles') && $currentUser->roles) {
                        $roleNames = $currentUser->roles->pluck('name')->toArray();
                        $hasPermission = !empty(array_intersect($roleNames, ['company_manager', 'hr', 'project_manager']));
                    }
                }

                if ($hasPermission) {
                    $query = $taskFilterService->applyHierarchicalTaskFiltering($query, $currentUser);
                }
            }

            if (isset($filters['project_id']) && $filters['project_id']) {
                $query->where('project_id', $filters['project_id']);
            }

            if (isset($filters['status']) && $filters['status']) {
                $query->whereHas('users', function ($q) use ($userId, $filters) {
                    $q->where('users.id', $userId)
                        ->where('task_users.status', $filters['status']);
                });
            }

            $totalCount = $query->count();
            if ($totalCount === 0) {
                return new \Illuminate\Pagination\LengthAwarePaginator(
                    collect([]),
                    0,
                    10,
                    1,
                    ['path' => request()->url(), 'query' => request()->query()]
                );
            }

            if ($paginate) {
                $result = $query->orderBy('created_at', 'desc')->paginate(10);
            } else {
                $result = $query->orderBy('created_at', 'desc')->get();
            }

            if ($result && $result instanceof \Illuminate\Database\Eloquent\Collection) {
                foreach ($result as $task) {
                    if (!$task->users->isEmpty()) {
                        $userPivot = $task->users->first()->pivot;
                        $task->pivot = $userPivot;

                        $taskUser = TaskUser::with(['transferredToUser', 'originalTaskUser.user', 'administrativeApprover', 'technicalApprover'])
                            ->where('task_id', $task->id)
                            ->where('user_id', $userId)
                            ->first();

                        if ($taskUser) {
                            if (!isset($task->pivot->id) || !$task->pivot->id) {
                                $task->pivot->id = $taskUser->id;
                            }

                            $task->pivot->is_additional_task = $taskUser->is_additional_task ?? false;
                            $task->pivot->task_source = $taskUser->task_source ?? 'assigned';
                            $task->pivot->is_transferred = $taskUser->is_transferred ?? false;
                            $task->pivot->original_task_user_id = $taskUser->original_task_user_id ?? null;

                            $task->pivot->administrative_approval = $taskUser->administrative_approval ?? false;
                            $task->pivot->technical_approval = $taskUser->technical_approval ?? false;
                            $task->pivot->administrative_approval_at = $taskUser->administrative_approval_at ?? null;
                            $task->pivot->technical_approval_at = $taskUser->technical_approval_at ?? null;
                            $task->pivot->administrativeApprover = $taskUser->administrativeApprover ?? null;
                            $task->pivot->technicalApprover = $taskUser->technicalApprover ?? null;

                            $task->transferredToUser = $taskUser->transferredToUser;
                            $task->transferred_at = $taskUser->transferred_at;

                            if ($taskUser->originalTaskUser) {
                                $task->original_user = $taskUser->originalTaskUser->user;
                            }
                        }

                        if (isset($task->pivot->id) && $task->pivot->id) {
                            $task->notes_count = \App\Models\TaskNote::where('task_type', 'regular')
                                ->where('task_user_id', $task->pivot->id)
                                ->where('created_by', $userId)
                                ->count();
                        } else {
                            $task->notes_count = 0;
                        }
                    }
                }
            } elseif ($result && $result instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator) {
                foreach ($result->items() as $task) {
                    if (!$task->users->isEmpty()) {
                        $userPivot = $task->users->first()->pivot;
                        $task->pivot = $userPivot;

                        $taskUser = TaskUser::with(['transferredToUser', 'originalTaskUser.user', 'administrativeApprover', 'technicalApprover'])
                            ->where('task_id', $task->id)
                            ->where('user_id', $userId)
                            ->first();

                        if ($taskUser) {
                            if (!isset($task->pivot->id) || !$task->pivot->id) {
                                $task->pivot->id = $taskUser->id;
                            }

                            $task->pivot->is_additional_task = $taskUser->is_additional_task ?? false;
                            $task->pivot->task_source = $taskUser->task_source ?? 'assigned';
                            $task->pivot->is_transferred = $taskUser->is_transferred ?? false;
                            $task->pivot->original_task_user_id = $taskUser->original_task_user_id ?? null;

                            $task->pivot->administrative_approval = $taskUser->administrative_approval ?? false;
                            $task->pivot->technical_approval = $taskUser->technical_approval ?? false;
                            $task->pivot->administrative_approval_at = $taskUser->administrative_approval_at ?? null;
                            $task->pivot->technical_approval_at = $taskUser->technical_approval_at ?? null;
                            $task->pivot->administrativeApprover = $taskUser->administrativeApprover ?? null;
                            $task->pivot->technicalApprover = $taskUser->technicalApprover ?? null;

                            $task->transferredToUser = $taskUser->transferredToUser;
                            $task->transferred_at = $taskUser->transferred_at;

                            if ($taskUser->originalTaskUser) {
                                $task->original_user = $taskUser->originalTaskUser->user;
                            }
                        }

                        if (isset($task->pivot->id) && $task->pivot->id) {
                            $task->notes_count = \App\Models\TaskNote::where('task_type', 'regular')
                                ->where('task_user_id', $task->pivot->id)
                                ->where('created_by', $userId)
                                ->count();
                        } else {
                            $task->notes_count = 0;
                        }
                    }
                }
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Error getting user tasks', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return new \Illuminate\Pagination\LengthAwarePaginator(
                collect([]),
                0,
                10,
                1,
                ['path' => request()->url(), 'query' => request()->query()]
            );
        }
    }

    public function enrichTaskWithUserData(Task $task, int $userId): Task
    {
        $taskUser = TaskUser::with(['transferredToUser', 'originalTaskUser.user', 'administrativeApprover', 'technicalApprover'])
            ->where('task_id', $task->id)
            ->where('user_id', $userId)
            ->first();

        if ($taskUser) {
            $task->user_status = $taskUser->status;
            $task->user_role = $taskUser->role;

            $task->is_transferred = $taskUser->is_transferred ?? false;
            $task->is_additional_task = $taskUser->is_additional_task ?? false;
            $task->task_source = $taskUser->task_source ?? null;

            $task->administrative_approval = $taskUser->administrative_approval ?? false;
            $task->technical_approval = $taskUser->technical_approval ?? false;
            $task->administrative_approval_at = $taskUser->administrative_approval_at ?? null;
            $task->technical_approval_at = $taskUser->technical_approval_at ?? null;
            $task->administrativeApprover = $taskUser->administrativeApprover ?? null;
            $task->technicalApprover = $taskUser->technicalApprover ?? null;

            $task->transferredToUser = $taskUser->transferredToUser;
            $task->transferred_at = $taskUser->transferred_at;

            if ($taskUser->originalTaskUser) {
                $task->original_user = $taskUser->originalTaskUser->user;
            }
        }

        return $task;
    }

    public function getUserTemplateTasks(int $userId, array $filters = [])
    {
        try {
            $query = TemplateTaskUser::with([
                'templateTask.template',
                'project',
                'season',
                'assignedBy',
                'transferredToUser',
                'originalTemplateTaskUser.user',
                'administrativeApprover',
                'technicalApprover'
            ])
                ->withCount(['notes', 'revisions', 'revisions as pending_revisions_count' => function ($query) {
                    $query->where('status', 'pending');
                }])
                ->where('user_id', $userId)
                ->whereHas('templateTask');

            if (isset($filters['project_id']) && $filters['project_id']) {
                $query->where('project_id', $filters['project_id']);
            }

            if (isset($filters['status']) && $filters['status']) {
                $query->where('status', $filters['status']);
            }

            $templateTaskUsers = $query->orderBy('created_at', 'desc')->get();

            if ($templateTaskUsers->isEmpty()) {
                return collect([]);
            }

            return $templateTaskUsers->map(function ($templateTaskUser) {
                if (!$templateTaskUser || !$templateTaskUser->templateTask) {
                    return null;
                }

                $templateTask = $templateTaskUser->templateTask;
                $template = $templateTask->template ?? null;

                $task = new \stdClass();
                $task->id = $templateTaskUser->id;
                $task->name = ($templateTask->name ?? 'مهمة قالب') . ' (قالب)';
                $task->description = $templateTask->description ?? 'مهمة من قالب';
                $task->is_template = true;
                $task->template_name = $template->name ?? 'قالب غير محدد';

                $task->project = $templateTaskUser->project;
                $task->project_id = $templateTaskUser->project_id;

                $task->service = $template->service ?? null;
                $task->service_id = $template->service_id ?? null;

                $task->estimated_hours = $templateTask->estimated_hours ?? 0;
                $task->estimated_minutes = $templateTask->estimated_minutes ?? 0;

                $actualMinutes = (int)($templateTaskUser->actual_minutes ?? 0);
                $task->actual_hours = $actualMinutes > 0 ? intval($actualMinutes / 60) : 0;
                $task->actual_minutes = $actualMinutes > 0 ? ($actualMinutes % 60) : 0;

                $task->status = $templateTaskUser->status ?? 'new';
                $task->user_status = $templateTaskUser->status ?? 'new';
                $task->due_date = $templateTaskUser->due_date ?? null;
                $task->deadline = $templateTaskUser->deadline ?? null;
                $task->created_at = $templateTaskUser->created_at;
                $task->updated_at = $templateTaskUser->updated_at;

                $task->started_at = $templateTaskUser->started_at;
                $task->paused_at = $templateTaskUser->paused_at;
                $task->completed_at = $templateTaskUser->completed_at;
                $task->season_id = $templateTaskUser->season_id;

                $task->pivot = new \stdClass();
                $task->pivot->id = $templateTaskUser->id;
                $task->pivot->status = $templateTaskUser->status ?? 'new';
                $task->pivot->role = 'منفذ';
                $task->pivot->estimated_hours = $task->estimated_hours;
                $task->pivot->estimated_minutes = $task->estimated_minutes;
                $task->pivot->actual_hours = $task->actual_hours;
                $task->pivot->actual_minutes = $task->actual_minutes;
                $task->pivot->due_date = $templateTaskUser->due_date ?? $templateTaskUser->deadline ?? null;
                $task->pivot->is_additional_task = $templateTaskUser->is_additional_task ?? false;
                $task->pivot->task_source = $templateTaskUser->task_source ?? 'assigned';
                $task->pivot->is_transferred = $templateTaskUser->is_transferred ?? false;

                $task->is_transferred = $templateTaskUser->is_transferred ?? false;
                $task->is_additional_task = $templateTaskUser->is_additional_task ?? false;
                $task->task_source = $templateTaskUser->task_source ?? null;

                $task->transferredToUser = $templateTaskUser->transferredToUser;
                $task->transferred_at = $templateTaskUser->transferred_at;

                if ($templateTaskUser->originalTemplateTaskUser) {
                    $task->original_user = $templateTaskUser->originalTemplateTaskUser->user;
                }

                $task->notes_count = $templateTaskUser->notes_count ?? 0;

                $task->revisions_count = $templateTaskUser->revisions_count ?? 0;
                $task->pending_revisions_count = $templateTaskUser->pending_revisions_count ?? 0;
                $task->approved_revisions_count = $templateTaskUser->approved_revisions_count ?? 0;
                $task->rejected_revisions_count = $templateTaskUser->rejected_revisions_count ?? 0;

                $task->revisions_status = $this->calculateRevisionsStatus(
                    $task->revisions_count,
                    $task->pending_revisions_count,
                    $task->approved_revisions_count,
                    $task->rejected_revisions_count
                );

                $task->is_transferred = $templateTaskUser->is_transferred ?? false;
                $task->is_additional_task = $templateTaskUser->is_additional_task ?? false;
                $task->task_source = $templateTaskUser->task_source ?? 'assigned';
                $task->transfer_type = $templateTaskUser->transfer_type ?? null;
                $task->transfer_reason = $templateTaskUser->transfer_reason ?? null;
                $task->transferred_at = $templateTaskUser->transferred_at ?? null;

                $task->created_by = $templateTaskUser->assigned_by ?? null;
                $task->createdBy = $templateTaskUser->assignedBy ?? null;

                $task->administrative_approval = $templateTaskUser->administrative_approval ?? false;
                $task->technical_approval = $templateTaskUser->technical_approval ?? false;
                $task->administrative_approval_at = $templateTaskUser->administrative_approval_at ?? null;
                $task->technical_approval_at = $templateTaskUser->technical_approval_at ?? null;
                $task->administrativeApprover = $templateTaskUser->administrativeApprover ?? null;
                $task->technicalApprover = $templateTaskUser->technicalApprover ?? null;

                return $task;
            })->filter();
        } catch (\Exception $e) {
            Log::error('Error getting user template tasks', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return collect([]);
        }
    }

    public function getAllTemplateTasks(array $filters = [])
    {
        try {
            $query = TemplateTaskUser::with([
                'templateTask.template',
                'user',
                'project',
                'assignedBy',
                'transferredToUser',
                'originalTemplateTaskUser.user',
                'administrativeApprover',
                'technicalApprover'
            ])
                ->withCount(['notes', 'revisions', 'revisions as pending_revisions_count' => function ($query) {
                    $query->where('status', 'pending');
                }]);

            $taskFilterService = app(\App\Services\TaskController\TaskFilterService::class);
            $query = $taskFilterService->applyHierarchicalTemplateTaskFiltering($query);

            if (isset($filters['project_id']) && $filters['project_id']) {
                $query->where('project_id', $filters['project_id']);
            }

            if (isset($filters['status']) && $filters['status']) {
                $query->where('status', $filters['status']);
            }

            if (isset($filters['user_id']) && $filters['user_id']) {
                $query->where('user_id', $filters['user_id']);
            }

            $templateTaskUsers = $query->orderBy('created_at', 'desc')->get();

            return $templateTaskUsers->map(function ($templateTaskUser) {
                if (!$templateTaskUser || !$templateTaskUser->templateTask) {
                    return null;
                }

                $templateTask = $templateTaskUser->templateTask;
                $template = $templateTask->template ?? null;

                $task = new \stdClass();
                $task->id = $templateTaskUser->id;
                $task->name = ($templateTask->name ?? 'مهمة قالب');
                $task->description = $templateTask->description ?? 'مهمة من قالب';
                $task->is_template = true;
                $task->template_name = $template->name ?? 'قالب غير محدد';

                $task->project = $templateTaskUser->project;
                $task->project_id = $templateTaskUser->project_id;

                $task->service = $template->service ?? null;
                $task->service_id = $template->service_id ?? null;

                $actualMinutes = (int)($templateTaskUser->actual_minutes ?? 0);

                if ($templateTaskUser->user) {
                    $user = $templateTaskUser->user;
                    $user->pivot = (object) [
                        'role' => 'منفذ قالب',
                        'status' => $templateTaskUser->status ?? 'new',
                        'estimated_hours' => $templateTask->estimated_hours ?? 0,
                        'estimated_minutes' => $templateTask->estimated_minutes ?? 0,
                        'actual_hours' => $actualMinutes > 0 ? intval($actualMinutes / 60) : 0,
                        'actual_minutes' => $actualMinutes > 0 ? ($actualMinutes % 60) : 0,
                        'due_date' => $templateTaskUser->due_date ?? $templateTaskUser->deadline ?? null,
                    ];
                    $task->users = collect([$user]);
                } else {
                    $task->users = collect([]);
                }

                $task->createdBy = null;
                $task->created_by = null;

                $task->estimated_hours = $templateTask->estimated_hours ?? 0;
                $task->estimated_minutes = $templateTask->estimated_minutes ?? 0;

                $task->actual_hours = $actualMinutes > 0 ? intval($actualMinutes / 60) : 0;
                $task->actual_minutes = $actualMinutes > 0 ? ($actualMinutes % 60) : 0;
                $task->actual_minutes_raw = $actualMinutes;

                $task->status = $templateTaskUser->status ?? 'new';
                $task->due_date = $templateTaskUser->due_date ?? null;
                $task->deadline = $templateTaskUser->deadline ?? null;
                $task->created_at = $templateTaskUser->created_at;
                $task->updated_at = $templateTaskUser->updated_at;

                $task->started_at = $templateTaskUser->started_at;
                $task->paused_at = $templateTaskUser->paused_at;
                $task->completed_at = $templateTaskUser->completed_at;
                $task->season_id = $templateTaskUser->season_id;

                $task->notes_count = $templateTaskUser->notes_count ?? 0;

                $task->revisions_count = $templateTaskUser->revisions_count ?? 0;
                $task->pending_revisions_count = $templateTaskUser->pending_revisions_count ?? 0;
                $task->approved_revisions_count = $templateTaskUser->approved_revisions_count ?? 0;
                $task->rejected_revisions_count = $templateTaskUser->rejected_revisions_count ?? 0;

                $task->revisions_status = $this->calculateRevisionsStatus(
                    $task->revisions_count,
                    $task->pending_revisions_count,
                    $task->approved_revisions_count,
                    $task->rejected_revisions_count
                );

                $task->is_transferred = $templateTaskUser->is_transferred ?? false;
                $task->is_additional_task = $templateTaskUser->is_additional_task ?? false;
                $task->task_source = $templateTaskUser->task_source ?? 'assigned';
                $task->transfer_type = $templateTaskUser->transfer_type ?? null;
                $task->transfer_reason = $templateTaskUser->transfer_reason ?? null;
                $task->transferred_at = $templateTaskUser->transferred_at ?? null;
                $task->transferred_to_user_id = $templateTaskUser->transferred_to_user_id ?? null;

                $task->administrative_approval = $templateTaskUser->administrative_approval ?? false;
                $task->technical_approval = $templateTaskUser->technical_approval ?? false;
                $task->administrative_approval_at = $templateTaskUser->administrative_approval_at ?? null;
                $task->technical_approval_at = $templateTaskUser->technical_approval_at ?? null;
                $task->administrativeApprover = $templateTaskUser->administrativeApprover ?? null;
                $task->technicalApprover = $templateTaskUser->technicalApprover ?? null;

                $task->transferred_to_user = $templateTaskUser->transferredToUser ?? null;

                if ($templateTaskUser->is_additional_task && $templateTaskUser->originalTemplateTaskUser) {
                    $task->original_user = $templateTaskUser->originalTemplateTaskUser->user ?? null;
                }

                $task->created_by = $templateTaskUser->assigned_by ?? null;
                $task->createdBy = $templateTaskUser->assignedBy ?? null;

                return $task;
            })->filter();

        } catch (\Exception $e) {
            Log::error('Error getting all template tasks', [
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);

            return collect([]);
        }
    }
    
    private function calculateRevisionsStatus($total, $pending, $approved, $rejected)
    {
        if ($total == 0) return 'none';

        if ($pending > 0) {
            if ($approved > 0 || $rejected > 0) {
                return 'mixed';
            }
            return 'pending';
        }

        if ($approved > 0 && $rejected == 0) {
            return 'approved';
        }

        if ($rejected > 0 && $approved == 0) {
                return 'rejected';
        }

        return 'mixed';
    }
}
