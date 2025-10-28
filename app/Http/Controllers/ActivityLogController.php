<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;
use App\Models\User;
use App\Models\Task;
use App\Models\Project;
// Import للـ models الجديدة المستخدمة في Activity Log
use App\Models\OverTimeRequests;
use App\Models\PermissionRequest;
use App\Models\AbsenceRequest;
use App\Models\Client;
use App\Models\CallLog;
use App\Models\ClientTicket;
use App\Models\EmployeeEvaluation;
use App\Models\KpiEvaluation;
// Models إضافية جديدة
use App\Models\AdditionalTask;
use App\Models\AdditionalTaskUser;
use App\Models\AdministrativeDecision;
use App\Models\AttendanceRecord;
use App\Models\Attendance;
use App\Models\TemplateTask;
use App\Models\TaskUser;
use App\Models\TaskTemplate;
use App\Models\ProjectNote;
use App\Models\Meeting;
use App\Models\FoodAllowance;
use App\Models\GraphicTaskType;
use App\Models\EvaluationDetail;
use App\Models\EvaluationCriteria;
use App\Models\DepartmentRole;
use App\Models\ProjectServiceUser;
use App\Models\RoleEvaluationMapping;
use App\Models\RoleHierarchy;
use App\Models\Skill;
use App\Models\SkillCategory;
use App\Models\SpecialCase;
use App\Models\TaskGraphicType;
use App\Models\Team;
use App\Models\TicketAssignment;
use App\Models\TicketComment;
use App\Models\TicketWorkflowHistory;
use App\Models\Package;
use App\Models\CompanyService;
use App\Models\CriteriaEvaluatorRole;
use App\Models\Comment;
use App\Models\Badge;
use App\Models\BadgeDemotionRule;
use App\Models\AttachmentShare;

