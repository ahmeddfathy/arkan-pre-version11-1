<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSecureId;

class UserSeasonPoint extends Model
{
    use HasFactory, HasSecureId;

    protected $fillable = [
        'user_id',
        'season_id',
        'total_points',
        'current_badge_id',
        'highest_badge_id',
        'previous_season_badge_id',
        'tasks_completed',
        'projects_completed',
        'total_minutes_worked',
    ];

    /**
     * المستخدم صاحب النقاط
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * الموسم الذي تنتمي إليه هذه النقاط
     */
    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    /**
     * الشارة الحالية للمستخدم في هذا الموسم
     */
    public function currentBadge()
    {
        return $this->belongsTo(Badge::class, 'current_badge_id');
    }

    /**
     * أعلى شارة حصل عليها المستخدم في هذا الموسم
     */
    public function highestBadge()
    {
        return $this->belongsTo(Badge::class, 'highest_badge_id');
    }

    /**
     * الشارة التي كان يملكها المستخدم في الموسم السابق
     */
    public function previousSeasonBadge()
    {
        return $this->belongsTo(Badge::class, 'previous_season_badge_id');
    }

    /**
     * إضافة نقاط للمستخدم وتحديث الشارة إذا لزم الأمر
     */
    public function addPoints($points, $tasksCompleted = 0, $projectsCompleted = 0, $minutesWorked = 0)
    {
        // تحديث النقاط
        $this->total_points += $points;
        $this->tasks_completed += $tasksCompleted;
        $this->projects_completed += $projectsCompleted;
        $this->total_minutes_worked += $minutesWorked;

        // الحصول على الشارة المناسبة للنقاط الحالية
        $newBadge = Badge::getBadgeForPoints($this->total_points);

        // إذا كان المستخدم يستحق شارة جديدة (أعلى من الشارة الحالية أو لم يحصل على شارة بعد)
        if ($newBadge && (!$this->current_badge_id || $newBadge->level > $this->currentBadge->level)) {
            // تحديث الشارة الحالية
            $this->current_badge_id = $newBadge->id;

            // تحديث أعلى شارة إذا كانت الشارة الجديدة أعلى
            if (!$this->highest_badge_id || $newBadge->level > $this->highestBadge->level) {
                $this->highest_badge_id = $newBadge->id;
            }

            // إضافة سجل للشارة الجديدة
            UserBadge::create([
                'user_id' => $this->user_id,
                'badge_id' => $newBadge->id,
                'season_id' => $this->season_id,
                'points_earned' => $this->total_points,
                'earned_at' => now(),
                'is_active' => true,
                'notes' => 'تمت الترقية إلى شارة جديدة بعد الوصول إلى ' . $this->total_points . ' نقطة'
            ]);

            // إلغاء تنشيط الشارات القديمة
            UserBadge::where('user_id', $this->user_id)
                     ->where('season_id', $this->season_id)
                     ->where('badge_id', '!=', $newBadge->id)
                     ->update(['is_active' => false]);
        }

        $this->save();

        return $newBadge;
    }

    public static function getOrCreate($userId, $seasonId)
    {
        $userSeasonPoints = self::where('user_id', $userId)
                               ->where('season_id', $seasonId)
                               ->first();

        if (!$userSeasonPoints) {
            $userSeasonPoints = self::create([
                'user_id' => $userId,
                'season_id' => $seasonId,
                'total_points' => 0,
                'tasks_completed' => 0,
                'projects_completed' => 0,
                'total_minutes_worked' => 0,
            ]);
        }

        return $userSeasonPoints;
    }
}
