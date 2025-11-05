<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\HasSecureId;

class ProjectServiceUser extends Model
{
    use HasFactory, HasSecureId, LogsActivity;

    protected $table = 'project_service_user';

    protected $fillable = [
        'project_id',
        'service_id',
        'user_id',
        'team_id',
        'role_id',
        'project_share',
        'is_acknowledged',
        'acknowledged_at',
        'deadline',
        'delivered_at',
        'status',
        'administrative_approval',
        'administrative_approval_at',
        'administrative_approver_id',
        'technical_approval',
        'technical_approval_at',
        'technical_approver_id',
        'administrative_notes',
        'technical_notes',
    ];

    // حالات المشروع الممكنة للموظف
    const STATUS_IN_PROGRESS = 'جاري';
    const STATUS_WAITING_FORM = 'واقف ع النموذج';
    const STATUS_WAITING_QUESTIONS = 'واقف ع الأسئلة';
    const STATUS_WAITING_CLIENT = 'واقف ع العميل';
    const STATUS_WAITING_CALL = 'واقف ع مكالمة';
    const STATUS_PAUSED = 'موقوف';
    const STATUS_DRAFT_DELIVERY = 'تسليم مسودة';
    const STATUS_FINAL_DELIVERY = 'تم تسليم نهائي';

