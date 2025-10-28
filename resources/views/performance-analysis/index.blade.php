@extends('layouts.app')



@push('styles')
<link rel="stylesheet" href="{{ asset('css/performance-analysis.css') }}?v={{ time() }}">
@endpush

@section('content')
<div class="full-width-content">
    <!-- Header -->
    <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-1">
                    <i class="fas fa-chart-line me-2"></i>
                    تحليل الأداء للمراجع العام
                </h2>
                <p class="mb-0" style="opacity: 0.9;">عرض شامل لجميع المشاريع وخدماتها وبياناتها</p>
            </div>
            <div style="font-size: 3rem; opacity: 0.2;">
                <i class="fas fa-chart-bar"></i>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filter-section">
                    <form method="GET" action="{{ route('performance-analysis.index') }}" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">البحث بكود المشروع</label>
                            <div class="input-group">
                                <input type="text"
                                    class="form-control"
                                    name="project_code"
                                    value="{{ request('project_code') }}"
                                    placeholder="أدخل كود المشروع">
                                <button class="btn btn-outline-primary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">من تاريخ</label>
                            <input type="date"
                                class="form-control"
                                name="date_from"
                                value="{{ request('date_from') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">إلى تاريخ</label>
                            <input type="date"
                                class="form-control"
                                name="date_to"
                                value="{{ request('date_to') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">المراجع</label>
                            <select class="form-select" name="reviewer_id">
                                <option value="">جميع المراجعين</option>
                                @foreach($reviewers as $reviewer)
                                <option value="{{ $reviewer->id }}" {{ request('reviewer_id') == $reviewer->id ? 'selected' : '' }}>
                                    {{ $reviewer->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">الخدمة</label>
                            <select class="form-select" name="service_id">
                                <option value="">جميع الخدمات</option>
                                @foreach($services as $service)
                                <option value="{{ $service->id }}" {{ request('service_id') == $service->id ? 'selected' : '' }}>
                                    {{ $service->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">الحالة</label>
                            <select class="form-select" name="status">
                                <option value="">جميع الحالات</option>
                                @foreach($statuses as $status)
                                <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                                    {{ $status }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">الشهر والسنة</label>
                            <input type="month"
                                class="form-control"
                                name="month_year"
                                value="{{ request('month_year') ?: date('Y-m') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-action">
                                    <i class="fas fa-filter me-2"></i>
                                    تطبيق الفلاتر
                                </button>
                                <a href="{{ route('performance-analysis.index') }}" class="btn btn-secondary btn-action">
                                    <i class="fas fa-times me-2"></i>
                                    مسح
                                </a>
                            </div>
                        </div>
                    </form>
    </div>

    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stats-card bg-primary bg-opacity-10" style="border: 2px solid #667eea;">
                <div class="text-center">
                    <i class="fas fa-project-diagram text-primary"></i>
                    <h4 class="text-primary">{{ $projects->count() }}</h4>
                    <p class="text-muted">إجمالي المشاريع</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card bg-success bg-opacity-10" style="border: 2px solid #28a745;">
                <div class="text-center">
                    <i class="fas fa-check-circle text-success"></i>
                    <h4 class="text-success">{{ $projects->where('status', 'مكتمل')->count() }}</h4>
                    <p class="text-muted">مشاريع مكتملة</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card bg-warning bg-opacity-10" style="border: 2px solid #ffc107;">
                <div class="text-center">
                    <i class="fas fa-clock text-warning"></i>
                    <h4 class="text-warning">{{ $projects->where('status', 'قيد التنفيذ')->count() }}</h4>
                    <p class="text-muted">قيد التنفيذ</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card bg-info bg-opacity-10" style="border: 2px solid #17a2b8;">
                <div class="text-center">
                    <i class="fas fa-plus-circle text-info"></i>
                    <h4 class="text-info">{{ $projects->where('status', 'جديد')->count() }}</h4>
                    <p class="text-muted">مشاريع جديدة</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Projects List -->
    <div class="card-modern">
        <div class="card-body p-4">
            <h5 class="mb-4">
                <i class="fas fa-list me-2 text-primary"></i>
                قائمة المشاريع والخدمات
            </h5>
            @if($projects->count() > 0)
            <div class="table-responsive">
                <table class="table table-modern">
                    <thead>
                                <tr>
                                    <th>كود المشروع</th>
                                    <th>اسم المشروع</th>
                                    <th>المراجع</th>
                                    <th>الحالة</th>
                                    <th>تاريخ الإنشاء</th>
                                    <th>الخدمات</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($projects as $project)
                                <tr>
                                    <td class="text-center">
                                        <span class="badge badge-modern bg-primary">{{ $project->code ?? 'غير محدد' }}</span>
                                    </td>
                                    <td>
                                        <strong style="color: #2d3748;">{{ $project->name }}</strong>
                                        @if($project->is_urgent)
                                        <span class="badge badge-modern bg-danger ms-2">عاجل</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                        // جلب المراجعين (الذين لديهم أدوار بـ hierarchy_level = 2)
                                        $projectReviewers = $project->serviceParticipants
                                        ->whereIn('role_id', $reviewerRoleIds)
                                        ->where('user', '!=', null);
                                        @endphp
                                        @if($projectReviewers->count() > 0)
                                        @foreach($projectReviewers as $participant)
                                        <span class="reviewer-badge">{{ $participant->user->name }}</span>
                                        @endforeach
                                        @else
                                        <span class="text-muted">غير محدد</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @php
                                        $statusColors = [
                                        'جديد' => 'success',
                                        'قيد التنفيذ' => 'warning',
                                        'مكتمل' => 'primary',
                                        'معلق' => 'secondary',
                                        'ملغي' => 'danger'
                                        ];
                                        $color = $statusColors[$project->status] ?? 'secondary';
                                        @endphp
                                        <span class="badge badge-modern bg-{{ $color }}">{{ $project->status }}</span>
                                    </td>
                                    <td>{{ $project->created_at->format('Y-m-d') }}</td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach($project->services as $service)
                                            <span class="service-badge">{{ $service->name }}</span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-2">
                                            @php
                                                $userMaxHierarchy = \App\Models\RoleHierarchy::getUserMaxHierarchyLevel(Auth::user());
                                            @endphp
                                            @if($userMaxHierarchy && $userMaxHierarchy >= 4)
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-secondary"
                                                    onclick="openProjectSidebar({{ $project->id }})"
                                                    title="التفاصيل الكاملة">
                                                    <i class="fas fa-info-circle me-1"></i>
                                                    معلومات
                                                </button>
                                            @endif
                                            <a href="{{ route('performance-analysis.show', $project->id) }}"
                                                class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye me-1"></i>
                                                عرض التفاصيل
                                            </a>
                                            <a href="{{ route('projects.show', $project->id) }}"
                                                class="btn btn-sm btn-outline-info">
                                                <i class="fas fa-external-link-alt me-1"></i>
                                                المشروع
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
            </div>
            @else
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h5>لا توجد مشاريع</h5>
                <p>لم يتم العثور على مشاريع تطابق المعايير المحددة</p>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Include Project Sidebar -->
@include('projects.partials._project_sidebar')

@endsection
