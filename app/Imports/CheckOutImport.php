<?php

namespace App\Imports;

use App\Models\SpecialCase;
use Maatwebsite\Excel\Concerns\ToModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CheckOutImport implements ToModel
{
    private const WORK_END_TIME = '16:00:00';

    public function model(array $row)
    {
        try {
            Log::info('Processing check-out row', [
                'row_data' => $row
            ]);

            // تجاهل الصف إذا كان فارغاً أو عناوين
            if (empty($row[0]) || empty($row[2]) || !is_numeric($row[2])) {
                Log::info('Skipping invalid row', [
                    'reason' => 'Empty or non-numeric data',
                    'row' => $row
                ]);
                return null;
            }

            // تحويل الرقم العشري إلى وقت
            $excelTime = floatval($row[0]);
            $totalMinutes = round($excelTime * 24 * 60);
            $hours = floor($totalMinutes / 60);
            $minutes = $totalMinutes % 60;

            // التأكد من أن الساعات لا تتجاوز 24
            $hours = $hours % 24;
            $timeString = sprintf('%02d:%02d:00', $hours, $minutes);

            Log::info('Time conversion', [
                'excel_time' => $row[0],
                'total_minutes' => $totalMinutes,
                'hours' => $hours,
                'minutes' => $minutes,
                'converted_time' => $timeString
            ]);

            // التحقق من وجود الموظف
            $employee = \App\Models\User::where('employee_id', (int)$row[2])->first();
            if (!$employee) {
                Log::error('Employee not found', [
                    'employee_id' => $row[2]
                ]);
                return null;
            }

            $workEnd = Carbon::createFromTimeString(self::WORK_END_TIME);
            $checkOut = Carbon::createFromTimeString($timeString);

            // حساب دقائق الخروج المبكر
            $earlyLeaveMinutes = $checkOut->lt($workEnd) ? abs($workEnd->diffInMinutes($checkOut)) : 0;

            // البحث عن سجل موجود لنفس الموظف ونفس اليوم
            $specialCase = SpecialCase::where('employee_id', (int)$row[2])
                ->whereDate('date', now()->format('Y-m-d'))
                ->first();

            if ($specialCase) {
                Log::info('Updating existing record', [
                    'employee_id' => $row[2],
                    'excel_time' => $row[0],
                    'converted_time' => $timeString
                ]);

                $specialCase->update([
                    'check_out' => $timeString,
                    'early_leave_minutes' => $earlyLeaveMinutes
                ]);

                return null;
            }

            Log::info('Creating new check-out record', [
                'employee_id' => $row[2],
                'excel_time' => $row[0],
                'converted_time' => $timeString
            ]);

            return new SpecialCase([
                'employee_id' => (int)$row[2],
                'date' => now()->format('Y-m-d'),
                'check_out' => $timeString,
                'early_leave_minutes' => $earlyLeaveMinutes,
                'reason' => 'تم الاستيراد من ملف Excel',
            ]);
        } catch (\Exception $e) {
            Log::error('Error processing check-out row', [
                'error' => $e->getMessage(),
                'row' => $row,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
