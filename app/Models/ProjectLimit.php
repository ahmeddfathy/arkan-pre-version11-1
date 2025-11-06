<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\HasSecureId;
use Illuminate\Support\Facades\DB;

class ProjectLimit extends Model
{
    use HasFactory, HasSecureId, LogsActivity;

    protected $fillable = [
        'limit_type',
        'month',
        'entity_id',
        'entity_name',
        'monthly_limit',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'month' => 'integer',
        'entity_id' => 'integer',
        'monthly_limit' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Configure Activity Log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['limit_type', 'month', 'entity_id', 'entity_name', 'monthly_limit', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match ($eventName) {
                'created' => 'تم إنشاء حد شهري جديد',
                'updated' => 'تم تحديث الحد الشهري',
                'deleted' => 'تم حذف الحد الشهري',
                default => $eventName
            });
    }

    // =====================================================
    // العلاقات
    // =====================================================

    /**
     * العلاقة مع User (للموظفين)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'entity_id');
    }

    /**
     * العلاقة مع Team (للفرق)
     */
    public function team()
    {
        return $this->belongsTo(Team::class, 'entity_id');
    }

    // =====================================================
    // Scopes
    // =====================================================

    /**
     * حدود الشركة
     */
    public function scopeCompany($query)
    {
        return $query->where('limit_type', 'company');
    }

    /**
     * حدود الأقسام
     */
    public function scopeDepartment($query)
    {
        return $query->where('limit_type', 'department');
    }

    /**
     * حدود الفرق
     */
    public function scopeTeam($query)
    {
        return $query->where('limit_type', 'team');
    }

    /**
     * حدود الموظفين
     */
    public function scopeUserLimit($query)
    {
        return $query->where('limit_type', 'user');
    }

    /**
     * النشطة فقط
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * حسب الشهر
     */
    public function scopeForMonth($query, $month)
    {
        return $query->where('month', $month);
    }

    /**
     * الحدود العامة (لجميع الشهور)
     */
    public function scopeGeneral($query)
    {
        return $query->whereNull('month');
    }

    // =====================================================
    // Helper Methods
    // =====================================================

    /**
     * الحصول على نص نوع الحد
     */
    public function getLimitTypeTextAttribute(): string
    {
        return match ($this->limit_type) {
            'company' => 'الشركة',
            'department' => 'قسم',
            'team' => 'فريق',
            'user' => 'موظف',
            default => 'غير محدد'
        };
    }

    /**
     * الحصول على اسم الشهر
     */
    public function getMonthNameAttribute(): string
    {
        if (is_null($this->month)) {
            return 'جميع الشهور';
        }

        $months = [
            1 => 'يناير',
            2 => 'فبراير',
            3 => 'مارس',
            4 => 'أبريل',
            5 => 'مايو',
            6 => 'يونيو',
            7 => 'يوليو',
            8 => 'أغسطس',
            9 => 'سبتمبر',
            10 => 'أكتوبر',
            11 => 'نوفمبر',
            12 => 'ديسمبر',
        ];

        return $months[$this->month] ?? 'غير محدد';
    }

    /**
     * الحصول على اسم الكيان (Entity)
     */
    public function getEntityDisplayNameAttribute(): string
    {
        if ($this->limit_type === 'company') {
            return 'الشركة بالكامل';
        }

        return $this->entity_name ?? 'غير محدد';
    }

    // =====================================================
    // Static Methods لحساب الإحصائيات
    // =====================================================

    /**
     * الحصول على الحد الشهري لكيان معين
     */
    public static function getLimitFor(string $type, int $month, $entityId = null): ?int
    {
        // البحث عن حد محدد للشهر
        $specificLimit = self::active()
            ->where('limit_type', $type)
            ->where('month', $month);

        // للأقسام نستخدم entity_name، للباقي نستخدم entity_id
        if ($type === 'department' && $entityId) {
            $specificLimit->where('entity_name', $entityId);
        } elseif ($entityId) {
            $specificLimit->where('entity_id', $entityId);
        } elseif ($type === 'company') {
            $specificLimit->whereNull('entity_id');
        }

        $specificLimit = $specificLimit->first();

        if ($specificLimit) {
            return $specificLimit->monthly_limit;
        }

        // إذا لم يوجد حد محدد، البحث عن الحد العام
        $generalLimit = self::active()
            ->where('limit_type', $type)
            ->whereNull('month');

        // للأقسام نستخدم entity_name، للباقي نستخدم entity_id
        if ($type === 'department' && $entityId) {
            $generalLimit->where('entity_name', $entityId);
        } elseif ($entityId) {
            $generalLimit->where('entity_id', $entityId);
        } elseif ($type === 'company') {
            $generalLimit->whereNull('entity_id');
        }

        $generalLimit = $generalLimit->first();

        return $generalLimit?->monthly_limit;
    }

