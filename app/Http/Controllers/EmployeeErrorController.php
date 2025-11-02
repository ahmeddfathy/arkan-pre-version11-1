<?php

namespace App\Http\Controllers;

use App\Models\EmployeeError;
use App\Models\TaskUser;
use App\Models\TemplateTaskUser;
use App\Models\ProjectServiceUser;
use App\Models\User;
use App\Services\EmployeeErrorController\EmployeeErrorManagementService;
use App\Services\EmployeeErrorController\EmployeeErrorIndexService;
use App\Services\EmployeeErrorController\EmployeeErrorFilterService;
use App\Services\EmployeeErrorController\EmployeeErrorStatisticsService;
use App\Services\EmployeeErrorController\EmployeeErrorValidationService;
use App\Services\EmployeeErrorController\EmployeeErrorNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeErrorController extends Controller
{
    protected $managementService;
    protected $indexService;
    protected $filterService;
    protected $statisticsService;
    protected $validationService;
    protected $notificationService;

    public function __construct(
        EmployeeErrorManagementService $managementService,
        EmployeeErrorIndexService $indexService,
        EmployeeErrorFilterService $filterService,
        EmployeeErrorStatisticsService $statisticsService,
        EmployeeErrorValidationService $validationService,
        EmployeeErrorNotificationService $notificationService
    ) {
        $this->managementService = $managementService;
        $this->indexService = $indexService;
        $this->filterService = $filterService;
        $this->statisticsService = $statisticsService;
        $this->validationService = $validationService;
        $this->notificationService = $notificationService;
    }

    /**
     * عرض قائمة الأخطاء للموظف الحالي
     */
    public function index(Request $request)
    {
        $user = Auth::user();


        $myErrors = $this->indexService->getMyErrors($user, $request->all());


        $reportedErrors = collect();
        if ($this->validationService->canReportError()) {
            $reportedErrors = $this->indexService->getReportedErrors($user, $request->all());
        }


        $allErrors = collect();
        if ($user->hasRole(['admin', 'super-admin', 'hr', 'project_manager'])) {
            $allErrors = $this->indexService->getAllErrors($request->all());
        }


        $criticalErrors = $this->indexService->getAllCriticalErrors($request->all());

        // إحصائيات منفصلة لكل tab
        $myErrorsStats = $this->statisticsService->getMyErrorsStats($user, $request->all());

        $allErrorsStats = [];
        if ($user->hasRole(['admin', 'super-admin', 'hr', 'project_manager'])) {
            $allErrorsStats = $this->statisticsService->getAllErrorsStats($request->all());
        }

        $reportedErrorsStats = [];
        if ($this->validationService->canReportError()) {
            $reportedErrorsStats = $this->statisticsService->getReportedErrorsStats($user, $request->all());
        }

        $criticalErrorsStats = $this->statisticsService->getCriticalErrorsStats($request->all());

        return view('employee-errors.index', [
            'myErrors' => $myErrors,
            'reportedErrors' => $reportedErrors,
            'allErrors' => $allErrors,
            'criticalErrors' => $criticalErrors,
            'myErrorsStats' => $myErrorsStats,
            'allErrorsStats' => $allErrorsStats,
            'reportedErrorsStats' => $reportedErrorsStats,
            'criticalErrorsStats' => $criticalErrorsStats,
        ]);
    }

    /**
     * عرض أخطاء موظف معين (للمديرين)
     */
    public function userErrors(Request $request, $userId)
    {
        // التحقق من الصلاحيات
        if (!$this->validationService->canViewTeamStats()) {
            abort(403, 'غير مصرح لك بعرض أخطاء الموظفين');
        }

        $user = User::findOrFail($userId);

        $filters = array_filter([
            'error_type' => $request->get('error_type'),
            'error_category' => $request->get('error_category'),
        ]);

        $employeeErrors = $this->indexService->getUserErrors($user, $filters);
        $stats = $this->statisticsService->getUserErrorStats($user);

        return view('employee-errors.user-errors', compact('user', 'employeeErrors', 'stats'));
    }

    /**
     * تسجيل خطأ جديد على مهمة
     */
    public function storeTaskError(Request $request, $taskUserId)
    {
        if (!$this->validationService->canReportError()) {
            return response()->json(['message' => 'غير مصرح لك بتسجيل الأخطاء'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'error_category' => 'required|in:quality,deadline,communication,technical,procedural,other',
            'error_type' => 'required|in:normal,critical',
        ]);

        $taskUser = TaskUser::findOrFail($taskUserId);

        try {
            $error = $this->managementService->createError($taskUser, $validated);

            // إرسال إشعار
            $this->notificationService->notifyOnErrorCreated($error);

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل الخطأ بنجاح',
                'error' => $error->load(['user', 'reportedBy'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * تسجيل خطأ على مهمة قالب
     */
    public function storeTemplateTaskError(Request $request, $templateTaskUserId)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'error_category' => 'required|in:quality,deadline,communication,technical,procedural,other',
            'error_type' => 'required|in:normal,critical',
        ]);

        $templateTaskUser = TemplateTaskUser::findOrFail($templateTaskUserId);

        if (!$this->validationService->canReportError()) {
            return response()->json(['message' => 'غير مصرح لك بتسجيل الأخطاء'], 403);
        }

        try {
            $error = $this->managementService->createError($templateTaskUser, $validated);
            $this->notificationService->notifyOnErrorCreated($error);

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل الخطأ بنجاح',
                'error' => $error->load(['user', 'reportedBy'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * تسجيل خطأ على مشروع
     */
    public function storeProjectError(Request $request, $projectServiceUserId)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'error_category' => 'required|in:quality,deadline,communication,technical,procedural,other',
            'error_type' => 'required|in:normal,critical',
        ]);

        $projectServiceUser = ProjectServiceUser::findOrFail($projectServiceUserId);

        if (!$this->validationService->canReportError()) {
            return response()->json(['message' => 'غير مصرح لك بتسجيل الأخطاء'], 403);
        }

        try {
            $error = $this->managementService->createError($projectServiceUser, $validated);
            $this->notificationService->notifyOnErrorCreated($error);

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل الخطأ بنجاح',
                'error' => $error->load(['user', 'reportedBy'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * تحديث خطأ
     */
    public function update(Request $request, $errorId)
    {
        $error = EmployeeError::findOrFail($errorId);

        // التحقق من الصلاحيات
        if (!$this->validationService->canEditError($error)) {
            return response()->json(['message' => 'غير مصرح لك بتعديل هذا الخطأ'], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'error_category' => 'sometimes|required|in:quality,deadline,communication,technical,procedural,other',
            'error_type' => 'sometimes|required|in:normal,critical',
        ]);

        try {
            $error = $this->managementService->updateError($error, $validated);
            $this->notificationService->notifyOnErrorUpdated($error);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الخطأ بنجاح',
                'error' => $error
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * حذف خطأ
     */
    public function destroy($errorId)
    {
        $error = EmployeeError::findOrFail($errorId);

        // التحقق من الصلاحيات
        if (!$this->validationService->canDeleteError($error)) {
            return response()->json(['message' => 'غير مصرح لك بحذف هذا الخطأ'], 403);
        }

        try {
            $this->managementService->deleteError($error);
            $this->notificationService->notifyOnErrorDeleted($error);

            return response()->json([
                'success' => true,
                'message' => 'تم حذف الخطأ بنجاح'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * عرض أخطاء مشروع معين
     */
    public function projectErrors(Request $request, $projectId)
    {
        if (!$this->validationService->canViewTeamStats()) {
            abort(403, 'غير مصرح لك بعرض أخطاء المشاريع');
        }

        $filters = array_filter([
            'user_id' => $request->get('user_id'),
            'error_type' => $request->get('error_type'),
        ]);

        $employeeErrors = $this->indexService->getProjectErrors($projectId, $filters);

        return view('employee-errors.project-errors', compact('projectId', 'employeeErrors'));
    }

    /**
     * عرض تفاصيل خطأ معين
     */
    public function show($errorId)
    {
        $error = EmployeeError::with(['user', 'reportedBy', 'errorable'])->findOrFail($errorId);

        // التحقق من الصلاحيات
        if (!$this->validationService->canViewErrors($error->user_id)) {
            abort(403, 'غير مصرح لك بعرض هذا الخطأ');
        }

        return view('employee-errors.show', compact('error'));
    }

    /**
     * الحصول على إحصائيات الأخطاء للفريق
     */
    public function teamStats(Request $request)
    {
        if (!$this->validationService->canViewTeamStats()) {
            abort(403, 'غير مصرح لك بعرض إحصائيات الفريق');
        }

        $teamId = $request->get('team_id');
        $department = $request->get('department');

        $stats = $this->statisticsService->getTeamErrorStats($teamId, $department);

        return response()->json($stats);
    }

    /**
     * جلب المهام/المشاريع الخاصة بموظف معين
     */
    public function getErrorables(Request $request)
    {
        $type = $request->get('type');
        $userId = $request->get('user_id');
        $projectCode = $request->get('project_code'); // كود المشروع (اختياري)

        if (!$type || !$userId) {
            return response()->json([
                'success' => false,
                'message' => 'البيانات غير كاملة'
            ]);
        }

        $errorables = [];

        try {
            switch ($type) {
                case 'TaskUser':
                    $query = TaskUser::where('user_id', $userId)
                        ->with('task.project');

                    // تصفية حسب كود المشروع إذا كان موجوداً
                    if ($projectCode) {
                        $query->whereHas('task.project', function ($q) use ($projectCode) {
                            $q->where('code', $projectCode);
                        });
                    }

                    $errorables = $query->get()
                        ->map(function ($taskUser) {
                            $taskName = $taskUser->task->name ?? "مهمة #{$taskUser->id}";
                            $projectCode = $taskUser->task->project->code ?? '';

                            return [
                                'id' => $taskUser->id,
                                'name' => $projectCode ? "[$projectCode] $taskName" : $taskName
                            ];
                        });
                    break;

                case 'TemplateTaskUser':
                    $query = TemplateTaskUser::where('user_id', $userId)
                        ->with('templateTask', 'project');

                    // تصفية حسب كود المشروع إذا كان موجوداً
                    if ($projectCode) {
                        $query->whereHas('project', function ($q) use ($projectCode) {
                            $q->where('code', $projectCode);
                        });
                    }

                    $errorables = $query->get()
                        ->map(function ($templateTaskUser) {
                            $taskName = $templateTaskUser->templateTask->name ?? "مهمة قالب #{$templateTaskUser->id}";
                            $projectCode = $templateTaskUser->project->code ?? '';

                            return [
                                'id' => $templateTaskUser->id,
                                'name' => $projectCode ? "[$projectCode] $taskName" : $taskName
                            ];
                        });
                    break;

                case 'ProjectServiceUser':
                    $query = ProjectServiceUser::where('user_id', $userId)
                        ->with('project');

                    // تصفية حسب كود المشروع إذا كان موجوداً
                    if ($projectCode) {
                        $query->whereHas('project', function ($q) use ($projectCode) {
                            $q->where('code', $projectCode);
                        });
                    }

                    $errorables = $query->get()
                        ->map(function ($projectServiceUser) {
                            $projectName = $projectServiceUser->project->name ?? "مشروع #{$projectServiceUser->id}";
                            $projectCode = $projectServiceUser->project->code ?? '';

                            return [
                                'id' => $projectServiceUser->id,
                                'name' => $projectCode ? "[$projectCode] $projectName" : $projectName
                            ];
                        });
                    break;

                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'نوع غير صالح'
                    ]);
            }

            return response()->json([
                'success' => true,
                'errorables' => $errorables
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * جلب الموظفين المتاحين لإضافة أخطاء عليهم
     */
    public function getAvailableUsers()
    {
        $users = $this->validationService->getUsersCanAddErrorsTo();

        return response()->json([
            'success' => true,
            'users' => $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name
                ];
            })
        ]);
    }

    /**
     * جلب المشاريع التي يشارك فيها الموظف
     */
    public function getEmployeeProjects(Request $request)
    {
        $userId = $request->get('user_id');

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'معرف الموظف مطلوب'
            ], 400);
        }

        try {
            // جلب جميع المشاريع التي يشارك فيها الموظف
            $projects = ProjectServiceUser::where('user_id', $userId)
                ->with('project:id,code,name')
                ->whereHas('project')
                ->get()
                ->map(function ($projectServiceUser) {
                    return [
                        'id' => $projectServiceUser->project->id,
                        'code' => $projectServiceUser->project->code,
                        'name' => $projectServiceUser->project->name,
                    ];
                })
                ->unique('code') // إزالة التكرارات بناءً على الكود
                ->values()
                ->sortBy('code'); // ترتيب حسب الكود

            return response()->json([
                'success' => true,
                'projects' => $projects->values()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب المشاريع: ' . $e->getMessage()
            ], 500);
        }
    }
}
