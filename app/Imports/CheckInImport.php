<?php

namespace App\Imports;

use App\Models\SpecialCase;
use Maatwebsite\Excel\Concerns\ToModel;
use Carbon\Carbon;

class CheckInImport implements ToModel
{
    private const WORK_START_TIME = '08:00:00';

    public function model(array $row)
    {
        if (empty($row[0]) || empty($row[2]) || !is_numeric($row[2])) {
            return null;
        }

        $excelTime = floatval($row[0]);
        $totalMinutes = round($excelTime * 24 * 60);
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;

        $hours = $hours % 24;
        $timeString = sprintf('%02d:%02d:00', $hours, $minutes);

        $employee = \App\Models\User::where('employee_id', (int)$row[2])->first();
        if (!$employee) {
            return null;
        }

        $existingRecord = SpecialCase::where('employee_id', (int)$row[2])
            ->whereDate('date', now())
            ->first();

        if ($existingRecord) {
            return null;
        }

        $workStart = Carbon::createFromTimeString(self::WORK_START_TIME);
        $checkIn = Carbon::createFromTimeString($timeString);

        $lateMinutes = $checkIn->gt($workStart) ? abs($checkIn->diffInMinutes($workStart)) : 0;

        $specialCase = new SpecialCase([
            'employee_id' => (int)$row[2],
            'date' => now()->format('Y-m-d'),
            'check_in' => $timeString,
            'late_minutes' => $lateMinutes,
            'reason' => 'تم الاستيراد من ملف Excel',
        ]);

        return $specialCase;
    }
}