class ActivityLogController extends Controller
{
    /**
     * عرض جميع النشاطات - إحصائيات المستخدمين اليومي
     */
    public function index(Request $request)
    {
        // فلتر الأيام السريع
        $daysFilter = $request->input('days', 1); // الديفولت يوم واحد
        $selectedDate = $request->filled('date') ? $request->date : now()->format('Y-m-d');

        // تحديد التاريخ بناءً على فلتر الأيام أو التاريخ المحدد
        if ($request->filled('date')) {
            $dateFrom = $selectedDate;
            $dateTo = $selectedDate;
        } else {
            $dateFrom = now()->subDays($daysFilter - 1)->format('Y-m-d');
            $dateTo = now()->format('Y-m-d');
        }

        // فلترة المستخدمين النشطين فقط (يشمل من لا يملك قيمة أيضاً)
        $usersQuery = User::where(function($query) {
            $query->where('employee_status', 'active')
                  ->orWhereNull('employee_status');
        });

        if ($request->filled('user_id')) {
            $usersQuery->where('id', $request->user_id);
        }

        // الحصول على جميع المستخدمين النشطين مع إحصائياتهم الشاملة
        $users = $usersQuery->select('id', 'name', 'email', 'employee_id', 'profile_photo_path', 'employee_status')
                           ->withCount([
                               'activities as total_activities' => function($query) use ($dateFrom, $dateTo) {
                                   $query->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
                               },
                               // إدارة المشاريع والمهام
                               'activities as tasks_activities' => function($query) use ($dateFrom, $dateTo) {
                                   $query->where('subject_type', Task::class)
                                         ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
                               },
                               'activities as projects_activities' => function($query) use ($dateFrom, $dateTo) {
                                   $query->where('subject_type', Project::class)
                                         ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
                               },
                               // طلبات الموظفين
                               'activities as overtime_activities' => function($query) use ($dateFrom, $dateTo) {
                                   $query->where('subject_type', 'App\\Models\\OverTimeRequests')
                                         ->orWhere('properties->page', 'overtime_requests_index')
                                         ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
                               },
                               'activities as permission_activities' => function($query) use ($dateFrom, $dateTo) {
                                   $query->where('subject_type', 'App\\Models\\PermissionRequest')
                                         ->orWhere('properties->page', 'like', '%permission%')
                                         ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
                               },
                               'activities as absence_activities' => function($query) use ($dateFrom, $dateTo) {
                                   $query->where('subject_type', 'App\\Models\\AbsenceRequest')
                                         ->orWhere('properties->page', 'absence_requests_index')
                                         ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
                               },
                               // إدارة العملاء والمبيعات
                               'activities as clients_activities' => function($query) use ($dateFrom, $dateTo) {
                                   $query->where('subject_type', 'App\\Models\\Client')
                                         ->orWhere('properties->page', 'like', '%client%')
                                         ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
                               },
                               'activities as calls_activities' => function($query) use ($dateFrom, $dateTo) {
                                   $query->where('subject_type', 'App\\Models\\CallLog')
                                         ->orWhere('properties->page', 'call_logs_index')
                                         ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
                               },
                               'activities as tickets_activities' => function($query) use ($dateFrom, $dateTo) {
                                   $query->where('subject_type', 'App\\Models\\ClientTicket')
                                         ->orWhere('properties->page', 'client_tickets_index')
                                         ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
                               },
                               // إدارة الموظفين والتقييمات
                               'activities as employee_activities' => function($query) use ($dateFrom, $dateTo) {
                                   $query->where('properties->page', 'like', '%employee%')
                                         ->orWhere('subject_type', 'App\\Models\\EmployeeEvaluation')
                                         ->orWhere('subject_type', 'App\\Models\\KpiEvaluation')
                                         ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
                               },
                               // أنشطة أخرى (لوحات التحكم، التقارير، الإشعارات، etc.)
                               'activities as dashboard_activities' => function($query) use ($dateFrom, $dateTo) {
                                   $query->where('properties->action_type', 'view_dashboard')
                                         ->orWhere('properties->page', 'like', '%dashboard%')
                                         ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
                               },
                               'activities as reports_activities' => function($query) use ($dateFrom, $dateTo) {
                                   $query->where('properties->page', 'like', '%report%')
                                         ->orWhere('properties->page', 'like', '%statistics%')
                                         ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
                               },
                               // أنشطة المهام الإضافية
                               'activities as additional_tasks_activities' => function($query) use ($dateFrom, $dateTo) {
                                   $query->whereIn('subject_type', [AdditionalTask::class, AdditionalTaskUser::class])
                                         ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
                               },
                               // أنشطة الحضور
                               'activities as attendance_activities' => function($query) use ($dateFrom, $dateTo) {
                                   $query->whereIn('subject_type', [Attendance::class, AttendanceRecord::class, SpecialCase::class])
                                         ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
                               },
                               // أنشطة قوالب المهام
                               'activities as task_templates_activities' => function($query) use ($dateFrom, $dateTo) {
                                   $query->whereIn('subject_type', [TaskTemplate::class, TemplateTask::class, TaskUser::class])
                                         ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
                               },
                               // أنشطة الاجتماعات والملاحظات
                               'activities as meetings_activities' => function($query) use ($dateFrom, $dateTo) {
                                   $query->whereIn('subject_type', [Meeting::class, ProjectNote::class])
                                         ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
                               },
                               // أنشطة التقييمات المتقدمة
                               'activities as advanced_evaluations_activities' => function($query) use ($dateFrom, $dateTo) {
                                   $query->whereIn('subject_type', [EvaluationDetail::class, EvaluationCriteria::class, CriteriaEvaluatorRole::class])
                                         ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
                               },
                               // أنشطة المهارات والأدوار
                               'activities as skills_roles_activities' => function($query) use ($dateFrom, $dateTo) {
                                   $query->whereIn('subject_type', [Skill::class, SkillCategory::class, DepartmentRole::class, RoleHierarchy::class, RoleEvaluationMapping::class])
                                         ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
                               },
                               // أنشطة الخدمات والحزم
                               'activities as services_packages_activities' => function($query) use ($dateFrom, $dateTo) {
                                   $query->whereIn('subject_type', [CompanyService::class, Package::class, ProjectServiceUser::class])
                                         ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
                               },
                               // أنشطة الشارات والمكافآت
                               'activities as badges_rewards_activities' => function($query) use ($dateFrom, $dateTo) {
                                   $query->whereIn('subject_type', [Badge::class, BadgeDemotionRule::class, FoodAllowance::class])
                                         ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
                               },
                               // أنشطة التذاكر المتقدمة
                               'activities as advanced_tickets_activities' => function($query) use ($dateFrom, $dateTo) {
                                   $query->whereIn('subject_type', [TicketAssignment::class, TicketComment::class, TicketWorkflowHistory::class])
                                         ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
                               },
                               // أنشطة المرفقات والفرق
                               'activities as attachments_teams_activities' => function($query) use ($dateFrom, $dateTo) {
                                   $query->whereIn('subject_type', [AttachmentShare::class, Team::class])
                                         ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
                               },
                               // أنشطة اجتماعية وإدارية
                               'activities as social_admin_activities' => function($query) use ($dateFrom, $dateTo) {
                                   $query->whereIn('subject_type', [Comment::class, AdministrativeDecision::class, GraphicTaskType::class, TaskGraphicType::class])
                                         ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
                               }
                           ])
                           ->with(['activities' => function($query) use ($dateFrom, $dateTo) {
                               $query->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                                     ->latest()
                                     ->limit(5); // آخر 5 نشاطات
                           }])
                           ->orderByRaw('total_activities DESC, name ASC')
                           ->get();

        // تحويل البيانات للعرض مع الإحصائيات الشاملة
        $userStats = $users->map(function($user) {
            $hasActivity = $user->total_activities > 0;
            return [
                'user' => $user,
                'total_activities' => $user->total_activities,
                // إدارة المشاريع والمهام
                'tasks_activities' => $user->tasks_activities ?? 0,
                'projects_activities' => $user->projects_activities ?? 0,
                // طلبات الموظفين
                'overtime_activities' => $user->overtime_activities ?? 0,
                'permission_activities' => $user->permission_activities ?? 0,
                'absence_activities' => $user->absence_activities ?? 0,
                // إدارة العملاء والمبيعات
                'clients_activities' => $user->clients_activities ?? 0,
                'calls_activities' => $user->calls_activities ?? 0,
                'tickets_activities' => $user->tickets_activities ?? 0,
                // إدارة الموظفين والتقييمات
                'employee_activities' => $user->employee_activities ?? 0,
                // أنشطة أخرى
                'dashboard_activities' => $user->dashboard_activities ?? 0,
                'reports_activities' => $user->reports_activities ?? 0,
                // الأنشطة الجديدة
                'additional_tasks_activities' => $user->additional_tasks_activities ?? 0,
                'attendance_activities' => $user->attendance_activities ?? 0,
                'task_templates_activities' => $user->task_templates_activities ?? 0,
                'meetings_activities' => $user->meetings_activities ?? 0,
                'advanced_evaluations_activities' => $user->advanced_evaluations_activities ?? 0,
                'skills_roles_activities' => $user->skills_roles_activities ?? 0,
                'services_packages_activities' => $user->services_packages_activities ?? 0,
                'badges_rewards_activities' => $user->badges_rewards_activities ?? 0,
                'advanced_tickets_activities' => $user->advanced_tickets_activities ?? 0,
                'attachments_teams_activities' => $user->attachments_teams_activities ?? 0,
                'social_admin_activities' => $user->social_admin_activities ?? 0,
                // معلومات الحالة
                'recent_activities' => $user->activities, // آخر 5 نشاطات
                'has_activity' => $hasActivity, // هل دخل السيستم أم لا
                'status_class' => $hasActivity ? 'table-success' : 'table-danger' // للتلوين
            ];
        });

        // بيانات للفلترة
        $allUsers = User::where(function($query) {
            $query->where('employee_status', 'active')
                  ->orWhereNull('employee_status');
        })->select('id', 'name')->orderBy('name')->get();
        $daysOptions = [
            1 => 'اليوم',
            3 => 'آخر 3 أيام',
            7 => 'الأسبوع الماضي',
            30 => 'الشهر الماضي'
        ];

        // إحصائيات عامة شاملة
        $totalStats = [
            // إحصائيات المستخدمين
            'total_users' => $users->count(),
            'active_users' => $userStats->where('has_activity', true)->count(),
            'inactive_users' => $userStats->where('has_activity', false)->count(),
            'date_range' => $dateFrom === $dateTo ? $dateFrom : "$dateFrom إلى $dateTo",

            // إحصائيات الأنشطة الإجمالية
            'total_activities' => $userStats->sum('total_activities'),
            'avg_activities_per_user' => $userStats->where('has_activity', true)->count() > 0 ?
                round($userStats->sum('total_activities') / $userStats->where('has_activity', true)->count(), 1) : 0,

            // إحصائيات أنشطة المشاريع والمهام
            'tasks_total' => $userStats->sum('tasks_activities'),
            'projects_total' => $userStats->sum('projects_activities'),

            // إحصائيات طلبات الموظفين
            'overtime_total' => $userStats->sum('overtime_activities'),
            'permission_total' => $userStats->sum('permission_activities'),
            'absence_total' => $userStats->sum('absence_activities'),
            'employee_requests_total' => $userStats->sum('overtime_activities') + $userStats->sum('permission_activities') + $userStats->sum('absence_activities'),

            // إحصائيات العملاء والمبيعات
            'clients_total' => $userStats->sum('clients_activities'),
            'calls_total' => $userStats->sum('calls_activities'),
            'tickets_total' => $userStats->sum('tickets_activities'),
            'crm_total' => $userStats->sum('clients_activities') + $userStats->sum('calls_activities') + $userStats->sum('tickets_activities'),

            // إحصائيات إدارة الموظفين والتقييمات
            'employee_management_total' => $userStats->sum('employee_activities'),

            // إحصائيات أخرى
            'dashboard_total' => $userStats->sum('dashboard_activities'),
            'reports_total' => $userStats->sum('reports_activities'),
            'other_activities_total' => $userStats->sum('dashboard_activities') + $userStats->sum('reports_activities'),

            // إحصائيات الأنشطة الجديدة
            'additional_tasks_total' => $userStats->sum('additional_tasks_activities'),
            'attendance_total' => $userStats->sum('attendance_activities'),
            'task_templates_total' => $userStats->sum('task_templates_activities'),
            'meetings_total' => $userStats->sum('meetings_activities'),
            'advanced_evaluations_total' => $userStats->sum('advanced_evaluations_activities'),
            'skills_roles_total' => $userStats->sum('skills_roles_activities'),
            'services_packages_total' => $userStats->sum('services_packages_activities'),
            'badges_rewards_total' => $userStats->sum('badges_rewards_activities'),
            'advanced_tickets_total' => $userStats->sum('advanced_tickets_activities'),
            'attachments_teams_total' => $userStats->sum('attachments_teams_activities'),
            'social_admin_total' => $userStats->sum('social_admin_activities'),

            // أكثر المستخدمين نشاطاً
            'most_active_user' => $userStats->where('has_activity', true)->sortByDesc('total_activities')->first(),

            // توزيع الأنشطة الشامل
            'activity_distribution' => [
                'projects_and_tasks' => $userStats->sum('tasks_activities') + $userStats->sum('projects_activities') + $userStats->sum('additional_tasks_activities') + $userStats->sum('task_templates_activities'),
                'employee_requests' => $userStats->sum('overtime_activities') + $userStats->sum('permission_activities') + $userStats->sum('absence_activities'),
                'crm_activities' => $userStats->sum('clients_activities') + $userStats->sum('calls_activities') + $userStats->sum('tickets_activities') + $userStats->sum('advanced_tickets_activities'),
                'employee_management' => $userStats->sum('employee_activities') + $userStats->sum('advanced_evaluations_activities') + $userStats->sum('skills_roles_activities'),
                'attendance_management' => $userStats->sum('attendance_activities'),
                'meetings_notes' => $userStats->sum('meetings_activities'),
                'services_packages' => $userStats->sum('services_packages_activities'),
                'badges_rewards' => $userStats->sum('badges_rewards_activities'),
                'attachments_teams' => $userStats->sum('attachments_teams_activities'),
                'social_admin' => $userStats->sum('social_admin_activities'),
                'reports_and_dashboards' => $userStats->sum('dashboard_activities') + $userStats->sum('reports_activities')
            ]
        ];

        return view('activity-log.index', compact(
            'userStats',
            'allUsers',
            'daysOptions',
            'totalStats',
            'daysFilter',
            'selectedDate'
        ));
    }

