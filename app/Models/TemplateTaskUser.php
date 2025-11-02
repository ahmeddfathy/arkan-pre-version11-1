<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use App\Traits\HasSecureId;
use App\Traits\HasNTPTime;

class TemplateTaskUser extends Model implements Auditable
{
    use HasFactory, AuditableTrait, HasSecureId, HasNTPTime;

    protected $auditEvents = [
        'created',
        'updated',
        'deleted',
    ];

    protected $auditInclude = [
        'template_task_id',
        'user_id',
        'season_id',
        'project_id',
        'original_user_id',
        'assigned_by',
        'assigned_at',
        'deadline',
        'status',
        'started_at',
        'paused_at',
        'completed_at',
        'actual_minutes',
        'actual_hours',
        'estimated_hours',
        'estimated_minutes',
        'is_approved',
        'awarded_points',
        'approval_note',
        'approved_by',
        'approved_at',
        'is_transferred',
        'transferred_from_at',
        'transfer_reason',
        'transfer_type',

    ];

    protected $table = 'template_task_user';

    protected $fillable = [
        'template_task_id',
        'user_id',
        'season_id',
        'project_id',
        'original_user_id',
        'assigned_by',
        'assigned_at',
        'deadline',
        'status',
        'started_at',
        'paused_at',
        'completed_at',
        'actual_minutes',
        'actual_hours',
        'estimated_hours',
        'estimated_minutes',
        'items', // بنود المهمة مع حالاتها (JSON)
        'start_date',
        'due_date',
        'is_approved',
        'awarded_points',
        'approval_note',
        'approved_by',
        'approved_at',
        'is_transferred',
        'transferred_from_at',
        'transfer_reason',
        'transfer_type',
        'transferred_to_user_id',
        'transferred_record_id',
        'transferred_at',
        'transfer_points',
        'original_template_task_user_id',
        'is_additional_task',
        'task_source',
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
        'assigned_at' => 'datetime',
        'deadline' => 'datetime',
        'started_at' => 'datetime',
        'paused_at' => 'datetime',
        'completed_at' => 'datetime',
        'actual_minutes' => 'integer',
        'actual_hours' => 'integer',
        'estimated_hours' => 'integer',
        'estimated_minutes' => 'integer',
        'start_date' => 'datetime',
        'due_date' => 'datetime',
        'transfer_points' => 'integer',
        'transferred_at' => 'datetime',
        'is_additional_task' => 'boolean',
        'task_source' => 'string',
        'is_approved' => 'boolean',
        'awarded_points' => 'integer',
        'approved_at' => 'datetime',
        'is_transferred' => 'boolean',
        // casts للاعتماد الإداري والفني
        'administrative_approval' => 'boolean',
        'administrative_approval_at' => 'datetime',
        'technical_approval' => 'boolean',
        'technical_approval_at' => 'datetime',
    ];

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function templateTask()
    {
        return $this->belongsTo(TemplateTask::class, 'template_task_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }


    public function originalUser()
    {
        return $this->belongsTo(User::class, 'original_user_id');
    }

    public function originalTemplateTaskUser()
    {
        return $this->belongsTo(TemplateTaskUser::class, 'original_template_task_user_id');
    }

    public function transferredRecord()
    {
        return $this->belongsTo(TemplateTaskUser::class, 'transferred_record_id');
    }

    public function transferredToUser()
    {
        return $this->belongsTo(User::class, 'transferred_to_user_id');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }


    public function transferToUser(User $toUser, int $transferPoints, string $reason = null): array
    {
        $transferService = app(\App\Services\Tasks\TaskTransferService::class);
        return $transferService->transferTemplateTask($this, $toUser, $transferPoints, $reason);
    }


    public function canBeTransferred(User $toUser, int $transferPoints): array
    {
        $transferService = app(\App\Services\Tasks\TaskTransferService::class);
        return $transferService->canTransferTask($this, $toUser, $transferPoints);
    }


    public function getTransferHistory(): array
    {
        return DB::table('task_transfers')
            ->where('template_task_user_id', $this->id)
            ->orderBy('transferred_at', 'desc')
            ->get()
            ->toArray();
    }


    public function isTransferred(): bool
    {
        return $this->is_transferred === true;
    }

    public function isAdditionalTask(): bool
    {
        return $this->is_additional_task === true && $this->task_source === 'transferred';
    }

    public function isTransferredFromAnother(): bool
    {
        return !is_null($this->original_template_task_user_id);
    }


    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }


    public function isApproved(): bool
    {
        return $this->is_approved && $this->approved_at;
    }


    public function getEarnedPointsAttribute(): int
    {
        if ($this->is_approved && $this->awarded_points !== null) {
            return $this->awarded_points;
        }

        return $this->templateTask ? ($this->templateTask->points ?? 0) : 0;
    }


    public function canBeApproved(): bool
    {
        return $this->status === 'completed' && !$this->is_approved;
    }


    public function getOriginalUserNameAttribute(): ?string
    {
        return $this->originalUser?->name;
    }


    public function getTransferInfoAttribute(): ?array
    {
        if (!$this->isTransferredFromAnother()) {
            return null;
        }

        return [
            'original_user_name' => $this->original_user_name,
            'transferred_at' => $this->transferred_from_at,
            'reason' => $this->transfer_reason,
            'days_ago' => $this->transferred_from_at?->diffForHumans()
        ];
    }


    public function scopeTransferredFromOthers($query)
    {
        return $query->where('is_transferred', true)
            ->whereNotNull('original_user_id');
    }


    public function scopeOriginalTasks($query)
    {
        return $query->where('is_transferred', false)
            ->orWhereNull('is_transferred');
    }


    public function notes()
    {
        return $this->hasMany(TaskNote::class, 'template_task_user_id')
            ->where('task_type', 'template')
            ->orderBy('created_at', 'desc');
    }


    public function getNotesCountAttribute()
    {
        return $this->notes()->count();
    }


    public function timeLogs()
    {
        return $this->hasMany(TaskTimeLog::class, 'template_task_user_id')
            ->orderBy('started_at', 'desc');
    }


    public function activeTimeLog()
    {
        return $this->hasOne(TaskTimeLog::class, 'template_task_user_id')
            ->whereNull('stopped_at')
            ->latest('started_at');
    }


    public function hasActiveTimeLog(): bool
    {
        return $this->activeTimeLog()->exists();
    }


    public function startTimeLog(): TaskTimeLog
    {
        $now = $this->getCurrentCairoTime();

        TaskTimeLog::where('user_id', $this->user_id)
            ->whereNull('stopped_at')
            ->update(['stopped_at' => $now]);

        return TaskTimeLog::create([
            'template_task_user_id' => $this->id,
            'user_id' => $this->user_id,
            'task_type' => 'template',
            'started_at' => $now,
            'work_date' => $now->toDateString(),
            'season_id' => $this->season_id,
        ]);
    }
    
    public function stopActiveTimeLog(): ?TaskTimeLog
    {
        $activeLog = $this->activeTimeLog()->first();

        if ($activeLog) {
            $activeLog->stop();


            return $activeLog;
        }

        return null;
    }

    public function getTodayLoggedMinutes(): int
    {
        $today = $this->getCurrentCairoTime()->toDateString();

        return $this->timeLogs()
            ->where('work_date', $today)
            ->whereNotNull('stopped_at')
            ->sum('duration_minutes') ?? 0;
    }

    public function revisions()
    {
        return $this->hasMany(TaskRevision::class, 'template_task_user_id');
    }

    public function getRevisionsCountAttribute()
    {
        return $this->revisions()->count();
    }

    public function getPendingRevisionsCountAttribute()
    {
        return $this->revisions()->where('status', 'pending')->count();
    }

    public function getApprovedRevisionsCountAttribute()
    {
        return $this->revisions()->where('status', 'approved')->count();
    }

    public function getRejectedRevisionsCountAttribute()
    {
        return $this->revisions()->where('status', 'rejected')->count();
    }

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

    public function errors()
    {
        return $this->morphMany(EmployeeError::class, 'errorable');
    }

    public function getErrorsCountAttribute()
    {
        return $this->errors()->count();
    }

    public function getCriticalErrorsCountAttribute()
    {
        return $this->errors()->where('error_type', 'critical')->count();
    }

    public function getNormalErrorsCountAttribute()
    {
        return $this->errors()->where('error_type', 'normal')->count();
    }

    public function hasErrors(): bool
    {
        return $this->errors()->exists();
    }

    public function hasCriticalErrors(): bool
    {
        return $this->errors()->where('error_type', 'critical')->exists();
    }

    public function administrativeApprover()
    {
        return $this->belongsTo(User::class, 'administrative_approver_id');
    }

    public function technicalApprover()
    {
        return $this->belongsTo(User::class, 'technical_approver_id');
    }

    public function isProjectTask(): bool
    {
        return !is_null($this->project_id);
    }

    public function hasAdministrativeApproval(): bool
    {
        return (bool) $this->administrative_approval;
    }

    public function hasTechnicalApproval(): bool
    {
        return (bool) $this->technical_approval;
    }

    public function hasAllRequiredApprovals(): bool
    {
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

    public function grantAdministrativeApproval($approverId, $notes = null): bool
    {
        return $this->update([
            'administrative_approval' => true,
            'administrative_approval_at' => $this->getCurrentCairoTime(),
            'administrative_approver_id' => $approverId,
            'administrative_notes' => $notes,
        ]);
    }

    public function grantTechnicalApproval($approverId, $notes = null): bool
    {
        return $this->update([
            'technical_approval' => true,
            'technical_approval_at' => $this->getCurrentCairoTime(),
            'technical_approver_id' => $approverId,
            'technical_notes' => $notes,
        ]);
    }

    public function revokeAdministrativeApproval(): bool
    {
        return $this->update([
            'administrative_approval' => false,
            'administrative_approval_at' => null,
            'administrative_approver_id' => null,
            'administrative_notes' => null,
        ]);
    }

    public function revokeTechnicalApproval(): bool
    {
        return $this->update([
            'technical_approval' => false,
            'technical_approval_at' => null,
            'technical_approver_id' => null,
            'technical_notes' => null,
        ]);
    }

    public function canChangeStatus(): bool
    {
        if ($this->hasAdministrativeApproval() || $this->hasTechnicalApproval()) {
            return false;
        }

        return true;
    }

    public function canBeDragged(): bool
    {
        if ($this->hasAdministrativeApproval() || $this->hasTechnicalApproval()) {
            return false;
        }

        return ($this->user_id === auth()->user()?->id) && !($this->is_transferred ?? false);
    }


    public function getRequiredApprovals(): array
    {
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
            $adminApprovers = RoleApproval::getApproversForRole($role->id, 'administrative');
            if ($adminApprovers->isNotEmpty()) {
                $needsAdministrative = true;
            }

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

    public function canUserApprove($userId, $approvalType): bool
    {
        if (!$this->isProjectTask()) {
            return false;
        }

        $approverUser = User::find($userId);
        if (!$approverUser) {
            return false;
        }

        $approverRoles = $approverUser->roles->pluck('id')->toArray();

        $taskUserRoles = $this->user->roles->pluck('id')->toArray();

        foreach ($approverRoles as $approverRoleId) {
            foreach ($taskUserRoles as $taskUserRoleId) {
                if (RoleApproval::canApprove($approverRoleId, $taskUserRoleId, $approvalType)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function isFullyCompleted(): bool
    {
        if (!$this->isProjectTask()) {
            return (bool) $this->is_approved;
        }

        return (bool) $this->is_approved && $this->hasAllRequiredApprovals();
    }

    public function canUpdateStatus(): bool
    {
        if (!$this->project_id) {
            return true;
        }

        $project = $this->project;
        if (!$project) {
            return true;
        }

        if ($project->status === 'ملغي') {
            return false;
        }

        return true;
    }

    public function getStatusUpdateErrorMessage(): string
    {
        if (!$this->project_id) {
            return '';
        }

        $project = $this->project;
        if ($project && $project->status === 'ملغي') {
            return 'لا يمكن تحديث حالة المهمة لأن المشروع تم إلغاؤه';
        }

        return '';
    }
}
