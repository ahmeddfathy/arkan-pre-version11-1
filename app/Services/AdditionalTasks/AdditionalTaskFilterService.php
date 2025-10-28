<?php

namespace App\Services\AdditionalTasks;

use App\Models\User;
use App\Models\AdditionalTask;
use App\Models\RoleHierarchy;
use App\Models\DepartmentRole;
use App\Services\TaskController\TaskHierarchyService;
use App\Services\Auth\RoleCheckService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdditionalTaskFilterService
{
    protected $hierarchyService;
    protected $roleCheckService;

    public function __construct(TaskHierarchyService $hierarchyService, RoleCheckService $roleCheckService)
    {
        $this->hierarchyService = $hierarchyService;
        $this->roleCheckService = $roleCheckService;
    }

    /**
     * جلب المهام الإضافية المتاحة للمستخدم الحالي حسب مستوى منشئها
     */
    public function getAvailableTasksForUser()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // جلب كل المهام النشطة وغير المنتهية التي لم يقدم عليها المستخدم
        $tasks = AdditionalTask::active()
            ->where('current_end_time', '>', now()) // المهام غير المنتهية فقط
            ->with('creator')
            ->whereDoesntHave('users', function($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->get();

        // فلترة المهام حسب مستوى منشئها والقسم المستهدف
        return $tasks->filter(function($task) use ($user) {
            return $this->canUserSeeTask($user, $task);
        });
    }

    /**
     * جلب المهام الإضافية للعرض في صفحة الإدارة (حسب مستوى منشئها)
     */
    public function getTasksForIndex()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // الأدوار العليا - كل المهام
        if ($this->roleCheckService->userHasRole(['company_manager', 'hr', 'project_manager'])) {
            return AdditionalTask::with(['creator', 'season'])
                ->withCount(['taskUsers', 'taskUsers as completed_count' => function($query) {
                    $query->where('status', 'completed');
                }])
                ->orderBy('created_at', 'desc');
        }

        // جميع المستخدمين - المهام حسب مستوى منشئها (نفس منطق user-tasks)
        $tasks = AdditionalTask::with(['creator', 'season'])
            ->withCount(['taskUsers', 'taskUsers as completed_count' => function($query) {
                $query->where('status', 'completed');
            }])
            ->get();

        // فلترة المهام حسب مستوى منشئها
        $filteredTasks = $tasks->filter(function($task) use ($user) {
            return $this->canUserSeeTask($user, $task);
        });

        // إرجاع Collection مع ترتيب حسب تاريخ الإنشاء
        return $filteredTasks->sortByDesc('created_at');
    }

    /**
     * التحقق من صلاحية المستخدم لإنشاء مهمة جديدة
     */
    public function canCreateTask(): array
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // الأدوار العليا
        if ($this->roleCheckService->userHasRole(['company_manager', 'hr', 'project_manager'])) {
            return [
                'can_create' => true,
                'can_target_all' => true,
                'can_target_department' => true,
                'available_departments' => User::distinct()->pluck('department')->filter()->sort()->toArray()
            ];
        }

        $globalLevel = $this->hierarchyService->getCurrentUserHierarchyLevel($user);
        $departmentLevel = DepartmentRole::getUserDepartmentHierarchyLevel($user);

        // المستوى 5+ - كل الصلاحيات
        if ($globalLevel && $globalLevel >= 5) {
            return [
                'can_create' => true,
                'can_target_all' => true,
                'can_target_department' => true,
                'available_departments' => User::distinct()->pluck('department')->filter()->sort()->toArray()
            ];
        }

        // المستوى 4 - إنشاء للقسم + عام
        if ($departmentLevel && $departmentLevel >= 4) {
            return [
                'can_create' => true,
                'can_target_all' => true,
                'can_target_department' => true,
                'available_departments' => [$user->department]
            ];
        }

        // المستوى 3+ - إنشاء للفريق
        if ($departmentLevel && $departmentLevel >= 3) {
            return [
                'can_create' => true,
                'can_target_all' => false,
                'can_target_department' => true,
                'available_departments' => [$user->department],
                'team_level' => true
            ];
        }

        // أقل من المستوى 2 - لا يُسمح
        return [
            'can_create' => false,
            'message' => 'لا تملك الصلاحيات اللازمة لإنشاء مهام إضافية'
        ];
    }

    /**
     * التحقق من صلاحية المستخدم لتعديل مهمة
     */
    public function canEditTask(AdditionalTask $task): array
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // الأدوار العليا
        if ($this->roleCheckService->userHasRole(['company_manager', 'hr', 'project_manager'])) {
            return ['can_edit' => true];
        }

        // منشئ المهمة يمكنه التعديل
        if ($task->created_by == $user->id) {
            return ['can_edit' => true];
        }

        return [
            'can_edit' => false,
            'message' => 'يمكنك تعديل المهام التي أنشأتها فقط'
        ];
    }

    /**
     * جلب الأقسام المتاحة للمستخدم الحالي
     */
    public function getAvailableDepartments(): array
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($this->roleCheckService->userHasRole(['company_manager', 'hr', 'project_manager'])) {
            return User::distinct()->pluck('department')->filter()->sort()->toArray();
        }

        $globalLevel = $this->hierarchyService->getCurrentUserHierarchyLevel($user);

        if ($globalLevel && $globalLevel >= 5) {
            return User::distinct()->pluck('department')->filter()->sort()->toArray();
        }

        return $user->department ? [$user->department] : [];
    }


    public function canUserSeeTask(User $user, AdditionalTask $task): bool
    {
        $creator = $task->creator;
        if (!$creator) {
            return false; // إذا لم يُعثر على منشئ المهمة
        }

        if ($task->target_type === 'department') {
            // المهمة مخصصة لقسم معين
            if ($user->department !== $task->target_department) {
                return false; // المستخدم ليس من القسم المستهدف
            }
        }
        // إذا كانت target_type = 'all' فتكمل الفحوصات العادية

        // تحقق من أن منشئ المهمة له دور عليا
        $creatorRoles = $creator->roles->pluck('name')->toArray();
        if (array_intersect($creatorRoles, ['company_manager', 'hr', 'project_manager'])) {
            return true;
        }

        $creatorGlobalLevel = $this->hierarchyService->getCurrentUserHierarchyLevel($creator);
        $creatorDepartmentLevel = DepartmentRole::getUserDepartmentHierarchyLevel($creator);

        // منشئ بمستوى 5+ - يراها الجميع (بعد فحص target_department)
        if ($creatorGlobalLevel && $creatorGlobalLevel >= 5) {
            return true;
        }

        // منشئ بمستوى 4 - قسمه فقط (بعد فحص target_department)
        if ($creatorDepartmentLevel && $creatorDepartmentLevel >= 4) {
            return $user->department === $creator->department;
        }

        // منشئ بمستوى 3 - فريقه فقط (بعد فحص target_department)
        if ($creatorDepartmentLevel && $creatorDepartmentLevel >= 3) {
            return $this->areInSameTeam($user, $creator);
        }

        // منشئ بمستوى أقل من 2 - لا أحد يراها (إلا هو)
        return $user->id === $creator->id;
    }

    /**
     * التحقق من أن المستخدمين في نفس الفريق
     */
    private function areInSameTeam(User $user1, User $user2): bool
    {
        // نفس القسم أولاً
        if ($user1->department !== $user2->department) {
            return false;
        }

        // التحقق من الفريق الحالي
        if ($user1->current_team_id && $user1->current_team_id === $user2->current_team_id) {
            return true;
        }

        // التحقق من عضوية الفرق
        $user1Teams = DB::table('team_user')->where('user_id', $user1->id)->pluck('team_id');
        $user2Teams = DB::table('team_user')->where('user_id', $user2->id)->pluck('team_id');

        return $user1Teams->intersect($user2Teams)->isNotEmpty();
    }

    /**
     * التحقق من إمكانية الوصول للمهام الإضافية (أي مستخدم مسجل)
     */
    public function checkMinimumLevel(): array
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user) {
            return [
                'has_access' => false,
                'message' => 'يجب تسجيل الدخول للوصول للمهام الإضافية'
            ];
        }

        // أي مستخدم مسجل دخول يمكنه الوصول
        return ['has_access' => true];
    }

    /**
     * التحقق من صحة ربط أدوار المستخدم بقسمه
     */
    private function userHasValidDepartmentRole(User $user): bool
    {
        $userRoles = $user->roles->pluck('name')->toArray();
        if (array_intersect($userRoles, ['company_manager', 'hr', 'project_manager'])) {
            return true;
        }

        if (!$user->department) {
            return false;
        }

        $userRoleIds = $user->roles->pluck('id')->toArray();
        foreach ($userRoleIds as $userRoleId) {
            if (!DepartmentRole::mappingExists($user->department, $userRoleId)) {
                return false;
            }
        }

        return true;
    }

    /**
     * الحصول على رسالة خطأ ربط الأدوار
     */
    public function getDepartmentRoleErrorMessage(User $user): ?string
    {
        if (!$user->department) {
            return null; // لا توجد قيود على من بدون قسم
        }

        // الأدوار التي تحتاج ربط بالقسم (الأدوار الإدارية والقيادية)
        $rolesRequiringMapping = [
            'hr', 'admin', 'super-admin', 'project_manager',
            'technical_team_leader', 'technical_department_manager',
            'marketing_team_leader', 'marketing_department_manager',
            'customer_service_team_leader', 'customer_service_department_manager',
            'coordination_team_leader', 'coordination_department_manager',
            'company_manager'
        ];

        $userRoleIds = $user->roles->pluck('id')->toArray();
        foreach ($userRoleIds as $userRoleId) {
            $role = \Spatie\Permission\Models\Role::find($userRoleId);
            if ($role && in_array($role->name, $rolesRequiringMapping)) {
                if (!DepartmentRole::mappingExists($user->department, $userRoleId)) {
                    return "لديك دور '{$role->name}' غير مربوط بقسم '{$user->department}'. يرجى التواصل مع الإدارة لربط الدور بالقسم.";
                }
            }
        }

        return null;
    }
}
