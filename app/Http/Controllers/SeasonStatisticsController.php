<?php

namespace App\Http\Controllers;

use App\Models\Season;
use App\Models\User;
use App\Models\UserSeasonPoint;
use App\Models\UserBadge;
use App\Services\SeasonStatisticsService;
use App\Services\BadgeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SeasonStatisticsController extends Controller
{
    protected $seasonStatisticsService;
    protected $badgeService;

    public function __construct(SeasonStatisticsService $seasonStatisticsService, BadgeService $badgeService)
    {
        $this->seasonStatisticsService = $seasonStatisticsService;
        $this->badgeService = $badgeService;
    }

    /**
     * جلب بيانات النقاط والشارات للمستخدم في موسم معين
     */
    private function getUserPointsAndBadges($userId, $seasonId)
    {
        $user = User::find($userId);
        if (!$user || !$seasonId) {
            return null;
        }

        // جلب نقاط الموسم
        $seasonPoints = UserSeasonPoint::where('user_id', $userId)
            ->where('season_id', $seasonId)
            ->with(['currentBadge', 'highestBadge'])
            ->first();

        // جلب الشارات الحديثة للمستخدم في هذا الموسم
        $recentBadges = UserBadge::where('user_id', $userId)
            ->where('season_id', $seasonId)
            ->with('badge')
            ->orderBy('earned_at', 'desc')
            ->limit(5)
            ->get();

        return [
            'season_points' => $seasonPoints ? [
                'total_points' => $seasonPoints->total_points,
                'tasks_completed' => $seasonPoints->tasks_completed,
                'projects_completed' => $seasonPoints->projects_completed,
                'minutes_worked' => $seasonPoints->total_minutes_worked,
                'current_badge' => $seasonPoints->currentBadge,
                'highest_badge' => $seasonPoints->highestBadge,
            ] : null,
            'recent_badges' => $recentBadges,
            'total_badges_count' => UserBadge::where('user_id', $userId)
                ->where('season_id', $seasonId)
                ->count(),
        ];
    }

    /**
     * عرض صفحة إحصائيات المستخدم الحالي في الموسم
     */
    public function myStatistics(Request $request)
    {
        $seasons = Season::orderBy('start_date', 'desc')->get();
        $currentSeason = Season::getCurrentSeason();

        $selectedSeasonId = $request->input('season_id', $currentSeason ? $currentSeason->id : null);

        if (!$selectedSeasonId) {
            return view('season-statistics.my-statistics', [
                'seasons' => $seasons,
                'currentSeason' => $currentSeason,
                'selectedSeason' => null,
                'statistics' => null
            ]);
        }

        $statistics = $this->seasonStatisticsService->getUserStatistics(Auth::id(), $selectedSeasonId);
        $pointsAndBadges = $this->getUserPointsAndBadges(Auth::id(), $selectedSeasonId);

        return view('season-statistics.my-statistics', [
            'seasons' => $seasons,
            'currentSeason' => $currentSeason,
            'selectedSeason' => Season::find($selectedSeasonId),
            'statistics' => $statistics,
            'pointsAndBadges' => $pointsAndBadges
        ]);
    }

    /**
     * عرض صفحة إحصائيات مستخدم معين في الموسم
     */
    public function userStatistics(Request $request, $userId)
    {
        // التحقق من الصلاحيات (يمكن للمديرين و HR فقط رؤية إحصائيات المستخدمين الآخرين)
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        if (Auth::id() != $userId && !$authUser->hasRole(['admin', 'super-admin', 'hr'])) {
            return redirect()->route('seasons.statistics.my')->with('error', 'ليس لديك صلاحية لعرض إحصائيات هذا المستخدم');
        }

        $user = User::findOrFail($userId);
        $seasons = Season::orderBy('start_date', 'desc')->get();
        $currentSeason = Season::getCurrentSeason();

        $selectedSeasonId = $request->input('season_id', $currentSeason ? $currentSeason->id : null);

        if (!$selectedSeasonId) {
            return view('season-statistics.user-statistics', [
                'user' => $user,
                'seasons' => $seasons,
                'currentSeason' => $currentSeason,
                'selectedSeason' => null,
                'statistics' => null
            ]);
        }

        $statistics = $this->seasonStatisticsService->getUserStatistics($userId, $selectedSeasonId);
        $pointsAndBadges = $this->getUserPointsAndBadges($userId, $selectedSeasonId);

        return view('season-statistics.user-statistics', [
            'user' => $user,
            'seasons' => $seasons,
            'currentSeason' => $currentSeason,
            'selectedSeason' => Season::find($selectedSeasonId),
            'statistics' => $statistics,
            'pointsAndBadges' => $pointsAndBadges
        ]);
    }

    /**
     * عرض صفحة إحصائيات الشركة في الموسم
     */
    public function companyStatistics(Request $request)
    {
        // التحقق من الصلاحيات (يمكن للمديرين و HR فقط رؤية إحصائيات الشركة)
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        if (!$authUser->hasRole(['admin', 'super-admin', 'hr'])) {
            return redirect()->route('seasons.statistics.my')->with('error', 'ليس لديك صلاحية لعرض إحصائيات الشركة');
        }

        $seasons = Season::orderBy('start_date', 'desc')->get();
        $currentSeason = Season::getCurrentSeason();

        $selectedSeasonId = $request->input('season_id', $currentSeason ? $currentSeason->id : null);

        if (!$selectedSeasonId) {
            return view('season-statistics.company-statistics', [
                'seasons' => $seasons,
                'currentSeason' => $currentSeason,
                'selectedSeason' => null,
                'statistics' => null
            ]);
        }

        $statistics = $this->seasonStatisticsService->getCompanyStatistics($selectedSeasonId);
        $selectedSeason = Season::find($selectedSeasonId);

        // إضافة إحصائيات الشارات للشركة
        $badgeStats = $this->badgeService->getBadgeStatistics($selectedSeason);

        return view('season-statistics.company-statistics', [
            'seasons' => $seasons,
            'currentSeason' => $currentSeason,
            'selectedSeason' => $selectedSeason,
            'statistics' => $statistics,
            'badgeStats' => $badgeStats
        ]);
    }

    /**
     * عرض صفحة إحصائيات جميع المستخدمين في الموسم
     */
    public function allUsersStatistics(Request $request)
    {
        // التحقق من الصلاحيات (يمكن للمديرين و HR فقط رؤية إحصائيات جميع المستخدمين)
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        if (!$authUser->hasRole(['admin', 'super-admin', 'hr'])) {
            return redirect()->route('seasons.statistics.my')->with('error', 'ليس لديك صلاحية لعرض إحصائيات جميع المستخدمين');
        }

        $seasons = Season::orderBy('start_date', 'desc')->get();
        $currentSeason = Season::getCurrentSeason();

        $selectedSeasonId = $request->input('season_id', $currentSeason ? $currentSeason->id : null);

        if (!$selectedSeasonId) {
            return view('season-statistics.all-users-statistics', [
                'seasons' => $seasons,
                'currentSeason' => $currentSeason,
                'selectedSeason' => null,
                'statistics' => null
            ]);
        }

        $statistics = $this->seasonStatisticsService->getAllUsersStatistics($selectedSeasonId);

        // إضافة بيانات النقاط والشارات لكل مستخدم
        if (isset($statistics['users']) && is_array($statistics['users'])) {
            foreach ($statistics['users'] as &$userStat) {
                if (isset($userStat['user_id'])) {
                    $pointsAndBadges = $this->getUserPointsAndBadges($userStat['user_id'], $selectedSeasonId);
                    $userStat['points_and_badges'] = $pointsAndBadges;
                }
            }
        }

        return view('season-statistics.all-users-statistics', [
            'seasons' => $seasons,
            'currentSeason' => $currentSeason,
            'selectedSeason' => Season::find($selectedSeasonId),
            'statistics' => $statistics
        ]);
    }

    /**
     * عرض بيانات إحصائيات المستخدم بتنسيق JSON (للاستخدام في API)
     */
    public function getUserStatisticsJson(Request $request, $userId)
    {
        // التحقق من الصلاحيات (يمكن للمديرين و HR فقط رؤية إحصائيات المستخدمين الآخرين)
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        if (Auth::id() != $userId && !$authUser->hasRole(['admin', 'super-admin', 'hr'])) {
            return response()->json(['error' => 'غير مصرح لك بعرض هذه البيانات'], 403);
        }

        $seasonId = $request->input('season_id');
        $statistics = $this->seasonStatisticsService->getUserStatistics($userId, $seasonId);

        return response()->json($statistics);
    }

    /**
     * عرض بيانات إحصائيات الشركة بتنسيق JSON (للاستخدام في API)
     */
    public function getCompanyStatisticsJson(Request $request)
    {
        // التحقق من الصلاحيات (يمكن للمديرين و HR فقط رؤية إحصائيات الشركة)
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        if (!$authUser->hasRole(['admin', 'super-admin', 'hr'])) {
            return response()->json(['error' => 'غير مصرح لك بعرض هذه البيانات'], 403);
        }

        $seasonId = $request->input('season_id');
        $statistics = $this->seasonStatisticsService->getCompanyStatistics($seasonId);

        return response()->json($statistics);
    }
}
