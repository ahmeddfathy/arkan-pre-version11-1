<?php

namespace App\Services\ProjectDashboard;

use App\Models\TaskRevision;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Services\ProjectDashboard\DateFilterService;

class RevisionStatsService
{
    protected $dateFilterService;

    public function __construct(DateFilterService $dateFilterService)
    {
        $this->dateFilterService = $dateFilterService;
    }
    /**
     * الحصول على إحصائيات التعديلات العامة
     */
    public function getGeneralRevisionStats($isAdmin = false, $userId = null, $dateFilters = null)
    {
        $query = TaskRevision::query();

        // إذا لم يكن المستخدم مدير، أظهر تعديلاته فقط أو المرتبطة به
        if (!$isAdmin && $userId) {
            if (is_array($userId)) {
                // للأقسام - مجموعة من المستخدمين
                $query->where(function($q) use ($userId) {
                    $q->whereIn('created_by', $userId)
                      ->orWhereIn('assigned_to', $userId)
                      ->orWhereIn('reviewed_by', $userId)
                      ->orWhereIn('executor_user_id', $userId)
                      ->orWhereIn('responsible_user_id', $userId)
                      // البحث في مصفوفة المراجعين JSON
                      ->orWhere(function($subQ) use ($userId) {
                          foreach ($userId as $uid) {
                              $subQ->orWhereJsonContains('reviewers', ['reviewer_id' => $uid]);
                          }
                      });
                });
            } else {
                // لمستخدم واحد
                $query->where(function($q) use ($userId) {
                    $q->where('created_by', $userId)
                      ->orWhere('assigned_to', $userId)
                      ->orWhere('reviewed_by', $userId)
                      ->orWhere('executor_user_id', $userId)
                      ->orWhere('responsible_user_id', $userId)
                      // البحث في مصفوفة المراجعين JSON
                      ->orWhereJsonContains('reviewers', ['reviewer_id' => $userId]);
                });
            }
        }

        // تطبيق فلترة التاريخ
        if ($dateFilters && $dateFilters['has_filter']) {
            $this->dateFilterService->applyDateFilter(
                $query,
                'revision_date',
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }

        $totalRevisions = $query->count();
        $pendingRevisions = (clone $query)->where('status', 'pending')->count();
        $approvedRevisions = (clone $query)->where('status', 'approved')->count();
        $rejectedRevisions = (clone $query)->where('status', 'rejected')->count();

        // إحصائيات حسب النوع
        $taskRevisions = (clone $query)->where('revision_type', 'task')->count();
        $projectRevisions = (clone $query)->where('revision_type', 'project')->count();
        $generalRevisions = (clone $query)->where('revision_type', 'general')->count();

        // إحصائيات حسب المصدر
        $internalRevisions = (clone $query)->where('revision_source', 'internal')->count();
        $externalRevisions = (clone $query)->where('revision_source', 'external')->count();

        // إحصائيات حديثة (آخر 7 أيام)
        $recentRevisions = (clone $query)->where('revision_date', '>=', Carbon::now()->subDays(7))->count();

        // إحصائيات هذا الشهر
        $thisMonthRevisions = (clone $query)->whereMonth('revision_date', Carbon::now()->month)
                                           ->whereYear('revision_date', Carbon::now()->year)
                                           ->count();

        // ✅ إحصائيات حسب الدور (مسؤول / منفذ / مراجع)
        $asResponsibleCount = 0;
        $asExecutorCount = 0;
        $asReviewerCount = 0;

        if (!$isAdmin && $userId) {
            $userIds = is_array($userId) ? $userId : [$userId];

            // عدد التعديلات كمسؤول (مرتكب الخطأ)
            $responsibleQuery = TaskRevision::whereIn('responsible_user_id', $userIds);
            if ($dateFilters && $dateFilters['has_filter']) {
                $this->dateFilterService->applyDateFilter($responsibleQuery, 'revision_date', $dateFilters['from_date'], $dateFilters['to_date']);
            }
            $asResponsibleCount = $responsibleQuery->count();

            // عدد التعديلات كمنفذ
            $executorQuery = TaskRevision::whereIn('executor_user_id', $userIds);
            if ($dateFilters && $dateFilters['has_filter']) {
                $this->dateFilterService->applyDateFilter($executorQuery, 'revision_date', $dateFilters['from_date'], $dateFilters['to_date']);
            }
            $asExecutorCount = $executorQuery->count();

            // عدد التعديلات كمراجع
            $reviewerQuery = TaskRevision::where(function($q) use ($userIds) {
                foreach ($userIds as $uid) {
                    $q->orWhereJsonContains('reviewers', ['reviewer_id' => $uid]);
                }
            });
            if ($dateFilters && $dateFilters['has_filter']) {
                $this->dateFilterService->applyDateFilter($reviewerQuery, 'revision_date', $dateFilters['from_date'], $dateFilters['to_date']);
            }
            $asReviewerCount = $reviewerQuery->count();
        }

        return [
            'total' => $totalRevisions,
            'pending' => $pendingRevisions,
            'approved' => $approvedRevisions,
            'rejected' => $rejectedRevisions,
            'task_type' => $taskRevisions,
            'project_type' => $projectRevisions,
            'general_type' => $generalRevisions,
            'internal' => $internalRevisions,
            'external' => $externalRevisions,
            'recent' => $recentRevisions,
            'this_month' => $thisMonthRevisions,
            'approval_rate' => $totalRevisions > 0 ? round(($approvedRevisions / $totalRevisions) * 100, 1) : 0,
            'rejection_rate' => $totalRevisions > 0 ? round(($rejectedRevisions / $totalRevisions) * 100, 1) : 0,
            // ✅ إحصائيات الأدوار
            'as_responsible' => $asResponsibleCount,
            'as_executor' => $asExecutorCount,
            'as_reviewer' => $asReviewerCount,
        ];
    }

    /**
     * الحصول على إحصائيات التعديلات حسب الحالة
     */
    public function getRevisionsByStatus($isAdmin = false, $userId = null, $dateFilters = null)
    {
        $query = TaskRevision::query();

        if (!$isAdmin && $userId) {
            if (is_array($userId)) {
                $query->where(function($q) use ($userId) {
                    $q->whereIn('created_by', $userId)
                      ->orWhereIn('assigned_to', $userId)
                      ->orWhereIn('reviewed_by', $userId)
                      ->orWhereIn('executor_user_id', $userId)
                      ->orWhereIn('responsible_user_id', $userId)
                      ->orWhere(function($subQ) use ($userId) {
                          foreach ($userId as $uid) {
                              $subQ->orWhereJsonContains('reviewers', ['reviewer_id' => $uid]);
                          }
                      });
                });
            } else {
                $query->where(function($q) use ($userId) {
                    $q->where('created_by', $userId)
                      ->orWhere('assigned_to', $userId)
                      ->orWhere('reviewed_by', $userId)
                      ->orWhere('executor_user_id', $userId)
                      ->orWhere('responsible_user_id', $userId)
                      ->orWhereJsonContains('reviewers', ['reviewer_id' => $userId]);
                });
            }
        }

        return $query->select('status', DB::raw('count(*) as count'))
                    ->groupBy('status')
                    ->pluck('count', 'status')
                    ->toArray();
    }

    /**
     * الحصول على إحصائيات التعديلات حسب النوع
     */
    public function getRevisionsByType($isAdmin = false, $userId = null, $dateFilters = null)
    {
        $query = TaskRevision::query();

        $this->applyUserFilter($query, $isAdmin, $userId);

        return $query->select('revision_type', DB::raw('count(*) as count'))
                    ->groupBy('revision_type')
                    ->pluck('count', 'revision_type')
                    ->toArray();
    }

    /**
     * الحصول على أحدث التعديلات
     */
    public function getLatestRevisions($isAdmin = false, $userId = null, $dateFilters = null, $limit = 5)
    {
        $query = TaskRevision::with(['creator', 'project', 'assignedUser']);

        $this->applyUserFilter($query, $isAdmin, $userId);

        // تطبيق فلترة التاريخ
        if ($dateFilters && $dateFilters['has_filter']) {
            $this->dateFilterService->applyDateFilter(
                $query,
                'revision_date',
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }

        return $query->latest('revision_date')->take($limit)->get();
    }

    /**
     * الحصول على التعديلات المعلقة الأكثر أهمية
     */
    public function getPendingRevisions($isAdmin = false, $userId = null, $dateFilters = null, $limit = 10)
    {
        $query = TaskRevision::with(['creator', 'project', 'assignedUser'])
                            ->where('status', 'pending');

        if (!$isAdmin && $userId) {
            if (is_array($userId)) {
                $query->where(function($q) use ($userId) {
                    $q->whereIn('created_by', $userId)
                      ->orWhereIn('assigned_to', $userId)
                      ->orWhereIn('reviewed_by', $userId);
                });
            } else {
                $query->where(function($q) use ($userId) {
                    $q->where('created_by', $userId)
                      ->orWhere('assigned_to', $userId)
                      ->orWhere('reviewed_by', $userId);
                });
            }
        }

        return $query->orderBy('revision_date')
                    ->take($limit)
                    ->get();
    }

    /**
     * إحصائيات التعديلات الشهرية (آخر 6 أشهر)
     */
    public function getMonthlyRevisionStats($isAdmin = false, $userId = null, $months = 6)
    {
        $stats = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();

            $query = TaskRevision::whereBetween('revision_date', [$monthStart, $monthEnd]);

            if (!$isAdmin && $userId) {
                $query->where(function($q) use ($userId) {
                    $q->where('created_by', $userId)
                      ->orWhere('assigned_to', $userId)
                      ->orWhere('reviewed_by', $userId);
                });
            }

            $total = $query->count();
            $approved = (clone $query)->where('status', 'approved')->count();
            $rejected = (clone $query)->where('status', 'rejected')->count();
            $pending = (clone $query)->where('status', 'pending')->count();

            $stats[] = [
                'month' => $month->format('M Y'),
                'month_ar' => $month->locale('ar')->format('M Y'),
                'total' => $total,
                'approved' => $approved,
                'rejected' => $rejected,
                'pending' => $pending,
            ];
        }

        return $stats;
    }

    /**
     * أفضل المراجعين (الذين يراجعون التعديلات)
     */
    public function getTopReviewers($limit = 5)
    {
        return TaskRevision::select('reviewed_by', DB::raw('count(*) as reviews_count'))
                          ->with('reviewer:id,name')
                          ->whereNotNull('reviewed_by')
                          ->groupBy('reviewed_by')
                          ->orderBy('reviews_count', 'desc')
                          ->take($limit)
                          ->get()
                          ->map(function($item) {
                              return [
                                  'reviewer' => $item->reviewer,
                                  'reviews_count' => $item->reviews_count
                              ];
                          });
    }

    /**
     * أكثر المستخدمين إنشاءً للتعديلات
     */
    public function getTopRevisionCreators($limit = 5)
    {
        return TaskRevision::select('created_by', DB::raw('count(*) as revisions_count'))
                          ->with('creator:id,name')
                          ->groupBy('created_by')
                          ->orderBy('revisions_count', 'desc')
                          ->take($limit)
                          ->get()
                          ->map(function($item) {
                              return [
                                  'creator' => $item->creator,
                                  'revisions_count' => $item->revisions_count
                              ];
                          });
    }

    /**
     * إحصائيات التعديلات حسب المشروع
     */
    public function getRevisionsByProject($limit = 10)
    {
        return TaskRevision::select('project_id', DB::raw('count(*) as revisions_count'))
                          ->with('project:id,name')
                          ->whereNotNull('project_id')
                          ->groupBy('project_id')
                          ->orderBy('revisions_count', 'desc')
                          ->take($limit)
                          ->get()
                          ->map(function($item) {
                              return [
                                  'project' => $item->project,
                                  'revisions_count' => $item->revisions_count
                              ];
                          });
    }

    /**
     * متوسط وقت المراجعة (بالأيام)
     */
    public function getAverageReviewTime($dateFilters = null)
    {
        $reviewedRevisions = TaskRevision::whereNotNull('reviewed_at')
                                       ->whereNotNull('revision_date')
                                       ->get();

        if ($reviewedRevisions->isEmpty()) {
            return 0;
        }

        $totalDays = 0;
        $count = 0;

        foreach ($reviewedRevisions as $revision) {
            $revisionDate = Carbon::parse($revision->revision_date);
            $reviewDate = Carbon::parse($revision->reviewed_at);

            if ($reviewDate->greaterThan($revisionDate)) {
                $totalDays += $revisionDate->diffInDays($reviewDate);
                $count++;
            }
        }

        return $count > 0 ? round($totalDays / $count, 1) : 0;
    }

    /**
     * إحصائيات الملفات المرفقة
     */
    public function getAttachmentStats($isAdmin = false, $userId = null, $dateFilters = null)
    {
        $query = TaskRevision::query();

        if (!$isAdmin && $userId) {
            if (is_array($userId)) {
                $query->where(function($q) use ($userId) {
                    $q->whereIn('created_by', $userId)
                      ->orWhereIn('assigned_to', $userId)
                      ->orWhereIn('reviewed_by', $userId);
                });
            } else {
                $query->where(function($q) use ($userId) {
                    $q->where('created_by', $userId)
                      ->orWhere('assigned_to', $userId)
                      ->orWhere('reviewed_by', $userId);
                });
            }
        }

        $totalRevisions = $query->count();
        $withAttachments = (clone $query)->whereNotNull('attachment_path')->count();
        $totalSize = (clone $query)->whereNotNull('attachment_size')->sum('attachment_size');

        return [
            'total_revisions' => $totalRevisions,
            'with_attachments' => $withAttachments,
            'attachment_rate' => $totalRevisions > 0 ? round(($withAttachments / $totalRevisions) * 100, 1) : 0,
            'total_size_mb' => round($totalSize / (1024 * 1024), 2),
            'average_size_mb' => $withAttachments > 0 ? round(($totalSize / $withAttachments) / (1024 * 1024), 2) : 0
        ];
    }

    /**
     * التعديلات الأكثر إلحاحاً (معلقة منذ أكثر من 3 أيام)
     */
    public function getUrgentRevisions($isAdmin = false, $userId = null, $dateFilters = null)
    {
        $query = TaskRevision::with(['creator', 'project', 'assignedUser'])
                            ->where('status', 'pending')
                            ->where('revision_date', '<=', Carbon::now()->subDays(3));

        if (!$isAdmin && $userId) {
            if (is_array($userId)) {
                $query->where(function($q) use ($userId) {
                    $q->whereIn('created_by', $userId)
                      ->orWhereIn('assigned_to', $userId)
                      ->orWhereIn('reviewed_by', $userId);
                });
            } else {
                $query->where(function($q) use ($userId) {
                    $q->where('created_by', $userId)
                      ->orWhere('assigned_to', $userId)
                      ->orWhere('reviewed_by', $userId);
                });
            }
        }

        return $query->orderBy('revision_date')->get();
    }

    /**
     * الحصول على إحصائيات التعديلات لمشروع معين (تشمل التعديلات المرتبطة بمهام المشروع)
     */
    public function getProjectRevisionStats($projectId, $isAdmin = false, $userId = null)
    {
        $query = $this->buildProjectRevisionsQuery($projectId, $isAdmin, $userId);

        $totalRevisions = $query->count();
        $pendingRevisions = (clone $query)->where('status', 'pending')->count();
        $approvedRevisions = (clone $query)->where('status', 'approved')->count();
        $rejectedRevisions = (clone $query)->where('status', 'rejected')->count();

        // إحصائيات حسب المصدر
        $internalRevisions = (clone $query)->where('revision_source', 'internal')->count();
        $externalRevisions = (clone $query)->where('revision_source', 'external')->count();

        // حساب الإحصائيات الإضافية
        $recentRevisions = (clone $query)->where('created_at', '>=', now()->subDays(7))->count();
        $thisMonthRevisions = (clone $query)->whereMonth('created_at', now()->month)->count();

        $approvalRate = $totalRevisions > 0 ? round(($approvedRevisions / $totalRevisions) * 100) : 0;
        $rejectionRate = $totalRevisions > 0 ? round(($rejectedRevisions / $totalRevisions) * 100) : 0;

        return [
            'total' => $totalRevisions,
            'pending' => $pendingRevisions,
            'approved' => $approvedRevisions,
            'rejected' => $rejectedRevisions,
            'internal' => $internalRevisions,
            'external' => $externalRevisions,
            'recent' => $recentRevisions,
            'this_month' => $thisMonthRevisions,
            'approval_rate' => $approvalRate,
            'rejection_rate' => $rejectionRate,
        ];
    }

    /**
     * الحصول على آخر التعديلات لمشروع معين
     */
    public function getProjectLatestRevisions($projectId, $isAdmin = false, $userId = null, $limit = 10)
    {
        $query = $this->buildProjectRevisionsQuery($projectId, $isAdmin, $userId);

        return $query->with(['creator', 'task', 'taskUser'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * الحصول على التعديلات المعلقة لمشروع معين
     */
    public function getProjectPendingRevisions($projectId, $isAdmin = false, $userId = null, $limit = 10)
    {
        $query = $this->buildProjectRevisionsQuery($projectId, $isAdmin, $userId);

        return $query->with(['creator', 'task', 'taskUser'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * الحصول على التعديلات الملحة لمشروع معين
     */
    public function getProjectUrgentRevisions($projectId, $isAdmin = false, $userId = null, $limit = 10)
    {
        $query = $this->buildProjectRevisionsQuery($projectId, $isAdmin, $userId);

        return $query->with(['creator', 'task', 'taskUser'])
            ->where('status', 'pending')
            ->where('created_at', '<', now()->subDays(3))
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * بناء استعلام التعديلات للمشروع (مساعد)
     */
    private function buildProjectRevisionsQuery($projectId, $isAdmin = false, $userId = null)
    {
        $query = TaskRevision::where(function($q) use ($projectId) {
            // التعديلات المرتبطة مباشرة بالمشروع
            $q->where('project_id', $projectId)
              // التعديلات المرتبطة بمهام تنتمي للمشروع
              ->orWhereHas('task', function($taskQ) use ($projectId) {
                  $taskQ->where('project_id', $projectId);
              })
              // التعديلات المرتبطة بمستخدمي مهام تنتمي للمشروع
              ->orWhereHas('taskUser', function($taskUserQ) use ($projectId) {
                  $taskUserQ->whereHas('task', function($subQ) use ($projectId) {
                      $subQ->where('project_id', $projectId);
                  });
              });
        });

        // إذا لم يكن مدير، أظهر التعديلات المرتبطة به فقط
        if (!$isAdmin && $userId) {
            if (is_array($userId)) {
                $query->where(function($q) use ($userId) {
                    $q->whereIn('created_by', $userId)
                      ->orWhereIn('assigned_to', $userId)
                      ->orWhereIn('reviewed_by', $userId);
                });
            } else {
                $query->where(function($q) use ($userId) {
                    $q->where('created_by', $userId)
                      ->orWhere('assigned_to', $userId)
                      ->orWhere('reviewed_by', $userId);
                });
            }
        }

        return $query;
    }

    /**
     * الحصول على إحصائيات التعديلات مقسمة حسب النوع (مهام/مشاريع)
     */
    public function getRevisionsByCategory($projectId = null, $isAdmin = false, $userId = null, $dateFilters = null)
    {
        if ($projectId) {
            // للمشروع المحدد
            $taskRevisions = $this->getTaskRevisionsForProject($projectId, $isAdmin, $userId, $dateFilters);
            $projectRevisions = $this->getDirectProjectRevisions($projectId, $isAdmin, $userId, $dateFilters);
        } else {
            // عام
            $taskRevisions = $this->getTaskRevisionsGeneral($isAdmin, $userId, $dateFilters);
            $projectRevisions = $this->getDirectProjectRevisionsGeneral($isAdmin, $userId, $dateFilters);
        }

        return [
            'task_revisions' => [
                'total' => $taskRevisions['total'],
                'pending' => $taskRevisions['pending'],
                'approved' => $taskRevisions['approved'],
                'rejected' => $taskRevisions['rejected'],
            ],
            'project_revisions' => [
                'total' => $projectRevisions['total'],
                'pending' => $projectRevisions['pending'],
                'approved' => $projectRevisions['approved'],
                'rejected' => $projectRevisions['rejected'],
            ],
            'combined' => [
                'total' => $taskRevisions['total'] + $projectRevisions['total'],
                'pending' => $taskRevisions['pending'] + $projectRevisions['pending'],
                'approved' => $taskRevisions['approved'] + $projectRevisions['approved'],
                'rejected' => $taskRevisions['rejected'] + $projectRevisions['rejected'],
            ]
        ];
    }

    /**
     * الحصول على تعديلات المهام لمشروع معين
     */
    private function getTaskRevisionsForProject($projectId, $isAdmin, $userId, $dateFilters = null)
    {
        $query = TaskRevision::where(function($q) use ($projectId) {
            // التعديلات المرتبطة بمهام تنتمي للمشروع
            $q->whereHas('task', function($taskQ) use ($projectId) {
                $taskQ->where('project_id', $projectId);
            })
            // التعديلات المرتبطة بمستخدمي مهام تنتمي للمشروع
            ->orWhereHas('taskUser', function($taskUserQ) use ($projectId) {
                $taskUserQ->whereHas('task', function($subQ) use ($projectId) {
                    $subQ->where('project_id', $projectId);
                });
            });
        });

        $this->applyUserFilter($query, $isAdmin, $userId);

        // تطبيق فلتر التاريخ
        if ($dateFilters && isset($dateFilters['has_filter']) && $dateFilters['has_filter']) {
            $this->dateFilterService->applyDateFilter(
                $query,
                'revision_date',
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }

        return $this->calculateStats($query);
    }

    /**
     * الحصول على التعديلات المرتبطة مباشرة بالمشروع
     */
    private function getDirectProjectRevisions($projectId, $isAdmin, $userId, $dateFilters = null)
    {
        $query = TaskRevision::where('project_id', $projectId);
        $this->applyUserFilter($query, $isAdmin, $userId);

        // تطبيق فلتر التاريخ
        if ($dateFilters && isset($dateFilters['has_filter']) && $dateFilters['has_filter']) {
            $this->dateFilterService->applyDateFilter(
                $query,
                'revision_date',
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }
        return $this->calculateStats($query);
    }

    /**
     * الحصول على تعديلات المهام بشكل عام
     */
    private function getTaskRevisionsGeneral($isAdmin, $userId, $dateFilters = null)
    {
        // ✅ استخدام revision_type بدل task_id/task_user_id
        $query = TaskRevision::where('revision_type', 'task');

        $this->applyUserFilter($query, $isAdmin, $userId);

        // تطبيق فلتر التاريخ
        if ($dateFilters && isset($dateFilters['has_filter']) && $dateFilters['has_filter']) {
            $this->dateFilterService->applyDateFilter(
                $query,
                'revision_date',
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }
        return $this->calculateStats($query);
    }

    /**
     * الحصول على تعديلات المشاريع بشكل عام
     */
    private function getDirectProjectRevisionsGeneral($isAdmin, $userId, $dateFilters = null)
    {
        // ✅ استخدام revision_type بدل project_id
        $query = TaskRevision::where('revision_type', 'project');

        $this->applyUserFilter($query, $isAdmin, $userId);

        // تطبيق فلتر التاريخ
        if ($dateFilters && isset($dateFilters['has_filter']) && $dateFilters['has_filter']) {
            $this->dateFilterService->applyDateFilter(
                $query,
                'revision_date',
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }
        return $this->calculateStats($query);
    }

    /**
     * تطبيق فلتر المستخدم على الاستعلام
     */
    private function applyUserFilter($query, $isAdmin, $userId)
    {
        if (!$isAdmin && $userId) {
            if (is_array($userId)) {
                $query->where(function($q) use ($userId) {
                    $q->whereIn('created_by', $userId)
                      ->orWhereIn('assigned_to', $userId)
                      ->orWhereIn('reviewed_by', $userId)
                      ->orWhereIn('executor_user_id', $userId)
                      ->orWhereIn('responsible_user_id', $userId)
                      // البحث في مصفوفة المراجعين JSON
                      ->orWhere(function($subQ) use ($userId) {
                          foreach ($userId as $uid) {
                              $subQ->orWhereJsonContains('reviewers', ['reviewer_id' => $uid]);
                          }
                      });
                });
            } else {
                $query->where(function($q) use ($userId) {
                    $q->where('created_by', $userId)
                      ->orWhere('assigned_to', $userId)
                      ->orWhere('reviewed_by', $userId)
                      ->orWhere('executor_user_id', $userId)
                      ->orWhere('responsible_user_id', $userId)
                      // البحث في مصفوفة المراجعين JSON
                      ->orWhereJsonContains('reviewers', ['reviewer_id' => $userId]);
                });
            }
        }
    }

    /**
     * حساب الإحصائيات من الاستعلام
     */
    private function calculateStats($query)
    {
        $total = $query->count();
        $pending = (clone $query)->where('status', 'pending')->count();
        $approved = (clone $query)->where('status', 'approved')->count();
        $rejected = (clone $query)->where('status', 'rejected')->count();

        return [
            'total' => $total,
            'pending' => $pending,
            'approved' => $approved,
            'rejected' => $rejected,
        ];
    }

    /**
     * الحصول على إحصائيات التعديلات المنقولة للموظف أو مجموعة موظفين
     */
    public function getRevisionTransferStats($userId, $dateFilters = null)
    {
        // Support both single user ID and array of user IDs
        if (is_array($userId)) {
            $transferredToMe = \App\Models\RevisionAssignment::whereIn('to_user_id', $userId);
            $transferredFromMe = \App\Models\RevisionAssignment::whereIn('from_user_id', $userId);
        } else {
            $transferredToMe = \App\Models\RevisionAssignment::where('to_user_id', $userId);
            $transferredFromMe = \App\Models\RevisionAssignment::where('from_user_id', $userId);
        }

        // تطبيق فلترة التاريخ
        if ($dateFilters && $dateFilters['has_filter']) {
            $transferredToMe = $this->dateFilterService->applyDateFilter(
                $transferredToMe,
                'created_at',
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );

            $transferredFromMe = $this->dateFilterService->applyDateFilter(
                $transferredFromMe,
                'created_at',
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }

        // حساب الإحصائيات
        $executorTransferredToMe = (clone $transferredToMe)->where('assignment_type', 'executor')->count();
        $reviewerTransferredToMe = (clone $transferredToMe)->where('assignment_type', 'reviewer')->count();
        $totalTransferredToMe = $transferredToMe->count();

        $executorTransferredFromMe = (clone $transferredFromMe)->where('assignment_type', 'executor')->count();
        $reviewerTransferredFromMe = (clone $transferredFromMe)->where('assignment_type', 'reviewer')->count();
        $totalTransferredFromMe = $transferredFromMe->count();

        // جلب تفاصيل آخر التعديلات المنقولة
        $transferredToMeQuery = is_array($userId)
            ? \App\Models\RevisionAssignment::whereIn('to_user_id', $userId)
            : \App\Models\RevisionAssignment::where('to_user_id', $userId);

        $transferredToMeDetails = $transferredToMeQuery
            ->with(['revision', 'fromUser', 'assignedBy'])
            ->when($dateFilters && $dateFilters['has_filter'], function($query) use ($dateFilters) {
                return $this->dateFilterService->applyDateFilter(
                    $query,
                    'created_at',
                    $dateFilters['from_date'],
                    $dateFilters['to_date']
                );
            })
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $transferredFromMeQuery = is_array($userId)
            ? \App\Models\RevisionAssignment::whereIn('from_user_id', $userId)
            : \App\Models\RevisionAssignment::where('from_user_id', $userId);

        $transferredFromMeDetails = $transferredFromMeQuery
            ->with(['revision', 'toUser', 'assignedBy'])
            ->when($dateFilters && $dateFilters['has_filter'], function($query) use ($dateFilters) {
                return $this->dateFilterService->applyDateFilter(
                    $query,
                    'created_at',
                    $dateFilters['from_date'],
                    $dateFilters['to_date']
                );
            })
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return [
            'has_transfers' => ($totalTransferredToMe > 0 || $totalTransferredFromMe > 0),
            'transferred_to_me' => $totalTransferredToMe,
            'executor_transferred_to_me' => $executorTransferredToMe,
            'reviewer_transferred_to_me' => $reviewerTransferredToMe,
            'transferred_from_me' => $totalTransferredFromMe,
            'executor_transferred_from_me' => $executorTransferredFromMe,
            'reviewer_transferred_from_me' => $reviewerTransferredFromMe,
            'transferred_to_me_details' => $transferredToMeDetails,
            'transferred_from_me_details' => $transferredFromMeDetails,
        ];
    }
}
