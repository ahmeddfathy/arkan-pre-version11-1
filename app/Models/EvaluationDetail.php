<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\HasSecureId;

class EvaluationDetail extends Model
{
    use HasFactory, HasSecureId, LogsActivity;

    protected $fillable = [
        'evaluation_id',
        'skill_id',
        'points',
        'comments',
    ];

    /**
     * Configure Activity Log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['evaluation_id', 'skill_id', 'points', 'comments'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'تم إنشاء تفاصيل تقييم جديدة',
                'updated' => 'تم تحديث تفاصيل التقييم',
                'deleted' => 'تم حذف تفاصيل التقييم',
                default => $eventName
            });
    }

    public function evaluation()
    {
        return $this->belongsTo(EmployeeEvaluation::class, 'evaluation_id');
    }

    public function skill()
    {
        return $this->belongsTo(Skill::class, 'skill_id');
    }

    public function getScorePercentageAttribute()
    {
        $maxPoints = $this->skill ? $this->skill->max_points : 0;
        if ($maxPoints == 0) return 0;

        return round(($this->points / $maxPoints) * 100, 1);
    }
}
