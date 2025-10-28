<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\HasSecureId;
use Illuminate\Support\Facades\Auth;

class AttachmentConfirmation extends Model
{
    use HasFactory, HasSecureId, LogsActivity;

    protected $fillable = [
        'attachment_id',
        'project_id',
        'requested_by',
        'manager_id',
        'status',
        'notes',
        'file_type',
        'confirmed_by',
        'confirmed_at',
    ];

    /**
     * أنواع الملفات المتاحة لتأكيد المرفقات
     */
    public static function getFileTypes(): array
    {
        return [
            'مسودة',
            'تعديلات',
            'نهائي',
            'دراسة مصغرة',
            'ريف',
            'ترجمة',
            'ملخص',
            'عروض أسعار',
            'بروبوزيت',
            '2d',
            '3d',
            'مسودة نهائي',
            'دراسة مصغرة او ريف او نها',
            'تسويقي فقط',
            'تقييم مالي',
            'تقرير افضل فرصة او مدينة',
            'فورم',
            'خطة عمل',
            'تقرير',
            'تعديل باوربوينت',
            'تغيير ورق',
            'جهات تمويل',
            'شيت اكسيل',
            'اشتراطات',
            'تقرير افضل حي',
            'ترجمة مرفقات',
            'خطة توسع',
            'تعديل علي دراسة',
            'بحث علمي',
            'موردين',
            'تعديل ع الترجمة',
            'خطة داخلية',
            'دراسة فنية',
            'دراسة فنية ومالية',
            'خطة تسويقيه',
            'تحديد افضل سيناريو',
            'خطة تشغيل',
            'نهائي الخطة',
            'خطة تنفيذية',
        ];
    }

    protected $casts = [
        'confirmed_at' => 'datetime',
    ];

    /**
     * Configure Activity Log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'attachment_id', 'project_id', 'requested_by', 'manager_id',
                'status', 'notes', 'file_type', 'confirmed_at'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'تم إنشاء طلب تأكيد مرفق',
                'updated' => 'تم تحديث طلب تأكيد المرفق',
                'deleted' => 'تم حذف طلب تأكيد المرفق',
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
     * العلاقة مع المشروع
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * العلاقة مع الشخص الذي طلب التأكيد
     */
    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * العلاقة مع المسؤول
     */
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * العلاقة مع الشخص الذي قام بالتأكيد/الرفض
     */
    public function confirmedBy()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    /**
     * Scope للطلبات المعلقة
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope للطلبات المؤكدة
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    /**
     * Scope للطلبات المرفوضة
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope للطلبات الخاصة بمسؤول معين
     */
    public function scopeForManager($query, $managerId)
    {
        return $query->where('manager_id', $managerId);
    }

    /**
     * Scope للطلبات الخاصة بمشروع معين
     */
    public function scopeForProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * تأكيد الطلب
     */
    public function confirm($notes = null, $confirmedBy = null)
    {
        $this->update([
            'status' => 'confirmed',
            'notes' => $notes,
            'confirmed_by' => $confirmedBy ?? Auth::id(),
            'confirmed_at' => now(),
        ]);
    }

    /**
     * رفض الطلب
     */
    public function reject($notes = null, $confirmedBy = null)
    {
        $this->update([
            'status' => 'rejected',
            'notes' => $notes,
            'confirmed_by' => $confirmedBy ?? Auth::id(),
            'confirmed_at' => now(),
        ]);
    }

    /**
     * التحقق من حالة الطلب
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isConfirmed()
    {
        return $this->status === 'confirmed';
    }

    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    /**
     * Scope للطلبات الخاصة بمستخدم معين
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('requested_by', $userId);
    }

    /**
     * Scope للطلبات بحالة معينة
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }
}
