<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\HasSecureId;
use Illuminate\Support\Facades\Auth;

class TicketWorkflowHistory extends Model
{
    use HasFactory, HasSecureId, LogsActivity;

    protected $table = 'ticket_workflow_history';

    protected $fillable = [
        'ticket_id',
        'user_id',
        'action',
        'description',
        'old_value',
        'new_value',
        'changed_at'
    ];

    protected $casts = [
        'changed_at' => 'datetime'
    ];

    /**
     * Configure Activity Log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'ticket_id', 'user_id', 'action', 'description',
                'old_value', 'new_value', 'changed_at'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'تم تسجيل حدث جديد في سير عمل التذكرة',
                'updated' => 'تم تحديث سجل سير عمل التذكرة',
                'deleted' => 'تم حذف سجل سير عمل التذكرة',
                default => $eventName
            });
    }

    /**
     * Get the ticket this history belongs to
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(ClientTicket::class, 'ticket_id');
    }

    /**
     * Get the user who made the change
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get action in Arabic
     */
    public function getActionArabicAttribute(): string
    {
        return match($this->action) {
            'created' => 'تم إنشاء التذكرة',
            'assigned' => 'تم تعيين التذكرة',
            'unassigned' => 'تم إلغاء تعيين التذكرة',
            'status_changed' => 'تم تغيير الحالة',
            'priority_changed' => 'تم تغيير الأولوية',
            'resolved' => 'تم حل التذكرة',
            'reopened' => 'تم إعادة فتح التذكرة',
            'comment_added' => 'تم إضافة تعليق',
            'updated' => 'تم تحديث التذكرة',
            default => $this->action
        };
    }

    /**
     * Get action icon
     */
    public function getIconAttribute(): string
    {
        return match($this->action) {
            'created' => 'fas fa-plus-circle',
            'assigned' => 'fas fa-user-plus',
            'unassigned' => 'fas fa-user-minus',
            'status_changed' => 'fas fa-exchange-alt',
            'priority_changed' => 'fas fa-exclamation-triangle',
            'resolved' => 'fas fa-check-circle',
            'reopened' => 'fas fa-undo',
            'comment_added' => 'fas fa-comment',
            'updated' => 'fas fa-edit',
            default => 'fas fa-circle'
        };
    }

    /**
     * Get action color
     */
    public function getColorAttribute(): string
    {
        return match($this->action) {
            'created' => 'primary',
            'assigned' => 'info',
            'unassigned' => 'warning',
            'status_changed' => 'secondary',
            'priority_changed' => 'warning',
            'resolved' => 'success',
            'reopened' => 'warning',
            'comment_added' => 'info',
            'updated' => 'primary',
            default => 'secondary'
        };
    }

    /**
     * Create history record
     */
    public static function createRecord($ticketId, $action, $description, $oldValue = null, $newValue = null, $userId = null)
    {
        return static::create([
            'ticket_id' => $ticketId,
            'user_id' => $userId ?? Auth::id(),
            'action' => $action,
            'description' => $description,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'changed_at' => now()
        ]);
    }
}
