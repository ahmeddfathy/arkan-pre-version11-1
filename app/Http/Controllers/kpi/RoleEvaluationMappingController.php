<?php

namespace App\Http\Controllers\Kpi;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\RoleEvaluationMapping;
use App\Models\DepartmentRole;
use App\Models\EvaluationCriteria;
use App\Models\CriteriaEvaluatorRole;
use Spatie\Permission\Models\Role;


class RoleEvaluationMappingController extends Controller
{
    /**
     * عرض خريطة التقييم
     */
    public function index(Request $request)
    {
        $selectedDepartment = $request->get('department');

        // الحصول على الأقسام المتاحة
        $departments = DepartmentRole::select('department_name')
            ->distinct()
            ->pluck('department_name');

        // الحصول على خريطة التقييم
        $evaluationMatrix = RoleEvaluationMapping::getEvaluationMatrix($selectedDepartment);

        $roles = Role::all();

        return view('kpi.role-evaluation-mapping.index', compact(
            'evaluationMatrix',
            'departments',
            'selectedDepartment',
            'roles'
        ));
    }

    /**
     * عرض نموذج إضافة ربط جديد
     */
    public function create()
    {
        $roles = Role::all();
        $departments = DepartmentRole::select('department_name')
            ->distinct()
            ->pluck('department_name');

        return view('kpi.role-evaluation-mapping.create', compact('roles', 'departments'));
    }

    /**
     * حفظ ربط جديد
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'role_to_evaluate_id' => 'required|exists:roles,id',
            'department_name' => 'required|string',
            'evaluators' => 'required|array|min:1',
            'evaluators.*.role_id' => 'required|exists:roles,id',
            'evaluators.*.can_evaluate' => 'boolean',
            'evaluators.*.can_view' => 'boolean',
            'evaluators.*.criteria_ids' => 'array',
            'evaluators.*.criteria_ids.*' => 'exists:evaluation_criteria,id',
        ]);

        try {
            $createdMappings = [];

            foreach ($validated['evaluators'] as $index => $evaluatorData) {
                // التحقق من عدم وجود ربط مكرر
                $exists = RoleEvaluationMapping::where('role_to_evaluate_id', $validated['role_to_evaluate_id'])
                    ->where('evaluator_role_id', $evaluatorData['role_id'])
                    ->where('department_name', $validated['department_name'])
                    ->exists();

                if ($exists) {
                    // إذا كان الربط موجود، نتجاهله ونكمل
                    continue;
                }

                // إنشاء الربط الأساسي
                $mappingData = [
                    'role_to_evaluate_id' => $validated['role_to_evaluate_id'],
                    'evaluator_role_id' => $evaluatorData['role_id'],
                    'department_name' => $validated['department_name'],
                    'can_evaluate' => isset($evaluatorData['can_evaluate']) && $evaluatorData['can_evaluate'] ? 1 : 0,
                    'can_view' => isset($evaluatorData['can_view']) && $evaluatorData['can_view'] ? 1 : 0,
                ];

                $mapping = RoleEvaluationMapping::create($mappingData);
                $createdMappings[] = $mapping;

                // ربط البنود إذا تم تحديدها
                if (!empty($evaluatorData['criteria_ids'])) {
                    foreach ($evaluatorData['criteria_ids'] as $criteriaId) {
                        CriteriaEvaluatorRole::create([
                            'criteria_id' => $criteriaId,
                            'evaluator_role_id' => $evaluatorData['role_id'],
                            'department_name' => $validated['department_name'],
                        ]);
                    }
                }
            }

            if (empty($createdMappings)) {
                return back()->withErrors(['error' => 'جميع الروابط المحددة موجودة بالفعل']);
            }

            return redirect()->route('role-evaluation-mapping.index')
                ->with('success', 'تم إضافة ' . count($createdMappings) . ' ربط بنجاح');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'حدث خطأ أثناء حفظ البيانات: ' . $e->getMessage()]);
        }
    }

    /**
     * عرض تفاصيل ربط
     */
    public function show(RoleEvaluationMapping $roleEvaluationMapping)
    {
        // الحصول على البنود المربوطة بهذا الربط
        $linkedCriteria = CriteriaEvaluatorRole::with(['criteria'])
            ->where('evaluator_role_id', $roleEvaluationMapping->evaluator_role_id)
            ->where('department_name', $roleEvaluationMapping->department_name)
            ->whereHas('criteria', function($q) use ($roleEvaluationMapping) {
                $q->where('role_id', $roleEvaluationMapping->role_to_evaluate_id);
            })
            ->get();

        return view('kpi.role-evaluation-mapping.show', compact('roleEvaluationMapping', 'linkedCriteria'));
    }

