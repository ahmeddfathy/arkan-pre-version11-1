<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\HasSecureId;

class GraphicTaskType extends Model
{
    use HasFactory, HasSecureId, LogsActivity;

    protected $fillable = [
        'name',
        'description',
        'points',
        'min_minutes',
        'max_minutes',
        'average_minutes',
        'department',
        'is_active',
    ];

    protected $casts = [
        'points' => 'integer',
        'min_minutes' => 'integer',
        'max_minutes' => 'integer',
        'average_minutes' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Configure Activity Log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name', 'description', 'points', 'min_minutes', 'max_minutes',
                'average_minutes', 'department', 'is_active'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'تم إنشاء نوع مهمة جرافيكية جديد',
                'updated' => 'تم تحديث نوع المهمة الجرافيكية',
                'deleted' => 'تم حذف نوع المهمة الجرافيكية',
                default => $eventName
            });
    }

    /**
     * Get the tasks associated with this graphic task type
     */
    public function tasks()
    {
        return $this->belongsToMany(Task::class, 'task_graphic_types', 'graphic_task_type_id', 'task_id')
                    ->withTimestamps();
    }

    /**
     * Scope for active graphic task types
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific department
     */
    public function scopeForDepartment($query, $department)
    {
        return $query->where('department', $department);
    }

    /**
     * Get formatted average time
     */
    public function getAverageTimeFormattedAttribute()
    {
        $hours = intval($this->average_minutes / 60);
        $minutes = $this->average_minutes % 60;

        if ($hours > 0) {
            return "{$hours}س {$minutes}د";
        }

        return "{$minutes}د";
    }

    /**
     * Get time range formatted
     */
    public function getTimeRangeFormattedAttribute()
    {
        return "{$this->min_minutes} - {$this->max_minutes} دقيقة";
    }
}
