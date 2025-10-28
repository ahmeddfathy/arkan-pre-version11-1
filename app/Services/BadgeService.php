<?php

namespace App\Services;

use App\Models\Badge;
use App\Models\BadgeDemotionRule;
use App\Models\Season;
use App\Models\User;
use App\Models\UserBadge;
use App\Models\UserSeasonPoint;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BadgeService
{
    /**
     * إضافة نقاط لمستخدم في الموسم الحالي
     *
     * @param User
     * @param int
     * @param int
     * @param int
     * @param int
     * @return array
     */
    public function addPointsToUser(User $user, $points, $tasksCompleted = 0, $projectsCompleted = 0, $minutesWorked = 0)
    {
        $season = Season::getCurrentActiveSeason();

        if (!$season) {
            return [
                'success' => false,
                'message' => 'لا يوجد موسم نشط حاليًا',
                'data' => null
            ];
        }

        $newBadge = $user->addPointsForSeason(
            $season->id,
            $points,
            $tasksCompleted,
            $projectsCompleted,
            $minutesWorked
        );

        $userPoints = $user->getSeasonPoints($season->id);

        if ($newBadge && $userPoints->current_badge_id == $newBadge->id) {
            return [
                'success' => true,
                'message' => 'تمت إضافة النقاط وترقية المستخدم إلى شارة جديدة: ' . $newBadge->name,
                'data' => [
                    'badge' => $newBadge,
                    'user_points' => $userPoints->total_points,
                    'is_promotion' => true
                ]
            ];
        }

        return [
            'success' => true,
            'message' => 'تمت إضافة النقاط بنجاح',
            'data' => [
                'user_points' => $userPoints->total_points,
                'is_promotion' => false
            ]
        ];
    }

    /**
     * تحديث شارة المستخدم بناءً على النقاط الحالية
     */
    public function updateUserBadge(User $user, Season $season = null)
    {
        if (!$season) {
            $season = Season::where('is_active', true)->first();
        }

        if (!$season) {
            return ['success' => false, 'message' => 'لا يوجد موسم نشط'];
        }

        // الحصول على نقاط المستخدم في الموسم
        $userSeasonPoint = UserSeasonPoint::where('user_id', $user->id)
                                         ->where('season_id', $season->id)
                                         ->first();

        if (!$userSeasonPoint) {
            return ['success' => false, 'message' => 'لا توجد نقاط للمستخدم في هذا الموسم'];
        }

        $totalPoints = $userSeasonPoint->total_points;

        // العثور على الشارة المناسبة بناءً على النقاط
        $appropriateBadge = Badge::where('required_points', '<=', $totalPoints)
                                ->orderBy('required_points', 'desc')
                                ->first();

        if (!$appropriateBadge) {
            return ['success' => false, 'message' => 'لا توجد شارة مناسبة لهذا العدد من النقاط'];
        }

        // التحقق من الشارة الحالية
        $currentUserBadge = UserBadge::where('user_id', $user->id)
                                    ->where('season_id', $season->id)
                                    ->where('is_active', true)
                                    ->first();

        // إذا كانت الشارة الحالية هي نفس الشارة المناسبة، لا حاجة للتحديث
        if ($currentUserBadge && $currentUserBadge->badge_id == $appropriateBadge->id) {
            return ['success' => true, 'message' => 'المستخدم لديه الشارة المناسبة بالفعل', 'badge' => $appropriateBadge];
        }

        return DB::transaction(function () use ($user, $season, $appropriateBadge, $totalPoints, $currentUserBadge) {
            // إلغاء تفعيل الشارة الحالية
            if ($currentUserBadge) {
                $currentUserBadge->update(['is_active' => false]);
            }

            // إنشاء أو تحديث الشارة الجديدة
            $userBadge = UserBadge::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'badge_id' => $appropriateBadge->id,
                    'season_id' => $season->id,
                ],
                [
                    'points_earned' => $totalPoints,
                    'earned_at' => Carbon::now(),
                    'is_active' => true,
                    'notes' => 'تم منحها تلقائياً بناءً على النقاط المكتسبة'
                ]
            );

            Log::info('Badge updated automatically', [
                'user_id' => $user->id,
                'badge_id' => $appropriateBadge->id,
                'season_id' => $season->id,
                'points' => $totalPoints
            ]);

            return [
                'success' => true,
                'message' => 'تم تحديث الشارة تلقائياً إلى: ' . $appropriateBadge->name,
                'badge' => $appropriateBadge,
                'user_badge' => $userBadge
            ];
        });
    }

    /**
     * إضافة نقاط للمستخدم وتحديث الشارة تلقائياً
     */
    public function addPointsAndUpdateBadge(User $user, int $points, Season $season = null, array $details = [])
    {
        if (!$season) {
            $season = Season::where('is_active', true)->first();
        }

        if (!$season) {
            return ['success' => false, 'message' => 'لا يوجد موسم نشط'];
        }

        return DB::transaction(function () use ($user, $points, $season, $details) {
            // تحديث أو إنشاء نقاط المستخدم
            $userSeasonPoint = UserSeasonPoint::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'season_id' => $season->id,
                ],
                [
                    'total_points' => DB::raw("total_points + {$points}"),
                    'tasks_completed' => DB::raw("tasks_completed + " . ($details['tasks_completed'] ?? 0)),
                    'projects_completed' => DB::raw("projects_completed + " . ($details['projects_completed'] ?? 0)),
                    'minutes_worked' => DB::raw("minutes_worked + " . ($details['minutes_worked'] ?? 0)),
                ]
            );

            // إعادة تحميل البيانات للحصول على القيم المحدثة
            $userSeasonPoint->refresh();

            // تحديث الشارة تلقائياً
            $badgeResult = $this->updateUserBadge($user, $season);

            return [
                'success' => true,
                'message' => "تم إضافة {$points} نقطة. " . ($badgeResult['message'] ?? ''),
                'total_points' => $userSeasonPoint->total_points,
                'badge_updated' => $badgeResult['success'] ?? false,
                'new_badge' => $badgeResult['badge'] ?? null
            ];
        });
    }

    /**
     * تحديث شارات جميع المستخدمين في موسم معين
     */
    public function updateAllUserBadges(Season $season = null)
    {
        if (!$season) {
            $season = Season::where('is_active', true)->first();
        }

        if (!$season) {
            return ['success' => false, 'message' => 'لا يوجد موسم نشط'];
        }

        $userSeasonPoints = UserSeasonPoint::where('season_id', $season->id)->get();
        $updatedUsers = 0;
        $errors = [];

        foreach ($userSeasonPoints as $userSeasonPoint) {
            try {
                $result = $this->updateUserBadge($userSeasonPoint->user, $season);
                if ($result['success']) {
                    $updatedUsers++;
                }
            } catch (\Exception $e) {
                $errors[] = "خطأ في تحديث شارة المستخدم {$userSeasonPoint->user->name}: " . $e->getMessage();
                Log::error('Error updating user badge', [
                    'user_id' => $userSeasonPoint->user_id,
                    'season_id' => $season->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'success' => true,
            'message' => "تم تحديث شارات {$updatedUsers} مستخدم",
            'updated_users' => $updatedUsers,
            'errors' => $errors
        ];
    }

    /**
     * التحقق من استحقاق المستخدم لشارة معينة
     */
    public function checkBadgeEligibility(User $user, Badge $badge, Season $season = null)
    {
        if (!$season) {
            $season = Season::where('is_active', true)->first();
        }

        if (!$season) {
            return false;
        }

        $userSeasonPoint = UserSeasonPoint::where('user_id', $user->id)
                                         ->where('season_id', $season->id)
                                         ->first();

        if (!$userSeasonPoint) {
            return false;
        }

        return $userSeasonPoint->total_points >= $badge->required_points;
    }

    /**
     * الحصول على الشارة التالية للمستخدم
     */
    public function getNextBadge(User $user, Season $season = null)
    {
        if (!$season) {
            $season = Season::where('is_active', true)->first();
        }

        if (!$season) {
            return null;
        }

        $userSeasonPoint = UserSeasonPoint::where('user_id', $user->id)
                                         ->where('season_id', $season->id)
                                         ->first();

        if (!$userSeasonPoint) {
            return Badge::orderBy('required_points', 'asc')->first();
        }

        return Badge::where('required_points', '>', $userSeasonPoint->total_points)
                   ->orderBy('required_points', 'asc')
                   ->first();
    }

    /**
     * الحصول على عدد النقاط المطلوبة للشارة التالية
     */
    public function getPointsNeededForNextBadge(User $user, Season $season = null)
    {
        $nextBadge = $this->getNextBadge($user, $season);

        if (!$nextBadge) {
            return 0; // المستخدم وصل لأعلى شارة
        }

        if (!$season) {
            $season = Season::where('is_active', true)->first();
        }

        $userSeasonPoint = UserSeasonPoint::where('user_id', $user->id)
                                         ->where('season_id', $season->id)
                                         ->first();

        $currentPoints = $userSeasonPoint ? $userSeasonPoint->total_points : 0;

        return max(0, $nextBadge->required_points - $currentPoints);
    }

    /**
     * تطبيق قواعد الهبوط على جميع المستخدمين عند بداية موسم جديد
     *
     * @param Season $oldSeason الموسم القديم
     * @param Season $newSeason الموسم الجديد
     * @return array إحصائيات عن عدد المستخدمين الذين تم تخفيض رتبتهم
     */
    public function applyDemotionRules(Season $oldSeason, Season $newSeason)
    {
        DB::beginTransaction();

        try {
            // تطبيق قواعد الهبوط
            $stats = $oldSeason->applyDemotionRules($newSeason);

            DB::commit();

            return [
                'success' => true,
                'message' => 'تم تطبيق قواعد الهبوط بنجاح',
                'data' => $stats
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('فشل في تطبيق قواعد الهبوط: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء تطبيق قواعد الهبوط: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * الحصول على إحصائيات الشارات لموسم معين
     *
     * @param Season $season الموسم
     * @return array إحصائيات الشارات
     */
    public function getBadgeStatistics(Season $season)
    {
        $stats = [
            'total_users_with_badges' => 0,
            'badges_distribution' => [],
            'badges_by_department' => [],
            'top_users' => []
        ];

        // إحصائيات المستخدمين الذين لديهم نقاط
        $userPointsCount = UserSeasonPoint::where('season_id', $season->id)
            ->count();

        $stats['total_users_with_badges'] = $userPointsCount;

        // إحصائيات الشارات حسب القسم - تم تعطيل هذه الوظيفة مؤقتًا
        $stats['badges_by_department'] = [];

        // أعلى 10 مستخدمين من حيث النقاط
        $topUsers = UserSeasonPoint::where('season_id', $season->id)
            ->join('users', 'user_season_points.user_id', '=', 'users.id')
            ->select(
                'users.id',
                'users.name',
                'users.email',
                'users.profile_photo_path',
                'user_season_points.total_points',
                'user_season_points.tasks_completed',
                'user_season_points.projects_completed',
                'user_season_points.total_minutes_worked'
            )
            ->orderBy('user_season_points.total_points', 'desc')
            ->limit(10)
            ->get();

        $stats['top_users'] = $topUsers;

        return $stats;
    }

    /**
     * إنشاء قاعدة هبوط جديدة أو تحديثها
     *
     * @param int $fromBadgeId معرف الشارة العليا
     * @param int $toBadgeId معرف الشارة المخفضة
     * @param int $demotionLevels عدد المستويات التي سيتم تخفيضها
     * @param int $pointsPercentageRetained النسبة المئوية للنقاط المحتفظ بها
     * @param bool $isActive حالة تنشيط القاعدة
     * @param string|null $description وصف القاعدة
     * @return array
     */
    public function createOrUpdateDemotionRule(
        $fromBadgeId,
        $toBadgeId,
        $demotionLevels = 1,
        $pointsPercentageRetained = 50,
        $isActive = true,
        $description = null
    ) {
        $fromBadge = Badge::find($fromBadgeId);
        $toBadge = Badge::find($toBadgeId);

        if (!$fromBadge || !$toBadge) {
            return [
                'success' => false,
                'message' => 'الشارة غير موجودة',
                'data' => null
            ];
        }

        if ($fromBadge->level <= $toBadge->level) {
            return [
                'success' => false,
                'message' => 'لا يمكن أن يكون مستوى الشارة المخفضة أعلى من أو مساوٍ لمستوى الشارة العليا',
                'data' => null
            ];
        }

        try {
            $rule = BadgeDemotionRule::updateOrCreate(
                [
                    'from_badge_id' => $fromBadgeId,
                    'to_badge_id' => $toBadgeId
                ],
                [
                    'demotion_levels' => $demotionLevels,
                    'points_percentage_retained' => $pointsPercentageRetained,
                    'is_active' => $isActive,
                    'description' => $description
                ]
            );

            return [
                'success' => true,
                'message' => 'تم إنشاء/تحديث قاعدة الهبوط بنجاح',
                'data' => $rule
            ];
        } catch (\Exception $e) {
            Log::error('فشل في إنشاء/تحديث قاعدة الهبوط: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء/تحديث قاعدة الهبوط: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
}
