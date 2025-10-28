<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Models\User;
use App\Models\DepartmentRole;
use App\Models\RoleHierarchy;

class DepartmentRoleController extends Controller
{
    /**
     * Display a listing of department roles.
     */
    public function index()
    {
        // الحصول على جميع الأقسام المتاحة
        $departments = User::getAvailableDepartments();

        // الحصول على جميع الأدوار
        $roles = Role::all();

        // الحصول على الروابط الحالية
        $departmentRoles = DepartmentRole::getDepartmentRolesGrouped();

        return view('admin.department-roles.index', compact('departments', 'roles', 'departmentRoles'));
    }

    /**
     * Show the form for creating a new department role mapping.
     */
    public function create()
    {
        $departments = User::getAvailableDepartments();
        $roles = Role::all();
        $hierarchyLevels = DepartmentRole::getHierarchyLevels();

        return view('admin.department-roles.create', compact('departments', 'roles', 'hierarchyLevels'));
    }

    /**
     * Store a newly created department role mapping.
     */
    public function store(Request $request)
    {
        $request->validate([
            'department_name' => 'required|string|max:255',
            'role_ids' => 'required|array|min:1',
            'role_ids.*' => 'required|exists:roles,id',
        ]);

        $errors = [];
        $successCount = 0;

        foreach ($request->role_ids as $roleId) {
            // الحصول على المستوى الهرمي التلقائي من جدول role_hierarchy
            $hierarchyLevel = RoleHierarchy::getRoleHierarchyLevel($roleId);

            if (!$hierarchyLevel) {
                $role = Role::find($roleId);
                $errors[] = "الدور '{$role->name}' ليس له مستوى هرمي محدد في النظام";
                continue;
            }

            // التحقق من عدم وجود الربط مسبقاً
            if (DepartmentRole::mappingExists($request->department_name, $roleId)) {
                $role = Role::find($roleId);
                $errors[] = "الدور '{$role->name}' مربوط بالفعل بهذا القسم";
                continue;
            }

            // إدراج الربط الجديد بالمستوى الهرمي التلقائي
            DepartmentRole::create([
                'department_name' => $request->department_name,
                'role_id' => $roleId,
                'hierarchy_level' => $hierarchyLevel,
            ]);

            $successCount++;
        }

        // Handle AJAX requests
        if ($request->ajax() || $request->expectsJson()) {
            if ($successCount > 0 && empty($errors)) {
                return response()->json([
                    'success' => true,
                    'message' => "تم ربط {$successCount} دور بنجاح"
                ]);
            } elseif ($successCount > 0 && !empty($errors)) {
                return response()->json([
                    'success' => true,
                    'message' => "تم ربط {$successCount} دور بنجاح",
                    'warnings' => $errors
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم ربط أي دور',
                    'errors' => $errors
                ], 400);
            }
        }

        if ($successCount > 0) {
            $message = "تم ربط {$successCount} دور بالقسم بنجاح";
            if (!empty($errors)) {
                $message .= ". بعض الأدوار لم يتم ربطها: " . implode(', ', $errors);
            }
            return redirect()->route('department-roles.index')->with('success', $message);
        } else {
            return redirect()->back()
                           ->withErrors($errors)
                           ->withInput();
        }
    }

    /**
     * Show the form for editing all roles of a specific department.
     */
    public function edit($departmentName)
    {
        // التحقق من وجود القسم
        $departments = User::getAvailableDepartments();
        if (!in_array($departmentName, $departments->toArray())) {
            return redirect()->route('department-roles.index')
                           ->withErrors(['error' => 'القسم غير موجود']);
        }

        // الحصول على جميع أدوار القسم
        $departmentRoles = DepartmentRole::with('role')
                                        ->where('department_name', $departmentName)
                                        ->orderBy('hierarchy_level', 'desc')
                                        ->get();

        // الحصول على جميع الأدوار المتاحة
        $allRoles = Role::all();

        // الحصول على الأدوار غير المرتبطة بالقسم
        $linkedRoleIds = $departmentRoles->pluck('role_id')->toArray();
        $availableRoles = $allRoles->whereNotIn('id', $linkedRoleIds);

        return view('admin.department-roles.edit', compact(
            'departmentName',
            'departmentRoles',
            'allRoles',
            'availableRoles',
            'departments'
        ));
    }

