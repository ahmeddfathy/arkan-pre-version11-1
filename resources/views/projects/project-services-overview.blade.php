@extends('layouts.app')

@section('title', 'نظرة عامة على المشاريع')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/projects-services.css') }}">
@endpush

@section('content')
<div class="simple-container">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>📊 نظرة عامة على المشاريع</h1>
            <p>عرض سريع وبسيط لجميع المشاريع وخدماتها</p>
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
                                <div class="workflow-container" style="margin-bottom: 0.5rem;">
                                    @php
                                        // تجميع الخدمات حسب المستوى
                                        $servicesByLevel = collect($projectServices)->groupBy('execution_order')->sortKeys();
                                    @endphp

                                    @foreach($servicesByLevel as $level => $levelServices)
                                        <div style="margin-bottom: 0.5rem;">
                                            <!-- Level Header -->
                                            <div style="font-size: 0.7rem; color: #6b7280; margin-bottom: 0.3rem; font-weight: 600;">
                                                المستوى {{ $level }}
                                            </div>

                                            <!-- Services in this level -->
                                            <div class="workflow-steps" style="display: flex; gap: 0.5rem; align-items: stretch; flex-wrap: wrap;">
                                                @foreach($levelServices as $index => $service)
                                                    @php
                                                        $serviceData = (object) $service;
                                                        $serviceParticipants = $serviceData->participants ?? [];
                                                    @endphp

                                                    <div class="workflow-step-container" style="flex: 0 1 auto; min-width: 200px;">
                                                        <div class="workflow-step {{ $serviceData->status_class }}"
                                                             style="text-align: center; padding: 0.5rem 0.6rem; border-radius: 6px; font-size: 0.75rem; font-weight: 500; margin-bottom: 0.3rem;">
                                                            <div style="font-size: 1.1rem; margin-bottom: 0.2rem;">{{ $serviceData->status_icon }}</div>
                                                            <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $serviceData->name }}">
                                                                {{ Str::limit($serviceData->name, 15) }}
                                                            </div>
                                                        </div>

                                                        <!-- سهم يشير للموظفين -->
                                                        @if(count($serviceParticipants) > 0)
                                                            <div style="text-align: center; margin: 0.2rem 0; color: #9ca3af; font-size: 1rem; line-height: 1;">
                                                                ↓
                                                            </div>

                                                            <!-- عنوان المشاركين -->
                                                            <div style="text-align: center; font-size: 0.65rem; color: #6b7280; font-weight: 600; margin-bottom: 0.3rem; padding: 0.2rem 0.3rem; background: #f3f4f6; border-radius: 4px;">
                                                                👥 الموظفين
                                                            </div>
                                                        @endif

                                                        <!-- عرض المشاركين في الخدمة -->
                                                        @if(count($serviceParticipants) > 0)
                                                            <div class="service-participants-compact" style="display: flex; flex-direction: column; gap: 0.2rem; padding: 0.2rem;">
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
                                                                         style="background: {{ $participantBgColor }}; border-right: 3px solid {{ $participantColor }};"
                                                                         title="{{ $participant['name'] }} - {{ $participant['status'] }}">
                                                                        <span class="participant-icon" style="font-size: 0.7rem;">{{ $participant['status_icon'] }}</span>
                                                                        <span class="participant-name" style="font-size: 0.65rem; color: #374151;">
                                                                            {{ Str::limit($participant['name'], 12) }}
                                                                        </span>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        @else
                                                            <div style="font-size: 0.65rem; color: #9ca3af; text-align: center; padding: 0.3rem; font-style: italic;">
                                                                لا يوجد موظفين
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>

                                        @if(!$loop->last)
                                            <!-- السهم بين المستويات -->
                                            <div style="text-align: center; margin: 0.3rem 0; color: #9ca3af; font-size: 1.5rem;">
                                                ↓
                                            </div>
                                        @endif
                                    @endforeach
                                </div>

                                <!-- Progress Stats -->
                                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.75rem; color: #6b7280;">
                                    <span>
                                        <i class="fas fa-check-circle" style="color: #10b981;"></i>
                                        {{ $completedServices }}/{{ $totalServices }} مكتملة
                                    </span>
                                    <span style="font-weight: 600; color: #3b82f6;">
                                        {{ $progressPercentage }}%
                                    </span>
                                    <button class="services-btn" style="font-size: 0.75rem; padding: 0.3rem 0.6rem;"
                                            data-project-id="{{ $project->id }}"
                                            data-project-name="{{ $project->name }}"
                                            onclick="toggleServices(this)">
                                        <i class="fas fa-list"></i>
                                        تفاصيل
                                    </button>
                                </div>

                                @php
                                    $overviewPreparationPeriodsCount = \App\Models\ProjectPreparationHistory::getPreparationPeriodsCount($project->id);
                                @endphp
                                @if($overviewPreparationPeriodsCount > 0)
                                    <div style="margin-top: 0.5rem; text-align: center;">
                                        <span class="badge bg-info text-white" style="font-size: 0.65rem; padding: 0.3rem 0.5rem;">
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
