<?php

namespace App\Http\Controllers\Kpi;

use Illuminate\Http\Request;
use App\Models\EvaluationCriteria;
use App\Models\RoleEvaluationMapping;
use App\Models\KpiEvaluation;
use App\Models\ProjectEvaluation;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class KpiEvaluationController extends Controller
{

    public function create(Request $request)
    {
        $user = Auth::user();
        $userRoles = $user->roles->pluck('id')->toArray();

        if (empty($userRoles)) {
            abort(403, 'لا يوجد دور محدد للمستخدم');
        }

        // جلب جميع الأدوار اللي المستخدم مصرح له يقيمها (بدون تقييد بالقسم في البداية)
        // التحقق من جميع أدوار المستخدم
        $rolesCanEvaluate = RoleEvaluationMapping::with(['roleToEvaluate'])
            ->whereIn('evaluator_role_id', $userRoles)
            ->where('can_evaluate', true)
            ->get();

        // إزالة التكرار: جمع الأدوار الفريدة فقط
        $rolesCanEvaluate = $rolesCanEvaluate->unique(function ($mapping) {
            return $mapping->role_to_evaluate_id;
        })->values();

        $selectedRoleId = $request->get('role_id');

        if (!$selectedRoleId) {
            return view('kpi.employee-kpi-evaluations.select-role', compact('rolesCanEvaluate'));
        }

        // التحقق من جميع أدوار المستخدم
        $canEvaluate = RoleEvaluationMapping::whereIn('evaluator_role_id', $userRoles)
            ->where('role_to_evaluate_id', $selectedRoleId)
            ->where('can_evaluate', true)
            ->when($user->department, function ($query) use ($user) {
                return $query->where('department_name', $user->department);
            })
            ->exists();

        if (!$canEvaluate) {
            // محاولة أخرى بدون تقييد بالقسم
            $canEvaluate = RoleEvaluationMapping::whereIn('evaluator_role_id', $userRoles)
                ->where('role_to_evaluate_id', $selectedRoleId)
                ->where('can_evaluate', true)
                ->exists();
        }

        if (!$canEvaluate) {
            abort(403, 'غير مصرح لك بتقييم هذا الدور');
        }

        // الحصول على نوع التقييم المختار أو الافتراضي
        $evaluationType = $request->get('evaluation_type', 'monthly');

        $evaluationCriteria = EvaluationCriteria::getCriteriaGroupedByType($selectedRoleId, $evaluationType);
        $selectedRole = Role::find($selectedRoleId);

        $projectBasedCriteria = EvaluationCriteria::forRole($selectedRoleId)
            ->forPeriod($evaluationType)
            ->projectBased()
            ->active()
            ->ordered()
            ->get();

        // إعداد أنواع التقييم
        $evaluationTypes = [
            'monthly' => 'تقييم شهري',
            'bi_weekly' => 'تقييم نصف شهري'
        ];

        if (!$evaluationCriteria || $evaluationCriteria->isEmpty()) {
            return redirect()->route('kpi-evaluation.create')
                ->with('error', 'لا توجد بنود تقييم معرّفة لهذا الدور. يرجى إضافة بنود التقييم أولاً.');
        }

        $usersWithRole = \App\Models\User::whereHas('roles', function ($query) use ($selectedRoleId) {
            $query->where('roles.id', $selectedRoleId);
        })
            ->when($user->department, function ($query) use ($user) {
                return $query->where('department', $user->department);
            })
            ->select('id', 'name', 'department')
            ->get();

        if ($usersWithRole->isEmpty()) {
            $usersWithRole = \App\Models\User::whereHas('roles', function ($query) use ($selectedRoleId) {
                $query->where('roles.id', $selectedRoleId);
            })
                ->select('id', 'name', 'department')
                ->get();
        }

        return view('kpi.employee-kpi-evaluations.create', compact(
            'evaluationCriteria',
            'selectedRole',
            'selectedRoleId',
            'usersWithRole',
            'projectBasedCriteria',
            'evaluationType',
            'evaluationTypes'
        ));
    }


    public function store(Request $request)
    {
        $user = Auth::user();
        $userRole = $user->roles->first();

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_id' => 'required|exists:roles,id',
            'evaluation_type' => 'required|in:monthly,bi_weekly',
            'review_month' => 'required|date_format:Y-m',
            'criteria_scores' => 'required|array',
            'criteria_scores.*' => 'string|numeric|min:0',
            'criteria_notes' => 'nullable|array',
            'criteria_notes.*' => 'nullable|string|max:1000',
            'project_criteria' => 'nullable|array',
            'project_criteria.*' => 'array',
            'project_criteria.*.*' => 'string|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $validated['review_month'] = $validated['review_month'] . '-01';

        $canEvaluate = RoleEvaluationMapping::where('evaluator_role_id', $userRole->id)
            ->where('role_to_evaluate_id', $validated['role_id'])
            ->where('can_evaluate', true)
            ->when($user->department, function ($query) use ($user) {
                return $query->where('department_name', $user->department);
            })
            ->exists();

        // إذا لم نجد صلاحية مع القسم، جرب بدون قيد القسم
        if (!$canEvaluate) {
            $canEvaluate = RoleEvaluationMapping::where('evaluator_role_id', $userRole->id)
                ->where('role_to_evaluate_id', $validated['role_id'])
                ->where('can_evaluate', true)
                ->exists();
        }

        if (!$canEvaluate) {
            abort(403, 'غير مصرح لك بتقييم هذا الدور');
        }

        // الحصول على بنود التقييم للدور حسب نوع التقييم
        $criteria = EvaluationCriteria::forRole($validated['role_id'])
            ->forPeriod($validated['evaluation_type'])
            ->active()
            ->get();

        // حساب النتائج
        $totalScore = 0;
        $totalDeductions = 0;
        $totalBonus = 0;
        $totalDevelopment = 0;

        foreach ($criteria as $criterion) {
            $score = $validated['criteria_scores'][$criterion->id] ?? 0;

            // التحقق من أن النقاط لا تتجاوز الحد الأقصى
            if ($score > $criterion->max_points) {
                return back()->withErrors([
                    "criteria_scores.{$criterion->id}" => "النقاط تتجاوز الحد الأقصى ({$criterion->max_points})"
                ]);
            }

            switch ($criterion->criteria_type) {
                case 'positive':
                    $totalScore += $score;
                    break;
                case 'negative':
                    $totalDeductions += $score;
                    break;
                case 'bonus':
                    $totalBonus += $score;
                    break;
                case 'development':
                    $totalDevelopment += $score;
                    break;
            }
        }

        $finalScore = $totalScore + $totalBonus + $totalDevelopment - $totalDeductions;

        // إنشاء سجل التقييم (يمكن تخصيصه حسب نوع التقييم)
        $evaluationData = [
            'user_id' => $validated['user_id'],
            'reviewer_id' => $user->id,
            'evaluation_type' => $validated['evaluation_type'],
            'review_month' => $validated['review_month'],
            'total_score' => $totalScore,
            'total_after_deductions' => $finalScore,
            'notes' => $validated['notes'],
        ];

        // إضافة النقاط للحقول المناسبة حسب بنود التقييم
        foreach ($criteria as $criterion) {
            $fieldName = $this->getCriteriaFieldName($criterion);
            if ($fieldName) {
                $evaluationData[$fieldName] = $validated['criteria_scores'][$criterion->id] ?? 0;
            }
        }


        // إعداد بيانات البنود مع الأسماء والتفاصيل للحفظ التاريخي
        $criteriaWithDetails = [];
        foreach ($criteria as $criterion) {
            $score = (int) ($validated['criteria_scores'][$criterion->id] ?? 0);
            $note = $validated['criteria_notes'][$criterion->id] ?? null;
            $criteriaWithDetails[] = [
                'id' => (int) $criterion->id,
                'name' => $criterion->criteria_name,
                'description' => $criterion->criteria_description,
                'score' => $score,
                'max_points' => (int) $criterion->max_points,
                'criteria_type' => $criterion->criteria_type,
                'category' => $criterion->category,
                'sort_order' => (int) $criterion->sort_order,
                'note' => $note // إضافة الملاحظة
            ];
        }

        try {
            // حفظ تقييمات KPI مع البيانات التفصيلية
            $result = KpiEvaluation::create(array_merge($evaluationData, [
                'role_id' => $validated['role_id'],
                'criteria_scores' => json_encode($criteriaWithDetails),
                'total_bonus' => $totalBonus,
                'total_deductions' => $totalDeductions,
                'total_development' => $totalDevelopment,
            ]));

            // حفظ تقييمات المشاريع إذا وجدت
            if (!empty($validated['project_criteria'])) {
                // إرسال التاريخ بـ format Y-m للمشاريع (مش Y-m-d)
                $projectReviewMonth = substr($validated['review_month'], 0, 7); // إزالة -01
                $this->saveProjectEvaluations(
                    $validated['project_criteria'],
                    $validated['user_id'],
                    $validated['role_id'],
                    $projectReviewMonth,
                    $user->id
                );
            }
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'حدث خطأ أثناء حفظ التقييم: ' . $e->getMessage()]);
        }

        return redirect()->route('kpi-evaluation.create')
            ->with('success', 'تم حفظ التقييم بنجاح');
    }

    /**
     * حفظ تقييمات المشاريع
     */
    private function saveProjectEvaluations($projectCriteria, $userId, $roleId, $reviewMonth, $evaluatorId)
    {
        foreach ($projectCriteria as $projectId => $criteriaScores) {
            if (ProjectEvaluation::hasProjectEvaluation($userId, $projectId, $roleId, $reviewMonth)) {
                continue;
            }


            // تحويل جميع القيم لـ integers
            $criteriaScores = array_map('intval', $criteriaScores);
            $totalProjectScore = array_sum($criteriaScores);

            $criteriaDetails = EvaluationCriteria::whereIn('id', array_keys($criteriaScores))
                ->projectBased()
                ->get()
                ->mapWithKeys(function ($criterion) use ($criteriaScores) {
                    return [
                        $criterion->id => [
                            'name' => $criterion->criteria_name,
                            'score' => (int) ($criteriaScores[$criterion->id] ?? 0),
                            'max_points' => (int) $criterion->max_points,
                        ]
                    ];
                });


            ProjectEvaluation::create([
                'user_id' => $userId,
                'project_id' => $projectId,
                'role_id' => $roleId,
                'evaluator_id' => $evaluatorId,
                'review_month' => $reviewMonth,
                'total_project_score' => $totalProjectScore,
                'criteria_scores' => $criteriaDetails->toArray(),
            ]);
        }
    }


    private function getCriteriaFieldName($criterion)
    {

        $fieldMapping = [
            'نسبة التفاعل مع العملاء' => 'client_interaction_score',
            'نسبة التعاقد مع العملاء' => 'client_contract_score',
            'سرعة التواصل مع العملاء' => 'client_communication_speed_score',

        ];

        return $fieldMapping[$criterion->criteria_name] ?? null;
    }



    public function index(Request $request)
    {
        if (\Illuminate\Support\Facades\Auth::check()) {
            activity()
                ->causedBy(\Illuminate\Support\Facades\Auth::user())
                ->withProperties([
                    'action_type' => 'view_index',
                    'page' => 'kpi_evaluations_index',
                    'filters' => $request->all(),
                    'viewed_at' => now()->toDateTimeString(),
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip()
                ])
                ->log('دخل على صفحة تقييمات KPI');
        }

        $user = Auth::user();
        $userRole = $user->roles->first();

        // الحصول على التقييمات مدمجة حسب الموظف والشهر ونوع التقييم (بدون تفريق الأدوار)
        $query = KpiEvaluation::where('reviewer_id', $user->id)
            ->join('users', 'kpi_evaluations.user_id', '=', 'users.id')
            ->select([
                'kpi_evaluations.user_id',
                DB::raw('GROUP_CONCAT(DISTINCT kpi_evaluations.role_id) as role_ids'), // جميع الأدوار
                'kpi_evaluations.evaluation_type',
                DB::raw('DATE_FORMAT(kpi_evaluations.review_month, "%Y-%m") as month'),
                DB::raw('SUM(kpi_evaluations.total_score) as total_score'),
                DB::raw('SUM(kpi_evaluations.total_after_deductions) as total_after_deductions'),
                DB::raw('SUM(kpi_evaluations.total_bonus) as total_bonus'),
                DB::raw('SUM(kpi_evaluations.total_deductions) as total_deductions'),
                DB::raw('SUM(kpi_evaluations.total_development) as total_development'),
                DB::raw('MAX(kpi_evaluations.created_at) as created_at'),
                DB::raw('COUNT(DISTINCT kpi_evaluations.role_id) as roles_count'), // عدد الأدوار
                DB::raw('COUNT(*) as evaluation_count')
            ])
            ->groupBy('kpi_evaluations.user_id', 'kpi_evaluations.evaluation_type', DB::raw('DATE_FORMAT(kpi_evaluations.review_month, "%Y-%m")'))
            ->orderBy('created_at', 'desc');

        // تطبيق فلتر الشهر والسنة
        if ($request->filled('month_year')) {
            $query->whereRaw('DATE_FORMAT(kpi_evaluations.review_month, "%Y-%m") = ?', [$request->month_year]);
        }

        if ($request->filled('department')) {
            $query->where('users.department', $request->department);
        }

        if ($request->filled('employee_id')) {
            $query->where('kpi_evaluations.user_id', $request->employee_id);
        }

        if ($request->filled('role_id')) {
            $query->where('kpi_evaluations.role_id', $request->role_id);
        }

        if ($request->filled('evaluation_type')) {
            $query->where('kpi_evaluations.evaluation_type', $request->evaluation_type);
        }

        $evaluations = $query->paginate(15);


        $evaluations->getCollection()->transform(function ($evaluation) {

            $evaluation->user = \App\Models\User::find($evaluation->user_id);

            // جلب جميع الأدوار للموظف
            $roleIds = explode(',', $evaluation->role_ids);
            $evaluation->roles = \Spatie\Permission\Models\Role::whereIn('id', $roleIds)->get();
            $evaluation->role = $evaluation->roles->first(); // الدور الأول للعرض

            // جلب تقييمات المشاريع لجميع الأدوار
            $projectEvaluations = ProjectEvaluation::where([
                'user_id' => $evaluation->user_id,
                'review_month' => $evaluation->month,
            ])->whereIn('role_id', $roleIds)->get();

            $evaluation->project_score = $projectEvaluations->sum('total_project_score');
            $evaluation->project_count = $projectEvaluations->count();
            $evaluation->final_total = $evaluation->total_after_deductions + $evaluation->project_score;

            // تحويل created_at إلى Carbon object
            $evaluation->created_at = \Carbon\Carbon::parse($evaluation->created_at);

            return $evaluation;
        });

        // الحصول على الأدوار المتاحة للفلترة (جميع الأدوار اللي المستخدم مصرح له يقيمها)
        $rolesCanEvaluate = RoleEvaluationMapping::with(['roleToEvaluate'])
            ->where('evaluator_role_id', $userRole->id)
            ->where('can_evaluate', true)
            ->get()
            ->unique(function ($mapping) {
                return $mapping->role_to_evaluate_id;
            })
            ->values();

        // الحصول على جميع الأقسام
        $departments = \App\Models\User::getAvailableDepartments();

        // الحصول على جميع الموظفين النشطين
        $employees = \App\Models\User::where('employee_status', 'active')
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->orderBy('name')
            ->get(['id', 'name', 'department']);

        return view('kpi.employee-kpi-evaluations.index', compact('evaluations', 'rolesCanEvaluate', 'departments', 'employees'));
    }

    /**
     * عرض تفاصيل التقييمات المدمجة للموظف في شهر محدد (دور واحد أو كل الأدوار)
     */
    public function details(Request $request)
    {
        $userId = $request->get('user_id');
        $roleId = $request->get('role_id'); // اختياري الآن
        $month = $request->get('month');

        if (!$userId || !$month) {
            abort(404, 'بيانات غير صحيحة: user_id=' . $userId . ', month=' . $month);
        }

        $user = \App\Models\User::find($userId);

        if (!$user) {
            abort(404, 'المستخدم غير موجود: ID = ' . $userId);
        }

        // إذا كان role_id محدد، اعرض دور واحد فقط
        if ($roleId) {
            $role = \Spatie\Permission\Models\Role::find($roleId);
            if (!$role) {
                abort(404, 'الدور غير موجود: ID = ' . $roleId);
            }
            $roleIds = [$roleId];
            $roles = collect([$role]);
        } else {
            // جلب جميع الأدوار التي تم تقييم المستخدم فيها في هذا الشهر
            $roleIds = KpiEvaluation::where('user_id', $userId)
                ->whereRaw('DATE_FORMAT(review_month, "%Y-%m") = ?', [$month])
                ->distinct()
                ->pluck('role_id')
                ->toArray();

            $roles = \Spatie\Permission\Models\Role::whereIn('id', $roleIds)->get();
            $role = $roles->first(); // للتوافق مع الكود القديم
        }

        // الحصول على التقييمات العامة لجميع الأدوار
        $generalEvaluations = KpiEvaluation::with(['user', 'role'])
            ->where('user_id', $userId)
            ->whereIn('role_id', $roleIds)
            ->whereRaw('DATE_FORMAT(review_month, "%Y-%m") = ?', [$month])
            ->orderBy('role_id')
            ->orderBy('created_at', 'desc')
            ->get();

        // الحصول على تقييمات المشاريع لجميع الأدوار
        $projectEvaluations = ProjectEvaluation::with(['project', 'evaluator', 'role'])
            ->where('user_id', $userId)
            ->whereIn('role_id', $roleIds)
            ->where('review_month', $month)
            ->orderBy('role_id')
            ->orderBy('created_at', 'desc')
            ->get();

        // حساب الإجماليات
        $totalGeneralScore = $generalEvaluations->sum('total_after_deductions');
        $totalProjectScore = $projectEvaluations->sum('total_project_score');
        $totalDevelopmentScore = $generalEvaluations->sum('total_development');
        $finalTotal = $totalGeneralScore + $totalProjectScore + $totalDevelopmentScore;

        // تجميع التقييمات حسب الدور للعرض المنظم
        $evaluationsByRole = $generalEvaluations->groupBy('role_id');
        $projectsByRole = $projectEvaluations->groupBy('role_id');

        return view('kpi.employee-kpi-evaluations.details', compact(
            'user',
            'role',
            'roles',
            'month',
            'generalEvaluations',
            'projectEvaluations',
            'totalGeneralScore',
            'totalProjectScore',
            'totalDevelopmentScore',
            'finalTotal',
            'evaluationsByRole',
            'projectsByRole'
        ));
    }

    /**
     * عرض تفاصيل تقييم محدد
     */
    public function show(KpiEvaluation $kpiEvaluation)
    {
        $user = Auth::user();

        // تسجيل النشاط - عرض تفاصيل تقييم KPI
        if (\Illuminate\Support\Facades\Auth::check()) {
            activity()
                ->performedOn($kpiEvaluation)
                ->causedBy(\Illuminate\Support\Facades\Auth::user())
                ->withProperties([
                    'evaluation_id' => $kpiEvaluation->id,
                    'evaluated_user_id' => $kpiEvaluation->user_id,
                    'reviewer_id' => $kpiEvaluation->reviewer_id,
                    'action_type' => 'view',
                    'viewed_at' => now()->toDateTimeString(),
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip()
                ])
                ->log('شاهد تفاصيل تقييم KPI');
        }

        // التحقق من أن المستخدم هو من قام بالتقييم أو لديه صلاحية المشاهدة
        if ($kpiEvaluation->reviewer_id !== $user->id) {
            abort(403, 'غير مصرح لك بمشاهدة هذا التقييم');
        }

        $criteriaWithScores = $kpiEvaluation->getCriteriaWithScores();

        return view('kpi.employee-kpi-evaluations.show', compact('kpiEvaluation', 'criteriaWithScores'));
    }


    public function getCriteriaByRole(Request $request)
    {
        $roleId = $request->get('role_id');
        $user = Auth::user();
        $userRole = $user->roles->first();

        $canEvaluate = RoleEvaluationMapping::where('evaluator_role_id', $userRole->id)
            ->where('role_to_evaluate_id', $roleId)
            ->where('can_evaluate', true)
            ->when($user->department, function ($query) use ($user) {
                return $query->where('department_name', $user->department);
            })
            ->exists();

        if (!$canEvaluate) {
            $canEvaluate = RoleEvaluationMapping::where('evaluator_role_id', $userRole->id)
                ->where('role_to_evaluate_id', $roleId)
                ->where('can_evaluate', true)
                ->exists();
        }

        if (!$canEvaluate) {
            return response()->json(['error' => 'غير مصرح لك بتقييم هذا الدور'], 403);
        }

        $criteria = EvaluationCriteria::getCriteriaGroupedByType($roleId);

        return response()->json([
            'success' => true,
            'criteria' => $criteria,
            'totals' => [
                'positive' => EvaluationCriteria::getTotalPointsForRole($roleId, 'positive'),
                'negative' => EvaluationCriteria::getTotalPointsForRole($roleId, 'negative'),
                'bonus' => EvaluationCriteria::getTotalPointsForRole($roleId, 'bonus'),
                'development' => EvaluationCriteria::getTotalPointsForRole($roleId, 'development'),
            ]
        ]);
    }


    public function getUserProjects(Request $request)
    {
        $userId = $request->get('user_id');
        $roleId = $request->get('role_id');
        $reviewMonth = $request->get('review_month', now()->format('Y-m'));

        if (!$userId || !$roleId) {
            return response()->json(['error' => 'معطيات غير صحيحة'], 400);
        }

        $projects = \App\Models\Project::whereHas('serviceParticipants', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
            ->with(['client', 'serviceParticipants' => function ($query) use ($userId) {
                $query->where('user_id', $userId);
            }])
            ->where('status', '!=', 'ملغي') // استبعاد المشاريع الملغاة
            ->orderBy('created_at', 'desc')
            ->get();

        $projectCriteria = EvaluationCriteria::forRole($roleId)
            ->projectBased()
            ->active()
            ->ordered()
            ->get();

        return response()->json([
            'success' => true,
            'projects' => $projects->map(function ($project) use ($userId, $roleId, $reviewMonth) {
                $isEvaluated = ProjectEvaluation::hasProjectEvaluation($userId, $project->id, $roleId, $reviewMonth);
                $evaluation = null;

                if ($isEvaluated) {
                    $evaluation = ProjectEvaluation::getProjectEvaluation($userId, $project->id, $roleId, $reviewMonth);
                }

                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'client_name' => $project->client->name ?? 'غير محدد',
                    'status' => $project->status,
                    'start_date' => $project->start_date ? (\Carbon\Carbon::parse($project->start_date)->toDateString()) : null,
                    'team_delivery_date' => $project->team_delivery_date ? (\Carbon\Carbon::parse($project->team_delivery_date)->toDateString()) : null,
                    'is_evaluated' => $isEvaluated,
                    'evaluation_score' => $evaluation ? $evaluation->total_project_score : null,
                    'evaluation_date' => $evaluation ? $evaluation->created_at->format('Y-m-d H:i') : null,
                    'evaluator_name' => $evaluation ? $evaluation->evaluator->name : null,
                    'evaluation_criteria_scores' => $evaluation ? $evaluation->criteria_scores : null,
                ];
            }),
            'criteria' => $projectCriteria->map(function ($criteria) {
                return [
                    'id' => $criteria->id,
                    'name' => $criteria->criteria_name,
                    'description' => $criteria->criteria_description,
                    'max_points' => $criteria->max_points,
                    'type' => $criteria->criteria_type,
                ];
            }),
            'review_month' => $reviewMonth
        ]);
    }

    /**
     * جلب تفاصيل التقييم للسايدبار عبر AJAX
     */
    public function getSidebarDetails(KpiEvaluation $kpiEvaluation)
    {
        try {
            $user = Auth::user();



            // التحقق من الصلاحية - نفس شروط show method
            if ($kpiEvaluation->reviewer_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بمشاهدة هذا التقييم'
                ], 403);
            }

            $kpiEvaluation->load(['user', 'reviewer', 'role']);


            $criteriaWithScores = $kpiEvaluation->getCriteriaWithScores();

            $data = [
                'id' => $kpiEvaluation->id,
                'total_score' => $kpiEvaluation->total_score,
                'total_bonus' => $kpiEvaluation->total_bonus,
                'total_deductions' => $kpiEvaluation->total_deductions,
                'total_development' => $kpiEvaluation->total_development ?? 0,
                'total_after_deductions' => $kpiEvaluation->total_after_deductions,
                'notes' => $kpiEvaluation->notes,
                'created_at' => $kpiEvaluation->created_at->format('Y-m-d H:i'),
                'reviewer_name' => $kpiEvaluation->reviewer->name ?? 'غير محدد',
                'role_name' => $kpiEvaluation->role->display_name ?? $kpiEvaluation->role->name ?? 'غير محدد',
                'criteria' => $criteriaWithScores->map(function ($criterion) {
                    return [
                        'id' => $criterion->id ?? null,
                        'criteria_name' => $criterion->criteria_name ?? 'غير محدد',
                        'criteria_description' => $criterion->criteria_description ?? '',
                        'score' => (int) ($criterion->score ?? 0),
                        'max_points' => (int) ($criterion->max_points ?? 0),
                        'criteria_type' => $criterion->criteria_type ?? 'positive',
                        'category' => $criterion->category ?? null,
                        'note' => $criterion->note ?? null
                    ];
                })->toArray()
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحميل التفاصيل: ' . $e->getMessage()
            ], 500);
        }
    }


    public function getUserDetails(Request $request, $userId, $month)
    {
        try {
            $user = \App\Models\User::find($userId);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'المستخدم غير موجود'
                ], 404);
            }

            $currentUser = Auth::user();

            // الحصول على نوع التقييم من الـ request أو من آخر تقييم للموظف
            $evaluationType = $request->get('evaluation_type');

            if (!$evaluationType) {
                // إذا لم يتم تمرير نوع التقييم، نجيبه من آخر تقييم
                $latestEvaluation = KpiEvaluation::where('user_id', $userId)
                    ->whereRaw('DATE_FORMAT(review_month, "%Y-%m") = ?', [$month])
                    ->orderBy('created_at', 'desc')
                    ->first();

                $evaluationType = $latestEvaluation ? $latestEvaluation->evaluation_type : 'monthly';
            }

            // إنشاء arrays فارغة كـ default
            $revisions = [];
            $delayedProjects = [];
            $delayedTasks = [];
            $employeeErrors = [];
            $transferredTasks = [];

            try {
                $revisions = $this->getUserRevisions($userId, $month, $evaluationType);
            } catch (\Exception $e) {
                Log::error('Error getting user revisions: ' . $e->getMessage());
            }

            try {
                $delayedProjects = $this->getDelayedProjects($userId, $month, $evaluationType);
            } catch (\Exception $e) {
                Log::error('Error getting delayed projects: ' . $e->getMessage());
            }

            try {
                $delayedTasks = $this->getDelayedTasks($userId, $month, $evaluationType);
            } catch (\Exception $e) {
                Log::error('Error getting delayed tasks: ' . $e->getMessage());
            }

            try {
                $employeeErrors = $this->getUserErrors($userId, $month, $evaluationType);
            } catch (\Exception $e) {
                Log::error('Error getting user errors: ' . $e->getMessage());
            }

            try {
                $transferredTasks = $this->getTransferredTasks($userId, $month, $evaluationType);
            } catch (\Exception $e) {
                Log::error('Error getting transferred tasks: ' . $e->getMessage());
            }

            $deliveredProjects = [];
            try {
                $deliveredProjects = $this->getDeliveredProjects($userId, $month, $evaluationType);
            } catch (\Exception $e) {
                Log::error('Error getting delivered projects: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'revisions' => $revisions,
                    'delayed_projects' => $delayedProjects,
                    'delayed_tasks' => $delayedTasks,
                    'employee_errors' => $employeeErrors,
                    'transferred_tasks' => $transferredTasks,
                    'delivered_projects' => $deliveredProjects,
                    'evaluation_type' => $evaluationType,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'department' => $user->department
                    ],
                    'month' => $month
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getUserDetails: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحميل التفاصيل: ' . $e->getMessage()
            ], 500);
        }
    }


    private function getUserRevisions($userId, $month, $evaluationType = 'monthly')
    {
        try {
            // استخدام الفترة الصحيحة حسب نوع التقييم
            $period = \App\Models\KpiEvaluation::getEvaluationPeriod($month, $evaluationType);
            $startDate = $period['start'] . ' 00:00:00';
            $endDate = $period['end'] . ' 23:59:59';

            // جلب التعديلات التي المستخدم مسؤول عنها (هيتحاسب عليها)
            $revisions = \App\Models\TaskRevision::with(['creator', 'reviewer', 'responsibleUser', 'executorUser'])
                ->where('responsible_user_id', $userId)
                ->whereBetween('revision_date', [$startDate, $endDate])
                ->orderBy('revision_date', 'desc')
                ->get();

            return $revisions->map(function ($revision) {
                return [
                    'id' => $revision->id,
                    'title' => $revision->title ?? 'بدون عنوان',
                    'description' => $revision->description ?? '',
                    'notes' => $revision->notes ?? '',
                    'responsibility_notes' => $revision->responsibility_notes ?? '',
                    'status' => $revision->status ?? 'pending',
                    'status_text' => $revision->status_text ?? 'في الانتظار',
                    'status_color' => $revision->status_color ?? 'warning',
                    'revision_source' => $revision->revision_source ?? 'internal',
                    'revision_source_text' => $revision->revision_source_text ?? 'تعديل داخلي',
                    'revision_source_color' => $revision->revision_source_color ?? 'primary',
                    'revision_source_icon' => $revision->revision_source_icon ?? 'fas fa-users',
                    'revision_date' => $revision->revision_date ? $revision->revision_date->format('Y-m-d H:i') : now()->format('Y-m-d H:i'),
                    'creator_name' => $revision->creator->name ?? 'غير محدد',
                    'reviewer_name' => $revision->reviewer->name ?? null,
                    'responsible_user_name' => $revision->responsibleUser->name ?? 'غير محدد',
                    'executor_user_name' => $revision->executorUser->name ?? null,
                    'attachment_name' => $revision->attachment_name ?? null,
                    'attachment_url' => $revision->attachment_url ?? null,
                    'attachment_icon' => $revision->attachment_icon ?? 'fas fa-file',
                    'formatted_attachment_size' => $revision->formatted_attachment_size ?? null
                ];
            });
        } catch (\Exception $e) {
            Log::error('getUserRevisions error: ' . $e->getMessage());
            return collect([]);
        }
    }


    private function getDelayedProjects($userId, $month, $evaluationType = 'monthly')
    {
        try {
            // استخدام الفترة الصحيحة حسب نوع التقييم
            $period = \App\Models\KpiEvaluation::getEvaluationPeriod($month, $evaluationType);
            $startDate = $period['start'] . ' 00:00:00';
            $endDate = $period['end'] . ' 23:59:59';

            // البحث في project_service_user عن مشاريع الموظف المتأخرة
            $delayedProjectParticipations = \App\Models\ProjectServiceUser::with(['project.client'])
                ->where('user_id', $userId)
                ->whereNotNull('deadline')
                ->where(function ($query) use ($startDate, $endDate) {
                    // الحالة 1: سلّم متأخر (delivered_at > deadline)
                    // يظهر إذا: التسليم ضمن الفترة OR الديدلاين ضمن الفترة
                    $query->where(function ($q) use ($startDate, $endDate) {
                        $q->whereNotNull('delivered_at')
                            ->whereRaw('delivered_at > deadline')
                            ->where(function ($sq) use ($startDate, $endDate) {
                                $sq->whereBetween('delivered_at', [$startDate, $endDate])
                                    ->orWhereBetween('deadline', [$startDate, $endDate]);
                            });
                    })
                        // الحالة 2: لم يسلم بعد والديدلاين فات (لسه متأخر)
                        // يظهر إذا: الديدلاين ضمن الفترة OR الديدلاين فات وإحنا لسه في الفترة
                        ->orWhere(function ($q) use ($startDate, $endDate) {
                            $q->whereNull('delivered_at')
                                ->where('deadline', '<', now())
                                ->where(function ($sq) use ($startDate, $endDate) {
                                    $sq->whereBetween('deadline', [$startDate, $endDate])
                                        ->orWhere(function ($ssq) use ($startDate, $endDate) {
                                            // الديدلاين فات قبل الفترة لكن لسه مش مسلم
                                            $ssq->where('deadline', '<', $startDate)
                                                ->where(DB::raw("'$endDate'"), '>=', DB::raw('CURDATE()'));
                                        });
                                });
                        });
                })
                ->get();

            return $delayedProjectParticipations->map(function ($participation) {
                $delayDays = 0;
                $deliveryDate = null;
                $isDelivered = !is_null($participation->delivered_at);

                if ($participation->deadline) {
                    if ($isDelivered) {
                        // حساب التأخير بناءً على تاريخ التسليم الفعلي
                        $delayDays = \Carbon\Carbon::parse($participation->deadline)
                            ->diffInDays(\Carbon\Carbon::parse($participation->delivered_at), false);
                        $deliveryDate = \Carbon\Carbon::parse($participation->delivered_at)->format('Y-m-d');
                    } else {
                        // لم يسلم بعد - حساب التأخير حتى الآن
                        $delayDays = \Carbon\Carbon::parse($participation->deadline)
                            ->diffInDays(now(), false);
                        $deliveryDate = 'لم يسلم بعد';
                    }
                }

                return [
                    'project_id' => $participation->project->id ?? null,
                    'project_name' => $participation->project->name ?? 'مشروع بدون اسم',
                    'client_name' => $participation->project->client->name ?? 'غير محدد',
                    'deadline' => $participation->deadline ? \Carbon\Carbon::parse($participation->deadline)->format('Y-m-d') : null,
                    'delivery_date' => $deliveryDate,
                    'delay_days' => abs($delayDays), // القيمة المطلقة لعدد الأيام
                    'status' => $participation->project->status ?? 'غير محدد',
                    'is_delivered' => $isDelivered,
                ];
            });
        } catch (\Exception $e) {
            Log::error('getDelayedProjects error: ' . $e->getMessage());
            return collect([]);
        }
    }


    private function getDelayedTasks($userId, $month, $evaluationType = 'monthly')
    {
        try {
            // استخدام الفترة الصحيحة حسب نوع التقييم
            $period = \App\Models\KpiEvaluation::getEvaluationPeriod($month, $evaluationType);
            $startDate = $period['start'] . ' 00:00:00';
            $endDate = $period['end'] . ' 23:59:59';

            $delayedTasks = collect();

            // المهام العادية المتأخرة (استبعاد المنقولة)
            try {
                $regularTasks = \App\Models\TaskUser::with(['task'])
                    ->where('user_id', $userId)
                    ->where('is_transferred', '!=', 1) // استبعاد المهام المنقولة
                    ->whereNotNull('due_date')
                    ->where(function ($query) use ($startDate, $endDate) {
                        // الحالة 1: اكتملت متأخرة (completed_date > due_date)
                        $query->where(function ($q) use ($startDate, $endDate) {
                            $q->where('status', 'completed')
                                ->whereNotNull('completed_date')
                                ->whereRaw('completed_date > due_date')
                                ->where(function ($sq) use ($startDate, $endDate) {
                                    // تظهر إذا: الإكمال ضمن الفترة OR الـ due_date ضمن الفترة
                                    $sq->whereBetween('completed_date', [$startDate, $endDate])
                                        ->orWhereBetween('due_date', [$startDate, $endDate]);
                                });
                        })
                            // الحالة 2: لم تكتمل بعد والـ due_date فات
                            ->orWhere(function ($q) use ($startDate, $endDate) {
                                $q->where('status', '!=', 'completed')
                                    ->where('due_date', '<', now())
                                    ->where(function ($sq) use ($startDate, $endDate) {
                                        $sq->whereBetween('due_date', [$startDate, $endDate])
                                            ->orWhere(function ($ssq) use ($startDate, $endDate) {
                                                // الـ due_date فات قبل الفترة لكن لسه مش مكتملة
                                                $ssq->where('due_date', '<', $startDate)
                                                    ->where(DB::raw("'$endDate'"), '>=', DB::raw('CURDATE()'));
                                            });
                                    });
                            });
                    })
                    ->get();

                foreach ($regularTasks as $taskUser) {
                    $delayHours = 0;
                    $completedDate = null;
                    $isCompleted = $taskUser->status === 'completed' && $taskUser->completed_date;

                    if ($taskUser->due_date) {
                        if ($isCompleted) {
                            // حساب التأخير بناءً على تاريخ الإكمال الفعلي
                            $delayHours = \Carbon\Carbon::parse($taskUser->due_date)
                                ->diffInHours(\Carbon\Carbon::parse($taskUser->completed_date), false);
                            $completedDate = \Carbon\Carbon::parse($taskUser->completed_date)->format('Y-m-d H:i');
                        } else {
                            // لم تكتمل بعد - حساب التأخير حتى الآن
                            $delayHours = \Carbon\Carbon::parse($taskUser->due_date)
                                ->diffInHours(now(), false);
                            $completedDate = 'لم تكتمل بعد';
                        }
                    }

                    $delayedTasks->push([
                        'task_id' => $taskUser->task ? $taskUser->task->id : 0,
                        'task_name' => $taskUser->task ? $taskUser->task->name : 'مهمة محذوفة',
                        'task_type' => 'regular',
                        'due_date' => $taskUser->due_date ? \Carbon\Carbon::parse($taskUser->due_date)->format('Y-m-d H:i') : null,
                        'completed_date' => $completedDate,
                        'delay_hours' => abs($delayHours),
                        'project_name' => null,
                        'is_completed' => $isCompleted
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Error getting regular delayed tasks: ' . $e->getMessage());
            }

            // مهام القوالب المتأخرة (استبعاد المنقولة)
            try {
                $templateTasks = \App\Models\TemplateTaskUser::with(['templateTask'])
                    ->where('user_id', $userId)
                    ->where('is_transferred', '!=', 1) // استبعاد المهام المنقولة
                    ->whereNotNull('deadline')
                    ->where(function ($query) use ($startDate, $endDate) {
                        // الحالة 1: اكتملت متأخرة (completed_at > deadline)
                        $query->where(function ($q) use ($startDate, $endDate) {
                            $q->where('status', 'completed')
                                ->whereNotNull('completed_at')
                                ->whereRaw('completed_at > deadline')
                                ->where(function ($sq) use ($startDate, $endDate) {
                                    // تظهر إذا: الإكمال ضمن الفترة OR الـ deadline ضمن الفترة
                                    $sq->whereBetween('completed_at', [$startDate, $endDate])
                                        ->orWhereBetween('deadline', [$startDate, $endDate]);
                                });
                        })
                            // الحالة 2: لم تكتمل بعد والـ deadline فات
                            ->orWhere(function ($q) use ($startDate, $endDate) {
                                $q->where('status', '!=', 'completed')
                                    ->where('deadline', '<', now())
                                    ->where(function ($sq) use ($startDate, $endDate) {
                                        $sq->whereBetween('deadline', [$startDate, $endDate])
                                            ->orWhere(function ($ssq) use ($startDate, $endDate) {
                                                // الـ deadline فات قبل الفترة لكن لسه مش مكتملة
                                                $ssq->where('deadline', '<', $startDate)
                                                    ->where(DB::raw("'$endDate'"), '>=', DB::raw('CURDATE()'));
                                            });
                                    });
                            });
                    })
                    ->get();

                foreach ($templateTasks as $templateTaskUser) {
                    $delayHours = 0;
                    $completedDate = null;
                    $isCompleted = $templateTaskUser->status === 'completed' && $templateTaskUser->completed_at;

                    if ($templateTaskUser->deadline) {
                        if ($isCompleted) {
                            // حساب التأخير بناءً على تاريخ الإكمال الفعلي
                            $delayHours = \Carbon\Carbon::parse($templateTaskUser->deadline)
                                ->diffInHours(\Carbon\Carbon::parse($templateTaskUser->completed_at), false);
                            $completedDate = \Carbon\Carbon::parse($templateTaskUser->completed_at)->format('Y-m-d H:i');
                        } else {
                            // لم تكتمل بعد - حساب التأخير حتى الآن
                            $delayHours = \Carbon\Carbon::parse($templateTaskUser->deadline)
                                ->diffInHours(now(), false);
                            $completedDate = 'لم تكتمل بعد';
                        }
                    }

                    $delayedTasks->push([
                        'task_id' => $templateTaskUser->templateTask ? $templateTaskUser->templateTask->id : 0,
                        'task_name' => $templateTaskUser->templateTask ? $templateTaskUser->templateTask->name : 'مهمة قالب محذوفة',
                        'task_type' => 'template',
                        'due_date' => $templateTaskUser->deadline ? \Carbon\Carbon::parse($templateTaskUser->deadline)->format('Y-m-d H:i') : null,
                        'completed_date' => $completedDate,
                        'delay_hours' => abs($delayHours),
                        'project_name' => null,
                        'is_completed' => $isCompleted
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Error getting template delayed tasks: ' . $e->getMessage());
            }

            return $delayedTasks->sortByDesc('delay_hours')->values();
        } catch (\Exception $e) {
            Log::error('getDelayedTasks error: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * جلب المهام المنقولة من الموظف في شهر محدد
     */
    private function getTransferredTasks($userId, $month, $evaluationType = 'monthly')
    {
        try {
            // استخدام الفترة الصحيحة حسب نوع التقييم
            $period = \App\Models\KpiEvaluation::getEvaluationPeriod($month, $evaluationType);
            $startDate = $period['start'] . ' 00:00:00';
            $endDate = $period['end'] . ' 23:59:59';

            $transferredTasks = collect();

            // المهام العادية المنقولة
            try {
                $regularTransferred = \App\Models\TaskUser::with(['task', 'transferredToUser'])
                    ->where('user_id', $userId)
                    ->where('is_transferred', true)
                    ->whereBetween('transferred_from_at', [$startDate, $endDate])
                    ->get();

                foreach ($regularTransferred as $taskUser) {
                    $transferredTasks->push([
                        'task_id' => $taskUser->task ? $taskUser->task->id : 0,
                        'task_name' => $taskUser->task ? $taskUser->task->name : 'مهمة محذوفة',
                        'task_type' => 'regular',
                        'transferred_to' => $taskUser->transferredToUser ? $taskUser->transferredToUser->name : 'غير محدد',
                        'transfer_reason' => $taskUser->transfer_reason ?? 'بدون سبب',
                        'transferred_at' => $taskUser->transferred_from_at ? \Carbon\Carbon::parse($taskUser->transferred_from_at)->format('Y-m-d H:i') : null,
                        'due_date' => $taskUser->due_date ? \Carbon\Carbon::parse($taskUser->due_date)->format('Y-m-d H:i') : null,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Error getting regular transferred tasks: ' . $e->getMessage());
            }

            // مهام القوالب المنقولة
            try {
                $templateTransferred = \App\Models\TemplateTaskUser::with(['templateTask'])
                    ->where('user_id', $userId)
                    ->where('is_transferred', true)
                    ->whereBetween('transferred_from_at', [$startDate, $endDate])
                    ->get();

                foreach ($templateTransferred as $templateTaskUser) {
                    $transferredTasks->push([
                        'task_id' => $templateTaskUser->templateTask ? $templateTaskUser->templateTask->id : 0,
                        'task_name' => $templateTaskUser->templateTask ? $templateTaskUser->templateTask->name : 'مهمة قالب محذوفة',
                        'task_type' => 'template',
                        'transferred_to' => 'غير محدد', // Template tasks may not have transferred_to_user
                        'transfer_reason' => $templateTaskUser->transfer_reason ?? 'بدون سبب',
                        'transferred_at' => $templateTaskUser->transferred_from_at ? \Carbon\Carbon::parse($templateTaskUser->transferred_from_at)->format('Y-m-d H:i') : null,
                        'due_date' => $templateTaskUser->deadline ? \Carbon\Carbon::parse($templateTaskUser->deadline)->format('Y-m-d H:i') : null,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Error getting template transferred tasks: ' . $e->getMessage());
            }

            return $transferredTasks->sortByDesc('transferred_at')->values()->toArray();
        } catch (\Exception $e) {
            Log::error('getTransferredTasks error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * جلب أخطاء الموظف في شهر محدد
     */
    private function getUserErrors($userId, $month, $evaluationType = 'monthly')
    {
        try {
            // استخدام الفترة الصحيحة حسب نوع التقييم
            $period = \App\Models\KpiEvaluation::getEvaluationPeriod($month, $evaluationType);
            $startDate = $period['start'] . ' 00:00:00';
            $endDate = $period['end'] . ' 23:59:59';

            $errors = \App\Models\EmployeeError::with(['reportedBy'])
                ->where('user_id', $userId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->orderBy('error_type', 'desc') // الجوهرية أولاً
                ->orderBy('created_at', 'desc')
                ->get();

            return $errors->map(function ($error) {
                return [
                    'id' => $error->id,
                    'title' => $error->title ?? 'خطأ بدون عنوان',
                    'description' => $error->description ?? '',
                    'error_type' => $error->error_type,
                    'error_type_text' => $error->error_type_text,
                    'error_category' => $error->error_category,
                    'error_category_text' => $error->error_category_text,
                    'error_color' => $error->error_color,
                    'reported_by' => $error->reportedBy ? $error->reportedBy->name : 'غير محدد',
                    'created_at' => $error->created_at->format('Y-m-d H:i'),
                    'created_at_human' => $error->created_at->locale('ar')->diffForHumans()
                ];
            })->toArray();
        } catch (\Exception $e) {
            Log::error('Error in getUserErrors: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * جلب المشاريع المسلّمة في شهر محدد
     */
    private function getDeliveredProjects($userId, $month, $evaluationType = 'monthly')
    {
        try {
            // استخدام الفترة الصحيحة حسب نوع التقييم
            $period = \App\Models\KpiEvaluation::getEvaluationPeriod($month, $evaluationType);
            $startDate = $period['start'] . ' 00:00:00';
            $endDate = $period['end'] . ' 23:59:59';

            $deliveredProjects = \App\Models\ProjectServiceUser::with([
                'project.client',
                'service',
                'administrativeApprover',
                'technicalApprover'
            ])
                ->where('user_id', $userId)
                ->whereNotNull('delivered_at')
                ->whereBetween('delivered_at', [$startDate, $endDate])
                ->orderBy('delivered_at', 'desc')
                ->get();

            return $deliveredProjects->map(function ($participation) {
                // الحصول على الاعتمادات المطلوبة
                $requiredApprovals = $participation->getRequiredApprovals();

                // ملاحظة الاعتماد الإداري (إن وجدت أو مطلوبة)
                $administrativeNote = null;
                if ($requiredApprovals['needs_administrative']) {
                    $administrativeNote = $participation->administrative_notes ?? 'لا يوجد';
                }

                // ملاحظة الاعتماد الفني (إن وجدت أو مطلوبة)
                $technicalNote = null;
                if ($requiredApprovals['needs_technical']) {
                    $technicalNote = $participation->technical_notes ?? 'لا يوجد';
                }

                return [
                    'project_id' => $participation->project->id ?? null,
                    'project_name' => $participation->project->name ?? 'مشروع بدون اسم',
                    'project_code' => $participation->project->code ?? null,
                    'client_name' => $participation->project->client->name ?? 'غير محدد',
                    'service_name' => $participation->service->name ?? 'غير محدد',
                    'delivered_at' => $participation->delivered_at ? $participation->delivered_at->format('Y-m-d H:i') : null,
                    'delivered_at_formatted' => $participation->delivered_at ? $participation->delivered_at->locale('ar')->translatedFormat('d M Y - H:i') : null,
                    'needs_administrative' => $requiredApprovals['needs_administrative'],
                    'needs_technical' => $requiredApprovals['needs_technical'],
                    'has_administrative_approval' => $participation->hasAdministrativeApproval(),
                    'has_technical_approval' => $participation->hasTechnicalApproval(),
                    'administrative_approval_at' => $participation->administrative_approval_at ? $participation->administrative_approval_at->format('Y-m-d H:i') : null,
                    'technical_approval_at' => $participation->technical_approval_at ? $participation->technical_approval_at->format('Y-m-d H:i') : null,
                    'administrative_approver_name' => $participation->administrativeApprover->name ?? null,
                    'technical_approver_name' => $participation->technicalApprover->name ?? null,
                    'administrative_note' => $administrativeNote,
                    'technical_note' => $technicalNote,
                    'has_all_approvals' => $participation->hasAllRequiredApprovals()
                ];
            })->toArray();
        } catch (\Exception $e) {
            Log::error('Error in getDeliveredProjects: ' . $e->getMessage());
            return [];
        }
    }
}
