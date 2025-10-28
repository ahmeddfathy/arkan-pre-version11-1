<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\HasSecureId;

class Badge extends Model
{
    use HasFactory, HasSecureId, LogsActivity;

    protected $fillable = [
        'name',
        'description',
        'icon',
        'color_code',
        'required_points',
        'level',
    ];

    /**
     * Configure Activity Log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'description', 'icon', 'color_code', 'required_points', 'level'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'تم إنشاء شارة جديدة',
                'updated' => 'تم تحديث الشارة',
                'deleted' => 'تم حذف الشارة',
                default => $eventName
            });
    }

    /**
     * المستخدمين الذين حصلوا على هذه الشارة
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_badges')
                    ->withPivot(['season_id', 'points_earned', 'earned_at', 'is_active', 'notes'])
                    ->withTimestamps();
    }

    /**
     * مواسم المستخدمين التي يكون لديهم فيها هذه الشارة حاليًا
     */
    public function userSeasons()
    {
        return $this->hasMany(UserSeasonPoint::class, 'current_badge_id');
    }

    /**
     * قواعد الهبوط من هذه الشارة
     */
    public function demotionRules()
    {
        return $this->hasMany(BadgeDemotionRule::class, 'from_badge_id');
    }

    /**
     * الشارات التي يمكن الهبوط إليها من هذه الشارة
     */
    public function demotesToBadges()
    {
        return $this->belongsToMany(Badge::class, 'badge_demotion_rules', 'from_badge_id', 'to_badge_id')
                    ->withPivot(['demotion_levels', 'points_percentage_retained', 'is_active', 'description'])
                    ->withTimestamps();
    }

    /**
     * الشارات التي يمكن أن تهبط منها إلى هذه الشارة
     */
    public function demotesFromBadges()
    {
        return $this->belongsToMany(Badge::class, 'badge_demotion_rules', 'to_badge_id', 'from_badge_id')
                    ->withPivot(['demotion_levels', 'points_percentage_retained', 'is_active', 'description'])
                    ->withTimestamps();
    }

    /**
     * الحصول على قاعدة الهبوط إلى شارة معينة
     */
    public function getDemotionRuleTo(Badge $toBadge)
    {
        return BadgeDemotionRule::where('from_badge_id', $this->id)
                                ->where('to_badge_id', $toBadge->id)
                                ->where('is_active', true)
                                ->first();
    }

    /**
     * الحصول على الشارة التي يجب الهبوط إليها بناءً على قواعد الهبوط
     */
    public function getNextLowerBadge()
    {
        return Badge::whereIn('id', function($query) {
                    $query->select('to_badge_id')
                          ->from('badge_demotion_rules')
                          ->where('from_badge_id', $this->id)
                          ->where('is_active', true);
                })
                ->orderByDesc('level')
                ->first();
    }

    /**
     * الحصول على نسبة النقاط التي سيتم الاحتفاظ بها عند الهبوط إلى الشارة المحددة
     */
    public function getPointsPercentageRetainedFor(Badge $toBadge)
    {
        $rule = $this->getDemotionRuleTo($toBadge);
        return $rule ? $rule->points_percentage_retained : 0;
    }

    /**
     * الحصول على الشارة بناءً على عدد النقاط
     */
    public static function getBadgeForPoints($points)
    {
        return self::where('required_points', '<=', $points)
                   ->orderByDesc('required_points')
                   ->first();
    }
}
