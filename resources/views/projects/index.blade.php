@extends('layouts.app')

@section('title', 'إدارة المشاريع')

@section('content')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/projects.css') }}">
<link rel="stylesheet" href="{{ asset('css/project-acknowledgment.css') }}">
<!-- Projects Kanban CSS -->
<link rel="stylesheet" href="{{ asset('css/projects/projects-kanban.css') }}">
<!-- Projects Calendar CSS -->
<link rel="stylesheet" href="{{ asset('css/tasks/my-tasks-calendar.css') }}">
<!-- Ultra Modern Projects CSS -->
<link rel="stylesheet" href="{{ asset('css/projects/projects-index-modern.css') }}">
<!-- Projects Index Custom Styles -->
<link rel="stylesheet" href="{{ asset('css/projects/index/projects-index-styles.css') }}">
<!-- SweetAlert2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

@endpush
<div class="projects-modern-container">
    <div class="row">
        <div class="col-12">
            <div class="projects-glass-card">
                <div class="projects-modern-header d-flex justify-content-between align-items-center">
                    <h3 class="projects-header-title mb-0">
                        <i class="fas fa-project-diagram me-3"></i>
                        إدارة المشاريع
                    </h3>
                    <div class="d-flex gap-2">
                        <a href="{{ route('projects.preparation-period') }}" class="btn btn-outline-light">
                            <i class="fas fa-clock me-2"></i>
                            فترات التحضير
                        </a>
                        <a href="{{ route('projects.create') }}" class="projects-add-btn">
                            <i class="fas fa-plus-circle me-2"></i>
                            إضافة مشروع جديد
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                                        <!-- Ultra Modern Filter & Search Section -->
                    <div class="projects-filter-section">
                        <!-- الصف الأول: الفلاتر -->
                        <div class="row g-2 align-items-end mb-3">
                            <!-- البحث -->
                            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12">
                                <label class="filter-label">🔍 البحث في المشاريع</label>
                                <div class="position-relative">
                                    <i class="fas fa-search position-absolute" style="top: 50%; right: 15px; transform: translateY(-50%); color: rgba(103, 126, 234, 0.6); z-index: 2;"></i>
                                    <input type="text" class="projects-search-input filter-box" id="searchProject" placeholder="ابحث عن اسم المشروع، العميل، أو المسؤول..." title="ابحث في أسماء المشاريع، العملاء، والمسؤولين" style="padding-right: 45px;">
                                </div>
                            </div>

                            <!-- فلتر الحالة -->
                            <div class="col-xl-2 col-lg-2 col-md-3 col-sm-6">
                                <label class="filter-label">📊 حالة المشروع</label>
                                <select class="projects-select filter-box" id="statusFilter" title="فلتر المشاريع حسب حالة التنفيذ">
                                    <option value="">جميع الحالات</option>
                                    <option value="جديد">🆕 جديد</option>
                                    <option value="جاري التنفيذ">⚙️ جاري التنفيذ</option>
                                    <option value="مكتمل">✅ مكتمل</option>
                                    <option value="ملغي">❌ ملغي</option>
                                </select>
                            </div>

                            <!-- فلتر العملاء -->
                            <div class="col-xl-2 col-lg-2 col-md-3 col-sm-6">
                                <label class="filter-label">👥 العميل</label>
                                <select class="projects-select filter-box" id="clientFilter" title="فلتر المشاريع حسب العميل">
                                    <option value="">جميع العملاء</option>
                                    <!-- يمكن تعبئة هذا ديناميكياً من البيانات -->
                                </select>
                            </div>

                            <!-- فلتر الشهر والسنة -->
                            <div class="col-xl-2 col-lg-2 col-md-3 col-sm-6">
                                <label class="filter-label">📅 شهر إنشاء المشروع</label>
                                <div class="position-relative">
                                    <i class="fas fa-calendar position-absolute" style="top: 50%; right: 15px; transform: translateY(-50%); color: rgba(103, 126, 234, 0.6); z-index: 2;"></i>
                                    <input type="month" class="projects-select filter-box" id="monthYearFilter" placeholder="اختر الشهر والسنة" title="فلتر المشاريع حسب شهر وسنة الإنشاء" style="padding-right: 45px;">
                                </div>
                            </div>

                            <!-- فلتر نوع التاريخ للتقويم -->
                            <div class="col-xl-2 col-lg-2 col-md-3 col-sm-6">
                                <label class="filter-label">⚙️ نوع موعد التسليم</label>
                                <select class="projects-select filter-box" id="dateTypeFilter" title="اختر نوع التاريخ المستخدم في فلتر شهر التسليم">
                                    <option value="client_agreed">🤝 متفق مع العميل</option>
                                    <option value="team_delivery">👥 محدد من الفريق</option>
                                </select>
                                <small class="text-muted mt-1 d-block" style="font-size: 10px;">يؤثر على فلتر الشهر التالي ←</small>
                            </div>

                            <!-- فلتر شهر الاستلام -->
                            <div class="col-xl-2 col-lg-2 col-md-3 col-sm-6 connected-filters">
                                <label class="filter-label" id="deliveryMonthLabel">🚚 شهر التسليم (متفق مع العميل)</label>
                                <div class="position-relative">
                                    <i class="fas fa-calendar-check position-absolute" style="top: 50%; right: 15px; transform: translateY(-50%); color: rgba(103, 126, 234, 0.6); z-index: 2;"></i>
                                    <input type="month" class="projects-select filter-box" id="deliveryMonthFilter" placeholder="اختر شهر التسليم" title="فلتر المشاريع حسب موعد التسليم" style="padding-right: 45px;">
                                </div>
                            </div>

                            <!-- زر إعادة تعيين الفلاتر -->
                            <div class="col-xl-1 col-lg-2 col-md-2 col-sm-6">
                                <label class="filter-label">🔄 إعادة تعيين</label>
                                <button type="button" class="btn btn-outline-secondary filter-box" id="resetFiltersBtn" title="إعادة تعيين جميع الفلاتر إلى الوضع الافتراضي">
                                    <i class="fas fa-undo-alt"></i>
                                </button>
                            </div>
                        </div>

                        <div class="row g-3 align-items-center">
                            <div class="col-12 text-center">
                                <div class="projects-view-toggle">
                                    <button type="button" class="projects-view-btn active" id="tableViewBtn">
                                        <i class="fas fa-table me-2"></i>
                                        جدول
                                    </button>
                                    <button type="button" class="projects-view-btn" id="kanbanViewBtn">
                                        <i class="fas fa-columns me-2"></i>
                                        كانبان
                                    </button>
                                    <button type="button" class="projects-view-btn" id="calendarViewBtn">
                                        <i class="fas fa-calendar-alt me-2"></i>
                                        تقويم
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="projects-modern-table" id="tableView" style="display: block;">
                        <div class="projects-table-container">
                            <table class="projects-table">
                                <thead>
                                    <tr>
                                        <th>📋 اسم المشروع</th>
                                        <th>🏷️ الكود</th>
                                        <th>👤 العميل</th>
                                        <th>👨‍💼 المسؤول</th>
                                        <th>📊 الحالة</th>
                                        <th>📅 تاريخ البداية</th>
                                        <th id="deliveryDateHeader">📅 تواريخ التسليم</th>
                                        <th>📈 التقدم</th>
                                        <th>⚙️ الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($projects as $project)
                                    <tr class="project-row {{ $project->is_urgent ? 'urgent' : '' }} {{ $project->hasUnacknowledgedParticipation() ? 'unacknowledged-project' : '' }}"
                                        data-project-month-year="{{ $project->project_month_year }}"
                                        data-team-delivery="{{ $project->team_delivery_date ? $project->team_delivery_date->format('Y-m') : '' }}"
                                        data-client-delivery="{{ $project->client_agreed_delivery_date ? $project->client_agreed_delivery_date->format('Y-m') : '' }}"
                                        data-actual-delivery="{{ $project->actual_delivery_date ? $project->actual_delivery_date->format('Y-m') : '' }}">
                                        <td class="projects-project-title">
                                            <div class="position-relative">
                                                @if($project->is_urgent)
                                                    <div class="projects-urgent-indicator" title="مشروع مستعجل">
                                                        🚨
                                                    </div>
                                                @endif
                                                @if($project->hasUnacknowledgedParticipation())
                                                    <div class="projects-unacknowledged-indicator" title="مشروع غير مستلم">
                                                        ⚠️
                                                    </div>
                                                @endif
                                                <h6 class="mb-1 {{ $project->is_urgent ? 'text-danger' : '' }}">
                                                    {{ $project->name }}
                                                    @if($project->preparation_enabled && $project->isInPreparationPeriod())
                                                        <span class="badge bg-primary" style="font-size: 0.7rem; vertical-align: middle;" title="في فترة التحضير - باقي {{ $project->remaining_preparation_days }} يوم">
                                                            <i class="fas fa-clock"></i> تحضير
                                                        </span>
                                                    @endif
                                                </h6>
                                                @if($project->description)
                                                    <small class="text-muted d-block">{{ Str::limit($project->description, 40) }}</small>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            @if($project->code)
                                                <span class="projects-status-badge projects-status-new">{{ $project->code }}</span>
                                            @else
                                                <span class="text-muted">🚫 غير محدد</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center justify-content-center">
                                                <span class="fw-bold">🏢 {{ $project->client->name }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center justify-content-center">
                                                <span class="fw-bold">👨‍💼 {{ $project->manager ?? 'غير محدد' }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            @php
                                                $statusClasses = [
                                                    'جديد' => 'projects-status-new',
                                                    'جاري التنفيذ' => 'projects-status-inprogress',
                                                    'مكتمل' => 'projects-status-completed',
                                                    'ملغي' => 'projects-status-cancelled'
                                                ];
                                                $statusClass = $statusClasses[$project->status] ?? 'projects-status-new';
                                            @endphp
                                            <span class="projects-status-badge {{ $statusClass }}">{{ $project->status }}</span>
                                        </td>
                                        <td>
                                            @if($project->start_date)
                                                <div class="table-date-item">
                                                    <i class="fas fa-play-circle text-primary me-1"></i>
                                                    <span class="fw-bold text-primary">{{ $project->start_date->format('Y-m-d') }}</span>
                                                </div>
                                            @else
                                                <span class="text-muted">🚫 غير محدد</span>
                                            @endif
                                        </td>
                                        <td class="delivery-date-cell"
                                            data-client-date="{{ $project->client_agreed_delivery_date ? $project->client_agreed_delivery_date->format('Y-m-d') : '' }}"
                                            data-team-date="{{ $project->team_delivery_date ? $project->team_delivery_date->format('Y-m-d') : '' }}">
                                            <div class="table-dates-container">
                                                @if($project->team_delivery_date)
                                                    <div class="table-date-item team-date">
                                                        <i class="fas fa-users text-success me-1"></i>
                                                        <small class="text-muted">محدد من الفريق:</small>
                                                        <span class="fw-bold text-success">{{ $project->team_delivery_date->format('Y-m-d') }}</span>
                                                    </div>
                                                @endif

                                                @if($project->client_agreed_delivery_date)
                                                    <div class="table-date-item client-date">
                                                        <i class="fas fa-handshake text-warning me-1"></i>
                                                        <small class="text-muted">متفق مع العميل:</small>
                                                        <span class="fw-bold text-warning">{{ $project->client_agreed_delivery_date->format('Y-m-d') }}</span>
                                                    </div>
                                                @endif

                                                @if($project->actual_delivery_date)
                                                    <div class="table-date-item actual-date">
                                                        <i class="fas fa-check-circle text-info me-1"></i>
                                                        <small class="text-muted">التسليم الفعلي:</small>
                                                        <span class="fw-bold text-info">{{ $project->actual_delivery_date->format('Y-m-d') }}</span>
                                                    </div>
                                                @endif

                                                @if(!$project->team_delivery_date && !$project->client_agreed_delivery_date && !$project->actual_delivery_date)
                                                    <span class="text-muted">🚫 غير محدد</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            @php
                                                $completedPercentage = $project->completion_percentage;
                                                $hasTasks = ($project->tasks->count() + $project->templateTaskUsers()->count()) > 0;
                                            @endphp

                                            @if($hasTasks)
                                                <div class="projects-progress-container">
                                                    <div class="projects-progress-bar">
                                                        <div class="projects-progress-fill" data-progress="{{ $completedPercentage }}"></div>
                                                    </div>
                                                    <span class="projects-progress-text">{{ round($completedPercentage) }}%</span>
                                                </div>
                                            @else
                                                <span class="text-muted">🚫 لا توجد مهام</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="projects-actions">
                                                @php
                                                    $userMaxHierarchy = \App\Models\RoleHierarchy::getUserMaxHierarchyLevel(Auth::user());
                                                    $preparationPeriodsCount = \App\Models\ProjectPreparationHistory::getPreparationPeriodsCount($project->id);
                                                @endphp
                                                @if($userMaxHierarchy && $userMaxHierarchy >= 4)
                                                    <button type="button" class="projects-action-btn projects-btn-info" title="التفاصيل الكاملة" onclick="openProjectSidebar({{ $project->id }})" style="background: linear-gradient(135deg, #17a2b8, #138496); color: white;">
                                                        <i class="fas fa-info-circle"></i>
                                                    </button>
                                                @endif
                                                <a href="{{ route('projects.show', $project) }}" class="projects-action-btn projects-btn-view" title="عرض">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('projects.edit', $project) }}" class="projects-action-btn projects-btn-edit" title="تعديل">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="{{ route('projects.custom-fields.edit', $project) }}" class="projects-action-btn" title="البيانات الإضافية" style="background: linear-gradient(135deg, #9c27b0, #7b1fa2); color: white;">
                                                    <i class="fas fa-database"></i>
                                                </a>
                                                @if($preparationPeriodsCount > 0)
                                                    <div class="d-inline-block ms-1" title="عدد فترات التحضير: {{ $preparationPeriodsCount }}">
                                                        <span class="badge bg-info text-white" style="font-size: 0.75rem; padding: 0.35rem 0.5rem;">
                                                            <i class="fas fa-history me-1"></i>
                                                            {{ $preparationPeriodsCount }}
                                                        </span>
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($projects->isEmpty())
                            <div class="projects-empty-state">
                                <div class="projects-empty-icon">📋</div>
                                <h4 class="projects-empty-title">لا توجد مشاريع بعد!</h4>
                                <p class="projects-empty-subtitle">ابدأ رحلتك في إدارة المشاريع وأضف مشروعك الأول</p>
                                <a href="{{ route('projects.create') }}" class="projects-empty-btn">
                                    <i class="fas fa-rocket me-2"></i>
                                    إضافة مشروع جديد
                                </a>
                            </div>
                        @endif
                    </div>



                    <div id="kanbanView" style="display: none;">
                        <div class="projects-index-kanban-board">
                            <div class="projects-index-kanban-columns">
                                @php
                                    $statusGroups = [
                                        'جديد' => ['label' => 'جديد', 'color' => 'new', 'icon' => 'fas fa-plus-circle'],
                                        'جاري التنفيذ' => ['label' => 'جاري التنفيذ', 'color' => 'in-progress', 'icon' => 'fas fa-cogs'],
                                        'مكتمل' => ['label' => 'مكتمل', 'color' => 'completed', 'icon' => 'fas fa-check-circle'],
                                        'ملغي' => ['label' => 'ملغي', 'color' => 'cancelled', 'icon' => 'fas fa-times-circle']
                                    ];
                                @endphp

                                @foreach($statusGroups as $status => $statusData)
                                    @php
                                        $statusProjects = $projects->filter(function($project) use ($status) {
                                            return $project->status == $status;
                                        });
                                    @endphp

                                    <div class="projects-index-kanban-column" data-status="{{ $status }}">
                                        <div class="projects-index-kanban-header {{ $statusData['color'] }}">
                                            <h6>
                                                <i class="{{ $statusData['icon'] }}"></i>
                                                {{ $statusData['label'] }}
                                            </h6>
                                            <span class="project-count">{{ $statusProjects->count() }}</span>
                                        </div>
                                        <div class="projects-index-kanban-cards kanban-drop-zone" data-status="{{ $status }}">
                                            @forelse($statusProjects as $project)
                                                <div class="projects-index-kanban-card {{ $project->is_urgent ? 'urgent-project' : '' }} {{ $project->hasUnacknowledgedParticipation() ? 'unacknowledged-project' : '' }}"
                                                     data-status="{{ $project->status }}"
                                                     data-project-id="{{ $project->id }}"
                                                     data-project-month-year="{{ $project->project_month_year }}"
                                                     data-team-delivery="{{ $project->team_delivery_date ? $project->team_delivery_date->format('Y-m') : '' }}"
                                                     data-client-delivery="{{ $project->client_agreed_delivery_date ? $project->client_agreed_delivery_date->format('Y-m') : '' }}"
                                                     data-actual-delivery="{{ $project->actual_delivery_date ? $project->actual_delivery_date->format('Y-m') : '' }}"
                                                     draggable="true">

                                                    {{-- Card Title --}}
                                                    <div class="projects-index-kanban-card-title">
                                                        <i class="fas fa-project-diagram"></i>
                                                        @if($project->is_urgent)
                                                            <i class="fas fa-exclamation-triangle text-danger me-1"></i>
                                                        @endif
                                                        @if($project->hasUnacknowledgedParticipation())
                                                            <i class="fas fa-exclamation-circle text-warning me-1"></i>
                                                        @endif
                                                        {{ Str::limit($project->name, 30) }}
                                                        @if($project->code)
                                                            <span class="badge bg-light text-dark">{{ $project->code }}</span>
                                                        @endif
                                                        @if($project->preparation_enabled && $project->isInPreparationPeriod())
                                                            <span class="badge bg-primary" style="font-size: 0.7rem;" title="في فترة التحضير">
                                                                <i class="fas fa-clock"></i>
                                                            </span>
                                                        @endif
                                                    </div>

                                                    {{-- Description --}}
                                                    @if($project->description)
                                                        <div class="projects-index-kanban-card-description">
                                                            {{ Str::limit($project->description, 80) }}
                                                        </div>
                                                    @endif

                                                    {{-- Meta Information --}}
                                                    <div class="projects-index-kanban-card-meta">
                                                        <div class="projects-index-kanban-card-client">
                                                            {{ Str::limit($project->client->name, 20) }}
                                                        </div>
                                                        @if($project->manager)
                                                            <div class="projects-index-kanban-card-manager">
                                                                {{ Str::limit($project->manager, 20) }}
                                                            </div>
                                                        @endif
                                                    </div>

                                                    {{-- Progress Section --}}
                                                    @php
                                                        $completedPercentage = $project->completion_percentage;
                                                        $regularTasksCount = $project->tasks->count();
                                                        $templateTasksCount = $project->templateTaskUsers()->count();
                                                        $totalTasksCount = $regularTasksCount + $templateTasksCount;
                                                        $completedRegularTasks = $project->tasks->where('status', 'completed')->count();
                                                        $completedTemplateTasks = $project->templateTaskUsers()->where('status', 'completed')->count();
                                                        $completedTasksCount = $completedRegularTasks + $completedTemplateTasks;
                                                    @endphp

                                                    @if($totalTasksCount > 0)
                                                        <div class="projects-index-kanban-card-progress">
                                                            <span class="projects-index-kanban-card-progress-text">{{ round($completedPercentage) }}%</span>
                                                            <div class="progress">
                                                                <div class="progress-bar" role="progressbar"
                                                                     data-progress="{{ $completedPercentage }}"
                                                                     aria-valuenow="{{ $completedPercentage }}"
                                                                     aria-valuemin="0" aria-valuemax="100"></div>
                                                            </div>
                                                            <small class="text-muted">{{ $completedTasksCount }}/{{ $totalTasksCount }} مهام</small>
                                                        </div>
                                                    @endif

                                                    {{-- Dates Section --}}
                                                    <div class="projects-index-kanban-card-dates">
                                                        @if($project->start_date)
                                                            <div class="projects-index-kanban-card-date start-date">
                                                                <i class="fas fa-play-circle me-1"></i>
                                                                <span class="date-label">تاريخ البداية:</span>
                                                                <span class="date-value">{{ $project->start_date->format('Y-m-d') }}</span>
                                                            </div>
                                                        @endif

                                                        @if($project->team_delivery_date)
                                                            <div class="projects-index-kanban-card-date team-date">
                                                                <i class="fas fa-users me-1"></i>
                                                                <span class="date-label">محدد من الفريق:</span>
                                                                <span class="date-value">{{ $project->team_delivery_date->format('Y-m-d') }}</span>
                                                            </div>
                                                        @endif

                                                        @if($project->client_agreed_delivery_date)
                                                            <div class="projects-index-kanban-card-date client-date">
                                                                <i class="fas fa-handshake me-1"></i>
                                                                <span class="date-label">متفق مع العميل:</span>
                                                                <span class="date-value">{{ $project->client_agreed_delivery_date->format('Y-m-d') }}</span>
                                                            </div>
                                                        @endif

                                                        @if($project->actual_delivery_date)
                                                            <div class="projects-index-kanban-card-date actual-date">
                                                                <i class="fas fa-check-circle me-1"></i>
                                                                <span class="date-label">تاريخ التسليم الفعلي:</span>
                                                                <span class="date-value">{{ $project->actual_delivery_date->format('Y-m-d') }}</span>
                                                            </div>
                                                        @endif
                                                    </div>

                                                    {{-- Actions --}}
                                                    <div class="projects-index-kanban-card-actions">
                                                        @php
                                                            $userMaxHierarchy = \App\Models\RoleHierarchy::getUserMaxHierarchyLevel(Auth::user());
                                                            $kanbanPreparationPeriodsCount = \App\Models\ProjectPreparationHistory::getPreparationPeriodsCount($project->id);
                                                        @endphp
                                                        @if($userMaxHierarchy && $userMaxHierarchy >= 4)
                                                            <button type="button" class="btn btn-outline-info" title="التفاصيل الكاملة" onclick="openProjectSidebar({{ $project->id }})">
                                                                <i class="fas fa-info-circle"></i>
                                                            </button>
                                                        @endif
                                                        <a href="{{ route('projects.show', $project) }}" class="btn btn-outline-primary">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="{{ route('projects.edit', $project) }}" class="btn btn-outline-warning">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="{{ route('projects.custom-fields.edit', $project) }}" class="btn btn-outline-secondary" title="البيانات الإضافية" style="background: linear-gradient(135deg, #9c27b0, #7b1fa2); color: white; border: none;">
                                                            <i class="fas fa-database"></i>
                                                        </a>
                                                        @if($kanbanPreparationPeriodsCount > 0)
                                                            <div class="d-inline-block" title="عدد فترات التحضير: {{ $kanbanPreparationPeriodsCount }}">
                                                                <span class="badge bg-info text-white" style="font-size: 0.7rem;">
                                                                    <i class="fas fa-history me-1"></i>
                                                                    {{ $kanbanPreparationPeriodsCount }}
                                                                </span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            @empty
                                                <div class="projects-index-kanban-empty-state">
                                                    <i class="fas fa-inbox"></i>
                                                    <p>لا توجد مشاريع</p>
                                                </div>
                                            @endforelse
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Projects Calendar View -->
                    <div id="calendarView" class="calendar-view" style="display: none;">
                        <div class="calendar-container">
                            <!-- Calendar Header -->
                            <div class="calendar-header">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <button class="btn btn-outline-secondary" id="prevMonthProjects">
                                            <i class="fas fa-chevron-left"></i>
                                        </button>
                                        <h5 id="currentMonthYearProjects" class="mb-0 fw-bold"></h5>
                                        <button class="btn btn-outline-secondary" id="nextMonthProjects">
                                            <i class="fas fa-chevron-right"></i>
                                        </button>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <button class="btn btn-sm btn-outline-primary" id="backToTableBtnProjects">
                                            <i class="fas fa-table me-1"></i>
                                            العودة للجدول
                                        </button>
                                        <button class="btn btn-sm btn-primary" id="todayBtnProjects">اليوم</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Calendar Grid -->
                            <div class="calendar-grid">
                                <!-- Days of Week Header -->
                                <div class="calendar-weekdays">
                                    <div class="calendar-weekday">الأحد</div>
                                    <div class="calendar-weekday">الاثنين</div>
                                    <div class="calendar-weekday">الثلاثاء</div>
                                    <div class="calendar-weekday">الأربعاء</div>
                                    <div class="calendar-weekday">الخميس</div>
                                    <div class="calendar-weekday">الجمعة</div>
                                    <div class="calendar-weekday">السبت</div>
                                </div>

                                <!-- Calendar Days -->
                                <div class="calendar-days" id="calendarDaysProjects">
                                    <!-- Days will be populated by JavaScript -->
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($projects->hasPages())
                        <div class="d-flex justify-content-center mt-4">
                            {{ $projects->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>




@push('scripts')
<!-- SweetAlert2 JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Projects Calendar JavaScript -->
<script src="{{ asset('js/projects/projects-calendar.js') }}?v={{ time() }}"></script>

<!-- Projects Index Main JavaScript -->
<script src="{{ asset('js/projects/index/projects-index-main.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/projects/index/projects-index-kanban.js') }}?v={{ time() }}"></script>

@endpush

<!-- Include Project Sidebar -->
@include('projects.partials._project_sidebar')

@endsection
