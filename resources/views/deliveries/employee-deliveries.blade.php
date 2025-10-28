@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
<link rel="stylesheet" href="{{ asset('css/employee-deliveries.css') }}">
@endpush

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-truck-loading text-primary"></i>
            تسليمات الموظفين
        </h1>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-success btn-sm" onclick="refreshData()">
                <i class="fas fa-sync-alt"></i> تحديث
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                إجمالي التسليمات
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="total-deliveries">
                                {{ $deliveries->count() }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                معتمدة إدارياً
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="administrative-approved-deliveries">
                                {{ $deliveries->where('administrative_approval', true)->count() }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                معتمدة فنياً
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="technical-approved-deliveries">
                                {{ $deliveries->where('technical_approval', true)->count() }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-cogs fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                متأخرة
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="overdue-deliveries">
                                {{ $deliveries->filter(function($d) { return $d->deadline && $d->deadline->isPast(); })->count() }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card filters-card">
        <form id="filtersForm" method="GET">
            <div class="row">
                <div class="col-md-2">
                    <label class="form-label">نوع التاريخ</label>
                    <select name="date_type" class="form-select">
                        <option value="team" {{ request('date_type') == 'team' || !request('date_type') ? 'selected' : '' }}>
                            <i class="fas fa-users"></i> المتفق مع الفريق
                        </option>
                        <option value="client" {{ request('date_type') == 'client' ? 'selected' : '' }}>
                            <i class="fas fa-user-tie"></i> المتفق مع العميل
                        </option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">فلتر التاريخ</label>
                    <select name="date_filter" id="date_filter" class="form-select">
                        <option value="">كل الفترات</option>
                        <option value="current_week" {{ request('date_filter') == 'current_week' ? 'selected' : '' }}>
                            الأسبوع الحالي
                        </option>
                        <option value="last_week" {{ request('date_filter') == 'last_week' ? 'selected' : '' }}>
                            الأسبوع الماضي
                        </option>
                        <option value="current_month" {{ request('date_filter') == 'current_month' ? 'selected' : '' }}>
                            الشهر الحالي
                        </option>
                        <option value="last_month" {{ request('date_filter') == 'last_month' ? 'selected' : '' }}>
                            الشهر الماضي
                        </option>
                        <option value="custom" {{ request('date_filter') == 'custom' ? 'selected' : '' }}>
                            فترة مخصصة
                        </option>
                    </select>
                </div>

                <div class="col-md-3" id="custom-date-range" style="display: none;">
                    <label class="form-label">من - إلى</label>
                    <div class="d-flex gap-2">
                        <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                        <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                    </div>
                </div>

                <div class="col-md-2">
                    <label class="form-label">الموظف</label>
                    <select name="user_id" class="form-select">
                        <option value="">جميع الموظفين</option>
                        @foreach($allowedUsers as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">المشروع</label>
                    <select name="project_id" class="form-select">
                        <option value="">جميع المشاريع</option>
                        @foreach($allowedProjects as $project)
                            <option value="{{ $project->id }}" {{ request('project_id') == $project->id ? 'selected' : '' }}>
                                {{ $project->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

            </div>

            <div class="row mt-3">
                <div class="col-md-3">
                    <label class="form-label">الخدمة</label>
                    <select name="service_id" class="form-select">
                        <option value="">جميع الخدمات</option>
                        @foreach($allowedServices as $service)
                            <option value="{{ $service->id }}" {{ request('service_id') == $service->id ? 'selected' : '' }}>
                                {{ $service->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> فلترة
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="clearFilters()">
                            <i class="fas fa-times"></i> مسح الفلاتر
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Tabs Navigation -->
    <div class="card shadow">
        <div class="card-header py-3">
            <ul class="nav nav-tabs card-header-tabs" id="deliveriesTabs" role="tablist">
                @if($userHierarchyLevel >= 3)
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $userHierarchyLevel >= 3 ? 'active' : '' }}" id="projects-deadlines-tab" data-bs-toggle="tab" data-bs-target="#projects-deadlines" type="button" role="tab">
                        <i class="fas fa-project-diagram"></i>
                        المشاريع والديدلاينز
                        <span class="badge badge-info ms-2">{{ $projects->count() }}</span>
                    </button>
                </li>
                @endif
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $userHierarchyLevel < 3 ? 'active' : '' }}" id="my-deliveries-tab" data-bs-toggle="tab" data-bs-target="#my-deliveries" type="button" role="tab">
                        <i class="fas fa-user-check"></i>
                        تسليماتي الشخصية
                        <span class="badge badge-success ms-2">{{ $myDeliveries->count() }}</span>
                    </button>
                </li>
                @if($showAllDeliveriesTab)
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="all-deliveries-tab" data-bs-toggle="tab" data-bs-target="#all-deliveries" type="button" role="tab">
                        <i class="fas fa-list"></i>
                        جميع التسليمات
                        <span class="badge badge-primary ms-2">{{ $deliveries->count() }}</span>
                    </button>
                </li>
                @endif
            </ul>
        </div>

        <div class="card-body">
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
                        <table class="table table-bordered table-hover" id="projectsTable" width="100%" cellspacing="0">
                            <thead class="table-info">
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
                                            <div class="d-flex flex-wrap gap-1">
                                                <button type="button" class="btn btn-primary btn-action"
                                                        onclick="viewProjectDeliveries({{ $project->id }})">
                                                    <i class="fas fa-eye"></i> عرض التسليمات
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
                        <table class="table table-bordered table-hover" id="myDeliveriesTable" width="100%" cellspacing="0">
                            <thead class="table-success">
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
                                            <div class="d-flex flex-wrap gap-1">
                                                {{-- أزرار الاعتماد الإداري --}}
                                                @if(isset($delivery->can_approve_administrative) && $delivery->can_approve_administrative && !$delivery->hasAdministrativeApproval())
                                                    <button type="button" class="btn btn-outline-primary btn-action"
                                                            onclick="grantAdministrativeApproval({{ $delivery->id }})">
                                                        <i class="fas fa-user-check"></i> اعتماد إداري
                                                    </button>
                                                @endif

                                                @if(isset($delivery->can_approve_administrative) && $delivery->can_approve_administrative && $delivery->hasAdministrativeApproval())
                                                    <button type="button" class="btn btn-outline-warning btn-action"
                                                            onclick="revokeAdministrativeApproval({{ $delivery->id }})">
                                                        <i class="fas fa-user-times"></i> إلغاء اعتماد إداري
                                                    </button>
                                                @endif

                                                {{-- أزرار الاعتماد الفني --}}
                                                @if(isset($delivery->can_approve_technical) && $delivery->can_approve_technical && !$delivery->hasTechnicalApproval())
                                                    <button type="button" class="btn btn-outline-success btn-action"
                                                            onclick="grantTechnicalApproval({{ $delivery->id }})">
                                                        <i class="fas fa-cogs"></i> اعتماد فني
                                                    </button>
                                                @endif

                                                @if(isset($delivery->can_approve_technical) && $delivery->can_approve_technical && $delivery->hasTechnicalApproval())
                                                    <button type="button" class="btn btn-outline-danger btn-action"
                                                            onclick="revokeTechnicalApproval({{ $delivery->id }})">
                                                        <i class="fas fa-times-circle"></i> إلغاء اعتماد فني
                                                    </button>
                                                @endif

                                                <button type="button" class="btn btn-info btn-action"
                                                        onclick="viewDeliveryDetails({{ $delivery->id }})">
                                                    <i class="fas fa-eye"></i> عرض
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
                        <table class="table table-bordered table-hover" id="deliveriesTable" width="100%" cellspacing="0">
                            <thead class="table-dark">
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
                                            <div class="d-flex flex-wrap gap-1">
                                                {{-- أزرار الاعتماد الإداري --}}
                                                @if(isset($delivery->can_approve_administrative) && $delivery->can_approve_administrative && !$delivery->hasAdministrativeApproval())
                                                    <button type="button" class="btn btn-outline-primary btn-action"
                                                            onclick="grantAdministrativeApproval({{ $delivery->id }})">
                                                        <i class="fas fa-user-check"></i> اعتماد إداري
                                                    </button>
                                                @endif

                                                @if(isset($delivery->can_approve_administrative) && $delivery->can_approve_administrative && $delivery->hasAdministrativeApproval())
                                                    <button type="button" class="btn btn-outline-warning btn-action"
                                                            onclick="revokeAdministrativeApproval({{ $delivery->id }})">
                                                        <i class="fas fa-user-times"></i> إلغاء اعتماد إداري
                                                    </button>
                                                @endif

                                                {{-- أزرار الاعتماد الفني --}}
                                                @if(isset($delivery->can_approve_technical) && $delivery->can_approve_technical && !$delivery->hasTechnicalApproval())
                                                    <button type="button" class="btn btn-outline-success btn-action"
                                                            onclick="grantTechnicalApproval({{ $delivery->id }})">
                                                        <i class="fas fa-cogs"></i> اعتماد فني
                                                    </button>
                                                @endif

                                                @if(isset($delivery->can_approve_technical) && $delivery->can_approve_technical && $delivery->hasTechnicalApproval())
                                                    <button type="button" class="btn btn-outline-danger btn-action"
                                                            onclick="revokeTechnicalApproval({{ $delivery->id }})">
                                                        <i class="fas fa-times-circle"></i> إلغاء اعتماد فني
                                                    </button>
                                                @endif

                                                <button type="button" class="btn btn-info btn-action"
                                                        onclick="viewDeliveryDetails({{ $delivery->id }})">
                                                    <i class="fas fa-eye"></i> عرض
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

<script>
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
