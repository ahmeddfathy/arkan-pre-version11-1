@extends('layouts.app')

@section('title', 'أداء الموظف في المشروع - ' . $employee->name)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/projects-dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('css/projects-analytics.css') }}">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
@endpush

@section('content')
<div class="modern-dashboard employee-analytics">
    <!-- Header Section -->
    <div class="dashboard-header-modern">
        <div class="header-content">
            <div class="header-left">
                <div class="page-title">
                    <div class="title-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="title-text">
                        <h1>أداء الموظف في المشروع</h1>
                        <p>{{ $employee->name }} - {{ $project->name }}</p>
                        <div class="employee-meta">
                            <span class="department-badge">
                                <i class="fas fa-building"></i>
                                {{ $employee->department ?? 'قسم غير محدد' }}
                            </span>
                            <span class="project-badge">
                                <i class="fas fa-project-diagram"></i>
                                {{ $project->status }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="header-actions">
                <button class="action-btn secondary" onclick="window.location.reload()">
                    <i class="fas fa-arrows-rotate"></i>
                    تحديث
                </button>
                <a href="{{ route('projects.analytics', $project) }}" class="action-btn outline">
                    <i class="fas fa-chart-bar"></i>
                    إحصائيات المشروع
                </a>
                <a href="{{ route('projects.show', $project) }}" class="action-btn primary">
                    <i class="fas fa-eye"></i>
                    عرض المشروع
                </a>
            </div>
        </div>
    </div>

    <!-- Employee Overview Stats -->
    <div class="stats-grid">
        <div class="stat-card-modern primary">
            <div class="stat-icon">
                <i class="fas fa-tasks"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number">{{ $performanceData['task_stats']['combined']['total'] }}</div>
                <div class="stat-label">إجمالي المهام</div>
                <div class="stat-trend">
                    <i class="fas fa-layer-group"></i>
                    عادية: {{ $performanceData['task_stats']['regular']['total'] }} | قوالب: {{ $performanceData['task_stats']['template']['total'] }}
                </div>
            </div>
        </div>

        <div class="stat-card-modern success">
            <div class="stat-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number">{{ $performanceData['task_stats']['completion_rate'] }}%</div>
                <div class="stat-label">معدل الإنجاز</div>
                <div class="stat-trend positive">
                    <i class="fas fa-trophy"></i>
                    {{ $performanceData['task_stats']['combined']['completed'] }} مكتملة
                </div>
            </div>
        </div>

        <div class="stat-card-modern info">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"
                     id="employee-spent-timer"
                     data-initial-minutes="{{ (int)($performanceData['time_stats']['spent_minutes'] ?? 0) }}"
                     data-active-count="{{ (int)($performanceData['time_stats']['real_time_minutes'] ?? 0) > 0 ? 1 : 0 }}"
                     data-started-at="{{ now()->timestamp * 1000 }}">
                    @php
                        $totalMinutes = (int)($performanceData['time_stats']['spent_minutes'] ?? 0);
                        $hours = intval($totalMinutes / 60);
                        $minutes = $totalMinutes % 60;
                    @endphp
                    {{ sprintf('%d:%02d:%02d', $hours, $minutes, 0) }}
                    @if($performanceData['time_stats']['real_time_minutes'] > 0)
                        <span class="real-time-indicator" style="color: #28a745; font-size: 0.8em; margin-left: 5px;">●</span>
                    @endif
                </div>
                <div class="stat-label">الوقت المستغرق (ساعات:دقائق:ثواني)</div>
                <div class="stat-trend">
                    المقدر: {{ $performanceData['time_stats']['estimated_formatted'] }}
                    @if($performanceData['time_stats']['real_time_minutes'] > 0)
                        <br>نشط الآن: <span class="real-time-addition">{{ $performanceData['time_stats']['real_time_formatted'] }}</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="stat-card-modern {{ $performanceData['time_stats']['efficiency'] >= 100 ? 'success' : ($performanceData['time_stats']['efficiency'] >= 80 ? 'warning' : 'danger') }}">
            <div class="stat-icon">
                <i class="fas fa-tachometer-alt"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number">{{ $performanceData['time_stats']['efficiency'] }}%</div>
                <div class="stat-label">كفاءة الوقت</div>
                <div class="stat-trend">
                    @if($performanceData['time_stats']['efficiency'] >= 100)
                        <i class="fas fa-thumbs-up"></i>
                        ممتاز
                    @elseif($performanceData['time_stats']['efficiency'] >= 80)
                        <i class="fas fa-balance-scale"></i>
                        جيد
                    @else
                        <i class="fas fa-exclamation-triangle"></i>
                        يحتاج تحسين
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Task Breakdown -->
    <div class="section-modern">
        <div class="section-header">
            <h2>
                <i class="fas fa-chart-pie"></i>
                تفصيل المهام
            </h2>
        </div>

        <div class="tasks-breakdown-grid">
            <div class="task-type-card regular">
                <div class="task-type-header">
                    <div class="task-type-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="task-type-info">
                        <h3>المهام العادية</h3>
                        <p>المهام المخصصة مباشرة</p>
                    </div>
                </div>
                <div class="task-type-stats">
                    <div class="task-stat-item">
                        <span class="task-stat-number new">{{ $performanceData['task_stats']['regular']['new'] }}</span>
                        <span class="task-stat-label">جديدة</span>
                    </div>
                    <div class="task-stat-item">
                        <span class="task-stat-number in-progress">{{ $performanceData['task_stats']['regular']['in_progress'] }}</span>
                        <span class="task-stat-label">قيد التنفيذ</span>
                    </div>
                    <div class="task-stat-item">
                        <span class="task-stat-number paused">{{ $performanceData['task_stats']['regular']['paused'] }}</span>
                        <span class="task-stat-label">متوقفة</span>
                    </div>
                    <div class="task-stat-item">
                        <span class="task-stat-number completed">{{ $performanceData['task_stats']['regular']['completed'] }}</span>
                        <span class="task-stat-label">مكتملة</span>
                    </div>
                </div>
            </div>

            <div class="task-type-card template">
                <div class="task-type-header">
                    <div class="task-type-icon">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <div class="task-type-info">
                        <h3>مهام القوالب</h3>
                        <p>المهام من القوالب المحددة</p>
                    </div>
                </div>
                <div class="task-type-stats">
                    <div class="task-stat-item">
                        <span class="task-stat-number new">{{ $performanceData['task_stats']['template']['new'] }}</span>
                        <span class="task-stat-label">جديدة</span>
                    </div>
                    <div class="task-stat-item">
                        <span class="task-stat-number in-progress">{{ $performanceData['task_stats']['template']['in_progress'] }}</span>
                        <span class="task-stat-label">قيد التنفيذ</span>
                    </div>
                    <div class="task-stat-item">
                        <span class="task-stat-number paused">{{ $performanceData['task_stats']['template']['paused'] }}</span>
                        <span class="task-stat-label">متوقفة</span>
                    </div>
                    <div class="task-stat-item">
                        <span class="task-stat-number completed">{{ $performanceData['task_stats']['template']['completed'] }}</span>
                        <span class="task-stat-label">مكتملة</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Transfer Statistics Section -->
    @if(isset($performanceData['transfer_stats']) && $performanceData['transfer_stats']['has_transfers'])
    <div class="section-modern">
        <div class="section-header">
            <h2>
                <i class="fas fa-arrow-right-arrow-left"></i>
                إحصائيات نقل المهام
            </h2>
        </div>

        <div class="stats-grid">
            <div class="stat-card-modern info">
                <div class="stat-icon">
                    <i class="fas fa-arrow-right"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number">{{ $performanceData['transfer_stats']['transferred_to_me'] }}</div>
                    <div class="stat-label">مهام منقولة إليّ</div>
                    <div class="stat-trend">
                        عادية: {{ $performanceData['transfer_stats']['regular_transferred_to_me'] }} |
                        قوالب: {{ $performanceData['transfer_stats']['template_transferred_to_me'] }}
                    </div>
                </div>
            </div>

            <div class="stat-card-modern warning">
                <div class="stat-icon">
                    <i class="fas fa-arrow-left"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number">{{ $performanceData['transfer_stats']['transferred_from_me'] }}</div>
                    <div class="stat-label">مهام منقولة مني</div>
                    <div class="stat-trend">
                        عادية: {{ $performanceData['transfer_stats']['regular_transferred_from_me'] }} |
                        قوالب: {{ $performanceData['transfer_stats']['template_transferred_from_me'] }}
                    </div>
                </div>
            </div>
        </div>

        <!-- المهام المنقولة إلي -->
        @if(count($performanceData['transfer_stats']['transferred_to_details']) > 0)
        <div class="transferred-tasks-details received">
            <h3>
                <i class="fas fa-download"></i>
                تفاصيل المهام المنقولة إليّ
            </h3>
            <div class="transferred-tasks-list">
                @foreach($performanceData['transfer_stats']['transferred_to_details'] as $transfer)
                <div class="transferred-task-item received">
                    <div class="task-info">
                        <div class="task-name">
                            {{ $transfer['task_name'] }}
                            <span class="task-type-badge {{ $transfer['task_type'] }}">
                                {{ $transfer['task_type'] == 'regular' ? 'عادية' : 'قالب' }}
                            </span>
                        </div>
                        <div class="transfer-details">
                            <span class="transferred-from">
                                <i class="fas fa-user"></i>
                                منقولة من: {{ $transfer['original_user_name'] ?? 'غير محدد' }}
                            </span>
                            <span class="transfer-date">
                                <i class="fas fa-calendar"></i>
                                {{ \Carbon\Carbon::parse($transfer['transferred_at'])->format('Y/m/d H:i') }}
                            </span>
                        </div>
                        @if($transfer['transfer_reason'])
                        <div class="transfer-reason">
                            <i class="fas fa-comment"></i>
                            {{ $transfer['transfer_reason'] }}
                        </div>
                        @endif
                    </div>
                    <div class="task-status">
                        <span class="status-badge status-{{ $transfer['status'] }}">
                            {{ $transfer['status'] }}
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- المهام المنقولة مني -->
        @if(count($performanceData['transfer_stats']['transferred_from_details']) > 0)
        <div class="transferred-tasks-details transferred">
            <h3>
                <i class="fas fa-upload"></i>
                تفاصيل المهام المنقولة مني
            </h3>
            <div class="transferred-tasks-list">
                @foreach($performanceData['transfer_stats']['transferred_from_details'] as $transfer)
                <div class="transferred-task-item transferred">
                    <div class="task-info">
                        <div class="task-name">
                            {{ $transfer['task_name'] }}
                            <span class="task-type-badge {{ $transfer['task_type'] }}">
                                {{ $transfer['task_type'] == 'regular' ? 'عادية' : 'قالب' }}
                            </span>
                        </div>
                        <div class="transfer-details">
                            <span class="transferred-to">
                                <i class="fas fa-user"></i>
                                منقولة إلى: {{ $transfer['current_user_name'] ?? 'غير محدد' }}
                            </span>
                            <span class="transfer-date">
                                <i class="fas fa-calendar"></i>
                                {{ \Carbon\Carbon::parse($transfer['transferred_at'])->format('Y/m/d H:i') }}
                            </span>
                        </div>
                        @if($transfer['transfer_reason'])
                        <div class="transfer-reason">
                            <i class="fas fa-comment"></i>
                            {{ $transfer['transfer_reason'] }}
                        </div>
                        @endif
                    </div>
                    <div class="task-status">
                        <span class="status-badge status-{{ $transfer['status'] }}">
                            {{ $transfer['status'] }}
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
    @endif

    <!-- Revision Transfer Statistics Section -->
    @if(isset($employeeRevisionTransferStats) && $employeeRevisionTransferStats['has_transfers'])
    <div class="section-modern">
        <div class="section-header">
            <h2>
                <i class="fas fa-exchange-alt"></i>
                إحصائيات نقل التعديلات (مهام إضافية)
            </h2>
        </div>

        <div class="stats-grid">
            <!-- Received Revisions Card -->
            <div class="stat-card-modern success">
                <div class="stat-icon">
                    <i class="fas fa-arrow-down"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number">{{ $employeeRevisionTransferStats['transferred_to_me'] }}</div>
                    <div class="stat-label">تعديلات منقولة إليّ</div>
                    <div class="stat-trend">
                        <i class="fas fa-wrench"></i> كمنفذ: {{ $employeeRevisionTransferStats['executor_transferred_to_me'] }} |
                        <i class="fas fa-check-circle"></i> كمراجع: {{ $employeeRevisionTransferStats['reviewer_transferred_to_me'] }}
                    </div>
                </div>
            </div>

            <!-- Sent Revisions Card -->
            <div class="stat-card-modern warning">
                <div class="stat-icon">
                    <i class="fas fa-arrow-up"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number">{{ $employeeRevisionTransferStats['transferred_from_me'] }}</div>
                    <div class="stat-label">تعديلات منقولة مني</div>
                    <div class="stat-trend">
                        <i class="fas fa-wrench"></i> كمنفذ: {{ $employeeRevisionTransferStats['executor_transferred_from_me'] }} |
                        <i class="fas fa-check-circle"></i> كمراجع: {{ $employeeRevisionTransferStats['reviewer_transferred_from_me'] }}
                    </div>
                </div>
            </div>

            <!-- Additional Tasks Card -->
            <div class="stat-card-modern info">
                <div class="stat-icon">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number">{{ $employeeRevisionTransferStats['transferred_to_me'] }}</div>
                    <div class="stat-label">إجمالي التعديلات الإضافية</div>
                    <div class="stat-trend">
                        <i class="fas fa-star"></i>
                        تعديلات لم تكن من نصيبك الأصلي
                    </div>
                </div>
            </div>
        </div>

        @if($employeeRevisionTransferStats['transferred_to_me_details']->count() > 0)
        <div class="transferred-tasks-details received">
            <h3>
                <i class="fas fa-download"></i>
                تفاصيل التعديلات المنقولة إليّ
            </h3>
            <div class="transferred-tasks-list">
                @foreach($employeeRevisionTransferStats['transferred_to_me_details'] as $transfer)
                <div class="transferred-task-item received">
                    <div class="task-info">
                        <div class="task-name">
                            {{ $transfer->revision->title ?? 'تعديل محذوف' }}
                            <span class="task-type-badge {{ $transfer->assignment_type }}">
                                {{ $transfer->assignment_type == 'executor' ? 'منفذ' : 'مراجع' }}
                            </span>
                        </div>
                        <div class="transfer-details">
                            @if($transfer->fromUser)
                            <span class="transferred-from">
                                <i class="fas fa-user"></i>
                                منقولة من: {{ $transfer->fromUser->name }}
                            </span>
                            @endif
                            <span class="transfer-date">
                                <i class="fas fa-calendar"></i>
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
                    @if($transfer->revision)
                    <div class="task-status">
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
                @endforeach
            </div>
        </div>
        @endif

        @if($employeeRevisionTransferStats['transferred_from_me_details']->count() > 0)
        <div class="transferred-tasks-details transferred">
            <h3>
                <i class="fas fa-upload"></i>
                تفاصيل التعديلات المنقولة مني
            </h3>
            <div class="transferred-tasks-list">
                @foreach($employeeRevisionTransferStats['transferred_from_me_details'] as $transfer)
                <div class="transferred-task-item transferred">
                    <div class="task-info">
                        <div class="task-name">
                            {{ $transfer->revision->title ?? 'تعديل محذوف' }}
                            <span class="task-type-badge {{ $transfer->assignment_type }}">
                                {{ $transfer->assignment_type == 'executor' ? 'منفذ' : 'مراجع' }}
                            </span>
                        </div>
                        <div class="transfer-details">
                            <span class="transferred-to">
                                <i class="fas fa-user"></i>
                                منقولة إلى: {{ $transfer->toUser->name }}
                            </span>
                            <span class="transfer-date">
                                <i class="fas fa-calendar"></i>
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
                    @if($transfer->revision)
                    <div class="task-status">
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
    </div>
    @endif

    <!-- Employee Revisions -->
    @if(isset($revisionStats) && $revisionStats['total'] > 0)
    <div class="section-modern">
        <div class="section-header">
            <h2>
                <i class="fas fa-clipboard-list"></i>
                تعديلات الموظف
            </h2>
        </div>

        <div class="revisions-stats-grid">
            <div class="revision-stat-card total">
                <div class="revision-stat-icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="revision-stat-content">
                    <div class="revision-stat-number">{{ $revisionStats['total'] }}</div>
                    <div class="revision-stat-label">إجمالي التعديلات</div>
                </div>
            </div>

            <div class="revision-stat-card pending">
                <div class="revision-stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="revision-stat-content">
                    <div class="revision-stat-number">{{ $revisionStats['pending'] }}</div>
                    <div class="revision-stat-label">معلقة</div>
                </div>
            </div>

            <div class="revision-stat-card approved">
                <div class="revision-stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="revision-stat-content">
                    <div class="revision-stat-number">{{ $revisionStats['approved'] }}</div>
                    <div class="revision-stat-label">موافق عليها</div>
                </div>
            </div>

            <div class="revision-stat-card rejected">
                <div class="revision-stat-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="revision-stat-content">
                    <div class="revision-stat-number">{{ $revisionStats['rejected'] }}</div>
                    <div class="revision-stat-label">مرفوضة</div>
                </div>
            </div>

            <div class="revision-stat-card internal">
                <div class="revision-stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="revision-stat-content">
                    <div class="revision-stat-number">{{ $revisionStats['internal'] ?? 0 }}</div>
                    <div class="revision-stat-label">تعديلات داخلية</div>
                </div>
            </div>

            <div class="revision-stat-card external">
                <div class="revision-stat-icon">
                    <i class="fas fa-external-link-alt"></i>
                </div>
                <div class="revision-stat-content">
                    <div class="revision-stat-number">{{ $revisionStats['external'] ?? 0 }}</div>
                    <div class="revision-stat-label">تعديلات خارجية</div>
                </div>
            </div>
        </div>

        <!-- Revision Categories Breakdown -->
        @if(isset($revisionsByCategory))
        <div class="revision-categories-grid">
            <div class="category-card">
                <div class="category-header">
                    <i class="fas fa-tasks text-primary"></i>
                    <h4>تعديلات المهام</h4>
                </div>
                <div class="category-stats">
                    <div class="stat-item">
                        <div class="stat-number">{{ $revisionsByCategory['task_revisions']['total'] ?? 0 }}</div>
                        <div class="stat-label">الإجمالي</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number text-warning">{{ $revisionsByCategory['task_revisions']['pending'] ?? 0 }}</div>
                        <div class="stat-label">معلق</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number text-success">{{ $revisionsByCategory['task_revisions']['approved'] ?? 0 }}</div>
                        <div class="stat-label">مقبول</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number text-danger">{{ $revisionsByCategory['task_revisions']['rejected'] ?? 0 }}</div>
                        <div class="stat-label">مرفوض</div>
                    </div>
                </div>
            </div>

            <div class="category-card">
                <div class="category-header">
                    <i class="fas fa-project-diagram text-info"></i>
                    <h4>تعديلات المشروع</h4>
                </div>
                <div class="category-stats">
                    <div class="stat-item">
                        <div class="stat-number">{{ $revisionsByCategory['project_revisions']['total'] ?? 0 }}</div>
                        <div class="stat-label">الإجمالي</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number text-warning">{{ $revisionsByCategory['project_revisions']['pending'] ?? 0 }}</div>
                        <div class="stat-label">معلق</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number text-success">{{ $revisionsByCategory['project_revisions']['approved'] ?? 0 }}</div>
                        <div class="stat-label">مقبول</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number text-danger">{{ $revisionsByCategory['project_revisions']['rejected'] ?? 0 }}</div>
                        <div class="stat-label">مرفوض</div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if($urgentRevisions->count() > 0 || $latestRevisions->count() > 0)
        <div class="employee-revisions-alerts">
            @if($urgentRevisions->count() > 0)
            <div class="employee-alert-card urgent">
                <div class="employee-alert-header">
                    <i class="fas fa-fire"></i>
                    <h4>تعديلات ملحة</h4>
                    <span class="employee-alert-count">{{ $urgentRevisions->count() }}</span>
                </div>
                <div class="employee-alert-list">
                    @foreach($urgentRevisions->take(3) as $revision)
                    <div class="employee-alert-item">
                        <div class="employee-alert-info">
                            <div class="employee-alert-title">{{ Str::limit($revision->title, 35) }}</div>
                            <div class="employee-alert-meta">{{ $revision->revision_date->diffForHumans() }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            @if($latestRevisions->count() > 0)
            <div class="employee-alert-card latest">
                <div class="employee-alert-header">
                    <i class="fas fa-history"></i>
                    <h4>آخر التعديلات</h4>
                    <span class="employee-alert-count">{{ $latestRevisions->count() }}</span>
                </div>
                <div class="employee-alert-list">
                    @foreach($latestRevisions->take(3) as $revision)
                    <div class="employee-alert-item">
                        <div class="employee-alert-info">
                            <div class="employee-alert-title">{{ Str::limit($revision->title, 35) }}</div>
                            <div class="employee-alert-meta">
                                <span class="employee-status-badge status-{{ $revision->status }}">
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
            </div>
            @endif
        </div>
        @endif
    </div>
    @endif

    <!-- Overdue Tasks -->
    @if(count($performanceData['overdue_tasks']) > 0)
    <div class="section-modern alert-section">
        <div class="section-header">
            <h2>
                <i class="fas fa-exclamation-triangle"></i>
                المهام المتأخرة
            </h2>
            <span class="section-count alert">{{ count($performanceData['overdue_tasks']) }}</span>
        </div>

        <div class="overdue-tasks-grid">
            @foreach($performanceData['overdue_tasks'] as $task)
            <div class="overdue-task-card">
                <div class="overdue-header">
                    <div class="overdue-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="overdue-info">
                        <h4>{{ $task['name'] }}</h4>
                        <p>متأخر {{ $task['days_overdue'] }} يوم</p>
                    </div>
                </div>
                <div class="overdue-details">
                    <div class="overdue-date">
                        <i class="fas fa-calendar-xmark"></i>
                        الموعد النهائي: {{ \Carbon\Carbon::parse($task['due_date'])->format('Y/m/d') }}
                    </div>
                    <div class="overdue-status">
                        <span class="status-badge status-{{ $task['status'] }}">
                            {{ $task['status'] }}
                        </span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Employee Project Meetings -->
    @if(isset($employeeProjectMeetings) && $employeeProjectMeetings->count() > 0)
    <div class="section-modern">
        <div class="section-header">
            <h2>
                <i class="fas fa-users"></i>
                اجتماعات المشروع - {{ $employee->name }}
            </h2>
            <span class="section-count">{{ $employeeProjectMeetings->count() }}</span>
        </div>

        <div class="meetings-grid">
            @foreach($employeeProjectMeetings as $meeting)
            <div class="meeting-card">
                <div class="meeting-header">
                    <div class="meeting-icon">
                        <i class="fas fa-video"></i>
                    </div>
                    <div class="meeting-info">
                        <h4>{{ $meeting->title ?? 'اجتماع بدون عنوان' }}</h4>
                        <p class="meeting-description">
                            {{ $meeting->description ? Str::limit($meeting->description, 100) : 'لا يوجد وصف' }}
                        </p>
                    </div>
                </div>
                <div class="meeting-details">
                    <div class="meeting-meta">
                        <div class="meeting-date">
                            <i class="fas fa-calendar"></i>
                            <span>{{ \Carbon\Carbon::parse($meeting->created_at)->format('Y/m/d') }}</span>
                        </div>
                        <div class="meeting-time">
                            <i class="fas fa-clock"></i>
                            <span>{{ \Carbon\Carbon::parse($meeting->created_at)->format('H:i') }}</span>
                        </div>
                    </div>
                    <div class="meeting-status">
                        <span class="status-badge status-{{ $meeting->status ?? 'scheduled' }}">
                            {{ $meeting->status ?? 'مجدولة' }}
                        </span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Recent Activities -->
    <div class="section-modern">
        <div class="section-header">
            <h2>
                <i class="fas fa-history"></i>
                الأنشطة الحديثة
            </h2>
        </div>

        <div class="activities-timeline">
            @foreach($performanceData['recent_activities'] as $activity)
            <div class="activity-item">
                <div class="activity-icon {{ $activity['type'] }}">
                    <i class="fas {{ $activity['type'] === 'regular_task' ? 'fa-tasks' : 'fa-layer-group' }}"></i>
                </div>
                <div class="activity-content">
                    <div class="activity-header">
                        <span class="activity-task">{{ $activity['task_name'] }}</span>
                        <span class="activity-type">({{ $activity['type'] === 'regular_task' ? 'مهمة عادية' : 'مهمة قالب' }})</span>
                    </div>
                    <div class="activity-details">
                        <span class="activity-status status-{{ $activity['status'] }}">{{ $activity['status'] }}</span>
                        <span class="activity-time">{{ \Carbon\Carbon::parse($activity['updated_at'])->diffForHumans() }}</span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- All Tasks -->
    <div class="section-modern">
        <div class="section-header">
            <h2>
                <i class="fas fa-list"></i>
                جميع المهام
            </h2>
        </div>

        <div class="tabs-container">
            <div class="tabs-nav">
                <button class="tab-btn active" data-tab="regular-tasks">
                    <i class="fas fa-tasks"></i>
                    المهام العادية ({{ count($performanceData['all_tasks']['regular']) }})
                </button>
                <button class="tab-btn" data-tab="template-tasks">
                    <i class="fas fa-layer-group"></i>
                    مهام القوالب ({{ count($performanceData['all_tasks']['template']) }})
                </button>
            </div>

            <div class="tab-content active" id="regular-tasks">
                <div class="modern-table-container">
                    <div class="table-responsive">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>اسم المهمة</th>
                                    <th>الحالة</th>
                                    <th>الوقت المقدر</th>
                                    <th>الوقت الفعلي</th>
                                    <th>الموعد النهائي</th>
                                    <th>آخر تحديث</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($performanceData['all_tasks']['regular'] as $task)
                                <tr>
                                    <td>
                                        <div class="task-name">{{ $task->task_name }}</div>
                                    </td>
                                    <td>
                                        <span class="status-badge status-{{ $task->status }}">
                                            {{ $task->status }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="time-value">
                                            {{ $task->estimated_hours }}h {{ $task->estimated_minutes }}m
                                        </span>
                                    </td>
                                    <td>
                                        <span class="time-value">
                                            {{ $task->actual_hours }}h {{ $task->actual_minutes }}m
                                        </span>
                                    </td>
                                    <td>
                                        @if($task->due_date)
                                            <span class="date {{ \Carbon\Carbon::parse($task->due_date)->isPast() && in_array($task->status, ['new', 'in_progress']) ? 'overdue' : '' }}">
                                                {{ \Carbon\Carbon::parse($task->due_date)->format('Y/m/d') }}
                                            </span>
                                        @else
                                            <span class="date no-date">غير محدد</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="update-time">
                                            {{ \Carbon\Carbon::parse($task->updated_at)->diffForHumans() }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="empty-state">
                                        <div class="empty-icon">
                                            <i class="fas fa-tasks"></i>
                                        </div>
                                        <div class="empty-text">لا توجد مهام عادية</div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="tab-content" id="template-tasks">
                <div class="modern-table-container">
                    <div class="table-responsive">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>اسم المهمة</th>
                                    <th>الحالة</th>
                                    <th>الوقت الفعلي</th>
                                    <th>آخر تحديث</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($performanceData['all_tasks']['template'] as $task)
                                <tr>
                                    <td>
                                        <div class="task-name">{{ $task->task_name }}</div>
                                    </td>
                                    <td>
                                        <span class="status-badge status-{{ $task->status }}">
                                            {{ $task->status }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="time-value">
                                            {{ intdiv($task->actual_minutes, 60) }}h {{ $task->actual_minutes % 60 }}m
                                        </span>
                                    </td>
                                    <td>
                                        <span class="update-time">
                                            {{ \Carbon\Carbon::parse($task->updated_at)->diffForHumans() }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="empty-state">
                                        <div class="empty-icon">
                                            <i class="fas fa-layer-group"></i>
                                        </div>
                                        <div class="empty-text">لا توجد مهام قوالب</div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Tabs functionality
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');

            // Remove active class from all buttons and contents
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));

            // Add active class to clicked button and corresponding content
            this.classList.add('active');
            document.getElementById(targetTab).classList.add('active');
        });
    });

    // Real-time Timer functionality
    function startEmployeeAnalyticsTimer(el) {
        if (!el) return;
        const initialMinutes = parseInt(el.getAttribute('data-initial-minutes') || '0', 10);
        const activeCount = parseInt(el.getAttribute('data-active-count') || '0', 10);
        let totalSeconds = initialMinutes * 60;
        const tickPerSecond = activeCount > 0 ? 1 : 0;

        function format(seconds) {
            const h = Math.floor(seconds / 3600);
            const m = Math.floor((seconds % 3600) / 60);
            const s = seconds % 60;
            return `${h}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
        }

        // Update initial display
        const displayText = format(totalSeconds);
        const realTimeIndicator = el.querySelector('.real-time-indicator');
        if (realTimeIndicator) {
            el.innerHTML = displayText + el.innerHTML.substring(el.innerHTML.indexOf('<span'));
        } else {
            el.textContent = displayText;
        }

        if (tickPerSecond > 0) {
            setInterval(() => {
                totalSeconds += tickPerSecond;
                const newDisplayText = format(totalSeconds);
                if (realTimeIndicator) {
                    el.innerHTML = newDisplayText + el.innerHTML.substring(el.innerHTML.indexOf('<span'));
                } else {
                    el.textContent = newDisplayText;
                }
            }, 1000);
        }
    }

    // ✅ إضافة Page Visibility API لحل مشكلة توقف التايمر في صفحة تحليلات الموظف
    function initializeEmployeeAnalyticsPageVisibilityHandler() {
        // الكشف عن تغيير حالة الصفحة (نشطة/غير نشطة)
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                // المستخدم عاد للتاب - نحديث التايمر
                syncEmployeeAnalyticsTimerWithRealTime();
            }
        });

        // تحديث التايمر كل 10 ثوان كـ backup عندما التاب نشط
        setInterval(function() {
            if (!document.hidden) {
                syncEmployeeAnalyticsTimerWithRealTime();
            }
        }, 10000);

        // تحديث التايمر عند النقر على أي مكان في الصفحة
        document.addEventListener('click', function() {
            if (!document.hidden) {
                setTimeout(() => {
                    syncEmployeeAnalyticsTimerWithRealTime();
                }, 100);
            }
        });
    }

    function syncEmployeeAnalyticsTimerWithRealTime() {
        // ✅ تحديث تايمر الموظف بالوقت الفعلي
        const timerElement = document.getElementById('employee-spent-timer');
        if (timerElement) {
            const initialMinutes = parseInt(timerElement.getAttribute('data-initial-minutes') || '0', 10);
            const activeCount = parseInt(timerElement.getAttribute('data-active-count') || '0', 10);
            const startedAt = timerElement.getAttribute('data-started-at');

            if (activeCount > 0 && startedAt && startedAt !== 'null' && startedAt !== '') {
                // ✅ حساب الوقت الفعلي من البداية
                const startTimestamp = parseInt(startedAt);
                if (!isNaN(startTimestamp)) {
                    const now = new Date().getTime();
                    const elapsedSeconds = Math.floor((now - startTimestamp) / 1000);
                    const totalSeconds = (initialMinutes * 60) + elapsedSeconds;

                    function format(seconds) {
                        const h = Math.floor(seconds / 3600);
                        const m = Math.floor((seconds % 3600) / 60);
                        const s = seconds % 60;
                        return `${h}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
                    }

                    const newDisplayText = format(totalSeconds);
                    const realTimeIndicator = timerElement.querySelector('.real-time-indicator');
                    if (realTimeIndicator) {
                        timerElement.innerHTML = newDisplayText + timerElement.innerHTML.substring(timerElement.innerHTML.indexOf('<span'));
                    } else {
                        timerElement.textContent = newDisplayText;
                    }
                }
            }
        }
    }

    // Start timer for employee spent time
    startEmployeeAnalyticsTimer(document.getElementById('employee-spent-timer'));

    // ✅ تهيئة Page Visibility Handler
    initializeEmployeeAnalyticsPageVisibilityHandler();

    // Auto-refresh functionality
    setInterval(function() {
        if (!document.hidden) {
            console.log('Auto-refresh employee analytics');
            // You can implement AJAX refresh here if needed
        }
    }, 60000); // Refresh every minute
});
</script>