    /**
     * حساب عدد المشاريع الحالية لكيان معين في شهر معين
     */
    public static function getCurrentProjectCount(string $type, int $month, int $year, $entityId = null): int
    {
        $query = Project::whereMonth('created_at', $month)
            ->whereYear('created_at', $year);

        switch ($type) {
            case 'company':
                // عدد المشاريع في الشركة كلها
                return $query->count();

            case 'department':
                // عدد المشاريع في قسم معين (من خلال الخدمات)
                return $query->whereHas('services', function ($q) use ($entityId) {
                    $q->where('department', $entityId);
                })->count();

            case 'team':
                $projectIds = DB::table('project_service_user')
                    ->join('projects', 'project_service_user.project_id', '=', 'projects.id')
                    ->where('project_service_user.team_id', $entityId)
                    ->whereNotNull('project_service_user.team_id')
                    ->whereMonth('projects.created_at', $month)
                    ->whereYear('projects.created_at', $year)
                    ->groupBy('projects.id')
                    ->pluck('projects.id')
                    ->toArray();
                return count($projectIds);

            case 'user':
                // عدد المشاريع لموظف معين (من project_service_user)
                return DB::table('project_service_user')
                    ->join('projects', 'project_service_user.project_id', '=', 'projects.id')
                    ->where('project_service_user.user_id', $entityId)
                    ->whereMonth('projects.created_at', $month)
                    ->whereYear('projects.created_at', $year)
                    ->distinct('projects.id')
                    ->count('projects.id');

            default:
                return 0;
        }
    }

    /**
     * حساب عدد المشاريع حسب الحالة (Status Breakdown)
     */
    public static function getProjectsStatusBreakdown(string $type, int $month, int $year, $entityId = null): array
    {
        // للشركة: نستخدم Projects مباشرة
        if ($type === 'company') {
            $query = Project::whereMonth('created_at', $month)
                ->whereYear('created_at', $year);

            return [
                'new' => (clone $query)->where('status', 'جديد')->count(),
                'in_progress' => (clone $query)->where('status', 'جاري التنفيذ')->count(),
                'completed' => (clone $query)->where('status', 'مكتمل')->count(),
                'cancelled' => (clone $query)->where('status', 'ملغي')->count(),
                'on_hold' => (clone $query)->where('status', 'معلق')->count(),
                'total' => $query->count(),
            ];
        }

        // للقسم/الفريق/الموظف: نستخدم project_service_user ثم نجيب المشاريع
        $projectIds = [];

        switch ($type) {
            case 'department':
                // المشاريع من خلال الخدمات في القسم
                // نستخدم groupBy عشان نضمن إن كل مشروع يتحسب مرة واحدة بس
                $projectIds = DB::table('project_service_user')
                    ->join('projects', 'project_service_user.project_id', '=', 'projects.id')
                    ->join('company_services', 'project_service_user.service_id', '=', 'company_services.id')
                    ->where('company_services.department', $entityId)
                    ->whereMonth('projects.created_at', $month)
                    ->whereYear('projects.created_at', $year)
                    ->groupBy('projects.id')
                    ->pluck('projects.id')
                    ->toArray();
                break;

            case 'team':
                $projectIds = DB::table('project_service_user')
                    ->join('projects', 'project_service_user.project_id', '=', 'projects.id')
                    ->where('project_service_user.team_id', $entityId)
                    ->whereNotNull('project_service_user.team_id')
                    ->whereMonth('projects.created_at', $month)
                    ->whereYear('projects.created_at', $year)
                    ->groupBy('projects.id')
                    ->pluck('projects.id')
                    ->toArray();
                break;

            case 'user':
                // المشاريع من خلال الموظف
                // نستخدم groupBy عشان نضمن إن كل مشروع يتحسب مرة واحدة بس
                $projectIds = DB::table('project_service_user')
                    ->join('projects', 'project_service_user.project_id', '=', 'projects.id')
                    ->where('project_service_user.user_id', $entityId)
                    ->whereMonth('projects.created_at', $month)
                    ->whereYear('projects.created_at', $year)
                    ->groupBy('projects.id')
                    ->pluck('projects.id')
                    ->toArray();
                break;
        }

        // لو مفيش مشاريع، نرجع أصفار
        if (empty($projectIds)) {
            return [
                'new' => 0,
                'in_progress' => 0,
                'completed' => 0,
                'cancelled' => 0,
                'on_hold' => 0,
                'total' => 0,
            ];
        }

        // حساب عدد المشاريع لكل حالة من المشاريع المحددة
        return [
            'new' => Project::whereIn('id', $projectIds)->where('status', 'جديد')->count(),
            'in_progress' => Project::whereIn('id', $projectIds)->where('status', 'جاري التنفيذ')->count(),
            'completed' => Project::whereIn('id', $projectIds)->where('status', 'مكتمل')->count(),
            'cancelled' => Project::whereIn('id', $projectIds)->where('status', 'ملغي')->count(),
            'on_hold' => Project::whereIn('id', $projectIds)->where('status', 'معلق')->count(),
            'total' => count($projectIds),
        ];
    }

