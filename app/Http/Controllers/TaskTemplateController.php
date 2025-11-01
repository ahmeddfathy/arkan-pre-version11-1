<?php

namespace App\Http\Controllers;

use App\Models\TaskTemplate;
use App\Models\CompanyService;
use App\Models\Task;
use App\Models\TemplateTask;
use App\Models\TemplateTaskUser;
use App\Traits\SeasonAwareTrait;
use App\Traits\HasNTPTime;
use App\Services\Tasks\TaskTimeSplitService;
use App\Services\ProjectManagement\ProjectServiceStatusService;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TaskTemplateController extends Controller
{
    use SeasonAwareTrait, HasNTPTime;

    protected $taskTimeSplitService;
    protected $projectServiceStatusService;
    protected $taskDeliveryNotificationService;

    public function __construct(
        TaskTimeSplitService $taskTimeSplitService,
        ProjectServiceStatusService $projectServiceStatusService,
        \App\Services\Notifications\TaskDeliveryNotificationService $taskDeliveryNotificationService
    ) {
        $this->taskTimeSplitService = $taskTimeSplitService;
        $this->projectServiceStatusService = $projectServiceStatusService;
        $this->taskDeliveryNotificationService = $taskDeliveryNotificationService;
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        // تسجيل النشاط - دخول صفحة قوالب المهام
        if (\Illuminate\Support\Facades\Auth::check()) {
            activity()
                ->causedBy(\Illuminate\Support\Facades\Auth::user())
                ->withProperties([
                    'action_type' => 'view_index',
                    'page' => 'task_templates_index',
                    'filters' => $request->all(),
                    'viewed_at' => now()->toDateTimeString(),
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip()
                ])
                ->log('دخل على صفحة قوالب المهام');
        }

        $query = TaskTemplate::with('service');

        if ($request->has('service_id') && $request->service_id) {
            $query->where('service_id', $request->service_id);
        }

        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $templates = $query->orderBy('service_id')->orderBy('order')->paginate(15);
        $services = CompanyService::orderBy('name')->get();

        return view('task-templates.index', compact('templates', 'services'));
    }

    public function create()
    {
        $services = CompanyService::orderBy('name')->get();
        return view('task-templates.create', compact('services'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'service_id' => 'required|exists:company_services,id',
            'estimated_hours' => 'required|integer|min:0',
            'estimated_minutes' => 'required|integer|min:0|max:59',
            'order' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $isActive = false;
        if ($request->has('is_active')) {
            $value = $request->input('is_active');
            $isActive = in_array($value, [true, 1, '1', 'true', 'on'], true);
        }

        TaskTemplate::create([
            'name' => $request->name,
            'description' => $request->description,
            'service_id' => $request->service_id,
            'estimated_hours' => $request->estimated_hours,
            'estimated_minutes' => $request->estimated_minutes,
            'order' => $request->order,
            'is_active' => $isActive,
        ]);

        return redirect()->route('task-templates.index')
            ->with('success', 'تم إنشاء قالب المهام بنجاح');
    }

    public function show(TaskTemplate $taskTemplate)
    {
        // تسجيل النشاط - عرض تفاصيل قالب المهام
        if (\Illuminate\Support\Facades\Auth::check()) {
            activity()
                ->performedOn($taskTemplate)
                ->causedBy(\Illuminate\Support\Facades\Auth::user())
                ->withProperties([
                    'template_name' => $taskTemplate->name,
                    'service_id' => $taskTemplate->service_id,
                    'service_name' => $taskTemplate->service ? $taskTemplate->service->name : null,
                    'action_type' => 'view',
                    'viewed_at' => now()->toDateTimeString(),
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip()
                ])
                ->log('شاهد قالب المهام');
        }

        $taskTemplate->load(['service', 'templateTasks.role']);

        // الحصول على أدوار القسم المرتبط بالخدمة
        $departmentRoles = collect();
        if ($taskTemplate->service && $taskTemplate->service->department) {
            $departmentRoles = \App\Models\DepartmentRole::with('role')
                ->where('department_name', $taskTemplate->service->department)
                ->orderBy('hierarchy_level', 'desc')
                ->get();
        }

        return view('task-templates.show', compact('taskTemplate', 'departmentRoles'));
    }

    public function edit(TaskTemplate $taskTemplate)
    {
        $services = CompanyService::orderBy('name')->get();
        return view('task-templates.edit', compact('taskTemplate', 'services'));
    }

    public function update(Request $request, TaskTemplate $taskTemplate)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'service_id' => 'required|exists:company_services,id',
            'estimated_hours' => 'required|integer|min:0',
            'estimated_minutes' => 'required|integer|min:0|max:59',
            'order' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $isActive = false;
        if ($request->has('is_active')) {
            $value = $request->input('is_active');
            $isActive = in_array($value, [true, 1, '1', 'true', 'on'], true);
        }

        $taskTemplate->update([
            'name' => $request->name,
            'description' => $request->description,
            'service_id' => $request->service_id,
            'estimated_hours' => $request->estimated_hours,
            'estimated_minutes' => $request->estimated_minutes,
            'order' => $request->order,
            'is_active' => $isActive,
        ]);

        return redirect()->route('task-templates.index')
            ->with('success', 'تم تحديث قالب المهام بنجاح');
    }

    public function destroy(TaskTemplate $taskTemplate)
    {
        $taskTemplate->delete();
        return redirect()->route('task-templates.index')
            ->with('success', 'تم حذف قالب المهام بنجاح');
    }

    public function getTemplatesForService($serviceId)
    {
        $templates = TaskTemplate::where('service_id', $serviceId)
            ->where('is_active', true)
            ->orderBy('order')
            ->get();

        return response()->json($templates);
    }

    public function toggleStatus($id)
    {
        $template = TaskTemplate::findOrFail($id);
        $template->is_active = !$template->is_active;
        $template->save();

        return redirect()->back()->with('success', 'تم تغيير حالة القالب بنجاح');
    }

    public function cloneTemplate($id)
    {
        $originalTemplate = TaskTemplate::findOrFail($id);

        $newTemplate = $originalTemplate->replicate();
        $newTemplate->name = $originalTemplate->name . ' (نسخة)';
        $newTemplate->save();

        return redirect()->route('task-templates.edit', $newTemplate)
            ->with('success', 'تم نسخ القالب بنجاح، يمكنك تعديله الآن');
    }

    public function storeTask(Request $request, TaskTemplate $template)
    {
        $isFlexible = false;
        if ($request->has('is_flexible_time')) {
            $value = $request->input('is_flexible_time');
            $isFlexible = in_array($value, [true, 1, '1', 'true', 'on'], true);
        }

        $validationRules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'role_id' => 'nullable|exists:roles,id',
            'order' => 'required|integer|min:0',
            'points' => 'nullable|integer|min:0|max:1000',
            'is_active' => 'nullable|in:0,1,true,false',
            'is_flexible_time' => 'nullable|in:0,1,true,false',
        ];

        if (!$isFlexible) {
            $validationRules['estimated_hours'] = 'required|integer|min:0';
            $validationRules['estimated_minutes'] = 'required|integer|min:0|max:59';
        }

        $validator = Validator::make($request->all(), $validationRules);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // 🔥 التحقق من توزيع الوقت إذا لم تكن المهمة مرنة
        if (!$isFlexible) {
            $hoursToAdd = (int)$request->estimated_hours;
            $minutesToAdd = (int)$request->estimated_minutes;
            $roleId = $request->role_id;

            // تسجيل للمتابعة والتشخيص
            Log::info('Adding new template task validation', [
                'template_id' => $template->id,
                'template_total_minutes' => $template->getTotalEstimatedMinutesAttribute(),
                'hours_to_add' => $hoursToAdd,
                'minutes_to_add' => $minutesToAdd,
                'role_id' => $roleId,
                'role_id_is_null' => is_null($roleId),
                'role_id_is_empty_string' => $roleId === '',
                'request_data' => $request->all()
            ]);

            $timeValidation = $template->validateTimeDistributionForNewTask($hoursToAdd, $minutesToAdd, $roleId);

            Log::info('Time validation result', [
                'validation_result' => $timeValidation,
                'template_id' => $template->id
            ]);

            if (!$timeValidation['valid']) {
                return response()->json([
                    'success' => false,
                    'errors' => ['time_distribution' => [$timeValidation['message']]]
                ], 422);
            }
        }

        $isActive = false;
        if ($request->has('is_active')) {
            $value = $request->input('is_active');
            $isActive = in_array($value, [true, 1, '1', 'true', 'on'], true);
        }

        $isFlexibleTime = false;
        if ($request->has('is_flexible_time')) {
            $value = $request->input('is_flexible_time');
            $isFlexibleTime = in_array($value, [true, 1, '1', 'true', 'on'], true);
        }

        $taskData = [
            'task_template_id' => $template->id,
            'name' => $request->name,
            'description' => $request->description,
            'role_id' => $request->role_id,
            'order' => $request->order,
            'points' => $request->points ?? 10,
            'is_active' => $isActive,
            'is_flexible_time' => $isFlexibleTime,
        ];

        if (!$isFlexibleTime) {
            $taskData['estimated_hours'] = $request->estimated_hours;
            $taskData['estimated_minutes'] = $request->estimated_minutes;
        } else {
            $taskData['estimated_hours'] = null;
            $taskData['estimated_minutes'] = null;
        }

        $templateTask = TemplateTask::create($taskData);

        // 📋 حفظ البنود إذا تم إرسالها
        if ($request->has('items') && is_array($request->items)) {
            $templateTask->items = $request->items;
            $templateTask->save();
        }

        // إعادة تحميل المهمة مع العلاقات
        $templateTask->load('role');

        return response()->json([
            'success' => true,
            'task' => $templateTask,
            'message' => 'تم إضافة المهمة بنجاح',
        ]);
    }

    public function updateTask(Request $request, TemplateTask $templateTask)
    {
        $isFlexible = false;
        if ($request->has('is_flexible_time')) {
            $value = $request->input('is_flexible_time');
            $isFlexible = in_array($value, [true, 1, '1', 'true', 'on'], true);
        }

        $validationRules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'role_id' => 'nullable|exists:roles,id',
            'order' => 'required|integer|min:0',
            'points' => 'nullable|integer|min:0|max:1000',
            'is_active' => 'nullable|in:0,1,true,false',
            'is_flexible_time' => 'nullable|in:0,1,true,false',
        ];

        if (!$isFlexible) {
            $validationRules['estimated_hours'] = 'required|integer|min:0';
            $validationRules['estimated_minutes'] = 'required|integer|min:0|max:59';
        }

        $validator = Validator::make($request->all(), $validationRules);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // 🔥 التحقق من توزيع الوقت إذا لم تكن المهمة مرنة
        if (!$isFlexible) {
            $hoursToUpdate = (int)$request->estimated_hours;
            $minutesToUpdate = (int)$request->estimated_minutes;
            $roleId = $request->role_id;

            // تسجيل للمتابعة والتشخيص
            Log::info('Updating template task validation', [
                'template_task_id' => $templateTask->id,
                'template_id' => $templateTask->template->id,
                'template_total_minutes' => $templateTask->template->getTotalEstimatedMinutesAttribute(),
                'hours_to_update' => $hoursToUpdate,
                'minutes_to_update' => $minutesToUpdate,
                'role_id' => $roleId,
                'old_role_id' => $templateTask->role_id,
                'old_hours' => $templateTask->estimated_hours,
                'old_minutes' => $templateTask->estimated_minutes,
                'request_data' => $request->all()
            ]);

            // استبعاد المهمة الحالية من الحساب لأننا نحدثها
            $timeValidation = $templateTask->template->validateTimeDistributionForNewTask(
                $hoursToUpdate,
                $minutesToUpdate,
                $roleId,
                $templateTask->id // استبعاد المهمة الحالية
            );

            Log::info('Update time validation result', [
                'validation_result' => $timeValidation,
                'template_task_id' => $templateTask->id
            ]);

            if (!$timeValidation['valid']) {
                return response()->json([
                    'success' => false,
                    'errors' => ['time_distribution' => [$timeValidation['message']]]
                ], 422);
            }
        }

        $isActive = false;
        if ($request->has('is_active')) {
            $value = $request->input('is_active');
            $isActive = in_array($value, [true, 1, '1', 'true', 'on'], true);
        }

        $isFlexibleTime = false;
        if ($request->has('is_flexible_time')) {
            $value = $request->input('is_flexible_time');
            $isFlexibleTime = in_array($value, [true, 1, '1', 'true', 'on'], true);
        }

        $updateData = [
            'name' => $request->name,
            'description' => $request->description,
            'role_id' => $request->role_id,
            'order' => $request->order,
            'points' => $request->points ?? 10,
            'is_active' => $isActive,
            'is_flexible_time' => $isFlexibleTime,
        ];

        if (!$isFlexibleTime) {
            $updateData['estimated_hours'] = $request->estimated_hours;
            $updateData['estimated_minutes'] = $request->estimated_minutes;
        } else {
            $updateData['estimated_hours'] = null;
            $updateData['estimated_minutes'] = null;
        }

        $templateTask->update($updateData);

        // 📋 تحديث البنود إذا تم إرسالها
        if ($request->has('items')) {
            $templateTask->items = is_array($request->items) ? $request->items : [];
            $templateTask->save();
        }

        // إعادة تحميل المهمة مع العلاقات
        $templateTask->load('role');

        return response()->json([
            'success' => true,
            'task' => $templateTask,
            'message' => 'تم تحديث المهمة بنجاح',
        ]);
    }

    public function destroyTask(TemplateTask $templateTask)
    {
        $templateTask->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف المهمة بنجاح',
        ]);
    }

    public function updateTaskStatus(Request $request, $templateTaskUserId)
    {
        $task = TemplateTaskUser::findOrFail($templateTaskUserId);

        if ($task->user_id != $request->user()->id) {
            Log::warning('Unauthorized template task status update attempt', [
                'template_task_user_id' => $templateTaskUserId,
                'task_owner_id' => $task->user_id,
                'attempted_by_user_id' => $request->user()->id,
                'attempted_status' => $request->input('status')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بتحديث هذه المهمة - هذه المهمة مخصصة لمستخدم آخر',
                'no_change' => true
            ], 403);
        }

        // منع صاحب السجل الأصلي من تغيير حالة المهمة بعد نقلها لشخص آخر
        if ($task->is_transferred === true) {
            Log::info('Blocked template task status update on transferred-from record', [
                'template_task_user_id' => $templateTaskUserId,
                'user_id' => $request->user()->id,
                'status_attempt' => $request->input('status')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'تم نقل هذه المهمة من حسابك - لا يمكنك تغيير حالتها',
                'no_change' => true
            ], 403);
        }

        // التحقق من إمكانية تحديث حالة المهمة بناءً على حالة المشروع
        if (!$task->canUpdateStatus()) {
            $errorMessage = $task->getStatusUpdateErrorMessage();
            
            Log::warning('Blocked template task status update due to cancelled project', [
                'template_task_user_id' => $templateTaskUserId,
                'project_id' => $task->project_id,
                'project_status' => $task->project?->status,
                'attempted_status' => $request->input('status'),
                'user_id' => $request->user()->id
            ]);

            return response()->json([
                'success' => false,
                'message' => $errorMessage ?: 'لا يمكن تحديث حالة المهمة لأن المشروع تم إلغاؤه',
                'no_change' => true
            ], 403);
        }

        $status = $request->input('status');
        $validStatuses = ['new', 'in_progress', 'paused', 'completed'];

        if (!in_array($status, $validStatuses)) {
            return response()->json(['error' => 'حالة غير صالحة'], 400);
        }

        $previousStatus = $task->status;

        if ($previousStatus === $status) {
            return response()->json([
                'success' => true,
                'message' => 'المهمة بالفعل في هذه الحالة',
                'task' => $task,
                'minutesSpent' => $task->actual_minutes ?? 0,
                'no_change' => true
            ]);
        }

        // 📋 التحقق من البنود قبل التحويل لـ "مكتمل"
        if ($status === 'completed') {
            $itemsValidation = $this->validateTemplateTaskItems($task);
            if (!$itemsValidation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $itemsValidation['message'],
                    'pending_items' => $itemsValidation['pending_items'] ?? []
                ], 400);
            }
        }

        $now = $this->getCurrentCairoTime();

        if ($previousStatus == 'in_progress' && in_array($status, ['paused', 'completed'])) {
            if ($task->started_at) {
                $startTime = Carbon::parse($task->started_at);

                // 🔥 استخدام TaskTimeSplitService لحساب الوقت الصحيح مع تحديث checkpoint للمهام الأخرى
                $minutesSpent = $this->taskTimeSplitService->calculateAndUpdateCheckpoint(
                    $task->id,
                    true, // هذه مهمة قالب
                    $startTime,
                    $now,
                    $task->user_id
                );

                $task->actual_minutes = ($task->actual_minutes ?? 0) + $minutesSpent;

                Log::info("Template task time calculated with splitting", [
                    'template_task_user_id' => $task->id,
                    'user_id' => $task->user_id,
                    'original_minutes' => $startTime->diffInMinutes($now),
                    'split_minutes' => $minutesSpent,
                    'total_minutes' => $task->actual_minutes
                ]);
            }
        }

        $task->status = $status;

        if ($status == 'in_progress') {
            $task->started_at = $now;
            $task->paused_at = null;
        } else if ($status == 'paused') {
            $task->paused_at = $now;
        } else if ($status == 'completed') {
            $task->completed_at = $now;
        }

        if (!$task->season_id) {
            if ($task->project_id) {
                $project = \App\Models\Project::find($task->project_id);
                if ($project && $project->season_id) {
                    $task->season_id = $project->season_id;
                } else {
                    $task->season_id = $this->getCurrentSeasonId();
                }
            } else {
                $task->season_id = $this->getCurrentSeasonId();
            }
        }

        $task->save();

        $task->refresh();

        // 🔥 تحديث حالة الخدمة بناءً على حالة مهام القوالب
        if ($task->project && $task->templateTask && $task->templateTask->template && $task->templateTask->template->service_id) {
            $this->projectServiceStatusService->updateServiceStatus(
                $task->project,
                $task->templateTask->template->service_id
            );
        }

        // 🔔 إرسال إشعارات عند اكتمال التاسك
        if ($status === 'completed') {
            try {
                $this->taskDeliveryNotificationService->notifyTaskCompleted($task);
            } catch (\Exception $e) {
                Log::warning('Failed to send template task completion notifications', [
                    'template_task_user_id' => $task->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث حالة المهمة بنجاح',
            'task' => $task,
            'minutesSpent' => $task->actual_minutes ?? 0
        ]);
    }

    /**
     * التحقق من أن جميع البنود تم تحديد حالتها
     *
     * @param TemplateTaskUser $templateTaskUser
     * @return array
     */
    private function validateTemplateTaskItems(\App\Models\TemplateTaskUser $templateTaskUser): array
    {
        $items = $templateTaskUser->items ?? [];

        // إذا لم يكن هناك بنود، التحقق ناجح
        if (empty($items)) {
            return ['valid' => true];
        }

        $pendingItems = [];

        foreach ($items as $item) {
            $status = $item['status'] ?? 'pending';

            // إذا كان البند لا يزال pending (لم يتم تحديد حالته)
            if ($status === 'pending') {
                $pendingItems[] = [
                    'id' => $item['id'] ?? '',
                    'title' => $item['title'] ?? 'بند بدون عنوان',
                    'description' => $item['description'] ?? ''
                ];
            }
        }

        if (!empty($pendingItems)) {
            $count = count($pendingItems);
            $itemsList = implode('، ', array_column($pendingItems, 'title'));

            return [
                'valid' => false,
                'message' => "⚠️ لا يمكن إكمال المهمة! يجب تحديد حالة جميع البنود أولاً.\n\nالبنود المتبقية ({$count}): {$itemsList}",
                'pending_items' => $pendingItems
            ];
        }

        return ['valid' => true];
    }
}

