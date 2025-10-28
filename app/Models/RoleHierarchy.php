<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\HasSecureId;

class RoleHierarchy extends Model
{
    use HasFactory, HasSecureId, LogsActivity;

    protected $table = 'role_hierarchy';

    protected $fillable = [
        'role_id',
        'hierarchy_level',
        'description'
    ];

    /**
     * Configure Activity Log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['role_id', 'hierarchy_level', 'description'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match ($eventName) {
                'created' => 'تم إنشاء تسلسل هرمي جديد للأدوار',
                'updated' => 'تم تحديث التسلسل الهرمي للأدوار',
                'deleted' => 'تم حذف التسلسل الهرمي للأدوار',
                default => $eventName
            });
    }

    /**
     * علاقة مع جدول الأدوار
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * الحصول على المستوى الهرمي لدور معين
     */
    public static function getRoleHierarchyLevel($roleId)
    {
        return self::where('role_id', $roleId)->value('hierarchy_level');
    }

    /**
     * الحصول على المستوى الهرمي لدور بالاسم
     */
    public static function getRoleHierarchyLevelByName($roleName)
    {
        return self::whereHas('role', function ($query) use ($roleName) {
            $query->where('name', $roleName);
        })->value('hierarchy_level');
    }

    /**
     * الحصول على جميع الأدوار مرتبة حسب المستوى الهرمي
     */
    public static function getAllRolesWithHierarchy()
    {
        return self::with('role')
            ->orderBy('hierarchy_level', 'asc')
            ->get();
    }

    /**
     * الحصول على الأدوار الأقل من مستوى معين أو المساوية له
     */
    public static function getRolesBelowLevel($maxLevel)
    {
        return self::with('role')
            ->where('hierarchy_level', '<=', $maxLevel)
            ->orderBy('hierarchy_level', 'desc')
            ->get();
    }

    /**
     * الحصول على أعلى مستوى هرمي لمستخدم من جميع أدواره
     */
    public static function getUserMaxHierarchyLevel($user)
    {
        $userRoles = $user->roles->pluck('id')->toArray();

        if (empty($userRoles)) {
            return null;
        }

        return self::whereIn('role_id', $userRoles)
            ->max('hierarchy_level');
    }

    /**
     * الحصول على IDs الأدوار حسب المستوى الهرمي
     *
     * @param int $hierarchyLevel المستوى الهرمي المطلوب
     * @return array مصفوفة من IDs الأدوار
     */
    public static function getRoleIdsByHierarchyLevel($hierarchyLevel)
    {
        return self::where('hierarchy_level', $hierarchyLevel)
            ->pluck('role_id')
            ->toArray();
    }

    /**
     * الحصول على IDs أدوار المراجعين (hierarchy_level = 2)
     *
     * @return array مصفوفة من IDs الأدوار
     */
    public static function getReviewerRoleIds()
    {
        return self::getRoleIdsByHierarchyLevel(2);
    }

    /**
     * التحقق من أن الدور له مستوى هرمي معين
     *
     * @param int $roleId معرف الدور
     * @param int $hierarchyLevel المستوى الهرمي
     * @return bool
     */
    public static function isRoleAtLevel($roleId, $hierarchyLevel)
    {
        return self::where('role_id', $roleId)
            ->where('hierarchy_level', $hierarchyLevel)
            ->exists();
    }
}
