@extends('layouts.app')

@section('title', 'سجل تقييمات KPI')

@push('styles')
<link href="{{ asset('css/evaluation-criteria-modern.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="evaluation-container">
    <!-- Header Section -->
    <div class="modern-card modern-card-header-white mb-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px; box-shadow: 0 8px 32px rgba(0,0,0,0.1);">
        <div class="modern-card-header d-flex justify-content-between align-items-center" style="color: white;">
            <div class="d-flex align-items-center">
                <div class="icon-container me-3">
                    <i class="fas fa-chart-line floating" style="font-size: 2rem; color: white;"></i>
                </div>
                <div>
                    <h2 class="mb-1" style="color: white !important;">📈 سجل تقييمات KPI</h2>
                    <p class="mb-0" style="color: white !important; opacity: 0.9;">عرض وإدارة تقييمات أداء الموظفين بناءً على KPI</p>
                </div>
            </div>
            <div>
                <a href="{{ route('kpi-evaluation.create') }}" class="btn btn-modern btn-outline-light" style="color: white; border-color: rgba(255,255,255,0.5);">
                    <i class="fas fa-plus me-2"></i>
                    تقييم جديد
                </a>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="modern-card mb-4">
        <div class="modern-card-body">
            <form method="GET" action="{{ route('kpi-evaluation.index') }}" class="row g-3">
                <!-- Month and Year Filter -->
                <div class="col-md-3">
                    <div class="form-floating-modern">
                        <input type="month"
                               name="month_year"
                               id="month_year"
                               class="form-control-modern"
                               value="{{ request('month_year') }}"
                               placeholder="اختر الشهر والسنة">
                        <label for="month_year">الشهر والسنة</label>
                    </div>
                </div>

                <!-- Department Filter -->
                <div class="col-md-3">
                    <div class="form-floating-modern">
                        <select name="department" class="form-select-modern" id="department" onchange="filterEmployees()">
                            <option value="">جميع الأقسام</option>
                            @foreach($departments as $department)
                                <option value="{{ $department }}" {{ request('department') == $department ? 'selected' : '' }}>
                                    {{ $department }}
                                </option>
                            @endforeach
                        </select>
                        <label for="department">القسم</label>
                    </div>
                </div>

                <!-- Employee Filter -->
                <div class="col-md-3">
                    <div class="form-floating-modern">
                        <select name="employee_id" class="form-select-modern" id="employee_id">
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
                        <label for="employee_id">الموظف</label>
                    </div>
                </div>

                <!-- Role Filter -->
                <div class="col-md-3">
                    <div class="form-floating-modern">
                        <select name="role_id" class="form-select-modern" id="role_id">
                            <option value="">جميع الأدوار</option>
                            @foreach($rolesCanEvaluate as $mapping)
                                <option value="{{ $mapping->roleToEvaluate->id }}"
                                    {{ request('role_id') == $mapping->roleToEvaluate->id ? 'selected' : '' }}>
                                    {{ $mapping->roleToEvaluate->display_name ?? $mapping->roleToEvaluate->name }}
                                </option>
                            @endforeach
                        </select>
                        <label for="role_id">الدور</label>
                    </div>
                </div>

                <!-- Evaluation Type Filter -->
                <div class="col-md-3">
                    <div class="form-floating-modern">
                        <select name="evaluation_type" class="form-select-modern" id="evaluation_type">
                            <option value="">جميع الأنواع</option>
                            <option value="monthly" {{ request('evaluation_type') == 'monthly' ? 'selected' : '' }}>
                                📅 شهري
                            </option>
                            <option value="bi_weekly" {{ request('evaluation_type') == 'bi_weekly' ? 'selected' : '' }}>
                                ⚡ نصف شهري
                            </option>
                        </select>
                        <label for="evaluation_type">نوع التقييم</label>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="col-md-12 d-flex align-items-end gap-2 justify-content-center">
                    <button type="submit" class="btn btn-modern btn-primary">
                        <i class="fas fa-search me-2"></i>
                        بحث
                    </button>

                    @if(request()->hasAny(['month_year', 'department', 'employee_id', 'role_id', 'evaluation_type']))
                        <a href="{{ route('kpi-evaluation.index') }}" class="btn btn-modern btn-outline-secondary">
                            <i class="fas fa-times me-2"></i>
                            إزالة الفلاتر
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="stats-card">
                <div class="stats-icon">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stats-content">
                    <h3 class="stats-number">{{ $evaluations->total() }}</h3>
                    <p class="stats-label">إجمالي التقييمات</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card">
                <div class="stats-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stats-content">
                    <h3 class="stats-number">{{ $evaluations->where('review_month', now()->format('Y-m'))->count() }}</h3>
                    <p class="stats-label">تقييمات الشهر الحالي</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card">
                <div class="stats-icon">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <div class="stats-content">
                    <h3 class="stats-number">{{ round($evaluations->avg('total_after_deductions'), 1) }}</h3>
                    <p class="stats-label">متوسط النقاط</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Evaluations Table -->
    <div class="modern-card">
        <div class="modern-card-body p-0">
            @if($evaluations->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                            <tr>
                                <th style="border: none; padding: 1rem;">الموظف</th>
                                <th style="border: none; padding: 1rem;">الدور</th>
                                <th style="border: none; padding: 1rem;">نوع التقييم</th>
                                <th style="border: none; padding: 1rem;">شهر التقييم</th>
                                <th style="border: none; padding: 1rem;">النقاط الإجمالية</th>
                                <th style="border: none; padding: 1rem;">تفاصيل النقاط</th>
                                <th style="border: none; padding: 1rem;">النقاط النهائية</th>
                                <th style="border: none; padding: 1rem;">تاريخ الإنشاء</th>
                                <th style="border: none; padding: 1rem; text-align: center;">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($evaluations as $evaluation)
                                <tr class="fade-in">
                                    <td style="padding: 1rem; border-bottom: 1px solid rgba(0,0,0,0.1);">
                                        <div>
                                            <strong>{{ $evaluation->user->name ?? 'N/A' }}</strong>
                                            @if($evaluation->user->department)
                                                <br><small class="text-muted">{{ $evaluation->user->department }}</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td style="padding: 1rem; border-bottom: 1px solid rgba(0,0,0,0.1);">
                                        @if(isset($evaluation->roles) && $evaluation->roles->count() > 1)
                                            <div class="d-flex flex-wrap gap-1">
                                                @foreach($evaluation->roles as $role)
                                                    <span class="badge-modern" style="font-size: 0.75rem;">
                                                        {{ $role->display_name ?? $role->name }}
                                                    </span>
                                                @endforeach
                                            </div>
                                            <small class="text-muted d-block mt-1">
                                                <i class="fas fa-layer-group me-1"></i>{{ $evaluation->roles_count }} دور
                                            </small>
                                        @else
                                            <span class="badge-modern">
                                                {{ $evaluation->role->display_name ?? $evaluation->role->name ?? 'N/A' }}
                                            </span>
                                        @endif
                                    </td>
                                    <td style="padding: 1rem; border-bottom: 1px solid rgba(0,0,0,0.1);">
                                        @php
                                            $evaluationType = $evaluation->evaluation_type ?? 'monthly';
                                        @endphp
                                        @if($evaluationType === 'monthly')
                                            <span class="badge bg-primary">
                                                📅 شهري
                                            </span>
                                        @elseif($evaluationType === 'bi_weekly')
                                            <span class="badge bg-warning text-dark">
                                                ⚡ نصف شهري
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">
                                                غير محدد
                                            </span>
                                        @endif
                                    </td>
                                    <td style="padding: 1rem; border-bottom: 1px solid rgba(0,0,0,0.1);">
                                        @if($evaluation->month)
                                            @php
                                                try {
                                                    $monthDate = \Carbon\Carbon::createFromFormat('Y-m', $evaluation->month);
                                                    echo $monthDate->locale('ar')->translatedFormat('F Y');
                                                } catch (\Exception $e) {
                                                    echo $evaluation->month;
                                                }
                                            @endphp
                                        @else
                                            <span class="text-muted">غير محدد</span>
                                        @endif
                                    </td>
                                    <td style="padding: 1rem; border-bottom: 1px solid rgba(0,0,0,0.1);">
                                        <div class="d-flex flex-column align-items-center">
                                            <span class="score-badge total mb-1">{{ $evaluation->total_after_deductions }}</span>
                                            @if($evaluation->project_score > 0)
                                                <small class="text-success">
                                                    <i class="fas fa-plus me-1"></i>{{ $evaluation->project_score }} ({{ $evaluation->project_count }} مشروع)
                                                </small>
                                            @endif
                                        </div>
                                    </td>
                                    <td style="padding: 1rem; border-bottom: 1px solid rgba(0,0,0,0.1);">
                                        <div class="score-breakdown-mini">
                                            @if($evaluation->total_score > 0)
                                                <span class="badge bg-success me-1 mb-1" style="font-size: 0.7rem;">
                                                    <i class="fas fa-star me-1"></i>{{ $evaluation->total_score }} أساسي
                                                </span>
                                            @endif
                                            @if($evaluation->total_bonus > 0)
                                                <span class="badge bg-warning text-dark me-1 mb-1" style="font-size: 0.7rem;">
                                                    <i class="fas fa-gift me-1"></i>{{ $evaluation->total_bonus }} بونص
                                                </span>
                                            @endif
                                            @if($evaluation->total_development > 0)
                                                <span class="badge bg-info me-1 mb-1" style="font-size: 0.7rem;">
                                                    <i class="fas fa-graduation-cap me-1"></i>{{ $evaluation->total_development }} تطويري
                                                </span>
                                            @endif
                                            @if($evaluation->total_deductions > 0)
                                                <span class="badge bg-danger me-1 mb-1" style="font-size: 0.7rem;">
                                                    <i class="fas fa-minus me-1"></i>{{ $evaluation->total_deductions }} خصم
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td style="padding: 1rem; border-bottom: 1px solid rgba(0,0,0,0.1);">
                                        <span class="score-badge final
                                            @if($evaluation->final_total >= 80) excellent
                                            @elseif($evaluation->final_total >= 60) good
                                            @else needs-improvement
                                            @endif">
                                            {{ $evaluation->final_total }}
                                        </span>
                                        @if($evaluation->evaluation_count > 1)
                                            <br><small class="text-muted">
                                                <i class="fas fa-layer-group me-1"></i>{{ $evaluation->evaluation_count }} تقييم
                                            </small>
                                        @endif
                                    </td>
                                    <td style="padding: 1rem; border-bottom: 1px solid rgba(0,0,0,0.1);">
                                        {{ $evaluation->created_at->locale('ar')->diffForHumans() }}
                                    </td>
                                    <td style="padding: 1rem; border-bottom: 1px solid rgba(0,0,0,0.1); text-align: center;">
                                        <a href="{{ route('kpi-evaluation.details', ['user_id' => $evaluation->user_id, 'month' => $evaluation->month]) }}"
                                           class="btn btn-modern btn-sm btn-outline-primary">
                                            <i class="fas fa-eye me-1"></i>
                                            عرض
                                            @if(isset($evaluation->roles_count) && $evaluation->roles_count > 1)
                                                <span class="badge bg-info ms-1">{{ $evaluation->roles_count }}</span>
                                            @endif
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($evaluations->hasPages())
                    <div class="d-flex justify-content-center p-4">
                        {{ $evaluations->appends(request()->query())->links() }}
                    </div>
                @endif
            @else
                <div class="text-center py-5">
                    <div class="empty-state">
                        <i class="fas fa-chart-line mb-3" style="font-size: 4rem; color: var(--color-muted);"></i>
                        <h4 class="text-muted">لا توجد تقييمات</h4>
                        <p class="text-muted mb-4">لم يتم إجراء أي تقييمات بعد</p>
                        <a href="{{ route('kpi-evaluation.create') }}" class="btn btn-modern btn-primary">
                            <i class="fas fa-plus me-2"></i>
                            إضافة تقييم جديد
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

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

.empty-state {
    max-width: 400px;
    margin: 0 auto;
}

.score-breakdown-mini {
    display: flex;
    flex-wrap: wrap;
    gap: 2px;
    justify-content: center;
    align-items: center;
    min-width: 200px;
}

.score-breakdown-mini .badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-weight: 500;
    white-space: nowrap;
}

