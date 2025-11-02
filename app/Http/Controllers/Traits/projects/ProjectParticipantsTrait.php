<?php

namespace App\Http\Controllers\Traits\Projects;

use App\Models\Project;
use App\Models\ProjectNote;
use App\Models\ProjectServiceUser;
use App\Models\TemplateTaskUser;
use App\Models\TaskUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait ProjectParticipantsTrait
{
    protected $participantService;
    protected $taskService;
    protected $notesService;
    protected $deliveryService;
    protected $authorizationService;
    protected $roleCheckService;

    /**
     * تعيين مشاركين في المشروع
     */
    public function assignParticipants(Request $request, Project $project)
    {
        // فحص صلاحيات إدارة المشاركين
        $canManage = $this->authorizationService->canManageProjectParticipants($project);

        if (!$canManage) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مسموح لك بإدارة مشاركي المشروع'
                ], 403);
            }
            return back()->with('error', 'غير مسموح لك بإدارة مشاركي المشروع');
        }

        if ($request->has('team_id') && $request->team_id) {
            $serviceId = $request->input('service_id');
            $teamId = $request->input('team_id');

            $result = $this->participantService->assignTeamOwner($project, $serviceId, $teamId);

            if ($request->ajax()) {
                return response()->json($result);
            }

            if ($result['success']) {
                return redirect()->route('projects.show', $project)
                    ->with('success', $result['message']);
            } else {
                return back()->with('error', $result['message']);
            }
        } else {
            $data = $request->input('participants', []);
            $deadlines = $request->input('deadlines', []);
            $projectShares = $request->input('project_shares', []);
            $participantRoles = $request->input('participant_role', []);

            $result = $this->participantService->assignParticipants($project, $data, [], $deadlines, $projectShares, $participantRoles);

            if ($request->ajax()) {
                return response()->json($result);
            }

            if ($result['success']) {
                return redirect()->route('projects.show', $project)
                    ->with('success', $result['message']);
            } else {
                return back()->with('error', $result['message']);
            }
        }
    }

    /**
     * إزالة مشارك من المشروع
     */
    public function removeParticipant(Request $request, Project $project)
    {
        // فحص صلاحيات إدارة المشاركين
        $canManage = $this->authorizationService->canManageProjectParticipants($project);

        if (!$canManage) {
            return response()->json([
                'success' => false,
                'message' => 'غير مسموح لك بحذف مشاركي المشروع'
            ], 403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'service_id' => 'required|exists:company_services,id'
        ]);

        $result = $this->participantService->removeParticipant(
            $project,
            $request->user_id,
            $request->service_id
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * تحديث دور المشارك
     */
    public function updateParticipantRole(Request $request, Project $project)
    {
        // فحص صلاحيات إدارة المشاركين
        $canManage = $this->authorizationService->canManageProjectParticipants($project);

        if (!$canManage) {
            return response()->json([
                'success' => false,
                'message' => 'غير مسموح لك بإدارة مشاركي المشروع'
            ], 403);
        }

        $request->validate([
            'participant_id' => 'required|exists:project_service_user,id',
            'role_id' => 'required|exists:roles,id'
        ]);

        try {
            $participant = ProjectServiceUser::findOrFail($request->participant_id);
            $participant->update(['role_id' => $request->role_id]);

            activity()
                ->causedBy(Auth::user())
                ->performedOn($participant)
                ->log('تحديث دور المشارك في المشروع');

            return response()->json([
                'success' => true,
                'message' => 'تم تحديد الدور بنجاح'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديد الدور'
            ], 500);
        }
    }

    /**
     * تحديث موعد انتهاء المشارك
     */
    public function updateUserDeadline(Request $request, Project $project)
    {
        // فحص صلاحيات إدارة المشاركين
        $canManage = $this->authorizationService->canManageProjectParticipants($project);

        if (!$canManage) {
            return response()->json([
                'success' => false,
                'message' => 'غير مسموح لك بتحديث مواعيد المشاركين'
            ], 403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'service_id' => 'required|exists:company_services,id',
            'deadline' => 'nullable|date'
        ]);

        $result = $this->participantService->updateUserDeadline(
            $project,
            $request->user_id,
            $request->service_id,
            $request->deadline
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * جلب المهام المتاحة للقوالب
     */
    public function getAvailableTemplateTasks(Request $request, Project $project)
    {
        // فحص صلاحيات إدارة المشاركين
        $canManage = $this->authorizationService->canManageProjectParticipants($project);

        if (!$canManage) {
            return response()->json([
                'success' => false,
                'message' => 'غير مسموح لك بإدارة مهام المشاركين'
            ], 403);
        }

        $request->validate([
            'service_id' => 'required|exists:company_services,id',
            'user_id' => 'required|exists:users,id'
        ]);

        $result = $this->participantService->getAvailableTemplateTasks(
            $request->service_id,
            $project->id,
            $request->user_id
        );

        return response()->json($result);
    }

    /**
     * تعيين مهام قوالب محددة
     */
    public function assignSelectedTemplateTasks(Request $request, Project $project)
    {
        // فحص صلاحيات إدارة المشاركين
        $canManage = $this->authorizationService->canManageProjectParticipants($project);

        if (!$canManage) {
            return response()->json([
                'success' => false,
                'message' => 'غير مسموح لك بإدارة مهام المشاركين'
            ], 403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'service_id' => 'required|exists:company_services,id',
            'selected_task_ids' => 'required|array',
            'selected_task_ids.*' => 'exists:template_tasks,id',
            'task_deadlines' => 'nullable|array',
            'task_deadlines.*' => 'nullable|date|after:now'
        ]);

        $result = $this->participantService->assignSelectedTemplateTasks(
            $project,
            $request->user_id,
            $request->service_id,
            $request->selected_task_ids,
            $request->task_deadlines
        );

        return response()->json($result);
    }

    /**
     * جلب مهام القوالب
     */
    public function getTemplateTasks(Project $project)
    {
        return response()->json($this->taskService->getTemplateTasks($project));
    }

    /**
     * جلب المهام العادية
     */
    public function getRegularTasks(Project $project)
    {
        return response()->json($this->taskService->getRegularTasks($project));
    }

    /**
     * جلب تفاصيل المهمة
     */
    public function getTaskDetails($type, $taskUserId)
    {
        try {
            $currentUser = \Illuminate\Support\Facades\Auth::user();
            $currentUserId = $currentUser ? $currentUser->id : null;

            \Illuminate\Support\Facades\Log::info('getTaskDetails called', [
                'type' => $type,
                'taskUserId' => $taskUserId,
                'user_id' => $currentUserId
            ]);

            if ($type === 'template') {
                // أولاً محاولة البحث بـ TemplateTaskUser ID
                $taskUser = TemplateTaskUser::with(['templateTask.template.service', 'user', 'project'])
                    ->find($taskUserId);

                if (!$taskUser) {
                    \Illuminate\Support\Facades\Log::info('TemplateTaskUser not found by taskUserId, trying by task_id', [
                        'taskUserId' => $taskUserId
                    ]);

                    // إذا لم نجد TemplateTaskUser، نحاول البحث بـ template_task_id
                    $templateTask = \App\Models\TemplateTask::find($taskUserId);
                    if ($templateTask) {
                        \Illuminate\Support\Facades\Log::info('Found template task by ID, now looking for TemplateTaskUser', [
                            'template_task_id' => $taskUserId,
                            'task_name' => $templateTask->name,
                            'current_user_id' => $currentUserId
                        ]);

                        // البحث عن TemplateTaskUser للمستخدم الحالي
                        $taskUser = TemplateTaskUser::with(['templateTask.template.service', 'user', 'project'])
                            ->where('template_task_id', $taskUserId)
                            ->where('user_id', $currentUserId)
                            ->first();

                        if (!$taskUser) {
                            \Illuminate\Support\Facades\Log::info('No TemplateTaskUser found for current user, trying any user', [
                                'template_task_id' => $taskUserId,
                                'current_user_id' => $currentUserId
                            ]);

                            // إذا لم نجد للمستخدم الحالي، نجرب أي مستخدم (للإداريين فقط)
                            if ($currentUser && $this->roleCheckService->userHasRole(['hr', 'admin'])) {
                                $taskUser = TemplateTaskUser::with(['templateTask.template.service', 'user', 'project'])
                                    ->where('template_task_id', $taskUserId)
                                    ->first();
                            }
                        }
                    }

                    if (!$taskUser) {
                        \Illuminate\Support\Facades\Log::error('Error in getTaskDetails', [
                            'type' => $type,
                            'taskUserId' => $taskUserId,
                            'error' => 'المهمة غير موجودة أو غير مُعيَّنة لك',
                            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10)
                        ]);

                        return response()->json([
                            'success' => false,
                            'message' => 'المهمة غير موجودة أو غير مُعيَّنة لك'
                        ], 404);
                    }
                }

                // التحقق من الصلاحيات للمهام القوالب
                if ($currentUser && $taskUser->user_id !== $currentUserId && !$this->roleCheckService->userHasRole(['hr', 'admin'])) {
                    \Illuminate\Support\Facades\Log::warning('User trying to access template task without permission', [
                        'current_user_id' => $currentUserId,
                        'task_user_id' => $taskUser->user_id,
                        'template_task_user_id' => $taskUser->id
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'غير مصرح لك بعرض هذه المهمة'
                    ], 403);
                }

                $task = [
                    'id' => $taskUser->template_task_id, // ✅ المهمة القالبية الأساسية
                    'template_task_user_id' => $taskUser->id, // TaskUser ID
                    'pivot_id' => $taskUser->id, // للتوافق مع الكود القديم
                    'task_user_id' => $taskUser->id, // للتوافق مع الكود القديم
                    'type' => 'template',
                    'name' => $taskUser->templateTask->name,
                    'description' => $taskUser->templateTask->description,
                    'status' => $taskUser->status,
                    'deadline' => $taskUser->deadline,
                    'estimated_hours' => $taskUser->templateTask->estimated_hours,
                    'estimated_minutes' => $taskUser->templateTask->estimated_minutes,
                    'actual_minutes' => $taskUser->actual_minutes,
                    'user' => $taskUser->user,
                    'project' => $taskUser->project,
                    'service' => $taskUser->templateTask->template->service ?? null,
                    'can_edit' => \Illuminate\Support\Facades\Auth::check() ? ($this->roleCheckService->userHasRole(['hr', 'admin']) || $taskUser->user_id === \Illuminate\Support\Facades\Auth::id()) : false,
                    'assigned_at' => $taskUser->assigned_at,
                    'started_at' => $taskUser->started_at,
                    'completed_at' => $taskUser->completed_at,
                    'is_approved' => $taskUser->is_approved,
                    'awarded_points' => $taskUser->awarded_points
                ];
            } else {
                // أولاً محاولة البحث بـ TaskUser ID
                $taskUser = TaskUser::with(['task.service', 'user', 'task.project'])
                    ->find($taskUserId);

                if (!$taskUser) {
                    \Illuminate\Support\Facades\Log::info('TaskUser not found by taskUserId, trying by task_id', [
                        'taskUserId' => $taskUserId
                    ]);

                    // إذا لم نجد TaskUser، نحاول البحث بـ task_id
                    $task = \App\Models\Task::find($taskUserId);
                    if ($task) {
                        \Illuminate\Support\Facades\Log::info('Found task by ID, now looking for TaskUser', [
                            'task_id' => $taskUserId,
                            'task_name' => $task->name,
                            'current_user_id' => $currentUserId
                        ]);

                        // البحث عن TaskUser للمستخدم الحالي
                        $taskUser = TaskUser::with(['task.service', 'user', 'task.project'])
                            ->where('task_id', $taskUserId)
                            ->where('user_id', $currentUserId)
                            ->first();

                        if (!$taskUser) {
                            \Illuminate\Support\Facades\Log::info('No TaskUser found for current user, trying any user', [
                                'task_id' => $taskUserId,
                                'current_user_id' => $currentUserId
                            ]);

                            // التحقق من جميع TaskUser المرتبطة بهذه المهمة
                            $allTaskUsers = TaskUser::where('task_id', $taskUserId)->get();
                            \Illuminate\Support\Facades\Log::info('All TaskUsers for this task', [
                                'task_id' => $taskUserId,
                                'count' => $allTaskUsers->count(),
                                'task_users' => $allTaskUsers->pluck('user_id')->toArray()
                            ]);

                            // إذا لم نجد للمستخدم الحالي، نجرب أي مستخدم (للإداريين فقط)
                            if ($currentUser && $this->roleCheckService->userHasRole(['hr', 'admin'])) {
                                $taskUser = TaskUser::with(['task.service', 'user', 'task.project'])
                                    ->where('task_id', $taskUserId)
                                    ->first();
                            }

                            // إذا لم نجد TaskUser، نعرض بيانات المهمة مباشرة للجميع
                            if (!$taskUser && $allTaskUsers->count() === 0) {
                                \Illuminate\Support\Facades\Log::info('Task exists but has no TaskUser assignments, showing task data', [
                                    'task_id' => $taskUserId,
                                    'task_name' => $task->name,
                                    'user_id' => $currentUserId
                                ]);

                                // إنشاء بيانات المهمة مباشرة من جدول Task
                                $taskData = [
                                    'id' => $task->id,
                                    'type' => 'regular',
                                    'title' => $task->name,
                                    'name' => $task->name,
                                    'description' => $task->description,
                                    'status' => $task->status ?? 'غير محدد',
                                    'due_date' => $task->due_date,
                                    'estimated_hours' => $task->estimated_hours,
                                    'estimated_minutes' => $task->estimated_minutes,
                                    'actual_minutes' => 0,
                                    'user' => null,
                                    'project' => $task->project,
                                    'service' => $task->service ?? null,
                                    'can_edit' => $currentUser && ($this->roleCheckService->userHasRole(['hr', 'admin', 'project_manager']) || $task->created_by === $currentUserId),
                                    'start_date' => $task->start_date,
                                    'started_at' => $task->start_date,
                                    'completed_date' => $task->completed_date,
                                    'is_approved' => false,
                                    'awarded_points' => null,
                                    'is_unassigned' => true, // علامة للإشارة أن المهمة غير مُعيَّنة
                                    'created_by' => $task->created_by,
                                    'points' => $task->points ?? 0
                                ];

                                \Illuminate\Support\Facades\Log::info('Returning unassigned task data for all users', [
                                    'task_id' => $taskUserId,
                                    'user_id' => $currentUserId,
                                    'can_edit' => $taskData['can_edit']
                                ]);

                                return response()->json([
                                    'success' => true,
                                    'task' => $taskData,
                                    'warning' => 'هذه المهمة غير مُعيَّنة لأي مستخدم حالياً'
                                ]);
                            }
                        }
                    }

                    if (!$taskUser) {
                        \Illuminate\Support\Facades\Log::error('Error in getTaskDetails', [
                            'type' => $type,
                            'taskUserId' => $taskUserId,
                            'error' => 'المهمة غير موجودة أو غير مُعيَّنة لك',
                            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10)
                        ]);

                        return response()->json([
                            'success' => false,
                            'message' => 'المهمة غير موجودة أو غير مُعيَّنة لك'
                        ], 404);
                    }
                }

                // التحقق من الصلاحيات للمهام العادية
                if ($currentUser && $taskUser->user_id !== $currentUserId && !$this->roleCheckService->userHasRole(['hr', 'admin'])) {
                    \Illuminate\Support\Facades\Log::warning('User trying to access task without permission', [
                        'current_user_id' => $currentUserId,
                        'task_user_id' => $taskUser->user_id,
                        'task_user_id_param' => $taskUser->id
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'غير مصرح لك بعرض هذه المهمة'
                    ], 403);
                }

                \Illuminate\Support\Facades\Log::info('🔍 Building task response', [
                    'task_user_id' => $taskUser->id,
                    'task_id' => $taskUser->task_id,
                    'task_exists' => \App\Models\Task::find($taskUser->task_id) ? 'yes' : 'no'
                ]);

                $task = [
                    'id' => $taskUser->task_id, // ✅ المهمة الأساسية
                    'task_user_id' => $taskUser->id, // TaskUser ID
                    'pivot_id' => $taskUser->id, // للتوافق مع الكود القديم
                    'type' => 'regular',
                    'title' => $taskUser->task->name,
                    'name' => $taskUser->task->name, // for consistency
                    'description' => $taskUser->task->description,
                    'status' => $taskUser->status,
                    'due_date' => $taskUser->due_date,
                    'estimated_hours' => $taskUser->estimated_hours,
                    'estimated_minutes' => $taskUser->estimated_minutes,
                    'actual_minutes' => ($taskUser->actual_hours * 60) + $taskUser->actual_minutes,
                    'user' => $taskUser->user,
                    'project' => $taskUser->task->project,
                    'service' => $taskUser->task->service ?? null,
                    'can_edit' => \Illuminate\Support\Facades\Auth::check() ? ($this->roleCheckService->userHasRole(['hr', 'admin']) || $taskUser->user_id === \Illuminate\Support\Facades\Auth::id()) : false,
                    'start_date' => $taskUser->start_date,
                    'started_at' => $taskUser->start_date, // إضافة started_at للتوافق مع المؤقت
                    'completed_date' => $taskUser->completed_date,
                    'is_approved' => $taskUser->is_approved,
                    'awarded_points' => $taskUser->awarded_points
                ];
            }

            \Illuminate\Support\Facades\Log::info('✅ getTaskDetails successful', [
                'type' => $type,
                'taskUserId' => $taskUserId,
                'returned_task_id' => $task['id'] ?? null,
                'task_user_id' => $task['task_user_id'] ?? $task['template_task_user_id'] ?? null,
                'found_task_user_id' => $taskUser->id,
                'user_id' => $currentUserId
            ]);

            return response()->json([
                'success' => true,
                'task' => $task
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Exception in getTaskDetails', [
                'type' => $type,
                'taskUserId' => $taskUserId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في تحميل بيانات المهمة'
            ], 500);
        }
    }

    /**
     * جلب الملاحظات
     */
    public function getNotes(Request $request, Project $project)
    {
        try {
            $query = $request->get('query');
            $noteType = $request->get('note_type');
            $userId = $request->get('user_id');
            $perPage = $request->get('per_page', 20);
            $targetDepartment = $request->get('target_department');

            $notes = $this->notesService->getNotes($project->id, $query, $noteType, $userId, $perPage, $targetDepartment);

            return response()->json([
                'success' => true,
                'notes' => $notes
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getNotes: ' . $e->getMessage(), [
                'project_id' => $project->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في تحميل الملاحظات: ' . $e->getMessage(),
                'notes' => ['data' => []]
            ], 500);
        }
    }

    /**
     * إضافة ملاحظة جديدة
     */
    public function storeNote(Request $request, Project $project)
    {
        $request->validate([
            'content' => 'required|string|max:5000',
            'note_type' => 'nullable|in:general,update,issue,question,solution',
            'is_important' => 'nullable|boolean',
            'is_pinned' => 'nullable|boolean',
            'target_department' => 'nullable|string|max:255',
        ]);

        try {
            $noteData = $this->notesService->storeNote($project, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة الملاحظة بنجاح',
                'note' => $noteData,
                'mentions_count' => count($noteData['mentions'] ?? []),
                'target_department' => $request->target_department
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إضافة الملاحظة: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * تحديث ملاحظة
     */
    public function updateNote(Request $request, Project $project, ProjectNote $note)
    {
        $user = Auth::user();
        $isAdmin = $this->authorizationService->isAdmin();

        if ($note->user_id !== $user->id && !$isAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'غير مسموح لك بتعديل هذه الملاحظة'
            ], 403);
        }

        $request->validate([
            'content' => 'required|string|max:5000',
            'note_type' => 'nullable|in:general,update,issue,question,solution',
            'is_important' => 'nullable|boolean',
            'is_pinned' => 'nullable|boolean',
        ]);

        try {
            $updatedNoteData = $this->notesService->updateNote($note, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الملاحظة بنجاح',
                'note' => $updatedNoteData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث الملاحظة: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * حذف ملاحظة
     */
    public function deleteNote(Project $project, ProjectNote $note)
    {
        $user = Auth::user();
        $isAdmin = $this->authorizationService->isAdmin();

        if ($note->user_id !== $user->id && !$isAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'غير مسموح لك بحذف هذه الملاحظة'
            ], 403);
        }

        try {
            $this->notesService->deleteNote($note);

            return response()->json([
                'success' => true,
                'message' => 'تم حذف الملاحظة بنجاح'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف الملاحظة: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * تثبيت/إلغاء تثبيت ملاحظة
     */
    public function toggleNotePin(Project $project, ProjectNote $note)
    {
        $user = Auth::user();
        $isAdmin = $this->authorizationService->isAdmin();

        if ($note->user_id !== $user->id && !$isAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'غير مسموح لك بتثبيت هذه الملاحظة'
            ], 403);
        }

        $isPinned = $this->notesService->toggleNotePin($note);

        return response()->json([
            'success' => true,
            'message' => $isPinned ? 'تم تثبيت الملاحظة' : 'تم إلغاء تثبيت الملاحظة',
            'is_pinned' => $isPinned
        ]);
    }

    /**
     * تمييز/إلغاء تمييز ملاحظة كمهمة
     */
    public function toggleNoteImportant(Project $project, ProjectNote $note)
    {
        $user = Auth::user();
        $isAdmin = $this->authorizationService->isAdmin();

        if ($note->user_id !== $user->id && !$isAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'غير مسموح لك بتعديل أهمية هذه الملاحظة'
            ], 403);
        }

        $isImportant = $this->notesService->toggleNoteImportant($note);

        return response()->json([
            'success' => true,
            'message' => $isImportant ? 'تم تمييز الملاحظة كمهمة' : 'تم إلغاء تمييز الملاحظة',
            'is_important' => $isImportant
        ]);
    }

    /**
     * جلب إحصائيات الملاحظات
     */
    public function getNotesStats(Project $project)
    {
        try {
            $stats = $this->notesService->getNotesStats($project->id);

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getNotesStats: ' . $e->getMessage(), [
                'project_id' => $project->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطأ في تحميل الإحصائيات',
                'stats' => [
                    'total' => 0,
                    'important' => 0,
                    'pinned' => 0,
                    'issues' => 0,
                    'solutions' => 0,
                    'recent' => 0
                ]
            ], 500);
        }
    }

    /**
     * جلب مستخدمي المشروع للإشارة إليهم
     */
    public function getProjectUsersForMentions(Project $project)
    {
        try {
            $users = $this->notesService->getProjectUsersForMentions($project);

            return response()->json([
                'success' => true,
                'users' => $users
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getProjectUsersForMentions: ' . $e->getMessage(), [
                'project_id' => $project->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطأ في تحميل المستخدمين',
                'users' => []
            ], 500);
        }
    }

    /**
     * Get participant tasks (regular, additional, template, and transferred)
     */
    public function getParticipantTasks($projectId, $userId)
    {
        try {
            $currentUser = Auth::user();

            // التحقق من الصلاحيات - فقط الأدوار المحددة يمكنها الوصول
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
                    'message' => 'غير مسموح لك بعرض تفاصيل هذا المشروع'
                ], 403);
            }

            $project = Project::findOrFail($projectId);

            // Get regular tasks with errors, revisions, and delivery status
            $regularTasks = DB::table('tasks')
                ->join('task_users', 'tasks.id', '=', 'task_users.task_id')
                ->leftJoin('task_users as original_task', 'task_users.original_task_user_id', '=', 'original_task.id')
                ->leftJoin('users as original_user', 'original_task.user_id', '=', 'original_user.id')
                ->where('tasks.project_id', $projectId)
                ->where('task_users.user_id', $userId)
                ->select(
                    'tasks.id',
                    'tasks.name',
                    'tasks.description',
                    'tasks.order',
                    'task_users.id as task_user_id',
                    'task_users.status',
                    'task_users.due_date as deadline',
                    'task_users.completed_date',
                    'task_users.estimated_hours',
                    'task_users.estimated_minutes',
                    'task_users.actual_hours',
                    'task_users.actual_minutes',
                    'task_users.is_additional_task',
                    'task_users.task_source',
                    'task_users.transfer_reason',
                    'task_users.transferred_at',
                    'task_users.is_transferred',
                    'task_users.original_task_user_id',
                    'original_user.name as transferred_from_user_name'
                )
                ->get()
                ->map(function ($task) use ($userId) {
                    // حساب حالة التسليم (قبل/بعد الـ deadline)
                    $delivery_status = null;
                    $days_late = null;

                    if ($task->deadline) {
                        $deadline = \Carbon\Carbon::parse($task->deadline);
                        $now = \Carbon\Carbon::now();

                        if ($task->completed_date) {
                            // المهمة مكتملة - قارن تاريخ الإكمال بالـ deadline
                            $completed = \Carbon\Carbon::parse($task->completed_date);

                            if ($completed->lessThanOrEqualTo($deadline)) {
                                $delivery_status = 'on_time';
                            } else {
                                $delivery_status = 'late';
                                $days_late = $completed->diffInDays($deadline);
                            }
                        } else {
                            // المهمة مش مكتملة - قارن التاريخ الحالي بالـ deadline أولاً
                            if ($now->greaterThan($deadline)) {
                                // المهمة متأخرة - سواء كانت جديدة أو مبدية
                                $delivery_status = 'late';
                                $days_late = $now->diffInDays($deadline);
                            } else {
                                // المهمة لسه في الوقت المحدد
                                if ($task->status === 'new') {
                                    // المهمة جديدة - لا تظهر حالة تسليم
                                    $delivery_status = null;
                                } else {
                                    // المهمة مبدية أو في تقدم
                                    $delivery_status = 'on_time';
                                }
                            }
                        }
                    }

                    // عدد الأخطاء
                    $errors_count = DB::table('employee_errors')
                        ->where('errorable_type', 'App\\Models\\TaskUser')
                        ->where('errorable_id', $task->task_user_id)
                        ->where('user_id', $userId)
                        ->whereNull('deleted_at')
                        ->count();

                    // عدد التعديلات
                    $revisions_count = DB::table('task_revisions')
                        ->where('task_user_id', $task->task_user_id)
                        ->count();

                    // تحديد إذا كانت المهمة منقولة للشخص أو منه
                    $is_transferred_from = $task->is_transferred; // منقول منه
                    $is_transferred_to = !empty($task->original_task_user_id); // منقول ليه

                    // حساب الوقت المقدر والفعلي
                    $estimated_time = $this->formatTime($task->estimated_hours, $task->estimated_minutes);
                    $actual_time = $this->formatTime($task->actual_hours, $task->actual_minutes);

                    return [
                        'id' => $task->id,
                        'task_user_id' => $task->task_user_id,
                        'name' => $task->name,
                        'description' => $task->description,
                        'order' => $task->order,
                        'status' => $task->status,
                        'work_status' => null,
                        'deadline' => $task->deadline,
                        'completed_date' => $task->completed_date,
                        'delivery_status' => $delivery_status,
                        'days_late' => $days_late,
                        'estimated_time' => $estimated_time,
                        'actual_time' => $actual_time,
                        'task_type' => 'regular',
                        'is_additional' => $task->is_additional_task,
                        'is_transferred_from' => $is_transferred_from,
                        'is_transferred_to' => $is_transferred_to,
                        'transferred_from_user' => $task->transferred_from_user_name,
                        'transfer_reason' => $task->transfer_reason,
                        'transferred_at' => $task->transferred_at,
                        'errors_count' => $errors_count,
                        'revisions_count' => $revisions_count,
                    ];
                });

            // Get template tasks with errors, revisions, and delivery status
            $templateTasks = DB::table('template_tasks')
                ->join('template_task_user', 'template_tasks.id', '=', 'template_task_user.template_task_id')
                ->leftJoin('template_task_user as original_task', 'template_task_user.original_template_task_user_id', '=', 'original_task.id')
                ->leftJoin('users as original_user', 'original_task.user_id', '=', 'original_user.id')
                ->where('template_task_user.project_id', $projectId)
                ->where('template_task_user.user_id', $userId)
                ->select(
                    'template_tasks.id',
                    'template_tasks.name',
                    'template_tasks.description',
                    'template_tasks.order',
                    'template_tasks.estimated_hours',
                    'template_tasks.estimated_minutes',
                    'template_task_user.id as template_task_user_id',
                    'template_task_user.status',
                    'template_task_user.deadline',
                    'template_task_user.completed_at',
                    'template_task_user.actual_minutes',
                    'template_task_user.is_additional_task',
                    'template_task_user.task_source',
                    'template_task_user.transfer_reason',
                    'template_task_user.transferred_at',
                    'template_task_user.is_transferred',
                    'template_task_user.original_template_task_user_id',
                    'original_user.name as transferred_from_user_name'
                )
                ->get()
                ->map(function ($task) use ($userId) {
                    // حساب حالة التسليم (قبل/بعد الـ deadline)
                    $delivery_status = null;
                    $days_late = null;

                    if ($task->deadline) {
                        $deadline = \Carbon\Carbon::parse($task->deadline);
                        $now = \Carbon\Carbon::now();

                        if ($task->completed_at) {
                            // المهمة مكتملة - قارن تاريخ الإكمال بالـ deadline
                            $completed = \Carbon\Carbon::parse($task->completed_at);

                            if ($completed->lessThanOrEqualTo($deadline)) {
                                $delivery_status = 'on_time';
                            } else {
                                $delivery_status = 'late';
                                $days_late = $completed->diffInDays($deadline);
                            }
                        } else {
                            // المهمة مش مكتملة - قارن التاريخ الحالي بالـ deadline أولاً
                            if ($now->greaterThan($deadline)) {
                                // المهمة متأخرة - سواء كانت جديدة أو مبدية
                                $delivery_status = 'late';
                                $days_late = $now->diffInDays($deadline);
                            } else {
                                // المهمة لسه في الوقت المحدد
                                if ($task->status === 'new') {
                                    // المهمة جديدة - لا تظهر حالة تسليم
                                    $delivery_status = null;
                                } else {
                                    // المهمة مبدية أو في تقدم
                                    $delivery_status = 'on_time';
                                }
                            }
                        }
                    }

                    // عدد الأخطاء
                    $errors_count = DB::table('employee_errors')
                        ->where('errorable_type', 'App\\Models\\TemplateTaskUser')
                        ->where('errorable_id', $task->template_task_user_id)
                        ->where('user_id', $userId)
                        ->whereNull('deleted_at')
                        ->count();

                    // عدد التعديلات
                    $revisions_count = DB::table('task_revisions')
                        ->where('template_task_user_id', $task->template_task_user_id)
                        ->count();

                    // تحديد إذا كانت المهمة منقولة للشخص أو منه
                    $is_transferred_from = $task->is_transferred; // منقول منه
                    $is_transferred_to = !empty($task->original_template_task_user_id); // منقول ليه

                    // حساب الوقت المقدر والفعلي لمهام القوالب
                    $estimated_time = $this->formatTime($task->estimated_hours, $task->estimated_minutes);
                    $actual_time = $this->formatMinutes($task->actual_minutes);

                    return [
                        'id' => $task->id,
                        'template_task_user_id' => $task->template_task_user_id,
                        'name' => $task->name,
                        'description' => $task->description,
                        'order' => $task->order,
                        'status' => $task->status,
                        'work_status' => null,
                        'deadline' => $task->deadline,
                        'completed_date' => $task->completed_at,
                        'delivery_status' => $delivery_status,
                        'days_late' => $days_late,
                        'estimated_time' => $estimated_time,
                        'actual_time' => $actual_time,
                        'task_type' => 'template',
                        'is_additional' => $task->is_additional_task,
                        'is_transferred_from' => $is_transferred_from,
                        'is_transferred_to' => $is_transferred_to,
                        'transferred_from_user' => $task->transferred_from_user_name,
                        'transfer_reason' => $task->transfer_reason,
                        'transferred_at' => $task->transferred_at,
                        'errors_count' => $errors_count,
                        'revisions_count' => $revisions_count,
                    ];
                });

            // Merge all tasks
            $allTasks = $regularTasks->concat($templateTasks)->sortBy('order')->values();

            // Group tasks by type
            $taskGroups = [
                'regular' => $allTasks->where('task_type', 'regular')
                    ->where('is_additional', false)
                    ->where('is_transferred_from', false)
                    ->where('is_transferred_to', false)
                    ->values(),
                'additional' => $allTasks->filter(function ($task) {
                    // المهام الإضافية + المهام المنقولة للشخص
                    return $task['is_additional'] === true || $task['is_transferred_to'] === true;
                })->values(),
                'template' => $allTasks->where('task_type', 'template')
                    ->where('is_additional', false)
                    ->where('is_transferred_from', false)
                    ->where('is_transferred_to', false)
                    ->values(),
                'transferred' => $allTasks->where('is_transferred_from', true)->values(),
            ];

            return response()->json([
                'success' => true,
                'tasks' => $taskGroups,
                'total' => $allTasks->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching participant tasks', [
                'project_id' => $projectId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في تحميل المهام'
            ], 500);
        }
    }

    /**
     * Get task errors details
     */
    public function getTaskErrors($taskType, $taskId)
    {
        try {
            $currentUser = Auth::user();

            // التحقق من الصلاحيات - فقط الأدوار المحددة يمكنها الوصول
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
                    'message' => 'غير مسموح لك بعرض أخطاء المهام'
                ], 403);
            }

            // تحديد نوع المهمة
            if ($taskType === 'regular') {
                $errorableType = 'App\\Models\\TaskUser';
            } else if ($taskType === 'template') {
                $errorableType = 'App\\Models\\TemplateTaskUser';
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'نوع المهمة غير صحيح'
                ], 400);
            }

            // جلب الأخطاء
            $errors = DB::table('employee_errors')
                ->join('users as error_user', 'employee_errors.user_id', '=', 'error_user.id')
                ->leftJoin('users as reporter', 'employee_errors.reported_by', '=', 'reporter.id')
                ->where('employee_errors.errorable_type', $errorableType)
                ->where('employee_errors.errorable_id', $taskId)
                ->whereNull('employee_errors.deleted_at')
                ->select(
                    'employee_errors.id',
                    'employee_errors.title',
                    'employee_errors.description',
                    'employee_errors.error_type',
                    'employee_errors.error_category',
                    'employee_errors.created_at',
                    'error_user.name as user_name',
                    'reporter.name as reporter_name'
                )
                ->orderBy('employee_errors.created_at', 'desc')
                ->get()
                ->map(function ($error) {
                    return [
                        'id' => $error->id,
                        'title' => $error->title,
                        'description' => $error->description,
                        'error_type' => $error->error_type,
                        'error_type_text' => $error->error_type === 'critical' ? 'جوهري' : 'عادي',
                        'error_category' => $error->error_category,
                        'error_category_text' => $this->getErrorCategoryText($error->error_category),
                        'user_name' => $error->user_name,
                        'reporter_name' => $error->reporter_name,
                        'created_at' => $error->created_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'errors' => $errors,
                'total' => $errors->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching task errors', [
                'task_type' => $taskType,
                'task_id' => $taskId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في تحميل الأخطاء'
            ], 500);
        }
    }

    /**
     * Get task revisions details
     */
    public function getTaskRevisions($taskType, $taskId)
    {
        try {
            $currentUser = Auth::user();

            // التحقق من الصلاحيات - فقط الأدوار المحددة يمكنها الوصول
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
                    'message' => 'غير مسموح لك بعرض تعديلات المهام'
                ], 403);
            }

            // تحديد الحقل المناسب
            $taskField = $taskType === 'regular' ? 'task_user_id' : 'template_task_user_id';

            // جلب التعديلات
            $revisions = DB::table('task_revisions')
                ->leftJoin('users as creator', 'task_revisions.created_by', '=', 'creator.id')
                ->leftJoin('users as assigned', 'task_revisions.assigned_to', '=', 'assigned.id')
                ->leftJoin('users as reviewer', 'task_revisions.reviewed_by', '=', 'reviewer.id')
                ->leftJoin('users as responsible', 'task_revisions.responsible_user_id', '=', 'responsible.id')
                ->where('task_revisions.' . $taskField, $taskId)
                ->select(
                    'task_revisions.id',
                    'task_revisions.title',
                    'task_revisions.description',
                    'task_revisions.status',
                    'task_revisions.revision_source',
                    'task_revisions.revision_type',
                    'task_revisions.created_at',
                    'task_revisions.actual_minutes',
                    'creator.name as creator_name',
                    'assigned.name as assigned_name',
                    'reviewer.name as reviewer_name',
                    'responsible.name as responsible_name'
                )
                ->orderBy('task_revisions.created_at', 'desc')
                ->get()
                ->map(function ($revision) {
                    return [
                        'id' => $revision->id,
                        'title' => $revision->title,
                        'description' => $revision->description,
                        'status' => $revision->status,
                        'status_text' => $this->getRevisionStatusText($revision->status),
                        'revision_source' => $revision->revision_source,
                        'revision_source_text' => $revision->revision_source === 'internal' ? 'داخلي' : 'خارجي',
                        'revision_type' => $revision->revision_type,
                        'actual_time' => $this->formatMinutes($revision->actual_minutes),
                        'creator_name' => $revision->creator_name,
                        'assigned_name' => $revision->assigned_name,
                        'reviewer_name' => $revision->reviewer_name,
                        'responsible_name' => $revision->responsible_name,
                        'created_at' => $revision->created_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'revisions' => $revisions,
                'total' => $revisions->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching task revisions', [
                'task_type' => $taskType,
                'task_id' => $taskId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في تحميل التعديلات'
            ], 500);
        }
    }

    private function getErrorCategoryText($category)
    {
        return match ($category) {
            'quality' => 'جودة',
            'deadline' => 'موعد نهائي',
            'communication' => 'تواصل',
            'technical' => 'فني',
            'procedural' => 'إجرائي',
            'other' => 'أخرى',
            default => 'غير محدد'
        };
    }

    private function getRevisionStatusText($status)
    {
        return match ($status) {
            'new' => 'جديد',
            'in_progress' => 'جاري العمل',
            'paused' => 'متوقف',
            'completed' => 'مكتمل',
            default => 'غير محدد'
        };
    }

    private function formatMinutes($minutes)
    {
        if (!$minutes || $minutes == 0) {
            return '0د';
        }

        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        $parts = [];
        if ($hours > 0) {
            $parts[] = $hours . ' س';
        }
        if ($mins > 0) {
            $parts[] = $mins . 'د';
        }

        return implode(' ', $parts);
    }

    private function formatTime($hours, $minutes)
    {
        if ((!$hours || $hours == 0) && (!$minutes || $minutes == 0)) {
            return '0د';
        }

        $parts = [];
        if ($hours && $hours > 0) {
            $parts[] = $hours . ' س';
        }
        if ($minutes && $minutes > 0) {
            $parts[] = $minutes . 'د';
        }

        return implode(' ', $parts);
    }
}
