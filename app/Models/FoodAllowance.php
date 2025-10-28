<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\HasSecureId;

class FoodAllowance extends Model
{
    use HasSecureId, LogsActivity;

    protected $fillable = [
        'user_id',
        'date',
        'amount',
        'food_type',
        'created_by',
    ];


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['user_id', 'date', 'amount', 'food_type', 'created_by'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'تم إنشاء بدل أكل جديد',
                'updated' => 'تم تحديث بدل الأكل',
                'deleted' => 'تم حذف بدل الأكل',
                default => $eventName
            });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
