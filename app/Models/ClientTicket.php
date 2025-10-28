<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Casts\SafeEncryptedCast;
use App\Traits\HasSecureId;

class ClientTicket extends Model
{
    use HasFactory, HasSecureId, LogsActivity;

    protected $fillable = [
        'ticket_number',
        'title',
        'description',
        'status',
        'priority',
        'department',
        'project_id',
        'assigned_to',
        'created_by',
        'resolved_at',
        'resolution_notes',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'description' => SafeEncryptedCast::class,
        'resolution_notes' => SafeEncryptedCast::class,
    ];

    /**
     * Configure Activity Log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'ticket_number', 'title', 'description', 'status', 'priority', 'department',
                'project_id', 'assigned_to', 'created_by', 'resolved_at'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'تم إنشاء تذكرة عميل جديدة',
                'updated' => 'تم تحديث تذكرة العميل',
                'deleted' => 'تم حذف تذكرة العميل',
                default => $eventName
            });
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ticket) {
            if (empty($ticket->ticket_number)) {
                $ticket->ticket_number = self::generateTicketNumber();
            }
        });
    }

    public static function generateTicketNumber()
    {
        do {
            $number = 'TKT-' . date('Y') . '-' . str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT);
        } while (self::where('ticket_number', $number)->exists());

        return $number;
    }

    // Relationships
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function assignedEmployee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function comments()
    {
        return $this->hasMany(TicketComment::class, 'ticket_id')->orderBy('created_at', 'asc');
    }

    public function assignments()
    {
        return $this->hasMany(TicketAssignment::class, 'ticket_id');
    }

    public function activeAssignments()
    {
        return $this->hasMany(TicketAssignment::class, 'ticket_id')->where('is_active', true);
    }

    public function history()
    {
        return $this->hasMany(TicketWorkflowHistory::class, 'ticket_id')->orderBy('changed_at', 'desc');
    }

    public function assignedUsers()
    {
        return $this->belongsToMany(User::class, 'ticket_assignments', 'ticket_id', 'user_id')
                    ->wherePivot('is_active', true)
                    ->withPivot(['assigned_at', 'assigned_by', 'assignment_notes']);
    }

    // Accessors
    public function getStatusArabicAttribute()
    {
        $statuses = [
            'open' => 'مفتوحة',
            'assigned' => 'معينة',
            'resolved' => 'محلولة',
            'closed' => 'مغلقة'
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    public function getPriorityArabicAttribute()
    {
        $priorities = [
            'low' => 'منخفضة',
            'medium' => 'متوسطة',
            'high' => 'عالية'
        ];

        return $priorities[$this->priority] ?? $this->priority;
    }

    public function getDepartmentArabicAttribute()
    {
        // إذا كان القسم فارغ
        if (!$this->department) {
            return 'غير محدد';
        }

        // ترجمة بعض الأقسام الشائعة
        $commonDepartments = [
            'technical' => 'قسم فني',
            'financial' => 'قسم مالي',
            'marketing' => 'قسم تسويقي',
            'customer_service' => 'خدمة عملاء',
            'general' => 'عام'
        ];

        return $commonDepartments[$this->department] ?? $this->department;
    }

    public function getPriorityColorAttribute()
    {
        $colors = [
            'low' => 'success',
            'medium' => 'warning',
            'high' => 'danger'
        ];

        return $colors[$this->priority] ?? 'secondary';
    }

    public function getStatusColorAttribute()
    {
        $colors = [
            'open' => 'danger',
            'assigned' => 'warning',
            'resolved' => 'success',
            'closed' => 'secondary'
        ];

        return $colors[$this->status] ?? 'secondary';
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeByDepartment($query, $department)
    {
        return $query->where('department', $department);
    }

    public function scopeForProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'assigned']);
    }

    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    // Methods
    public function resolve($notes = null)
    {
        $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolution_notes' => $notes
        ]);
    }

    public function reopen()
    {
        $this->update([
            'status' => 'open',
            'resolved_at' => null,
            'resolution_notes' => null
        ]);
    }

    public function assignTo($userId)
    {
        $this->update([
            'assigned_to' => $userId,
            'status' => 'assigned'
        ]);
    }

    public function close()
    {
        $this->update([
            'status' => 'closed'
        ]);
    }

    // Simple helper methods
    public function isOpen()
    {
        return in_array($this->status, ['open', 'assigned']);
    }

    public function isResolved()
    {
        return $this->status === 'resolved';
    }

    public function isClosed()
    {
        return $this->status === 'closed';
    }

    // Check if high priority and needs attention
    public function isHighPriority()
    {
        return $this->priority === 'high';
    }
}
