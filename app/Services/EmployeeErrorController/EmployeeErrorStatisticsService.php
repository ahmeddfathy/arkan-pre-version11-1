<?php

namespace App\Services\EmployeeErrorController;

use App\Models\EmployeeError;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EmployeeErrorStatisticsService
{
    /**
     * الحصول على إحصائيات أخطاء موظف
     */
    public function getUserErrorStats(User $user): array
    {
        $errors = EmployeeError::where('user_id', $user->id);

        return [
            'total_errors' => $errors->count(),
            'critical_errors' => (clone $errors)->where('error_type', 'critical')->count(),
            'normal_errors' => (clone $errors)->where('error_type', 'normal')->count(),
            'by_category' => [
                'quality' => (clone $errors)->where('error_category', 'quality')->count(),
                'deadline' => (clone $errors)->where('error_category', 'deadline')->count(),
                'communication' => (clone $errors)->where('error_category', 'communication')->count(),
                'technical' => (clone $errors)->where('error_category', 'technical')->count(),
                'procedural' => (clone $errors)->where('error_category', 'procedural')->count(),
                'other' => (clone $errors)->where('error_category', 'other')->count(),
            ],
            'by_month' => $this->getErrorsByMonth($user->id),
            'trend' => $this->getErrorTrend($user->id),
        ];
    }

    /**
     * إحصائيات الأخطاء اللي سجلها المدير
     */
    public function getReporterStats(User $user): array
    {
        $errors = EmployeeError::where('reported_by', $user->id);

        return [
            'total_errors' => $errors->count(),
            'critical_errors' => (clone $errors)->where('error_type', 'critical')->count(),
            'normal_errors' => (clone $errors)->where('error_type', 'normal')->count(),
            'by_category' => [
                'quality' => (clone $errors)->where('error_category', 'quality')->count(),
                'deadline' => (clone $errors)->where('error_category', 'deadline')->count(),
                'communication' => (clone $errors)->where('error_category', 'communication')->count(),
                'technical' => (clone $errors)->where('error_category', 'technical')->count(),
                'procedural' => (clone $errors)->where('error_category', 'procedural')->count(),
                'other' => (clone $errors)->where('error_category', 'other')->count(),
            ],
            'top_employees' => $this->getTopEmployeesWithErrors($user->id),
            'by_month' => $this->getReportedErrorsByMonth($user->id),
        ];
    }

    /**
     * إحصائيات الأخطاء للفريق أو قسم
     */
    public function getTeamErrorStats($teamId = null, $department = null): array
    {
        $query = EmployeeError::query();

        if ($teamId) {
            $query->whereHas('user', function ($q) use ($teamId) {
                $q->where('current_team_id', $teamId);
            });
        }

        if ($department) {
            $query->whereHas('user', function ($q) use ($department) {
                $q->where('department', $department);
            });
        }

        $errors = $query->get();

        return [
            'total_errors' => $errors->count(),
            'critical_errors' => $errors->where('error_type', 'critical')->count(),
            'normal_errors' => $errors->where('error_type', 'normal')->count(),
            'top_users_with_errors' => $this->getTopUsersWithErrors($errors),
            'by_category' => $errors->groupBy('error_category')->map->count(),
            'by_type' => $errors->groupBy('error_type')->map->count(),
            'average_per_employee' => $this->calculateAverageErrorsPerEmployee($errors),
        ];
    }

    /**
     * الحصول على أكثر الموظفين الذين لديهم أخطاء
     */
    private function getTopUsersWithErrors($errors, $limit = 10)
    {
        return $errors->groupBy('user_id')
                     ->map(function ($userErrors) {
                         return [
                             'user' => $userErrors->first()->user,
                             'total' => $userErrors->count(),
                             'critical' => $userErrors->where('error_type', 'critical')->count(),
                         ];
                     })
                     ->sortByDesc('total')
                     ->take($limit)
                     ->values();
    }

    /**
     * أكثر الموظفين أخطاءً لمدير معين
     */
    private function getTopEmployeesWithErrors($reporterId, $limit = 10)
    {
        return EmployeeError::where('reported_by', $reporterId)
            ->select('user_id',
                DB::raw('COUNT(*) as total_errors'),
                DB::raw('SUM(CASE WHEN error_type = "critical" THEN 1 ELSE 0 END) as critical_errors'))
            ->groupBy('user_id')
            ->orderByDesc('total_errors')
            ->limit($limit)
            ->with('user')
            ->get()
            ->map(function ($item) {
                return [
                    'user' => $item->user,
                    'total' => $item->total_errors,
                    'critical' => $item->critical_errors,
                ];
            });
    }

    /**
     * الأخطاء حسب الشهر
     */
    private function getErrorsByMonth($userId, $months = 12)
    {
        return EmployeeError::where('user_id', $userId)
            ->where('created_at', '>=', Carbon::now()->subMonths($months))
            ->select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();
    }

    /**
     * الأخطاء المسجلة حسب الشهر
     */
    private function getReportedErrorsByMonth($reporterId, $months = 12)
    {
        return EmployeeError::where('reported_by', $reporterId)
            ->where('created_at', '>=', Carbon::now()->subMonths($months))
            ->select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();
    }

    /**
     * اتجاه الأخطاء (زيادة أم نقصان)
     */
    private function getErrorTrend($userId)
    {
        $thisMonth = EmployeeError::where('user_id', $userId)
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();

        $lastMonth = EmployeeError::where('user_id', $userId)
            ->whereMonth('created_at', Carbon::now()->subMonth()->month)
            ->whereYear('created_at', Carbon::now()->subMonth()->year)
            ->count();

        if ($lastMonth == 0) {
            return [
                'direction' => $thisMonth > 0 ? 'up' : 'stable',
                'percentage' => 0
            ];
        }

        $percentage = (($thisMonth - $lastMonth) / $lastMonth) * 100;

        return [
            'direction' => $percentage > 0 ? 'up' : ($percentage < 0 ? 'down' : 'stable'),
            'percentage' => abs(round($percentage, 2))
        ];
    }

    /**
     * متوسط الأخطاء لكل موظف
     */
    private function calculateAverageErrorsPerEmployee($errors)
    {
        $uniqueUsers = $errors->pluck('user_id')->unique()->count();

        if ($uniqueUsers == 0) {
            return 0;
        }

        return round($errors->count() / $uniqueUsers, 2);
    }

    /**
     * إحصائيات مقارنة بين الأقسام
     */
    public function getDepartmentComparison(): array
    {
        return EmployeeError::join('users', 'employee_errors.user_id', '=', 'users.id')
            ->select('users.department',
                DB::raw('COUNT(*) as total_errors'),
                DB::raw('SUM(CASE WHEN error_type = "critical" THEN 1 ELSE 0 END) as critical_errors'))
            ->groupBy('users.department')
            ->orderByDesc('total_errors')
            ->get()
            ->toArray();
    }

    /**
     * إحصائيات الأخطاء الشهرية العامة
     */
    public function getMonthlyOverview($month = null, $year = null): array
    {
        $month = $month ?? Carbon::now()->month;
        $year = $year ?? Carbon::now()->year;

        $errors = EmployeeError::whereMonth('created_at', $month)
                              ->whereYear('created_at', $year);

        return [
            'total_errors' => $errors->count(),
            'critical_errors' => (clone $errors)->where('error_type', 'critical')->count(),
            'normal_errors' => (clone $errors)->where('error_type', 'normal')->count(),
            'by_category' => (clone $errors)->get()->groupBy('error_category')->map->count(),
            'by_day' => $this->getErrorsByDay($month, $year),
        ];
    }

    /**
     * الأخطاء حسب اليوم في شهر معين
     */
    private function getErrorsByDay($month, $year)
    {
        return EmployeeError::whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->select(
                DB::raw('DAY(created_at) as day'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('day')
            ->orderBy('day')
            ->get();
    }

    /**
     * جلب الإحصائيات حسب الدور
     */
    public function getStatsBasedOnRole(User $user): array
    {
        $globalLevel = \App\Models\RoleHierarchy::getUserMaxHierarchyLevel($user);
        $departmentLevel = \App\Models\DepartmentRole::getUserDepartmentHierarchyLevel($user);

        // إذا كان مدير
        if ($user->hasRole(['admin', 'super-admin', 'hr', 'project_manager']) ||
            ($globalLevel && $globalLevel >= 2) ||
            ($departmentLevel && $departmentLevel >= 2)) {

            return [
                'total_errors' => EmployeeError::count(),
                'critical_errors' => EmployeeError::where('error_type', 'critical')->count(),
                'normal_errors' => EmployeeError::where('error_type', 'normal')->count(),
                'by_category' => [
                    'quality' => EmployeeError::where('error_category', 'quality')->count(),
                    'deadline' => EmployeeError::where('error_category', 'deadline')->count(),
                    'communication' => EmployeeError::where('error_category', 'communication')->count(),
                    'technical' => EmployeeError::where('error_category', 'technical')->count(),
                    'procedural' => EmployeeError::where('error_category', 'procedural')->count(),
                    'other' => EmployeeError::where('error_category', 'other')->count(),
                ]
            ];
        }

        // إذا كان موظف عادي
        return [
            'total_errors' => EmployeeError::where('user_id', $user->id)->count(),
            'critical_errors' => EmployeeError::where('user_id', $user->id)->where('error_type', 'critical')->count(),
            'normal_errors' => EmployeeError::where('user_id', $user->id)->where('error_type', 'normal')->count(),
            'by_category' => [
                'quality' => EmployeeError::where('user_id', $user->id)->where('error_category', 'quality')->count(),
                'deadline' => EmployeeError::where('user_id', $user->id)->where('error_category', 'deadline')->count(),
                'communication' => EmployeeError::where('user_id', $user->id)->where('error_category', 'communication')->count(),
                'technical' => EmployeeError::where('user_id', $user->id)->where('error_category', 'technical')->count(),
                'procedural' => EmployeeError::where('user_id', $user->id)->where('error_category', 'procedural')->count(),
                'other' => EmployeeError::where('user_id', $user->id)->where('error_category', 'other')->count(),
            ]
        ];
    }

    /**
     * الحصول على إحصائيات أخطاء لمجموعة موظفين (للأقسام/الفرق/المشاريع)
     * @param array|int $userIds - معرف موظف واحد أو مصفوفة معرفات
     * @param array|null $dateFilters - فلاتر التاريخ
     */
    public function getGroupErrorStats($userIds, $dateFilters = null): array
    {
        // تحويل إلى مصفوفة إذا كان رقم واحد
        if (!is_array($userIds)) {
            $userIds = [$userIds];
        }

        $query = EmployeeError::whereIn('user_id', $userIds);

        // تطبيق فلاتر التاريخ إذا وجدت
        if ($dateFilters && isset($dateFilters['start_date'])) {
            $query->where('created_at', '>=', $dateFilters['start_date']);
        }
        if ($dateFilters && isset($dateFilters['end_date'])) {
            $query->where('created_at', '<=', $dateFilters['end_date']);
        }

        $errors = $query->get();

        // الحصول على آخر 5 أخطاء
        $latestErrors = $query->with(['user', 'reportedBy', 'errorable'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return [
            'has_errors' => $errors->count() > 0,
            'total_errors' => $errors->count(),
            'critical_errors' => $errors->where('error_type', 'critical')->count(),
            'normal_errors' => $errors->where('error_type', 'normal')->count(),
            'by_category' => [
                'quality' => $errors->where('error_category', 'quality')->count(),
                'deadline' => $errors->where('error_category', 'deadline')->count(),
                'communication' => $errors->where('error_category', 'communication')->count(),
                'technical' => $errors->where('error_category', 'technical')->count(),
                'procedural' => $errors->where('error_category', 'procedural')->count(),
                'other' => $errors->where('error_category', 'other')->count(),
            ],
            'latest_errors' => $latestErrors,
            'top_users' => $this->getTopUsersWithErrorsFromCollection($errors, 5),
        ];
    }

    /**
     * الحصول على أكثر الموظفين أخطاءً من collection
     */
    private function getTopUsersWithErrorsFromCollection($errors, $limit = 5)
    {
        return $errors->groupBy('user_id')
            ->map(function ($userErrors) {
                $user = $userErrors->first()->user;
                return [
                    'user_id' => $user->id ?? null,
                    'user_name' => $user->name ?? 'غير محدد',
                    'user_department' => $user->department ?? 'غير محدد',
                    'total' => $userErrors->count(),
                    'critical' => $userErrors->where('error_type', 'critical')->count(),
                    'normal' => $userErrors->where('error_type', 'normal')->count(),
                ];
            })
            ->sortByDesc('total')
            ->take($limit)
            ->values();
    }
}

