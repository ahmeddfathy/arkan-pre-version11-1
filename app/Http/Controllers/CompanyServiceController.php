<?php

namespace App\Http\Controllers;

use App\Models\CompanyService;
use App\Models\User;
use App\Services\Auth\RoleCheckService;
use App\Services\ProjectManagement\ServiceDependencyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CompanyServiceController extends Controller
{
    protected $roleCheckService;
    protected $dependencyService;

    public function __construct(
        RoleCheckService $roleCheckService,
        ServiceDependencyService $dependencyService
    ) {
        $this->roleCheckService = $roleCheckService;
        $this->dependencyService = $dependencyService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (!$this->roleCheckService->userHasRole('sales_employee')) {
            abort(403, 'غير مسموح لك بتنفيذ هذا الإجراء. يجب أن تكون موظف مبيعات.');
        }

        // تسجيل نشاط عرض قائمة الخدمات
        if (Auth::check()) {
            activity()
                ->causedBy(Auth::user())
                ->withProperties([
                    'page' => 'company_services_index',
                    'action_type' => 'view_list',
                    'department_filter' => $request->department ?? 'all'
                ])
                ->log('عرض قائمة خدمات الشركة');
        }

        $query = CompanyService::query();

        if ($request->has('department') && $request->department) {
            $query->where('department', $request->department);
        }

        $services = $query->orderBy('created_at', 'desc')->paginate(10);
        $departments = User::select('department')->distinct()->whereNotNull('department')->pluck('department');

        return view('company-services.index', compact('services', 'departments'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if (!$this->roleCheckService->userHasRole('sales_employee')) {
            abort(403, 'غير مسموح لك بتنفيذ هذا الإجراء. يجب أن تكون موظف مبيعات.');
        }

        $departments = User::select('department')->distinct()->whereNotNull('department')->pluck('department');
        return view('company-services.create', compact('departments'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!$this->roleCheckService->userHasRole('sales_employee')) {
            abort(403, 'غير مسموح لك بتنفيذ هذا الإجراء. يجب أن تكون موظف مبيعات.');
        }

        $request->merge([
            'is_active' => $request->boolean('is_active')
        ]);
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:company_services',
            'description' => 'nullable|string|max:1000',
            'points' => 'required|integer|min:0|max:1000',
            'max_points_per_project' => 'nullable|integer|min:0|max:10000',
            'is_active' => 'boolean',
            'department' => 'nullable|string|max:255'
        ], [
            'name.required' => 'اسم الخدمة مطلوب',
            'name.unique' => 'اسم الخدمة موجود مسبقاً',
            'points.required' => 'عدد النقاط مطلوب',
            'points.integer' => 'عدد النقاط يجب أن يكون رقماً صحيحاً',
            'points.min' => 'عدد النقاط يجب أن يكون أكبر من أو يساوي 0',
            'points.max' => 'عدد النقاط يجب أن يكون أقل من أو يساوي 1000',
            'max_points_per_project.integer' => 'الحد الأقصى للنقاط يجب أن يكون رقماً صحيحاً',
            'max_points_per_project.min' => 'الحد الأقصى للنقاط يجب أن يكون 0 أو أكثر',
            'max_points_per_project.max' => 'الحد الأقصى للنقاط يجب أن يكون أقل من 10000'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        CompanyService::create([
            'name' => $request->name,
            'description' => $request->description,
            'points' => $request->points,
            'max_points_per_project' => $request->max_points_per_project ?? 0,
            'is_active' => $request->is_active,
            'department' => $request->department
        ]);

        return redirect()->route('company-services.index')
            ->with('success', 'تم إضافة الخدمة بنجاح');
    }

    /**
     * Display the specified resource.
     */
    public function show(CompanyService $companyService)
    {
        if (!$this->roleCheckService->userHasRole('sales_employee')) {
            abort(403, 'غير مسموح لك بتنفيذ هذا الإجراء. يجب أن تكون موظف مبيعات.');
        }

        // تسجيل نشاط عرض تفاصيل الخدمة
        if (Auth::check()) {
            activity()
                ->causedBy(Auth::user())
                ->performedOn($companyService)
                ->withProperties([
                    'page' => 'company_services_show',
                    'action_type' => 'view_details',
                    'service_id' => $companyService->id,
                    'service_name' => $companyService->name,
                    'service_department' => $companyService->department
                ])
                ->log('عرض تفاصيل خدمة الشركة: ' . $companyService->name);
        }

        $allRoles = \Spatie\Permission\Models\Role::all();
        $companyService->load(['requiredRoles', 'dependencies', 'dependentServices']);

        return view('company-services.show', compact('companyService', 'allRoles'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CompanyService $companyService)
    {
        if (!$this->roleCheckService->userHasRole('sales_employee')) {
            abort(403, 'غير مسموح لك بتنفيذ هذا الإجراء. يجب أن تكون موظف مبيعات.');
        }

        $departments = User::select('department')->distinct()->whereNotNull('department')->pluck('department');
        return view('company-services.edit', compact('companyService', 'departments'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CompanyService $companyService)
    {
        if (!$this->roleCheckService->userHasRole('sales_employee')) {
            abort(403, 'غير مسموح لك بتنفيذ هذا الإجراء. يجب أن تكون موظف مبيعات.');
        }

        $request->merge([
            'is_active' => $request->boolean('is_active')
        ]);
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:company_services,name,' . $companyService->id,
            'description' => 'nullable|string|max:1000',
            'points' => 'required|integer|min:0|max:1000',
            'max_points_per_project' => 'nullable|integer|min:0|max:10000',
            'is_active' => 'boolean',
            'department' => 'nullable|string|max:255'
        ], [
            'name.required' => 'اسم الخدمة مطلوب',
            'name.unique' => 'اسم الخدمة موجود مسبقاً',
            'points.required' => 'عدد النقاط مطلوب',
            'points.integer' => 'عدد النقاط يجب أن يكون رقماً صحيحاً',
            'points.min' => 'عدد النقاط يجب أن يكون أكبر من أو يساوي 0',
            'points.max' => 'عدد النقاط يجب أن يكون أقل من أو يساوي 1000',
            'max_points_per_project.integer' => 'الحد الأقصى للنقاط يجب أن يكون رقماً صحيحاً',
            'max_points_per_project.min' => 'الحد الأقصى للنقاط يجب أن يكون 0 أو أكثر',
            'max_points_per_project.max' => 'الحد الأقصى للنقاط يجب أن يكون أقل من 10000'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $companyService->update([
            'name' => $request->name,
            'description' => $request->description,
            'points' => $request->points,
            'max_points_per_project' => $request->max_points_per_project ?? 0,
            'is_active' => $request->is_active,
            'department' => $request->department
        ]);

        return redirect()->route('company-services.index')
            ->with('success', 'تم تحديث الخدمة بنجاح');
    }



    /**
     * Toggle service status
     */
    public function toggleStatus(CompanyService $companyService)
    {
        if (!$this->roleCheckService->userHasRole('sales_employee')) {
            abort(403, 'غير مسموح لك بتنفيذ هذا الإجراء. يجب أن تكون موظف مبيعات.');
        }

        $companyService->update([
            'is_active' => !$companyService->is_active
        ]);

        $status = $companyService->is_active ? 'مفعلة' : 'معطلة';
        return redirect()->route('company-services.index')
            ->with('success', "تم تغيير حالة الخدمة إلى {$status}");
    }

    /**
     * Get services for API
     */
    public function getServices(Request $request)
    {
        if (!$this->roleCheckService->userHasRole('sales_employee')) {
            abort(403, 'غير مسموح لك بتنفيذ هذا الإجراء. يجب أن تكون موظف مبيعات.');
        }
        $department = $request->get('department');

        $services = CompanyService::active()
            ->when($department, function($query) use ($department) {
                return $query->where(function($q) use ($department) {
                    $q->where('department', $department)
                      ->orWhereNull('department');
                });
            })
            ->orderByPoints()
            ->get();

        return response()->json($services);
    }

    /**
     * إضافة دور مطلوب للخدمة
     */
    public function attachRole(Request $request, CompanyService $companyService)
    {
        if (!$this->roleCheckService->userHasRole('sales_employee')) {
            return response()->json(['success' => false, 'message' => 'غير مسموح'], 403);
        }

        $request->validate([
            'role_id' => 'required|exists:roles,id'
        ]);

        try {
            $companyService->requiredRoles()->attach($request->role_id);

            activity()
                ->causedBy(Auth::user())
                ->performedOn($companyService)
                ->log('أضاف دور مطلوب للخدمة');

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'حدث خطأ'], 500);
        }
    }

    /**
     * حذف دور مطلوب من الخدمة
     */
    public function detachRole(CompanyService $companyService, $roleId)
    {
        if (!$this->roleCheckService->userHasRole('sales_employee')) {
            return response()->json(['success' => false, 'message' => 'غير مسموح'], 403);
        }

        try {
            $companyService->requiredRoles()->detach($roleId);

            activity()
                ->causedBy(Auth::user())
                ->performedOn($companyService)
                ->log('حذف دور مطلوب من الخدمة');

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'حدث خطأ'], 500);
        }
    }

    // ==================== Service Dependencies Methods ====================

    /**
     * الحصول على جميع الاعتماديات
     */
    public function getAllDependencies()
    {
        if (!$this->roleCheckService->userHasRole('sales_employee')) {
            return response()->json(['success' => false, 'message' => 'غير مسموح'], 403);
        }

        try {
            $dependencies = $this->dependencyService->getAllDependencies();
            $services = CompanyService::active()
                                     ->orderBy('execution_order')
                                     ->orderBy('name')
                                     ->get();

            return response()->json([
                'success' => true,
                'dependencies' => $dependencies,
                'services' => $services
            ]);

        } catch (\Exception $e) {
            Log::error('خطأ في الحصول على الاعتماديات', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء الحصول على الاعتماديات'
            ], 500);
        }
    }

    /**
     * إضافة اعتمادية جديدة
     */
    public function addDependency(Request $request)
    {
        if (!$this->roleCheckService->userHasRole('sales_employee')) {
            return response()->json(['success' => false, 'message' => 'غير مسموح'], 403);
        }

        $validator = Validator::make($request->all(), [
            'service_id' => 'required|exists:company_services,id',
            'depends_on_service_id' => 'required|exists:company_services,id|different:service_id',
            'notes' => 'nullable|string|max:500'
        ], [
            'service_id.required' => 'يجب تحديد الخدمة',
            'depends_on_service_id.different' => 'لا يمكن للخدمة أن تعتمد على نفسها',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            $result = $this->dependencyService->addDependency(
                $request->service_id,
                $request->depends_on_service_id,
                $request->notes
            );

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            Log::error('خطأ في إضافة الاعتمادية', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إضافة الاعتمادية'
            ], 500);
        }
    }

    /**
     * حذف اعتمادية
     */
    public function removeDependency($serviceId, $dependsOnServiceId)
    {
        if (!$this->roleCheckService->userHasRole('sales_employee')) {
            return response()->json(['success' => false, 'message' => 'غير مسموح'], 403);
        }

        try {
            $result = $this->dependencyService->removeDependency($serviceId, $dependsOnServiceId);
            return response()->json($result, $result['success'] ? 200 : 404);

        } catch (\Exception $e) {
            Log::error('خطأ في حذف الاعتمادية', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف الاعتمادية'
            ], 500);
        }
    }

    /**
     * تحديث ترتيب/مستوى الخدمة
     */
    public function updateExecutionOrder(Request $request, CompanyService $companyService)
    {
        if (!$this->roleCheckService->userHasRole('sales_employee')) {
            return response()->json(['success' => false, 'message' => 'غير مسموح'], 403);
        }

        $validator = Validator::make($request->all(), [
            'execution_order' => 'required|integer|min:1|max:10'
        ], [
            'execution_order.required' => 'يجب تحديد رقم الترتيب',
            'execution_order.min' => 'رقم الترتيب يجب أن يكون 1 على الأقل',
            'execution_order.max' => 'رقم الترتيب يجب ألا يتجاوز 10'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            $companyService->execution_order = $request->execution_order;
            $companyService->save();

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث ترتيب الخدمة بنجاح',
                'service' => $companyService
            ]);

        } catch (\Exception $e) {
            Log::error('خطأ في تحديث ترتيب الخدمة', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث ترتيب الخدمة'
            ], 500);
        }
    }
}
