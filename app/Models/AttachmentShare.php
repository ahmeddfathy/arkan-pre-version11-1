<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\HasSecureId;

class AttachmentShare extends Model
{
    use HasFactory, HasSecureId, LogsActivity;

    protected $fillable = [
        'attachment_id',
        'shared_by',
        'shared_with',
        'access_token',
        'expires_at',
        'view_count',
        'is_active',
        'description',
    ];

    // أنواع الملفات انتقلت إلى AttachmentConfirmation@getFileTypes

    protected $casts = [
        'expires_at' => 'datetime',
        'shared_with' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Configure Activity Log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'attachment_id', 'shared_by', 'shared_with', 'access_token',
                'expires_at', 'view_count', 'is_active', 'description'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'تم مشاركة مرفق جديد',
                'updated' => 'تم تحديث مشاركة المرفق',
                'deleted' => 'تم حذف مشاركة المرفق',
                default => $eventName
            });
    }

    /**
     * العلاقة مع المرفق
     */
    public function attachment()
    {
        return $this->belongsTo(ProjectAttachment::class, 'attachment_id');
    }

    /**
     * العلاقة مع المستخدم الذي شارك الملف
     */
    public function sharedBy()
    {
        return $this->belongsTo(User::class, 'shared_by');
    }

    /**
     * جلب المستخدمين المشارك معهم
     */
    public function getSharedWithUsersAttribute()
    {
        if (!$this->shared_with) {
            return collect();
        }

        return User::whereIn('id', $this->shared_with)->get();
    }

    /**
     * زيادة عدد المشاهدات
     */
    public function incrementViewCount()
    {
        $this->increment('view_count');
    }

    /**
     * التحقق من صلاحية المشاركة
     */
    public function isValid()
    {
        return $this->is_active &&
               (!$this->expires_at || $this->expires_at->isFuture());
    }

    /**
     * التحقق من انتهاء صلاحية المشاركة
     */
    public function isExpired()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * تعطيل المشاركة
     */
    public function deactivate()
    {
        $this->update(['is_active' => false]);
    }

    /**
     * التحقق من أن المستخدم مخول للوصول
     */
    public function canUserAccess($userId)
    {
        return $this->isValid() && in_array($userId, $this->shared_with ?? []);
    }

    /**
     * جلب المشاركات الصالحة فقط
     */
    public function scopeValid($query)
    {
        return $query->where('is_active', true)
                    ->where(function($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    /**
     * جلب المشاركات المنتهية الصلاحية
     */
    public function scopeExpired($query)
    {
        return $query->where(function($q) {
            $q->where('is_active', false)
              ->orWhere(function($q2) {
                  $q2->whereNotNull('expires_at')
                     ->where('expires_at', '<=', now());
              });
        });
    }

    /**
     * إنشاء رمز وصول فريد
     */
    public static function generateAccessToken()
    {
        do {
            $token = \Str::random(60);
        } while (self::where('access_token', $token)->exists());

        return $token;
    }
}
