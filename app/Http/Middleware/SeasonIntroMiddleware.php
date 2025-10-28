<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Season;
use App\Models\UserSeasonView;
use Symfony\Component\HttpFoundation\Response;

class SeasonIntroMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // تحقق من أن المستخدم مسجل الدخول
        if (!Auth::check()) {
            return $next($request);
        }

        // تجنب إعادة التوجيه اللانهائي - استثناء المسارات المهمة
        if ($request->is('season-intro*') ||
            $request->is('api/*') ||
            $request->is('notifications*') ||
            $request->is('logout') ||
            $request->ajax()) {
            return $next($request);
        }

        // الحصول على السيزون النشط الحالي
        $currentSeason = Season::getCurrentSeason();

        if (!$currentSeason) {
            return $next($request);
        }

        $user = Auth::user();

        // التحقق من أن المستخدم لم يشاهد السيزون الحالي
        if (!UserSeasonView::hasUserSeenSeason($user->id, $currentSeason->id)) {
            // إعادة التوجيه إلى صفحة مقدمة السيزون
            return redirect()->route('season.intro', $currentSeason->id);
        }

        return $next($request);
    }
}