<style>
/* Additional styles for employee analytics */
.employee-meta {
    display: flex;
    align-items: center;
    gap: var(--space-4);
    margin-top: var(--space-3);
}

.project-badge {
    background: var(--arkan-primary-lighter);
    color: var(--arkan-primary-dark);
    padding: var(--space-2) var(--space-3);
    border-radius: var(--radius-md);
    font-size: 0.8rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: var(--space-2);
}

.tasks-breakdown-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: var(--space-6);
}

.task-type-card {
    background: var(--white);
    border-radius: var(--radius-xl);
    padding: var(--space-6);
    box-shadow: var(--shadow-md);
    border: 2px solid var(--gray-100);
    transition: var(--transition-normal);
}







.task-type-header {
    display: flex;
    align-items: center;
    gap: var(--space-4);
    margin-bottom: var(--space-5);
    padding-bottom: var(--space-3);
    border-bottom: 1px solid var(--gray-100);
}

.task-type-icon {
    width: 60px;
    height: 60px;
    border-radius: var(--radius-lg);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: var(--white);
    flex-shrink: 0;
}

.task-type-card.regular .task-type-icon {
    background: linear-gradient(135deg, var(--arkan-primary), var(--arkan-primary-dark));
}

.task-type-card.template .task-type-icon {
    background: linear-gradient(135deg, var(--arkan-success), #1ea568);
}

.task-type-info h3 {
    font-size: 1.3rem;
    font-weight: 800;
    color: var(--text-primary);
    margin: 0 0 var(--space-1) 0;
}

.task-type-info p {
    color: var(--text-secondary);
    margin: 0;
    font-size: 0.9rem;
}

.task-type-stats {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--space-4);
}

