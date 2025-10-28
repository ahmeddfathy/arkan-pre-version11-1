<?php

namespace App\Http\Controllers;

use App\Models\EmployeeEvaluation;
use App\Models\EvaluationDetail;
use App\Models\Skill;
use App\Models\SkillCategory;
use App\Models\User;
use App\Services\Auth\RoleCheckService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmployeeEvaluationController extends Controller
{
    protected $roleCheckService;

    public function __construct(RoleCheckService $roleCheckService)
    {
        $this->middleware(['auth']);
        $this->roleCheckService = $roleCheckService;
    }

    /**
     * عرض قائمة التقييمات
     */
    public function index()
    {
        // التحقق من الصلاحيات - فقط المدراء والإدارة العليا وقسم الموارد البشرية
        $roles = ['admin', 'super-admin', 'hr', 'project_manager'];
        if (!$this->roleCheckService->userHasRole($roles)) {
            // إذا كان المستخدم موظف عادي، نعرض له تقييماته فقط
            return $this->myEvaluations();
        }

        // تسجيل النشاط - دخول صفحة تقييمات الموظفين
        if (\Illuminate\Support\Facades\Auth::check()) {
            activity()
                ->causedBy(\Illuminate\Support\Facades\Auth::user())
                ->withProperties([
                    'action_type' => 'view_index',
                    'page' => 'employee_evaluations_index',
                    'viewed_at' => now()->toDateTimeString(),
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip()
                ])
                ->log('دخل على صفحة تقييمات الموظفين');
        }

        $evaluations = EmployeeEvaluation::with(['user', 'evaluator'])
            ->orderBy('evaluation_date', 'desc')
            ->paginate(10);

        return view('employee-evaluations.index', compact('evaluations'));
    }

    /**
     * عرض تقييمات الموظف الحالي
     */
    public function myEvaluations()
    {
        // تسجيل النشاط - دخول صفحة تقييماتي الشخصية
        if (\Illuminate\Support\Facades\Auth::check()) {
            activity()
                ->causedBy(\Illuminate\Support\Facades\Auth::user())
                ->withProperties([
                    'action_type' => 'view_my_evaluations',
                    'page' => 'my_employee_evaluations',
                    'viewed_at' => now()->toDateTimeString(),
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip()
                ])
                ->log('دخل على صفحة تقييماتي الشخصية');
        }

        $evaluations = EmployeeEvaluation::where('user_id', Auth::id())
            ->with('evaluator')
            ->orderBy('evaluation_date', 'desc')
            ->paginate(10);

        return view('employee-evaluations.my-evaluations', compact('evaluations'));
    }

    /**
     * عرض نموذج إنشاء تقييم جديد
     */
    public function create()
    {
        // التحقق من الصلاحيات
        $roles = ['admin', 'super-admin', 'hr', 'project_manager'];
        if (!$this->roleCheckService->userHasRole($roles)) {
            return redirect()->route('employee-evaluations.index')
                ->with('error', 'ليس لديك صلاحية لإنشاء تقييمات جديدة');
        }

        $users = User::orderBy('name')->get();
        $skillCategories = SkillCategory::with(['skills' => function($query) {
            $query->where('is_active', true)->orderBy('name');
        }])->get();

        return view('employee-evaluations.create', compact('users', 'skillCategories'));
    }

    /**
     * حفظ تقييم جديد
     */
    public function store(Request $request)
    {
        // التحقق من الصلاحيات
        $roles = ['admin', 'super-admin', 'hr', 'project_manager'];
        if (!$this->roleCheckService->userHasRole($roles)) {
            return redirect()->route('employee-evaluations.index')
                ->with('error', 'ليس لديك صلاحية لإنشاء تقييمات جديدة');
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'evaluation_period' => 'required|string|max:100',
            'evaluation_date' => 'required|date',
            'notes' => 'nullable|string',
            'skills' => 'required|array',
            'skills.*' => 'required|exists:skills,id',
            'points' => 'required|array',
            'points.*' => 'required|integer|min:0',
            'comments' => 'nullable|array',
            'comments.*' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            // إنشاء التقييم الرئيسي
            $evaluation = EmployeeEvaluation::create([
                'user_id' => $request->user_id,
                'evaluator_id' => Auth::id(),
                'evaluation_period' => $request->evaluation_period,
                'evaluation_date' => $request->evaluation_date,
                'notes' => $request->notes,
            ]);

            // إضافة تفاصيل التقييم لكل مهارة
            foreach ($request->skills as $index => $skillId) {
                EvaluationDetail::create([
                    'evaluation_id' => $evaluation->id,
                    'skill_id' => $skillId,
                    'points' => $request->points[$index],
                    'comments' => $request->comments[$index] ?? null,
                ]);
            }

            DB::commit();

            return redirect()->route('employee-evaluations.show', $evaluation)
                ->with('success', 'تم إنشاء التقييم بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء حفظ التقييم: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * عرض تفاصيل تقييم محدد
     */
    public function show(EmployeeEvaluation $employeeEvaluation)
    {
        // التحقق من الصلاحيات
        $roles = ['admin', 'super-admin', 'hr', 'project_manager'];
        if (!$this->roleCheckService->userHasRole($roles) &&
            Auth::id() != $employeeEvaluation->user_id) {
            return redirect()->route('employee-evaluations.index')
                ->with('error', 'ليس لديك صلاحية لعرض هذا التقييم');
        }

        // تسجيل النشاط - عرض تفاصيل تقييم الموظف
        if (\Illuminate\Support\Facades\Auth::check()) {
            activity()
                ->performedOn($employeeEvaluation)
                ->causedBy(\Illuminate\Support\Facades\Auth::user())
                ->withProperties([
                    'evaluation_id' => $employeeEvaluation->id,
                    'evaluated_user_id' => $employeeEvaluation->user_id,
                    'evaluated_user_name' => $employeeEvaluation->user->name ?? null,
                    'evaluator_id' => $employeeEvaluation->evaluator_id,
                    'evaluator_name' => $employeeEvaluation->evaluator->name ?? null,
                    'evaluation_period' => $employeeEvaluation->evaluation_period,
                    'evaluation_date' => $employeeEvaluation->evaluation_date,
                    'action_type' => 'view',
                    'viewed_at' => now()->toDateTimeString(),
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip()
                ])
                ->log('شاهد تفاصيل تقييم الموظف');
        }

        $evaluation = $employeeEvaluation->load(['user', 'evaluator', 'evaluationDetails.skill.category']);

        // تجميع التفاصيل حسب التصنيف
        $detailsByCategory = [];
        $totalPoints = 0;
        $maxTotalPoints = 0;

        foreach ($evaluation->evaluationDetails as $detail) {
            $categoryName = $detail->skill->category->name;

            if (!isset($detailsByCategory[$categoryName])) {
                $detailsByCategory[$categoryName] = [];
            }

            $detailsByCategory[$categoryName][] = $detail;
            $totalPoints += $detail->points;
            $maxTotalPoints += $detail->skill->max_points;
        }

        $percentageScore = $maxTotalPoints > 0 ? round(($totalPoints / $maxTotalPoints) * 100) : 0;

        return view('employee-evaluations.show', compact(
            'evaluation',
            'detailsByCategory',
            'totalPoints',
            'maxTotalPoints',
            'percentageScore'
        ));
    }

    /**
     * عرض نموذج تعديل تقييم
     */
    public function edit(EmployeeEvaluation $employeeEvaluation)
    {
        // التحقق من الصلاحيات
        $roles = ['admin', 'super-admin', 'hr'];
        if (!$this->roleCheckService->userHasRole($roles) &&
            Auth::id() != $employeeEvaluation->evaluator_id) {
            return redirect()->route('employee-evaluations.index')
                ->with('error', 'ليس لديك صلاحية لتعديل هذا التقييم');
        }

        $evaluation = $employeeEvaluation->load(['evaluationDetails.skill.category']);
        $skillCategories = SkillCategory::with(['skills' => function($query) {
            $query->where('is_active', true)->orderBy('name');
        }])->get();

        // تحضير البيانات الحالية للنموذج
        $existingDetails = [];
        foreach ($evaluation->evaluationDetails as $detail) {
            $existingDetails[$detail->skill_id] = [
                'points' => $detail->points,
                'comments' => $detail->comments,
            ];
        }

        return view('employee-evaluations.edit', compact(
            'evaluation',
            'skillCategories',
            'existingDetails'
        ));
    }

    /**
     * تحديث تقييم محدد
     */
    public function update(Request $request, EmployeeEvaluation $employeeEvaluation)
    {
        // التحقق من الصلاحيات
        $roles = ['admin', 'super-admin', 'hr'];
        if (!$this->roleCheckService->userHasRole($roles) &&
            Auth::id() != $employeeEvaluation->evaluator_id) {
            return redirect()->route('employee-evaluations.index')
                ->with('error', 'ليس لديك صلاحية لتعديل هذا التقييم');
        }

        $request->validate([
            'evaluation_period' => 'required|string|max:100',
            'evaluation_date' => 'required|date',
            'notes' => 'nullable|string',
            'skills' => 'required|array',
            'skills.*' => 'required|exists:skills,id',
            'points' => 'required|array',
            'points.*' => 'required|integer|min:0',
            'comments' => 'nullable|array',
            'comments.*' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            // تحديث التقييم الرئيسي
            $employeeEvaluation->update([
                'evaluation_period' => $request->evaluation_period,
                'evaluation_date' => $request->evaluation_date,
                'notes' => $request->notes,
            ]);

            // حذف التفاصيل الحالية
            EvaluationDetail::where('evaluation_id', $employeeEvaluation->id)->delete();

            // إعادة إنشاء تفاصيل التقييم لكل مهارة
            foreach ($request->skills as $index => $skillId) {
                EvaluationDetail::create([
                    'evaluation_id' => $employeeEvaluation->id,
                    'skill_id' => $skillId,
                    'points' => $request->points[$index],
                    'comments' => $request->comments[$index] ?? null,
                ]);
            }

            DB::commit();

            return redirect()->route('employee-evaluations.show', $employeeEvaluation)
                ->with('success', 'تم تحديث التقييم بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء تحديث التقييم: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * حذف تقييم محدد
     */
    public function destroy(EmployeeEvaluation $employeeEvaluation)
    {
        // التحقق من الصلاحيات - فقط المدير أو HR يمكنهم الحذف
        $roles = ['admin', 'super-admin', 'hr'];
        if (!$this->roleCheckService->userHasRole($roles)) {
            return redirect()->route('employee-evaluations.index')
                ->with('error', 'ليس لديك صلاحية لحذف التقييمات');
        }

        DB::beginTransaction();

        try {
            // حذف تفاصيل التقييم أولاً
            EvaluationDetail::where('evaluation_id', $employeeEvaluation->id)->delete();

            // ثم حذف التقييم نفسه
            $employeeEvaluation->delete();

            DB::commit();

            return redirect()->route('employee-evaluations.index')
                ->with('success', 'تم حذف التقييم بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء حذف التقييم: ' . $e->getMessage());
        }
    }
}
