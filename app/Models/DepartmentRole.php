<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\HasSecureId;

class DepartmentRole extends Model
{
    use HasSecureId, LogsActivity;

    protected $table = 'department_roles';

    protected $fillable = [
        'department_name',
        'role_id',
        'hierarchy_level',
    ];

    /**
     * Configure Activity Log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['department_name', 'role_id', 'hierarchy_level'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'تم إنشاء ربط دور قسم جديد',
                'updated' => 'تم تحديث ربط دور القسم',
                'deleted' => 'تم حذف ربط دور القسم',
                default => $eventName
            });
    }

    /**
     * العلاقة مع نموذج Role
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * الحصول على روابط الأقسام مجمعة حسب اسم القسم
     */
    public static function getDepartmentRolesGrouped()
    {
        return static::with('role')
            ->orderBy('department_name')
            ->orderBy('role_id')
            ->get()
            ->groupBy('department_name');
    }

    /**
     * الحصول على أدوار قسم معين
     */
    public static function getDepartmentRoles($departmentName)
    {
        return static::with('role')
            ->where('department_name', $departmentName)
            ->orderBy('role_id')
            ->get();
    }

    /**
     * التحقق من وجود ربط معين
     */
    public static function mappingExists($departmentName, $roleId, $excludeId = null)
    {
        $query = static::where('department_name', $departmentName)
            ->where('role_id', $roleId);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * الحصول على مستوى دور المستخدم في قسمه
     */
    public static function getUserDepartmentHierarchyLevel($user)
    {
        if (!$user->department) {
            return null;
        }

        $userRoles = $user->roles->pluck('id')->toArray();

        return static::where('department_name', $user->department)
            ->whereIn('role_id', $userRoles)
            ->max('hierarchy_level');
    }

    /**
     * الحصول على الأدوار في قسم معين حسب المستوى الهرمي
     */
    public static function getRolesByHierarchyLevel($departmentName, $maxLevel = null)
    {
        $query = static::with('role')
            ->where('department_name', $departmentName);

        if ($maxLevel !== null) {
            $query->where('hierarchy_level', '<=', $maxLevel);
        }

        return $query->orderBy('hierarchy_level', 'desc')
            ->orderBy('role_id')
            ->get();
    }

        /**
     * الحصول على خريطة المستويات الهرمية (مرنة)
     */
    public static function getHierarchyLevels()
    {
        // إرجاع مستويات من 1 إلى 10 للمرونة
        $levels = [];
        for ($i = 1; $i <= 10; $i++) {
            $levels[$i] = "مستوى {$i}";
        }
        return $levels;
    }

    /**
     * الحصول على أعلى مستوى هرمي مستخدم في النظام
     */
    public static function getMaxHierarchyLevel()
    {
        return static::max('hierarchy_level') ?? 1;
    }

    /**
     * الحصول على المستويات المستخدمة فعلياً في قسم معين
     */
    public static function getUsedHierarchyLevels($departmentName = null)
    {
        $query = static::select('hierarchy_level')
            ->distinct()
            ->orderBy('hierarchy_level', 'desc');

        if ($departmentName) {
            $query->where('department_name', $departmentName);
        }

        return $query->pluck('hierarchy_level')->toArray();
    }
}
