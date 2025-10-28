<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\HasSecureId;

class Package extends Model
{
    use HasSecureId, LogsActivity;

    protected $fillable = [
        'name',
        'description',
        'services',
        'total_points',
    ];

    protected $casts = [
        'services' => 'array',
    ];

    /**
     * Configure Activity Log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'description', 'services', 'total_points'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'تم إنشاء حزمة جديدة',
                'updated' => 'تم تحديث الحزمة',
                'deleted' => 'تم حذف الحزمة',
                default => $eventName
            });
    }

}
