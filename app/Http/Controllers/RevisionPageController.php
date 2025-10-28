<?php

namespace App\Http\Controllers;

use App\Models\TaskRevision;
use App\Services\Tasks\RevisionFilterService;
use App\Services\ProjectDashboard\RevisionStatsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class RevisionPageController extends Controller
{
    protected $revisionFilterService;
    protected $revisionStatsService;

    public function __construct(
        RevisionFilterService $revisionFilterService,
        RevisionStatsService $revisionStatsService
    ) {
        $this->middleware('auth');
        $this->revisionFilterService = $revisionFilterService;
        $this->revisionStatsService = $revisionStatsService;
    }

    /**
     * عرض صفحة التعديلات
     */
    public function index()
    {
        // تسجيل نشاط دخول صفحة التعديلات
        if (Auth::check()) {
            activity()
                ->causedBy(Auth::user())
                ->withProperties([
                    'action_type' => 'view_revisions_page',
                    'page' => 'revisions_list',
                    'viewed_at' => now()->toDateTimeString(),
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip()
                ])
                ->log('دخل على صفحة التعديلات');
        }

        // جلب المشاريع للـ sidebar (مع الكود)
        $projects = \App\Models\Project::select('id', 'name', 'code')
            ->orderBy('code')
            ->orderBy('name')
            ->get();

        // إحصائيات التعديلات المنقولة
        $revisionTransferStats = $this->revisionStatsService->getRevisionTransferStats(Auth::id(), null);

        return view('revisions.page', compact('projects', 'revisionTransferStats'));
    }

    /**
     * الحصول على جميع التعديلات (للتبويب الأول)
     */
    public function getAllRevisions(Request $request)
    {
        try {
            $query = TaskRevision::with([
                'creator:id,name',
                'reviewer:id,name',
                'assignedUser:id,name',
                'responsibleUser:id,name',
                'executorUser:id,name',
                'project:id,name,code',
                'season:id,name',
                'taskUser',
                'taskUser.user:id,name',
                'templateTaskUser',
                'templateTaskUser.user:id,name'
            ]);

            // تطبيق الفلترة الهرمية
            $query = $this->revisionFilterService->applyHierarchicalRevisionFiltering($query);

            // فلترة حسب النوع
            if ($request->filled('revision_type')) {
                $query->where('revision_type', $request->revision_type);
            }

            // فلترة حسب المصدر
            if ($request->filled('revision_source')) {
                $query->where('revision_source', $request->revision_source);
            }

            // فلترة حسب الحالة
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // فلترة حسب المشروع
            if ($request->filled('project_id')) {
                $query->where('project_id', $request->project_id);
            }

            // فلترة حسب كود المشروع (مع مراعاة الفلترة الهرمية)
            if ($request->filled('project_code')) {
                $query->whereHas('project', function($q) use ($request) {
                    $q->where('code', 'like', '%' . $request->project_code . '%');
                });
            }

            // فلترة حسب الشهر
            if ($request->filled('month')) {
                $query->whereYear('revision_date', substr($request->month, 0, 4))
                      ->whereMonth('revision_date', substr($request->month, 5, 2));
            }

            // البحث في العنوان والوصف
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $revisions = $query->latest('revision_date')
                              ->paginate(15);

            return response()->json([
                'success' => true,
                'revisions' => $revisions
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching all revisions', [
                'error' => $e->getMessage(),
                'filters' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في تحميل التعديلات'
            ], 500);
        }
    }

    /**
     * الحصول على التعديلات المرتبطة بالمهام المسندة للمستخدم الحالي (للتبويب الثاني)
     */
    public function getMyRevisions(Request $request)
    {
        try {
            $userId = Auth::id();

            $query = TaskRevision::with([
                'creator:id,name',
                'reviewer:id,name',
                'project:id,name,code',
                'responsibleUser:id,name',
                'executorUser:id,name',
                'season:id,name',
                'taskUser',
                'taskUser.user:id,name',
                'templateTaskUser',
                'templateTaskUser.user:id,name'
            ]);

            // استخدام الفلترة الجديدة للتعديلات المسندة للمستخدم
            $user = Auth::user();
            $query = $this->revisionFilterService->getMyAssignedRevisions($query, $user);

            // فلترة حسب النوع
            if ($request->filled('revision_type')) {
                $query->where('revision_type', $request->revision_type);
            }

            // فلترة حسب المصدر
            if ($request->filled('revision_source')) {
                $query->where('revision_source', $request->revision_source);
            }

            // فلترة حسب الحالة
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // فلترة حسب المشروع
            if ($request->filled('project_id')) {
                $query->where('project_id', $request->project_id);
            }

            // فلترة حسب كود المشروع (مع مراعاة الفلترة الهرمية)
            if ($request->filled('project_code')) {
                $query->whereHas('project', function($q) use ($request) {
                    $q->where('code', 'like', '%' . $request->project_code . '%');
                });
            }

            // فلترة حسب الشهر
            if ($request->filled('month')) {
                $query->whereYear('revision_date', substr($request->month, 0, 4))
                      ->whereMonth('revision_date', substr($request->month, 5, 2));
            }

            // البحث في العنوان والوصف
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $revisions = $query->latest('revision_date')
                              ->paginate(15);

            return response()->json([
                'success' => true,
                'revisions' => $revisions
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching user revisions', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'filters' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في تحميل تعديلاتك'
            ], 500);
        }
    }

    /**
     * الحصول على التعديلات التي أضافها المستخدم الحالي (للتبويب الثالث)
     */
    public function getMyCreatedRevisions(Request $request)
    {
        try {
            $userId = Auth::id();

            $query = TaskRevision::with([
                'creator:id,name',
                'reviewer:id,name',
                'assignedUser:id,name',
                'responsibleUser:id,name',
                'executorUser:id,name',
                'project:id,name,code',
                'season:id,name',
                'taskUser',
                'taskUser.user:id,name',
                'templateTaskUser',
                'templateTaskUser.user:id,name'
            ]);

            // استخدام الفلترة الجديدة للتعديلات التي أنشأها المستخدم
            $user = Auth::user();
            $query = $this->revisionFilterService->getMyCreatedRevisions($query, $user);

            // فلترة حسب النوع
            if ($request->filled('revision_type')) {
                $query->where('revision_type', $request->revision_type);
            }

            // فلترة حسب المصدر
            if ($request->filled('revision_source')) {
                $query->where('revision_source', $request->revision_source);
            }

            // فلترة حسب الحالة
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // فلترة حسب المشروع
            if ($request->filled('project_id')) {
                $query->where('project_id', $request->project_id);
            }

            // فلترة حسب كود المشروع (مع مراعاة الفلترة الهرمية)
            if ($request->filled('project_code')) {
                $query->whereHas('project', function($q) use ($request) {
                    $q->where('code', 'like', '%' . $request->project_code . '%');
                });
            }

            // فلترة حسب الشهر
            if ($request->filled('month')) {
                $query->whereYear('revision_date', substr($request->month, 0, 4))
                      ->whereMonth('revision_date', substr($request->month, 5, 2));
            }

            // البحث في العنوان والوصف
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $revisions = $query->latest('revision_date')
                              ->paginate(15);

            return response()->json([
                'success' => true,
                'revisions' => $revisions
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching user created revisions', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'filters' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في تحميل التعديلات التي أضفتها'
            ], 500);
        }
    }

    /**
     * الحصول على إحصائيات التعديلات
     */
    public function getStats()
    {
        try {
            $userId = Auth::id();

            // إحصائيات عامة مع تطبيق الفلترة الهرمية
            $allRevisionsQuery = TaskRevision::query();
            $allRevisionsQuery = $this->revisionFilterService->applyHierarchicalRevisionFiltering($allRevisionsQuery);

            $totalRevisions = $allRevisionsQuery->count();
            $pendingRevisions = (clone $allRevisionsQuery)->where('status', 'pending')->count();
            $approvedRevisions = (clone $allRevisionsQuery)->where('status', 'approved')->count();
            $rejectedRevisions = (clone $allRevisionsQuery)->where('status', 'rejected')->count();

            // إحصائيات التعديلات المسندة للمستخدم (مباشرة أو عبر TaskUser أو TemplateTaskUser)
            $myAssignedRevisions = TaskRevision::where(function($q) use ($userId) {
                $q->where('assigned_to', $userId)
                  ->orWhereHas('taskUser', function($taskUserQuery) use ($userId) {
                      $taskUserQuery->where('user_id', $userId);
                  })
                  ->orWhereHas('templateTaskUser', function($templateTaskUserQuery) use ($userId) {
                      $templateTaskUserQuery->where('user_id', $userId);
                  });
            })->count();

            $myAssignedPending = TaskRevision::where(function($q) use ($userId) {
                $q->where('assigned_to', $userId)
                  ->orWhereHas('taskUser', function($taskUserQuery) use ($userId) {
                      $taskUserQuery->where('user_id', $userId);
                  })
                  ->orWhereHas('templateTaskUser', function($templateTaskUserQuery) use ($userId) {
                      $templateTaskUserQuery->where('user_id', $userId);
                  });
            })->where('status', 'pending')->count();

            $myAssignedApproved = TaskRevision::where(function($q) use ($userId) {
                $q->where('assigned_to', $userId)
                  ->orWhereHas('taskUser', function($taskUserQuery) use ($userId) {
                      $taskUserQuery->where('user_id', $userId);
                  })
                  ->orWhereHas('templateTaskUser', function($templateTaskUserQuery) use ($userId) {
                      $templateTaskUserQuery->where('user_id', $userId);
                  });
            })->where('status', 'approved')->count();

            $myAssignedRejected = TaskRevision::where(function($q) use ($userId) {
                $q->where('assigned_to', $userId)
                  ->orWhereHas('taskUser', function($taskUserQuery) use ($userId) {
                      $taskUserQuery->where('user_id', $userId);
                  })
                  ->orWhereHas('templateTaskUser', function($templateTaskUserQuery) use ($userId) {
                      $templateTaskUserQuery->where('user_id', $userId);
                  });
            })->where('status', 'rejected')->count();

            // إحصائيات التعديلات التي أنشأها المستخدم
            $myCreatedRevisions = TaskRevision::where('created_by', $userId)->count();
            $myCreatedPending = TaskRevision::where('created_by', $userId)
                                          ->where('status', 'pending')->count();
            $myCreatedApproved = TaskRevision::where('created_by', $userId)
                                           ->where('status', 'approved')->count();
            $myCreatedRejected = TaskRevision::where('created_by', $userId)
                                           ->where('status', 'rejected')->count();

            // إحصائيات حسب المصدر مع تطبيق الفلترة الهرمية
            $internalRevisions = (clone $allRevisionsQuery)->where('revision_source', 'internal')->count();
            $externalRevisions = (clone $allRevisionsQuery)->where('revision_source', 'external')->count();

            return response()->json([
                'success' => true,
                'stats' => [
                    'general' => [
                        'total' => $totalRevisions,
                        'pending' => $pendingRevisions,
                        'approved' => $approvedRevisions,
                        'rejected' => $rejectedRevisions,
                        'internal' => $internalRevisions,
                        'external' => $externalRevisions
                    ],
                    'my_assigned_revisions' => [
                        'total' => $myAssignedRevisions,
                        'pending' => $myAssignedPending,
                        'approved' => $myAssignedApproved,
                        'rejected' => $myAssignedRejected
                    ],
                    'my_created_revisions' => [
                        'total' => $myCreatedRevisions,
                        'pending' => $myCreatedPending,
                        'approved' => $myCreatedApproved,
                        'rejected' => $myCreatedRejected
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching revision stats', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في تحميل الإحصائيات'
            ], 500);
        }
    }

    /**
     * الحصول على تفاصيل تعديل واحد
     */
    public function getRevisionDetails($revisionId)
    {
        try {
            // تطبيق الفلترة الهرمية للتأكد من أن المستخدم يحق له رؤية هذا التعديل
            $query = TaskRevision::with([
                'creator:id,name',
                'reviewer:id,name',
                'assignedUser:id,name',
                'responsibleUser:id,name',
                'executorUser:id,name',
                'project:id,name,code',
                'season:id,name',
                'taskUser',
                'taskUser.user:id,name',
                'templateTaskUser',
                'templateTaskUser.user:id,name'
            ]);

            $query = $this->revisionFilterService->applyHierarchicalRevisionFiltering($query);
            $revision = $query->find($revisionId);

            if (!$revision) {
                return response()->json([
                    'success' => false,
                    'message' => 'التعديل غير موجود أو ليس لديك صلاحية لرؤيته'
                ], 404);
            }

            // تسجيل نشاط عرض تفاصيل التعديل
            activity()
                ->performedOn($revision)
                ->causedBy(Auth::user())
                ->withProperties([
                    'action_type' => 'view_revision_details',
                    'revision_id' => $revision->id,
                    'revision_title' => $revision->title,
                    'viewed_at' => now()->toDateTimeString()
                ])
                ->log('عرض تفاصيل التعديل');

            return response()->json([
                'success' => true,
                'revision' => $revision
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching revision details', [
                'revision_id' => $revisionId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في تحميل تفاصيل التعديل'
            ], 500);
        }
    }

    /**
     * عرض صفحة إحصائيات نقل التعديلات
     */
    public function transferStatistics()
    {
        // تسجيل نشاط دخول صفحة إحصائيات النقل
        if (Auth::check()) {
            activity()
                ->causedBy(Auth::user())
                ->withProperties([
                    'action_type' => 'view_transfer_statistics_page',
                    'page' => 'revision_transfer_statistics',
                    'viewed_at' => now()->toDateTimeString(),
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip()
                ])
                ->log('دخل على صفحة إحصائيات نقل التعديلات');
        }

        // جلب المشاريع للفلتر
        $projects = \App\Models\Project::select('id', 'name')
            ->orderBy('name')
            ->get();

        // جلب المستخدمين للفلتر
        $users = \App\Models\User::select('id', 'name')
            ->orderBy('name')
            ->get();

        return view('revisions.transfer-statistics', compact('projects', 'users'));
    }

    /**
     * الحصول على سجلات نقل التعديلات
     */
    public function getTransferRecords(Request $request)
    {
        try {
            $query = \App\Models\RevisionAssignment::with([
                'revision:id,title,revision_type,project_id',
                'revision.project:id,name,code',
                'fromUser:id,name',
                'toUser:id,name',
                'assignedBy:id,name'
            ]);

            // فلترة حسب الشهر
            if ($request->filled('month')) {
                $month = $request->month;
                $query->whereYear('created_at', substr($month, 0, 4))
                      ->whereMonth('created_at', substr($month, 5, 2));
            }

            // فلترة حسب نوع التعيين (executor أو reviewer)
            if ($request->filled('assignment_type')) {
                $query->where('assignment_type', $request->assignment_type);
            }

            // فلترة حسب المشروع
            if ($request->filled('project_id')) {
                $query->whereHas('revision', function($q) use ($request) {
                    $q->where('project_id', $request->project_id);
                });
            }

            // فلترة حسب المستخدم (من أو إلى)
            if ($request->filled('user_id')) {
                $userId = $request->user_id;
                $query->where(function($q) use ($userId) {
                    $q->where('from_user_id', $userId)
                      ->orWhere('to_user_id', $userId);
                });
            }

            // فلترة حسب نطاق التاريخ
            if ($request->filled('from_date')) {
                $query->whereDate('created_at', '>=', $request->from_date);
            }

            if ($request->filled('to_date')) {
                $query->whereDate('created_at', '<=', $request->to_date);
            }

            $records = $query->latest('created_at')
                            ->paginate(20);

            return response()->json([
                'success' => true,
                'records' => $records
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching transfer records', [
                'error' => $e->getMessage(),
                'filters' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في تحميل سجلات النقل'
            ], 500);
        }
    }

    /**
     * الحصول على إحصائيات نقل التعديلات
     */
    public function getTransferStats(Request $request)
    {
        try {
            $query = \App\Models\RevisionAssignment::query();

            // تطبيق نفس الفلاتر
            if ($request->filled('month')) {
                $month = $request->month;
                $query->whereYear('created_at', substr($month, 0, 4))
                      ->whereMonth('created_at', substr($month, 5, 2));
            }

            if ($request->filled('from_date')) {
                $query->whereDate('created_at', '>=', $request->from_date);
            }

            if ($request->filled('to_date')) {
                $query->whereDate('created_at', '<=', $request->to_date);
            }

            if ($request->filled('project_id')) {
                $query->whereHas('revision', function($q) use ($request) {
                    $q->where('project_id', $request->project_id);
                });
            }

            if ($request->filled('user_id')) {
                $userId = $request->user_id;
                $query->where(function($q) use ($userId) {
                    $q->where('from_user_id', $userId)
                      ->orWhere('to_user_id', $userId);
                });
            }

            // إحصائيات عامة
            $totalTransfers = $query->count();
            $executorTransfers = (clone $query)->where('assignment_type', 'executor')->count();
            $reviewerTransfers = (clone $query)->where('assignment_type', 'reviewer')->count();

            // أكثر المستخدمين نقلاً منه
            $topFromUsers = \App\Models\RevisionAssignment::select('from_user_id', DB::raw('count(*) as transfer_count'))
                ->whereNotNull('from_user_id')
                ->when($request->filled('month'), function($q) use ($request) {
                    $month = $request->month;
                    $q->whereYear('created_at', substr($month, 0, 4))
                      ->whereMonth('created_at', substr($month, 5, 2));
                })
                ->when($request->filled('from_date'), function($q) use ($request) {
                    $q->whereDate('created_at', '>=', $request->from_date);
                })
                ->when($request->filled('to_date'), function($q) use ($request) {
                    $q->whereDate('created_at', '<=', $request->to_date);
                })
                ->groupBy('from_user_id')
                ->orderByDesc('transfer_count')
                ->limit(5)
                ->with('fromUser:id,name')
                ->get();

            // أكثر المستخدمين نقلاً إليه
            $topToUsers = \App\Models\RevisionAssignment::select('to_user_id', DB::raw('count(*) as transfer_count'))
                ->when($request->filled('month'), function($q) use ($request) {
                    $month = $request->month;
                    $q->whereYear('created_at', substr($month, 0, 4))
                      ->whereMonth('created_at', substr($month, 5, 2));
                })
                ->when($request->filled('from_date'), function($q) use ($request) {
                    $q->whereDate('created_at', '>=', $request->from_date);
                })
                ->when($request->filled('to_date'), function($q) use ($request) {
                    $q->whereDate('created_at', '<=', $request->to_date);
                })
                ->groupBy('to_user_id')
                ->orderByDesc('transfer_count')
                ->limit(5)
                ->with('toUser:id,name')
                ->get();

            return response()->json([
                'success' => true,
                'stats' => [
                    'total_transfers' => $totalTransfers,
                    'executor_transfers' => $executorTransfers,
                    'reviewer_transfers' => $reviewerTransfers,
                    'top_from_users' => $topFromUsers,
                    'top_to_users' => $topToUsers
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching transfer stats', [
                'error' => $e->getMessage(),
                'filters' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في تحميل الإحصائيات'
            ], 500);
        }
    }

    /**
     * جلب قائمة المشاريع للفلترة
     */
    public function getProjectsList(Request $request)
    {
        try {
            // جلب جميع المشاريع التي لها كود
            $projects = \App\Models\Project::whereNotNull('code')
                             ->where('code', '!=', '')
                             ->select('id', 'name', 'code')
                             ->orderBy('code')
                             ->get()
                             ->map(function($project) {
                                 return [
                                     'id' => $project->id,
                                     'code' => $project->code,
                                     'name' => $project->name,
                                     'display' => $project->code . ' - ' . $project->name
                                 ];
                             });

            return response()->json([
                'success' => true,
                'projects' => $projects
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching projects list', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جلب قائمة المشاريع'
            ], 500);
        }
    }
}
