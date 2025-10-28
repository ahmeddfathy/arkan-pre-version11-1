@extends('layouts.app')

@push('styles')
<link href="{{ asset('css/employee-competition.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="container-fluid py-4">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h1 class="text-center mb-4">🏆 مسابقة الموظف المثالي</h1>
                    <div class="row justify-content-center">
                        <div class="col-md-4">
                            <form method="GET" class="d-flex gap-2">
                                <input type="date"
                                    class="form-control"
                                    name="start_date"
                                    value="{{ $startDate }}"
                                    required>
                                <input type="date"
                                    class="form-control"
                                    name="end_date"
                                    value="{{ $endDate }}"
                                    required>
                                <button type="submit" class="btn btn-primary">تطبيق</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Competition Rules Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card rules-card">
                <div class="card-body">
                    <h3 class="text-center mb-4">📋 قواعد المسابقة</h3>
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="mb-3">🎯 نظام النقاط</h5>
                            <div class="rule-item">
                                <strong>نسبة الحضور (٥٠ نقطة)</strong>
                                <ul class="mb-0">
                                    <li>يتم احتساب النقاط بناءً على نسبة الحضور الشهرية</li>
                                    <li>كل ١٪ حضور = ٠.٥ نقطة</li>
                                </ul>
                            </div>
                            <div class="rule-item">
                                <strong>الحضور المبكر (٢٠ نقطة)</strong>
                                <ul class="mb-0">
                                    <li>الساعة الأولى: ١٠ نقاط كحد أقصى</li>
                                    <li>الساعة الثانية: ٦ نقاط إضافية</li>
                                    <li>أكثر من ساعتين: ٤ نقاط إضافية</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h5 class="mb-3">📉 الخصومات</h5>
                            <div class="rule-item">
                                <strong>الغياب (حتى -١٥ نقطة)</strong>
                                <ul class="mb-0">
                                    <li>أول يومين: -٤ نقاط لكل يوم</li>
                                    <li>اليوم ٣-٤: -٥ نقاط لكل يوم</li>
                                    <li>٥ أيام فأكثر: -٦ نقاط لكل يوم</li>
                                </ul>
                            </div>
                            <div class="rule-item">
                                <strong>الأذونات والإجازات (حتى -٧.٥ نقطة لكل منهما)</strong>
                                <ul class="mb-0">
                                    <li>أول إذنين/إجازتين: -٢ نقطة لكل مرة</li>
                                    <li>٣-٤ مرات: -٢.٥ نقطة لكل مرة</li>
                                    <li>٥ مرات فأكثر: -٣ نقاط لكل مرة</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Performers Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card trophy-card shadow">
                <div class="card-body">
                    <h3 class="text-center mb-4">🌟 الفائزون في المسابقة</h3>
                    <div class="row">
                        @foreach($topPerformers['overall'] as $index => $employee)
                        <div class="col-md-4">
                            <div class="text-center">
                                <div class="position-relative d-inline-block">
                                    <img src="{{ $employee['profile_photo'] }}"
                                         alt="{{ $employee['name'] }}"
                                         class="profile-photo mb-2"
                                         style="width: 100px; height: 100px;">
                                    <div class="position-absolute bottom-0 end-0">
                                        <span class="badge bg-primary rounded-circle p-2">
                                            {{ $index + 1 }}
                                        </span>
                                    </div>
                                </div>
                                <h4>{{ $employee['name'] }}</h4>
                                <p class="text-muted">{{ $employee['department'] }}</p>
                                <h5>{{ $employee['points'] }} نقطة</h5>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Categories Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h3 class="text-center mb-4">🎯 التصنيفات</h3>
                    <div class="d-flex justify-content-center gap-2 flex-wrap mb-4">
                        <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'points']) }}"
                           class="btn sort-button {{ $sortBy == 'points' ? 'active' : '' }}">
                            🏆 النقاط الإجمالية
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'attendance']) }}"
                           class="btn sort-button {{ $sortBy == 'attendance' ? 'active' : '' }}">
                            ✅ نسبة الحضور
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'early_minutes']) }}"
                           class="btn sort-button {{ $sortBy == 'early_minutes' ? 'active' : '' }}">
                            🌅 الحضور المبكر
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'absences']) }}"
                           class="btn sort-button {{ $sortBy == 'absences' ? 'active' : '' }}">
                            ❌ الغياب
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'permissions']) }}"
                           class="btn sort-button {{ $sortBy == 'permissions' ? 'active' : '' }}">
                            📝 الأذونات
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'leaves']) }}"
                           class="btn sort-button {{ $sortBy == 'leaves' ? 'active' : '' }}">
                            🏖️ الإجازات
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Employees Ranking Table - SORTED BY COMPETITION RANK -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>الترتيب</th>
                                    <th>الموظف</th>
                                    <th>القسم</th>
                                    <th>نسبة الحضور</th>
                                    <th>الحضور المبكر</th>
                                    <th>الغياب</th>
                                    <th>الأذونات</th>
                                    <th>الإجازات</th>
                                    <th>ساعات العمل</th>
                                    <th>النقاط</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    // هنا نقوم بعمل ترتيب للموظفين حسب النقاط قبل عرضهم
                                    // ترتيب تنازلي - من الأعلى للأقل
                                    $sortedEmployees = collect($employees)->sortByDesc('points')->values()->all();
                                @endphp

                                @foreach($sortedEmployees as $index => $employee)
                                <tr>
                                    <td>
                                        <div class="employee-rank {{ $index < 3 ? 'rank-'.($index+1) : 'bg-light' }}">
                                            {{ $index + 1 }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <img src="{{ $employee['profile_photo'] }}"
                                                 alt="{{ $employee['name'] }}"
                                                 class="profile-photo">
                                            <div>
                                                <div>{{ $employee['name'] }}</div>
                                                <small class="text-muted">{{ $employee['employee_id'] }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $employee['department'] }}</td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <div class="progress mb-1" style="height: 20px;">
                                                <div class="progress-bar {{ $employee['attendance_percentage'] >= 90 ? 'bg-success' : ($employee['attendance_percentage'] >= 75 ? 'bg-warning' : 'bg-danger') }}"
                                                    role="progressbar"
                                                    style="width: {{ $employee['attendance_percentage'] }}%">
                                                    {{ number_format($employee['attendance_percentage'], 1) }}٪
                                                </div>
                                            </div>
                                            <small class="text-muted">
                                                {{ number_format($employee['actual_attendance_days']) }} من {{ number_format($employee['total_working_days']) }} يوم
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info stats-badge">
                                            {{ number_format($employee['early_minutes']) }} دقيقة
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $employee['absences'] > 0 ? 'bg-danger' : 'bg-success' }} stats-badge">
                                            {{ number_format($employee['absences']) }} أيام
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning stats-badge">
                                            {{ number_format($employee['permissions_count']) }} مرات
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary stats-badge">
                                            {{ number_format($employee['leaves_count']) }} أيام
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="badge bg-primary stats-badge mb-1">
                                                {{ number_format($employee['actual_working_hours'], 1) }} ساعة
                                            </span>
                                            <small class="text-muted">
                                                من {{ number_format($employee['total_shift_hours'], 1) }} ساعة
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column align-items-center">
                                            <span class="badge bg-success stats-badge mb-2">
                                                {{ number_format($employee['points'], 1) }} نقطة
                                            </span>
                                            <button class="btn btn-sm btn-outline-info"
                                                    type="button"
                                                    data-bs-toggle="popover"
                                                    data-bs-placement="left"
                                                    data-bs-html="true"
                                                    data-bs-title="تفاصيل النقاط"
                                                    data-bs-content="
                                                        <div class='text-end points-breakdown'>
                                                            <div>الحضور: {{ number_format(($employee['attendance_percentage'] / 100) * 50, 1) }} من ٥٠</div>
                                                            <div>الحضور المبكر: {{ number_format(min(($employee['early_minutes'] / 60) * 2.5, 20), 1) }} من ٢٠</div>
                                                            <div>خصم الغياب: {{ number_format(min($employee['absences'] * 5, 15), 1) }} من -١٥</div>
                                                            <div>خصم الأذونات: {{ number_format(min($employee['permissions_count'] * 2.5, 7.5), 1) }} من -٧.٥</div>
                                                            <div>خصم الإجازات: {{ number_format(min($employee['leaves_count'] * 2.5, 7.5), 1) }} من -٧.٥</div>
                                                            <div class='mt-2'>
                                                                <strong>ساعات العمل:</strong><br>
                                                                {{ number_format($employee['actual_working_hours'], 1) }} من {{ number_format($employee['total_shift_hours'], 1) }} ساعة
                                                            </div>
                                                        </div>
                                                    ">
                                                <i class="fas fa-info-circle"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
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
document.addEventListener('DOMContentLoaded', function() {
    const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
    const popoverList = [...popoverTriggerList].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl));
});
</script>
@endpush
