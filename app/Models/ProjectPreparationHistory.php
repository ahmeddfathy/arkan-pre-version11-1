<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ProjectPreparationHistory extends Model
{
    use HasFactory;

    protected $table = 'project_preparation_history';

    protected $fillable = [
        'project_id',
        'preparation_start_date',
        'preparation_days',
        'preparation_end_date',
        'notes',
        'user_id',
        'is_current',
        'effective_from'
    ];

    protected $casts = [
        'preparation_start_date' => 'datetime',
        'preparation_end_date' => 'datetime',
        'effective_from' => 'datetime',
        'is_current' => 'boolean',
        'preparation_days' => 'integer'
    ];

    protected $appends = ['time_ago', 'duration_text'];

    /**
     * العلاقة مع المشروع
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * العلاقة مع المستخدم الذي قام بالتغيير
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope للحصول على الفترات الحالية فقط
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    /**
     * Scope للحصول على فترات مشروع معين
     */
    public function scopeForProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * الحصول على الوقت منذ بدء هذه الفترة
     */
    public function getTimeAgoAttribute()
    {
        return $this->effective_from ? $this->effective_from->diffForHumans() : null;
    }

    /**
     * الحصول على مدة الفترة بالنص
     */
    public function getDurationTextAttribute()
    {
        if (!$this->preparation_days) {
            return 'غير محدد';
        }

        return $this->preparation_days . ' يوم عمل';
    }

    /**
     * هل هذه الفترة نشطة حالياً؟
     */
    public function isActive()
    {
        if (!$this->preparation_start_date || !$this->preparation_end_date) {
            return false;
        }

        $now = Carbon::now();
        return $now->between($this->preparation_start_date, $this->preparation_end_date);
    }

    /**
     * إنشاء سجل جديد لفترة تحضير
     */
    public static function logPreparationPeriod($projectId, $startDate, $days, $endDate = null, $notes = null, $userId = null)
    {
        $userId = $userId ?? Auth::id() ?? 1;

        // جعل جميع الفترات السابقة غير current
        static::where('project_id', $projectId)
            ->where('is_current', true)
            ->update(['is_current' => false]);

        return static::create([
            'project_id' => $projectId,
            'preparation_start_date' => $startDate,
            'preparation_days' => $days,
            'preparation_end_date' => $endDate,
            'notes' => $notes,
            'user_id' => $userId,
            'is_current' => true,
            'effective_from' => now()
        ]);
    }

    /**
     * الحصول على تاريخ فترات التحضير لمشروع معين
     */
    public static function getHistoryForProject($projectId)
    {
        return static::where('project_id', $projectId)
            ->with('user')
            ->orderBy('effective_from', 'desc')
            ->get();
    }

    /**
     * الحصول على الفترة الحالية لمشروع معين
     */
    public static function getCurrentPeriod($projectId)
    {
        return static::where('project_id', $projectId)
            ->where('is_current', true)
            ->first();
    }

    /**
     * التحقق من دخول المشروع في أكثر من فترة تحضير
     */
    public static function hasMultiplePreparationPeriods($projectId)
    {
        return static::where('project_id', $projectId)->count() > 1;
    }

    /**
     * عدد فترات التحضير لمشروع معين
     */
    public static function getPreparationPeriodsCount($projectId)
    {
        return static::where('project_id', $projectId)->count();
    }
}