    /**
     * Update the specified department role mapping.
     */
    public function update(Request $request, $departmentName)
    {
        $request->validate([
            'existing_roles' => 'nullable|array',
            'existing_roles.*.id' => 'required|exists:department_roles,id',
            'new_roles' => 'nullable|array',
            'new_roles.*.role_id' => 'required|exists:roles,id',
            'deleted_roles' => 'nullable|array',
            'deleted_roles.*' => 'exists:department_roles,id',
        ]);

        try {
            DB::beginTransaction();

            $updatedCount = 0;
            $addedCount = 0;
            $deletedCount = 0;

            // تحديث الأدوار الموجودة (لا نحتاج تحديث hierarchy_level لأنه تلقائي الآن)
            if ($request->has('existing_roles')) {
                foreach ($request->existing_roles as $existingRole) {
                    $departmentRole = DepartmentRole::find($existingRole['id']);
                    if ($departmentRole) {
                        // الحصول على المستوى الهرمي التلقائي الحديث
                        $hierarchyLevel = RoleHierarchy::getRoleHierarchyLevel($departmentRole->role_id);
                        if ($hierarchyLevel) {
                            $departmentRole->update([
                                'hierarchy_level' => $hierarchyLevel
                            ]);
                            $updatedCount++;
                        }
                    }
                }
            }

            if ($request->has('new_roles')) {
                foreach ($request->new_roles as $newRole) {

                    if (!DepartmentRole::mappingExists($departmentName, $newRole['role_id'])) {
                        // الحصول على المستوى الهرمي التلقائي
                        $hierarchyLevel = RoleHierarchy::getRoleHierarchyLevel($newRole['role_id']);

                        // استخدام قيمة افتراضية إذا لم يتم العثور على مستوى هرمي
                        if (!$hierarchyLevel) {
                            $hierarchyLevel = 1; // قيمة افتراضية
                        }

                        DepartmentRole::create([
                            'department_name' => $departmentName,
                            'role_id' => $newRole['role_id'],
                            'hierarchy_level' => $hierarchyLevel,
                        ]);
                        $addedCount++;
                    }
                }
            }

            // حذف الأدوار المحددة
            if ($request->has('deleted_roles')) {
                $deleted = DepartmentRole::whereIn('id', $request->deleted_roles)->delete();
                $deletedCount = $deleted;
            }

            DB::commit();

            $message = "تم تحديث أدوار القسم بنجاح. ";
            if ($updatedCount > 0) $message .= "محدث: {$updatedCount}, ";
            if ($addedCount > 0) $message .= "مضاف: {$addedCount}, ";
            if ($deletedCount > 0) $message .= "محذوف: {$deletedCount}";

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => $message]);
            }

            return redirect()->route('department-roles.index')
                            ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'حدث خطأ: ' . $e->getMessage()], 500);
            }

            return redirect()->back()
                           ->withErrors(['error' => 'حدث خطأ أثناء التحديث: ' . $e->getMessage()])
                           ->withInput();
        }
    }

    /**
     * Display the specified department details.
     */
    public function show($departmentName)
    {
        // الحصول على أدوار القسم
        $roles = DepartmentRole::getDepartmentRoles($departmentName);

        // الحصول على موظفي القسم
        $users = User::where('department', $departmentName)->get();

        return view('admin.department-roles.show', compact('departmentName', 'roles', 'users'));
    }

    /**
     * Remove the specified department role mapping.
     */
    public function destroy($id)
    {
        $departmentRole = DepartmentRole::find($id);

        if ($departmentRole && $departmentRole->delete()) {
            return redirect()->route('department-roles.index')
                           ->with('success', 'تم حذف الربط بنجاح');
        }

        return redirect()->route('department-roles.index')
                        ->with('error', 'فشل في حذف الربط');
    }

    /**
     * عرض صفحة إدارة ترتيب الأدوار
     */
    public function manageHierarchy()
    {
        $rolesWithHierarchy = RoleHierarchy::getAllRolesWithHierarchy();
        $allRoles = Role::all();
        $rolesWithoutHierarchy = $allRoles->whereNotIn('id', $rolesWithHierarchy->pluck('role_id'));

        return view('admin.department-roles.manage-hierarchy', compact(
            'rolesWithHierarchy',
            'rolesWithoutHierarchy'
        ));
    }

    /**
     * حفظ ترتيب الأدوار
     */
    public function saveHierarchy(Request $request)
    {
        $request->validate([
            'roles' => 'required|array',
            'roles.*.role_id' => 'required|exists:roles,id',
            'roles.*.hierarchy_level' => 'required|integer|min:1|max:100',
            'roles.*.description' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            foreach ($request->roles as $roleData) {
                RoleHierarchy::updateOrCreate(
                    ['role_id' => $roleData['role_id']],
                    [
                        'hierarchy_level' => $roleData['hierarchy_level'],
                        'description' => $roleData['description'] ?? null,
                    ]
                );
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم حفظ ترتيب الأدوار بنجاح'
                ]);
            }

            return redirect()->route('department-roles.manage-hierarchy')
                           ->with('success', 'تم حفظ ترتيب الأدوار بنجاح');

        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                           ->withErrors(['error' => 'حدث خطأ: ' . $e->getMessage()]);
        }
    }
}
