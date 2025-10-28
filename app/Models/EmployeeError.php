<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasSecureId;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class EmployeeError extends Model
{
    use HasFactory, SoftDeletes, HasSecureId, LogsActivity;

    protected $fillable = [
        'secure_id',
        'user_id',
        'errorable_type',
        'errorable_id',
        'title',
        'description',
        'error_category',
        'error_type',
        'reported_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Boot the model and generate secure_id
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->secure_id)) {
                $model->secure_id = \Illuminate\Support\Str::random(32);
            }
        });
    }

    /**
     * Configure Activity Log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'user_id', 'errorable_type', 'errorable_id', 'title',
                'description', 'error_category', 'error_type', 'reported_by'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'تم تسجيل خطأ على موظف',
                'updated' => 'تم تحديث خطأ الموظف',
                'deleted' => 'تم حذف خطأ الموظف',
                default => $eventName
            });
    }

    /**
     * علاقة polymorphic مع المهمة أو المشروع
     * TaskUser, TemplateTaskUser, ProjectServiceUser
     */
    public function errorable()
    {
        return $this->morphTo();
    }

    /**
     * الموظف صاحب الخطأ
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * من سجل الخطأ
     */
    public function reportedBy()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    /**
     * التحقق من نوع الخطأ
     */
    public function isCritical(): bool
    {
        return $this->error_type === 'critical';
    }

    public function isNormal(): bool
    {
        return $this->error_type === 'normal';
    }

    /**
     * الحصول على نص نوع الخطأ
     */
    public function getErrorTypeTextAttribute(): string
    {
        return match($this->error_type) {
            'critical' => 'جوهري',
            'normal' => 'عادي',
            default => 'غير محدد'
        };
    }

    /**
     * الحصول على نص تصنيف الخطأ
     */
    public function getErrorCategoryTextAttribute(): string
    {
        return match($this->error_category) {
            'quality' => 'جودة',
            'deadline' => 'موعد نهائي',
            'communication' => 'تواصل',
            'technical' => 'فني',
            'procedural' => 'إجرائي',
            'other' => 'أخرى',
            default => 'غير محدد'
        };
    }

    /**
     * الحصول على لون الخطأ حسب النوع
     */
    public function getErrorColorAttribute(): string
    {
        return match($this->error_type) {
            'critical' => 'red',
            'normal' => 'orange',
            default => 'gray'
        };
    }

    /**
     * Scope للأخطاء الجوهرية
     */
    public function scopeCritical($query)
    {
        return $query->where('error_type', 'critical');
    }

    /**
     * Scope للأخطاء العادية
     */
    public function scopeNormal($query)
    {
        return $query->where('error_type', 'normal');
    }

    /**
     * Scope حسب التصنيف
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('error_category', $category);
    }

    /**
     * Scope حسب الموظف
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope حسب من سجل الخطأ
     */
    public function scopeByReporter($query, $reporterId)
    {
        return $query->where('reported_by', $reporterId);
    }
}
