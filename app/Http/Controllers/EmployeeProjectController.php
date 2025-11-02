<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProjectServiceUser;
use App\Models\Project;
use App\Services\ProjectManagement\ProjectDeliveryService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmployeeProjectController extends Controller
{
    protected $deliveryService;

    public function __construct(ProjectDeliveryService $deliveryService)
    {
        $this->deliveryService = $deliveryService;
    }

    /**
     * عرض صفحة المشاريع للموظف
     */
    public function index(Request $request)
    {
        $user = Auth::user();


        $hierarchyLevel = \App\Models\RoleHierarchy::getUserMaxHierarchyLevel($user);


        if ($hierarchyLevel == 3) {
            return $this->teamLeaderIndex($request);
        }


        $query = ProjectServiceUser::query()
            ->with(['project', 'service', 'team', 'user'])
            ->forUser($user->id);

        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }


        if ($request->filled('deadline_filter')) {
            switch ($request->deadline_filter) {
                case 'today':
                    $query->deadlineToday();
                    break;
                case 'this_week':
                    $query->deadlineThisWeek();
                    break;
                case 'this_month':
                    $query->deadlineThisMonth();
                    break;
                case 'overdue':
                    $query->overdue();
                    break;
                case 'upcoming':
                    $query->upcoming();
                    break;
            }
        }

        if ($request->filled('project_id')) {
            $query->forProject($request->project_id);
        }

        if ($request->filled('search')) {
            $query->whereHas('project', function ($q) use ($request) {
                $q->where('code', 'like', '%' . $request->search . '%')
                    ->orWhere('name', 'like', '%' . $request->search . '%');
            });
        }

        $sortBy = $request->get('sort_by', 'deadline');
        $sortOrder = $request->get('sort_order', 'asc');

        if ($sortBy === 'deadline') {
            $query->orderBy('deadline', $sortOrder);
        } elseif ($sortBy === 'status') {
            $query->orderBy('status', $sortOrder);
        } elseif ($sortBy === 'project_name') {
            $query->join('projects', 'project_service_user.project_id', '=', 'projects.id')
                ->orderBy('projects.name', $sortOrder)
                ->select('project_service_user.*');
        }

        $stats = [
            'total' => ProjectServiceUser::forUser($user->id)->count(),
            'in_progress' => ProjectServiceUser::forUser($user->id)->byStatus(ProjectServiceUser::STATUS_IN_PROGRESS)->count(),
            'draft_delivery' => ProjectServiceUser::forUser($user->id)->byStatus(ProjectServiceUser::STATUS_DRAFT_DELIVERY)->count(),
            'final_delivery' => ProjectServiceUser::forUser($user->id)->byStatus(ProjectServiceUser::STATUS_FINAL_DELIVERY)->count(),
            'overdue' => ProjectServiceUser::forUser($user->id)->overdue()->count(),
            'this_week' => ProjectServiceUser::forUser($user->id)->deadlineThisWeek()->count(),
            'this_month' => ProjectServiceUser::forUser($user->id)->deadlineThisMonth()->count(),
        ];

        $projects = $query->paginate(15)->withQueryString();


        $allProjects = Project::whereHas('projectServiceUsers', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->get(['id', 'name', 'code']);

        return view('employee.projects.index', compact('projects', 'stats', 'allProjects'));
    }


    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string'
        ]);

        $projectServiceUser = ProjectServiceUser::findOrFail($id);

        if ($projectServiceUser->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بتحديث هذا المشروع'
            ], 403);
        }

        $oldStatus = $projectServiceUser->status;
        $projectServiceUser->updateStatus($request->status);

        $user = Auth::user();
        $hierarchyLevel = \App\Models\RoleHierarchy::getUserMaxHierarchyLevel($user);
        $serviceStatusUpdated = false;

        // لوج عام لتحديث حالة الموظف
        Log::info('Employee Status Updated', [
            'project_service_user_id' => $projectServiceUser->id,
            'project_id' => $projectServiceUser->project_id,
            'service_id' => $projectServiceUser->service_id,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'hierarchy_level' => $hierarchyLevel,
            'old_status' => $oldStatus,
            'new_status' => $request->status,
            'timestamp' => now()->format('Y-m-d H:i:s')
        ]);

        if ($hierarchyLevel == 2) {
            $project = Project::find($projectServiceUser->project_id);
            if ($project) {
                $project->services()->updateExistingPivot($projectServiceUser->service_id, [
                    'service_status' => $request->status,
                    'updated_at' => now()
                ]);
                $serviceStatusUpdated = true;

                // لوج خاص بالمستوى الهرمي 2 - تحديث حالة الخدمة بالكامل
                Log::info('🔥 HIERARCHY LEVEL 2: Service Status Updated', [
                    'action' => 'FULL_SERVICE_STATUS_UPDATE',
                    'project_id' => $projectServiceUser->project_id,
                    'project_name' => $project->name,
                    'service_id' => $projectServiceUser->service_id,
                    'service_name' => $projectServiceUser->service->name ?? 'N/A',
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'user_email' => $user->email,
                    'hierarchy_level' => $hierarchyLevel,
                    'old_status' => $oldStatus,
                    'new_status' => $request->status,
                    'service_status_updated' => true,
                    'pivot_table_updated' => true,
                    'timestamp' => now()->format('Y-m-d H:i:s'),
                    'impact' => 'يؤثر على حالة الخدمة بالكامل في المشروع'
                ]);
            }
        }

        try {
            $project = $projectServiceUser->project;
            $service = $projectServiceUser->service;

            $dependentServices = DB::table('service_dependencies')
                ->where('depends_on_service_id', $projectServiceUser->service_id)
                ->pluck('service_id');

            Log::info('Checking dependent services', [
                'project_id' => $projectServiceUser->project_id,
                'service_id' => $projectServiceUser->service_id,
                'dependent_services' => $dependentServices->toArray(),
                'count' => $dependentServices->count()
            ]);

            if ($dependentServices->isNotEmpty()) {
                // جلب المشاركين في الخدمات المعتمدة
                $dependentParticipants = ProjectServiceUser::where('project_id', $projectServiceUser->project_id)
                    ->whereIn('service_id', $dependentServices)
                    ->where('user_id', '!=', $user->id)
                    ->with(['user', 'service'])
                    ->get();

                // مصفوفة لتخزين معلومات المستلمين للإشعارات
                $notifiedUsers = [];
                $usersWithoutSlack = [];

                foreach ($dependentParticipants as $participant) {
                    if ($participant->user && $participant->user->slack_user_id) {
                        // بناء رسالة سلاك بالصيغة الصحيحة
                        $projectUrl = route('projects.show', $project->id);

                        $message = [
                            'text' => "📊 تحديث في خدمة يعتمد عليها عملك",
                            'blocks' => [
                                [
                                    'type' => 'header',
                                    'text' => [
                                        'type' => 'plain_text',
                                        'text' => '📊 تحديث في خدمة يعتمد عليها عملك'
                                    ]
                                ],
                                [
                                    'type' => 'section',
                                    'fields' => [
                                        [
                                            'type' => 'mrkdwn',
                                            'text' => "*المشروع:*\n{$project->name}"
                                        ],
                                        [
                                            'type' => 'mrkdwn',
                                            'text' => "*الخدمة المحدثة:*\n{$service->name}"
                                        ],
                                        [
                                            'type' => 'mrkdwn',
                                            'text' => "*خدمتك:*\n{$participant->service->name}"
                                        ],
                                        [
                                            'type' => 'mrkdwn',
                                            'text' => "*الموظف:*\n{$user->name}"
                                        ],
                                        [
                                            'type' => 'mrkdwn',
                                            'text' => "*الحالة الجديدة:*\n{$request->status}"
                                        ],
                                        [
                                            'type' => 'mrkdwn',
                                            'text' => "*الحالة السابقة:*\n{$oldStatus}"
                                        ]
                                    ]
                                ],
                                [
                                    'type' => 'section',
                                    'text' => [
                                        'type' => 'mrkdwn',
                                        'text' => "💡 *هذا التحديث قد يؤثر على عملك في الخدمة المعتمدة*"
                                    ]
                                ],
                                [
                                    'type' => 'actions',
                                    'elements' => [
                                        [
                                            'type' => 'button',
                                            'text' => [
                                                'type' => 'plain_text',
                                                'text' => '🔗 عرض المشروع'
                                            ],
                                            'url' => $projectUrl,
                                            'style' => 'primary'
                                        ]
                                    ]
                                ],
                                [
                                    'type' => 'context',
                                    'elements' => [
                                        [
                                            'type' => 'mrkdwn',
                                            'text' => '📅 ' . now()->format('d/m/Y - H:i')
                                        ]
                                    ]
                                ]
                            ]
                        ];

                        // إرسال إشعار سلاك
                        \App\Jobs\SendSlackNotification::dispatch(
                            $participant->user,
                            $message,
                            'تحديث حالة خدمة معتمد عليها'
                        );

                        // إضافة المستخدم للقائمة
                        $notifiedUsers[] = [
                            'user_id' => $participant->user_id,
                            'user_name' => $participant->user->name,
                            'user_email' => $participant->user->email,
                            'service_id' => $participant->service_id,
                            'service_name' => $participant->service->name ?? 'N/A',
                            'slack_user_id' => $participant->user->slack_user_id
                        ];

                        Log::info('📧 Slack notification queued for participant', [
                            'recipient_user_id' => $participant->user_id,
                            'recipient_name' => $participant->user->name,
                            'recipient_email' => $participant->user->email,
                            'recipient_service' => $participant->service->name ?? 'N/A',
                            'project_id' => $project->id,
                            'project_name' => $project->name,
                            'updated_service' => $service->name,
                            'updated_by' => $user->name,
                            'status_change' => "{$oldStatus} → {$request->status}"
                        ]);
                    } else {
                        // المستخدم ليس لديه Slack ID - إرسال Database Notification
                        if ($participant->user) {
                            // بيانات الإشعار
                            $projectDisplay = $project->code ?? $project->name; // كود المشروع أو اسمه كبديل
                            $notificationData = [
                                'title' => '📊 تحديث في خدمة يعتمد عليها عملك',
                                'message' => "تم تحديث حالة خدمة {$service->name} في مشروع {$projectDisplay}",
                                'project_id' => $project->id,
                                'project_name' => $project->name,
                                'project_code' => $project->code ?? null,
                                'service_id' => $service->id,
                                'service_name' => $service->name,
                                'your_service_id' => $participant->service_id,
                                'your_service_name' => $participant->service->name ?? 'N/A',
                                'updated_by_user_id' => $user->id,
                                'updated_by_name' => $user->name,
                                'old_status' => $oldStatus,
                                'new_status' => $request->status,
                                'status_change' => "{$oldStatus} → {$request->status}",
                                'url' => route('projects.show', $project->id),
                                'type' => 'dependent_service_status_updated',
                                'timestamp' => now()->format('Y-m-d H:i:s'),
                                'icon' => '📊',
                                'priority' => 'high'
                            ];

                            // إرسال Database Notification عبر Job
                            \App\Jobs\SendDatabaseNotification::dispatch(
                                $participant->user,
                                $notificationData,
                                'تحديث حالة خدمة معتمد عليها'
                            );

                            $usersWithoutSlack[] = [
                                'user_id' => $participant->user_id,
                                'user_name' => $participant->user->name,
                                'user_email' => $participant->user->email,
                                'service_name' => $participant->service->name ?? 'N/A',
                                'notification_type' => 'database'
                            ];

                            Log::info('📬 Database notification queued for user without Slack', [
                                'recipient_user_id' => $participant->user_id,
                                'recipient_name' => $participant->user->name,
                                'recipient_email' => $participant->user->email,
                                'recipient_service' => $participant->service->name ?? 'N/A',
                                'project_id' => $project->id,
                                'project_name' => $project->name,
                                'updated_service' => $service->name,
                                'updated_by' => $user->name,
                                'status_change' => "{$oldStatus} → {$request->status}",
                                'notification_type' => 'database'
                            ]);
                        }
                    }
                }

                // لوج نهائي شامل بجميع المستلمين
                Log::info('🔔 Slack Notifications Summary', [
                    'action' => 'DEPENDENT_SERVICES_NOTIFICATION',
                    'project_id' => $projectServiceUser->project_id,
                    'project_name' => $project->name,
                    'service_id' => $projectServiceUser->service_id,
                    'service_name' => $service->name,
                    'updated_by_user_id' => $user->id,
                    'updated_by_name' => $user->name,
                    'status_change' => "{$oldStatus} → {$request->status}",
                    'dependent_services_count' => $dependentServices->count(),
                    'total_participants' => $dependentParticipants->count(),
                    'notified_users_count' => count($notifiedUsers),
                    'users_without_slack_count' => count($usersWithoutSlack),
                    'notified_users' => $notifiedUsers,
                    'users_without_slack' => $usersWithoutSlack,
                    'timestamp' => now()->format('Y-m-d H:i:s')
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to queue dependent services status update notification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'project_service_user_id' => $id
            ]);
            // لا نوقف العملية حتى لو فشل الإشعار
        }

        // رسالة مختلفة حسب المستوى الهرمي
        $message = $serviceStatusUpdated
            ? 'تم تحديث حالتك وحالة الخدمة بنجاح'
            : 'تم تحديث حالة المشروع بنجاح';

        return response()->json([
            'success' => true,
            'message' => $message,
            'status' => $projectServiceUser->status,
            'status_color' => $projectServiceUser->getStatusColor(),
            'service_status_updated' => $serviceStatusUpdated
        ]);
    }

    /**
     * الحصول على تفاصيل المشروع
     */
    public function show($id)
    {
        $projectServiceUser = ProjectServiceUser::with([
            'project',
            'service',
            'team',
            'user',
            'administrativeApprover',
            'technicalApprover',
            'tasks',
            'errors'
        ])->findOrFail($id);

        // التحقق من صلاحية المستخدم
        if ($projectServiceUser->user_id !== Auth::id()) {
            abort(403, 'غير مصرح لك بعرض هذا المشروع');
        }

        return view('employee.projects.show', compact('projectServiceUser'));
    }

    /**
     * إحصائيات سريعة
     */
    public function quickStats()
    {
        $user = Auth::user();

        $stats = [
            'today' => ProjectServiceUser::forUser($user->id)->deadlineToday()->count(),
            'this_week' => ProjectServiceUser::forUser($user->id)->deadlineThisWeek()->count(),
            'overdue' => ProjectServiceUser::forUser($user->id)->overdue()->count(),
            'in_progress' => ProjectServiceUser::forUser($user->id)->byStatus(ProjectServiceUser::STATUS_IN_PROGRESS)->count(),
        ];

        return response()->json($stats);
    }

    /**
     * تسليم المشروع
     */
    public function deliverProject(Request $request, $id)
    {
        $projectServiceUser = ProjectServiceUser::findOrFail($id);

        // التحقق من صلاحية المستخدم
        if ($projectServiceUser->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بتسليم هذا المشروع'
            ], 403);
        }

        // ✅ التحقق من الحالة قبل التسليم
        $validDeliveryStatuses = [
            ProjectServiceUser::STATUS_DRAFT_DELIVERY,  // 'تسليم مسودة'
            ProjectServiceUser::STATUS_FINAL_DELIVERY   // 'تم تسليم نهائي'
        ];

        if (!in_array($projectServiceUser->status, $validDeliveryStatuses)) {
            return response()->json([
                'success' => false,
                'message' => 'يجب تغيير حالة المشروع إلى "تسليم مسودة" أو "تم تسليم نهائي" قبل التسليم. الحالة الحالية: ' . $projectServiceUser->status
            ], 400);
        }

        // ✅ استخدام ProjectDeliveryService لإرسال الإشعارات للمعتمدين
        $result = $this->deliveryService->deliverParticipantProject($id);

        if ($result['success']) {
            $projectServiceUser->refresh(); // تحديث البيانات بعد التسليم

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'delivered_at' => $projectServiceUser->delivered_at ? $projectServiceUser->delivered_at->format('Y/m/d h:i A') : null,
                'participant' => $result['participant'] ?? null
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message']
        ], $result['status_code'] ?? 500);
    }

    /**
     * إلغاء تسليم المشروع
     */
    public function undeliverProject(Request $request, $id)
    {
        // ✅ استخدام ProjectDeliveryService لإرسال الإشعارات عند إلغاء التسليم
        $result = $this->deliveryService->undeliverParticipantProject($id);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'participant' => $result['participant'] ?? null
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message']
        ], $result['status_code'] ?? 500);
    }

    /**
     * عرض صفحة المشاريع لـ Team Leader
     * يعرض المشاريع مجموعة حسب الخدمة مع الموظفين في كل خدمة
     * - التيم ليدر (hierarchy_level = 3) يستطيع المشاهدة فقط
     * - المسؤول عن الخدمة (hierarchy_level = 2) يستطيع تغيير حالة الخدمة
     */
    public function teamLeaderIndex(Request $request)
    {
        $user = Auth::user();

        // الخطوة 1: جلب المشاريع والخدمات التي يعمل عليها Team Leader
        $myProjectServices = ProjectServiceUser::where('user_id', $user->id)
            ->get(['project_id', 'service_id'])
            ->map(function ($item) {
                return $item->project_id . '-' . $item->service_id;
            })
            ->unique()
            ->toArray();

        // إذا لم يكن لديه مشاريع، نرجع الصفحة فارغة
        if (empty($myProjectServices)) {
            $groupedProjects = collect([]);
            $stats = [
                'total_services' => 0,
                'completed_services' => 0,
                'overdue_services' => 0,
                'in_progress_services' => 0,
                'total_members' => 0,
                'avg_completion' => 0,
            ];
            $allProjects = collect([]);
            return view('employee.projects.team-leader', compact('groupedProjects', 'stats', 'allProjects'));
        }

        // الخطوة 2: جلب كل الموظفين في نفس المشاريع والخدمات
        $query = ProjectServiceUser::query()
            ->with([
                'project',
                'service',
                'team',
                'user',
                'administrativeApprover',
                'technicalApprover'
            ])
            ->where(function ($q) use ($user) {
                // نجيب المشاريع والخدمات اللي Team Leader شغال عليها
                $myProjects = ProjectServiceUser::where('user_id', $user->id)
                    ->select('project_id', 'service_id')
                    ->get();

                foreach ($myProjects as $myProject) {
                    $q->orWhere(function ($subQ) use ($myProject) {
                        $subQ->where('project_id', $myProject->project_id)
                            ->where('service_id', $myProject->service_id);
                    });
                }
            });

        // الفلترة حسب الحالة
        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        // الفلترة حسب الديدلاين
        if ($request->filled('deadline_filter')) {
            switch ($request->deadline_filter) {
                case 'today':
                    $query->deadlineToday();
                    break;
                case 'this_week':
                    $query->deadlineThisWeek();
                    break;
                case 'this_month':
                    $query->deadlineThisMonth();
                    break;
                case 'overdue':
                    $query->overdue();
                    break;
                case 'upcoming':
                    $query->upcoming();
                    break;
            }
        }

        // الفلترة حسب المشروع
        if ($request->filled('project_id')) {
            $query->forProject($request->project_id);
        }

        // البحث عن المشروع بالكود أو الاسم
        if ($request->filled('search')) {
            $query->whereHas('project', function ($q) use ($request) {
                $q->where('code', 'like', '%' . $request->search . '%')
                    ->orWhere('name', 'like', '%' . $request->search . '%');
            });
        }

        // جلب البيانات
        $projectServices = $query->get();

        // تجميع البيانات حسب المشروع والخدمة
        $groupedProjects = $projectServices->groupBy(function ($item) {
            return $item->project_id . '-' . $item->service_id;
        })->map(function ($serviceUsers, $key) use ($user) {
            $first = $serviceUsers->first();

            // حساب الإحصائيات للخدمة
            $stats = [
                'total' => $serviceUsers->count(),
                'completed' => $serviceUsers->where('status', ProjectServiceUser::STATUS_FINAL_DELIVERY)->count(),
                'in_progress' => $serviceUsers->where('status', ProjectServiceUser::STATUS_IN_PROGRESS)->count(),
                'draft_delivery' => $serviceUsers->where('status', ProjectServiceUser::STATUS_DRAFT_DELIVERY)->count(),
                'overdue' => $serviceUsers->filter(function ($item) {
                    return $item->isOverdue() && $item->status != ProjectServiceUser::STATUS_FINAL_DELIVERY;
                })->count(),
            ];

            // حساب نسبة الإنجاز
            $completionPercentage = $stats['total'] > 0
                ? round(($stats['completed'] / $stats['total']) * 100)
                : 0;

            // حالة Team Leader نفسه في هذه الخدمة
            $myRecord = $serviceUsers->firstWhere('user_id', $user->id);
            $myStatus = $myRecord ? $myRecord->status : ProjectServiceUser::STATUS_IN_PROGRESS;

            return [
                'project' => $first->project,
                'service' => $first->service,
                'team' => $first->team,
                'members' => $serviceUsers,
                'stats' => $stats,
                'completion_percentage' => $completionPercentage,
                'service_status' => $myStatus, // حالة Team Leader نفسه
                'earliest_deadline' => $serviceUsers->min('deadline'),
            ];
        })->sortBy('earliest_deadline')->values();

        // إحصائيات عامة للـ Team Leader
        $stats = [
            'total_services' => $groupedProjects->count(),
            'completed_services' => $groupedProjects->where('service_status', ProjectServiceUser::STATUS_FINAL_DELIVERY)->count(),
            'overdue_services' => $groupedProjects->filter(function ($service) {
                return $service['stats']['overdue'] > 0;
            })->count(),
            'in_progress_services' => $groupedProjects->where('service_status', ProjectServiceUser::STATUS_IN_PROGRESS)->count(),
            'total_members' => $projectServices->unique('user_id')->count(),
            'avg_completion' => $groupedProjects->count() > 0
                ? round($groupedProjects->avg('completion_percentage'))
                : 0,
        ];

        // قائمة المشاريع للفلتر (المشاريع التي يعمل عليها Team Leader)
        $allProjects = Project::whereHas('projectServiceUsers', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->get(['id', 'name', 'code']);

        return view('employee.projects.team-leader', compact('groupedProjects', 'stats', 'allProjects'));
    }

    /**
     * تحديث حالة الخدمة بالكامل (للمسؤول عن الخدمة الذي له hierarchy_level = 2)
     */
    public function updateServiceStatus(Request $request, $projectId, $serviceId)
    {
        $request->validate([
            'status' => 'required|string'
        ]);

        $user = Auth::user();

        // التحقق من المستوى الهرمي للمستخدم
        $hierarchyLevel = \App\Models\RoleHierarchy::getUserMaxHierarchyLevel($user);

        // التحقق من أن المستخدم يعمل على هذا المشروع والخدمة
        $isWorking = ProjectServiceUser::where('user_id', $user->id)
            ->where('project_id', $projectId)
            ->where('service_id', $serviceId)
            ->exists();

        if (!$isWorking) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بتحديث هذه الخدمة'
            ], 403);
        }

        // التحقق من صحة الحالة
        $validStatuses = array_keys(ProjectServiceUser::getAvailableStatuses());
        if (!in_array($request->status, $validStatuses)) {
            return response()->json([
                'success' => false,
                'message' => 'الحالة المحددة غير صحيحة'
            ], 400);
        }

        // تحديث حالة المستخدم نفسه في هذه الخدمة
        $myRecord = ProjectServiceUser::where('project_id', $projectId)
            ->where('service_id', $serviceId)
            ->where('user_id', $user->id)
            ->first();

        if (!$myRecord) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على سجل الخدمة'
            ], 404);
        }

        $oldStatus = $myRecord->status;
        $myRecord->status = $request->status;
        $myRecord->save();

        // تحديث حالة الخدمة في المشروع (project_service pivot table) فقط للمستوى الهرمي 2
        $serviceStatusUpdated = false;

        // لوج عام لتحديث حالة الموظف
        Log::info('Employee Status Updated via updateServiceStatus', [
            'project_service_user_id' => $myRecord->id,
            'project_id' => $projectId,
            'service_id' => $serviceId,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'hierarchy_level' => $hierarchyLevel,
            'old_status' => $oldStatus,
            'new_status' => $request->status,
            'timestamp' => now()->format('Y-m-d H:i:s')
        ]);

        if ($hierarchyLevel == 2) {
            $project = Project::find($projectId);
            if ($project) {
                $service = $project->services()->find($serviceId);

                $project->services()->updateExistingPivot($serviceId, [
                    'service_status' => $request->status,
                    'updated_at' => now()
                ]);
                $serviceStatusUpdated = true;

                // لوج خاص بالمستوى الهرمي 2 - تحديث حالة الخدمة بالكامل
                Log::info('🔥 HIERARCHY LEVEL 2: Service Status Updated via updateServiceStatus', [
                    'action' => 'FULL_SERVICE_STATUS_UPDATE_FROM_TEAM_LEADER_PAGE',
                    'method' => 'updateServiceStatus',
                    'project_id' => $projectId,
                    'project_name' => $project->name,
                    'project_code' => $project->code,
                    'service_id' => $serviceId,
                    'service_name' => $service->name ?? 'N/A',
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'user_email' => $user->email,
                    'hierarchy_level' => $hierarchyLevel,
                    'old_status' => $oldStatus,
                    'new_status' => $request->status,
                    'service_status_updated' => true,
                    'pivot_table_updated' => true,
                    'timestamp' => now()->format('Y-m-d H:i:s'),
                    'impact' => 'يؤثر على حالة الخدمة بالكامل في المشروع (تم التحديث من صفحة قائد الفريق)'
                ]);
            }
        } else {
            // لوج للمستويات الأخرى (مثل Team Leader مستوى 3)
            Log::info('Team Leader Personal Status Updated', [
                'action' => 'PERSONAL_STATUS_UPDATE_ONLY',
                'method' => 'updateServiceStatus',
                'project_id' => $projectId,
                'service_id' => $serviceId,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'hierarchy_level' => $hierarchyLevel,
                'old_status' => $oldStatus,
                'new_status' => $request->status,
                'service_status_updated' => false,
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'note' => 'تم تحديث الحالة الشخصية فقط - لا يؤثر على حالة الخدمة الكلية'
            ]);
        }

        // رسالة مختلفة حسب المستوى الهرمي
        $message = $serviceStatusUpdated
            ? 'تم تحديث حالتك وحالة الخدمة بنجاح'
            : 'تم تحديث حالتك بنجاح';

        return response()->json([
            'success' => true,
            'message' => $message,
            'updated_count' => 1,
            'service_status_updated' => $serviceStatusUpdated
        ]);
    }
}
