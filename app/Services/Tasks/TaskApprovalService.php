<?php

namespace App\Services\Tasks;

use App\Models\TaskUser;
use App\Models\TemplateTaskUser;
use App\Models\User;
use App\Models\Season;
use App\Models\UserSeasonPoint;
use App\Services\Slack\TaskSlackService;
use App\Services\Tasks\TaskNotificationService;
use App\Traits\HasNTPTime;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TaskApprovalService
{
    use HasNTPTime;

    protected $taskSlackService;
    protected $taskNotificationService;

    public function __construct(
        TaskSlackService $taskSlackService,
        TaskNotificationService $taskNotificationService
    ) {
        $this->taskSlackService = $taskSlackService;
        $this->taskNotificationService = $taskNotificationService;
    }

    /**
     * موافقة Team Leader على مهمة عادية
     */
    public function approveRegularTask(TaskUser $taskUser, int $awardedPoints = null, string $note = null): array
    {
        try {
            // التحقق من صلاحية المستخدم للاعتماد
            if (!$this->canApproveTask($taskUser)) {
                return [
                    'success' => false,
                    'message' => 'غير مصرح لك باعتماد هذه المهمة. فقط منشئ المهمة أو HR/Admin يمكنهم اعتمادها'
                ];
            }

            if (!$taskUser->canBeApproved()) {
                return [
                    'success' => false,
                    'message' => 'لا يمكن الموافقة على هذه المهمة. تأكد من أنها مكتملة وغير موافق عليها مسبقاً'
                ];
            }

            $originalPoints = $taskUser->task->points ?? 0;
            $finalPoints = $awardedPoints ?? $originalPoints;

            DB::transaction(function () use ($taskUser, $finalPoints, $note) {
                // تحديث معلومات الموافقة
                $taskUser->update([
                    'is_approved' => true,
                    'awarded_points' => $finalPoints,
                    'approval_note' => $note,
                    'approved_by' => Auth::id(),
                    'approved_at' => $this->getCurrentCairoTime()
                ]);

                // إضافة النقاط للمستخدم
                $this->addPointsToUser($taskUser->user, $finalPoints);
            });

            Log::info('Regular task approved', [
                'task_user_id' => $taskUser->id,
                'task_id' => $taskUser->task_id,
                'user_id' => $taskUser->user_id,
                'approved_by' => Auth::id(),
                'original_points' => $originalPoints,
                'awarded_points' => $finalPoints,
                'note' => $note
            ]);

            // 🚀 إرسال إشعار بالنقاط الجديدة للموظف
            try {
                $approvedBy = Auth::user();
                $this->taskNotificationService->notifyPointsAwarded(
                    $taskUser,
                    $approvedBy,
                    $finalPoints,
                    $note
                );
            } catch (\Exception $e) {
                Log::error('Failed to send points awarded notification', [
                    'error' => $e->getMessage(),
                    'task_user_id' => $taskUser->id,
                    'points' => $finalPoints
                ]);
            }

            // إرسال إشعار Slack للموظف
            try {
                $approverName = Auth::user()->name;
                $this->taskSlackService->sendTaskApprovalNotification(
                    $taskUser,
                    $finalPoints,
                    $approverName,
                    $note
                );
            } catch (\Exception $e) {
                Log::warning('Failed to send Slack notification for task approval', [
                    'task_user_id' => $taskUser->id,
                    'error' => $e->getMessage()
                ]);
                // لا نفشل العملية بسبب Slack
            }

            return [
                'success' => true,
                'message' => 'تم اعتماد المهمة بنجاح',
                'data' => [
                    'task_user' => $taskUser->fresh(),
                    'awarded_points' => $finalPoints,
                    'original_points' => $originalPoints
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Error approving regular task', [
                'task_user_id' => $taskUser->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء اعتماد المهمة: ' . $e->getMessage()
            ];
        }
    }

    /**
     * موافقة Team Leader على مهمة تمبليت
     */
    public function approveTemplateTask(TemplateTaskUser $templateTaskUser, int $awardedPoints = null, string $note = null): array
    {
        try {
            // التحقق من صلاحية المستخدم للاعتماد
            if (!$this->canApproveTask($templateTaskUser)) {
                return [
                    'success' => false,
                    'message' => 'غير مصرح لك باعتماد هذه المهمة. فقط من أضاف المهمة أو HR/Admin يمكنهم اعتمادها'
                ];
            }

            if (!$templateTaskUser->canBeApproved()) {
                return [
                    'success' => false,
                    'message' => 'لا يمكن الموافقة على هذه المهمة. تأكد من أنها مكتملة وغير موافق عليها مسبقاً'
                ];
            }

            $originalPoints = $templateTaskUser->templateTask->points ?? 0;
            $finalPoints = $awardedPoints ?? $originalPoints;

            DB::transaction(function () use ($templateTaskUser, $finalPoints, $note) {
                // تحديث معلومات الموافقة
                $templateTaskUser->update([
                    'is_approved' => true,
                    'awarded_points' => $finalPoints,
                    'approval_note' => $note,
                    'approved_by' => Auth::id(),
                    'approved_at' => $this->getCurrentCairoTime()
                ]);

                // إضافة النقاط للمستخدم
                $this->addPointsToUser($templateTaskUser->user, $finalPoints);
            });

            Log::info('Template task approved', [
                'template_task_user_id' => $templateTaskUser->id,
                'template_task_id' => $templateTaskUser->template_task_id,
                'user_id' => $templateTaskUser->user_id,
                'approved_by' => Auth::id(),
                'original_points' => $originalPoints,
                'awarded_points' => $finalPoints,
                'note' => $note
            ]);

            // 🚀 إرسال إشعار بالنقاط الجديدة للموظف (للمهام من القوالب)
            try {
                $approvedBy = Auth::user();
                $this->taskNotificationService->notifyTemplateTaskPointsAwarded(
                    $templateTaskUser,
                    $approvedBy,
                    $finalPoints,
                    $note
                );
            } catch (\Exception $e) {
                Log::error('Failed to send template task points notification', [
                    'error' => $e->getMessage(),
                    'template_task_user_id' => $templateTaskUser->id,
                    'points' => $finalPoints
                ]);
            }

            // إرسال إشعار Slack للموظف
            try {
                $approverName = Auth::user()->name;
                $this->taskSlackService->sendTaskApprovalNotification(
                    $templateTaskUser,
                    $finalPoints,
                    $approverName,
                    $note
                );
            } catch (\Exception $e) {
                Log::warning('Failed to send Slack notification for template task approval', [
                    'template_task_user_id' => $templateTaskUser->id,
                    'error' => $e->getMessage()
                ]);
                // لا نفشل العملية بسبب Slack
            }

            return [
                'success' => true,
                'message' => 'تم اعتماد المهمة بنجاح',
                'data' => [
                    'template_task_user' => $templateTaskUser->fresh(),
                    'awarded_points' => $finalPoints,
                    'original_points' => $originalPoints
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Error approving template task', [
                'template_task_user_id' => $templateTaskUser->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء اعتماد المهمة: ' . $e->getMessage()
            ];
        }
    }

    /**
     * رفض موافقة المهمة مع إرجاع النقاط
     */
    public function rejectTaskApproval($taskUser, string $reason = null): array
    {
        try {
            $isTemplate = $taskUser instanceof TemplateTaskUser;

            if (!$taskUser->isApproved()) {
                return [
                    'success' => false,
                    'message' => 'المهمة غير موافق عليها مسبقاً'
                ];
            }

            DB::transaction(function () use ($taskUser, $reason) {
                // حفظ النقاط التي تم منحها للإرجاع
                $pointsToReturn = $taskUser->awarded_points ?? 0;

                // إلغاء الموافقة
                $taskUser->update([
                    'is_approved' => false,
                    'awarded_points' => null,
                    'approval_note' => $reason ? "تم الرفض: " . $reason : "تم إلغاء الموافقة",
                    'approved_by' => null,
                    'approved_at' => null
                ]);

                // إرجاع النقاط من المستخدم
                if ($pointsToReturn > 0) {
                    $this->removePointsFromUser($taskUser->user, $pointsToReturn);
                }
            });

            return [
                'success' => true,
                'message' => 'تم إلغاء الموافقة وإرجاع النقاط بنجاح'
            ];

        } catch (\Exception $e) {
            Log::error('Error rejecting task approval', [
                'task_user_id' => $taskUser->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء إلغاء الموافقة: ' . $e->getMessage()
            ];
        }
    }

        /**
     * الحصول على جميع المهام للموافقة
     */
    public function getAllTasksForApproval(User $teamLeader = null): array
    {
        $user = $teamLeader ?? Auth::user();

        // التحقق من كون المستخدم HR أو Admin
        $userRoles = $user->roles->pluck('name')->toArray();
        $allowedRoles = ['hr', 'project_manager', 'company_manager', 'operations_manager'];
        $isHrOrAdmin = !empty(array_intersect($allowedRoles, $userRoles));

        // فحص إذا كان المستخدم لديه صلاحيات اعتماد في RoleApproval
        $userRoleIds = $user->roles->pluck('id')->toArray();
        $hasApprovalPermissions = !empty($userRoleIds) && \App\Models\RoleApproval::whereIn('approver_role_id', $userRoleIds)
            ->where('is_active', true)
            ->exists();

        // بناء استعلام المهام العادية - جلب كل المهام
        $regularTasksQuery = TaskUser::with(['task' => function($query) {
                $query->with(['project', 'createdBy']);
            }, 'user'])
            ->whereIn('status', ['new', 'in_progress', 'paused', 'completed']);

        // إذا لم يكن HR أو Admin ولا عنده صلاحيات اعتماد، قيد النتائج لمن أنشأ المهمة فقط
        if (!$isHrOrAdmin && !$hasApprovalPermissions) {
            $regularTasksQuery->whereHas('task', function ($query) use ($user) {
                $query->where('created_by', $user->id);
            });
        } elseif (!$isHrOrAdmin && $hasApprovalPermissions) {
            // المستخدم عنده صلاحيات اعتماد، اجلب المهام التي أنشأها أو يمكنه اعتمادها
            $regularTasksQuery->where(function($q) use ($user, $userRoleIds) {
                // المهام التي أنشأها
                $q->whereHas('task', function ($query) use ($user) {
                    $query->where('created_by', $user->id);
                })
                // أو المهام من مشاريع ومستخدمين يمكنه اعتمادهم
                ->orWhere(function($subQ) use ($userRoleIds) {
                    $subQ->whereHas('task', function ($taskQ) {
                        $taskQ->whereNotNull('project_id');
                    })
                    ->whereHas('user.roles', function($roleQ) use ($userRoleIds) {
                        $roleQ->whereIn('roles.id', function($innerQ) use ($userRoleIds) {
                            $innerQ->select('role_id')
                                ->from('role_approvals')
                                ->whereIn('approver_role_id', $userRoleIds)
                                ->where('is_active', true);
                        });
                    });
                });
            });
        }

        $regularTasks = $regularTasksQuery->orderBy('created_at', 'desc')->get();

        // بناء استعلام مهام التمبليت - جلب كل المهام
        $templateTasksQuery = TemplateTaskUser::with(['templateTask' => function($query) {
                $query->with(['template']);
            }, 'user', 'project', 'assignedBy'])
            ->whereIn('status', ['new', 'in_progress', 'paused', 'completed']);

        // إذا لم يكن HR أو Admin ولا عنده صلاحيات اعتماد، قيد النتائج لمن أضاف المهمة فقط
        if (!$isHrOrAdmin && !$hasApprovalPermissions) {
            $templateTasksQuery->where('assigned_by', $user->id);
        } elseif (!$isHrOrAdmin && $hasApprovalPermissions) {
            // المستخدم عنده صلاحيات اعتماد، اجلب المهام التي أضافها أو يمكنه اعتمادها
            $templateTasksQuery->where(function($q) use ($user, $userRoleIds) {
                // المهام التي أضافها
                $q->where('assigned_by', $user->id)
                // أو المهام من مشاريع ومستخدمين يمكنه اعتمادهم
                ->orWhere(function($subQ) use ($userRoleIds) {
                    $subQ->whereNotNull('project_id')
                    ->whereHas('user.roles', function($roleQ) use ($userRoleIds) {
                        $roleQ->whereIn('roles.id', function($innerQ) use ($userRoleIds) {
                            $innerQ->select('role_id')
                                ->from('role_approvals')
                                ->whereIn('approver_role_id', $userRoleIds)
                                ->where('is_active', true);
                        });
                    });
                });
            });
        }

        $templateTasks = $templateTasksQuery->orderBy('created_at', 'desc')->get();

        // دمج المهام وتجهيز البيانات
        $allTasks = collect();

        // إضافة المهام العادية
        foreach ($regularTasks as $taskUser) {
            $allTasks->push([
                'id' => $taskUser->id,
                'type' => 'regular',
                'task_name' => $taskUser->task->name,
                'user' => $taskUser->user,
                'points' => $taskUser->task->points ?? 0,
                'status' => $taskUser->status,
                'deadline' => $taskUser->task->due_date,
                'actual_time' => [
                    'hours' => $taskUser->actual_hours ?? 0,
                    'minutes' => $taskUser->actual_minutes ?? 0
                ],
                'completed_date' => $taskUser->completed_date,
                'is_approved' => $taskUser->is_approved,
                'approved_at' => $taskUser->approved_at,
                'awarded_points' => $taskUser->awarded_points,
                'approval_note' => $taskUser->approval_note,
                'created_by' => $taskUser->task->createdBy->name ?? 'غير محدد',
                'project' => $taskUser->task->project ?? null,
                'raw_data' => $taskUser
            ]);
        }

        // إضافة مهام القوالب
        foreach ($templateTasks as $templateTaskUser) {
            $allTasks->push([
                'id' => $templateTaskUser->id,
                'type' => 'template',
                'task_name' => $templateTaskUser->templateTask->name,
                'user' => $templateTaskUser->user,
                'points' => $templateTaskUser->templateTask->points ?? 0,
                'status' => $templateTaskUser->status,
                'deadline' => $templateTaskUser->deadline ?? $templateTaskUser->due_date,
                'actual_time' => [
                    'hours' => floor(($templateTaskUser->actual_minutes ?? 0) / 60),
                    'minutes' => ($templateTaskUser->actual_minutes ?? 0) % 60
                ],
                'completed_date' => $templateTaskUser->completed_at,
                'is_approved' => $templateTaskUser->is_approved,
                'approved_at' => $templateTaskUser->approved_at,
                'awarded_points' => $templateTaskUser->awarded_points,
                'approval_note' => $templateTaskUser->approval_note,
                'created_by' => $templateTaskUser->assignedBy->name ?? 'غير محدد',
                'project' => $templateTaskUser->project ?? null,
                'raw_data' => $templateTaskUser
            ]);
        }

        // ترتيب المهام حسب التاريخ
        $allTasks = $allTasks->sortByDesc(function($task) {
            return $task['completed_date'] ?? $task['raw_data']->created_at;
        });

        return [
            'all_tasks' => $allTasks,
            'regular_tasks' => $regularTasks,
            'template_tasks' => $templateTasks,
            'total_tasks' => $allTasks->count(),
            'pending_approval' => $allTasks->where('is_approved', false)->where('status', 'completed')->count(),
            'is_hr_or_admin' => $isHrOrAdmin
        ];
    }

    /**
     * الحصول على المهام المنتظرة للموافقة (الطريقة القديمة للتوافق)
     */
    public function getPendingApprovalTasks(User $teamLeader = null): array
    {
        $result = $this->getAllTasksForApproval($teamLeader);

        // فلترة المهام المكتملة وغير المعتمدة فقط للتوافق مع الكود الحالي
        $pendingRegularTasks = $result['regular_tasks']->where('status', 'completed')->where('is_approved', false);
        $pendingTemplateTasks = $result['template_tasks']->where('status', 'completed')->where('is_approved', false);

        return [
            'regular_tasks' => $pendingRegularTasks,
            'template_tasks' => $pendingTemplateTasks,
            'total_pending' => $pendingRegularTasks->count() + $pendingTemplateTasks->count(),
            'is_hr_or_admin' => $result['is_hr_or_admin']
        ];
    }

    /**
     * الحصول على المشاريع مع إحصائيات المهام
     */
    public function getProjectsWithTaskStats(User $teamLeader = null): array
    {
        $user = $teamLeader ?? Auth::user();

        // التحقق من كون المستخدم HR أو Admin
        $userRoles = $user->roles->pluck('name')->toArray();
        $allowedRoles = ['hr', 'project_manager', 'company_manager', 'operations_manager'];
        $isHrOrAdmin = !empty(array_intersect($allowedRoles, $userRoles));

        // فحص إذا كان المستخدم لديه صلاحيات اعتماد في RoleApproval
        $userRoleIds = $user->roles->pluck('id')->toArray();
        $hasApprovalPermissions = !empty($userRoleIds) && \App\Models\RoleApproval::whereIn('approver_role_id', $userRoleIds)
            ->where('is_active', true)
            ->exists();

        // بناء استعلام المشاريع
        $projectsQuery = \App\Models\Project::with(['tasks.users', 'templateTaskUsers']);

        // إذا لم يكن HR أو Admin ولا عنده صلاحيات اعتماد، قيد النتائج للمشاريع التي أنشأ مهام بها فقط
        if (!$isHrOrAdmin && !$hasApprovalPermissions) {
            $projectsQuery->where(function($query) use ($user) {
                $query->whereHas('tasks', function($q) use ($user) {
                    $q->where('created_by', $user->id);
                })->orWhereHas('templateTaskUsers', function($q) use ($user) {
                    $q->where('assigned_by', $user->id);
                });
            });
        } elseif (!$isHrOrAdmin && $hasApprovalPermissions) {
            // المستخدم عنده صلاحيات اعتماد، اجلب المشاريع التي أنشأ مهام بها أو يمكنه اعتماد مهامها
            $projectsQuery->where(function($query) use ($user, $userRoleIds) {
                $query->whereHas('tasks', function($q) use ($user) {
                    $q->where('created_by', $user->id);
                })
                ->orWhereHas('templateTaskUsers', function($q) use ($user) {
                    $q->where('assigned_by', $user->id);
                })
                // أو المشاريع التي فيها مهام لمستخدمين يمكنه اعتمادهم
                ->orWhereHas('serviceParticipants.user.roles', function($roleQ) use ($userRoleIds) {
                    $roleQ->whereIn('roles.id', function($innerQ) use ($userRoleIds) {
                        $innerQ->select('role_id')
                            ->from('role_approvals')
                            ->whereIn('approver_role_id', $userRoleIds)
                            ->where('is_active', true);
                    });
                });
            });
        }

        $projects = $projectsQuery->get();

        // تجهيز إحصائيات لكل مشروع
        $projectsWithStats = $projects->map(function($project) use ($user, $isHrOrAdmin) {
            // إحصائيات المهام العادية - استخدام TaskUser مباشرة
            $regularTasksQuery = TaskUser::whereHas('task', function($query) use ($project) {
                $query->where('project_id', $project->id);
            });

            if (!$isHrOrAdmin) {
                $regularTasksQuery->whereHas('task', function($query) use ($user) {
                    $query->where('created_by', $user->id);
                });
            }

            $regularTasks = $regularTasksQuery->get();

            $regularTasksStats = $regularTasks->map(function($taskUser) {
                return [
                    'total' => 1,
                    'completed' => $taskUser->status === 'completed' ? 1 : 0,
                    'pending_approval' => ($taskUser->status === 'completed' && !$taskUser->is_approved) ? 1 : 0,
                    'approved' => $taskUser->is_approved ? 1 : 0,
                ];
            });

            // إحصائيات مهام القوالب
            $templateTasksQuery = $project->templateTaskUsers();
            if (!$isHrOrAdmin) {
                $templateTasksQuery->where('assigned_by', $user->id);
            }
            $templateTasks = $templateTasksQuery->get();

            $templateTasksStats = $templateTasks->map(function($templateTaskUser) {
                return [
                    'total' => 1,
                    'completed' => $templateTaskUser->status === 'completed' ? 1 : 0,
                    'pending_approval' => ($templateTaskUser->status === 'completed' && !$templateTaskUser->is_approved) ? 1 : 0,
                    'approved' => $templateTaskUser->is_approved ? 1 : 0,
                ];
            });

            // دمج الإحصائيات
            $allStats = $regularTasksStats->concat($templateTasksStats);

            return [
                'id' => $project->id,
                'name' => $project->name,
                'description' => $project->description,
                'manager' => $project->manager,
                'stats' => [
                    'total_tasks' => $allStats->sum('total'),
                    'completed_tasks' => $allStats->sum('completed'),
                    'pending_approval' => $allStats->sum('pending_approval'),
                    'approved_tasks' => $allStats->sum('approved'),
                    'completion_percentage' => $allStats->sum('total') > 0
                        ? round(($allStats->sum('completed') / $allStats->sum('total')) * 100, 2)
                        : 0,
                    'approval_percentage' => $allStats->sum('completed') > 0
                        ? round(($allStats->sum('approved') / $allStats->sum('completed')) * 100, 2)
                        : 0
                ],
                'project_data' => $project
            ];
        })->filter(function($project) {
            return $project['stats']['total_tasks'] > 0; // إظهار المشاريع التي تحتوي على مهام فقط
        });

        return [
            'projects' => $projectsWithStats,
            'is_hr_or_admin' => $isHrOrAdmin
        ];
    }

    /**
     * الحصول على مهام مشروع محدد
     */
    public function getProjectTasks(int $projectId, User $teamLeader = null): array
    {
        $user = $teamLeader ?? Auth::user();

        // التحقق من كون المستخدم HR أو Admin
        $userRoles = $user->roles->pluck('name')->toArray();
        $allowedRoles = ['hr', 'project_manager', 'company_manager', 'operations_manager'];
        $isHrOrAdmin = !empty(array_intersect($allowedRoles, $userRoles));

        // جلب المشروع
        $project = \App\Models\Project::findOrFail($projectId);

        // بناء استعلام المهام العادية للمشروع
        $regularTasksQuery = TaskUser::with(['task' => function($query) {
                $query->with(['project', 'createdBy']);
            }, 'user'])
            ->whereHas('task', function($query) use ($projectId) {
                $query->where('project_id', $projectId);
            })
            ->whereIn('status', ['new', 'in_progress', 'paused', 'completed']);

        // إذا لم يكن HR أو Admin، قيد النتائج لمن أنشأ المهمة فقط
        if (!$isHrOrAdmin) {
            $regularTasksQuery->whereHas('task', function ($query) use ($user) {
                $query->where('created_by', $user->id);
            });
        }

        $regularTasks = $regularTasksQuery->orderBy('created_at', 'desc')->get();

        // بناء استعلام مهام التمبليت للمشروع
        $templateTasksQuery = TemplateTaskUser::with(['templateTask' => function($query) {
                $query->with(['template']);
            }, 'user', 'project', 'assignedBy'])
            ->where('project_id', $projectId)
            ->whereIn('status', ['new', 'in_progress', 'paused', 'completed']);

        // إذا لم يكن HR أو Admin، قيد النتائج لمن أضاف المهمة فقط
        if (!$isHrOrAdmin) {
            $templateTasksQuery->where('assigned_by', $user->id);
        }

        $templateTasks = $templateTasksQuery->orderBy('created_at', 'desc')->get();

        // دمج المهام وتجهيز البيانات (نفس منطق getAllTasksForApproval)
        $allTasks = collect();

        // إضافة المهام العادية
        foreach ($regularTasks as $taskUser) {
            $allTasks->push([
                'id' => $taskUser->id,
                'type' => 'regular',
                'task_name' => $taskUser->task->name,
                'user' => $taskUser->user,
                'points' => $taskUser->task->points ?? 0,
                'status' => $taskUser->status,
                'deadline' => $taskUser->task->due_date,
                'actual_time' => [
                    'hours' => $taskUser->actual_hours ?? 0,
                    'minutes' => $taskUser->actual_minutes ?? 0
                ],
                'completed_date' => $taskUser->completed_date,
                'is_approved' => $taskUser->is_approved,
                'approved_at' => $taskUser->approved_at,
                'awarded_points' => $taskUser->awarded_points,
                'approval_note' => $taskUser->approval_note,
                'created_by' => $taskUser->task->createdBy->name ?? 'غير محدد',
                'project' => $taskUser->task->project ?? null,
                'raw_data' => $taskUser
            ]);
        }

        // إضافة مهام القوالب
        foreach ($templateTasks as $templateTaskUser) {
            $allTasks->push([
                'id' => $templateTaskUser->id,
                'type' => 'template',
                'task_name' => $templateTaskUser->templateTask->name,
                'user' => $templateTaskUser->user,
                'points' => $templateTaskUser->templateTask->points ?? 0,
                'status' => $templateTaskUser->status,
                'deadline' => $templateTaskUser->deadline ?? $templateTaskUser->due_date,
                'actual_time' => [
                    'hours' => floor(($templateTaskUser->actual_minutes ?? 0) / 60),
                    'minutes' => ($templateTaskUser->actual_minutes ?? 0) % 60
                ],
                'completed_date' => $templateTaskUser->completed_at,
                'is_approved' => $templateTaskUser->is_approved,
                'approved_at' => $templateTaskUser->approved_at,
                'awarded_points' => $templateTaskUser->awarded_points,
                'approval_note' => $templateTaskUser->approval_note,
                'created_by' => $templateTaskUser->assignedBy->name ?? 'غير محدد',
                'project' => $templateTaskUser->project ?? null,
                'raw_data' => $templateTaskUser
            ]);
        }

        // ترتيب المهام حسب التاريخ
        $allTasks = $allTasks->sortByDesc(function($task) {
            return $task['completed_date'] ?? $task['raw_data']->created_at;
        });

        return [
            'project' => $project,
            'all_tasks' => $allTasks,
            'regular_tasks' => $regularTasks,
            'template_tasks' => $templateTasks,
            'total_tasks' => $allTasks->count(),
            'pending_approval' => $allTasks->where('is_approved', false)->where('status', 'completed')->count(),
            'is_hr_or_admin' => $isHrOrAdmin
        ];
    }

    /**
     * إضافة النقاط للمستخدم
     */
    private function addPointsToUser(User $user, int $points): void
    {
        if ($points <= 0) return;

        $season = Season::where('is_active', true)->first();
        if (!$season) return;

        UserSeasonPoint::updateOrCreate(
            [
                'user_id' => $user->id,
                'season_id' => $season->id,
            ],
            [
                'total_points' => DB::raw("total_points + {$points}"),
                'tasks_completed' => DB::raw("tasks_completed + 1"),
            ]
        );
    }

    /**
     * إزالة النقاط من المستخدم
     */
    private function removePointsFromUser(User $user, int $points): void
    {
        if ($points <= 0) return;

        $season = Season::where('is_active', true)->first();
        if (!$season) return;

        $userSeasonPoint = UserSeasonPoint::where('user_id', $user->id)
            ->where('season_id', $season->id)
            ->first();

        if ($userSeasonPoint) {
            $userSeasonPoint->update([
                'total_points' => max(0, $userSeasonPoint->total_points - $points),
                'tasks_completed' => max(0, $userSeasonPoint->tasks_completed - 1)
            ]);
        }
    }

        /**
     * التحقق من صلاحية المستخدم لاعتماد المهام
     */
    public function canApproveTask($taskUser, User $user = null): bool
    {
        $currentUser = $user ?? Auth::user();

        // التحقق من الأدوار الإدارية أولاً - الأدوار المسموح لها باعتماد أي مهمة
        $allowedRoles = ['hr', 'project_manager', 'company_manager', 'operations_manager'];
        $userRoles = $currentUser->roles->pluck('name')->toArray();

        if (!empty(array_intersect($allowedRoles, $userRoles))) {
            return true;
        }

        // للمستخدمين العاديين، التحقق حسب نوع المهمة
        if ($taskUser instanceof TaskUser) {
            // للمهام العادية: فقط من أنشأ المهمة يمكنه اعتمادها
            return $taskUser->task->created_by === $currentUser->id;
        } else {
            // لمهام القوالب: فقط من أضاف المهمة يمكنه اعتمادها
            return $taskUser->assigned_by === $currentUser->id;
        }
    }
}
