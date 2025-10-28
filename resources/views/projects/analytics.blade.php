@extends('layouts.app')

@section('title', 'إحصائيات المشروع - ' . $project->name)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/projects-dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('css/projects-analytics.css') }}">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
/* Time Statistics Section Styles */
.time-overview-section {
    margin: 2rem 0;
}

.section-header-modern {
    margin-bottom: 2rem;
    text-align: center;
}

.section-header-modern h2 {
    font-size: 2rem;
    font-weight: 700;
    color: #2d3748;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
}

.section-header-modern p {
    color: #718096;
    font-size: 1.1rem;
}

.stats-grid.time-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    margin-bottom: 2rem;
}

.stat-card-modern.estimated::before {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
}

.stat-card-modern.flexible::before {
    background: linear-gradient(135deg, #10b981, #059669);
}

.stat-card-modern.total::before {
    background: linear-gradient(135deg, #8b5cf6, #7c3aed);
}

.stat-card-modern.estimated .stat-icon {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
}

.stat-card-modern.flexible .stat-icon {
    background: linear-gradient(135deg, #10b981, #059669);
}

.stat-card-modern.total .stat-icon {
    background: linear-gradient(135deg, #8b5cf6, #7c3aed);
}

.stat-card-modern.warning::before {
    background: linear-gradient(135deg, #f59e0b, #d97706);
}

.stat-card-modern.warning .stat-icon {
    background: linear-gradient(135deg, #f59e0b, #d97706);
}

/* Time Analytics Flexible Styles */
.time-stat.flexible {
    border-left: 3px solid #10b981;
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.05), rgba(16, 185, 129, 0.1));
}

.time-value.flexible-time {
    color: #059669;
    font-weight: 700;
}

.time-note {
    font-size: 0.8rem;
    color: #6b7280;
    font-style: italic;
    margin-top: 0.25rem;
}

/* Real-time addition styles */
.real-time-addition {
    color: #059669;
    font-weight: 700;
    animation: pulse-real-time 2s infinite;
}

@keyframes pulse-real-time {
    0% { opacity: 1; }
    50% { opacity: 0.7; }
    100% { opacity: 1; }
}

/* Real-time stat styling */
.time-stat.real-time {
    border-left: 3px solid #059669;
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.05), rgba(16, 185, 129, 0.1));
}

/* ✅ Transfer Statistics Styling */
.transfer-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    align-items: center;
}

.transfer-stat {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 600;
}

.transfer-stat.transferred-from {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(220, 38, 38, 0.15));
    color: #dc2626;
    border: 1px solid rgba(239, 68, 68, 0.2);
}

.transfer-stat.transferred-to {
    background: linear-gradient(135deg, rgba(34, 197, 94, 0.1), rgba(22, 163, 74, 0.15));
    color: #16a34a;
    border: 1px solid rgba(34, 197, 94, 0.2);
}

.transfer-stat i {
    font-size: 0.6rem;
    opacity: 0.8;
}

.no-transfers {
    color: #9ca3af;
    font-size: 0.8rem;
    font-style: italic;
}

/* ✅ Detailed Transfer Statistics Styling */
.transfer-info-detailed {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    min-width: 140px;
}

.transfer-row {
    display: flex;
    align-items: center;
    justify-content: flex-start;
}

.transfer-stat-detailed {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.375rem 0.5rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 500;
    width: 100%;
    transition: all 0.2s ease;
}

.transfer-stat-detailed:hover {
    transform: translateX(2px);
}

.transfer-stat-detailed.transferred-from {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.08), rgba(220, 38, 38, 0.12));
    color: #dc2626;
    border-left: 3px solid #ef4444;
}

.transfer-stat-detailed.transferred-to {
    background: linear-gradient(135deg, rgba(34, 197, 94, 0.08), rgba(22, 163, 74, 0.12));
    color: #16a34a;
    border-left: 3px solid #22c55e;
}

.transfer-stat-detailed i {
    font-size: 0.7rem;
    opacity: 0.8;
    width: 12px;
    text-align: center;
}

.transfer-label {
    font-size: 0.7rem;
    opacity: 0.8;
    flex: 1;
}

.transfer-number {
    font-weight: 700;
    font-size: 0.8rem;
    min-width: 20px;
    text-align: center;
    background: rgba(255, 255, 255, 0.7);
    padding: 0.125rem 0.375rem;
    border-radius: 4px;
}

.transfer-total {
    text-align: center;
    margin-top: 0.25rem;
    padding-top: 0.25rem;
    border-top: 1px solid rgba(0, 0, 0, 0.1);
}

.transfer-total small {
    font-size: 0.65rem;
    color: #6b7280;
    font-weight: 500;
}
</style>
@endpush

