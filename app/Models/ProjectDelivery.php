<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectDelivery extends Model
{
    use HasFactory;

    protected $table = 'project_deliveries';

    protected $fillable = [
        'project_id',
        'delivery_type',
        'delivery_date',
        'delivered_by',
        'notes',
    ];

    protected $casts = [
        'delivery_date' => 'datetime',
    ];

    /**
     * العلاقة مع المشروع
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * العلاقة مع المستخدم الذي قام بالتسليم
     */
    public function deliveredBy()
    {
        return $this->belongsTo(User::class, 'delivered_by');
    }

    /**
     * Scope للحصول على المسودات فقط
     */
    public function scopeDrafts($query)
    {
        return $query->where('delivery_type', 'مسودة');
    }

    /**
     * Scope للحصول على التسليمات النهائية فقط
     */
    public function scopeFinal($query)
    {
        return $query->where('delivery_type', 'نهائي');
    }

    /**
     * Scope للحصول على تسليمات مشروع معين
     */
    public function scopeForProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * الحصول على أنواع التسليم المتاحة
     */
    public static function getDeliveryTypes(): array
    {
        return [
            'مسودة' => 'مسودة',
            'كامل' => 'كامل (نهائي)',
            'خدمات' => 'خدمات',
            'تعديل_على_الدراسة' => 'تعديل على الدراسة',
            'تقييم_مالي' => 'تقييم مالي',
            'ترجمة' => 'ترجمة',
            'ملخص' => 'ملخص',
            'عروض_اسعار' => 'عروض أسعار',
            'خطة_عمل' => 'خطة عمل',
            'خطة_تشغيل' => 'خطة تشغيل',
            'باور_بوينت' => 'باور بوينت',
        ];
    }

    /**
     * الحصول على أيقونات أنواع التسليم
     */
    public static function getDeliveryTypeIcons(): array
    {
        return [
            'مسودة' => '📄',
            'كامل' => '✅',
            'خدمات' => '🔧',
            'تعديل_على_الدراسة' => '📝',
            'تقييم_مالي' => '💰',
            'ترجمة' => '🌐',
            'ملخص' => '📋',
            'عروض_اسعار' => '💵',
            'خطة_عمل' => '📊',
            'خطة_تشغيل' => '⚙️',
            'باور_بوينت' => '🎯',
        ];
    }

    /**
     * الحصول على ألوان أنواع التسليم
     */
    public static function getDeliveryTypeColors(): array
    {
        return [
            'مسودة' => '#fef3c7',
            'كامل' => '#d1fae5',
            'خدمات' => '#dbeafe',
            'تعديل_على_الدراسة' => '#fce7f3',
            'تقييم_مالي' => '#fef3c7',
            'ترجمة' => '#e0e7ff',
            'ملخص' => '#dbeafe',
            'عروض_اسعار' => '#fef3c7',
            'خطة_عمل' => '#ddd6fe',
            'خطة_تشغيل' => '#e0e7ff',
            'باور_بوينت' => '#fce7f3',
        ];
    }

    /**
     * التحقق من أن التسليم مسودة
     */
    public function isDraft(): bool
    {
        return $this->delivery_type === 'مسودة';
    }

    /**
     * التحقق من أن التسليم نهائي/كامل
     */
    public function isFinal(): bool
    {
        return in_array($this->delivery_type, ['نهائي', 'كامل']);
    }
}

