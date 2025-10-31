@extends('layouts.app')

@section('title', 'أداء الموظف - ' . $employee->name)

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Pass data to JavaScript
    window.performanceData = {
        projectStats: @json($projectStats),
        projectCompletionPoints: @json($projectCompletionPoints ?? 0),
        taskStats: @json($taskStats),
        monthlyStats: @json($monthlyStats),
        timeStats: {
            estimated_hours: {{ round($timeStats['estimated_minutes'] / 60, 1) }},
            spent_hours: {{ round($timeStats['spent_minutes'] / 60, 1) }}
        }
    };
</script>
<script src="{{ asset('js/project-dashboard/employee/performance.js') }}"></script>
<script src="{{ asset('js/project-dashboard/employee/filters.js') }}"></script>
@endpush

@push('styles')
<link rel="stylesheet" href="{{ asset('css/projects-dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('css/project-completion-modal.css') }}">
<link rel="stylesheet" href="{{ asset('css/project-dashboard/employees.css') }}">
<link rel="stylesheet" href="{{ asset('css/project-dashboard/employee/performance.css') }}">
<link rel="stylesheet" href="{{ asset('css/project-dashboard/revisions-modern.css') }}">
<style>
    /* Container Fluid Styling for Full Width */
    .container-fluid {
        width: 100%;
        padding-right: 30px;
        padding-left: 30px;
        margin-right: auto;
        margin-left: auto;
    }

    /* Responsive Padding */
    @media (max-width: 768px) {
        .container-fluid {
            padding-right: 15px;
            padding-left: 15px;
        }
    }

    /* Ensure Modern Dashboard Takes Full Width */
    .modern-dashboard {
        width: 100%;
        max-width: 100%;
    }
</style>
@endpush

