<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Schedule;

Schedule::command('inspire')->hourly();

Schedule::command('attendance:create-daily')
    ->dailyAt('10:30')
    ->timezone('Africa/Cairo')
    ->name('attendance:create-daily');

Schedule::command('check:birthdays')
    ->everyMinute()
    ->name('check:birthdays');

Schedule::command('check:contracts')
    ->everyMinute()
    ->name('check:contracts');


Schedule::command('tasks:pause-running --time=12pm')
    ->dailyAt('12:00')
    ->timezone('Africa/Cairo')
    ->name('pause-tasks-break-time')
    ->description('إيقاف المهام في وقت البريك');


Schedule::command('tasks:pause-running --time=4pm')
    ->dailyAt('16:00')
    ->timezone('Africa/Cairo')
    ->name('pause-tasks-end-work')
    ->description('إيقاف المهام النشطة في نهاية العمل');
