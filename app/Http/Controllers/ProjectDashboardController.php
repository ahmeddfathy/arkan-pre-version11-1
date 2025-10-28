<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Meeting;
use App\Models\CompanyService;
use App\Services\Auth\RoleCheckService;
use App\Services\ProjectDashboard\ProjectStatsService;
use App\Services\ProjectDashboard\TaskStatsService;
use App\Services\ProjectDashboard\TimeCalculationService;
use App\Services\ProjectDashboard\DepartmentService;
use App\Services\ProjectDashboard\TeamService;
use App\Services\ProjectDashboard\EmployeePerformanceService;
use App\Services\ProjectDashboard\RevisionStatsService;
use App\Services\ProjectDashboard\DateFilterService;
use App\Services\ProjectManagement\ProjectAnalyticsService;
use App\Services\EmployeeErrorController\EmployeeErrorStatisticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ProjectDashboardController extends Controller
{
    protected $roleCheckService;
    protected $projectStatsService;
    protected $taskStatsService;
    protected $timeCalculationService;
    protected $departmentService;
    protected $teamService;
    protected $employeePerformanceService;
    protected $revisionStatsService;
    protected $dateFilterService;
    protected $analyticsService;
    protected $errorStatsService;

    public function __construct(
        RoleCheckService $roleCheckService,
        ProjectStatsService $projectStatsService,
        TaskStatsService $taskStatsService,
        TimeCalculationService $timeCalculationService,
        DepartmentService $departmentService,
        TeamService $teamService,
        EmployeePerformanceService $employeePerformanceService,
        RevisionStatsService $revisionStatsService,
        DateFilterService $dateFilterService,
        ProjectAnalyticsService $analyticsService,
        EmployeeErrorStatisticsService $errorStatsService
    ) {
        $this->roleCheckService = $roleCheckService;
        $this->projectStatsService = $projectStatsService;
        $this->taskStatsService = $taskStatsService;
        $this->timeCalculationService = $timeCalculationService;
        $this->departmentService = $departmentService;
        $this->teamService = $teamService;
        $this->employeePerformanceService = $employeePerformanceService;
        $this->revisionStatsService = $revisionStatsService;
        $this->dateFilterService = $dateFilterService;
        $this->analyticsService = $analyticsService;
        $this->errorStatsService = $errorStatsService;
    }

    /**
     * صفحة لوحة التحكم الرئيسية للمشاريع
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $userHierarchyLevel = \App\Models\RoleHierarchy::getUserMaxHierarchyLevel($user);

        // Level 1 & 2 لا يمكنهم الوصول للـ dashboard
        if ($userHierarchyLevel < 3) {
            abort(403, 'غير مسموح لك بالوصول إلى هذه الصفحة');
        }

        // Level 3 يُعاد توجيهه لـ dashboard التيم
        if ($userHierarchyLevel == 3) {
            $userTeam = DB::table('teams')->where('user_id', $user->id)->first();
            if ($userTeam) {
                return redirect()->route('departments.teams.show', [
                    'department' => urlencode($user->department ?? 'Unknown'),
                    'teamId' => $userTeam->id
                ]);
            }
        }

        // Level 4 يُعاد توجيهه لـ dashboard القسم
        if ($userHierarchyLevel == 4) {
            if ($user->department) {
                return redirect()->route('departments.show', [
                    'department' => urlencode($user->department)
                ]);
            }
        }

        // Level 5+ يصل للـ dashboard العام

        // معالجة فلاتر التاريخ
        $dateFilters = $this->dateFilterService->processDateFilters($request);

        // تسجيل النشاط - دخول لوحة تحكم المشاريع
        if (\Illuminate\Support\Facades\Auth::check()) {
            activity()
                ->causedBy(\Illuminate\Support\Facades\Auth::user())
                ->withProperties([
                    'action_type' => 'view_index',
                    'page' => 'project_dashboard',
                    'date_filters' => $dateFilters,
                    'viewed_at' => now()->toDateTimeString(),
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip()
                ])
                ->log('دخل على لوحة تحكم المشاريع');
        }

        $user = Auth::user();
        $isAdmin = $this->roleCheckService->userHasRole(['hr', 'company_manager', 'project_manager']);
        $userId = $isAdmin ? null : $user->id;

        // إحصائيات المشاريع العامة
        $projectStats = $this->projectStatsService->getGeneralProjectStats($isAdmin, $userId, $dateFilters);

        // إحصائيات المهام مع التفرقة بين الأصلية والإضافية
        $userIdsForStats = $isAdmin ? null : $user->id;

        $regularTasksWithAdditional = $this->taskStatsService->getRegularTaskStatsWithAdditional($userIdsForStats, $dateFilters);
        $templateTasksWithAdditional = $this->taskStatsService->getTemplateTaskStatsWithAdditional($userIdsForStats, $dateFilters);

        $combinedTaskStats = $this->taskStatsService->combineTaskStatsWithAdditional(
            $regularTasksWithAdditional,
            $templateTasksWithAdditional
        );

        // الإحصائيات القديمة للتوافق مع الـ View الحالية
        $totalTasks = $regularTasksWithAdditional['combined']['total'];
        $newTasks = $regularTasksWithAdditional['combined']['new'];
        $inProgressTasks = $regularTasksWithAdditional['combined']['in_progress'];
        $pausedTasks = $regularTasksWithAdditional['combined']['paused'];
        $completedTasks = $regularTasksWithAdditional['combined']['completed'];

        $totalTemplateTasks = $templateTasksWithAdditional['combined']['total'];
        $newTemplateTasks = $templateTasksWithAdditional['combined']['new'];
        $inProgressTemplateTasks = $templateTasksWithAdditional['combined']['in_progress'];
        $pausedTemplateTasks = $templateTasksWithAdditional['combined']['paused'];
        $completedTemplateTasks = $templateTasksWithAdditional['combined']['completed'];

        // دمج إحصائيات المهام
        $allTotalTasks = $combinedTaskStats['grand_total']['total'];
        $allNewTasks = $combinedTaskStats['grand_total']['new'];
        $allInProgressTasks = $combinedTaskStats['grand_total']['in_progress'];
        $allPausedTasks = $combinedTaskStats['grand_total']['paused'];
        $allCompletedTasks = $combinedTaskStats['grand_total']['completed'];

        // إحصائيات المهام الأصلية والإضافية
        $allOriginalTasks = $combinedTaskStats['all_original']['total'];
        $allAdditionalTasks = $combinedTaskStats['all_additional']['total'];

        // المشاريع المختلفة
        $latestProjects = $this->projectStatsService->getLatestProjects($isAdmin, $userId, 5, $dateFilters);
        $activeProjects = $this->projectStatsService->getActiveProjects($isAdmin, $userId, $dateFilters);
        $overdueProjects = $this->projectStatsService->getOverdueProjects($isAdmin, $userId, $dateFilters);
        $kanbanProjects = $this->projectStatsService->getKanbanProjects($isAdmin, $userId, $dateFilters);

        // إحصائيات الخدمات والموظفين
        $serviceStats = $this->projectStatsService->getServiceStats($isAdmin, $userId, $dateFilters);
        $topEmployees = $this->projectStatsService->getTopEmployees($isAdmin, $userId, 10, $dateFilters);

        // البيانات الأساسية
        $employees = User::orderBy('name')->pluck('name', 'id');
        $departments = CompanyService::select('department')->distinct()->whereNotNull('department')->pluck('department');
        $services = CompanyService::where('is_active', true)->orderBy('name')->pluck('name', 'id');

        // اجتماعات اليوم
        $todayMeetings = Meeting::whereDate('start_time', Carbon::today())
            ->with(['creator', 'client', 'participants'])
            ->orderBy('start_time')
            ->get();

        if (!$isAdmin) {
            $todayMeetings = $todayMeetings->filter(function($meeting) use ($user) {
                return $meeting->created_by == $user->id ||
                       $meeting->participants->contains('id', $user->id);
            });
        }

        // بيانات الأقسام مع الإحصائيات
        $departmentsData = $this->projectStatsService->getDepartmentsData();
        $departmentsData = $this->departmentService->addStatsToDeepartmentsData($departmentsData);

        // إحصائيات التعديلات
        $revisionStats = $this->revisionStatsService->getGeneralRevisionStats($isAdmin, $userId, $dateFilters);
        $latestRevisions = $this->revisionStatsService->getLatestRevisions($isAdmin, $userId, $dateFilters);
        $pendingRevisions = $this->revisionStatsService->getPendingRevisions($isAdmin, $userId, $dateFilters);
        $urgentRevisions = $this->revisionStatsService->getUrgentRevisions($isAdmin, $userId, $dateFilters);
        $revisionsByType = $this->revisionStatsService->getRevisionsByType($isAdmin, $userId, $dateFilters);
        $revisionsByCategory = $this->revisionStatsService->getRevisionsByCategory(null, $isAdmin, $userId, $dateFilters);
        $revisionsByStatus = $this->revisionStatsService->getRevisionsByStatus($isAdmin, $userId, $dateFilters);
        $attachmentStats = $this->revisionStatsService->getAttachmentStats($isAdmin, $userId, $dateFilters);
        $averageReviewTime = $this->revisionStatsService->getAverageReviewTime($dateFilters);

        // إضافة وصف الفترة الزمنية
        $periodDescription = $this->dateFilterService->getPeriodDescription($dateFilters);

        // ✅ إحصائيات نقل المهام العامة
        $globalTransferStats = $this->getGlobalTransferStatistics($isAdmin, $userId, $dateFilters);

        // ✅ إحصائيات نقل التعديلات
        $globalRevisionTransferStats = $this->revisionStatsService->getRevisionTransferStats(
            $isAdmin ? null : $userId,
            $dateFilters
        );

        // ✅ إحصائيات الأخطاء
        $globalErrorStats = $this->errorStatsService->getGroupErrorStats(
            $isAdmin ? User::pluck('id')->toArray() : $userId,
            $dateFilters
        );

        // ✅ إحصائيات تأخير المشاريع للداش بورد
        $dashboardProjectOverdueStats = $this->projectStatsService->calculateDashboardOverdueStats($isAdmin, $userId, $dateFilters);

        // ✅ إحصائيات تأخير المهام للداش بورد
        $dashboardTaskOverdueStats = $this->taskStatsService->getOverdueTasksStats($userId, $dateFilters);

        // ✅ تفاصيل المهام المتأخرة
        $dashboardTaskOverdueDetails = $this->taskStatsService->getOverdueTasksDetails(
            $userId ? [$userId] : null,
            $dateFilters,
            20
        );

        // ✅ آخر المهام العادية من task_users
        $recentRegularTasksQuery = DB::table('task_users')
            ->join('tasks', 'task_users.task_id', '=', 'tasks.id')
            ->join('users', 'task_users.user_id', '=', 'users.id')
            ->leftJoin('projects', 'tasks.project_id', '=', 'projects.id')
            ->select(
                'task_users.id',
                'task_users.user_id',
                'tasks.name as task_name',
                'task_users.status',
                'users.name as user_name',
                'projects.name as project_name',
                'task_users.updated_at as last_updated'
            );

        if (!$isAdmin) {
            $recentRegularTasksQuery->where('task_users.user_id', $user->id);
        }

        // تطبيق فلاتر التاريخ على آخر المهام العادية
        if ($dateFilters['has_filter']) {
            $this->dateFilterService->applyDateFilter(
                $recentRegularTasksQuery,
                'task_users.updated_at',
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }

        $recentRegularTasks = $recentRegularTasksQuery
            ->orderBy('task_users.updated_at', 'desc')
            ->take(24)
            ->get();

        // ✅ آخر مهام القوالب من template_task_user
        $recentTemplateTasksQuery = DB::table('template_task_user')
            ->join('template_tasks', 'template_task_user.template_task_id', '=', 'template_tasks.id')
            ->join('users', 'template_task_user.user_id', '=', 'users.id')
            ->leftJoin('projects', 'template_task_user.project_id', '=', 'projects.id')
            ->select(
                'template_task_user.id',
                'template_task_user.user_id',
                'template_tasks.name as task_name',
                'template_task_user.status',
                'users.name as user_name',
                'projects.name as project_name',
                'template_task_user.updated_at as last_updated'
            );

        if (!$isAdmin) {
            $recentTemplateTasksQuery->where('template_task_user.user_id', $user->id);
        }

        // تطبيق فلاتر التاريخ على آخر مهام القوالب
        if ($dateFilters['has_filter']) {
            $this->dateFilterService->applyDateFilter(
                $recentTemplateTasksQuery,
                'template_task_user.updated_at',
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }

        $recentTemplateTasks = $recentTemplateTasksQuery
            ->orderBy('template_task_user.updated_at', 'desc')
            ->take(24)
            ->get();

        // ✅ دمج المهام في PHP وإضافة نوع المهمة
        $combinedTasks = collect();

        // إضافة المهام العادية
        foreach ($recentRegularTasks as $task) {
            $task->task_type = 'regular';
            $combinedTasks->push($task);
        }

        // إضافة مهام القوالب
        foreach ($recentTemplateTasks as $task) {
            $task->task_type = 'template';
            $combinedTasks->push($task);
        }

        // ترتيب وأخذ أول 12 مهمة
        $recentTasks = $combinedTasks
            ->sortByDesc('last_updated')
            ->take(12)
            ->values();

        // ✅ آخر المشاريع المحدثة
        $recentProjectsQuery = Project::with(['client', 'participants'])
            ->select('projects.*');

        if (!$isAdmin) {
            $userProjectIds = DB::table('project_service_user')
                ->where('user_id', $user->id)
                ->pluck('project_id')
                ->toArray();
            $recentProjectsQuery->whereIn('id', $userProjectIds);
        }

        // تطبيق فلاتر التاريخ على آخر المشاريع
        if ($dateFilters['has_filter']) {
            $this->dateFilterService->applyDateFilter(
                $recentProjectsQuery,
                'projects.updated_at',
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }

        $recentProjects = $recentProjectsQuery
            ->orderBy('updated_at', 'desc')
            ->take(12)
            ->get();

        // ✅ آخر الاجتماعات
        $recentMeetingsQuery = Meeting::with(['creator', 'client', 'participants']);

        if (!$isAdmin) {
            $recentMeetingsQuery->where(function($query) use ($user) {
                $query->where('created_by', $user->id)
                      ->orWhereHas('participants', function($subQuery) use ($user) {
                          $subQuery->where('user_id', $user->id);
                      });
            });
        }

        // تطبيق فلاتر التاريخ على آخر الاجتماعات
        if ($dateFilters['has_filter']) {
            $this->dateFilterService->applyDateFilter(
                $recentMeetingsQuery,
                'meetings.created_at',
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }

        $recentMeetings = $recentMeetingsQuery
            ->orderBy('created_at', 'desc')
            ->take(12)
            ->get();

        return view('projects.dashboard', compact(
            'totalTasks',
            'newTasks',
            'inProgressTasks',
            'pausedTasks',
            'completedTasks',
            'totalTemplateTasks',
            'newTemplateTasks',
            'inProgressTemplateTasks',
            'pausedTemplateTasks',
            'completedTemplateTasks',
            'allTotalTasks',
            'allNewTasks',
            'allInProgressTasks',
            'allPausedTasks',
            'allCompletedTasks',
            'allOriginalTasks',
            'allAdditionalTasks',
            'combinedTaskStats',
            'latestProjects',
            'activeProjects',
            'overdueProjects',
            'serviceStats',
            'topEmployees',
            'employees',
            'departments',
            'services',
            'isAdmin',
            'kanbanProjects',
            'todayMeetings',
            'departmentsData',
            'revisionStats',
            'latestRevisions',
            'pendingRevisions',
            'urgentRevisions',
            'revisionsByType',
            'revisionsByCategory',
            'revisionsByStatus',
            'attachmentStats',
            'averageReviewTime',
            'dateFilters',
            'periodDescription',
            'globalTransferStats',
            'globalRevisionTransferStats',
            'globalErrorStats',
            'dashboardProjectOverdueStats',
            'dashboardTaskOverdueStats',
            'dashboardTaskOverdueDetails',
            'recentTasks',
            'recentProjects',
            'recentMeetings'
        ) + $projectStats);
    }

    /**
     * عرض مهام موظف في مشروع معين
     */
    public function employeeProjectTasks(Request $request, $userId, $projectId)
    {
        $employee = User::findOrFail($userId);
        $project = Project::with('client')->findOrFail($projectId);

        // تسجيل النشاط - عرض مهام موظف في مشروع
        if (\Illuminate\Support\Facades\Auth::check()) {
            activity()
                ->performedOn($project)
                ->causedBy(\Illuminate\Support\Facades\Auth::user())
                ->withProperties([
                    'employee_id' => $userId,
                    'employee_name' => $employee->name,
                    'project_id' => $projectId,
                    'project_name' => $project->name,
                    'action_type' => 'view_employee_project_tasks',
                    'viewed_at' => now()->toDateTimeString(),
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip()
                ])
                ->log('شاهد مهام الموظف في المشروع');
        }

        $tasks = Task::with(['service'])
            ->where('project_id', $projectId)
            ->whereHas('users', function ($query) use ($userId) {
                $query->where('users.id', $userId);
            })
            ->get();

        foreach ($tasks as $task) {
            $taskUser = DB::table('task_users')
                ->where('task_id', $task->id)
                ->where('user_id', $userId)
                ->first();

            if ($taskUser) {
                $task->role = $taskUser->role;
                $task->user_status = $taskUser->status;
                $task->user_estimated_hours = $taskUser->estimated_hours;
                $task->user_estimated_minutes = $taskUser->estimated_minutes;
                $task->user_actual_hours = $taskUser->actual_hours;
                $task->user_actual_minutes = $taskUser->actual_minutes;
            }
        }

        return view('projects.employee-project-tasks', compact('employee', 'project', 'tasks'));
    }

    /**
     * الحصول على أعضاء فريق القسم (AJAX)
     */
    public function getDepartmentTeam(Request $request)
    {
        $department = $request->get('department');

        if (!$department) {
            return response()->json(['error' => 'Department not specified'], 400);
        }

        $teamMembers = $this->departmentService->getDepartmentTeamMembers($department);

        return response()->json([
            'department' => $department,
            'team_members' => $teamMembers,
            'total_members' => $teamMembers->count()
        ]);
    }

    /**
     * عرض تفاصيل قسم معين
     */
    public function showDepartment(Request $request, $department)
    {
        $department = urldecode($department);
        $user = Auth::user();
        $userHierarchyLevel = \App\Models\RoleHierarchy::getUserMaxHierarchyLevel($user);

        // Level 1 & 2 لا يمكنهم الوصول
        if ($userHierarchyLevel < 3) {
            abort(403, 'غير مسموح لك بالوصول إلى هذه الصفحة');
        }

        // Level 3 لا يمكنه الوصول لصفحة القسم
        if ($userHierarchyLevel == 3) {
            abort(403, 'غير مسموح لك بالوصول إلى صفحة القسم. يمكنك فقط الوصول لصفحة فريقك');
        }

        // Level 4 يصل فقط لقسمه
        if ($userHierarchyLevel == 4) {
            if ($user->department != $department) {
                abort(403, 'يمكنك فقط الوصول لصفحة قسمك');
            }
        }

        // Level 5+ يصل لكل الأقسام

        // معالجة فلاتر التاريخ
        $dateFilters = $this->dateFilterService->processDateFilters($request);

        // تسجيل النشاط - عرض تفاصيل القسم
        if (\Illuminate\Support\Facades\Auth::check()) {
            activity()
                ->causedBy(\Illuminate\Support\Facades\Auth::user())
                ->withProperties([
                    'department_name' => $department,
                    'action_type' => 'view_department',
                    'date_filters' => $dateFilters,
                    'viewed_at' => now()->toDateTimeString(),
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip()
                ])
                ->log('شاهد تفاصيل القسم');
        }

        $departmentDetails = $this->departmentService->getDepartmentDetails($department, $dateFilters);

        if (!$departmentDetails) {
            abort(404, 'القسم غير موجود');
        }

        $departmentData = $departmentDetails['department_data'];
        $departmentProjects = $departmentDetails['department_projects'];
        $taskStats = $departmentDetails['task_stats'];
        $timeStats = $departmentDetails['time_stats'];
        $teams = $departmentDetails['teams'];

        // الحصول على معرفات مستخدمي القسم للتفرقة بين المهام الأصلية والإضافية
        $departmentUserIds = $this->departmentService->getDepartmentUserIds($department);

        $regularTasksWithAdditional = $this->taskStatsService->getRegularTaskStatsWithAdditional($departmentUserIds, $dateFilters);
        $templateTasksWithAdditional = $this->taskStatsService->getTemplateTaskStatsWithAdditional($departmentUserIds, $dateFilters);

        $combinedTaskStats = $this->taskStatsService->combineTaskStatsWithAdditional(
            $regularTasksWithAdditional,
            $templateTasksWithAdditional
        );

        // حساب إحصائيات المشاريع
        $projectStats = $this->projectStatsService->calculateProjectStats($departmentProjects);

        // الحصول على معرفات مستخدمي القسم
        $departmentUserIds = $this->departmentService->getDepartmentUserIds($department);

        // المشاريع المتأخرة
        $overdueProjects = $this->projectStatsService->filterOverdueProjects($departmentProjects);

        // إحصائيات تأخير المشاريع (مع تمرير معرفات المستخدمين)
        $projectOverdueStats = $this->projectStatsService->calculateOverdueProjectStats($departmentProjects, $departmentUserIds);

        // إحصائيات تأخير المهام
        $taskOverdueStats = $this->taskStatsService->getOverdueTasksStatsByDepartment($department, $dateFilters);

        // معدلات الإنجاز
        $completedTasks = $taskStats['combined']['completed'];
        $totalTasks = $taskStats['combined']['total'];
        $completionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

        // إضافة إحصائيات الأداء للفرق
        $teams = $this->teamService->addPerformanceStatsToTeams($teams);

        // إحصائيات التعديلات للقسم (مقسمة حسب النوع: مهام/مشاريع)
        $revisionStats = $this->revisionStatsService->getGeneralRevisionStats(false, $departmentUserIds, $dateFilters);
        $revisionsByCategory = $this->revisionStatsService->getRevisionsByCategory(null, false, $departmentUserIds, $dateFilters);
        $latestRevisions = $this->revisionStatsService->getLatestRevisions(false, $departmentUserIds, $dateFilters);
        $pendingRevisions = $this->revisionStatsService->getPendingRevisions(false, $departmentUserIds, $dateFilters);
        $urgentRevisions = $this->revisionStatsService->getUrgentRevisions(false, $departmentUserIds, $dateFilters);
        $departmentAttachmentStats = $this->revisionStatsService->getAttachmentStats(false, $departmentUserIds, $dateFilters);

        // إحصائيات نقل المهام للقسم
        $departmentTransferStats = $this->getDepartmentTransferStatistics($department, $dateFilters);

        // إحصائيات نقل التعديلات للقسم
        $departmentRevisionTransferStats = $this->revisionStatsService->getRevisionTransferStats($departmentUserIds, $dateFilters);

        // إحصائيات الأخطاء للقسم
        $departmentErrorStats = $this->errorStatsService->getGroupErrorStats($departmentUserIds, $dateFilters);

        // إضافة وصف الفترة الزمنية
        $periodDescription = $this->dateFilterService->getPeriodDescription($dateFilters);

        // ✅ آخر المهام العادية من task_users للقسم
        $recentRegularTasksQuery = DB::table('task_users')
            ->join('tasks', 'task_users.task_id', '=', 'tasks.id')
            ->join('users', 'task_users.user_id', '=', 'users.id')
            ->leftJoin('projects', 'tasks.project_id', '=', 'projects.id')
            ->whereIn('task_users.user_id', $departmentUserIds)
            ->select(
                'task_users.id',
                'task_users.user_id',
                'tasks.name as task_name',
                'task_users.status',
                'users.name as user_name',
                'projects.name as project_name',
                'task_users.updated_at as last_updated',
            );

        // تطبيق فلاتر التاريخ على آخر المهام العادية
        if ($dateFilters['has_filter']) {
            $this->dateFilterService->applyDateFilter(
                $recentRegularTasksQuery,
                'task_users.updated_at',
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }

        // ✅ آخر مهام القوالب من template_task_user للقسم
        $recentTemplateTasksQuery = DB::table('template_task_user')
            ->join('template_tasks', 'template_task_user.template_task_id', '=', 'template_tasks.id')
            ->join('users', 'template_task_user.user_id', '=', 'users.id')
            ->leftJoin('projects', 'template_task_user.project_id', '=', 'projects.id')
            ->whereIn('template_task_user.user_id', $departmentUserIds)
            ->select(
                'template_task_user.id',
                'template_task_user.user_id',
                'template_tasks.name as task_name',
                'template_task_user.status',
                'users.name as user_name',
                'projects.name as project_name',
                'template_task_user.updated_at as last_updated',
            );

        // تطبيق فلاتر التاريخ على آخر مهام القوالب
        if ($dateFilters['has_filter']) {
            $this->dateFilterService->applyDateFilter(
                $recentTemplateTasksQuery,
                'template_task_user.updated_at',
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }

        $recentRegularTasks = $recentRegularTasksQuery
            ->orderBy('task_users.updated_at', 'desc')
            ->take(24)
            ->get();

        $recentTemplateTasks = $recentTemplateTasksQuery
            ->orderBy('template_task_user.updated_at', 'desc')
            ->take(24)
            ->get();

        // ✅ دمج المهام في PHP وإضافة نوع المهمة
        $combinedTasks = collect();

        // إضافة المهام العادية
        foreach ($recentRegularTasks as $task) {
            $task->task_type = 'regular';
            $combinedTasks->push($task);
        }

        // إضافة مهام القوالب
        foreach ($recentTemplateTasks as $task) {
            $task->task_type = 'template';
            $combinedTasks->push($task);
        }

        // ترتيب وأخذ أول 12 مهمة
        $recentTasks = $combinedTasks
            ->sortByDesc('last_updated')
            ->take(12)
            ->values();


        $departmentProjectIds = DB::table('project_service_user')
            ->whereIn('user_id', $departmentUserIds)
            ->pluck('project_id')
            ->toArray();

        $recentProjectsQuery = Project::with(['client', 'participants'])
            ->whereIn('id', $departmentProjectIds)
            ->select('projects.*');

        // تطبيق فلاتر التاريخ على آخر المشاريع
        if ($dateFilters['has_filter']) {
            $this->dateFilterService->applyDateFilter(
                $recentProjectsQuery,
                'projects.updated_at',
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }

        $recentProjects = $recentProjectsQuery
            ->orderBy('updated_at', 'desc')
            ->take(12)
            ->get();

        // ✅ آخر الاجتماعات للقسم
        $recentMeetingsQuery = Meeting::with(['creator', 'client', 'participants'])
            ->where(function($query) use ($departmentUserIds) {
                $query->whereIn('created_by', $departmentUserIds)
                      ->orWhereHas('participants', function($subQuery) use ($departmentUserIds) {
                          $subQuery->whereIn('user_id', $departmentUserIds);
                      });
            });

        // تطبيق فلاتر التاريخ على آخر الاجتماعات
        if ($dateFilters['has_filter']) {
            $this->dateFilterService->applyDateFilter(
                $recentMeetingsQuery,
                'meetings.created_at',
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }

        $recentMeetings = $recentMeetingsQuery
            ->orderBy('created_at', 'desc')
            ->take(12)
            ->get();

        return view('projects.departments.show', compact(
            'department',
            'departmentData',
            'projectStats',
            'overdueProjects',
            'projectOverdueStats',
            'taskOverdueStats',
            'taskStats',
            'combinedTaskStats',
            'completedTasks',
            'totalTasks',
            'completionRate',
            'timeStats',
            'teams',
            'revisionStats',
            'revisionsByCategory',
            'latestRevisions',
            'pendingRevisions',
            'urgentRevisions',
            'departmentAttachmentStats',
            'departmentTransferStats',
            'departmentRevisionTransferStats',
            'departmentErrorStats',
            'dateFilters',
            'periodDescription',
            'recentTasks',
            'recentProjects',
            'recentMeetings'
        ));
    }

    /**
     * عرض تفاصيل فريق معين
     */
    public function showTeam(Request $request, $department, $teamId)
    {
        $department = urldecode($department);
        $user = Auth::user();
        $userHierarchyLevel = \App\Models\RoleHierarchy::getUserMaxHierarchyLevel($user);

        // Level 1 & 2 لا يمكنهم الوصول
        if ($userHierarchyLevel < 3) {
            abort(403, 'غير مسموح لك بالوصول إلى هذه الصفحة');
        }

        // Level 3 يصل فقط لفريقه
        if ($userHierarchyLevel == 3) {
            $userTeam = DB::table('teams')->where('user_id', $user->id)->first();
            if (!$userTeam || $userTeam->id != $teamId) {
                abort(403, 'يمكنك فقط الوصول لصفحة فريقك');
            }
        }

        // Level 4 يصل لكل الفرق في قسمه فقط
        if ($userHierarchyLevel == 4) {
            if ($user->department != $department) {
                abort(403, 'يمكنك فقط الوصول للفرق في قسمك');
            }
        }

        // Level 5+ يصل لكل الفرق

        // معالجة فلاتر التاريخ
        $dateFilters = $this->dateFilterService->processDateFilters($request);

        // تسجيل النشاط - عرض تفاصيل الفريق
        if (\Illuminate\Support\Facades\Auth::check()) {
            activity()
                ->causedBy(\Illuminate\Support\Facades\Auth::user())
                ->withProperties([
                    'department_name' => $department,
                    'team_id' => $teamId,
                    'action_type' => 'view_team',
                    'viewed_at' => now()->toDateTimeString(),
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip()
                ])
                ->log('شاهد تفاصيل الفريق');
        }

        $teamDetails = $this->teamService->getTeamDetails($teamId, $department, $dateFilters);

        if (!$teamDetails) {
            abort(404, 'الفريق غير موجود في هذا القسم');
        }

        $team = $teamDetails['team'];
        $teamMembers = $teamDetails['team_members'];
        $teamProjects = $teamDetails['team_projects'];
        $taskStats = $teamDetails['task_stats'];
        $timeStats = $teamDetails['time_stats'];

        // الحصول على معرفات أعضاء الفريق
        $teamUserIds = $this->teamService->getTeamUserIds($teamId);

        // التفرقة بين المهام الأصلية والإضافية
        $regularTasksWithAdditional = $this->taskStatsService->getRegularTaskStatsWithAdditional($teamUserIds, $dateFilters);
        $templateTasksWithAdditional = $this->taskStatsService->getTemplateTaskStatsWithAdditional($teamUserIds, $dateFilters);

        $combinedTaskStats = $this->taskStatsService->combineTaskStatsWithAdditional(
            $regularTasksWithAdditional,
            $templateTasksWithAdditional
        );

        // حساب إحصائيات المشاريع مع نسب المشاركة
        $projectStats = $this->teamService->calculateProjectStatsFromCollection($teamProjects, $teamUserIds, $dateFilters);

        // المشاريع المتأخرة
        $overdueProjects = $this->projectStatsService->filterOverdueProjects($teamProjects);

        // إحصائيات تأخير المشاريع (مع تمرير معرفات المستخدمين)
        $projectOverdueStats = $this->projectStatsService->calculateOverdueProjectStats($teamProjects, $teamUserIds);

        // إحصائيات تأخير المهام
        $taskOverdueStats = $this->taskStatsService->getOverdueTasksStats($teamUserIds, $dateFilters);

        // تفاصيل المهام المتأخرة مع أسماء المستخدمين
        $taskOverdueDetails = $this->taskStatsService->getOverdueTasksDetails($teamUserIds, $dateFilters, 15);

        // إحصائيات التعديلات للفريق (مقسمة حسب النوع: مهام/مشاريع)
        $revisionStats = $this->revisionStatsService->getGeneralRevisionStats(false, $teamUserIds, $dateFilters);
        $revisionsByCategory = $this->revisionStatsService->getRevisionsByCategory(null, false, $teamUserIds, $dateFilters);
        $latestRevisions = $this->revisionStatsService->getLatestRevisions(false, $teamUserIds, $dateFilters);
        $pendingRevisions = $this->revisionStatsService->getPendingRevisions(false, $teamUserIds, $dateFilters);
        $urgentRevisions = $this->revisionStatsService->getUrgentRevisions(false, $teamUserIds, $dateFilters);
        $teamAttachmentStats = $this->revisionStatsService->getAttachmentStats(false, $teamUserIds, $dateFilters);
        $averageReviewTime = $this->revisionStatsService->getAverageReviewTime();

        // إحصائيات نقل المهام للفريق
        $teamTransferStats = $this->getTeamTransferStatistics($teamUserIds, $dateFilters);

        // إحصائيات نقل التعديلات للفريق
        $teamRevisionTransferStats = $this->revisionStatsService->getRevisionTransferStats($teamUserIds, $dateFilters);

        // إحصائيات الأخطاء للفريق
        $teamErrorStats = $this->errorStatsService->getGroupErrorStats($teamUserIds, $dateFilters);

        // إضافة وصف الفترة الزمنية
        $periodDescription = $this->dateFilterService->getPeriodDescription($dateFilters);

        // ✅ آخر المهام العادية من task_users للفريق
        $recentRegularTasksQuery = DB::table('task_users')
            ->join('tasks', 'task_users.task_id', '=', 'tasks.id')
            ->join('users', 'task_users.user_id', '=', 'users.id')
            ->leftJoin('projects', 'tasks.project_id', '=', 'projects.id')
            ->whereIn('task_users.user_id', $teamUserIds)
            ->select(
                'task_users.id',
                'task_users.user_id',
                'tasks.name as task_name',
                'task_users.status',
                'users.name as user_name',
                'projects.name as project_name',
                'task_users.updated_at as last_updated'
            );

        // تطبيق فلاتر التاريخ على آخر المهام العادية
        if ($dateFilters['has_filter']) {
            $this->dateFilterService->applyDateFilter(
                $recentRegularTasksQuery,
                'task_users.updated_at',
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }

        $recentRegularTasks = $recentRegularTasksQuery
            ->orderBy('task_users.updated_at', 'desc')
            ->take(24)
            ->get();

        // ✅ آخر مهام القوالب من template_task_user للفريق
        $recentTemplateTasksQuery = DB::table('template_task_user')
            ->join('template_tasks', 'template_task_user.template_task_id', '=', 'template_tasks.id')
            ->join('users', 'template_task_user.user_id', '=', 'users.id')
            ->leftJoin('projects', 'template_task_user.project_id', '=', 'projects.id')
            ->whereIn('template_task_user.user_id', $teamUserIds)
            ->select(
                'template_task_user.id',
                'template_task_user.user_id',
                'template_tasks.name as task_name',
                'template_task_user.status',
                'users.name as user_name',
                'projects.name as project_name',
                'template_task_user.updated_at as last_updated'
            );

        // تطبيق فلاتر التاريخ على آخر مهام القوالب
        if ($dateFilters['has_filter']) {
            $this->dateFilterService->applyDateFilter(
                $recentTemplateTasksQuery,
                'template_task_user.updated_at',
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }

        $recentTemplateTasks = $recentTemplateTasksQuery
            ->orderBy('template_task_user.updated_at', 'desc')
            ->take(24)
            ->get();

        // ✅ دمج المهام في PHP وإضافة نوع المهمة
        $combinedTasks = collect();

        // إضافة المهام العادية
        foreach ($recentRegularTasks as $task) {
            $task->task_type = 'regular';
            $combinedTasks->push($task);
        }

        // إضافة مهام القوالب
        foreach ($recentTemplateTasks as $task) {
            $task->task_type = 'template';
            $combinedTasks->push($task);
        }

        // ترتيب وأخذ أول 12 مهمة
        $recentTasks = $combinedTasks
            ->sortByDesc('last_updated')
            ->take(12)
            ->values();

        // ✅ آخر المشاريع المحدثة للفريق
        $teamProjectIds = DB::table('project_service_user')
            ->whereIn('user_id', $teamUserIds)
            ->pluck('project_id')
            ->toArray();

        $recentProjectsQuery = Project::with(['client', 'participants'])
            ->whereIn('id', $teamProjectIds)
            ->select('projects.*');

        // تطبيق فلاتر التاريخ على آخر المشاريع
        if ($dateFilters['has_filter']) {
            $this->dateFilterService->applyDateFilter(
                $recentProjectsQuery,
                'projects.updated_at',
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }

        $recentProjects = $recentProjectsQuery
            ->orderBy('updated_at', 'desc')
            ->take(12)
            ->get();

        // ✅ آخر الاجتماعات للفريق
        $recentMeetingsQuery = Meeting::with(['creator', 'client', 'participants'])
            ->where(function($query) use ($teamUserIds) {
                $query->whereIn('created_by', $teamUserIds)
                      ->orWhereHas('participants', function($subQuery) use ($teamUserIds) {
                          $subQuery->whereIn('user_id', $teamUserIds);
                      });
            });

        // تطبيق فلاتر التاريخ على آخر الاجتماعات
        if ($dateFilters['has_filter']) {
            $this->dateFilterService->applyDateFilter(
                $recentMeetingsQuery,
                'meetings.created_at',
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }

        $recentMeetings = $recentMeetingsQuery
            ->orderBy('created_at', 'desc')
            ->take(12)
            ->get();

        return view('projects.departments.teams.show', compact(
            'department',
            'team',
            'teamMembers',
            'teamProjects',
            'projectStats',
            'overdueProjects',
            'projectOverdueStats',
            'taskOverdueStats',
            'taskOverdueDetails',
            'taskStats',
            'combinedTaskStats',
            'timeStats',
            'revisionStats',
            'revisionsByCategory',
            'latestRevisions',
            'pendingRevisions',
            'urgentRevisions',
            'teamAttachmentStats',
            'averageReviewTime',
            'teamTransferStats',
            'teamRevisionTransferStats',
            'teamErrorStats',
            'dateFilters',
            'periodDescription',
            'recentTasks',
            'recentProjects',
            'recentMeetings'
        ));
    }

    /**
     * عرض تقرير أداء موظف معين
     */
    public function showEmployeePerformance(Request $request, $userId)
    {
        $currentUser = Auth::user();
        $userHierarchyLevel = \App\Models\RoleHierarchy::getUserMaxHierarchyLevel($currentUser);
        $targetEmployee = User::findOrFail($userId);

        // Level 1 & 2 لا يمكنهم الوصول
        if ($userHierarchyLevel < 3) {
            abort(403, 'غير مسموح لك بالوصول إلى هذه الصفحة');
        }

        // Level 3 يصل فقط للموظفين في فريقه
        if ($userHierarchyLevel == 3) {
            $userTeam = DB::table('teams')->where('user_id', $currentUser->id)->first();
            if ($userTeam) {
                // التحقق من أن الموظف المستهدف في نفس الفريق
                $targetUserInTeam = DB::table('team_user')
                    ->where('team_id', $userTeam->id)
                    ->where('user_id', $userId)
                    ->exists();

                $targetIsTeamOwner = DB::table('teams')
                    ->where('id', $userTeam->id)
                    ->where('user_id', $userId)
                    ->exists();

                if (!$targetUserInTeam && !$targetIsTeamOwner && $userId != $currentUser->id) {
                    abort(403, 'يمكنك فقط الوصول لأداء الموظفين في فريقك');
                }
            }
        }

        // Level 4 يصل فقط للموظفين في قسمه
        if ($userHierarchyLevel == 4) {
            if ($currentUser->department != $targetEmployee->department) {
                abort(403, 'يمكنك فقط الوصول لأداء الموظفين في قسمك');
            }
        }

        // Level 5+ يصل لكل الموظفين

        // معالجة فلاتر التاريخ
        $dateFilters = $this->dateFilterService->processDateFilters($request);

        // تسجيل النشاط - عرض تقرير أداء الموظف
        if (\Illuminate\Support\Facades\Auth::check()) {
            $employee = User::find($userId);
            activity()
                ->performedOn($employee)
                ->causedBy(\Illuminate\Support\Facades\Auth::user())
                ->withProperties([
                    'employee_id' => $userId,
                    'employee_name' => $employee ? $employee->name : null,
                    'action_type' => 'view_employee_performance',
                    'date_filters' => $dateFilters,
                    'viewed_at' => now()->toDateTimeString(),
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip()
                ])
                ->log('شاهد تقرير أداء الموظف');
        }

        $performanceReport = $this->employeePerformanceService->getEmployeePerformanceReport($userId, $dateFilters);

        $employee = $performanceReport['employee'];
        $employeeProjects = $performanceReport['employee_projects'];
        $projectStats = $performanceReport['project_stats'];
        $totalProjectShare = $performanceReport['total_project_share'] ?? $projectStats['total']; // نسبة المشاركة الكلية
        $projectCompletionRate = $performanceReport['project_completion_rate'];
        $overdueProjects = $performanceReport['overdue_projects'];
        $taskStats = $performanceReport['task_stats'];
        $timeStats = $performanceReport['time_stats'];
        $recentTasks = $performanceReport['recent_tasks'];
        $monthlyStats = $performanceReport['monthly_stats'];
        $transferStats = $performanceReport['transfer_stats'];

        // إحصائيات تأخير المشاريع للموظف
        $projectOverdueStats = $this->projectStatsService->calculateOverdueProjectStats($employeeProjects, [$userId]);

        // إحصائيات تأخير المهام للموظف
        $taskOverdueStats = $this->taskStatsService->getOverdueTasksStats($userId, $dateFilters);

        // تفاصيل المهام المتأخرة للموظف
        $taskOverdueDetails = $this->taskStatsService->getOverdueTasksDetails([$userId], $dateFilters, 15);

        // التفرقة بين المهام الأصلية والإضافية
        $regularTasksWithAdditional = $this->taskStatsService->getRegularTaskStatsWithAdditional($userId, $dateFilters);
        $templateTasksWithAdditional = $this->taskStatsService->getTemplateTaskStatsWithAdditional($userId, $dateFilters);

        $combinedTaskStats = $this->taskStatsService->combineTaskStatsWithAdditional(
            $regularTasksWithAdditional,
            $templateTasksWithAdditional
        );

    // ✅ استخدام نسبة الإكمال المحسوبة من الخدمة (تشمل المهام المنقولة)
        $totalTasks = $taskStats['combined']['total'];
        $completedTasks = $taskStats['combined']['completed'];
        $completionRate = $taskStats['combined']['completion_rate'] ?? 0;

        $projectCompletionPoints = $projectStats['completion_points'] ?? 0;
        $effectiveCompletedProjects = $projectStats['effective_completed'] ?? 0;

        $projectCompletionDetails = $this->generateProjectCompletionDetailsForTable($userId, $employeeProjects, $dateFilters);

        $revisionStats = $this->revisionStatsService->getGeneralRevisionStats(false, $userId, $dateFilters);
        $revisionsByCategory = $this->revisionStatsService->getRevisionsByCategory(null, false, $userId, $dateFilters);
        $latestRevisions = $this->revisionStatsService->getLatestRevisions(false, $userId, $dateFilters);
        $pendingRevisions = $this->revisionStatsService->getPendingRevisions(false, $userId, $dateFilters);
        $urgentRevisions = $this->revisionStatsService->getUrgentRevisions(false, $userId, $dateFilters);
        $employeeAttachmentStats = $this->revisionStatsService->getAttachmentStats(false, $userId, $dateFilters);
        $averageReviewTime = $this->revisionStatsService->getAverageReviewTime($dateFilters);

        // إحصائيات التعديلات المنقولة
        $revisionTransferStats = $this->revisionStatsService->getRevisionTransferStats($userId, $dateFilters);

        // إحصائيات الأخطاء
        $employeeErrorStats = $this->errorStatsService->getGroupErrorStats($userId, $dateFilters);

        // إضافة وصف الفترة الزمنية
        $periodDescription = $this->dateFilterService->getPeriodDescription($dateFilters);

        // ✅ آخر المهام العادية من task_users للموظف المحدد
        $recentRegularEmployeeTasksQuery = DB::table('task_users')
            ->join('tasks', 'task_users.task_id', '=', 'tasks.id')
            ->join('users', 'task_users.user_id', '=', 'users.id')
            ->leftJoin('projects', 'tasks.project_id', '=', 'projects.id')
            ->where('task_users.user_id', $userId)
            ->select(
                'task_users.id',
                'task_users.user_id',
                'tasks.name as task_name',
                'task_users.status',
                'users.name as user_name',
                'projects.name as project_name',
                'task_users.updated_at as last_updated'
            );

        // تطبيق فلاتر التاريخ على آخر المهام العادية
        if ($dateFilters['has_filter']) {
            $this->dateFilterService->applyDateFilter(
                $recentRegularEmployeeTasksQuery,
                'task_users.updated_at',
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }

        $recentRegularEmployeeTasks = $recentRegularEmployeeTasksQuery
            ->orderBy('task_users.updated_at', 'desc')
            ->take(24)
            ->get();

        // ✅ آخر مهام القوالب من template_task_user للموظف المحدد
        $recentTemplateEmployeeTasksQuery = DB::table('template_task_user')
            ->join('template_tasks', 'template_task_user.template_task_id', '=', 'template_tasks.id')
            ->join('users', 'template_task_user.user_id', '=', 'users.id')
            ->leftJoin('projects', 'template_task_user.project_id', '=', 'projects.id')
            ->where('template_task_user.user_id', $userId)
            ->select(
                'template_task_user.id',
                'template_task_user.user_id',
                'template_tasks.name as task_name',
                'template_task_user.status',
                'users.name as user_name',
                'projects.name as project_name',
                'template_task_user.updated_at as last_updated'
            );

        // تطبيق فلاتر التاريخ على آخر مهام القوالب
        if ($dateFilters['has_filter']) {
            $this->dateFilterService->applyDateFilter(
                $recentTemplateEmployeeTasksQuery,
                'template_task_user.updated_at',
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }

        $recentTemplateEmployeeTasks = $recentTemplateEmployeeTasksQuery
            ->orderBy('template_task_user.updated_at', 'desc')
            ->take(24)
            ->get();

        // ✅ دمج المهام في PHP وإضافة نوع المهمة
        $combinedEmployeeTasks = collect();

        // إضافة المهام العادية
        foreach ($recentRegularEmployeeTasks as $task) {
            $task->task_type = 'regular';
            $combinedEmployeeTasks->push($task);
        }

        // إضافة مهام القوالب
        foreach ($recentTemplateEmployeeTasks as $task) {
            $task->task_type = 'template';
            $combinedEmployeeTasks->push($task);
        }

        // ترتيب وأخذ أول 12 مهمة
        $recentEmployeeTasks = $combinedEmployeeTasks
            ->sortByDesc('last_updated')
            ->take(12)
            ->values();

        // ✅ آخر المشاريع المحدثة للموظف
        $employeeProjectIds = DB::table('project_service_user')
            ->where('user_id', $userId)
            ->pluck('project_id')
            ->toArray();

        $recentEmployeeProjectsQuery = Project::with(['client', 'participants'])
            ->whereIn('id', $employeeProjectIds)
            ->select('projects.*');

        // تطبيق فلاتر التاريخ على آخر المشاريع
        if ($dateFilters['has_filter']) {
            $this->dateFilterService->applyDateFilter(
                $recentEmployeeProjectsQuery,
                'projects.updated_at',
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }

        $recentEmployeeProjects = $recentEmployeeProjectsQuery
            ->orderBy('updated_at', 'desc')
            ->take(12)
            ->get();

        // ✅ آخر الاجتماعات للموظف
        $recentEmployeeMeetingsQuery = Meeting::with(['creator', 'client', 'participants'])
            ->where(function($query) use ($userId) {
                $query->where('created_by', $userId)
                      ->orWhereHas('participants', function($subQuery) use ($userId) {
                          $subQuery->where('user_id', $userId);
                      });
            });

        // تطبيق فلاتر التاريخ على آخر الاجتماعات
        if ($dateFilters['has_filter']) {
            $this->dateFilterService->applyDateFilter(
                $recentEmployeeMeetingsQuery,
                'meetings.created_at',
                $dateFilters['from_date'],
                $dateFilters['to_date']
            );
        }

        $recentEmployeeMeetings = $recentEmployeeMeetingsQuery
            ->orderBy('created_at', 'desc')
            ->take(12)
            ->get();

        return view('projects.employees.performance', compact(
            'employee',
            'employeeProjects',
            'projectStats',
            'projectCompletionRate',
            'projectCompletionPoints',
            'effectiveCompletedProjects',
            'projectCompletionDetails',
            'overdueProjects',
            'projectOverdueStats',
            'taskOverdueStats',
            'taskOverdueDetails',
            'taskStats',
            'combinedTaskStats',
            'totalTasks',
            'completedTasks',
            'completionRate',
            'timeStats',
            'recentTasks',
            'monthlyStats',
            'transferStats',
            'revisionStats',
            'revisionsByCategory',
            'latestRevisions',
            'pendingRevisions',
            'urgentRevisions',
            'employeeAttachmentStats',
            'averageReviewTime',
            'revisionTransferStats',
            'employeeErrorStats',
            'dateFilters',
            'periodDescription',
            'recentEmployeeTasks',
            'recentEmployeeProjects',
            'recentEmployeeMeetings'
        ));
    }

            /**
     * ✅ توليد تفاصيل المشاريع للعرض في جدول modal
     */
    private function generateProjectCompletionDetailsForTable($userId, $employeeProjects, $dateFilters = null)
    {
        $projectDetails = [];
        $totalPoints = 0;

        foreach ($employeeProjects as $project) {
            // حساب تفاصيل الموظف في هذا المشروع
            $projectCompletion = $this->employeePerformanceService->calculateEmployeeProjectCompletion($userId, $project->id, $dateFilters);

            $completionRate = $projectCompletion['completion_rate'] ?? 0;
            $completedTasks = $projectCompletion['completed_tasks'] ?? 0;
            $totalTasks = $projectCompletion['total_tasks'] ?? 0;
            $transferredTasks = $projectCompletion['transferred_tasks'] ?? 0;
            $currentTasks = $totalTasks - $transferredTasks;

            $projectPoints = $completionRate / 100;
            $totalPoints += $projectPoints;

            // تحديد الحالة والرمز
            $status = 'جديد';
            $statusIcon = '📝';
            $statusClass = 'text-warning';

            if ($transferredTasks > 0 && $completedTasks > 0) {
                $status = 'مكتمل جزئياً';
                $statusIcon = '⚠️';
                $statusClass = 'text-warning';
            } elseif ($completionRate >= 100) {
                $status = 'مكتمل';
                $statusIcon = '✅';
                $statusClass = 'text-success';
            } elseif ($completedTasks > 0) {
                $status = 'قيد التنفيذ';
                $statusIcon = '🔄';
                $statusClass = 'text-info';
            }

            $projectDetails[] = [
                'name' => $project->name ?? "مشروع غير محدد",
                'completed_tasks' => $completedTasks,
                'current_tasks' => $currentTasks,
                'total_tasks' => $totalTasks,
                'transferred_tasks' => $transferredTasks,
                'completion_rate' => $completionRate,
                'points' => $projectPoints,
                'status' => $status,
                'status_icon' => $statusIcon,
                'status_class' => $statusClass
            ];
        }

        return [
            'projects' => $projectDetails,
            'total_points' => $totalPoints,
            'total_projects' => count($projectDetails)
        ];
    }

    /**
     * ✅ إحصائيات نقل المهام العامة
     */
    private function getGlobalTransferStatistics($isAdmin, $userId, $dateFilters)
    {
        // إجمالي المهام المنقولة في النظام
        $totalRegularTransfers = DB::table('task_users')
            ->where('is_transferred', true);

        $totalTemplateTransfers = DB::table('template_task_user')
            ->where('is_transferred', true);

        // المهام الإضافية (المنقولة إلى آخرين)
        $totalAdditionalRegular = DB::table('task_users')
            ->where('is_additional_task', true)
            ->where('task_source', 'transferred');

        $totalAdditionalTemplate = DB::table('template_task_user')
            ->where('is_additional_task', true)
            ->where('task_source', 'transferred');

        // تطبيق فلاتر التاريخ إذا كانت محددة
        if ($dateFilters['has_filter']) {
            $totalRegularTransfers->whereBetween('transferred_at', [$dateFilters['from_date'], $dateFilters['to_date']]);
            $totalTemplateTransfers->whereBetween('transferred_at', [$dateFilters['from_date'], $dateFilters['to_date']]);
            $totalAdditionalRegular->whereBetween('created_at', [$dateFilters['from_date'], $dateFilters['to_date']]);
            $totalAdditionalTemplate->whereBetween('created_at', [$dateFilters['from_date'], $dateFilters['to_date']]);
        }

        // إذا لم يكن admin، فلتر حسب المستخدم
        if (!$isAdmin) {
            $totalRegularTransfers->where('user_id', $userId);
            $totalTemplateTransfers->where('user_id', $userId);
            $totalAdditionalRegular->where('user_id', $userId);
            $totalAdditionalTemplate->where('user_id', $userId);
        }

        $regularTransfersCount = $totalRegularTransfers->count();
        $templateTransfersCount = $totalTemplateTransfers->count();
        $additionalRegularCount = $totalAdditionalRegular->count();
        $additionalTemplateCount = $totalAdditionalTemplate->count();

        // آخر عمليات النقل
        $recentTransfers = $this->getRecentGlobalTransfers($isAdmin, $userId, $dateFilters, 10);

        return [
            'total_transfers' => $regularTransfersCount + $templateTransfersCount,
            'regular_transfers' => $regularTransfersCount,
            'template_transfers' => $templateTransfersCount,
            'additional_tasks' => $additionalRegularCount + $additionalTemplateCount,
            'additional_regular' => $additionalRegularCount,
            'additional_template' => $additionalTemplateCount,
            'recent_transfers' => $recentTransfers,
            'has_transfers' => ($regularTransfersCount + $templateTransfersCount) > 0
        ];
    }

    /**
     * ✅ آخر عمليات النقل في النظام
     */
    private function getRecentGlobalTransfers($isAdmin, $userId, $dateFilters, $limit = 10)
    {
        // ✅ المهام العادية المنقولة مع collation fix
        $regularTransfers = DB::table('task_users as received')
            ->join('tasks', 'received.task_id', '=', 'tasks.id')
            ->leftJoin('task_users as original', 'received.original_task_user_id', '=', 'original.id')
            ->leftJoin('users as from_users', 'original.user_id', '=', 'from_users.id')
            ->join('users as to_users', 'received.user_id', '=', 'to_users.id')
            ->where('received.is_additional_task', true)
            ->where('received.task_source', 'transferred')
            ->whereNotNull('original.transferred_at')
            ->select(
                DB::raw('CAST(tasks.name AS CHAR) COLLATE utf8mb4_unicode_ci as task_name'),
                DB::raw('CAST(COALESCE(from_users.name, "غير محدد") AS CHAR) COLLATE utf8mb4_unicode_ci as from_user_name'),
                DB::raw('CAST(to_users.name AS CHAR) COLLATE utf8mb4_unicode_ci as to_user_name'),
                DB::raw('CAST(COALESCE(original.transfer_reason, "") AS CHAR) COLLATE utf8mb4_unicode_ci as transfer_reason'),
                DB::raw('CAST(COALESCE(original.transfer_type, "positive") AS CHAR) COLLATE utf8mb4_unicode_ci as transfer_type'),
                'original.transferred_at',
                DB::raw('CAST(received.status AS CHAR) COLLATE utf8mb4_unicode_ci as status'),
                DB::raw('CAST("regular" AS CHAR) COLLATE utf8mb4_unicode_ci as task_type')
            );

        // ✅ مهام القوالب المنقولة مع collation fix
        $templateTransfers = DB::table('template_task_user as received')
            ->join('template_tasks', 'received.template_task_id', '=', 'template_tasks.id')
            ->leftJoin('template_task_user as original', 'received.original_template_task_user_id', '=', 'original.id')
            ->leftJoin('users as from_users', 'original.user_id', '=', 'from_users.id')
            ->join('users as to_users', 'received.user_id', '=', 'to_users.id')
            ->where('received.is_additional_task', true)
            ->where('received.task_source', 'transferred')
            ->whereNotNull('original.transferred_at')
            ->select(
                DB::raw('CONVERT(template_tasks.name USING utf8mb4) COLLATE utf8mb4_unicode_ci as task_name'),
                DB::raw('CONVERT(COALESCE(from_users.name, "غير محدد") USING utf8mb4) COLLATE utf8mb4_unicode_ci as from_user_name'),
                DB::raw('CONVERT(to_users.name USING utf8mb4) COLLATE utf8mb4_unicode_ci as to_user_name'),
                DB::raw('CONVERT(COALESCE(original.transfer_reason, "") USING utf8mb4) COLLATE utf8mb4_unicode_ci as transfer_reason'),
                DB::raw('CONVERT(COALESCE(original.transfer_type, "positive") USING utf8mb4) COLLATE utf8mb4_unicode_ci as transfer_type'),
                'original.transferred_at',
                DB::raw('CONVERT(received.status USING utf8mb4) COLLATE utf8mb4_unicode_ci as status'),
                DB::raw('CONVERT("template" USING utf8mb4) COLLATE utf8mb4_unicode_ci as task_type')
            );

        // تطبيق فلاتر التاريخ
        if ($dateFilters['has_filter']) {
            $regularTransfers->whereBetween('original.transferred_at', [$dateFilters['from_date'], $dateFilters['to_date']]);
            $templateTransfers->whereBetween('original.transferred_at', [$dateFilters['from_date'], $dateFilters['to_date']]);
        }

        // إذا لم يكن admin، فلتر حسب المستخدم
        if (!$isAdmin) {
            $regularTransfers->where(function($query) use ($userId) {
                $query->where('received.user_id', $userId)
                      ->orWhere('original.user_id', $userId);
            });
            $templateTransfers->where(function($query) use ($userId) {
                $query->where('received.user_id', $userId)
                      ->orWhere('original.user_id', $userId);
            });
        }

        return $regularTransfers->union($templateTransfers)
            ->orderBy('transferred_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * حساب إحصائيات نقل المهام للفريق
     */
    private function getTeamTransferStatistics($teamUserIds, $dateFilters)
    {
        if (empty($teamUserIds)) {
            return [
                'has_transfers' => false,
                'total_transfers' => 0,
                'regular_transfers' => 0,
                'template_transfers' => 0,
                'recent_transfers' => []
            ];
        }

        // حساب إحصائيات المهام العادية المنقولة
        $regularTransfersQuery = DB::table('task_users as received')
            ->join('tasks', 'received.task_id', '=', 'tasks.id')
            ->leftJoin('task_users as original', 'received.original_task_user_id', '=', 'original.id')
            ->leftJoin('users as from_users', 'original.user_id', '=', 'from_users.id')
            ->join('users as to_users', 'received.user_id', '=', 'to_users.id')
            ->where(function ($query) use ($teamUserIds) {
                $query->whereIn('received.user_id', $teamUserIds)
                      ->orWhereIn('original.user_id', $teamUserIds);
            })
            ->where('received.is_additional_task', 1)
            ->where('received.task_source', 'transferred')
            ->whereNotNull('original.transferred_at');

        // تطبيق فلاتر التاريخ
        if (!empty($dateFilters['start_date']) && !empty($dateFilters['end_date'])) {
            $regularTransfersQuery->whereBetween('original.transferred_at', [
                $dateFilters['start_date'],
                $dateFilters['end_date']
            ]);
        }

        $regularTransfersCount = $regularTransfersQuery->count();

        // حساب إحصائيات مهام القوالب المنقولة
        $templateTransfersQuery = DB::table('template_task_user as received')
            ->join('template_tasks', 'received.template_task_id', '=', 'template_tasks.id')
            ->leftJoin('template_task_user as original', 'received.original_template_task_user_id', '=', 'original.id')
            ->leftJoin('users as from_users', 'original.user_id', '=', 'from_users.id')
            ->join('users as to_users', 'received.user_id', '=', 'to_users.id')
            ->where(function ($query) use ($teamUserIds) {
                $query->whereIn('received.user_id', $teamUserIds)
                      ->orWhereIn('original.user_id', $teamUserIds);
            })
            ->where('received.is_additional_task', 1)
            ->where('received.task_source', 'transferred')
            ->whereNotNull('original.transferred_at');

        // تطبيق فلاتر التاريخ
        if (!empty($dateFilters['start_date']) && !empty($dateFilters['end_date'])) {
            $templateTransfersQuery->whereBetween('original.transferred_at', [
                $dateFilters['start_date'],
                $dateFilters['end_date']
            ]);
        }

        $templateTransfersCount = $templateTransfersQuery->count();

        $totalTransfers = $regularTransfersCount + $templateTransfersCount;

        // جلب آخر عمليات النقل
        $recentTransfers = $this->getRecentTeamTransfers($teamUserIds, $dateFilters, 5);

        return [
            'has_transfers' => $totalTransfers > 0,
            'total_transfers' => $totalTransfers,
            'regular_transfers' => $regularTransfersCount,
            'template_transfers' => $templateTransfersCount,
            'recent_transfers' => $recentTransfers
        ];
    }

    /**
     * حساب إحصائيات نقل المهام للقسم
     */
    private function getDepartmentTransferStatistics($department, $dateFilters)
    {
        // الحصول على معرفات المستخدمين في القسم
        $departmentUserIds = $this->departmentService->getDepartmentUserIds($department);

        if (empty($departmentUserIds)) {
            return [
                'has_transfers' => false,
                'total_transfers' => 0,
                'regular_transfers' => 0,
                'template_transfers' => 0,
                'additional_tasks' => 0,
                'additional_regular' => 0,
                'additional_template' => 0,
                'recent_transfers' => []
            ];
        }

        // إجمالي المهام المنقولة من القسم
        $totalRegularTransfers = DB::table('task_users')
            ->whereIn('user_id', $departmentUserIds)
            ->where('is_transferred', true);

        $totalTemplateTransfers = DB::table('template_task_user')
            ->whereIn('user_id', $departmentUserIds)
            ->where('is_transferred', true);

        // المهام الإضافية المنقولة إلى القسم
        $totalAdditionalRegular = DB::table('task_users')
            ->whereIn('user_id', $departmentUserIds)
            ->where('is_additional_task', true)
            ->where('task_source', 'transferred');

        $totalAdditionalTemplate = DB::table('template_task_user')
            ->whereIn('user_id', $departmentUserIds)
            ->where('is_additional_task', true)
            ->where('task_source', 'transferred');

        // تطبيق فلاتر التاريخ إذا كانت محددة
        if ($dateFilters['has_filter']) {
            $totalRegularTransfers->whereBetween('transferred_at', [$dateFilters['from_date'], $dateFilters['to_date']]);
            $totalTemplateTransfers->whereBetween('transferred_at', [$dateFilters['from_date'], $dateFilters['to_date']]);
            $totalAdditionalRegular->whereBetween('created_at', [$dateFilters['from_date'], $dateFilters['to_date']]);
            $totalAdditionalTemplate->whereBetween('created_at', [$dateFilters['from_date'], $dateFilters['to_date']]);
        }

        $regularTransfersCount = $totalRegularTransfers->count();
        $templateTransfersCount = $totalTemplateTransfers->count();
        $additionalRegularCount = $totalAdditionalRegular->count();
        $additionalTemplateCount = $totalAdditionalTemplate->count();

        // آخر عمليات النقل للقسم
        $recentTransfers = $this->getRecentDepartmentTransfers($departmentUserIds, $dateFilters, 5);

        $totalTransfers = $regularTransfersCount + $templateTransfersCount;
        $totalAdditional = $additionalRegularCount + $additionalTemplateCount;

        return [
            'has_transfers' => ($totalTransfers > 0 || $totalAdditional > 0),
            'total_transfers' => $totalTransfers,
            'regular_transfers' => $regularTransfersCount,
            'template_transfers' => $templateTransfersCount,
            'additional_tasks' => $totalAdditional,
            'additional_regular' => $additionalRegularCount,
            'additional_template' => $additionalTemplateCount,
            'recent_transfers' => $recentTransfers
        ];
    }

    /**
     * الحصول على آخر عمليات النقل للقسم
     */
    private function getRecentDepartmentTransfers($departmentUserIds, $dateFilters, $limit = 10)
    {
        if (empty($departmentUserIds)) {
            return [];
        }

        // المهام العادية المنقولة
        $regularTransfers = DB::table('task_users as received')
            ->join('tasks', 'received.task_id', '=', 'tasks.id')
            ->leftJoin('task_users as original', 'received.original_task_user_id', '=', 'original.id')
            ->leftJoin('users as from_users', 'original.user_id', '=', 'from_users.id')
            ->join('users as to_users', 'received.user_id', '=', 'to_users.id')
            ->where(function($query) use ($departmentUserIds) {
                $query->whereIn('received.user_id', $departmentUserIds)
                      ->orWhereIn('original.user_id', $departmentUserIds);
            })
            ->where('received.is_additional_task', true)
            ->where('received.task_source', 'transferred')
            ->whereNotNull('original.transferred_at');

        // تطبيق فلاتر التاريخ
        if ($dateFilters['has_filter']) {
            $regularTransfers->whereBetween('original.transferred_at', [$dateFilters['from_date'], $dateFilters['to_date']]);
        }

        $regularTransfers->select(
            DB::raw('CAST(tasks.name AS CHAR) COLLATE utf8mb4_unicode_ci as task_name'),
            DB::raw('CAST(COALESCE(from_users.name, "غير محدد") AS CHAR) COLLATE utf8mb4_unicode_ci as from_user_name'),
            DB::raw('CAST(to_users.name AS CHAR) COLLATE utf8mb4_unicode_ci as to_user_name'),
            'original.user_id as from_user_id',
            'received.user_id as to_user_id',
            DB::raw('CAST(COALESCE(original.transfer_reason, "") AS CHAR) COLLATE utf8mb4_unicode_ci as transfer_reason'),
            DB::raw('CAST(COALESCE(original.transfer_type, "positive") AS CHAR) COLLATE utf8mb4_unicode_ci as transfer_type'),
            'original.transferred_at',
            DB::raw('CAST(received.status AS CHAR) COLLATE utf8mb4_unicode_ci as status'),
            DB::raw('CAST("regular" AS CHAR) COLLATE utf8mb4_unicode_ci as task_type')
        );

        // مهام القوالب المنقولة
        $templateTransfers = DB::table('template_task_user as received')
            ->join('template_tasks', 'received.template_task_id', '=', 'template_tasks.id')
            ->leftJoin('template_task_user as original', 'received.original_template_task_user_id', '=', 'original.id')
            ->leftJoin('users as from_users', 'original.user_id', '=', 'from_users.id')
            ->join('users as to_users', 'received.user_id', '=', 'to_users.id')
            ->where(function($query) use ($departmentUserIds) {
                $query->whereIn('received.user_id', $departmentUserIds)
                      ->orWhereIn('original.user_id', $departmentUserIds);
            })
            ->where('received.is_additional_task', true)
            ->where('received.task_source', 'transferred')
            ->whereNotNull('original.transferred_at');

        // تطبيق فلاتر التاريخ
        if ($dateFilters['has_filter']) {
            $templateTransfers->whereBetween('original.transferred_at', [$dateFilters['from_date'], $dateFilters['to_date']]);
        }

        $templateTransfers->select(
            DB::raw('CAST(template_tasks.name AS CHAR) COLLATE utf8mb4_unicode_ci as task_name'),
            DB::raw('CAST(COALESCE(from_users.name, "غير محدد") AS CHAR) COLLATE utf8mb4_unicode_ci as from_user_name'),
            DB::raw('CAST(to_users.name AS CHAR) COLLATE utf8mb4_unicode_ci as to_user_name'),
            'original.user_id as from_user_id',
            'received.user_id as to_user_id',
            DB::raw('CAST(COALESCE(original.transfer_reason, "") AS CHAR) COLLATE utf8mb4_unicode_ci as transfer_reason'),
            DB::raw('CAST(COALESCE(original.transfer_type, "positive") AS CHAR) COLLATE utf8mb4_unicode_ci as transfer_type'),
            'original.transferred_at',
            DB::raw('CAST(received.status AS CHAR) COLLATE utf8mb4_unicode_ci as status'),
            DB::raw('CAST("template" AS CHAR) COLLATE utf8mb4_unicode_ci as task_type')
        );

        return $regularTransfers->union($templateTransfers)
            ->orderBy('transferred_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * جلب آخر عمليات نقل المهام للفريق
     */
    private function getRecentTeamTransfers($teamUserIds, $dateFilters, $limit = 10)
    {
        if (empty($teamUserIds)) {
            return [];
        }

        // إنشاء query للمهام العادية
        $regularTransfers = DB::table('task_users as received')
            ->join('tasks', 'received.task_id', '=', 'tasks.id')
            ->leftJoin('task_users as original', 'received.original_task_user_id', '=', 'original.id')
            ->leftJoin('users as from_users', 'original.user_id', '=', 'from_users.id')
            ->join('users as to_users', 'received.user_id', '=', 'to_users.id')
            ->where(function ($query) use ($teamUserIds) {
                $query->whereIn('received.user_id', $teamUserIds)
                      ->orWhereIn('original.user_id', $teamUserIds);
            })
            ->where('received.is_additional_task', 1)
            ->where('received.task_source', 'transferred')
            ->whereNotNull('original.transferred_at');

        // تطبيق فلاتر التاريخ
        if (!empty($dateFilters['start_date']) && !empty($dateFilters['end_date'])) {
            $regularTransfers->whereBetween('original.transferred_at', [
                $dateFilters['start_date'],
                $dateFilters['end_date']
            ]);
        }

        $regularTransfers->select(
            DB::raw('CAST(tasks.name AS CHAR) COLLATE utf8mb4_unicode_ci as task_name'),
            DB::raw('CAST(COALESCE(from_users.name, "غير محدد") AS CHAR) COLLATE utf8mb4_unicode_ci as from_user_name'),
            DB::raw('CAST(to_users.name AS CHAR) COLLATE utf8mb4_unicode_ci as to_user_name'),
            'original.user_id as from_user_id',
            'received.user_id as to_user_id',
            DB::raw('CAST(COALESCE(original.transfer_reason, "") AS CHAR) COLLATE utf8mb4_unicode_ci as transfer_reason'),
            DB::raw('CAST(COALESCE(original.transfer_type, "positive") AS CHAR) COLLATE utf8mb4_unicode_ci as transfer_type'),
            'original.transferred_at',
            DB::raw('CAST(received.status AS CHAR) COLLATE utf8mb4_unicode_ci as status'),
            DB::raw('CAST("regular" AS CHAR) COLLATE utf8mb4_unicode_ci as task_type')
        );

        // إنشاء query لمهام القوالب
        $templateTransfers = DB::table('template_task_user as received')
            ->join('template_tasks', 'received.template_task_id', '=', 'template_tasks.id')
            ->leftJoin('template_task_user as original', 'received.original_template_task_user_id', '=', 'original.id')
            ->leftJoin('users as from_users', 'original.user_id', '=', 'from_users.id')
            ->join('users as to_users', 'received.user_id', '=', 'to_users.id')
            ->where(function ($query) use ($teamUserIds) {
                $query->whereIn('received.user_id', $teamUserIds)
                      ->orWhereIn('original.user_id', $teamUserIds);
            })
            ->where('received.is_additional_task', 1)
            ->where('received.task_source', 'transferred')
            ->whereNotNull('original.transferred_at');

        // تطبيق فلاتر التاريخ
        if (!empty($dateFilters['start_date']) && !empty($dateFilters['end_date'])) {
            $templateTransfers->whereBetween('original.transferred_at', [
                $dateFilters['start_date'],
                $dateFilters['end_date']
            ]);
        }

        $templateTransfers->select(
            DB::raw('CAST(template_tasks.name AS CHAR) COLLATE utf8mb4_unicode_ci as task_name'),
            DB::raw('CAST(COALESCE(from_users.name, "غير محدد") AS CHAR) COLLATE utf8mb4_unicode_ci as from_user_name'),
            DB::raw('CAST(to_users.name AS CHAR) COLLATE utf8mb4_unicode_ci as to_user_name'),
            'original.user_id as from_user_id',
            'received.user_id as to_user_id',
            DB::raw('CAST(COALESCE(original.transfer_reason, "") AS CHAR) COLLATE utf8mb4_unicode_ci as transfer_reason'),
            DB::raw('CAST(COALESCE(original.transfer_type, "positive") AS CHAR) COLLATE utf8mb4_unicode_ci as transfer_type'),
            'original.transferred_at',
            DB::raw('CAST(received.status AS CHAR) COLLATE utf8mb4_unicode_ci as status'),
            DB::raw('CAST("template" AS CHAR) COLLATE utf8mb4_unicode_ci as task_type')
        );

        return $regularTransfers->union($templateTransfers)
            ->orderBy('transferred_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