    /**
     * التحقق من تجاوز الحد
     */
    public static function isLimitExceeded(string $type, int $month, int $year, $entityId = null): array
    {
        $limit = self::getLimitFor($type, $month, $entityId);

        if (is_null($limit)) {
            return [
                'has_limit' => false,
                'is_exceeded' => false,
                'limit' => null,
                'current' => 0,
                'remaining' => null,
                'status_breakdown' => self::getProjectsStatusBreakdown($type, $month, $year, $entityId),
            ];
        }

        $current = self::getCurrentProjectCount($type, $month, $year, $entityId);
        $statusBreakdown = self::getProjectsStatusBreakdown($type, $month, $year, $entityId);

        return [
            'has_limit' => true,
            'is_exceeded' => $current >= $limit,
            'limit' => $limit,
            'current' => $current,
            'remaining' => max(0, $limit - $current),
            'percentage' => $limit > 0 ? round(($current / $limit) * 100, 2) : 0,
            'status_breakdown' => $statusBreakdown,
        ];
    }

    /**
     * الحصول على جميع الحدود لشهر معين مع الإحصائيات
     */
    public static function getMonthlyReport(int $month, int $year, $departmentFilter = null, $teamFilter = null): array
    {
        $report = [
            'month' => $month,
            'year' => $year,
            'company' => [],
            'departments' => [],
            'teams' => [],
            'users' => [],
        ];

        // حدود الشركة
        $companyLimits = self::active()->company()->get();
        foreach ($companyLimits as $limit) {
            $stats = self::isLimitExceeded('company', $month, $year);
            $report['company'][] = array_merge([
                'limit_record' => $limit,
                'month_name' => $limit->month_name,
            ], $stats);
        }

        // حدود الأقسام
        $deptLimits = self::active()->department();
        if ($departmentFilter) {
            $deptLimits->where('entity_name', $departmentFilter);
        }
        $deptLimits = $deptLimits->get();

        foreach ($deptLimits as $limit) {
            $stats = self::isLimitExceeded('department', $month, $year, $limit->entity_name);
            $report['departments'][] = array_merge([
                'limit_record' => $limit,
                'department_name' => $limit->entity_name,
                'month_name' => $limit->month_name,
            ], $stats);
        }

        // حدود الفرق
        $teamLimits = self::active()->team()->with('team');
        if ($teamFilter) {
            $teamLimits->where('entity_id', $teamFilter);
        }
        $teamLimits = $teamLimits->get();

        foreach ($teamLimits as $limit) {
            $stats = self::isLimitExceeded('team', $month, $year, $limit->entity_id);
            $report['teams'][] = array_merge([
                'limit_record' => $limit,
                'team' => $limit->team,
                'team_name' => $limit->team ? $limit->team->name : ($limit->entity_name ?? 'فريق محذوف'),
                'month_name' => $limit->month_name,
            ], $stats);
        }

        // حدود الموظفين + كل الموظفين النشطين
        $userLimits = self::active()->userLimit()->with('user')->get();
        $usersWithLimits = [];

        foreach ($userLimits as $limit) {
            // تطبيق الفلاتر
            if ($departmentFilter && optional($limit->user)->department !== $departmentFilter) {
                continue;
            }
            if ($teamFilter && optional($limit->user)->current_team_id != $teamFilter) {
                continue;
            }

            $stats = self::isLimitExceeded('user', $month, $year, $limit->entity_id);
            $usersWithLimits[$limit->entity_id] = true;
            $report['users'][] = array_merge([
                'limit_record' => $limit,
                'user' => $limit->user,
                'month_name' => $limit->month_name,
            ], $stats);
        }

        // جلب كل الموظفين النشطين
        $activeUsers = User::where('employee_status', 'active');

        // تطبيق الفلاتر
        if ($departmentFilter) {
            $activeUsers->where('department', $departmentFilter);
        }
        if ($teamFilter) {
            $activeUsers->where('current_team_id', $teamFilter);
        }

        $activeUsers = $activeUsers->get();

        foreach ($activeUsers as $user) {
            // تخطّي الموظفين اللي عندهم حدود محددة (تم إضافتهم فوق)
            if (isset($usersWithLimits[$user->id])) {
                continue;
            }

            $current = self::getCurrentProjectCount('user', $month, $year, $user->id);

            $report['users'][] = [
                'limit_record' => null,
                'user' => $user,
                'month_name' => 'جميع الشهور',
                'has_limit' => false,
                'is_exceeded' => false,
                'limit' => null,
                'current' => $current,
                'remaining' => null,
                'percentage' => 0,
            ];
        }

        // ترتيب الموظفين حسب عدد المشاريع (الأكثر أولاً)
        usort($report['users'], function ($a, $b) {
            return $b['current'] <=> $a['current'];
        });

        return $report;
    }
}
