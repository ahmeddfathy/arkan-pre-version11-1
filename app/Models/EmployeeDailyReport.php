<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class EmployeeDailyReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'report_date',
        'daily_work',
    ];

    protected $casts = [
        'report_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function getTodayReport($userId)
    {
        return self::where('user_id', $userId)
            ->where('report_date', Carbon::today())
            ->first();
    }

    public static function getReportByDate($userId, $date)
    {
        return self::where('user_id', $userId)
            ->where('report_date', $date)
            ->first();
    }
}

