<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\HasSecureId;

class CriteriaEvaluatorRole extends Model
{
    use HasFactory, HasSecureId, LogsActivity;

    protected $table = 'criteria_evaluator_roles';

    protected $fillable = [
        'criteria_id',
        'evaluator_role_id',
        'department_name',
        'is_primary',
        'evaluation_weight',
        'notes',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'evaluation_weight' => 'integer',
    ];

    /**
     * Configure Activity Log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'criteria_id', 'evaluator_role_id', 'department_name',
                'is_primary', 'evaluation_weight', 'notes'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'تم إنشاء ربط معيار بمقيم جديد',
                'updated' => 'تم تحديث ربط المعيار بالمقيم',
                'deleted' => 'تم حذف ربط المعيار بالمقيم',
                default => $eventName
            });
    }

    /**
     * العلاقة مع البند
     */
    public function criteria()
    {
        return $this->belongsTo(EvaluationCriteria::class, 'criteria_id');
    }

    /**
     * العلاقة مع الدور المقيم
     */
    public function evaluatorRole()
    {
        return $this->belongsTo(Role::class, 'evaluator_role_id');
    }

    /**
     * الحصول على الأدوار المقيمة لبند معين
     */
    public static function getEvaluatorsForCriteria($criteriaId, $departmentName = null)
    {
        $query = static::with(['evaluatorRole'])
            ->where('criteria_id', $criteriaId);

        if ($departmentName) {
            $query->where('department_name', $departmentName);
        }

        return $query->get();
    }

    /**
     * الحصول على البنود التي يمكن لدور معين تقييمها
     */
    public static function getCriteriaForEvaluator($evaluatorRoleId, $departmentName = null)
    {
        $query = static::with(['criteria'])
            ->where('evaluator_role_id', $evaluatorRoleId);

        if ($departmentName) {
            $query->where('department_name', $departmentName);
        }

        return $query->get();
    }

    /**
     * التحقق من إمكانية تقييم بند معين بواسطة دور معين
     */
    public static function canEvaluateCriteria($evaluatorRoleId, $criteriaId, $departmentName = null)
    {
        $query = static::where('evaluator_role_id', $evaluatorRoleId)
            ->where('criteria_id', $criteriaId);

        if ($departmentName) {
            $query->where('department_name', $departmentName);
        }

        return $query->exists();
    }

    /**
     * الحصول على البنود مجمعة حسب المقيم
     */
    public static function getCriteriaGroupedByEvaluator($roleId, $departmentName = null)
    {
        $query = static::with(['criteria', 'evaluatorRole'])
            ->whereHas('criteria', function($q) use ($roleId) {
                $q->where('role_id', $roleId)->where('is_active', true);
            });

        if ($departmentName) {
            $query->where('department_name', $departmentName);
        }

        $results = $query->get();

        return $results->groupBy('evaluator_role_id')->map(function($group) {
            return [
                'evaluator_role' => $group->first()->evaluatorRole,
                'criteria' => $group->map(function($item) {
                    return $item->criteria;
                })
            ];
        });
    }

    /**
     * تعيين مقيم أساسي لبند
     */
    public static function setPrimaryEvaluator($criteriaId, $evaluatorRoleId, $departmentName = null)
    {
        // إزالة العلامة الأساسية من جميع المقيمين للبند
        static::where('criteria_id', $criteriaId)->update(['is_primary' => false]);

        // تعيين المقيم الجديد كأساسي
        return static::updateOrCreate([
            'criteria_id' => $criteriaId,
            'evaluator_role_id' => $evaluatorRoleId,
            'department_name' => $departmentName,
        ], [
            'is_primary' => true,
        ]);
    }

    /**
     * الحصول على المقيم الأساسي للبند
     */
    public static function getPrimaryEvaluator($criteriaId, $departmentName = null)
    {
        $query = static::with(['evaluatorRole'])
            ->where('criteria_id', $criteriaId)
            ->where('is_primary', true);

        if ($departmentName) {
            $query->where('department_name', $departmentName);
        }

        return $query->first();
    }

    /**
     * ربط بند بعدة أدوار مقيمة
     */
    public static function attachCriteriaToEvaluators($criteriaId, $evaluatorRoles, $departmentName = null)
    {
        // حذف الروابط السابقة للقسم المحدد
        $query = static::where('criteria_id', $criteriaId);
        if ($departmentName) {
            $query->where('department_name', $departmentName);
        } else {
            $query->whereNull('department_name');
        }
        $query->delete();

        // إضافة الروابط الجديدة
        foreach ($evaluatorRoles as $roleData) {
            static::create([
                'criteria_id' => $criteriaId,
                'evaluator_role_id' => $roleData['role_id'],
                'department_name' => $departmentName,
                'is_primary' => $roleData['is_primary'] ?? false,
                'evaluation_weight' => $roleData['weight'] ?? 100,
                'notes' => $roleData['notes'] ?? null,
            ]);
        }
    }

    /**
     * Scope للقسم
     */
    public function scopeForDepartment($query, $departmentName)
    {
        return $query->where('department_name', $departmentName);
    }

    /**
     * Scope للمقيمين الأساسيين
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }
}
