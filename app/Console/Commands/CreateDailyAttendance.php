<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Traits\HasNTPTime;

class CreateDailyAttendance extends Command
{
    use HasNTPTime;

    protected $signature = 'attendance:create-daily';
    protected $description = 'Create absence records for users who did not check in';

    public function handle()
    {
        Log::info('Starting attendance check at: ' . now());

        $now = $this->getCurrentCairoTime();
        $today = $now->toDateString();

        $users = User::whereNotNull('employee_id')
            ->whereNotNull('work_shift_id')
            ->get();

        if ($users->isEmpty()) {
            Log::warning('No users found with employee_id and work_shift_id');
            $this->error('لا يوجد موظفين متاحين (يجب أن يكون لديهم employee_id و work_shift_id)');
            return;
        }

        $createdCount = 0;
        $skippedCount = 0;

        foreach ($users as $user) {
            $existingAttendance = Attendance::where('employee_id', $user->employee_id)
                ->where('date', $today)
                ->first();

            if (!$existingAttendance) {
                Attendance::create([
                    'employee_id' => $user->employee_id,
                    'date' => $today,
                    'check_in' => null,
                    'work_shift_id' => $user->work_shift_id,
                    'status' => 'absent',
                ]);

                $createdCount++;
                Log::info("Created absence record for user: {$user->name} (employee_id: {$user->employee_id})");
            } else {
                $skippedCount++;
            }
        }

        $this->info("تم إنشاء {$createdCount} سجل غياب، وتم تخطي {$skippedCount} موظف لديهم حضور");
        Log::info("Attendance check completed: {$createdCount} absence records created, {$skippedCount} users skipped");
    }
}
