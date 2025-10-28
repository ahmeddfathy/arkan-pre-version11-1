<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\HasSecureId;

class AdditionalTask extends Model
{
    use HasFactory, HasSecureId, LogsActivity;

    protected $fillable = [
        'title',
        'description',
        'points',
        'duration_hours',
        'original_end_time',
        'current_end_time',
        'extensions_count',
        'target_type',
        'target_department',
        'assignment_type',
        'max_participants',
        'status',
        'is_active',
        'icon',
        'color_code',
        'created_by',
        'season_id',
    ];

    protected $casts = [
        'original_end_time' => 'datetime',
        'current_end_time' => 'datetime',
        'duration_hours' => 'integer',
        'points' => 'integer',
        'extensions_count' => 'integer',
        'max_participants' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Configure Activity Log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'title', 'description', 'points', 'status', 'target_type', 'target_department',
                'assignment_type', 'max_participants', 'is_active', 'current_end_time',
                'created_by', 'season_id'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'تم إنشاء مهمة إضافية جديدة',
                'updated' => 'تم تحديث المهمة الإضافية',
                'deleted' => 'تم حذف المهمة الإضافية',
                default => $eventName
            });
    }

    // Relations
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'additional_task_users')
                    ->withPivot(['status', 'points_earned', 'user_notes', 'admin_notes', 'completion_data', 'applied_at', 'approved_at'])
                    ->withTimestamps();
    }

    public function taskUsers()
    {
        return $this->hasMany(AdditionalTaskUser::class);
    }

    // Helper Methods
    public function isExpired()
    {
        return Carbon::now()->gt($this->current_end_time);
    }

    public function timeRemaining()
    {
        if ($this->isExpired()) {
            return null;
        }

        return Carbon::now()->diff($this->current_end_time);
    }

    public function timeRemainingInHours()
    {
        if ($this->isExpired()) {
            return 0;
        }

        return Carbon::now()->diffInHours($this->current_end_time);
    }

    public function canBeExtended()
    {
        return $this->status === 'active' && $this->is_active;
    }

    public function extendTime($additionalHours, $reason = null)
    {
        if (!$this->canBeExtended()) {
            return false;
        }

        $this->current_end_time = $this->current_end_time->addHours((int)$additionalHours);
        $this->extensions_count += 1;
        $this->save();

        // Log the extension
        Log::info('Additional task time extended', [
            'task_id' => $this->id,
            'additional_hours' => $additionalHours,
            'new_end_time' => $this->current_end_time,
            'extensions_count' => $this->extensions_count,
            'reason' => $reason
        ]);

        return true;
    }

    public function getEligibleUsers()
    {
        if ($this->target_type === 'all') {
            return User::where('employee_status', 'active')->get();
        } elseif ($this->target_type === 'department') {
            return User::where('department', $this->target_department)
                      ->where('employee_status', 'active')
                      ->get();
        }

        return collect();
    }

        public function assignToUsers()
    {

        if ($this->assignment_type === 'application_required') {
            return 0;
        }

        $eligibleUsers = $this->getEligibleUsers();

        foreach ($eligibleUsers as $user) {
            AdditionalTaskUser::updateOrCreate([
                'additional_task_id' => $this->id,
                'user_id' => $user->id,
            ], [
                'status' => 'assigned'
            ]);
        }

        return $eligibleUsers->count();
    }

    public function requiresApplication()
    {
        return $this->assignment_type === 'application_required';
    }

    public function hasMaxParticipants()
    {
        return $this->max_participants !== null;
    }

    public function getApprovedParticipantsCount()
    {
        return $this->taskUsers()
                   ->whereIn('status', ['approved', 'assigned', 'completed'])
                   ->count();
    }

    public function canAcceptMoreParticipants()
    {
        if (!$this->hasMaxParticipants()) {
            return true;
        }

        return $this->getApprovedParticipantsCount() < $this->max_participants;
    }

    public function getPendingApplicationsCount()
    {
        return $this->taskUsers()->where('status', 'applied')->count();
    }

    public function getCompletionStats()
    {
        $totalAssigned = $this->taskUsers()->count();
        $completed = $this->taskUsers()->where('status', 'completed')->count();
        $assigned = $this->taskUsers()->where('status', 'assigned')->count();
        $pending = $this->taskUsers()->where('status', 'applied')->count();
        $failed = $this->taskUsers()->where('status', 'failed')->count();

        return [
            'total_assigned' => $totalAssigned,
            'completed' => $completed,
            'assigned' => $assigned,
            'pending' => $pending,
            'failed' => $failed,
            'completion_rate' => $totalAssigned > 0 ? round(($completed / $totalAssigned) * 100, 2) : 0,
        ];
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active')->where('is_active', true);
    }

    public function scopeExpired($query)
    {
        return $query->where('current_end_time', '<', Carbon::now());
    }

    public function scopeForUser($query, User $user)
    {
        return $query->where(function($q) use ($user) {
            $q->where('target_type', 'all')
              ->orWhere(function($subQ) use ($user) {
                  $subQ->where('target_type', 'department')
                       ->where('target_department', $user->department);
              });
        });
    }

    // Auto-expire expired tasks
    public static function expireOldTasks()
    {
        $expiredTasks = self::where('status', 'active')
                           ->where('current_end_time', '<', Carbon::now())
                           ->get();

        foreach ($expiredTasks as $task) {
            $task->update(['status' => 'expired']);

            // Mark uncompleted user tasks as failed
            $task->taskUsers()
                 ->where('status', 'assigned')
                 ->update(['status' => 'failed']);
        }

        return $expiredTasks->count();
    }
}