.task-stat-item {
    text-align: center;
    padding: var(--space-4);
    border-radius: var(--radius-lg);
    background: var(--white);
    transform: translateY(-2px);
}

.task-stat-item:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.task-stat-number {
    display: block;
    font-size: 2rem;
    font-weight: 900;
    line-height: 1;
    margin-bottom: var(--space-2);
}

.task-stat-number.new {
    color: var(--gray-600);
}

.task-stat-number.in-progress {
    color: var(--arkan-primary);
}

.task-stat-number.paused {
    color: var(--arkan-warning);
}

.task-stat-number.completed {
    color: var(--arkan-success);
}

.task-stat-label {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--text-secondary);
}

.overdue-tasks-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: var(--space-4);
}

.overdue-task-card {
    background: var(--white);
    border-radius: var(--radius-lg);
    padding: var(--space-4);
    border-left: 4px solid var(--arkan-danger);
    box-shadow: var(--shadow);
    transition: var(--transition-normal);
}



.overdue-header {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    margin-bottom: var(--space-3);
}

.overdue-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--arkan-danger), #dc2626);
    color: var(--white);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}

.overdue-info h4 {
    font-size: 1rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0 0 var(--space-1) 0;
}

.overdue-info p {
    font-size: 0.85rem;
    color: var(--arkan-danger);
    margin: 0;
    font-weight: 600;
}