    /**
     * عرض نموذج تعديل ربط
     */
    public function edit(RoleEvaluationMapping $roleEvaluationMapping)
    {
        $roles = Role::all();
        $departments = DepartmentRole::select('department_name')
            ->distinct()
            ->pluck('department_name');

        $linkedCriteria = CriteriaEvaluatorRole::with(['criteria'])
            ->where('evaluator_role_id', $roleEvaluationMapping->evaluator_role_id)
            ->where('department_name', $roleEvaluationMapping->department_name)
            ->whereHas('criteria', function($q) use ($roleEvaluationMapping) {
                $q->where('role_id', $roleEvaluationMapping->role_to_evaluate_id);
            })
            ->get();

        return view('kpi.role-evaluation-mapping.edit', compact(
            'roleEvaluationMapping',
            'roles',
            'departments',
            'linkedCriteria'
        ));
    }


    public function update(Request $request, RoleEvaluationMapping $roleEvaluationMapping)
    {
        $validated = $request->validate([
            'role_to_evaluate_id' => 'required|exists:roles,id',
            'evaluator_role_id' => 'required|exists:roles,id',
            'department_name' => 'required|string',
            'can_evaluate' => 'boolean',
            'can_view' => 'boolean',
            'criteria_ids' => 'array',
            'criteria_ids.*' => 'exists:evaluation_criteria,id',
        ]);

        $validated['can_evaluate'] = $request->has('can_evaluate');
        $validated['can_view'] = $request->has('can_view');

        try {
            // تحديث بيانات الربط الأساسية
            $roleEvaluationMapping->update($validated);

            // حذف جميع البنود المربوطة القديمة
            CriteriaEvaluatorRole::where('evaluator_role_id', $roleEvaluationMapping->evaluator_role_id)
                ->where('department_name', $roleEvaluationMapping->department_name)
                ->whereHas('criteria', function($q) use ($roleEvaluationMapping) {
                    $q->where('role_id', $roleEvaluationMapping->role_to_evaluate_id);
                })
                ->delete();

            // إضافة البنود الجديدة المحددة
            if (!empty($validated['criteria_ids'])) {
                foreach ($validated['criteria_ids'] as $criteriaId) {
                    CriteriaEvaluatorRole::create([
                        'criteria_id' => $criteriaId,
                        'evaluator_role_id' => $roleEvaluationMapping->evaluator_role_id,
                        'department_name' => $roleEvaluationMapping->department_name,
                    ]);
                }
            }

            return redirect()->route('role-evaluation-mapping.index')
                ->with('success', 'تم تحديث الربط بنجاح');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'حدث خطأ أثناء تحديث البيانات: ' . $e->getMessage()]);
        }
    }


    public function destroy(RoleEvaluationMapping $roleEvaluationMapping)
    {
        $roleEvaluationMapping->delete();

        return redirect()->route('role-evaluation-mapping.index')
            ->with('success', 'تم حذف الربط بنجاح');
    }


    public function getRolesCanEvaluate(Request $request)
    {
        $evaluatorRoleId = $request->get('evaluator_role_id');
        $departmentName = $request->get('department_name');

        if (!$evaluatorRoleId) {
            return response()->json(['error' => 'Evaluator role ID is required'], 400);
        }

        $roles = RoleEvaluationMapping::getRolesCanEvaluate($evaluatorRoleId, $departmentName);

        return response()->json([
            'success' => true,
            'roles' => $roles->map(function($mapping) {
                return [
                    'id' => $mapping->roleToEvaluate->id,
                    'name' => $mapping->roleToEvaluate->name,
                    'display_name' => $mapping->roleToEvaluate->display_name ?? $mapping->roleToEvaluate->name,
                ];
            })
        ]);
    }


    public function getCriteriaByRole(Request $request)
    {
        $roleId = $request->get('role_id');

        if (!$roleId) {
            return response()->json(['error' => 'معرف الدور مطلوب'], 400);
        }

        $criteria = EvaluationCriteria::where('role_id', $roleId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('criteria_name')
            ->get()
            ->groupBy('criteria_type');

        return response()->json([
            'success' => true,
            'criteria' => $criteria,
            'total_criteria' => EvaluationCriteria::where('role_id', $roleId)->where('is_active', true)->count(),
        ]);
    }


    public function getRolesByDepartment(Request $request)
    {
        $departmentName = $request->get('department_name');

        if (!$departmentName) {
            return response()->json(['error' => 'اسم القسم مطلوب'], 400);
        }

        $departmentRoles = DepartmentRole::where('department_name', $departmentName)
            ->with(['role'])
            ->get();

        $roles = $departmentRoles->map(function ($departmentRole) {
            return [
                'id' => $departmentRole->role->id,
                'name' => $departmentRole->role->name,
                'display_name' => $departmentRole->role->display_name ?? $departmentRole->role->name,
            ];
        });

        $uniqueRoles = $roles->unique('id')->values();

        return response()->json([
            'success' => true,
            'roles' => $uniqueRoles,
            'total_roles' => $uniqueRoles->count(),
        ]);
    }

}
