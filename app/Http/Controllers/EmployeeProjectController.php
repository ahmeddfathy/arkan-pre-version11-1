<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProjectServiceUser;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmployeeProjectController extends Controller
{
    /**
     * عرض صفحة المشاريع للموظف
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // التحقق من المستوى الهرمي للمستخدم
        $hierarchyLevel = \App\Models\RoleHierarchy::getUserMaxHierarchyLevel($user);

        // إذا كان Team Leader (hierarchy_level = 3) فقط، نعرض له صفحة team-leader
        // المستخدم بمستوى 2 يبقى في الصفحة العادية
        if ($hierarchyLevel == 3) {
            return $this->teamLeaderIndex($request);
        }

        // بناء الاستعلام الأساسي للموظف العادي
        $query = ProjectServiceUser::query()
            ->with(['project', 'service', 'team', 'user'])
            ->forUser($user->id);

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
            $query->whereHas('project', function($q) use ($request) {
                $q->where('code', 'like', '%' . $request->search . '%')
                  ->orWhere('name', 'like', '%' . $request->search . '%');
            });
        }

        // الترتيب
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

        // إحصائيات للموظف
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

        // قائمة المشاريع للفلتر
        $allProjects = Project::whereHas('projectServiceUsers', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })->get(['id', 'name', 'code']);

        return view('employee.projects.index', compact('projects', 'stats', 'allProjects'));
    }

    /**
     * تحديث حالة المشروع
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string'
        ]);

        $projectServiceUser = ProjectServiceUser::findOrFail($id);

        // التحقق من صلاحية المستخدم
        if ($projectServiceUser->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بتحديث هذا المشروع'
            ], 403);
        }

        $oldStatus = $projectServiceUser->status;
        $projectServiceUser->updateStatus($request->status);

        // التحقق من المستوى الهرمي للمستخدم وتحديث حالة الخدمة إذا كان المستخدم بمستوى 2
        $user = Auth::user();
        $hierarchyLevel = \App\Models\RoleHierarchy::getUserMaxHierarchyLevel($user);
        $serviceStatusUpdated = false;

        if ($hierarchyLevel == 2) {
            // تحديث حالة الخدمة في المشروع (project_service pivot table)
            $project = Project::find($projectServiceUser->project_id);
            if ($project) {
                $project->services()->updateExistingPivot($projectServiceUser->service_id, [
                    'service_status' => $request->status,
                    'updated_at' => now()
                ]);
                $serviceStatusUpdated = true;

                Log::info('Service Status Updated by Hierarchy Level 2 User', [
                    'project_id' => $projectServiceUser->project_id,
                    'service_id' => $projectServiceUser->service_id,
                    'user_id' => $user->id,
                    'hierarchy_level' => $hierarchyLevel,
                    'new_status' => $request->status,
                    'service_status_updated' => true
                ]);
            }
        }

        // 🔔 إرسال إشعار سلاك للمشاركين في الخدمات التي تعتمد على هذه الخدمة
        try {
            $project = $projectServiceUser->project;
            $service = $projectServiceUser->service;

            // جلب الخدمات التي تعتمد على الخدمة الحالية
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

                        Log::info('Slack notification queued for dependent service participant', [
                            'project_id' => $project->id,
                            'service_id' => $service->id,
                            'participant_user_id' => $participant->user_id,
                            'participant_service_id' => $participant->service_id,
                            'updated_by_user_id' => $user->id
                        ]);
                    }
                }

                Log::info('Dependent services status update notifications queued', [
                    'project_id' => $projectServiceUser->project_id,
                    'service_id' => $projectServiceUser->service_id,
                    'user_id' => $user->id,
                    'old_status' => $oldStatus,
                    'new_status' => $request->status,
                    'dependent_services_count' => $dependentServices->count(),
                    'notified_users' => $dependentParticipants->count()
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

        $projectServiceUser->deliver();

        return response()->json([
            'success' => true,
            'message' => 'تم تسليم المشروع بنجاح',
            'delivered_at' => $projectServiceUser->delivered_at->format('Y/m/d h:i A')
        ]);
    }

    /**
     * إلغاء تسليم المشروع
     */
    public function undeliverProject(Request $request, $id)
    {
        $projectServiceUser = ProjectServiceUser::findOrFail($id);

        // التحقق من صلاحية المستخدم
        if ($projectServiceUser->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بإلغاء تسليم هذا المشروع'
            ], 403);
        }

        // التحقق من إمكانية إلغاء التسليم
        if (!$projectServiceUser->canBeUndelivered()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن إلغاء التسليم لأنه تم اعتماد المشروع'
            ], 400);
        }

        $projectServiceUser->undeliver();

        return response()->json([
            'success' => true,
            'message' => 'تم إلغاء تسليم المشروع بنجاح'
        ]);
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
            ->map(function($item) {
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
            ->where(function($q) use ($user) {
                // نجيب المشاريع والخدمات اللي Team Leader شغال عليها
                $myProjects = ProjectServiceUser::where('user_id', $user->id)
                    ->select('project_id', 'service_id')
                    ->get();

                foreach ($myProjects as $myProject) {
                    $q->orWhere(function($subQ) use ($myProject) {
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
            $query->whereHas('project', function($q) use ($request) {
                $q->where('code', 'like', '%' . $request->search . '%')
                  ->orWhere('name', 'like', '%' . $request->search . '%');
            });
        }

        // جلب البيانات
        $projectServices = $query->get();

        // تجميع البيانات حسب المشروع والخدمة
        $groupedProjects = $projectServices->groupBy(function($item) {
            return $item->project_id . '-' . $item->service_id;
        })->map(function($serviceUsers, $key) use ($user) {
            $first = $serviceUsers->first();

            // حساب الإحصائيات للخدمة
            $stats = [
                'total' => $serviceUsers->count(),
                'completed' => $serviceUsers->where('status', ProjectServiceUser::STATUS_FINAL_DELIVERY)->count(),
                'in_progress' => $serviceUsers->where('status', ProjectServiceUser::STATUS_IN_PROGRESS)->count(),
                'draft_delivery' => $serviceUsers->where('status', ProjectServiceUser::STATUS_DRAFT_DELIVERY)->count(),
                'overdue' => $serviceUsers->filter(function($item) {
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
            'overdue_services' => $groupedProjects->filter(function($service) {
                return $service['stats']['overdue'] > 0;
            })->count(),
            'in_progress_services' => $groupedProjects->where('service_status', ProjectServiceUser::STATUS_IN_PROGRESS)->count(),
            'total_members' => $projectServices->unique('user_id')->count(),
            'avg_completion' => $groupedProjects->count() > 0
                ? round($groupedProjects->avg('completion_percentage'))
                : 0,
        ];

        // قائمة المشاريع للفلتر (المشاريع التي يعمل عليها Team Leader)
        $allProjects = Project::whereHas('projectServiceUsers', function($q) use ($user) {
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

        $myRecord->status = $request->status;
        $myRecord->save();

        // تحديث حالة الخدمة في المشروع (project_service pivot table) فقط للمستوى الهرمي 2
        $serviceStatusUpdated = false;
        if ($hierarchyLevel == 2) {
            $project = Project::find($projectId);
            if ($project) {
                $project->services()->updateExistingPivot($serviceId, [
                    'service_status' => $request->status,
                    'updated_at' => now()
                ]);
                $serviceStatusUpdated = true;
            }
        }

        // Log للتأكد
        Log::info('User Status Update', [
            'project_id' => $projectId,
            'service_id' => $serviceId,
            'user_id' => $user->id,
            'hierarchy_level' => $hierarchyLevel,
            'new_status' => $request->status,
            'service_status_updated' => $serviceStatusUpdated
        ]);

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
