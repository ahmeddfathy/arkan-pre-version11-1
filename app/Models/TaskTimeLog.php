<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Traits\HasSecureId;
use Illuminate\Support\Facades\DB;

class TaskTimeLog extends Model
{
    use HasFactory, HasSecureId;

    protected $table = 'task_time_logs';

    protected $fillable = [
        'task_user_id',
        'template_task_user_id',
        'user_id',
        'task_type',
        'started_at',
        'stopped_at',
        'duration_minutes',
        'work_date',
        'season_id',
    ];

    protected $guarded = [];

    public function setStartedAtAttribute($value)
    {
        if ($this->exists) {
            return;
        }

        $this->attributes['started_at'] = $value;
    }

    public static function whereRaw($sql, $bindings = [])
    {
        return parent::whereRaw($sql, $bindings);
    }

    protected $casts = [
        'started_at' => 'datetime',
        'stopped_at' => 'datetime',
        'duration_minutes' => 'integer',
        'work_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function taskUser()
    {
        return $this->belongsTo(TaskUser::class, 'task_user_id');
    }

    public function templateTaskUser()
    {
        return $this->belongsTo(TemplateTaskUser::class, 'template_task_user_id');
    }

    public function getTaskAttribute()
    {
        return $this->task_type === 'template' ? $this->templateTaskUser : $this->taskUser;
    }

    public function isActive(): bool
    {
        return is_null($this->stopped_at);
    }

    public function isCompleted(): bool
    {
        return !is_null($this->stopped_at);
    }

    public function stop(): self
    {
        if ($this->isActive()) {
            $originalStartedAt = $this->started_at;

            $this->stopped_at = now();
            $this->calculateDuration();

            if ($this->started_at != $originalStartedAt) {
                $this->started_at = $originalStartedAt;
            }

            $this->save();
        }

        return $this;
    }

    public function calculateDuration(): void
    {
        if ($this->started_at && $this->stopped_at) {
            $startTime = Carbon::parse($this->started_at);
            $stopTime = Carbon::parse($this->stopped_at);

            $diffInMinutes = $startTime->diffInMinutes($stopTime);

            if ($stopTime->lt($startTime) || $diffInMinutes > 1440) {
                $diffInMinutes = $startTime->diffInMinutes(now());
                if ($diffInMinutes > 1440) {
                    $diffInMinutes = 1440;
                }
            }

            $this->duration_minutes = $diffInMinutes;
        }
    }

    public function getFormattedDurationAttribute(): string
    {
        if (!$this->duration_minutes) {
            return '0 دقيقة';
        }

        $hours = intdiv($this->duration_minutes, 60);
        $minutes = $this->duration_minutes % 60;

        $parts = [];
        if ($hours > 0) {
            $parts[] = $hours . ' ساعة';
        }
        if ($minutes > 0) {
            $parts[] = $minutes . ' دقيقة';
        }

        return implode(' و ', $parts);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('stopped_at');
    }

    public function scopeCompleted($query)
    {
        return $query->whereNotNull('stopped_at');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('work_date', $date);
    }

    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('work_date', [$startDate, $endDate]);
    }

    public function scopeForTaskType($query, $taskType)
    {
        return $query->where('task_type', $taskType);
    }

    public function scopeForSeason($query, $seasonId)
    {
        return $query->where('season_id', $seasonId);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->work_date) {
                $model->work_date = Carbon::parse($model->started_at)->toDateString();
            }
        });

        static::created(function ($model) {
        });

        static::updating(function ($model) {
            if ($model->isDirty('started_at')) {
                $model->started_at = $model->getOriginal('started_at');
            }

            if ($model->isDirty('stopped_at') && $model->stopped_at) {
                $model->calculateDuration();
            }
        });

        static::updated(function ($model) {
        });

        static::saving(function ($model) {
        });

        static::saved(function ($model) {
            $freshModel = static::find($model->id);
            $rawData = DB::table('task_time_logs')->where('id', $model->id)->first();

            if ($freshModel && $freshModel->started_at != $model->started_at) {
                DB::table('task_time_logs')
                    ->where('id', $model->id)
                    ->update(['started_at' => $model->started_at]);
            }
        });
    }
}
