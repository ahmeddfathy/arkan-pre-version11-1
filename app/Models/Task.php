<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use App\Traits\HasSecureId;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Task extends Model implements Auditable
{
    use HasFactory, SoftDeletes, AuditableTrait, HasSecureId, LogsActivity;

    protected $auditEvents = [
        'created',
        'updated',
        'deleted',
    ];

    protected $auditInclude = [
        'name',
        'description',
        'project_id',
        'service_id',
        'task_template_id',
        'created_by',
        'status',
        'estimated_hours',
        'estimated_minutes',
        'actual_hours',
        'actual_minutes',
        'start_date',
        'due_date',
        'completed_date',
        'order',
        'points',
    ];

    protected $fillable = [
        'name',
        'description',
        'items', // بنود المهمة (JSON)
        'project_id',
        'service_id',
        'task_template_id',
        'created_by',
        'status',
        'estimated_hours',
        'estimated_minutes',
        'is_flexible_time',
        'actual_hours',
        'actual_minutes',
        'start_date',
        'due_date',
        'completed_date',
        'order',
        'points',
    ];

    protected $casts = [
        'items' => 'array', // تحويل JSON إلى array تلقائياً
        'estimated_hours' => 'integer',
        'estimated_minutes' => 'integer',
        'actual_hours' => 'integer',
        'actual_minutes' => 'integer',
        'order' => 'integer',
        'points' => 'integer',
        'is_flexible_time' => 'boolean',
        'start_date' => 'datetime',
        'due_date' => 'datetime',
        'completed_date' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'description', 'status', 'estimated_hours', 'estimated_minutes', 'actual_hours', 'actual_minutes', 'start_date', 'due_date', 'completed_date', 'points'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the project that owns the task
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the service associated with the task
     */
    public function service()
    {
        return $this->belongsTo(CompanyService::class);
    }

    /**
     * Get the template this task was created from (if any)
     */
    public function template()
    {
        return $this->belongsTo(TaskTemplate::class, 'task_template_id');
    }

    /**
     * Get the graphic task types (many-to-many relationship)
     */
    public function graphicTaskTypes()
    {
        return $this->belongsToMany(GraphicTaskType::class, 'task_graphic_types', 'task_id', 'graphic_task_type_id')
                    ->withTimestamps();
    }

    /**
     * Get the primary graphic task type (first one if multiple)
     */
    public function primaryGraphicTaskType()
    {
        return $this->graphicTaskTypes()->first();
    }

    /**
     * Get users assigned to this task
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'task_users')
                    ->withPivot('role', 'status', 'estimated_hours', 'estimated_minutes',
                                'actual_hours', 'actual_minutes', 'start_date', 'due_date',
                                'completed_date')
                    ->withTimestamps();
    }

    /**
     * Get the user who created this task
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }


    /**
     * Get tasks with new status
     */
    public function scopeNew($query)
    {
        return $query->where('status', 'new');
    }

    /**
     * Get tasks in progress
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Get paused tasks
     */
    public function scopePaused($query)
    {
        return $query->where('status', 'paused');
    }

    /**
     * Get completed tasks
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Get cancelled tasks
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Get tasks belonging to a specific project
     */
    public function scopeForProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Get tasks assigned to a specific user
     */
    public function scopeAssignedTo($query, $userId)
    {
        return $query->whereHas('users', function ($q) use ($userId) {
            $q->where('users.id', $userId);
        });
    }

    /**
     * Get the total estimated time in minutes
     */
    public function getTotalEstimatedMinutesAttribute()
    {
        // إذا كانت المهمة مرنة (بدون وقت محدد)
        if ($this->isFlexibleTime()) {
            return null;
        }

        return ($this->estimated_hours * 60) + $this->estimated_minutes;
    }

    /**
     * التحقق من كون المهمة مرنة (بدون وقت محدد)
     */
    public function isFlexibleTime()
    {
        return (bool) $this->is_flexible_time;
    }

    /**
     * التحقق من كون المهمة لها وقت محدد
     */
    public function hasEstimatedTime()
    {
        return !$this->isFlexibleTime();
    }

    /**
     * Get the total actual time in minutes
     */
    public function getTotalActualMinutesAttribute()
    {
        return ($this->actual_hours * 60) + $this->actual_minutes;
    }

    /**
     * Format estimated time as a string (e.g. "2h 30m")
     */
    public function getEstimatedTimeFormattedAttribute()
    {
        // إذا كانت المهمة مرنة (بدون وقت محدد)
        if ($this->isFlexibleTime()) {
            return 'Flexible';
        }

        if ($this->estimated_hours == 0 && $this->estimated_minutes == 0) {
            return '0m';
        }

        $parts = [];

        if ($this->estimated_hours > 0) {
            $parts[] = $this->estimated_hours . 'h';
        }

        if ($this->estimated_minutes > 0) {
            $parts[] = $this->estimated_minutes . 'm';
        }

        return implode(' ', $parts);
    }

    /**
     * Format actual time as a string (e.g. "2h 30m")
     */
    public function getActualTimeFormattedAttribute()
    {
        if ($this->actual_hours == 0 && $this->actual_minutes == 0) {
            return '0m';
        }

        $parts = [];

        if ($this->actual_hours > 0) {
            $parts[] = $this->actual_hours . 'h';
        }

        if ($this->actual_minutes > 0) {
            $parts[] = $this->actual_minutes . 'm';
        }

        return implode(' ', $parts);
    }

    /**
     * Get time status indicator (on time, delayed)
     */
    public function getTimeStatusAttribute()
    {
        // إذا كانت المهمة مرنة، الوقت الفعلي هو وقت المهمة بدون مقارنة
        if ($this->isFlexibleTime()) {
            return $this->status === 'completed' ? 'flexible_completed' : null;
        }

        // If task isn't completed yet, or doesn't have estimated time
        if ($this->status !== 'completed' || $this->getTotalEstimatedMinutesAttribute() === 0) {
            return null;
        }

        return $this->getTotalActualMinutesAttribute() <= $this->getTotalEstimatedMinutesAttribute()
            ? 'on_time'
            : 'delayed';
    }

    /**
     * Check if the task is overdue
     */
    public function getIsOverdueAttribute()
    {
        if (!$this->due_date || in_array($this->status, ['completed', 'cancelled'])) {
            return false;
        }

        return $this->due_date < Carbon::now();
    }

    /**
     * حساب النقاط من نوع المهمة الجرافيكية إذا كان محدد
     */
    public function getCalculatedPointsAttribute()
    {
        $primaryGraphicTaskType = $this->primaryGraphicTaskType();
        if ($primaryGraphicTaskType) {
            return $primaryGraphicTaskType->points;
        }

        return $this->points ?? 0;
    }

    /**
     * حساب الوقت المقدر من نوع المهمة الجرافيكية إذا كان محدد
     */
    public function getCalculatedEstimatedMinutesAttribute()
    {
        $primaryGraphicTaskType = $this->primaryGraphicTaskType();
        if ($primaryGraphicTaskType) {
            return $primaryGraphicTaskType->average_minutes;
        }

        return $this->getTotalEstimatedMinutesAttribute();
    }

    /**
     * التحقق من كون المهمة مهمة جرافيكية
     */
    public function isGraphicTask()
    {
        return $this->graphicTaskTypes()->exists();
    }

    /**
     * الحصول على تفاصيل نوع المهمة الجرافيكية
     */
    public function getGraphicTaskDetailsAttribute()
    {
        $primaryGraphicTaskType = $this->primaryGraphicTaskType();
        if (!$primaryGraphicTaskType) {
            return null;
        }

        return [
            'type_name' => $primaryGraphicTaskType->name ?? 'غير محدد',
            'points' => $primaryGraphicTaskType->points ?? 0,
            'average_time' => $primaryGraphicTaskType->average_minutes ?? 0,
            'time_range' => "{$primaryGraphicTaskType->min_minutes} - {$primaryGraphicTaskType->max_minutes} دقيقة",
        ];
    }

    /**
     * Update task status based on assigned users' progress
     */
    public function updateStatus()
    {
        $taskUsers = $this->users()->withPivot('status')->get();

        if ($taskUsers->isEmpty()) {
            return;
        }

        // If all users have completed their part
        if ($taskUsers->every(function ($user) {
            return $user->pivot->status === 'completed';
        })) {
            $this->update([
                'status' => 'completed',
                'completed_date' => Carbon::now(),
            ]);
            return;
        }

        // If any user is in progress
        if ($taskUsers->contains(function ($user) {
            return $user->pivot->status === 'in_progress';
        })) {
            $this->update(['status' => 'in_progress']);
            return;
        }

        // If any user is paused and no one is in progress
        if ($taskUsers->contains(function ($user) {
            return $user->pivot->status === 'paused';
        })) {
            $this->update(['status' => 'paused']);
            return;
        }

        // Default case - if everyone is new
        $this->update(['status' => 'new']);
    }

    /**
     * العلاقة مع تعديلات المهمة
     */
    public function revisions()
    {
        return $this->hasMany(TaskRevision::class);
    }

    /**
     * الحصول على عدد التعديلات للمهمة
     */
    public function getRevisionsCountAttribute()
    {
        return $this->revisions()->count();
    }

    /**
     * الحصول على عدد التعديلات المعلقة للمهمة
     */
    public function getPendingRevisionsCountAttribute()
    {
        return $this->revisions()->where('status', 'pending')->count();
    }

    /**
     * الحصول على عدد التعديلات المقبولة للمهمة
     */
    public function getApprovedRevisionsCountAttribute()
    {
        return $this->revisions()->where('status', 'approved')->count();
    }

    /**
     * الحصول على عدد التعديلات المرفوضة للمهمة
     */
    public function getRejectedRevisionsCountAttribute()
    {
        return $this->revisions()->where('status', 'rejected')->count();
    }

    /**
     * الحصول على حالة التعديلات الإجمالية للمهمة
     */
    public function getRevisionsStatusAttribute()
    {
        $total = $this->revisions_count;
        if ($total == 0) return 'none';

        $approved = $this->approved_revisions_count;
        $rejected = $this->rejected_revisions_count;
        $pending = $this->pending_revisions_count;

        if ($pending > 0) {
            if ($approved > 0 || $rejected > 0) {
                return 'mixed'; // خليط من الحالات
            }
            return 'pending'; // كلها معلقة
        }

        if ($approved > 0 && $rejected == 0) {
            return 'approved'; // كلها مقبولة
        }

        if ($rejected > 0 && $approved == 0) {
            return 'rejected'; // كلها مرفوضة
        }

        return 'mixed'; // خليط من مقبول ومرفوض
    }
}
