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


class PermissionRequest extends Model implements Auditable
{
    use AuditableTrait, HasSecureId, LogsActivity;

    protected $auditEvents = [
        'created',
        'updated',
        'deleted',
    ];

    protected $auditInclude = [
        'user_id',
        'departure_time',
        'return_time',
        'returned_on_time',
        'minutes_used',
        'remaining_minutes',
        'reason',
        'manager_status',
        'manager_rejection_reason',
        'hr_status',
        'hr_rejection_reason',
        'status'
    ];

    protected $fillable = [
        'user_id',
        'departure_time',
        'return_time',
        'returned_on_time',
        'minutes_used',
        'remaining_minutes',
        'reason',
        'manager_status',
        'manager_rejection_reason',
        'hr_status',
        'hr_rejection_reason',
        'status'
    ];

    protected $casts = [
        'departure_time' => 'datetime',
        'return_time' => 'datetime',
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
                'user_id', 'departure_time', 'return_time', 'reason', 'status',
                'manager_status', 'hr_status', 'returned_on_time', 'minutes_used', 'remaining_minutes'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'تم إنشاء طلب إذن جديد',
                'updated' => 'تم تحديث طلب الإذن',
                'deleted' => 'تم حذف طلب الإذن',
                default => $eventName
            });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function userWorkShift()
    {
        return $this->user->workShift;
    }

    public function getShiftEndTime()
    {
        $workShift = $this->userWorkShift();

        if ($workShift) {
            return \Carbon\Carbon::parse($workShift->check_out_time);
        }

        return \Carbon\Carbon::now()->setTimezone('Africa/Cairo')->setTime(16, 0, 0);
    }

    public function violations()
    {
        return $this->hasMany(Violation::class, 'permission_requests_id');
    }

    public function canRespond(User $user): bool
    {
        if ($user->hasRole('hr') && $user->hasPermissionTo('hr_respond_permission_request')) {
            return true;
        }

        if ($user->hasPermissionTo('manager_respond_permission_request')) {
            if ($this->user && $this->user->teams()->exists()) {
                return DB::table('team_user')
                    ->where('user_id', $user->id)
                    ->where('team_id', $this->user->currentTeam->id)
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
        return $user->hasPermissionTo('create_permission');
    }

    public function canUpdate(User $user): bool
    {
        if (!$user->hasPermissionTo('update_permission')) {
            return false;
        }
        return $user->id === $this->user_id && $this->status === 'pending';
    }

    public function canDelete(User $user): bool
    {
        if (!$user->hasPermissionTo('delete_permission')) {
            return false;
        }
        return $user->id === $this->user_id && $this->status === 'pending';
    }

    public function canModifyResponse(User $user): bool
    {
        if ($user->hasRole('hr') && $user->hasPermissionTo('hr_respond_permission_request')) {
            return true;
        }

        if (!$user->hasPermissionTo('manager_respond_permission_request')) {
            return false;
        }

        if ($user->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager'])) {
            return DB::table('team_user')
                ->where('user_id', $user->id)
                ->where('team_id', $this->user->currentTeam->id)
                ->where(function ($query) {
                    $query->where('role', 'admin')
                        ->orWhere('role', 'owner');
                })
                ->exists();
        }

        return false;
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

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function getReturnStatusLabel(): string
    {
        if ($this->returned_on_time === 2 || $this->returned_on_time == 2) {
            return 'لم يعد في الوقت المحدد';
        } elseif ($this->returned_on_time === 1 || $this->returned_on_time === true) {
            return 'عاد في الوقت المحدد';
        } elseif ($this->returned_on_time === 0 || $this->returned_on_time === null) {
            return 'غير محدد';
        } else {
            return 'غير محدد';
        }
    }

    public function calculateMinutesUsed()
    {
        return $this->departure_time->diffInMinutes($this->return_time);
    }

    public function calculateRemainingMinutes()
    {
        $totalAllowed = 180;
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $usedMinutes = self::where('user_id', $this->user_id)
            ->whereBetween('departure_time', [$startOfMonth, $endOfMonth])
            ->where('status', 'approved')
            ->sum('minutes_used');

        return $totalAllowed - $usedMinutes;
    }

    public function getFormattedDuration(): string
    {
        $minutes = $this->minutes_used;
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($hours > 0) {
            return sprintf('%d ساعة %d دقيقة', $hours, $remainingMinutes);
        }

        return sprintf('%d دقيقة', $minutes);
    }

    public function calculateActualMinutesUsed(): int
    {
        $now = Carbon::now()->setTimezone('Africa/Cairo');
        $departure = $this->departure_time;
        $scheduledReturn = $this->return_time;

        $shiftEndTime = $this->getShiftEndTime()->setDateFrom($departure);

        if ($scheduledReturn->gte($shiftEndTime)) {
            $scheduledReturn = $shiftEndTime;
        }

        $minutesUsed = 0;

        if ($this->returned_on_time === true || $this->returned_on_time === 1) {
            if ($now->lte($shiftEndTime)) {
                $minutesUsed = abs($departure->diffInMinutes($now));
            } else {
                $minutesUsed = abs($departure->diffInMinutes($shiftEndTime));
            }
        } else if ($this->returned_on_time === 2) {
            $minutesUsed = abs($departure->diffInMinutes($shiftEndTime));
        } else if ($this->returned_on_time === null || $this->returned_on_time === 0) {
            if ($now->gt($scheduledReturn)) {
                if ($now->gt($shiftEndTime)) {
                    $minutesUsed = abs($departure->diffInMinutes($shiftEndTime));
                } else {
                    $minutesUsed = abs($departure->diffInMinutes($now));
                }
            } else {
                $minutesUsed = abs($departure->diffInMinutes($scheduledReturn));
            }
        } else {
            $minutesUsed = abs($departure->diffInMinutes($scheduledReturn));
        }

        $maxPossibleMinutes = abs($departure->diffInMinutes($shiftEndTime));
        return min($minutesUsed, $maxPossibleMinutes);
    }

    public function updateActualMinutesUsed(): void
    {
        $oldMinutes = $this->minutes_used;
        $newMinutes = $this->calculateActualMinutesUsed();

        $this->minutes_used = $newMinutes;
        $this->save();
    }

    public function canMarkAsReturned(User $user): bool
    {
        if ($user->id !== $this->user_id) {
            return false;
        }

        if ($this->status !== 'approved') {
            return false;
        }

        if ($this->returned_on_time === true || $this->returned_on_time === 1 || $this->returned_on_time === 2) {
            return false;
        }

        $now = \Carbon\Carbon::now()->setTimezone('Africa/Cairo');
        $departureTime = \Carbon\Carbon::parse($this->departure_time);

        return $now->gte($departureTime);
    }

    public function canResetReturnStatus(User $user): bool
    {
        if ($user->id !== $this->user_id) {
            return false;
        }

        if ($this->status !== 'approved') {
            return false;
        }

        if ($this->returned_on_time !== true && $this->returned_on_time !== 1 && $this->returned_on_time !== 2) {
            return false;
        }

        $now = \Carbon\Carbon::now()->setTimezone('Africa/Cairo');
        $departureTime = \Carbon\Carbon::parse($this->departure_time);

        if (!$now->isSameDay($departureTime)) {
            return false;
        }

        return true;
    }

    public function isReturnTimePassed(): bool
    {
        $now = \Carbon\Carbon::now()->setTimezone('Africa/Cairo');
        $returnTime = \Carbon\Carbon::parse($this->return_time);

        $maxReturnTime = min($returnTime, $this->getShiftEndTime());

        if ($returnTime->format('H:i') === $this->getShiftEndTime()->format('H:i')) {
            if ($this->returned_on_time === false) {
                $this->returned_on_time = true;
                $this->save();
            }

            return true;
        }

        return $now->gt($maxReturnTime);
    }

    public function shouldShowCountdown(): bool
    {
        if ($this->returned_on_time === true || $this->returned_on_time === 1 || $this->returned_on_time === 2) {
            return false;
        }

        if (!$this->return_time) {
            return false;
        }

        if (!$this->isApproved()) {
            return false;
        }

        $now = \Carbon\Carbon::now()->setTimezone('Africa/Cairo');
        $departureTime = \Carbon\Carbon::parse($this->departure_time);
        $returnTime = \Carbon\Carbon::parse($this->return_time);

        if ($now->lt($departureTime)) {
            return false;
        }

        if (($this->returned_on_time === 0 || $this->returned_on_time === null) && $this->isApproved()) {
            if (request()->is('*check-end-of-day*') || request()->isMethod('post')) {
                $shiftEndTime = $this->getShiftEndTime()->setDateFrom($departureTime);
                $maxReturnTime = $returnTime->gt($shiftEndTime) ? $shiftEndTime : $returnTime;

                return $now->gte($departureTime);
            }

            return $now->gte($departureTime);
        }

        if ($this->isApproved()) {
            return $now->gte($departureTime);
        }

        return false;
    }

    public function canView(User $user): bool
    {
        if ($user->id === $this->user_id) {
            return true;
        }

        return $user->hasPermissionTo('view_permission');
    }

    public function canRespondAsManager(User $user): bool
    {
        return $user->hasPermissionTo('manager_respond_permission_request') &&
            $user->id !== $this->user_id;
    }

    public function canRespondAsHR(User $user): bool
    {
        return $user->hasPermissionTo('hr_respond_permission_request') &&
            $user->id !== $this->user_id;
    }

    public function canResetManagerResponse(User $user): bool
    {
        return $user->hasPermissionTo('manager_respond_permission_request') &&
            $this->manager_status !== 'pending';
    }

    public function canResetHRResponse(User $user): bool
    {
        return $user->hasPermissionTo('hr_respond_permission_request') &&
            $this->hr_status !== 'pending';
    }
}
