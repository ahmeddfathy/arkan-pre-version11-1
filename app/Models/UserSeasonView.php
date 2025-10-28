<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasSecureId;

class UserSeasonView extends Model
{
    use HasFactory, HasSecureId;

    protected $fillable = [
        'user_id',
        'season_id',
        'viewed_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    /**
     * العلاقة مع المستخدم
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * العلاقة مع السيزون
     */
    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    /**
     * التحقق من مشاهدة المستخدم للسيزون
     */
    public static function hasUserSeenSeason($userId, $seasonId)
    {
        return self::where('user_id', $userId)
                   ->where('season_id', $seasonId)
                   ->exists();
    }

    /**
     * تسجيل مشاهدة السيزون
     */
    public static function markSeasonAsSeen($userId, $seasonId)
    {
        return self::firstOrCreate([
            'user_id' => $userId,
            'season_id' => $seasonId,
        ], [
            'viewed_at' => now(),
        ]);
    }
}
