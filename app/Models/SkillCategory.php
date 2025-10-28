<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\HasSecureId;

class SkillCategory extends Model
{
    use HasFactory, SoftDeletes, HasSecureId, LogsActivity;

    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Configure Activity Log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'description'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'تم إنشاء فئة مهارات جديدة',
                'updated' => 'تم تحديث فئة المهارات',
                'deleted' => 'تم حذف فئة المهارات',
                default => $eventName
            });
    }

    public function skills()
    {
        return $this->hasMany(Skill::class, 'category_id');
    }
}
