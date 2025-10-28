<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Season;
use App\Models\UserSeasonView;

class SeasonIntroController extends Controller
{
    /**
     * عرض مقدمة السيزون الجديد
     */
    public function show(Season $season)
    {
        // التأكد من أن المستخدم مسجل الدخول
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // التحقق من أن السيزون نشط
        if (!$season->is_current && !$season->is_upcoming) {
            return redirect()->route('seasons.current')
                ->with('info', 'هذا السيزون غير متاح حالياً');
        }

        // التحقق من أن المستخدم لم يشاهد السيزون مسبقاً
        if (UserSeasonView::hasUserSeenSeason(Auth::id(), $season->id)) {
            return redirect()->intended(route('dashboard'))
                ->with('info', 'تم عرض مقدمة السيزون مسبقاً');
        }

        return view('seasons.intro', compact('season'));
    }

    /**
     * تسجيل مشاهدة السيزون والمتابعة
     */
    public function markAsSeen(Request $request, Season $season)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'غير مصرح'], 401);
        }

        try {
            // تسجيل المشاهدة
            UserSeasonView::markSeasonAsSeen(Auth::id(), $season->id);

            // إعادة التوجيه للصفحة المطلوبة أو الصفحة الرئيسية
            $redirectTo = $request->input('redirect_to', route('dashboard'));

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'redirect_url' => $redirectTo
                ]);
            }

            return redirect()->to($redirectTo)
                ->with('success', 'مرحباً بك في ' . $season->name . '!');

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'حدث خطأ أثناء المعالجة'], 500);
            }

            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء المعالجة');
        }
    }
}
