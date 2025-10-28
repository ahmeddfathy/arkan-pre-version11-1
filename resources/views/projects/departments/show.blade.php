@extends('layouts.app')

@section('title', 'تفاصيل قسم ' . $department)

@php
use Illuminate\Support\Facades\DB;
@endphp

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Pass data to JavaScript
    window.departmentData = {
        projectStats: @json($projectStats),
        taskStats: @json($taskStats)
    };
</script>
<script src="{{ asset('js/project-dashboard/departments/timer.js') }}"></script>
<script src="{{ asset('js/project-dashboard/departments/filters.js') }}"></script>
<script src="{{ asset('js/project-dashboard/departments/charts.js') }}"></script>
@endpush

@push('styles')
<link rel="stylesheet" href="{{ asset('css/projects-dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('css/project-dashboard/departments.css') }}">
<link rel="stylesheet" href="{{ asset('css/project-dashboard/departments/show.css') }}">
<link rel="stylesheet" href="{{ asset('css/project-dashboard/revisions-modern.css') }}">
@endpush

@section('content')
<div class="modern-dashboard">
    <!-- Department Header -->
    <div class="department-detail-header">
        <div class="container">
            <div class="department-info">
                <h1 class="department-name">{{ $department }}</h1>
                <p class="department-description">
                    {{ $departmentData->employees_count }} موظف |
                    {{ $projectStats['in_progress'] }} مشروع نشط |
                    {{ $completionRate }}% معدل الإنجاز
                </p>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="filters-section">
        <div class="container">
            <div class="filters-container">
                <div class="filters-header">
                    <h3><i class="fas fa-filter"></i> فلتر البيانات</h3>
                    <button type="button" class="toggle-filters-btn" id="toggleFiltersBtn">
                        <i class="fas fa-chevron-down"></i>
                        عرض/إخفاء الفلاتر
                    </button>
                </div>
                <div class="filters-content" id="filtersContent">
                    <form method="GET" action="{{ route('departments.show', ['department' => urlencode($department)]) }}" id="departmentFiltersForm">
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
                                <a href="{{ route('departments.show', ['department' => urlencode($department)]) }}" class="btn-reset-filters">
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

    <div class="container">
        <!-- Filter Indicator -->
        @if(isset($periodDescription) && $periodDescription != 'جميع الفترات')
        <div class="section-modern">
            <div class="filter-indicator-department">
                <i class="fas fa-filter text-primary"></i>
                <span class="filter-text">فلتر مطبق: {{ $periodDescription }}</span>
            </div>
        </div>
        @endif

        <!-- Department Statistics -->
        <div class="stats-grid">
            <div class="stat-card-modern primary">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number">{{ $departmentData->employees_count }}</div>
                    <div class="stat-label">إجمالي الموظفين</div>
                </div>
            </div>

            <div class="stat-card-modern success">
                <div class="stat-icon">
                    <i class="fas fa-project-diagram"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number">{{ $projectStats['in_progress'] }}</div>
                    <div class="stat-label">المشاريع النشطة</div>
                </div>
            </div>

            <div class="stat-card-modern info">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number">{{ $completedTasks }}</div>
                    <div class="stat-label">المهام المكتملة</div>
                </div>
            </div>

            <div class="stat-card-modern warning">
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number">{{ $completionRate }}%</div>
                    <div class="stat-label">معدل الإنجاز</div>
                </div>
            </div>
        </div>

        <!-- Projects Overview Section -->
        <div class="section-modern">
            <div class="section-header">
                <h2>
                    <i class="fas fa-briefcase"></i>
                    نظرة عامة على المشاريع
                </h2>
            </div>

            <div class="projects-overview-grid">
                <div class="project-summary-card">
                    <div class="project-icon all">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <div class="project-info">
                        <div class="project-number">{{ $projectStats['total'] }}</div>
                        <div class="project-label">إجمالي المشاريع</div>
                    </div>
                </div>

                <div class="project-summary-card">
                    <div class="project-icon new">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <div class="project-info">
                        <div class="project-number">{{ $projectStats['new'] }}</div>
                        <div class="project-label">جديدة</div>
                    </div>
                </div>

                <div class="project-summary-card">
                    <div class="project-icon progress">
                        <i class="fas fa-play-circle"></i>
                    </div>
                    <div class="project-info">
                        <div class="project-number">{{ $projectStats['in_progress'] }}</div>
                        <div class="project-label">قيد التنفيذ</div>
                    </div>
                </div>

                <div class="project-summary-card">
                    <div class="project-icon completed">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="project-info">
                        <div class="project-number">{{ $projectStats['completed'] }}</div>
                        <div class="project-label">مكتملة</div>
                    </div>
                </div>

                <div class="project-summary-card">
                    <div class="project-icon cancelled">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="project-info">
                        <div class="project-number">{{ $projectStats['cancelled'] }}</div>
                        <div class="project-label">ملغية</div>
                    </div>
                </div>

                <div class="project-summary-card completion-rate">
                    <div class="completion-circle">
                        <svg viewBox="0 0 36 36" class="circular-chart">
                            <path class="circle-bg" d="M18 2.0845
                                a 15.9155 15.9155 0 0 1 0 31.831
                                a 15.9155 15.9155 0 0 1 0 -31.831"/>
                            <path class="circle" stroke-dasharray="{{ $projectStats['total'] > 0 ? round(($projectStats['completed'] / $projectStats['total']) * 100) : 0 }}, 100" d="M18 2.0845
                                a 15.9155 15.9155 0 0 1 0 31.831
                                a 15.9155 15.9155 0 0 1 0 -31.831"/>
                            <text x="18" y="20.35" class="percentage">{{ $projectStats['total'] > 0 ? round(($projectStats['completed'] / $projectStats['total']) * 100) : 0 }}%</text>
                        </svg>
                    </div>
                    <div class="project-info">
                        <div class="project-label">معدل إكمال المشاريع</div>
                    </div>
                </div>
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

                <!-- Overdue Projects Details -->
                @if(count($projectOverdueStats['overdue_projects']) > 0)
                <div class="overdue-details-section">
                    <h5 class="overdue-details-title">
                        <i class="fas fa-list"></i>
                        تفاصيل المشاريع المتأخرة والمشاركين من القسم
                    </h5>
                    <div class="overdue-projects-list">
                        @foreach($projectOverdueStats['overdue_projects'] as $item)
                        <div class="overdue-project-item">
                            <div class="overdue-project-header">
                                <h6 class="overdue-project-name">
                                    <i class="fas fa-project-diagram"></i>
                                    {{ $item['project']->name }}
                                </h6>
                            </div>
                            <div class="overdue-project-participants">
                                <strong>المشاركين المتأخرين من القسم:</strong>
                                <div class="participants-list-detailed">
                                    @foreach($item['participants'] as $participant)
                                    <div class="participant-card overdue">
                                        <div class="participant-header">
                                            <span class="participant-name">
                                                <i class="fas fa-user-clock"></i>
                                                {{ $participant['name'] }}
                                            </span>
                                            <span class="overdue-badge-sm danger">
                                                <i class="fas fa-exclamation-triangle"></i>
                                                متأخر {{ $participant['days_overdue'] }} يوم
                                            </span>
                                        </div>
                                        <div class="participant-meta">
                                            <span class="meta-item">
                                                <i class="fas fa-percentage"></i>
                                                نسبة المشاركة: <strong>{{ $participant['project_share'] }}%</strong>
                                            </span>
                                            <span class="meta-item">
                                                <i class="fas fa-calendar-times"></i>
                                                الموعد: <strong>{{ \Carbon\Carbon::parse($participant['deadline'])->format('Y-m-d') }}</strong>
                                            </span>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Completed Late Projects Details -->
                @if(count($projectOverdueStats['completed_late_projects']) > 0)
                <div class="overdue-details-section">
                    <h5 class="overdue-details-title">
                        <i class="fas fa-check-circle"></i>
                        المشاريع المكتملة بتأخير والمشاركين من القسم
                    </h5>
                    <div class="overdue-projects-list">
                        @foreach($projectOverdueStats['completed_late_projects'] as $item)
                        <div class="overdue-project-item completed">
                            <div class="overdue-project-header">
                                <h6 class="overdue-project-name">
                                    <i class="fas fa-project-diagram"></i>
                                    {{ $item['project']->name }}
                                </h6>
                            </div>
                            <div class="overdue-project-participants">
                                <strong>المشاركين اللي اكملوا بتأخير من القسم:</strong>
                                <div class="participants-list-detailed">
                                    @foreach($item['participants'] as $participant)
                                    <div class="participant-card completed-late">
                                        <div class="participant-header">
                                            <span class="participant-name">
                                                <i class="fas fa-user-check"></i>
                                                {{ $participant['name'] }}
                                            </span>
                                            <span class="overdue-badge-sm info">
                                                <i class="fas fa-check-circle"></i>
                                                اكتمل بتأخير {{ $participant['days_late'] }} يوم
                                            </span>
                                        </div>
                                        <div class="participant-meta">
                                            <span class="meta-item">
                                                <i class="fas fa-percentage"></i>
                                                نسبة المشاركة: <strong>{{ $participant['project_share'] }}%</strong>
                                            </span>
                                            <span class="meta-item">
                                                <i class="fas fa-calendar-times"></i>
                                                الموعد: <strong>{{ \Carbon\Carbon::parse($participant['deadline'])->format('Y-m-d') }}</strong>
                                            </span>
                                            <span class="meta-item">
                                                <i class="fas fa-calendar-check"></i>
                                                اكتمل: <strong>{{ \Carbon\Carbon::parse($participant['completed_at'])->format('Y-m-d') }}</strong>
                                            </span>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
            @endif
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
                        <div class="task-breakdown">
                            <span>عادية: {{ $taskStats['regular']['total'] }}</span>
                            <span>قوالب: {{ $taskStats['template']['total'] }}</span>
                        </div>
                    </div>
                </div>

                <div class="task-summary-card">
                    <div class="task-icon new">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <div class="task-info">
                        <div class="task-number">{{ $taskStats['combined']['new'] }}</div>
                        <div class="task-label">جديدة</div>
                        <div class="task-breakdown">
                            <span>عادية: {{ $taskStats['regular']['new'] }}</span>
                            <span>قوالب: {{ $taskStats['template']['new'] }}</span>
                        </div>
                    </div>
                </div>

                <div class="task-summary-card">
                    <div class="task-icon progress">
                        <i class="fas fa-spinner"></i>
                    </div>
                    <div class="task-info">
                        <div class="task-number">{{ $taskStats['combined']['in_progress'] }}</div>
                        <div class="task-label">قيد التنفيذ</div>
                        <div class="task-breakdown">
                            <span>عادية: {{ $taskStats['regular']['in_progress'] }}</span>
                            <span>قوالب: {{ $taskStats['template']['in_progress'] }}</span>
                        </div>
                    </div>
                </div>

                <div class="task-summary-card">
                    <div class="task-icon paused">
                        <i class="fas fa-pause"></i>
                    </div>
                    <div class="task-info">
                        <div class="task-number">{{ $taskStats['combined']['paused'] }}</div>
                        <div class="task-label">متوقفة</div>
                        <div class="task-breakdown">
                            <span>عادية: {{ $taskStats['regular']['paused'] }}</span>
                            <span>قوالب: {{ $taskStats['template']['paused'] }}</span>
                        </div>
                    </div>
                </div>

                <div class="task-summary-card">
                    <div class="task-icon completed">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="task-info">
                        <div class="task-number">{{ $taskStats['combined']['completed'] }}</div>
                        <div class="task-label">مكتملة</div>
                        <div class="task-breakdown">
                            <span>عادية: {{ $taskStats['regular']['completed'] }}</span>
                            <span>قوالب: {{ $taskStats['template']['completed'] }}</span>
                        </div>
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
                        <div class="task-breakdown">
                            <span>عادية: {{ $combinedTaskStats['regular']['original']['total'] }}</span>
                            <span>قوالب: {{ $combinedTaskStats['template']['original']['total'] }}</span>
                        </div>
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
                        <div class="task-breakdown">
                            <span>عادية: {{ $combinedTaskStats['regular']['additional']['total'] }}</span>
                            <span>قوالب: {{ $combinedTaskStats['template']['additional']['total'] }}</span>
                        </div>
                    </div>
                </div>
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
            </div>
            @endif
        </div>

        <!-- Time Statistics Section -->
        <div class="time-stats-section">
            <div class="section-header">
                <h3>
                    <i class="fas fa-clock"></i>
                    إحصائيات الوقت
                </h3>
            </div>
            <div class="time-stats-grid">
                <div class="time-stat-card estimated">
                    <div class="time-icon">
                        <i class="fas fa-hourglass-start"></i>
                    </div>
                    <div class="time-content">
                        <div class="time-number">{{ $timeStats['estimated_formatted'] }}</div>
                        <div class="time-label">الوقت المقدر</div>
                        <div class="time-description">للمهام ذات الوقت المحدد</div>
                    </div>
                </div>

                <div class="time-stat-card flexible">
                    <div class="time-icon">
                        <i class="fas fa-expand-arrows-alt"></i>
                    </div>
                    <div class="time-content">
                        <div class="time-number">{{ $timeStats['flexible_spent_formatted'] }}</div>
                        <div class="time-label">الوقت المرن</div>
                        <div class="time-description">المستهلك في المهام المرنة</div>
                    </div>
                </div>

                <div class="time-stat-card total">
                    <div class="time-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="time-content">
                        <div
                            class="time-number"
                            id="dept-actual-timer"
                            data-initial-minutes="{{ (int)($timeStats['spent_minutes'] ?? 0) }}"
                            data-active-count="{{ (int)(($taskStats['regular']['in_progress'] ?? 0) + ($taskStats['template']['in_progress'] ?? 0)) }}"
                            data-started-at="{{ now()->timestamp * 1000 }}"
                        >
                            @php
                                $totalMinutes = (int)($timeStats['spent_minutes'] ?? 0);
                                $hours = intval($totalMinutes / 60);
                                $minutes = $totalMinutes % 60;
                            @endphp
                            {{ sprintf('%d:%02d:%02d', $hours, $minutes, 0) }}
                        </div>
                        <div class="time-label">إجمالي الوقت</div>
                        <div class="time-description">الوقت الفعلي المستهلك (ساعات:دقائق:ثواني)</div>
                    </div>
                </div>

                @if($timeStats['estimated_minutes'] > 0)
                <div class="time-stat-card efficiency">
                    <div class="time-icon">
                        <i class="fas fa-tachometer-alt"></i>
                    </div>
                    <div class="time-content">
                        <div class="time-number">{{ $timeStats['efficiency'] }}%</div>
                        <div class="time-label">الكفاءة</div>
                        <div class="time-description">نسبة الوقت المقدر للفعلي</div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Department Analytics -->
        <div class="department-analytics">
            <!-- Projects Analytics -->
            <div class="analytics-section">
                <div class="section-header">
                    <h3>
                        <i class="fas fa-chart-pie"></i>
                        تحليل مشاريع القسم
                    </h3>
                </div>
                <div class="analytics-grid">
                    <div class="analytics-card">
                        <div class="chart-container">
                            <canvas id="projectStatusChart"></canvas>
                        </div>
                        <div class="chart-legend">
                            <div class="legend-item new">
                                <span class="legend-color"></span>
                                جديد: {{ $projectStats['new'] }}
                            </div>
                            <div class="legend-item progress">
                                <span class="legend-color"></span>
                                قيد التنفيذ: {{ $projectStats['in_progress'] }}
                            </div>
                            <div class="legend-item completed">
                                <span class="legend-color"></span>
                                مكتمل: {{ $projectStats['completed'] }}
                            </div>
                            <div class="legend-item cancelled">
                                <span class="legend-color"></span>
                                ملغي: {{ $projectStats['cancelled'] }}
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
                            @foreach($overdueProjects->take(5) as $project)
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
                            @if($overdueProjects->count() > 5)
                            <div class="more-projects">و{{ $overdueProjects->count() - 5 }} مشروع آخر...</div>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Tasks Analytics -->
            <div class="analytics-section">
                <div class="section-header">
                    <h3>
                        <i class="fas fa-chart-bar"></i>
                        تحليل مهام القسم
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
                            <h4>إحصائيات المهام التفصيلية</h4>
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
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Department Revisions - Modern Design -->
        @if(isset($revisionStats) && $revisionStats['total'] > 0)
        <div class="section-modern">
            <div class="section-header">
                <h2>
                    <i class="fas fa-clipboard-check"></i>
                    إحصائيات التعديلات للقسم
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
                @if(isset($revisionStats['approval_rate']))
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
                @endif

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

        <!-- Task Transfer Statistics -->
        @if(isset($departmentTransferStats) && $departmentTransferStats['has_transfers'])
        <div class="section-modern">
            <div class="section-header">
                <h2>
                    <i class="fas fa-exchange-alt"></i>
                    إحصائيات نقل المهام
                </h2>
            </div>

            <div class="transfer-stats-grid">
                <div class="transfer-stat-card total">
                    <div class="transfer-stat-icon">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <div class="transfer-stat-content">
                        <div class="transfer-stat-number">{{ $departmentTransferStats['total_transfers'] }}</div>
                        <div class="transfer-stat-label">إجمالي المهام المنقولة</div>
                        <div class="transfer-stat-breakdown">
                            <span>عادية: {{ $departmentTransferStats['regular_transfers'] }}</span>
                            <span>قوالب: {{ $departmentTransferStats['template_transfers'] }}</span>
                        </div>
                    </div>
                </div>

                <div class="transfer-stat-card additional">
                    <div class="transfer-stat-icon">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <div class="transfer-stat-content">
                        <div class="transfer-stat-number">{{ $departmentTransferStats['additional_tasks'] }}</div>
                        <div class="transfer-stat-label">المهام الإضافية</div>
                        <div class="transfer-stat-breakdown">
                            <span>عادية: {{ $departmentTransferStats['additional_regular'] }}</span>
                            <span>قوالب: {{ $departmentTransferStats['additional_template'] }}</span>
                        </div>
                    </div>
                </div>

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
                        <div class="transfer-stat-label">سجلات النقل</div>
                        <div class="transfer-stat-breakdown">
                            <span>جميع عمليات النقل</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Transfers -->
            @if(count($departmentTransferStats['recent_transfers']) > 0)
            <div class="recent-transfers-section">
                <h3>
                    <i class="fas fa-history"></i>
                    آخر عمليات النقل في القسم
                </h3>
                <div class="recent-transfers-list">
                    @foreach($departmentTransferStats['recent_transfers'] as $transfer)
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
                                <span class="transfer-type-badge {{ $transfer->transfer_type }}">
                                    {{ $transfer->transfer_type == 'positive' ? 'إيجابي' : 'سلبي' }}
                                </span>
                            </div>
                            <div class="transfer-details">
                                <span class="transfer-users">
                                    <i class="fas fa-user"></i>
                                    من: <strong>{{ $transfer->from_user_name }}</strong>
                                    <i class="fas fa-arrow-left mx-2"></i>
                                    إلى: <strong>{{ $transfer->to_user_name }}</strong>
                                </span>
                                <span class="transfer-time">
                                    <i class="fas fa-clock"></i>
                                    {{ \Carbon\Carbon::parse($transfer->transferred_at)->diffForHumans() }}
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

        <!-- Teams Section -->
        @if($teams->count() > 0)
        <div class="section-modern">
            <div class="section-header">
                <h2>
                    <i class="fas fa-users-cog"></i>
                    فرق العمل في القسم
                </h2>
                <span class="section-count">{{ $teams->count() }} فريق</span>
            </div>

            <div class="teams-grid">
                @foreach($teams as $team)
                <div class="team-card">
                    <div class="team-header">
                        <div class="team-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="team-info">
                            <h4>{{ $team->name }}</h4>
                            <p class="team-owner">
                                <i class="fas fa-crown"></i>
                                مدير الفريق: {{ $team->owner->name }}
                            </p>
                        </div>
                    </div>

                    <div class="team-stats">
                        <div class="team-stat members">
                            <div class="team-stat-content">
                                <div class="team-stat-number">{{ $team->users->count() }}</div>
                                <div class="team-stat-label">عضو في الفريق</div>
                            </div>
                        </div>

                        <div class="team-stat projects">
                            <div class="team-stat-content">
                                <div class="team-stat-number">{{ $team->active_projects_count }}</div>
                                <div class="team-stat-label">مشروع نشط</div>
                            </div>
                        </div>

                        <div class="team-stat tasks">
                            <div class="team-stat-content">
                                <div class="team-stat-number">{{ $team->completed_tasks_count }}</div>
                                <div class="team-stat-label">مهمة مكتملة</div>
                            </div>
                        </div>

                        <div class="team-stat performance">
                            <div class="team-stat-content">
                                @php
                                    // حساب معدل الأداء العام للفريق
                                    $teamUserIds = $team->users->pluck('id')->toArray();
                                    $teamTotalTasks = DB::table('task_users')
                                        ->whereIn('user_id', $teamUserIds)
                                        ->count();

                                    $teamTotalTemplateTasks = DB::table('template_task_user')
                                        ->whereIn('user_id', $teamUserIds)
                                        ->count();

                                    $allTeamTasks = $teamTotalTasks + $teamTotalTemplateTasks;
                                    $teamPerformance = $allTeamTasks > 0 ? round(($team->completed_tasks_count / $allTeamTasks) * 100) : 0;
                                @endphp
                                <div class="team-stat-number">{{ $teamPerformance }}%</div>
                                <div class="team-stat-label">معدل الإنجاز</div>
                            </div>
                        </div>
                    </div>

                    <div class="team-meta">
                        <div class="team-created">
                            <i class="fas fa-calendar-plus"></i>
                            تأسس في {{ $team->created_at->format('Y/m/d') }}
                        </div>
                        <div class="team-updated">
                            <i class="fas fa-clock"></i>
                            آخر تحديث {{ $team->updated_at->diffForHumans() }}
                        </div>
                    </div>

                    <div class="team-footer">
                        <a href="{{ route('departments.teams.show', ['department' => urlencode($department), 'teamId' => $team->id]) }}"
                           class="view-team-details">
                            <i class="fas fa-eye"></i>
                            دخول إلى الفريق
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @else
        <div class="section-modern">
            <div class="empty-state">
                <i class="fas fa-users-slash"></i>
                <h3>لا توجد فرق في هذا القسم</h3>
                <p>لم يتم إنشاء أي فرق عمل في قسم {{ $department }} بعد</p>
            </div>
        </div>
        @endif

        <!-- ✅ آخر النشاطات للقسم - Recent Activities Section -->
        <div class="row">
            <!-- آخر المهام -->
            <div class="col-lg-6 col-md-6 mb-4">
                <div class="section-modern">
                    <div class="section-header">
                        <h2>
                            <i class="fas fa-tasks"></i>
                            آخر المهام - {{ $department }}
                        </h2>
                        <span class="section-count">{{ $recentTasks->count() }}</span>
                    </div>

                    @if($recentTasks->count() > 0)
                    <div class="recent-activities-list">
                        @foreach($recentTasks->take(6) as $recentTask)
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
                                    <div class="user-name">
                                        <i class="fas fa-user"></i>
                                        {{ $recentTask->user_name }}
                                    </div>
                                    @if($recentTask->project_name)
                                    <div class="project-name">
                                        <i class="fas fa-project-diagram"></i>
                                        {{ Str::limit($recentTask->project_name, 20) }}
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
                        <p>لا توجد مهام حديثة في هذا القسم</p>
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
                            آخر المشاريع - {{ $department }}
                        </h2>
                        <span class="section-count">{{ $recentProjects->count() }}</span>
                    </div>

                    @if($recentProjects->count() > 0)
                    <div class="recent-projects-list">
                        @foreach($recentProjects->take(6) as $recentProject)
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
                        <p>لا توجد مشاريع حديثة في هذا القسم</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Revision Transfer Statistics Section -->
        @if(isset($departmentRevisionTransferStats) && $departmentRevisionTransferStats['has_transfers'])
        <div class="section-modern">
            <div class="section-header">
                <h2>
                    <i class="fas fa-exchange-alt"></i>
                    إحصائيات نقل التعديلات (مهام إضافية) - {{ $department }}
                </h2>
            </div>

            <div class="transfer-stats-grid">
                <!-- Received Revisions Card -->
                <div class="transfer-stat-card received">
                    <div class="transfer-stat-icon">
                        <i class="fas fa-arrow-down"></i>
                    </div>
                    <div class="transfer-stat-content">
                        <div class="transfer-stat-number">{{ $departmentRevisionTransferStats['transferred_to_me'] }}</div>
                        <div class="transfer-stat-label">تعديلات منقولة للقسم</div>
                        <div class="transfer-stat-breakdown">
                            <span><i class="fas fa-wrench"></i> كمنفذ: {{ $departmentRevisionTransferStats['executor_transferred_to_me'] }}</span>
                            <span><i class="fas fa-check-circle"></i> كمراجع: {{ $departmentRevisionTransferStats['reviewer_transferred_to_me'] }}</span>
                        </div>
                        <div class="transfer-stat-note">
                            <i class="fas fa-info-circle"></i>
                            تعديلات إضافية لأعضاء القسم
                        </div>
                    </div>
                </div>

                <!-- Sent Revisions Card -->
                <div class="transfer-stat-card sent">
                    <div class="transfer-stat-icon">
                        <i class="fas fa-arrow-up"></i>
                    </div>
                    <div class="transfer-stat-content">
                        <div class="transfer-stat-number">{{ $departmentRevisionTransferStats['transferred_from_me'] }}</div>
                        <div class="transfer-stat-label">تعديلات منقولة من القسم</div>
                        <div class="transfer-stat-breakdown">
                            <span><i class="fas fa-wrench"></i> كمنفذ: {{ $departmentRevisionTransferStats['executor_transferred_from_me'] }}</span>
                            <span><i class="fas fa-check-circle"></i> كمراجع: {{ $departmentRevisionTransferStats['reviewer_transferred_from_me'] }}</span>
                        </div>
                        <div class="transfer-stat-note">
                            <i class="fas fa-info-circle"></i>
                            تعديلات تم نقلها خارج القسم
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
                            {{ $departmentRevisionTransferStats['transferred_to_me'] }}
                        </div>
                        <div class="transfer-stat-label">إجمالي التعديلات الإضافية</div>
                        <div class="transfer-stat-breakdown">
                            <span><i class="fas fa-star"></i> تعديلات لم تكن من نصيب القسم</span>
                        </div>
                        <div class="transfer-stat-note">
                            <i class="fas fa-medal"></i>
                            دليل على تعاون أعضاء القسم
                        </div>
                    </div>
                </div>
            </div>

            @if($departmentRevisionTransferStats['transferred_to_me_details']->count() > 0)
            <div class="recent-transfers-section">
                <h3>
                    <i class="fas fa-history"></i>
                    تفاصيل التعديلات المنقولة للقسم (أحدث {{ $departmentRevisionTransferStats['transferred_to_me_details']->count() }} تعديل)
                </h3>
                <div class="recent-transfers-list">
                    @foreach($departmentRevisionTransferStats['transferred_to_me_details'] as $transfer)
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
                                    إلى: <strong>{{ $transfer->toUser->name }}</strong>
                                </span>
                                @if($transfer->fromUser)
                                <span class="project-info">
                                    <i class="fas fa-user"></i>
                                    من: {{ $transfer->fromUser->name }}
                                </span>
                                @endif
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
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        @endif

        <!-- الصف الثاني: آخر الاجتماعات والتعديلات -->
        <div class="row">
            <!-- آخر الاجتماعات -->
            <div class="col-lg-6 col-md-6 mb-4">
                <div class="section-modern">
                    <div class="section-header">
                        <h2>
                            <i class="fas fa-calendar-alt"></i>
                            آخر الاجتماعات - {{ $department }}
                        </h2>
                        <span class="section-count">{{ $recentMeetings->count() }}</span>
                    </div>

                    @if($recentMeetings->count() > 0)
                    <div class="recent-meetings-list">
                        @foreach($recentMeetings->take(6) as $recentMeeting)
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
                        <p>لا توجد اجتماعات حديثة في هذا القسم</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Department Error Statistics Section -->
            @if(isset($departmentErrorStats) && $departmentErrorStats['has_errors'])
            <div class="section-modern">
                <div class="section-header">
                    <h2>
                        <i class="fas fa-exclamation-triangle"></i>
                        إحصائيات الأخطاء - {{ $department }}
                    </h2>
                </div>

                <div class="stats-grid">
                    <div class="stat-card-modern danger">
                        <div class="stat-icon">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number">{{ $departmentErrorStats['total_errors'] }}</div>
                            <div class="stat-label">إجمالي الأخطاء</div>
                            <div class="stat-trend">
                                جوهرية: {{ $departmentErrorStats['critical_errors'] }} | عادية: {{ $departmentErrorStats['normal_errors'] }}
                            </div>
                        </div>
                    </div>

                    <div class="stat-card-modern warning">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number">{{ count($departmentErrorStats['top_users']) }}</div>
                            <div class="stat-label">موظفين لديهم أخطاء</div>
                            <div class="stat-trend">
                                جودة: {{ $departmentErrorStats['by_category']['quality'] }} | فنية: {{ $departmentErrorStats['by_category']['technical'] }}
                            </div>
                        </div>
                    </div>

                    <div class="stat-card-modern info">
                        <div class="stat-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number">{{ $departmentErrorStats['by_category']['deadline'] }}</div>
                            <div class="stat-label">أخطاء مواعيد</div>
                            <div class="stat-trend">
                                تواصل: {{ $departmentErrorStats['by_category']['communication'] }} | إجرائية: {{ $departmentErrorStats['by_category']['procedural'] }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- آخر التعديلات -->
            @if($latestRevisions->count() > 0)
            <div class="col-lg-6 col-md-6 mb-4">
                <div class="section-modern">
                    <div class="section-header">
                        <h2>
                            <i class="fas fa-history"></i>
                            آخر التعديلات - {{ $department }}
                        </h2>
                    </div>

                    <div class="recent-updates-list">
                        @if($latestRevisions->count() > 0)
                            @foreach($latestRevisions->take(6) as $revision)
                            <div class="alert-item" data-revision-type="{{ $revision->revision_type }}" data-status="{{ $revision->status }}">
                                <div class="alert-icon">
                                    @if($revision->status == 'approved')
                                        <i class="fas fa-check-circle"></i>
                                    @elseif($revision->status == 'rejected')
                                        <i class="fas fa-times-circle"></i>
                                    @else
                                        <i class="fas fa-clock"></i>
                                    @endif
                                </div>
                                <div class="alert-content">
                                    <div class="alert-title">
                                        <span class="revision-name">{{ Str::limit($revision->title, 35) }}</span>
                                        <div class="revision-type-badge">
                                            <i class="fas fa-{{ $revision->revision_type == 'project' ? 'project-diagram' : ($revision->revision_type == 'general' ? 'globe' : 'tasks') }}"></i>
                                            {{ $revision->revision_type == 'project' ? 'مشروع' : ($revision->revision_type == 'general' ? 'عام' : 'مهمة') }}
                                        </div>
                                    </div>
                                    <div class="alert-meta">
                                        <div class="revision-creator">
                                            <i class="fas fa-user"></i>
                                            {{ $revision->creator->name }}
                                        </div>
                                        <div class="revision-time">
                                            <i class="fas fa-clock"></i>
                                            {{ $revision->revision_date->diffForHumans() }}
                                        </div>
                                    </div>
                                </div>
                                <div class="alert-status status-{{ $revision->status }}">
                                    @if($revision->status == 'approved')
                                        <i class="fas fa-check-circle"></i>
                                        موافق عليه
                                    @elseif($revision->status == 'rejected')
                                        <i class="fas fa-times-circle"></i>
                                        مرفوض
                                    @else
                                        <i class="fas fa-clock"></i>
                                        في الانتظار
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Back Button -->
        <div class="text-center mt-4">
            <a href="{{ route('company-projects.dashboard') }}" class="action-btn outline">
                <i class="fas fa-arrow-right"></i>
                العودة إلى لوحة التحكم
            </a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@endpush

