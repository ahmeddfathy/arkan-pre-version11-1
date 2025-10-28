<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Permission\Models\Role;
use App\Traits\HasSecureId;

class TemplateTask extends Model implements Auditable
{
    use HasFactory, AuditableTrait, HasSecureId, LogsActivity;

    protected $auditEvents = [
        'created',
        'updated',
        'deleted',
    ];

    protected $auditInclude = [
        'task_template_id',
        'role_id',
        'name',
        'description',
        'order',
        'estimated_hours',
        'estimated_minutes',
        'is_active',
        'points',
    ];

    protected $fillable = [
        'task_template_id',
        'role_id',
        'name',
        'description',
        'items', // بنود مهمة القالب (JSON)
        'order',
        'estimated_hours',
        'estimated_minutes',
        'is_active',
        'points',
        'is_flexible_time',
    ];

    protected $casts = [
        'items' => 'array', // تحويل JSON إلى array تلقائياً
        'estimated_hours' => 'integer',
        'estimated_minutes' => 'integer',
        'order' => 'integer',
        'points' => 'integer',
        'is_active' => 'boolean',
        'is_flexible_time' => 'boolean',
    ];


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'task_template_id', 'role_id', 'name', 'description', 'order',
                'estimated_hours', 'estimated_minutes', 'is_active', 'points', 'is_flexible_time'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'تم إنشاء مهمة قالب جديدة',
                'updated' => 'تم تحديث مهمة القالب',
                'deleted' => 'تم حذف مهمة القالب',
                default => $eventName
            });
    }


    public function template()
    {
        return $this->belongsTo(TaskTemplate::class, 'task_template_id');
    }


    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }


    public function getTotalEstimatedMinutesAttribute()
    {
        // إذا كانت المهمة مرنة (بدون وقت محدد)
        if ($this->isFlexibleTime()) {
            return null;
        }

        return ($this->estimated_hours * 60) + $this->estimated_minutes;
    }


    public function isFlexibleTime()
    {
        return (bool) $this->is_flexible_time;
    }


    public function hasEstimatedTime()
    {
        return !$this->isFlexibleTime();
    }

    /**
     * تنسيق الوقت المقدر كسلسلة نصية
     */
    public function getEstimatedTimeFormattedAttribute()
    {
        // إذا كانت المهمة مرنة (بدون وقت محدد)
        if ($this->isFlexibleTime()) {
            return 'مرن';
        }

        if ($this->estimated_hours == 0 && $this->estimated_minutes == 0) {
            return '0م';
        }

        $parts = [];

        if ($this->estimated_hours > 0) {
            $parts[] = $this->estimated_hours . 'س';
        }

        if ($this->estimated_minutes > 0) {
            $parts[] = $this->estimated_minutes . 'د';
        }

        return implode(' ', $parts);
    }

    /**
     * المستخدمون المرتبطون بهذه المهمة (Kanban)
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'template_task_user', 'template_task_id', 'user_id')
            ->withPivot(['status', 'started_at', 'paused_at', 'completed_at', 'actual_minutes', 'project_id', 'season_id'])
            ->withTimestamps();
    }

    /**
     * الحصول على تخصيصات المهمة للمستخدمين (TemplateTaskUser records)
     */
    public function templateTaskUsers()
    {
        return $this->hasMany(TemplateTaskUser::class, 'template_task_id');
    }

    /**
     * التحقق من إمكانية تخصيص المهمة لمستخدم معين حسب دوره
     */
    public function canBeAssignedToUser($user)
    {
        // إذا لم يكن للمهمة دور محدد، فهي متاحة للجميع
        if (!$this->role_id) {
            return true;
        }

        // التحقق من أن المستخدم لديه الدور المطلوب
        return $user->hasRole($this->role_id);
    }

    /**
     * الحصول على نص الدور أو "للجميع"
     */
    public function getRoleDisplayAttribute()
    {
        return $this->role ? $this->role->name : 'للجميع';
    }

    /**
     * التحقق من كون المهمة مخصصة لدور معين
     */
    public function isRoleSpecific()
    {
        return !is_null($this->role_id);
    }

    /**
     * scope للمهام التي يمكن تخصيصها لمستخدم معين
     */
    public function scopeAssignableToUser($query, $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->whereNull('role_id') // المهام المتاحة للجميع
              ->orWhereIn('role_id', $user->roles->pluck('id')); // المهام للأدوار التي يملكها المستخدم
        });
    }

    /**
     * scope للمهام المخصصة لدور معين
     */
    public function scopeForRole($query, $roleId)
    {
        if ($roleId) {
            return $query->where('role_id', $roleId);
        }

        return $query->whereNull('role_id');
    }

    /**
     * scope للمهام العامة (بدون دور محدد)
     */
    public function scopeGeneral($query)
    {
        return $query->whereNull('role_id');
    }
}
