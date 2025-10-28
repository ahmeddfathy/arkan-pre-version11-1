<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\HasSecureId;

class Skill extends Model
{
    use HasFactory, SoftDeletes, HasSecureId, LogsActivity;

    protected $fillable = [
        'category_id',
        'name',
        'description',
        'max_points',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Configure Activity Log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['category_id', 'name', 'description', 'max_points', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'تم إنشاء مهارة جديدة',
                'updated' => 'تم تحديث المهارة',
                'deleted' => 'تم حذف المهارة',
                default => $eventName
            });
    }

    /**
     * Get the category that owns the skill.
     */
    public function category()
    {
        return $this->belongsTo(SkillCategory::class, 'category_id');
    }

    /**
     * Get the evaluation details for this skill.
     */
    public function evaluationDetails()
    {
        return $this->hasMany(EvaluationDetail::class);
    }
}
