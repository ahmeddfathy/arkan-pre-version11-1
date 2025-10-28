<?php

namespace App\Http\Controllers;

use App\Models\BadgeDemotionRule;
use App\Models\Badge;
use Illuminate\Http\Request;

class BadgeDemotionRuleController extends Controller
{
    /**
     * عرض قائمة قواعد هبوط الشارات
     */
    public function index()
    {
        $demotionRules = BadgeDemotionRule::with(['fromBadge', 'toBadge'])
                                         ->orderBy('created_at', 'desc')
                                         ->paginate(10);

        return view('badge-demotion-rules.index', compact('demotionRules'));
    }

    /**
     * عرض صفحة إنشاء قاعدة هبوط جديدة
     */
    public function create()
    {
        $badges = Badge::orderBy('level', 'desc')->get();

        return view('badge-demotion-rules.create', compact('badges'));
    }

    /**
     * حفظ قاعدة هبوط جديدة
     */
    public function store(Request $request)
    {
        $request->validate([
            'from_badge_id' => 'required|exists:badges,id',
            'to_badge_id' => 'required|exists:badges,id|different:from_badge_id',
            'demotion_levels' => 'required|integer|min:1',
            'points_percentage_retained' => 'required|integer|min:0|max:100',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
        ]);

        // التحقق من أن الشارة المستهدفة أقل في المستوى من الشارة المصدر
        $fromBadge = Badge::find($request->from_badge_id);
        $toBadge = Badge::find($request->to_badge_id);

        if ($toBadge->level >= $fromBadge->level) {
            return redirect()->back()
                           ->withInput()
                           ->with('error', 'يجب أن تكون الشارة المستهدفة أقل في المستوى من الشارة المصدر');
        }

        // التحقق من عدم وجود قاعدة هبوط مشابهة
        $existingRule = BadgeDemotionRule::where('from_badge_id', $request->from_badge_id)
                                        ->where('to_badge_id', $request->to_badge_id)
                                        ->first();

        if ($existingRule) {
            return redirect()->back()
                           ->withInput()
                           ->with('error', 'توجد قاعدة هبوط مشابهة بالفعل');
        }

        BadgeDemotionRule::create([
            'from_badge_id' => $request->from_badge_id,
            'to_badge_id' => $request->to_badge_id,
            'demotion_levels' => $request->demotion_levels,
            'points_percentage_retained' => $request->points_percentage_retained,
            'is_active' => $request->has('is_active'),
            'description' => $request->description,
        ]);

        return redirect()->route('badge-demotion-rules.index')
                        ->with('success', 'تم إنشاء قاعدة الهبوط بنجاح');
    }

    /**
     * عرض تفاصيل قاعدة الهبوط
     */
    public function show(BadgeDemotionRule $badgeDemotionRule)
    {
        $badgeDemotionRule->load(['fromBadge', 'toBadge']);

        return view('badge-demotion-rules.show', compact('badgeDemotionRule'));
    }

    /**
     * عرض صفحة تعديل قاعدة الهبوط
     */
    public function edit(BadgeDemotionRule $badgeDemotionRule)
    {
        $badges = Badge::orderBy('level', 'desc')->get();

        return view('badge-demotion-rules.edit', compact('badgeDemotionRule', 'badges'));
    }

    /**
     * تحديث قاعدة الهبوط
     */
    public function update(Request $request, BadgeDemotionRule $badgeDemotionRule)
    {
        $request->validate([
            'from_badge_id' => 'required|exists:badges,id',
            'to_badge_id' => 'required|exists:badges,id|different:from_badge_id',
            'demotion_levels' => 'required|integer|min:1',
            'points_percentage_retained' => 'required|integer|min:0|max:100',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
        ]);

        // التحقق من أن الشارة المستهدفة أقل في المستوى من الشارة المصدر
        $fromBadge = Badge::find($request->from_badge_id);
        $toBadge = Badge::find($request->to_badge_id);

        if ($toBadge->level >= $fromBadge->level) {
            return redirect()->back()
                           ->withInput()
                           ->with('error', 'يجب أن تكون الشارة المستهدفة أقل في المستوى من الشارة المصدر');
        }

        // التحقق من عدم وجود قاعدة هبوط مشابهة (باستثناء الحالية)
        $existingRule = BadgeDemotionRule::where('from_badge_id', $request->from_badge_id)
                                        ->where('to_badge_id', $request->to_badge_id)
                                        ->where('id', '!=', $badgeDemotionRule->id)
                                        ->first();

        if ($existingRule) {
            return redirect()->back()
                           ->withInput()
                           ->with('error', 'توجد قاعدة هبوط مشابهة بالفعل');
        }

        $badgeDemotionRule->update([
            'from_badge_id' => $request->from_badge_id,
            'to_badge_id' => $request->to_badge_id,
            'demotion_levels' => $request->demotion_levels,
            'points_percentage_retained' => $request->points_percentage_retained,
            'is_active' => $request->has('is_active'),
            'description' => $request->description,
        ]);

        return redirect()->route('badge-demotion-rules.index')
                        ->with('success', 'تم تحديث قاعدة الهبوط بنجاح');
    }

    /**
     * حذف قاعدة الهبوط
     */
    public function destroy(BadgeDemotionRule $badgeDemotionRule)
    {
        $badgeDemotionRule->delete();

        return redirect()->route('badge-demotion-rules.index')
                        ->with('success', 'تم حذف قاعدة الهبوط بنجاح');
    }

    /**
     * تفعيل/إلغاء تفعيل قاعدة الهبوط
     */
    public function toggleActive(BadgeDemotionRule $badgeDemotionRule)
    {
        $badgeDemotionRule->update(['is_active' => !$badgeDemotionRule->is_active]);

        $status = $badgeDemotionRule->is_active ? 'تم تفعيل' : 'تم إلغاء تفعيل';

        return redirect()->back()
                        ->with('success', $status . ' قاعدة الهبوط بنجاح');
    }

    /**
     * الحصول على القواعد النشطة للهبوط
     */
    public function getActiveRules()
    {
        $activeRules = BadgeDemotionRule::getActiveDemotionRules();

        return response()->json($activeRules);
    }

    /**
     * الحصول على قواعد الهبوط للشارة المحددة
     */
    public function getRulesForBadge(Badge $badge)
    {
        $demotionRules = BadgeDemotionRule::where('from_badge_id', $badge->id)
                                         ->where('is_active', true)
                                         ->with('toBadge')
                                         ->get();

        return response()->json($demotionRules);
    }

    /**
     * معاينة تأثير قاعدة الهبوط
     */
    public function previewDemotion(Request $request)
    {
        $request->validate([
            'from_badge_id' => 'required|exists:badges,id',
            'to_badge_id' => 'required|exists:badges,id',
            'points_percentage_retained' => 'required|integer|min:0|max:100',
            'current_points' => 'required|integer|min:0',
        ]);

        $fromBadge = Badge::find($request->from_badge_id);
        $toBadge = Badge::find($request->to_badge_id);
        $currentPoints = $request->current_points;
        $retainedPercentage = $request->points_percentage_retained;

        $pointsAfterDemotion = floor($currentPoints * ($retainedPercentage / 100));

        return response()->json([
            'from_badge' => $fromBadge,
            'to_badge' => $toBadge,
            'current_points' => $currentPoints,
            'points_after_demotion' => $pointsAfterDemotion,
            'points_lost' => $currentPoints - $pointsAfterDemotion,
            'retained_percentage' => $retainedPercentage,
        ]);
    }
}
