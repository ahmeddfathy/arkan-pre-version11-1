<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectAttachment;
use App\Models\Client;
use App\Models\CompanyService;
use App\Models\User;
use App\Services\ProjectManagement\ProjectService;
use App\Services\ProjectManagement\AttachmentSharingService;
use App\Services\ProjectManagement\ParticipantService;
use App\Services\ProjectManagement\ProjectAnalyticsService;
use App\Services\ProjectManagement\ProjectEmployeeAnalyticsService;
use App\Services\ProjectManagement\ProjectTeamRecommendationService;
use App\Services\ProjectManagement\ProjectNotesService;
use App\Services\ProjectManagement\ProjectAttachmentReplyService;
use App\Services\ProjectManagement\ProjectServiceProgressService;
use App\Services\ProjectManagement\ProjectAttachmentSharingHandlerService;
use App\Services\ProjectManagement\ProjectTaskService;
use App\Services\ProjectManagement\ProjectAttachmentUploadService;
use App\Services\ProjectManagement\ProjectCRUDService;
use App\Services\ProjectManagement\ProjectAttachmentManagementService;
use App\Services\ProjectManagement\ProjectAuthorizationService;

use App\Services\ProjectDashboard\RevisionStatsService;
use App\Services\EmployeeErrorController\EmployeeErrorStatisticsService;
use App\Services\ProjectManagement\ProjectDeliveryService;
use App\Services\ProjectManagement\ProjectCodeService;
use App\Services\ProjectManagement\ProjectSidebarService;
use App\Services\ProjectManagement\ProjectValidationService;
use App\Services\Auth\RoleCheckService;
use App\Traits\SeasonAwareTrait;
use App\Traits\HasNTPTime;
use App\Http\Controllers\Traits\Projects\ProjectCRUDTrait;
use App\Http\Controllers\Traits\Projects\ProjectAnalyticsTrait;
use App\Http\Controllers\Traits\Projects\ProjectAttachmentsTrait;
use App\Http\Controllers\Traits\Projects\ProjectParticipantsTrait;
use App\Models\ProjectNote;
use App\Models\TemplateTaskUser;
use App\Models\TaskUser;
use App\Models\ProjectServiceUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProjectController extends Controller
{
    use SeasonAwareTrait;
    use HasNTPTime;
    use ProjectCRUDTrait;
    use ProjectAnalyticsTrait;
    use ProjectAttachmentsTrait;
    use ProjectParticipantsTrait;

    protected $projectService;
    protected $crudService;
    protected $attachmentManagementService;
    protected $authorizationService;
    protected $analyticsService;
    protected $employeeAnalyticsService;
    protected $teamRecommendationService;
    protected $notesService;
    protected $attachmentReplyService;
    protected $serviceProgressService;
    protected $attachmentSharingHandlerService;
    protected $taskService;
    protected $attachmentUploadService;
    protected $participantService;
    protected $attachmentSharingService;
    protected $revisionStatsService;
    protected $errorStatsService;
    protected $deliveryService;
    protected $codeService;
    protected $sidebarService;
    protected $validationService;
    protected $roleCheckService;

    public function __construct(
        ProjectService $projectService,
        ProjectCRUDService $crudService,
        ProjectAttachmentManagementService $attachmentManagementService,
        ProjectAuthorizationService $authorizationService,
        ProjectAnalyticsService $analyticsService,
        ProjectEmployeeAnalyticsService $employeeAnalyticsService,
        ProjectTeamRecommendationService $teamRecommendationService,
        ProjectNotesService $notesService,
        ProjectAttachmentReplyService $attachmentReplyService,
        ProjectServiceProgressService $serviceProgressService,
        ProjectAttachmentSharingHandlerService $attachmentSharingHandlerService,
        ProjectTaskService $taskService,
        ProjectAttachmentUploadService $attachmentUploadService,
        ParticipantService $participantService,
        AttachmentSharingService $attachmentSharingService,
        RevisionStatsService $revisionStatsService,
        EmployeeErrorStatisticsService $errorStatsService,
        ProjectDeliveryService $deliveryService,
        ProjectCodeService $codeService,
        ProjectSidebarService $sidebarService,
        ProjectValidationService $validationService,
        RoleCheckService $roleCheckService
    ) {
        $this->projectService = $projectService;
        $this->crudService = $crudService;
        $this->attachmentManagementService = $attachmentManagementService;
        $this->authorizationService = $authorizationService;
        $this->analyticsService = $analyticsService;
        $this->employeeAnalyticsService = $employeeAnalyticsService;
        $this->teamRecommendationService = $teamRecommendationService;
        $this->notesService = $notesService;
        $this->attachmentReplyService = $attachmentReplyService;
        $this->serviceProgressService = $serviceProgressService;
        $this->attachmentSharingHandlerService = $attachmentSharingHandlerService;
        $this->taskService = $taskService;
        $this->attachmentUploadService = $attachmentUploadService;
        $this->participantService = $participantService;
        $this->attachmentSharingService = $attachmentSharingService;
        $this->revisionStatsService = $revisionStatsService;
        $this->errorStatsService = $errorStatsService;
        $this->deliveryService = $deliveryService;
        $this->codeService = $codeService;
        $this->sidebarService = $sidebarService;
        $this->validationService = $validationService;
        $this->roleCheckService = $roleCheckService;
    }

    /**
     * عرض صفحة قائمة المشاريع لتغيير الحالة
     */
    public function changeStatusIndex(Request $request)
    {
        // جلب جميع المشاريع للقائمة المنسدلة (مرتبة من الأحدث)
        $allProjects = Project::with('client')
            ->orderBy('created_at', 'desc')
            ->get(['id', 'name', 'code', 'status', 'created_at']);

        $query = Project::query()->with('client');

        // فلتر بالحالة
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // إذا تم اختيار مشروع محدد من القائمة
        if ($request->filled('project_id')) {
            $query->where('id', $request->project_id);
        }

        $projects = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        return view('projects.internal-delivery.index', compact('projects', 'allProjects'));
    }

    /**
     * عرض صفحة تغيير حالة المشروع
     */
    public function changeStatus(Project $project)
    {
        return view('projects.internal-delivery.change-status', compact('project'));
    }

    /**
     * تحديث حالة المشروع
     */
    public function updateStatus(Request $request, Project $project)
    {
        $request->validate([
            'status' => 'required|string|in:جديد,جاري التنفيذ,مكتمل,ملغي',
            'delivery_type' => 'required|string|in:مسودة,كامل',
            'delivery_notes' => 'nullable|string|max:1000',
        ]);

        $oldStatus = $project->status;
        $newStatus = $request->status;
        $oldDeliveryType = $project->delivery_type;
        $newDeliveryType = $request->delivery_type;

        // الحصول على التاريخ والوقت الحالي من NTP
        $currentDateTime = $this->getCurrentCairoTime();

        // تحديث البيانات
        $project->status = $newStatus;
        $project->actual_delivery_date = $currentDateTime;
        $project->delivery_type = $request->delivery_type;

        if ($request->has('delivery_notes')) {
            $project->delivery_notes = $request->delivery_notes;
        }

        $project->save();

        // تسجيل النشاط
        $properties = [
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => Auth::user()->name,
            'delivery_date' => $currentDateTime->format('Y-m-d H:i:s'),
            'old_delivery_type' => $oldDeliveryType,
            'new_delivery_type' => $newDeliveryType,
        ];

        activity()
            ->performedOn($project)
            ->causedBy(Auth::user())
            ->withProperties($properties)
            ->log('تم تحديث التسليم الداخلي للمشروع');

        $message = 'تم تحديث حالة المشروع "' . $project->name . '" بنجاح من "' . $oldStatus . '" إلى "' . $newStatus . '"';
        $message .= ' - نوع التسليم: ' . $newDeliveryType;
        $message .= ' - تاريخ التسليم: ' . $currentDateTime->format('Y-m-d H:i:s');

        return redirect()->route('projects.internal-delivery.index')
            ->with('success', $message);
    }
}
