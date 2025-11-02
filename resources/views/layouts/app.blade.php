<!DOCTYPE html>
<html lang="en" dir="@yield('htmldir', 'ltr')">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Attendance System') }}</title>

    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">
    <link href="{{ asset('css/toast-notifications.css') }}" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}?t={{ filemtime(public_path('css/app.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/sidebar.css') }}" rel="stylesheet">


    @stack('styles')
    @livewireStyles
</head>

<body class="d-flex flex-column min-vh-100 {{ auth()->user() ? 'user-logged-in' : 'user-guest' }}" x-data x-cloak>
    <div class="page-transition">
        @if(auth()->user())
        @livewire('navigation-menu')
        @else
        @include('layouts.navigation')
        @endif
    </div>

    <!-- Sidebar Toggle Button -->
    @if(auth()->user())
    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <span class="close-btn" id="closeSidebar">
            <i class="fas fa-times"></i>
        </span>

        <div class="sidebar-user">
            <img src="{{ auth()->user()->profile_photo_url }}" alt="{{ auth()->user()->name }}">
            <h4>{{ auth()->user()->name }}</h4>
            <p>{{ auth()->user()->email }}</p>
        </div>

        <div class="sidebar-category">القائمة الرئيسية</div>
        <ul class="sidebar-menu">
            @if(Auth::user()->hasAnyRole(['hr', 'company_manager', 'project_manager']))
            <li>
                <a href="{{ route('service-data.index') }}" class="{{ request()->routeIs('service-data.*') ? 'active' : '' }}">
                    <i class="fas fa-database"></i>
                    <span>إدارة بيانات الخدمات</span>
                </a>
            </li>
            <li>
                <a href="{{ route('performance-analysis.index') }}" class="{{ request()->routeIs('performance-analysis.*') ? 'active' : '' }}">
                    <i class="fas fa-chart-line"></i>
                    <span>تحليل الأداء للمراجع العام</span>
                </a>
            </li>
            @endif
            <li>
                <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>لوحة التحكم</span>
                </a>
            </li>
            @php
            $userHierarchyLevel = \App\Models\RoleHierarchy::getUserMaxHierarchyLevel(Auth::user());
            $currentUser = Auth::user();
            $dashboardUrl = null;
            $isActive = false;

            if ($userHierarchyLevel == 3) {
            // Level 3: رابط مباشر لـ dashboard الفريق
            $userTeam = DB::table('teams')->where('user_id', $currentUser->id)->first();
            if ($userTeam) {
            $dashboardUrl = route('departments.teams.show', [
            'department' => urlencode($currentUser->department ?? 'Unknown'),
            'teamId' => $userTeam->id
            ]);
            $isActive = request()->routeIs('departments.teams.show');
            }
            } elseif ($userHierarchyLevel == 4) {
            // Level 4: رابط مباشر لـ dashboard القسم
            if ($currentUser->department) {
            $dashboardUrl = route('departments.show', [
            'department' => urlencode($currentUser->department)
            ]);
            $isActive = request()->routeIs('departments.show');
            }
            } elseif ($userHierarchyLevel >= 5) {
            // Level 5+: الرابط العادي للـ main dashboard
            $dashboardUrl = route('company-projects.dashboard');
            $isActive = request()->routeIs('company-projects.dashboard');
            }
            @endphp
            @if($userHierarchyLevel >= 3 && $dashboardUrl)
            <li>
                <a href="{{ $dashboardUrl }}" class="{{ $isActive ? 'active' : '' }}">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>لوحة تحكم المشاريع</span>
                </a>
            </li>
            @endif
            <li>
                <a href="{{ route('projects.index') }}" class="{{ request()->routeIs('projects.index') ? 'active' : '' }}">
                    <i class="fas fa-project-diagram"></i>
                    <span>المشاريع</span>
                </a>
            </li>
            <li>
                <a href="{{ route('employee.projects.index') }}" class="{{ request()->routeIs('employee.projects.*') ? 'active' : '' }}">
                    <i class="fas fa-folder-open"></i>
                    <span>مشاريعي</span>
                </a>
            </li>
            @if(Auth::user()->hasAnyRole(['operation_assistant', 'technical_support', 'operations_manager', 'project_manager', 'company_manager', 'hr']))
            <li>
                <a href="{{ route('projects.archive') }}" class="{{ request()->routeIs('projects.archive') ? 'active' : '' }}">
                    <i class="fas fa-archive"></i>
                    <span>أرشيف المشاريع</span>
                </a>
            </li>
            @endif
            <li>
                <a href="{{ route('projects.services-overview') }}" class="{{ request()->routeIs('projects.services-overview') ? 'active' : '' }}">
                    <i class="fas fa-table"></i>
                    <span>نظرة عامة على المشاريع</span>
                </a>
            </li>
            <li>
                <a href="{{ route('projects.preparation-period') }}" class="{{ request()->routeIs('projects.preparation-period') ? 'active' : '' }}">
                    <i class="fas fa-hourglass-half"></i>
                    <span>فترة التحضير للمشاريع</span>
                </a>
            </li>
            @if(Auth::user()->hasRole(['project_manager', 'operations_manager', 'operation_assistant', 'technical_support']))
            <li>
                <a href="{{ route('projects.pause.index') }}" class="{{ request()->routeIs('projects.pause.*') ? 'active' : '' }}">
                    <i class="fas fa-pause-circle"></i>
                    <span>إدارة توقيف المشاريع</span>
                </a>
            </li>
            @endif
            @if(Auth::user()->hasRole(['technical_support', 'customer_service_team_leader', 'sales_employee', 'customer_service_department_manager']))
            <li>
                <a href="{{ route('projects.deliveries.index') }}" class="{{ request()->routeIs('projects.deliveries.*') ? 'active' : '' }}">
                    <i class="fas fa-shipping-fast"></i>
                    <span>إدارة تسليمات المشاريع (خدمة العملاء)</span>
                </a>
            </li>
            @endif
            @if(Auth::user()->hasRole(['operation_assistant', 'operations_manager', 'project_manager', 'company_manager', 'hr', 'general_reviewer']))
            <li>
                <a href="{{ route('projects.client-deliveries') }}" class="{{ request()->routeIs('projects.client-deliveries') ? 'active' : '' }}">
                    <i class="fas fa-handshake"></i>
                    <span>للفريق التنفيذي (مواعيد التسليم مع العملاء)</span>
                </a>
            </li>
            @endif
            @if(Auth::user()->hasRole(['coordination-team-employee', 'coordination_team_leader', 'coordination_department_manager', 'general_reviewer']))
            <li>
                <a href="{{ route('projects.internal-delivery.index') }}" class="{{ request()->routeIs('projects.internal-delivery.*') ? 'active' : '' }}">
                    <span>التسليم الداخلي للمشاريع</span>
                </a>
            </li>
            @endif
            @if(Auth::user()->hasRole('hr'))
            <li>
                <a href="{{ route('project-fields.index') }}" class="{{ request()->routeIs('project-fields.*') ? 'active' : '' }}">
                    <i class="fas fa-sliders-h"></i>
                    <span>حقول المشاريع الإضافية</span>
                </a>
            </li>
            @endif
            @if(Auth::user()->hasRole('hr'))
            <li>
                <a href="{{ route('project-limits.index') }}" class="{{ request()->routeIs('project-limits.*') ? 'active' : '' }}">
                    <i class="fas fa-chart-line"></i>
                    <span>الحد الشهري للمشاريع</span>
                </a>
            </li>
            @endif
            <li>
                <a href="{{ route('tasks.index') }}" class="{{ request()->routeIs('tasks.index') ? 'active' : '' }}">
                    <i class="fas fa-tasks"></i>
                    <span>المهام</span>
                </a>
            </li>
            <li>
                <a href="{{ route('tasks.my-tasks') }}" class="{{ request()->routeIs('tasks.my-tasks') ? 'active' : '' }}">
                    <i class="fas fa-user-check"></i>
                    <span>مهامي</span>
                </a>
            </li>
            <li>
                <a href="{{ route('deliveries.index') }}" class="{{ request()->routeIs('deliveries.*') ? 'active' : '' }}">
                    <i class="fas fa-truck-loading"></i>
                    <span>تسليمات المشاريع</span>
                </a>
            </li>
            <li>
                <a href="{{ route('task-deliveries.index') }}" class="{{ request()->routeIs('task-deliveries.*') ? 'active' : '' }}">
                    <i class="fas fa-clipboard-check"></i>
                    <span>تسليمات التاسكات</span>
                </a>
            </li>
            @if(Auth::user()->hasRole(['hr', 'project_manager']))
            <li>
                <a href="{{ route('task-templates.index') }}" class="{{ request()->routeIs('task-templates.*') ? 'active' : '' }}">
                    <i class="fas fa-clipboard-list"></i>
                    <span>قوالب المهام</span>
                </a>
            </li>
            <li>
                <a href="{{ route('task-transfers.history') }}" class="{{ request()->routeIs('task-transfers.*') ? 'active' : '' }}">
                    <i class="fas fa-exchange-alt"></i>
                    <span>تاريخ نقل المهام</span>
                </a>
            </li>
            @endif
            <li>
                <a href="{{ route('revision.my-revisions-page') }}" class="{{ request()->routeIs('revision.my-revisions-page') ? 'active' : '' }}">
                    <i class="fas fa-user-edit"></i>
                    <span>تعديلاتي</span>
                </a>
            </li>
            <li>
                <a href="{{ route('revision.page') }}" class="{{ request()->routeIs('revision.page') ? 'active' : '' }}">
                    <i class="fas fa-edit"></i>
                    <span>التعديلات</span>
                </a>
            </li>
            <li>
                <a href="{{ route('employee-reports.index') }}" class="{{ request()->routeIs('employee-reports.*') ? 'active' : '' }}">
                    <i class="fas fa-user-clock"></i>
                    <span>تقارير الموظفين</span>
                </a>
            </li>
            <li>
                <a href="{{ route('profile.show') }}" class="{{ request()->routeIs('profile.show') ? 'active' : '' }}">
                    <i class="fas fa-user"></i>
                    <span>الملف الشخصي</span>
                </a>
            </li>
            <li>
                <a href="{{ route('employee.profile.show') }}" class="{{ request()->routeIs('employee.profile.*') ? 'active' : '' }}">
                    <i class="fas fa-id-card"></i>
                    <span>ملفي الشامل</span>
                </a>
            </li>
            <li>
                <a href="{{ route('employee-errors.index') }}" class="{{ request()->routeIs('employee-errors.*') ? 'active' : '' }}">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>أخطائي</span>
                </a>
            </li>
            @if(Auth::user()->hasRole('hr'))
            <li>
                <a href="{{ route('company-services.index') }}" class="{{ request()->routeIs('company-services.*') ? 'active' : '' }}">
                    <i class="fas fa-concierge-bell"></i>
                    <span>خدمات الشركة</span>
                </a>
            </li>
            @endif
        </ul>

        <div class="sidebar-category">المهام الإضافية</div>
        <ul class="sidebar-menu">
            <li>
                <a href="{{ route('additional-tasks.index') }}" class="{{ request()->routeIs('additional-tasks.index') ? 'active' : '' }}">
                    <i class="fas fa-tasks"></i>
                    <span>كل المهام الإضافية</span>
                </a>
            </li>
            @php
            $userHierarchyLevelForTasks = \App\Models\RoleHierarchy::getUserMaxHierarchyLevel(Auth::user());
            @endphp
            @if($userHierarchyLevelForTasks !== null && $userHierarchyLevelForTasks >= 3)
            <li>
                <a href="{{ route('additional-tasks.create') }}" class="{{ request()->routeIs('additional-tasks.create') ? 'active' : '' }}">
                    <i class="fas fa-plus-circle"></i>
                    <span>إضافة مهمة إضافية</span>
                </a>
            </li>
            @endif
            <li>
                <a href="{{ route('additional-tasks.user-tasks') }}" class="{{ request()->routeIs('additional-tasks.user-tasks') ? 'active' : '' }}">
                    <i class="fas fa-user-check"></i>
                    <span>مهامي الإضافية</span>
                </a>
            </li>
            @if(Auth::user()->hasRole(['hr', 'project_manager']))
            <li>
                <a href="{{ route('additional-tasks.applications') }}" class="{{ request()->routeIs('additional-tasks.applications') ? 'active' : '' }}">
                    <i class="fas fa-file-contract"></i>
                    <span>طلبات المهام الإضافية</span>
                </a>
            </li>
            @endif
        </ul>

        @if(Auth::user()->hasRole(['hr', 'project_manager']))
        <div class="sidebar-category">أدوات إدارة المهام</div>
        <ul class="sidebar-menu">
            <li>
                <a href="{{ route('attachment-shares.index') }}" class="{{ request()->routeIs('attachment-shares.*') ? 'active' : '' }}">
                    <i class="fas fa-share-alt"></i>
                    <span>الملفات المشاركة</span>
                </a>
            </li>
            <li>
                <a href="{{ route('attachment-confirmations.index') }}" class="{{ request()->routeIs('attachment-confirmations.*') ? 'active' : '' }}">
                    <i class="fas fa-check-circle"></i>
                    <span>تأكيد المرفقات</span>
                </a>
            </li>
            @if(Auth::user()->hasRole('hr'))
            <li>
                <a href="{{ route('graphic-task-types.index') }}" class="{{ request()->routeIs('graphic-task-types.*') ? 'active' : '' }}">
                    <i class="fas fa-palette"></i>
                    <span>أنواع المهام الجرافيكية</span>
                </a>
            </li>
            @endif
        </ul>
        @endif

        <div class="sidebar-category">الحضور والانصراف</div>
        <ul class="sidebar-menu">
            @if(Auth::user()->hasRole('hr'))
            <li>
                <a href="/attendance" class="{{ request()->is('attendance') ? 'active' : '' }}">
                    <i class="fas fa-clipboard-list"></i>
                    <span>سجلات الحضور</span>
                </a>
            </li>
            @endif
            <li>
                <a href="/attendance-system/my-attendance" class="{{ request()->is('attendance-system/my-attendance') ? 'active' : '' }}">
                    <i class="fas fa-clock"></i>
                    <span>تسجيل حضور</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-category">الطلبات</div>
        <ul class="sidebar-menu">
            <li>
                <a href="{{ route('overtime-requests.index') }}" class="{{ request()->routeIs('overtime-requests.*') ? 'active' : '' }}">
                    <i class="fas fa-business-time"></i>
                    <span>طلبات العمل الإضافي</span>
                </a>
            </li>
            <li>
                <a href="{{ route('absence-requests.index') }}" class="{{ request()->routeIs('absence-requests.*') ? 'active' : '' }}">
                    <i class="fas fa-calendar-times"></i>
                    <span>طلبات الاجازة</span>
                </a>
            </li>
            <li>
                <a href="{{ route('permission-requests.index') }}" class="{{ request()->routeIs('permission-requests.*') ? 'active' : '' }}">
                    <i class="fas fa-door-open"></i>
                    <span>طلبات الاستئذان</span>
                </a>
            </li>
            <li>
                <a href="{{ route('attachment-confirmations.my-requests') }}" class="{{ request()->routeIs('attachment-confirmations.my-requests') ? 'active' : '' }}">
                    <i class="fas fa-file-check"></i>
                    <span>طلبات تأكيد المرفقات</span>
                </a>
            </li>
            @if(Auth::user()->hasRole('hr'))
            <li>
                <a href="{{ route('food-allowances.index') }}" class="{{ request()->routeIs('food-allowances.index') ? 'active' : '' }}">
                    <i class="fas fa-utensils"></i>
                    <span>إدارة الطعام</span>
                </a>
            </li>
            @endif
            <li>
                <a href="{{ route('food-allowances.my-requests') }}" class="{{ request()->routeIs('food-allowances.my-requests') ? 'active' : '' }}">
                    <i class="fas fa-hamburger"></i>
                    <span>طلبات الأكل الخاصة بي</span>
                </a>
            </li>
        </ul>



        <!-- سجل تقييمات KPI - متاح للجميع -->
        <div class="sidebar-category">📊 تقييمات KPI والأداء</div>
        <ul class="sidebar-menu">
            <li>
                <a href="{{ route('kpi-evaluation.index') }}" class="{{ request()->routeIs('kpi-evaluation.index') ? 'active' : '' }}">
                    <i class="fas fa-list-alt"></i>
                    <span>سجل تقييمات KPI</span>
                </a>
            </li>
        </ul>

        <!-- النظام الديناميكي لإدارة التقييمات -->
        @php
        // التحقق من جميع الأدوار التي يمتلكها المستخدم
        $userRoles = Auth::user()->roles->pluck('id')->toArray();
        // التحقق من وجود mappings في RoleEvaluationMapping حيث can_evaluate = true لأي من أدوار المستخدم
        $canAccessDynamicEvaluation = false;
        if (!empty($userRoles)) {
        $canAccessDynamicEvaluation = \App\Models\RoleEvaluationMapping::whereIn('evaluator_role_id', $userRoles)
        ->where('can_evaluate', true)
        ->exists();
        }
        @endphp

        @if($canAccessDynamicEvaluation)
        <div class="sidebar-category">إدارة تقييمات KPI</div>
        <ul class="sidebar-menu">
            <li>
                <a href="{{ route('kpi-evaluation.create') }}" class="{{ request()->routeIs('kpi-evaluation.create') ? 'active' : '' }}">
                    <i class="fas fa-magic"></i>
                    <span>تقييم KPI للموظفين</span>
                </a>
            </li>
            @if(Auth::user()->hasAnyRole(['hr', 'project_manager', 'company_manager']))
            <li>
                <a href="{{ route('evaluation-criteria.index') }}" class="{{ request()->routeIs('evaluation-criteria.*') ? 'active' : '' }}">
                    <i class="fas fa-list-check"></i>
                    <span>إدارة بنود التقييم</span>
                </a>
            </li>
            <li>
                <a href="{{ route('role-evaluation-mapping.index') }}" class="{{ request()->routeIs('role-evaluation-mapping.*') ? 'active' : '' }}">
                    <i class="fas fa-link"></i>
                    <span>ربط الأدوار بالتقييم</span>
                </a>
            </li>
            @endif
        </ul>
        @endif

        @if(Auth::user()->hasRole('hr'))
        <div class="sidebar-category">إدارة الموارد البشرية</div>
        <ul class="sidebar-menu">
            <li>
                <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'active' : '' }}">
                    <i class="fas fa-users"></i>
                    <span>إدارة الموظفين</span>
                </a>
            </li>
            <li>
                <a href="{{ route('roles.index') }}" class="{{ request()->routeIs('roles.*') ? 'active' : '' }}">
                    <i class="fas fa-user-tag"></i>
                    <span>إدارة الأدوار</span>
                </a>
            </li>
            <li>
                <a href="{{ route('department-roles.index') }}" class="{{ request()->routeIs('department-roles.*') ? 'active' : '' }}">
                    <i class="fas fa-sitemap"></i>
                    <span>إدارة أدوار الأقسام</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.teams.index') }}" class="{{ request()->routeIs('admin.teams.*') ? 'active' : '' }}">
                    <i class="fas fa-users-cog"></i>
                    <span>إدارة الفرق</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.role-approvals.index') }}" class="{{ request()->routeIs('admin.role-approvals.*') ? 'active' : '' }}">
                    <i class="fas fa-user-check"></i>
                    <span> الخاصه بتسليمات المشاريع اعتمادات الأدوار</span>
                </a>
            </li>
            <li>
                <a href="{{ route('salary-sheets.index') }}" class="{{ request()->routeIs('salary-sheets.*') ? 'active' : '' }}">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <span>كشوف المرتبات</span>
                </a>
            </li>
            <li>
                <a href="{{ route('work-shifts.index') }}" class="{{ request()->routeIs('work-shifts.*') ? 'active' : '' }}">
                    <i class="fas fa-clock"></i>
                    <span>إدارة الورديات</span>
                </a>
            </li>
            <li>
                <a href="{{ route('users.assign-work-shifts') }}" class="{{ request()->routeIs('users.assign-work-shifts') ? 'active' : '' }}">
                    <i class="fas fa-user-clock"></i>
                    <span>تعيين الورديات</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.notifications.index') }}" class="{{ request()->routeIs('admin.notifications.*') ? 'active' : '' }}">
                    <i class="fas fa-gavel"></i>
                    <span>القرارات الإدارية</span>
                </a>
            </li>
            <li>
                <a href="{{ route('food-allowances.index') }}" class="{{ request()->routeIs('food-allowances.*') ? 'active' : '' }}">
                    <i class="fas fa-utensils"></i>
                    <span>إدارة الطعام</span>
                </a>
            </li>
            <li>
                <a href="{{ route('activity-log.index') }}" class="{{ request()->routeIs('activity-log.*') ? 'active' : '' }}">
                    <i class="fas fa-history"></i>
                    <span>سجل النشاطات</span>
                </a>
            </li>
            <li>
                <a href="{{ route('tracking-employee.dashboard') }}" class="{{ request()->routeIs('tracking-employee.*') ? 'active' : '' }}">
                    <i class="fas fa-user-clock"></i>
                    <span>تتبع نشاط الموظفين</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-category">التقارير والإحصائيات</div>
        <ul class="sidebar-menu">
            <li>
                <a href="{{ route('employee-statistics.index') }}" class="{{ request()->routeIs('employee-statistics.*') ? 'active' : '' }}">
                    <i class="fas fa-chart-line"></i>
                    <span>إحصائيات الموظفين</span>
                </a>
            </li>
            <li>
                <a href="{{ route('employee-competition.index') }}" class="{{ request()->routeIs('employee-competition.*') ? 'active' : '' }}">
                    <i class="fas fa-trophy"></i>
                    <span>المسابقة</span>
                </a>
            </li>
            <li>
                <a href="{{ route('employee-birthdays.index') }}" class="{{ request()->routeIs('employee-birthdays.*') ? 'active' : '' }}">
                    <i class="fas fa-birthday-cake"></i>
                    <span>أعياد الميلاد</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-category">الإدارة المتقدمة</div>
        <ul class="sidebar-menu">
            <li>
                <a href="{{ route('special-cases.index') }}" class="{{ request()->routeIs('special-cases.index') ? 'active' : '' }}">
                    <i class="fas fa-users-cog"></i>
                    <span>إدارة الحالات الخاصة</span>
                </a>
            </li>
            <li>
                <a href="{{ route('special-cases.create') }}" class="{{ request()->routeIs('special-cases.create') ? 'active' : '' }}">
                    <i class="fas fa-plus-circle"></i>
                    <span>إضافة حالة خاصة</span>
                </a>
            </li>
            <li>
                <a href="{{ route('audit-log.index') }}" class="{{ request()->routeIs('audit-log.index') ? 'active' : '' }}">
                    <i class="fas fa-history"></i>
                    <span>سجلات التدقيق</span>
                </a>
            </li>
        </ul>
        @endif

        @if(Auth::user()->hasRole('hr'))
        <div class="sidebar-category">الإشعارات</div>
        <ul class="sidebar-menu">
            <li>
                <a href="{{ route('notifications.unread') }}" class="{{ request()->routeIs('notifications.unread') ? 'active' : '' }}">
                    <i class="fas fa-gavel"></i>
                    <span>القرارات الإدارية</span>
                </a>
            </li>
        </ul>

        <!-- إدارة المهارات والتقييمات -->
        <div class="sidebar-category">المهارات والتقييمات</div>
        <ul class="sidebar-menu">
            <li>
                <a href="{{ route('skills.index') }}" class="{{ request()->routeIs('skills.index') ? 'active' : '' }}">
                    <i class="fas fa-star"></i>
                    <span>المهارات</span>
                </a>
            </li>
            <li>
                <a href="{{ route('skill-categories.index') }}" class="{{ request()->routeIs('skill-categories.*') ? 'active' : '' }}">
                    <i class="fas fa-list-alt"></i>
                    <span>تصنيفات المهارات</span>
                </a>
            </li>
            <li>
                <a href="{{ route('employee-evaluations.index') }}" class="{{ request()->routeIs('employee-evaluations.index') ? 'active' : '' }}">
                    <i class="fas fa-clipboard-check"></i>
                    <span>تقييمات الموظفين</span>
                </a>
            </li>
            <li>
                <a href="{{ route('employee-evaluations.my-evaluations') }}" class="{{ request()->routeIs('employee-evaluations.my-evaluations') ? 'active' : '' }}">
                    <i class="fas fa-user-check"></i>
                    <span>تقييماتي</span>
                </a>
            </li>
        </ul>
        @endif

        @if(Auth::user()->hasRole(['hr', 'project_manager']))
        <div class="sidebar-category">إدارة المشاريع الإضافية</div>
        <ul class="sidebar-menu">
            <li>
                <a href="{{ route('clients.index') }}" class="{{ request()->routeIs('clients.*') ? 'active' : '' }}">
                    <i class="fas fa-user-tie"></i>
                    <span>العملاء</span>
                </a>
            </li>
            <li>
                <a href="{{ route('packages.index') }}" class="{{ request()->routeIs('packages.*') ? 'active' : '' }}">
                    <i class="fas fa-box"></i>
                    <span>الباقات</span>
                </a>
            </li>
        </ul>
        @endif

        @if(Auth::user()->hasRole('hr'))
        <!-- المواسم -->
        <div class="sidebar-category">المواسم</div>
        <ul class="sidebar-menu">
            <li>
                <a href="{{ route('seasons.current') }}" class="{{ request()->routeIs('seasons.current') ? 'active' : '' }}">
                    <i class="fas fa-trophy"></i>
                    <span>السيزون الحالي</span>
                </a>
            </li>
            <li>
                <a href="{{ route('seasons.index') }}" class="{{ request()->routeIs('seasons.index') ? 'active' : '' }}">
                    <i class="fas fa-calendar-alt"></i>
                    <span>إدارة المواسم</span>
                </a>
            </li>
        </ul>

        <!-- إحصائيات المواسم -->
        <div class="sidebar-category">📊 إحصائيات المواسم</div>
        <ul class="sidebar-menu">
            <li>
                <a href="{{ route('seasons.statistics.my') }}" class="{{ request()->routeIs('seasons.statistics.my') ? 'active' : '' }}">
                    <i class="fas fa-user-chart"></i>
                    <span>إحصائياتي</span>
                </a>
            </li>
            <li>
                <a href="{{ route('seasons.statistics.company') }}" class="{{ request()->routeIs('seasons.statistics.company') ? 'active' : '' }}">
                    <i class="fas fa-building"></i>
                    <span>إحصائيات الشركة</span>
                </a>
            </li>
            <li>
                <a href="{{ route('seasons.statistics.all-users') }}" class="{{ request()->routeIs('seasons.statistics.all-users') ? 'active' : '' }}">
                    <i class="fas fa-users-chart"></i>
                    <span>إحصائيات جميع الموظفين</span>
                </a>
            </li>
        </ul>
        @endif

        @if(Auth::user()->hasRole(['hr', 'company_manager', 'customer_service_team_leader', 'customer_service_department_manager', 'technical_support', 'sales_employee']))
        <!-- نظام إدارة علاقات العملاء (CRM) -->
        <div class="sidebar-category">إدارة علاقات العملاء</div>
        <ul class="sidebar-menu">
            <li>
                <a href="{{ route('clients.crm-dashboard') }}" class="{{ request()->routeIs('clients.crm-dashboard') ? 'active' : '' }}">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>لوحة تحكم CRM</span>
                </a>
            </li>
            <li>
                <a href="{{ route('call-logs.index') }}" class="{{ request()->routeIs('call-logs.*') ? 'active' : '' }}">
                    <i class="fas fa-phone"></i>
                    <span>سجلات المكالمات</span>
                </a>
            </li>
            <li>
                <a href="{{ route('client-tickets.index') }}" class="{{ request()->routeIs('client-tickets.*') ? 'active' : '' }}">
                    <i class="fas fa-ticket-alt"></i>
                    <span>تذاكر الدعم</span>
                </a>
            </li>
            <li>
                <a href="{{ route('client-tickets.dashboard') }}" class="{{ request()->routeIs('client-tickets.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-chart-pie"></i>
                    <span>إحصائيات التذاكر</span>
                </a>
            </li>
        </ul>
        @endif

    </div>

    <!-- Overlay for sidebar -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    @endif

    <div class="wrapper flex-grow-1 d-flex flex-column page-transition" id="contentWrapper">
        @if(session('success'))
        <script>
            toastr.success("{{ session('success') }}");
        </script>
        @endif
        @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <div class="d-flex">
                <div class="me-2">
                    <i class="fas fa-exclamation-circle fa-lg"></i>
                </div>
                <div>
                    <ul class="mb-0 ps-0" style="list-style: none;">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif


        @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
        @endif


        <main class="flex-grow-1">
            @yield('content')

            @isset($slot)
            {{ $slot }}
            @endisset

        </main>

        @include('layouts.footer')
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.9.1/gsap.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>

    <script src="{{ asset('js/app.js' ) }}"></script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])


    <script>
        AOS.init({
            duration: 1000,
            once: true
        });

        // Sidebar functionality
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const closeSidebar = document.getElementById('closeSidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const contentWrapper = document.getElementById('contentWrapper');

            if (sidebar && sidebarToggle && closeSidebar && sidebarOverlay) {
                // Toggle sidebar
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                    sidebarOverlay.classList.toggle('active');
                    contentWrapper.classList.toggle('active');
                });

                // Close sidebar
                closeSidebar.addEventListener('click', function() {
                    sidebar.classList.remove('active');
                    sidebarOverlay.classList.remove('active');
                    contentWrapper.classList.remove('active');
                });

                // Close sidebar when clicking overlay
                sidebarOverlay.addEventListener('click', function() {
                    sidebar.classList.remove('active');
                    sidebarOverlay.classList.remove('active');
                    contentWrapper.classList.remove('active');
                });

                // Submenu toggle
                const submenuItems = document.querySelectorAll('.has-submenu');
                submenuItems.forEach(item => {
                    item.addEventListener('click', function(e) {
                        e.preventDefault();
                        this.classList.toggle('active');
                        const submenu = this.nextElementSibling;
                        if (submenu && submenu.classList.contains('sidebar-submenu')) {
                            submenu.classList.toggle('active');
                        }
                    });
                });
            }
        });
    </script>

    <script src="{{ asset('js/toast-notifications.js') }}"></script>


    @stack('scripts')
    @stack('modals')

    @livewireScripts
    <x-firebase-messaging />
</body>

</html>