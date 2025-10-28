<?php

namespace App\Services\ProjectManagement;

use App\Models\Project;
use App\Models\Client;
use App\Models\CompanyService;
use App\Services\Auth\RoleCheckService;
use App\Traits\SeasonAwareTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectCRUDService
{
    use SeasonAwareTrait;

    protected $projectService;
    protected $roleCheckService;

    public function __construct(
        ProjectService $projectService,
        RoleCheckService $roleCheckService
    ) {
        $this->projectService = $projectService;
        $this->roleCheckService = $roleCheckService;
    }

    /**
     * Get data for project creation form
     */
    public function getCreateData()
    {
        if (!$this->roleCheckService->userHasRole('sales_employee')) {
            abort(403, 'غير مسموح لك بتنفيذ هذا الإجراء. يجب أن تكون موظف مبيعات.');
        }

        // تحميل الباقات مع خدماتها
        $packages = \App\Models\Package::all()->map(function($package) {
            return [
                'id' => $package->id,
                'name' => $package->name,
                'total_points' => $package->total_points,
                'services' => $package->services ?? [] // IDs الخدمات في الباقة
            ];
        });

        return [
            'clients' => Client::all(),
            'services' => CompanyService::all(),
            'packages' => $packages
        ];
    }

    /**
     * Store a new project
     */
    public function store(Request $request)
    {
        if (!$this->roleCheckService->userHasRole('sales_employee')) {
            abort(403, 'غير مسموح لك بتنفيذ هذا الإجراء. يجب أن تكون موظف مبيعات.');
        }

        $validationRules = [
            'name' => 'required|string|max:255',
            'company_type' => 'required|in:A,K',
            'description' => 'nullable|string',
            'client_id' => 'required|exists:clients,id',
            'start_date' => 'nullable|date',
            'team_delivery_date' => 'nullable|date|after_or_equal:start_date',
            'client_agreed_delivery_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:جديد,موقوف',
            'pause_reason' => 'nullable|required_if:status,موقوف|in:واقف ع النموذج,واقف ع الأسئلة,واقف ع العميل,واقف ع مكالمة,موقوف',
            'pause_notes' => 'nullable|string',
            'is_urgent' => 'nullable|boolean',
            'preparation_enabled' => 'nullable|boolean',
            'preparation_start_date' => 'nullable|date|required_if:preparation_enabled,1',
            'preparation_days' => 'nullable|integer|min:1|required_if:preparation_enabled,1',
            'selected_services' => 'array',
            'selected_services.*' => 'exists:company_services,id',
            'service_statuses' => 'array',
            'service_statuses.*' => 'in:لم تبدأ,قيد التنفيذ,مكتملة',
            'note' => 'nullable|string',
            'code' => 'nullable|string|max:50|unique:projects,code,NULL,id,deleted_at,NULL',
        ];

        $request->validate($validationRules);

        // Añadir el season_id automáticamente وتأكيد حالة المشروع
        $data = $request->all();
        $data['season_id'] = $request->input('season_id', $this->getCurrentSeasonId());

        // تأكيد أن حالة المشروع هي "جديد" فقط عند الإنشاء
        $data['status'] = 'جديد';

        $result = $this->projectService->createProject($data);

        return [
            'success' => $result['success'],
            'message' => $result['success'] ? 'تم إنشاء المشروع بنجاح' : $result['message'],
            'redirect' => $result['success'] ? 'projects.index' : null
        ];
    }

    /**
     * Get project details for show view
     */
    public function show(Project $project)
    {
        $data = $this->projectService->getProjectDetails($project);

        // تحميل الردود مع المرفقات
        if (isset($data['project'])) {
            $data['project']->load(['attachments.replies.user', 'attachments.user']);
        }

        return $data;
    }

    /**
     * Get data for project edit form
     */
    public function getEditData(Project $project)
    {
        if (!$this->roleCheckService->userHasRole('sales_employee')) {
            abort(403, 'غير مسموح لك بتنفيذ هذا الإجراء. يجب أن تكون موظف مبيعات.');
        }

        // التحقق من أن المستخدم الحالي هو مدير المشروع
        if ($project->manager !== Auth::user()->name) {
            abort(403, 'غير مسموح لك بتعديل هذا المشروع. فقط مدير المشروع يمكنه تعديله.');
        }

        $clients = Client::all();
        $services = CompanyService::all();
        $project->load(['client', 'services']);

        // تحميل الباقات مع خدماتها
        $packages = \App\Models\Package::all()->map(function($package) {
            return [
                'id' => $package->id,
                'name' => $package->name,
                'total_points' => $package->total_points,
                'services' => $package->services ?? [] // IDs الخدمات في الباقة
            ];
        });

        return [
            'project' => $project,
            'clients' => $clients,
            'services' => $services,
            'packages' => $packages
        ];
    }

    /**
     * Update project
     */
public function update(Request $request, Project $project)
    {
        if (!$this->roleCheckService->userHasRole('sales_employee')) {
            abort(403, 'غير مسموح لك بتنفيذ هذا الإجراء. يجب أن تكون موظف مبيعات.');
        }

        // التحقق من أن المستخدم الحالي هو مدير المشروع
        if ($project->manager !== Auth::user()->name) {
            abort(403, 'غير مسموح لك بتعديل هذا المشروع. فقط مدير المشروع يمكنه تعديله.');
        }

        // ✅ التحقق من عمر المشروع - بعد يومين، لا يمكن تعديل إلا التواريخ فقط
        $createdDate = $project->created_at instanceof \Illuminate\Support\Carbon ? $project->created_at : \Carbon\Carbon::parse($project->created_at);
        $now = \Carbon\Carbon::now();
        $daysAgo = (int)$createdDate->diffInDays($now); // تحويل لـ integer
        $isOldProject = $daysAgo >= 2; // >= بدل > لتغطية جميع الحالات

        if ($isOldProject) {
            // الحقول المسموحة فقط للمشاريع القديمة (التواريخ وفترات التحضير فقط)
            $allowedFieldsForOldProjects = [
                'start_date',
                'team_delivery_date',
                'client_agreed_delivery_date',
                'preparation_enabled',
                'preparation_start_date',
                'preparation_days'
            ];

            // التحقق من أن المستخدم لا يحاول تعديل حقول ممنوعة
            // نتحقق فقط من الحقول التي لها قيم فعلية (ليست فارغة)
            $submittedFields = collect($request->all())
                ->filter(function($value, $key) {
                    return !in_array($key, ['_token', '_method']) &&
                           $value !== null &&
                           $value !== '' &&
                           $value !== [];
                })
                ->keys()
                ->toArray();

            $forbiddenFields = array_diff($submittedFields, $allowedFieldsForOldProjects);

            if (!empty($forbiddenFields)) {
                return [
                    'success' => false,
                    'message' => 'لا يمكن تعديل هذا المشروع بعد ' . $daysAgo . ' أيام من الإنشاء. يمكنك فقط تعديل التواريخ وفترات التحضير.',
                    'redirect' => 'projects.edit'
                ];
            }

            // التحقق من القيم - نسمح فقط بتحديثات التواريخ وفترات التحضير
            $data = collect($request->all())
                ->only($allowedFieldsForOldProjects)
                ->toArray();
        } else {
            $data = $request->all();
        }

        $validationRules = [
            'name' => 'required|string|max:255',
            'company_type' => 'required|in:A,K',
            'description' => 'nullable|string',
            'client_id' => 'required|exists:clients,id',
            'start_date' => 'nullable|date',
            'team_delivery_date' => 'nullable|date|after_or_equal:start_date',
            'client_agreed_delivery_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:جديد,جاري التنفيذ,مكتمل,ملغي,موقوف',
            'pause_reason' => 'nullable|required_if:status,موقوف|in:واقف ع النموذج,واقف ع الأسئلة,واقف ع العميل,واقف ع مكالمة,موقوف',
            'pause_notes' => 'nullable|string',
            'is_urgent' => 'nullable|boolean',
            'preparation_enabled' => 'nullable|boolean',
            'preparation_start_date' => 'nullable|date|required_if:preparation_enabled,1',
            'preparation_days' => 'nullable|integer|min:1|required_if:preparation_enabled,1',
            'selected_services' => 'array',
            'selected_services.*' => 'exists:company_services,id',
            'service_statuses' => 'array',
            'service_statuses.*' => 'in:لم تبدأ,قيد التنفيذ,مكتملة',
            'manager' => 'nullable|string|max:255',
            'note' => 'nullable|string',
        ];

        // لو كان المشروع قديم، نقلل قواعد التحقق
        if ($isOldProject) {
            $validationRules = collect($validationRules)
                ->only([
                    'start_date',
                    'team_delivery_date',
                    'client_agreed_delivery_date',
                    'preparation_enabled',
                    'preparation_start_date',
                    'preparation_days'
                ])
                ->toArray();
        }

        $request->validate($validationRules);

        // التحقق من عدم محاولة تغيير كود المشروع
        if ($request->has('code') && $request->code !== $project->code) {
            return [
                'success' => false,
                'message' => 'لا يمكن تعديل كود المشروع بعد إنشائه',
                'redirect' => 'projects.edit',
            ];
        }

        // ✅ التحقق من عدم محاولة تغيير مدير المشروع (لا يمكن تعديله نهائياً)
        if ($request->has('manager') && $request->manager !== $project->manager) {
            return [
                'success' => false,
                'message' => 'لا يمكن تعديل مدير المشروع. هذا الحقل محمي ولا يمكن تغييره.',
                'redirect' => 'projects.edit',
            ];
        }

        // Añadir el season_id automáticamente إذا لم يكن موجوداً
        if (!$isOldProject) {
            if (!$project->season_id && !isset($data['season_id'])) {
                $data['season_id'] = $this->getCurrentSeasonId();
            }
        }

        // إزالة كود المشروع من البيانات لضمان عدم تعديله
        unset($data['code']);

        // إزالة حقل مدير المشروع من البيانات - لا يمكن تعديله نهائياً
        unset($data['manager']);

        $result = $this->projectService->updateProject($project, $data);

        // إضافة معلومات إضافية عن العملية
        $message = $result['success'] ? 'تم تحديث المشروع بنجاح' : $result['message'];

        if ($result['success'] && isset($result['folder_update_skipped']) && $result['folder_update_skipped']) {
            $message .= ' (تم تحسين العملية - لم تكن هناك حاجة لتحديث الفولدرات)';
        } elseif ($result['success'] && isset($result['folder_updated']) && $result['folder_updated']) {
            $message .= ' (تم تحديث بنية الفولدرات)';
        }

        return [
            'success' => $result['success'],
            'message' => $message,
            'redirect' => $result['success'] ? 'projects.index' : null
        ];
    }

    /**
     * Delete project
     */
    public function destroy(Project $project)
    {
        // حذف المشاريع محظور نهائياً لحماية البيانات التاريخية
        return [
            'success' => false,
            'message' => 'حذف المشاريع محظور نهائياً للحفاظ على سلامة البيانات التاريخية للنظام. يمكنك تغيير حالة المشروع إلى "ملغي" بدلاً من ذلك.',
            'redirect' => 'projects.index'
        ];
    }

    /**
     * Acknowledge project receipt
     */
    public function acknowledgeProject(Project $project)
    {
        $userId = Auth::id();

        if ($project->acknowledgeForUser($userId)) {
            return [
                'success' => true,
                'message' => 'تم تأكيد استلام المشروع بنجاح'
            ];
        }

        return [
            'success' => false,
            'message' => 'فشل في تأكيد استلام المشروع'
        ];
    }

    /**
     * Get validation rules for store
     */
    public function getStoreValidationRules()
    {
        return [
            'name' => 'required|string|max:255',
            'company_type' => 'required|in:A,K',
            'description' => 'nullable|string',
            'client_id' => 'required|exists:clients,id',
            'start_date' => 'nullable|date',
            'team_delivery_date' => 'nullable|date|after_or_equal:start_date',
            'client_agreed_delivery_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:جديد,جاري التنفيذ,مكتمل,ملغي',
            'is_urgent' => 'nullable|boolean',
            'preparation_enabled' => 'nullable|boolean',
            'preparation_start_date' => 'nullable|date|required_if:preparation_enabled,1',
            'preparation_days' => 'nullable|integer|min:1|required_if:preparation_enabled,1',
            'selected_services' => 'array',
            'selected_services.*' => 'exists:company_services,id',
            'service_statuses' => 'array',
            'service_statuses.*' => 'in:لم تبدأ,قيد التنفيذ,مكتملة',
            'note' => 'nullable|string',
            'code' => 'nullable|string|max:50|unique:projects,code,NULL,id,deleted_at,NULL',
        ];
    }

    /**
     * Get validation rules for update
     */
    public function getUpdateValidationRules(Project $project)
    {
        return [
            'name' => 'required|string|max:255',
            'company_type' => 'required|in:A,K',
            'description' => 'nullable|string',
            'client_id' => 'required|exists:clients,id',
            'start_date' => 'nullable|date',
            'team_delivery_date' => 'nullable|date|after_or_equal:start_date',
            'client_agreed_delivery_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:جديد,جاري التنفيذ,مكتمل,ملغي',
            'is_urgent' => 'nullable|boolean',
            'preparation_enabled' => 'nullable|boolean',
            'preparation_start_date' => 'nullable|date|required_if:preparation_enabled,1',
            'preparation_days' => 'nullable|integer|min:1|required_if:preparation_enabled,1',
            'selected_services' => 'array',
            'selected_services.*' => 'exists:company_services,id',
            'service_statuses' => 'array',
            'service_statuses.*' => 'in:لم تبدأ,قيد التنفيذ,مكتملة',
            'manager' => 'nullable|string|max:255',
            'note' => 'nullable|string',
            'code' => 'nullable|string|max:50|unique:projects,code,'.$project->id,
        ];
    }
}
