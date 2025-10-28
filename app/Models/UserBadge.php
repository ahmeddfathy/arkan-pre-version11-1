<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSecureId;

class UserBadge extends Model
{
    use HasFactory, HasSecureId;

    protected $fillable = [
        'user_id',
        'badge_id',
        'season_id',
        'points_earned',
        'earned_at',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'earned_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * المستخدم الذي حصل على الشارة
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * الشارة التي حصل عليها المستخدم
     */
    public function badge()
    {
        return $this->belongsTo(Badge::class);
    }

    /**
     * الموسم الذي حصل فيه المستخدم على الشارة
     */
    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    /**
     * الحصول على آخر شارة حصل عليها المستخدم في موسم معين
     */
    public static function getLatestBadgeForUserInSeason($userId, $seasonId)
    {
        $userBadge = self::where('user_id', $userId)
                        ->where('season_id', $seasonId)
                        ->where('is_active', true)
                        ->orderByDesc('earned_at')
                        ->first();

        return $userBadge ? $userBadge->badge : null;
    }

    /**
     * الحصول على أعلى شارة حصل عليها المستخدم في موسم معين
     */
    public static function getHighestBadgeForUserInSeason($userId, $seasonId)
    {
        $userBadge = self::join('badges', 'user_badges.badge_id', '=', 'badges.id')
                        ->where('user_id', $userId)
                        ->where('season_id', $seasonId)
                        ->orderByDesc('badges.level')
                        ->select('user_badges.*')
                        ->first();

        return $userBadge ? $userBadge->badge : null;
    }

    /**
     * إلغاء تنشيط كل شارات المستخدم في موسم معين
     */
    public static function deactivateAllUserBadgesInSeason($userId, $seasonId)
    {
        return self::where('user_id', $userId)
                    ->where('season_id', $seasonId)
                    ->update(['is_active' => false]);
    }
}
