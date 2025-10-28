<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServicePause extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'service_id',
        'pause_reason',
        'pause_notes',
        'paused_at',
        'resumed_at',
        'paused_by',
        'resumed_by',
        'is_active',
    ];

    protected $casts = [
        'paused_at' => 'datetime',
        'resumed_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * العلاقة مع المشروع
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * العلاقة مع الخدمة
     */
    public function service()
    {
        return $this->belongsTo(CompanyService::class, 'service_id');
    }

    /**
     * العلاقة مع المستخدم الذي قام بالتوقيف
     */
    public function pausedBy()
    {
        return $this->belongsTo(User::class, 'paused_by');
    }

    /**
     * العلاقة مع المستخدم الذي قام بإلغاء التوقيف
     */
    public function resumedBy()
    {
        return $this->belongsTo(User::class, 'resumed_by');
    }

    /**
     * Scope للحصول على التوقيفات النشطة فقط
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope للحصول على التوقيفات المنتهية
     */
    public function scopeCompleted($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope للحصول على توقيفات مشروع معين
     */
    public function scopeForProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Scope للحصول على توقيفات خدمة معينة
     */
    public function scopeForService($query, $serviceId)
    {
        return $query->where('service_id', $serviceId);
    }

    /**
     * Scope للحصول على التوقيفات حسب السبب
     */
    public function scopeByReason($query, $reason)
    {
        return $query->where('pause_reason', $reason);
    }

    /**
     * الحصول على مدة التوقيف بالأيام (عدد صحيح فقط)
     */
    public function getDurationInDaysAttribute()
    {
        if (!$this->paused_at) {
            return 0;
        }

        $endDate = $this->resumed_at ?? now();

        // نستخدم floor للحصول على عدد صحيح من الأيام
        $days = floor($this->paused_at->diffInDays($endDate, false));

        return (int) $days;
    }

    /**
     * الحصول على مدة التوقيف بالساعات
     */
    public function getDurationInHoursAttribute()
    {
        if (!$this->paused_at) {
            return 0;
        }

        $endDate = $this->resumed_at ?? now();
        return $this->paused_at->diffInHours($endDate);
    }

    /**
     * الحصول على مدة التوقيف بصيغة نصية
     */
    public function getDurationShortAttribute()
    {
        if (!$this->paused_at) {
            return 'لم يبدأ';
        }

        $endDate = $this->resumed_at ?? now();
        $days = $this->paused_at->diffInDays($endDate);

        // إذا أقل من يوم
        if ($days == 0) {
            return 'أقل من يوم';
        }

        // إذا يوم واحد
        if ($days == 1) {
            return 'يوم واحد';
        }

        // إذا يومين
        if ($days == 2) {
            return 'يومين';
        }

        // إذا أكثر من يومين
        return $days . ' يوم';
    }

    /**
     * إلغاء التوقيف
     */
    public function resume($userId = null)
    {
        $this->resumed_at = now();
        $this->resumed_by = $userId ?? \Illuminate\Support\Facades\Auth::id();
        $this->is_active = false;

        return $this->save();
    }

    /**
     * التحقق من أن التوقيف نشط
     */
    public function isActive()
    {
        return $this->is_active === true;
    }
}