@section('content')
<div class="modern-dashboard project-analytics">
    <!-- Header Section -->
    <div class="dashboard-header-modern">
        <div class="header-content">
            <div class="header-left">
                <div class="page-title">
                    <div class="title-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="title-text">
                        <h1>إحصائيات المشروع</h1>
                        <p>{{ $project->name }}</p>
                        <div class="project-meta">
                            <span class="status-badge status-{{ str_replace(' ', '-', $project->status) }}">
                                {{ $project->status }}
                            </span>
                            @if($project->client)
                            <span class="client-info">
                                <i class="fas fa-user"></i>
                                {{ $project->client->name }}
                            </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="header-actions">
                <button class="action-btn secondary" onclick="window.location.reload()">
                    <i class="fas fa-sync-alt"></i>
                    تحديث
                </button>
                <a href="{{ route('projects.show', $project) }}" class="action-btn outline">
                    <i class="fas fa-eye"></i>
                    عرض المشروع
                </a>
                <a href="{{ route('projects.index') }}" class="action-btn primary">
                    <i class="fas fa-list"></i>
                    قائمة المشاريع
                </a>
            </div>
        </div>
    </div>

    <!-- Project Overview Stats -->
    <div class="stats-grid">
        <div class="stat-card-modern primary">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number">{{ $project_overview['total_participants'] }}</div>
                <div class="stat-label">إجمالي الأعضاء</div>
                <div class="stat-trend positive">
                    <i class="fas fa-user-check"></i>
                    {{ $project_overview['active_participants'] }} نشط
                </div>
            </div>
        </div>

        <div class="stat-card-modern success">
            <div class="stat-icon">
                <i class="fas fa-tasks"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number">{{ $project_overview['completion_percentage'] }}%</div>
                <div class="stat-label">نسبة الإنجاز</div>
                <div class="stat-trend">
                    <i class="fas fa-chart-line"></i>
                    معدل التقدم
                </div>
            </div>
        </div>

        <div class="stat-card-modern info">
            <div class="stat-icon">
                <i class="fas fa-cogs"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number">{{ $service_analytics['completion_rate'] }}%</div>
                <div class="stat-label">إنجاز الخدمات</div>
                <div class="stat-trend">
                    {{ $service_analytics['services']->where('status', 'مكتملة')->count() }} من {{ $service_analytics['services']->count() }}
                </div>
            </div>
        </div>

        <div class="stat-card-modern {{ $project_overview['is_overdue'] ? 'warning' : 'info' }}">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                @if($project_overview['days_remaining'] !== null)
                    @if($project_overview['days_remaining'] > 0)
                        <div class="stat-number">{{ $project_overview['days_remaining'] }}</div>
                        <div class="stat-label">يوم متبقي</div>
                    @elseif($project_overview['days_remaining'] < 0)
                        <div class="stat-number">{{ abs($project_overview['days_remaining']) }}</div>
                        <div class="stat-label">يوم متأخر</div>
                    @else
                        <div class="stat-number">اليوم</div>
                        <div class="stat-label">الموعد النهائي</div>
                    @endif
                @else
                    <div class="stat-number">∞</div>
                    <div class="stat-label">بدون موعد نهائي</div>
                @endif
                <div class="stat-trend">
                    @if($project_overview['time_efficiency'] > 0)
                        <i class="fas fa-tachometer-alt"></i>
                        كفاءة {{ $project_overview['time_efficiency'] }}%
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Time Statistics Section -->
    <div class="time-overview-section">
        <div class="section-header-modern">
            <h2>
                <i class="fas fa-clock"></i>
                إحصائيات الوقت
            </h2>
            <p>نظرة عامة على الأوقات المختلفة للمشروع</p>
        </div>

        <div class="stats-grid time-stats">
            <div class="stat-card-modern estimated">
                <div class="stat-icon">
                    <i class="fas fa-hourglass-start"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number">
                        @if(isset($project_overview['total_time_estimated']) && $project_overview['total_time_estimated'] > 0)
                            @php
                                $hours = intval($project_overview['total_time_estimated'] / 60);
                                $minutes = $project_overview['total_time_estimated'] % 60;
                                echo ($hours > 0 ? $hours . 'h ' : '') . ($minutes > 0 ? $minutes . 'm' : ($hours == 0 ? '0h' : ''));
                            @endphp
                        @else
                            0h
                        @endif
                    </div>
                    <div class="stat-label">الوقت المقدر</div>
                    <div class="stat-trend">
                        <i class="fas fa-calendar-alt"></i>
                        للمهام المحددة الوقت
                    </div>
                </div>
            </div>

            <div class="stat-card-modern flexible">
                <div class="stat-icon">
                    <i class="fas fa-expand-arrows-alt"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number">{{ $project_overview['total_flexible_time_spent_formatted'] ?? '0h' }}</div>
                    <div class="stat-label">الوقت المرن</div>
                    <div class="stat-trend">
                        <i class="fas fa-infinity"></i>
                        المهام بدون وقت محدد
                    </div>
                </div>
            </div>

            <div class="stat-card-modern total">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"
                         id="project-total-timer"
                         data-initial-minutes="{{ (int)($project_overview['total_time_spent'] ?? 0) }}"
                         data-active-count="{{ (int)($project_overview['total_real_time'] ?? 0) > 0 ? 1 : 0 }}"
                         data-started-at="{{ now()->timestamp * 1000 }}">
                        @if(isset($project_overview['total_time_spent']) && $project_overview['total_time_spent'] > 0)
                            @php
                                $totalMinutes = (int)($project_overview['total_time_spent'] ?? 0);
                                $hours = intval($totalMinutes / 60);
                                $minutes = $totalMinutes % 60;
                            @endphp
                            {{ sprintf('%d:%02d:%02d', $hours, $minutes, 0) }}
                        @else
                            0:00:00
                        @endif
                        @if(isset($project_overview['total_real_time']) && $project_overview['total_real_time'] > 0)
                            <span class="real-time-indicator" style="color: #28a745; font-size: 0.8em; margin-left: 5px;">●</span>
                        @endif
                    </div>
                    <div class="stat-label">إجمالي الوقت (ساعات:دقائق:ثواني)</div>
                    <div class="stat-trend">
                        <i class="fas fa-check-circle"></i>
                        الوقت الفعلي المنجز
                        @if(isset($project_overview['total_real_time']) && $project_overview['total_real_time'] > 0)
                            <br><span class="real-time-addition">نشط: {{ $project_overview['total_real_time_formatted'] }}</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="charts-section">
        <div class="chart-card">
            <div class="chart-header">
                <h3>
                    <i class="fas fa-chart-pie"></i>
                    توزيع حالات المهام
                </h3>
            </div>
            <div class="chart-container">
                <canvas id="taskStatusChart"></canvas>
            </div>
        </div>

        <div class="chart-card">
            <div class="chart-header">
                <h3>
                    <i class="fas fa-chart-bar"></i>
                    حالة الخدمات
                </h3>
            </div>
            <div class="chart-container">
                <canvas id="serviceStatusChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Department Performance -->
    @if(count($team_statistics) > 0)
    <div class="section-modern">
        <div class="section-header">
            <h2>
                <i class="fas fa-users-cog"></i>
                أداء الأقسام
            </h2>
            <span class="section-count">
                <i class="fas fa-building"></i>
                {{ count($team_statistics) }}
            </span>
        </div>

        <div class="departments-grid">
            @foreach($team_statistics as $department => $stats)
            <div class="department-card-modern">
                <div class="department-header">
                    <div class="department-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="department-info">
                        <h4 class="department-name">{{ $department }}</h4>
                        <p class="department-members">{{ $stats['members_count'] }} عضو</p>
                    </div>
                </div>

                <div class="department-stats">
                    <div class="stat-item">
                        <div class="stat-icon tasks">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-number">{{ $stats['total_tasks'] }}</span>
                            <span class="stat-label">إجمالي المهام</span>
                        </div>
                    </div>

                    <div class="stat-item">
                        <div class="stat-icon completed">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-number">{{ $stats['completed_tasks'] }}</span>
                            <span class="stat-label">مهام مكتملة</span>
                        </div>
                    </div>

                    <div class="stat-item">
                        <div class="stat-icon rate">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-number">{{ $stats['completion_rate'] }}%</span>
                            <span class="stat-label">معدل الإنجاز</span>
                        </div>
                    </div>
                </div>


            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Project Revisions -->
    @if(isset($revisionStats) && $revisionStats['total'] > 0)
    <div class="section-modern">
        <div class="section-header">
            <h2>
                <i class="fas fa-clipboard-list"></i>
                تعديلات المشروع
            </h2>
        </div>

        <div class="revisions-overview-grid">
            <div class="revision-summary-card">
                <div class="revision-icon all">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="revision-info">
                    <div class="revision-number">{{ $revisionStats['total'] }}</div>
                    <div class="revision-label">إجمالي التعديلات</div>
                </div>
            </div>

            <div class="revision-summary-card">
                <div class="revision-icon pending">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="revision-info">
                    <div class="revision-number">{{ $revisionStats['pending'] }}</div>
                    <div class="revision-label">معلقة</div>
                </div>
            </div>

            <div class="revision-summary-card">
                <div class="revision-icon approved">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="revision-info">
                    <div class="revision-number">{{ $revisionStats['approved'] }}</div>
                    <div class="revision-label">موافق عليها</div>
                </div>
            </div>

            <div class="revision-summary-card">
                <div class="revision-icon rejected">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="revision-info">
                    <div class="revision-number">{{ $revisionStats['rejected'] }}</div>
                    <div class="revision-label">مرفوضة</div>
                </div>
            </div>

            <div class="revision-summary-card">
                <div class="revision-icon internal">
                    <i class="fas fa-users"></i>
                </div>
                <div class="revision-info">
                    <div class="revision-number">{{ $revisionStats['internal'] ?? 0 }}</div>
                    <div class="revision-label">تعديلات داخلية</div>
                </div>
            </div>

            <div class="revision-summary-card">
                <div class="revision-icon external">
                    <i class="fas fa-external-link-alt"></i>
                </div>
                <div class="revision-info">
                    <div class="revision-number">{{ $revisionStats['external'] ?? 0 }}</div>
                    <div class="revision-label">تعديلات خارجية</div>
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
        <div class="revision-alerts-grid">
            @if($urgentRevisions->count() > 0)
            <div class="alert-card urgent">
                <div class="alert-header">
                    <i class="fas fa-fire"></i>
                    <h4>تعديلات ملحة</h4>
                    <span class="count">{{ $urgentRevisions->count() }}</span>
                </div>
                <div class="alert-list">
                    @foreach($urgentRevisions->take(3) as $revision)
                    <div class="alert-item">
                        <div class="item-icon">
                            @if($revision->revision_type == 'project')
                                <i class="fas fa-project-diagram text-primary"></i>
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
                    @foreach($latestRevisions->take(3) as $revision)
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
            </div>
            @endif
        </div>
        @endif
    </div>
    @endif

    <!-- Task Transfer Statistics Section -->
    @if(isset($transfer_statistics) && $transfer_statistics['has_transfers'])
    <div class="section-modern">
        <div class="section-header">
            <h2>
                <i class="fas fa-exchange-alt"></i>
                إحصائيات نقل المهام
            </h2>
            <span class="section-count">{{ $transfer_statistics['total_transfers'] }}</span>
        </div>

        <!-- Transfer Overview Cards -->
        <div class="stats-grid transfer-stats-grid">
            <div class="stat-card-modern info">
                <div class="stat-icon">
                    <i class="fas fa-arrow-right-arrow-left"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number">{{ $transfer_statistics['total_transfers'] }}</div>
                    <div class="stat-label">إجمالي النقل</div>
                    <div class="stat-trend">
                        عادية: {{ $transfer_statistics['regular_transfers'] }} |
                        قوالب: {{ $transfer_statistics['template_transfers'] }}
                    </div>
                </div>
            </div>

            <div class="stat-card-modern success">
                <div class="stat-icon">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number">{{ $transfer_statistics['additional_tasks'] }}</div>
                    <div class="stat-label">مهام إضافية</div>
                    <div class="stat-trend">
                        منقولة إلى أعضاء المشروع
                    </div>
                </div>
            </div>

            <div class="stat-card-modern primary">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number">{{ count($transfer_statistics['top_transfer_users']) }}</div>
                    <div class="stat-label">أعضاء نشطين</div>
                    <div class="stat-trend">
                        في عمليات النقل
                    </div>
                </div>
            </div>

            <div class="stat-card-modern warning">
                <div class="stat-icon">
                    <i class="fas fa-list"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number">{{ count($transfer_statistics['all_transfers']) }}</div>
                    <div class="stat-label">عمليات النقل المعروضة</div>
                    <div class="stat-trend">
                        جميع المنقولات في المشروع
                    </div>
                </div>
            </div>
        </div>

        <!-- All Project Transfers -->
        @if(count($transfer_statistics['all_transfers']) > 0)
        <div class="transfer-section">
            <h3>
                <i class="fas fa-exchange-alt"></i>
                جميع عمليات النقل في المشروع
            </h3>
            <div class="transfer-timeline">
                @foreach($transfer_statistics['all_transfers'] as $transfer)
                <div class="transfer-item">
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
                                من: <strong>{{ $transfer->from_user_name ?? 'غير محدد' }}</strong>
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

        <!-- Top Transfer Users -->
        @if(count($transfer_statistics['top_transfer_users']) > 0)
        <div class="transfer-users-section">
            <h3>
                <i class="fas fa-trophy"></i>
                الأعضاء الأكثر استلاماً للمهام
            </h3>
            <div class="transfer-users-grid">
                @foreach($transfer_statistics['top_transfer_users'] as $index => $user)
                <div class="transfer-user-card">
                    <div class="user-rank">
                        <span class="rank-number">{{ $index + 1 }}</span>
                        @if($index == 0)
                            <i class="fas fa-crown text-warning"></i>
                        @elseif($index == 1)
                            <i class="fas fa-medal text-secondary"></i>
                        @elseif($index == 2)
                            <i class="fas fa-award text-warning"></i>
                        @endif
                    </div>
                    <div class="user-avatar">
                        @if($user['avatar'])
                            <img src="{{ asset('storage/' . $user['avatar']) }}" alt="{{ $user['name'] }}"
                                 onerror="this.src='{{ asset('avatars/man.gif') }}'">
                        @else
                            <img src="{{ asset('avatars/man.gif') }}" alt="{{ $user['name'] }}">
                        @endif
                    </div>
                    <div class="user-info">
                        <div class="user-name">{{ $user['name'] }}</div>
                        <div class="user-department">{{ $user['department'] }}</div>
                    </div>
                    <div class="user-stats">
                        <div class="stat-item">
                            <span class="stat-number">{{ $user['total_received'] }}</span>
                            <span class="stat-label">إجمالي</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">{{ $user['received_regular'] }}</span>
                            <span class="stat-label">عادية</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">{{ $user['received_template'] }}</span>
                            <span class="stat-label">قوالب</span>
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
    @if(isset($projectRevisionTransferStats) && $projectRevisionTransferStats['has_transfers'])
    <div class="section-modern">
        <div class="section-header">
            <h2>
                <i class="fas fa-exchange-alt"></i>
                إحصائيات نقل التعديلات (مهام إضافية) - {{ $project->name }}
            </h2>
        </div>

        <div class="stats-grid">
            <!-- Received Revisions Card -->
            <div class="stat-card-modern success">
                <div class="stat-icon">
                    <i class="fas fa-arrow-down"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number">{{ $projectRevisionTransferStats['transferred_to_me'] }}</div>
                    <div class="stat-label">تعديلات منقولة للمشروع</div>
                    <div class="stat-trend">
                        <i class="fas fa-wrench"></i> كمنفذ: {{ $projectRevisionTransferStats['executor_transferred_to_me'] }} |
                        <i class="fas fa-check-circle"></i> كمراجع: {{ $projectRevisionTransferStats['reviewer_transferred_to_me'] }}
                    </div>
                </div>
            </div>

            <!-- Sent Revisions Card -->
            <div class="stat-card-modern warning">
                <div class="stat-icon">
                    <i class="fas fa-arrow-up"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number">{{ $projectRevisionTransferStats['transferred_from_me'] }}</div>
                    <div class="stat-label">تعديلات منقولة من المشروع</div>
                    <div class="stat-trend">
                        <i class="fas fa-wrench"></i> كمنفذ: {{ $projectRevisionTransferStats['executor_transferred_from_me'] }} |
                        <i class="fas fa-check-circle"></i> كمراجع: {{ $projectRevisionTransferStats['reviewer_transferred_from_me'] }}
                    </div>
                </div>
            </div>

            <!-- Additional Tasks Card -->
            <div class="stat-card-modern info">
                <div class="stat-icon">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number">{{ $projectRevisionTransferStats['transferred_to_me'] }}</div>
                    <div class="stat-label">إجمالي التعديلات الإضافية</div>
                    <div class="stat-trend">
                        <i class="fas fa-star"></i>
                        تعديلات إضافية للمشروع
                    </div>
                </div>
            </div>
        </div>

        @if($projectRevisionTransferStats['transferred_to_me_details']->count() > 0)
        <div class="transfer-section">
            <h3>
                <i class="fas fa-download"></i>
                تفاصيل التعديلات المنقولة للمشروع (أحدث {{ $projectRevisionTransferStats['transferred_to_me_details']->count() }} تعديل)
            </h3>
            <div class="transfer-timeline">
                @foreach($projectRevisionTransferStats['transferred_to_me_details'] as $transfer)
                <div class="transfer-item">
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
                            <span class="transfer-users">
                                <i class="fas fa-arrow-left mx-2"></i>
                                إلى: <strong>{{ $transfer->toUser->name }}</strong>
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

        @if($projectRevisionTransferStats['transferred_from_me_details']->count() > 0)
        <div class="transfer-section">
            <h3>
                <i class="fas fa-upload"></i>
                تفاصيل التعديلات المنقولة من المشروع (أحدث {{ $projectRevisionTransferStats['transferred_from_me_details']->count() }} تعديل)
            </h3>
            <div class="transfer-timeline">
                @foreach($projectRevisionTransferStats['transferred_from_me_details'] as $transfer)
                <div class="transfer-item">
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
                                منقولة من: <strong>{{ $transfer->fromUser ? $transfer->fromUser->name : 'غير محدد' }}</strong>
                            </span>
                            <span class="transfer-users">
                                <i class="fas fa-arrow-left mx-2"></i>
                                إلى: <strong>{{ $transfer->toUser->name }}</strong>
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

    <!-- Project Error Statistics Section -->
    @if(isset($projectErrorStats) && $projectErrorStats['has_errors'])
    <div class="section-modern">
        <div class="section-header">
            <h2>
                <i class="fas fa-exclamation-triangle"></i>
                إحصائيات الأخطاء - {{ $project->name }}
            </h2>
        </div>

        <div class="stats-grid">
            <div class="stat-card-modern danger">
                <div class="stat-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number">{{ $projectErrorStats['total_errors'] }}</div>
                    <div class="stat-label">إجمالي الأخطاء</div>
                    <div class="stat-trend">
                        جوهرية: {{ $projectErrorStats['critical_errors'] }} | عادية: {{ $projectErrorStats['normal_errors'] }}
                    </div>
                </div>
            </div>

            <div class="stat-card-modern warning">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number">{{ count($projectErrorStats['top_users']) }}</div>
                    <div class="stat-label">موظفين لديهم أخطاء</div>
                    <div class="stat-trend">
                        جودة: {{ $projectErrorStats['by_category']['quality'] }} | فنية: {{ $projectErrorStats['by_category']['technical'] }}
                    </div>
                </div>
            </div>

            <div class="stat-card-modern info">
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number">{{ $projectErrorStats['by_category']['deadline'] }}</div>
                    <div class="stat-label">أخطاء مواعيد</div>
                    <div class="stat-trend">
                        تواصل: {{ $projectErrorStats['by_category']['communication'] }} | إجرائية: {{ $projectErrorStats['by_category']['procedural'] }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Individual Performance -->
    <div class="section-modern">
        <div class="section-header">
            <h2>
                <i class="fas fa-user-chart"></i>
                أداء الأعضاء
            </h2>
            <span class="section-count">{{ count($individual_performance) }}</span>
        </div>

        <div class="modern-table-container">
            <div class="table-responsive">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>العضو</th>
                            <th>القسم</th>
                            <th>إجمالي المهام</th>
                            <th>المكتملة</th>
                            <th>قيد التنفيذ</th>
                            <th>متوقفة</th>
                            <th>المهام المنقولة</th>
                            <th>معدل الإنجاز</th>
                            <th>آخر نشاط</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($individual_performance as $performance)
                        <tr class="{{ !$performance['is_active'] ? 'inactive-member' : '' }}">
                            <td>
                                <div class="user-info">
                                    <div class="user-avatar-img">
                                        @if(isset($performance['user']['avatar']) && $performance['user']['avatar'])
                                            <img src="{{ asset('storage/' . $performance['user']['avatar']) }}"
                                                 alt="{{ $performance['user']['name'] }}"
                                                 onerror="this.src='{{ asset('avatars/man.gif') }}'">
                                        @else
                                            <img src="{{ asset('avatars/man.gif') }}" alt="{{ $performance['user']['name'] }}">
                                        @endif
                                    </div>
                                    <div class="user-details">
                                        <div class="user-name">{{ $performance['user']['name'] }}</div>
                                        <div class="user-email">{{ $performance['user']['email'] }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="department-badge">
                                    {{ $performance['user']['department'] ?? 'غير محدد' }}
                                </span>
                            </td>
                            <td>
                                <span class="task-count total">{{ $performance['tasks']['total'] }}</span>
                            </td>
                            <td>
                                <span class="task-count completed">{{ $performance['tasks']['completed'] }}</span>
                            </td>
                            <td>
                                <span class="task-count in-progress">{{ $performance['tasks']['in_progress'] }}</span>
                            </td>
                            <td>
                                <span class="task-count paused">{{ $performance['tasks']['paused'] }}</span>
                            </td>
                            <td>
                                @if(isset($performance['transfer_stats']) && $performance['transfer_stats']['has_transfers'])
                                <div class="transfer-info-detailed">
                                    <div class="transfer-row">
                                        <div class="transfer-stat-detailed transferred-from" title="مهام نُقلت من هذا المستخدم">
                                            <i class="fas fa-arrow-up"></i>
                                            <span class="transfer-label">منقولة منه:</span>
                                            <span class="transfer-number">{{ $performance['transfer_stats']['transferred_from_me'] }}</span>
                                        </div>
                                    </div>
                                    <div class="transfer-row">
                                        <div class="transfer-stat-detailed transferred-to" title="مهام نُقلت إلى هذا المستخدم">
                                            <i class="fas fa-arrow-down"></i>
                                            <span class="transfer-label">منقولة إليه:</span>
                                            <span class="transfer-number">{{ $performance['transfer_stats']['transferred_to_me'] }}</span>
                                        </div>
                                    </div>
                                    @if($performance['transfer_stats']['transferred_from_me'] > 0 || $performance['transfer_stats']['transferred_to_me'] > 0)
                                    <div class="transfer-total">
                                        <small class="text-muted">
                                            المجموع: {{ $performance['transfer_stats']['transferred_from_me'] + $performance['transfer_stats']['transferred_to_me'] }}
                                        </small>
                                    </div>
                                    @endif
                                </div>
                                @else
                                <div class="transfer-info-detailed">
                                    <div class="transfer-row">
                                        <div class="transfer-stat-detailed transferred-from">
                                            <i class="fas fa-arrow-up"></i>
                                            <span class="transfer-label">منقولة منه:</span>
                                            <span class="transfer-number">0</span>
                                        </div>
                                    </div>
                                    <div class="transfer-row">
                                        <div class="transfer-stat-detailed transferred-to">
                                            <i class="fas fa-arrow-down"></i>
                                            <span class="transfer-label">منقولة إليه:</span>
                                            <span class="transfer-number">0</span>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </td>
                            <td>
                                <div class="progress-container">
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: {{ $performance['tasks']['completion_rate'] }}%;"></div>
                                    </div>
                                    <span class="progress-text">{{ $performance['tasks']['completion_rate'] }}%</span>
                                </div>
                            </td>
                            <td>
                                @if($performance['last_activity'])
                                    <span class="activity-time {{ $performance['is_active'] ? 'active' : 'inactive' }}">
                                        {{ $performance['last_activity']->diffForHumans() }}
                                    </span>
                                @else
                                    <span class="activity-time inactive">لا يوجد نشاط</span>
                                @endif
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a href="{{ route('projects.employee-analytics', [$project, $performance['user']['id']]) }}"
                                       class="action-btn-small primary" title="عرض التفاصيل">
                                        <i class="fas fa-chart-bar"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="empty-state">
                                <div class="empty-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="empty-text">لا يوجد أعضاء في هذا المشروع</div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>



    <!-- Delayed Work -->
    @if(count($delayed_work) > 0)
    <div class="section-modern alert-section">
        <div class="section-header">
            <h2>
                <i class="fas fa-exclamation-triangle"></i>
                العمل المتأخر
            </h2>
            <span class="section-count alert">{{ count($delayed_work) }}</span>
        </div>

        <div class="delayed-work-list">
            @foreach($delayed_work as $delayed)
                @if($delayed['type'] === 'regular_task')
                <div class="delayed-item task-delayed">
                    <div class="delayed-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="delayed-info">
                        <h4>{{ $delayed['name'] }}</h4>
                        <p>مهمة متأخرة {{ $delayed['days_overdue'] }} يوم</p>
                        <div class="assigned-users">
                            @foreach($delayed['assigned_users'] as $user)
                            <span class="user-badge">{{ $user['name'] }}</span>
                            @endforeach
                        </div>
                    </div>
                    <div class="delayed-status">
                        <span class="status-badge status-{{ $delayed['status'] }}">
                            {{ $delayed['status'] }}
                        </span>
                    </div>
                </div>
                @elseif($delayed['type'] === 'project_progress')
                <div class="delayed-item project-delayed">
                    <div class="delayed-icon">
                        <i class="fas fa-project-diagram"></i>
                    </div>
                    <div class="delayed-info">
                        <h4>{{ $delayed['message'] }}</h4>
                        <p>التقدم الحالي: {{ $delayed['current_progress'] }}% | المتوقع: {{ $delayed['expected_progress'] }}%</p>
                        <p>المتبقي: {{ $delayed['days_remaining'] }} يوم</p>
                    </div>
                </div>
                @endif
            @endforeach
        </div>
    </div>
    @endif

    <!-- Project Meetings -->
    @if(isset($projectMeetings) && $projectMeetings->count() > 0)
    <div class="section-modern">
        <div class="section-header">
            <h2>
                <i class="fas fa-users"></i>
                اجتماعات المشروع
            </h2>
            <span class="section-count">{{ $projectMeetings->count() }}</span>
        </div>

        <div class="meetings-grid">
            @foreach($projectMeetings as $meeting)
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
            @foreach($recent_activities->take(10) as $activity)
            <div class="activity-item">
                <div class="activity-icon {{ $activity['type'] }}">
                    <i class="fas {{ $activity['type'] === 'regular_task' ? 'fa-tasks' : 'fa-layer-group' }}"></i>
                </div>
                <div class="activity-content">
                    <div class="activity-header">
                        <span class="activity-user">{{ $activity['user_name'] }}</span>
                        <span class="activity-action">قام بتحديث المهمة</span>
                        <span class="activity-task">{{ $activity['task_name'] }}</span>
                    </div>
                    <div class="activity-details">
                        <span class="activity-status status-{{ $activity['status'] }}">{{ $activity['status'] }}</span>
                        <span class="activity-time">{{ $activity['time_ago'] }}</span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>


</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Chart Colors
    const colors = {
        primary: '#4BAAD4',
        success: '#23c277',
        warning: '#ffad46',
        info: '#4BAAD4',
        secondary: '#6c757d',
        danger: '#f5536d'
    };

    // Task Status Chart
    const taskCtx = document.getElementById('taskStatusChart').getContext('2d');
    new Chart(taskCtx, {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($charts_data['task_status_chart']['labels']) !!},
            datasets: [{
                data: {!! json_encode($charts_data['task_status_chart']['data']) !!},
                backgroundColor: [
                    colors.info,
                    colors.primary,
                    colors.warning,
                    colors.success
                ],
                borderWidth: 0,
                cutout: '70%'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        font: {
                            size: 14,
                            family: 'Cairo'
                        }
                    }
                }
            }
        }
    });

    // Service Status Chart
    const serviceCtx = document.getElementById('serviceStatusChart').getContext('2d');
    new Chart(serviceCtx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($charts_data['service_status_chart']['labels']) !!},
            datasets: [{
                data: {!! json_encode($charts_data['service_status_chart']['data']) !!},
                backgroundColor: [
                    colors.secondary,
                    colors.primary,
                    colors.success
                ],
                borderRadius: 8,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0,0,0,0.1)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });

    // Real-time Timer functionality
    function startProjectTimer(el) {
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

    // ✅ إضافة Page Visibility API لحل مشكلة توقف التايمر في صفحة التحليلات
    function initializeAnalyticsPageVisibilityHandler() {
        // الكشف عن تغيير حالة الصفحة (نشطة/غير نشطة)
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                // المستخدم عاد للتاب - نحديث جميع التايمرات
                syncAllAnalyticsTimersWithRealTime();
            }
        });

        // تحديث التايمرات كل 10 ثوان كـ backup عندما التاب نشط
        setInterval(function() {
            if (!document.hidden) {
                syncAllAnalyticsTimersWithRealTime();
            }
        }, 10000);

        // تحديث التايمرات عند النقر على أي مكان في الصفحة
        document.addEventListener('click', function() {
            if (!document.hidden) {
                setTimeout(() => {
                    syncAllAnalyticsTimersWithRealTime();
                }, 100);
            }
        });
    }

    function syncAllAnalyticsTimersWithRealTime() {
        // ✅ تحديث تايمر المشروع الكلي
        const projectTimer = document.getElementById('project-total-timer');
        if (projectTimer) {
            syncSingleAnalyticsTimer(projectTimer);
        }

        // ✅ تحديث جميع تايمرات المستخدمين
        for (let i = 0; i < 50; i++) {
            const userTimer = document.getElementById('user-timer-' + i);
            if (userTimer) {
                syncSingleAnalyticsTimer(userTimer);
            } else {
                break; // No more user timers
            }
        }
    }

    function syncSingleAnalyticsTimer(timerElement) {
        if (!timerElement) return;

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

    // Start timers for project total and individual users
    startProjectTimer(document.getElementById('project-total-timer'));

    // Start timers for all user analytics
    for (let i = 0; i < 50; i++) { // Assuming max 50 users, adjust if needed
        const userTimer = document.getElementById('user-timer-' + i);
        if (userTimer) {
            startProjectTimer(userTimer);
        } else {
            break; // No more user timers
        }
    }

    // ✅ تهيئة Page Visibility Handler
    initializeAnalyticsPageVisibilityHandler();

    // Auto-refresh data every 30 seconds
    setInterval(function() {
        if (!document.hidden) {
            // You can implement AJAX refresh here if needed
            console.log('Auto-refresh analytics data');
        }
    }, 30000);
});
</script>

