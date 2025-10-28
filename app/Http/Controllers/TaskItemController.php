<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskUser;
use App\Models\TemplateTask;
use App\Models\TemplateTaskUser;
use App\Services\Tasks\TaskItemService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Controller لإدارة بنود المهام
 * يدعم المهام العادية ومهام القوالب
 */
class TaskItemController extends Controller
{
    protected $taskItemService;

    public function __construct(TaskItemService $taskItemService)
    {
        $this->taskItemService = $taskItemService;
        $this->middleware('auth');
    }

    /**
     * إضافة بند جديد للمهمة الأساسية (Tasks)
     * POST /tasks/{task}/items
     */
    public function addToTask(Request $request, Task $task)
    {
        Log::info('✅ TaskItemController::addToTask called', [
            'task_id' => $task->id,
            'request_data' => $request->all(),
            'request_url' => request()->url()
        ]);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            Log::warning('❌ Validation failed for addToTask', [
                'errors' => $validator->errors()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->taskItemService->addItemToTask($task, $request->only(['title', 'description']));

        Log::info($result['success'] ? '✅ Item added successfully' : '❌ Failed to add item', [
            'result' => $result
        ]);

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * إضافة بند جديد لمهمة القالب (TemplateTask)
     * POST /template-tasks/{templateTask}/items
     */
    public function addToTemplateTask(Request $request, TemplateTask $templateTask)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->taskItemService->addItemToTemplateTask($templateTask, $request->only(['title', 'description']));

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * تحديث بند في المهمة الأساسية
     * PUT /tasks/{task}/items/{itemId}
     */
    public function updateTaskItem(Request $request, Task $task, string $itemId)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'order' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->taskItemService->updateTaskItem($task, $itemId, $request->only(['title', 'description', 'order']));

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * حذف بند من المهمة الأساسية
     * DELETE /tasks/{task}/items/{itemId}
     */
    public function deleteTaskItem(Task $task, string $itemId)
    {
        $result = $this->taskItemService->deleteTaskItem($task, $itemId);

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * تحديث حالة بند للمستخدم (تم/لم يتم/لا ينطبق) - مهمة عادية
     * PUT /task-users/{taskUser}/items/{itemId}/status
     */
    public function updateTaskUserItemStatus(Request $request, TaskUser $taskUser, string $itemId)
    {
        // التحقق من الصلاحيات
        if ($taskUser->user_id !== Auth::id() && !Auth::user()->hasRole(['hr', 'admin', 'company_manager'])) {
            return response()->json([
                'success' => false,
                'message' => 'غير مسموح لك بتحديث هذا البند'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,completed,not_applicable',
            'note' => 'nullable|string|max:500|required_if:status,not_applicable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->taskItemService->updateTaskUserItemStatus(
            $taskUser,
            $itemId,
            $request->status,
            $request->note
        );

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * تحديث حالة بند للمستخدم (تم/لم يتم/لا ينطبق) - مهمة قالب
     * PUT /template-task-users/{templateTaskUser}/items/{itemId}/status
     */
    public function updateTemplateTaskUserItemStatus(Request $request, TemplateTaskUser $templateTaskUser, string $itemId)
    {
        // التحقق من الصلاحيات
        if ($templateTaskUser->user_id !== Auth::id() && !Auth::user()->hasRole(['hr', 'admin', 'company_manager'])) {
            return response()->json([
                'success' => false,
                'message' => 'غير مسموح لك بتحديث هذا البند'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,completed,not_applicable',
            'note' => 'nullable|string|max:500|required_if:status,not_applicable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->taskItemService->updateTemplateTaskUserItemStatus(
            $templateTaskUser,
            $itemId,
            $request->status,
            $request->note
        );

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * الحصول على بنود المهمة الأساسية
     * GET /tasks/{task}/items
     */
    public function getTaskItems($taskId)
    {
        Log::info('✅ TaskItemController::getTaskItems called', [
            'received_task_id' => $taskId,
            'request_url' => request()->url(),
            'request_method' => request()->method()
        ]);

        // البحث عن Task (مع withTrashed للتحقق من المحذوفة)
        $task = Task::withTrashed()->find($taskId);

        if (!$task) {
            Log::error('❌ Task not found', [
                'task_id' => $taskId
            ]);

            return response()->json([
                'success' => false,
                'message' => "المهمة رقم {$taskId} غير موجودة"
            ], 404);
        }

        if ($task->trashed()) {
            Log::warning('⚠️ Task is soft deleted', [
                'task_id' => $taskId,
                'deleted_at' => $task->deleted_at
            ]);

            return response()->json([
                'success' => false,
                'message' => "المهمة رقم {$taskId} محذوفة"
            ], 410);
        }

        Log::info('✅ Task found successfully', [
            'task_id' => $task->id,
            'task_name' => $task->name,
            'items_count' => count($task->items ?? [])
        ]);

        return response()->json([
            'success' => true,
            'items' => $task->items ?? []
        ]);
    }

    /**
     * الحصول على بنود مهمة القالب
     * GET /template-tasks/{templateTask}/items
     */
    public function getTemplateTaskItems($templateTaskId)
    {
        Log::info('✅ TaskItemController::getTemplateTaskItems called', [
            'received_template_task_id' => $templateTaskId,
            'request_url' => request()->url(),
            'request_method' => request()->method()
        ]);

        // البحث عن TemplateTask
        $templateTask = TemplateTask::find($templateTaskId);

        if (!$templateTask) {
            Log::error('❌ TemplateTask not found', [
                'template_task_id' => $templateTaskId
            ]);

            return response()->json([
                'success' => false,
                'message' => "مهمة القالب رقم {$templateTaskId} غير موجودة"
            ], 404);
        }

        Log::info('✅ TemplateTask found successfully', [
            'template_task_id' => $templateTask->id,
            'template_task_name' => $templateTask->name,
            'items_count' => count($templateTask->items ?? [])
        ]);

        return response()->json([
            'success' => true,
            'items' => $templateTask->items ?? []
        ]);
    }

    /**
     * الحصول على بنود المستخدم مع حالاتها - مهمة عادية
     * GET /task-users/{taskUser}/items
     */
    public function getTaskUserItems(TaskUser $taskUser)
    {
        // التحقق من الصلاحيات
        if ($taskUser->user_id !== Auth::id() && !Auth::user()->hasRole(['hr', 'admin', 'company_manager'])) {
            return response()->json([
                'success' => false,
                'message' => 'غير مسموح لك بعرض هذه البنود'
            ], 403);
        }

        // ✅ تحميل المهمة الأساسية للتحقق من البنود
        $taskUser->load('task');

        // ✅ إذا لم توجد بنود في TaskUser، نسخها من Task
        if (empty($taskUser->items) && $taskUser->task && !empty($taskUser->task->items)) {
            Log::info('⚠️ TaskUser has no items, copying from Task', [
                'task_user_id' => $taskUser->id,
                'task_id' => $taskUser->task_id,
                'task_items_count' => count($taskUser->task->items)
            ]);

            $this->taskItemService->copyItemsToTaskUser($taskUser->task, $taskUser);
            $taskUser->refresh(); // إعادة تحميل البيانات

            Log::info('✅ Items copied successfully', [
                'task_user_id' => $taskUser->id,
                'items_count' => count($taskUser->items ?? [])
            ]);
        }

        $progress = $this->taskItemService->calculateTaskProgress($taskUser);

        return response()->json([
            'success' => true,
            'items' => $taskUser->items ?? [],
            'progress' => $progress
        ]);
    }

    /**
     * الحصول على بنود المستخدم مع حالاتها - مهمة قالب
     * GET /template-task-users/{templateTaskUser}/items
     */
    public function getTemplateTaskUserItems(TemplateTaskUser $templateTaskUser)
    {
        // التحقق من الصلاحيات
        if ($templateTaskUser->user_id !== Auth::id() && !Auth::user()->hasRole(['hr', 'admin', 'company_manager'])) {
            return response()->json([
                'success' => false,
                'message' => 'غير مسموح لك بعرض هذه البنود'
            ], 403);
        }

        // ✅ تحميل المهمة الأساسية للتحقق من البنود
        $templateTaskUser->load('templateTask');

        // ✅ إذا لم توجد بنود في TemplateTaskUser، نسخها من TemplateTask
        if (empty($templateTaskUser->items) && $templateTaskUser->templateTask && !empty($templateTaskUser->templateTask->items)) {
            Log::info('⚠️ TemplateTaskUser has no items, copying from TemplateTask', [
                'template_task_user_id' => $templateTaskUser->id,
                'template_task_id' => $templateTaskUser->template_task_id,
                'template_task_items_count' => count($templateTaskUser->templateTask->items)
            ]);

            $this->taskItemService->copyItemsToTemplateTaskUser($templateTaskUser->templateTask, $templateTaskUser);
            $templateTaskUser->refresh(); // إعادة تحميل البيانات

            Log::info('✅ Items copied successfully', [
                'template_task_user_id' => $templateTaskUser->id,
                'items_count' => count($templateTaskUser->items ?? [])
            ]);
        }

        $progress = $this->taskItemService->calculateTemplateTaskProgress($templateTaskUser);

        return response()->json([
            'success' => true,
            'items' => $templateTaskUser->items ?? [],
            'progress' => $progress
        ]);
    }

    /**
     * حساب نسبة الإنجاز للمهمة
     * GET /task-users/{taskUser}/items/progress
     */
    public function getTaskProgress(TaskUser $taskUser)
    {
        // التحقق من الصلاحيات
        if ($taskUser->user_id !== Auth::id() && !Auth::user()->hasRole(['hr', 'admin', 'company_manager'])) {
            return response()->json([
                'success' => false,
                'message' => 'غير مسموح لك بعرض هذا التقدم'
            ], 403);
        }

        $progress = $this->taskItemService->calculateTaskProgress($taskUser);

        return response()->json([
            'success' => true,
            'progress' => $progress
        ]);
    }

    /**
     * حساب نسبة الإنجاز لمهمة القالب
     * GET /template-task-users/{templateTaskUser}/items/progress
     */
    public function getTemplateTaskProgress(TemplateTaskUser $templateTaskUser)
    {
        // التحقق من الصلاحيات
        if ($templateTaskUser->user_id !== Auth::id() && !Auth::user()->hasRole(['hr', 'admin', 'company_manager'])) {
            return response()->json([
                'success' => false,
                'message' => 'غير مسموح لك بعرض هذا التقدم'
            ], 403);
        }

        $progress = $this->taskItemService->calculateTemplateTaskProgress($templateTaskUser);

        return response()->json([
            'success' => true,
            'progress' => $progress
        ]);
    }
}

