<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Schedule;

Schedule::command('inspire')->hourly();

Schedule::command('attendance:create-daily')
    ->everyMinute()
    ->name('attendance:create-daily');

Schedule::command('check:birthdays')
    ->everyMinute()
    ->name('check:birthdays');

Schedule::command('check:contracts')
    ->everyMinute()
    ->name('check:contracts');

// 🍽️ إيقاف المهام في وقت البريك - الساعة 1 ظهراً
Schedule::command('tasks:pause-running --time=1pm')
    ->dailyAt('13:00')
    ->timezone('Africa/Cairo')
    ->name('pause-tasks-break-time')
    ->description('إيقاف المهام في وقت البريك');

// 🏠 إيقاف المهام في نهاية العمل - كل دقيقة
Schedule::command('tasks:pause-running --time=all')
    ->everyMinute()
    ->name('pause-tasks-end-work')
    ->description('إيقاف المهام النشطة كل دقيقة');