.overdue-details {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: var(--space-3);
}

.overdue-date {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    font-size: 0.8rem;
    color: var(--text-secondary);
}

.tabs-container {
    border-radius: var(--radius-lg);
    overflow: hidden;
    border: 1px solid var(--gray-200);
}

.tabs-nav {
    display: flex;
    background: var(--gray-50);
    border-bottom: 1px solid var(--gray-200);
}

.tab-btn {
    flex: 1;
    padding: var(--space-4);
    background: none;
    border: none;
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--text-secondary);
    cursor: pointer;
    transition: var(--transition-fast);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-2);
}



.tab-btn.active {
    background: var(--arkan-primary);
    color: var(--white);
}

.tab-content {
    display: none;
    padding: var(--space-4);
}

.tab-content.active {
    display: block;
}

.task-name {
    font-weight: 600;
    color: var(--text-primary);
}

.time-value {
    font-family: 'Courier New', monospace;
    font-weight: 600;
    color: var(--text-secondary);
}

.update-time {
    font-size: 0.85rem;
    color: var(--text-muted);
}

@media (max-width: 768px) {
    .tasks-breakdown-grid {
        grid-template-columns: 1fr;
    }

    .task-type-stats {
        grid-template-columns: 1fr;
        gap: var(--space-2);
    }

    .overdue-tasks-grid {
        grid-template-columns: 1fr;
    }

    .tabs-nav {
        flex-direction: column;
    }

    .employee-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: var(--space-2);
    }
}

