<?php

namespace App\Http\Controllers;

use App\Models\ProjectLimit;
use App\Models\User;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProjectLimitController extends Controller
{
    /**
     * تقييد إدارة الحدود على role HR فقط باستخدام hasRole('hr')
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $routeName = optional($request->route())->getName();
            $publicRoutes = ['project-limits.report', 'project-limits.quick-stats'];

            if (!in_array($routeName, $publicRoutes)) {
                $user = auth()->user();
                if (!$user || !$user->hasRole('hr')) {
                    abort(403, 'غير مصرح لك بالوصول لهذه الصفحة');
                }
            }

            return $next($request);
        });
    }
    /**
     * عرض قائمة الحدود الشهرية
     */
    public function index()
    {
        $limits = ProjectLimit::with(['user', 'team'])
            ->orderBy('limit_type')
            ->orderBy('month')
            ->paginate(20);

        return view('project-limits.index', compact('limits'));
    }

    /**
     * عرض صفحة إضافة حد جديد
     */
    public function create()
    {
        $users = User::where('employee_status', 'active')->orderBy('name')->get();
        $teams = Team::orderBy('name')->get();
        $departments = User::getAvailableDepartments();

        return view('project-limits.create', compact('users', 'teams', 'departments'));
    }

    /**
     * حفظ حد جديد
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'limit_type' => 'required|in:company,department,team,user',
            'month' => 'nullable|integer|min:1|max:12',
            'entity_id' => 'nullable|integer',
            'entity_name' => 'nullable|string|max:255',
            'monthly_limit' => 'required|integer|min:0',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // التحقق من عدم التكرار حسب النوع والكيان
        $existsQuery = ProjectLimit::where('limit_type', $request->limit_type)
            ->when(is_null($request->month), fn($q) => $q->whereNull('month'), fn($q) => $q->where('month', $request->month));

        switch ($request->limit_type) {
            case 'company':
                $existsQuery->whereNull('entity_id');
                break;
            case 'department':
                $existsQuery->where('entity_name', $request->entity_name);
                break;
            case 'team':
                $existsQuery->where('entity_id', $request->entity_id);
                break;
            case 'user':
                $existsQuery->where('entity_id', $request->entity_id);
                break;
        }

        $exists = $existsQuery->exists();

        if ($exists) {
            return redirect()->back()
                ->with('error', 'يوجد بالفعل حد محدد لهذا الكيان في هذا الشهر!')
                ->withInput();
        }

        ProjectLimit::create($request->all());

        return redirect()->route('project-limits.index')
            ->with('success', 'تم إضافة الحد الشهري بنجاح!');
    }

    /**
     * عرض صفحة تعديل حد
     */
    public function edit(ProjectLimit $projectLimit)
    {
        $users = User::where('employee_status', 'active')->orderBy('name')->get();
        $teams = Team::orderBy('name')->get();
        $departments = User::getAvailableDepartments();

        return view('project-limits.edit', compact('projectLimit', 'users', 'teams', 'departments'));
    }

    /**
     * تحديث حد موجود
     */
    public function update(Request $request, ProjectLimit $projectLimit)
    {
        $validator = Validator::make($request->all(), [
            'limit_type' => 'required|in:company,department,team,user',
            'month' => 'nullable|integer|min:1|max:12',
            'entity_id' => 'nullable|integer',
            'entity_name' => 'nullable|string|max:255',
            'monthly_limit' => 'required|integer|min:0',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // التحقق من عدم التكرار (مع استثناء السجل الحالي)
        $existsQuery = ProjectLimit::where('limit_type', $request->limit_type)
            ->when(is_null($request->month), fn($q) => $q->whereNull('month'), fn($q) => $q->where('month', $request->month))
            ->where('id', '!=', $projectLimit->id);

        switch ($request->limit_type) {
            case 'company':
                $existsQuery->whereNull('entity_id');
                break;
            case 'department':
                $existsQuery->where('entity_name', $request->entity_name);
                break;
            case 'team':
                $existsQuery->where('entity_id', $request->entity_id);
                break;
            case 'user':
                $existsQuery->where('entity_id', $request->entity_id);
                break;
        }

        $exists = $existsQuery->exists();

        if ($exists) {
            return redirect()->back()
                ->with('error', 'يوجد بالفعل حد محدد لهذا الكيان في هذا الشهر!')
                ->withInput();
        }

        $projectLimit->update($request->all());

        return redirect()->route('project-limits.index')
            ->with('success', 'تم تحديث الحد الشهري بنجاح!');
    }

    /**
     * حذف حد
     */
    public function destroy(ProjectLimit $projectLimit)
    {
        $projectLimit->delete();

        return redirect()->route('project-limits.index')
            ->with('success', 'تم حذف الحد الشهري بنجاح!');
    }

    /**
     * عرض صفحة التقرير الشهري
     */
    public function report(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        $department = $request->input('department');
        $teamId = $request->input('team_id');

        $report = ProjectLimit::getMonthlyReport($month, $year, $department, $teamId);

        // جلب الأقسام والفرق للفلاتر
        $departments = User::getAvailableDepartments();
        $teams = Team::orderBy('name')->get();

        return view('project-limits.report', compact('report', 'month', 'year', 'departments', 'teams'));
    }

    /**
     * API للحصول على إحصائيات سريعة
     */
    public function quickStats(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        $companyStats = ProjectLimit::isLimitExceeded('company', $month, $year);

        return response()->json([
            'success' => true,
            'month' => $month,
            'year' => $year,
            'company_stats' => $companyStats,
        ]);
    }
}
