<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\HasSecureId;

class AdministrativeDecision extends Model
{
    use HasSecureId, LogsActivity;

    protected $fillable = [
        'notification_id',
        'user_id',
        'acknowledged_at'
    ];

    protected $casts = [
        'acknowledged_at' => 'datetime'
    ];

    /**
     * Configure Activity Log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['notification_id', 'user_id', 'acknowledged_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'تم إنشاء قرار إداري جديد',
                'updated' => 'تم تحديث القرار الإداري',
                'deleted' => 'تم حذف القرار الإداري',
                default => $eventName
            });
    }

    public function notification()
    {
        return $this->belongsTo(Notification::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
