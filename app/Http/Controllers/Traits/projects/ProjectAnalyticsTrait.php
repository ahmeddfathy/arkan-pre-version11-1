<?php

namespace App\Http\Controllers\Traits\Projects;

use App\Models\Project;
use App\Models\User;
use App\Models\ProjectServiceUser;
use App\Models\TaskRevision;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait ProjectAnalyticsTrait
{
    protected $analyticsService;
    protected $employeeAnalyticsService;
    protected $teamRecommendationService;
    protected $serviceProgressService;
    protected $authorizationService;
    protected $revisionStatsService;
    protected $errorStatsService;
    protected $sidebarService;

    public function analytics(Project $project)
    {
        $user = Auth::user();
        $isAdmin = $this->authorizationService->isAdmin();

        if (!$isAdmin) {
            $userProjectIds = DB::table('project_service_user')
                ->where('user_id', $user->id)
                ->pluck('project_id')
                ->toArray();

            if (!in_array($project->id, $userProjectIds)) {
                abort(403, 'غير مسموح لك بعرض إحصائيات هذا المشروع');
            }
        }

        activity()
            ->causedBy($user)
            ->performedOn($project)
            ->withProperties([
                'action_type' => 'view_analytics',
                'page' => 'project_analytics',
                'project_id' => $project->id,
                'project_name' => $project->name,
                'viewed_at' => now()->toDateTimeString(),
                'user_agent' => request()->userAgent(),
                'ip_address' => request()->ip()
            ])
            ->log('دخل على صفحة إحصائيات المشروع: ' . $project->name);

        $analyticsData = $this->analyticsService->getProjectAnalytics($project);

        $user = Auth::user();
        $isAdmin = $this->authorizationService->isAdmin();

        $revisionStats = $this->revisionStatsService->getProjectRevisionStats($project->id, $isAdmin, $user->id);
        $revisionsByCategory = $this->revisionStatsService->getRevisionsByCategory($project->id, $isAdmin, $user->id);
        $latestRevisions = $this->revisionStatsService->getProjectLatestRevisions($project->id, $isAdmin, $user->id);
        $pendingRevisions = $this->revisionStatsService->getProjectPendingRevisions($project->id, $isAdmin, $user->id);
        $urgentRevisions = $this->revisionStatsService->getProjectUrgentRevisions($project->id, $isAdmin, $user->id);

        $attachmentStats = $this->revisionStatsService->getAttachmentStats($isAdmin, [$user->id]);
        $averageReviewTime = $this->revisionStatsService->getAverageReviewTime();

        $projectMeetings = DB::table('meetings')
            ->where('project_id', $project->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $projectUserIds = DB::table('project_service_user')
            ->where('project_id', $project->id)
            ->pluck('user_id')
            ->toArray();

        $projectRevisionTransferStats = $this->revisionStatsService->getRevisionTransferStats($projectUserIds, null);

        $projectErrorStats = $this->errorStatsService->getGroupErrorStats($projectUserIds, null);

        return view('projects.analytics', array_merge(
            $analyticsData,
            [
                'project' => $project,
                'revisionStats' => $revisionStats,
                'revisionsByCategory' => $revisionsByCategory,
                'latestRevisions' => $latestRevisions,
                'pendingRevisions' => $pendingRevisions,
                'urgentRevisions' => $urgentRevisions,
                'attachmentStats' => $attachmentStats,
                'averageReviewTime' => $averageReviewTime,
                'projectMeetings' => $projectMeetings,
                'projectRevisionTransferStats' => $projectRevisionTransferStats,
                'projectErrorStats' => $projectErrorStats
            ]
        ));
    }

    public function getEmployeeProjectPerformance(Request $request, Project $project, $userId)
    {
        $user = Auth::user();
        $isAdmin = $this->authorizationService->isAdmin();

        if (!$isAdmin && $user->id != $userId) {
            $userProjectIds = DB::table('project_service_user')
                ->where('user_id', $user->id)
                ->pluck('project_id')
                ->toArray();

            if (!in_array($project->id, $userProjectIds)) {
                abort(403, 'غير مسموح لك بعرض هذه المعلومات');
            }
        }

        $employee = User::findOrFail($userId);

        $isProjectMember = DB::table('project_service_user')
            ->where('project_id', $project->id)
            ->where('user_id', $userId)
            ->exists();

        if (!$isProjectMember) {
            abort(404, 'الموظف ليس عضواً في هذا المشروع');
        }

        $performanceData = $this->employeeAnalyticsService->getEmployeeProjectAnalytics($project, $employee);

        activity()
            ->causedBy($user)
            ->performedOn($project)
            ->withProperties([
                'action_type' => 'view_employee_analytics',
                'page' => 'employee_analytics',
                'project_id' => $project->id,
                'project_name' => $project->name,
                'employee_id' => $employee->id,
                'employee_name' => $employee->name,
                'viewed_at' => now()->toDateTimeString(),
                'user_agent' => request()->userAgent(),
                'ip_address' => request()->ip()
            ])
            ->log('دخل على صفحة تحليلات الموظف: ' . $employee->name . ' في المشروع: ' . $project->name);

        $revisionStats = $this->revisionStatsService->getGeneralRevisionStats(false, [$userId]);
        $latestRevisions = $this->revisionStatsService->getLatestRevisions(false, [$userId]);
        $pendingRevisions = $this->revisionStatsService->getPendingRevisions(false, [$userId]);
        $urgentRevisions = $this->revisionStatsService->getUrgentRevisions(false, [$userId]);
        $employeeAttachmentStats = $this->revisionStatsService->getAttachmentStats(false, [$userId]);
        $averageReviewTime = $this->revisionStatsService->getAverageReviewTime();

        $employeeProjectMeetings = DB::table('meetings')
            ->where('project_id', $project->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $employeeRevisionTransferStats = $this->revisionStatsService->getRevisionTransferStats($userId, null);

        $revisionsByCategory = $this->revisionStatsService->getRevisionsByCategory(null, false, [$userId]);

        $employeeErrorStats = $this->errorStatsService->getGroupErrorStats($userId, null);

        return view('projects.employee-analytics', compact(
            'project',
            'employee',
            'performanceData',
            'revisionStats',
            'revisionsByCategory',
            'latestRevisions',
            'pendingRevisions',
            'urgentRevisions',
            'employeeAttachmentStats',
            'averageReviewTime',
            'employeeProjectMeetings',
            'employeeRevisionTransferStats',
            'employeeErrorStats'
        ));
    }

    public function serviceAnalyticsPage(Project $project)
    {
        $user = Auth::user();
        $isAdmin = $this->authorizationService->isAdmin();

        if (!$isAdmin) {
            $userProjectIds = DB::table('project_service_user')
                ->where('user_id', $user->id)
                ->pluck('project_id')
                ->toArray();

            if (!in_array($project->id, $userProjectIds)) {
                abort(403, 'غير مسموح لك بعرض إحصائيات هذا المشروع');
            }
        }

        activity()
            ->causedBy($user)
            ->performedOn($project)
            ->withProperties([
                'action_type' => 'view_services_analytics',
                'page' => 'services_analytics',
                'project_id' => $project->id,
                'project_name' => $project->name,
                'viewed_at' => now()->toDateTimeString(),
                'user_agent' => request()->userAgent(),
                'ip_address' => request()->ip()
            ])
            ->log('دخل على صفحة إحصائيات الخدمات للمشروع: ' . $project->name);

        $user = Auth::user();
        $isAdmin = $this->authorizationService->isAdmin();

        $revisionStats = $this->revisionStatsService->getProjectRevisionStats($project->id, $isAdmin, $user->id);
        $revisionsByCategory = $this->revisionStatsService->getRevisionsByCategory($project->id, $isAdmin, $user->id);
        $latestRevisions = $this->revisionStatsService->getProjectLatestRevisions($project->id, $isAdmin, $user->id);
        $pendingRevisions = $this->revisionStatsService->getProjectPendingRevisions($project->id, $isAdmin, $user->id);
        $urgentRevisions = $this->revisionStatsService->getProjectUrgentRevisions($project->id, $isAdmin, $user->id);

        $attachmentStats = $this->revisionStatsService->getAttachmentStats($isAdmin, [$user->id]);
        $averageReviewTime = $this->revisionStatsService->getAverageReviewTime();

        return view('projects.services-analytics', compact(
            'project',
            'revisionStats',
            'revisionsByCategory',
            'latestRevisions',
            'pendingRevisions',
            'urgentRevisions',
            'attachmentStats',
            'averageReviewTime'
        ));
    }

    public function getServiceAnalytics(Project $project)
    {
        $user = Auth::user();
        $isAdmin = $this->authorizationService->isAdmin();

        if (!$isAdmin) {
            $userProjectIds = DB::table('project_service_user')
                ->where('user_id', $user->id)
                ->pluck('project_id')
                ->toArray();

            if (!in_array($project->id, $userProjectIds)) {
                return response()->json(['error' => 'غير مسموح لك بعرض إحصائيات هذا المشروع'], 403);
            }
        }

        $serviceAnalytics = $this->analyticsService->getServiceAnalytics($project);

        return response()->json($serviceAnalytics);
    }

    public function updateServiceProgress(Request $request, Project $project, $serviceId)
    {
        $request->validate([
            'status' => 'required|in:لم تبدأ,قيد التنفيذ,مكتملة,معلقة,ملغية',
            'progress_percentage' => 'nullable|integer|min:0|max:100',
            'progress_notes' => 'nullable|string|max:1000'
        ]);

        try {
            $updateData = $this->serviceProgressService->updateServiceProgress($project, $serviceId, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث تقدم الخدمة بنجاح',
                'service_progress' => $updateData
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        }
    }

    public function getServiceProgressHistory(Project $project, $serviceId)
    {
        try {
            $historyData = $this->serviceProgressService->getServiceProgressHistory($project, $serviceId);
            return response()->json($historyData);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], $e->getMessage() === 'الخدمة غير موجودة في هذا المشروع' ? 404 : 403);
        }
    }

    public function getServiceAlerts(Project $project)
    {
        try {
            $alertsData = $this->serviceProgressService->getServiceAlerts($project);
            return response()->json($alertsData);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        }
    }

    public function getSmartTeamSuggestion(Request $request, Project $project, $serviceId)
    {
        if (!$this->authorizationService->canViewTeamSuggestion()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مسموح لك بعرض اقتراح الفريق'
            ], 403);
        }

        try {
            $result = $this->teamRecommendationService->getSmartTeamSuggestion($project, $serviceId);

            return response()->json([
                'success' => true,
                'service' => $result['service'],
                'teams_analysis' => $result['teams_analysis'],
                'best_team' => $result['best_team']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحليل الفرق: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getSidebarDetails(Project $project)
    {
        $result = $this->sidebarService->getSidebarDetails($project);
        return response()->json($result, $result['status_code']);
    }

    public function getProjectParticipants(Project $project)
    {
        $result = $this->sidebarService->getProjectParticipants($project);
        return response()->json($result, $result['status_code']);
    }

    public function getProjectServicesSimple($projectId)
    {
        try {
            $project = Project::with(['services'])->findOrFail($projectId);

            // التحقق من الصلاحيات - فقط الأدوار المحددة يمكنها الوصول
            $user = Auth::user();
            $allowedRoles = [
                'company_manager',
                'project_manager',
                'operations_manager',
                'general_reviewer',
                'operation_assistant',
                'coordination_department_manager',
                'coordination_team_leader',
                'coordination-team-employee'
            ];
            $hasPermission = $this->roleCheckService->userHasRole($allowedRoles);

            if (!$hasPermission) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مسموح لك بعرض خدمات هذا المشروع'
                ], 403);
            }

            $services = $project->services->map(function ($service) use ($project) {
                $participantsCount = ProjectServiceUser::where('project_id', $project->id)
                    ->where('service_id', $service->id)
                    ->count();

                $deliveredParticipantsCount = ProjectServiceUser::where('project_id', $project->id)
                    ->where('service_id', $service->id)
                    ->whereNotNull('delivered_at')
                    ->count();

                $totalTasks = \App\Models\TaskUser::whereHas('task', function ($query) use ($project, $service) {
                    $query->where('project_id', $project->id)
                        ->where('service_id', $service->id);
                })->count();

                $completedTasks = \App\Models\TaskUser::where('status', 'completed')
                    ->whereHas('task', function ($query) use ($project, $service) {
                        $query->where('project_id', $project->id)
                            ->where('service_id', $service->id);
                    })->count();

                $totalTemplateTasks = \App\Models\TemplateTaskUser::where('project_id', $project->id)
                    ->whereHas('templateTask.template', function ($query) use ($service) {
                        $query->where('service_id', $service->id);
                    })->count();

                $completedTemplateTasks = \App\Models\TemplateTaskUser::where('project_id', $project->id)
                    ->where('status', 'completed')
                    ->whereHas('templateTask.template', function ($query) use ($service) {
                        $query->where('service_id', $service->id);
                    })->count();

                $totalTasks += $totalTemplateTasks;
                $completedTasks += $completedTemplateTasks;

                $deliveryStatus = 'not_delivered'; // افتراضياً: لم يتم التسليم
                $deliveryStatusText = '⏳ لم يتم التسليم';
                $deliveryStatusClass = 'warning';

                $participants = ProjectServiceUser::where('project_id', $project->id)
                    ->where('service_id', $service->id)
                    ->with('user')
                    ->get();

                if ($participants->isNotEmpty()) {
                    $allDelivered = $participants->every(fn($p) => !is_null($p->delivered_at));

                    if ($allDelivered) {
                        $latestDelivery = $participants->max('delivered_at');
                        $earliestDeadline = $participants->min('deadline');

                        if ($earliestDeadline && $latestDelivery) {
                            if ($latestDelivery <= $earliestDeadline) {
                                $deliveryStatus = 'before_deadline';
                                $deliveryStatusText = '✅ تم التسليم قبل الموعد';
                                $deliveryStatusClass = 'success';
                            } else {
                                $deliveryStatus = 'after_deadline';
                                $deliveryStatusText = '⚠️ تم التسليم بعد الموعد';
                                $deliveryStatusClass = 'danger';
                            }
                        } else {
                            $deliveryStatus = 'delivered';
                            $deliveryStatusText = '✅ تم التسليم';
                            $deliveryStatusClass = 'success';
                        }
                    } else {
                        $deliveryStatus = 'not_delivered';
                        $deliveryStatusText = '⏳ لم يتم التسليم';
                        $deliveryStatusClass = 'warning';
                    }
                }

                $participantsData = $participants->map(function ($participant) use ($project, $service) {
                    $hasDelivered = !is_null($participant->delivered_at);
                    $isLate = false;

                    if ($hasDelivered && $participant->deadline) {
                        $isLate = $participant->delivered_at > $participant->deadline;
                    }

                    $deliveryStatus = 'not_delivered';
                    $deliveryStatusText = 'لم يسلم';
                    $deliveryStatusIcon = '⏳';

                    if ($hasDelivered) {
                        if ($isLate) {
                            $deliveryStatus = 'delivered_late';
                            $deliveryStatusText = 'سلم متأخراً';
                            $deliveryStatusIcon = '⚠️';
                        } else {
                            $deliveryStatus = 'delivered_on_time';
                            $deliveryStatusText = 'سلم في الموعد';
                            $deliveryStatusIcon = '✅';
                        }
                    }

                    $userTasks = \App\Models\TaskUser::where('user_id', $participant->user_id)
                        ->whereHas('task', function ($query) use ($project, $service) {
                            $query->where('project_id', $project->id)
                                ->where('service_id', $service->id);
                        })
                        ->get();

                    $userTemplateTasks = \App\Models\TemplateTaskUser::where('user_id', $participant->user_id)
                        ->where('project_id', $project->id)
                        ->whereHas('templateTask.template', function ($query) use ($service) {
                            $query->where('service_id', $service->id);
                        })
                        ->get();

                    $allUserTasks = $userTasks->concat($userTemplateTasks);

                    $totalTasks = $allUserTasks->count();
                    $completedTasks = $allUserTasks->where('status', 'completed')->count();

                    $now = now();
                    $lateTasks = $allUserTasks->filter(function ($task) use ($now) {
                        if (!$task->deadline) {
                            return false;
                        }

                        if ($task->status === 'completed') {
                            $completedAt = $task->completed_at ?? $task->updated_at;
                            return $completedAt && $completedAt > $task->deadline;
                        }

                        return $now->greaterThan($task->deadline);
                    })->count();

                    return [
                        'id' => $participant->user_id,
                        'service_id' => $service->id,
                        'name' => $participant->user->name ?? 'غير محدد',
                        'employee_id' => $participant->user->employee_id ?? null,
                        'delivered' => $hasDelivered,
                        'delivered_at' => $participant->delivered_at ? $participant->delivered_at->format('Y-m-d H:i') : null,
                        'deadline' => $participant->deadline ? $participant->deadline->format('Y-m-d') : null,
                        'is_late' => $isLate,
                        'delivery_status' => $deliveryStatus,
                        'delivery_status_text' => $deliveryStatusText,
                        'delivery_status_icon' => $deliveryStatusIcon,
                        'total_tasks' => $totalTasks,
                        'completed_tasks' => $completedTasks,
                        'late_tasks' => $lateTasks,
                    ];
                });

                $calculatedProgress = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'status' => $service->pivot->service_status ?? 'لم تبدأ',
                    'progress' => $calculatedProgress,
                    'participants_count' => $participantsCount,
                    'delivered_participants_count' => $deliveredParticipantsCount,
                    'total_tasks' => $totalTasks,
                    'completed_tasks' => $completedTasks,
                    'delivery_status' => $deliveryStatus,
                    'delivery_status_text' => $deliveryStatusText,
                    'delivery_status_class' => $deliveryStatusClass,
                    'participants' => $participantsData,
                ];
            });

            return response()->json([
                'success' => true,
                'services' => $services
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching project services', [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في تحميل الخدمات: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getProjectDetailsForSidebar($projectId)
    {
        try {
            $user = Auth::user();

            $allowedRoles = ['company_manager', 'project_manager', 'operations_manager', 'general_reviewer', 'operation_assistant'];
            $hasPermission = $this->roleCheckService->userHasRole($allowedRoles);

            if (!$hasPermission) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مسموح لك بعرض تفاصيل هذا المشروع'
                ], 403);
            }

            $project = Project::with(['client', 'services'])->findOrFail($projectId);

            $services = $project->services->map(function ($service) use ($project) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'status' => $service->pivot->service_status ?? 'لم تبدأ',
                    'progress' => $service->pivot->progress_percentage ?? 0,
                ];
            });

            $participants = ProjectServiceUser::where('project_id', $project->id)
                ->with(['user', 'role', 'service'])
                ->get()
                ->groupBy('user_id')
                ->map(function ($userServices) {
                    $firstService = $userServices->first();
                    return [
                        'user_id' => $firstService->user->id,
                        'user_name' => $firstService->user->name,
                        'services' => $userServices->map(function ($service) {
                            return [
                                'service_id' => $service->service_id,
                                'service_name' => $service->service->name,
                                'role_name' => $service->role ? $service->role->name : 'غير محدد',
                            ];
                        })->values()
                    ];
                })->values();

            // جلب التعديلات
            $revisions = TaskRevision::where('project_id', $project->id)
                ->with(['creator', 'service', 'assignedUser', 'responsibleUser'])
                ->latest()
                ->limit(50)
                ->get()
                ->map(function ($revision) {
                    return [
                        'id' => $revision->id,
                        'revision_code' => $revision->revision_code,
                        'title' => $revision->title,
                        'description' => $revision->description,
                        'status' => $revision->status,
                        'status_text' => $revision->status_text,
                        'status_color' => $revision->status_color,
                        'source' => $revision->revision_source,
                        'source_text' => $revision->revision_source_text,
                        'source_color' => $revision->revision_source_color,
                        'service_name' => $revision->service ? $revision->service->name : 'عام',
                        'creator_name' => $revision->creator ? $revision->creator->name : 'غير معروف',
                        'assigned_to_name' => $revision->assignedUser ? $revision->assignedUser->name : null,
                        'responsible_name' => $revision->responsibleUser ? $revision->responsibleUser->name : null,
                        'created_at' => $revision->created_at->format('Y-m-d H:i'),
                        'created_at_diff' => $revision->created_at->diffForHumans(),
                    ];
                });

            return response()->json([
                'success' => true,
                'project' => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'code' => $project->code,
                    'description' => $project->description,
                ],
                'services' => $services,
                'participants' => $participants,
                'revisions' => $revisions
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching project details for sidebar', [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في تحميل تفاصيل المشروع'
            ], 500);
        }
    }
}