@section('content')
<div class="modern-dashboard">
    <!-- Breadcrumb -->
    <div class="container-fluid">
        <nav class="breadcrumb">
            <a href="{{ route('company-projects.dashboard') }}" class="breadcrumb-item">لوحة التحكم</a>
            @if($employee->department)
            <a href="{{ route('departments.show', urlencode($employee->department)) }}" class="breadcrumb-item">{{ $employee->department }}</a>
            @endif
            <span class="breadcrumb-item active">{{ $employee->name }}</span>
        </nav>
    </div>

    <!-- Employee Header -->
    <div class="employee-header">
        <div class="container-fluid">
            <div class="employee-info">
                                                @php
                    $nameParts = explode(' ', trim($employee->name));
                    $initials = count($nameParts) >= 2
                        ? substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1)
                        : substr($employee->name, 0, 2);

                    // تحديد الصورة بناءً على الجنس
                    $avatarImage = 'man.gif'; // الافتراضي للرجال
                    if ($employee->gender === 'female' || $employee->gender === 'أنثى' || $employee->gender === 'انثى') {
                        $avatarImage = 'women-avatar.gif';
                    }
                @endphp
                <img
                    src="{{ asset('avatars/' . $avatarImage) }}"
                    alt="{{ $employee->name }}"
                    class="employee-avatar-large"
                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                />
                <div class="employee-avatar-fallback" style="display: none;">
                    {{ $initials }}
                </div>
                <div class="employee-details">
                    <h1>{{ $employee->name }}</h1>
                    <div class="employee-meta">
                        <div>{{ $employee->email }}</div>
                        @if($employee->phone_number)
                        <div>{{ $employee->phone_number }}</div>
                        @endif
                    </div>
                    @if($employee->department)
                    <div class="employee-department">{{ $employee->department }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="filters-section">
        <div class="container-fluid">
            <div class="filters-container">
                <div class="filters-header">
                    <h3><i class="fas fa-filter"></i> فلتر البيانات</h3>
                    <button type="button" class="toggle-filters-btn" id="toggleFiltersBtn">
                        <i class="fas fa-chevron-down"></i>
                        عرض الفلاتر
                    </button>
                </div>
                <div class="filters-content collapsed" id="filtersContent">
                    <form method="GET" action="{{ route('employees.performance', $employee->id) }}" id="employeeFiltersForm">
                        <div class="filters-grid">
                            <!-- Date Range Filter -->
                            <div class="filter-group">
                                <label><i class="fas fa-calendar"></i> الفترة الزمنية</label>
                                <div class="date-filter-options">
                                    <div class="date-option">
                                        <input type="radio" id="quick_filter" name="filter_type" value="quick"
                                               {{ request('filter_type', 'quick') == 'quick' ? 'checked' : '' }}>
                                        <label for="quick_filter">فلتر سريع</label>
                                    </div>
                                    <div class="date-option">
                                        <input type="radio" id="custom_filter" name="filter_type" value="custom"
                                               {{ request('filter_type') == 'custom' ? 'checked' : '' }}>
                                        <label for="custom_filter">تاريخ مخصص</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Quick Filter (Month/Period) -->
                            <div class="filter-group quick-filter" id="quickFilterGroup">
                                <label>الفترة</label>
                                <select name="quick_period" id="quickPeriod" class="form-select">
                                    <option value="">جميع الفترات</option>
                                    <option value="this_month" {{ request('quick_period') == 'this_month' ? 'selected' : '' }}>الشهر الحالي</option>
                                    <option value="last_month" {{ request('quick_period') == 'last_month' ? 'selected' : '' }}>الشهر الماضي</option>
                                    <option value="this_quarter" {{ request('quick_period') == 'this_quarter' ? 'selected' : '' }}>الربع الحالي</option>
                                    <option value="last_quarter" {{ request('quick_period') == 'last_quarter' ? 'selected' : '' }}>الربع الماضي</option>
                                    <option value="this_year" {{ request('quick_period') == 'this_year' ? 'selected' : '' }}>السنة الحالية</option>
                                    <option value="last_year" {{ request('quick_period') == 'last_year' ? 'selected' : '' }}>السنة الماضية</option>
                                    <option value="last_7_days" {{ request('quick_period') == 'last_7_days' ? 'selected' : '' }}>آخر 7 أيام</option>
                                    <option value="last_30_days" {{ request('quick_period') == 'last_30_days' ? 'selected' : '' }}>آخر 30 يوم</option>
                                </select>
                            </div>

                            <!-- Custom Date Range -->
                            <div class="filter-group custom-filter" id="customFilterGroup" style="display: none;">
                                <label>من تاريخ</label>
                                <input type="date" name="from_date" id="fromDate" value="{{ request('from_date') }}" class="form-input">
                            </div>

                            <div class="filter-group custom-filter" id="customFilterGroup2" style="display: none;">
                                <label>إلى تاريخ</label>
                                <input type="date" name="to_date" id="toDate" value="{{ request('to_date') }}" class="form-input">
                            </div>

                            <!-- Apply Filters Button -->
                            <div class="filter-actions">
                                <button type="submit" class="btn-apply-filters">
                                    <i class="fas fa-search"></i>
                                    تطبيق الفلتر
                                </button>
                                <a href="{{ route('employees.performance', $employee->id) }}" class="btn-reset-filters">
                                    <i class="fas fa-undo"></i>
                                    إعادة تعيين
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <!-- Filter Indicator -->
        @if(isset($periodDescription) && $periodDescription != 'جميع الفترات')
        <div class="section-modern">
            <div class="filter-indicator-employee">
                <i class="fas fa-filter text-primary"></i>
                <span class="filter-text">فلتر مطبق: {{ $periodDescription }}</span>
            </div>
        </div>
        @endif

        <!-- Performance Overview -->
        <div class="performance-overview">
            <div class="section-header">
                <h2>
                    <i class="fas fa-chart-line"></i>
                    نظرة عامة على الأداء
                </h2>
            </div>

            <div class="stats-grid">
                <div class="stat-card-modern primary">
                    <div class="stat-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number">{{ $totalTasks }}</div>
                        <div class="stat-label">إجمالي المهام</div>
                    </div>
                </div>

                <div class="stat-card-modern success">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number">{{ $completedTasks }}</div>
                        <div class="stat-label">المهام المكتملة</div>
                    </div>
                </div>

                <div class="stat-card-modern info">
                    <div class="stat-icon">
                        <i class="fas fa-project-diagram"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number">{{ number_format($totalProjectShare ?? $projectStats['total'], 1) }}</div>
                        <div class="stat-label">نسبة المشاريع</div>
                        <div class="stat-sublabel">{{ $employeeProjects->count() }} مشروع</div>
                    </div>
                </div>

                <div class="stat-card-modern success">
                    <div class="stat-icon">
                        <i class="fas fa-check-double"></i>
                    </div>
                                                                <div class="stat-content">
                                                  <div class="stat-number position-relative clickable-stat"
                              data-bs-toggle="modal"
                              data-bs-target="#projectCompletionModal"
                              title="انقر لعرض التفاصيل">
                             {{ number_format($projectCompletionPoints ?? 0, 1) }}
                             <i class="fas fa-chart-pie text-info ms-1" style="font-size: 0.9em;"></i>
                         </div>
                         <div class="stat-label">نقاط المشاريع المكتملة</div>
                         <div class="stat-trend">{{ $projectCompletionRate }}% مكتمل</div>
                      </div>
                </div>

                <div class="stat-card-modern warning">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"
                             id="employee-actual-timer"
                             data-initial-minutes="{{ (int)($timeStats['spent_minutes'] ?? 0) }}"
                             data-active-count="{{ (int)($timeStats['active_tasks_count'] ?? 0) }}"
                             data-started-at="{{ now()->timestamp * 1000 }}">
                            @php
                                $totalMinutes = (int)($timeStats['spent_minutes'] ?? 0);
                                $hours = intval($totalMinutes / 60);
                                $minutes = $totalMinutes % 60;
                            @endphp
                            {{ sprintf('%d:%02d:%02d', $hours, $minutes, 0) }}
                            @if(($timeStats['active_tasks_count'] ?? 0) > 0)
                                <span class="real-time-indicator" style="color: #28a745; font-size: 0.8em; margin-left: 5px;">●</span>
                            @endif
                        </div>
                        <div class="stat-label">وقت العمل (ساعات:دقائق:ثواني)
                            @if(($timeStats['active_tasks_count'] ?? 0) > 0)
                                <span style="color: #28a745; font-size: 0.9em;"> - نشط الآن</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="stat-card-modern success">
                    <div class="stat-icon">
                        <i class="fas fa-expand-arrows-alt"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number">{{ round(($timeStats['flexible_spent_minutes'] ?? 0) / 60, 1) }}</div>
                        <div class="stat-label">ساعات مرنة</div>
                        <div class="stat-sublabel">مهام بدون وقت محدد</div>
                    </div>
                </div>
            </div>

            <div class="performance-chart">
                <div class="chart-item">
                    <div class="chart-circle completion" style="--percentage: {{ $completionRate }}">
                        <span class="chart-value">{{ $completionRate }}%</span>
                    </div>
                    <div class="chart-label">معدل إنجاز المهام</div>
                </div>

                <div class="chart-item">
                                            <div class="chart-circle time-compliance" style="--percentage: {{ $timeStats['efficiency'] }}">
                            <span class="chart-value">{{ $timeStats['efficiency'] }}%</span>
                    </div>
                    <div class="chart-label">الالتزام بالوقت المقدر</div>
                </div>

                <div class="chart-item">
                    <div class="chart-circle completion" style="--percentage: {{ $projectCompletionRate }}">
                        <span class="chart-value">{{ $projectCompletionRate }}%</span>
                    </div>
                    <div class="chart-label">نسبة إنجاز المشاريع</div>
                </div>

                <div class="chart-item">
                    <div class="time-stats">
                        <div class="time-stat">
                            <div class="time-value">{{ round($timeStats['estimated_minutes'] / 60, 1) }}</div>
                            <div class="time-label">ساعات مقدرة</div>
                        </div>
                        <div class="time-stat">
                            <div class="time-value"
                                 id="employee-time-hours"
                                 data-initial-minutes="{{ (int)($timeStats['spent_minutes'] ?? 0) }}"
                                 data-active-count="{{ (int)($timeStats['active_tasks_count'] ?? 0) }}"
                                 data-started-at="{{ now()->timestamp * 1000 }}">
                                @php
                                    $totalMinutes = (int)($timeStats['spent_minutes'] ?? 0);
                                    $hours = intval($totalMinutes / 60);
                                    $minutes = $totalMinutes % 60;
                                @endphp
                                {{ sprintf('%d:%02d:%02d', $hours, $minutes, 0) }}
                                @if(($timeStats['active_tasks_count'] ?? 0) > 0)
                                    <span class="real-time-addition" style="color: #28a745; font-size: 0.8em;">●</span>
                                @endif
                            </div>
                            <div class="time-label">وقت فعلي</div>
                        </div>
                        <div class="time-stat flexible">
                            <div class="time-value">{{ round(($timeStats['flexible_spent_minutes'] ?? 0) / 60, 1) }}</div>
                            <div class="time-label">ساعات مرنة</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Projects Status Overview Section -->
        <div class="section-modern">
            <div class="section-header">
                <h2>
                    <i class="fas fa-project-diagram"></i>
                    حالات المشاريع
                </h2>
            </div>

            <div class="stats-grid">
                <!-- جاري التنفيذ -->
                <div class="stat-card-modern primary">
                    <div class="stat-icon">
                        <i class="fas fa-play-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number">{{ number_format($projectStats['in_progress'] ?? 0, 1) }}</div>
                        <div class="stat-label">جاري التنفيذ</div>
                        <div class="stat-trend">
                            {{ $projectStats['in_progress'] > 0 ? round(($projectStats['in_progress'] / max($projectStats['total'], 1)) * 100) : 0 }}% من الإجمالي
                        </div>
                    </div>
                </div>

                <!-- واقف على النموذج -->
                <div class="stat-card-modern warning">
                    <div class="stat-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number">{{ number_format($projectStats['waiting_form'] ?? 0, 1) }}</div>
                        <div class="stat-label">واقف على النموذج</div>
                        <div class="stat-trend">
                            <i class="fas fa-hourglass-half"></i>
                            في انتظار النموذج
                        </div>
                    </div>
                </div>

                <!-- واقف على الأسئلة -->
                <div class="stat-card-modern info">
                    <div class="stat-icon">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number">{{ number_format($projectStats['waiting_questions'] ?? 0, 1) }}</div>
                        <div class="stat-label">واقف على الأسئلة</div>
                        <div class="stat-trend">
                            <i class="fas fa-hourglass-half"></i>
                            في انتظار الإجابات
                        </div>
                    </div>
                </div>

                <!-- واقف على العميل -->
                <div class="stat-card-modern warning">
                    <div class="stat-icon">
                        <i class="fas fa-user-clock"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number">{{ number_format($projectStats['waiting_client'] ?? 0, 1) }}</div>
                        <div class="stat-label">واقف على العميل</div>
                        <div class="stat-trend">
                            <i class="fas fa-hourglass-half"></i>
                            في انتظار العميل
                        </div>
                    </div>
                </div>

                <!-- واقف على مكالمة -->
                <div class="stat-card-modern info">
                    <div class="stat-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number">{{ number_format($projectStats['waiting_call'] ?? 0, 1) }}</div>
                        <div class="stat-label">واقف على مكالمة</div>
                        <div class="stat-trend">
                            <i class="fas fa-hourglass-half"></i>
                            في انتظار المكالمة
                        </div>
                    </div>
                </div>

                <!-- موقوف -->
                <div class="stat-card-modern" style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); border: none;">
                    <div class="stat-icon" style="background: rgba(255, 255, 255, 0.25);">
                        <i class="fas fa-pause-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number" style="color: white; -webkit-text-fill-color: white; background: none;">{{ number_format($projectStats['paused'] ?? 0, 1) }}</div>
                        <div class="stat-label" style="color: rgba(255, 255, 255, 0.95);">موقوف</div>
                        <div class="stat-trend" style="background: rgba(255, 255, 255, 0.2); color: white;">
                            <i class="fas fa-pause"></i>
                            مشاريع متوقفة
                        </div>
                    </div>
                </div>

                <!-- تسليم مسودة -->
                <div class="stat-card-modern" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); border: none;">
                    <div class="stat-icon" style="background: rgba(255, 255, 255, 0.25);">
                        <i class="fas fa-file-upload"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number" style="color: white; -webkit-text-fill-color: white; background: none;">{{ number_format($projectStats['draft_delivery'] ?? 0, 1) }}</div>
                        <div class="stat-label" style="color: rgba(255, 255, 255, 0.95);">تسليم مسودة</div>
                        <div class="stat-trend" style="background: rgba(255, 255, 255, 0.2); color: white;">
                            <i class="fas fa-file"></i>
                            مسودة مسلمة
                        </div>
                    </div>
                </div>

                <!-- تم تسليم نهائي -->
                <div class="stat-card-modern" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); border: none;">
                    <div class="stat-icon" style="background: rgba(255, 255, 255, 0.25);">
                        <i class="fas fa-check-double"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number" style="color: white; -webkit-text-fill-color: white; background: none;">{{ number_format($projectStats['final_delivery'] ?? 0, 1) }}</div>
                        <div class="stat-label" style="color: rgba(255, 255, 255, 0.95);">تم تسليم نهائي</div>
                        <div class="stat-trend" style="background: rgba(255, 255, 255, 0.2); color: white;">
                            <i class="fas fa-trophy"></i>
                            {{ $projectStats['final_delivery'] > 0 ? round(($projectStats['final_delivery'] / max($projectStats['total'], 1)) * 100) : 0 }}% مكتمل
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tasks Overview Section -->
        <div class="section-modern">
            <div class="section-header">
                <h2>
                    <i class="fas fa-tasks"></i>
                    نظرة عامة على المهام
                </h2>
            </div>

            <div class="tasks-overview-grid">
                <div class="task-summary-card">
                    <div class="task-icon all">
                        <i class="fas fa-list"></i>
                    </div>
                    <div class="task-info">
                        <div class="task-number">{{ $taskStats['combined']['total'] }}</div>
                        <div class="task-label">إجمالي المهام</div>
                    </div>
                </div>

                <div class="task-summary-card">
                    <div class="task-icon new">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <div class="task-info">
                        <div class="task-number">{{ $taskStats['combined']['new'] }}</div>
                        <div class="task-label">جديدة</div>
                    </div>
                </div>

                <div class="task-summary-card">
                    <div class="task-icon progress">
                        <i class="fas fa-spinner"></i>
                    </div>
                    <div class="task-info">
                        <div class="task-number">{{ $taskStats['combined']['in_progress'] }}</div>
                        <div class="task-label">قيد التنفيذ</div>
                    </div>
                </div>

                <div class="task-summary-card">
                    <div class="task-icon paused">
                        <i class="fas fa-pause"></i>
                    </div>
                    <div class="task-info">
                        <div class="task-number">{{ $taskStats['combined']['paused'] }}</div>
                        <div class="task-label">متوقفة</div>
                    </div>
                </div>

                <div class="task-summary-card">
                    <div class="task-icon completed">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="task-info">
                        <div class="task-number">{{ $taskStats['combined']['completed'] }}</div>
                        <div class="task-label">مكتملة</div>
                    </div>
                </div>

                @if(isset($combinedTaskStats))
                <!-- المهام الأصلية -->
                <div class="task-summary-card" style="border-top: 4px solid #3498db;">
                    <div class="task-icon" style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="task-info">
                        <div class="task-number">{{ $combinedTaskStats['all_original']['total'] }}</div>
                        <div class="task-label">مهام أصلية</div>
                    </div>
                </div>

                <!-- المهام الإضافية -->
                <div class="task-summary-card" style="border-top: 4px solid #e67e22;">
                    <div class="task-icon" style="background: linear-gradient(135deg, #e67e22 0%, #d35400 100%);">
                        <i class="fas fa-plus-square"></i>
                    </div>
                    <div class="task-info">
                        <div class="task-number">{{ $combinedTaskStats['all_additional']['total'] }}</div>
                        <div class="task-label">مهام إضافية</div>
                    </div>
                </div>

                <!-- المهام المنقولة -->
                @if(isset($transferStats) && isset($transferStats['transferred_from_me']))
                <div class="task-summary-card" style="border-top: 4px solid #6c757d;">
                    <div class="task-icon" style="background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <div class="task-info">
                        <div class="task-number">{{ $transferStats['transferred_from_me'] }}</div>
                        <div class="task-label">مهام منقولة</div>
                    </div>
                </div>
                @endif
                @endif

                <div class="task-summary-card completion-rate">
                    <div class="completion-circle">
                        <svg viewBox="0 0 36 36" class="circular-chart">
                            <path class="circle-bg" d="M18 2.0845
                                a 15.9155 15.9155 0 0 1 0 31.831
                                a 15.9155 15.9155 0 0 1 0 -31.831"/>
                            <path class="circle" stroke-dasharray="{{ $taskStats['combined']['total'] > 0 ? round(($taskStats['combined']['completed'] / $taskStats['combined']['total']) * 100) : 0 }}, 100" d="M18 2.0845
                                a 15.9155 15.9155 0 0 1 0 31.831
                                a 15.9155 15.9155 0 0 1 0 -31.831"/>
                            <text x="18" y="20.35" class="percentage">{{ $taskStats['combined']['total'] > 0 ? round(($taskStats['combined']['completed'] / $taskStats['combined']['total']) * 100) : 0 }}%</text>
                        </svg>
                    </div>
                    <div class="task-info">
                        <div class="task-label">معدل إنجاز المهام</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Analytics & Charts -->
        <div class="employee-analytics">
            <!-- Projects Analytics -->
            <div class="analytics-section">
                <div class="section-header">
                    <h3>
                        <i class="fas fa-chart-pie"></i>
                        تحليل المشاريع
                    </h3>
                </div>
                <div class="analytics-grid">
                    <div class="analytics-card">
                        <div class="chart-container">
                            <canvas id="projectStatusChart"></canvas>
                        </div>
                        <div class="chart-legend">
                            <div class="legend-item progress">
                                <span class="legend-color" style="background: #3498db;"></span>
                                جاري: {{ number_format($projectStats['in_progress'] ?? 0, 1) }}
                            </div>
                            <div class="legend-item" style="color: #f39c12;">
                                <span class="legend-color" style="background: #f39c12;"></span>
                                واقف (نموذج): {{ number_format($projectStats['waiting_form'] ?? 0, 1) }}
                            </div>
                            <div class="legend-item" style="color: #3498db;">
                                <span class="legend-color" style="background: #3498db;"></span>
                                واقف (أسئلة): {{ number_format($projectStats['waiting_questions'] ?? 0, 1) }}
                            </div>
                            <div class="legend-item" style="color: #e67e22;">
                                <span class="legend-color" style="background: #e67e22;"></span>
                                واقف (عميل): {{ number_format($projectStats['waiting_client'] ?? 0, 1) }}
                            </div>
                            <div class="legend-item" style="color: #9b59b6;">
                                <span class="legend-color" style="background: #9b59b6;"></span>
                                واقف (مكالمة): {{ number_format($projectStats['waiting_call'] ?? 0, 1) }}
                            </div>
                            <div class="legend-item" style="color: #e74c3c;">
                                <span class="legend-color" style="background: #e74c3c;"></span>
                                موقوف: {{ number_format($projectStats['paused'] ?? 0, 1) }}
                            </div>
                            <div class="legend-item" style="color: #f093fb;">
                                <span class="legend-color" style="background: #f093fb;"></span>
                                تسليم مسودة: {{ number_format($projectStats['draft_delivery'] ?? 0, 1) }}
                            </div>
                            <div class="legend-item completed">
                                <span class="legend-color" style="background: #00f2fe;"></span>
                                <span style="cursor: pointer;"
                                      data-bs-toggle="modal"
                                      data-bs-target="#projectCompletionModal">
                                    تسليم نهائي: {{ number_format($projectStats['final_delivery'] ?? 0, 1) }}
                                    <i class="fas fa-chart-bar text-info ms-1" style="font-size: 0.7em;"></i>
                                </span>
                            </div>
                        </div>
                    </div>

                    @if($overdueProjects->count() > 0)
                    <div class="analytics-card overdue-projects">
                        <div class="card-header">
                            <h4>
                                <i class="fas fa-exclamation-triangle"></i>
                                مشاريع متأخرة
                            </h4>
                            <span class="overdue-count">{{ $overdueProjects->count() }}</span>
                        </div>
                        <div class="overdue-list">
                            @foreach($overdueProjects->take(3) as $project)
                            <div class="overdue-item">
                                <div class="project-name">{{ $project->name }}</div>
                                <div class="overdue-days">
                                    @php
                                        $deliveryDate = $project->client_agreed_delivery_date ?? $project->team_delivery_date;
                                    @endphp
                                    متأخر {{ \Carbon\Carbon::parse($deliveryDate)->diffInDays(now()) }} يوم
                                </div>
                            </div>
                            @endforeach
                            @if($overdueProjects->count() > 3)
                            <div class="more-projects">و{{ $overdueProjects->count() - 3 }} مشروع آخر...</div>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Project Overdue Stats -->
                @if($projectOverdueStats['total_overdue'] > 0)
                <div class="overdue-stats-section">
                    <div class="overdue-header">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h4>إحصائيات تأخير المشاريع</h4>
                    </div>
                    <div class="overdue-cards-grid">
                        <div class="overdue-card warning">
                            <div class="overdue-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="overdue-content">
                                <div class="overdue-number">{{ $projectOverdueStats['overdue_active'] }}</div>
                                <div class="overdue-label">متأخرة ولم تكتمل</div>
                                <div class="overdue-description">مشاريع تجاوزت الموعد</div>
                            </div>
                        </div>

                        <div class="overdue-card info">
                            <div class="overdue-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="overdue-content">
                                <div class="overdue-number">{{ $projectOverdueStats['completed_late'] }}</div>
                                <div class="overdue-label">مكتملة بتأخير</div>
                                <div class="overdue-description">اكتملت بعد الموعد المحدد</div>
                            </div>
                        </div>

                        <div class="overdue-card danger">
                            <div class="overdue-icon">
                                <i class="fas fa-exclamation-circle"></i>
                            </div>
                            <div class="overdue-content">
                                <div class="overdue-number">{{ $projectOverdueStats['total_overdue'] }}</div>
                                <div class="overdue-label">إجمالي المتأخرة</div>
                                <div class="overdue-description">جميع المشاريع المتأخرة</div>
                            </div>
                        </div>
                    </div>

                    <!-- Project Overdue Details -->
                    @if(count($projectOverdueStats['overdue_projects']) > 0 || count($projectOverdueStats['completed_late_projects']) > 0)
                    <div class="overdue-details-section">
                        <h5 class="overdue-details-title">
                            <i class="fas fa-list"></i>
                            تفاصيل المشاريع المتأخرة
                        </h5>
                        <div class="overdue-projects-list">
                            @foreach($projectOverdueStats['overdue_projects'] as $item)
                            <div class="overdue-project-item">
                                <div class="overdue-project-header">
                                    <h6 class="overdue-project-name">
                                        <i class="fas fa-project-diagram"></i>
                                        {{ $item['project']->name }}
                                    </h6>
                                    <span class="overdue-badge danger">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        متأخر {{ $item['participants'][0]['days_overdue'] }} يوم
                                    </span>
                                </div>
                                <div class="overdue-project-participants">
                                    <strong>نسبة المشاركة:</strong>
                                    <span class="participant-badge">
                                        <i class="fas fa-percentage"></i>
                                        {{ $item['participants'][0]['project_share'] }}%
                                    </span>
                                </div>
                            </div>
                            @endforeach

                            @foreach($projectOverdueStats['completed_late_projects'] as $item)
                            <div class="overdue-project-item completed">
                                <div class="overdue-project-header">
                                    <h6 class="overdue-project-name">
                                        <i class="fas fa-project-diagram"></i>
                                        {{ $item['project']->name }}
                                    </h6>
                                    <span class="overdue-badge info">
                                        <i class="fas fa-check-circle"></i>
                                        اكتمل بتأخير {{ $item['participants'][0]['days_late'] }} يوم
                                    </span>
                                </div>
                                <div class="overdue-project-participants">
                                    <strong>نسبة المشاركة:</strong>
                                    <span class="participant-badge">
                                        <i class="fas fa-percentage"></i>
                                        {{ $item['participants'][0]['project_share'] }}%
                                    </span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
                @endif
            </div>

            <!-- Tasks Analytics -->
            <div class="analytics-section">
                <div class="section-header">
                    <h3>
                        <i class="fas fa-chart-bar"></i>
                        تحليل المهام
                    </h3>
                </div>
                <div class="analytics-grid">
                    <div class="analytics-card">
                        <div class="chart-container">
                            <canvas id="tasksChart"></canvas>
                        </div>
                    </div>

                    <div class="analytics-card tasks-breakdown">
                        <div class="card-header">
                            <h4>تفصيل أنواع المهام</h4>
                        </div>
                        <div class="task-types">
                            <div class="task-type regular">
                                <div class="task-type-header">
                                    <i class="fas fa-clipboard-list"></i>
                                    <span>المهام العادية</span>
                                </div>
                                <div class="task-stats-grid">
                                    <div class="task-stat">
                                        <span class="count">{{ $taskStats['regular']['total'] }}</span>
                                        <span class="label">إجمالي</span>
                                    </div>
                                    <div class="task-stat">
                                        <span class="count">{{ $taskStats['regular']['completed'] }}</span>
                                        <span class="label">مكتملة</span>
                                    </div>
                                    <div class="task-stat">
                                        <span class="count">{{ $taskStats['regular']['in_progress'] }}</span>
                                        <span class="label">قيد التنفيذ</span>
                                    </div>
                                    <div class="task-stat">
                                        <span class="count">{{ $taskStats['regular']['paused'] }}</span>
                                        <span class="label">متوقفة</span>
                                    </div>
                                    <div class="task-stat">
                                        <span class="count">{{ $taskStats['regular']['transferred_out'] ?? 0 }}</span>
                                        <span class="label">منقولة</span>
                                    </div>
                                </div>
                            </div>

                            <div class="task-type template">
                                <div class="task-type-header">
                                    <i class="fas fa-layer-group"></i>
                                    <span>مهام القوالب</span>
                                </div>
                                <div class="task-stats-grid">
                                    <div class="task-stat">
                                        <span class="count">{{ $taskStats['template']['total'] }}</span>
                                        <span class="label">إجمالي</span>
                                    </div>
                                    <div class="task-stat">
                                        <span class="count">{{ $taskStats['template']['completed'] }}</span>
                                        <span class="label">مكتملة</span>
                                    </div>
                                    <div class="task-stat">
                                        <span class="count">{{ $taskStats['template']['in_progress'] }}</span>
                                        <span class="label">قيد التنفيذ</span>
                                    </div>
                                    <div class="task-stat">
                                        <span class="count">{{ $taskStats['template']['paused'] }}</span>
                                        <span class="label">متوقفة</span>
                                    </div>
                                    <div class="task-stat">
                                        <span class="count">{{ $taskStats['template']['transferred_out'] ?? 0 }}</span>
                                        <span class="label">منقولة</span>
                                    </div>
                                </div>
                            </div>

                            @if(isset($combinedTaskStats))
                            <!-- المهام الأصلية -->
                            <div class="task-type" style="background: linear-gradient(135deg, #ecf5ff 0%, #e3f2fd 100%); border-right: 4px solid #3498db;">
                                <div class="task-type-header">
                                    <i class="fas fa-tasks"></i>
                                    <span>المهام الأصلية</span>
                                </div>
                                <div class="task-stats-grid">
                                    <div class="task-stat">
                                        <span class="count">{{ $combinedTaskStats['all_original']['total'] }}</span>
                                        <span class="label">إجمالي</span>
                                    </div>
                                    <div class="task-stat">
                                        <span class="count">{{ $combinedTaskStats['all_original']['completed'] }}</span>
                                        <span class="label">مكتملة</span>
                                    </div>
                                    <div class="task-stat">
                                        <span class="count">{{ $combinedTaskStats['all_original']['in_progress'] }}</span>
                                        <span class="label">قيد التنفيذ</span>
                                    </div>
                                    <div class="task-stat">
                                        <span class="count">{{ $combinedTaskStats['all_original']['paused'] }}</span>
                                        <span class="label">متوقفة</span>
                                    </div>
                                </div>
                            </div>

                            <!-- المهام الإضافية -->
                            <div class="task-type" style="background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%); border-right: 4px solid #e67e22;">
                                <div class="task-type-header">
                                    <i class="fas fa-plus-square"></i>
                                    <span>المهام الإضافية</span>
                                </div>
                                <div class="task-stats-grid">
                                    <div class="task-stat">
                                        <span class="count">{{ $combinedTaskStats['all_additional']['total'] }}</span>
                                        <span class="label">إجمالي</span>
                                    </div>
                                    <div class="task-stat">
                                        <span class="count">{{ $combinedTaskStats['all_additional']['completed'] }}</span>
                                        <span class="label">مكتملة</span>
                                    </div>
                                    <div class="task-stat">
                                        <span class="count">{{ $combinedTaskStats['all_additional']['in_progress'] }}</span>
                                        <span class="label">قيد التنفيذ</span>
                                    </div>
                                    <div class="task-stat">
                                        <span class="count">{{ $combinedTaskStats['all_additional']['paused'] }}</span>
                                        <span class="label">متوقفة</span>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Task Overdue Stats -->
                @if($taskOverdueStats['total_overdue'] > 0)
                <div class="overdue-stats-section">
                    <div class="overdue-header">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h4>إحصائيات تأخير المهام</h4>
                    </div>
                    <div class="overdue-cards-grid">
                        <div class="overdue-card warning">
                            <div class="overdue-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="overdue-content">
                                <div class="overdue-number">{{ $taskOverdueStats['overdue_pending'] }}</div>
                                <div class="overdue-label">متأخرة ولم تكتمل</div>
                                <div class="overdue-description">
                                    عادية: {{ $taskOverdueStats['regular_overdue_pending'] }} |
                                    قوالب: {{ $taskOverdueStats['template_overdue_pending'] }}
                                </div>
                            </div>
                        </div>

                        <div class="overdue-card info">
                            <div class="overdue-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="overdue-content">
                                <div class="overdue-number">{{ $taskOverdueStats['completed_late'] }}</div>
                                <div class="overdue-label">مكتملة بتأخير</div>
                                <div class="overdue-description">
                                    عادية: {{ $taskOverdueStats['regular_completed_late'] }} |
                                    قوالب: {{ $taskOverdueStats['template_completed_late'] }}
                                </div>
                            </div>
                        </div>

                        <div class="overdue-card danger">
                            <div class="overdue-icon">
                                <i class="fas fa-exclamation-circle"></i>
                            </div>
                            <div class="overdue-content">
                                <div class="overdue-number">{{ $taskOverdueStats['total_overdue'] }}</div>
                                <div class="overdue-label">إجمالي المتأخرة</div>
                                <div class="overdue-description">جميع المهام المتأخرة</div>
                            </div>
                        </div>
                    </div>

                    <!-- Task Overdue Details -->
                    @if($taskOverdueDetails['overdue_pending']->count() > 0 || $taskOverdueDetails['completed_late']->count() > 0)
                    <div class="overdue-details-section">
                        <h5 class="overdue-details-title">
                            <i class="fas fa-list"></i>
                            تفاصيل المهام المتأخرة
                        </h5>
                        <div class="overdue-projects-list">
                            @if($taskOverdueDetails['overdue_pending']->count() > 0)
                            <div class="overdue-project-item">
                                <div class="overdue-project-header">
                                    <h6 class="overdue-project-name">
                                        <i class="fas fa-clock"></i>
                                        مهام متأخرة ولم تكتمل
                                    </h6>
                                    <span class="overdue-badge danger">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        {{ $taskOverdueDetails['overdue_pending']->count() }} مهمة
                                    </span>
                                </div>
                                <div class="overdue-project-participants">
                                    <div class="participants-list">
                                        @foreach($taskOverdueDetails['overdue_pending'] as $task)
                                        <span class="participant-badge">
                                            <i class="fas fa-{{ $task->task_type == 'template' ? 'layer-group' : 'tasks' }}"></i>
                                            {{ \Str::limit($task->task_name, 30) }}
                                            <small>({{ $task->task_type == 'template' ? 'قالب' : 'عادية' }} - متأخرة {{ $task->days_overdue }} يوم)</small>
                                        </span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            @endif

                            @if($taskOverdueDetails['completed_late']->count() > 0)
                            <div class="overdue-project-item completed">
                                <div class="overdue-project-header">
                                    <h6 class="overdue-project-name">
                                        <i class="fas fa-check-circle"></i>
                                        مهام مكتملة بتأخير
                                    </h6>
                                    <span class="overdue-badge info">
                                        <i class="fas fa-check-circle"></i>
                                        {{ $taskOverdueDetails['completed_late']->count() }} مهمة
                                    </span>
                                </div>
                                <div class="overdue-project-participants">
                                    <div class="participants-list">
                                        @foreach($taskOverdueDetails['completed_late'] as $task)
                                        <span class="participant-badge">
                                            <i class="fas fa-{{ $task->task_type == 'template' ? 'layer-group' : 'tasks' }}"></i>
                                            {{ \Str::limit($task->task_name, 30) }}
                                            <small>({{ $task->task_type == 'template' ? 'قالب' : 'عادية' }} - اكتملت بتأخير {{ $task->days_late }} يوم)</small>
                                        </span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
                @endif
            </div>

            <!-- Monthly Performance -->
            <div class="analytics-section">
                <div class="section-header">
                    <h3>
                        <i class="fas fa-chart-line"></i>
                        الأداء الشهري
                    </h3>
                </div>
                <div class="analytics-grid">
                    <div class="analytics-card performance-trend">
                        <div class="chart-container">
                            <canvas id="monthlyChart"></canvas>
                        </div>
                    </div>

                    <div class="analytics-card efficiency">
                        <div class="card-header">
                            <h4>كفاءة الوقت</h4>
                        </div>
                        <div class="efficiency-circle">
                            <div class="circle-progress" style="--percentage: {{ $timeStats['efficiency'] }}">
                                <span class="percentage">{{ $timeStats['efficiency'] }}%</span>
                            </div>
                            <div class="efficiency-label">كفاءة استخدام الوقت</div>
                        </div>
                        <div class="time-details">
                            <div class="time-item estimated">
                                <span class="time-value">{{ $timeStats['estimated_formatted'] }}</span>
                                <span class="time-label">وقت مقدر</span>
                            </div>
                            <div class="time-item actual">
                                <span class="time-value"
                                      id="employee-efficiency-timer"
                                      data-initial-minutes="{{ (int)($timeStats['spent_minutes'] ?? 0) }}"
                                      data-active-count="{{ (int)($timeStats['active_tasks_count'] ?? 0) }}"
                                      data-started-at="{{ now()->timestamp * 1000 }}">
                                    @php
                                        $totalMinutes = (int)($timeStats['spent_minutes'] ?? 0);
                                        $hours = intval($totalMinutes / 60);
                                        $minutes = $totalMinutes % 60;
                                    @endphp
                                    {{ sprintf('%d:%02d:%02d', $hours, $minutes, 0) }}
                                    @if(($timeStats['active_tasks_count'] ?? 0) > 0)
                                        <span class="real-time-addition" style="color: #28a745; font-size: 0.8em;">●</span>
                                    @endif
                                </span>
                                <span class="time-label">وقت فعلي</span>
                            </div>
                            <div class="time-item flexible">
                                <span class="time-value">{{ $timeStats['flexible_spent_formatted'] ?? '0h' }}</span>
                                <span class="time-label">وقت مرن</span>
                                <span class="time-note">مهام بدون وقت محدد</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Revisions Analytics - Modern Design -->
            @if($revisionStats['total'] > 0)
            <div class="section-modern">
                <div class="section-header">
                    <h2>
                        <i class="fas fa-clipboard-check"></i>
                        إحصائيات التعديلات
                    </h2>
                    <span class="section-badge">{{ $revisionStats['total'] }} تعديل</span>
                </div>

                <!-- Main Stats Cards -->
                <div class="revision-stats-modern-grid">
                    <!-- Total Revisions Card -->
                    <div class="revision-stat-modern total">
                        <div class="stat-icon-modern">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <div class="stat-content-modern">
                            <div class="stat-number-modern">{{ $revisionStats['total'] }}</div>
                            <div class="stat-label-modern">إجمالي التعديلات</div>
                        </div>
                        <div class="stat-badge-modern">الكل</div>
                    </div>

                    <!-- Pending Revisions Card -->
                    <div class="revision-stat-modern pending">
                        <div class="stat-icon-modern">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-content-modern">
                            <div class="stat-number-modern">{{ $revisionStats['pending'] }}</div>
                            <div class="stat-label-modern">معلقة</div>
                        </div>
                        <div class="stat-progress-modern" style="--progress: {{ $revisionStats['total'] > 0 ? round(($revisionStats['pending'] / $revisionStats['total']) * 100) : 0 }}%"></div>
                    </div>

                    <!-- Approved Revisions Card -->
                    <div class="revision-stat-modern approved">
                        <div class="stat-icon-modern">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-content-modern">
                            <div class="stat-number-modern">{{ $revisionStats['approved'] }}</div>
                            <div class="stat-label-modern">موافق عليها</div>
                        </div>
                        <div class="stat-progress-modern" style="--progress: {{ $revisionStats['total'] > 0 ? round(($revisionStats['approved'] / $revisionStats['total']) * 100) : 0 }}%"></div>
                    </div>

                    <!-- Rejected Revisions Card -->
                    <div class="revision-stat-modern rejected">
                        <div class="stat-icon-modern">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="stat-content-modern">
                            <div class="stat-number-modern">{{ $revisionStats['rejected'] }}</div>
                            <div class="stat-label-modern">مرفوضة</div>
                        </div>
                        <div class="stat-progress-modern" style="--progress: {{ $revisionStats['total'] > 0 ? round(($revisionStats['rejected'] / $revisionStats['total']) * 100) : 0 }}%"></div>
                    </div>

                    <!-- Approval Rate Card -->
                    <div class="revision-stat-modern approval-rate">
                        <div class="approval-circle-modern">
                            <svg viewBox="0 0 36 36">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                                <path class="circle-progress" stroke-dasharray="{{ $revisionStats['approval_rate'] }}, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                                <text x="18" y="20.35" class="percentage">{{ $revisionStats['approval_rate'] }}%</text>
                            </svg>
                        </div>
                        <div class="stat-label-modern">معدل الموافقة</div>
                    </div>

                    <!-- Internal/External Stats -->
                    <div class="revision-stat-modern internal">
                        <div class="stat-icon-modern">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="stat-content-modern">
                            <div class="stat-number-modern">{{ $revisionStats['internal'] ?? 0 }}</div>
                            <div class="stat-label-modern">داخلية</div>
                        </div>
                    </div>

                    <div class="revision-stat-modern external">
                        <div class="stat-icon-modern">
                            <i class="fas fa-globe"></i>
                        </div>
                        <div class="stat-content-modern">
                            <div class="stat-number-modern">{{ $revisionStats['external'] ?? 0 }}</div>
                            <div class="stat-label-modern">خارجية</div>
                        </div>
                    </div>

                    <!-- ✅ As Responsible (مرتكب الخطأ) -->
                    @if(isset($revisionStats['as_responsible']) && $revisionStats['as_responsible'] > 0)
                    <div class="revision-stat-modern responsible">
                        <div class="stat-icon-modern">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="stat-content-modern">
                            <div class="stat-number-modern">{{ $revisionStats['as_responsible'] }}</div>
                            <div class="stat-label-modern">مسؤول عن خطأ</div>
                        </div>
                        <div class="stat-badge-modern">مرتكب</div>
                    </div>
                    @endif

                    <!-- ✅ As Executor (منفذ) -->
                    @if(isset($revisionStats['as_executor']) && $revisionStats['as_executor'] > 0)
                    <div class="revision-stat-modern executor">
                        <div class="stat-icon-modern">
                            <i class="fas fa-hammer"></i>
                        </div>
                        <div class="stat-content-modern">
                            <div class="stat-number-modern">{{ $revisionStats['as_executor'] }}</div>
                            <div class="stat-label-modern">منفذ</div>
                        </div>
                        <div class="stat-badge-modern">تنفيذ</div>
                    </div>
                    @endif

                    <!-- ✅ As Reviewer (مراجع) -->
                    @if(isset($revisionStats['as_reviewer']) && $revisionStats['as_reviewer'] > 0)
                    <div class="revision-stat-modern reviewer">
                        <div class="stat-icon-modern">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div class="stat-content-modern">
                            <div class="stat-number-modern">{{ $revisionStats['as_reviewer'] }}</div>
                            <div class="stat-label-modern">مراجع</div>
                        </div>
                        <div class="stat-badge-modern">مراجعة</div>
                    </div>
                    @endif
                </div>

                <!-- Categories Breakdown -->
                @if(isset($revisionsByCategory))
                <div class="revision-categories-modern">
                    <h3 class="categories-title">
                        <i class="fas fa-layer-group"></i>
                        التصنيف حسب النوع
                    </h3>
                    <div class="categories-grid">
                        <!-- Task Revisions -->
                        <div class="category-card-modern task">
                            <div class="category-header-modern">
                                <div class="category-icon-modern">
                                    <i class="fas fa-tasks"></i>
                                </div>
                                <h4>تعديلات المهام</h4>
                            </div>
                            <div class="category-stats-modern">
                                <div class="category-stat">
                                    <span class="number">{{ $revisionsByCategory['task_revisions']['total'] ?? 0 }}</span>
                                    <span class="label">الكل</span>
                                </div>
                                <div class="category-stat pending">
                                    <span class="number">{{ $revisionsByCategory['task_revisions']['pending'] ?? 0 }}</span>
                                    <span class="label">معلق</span>
                                </div>
                                <div class="category-stat approved">
                                    <span class="number">{{ $revisionsByCategory['task_revisions']['approved'] ?? 0 }}</span>
                                    <span class="label">مقبول</span>
                                </div>
                                <div class="category-stat rejected">
                                    <span class="number">{{ $revisionsByCategory['task_revisions']['rejected'] ?? 0 }}</span>
                                    <span class="label">مرفوض</span>
                                </div>
                            </div>
                        </div>

                        <!-- Project Revisions -->
                        <div class="category-card-modern project">
                            <div class="category-header-modern">
                                <div class="category-icon-modern">
                                    <i class="fas fa-project-diagram"></i>
                                </div>
                                <h4>تعديلات المشروع</h4>
                            </div>
                            <div class="category-stats-modern">
                                <div class="category-stat">
                                    <span class="number">{{ $revisionsByCategory['project_revisions']['total'] ?? 0 }}</span>
                                    <span class="label">الكل</span>
                                </div>
                                <div class="category-stat pending">
                                    <span class="number">{{ $revisionsByCategory['project_revisions']['pending'] ?? 0 }}</span>
                                    <span class="label">معلق</span>
                                </div>
                                <div class="category-stat approved">
                                    <span class="number">{{ $revisionsByCategory['project_revisions']['approved'] ?? 0 }}</span>
                                    <span class="label">مقبول</span>
                                </div>
                                <div class="category-stat rejected">
                                    <span class="number">{{ $revisionsByCategory['project_revisions']['rejected'] ?? 0 }}</span>
                                    <span class="label">مرفوض</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
            @endif

            <!-- Time Analytics -->
            <div class="analytics-section">
                <div class="section-header">
                    <h3>
                        <i class="fas fa-stopwatch"></i>
                        تحليل الوقت
                    </h3>
                </div>
                <div class="analytics-grid">
                    <div class="analytics-card time-comparison">
                        <div class="chart-container">
                            <canvas id="timeChart"></canvas>
                        </div>
                    </div>

                    <div class="analytics-card time-stats">
                        <div class="card-header">
                            <h4>إحصائيات الوقت</h4>
                        </div>
                        <div class="time-metrics">
                            <div class="time-metric">
                                <div class="metric-icon estimated">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="metric-info">
                                    <span class="metric-value">{{ round($timeStats['estimated_minutes'] / 60, 1) }}h</span>
                                    <span class="metric-label">مجموع الوقت المقدر</span>
                                </div>
                            </div>

                            <div class="time-metric">
                                <div class="metric-icon actual">
                                    <i class="fas fa-hourglass-half"></i>
                                </div>
                                <div class="metric-info">
                                    <span class="metric-value"
                                          id="employee-analytics-timer"
                                          data-initial-minutes="{{ (int)($timeStats['spent_minutes'] ?? 0) }}"
                                          data-active-count="{{ (int)($timeStats['active_tasks_count'] ?? 0) }}"
                                          data-started-at="{{ now()->timestamp * 1000 }}">
                                        @php
                                            $totalMinutes = (int)($timeStats['spent_minutes'] ?? 0);
                                            $hours = intval($totalMinutes / 60);
                                            $minutes = $totalMinutes % 60;
                                        @endphp
                                        {{ sprintf('%d:%02d:%02d', $hours, $minutes, 0) }}
                                        @if(($timeStats['active_tasks_count'] ?? 0) > 0)
                                            <span class="real-time-addition" style="color: #28a745; font-size: 0.8em;">●</span>
                                        @endif
                                    </span>
                                    <span class="metric-label">مجموع الوقت الفعلي</span>
                                </div>
                            </div>

                            <div class="time-metric">
                                <div class="metric-icon avg">
                                    <i class="fas fa-chart-bar"></i>
                                </div>
                                <div class="metric-info">
                                    <span class="metric-value">{{ $totalTasks > 0 ? round($timeStats['spent_minutes'] / $totalTasks) : 0 }}m</span>
                                    <span class="metric-label">متوسط الوقت لكل مهمة</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transfer Statistics Section -->
        @if(isset($transferStats) && $transferStats['has_transfers'])
        <div class="section-modern">
            <div class="section-header">
                <h2>
                    <i class="fas fa-exchange-alt"></i>
                    إحصائيات نقل المهام (جميع المشاريع)
                </h2>
            </div>

            <div class="transfer-stats-grid">
                <!-- Received Tasks Card -->
                <div class="transfer-stat-card received">
                    <div class="transfer-stat-icon">
                        <i class="fas fa-arrow-down"></i>
                    </div>
                    <div class="transfer-stat-content">
                        <div class="transfer-stat-number">{{ $transferStats['transferred_to_me'] }}</div>
                        <div class="transfer-stat-label">مهام منقولة إليّ</div>
                        <div class="transfer-stat-breakdown">
                            <span><i class="fas fa-tasks"></i> عادية: {{ $transferStats['regular_transferred_to_me'] }}</span>
                            <span><i class="fas fa-layer-group"></i> قوالب: {{ $transferStats['template_transferred_to_me'] }}</span>
                        </div>
                    </div>
                </div>

                <!-- Sent Tasks Card -->
                <div class="transfer-stat-card sent">
                    <div class="transfer-stat-icon">
                        <i class="fas fa-arrow-up"></i>
                    </div>
                    <div class="transfer-stat-content">
                        <div class="transfer-stat-number">{{ $transferStats['transferred_from_me'] }}</div>
                        <div class="transfer-stat-label">مهام منقولة مني</div>
                        <div class="transfer-stat-breakdown">
                            <span><i class="fas fa-tasks"></i> عادية: {{ $transferStats['regular_transferred_from_me'] }}</span>
                            <span><i class="fas fa-layer-group"></i> قوالب: {{ $transferStats['template_transferred_from_me'] }}</span>
                        </div>
                    </div>
                </div>

                <!-- History Card -->
                <div class="transfer-stat-card history">
                    <div class="transfer-stat-icon">
                        <i class="fas fa-history"></i>
                    </div>
                    <div class="transfer-stat-content">
                        <div class="transfer-stat-number">
                            <a href="{{ route('task-transfers.history') }}" class="transfer-history-link">
                                <i class="fas fa-eye"></i>
                                عرض السجلات
                            </a>
                        </div>
                        <div class="transfer-stat-label">سجلات النقل الكاملة</div>
                        <div class="transfer-stat-breakdown">
                            <span><i class="fas fa-database"></i> جميع عمليات النقل</span>
                        </div>
                    </div>
                </div>
            </div>

            @if(count($transferStats['transferred_from_details']) > 0)
            <div class="recent-transfers-section">
                <h3>
                    <i class="fas fa-history"></i>
                    تفاصيل المهام المنقولة مني (أحدث {{ count($transferStats['transferred_from_details']) }} مهمة)
                </h3>
                <div class="recent-transfers-list">
                    @foreach($transferStats['transferred_from_details'] as $transfer)
                    <div class="recent-transfer-item">
                        <div class="transfer-icon {{ $transfer->task_type }}">
                            <i class="fas {{ $transfer->task_type == 'regular' ? 'fa-tasks' : 'fa-layer-group' }}"></i>
                        </div>
                        <div class="transfer-content">
                            <div class="transfer-header">
                                <span class="task-name">{{ $transfer->task_name }}</span>
                                <span class="task-type-badge {{ $transfer->task_type }}">
                                    {{ $transfer->task_type == 'regular' ? 'عادية' : 'قالب' }}
                                </span>
                            </div>
                            <div class="transfer-details">
                                <span class="transfer-users">
                                    <i class="fas fa-user"></i>
                                    منقولة إلى: <strong>{{ $transfer->current_user_name }}</strong>
                                </span>
                                <span class="project-info">
                                    <i class="fas fa-project-diagram"></i>
                                    المشروع: {{ $transfer->project_name ?? 'غير محدد' }}
                                </span>
                                <span class="transfer-time">
                                    <i class="fas fa-clock"></i>
                                    {{ \Carbon\Carbon::parse($transfer->transferred_from_at)->diffForHumans() }}
                                </span>
                            </div>
                            @if($transfer->transfer_reason)
                            <div class="transfer-reason">
                                <i class="fas fa-comment"></i>
                                {{ $transfer->transfer_reason }}
                            </div>
                            @endif
                            <div class="transfer-status">
                                <span class="status-badge status-{{ $transfer->status }}">
                                    {{ $transfer->status }}
                                </span>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        @endif

        <!-- Revision Transfer Statistics Section -->
        @if(isset($revisionTransferStats) && $revisionTransferStats['has_transfers'])
        <div class="section-modern">
            <div class="section-header">
                <h2>
                    <i class="fas fa-exchange-alt"></i>
                    إحصائيات نقل التعديلات (مهام إضافية)
                </h2>
            </div>

            <div class="transfer-stats-grid">
                <!-- Received Revisions Card -->
                <div class="transfer-stat-card received">
                    <div class="transfer-stat-icon">
                        <i class="fas fa-arrow-down"></i>
                    </div>
                    <div class="transfer-stat-content">
                        <div class="transfer-stat-number">{{ $revisionTransferStats['transferred_to_me'] }}</div>
                        <div class="transfer-stat-label">تعديلات منقولة إليّ</div>
                        <div class="transfer-stat-breakdown">
                            <span><i class="fas fa-wrench"></i> كمنفذ: {{ $revisionTransferStats['executor_transferred_to_me'] }}</span>
                            <span><i class="fas fa-check-circle"></i> كمراجع: {{ $revisionTransferStats['reviewer_transferred_to_me'] }}</span>
                        </div>
                        <div class="transfer-stat-note">
                            <i class="fas fa-info-circle"></i>
                            هذه تعديلات إضافية تم تكليفك بها
                        </div>
                    </div>
                </div>

                <!-- Sent Revisions Card -->
                <div class="transfer-stat-card sent">
                    <div class="transfer-stat-icon">
                        <i class="fas fa-arrow-up"></i>
                    </div>
                    <div class="transfer-stat-content">
                        <div class="transfer-stat-number">{{ $revisionTransferStats['transferred_from_me'] }}</div>
                        <div class="transfer-stat-label">تعديلات منقولة مني</div>
                        <div class="transfer-stat-breakdown">
                            <span><i class="fas fa-wrench"></i> كمنفذ: {{ $revisionTransferStats['executor_transferred_from_me'] }}</span>
                            <span><i class="fas fa-check-circle"></i> كمراجع: {{ $revisionTransferStats['reviewer_transferred_from_me'] }}</span>
                        </div>
                        <div class="transfer-stat-note">
                            <i class="fas fa-info-circle"></i>
                            تعديلات تم نقلها لشخص آخر
                        </div>
                    </div>
                </div>

                <!-- Additional Tasks Card -->
                <div class="transfer-stat-card history">
                    <div class="transfer-stat-icon">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <div class="transfer-stat-content">
                        <div class="transfer-stat-number">
                            {{ $revisionTransferStats['transferred_to_me'] }}
                        </div>
                        <div class="transfer-stat-label">إجمالي التعديلات الإضافية</div>
                        <div class="transfer-stat-breakdown">
                            <span><i class="fas fa-star"></i> تعديلات لم تكن من نصيبك الأصلي</span>
                        </div>
                        <div class="transfer-stat-note">
                            <i class="fas fa-medal"></i>
                            دليل على المساعدة والتعاون
                        </div>
                    </div>
                </div>
            </div>

            @if($revisionTransferStats['transferred_to_me_details']->count() > 0)
            <div class="recent-transfers-section">
                <h3>
                    <i class="fas fa-history"></i>
                    تفاصيل التعديلات المنقولة إليّ (أحدث {{ $revisionTransferStats['transferred_to_me_details']->count() }} تعديل)
                </h3>
                <div class="recent-transfers-list">
                    @foreach($revisionTransferStats['transferred_to_me_details'] as $transfer)
                    <div class="recent-transfer-item">
                        <div class="transfer-icon {{ $transfer->assignment_type }}">
                            <i class="fas {{ $transfer->assignment_type == 'executor' ? 'fa-wrench' : 'fa-check-circle' }}"></i>
                        </div>
                        <div class="transfer-content">
                            <div class="transfer-header">
                                <span class="task-name">{{ $transfer->revision->title ?? 'تعديل محذوف' }}</span>
                                <span class="task-type-badge {{ $transfer->assignment_type }}">
                                    {{ $transfer->assignment_type == 'executor' ? 'منفذ' : 'مراجع' }}
                                </span>
                            </div>
                            <div class="transfer-details">
                                @if($transfer->fromUser)
                                <span class="transfer-users">
                                    <i class="fas fa-user"></i>
                                    منقولة من: <strong>{{ $transfer->fromUser->name }}</strong>
                                </span>
                                @endif
                                <span class="project-info">
                                    <i class="fas fa-user-tie"></i>
                                    بواسطة: {{ $transfer->assignedBy->name ?? 'غير محدد' }}
                                </span>
                                <span class="transfer-time">
                                    <i class="fas fa-clock"></i>
                                    {{ $transfer->created_at->diffForHumans() }}
                                </span>
                            </div>
                            @if($transfer->reason)
                            <div class="transfer-reason">
                                <i class="fas fa-comment"></i>
                                {{ $transfer->reason }}
                            </div>
                            @endif
                            @if($transfer->revision)
                            <div class="transfer-status">
                                <span class="status-badge status-{{ $transfer->revision->status }}">
                                    @if($transfer->revision->status == 'new')
                                        جديد
                                    @elseif($transfer->revision->status == 'in_progress')
                                        قيد التنفيذ
                                    @elseif($transfer->revision->status == 'completed')
                                        مكتمل
                                    @elseif($transfer->revision->status == 'approved')
                                        موافق عليه
                                    @else
                                        {{ $transfer->revision->status }}
                                    @endif
                                </span>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            @if($revisionTransferStats['transferred_from_me_details']->count() > 0)
            <div class="recent-transfers-section">
                <h3>
                    <i class="fas fa-share"></i>
                    تفاصيل التعديلات المنقولة مني (أحدث {{ $revisionTransferStats['transferred_from_me_details']->count() }} تعديل)
                </h3>
                <div class="recent-transfers-list">
                    @foreach($revisionTransferStats['transferred_from_me_details'] as $transfer)
                    <div class="recent-transfer-item">
                        <div class="transfer-icon {{ $transfer->assignment_type }}">
                            <i class="fas {{ $transfer->assignment_type == 'executor' ? 'fa-wrench' : 'fa-check-circle' }}"></i>
                        </div>
                        <div class="transfer-content">
                            <div class="transfer-header">
                                <span class="task-name">{{ $transfer->revision->title ?? 'تعديل محذوف' }}</span>
                                <span class="task-type-badge {{ $transfer->assignment_type }}">
                                    {{ $transfer->assignment_type == 'executor' ? 'منفذ' : 'مراجع' }}
                                </span>
                            </div>
                            <div class="transfer-details">
                                <span class="transfer-users">
                                    <i class="fas fa-user"></i>
                                    منقولة إلى: <strong>{{ $transfer->toUser->name }}</strong>
                                </span>
                                <span class="project-info">
                                    <i class="fas fa-user-tie"></i>
                                    بواسطة: {{ $transfer->assignedBy->name ?? 'غير محدد' }}
                                </span>
                                <span class="transfer-time">
                                    <i class="fas fa-clock"></i>
                                    {{ $transfer->created_at->diffForHumans() }}
                                </span>
                            </div>
                            @if($transfer->reason)
                            <div class="transfer-reason">
                                <i class="fas fa-comment"></i>
                                {{ $transfer->reason }}
                            </div>
                            @endif
                            @if($transfer->revision)
                            <div class="transfer-status">
                                <span class="status-badge status-{{ $transfer->revision->status }}">
                                    @if($transfer->revision->status == 'new')
                                        جديد
                                    @elseif($transfer->revision->status == 'in_progress')
                                        قيد التنفيذ
                                    @elseif($transfer->revision->status == 'completed')
                                        مكتمل
                                    @elseif($transfer->revision->status == 'approved')
                                        موافق عليه
                                    @else
                                        {{ $transfer->revision->status }}
                                    @endif
                                </span>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        @endif

        <!-- Employee Error Statistics Section -->
        @if(isset($employeeErrorStats) && $employeeErrorStats['has_errors'])
        <div class="section-modern">
            <div class="section-header">
                <h2>
                    <i class="fas fa-exclamation-triangle"></i>
                    إحصائيات الأخطاء
                </h2>
            </div>

            <div class="stats-grid">
                <div class="stat-card-modern danger">
                    <div class="stat-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number">{{ $employeeErrorStats['total_errors'] }}</div>
                        <div class="stat-label">إجمالي الأخطاء</div>
                        <div class="stat-trend">
                            <i class="fas fa-fire"></i> جوهرية: {{ $employeeErrorStats['critical_errors'] }} |
                            <i class="fas fa-info-circle"></i> عادية: {{ $employeeErrorStats['normal_errors'] }}
                        </div>
                    </div>
                </div>

                <div class="stat-card-modern warning">
                    <div class="stat-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number">{{ $employeeErrorStats['by_category']['quality'] + $employeeErrorStats['by_category']['technical'] }}</div>
                        <div class="stat-label">أخطاء جودة وفنية</div>
                        <div class="stat-trend">
                            جودة: {{ $employeeErrorStats['by_category']['quality'] }} | فنية: {{ $employeeErrorStats['by_category']['technical'] }}
                        </div>
                    </div>
                </div>

                <div class="stat-card-modern info">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number">{{ $employeeErrorStats['by_category']['deadline'] + $employeeErrorStats['by_category']['communication'] }}</div>
                        <div class="stat-label">أخطاء مواعيد وتواصل</div>
                        <div class="stat-trend">
                            مواعيد: {{ $employeeErrorStats['by_category']['deadline'] }} | تواصل: {{ $employeeErrorStats['by_category']['communication'] }}
                        </div>
                    </div>
                </div>
            </div>

            @if(count($employeeErrorStats['latest_errors']) > 0)
            <div class="error-details-section">
                <div class="error-table-header">
                    <h3>
                        <i class="fas fa-list-alt"></i>
                        آخر الأخطاء المسجلة
                    </h3>
                </div>

                <div class="table-responsive">
                    <table class="errors-table">
                        <thead>
                            <tr>
                                <th class="error-type-col">النوع</th>
                                <th class="error-title-col">عنوان الخطأ</th>
                                <th class="error-category-col">الفئة</th>
                                <th class="error-description-col">الوصف</th>
                                <th class="error-reporter-col">المُسجِّل</th>
                                <th class="error-date-col">التاريخ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($employeeErrorStats['latest_errors'] as $error)
                            <tr class="error-row {{ $error->error_type == 'critical' ? 'row-critical' : 'row-normal' }}">
                                <td class="error-type-cell">
                                    <span class="error-type-label {{ $error->error_type == 'critical' ? 'type-critical' : 'type-normal' }}">
                                        <i class="fas {{ $error->error_type == 'critical' ? 'fa-exclamation-circle' : 'fa-info-circle' }}"></i>
                                        {{ $error->error_type == 'critical' ? 'جوهري' : 'عادي' }}
                                    </span>
                                </td>
                                <td class="error-title-cell">
                                    <strong>{{ $error->title }}</strong>
                                </td>
                                <td class="error-category-cell">
                                    <span class="category-tag">
                                        <i class="fas fa-tag"></i>
                                        {{ $error->error_category }}
                                    </span>
                                </td>
                                <td class="error-description-cell">
                                    @if($error->description)
                                        {{ Str::limit($error->description, 80) }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="error-reporter-cell">
                                    @if($error->reportedBy)
                                        <div class="reporter-info">
                                            <i class="fas fa-user"></i>
                                            {{ $error->reportedBy->name }}
                                        </div>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="error-date-cell">
                                    <div class="date-info">
                                        <i class="fas fa-clock"></i>
                                        {{ $error->created_at->diffForHumans() }}
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>
        @endif

        <!-- Employee Projects -->
        <div class="projects-section">
            <div class="section-header">
                <h2>
                    <i class="fas fa-briefcase"></i>
                    مشاريع الموظف
                </h2>
                <span class="section-count">{{ $employeeProjects->count() }}</span>
            </div>

            @if($employeeProjects->count() > 0)
            <div class="projects-grid">
                @foreach($employeeProjects as $project)
                <div class="project-card">
                    <div class="project-header">
                        <div class="project-icon">
                            <i class="fas fa-project-diagram"></i>
                        </div>
                        <div class="project-info">
                            <h4>{{ $project->name }}</h4>
                            <p class="project-client">
                                <i class="fas fa-user"></i>
                                {{ $project->client_name ?? 'بدون عميل' }}
                            </p>
                            <span class="project-status status-{{ str_replace(' ', '-', $project->status) }}">
                                {{ $project->status }}
                            </span>
                        </div>
                    </div>

                    @if($project->description)
                    <p style="color: #666; font-size: 0.9rem; margin-top: 1rem;">
                        {{ Str::limit($project->description, 100) }}
                    </p>
                    @endif

                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e2e8f0;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 0.9rem; color: #666;">
                                <i class="fas fa-calendar"></i>
                                @if($project->start_date)
                                {{ \Carbon\Carbon::parse($project->start_date)->format('Y/m/d') }}
                                @else
                                غير محدد
                                @endif
                            </span>
                            @php
                                $deliveryDate = $project->client_agreed_delivery_date ?? $project->team_delivery_date;
                            @endphp
                            @if($deliveryDate)
                            <span style="font-size: 0.9rem; color: #666;">
                                <i class="fas fa-flag-checkered"></i>
                                {{ \Carbon\Carbon::parse($deliveryDate)->format('Y/m/d') }}
                            </span>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="empty-state">
                <i class="fas fa-briefcase"></i>
                <p>لا يوجد مشاريع مخصصة لهذا الموظف</p>
            </div>
            @endif
        </div>

        <!-- Recent Activities -->
        @if($recentTasks->count() > 0)
        <div class="recent-activities">
            <div class="section-header">
                <h2>
                    <i class="fas fa-history"></i>
                    النشاطات الأخيرة
                </h2>
            </div>

            <div class="activity-list">
                @foreach($recentTasks as $task)
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="fas fa-task"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title">{{ $task->task_name }}</div>
                        @if($task->project_name)
                        <div class="activity-project">
                            <i class="fas fa-project-diagram"></i>
                            {{ $task->project_name }}
                        </div>
                        @endif
                        <div class="activity-date">
                            {{ \Carbon\Carbon::parse($task->updated_at)->diffForHumans() }}
                        </div>
                    </div>
                    <div class="activity-status status-{{ $task->status }}">
                        @switch($task->status)
                            @case('completed')
                                مكتملة
                                @break
                            @case('in_progress')
                                قيد التنفيذ
                                @break
                            @case('new')
                                جديدة
                                @break
                            @case('paused')
                                متوقفة
                                @break
                            @default
                                {{ $task->status }}
                        @endswitch
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- ✅ آخر النشاطات للموظف - Recent Activities Section -->
        <div class="row">
            <!-- آخر المهام -->
            <div class="col-lg-6 col-md-6 mb-4">
                <div class="section-modern">
                    <div class="section-header">
                        <h2>
                            <i class="fas fa-tasks"></i>
                            آخر المهام - {{ $employee->name }}
                        </h2>
                        <span class="section-count">{{ $recentEmployeeTasks->count() }}</span>
                    </div>

                    @if($recentEmployeeTasks->count() > 0)
                    <div class="recent-activities-list">
                        @foreach($recentEmployeeTasks->take(6) as $recentTask)
                        <div class="activity-item" data-task-type="{{ $recentTask->task_type ?? 'regular' }}" data-status="{{ $recentTask->status }}">
                            <div class="activity-icon">
                                @if(($recentTask->task_type ?? 'regular') === 'template')
                                    <i class="fas fa-layer-group"></i>
                                @else
                                    <i class="fas fa-tasks"></i>
                                @endif
                            </div>
                            <div class="activity-content">
                                <div class="activity-title">
                                    <span class="task-name">{{ Str::limit($recentTask->task_name, 40) }}</span>
                                    <div class="task-type-badge {{ $recentTask->task_type ?? 'regular' }}">
                                        @if(($recentTask->task_type ?? 'regular') === 'template')
                                            <i class="fas fa-layer-group"></i>
                                            قالب
                                        @else
                                            <i class="fas fa-tasks"></i>
                                            عادية
                                        @endif
                                    </div>
                                </div>
                                <div class="activity-meta">
                                    @if($recentTask->project_name)
                                    <div class="project-name">
                                        <i class="fas fa-project-diagram"></i>
                                        {{ Str::limit($recentTask->project_name, 25) }}
                                    </div>
                                    @endif
                                    <div class="activity-time">
                                        <i class="fas fa-clock"></i>
                                        {{ \Carbon\Carbon::parse($recentTask->last_updated)->diffForHumans() }}
                                    </div>
                                </div>
                            </div>
                            <div class="activity-status status-{{ $recentTask->status }}">
                                @if($recentTask->status == 'completed')
                                    <i class="fas fa-check-circle"></i>
                                    مكتملة
                                @elseif($recentTask->status == 'in_progress')
                                    <i class="fas fa-play-circle"></i>
                                    قيد التنفيذ
                                @elseif($recentTask->status == 'paused')
                                    <i class="fas fa-pause-circle"></i>
                                    متوقفة
                                @else
                                    <i class="fas fa-circle"></i>
                                    {{ $recentTask->status == 'new' ? 'جديدة' : $recentTask->status }}
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="empty-state">
                        <i class="fas fa-tasks"></i>
                        <p>لا توجد مهام حديثة لهذا الموظف</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- آخر المشاريع -->
            <div class="col-lg-6 col-md-6 mb-4">
                <div class="section-modern">
                    <div class="section-header">
                        <h2>
                            <i class="fas fa-project-diagram"></i>
                            آخر المشاريع - {{ $employee->name }}
                        </h2>
                        <span class="section-count">{{ $recentEmployeeProjects->count() }}</span>
                    </div>

                    @if($recentEmployeeProjects->count() > 0)
                    <div class="recent-projects-list">
                        @foreach($recentEmployeeProjects->take(6) as $recentProject)
                        <div class="project-item-recent" data-status="{{ str_replace(' ', '-', $recentProject->status) }}">
                            <div class="project-icon">
                                <i class="fas fa-folder"></i>
                            </div>
                            <div class="project-content">
                                <div class="project-title">
                                    <span class="project-name">
                                        <a href="{{ route('projects.show', $recentProject) }}">
                                            {{ Str::limit($recentProject->name, 35) }}
                                        </a>
                                    </span>
                                    <div class="project-type-badge">
                                        <i class="fas fa-project-diagram"></i>
                                        مشروع
                                    </div>
                                </div>
                                <div class="project-meta">
                                    <div class="project-client">
                                        <i class="fas fa-user"></i>
                                        {{ $recentProject->client->name ?? 'بدون عميل' }}
                                    </div>
                                    <div class="project-participants">
                                        <i class="fas fa-users"></i>
                                        {{ $recentProject->participants->count() }} مشارك
                                    </div>
                                    <div class="project-time">
                                        <i class="fas fa-clock"></i>
                                        {{ $recentProject->updated_at->diffForHumans() }}
                                    </div>
                                </div>
                            </div>
                            <div class="project-status status-{{ str_replace(' ', '-', $recentProject->status) }}">
                                <i class="fas fa-circle"></i>
                                {{ $recentProject->status }}
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="empty-state">
                        <i class="fas fa-project-diagram"></i>
                        <p>لا توجد مشاريع حديثة لهذا الموظف</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- الصف الثاني: آخر الاجتماعات والتعديلات -->
        <div class="row">
            <!-- آخر الاجتماعات -->
            <div class="col-lg-6 col-md-6 mb-4">
                <div class="section-modern">
                    <div class="section-header">
                        <h2>
                            <i class="fas fa-calendar-alt"></i>
                            آخر الاجتماعات - {{ $employee->name }}
                        </h2>
                        <span class="section-count">{{ $recentEmployeeMeetings->count() }}</span>
                    </div>

                    @if($recentEmployeeMeetings->count() > 0)
                    <div class="recent-meetings-list">
                        @foreach($recentEmployeeMeetings->take(6) as $recentMeeting)
                        <div class="meeting-item-recent" data-meeting-type="{{ $recentMeeting->client ? 'client' : 'internal' }}">
                            <div class="meeting-icon">
                                <i class="fas fa-video"></i>
                            </div>
                            <div class="meeting-content">
                                <div class="meeting-title">
                                    <span class="meeting-name">{{ Str::limit($recentMeeting->title, 35) }}</span>
                                    <div class="meeting-type-badge">
                                        <i class="fas fa-calendar-alt"></i>
                                        اجتماع
                                    </div>
                                </div>
                                <div class="meeting-meta">
                                    <div class="meeting-creator">
                                        <i class="fas fa-user-tie"></i>
                                        {{ $recentMeeting->creator->name }}
                                    </div>
                                    @if($recentMeeting->client)
                                    <div class="meeting-client">
                                        <i class="fas fa-building"></i>
                                        {{ $recentMeeting->client->name }}
                                    </div>
                                    @endif
                                    <div class="meeting-participants">
                                        <i class="fas fa-users"></i>
                                        {{ $recentMeeting->participants->count() }} مشارك
                                    </div>
                                    <div class="meeting-time">
                                        <i class="fas fa-clock"></i>
                                        @if($recentMeeting->start_time)
                                            {{ \Carbon\Carbon::parse($recentMeeting->start_time)->format('Y/m/d H:i') }}
                                        @else
                                            {{ $recentMeeting->created_at->diffForHumans() }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="meeting-status">
                                @if($recentMeeting->start_time && \Carbon\Carbon::parse($recentMeeting->start_time)->isFuture())
                                    <i class="fas fa-clock"></i>
                                    قادم
                                @elseif($recentMeeting->start_time && \Carbon\Carbon::parse($recentMeeting->start_time)->isPast())
                                    <i class="fas fa-check-circle"></i>
                                    منتهي
                                @else
                                    <i class="fas fa-calendar"></i>
                                    مجدول
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="empty-state">
                        <i class="fas fa-calendar-alt"></i>
                        <p>لا توجد اجتماعات حديثة لهذا الموظف</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- آخر التعديلات -->
            @if($latestRevisions->count() > 0 || $urgentRevisions->count() > 0)
            <div class="col-lg-6 col-md-6 mb-4">
                <div class="section-modern">
                    <div class="section-header">
                        <h2>
                            <i class="fas fa-exclamation-triangle"></i>
                            التعديلات الهامة - {{ $employee->name }}
                        </h2>
                    </div>

                    <div class="revision-alerts-grid">
                        @if($urgentRevisions->count() > 0)
                        <div class="alert-card urgent">
                            <div class="alert-header">
                                <i class="fas fa-fire"></i>
                                <h4>تعديلات ملحة (أكثر من 3 أيام)</h4>
                                <span class="count">{{ $urgentRevisions->count() }}</span>
                            </div>
                            <div class="alert-list">
                                @foreach($urgentRevisions->take(3) as $revision)
                                <div class="alert-item">
                                    <div class="item-icon">
                                        @if($revision->revision_type == 'project')
                                            <i class="fas fa-project-diagram text-primary"></i>
                                        @elseif($revision->revision_type == 'general')
                                            <i class="fas fa-globe text-info"></i>
                                        @else
                                            <i class="fas fa-tasks text-secondary"></i>
                                        @endif
                                    </div>
                                    <div class="item-info">
                                        <div class="item-title">{{ Str::limit($revision->title, 40) }}</div>
                                        <div class="item-meta">
                                            <span class="creator">{{ $revision->creator->name }}</span>
                                            <span class="date">{{ $revision->revision_date->diffForHumans() }}</span>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @if($urgentRevisions->count() > 3)
                            <div class="alert-footer">
                                <span class="text-muted">
                                    يوجد {{ $urgentRevisions->count() }} تعديل معلق
                                </span>
                            </div>
                            @endif
                        </div>
                        @endif

                        @if($latestRevisions->count() > 0)
                        <div class="alert-card latest">
                            <div class="alert-header">
                                <i class="fas fa-history"></i>
                                <h4>آخر التعديلات</h4>
                                <span class="count">{{ $latestRevisions->count() }}</span>
                            </div>
                            <div class="alert-list">
                                @foreach($latestRevisions->take(4) as $revision)
                                <div class="alert-item">
                                    <div class="item-icon">
                                        @if($revision->status == 'approved')
                                            <i class="fas fa-check-circle text-success"></i>
                                        @elseif($revision->status == 'rejected')
                                            <i class="fas fa-times-circle text-danger"></i>
                                        @else
                                            <i class="fas fa-clock text-warning"></i>
                                        @endif
                                    </div>
                                    <div class="item-info">
                                        <div class="item-title">{{ Str::limit($revision->title, 35) }}</div>
                                        <div class="item-meta">
                                            <span class="creator">{{ $revision->creator->name }}</span>
                                            <span class="status status-{{ $revision->status }}">
                                                @if($revision->status == 'approved')
                                                    موافق عليه
                                                @elseif($revision->status == 'rejected')
                                                    مرفوض
                                                @else
                                                    في الانتظار
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            <div class="alert-footer">
                                <span class="text-muted">
                                    آخر التعديلات للموظف
                                </span>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Back Button -->
        <div class="text-center mt-4">
            @if($employee->department)
            <a href="{{ route('departments.show', urlencode($employee->department)) }}" class="action-btn secondary mr-3">
                <i class="fas fa-arrow-right"></i>
                العودة إلى {{ $employee->department }}
            </a>
            @endif
            <a href="{{ route('company-projects.dashboard') }}" class="action-btn outline">
                <i class="fas fa-home"></i>
                لوحة التحكم الرئيسية
            </a>
        </div>
    </div>
</div>

<!-- ✅ Modal لعرض تفاصيل نقاط إكمال المشاريع -->
<div class="modal fade" id="projectCompletionModal" tabindex="-1" aria-labelledby="projectCompletionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-gradient-primary text-white">
                <h5 class="modal-title" id="projectCompletionModalLabel">
                    <i class="fas fa-chart-pie me-2"></i>
                    تفاصيل نقاط إكمال المشاريع - {{ $employee->name }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body">
                <!-- Header Summary -->
                <div class="row mb-4">
                    <div class="col-md-4 text-center">
                        <div class="stat-summary">
                            <div class="stat-number text-primary fs-2 fw-bold">
                                {{ $projectCompletionDetails['total_projects'] ?? 0 }}
                            </div>
                            <div class="stat-label text-muted">إجمالي المشاريع</div>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="stat-summary">
                            <div class="stat-number text-success fs-2 fw-bold">
                                {{ number_format($projectCompletionDetails['total_points'] ?? 0, 1) }}
                            </div>
                            <div class="stat-label text-muted">إجمالي النقاط</div>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="stat-summary">
                            <div class="stat-number text-info fs-2 fw-bold">
                                {{ $projectCompletionRate }}%
                            </div>
                            <div class="stat-label text-muted">معدل الإكمال</div>
                        </div>
                    </div>
                </div>

                <!-- Projects Table -->
                @if(isset($projectCompletionDetails['projects']) && count($projectCompletionDetails['projects']) > 0)
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead class="table-primary">
                            <tr>
                                <th><i class="fas fa-project-diagram me-1"></i>اسم المشروع</th>
                                <th class="text-center"><i class="fas fa-tasks me-1"></i>المهام المكتملة</th>
                                <th class="text-center"><i class="fas fa-list-ol me-1"></i>المهام الحالية</th>
                                <th class="text-center"><i class="fas fa-exchange-alt me-1"></i>المهام المنقولة</th>
                                <th class="text-center"><i class="fas fa-percentage me-1"></i>نسبة الإكمال</th>
                                <th class="text-center"><i class="fas fa-star me-1"></i>النقاط</th>
                                <th class="text-center"><i class="fas fa-flag me-1"></i>الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($projectCompletionDetails['projects'] as $project)
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark">{{ $project['name'] }}</div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success">{{ $project['completed_tasks'] }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info">{{ $project['current_tasks'] }}</span>
                                </td>
                                <td class="text-center">
                                    @if($project['transferred_tasks'] > 0)
                                        <span class="badge bg-warning">{{ $project['transferred_tasks'] }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-success"
                                             style="width: {{ $project['completion_rate'] }}%"></div>
                                    </div>
                                    <small class="text-muted">{{ $project['completion_rate'] }}%</small>
                                </td>
                                <td class="text-center">
                                    <span class="fw-bold text-primary">{{ number_format($project['points'], 1) }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="{{ $project['status_class'] }}">
                                        {{ $project['status_icon'] }} {{ $project['status'] }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-secondary">
                            <tr class="fw-bold">
                                <td>الإجمالي</td>
                                <td class="text-center">
                                    {{ collect($projectCompletionDetails['projects'])->sum('completed_tasks') }}
                                </td>
                                <td class="text-center">
                                    {{ collect($projectCompletionDetails['projects'])->sum('current_tasks') }}
                                </td>
                                <td class="text-center">
                                    {{ collect($projectCompletionDetails['projects'])->sum('transferred_tasks') }}
                                </td>
                                <td class="text-center">-</td>
                                <td class="text-center text-primary fs-5">
                                    {{ number_format($projectCompletionDetails['total_points'], 1) }}
                                </td>
                                <td class="text-center">-</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                @else
                <div class="text-center py-4">
                    <i class="fas fa-inbox text-muted" style="font-size: 3rem;"></i>
                    <h5 class="text-muted mt-3">لا توجد مشاريع</h5>
                    <p class="text-muted">لم يتم تكليف هذا الموظف بأي مشاريع حتى الآن</p>
                </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>إغلاق
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

