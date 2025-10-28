<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\HasSecureId;

class TaskGraphicType extends Model
{
    use HasFactory, HasSecureId, LogsActivity;

    protected $fillable = [
        'task_id',
        'graphic_task_type_id',
    ];

    /**
     * Configure Activity Log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['task_id', 'graphic_task_type_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'تم ربط مهمة بنوع جرافيكي',
                'updated' => 'تم تحديث ربط المهمة الجرافيكية',
                'deleted' => 'تم إلغاء ربط المهمة الجرافيكية',
                default => $eventName
            });
    }

    /**
     * Get the task
     */
    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the graphic task type
     */
    public function graphicTaskType()
    {
        return $this->belongsTo(GraphicTaskType::class);
    }
}
