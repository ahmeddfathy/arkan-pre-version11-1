<?php

namespace App\Http\Controllers;

use App\Models\CompanyService;
use App\Models\ServiceDataField;
use App\Services\ServiceDataManagementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServiceDataManagementController extends Controller
{
    protected $serviceDataService;

    public function __construct(ServiceDataManagementService $serviceDataService)
    {
        $this->serviceDataService = $serviceDataService;
    }

    /**
     * عرض صفحة اختيار الخدمة
     */
    public function index()
    {
        // التحقق من الصلاحيات
        if (!Auth::user()->hasAnyRole(['hr', 'admin', 'company_manager', 'project_manager'])) {
            abort(403, 'غير مصرح لك بالوصول لهذه الصفحة');
        }

        $services = CompanyService::active()
            ->withCount('taskTemplates')
            ->orderBy('name')
            ->get();

        return view('service-data-management.index', compact('services'));
    }

    /**
     * عرض صفحة إدارة حقول الخدمة
     */
    public function manageService($serviceId)
    {
        // التحقق من الصلاحيات
        if (!Auth::user()->hasAnyRole(['hr', 'admin', 'company_manager', 'project_manager'])) {
            abort(403, 'غير مصرح لك بالوصول لهذه الصفحة');
        }

        $service = CompanyService::findOrFail($serviceId);
        $fields = $this->serviceDataService->getServiceFields($serviceId);
        $statistics = $this->serviceDataService->getFieldStatistics($serviceId);

        // جلب الخدمات الأخرى للنسخ
        $otherServices = CompanyService::active()
            ->where('id', '!=', $serviceId)
            ->whereHas('dataFields')
            ->orderBy('name')
            ->get();

        return view('service-data-management.manage', compact('service', 'fields', 'statistics', 'otherServices'));
    }

    /**
     * عرض صفحة إنشاء حقل جديد
     */
    public function createField($serviceId)
    {
        // التحقق من الصلاحيات
        if (!Auth::user()->hasAnyRole(['hr', 'admin', 'company_manager', 'project_manager'])) {
            abort(403, 'غير مصرح لك بالوصول لهذه الصفحة');
        }

        $service = CompanyService::findOrFail($serviceId);

        return view('service-data-management.create-field', compact('service'));
    }

    /**
     * حفظ حقل جديد
     */
    public function storeField(Request $request, $serviceId)
    {
        $validated = $request->validate([
            'field_label' => 'required|string|max:255',
            'field_type' => 'required|in:boolean,date,dropdown,text',
            'field_options' => 'required_if:field_type,dropdown|nullable|array',
            'field_options.*' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_required' => 'boolean',
        ]);

        $validated['service_id'] = $serviceId;
        $validated['is_required'] = $request->has('is_required');

        $result = $this->serviceDataService->createField($validated);

        if ($result['success']) {
            return redirect()
                ->route('service-data.manage', $serviceId)
                ->with('success', $result['message']);
        }

        return back()
            ->withInput()
            ->with('error', $result['message']);
    }

    /**
     * عرض صفحة تعديل حقل
     */
    public function editField($fieldId)
    {
        // التحقق من الصلاحيات
        if (!Auth::user()->hasAnyRole(['hr', 'admin', 'company_manager', 'project_manager'])) {
            abort(403, 'غير مصرح لك بالوصول لهذه الصفحة');
        }

        $field = ServiceDataField::with('service')->findOrFail($fieldId);

        return view('service-data-management.edit-field', compact('field'));
    }

    /**
     * تحديث حقل
     */
    public function updateField(Request $request, $fieldId)
    {
        $validated = $request->validate([
            'field_label' => 'required|string|max:255',
            'field_type' => 'required|in:boolean,date,dropdown,text',
            'field_options' => 'required_if:field_type,dropdown|nullable|array',
            'field_options.*' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_required' => 'boolean',
        ]);

        $validated['is_required'] = $request->has('is_required');

        $field = ServiceDataField::findOrFail($fieldId);
        $result = $this->serviceDataService->updateField($fieldId, $validated);

        if ($result['success']) {
            return redirect()
                ->route('service-data.manage', $field->service_id)
                ->with('success', $result['message']);
        }

        return back()
            ->withInput()
            ->with('error', $result['message']);
    }

    /**
     * حذف حقل
     */
    public function deleteField($fieldId)
    {
        $field = ServiceDataField::findOrFail($fieldId);
        $serviceId = $field->service_id;

        $result = $this->serviceDataService->deleteField($fieldId);

        if ($result['success']) {
            return redirect()
                ->route('service-data.manage', $serviceId)
                ->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * تبديل حالة الحقل
     */
    public function toggleFieldStatus($fieldId)
    {
        $result = $this->serviceDataService->toggleFieldStatus($fieldId);

        return response()->json($result);
    }

    /**
     * إعادة ترتيب الحقول
     */
    public function reorderFields(Request $request, $serviceId)
    {
        $validated = $request->validate([
            'field_ids' => 'required|array',
            'field_ids.*' => 'required|integer|exists:service_data_fields,id'
        ]);

        $result = $this->serviceDataService->reorderFields($serviceId, $validated['field_ids']);

        return response()->json($result);
    }

    /**
     * نسخ الحقول من خدمة أخرى
     */
    public function copyFieldsFromService(Request $request, $targetServiceId)
    {
        $validated = $request->validate([
            'source_service_id' => 'required|integer|exists:company_services,id'
        ]);

        $result = $this->serviceDataService->copyFieldsFromService(
            $validated['source_service_id'],
            $targetServiceId
        );

        if ($result['success']) {
            return redirect()
                ->route('service-data.manage', $targetServiceId)
                ->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }
}
