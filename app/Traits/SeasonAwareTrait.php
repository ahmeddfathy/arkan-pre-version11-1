<?php

namespace App\Traits;

use App\Models\Season;

trait SeasonAwareTrait
{
    /**
     * الحصول على معرف الموسم النشط حالياً
     *
     * @return int|null
     */
    protected function getCurrentSeasonId()
    {
        $currentSeason = Season::getCurrentSeason();
        return $currentSeason ? $currentSeason->id : null;
    }

    /**
     * التحقق مما إذا كان الموسم منتهي
     *
     * @param int $seasonId
     * @return bool
     */
    protected function isExpiredSeason($seasonId)
    {
        if (!$seasonId) {
            return false;
        }

        $season = Season::find($seasonId);

        if (!$season) {
            return false;
        }

        return $season->getIsExpiredAttribute();
    }
}