    /**
     * الحصول على جميع الحالات الممكنة
     */
    public static function getAvailableStatuses(): array
    {
        return [
            self::STATUS_IN_PROGRESS => 'جاري',
            self::STATUS_WAITING_FORM => 'واقف ع النموذج',
            self::STATUS_WAITING_QUESTIONS => 'واقف ع الأسئلة',
            self::STATUS_WAITING_CLIENT => 'واقف ع العميل',
            self::STATUS_WAITING_CALL => 'واقف ع مكالمة',
            self::STATUS_PAUSED => 'موقوف',
            self::STATUS_DRAFT_DELIVERY => 'تسليم مسودة',
            self::STATUS_FINAL_DELIVERY => 'تم تسليم نهائي',
        ];
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function service()
    {
        return $this->belongsTo(CompanyService::class, 'service_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * الدور المحدد للمشارك في الخدمة
     */
    public function role()
    {
        return $this->belongsTo(\Spatie\Permission\Models\Role::class);
    }

    /**
     * المعتمد الإداري
     */
    public function administrativeApprover()
    {
        return $this->belongsTo(User::class, 'administrative_approver_id');
    }

    /**
     * المعتمد الفني
     */
    public function technicalApprover()
    {
        return $this->belongsTo(User::class, 'technical_approver_id');
    }

    protected $casts = [
        'project_share' => 'decimal:2',
        'is_acknowledged' => 'boolean',
        'acknowledged_at' => 'datetime',
        'delivered_at' => 'datetime',
        'administrative_approval' => 'boolean',
        'administrative_approval_at' => 'datetime',
        'technical_approval' => 'boolean',
        'technical_approval_at' => 'datetime',
        'deadline' => 'datetime',
    ];

    /**
     * Configure Activity Log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'project_id',
                'service_id',
                'user_id',
                'team_id',
                'project_share',
                'is_acknowledged',
                'acknowledged_at',
                'deadline',
                'delivered_at',
                'status',
                'administrative_approval',
                'administrative_approval_at',
                'administrative_approver_id',
                'technical_approval',
                'technical_approval_at',
                'technical_approver_id',
                'administrative_notes',
                'technical_notes'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match ($eventName) {
                'created' => 'تم تعيين مستخدم لخدمة مشروع',
                'updated' => 'تم تحديث تعيين مستخدم المشروع',
                'deleted' => 'تم إلغاء تعيين مستخدم المشروع',
                default => $eventName
            });
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
            'administrative_approval_at' => now(),
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
            'technical_approval_at' => now(),
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
     * تحديد ما إذا كان المستخدم قد أكد استلام المشروع أم لا
     */
    public function isAcknowledged(): bool
    {
        return $this->is_acknowledged;
    }


    public function acknowledge(): bool
    {
        return $this->update([
            'is_acknowledged' => true,
            'acknowledged_at' => now(),
        ]);
    }


    public function unacknowledge(): bool
    {
        return $this->update([
            'is_acknowledged' => false,
            'acknowledged_at' => null,
        ]);
    }

    /**
     * تحديد ما إذا كان المستخدم قد سلم المشروع أم لا
     */
    public function isDelivered(): bool
    {
        return !is_null($this->delivered_at);
    }

    /**
     * فحص ما إذا كان يمكن إعادة تسليم المشروع
     */
    public function canBeRedelivered(): bool
    {
        // لا يمكن إعادة التسليم إذا تم اعتماد المشروع مسبقاً
        if ($this->hasAdministrativeApproval() || $this->hasTechnicalApproval()) {
            return false;
        }

        return true;
    }

    /**
     * فحص ما إذا كان يمكن إلغاء تسليم المشروع
     */
    public function canBeUndelivered(): bool
    {
        // لا يمكن إلغاء التسليم إذا تم اعتماد المشروع مسبقاً
        if ($this->hasAdministrativeApproval() || $this->hasTechnicalApproval()) {
            return false;
        }

        // يمكن إلغاء التسليم فقط إذا كان مسلم ومش معتمد
        return $this->isDelivered();
    }

    /**
     * تسليم المشروع
     */
    public function deliver(): bool
    {
        return $this->update([
            'delivered_at' => now(),
        ]);
    }

    /**
     * إلغاء تسليم المشروع
     */
    public function undeliver(): bool
    {
        return $this->update([
            'delivered_at' => null,
        ]);
    }

    /**
     * فحص ما إذا كان المستخدم مستلم ومسلم ومعتمد كاملاً
     */
    public function isFullyCompleted(): bool
    {
        return $this->isAcknowledged() && $this->hasAllRequiredApprovals();
    }

    /**
     * التحقق من انتهاء الموعد النهائي
     */
    public function isOverdue(): bool
    {
        if (!$this->deadline) {
            return false;
        }

        return now()->isAfter($this->deadline);
    }

    /**
     * التحقق من اقتراب الموعد النهائي (خلال عدد معين من الأيام)
     */
    public function isDueSoon(int $days = 3): bool
    {
        if (!$this->deadline) {
            return false;
        }

        return now()->diffInDays($this->deadline, false) <= $days && !$this->isOverdue();
    }

    /**
     * الحصول على الأيام المتبقية للموعد النهائي
     */
    public function getDaysRemaining(): ?int
    {
        if (!$this->deadline) {
            return null;
        }

        $today = now()->startOfDay();
        $deadlineDate = $this->deadline->startOfDay();


        $diffInDays = $today->diffInDays($deadlineDate, false);

        return (int) $diffInDays;
    }


    public function setDeadline($deadline): bool
    {
        return $this->update([
            'deadline' => $deadline,
        ]);
    }



    public function getRequiredApprovals(): array
    {
        if (!$this->user) {
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
        $user = User::find($userId);
        if (!$user) {
            return false;
        }

        if (!$this->user) {
            return false;
        }

        $userRoles = $user->roles;
        $deliveryUserRoles = $this->user->roles;

        foreach ($deliveryUserRoles as $deliveryRole) {
            foreach ($userRoles as $userRole) {
                $approvalRule = RoleApproval::where('approver_role_id', $userRole->id)
                    ->where('role_id', $deliveryRole->id)
                    ->where('approval_type', $approvalType)
                    ->where('is_active', true)
                    ->first();

                if ($approvalRule) {
                    if ($approvalRule->requires_same_project) {
                        $isUserInSameProject = ProjectServiceUser::where('project_id', $this->project_id)
                            ->where('user_id', $userId)
                            ->exists();

                        if (!$isUserInSameProject) {
                            continue; // المستخدم ليس في نفس المشروع، لا يمكنه الاعتماد
                        }
                    }

                    if ($approvalRule->requires_team_owner) {
                        $deliveryUserTeamId = $this->user->current_team_id;

                        if ($deliveryUserTeamId) {
                            $isTeamOwner = \App\Models\Team::where('id', $deliveryUserTeamId)
                                ->where('user_id', $userId)
                                ->exists();

                            if (!$isTeamOwner) {
                                continue; // المستخدم ليس مالك الفريق، لا يمكنه الاعتماد
                            }
                        } else {
                            continue; // الشخص المطلوب اعتماده ليس في فريق، لا يمكن الاعتماد
                        }
                    }

                    return true; // المستخدم يمكنه الاعتماد
                }
            }
        }

        return false;
    }

    /**
     * الحصول على حالة الاعتماد الشاملة
     */
    public function getApprovalStatus(): array
    {
        $required = $this->getRequiredApprovals();

        return [
            'administrative' => [
                'required' => $required['needs_administrative'],
                'approved' => $this->hasAdministrativeApproval(),
                'approved_at' => $this->administrative_approval_at,
                'approver' => $this->administrativeApprover,
                'notes' => $this->administrative_notes,
            ],
            'technical' => [
                'required' => $required['needs_technical'],
                'approved' => $this->hasTechnicalApproval(),
                'approved_at' => $this->technical_approval_at,
                'approver' => $this->technicalApprover,
                'notes' => $this->technical_notes,
            ],
            'fully_approved' => $this->hasAllRequiredApprovals(),
        ];
    }

    /**
     * الحصول على نسبة المشاركة كنص
     */
    public function getProjectShareLabel(): string
    {
        if ($this->project_share == 0.00) {
            return 'بدون محاسبة (وصول فقط)';
        } elseif ($this->project_share == 1.00) {
            return 'مشروع كامل';
        } elseif ($this->project_share == 0.50) {
            return 'نص مشروع';
        } elseif ($this->project_share == 0.25) {
            return 'ربع مشروع';
        } elseif ($this->project_share == 0.75) {
            return 'ثلاثة أرباع مشروع';
        } else {
            return ($this->project_share * 100) . '%';
        }
    }

    /**
     * الحصول على قيمة المشاركة كنسبة مئوية
     */
    public function getProjectSharePercentage(): float
    {
        return $this->project_share * 100;
    }

    /**
     * أخطاء الموظف في هذا المشروع
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
     * المهام المرتبطة بهذا المستخدم في هذه الخدمة
     */
    public function tasks()
    {
        return $this->hasMany(TaskUser::class, 'user_id', 'user_id')
            ->whereHas('task', function ($query) {
                $query->where('project_id', $this->project_id)
                    ->where('service_id', $this->service_id);
            });
    }

    /**
     * الحصول على عدد المهام المرتبطة
     */
    public function getTasksCountAttribute()
    {
        return $this->tasks()->count();
    }

    /**
     * الحصول على المهام المكتملة
     */
    public function getCompletedTasksCountAttribute()
    {
        return $this->tasks()->where('status', 'completed')->count();
    }

    /**
     * الحصول على المهام المتأخرة
     */
    public function getOverdueTasksCountAttribute()
    {
        return $this->tasks()->whereHas('task', function ($query) {
            $query->where('due_date', '<', now())
                ->where('status', '!=', 'completed');
        })->count();
    }

    /**
     * التحقق من وجود أخطاء جوهرية
     */
    public function hasCriticalErrors(): bool
    {
        return $this->errors()->where('error_type', 'critical')->exists();
    }

    /**
     * تحديث حالة المشروع
     */
    public function updateStatus(string $status): bool
    {
        return $this->update(['status' => $status]);
    }

    /**
     * الحصول على لون الحالة للعرض
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            self::STATUS_IN_PROGRESS => 'success',
            self::STATUS_WAITING_FORM => 'warning',
            self::STATUS_WAITING_QUESTIONS => 'warning',
            self::STATUS_WAITING_CLIENT => 'warning',
            self::STATUS_WAITING_CALL => 'info',
            self::STATUS_PAUSED => 'secondary',
            self::STATUS_DRAFT_DELIVERY => 'primary',
            self::STATUS_FINAL_DELIVERY => 'success',
            default => 'secondary'
        };
    }

    /**
     * Scope للمشاريع حسب الحالة
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope للمشاريع التي سيتم تسليمها هذا الشهر
     */
    public function scopeDeadlineThisMonth($query)
    {
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        return $query->whereBetween('deadline', [$startOfMonth, $endOfMonth]);
    }

    /**
     * Scope للمشاريع التي سيتم تسليمها هذا الأسبوع
     */
    public function scopeDeadlineThisWeek($query)
    {
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();

        return $query->whereBetween('deadline', [$startOfWeek, $endOfWeek]);
    }

    /**
     * Scope للمشاريع التي سيتم تسليمها اليوم
     */
    public function scopeDeadlineToday($query)
    {
        return $query->whereDate('deadline', today());
    }

    /**
     * Scope للمشاريع المتأخرة
     */
    public function scopeOverdue($query)
    {
        return $query->where('deadline', '<', now())
            ->whereNotIn('status', [self::STATUS_FINAL_DELIVERY]);
    }

    /**
     * Scope للمشاريع القادمة (في المستقبل)
     */
    public function scopeUpcoming($query)
    {
        return $query->where('deadline', '>', now());
    }

    /**
     * Scope لفلترة المشاريع حسب المستخدم
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope لفلترة المشاريع حسب الفريق
     */
    public function scopeForTeam($query, $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    /**
     * Scope لفلترة المشاريع حسب المشروع
     */
    public function scopeForProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }
}
