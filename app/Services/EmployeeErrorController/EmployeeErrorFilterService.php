<?php

namespace App\Services\EmployeeErrorController;

use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class EmployeeErrorFilterService
{
    /**
     * تطبيق جميع الفلاتر على الاستعلام
     */
    public function applyFilters(Builder $query, array $filters): Builder
    {
        if (isset($filters['error_type'])) {
            $query = $this->filterByErrorType($query, $filters['error_type']);
        }

        if (isset($filters['error_category'])) {
            $query = $this->filterByCategory($query, $filters['error_category']);
        }

        if (isset($filters['errorable_type'])) {
            $query = $this->filterByErrorableType($query, $filters['errorable_type']);
        }

        if (isset($filters['user_id'])) {
            $query = $this->filterByUser($query, $filters['user_id']);
        }

        if (isset($filters['reported_by'])) {
            $query = $this->filterByReporter($query, $filters['reported_by']);
        }

        if (isset($filters['date_from'])) {
            $query = $this->filterByDateFrom($query, $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query = $this->filterByDateTo($query, $filters['date_to']);
        }

        if (isset($filters['department'])) {
            $query = $this->filterByDepartment($query, $filters['department']);
        }

        if (isset($filters['project_id'])) {
            $query = $this->filterByProject($query, $filters['project_id']);
        }

        // ✅ فلتر الشهر
        if (isset($filters['month'])) {
            $query = $this->filterByMonth($query, $filters['month']);
        }

        // ✅ فلتر كود المشروع
        if (isset($filters['project_code'])) {
            $query = $this->filterByProjectCode($query, $filters['project_code']);
        }

        return $query;
    }

    /**
     * فلتر حسب نوع الخطأ
     */
    private function filterByErrorType(Builder $query, string $errorType): Builder
    {
        return $query->where('error_type', $errorType);
    }

    /**
     * فلتر حسب تصنيف الخطأ
     */
    private function filterByCategory(Builder $query, string $category): Builder
    {
        return $query->where('error_category', $category);
    }

    /**
     * فلتر حسب نوع الكيان (TaskUser, TemplateTaskUser, ProjectServiceUser)
     */
    private function filterByErrorableType(Builder $query, string $errorableType): Builder
    {
        return $query->where('errorable_type', $errorableType);
    }

    /**
     * فلتر حسب الموظف
     */
    private function filterByUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * فلتر حسب من سجل الخطأ
     */
    private function filterByReporter(Builder $query, int $reporterId): Builder
    {
        return $query->where('reported_by', $reporterId);
    }

    /**
     * فلتر من تاريخ معين
     */
    private function filterByDateFrom(Builder $query, string $dateFrom): Builder
    {
        return $query->whereDate('created_at', '>=', Carbon::parse($dateFrom));
    }

    /**
     * فلتر إلى تاريخ معين
     */
    private function filterByDateTo(Builder $query, string $dateTo): Builder
    {
        return $query->whereDate('created_at', '<=', Carbon::parse($dateTo));
    }

    /**
     * فلتر حسب القسم
     */
    private function filterByDepartment(Builder $query, string $department): Builder
    {
        return $query->whereHas('user', function ($q) use ($department) {
            $q->where('department', $department);
        });
    }

    /**
     * فلتر حسب المشروع
     */
    private function filterByProject(Builder $query, int $projectId): Builder
    {
        return $query->whereHasMorph('errorable', [\App\Models\ProjectServiceUser::class], function ($q) use ($projectId) {
            $q->where('project_id', $projectId);
        });
    }

    /**
     * فلتر الأخطاء الحديثة (آخر 7 أيام)
     */
    public function getRecentErrors(Builder $query): Builder
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays(7));
    }

    /**
     * فلتر الأخطاء الجوهرية فقط
     */
    public function getCriticalErrors(Builder $query): Builder
    {
        return $query->where('error_type', 'critical');
    }

    /**
     * فلتر الأخطاء العادية فقط
     */
    public function getNormalErrors(Builder $query): Builder
    {
        return $query->where('error_type', 'normal');
    }

    /**
     * فلتر الأخطاء المحذوفة
     */
    public function getTrashedErrors(Builder $query): Builder
    {
        return $query->onlyTrashed();
    }

    /**
     * فلتر الأخطاء حسب فترة زمنية
     */
    public function filterByPeriod(Builder $query, string $period): Builder
    {
        switch ($period) {
            case 'today':
                return $query->whereDate('created_at', Carbon::today());

            case 'yesterday':
                return $query->whereDate('created_at', Carbon::yesterday());

            case 'this_week':
                return $query->whereBetween('created_at', [
                    Carbon::now()->startOfWeek(),
                    Carbon::now()->endOfWeek()
                ]);

            case 'last_week':
                return $query->whereBetween('created_at', [
                    Carbon::now()->subWeek()->startOfWeek(),
                    Carbon::now()->subWeek()->endOfWeek()
                ]);

            case 'this_month':
                return $query->whereMonth('created_at', Carbon::now()->month)
                            ->whereYear('created_at', Carbon::now()->year);

            case 'last_month':
                return $query->whereMonth('created_at', Carbon::now()->subMonth()->month)
                            ->whereYear('created_at', Carbon::now()->subMonth()->year);

            case 'this_year':
                return $query->whereYear('created_at', Carbon::now()->year);

            default:
                return $query;
        }
    }

    /**
     * ✅ فلتر حسب الشهر (YYYY-MM format)
     */
    private function filterByMonth(Builder $query, string $month): Builder
    {
        try {
            $date = Carbon::createFromFormat('Y-m', $month);
            return $query->whereYear('created_at', $date->year)
                        ->whereMonth('created_at', $date->month);
        } catch (\Exception $e) {
            return $query;
        }
    }

    /**
     * ✅ فلتر حسب كود المشروع
     */
    private function filterByProjectCode(Builder $query, string $projectCode): Builder
    {
        return $query->whereHasMorph('errorable', [
            \App\Models\TaskUser::class,
            \App\Models\TemplateTaskUser::class,
            \App\Models\ProjectServiceUser::class
        ], function ($q, $type) use ($projectCode) {
            if ($type === \App\Models\TaskUser::class) {
                // للمهام العادية
                $q->whereHas('task.project', function ($subQ) use ($projectCode) {
                    $subQ->where('code', $projectCode);
                });
            } elseif ($type === \App\Models\TemplateTaskUser::class) {
                // لمهام القوالب
                $q->whereHas('project', function ($subQ) use ($projectCode) {
                    $subQ->where('code', $projectCode);
                });
            } elseif ($type === \App\Models\ProjectServiceUser::class) {
                // للمشاريع
                $q->whereHas('project', function ($subQ) use ($projectCode) {
                    $subQ->where('code', $projectCode);
                });
            }
        });
    }
}

