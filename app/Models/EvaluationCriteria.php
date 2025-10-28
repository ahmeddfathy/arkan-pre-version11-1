<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Models\Role;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\HasSecureId;

class EvaluationCriteria extends Model
{
    use HasFactory, SoftDeletes, HasSecureId, LogsActivity;

    protected $table = 'evaluation_criteria';

    protected $fillable = [
        'role_id',
        'criteria_name',
        'criteria_description',
        'max_points',
        'criteria_type', // positive, negative, bonus, development
        'category', // مجموعة البند (أساسي، إضافي، خصم)
        'is_active',
        'sort_order',
        'evaluate_per_project', // هل يتم تقييم هذا البند لكل مشروع
        'evaluation_period', // فترة التقييم: monthly, bi_weekly
    ];

    protected $casts = [
        'max_points' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'evaluate_per_project' => 'boolean',
        'evaluation_period' => 'string',
    ];

    /**
     * Configure Activity Log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'role_id', 'criteria_name', 'criteria_description', 'max_points',
                'criteria_type', 'category', 'is_active', 'sort_order', 'evaluation_period'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'تم إنشاء معيار تقييم جديد',
                'updated' => 'تم تحديث معيار التقييم',
                'deleted' => 'تم حذف معيار التقييم',
                default => $eventName
            });
    }

    /**
     * علاقة مع الدور
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * علاقة مع الأدوار المقيمة
     */
    public function evaluatorRoles()
    {
        return $this->hasMany(CriteriaEvaluatorRole::class, 'criteria_id');
    }

    /**
     * الحصول على الأدوار التي يمكنها تقييم هذا البند
     */
    public function getEvaluators($departmentName = null)
    {
        $query = $this->evaluatorRoles()->with('evaluatorRole');

        if ($departmentName) {
            $query->where('department_name', $departmentName);
        }

        return $query->get();
    }

    /**
     * التحقق من إمكانية تقييم هذا البند بواسطة دور معين
     */
    public function canBeEvaluatedBy($evaluatorRoleId, $departmentName = null)
    {
        return CriteriaEvaluatorRole::canEvaluateCriteria($evaluatorRoleId, $this->id, $departmentName);
    }


    public function scopePositive($query)
    {
        return $query->where('criteria_type', 'positive');
    }

    /**
     * الحصول على البنود السلبية (خصومات)
     */
    public function scopeNegative($query)
    {
        return $query->where('criteria_type', 'negative');
    }

    /**
     * الحصول على بنود البونص
     */
    public function scopeBonus($query)
    {
        return $query->where('criteria_type', 'bonus');
    }

    /**
     * Scope للبنود التطويرية
     */
    public function scopeDevelopment($query)
    {
        return $query->where('criteria_type', 'development');
    }

    /**
     * الحصول على البنود النشطة
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * الحصول على البنود حسب الدور
     */
    public function scopeForRole($query, $roleId)
    {
        return $query->where('role_id', $roleId);
    }

    /**
     * الحصول على البنود مرتبة
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('criteria_name');
    }

    /**
     * الحصول على البنود المرتبطة بالمشاريع
     */
    public function scopeProjectBased($query)
    {
        return $query->where('evaluate_per_project', true);
    }

    /**
     * الحصول على البنود غير المرتبطة بالمشاريع
     */
    public function scopeNonProjectBased($query)
    {
        return $query->where('evaluate_per_project', false);
    }

    /**
     * إجمالي النقاط لدور معين (البنود العامة فقط)
     */
    public static function getTotalPointsForRole($roleId, $type = 'positive')
    {
        return static::forRole($roleId)
            ->active()
            ->nonProjectBased() // استبعاد بنود المشاريع من الحساب
            ->where('criteria_type', $type)
            ->sum('max_points');
    }

    /**
     * الحصول على البنود الشهرية
     */
    public function scopeMonthly($query)
    {
        return $query->where('evaluation_period', 'monthly');
    }

    /**
     * الحصول على البنود نصف الشهرية
     */
    public function scopeBiWeekly($query)
    {
        return $query->where('evaluation_period', 'bi_weekly');
    }

    /**
     * الحصول على البنود حسب فترة التقييم
     */
    public function scopeForPeriod($query, $period)
    {
        return $query->where('evaluation_period', $period);
    }

    /**
     * الحصول على البنود مجمعة حسب النوع
     */
    public static function getCriteriaGroupedByType($roleId, $evaluationPeriod = 'monthly')
    {
        return static::forRole($roleId)
            ->active()
            ->forPeriod($evaluationPeriod)
            ->nonProjectBased() // استبعاد بنود المشاريع
            ->ordered()
            ->get()
            ->groupBy('criteria_type');
    }
}
