@extends('layouts.app')

@section('title', 'نظرة عامة على المشاريع')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/projects-services.css') }}">
@endpush

@section('content')
<div class="simple-container">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1>📊 نظرة عامة على المشاريع</h1>
                <p>عرض سريع وبسيط لجميع المشاريع وخدماتها</p>
            </div>
            <button onclick="openRevisionGuide()"
                    style="background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; font-size: 0.9rem; font-weight: 600; cursor: pointer; box-shadow: 0 4px 6px rgba(99, 102, 241, 0.3); transition: all 0.3s ease; display: flex; align-items: center; gap: 0.5rem;"
                    onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 12px rgba(99, 102, 241, 0.4)'"
                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px rgba(99, 102, 241, 0.3)'">
                <i class="fas fa-book-open"></i>
                <span>📖 دليل ألوان التعديلات</span>
            </button>
        </div>

        <!-- Filters Section -->
        <div class="filters-section">
            <div class="filters-row">
                <!-- Month Filter -->
                <div class="filter-group">
                    <label for="monthFilter" class="filter-label">
                        <i class="fas fa-calendar-alt"></i>
                        فلتر بالشهر
                    </label>
                    <select id="monthFilter" class="filter-select" onchange="filterByMonth()">
                        <option value="">جميع الأشهر</option>
                        <option value="01">يناير</option>
                        <option value="02">فبراير</option>
                        <option value="03">مارس</option>
                        <option value="04">أبريل</option>
                        <option value="05">مايو</option>
                        <option value="06">يونيو</option>
                        <option value="07">يوليو</option>
                        <option value="08">أغسطس</option>
                        <option value="09">سبتمبر</option>
                        <option value="10">أكتوبر</option>
                        <option value="11">نوفمبر</option>
                        <option value="12">ديسمبر</option>
                    </select>
                </div>

                <!-- Project Code Filter -->
                <div class="filter-group">
                    <label for="projectCodeFilter" class="filter-label">
                        <i class="fas fa-code"></i>
                        فلتر بكود المشروع
                    </label>
                    <select id="projectCodeFilter" class="filter-select" onchange="filterByProjectCode()">
                        <option value="">جميع أكواد المشاريع</option>
                        <!-- Project codes will be loaded here -->
                    </select>
                </div>

                <!-- Clear Filters -->
                <div class="filter-group">
                    <button class="clear-filters-btn" onclick="clearAllFilters()">
                        <i class="fas fa-times"></i>
                        مسح الفلاتر
                    </button>
                </div>
            </div>
        </div>

        <!-- Statistics Row -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-number">{{ $totalProjects }}</div>
                <div class="stat-label">إجمالي المشاريع</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $activeProjects }}</div>
                <div class="stat-label">مشاريع نشطة</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $completedProjects }}</div>
                <div class="stat-label">مشاريع مكتملة</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $totalServices }}</div>
                <div class="stat-label">إجمالي الخدمات</div>
            </div>
        </div>

        <!-- Projects Table -->
        <div class="projects-table-container">
            <div class="table-header">
                <h2>📋 قائمة المشاريع</h2>
            </div>

            <table class="projects-table">
                <thead>
                    <tr>
                        <th>المشروع</th>
                        <th>العميل</th>
                        <th>الحالة</th>
                        <th>التاريخ</th>
                        <th>الخدمات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($projects as $project)
                    <tr class="project-row"
                        data-project-id="{{ $project->id }}"
                        data-project-code="{{ $project->code ?? '' }}"
                        data-project-date="{{ $project->created_at ? \Carbon\Carbon::parse($project->created_at)->format('Y-m-d') : '' }}">
                        <td>
                            <div class="project-info">
                                <button class="project-details-btn"
                                        data-project-id="{{ $project->id }}"
                                        data-project-name="{{ $project->name }}"
                                        onclick="openProjectSidebar(this)"
                                        title="عرض تفاصيل المشروع">
                                    <i class="fas fa-info-circle"></i>
                                </button>
                                <div class="project-avatar">
                                    <i class="fas fa-project-diagram"></i>
                                </div>
                                <div class="project-details">
                                    @if($project->code)
                                        <div class="project-code-display">{{ $project->code }}</div>
                                    @endif
                                    <h4>
                                        @if($project->is_urgent)
                                            <span class="urgent-indicator">🚨 مستعجل</span>
                                        @endif
                                        {{ $project->name }}
                                    </h4>
                                    <p>{{ Str::limit($project->description, 50) }}</p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="client-info">
                                {{ optional($project->client)->name ?? 'غير محدد' }}
                            </div>
                        </td>
                        <td>
                            @php
                                $statusClasses = [
                                    'جديد' => 'status-new',
                                    'جاري التنفيذ' => 'status-in-progress',
                                    'مكتمل' => 'status-completed',
                                    'ملغي' => 'status-cancelled'
                                ];
                                $statusClass = $statusClasses[$project->status] ?? 'status-new';
                            @endphp
                            <span class="status-badge {{ $statusClass }}">
                                {{ $project->status }}
                            </span>
                        </td>
                        <td>
                            <div style="color: #6b7280; font-size: 0.9rem;">
                                {{ $project->created_at->format('Y/m/d') }}
                            </div>
                        </td>
                        <td>
                            @php
                                // استخدام الـ Workflow Service
                                $workflowService = app(\App\Services\ProjectManagement\ProjectServiceWorkflowService::class);
                                $workflow = $workflowService->getProjectServicesWorkflow($project->id);

                                $projectServices = $workflow['services'];
                                $totalServices = $workflow['total'];
                                $completedServices = $workflow['completed'];
                                $progressPercentage = $workflow['progress_percentage'];
                            @endphp

                            <div style="width: 100%; margin: 0 auto;">
                                <!-- Workflow Progress Bar - Grouped by Level -->
                                <div class="workflow-container" style="margin-bottom: 0.3rem;">
                                    @php
                                        // تجميع الخدمات حسب المستوى
                                        $servicesByLevel = collect($projectServices)->groupBy('execution_order')->sortKeys();
                                    @endphp

                                    @foreach($servicesByLevel as $level => $levelServices)
                                        <div style="margin-bottom: 0.3rem;">
                                            <!-- Level Header -->
                                            <div style="font-size: 0.65rem; color: #6b7280; margin-bottom: 0.2rem; font-weight: 600;">
                                                المستوى {{ $level }}
                                            </div>

                                            <!-- Services in this level -->
                                            <div class="workflow-steps" style="display: flex; gap: 0.3rem; align-items: stretch; flex-wrap: wrap;">
                                                @foreach($levelServices as $index => $service)
                                                    @php
                                                        $serviceData = (object) $service;
                                                        $serviceParticipants = $serviceData->participants ?? [];
                                                    @endphp

                                                    <div class="workflow-step-container" style="flex: 0 1 auto; min-width: 150px;">
                                                        <div class="workflow-step {{ $serviceData->status_class }}"
                                                             style="text-align: center; padding: 0.3rem 0.4rem; border-radius: 5px; font-size: 0.7rem; font-weight: 500; margin-bottom: 0.2rem; position: relative;">
                                                            <div style="font-size: 0.9rem; margin-bottom: 0.1rem;">{{ $serviceData->status_icon }}</div>
                                                            <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $serviceData->name }}">
                                                                {{ Str::limit($serviceData->name, 12) }}
                                                            </div>

                                                            @if(isset($serviceData->revisions_count) && $serviceData->revisions_count > 0)
                                                                @php
                                                                    $revisionsData = $serviceData->revisions_data ?? [];
                                                                    $internal = $revisionsData['internal'] ?? 0;
                                                                    $external = $revisionsData['external'] ?? 0;
                                                                    $byStatus = $revisionsData['by_status'] ?? [];

                                                                    // تحديد الحالة السائدة
                                                                    $newCount = $byStatus['new'] ?? 0;
                                                                    $inProgressCount = $byStatus['in_progress'] ?? 0;
                                                                    $pausedCount = $byStatus['paused'] ?? 0;
                                                                    $completedCount = $byStatus['completed'] ?? 0;

                                                                    // تحديد لون الدائرة حسب الحالة السائدة
                                                                    $badgeColor = '#9ca3af'; // رمادي (افتراضي)
                                                                    $badgeBorderColor = '#6b7280';
                                                                    $dominantStatusIcon = '🚨';
                                                                    $statusLabel = '';

                                                                    if ($pausedCount > 0) {
                                                                        // واقف - له أولوية قصوى
                                                                        $badgeColor = '#ef4444'; // أحمر
                                                                        $badgeBorderColor = '#dc2626';
                                                                        $dominantStatusIcon = '⏸️';
                                                                        $statusLabel = 'واقف';
                                                                    } elseif ($inProgressCount > 0) {
                                                                        // جاري
                                                                        $badgeColor = '#3b82f6'; // أزرق
                                                                        $badgeBorderColor = '#2563eb';
                                                                        $dominantStatusIcon = '🔄';
                                                                        $statusLabel = 'جاري';
                                                                    } elseif ($newCount > 0) {
                                                                        // جديد
                                                                        $badgeColor = '#f97316'; // برتقالي
                                                                        $badgeBorderColor = '#ea580c';
                                                                        $dominantStatusIcon = '🆕';
                                                                        $statusLabel = 'جديد';
                                                                    } elseif ($completedCount > 0) {
                                                                        // مكتمل
                                                                        $badgeColor = '#22c55e'; // أخضر
                                                                        $badgeBorderColor = '#16a34a';
                                                                        $dominantStatusIcon = '✅';
                                                                        $statusLabel = 'مكتمل';
                                                                    }

                                                                    // تحديد المصدر (داخلي/خارجي/مختلط)
                                                                    $sourceIcon = '';
                                                                    $sourceLabel = '';
                                                                    if ($internal > 0 && $external > 0) {
                                                                        $sourceIcon = '🔀'; // مختلط
                                                                        $sourceLabel = 'داخلي+خارجي';
                                                                    } elseif ($internal > 0) {
                                                                        $sourceIcon = '🏢'; // داخلي فقط
                                                                        $sourceLabel = 'داخلي';
                                                                    } elseif ($external > 0) {
                                                                        $sourceIcon = '🌐'; // خارجي فقط
                                                                        $sourceLabel = 'خارجي';
                                                                    }

                                                                    // بناء النص التوضيحي
                                                                    $tooltipText = "عدد التعديلات: {$serviceData->revisions_count}\n";
                                                                    $tooltipText .= "الحالة: {$statusLabel}\n";
                                                                    $tooltipText .= "المصدر: {$sourceLabel}\n\n";
                                                                    if ($internal > 0) $tooltipText .= "• داخلي: {$internal}\n";
                                                                    if ($external > 0) $tooltipText .= "• خارجي: {$external}\n";
                                                                    $tooltipText .= "\nتفصيل الحالات:\n";
                                                                    if ($newCount > 0) $tooltipText .= "🆕 جديد: {$newCount}\n";
                                                                    if ($inProgressCount > 0) $tooltipText .= "🔄 جاري: {$inProgressCount}\n";
                                                                    if ($pausedCount > 0) $tooltipText .= "⏸️ واقف: {$pausedCount}\n";
                                                                    if ($completedCount > 0) $tooltipText .= "✅ مكتمل: {$completedCount}";
                                                                @endphp

                                                                <div class="revision-badge-wrapper" style="position: absolute; top: -8px; left: -8px;">
                                                                    <!-- الدائرة الرئيسية بلون حسب الحالة -->
                                                                    <div class="revision-badge"
                                                                         style="background: {{ $badgeColor }}; color: white; border-radius: 50%; width: 26px; height: 26px; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 700; border: 2.5px solid white; box-shadow: 0 3px 6px rgba(0,0,0,0.3); cursor: help;"
                                                                         title="{{ $tooltipText }}">
                                                                        {{ $serviceData->revisions_count }}
                                                                    </div>

                                                                    <!-- أيقونة المصدر (داخلي/خارجي/مختلط) -->
                                                                    @if($sourceIcon)
                                                                        <div style="position: absolute; bottom: -5px; left: 50%; transform: translateX(-50%); background: white; border-radius: 50%; width: 16px; height: 16px; display: flex; align-items: center; justify-content: center; font-size: 0.6rem; border: 2px solid {{ $badgeBorderColor }}; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                                                                            {{ $sourceIcon }}
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            @endif
                                                        </div>

                                                        <!-- سهم يشير للموظفين -->
                                                        @if(count($serviceParticipants) > 0)
                                                            <div style="text-align: center; margin: 0.1rem 0; color: #9ca3af; font-size: 0.8rem; line-height: 1;">
                                                                ↓
                                                            </div>

                                                            <!-- عنوان المشاركين -->
                                                            <div style="text-align: center; font-size: 0.6rem; color: #6b7280; font-weight: 600; margin-bottom: 0.2rem; padding: 0.15rem 0.25rem; background: #f3f4f6; border-radius: 3px;">
                                                                👥 الموظفين
                                                            </div>
                                                        @endif

                                                        <!-- عرض المشاركين في الخدمة -->
                                                        @if(count($serviceParticipants) > 0)
                                                            <div class="service-participants-compact" style="display: flex; flex-direction: column; gap: 0.15rem; padding: 0.15rem;">
                                                                @foreach($serviceParticipants as $participant)
                                                                    @php
                                                                        // تحديد اللون حسب حالة الموظف
                                                                        $participantColor = match($participant['status']) {
                                                                            'تم تسليم نهائي' => '#10b981',      // أخضر
                                                                            'تسليم مسودة' => '#f59e0b',       // برتقالي
                                                                            'جاري' => '#3b82f6',              // أزرق
                                                                            'موقوف', 'واقف ع النموذج', 'واقف ع الأسئلة', 'واقف ع العميل', 'واقف ع مكالمة' => '#ec4899', // وردي
                                                                            default => '#9ca3af'              // رمادي
                                                                        };

                                                                        $participantBgColor = match($participant['status']) {
                                                                            'تم تسليم نهائي' => '#d1fae5',
                                                                            'تسليم مسودة' => '#fef3c7',
                                                                            'جاري' => '#dbeafe',
                                                                            'موقوف', 'واقف ع النموذج', 'واقف ع الأسئلة', 'واقف ع العميل', 'واقف ع مكالمة' => '#fce7f3',
                                                                            default => '#f3f4f6'
                                                                        };
                                                                    @endphp

                                                                    <div class="participant-mini-card"
                                                                         style="background: {{ $participantBgColor }}; border-right: 2px solid {{ $participantColor }};"
                                                                         title="{{ $participant['name'] }} - {{ $participant['status'] }}">
                                                                        <span class="participant-icon" style="font-size: 0.65rem;">{{ $participant['status_icon'] }}</span>
                                                                        <span class="participant-name" style="font-size: 0.6rem; color: #374151;">
                                                                            {{ Str::limit($participant['name'], 10) }}
                                                                        </span>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        @else
                                                            <div style="font-size: 0.6rem; color: #9ca3af; text-align: center; padding: 0.2rem; font-style: italic;">
                                                                لا يوجد موظفين
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>

                                        @if(!$loop->last)
                                            <!-- السهم بين المستويات -->
                                            <div style="text-align: center; margin: 0.2rem 0; color: #9ca3af; font-size: 1.2rem;">
                                                ↓
                                            </div>
                                        @endif
                                    @endforeach
                                </div>

                                <!-- Progress Stats -->
                                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.7rem; color: #6b7280; margin-top: 0.3rem;">
                                    <span>
                                        <i class="fas fa-check-circle" style="color: #10b981;"></i>
                                        {{ $completedServices }}/{{ $totalServices }} مكتملة
                                    </span>
                                    <span style="font-weight: 600; color: #3b82f6;">
                                        {{ $progressPercentage }}%
                                    </span>
                                    <button class="services-btn" style="font-size: 0.7rem; padding: 0.25rem 0.5rem;"
                                            data-project-id="{{ $project->id }}"
                                            data-project-name="{{ $project->name }}"
                                            onclick="toggleServices(this)">
                                        <i class="fas fa-list"></i>
                                        تفاصيل
                                    </button>
                                </div>

                                @php
                                    // حساب إجمالي التعديلات للمشروع
                                    $totalRevisions = collect($projectServices)->sum('revisions_count');
                                    $totalInternal = collect($projectServices)->sum('revisions_data.internal');
                                    $totalExternal = collect($projectServices)->sum('revisions_data.external');
                                    $totalNew = collect($projectServices)->sum('revisions_data.by_status.new');
                                    $totalInProgress = collect($projectServices)->sum('revisions_data.by_status.in_progress');
                                    $totalPaused = collect($projectServices)->sum('revisions_data.by_status.paused');
                                    $totalCompleted = collect($projectServices)->sum('revisions_data.by_status.completed');
                                @endphp
                                @if($totalRevisions > 0)
                                    <div style="margin-top: 0.3rem; display: flex; gap: 0.3rem; justify-content: center; flex-wrap: wrap; align-items: center;">
                                        <!-- إجمالي التعديلات -->
                                        <span class="badge" style="background: #fef2f2; color: #dc2626; font-size: 0.65rem; padding: 0.2rem 0.5rem; border: 1px solid #fee2e2; border-radius: 4px;">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            تعديلات: {{ $totalRevisions }}
                                        </span>

                                        <!-- داخلي/خارجي -->
                                        @if($totalInternal > 0)
                                            <span class="badge" style="background: #dbeafe; color: #1e40af; font-size: 0.6rem; padding: 0.15rem 0.4rem; border: 1px solid #bfdbfe; border-radius: 4px;">
                                                🏢 داخلي: {{ $totalInternal }}
                                            </span>
                                        @endif
                                        @if($totalExternal > 0)
                                            <span class="badge" style="background: #fef3c7; color: #92400e; font-size: 0.6rem; padding: 0.15rem 0.4rem; border: 1px solid #fde68a; border-radius: 4px;">
                                                🌐 خارجي: {{ $totalExternal }}
                                            </span>
                                        @endif

                                        <!-- الحالات -->
                                        @if($totalPaused > 0)
                                            <span class="badge" style="background: #fee2e2; color: #dc2626; font-size: 0.55rem; padding: 0.15rem 0.35rem; border-radius: 3px; border: 1px solid #fecaca;">
                                                ⏸️ {{ $totalPaused }}
                                            </span>
                                        @endif
                                        @if($totalInProgress > 0)
                                            <span class="badge" style="background: #dbeafe; color: #2563eb; font-size: 0.55rem; padding: 0.15rem 0.35rem; border-radius: 3px; border: 1px solid #bfdbfe;">
                                                🔄 {{ $totalInProgress }}
                                            </span>
                                        @endif
                                        @if($totalNew > 0)
                                            <span class="badge" style="background: #ffedd5; color: #ea580c; font-size: 0.55rem; padding: 0.15rem 0.35rem; border-radius: 3px; border: 1px solid #fed7aa;">
                                                🆕 {{ $totalNew }}
                                            </span>
                                        @endif
                                        @if($totalCompleted > 0)
                                            <span class="badge" style="background: #dcfce7; color: #16a34a; font-size: 0.55rem; padding: 0.15rem 0.35rem; border-radius: 3px; border: 1px solid #bbf7d0;">
                                                ✅ {{ $totalCompleted }}
                                            </span>
                                        @endif
                                    </div>

                                @endif

                                @php
                                    $overviewPreparationPeriodsCount = \App\Models\ProjectPreparationHistory::getPreparationPeriodsCount($project->id);
                                @endphp
                                @if($overviewPreparationPeriodsCount > 0)
                                    <div style="margin-top: 0.3rem; text-align: center;">
                                        <span class="badge bg-info text-white" style="font-size: 0.6rem; padding: 0.2rem 0.4rem;">
                                            <i class="fas fa-history"></i>
                                            فترات تحضير: {{ $overviewPreparationPeriodsCount }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </td>
                    </tr>
                    <!-- Services row (initially hidden) -->
                    <tr class="services-row" id="services-{{ $project->id }}" style="display: none;">
                        <td colspan="5" class="services-cell">
                            <div class="services-container">
                                <div class="services-loading">
                                    <i class="fas fa-spinner fa-spin"></i>
                                    جاري تحميل الخدمات...
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <h4>لا توجد مشاريع</h4>
                            <p>لم يتم العثور على أي مشاريع</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Revision Guide Sidebar -->
<div id="revisionGuideSidebar" class="project-sidebar">
    <div class="sidebar-overlay" onclick="closeRevisionGuide()"></div>
    <div class="sidebar-content" style="max-width: 500px;">
        <div class="sidebar-header" style="background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);">
            <div class="sidebar-title-section">
                <div class="project-title-row">
                    <i class="fas fa-book-open project-icon" style="color: white;"></i>
                    <h3 style="color: white;">📖 دليل ألوان التعديلات</h3>
                </div>
                <p style="color: rgba(255,255,255,0.9); font-size: 0.85rem; margin-top: 0.5rem;">
                    تعرف على معنى كل لون وأيقونة في مؤشرات التعديلات
                </p>
            </div>
            <button class="sidebar-close" onclick="closeRevisionGuide()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="sidebar-body" style="padding: 1.5rem;">
            <!-- حالات التعديلات (لون الدائرة) -->
            <div style="margin-bottom: 1.5rem;">
                <h4 style="font-size: 1rem; color: #374151; font-weight: 700; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                    <span style="font-size: 1.5rem;">🎨</span>
                    لون الدائرة = حالة التعديل
                </h4>
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <div style="display: flex; gap: 0.75rem; align-items: center; padding: 0.75rem; background: #fee2e2; border-right: 4px solid #ef4444; border-radius: 8px;">
                        <span style="display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; background: #ef4444; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 6px rgba(0,0,0,0.2); color: white; font-weight: 700; font-size: 0.9rem;">5</span>
                        <div style="flex: 1;">
                            <div style="font-weight: 700; color: #dc2626; font-size: 0.95rem; margin-bottom: 0.2rem;">⏸️ واقف (أولوية قصوى!)</div>
                            <div style="font-size: 0.8rem; color: #991b1b;">يحتاج متابعة فورية - متوقف مؤقتاً</div>
                        </div>
                    </div>

                    <div style="display: flex; gap: 0.75rem; align-items: center; padding: 0.75rem; background: #dbeafe; border-right: 4px solid #3b82f6; border-radius: 8px;">
                        <span style="display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; background: #3b82f6; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 6px rgba(0,0,0,0.2); color: white; font-weight: 700; font-size: 0.9rem;">3</span>
                        <div style="flex: 1;">
                            <div style="font-weight: 700; color: #2563eb; font-size: 0.95rem; margin-bottom: 0.2rem;">🔄 جاري</div>
                            <div style="font-size: 0.8rem; color: #1e40af;">يتم العمل عليه حالياً</div>
                        </div>
                    </div>

                    <div style="display: flex; gap: 0.75rem; align-items: center; padding: 0.75rem; background: #ffedd5; border-right: 4px solid #f97316; border-radius: 8px;">
                        <span style="display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; background: #f97316; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 6px rgba(0,0,0,0.2); color: white; font-weight: 700; font-size: 0.9rem;">2</span>
                        <div style="flex: 1;">
                            <div style="font-weight: 700; color: #ea580c; font-size: 0.95rem; margin-bottom: 0.2rem;">🆕 جديد</div>
                            <div style="font-size: 0.8rem; color: #c2410c;">لم يبدأ العمل عليه بعد</div>
                        </div>
                    </div>

                    <div style="display: flex; gap: 0.75rem; align-items: center; padding: 0.75rem; background: #dcfce7; border-right: 4px solid #22c55e; border-radius: 8px;">
                        <span style="display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; background: #22c55e; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 6px rgba(0,0,0,0.2); color: white; font-weight: 700; font-size: 0.9rem;">4</span>
                        <div style="flex: 1;">
                            <div style="font-weight: 700; color: #16a34a; font-size: 0.95rem; margin-bottom: 0.2rem;">✅ مكتمل</div>
                            <div style="font-size: 0.8rem; color: #15803d;">تم الانتهاء منه بنجاح</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- المصدر (الأيقونة أسفل الدائرة) -->
            <div style="margin-bottom: 1.5rem;">
                <h4 style="font-size: 1rem; color: #374151; font-weight: 700; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                    <span style="font-size: 1.5rem;">📍</span>
                    الأيقونة أسفل الدائرة = مصدر التعديل
                </h4>
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <div style="display: flex; gap: 0.75rem; align-items: center; padding: 0.75rem; background: #f3f4f6; border-right: 4px solid #6b7280; border-radius: 8px;">
                        <div style="position: relative;">
                            <span style="display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; background: #3b82f6; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 6px rgba(0,0,0,0.2); color: white; font-weight: 700; font-size: 0.9rem;">3</span>
                            <span style="position: absolute; bottom: -8px; left: 50%; transform: translateX(-50%); font-size: 1.2rem; background: white; border-radius: 50%; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; border: 2px solid #3b82f6; box-shadow: 0 2px 4px rgba(0,0,0,0.15);">🏢</span>
                        </div>
                        <div style="flex: 1;">
                            <div style="font-weight: 700; color: #374151; font-size: 0.95rem; margin-bottom: 0.2rem;">🏢 داخلي</div>
                            <div style="font-size: 0.8rem; color: #6b7280;">من الفريق الداخلي (خطأ من موظف)</div>
                        </div>
                    </div>

                    <div style="display: flex; gap: 0.75rem; align-items: center; padding: 0.75rem; background: #f3f4f6; border-right: 4px solid #6b7280; border-radius: 8px;">
                        <div style="position: relative;">
                            <span style="display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; background: #f59e0b; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 6px rgba(0,0,0,0.2); color: white; font-weight: 700; font-size: 0.9rem;">2</span>
                            <span style="position: absolute; bottom: -8px; left: 50%; transform: translateX(-50%); font-size: 1.2rem; background: white; border-radius: 50%; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; border: 2px solid #f59e0b; box-shadow: 0 2px 4px rgba(0,0,0,0.15);">🌐</span>
                        </div>
                        <div style="flex: 1;">
                            <div style="font-weight: 700; color: #374151; font-size: 0.95rem; margin-bottom: 0.2rem;">🌐 خارجي</div>
                            <div style="font-size: 0.8rem; color: #6b7280;">من العميل (طلب تعديل)</div>
                        </div>
                    </div>

                    <div style="display: flex; gap: 0.75rem; align-items: center; padding: 0.75rem; background: #f3f4f6; border-right: 4px solid #6b7280; border-radius: 8px;">
                        <div style="position: relative;">
                            <span style="display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; background: #ef4444; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 6px rgba(0,0,0,0.2); color: white; font-weight: 700; font-size: 0.9rem;">5</span>
                            <span style="position: absolute; bottom: -8px; left: 50%; transform: translateX(-50%); font-size: 1.2rem; background: white; border-radius: 50%; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; border: 2px solid #ef4444; box-shadow: 0 2px 4px rgba(0,0,0,0.15);">🔀</span>
                        </div>
                        <div style="flex: 1;">
                            <div style="font-weight: 700; color: #374151; font-size: 0.95rem; margin-bottom: 0.2rem;">🔀 مختلط</div>
                            <div style="font-size: 0.8rem; color: #6b7280;">داخلي وخارجي معاً</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ملاحظة مهمة -->
            <div style="padding: 1rem; background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border: 2px solid #fbbf24; border-radius: 10px; box-shadow: 0 2px 8px rgba(251, 191, 36, 0.2);">
                <div style="display: flex; gap: 0.75rem; align-items: start;">
                    <span style="font-size: 1.5rem;">💡</span>
                    <div>
                        <div style="font-weight: 700; color: #92400e; font-size: 0.95rem; margin-bottom: 0.3rem;">ملاحظة مهمة</div>
                        <div style="font-size: 0.8rem; color: #78350f; line-height: 1.6;">
                            • الرقم داخل الدائرة = إجمالي عدد التعديلات<br>
                            • لو مش شايف أي دائرة = مفيش تعديلات على الخدمة دي<br>
                            • مرر الماوس على الدائرة لرؤية التفاصيل الكاملة
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Project Details Sidebar -->
<div id="projectDetailsSidebar" class="project-sidebar">
    <div class="sidebar-overlay" onclick="closeProjectSidebar()"></div>
    <div class="sidebar-content">
        <div class="sidebar-header">
            <div class="sidebar-title-section">
                <div class="project-title-row">
                    <i class="fas fa-folder project-icon"></i>
                    <h3 id="sidebarProjectName">تفاصيل المشروع</h3>
                </div>
                <p id="sidebarProjectCode" class="project-code">كود المشروع</p>
            </div>
            <button class="sidebar-close" onclick="closeProjectSidebar()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="sidebar-body">
            <!-- Loading State -->
            <div id="sidebarLoading" class="sidebar-loading">
                <i class="fas fa-spinner fa-spin"></i>
                <p>جاري تحميل البيانات...</p>
            </div>

            <!-- Content -->
            <div id="sidebarContent" style="display: none;">
                <!-- Services Section -->
                <div class="sidebar-section">
                    <h4 class="section-title">
                        <i class="fas fa-cog"></i>
                        الخدمات
                    </h4>
                    <div id="sidebarServices" class="services-chips">
                        <!-- Services will be loaded here -->
                    </div>
                </div>

                <!-- Participants Section -->
                <div class="sidebar-section">
                    <h4 class="section-title">
                        <i class="fas fa-users"></i>
                        المشاركين
                    </h4>
                    <div id="sidebarParticipants" class="participants-list">
                        <!-- Participants will be loaded here -->
                    </div>
                </div>

                <!-- Tasks Section -->
                <div id="tasksSection" class="sidebar-section" style="display: none;">
                    <h4 class="section-title">
                        <i class="fas fa-tasks"></i>
                        مهام: <span id="selectedParticipantName"></span>
                    </h4>
                    <div id="sidebarTasks" class="tasks-container">
                        <!-- Tasks will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="{{ asset('js/projects-services-overview.js') }}"></script>
@endpush
