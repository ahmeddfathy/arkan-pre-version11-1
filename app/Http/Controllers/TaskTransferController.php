<?php

namespace App\Http\Controllers;

use App\Models\TaskUser;
use App\Models\TemplateTaskUser;
use App\Models\User;
use App\Services\Tasks\TaskTransferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TaskTransferController extends Controller
{
    protected $transferService;

    public function __construct(TaskTransferService $transferService)
    {
        $this->transferService = $transferService;
    }

    public function index()
    {
        // تسجيل النشاط - دخول صفحة نقل المهام
        if (\Illuminate\Support\Facades\Auth::check()) {
            activity()
                ->causedBy(\Illuminate\Support\Facades\Auth::user())
                ->withProperties([
                    'action_type' => 'view_index',
                    'page' => 'task_transfer_index',
                    'viewed_at' => now()->toDateTimeString(),
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip()
                ])
                ->log('دخل على صفحة نقل المهام');
        }

        return view('tasks.transfer.index');
    }

        public function transferHistory(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');
        $fromUserId = $request->get('from_user_id');
        $toUserId = $request->get('to_user_id');
        $taskType = $request->get('task_type', 'all');
        $seasonId = $request->get('season_id');

        // البناء الأساسي للاستعلام
        $query = DB::table('task_transfers')
            ->leftJoin('task_users', 'task_transfers.task_user_id', '=', 'task_users.id')
            ->leftJoin('tasks', 'task_users.task_id', '=', 'tasks.id')
            ->leftJoin('users as from_users', 'task_transfers.from_user_id', '=', 'from_users.id')
            ->leftJoin('users as to_users', 'task_transfers.to_user_id', '=', 'to_users.id')
            ->leftJoin('seasons', 'task_users.season_id', '=', 'seasons.id')
            ->leftJoin('projects', 'tasks.project_id', '=', 'projects.id')
            ->select([
                'task_transfers.id',
                'task_transfers.from_user_id',
                'task_transfers.to_user_id',
                'task_transfers.points_transferred as transfer_points',
                'task_transfers.reason',
                'task_transfers.transferred_at',
                'task_transfers.created_at',
                'task_transfers.updated_at',
                'tasks.name as task_name',
                'tasks.description as task_description',
                'from_users.name as from_user_name',
                'from_users.employee_id as from_user_employee_id',
                'to_users.name as to_user_name',
                'to_users.employee_id as to_user_employee_id',
                'seasons.name as season_name',
                'projects.name as project_name',
                DB::raw("'task' as transfer_type")
            ]);

        // تطبيق الفلاتر
        if ($fromDate) {
            $query->where('task_transfers.transferred_at', '>=', $fromDate . ' 00:00:00');
        }

        if ($toDate) {
            $query->where('task_transfers.transferred_at', '<=', $toDate . ' 23:59:59');
        }

        if ($fromUserId) {
            $query->where('task_transfers.from_user_id', $fromUserId);
        }

        if ($toUserId) {
            $query->where('task_transfers.to_user_id', $toUserId);
        }

        if ($seasonId) {
            $query->where('task_users.season_id', $seasonId);
        }

        if ($taskType === 'task') {
            // عرض المهام العادية فقط - الاستعلام جاهز
        } elseif ($taskType === 'template_task') {
            // عرض استعلام فارغ لمهام القوالب (يمكن تطويره لاحقاً)
            $query = $query->whereRaw('1 = 0'); // لا توجد نتائج
        }

        // ترتيب النتائج والترقيم
        $transfers = $query->orderBy('task_transfers.transferred_at', 'desc')->paginate($perPage);

        // الحصول على البيانات للفلاتر
        $users = User::select('id', 'name', 'employee_id')->orderBy('name')->get();
        $seasons = \App\Models\Season::orderBy('name')->get();

        // تسجيل النشاط - عرض سجل نقل المهام
        if (\Illuminate\Support\Facades\Auth::check()) {
            activity()
                ->causedBy(\Illuminate\Support\Facades\Auth::user())
                ->withProperties([
                    'action_type' => 'view_transfer_history',
                    'page' => 'task_transfer_history',
                    'filters' => $request->all(),
                    'results_count' => $transfers->count(),
                    'viewed_at' => now()->toDateTimeString(),
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip()
                ])
                ->log('شاهد سجل نقل المهام');
        }

        return view('tasks.transfer.history', compact('transfers', 'users', 'seasons'));
    }

    public function transferTask(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'task_user_id' => 'nullable|exists:task_users,id',
            'task_id' => 'nullable|exists:tasks,id',
            'to_user_id' => 'required|exists:users,id',
            'transfer_type' => 'required|in:positive,negative',
            'reason' => 'nullable|string|max:500',
            'new_deadline' => 'nullable|date|after_or_equal:today'
        ], [
            'transfer_type.required' => 'نوع النقل مطلوب',
            'transfer_type.in' => 'نوع النقل يجب أن يكون إيجابي أو سلبي',
            'to_user_id.required' => 'المستخدم المستقبل مطلوب',
            'to_user_id.exists' => 'المستخدم المستقبل غير موجود',
            'new_deadline.date' => 'الموعد النهائي يجب أن يكون تاريخاً صحيحاً',
            'new_deadline.after_or_equal' => 'الموعد النهائي يجب أن يكون اليوم أو بعده'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'البيانات غير صحيحة: ' . $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        // التحقق من وجود معرف للمهمة
        if (!$request->task_user_id && !$request->task_id) {
            return response()->json([
                'success' => false,
                'message' => 'معرف المهمة مطلوب (task_user_id أو task_id)'
            ], 422);
        }

        $taskUser = null;
        $task = null;
        $toUser = User::findOrFail($request->to_user_id);

        // محاولة العثور على TaskUser أولاً
        if ($request->task_user_id) {
            $taskUser = TaskUser::find($request->task_user_id);
        }

        // إذا لم نجد TaskUser، نحاول البحث بـ task_id
        if (!$taskUser && $request->task_id) {
            $task = \App\Models\Task::find($request->task_id);

            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'المهمة غير موجودة'
                ], 404);
            }

            // البحث عن أي TaskUser مرتبط بهذه المهمة
            $taskUsers = TaskUser::where('task_id', $task->id)->get();

            if ($taskUsers->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => '🚫 لا يمكن نقل هذه المهمة',
                    'reason' => 'المهمة غير مُعيَّنة لأي مستخدم حالياً',
                    'solution' => 'يجب تعيين المهمة لمستخدم أولاً قبل إمكانية نقلها',
                    'task_name' => $task->name,
                    'task_id' => $task->id,
                    'suggested_action' => 'assign_first'
                ], 400);
            }

            // إذا وجدنا TaskUser، نأخذ الأول
            $taskUser = $taskUsers->first();
        }

        if (!$taskUser) {
            return response()->json([
                'success' => false,
                'message' => '🚫 لا يمكن نقل هذه المهمة',
                'reason' => 'المهمة غير مُعيَّنة لأي مستخدم حالياً',
                'solution' => 'يجب تعيين المهمة لمستخدم أولاً قبل إمكانية نقلها'
            ], 400);
        }

        if (!$this->canUserTransferTask($taskUser)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لنقل هذه المهمة'
            ], 403);
        }

        // الحصول على نقاط المهمة تلقائياً
        $taskPoints = $taskUser->task->points ?? 10; // القيمة الافتراضية 10 نقاط

        $canTransfer = $this->transferService->canTransferTask(
            $taskUser,
            $toUser,
            $taskPoints
        );

        if (!$canTransfer['can_transfer']) {
            return response()->json([
                'success' => false,
                'message' => $canTransfer['reason'],
                'data' => $canTransfer
            ], 400);
        }

        $result = $this->transferService->transferTask(
            $taskUser,
            $toUser,
            $taskPoints,
            $request->reason,
            $request->transfer_type,
            $request->new_deadline
        );

        return response()->json($result);
    }

    public function transferTemplateTask(Request $request)
    {
        Log::info('Template Task Transfer Request:', [
            'request_data' => $request->all(),
            'user_id' => Auth::id()
        ]);

        $validator = Validator::make($request->all(), [
            'template_task_user_id' => 'required|exists:template_task_user,id',
            'to_user_id' => 'required|exists:users,id',
            'transfer_type' => 'required|in:positive,negative',
            'reason' => 'nullable|string|max:500',
            'new_deadline' => 'nullable|date|after_or_equal:today'
        ], [
            'transfer_type.required' => 'نوع النقل مطلوب',
            'transfer_type.in' => 'نوع النقل يجب أن يكون إيجابي أو سلبي',
            'to_user_id.required' => 'المستخدم المستقبل مطلوب',
            'to_user_id.exists' => 'المستخدم المستقبل غير موجود',
            'template_task_user_id.required' => 'معرف مهمة القالب مطلوب',
            'template_task_user_id.exists' => 'مهمة القالب غير موجودة',
            'new_deadline.date' => 'الموعد النهائي يجب أن يكون تاريخاً صحيحاً',
            'new_deadline.after_or_equal' => 'الموعد النهائي يجب أن يكون اليوم أو بعده'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'البيانات غير صحيحة: ' . $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        $templateTaskUser = TemplateTaskUser::findOrFail($request->template_task_user_id);
        $toUser = User::findOrFail($request->to_user_id);

        if (!$this->canUserTransferTemplateTask($templateTaskUser)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لنقل هذه المهمة'
            ], 403);
        }

        // الحصول على نقاط مهمة القالب تلقائياً
        $templateTaskPoints = $templateTaskUser->templateTask->points ?? 10; // القيمة الافتراضية 10 نقاط

        $canTransfer = $this->transferService->canTransferTask(
            $templateTaskUser,
            $toUser,
            $templateTaskPoints
        );

        if (!$canTransfer['can_transfer']) {
            return response()->json([
                'success' => false,
                'message' => $canTransfer['reason'],
                'data' => $canTransfer
            ], 400);
        }

        $result = $this->transferService->transferTemplateTask(
            $templateTaskUser,
            $toUser,
            $templateTaskPoints,
            $request->reason,
            $request->transfer_type,
            $request->new_deadline
        );

        Log::info('📤 Controller returning result', [
            'result' => $result,
            'success' => $result['success'] ?? null
        ]);

        return response()->json($result);
    }

    public function getUserTransferHistory(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $seasonId = $request->get('season_id');

        $season = null;
        if ($seasonId) {
            $season = \App\Models\Season::find($seasonId);
        }

        $history = $this->transferService->getUserTransferHistory($user, $season);

        return response()->json([
            'success' => true,
            'data' => $history
        ]);
    }

    public function checkTransferability(Request $request)
    {
        Log::info('Check Transferability Request:', [
            'request_data' => $request->all(),
            'user_id' => Auth::id()
        ]);

        $validator = Validator::make($request->all(), [
            'task_user_id' => 'required_without:template_task_user_id|exists:task_users,id',
            'template_task_user_id' => 'required_without:task_user_id|exists:template_task_user,id',
            'to_user_id' => 'required|exists:users,id',
            'transfer_type' => 'nullable|in:positive,negative'
        ], [
            'transfer_type.in' => 'نوع النقل يجب أن يكون إيجابي أو سلبي',
            'to_user_id.required' => 'المستخدم المستقبل مطلوب',
            'to_user_id.exists' => 'المستخدم المستقبل غير موجود',
            'template_task_user_id.exists' => 'مهمة القالب غير موجودة في قاعدة البيانات'
        ]);

        if ($validator->fails()) {
            Log::warning('Check Transferability Validation Failed:', [
                'errors' => $validator->errors()->toArray(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'البيانات غير صحيحة: ' . $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        $toUser = User::findOrFail($request->to_user_id);

        // استخدام قيمة افتراضية لـ transfer_type إذا لم يتم إرسالها
        $transferType = $request->transfer_type ?? 'positive';

        if ($request->task_user_id) {
            $taskUser = TaskUser::findOrFail($request->task_user_id);
            // الحصول على نقاط المهمة تلقائياً
            $taskPoints = $taskUser->task->points ?? 10;
            $result = $this->transferService->canTransferTask($taskUser, $toUser, $taskPoints);
        } else {
            $templateTaskUser = TemplateTaskUser::findOrFail($request->template_task_user_id);
            // الحصول على نقاط مهمة القالب تلقائياً
            $templateTaskPoints = $templateTaskUser->templateTask->points ?? 10;
            $result = $this->transferService->canTransferTask($templateTaskUser, $toUser, $templateTaskPoints);
        }

        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

    public function getAvailableUsers(Request $request)
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        $users = User::where('id', '!=', $currentUser->id)
            ->select('id', 'name', 'employee_id')
            ->orderBy('name')
            ->get();

        // إضافة النقاط الحالية لكل مستخدم (اختياري)
        $season = \App\Models\Season::where('is_active', true)->first();
        if ($season) {
            $users = $users->map(function($user) use ($season) {
                $userPoints = \App\Models\UserSeasonPoint::where('user_id', $user->id)
                    ->where('season_id', $season->id)
                    ->first();

                $user->current_points = $userPoints ? $userPoints->total_points : 0;
                return $user;
            });
        }

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    public function getCurrentUserPoints(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $season = \App\Models\Season::where('is_active', true)->first();

        $points = 0;
        if ($season) {
            $userSeasonPoint = \App\Models\UserSeasonPoint::where('user_id', $user->id)
                ->where('season_id', $season->id)
                ->first();

            $points = $userSeasonPoint ? $userSeasonPoint->total_points : 0;
        }

        return response()->json([
            'success' => true,
            'points' => $points
        ]);
    }

    private function canUserTransferTask(TaskUser $taskUser): bool
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // اذا كان هذا السجل هو السجل الأصلي الذي تم نقل المهمة منه سابقاً
        // فغير مسموح إجراء نقل جديد من خلاله، لكن نسمح لمن قام بالنقل بالتصحيح من خلال السجل الجديد فقط
        if ($taskUser->is_transferred === true) {
            Log::info('Blocked transfer on transferred-from original record', [
                'task_user_id' => $taskUser->id,
                'user_id' => $user->id
            ]);
            return false;
        }

        // التحقق من صاحب المهمة
        if ($taskUser->user_id === $user->id) {
            return true;
        }

        // التحقق من الأدوار الإدارية
        try {
            if ($user->hasRole(['admin', 'manager', 'hr', 'company_manager', 'project_manager'])) {
                return true;
            }
        } catch (\Exception $e) {
            Log::warning('Role check failed in canUserTransferTask', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }

        // التحقق من صلاحية النقل
        try {
            if ($user->hasPermissionTo('transfer-tasks')) {
                return true;
            }
        } catch (\Exception $e) {
            Log::warning('Permission check failed in canUserTransferTask', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }

        // Log للمساعدة في debug
        Log::info('User cannot transfer task', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'task_user_id' => $taskUser->id,
            'task_owner_id' => $taskUser->user_id,
            'user_roles' => $user->roles->pluck('name')->toArray() ?? []
        ]);

        return false;
    }

    private function canUserTransferTemplateTask(TemplateTaskUser $templateTaskUser): bool
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // منع النقل من السجل الأصلي الذي سبق وتم نقل المهمة منه
        if ($templateTaskUser->is_transferred === true) {
            Log::info('Blocked transfer on transferred-from original template record', [
                'template_task_user_id' => $templateTaskUser->id,
                'user_id' => $user->id
            ]);
            return false;
        }

        // التحقق من صاحب المهمة
        if ($templateTaskUser->user_id === $user->id) {
            return true;
        }

        // التحقق من الأدوار الإدارية (نفس الصلاحيات كالمهام العادية)
        try {
            if ($user->hasRole(['admin', 'manager', 'hr', 'company_manager', 'project_manager'])) {
                return true;
            }
        } catch (\Exception $e) {
            Log::warning('Role check failed in canUserTransferTemplateTask', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }

        // التحقق من صلاحية النقل
        try {
            if ($user->hasPermissionTo('transfer-tasks')) {
                return true;
            }
        } catch (\Exception $e) {
            Log::warning('Permission check failed in canUserTransferTemplateTask', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }

        // Log للمساعدة في debug
        Log::info('User cannot transfer template task', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'template_task_user_id' => $templateTaskUser->id,
            'task_owner_id' => $templateTaskUser->user_id,
            'user_roles' => $user->roles->pluck('name')->toArray() ?? []
        ]);

        return false;
    }

    /**
     * إلغاء نقل مهمة عادية
     */
    public function cancelTaskTransfer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'task_user_id' => 'required|exists:task_users,id',
            'cancel_reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        $taskUser = TaskUser::findOrFail($request->task_user_id);

        // التحقق من الصلاحية
        if (!$this->canCancelTransfer($taskUser)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية إلغاء هذا النقل'
            ], 403);
        }

        $result = $this->transferService->cancelTaskTransfer($taskUser, $request->cancel_reason);

        if ($result['success']) {
            activity()
                ->causedBy(Auth::user())
                ->performedOn($taskUser)
                ->withProperties([
                    'task_name' => $taskUser->task->name ?? 'غير معروف',
                    'original_user' => $result['original_user'] ?? 'غير معروف',
                    'cancel_reason' => $request->cancel_reason
                ])
                ->log('تم إلغاء نقل مهمة');
        }

        return response()->json($result);
    }

    /**
     * إلغاء نقل مهمة قالب
     */
    public function cancelTemplateTaskTransfer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'template_task_user_id' => 'required|exists:template_task_user,id',
            'cancel_reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        $templateTaskUser = TemplateTaskUser::findOrFail($request->template_task_user_id);

        // التحقق من الصلاحية
        if (!$this->canCancelTemplateTransfer($templateTaskUser)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية إلغاء هذا النقل'
            ], 403);
        }

        $result = $this->transferService->cancelTemplateTaskTransfer($templateTaskUser, $request->cancel_reason);

        if ($result['success']) {
            activity()
                ->causedBy(Auth::user())
                ->performedOn($templateTaskUser)
                ->withProperties([
                    'task_name' => $templateTaskUser->templateTask->name ?? 'غير معروف',
                    'original_user' => $result['original_user'] ?? 'غير معروف',
                    'cancel_reason' => $request->cancel_reason
                ])
                ->log('تم إلغاء نقل مهمة قالب');
        }

        return response()->json($result);
    }

    /**
     * التحقق من صلاحية إلغاء نقل مهمة
     */
    private function canCancelTransfer(TaskUser $taskUser): bool
    {
        $user = Auth::user();

        // Admin أو HR يمكنهم إلغاء أي نقل
        if ($user->hasRole(['admin', 'hr'])) {
            return true;
        }

        // الموظف الحالي (اللي المهمة عنده) يمكنه إلغاء النقل
        if ($taskUser->user_id == $user->id) {
            return true;
        }

        // الموظف الأصلي يمكنه إلغاء النقل
        if ($taskUser->original_task_user_id) {
            $originalTaskUser = TaskUser::find($taskUser->original_task_user_id);
            if ($originalTaskUser && $originalTaskUser->user_id == $user->id) {
                return true;
            }
        }

        return false;
    }

    /**
     * التحقق من صلاحية إلغاء نقل مهمة قالب
     */
    private function canCancelTemplateTransfer(TemplateTaskUser $templateTaskUser): bool
    {
        $user = Auth::user();

        // Admin أو HR يمكنهم إلغاء أي نقل
        if ($user->hasRole(['admin', 'hr'])) {
            return true;
        }

        // الموظف الحالي (اللي المهمة عنده) يمكنه إلغاء النقل
        if ($templateTaskUser->user_id == $user->id) {
            return true;
        }

        // الموظف الأصلي يمكنه إلغاء النقل
        if ($templateTaskUser->original_template_task_user_id) {
            $originalTemplateTaskUser = TemplateTaskUser::find($templateTaskUser->original_template_task_user_id);
            if ($originalTemplateTaskUser && $originalTemplateTaskUser->user_id == $user->id) {
                return true;
            }
        }

        return false;
    }
}