    /**
     * عرض نشاطات مستخدم معين
     */
    public function userActivities(User $user)
    {
        $activities = Activity::forSubject($user)
                             ->with(['causer', 'subject'])
                             ->latest()
                             ->paginate(20);

        return view('activity-log.user-activities', compact('activities', 'user'));
    }

    /**
     * عرض نشاطات نموذج معين
     */
    public function modelActivities(Request $request)
    {
        $modelType = $request->model_type;
        $modelId = $request->model_id;

        if (!$modelType || !$modelId) {
            return redirect()->back()->with('error', 'نوع النموذج والمعرف مطلوبان');
        }

        $model = $modelType::find($modelId);

        if (!$model) {
            return redirect()->back()->with('error', 'النموذج غير موجود');
        }

        $activities = Activity::forSubject($model)
                             ->with(['causer', 'subject'])
                             ->latest()
                             ->paginate(20);

        return view('activity-log.model-activities', compact('activities', 'model'));
    }

    /**
     * عرض تفاصيل نشاط معين
     */
    public function show(Activity $activity)
    {
        $activity->load(['causer', 'subject']);

        return view('activity-log.show', compact('activity'));
    }

    /**
     * حذف نشاط معين
     */
    public function destroy(Activity $activity)
    {
        $activity->delete();

        return redirect()->back()->with('success', 'تم حذف النشاط بنجاح');
    }

