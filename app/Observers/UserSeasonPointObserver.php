<?php

namespace App\Observers;

use App\Models\UserSeasonPoint;
use App\Services\BadgeService;
use Illuminate\Support\Facades\Log;

class UserSeasonPointObserver
{
    protected $badgeService;

    public function __construct(BadgeService $badgeService)
    {
        $this->badgeService = $badgeService;
    }

    /**
     * Handle the UserSeasonPoint "created" event.
     */
    public function created(UserSeasonPoint $userSeasonPoint)
    {
        $this->updateUserBadge($userSeasonPoint);
    }

    /**
     * Handle the UserSeasonPoint "updated" event.
     */
    public function updated(UserSeasonPoint $userSeasonPoint)
    {
        // التحقق من أن النقاط تغيرت فعلاً
        if ($userSeasonPoint->wasChanged('total_points')) {
            $this->updateUserBadge($userSeasonPoint);
        }
    }

    /**
     * تحديث شارة المستخدم
     */
    private function updateUserBadge(UserSeasonPoint $userSeasonPoint)
    {
        try {
            $user = $userSeasonPoint->user;
            $season = $userSeasonPoint->season;

            if ($user && $season) {
                $result = $this->badgeService->updateUserBadge($user, $season);

                if ($result['success']) {
                    Log::info('Badge automatically updated via Observer', [
                        'user_id' => $user->id,
                        'season_id' => $season->id,
                        'points' => $userSeasonPoint->total_points,
                        'message' => $result['message']
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error updating badge via Observer', [
                'user_id' => $userSeasonPoint->user_id,
                'season_id' => $userSeasonPoint->season_id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
