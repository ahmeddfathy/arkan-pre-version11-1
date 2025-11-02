<?php

namespace App\Http\Controllers\Kpi;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\EvaluationCriteria;
use App\Models\RoleEvaluationMapping;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;


class EvaluationCriteriaController extends Controller
{
    /**
     * تقييد إدارة بنود التقييم على HR و project_manager و company_manager فقط
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            if (!$user || !$user->hasAnyRole(['hr', 'project_manager', 'company_manager'])) {
                abort(403, 'غير مصرح لك بالوصول إلى إدارة بنود التقييم. يجب أن تكون لديك صلاحية HR أو Project Manager أو Company Manager.');
            }
            return $next($request);
        });
    }

    /**
     * عرض قائمة البنود
     */
    public function index(Request $request)
    {
        // الحصول على الأدوار مع عدد البنود لكل دور (استبعاد employee)
        $rolesQuery = Role::where('name', '!=', 'employee');

        // فلترة حسب الدور
        if ($request->filled('role_id')) {
            $rolesQuery->where('id', $request->role_id);
        }

        $roles = $rolesQuery->get();

        // الحصول على عدد البنود لكل دور
        $roleCriteriaCounts = EvaluationCriteria::select('role_id')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('role_id')
            ->pluck('count', 'role_id')
            ->toArray();

        // إضافة عدد البنود لكل دور
        $roles = $roles->map(function ($role) use ($roleCriteriaCounts) {
            $role->criteria_count = $roleCriteriaCounts[$role->id] ?? 0;
            return $role;
        });

        // الحصول على البنود مع التفاصيل للعرض
        $criteriaQuery = EvaluationCriteria::with('role')->orderBy('id'); // ترتيب حسب ID بدلاً من sort_order

        // فلترة حسب الدور
        if ($request->filled('role_id')) {
            $criteriaQuery->forRole($request->role_id);
        }

        // فلترة حسب نوع البند
        if ($request->filled('criteria_type')) {
            $criteriaQuery->where('criteria_type', $request->criteria_type);
        }

        // فلترة حسب فترة التقييم
        if ($request->filled('evaluation_period')) {
            $criteriaQuery->where('evaluation_period', $request->evaluation_period);
        }

        $criteria = $criteriaQuery->get();

        return view('kpi.evaluation-criteria.index', compact('criteria', 'roles'));
    }

    /**
     * عرض صفحة اختيار الدور لإضافة بنود التقييم
     */
    public function selectRole(Request $request)
    {
        // الحصول على معرفات الأدوار حسب القسم إذا كان محدداً
        if ($request->filled('department')) {
            $roleIds = \App\Models\DepartmentRole::where('department_name', $request->department)
                ->pluck('role_id')
                ->toArray();

            $roles = Role::whereIn('id', $roleIds)
                ->where('name', '!=', 'employee') // استبعاد دور employee
                ->get();
        } else {
            $roles = Role::where('name', '!=', 'employee')->get(); // استبعاد دور employee
        }

        // إضافة معلومات الأقسام لكل دور
        $roles = $roles->map(function ($role) {
            $departmentRoles = \App\Models\DepartmentRole::where('role_id', $role->id)
                ->get();

            $role->departments = $departmentRoles->pluck('department_name')->unique()->values();
            $role->department = $departmentRoles->pluck('department_name')->first(); // القسم الأول للعرض

            return $role;
        });

        // الحصول على الأقسام المتاحة من جدول department_roles
        $departments = \App\Models\DepartmentRole::distinct()
            ->pluck('department_name')
            ->filter()
            ->sort()
            ->values();

        // الحصول على عدد البنود لكل دور
        $roleCriteriaCounts = EvaluationCriteria::select('role_id')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('role_id')
            ->pluck('count', 'role_id')
            ->toArray();

        return view('kpi.evaluation-criteria.select-role', compact('roles', 'departments', 'roleCriteriaCounts'));
    }

    /**
     * عرض نموذج إضافة بند جديد
     */
    public function create(Request $request)
    {
        // التحقق من أن الدور المحدد ليس employee
        if ($request->filled('role_id')) {
            $selectedRole = Role::find($request->role_id);
            if ($selectedRole && $selectedRole->name === 'employee') {
                return redirect()->route('evaluation-criteria.select-role')
                    ->with('error', 'لا يمكن إضافة بنود تقييم لدور Employee');
            }
        }

        $roles = Role::where('name', '!=', 'employee')->get(); // استبعاد دور employee
        $criteriaTypes = [
            'positive' => 'بند إيجابي',
            'negative' => 'خصم/سالب',
            'bonus' => 'بونص إضافي',
            'development' => 'بند تطويري'
        ];

        $evaluationPeriods = [
            'monthly' => 'شهري',
            'bi_weekly' => 'نصف شهري'
        ];

        $criteriaCategories = [
            'بنود إيجابية' => 'بنود إيجابية',
            'بنود سلبية' => 'بنود سلبية',
            'بنود تطويرية' => 'بنود تطويرية',
            'بونص' => 'بونص'
        ];

        $selectedRole = null;
        if ($request->filled('role_id')) {
            $selectedRole = Role::where('name', '!=', 'employee')->find($request->role_id);
        }

        return view('kpi.evaluation-criteria.create', compact('roles', 'criteriaTypes', 'evaluationPeriods', 'criteriaCategories', 'selectedRole'));
    }