<style>
/* Project Revisions Styles */
.revisions-overview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.revision-summary-card {
    background: linear-gradient(135deg, #fff 0%, #f8f9ff 100%);
    border-radius: 16px;
    padding: 1.5rem;
    border: 1px solid #e3e6ef;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.revision-summary-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.revision-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
    font-size: 20px;
}

.revision-icon.all { background: linear-gradient(135deg, #4BAAD4, #5bc0de); color: white; }
.revision-icon.pending { background: linear-gradient(135deg, #ffad46, #ffc107); color: white; }
.revision-icon.approved { background: linear-gradient(135deg, #23c277, #28a745); color: white; }
.revision-icon.rejected { background: linear-gradient(135deg, #e74c3c, #dc3545); color: white; }
.revision-icon.internal { background: linear-gradient(135deg, #17a2b8, #138496); color: white; }
.revision-icon.external { background: linear-gradient(135deg, #fd7e14, #e76f00); color: white; }

.revision-info {
    text-align: center;
}

.revision-number {
    font-size: 2rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 0.25rem;
}

.revision-label {
    font-size: 0.9rem;
    color: #6c757d;
    font-weight: 500;
}

/* Revision Alerts */
.revision-alerts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 1.5rem;
}

.alert-card {
    background: white;
    border-radius: 16px;
    border: 1px solid #e3e6ef;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.alert-card.urgent {
    border-left: 4px solid #e74c3c;
}

.alert-card.latest {
    border-left: 4px solid #4BAAD4;
}

.alert-header {
    padding: 1rem 1.5rem;
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.alert-header i {
    font-size: 1.2rem;
    color: #495057;
}

.alert-header h4 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    color: #2c3e50;
    flex: 1;
}

.alert-header .count {
    background: #495057;
    color: white;
    border-radius: 20px;
    padding: 0.25rem 0.75rem;
    font-size: 0.8rem;
    font-weight: 600;
}

.alert-list {
    max-height: 240px;
    overflow-y: auto;
}

.alert-item {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #f8f9fa;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.alert-item:last-child {
    border-bottom: none;
}

.item-icon {
    font-size: 1.1rem;
}

.item-info {
    flex: 1;
}

.item-title {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.25rem;
    font-size: 0.9rem;
}

.item-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.8rem;
    color: #6c757d;
}

.status {
    padding: 0.2rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
}

.status-approved { background: #d4edda; color: #155724; }
.status-rejected { background: #f8d7da; color: #721c24; }
.status-pending { background: #fff3cd; color: #856404; }

@media (max-width: 768px) {
    .revisions-overview-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .revision-alerts-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 576px) {
    .revisions-overview-grid {
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

/* ✅ Task Transfer Statistics Styles */
.transfer-stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
    margin-bottom: 2rem;
}

@media (max-width: 768px) {
    .transfer-stats-grid {
        grid-template-columns: 1fr;
    }
}

.transfer-section {
    margin-top: 2rem;
    background: var(--white);
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.transfer-section h3 {
    font-size: 1.2rem;
    font-weight: 700;
    color: #2d3748;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.transfer-timeline {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.transfer-item {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 1rem;
    border-left: 4px solid #4BAAD4;
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    transition: all 0.3s ease;
}

.transfer-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.transfer-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: white;
    flex-shrink: 0;
}

.transfer-icon.regular {
    background: linear-gradient(135deg, #4BAAD4, #5bc0de);
}

.transfer-icon.template {
    background: linear-gradient(135deg, #8b5cf6, #7c3aed);
}

.transfer-content {
    flex: 1;
}

.transfer-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
    flex-wrap: wrap;
}

.task-name {
    font-weight: 600;
    color: #2d3748;
    font-size: 1rem;
}

.task-type-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 600;
}

.task-type-badge.regular {
    background: linear-gradient(135deg, rgba(75, 170, 212, 0.1), rgba(75, 170, 212, 0.2));
    color: #4BAAD4;
}

.task-type-badge.template {
    background: linear-gradient(135deg, rgba(139, 92, 246, 0.1), rgba(139, 92, 246, 0.2));
    color: #8b5cf6;
}

.transfer-type-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 600;
}

.transfer-type-badge.positive {
    background: linear-gradient(135deg, rgba(34, 197, 94, 0.1), rgba(34, 197, 94, 0.2));
    color: #22c55e;
}

.transfer-type-badge.negative {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(239, 68, 68, 0.2));
    color: #ef4444;
}

.transfer-details {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    margin-bottom: 0.75rem;
    flex-wrap: wrap;
    font-size: 0.9rem;
    color: #6b7280;
}

.transfer-users {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.transfer-time {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.transfer-reason {
    background: #e5e7eb;
    padding: 0.5rem;
    border-radius: 8px;
    font-size: 0.85rem;
    color: #4b5563;
    font-style: italic;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
}

.transfer-status {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Transfer Users Section */
.transfer-users-section {
    margin-top: 2rem;
    background: var(--white);
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.transfer-users-section h3 {
    font-size: 1.2rem;
    font-weight: 700;
    color: #2d3748;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.transfer-users-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1rem;
}

.transfer-user-card {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border-radius: 16px;
    padding: 1.5rem;
    border: 1px solid #e3e6ef;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.transfer-user-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.user-rank {
    position: absolute;
    top: 1rem;
    right: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.rank-number {
    background: #4BAAD4;
    color: white;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.9rem;
}

.user-avatar {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    overflow: hidden;
    margin: 0 auto 1rem;
    border: 3px solid #4BAAD4;
}

.user-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.user-info {
    text-align: center;
    margin-bottom: 1rem;
}

.user-name {
    font-weight: 700;
    color: #2d3748;
    font-size: 1.1rem;
    margin-bottom: 0.25rem;
}

.user-department {
    color: #6b7280;
    font-size: 0.9rem;
}

.user-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.5rem;
}

.user-stats .stat-item {
    text-align: center;
    padding: 0.75rem 0.5rem;
    background: rgba(255, 255, 255, 0.7);
    border-radius: 8px;
}

.user-stats .stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: #4BAAD4;
    display: block;
    margin-bottom: 0.25rem;
}

.user-stats .stat-label {
    font-size: 0.8rem;
    color: #6b7280;
    font-weight: 500;
}

@media (max-width: 768px) {
    .transfer-details {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }

    .transfer-users-grid {
        grid-template-columns: 1fr;
    }

    .transfer-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }

    /* Mobile styles for detailed transfer stats */
    .transfer-info-detailed {
        min-width: 120px;
        gap: 0.375rem;
    }

    .transfer-stat-detailed {
        padding: 0.25rem 0.375rem;
        font-size: 0.7rem;
    }

    .transfer-label {
        font-size: 0.65rem;
    }

    .transfer-number {
        font-size: 0.7rem;
        min-width: 18px;
        padding: 0.1rem 0.25rem;
    }

    .transfer-total small {
        font-size: 0.6rem;
    }
}

/* ✅ Project Meetings Styles */
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
