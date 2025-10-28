<?php

namespace App\Services\ProjectDashboard;

use Illuminate\Support\Facades\DB;

class TaskStatsService
{
    protected $dateFilterService;

    public function __construct(DateFilterService $dateFilterService)
    {
        $this->dateFilterService = $dateFilterService;
    }
    /**
     * الحصول على إحصائيات المهام العادية حسب حالة المستخدم
     */
    public function getRegularTaskStats($userIds = null, $userCondition = null, $dateFilters = null)
    {
        $query = DB::table('task_users');

        if ($userIds) {
            if (is_array($userIds)) {
                $query->whereIn('user_id', $userIds);
            } else {
                $query->where('user_id', $userIds);
            }
        }

        if ($userCondition && is_callable($userCondition)) {
            $query->where($userCondition);
        }

        // تطبيق فلاتر التاريخ
        if ($dateFilters && isset($dateFilters['has_filter']) && $dateFilters['has_filter']) {
            $this->dateFilterService->applyDateFilter(
                $query,
                'task_users.created_at',
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }

        return $query->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    /**
     * الحصول على إحصائيات مهام القوالب حسب حالة المستخدم
     */
    public function getTemplateTaskStats($userIds = null, $userCondition = null, $dateFilters = null)
    {
        $query = DB::table('template_task_user');

        if ($userIds) {
            if (is_array($userIds)) {
                $query->whereIn('user_id', $userIds);
            } else {
                $query->where('user_id', $userIds);
            }
        }

        if ($userCondition && is_callable($userCondition)) {
            $query->where($userCondition);
        }

        // تطبيق فلاتر التاريخ
        if ($dateFilters && isset($dateFilters['has_filter']) && $dateFilters['has_filter']) {
            $this->dateFilterService->applyDateFilter(
                $query,
                'template_task_user.created_at',
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }

        return $query->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    /**
     * الحصول على إحصائيات المهام العادية للقسم
     */
    public function getRegularTaskStatsByDepartment($department, $dateFilters = null)
    {
        $query = DB::table('task_users')
            ->join('users', 'task_users.user_id', '=', 'users.id')
            ->where('users.department', $department);

        // تطبيق فلاتر التاريخ
        if ($dateFilters && isset($dateFilters['has_filter']) && $dateFilters['has_filter']) {
            $this->dateFilterService->applyDateFilter(
                $query,
                'task_users.created_at',
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }

        return $query->select('task_users.status', DB::raw('count(*) as count'))
            ->groupBy('task_users.status')
            ->pluck('count', 'status')
            ->toArray();
    }

    /**
     * الحصول على إحصائيات مهام القوالب للقسم
     */
    public function getTemplateTaskStatsByDepartment($department, $dateFilters = null)
    {
        $query = DB::table('template_task_user')
            ->join('users', 'template_task_user.user_id', '=', 'users.id')
            ->where('users.department', $department);

        // تطبيق فلاتر التاريخ
        if ($dateFilters && isset($dateFilters['has_filter']) && $dateFilters['has_filter']) {
            $this->dateFilterService->applyDateFilter(
                $query,
                'template_task_user.created_at',
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }

        return $query->select('template_task_user.status', DB::raw('count(*) as count'))
            ->groupBy('template_task_user.status')
            ->pluck('count', 'status')
            ->toArray();
    }

    /**
     * الحصول على إحصائيات المهام العادية مع معالجة النقل
     */
    public function getRegularTaskStatsWithTransfers($userId, $dateFilters = null)
    {
        // نلف شروط OR داخل مجموعة واحدة لضمان تطبيق فلتر التاريخ على الكل: ((A) OR (B)) AND (date)
        $query = DB::table('task_users')
            ->where(function($outer) use ($userId) {
                $outer->where(function($query) use ($userId) {
                    // المهام الحالية للمستخدم (غير منقولة من غيره)
                    $query->where('user_id', $userId)
                          ->where(function($subQuery) {
                              $subQuery->where('is_transferred', false)
                                       ->orWhereNull('is_transferred');
                          });
                })
                ->orWhere(function($query) use ($userId) {
                    // المهام المنقولة من المستخدم = تحسب كغير مكتملة له
                    $query->where('original_user_id', $userId)
                          ->where('is_transferred', true)
                          ->where('user_id', '!=', $userId);
                });
            });

        // تطبيق فلاتر التاريخ
        if ($dateFilters && isset($dateFilters['has_filter']) && $dateFilters['has_filter']) {
            $this->dateFilterService->applyDateFilter(
                $query,
                'task_users.created_at',
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }

        return $query->select(
                DB::raw("CASE
                    WHEN original_user_id = $userId AND is_transferred = 1 AND user_id != $userId
                    THEN 'transferred_out'
                    ELSE status
                END as effective_status"),
                DB::raw('count(*) as count')
            )
            ->groupBy('effective_status')
            ->pluck('count', 'effective_status')
            ->toArray();
    }

    /**
     * الحصول على إحصائيات مهام القوالب مع معالجة النقل
     */
    public function getTemplateTaskStatsWithTransfers($userId, $dateFilters = null)
    {
        // نلف شروط OR داخل مجموعة واحدة لضمان تطبيق فلتر التاريخ على الكل: ((A) OR (B)) AND (date)
        $query = DB::table('template_task_user')
            ->where(function($outer) use ($userId) {
                $outer->where(function($query) use ($userId) {
                    // المهام الحالية للمستخدم (غير منقولة من غيره)
                    $query->where('user_id', $userId)
                          ->where(function($subQuery) {
                              $subQuery->where('is_transferred', false)
                                       ->orWhereNull('is_transferred');
                          });
                })
                ->orWhere(function($query) use ($userId) {
                    // المهام المنقولة من المستخدم = تحسب كغير مكتملة له
                    $query->where('original_user_id', $userId)
                          ->where('is_transferred', true)
                          ->where('user_id', '!=', $userId);
                });
            });

        // تطبيق فلاتر التاريخ
        if ($dateFilters && isset($dateFilters['has_filter']) && $dateFilters['has_filter']) {
            $this->dateFilterService->applyDateFilter(
                $query,
                'template_task_user.created_at',
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }

        return $query->select(
                DB::raw("CASE
                    WHEN original_user_id = $userId AND is_transferred = 1 AND user_id != $userId
                    THEN 'transferred_out'
                    ELSE status
                END as effective_status"),
                DB::raw('count(*) as count')
            )
            ->groupBy('effective_status')
            ->pluck('count', 'effective_status')
            ->toArray();
    }

    /**
     * دمج إحصائيات المهام العادية ومهام القوالب
     */
    public function combineTaskStats($regularStats, $templateStats)
    {
        $combined = [
            'regular' => [
                'total' => array_sum($regularStats),
                'new' => $regularStats['new'] ?? 0,
                'in_progress' => $regularStats['in_progress'] ?? 0,
                'paused' => $regularStats['paused'] ?? 0,
                'completed' => $regularStats['completed'] ?? 0,
                'transferred_out' => $regularStats['transferred_out'] ?? 0,
            ],
            'template' => [
                'total' => array_sum($templateStats),
                'new' => $templateStats['new'] ?? 0,
                'in_progress' => $templateStats['in_progress'] ?? 0,
                'paused' => $templateStats['paused'] ?? 0,
                'completed' => $templateStats['completed'] ?? 0,
                'transferred_out' => $templateStats['transferred_out'] ?? 0,
            ]
        ];

        $combined['combined'] = [
            'total' => $combined['regular']['total'] + $combined['template']['total'],
            'new' => $combined['regular']['new'] + $combined['template']['new'],
            'in_progress' => $combined['regular']['in_progress'] + $combined['template']['in_progress'],
            'paused' => $combined['regular']['paused'] + $combined['template']['paused'],
            'completed' => $combined['regular']['completed'] + $combined['template']['completed'],
            'transferred_out' => $combined['regular']['transferred_out'] + $combined['template']['transferred_out'],
        ];

        return $combined;
    }

    /**
     * الحصول على إحصائيات المهام العادية مع التفرقة بين العادية والإضافية
     */
    public function getRegularTaskStatsWithAdditional($userIds = null, $dateFilters = null)
    {
        $query = DB::table('task_users');

        if ($userIds) {
            if (is_array($userIds)) {
                $query->whereIn('user_id', $userIds);
            } else {
                $query->where('user_id', $userIds);
            }
        }

        // تطبيق فلاتر التاريخ
        if ($dateFilters && isset($dateFilters['has_filter']) && $dateFilters['has_filter']) {
            $this->dateFilterService->applyDateFilter(
                $query,
                'task_users.created_at',
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }

        $results = $query->select(
                'status',
                DB::raw('COALESCE(is_additional_task, 0) as is_additional'),
                DB::raw('count(*) as count')
            )
            ->groupBy('status', 'is_additional')
            ->get();

        $stats = [
            'original' => [], // المهام الأصلية
            'additional' => [], // المهام الإضافية
        ];

        foreach ($results as $row) {
            $type = $row->is_additional ? 'additional' : 'original';
            $stats[$type][$row->status] = $row->count;
        }

        return [
            'original' => [
                'total' => array_sum($stats['original']),
                'new' => $stats['original']['new'] ?? 0,
                'in_progress' => $stats['original']['in_progress'] ?? 0,
                'paused' => $stats['original']['paused'] ?? 0,
                'completed' => $stats['original']['completed'] ?? 0,
            ],
            'additional' => [
                'total' => array_sum($stats['additional']),
                'new' => $stats['additional']['new'] ?? 0,
                'in_progress' => $stats['additional']['in_progress'] ?? 0,
                'paused' => $stats['additional']['paused'] ?? 0,
                'completed' => $stats['additional']['completed'] ?? 0,
            ],
            'combined' => [
                'total' => array_sum($stats['original']) + array_sum($stats['additional']),
                'new' => ($stats['original']['new'] ?? 0) + ($stats['additional']['new'] ?? 0),
                'in_progress' => ($stats['original']['in_progress'] ?? 0) + ($stats['additional']['in_progress'] ?? 0),
                'paused' => ($stats['original']['paused'] ?? 0) + ($stats['additional']['paused'] ?? 0),
                'completed' => ($stats['original']['completed'] ?? 0) + ($stats['additional']['completed'] ?? 0),
            ]
        ];
    }

    /**
     * الحصول على إحصائيات مهام القوالب مع التفرقة بين العادية والإضافية
     */
    public function getTemplateTaskStatsWithAdditional($userIds = null, $dateFilters = null)
    {
        $query = DB::table('template_task_user');

        if ($userIds) {
            if (is_array($userIds)) {
                $query->whereIn('user_id', $userIds);
            } else {
                $query->where('user_id', $userIds);
            }
        }

        // تطبيق فلاتر التاريخ
        if ($dateFilters && isset($dateFilters['has_filter']) && $dateFilters['has_filter']) {
            $this->dateFilterService->applyDateFilter(
                $query,
                'template_task_user.created_at',
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }

        $results = $query->select(
                'status',
                DB::raw('COALESCE(is_additional_task, 0) as is_additional'),
                DB::raw('count(*) as count')
            )
            ->groupBy('status', 'is_additional')
            ->get();

        $stats = [
            'original' => [], // المهام الأصلية
            'additional' => [], // المهام الإضافية
        ];

        foreach ($results as $row) {
            $type = $row->is_additional ? 'additional' : 'original';
            $stats[$type][$row->status] = $row->count;
        }

        return [
            'original' => [
                'total' => array_sum($stats['original']),
                'new' => $stats['original']['new'] ?? 0,
                'in_progress' => $stats['original']['in_progress'] ?? 0,
                'paused' => $stats['original']['paused'] ?? 0,
                'completed' => $stats['original']['completed'] ?? 0,
            ],
            'additional' => [
                'total' => array_sum($stats['additional']),
                'new' => $stats['additional']['new'] ?? 0,
                'in_progress' => $stats['additional']['in_progress'] ?? 0,
                'paused' => $stats['additional']['paused'] ?? 0,
                'completed' => $stats['additional']['completed'] ?? 0,
            ],
            'combined' => [
                'total' => array_sum($stats['original']) + array_sum($stats['additional']),
                'new' => ($stats['original']['new'] ?? 0) + ($stats['additional']['new'] ?? 0),
                'in_progress' => ($stats['original']['in_progress'] ?? 0) + ($stats['additional']['in_progress'] ?? 0),
                'paused' => ($stats['original']['paused'] ?? 0) + ($stats['additional']['paused'] ?? 0),
                'completed' => ($stats['original']['completed'] ?? 0) + ($stats['additional']['completed'] ?? 0),
            ]
        ];
    }

    /**
     * دمج إحصائيات المهام مع التفرقة بين الأصلية والإضافية
     */
    public function combineTaskStatsWithAdditional($regularStats, $templateStats)
    {
        return [
            'regular' => $regularStats,
            'template' => $templateStats,
            'all_original' => [
                'total' => $regularStats['original']['total'] + $templateStats['original']['total'],
                'new' => $regularStats['original']['new'] + $templateStats['original']['new'],
                'in_progress' => $regularStats['original']['in_progress'] + $templateStats['original']['in_progress'],
                'paused' => $regularStats['original']['paused'] + $templateStats['original']['paused'],
                'completed' => $regularStats['original']['completed'] + $templateStats['original']['completed'],
            ],
            'all_additional' => [
                'total' => $regularStats['additional']['total'] + $templateStats['additional']['total'],
                'new' => $regularStats['additional']['new'] + $templateStats['additional']['new'],
                'in_progress' => $regularStats['additional']['in_progress'] + $templateStats['additional']['in_progress'],
                'paused' => $regularStats['additional']['paused'] + $templateStats['additional']['paused'],
                'completed' => $regularStats['additional']['completed'] + $templateStats['additional']['completed'],
            ],
            'grand_total' => [
                'total' => $regularStats['combined']['total'] + $templateStats['combined']['total'],
                'new' => $regularStats['combined']['new'] + $templateStats['combined']['new'],
                'in_progress' => $regularStats['combined']['in_progress'] + $templateStats['combined']['in_progress'],
                'paused' => $regularStats['combined']['paused'] + $templateStats['combined']['paused'],
                'completed' => $regularStats['combined']['completed'] + $templateStats['combined']['completed'],
            ]
        ];
    }

    /**
     * الحصول على إحصائيات المهام المتأخرة
     */
    public function getOverdueTasksStats($userIds = null, $dateFilters = null)
    {
        $now = now();

        // المهام العادية المتأخرة (غير مكتملة وتجاوزت due_date)
        $overdueRegularQuery = DB::table('task_users')
            ->join('tasks', 'task_users.task_id', '=', 'tasks.id')
            ->whereNotNull('task_users.due_date')
            ->where('task_users.due_date', '<', $now)
            ->whereIn('task_users.status', ['new', 'in_progress', 'paused']);

        if ($userIds) {
            if (is_array($userIds)) {
                $overdueRegularQuery->whereIn('task_users.user_id', $userIds);
            } else {
                $overdueRegularQuery->where('task_users.user_id', $userIds);
            }
        }

        if ($dateFilters && isset($dateFilters['has_filter']) && $dateFilters['has_filter']) {
            $this->dateFilterService->applyDateFilter(
                $overdueRegularQuery,
                'task_users.created_at',
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }

        $overdueRegular = $overdueRegularQuery->count();

        // المهام العادية المكتملة بعد الموعد
        $completedLateRegularQuery = DB::table('task_users')
            ->whereNotNull('task_users.due_date')
            ->whereNotNull('task_users.completed_date')
            ->whereRaw('task_users.completed_date > task_users.due_date')
            ->where('task_users.status', 'completed');

        if ($userIds) {
            if (is_array($userIds)) {
                $completedLateRegularQuery->whereIn('task_users.user_id', $userIds);
            } else {
                $completedLateRegularQuery->where('task_users.user_id', $userIds);
            }
        }

        if ($dateFilters && isset($dateFilters['has_filter']) && $dateFilters['has_filter']) {
            $this->dateFilterService->applyDateFilter(
                $completedLateRegularQuery,
                'task_users.created_at',
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }

        $completedLateRegular = $completedLateRegularQuery->count();

        // مهام القوالب المتأخرة (غير مكتملة وتجاوزت deadline)
        $overdueTemplateQuery = DB::table('template_task_user')
            ->whereNotNull('template_task_user.deadline')
            ->where('template_task_user.deadline', '<', $now)
            ->whereIn('template_task_user.status', ['new', 'in_progress', 'paused']);

        if ($userIds) {
            if (is_array($userIds)) {
                $overdueTemplateQuery->whereIn('template_task_user.user_id', $userIds);
            } else {
                $overdueTemplateQuery->where('template_task_user.user_id', $userIds);
            }
        }

        if ($dateFilters && isset($dateFilters['has_filter']) && $dateFilters['has_filter']) {
            $this->dateFilterService->applyDateFilter(
                $overdueTemplateQuery,
                'template_task_user.created_at',
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }

        $overdueTemplate = $overdueTemplateQuery->count();

        // مهام القوالب المكتملة بعد الموعد
        $completedLateTemplateQuery = DB::table('template_task_user')
            ->whereNotNull('template_task_user.deadline')
            ->whereNotNull('template_task_user.completed_at')
            ->whereRaw('template_task_user.completed_at > template_task_user.deadline')
            ->where('template_task_user.status', 'completed');

        if ($userIds) {
            if (is_array($userIds)) {
                $completedLateTemplateQuery->whereIn('template_task_user.user_id', $userIds);
            } else {
                $completedLateTemplateQuery->where('template_task_user.user_id', $userIds);
            }
        }

        if ($dateFilters && isset($dateFilters['has_filter']) && $dateFilters['has_filter']) {
            $this->dateFilterService->applyDateFilter(
                $completedLateTemplateQuery,
                'template_task_user.created_at',
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }

        $completedLateTemplate = $completedLateTemplateQuery->count();

        return [
            'overdue_pending' => $overdueRegular + $overdueTemplate, // متأخرة ولم تكتمل بعد
            'completed_late' => $completedLateRegular + $completedLateTemplate, // اكتملت بعد الموعد
            'total_overdue' => $overdueRegular + $overdueTemplate + $completedLateRegular + $completedLateTemplate,
            'regular_overdue_pending' => $overdueRegular,
            'template_overdue_pending' => $overdueTemplate,
            'regular_completed_late' => $completedLateRegular,
            'template_completed_late' => $completedLateTemplate,
        ];
    }

    /**
     * الحصول على إحصائيات المهام المتأخرة للقسم
     */
    public function getOverdueTasksStatsByDepartment($department, $dateFilters = null)
    {
        $departmentUserIds = \App\Models\User::where('department', $department)->pluck('id')->toArray();
        return $this->getOverdueTasksStats($departmentUserIds, $dateFilters);
    }

    /**
     * الحصول على نشاط المستخدم الأخير
     */
    public function getLastActivity($userId)
    {
        $lastRegularActivity = DB::table('task_users')
            ->where('user_id', $userId)
            ->orderBy('updated_at', 'desc')
            ->value('updated_at');

        $lastTemplateActivity = DB::table('template_task_user')
            ->where('user_id', $userId)
            ->orderBy('updated_at', 'desc')
            ->value('updated_at');

        if ($lastRegularActivity && $lastTemplateActivity) {
            return max($lastRegularActivity, $lastTemplateActivity);
        } elseif ($lastRegularActivity) {
            return $lastRegularActivity;
        } elseif ($lastTemplateActivity) {
            return $lastTemplateActivity;
        }

        return null;
    }

    /**
     * الحصول على المهام الحديثة للمستخدم
     */
    public function getRecentTasks($userId, $limit = 10, $dateFilters = null)
    {
        $query = DB::table('task_users')
            ->join('tasks', 'task_users.task_id', '=', 'tasks.id')
            ->leftJoin('projects', 'tasks.project_id', '=', 'projects.id')
            ->where('task_users.user_id', $userId);

        // تطبيق فلاتر التاريخ
        if ($dateFilters && isset($dateFilters['has_filter']) && $dateFilters['has_filter']) {
            $this->dateFilterService->applyDateFilter(
                $query,
                'task_users.created_at',
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }

        return $query->select('tasks.name as task_name', 'projects.name as project_name', 'task_users.status', 'task_users.updated_at')
            ->orderBy('task_users.updated_at', 'desc')
            ->take($limit)
            ->get();
    }

    /**
     * حساب المهام المكتملة شهرياً للمستخدم
     */
    public function getMonthlyTaskCompletion($userId, $months = 6, $dateFilters = null)
    {
        $monthlyStats = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $month = \Carbon\Carbon::now()->subMonths($i);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();

            // تطبيق فلاتر التاريخ إذا كانت موجودة
            if ($dateFilters && isset($dateFilters['has_filter']) && $dateFilters['has_filter']) {
                // تطبيق قيود التاريخ المحددة من المستخدم
                if ($dateFilters['from_date'] && $monthEnd < $dateFilters['from_date']) {
                    continue; // تخطي هذا الشهر إذا كان قبل تاريخ البداية المحدد
                }
                if ($dateFilters['to_date'] && $monthStart > $dateFilters['to_date']) {
                    continue; // تخطي هذا الشهر إذا كان بعد تاريخ النهاية المحدد
                }

                // تعديل نطاق الشهر ليتوافق مع الفلتر
                $monthStart = max($monthStart, $dateFilters['from_date'] ?? $monthStart);
                $monthEnd = min($monthEnd, $dateFilters['to_date'] ?? $monthEnd);
            }

            $monthlyCompletedTasks = DB::table('task_users')
                ->where('user_id', $userId)
                ->where('status', 'completed')
                ->whereBetween('updated_at', [$monthStart, $monthEnd])
                ->count();

            $monthlyTemplateCompletedTasks = DB::table('template_task_user')
                ->where('user_id', $userId)
                ->where('status', 'completed')
                ->whereBetween('updated_at', [$monthStart, $monthEnd])
                ->count();

            $monthlyStats[] = [
                'month' => $month->format('M Y'),
                'month_ar' => $month->locale('ar')->format('M Y'),
                'completed_tasks' => $monthlyCompletedTasks + $monthlyTemplateCompletedTasks
            ];
        }

        return $monthlyStats;
    }

    /**
     * الحصول على تفاصيل المهام المتأخرة مع أسماء المستخدمين
     */
    public function getOverdueTasksDetails($userIds = null, $dateFilters = null, $limit = 10)
    {
        $now = now();

        // المهام العادية المتأخرة (غير مكتملة وتجاوزت due_date)
        $overdueRegularQuery = DB::table('task_users')
            ->join('tasks', 'task_users.task_id', '=', 'tasks.id')
            ->join('users', 'task_users.user_id', '=', 'users.id')
            ->whereNotNull('task_users.due_date')
            ->where('task_users.due_date', '<', $now)
            ->whereIn('task_users.status', ['new', 'in_progress', 'paused'])
            ->select(
                'tasks.name as task_name',
                'users.id as user_id',
                'users.name as user_name',
                'task_users.due_date',
                'task_users.status',
                DB::raw("'regular' as task_type"),
                DB::raw("DATEDIFF(NOW(), task_users.due_date) as days_overdue")
            );

        if ($userIds && is_array($userIds)) {
            $overdueRegularQuery->whereIn('task_users.user_id', $userIds);
        }

        if ($dateFilters && isset($dateFilters['has_filter']) && $dateFilters['has_filter']) {
            $this->dateFilterService->applyDateFilter(
                $overdueRegularQuery,
                'task_users.created_at',
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }

        $overdueRegular = $overdueRegularQuery->limit($limit)->get();

        // مهام القوالب المتأخرة (غير مكتملة وتجاوزت deadline)
        $overdueTemplateQuery = DB::table('template_task_user')
            ->join('template_tasks', 'template_task_user.template_task_id', '=', 'template_tasks.id')
            ->join('users', 'template_task_user.user_id', '=', 'users.id')
            ->whereNotNull('template_task_user.deadline')
            ->where('template_task_user.deadline', '<', $now)
            ->whereIn('template_task_user.status', ['new', 'in_progress', 'paused'])
            ->select(
                'template_tasks.name as task_name',
                'users.id as user_id',
                'users.name as user_name',
                'template_task_user.deadline as due_date',
                'template_task_user.status',
                DB::raw("'template' as task_type"),
                DB::raw("DATEDIFF(NOW(), template_task_user.deadline) as days_overdue")
            );

        if ($userIds && is_array($userIds)) {
            $overdueTemplateQuery->whereIn('template_task_user.user_id', $userIds);
        }

        if ($dateFilters && isset($dateFilters['has_filter']) && $dateFilters['has_filter']) {
            $this->dateFilterService->applyDateFilter(
                $overdueTemplateQuery,
                'template_task_user.created_at',
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }

        $overdueTemplate = $overdueTemplateQuery->limit($limit)->get();

        // المهام العادية المكتملة بعد الموعد
        $completedLateRegularQuery = DB::table('task_users')
            ->join('tasks', 'task_users.task_id', '=', 'tasks.id')
            ->join('users', 'task_users.user_id', '=', 'users.id')
            ->whereNotNull('task_users.due_date')
            ->whereNotNull('task_users.completed_date')
            ->whereRaw('task_users.completed_date > task_users.due_date')
            ->where('task_users.status', 'completed')
            ->select(
                'tasks.name as task_name',
                'users.id as user_id',
                'users.name as user_name',
                'task_users.due_date',
                'task_users.completed_date',
                'task_users.status',
                DB::raw("'regular' as task_type"),
                DB::raw("DATEDIFF(task_users.completed_date, task_users.due_date) as days_late")
            );

        if ($userIds && is_array($userIds)) {
            $completedLateRegularQuery->whereIn('task_users.user_id', $userIds);
        }

        if ($dateFilters && isset($dateFilters['has_filter']) && $dateFilters['has_filter']) {
            $this->dateFilterService->applyDateFilter(
                $completedLateRegularQuery,
                'task_users.created_at',
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }

        $completedLateRegular = $completedLateRegularQuery->limit($limit)->get();

        // مهام القوالب المكتملة بعد الموعد
        $completedLateTemplateQuery = DB::table('template_task_user')
            ->join('template_tasks', 'template_task_user.template_task_id', '=', 'template_tasks.id')
            ->join('users', 'template_task_user.user_id', '=', 'users.id')
            ->whereNotNull('template_task_user.deadline')
            ->whereNotNull('template_task_user.completed_at')
            ->whereRaw('template_task_user.completed_at > template_task_user.deadline')
            ->where('template_task_user.status', 'completed')
            ->select(
                'template_tasks.name as task_name',
                'users.id as user_id',
                'users.name as user_name',
                'template_task_user.deadline as due_date',
                'template_task_user.completed_at as completed_date',
                'template_task_user.status',
                DB::raw("'template' as task_type"),
                DB::raw("DATEDIFF(template_task_user.completed_at, template_task_user.deadline) as days_late")
            );

        if ($userIds && is_array($userIds)) {
            $completedLateTemplateQuery->whereIn('template_task_user.user_id', $userIds);
        }

        if ($dateFilters && isset($dateFilters['has_filter']) && $dateFilters['has_filter']) {
            $this->dateFilterService->applyDateFilter(
                $completedLateTemplateQuery,
                'template_task_user.created_at',
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }

        $completedLateTemplate = $completedLateTemplateQuery->limit($limit)->get();

        return [
            'overdue_pending' => $overdueRegular->merge($overdueTemplate),
            'completed_late' => $completedLateRegular->merge($completedLateTemplate),
        ];
    }
}
