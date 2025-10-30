<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * ServiceDependency Model
 *
 * يمثل علاقة الاعتمادية بين خدمتين
 * "الخدمة A تعتمد على الخدمة B"
 */
class ServiceDependency extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'service_id',
        'depends_on_service_id',
        'notes',
    ];

    /**
     * الخدمة التابعة (التي تنتظر)
     */
    public function service()
    {
        return $this->belongsTo(CompanyService::class, 'service_id');
    }

    /**
     * الخدمة المطلوبة (التي يجب أن تكتمل أولاً)
     */
    public function dependsOnService()
    {
        return $this->belongsTo(CompanyService::class, 'depends_on_service_id');
    }

    /**
     * Activity Log Configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['service_id', 'depends_on_service_id', 'notes'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'تم إنشاء اعتمادية خدمة جديدة',
                'updated' => 'تم تحديث اعتمادية الخدمة',
                'deleted' => 'تم حذف اعتمادية الخدمة',
                default => $eventName
            });
    }
}

