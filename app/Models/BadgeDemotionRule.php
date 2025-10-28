<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\HasSecureId;

class BadgeDemotionRule extends Model
{
    use HasFactory, HasSecureId, LogsActivity;

    protected $fillable = [
        'from_badge_id',
        'to_badge_id',
        'demotion_levels',
        'points_percentage_retained',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'points_percentage_retained' => 'integer',
        'demotion_levels' => 'integer',
    ];

    /**
     * Configure Activity Log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'from_badge_id', 'to_badge_id', 'demotion_levels',
                'points_percentage_retained', 'is_active', 'description'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'تم إنشاء قاعدة هبوط شارة جديدة',
                'updated' => 'تم تحديث قاعدة هبوط الشارة',
                'deleted' => 'تم حذف قاعدة هبوط الشارة',
                default => $eventName
            });
    }

    /**
     * الشارة التي سيتم الهبوط منها
     */
    public function fromBadge()
    {
        return $this->belongsTo(Badge::class, 'from_badge_id');
    }

    /**
     * الشارة التي سيتم الهبوط إليها
     */
    public function toBadge()
    {
        return $this->belongsTo(Badge::class, 'to_badge_id');
    }

    /**
     * الحصول على قاعدة الهبوط من شارة محددة إلى شارة محددة
     */
    public static function getRuleForBadges($fromBadgeId, $toBadgeId)
    {
        return self::where('from_badge_id', $fromBadgeId)
                   ->where('to_badge_id', $toBadgeId)
                   ->where('is_active', true)
                   ->first();
    }

    /**
     * الحصول على القواعد النشطة للهبوط
     */
    public static function getActiveDemotionRules()
    {
        return self::where('is_active', true)
                   ->with(['fromBadge', 'toBadge'])
                   ->get();
    }
}
