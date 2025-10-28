<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskUser;
use App\Models\User;
use App\Models\GraphicTaskType;
use App\Traits\SeasonAwareTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

// Task Controller Services
use App\Services\TaskController\TaskManagementService;
use App\Services\TaskController\TaskUserAssignmentService;
use App\Services\TaskController\TaskHierarchyService;
use App\Services\TaskController\TaskFilterService;
use App\Services\TaskController\TaskStatusService;
use App\Services\TaskController\TaskIndexService;
use App\Services\TaskController\TaskActivityService;
use App\Services\TaskController\TaskValidationService;
use App\Services\TaskController\TaskDataTransformationService;
use App\Services\ProjectPointsValidationService;

class TaskController extends Controller
{
    use SeasonAwareTrait;

    protected $taskManagementService;
    protected $taskUserAssignmentService;
    protected $taskHierarchyService;
    protected $taskFilterService;
    protected $taskStatusService;
    protected $taskIndexService;
    protected $taskActivityService;
    protected $taskValidationService;
    protected $taskDataTransformationService;
    protected $pointsValidationService;

    public function __construct(
        TaskManagementService $taskManagementService,
        TaskUserAssignmentService $taskUserAssignmentService,
        TaskHierarchyService $taskHierarchyService,
        TaskFilterService $taskFilterService,
        TaskStatusService $taskStatusService,
        TaskIndexService $taskIndexService,
        TaskActivityService $taskActivityService,
        TaskValidationService $taskValidationService,
        TaskDataTransformationService $taskDataTransformationService,
        ProjectPointsValidationService $pointsValidationService
    ) {
        $this->taskManagementService = $taskManagementService;
        $this->taskUserAssignmentService = $taskUserAssignmentService;
        $this->taskHierarchyService = $taskHierarchyService;
        $this->taskFilterService = $taskFilterService;
        $this->taskStatusService = $taskStatusService;
        $this->taskIndexService = $taskIndexService;
        $this->taskActivityService = $taskActivityService;
        $this->taskValidationService = $taskValidationService;
        $this->taskDataTransformationService = $taskDataTransformationService;
        $this->pointsValidationService = $pointsValidationService;
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        try {
            // تسجيل نشاط دخول صفحة المهام
            $this->taskActivityService->logTasksIndexView($request);

            // جلب المهام باستخدام TaskIndexService
            $tasksData = $this->taskIndexService->getTasksForIndex($request);

            // جلب البيانات المساعدة
            $supportData = $this->taskIndexService->getIndexSupportData();

            $isGraphicOnlyUser = $this->determineIfGraphicOnlyUser();

            return view('tasks.index', array_merge($tasksData, $supportData, compact('isGraphicOnlyUser')));

        } catch (\Exception $e) {
            Log::error('Error in tasks index method', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // في حالة الخطأ، أرسل بيانات فارغة آمنة
            $tasks = new \Illuminate\Pagination\LengthAwarePaginator(
                collect([]),
                0,
                15,
                1,
                ['path' => request()->url(), 'query' => request()->query()]
            );

            $supportData = $this->taskIndexService->getIndexSupportData();
            $isGraphicOnlyUser = $this->determineIfGraphicOnlyUser();

            return view('tasks.index', array_merge(compact('tasks'), $supportData, compact('isGraphicOnlyUser')))
                ->with('error', 'حدث خطأ في تحميل المهام. يرجى المحاولة مرة أخرى.');
        }
    }

    public function create()
    {
        $projects = $this->taskFilterService->getAvailableProjects();
        $services = $this->taskFilterService->getFilteredServicesForUser();
        $users = $this->taskFilterService->getFilteredUsersForCurrentUser();
        $roles = $this->taskFilterService->getFilteredRolesForUser();
        $graphicTaskTypes = GraphicTaskType::active()->orderBy('name')->get();

        $isGraphicOnlyUser = $this->determineIfGraphicOnlyUser();

        return view('tasks.create', compact('projects', 'services', 'users', 'roles', 'graphicTaskTypes', 'isGraphicOnlyUser'));
    }

    public function store(Request $request)
    {
        // التحقق من صلاحية المستخدم للقسم والخدمة المختارة
        if (!$this->taskHierarchyService->canUserAccessService($request->service_id)) {
            return redirect()->back()
                ->with('error', 'غير مسموح لك بإنشاء مهام لهذه الخدمة. تأكد من أن دورك مرتبط بقسمك.')
                ->withInput();
        }

        // إضافة تتبع البيانات المستلمة للتشخيص
        Log::info('Task creation request received', [
            'all_data' => $request->all(),
            'assigned_users' => $request->get('assigned_users'),
            'has_assigned_users' => $request->has('assigned_users'),
            'assigned_users_empty' => empty($request->assigned_users)
        ]);

        // البداية بـ validation أساسي أولاً
        $basicValidationRules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'project_id' => 'nullable|exists:projects,id',
            'service_id' => 'required|exists:company_services,id',
            'graphic_task_type_id' => 'nullable|exists:graphic_task_types,id',
            'points' => 'nullable|integer|min:0|max:1000',
            'due_date' => 'nullable|date',
            'assigned_users' => 'required|array|min:1', // ✅ إجباري ولازم موظف واحد على الأقل
            'assigned_users.*.user_id' => 'required|exists:users,id',
            'assigned_users.*.role' => 'nullable|string',
            'is_flexible_time' => 'nullable|in:true,false,1,0',
            'is_additional_task' => 'nullable|in:true,false,1,0',
        ];

        // التحقق الأساسي أولاً
        $basicValidator = Validator::make($request->all(), $basicValidationRules, [
            'assigned_users.required' => 'يجب تعيين موظف واحد على الأقل للمهمة',
            'assigned_users.min' => 'يجب تعيين موظف واحد على الأقل للمهمة',
        ]);

        if ($basicValidator->fails()) {
            // ✅ في حالة AJAX، إرجاع JSON
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $basicValidator->errors()->first(),
                    'errors' => $basicValidator->errors()
                ], 422);
            }

            return redirect()->back()
                ->withErrors($basicValidator)
                ->withInput();
        }

        // الآن يمكن الحصول على قيمة is_flexible_time بشكل آمن
        $flexibleTimeValue = $request->input('is_flexible_time', '0');
        $isFlexible = filter_var($flexibleTimeValue, FILTER_VALIDATE_BOOLEAN) || $flexibleTimeValue === '1';

        // الحصول على قيمة is_additional_task
        $additionalTaskValue = $request->input('is_additional_task', '0');
        $isAdditionalTask = filter_var($additionalTaskValue, FILTER_VALIDATE_BOOLEAN) || $additionalTaskValue === '1';

        Log::info('Flexible time processing', [
            'raw_value' => $flexibleTimeValue,
            'is_flexible' => $isFlexible,
            'filter_result' => filter_var($flexibleTimeValue, FILTER_VALIDATE_BOOLEAN),
            'is_true_string' => $flexibleTimeValue === 'true'
        ]);

        Log::info('Additional task processing', [
            'raw_value' => $additionalTaskValue,
            'is_additional_task' => $isAdditionalTask
        ]);

        // إضافة قواعد الوقت المقدر إذا لم تكن المهمة مرنة
        $validationRules = $basicValidationRules;
        if (!$isFlexible) {
            $validationRules['estimated_hours'] = 'required|integer|min:0';
            $validationRules['estimated_minutes'] = 'required|integer|min:0|max:59';
            $validationRules['assigned_users.*.estimated_hours'] = 'nullable|integer|min:0';
            $validationRules['assigned_users.*.estimated_minutes'] = 'nullable|integer|min:0|max:59';
            Log::info('Added REQUIRED time rules for regular task');
        } else {
            // للمهام المرنة، الوقت المقدر اختياري
            $validationRules['estimated_hours'] = 'nullable|integer|min:0';
            $validationRules['estimated_minutes'] = 'nullable|integer|min:0|max:59';
            Log::info('Added NULLABLE time rules for flexible task');
        }

        Log::info('Final validation rules', [
            'estimated_hours_rule' => $validationRules['estimated_hours'] ?? 'not set',
            'estimated_minutes_rule' => $validationRules['estimated_minutes'] ?? 'not set',
            'is_flexible' => $isFlexible
        ]);

        $validator = Validator::make($request->all(), $validationRules);

        if ($validator->fails()) {
            Log::error('Task validation failed', [
                'errors' => $validator->errors()->toArray(),
                'input' => $request->all()
            ]);
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // التحقق من حدود النقاط للخدمة في المشروع (إذا كان مشروع محدد)
        if ($request->project_id && $request->service_id) {
            $project = \App\Models\Project::find($request->project_id);
            $service = \App\Models\CompanyService::find($request->service_id);
            $taskPoints = $request->points ?? 10;

            if ($project && $service) {
                $pointsValidation = $this->pointsValidationService->canAddTaskToProject($project, $service, $taskPoints);

                if (!$pointsValidation['can_add']) {
                    return redirect()->back()
                        ->with('error', $pointsValidation['message'])
                        ->with('points_info', [
                            'current_points' => $pointsValidation['current_points'],
                            'max_points' => $pointsValidation['max_points'],
                            'remaining_points' => $pointsValidation['remaining_points'],
                            'requested_points' => $pointsValidation['requested_points']
                        ])
                        ->withInput();
                }
            }
        }

        try {
            DB::beginTransaction();

            // إعداد بيانات المهمة
            $taskData = [
                'name' => $request->name,
                'description' => $request->description,
                'project_id' => $request->project_id ?: null,
                'service_id' => $request->service_id,
                'points' => $request->points ?? 10,
                'due_date' => $request->due_date,
                'status' => 'new', // المهام الجديدة تبدأ بحالة "جديدة" دائماً
                'created_by' => Auth::id(),
                'is_flexible_time' => $isFlexible,
            ];

            // إضافة الوقت المقدر إذا لم تكن المهمة مرنة
            if (!$isFlexible) {
                $taskData['estimated_hours'] = $request->estimated_hours;
                $taskData['estimated_minutes'] = $request->estimated_minutes;
            }

            // إنشاء المهمة باستخدام TaskManagementService
            $task = $this->taskManagementService->createTask($taskData, $request->graphic_task_type_id);

            // 📋 حفظ البنود إذا تم إرسالها
            if ($request->has('items')) {
                $items = is_string($request->items) ? json_decode($request->items, true) : $request->items;
                if (is_array($items) && !empty($items)) {
                    $task->items = $items;
                    $task->save();
                }
            }

            // تعيين المستخدمين للمهمة باستخدام TaskUserAssignmentService
            if ($request->has('assigned_users') && !empty($request->assigned_users)) {
                $this->taskUserAssignmentService->assignUsersToTask(
                    $task,
                    $request->assigned_users,
                    $isFlexible,
                    $isAdditionalTask
                );
            }

            DB::commit();

            // ✅ التحقق إذا كان الطلب AJAX
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم إنشاء المهمة بنجاح',
                    'redirect' => route('tasks.index')
                ]);
            }

            return redirect()->route('tasks.index')
                ->with('success', 'تم إنشاء المهمة بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();

            // ✅ التحقق إذا كان الطلب AJAX
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'حدث خطأ أثناء إنشاء المهمة: ' . $e->getMessage(),
                    'message' => $e->getMessage()
                ], 422);
            }

            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء إنشاء المهمة: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show($id)
    {
        try {
            // تحقق إذا كانت المهمة من نوع template
            $isTemplate = request()->get('is_template', false);

            if ($isTemplate === 'true' || $isTemplate === true) {
                return $this->showTemplateTask($id);
            }

            // للمهام العادية - التحقق من وجود المهمة أولاً
            $task = Task::find($id);
            $taskUser = null;

            if (!$task) {
                // إذا لم توجد المهمة العادية، ربما الـ ID هو task_user_id
                $taskUser = \App\Models\TaskUser::with(['task', 'user'])->find($id);
                if ($taskUser && $taskUser->task) {
                    $task = $taskUser->task;
                } else {
                    // تحقق من وجود TemplateTaskUser بنفس المعرف
                    $templateTaskUser = \App\Models\TemplateTaskUser::find($id);
                    if ($templateTaskUser) {
                        // إعادة توجيه للتعامل مع مهمة القالب
                        return $this->showTemplateTask($id);
                    }

                    // إذا لم توجد أي منهما، أرجع خطأ
                    throw new \Illuminate\Database\Eloquent\ModelNotFoundException('المهمة غير موجودة');
                }
            }

            $task->load(['project', 'service', 'users', 'graphicTaskTypes', 'createdBy']);

            // تسجيل نشاط عرض المهمة
            if (\Illuminate\Support\Facades\Auth::check()) {
                activity()
                    ->performedOn($task)
                    ->causedBy(\Illuminate\Support\Facades\Auth::user())
                    ->withProperties([
                        'task_name' => $task->name,
                        'task_status' => $task->status,
                        'project_name' => $task->project ? $task->project->name : null,
                        'project_id' => $task->project_id,
                        'action_type' => 'view',
                        'viewed_at' => now()->toDateTimeString(),
                        'user_agent' => request()->userAgent(),
                        'ip_address' => request()->ip()
                    ])
                    ->log('شاهد المهمة');
            }

            if (request()->expectsJson() || request()->ajax()) {
                $taskData = $task->toArray();

                // إذا كان لدينا TaskUser محدد، أضف معلوماته
                if ($taskUser) {
                    $taskData['current_task_user'] = [
                        'id' => $taskUser->id,
                        'user_id' => $taskUser->user_id,
                        'user_name' => $taskUser->user ? $taskUser->user->name : 'غير محدد',
                        'status' => $taskUser->status,
                        'actual_hours' => $taskUser->actual_hours ?? 0,
                        'actual_minutes' => $taskUser->actual_minutes ?? 0,
                        'estimated_hours' => $taskUser->estimated_hours ?? 0,
                        'estimated_minutes' => $taskUser->estimated_minutes ?? 0,
                        'is_transferred' => $taskUser->is_transferred ?? false,
                        'is_additional_task' => $taskUser->is_additional_task ?? false,
                        'task_source' => $taskUser->task_source ?? null,
                        'transferred_at' => $taskUser->transferred_at ? $taskUser->transferred_at->toDateTimeString() : null,
                    ];
                }

                // إصلاح بيانات الموظفين مع التواريخ الصحيحة من task_users
                if (isset($taskData['users']) && is_array($taskData['users'])) {
                    foreach ($taskData['users'] as $key => $user) {
                        if (!isset($user['pivot'])) {
                            $taskData['users'][$key]['pivot'] = [
                                'task_id' => $task->id,
                                'user_id' => $user['id'],
                                'role' => 'غير محدد',
                                'status' => 'new',
                                'estimated_hours' => 0,
                                'estimated_minutes' => 0,
                                'actual_hours' => 0,
                                'actual_minutes' => 0,
                                'due_date' => null,
                                'completed_date' => null
                            ];
                        } else {
                            // تنسيق التواريخ من pivot بشكل صحيح
                            if (isset($user['pivot']['due_date']) && $user['pivot']['due_date']) {
                                $dueDate = Carbon::parse($user['pivot']['due_date']);
                                $taskData['users'][$key]['pivot']['due_date'] = $dueDate->format('Y-m-d');
                            }
                        }
                    }
                }

                // تنسيق التواريخ الرئيسية للمهمة أيضاً
                if (isset($taskData['due_date']) && $taskData['due_date']) {
                    $taskData['due_date'] = Carbon::parse($taskData['due_date'])->format('Y-m-d');
                }

                return response()->json($taskData);
            }

            return view('tasks.show', compact('task'));
        } catch (\Exception $e) {
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'error' => 'حدث خطأ في تحميل بيانات المهمة',
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ], 500);
            }

            return redirect()->route('tasks.index')
                ->with('error', 'حدث خطأ في تحميل بيانات المهمة: ' . $e->getMessage());
        }
    }

    /**
     * عرض تفاصيل مهمة قالب
     */
    private function showTemplateTask($templateTaskUserId)
    {
        try {
            $templateTaskUser = \App\Models\TemplateTaskUser::with([
                'templateTask.template',
                'project',
                'user',
                'season'
            ])->findOrFail($templateTaskUserId);

            // تسجيل نشاط عرض مهمة القالب
            if (\Illuminate\Support\Facades\Auth::check()) {
                activity()
                    ->performedOn($templateTaskUser->templateTask)
                    ->causedBy(\Illuminate\Support\Facades\Auth::user())
                    ->withProperties([
                        'template_task_name' => $templateTaskUser->templateTask->name ?? 'مهمة قالب',
                        'template_task_status' => $templateTaskUser->status,
                        'project_name' => $templateTaskUser->project ? $templateTaskUser->project->name : null,
                        'project_id' => $templateTaskUser->project_id,
                        'template_task_user_id' => $templateTaskUserId,
                        'action_type' => 'view',
                        'viewed_at' => now()->toDateTimeString(),
                        'user_agent' => request()->userAgent(),
                        'ip_address' => request()->ip()
                    ])
                    ->log('شاهد مهمة القالب');
            }

            if (request()->expectsJson() || request()->ajax()) {
                $templateTask = $templateTaskUser->templateTask;
                $template = $templateTask->template ?? null;

                // تحويل مهمة القالب إلى شكل يشبه المهمة العادية
                $taskData = [
                    'id' => $templateTaskUser->id,
                    'name' => ($templateTask->name ?? 'مهمة قالب') . ' (قالب)',
                    'description' => $templateTask->description ?? 'مهمة من قالب',
                    'is_template' => true,
                    'template_name' => $template->name ?? 'قالب غير محدد',

                    // المشروع
                    'project' => $templateTaskUser->project ? [
                        'id' => $templateTaskUser->project->id,
                        'name' => $templateTaskUser->project->name
                    ] : null,
                    'project_id' => $templateTaskUser->project_id,

                    // الخدمة
                    'service' => $template && $template->service ? [
                        'id' => $template->service->id,
                        'name' => $template->service->name
                    ] : null,
                    'service_id' => $template->service_id ?? null,

                    // الأوقات
                    'estimated_hours' => $templateTask->estimated_hours ?? 0,
                    'estimated_minutes' => $templateTask->estimated_minutes ?? 0,
                    'actual_hours' => $templateTaskUser->actual_minutes ? intval($templateTaskUser->actual_minutes / 60) : 0,
                    'actual_minutes' => $templateTaskUser->actual_minutes ? ($templateTaskUser->actual_minutes % 60) : 0,

                    // الحالة والتواريخ
                    'status' => $templateTaskUser->status ?? 'new',
                    'due_date' => null,
                    'created_at' => $templateTaskUser->created_at,
                    'updated_at' => $templateTaskUser->updated_at,

                    // المستخدم المعين
                    'users' => $templateTaskUser->user ? [[
                        'id' => $templateTaskUser->user->id,
                        'name' => $templateTaskUser->user->name,
                        'email' => $templateTaskUser->user->email,
                        'pivot' => [
                            'task_id' => $templateTaskUser->id,
                            'user_id' => $templateTaskUser->user->id,
                            'role' => 'منفذ قالب',
                            'status' => $templateTaskUser->status ?? 'new',
                            'estimated_hours' => $templateTask->estimated_hours ?? 0,
                            'estimated_minutes' => $templateTask->estimated_minutes ?? 0,
                            'actual_hours' => $templateTaskUser->actual_minutes ? intval($templateTaskUser->actual_minutes / 60) : 0,
                            'actual_minutes' => $templateTaskUser->actual_minutes ? ($templateTaskUser->actual_minutes % 60) : 0,
                        ]
                    ]] : [],

                    // منشئ المهمة (لا يوجد للقوالب)
                    'createdBy' => null,
                    'created_by' => null,
                ];

                return response()->json($taskData);
            }

            return view('tasks.show', compact('templateTaskUser'));
        } catch (\Exception $e) {
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'error' => 'حدث خطأ في تحميل بيانات مهمة القالب',
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ], 500);
            }

            return redirect()->route('tasks.index')
                ->with('error', 'حدث خطأ في تحميل بيانات مهمة القالب: ' . $e->getMessage());
        }
    }

        public function edit($id)
    {
        try {
            // تسجيل محاولة التعديل للتشخيص
            Log::info('Edit task request', [
                'id' => $id,
                'is_template' => request()->get('is_template', false),
                'is_ajax' => request()->ajax(),
                'expects_json' => request()->expectsJson()
            ]);

            // التحقق من نوع المهمة أولاً
            $isTemplate = request()->get('is_template', false);

            if ($isTemplate === 'true' || $isTemplate === true) {
                return $this->editTemplateTask($id);
            }

            // للمهام العادية - التحقق من وجود المهمة أولاً
            $task = Task::find($id);
            if (!$task) {
                Log::warning('Task not found for edit', ['id' => $id]);

                // إذا لم توجد المهمة العادية، ربما هي مهمة قالب
                // تحقق من وجود TemplateTaskUser بنفس المعرف
                $templateTaskUser = \App\Models\TemplateTaskUser::find($id);
                if ($templateTaskUser) {
                    Log::info('Found template task instead, redirecting', ['id' => $id]);
                    // إعادة توجيه للتعامل مع مهمة القالب
                    return $this->editTemplateTask($id);
                }

                // إذا لم توجد أي منهما، أرجع خطأ
                if (request()->expectsJson() || request()->ajax()) {
                    return response()->json([
                        'error' => 'المهمة غير موجودة',
                        'message' => 'لم يتم العثور على المهمة بالمعرف: ' . $id
                    ], 404);
                }

                throw new \Illuminate\Database\Eloquent\ModelNotFoundException('المهمة غير موجودة');
            }

            // تحميل العلاقات مع pivot data كاملة
            $task->load(['users', 'project', 'service', 'graphicTaskTypes', 'createdBy']);

            $projects = $this->taskFilterService->getAvailableProjects();
            $services = $this->taskFilterService->getFilteredServicesForUser();
            $users = $this->taskFilterService->getFilteredUsersForCurrentUser();
            $roles = $this->taskFilterService->getFilteredRolesForUser();

            if (request()->expectsJson() || request()->ajax()) {
                $taskData = $task->toArray();

                // إصلاح بيانات الموظفين مع التواريخ الصحيحة من task_users
                if (isset($taskData['users']) && is_array($taskData['users'])) {
                    foreach ($taskData['users'] as $key => $user) {
                        if (!isset($user['pivot'])) {
                            // إذا لم تكن بيانات pivot موجودة، أحضرها يدوياً
                            $taskUser = TaskUser::where('task_id', $task->id)
                                              ->where('user_id', $user['id'])
                                              ->first();

                            if ($taskUser) {
                                $taskData['users'][$key]['pivot'] = [
                                    'id' => $taskUser->id,
                                    'task_id' => $task->id,
                                    'user_id' => $user['id'],
                                    'role' => $taskUser->role ?? 'غير محدد',
                                    'status' => $taskUser->status ?? 'new',
                                    'estimated_hours' => $taskUser->estimated_hours ?? 0,
                                    'estimated_minutes' => $taskUser->estimated_minutes ?? 0,
                                    'actual_hours' => $taskUser->actual_hours ?? 0,
                                    'actual_minutes' => $taskUser->actual_minutes ?? 0,
                                    'due_date' => $taskUser->due_date ? $taskUser->due_date->format('Y-m-d') : null,
                                    'completed_date' => $taskUser->completed_date ? $taskUser->completed_date->format('Y-m-d') : null
                                ];
                            } else {
                                $taskData['users'][$key]['pivot'] = [
                                    'task_id' => $task->id,
                                    'user_id' => $user['id'],
                                    'role' => 'غير محدد',
                                    'status' => 'new',
                                    'estimated_hours' => 0,
                                    'estimated_minutes' => 0,
                                    'actual_hours' => 0,
                                    'actual_minutes' => 0,
                                    'due_date' => null,
                                    'completed_date' => null
                                ];
                            }
                        } else {
                            // تنسيق التواريخ من pivot بشكل صحيح للتعديل
                            if (isset($user['pivot']['due_date']) && $user['pivot']['due_date']) {
                                $dueDate = Carbon::parse($user['pivot']['due_date']);
                                $taskData['users'][$key]['pivot']['due_date'] = $dueDate->format('Y-m-d');
                            }
                        }
                    }
                }

                // تنسيق التواريخ الرئيسية للمهمة للتعديل
                if (isset($taskData['due_date']) && $taskData['due_date']) {
                    $taskData['due_date'] = Carbon::parse($taskData['due_date'])->format('Y-m-d');
                }

                return response()->json($taskData);
            }

            return view('tasks.edit', compact('task', 'projects', 'services', 'users', 'roles'));
        } catch (\Exception $e) {
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'error' => 'حدث خطأ في تحميل بيانات المهمة',
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ], 500);
            }

            return redirect()->route('tasks.index')
                ->with('error', 'حدث خطأ في تحميل بيانات المهمة: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        // التحقق من نوع المهمة أولاً
        $isTemplate = $request->get('is_template', false);

        if ($isTemplate === 'true' || $isTemplate === true) {
            return $this->updateTemplateTask($request, $id);
        }

        // للمهام العادية - التحقق من وجود المهمة أولاً
        $task = Task::find($id);
        if (!$task) {
            // إذا لم توجد المهمة العادية، ربما هي مهمة قالب
            // تحقق من وجود TemplateTaskUser بنفس المعرف
            $templateTaskUser = \App\Models\TemplateTaskUser::find($id);
            if ($templateTaskUser) {
                // إعادة توجيه للتعامل مع مهمة القالب
                return $this->updateTemplateTask($request, $id);
            }

            // إذا لم توجد أي منهما، أرجع خطأ
            return redirect()->back()
                ->with('error', 'المهمة غير موجودة')
                ->withInput();
        }

        if (!$this->taskHierarchyService->canUserAccessService($request->service_id)) {
            return redirect()->back()
                ->with('error', 'غير مسموح لك بتعديل مهام لهذه الخدمة. تأكد من أن دورك مرتبط بقسمك.')
                ->withInput();
        }

        // التحقق من قيمة is_flexible_time
        $flexibleTimeValue = $request->input('is_flexible_time', 'false');
        $isFlexible = filter_var($flexibleTimeValue, FILTER_VALIDATE_BOOLEAN) || $flexibleTimeValue === 'true';

        // الحصول على قيمة is_additional_task
        $additionalTaskValue = $request->input('is_additional_task', 'false');
        $isAdditionalTask = filter_var($additionalTaskValue, FILTER_VALIDATE_BOOLEAN) || $additionalTaskValue === 'true';

        $validationRules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'project_id' => 'nullable|exists:projects,id',
            'service_id' => 'required|exists:company_services,id',
            'graphic_task_type_id' => 'nullable|exists:graphic_task_types,id',
            'status' => 'required|in:new,cancelled',
            'due_date' => 'nullable|date',
            'points' => 'nullable|integer|min:0|max:1000',
            'assigned_users' => 'nullable|array',
            'assigned_users.*.user_id' => 'required|exists:users,id',
            'assigned_users.*.role' => 'nullable|string',
            'is_flexible_time' => 'nullable|in:true,false,1,0',
            'is_additional_task' => 'nullable|in:true,false,1,0',
        ];

        // إضافة قواعد الوقت حسب نوع المهمة
        if (!$isFlexible) {
            $validationRules['estimated_hours'] = 'required|integer|min:0';
            $validationRules['estimated_minutes'] = 'required|integer|min:0|max:59';
            $validationRules['assigned_users.*.estimated_hours'] = 'required|integer|min:0';
            $validationRules['assigned_users.*.estimated_minutes'] = 'required|integer|min:0|max:59';
        } else {
            $validationRules['estimated_hours'] = 'nullable|integer|min:0';
            $validationRules['estimated_minutes'] = 'nullable|integer|min:0|max:59';
            $validationRules['assigned_users.*.estimated_hours'] = 'nullable|integer|min:0';
            $validationRules['assigned_users.*.estimated_minutes'] = 'nullable|integer|min:0|max:59';
        }

        $validator = Validator::make($request->all(), $validationRules);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // التحقق من حدود النقاط عند التعديل (إذا تم تغيير النقاط)
        if ($request->has('points') && $request->project_id && $request->service_id) {
            $newPoints = $request->points;
            $oldPoints = $task->points;

            if ($newPoints != $oldPoints) {
                $project = \App\Models\Project::find($request->project_id);
                $service = \App\Models\CompanyService::find($request->service_id);

                if ($project && $service) {
                    $pointsValidation = $this->pointsValidationService->canUpdateTaskPoints($task, $newPoints);

                    if (!$pointsValidation['can_update']) {
                        return redirect()->back()
                            ->with('error', $pointsValidation['message'])
                            ->withInput();
                    }
                }
            }
        }

        try {
            DB::beginTransaction();

            $updateData = [
                'name' => $request->name,
                'description' => $request->description,
                'project_id' => $request->project_id,
                'service_id' => $request->service_id,
                'status' => $request->status,
                'due_date' => $request->due_date,
                'is_flexible_time' => $isFlexible,
            ];

            // إضافة النقاط إذا تم تمريرها
            if ($request->has('points')) {
                $updateData['points'] = $request->points;
            }

            // إضافة الوقت المقدر فقط إذا لم تكن المهمة مرنة
            if (!$isFlexible) {
                $updateData['estimated_hours'] = $request->estimated_hours;
                $updateData['estimated_minutes'] = $request->estimated_minutes;
            } else {
                $updateData['estimated_hours'] = null;
                $updateData['estimated_minutes'] = null;
            }

            $this->taskManagementService->updateTask($task, $updateData, $request->graphic_task_type_id);

            // 📋 تحديث البنود إذا تم إرسالها
            if ($request->has('items')) {
                $items = is_string($request->items) ? json_decode($request->items, true) : $request->items;
                $task->items = is_array($items) ? $items : [];
                $task->save();
            }

            if ($request->has('assigned_users') && !empty($request->assigned_users)) {
                $this->taskUserAssignmentService->updateTaskUserAssignments(
                    $task,
                    $request->assigned_users,
                    ['hours' => $request->estimated_hours, 'minutes' => $request->estimated_minutes],
                    $isAdditionalTask
                );
                    } else {
                $this->taskUserAssignmentService->removeAllUserAssignments($task->id);
            }

            DB::commit();

            // ✅ التحقق إذا كان الطلب AJAX
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم تحديث المهمة بنجاح',
                    'redirect' => route('tasks.index')
                ]);
            }

            return redirect()->route('tasks.index')
                ->with('success', 'تم تحديث المهمة بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();

            // ✅ التحقق إذا كان الطلب AJAX
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'حدث خطأ أثناء تحديث المهمة: ' . $e->getMessage(),
                    'message' => $e->getMessage()
                ], 422);
            }

            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء تحديث المهمة: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy(Task $task)
    {
        if ($this->taskManagementService->deleteTask($task)) {
            return redirect()->route('tasks.index')
                ->with('success', 'تم حذف المهمة بنجاح');
        } else {
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء حذف المهمة');
        }
    }

    public function getTasksForProject($projectId)
    {
        $result = $this->taskManagementService->getProjectTasks($projectId);

        // تسجيل نشاط دخول مهام مشروع معين
        if (\Illuminate\Support\Facades\Auth::check() && $result['project']) {
            activity()
                ->performedOn($result['project'])
                ->causedBy(\Illuminate\Support\Facades\Auth::user())
                ->withProperties([
                    'action_type' => 'view_project_tasks',
                    'project_name' => $result['project']->name,
                    'project_id' => $projectId,
                    'viewed_at' => now()->toDateTimeString(),
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip()
                ])
                ->log('دخل على مهام المشروع');
        }

        // تطبيق الفلترة الهرمية على مهام المشروع
        $tasksQuery = Task::with(['service', 'users'])
            ->where('project_id', $projectId);

        $tasksQuery = $this->taskFilterService->applyHierarchicalTaskFiltering($tasksQuery);
        $filteredTasks = $tasksQuery->orderBy('created_at', 'desc')->get();

        return view('tasks.project_tasks', [
            'project' => $result['project'],
            'tasks' => $filteredTasks
        ]);
    }

    public function myTasks(Request $request)
    {
        try {
            // تسجيل نشاط دخول صفحة مهامي
            if (\Illuminate\Support\Facades\Auth::check()) {
                activity()
                    ->causedBy(\Illuminate\Support\Facades\Auth::user())
                    ->withProperties([
                        'action_type' => 'view_my_tasks',
                        'page' => 'my_tasks',
                        'filters' => $request->all(),
                        'viewed_at' => now()->toDateTimeString(),
                        'user_agent' => request()->userAgent(),
                        'ip_address' => request()->ip()
                    ])
                    ->log('دخل على صفحة مهامي');
            }

            $userId = Auth::id();
            $user = User::findOrFail($userId);

            // جلب المهام العادية مع حماية (بدون pagination لصفحة My Tasks)
            $tasks = $this->taskManagementService->getUserTasks($userId, $request->all(), false);

            // تم إصلاح مشكلة عرض المهام العادية للمستخدمين العاديين

            // إثراء بيانات المهام العادية إذا وجدت
            if ($tasks && $tasks->count() > 0) {
                foreach ($tasks as $task) {
                    $this->taskManagementService->enrichTaskWithUserData($task, $userId);
                }
            }

            // إضافة مهام القوالب (Template Tasks) مع حماية
            $templateTasks = $this->taskManagementService->getUserTemplateTasks($userId, $request->all()) ?? collect([]);



            // دمج المهام العادية مع مهام القوالب مع حماية كاملة
            $tasksArray = [];
            $templateTasksArray = [];

            // استخراج المهام العادية بحماية - إصلاح مشكلة pagination metadata
            if ($tasks && method_exists($tasks, 'items')) {
                // للـ paginator، استخدم items() للحصول على المهام الفعلية فقط
                $tasksArray = $tasks->items();
            } elseif ($tasks && method_exists($tasks, 'toArray')) {
                $tasksArray = $tasks->toArray();
            } elseif ($tasks && is_iterable($tasks)) {
                $tasksArray = iterator_to_array($tasks);
            } else {
                $tasksArray = $tasks ? [$tasks] : [];
            }

            // استخراج مهام القوالب بحماية
            if ($templateTasks && method_exists($templateTasks, 'items')) {
                // إذا كان paginator
                $templateTasksArray = $templateTasks->items();
            } elseif ($templateTasks && method_exists($templateTasks, 'toArray')) {
                $templateTasksArray = $templateTasks->toArray() ?? [];
            } else {
                $templateTasksArray = [];
            }

            // دمج البيانات مع حماية كاملة
            $allTasksArray = array_merge(
                is_array($tasksArray) ? $tasksArray : [],
                is_array($templateTasksArray) ? $templateTasksArray : []
            );



                        // إنشاء collection مع حماية
            $allTasks = collect($allTasksArray);

            // ترتيب المهام حسب التاريخ مع حماية إضافية
            if ($allTasks->isNotEmpty()) {
                $allTasks = $allTasks->filter()->sortByDesc('created_at');
            }

            // جلب المشاريع مع حماية
            $projects = $this->taskFilterService->getUserProjects($userId) ?? collect([]);

            return view('tasks.my-tasks', compact('allTasks', 'tasks', 'templateTasks', 'projects', 'user'));

        } catch (\Exception $e) {
            Log::error('Error in myTasks method', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // في حالة الخطأ، أرسل بيانات فارغة آمنة
            $allTasks = collect([]);
            $tasks = new \Illuminate\Pagination\LengthAwarePaginator(
                collect([]),
                0,
                10,
                1,
                ['path' => request()->url(), 'query' => request()->query()]
            );
            $templateTasks = collect([]);
            $projects = collect([]);
            $user = Auth::user();

            return view('tasks.my-tasks', compact('allTasks', 'tasks', 'templateTasks', 'projects', 'user'))
                ->with('error', 'حدث خطأ في تحميل المهام. يرجى المحاولة مرة أخرى.');
        }
    }


    public function updateTaskUserStatus(Request $request, $taskUserId)
    {
            $validated = $request->validate([
                'status' => 'required|string|in:new,in_progress,paused,completed',
            ]);

        $result = $this->taskStatusService->updateTaskUserStatus($taskUserId, $validated['status']);

        if (!$result['success']) {
            $statusCode = isset($result['code']) ? $result['code'] : 500;
            return response()->json($result, $statusCode);
        }

        return response()->json($result);
    }

    public function changeStatus(Request $request, Task $task)
    {
        $validatedData = $request->validate([
            'status' => 'required|string|in:pending,in_progress,completed,cancelled'
        ]);

        $result = $this->taskStatusService->changeTaskStatus($task, $validatedData['status']);
        return response()->json($result);
    }

    public function getUsersByService($serviceId)
    {
        $result = $this->taskFilterService->getUsersByService($serviceId);
        return response()->json($result);
    }


    public function getUsersByRole($roleName)
    {
        $result = $this->taskFilterService->getUsersByRole($roleName);
        return response()->json($result);
    }

    /**
     * جلب جميع المستخدمين المتاحين لتعبئة القوائم المنسدلة
     */
    public function getAllUsers()
    {
        try {
            $users = $this->taskFilterService->getFilteredUsersForCurrentUser();
            return response()->json($users);
        } catch (\Exception $e) {
            Log::error('خطأ في تحميل المستخدمين', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([], 500);
        }
    }

    /**
     * تعديل مهمة قالب
     */
    private function editTemplateTask($templateTaskUserId)
    {
        try {
            $templateTaskUser = \App\Models\TemplateTaskUser::with([
                'templateTask.template',
                'project',
                'user',
                'season'
            ])->findOrFail($templateTaskUserId);

            if (request()->expectsJson() || request()->ajax()) {
                $templateTask = $templateTaskUser->templateTask;
                $template = $templateTask->template ?? null;

                // تحويل مهمة القالب إلى شكل يشبه المهمة العادية للتعديل
                $taskData = [
                    'id' => $templateTaskUser->id,
                    'name' => $templateTask->name ?? 'مهمة قالب',
                    'description' => $templateTask->description ?? 'مهمة من قالب',
                    'is_template' => true,
                    'template_name' => $template->name ?? 'قالب غير محدد',

                    // المشروع
                    'project' => $templateTaskUser->project ? [
                        'id' => $templateTaskUser->project->id,
                        'name' => $templateTaskUser->project->name
                    ] : null,
                    'project_id' => $templateTaskUser->project_id,

                    // الخدمة
                    'service' => $template && $template->service ? [
                        'id' => $template->service->id,
                        'name' => $template->service->name
                    ] : null,
                    'service_id' => $template->service_id ?? null,

                    // الأوقات
                    'estimated_hours' => $templateTask->estimated_hours ?? 0,
                    'estimated_minutes' => $templateTask->estimated_minutes ?? 0,
                    'actual_hours' => $templateTaskUser->actual_minutes ? intval($templateTaskUser->actual_minutes / 60) : 0,
                    'actual_minutes' => $templateTaskUser->actual_minutes ? ($templateTaskUser->actual_minutes % 60) : 0,

                    // الحالة والتواريخ
                    'status' => $templateTaskUser->status ?? 'new',
                    'due_date' => $templateTaskUser->due_date,
                    'created_at' => $templateTaskUser->created_at,
                    'updated_at' => $templateTaskUser->updated_at,

                    // المستخدم المعين
                    'users' => $templateTaskUser->user ? [[
                        'id' => $templateTaskUser->user->id,
                        'name' => $templateTaskUser->user->name,
                        'email' => $templateTaskUser->user->email,
                        'pivot' => [
                            'id' => $templateTaskUser->id,
                            'task_id' => $templateTaskUser->id,
                            'user_id' => $templateTaskUser->user->id,
                            'role' => 'منفذ قالب',
                            'status' => $templateTaskUser->status ?? 'new',
                            'estimated_hours' => $templateTask->estimated_hours ?? 0,
                            'estimated_minutes' => $templateTask->estimated_minutes ?? 0,
                            'actual_hours' => $templateTaskUser->actual_minutes ? intval($templateTaskUser->actual_minutes / 60) : 0,
                            'actual_minutes' => $templateTaskUser->actual_minutes ? ($templateTaskUser->actual_minutes % 60) : 0,
                            'due_date' => $templateTaskUser->due_date ? Carbon::parse($templateTaskUser->due_date)->format('Y-m-d') : null,
                        ]
                    ]] : [],

                    // منشئ المهمة (لا يوجد للقوالب)
                    'createdBy' => null,
                    'created_by' => null,

                    // النقاط
                    'points' => $templateTask->points ?? 10,

                    // بيانات إضافية للقوالب
                    'template_task_id' => $templateTask->id,
                    'template_id' => $template->id ?? null,
                    'season_id' => $templateTaskUser->season_id,
                ];

                return response()->json($taskData);
            }

            // للعرض العادي (إذا لزم الأمر)
            $projects = $this->taskFilterService->getAvailableProjects();
            $services = $this->taskFilterService->getFilteredServicesForUser();
            $users = $this->taskFilterService->getFilteredUsersForCurrentUser();
            $roles = $this->taskFilterService->getFilteredRolesForUser();

            return view('tasks.edit', compact('templateTaskUser', 'projects', 'services', 'users', 'roles'))
                ->with('isTemplate', true);

        } catch (\Exception $e) {
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'error' => 'حدث خطأ في تحميل بيانات مهمة القالب',
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ], 500);
            }

            return redirect()->route('tasks.index')
                ->with('error', 'حدث خطأ في تحميل بيانات مهمة القالب: ' . $e->getMessage());
        }
    }

    /**
     * تحديث مهمة قالب
     */
    private function updateTemplateTask(Request $request, $templateTaskUserId)
    {
        try {
            // جلب مهمة القالب
            $templateTaskUser = \App\Models\TemplateTaskUser::with([
                'templateTask.template',
                'project',
                'user'
            ])->findOrFail($templateTaskUserId);

            // التحقق من الصلاحيات (يمكن تطبيق نفس منطق المهام العادية أو تخصيصه للقوالب)

            // قواعد التحقق لمهام القوالب
            $validationRules = [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'project_id' => 'nullable|exists:projects,id',
                'status' => 'required|in:new,cancelled',
                'estimated_hours' => 'nullable|integer|min:0',
                'estimated_minutes' => 'nullable|integer|min:0|max:59',
                'points' => 'nullable|integer|min:0|max:1000',
                'assigned_users' => 'nullable|array',
                'assigned_users.*.user_id' => 'required|exists:users,id',
            ];

            $validator = Validator::make($request->all(), $validationRules);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            // التحقق من حدود النقاط عند تحديث مهمة القالب
            if ($request->has('points') && $templateTaskUser->project_id) {
                $newPoints = $request->points ?? 10;
                $templateTask = $templateTaskUser->templateTask;
                $oldPoints = $templateTask ? $templateTask->points : 0;

                if ($newPoints != $oldPoints && $templateTask && $templateTask->template) {
                    $project = \App\Models\Project::find($templateTaskUser->project_id);
                    $service = \App\Models\CompanyService::find($templateTask->template->service_id);

                    if ($project && $service && $service->hasMaxPointsLimit()) {
                        // حساب الفرق في النقاط
                        $pointsDifference = $newPoints - $oldPoints;

                        if ($pointsDifference > 0) {
                            $pointsValidation = $this->pointsValidationService->canAddTaskToProject($project, $service, $pointsDifference);

                            if (!$pointsValidation['can_add']) {
                                return redirect()->back()
                                    ->with('error', $pointsValidation['message'])
                                    ->withInput();
                            }
                        }
                    }
                }
            }

            DB::beginTransaction();

            $templateTask = $templateTaskUser->templateTask;
            if ($templateTask) {
                $templateTask->update([
                    'name' => $request->name,
                    'description' => $request->description,
                    'estimated_hours' => $request->estimated_hours ?? 0,
                    'estimated_minutes' => $request->estimated_minutes ?? 0,
                    'points' => $request->points ?? 10,
                ]);
            }

            $templateTaskUser->update([
                'project_id' => $request->project_id,
                'status' => $request->status,
            ]);


            if ($request->has('assigned_users') && !empty($request->assigned_users)) {
                $firstUser = $request->assigned_users[0];
                if (isset($firstUser['user_id']) && $firstUser['user_id'] != $templateTaskUser->user_id) {
                    $templateTaskUser->update([
                        'user_id' => $firstUser['user_id']
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('tasks.index')
                ->with('success', 'تم تحديث مهمة القالب بنجاح');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء تحديث مهمة القالب: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * تحديد ما إذا كان المستخدم مقتصر على الجرافيك فقط
     */
    private function determineIfGraphicOnlyUser(): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        $graphicOnlyRoles = [
            'employee',
            'sales_employee',
            'marketing_team_employee',
            'financial_team_employee',
            'technical_team_employee',
            'technical_reviewer',
            'marketing_reviewer',
            'financial_reviewer'
        ];

        $userRoles = $user->roles->pluck('name')->toArray();

        if (empty($userRoles)) {
            return false;
        }

        // إذا كان لديك أي دور خارج أدوار الجرافيك، فأنت لست مقتصر على الجرافيك فقط
        $hasHigherRole = !empty(array_diff($userRoles, $graphicOnlyRoles));

        // تعتبر graphic only user فقط إذا كانت كل أدوارك ضمن أدوار الجرافيك
        return !$hasHigherRole;
    }

    /**
     * Get users with same role as the assigned user (for task revisions)
     */
    public function getTaskUserRoleUsers(Request $request)
    {
        try {
            $taskType = $request->input('task_type'); // 'regular' or 'template'
            $taskId = $request->input('task_id');
            $taskUserId = $request->input('task_user_id');

            if (!$taskType || !$taskId) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات المهمة غير مكتملة'
                ], 400);
            }

            $assignedUser = null;
            $userRole = null;

            // Get the assigned user and their role
            if ($taskType === 'template') {
                $templateTaskUser = \App\Models\TemplateTaskUser::find($taskId);
                if ($templateTaskUser) {
                    $assignedUser = $templateTaskUser->user;
                    // Get main role from template task
                    $templateTask = $templateTaskUser->templateTask;
                    if ($templateTask) {
                        $userRole = $templateTask->role_name;
                    }
                }
            } else {
                // Regular task
                if ($taskUserId) {
                    $taskUser = TaskUser::find($taskUserId);
                    if ($taskUser) {
                        $assignedUser = $taskUser->user;
                    }
                }

                // If no task_user_id, try to get from task
                if (!$assignedUser) {
                    $task = Task::find($taskId);
                    if ($task && $task->taskUsers->count() > 0) {
                        $assignedUser = $task->taskUsers->first()->user;
                    }
                }

                // Get role from task
                if (!$userRole) {
                    $task = Task::find($taskId);
                    if ($task) {
                        $userRole = $task->role_name;
                    }
                }
            }

            if (!$assignedUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم العثور على المستخدم المسند إليه'
                ], 404);
            }

            // Get all users with the same role
            $users = collect();

            if ($userRole) {
                $users = User::whereHas('roles', function($query) use ($userRole) {
                    $query->where('name', $userRole);
                })->get(['id', 'name']);
            } else {
                // Fallback: get users with any of the assigned user's roles
                $assignedUserRoles = $assignedUser->roles->pluck('name')->toArray();
                $users = User::whereHas('roles', function($query) use ($assignedUserRoles) {
                    $query->whereIn('name', $assignedUserRoles);
                })->get(['id', 'name']);
            }

            // Mark the assigned user
            $users = $users->map(function($user) use ($assignedUser) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'is_assigned' => $user->id == $assignedUser->id
                ];
            });

            return response()->json([
                'success' => true,
                'users' => $users,
                'assigned_user_id' => $assignedUser->id
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting task user role users', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جلب المستخدمين'
            ], 500);
        }
    }


}
