<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckBirthdays extends Command
{
    protected $signature = 'check:birthdays';
    protected $description = 'Check for upcoming birthdays and create notifications';

    public function handle()
    {
        try {
            Log::info('Starting birthday check command...');

            $today = Carbon::now('Africa/Cairo');
            Log::info('Current date: ' . $today->format('Y-m-d'));

            // التحقق من وجود عمود date_of_birth
            if (!Schema::hasColumn('users', 'date_of_birth')) {
                Log::error('date_of_birth column not found in users table');
                return;
            }

            $users = User::whereNotNull('date_of_birth')
                ->whereMonth('date_of_birth', $today->month)
                ->whereDay('date_of_birth', '>=', $today->day)
                ->whereDay('date_of_birth', '<=', $today->copy()->addDay()->day)
                ->get();

            Log::info('Found ' . $users->count() . ' users with birthdays');

            if ($users->isEmpty()) {
                $this->info('لا توجد أعياد ميلاد اليوم أو غداً');
                return;
            }

            foreach ($users as $user) {
                try {
                    DB::beginTransaction();

                    $birthDate = Carbon::parse($user->date_of_birth);
                    Log::info("Processing user: {$user->name}, Birth date: {$user->date_of_birth}");

                    if ($birthDate->isBirthday($today)) {
                        Log::info("Creating notification for {$user->name}'s birthday today");

                        // إشعار واحد للجميع عن عيد الميلاد اليوم
                        $notification = Notification::create([
                            'user_id' => 1,
                            'type' => 'admin_broadcast',
                            'data' => [
                                'title' => 'عيد ميلاد سعيد! 🎉',
                                'message' => "اليوم عيد ميلاد {$user->name}! كل عام وأنتم بخير",
                                'sender_name' => 'النظام',
                                'recipients' => 'all'
                            ],
                            'read_at' => null
                        ]);

                        if (!$notification) {
                            throw new \Exception("Failed to create notification for {$user->name}'s birthday");
                        }

                        $this->info("اليوم عيد ميلاد {$user->name}!");
                        Log::info("Successfully created notification for {$user->name}'s birthday today");
                    } elseif ($birthDate->isBirthday($today->copy()->addDay())) {
                        Log::info("Creating notification for {$user->name}'s birthday tomorrow");

                        // إشعار واحد للجميع عن عيد الميلاد غداً
                        $notification = Notification::create([
                            'user_id' => 1,
                            'type' => 'admin_broadcast',
                            'data' => [
                                'title' => 'عيد ميلاد غداً 🎂',
                                'message' => "غداً عيد ميلاد {$user->name}",
                                'sender_name' => 'النظام',
                                'recipients' => 'all'
                            ],
                            'read_at' => null
                        ]);

                        if (!$notification) {
                            throw new \Exception("Failed to create notification for {$user->name}'s birthday tomorrow");
                        }

                        $this->info("غداً عيد ميلاد {$user->name}!");
                        Log::info("Successfully created notification for {$user->name}'s birthday tomorrow");
                    }

                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error("Error processing birthday for user {$user->id}: " . $e->getMessage());
                    Log::error($e->getTraceAsString());
                    continue;
                }
            }
        } catch (\Exception $e) {
            Log::error('Error in check:birthdays command: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            $this->error('حدث خطأ أثناء فحص أعياد الميلاد: ' . $e->getMessage());
        }
    }
}
