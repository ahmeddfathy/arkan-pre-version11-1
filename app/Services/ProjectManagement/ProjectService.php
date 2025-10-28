<?php

namespace App\Services\ProjectManagement;

use App\Models\Project;
use App\Models\Client;
use App\Models\CompanyService;
use App\Models\Package;
use App\Models\User;
use App\Models\ProjectServiceUser;
use App\Models\TemplateTaskUser;
use App\Models\TaskUser;
use App\Services\Auth\RoleCheckService;
use App\Services\Notifications\ProjectNotificationService;
use App\Services\ProjectManagement\TeamLeaderAssignmentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProjectService
{
    protected $storageService;
    protected $roleCheckService;
    protected $notificationService;
    protected $teamLeaderAssignmentService;

    public function __construct(
        ProjectStorageService $storageService,
        RoleCheckService $roleCheckService,
        ProjectNotificationService $notificationService,
        TeamLeaderAssignmentService $teamLeaderAssignmentService
    ) {
        $this->storageService = $storageService;
        $this->roleCheckService = $roleCheckService;
        $this->notificationService = $notificationService;
        $this->teamLeaderAssignmentService = $teamLeaderAssignmentService;
    }

    public function getProjects()
    {
        $user = Auth::user();
        $isAdmin = $this->roleCheckService->userHasRole(['hr', 'company_manager', 'project_manager', 'sales_employee', 'operation_assistant']);

        $projectsQuery = Project::with(['client', 'services']);

        if (!$isAdmin) {
            $userProjectIds = DB::table('project_service_user')
                ->where('user_id', $user->id)
                ->pluck('project_id')
                ->toArray();

            $projectsQuery->whereIn('id', $userProjectIds);
        }

        return $projectsQuery->orderBy('is_urgent', 'desc')->latest()->paginate(10);
    }


    public function createProject(array $data)
    {
        // زيادة وقت التنفيذ لتجنب timeout عند إنشاء المشروع
        @set_time_limit(180);

        try {
            DB::beginTransaction();

            $project = Project::create([
                'name' => $data['name'],
                'company_type' => $data['company_type'],
                'description' => $data['description'],
                'client_id' => $data['client_id'],
                'start_date' => $data['start_date'],
                'team_delivery_date' => $data['team_delivery_date'] ?? null,
                'actual_delivery_date' => $data['actual_delivery_date'] ?? null,
                'client_agreed_delivery_date' => $data['client_agreed_delivery_date'] ?? null,
                'status' => 'جديد', // تأكيد أن المشاريع الجديدة تبدأ بحالة "جديد" دائماً
                'is_urgent' => isset($data['is_urgent']) ? (bool)$data['is_urgent'] : false,
                'preparation_enabled' => isset($data['preparation_enabled']) ? (bool)$data['preparation_enabled'] : false,
                'preparation_start_date' => $data['preparation_start_date'] ?? null,
                'preparation_days' => $data['preparation_days'] ?? null,
                'total_points' => 0,
                'manager' => Auth::user()->name,
                'note' => $data['note'],
                'package_id' => $data['package_id'] ?? null,
                'code' => $data['code'] ?? null,
            ]);

            // إضافة الخدمات المحددة فقط (سواء من باقة أو اختيار يدوي)
            // عند اختيار باقة، الفرونت إند يرسل فقط الخدمات المحددة (بعد الاستثناء)
            if (isset($data['selected_services']) && is_array($data['selected_services'])) {
                $servicesData = [];
                foreach ($data['selected_services'] as $serviceId) {
                    $status = $data['service_statuses'][$serviceId] ?? 'لم تبدأ';

                    // إنشاء بيانات افتراضية فارغة للحقول الديناميكية
                    $service = CompanyService::with('dataFields')->find($serviceId);
                    $defaultServiceData = [];
                    if ($service && $service->dataFields) {
                        foreach ($service->dataFields as $field) {
                            $defaultServiceData[$field->field_name] = null;
                        }
                    }

                    $servicesData[$serviceId] = [
                        'service_status' => $status,
                        'service_data' => !empty($defaultServiceData) ? json_encode($defaultServiceData) : null
                    ];
                }

                if (!empty($servicesData)) {
                    $project->services()->attach($servicesData);
                }
            }

            $project->calculateTotalPoints();

            // محاولة إنشاء الفولدرات على Wasabi (اختياري - لن يعطل إنشاء المشروع)
            try {
                $this->storageService->createProjectFolderStructure($project);
            } catch (\Exception $e) {
                // تسجيل الخطأ فقط - لا نوقف إنشاء المشروع
                Log::warning('فشل إنشاء فولدرات المشروع على Wasabi - يمكن إنشاؤها لاحقاً', [
                    'project_id' => $project->id,
                    'error' => $e->getMessage()
                ]);
            }

            // إرسال إشعار لفريق العمليات
            $this->notificationService->notifyOperationAssistantNewProject($project, Auth::user());

            // ✅ إضافة Team Leaders تلقائياً لخدمات المشروع بشكل دوري (Round Robin)
            try {
                $teamLeaderResult = $this->teamLeaderAssignmentService->assignTeamLeadersToProjectServices($project);

                if ($teamLeaderResult['success']) {
                    Log::info('✅ تم تعيين Team Leaders تلقائياً', [
                        'project_id' => $project->id,
                        'success_count' => $teamLeaderResult['success_count'] ?? 0,
                        'failure_count' => $teamLeaderResult['failure_count'] ?? 0
                    ]);
                } else {
                    Log::warning('⚠️ فشل تعيين Team Leaders تلقائياً', [
                        'project_id' => $project->id,
                        'message' => $teamLeaderResult['message'] ?? 'Unknown error'
                    ]);
                }
            } catch (\Exception $e) {
                // عدم إيقاف إنشاء المشروع إذا فشل تعيين Team Leaders
                Log::error('❌ خطأ في تعيين Team Leaders تلقائياً', [
                    'project_id' => $project->id,
                    'error' => $e->getMessage()
                ]);
            }

            DB::commit();

            return [
                'success' => true,
                'project' => $project,
                'team_leaders_assigned' => $teamLeaderResult['success_count'] ?? 0,
                'assignment_message' => $teamLeaderResult['message'] ?? 'Team Leaders assigned'
            ];
        } catch (\Exception $e) {
            DB::rollback();
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء المشروع: ' . $e->getMessage()
            ];
        }
    }

    public function updateProject(Project $project, array $data)
    {
        try {
            DB::beginTransaction();

            // احفظ القيم القديمة للمقارنة
            $oldName = $project->name;
            $oldClientId = $project->client_id;
            $oldServices = $project->services->pluck('id')->sort()->values()->toArray();

            $project->update([
                'name' => $data['name'],
                'company_type' => $data['company_type'] ?? $project->company_type,
                'description' => $data['description'],
                'client_id' => $data['client_id'],
                'start_date' => $data['start_date'],
                'team_delivery_date' => $data['team_delivery_date'] ?? $project->team_delivery_date,
                'actual_delivery_date' => $data['actual_delivery_date'] ?? $project->actual_delivery_date,
                'client_agreed_delivery_date' => $data['client_agreed_delivery_date'] ?? $project->client_agreed_delivery_date,
                'status' => $data['status'],
                'is_urgent' => isset($data['is_urgent']) ? (bool)$data['is_urgent'] : $project->is_urgent,
                'preparation_enabled' => isset($data['preparation_enabled']) ? (bool)$data['preparation_enabled'] : $project->preparation_enabled,
                'preparation_start_date' => $data['preparation_start_date'] ?? $project->preparation_start_date,
                'preparation_days' => $data['preparation_days'] ?? $project->preparation_days,
                'manager' => $data['manager'],
                'note' => $data['note'],
                'package_id' => $data['package_id'] ?? null,

            ]);

            $project->services()->detach();

            // إضافة الخدمات المحددة فقط (سواء من باقة أو اختيار يدوي)
            // عند اختيار باقة، الفرونت إند يرسل فقط الخدمات المحددة (بعد الاستثناء والإضافة)
            $newServices = [];
            if (isset($data['selected_services']) && is_array($data['selected_services'])) {
                $servicesData = [];
                foreach ($data['selected_services'] as $serviceId) {
                    $status = $data['service_statuses'][$serviceId] ?? 'لم تبدأ';

                    // إنشاء بيانات افتراضية فارغة للحقول الديناميكية
                    $service = CompanyService::with('dataFields')->find($serviceId);
                    $defaultServiceData = [];
                    if ($service && $service->dataFields) {
                        foreach ($service->dataFields as $field) {
                            $defaultServiceData[$field->field_name] = null;
                        }
                    }

                    $servicesData[$serviceId] = [
                        'service_status' => $status,
                        'service_data' => !empty($defaultServiceData) ? json_encode($defaultServiceData) : null
                    ];
                    $newServices[] = (int)$serviceId;
                }

                if (!empty($servicesData)) {
                    $project->services()->attach($servicesData);
                }
            }

            $project->calculateTotalPoints();

            // تحديد ما إذا كان هناك تغيير في بنية الفولدرات
            $newServices = array_unique($newServices);
            sort($newServices);

            $needsFolderUpdate = false;
            $reasons = [];

            // فحص التغييرات التي تؤثر على بنية الفولدرات
            if ($oldName !== $data['name']) {
                $needsFolderUpdate = true;
                $reasons[] = 'تغيير اسم المشروع';
                $this->storageService->updateProjectAttachmentPaths($project, $oldName);
            }

            if ($oldClientId !== $data['client_id']) {
                $needsFolderUpdate = true;
                $reasons[] = 'تغيير العميل';
            }

            if ($oldServices !== $newServices) {
                $needsFolderUpdate = true;
                $reasons[] = 'تغيير الخدمات';
            }

            // تحديث بنية الفولدرات فقط عند الحاجة
            if ($needsFolderUpdate) {
                Log::info("تحديث بنية فولدرات المشروع {$project->name} - الأسباب: " . implode(', ', $reasons));
                try {
                    $this->storageService->createProjectFolderStructure($project);
                    $folderUpdated = true;
                    $folderUpdateSkipped = false;
                } catch (\Exception $e) {
                    // تسجيل الخطأ فقط - لا نوقف تحديث المشروع
                    Log::warning('فشل تحديث فولدرات المشروع على Wasabi', [
                        'project_id' => $project->id,
                        'error' => $e->getMessage()
                    ]);
                    $folderUpdated = false;
                    $folderUpdateSkipped = true;
                }
            } else {
                Log::info("تم تخطي تحديث فولدرات المشروع {$project->name} - لا توجد تغييرات تؤثر على البنية");
                $folderUpdated = false;
                $folderUpdateSkipped = true;
            }

            DB::commit();

            return [
                'success' => true,
                'project' => $project,
                'folder_updated' => $folderUpdated,
                'folder_update_skipped' => $folderUpdateSkipped,
                'optimization_reasons' => $folderUpdateSkipped ? ['لا توجد تغييرات تؤثر على بنية الفولدرات'] : $reasons
            ];
        } catch (\Exception $e) {
            DB::rollback();
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث المشروع: ' . $e->getMessage()
            ];
        }
    }

    public function deleteProject(Project $project)
    {
        try {
            $project->delete();
            return ['success' => true];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف المشروع: ' . $e->getMessage()
            ];
        }
    }

    public function getProjectDetails(Project $project)
    {
        $project->load(['client', 'services', 'package', 'attachments']);

        $userIds = ProjectServiceUser::where('project_id', $project->id)
            ->pluck('user_id')
            ->unique();

        $projectUsers = User::whereIn('id', $userIds)->get();

        // فقط مهام المستخدم الحالي (أمان أكثر)
        $currentUser = Auth::user();
        $userTasks = [];
        $userActualTasks = [];

        // الحصول على الخدمات التي يشارك فيها المستخدم الحالي في هذا المشروع
        $currentUserServiceIds = DB::table('project_service_user')
            ->where('project_id', $project->id)
            ->where('user_id', $currentUser->id)
            ->pluck('service_id')
            ->toArray();

        // مهام القوالب للمستخدم الحالي فقط ومن خدماته فقط
        $userTasks[$currentUser->id] = TemplateTaskUser::with(['templateTask.template.service'])
            ->where('user_id', $currentUser->id)
            ->where('project_id', $project->id)
            ->whereHas('templateTask.template', function($query) use ($currentUserServiceIds) {
                $query->whereIn('service_id', $currentUserServiceIds);
            })
            ->get();

        // المهام العادية للمستخدم الحالي فقط ومن خدماته فقط
        $userActualTasks[$currentUser->id] = TaskUser::with(['task.service'])
            ->whereHas('task', function($query) use ($project, $currentUserServiceIds) {
                $query->where('project_id', $project->id)
                      ->whereIn('service_id', $currentUserServiceIds);
            })
            ->where('user_id', $currentUser->id)
            ->get();

        $allUsers = User::all();
        $packageServices = collect([]);
        $serviceUsersMap = [];

        if ($project->package_id) {
            if ($project->package && is_array($project->package->services)) {
                $packageServices = CompanyService::whereIn('id', $project->package->services)
                    ->with('requiredRoles')
                    ->get();

                foreach ($packageServices as $service) {
                    $serviceUsersMap[$service->id] = User::where('department', $service->department)->get();
                }
            }
        } else {
            foreach ($project->services as $service) {
                $serviceUsersMap[$service->id] = User::where('department', $service->department)->get();
            }
        }

        // جلب الفرق حسب الأقسام
        $departmentTeams = [];
        $teamMembers = [];

        $departments = collect();
        if ($project->package_id && $project->package && is_array($project->package->services)) {
            $departments = CompanyService::whereIn('id', $project->package->services)
                ->pluck('department')->unique()->filter();
        } else {
            $departments = $project->services->pluck('department')->unique()->filter();
        }

        foreach ($departments as $department) {
            // جلب الفرق المرتبطة بهذا القسم
            $teams = DB::table('teams')
                ->join('users as owners', 'teams.user_id', '=', 'owners.id')
                ->leftJoin('team_user', 'teams.id', '=', 'team_user.team_id')
                ->leftJoin('users as members', 'team_user.user_id', '=', 'members.id')
                ->where(function($query) use ($department) {
                    $query->where('owners.department', $department)
                          ->orWhere('members.department', $department);
                })
                ->where('teams.personal_team', false)
                ->select('teams.id', 'teams.name', 'teams.user_id', 'owners.name as owner_name')
                ->distinct()
                ->orderBy('teams.name')
                ->get();

            $departmentTeams[$department] = $teams;

            // جلب أعضاء كل فريق
            foreach ($teams as $team) {
                $members = DB::table('users')
                    ->leftJoin('team_user', function($join) use ($team) {
                        $join->on('users.id', '=', 'team_user.user_id')
                             ->where('team_user.team_id', '=', $team->id);
                    })
                    ->where(function($query) use ($team) {
                        $query->where('users.id', $team->user_id)
                              ->orWhereNotNull('team_user.user_id');
                    })
                    ->select('users.id', 'users.name', 'users.email', 'users.department')
                    ->orderBy('users.name')
                    ->get();

                $teamMembers[$team->id] = $members;
            }
        }


        $currentUser = Auth::user();
        $isOperationAssistant = $this->roleCheckService->userHasRole('operation_assistant');
        $isAdmin = $this->roleCheckService->userHasRole(['hr', 'company_manager', 'project_manager', 'sales_employee']);


        $isOperationAssistantOnly = $isOperationAssistant && !$isAdmin;

        // تحديد المشاركين الذين لا يملكون مهام قوالب لكل خدمة
        $participantsWithTaskStatus = [];
        $allParticipants = ProjectServiceUser::where('project_id', $project->id)
            ->with('user', 'service')
            ->get();

        foreach ($allParticipants as $participant) {
            $hasTemplateTasks = TemplateTaskUser::whereHas('templateTask.template', function($query) use ($participant) {
                $query->where('service_id', $participant->service_id);
            })
            ->where('user_id', $participant->user_id)
            ->where('project_id', $project->id)
            ->exists();

            $participantsWithTaskStatus[] = [
                'user_id' => $participant->user_id,
                'service_id' => $participant->service_id,
                'has_template_tasks' => $hasTemplateTasks
            ];
        }

        // إعداد بيانات المرفقات والمجلدات
        $attachmentData = $this->prepareAttachmentData($project, $currentUser, $isAdmin);

        // إعداد صلاحيات إدارة المشاركين
        $authorizationService = app(ProjectAuthorizationService::class);
        $canManageParticipants = $authorizationService->canManageProjectParticipants($project);
        $canViewTeamSuggestion = $authorizationService->canViewTeamSuggestion();

        // الجدول يظهر للكل عشان يشوفوا مين في المشروع
        $canViewParticipants = true;

        return [
            'project' => $project,
            'allUsers' => $allUsers,
            'packageServices' => $packageServices,
            'serviceUsersMap' => $serviceUsersMap,
            'projectUsers' => $projectUsers,
            'userTasks' => $userTasks,
            'userActualTasks' => $userActualTasks,
            'departmentTeams' => $departmentTeams,
            'teamMembers' => $teamMembers,
            'currentUser' => $currentUser,
            'isOperationAssistant' => $isOperationAssistant,
            'isAdmin' => $isAdmin,
            'isOperationAssistantOnly' => $isOperationAssistantOnly,
            'participantsWithTaskStatus' => $participantsWithTaskStatus,
            'fixedTypes' => $attachmentData['fixedTypes'],
            'userServices' => $attachmentData['userServices'],
            'filteredAttachments' => $attachmentData['filteredAttachments'],
            'isUserAdmin' => $attachmentData['isAdmin'],
            'canManageParticipants' => $canManageParticipants,
            'canViewTeamSuggestion' => $canViewTeamSuggestion,
            'canViewParticipants' => $canViewParticipants
        ];
    }

    /**
     * إعداد بيانات المرفقات والمجلدات حسب صلاحيات المستخدم
     */
    private function prepareAttachmentData(Project $project, $currentUser, $isAdmin)
    {
        $fixedTypes = ['مرفقات أولية', 'تقارير مكالمات', 'مرفقات من العميل', 'عقود', 'الدراسه النهائيه'];

        // التحقق من صلاحيات المستخدم
        $userRoles = $currentUser->roles->pluck('name')->toArray() ?? [];
        $isUserAdmin = !empty(array_intersect($userRoles, ['hr', 'admin', 'company_manager', 'project_manager']));

        if ($isUserAdmin) {
            // الأدمن يرى كل الخدمات
            $projectServices = $project->services->pluck('name')->toArray();
            if ($project->package_id && $project->package && is_array($project->package->services)) {
                $packageServices = CompanyService::whereIn('id', $project->package->services)->pluck('name')->toArray();
                $projectServices = array_merge($projectServices, $packageServices);
            }
            $userServices = array_unique($projectServices);
        } else {
            // المستخدم العادي يرى خدماته فقط
            $userServiceIds = DB::table('project_service_user')
                ->where('project_id', $project->id)
                ->where('user_id', $currentUser->id)
                ->pluck('service_id')
                ->toArray();

            // جلب أسماء الخدمات التي يشارك فيها المستخدم فقط
            $userServices = CompanyService::whereIn('id', $userServiceIds)->pluck('name')->toArray();
        }

        // فلترة المرفقات حسب الصلاحيات
        $filteredAttachments = $this->filterAttachmentsByUser($project->attachments, $currentUser, $isUserAdmin);

        return [
            'fixedTypes' => $fixedTypes,
            'userServices' => $userServices,
            'filteredAttachments' => $filteredAttachments,
            'isAdmin' => $isUserAdmin
        ];
    }

    /**
     * فلترة المرفقات حسب صلاحيات المستخدم
     */
    private function filterAttachmentsByUser($attachments, $currentUser, $isAdmin)
    {
        if ($isAdmin) {
            return $attachments; // الأدمن يرى كل المرفقات
        }

        // المستخدم العادي يرى مرفقاته ومرفقات مهامه فقط
        return $attachments->filter(function($attachment) use ($currentUser) {
            // إذا كان الملف بدون مهمة (عام) أو من رفعه المستخدم الحالي
            if (empty($attachment->task_type) || $attachment->user_id == $currentUser->id) {
                return true;
            }

            // إذا كان مربوط بمهمة قالب، تأكد أنها مهمة المستخدم الحالي
            if ($attachment->task_type == 'template_task' && $attachment->templateTaskUser) {
                return $attachment->templateTaskUser->user_id == $currentUser->id;
            }

            // إذا كان مربوط بمهمة عادية، تأكد أنها مهمة المستخدم الحالي
            if ($attachment->task_type == 'regular_task' && $attachment->taskUser) {
                return $attachment->taskUser->user_id == $currentUser->id;
            }

            return false;
        });
    }
}