/* Real-time addition styles */
.real-time-addition {
    color: var(--arkan-success);
    font-weight: 700;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.7; }
    100% { opacity: 1; }
}

/* ✅ Transfer Statistics Styles */
.transferred-tasks-details {
    margin-top: 2rem;
    background: var(--gray-50);
    border-radius: var(--radius-lg);
    padding: var(--space-6);
    position: relative;
}

.transferred-tasks-details.received {
    background: linear-gradient(135deg, rgba(34, 197, 94, 0.05), rgba(34, 197, 94, 0.1));
    border-left: 4px solid #22c55e;
}

.transferred-tasks-details.transferred {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.05), rgba(239, 68, 68, 0.1));
    border-left: 4px solid #ef4444;
}

.transferred-tasks-details h3 {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: var(--space-4);
    display: flex;
    align-items: center;
    gap: var(--space-3);
}

.transferred-tasks-list {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
}

.transferred-task-item {
    background: var(--white);
    border-radius: var(--radius-lg);
    padding: var(--space-4);
    border-left: 4px solid var(--arkan-warning);
    box-shadow: var(--shadow);
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    transition: var(--transition-normal);
}

.transferred-task-item.received {
    border-left: 4px solid #22c55e;
    background: linear-gradient(135deg, rgba(34, 197, 94, 0.02), rgba(34, 197, 94, 0.05));
}

