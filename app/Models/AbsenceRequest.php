<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Casts\SafeEncryptedCast;
use App\Traits\HasSecureId;


class AbsenceRequest extends Model implements Auditable
{
    use AuditableTrait, HasSecureId, LogsActivity;

    protected $auditEvents = [
        'created',
        'updated',
        'deleted',
    ];

    protected $auditInclude = [
        'user_id',
        'absence_date',
        'reason',
        'status',
        'manager_status',
        'hr_status',
        'manager_rejection_reason',
        'hr_rejection_reason'
    ];

    protected $fillable = [
        'user_id',
        'absence_date',
        'reason',
        'status',
        'manager_status',
        'hr_status',
        'manager_rejection_reason',
        'hr_rejection_reason'
    ];

    protected $attributes = [
        'status' => 'pending',
        'manager_status' => 'pending',
        'hr_status' => 'pending',
        'manager_rejection_reason' => null,
        'hr_rejection_reason' => null
    ];

    protected $dates = [
        'absence_date'
    ];

    protected $casts = [
        'absence_date' => 'datetime',
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
                'user_id', 'absence_date', 'reason', 'status',
                'manager_status', 'hr_status', 'manager_rejection_reason', 'hr_rejection_reason'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'تم إنشاء طلب غياب جديد',
                'updated' => 'تم تحديث طلب الغياب',
                'deleted' => 'تم حذف طلب الغياب',
                default => $eventName
            });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function canRespond(User $user): bool
    {
        if ($user->hasRole('hr') && $user->hasPermissionTo('hr_respond_absence_request')) {
            return true;
        }

        if (
            $user->hasAnyRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager'])
            && $user->hasPermissionTo('manager_respond_absence_request')
        ) {
            return true;
        }

        // حالة خاصة: مستخدم HR لديه صلاحية الرد كمدير ويملك فريقًا يكون صاحب الطلب عضوًا فيه
        if ($user->hasRole('hr') && $user->hasPermissionTo('manager_respond_absence_request')) {
            foreach ($user->ownedTeams as $team) {
                if ($team->users->contains('id', $this->user_id)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function canCreate(User $user): bool
    {
        return $user->hasPermissionTo('create_absence');
    }

    public function canUpdate(User $user): bool
    {
        if (!$user->hasPermissionTo('update_absence')) {
            return false;
        }

        // Allow HR to update their own requests if HR status is approved
        if ($user->id === $this->user_id && $user->hasRole('hr') && $this->hr_status === 'approved') {
            return true;
        }

        // Regular case - user can update their pending requests
        return $user->id === $this->user_id && $this->status === 'pending';
    }

    public function canDelete(User $user): bool
    {
        if (!$user->hasPermissionTo('delete_absence')) {
            return false;
        }

        // Allow HR to delete their own requests if HR status is approved
        if ($user->id === $this->user_id && $user->hasRole('hr') && $this->hr_status === 'approved') {
            return true;
        }

        // Regular case - user can delete their pending requests
        return $user->id === $this->user_id && $this->status === 'pending';
    }

    public function canModifyResponse(User $user): bool
    {
        try {
            if ($user->hasRole('hr') && $user->hasPermissionTo('hr_respond_absence_request')) {
                return true;
            }

            if (
                $user->hasAnyRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager'])
                && $user->hasPermissionTo('manager_respond_absence_request')
            ) {
                if ($this->user_id === $user->id) {
                    return true;
                }

                if ($this->user) {
                    foreach ($user->ownedTeams as $team) {
                        $isTeamMember = DB::table('team_user')
                            ->where('user_id', operator: $this->user_id)
                            ->where('team_id', $team->id)
                            ->exists();

                        if ($isTeamMember) {
                            return true;
                        }
                    }

                    $managedTeams = DB::table('team_user')
                        ->where('user_id', $user->id)
                        ->whereIn('role', ['owner', 'admin'])
                        ->pluck('team_id');

                    if ($managedTeams->isNotEmpty()) {
                        $isInManagedTeam = DB::table('team_user')
                            ->where('user_id', $this->user_id)
                            ->whereIn('team_id', $managedTeams)
                            ->exists();

                        if ($isInManagedTeam) {
                            return true;
                        }
                    }
                }
            }

            // حالة خاصة: مستخدم HR لديه صلاحية الرد كمدير ويملك فريقًا يكون صاحب الطلب عضوًا فيه
            if ($user->hasRole('hr') && $user->hasPermissionTo('manager_respond_absence_request')) {
                if ($this->user) {
                    foreach ($user->ownedTeams as $team) {
                        $isTeamMember = DB::table('team_user')
                            ->where('user_id', $this->user_id)
                            ->where('team_id', $team->id)
                            ->exists();

                        if ($isTeamMember) {
                            return true;
                        }
                    }
                }
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
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
        // إذا كان أي من الحالتين مرفوض، الحالة النهائية مرفوضة
        if ($this->manager_status === 'rejected' || $this->hr_status === 'rejected') {
            $this->status = 'rejected';
            return;
        }

        // تحقق ما إذا كان المستخدم لديه فريق
        $hasTeam = DB::table('team_user')->where('user_id', $this->user_id)->exists();

        // تحقق ما إذا كان المستخدم من HR
        $isHrUser = DB::table('model_has_roles')
            ->where('model_id', $this->user_id)
            ->whereIn('role_id', function ($query) {
                $query->select('id')
                    ->from('roles')
                    ->where('name', 'hr');
            })
            ->exists();

        if (!$hasTeam && !$isHrUser) {
            // الموظفون العاديون بدون فريق يحتاجون فقط موافقة HR
            if ($this->hr_status === 'approved') {
                $this->status = 'approved';
                return;
            }
        } else {
            // الموظفون في فرق أو موظفو HR يحتاجون موافقة المدير و HR معًا
            if ($this->manager_status === 'approved' && $this->hr_status === 'approved') {
                $this->status = 'approved';
                return;
            }
        }

        $this->status = 'pending';
    }

    // دالة جديدة للتحقق إذا كان المستخدم لديه فريق
    public function userHasTeam(): bool
    {
        return DB::table('team_user')->where('user_id', $this->user_id)->exists();
    }

    public function hasExceededLimit(): bool
    {
        $maxDays = $this->user->getMaxAllowedAbsenceDays();
        $startOfYear = Carbon::now()->startOfYear();
        $endOfYear = Carbon::now()->endOfYear();

        $approvedDays = static::where('user_id', $this->user_id)
            ->where('status', 'approved')
            ->whereBetween('absence_date', [$startOfYear, $endOfYear])
            ->count();

        return $approvedDays > $maxDays;
    }

    public function getRemainingDays(): int
    {
        $maxDays = $this->user->getMaxAllowedAbsenceDays();
        $startOfYear = Carbon::now()->startOfYear();
        $endOfYear = Carbon::now()->endOfYear();

        $approvedDays = static::where('user_id', $this->user_id)
            ->where('status', 'approved')
            ->whereBetween('absence_date', [$startOfYear, $endOfYear])
            ->count();

        return max(0, $maxDays - $approvedDays);
    }
}
