<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CreateDailyAttendance extends Command
{
    protected $signature = 'attendance:create-daily';
    protected $description = 'Create attendance record for a random user every minute';

    public function handle()
    {
        Log::info('Starting attendance creation at: ' . now());

        // ضبط التوقيت على توقيت مصر
        $now = Carbon::now('Africa/Cairo');

        // اختيار موظف عشوائي
        $user = User::inRandomOrder()->first();

        if ($user) {
            // التحقق من عدم وجود حضور لهذا الموظف في نفس اليوم
            $existingAttendance = Attendance::where('user_id', $user->id)
                ->whereDate('check_in_time', $now->toDateString())
                ->first();

            if (!$existingAttendance) {
                Attendance::create([
                    'user_id' => $user->id,
                    'check_in_time' => $now,
                ]);

                Log::info("Created attendance record for user: {$user->name}");
                $this->info("تم تسجيل حضور الموظف: {$user->name}");
            } else {
                Log::info("User {$user->name} already has attendance for today");
                $this->info("الموظف {$user->name} لديه تسجيل حضور اليوم بالفعل");
            }
        } else {
            Log::warning('No users found in database');
            $this->error('لا يوجد موظفين في قاعدة البيانات');
        }
    }
}