.transferred-task-item.transferred {
    border-left: 4px solid #ef4444;
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.02), rgba(239, 68, 68, 0.05));
}

.transferred-task-item:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.task-info {
    flex: 1;
}

.task-name {
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: var(--space-2);
    display: flex;
    align-items: center;
    gap: var(--space-2);
}

.task-type-badge {
    padding: var(--space-1) var(--space-2);
    border-radius: var(--radius-md);
    font-size: 0.7rem;
    font-weight: 600;
}

.task-type-badge.regular {
    background: linear-gradient(135deg, var(--arkan-primary-lighter), rgba(75, 170, 212, 0.2));
    color: var(--arkan-primary-dark);
}

.task-type-badge.template {
    background: linear-gradient(135deg, rgba(139, 92, 246, 0.1), rgba(139, 92, 246, 0.2));
    color: #7c3aed;
}

.transfer-details {
    display: flex;
    flex-direction: column;
    gap: var(--space-1);
    margin-bottom: var(--space-2);
}

.transferred-to,
.transferred-from,
.transfer-date {
    font-size: 0.85rem;
    color: var(--text-secondary);
    display: flex;
    align-items: center;
    gap: var(--space-2);
}

.transferred-to i,
.transferred-from i,
.transfer-date i {
    width: 12px;
    color: var(--arkan-primary);
}