    /**
     * حفظ بند جديد
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'role_id' => 'required|exists:roles,id',
            'criteria_name' => 'required|string|max:255',
            'criteria_description' => 'nullable|string',
            'max_points' => 'required|integer|min:0|max:1000',
            'criteria_type' => 'required|in:positive,negative,bonus',
            'category' => 'nullable|string|max:100',
            'is_active' => 'boolean',
            'evaluate_per_project' => 'boolean',
            'evaluation_period' => 'required|in:monthly,bi_weekly',
        ]);

        // التحقق من أن الدور ليس employee
        $role = Role::find($validated['role_id']);
        if ($role && $role->name === 'employee') {
            return back()->withErrors(['role_id' => 'لا يمكن إضافة بنود تقييم لدور Employee'])->withInput();
        }

        $validated['is_active'] = $request->has('is_active');
        $validated['evaluate_per_project'] = $request->has('evaluate_per_project');

        // حساب الترتيب التلقائي - آخر ترتيب + 1
        $lastOrder = EvaluationCriteria::where('role_id', $validated['role_id'])
            ->max('sort_order') ?? 0;
        $validated['sort_order'] = $lastOrder + 1;

        EvaluationCriteria::create($validated);

        // إرجاع المستخدم لنفس الصفحة مع معلومات النجاح
        $redirectUrl = route('evaluation-criteria.create');
        if ($request->filled('role_id')) {
            $redirectUrl = route('evaluation-criteria.create', ['role_id' => $request->role_id]);
        }

        return redirect($redirectUrl)
            ->with('success', 'تم إضافة البند بنجاح! يمكنك إضافة بند آخر.')
            ->with('toast', true); // إشارة لإظهار toast
    }

    /**
     * عرض تفاصيل بند
     */
    public function show(EvaluationCriteria $evaluationCriteria)
    {
        // حساب الترتيب الحالي للبند داخل نفس الدور
        $currentOrder = EvaluationCriteria::where('role_id', $evaluationCriteria->role_id)
            ->where('id', '<=', $evaluationCriteria->id)
            ->count();

        return view('kpi.evaluation-criteria.show', compact('evaluationCriteria', 'currentOrder'));
    }

    /**
     * عرض نموذج تعديل بند
     */
    public function edit(EvaluationCriteria $evaluationCriteria)
    {
        $roles = Role::where('name', '!=', 'employee')->get(); // استبعاد دور employee
        $criteriaTypes = [
            'positive' => 'بند إيجابي',
            'negative' => 'خصم/سالب',
            'bonus' => 'بونص إضافي',
            'development' => 'بند تطويري'
        ];

        $evaluationPeriods = [
            'monthly' => 'شهري',
            'bi_weekly' => 'نصف شهري'
        ];

        $criteriaCategories = [
            'بنود إيجابية' => 'بنود إيجابية',
            'بنود سلبية' => 'بنود سلبية',
            'بنود تطويرية' => 'بنود تطويرية',
            'بونص' => 'بونص'
        ];

        return view('kpi.evaluation-criteria.edit', compact('evaluationCriteria', 'roles', 'criteriaTypes', 'evaluationPeriods', 'criteriaCategories'));
    }

    /**
     * تحديث بند
     */
    public function update(Request $request, EvaluationCriteria $evaluationCriteria)
    {
        $validated = $request->validate([
            'role_id' => 'required|exists:roles,id',
            'criteria_name' => 'required|string|max:255',
            'criteria_description' => 'nullable|string',
            'max_points' => 'required|integer|min:0|max:1000',
            'criteria_type' => 'required|in:positive,negative,bonus',
            'category' => 'nullable|string|max:100',
            'is_active' => 'boolean',
            'evaluate_per_project' => 'boolean',
            'evaluation_period' => 'required|in:monthly,bi_weekly',
        ]);

        // التحقق من أن الدور ليس employee
        $role = Role::find($validated['role_id']);
        if ($role && $role->name === 'employee') {
            return back()->withErrors(['role_id' => 'لا يمكن تعديل أو نقل بنود تقييم لدور Employee'])->withInput();
        }

        $validated['is_active'] = $request->has('is_active');
        $validated['evaluate_per_project'] = $request->has('evaluate_per_project');

        // الحفاظ على الترتيب الحالي إذا لم يتغير الدور
        if ($evaluationCriteria->role_id == $validated['role_id']) {
            $validated['sort_order'] = $evaluationCriteria->sort_order;
        } else {
            // إذا تغير الدور، احسب ترتيب جديد
            $lastOrder = EvaluationCriteria::where('role_id', $validated['role_id'])
                ->max('sort_order') ?? 0;
            $validated['sort_order'] = $lastOrder + 1;
        }

        $evaluationCriteria->update($validated);

        return redirect()->route('evaluation-criteria.index')
            ->with('success', 'تم تحديث البند بنجاح');
    }

    /**
     * حذف بند
     */
    public function destroy(EvaluationCriteria $evaluationCriteria)
    {
        $evaluationCriteria->delete();

        return redirect()->route('evaluation-criteria.index')
            ->with('success', 'تم حذف البند بنجاح');
    }

    /**
     * الحصول على البنود حسب الدور (AJAX)
     */
    public function getCriteriaByRole(Request $request)
    {
        $roleId = $request->get('role_id');

        if (!$roleId) {
            return response()->json(['error' => 'Role ID is required'], 400);
        }

        $criteria = EvaluationCriteria::getCriteriaGroupedByType($roleId);

        return response()->json([
            'success' => true,
            'criteria' => $criteria,
            'total_positive' => EvaluationCriteria::getTotalPointsForRole($roleId, 'positive'),
            'total_negative' => EvaluationCriteria::getTotalPointsForRole($roleId, 'negative'),
            'total_bonus' => EvaluationCriteria::getTotalPointsForRole($roleId, 'bonus'),
        ]);
    }
}
