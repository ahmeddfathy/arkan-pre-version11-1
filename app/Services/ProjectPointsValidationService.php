<?php

namespace App\Services;

use App\Models\Project;
use App\Models\CompanyService;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProjectPointsValidationService
{
    /**
     * التحقق من إمكانية إضافة مهمة جديدة للمشروع
     */
    public function canAddTaskToProject(Project $project, CompanyService $service, int $taskPoints): array
    {
        // التحقق من وجود حد أقصى للخدمة
        if (!$service->hasMaxPointsLimit()) {
            return [
                'can_add' => true,
                'message' => 'لا يوجد حد أقصى محدد لهذه الخدمة',
                'current_points' => $service->getCurrentPointsForProject($project->id),
                'max_points' => null,
                'remaining_points' => null
            ];
        }

        $currentPoints = $service->getCurrentPointsForProject($project->id);
        $maxPoints = $service->getMaxPointsPerProject();
        $remainingPoints = $service->getRemainingPointsForProject($project->id);

        $canAdd = $service->canAddPointsToProject($project->id, $taskPoints);

        return [
            'can_add' => $canAdd,
            'message' => $canAdd
                ? 'يمكن إضافة المهمة'
                : "تجاوز الحد الأقصى للنقاط. الحد الأقصى: {$maxPoints}، النقاط الحالية: {$currentPoints}، مطلوب: {$taskPoints}",
            'current_points' => $currentPoints,
            'max_points' => $maxPoints,
            'remaining_points' => $remainingPoints,
            'requested_points' => $taskPoints
        ];
    }

    /**
     * التحقق من إمكانية تخصيص مهام متعددة لمشروع
     */
    public function canAddMultipleTasksToProject(Project $project, CompanyService $service, array $tasks): array
    {
        $totalRequestedPoints = array_sum(array_column($tasks, 'points'));

        return $this->canAddTaskToProject($project, $service, $totalRequestedPoints);
    }

    /**
     * التحقق من إمكانية تعديل نقاط مهمة موجودة
     */
    public function canUpdateTaskPoints(Task $task, int $newPoints): array
    {
        if (!$task->service) {
            return [
                'can_update' => true,
                'message' => 'المهمة غير مرتبطة بخدمة'
            ];
        }

        $service = $task->service;
        $project = $task->project;

        if (!$service->hasMaxPointsLimit()) {
            return [
                'can_update' => true,
                'message' => 'لا يوجد حد أقصى محدد لهذه الخدمة'
            ];
        }

        // حساب الفرق في النقاط
        $pointsDifference = $newPoints - $task->points;

        // إذا كان التعديل يقلل النقاط، فهو مسموح دائماً
        if ($pointsDifference <= 0) {
            return [
                'can_update' => true,
                'message' => 'تقليل النقاط مسموح دائماً'
            ];
        }

        // التحقق من إمكانية إضافة النقاط الإضافية
        return $this->canAddTaskToProject($project, $service, $pointsDifference);
    }

    /**
     * الحصول على تقرير شامل لحالة النقاط في المشروع
     */
    public function getProjectPointsReport(Project $project): array
    {
        $services = $project->services()->with('tasks')->get();
        $report = [];

        foreach ($services as $service) {
            $currentPoints = $service->getCurrentPointsForProject($project->id);
            $maxPoints = $service->getMaxPointsPerProject();
            $hasLimit = $service->hasMaxPointsLimit();

            $report[] = [
                'service_id' => $service->id,
                'service_name' => $service->name,
                'current_points' => $currentPoints,
                'max_points' => $maxPoints,
                'has_limit' => $hasLimit,
                'remaining_points' => $hasLimit ? max(0, $maxPoints - $currentPoints) : null,
                'is_over_limit' => $hasLimit && $currentPoints > $maxPoints,
                'usage_percentage' => $hasLimit && $maxPoints > 0 ? round(($currentPoints / $maxPoints) * 100, 2) : null
            ];
        }

        return $report;
    }

    /**
     * التحقق من جميع المشاريع التي تجاوزت الحد الأقصى
     */
    public function getOverLimitProjects(): array
    {
        $overLimitProjects = [];

        $services = CompanyService::where('max_points_per_project', '>', 0)->get();

        foreach ($services as $service) {
            $projects = Project::whereHas('services', function($query) use ($service) {
                $query->where('company_services.id', $service->id);
            })->get();

            foreach ($projects as $project) {
                if ($service->isOverLimitForProject($project->id)) {
                    $overLimitProjects[] = [
                        'project_id' => $project->id,
                        'project_name' => $project->name,
                        'service_id' => $service->id,
                        'service_name' => $service->name,
                        'current_points' => $service->getCurrentPointsForProject($project->id),
                        'max_points' => $service->getMaxPointsPerProject(),
                        'excess_points' => $service->getCurrentPointsForProject($project->id) - $service->getMaxPointsPerProject()
                    ];
                }
            }
        }

        return $overLimitProjects;
    }

    /**
     * اقتراح توزيع النقاط بناءً على الحدود المتاحة
     */
    public function suggestPointsDistribution(Project $project, CompanyService $service, array $proposedTasks): array
    {
        if (!$service->hasMaxPointsLimit()) {
            return [
                'can_distribute' => true,
                'suggested_tasks' => $proposedTasks,
                'message' => 'لا يوجد حدود، يمكن توزيع جميع المهام كما هو مطلوب'
            ];
        }

        $availablePoints = $service->getRemainingPointsForProject($project->id);
        $totalRequestedPoints = array_sum(array_column($proposedTasks, 'points'));

        if ($totalRequestedPoints <= $availablePoints) {
            return [
                'can_distribute' => true,
                'suggested_tasks' => $proposedTasks,
                'message' => 'يمكن توزيع جميع المهام ضمن الحد المسموح'
            ];
        }

        // اقتراح تعديل النقاط للمهام لتناسب الحد المتاح
        $suggestedTasks = [];
        $distributionRatio = $availablePoints / $totalRequestedPoints;

        foreach ($proposedTasks as $task) {
            $suggestedPoints = max(1, floor($task['points'] * $distributionRatio));
            $suggestedTasks[] = array_merge($task, [
                'original_points' => $task['points'],
                'suggested_points' => $suggestedPoints
            ]);
        }

        return [
            'can_distribute' => false,
            'suggested_tasks' => $suggestedTasks,
            'message' => "النقاط المطلوبة ({$totalRequestedPoints}) تتجاوز الحد المتاح ({$availablePoints}). تم اقتراح توزيع معدل.",
            'available_points' => $availablePoints,
            'requested_points' => $totalRequestedPoints,
            'adjustment_ratio' => $distributionRatio
        ];
    }
}
