<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Casts\SafeEncryptedCast;
use App\Traits\HasSecureId;

class CallLog extends Model
{
    use HasFactory, HasSecureId, LogsActivity;

    protected $fillable = [
        'client_id',
        'employee_id',
        'created_by',
        'call_date',
        'contact_type',
        'call_summary',
        'notes',
        'duration_minutes',
        'outcome',
        'status',
    ];

    protected $casts = [
        'call_date' => 'datetime',
        'duration_minutes' => 'integer',
        'call_summary' => SafeEncryptedCast::class,
        'notes' => SafeEncryptedCast::class,
    ];

    /**
     * Configure Activity Log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'client_id', 'employee_id', 'created_by', 'call_date',
                'contact_type', 'call_summary', 'notes', 'duration_minutes',
                'outcome', 'status'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'تم إنشاء سجل مكالمة جديد',
                'updated' => 'تم تحديث سجل المكالمة',
                'deleted' => 'تم حذف سجل المكالمة',
                default => $eventName
            });
    }

    // Relationships
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Accessors & Mutators
    public function getContactTypeArabicAttribute()
    {
        $types = [
            'call' => 'مكالمة هاتفية',
            'email' => 'بريد إلكتروني',
            'whatsapp' => 'واتساب',
            'meeting' => 'اجتماع',
            'other' => 'آخر'
        ];

        return $types[$this->contact_type] ?? $this->contact_type;
    }

    public function getOutcomeArabicAttribute()
    {
        // Since outcome is now free text, just return it as is
        return $this->outcome;
    }

    public function getStatusArabicAttribute()
    {
        $statuses = [
            'successful' => 'تمت بنجاح',
            'failed' => 'فشلت',
            'needs_followup' => 'تحتاج متابعة'
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    public function getDurationFormattedAttribute()
    {
        if (!$this->duration_minutes) {
            return null;
        }

        $hours = floor($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;

        if ($hours > 0) {
            return $hours . ' ساعة ' . $minutes . ' دقيقة';
        }

        return $minutes . ' دقيقة';
    }

    // Scopes
    public function scopeForClient($query, $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeByEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeByContactType($query, $type)
    {
        return $query->where('contact_type', $type);
    }

    public function scopeRecentCalls($query, $days = 30)
    {
        return $query->where('call_date', '>=', Carbon::now()->subDays($days));
    }

}
