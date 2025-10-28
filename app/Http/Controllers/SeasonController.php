<?php

namespace App\Http\Controllers;

use App\Models\Season;
use App\Services\Auth\RoleCheckService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class SeasonController extends Controller
{
    protected $roleCheckService;

    public function __construct(RoleCheckService $roleCheckService)
    {
        $this->middleware(['auth']);
        $this->roleCheckService = $roleCheckService;
    }

    /**
     * عرض قائمة المواسم
     */
    public function index()
    {
        // التحقق من الصلاحيات - فقط المدراء والإدارة العليا وقسم الموارد البشرية
        $roles = ['admin', 'super-admin', 'hr'];
        if (!$this->roleCheckService->userHasRole($roles)) {
            return redirect()->route('seasons.current')
                ->with('error', 'ليس لديك صلاحية لعرض قائمة المواسم');
        }

        $seasons = Season::orderBy('start_date', 'desc')->get();
        return view('seasons.index', compact('seasons'));
    }

    /**
     * عرض السيزون الحالي للموظفين
     */
    public function current()
    {
        $currentSeason = Season::getCurrentSeason();
        $upcomingSeason = Season::getUpcomingSeason();
        $pastSeasons = Season::where('end_date', '<', Carbon::now())
            ->where('is_active', true)
            ->orderBy('end_date', 'desc')
            ->limit(3)
            ->get();

        return view('seasons.current', compact('currentSeason', 'upcomingSeason', 'pastSeasons'));
    }

    /**
     * عرض نموذج إنشاء سيزون جديد
     */
    public function create()
    {
        // التحقق من الصلاحيات
        $roles = ['admin', 'super-admin', 'hr'];
        if (!$this->roleCheckService->userHasRole($roles)) {
            return redirect()->route('seasons.current')
                ->with('error', 'ليس لديك صلاحية لإنشاء سيزون جديد');
        }

        return view('seasons.create');
    }

    /**
     * حفظ سيزون جديد
     */
    public function store(Request $request)
    {
        // التحقق من الصلاحيات
        $roles = ['admin', 'super-admin', 'hr'];
        if (!$this->roleCheckService->userHasRole($roles)) {
            return redirect()->route('seasons.current')
                ->with('error', 'ليس لديك صلاحية لإنشاء سيزون جديد');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'banner_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'color_theme' => 'nullable|string|max:7',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_active' => 'nullable|boolean',
            'rewards' => 'nullable|array',
        ]);

        $seasonData = $request->except(['image', 'banner_image']);

        // معالجة الصور
        if ($request->hasFile('image')) {
            $seasonData['image'] = $request->file('image')->store('seasons/images', 'public');
        }

        if ($request->hasFile('banner_image')) {
            $seasonData['banner_image'] = $request->file('banner_image')->store('seasons/banners', 'public');
        }

        // تعيين القيمة الافتراضية لـ is_active
        $seasonData['is_active'] = $request->has('is_active');

        Season::create($seasonData);

        return redirect()->route('seasons.index')
            ->with('success', 'تم إنشاء السيزون بنجاح');
    }

    /**
     * عرض تفاصيل سيزون محدد
     */
    public function show(Season $season)
    {
        return view('seasons.show', compact('season'));
    }

    /**
     * عرض نموذج تعديل سيزون
     */
    public function edit(Season $season)
    {
        // التحقق من الصلاحيات
        $roles = ['admin', 'super-admin', 'hr'];
        if (!$this->roleCheckService->userHasRole($roles)) {
            return redirect()->route('seasons.current')
                ->with('error', 'ليس لديك صلاحية لتعديل السيزون');
        }

        return view('seasons.edit', compact('season'));
    }

    /**
     * تحديث سيزون محدد
     */
    public function update(Request $request, Season $season)
    {
        // التحقق من الصلاحيات
        $roles = ['admin', 'super-admin', 'hr'];
        if (!$this->roleCheckService->userHasRole($roles)) {
            return redirect()->route('seasons.current')
                ->with('error', 'ليس لديك صلاحية لتعديل السيزون');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'banner_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'color_theme' => 'nullable|string|max:7',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_active' => 'nullable|boolean',
            'rewards' => 'nullable|array',
        ]);

        $seasonData = $request->except(['image', 'banner_image']);

        // معالجة الصور
        if ($request->hasFile('image')) {
            // حذف الصورة القديمة إذا وجدت
            if ($season->image) {
                Storage::disk('public')->delete($season->image);
            }
            $seasonData['image'] = $request->file('image')->store('seasons/images', 'public');
        }

        if ($request->hasFile('banner_image')) {
            // حذف الصورة القديمة إذا وجدت
            if ($season->banner_image) {
                Storage::disk('public')->delete($season->banner_image);
            }
            $seasonData['banner_image'] = $request->file('banner_image')->store('seasons/banners', 'public');
        }

        // تعيين القيمة الافتراضية لـ is_active
        $seasonData['is_active'] = $request->has('is_active');

        $season->update($seasonData);

        return redirect()->route('seasons.index')
            ->with('success', 'تم تحديث السيزون بنجاح');
    }

    /**
     * حذف سيزون محدد
     */
    public function destroy(Season $season)
    {
        // التحقق من الصلاحيات
        $roles = ['admin', 'super-admin'];
        if (!$this->roleCheckService->userHasRole($roles)) {
            return redirect()->route('seasons.index')
                ->with('error', 'ليس لديك صلاحية لحذف السيزون');
        }

        // حذف الصور
        if ($season->image) {
            Storage::disk('public')->delete($season->image);
        }

        if ($season->banner_image) {
            Storage::disk('public')->delete($season->banner_image);
        }

        $season->delete();

        return redirect()->route('seasons.index')
            ->with('success', 'تم حذف السيزون بنجاح');
    }
}
