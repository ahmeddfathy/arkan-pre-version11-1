<?php

namespace App\Http\Controllers;

use App\Models\AdditionalTask;
use App\Models\AdditionalTaskUser;
use App\Models\Season;
use App\Models\User;
use App\Models\DepartmentRole;
use App\Services\AdditionalTasks\AdditionalTaskFilterService;
use App\Services\Notifications\AdditionalTaskNotificationService;
use App\Services\TaskController\TaskHierarchyService;
use App\Services\Auth\RoleCheckService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AdditionalTaskController extends Controller
{
    protected $filterService;
    protected $roleCheckService;
    protected $notificationService;

    public function __construct(
        AdditionalTaskFilterService $filterService,
        RoleCheckService $roleCheckService,
        AdditionalTaskNotificationService $notificationService
    )
    {
        $this->filterService = $filterService;
        $this->roleCheckService = $roleCheckService;
        $this->notificationService = $notificationService;
    }
    /**
     * عرض قائمة المهام الإضافية
     */
    public function index()
    {
        // تسجيل النشاط - دخول صفحة المهام الإضافية
        if (\Illuminate\Support\Facades\Auth::check()) {
            activity()
                ->causedBy(\Illuminate\Support\Facades\Auth::user())
                ->withProperties([
                    'action_type' => 'view_index',
                    'page' => 'additional_tasks_index',
                    'viewed_at' => now()->toDateTimeString(),
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip()
                ])
                ->log('دخل على صفحة المهام الإضافية');
        }

        // التحقق من المستوى المطلوب - المستوى 2+ فقط لصفحة الإدارة
        $user = Auth::user();
        $createCheck = $this->filterService->canCreateTask();

        if (!$createCheck['can_create']) {
            return redirect()->route('additional-tasks.user-tasks')
                           ->with('info', 'تم توجيهك لصفحة المهام الإضافية المتاحة لك');
        }

        // التحقق من صحة ربط الأدوار - فقط للمستخدمين الذين يحتاجون إنشاء مهام
        $errorMessage = $this->filterService->getDepartmentRoleErrorMessage($user);
        if ($errorMessage) {
            return redirect()->route('dashboard')
                           ->with('error', $errorMessage);
        }

        $tasksQuery = $this->filterService->getTasksForIndex();

        // إذا كانت Collection (للمستوى 1) نحولها لـ paginate
        if ($tasksQuery instanceof \Illuminate\Support\Collection) {
            $currentPage = request()->get('page', 1);
            $perPage = 15;
            $tasks = new \Illuminate\Pagination\LengthAwarePaginator(
                $tasksQuery->forPage($currentPage, $perPage),
                $tasksQuery->count(),
                $perPage,
                $currentPage,
                ['path' => request()->url(), 'pageName' => 'page']
            );
        } else {
            // إذا كانت Query Builder (للأدوار العليا)
            $tasks = $tasksQuery->paginate(15);
        }

        // معلومات الصلاحيات للعرض
        $createCheck = $this->filterService->canCreateTask();

        return view('additional-tasks.index', compact('tasks', 'createCheck'));
    }

    /**
     * عرض صفحة إنشاء مهمة إضافية جديدة
     */
    public function create()
    {
        // التحقق من صلاحية الإنشاء
        $createCheck = $this->filterService->canCreateTask();
        if (!$createCheck['can_create']) {
            return redirect()->route('additional-tasks.index')
                           ->with('error', $createCheck['message'] ?? 'لا تملك الصلاحيات اللازمة لإنشاء مهام جديدة');
        }

        $seasons = Season::orderBy('created_at', 'desc')->get();
        $departments = $createCheck['available_departments'];

        return view('additional-tasks.create', compact('seasons', 'departments', 'createCheck'));
    }

    /**
     * حفظ مهمة إضافية جديدة
     */
    public function store(Request $request)
    {
        // التحقق من صلاحية الإنشاء
        $createCheck = $this->filterService->canCreateTask();
        if (!$createCheck['can_create']) {
            return redirect()->route('additional-tasks.index')
                           ->with('error', $createCheck['message'] ?? 'لا تملك الصلاحيات اللازمة لإنشاء مهام جديدة');
        }

        // التحقق من إعداد المهمة المرنة
        $isFlexible = $request->has('is_flexible_time') && $request->is_flexible_time;

        $validationRules = [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'points' => 'required|integer|min:1|max:1000',
            'target_type' => 'required|in:all,department',
            'target_department' => 'required_if:target_type,department|nullable|string',
            'max_participants' => 'required|integer|min:1|max:1000',
            'season_id' => 'nullable|exists:seasons,id',
            'icon' => 'nullable|string',
            'color_code' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'is_flexible_time' => 'nullable|boolean',
        ];

        // التحقق من صحة نوع الهدف حسب الصلاحيات
        if ($request->target_type === 'all' && !$createCheck['can_target_all']) {
            return redirect()->back()
                           ->withInput()
                           ->with('error', 'لا تملك الصلاحيات لإنشاء مهام عامة');
        }

        if ($request->target_type === 'department' && !in_array($request->target_department, $createCheck['available_departments'])) {
            return redirect()->back()
                           ->withInput()
                           ->with('error', 'لا تملك الصلاحيات لإنشاء مهام لهذا القسم');
        }

        // إضافة قاعدة المدة إذا لم تكن المهمة مرنة
        if (!$isFlexible) {
            $validationRules['duration_hours'] = 'required|integer|min:1|max:8760'; // سنة كاملة كحد أقصى
        }

        $request->validate($validationRules);

        // إعداد بيانات المهمة الإضافية
        $taskData = [
            'title' => $request->title,
            'description' => $request->description,
            'points' => $request->points,
            'target_type' => $request->target_type,
            'target_department' => $request->target_type === 'department' ? $request->target_department : null,
            'assignment_type' => 'application_required', // دائماً يتطلب تقديم
            'max_participants' => $request->max_participants,
            'season_id' => $request->season_id ?: Season::where('is_active', true)->first()?->id,
            'icon' => $request->icon,
            'color_code' => $request->color_code ?: '#3B82F6',
            'created_by' => Auth::id(),
            'status' => 'active',
        ];

        // إضافة معلومات الوقت إذا لم تكن المهمة مرنة
        if (!$isFlexible) {
            $currentTime = Carbon::now();
            $endTime = $currentTime->copy()->addHours((int)$request->duration_hours);

            $taskData['duration_hours'] = $request->duration_hours;
            $taskData['original_end_time'] = $endTime;
            $taskData['current_end_time'] = $endTime;
        } else {
            // للمهام المرنة، نترك الوقت null
            $taskData['duration_hours'] = null;
            $taskData['original_end_time'] = null;
            $taskData['current_end_time'] = null;
        }

        $task = AdditionalTask::create($taskData);

        // إرسال إشعارات للمستخدمين المؤهلين
        $notificationResult = $this->notificationService->notifyEligibleUsers($task);

        $message = 'تم إنشاء المهمة بنجاح';
        if ($notificationResult['success'] && $notificationResult['notified_count'] > 0) {
            $message .= " وإرسال إشعارات لـ {$notificationResult['notified_count']} مستخدم";
        }

        return redirect()->route('additional-tasks.index')
                        ->with('success', $message);
    }

    /**
     * عرض تفاصيل مهمة إضافية
     */
    public function show(AdditionalTask $additionalTask)
    {
        // تسجيل النشاط - عرض تفاصيل المهمة الإضافية
        if (\Illuminate\Support\Facades\Auth::check()) {
            activity()
                ->performedOn($additionalTask)
                ->causedBy(\Illuminate\Support\Facades\Auth::user())
                ->withProperties([
                    'task_title' => $additionalTask->title,
                    'task_id' => $additionalTask->id,
                    'creator_id' => $additionalTask->created_by,
                    'action_type' => 'view',
                    'viewed_at' => now()->toDateTimeString(),
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip()
                ])
                ->log('شاهد تفاصيل المهمة الإضافية');
        }

        $additionalTask->load(['creator', 'season']);
        $stats = $additionalTask->getCompletionStats();

        $taskUsers = $additionalTask->taskUsers()
                                   ->with('user')
                                   ->orderBy('status')
                                   ->orderBy('applied_at', 'desc')
                                   ->paginate(20);

        return view('additional-tasks.show', compact('additionalTask', 'stats', 'taskUsers'));
    }

    /**
     * عرض صفحة تعديل مهمة إضافية
     */
    public function edit(AdditionalTask $additionalTask)
    {
        // التحقق من صلاحية التعديل
        $editCheck = $this->filterService->canEditTask($additionalTask);
        if (!$editCheck['can_edit']) {
            return redirect()->route('additional-tasks.index')
                           ->with('error', $editCheck['message'] ?? 'لا تملك الصلاحيات اللازمة لتعديل هذه المهمة');
        }

        $seasons = Season::orderBy('created_at', 'desc')->get();
        $departments = $this->filterService->getAvailableDepartments();
        $createCheck = $this->filterService->canCreateTask(); // للتحقق من الصلاحيات

        return view('additional-tasks.edit', compact('additionalTask', 'seasons', 'departments', 'createCheck'));
    }

    /**
     * تحديث مهمة إضافية
     */
    public function update(Request $request, AdditionalTask $additionalTask)
    {
        // التحقق من صلاحية التعديل
        $editCheck = $this->filterService->canEditTask($additionalTask);
        if (!$editCheck['can_edit']) {
            return redirect()->route('additional-tasks.index')
                           ->with('error', $editCheck['message'] ?? 'لا تملك الصلاحيات اللازمة لتعديل هذه المهمة');
        }

        $createCheck = $this->filterService->canCreateTask();

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'points' => 'required|integer|min:1|max:1000',
            'target_type' => 'required|in:all,department',
            'target_department' => 'required_if:target_type,department|nullable|string',
            'max_participants' => 'required|integer|min:1|max:1000',
            'season_id' => 'nullable|exists:seasons,id',
            'icon' => 'nullable|string',
            'color_code' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'is_active' => 'boolean',
        ]);

        // التحقق من صحة نوع الهدف حسب الصلاحيات
        if ($request->target_type === 'all' && !$createCheck['can_target_all']) {
            return redirect()->back()
                           ->withInput()
                           ->with('error', 'لا تملك الصلاحيات لجعل المهمة عامة');
        }

        if ($request->target_type === 'department' && !in_array($request->target_department, $createCheck['available_departments'])) {
            return redirect()->back()
                           ->withInput()
                           ->with('error', 'لا تملك الصلاحيات لتخصيص المهمة لهذا القسم');
        }

        $additionalTask->update([
            'title' => $request->title,
            'description' => $request->description,
            'points' => $request->points,
            'target_type' => $request->target_type,
            'target_department' => $request->target_type === 'department' ? $request->target_department : null,
            'assignment_type' => 'application_required', // دائماً يتطلب تقديم
            'max_participants' => $request->max_participants,
            'season_id' => $request->season_id,
            'icon' => $request->icon,
            'color_code' => $request->color_code ?: '#3B82F6',
            'is_active' => $request->boolean('is_active', true),
        ]);

        // التعامل مع تمديد الوقت
        if ($request->extend_hours && $request->extend_hours > 0 && $additionalTask->canBeExtended()) {
            $additionalTask->extendTime((int)$request->extend_hours, 'تمديد من صفحة التعديل');
        }

        return redirect()->route('additional-tasks.show', $additionalTask)
                        ->with('success', 'تم تحديث المهمة بنجاح');
    }

    /**
     * حذف مهمة إضافية
     */
    public function destroy(AdditionalTask $additionalTask)
    {
        // التحقق من صلاحية الحذف
        $editCheck = $this->filterService->canEditTask($additionalTask);
        if (!$editCheck['can_edit']) {
            return redirect()->route('additional-tasks.index')
                           ->with('error', $editCheck['message'] ?? 'لا تملك الصلاحيات اللازمة لحذف هذه المهمة');
        }

        $title = $additionalTask->title;
        $additionalTask->delete();

        return redirect()->route('additional-tasks.index')
                        ->with('success', "تم حذف المهمة '{$title}' بنجاح");
    }

    /**
     * تمديد وقت مهمة إضافية
     */
    public function extendTime(Request $request, AdditionalTask $additionalTask)
    {
        // التحقق من صلاحية التمديد
        $editCheck = $this->filterService->canEditTask($additionalTask);
        if (!$editCheck['can_edit']) {
            return redirect()->back()
                           ->with('error', $editCheck['message'] ?? 'لا تملك الصلاحيات اللازمة لتمديد هذه المهمة');
        }

        $request->validate([
            'additional_hours' => 'required|integer|min:1|max:168', // أسبوع كحد أقصى
            'reason' => 'nullable|string|max:500',
        ]);

        if (!$additionalTask->canBeExtended()) {
            return redirect()->back()
                           ->with('error', 'لا يمكن تمديد هذه المهمة');
        }

        $success = $additionalTask->extendTime((int)$request->additional_hours, $request->reason);

        if ($success) {
            return redirect()->back()
                           ->with('success', "تم تمديد المهمة بـ {$request->additional_hours} ساعة إضافية");
        } else {
            return redirect()->back()
                           ->with('error', 'فشل في تمديد المهمة');
        }
    }

    /**
     * تغيير حالة المهمة
     */
    public function toggleStatus(AdditionalTask $additionalTask)
    {
        // التحقق من صلاحية تغيير الحالة
        $editCheck = $this->filterService->canEditTask($additionalTask);
        if (!$editCheck['can_edit']) {
            return redirect()->back()
                           ->with('error', $editCheck['message'] ?? 'لا تملك الصلاحيات اللازمة لتغيير حالة هذه المهمة');
        }

        $newStatus = $additionalTask->status === 'active' ? 'cancelled' : 'active';
        $additionalTask->update(['status' => $newStatus]);

        $message = $newStatus === 'active' ? 'تم تفعيل المهمة' : 'تم إلغاء المهمة';

        return redirect()->back()->with('success', $message);
    }

        /**
     * عرض المهام للمستخدم
     */
    public function userTasks()
    {
        $user = Auth::user();

        // تسجيل النشاط - دخول صفحة مهام المستخدم الإضافية
        if (\Illuminate\Support\Facades\Auth::check()) {
            activity()
                ->causedBy($user)
                ->withProperties([
                    'action_type' => 'view_user_tasks',
                    'page' => 'additional_tasks_user',
                    'viewed_at' => now()->toDateTimeString(),
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip()
                ])
                ->log('دخل على صفحة المهام الإضافية الخاصة به');
        }

        // التحقق من المستوى المطلوب
        $accessCheck = $this->filterService->checkMinimumLevel();
        if (!$accessCheck['has_access']) {
            return redirect()->route('dashboard')
                           ->with('error', $accessCheck['message']);
        }

        // التحقق من صحة ربط الأدوار - فقط للمستخدمين الذين يحتاجون صلاحيات خاصة
        $errorMessage = $this->filterService->getDepartmentRoleErrorMessage($user);
        if ($errorMessage) {
            return redirect()->route('dashboard')
                           ->with('error', $errorMessage);
        }

        // المهام المتاحة (غير مُخصصة بعد) - باستخدام نظام الصلاحيات
        $availableTasks = $this->filterService->getAvailableTasksForUser();

        // المهام المقبولة/المُخصصة
        $acceptedTasks = AdditionalTaskUser::where('user_id', $user->id)
                                        ->where('status', 'assigned')
                                        ->with('additionalTask')
                                        ->get();

        // المهام في انتظار الموافقة
        $pendingTasks = AdditionalTaskUser::where('user_id', $user->id)
                                         ->where('status', 'applied')
                                         ->with('additionalTask')
                                         ->get();

        // المهام المرفوضة
        $rejectedTasks = AdditionalTaskUser::where('user_id', $user->id)
                                          ->where('status', 'rejected')
                                          ->with('additionalTask')
                                          ->get();

        return view('additional-tasks.user-tasks', compact('availableTasks', 'acceptedTasks', 'pendingTasks', 'rejectedTasks'));
    }

    /**
     * قبول مهمة إضافية
     */
    public function acceptTask(AdditionalTask $additionalTask)
    {
        $user = Auth::user();

        // التحقق من صلاحية المهمة
        if ($additionalTask->status !== 'active' || $additionalTask->isExpired()) {
            return redirect()->back()
                           ->with('error', 'هذه المهمة غير متاحة');
        }

        // التحقق من عدم وجود المهمة للمستخدم مسبقاً
        if ($additionalTask->users()->where('user_id', $user->id)->exists()) {
            return redirect()->back()
                           ->with('error', 'لديك هذه المهمة بالفعل');
        }

        // إنشاء تخصيص جديد
        AdditionalTaskUser::create([
            'additional_task_id' => $additionalTask->id,
            'user_id' => $user->id,
            'status' => 'assigned',
        ]);

        return redirect()->back()
                        ->with('success', 'تم قبول المهمة بنجاح');
    }


    /**
     * التقديم على مهمة إضافية
     */
    public function applyForTask(Request $request, AdditionalTask $additionalTask)
    {
        $user = Auth::user();

        $request->validate([
            'user_notes' => 'nullable|string|max:1000',
        ]);

        // التحقق من صلاحية المهمة
        if (!$additionalTask->requiresApplication() ||
            $additionalTask->status !== 'active' ||
            $additionalTask->isExpired()) {
            return redirect()->back()
                           ->with('error', 'هذه المهمة غير متاحة للتقديم');
        }

        // التحقق من عدم وجود تقديم سابق
        if (AdditionalTaskUser::where('additional_task_id', $additionalTask->id)
                             ->where('user_id', $user->id)
                             ->exists()) {
            return redirect()->back()
                           ->with('error', 'لقد تقدمت على هذه المهمة مسبقاً');
        }

        // التحقق من وجود مقاعد متاحة
        if (!$additionalTask->canAcceptMoreParticipants()) {
            return redirect()->back()
                           ->with('error', 'لا توجد مقاعد متاحة في هذه المهمة');
        }

        // إنشاء طلب التقديم
        AdditionalTaskUser::create([
            'additional_task_id' => $additionalTask->id,
            'user_id' => $user->id,
            'status' => 'applied',
            'applied_at' => Carbon::now(),
            'user_notes' => $request->user_notes,
        ]);

        return redirect()->back()
                        ->with('success', 'تم تقديم طلبك بنجاح! في انتظار الموافقة من الإدارة');
    }

    /**
     * الموافقة على طلب تقديم
     */
    public function approveApplication(Request $request, AdditionalTaskUser $additionalTaskUser)
    {
        $request->validate([
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        if ($additionalTaskUser->approveApplication($request->admin_notes)) {
            // إرسال إشعار للمستخدم بالموافقة
            $this->notificationService->notifyUserApproved($additionalTaskUser);

            return redirect()->back()
                           ->with('success', 'تم قبول الطلب بنجاح وإرسال إشعار للمستخدم');
        } else {
            return redirect()->back()
                           ->with('error', 'لا يمكن قبول هذا الطلب');
        }
    }

    /**
     * رفض طلب تقديم
     */
    public function rejectApplication(Request $request, AdditionalTaskUser $additionalTaskUser)
    {
        $request->validate([
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        if ($additionalTaskUser->rejectApplication($request->admin_notes)) {
            // إرسال إشعار للمستخدم بالرفض
            $this->notificationService->notifyUserRejected($additionalTaskUser);

            return redirect()->back()
                           ->with('success', 'تم رفض الطلب وإرسال إشعار للمستخدم');
        } else {
            return redirect()->back()
                           ->with('error', 'لا يمكن رفض هذا الطلب');
        }
    }

    /**
     * عرض صفحة إدارة الطلبات
     */
    public function applications(Request $request)
    {
        // التحقق من المستوى المطلوب
        $accessCheck = $this->filterService->checkMinimumLevel();
        if (!$accessCheck['has_access']) {
            return redirect()->route('dashboard')
                           ->with('error', $accessCheck['message']);
        }

        $user = Auth::user();
        // التحقق من صحة ربط الأدوار - فقط للمستخدمين الذين يحتاجون صلاحيات خاصة
        $errorMessage = $this->filterService->getDepartmentRoleErrorMessage($user);
        if ($errorMessage) {
            return redirect()->route('dashboard')
                           ->with('error', $errorMessage);
        }

        // فلترة الطلبات حسب الصلاحيات
        $query = AdditionalTaskUser::with(['additionalTask', 'user'])
                                  ->where('status', 'applied');

        // فلترة حسب task_id إذا تم تمريره
        if ($request->has('task_id') && $request->task_id) {
            $query->where('additional_task_id', $request->task_id);
        }

        // الأدوار العليا - كل الطلبات
        if (!$this->roleCheckService->userHasRole(['company_manager', 'hr', 'project_manager'])) {
            $hierarchyService = app(TaskHierarchyService::class);
            $globalLevel = $hierarchyService->getCurrentUserHierarchyLevel($user);
            $departmentLevel = DepartmentRole::getUserDepartmentHierarchyLevel($user);

            if ($globalLevel && $globalLevel >= 4) {
                // المستوى 4+ - كل الطلبات
            } elseif ($departmentLevel && $departmentLevel >= 2) {
                // المستوى 2+ - طلبات القسم فقط + المهام التي أنشأها
                $query->whereHas('additionalTask', function($q) use ($user) {
                    $q->where(function($subQ) use ($user) {
                        $subQ->where('target_type', 'department')
                             ->where('target_department', $user->department);
                    })
                    ->orWhere('created_by', $user->id);
                });
            } else {
                // أقل من المستوى 2 - لا طلبات
                $query->whereRaw('1 = 0');
            }
        }

        $applications = $query->orderBy('applied_at', 'desc')->paginate(20);

        // الحصول على معلومات المهمة المحددة إذا تم تمرير task_id
        $selectedTask = null;
        if ($request->has('task_id') && $request->task_id) {
            $selectedTask = AdditionalTask::find($request->task_id);
        }

        return view('additional-tasks.applications', compact('applications', 'selectedTask'));
    }
}
