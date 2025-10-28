<?php

namespace App\Services\TaskController;

use App\Models\Task;
use App\Models\User;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskActivityService
{
    /**
     * تسجيل نشاط دخول صفحة المهام
     */
    public function logTasksIndexView(Request $request): void
    {
        if (!Auth::check()) {
            return;
        }

        activity()
            ->causedBy(Auth::user())
            ->withProperties([
                'action_type' => 'view_index',
                'page' => 'tasks_list',
                'filters' => $request->all(),
                'viewed_at' => now()->toDateTimeString(),
                'user_agent' => $request->userAgent(),
                'ip_address' => $request->ip()
            ])
            ->log('دخل على صفحة المهام');
    }

    /**
     * تسجيل نشاط عرض المهمة
     */
    public function logTaskView(Task $task, Request $request): void
    {
        if (!Auth::check()) {
            return;
        }

        activity()
            ->performedOn($task)
            ->causedBy(Auth::user())
            ->withProperties([
                'task_name' => $task->name,
                'task_status' => $task->status,
                'project_name' => $task->project ? $task->project->name : null,
                'project_id' => $task->project_id,
                'action_type' => 'view',
                'viewed_at' => now()->toDateTimeString(),
                'user_agent' => $request->userAgent(),
                'ip_address' => $request->ip()
            ])
            ->log('شاهد المهمة');
    }

    /**
     * تسجيل نشاط عرض مهمة القالب
     */
    public function logTemplateTaskView($templateTaskUser, Request $request): void
    {
        if (!Auth::check()) {
            return;
        }

        activity()
            ->performedOn($templateTaskUser->templateTask)
            ->causedBy(Auth::user())
            ->withProperties([
                'template_task_name' => $templateTaskUser->templateTask->name ?? 'مهمة قالب',
                'template_task_status' => $templateTaskUser->status,
                'project_name' => $templateTaskUser->project ? $templateTaskUser->project->name : null,
                'project_id' => $templateTaskUser->project_id,
                'template_task_user_id' => $templateTaskUser->id,
                'action_type' => 'view',
                'viewed_at' => now()->toDateTimeString(),
                'user_agent' => $request->userAgent(),
                'ip_address' => $request->ip()
            ])
            ->log('شاهد مهمة القالب');
    }

    /**
     * تسجيل نشاط دخول مهام المشروع
     */
    public function logProjectTasksView(Project $project, int $projectId, Request $request): void
    {
        if (!Auth::check()) {
            return;
        }

        activity()
            ->performedOn($project)
            ->causedBy(Auth::user())
            ->withProperties([
                'action_type' => 'view_project_tasks',
                'project_name' => $project->name,
                'project_id' => $projectId,
                'viewed_at' => now()->toDateTimeString(),
                'user_agent' => $request->userAgent(),
                'ip_address' => $request->ip()
            ])
            ->log('دخل على مهام المشروع');
    }

    /**
     * تسجيل نشاط دخول صفحة مهامي
     */
    public function logMyTasksView(Request $request): void
    {
        if (!Auth::check()) {
            return;
        }

        activity()
            ->causedBy(Auth::user())
            ->withProperties([
                'action_type' => 'view_my_tasks',
                'page' => 'my_tasks',
                'filters' => $request->all(),
                'viewed_at' => now()->toDateTimeString(),
                'user_agent' => $request->userAgent(),
                'ip_address' => $request->ip()
            ])
            ->log('دخل على صفحة مهامي');
    }
}
