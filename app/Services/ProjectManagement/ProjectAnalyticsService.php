<?php

namespace App\Services\ProjectManagement;

use App\Models\Project;
use App\Models\User;
use App\Models\Task;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class ProjectAnalyticsService
{
    public function getProjectAnalytics(Project $project)
    {
        return [
            'project_overview' => $this->getProjectOverview($project),
            'team_statistics' => $this->getTeamStatistics($project),
            'task_analytics' => $this->getTaskAnalytics($project),
            'service_analytics' => $this->getServiceAnalytics($project),
            'time_analytics' => $this->getTimeAnalytics($project),
            'individual_performance' => $this->getIndividualPerformance($project),
            'delayed_work' => $this->getDelayedWork($project),
            'charts_data' => $this->getChartsData($project),
            'recent_activities' => $this->getRecentActivities($project),
            'project_timeline' => $this->getProjectTimeline($project),
            'transfer_statistics' => $this->getProjectTransferStatistics($project)
        ];
    }

    public function getProjectOverview(Project $project)
    {
        $totalParticipants = $project->participants()->count();
        $activeParticipants = $this->getActiveParticipants($project);

        $completionPercentage = $project->getCompletionPercentageAttribute();
        $totalServices = $project->services()->count();
        $completedServices = $project->services()->wherePivot('service_status', 'مكتملة')->count();

        $totalTimeSpent = $project->getTotalActualMinutesAttribute();
        $totalTimeEstimated = $project->getTotalEstimatedMinutesAttribute();

        $regularRealTime = $this->calculateRealTimeMinutes(null, false, $project->id);
        $templateRealTime = $this->calculateRealTimeMinutes(null, true, $project->id);
        $totalRealTime = $regularRealTime + $templateRealTime;

        $totalTimeSpentWithRealTime = $totalTimeSpent + $totalRealTime;

        $totalFlexibleTimeSpent = $this->getTotalFlexibleTimeSpent($project);

        return [
            'total_participants' => $totalParticipants,
            'active_participants' => $activeParticipants,
            'completion_percentage' => $completionPercentage,
            'total_services' => $totalServices,
            'completed_services' => $completedServices,
            'service_completion_rate' => $totalServices > 0 ? round(($completedServices / $totalServices) * 100) : 0,
            'total_time_spent' => $totalTimeSpentWithRealTime,
            'total_time_estimated' => $totalTimeEstimated,
            'total_flexible_time_spent' => $totalFlexibleTimeSpent,
            'total_flexible_time_spent_formatted' => $this->formatMinutesToTime($totalFlexibleTimeSpent),
            'total_real_time' => $totalRealTime,
            'total_real_time_formatted' => $this->formatMinutesToTime($totalRealTime),
            'time_efficiency' => $totalTimeEstimated > 0 ? round(($totalTimeEstimated / max(1, $totalTimeSpentWithRealTime)) * 100) : 0,
            'is_overdue' => $project->client_agreed_delivery_date && Carbon::now()->gt(Carbon::parse($project->client_agreed_delivery_date->format('Y-m-d'))) &&
                          in_array($project->status, ['جديد', 'جاري التنفيذ']),
            'days_remaining' => $project->client_agreed_delivery_date ? Carbon::now()->diffInDays(Carbon::parse($project->client_agreed_delivery_date->format('Y-m-d')), false) : null
        ];
    }

    public function getTeamStatistics(Project $project)
    {
        $participants = $project->participants()->get();
        $departments = $participants->groupBy('department');

        $departmentStats = [];
        foreach ($departments as $department => $members) {
            if (empty($department)) continue;

            $memberIds = $members->pluck('id')->toArray();

            $regularTasks = DB::table('task_users')
                ->join('tasks', 'task_users.task_id', '=', 'tasks.id')
                ->whereIn('task_users.user_id', $memberIds)
                ->where('tasks.project_id', $project->id)
                ->select('task_users.status', DB::raw('count(*) as count'))
                ->groupBy('task_users.status')
                ->pluck('count', 'status')
                ->toArray();

            $templateTasks = DB::table('template_task_user')
                ->whereIn('user_id', $memberIds)
                ->where('project_id', $project->id)
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            $totalTasks = array_sum($regularTasks) + array_sum($templateTasks);
            $completedTasks = ($regularTasks['completed'] ?? 0) + ($templateTasks['completed'] ?? 0);

            $departmentStats[$department] = [
                'name' => $department,
                'members_count' => $members->count(),
                'total_tasks' => $totalTasks,
                'completed_tasks' => $completedTasks,
                'completion_rate' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0
            ];
        }

        return $departmentStats;
    }

    public function getTaskAnalytics(Project $project)
    {
        $regularTasks = $project->tasks;
        $regularTaskStats = [
            'total' => $regularTasks->count(),
            'new' => $regularTasks->where('status', 'new')->count(),
            'in_progress' => $regularTasks->where('status', 'in_progress')->count(),
            'paused' => $regularTasks->where('status', 'paused')->count(),
            'completed' => $regularTasks->where('status', 'completed')->count(),
        ];

        $templateTaskStats = DB::table('template_task_user')
            ->where('project_id', $project->id)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $templateTaskCounts = [
            'total' => array_sum($templateTaskStats),
            'new' => $templateTaskStats['new'] ?? 0,
            'in_progress' => $templateTaskStats['in_progress'] ?? 0,
            'paused' => $templateTaskStats['paused'] ?? 0,
            'completed' => $templateTaskStats['completed'] ?? 0,
        ];

        $combinedStats = [
            'total' => $regularTaskStats['total'] + $templateTaskCounts['total'],
            'new' => $regularTaskStats['new'] + $templateTaskCounts['new'],
            'in_progress' => $regularTaskStats['in_progress'] + $templateTaskCounts['in_progress'],
            'paused' => $regularTaskStats['paused'] + $templateTaskCounts['paused'],
            'completed' => $regularTaskStats['completed'] + $templateTaskCounts['completed'],
        ];

        return [
            'regular' => $regularTaskStats,
            'template' => $templateTaskCounts,
            'combined' => $combinedStats,
            'completion_rate' => $combinedStats['total'] > 0 ?
                round(($combinedStats['completed'] / $combinedStats['total']) * 100) : 0
        ];
    }

    public function getServiceAnalytics(Project $project)
    {
        $services = $project->services()->withPivot('service_status', 'created_at', 'updated_at')->get();

        $serviceStats = [];
        $statusCounts = [
            'لم تبدأ' => 0,
            'قيد التنفيذ' => 0,
            'مكتملة' => 0,
            'معلقة' => 0,
            'ملغية' => 0,
            'جاري' => 0,
            'واقف ع النموذج' => 0,
            'واقف ع الأسئلة' => 0,
            'واقف ع العميل' => 0,
            'واقف ع مكالمة' => 0,
            'موقوف' => 0,
            'تسليم مسودة' => 0,
            'تم تسليم نهائي' => 0
        ];

        $departmentProgress = [];
        $totalServices = $services->count();
        $totalPoints = 0;
        $completedPoints = 0;

        foreach ($services as $service) {
            $status = $service->pivot->service_status ?? 'لم تبدأ';

            // حماية ضد الحالات غير المعروفة
            if (!isset($statusCounts[$status])) {
                $statusCounts[$status] = 0;
            }
            $statusCounts[$status]++;

            $totalPoints += $service->points ?? 0;
            if ($status === 'مكتملة') {
                $completedPoints += $service->points ?? 0;
            }

            $serviceParticipants = DB::table('project_service_user')
                ->join('users', 'project_service_user.user_id', '=', 'users.id')
                ->where('project_service_user.project_id', $project->id)
                ->where('project_service_user.service_id', $service->id)
                ->select('users.id', 'users.name', 'users.department')
                ->get();

            $originalServiceParticipants = $serviceParticipants;

            $department = $service->department ?? 'غير محدد';
            if (!isset($departmentProgress[$department])) {
                $departmentProgress[$department] = [
                    'total' => 0,
                    'completed' => 0,
                    'in_progress' => 0,
                    'not_started' => 0,
                    'services' => []
                ];
            }

            $serviceTasks = $this->getServiceTaskCompletion($project, $service, $originalServiceParticipants->pluck('id')->toArray());

            if (!isset($departmentProgress[$department]['total_tasks'])) {
                $departmentProgress[$department]['total_tasks'] = 0;
                $departmentProgress[$department]['completed_tasks'] = 0;
            }

            $departmentProgress[$department]['total']++;
            $departmentProgress[$department]['services'][] = $service->name;
            $departmentProgress[$department]['total_tasks'] += $serviceTasks['total_tasks'];
            $departmentProgress[$department]['completed_tasks'] += $serviceTasks['completed_tasks'];

            switch ($status) {
                case 'مكتملة':
                    $departmentProgress[$department]['completed']++;
                    break;
                case 'قيد التنفيذ':
                    $departmentProgress[$department]['in_progress']++;
                    break;
                case 'لم تبدأ':
                    $departmentProgress[$department]['not_started']++;
                    break;
            }

            $serviceStats[] = [
                'id' => $service->id,
                'name' => $service->name,
                'department' => $department,
                'status' => $status,
                'points' => $service->points ?? 0,
                'participants_count' => $originalServiceParticipants->count(),
                'participants' => $originalServiceParticipants,
                'task_completion' => $serviceTasks,
                'created_at' => $service->pivot->created_at,
                'updated_at' => $service->pivot->updated_at,
                'status_class' => $this->getStatusClass($status),
                'progress_percentage' => $serviceTasks['completion_rate'],
                'task_progress_percentage' => $serviceTasks['completion_rate']
            ];
        }

        foreach ($departmentProgress as $dept => &$progress) {
            $progress['completion_rate'] = $progress['total_tasks'] > 0 ?
                round(($progress['completed_tasks'] / $progress['total_tasks']) * 100) : 0;
            $progress['services_completion_rate'] = $progress['total'] > 0 ?
                round(($progress['completed'] / $progress['total']) * 100) : 0;
        }

        $serviceWeight = $totalServices > 0 ? (100 / $totalServices) : 0;
        $weightedCompletionRate = 0;

        foreach ($serviceStats as $service) {
            $serviceCompletionRate = $service['task_completion']['completion_rate'];
            $contributionToProject = ($serviceCompletionRate * $serviceWeight) / 100;
            $weightedCompletionRate += $contributionToProject;
        }

        $overallTaskCompletionRate = round($weightedCompletionRate);

        $servicesWithTasks = collect($serviceStats)->filter(function($service) {
            return $service['task_completion']['total_tasks'] > 0;
        });

        $totalProjectTasks = $servicesWithTasks->sum('task_completion.total_tasks');
        $totalProjectCompletedTasks = $servicesWithTasks->sum('task_completion.completed_tasks');

        $oldCalculationRate = $totalProjectTasks > 0 ?
            round(($totalProjectCompletedTasks / $totalProjectTasks) * 100) :
            ($totalServices > 0 ? round(($statusCounts['مكتملة'] / $totalServices) * 100) : 0);

        $servicesCompletionRate = $totalServices > 0 ? round(($statusCounts['مكتملة'] / $totalServices) * 100) : 0;

        return [
            'services' => collect($serviceStats),
            'status_counts' => $statusCounts,
            'completion_rate' => $overallTaskCompletionRate,
            'services_completion_rate' => $servicesCompletionRate,
            'points_completion_rate' => $totalPoints > 0 ? round(($completedPoints / $totalPoints) * 100) : 0,
            'department_progress' => $departmentProgress,
            'total_services' => $totalServices,
            'total_points' => $totalPoints,
            'completed_points' => $completedPoints,
            'total_project_tasks' => $totalProjectTasks,
            'total_project_completed_tasks' => $totalProjectCompletedTasks,
            'chart_data' => $this->getServiceChartData($statusCounts, $departmentProgress),
            'service_timeline' => $this->getServiceTimeline($services)
        ];
    }

    private function getServiceTaskCompletion(Project $project, $service, array $participantIds)
    {
        if (empty($participantIds)) {
            return [
                'total_tasks' => 0,
                'completed_tasks' => 0,
                'completion_rate' => 0
            ];
        }

        try {
            $regularTasks = DB::table('task_users')
                ->join('tasks', 'task_users.task_id', '=', 'tasks.id')
                ->whereIn('task_users.user_id', $participantIds)
                ->where('tasks.project_id', $project->id)
                ->where('tasks.service_id', $service->id)
                ->get();

            $templateTasks = collect();

            try {
                if (Schema::hasTable('template_task_user') && Schema::hasTable('template_tasks') && Schema::hasTable('task_templates')) {
                    $templateTasks = DB::table('template_task_user')
                        ->join('template_tasks', 'template_task_user.template_task_id', '=', 'template_tasks.id')
                        ->join('task_templates', 'template_tasks.task_template_id', '=', 'task_templates.id')
                        ->whereIn('template_task_user.user_id', $participantIds)
                        ->where('template_task_user.project_id', $project->id)
                        ->where('task_templates.service_id', $service->id)
                        ->select('template_task_user.*')
                        ->get();
                }
            } catch (\Exception $templateException) {
                $templateTasks = collect();
            }

            $totalTasks = $regularTasks->count() + $templateTasks->count();
            $completedTasks = $regularTasks->where('status', 'completed')->count() +
                             $templateTasks->where('status', 'completed')->count();

            return [
                'total_tasks' => $totalTasks,
                'completed_tasks' => $completedTasks,
                'completion_rate' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0
            ];
        } catch (\Exception $e) {
            return [
                'total_tasks' => 0,
                'completed_tasks' => 0,
                'completion_rate' => 0
            ];
        }
    }


    private function getStatusClass($status)
    {
        switch ($status) {
            case 'لم تبدأ':
                return 'text-gray-500 bg-gray-100';
            case 'قيد التنفيذ':
                return 'text-blue-600 bg-blue-100';
            case 'مكتملة':
                return 'text-green-600 bg-green-100';
            case 'معلقة':
                return 'text-yellow-600 bg-yellow-100';
            case 'ملغية':
                return 'text-red-600 bg-red-100';
            case 'جاري':
                return 'text-green-600 bg-green-100';
            case 'واقف ع النموذج':
                return 'text-orange-600 bg-orange-100';
            case 'واقف ع الأسئلة':
                return 'text-orange-600 bg-orange-100';
            case 'واقف ع العميل':
                return 'text-orange-600 bg-orange-100';
            case 'واقف ع مكالمة':
                return 'text-orange-600 bg-orange-100';
            case 'موقوف':
                return 'text-red-600 bg-red-100';
            case 'تسليم مسودة':
                return 'text-purple-600 bg-purple-100';
            case 'تم تسليم نهائي':
                return 'text-green-600 bg-green-100';
            default:
                return 'text-gray-500 bg-gray-100';
        }
    }

    private function getServiceChartData($statusCounts, $departmentProgress)
    {
        return [
            'status_pie' => [
                'labels' => array_keys($statusCounts),
                'data' => array_values($statusCounts),
                'colors' => ['#6B7280', '#3B82F6', '#10B981', '#F59E0B', '#EF4444']
            ],
            'department_bar' => [
                'labels' => array_keys($departmentProgress),
                'completion_rates' => array_column($departmentProgress, 'completion_rate'),
                'services_completion_rates' => array_column($departmentProgress, 'services_completion_rate'),
                'colors' => ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6']
            ]
        ];
    }

    private function getServiceTimeline($services)
    {
        return $services->map(function ($service) {
            return [
                'service_name' => $service->name,
                'status' => $service->pivot->service_status,
                'created_at' => $service->pivot->created_at,
                'updated_at' => $service->pivot->updated_at,
                'department' => $service->department ?? 'غير محدد'
            ];
        })->sortBy('created_at')->values()->all();
    }

    public function getTimeAnalytics(Project $project)
    {
        $participants = $project->participants()->get();
        $timeStats = [];

        foreach ($participants as $participant) {
            $regularTime = DB::table('task_users')
                ->join('tasks', 'task_users.task_id', '=', 'tasks.id')
                ->where('task_users.user_id', $participant->id)
                ->where('tasks.project_id', $project->id)
                ->select(
                    DB::raw('SUM((task_users.actual_hours * 60) + task_users.actual_minutes) as actual_minutes'),
                    DB::raw('SUM(CASE WHEN task_users.estimated_hours IS NOT NULL AND task_users.estimated_minutes IS NOT NULL
                              THEN (task_users.estimated_hours * 60) + task_users.estimated_minutes
                              ELSE 0 END) as estimated_minutes'),
                    DB::raw('SUM(CASE WHEN task_users.estimated_hours IS NULL OR task_users.estimated_minutes IS NULL
                              THEN (task_users.actual_hours * 60) + task_users.actual_minutes
                              ELSE 0 END) as flexible_actual_minutes')
                )
                ->first();

            $templateTime = DB::table('template_task_user')
                ->join('template_tasks', 'template_task_user.template_task_id', '=', 'template_tasks.id')
                ->where('template_task_user.user_id', $participant->id)
                ->where('template_task_user.project_id', $project->id)
                ->select(
                    DB::raw('SUM(template_task_user.actual_minutes) as actual_minutes'),
                    DB::raw('SUM(CASE WHEN template_tasks.estimated_hours IS NOT NULL AND template_tasks.estimated_minutes IS NOT NULL
                              THEN (template_tasks.estimated_hours * 60) + template_tasks.estimated_minutes
                              ELSE 0 END) as estimated_minutes'),
                    DB::raw('SUM(CASE WHEN template_tasks.estimated_hours IS NULL OR template_tasks.estimated_minutes IS NULL
                              THEN template_task_user.actual_minutes
                              ELSE 0 END) as flexible_actual_minutes')
                )
                ->first();

            $totalActual = ($regularTime->actual_minutes ?? 0) + ($templateTime->actual_minutes ?? 0);
            $totalEstimated = ($regularTime->estimated_minutes ?? 0) + ($templateTime->estimated_minutes ?? 0);
            $totalFlexibleActual = ($regularTime->flexible_actual_minutes ?? 0) + ($templateTime->flexible_actual_minutes ?? 0);

            $regularRealTime = $this->calculateRealTimeMinutes($participant->id, false, $project->id);
            $templateRealTime = $this->calculateRealTimeMinutes($participant->id, true, $project->id);
            $totalRealTime = $regularRealTime + $templateRealTime;

            $totalActualWithRealTime = $totalActual + $totalRealTime;

            $timeStats[] = [
                'user_id' => $participant->id,
                'user_name' => $participant->name,
                'department' => $participant->department,
                'actual_minutes' => $totalActualWithRealTime,
                'estimated_minutes' => $totalEstimated,
                'flexible_actual_minutes' => $totalFlexibleActual,
                'real_time_minutes' => $totalRealTime,
                'real_time_formatted' => $this->formatMinutesToTime($totalRealTime),
                'efficiency' => $totalEstimated > 0 ? round(($totalEstimated / max(1, $totalActualWithRealTime)) * 100) : 0,
                'actual_formatted' => $this->formatMinutesToTime($totalActualWithRealTime),
                'estimated_formatted' => $this->formatMinutesToTime($totalEstimated),
                'flexible_actual_formatted' => $this->formatMinutesToTime($totalFlexibleActual)
            ];
        }

        usort($timeStats, function($a, $b) {
            return $b['actual_minutes'] <=> $a['actual_minutes'];
        });

        return $timeStats;
    }

    public function getIndividualPerformance(Project $project)
    {
        $participants = $project->participants()->get();
        $performance = [];

        foreach ($participants as $participant) {
            $regularTaskStats = DB::table('task_users')
                ->join('tasks', 'task_users.task_id', '=', 'tasks.id')
                ->where('task_users.user_id', $participant->id)
                ->where('tasks.project_id', $project->id)
                ->select('task_users.status', DB::raw('count(*) as count'))
                ->groupBy('task_users.status')
                ->pluck('count', 'status')
                ->toArray();

            $templateTaskStats = DB::table('template_task_user')
                ->where('user_id', $participant->id)
                ->where('project_id', $project->id)
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            // ✅ إحصائيات المهام المنقولة
            $transferStats = $this->getTransferStatistics($participant->id, $project->id);

            $totalTasks = array_sum($regularTaskStats) + array_sum($templateTaskStats);
            $completedTasks = ($regularTaskStats['completed'] ?? 0) + ($templateTaskStats['completed'] ?? 0);
            $inProgressTasks = ($regularTaskStats['in_progress'] ?? 0) + ($templateTaskStats['in_progress'] ?? 0);
            $pausedTasks = ($regularTaskStats['paused'] ?? 0) + ($templateTaskStats['paused'] ?? 0);

            $lastRegularActivity = DB::table('task_users')
                ->join('tasks', 'task_users.task_id', '=', 'tasks.id')
                ->where('task_users.user_id', $participant->id)
                ->where('tasks.project_id', $project->id)
                ->orderBy('task_users.updated_at', 'desc')
                ->value('task_users.updated_at');

            $lastTemplateActivity = DB::table('template_task_user')
                ->where('user_id', $participant->id)
                ->where('project_id', $project->id)
                ->orderBy('updated_at', 'desc')
                ->value('updated_at');

            $lastActivity = null;
            if ($lastRegularActivity && $lastTemplateActivity) {
                $lastActivity = max($lastRegularActivity, $lastTemplateActivity);
            } elseif ($lastRegularActivity) {
                $lastActivity = $lastRegularActivity;
            } elseif ($lastTemplateActivity) {
                $lastActivity = $lastTemplateActivity;
            }

            $performance[] = [
                'user' => [
                    'id' => $participant->id,
                    'name' => $participant->name,
                    'email' => $participant->email,
                    'department' => $participant->department
                ],
                'tasks' => [
                    'total' => $totalTasks,
                    'completed' => $completedTasks,
                    'in_progress' => $inProgressTasks,
                    'paused' => $pausedTasks,
                    'completion_rate' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0
                ],
                'transfer_stats' => $transferStats,
                'last_activity' => $lastActivity ? Carbon::parse($lastActivity) : null,
                'is_active' => $lastActivity ? Carbon::parse($lastActivity)->isAfter(Carbon::now()->subDays(3)) : false
            ];
        }

        usort($performance, function($a, $b) {
            return $b['tasks']['completion_rate'] <=> $a['tasks']['completion_rate'];
        });

        return $performance;
    }

    public function getDelayedWork(Project $project)
    {
        $delayedWork = [];

        $overdueTasks = $project->tasks()
            ->where('due_date', '<', Carbon::now())
            ->whereIn('status', ['new', 'in_progress'])
            ->with('users')
            ->get();

        foreach ($overdueTasks as $task) {
            $delayedWork[] = [
                'type' => 'regular_task',
                'id' => $task->id,
                'name' => $task->name,
                'due_date' => $task->due_date,
                'days_overdue' => Carbon::parse($task->due_date)->diffInDays(Carbon::now()),
                'status' => $task->status,
                'assigned_users' => $task->users->map(function($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'department' => $user->department
                    ];
                })
            ];
        }

        if ($project->client_agreed_delivery_date && Carbon::now()->lt(Carbon::parse($project->client_agreed_delivery_date->toDateString()))) {
            $daysRemaining = Carbon::now()->diffInDays(Carbon::parse($project->client_agreed_delivery_date->toDateString()));
            $completionRate = $project->getCompletionPercentageAttribute();

            $projectStartDate = $project->start_date ?: $project->created_at;
            $expectedCompletionRate = 100 - ($daysRemaining / $projectStartDate->diffInDays(Carbon::parse($project->client_agreed_delivery_date->toDateString())) * 100);

            if ($completionRate < $expectedCompletionRate - 10) {
                $delayedWork[] = [
                    'type' => 'project_progress',
                    'message' => 'معدل تقدم المشروع أقل من المتوقع',
                    'current_progress' => $completionRate,
                    'expected_progress' => round($expectedCompletionRate),
                    'days_remaining' => $daysRemaining
                ];
            }
        }

        return $delayedWork;
    }

    public function getChartsData(Project $project)
    {
        $taskAnalytics = $this->getTaskAnalytics($project);
        $serviceAnalytics = $this->getServiceAnalytics($project);
        $teamStats = $this->getTeamStatistics($project);

        return [
            'task_status_chart' => [
                'labels' => ['جديدة', 'قيد التنفيذ', 'متوقفة', 'مكتملة'],
                'data' => [
                    $taskAnalytics['combined']['new'],
                    $taskAnalytics['combined']['in_progress'],
                    $taskAnalytics['combined']['paused'],
                    $taskAnalytics['combined']['completed']
                ]
            ],
            'service_status_chart' => [
                'labels' => ['لم تبدأ', 'قيد التنفيذ', 'مكتملة'],
                'data' => [
                    $serviceAnalytics['status_counts']['لم تبدأ'],
                    $serviceAnalytics['status_counts']['قيد التنفيذ'],
                    $serviceAnalytics['status_counts']['مكتملة']
                ]
            ],
            'department_performance_chart' => [
                'labels' => array_keys($teamStats),
                'data' => array_map(function($dept) {
                    return $dept['completion_rate'];
                }, array_values($teamStats))
            ]
        ];
    }

    public function getRecentActivities(Project $project)
    {
        $activities = [];

        $recentTaskUpdates = DB::table('task_users')
            ->join('tasks', 'task_users.task_id', '=', 'tasks.id')
            ->join('users', 'task_users.user_id', '=', 'users.id')
            ->where('tasks.project_id', $project->id)
            ->select(
                DB::raw('CAST(tasks.name AS CHAR CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci) as task_name'),
                DB::raw('CAST(users.name AS CHAR CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci) as user_name'),
                DB::raw('CAST(task_users.status AS CHAR CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci) as status'),
                'task_users.updated_at',
                DB::raw("'regular_task' as type")
            )
            ->orderBy('task_users.updated_at', 'desc')
            ->take(10);

        $recentTemplateUpdates = DB::table('template_task_user')
            ->join('template_tasks', 'template_task_user.template_task_id', '=', 'template_tasks.id')
            ->join('users', 'template_task_user.user_id', '=', 'users.id')
            ->where('template_task_user.project_id', $project->id)
            ->select(
                DB::raw('CAST(template_tasks.name AS CHAR CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci) as task_name'),
                DB::raw('CAST(users.name AS CHAR CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci) as user_name'),
                DB::raw('CAST(template_task_user.status AS CHAR CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci) as status'),
                'template_task_user.updated_at',
                DB::raw("'template_task' as type")
            )
            ->orderBy('template_task_user.updated_at', 'desc')
            ->take(10);

        $allActivities = $recentTaskUpdates
            ->union($recentTemplateUpdates)
            ->orderBy('updated_at', 'desc')
            ->take(15)
            ->get();

        return $allActivities->map(function($activity) {
            return [
                'task_name' => $activity->task_name,
                'user_name' => $activity->user_name,
                'status' => $activity->status,
                'type' => $activity->type,
                'updated_at' => Carbon::parse($activity->updated_at),
                'time_ago' => Carbon::parse($activity->updated_at)->diffForHumans()
            ];
        });
    }

    public function getProjectTimeline(Project $project)
    {
        $timeline = [];

        if ($project->start_date) {
            $timeline[] = [
                'type' => 'project_start',
                'date' => $project->start_date,
                'title' => 'بداية المشروع',
                'description' => 'تم بدء المشروع'
            ];
        }

        if ($project->client_agreed_delivery_date) {
            $timeline[] = [
                'type' => 'project_deadline',
                'date' => $project->client_agreed_delivery_date,
                'title' => 'تاريخ التسليم المتفق عليه مع العميل',
                'description' => 'الموعد المتفق عليه مع العميل لتسليم المشروع',
                'is_future' => Carbon::now()->lt(Carbon::parse($project->client_agreed_delivery_date->toDateString()))
            ];
        }

        if ($project->team_delivery_date) {
            $timeline[] = [
                'type' => 'team_deadline',
                'date' => $project->team_delivery_date,
                'title' => 'تاريخ التسليم المحدد من الفريق',
                'description' => 'الموعد المحدد من قبل الفريق لتسليم المشروع',
                'is_future' => Carbon::now()->lt(Carbon::parse($project->team_delivery_date->toDateString()))
            ];
        }

        if ($project->actual_delivery_date) {
            $timeline[] = [
                'type' => 'actual_delivery',
                'date' => $project->actual_delivery_date,
                'title' => 'تاريخ التسليم الفعلي',
                'description' => 'تاريخ التسليم الفعلي للمشروع',
                'is_future' => false
            ];
        }

        $timeline[] = [
            'type' => 'current',
            'date' => Carbon::now(),
            'title' => 'اليوم',
            'description' => 'الوضع الحالي'
        ];

        usort($timeline, function($a, $b) {
            return $a['date']->timestamp <=> $b['date']->timestamp;
        });

        return $timeline;
    }

    private function getActiveParticipants(Project $project)
    {
        return DB::table('project_service_user')
            ->join('users', 'project_service_user.user_id', '=', 'users.id')
            ->leftJoin('task_users', function($join) use ($project) {
                $join->on('users.id', '=', 'task_users.user_id')
                     ->join('tasks', 'task_users.task_id', '=', 'tasks.id')
                     ->where('tasks.project_id', $project->id);
            })
            ->leftJoin('template_task_user', function($join) use ($project) {
                $join->on('users.id', '=', 'template_task_user.user_id')
                     ->where('template_task_user.project_id', $project->id);
            })
            ->where('project_service_user.project_id', $project->id)
            ->where(function($query) {
                $query->where('task_users.updated_at', '>', Carbon::now()->subDays(7))
                      ->orWhere('template_task_user.updated_at', '>', Carbon::now()->subDays(7));
            })
            ->distinct('users.id')
            ->count('users.id');
    }

    private function getTotalFlexibleTimeSpent(Project $project)
    {
        $regularFlexibleTime = DB::table('task_users')
            ->join('tasks', 'task_users.task_id', '=', 'tasks.id')
            ->where('tasks.project_id', $project->id)
            ->where(function($query) {
                $query->whereNull('task_users.estimated_hours')
                      ->orWhereNull('task_users.estimated_minutes');
            })
            ->sum(DB::raw('(task_users.actual_hours * 60) + task_users.actual_minutes'));

        $templateFlexibleTime = DB::table('template_task_user')
            ->join('template_tasks', 'template_task_user.template_task_id', '=', 'template_tasks.id')
            ->where('template_task_user.project_id', $project->id)
            ->where(function($query) {
                $query->whereNull('template_tasks.estimated_hours')
                      ->orWhereNull('template_tasks.estimated_minutes');
            })
            ->sum('template_task_user.actual_minutes');

        return $regularFlexibleTime + $templateFlexibleTime;
    }

    public function calculateRealTimeMinutes($userIds = null, $isTemplate = false, $projectId = null)
    {
        $now = Carbon::now();

        if ($isTemplate) {
            $query = DB::table('template_task_user')
                ->where('template_task_user.status', 'in_progress')
                ->whereNotNull('template_task_user.started_at');

            if ($userIds) {
                if (is_array($userIds)) {
                    $query->whereIn('template_task_user.user_id', $userIds);
                } else {
                    $query->where('template_task_user.user_id', $userIds);
                }
            }

            if ($projectId) {
                $query->where('template_task_user.project_id', $projectId);
            }

            $inProgressTasks = $query->get();

            $userTimes = [];
            foreach ($inProgressTasks as $task) {
                $userId = $task->user_id;
                $startedAt = Carbon::parse($task->started_at);

                if (!isset($userTimes[$userId]) || $startedAt->lt(Carbon::parse($userTimes[$userId]['earliest_start']))) {
                    $userTimes[$userId] = [
                        'earliest_start' => $task->started_at,
                        'minutes_elapsed' => $startedAt->diffInMinutes($now)
                    ];
                }
            }

            return array_sum(array_column($userTimes, 'minutes_elapsed'));
        } else {
            $query = DB::table('task_users')
                ->where('task_users.status', 'in_progress')
                ->whereNotNull('task_users.start_date');

            if ($userIds) {
                if (is_array($userIds)) {
                    $query->whereIn('task_users.user_id', $userIds);
                } else {
                    $query->where('task_users.user_id', $userIds);
                }
            }

            if ($projectId) {
                $query->join('tasks', 'task_users.task_id', '=', 'tasks.id')
                     ->where('tasks.project_id', $projectId);
            }

            $inProgressTasks = $query->get();

            $userTimes = [];
            foreach ($inProgressTasks as $task) {
                $userId = $task->user_id;
                $startedAt = Carbon::parse($task->start_date);

                if (!isset($userTimes[$userId]) || $startedAt->lt(Carbon::parse($userTimes[$userId]['earliest_start']))) {
                    $userTimes[$userId] = [
                        'earliest_start' => $task->start_date,
                        'minutes_elapsed' => $startedAt->diffInMinutes($now)
                    ];
                }
            }

            return array_sum(array_column($userTimes, 'minutes_elapsed'));
        }
    }

    private function formatMinutesToTime($minutes)
    {
        if ($minutes == 0) return '0h';

        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        $parts = [];
        if ($hours > 0) $parts[] = $hours . 'h';
        if ($mins > 0) $parts[] = $mins . 'm';

        return implode(' ', $parts);
    }

    /**
     * ✅ إحصائيات نقل المهام للمستخدم (محدثة لتطابق منطق النقل الصحيح)
     *
     * المنطق:
     * - المهام المنقولة إلى المستخدم: is_additional_task=true && task_source='transferred'
     * - المهام المنقولة من المستخدم: is_transferred=true في السجل الأصلي
     */
    private function getTransferStatistics($userId, $projectId)
    {
        // ✅ المهام العادية المنقولة إلى المستخدم (بناءً على منطق النقل الصحيح)
        $regularTransferredToUser = DB::table('task_users')
            ->join('tasks', 'task_users.task_id', '=', 'tasks.id')
            ->where('task_users.user_id', $userId)
            ->where('tasks.project_id', $projectId)
            ->where('task_users.is_additional_task', true)
            ->where('task_users.task_source', 'transferred')
            ->count();

        // ✅ مهام القوالب المنقولة إلى المستخدم (بناءً على منطق النقل الصحيح)
        $templateTransferredToUser = DB::table('template_task_user')
            ->where('user_id', $userId)
            ->where('project_id', $projectId)
            ->where('is_additional_task', true)
            ->where('task_source', 'transferred')
            ->count();

        // ✅ المهام العادية المنقولة من المستخدم (السجل الأصلي منقول)
        $regularTransferredFromUser = DB::table('task_users')
            ->join('tasks', 'task_users.task_id', '=', 'tasks.id')
            ->where('task_users.user_id', $userId)
            ->where('tasks.project_id', $projectId)
            ->where('task_users.is_transferred', true)
            ->count();

        // ✅ مهام القوالب المنقولة من المستخدم (السجل الأصلي منقول)
        $templateTransferredFromUser = DB::table('template_task_user')
            ->where('user_id', $userId)
            ->where('project_id', $projectId)
            ->where('is_transferred', true)
            ->count();

        // معلومات تفصيلية عن المهام المنقولة من المستخدم
        $transferredFromDetails = [];

        // ✅ المهام العادية المنقولة من المستخدم مع التفاصيل (السجلات المنقولة)
        $regularTransferredDetails = DB::table('task_users as original')
            ->join('tasks', 'original.task_id', '=', 'tasks.id')
            ->leftJoin('task_users as transferred', 'original.transferred_record_id', '=', 'transferred.id')
            ->leftJoin('users', 'transferred.user_id', '=', 'users.id')
            ->where('original.user_id', $userId)
            ->where('tasks.project_id', $projectId)
            ->where('original.is_transferred', true)
            ->select(
                'tasks.name as task_name',
                'users.name as current_user_name',
                'transferred.status',
                'original.transfer_reason',
                'original.transferred_at',
                DB::raw("'regular' as task_type")
            )
            ->get();

        // ✅ مهام القوالب المنقولة من المستخدم مع التفاصيل (السجلات المنقولة)
        $templateTransferredDetails = DB::table('template_task_user as original')
            ->join('template_tasks', 'original.template_task_id', '=', 'template_tasks.id')
            ->leftJoin('template_task_user as transferred', 'original.transferred_record_id', '=', 'transferred.id')
            ->leftJoin('users', 'transferred.user_id', '=', 'users.id')
            ->where('original.user_id', $userId)
            ->where('original.project_id', $projectId)
            ->where('original.is_transferred', true)
            ->select(
                'template_tasks.name as task_name',
                'users.name as current_user_name',
                'transferred.status',
                'original.transfer_reason',
                'original.transferred_at',
                DB::raw("'template' as task_type")
            )
            ->get();

        $transferredFromDetails = $regularTransferredDetails->merge($templateTransferredDetails)
            ->sortByDesc('transferred_at')
            ->take(10) // أحدث 10 مهام منقولة
            ->values()
            ->toArray();

        return [
            'transferred_to_me' => $regularTransferredToUser + $templateTransferredToUser,
            'transferred_from_me' => $regularTransferredFromUser + $templateTransferredFromUser,
            'regular_transferred_to_me' => $regularTransferredToUser,
            'template_transferred_to_me' => $templateTransferredToUser,
            'regular_transferred_from_me' => $regularTransferredFromUser,
            'template_transferred_from_me' => $templateTransferredFromUser,
            'transferred_from_details' => $transferredFromDetails,
            'has_transfers' => ($regularTransferredToUser + $templateTransferredToUser + $regularTransferredFromUser + $templateTransferredFromUser) > 0
        ];
    }

    /**
     * ✅ إحصائيات نقل المهام للمشروع الكامل
     */
    public function getProjectTransferStatistics(Project $project)
    {
        // إجمالي المهام المنقولة في المشروع
        $totalRegularTransfers = DB::table('task_users')
            ->join('tasks', 'task_users.task_id', '=', 'tasks.id')
            ->where('tasks.project_id', $project->id)
            ->where('task_users.is_transferred', true)
            ->count();

        $totalTemplateTransfers = DB::table('template_task_user')
            ->where('project_id', $project->id)
            ->where('is_transferred', true)
            ->count();

        // المهام الإضافية (المنقولة إلى آخرين)
        $totalAdditionalRegular = DB::table('task_users')
            ->join('tasks', 'task_users.task_id', '=', 'tasks.id')
            ->where('tasks.project_id', $project->id)
            ->where('task_users.is_additional_task', true)
            ->where('task_users.task_source', 'transferred')
            ->count();

        $totalAdditionalTemplate = DB::table('template_task_user')
            ->where('project_id', $project->id)
            ->where('is_additional_task', true)
            ->where('task_source', 'transferred')
            ->count();

        // جميع المهام المنقولة في المشروع
        $allTransfers = $this->getAllProjectTransfers($project->id);

        // إحصائيات المستخدمين الأكثر نقلاً
        $topTransferUsers = $this->getTopTransferUsers($project->id);

        return [
            'total_transfers' => $totalRegularTransfers + $totalTemplateTransfers,
            'regular_transfers' => $totalRegularTransfers,
            'template_transfers' => $totalTemplateTransfers,
            'additional_tasks' => $totalAdditionalRegular + $totalAdditionalTemplate,
            'additional_regular' => $totalAdditionalRegular,
            'additional_template' => $totalAdditionalTemplate,
            'all_transfers' => $allTransfers,
            'top_transfer_users' => $topTransferUsers,
            'has_transfers' => ($totalRegularTransfers + $totalTemplateTransfers) > 0
        ];
    }

    /**
     * الحصول على جميع المهام المنقولة في المشروع
     */
    private function getAllProjectTransfers($projectId)
    {
        // ✅ المهام العادية المنقولة (المهام المستلمة بـ task_source=transferred)
        $regularTransfers = DB::table('task_users as received')
            ->join('tasks', 'received.task_id', '=', 'tasks.id')
            ->leftJoin('task_users as original', 'received.original_task_user_id', '=', 'original.id')
            ->leftJoin('users as from_users', 'original.user_id', '=', 'from_users.id')
            ->join('users as to_users', 'received.user_id', '=', 'to_users.id')
            ->where('tasks.project_id', $projectId)
            ->where('received.is_additional_task', true)
            ->where('received.task_source', 'transferred')
            ->whereNotNull('original.transferred_at')
            ->select(
                DB::raw('CONVERT(tasks.name USING utf8mb4) COLLATE utf8mb4_unicode_ci as task_name'),
                DB::raw('CONVERT(COALESCE(from_users.name, "غير محدد") USING utf8mb4) COLLATE utf8mb4_unicode_ci as from_user_name'),
                DB::raw('CONVERT(to_users.name USING utf8mb4) COLLATE utf8mb4_unicode_ci as to_user_name'),
                DB::raw('CONVERT(COALESCE(original.transfer_reason, "") USING utf8mb4) COLLATE utf8mb4_unicode_ci as transfer_reason'),
                DB::raw('CONVERT(COALESCE(original.transfer_type, "positive") USING utf8mb4) COLLATE utf8mb4_unicode_ci as transfer_type'),
                'original.transferred_at',
                DB::raw('CONVERT(received.status USING utf8mb4) COLLATE utf8mb4_unicode_ci as status'),
                DB::raw('CONVERT("regular" USING utf8mb4) COLLATE utf8mb4_unicode_ci as task_type')
            )
            ->orderBy('original.transferred_at', 'desc');

        // ✅ مهام القوالب المنقولة (المهام المستلمة بـ task_source=transferred)
        $templateTransfers = DB::table('template_task_user as received')
            ->join('template_tasks', 'received.template_task_id', '=', 'template_tasks.id')
            ->leftJoin('template_task_user as original', 'received.original_template_task_user_id', '=', 'original.id')
            ->leftJoin('users as from_users', 'original.user_id', '=', 'from_users.id')
            ->join('users as to_users', 'received.user_id', '=', 'to_users.id')
            ->where('received.project_id', $projectId)
            ->where('received.is_additional_task', true)
            ->where('received.task_source', 'transferred')
            ->whereNotNull('original.transferred_at')
            ->select(
                DB::raw('CONVERT(template_tasks.name USING utf8mb4) COLLATE utf8mb4_unicode_ci as task_name'),
                DB::raw('CONVERT(COALESCE(from_users.name, "غير محدد") USING utf8mb4) COLLATE utf8mb4_unicode_ci as from_user_name'),
                DB::raw('CONVERT(to_users.name USING utf8mb4) COLLATE utf8mb4_unicode_ci as to_user_name'),
                DB::raw('CONVERT(COALESCE(original.transfer_reason, "") USING utf8mb4) COLLATE utf8mb4_unicode_ci as transfer_reason'),
                DB::raw('CONVERT(COALESCE(original.transfer_type, "positive") USING utf8mb4) COLLATE utf8mb4_unicode_ci as transfer_type'),
                'original.transferred_at',
                DB::raw('CONVERT(received.status USING utf8mb4) COLLATE utf8mb4_unicode_ci as status'),
                DB::raw('CONVERT("template" USING utf8mb4) COLLATE utf8mb4_unicode_ci as task_type')
            )
            ->orderBy('original.transferred_at', 'desc');

        // دمج النتائج وترتيبها (جميع النتائج بدون حد أقصى)
        return $regularTransfers->union($templateTransfers)
            ->orderBy('transferred_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * الحصول على المستخدمين الأكثر نقلاً للمهام
     */
    private function getTopTransferUsers($projectId, $limit = 10)
    {
        // إحصائيات المستخدمين في نقل المهام
        $userTransferStats = [];

        // المهام العادية - المستلمة
        $regularReceivedStats = DB::table('task_users')
            ->join('tasks', 'task_users.task_id', '=', 'tasks.id')
            ->join('users', 'task_users.user_id', '=', 'users.id')
            ->where('tasks.project_id', $projectId)
            ->where('task_users.is_additional_task', true)
            ->where('task_users.task_source', 'transferred')
            ->groupBy('task_users.user_id', 'users.name', 'users.department')
            ->select(
                'task_users.user_id',
                'users.name',
                'users.department',
                DB::raw('COUNT(*) as received_count')
            )
            ->get();

        // مهام القوالب - المستلمة
        $templateReceivedStats = DB::table('template_task_user')
            ->join('users', 'template_task_user.user_id', '=', 'users.id')
            ->where('template_task_user.project_id', $projectId)
            ->where('template_task_user.is_additional_task', true)
            ->where('template_task_user.task_source', 'transferred')
            ->groupBy('template_task_user.user_id', 'users.name', 'users.department')
            ->select(
                'template_task_user.user_id',
                'users.name',
                'users.department',
                DB::raw('COUNT(*) as received_count')
            )
            ->get();

        // دمج النتائج
        foreach ($regularReceivedStats as $stat) {
            $userTransferStats[$stat->user_id] = [
                'user_id' => $stat->user_id,
                'name' => $stat->name,
                'department' => $stat->department ?? 'غير محدد',
                'avatar' => null, // سيتم تعبئته لاحقاً إذا كان متوفراً
                'received_regular' => $stat->received_count,
                'received_template' => 0,
                'total_received' => $stat->received_count
            ];
        }

        foreach ($templateReceivedStats as $stat) {
            if (isset($userTransferStats[$stat->user_id])) {
                $userTransferStats[$stat->user_id]['received_template'] = $stat->received_count;
                $userTransferStats[$stat->user_id]['total_received'] += $stat->received_count;
            } else {
                $userTransferStats[$stat->user_id] = [
                    'user_id' => $stat->user_id,
                    'name' => $stat->name,
                    'department' => $stat->department ?? 'غير محدد',
                    'avatar' => null, // سيتم تعبئته لاحقاً إذا كان متوفراً
                    'received_regular' => 0,
                    'received_template' => $stat->received_count,
                    'total_received' => $stat->received_count
                ];
            }
        }

        // جلب صور المستخدمين إذا كان العمود متوفراً
        try {
            if (Schema::hasColumn('users', 'avatar')) {
                $userIds = array_keys($userTransferStats);
                $avatars = DB::table('users')
                    ->whereIn('id', $userIds)
                    ->select('id', 'avatar')
                    ->get()
                    ->keyBy('id');

                foreach ($userTransferStats as $userId => &$stats) {
                    if (isset($avatars[$userId])) {
                        $stats['avatar'] = $avatars[$userId]->avatar;
                    }
                }
            }
        } catch (\Exception $e) {
            // تجاهل خطأ عمود الصورة إذا لم يكن موجوداً
        }

        // ترتيب حسب العدد الأكبر وإرجاع أفضل المستخدمين
        return collect($userTransferStats)
            ->sortByDesc('total_received')
            ->take($limit)
            ->values()
            ->toArray();
    }
}
