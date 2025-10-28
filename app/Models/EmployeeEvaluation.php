<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Casts\SafeEncryptedCast;
use App\Traits\HasSecureId;

class EmployeeEvaluation extends Model
{
    use HasFactory, SoftDeletes, HasSecureId, LogsActivity;

    protected $fillable = [
        'user_id',
        'evaluator_id',
        'evaluation_period',
        'evaluation_date',
        'notes',
    ];

    protected $casts = [
        'evaluation_date' => 'date',
        'notes' => SafeEncryptedCast::class,
    ];

    /**
     * Configure Activity Log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['user_id', 'evaluator_id', 'evaluation_period', 'evaluation_date'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'تم إنشاء تقييم موظف جديد',
                'updated' => 'تم تحديث تقييم الموظف',
                'deleted' => 'تم حذف تقييم الموظف',
                default => $eventName
            });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function evaluator()
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function evaluationDetails()
    {
        return $this->hasMany(EvaluationDetail::class, 'evaluation_id');
    }

    public function getTotalPointsAttribute()
    {
        return $this->evaluationDetails()->sum('points');
    }

    public function getMaxPossiblePointsAttribute()
    {
        $skillIds = $this->evaluationDetails()->pluck('skill_id')->toArray();
        return Skill::whereIn('id', $skillIds)->sum('max_points');
    }

    public function getScorePercentageAttribute()
    {
        $maxPoints = $this->max_possible_points;
        if ($maxPoints == 0) return 0;

        return round(($this->total_points / $maxPoints) * 100, 1);
    }
}
