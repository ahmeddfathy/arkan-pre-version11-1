<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Casts\SafeEncryptedCast;
use App\Traits\HasSecureId;

class Attendance extends Model
{
    use HasFactory, HasSecureId, LogsActivity;

    protected $fillable = [
        'employee_id',
        'date',
        'check_in',
        'check_out',
        'early_minutes',
        'late_minutes',
        'working_hours',
        'work_shift_id',
        'status',
        'notes'
    ];

    protected $casts = [
        'date' => 'date',
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'early_minutes' => 'integer',
        'late_minutes' => 'integer',
        'working_hours' => 'float',
        'notes' => SafeEncryptedCast::class,
    ];

    /**
     * Configure Activity Log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'employee_id', 'date', 'check_in', 'check_out', 'early_minutes',
                'late_minutes', 'working_hours', 'status', 'work_shift_id'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'تم تسجيل حضور جديد',
                'updated' => 'تم تحديث بيانات الحضور',
                'deleted' => 'تم حذف سجل الحضور',
                default => $eventName
            });
    }

    public function getEarlyArrivalMinutes()
    {
        if ($this->early_minutes > 0) {
            return $this->early_minutes;
        }

        if (!$this->check_in || !$this->workShift) {
            return 0;
        }

        $shiftStartTime = Carbon::parse(Carbon::parse($this->date)->format('Y-m-d') . ' ' . $this->workShift->check_in_time->format('H:i:s'), 'Africa/Cairo');

        if ($this->check_in->lt($shiftStartTime)) {
            return round(abs($shiftStartTime->diffInMinutes($this->check_in)));
        }

        return 0;
    }

    public function getLateArrivalMinutes()
    {
        if ($this->late_minutes > 0) {
            return $this->late_minutes;
        }

        if (!$this->check_in || !$this->workShift) {
            return 0;
        }

        $shiftStartTime = Carbon::parse(Carbon::parse($this->date)->format('Y-m-d') . ' ' . $this->workShift->check_in_time->format('H:i:s'), 'Africa/Cairo');

        if ($this->check_in->gt($shiftStartTime)) {
            return abs(round($this->check_in->diffInMinutes($shiftStartTime)));
        }

        return 0;
    }

    public function getEarlyDepartureMinutes()
    {
        if ($this->status == 'early_leave' && $this->notes && preg_match('/Early departure: (\d+) minutes/', $this->notes, $matches)) {
            return (int)$matches[1];
        }

        if (!$this->check_out || !$this->workShift) {
            return 0;
        }

        $shiftEndTime = Carbon::parse(Carbon::parse($this->date)->format('Y-m-d') . ' ' . $this->workShift->check_out_time->format('H:i:s'), 'Africa/Cairo');

        if ($this->check_out->lt($shiftEndTime)) {
            return abs(round($shiftEndTime->diffInMinutes($this->check_out)));
        }

        return 0;
    }

    public function getLateDepartureMinutes()
    {
        if ($this->notes && preg_match('/Late departure: (\d+) minutes/', $this->notes, $matches)) {
            return (int)$matches[1];
        }

        if (!$this->check_out || !$this->workShift) {
            return 0;
        }

        $shiftEndTime = Carbon::parse(Carbon::parse($this->date)->format('Y-m-d') . ' ' . $this->workShift->check_out_time->format('H:i:s'), 'Africa/Cairo');

        if ($this->check_out->gt($shiftEndTime)) {
            return abs(round($this->check_out->diffInMinutes($shiftEndTime)));
        }

        return 0;
    }

    public function calculateWorkingHours()
    {
        if (!$this->check_in || !$this->check_out) {
            return null;
        }

        $hours = $this->check_out->floatDiffInHours($this->check_in);

        return round($hours, 2);
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id', 'employee_id');
    }

    public function workShift()
    {
        return $this->belongsTo(WorkShift::class);
    }
}
