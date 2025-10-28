<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CheckContractEndDates extends Command
{
    protected $signature = 'check:contracts';
    protected $description = 'Check for contracts ending tomorrow and notify HR and employees';

    public function handle()
    {
        try {
            Log::info('Starting contract check command...');

            $this->info('بدء التحقق من تواريخ نهاية العقود...');

            // الحصول على تاريخ الغد
            $tomorrow = Carbon::tomorrow();

            // البحث عن الموظفين الذين تنتهي عقودهم غداً
            $users = User::whereDate('last_contract_end_date', $tomorrow)->get();
            Log::info('Found ' . $users->count() . ' users with contracts ending tomorrow');

            if ($users->count() > 0) {
                // الحصول على مسؤولي HR
                $hrUsers = User::get()->filter(function ($user) {
                    return $user->hasRole('hr');
                });
                Log::info('Found ' . $hrUsers->count() . ' HR users');

                if ($hrUsers->isEmpty()) {
                    Log::warning('No HR users found in the system');
                }

                foreach ($users as $user) {
                    Log::info("Processing contract end notification for user: {$user->name}");

                    // إشعار للموظف نفسه
                    Notification::create([
                        'user_id' => $user->id,
                        'type' => 'admin_broadcast',
                        'data' => [
                            'title' => 'تنبيه انتهاء العقد ⚠️',
                            'message' => "تنبيه: عقدك ينتهي غداً {$tomorrow->format('Y-m-d')}",
                            'sender_name' => 'النظام',
                            'recipients' => 'employee'
                        ],
                        'read_at' => null
                    ]);

                    // إنشاء إشعار لكل مسؤول HR
                    foreach ($hrUsers as $hr) {
                        Notification::create([
                            'user_id' => $hr->id,
                            'type' => 'admin_broadcast',
                            'data' => [
                                'title' => 'تنبيه انتهاء عقد موظف ⚠️',
                                'message' => "ينتهي غداً {$tomorrow->format('Y-m-d')} عقد الموظف: {$user->name}",
                                'sender_name' => 'النظام',
                                'recipients' => 'hr'
                            ],
                            'read_at' => null
                        ]);
                        Log::info("Created notification for HR user: {$hr->name}");
                    }

                    Log::info("Created notifications for user: {$user->name}");
                    $this->info("تم إنشاء إشعارات لانتهاء عقد: {$user->name}");
                }

                $this->info("تم إنشاء إشعارات لـ {$users->count()} موظف و {$hrUsers->count()} مسؤول HR");
            } else {
                $this->info('لا توجد عقود تنتهي غداً ' . $tomorrow->format('Y-m-d'));
            }
        } catch (\Exception $e) {
            Log::error('Error in check:contracts command: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            $this->error('حدث خطأ أثناء فحص العقود: ' . $e->getMessage());
        }
    }
}
