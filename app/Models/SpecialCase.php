<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\HasSecureId;

class SpecialCase extends Model
{
    use HasSecureId, LogsActivity;

    protected $fillable = [
    'employee_id',
    'date',
    'check_in',
    'check_out',
    'late_minutes',
    'early_leave_minutes',
    'reason'
  ];

  protected $casts = [
    'date' => 'date',
    'check_in' => 'datetime',
    'check_out' => 'datetime',
  ];

  /**
   * Configure Activity Log options
   */
  public function getActivitylogOptions(): LogOptions
  {
      return LogOptions::defaults()
          ->logOnly([
              'employee_id', 'date', 'check_in', 'check_out',
              'late_minutes', 'early_leave_minutes', 'reason'
          ])
          ->logOnlyDirty()
          ->dontSubmitEmptyLogs()
          ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
              'created' => 'تم إنشاء حالة خاصة جديدة',
              'updated' => 'تم تحديث الحالة الخاصة',
              'deleted' => 'تم حذف الحالة الخاصة',
              default => $eventName
          });
  }

  public function employee()
  {
    return $this->belongsTo(User::class, 'employee_id', 'employee_id');
  }
}
