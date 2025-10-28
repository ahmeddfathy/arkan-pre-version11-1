<?php

namespace App\Services\ProjectManagement;

use App\Models\Project;
use Illuminate\Support\Facades\DB;

class ProjectTaskService
{

    public function getTemplateTasks(Project $project)
    {
        $currentUser = auth()->user();

        // جلب الخدمات التي يشارك فيها المستخدم الحالي في هذا المشروع
        $userServiceIds = DB::table('project_service_user')
            ->where('project_id', $project->id)
            ->where('user_id', $currentUser->id)
            ->pluck('service_id')
            ->toArray();

        $templateTasks = $project->templateTaskUsers()
            ->with('templateTask.template')
            ->withCount([
                'revisions',
                'revisions as pending_revisions_count' => function($query) {
                    $query->where('status', 'pending');
                },
                'revisions as approved_revisions_count' => function($query) {
                    $query->where('status', 'approved');
                },
                'revisions as rejected_revisions_count' => function($query) {
                    $query->where('status', 'rejected');
                }
            ])
            ->where('user_id', $currentUser->id) // فقط مهام المستخدم الحالي
            ->whereHas('templateTask.template', function($query) use ($userServiceIds) {
                $query->whereIn('service_id', $userServiceIds); // فقط الخدمات التي يشارك فيها
            })
            ->get()
            ->map(function ($templateTaskUser) {
                return [
                    'id' => $templateTaskUser->id,
                    'name' => $templateTaskUser->templateTask->name ?? 'مهمة بدون اسم',
                    'status' => $templateTaskUser->status ?? 'غير محدد',
                    'service_name' => $templateTaskUser->templateTask->template->service->name ?? 'خدمة غير معروفة',
                    'deadline' => $templateTaskUser->deadline ? $templateTaskUser->deadline->format('Y-m-d H:i:s') : null,
                    'deadline_formatted' => $templateTaskUser->deadline ? $templateTaskUser->deadline->format('d/m/Y H:i') : null,
                    'is_overdue' => $templateTaskUser->deadline ? $templateTaskUser->deadline->isPast() && $templateTaskUser->status !== 'completed' : false,
                    'deadline_status' => $templateTaskUser->deadline ? $this->getDeadlineStatus($templateTaskUser) : null,
                'revisions_count' => $templateTaskUser->revisions_count ?? 0,
                'pending_revisions_count' => $templateTaskUser->pending_revisions_count ?? 0,
                'approved_revisions_count' => $templateTaskUser->approved_revisions_count ?? 0,
                'rejected_revisions_count' => $templateTaskUser->rejected_revisions_count ?? 0,
                'revisions_status' => $this->calculateRevisionsStatus(
                    $templateTaskUser->revisions_count ?? 0,
                    $templateTaskUser->pending_revisions_count ?? 0,
                    $templateTaskUser->approved_revisions_count ?? 0,
                    $templateTaskUser->rejected_revisions_count ?? 0
                )
                ];
            });

        return ['tasks' => $templateTasks];
    }

    /**
     * Get regular tasks for a project (filtered by current user and their services)
     */
    public function getRegularTasks(Project $project)
    {
        $currentUser = auth()->user();

        // جلب الخدمات التي يشارك فيها المستخدم الحالي في هذا المشروع
        $userServiceIds = DB::table('project_service_user')
            ->where('project_id', $project->id)
            ->where('user_id', $currentUser->id)
            ->pluck('service_id')
            ->toArray();

        $tasks = $project->taskUsers()
            ->with('task.project')
            ->withCount([
                'revisions',
                'revisions as pending_revisions_count' => function($query) {
                    $query->where('status', 'pending');
                },
                'revisions as approved_revisions_count' => function($query) {
                    $query->where('status', 'approved');
                },
                'revisions as rejected_revisions_count' => function($query) {
                    $query->where('status', 'rejected');
                }
            ])
            ->where('user_id', $currentUser->id) // فقط مهام المستخدم الحالي
            ->whereHas('task', function($query) use ($userServiceIds) {
                $query->whereIn('service_id', $userServiceIds); // فقط الخدمات التي يشارك فيها
            })
            ->get()
            ->map(function ($taskUser) {
                return [
                    'id' => $taskUser->id,
                    'name' => $taskUser->task->name ?? 'مهمة بدون اسم',
                    'status' => $taskUser->status ?? 'غير محدد',
                    'service_name' => $taskUser->task->service->name ?? 'خدمة غير معروفة',
                    'revisions_count' => $taskUser->revisions_count ?? 0,
                    'pending_revisions_count' => $taskUser->pending_revisions_count ?? 0,
                    'approved_revisions_count' => $taskUser->approved_revisions_count ?? 0,
                    'rejected_revisions_count' => $taskUser->rejected_revisions_count ?? 0,
                    'revisions_status' => $this->calculateRevisionsStatus(
                        $taskUser->revisions_count ?? 0,
                        $taskUser->pending_revisions_count ?? 0,
                        $taskUser->approved_revisions_count ?? 0,
                        $taskUser->rejected_revisions_count ?? 0
                    )
                ];
            });

        return ['tasks' => $tasks];
    }

    /**
     * Get all project tasks (template + regular)
     */
    public function getAllProjectTasks(Project $project)
    {
        return [
            'template_tasks' => $this->getTemplateTasks($project)['tasks'],
            'regular_tasks' => $this->getRegularTasks($project)['tasks']
        ];
    }

    /**
     * Get project tasks count by status
     */
    public function getTasksCountByStatus(Project $project)
    {
        // Template tasks count
        $templateTasksCount = $project->templateTaskUsers()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Regular tasks count
        $regularTasksCount = $project->taskUsers()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'template_tasks' => $templateTasksCount,
            'regular_tasks' => $regularTasksCount,
            'total_template' => array_sum($templateTasksCount),
            'total_regular' => array_sum($regularTasksCount),
            'grand_total' => array_sum($templateTasksCount) + array_sum($regularTasksCount)
        ];
    }

    /**
     * Get deadline status for a template task user
     */
    private function getDeadlineStatus($templateTaskUser)
    {
        if (!$templateTaskUser->deadline) {
            return 'no_deadline';
        }

        if ($templateTaskUser->status === 'completed') {
            return 'completed';
        }

        if ($templateTaskUser->deadline->isPast()) {
            return 'overdue';
        }

        if ($templateTaskUser->deadline->diffInHours(now()) <= 24) {
            return 'due_soon';
        }

        return 'on_time';
    }

    /**
     * حساب حالة التعديلات بناءً على الأعداد
     */
    private function calculateRevisionsStatus($total, $pending, $approved, $rejected)
    {
        if ($total == 0) return 'none';

        if ($pending > 0) {
            if ($approved > 0 || $rejected > 0) {
                return 'mixed'; // خليط من الحالات
            }
            return 'pending'; // كلها معلقة
        }

        if ($approved > 0 && $rejected == 0) {
            return 'approved'; // كلها مقبولة
        }

        if ($rejected > 0 && $approved == 0) {
            return 'rejected'; // كلها مرفوضة
        }

        return 'mixed'; // خليط من مقبول ومرفوض
    }
}