/* إخفاء شريط التمرير الأفقي مع الحفاظ على إمكانية التمرير */
.table-responsive {
    /* Firefox */
    scrollbar-width: none;
    /* IE and Edge */
    -ms-overflow-style: none;
}

/* WebKit browsers (Chrome, Safari, Edge) */
.table-responsive::-webkit-scrollbar {
    display: none;
}

@media (max-width: 768px) {
    .stats-card {
        margin-bottom: 1rem;
    }

    .table-responsive {
        font-size: 0.8rem;
        /* Firefox */
        scrollbar-width: none;
        /* IE and Edge */
        -ms-overflow-style: none;
    }

    .modern-card-header {
        flex-direction: column;
        text-align: center;
    }

    .modern-card-header > div {
        margin-bottom: 1rem;
    }

    .modern-card-header > div:last-child {
        margin-bottom: 0;
    }

    .score-breakdown-mini {
        min-width: 150px;
        gap: 1px;
    }

    .score-breakdown-mini .badge {
        font-size: 0.6rem;
        padding: 0.2rem 0.4rem;
    }

    .table th, .table td {
        padding: 0.5rem !important;
    }
}

/* تحسين مظهر Date Picker */
.form-control-modern {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    background: #ffffff;
    font-size: 1rem;
    transition: all 0.3s ease;
    direction: ltr;
}

.form-control-modern:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    outline: none;
}
</style>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Intersection Observer for animations
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    });

    // Observe fade-in elements
    document.querySelectorAll('.fade-in').forEach((el) => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'all 0.6s ease';
        observer.observe(el);
    });

    // Add hover effects to table rows
    document.querySelectorAll('tbody tr').forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.background = 'linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%)';
            this.style.transform = 'translateX(5px)';
        });

        row.addEventListener('mouseleave', function() {
            this.style.background = '';
            this.style.transform = '';
        });
    });
});

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
