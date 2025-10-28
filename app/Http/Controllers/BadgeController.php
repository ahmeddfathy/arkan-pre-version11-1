<?php

namespace App\Http\Controllers;

use App\Models\Badge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BadgeController extends Controller
{
    /**
     * عرض قائمة الشارات
     */
    public function index()
    {
        $badges = Badge::orderBy('level', 'desc')->paginate(10);

        return view('badges.index', compact('badges'));
    }

    /**
     * عرض صفحة إنشاء شارة جديدة
     */
    public function create()
    {
        return view('badges.create');
    }

    /**
     * حفظ شارة جديدة
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'color_code' => 'nullable|string|max:7',
            'required_points' => 'required|integer|min:0',
            'level' => 'required|integer|min:1',
        ]);

        $data = $request->except('icon');

        // معالجة رفع الأيقونة
        if ($request->hasFile('icon')) {
            $iconPath = $request->file('icon')->store('badges', 'public');
            $data['icon'] = $iconPath;
        }

        Badge::create($data);

        return redirect()->route('badges.index')
                        ->with('success', 'تم إنشاء الشارة بنجاح');
    }

    /**
     * عرض تفاصيل شارة
     */
    public function show(Badge $badge)
    {
        $badge->load(['users', 'demotionRules.toBadge']);

        return view('badges.show', compact('badge'));
    }

    /**
     * عرض صفحة تعديل الشارة
     */
    public function edit(Badge $badge)
    {
        return view('badges.edit', compact('badge'));
    }

    /**
     * تحديث الشارة
     */
    public function update(Request $request, Badge $badge)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'color_code' => 'nullable|string|max:7',
            'required_points' => 'required|integer|min:0',
            'level' => 'required|integer|min:1',
        ]);

        $data = $request->except('icon');

        // معالجة رفع الأيقونة الجديدة
        if ($request->hasFile('icon')) {
            // حذف الأيقونة القديمة
            if ($badge->icon && Storage::disk('public')->exists($badge->icon)) {
                Storage::disk('public')->delete($badge->icon);
            }

            $iconPath = $request->file('icon')->store('badges', 'public');
            $data['icon'] = $iconPath;
        }

        $badge->update($data);

        return redirect()->route('badges.index')
                        ->with('success', 'تم تحديث الشارة بنجاح');
    }

    /**
     * حذف الشارة
     */
    public function destroy(Badge $badge)
    {
        try {
            // التحقق من وجود مستخدمين لديهم هذه الشارة
            if ($badge->users()->count() > 0) {
                return redirect()->route('badges.index')
                                ->with('error', 'لا يمكن حذف الشارة لأن هناك مستخدمين لديهم هذه الشارة');
            }

            // حذف الأيقونة من التخزين
            if ($badge->icon && Storage::disk('public')->exists($badge->icon)) {
                Storage::disk('public')->delete($badge->icon);
            }

            $badge->delete();

            return redirect()->route('badges.index')
                            ->with('success', 'تم حذف الشارة بنجاح');
        } catch (\Exception $e) {
            return redirect()->route('badges.index')
                            ->with('error', 'حدث خطأ أثناء حذف الشارة');
        }
    }
}
