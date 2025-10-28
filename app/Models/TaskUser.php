<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\HasSecureId;
use App\Traits\HasNTPTime;

class TaskUser extends Model implements Auditable
{
    use HasFactory, AuditableTrait, HasSecureId, LogsActivity, HasNTPTime;

    protected $auditEvents = [
        'created',
        'updated',
        'deleted',
    ];

    protected $auditInclude = [
        'task_id',
        'user_id',
        'season_id',
        'role',
        'status',
        'work_status',
        'estimated_hours',
        'estimated_minutes',
        'actual_hours',
        'actual_minutes',
        'start_date',
        'due_date',
        'completed_date',
        'is_approved',
        'awarded_points',
        'approval_note',
        'approved_by',
        'approved_at',
        'is_transferred',
        'transferred_to_user_id',
        'transferred_record_id',
        'transferred_at',
        'transfer_type',
        'transfer_reason',
        'transfer_points',
        'original_task_user_id',
        'is_additional_task',
        'task_source',
    ];

    protected $table = 'task_users';

    protected $fillable = [
        'task_id',
        'user_id',
        'season_id',
        'role',
        'status',
        'work_status',
        'estimated_hours',
        'estimated_minutes',
        'actual_hours',
        'actual_minutes',
        'items', // بنود المهمة مع حالاتها (JSON)
        'start_date',
        'due_date',
        'completed_date',
        'is_approved',
        'awarded_points',
        'approval_note',
        'approved_by',
        'approved_at',
        'is_transferred',
        'transferred_to_user_id',
        'transferred_record_id',
        'transferred_at',
        'transfer_type',
        'transfer_reason',
        'transfer_points',
        'original_task_user_id',
        'is_additional_task',
        'task_source',
        'is_flexible_time',
        // حقول الاعتماد الإداري والفني
        'administrative_approval',
        'administrative_approval_at',
        'administrative_approver_id',
        'technical_approval',
        'technical_approval_at',
        'technical_approver_id',
        'administrative_notes',
        'technical_notes',
    ];

    protected $casts = [
        'items' => 'array', // تحويل JSON إلى array تلقائياً
        'estimated_hours' => 'integer',
        'estimated_minutes' => 'integer',
        'actual_hours' => 'integer',
        'actual_minutes' => 'integer',
        'transfer_points' => 'integer',
        'transferred_at' => 'datetime',
        'is_additional_task' => 'boolean',
        'start_date' => 'datetime',
        'due_date' => 'datetime',
        'completed_date' => 'datetime',
        'is_approved' => 'boolean',
        'awarded_points' => 'integer',
        'approved_at' => 'datetime',
        'is_transferred' => 'boolean',
        'transferred_from_at' => 'datetime',
        'is_flexible_time' => 'boolean',
        // casts للاعتماد الإداري والفني
        'administrative_approval' => 'boolean',
        'administrative_approval_at' => 'datetime',
        'technical_approval' => 'boolean',
        'technical_approval_at' => 'datetime',
    ];

    /**
     * Configure Activity Log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'task_id', 'user_id', 'season_id', 'role', 'status',
                'estimated_hours', 'estimated_minutes', 'actual_hours', 'actual_minutes',
                'start_date', 'due_date', 'completed_date', 'is_approved', 'awarded_points',
                'is_transferred', 'transfer_reason'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'تم تعيين مهمة لمستخدم',
                'updated' => 'تم تحديث مهمة المستخدم',
                'deleted' => 'تم حذف تعيين المهمة',
                default => $eventName
            });
    }

    /**
     * Get the season associated with this assignment
     */
    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    /**
     * Get the task associated with this assignment
     */
    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the user associated with this assignment
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * الحصول على المستخدم الأصلي الذي تم تخصيص المهمة له في البداية
     */
    public function getOriginalUserAttribute()
    {
        return $this->originalTaskUser?->user;
    }

    /**
     * العلاقة مع المستخدم المنقولة إليه المهمة
     */
    public function transferredToUser()
    {
        return $this->belongsTo(User::class, 'transferred_to_user_id');
    }

