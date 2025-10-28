@extends('layouts.app')

@section('title', 'تسليمات الموظفين')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
<link rel="stylesheet" href="{{ asset('css/employee-deliveries.css') }}">
<style>
    /* Full Width Layout for Employee Deliveries */
    .employee-deliveries-page .container {
        max-width: 100% !important;
        width: 100% !important;
    }

    /* Ensure proper spacing on smaller screens */
    @media (max-width: 1200px) {
        .employee-deliveries-page .simple-container {
            padding: 1.5rem 1rem;
        }
    }

    @media (max-width: 992px) {
        .employee-deliveries-page .simple-container {
            padding: 1rem 0.75rem;
        }
    }
</style>
@endpush

@section('content')
<div class="employee-deliveries-page">
<div class="simple-container">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>📦 تسليمات الموظفين</h1>
            <p>عرض وإدارة جميع التسليمات مع المواعيد النهائية والاعتمادات</p>
        </div>

        <!-- Success/Error Messages -->
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

        <!-- Statistics Row -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-number" id="total-deliveries">{{ $deliveries->count() }}</div>
                <div class="stat-label">إجمالي التسليمات</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="administrative-approved-deliveries">{{ $deliveries->where('administrative_approval', true)->count() }}</div>
                <div class="stat-label">معتمدة إدارياً</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="technical-approved-deliveries">{{ $deliveries->where('technical_approval', true)->count() }}</div>
                <div class="stat-label">معتمدة فنياً</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="overdue-deliveries">{{ $deliveries->filter(function($d) { return $d->deadline && $d->deadline->isPast(); })->count() }}</div>
                <div class="stat-label">متأخرة</div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="filters-section">
            <form id="filtersForm" method="GET">
                <div class="filters-row">
                    <!-- Date Type Filter -->
                    <div class="filter-group">
                        <label for="dateTypeFilter" class="filter-label">
                            <i class="fas fa-calendar"></i>
                            نوع التاريخ
                        </label>
                        <select name="date_type" id="dateTypeFilter" class="filter-select">
                            <option value="team" {{ request('date_type') == 'team' || !request('date_type') ? 'selected' : '' }}>المتفق مع الفريق</option>
                            <option value="client" {{ request('date_type') == 'client' ? 'selected' : '' }}>المتفق مع العميل</option>
                        </select>
                    </div>

                    <!-- Date Filter -->
                    <div class="filter-group">
                        <label for="date_filter" class="filter-label">
                            <i class="fas fa-calendar-alt"></i>
                            فلتر التاريخ
                        </label>
                        <select name="date_filter" id="date_filter" class="filter-select" onchange="toggleCustomDateRange()">
                            <option value="">كل الفترات</option>
                            <option value="current_week" {{ request('date_filter') == 'current_week' ? 'selected' : '' }}>الأسبوع الحالي</option>
                            <option value="last_week" {{ request('date_filter') == 'last_week' ? 'selected' : '' }}>الأسبوع الماضي</option>
                            <option value="current_month" {{ request('date_filter') == 'current_month' ? 'selected' : '' }}>الشهر الحالي</option>
                            <option value="last_month" {{ request('date_filter') == 'last_month' ? 'selected' : '' }}>الشهر الماضي</option>
                            <option value="custom" {{ request('date_filter') == 'custom' ? 'selected' : '' }}>فترة مخصصة</option>
                        </select>
                    </div>

                    <!-- Employee Filter -->
                    <div class="filter-group">
                        <label for="userFilter" class="filter-label">
                            <i class="fas fa-user"></i>
                            الموظف
                        </label>
                        <select name="user_id" id="userFilter" class="filter-select">
                            <option value="">جميع الموظفين</option>
                            @foreach($allowedUsers as $user)
                                <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Project Filter -->
                    <div class="filter-group">
                        <label for="projectFilter" class="filter-label">
                            <i class="fas fa-project-diagram"></i>
                            المشروع
                        </label>
                        <select name="project_id" id="projectFilter" class="filter-select">
                            <option value="">جميع المشاريع</option>
                            @foreach($allowedProjects as $project)
                                <option value="{{ $project->id }}" {{ request('project_id') == $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Service Filter -->
                    <div class="filter-group">
                        <label for="serviceFilter" class="filter-label">
                            <i class="fas fa-cogs"></i>
                            الخدمة
                        </label>
                        <select name="service_id" id="serviceFilter" class="filter-select">
                            <option value="">جميع الخدمات</option>
                            @foreach($allowedServices as $service)
                                <option value="{{ $service->id }}" {{ request('service_id') == $service->id ? 'selected' : '' }}>{{ $service->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Search Button -->
                    <div class="filter-group">
                        <label class="filter-label" style="opacity: 0;">بحث</label>
                        <button type="submit" class="search-btn">
                            <i class="fas fa-filter"></i>
                            فلترة
                        </button>
                    </div>

                    <!-- Clear Filters -->
                    @if(request()->hasAny(['date_type', 'date_filter', 'user_id', 'project_id', 'service_id', 'start_date', 'end_date']))
                        <div class="filter-group">
                            <label class="filter-label" style="opacity: 0;">مسح</label>
                            <button type="button" class="clear-filters-btn" onclick="clearFilters()">
                                <i class="fas fa-times"></i>
                                مسح الفلاتر
                            </button>
                        </div>
                    @endif
                </div>

                <!-- Custom Date Range (Initially Hidden) -->
                <div id="custom-date-range" class="filters-row mt-3" style="display: {{ request('date_filter') == 'custom' ? 'flex' : 'none' }};">
                    <div class="filter-group">
                        <label for="start_date" class="filter-label">
                            <i class="fas fa-calendar-day"></i>
                            من تاريخ
                        </label>
                        <input type="date" name="start_date" id="start_date" class="filter-select" value="{{ request('start_date') }}">
                    </div>
                    <div class="filter-group">
                        <label for="end_date" class="filter-label">
                            <i class="fas fa-calendar-day"></i>
                            إلى تاريخ
                        </label>
                        <input type="date" name="end_date" id="end_date" class="filter-select" value="{{ request('end_date') }}">
                    </div>
                </div>
            </form>
        </div>

        <!-- Tabs Navigation -->
        <div class="projects-table-container">
            <div class="table-header">
                <ul class="nav nav-tabs" id="deliveriesTabs" role="tablist" style="border: none; margin: 0;">
                    @if($userHierarchyLevel >= 3)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $userHierarchyLevel >= 3 ? 'active' : '' }}" id="projects-deadlines-tab" data-bs-toggle="tab" data-bs-target="#projects-deadlines" type="button" role="tab" style="color: white; border: none; padding: 0.75rem 1.5rem; margin: 0 0.25rem; border-radius: 8px; background: rgba(255, 255, 255, 0.2);">
                            <i class="fas fa-project-diagram"></i>
                            المشاريع والديدلاينز
                            <span class="badge bg-light text-dark ms-2">{{ $projects->count() }}</span>
                        </button>
                    </li>
                    @endif
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $userHierarchyLevel < 3 ? 'active' : '' }}" id="my-deliveries-tab" data-bs-toggle="tab" data-bs-target="#my-deliveries" type="button" role="tab" style="color: white; border: none; padding: 0.75rem 1.5rem; margin: 0 0.25rem; border-radius: 8px; background: rgba(255, 255, 255, 0.2);">
                            <i class="fas fa-user-check"></i>
                            تسليماتي الشخصية
                            <span class="badge bg-light text-dark ms-2">{{ $myDeliveries->count() }}</span>
                        </button>
                    </li>
                    @if($showAllDeliveriesTab)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="all-deliveries-tab" data-bs-toggle="tab" data-bs-target="#all-deliveries" type="button" role="tab" style="color: white; border: none; padding: 0.75rem 1.5rem; margin: 0 0.25rem; border-radius: 8px; background: rgba(255, 255, 255, 0.2);">
                            <i class="fas fa-list"></i>
                            جميع التسليمات
                            <span class="badge bg-light text-dark ms-2">{{ $deliveries->count() }}</span>
                        </button>
                    </li>
                    @endif
                </ul>
            </div>

            <div style="padding: 2rem;">
            <div class="tab-content" id="deliveriesTabsContent">
                @if($userHierarchyLevel >= 3)
                <!-- Projects and Deadlines Tab -->
                <div class="tab-pane fade {{ $userHierarchyLevel >= 3 ? 'show active' : '' }}" id="projects-deadlines" role="tabpanel">
                    <!-- Projects Statistics -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="text-xs font-weight-bold text-uppercase mb-1">إجمالي المشاريع</div>
                                <div class="h6 mb-0 font-weight-bold text-gray-800">{{ $projects->count() }}</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">مكتملة</div>
                                <div class="h6 mb-0 font-weight-bold text-gray-800">{{ $projects->where('status', 'مكتمل')->count() }}</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">جارية</div>
                                <div class="h6 mb-0 font-weight-bold text-gray-800">{{ $projects->where('status', 'جاري التنفيذ')->count() }}</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">متأخرة</div>
                                <div class="h6 mb-0 font-weight-bold text-gray-800">{{ $projects->filter(function($p) { return $p->client_agreed_delivery_date && $p->client_agreed_delivery_date->isPast() && $p->status !== 'مكتمل'; })->count() }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="projects-table" id="projectsTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>المشروع</th>
                                    <th>العميل</th>
                                    <th>تاريخ بداية المشروع</th>
                                    <th>المتفق مع العميل</th>
                                    <th>المتفق مع الفريق</th>
                                    <th>الحالة</th>
                                    <th>التقدم</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($projects as $project)
                                    <tr class="project-row">
                                        <td>
                                            <div class="d-flex flex-column">
                                                <strong>
                                                    <a href="{{ route('projects.show', $project->id) }}" class="text-decoration-none text-primary fw-bold" target="_blank">
                                                        {{ $project->name ?? 'غير محدد' }}
                                                        <i class="fas fa-external-link-alt ms-1" style="font-size: 0.8em;"></i>
                                                    </a>
                                                </strong>
                                                @if($project->code)
                                                    <small class="text-muted">#{{ $project->code }}</small>
                                                @endif
                                                @if($project->is_urgent)
                                                    <span class="badge bg-danger">عاجل</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            @if($project->client)
                                                {{ $project->client->name }}
                                            @else
                                                <span class="text-muted">غير محدد</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($project->start_date)
                                                {{ $project->start_date->format('Y-m-d') }}
                                            @else
                                                <span class="text-muted">غير محدد</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($project->client_agreed_delivery_date)
                                                <span class="text-success">
                                                    <i class="fas fa-calendar-check"></i>
                                                    {{ $project->client_agreed_delivery_date->format('Y-m-d') }}
                                                </span>
                                                @if($project->client_agreed_delivery_date->isPast() && $project->status !== 'مكتمل')
                                                    <br><small class="text-danger">متأخر</small>
                                                @endif
                                            @else
                                                <span class="text-muted">غير محدد</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($project->team_delivery_date)
                                                <span class="text-warning">
                                                    <i class="fas fa-users"></i>
                                                    {{ $project->team_delivery_date->format('Y-m-d') }}
                                                </span>
                                            @else
                                                <span class="text-muted">غير محدد</span>
                                            @endif
                                        </td>
                                        <td>
                                            @switch($project->status)
                                                @case('جديد')
                                                    <span class="badge bg-secondary">{{ $project->status }}</span>
                                                    @break
                                                @case('جاري التنفيذ')
                                                    <span class="badge bg-primary">{{ $project->status }}</span>
                                                    @break
                                                @case('مكتمل')
                                                    <span class="badge bg-success">{{ $project->status }}</span>
                                                    @break
                                                @case('ملغي')
                                                    <span class="badge bg-danger">{{ $project->status }}</span>
                                                    @break
                                                @default
                                                    <span class="badge bg-light text-dark">{{ $project->status }}</span>
                                            @endswitch
                                        </td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar" role="progressbar"
                                                     style="width: {{ $project->completion_percentage ?? 0 }}%"
                                                     aria-valuenow="{{ $project->completion_percentage ?? 0 }}"
                                                     aria-valuemin="0" aria-valuemax="100">
                                                    {{ $project->completion_percentage ?? 0 }}%
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
                                                <button type="button" class="services-btn"
                                                        style="background: linear-gradient(135deg, #3b82f6, #2563eb); color: white;"
                                                        onclick="viewProjectDeliveries({{ $project->id }})"
                                                        title="عرض التسليمات">
                                                    <i class="fas fa-eye"></i>
                                                    عرض التسليمات
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-project-diagram fa-3x mb-3"></i>
                                                <p>لا توجد مشاريع متاحة</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                <!-- My Deliveries Tab -->
                <div class="tab-pane fade {{ $userHierarchyLevel < 3 ? 'show active' : '' }}" id="my-deliveries" role="tabpanel">
                    <!-- My Deliveries Statistics -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="text-center">
                                <div class="text-xs font-weight-bold text-uppercase mb-1">الإجمالي</div>
                                <div class="h6 mb-0 font-weight-bold text-gray-800">{{ $myStats['total'] }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-center">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">متأخرة</div>
                                <div class="h6 mb-0 font-weight-bold text-gray-800">{{ $myStats['overdue'] }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="projects-table" id="myDeliveriesTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>المشروع</th>
                                    <th>الخدمة</th>
                                    @if($showProjectDates)
                                        <th>تاريخ بداية المشروع</th>
                                        <th>تاريخ التسليم للعميل</th>
                                    @endif
                                    <th>موعد التسليم</th>
                                    <th>الاعتماد الإداري</th>
                                    <th>الاعتماد الفني</th>
                                    <th>الموعد النهائي</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($myDeliveries as $delivery)
                                    <tr class="delivery-row">
                                        <td>
                                            <div class="d-flex flex-column">
                                                <strong>
                                                    @if($delivery->project)
                                                        <a href="{{ route('projects.show', $delivery->project->id) }}" class="text-decoration-none text-primary fw-bold" target="_blank">
                                                            {{ $delivery->project->name ?? 'غير محدد' }}
                                                            <i class="fas fa-external-link-alt ms-1" style="font-size: 0.8em;"></i>
                                                        </a>
                                                    @else
                                                        غير محدد
                                                    @endif
                                                </strong>
                                                @if($delivery->project && $delivery->project->code)
                                                    <small class="text-muted">#{{ $delivery->project->code }}</small>
                                                @endif
                                                @if($delivery->project && $delivery->project->client)
                                                    <small class="text-info">{{ $delivery->project->client->name }}</small>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span>{{ $delivery->service->name ?? 'غير محدد' }}</span>
                                                @if($delivery->service && $delivery->service->department)
                                                    <small class="text-muted">{{ $delivery->service->department }}</small>
                                                @endif
                                            </div>
                                        </td>
                                        @if($showProjectDates)
                                            <td>
                                                @if($delivery->project && $delivery->project->start_date)
                                                    {{ $delivery->project->start_date->format('Y-m-d') }}
                                                @else
                                                    <span class="text-muted">غير محدد</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($delivery->project && $delivery->project->end_date)
                                                    {{ $delivery->project->end_date->format('Y-m-d') }}
                                                @else
                                                    <span class="text-muted">غير محدد</span>
                                                @endif
                                            </td>
                                        @endif
                                        <td>
                                            @if($delivery->delivered_at)
                                                <div class="d-flex flex-column">
                                                    <span class="badge bg-primary">
                                                        <i class="fas fa-calendar-check"></i>
                                                        {{ $delivery->delivered_at->format('Y-m-d') }}
                                                    </span>
                                                    <small class="text-muted mt-1">
                                                        {{ $delivery->delivered_at->format('H:i') }}
                                                    </small>
                                                </div>
                                            @else
                                                <span class="badge bg-secondary">
                                                    <i class="fas fa-clock"></i> لم يسلم بعد
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($delivery->hasAdministrativeApproval())
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check-circle"></i> معتمد
                                                </span>
                                                @if($delivery->administrative_approval_at)
                                                    <br><small class="text-muted">{{ $delivery->administrative_approval_at->format('Y-m-d H:i') }}</small>
                                                @endif
                                                @if($delivery->administrativeApprover)
                                                    <br><small class="text-info">{{ $delivery->administrativeApprover->name }}</small>
                                                @endif
                                            @elseif(isset($delivery->required_approvals) && $delivery->required_approvals['needs_administrative'])
                                                <span class="badge bg-warning text-dark">
                                                    <i class="fas fa-hourglass-half"></i> في الانتظار
                                                </span>
                                            @else
                                                <span class="text-muted">غير مطلوب</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($delivery->hasTechnicalApproval())
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check-circle"></i> معتمد
                                                </span>
                                                @if($delivery->technical_approval_at)
                                                    <br><small class="text-muted">{{ $delivery->technical_approval_at->format('Y-m-d H:i') }}</small>
                                                @endif
                                                @if($delivery->technicalApprover)
                                                    <br><small class="text-info">{{ $delivery->technicalApprover->name }}</small>
                                                @endif
                                            @elseif(isset($delivery->required_approvals) && $delivery->required_approvals['needs_technical'])
                                                <span class="badge bg-warning text-dark">
                                                    <i class="fas fa-hourglass-half"></i> في الانتظار
                                                </span>
                                            @else
                                                <span class="text-muted">غير مطلوب</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($delivery->deadline)
                                                {{ $delivery->deadline->format('Y-m-d') }}
                                                @if($delivery->delivered_at)
                                                    @php
                                                        $deliveredDate = $delivery->delivered_at->startOfDay();
                                                        $deadlineDate = $delivery->deadline->startOfDay();
                                                        $diffDays = $deliveredDate->diffInDays($deadlineDate, false);
                                                    @endphp
                                                    <br>
                                                    <small class="@if($diffDays >= 0) text-success @else text-danger @endif">
                                                        @if($diffDays > 0)
                                                            <i class="fas fa-check-circle"></i> سُلّم قبل الموعد بـ {{ abs($diffDays) }} يوم
                                                        @elseif($diffDays < 0)
                                                            <i class="fas fa-exclamation-triangle"></i> سُلّم متأخر {{ abs($diffDays) }} يوم
                                                        @else
                                                            <i class="fas fa-check"></i> سُلّم في الموعد
                                                        @endif
                                                    </small>
                                                @elseif($delivery->days_remaining !== null)
                                                    <br>
                                                    <small class="text-muted">
                                                        @if($delivery->days_remaining > 0)
                                                            باقي {{ $delivery->days_remaining }} يوم
                                                        @elseif($delivery->days_remaining < 0)
                                                            متأخر {{ abs($delivery->days_remaining) }} يوم
                                                        @else
                                                            اليوم
                                                        @endif
                                                    </small>
                                                @endif
                                            @else
                                                <span class="text-muted">غير محدد</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
                                                {{-- أزرار الاعتماد الإداري --}}
                                                @if(isset($delivery->can_approve_administrative) && $delivery->can_approve_administrative && !$delivery->hasAdministrativeApproval())
                                                    <button type="button" class="services-btn"
                                                            style="background: linear-gradient(135deg, #3b82f6, #2563eb); color: white;"
                                                            onclick="grantAdministrativeApproval({{ $delivery->id }})"
                                                            title="اعتماد إداري">
                                                        <i class="fas fa-user-check"></i>
                                                        اعتماد إداري
                                                    </button>
                                                @endif

                                                @if(isset($delivery->can_approve_administrative) && $delivery->can_approve_administrative && $delivery->hasAdministrativeApproval())
                                                    <button type="button" class="services-btn"
                                                            style="background: linear-gradient(135deg, #f59e0b, #d97706); color: white;"
                                                            onclick="revokeAdministrativeApproval({{ $delivery->id }})"
                                                            title="إلغاء اعتماد إداري">
                                                        <i class="fas fa-user-times"></i>
                                                        إلغاء اعتماد إداري
                                                    </button>
                                                @endif

                                                {{-- أزرار الاعتماد الفني --}}
                                                @if(isset($delivery->can_approve_technical) && $delivery->can_approve_technical && !$delivery->hasTechnicalApproval())
                                                    <button type="button" class="services-btn"
                                                            style="background: linear-gradient(135deg, #10b981, #059669); color: white;"
                                                            onclick="grantTechnicalApproval({{ $delivery->id }})"
                                                            title="اعتماد فني">
                                                        <i class="fas fa-cogs"></i>
                                                        اعتماد فني
                                                    </button>
                                                @endif

                                                @if(isset($delivery->can_approve_technical) && $delivery->can_approve_technical && $delivery->hasTechnicalApproval())
                                                    <button type="button" class="services-btn"
                                                            style="background: linear-gradient(135deg, #ef4444, #dc2626); color: white;"
                                                            onclick="revokeTechnicalApproval({{ $delivery->id }})"
                                                            title="إلغاء اعتماد فني">
                                                        <i class="fas fa-times-circle"></i>
                                                        إلغاء اعتماد فني
                                                    </button>
                                                @endif

                                                <button type="button" class="services-btn"
                                                        style="background: linear-gradient(135deg, #6366f1, #4f46e5); color: white;"
                                                        onclick="viewDeliveryDetails({{ $delivery->id }})"
                                                        title="عرض التفاصيل">
                                                    <i class="fas fa-eye"></i>
                                                    عرض
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $showProjectDates ? 10 : 8 }}" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                                <p>لا توجد تسليمات شخصية متاحة</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if($showAllDeliveriesTab)
                <!-- All Deliveries Tab -->
                <div class="tab-pane fade" id="all-deliveries" role="tabpanel">
                    <!-- All Deliveries Statistics -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="text-xs font-weight-bold text-uppercase mb-1">الإجمالي</div>
                                <div class="h6 mb-0 font-weight-bold text-gray-800">{{ $stats['total'] }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-center">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">متأخرة</div>
                                <div class="h6 mb-0 font-weight-bold text-gray-800">{{ $stats['overdue'] }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="projects-table" id="deliveriesTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>المشروع</th>
                                    <th>الخدمة</th>
                                    <th>الموظف</th>
                                    <th>القسم</th>
                                    <th>الفريق</th>
                                    <th>موعد التسليم</th>
                                    <th>الاعتماد الإداري</th>
                                    <th>الاعتماد الفني</th>
                                    <th>الموعد النهائي</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($deliveries as $delivery)
                                    <tr class="delivery-row">
                                        <td>
                                            <div class="d-flex flex-column">
                                                <strong>
                                                    @if($delivery->project)
                                                        <a href="{{ route('projects.show', $delivery->project->id) }}" class="text-decoration-none text-primary fw-bold" target="_blank">
                                                            {{ $delivery->project->name ?? 'غير محدد' }}
                                                            <i class="fas fa-external-link-alt ms-1" style="font-size: 0.8em;"></i>
                                                        </a>
                                                    @else
                                                        غير محدد
                                                    @endif
                                                </strong>
                                                @if($delivery->project && $delivery->project->code)
                                                    <small class="text-muted">#{{ $delivery->project->code }}</small>
                                                @endif
                                                @if($delivery->project && $delivery->project->client)
                                                    <small class="text-info">{{ $delivery->project->client->name }}</small>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span>{{ $delivery->service->name ?? 'غير محدد' }}</span>
                                                @if($delivery->service && $delivery->service->department)
                                                    <small class="text-muted">{{ $delivery->service->department }}</small>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <strong>{{ $delivery->user->name ?? 'غير محدد' }}</strong>
                                                @if(isset($delivery->user->hierarchy_title))
                                                    <span class="hierarchy-badge">{{ $delivery->user->hierarchy_title }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>{{ $delivery->user->department ?? 'غير محدد' }}</td>
                                        <td>
                                            @if($delivery->team)
                                                {{ $delivery->team->name }}
                                            @elseif(isset($delivery->user->team_info['name']))
                                                {{ $delivery->user->team_info['name'] }}
                                            @else
                                                غير محدد
                                            @endif
                                        </td>
                                        <td>
                                            @if($delivery->delivered_at)
                                                <div class="d-flex flex-column">
                                                    <span class="badge bg-primary">
                                                        <i class="fas fa-calendar-check"></i>
                                                        {{ $delivery->delivered_at->format('Y-m-d') }}
                                                    </span>
                                                    <small class="text-muted mt-1">
                                                        {{ $delivery->delivered_at->format('H:i') }}
                                                    </small>
                                                </div>
                                            @else
                                                <span class="badge bg-secondary">
                                                    <i class="fas fa-clock"></i> لم يسلم بعد
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($delivery->hasAdministrativeApproval())
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check-circle"></i> معتمد
                                                </span>
                                                @if($delivery->administrative_approval_at)
                                                    <br><small class="text-muted">{{ $delivery->administrative_approval_at->format('Y-m-d H:i') }}</small>
                                                @endif
                                                @if($delivery->administrativeApprover)
                                                    <br><small class="text-info">{{ $delivery->administrativeApprover->name }}</small>
                                                @endif
                                            @elseif(isset($delivery->required_approvals) && $delivery->required_approvals['needs_administrative'])
                                                <span class="badge bg-warning text-dark">
                                                    <i class="fas fa-hourglass-half"></i> في الانتظار
                                                </span>
                                            @else
                                                <span class="text-muted">غير مطلوب</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($delivery->hasTechnicalApproval())
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check-circle"></i> معتمد
                                                </span>
                                                @if($delivery->technical_approval_at)
                                                    <br><small class="text-muted">{{ $delivery->technical_approval_at->format('Y-m-d H:i') }}</small>
                                                @endif
                                                @if($delivery->technicalApprover)
                                                    <br><small class="text-info">{{ $delivery->technicalApprover->name }}</small>
                                                @endif
                                            @elseif(isset($delivery->required_approvals) && $delivery->required_approvals['needs_technical'])
                                                <span class="badge bg-warning text-dark">
                                                    <i class="fas fa-hourglass-half"></i> في الانتظار
                                                </span>
                                            @else
                                                <span class="text-muted">غير مطلوب</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($delivery->deadline)
                                                {{ $delivery->deadline->format('Y-m-d') }}
                                                @if($delivery->delivered_at)
                                                    @php
                                                        $deliveredDate = $delivery->delivered_at->startOfDay();
                                                        $deadlineDate = $delivery->deadline->startOfDay();
                                                        $diffDays = $deliveredDate->diffInDays($deadlineDate, false);
                                                    @endphp
                                                    <br>
                                                    <small class="@if($diffDays >= 0) text-success @else text-danger @endif">
                                                        @if($diffDays > 0)
                                                            <i class="fas fa-check-circle"></i> سُلّم قبل الموعد بـ {{ abs($diffDays) }} يوم
                                                        @elseif($diffDays < 0)
                                                            <i class="fas fa-exclamation-triangle"></i> سُلّم متأخر {{ abs($diffDays) }} يوم
                                                        @else
                                                            <i class="fas fa-check"></i> سُلّم في الموعد
                                                        @endif
                                                    </small>
                                                @elseif($delivery->days_remaining !== null)
                                                    <br>
                                                    <small class="text-muted">
                                                        @if($delivery->days_remaining > 0)
                                                            باقي {{ $delivery->days_remaining }} يوم
                                                        @elseif($delivery->days_remaining < 0)
                                                            متأخر {{ abs($delivery->days_remaining) }} يوم
                                                        @else
                                                            اليوم
                                                        @endif
                                                    </small>
                                                @endif
                                            @else
                                                <span class="text-muted">غير محدد</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
                                                {{-- أزرار الاعتماد الإداري --}}
                                                @if(isset($delivery->can_approve_administrative) && $delivery->can_approve_administrative && !$delivery->hasAdministrativeApproval())
                                                    <button type="button" class="services-btn"
                                                            style="background: linear-gradient(135deg, #3b82f6, #2563eb); color: white;"
                                                            onclick="grantAdministrativeApproval({{ $delivery->id }})"
                                                            title="اعتماد إداري">
                                                        <i class="fas fa-user-check"></i>
                                                        اعتماد إداري
                                                    </button>
                                                @endif

                                                @if(isset($delivery->can_approve_administrative) && $delivery->can_approve_administrative && $delivery->hasAdministrativeApproval())
                                                    <button type="button" class="services-btn"
                                                            style="background: linear-gradient(135deg, #f59e0b, #d97706); color: white;"
                                                            onclick="revokeAdministrativeApproval({{ $delivery->id }})"
                                                            title="إلغاء اعتماد إداري">
                                                        <i class="fas fa-user-times"></i>
                                                        إلغاء اعتماد إداري
                                                    </button>
                                                @endif

                                                {{-- أزرار الاعتماد الفني --}}
                                                @if(isset($delivery->can_approve_technical) && $delivery->can_approve_technical && !$delivery->hasTechnicalApproval())
                                                    <button type="button" class="services-btn"
                                                            style="background: linear-gradient(135deg, #10b981, #059669); color: white;"
                                                            onclick="grantTechnicalApproval({{ $delivery->id }})"
                                                            title="اعتماد فني">
                                                        <i class="fas fa-cogs"></i>
                                                        اعتماد فني
                                                    </button>
                                                @endif

                                                @if(isset($delivery->can_approve_technical) && $delivery->can_approve_technical && $delivery->hasTechnicalApproval())
                                                    <button type="button" class="services-btn"
                                                            style="background: linear-gradient(135deg, #ef4444, #dc2626); color: white;"
                                                            onclick="revokeTechnicalApproval({{ $delivery->id }})"
                                                            title="إلغاء اعتماد فني">
                                                        <i class="fas fa-times-circle"></i>
                                                        إلغاء اعتماد فني
                                                    </button>
                                                @endif

                                                <button type="button" class="services-btn"
                                                        style="background: linear-gradient(135deg, #6366f1, #4f46e5); color: white;"
                                                        onclick="viewDeliveryDetails({{ $delivery->id }})"
                                                        title="عرض التفاصيل">
                                                    <i class="fas fa-eye"></i>
                                                    عرض
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                                <p>لا توجد تسليمات متاحة</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
</div>
</div>

<!-- Delivery Details Modal -->
<div class="modal fade" id="deliveryDetailsModal" tabindex="-1" aria-labelledby="deliveryDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deliveryDetailsModalLabel">
                    <i class="fas fa-info-circle"></i>
                    تفاصيل التسليمة
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="deliveryDetailsContent">
                <!-- سيتم تحميل المحتوى ديناميكياً -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

<!-- Employee Deliveries Module Files -->
<script src="{{ asset('js/employee-deliveries/core.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/employee-deliveries/tables.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/employee-deliveries/actions.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/employee-deliveries.js') }}?v={{ time() }}"></script>

<style>
    /* Tab Styles */
    #deliveriesTabs .nav-link {
        transition: all 0.3s ease;
    }

    #deliveriesTabs .nav-link:hover {
        background: rgba(255, 255, 255, 0.3) !important;
        transform: translateY(-2px);
    }

    #deliveriesTabs .nav-link.active {
        background: rgba(255, 255, 255, 0.95) !important;
        color: #4f46e5 !important;
        font-weight: 600;
    }

    #deliveriesTabs .nav-link.active .badge {
        background: #4f46e5 !important;
        color: white !important;
    }

    /* Custom Date Range Toggle */
    .filter-select[type="date"] {
        cursor: pointer;
    }

    /* Empty State Styling */
    .text-muted {
        color: #6b7280 !important;
    }

    .empty-state {
        text-align: center;
        padding: 3rem 1rem !important;
        color: #6b7280;
    }

    .empty-state i {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }

    /* Services Button Styles from Projects Page */
    .services-btn {
        padding: 10px 20px;
        border: none;
        border-radius: 8px;
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        text-decoration: none;
        white-space: nowrap;
    }

    .services-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .services-btn i {
        font-size: 0.9rem;
    }
</style>

<script>
// Toggle custom date range visibility
function toggleCustomDateRange() {
    const dateFilter = document.getElementById('date_filter');
    const customDateRange = document.getElementById('custom-date-range');

    if (dateFilter.value === 'custom') {
        customDateRange.style.display = 'flex';
    } else {
        customDateRange.style.display = 'none';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleCustomDateRange();
});

// حفظ واستعادة التاب النشط
$(document).ready(function() {
    // استعادة التاب المحفوظ عند تحميل الصفحة
    const savedTab = localStorage.getItem('deliveries_active_tab');
    if (savedTab) {
        const tabButton = document.querySelector(`button[data-bs-target="${savedTab}"]`);
        if (tabButton) {
            const tab = new bootstrap.Tab(tabButton);
            tab.show();
        }
    }

    // حفظ التاب النشط عند التبديل
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        const targetTab = $(e.target).attr('data-bs-target');
        localStorage.setItem('deliveries_active_tab', targetTab);
    });
});
</script>
@endpush