    /**
     * تنظيف النشاطات القديمة
     */
    public function clean(Request $request)
    {
        $days = $request->input('days', 30);

        $deletedCount = Activity::where('created_at', '<', now()->subDays($days))->delete();

        return redirect()->back()->with('success', "تم حذف {$deletedCount} نشاط أقدم من {$days} يوم");
    }

  /**
     * عرض نشاط المستخدم اليومي
     */
    public function userDaily(Request $request)
    {
        $users = User::select('id', 'name', 'email', 'employee_id')->get();
        $selectedUser = null;
        $activities = collect();
        $stats = [
            'total_activities' => 0,
            'tasks_activities' => 0,
            'projects_activities' => 0,
            'unique_items' => 0
        ];

        if ($request->filled('user_id')) {
            $selectedUser = User::find($request->user_id);

            if ($selectedUser) {
                $query = Activity::where('causer_id', $selectedUser->id)
                                ->whereDate('created_at', $request->date ?? now()->format('Y-m-d'))
                                ->with(['subject']);

                // فلترة حسب نوع النشاط
                if ($request->filled('subject_type')) {
                    $query->where('subject_type', $request->subject_type);
                }

                $activities = $query->orderBy('created_at', 'desc')->get();

                // حساب الإحصائيات الشاملة
                $stats['total_activities'] = $activities->count();

                // إدارة المشاريع والمهام
                $stats['tasks_activities'] = $activities->where('subject_type', Task::class)->count();
                $stats['projects_activities'] = $activities->where('subject_type', Project::class)->count();

                // طلبات الموظفين
                $stats['overtime_activities'] = $activities->where('subject_type', 'App\\Models\\OverTimeRequests')->count() +
                    $activities->where('properties.page', 'overtime_requests_index')->count();
                $stats['permission_activities'] = $activities->where('subject_type', 'App\\Models\\PermissionRequest')->count() +
                    $activities->filter(function($activity) { return str_contains($activity->properties['page'] ?? '', 'permission'); })->count();
                $stats['absence_activities'] = $activities->where('subject_type', 'App\\Models\\AbsenceRequest')->count() +
                    $activities->where('properties.page', 'absence_requests_index')->count();

                // إدارة العملاء والمبيعات
                $stats['clients_activities'] = $activities->where('subject_type', 'App\\Models\\Client')->count() +
                    $activities->filter(function($activity) { return str_contains($activity->properties['page'] ?? '', 'client'); })->count();
                $stats['calls_activities'] = $activities->where('subject_type', 'App\\Models\\CallLog')->count() +
                    $activities->where('properties.page', 'call_logs_index')->count();
                $stats['tickets_activities'] = $activities->where('subject_type', 'App\\Models\\ClientTicket')->count() +
                    $activities->where('properties.page', 'client_tickets_index')->count();

                // إدارة الموظفين والتقييمات
                $stats['employee_activities'] = $activities->where('subject_type', 'App\\Models\\EmployeeEvaluation')->count() +
                    $activities->where('subject_type', 'App\\Models\\KpiEvaluation')->count() +
                    $activities->filter(function($activity) { return str_contains($activity->properties['page'] ?? '', 'employee'); })->count();

                // أنشطة أخرى
                $stats['dashboard_activities'] = $activities->where('properties.action_type', 'view_dashboard')->count() +
                    $activities->filter(function($activity) { return str_contains($activity->properties['page'] ?? '', 'dashboard'); })->count();
                $stats['reports_activities'] = $activities->filter(function($activity) {
                    $page = $activity->properties['page'] ?? '';
                    return str_contains($page, 'report') || str_contains($page, 'statistics');
                })->count();

                // الأنشطة الجديدة
                $stats['additional_tasks_activities'] = $activities->whereIn('subject_type', [AdditionalTask::class, AdditionalTaskUser::class])->count();
                $stats['attendance_activities'] = $activities->whereIn('subject_type', [Attendance::class, AttendanceRecord::class, SpecialCase::class])->count();
                $stats['task_templates_activities'] = $activities->whereIn('subject_type', [TaskTemplate::class, TemplateTask::class, TaskUser::class])->count();
                $stats['meetings_activities'] = $activities->whereIn('subject_type', [Meeting::class, ProjectNote::class])->count();
                $stats['advanced_evaluations_activities'] = $activities->whereIn('subject_type', [EvaluationDetail::class, EvaluationCriteria::class, CriteriaEvaluatorRole::class])->count();
                $stats['skills_roles_activities'] = $activities->whereIn('subject_type', [Skill::class, SkillCategory::class, DepartmentRole::class, RoleHierarchy::class, RoleEvaluationMapping::class])->count();
                $stats['services_packages_activities'] = $activities->whereIn('subject_type', [CompanyService::class, Package::class, ProjectServiceUser::class])->count();
                $stats['badges_rewards_activities'] = $activities->whereIn('subject_type', [Badge::class, BadgeDemotionRule::class, FoodAllowance::class])->count();
                $stats['advanced_tickets_activities'] = $activities->whereIn('subject_type', [TicketAssignment::class, TicketComment::class, TicketWorkflowHistory::class])->count();
                $stats['attachments_teams_activities'] = $activities->whereIn('subject_type', [AttachmentShare::class, Team::class])->count();
                $stats['social_admin_activities'] = $activities->whereIn('subject_type', [Comment::class, AdministrativeDecision::class, GraphicTaskType::class, TaskGraphicType::class])->count();

                $stats['unique_items'] = $activities->pluck('subject_id')->unique()->count();

                // تجميع الأنشطة حسب النوع للرسم البياني الشامل
                $stats['activity_breakdown'] = [
                    'المشاريع والمهام' => $stats['tasks_activities'] + $stats['projects_activities'] + $stats['additional_tasks_activities'] + $stats['task_templates_activities'],
                    'طلبات الموظفين' => $stats['overtime_activities'] + $stats['permission_activities'] + $stats['absence_activities'],
                    'إدارة العملاء' => $stats['clients_activities'] + $stats['calls_activities'] + $stats['tickets_activities'] + $stats['advanced_tickets_activities'],
                    'إدارة الموظفين' => $stats['employee_activities'] + $stats['advanced_evaluations_activities'] + $stats['skills_roles_activities'],
                    'الحضور والغياب' => $stats['attendance_activities'],
                    'الاجتماعات والملاحظات' => $stats['meetings_activities'],
                    'الخدمات والحزم' => $stats['services_packages_activities'],
                    'الشارات والمكافآت' => $stats['badges_rewards_activities'],
                    'المرفقات والفرق' => $stats['attachments_teams_activities'],
                    'الاجتماعية والإدارية' => $stats['social_admin_activities'],
                    'التقارير ولوحات التحكم' => $stats['dashboard_activities'] + $stats['reports_activities']
                ];
            }
        }

        return view('activity-log.user-daily', compact('users', 'selectedUser', 'activities', 'stats'));
    }

