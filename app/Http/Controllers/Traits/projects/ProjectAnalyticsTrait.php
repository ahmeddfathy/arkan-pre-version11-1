<?php

namespace App\Http\Controllers\Traits\Projects;

use App\Models\Project;
use App\Models\User;
use App\Models\ProjectServiceUser;
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

    /**
     * عرض صفحة إحصائيات المشروع
     */
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

        // تسجيل نشاط دخول صفحة إحصائيات المشروع
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

        // إحصائيات التعديلات للمشروع (مقسمة حسب النوع: مهام/مشاريع)
        $user = Auth::user();
        $isAdmin = $this->authorizationService->isAdmin();

        $revisionStats = $this->revisionStatsService->getProjectRevisionStats($project->id, $isAdmin, $user->id);
        $revisionsByCategory = $this->revisionStatsService->getRevisionsByCategory($project->id, $isAdmin, $user->id);
        $latestRevisions = $this->revisionStatsService->getProjectLatestRevisions($project->id, $isAdmin, $user->id);
        $pendingRevisions = $this->revisionStatsService->getProjectPendingRevisions($project->id, $isAdmin, $user->id);
        $urgentRevisions = $this->revisionStatsService->getProjectUrgentRevisions($project->id, $isAdmin, $user->id);

        // إحصائيات الملفات والوقت العامة
        $attachmentStats = $this->revisionStatsService->getAttachmentStats($isAdmin, [$user->id]);
        $averageReviewTime = $this->revisionStatsService->getAverageReviewTime();

        // ✅ آخر الاجتماعات المتعلقة بالمشروع
        $projectMeetings = DB::table('meetings')
            ->where('project_id', $project->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // ✅ إحصائيات نقل التعديلات للمشروع
        $projectUserIds = DB::table('project_service_user')
            ->where('project_id', $project->id)
            ->pluck('user_id')
            ->toArray();

        $projectRevisionTransferStats = $this->revisionStatsService->getRevisionTransferStats($projectUserIds, null);

        // ✅ إحصائيات الأخطاء للمشروع
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

    /**
     * عرض إحصائيات أداء موظف في مشروع معين
     */
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

        // تسجيل نشاط دخول صفحة تحليلات الموظف
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

        // إحصائيات التعديلات للموظف في المشروع
        $revisionStats = $this->revisionStatsService->getGeneralRevisionStats(false, [$userId]);
        $latestRevisions = $this->revisionStatsService->getLatestRevisions(false, [$userId]);
        $pendingRevisions = $this->revisionStatsService->getPendingRevisions(false, [$userId]);
        $urgentRevisions = $this->revisionStatsService->getUrgentRevisions(false, [$userId]);
        $employeeAttachmentStats = $this->revisionStatsService->getAttachmentStats(false, [$userId]);
        $averageReviewTime = $this->revisionStatsService->getAverageReviewTime();

        // ✅ اجتماعات المشروع للموظف
        $employeeProjectMeetings = DB::table('meetings')
            ->where('project_id', $project->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // ✅ إحصائيات نقل التعديلات للموظف
        $employeeRevisionTransferStats = $this->revisionStatsService->getRevisionTransferStats($userId, null);

        // إضافة الـ revisionsByCategory
        $revisionsByCategory = $this->revisionStatsService->getRevisionsByCategory(null, false, [$userId]);

        // ✅ إحصائيات الأخطاء للموظف في المشروع
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

    /**
     * عرض صفحة إحصائيات الخدمات
     */
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

        // تسجيل نشاط دخول صفحة إحصائيات الخدمات
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

        // إحصائيات التعديلات للمشروع (مقسمة حسب النوع: مهام/مشاريع)
        $user = Auth::user();
        $isAdmin = $this->authorizationService->isAdmin();

        $revisionStats = $this->revisionStatsService->getProjectRevisionStats($project->id, $isAdmin, $user->id);
        $revisionsByCategory = $this->revisionStatsService->getRevisionsByCategory($project->id, $isAdmin, $user->id);
        $latestRevisions = $this->revisionStatsService->getProjectLatestRevisions($project->id, $isAdmin, $user->id);
        $pendingRevisions = $this->revisionStatsService->getProjectPendingRevisions($project->id, $isAdmin, $user->id);
        $urgentRevisions = $this->revisionStatsService->getProjectUrgentRevisions($project->id, $isAdmin, $user->id);

        // إحصائيات الملفات والوقت العامة
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

    /**
     * جلب إحصائيات الخدمات
     */
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

    /**
     * تحديث تقدم الخدمة
     */
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

    /**
     * جلب تاريخ تقدم الخدمة
     */
    public function getServiceProgressHistory(Project $project, $serviceId)
    {
        try {
            $historyData = $this->serviceProgressService->getServiceProgressHistory($project, $serviceId);
            return response()->json($historyData);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], $e->getMessage() === 'الخدمة غير موجودة في هذا المشروع' ? 404 : 403);
        }
    }

    /**
     * جلب تنبيهات الخدمة
     */
    public function getServiceAlerts(Project $project)
    {
        try {
            $alertsData = $this->serviceProgressService->getServiceAlerts($project);
            return response()->json($alertsData);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        }
    }

    /**
     * جلب اقتراح الفريق الذكي
     */
    public function getSmartTeamSuggestion(Request $request, Project $project, $serviceId)
    {
        // فحص صلاحيات عرض اقتراح الفريق - للـ operation assistant فقط
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

    /**
     * Get project details for sidebar
     */
    public function getSidebarDetails(Project $project)
    {
        $result = $this->sidebarService->getSidebarDetails($project);
        return response()->json($result, $result['status_code']);
    }

    /**
     * Get project participants for revisions
     */
    public function getProjectParticipants(Project $project)
    {
        $result = $this->sidebarService->getProjectParticipants($project);
        return response()->json($result, $result['status_code']);
    }

    /**
     * Get project services for simple overview
     */
    public function getProjectServicesSimple($projectId)
    {
        try {
            $project = Project::with(['services'])->findOrFail($projectId);

            // Check if user has access to this project
            $user = Auth::user();
            $isAdmin = $this->roleCheckService->userHasRole(['hr', 'company_manager', 'project_manager', 'sales_employee', 'operation_assistant']);

            if (!$isAdmin) {
                $userProjectIds = DB::table('project_service_user')
                    ->where('user_id', $user->id)
                    ->pluck('project_id')
                    ->toArray();

                if (!in_array($project->id, $userProjectIds)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'غير مسموح لك بعرض خدمات هذا المشروع'
                    ], 403);
                }
            }

            $services = $project->services->map(function($service) use ($project) {
                // حساب عدد المشاركين الكلي
                $participantsCount = ProjectServiceUser::where('project_id', $project->id)
                    ->where('service_id', $service->id)
                    ->count();

                // حساب عدد المشاركين الذين سلموا
                $deliveredParticipantsCount = ProjectServiceUser::where('project_id', $project->id)
                    ->where('service_id', $service->id)
                    ->whereNotNull('delivered_at')
                    ->count();

                // حساب عدد المهام الكلي والمكتملة لهذه الخدمة في المشروع
                $totalTasks = \App\Models\TaskUser::whereHas('task', function($query) use ($project, $service) {
                    $query->where('project_id', $project->id)
                          ->where('service_id', $service->id);
                })->count();

                $completedTasks = \App\Models\TaskUser::where('status', 'completed')
                    ->whereHas('task', function($query) use ($project, $service) {
                        $query->where('project_id', $project->id)
                              ->where('service_id', $service->id);
                    })->count();

                // إضافة مهام القوالب
                // ملاحظة: service_id موجود في TaskTemplate وليس في TemplateTask
                // يجب الوصول إليه عبر: TemplateTaskUser -> TemplateTask -> TaskTemplate
                $totalTemplateTasks = \App\Models\TemplateTaskUser::where('project_id', $project->id)
                    ->whereHas('templateTask.template', function($query) use ($service) {
                        $query->where('service_id', $service->id);
                    })->count();

                $completedTemplateTasks = \App\Models\TemplateTaskUser::where('project_id', $project->id)
                    ->where('status', 'completed')
                    ->whereHas('templateTask.template', function($query) use ($service) {
                        $query->where('service_id', $service->id);
                    })->count();

                $totalTasks += $totalTemplateTasks;
                $completedTasks += $completedTemplateTasks;

                // حساب حالة التسليم بناءً على الديدلاين
                $deliveryStatus = 'not_delivered'; // افتراضياً: لم يتم التسليم
                $deliveryStatusText = '⏳ لم يتم التسليم';
                $deliveryStatusClass = 'warning';

                // جلب معلومات الديدلاين والتسليم مع بيانات المشاركين
                $participants = ProjectServiceUser::where('project_id', $project->id)
                    ->where('service_id', $service->id)
                    ->with('user')
                    ->get();

                if ($participants->isNotEmpty()) {
                    $allDelivered = $participants->every(fn($p) => !is_null($p->delivered_at));

                    if ($allDelivered) {
                        // جميع المشاركين سلموا، نتحقق من الديدلاين
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
                        // بعض المشاركين لم يسلموا بعد
                        $deliveryStatus = 'not_delivered';
                        $deliveryStatusText = '⏳ لم يتم التسليم';
                        $deliveryStatusClass = 'warning';
                    }
                }

                // تجهيز بيانات المشاركين مع حالاتهم ومعلومات المهام
                $participantsData = $participants->map(function($participant) use ($project, $service) {
                    $hasDelivered = !is_null($participant->delivered_at);
                    $isLate = false;

                    if ($hasDelivered && $participant->deadline) {
                        $isLate = $participant->delivered_at > $participant->deadline;
                    }

                    // تحديد حالة التسليم
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

                    // حساب مهام المشارك في هذه الخدمة
                    // المهام العادية
                    $userTasks = \App\Models\TaskUser::where('user_id', $participant->user_id)
                        ->whereHas('task', function($query) use ($project, $service) {
                            $query->where('project_id', $project->id)
                                  ->where('service_id', $service->id);
                        })
                        ->get();

                    // مهام القوالب
                    $userTemplateTasks = \App\Models\TemplateTaskUser::where('user_id', $participant->user_id)
                        ->where('project_id', $project->id)
                        ->whereHas('templateTask.template', function($query) use ($service) {
                            $query->where('service_id', $service->id);
                        })
                        ->get();

                    // دمج كل المهام
                    $allUserTasks = $userTasks->concat($userTemplateTasks);

                    // حساب المهام
                    $totalTasks = $allUserTasks->count();
                    $completedTasks = $allUserTasks->where('status', 'completed')->count();

                    // حساب المهام المتأخرة (أي مهمة عدت الديدلاين بتاعها)
                    $now = now();
                    $lateTasks = $allUserTasks->filter(function($task) use ($now) {
                        // لو مفيش ديدلاين، مش متأخرة
                        if (!$task->deadline) {
                            return false;
                        }

                        // لو المهمة مكتملة، نشوف اتسلمت بعد الديدلاين ولا لأ
                        if ($task->status === 'completed') {
                            $completedAt = $task->completed_at ?? $task->updated_at;
                            return $completedAt && $completedAt > $task->deadline;
                        }

                        // لو المهمة لسه شغالة والديدلاين عدى = متأخرة
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
                        // معلومات المهام
                        'total_tasks' => $totalTasks,
                        'completed_tasks' => $completedTasks,
                        'late_tasks' => $lateTasks,
                    ];
                });

                // حساب الـ Progress من المهام المكتملة الفعلية
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

    /**
     * Get complete project details with participants and their tasks
     */
    public function getProjectDetailsForSidebar($projectId)
    {
        try {
            $user = Auth::user();
            $isAdmin = $this->roleCheckService->userHasRole(['hr', 'company_manager', 'project_manager', 'sales_employee', 'operation_assistant']);

            $project = Project::with(['client', 'services'])->findOrFail($projectId);

            // Check if user has access to this project
            if (!$isAdmin) {
                $userProjectIds = DB::table('project_service_user')
                    ->where('user_id', $user->id)
                    ->pluck('project_id')
                    ->toArray();

                if (!in_array($project->id, $userProjectIds)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'غير مسموح لك بعرض تفاصيل هذا المشروع'
                    ], 403);
                }
            }

            // Get all services
            $services = $project->services->map(function($service) use ($project) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'status' => $service->pivot->service_status ?? 'لم تبدأ',
                    'progress' => $service->pivot->progress_percentage ?? 0,
                ];
            });

            // Get all participants with their details
            $participants = ProjectServiceUser::where('project_id', $project->id)
                ->with(['user', 'role', 'service'])
                ->get()
                ->groupBy('user_id')
                ->map(function($userServices) {
                    $firstService = $userServices->first();
                    return [
                        'user_id' => $firstService->user->id,
                        'user_name' => $firstService->user->name,
                        'services' => $userServices->map(function($service) {
                            return [
                                'service_id' => $service->service_id,
                                'service_name' => $service->service->name,
                                'role_name' => $service->role ? $service->role->name : 'غير محدد',
                            ];
                        })->values()
                    ];
                })->values();

            return response()->json([
                'success' => true,
                'project' => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'code' => $project->code,
                    'description' => $project->description,
                ],
                'services' => $services,
                'participants' => $participants
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
