<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Casts\SafeEncryptedCast;
use App\Traits\HasSecureId;


class OverTimeRequests extends Model implements Auditable
{
    use AuditableTrait, HasSecureId, LogsActivity;

    protected $auditEvents = [
        'created',
        'updated',
        'deleted',
    ];

    protected $auditInclude = [
        'user_id',
        'overtime_date',
        'start_time',
        'end_time',
        'reason',
        'manager_status',
        'manager_rejection_reason',
        'hr_status',
        'hr_rejection_reason',
        'status'
    ];

    protected $fillable = [
        'user_id',
        'overtime_date',
        'start_time',
        'end_time',
        'reason',
        'manager_status',
        'manager_rejection_reason',
        'hr_status',
        'hr_rejection_reason',
        'status'
    ];

    protected $casts = [
        'overtime_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'reason' => SafeEncryptedCast::class,
        'manager_rejection_reason' => SafeEncryptedCast::class,
        'hr_rejection_reason' => SafeEncryptedCast::class,
    ];

    /**
     * Configure Activity Log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'user_id', 'overtime_date', 'start_time', 'end_time', 'reason',
                'manager_status', 'hr_status', 'status'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'تم إنشاء طلب عمل إضافي جديد',
                'updated' => 'تم تحديث طلب العمل الإضافي',
                'deleted' => 'تم حذف طلب العمل الإضافي',
                default => $eventName
            });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function canRespond(User $user): bool
    {
        if ($user->hasRole('hr') && $user->hasPermissionTo('hr_respond_overtime_request')) {
            return true;
        }

        if (
            $user->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager']) &&
            $user->hasPermissionTo('manager_respond_overtime_request')
        ) {
            if ($this->user && $this->user->teams()->exists()) {
                return DB::table('team_user')
                    ->where('user_id', $user->id)
                    ->where('team_id', $this->user->team_id)
                    ->where(function ($query) {
                        $query->where('role', 'admin')
                            ->orWhere('role', 'owner');
                    })
                    ->exists();
            }
        }

        return false;
    }

    public function canCreate(User $user): bool
    {
        return $user->hasPermissionTo('create_overtime');
    }

    public function canUpdate(User $user): bool
    {
        if (!$user->hasPermissionTo('update_overtime')) {
            return false;
        }
        return $user->id === $this->user_id && $this->status === 'pending';
    }

    public function canDelete(User $user): bool
    {
        if (!$user->hasPermissionTo('delete_overtime')) {
            return false;
        }
        return $user->id === $this->user_id && $this->status === 'pending';
    }

    public function canModifyResponse(User $user): bool
    {
        if ($user->hasRole('hr') && $user->hasPermissionTo('hr_respond_overtime_request')) {
            return true;
        }

        if (!$user->hasPermissionTo('manager_respond_overtime_request')) {
            return false;
        }

        if ($user->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager'])) {
            return DB::table('team_user')
                ->where('user_id', $user->id)
                ->where('team_id', $this->user->team_id)
                ->where(function ($query) {
                    $query->where('role', 'admin')
                        ->orWhere('role', 'owner');
                })
                ->exists();
        }

        return false;
    }

    public function getFormattedDuration(): string
    {
        $startTime = Carbon::parse($this->start_time);
        $endTime = Carbon::parse($this->end_time);
        $minutes = $startTime->diffInMinutes($endTime);
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        return sprintf('%d:%02d', $hours, $remainingMinutes);
    }

    public function getOvertimeHours(): float
    {
        $startTime = Carbon::parse($this->start_time);
        $endTime = Carbon::parse($this->end_time);
        return $startTime->diffInMinutes($endTime) / 60;
    }

    public function updateManagerStatus(string $status, ?string $rejectionReason = null): void
    {
        $this->manager_status = $status;
        $this->manager_rejection_reason = $rejectionReason;
        $this->updateFinalStatus();
        $this->save();
    }

    public function updateHrStatus(string $status, ?string $rejectionReason = null): void
    {
        $this->hr_status = $status;
        $this->hr_rejection_reason = $rejectionReason;
        $this->updateFinalStatus();
        $this->save();
    }

    public function updateFinalStatus(): void
    {
        if ($this->user && (!$this->user->teams()->exists() || $this->user->teams()->where('name', 'HR')->exists())) {
            if ($this->hr_status === 'rejected') {
                $this->status = 'rejected';
            } elseif ($this->hr_status === 'approved') {
                $this->status = 'approved';
            } else {
                $this->status = 'pending';
            }
        } else {
            if ($this->manager_status === 'rejected' || $this->hr_status === 'rejected') {
                $this->status = 'rejected';
            } elseif ($this->manager_status === 'approved' && $this->hr_status === 'approved') {
                $this->status = 'approved';
            } else {
                $this->status = 'pending';
            }
        }
    }

    public function getRejectionReason(): ?string
    {
        if ($this->status !== 'rejected') {
            return null;
        }

        return $this->manager_status === 'rejected'
            ? $this->manager_rejection_reason
            : $this->hr_rejection_reason;
    }

    /**
     * الحصول على تاريخ العمل الإضافي بتنسيق محسن
     *
     * @return string
     */
    public function getFormattedOvertimeDateAttribute(): string
    {
        return $this->overtime_date ? Carbon::parse($this->overtime_date)->format('Y-m-d') : '';
    }
}
