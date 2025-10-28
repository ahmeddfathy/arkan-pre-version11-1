@extends('layouts.app')

@push('styles')
<link href="{{ asset('css/attendance-preview.css') }}" rel="stylesheet">
    <style>
        .card {
            opacity: 1 !important;
        }
    </style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Chart.js Configuration
    Chart.defaults.font.family = 'Segoe UI, Tahoma, Geneva, Verdana, sans-serif';
    Chart.defaults.color = '#475569';
    Chart.defaults.plugins.tooltip.rtl = true;
    Chart.defaults.plugins.tooltip.titleAlign = 'right';
    Chart.defaults.plugins.tooltip.bodyAlign = 'right';

    // Common Colors
    const colors = {
        present: {
            bg: 'rgba(34, 197, 94, 0.7)',
            border: 'rgba(34, 197, 94, 1)'
        },
        absent: {
            bg: 'rgba(239, 68, 68, 0.7)',
            border: 'rgba(239, 68, 68, 1)'
        },
        late: {
            bg: 'rgba(245, 158, 11, 0.7)',
            border: 'rgba(245, 158, 11, 1)'
        },
        primary: {
            bg: 'rgba(59, 130, 246, 0.7)',
            border: 'rgba(59, 130, 246, 1)'
        },
        weekend: {
            bg: 'rgba(139, 92, 246, 0.7)',
            border: 'rgba(139, 92, 246, 1)'
        }
    };

    // Common Chart Options
    const commonPieOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                rtl: true,
                labels: {
                    font: {
                        family: 'Segoe UI, Tahoma, Geneva, Verdana, sans-serif'
                    },
                    padding: 20
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.parsed || 0;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = total > 0 ? ((value * 100) / total).toFixed(1) : 0;
                        return `${label}: ${value} (${percentage}%)`;
                    }
                }
            }
        }
    };

    const commonBarOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'bottom',
                rtl: true,
                labels: {
                    font: {
                        family: 'Segoe UI, Tahoma, Geneva, Verdana, sans-serif'
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    font: {
                        family: 'Segoe UI, Tahoma, Geneva, Verdana, sans-serif'
                    }
                }
            },
            x: {
                ticks: {
                    font: {
                        family: 'Segoe UI, Tahoma, Geneva, Verdana, sans-serif'
                    }
                }
            }
        }
    };

    // Attendance Pie Chart
    const attendanceCtx = document.getElementById('attendanceChart')?.getContext('2d');
    if (attendanceCtx) {
        const presentDays = {{ $attendanceStats['present_days'] }};
        const absentDays = {{ $attendanceStats['absent_days'] }};
        const weekendDays = {{ $attendanceStats['weekend_days'] ?? 0 }};

        new Chart(attendanceCtx, {
            type: 'pie',
            data: {
                labels: ['أيام الحضور', 'أيام الغياب', 'عطلة أسبوعية'],
                datasets: [{
                    data: [presentDays, absentDays, weekendDays],
                    backgroundColor: [colors.present.bg, colors.absent.bg, colors.weekend.bg],
                    borderColor: [colors.present.border, colors.absent.border, colors.weekend.border],
                    borderWidth: 1
                }]
            },
            options: commonPieOptions
        });
    }

    // Late Details Chart
    const lateCtx = document.getElementById('lateChart')?.getContext('2d');
    if (lateCtx) {
        const lateDays = {{ $attendanceStats['late_days'] }};
        const totalDelayMinutes = {{ $attendanceStats['total_delay_minutes'] }};
        const earlyExitMinutes = {{ $attendanceStats['early_exit_minutes'] ?? 0 }};

        new Chart(lateCtx, {
            type: 'bar',
            data: {
                labels: ['مرات التأخير', 'دقائق التأخير', 'دقائق الخروج المبكر'],
                datasets: [{
                    label: 'التفاصيل',
                    data: [lateDays, totalDelayMinutes, earlyExitMinutes],
                    backgroundColor: [colors.late.bg, colors.primary.bg, colors.absent.bg],
                    borderColor: [colors.late.border, colors.primary.border, colors.absent.border],
                    borderWidth: 1
                }]
            },
            options: {
                ...commonBarOptions,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const index = context.dataIndex;
                                if (index === 0) {
                                    return `مرات التأخير: ${context.parsed.y}`;
                                } else if (index === 1) {
                                    return `دقائق التأخير: ${context.parsed.y} (${(context.parsed.y / 60).toFixed(1)} ساعة)`;
                                } else {
                                    return `دقائق الخروج المبكر: ${context.parsed.y} (${(context.parsed.y / 60).toFixed(1)} ساعة)`;
                                }
                            }
                        }
                    }
                }
            }
        });
    }

    // Monthly Comparison Chart
    const monthlyComparisonCtx = document.getElementById('monthlyComparisonChart')?.getContext('2d');
    if (monthlyComparisonCtx) {
        const months = [
            @foreach($threeMonthsStats as $monthStat)
            '{{ $monthStat['month'] }} {{ $monthStat['year'] }}',
            @endforeach
        ];

        const presentDays = [
            @foreach($threeMonthsStats as $monthStat)
            {{ $monthStat['present_days'] }},
            @endforeach
        ];

        const absentDays = [
            @foreach($threeMonthsStats as $monthStat)
            {{ $monthStat['absent_days'] }},
            @endforeach
        ];

        const attendancePercentages = [
            @foreach($threeMonthsStats as $monthStat)
            {{ round(($monthStat['present_days'] / ($monthStat['total_days'] ?: 1)) * 100) }},
            @endforeach
        ];

        new Chart(monthlyComparisonCtx, {
            type: 'bar',
            data: {
                labels: months,
                datasets: [
                    {
                        label: 'أيام الحضور',
                        data: presentDays,
                        backgroundColor: colors.present.bg,
                        borderColor: colors.present.border,
                        borderWidth: 1
                    },
                    {
                        label: 'أيام الغياب',
                        data: absentDays,
                        backgroundColor: colors.absent.bg,
                        borderColor: colors.absent.border,
                        borderWidth: 1
                    },
                    {
                        type: 'line',
                        label: 'نسبة الحضور %',
                        data: attendancePercentages,
                        backgroundColor: 'transparent',
                        borderColor: colors.primary.border,
                        borderWidth: 2,
                        pointBackgroundColor: colors.primary.border,
                        pointRadius: 4,
                        yAxisID: 'percentage'
                    }
                ]
            },
            options: {
                ...commonBarOptions,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'عدد الأيام'
                        }
                    },
                    percentage: {
                        position: 'right',
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'النسبة المئوية %'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush

@section('content')
<div class="container-fluid px-4">
    <!-- Header Section with Enhanced Design -->
    <div class="header-section mb-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="page-title position-relative">
                    <div class="title-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <h3 class="text-gradient mb-2">تقرير الحضور والانصراف</h3>
                    <p class="text-muted mb-0">تفاصيل سجل الحضور والانصراف للموظف</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="d-flex justify-content-md-end mt-3 mt-md-0">
                    <!-- Filter Section -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body">
                            <form action="" method="GET" class="row g-3">
                                <div class="col-md-2">
                                    <label class="form-label">من تاريخ</label>
                                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">إلى تاريخ</label>
                                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">الشهر</label>
                                    <select name="month" class="form-select">
                                        <option value="">كل الشهور</option>
                                        @foreach(range(1, 12) as $monthNumber)
                                        @php
                                        $monthName = Carbon\Carbon::create()->month($monthNumber)->translatedFormat('F');
                                        @endphp
                                        <option value="{{ $monthNumber }}" {{ request('month') == $monthNumber ? 'selected' : '' }}>
                                            {{ $monthName }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">السنة</label>
                                    <select name="year" class="form-select">
                                        <option value="">كل السنوات</option>
                                        @foreach(range(now()->year - 2, now()->year) as $year)
                                        <option value="{{ $year }}" {{ request('year') == $year ? 'selected' : '' }}>
                                            {{ $year }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">الحالة</label>
                                    <select name="status" class="form-select">
                                        <option value="">كل الحالات</option>
                                        <option value="حضـور" {{ request('status') == 'حضـور' ? 'selected' : '' }}>حضور</option>
                                        <option value="غيــاب" {{ request('status') == 'غيــاب' ? 'selected' : '' }}>غياب</option>
                                        <option value="عطله إسبوعية" {{ request('status') == 'عطله إسبوعية' ? 'selected' : '' }}>عطلة أسبوعية</option>
                                    </select>
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-filter me-1"></i>
                                            تصفية
                                        </button>
                                        @if(request()->hasAny(['month', 'year', 'status', 'start_date', 'end_date']))
                                        <a href="{{ route('user.previewAttendance', ['employee_id' => $user->employee_id]) }}" class="btn btn-light">
                                            <i class="fas fa-times me-1"></i>
                                            مسح الفلتر
                                        </a>
                                        @endif
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Employee Info Card -->
    <div class="row">
        <div class="col-md-12">
            <div class="card employee-card border-0 shadow-hover mb-4">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <div class="avatar-circle-lg">
                                {{ substr($user->name, 0, 1) }}
                            </div>
                        </div>
                        <div class="col">
                            <h4 class="mb-2 employee-name">{{ $user->name }}</h4>
                            <div class="employee-info d-flex align-items-center flex-wrap">
                                <span class="badge bg-primary me-3 employee-badge">
                                    <i class="fas fa-id-card me-1"></i>
                                    {{ $user->employee_id }}
                                </span>
                                <span class="department me-3">
                                    <i class="fas fa-building me-1"></i>
                                    {{ $user->department }}
                                </span>
                                <span class="text-muted">
                                    <i class="fas fa-calendar me-1"></i>
                                    تاريخ التعيين: {{ $user->start_date_of_employment }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- إحصائيات الفترة الحالية (26-25) -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="alert alert-info">
                        <h6 class="mb-2">
                            إحصائيات الفترة من {{ $startDate->format('d/m/Y') }} إلى {{ $endDate->format('d/m/Y') }}
                            <div class="small text-muted">
                                شهر {{ $attendanceStats['period']['month'] }} {{ $attendanceStats['period']['year'] }}
                            </div>
                        </h6>
                        <div class="row text-center g-3">
                            <div class="col-md-3">
                                <div class="border-end">
                                    <h4 class="mb-0">{{ $attendanceStats['total_work_days'] }}</h4>
                                    <small>إجمالي أيام العمل</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border-end">
                                    <h4 class="mb-0">{{ $attendanceStats['present_days'] }}</h4>
                                    <small>أيام الحضور</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border-end">
                                    <h4 class="mb-0">{{ $attendanceStats['absent_days'] }}</h4>
                                    <small>أيام الغياب</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div>
                                    <h4 class="mb-0">{{ $attendanceStats['total_work_days'] > 0 ? round(($attendanceStats['present_days'] / $attendanceStats['total_work_days']) * 100, 1) : 0 }}%</h4>
                                    <small>نسبة الحضور</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- رسم بياني للحضور والغياب -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="chart-card">
                        <div class="card-header">
                            <h5><i class="fas fa-chart-pie me-2"></i>توزيع الحضور والغياب</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="attendanceChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="chart-card">
                        <div class="card-header">
                            <h5><i class="fas fa-chart-bar me-2"></i>تفاصيل التأخير</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="lateChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- إحصائيات آخر 3 شهور -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">إحصائيات آخر 3 شهور</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                @foreach($threeMonthsStats as $monthStat)
                                <div class="col-md-4">
                                    <div class="card h-100 border-0 shadow-sm">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <h5 class="card-title mb-0">{{ $monthStat['month'] }} {{ $monthStat['year'] }}</h5>
                                                <span class="badge bg-{{ $monthStat['present_days'] > $monthStat['absent_days'] ? 'success' : 'danger' }}">
                                                    {{ round(($monthStat['present_days'] / ($monthStat['total_days'] ?: 1)) * 100) }}%
                                                </span>
                                            </div>

                                            <div class="row text-center">
                                                <div class="col-4">
                                                    <div class="border-end">
                                                        <div class="fs-4 fw-bold text-secondary">{{ $monthStat['total_days'] }}</div>
                                                        <small class="text-muted d-block">إجمالي الأيام</small>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="border-end">
                                                        <div class="fs-4 fw-bold text-success">{{ $monthStat['present_days'] }}</div>
                                                        <small class="text-muted d-block">أيام الحضور</small>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div>
                                                        <div class="fs-4 fw-bold text-danger">{{ $monthStat['absent_days'] }}</div>
                                                        <small class="text-muted d-block">أيام الغياب</small>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Progress Bar -->
                                            <div class="mt-3">
                                                <div class="progress" style="height: 8px;">
                                                    <div class="progress-bar bg-success"
                                                        role="progressbar"
                                                        style="width: {{ ($monthStat['present_days'] / ($monthStat['total_days'] ?: 1)) * 100 }}%">
                                                    </div>
                                                    <div class="progress-bar bg-danger"
                                                        role="progressbar"
                                                        style="width: {{ ($monthStat['absent_days'] / ($monthStat['total_days'] ?: 1)) * 100 }}%">
                                                    </div>
                                                </div>
                                                <div class="d-flex justify-content-between mt-1">
                                                    <small class="text-success">الحضور</small>
                                                    <small class="text-danger">الغياب</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- رسم بياني لمقارنة الأشهر -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="chart-card">
                        <div class="card-header">
                            <h5><i class="fas fa-chart-line me-2"></i>مقارنة الحضور والغياب خلال الأشهر الثلاثة الماضية</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="monthlyComparisonChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- تفاصيل التأخير -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="stat-icon bg-warning-subtle">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-1">مرات التأخير</h6>
                                    <h3 class="mb-0">{{ $attendanceStats['late_days'] }}</h3>
                                    <small class="text-muted">
                                        إجمالي {{ round($attendanceStats['total_delay_minutes'] / 60, 1) }} ساعة تأخير
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- بطاقات الإحصائيات التفصيلية -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-card border-0 shadow-hover">
                        <div class="stat-icon bg-success-subtle pulse-success">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="stat-details">
                            <h6>أيام الحضور</h6>
                            <h3>{{ $attendanceStats['present_days'] }}</h3>
                            <small class="text-muted">من أصل {{ $attendanceStats['present_days'] + $attendanceStats['absent_days'] }} يوم</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card border-0 shadow-hover">
                        <div class="stat-icon bg-danger-subtle pulse-danger">
                            <i class="fas fa-times"></i>
                        </div>
                        <div class="stat-details">
                            <h6>أيام الغياب</h6>
                            <h3>{{ $attendanceStats['absent_days'] }}</h3>
                            <small class="text-muted">من أصل {{ $attendanceStats['present_days'] + $attendanceStats['absent_days'] }} يوم</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card border-0 shadow-hover">
                        <div class="stat-icon bg-warning-subtle pulse-warning">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-details">
                            <h6>مرات التأخير</h6>
                            <h3>{{ $attendanceStats['late_days'] }}</h3>
                            <small class="text-muted">{{ $attendanceStats['total_delay_minutes'] }} دقيقة تأخير</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card border-0 shadow-hover">
                        <div class="stat-icon bg-info-subtle pulse-info">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="stat-details">
                            <h6>المخالفات</h6>
                            <h3>{{ $attendanceStats['violation_days'] }}</h3>
                            <small class="text-muted">عدد أيام المخالفات</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- جدول سجلات الحضور -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">سجل الحضور والانصراف</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>التاريخ</th>
                                    <th>اليوم</th>
                                    <th>الحالة</th>
                                    <th>الوردية</th>
                                    <th>ساعات الوردية</th>
                                    <th>وقت الحضور</th>
                                    <th>وقت الانصراف</th>
                                    <th>التأخير (دقيقة)</th>
                                    <th>الخروج المبكر (دقيقة)</th>
                                    <th>ساعات العمل</th>
                                    <th>الوقت الإضافي</th>
                                    <th>الجزاء</th>
                                    <th>ملاحظات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($attendanceRecords as $record)
                                <tr>
                                    <td>{{ $record->attendance_date }}</td>
                                    <td>{{ $record->day }}</td>
                                    <td>{{ $record->status }}</td>
                                    <td>{{ $record->shift }}</td>
                                    <td>{{ $record->shift_hours }}</td>
                                    <td>{{ $record->entry_time ?? '-' }}</td>
                                    <td>{{ $record->exit_time ?? '-' }}</td>
                                    <td>
                                        @if($record->delay_minutes > 0)
                                        <span class="text-danger">{{ $record->delay_minutes }}</span>
                                        @else
                                        -
                                        @endif
                                    </td>
                                    <td>
                                        @if($record->early_minutes > 0)
                                        <span class="text-warning">{{ $record->early_minutes }}</span>
                                        @else
                                        -
                                        @endif
                                    </td>
                                    <td>{{ $record->working_hours ?? '-' }}</td>
                                    <td>
                                        @if($record->overtime_hours > 0)
                                        <span class="text-success">{{ $record->overtime_hours }}</span>
                                        @else
                                        -
                                        @endif
                                    </td>
                                    <td>
                                        @if($record->penalty > 0)
                                        <span class="text-danger">{{ $record->penalty }}</span>
                                        @else
                                        -
                                        @endif
                                    </td>
                                    <td>
                                        @if($record->notes)
                                        <span class="text-muted">{{ $record->notes }}</span>
                                        @else
                                        -
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="13" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-inbox fa-3x mb-3"></i>
                                            <p>لا توجد سجلات متاحة</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-center py-3">
                            {{ $attendanceRecords->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // تفعيل tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });

    // إزالة قيود التاريخ
    document.addEventListener('DOMContentLoaded', function() {
        const dateInputs = document.querySelectorAll('input[type="date"]');
        dateInputs.forEach(input => {
            // إزالة أي قيود
            input.removeAttribute('min');
            input.removeAttribute('max');

            // منع أي أحداث JavaScript تقيد اختيار التاريخ
            input.addEventListener('mousedown', function(e) {
                e.stopPropagation();
            }, true);
        });
    });
</script>
@endpush
