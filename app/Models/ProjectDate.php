<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ProjectDate extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'date_type',
        'date_value',
        'notes',
        'user_id',
        'is_current',
        'effective_from'
    ];

    protected $casts = [
        'date_value' => 'date',
        'effective_from' => 'datetime',
        'is_current' => 'boolean'
    ];

    /**
     * العلاقة مع المشروع
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * العلاقة مع المستخدم الذي أضاف التاريخ
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope للحصول على التواريخ الحالية فقط
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    /**
     * Scope للحصول على تواريخ مشروع معين
     */
    public function scopeForProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Scope للحصول على نوع تاريخ معين
     */
    public function scopeOfType($query, $dateType)
    {
        return $query->where('date_type', $dateType);
    }

    /**
     * الحصول على اسم نوع التاريخ بالعربية
     */
    public function getDateTypeNameAttribute()
    {
        $types = [
            'start_date' => 'تاريخ البداية',
            'team_delivery_date' => 'تاريخ تسليم الفريق',
            'client_agreed_delivery_date' => 'تاريخ متفق عليه مع العميل',
            'actual_delivery_date' => 'تاريخ التسليم الفعلي'
        ];

        return $types[$this->date_type] ?? $this->date_type;
    }

    /**
     * الحصول على التاريخ مع الوقت منذ الإضافة
     */
    public function getTimeAgoAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * إنشاء log لتاريخ قديم
     */
    public static function logOldDate($projectId, $dateType, $dateValue, $notes = null, $userId = null)
    {
        $userId = $userId ?? Auth::id() ?? 1;

        return static::create([
            'project_id' => $projectId,
            'date_type' => $dateType,
            'date_value' => $dateValue,
            'notes' => $notes,
            'user_id' => $userId,
            'is_current' => false,
            'effective_from' => now()
        ]);
    }

    /**
     * الحصول على تاريخ التسلسل الزمني لمشروع معين
     */
    public static function getHistoryForProject($projectId, $dateType = null)
    {
        $query = static::where('project_id', $projectId)
            ->with('user')
            ->orderBy('effective_from', 'desc');

        if ($dateType) {
            $query->where('date_type', $dateType);
        }

        return $query->get();
    }
}