    /**
     * العلاقة مع السجل الجديد المنقول
     */
    public function transferredRecord()
    {
        return $this->belongsTo(TaskUser::class, 'transferred_record_id');
    }

    /**
     * العلاقة مع السجل الأصلي (إذا كان السجل الحالي منقول من مكان آخر)
     */
    public function originalTaskUser()
    {
        return $this->belongsTo(TaskUser::class, 'original_task_user_id');
    }

    /**
     * التحقق من كون المهمة منقولة
     */
    public function isTransferred(): bool
    {
        return $this->is_transferred === true;
    }

    /**
     * التحقق من كون المهمة منقولة من مهمة أخرى
     */
    public function isTransferredFromAnother(): bool
    {
        return !is_null($this->original_task_user_id);
    }

    /**
     * الحصول على معلومات النقل
     */
    public function getTransferInfoAttribute(): ?array
    {
        if (!$this->isTransferred()) {
            return null;
        }

        return [
            'transferred_to_user_name' => $this->transferredToUser?->name,
            'transferred_at' => $this->transferred_at,
            'transfer_type' => $this->transfer_type,
            'transfer_reason' => $this->transfer_reason,
            'transfer_points' => $this->transfer_points,
            'days_since_transfer' => $this->transferred_at?->diffForHumans()
        ];
    }

    /**
     * التحقق من كون المهمة إضافية منقولة
     */
    public function isAdditionalTask(): bool
    {
        return $this->is_additional_task === true;
    }

    /**
     * التحقق من مصدر المهمة
     */
    public function getTaskSourceAttribute(): string
    {
        return $this->attributes['task_source'] ?? 'assigned';
    }

    /**
     * التحقق من كون المهمة أصلية أم منقولة
     */
    public function isOriginalAssignment(): bool
    {
        return $this->task_source === 'assigned' || is_null($this->task_source);
    }

    /**
     * الحصول على نص مصدر المهمة
     */
    public function getTaskSourceTextAttribute(): string
    {
        return match($this->task_source) {
            'transferred' => 'منقولة',
            'assigned' => 'مخصصة',
            default => 'غير محدد'
        };
    }

    /**
     * الحصول على معلومات شاملة عن المهمة
     */
    public function getTaskInfoAttribute(): array
    {
        return [
            'is_transferred' => $this->isTransferred(),
            'is_additional_task' => $this->isAdditionalTask(),
            'is_original_assignment' => $this->isOriginalAssignment(),
            'task_source' => $this->task_source_text,
            'transfer_info' => $this->transfer_info
        ];
    }

    /**
     * Scope للمهام الأصلية (المخصصة)
     */
    public function scopeOriginalTasks($query)
    {
        return $query->where('task_source', 'assigned')
                    ->orWhereNull('task_source');
    }

    /**
     * Scope للمهام المنقولة (الإضافية)
     */
    public function scopeTransferredTasks($query)
    {
        return $query->where('task_source', 'transferred');
    }

    /**
     * Scope للمهام الإضافية
     */
    public function scopeAdditionalTasks($query)
    {
        return $query->where('is_additional_task', true);
    }

    /**
     * Scope للمهام المنقولة من مستخدم آخر
     */
    public function scopeReceivedTransfers($query)
    {
        return $query->whereNotNull('original_task_user_id');
    }

    /**
     * Scope للمهام التي تم نقلها لمستخدم آخر
     */
    public function scopeSentTransfers($query)
    {
        return $query->where('is_transferred', true);
    }

    /**
     * Get the attachments for this task (for standalone tasks only)
     */
    public function attachments()
    {
        return $this->hasMany(TaskAttachment::class);
    }

    /**
     * Get the count of attachments for this task
     */
    public function getAttachmentsCountAttribute()
    {
        return $this->attachments()->where('is_uploaded', true)->count();
    }

