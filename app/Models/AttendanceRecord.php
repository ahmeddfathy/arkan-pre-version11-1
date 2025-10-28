<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\HasSecureId;

class AttendanceRecord extends Model
{
    use HasSecureId, LogsActivity;

    protected $fillable = [
    "user_id",
    'employee_id',
    'attendance_date',
    'day',
    'status',
    'shift',
    'shift_hours',
    'entry_time',
    'exit_time',
    'delay_minutes',
    'early_minutes',
    'working_hours',
    'overtime_hours',
    'penalty',
    'notes'
  ];

  /**
   * Configure Activity Log options
   */
  public function getActivitylogOptions(): LogOptions
  {
      return LogOptions::defaults()
          ->logOnly([
              'user_id', 'employee_id', 'attendance_date', 'status', 'shift',
              'entry_time', 'exit_time', 'delay_minutes', 'early_minutes',
              'working_hours', 'overtime_hours', 'penalty'
          ])
          ->logOnlyDirty()
          ->dontSubmitEmptyLogs()
          ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
              'created' => 'تم تسجيل سجل حضور جديد',
              'updated' => 'تم تحديث سجل الحضور',
              'deleted' => 'تم حذف سجل الحضور',
              default => $eventName
          });
  }

  public function user()
  {
    return $this->belongsTo(User::class);
  }
}
