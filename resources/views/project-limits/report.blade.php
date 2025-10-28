@extends('layouts.app')

@section('title', 'التقرير الشهري للمشاريع')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/project-limits.css') }}">
<style>
.report-stat-badge {
    padding: 0.35rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    transition: all 0.3s ease;
}

.report-stat-badge:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}
</style>
@endpush

@section('content')
<div class="limits-container">
    <div class="container-fluid px-3">
        @php
            $monthsMap = [
                1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل', 5 => 'مايو', 6 => 'يونيو',
                7 => 'يوليو', 8 => 'أغسطس', 9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
            ];
            $filterMonthName = $monthsMap[$month] ?? 'الشهر';
        @endphp

        <!-- Page Header -->
        <div class="page-header-limits">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h1>📈 التقرير الشهري للمشاريع</h1>
                    <p>مراقبة الحدود والإحصائيات الشهرية - {{ $filterMonthName }} {{ $year }}</p>
                </div>
                @if(auth()->check() && auth()->user()->hasRole('hr'))
                <a href="{{ route('project-limits.index') }}" class="btn btn-custom btn-gradient-light" style="margin-top: 1rem;">
                    <i class="fas fa-cog"></i>
                    إدارة الحدود
                </a>
                @endif
            </div>
        </div>

        <!-- Filters -->
        <div class="modern-form-card mb-4">
            <form method="GET" action="{{ route('project-limits.report') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label-modern">
                        <i class="fas fa-calendar"></i>
                        الشهر
                    </label>
                    <select name="month" class="form-control-modern form-select">
                        <option value="1" {{ $month == 1 ? 'selected' : '' }}>يناير</option>
                        <option value="2" {{ $month == 2 ? 'selected' : '' }}>فبراير</option>
                        <option value="3" {{ $month == 3 ? 'selected' : '' }}>مارس</option>
                        <option value="4" {{ $month == 4 ? 'selected' : '' }}>أبريل</option>
                        <option value="5" {{ $month == 5 ? 'selected' : '' }}>مايو</option>
                        <option value="6" {{ $month == 6 ? 'selected' : '' }}>يونيو</option>
                        <option value="7" {{ $month == 7 ? 'selected' : '' }}>يوليو</option>
                        <option value="8" {{ $month == 8 ? 'selected' : '' }}>أغسطس</option>
                        <option value="9" {{ $month == 9 ? 'selected' : '' }}>سبتمبر</option>
                        <option value="10" {{ $month == 10 ? 'selected' : '' }}>أكتوبر</option>
                        <option value="11" {{ $month == 11 ? 'selected' : '' }}>نوفمبر</option>
                        <option value="12" {{ $month == 12 ? 'selected' : '' }}>ديسمبر</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label-modern">
                        <i class="fas fa-calendar-alt"></i>
                        السنة
                    </label>
                    <select name="year" class="form-control-modern form-select">
                        @for($y = now()->year - 2; $y <= now()->year + 1; $y++)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label-modern">
                        <i class="fas fa-sitemap"></i>
                        القسم
                    </label>
                    <select name="department" class="form-control-modern form-select">
                        <option value="">الكل</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept }}" {{ request('department') == $dept ? 'selected' : '' }}>{{ $dept }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label-modern">
                        <i class="fas fa-users"></i>
                        الفريق
                    </label>
                    <select name="team_id" class="form-control-modern form-select">
                        <option value="">الكل</option>
                        @foreach($teams as $team)
                            <option value="{{ $team->id }}" {{ request('team_id') == $team->id ? 'selected' : '' }}>{{ $team->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-custom btn-gradient-primary w-100">
                        <i class="fas fa-search"></i>
                        عرض التقرير
                    </button>
                </div>
            </form>
        </div>

        <!-- Company Stats -->
        @if(count($report['company']) > 0)
        <div class="mb-4">
            <h4 class="mb-3" style="display: flex; align-items: center; gap: 0.5rem; color: #374151;">
                <i class="fas fa-building" style="color: #667eea;"></i>
                إحصائيات الشركة
            </h4>
            @foreach($report['company'] as $stat)
                @php
                    $percentage = $stat['percentage'] ?? 0;
                    $limit = $stat['limit'] ?? null;
                    $current = $stat['current'] ?? 0;
                    $remaining = $stat['remaining'] ?? null;
                    $isExceeded = $stat['is_exceeded'] ?? false;
                    $barClass = $isExceeded ? 'bg-danger' : ($percentage >= 80 ? 'bg-warning' : 'bg-success');
                    $rowClass = $isExceeded ? 'limit-exceeded' : ($percentage >= 80 ? 'limit-warning' : 'limit-safe');
                @endphp
                <div class="stat-card {{ $rowClass }} mb-3" style="padding: 2rem;">
                    <div class="row align-items-center">
                        <div class="col-md-2">
                            <div style="text-align: center;">
                                <i class="fas fa-building fa-3x mb-2" style="color: #667eea;"></i>
                                <h5 class="mb-1">🏢 الشركة بالكامل</h5>
                                <small class="text-muted">{{ optional($stat['limit_record'])->month === null ? $filterMonthName : ($stat['month_name'] ?? $filterMonthName) }}</small>
                            </div>
                        </div>
                        <div class="col-md-2 text-center">
                            <small class="text-muted d-block mb-1">الحد الشهري</small>
                            <h2 class="mb-0" style="color: #667eea; font-weight: 700;">{{ $limit !== null ? $limit : '-' }}</h2>
                        </div>
                        <div class="col-md-2 text-center">
                            <small class="text-muted d-block mb-1">المشاريع الحالية</small>
                            <h2 class="mb-0 {{ $isExceeded ? 'text-danger' : 'text-success' }}" style="font-weight: 700;">
                                {{ $current }}
                            </h2>
                        </div>
                        <div class="col-md-2 text-center">
                            <small class="text-muted d-block mb-1">المتبقي</small>
                            <h2 class="mb-0 {{ ($remaining ?? 0) <= 0 ? 'text-danger' : 'text-info' }}" style="font-weight: 700;">
                                {{ $remaining !== null ? $remaining : '-' }}
                            </h2>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block mb-2">نسبة الاستهلاك</small>
                            <div class="progress" style="height: 30px; border-radius: 15px;">
                                <div class="progress-bar {{ $barClass }}"
                                     style="width: {{ min($percentage, 100) }}%; font-size: 1rem; font-weight: 700;">
                                    {{ number_format($percentage, 1) }}%
                                </div>
                            </div>
                            @if($isExceeded)
                                <small class="text-danger mt-2 d-block" style="font-weight: 600;">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    تجاوز الحد المسموح!
                                </small>
                            @endif
                        </div>
                    </div>

                    @if(isset($stat['status_breakdown']))
                        @php $breakdown = $stat['status_breakdown']; @endphp
                        <div class="row mt-4 pt-4" style="border-top: 2px solid #e5e7eb;">
                            <div class="col-12 mb-3">
                                <h6 style="color: #6b7280; font-weight: 600;">
                                    <i class="fas fa-chart-pie me-2"></i>
                                    تفاصيل حالة المشاريع
                                </h6>
                            </div>
                            <div class="col-md-2 text-center mb-2">
                                <div class="report-stat-badge" style="background: linear-gradient(135deg, #6c757d, #5a6268); color: white; width: 100%; justify-content: center; padding: 1rem;">
                                    <div>
                                        <small class="d-block" style="opacity: 0.9;">جديد</small>
                                        <h3 class="mb-0">{{ $breakdown['new'] ?? 0 }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2 text-center mb-2">
                                <div class="report-stat-badge" style="background: linear-gradient(135deg, #007bff, #0056b3); color: white; width: 100%; justify-content: center; padding: 1rem;">
                                    <div>
                                        <small class="d-block" style="opacity: 0.9;">جاري التنفيذ</small>
                                        <h3 class="mb-0">{{ $breakdown['in_progress'] ?? 0 }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2 text-center mb-2">
                                <div class="report-stat-badge" style="background: linear-gradient(135deg, #28a745, #1e7e34); color: white; width: 100%; justify-content: center; padding: 1rem;">
                                    <div>
                                        <small class="d-block" style="opacity: 0.9;">مكتمل</small>
                                        <h3 class="mb-0">{{ $breakdown['completed'] ?? 0 }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2 text-center mb-2">
                                <div class="report-stat-badge" style="background: linear-gradient(135deg, #ffc107, #e0a800); color: white; width: 100%; justify-content: center; padding: 1rem;">
                                    <div>
                                        <small class="d-block" style="opacity: 0.9;">معلق</small>
                                        <h3 class="mb-0">{{ $breakdown['on_hold'] ?? 0 }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2 text-center mb-2">
                                <div class="report-stat-badge" style="background: linear-gradient(135deg, #dc3545, #c82333); color: white; width: 100%; justify-content: center; padding: 1rem;">
                                    <div>
                                        <small class="d-block" style="opacity: 0.9;">ملغي</small>
                                        <h3 class="mb-0">{{ $breakdown['cancelled'] ?? 0 }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2 text-center mb-2">
                                <div class="report-stat-badge" style="background: linear-gradient(135deg, #343a40, #23272b); color: white; width: 100%; justify-content: center; padding: 1rem;">
                                    <div>
                                        <small class="d-block" style="opacity: 0.9;">الإجمالي</small>
                                        <h3 class="mb-0">{{ $breakdown['total'] ?? 0 }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
        @endif

        <!-- Departments Stats -->
        @if(count($report['departments']) > 0)
        <div class="mb-4">
            <div class="limits-table-container">
                <div class="limits-table-header">
                    <h2>🏛️ إحصائيات الأقسام</h2>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead style="background: #f8fafc;">
                            <tr>
                                <th style="padding: 1rem; color: #374151; font-weight: 600;">القسم</th>
                                <th style="padding: 1rem; color: #374151; font-weight: 600;">الشهر</th>
                                <th style="padding: 1rem; color: #374151; font-weight: 600; text-align: center;">الحد</th>
                                <th style="padding: 1rem; color: #374151; font-weight: 600; text-align: center;">الحالي</th>
                                <th style="padding: 1rem; color: #374151; font-weight: 600; text-align: center;">المتبقي</th>
                                <th style="padding: 1rem; color: #374151; font-weight: 600;">النسبة</th>
                                <th style="padding: 1rem; color: #374151; font-weight: 600; text-align: center;">تفاصيل الحالة</th>
                                <th style="padding: 1rem; color: #374151; font-weight: 600; text-align: center;">الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($report['departments'] as $stat)
                                @php
                                    $percentage = $stat['percentage'] ?? 0;
                                    $limit = $stat['limit'] ?? null;
                                    $current = $stat['current'] ?? 0;
                                    $remaining = $stat['remaining'] ?? null;
                                    $isExceeded = $stat['is_exceeded'] ?? false;
                                    $barClass = $isExceeded ? 'bg-danger' : ($percentage >= 80 ? 'bg-warning' : 'bg-success');
                                    $rowClass = $isExceeded ? 'limit-exceeded' : ($percentage >= 80 ? 'limit-warning' : '');
                                @endphp
                                <tr class="entity-row {{ $rowClass }}" style="border-bottom: 1px solid #f0f0f0;">
                                    <td style="padding: 1.25rem;"><strong>{{ $stat['department_name'] ?? $stat['entity_name'] ?? 'غير محدد' }}</strong></td>
                                    <td style="padding: 1.25rem;">
                                        <span class="badge badge-modern" style="background: linear-gradient(135deg, #6c757d, #5a6268); color: white;">
                                            {{ optional($stat['limit_record'])->month === null ? $filterMonthName : ($stat['month_name'] ?? $filterMonthName) }}
                                        </span>
                                    </td>
                                    <td style="padding: 1.25rem; text-align: center;"><strong style="color: #667eea; font-size: 1.1rem;">{{ $limit !== null ? $limit : '-' }}</strong></td>
                                    <td style="padding: 1.25rem; text-align: center;"><strong style="font-size: 1.1rem;">{{ $current }}</strong></td>
                                    <td style="padding: 1.25rem; text-align: center;">
                                        <span class="badge badge-modern" style="background: {{ ($remaining ?? 0) <= 0 ? 'linear-gradient(135deg, #dc3545, #c82333)' : 'linear-gradient(135deg, #17a2b8, #138496)' }}; color: white;">
                                            {{ $remaining !== null ? $remaining : '-' }}
                                        </span>
                                    </td>
                                    <td style="padding: 1.25rem;">
                                        <div class="progress" style="height: 25px; border-radius: 12px;">
                                            <div class="progress-bar {{ $barClass }}"
                                                 style="width: {{ min($percentage, 100) }}%; font-weight: 600;">
                                                {{ number_format($percentage, 1) }}%
                                            </div>
                                        </div>
                                    </td>
                                    <td style="padding: 1.25rem; text-align: center;">
                                        @if(isset($stat['status_breakdown']))
                                            @php $b = $stat['status_breakdown']; @endphp
                                            <div style="display: flex; gap: 0.25rem; justify-content: center; flex-wrap: wrap;">
                                                <span class="badge" style="background: #6c757d; color: white;" title="جديد">{{ $b['new'] ?? 0 }}</span>
                                                <span class="badge" style="background: #007bff; color: white;" title="جاري">{{ $b['in_progress'] ?? 0 }}</span>
                                                <span class="badge" style="background: #28a745; color: white;" title="مكتمل">{{ $b['completed'] ?? 0 }}</span>
                                                <span class="badge" style="background: #ffc107; color: white;" title="معلق">{{ $b['on_hold'] ?? 0 }}</span>
                                                <span class="badge" style="background: #dc3545; color: white;" title="ملغي">{{ $b['cancelled'] ?? 0 }}</span>
                                            </div>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td style="padding: 1.25rem; text-align: center;">
                                        @if($isExceeded)
                                            <span class="badge badge-modern" style="background: linear-gradient(135deg, #dc3545, #c82333); color: white;">
                                                <i class="fas fa-exclamation-triangle"></i>
                                                تجاوز
                                            </span>
                                        @elseif($percentage >= 80)
                                            <span class="badge badge-modern" style="background: linear-gradient(135deg, #ffc107, #e0a800); color: white;">
                                                <i class="fas fa-exclamation-circle"></i>
                                                تحذير
                                            </span>
                                        @else
                                            <span class="badge badge-modern" style="background: linear-gradient(135deg, #28a745, #1e7e34); color: white;">
                                                <i class="fas fa-check-circle"></i>
                                                آمن
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        <!-- Teams Stats -->
        @if(count($report['teams']) > 0)
        <div class="mb-4">
            <div class="limits-table-container">
                <div class="limits-table-header" style="background: linear-gradient(135deg, #28a745, #1e7e34);">
                    <h2>👥 إحصائيات الفرق</h2>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead style="background: #f8fafc;">
                            <tr>
                                <th style="padding: 1rem; color: #374151; font-weight: 600;">الفريق</th>
                                <th style="padding: 1rem; color: #374151; font-weight: 600;">الشهر</th>
                                <th style="padding: 1rem; color: #374151; font-weight: 600; text-align: center;">الحد</th>
                                <th style="padding: 1rem; color: #374151; font-weight: 600; text-align: center;">الحالي</th>
                                <th style="padding: 1rem; color: #374151; font-weight: 600; text-align: center;">المتبقي</th>
                                <th style="padding: 1rem; color: #374151; font-weight: 600;">النسبة</th>
                                <th style="padding: 1rem; color: #374151; font-weight: 600; text-align: center;">تفاصيل الحالة</th>
                                <th style="padding: 1rem; color: #374151; font-weight: 600; text-align: center;">الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($report['teams'] as $stat)
                                @php
                                    $percentage = $stat['percentage'] ?? 0;
                                    $limit = $stat['limit'] ?? null;
                                    $current = $stat['current'] ?? 0;
                                    $remaining = $stat['remaining'] ?? null;
                                    $isExceeded = $stat['is_exceeded'] ?? false;
                                    $barClass = $isExceeded ? 'bg-danger' : ($percentage >= 80 ? 'bg-warning' : 'bg-success');
                                    $rowClass = $isExceeded ? 'limit-exceeded' : ($percentage >= 80 ? 'limit-warning' : '');
                                @endphp
                                <tr class="entity-row {{ $rowClass }}" style="border-bottom: 1px solid #f0f0f0;">
                                    <td style="padding: 1.25rem;"><strong>{{ $stat['team_name'] ?? $stat['entity_name'] ?? ($stat['team']->name ?? 'فريق محذوف') }}</strong></td>
                                    <td style="padding: 1.25rem;">
                                        <span class="badge badge-modern" style="background: linear-gradient(135deg, #6c757d, #5a6268); color: white;">
                                            {{ optional($stat['limit_record'])->month === null ? $filterMonthName : ($stat['month_name'] ?? $filterMonthName) }}
                                        </span>
                                    </td>
                                    <td style="padding: 1.25rem; text-align: center;"><strong style="color: #28a745; font-size: 1.1rem;">{{ $limit !== null ? $limit : '-' }}</strong></td>
                                    <td style="padding: 1.25rem; text-align: center;"><strong style="font-size: 1.1rem;">{{ $current }}</strong></td>
                                    <td style="padding: 1.25rem; text-align: center;">
                                        <span class="badge badge-modern" style="background: {{ ($remaining ?? 0) <= 0 ? 'linear-gradient(135deg, #dc3545, #c82333)' : 'linear-gradient(135deg, #17a2b8, #138496)' }}; color: white;">
                                            {{ $remaining !== null ? $remaining : '-' }}
                                        </span>
                                    </td>
                                    <td style="padding: 1.25rem;">
                                        <div class="progress" style="height: 25px; border-radius: 12px;">
                                            <div class="progress-bar {{ $barClass }}"
                                                 style="width: {{ min($percentage, 100) }}%; font-weight: 600;">
                                                {{ number_format($percentage, 1) }}%
                                            </div>
                                        </div>
                                    </td>
                                    <td style="padding: 1.25rem; text-align: center;">
                                        @if(isset($stat['status_breakdown']))
                                            @php $b = $stat['status_breakdown']; @endphp
                                            <div style="display: flex; gap: 0.25rem; justify-content: center; flex-wrap: wrap;">
                                                <span class="badge" style="background: #6c757d; color: white;" title="جديد">{{ $b['new'] ?? 0 }}</span>
                                                <span class="badge" style="background: #007bff; color: white;" title="جاري">{{ $b['in_progress'] ?? 0 }}</span>
                                                <span class="badge" style="background: #28a745; color: white;" title="مكتمل">{{ $b['completed'] ?? 0 }}</span>
                                                <span class="badge" style="background: #ffc107; color: white;" title="معلق">{{ $b['on_hold'] ?? 0 }}</span>
                                                <span class="badge" style="background: #dc3545; color: white;" title="ملغي">{{ $b['cancelled'] ?? 0 }}</span>
                                            </div>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td style="padding: 1.25rem; text-align: center;">
                                        @if($isExceeded)
                                            <span class="badge badge-modern" style="background: linear-gradient(135deg, #dc3545, #c82333); color: white;">
                                                <i class="fas fa-exclamation-triangle"></i>
                                                تجاوز
                                            </span>
                                        @elseif($percentage >= 80)
                                            <span class="badge badge-modern" style="background: linear-gradient(135deg, #ffc107, #e0a800); color: white;">
                                                <i class="fas fa-exclamation-circle"></i>
                                                تحذير
                                            </span>
                                        @else
                                            <span class="badge badge-modern" style="background: linear-gradient(135deg, #28a745, #1e7e34); color: white;">
                                                <i class="fas fa-check-circle"></i>
                                                آمن
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        <!-- Users Stats -->
        @if(count($report['users']) > 0)
        <div class="mb-4">
            <div class="limits-table-container">
                <div class="limits-table-header" style="background: linear-gradient(135deg, #ffc107, #e0a800);">
                    <h2>👤 إحصائيات الموظفين</h2>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead style="background: #f8fafc;">
                            <tr>
                                <th style="padding: 1rem; color: #374151; font-weight: 600;">الموظف</th>
                                <th style="padding: 1rem; color: #374151; font-weight: 600;">الشهر</th>
                                <th style="padding: 1rem; color: #374151; font-weight: 600; text-align: center;">الحد</th>
                                <th style="padding: 1rem; color: #374151; font-weight: 600; text-align: center;">الحالي</th>
                                <th style="padding: 1rem; color: #374151; font-weight: 600; text-align: center;">المتبقي</th>
                                <th style="padding: 1rem; color: #374151; font-weight: 600;">النسبة</th>
                                <th style="padding: 1rem; color: #374151; font-weight: 600; text-align: center;">تفاصيل الحالة</th>
                                <th style="padding: 1rem; color: #374151; font-weight: 600; text-align: center;">الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($report['users'] as $stat)
                                @php
                                    $percentage = $stat['percentage'] ?? 0;
                                    $limit = $stat['limit'] ?? null;
                                    $current = $stat['current'] ?? 0;
                                    $remaining = $stat['remaining'] ?? null;
                                    $isExceeded = $stat['is_exceeded'] ?? false;
                                    $barClass = $isExceeded ? 'bg-danger' : ($percentage >= 80 ? 'bg-warning' : 'bg-success');
                                    $rowClass = $isExceeded ? 'limit-exceeded' : ($percentage >= 80 ? 'limit-warning' : '');
                                @endphp
                                <tr class="entity-row {{ $rowClass }}" style="border-bottom: 1px solid #f0f0f0;">
                                    <td style="padding: 1.25rem;"><strong>{{ $stat['user_name'] ?? $stat['entity_name'] ?? ($stat['user']->name ?? 'موظف محذوف') }}</strong></td>
                                    <td style="padding: 1.25rem;">
                                        <span class="badge badge-modern" style="background: linear-gradient(135deg, #6c757d, #5a6268); color: white;">
                                            {{ optional($stat['limit_record'])->month === null ? $filterMonthName : ($stat['month_name'] ?? $filterMonthName) }}
                                        </span>
                                    </td>
                                    <td style="padding: 1.25rem; text-align: center;"><strong style="color: #ffc107; font-size: 1.1rem;">{{ $limit !== null ? $limit : '-' }}</strong></td>
                                    <td style="padding: 1.25rem; text-align: center;"><strong style="font-size: 1.1rem;">{{ $current }}</strong></td>
                                    <td style="padding: 1.25rem; text-align: center;">
                                        <span class="badge badge-modern" style="background: {{ ($remaining ?? 0) <= 0 ? 'linear-gradient(135deg, #dc3545, #c82333)' : 'linear-gradient(135deg, #17a2b8, #138496)' }}; color: white;">
                                            {{ $remaining !== null ? $remaining : '-' }}
                                        </span>
                                    </td>
                                    <td style="padding: 1.25rem;">
                                        <div class="progress" style="height: 25px; border-radius: 12px;">
                                            <div class="progress-bar {{ $barClass }}"
                                                 style="width: {{ min($percentage, 100) }}%; font-weight: 600;">
                                                {{ number_format($percentage, 1) }}%
                                            </div>
                                        </div>
                                    </td>
                                    <td style="padding: 1.25rem; text-align: center;">
                                        @if(isset($stat['status_breakdown']))
                                            @php $b = $stat['status_breakdown']; @endphp
                                            <div style="display: flex; gap: 0.25rem; justify-content: center; flex-wrap: wrap;">
                                                <span class="badge" style="background: #6c757d; color: white;" title="جديد">{{ $b['new'] ?? 0 }}</span>
                                                <span class="badge" style="background: #007bff; color: white;" title="جاري">{{ $b['in_progress'] ?? 0 }}</span>
                                                <span class="badge" style="background: #28a745; color: white;" title="مكتمل">{{ $b['completed'] ?? 0 }}</span>
                                                <span class="badge" style="background: #ffc107; color: white;" title="معلق">{{ $b['on_hold'] ?? 0 }}</span>
                                                <span class="badge" style="background: #dc3545; color: white;" title="ملغي">{{ $b['cancelled'] ?? 0 }}</span>
                                            </div>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td style="padding: 1.25rem; text-align: center;">
                                        @if($isExceeded)
                                            <span class="badge badge-modern" style="background: linear-gradient(135deg, #dc3545, #c82333); color: white;">
                                                <i class="fas fa-exclamation-triangle"></i>
                                                تجاوز
                                            </span>
                                        @elseif($percentage >= 80)
                                            <span class="badge badge-modern" style="background: linear-gradient(135deg, #ffc107, #e0a800); color: white;">
                                                <i class="fas fa-exclamation-circle"></i>
                                                تحذير
                                            </span>
                                        @else
                                            <span class="badge badge-modern" style="background: linear-gradient(135deg, #28a745, #1e7e34); color: white;">
                                                <i class="fas fa-check-circle"></i>
                                                آمن
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        <!-- Empty State -->
        @if(count($report['company']) == 0 && count($report['departments']) == 0 && count($report['teams']) == 0 && count($report['users']) == 0)
        <div style="text-align: center; padding: 5rem 2rem;">
            <i class="fas fa-chart-line" style="font-size: 5rem; color: #dee2e6; margin-bottom: 2rem;"></i>
            <h4 style="color: #6b7280; font-weight: 600; margin-bottom: 1rem;">لا توجد بيانات لعرضها</h4>
            <p style="color: #9ca3af; margin-bottom: 2rem;">لا توجد حدود محددة أو لا توجد مشاريع تتطابق مع الفلاتر المحددة</p>
            @if(auth()->check() && auth()->user()->hasRole('hr'))
            <a href="{{ route('project-limits.create') }}" class="btn btn-custom btn-gradient-primary">
                <i class="fas fa-plus"></i>
                إضافة حد شهري
            </a>
            @endif
        </div>
        @endif
    </div>
</div>
@endsection
