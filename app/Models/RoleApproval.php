<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class RoleApproval extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'role_id',
        'approver_role_id',
        'approval_type',
        'description',
        'is_active',
        'requires_same_project',
        'requires_team_owner'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'requires_same_project' => 'boolean',
        'requires_team_owner' => 'boolean'
    ];

    /**
     * الرول الذي يحتاج اعتماد
     */
    public function role()
    {
        return $this->belongsTo(\Spatie\Permission\Models\Role::class, 'role_id');
    }

    /**
     * الرول الذي يعطي الاعتماد
     */
    public function approverRole()
    {
        return $this->belongsTo(\Spatie\Permission\Models\Role::class, 'approver_role_id');
    }

    /**
     * Configure Activity Log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'role_id', 'approver_role_id', 'approval_type', 'description', 'is_active', 'requires_same_project', 'requires_team_owner'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'تم إنشاء قاعدة اعتماد جديدة',
                'updated' => 'تم تحديث قاعدة الاعتماد',
                'deleted' => 'تم حذف قاعدة الاعتماد',
                default => $eventName
            });
    }

    /**
     * الحصول على جميع المعتمدين لرول معين
     */
    public static function getApproversForRole($roleId, $approvalType = null)
    {
        $query = static::where('role_id', $roleId)
            ->where('is_active', true)
            ->with(['approverRole']);

        if ($approvalType) {
            $query->where('approval_type', $approvalType);
        }

        return $query->get();
    }

    /**
     * فحص إذا كان رول معين يمكنه اعتماد رول آخر
     */
    public static function canApprove($approverRoleId, $roleId, $approvalType)
    {
        return static::where('role_id', $roleId)
            ->where('approver_role_id', $approverRoleId)
            ->where('approval_type', $approvalType)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * الحصول على جميع الأدوار التي يمكن لرول معين اعتمادها
     */
    public static function getRolesCanApprove($approverRoleId, $approvalType = null)
    {
        $query = static::where('approver_role_id', $approverRoleId)
            ->where('is_active', true)
            ->with(['role']);

        if ($approvalType) {
            $query->where('approval_type', $approvalType);
        }

        return $query->get();
    }

    /**
     * إنشاء قاعدة اعتماد جديدة
     */
    public static function createApprovalRule($roleId, $approverRoleId, $approvalType, $description = null)
    {
        return static::create([
            'role_id' => $roleId,
            'approver_role_id' => $approverRoleId,
            'approval_type' => $approvalType,
            'description' => $description
        ]);
    }

    /**
     * فحص الصلاحيات المطلوبة لاعتماد تسليمة معينة
     */
    public static function getRequiredApprovalsForDelivery($userRoles)
    {
        $approvals = [];

        foreach ($userRoles as $role) {
            $administrativeApprovers = static::getApproversForRole($role->id, 'administrative');
            $technicalApprovers = static::getApproversForRole($role->id, 'technical');

            if ($administrativeApprovers->isNotEmpty()) {
                $approvals['administrative'] = $administrativeApprovers;
            }

            if ($technicalApprovers->isNotEmpty()) {
                $approvals['technical'] = $technicalApprovers;
            }
        }

        return $approvals;
    }
}
