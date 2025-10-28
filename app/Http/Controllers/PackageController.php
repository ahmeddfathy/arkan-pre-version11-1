<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\CompanyService;
use App\Services\Auth\RoleCheckService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PackageController extends Controller
{
    protected $roleCheckService;

    public function __construct(RoleCheckService $roleCheckService)
    {
        $this->roleCheckService = $roleCheckService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (!$this->roleCheckService->userHasRole('sales_employee')) {
            abort(403, 'غير مسموح لك بتنفيذ هذا الإجراء. يجب أن تكون موظف مبيعات.');
        }

        // تسجيل نشاط عرض قائمة الحزم
        if (Auth::check()) {
            activity()
                ->causedBy(Auth::user())
                ->withProperties([
                    'page' => 'packages_index',
                    'action_type' => 'view_list'
                ])
                ->log('عرض قائمة الحزم');
        }

        $packages = Package::latest()->paginate(10);
        return view('packages.index', compact('packages'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if (!$this->roleCheckService->userHasRole('sales_employee')) {
            abort(403, 'غير مسموح لك بتنفيذ هذا الإجراء. يجب أن تكون موظف مبيعات.');
        }

        $services = CompanyService::all();
        return view('packages.create', compact('services'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!$this->roleCheckService->userHasRole('sales_employee')) {
            abort(403, 'غير مسموح لك بتنفيذ هذا الإجراء. يجب أن تكون موظف مبيعات.');
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'services' => 'required|array',
            'services.*' => 'exists:company_services,id',
        ]);
        $selectedServices = CompanyService::whereIn('id', $data['services'])->get();
        $totalPoints = $selectedServices->sum('points');
        $package = Package::create([
            'name' => $data['name'],
            'description' => $data['description'],
            'services' => $data['services'],
            'total_points' => $totalPoints,
        ]);
        return redirect()->route('packages.index')->with('success', 'تم إنشاء الباقة بنجاح');
    }

    /**
     * Display the specified resource.
     */
    public function show(Package $package)
    {
        if (!$this->roleCheckService->userHasRole('sales_employee')) {
            abort(403, 'غير مسموح لك بتنفيذ هذا الإجراء. يجب أن تكون موظف مبيعات.');
        }

        // تسجيل نشاط عرض تفاصيل الحزمة
        if (Auth::check()) {
            activity()
                ->causedBy(Auth::user())
                ->performedOn($package)
                ->withProperties([
                    'page' => 'packages_show',
                    'action_type' => 'view_details',
                    'package_id' => $package->id,
                    'package_name' => $package->name,
                    'total_points' => $package->total_points,
                    'services_count' => count($package->services)
                ])
                ->log('عرض تفاصيل الحزمة: ' . $package->name);
        }

        $services = CompanyService::whereIn('id', $package->services)->get();
        return view('packages.show', compact('package', 'services'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Package $package)
    {
        if (!$this->roleCheckService->userHasRole('sales_employee')) {
            abort(403, 'غير مسموح لك بتنفيذ هذا الإجراء. يجب أن تكون موظف مبيعات.');
        }

        $services = CompanyService::all();
        $selected = $package->services;
        return view('packages.edit', compact('package', 'services', 'selected'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Package $package)
    {
        if (!$this->roleCheckService->userHasRole('sales_employee')) {
            abort(403, 'غير مسموح لك بتنفيذ هذا الإجراء. يجب أن تكون موظف مبيعات.');
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'services' => 'required|array',
            'services.*' => 'exists:company_services,id',
        ]);
        $selectedServices = CompanyService::whereIn('id', $data['services'])->get();
        $totalPoints = $selectedServices->sum('points');
        $package->update([
            'name' => $data['name'],
            'description' => $data['description'],
            'services' => $data['services'],
            'total_points' => $totalPoints,
        ]);
        return redirect()->route('packages.index')->with('success', 'تم تحديث الباقة بنجاح');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Package $package)
    {
        if (!$this->roleCheckService->userHasRole('sales_employee')) {
            abort(403, 'غير مسموح لك بتنفيذ هذا الإجراء. يجب أن تكون موظف مبيعات.');
        }

        $package->delete();
        return redirect()->route('packages.index')->with('success', 'تم حذف الباقة بنجاح');
    }
}