    /**
     * إحصائيات مفصلة للأنشطة
     */
    public function activityStats(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));

        // إحصائيات مفصلة لكل نوع من الأنشطة
        $activityStats = [
            // إدارة المشاريع والمهام
            'projects_and_tasks' => [
                'total' => Activity::whereIn('subject_type', [Task::class, Project::class])
                    ->orWhere('properties->page', 'like', '%task%')
                    ->orWhere('properties->page', 'like', '%project%')
                    ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                    ->count(),
                'tasks' => Activity::where('subject_type', Task::class)
                    ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                    ->count(),
                'projects' => Activity::where('subject_type', Project::class)
                    ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                    ->count()
            ],

            // طلبات الموظفين
            'employee_requests' => [
                'total' => Activity::where(function($query) {
                        $query->whereIn('subject_type', ['App\\Models\\OverTimeRequests', 'App\\Models\\PermissionRequest', 'App\\Models\\AbsenceRequest'])
                              ->orWhere('properties->page', 'overtime_requests_index')
                              ->orWhere('properties->page', 'absence_requests_index')
                              ->orWhere('properties->page', 'like', '%permission%');
                    })
                    ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                    ->count(),
                'overtime' => Activity::where('subject_type', 'App\\Models\\OverTimeRequests')
                    ->orWhere('properties->page', 'overtime_requests_index')
                    ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                    ->count(),
                'permissions' => Activity::where('subject_type', 'App\\Models\\PermissionRequest')
                    ->orWhere('properties->page', 'like', '%permission%')
                    ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                    ->count(),
                'absences' => Activity::where('subject_type', 'App\\Models\\AbsenceRequest')
                    ->orWhere('properties->page', 'absence_requests_index')
                    ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                    ->count()
            ],

            // إدارة العملاء والمبيعات
            'crm_activities' => [
                'total' => Activity::where(function($query) {
                        $query->whereIn('subject_type', ['App\\Models\\Client', 'App\\Models\\CallLog', 'App\\Models\\ClientTicket'])
                              ->orWhere('properties->page', 'like', '%client%')
                              ->orWhere('properties->page', 'call_logs_index')
                              ->orWhere('properties->page', 'client_tickets_index');
                    })
                    ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                    ->count(),
                'clients' => Activity::where('subject_type', 'App\\Models\\Client')
                    ->orWhere('properties->page', 'like', '%client%')
                    ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                    ->count(),
                'calls' => Activity::where('subject_type', 'App\\Models\\CallLog')
                    ->orWhere('properties->page', 'call_logs_index')
                    ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                    ->count(),
                'tickets' => Activity::where('subject_type', 'App\\Models\\ClientTicket')
                    ->orWhere('properties->page', 'client_tickets_index')
                    ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                    ->count()
            ],

            // إدارة الموظفين والتقييمات
            'employee_management' => [
                'total' => Activity::where(function($query) {
                        $query->whereIn('subject_type', ['App\\Models\\EmployeeEvaluation', 'App\\Models\\KpiEvaluation'])
                              ->orWhere('properties->page', 'like', '%employee%');
                    })
                    ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                    ->count(),
                'evaluations' => Activity::where('subject_type', 'App\\Models\\EmployeeEvaluation')
                    ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                    ->count(),
                'kpi_evaluations' => Activity::where('subject_type', 'App\\Models\\KpiEvaluation')
                    ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                    ->count(),
                'statistics' => Activity::where('properties->page', 'employee_statistics_index')
                    ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                    ->count(),
                'reports' => Activity::where('properties->page', 'employee_reports_index')
                    ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                    ->count()
            ],

            // تقارير ولوحات تحكم
            'reports_and_dashboards' => [
                'total' => Activity::where(function($query) {
                        $query->where('properties->action_type', 'view_dashboard')
                              ->orWhere('properties->page', 'like', '%dashboard%')
                              ->orWhere('properties->page', 'like', '%report%')
                              ->orWhere('properties->page', 'like', '%statistics%');
                    })
                    ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                    ->count(),
                'dashboards' => Activity::where('properties->action_type', 'view_dashboard')
                    ->orWhere('properties->page', 'like', '%dashboard%')
                    ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                    ->count(),
                'reports' => Activity::where('properties->page', 'like', '%report%')
                    ->orWhere('properties->page', 'like', '%statistics%')
                    ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                    ->count()
            ]
        ];

        // إجمالي جميع الأنشطة
        $grandTotal = Activity::whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])->count();

        return response()->json([
            'success' => true,
            'date_range' => [
                'from' => $dateFrom,
                'to' => $dateTo,
                'formatted' => $dateFrom === $dateTo ? $dateFrom : "$dateFrom إلى $dateTo"
            ],
            'grand_total' => $grandTotal,
            'activity_stats' => $activityStats,
            'percentages' => [
                'projects_and_tasks' => $grandTotal > 0 ? round(($activityStats['projects_and_tasks']['total'] / $grandTotal) * 100, 1) : 0,
                'employee_requests' => $grandTotal > 0 ? round(($activityStats['employee_requests']['total'] / $grandTotal) * 100, 1) : 0,
                'crm_activities' => $grandTotal > 0 ? round(($activityStats['crm_activities']['total'] / $grandTotal) * 100, 1) : 0,
                'employee_management' => $grandTotal > 0 ? round(($activityStats['employee_management']['total'] / $grandTotal) * 100, 1) : 0,
                'reports_and_dashboards' => $grandTotal > 0 ? round(($activityStats['reports_and_dashboards']['total'] / $grandTotal) * 100, 1) : 0
            ]
        ]);
    }

    /**
     * البحث في الأنشطة
     */
    public function search(Request $request)
    {
        $query = Activity::with(['causer', 'subject']);

        // فلتر حسب المستخدم
        if ($request->filled('user_id')) {
            $query->where('causer_id', $request->user_id);
        }

        // فلتر حسب نوع النشاط
        if ($request->filled('activity_type')) {
            switch($request->activity_type) {
                case 'tasks':
                    $query->where('subject_type', Task::class);
                    break;
                case 'projects':
                    $query->where('subject_type', Project::class);
                    break;
                case 'overtime':
                    $query->where('subject_type', 'App\\Models\\OverTimeRequests')
                          ->orWhere('properties->page', 'overtime_requests_index');
                    break;
                case 'permissions':
                    $query->where('subject_type', 'App\\Models\\PermissionRequest')
                          ->orWhere('properties->page', 'like', '%permission%');
                    break;
                case 'absences':
                    $query->where('subject_type', 'App\\Models\\AbsenceRequest')
                          ->orWhere('properties->page', 'absence_requests_index');
                    break;
                case 'clients':
                    $query->where('subject_type', 'App\\Models\\Client')
                          ->orWhere('properties->page', 'like', '%client%');
                    break;
                case 'calls':
                    $query->where('subject_type', 'App\\Models\\CallLog')
                          ->orWhere('properties->page', 'call_logs_index');
                    break;
                case 'tickets':
                    $query->where('subject_type', 'App\\Models\\ClientTicket')
                          ->orWhere('properties->page', 'client_tickets_index');
                    break;
                case 'evaluations':
                    $query->whereIn('subject_type', ['App\\Models\\EmployeeEvaluation', 'App\\Models\\KpiEvaluation']);
                    break;
                case 'reports':
                    $query->where('properties->page', 'like', '%report%')
                          ->orWhere('properties->page', 'like', '%statistics%');
                    break;
                case 'dashboards':
                    $query->where('properties->action_type', 'view_dashboard')
                          ->orWhere('properties->page', 'like', '%dashboard%');
                    break;
                // الأنشطة الجديدة
                case 'additional_tasks':
                    $query->whereIn('subject_type', [AdditionalTask::class, AdditionalTaskUser::class]);
                    break;
                case 'attendance':
                    $query->whereIn('subject_type', [Attendance::class, AttendanceRecord::class, SpecialCase::class]);
                    break;
                case 'task_templates':
                    $query->whereIn('subject_type', [TaskTemplate::class, TemplateTask::class, TaskUser::class]);
                    break;
                case 'meetings':
                    $query->whereIn('subject_type', [Meeting::class, ProjectNote::class]);
                    break;
                case 'advanced_evaluations':
                    $query->whereIn('subject_type', [EvaluationDetail::class, EvaluationCriteria::class, CriteriaEvaluatorRole::class]);
                    break;
                case 'skills_roles':
                    $query->whereIn('subject_type', [Skill::class, SkillCategory::class, DepartmentRole::class, RoleHierarchy::class, RoleEvaluationMapping::class]);
                    break;
                case 'services_packages':
                    $query->whereIn('subject_type', [CompanyService::class, Package::class, ProjectServiceUser::class]);
                    break;
                case 'badges_rewards':
                    $query->whereIn('subject_type', [Badge::class, BadgeDemotionRule::class, FoodAllowance::class]);
                    break;
                case 'advanced_tickets':
                    $query->whereIn('subject_type', [TicketAssignment::class, TicketComment::class, TicketWorkflowHistory::class]);
                    break;
                case 'attachments_teams':
                    $query->whereIn('subject_type', [AttachmentShare::class, Team::class]);
                    break;
                case 'social_admin':
                    $query->whereIn('subject_type', [Comment::class, AdministrativeDecision::class, GraphicTaskType::class, TaskGraphicType::class]);
                    break;
            }
        }

        // فلتر حسب التاريخ
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // فلتر حسب الوصف
        if ($request->filled('description')) {
            $query->where('description', 'like', '%' . $request->description . '%');
        }

        $activities = $query->latest()->paginate(20);

        // معلومات الفلترة للعرض
        $filterInfo = [
            'user_id' => $request->user_id,
            'activity_type' => $request->activity_type,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'description' => $request->description,
            'total_found' => $activities->total()
        ];

        return view('activity-log.search-results', compact('activities', 'filterInfo'));
    }

    /**
     * تصدير النشاطات
     */
    public function export(Request $request)
    {
        $query = Activity::with(['causer', 'subject']);

        // تطبيق نفس الفلاتر
        if ($request->filled('subject_type')) {
            $query->where('subject_type', $request->subject_type);
        }

        if ($request->filled('causer_id')) {
            $query->where('causer_id', $request->causer_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $activities = $query->latest()->get();

        $csvData = [];
        $csvData[] = ['التاريخ', 'المستخدم', 'النوع', 'الوصف', 'البيانات'];

        foreach ($activities as $activity) {
            $csvData[] = [
                $activity->created_at->format('Y-m-d H:i:s'),
                $activity->causer ? $activity->causer->name : 'غير محدد',
                class_basename($activity->subject_type),
                $activity->description,
                json_encode($activity->properties, JSON_UNESCAPED_UNICODE)
            ];
        }

        $filename = 'activity_log_' . now()->format('Y-m-d_H-i-s') . '.csv';

        $callback = function() use ($csvData) {
            $file = fopen('php://output', 'w');
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
