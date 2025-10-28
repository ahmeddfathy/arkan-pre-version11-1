<?php

namespace App\Services\TaskController;

use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class TaskIndexService
{
    protected $taskFilterService;
    protected $taskManagementService;

    public function __construct(
        TaskFilterService $taskFilterService,
        TaskManagementService $taskManagementService
    ) {
        $this->taskFilterService = $taskFilterService;
        $this->taskManagementService = $taskManagementService;
    }

    /**
     * جلب وتنظيم المهام لصفحة Index
     */
    public function getTasksForIndex(Request $request): array
    {
        $currentUserId = Auth::id();

        // جلب المهام العادية
        $regularTasks = $this->getRegularTasksWithUserData($request, $currentUserId);

        // جلب مهام القوالب
        $templateTasks = $this->taskManagementService->getAllTemplateTasks($request->all());

        // دمج وترتيب المهام
        $allTasksCollection = collect($regularTasks)->merge($templateTasks)
            ->sortByDesc('created_at');

        // إنشاء pagination
        $tasks = $this->createPagination($allTasksCollection, $request);

        return [
            'tasks' => $tasks,
            'regularTasks' => $regularTasks,
            'templateTasks' => $templateTasks
        ];
    }

    /**
     * جلب المهام العادية مع بيانات المستخدم - مع دعم المهام المنقولة كسجلين منفصلين
     */
    private function getRegularTasksWithUserData(Request $request, int $currentUserId): Collection
    {
        // جلب جميع TaskUsers بدلاً من Tasks لعرض المهام المنقولة كسجلين منفصلين
        $taskUsersQuery = \App\Models\TaskUser::with([
            'task.project',
            'task.service',
            'task.createdBy',
            'user',
            'season',
            'transferredToUser', // ✅ المستخدم المنقول إليه
            'originalTaskUser.user', // ✅ المستخدم الأصلي (للمهام المنقولة)
            'administrativeApprover', // ✅ المعتمد الإداري
            'technicalApprover' // ✅ المعتمد الفني
        ])
        ->whereHas('task', function($q) use ($request) {
            // تطبيق فلاتر المهام
            $q = $this->taskFilterService->applyHierarchicalTaskFiltering($q);
            $q = $this->taskFilterService->applyTaskFilters($q, $request->all());
        });

        $taskUsers = $taskUsersQuery->orderBy('created_at', 'desc')->get();

        // تحويل TaskUsers إلى تنسيق يشبه Tasks مع إضافة معلومات النقل
        $transformedTasks = collect();

        foreach ($taskUsers as $taskUser) {
            $task = $taskUser->task;
            if (!$task) continue;

            // إنشاء نسخة من المهمة مع بيانات TaskUser
            $transformedTask = $task->replicate();
            $transformedTask->setRelations($task->getRelations());

            // ⚠️ IMPORTANT: حفظ الـ ID الأصلي (replicate يحذف الـ ID)
            $transformedTask->id = $task->id;
            $transformedTask->task_user_id = $taskUser->id;

            // إضافة بيانات المستخدم المحدد
            $transformedTask->pivot = $taskUser;
            $transformedTask->user_status = $taskUser->status;
            $transformedTask->assigned_user = $taskUser->user;

            // تحديث users collection لتحتوي على المستخدم المعين مع بيانات pivot صحيحة
            $userWithPivot = clone $taskUser->user;
            $userWithPivot->pivot = $taskUser;

            // إضافة المستخدم للـ users collection
            $transformedTask->setRelation('users', collect([$userWithPivot]));

            // التأكد من أن pivot data متاحة على مستوى المهمة أيضاً
            $transformedTask->setAttribute('pivot', $taskUser);

            // إضافة معلومات النقل
            $transformedTask->is_transferred = $taskUser->is_transferred ?? false;
            $transformedTask->is_transferred_task = $taskUser->is_transferred ?? false;
            $transformedTask->is_additional_task = $taskUser->is_additional_task ?? false;
            $transformedTask->task_source = $taskUser->task_source ?? null;
            $transformedTask->transfer_type = $taskUser->transfer_type ?? null;
            $transformedTask->transferred_to_user_id = $taskUser->transferred_to_user_id ?? null;
            $transformedTask->original_task_user_id = $taskUser->original_task_user_id ?? null;

            // معلومات النقل للمهام المنقولة
            // إذا كانت مهمة إضافية ولها original_task_user_id، فهي منقولة
            if ($taskUser->is_additional_task && $taskUser->original_task_user_id) {
                $transformedTask->is_transferred_to = true;
                $transformedTask->task_source = 'transferred'; // تصحيح task_source

                // استخدام العلاقة المحملة مسبقاً بدلاً من query جديد
                $transformedTask->original_user = $taskUser->originalTaskUser?->user;
                $transformedTask->transferred_at = $taskUser->originalTaskUser?->transferred_at;
            }

            // معلومات النقل للمهام الأصلية المنقولة
            if ($taskUser->is_transferred) {
                $transformedTask->is_transferred_from = true;

                // استخدام العلاقة المحملة مسبقاً
                $transformedTask->transferred_to_user = $taskUser->transferredToUser;
                $transformedTask->transferred_at = $taskUser->transferred_at;
            }

            $transformedTask->notes_count = $this->getUserNotesCount($taskUser->id, $currentUserId);

            $transformedTask->task_user_id = $taskUser->id;

            // إضافة معلومات المستخدم للعرض
            $transformedTask->current_user_id = $taskUser->user_id;
            $transformedTask->current_user_name = $taskUser->user->name;

            // إضافة معلومات الحالة والأوقات
            $transformedTask->task_status = $taskUser->status;
            $transformedTask->actual_hours = $taskUser->actual_hours ?? 0;
            $transformedTask->actual_minutes = $taskUser->actual_minutes ?? 0;
            $transformedTask->estimated_hours = $taskUser->estimated_hours ?? $task->estimated_hours ?? 0;
            $transformedTask->estimated_minutes = $taskUser->estimated_minutes ?? $task->estimated_minutes ?? 0;

            // إضافة عدد المراجعات للمهمة
            $transformedTask->revisions_count = $task->revisions()->count();
            $transformedTask->pending_revisions_count = $task->revisions()->where('status', 'pending')->count();
            $transformedTask->approved_revisions_count = $task->revisions()->where('status', 'approved')->count();
            $transformedTask->rejected_revisions_count = $task->revisions()->where('status', 'rejected')->count();

            // ✅ إضافة بيانات الاعتماد الإداري والفني للـ pivot
            $transformedTask->pivot->administrative_approval = $taskUser->administrative_approval ?? false;
            $transformedTask->pivot->technical_approval = $taskUser->technical_approval ?? false;
            $transformedTask->pivot->administrative_approval_at = $taskUser->administrative_approval_at ?? null;
            $transformedTask->pivot->technical_approval_at = $taskUser->technical_approval_at ?? null;
            $transformedTask->pivot->administrativeApprover = $taskUser->administrativeApprover ?? null;
            $transformedTask->pivot->technicalApprover = $taskUser->technicalApprover ?? null;

            $transformedTasks->push($transformedTask);
        }

        return $transformedTasks;
    }

    /**
     * جلب عدد النوتات للمستخدم
     */
    private function getUserNotesCount(int $taskUserId, int $userId): int
    {
        return \App\Models\TaskNote::where('task_type', 'regular')
            ->where('task_user_id', $taskUserId)
            ->where('created_by', $userId)
            ->count();
    }

    /**
     * إنشاء pagination للمهام
     */
    private function createPagination(Collection $allTasksCollection, Request $request): LengthAwarePaginator
    {
        if ($request->has('show_all') && $request->show_all == '1') {
            return $this->createShowAllPagination($allTasksCollection, $request);
        }

        return $this->createRegularPagination($allTasksCollection, $request);
    }

    /**
     * إنشاء pagination لعرض جميع المهام
     */
    private function createShowAllPagination(Collection $allTasksCollection, Request $request): LengthAwarePaginator
    {
        $tasksCount = $allTasksCollection->count();
        $perPage = max($tasksCount, 1);

        return new LengthAwarePaginator(
            $allTasksCollection,
            $tasksCount,
            $perPage,
            1,
            ['path' => $request->url(), 'query' => $request->query()]
        );
    }

    /**
     * إنشاء pagination عادي
     */
    private function createRegularPagination(Collection $allTasksCollection, Request $request): LengthAwarePaginator
    {
        $tasksCount = $allTasksCollection->count();

        if ($tasksCount === 0) {
            return new LengthAwarePaginator(
                collect([]),
                0,
                15,
                1,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        }

        $currentPage = \Illuminate\Pagination\Paginator::resolveCurrentPage();
        $perPage = 15;
        $currentPageItems = $allTasksCollection->slice(($currentPage - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $currentPageItems,
            $tasksCount,
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );
    }

    /**
     * جلب البيانات المساعدة لصفحة Index
     */
    public function getIndexSupportData(): array
    {
        return [
            'projects' => $this->taskFilterService->getAvailableProjects() ?? collect([]),
            'services' => $this->taskFilterService->getFilteredServicesForUser() ?? collect([]),
            'users' => $this->taskFilterService->getFilteredUsersForCurrentUser() ?? collect([]),
            'roles' => $this->taskFilterService->getFilteredRolesForUser() ?? collect([]),
            'taskCreators' => $this->taskFilterService->getTaskCreators() ?? collect([]),
            'graphicTaskTypes' => \App\Models\GraphicTaskType::active()->orderBy('name')->get() ?? collect([]),
        ];
    }
}
