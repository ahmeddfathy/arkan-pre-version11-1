<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\HasSecureId;

class RoleEvaluationMapping extends Model
{
    use HasFactory, HasSecureId, LogsActivity;

    protected $table = 'role_evaluation_mappings';

    protected $fillable = [
        'role_to_evaluate_id', // الدور المُراد تقييمه
        'evaluator_role_id',   // الدور الذي يقوم بالتقييم
        'department_name',     // اسم القسم
        'can_evaluate',        // هل يستطيع التقييم
        'can_view',           // هل يستطيع المشاهدة فقط
    ];

    protected $casts = [
        'can_evaluate' => 'boolean',
        'can_view' => 'boolean',
    ];

    /**
     * Configure Activity Log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'role_to_evaluate_id', 'evaluator_role_id', 'department_name',
                'can_evaluate', 'can_view'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'تم إنشاء خريطة تقييم أدوار جديدة',
                'updated' => 'تم تحديث خريطة تقييم الأدوار',
                'deleted' => 'تم حذف خريطة تقييم الأدوار',
                default => $eventName
            });
    }

    /**
     * الدور المُراد تقييمه
     */
    public function roleToEvaluate()
    {
        return $this->belongsTo(Role::class, 'role_to_evaluate_id');
    }

    /**
     * الدور الذي يقوم بالتقييم
     */
    public function evaluatorRole()
    {
        return $this->belongsTo(Role::class, 'evaluator_role_id');
    }

    /**
     * الحصول على الأدوار التي يمكن لدور معين تقييمها
     */
    public static function getRolesCanEvaluate($evaluatorRoleId, $departmentName = null)
    {
        $query = static::with(['roleToEvaluate'])
            ->where('evaluator_role_id', $evaluatorRoleId)
            ->where('can_evaluate', true);

        if ($departmentName) {
            $query->where('department_name', $departmentName);
        }

        return $query->get();
    }

    /**
     * الحصول على الأدوار التي يمكنها تقييم دور معين
     */
    public static function getEvaluatorsForRole($roleToEvaluateId, $departmentName = null)
    {
        $query = static::with(['evaluatorRole'])
            ->where('role_to_evaluate_id', $roleToEvaluateId)
            ->where('can_evaluate', true);

        if ($departmentName) {
            $query->where('department_name', $departmentName);
        }

        return $query->get();
    }

    /**
     * التحقق من إمكانية تقييم دور معين
     */
    public static function canEvaluate($evaluatorRoleId, $roleToEvaluateId, $departmentName = null)
    {
        $query = static::where('evaluator_role_id', $evaluatorRoleId)
            ->where('role_to_evaluate_id', $roleToEvaluateId)
            ->where('can_evaluate', true);

        if ($departmentName) {
            $query->where('department_name', $departmentName);
        }

        return $query->exists();
    }

    /**
     * الحصول على الخريطة الكاملة للتقييم
     */
    public static function getEvaluationMatrix($departmentName = null)
    {
        $query = static::with(['roleToEvaluate', 'evaluatorRole']);

        if ($departmentName) {
            $query->where('department_name', $departmentName);
        }

        return $query->get()->groupBy('department_name');
    }
}
