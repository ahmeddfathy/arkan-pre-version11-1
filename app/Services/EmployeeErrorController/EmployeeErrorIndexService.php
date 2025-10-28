<?php

namespace App\Services\EmployeeErrorController;

use App\Models\EmployeeError;
use App\Models\User;
use App\Models\RoleHierarchy;
use App\Models\DepartmentRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;

class EmployeeErrorIndexService
{
    protected $errorFilterService;
    protected $errorStatisticsService;

    public function __construct(
        EmployeeErrorFilterService $errorFilterService,
        EmployeeErrorStatisticsService $errorStatisticsService
    ) {
        $this->errorFilterService = $errorFilterService;
        $this->errorStatisticsService = $errorStatisticsService;
    }

    /**
     * جلب وتنظيم الأخطاء لصفحة Index
     */
    public function getErrorsForIndex(Request $request): array
    {
        $user = Auth::user();

        $filters = [
            'error_type' => $request->get('error_type'),
            'error_category' => $request->get('error_category'),
            'errorable_type' => $request->get('errorable_type'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'month' => $request->get('month'), // ✅ فلتر الشهر
            'project_code' => $request->get('project_code'), // ✅ فلتر كود المشروع
        ];

        // جلب الأخطاء حسب الدور
        $employeeErrors = $this->getErrorsBasedOnRole($user, array_filter($filters));

        // الإحصائيات حسب الدور
        $stats = $this->getStatsBasedOnRole($user);

        return [
            'employeeErrors' => $employeeErrors,
            'stats' => $stats,
            'filters' => $filters
        ];
    }

    /**
     * جلب الأخطاء حسب المستوى الهرمي للمستخدم
     */
    private function getErrorsBasedOnRole(User $user, array $filters): Collection
    {
        $query = EmployeeError::query();

        // التحقق من المستوى الهرمي
        $globalLevel = RoleHierarchy::getUserMaxHierarchyLevel($user);
        $departmentLevel = DepartmentRole::getUserDepartmentHierarchyLevel($user);

        // إذا كان المستوى الهرمي 2 أو أعلى، يعرض الأخطاء اللي سجلها
        if (($globalLevel && $globalLevel >= 2) || ($departmentLevel && $departmentLevel >= 2)) {
            // للمديرين: عرض الأخطاء اللي سجلوها
            $query->where('reported_by', $user->id);
        } else {
            // للموظفين: عرض الأخطاء اللي عليهم
            $query->where('user_id', $user->id);
        }

        // تطبيق الفلاتر
        $query = $this->errorFilterService->applyFilters($query, $filters);

        return $query->with(['errorable', 'reportedBy', 'user'])
                     ->latest()
                     ->get();
    }

    /**
     * الإحصائيات حسب المستوى الهرمي
     */
    private function getStatsBasedOnRole(User $user): array
    {
        // التحقق من المستوى الهرمي
        $globalLevel = RoleHierarchy::getUserMaxHierarchyLevel($user);
        $departmentLevel = DepartmentRole::getUserDepartmentHierarchyLevel($user);

        // إذا كان المستوى الهرمي 2 أو أعلى، إحصائيات الأخطاء اللي سجلها
        if (($globalLevel && $globalLevel >= 2) || ($departmentLevel && $departmentLevel >= 2)) {
            // للمديرين: إحصائيات الأخطاء اللي سجلوها
            return $this->errorStatisticsService->getReporterStats($user);
        } else {
            // للموظفين: إحصائيات الأخطاء اللي عليهم
            return $this->errorStatisticsService->getUserErrorStats($user);
        }
    }

    /**
     * جلب أخطاء موظف معين (للمديرين)
     */
    public function getUserErrors(User $user, array $filters = []): Collection
    {
        $query = EmployeeError::where('user_id', $user->id);

        // تطبيق الفلاتر
        $query = $this->errorFilterService->applyFilters($query, $filters);

        return $query->with(['errorable', 'reportedBy'])
                     ->latest()
                     ->get();
    }

    /**
     * جلب أخطاء مشروع معين
     */
    public function getProjectErrors($projectId, array $filters = []): Collection
    {
        $query = EmployeeError::whereHasMorph('errorable', [\App\Models\ProjectServiceUser::class], function ($q) use ($projectId) {
            $q->where('project_id', $projectId);
        });

        // تطبيق الفلاتر
        $query = $this->errorFilterService->applyFilters($query, $filters);

        return $query->with(['user', 'errorable', 'reportedBy'])
                     ->latest()
                     ->get();
    }

    /**
     * جلب أخطاء مهمة معينة
     */
    public function getTaskErrors($taskId, array $filters = []): Collection
    {
        $query = EmployeeError::whereHasMorph('errorable', [\App\Models\TaskUser::class], function ($q) use ($taskId) {
            $q->where('task_id', $taskId);
        });

        // تطبيق الفلاتر
        $query = $this->errorFilterService->applyFilters($query, $filters);

        return $query->with(['user', 'errorable', 'reportedBy'])
                     ->latest()
                     ->get();
    }

    /**
     * جلب الأخطاء حسب القسم
     */
    public function getDepartmentErrors(string $department, array $filters = []): Collection
    {
        $query = EmployeeError::whereHas('user', function ($q) use ($department) {
            $q->where('department', $department);
        });

        // تطبيق الفلاتر
        $query = $this->errorFilterService->applyFilters($query, $filters);

        return $query->with(['user', 'errorable', 'reportedBy'])
                     ->latest()
                     ->get();
    }

    /**
     * البحث في الأخطاء
     */
    public function searchErrors(string $searchTerm, array $filters = []): Collection
    {
        $query = EmployeeError::where(function ($q) use ($searchTerm) {
            $q->where('title', 'like', "%{$searchTerm}%")
              ->orWhere('description', 'like', "%{$searchTerm}%")
              ->orWhereHas('user', function ($userQuery) use ($searchTerm) {
                  $userQuery->where('name', 'like', "%{$searchTerm}%");
              });
        });

        // تطبيق الفلاتر
        $query = $this->errorFilterService->applyFilters($query, $filters);

        return $query->with(['user', 'errorable', 'reportedBy'])
                     ->latest()
                     ->get();
    }

    /**
     * جلب أخطائي (الأخطاء المسجلة علي)
     */
    public function getMyErrors(User $user, array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = EmployeeError::where('user_id', $user->id);

        // تطبيق الفلاتر
        $query = $this->errorFilterService->applyFilters($query, $filters);

        return $query->with(['user', 'errorable', 'reportedBy'])
                     ->latest()
                     ->get();
    }

    /**
     * جلب الأخطاء التي أضفتها
     */
    public function getReportedErrors(User $user, array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = EmployeeError::where('reported_by', $user->id);

        // تطبيق الفلاتر
        $query = $this->errorFilterService->applyFilters($query, $filters);

        return $query->with(['user', 'errorable', 'reportedBy'])
                     ->latest()
                     ->get();
    }

    /**
     * جلب جميع الأخطاء (للمديرين)
     */
    public function getAllErrors(array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = EmployeeError::query();

        // تطبيق الفلاتر
        $query = $this->errorFilterService->applyFilters($query, $filters);

        return $query->with(['user', 'errorable', 'reportedBy'])
                     ->latest()
                     ->get();
    }
}