    /**
     * Check if this task can have attachments (not linked to a project)
     */
    public function canHaveAttachments()
    {
        return is_null($this->project_id);
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
        // التحقق من حقل is_flexible_time أولاً، ثم التحقق من الطريقة القديمة للتوافق مع البيانات الموجودة
        return (bool) $this->is_flexible_time || (is_null($this->estimated_hours) && is_null($this->estimated_minutes));
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
     * Format estimated time as a string
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
     * Format actual time as a string
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
     * Check if the assignment is overdue
     */
    public function getIsOverdueAttribute()
    {
        if (!$this->due_date || in_array($this->status, ['completed', 'cancelled'])) {
            return false;
        }

        return $this->due_date < Carbon::now();
    }

    /**
     * Update the actual time based on manual input (النظام الأصلي)
     */
    public function updateActualTime($minutesSpent)
    {
        $totalMinutes = $this->getTotalActualMinutesAttribute() + $minutesSpent;

        $hours = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes % 60;

        $this->update([
            'actual_hours' => $hours,
            'actual_minutes' => $minutes
        ]);
    }

    /**
     * نقل المهمة إلى مستخدم آخر مع النقاط
     */
    public function transferToUser(User $toUser, int $transferPoints, string $reason = null): array
    {
        $transferService = app(\App\Services\Tasks\TaskTransferService::class);
        return $transferService->transferTask($this, $toUser, $transferPoints, $reason);
    }

    /**
     * التحقق من إمكانية نقل المهمة
     */
    public function canBeTransferred(User $toUser, int $transferPoints): array
    {
        $transferService = app(\App\Services\Tasks\TaskTransferService::class);
        return $transferService->canTransferTask($this, $toUser, $transferPoints);
    }

    /**
     * الحصول على تاريخ النقل للمهمة
     */
    public function getTransferHistory(): array
    {
        return DB::table('task_transfers')
            ->where('task_user_id', $this->id)
            ->orderBy('transferred_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * العلاقة مع المستخدم الذي وافق على المهمة
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * التحقق من موافقة المهمة
     */
    public function isApproved(): bool
    {
        return $this->is_approved && $this->approved_at;
    }

    /**
     * الحصول على النقاط المستحقة (المحددة أو الافتراضية)
     */
    public function getEarnedPointsAttribute(): int
    {
        if ($this->is_approved && $this->awarded_points !== null) {
            return $this->awarded_points;
        }

        return $this->task ? ($this->task->points ?? 0) : 0;
    }

    /**
     * التحقق من إمكانية الموافقة على المهمة
     */
    public function canBeApproved(): bool
    {
        return $this->status === 'completed' && !$this->is_approved;
    }

    /**
     * Scope: المهام المنقولة من مستخدمين آخرين
     */
    public function scopeTransferredFromOthers($query)
    {
        return $query->whereNotNull('original_task_user_id');
    }


    /**
     * التحقق من انتهاء due_date المهمة
     */
    public function isOverdue(): bool
    {
        if (!$this->due_date) {
            return false;
        }

        return $this->due_date->isPast() && $this->status !== 'completed';
    }

    /**
     * التحقق من قرب انتهاء due_date المهمة (خلال 24 ساعة)
     */
    public function isDueSoon(): bool
    {
        if (!$this->due_date) {
            return false;
        }

        return $this->due_date->isFuture() &&
               $this->due_date->diffInHours(now()) <= 24 &&
               $this->status !== 'completed';
    }

    /**
     * الحصول على المدة المتبقية للـ due_date
     */
    public function getRemainingTimeAttribute(): ?string
    {
        if (!$this->due_date) {
            return null;
        }

        if ($this->due_date->isPast()) {
            return 'منتهية منذ ' . $this->due_date->diffForHumans();
        }

        return 'باقي ' . $this->due_date->diffForHumans();
    }

    /**
     * الحصول على حالة due_date
     */
    public function getDeadlineStatusAttribute(): string
    {
        if (!$this->due_date) {
            return 'بدون موعد نهائي';
        }

        if ($this->status === 'completed') {
            return 'مكتملة';
        }

        if ($this->isOverdue()) {
            return 'متأخرة';
        }

        if ($this->isDueSoon()) {
            return 'تنتهي قريباً';
        }

        return 'في الموعد';
    }

    /**
     * الحصول على لون due_date حسب الحالة
     */
    public function getDeadlineColorAttribute(): string
    {
        if (!$this->due_date) {
            return 'gray';
        }

        if ($this->status === 'completed') {
            return 'green';
        }

        if ($this->isOverdue()) {
            return 'red';
        }

        if ($this->isDueSoon()) {
            return 'orange';
        }

        return 'blue';
    }

    /**
     * Scope: المهام المتأخرة عن due_date
     */
    public function scopeOverdue($query)
    {
        return $query->whereNotNull('due_date')
                    ->where('due_date', '<', now())
                    ->where('status', '!=', 'completed');
    }

    /**
     * Scope: المهام التي تنتهي قريباً (خلال 24 ساعة)
     */
    public function scopeDueSoon($query)
    {
        return $query->whereNotNull('due_date')
                    ->where('due_date', '>', now())
                    ->where('due_date', '<=', now()->addHours(24))
                    ->where('status', '!=', 'completed');
    }

    /**
     * Scope: المهام حسب due_date
     */
    public function scopeWithDeadline($query)
    {
        return $query->whereNotNull('due_date');
    }

    /**
     * Scope: المهام بدون due_date
     */
    public function scopeWithoutDeadline($query)
    {
        return $query->whereNull('due_date');
    }

    /**
     * Get task notes for this task assignment
     */
    public function notes()
    {
        return $this->hasMany(TaskNote::class, 'task_user_id')
                    ->where('task_type', 'regular')
                    ->orderBy('created_at', 'desc');
    }

    /**
     * Get notes count for this task assignment
     */
    public function getNotesCountAttribute()
    {
        return $this->notes()->count();
    }

    /**
     * Get time logs for this task assignment
     */
    public function timeLogs()
    {
        return $this->hasMany(TaskTimeLog::class, 'task_user_id')
                    ->orderBy('started_at', 'desc');
    }

    /**
     * Get active time log for this task assignment
     */
    public function activeTimeLog()
    {
        return $this->hasOne(TaskTimeLog::class, 'task_user_id')
                    ->whereNull('stopped_at')
                    ->latest('started_at');
    }

    /**
     * التحقق من وجود جلسة نشطة
     */
    public function hasActiveTimeLog(): bool
    {
        return $this->activeTimeLog()->exists();
    }

    /**
     * بدء جلسة عمل جديدة
     */
    public function startTimeLog(): TaskTimeLog
    {
        $now = $this->getCurrentCairoTime();

        // إيقاف أي جلسة نشطة للمستخدم
        TaskTimeLog::where('user_id', $this->user_id)
                  ->whereNull('stopped_at')
                  ->update(['stopped_at' => $now]);

        return TaskTimeLog::create([
            'task_user_id' => $this->id,
            'user_id' => $this->user_id,
            'task_type' => 'regular',
            'started_at' => $now,
            'work_date' => $now->toDateString(),
            'season_id' => $this->season_id,
        ]);
    }

        /**
     * إيقاف الجلسة النشطة (time logs منفصل عن النظام الأصلي)
     */
    public function stopActiveTimeLog(): ?TaskTimeLog
    {
        $activeLog = $this->activeTimeLog()->first();

        if ($activeLog) {
            $activeLog->stop();

            // لا نحدث actual_time - النظام الأصلي منفصل عن time logs

            return $activeLog;
        }

        return null;
    }

    /**
     * الحصول على إجمالي الوقت المسجل اليوم
     */
    public function getTodayLoggedMinutes(): int
    {
        $today = $this->getCurrentCairoTime()->toDateString();

        return $this->timeLogs()
                   ->where('work_date', $today)
                   ->whereNotNull('stopped_at')
                   ->sum('duration_minutes') ?? 0;
    }

    /**
     * العلاقة مع تعديلات المهمة
     */
    public function revisions()
    {
        return $this->hasMany(TaskRevision::class, 'task_user_id');
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
                return 'mixed';
            }
            return 'pending';
        }

        if ($approved > 0 && $rejected == 0) {
            return 'approved';
        }

        if ($rejected > 0 && $approved == 0) {
            return 'rejected';
        }

        return 'mixed';
    }

    /**
     * أخطاء الموظف في هذه المهمة
     */
    public function errors()
    {
        return $this->morphMany(EmployeeError::class, 'errorable');
    }

    /**
     * الحصول على عدد الأخطاء
     */
    public function getErrorsCountAttribute()
    {
        return $this->errors()->count();
    }

    /**
     * الحصول على عدد الأخطاء الجوهرية
     */
    public function getCriticalErrorsCountAttribute()
    {
        return $this->errors()->where('error_type', 'critical')->count();
    }

    /**
     * الحصول على عدد الأخطاء العادية
     */
    public function getNormalErrorsCountAttribute()
    {
        return $this->errors()->where('error_type', 'normal')->count();
    }

    /**
     * التحقق من وجود أخطاء
     */
    public function hasErrors(): bool
    {
        return $this->errors()->exists();
    }

    /**
     * التحقق من وجود أخطاء جوهرية
     */
    public function hasCriticalErrors(): bool
    {
        return $this->errors()->where('error_type', 'critical')->exists();
    }

    // ======================================
    // نظام الاعتماد الإداري والفني للمشاريع
    // ======================================

    /**
     * العلاقة مع المعتمد الإداري
     */
    public function administrativeApprover()
    {
        return $this->belongsTo(User::class, 'administrative_approver_id');
    }

    /**
     * العلاقة مع المعتمد الفني
     */
    public function technicalApprover()
    {
        return $this->belongsTo(User::class, 'technical_approver_id');
    }

    /**
     * فحص ما إذا كانت التاسك مرتبطة بمشروع
     */
    public function isProjectTask(): bool
    {
        return $this->task && !is_null($this->task->project_id);
    }

    /**
     * فحص ما إذا كان المستخدم حصل على الاعتماد الإداري
     */
    public function hasAdministrativeApproval(): bool
    {
        return (bool) $this->administrative_approval;
    }

    /**
     * فحص ما إذا كان المستخدم حصل على الاعتماد الفني
     */
    public function hasTechnicalApproval(): bool
    {
        return (bool) $this->technical_approval;
    }

    /**
     * فحص ما إذا كان المستخدم حصل على جميع الاعتمادات المطلوبة
     */
    public function hasAllRequiredApprovals(): bool
    {
        // التاسكات العادية (بدون project_id) لا تحتاج اعتمادات إضافية
        if (!$this->isProjectTask()) {
            return true;
        }

        $requiredApprovals = $this->getRequiredApprovals();

        if ($requiredApprovals['needs_administrative'] && !$this->hasAdministrativeApproval()) {
            return false;
        }

        if ($requiredApprovals['needs_technical'] && !$this->hasTechnicalApproval()) {
            return false;
        }

        return true;
    }

    /**
     * إعطاء الاعتماد الإداري
     */
    public function grantAdministrativeApproval($approverId, $notes = null): bool
    {
        return $this->update([
            'administrative_approval' => true,
            'administrative_approval_at' => $this->getCurrentCairoTime(),
            'administrative_approver_id' => $approverId,
            'administrative_notes' => $notes,
        ]);
    }

    /**
     * إعطاء الاعتماد الفني
     */
    public function grantTechnicalApproval($approverId, $notes = null): bool
    {
        return $this->update([
            'technical_approval' => true,
            'technical_approval_at' => $this->getCurrentCairoTime(),
            'technical_approver_id' => $approverId,
            'technical_notes' => $notes,
        ]);
    }

    /**
     * إلغاء الاعتماد الإداري
     */
    public function revokeAdministrativeApproval(): bool
    {
        return $this->update([
            'administrative_approval' => false,
            'administrative_approval_at' => null,
            'administrative_approver_id' => null,
            'administrative_notes' => null,
        ]);
    }

    /**
     * إلغاء الاعتماد الفني
     */
    public function revokeTechnicalApproval(): bool
    {
        return $this->update([
            'technical_approval' => false,
            'technical_approval_at' => null,
            'technical_approver_id' => null,
            'technical_notes' => null,
        ]);
    }

    /**
     * فحص ما إذا كان يمكن تغيير حالة المهمة
     */
    public function canChangeStatus(): bool
    {
        // لا يمكن تغيير الحالة إذا تم اعتماد المهمة مسبقاً
        if ($this->hasAdministrativeApproval() || $this->hasTechnicalApproval()) {
            return false;
        }

        return true;
    }

    /**
     * فحص ما إذا كان يمكن سحب المهمة في الكانبان
     */
    public function canBeDragged(): bool
    {
        // لا يمكن السحب إذا تم اعتماد المهمة مسبقاً
        if ($this->hasAdministrativeApproval() || $this->hasTechnicalApproval()) {
            return false;
        }

        // الشروط الأساسية للسحب
        return ($this->user_id === auth()->user()?->id) && !($this->is_transferred ?? false);
    }

    /**
     * الحصول على الاعتمادات المطلوبة للتاسك حسب رول المستخدم
     */
    public function getRequiredApprovals(): array
    {
        // التاسكات العادية (بدون project_id) لا تحتاج اعتمادات
        if (!$this->isProjectTask()) {
            return [
                'needs_administrative' => false,
                'needs_technical' => false,
                'administrative_approvers' => collect(),
                'technical_approvers' => collect(),
            ];
        }

        $userRoles = $this->user->roles;
        $needsAdministrative = false;
        $needsTechnical = false;

        foreach ($userRoles as $role) {
            // فحص إذا كان الرول يحتاج اعتماد إداري
            $adminApprovers = RoleApproval::getApproversForRole($role->id, 'administrative');
            if ($adminApprovers->isNotEmpty()) {
                $needsAdministrative = true;
            }

            // فحص إذا كان الرول يحتاج اعتماد فني
            $techApprovers = RoleApproval::getApproversForRole($role->id, 'technical');
            if ($techApprovers->isNotEmpty()) {
                $needsTechnical = true;
            }
        }

        return [
            'needs_administrative' => $needsAdministrative,
            'needs_technical' => $needsTechnical,
            'administrative_approvers' => $needsAdministrative ?
                RoleApproval::getApproversForRole($userRoles->pluck('id')->toArray(), 'administrative') : collect(),
            'technical_approvers' => $needsTechnical ?
                RoleApproval::getApproversForRole($userRoles->pluck('id')->toArray(), 'technical') : collect(),
        ];
    }

    /**
     * فحص ما إذا كان المستخدم المحدد يمكنه اعتماد هذه التاسك
     */
    public function canUserApprove($userId, $approvalType): bool
    {
        // التاسكات العادية لا تحتاج اعتمادات
        if (!$this->isProjectTask()) {
            return false;
        }

        // الحصول على أدوار المستخدم الذي يريد الاعتماد
        $approverUser = User::find($userId);
        if (!$approverUser) {
            return false;
        }

        $approverRoles = $approverUser->roles->pluck('id')->toArray();

        // الحصول على أدوار الموظف المكلف بالتاسك
        $taskUserRoles = $this->user->roles->pluck('id')->toArray();

        // فحص إذا كان أي من أدوار المعتمد يمكنه اعتماد أي من أدوار الموظف
        foreach ($approverRoles as $approverRoleId) {
            foreach ($taskUserRoles as $taskUserRoleId) {
                if (RoleApproval::canApprove($approverRoleId, $taskUserRoleId, $approvalType)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * فحص ما إذا كانت التاسك مكتملة بالكامل (approved + جميع الاعتمادات)
     */
    public function isFullyCompleted(): bool
    {
        // للتاسكات العادية: فقط is_approved
        if (!$this->isProjectTask()) {
            return (bool) $this->is_approved;
        }

        // للتاسكات المرتبطة بمشاريع: is_approved + جميع الاعتمادات المطلوبة
        return (bool) $this->is_approved && $this->hasAllRequiredApprovals();
    }
}
