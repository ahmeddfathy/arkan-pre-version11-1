<?php

namespace App\Services\ProjectManagement;

use App\Models\Project;

class ProjectValidationService
{
    /**
     * التحقق من إمكانية حذف المشروع
     */
    public function checkDeletionPossibility($projectId): array
    {
        try {
            $project = Project::findOrFail($projectId);

            $canBeDeleted = $project->canBeDeleted();
            $errorMessage = $canBeDeleted ? null : $project->getDeletionErrorMessage();

            // إحصائيات المهام
            $taskStats = [
                'total_tasks' => $project->tasks()->count(),
                'running_tasks' => $project->tasks()->whereIn('status', ['جاري', 'متوقف'])->count(),
                'incomplete_tasks' => $project->tasks()->whereNotIn('status', ['مكتمل', 'ملغي'])->count(),
                'completed_tasks' => $project->tasks()->where('status', 'مكتمل')->count(),
            ];

            return [
                'success' => true,
                'can_be_deleted' => $canBeDeleted,
                'error_message' => $errorMessage,
                'task_stats' => $taskStats,
                'status_code' => 200
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'حدث خطأ في التحقق من إمكانية الحذف',
                'status_code' => 500
            ];
        }
    }
}

