<?php

namespace App\Http\Controllers;

use App\Imports\UsersImport;

use Maatwebsite\Excel\Facades\Excel;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;
use App\Models\WorkShift;
use Illuminate\Support\Facades\Auth;
use App\Services\Auth\RoleCheckService;

class UserController extends Controller
{
    protected $roleCheckService;

    public function __construct(RoleCheckService $roleCheckService)
    {
        $this->roleCheckService = $roleCheckService;

        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (!$this->roleCheckService->userHasRole('hr')) {
                abort(403, 'غير مسموح لك بالوصول إلى هذه الصفحة. يجب أن تكون من قسم الموارد البشرية.');
            }
            return $next($request);
        });
    }
    public function index(Request $request)
    {
        $query = User::with(['roles', 'permissions']);

        if ($request->has('employee_name') && !empty($request->employee_name)) {
            $query->where('name', 'like', "%{$request->employee_name}%");
        }

        if ($request->has('department') && !empty($request->department)) {
            $query->where('department', $request->department);
        }

        if ($request->has('status') && !empty($request->status)) {
            $query->where('employee_status', $request->status);
        }

        $users = $query->latest()->paginate(10);
        $totalUsers = User::count();

        foreach ($users as $user) {
            $allPermissions = Permission::all();
        }

        $employees = User::select('name')->distinct()->get();
        $departments = User::select('department')->distinct()->whereNotNull('department')->get();
        $roles = Role::all();
        $permissions = Permission::all();

        return view('users.index', compact('users', 'employees', 'departments', 'roles', 'permissions', 'totalUsers'));
    }

    public function show($id)
    {
        $user = User::with('workShift')->findOrFail($id);
        return view('users.show', compact('user'));
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully');
    }

    public function getRolePermissions($roleName)
    {
        try {
            $role = Role::findByName($roleName);
            if (!$role) {
                return response()->json([]);
            }

            $permissions = $role->permissions->pluck('name')->toArray();

            return response()->json($permissions);
        } catch (\Exception $e) {
            return response()->json([], 500);
        }
    }

    public function updateRolesAndPermissions(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            DB::beginTransaction();

            if ($request->has('roles') && !empty($request->roles)) {
                $roleNames = $request->roles;
                $roles = [];

                foreach ($roleNames as $roleName) {
                    $role = Role::findByName($roleName);
                    if (!$role) {
                        throw new \Exception("الرول '{$roleName}' غير موجود");
                    }
                    $roles[] = $role;
                }

                $user->syncRoles($roles);

                $allRolePermissions = [];
                foreach ($roles as $role) {
                    $rolePermissions = $role->permissions->pluck('name')->toArray();
                    $allRolePermissions = array_merge($allRolePermissions, $rolePermissions);
                }
                $allRolePermissions = array_unique($allRolePermissions);

                $requestedPermissions = $request->permissions ?? [];

                DB::table('model_has_permissions')
                    ->where('model_type', get_class($user))
                    ->where('model_id', $user->id)
                    ->delete();

                $user->permissions()->detach();

                foreach ($requestedPermissions as $permissionName) {
                    $permission = Permission::where('name', $permissionName)->first();
                    if (!$permission) continue;

                    if (in_array($permissionName, $allRolePermissions)) {
                        continue;
                    } else {
                        $user->givePermissionTo($permission);
                    }
                }

                $permissionsToBlock = array_diff($allRolePermissions, $requestedPermissions);
                foreach ($permissionsToBlock as $permissionName) {
                    $permission = Permission::where('name', $permissionName)->first();
                    if ($permission) {
                        DB::table('model_has_permissions')->insert([
                            'permission_id' => $permission->id,
                            'model_type' => get_class($user),
                            'model_id' => $user->id,
                            'forbidden' => true
                        ]);
                    }
                }
            } else {
                $user->syncRoles([]);
                $user->syncPermissions([]);

                DB::table('model_has_permissions')
                    ->where('model_type', User::class)
                    ->where('model_id', $user->id)
                    ->delete();
            }

            DB::commit();

            $rolesCount = isset($roles) ? count($roles) : 0;
            $blockedCount = isset($permissionsToBlock) ? count($permissionsToBlock) : 0;
            $additionalCount = isset($requestedPermissions) && isset($allRolePermissions) ?
                count(array_diff($requestedPermissions, $allRolePermissions)) : 0;

            $message = 'تم تحديث الأدوار والصلاحيات بنجاح';
            if ($rolesCount > 0 || $blockedCount > 0 || $additionalCount > 0) {
                $details = [];
                if ($rolesCount > 0) $details[] = "{$rolesCount} أدوار";
                if ($additionalCount > 0) $details[] = "{$additionalCount} صلاحيات إضافية";
                if ($blockedCount > 0) $details[] = "{$blockedCount} صلاحيات محظورة";
                $message .= ' (' . implode(', ', $details) . ')';
            }

            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث الأدوار والصلاحيات: ' . $e->getMessage()
            ], 500);
        }
    }


    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv',
        ]);

        try {
            Excel::import(new UsersImport, $request->file('file'));

            $employeeRole = Role::findByName('employee');

            if (!$employeeRole) {
                return redirect()->route('users.index')
                    ->with('error', 'لم يتم العثور على دور "موظف". يرجى إنشاء هذا الدور أولاً.');
            }

            $usersWithoutRoles = User::whereDoesntHave('roles')->get();
            $assignedCount = 0;

            foreach ($usersWithoutRoles as $user) {
                $user->assignRole($employeeRole);
                $assignedCount++;
            }

            return redirect()->route('users.index')
                ->with('success', "تم استيراد المستخدمين بنجاح وتعيين دور الموظف لـ {$assignedCount} مستخدمين جدد.");
        } catch (\Exception $e) {
            return redirect()->route('users.index')
                ->with('error', 'حدث خطأ أثناء استيراد المستخدمين: ' . $e->getMessage());
        }
    }

    public function removeRolesAndPermissions($id)
    {
        try {
            $user = User::findOrFail($id);

            DB::beginTransaction();

            $user->syncRoles([]);
            $user->syncPermissions([]);

            DB::table('model_has_permissions')
                ->where('model_type', User::class)
                ->where('model_id', $user->id)
                ->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم إزالة جميع الأدوار والصلاحيات بنجاح'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إزالة الأدوار والصلاحيات'
            ], 500);
        }
    }

    public function resetToEmployee($id)
    {
        try {
            $user = User::findOrFail($id);

            DB::beginTransaction();

            $user->syncRoles([]);
            $user->syncPermissions([]);

            DB::table('model_has_permissions')
                ->where('model_type', User::class)
                ->where('model_id', $user->id)
                ->delete();

            $user->assignRole('employee');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم إعادة تعيين المستخدم كموظف بنجاح'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إعادة تعيين المستخدم: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getEmployeesWithoutRole()
    {
        $usersWithoutRole = User::whereDoesntHave('roles')->get();
        return view('users.without_roles', compact('usersWithoutRole'));
    }

    public function assignEmployeeRole(Request $request)
    {
        $employeeRole = Role::findByName('employee');

        if ($request->has('user_ids')) {
            foreach ($request->user_ids as $userId) {
                $user = User::find($userId);
                if ($user) {
                    $user->assignRole($employeeRole);
                }
            }
        }

        return redirect()->back()->with('success', 'تم تعيين دور الموظف بنجاح');
    }

    public function getForbiddenPermissions($id)
    {
        $user = User::findOrFail($id);

        $forbiddenPermissions = DB::table('model_has_permissions')
            ->where([
                'model_type' => get_class($user),
                'model_id' => $user->id,
                'forbidden' => true
            ])
            ->join('permissions', 'model_has_permissions.permission_id', '=', 'permissions.id')
            ->pluck('permissions.name')
            ->toArray();

        return response()->json($forbiddenPermissions);
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        $workShifts = WorkShift::where('is_active', true)->get();
        return view('users.edit', compact('user', 'workShifts'));
    }

    public function assignWorkShifts(Request $request)
    {
        $query = User::orderBy('name')->where('employee_status', 'active');

        if ($request->has('search') && !empty($request->search)) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $users = $query->paginate(10);
        $workShifts = WorkShift::where('is_active', true)->get();
        $allUserNames = User::orderBy('name')->where('employee_status', 'active')->pluck('name')->toArray();

        return view('users.assign-work-shifts', compact('users', 'workShifts', 'allUserNames'));
    }

    /**
     * Get all active users for revision assignment (no HR restriction)
     */
    public function getAllUsersForRevision()
    {
        try {
            $users = User::select('id', 'name', 'department')
                ->where('employee_status', 'active')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'users' => $users
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جلب المستخدمين',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function saveSingleWorkShift(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'work_shift_id' => 'nullable|exists:work_shifts,id'
            ]);

            User::where('id', $validated['user_id'])
                ->update(['work_shift_id' => $validated['work_shift_id']]);

            return response()->json([
                'success' => true,
                'message' => 'تم تعيين الوردية بنجاح'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تعيين الوردية'
            ], 500);
        }
    }

    public function getAdditionalPermissions($id)
    {
        $user = User::findOrFail($id);

        $userDirectPermissions = $user->permissions->pluck('name')->toArray();

        $allRolePermissions = [];
        foreach ($user->roles as $role) {
            $rolePermissions = $role->permissions->pluck('name')->toArray();
            $allRolePermissions = array_merge($allRolePermissions, $rolePermissions);
        }
        $allRolePermissions = array_unique($allRolePermissions);

        $additionalPermissions = array_diff($userDirectPermissions, $allRolePermissions);

        return response()->json(array_values($additionalPermissions));
    }


}
