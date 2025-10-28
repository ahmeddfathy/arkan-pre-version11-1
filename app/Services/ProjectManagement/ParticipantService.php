<?php

namespace App\Services\ProjectManagement;

use App\Models\Project;
use App\Models\CompanyService;
use App\Models\TaskTemplate;
use App\Models\TemplateTask;
use App\Models\ProjectServiceUser;
use App\Models\TemplateTaskUser;
use App\Models\User;
use App\Services\Notifications\ProjectNotificationService;
use App\Services\SlackNotificationService;
use App\Services\ProjectPointsValidationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ParticipantService
{
    protected $notificationService;
    protected $slackNotificationService;
    protected $pointsValidationService;

    public function __construct(
        ProjectNotificationService $notificationService,
        SlackNotificationService $slackNotificationService,
        ProjectPointsValidationService $pointsValidationService
    ) {
        $this->notificationService = $notificationService;
        $this->slackNotificationService = $slackNotificationService;
        $this->pointsValidationService = $pointsValidationService;
    }

    public function assignParticipants(Project $project, array $participantsData, array $teamData = [], array $deadlines = [], array $projectShares = [], array $participantRoles = [])
    {
        try {
            DB::beginTransaction();

            foreach ($participantsData as $serviceId => $userIds) {
                if (empty($userIds) || !is_array($userIds)) {
                    continue;
                }

                $service = CompanyService::find($serviceId);

                foreach ($userIds as $userId) {
                    $existingLink = ProjectServiceUser::where([
                        'project_id' => $project->id,
                        'service_id' => $serviceId,
                        'user_id' => $userId,
                    ])->exists();

                    if ($existingLink) {
                        continue;
                    }

                    // جلب التيم الخاص بالمستخدم إن وجد
                    $user = User::find($userId);
                    $teamId = null;

                    if ($user) {
                        // البحث عن التيم الذي يملكه المستخدم
                        $userTeam = $user->ownedTeams()->where('personal_team', false)->first();
                        $teamId = $userTeam ? $userTeam->id : null;
                    }

                    // التحقق من وجود deadline للمستخدم
                    $userDeadline = null;
                    if (isset($deadlines[$userId]) && !empty($deadlines[$userId])) {
                        $userDeadline = $deadlines[$userId];
                    }

                    // التحقق من نسبة المشاركة (project_share) - افتراضي 1.00 (مشروع كامل)
                    $projectShare = 1.00;
                    if (isset($projectShares[$userId]) && !empty($projectShares[$userId])) {
                        $projectShare = (float) $projectShares[$userId];
                    }

                    // التحقق من الدور المحدد للمشارك
                    $roleId = null;
                    if (isset($participantRoles[$serviceId]) && !empty($participantRoles[$serviceId])) {
                        $roleId = (int) $participantRoles[$serviceId];

                        // التحقق من أن المستخدم لديه هذا الدور
                        if (!$user) {
                            $user = User::find($userId);
                        }

                        if ($user) {
                            $hasRole = $user->roles()->where('roles.id', $roleId)->exists();

                            if (!$hasRole) {
                                DB::rollBack();
                                $role = \Spatie\Permission\Models\Role::find($roleId);
                                $roleName = $role ? $role->display_name ?? $role->name : 'غير محدد';

                                return [
                                    'success' => false,
                                    'message' => 'المستخدم ' . $user->name . ' ليس لديه دور "' . $roleName . '" المطلوب لهذه الخدمة'
                                ];
                            }
                        }

                        \Log::info("Role assigned for user $userId in service $serviceId: $roleId");
                    } else {
                        \Log::info("No role assigned for user $userId in service $serviceId");
                    }

                    ProjectServiceUser::create([
                        'project_id' => $project->id,
                        'service_id' => $serviceId,
                        'user_id' => $userId,
                        'team_id' => $teamId,
                        'role_id' => $roleId,
                        'deadline' => $userDeadline,
                        'project_share' => $projectShare,
                    ]);


                    try {
                        $user = User::find($userId);
                        $currentUser = Auth::user();

                        if ($user && $service) {
                            $this->notificationService->notifyUserAddedToProject(
                                $user,
                                $project,
                                $service,
                                $currentUser
                            );

                            // إرسال إشعار Slack إذا كان المستخدم لديه slack_user_id
                            if ($user->slack_user_id) {
                                $this->slackNotificationService->sendProjectAssignmentNotification(
                                    $project,
                                    $user,
                                    $currentUser
                                );
                            }
                        }
                    } catch (\Exception $e) {
                        Log::error('خطأ في إرسال إشعار إضافة مشارك للمشروع', [
                            'error' => $e->getMessage(),
                            'project_id' => $project->id,
                            'service_id' => $serviceId,
                            'user_id' => $userId
                        ]);
                    }

                    // ✅ سجل فقط أن المستخدم تم إضافته - المهام ستضاف لاحقاً عند الاختيار
                    Log::info('تم إضافة مستخدم للمشروع بدون مهام تلقائية:', [
                        'serviceId' => $serviceId,
                        'userId' => $userId,
                        'projectId' => $project->id
                    ]);
                }
            }

            DB::commit();
            return [
                'success' => true,
                'message' => 'تم إضافة المشاركين بنجاح'
            ];

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('خطأ في إضافة المشاركين للمشروع', [
                'error' => $e->getMessage(),
                'project_id' => $project->id,
                'participants_data' => $participantsData,
                'deadlines' => $deadlines
            ]);
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء إضافة المشاركين: ' . $e->getMessage()
            ];
        }
    }

    /**
     * تحديث الموعد النهائي لمستخدم في مشروع
     */
    public function updateUserDeadline(Project $project, $userId, $serviceId, $deadline = null)
    {
        $projectServiceUser = ProjectServiceUser::where([
            'project_id' => $project->id,
            'user_id' => $userId,
            'service_id' => $serviceId,
        ])->first();

        if (!$projectServiceUser) {
            return [
                'success' => false,
                'message' => 'المستخدم غير مشارك في هذا المشروع'
            ];
        }

        $projectServiceUser->update([
            'deadline' => $deadline
        ]);

        return [
            'success' => true,
            'message' => $deadline ? 'تم تحديث الموعد النهائي بنجاح' : 'تم حذف الموعد النهائي'
        ];
    }

    /**
     * ✅ إضافة مهام قوالب محددة للمستخدم
     */
    public function assignSelectedTemplateTasks(Project $project, $userId, $serviceId, array $selectedTaskIds, $taskDeadlines = null)
    {
        try {
            DB::beginTransaction();

            $assignedTasks = [];

            // جلب المستخدم للتحقق من دوره
            $user = User::find($userId);
            if (!$user) {
                throw new \Exception('المستخدم غير موجود');
            }

            // جلب الخدمة للتحقق من حدود النقاط
            $service = CompanyService::find($serviceId);
            if (!$service) {
                throw new \Exception('الخدمة غير موجودة');
            }

            // التحقق من حدود النقاط للخدمة في المشروع
            if ($service->hasMaxPointsLimit()) {
                // حساب مجموع نقاط المهام المطلوب تخصيصها
                $totalRequestedPoints = 0;
                $templateTasks = TemplateTask::whereIn('id', $selectedTaskIds)
                    ->whereHas('template', function ($q) use ($serviceId) {
                        $q->where('service_id', $serviceId);
                    })
                    ->get();

                foreach ($templateTasks as $templateTask) {
                    $totalRequestedPoints += $templateTask->points ?? 0;
                }

                // التحقق من إمكانية إضافة هذه النقاط
                $pointsValidation = $this->pointsValidationService->canAddTaskToProject($project, $service, $totalRequestedPoints);

                if (!$pointsValidation['can_add']) {
                    throw new \Exception($pointsValidation['message']);
                }
            }

            $userRoleIds = $user->roles->pluck('id')->toArray();

            foreach ($selectedTaskIds as $templateTaskId) {
                // التحقق من أن المهمة تنتمي للخدمة المحددة مع فلترة الدور
                $templateTask = TemplateTask::whereHas('template', function ($q) use ($serviceId) {
                    $q->where('service_id', $serviceId)
                      ->where('is_active', true);
                })->where(function ($query) use ($userRoleIds) {
                    $query->whereNull('role_id') // المهام العامة
                          ->orWhereIn('role_id', $userRoleIds); // المهام لأدوار المستخدم
                })->find($templateTaskId);

                if (!$templateTask) {
                    Log::warning('User tried to assign unauthorized template task', [
                        'user_id' => $userId,
                        'template_task_id' => $templateTaskId,
                        'user_roles' => $userRoleIds
                    ]);
                    continue; // تجاهل المهام غير المناسبة لدور المستخدم
                }

                // الحصول على deadline للمهمة الحالية
                $currentTaskDeadline = null;
                if ($taskDeadlines && isset($taskDeadlines[$templateTaskId]) && $taskDeadlines[$templateTaskId]) {
                    $currentTaskDeadline = Carbon::parse($taskDeadlines[$templateTaskId]);
                }

                // إنشاء TemplateTaskUser إذا لم تكن موجودة
                $templateTaskUser = TemplateTaskUser::firstOrCreate([
                    'template_task_id' => $templateTask->id,
                    'user_id' => $userId,
                    'project_id' => $project->id,
                ], [
                    'project_id' => $project->id,
                    'assigned_by' => Auth::id(),
                    'assigned_at' => now(),
                    'deadline' => $currentTaskDeadline,
                    'status' => 'new',
                    'started_at' => null,
                    'paused_at' => null,
                    'completed_at' => null,
                    'actual_minutes' => 0
                ]);

                // نسخ البنود من مهمة القالب إلى TemplateTaskUser عند الإنشاء
                if ($templateTaskUser->wasRecentlyCreated) {
                    $taskItemService = app(\App\Services\Tasks\TaskItemService::class);
                    $taskItemService->copyItemsToTemplateTaskUser($templateTask, $templateTaskUser);
                }

                // إرسال إشعار Slack إذا تم إنشاء template task جديد
                if ($templateTaskUser->wasRecentlyCreated) {
                    $user = User::find($userId);
                    $currentUser = Auth::user();

                    if ($user && $user->slack_user_id && $currentUser) {
                        // إنشاء كائن mock للمهمة لإرسال الإشعار
                        $service = CompanyService::find($serviceId);
                        $mockTask = (object) [
                            'id' => $templateTask->id,
                            'name' => $templateTask->name,
                            'description' => $templateTask->description,
                            'project_id' => $project->id,
                            'service_id' => $serviceId,
                            'due_date' => null,
                            'estimated_hours' => $templateTask->estimated_hours,
                            'estimated_minutes' => $templateTask->estimated_minutes,
                            'project_name' => $project->name,
                            'service_name' => $service ? $service->name : 'غير محدد'
                        ];

                        $this->slackNotificationService->sendTaskAssignmentNotification(
                            $mockTask,
                            $user,
                            $currentUser
                        );
                    }
                }

                $assignedTasks[] = [
                    'id' => $templateTaskUser->id,
                    'template_task_id' => $templateTask->id,
                    'name' => $templateTask->name,
                    'estimated_hours' => $templateTask->estimated_hours ?? 0,
                    'estimated_minutes' => $templateTask->estimated_minutes ?? 0
                ];
            }

            DB::commit();

            Log::info('تم إضافة مهام مختارة للمستخدم:', [
                'userId' => $userId,
                'serviceId' => $serviceId,
                'projectId' => $project->id,
                'assignedTasksCount' => count($assignedTasks)
            ]);

            return [
                'success' => true,
                'assigned_tasks' => $assignedTasks,
                'message' => 'تم إضافة ' . count($assignedTasks) . ' مهمة بنجاح'
            ];

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('خطأ في إضافة مهام القوالب:', [
                'error' => $e->getMessage(),
                'userId' => $userId,
                'serviceId' => $serviceId,
                'projectId' => $project->id
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء إضافة المهام: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ✅ جلب مهام القوالب المتاحة للخدمة مع فلترة حسب دور المستخدم
     */
    public function getAvailableTemplateTasks($serviceId, $projectId = null, $userId = null)
    {
        try {
            $user = null;
            if ($userId) {
                $user = User::find($userId);
            }

            // جلب المهام مع فلترة حسب الدور إذا تم تمرير user_id
            $templateTasksQuery = TemplateTask::whereHas('template', function ($q) use ($serviceId) {
                $q->where('service_id', $serviceId)
                  ->where('is_active', true);
            })->with(['template', 'role']);

            // فلترة حسب دور المستخدم إذا تم تمرير userId
            if ($user) {
                $userRoleIds = $user->roles->pluck('id')->toArray();
                $templateTasksQuery->where(function ($query) use ($userRoleIds) {
                    $query->whereNull('role_id') // المهام العامة (للجميع)
                          ->orWhereIn('role_id', $userRoleIds); // المهام للأدوار التي يملكها المستخدم
                });
            }

            $templateTasks = $templateTasksQuery->where('is_active', true)->get();

            return [
                'success' => true,
                'template_tasks' => $templateTasks->map(function ($task) use ($projectId) {
                    $taskData = [
                        'id' => $task->id,
                        'name' => $task->name,
                        'description' => $task->description,
                        'estimated_hours' => $task->estimated_hours ?? 0,
                        'estimated_minutes' => $task->estimated_minutes ?? 0,
                        'template_name' => $task->template->name ?? 'قالب غير محدد',
                        'role' => $task->role ? [
                            'id' => $task->role->id,
                            'name' => $task->role->name
                        ] : null,
                        'role_display' => $task->role ? $task->role->name : 'للجميع',
                        'is_role_specific' => !is_null($task->role_id),
                        'assigned_users' => []
                    ];

                    // إذا تم تمرير project_id، جلب المستخدمين المخصصين لهذه المهمة
                    if ($projectId) {
                        $assignedUsers = TemplateTaskUser::where('template_task_id', $task->id)
                            ->where('project_id', $projectId)
                            ->with('user:id,name,email')
                            ->get();

                        if ($assignedUsers->isNotEmpty()) {
                            $taskData['assigned_users'] = $assignedUsers->map(function ($assignedUser) {
                                return [
                                    'id' => $assignedUser->user->id,
                                    'name' => $assignedUser->user->name,
                                    'email' => $assignedUser->user->email,
                                    'status' => $assignedUser->status,
                                    'actual_minutes' => $assignedUser->actual_minutes ?? 0
                                ];
                            })->toArray();
                        }
                    }

                    return $taskData;
                })
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب المهام: ' . $e->getMessage()
            ];
        }
    }

    /**
     * إضافة مالك الفريق إلى المشروع
     */
    public function assignTeamOwner(Project $project, $serviceId, $teamId)
    {
        $team = \App\Models\Team::find($teamId);
        if (!$team) {
            return [
                'success' => false,
                'message' => 'الفريق المحدد غير موجود'
            ];
        }

        $ownerId = $team->user_id;

        // التحقق من عدم وجود ارتباط مسبق
        $existingLink = ProjectServiceUser::where([
            'project_id' => $project->id,
            'service_id' => $serviceId,
            'user_id' => $ownerId,
        ])->exists();

        if ($existingLink) {
            return [
                'success' => false,
                'message' => 'مالك الفريق مضاف مسبقاً للمشروع'
            ];
        }

        // إضافة مالك الفريق
        $participantsData = [
            $serviceId => [$ownerId]
        ];

        $this->assignParticipants($project, $participantsData);

        return [
            'success' => true,
            'message' => 'تم إضافة مالك الفريق بنجاح',
            'owner_id' => $ownerId,
            'owner_name' => $team->owner->name ?? 'غير معروف'
        ];
    }

    public function removeParticipant(Project $project, $userId, $serviceId)
    {
        try {
            DB::beginTransaction();

            $templateTaskIds = TemplateTask::whereHas('template', function ($query) use ($serviceId) {
                $query->where('service_id', $serviceId);
            })->pluck('id')->toArray();

            $activeTasksCount = TemplateTaskUser::where([
                'user_id' => $userId,
                'project_id' => $project->id
            ])->whereIn('template_task_id', $templateTaskIds)
              ->whereIn('status', ['in_progress', 'paused', 'completed'])
              ->count();

            if ($activeTasksCount > 0) {
                DB::rollback();
                return [
                    'success' => false,
                    'message' => "لا يمكن إزالة المستخدم لأن لديه {$activeTasksCount} من المهام قيد التنفيذ أو المكتملة.",
                    'active_tasks' => $activeTasksCount
                ];
            }

            $deleted = ProjectServiceUser::where([
                'project_id' => $project->id,
                'service_id' => $serviceId,
                'user_id' => $userId
            ])->delete();

            $tasksToDelete = TemplateTaskUser::where([
                'user_id' => $userId,
                'project_id' => $project->id,
                'status' => 'new'
            ])->whereIn('template_task_id', $templateTaskIds);

            $deletedTasksCount = $tasksToDelete->count();
            $tasksToDelete->delete();

            DB::commit();

            if ($deleted) {

                try {
                    $user = User::find($userId);
                    $service = CompanyService::find($serviceId);
                    $currentUser = Auth::user();

                    if ($user && $service) {
                        // إرسال الإشعار العادي
                        $this->notificationService->notifyUserRemovedFromProject(
                            $user,
                            $project,
                            $service,
                            $currentUser
                        );

                        // إرسال إشعار Slack إذا كان المستخدم لديه slack_user_id
                        if ($user->slack_user_id) {
                            $this->slackNotificationService->sendProjectRemovalNotification(
                                $project,
                                $user,
                                $currentUser
                            );
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('خطأ في إرسال إشعار إزالة مشارك من المشروع', [
                        'error' => $e->getMessage(),
                        'project_id' => $project->id,
                        'service_id' => $serviceId,
                        'user_id' => $userId
                    ]);
                }

                return [
                    'success' => true,
                    'message' => "تم إزالة المستخدم من المشروع وحذف {$deletedTasksCount} مهام غير مبدوءة.",
                    'deleted_tasks' => $deletedTasksCount,
                    'active_tasks' => 0
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'لم يتم العثور على ارتباط للمستخدم بهذه الخدمة في المشروع.'
                ];
            }
        } catch (\Exception $e) {
            DB::rollback();
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء إزالة المستخدم: ' . $e->getMessage()
            ];
        }
    }

    public function getProjectParticipants($projectId)
    {
        $serviceUsers = ProjectServiceUser::where('project_id', $projectId)
            ->with(['user', 'service'])
            ->get()
            ->groupBy('service_id');

        return $serviceUsers;
    }
}
