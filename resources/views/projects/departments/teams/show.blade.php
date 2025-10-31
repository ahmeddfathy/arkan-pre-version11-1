@extends('layouts.app')

@section('title', 'تفاصيل فريق ' . $team->name)

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Pass data to JavaScript
    window.teamData = {
        projectStats: @json($projectStats),
        taskStats: @json($taskStats),
        timeStats: {
            estimated_hours: {{ round($timeStats['estimated_minutes'] / 60, 1) }},
            spent_hours: {{ round($timeStats['spent_minutes'] / 60, 1) }}
        }
    };
</script>
<script src="{{ asset('js/project-dashboard/teams/filters.js') }}"></script>
<script src="{{ asset('js/project-dashboard/teams/timer.js') }}"></script>
<script src="{{ asset('js/project-dashboard/teams/charts.js') }}"></script>
@endpush

@push('styles')
<link rel="stylesheet" href="{{ asset('css/projects-dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('css/project-dashboard/teams.css') }}">
<link rel="stylesheet" href="{{ asset('css/project-dashboard/teams/show.css') }}">
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

    @media (max-width: 768px) {
        .container-fluid {
            padding-right: 15px;
            padding-left: 15px;
        }
    }

    /* Modern Dashboard Full Width */
    .modern-dashboard {
        width: 100%;
        max-width: 100%;
        margin: 0;
        padding: 1.5rem;
    }

    /* Projects Overview Grid */
    .projects-overview-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    @media (max-width: 768px) {
        .projects-overview-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
    }

    @media (max-width: 480px) {
        .projects-overview-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Project Summary Card */
    .project-summary-card {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        border: 2px solid #e9ecef;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        align-items: center;
        gap: 15px;
        position: relative;
        overflow: hidden;
    }

    .project-summary-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        opacity: 0;
        transition: opacity 0.3s ease;
    }



    /* Project Icon */
    .project-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: white;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        transition: transform 0.3s ease;
    }


    .project-icon.all {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .project-icon.progress {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    }

    .project-icon.completed {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    }

    .project-icon.new {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    }

    .project-icon.cancelled {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    }

    /* Project Info */
    .project-info {
        flex: 1;
    }

    .project-number {
        font-size: 28px;
        font-weight: 700;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 5px;
        line-height: 1.2;
    }

    .project-label {
        font-size: 14px;
        color: #6b7280;
        font-weight: 500;
        margin-bottom: 2px;
    }

    .project-sublabel {
        font-size: 12px;
        color: #9ca3af;
    }

    /* Completion Rate Card */
    .project-summary-card.completion-rate {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        color: white;
    }

    .project-summary-card.completion-rate .project-label {
        color: rgba(255, 255, 255, 0.95);
        font-weight: 600;
    }

    .completion-circle {
        width: 60px;
        height: 60px;
        position: relative;
    }

    .circular-chart {
        display: block;
        width: 100%;
        height: 100%;
    }

    .circle-bg {
        fill: none;
        stroke: rgba(255, 255, 255, 0.2);
        stroke-width: 3;
    }

    .circle {
        fill: none;
        stroke: white;
        stroke-width: 3;
        stroke-linecap: round;
        animation: progress 1s ease-out forwards;
    }

    @keyframes progress {
        0% {
            stroke-dasharray: 0 100;
        }
    }

    .percentage {
        fill: white;
        font-family: 'Cairo', sans-serif;
        font-size: 8px;
        font-weight: 700;
        text-anchor: middle;
    }

    /* Section Modern */
    .section-modern {
        background: white;
        border-radius: 20px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }

    .section-modern .section-header {
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 2px solid #e9ecef;
    }

    .section-modern .section-header h2 {
        font-size: 24px;
        font-weight: 700;
        color: #1f2937;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .section-modern .section-header h2 i {
        color: #667eea;
        font-size: 28px;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .project-number {
            font-size: 24px;
        }

        .project-icon {
            width: 50px;
            height: 50px;
            font-size: 20px;
        }

        .project-summary-card {
            padding: 15px;
        }

        .section-modern {
            padding: 20px;
        }
    }

    /* Animation on load */
    .project-summary-card {
        animation: fadeInUp 0.6s ease-out;
        animation-fill-mode: both;
    }

    .project-summary-card:nth-child(1) { animation-delay: 0.1s; }
    .project-summary-card:nth-child(2) { animation-delay: 0.15s; }
    .project-summary-card:nth-child(3) { animation-delay: 0.2s; }
    .project-summary-card:nth-child(4) { animation-delay: 0.25s; }
    .project-summary-card:nth-child(5) { animation-delay: 0.3s; }
    .project-summary-card:nth-child(6) { animation-delay: 0.35s; }
    .project-summary-card:nth-child(7) { animation-delay: 0.4s; }
    .project-summary-card:nth-child(8) { animation-delay: 0.45s; }
    .project-summary-card:nth-child(9) { animation-delay: 0.5s; }
    .project-summary-card:nth-child(10) { animation-delay: 0.55s; }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>
@endpush

@section('content')
<div class="modern-dashboard">
    <!-- Breadcrumb -->
    <div class="container-fluid">
        <nav class="breadcrumb">
            <a href="{{ route('company-projects.dashboard') }}" class="breadcrumb-item">لوحة التحكم</a>
            <a href="{{ route('departments.show', urlencode($department)) }}" class="breadcrumb-item">{{ $department }}</a>
            <span class="breadcrumb-item active">{{ $team->name }}</span>
        </nav>
    </div>

    <!-- Team Header -->
    <div class="team-detail-header">
        <div class="container-fluid">
            <div class="team-info">
                <h1 class="team-name">{{ $team->name }}</h1>
                <p class="team-description">
                    قسم {{ $department }} |
                    {{ $teamMembers->count() }} عضو |
                    تأسس {{ $team->created_at->format('Y') }}
                </p>
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
                        عرض/إخفاء الفلاتر
                    </button>
                </div>
                <div class="filters-content collapsed" id="filtersContent">
                    <form method="GET" action="{{ route('departments.teams.show', ['department' => urlencode($department), 'teamId' => $team->id]) }}" id="teamFiltersForm">
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
                                <a href="{{ route('departments.teams.show', ['department' => urlencode($department), 'teamId' => $team->id]) }}" class="btn-reset-filters">
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
            <div class="filter-indicator-team">
                <i class="fas fa-filter text-primary"></i>
                <span class="filter-text">فلتر مطبق: {{ $periodDescription }}</span>
            </div>
        </div>
        @endif



        <!-- Team Owner Information -->
        <div class="team-owner-info">
            <div class="owner-header">
                @php
                    $ownerNameParts = explode(' ', trim($team->owner->name));
                    $ownerInitials = count($ownerNameParts) >= 2
                        ? substr($ownerNameParts[0], 0, 1) . substr($ownerNameParts[1], 0, 1)
                        : substr($team->owner->name, 0, 2);

                    // تحديد الصورة بناءً على الجنس
                    $ownerAvatarImage = 'man.gif'; // الافتراضي للرجال
                    if ($team->owner->gender === 'female' || $team->owner->gender === 'أنثى' || $team->owner->gender === 'انثى') {
                        $ownerAvatarImage = 'women-avatar.gif';
                    }
                @endphp
                <img
                    src="{{ asset('avatars/' . $ownerAvatarImage) }}"
                    alt="{{ $team->owner->name }}"
                    class="owner-avatar"
                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                />
                <div class="owner-avatar-fallback" style="display: none;">
                    {{ $ownerInitials }}
                </div>
                <div class="owner-details">
                    <h3>{{ $team->owner->name }}</h3>
                    <p class="owner-role">مالك الفريق ومدير القسم</p>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card-modern primary">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number">{{ $teamMembers->count() }}</div>
                        <div class="stat-label">أعضاء الفريق</div>
                    </div>
                </div>

                <div class="stat-card-modern success">
                    <div class="stat-icon">
                        <i class="fas fa-project-diagram"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number">{{ number_format($projectStats['total'], 1) }}</div>
                        <div class="stat-label">إجمالي نسبة المشاريع</div>
                        <div class="stat-sublabel">{{ $teamProjects->count() }} مشروع</div>
                    </div>
                </div>

                <div class="stat-card-modern info">
                    <div class="stat-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number">{{ $taskStats['combined']['total'] }}</div>
                        <div class="stat-label">إجمالي المهام</div>
                    </div>
                </div>

                <div class="stat-card-modern warning">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"
                             id="team-actual-timer"
                             data-initial-minutes="{{ (int)($timeStats['spent_minutes'] ?? 0) }}"
                             data-active-count="{{ (int)(($taskStats['regular']['in_progress'] ?? 0) + ($taskStats['template']['in_progress'] ?? 0)) }}">
                            @php
                                $totalMinutes = (int)($timeStats['spent_minutes'] ?? 0);
                                $hours = intval($totalMinutes / 60);
                                $minutes = $totalMinutes % 60;
                            @endphp
                            {{ sprintf('%d:%02d:%02d', $hours, $minutes, 0) }}
                            @if(($taskStats['regular']['in_progress'] ?? 0) + ($taskStats['template']['in_progress'] ?? 0) > 0)
                                <span class="real-time-indicator" style="color: #28a745; font-size: 0.8em; margin-left: 5px;">●</span>
                            @endif
                        </div>
                        <div class="stat-label">إجمالي وقت العمل (ساعات:دقائق:ثواني)
                            @if(($taskStats['regular']['in_progress'] ?? 0) + ($taskStats['template']['in_progress'] ?? 0) > 0)
                                <span style="color: #28a745; font-size: 0.9em;"> - نشط الآن</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Flexible Time Stat Card -->
                <div class="stat-card-modern success">
                    <div class="stat-icon">
                        <i class="fas fa-expand-arrows-alt"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number">{{ $timeStats['flexible_spent_formatted'] }}</div>
                        <div class="stat-label">الوقت المرن</div>
                        <div class="stat-sublabel">المهام بدون وقت محدد</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Statistics & Charts -->
        <div class="team-analytics">
            <!-- Projects Analytics -->
            <!-- Projects Status Cards -->
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
                            <div class="project-number">{{ $projectStats['total'] ?? 0 }}</div>
                            <div class="project-label">إجمالي المشاريع</div>
                        </div>
                    </div>

                    <div class="project-summary-card">
                        <div class="project-icon progress">
                            <i class="fas fa-play-circle"></i>
                        </div>
                        <div class="project-info">
                            <div class="project-number">{{ $projectStats['in_progress'] ?? 0 }}</div>
                            <div class="project-label">جاري</div>
                        </div>
                    </div>

                    <div class="project-summary-card" style="border-color: #f39c12;">
                        <div class="project-icon" style="background: #f39c12;">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="project-info">
                            <div class="project-number">{{ $projectStats['waiting_form'] ?? 0 }}</div>
                            <div class="project-label">واقف (نموذج)</div>
                        </div>
                    </div>

                    <div class="project-summary-card" style="border-color: #3498db;">
                        <div class="project-icon" style="background: #3498db;">
                            <i class="fas fa-question-circle"></i>
                        </div>
                        <div class="project-info">
                            <div class="project-number">{{ $projectStats['waiting_questions'] ?? 0 }}</div>
                            <div class="project-label">واقف (أسئلة)</div>
                        </div>
                    </div>

                    <div class="project-summary-card" style="border-color: #e67e22;">
                        <div class="project-icon" style="background: #e67e22;">
                            <i class="fas fa-user-clock"></i>
                        </div>
                        <div class="project-info">
                            <div class="project-number">{{ $projectStats['waiting_client'] ?? 0 }}</div>
                            <div class="project-label">واقف (عميل)</div>
                        </div>
                    </div>

                    <div class="project-summary-card" style="border-color: #9b59b6;">
                        <div class="project-icon" style="background: #9b59b6;">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div class="project-info">
                            <div class="project-number">{{ $projectStats['waiting_call'] ?? 0 }}</div>
                            <div class="project-label">واقف (مكالمة)</div>
                        </div>
                    </div>

                    <div class="project-summary-card" style="border-color: #e74c3c;">
                        <div class="project-icon" style="background: #e74c3c;">
                            <i class="fas fa-pause-circle"></i>
                        </div>
                        <div class="project-info">
                            <div class="project-number">{{ $projectStats['paused'] ?? 0 }}</div>
                            <div class="project-label">موقوف</div>
                        </div>
                    </div>

                    <div class="project-summary-card" style="border-color: #f093fb;">
                        <div class="project-icon" style="background: #f093fb;">
                            <i class="fas fa-file-upload"></i>
                        </div>
                        <div class="project-info">
                            <div class="project-number">{{ $projectStats['draft_delivery'] ?? 0 }}</div>
                            <div class="project-label">تسليم مسودة</div>
                        </div>
                    </div>

                    <div class="project-summary-card" style="border-color: #00f2fe;">
                        <div class="project-icon completed" style="background: #00f2fe;">
                            <i class="fas fa-check-double"></i>
                        </div>
                        <div class="project-info">
                            <div class="project-number">{{ $projectStats['final_delivery'] ?? 0 }}</div>
                            <div class="project-label">تسليم نهائي</div>
                        </div>
                    </div>

                    <div class="project-summary-card completion-rate">
                        <div class="completion-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845
                                    a 15.9155 15.9155 0 0 1 0 31.831
                                    a 15.9155 15.9155 0 0 1 0 -31.831"/>
                                <path class="circle" stroke-dasharray="{{ $projectStats['total'] > 0 ? round(($projectStats['final_delivery'] / $projectStats['total']) * 100) : 0 }}, 100" d="M18 2.0845
                                    a 15.9155 15.9155 0 0 1 0 31.831
                                    a 15.9155 15.9155 0 0 1 0 -31.831"/>
                                <text x="18" y="20.35" class="percentage">{{ $projectStats['total'] > 0 ? round(($projectStats['final_delivery'] / $projectStats['total']) * 100) : 0 }}%</text>
                            </svg>
                        </div>
                        <div class="project-info">
                            <div class="project-label">معدل التسليم النهائي</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="analytics-section">
                <div class="section-header">
                    <h3>
                        <i class="fas fa-chart-pie"></i>
                        إحصائيات المشاريع
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
                                جاري: {{ $projectStats['in_progress'] ?? 0 }}
                            </div>
                            <div class="legend-item" style="color: #f39c12;">
                                <span class="legend-color" style="background: #f39c12;"></span>
                                واقف (نموذج): {{ $projectStats['waiting_form'] ?? 0 }}
                            </div>
                            <div class="legend-item" style="color: #3498db;">
                                <span class="legend-color" style="background: #3498db;"></span>
                                واقف (أسئلة): {{ $projectStats['waiting_questions'] ?? 0 }}
                            </div>
                            <div class="legend-item" style="color: #e67e22;">
                                <span class="legend-color" style="background: #e67e22;"></span>
                                واقف (عميل): {{ $projectStats['waiting_client'] ?? 0 }}
                            </div>
                            <div class="legend-item" style="color: #9b59b6;">
                                <span class="legend-color" style="background: #9b59b6;"></span>
                                واقف (مكالمة): {{ $projectStats['waiting_call'] ?? 0 }}
                            </div>
                            <div class="legend-item" style="color: #e74c3c;">
                                <span class="legend-color" style="background: #e74c3c;"></span>
                                موقوف: {{ $projectStats['paused'] ?? 0 }}
                            </div>
                            <div class="legend-item" style="color: #f093fb;">
                                <span class="legend-color" style="background: #f093fb;"></span>
                                تسليم مسودة: {{ $projectStats['draft_delivery'] ?? 0 }}
                            </div>
                            <div class="legend-item completed">
                                <span class="legend-color" style="background: #00f2fe;"></span>
                                تسليم نهائي: {{ $projectStats['final_delivery'] ?? 0 }}
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
                            <div class="more-projects">وأكثر...</div>
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
                    @if(count($projectOverdueStats['overdue_projects']) > 0)
                    <div class="overdue-details-section">
                        <h5 class="overdue-details-title">
                            <i class="fas fa-list"></i>
                            تفاصيل المشاريع المتأخرة والمشاركين من الفريق
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
                                    <strong>المشاركين المتأخرين من الفريق:</strong>
                                    <div class="participants-list">
                                        @foreach($item['participants'] as $participant)
                                        <span class="participant-badge">
                                            <i class="fas fa-user-clock"></i>
                                            {{ $participant['name'] }}
                                            <small>({{ $participant['project_share'] }}% - متأخر {{ $participant['days_overdue'] }} يوم)</small>
                                        </span>
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
                            المشاريع المكتملة بتأخير والمشاركين من الفريق
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
                                    <strong>المشاركين اللي اكملوا بتأخير من الفريق:</strong>
                                    <div class="participants-list">
                                        @foreach($item['participants'] as $participant)
                                        <span class="participant-badge">
                                            <i class="fas fa-user-check"></i>
                                            {{ $participant['name'] }}
                                            <small>({{ $participant['project_share'] }}% - اكتمل بتأخير {{ $participant['days_late'] }} يوم)</small>
                                        </span>
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

            <!-- Tasks Analytics -->
            <div class="analytics-section">
                <div class="section-header">
                    <h3>
                        <i class="fas fa-chart-bar"></i>
                        إحصائيات المهام
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
                            <h4>تفصيل المهام</h4>
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
                                </div>
                            </div>

                            <div class="task-type template">
                                <div class="task-type-header">
                                    <i class="fas fa-template"></i>
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
                    @if($taskOverdueDetails['overdue_pending']->count() > 0)
                    <div class="overdue-details-section">
                        <h5 class="overdue-details-title">
                            <i class="fas fa-list"></i>
                            تفاصيل المهام المتأخرة من الفريق
                        </h5>
                        <div class="overdue-projects-list">
                            @foreach($taskOverdueDetails['overdue_pending']->groupBy('user_id') as $userId => $userTasks)
                            <div class="overdue-project-item">
                                <div class="overdue-project-header">
                                    <h6 class="overdue-project-name">
                                        <i class="fas fa-user"></i>
                                        {{ $userTasks->first()->user_name }}
                                    </h6>
                                    <span class="overdue-badge danger">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        {{ $userTasks->count() }} مهمة متأخرة
                                    </span>
                                </div>
                                <div class="overdue-project-participants">
                                    <strong>المهام المتأخرة:</strong>
                                    <div class="participants-list">
                                        @foreach($userTasks as $task)
                                        <span class="participant-badge">
                                            <i class="fas fa-{{ $task->task_type == 'template' ? 'layer-group' : 'tasks' }}"></i>
                                            {{ \Str::limit($task->task_name, 30) }}
                                            <small>({{ $task->task_type == 'template' ? 'قالب' : 'عادية' }} - متأخرة {{ $task->days_overdue }} يوم)</small>
                                        </span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Task Completed Late Details -->
                    @if($taskOverdueDetails['completed_late']->count() > 0)
                    <div class="overdue-details-section">
                        <h5 class="overdue-details-title">
                            <i class="fas fa-check-circle"></i>
                            المهام المكتملة بتأخير من الفريق
                        </h5>
                        <div class="overdue-projects-list">
                            @foreach($taskOverdueDetails['completed_late']->groupBy('user_id') as $userId => $userTasks)
                            <div class="overdue-project-item completed">
                                <div class="overdue-project-header">
                                    <h6 class="overdue-project-name">
                                        <i class="fas fa-user"></i>
                                        {{ $userTasks->first()->user_name }}
                                    </h6>
                                    <span class="overdue-badge info">
                                        <i class="fas fa-check-circle"></i>
                                        {{ $userTasks->count() }} مهمة مكتملة بتأخير
                                    </span>
                                </div>
                                <div class="overdue-project-participants">
                                    <strong>المهام المكتملة بتأخير:</strong>
                                    <div class="participants-list">
                                        @foreach($userTasks as $task)
                                        <span class="participant-badge">
                                            <i class="fas fa-{{ $task->task_type == 'template' ? 'layer-group' : 'tasks' }}"></i>
                                            {{ \Str::limit($task->task_name, 30) }}
                                            <small>({{ $task->task_type == 'template' ? 'قالب' : 'عادية' }} - اكتملت بتأخير {{ $task->days_late }} يوم)</small>
                                        </span>
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

            <!-- Time Analytics -->
            <div class="analytics-section">
                <div class="section-header">
                    <h3>
                        <i class="fas fa-stopwatch"></i>
                        إحصائيات الوقت
                    </h3>
                </div>
                <div class="analytics-grid">
                    <div class="analytics-card time-comparison">
                        <div class="chart-container">
                            <canvas id="timeChart"></canvas>
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
                                      id="team-efficiency-timer"
                                      data-initial-minutes="{{ (int)($timeStats['spent_minutes'] ?? 0) }}"
                                      data-active-count="{{ (int)(($taskStats['regular']['in_progress'] ?? 0) + ($taskStats['template']['in_progress'] ?? 0)) }}">
                                    @php
                                        $totalMinutes = (int)($timeStats['spent_minutes'] ?? 0);
                                        $hours = intval($totalMinutes / 60);
                                        $minutes = $totalMinutes % 60;
                                    @endphp
                                    {{ sprintf('%d:%02d:%02d', $hours, $minutes, 0) }}
                                    @if(($taskStats['regular']['in_progress'] ?? 0) + ($taskStats['template']['in_progress'] ?? 0) > 0)
                                        <span class="real-time-addition" style="color: #28a745; font-size: 0.8em;">●</span>
                                    @endif
                                </span>
                                <span class="time-label">وقت فعلي (ساعات:دقائق:ثواني)</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Revisions Analytics - Modern Design -->
        @if(isset($revisionStats) && $revisionStats['total'] > 0)
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

                <!-- Attachments & Review Time -->
                <div class="revision-stat-modern attachments">
                    <div class="stat-icon-modern">
                        <i class="fas fa-paperclip"></i>
                    </div>
                    <div class="stat-content-modern">
                        <div class="stat-number-modern">{{ $teamAttachmentStats['with_attachments'] ?? 0 }}</div>
                        <div class="stat-label-modern">بملفات مرفقة</div>
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
    @if(isset($teamTransferStats) && $teamTransferStats['has_transfers'])
        <div class="section-modern">
            <div class="section-header">
                <h2>
                    <i class="fas fa-exchange-alt"></i>
                    إحصائيات نقل المهام
                </h2>
            </div>

            <div class="transfer-stats-grid">
                <!-- Total Transfers Card -->
                <div class="transfer-stat-card total">
                    <div class="transfer-stat-icon">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <div class="transfer-stat-content">
                        <div class="transfer-stat-number">{{ $teamTransferStats['total_transfers'] }}</div>
                        <div class="transfer-stat-label">إجمالي عمليات النقل</div>
                        <div class="transfer-stat-breakdown">
                            <span><i class="fas fa-tasks"></i> عادية: {{ $teamTransferStats['regular_transfers'] }}</span>
                            <span><i class="fas fa-layer-group"></i> قوالب: {{ $teamTransferStats['template_transfers'] }}</span>
                        </div>
                    </div>
                </div>

                @if(count($teamTransferStats['recent_transfers']) > 0)
                <!-- Recent Transfers Card -->
                <div class="recent-transfers-card">
                    <div class="card-header-transfers">
                        <h4>
                            <i class="fas fa-history"></i>
                            آخر عمليات النقل
                        </h4>
                    </div>
                    <div class="recent-transfers-list">
                        @foreach(array_slice($teamTransferStats['recent_transfers'], 0, 3) as $transfer)
                        <div class="recent-transfer-item">
                            <div class="transfer-icon-small {{ $transfer->task_type }}">
                                <i class="fas fa-{{ $transfer->task_type == 'regular' ? 'tasks' : 'layer-group' }}"></i>
                            </div>
                            <div class="transfer-details-content">
                                <div class="transfer-task-info">
                                    <span class="task-name">{{ Str::limit($transfer->task_name, 30) }}</span>
                                    <span class="task-type-badge {{ $transfer->task_type }}">
                                        {{ $transfer->task_type == 'regular' ? 'عادية' : 'قالب' }}
                                    </span>
                                </div>
                                <div class="transfer-user-info">
                                    <span class="from-user">{{ $transfer->from_user_name }}</span>
                                    <i class="fas fa-arrow-left transfer-arrow"></i>
                                    <span class="to-user">{{ $transfer->to_user_name }}</span>
                                </div>
                                <div class="transfer-date">
                                    <i class="fas fa-calendar"></i>
                                    {{ \Carbon\Carbon::parse($transfer->transferred_at)->format('d/m/Y') }}
                                </div>
                            </div>
                        </div>
                        @endforeach
                        @if(count($teamTransferStats['recent_transfers']) > 3)
                        <div class="more-transfers">
                            +{{ count($teamTransferStats['recent_transfers']) - 3 }} عملية أخرى
                        </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Revision Transfer Statistics Section -->
        @if(isset($teamRevisionTransferStats) && $teamRevisionTransferStats['has_transfers'])
        <div class="section-modern">
            <div class="section-header">
                <h2>
                    <i class="fas fa-exchange-alt"></i>
                    إحصائيات نقل التعديلات (مهام إضافية) - {{ $team->name }}
                </h2>
            </div>

            <div class="transfer-stats-grid">
                <!-- Received Revisions Card -->
                <div class="transfer-stat-card received">
                    <div class="transfer-stat-icon">
                        <i class="fas fa-arrow-down"></i>
                    </div>
                    <div class="transfer-stat-content">
                        <div class="transfer-stat-number">{{ $teamRevisionTransferStats['transferred_to_me'] }}</div>
                        <div class="transfer-stat-label">تعديلات منقولة للفريق</div>
                        <div class="transfer-stat-breakdown">
                            <span><i class="fas fa-wrench"></i> كمنفذ: {{ $teamRevisionTransferStats['executor_transferred_to_me'] }}</span>
                            <span><i class="fas fa-check-circle"></i> كمراجع: {{ $teamRevisionTransferStats['reviewer_transferred_to_me'] }}</span>
                        </div>
                        <div class="transfer-stat-note">
                            <i class="fas fa-info-circle"></i>
                            تعديلات إضافية لأعضاء الفريق
                        </div>
                    </div>
                </div>

                <!-- Sent Revisions Card -->
                <div class="transfer-stat-card sent">
                    <div class="transfer-stat-icon">
                        <i class="fas fa-arrow-up"></i>
                    </div>
                    <div class="transfer-stat-content">
                        <div class="transfer-stat-number">{{ $teamRevisionTransferStats['transferred_from_me'] }}</div>
                        <div class="transfer-stat-label">تعديلات منقولة من الفريق</div>
                        <div class="transfer-stat-breakdown">
                            <span><i class="fas fa-wrench"></i> كمنفذ: {{ $teamRevisionTransferStats['executor_transferred_from_me'] }}</span>
                            <span><i class="fas fa-check-circle"></i> كمراجع: {{ $teamRevisionTransferStats['reviewer_transferred_from_me'] }}</span>
                        </div>
                        <div class="transfer-stat-note">
                            <i class="fas fa-info-circle"></i>
                            تعديلات تم نقلها خارج الفريق
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
                            {{ $teamRevisionTransferStats['transferred_to_me'] }}
                        </div>
                        <div class="transfer-stat-label">إجمالي التعديلات الإضافية</div>
                        <div class="transfer-stat-breakdown">
                            <span><i class="fas fa-star"></i> تعديلات لم تكن من نصيب الفريق</span>
                        </div>
                        <div class="transfer-stat-note">
                            <i class="fas fa-medal"></i>
                            دليل على تعاون أعضاء الفريق
                        </div>
                    </div>
                </div>
            </div>

            @if($teamRevisionTransferStats['transferred_to_me_details']->count() > 0)
            <div class="recent-transfers-section">
                <h3>
                    <i class="fas fa-history"></i>
                    تفاصيل التعديلات المنقولة للفريق (أحدث {{ $teamRevisionTransferStats['transferred_to_me_details']->count() }} تعديل)
                </h3>
                <div class="recent-transfers-list">
                    @foreach($teamRevisionTransferStats['transferred_to_me_details'] as $transfer)
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

        <!-- Team Members -->
        <div class="team-members-section mt-4">
            <div class="section-header">
                <h2>
                    <i class="fas fa-user-friends"></i>
                    أعضاء الفريق
                </h2>
                <span class="section-count">{{ $teamMembers->count() }}</span>
            </div>

            @if($teamMembers->count() > 0)
            <div class="members-grid">
                @foreach($teamMembers as $member)
                <div class="member-card">
                    <div class="member-header">
                        @php
                            $memberNameParts = explode(' ', trim($member->name));
                            $memberInitials = count($memberNameParts) >= 2
                                ? substr($memberNameParts[0], 0, 1) . substr($memberNameParts[1], 0, 1)
                                : substr($member->name, 0, 2);

                            // تحديد الصورة بناءً على الجنس
                            $memberAvatarImage = 'man.gif'; // الافتراضي للرجال
                            if ($member->gender === 'female' || $member->gender === 'أنثى' || $member->gender === 'انثى') {
                                $memberAvatarImage = 'women-avatar.gif';
                            }
                        @endphp
                        <img
                            src="{{ asset('avatars/' . $memberAvatarImage) }}"
                            alt="{{ $member->name }}"
                            class="member-avatar"
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                        />
                        <div class="member-avatar-fallback" style="display: none;">
                            {{ $memberInitials }}
                        </div>
                        <div class="member-info">
                            <h5>{{ $member->name }}</h5>
                            <span class="member-role">{{ $member->pivot->role ?? 'عضو' }}</span>
                        </div>
                    </div>

                    <div class="member-stats">
                        <div class="member-stat">
                            <div class="member-stat-number">{{ number_format($member->total_projects, 1) }}</div>
                            <div class="member-stat-label">نسبة المشاريع</div>
                        </div>
                        <div class="member-stat">
                            <div class="member-stat-number">{{ number_format($member->active_projects_count, 1) }}</div>
                            <div class="member-stat-label">نشطة</div>
                        </div>
                        <div class="member-stat">
                            <div class="member-stat-number">{{ $member->completed_tasks }}</div>
                            <div class="member-stat-label">مهمة مكتملة</div>
                        </div>
                        <div class="member-stat">
                            <div class="member-stat-number">{{ $member->total_tasks }}</div>
                            <div class="member-stat-label">إجمالي المهام</div>
                        </div>
                    </div>

                    <div class="member-performance">
                        <div class="performance-circle" style="--percentage: {{ $member->completion_rate }}">
                            <span class="performance-text">{{ $member->completion_rate }}%</span>
                        </div>
                        <div style="font-size: 0.9rem; color: #666; font-weight: 500;">معدل الإنجاز</div>
                    </div>

                    @if($member->last_activity)
                    <div class="last-activity">
                        <i class="fas fa-clock"></i>
                        آخر نشاط: {{ \Carbon\Carbon::parse($member->last_activity)->diffForHumans() }}
                    </div>
                    @endif

                    <div class="member-footer">
                        <a href="{{ route('employees.performance', $member->id) }}" class="view-performance">
                            <i class="fas fa-chart-line"></i>
                            عرض الأداء التفصيلي
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="empty-state">
                <i class="fas fa-users"></i>
                <p>لا يوجد أعضاء في هذا الفريق</p>
            </div>
            @endif
        </div>

        <!-- Team Error Statistics Section -->
        @if(isset($teamErrorStats) && $teamErrorStats['has_errors'])
        <div class="section-modern">
            <div class="section-header">
                <h2>
                    <i class="fas fa-exclamation-triangle"></i>
                    إحصائيات الأخطاء - {{ $team->name }}
                </h2>
            </div>

            <div class="stats-grid">
                <div class="stat-card-modern danger">
                    <div class="stat-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number">{{ $teamErrorStats['total_errors'] }}</div>
                        <div class="stat-label">إجمالي الأخطاء</div>
                        <div class="stat-trend">
                            جوهرية: {{ $teamErrorStats['critical_errors'] }} | عادية: {{ $teamErrorStats['normal_errors'] }}
                        </div>
                    </div>
                </div>

                <div class="stat-card-modern warning">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number">{{ count($teamErrorStats['top_users']) }}</div>
                        <div class="stat-label">أعضاء لديهم أخطاء</div>
                        <div class="stat-trend">
                            جودة: {{ $teamErrorStats['by_category']['quality'] }} | فنية: {{ $teamErrorStats['by_category']['technical'] }}
                        </div>
                    </div>
                </div>

                <div class="stat-card-modern info">
                    <div class="stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number">{{ $teamErrorStats['by_category']['deadline'] }}</div>
                        <div class="stat-label">أخطاء مواعيد</div>
                        <div class="stat-trend">
                            تواصل: {{ $teamErrorStats['by_category']['communication'] }} | إجرائية: {{ $teamErrorStats['by_category']['procedural'] }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- ✅ آخر النشاطات للفريق - Recent Activities Section -->
        <div class="row">
            <!-- آخر المهام -->
            <div class="col-lg-6 col-md-6 mb-4">
                <div class="section-modern">
                    <div class="section-header">
                        <h2>
                            <i class="fas fa-tasks"></i>
                            آخر المهام - {{ $team->name }}
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
                        <p>لا توجد مهام حديثة في هذا الفريق</p>
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
                            آخر المشاريع - {{ $team->name }}
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
                        <p>لا توجد مشاريع حديثة في هذا الفريق</p>
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
                            آخر الاجتماعات - {{ $team->name }}
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
                        <p>لا توجد اجتماعات حديثة في هذا الفريق</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- آخر التعديلات -->
            @if($latestRevisions->count() > 0)
            <div class="col-lg-6 col-md-6 mb-4">
                <div class="section-modern">
                    <div class="section-header">
                        <h2>
                            <i class="fas fa-history"></i>
                            آخر التعديلات - {{ $team->name }}
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

        <!-- Back Buttons -->
        <div class="text-center mt-4">
            <a href="{{ route('departments.show', urlencode($department)) }}" class="action-btn secondary mr-3">
                <i class="fas fa-arrow-right"></i>
                العودة إلى القسم
            </a>
            <a href="{{ route('company-projects.dashboard') }}" class="action-btn outline">
                <i class="fas fa-home"></i>
                لوحة التحكم الرئيسية
            </a>
        </div>
    </div>
</div>
@endsection

