@extends('layouts.app')

@section('title', 'سجل تقييمات KPI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/projects-services.css') }}">
@endpush

@section('content')
<div class="simple-container">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h1>📈 سجل تقييمات KPI</h1>
                    <p>عرض وإدارة تقييمات أداء الموظفين بناءً على KPI</p>
                </div>
                <div class="mt-2 mt-md-0">
                    <a href="{{ route('kpi-evaluation.create') }}" class="btn btn-light btn-sm" style="color: white; background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3);">
                        <i class="fas fa-plus me-2"></i>
                        تقييم جديد
                    </a>
                </div>
            </div>
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

        <!-- Filters Section -->
        <div class="filters-section">
            <form method="GET" action="{{ route('kpi-evaluation.index') }}" id="filterForm">
                <div class="filters-row">
                    <!-- Month and Year Filter -->
                    <div class="filter-group">
                        <label for="month_year" class="filter-label">
                            <i class="fas fa-calendar-alt"></i>
                            الشهر والسنة
                        </label>
                        <input type="month"
                            name="month_year"
                            id="month_year"
                            class="filter-select"
                            value="{{ request('month_year') }}"
                            onchange="document.getElementById('filterForm').submit()">
                    </div>

                    <!-- Department Filter -->
                    <div class="filter-group">
                        <label for="department" class="filter-label">
                            <i class="fas fa-building"></i>
                            القسم
                        </label>
                        <select name="department" id="department" class="filter-select" onchange="filterEmployees(); document.getElementById('filterForm').submit();">
                            <option value="">جميع الأقسام</option>
                            @foreach($departments as $department)
                            <option value="{{ $department }}" {{ request('department') == $department ? 'selected' : '' }}>
                                {{ $department }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Employee Filter -->
                    <div class="filter-group">
                        <label for="employee_id" class="filter-label">
                            <i class="fas fa-user"></i>
                            الموظف
                        </label>
                        <select name="employee_id" id="employee_id" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                            <option value="">جميع الموظفين</option>
                            @foreach($employees as $employee)
                            <option value="{{ $employee->id }}"
                                data-department="{{ $employee->department }}"
                                {{ request('employee_id') == $employee->id ? 'selected' : '' }}
                                style="{{ request('department') && request('department') != $employee->department ? 'display: none;' : '' }}">
                                {{ $employee->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Role Filter -->
                    <div class="filter-group">
                        <label for="role_id" class="filter-label">
                            <i class="fas fa-user-tag"></i>
                            الدور
                        </label>
                        <select name="role_id" id="role_id" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                            <option value="">جميع الأدوار</option>
                            @foreach($rolesCanEvaluate as $mapping)
                            <option value="{{ $mapping->roleToEvaluate->id }}"
                                {{ request('role_id') == $mapping->roleToEvaluate->id ? 'selected' : '' }}>
                                {{ $mapping->roleToEvaluate->display_name ?? $mapping->roleToEvaluate->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Evaluation Type Filter -->
                    <div class="filter-group">
                        <label for="evaluation_type" class="filter-label">
                            <i class="fas fa-clock"></i>
                            نوع التقييم
                        </label>
                        <select name="evaluation_type" id="evaluation_type" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                            <option value="">جميع الأنواع</option>
                            <option value="monthly" {{ request('evaluation_type') == 'monthly' ? 'selected' : '' }}>
                                📅 شهري
                            </option>
                            <option value="bi_weekly" {{ request('evaluation_type') == 'bi_weekly' ? 'selected' : '' }}>
                                ⚡ نصف شهري
                            </option>
                        </select>
                    </div>

                    <!-- Search Button -->
                    <div class="filter-group">
                        <label class="filter-label" style="opacity: 0;">بحث</label>
                        <button type="submit" class="search-btn">
                            <i class="fas fa-search"></i>
                            بحث
                        </button>
                    </div>

                    <!-- Clear Filters -->
                    @if(request()->hasAny(['month_year', 'department', 'employee_id', 'role_id', 'evaluation_type']))
                    <div class="filter-group">
                        <label class="filter-label" style="opacity: 0;">مسح</label>
                        <a href="{{ route('kpi-evaluation.index') }}" class="clear-filters-btn">
                            <i class="fas fa-times"></i>
                            مسح الفلاتر
                        </a>
                    </div>
                    @endif
                </div>
            </form>
        </div>

        <!-- Statistics Row -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-number">{{ $evaluations->total() }}</div>
                <div class="stat-label">إجمالي التقييمات</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $evaluations->where('review_month', now()->format('Y-m'))->count() }}</div>
                <div class="stat-label">تقييمات الشهر الحالي</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ round($evaluations->avg('total_after_deductions'), 1) }}</div>
                <div class="stat-label">متوسط النقاط</div>
            </div>
            @if($evaluations->where('total_after_deductions', '>=', 80)->count() > 0)
            <div class="stat-card">
                <div class="stat-number">{{ $evaluations->where('total_after_deductions', '>=', 80)->count() }}</div>
                <div class="stat-label">تقييمات ممتازة</div>
            </div>
            @endif
        </div>

        <!-- Evaluations Table -->
        <div class="projects-table-container">
            <div class="table-header">
                <h2> قائمة التقييمات</h2>
            </div>

            @if($evaluations->count() > 0)
            <table class="projects-table">
                <thead>
                    <tr>
                        <th>الموظف</th>
                        <th>الدور</th>
                        <th>نوع التقييم</th>
                        <th>شهر التقييم</th>
                        <th>النقاط الإجمالية</th>
                        <th>تفاصيل النقاط</th>
                        <th>النقاط النهائية</th>
                        <th>تاريخ الإنشاء</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($evaluations as $evaluation)
                    <tr class="project-row">
                        <td>
                            <div class="project-info">
                                <div class="project-avatar">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="project-details">
                                    <h4>{{ $evaluation->user->name ?? 'N/A' }}</h4>
                                    @if($evaluation->user->department)
                                    <p>{{ $evaluation->user->department }}</p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="client-info">
                                @if(isset($evaluation->roles) && $evaluation->roles->count() > 1)
                                @foreach($evaluation->roles as $role)
                                <span class="status-badge status-in-progress" style="font-size: 0.75rem; display: inline-block; margin: 2px;">
                                    {{ $role->display_name ?? $role->name }}
                                </span>
                                @endforeach
                                @else
                                <span class="status-badge status-in-progress">
                                    {{ $evaluation->role->display_name ?? $evaluation->role->name ?? 'N/A' }}
                                </span>
                                @endif
                            </div>
                        </td>
                        <td>
                            @php
                            $evaluationType = $evaluation->evaluation_type ?? 'monthly';
                            @endphp
                            @if($evaluationType === 'monthly')
                            <span class="status-badge status-in-progress">
                                📅 شهري
                            </span>
                            @elseif($evaluationType === 'bi_weekly')
                            <span class="status-badge status-new">
                                ⚡ نصف شهري
                            </span>
                            @else
                            <span class="status-badge status-cancelled">
                                غير محدد
                            </span>
                            @endif
                        </td>
                        <td>
                            @if($evaluation->month)
                            @php
                            try {
                            $monthDate = \Carbon\Carbon::createFromFormat('Y-m', $evaluation->month);
                            echo $monthDate->locale('ar')->translatedFormat('Y/m');
                            } catch (\Exception $e) {
                            echo $evaluation->month;
                            }
                            @endphp
                            @else
                            <span style="color: #9ca3af;">غير محدد</span>
                            @endif
                        </td>
                        <td>
                            <div style="text-align: center;">
                                <span class="score-badge total">{{ $evaluation->total_after_deductions }}</span>
                                @if($evaluation->project_score > 0)
                                <div style="font-size: 0.8rem; color: #10b981; margin-top: 4px;">
                                    <i class="fas fa-plus"></i> {{ $evaluation->project_score }}
                                </div>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div class="score-breakdown-mini">
                                @if($evaluation->total_score > 0)
                                <span class="badge bg-success" style="font-size: 0.7rem; margin: 2px;">
                                    <i class="fas fa-star"></i> {{ $evaluation->total_score }}
                                </span>
                                @endif
                                @if($evaluation->total_bonus > 0)
                                <span class="badge bg-warning text-dark" style="font-size: 0.7rem; margin: 2px;">
                                    <i class="fas fa-gift"></i> {{ $evaluation->total_bonus }}
                                </span>
                                @endif
                                @if($evaluation->total_development > 0)
                                <span class="badge bg-info" style="font-size: 0.7rem; margin: 2px;">
                                    <i class="fas fa-graduation-cap"></i> {{ $evaluation->total_development }}
                                </span>
                                @endif
                                @if($evaluation->total_deductions > 0)
                                <span class="badge bg-danger" style="font-size: 0.7rem; margin: 2px;">
                                    <i class="fas fa-minus"></i> {{ $evaluation->total_deductions }}
                                </span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div style="text-align: center;">
                                <span class="score-badge final
                                        @if($evaluation->final_total >= 80) excellent
                                        @elseif($evaluation->final_total >= 60) good
                                        @else needs-improvement
                                        @endif">
                                    {{ $evaluation->final_total }}
                                </span>
                            </div>
                        </td>
                        <td>
                            <div style="color: #6b7280; font-size: 0.9rem;">
                                {{ $evaluation->created_at->locale('ar')->diffForHumans() }}
                            </div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
                                <a href="{{ route('kpi-evaluation.details', ['user_id' => $evaluation->user_id, 'month' => $evaluation->month]) }}"
                                    class="services-btn"
                                    title="عرض التفاصيل">
                                    <i class="fas fa-eye"></i>
                                    عرض
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Pagination -->
            @if($evaluations->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $evaluations->appends(request()->query())->links() }}
            </div>
            @endif
            @else
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h4>لا توجد تقييمات</h4>
                <p>لم يتم العثور على تقييمات مطابقة للفلاتر المحددة</p>
                <a href="{{ route('kpi-evaluation.create') }}" class="services-btn" style="margin-top: 1rem;">
                    <i class="fas fa-plus"></i>
                    إضافة تقييم جديد
                </a>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .score-badge {
        padding: 0.5rem 1rem;
        border-radius: 25px;
        font-weight: bold;
        color: white;
        display: inline-block;
        min-width: 60px;
        text-align: center;
    }

    .score-badge.total {
        background: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%);
    }

    .score-badge.final.excellent {
        background: linear-gradient(135deg, #00b894 0%, #00a085 100%);
    }

    .score-badge.final.good {
        background: linear-gradient(135deg, #fdcb6e 0%, #e17055 100%);
    }

    .score-badge.final.needs-improvement {
        background: linear-gradient(135deg, #fd79a8 0%, #e84393 100%);
    }

    .score-breakdown-mini {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        justify-content: center;
        align-items: center;
    }

    .score-breakdown-mini .badge {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-weight: 500;
        white-space: nowrap;
    }
</style>
@endpush

@push('scripts')
<script>
    // Filter employees by department
    function filterEmployees() {
        const departmentSelect = document.getElementById('department');
        const employeeSelect = document.getElementById('employee_id');
        const selectedDepartment = departmentSelect.value;

        // Get all employee options
        const employeeOptions = employeeSelect.querySelectorAll('option[data-department]');

        // Show/hide employees based on selected department
        employeeOptions.forEach(option => {
            if (selectedDepartment === '' || option.dataset.department === selectedDepartment) {
                option.style.display = '';
            } else {
                option.style.display = 'none';
            }
        });

        // Reset employee selection if the selected employee is not in the filtered department
        if (employeeSelect.value && selectedDepartment) {
            const selectedEmployeeOption = employeeSelect.querySelector(`option[value="${employeeSelect.value}"]`);
            if (selectedEmployeeOption && selectedEmployeeOption.dataset.department !== selectedDepartment) {
                employeeSelect.value = '';
            }
        }
    }
</script>
@endpush