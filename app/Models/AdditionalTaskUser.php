<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\HasSecureId;

class AdditionalTaskUser extends Model
{
    use HasFactory, HasSecureId, LogsActivity;

    protected $fillable = [
        'additional_task_id',
        'user_id',
        'status',
        'applied_at',
        'approved_at',
        'points_earned',
        'user_notes',
        'admin_notes',
        'completion_data',
    ];

    protected $casts = [
        'applied_at' => 'datetime',
        'approved_at' => 'datetime',
        'points_earned' => 'integer',
        'completion_data' => 'array',
    ];

    /**
     * Configure Activity Log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'additional_task_id', 'user_id', 'status', 'points_earned',
                'applied_at', 'approved_at'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'تم تسجيل مستخدم في مهمة إضافية',
                'updated' => 'تم تحديث حالة المستخدم في المهمة الإضافية',
                'deleted' => 'تم إلغاء تسجيل المستخدم من المهمة الإضافية',
                default => $eventName
            });
    }

    // Relations
    public function additionalTask()
    {
        return $this->belongsTo(AdditionalTask::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

        // Helper Methods
    public function canApply()
    {
        return $this->additionalTask->requiresApplication() &&
               $this->additionalTask->status === 'active' &&
               !$this->additionalTask->isExpired() &&
               $this->additionalTask->canAcceptMoreParticipants() &&
               !$this->exists; // المستخدم لم يتقدم من قبل
    }

    public function canApprove()
    {
        return $this->status === 'applied' &&
               $this->additionalTask->canAcceptMoreParticipants();
    }

    public function canReject()
    {
        return $this->status === 'applied';
    }


    public function applyForTask($userNotes = null)
    {
        if (!$this->canApply()) {
            return false;
        }

        $this->update([
            'status' => 'applied',
            'applied_at' => Carbon::now(),
            'user_notes' => $userNotes,
        ]);

        return true;
    }

    public function approveApplication($adminNotes = null)
    {
        if (!$this->canApprove()) {
            return false;
        }

        $this->update([
            'status' => 'assigned',
            'approved_at' => Carbon::now(),
            'admin_notes' => $adminNotes,
        ]);

        return true;
    }

    public function rejectApplication($adminNotes = null)
    {
        if (!$this->canReject()) {
            return false;
        }

        $this->update([
            'status' => 'rejected',
            'admin_notes' => $adminNotes,
        ]);

        return true;
    }


    // Scopes
    public function scopeApplied($query)
    {
        return $query->where('status', 'applied');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeAssigned($query)
    {
        return $query->where('status', 'assigned');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeForUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    public function scopePendingApproval($query)
    {
        return $query->where('status', 'applied');
    }
}
