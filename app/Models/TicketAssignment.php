<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\HasSecureId;

class TicketAssignment extends Model implements Auditable
{
    use HasFactory, AuditableTrait, HasSecureId, LogsActivity;

    protected $auditEvents = [
        'created',
        'updated',
        'deleted',
    ];

    protected $auditInclude = [
        'ticket_id',
        'user_id',
        'assigned_by',
        'assigned_at',
        'unassigned_at',
        'assignment_notes',
        'is_active'
    ];

    protected $fillable = [
        'ticket_id',
        'user_id',
        'assigned_by',
        'assigned_at',
        'unassigned_at',
        'assignment_notes',
        'is_active'
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'unassigned_at' => 'datetime',
        'is_active' => 'boolean'
    ];

    /**
     * Configure Activity Log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'ticket_id', 'user_id', 'assigned_by', 'assigned_at',
                'unassigned_at', 'assignment_notes', 'is_active'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'تم تعيين تذكرة لمستخدم',
                'updated' => 'تم تحديث تعيين التذكرة',
                'deleted' => 'تم حذف تعيين التذكرة',
                default => $eventName
            });
    }

    /**
     * Get the ticket this assignment belongs to
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(ClientTicket::class, 'ticket_id');
    }

    /**
     * Get the user assigned to the ticket
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who made the assignment
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Scope to get only active assignments
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }


    /**
     * Get duration of assignment
     */
    public function getDurationAttribute(): string
    {
        $start = $this->assigned_at;
        $end = $this->unassigned_at ?? Carbon::now();

        return $start->diffForHumans($end, true);
    }

    /**
     * Unassign user from ticket
     */
    public function unassign(): void
    {
        $this->update([
            'is_active' => false,
            'unassigned_at' => now()
        ]);
    }

}
