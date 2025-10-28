<?php

namespace App\Services\ProjectManagement;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskUser;
use App\Models\TemplateTaskUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProjectServiceStatusService
{
    public function updateServiceStatus(Project $project, int $serviceId): void
    {
        try {
            $taskStats = $this->getServiceTaskStats($project->id, $serviceId);

            $newStatus = $this->determineServiceStatus($taskStats);

            DB::table('project_service')
                ->where('project_id', $project->id)
                ->where('service_id', $serviceId)
                ->update([
                    'service_status' => $newStatus,
                    'updated_at' => now()
                ]);

            Log::info("Service status updated", [
                'project_id' => $project->id,
                'service_id' => $serviceId,
                'new_status' => $newStatus,
                'task_stats' => $taskStats
            ]);

            // ✅ تحديث حالة المشروع تلقائياً بناءً على حالة الخدمات
            $this->updateProjectStatus($project);

        } catch (\Exception $e) {
            Log::error("Error updating service status", [
                'project_id' => $project->id,
                'service_id' => $serviceId,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function updateAllServiceStatuses(Project $project): void
    {
        $services = $project->services()->get();

        foreach ($services as $service) {
            $this->updateServiceStatus($project, $service->id);
        }
    }

    private function getServiceTaskStats(int $projectId, int $serviceId): array
    {
        $regularTaskStats = DB::table('task_users')
            ->join('tasks', 'task_users.task_id', '=', 'tasks.id')
            ->where('tasks.project_id', $projectId)
            ->where('tasks.service_id', $serviceId)
            ->select(
                DB::raw('COUNT(*) as total_tasks'),
                DB::raw('SUM(CASE WHEN task_users.status = "completed" THEN 1 ELSE 0 END) as completed_tasks'),
                DB::raw('SUM(CASE WHEN task_users.status = "in_progress" THEN 1 ELSE 0 END) as in_progress_tasks'),
                DB::raw('SUM(CASE WHEN task_users.status = "paused" THEN 1 ELSE 0 END) as paused_tasks')
            )
            ->first();

        $templateTaskStats = DB::table('template_task_user')
            ->join('template_tasks', 'template_task_user.template_task_id', '=', 'template_tasks.id')
            ->join('task_templates', 'template_tasks.task_template_id', '=', 'task_templates.id')
            ->where('template_task_user.project_id', $projectId)
            ->where('task_templates.service_id', $serviceId)
            ->select(
                DB::raw('COUNT(*) as total_tasks'),
                DB::raw('SUM(CASE WHEN template_task_user.status = "completed" THEN 1 ELSE 0 END) as completed_tasks'),
                DB::raw('SUM(CASE WHEN template_task_user.status = "in_progress" THEN 1 ELSE 0 END) as in_progress_tasks'),
                DB::raw('SUM(CASE WHEN template_task_user.status = "paused" THEN 1 ELSE 0 END) as paused_tasks')
            )
            ->first();

        return [
            'total_tasks' => ($regularTaskStats->total_tasks ?? 0) + ($templateTaskStats->total_tasks ?? 0),
            'completed_tasks' => ($regularTaskStats->completed_tasks ?? 0) + ($templateTaskStats->completed_tasks ?? 0),
            'in_progress_tasks' => ($regularTaskStats->in_progress_tasks ?? 0) + ($templateTaskStats->in_progress_tasks ?? 0),
            'paused_tasks' => ($regularTaskStats->paused_tasks ?? 0) + ($templateTaskStats->paused_tasks ?? 0),
        ];
    }

    private function determineServiceStatus(array $taskStats): string
    {
        $totalTasks = $taskStats['total_tasks'];
        $completedTasks = $taskStats['completed_tasks'];
        $inProgressTasks = $taskStats['in_progress_tasks'];
        $pausedTasks = $taskStats['paused_tasks'];

        if ($totalTasks === 0) {
            return 'لم تبدأ';
        }

        // إذا كانت كل المهام مكتملة
        if ($completedTasks === $totalTasks) {
            return 'مكتملة';
        }

        // إذا كان هناك مهام في التقدم
        if ($inProgressTasks > 0) {
            return 'قيد التنفيذ';
        }

        // إذا كان هناك مهام متوقفة (سواء كان معها مهام مكتملة أو لا)
        if ($pausedTasks > 0) {
            return 'معلقة';
        }

        // إذا كان هناك مهام مكتملة فقط (بدون متوقفة أو في التقدم)
        if ($completedTasks > 0) {
            return 'قيد التنفيذ';
        }

        // إذا لم تبدأ أي مهمة
        return 'لم تبدأ';
    }

    public function getServiceCompletionRate(int $projectId, int $serviceId): float
    {
        $taskStats = $this->getServiceTaskStats($projectId, $serviceId);

        if ($taskStats['total_tasks'] === 0) {
            return 0.0;
        }

        return round(($taskStats['completed_tasks'] / $taskStats['total_tasks']) * 100, 2);
    }

    public function getProjectCompletionRate(Project $project): float
    {
        $services = $project->services()->get();

        if ($services->isEmpty()) {
            return 0.0;
        }

        $totalServices = $services->count();
        $completedServices = 0;

        foreach ($services as $service) {
            $serviceStatus = DB::table('project_service')
                ->where('project_id', $project->id)
                ->where('service_id', $service->id)
                ->value('service_status');

            if ($serviceStatus === 'مكتملة') {
                $completedServices++;
            }
        }

        return round(($completedServices / $totalServices) * 100, 2);
    }

    /**
     * تحديث حالة المشروع تلقائياً بناءً على حالة خدماته
     */
    public function updateProjectStatus(Project $project): void
    {
        try {
            // لا نغير حالة المشروع إذا كان ملغي أو موقوف
            if (in_array($project->status, ['ملغي', 'موقوف'])) {
                Log::info("Project status not updated - project is cancelled or paused", [
                    'project_id' => $project->id,
                    'current_status' => $project->status
                ]);
                return;
            }

            $serviceStatuses = $this->getProjectServicesStatuses($project);

            if (empty($serviceStatuses)) {
                Log::info("Project has no services - status not updated", [
                    'project_id' => $project->id
                ]);
                return;
            }

            $newProjectStatus = $this->determineProjectStatus($serviceStatuses);

            // تحديث حالة المشروع فقط إذا تغيرت
            if ($project->status !== $newProjectStatus) {
                $oldStatus = $project->status;
                $project->status = $newProjectStatus;
                $project->save();

                Log::info("Project status updated automatically", [
                    'project_id' => $project->id,
                    'old_status' => $oldStatus,
                    'new_status' => $newProjectStatus,
                    'service_statuses' => $serviceStatuses
                ]);
            }

        } catch (\Exception $e) {
            Log::error("Error updating project status", [
                'project_id' => $project->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * الحصول على حالات جميع خدمات المشروع
     */
    private function getProjectServicesStatuses(Project $project): array
    {
        $services = $project->services()->get();
        $statuses = [];

        foreach ($services as $service) {
            $status = DB::table('project_service')
                ->where('project_id', $project->id)
                ->where('service_id', $service->id)
                ->value('service_status');

            $statuses[] = $status ?? 'لم تبدأ';
        }

        return $statuses;
    }

    /**
     * تحديد حالة المشروع بناءً على حالات خدماته
     */
    private function determineProjectStatus(array $serviceStatuses): string
    {
        $totalServices = count($serviceStatuses);

        if ($totalServices === 0) {
            return 'جديد';
        }

        // عد كل حالة
        $notStartedCount = 0;
        $inProgressCount = 0;
        $completedCount = 0;
        $onHoldCount = 0;

        foreach ($serviceStatuses as $status) {
            switch ($status) {
                case 'لم تبدأ':
                    $notStartedCount++;
                    break;
                case 'قيد التنفيذ':
                    $inProgressCount++;
                    break;
                case 'مكتملة':
                    $completedCount++;
                    break;
                case 'معلقة':
                    $onHoldCount++;
                    break;
            }
        }

        // إذا كل الخدمات مكتملة → المشروع مكتمل
        if ($completedCount === $totalServices) {
            return 'مكتمل';
        }

        // إذا أي خدمة قيد التنفيذ أو معلقة → المشروع جاري التنفيذ
        if ($inProgressCount > 0 || $onHoldCount > 0) {
            return 'جاري التنفيذ';
        }

        // إذا بعض الخدمات مكتملة والباقي لم يبدأ → المشروع جاري التنفيذ
        if ($completedCount > 0) {
            return 'جاري التنفيذ';
        }

        // إذا كل الخدمات لم تبدأ → المشروع جديد
        if ($notStartedCount === $totalServices) {
            return 'جديد';
        }

        // افتراضي
        return 'جديد';
    }
}
