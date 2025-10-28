<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Services\Auth\RoleCheckService;

class RoleController extends Controller
{
    protected $roleCheckService;

    public function __construct(RoleCheckService $roleCheckService)
    {
        $this->roleCheckService = $roleCheckService;

        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (!$this->roleCheckService->userHasRole(['hr', 'company_manager'])) {
                abort(403, 'غير مسموح لك بالوصول إلى إدارة الأدوار');
            }
            return $next($request);
        });
    }

    /**
     * عرض قائمة الأدوار
     */
    public function index(Request $request)
    {
        $query = Role::with('users');

        // البحث
        if ($request->has('search') && !empty($request->search)) {
            $query->where('name', 'LIKE', '%' . $request->search . '%');
        }

        // فلترة حسب وجود مستخدمين
        if ($request->has('has_users') && $request->has_users !== '') {
            if ($request->has_users == '1') {
                $query->whereHas('users');
            } else {
                $query->whereDoesntHave('users');
            }
        }

        $roles = $query->get();

        // حساب الإحصائيات
        $stats = [
            'total_roles' => $roles->count(),
            'active_roles' => $roles->filter(function($role) {
                return $role->users->count() > 0;
            })->count(),
            'inactive_roles' => $roles->filter(function($role) {
                return $role->users->count() == 0;
            })->count(),
            'total_users' => \App\Models\User::whereHas('roles')->count()
        ];

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'roles' => $roles,
                    'stats' => $stats
                ]
            ]);
        }

        return view('admin.roles.index', compact('roles', 'stats'));
    }


    /**
     * عرض صفحة إنشاء دور جديد
     */
    public function create()
    {
        return view('admin.roles.create');
    }

    /**
     * حفظ دور جديد
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'display_name' => 'nullable|string|max:255',
            'description' => 'nullable|string'
        ], [
            'name.required' => 'اسم الدور مطلوب',
            'name.unique' => 'اسم الدور موجود بالفعل'
        ]);

        try {
            DB::beginTransaction();

            $role = Role::create([
                'name' => $validated['name'],
                'guard_name' => 'web'
            ]);

            DB::commit();

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'تم إنشاء الدور بنجاح',
                    'data' => [
                        'role' => $role
                    ]
                ], 201);
            }

            return redirect()->route('roles.index')
                ->with('success', 'تم إنشاء الدور بنجاح');

        } catch (\Exception $e) {
            DB::rollback();

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'حدث خطأ أثناء إنشاء الدور: ' . $e->getMessage()
                ], 500);
            }

            return back()->withErrors('حدث خطأ أثناء إنشاء الدور')
                ->withInput();
        }
    }

    /**
     * عرض تفاصيل دور معين
     */
    public function show($id)
    {
        $role = Role::with('users.teams')->findOrFail($id);

        $roleStats = [
            'users_count' => $role->users->count(),
            'departments' => $role->users->pluck('department')->filter()->unique()->values()
        ];

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'role' => $role,
                    'stats' => $roleStats
                ]
            ]);
        }

        return view('admin.roles.show', compact('role', 'roleStats'));
    }

    /**
     * عرض صفحة تعديل دور
     */
    public function edit($id)
    {
        $role = Role::findOrFail($id);

        return view('admin.roles.edit', compact('role'));
    }

    /**
     * تحديث دور موجود
     */
    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles')->ignore($role->id)
            ],
            'display_name' => 'nullable|string|max:255',
            'description' => 'nullable|string'
        ], [
            'name.required' => 'اسم الدور مطلوب',
            'name.unique' => 'اسم الدور موجود بالفعل'
        ]);

        try {
            DB::beginTransaction();

            $role->update([
                'name' => $validated['name']
            ]);

            DB::commit();

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'تم تحديث الدور بنجاح',
                    'data' => [
                        'role' => $role
                    ]
                ]);
            }

            return redirect()->route('roles.index')
                ->with('success', 'تم تحديث الدور بنجاح');

        } catch (\Exception $e) {
            DB::rollback();

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'حدث خطأ أثناء تحديث الدور: ' . $e->getMessage()
                ], 500);
            }

            return back()->withErrors('حدث خطأ أثناء تحديث الدور')
                ->withInput();
        }
    }

    /**
     * حذف دور
     */
    public function destroy(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        // التحقق من عدم وجود مستخدمين مرتبطين بهذا الدور
        if ($role->users()->count() > 0) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'لا يمكن حذف هذا الدور لأنه مرتبط بمستخدمين'
                ], 422);
            }

            return back()->withErrors('لا يمكن حذف هذا الدور لأنه مرتبط بمستخدمين');
        }

        try {
            DB::beginTransaction();

            // حذف الدور
            $role->delete();

            DB::commit();

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'تم حذف الدور بنجاح'
                ]);
            }

            return redirect()->route('roles.index')
                ->with('success', 'تم حذف الدور بنجاح');

        } catch (\Exception $e) {
            DB::rollback();

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'حدث خطأ أثناء حذف الدور: ' . $e->getMessage()
                ], 500);
            }

            return back()->withErrors('حدث خطأ أثناء حذف الدور');
        }
    }


    /**
     * نسخ دور موجود
     */
    public function duplicate(Request $request, $id)
    {
        $originalRole = Role::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
        ], [
            'name.required' => 'اسم الدور الجديد مطلوب',
            'name.unique' => 'اسم الدور موجود بالفعل'
        ]);

        try {
            DB::beginTransaction();

            $newRole = Role::create([
                'name' => $validated['name'],
                'guard_name' => 'web'
            ]);

            DB::commit();

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'تم نسخ الدور بنجاح',
                    'data' => [
                        'role' => $newRole
                    ]
                ], 201);
            }

            return redirect()->route('roles.index')
                ->with('success', 'تم نسخ الدور بنجاح');

        } catch (\Exception $e) {
            DB::rollback();

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'حدث خطأ أثناء نسخ الدور: ' . $e->getMessage()
                ], 500);
            }

            return back()->withErrors('حدث خطأ أثناء نسخ الدور')
                ->withInput();
        }
    }
}
