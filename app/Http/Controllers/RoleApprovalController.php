<?php

namespace App\Http\Controllers;

use App\Models\RoleApproval;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;

class RoleApprovalController extends Controller
{
    /**
     * عرض قائمة قواعد الاعتماد
     */
    public function index()
    {
        // التحقق من أن المستخدم له صلاحية HR فقط
        if (!Auth::user()->hasRole('hr')) {
            abort(403, 'غير مصرح لك بالوصول لهذه الصفحة');
        }

        // جلب قواعد الاعتماد مع العلاقات
        $roleApprovals = RoleApproval::with(['role', 'approverRole'])->get();

        // جلب أدوار الأقسام
        $departmentRoles = \App\Models\DepartmentRole::with('role')->get()->groupBy('department_name');

        // إنشاء mapping للأدوار والأقسام
        $roleDepartmentMap = [];
        foreach ($departmentRoles as $departmentName => $roles) {
            foreach ($roles as $departmentRole) {
                $roleDepartmentMap[$departmentRole->role_id] = $departmentName;
            }
        }

        // تجميع البيانات حسب القسم
        $approvalsByDepartment = [];
        foreach ($roleApprovals as $approval) {
            $departmentName = $roleDepartmentMap[$approval->role_id] ?? 'عام';
            $approvalsByDepartment[$departmentName][] = $approval;
        }

        $roles = Role::orderBy('name')->get();

        return view('admin.role-approvals.index', compact('roleApprovals', 'roles', 'approvalsByDepartment'));
    }

    /**
     * إنشاء قاعدة اعتماد جديدة
     */
    public function store(Request $request)
    {
        // التحقق من أن المستخدم له صلاحية HR فقط
        if (!Auth::user()->hasRole('hr')) {
            abort(403, 'غير مصرح لك بالوصول لهذه الصفحة');
        }

        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'approver_role_id' => 'required|exists:roles,id|different:role_id',
            'approval_type' => 'required|in:administrative,technical',
            'description' => 'nullable|string|max:255',
            'requires_same_project' => 'boolean',
        ]);

        // فحص عدم وجود قاعدة مماثلة
        $exists = RoleApproval::where('role_id', $request->role_id)
            ->where('approver_role_id', $request->approver_role_id)
            ->where('approval_type', $request->approval_type)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'قاعدة الاعتماد هذه موجودة بالفعل'
            ]);
        }

        $roleApproval = RoleApproval::create([
            'role_id' => $request->role_id,
            'approver_role_id' => $request->approver_role_id,
            'approval_type' => $request->approval_type,
            'description' => $request->description,
            'requires_same_project' => $request->boolean('requires_same_project', false),
            'requires_team_owner' => $request->boolean('requires_team_owner', false),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء قاعدة الاعتماد بنجاح',
            'data' => $roleApproval->load(['role', 'approverRole'])
        ]);
    }

    /**
     * تحديث قاعدة اعتماد
     */
    public function update(Request $request, RoleApproval $roleApproval)
    {
        // التحقق من أن المستخدم له صلاحية HR فقط
        if (!Auth::user()->hasRole('hr')) {
            abort(403, 'غير مصرح لك بالوصول لهذه الصفحة');
        }

        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'approver_role_id' => 'required|exists:roles,id|different:role_id',
            'approval_type' => 'required|in:administrative,technical',
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'requires_same_project' => 'boolean',
            'requires_team_owner' => 'boolean',
        ]);

        // فحص عدم وجود قاعدة مماثلة (باستثناء الحالية)
        $exists = RoleApproval::where('role_id', $request->role_id)
            ->where('approver_role_id', $request->approver_role_id)
            ->where('approval_type', $request->approval_type)
            ->where('id', '!=', $roleApproval->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'قاعدة الاعتماد هذه موجودة بالفعل'
            ]);
        }

        $roleApproval->update([
            'role_id' => $request->role_id,
            'approver_role_id' => $request->approver_role_id,
            'approval_type' => $request->approval_type,
            'description' => $request->description,
            'is_active' => $request->boolean('is_active', true),
            'requires_same_project' => $request->boolean('requires_same_project', false),
            'requires_team_owner' => $request->boolean('requires_team_owner', false)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث قاعدة الاعتماد بنجاح',
            'data' => $roleApproval->load(['role', 'approverRole'])
        ]);
    }

    /**
     * حذف قاعدة اعتماد
     */
    public function destroy(RoleApproval $roleApproval)
    {
        // التحقق من أن المستخدم له صلاحية HR فقط
        if (!Auth::user()->hasRole('hr')) {
            abort(403, 'غير مصرح لك بالوصول لهذه الصفحة');
        }

        $roleApproval->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف قاعدة الاعتماد بنجاح'
        ]);
    }

    /**
     * تبديل حالة النشاط
     */
    public function toggleStatus(RoleApproval $roleApproval)
    {
        // التحقق من أن المستخدم له صلاحية HR فقط
        if (!Auth::user()->hasRole('hr')) {
            abort(403, 'غير مصرح لك بالوصول لهذه الصفحة');
        }

        $roleApproval->update([
            'is_active' => !$roleApproval->is_active
        ]);

        return response()->json([
            'success' => true,
            'message' => $roleApproval->is_active ? 'تم تنشيط قاعدة الاعتماد' : 'تم إلغاء تنشيط قاعدة الاعتماد',
            'is_active' => $roleApproval->is_active
        ]);
    }

    /**
     * جلب الأدوار التي يمكن لرول معين اعتمادها
     */
    public function getRolesCanApprove(Request $request)
    {
        $approverRoleId = $request->get('approver_role_id');
        $approvalType = $request->get('approval_type');

        if (!$approverRoleId || !$approvalType) {
            return response()->json([
                'success' => false,
                'message' => 'معرف الرول ونوع الاعتماد مطلوبان'
            ]);
        }

        $roles = RoleApproval::getRolesCanApprove($approverRoleId, $approvalType);

        return response()->json([
            'success' => true,
            'data' => $roles
        ]);
    }

    /**
     * جلب المعتمدين لرول معين
     */
    public function getApproversForRole(Request $request)
    {
        $roleId = $request->get('role_id');
        $approvalType = $request->get('approval_type');

        if (!$roleId || !$approvalType) {
            return response()->json([
                'success' => false,
                'message' => 'معرف الرول ونوع الاعتماد مطلوبان'
            ]);
        }

        $approvers = RoleApproval::getApproversForRole($roleId, $approvalType);

        return response()->json([
            'success' => true,
            'data' => $approvers
        ]);
    }

}
