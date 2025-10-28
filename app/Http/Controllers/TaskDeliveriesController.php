<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\TaskUser;
use App\Models\TemplateTaskUser;
use App\Services\Tasks\TaskApprovalService;
use App\Services\ProjectPointsValidationService;
use App\Traits\HasNTPTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TaskDeliveriesController extends Controller
{
    use HasNTPTime;

    protected $taskApprovalService;
    protected $pointsValidationService;

    protected $taskDeliveryNotificationService;

    public function __construct(
        TaskApprovalService $taskApprovalService,
        ProjectPointsValidationService $pointsValidationService,
        \App\Services\Notifications\TaskDeliveryNotificationService $taskDeliveryNotificationService
    ) {
        $this->taskApprovalService = $taskApprovalService;
        $this->pointsValidationService = $pointsValidationService;
        $this->taskDeliveryNotificationService = $taskDeliveryNotificationService;
    }

    /**
     * عرض المهام المنتظرة للموافقة
     */
    public function index(Request $request)
    {
        // تسجيل النشاط - دخول صفحة موافقات المهام
        if (\Illuminate\Support\Facades\Auth::check()) {
            activity()
                ->causedBy(\Illuminate\Support\Facades\Auth::user())
                ->withProperties([
                    'action_type' => 'view_index',
                    'page' => 'task_approval_index',
                    'viewed_at' => now()->toDateTimeString(),
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip()
                ])
                ->log('دخل على صفحة موافقات المهام');
        }

        // جلب جميع المهام
        $allTasksData = $this->taskApprovalService->getAllTasksForApproval();

        // الحصول على المهام المنتظرة للموافقة للتوافق مع الكود الحالي
        $pendingTasks = $this->taskApprovalService->getPendingApprovalTasks();

        // جلب المشاريع مع الإحصائيات
        $projectsData = $this->taskApprovalService->getProjectsWithTaskStats();

        // جلب المهام المعتمدة مع الفلترة
        $approvedTasks = $this->getApprovedTasksWithFilters($request);

        // حساب الإحصائيات
        $approvedToday = TaskUser::where('approved_by', Auth::id())
            ->whereDate('approved_at', today())
            ->count() +
            TemplateTaskUser::where('approved_by', Auth::id())
                ->whereDate('approved_at', today())
                ->count();

        $approvedThisWeek = TaskUser::where('approved_by', Auth::id())
            ->whereBetween('approved_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count() +
            TemplateTaskUser::where('approved_by', Auth::id())
                ->whereBetween('approved_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->count();

        $stats = [
            'pending_approval' => $allTasksData['pending_approval'],
            'approved_today' => $approvedToday,
            'approved_this_week' => $approvedThisWeek,
            'total_tasks' => $allTasksData['total_tasks'],
            'pending_regular_tasks' => $pendingTasks['regular_tasks']->count(),
            'pending_template_tasks' => $pendingTasks['template_tasks']->count(),
            'approved_regular_tasks' => $approvedTasks['regular_tasks']->count(),
            'approved_template_tasks' => $approvedTasks['template_tasks']->count()
        ];

        return view('task-deliveries.index', compact('allTasksData', 'pendingTasks', 'projectsData', 'approvedTasks', 'stats'));
    }

    /**
     * موافقة على مهمة عادية
     */
    public function approveRegularTask(Request $request, TaskUser $taskUser)
    {
        // التحقق من الصلاحيات
        if (!$this->taskApprovalService->canApproveTask($taskUser)) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بالموافقة على هذه المهمة'
            ], 403);
        }

        $request->validate([
            'awarded_points' => 'nullable|integer|min:0|max:1000',
            'approval_note' => 'nullable|string|max:500'
        ]);

        $result = $this->taskApprovalService->approveRegularTask(
            $taskUser,
            $request->input('awarded_points'),
            $request->input('approval_note')
        );

        if ($result['success']) {
            return redirect()->route('task-deliveries.index')
                ->with('success', $result['message']);
        } else {
            return redirect()->back()
                ->with('error', $result['message'])
                ->withInput();
        }
    }

    /**
     * موافقة على مهمة تمبليت
     */
    public function approveTemplateTask(Request $request, TemplateTaskUser $templateTaskUser)
    {
        // التحقق من الصلاحيات
        if (!$this->taskApprovalService->canApproveTask($templateTaskUser)) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بالموافقة على هذه المهمة'
            ], 403);
        }

        $request->validate([
            'awarded_points' => 'nullable|integer|min:0|max:1000',
            'approval_note' => 'nullable|string|max:500'
        ]);

        $result = $this->taskApprovalService->approveTemplateTask(
            $templateTaskUser,
            $request->input('awarded_points'),
            $request->input('approval_note')
        );

        if ($result['success']) {
            return redirect()->route('task-deliveries.index')
                ->with('success', $result['message']);
        } else {
            return redirect()->back()
                ->with('error', $result['message'])
                ->withInput();
        }
    }

    /**
     * رفض موافقة مهمة
     */
    public function rejectTask(Request $request)
    {
        $request->validate([
            'task_type' => 'required|in:regular,template',
            'task_user_id' => 'required|integer',
            'rejection_reason' => 'nullable|string|max:500'
        ]);

        if ($request->task_type === 'regular') {
            // البحث المطور عن TaskUser
            $taskUser = TaskUser::find($request->task_user_id);
            if (!$taskUser) {
                $task = \App\Models\Task::find($request->task_user_id);
                if ($task) {
                    $currentUser = \Illuminate\Support\Facades\Auth::user();
                    $taskUser = TaskUser::where('task_id', $request->task_user_id)
                        ->where('user_id', $currentUser->id)
                        ->first();

                    if (!$taskUser) {
                        $userRoles = $currentUser->roles->pluck('name')->toArray();
                        $allowedRoles = ['hr', 'project_manager', 'company_manager', 'operations_manager'];
                        $isHrOrAdmin = !empty(array_intersect($allowedRoles, $userRoles));
                        if ($isHrOrAdmin) {
                            $taskUser = TaskUser::where('task_id', $request->task_user_id)->first();
                        }
                    }
                }

                if (!$taskUser) {
                    return response()->json([
                        'success' => false,
                        'message' => 'المهمة غير موجودة أو غير مُعيَّنة لك'
                    ], 404);
                }
            }
        } else {
            $taskUser = TemplateTaskUser::findOrFail($request->task_user_id);
        }

        // التحقق من الصلاحيات
        if (!$this->taskApprovalService->canApproveTask($taskUser)) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك برفض هذه المهمة'
            ], 403);
        }

        $result = $this->taskApprovalService->rejectTaskApproval(
            $taskUser,
            $request->input('rejection_reason')
        );

        return response()->json($result);
    }

    /**
     * الحصول على تفاصيل مهمة للموافقة
     */
    public function getTaskDetails(Request $request)
    {
        $request->validate([
            'task_type' => 'required|in:regular,template',
            'task_user_id' => 'required|integer'
        ]);

        // تسجيل النشاط - عرض تفاصيل مهمة للموافقة
        if (\Illuminate\Support\Facades\Auth::check()) {
            activity()
                ->causedBy(\Illuminate\Support\Facades\Auth::user())
                ->withProperties([
                    'task_type' => $request->task_type,
                    'task_user_id' => $request->task_user_id,
                    'action_type' => 'view_task_details',
                    'viewed_at' => now()->toDateTimeString(),
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip()
                ])
                ->log('شاهد تفاصيل مهمة للموافقة');
        }

        if ($request->task_type === 'regular') {
            // البحث المطور عن TaskUser
            $taskUser = TaskUser::with(['task', 'user'])->find($request->task_user_id);
            if (!$taskUser) {
                $task = \App\Models\Task::find($request->task_user_id);
                if ($task) {
                    $currentUser = \Illuminate\Support\Facades\Auth::user();
                    $taskUser = TaskUser::with(['task', 'user'])
                        ->where('task_id', $request->task_user_id)
                        ->where('user_id', $currentUser->id)
                        ->first();

                    if (!$taskUser) {
                        $userRoles = $currentUser->roles->pluck('name')->toArray();
                        $allowedRoles = ['hr', 'project_manager', 'company_manager', 'operations_manager'];
                        $isHrOrAdmin = !empty(array_intersect($allowedRoles, $userRoles));
                        if ($isHrOrAdmin) {
                            $taskUser = TaskUser::with(['task', 'user'])
                                ->where('task_id', $request->task_user_id)
                                ->first();
                        }
                    }
                }

                if (!$taskUser) {
                    return response()->json([
                        'success' => false,
                        'message' => 'المهمة غير موجودة أو غير مُعيَّنة لك'
                    ], 404);
                }
            }
            $originalPoints = $taskUser->task->points ?? 0;
            $taskName = $taskUser->task->name;
        } else {
            $taskUser = TemplateTaskUser::with(['templateTask.template', 'user'])
                ->findOrFail($request->task_user_id);
            $originalPoints = $taskUser->templateTask->points ?? 0;
            $taskName = $taskUser->templateTask->name;
        }

        // التحقق من الصلاحيات
        if (!$this->taskApprovalService->canApproveTask($taskUser)) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بعرض هذه المهمة'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'task_user' => $taskUser,
                'task_name' => $taskName,
                'original_points' => $originalPoints,
                'current_points' => $taskUser->awarded_points ?? $originalPoints,
                'user_name' => $taskUser->user->name,
                'completed_at' => $taskUser->completed_date ?? $taskUser->completed_at,
                'can_approve' => $taskUser->canBeApproved()
            ]
        ]);
    }

    /**
     * الحصول على إحصائيات الموافقة
     */
    public function getApprovalStats()
    {
        $user = Auth::user();
        $pendingTasks = $this->taskApprovalService->getPendingApprovalTasks($user);

        // إحصائيات إضافية
        $approvedToday = TaskUser::where('approved_by', $user->id)
            ->whereDate('approved_at', today())
            ->count() +
            TemplateTaskUser::where('approved_by', $user->id)
                ->whereDate('approved_at', today())
                ->count();

        $approvedThisWeek = TaskUser::where('approved_by', $user->id)
            ->whereBetween('approved_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count() +
            TemplateTaskUser::where('approved_by', $user->id)
                ->whereBetween('approved_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'pending_approval' => $pendingTasks['total_pending'],
                'approved_today' => $approvedToday,
                'approved_this_week' => $approvedThisWeek,
                'pending_regular_tasks' => $pendingTasks['regular_tasks']->count(),
                'pending_template_tasks' => $pendingTasks['template_tasks']->count()
            ]
        ]);
    }

    /**
     * تحديث نقاط مهمة موافق عليها
     */
    public function updateTaskPoints(Request $request)
    {
        $request->validate([
            'task_type' => 'required|in:regular,template',
            'task_user_id' => 'required|integer',
            'new_points' => 'required|integer|min:0|max:1000',
            'update_reason' => 'nullable|string|max:500'
        ]);

        if ($request->task_type === 'regular') {
            $taskUser = TaskUser::findOrFail($request->task_user_id);
        } else {
            $taskUser = TemplateTaskUser::findOrFail($request->task_user_id);
        }

        // التحقق من الصلاحيات والحالة
        if (!$this->taskApprovalService->canApproveTask($taskUser)) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بتعديل نقاط هذه المهمة'
            ], 403);
        }

        if (!$taskUser->isApproved()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تعديل نقاط مهمة غير موافق عليها'
            ], 400);
        }

        try {
            $oldPoints = $taskUser->awarded_points;
            $newPoints = $request->new_points;
            $pointsDifference = $newPoints - $oldPoints;

            // التحقق من حدود النقاط قبل التحديث
            if ($pointsDifference > 0) {
                $project = null;
                $service = null;

                if ($request->task_type === 'regular') {
                    $task = $taskUser->task;
                    if ($task) {
                        $project = $task->project;
                        $service = $task->service;
                    }
                } else {
                    $templateTask = $taskUser->templateTask;
                    if ($templateTask && $templateTask->template) {
                        $project = \App\Models\Project::find($taskUser->project_id);
                        $service = \App\Models\CompanyService::find($templateTask->template->service_id);
                    }
                }

                if ($project && $service && $service->hasMaxPointsLimit()) {
                    $pointsValidation = $this->pointsValidationService->canAddTaskToProject($project, $service, $pointsDifference);

                    if (!$pointsValidation['can_add']) {
                        return response()->json([
                            'success' => false,
                            'message' => $pointsValidation['message']
                        ], 400);
                    }
                }
            }

            // تحديث النقاط في قاعدة البيانات
            $taskUser->update([
                'awarded_points' => $newPoints,
                'approval_note' => $request->update_reason ?
                    $taskUser->approval_note . "\n[تحديث النقاط: " . now()->format('Y-m-d H:i') . "] " . $request->update_reason :
                    $taskUser->approval_note
            ]);

            // تحديث نقاط المستخدم
            if ($pointsDifference != 0) {
                $season = \App\Models\Season::where('is_active', true)->first();
                if ($season) {
                    $userSeasonPoint = \App\Models\UserSeasonPoint::where('user_id', $taskUser->user_id)
                        ->where('season_id', $season->id)
                        ->first();

                    if ($userSeasonPoint) {
                        $userSeasonPoint->update([
                            'total_points' => max(0, $userSeasonPoint->total_points + $pointsDifference)
                        ]);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث النقاط بنجاح',
                'data' => [
                    'old_points' => $oldPoints,
                    'new_points' => $newPoints,
                    'difference' => $pointsDifference
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث النقاط: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * جلب المهام المعتمدة مع الفلترة
     */
    private function getApprovedTasksWithFilters($request)
    {
        $user = Auth::user();

                // تحديد التواريخ للفلترة
        $filterType = $request->get('filter_type', 'all'); // all, today, week, month, custom

        // ضبط التواريخ حسب نوع الفلتر
        switch ($filterType) {
            case 'today':
                $dateFrom = $dateTo = today()->format('Y-m-d');
                break;
            case 'week':
                $dateFrom = now()->startOfWeek()->format('Y-m-d');
                $dateTo = now()->endOfWeek()->format('Y-m-d');
                break;
            case 'month':
                $dateFrom = now()->startOfMonth()->format('Y-m-d');
                $dateTo = now()->endOfMonth()->format('Y-m-d');
                break;
            case 'custom':
                // استخدام الشهور المحددة
                $monthFrom = $request->get('month_from', now()->month);
                $yearFrom = $request->get('year_from', now()->year);
                $monthTo = $request->get('month_to', now()->month);
                $yearTo = $request->get('year_to', now()->year);

                $dateFrom = Carbon::createFromDate($yearFrom, $monthFrom, 1)->startOfMonth()->format('Y-m-d');
                $dateTo = Carbon::createFromDate($yearTo, $monthTo, 1)->endOfMonth()->format('Y-m-d');
                break;
            case 'all':
            default:
                // للحصول على جميع المهام، نستخدم تاريخ بعيد في الماضي والمستقبل
                $dateFrom = '2020-01-01';
                $dateTo = '2030-12-31';
                break;
        }

        // استعلام المهام العادية المعتمدة
        $regularTasksQuery = TaskUser::with(['task.project', 'task.createdBy', 'user', 'approvedBy'])
            ->where('is_approved', true)
            ->whereNotNull('approved_at')
            ->whereBetween('approved_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);

        // استعلام مهام القوالب المعتمدة
        $templateTasksQuery = TemplateTaskUser::with(['templateTask.template', 'user', 'assignedBy', 'project', 'approvedBy'])
            ->where('is_approved', true)
            ->whereNotNull('approved_at')
            ->whereBetween('approved_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);

        // إذا لم يكن من الأدوار المسموح لها برؤية كل المهام
        $userRoles = $user->roles->pluck('name')->toArray();
        $allowedRoles = ['hr', 'project_manager', 'company_manager', 'operations_manager'];
        $isHrOrAdmin = !empty(array_intersect($userRoles, $allowedRoles));
        if (!$isHrOrAdmin) {
            // المهام العادية: المهام التي أنشأها المستخدم أو المهام من المشاريع التي هو مشارك فيها
            $regularTasksQuery->whereHas('task', function($q) use ($user) {
                $q->where(function($subQ) use ($user) {
                    // المهام التي أنشأها المستخدم
                    $subQ->where('created_by', $user->id)
                        // أو المهام من مشاريع هو مشارك فيها
                        ->orWhereHas('project.serviceParticipants', function($participantQ) use ($user) {
                            $participantQ->where('user_id', $user->id);
                        });
                });
            });

            // مهام القوالب: المهام التي أضافها المستخدم أو من مشاريع هو مشارك فيها
            $templateTasksQuery->where(function($q) use ($user) {
                // المهام التي أضافها المستخدم
                $q->where('assigned_by', $user->id)
                    // أو المهام من مشاريع هو مشارك فيها
                    ->orWhereHas('project.serviceParticipants', function($participantQ) use ($user) {
                        $participantQ->where('user_id', $user->id);
                    });
            });
        }

        $regularTasks = $regularTasksQuery->orderBy('approved_at', 'desc')->get();
        $templateTasks = $templateTasksQuery->orderBy('approved_at', 'desc')->get();

        return [
            'regular_tasks' => $regularTasks,
            'template_tasks' => $templateTasks,
            'total_approved' => $regularTasks->count() + $templateTasks->count(),
            'is_hr_or_admin' => $isHrOrAdmin,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'filter_type' => $filterType,
                'month_from' => $request->get('month_from', now()->month),
                'year_from' => $request->get('year_from', now()->year),
                'month_to' => $request->get('month_to', now()->month),
                'year_to' => $request->get('year_to', now()->year)
            ]
        ];
    }

    /**
     * فلترة المهام المعتمدة عبر AJAX
     */
    public function filterApprovedTasks(Request $request)
    {
        $approvedTasks = $this->getApprovedTasksWithFilters($request);

        $html = view('task-approval.partials.approved-tasks', compact('approvedTasks'))->render();

        return response()->json([
            'success' => true,
            'html' => $html,
            'stats' => [
                'approved_regular_tasks' => $approvedTasks['regular_tasks']->count(),
                'approved_template_tasks' => $approvedTasks['template_tasks']->count(),
                'total_approved' => $approvedTasks['total_approved']
            ]
        ]);
    }

    /**
     * جلب مهام مشروع محدد للموافقة
     */
    public function getProjectTasks(Request $request, $projectId)
    {
        try {
            // التحقق من صحة معرف المشروع
            if (!is_numeric($projectId) || $projectId <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'معرف المشروع غير صحيح'
                ], 400);
            }

            $projectTasks = $this->taskApprovalService->getProjectTasks((int)$projectId);

            // تحويل البيانات لتكون متوافقة مع الجدول الموحد
            $tasksData = $projectTasks['all_tasks'];

            return response()->json([
                'success' => true,
                'project' => $projectTasks['project'],
                'tasks' => $tasksData,
                'total_tasks' => $projectTasks['total_tasks'],
                'pending_approval' => $projectTasks['pending_approval'],
                'is_hr_or_admin' => $projectTasks['is_hr_or_admin']
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'المشروع غير موجود'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error fetching project tasks', [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب مهام المشروع'
            ], 500);
        }
    }

    // ======================================
    // نظام الاعتماد الإداري والفني للتاسكات المرتبطة بمشاريع
    // ======================================

    /**
     * إعطاء اعتماد إداري لتاسك عادية
     */
    public function grantAdministrativeApprovalTask(Request $request, TaskUser $taskUser)
    {
        try {
            // التحقق من أن التاسك مرتبطة بمشروع
            if (!$taskUser->isProjectTask()) {
                return response()->json([
                    'success' => false,
                    'message' => 'هذه التاسك غير مرتبطة بمشروع ولا تحتاج اعتماد إداري'
                ], 400);
            }

            // التحقق من أن التاسك مكتملة
            if ($taskUser->status !== 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'يجب أن تكون التاسك مكتملة أولاً'
                ], 400);
            }

            $currentUser = Auth::user();

            // التحقق من الصلاحيات
            if (!$taskUser->canUserApprove($currentUser->id, 'administrative')) {
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك صلاحية إعطاء الاعتماد الإداري لهذه التاسك'
                ], 403);
            }

            // التحقق من عدم وجود اعتماد إداري مسبق
            if ($taskUser->hasAdministrativeApproval()) {
                return response()->json([
                    'success' => false,
                    'message' => 'هذه التاسك معتمدة إدارياً بالفعل'
                ], 400);
            }

            // إعطاء الاعتماد الإداري
            $notes = $request->input('notes');
            $taskUser->grantAdministrativeApproval($currentUser->id, $notes);

            // إرسال إشعار للموظف
            try {
                $this->taskDeliveryNotificationService->notifyTaskApproved(
                    $taskUser->fresh(),
                    $currentUser,
                    'administrative'
                );
            } catch (\Exception $e) {
                Log::warning('Failed to send task approval notification', [
                    'task_user_id' => $taskUser->id,
                    'error' => $e->getMessage()
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'تم إعطاء الاعتماد الإداري بنجاح'
            ]);

        } catch (\Exception $e) {
            Log::error('Error granting administrative approval for task', [
                'task_user_id' => $taskUser->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إعطاء الاعتماد الإداري'
            ], 500);
        }
    }

    /**
     * إعطاء اعتماد فني لتاسك عادية
     */
    public function grantTechnicalApprovalTask(Request $request, TaskUser $taskUser)
    {
        try {
            // التحقق من أن التاسك مرتبطة بمشروع
            if (!$taskUser->isProjectTask()) {
                return response()->json([
                    'success' => false,
                    'message' => 'هذه التاسك غير مرتبطة بمشروع ولا تحتاج اعتماد فني'
                ], 400);
            }

            // التحقق من أن التاسك مكتملة
            if ($taskUser->status !== 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'يجب أن تكون التاسك مكتملة أولاً'
                ], 400);
            }

            $currentUser = Auth::user();

            // التحقق من الصلاحيات
            if (!$taskUser->canUserApprove($currentUser->id, 'technical')) {
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك صلاحية إعطاء الاعتماد الفني لهذه التاسك'
                ], 403);
            }

            // التحقق من عدم وجود اعتماد فني مسبق
            if ($taskUser->hasTechnicalApproval()) {
                return response()->json([
                    'success' => false,
                    'message' => 'هذه التاسك معتمدة فنياً بالفعل'
                ], 400);
            }

            // إعطاء الاعتماد الفني
            $notes = $request->input('notes');
            $taskUser->grantTechnicalApproval($currentUser->id, $notes);

            // إرسال إشعار للموظف
            try {
                $this->taskDeliveryNotificationService->notifyTaskApproved(
                    $taskUser->fresh(),
                    $currentUser,
                    'technical'
                );
            } catch (\Exception $e) {
                Log::warning('Failed to send task approval notification', [
                    'task_user_id' => $taskUser->id,
                    'error' => $e->getMessage()
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'تم إعطاء الاعتماد الفني بنجاح'
            ]);

        } catch (\Exception $e) {
            Log::error('Error granting technical approval for task', [
                'task_user_id' => $taskUser->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إعطاء الاعتماد الفني'
            ], 500);
        }
    }

    /**
     * إلغاء اعتماد إداري لتاسك عادية
     */
    public function revokeAdministrativeApprovalTask(TaskUser $taskUser)
    {
        try {
            $currentUser = Auth::user();

            // التحقق من الصلاحيات
            if (!$taskUser->canUserApprove($currentUser->id, 'administrative')) {
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك صلاحية إلغاء الاعتماد الإداري لهذه التاسك'
                ], 403);
            }

            // إلغاء الاعتماد الإداري
            $taskUser->revokeAdministrativeApproval();

            return response()->json([
                'success' => true,
                'message' => 'تم إلغاء الاعتماد الإداري بنجاح'
            ]);

        } catch (\Exception $e) {
            Log::error('Error revoking administrative approval for task', [
                'task_user_id' => $taskUser->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إلغاء الاعتماد الإداري'
            ], 500);
        }
    }

    /**
     * إلغاء اعتماد فني لتاسك عادية
     */
    public function revokeTechnicalApprovalTask(TaskUser $taskUser)
    {
        try {
            $currentUser = Auth::user();

            // التحقق من الصلاحيات
            if (!$taskUser->canUserApprove($currentUser->id, 'technical')) {
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك صلاحية إلغاء الاعتماد الفني لهذه التاسك'
                ], 403);
            }

            // إلغاء الاعتماد الفني
            $taskUser->revokeTechnicalApproval();

            return response()->json([
                'success' => true,
                'message' => 'تم إلغاء الاعتماد الفني بنجاح'
            ]);

        } catch (\Exception $e) {
            Log::error('Error revoking technical approval for task', [
                'task_user_id' => $taskUser->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إلغاء الاعتماد الفني'
            ], 500);
        }
    }

    /**
     * إعطاء اعتماد إداري لتاسك قالب
     */
    public function grantAdministrativeApprovalTemplate(Request $request, TemplateTaskUser $templateTaskUser)
    {
        try {
            // التحقق من أن التاسك مرتبطة بمشروع
            if (!$templateTaskUser->isProjectTask()) {
                return response()->json([
                    'success' => false,
                    'message' => 'هذه التاسك غير مرتبطة بمشروع ولا تحتاج اعتماد إداري'
                ], 400);
            }

            // التحقق من أن التاسك مكتملة
            if ($templateTaskUser->status !== 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'يجب أن تكون التاسك مكتملة أولاً'
                ], 400);
            }

            $currentUser = Auth::user();

            // التحقق من الصلاحيات
            if (!$templateTaskUser->canUserApprove($currentUser->id, 'administrative')) {
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك صلاحية إعطاء الاعتماد الإداري لهذه التاسك'
                ], 403);
            }

            // التحقق من عدم وجود اعتماد إداري مسبق
            if ($templateTaskUser->hasAdministrativeApproval()) {
                return response()->json([
                    'success' => false,
                    'message' => 'هذه التاسك معتمدة إدارياً بالفعل'
                ], 400);
            }

            // إعطاء الاعتماد الإداري
            $notes = $request->input('notes');
            $templateTaskUser->grantAdministrativeApproval($currentUser->id, $notes);

            // إرسال إشعار للموظف
            try {
                $this->taskDeliveryNotificationService->notifyTaskApproved(
                    $templateTaskUser->fresh(),
                    $currentUser,
                    'administrative'
                );
            } catch (\Exception $e) {
                Log::warning('Failed to send template task approval notification', [
                    'template_task_user_id' => $templateTaskUser->id,
                    'error' => $e->getMessage()
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'تم إعطاء الاعتماد الإداري بنجاح'
            ]);

        } catch (\Exception $e) {
            Log::error('Error granting administrative approval for template task', [
                'template_task_user_id' => $templateTaskUser->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إعطاء الاعتماد الإداري'
            ], 500);
        }
    }

    /**
     * إعطاء اعتماد فني لتاسك قالب
     */
    public function grantTechnicalApprovalTemplate(Request $request, TemplateTaskUser $templateTaskUser)
    {
        try {
            // التحقق من أن التاسك مرتبطة بمشروع
            if (!$templateTaskUser->isProjectTask()) {
                return response()->json([
                    'success' => false,
                    'message' => 'هذه التاسك غير مرتبطة بمشروع ولا تحتاج اعتماد فني'
                ], 400);
            }

            // التحقق من أن التاسك مكتملة
            if ($templateTaskUser->status !== 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'يجب أن تكون التاسك مكتملة أولاً'
                ], 400);
            }

            $currentUser = Auth::user();

            // التحقق من الصلاحيات
            if (!$templateTaskUser->canUserApprove($currentUser->id, 'technical')) {
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك صلاحية إعطاء الاعتماد الفني لهذه التاسك'
                ], 403);
            }

            // التحقق من عدم وجود اعتماد فني مسبق
            if ($templateTaskUser->hasTechnicalApproval()) {
                return response()->json([
                    'success' => false,
                    'message' => 'هذه التاسك معتمدة فنياً بالفعل'
                ], 400);
            }

            // إعطاء الاعتماد الفني
            $notes = $request->input('notes');
            $templateTaskUser->grantTechnicalApproval($currentUser->id, $notes);

            // إرسال إشعار للموظف
            try {
                $this->taskDeliveryNotificationService->notifyTaskApproved(
                    $templateTaskUser->fresh(),
                    $currentUser,
                    'technical'
                );
            } catch (\Exception $e) {
                Log::warning('Failed to send template task approval notification', [
                    'template_task_user_id' => $templateTaskUser->id,
                    'error' => $e->getMessage()
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'تم إعطاء الاعتماد الفني بنجاح'
            ]);

        } catch (\Exception $e) {
            Log::error('Error granting technical approval for template task', [
                'template_task_user_id' => $templateTaskUser->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إعطاء الاعتماد الفني'
            ], 500);
        }
    }

    /**
     * إلغاء اعتماد إداري لتاسك قالب
     */
    public function revokeAdministrativeApprovalTemplate(TemplateTaskUser $templateTaskUser)
    {
        try {
            $currentUser = Auth::user();

            // التحقق من الصلاحيات
            if (!$templateTaskUser->canUserApprove($currentUser->id, 'administrative')) {
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك صلاحية إلغاء الاعتماد الإداري لهذه التاسك'
                ], 403);
            }

            // إلغاء الاعتماد الإداري
            $templateTaskUser->revokeAdministrativeApproval();

            return response()->json([
                'success' => true,
                'message' => 'تم إلغاء الاعتماد الإداري بنجاح'
            ]);

        } catch (\Exception $e) {
            Log::error('Error revoking administrative approval for template task', [
                'template_task_user_id' => $templateTaskUser->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إلغاء الاعتماد الإداري'
            ], 500);
        }
    }

    /**
     * إلغاء اعتماد فني لتاسك قالب
     */
    public function revokeTechnicalApprovalTemplate(TemplateTaskUser $templateTaskUser)
    {
        try {
            $currentUser = Auth::user();

            // التحقق من الصلاحيات
            if (!$templateTaskUser->canUserApprove($currentUser->id, 'technical')) {
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك صلاحية إلغاء الاعتماد الفني لهذه التاسك'
                ], 403);
            }

            // إلغاء الاعتماد الفني
            $templateTaskUser->revokeTechnicalApproval();

            return response()->json([
                'success' => true,
                'message' => 'تم إلغاء الاعتماد الفني بنجاح'
            ]);

        } catch (\Exception $e) {
            Log::error('Error revoking technical approval for template task', [
                'template_task_user_id' => $templateTaskUser->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إلغاء الاعتماد الفني'
            ], 500);
        }
    }
}