.transferred-task-item.received .transferred-from i {
    color: #22c55e;
}

.transferred-task-item.transferred .transferred-to i {
    color: #ef4444;
}

.transfer-reason {
    font-size: 0.8rem;
    color: var(--text-muted);
    font-style: italic;
    background: var(--gray-100);
    padding: var(--space-2);
    border-radius: var(--radius-md);
    display: flex;
    align-items: flex-start;
    gap: var(--space-2);
}

.transfer-reason i {
    margin-top: 2px;
    color: var(--arkan-info);
}

.task-status {
    flex-shrink: 0;
}

/* Employee Revisions Styles */
.revisions-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.revision-stat-card {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    border: 1px solid #e3e6ef;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.revision-stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    bottom: 0;
    width: 4px;
}

.revision-stat-card.total::before { background: linear-gradient(180deg, #4BAAD4, #5bc0de); }
.revision-stat-card.pending::before { background: linear-gradient(180deg, #ffad46, #ffc107); }
.revision-stat-card.approved::before { background: linear-gradient(180deg, #23c277, #28a745); }
.revision-stat-card.rejected::before { background: linear-gradient(180deg, #e74c3c, #dc3545); }
.revision-stat-card.internal::before { background: linear-gradient(180deg, #17a2b8, #138496); }
.revision-stat-card.external::before { background: linear-gradient(180deg, #fd7e14, #e76f00); }

.revision-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.revision-stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: white;
    flex-shrink: 0;
}

.revision-stat-card.total .revision-stat-icon { background: linear-gradient(135deg, #4BAAD4, #5bc0de); }
.revision-stat-card.pending .revision-stat-icon { background: linear-gradient(135deg, #ffad46, #ffc107); }
.revision-stat-card.approved .revision-stat-icon { background: linear-gradient(135deg, #23c277, #28a745); }
.revision-stat-card.rejected .revision-stat-icon { background: linear-gradient(135deg, #e74c3c, #dc3545); }
.revision-stat-card.internal .revision-stat-icon { background: linear-gradient(135deg, #17a2b8, #138496); }
.revision-stat-card.external .revision-stat-icon { background: linear-gradient(135deg, #fd7e14, #e76f00); }

.revision-stat-content {
    flex: 1;
}

.revision-stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 0.25rem;
    line-height: 1;
}

.revision-stat-label {
    font-size: 0.9rem;
    color: #6c757d;
    font-weight: 500;
}

.employee-revisions-alerts {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 1.5rem;
}

.employee-alert-card {
    background: white;
    border-radius: 16px;
    border: 1px solid #e3e6ef;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.employee-alert-card.urgent {
    border-left: 4px solid #e74c3c;
}

.employee-alert-card.latest {
    border-left: 4px solid #4BAAD4;
}

.employee-alert-header {
    padding: 1rem 1.5rem;
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    display: flex;
    align-items: center;
    gap: 0.75rem;
    border-bottom: 1px solid #e9ecef;
}

.employee-alert-header i {
    font-size: 1.1rem;
    color: #495057;
}

.employee-alert-header h4 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    color: #2c3e50;
    flex: 1;
}

.employee-alert-count {
    background: #495057;
    color: white;
    border-radius: 20px;
    padding: 0.25rem 0.75rem;
    font-size: 0.8rem;
    font-weight: 600;
}

.employee-alert-list {
    padding: 1rem;
    max-height: 200px;
    overflow-y: auto;
}

.employee-alert-item {
    padding: 0.75rem 0;
    border-bottom: 1px solid #f8f9fa;
}

.employee-alert-item:last-child {
    border-bottom: none;
}

.employee-alert-title {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.25rem;
    font-size: 0.9rem;
}

.employee-alert-meta {
    font-size: 0.8rem;
    color: #6c757d;
}

.employee-status-badge {
    padding: 0.2rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
}

.employee-status-badge.status-approved { background: #d4edda; color: #155724; }
.employee-status-badge.status-rejected { background: #f8d7da; color: #721c24; }
.employee-status-badge.status-pending { background: #fff3cd; color: #856404; }

@media (max-width: 768px) {
    .revisions-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .employee-revisions-alerts {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 576px) {
    .revisions-stats-grid {
        grid-template-columns: 1fr;
    }
}

/* Revision Categories Styles */
.revision-categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.category-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    overflow: hidden;
    transition: all 0.3s ease;
}

.category-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.category-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 1rem;
    text-align: center;
    border-bottom: 2px solid #dee2e6;
}

.category-header i {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
    display: block;
}

.category-header h4 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: #495057;
}

.category-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    padding: 0.5rem;
}

.stat-item {
    text-align: center;
    padding: 0.75rem 0.5rem;
    border-right: 1px solid #eee;
}

.stat-item:last-child {
    border-right: none;
}

.stat-number {
    font-size: 1.5rem;
    font-weight: bold;
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.85rem;
    color: #6c757d;
    font-weight: 500;
}

@media (max-width: 576px) {
    .revision-categories-grid {
        grid-template-columns: 1fr;
    }

    .category-stats {
        grid-template-columns: repeat(2, 1fr);
    }
}


/* ✅ Employee Project Meetings Styles */
.meetings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 1.5rem;
}

.meeting-card {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    border: 1px solid #e3e6ef;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.meeting-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    bottom: 0;
    width: 4px;
    background: linear-gradient(180deg, #4BAAD4, #5bc0de);
}

.meeting-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.meeting-header {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1rem;
}

.meeting-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: linear-gradient(135deg, #4BAAD4, #5bc0de);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.meeting-info {
    flex: 1;
}

.meeting-info h4 {
    font-size: 1.1rem;
    font-weight: 700;
    color: #2d3748;
    margin: 0 0 0.5rem 0;
    line-height: 1.3;
}

.meeting-description {
    font-size: 0.9rem;
    color: #6b7280;
    margin: 0;
    line-height: 1.4;
}

.meeting-details {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #f1f5f9;
}

.meeting-meta {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.meeting-date,
.meeting-time {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.85rem;
    color: #6b7280;
}

.meeting-date i,
.meeting-time i {
    font-size: 0.8rem;
    color: #4BAAD4;
}

.meeting-status {
    flex-shrink: 0;
}

.status-badge.status-scheduled {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
}

.status-badge.status-completed {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.status-badge.status-cancelled {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
}

.status-badge.status-in-progress {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
}

@media (max-width: 768px) {
    .meetings-grid {
        grid-template-columns: 1fr;
    }

    .meeting-details {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }

    .meeting-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
}
</style>
@endpush
