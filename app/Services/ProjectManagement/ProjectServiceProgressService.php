<?php

namespace App\Services\ProjectManagement;

use App\Models\Project;
use App\Services\Auth\RoleCheckService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProjectServiceProgressService
{
    protected $roleCheckService;

    public function __construct(RoleCheckService $roleCheckService)
    {
        $this->roleCheckService = $roleCheckService;
    }

    /**
     * Update service status and progress
     */
    public function updateServiceProgress(Project $project, $serviceId, array $data)
    {
        // Check if user has permission to update service progress
        $user = Auth::user();
        $isAdmin = $this->roleCheckService->userHasRole(['hr', 'company_manager', 'project_manager', 'operation_assistant']);

        if (!$isAdmin) {
            // Check if user is assigned to this service
            $isAssigned = DB::table('project_service_user')
                ->where('project_id', $project->id)
                ->where('service_id', $serviceId)
                ->where('user_id', $user->id)
                ->exists();

            if (!$isAssigned) {
                throw new \Exception('غير مسموح لك بتحديث تقدم هذه الخدمة');
            }
        }

        $status = $data['status'];
        $progressPercentage = $data['progress_percentage'];

        // Auto-calculate progress percentage based on status if not provided
        if ($progressPercentage === null) {
            switch ($status) {
                case 'لم تبدأ':
                    $progressPercentage = 0;
                    break;
                case 'قيد التنفيذ':
                    $progressPercentage = 50;
                    break;
                case 'مكتملة':
                    $progressPercentage = 100;
                    break;
                case 'معلقة':
                    $progressPercentage = 25;
                    break;
                case 'ملغية':
                    $progressPercentage = 0;
                    break;
            }
        }

        $updateData = [
            'service_status' => $status,
            'progress_percentage' => $progressPercentage,
            'progress_notes' => $data['progress_notes'] ?? null,
            'updated_at' => now()
        ];

        // Set started_at timestamp if moving from "لم تبدأ" to any active status
        $currentStatus = DB::table('project_service')
            ->where('project_id', $project->id)
            ->where('service_id', $serviceId)
            ->value('service_status');

        if ($currentStatus === 'لم تبدأ' && in_array($status, ['قيد التنفيذ', 'مكتملة'])) {
            $updateData['started_at'] = now();
        }

        // Set completed_at timestamp if status is "مكتملة"
        if ($status === 'مكتملة') {
            $updateData['completed_at'] = now();
        }

        // Store progress history
        $existingHistory = DB::table('project_service')
            ->where('project_id', $project->id)
            ->where('service_id', $serviceId)
            ->value('progress_history');

        $history = $existingHistory ? json_decode($existingHistory, true) : [];
        $history[] = [
            'status' => $status,
            'progress_percentage' => $progressPercentage,
            'notes' => $data['progress_notes'] ?? null,
            'updated_by' => $user->name,
            'updated_at' => now()->toISOString()
        ];

        $updateData['progress_history'] = json_encode($history);

        DB::table('project_service')
            ->where('project_id', $project->id)
            ->where('service_id', $serviceId)
            ->update($updateData);

        return $updateData;
    }

    /**
     * Get service progress history
     */
    public function getServiceProgressHistory(Project $project, $serviceId)
    {
        $user = Auth::user();
        $isAdmin = $this->roleCheckService->userHasRole(['hr', 'company_manager', 'project_manager']);

        if (!$isAdmin) {
            $userProjectIds = DB::table('project_service_user')
                ->where('user_id', $user->id)
                ->pluck('project_id')
                ->toArray();

            if (!in_array($project->id, $userProjectIds)) {
                throw new \Exception('غير مسموح لك بعرض تاريخ هذه الخدمة');
            }
        }

        $serviceData = DB::table('project_service')
            ->join('company_services', 'project_service.service_id', '=', 'company_services.id')
            ->where('project_service.project_id', $project->id)
            ->where('project_service.service_id', $serviceId)
            ->select(
                'company_services.name as service_name',
                'project_service.*'
            )
            ->first();

        if (!$serviceData) {
            throw new \Exception('الخدمة غير موجودة في هذا المشروع');
        }

        $history = $serviceData->progress_history ? json_decode($serviceData->progress_history, true) : [];

        return [
            'service_name' => $serviceData->service_name,
            'current_status' => $serviceData->service_status,
            'current_progress' => $serviceData->progress_percentage,
            'started_at' => $serviceData->started_at,
            'completed_at' => $serviceData->completed_at,
            'history' => $history
        ];
    }

    /**
     * Get services that need attention (overdue, blocked, etc.)
     */
    public function getServiceAlerts(Project $project)
    {
        $user = Auth::user();
        $isAdmin = $this->roleCheckService->userHasRole(['hr', 'company_manager', 'project_manager']);

        if (!$isAdmin) {
            $userProjectIds = DB::table('project_service_user')
                ->where('user_id', $user->id)
                ->pluck('project_id')
                ->toArray();

            if (!in_array($project->id, $userProjectIds)) {
                throw new \Exception('غير مسموح لك بعرض تنبيهات هذا المشروع');
            }
        }

        $alerts = [];

        // Get services that have been "قيد التنفيذ" for too long (more than 30 days)
        $longRunningServices = $this->getLongRunningServices($project);
        foreach ($longRunningServices as $service) {
            $alerts[] = [
                'type' => 'long_running',
                'severity' => 'warning',
                'message' => "الخدمة '{$service->name}' قيد التنفيذ لأكثر من 30 يوم",
                'service_name' => $service->name,
                'started_at' => $service->started_at,
                'progress' => $service->progress_percentage
            ];
        }

        // Get services that are "معلقة"
        $pausedServices = $this->getPausedServices($project);
        foreach ($pausedServices as $service) {
            $alerts[] = [
                'type' => 'paused',
                'severity' => 'info',
                'message' => "الخدمة '{$service->name}' معلقة حالياً",
                'service_name' => $service->name,
                'notes' => $service->progress_notes
            ];
        }

        // Get services with low progress relative to time spent
        $lowProgressServices = $this->getLowProgressServices($project);
        foreach ($lowProgressServices as $service) {
            $alerts[] = [
                'type' => 'low_progress',
                'severity' => 'warning',
                'message' => "الخدمة '{$service->name}' لديها تقدم منخفض ({$service->progress_percentage}%) مقارنة بالوقت المستغرق",
                'service_name' => $service->name,
                'progress' => $service->progress_percentage,
                'started_at' => $service->started_at
            ];
        }

        return [
            'alerts' => $alerts,
            'total_alerts' => count($alerts),
            'severity_counts' => [
                'warning' => collect($alerts)->where('severity', 'warning')->count(),
                'info' => collect($alerts)->where('severity', 'info')->count(),
                'error' => collect($alerts)->where('severity', 'error')->count()
            ]
        ];
    }

    /**
     * Get services that have been running for too long
     */
    private function getLongRunningServices(Project $project)
    {
        return DB::table('project_service')
            ->join('company_services', 'project_service.service_id', '=', 'company_services.id')
            ->where('project_service.project_id', $project->id)
            ->where('project_service.service_status', 'قيد التنفيذ')
            ->where('project_service.started_at', '<', now()->subDays(30))
            ->select('company_services.name', 'project_service.started_at', 'project_service.progress_percentage')
            ->get();
    }

    /**
     * Get paused services
     */
    private function getPausedServices(Project $project)
    {
        return DB::table('project_service')
            ->join('company_services', 'project_service.service_id', '=', 'company_services.id')
            ->where('project_service.project_id', $project->id)
            ->where('project_service.service_status', 'معلقة')
            ->select('company_services.name', 'project_service.progress_notes')
            ->get();
    }

    /**
     * Get services with low progress
     */
    private function getLowProgressServices(Project $project)
    {
        return DB::table('project_service')
            ->join('company_services', 'project_service.service_id', '=', 'company_services.id')
            ->where('project_service.project_id', $project->id)
            ->where('project_service.service_status', 'قيد التنفيذ')
            ->where('project_service.progress_percentage', '<', 30)
            ->where('project_service.started_at', '<', now()->subDays(7))
            ->select('company_services.name', 'project_service.progress_percentage', 'project_service.started_at')
            ->get();
    }
}
