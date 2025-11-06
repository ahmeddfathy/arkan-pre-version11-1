@extends('layouts.app')

@section('title', 'تقارير الموظفين')

@push('styles')
<!-- External Libraries -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.css">

<!-- Employee Reports Styles -->
<link rel="stylesheet" href="{{ asset('css/enhanced-employee-reports.css') }}">
<link rel="stylesheet" href="{{ asset('css/employee-reports-main.css') }}">
<link rel="stylesheet" href="{{ asset('css/employee-reports-timeline.css') }}">
<link rel="stylesheet" href="{{ asset('css/employee-reports-custom.css') }}">
@endpush

@section('content')
<div class="container-fluid">
    <div class="page-header">
        <h2>تقارير الموظفين</h2>
    </div>

    <div class="card filter-card mb-4">
        <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-bottom: 2px solid #5a6fd8;">
            <h5 style="color: white; margin: 0;"><i class="fas fa-filter"></i> تصفية النتائج</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('employee-reports.index') }}" method="GET" class="row">
                @if($isManager)
                <div class="col-md-3 mb-3">
                    <label for="employee_id" class="form-label">الموظف:</label>
                    <select name="employee_id" id="employee_id" class="form-control">
                        <option value="">كل الموظفين</option>
                        @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" {{ $selectedEmployeeId == $employee->id ? 'selected' : '' }}>
                            {{ $employee->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-3 mb-3">
                    <label for="date_range" class="form-label">التاريخ:</label>
                    <input type="date" name="selected_date" id="selected_date" class="form-control"
                        value="{{ $selectedDate ? $selectedDate->format('Y-m-d') : \Carbon\Carbon::today()->format('Y-m-d') }}">
                </div>
                <div class="col-12 text-center">
                    <button type="submit" class="btn btn-primary px-5">
                        <i class="fas fa-search"></i> بحث
                    </button>
                    <a href="{{ route('employee-reports.index') }}" class="btn btn-secondary px-5 mr-2">
                        <i class="fas fa-sync-alt"></i> إعادة تعيين
                    </a>
                </div>
            </form>
        </div>
    </div>

    @if($selectedEmployeeId && Auth::id() == $selectedEmployeeId)
    <div class="card mb-4 shadow-sm" style=" border-radius: 10px; overflow: hidden;">
        <div class="card-header text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-bottom: 2px solid #5a6fd8;">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-white"><i class="fas fa-clipboard-list text-white"></i > التقرير اليومي</h5>
                <span class="badge badge-light text-white">{{ $selectedDate ? $selectedDate->format('d/m/Y') : \Carbon\Carbon::today()->format('d/m/Y') }}</span>
            </div>
        </div>
        <div class="card-body" style="background: linear-gradient(135deg, rgba(102, 126, 234, 0.02) 0%, rgba(118, 75, 162, 0.02) 100%);">
            <form id="dailyReportForm">
                @csrf
                <input type="hidden" name="report_date" value="{{ $selectedDate ? $selectedDate->format('Y-m-d') : \Carbon\Carbon::today()->format('Y-m-d') }}">
                <div class="form-group">
                    <label for="daily_work" class="font-weight-bold" style="color: #667eea;">
                        <i class="fas fa-pencil-alt"></i> ما الذي قمت به اليوم؟
                    </label>
                    <textarea
                        class="form-control"
                        id="daily_work"
                        name="daily_work"
                        rows="6"
                        placeholder=""
                        required
                        maxlength="5000"
                        style="border: 2px solid #e0e0e0; border-radius: 8px; resize: vertical; min-height: 150px;">{{ $dailyReport ? $dailyReport->daily_work : '' }}</textarea>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i> اكتب تقريراً مفصلاً عن إنجازاتك اليومية
                        </small>
                        <small class="form-text text-muted">
                            <span id="charCount" style="font-weight: bold; color: #667eea;">{{ $dailyReport ? strlen($dailyReport->daily_work) : 0 }}</span> / 5000 حرف
                        </small>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <button type="submit" class="btn btn-primary btn-lg" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; padding: 10px 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(102, 126, 234, 0.3);">
                        <i class="fas fa-save"></i> حفظ التقرير
                    </button>
                    @if($dailyReport)
                    <small class="text-muted" style="background: #f8f9fa; padding: 8px 15px; border-radius: 20px; border: 1px solid #e0e0e0;">
                        <i class="fas fa-check-circle text-success"></i> آخر تحديث: {{ $dailyReport->updated_at->diffForHumans() }}
                    </small>
                    @endif
                </div>
                <div id="reportMessage" class="mt-3" style="display: none;"></div>
            </form>
        </div>
    </div>
    @endif

    <div class="row">
        <div class="col-lg-3">
            <div class="card employee-list-card">
                <div class="card-header">
                    <div class="input-group">
                        <input type="text" class="form-control" id="employeeSearch" placeholder="بحث عن موظف...">
                        <div class="input-group-append">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="employee-list" id="employeeList">
                        @foreach($employees as $employee)
                        <a href="{{ route('employee-reports.index', ['employee_id' => $employee->id, 'selected_date' => $selectedDate ? $selectedDate->format('Y-m-d') : \Carbon\Carbon::today()->format('Y-m-d')]) }}"
                            class="employee-item {{ $selectedEmployeeId == $employee->id ? 'active' : '' }}"
                            data-employee-name="{{ strtolower($employee->name) }}"
                            data-employee-id="{{ $employee->id }}">
                            <div class="employee-info">
                                <h6>{{ $employee->name }}</h6>
                                <div class="small text-muted">{{ $employee->job_title ?? 'موظف' }}</div>
                            </div>
                        </a>
                        @endforeach

                        @if($employees->count() == 0)
                        <div class="text-center p-4 text-muted">
                            <i class="fas fa-users-slash fa-2x mb-2"></i>
                            <p>لا توجد موظفين متاحين</p>
                        </div>
                        @endif

                        @if($employees->count() > 50)
                        <div class="employee-list-footer text-center p-3 border-top">
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i>
                                يتم عرض {{ $employees->count() }} موظف. استخدم البحث لتصفية النتائج.
                            </small>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            @if($selectedEmployeeId)
            <div class="card">
                <div class="card-header">
                    <div>
                        @php
                        $selectedEmployee = $employees->where('id', $selectedEmployeeId)->first();
                        @endphp
                        <h5>
                            تقرير {{ $selectedEmployee ? $selectedEmployee->name : 'الموظف' }}
                        </h5>
                        <small class="text-muted">
                            {{ $selectedEmployee ? $selectedEmployee->job_title ?? 'موظف' : '' }}
                            {{ $selectedEmployee ? '| ' . $selectedEmployee->email : '' }}
                        </small>
                    </div>
                    <div>
                        <span class="badge badge-info">{{ $selectedDate ? $selectedDate->format('Y-m-d') : \Carbon\Carbon::today()->format('Y-m-d') }}</span>
                    </div>
                </div>
                <div class="card-body">
                    @if(count($taskData) > 0 || ($timeLogsData && count($timeLogsData['logs']) > 0) || ($revisionsData && count($revisionsData['revisions']) > 0) || ($meetingsData && count($meetingsData['meetings']) > 0) || $absenceData || ($permissionData && count($permissionData['requests']) > 0) || ($deliveredProjectsData && count($deliveredProjectsData['projects']) > 0))
                    <!-- Time Logs Timeline -->
                    @if($timeLogsData && count($timeLogsData['logs']) > 0)
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header text-white" style="background: linear-gradient(135deg, #36d1dc 0%, #5b86e5 100%); border-bottom: 2px solid #3bc9db;">
                                    <h5 class="mb-0"><i class="fas fa-clock"></i> جلسات العمل الفعلية</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-md-3 col-6">
                                            <div class="text-center">
                                                <div class="h4 text-primary mb-1">{{ $timeLogsData['sessions_count'] }}</div>
                                                <div class="small text-muted">جلسات العمل</div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-6">
                                            <div class="text-center">
                                                <div class="h4 text-success mb-1">{{ $timeLogsData['total_formatted'] }}</div>
                                                <div class="small text-muted">إجمالي الوقت</div>
                                            </div>
                                        </div>
                                        <div class="col-md-2 col-6">
                                            <div class="text-center">
                                                <div class="h4 text-info mb-1">{{ count($timeLogsData['active_sessions']) }}</div>
                                                <div class="small text-muted">جلسات نشطة</div>
                                            </div>
                                        </div>
                                        <div class="col-md-2 col-6">
                                            <div class="text-center">
                                                <div class="h4 {{ ($deliveredProjectsData && $deliveredProjectsData['total_projects'] > 0) ? 'text-success' : 'text-muted' }} mb-1">
                                                    {{ $deliveredProjectsData ? $deliveredProjectsData['total_projects'] : 0 }}
                                                </div>
                                                <div class="small text-muted">مشاريع مسلمة</div>
                                            </div>
                                        </div>
                                        <div class="col-md-2 col-12">
                                            <div class="text-center">
                                                <div class="h4 text-warning mb-1">{{ $selectedDate->format('d/m/Y') }}</div>
                                                <div class="small text-muted">التاريخ</div>
                                                @if($selectedDate->isToday())
                                                <div class="badge badge-success badge-pulse mt-1">مباشر</div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>



                                    <!-- Real-Time Timeline -->
                                    @if($shiftData && isset($shiftData['timeline']) && count($shiftData['timeline']) > 0 && isset($shiftData['shift_start']) && isset($shiftData['shift_end']))
                                    <div class="mt-4">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="mb-0"><i class="fas fa-chart-line"></i> الجدول الزمني الحقيقي ({{ $shiftData['shift_start'] }} - {{ $shiftData['shift_end'] }})</h6>
                                            <div class="timeline-legend">
                                                <div class="legend-item">
                                                    <div class="legend-color" style="background: #28a745;"></div>
                                                    <span>عمل فعلي</span>
                                                </div>
                                                <div class="legend-item">
                                                    <div class="legend-color" style="background: #dc3545;"></div>
                                                    <span>وقت فراغ</span>
                                                </div>
                                                <div class="legend-item">
                                                    <div class="legend-color" style="background: #f093fb;"></div>
                                                    <span>تعديل</span>
                                                </div>
                                                <div class="legend-item">
                                                    <div class="legend-color" style="background: #6c757d;"></div>
                                                    <span>لم يحن بعد</span>
                                                </div>
                                                @if($selectedDate->isToday())
                                                <div class="legend-item">
                                                    <div style="width: 3px; height: 12px; background: #ffc107;"></div>
                                                    <span>الوقت الحالي</span>
                                                </div>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Timeline Container -->
                                        <div class="real-time-timeline-container" style="background: #f8f9fa; border-radius: 12px; padding: 20px; overflow-x: auto;">
                                            @php
                                            // تجميع الدقائق حسب الساعات
                                            $hourlyMinutes = [];
                                            foreach($shiftData['timeline'] as $index => $minute) {
                                            $hour = substr($minute['start'], 0, 2);
                                            $hourlyMinutes[$hour][] = $minute + ['index' => $index];
                                            }
                                            @endphp

                                            @foreach($hourlyMinutes as $hour => $minutes)
                                            <!-- Hour Row -->
                                            <div class="hour-row mb-4" style="border-bottom: 2px solid #dee2e6; padding-bottom: 15px;">
                                                <!-- Hour Header -->
                                                <div class="hour-header mb-3" style="display: flex; align-items: center; justify-content: space-between;">
                                                    <h6 style="margin: 0; font-weight: bold; color: #495057; font-size: 16px;">
                                                        <i class="fas fa-clock" style="margin-left: 8px; color: #007bff;"></i>
                                                        الساعة {{ $hour }}:00 - {{ $hour }}:59
                                                    </h6>
                                                    <div style="font-size: 12px; color: #6c757d;">
                                                        {{ count($minutes) }} دقيقة
                                                    </div>
                                                </div>

                                                <!-- Minutes Row for this Hour -->
                                                <div class="minutes-row" style="display: flex; gap: 3px; flex-wrap: wrap; padding: 10px; background: #fff; border-radius: 8px; border: 1px solid #e9ecef; position: relative;">
                                                    @foreach($minutes as $minute)
                                                    @php
                                                    $color = '#dc3545'; // أحمر للفراغ
                                                    $opacity = '1';
                                                    $tooltip = 'فراغ - ' . $minute['start'];
                                                    $borderColor = '#c82333';
                                                    $textColor = '#fff';
                                                    $statusIcon = 'fas fa-pause';

                                                    if ($minute['status'] === 'working') {
                                                    // تحديد اللون حسب نوع العمل (مهمة أو تعديل)
                                                    $isRevisionWork = isset($minute['is_revision_work']) && $minute['is_revision_work'];

                                                    if ($isRevisionWork) {
                                                    $color = '#f093fb'; // وردي للتعديلات
                                                    $borderColor = '#e066f0';
                                                    $statusIcon = 'fas fa-edit';
                                                    } else {
                                                    $color = '#28a745'; // أخضر للمهام العادية
                                                    $borderColor = '#1e7e34';
                                                    $statusIcon = 'fas fa-play';
                                                    }

                                                    $taskNames = array_column($minute['tasks'], 'name');
                                                    $tooltip = ($isRevisionWork ? 'تعديل: ' : 'عمل: ') . implode(', ', $taskNames) . ' - ' . $minute['start'];

                                                    // إذا كانت جلسة نشطة، أضف تأثير نبضة
                                                    if (isset($minute['is_active_session']) && $minute['is_active_session']) {
                                                    $tooltip .= ' (نشط الآن)';
                                                    $statusIcon = 'fas fa-circle';
                                                    }
                                                    } elseif ($minute['status'] === 'break') {
                                                    $color = '#ffc107'; // أصفر للبريك
                                                    $borderColor = '#e0a800';
                                                    $statusIcon = 'fas fa-coffee';
                                                    $tooltip = 'بريك - ' . $minute['start'];
                                                    } elseif ($minute['status'] === 'future') {
                                                    $color = '#6c757d'; // رمادي للمستقبل
                                                    $borderColor = '#5a6268';
                                                    $opacity = '0.6';
                                                    $statusIcon = 'fas fa-clock';
                                                    $tooltip = 'لم يحن بعد - ' . $minute['start'];
                                                    }

                                                    // مربعات أكبر
                                                    $boxWidth = '18px';
                                                    $boxHeight = '50px';
                                                    $fontSize = '10px';
                                                    @endphp

                                                    <div class="minute-box {{ $minute['status'] === 'working' && isset($minute['is_active_session']) && $minute['is_active_session'] ? 'active-pulse' : '' }}"
                                                        style="width: {{ $boxWidth }}; height: {{ $boxHeight }};
                                                                            background: {{ $color }};
                                                                            opacity: {{ $opacity }};
                                                                            border: 2px solid {{ $borderColor }};
                                                                            border-radius: 6px;
                                                                            position: relative;
                                                                            transition: all 0.3s ease;
                                                                            cursor: pointer;
                                                                            display: flex;
                                                                            align-items: center;
                                                                            justify-content: center;
                                                                            box-shadow: 0 2px 4px rgba(0,0,0,0.1);"
                                                        data-toggle="tooltip"
                                                        data-placement="top"
                                                        title="{{ $tooltip }}"
                                                        data-minute-index="{{ $minute['index'] }}"
                                                        data-status="{{ $minute['status'] }}"
                                                        data-time="{{ $minute['start'] }}"
                                                        data-hour="{{ $hour }}"
                                                        data-is-active="{{ isset($minute['is_active_session']) ? ($minute['is_active_session'] ? 'true' : 'false') : 'false' }}">

                                                        <!-- رقم الدقيقة -->
                                                        <span style="font-size: {{ $fontSize }}; color: {{ $textColor }}; font-weight: bold; text-align: center; line-height: 1;">
                                                            {{ substr($minute['start'], -2) }}
                                                        </span>
                                                    </div>
                                                    @endforeach

                                                    <!-- Current Time Indicator for this hour -->
                                                    @if($selectedDate->isToday() && $shiftData && isset($shiftData['shift_start']) && isset($shiftData['shift_end']))
                                                    @php
                                                    $now = \Carbon\Carbon::now();
                                                    $shiftStartTime = \Carbon\Carbon::parse($shiftData['shift_start']);
                                                    $shiftEndTime = \Carbon\Carbon::parse($shiftData['shift_end']);
                                                    $currentHour = $now->format('H');

                                                    if ($now->gte($shiftStartTime) && $now->lte($shiftEndTime) && $currentHour == $hour) {
                                                    $currentMinute = $now->format('i');
                                                    $currentTimeFormatted = $now->format('H:i');
                                                    $minutePosition = ((int)$currentMinute) * (18 + 3); // box width + gap
                                                    } else {
                                                    $minutePosition = null;
                                                    }
                                                    @endphp

                                                    @if(isset($minutePosition))
                                                    <div class="current-time-pointer"
                                                        style="position: absolute;
                                                                            top: -20px;
                                                                            left: {{ $minutePosition }}px;
                                                                            z-index: 15;">
                                                        <div style="background: #ffc107;
                                                                               color: #212529;
                                                                               padding: 6px 10px;
                                                                               border-radius: 8px;
                                                                               font-size: 12px;
                                                                               font-weight: bold;
                                                                               box-shadow: 0 4px 12px rgba(255, 193, 7, 0.5);
                                                                               animation: current-time-glow 2s infinite;
                                                                               position: relative;
                                                                               white-space: nowrap;">
                                                            <i class="fas fa-clock" style="margin-left: 6px;"></i>{{ $currentTimeFormatted }}
                                                            <div style="position: absolute;
                                                                                   top: 100%;
                                                                                   left: 50%;
                                                                                   transform: translateX(-50%);
                                                                                   width: 0;
                                                                                   height: 0;
                                                                                   border-left: 8px solid transparent;
                                                                                   border-right: 8px solid transparent;
                                                                                   border-top: 10px solid #ffc107;">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    @endif
                                                    @endif
                                                </div>
                                            </div>
                                            @endforeach

                                        </div>
                                    </div>

                                    <!-- Timeline Stats -->
                                    <div class="row mt-3">
                                        <div class="col-md-2">
                                            <div class="text-center">
                                                <div class="h5 text-success mb-1">{{ $shiftData['working_time']['formatted'] }}</div>
                                                <div class="small text-muted">وقت العمل الفعلي</div>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="text-center">
                                                <div class="h5 text-warning mb-1">{{ $shiftData['break_time']['formatted'] }}</div>
                                                <div class="small text-muted">وقت البريك</div>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="text-center">
                                                <div class="h5 text-danger mb-1">{{ $shiftData['idle_time']['formatted'] }}</div>
                                                <div class="small text-muted">وقت الفراغ</div>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="text-center">
                                                <div class="h5 text-info mb-1">{{ $shiftData['total_shift_duration']['formatted'] }}</div>
                                                <div class="small text-muted">إجمالي الشيفت</div>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="text-center">
                                                @php
                                                $efficiency = $shiftData['past_time']['minutes'] > 0
                                                ? round(($shiftData['working_time']['minutes'] / $shiftData['past_time']['minutes']) * 100)
                                                : 0;
                                                @endphp
                                                <div class="h5 {{ $efficiency >= 70 ? 'text-success' : ($efficiency >= 50 ? 'text-warning' : 'text-danger') }} mb-1">{{ $efficiency }}%</div>
                                                <div class="small text-muted">كفاءة العمل</div>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="text-center">
                                                @if($shiftData['has_break'])
                                                <div class="h5 text-primary mb-1">{{ $shiftData['break_start'] }} - {{ $shiftData['break_end'] }}</div>
                                                <div class="small text-muted">فترة البريك</div>
                                                @else
                                                <div class="h5 text-muted mb-1">لا يوجد</div>
                                                <div class="small text-muted">وقت البريك</div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                <!-- Time Logs List -->
                                <div class="mt-4">
                                    <h6 class="mb-3"><i class="fas fa-list"></i> تفاصيل جلسات العمل</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>المهمة</th>
                                                    <th>المشروع</th>
                                                    <th>النوع</th>
                                                    <th>بداية العمل</th>
                                                    <th>نهاية العمل</th>
                                                    <th>المدة</th>
                                                    <th>الحالة</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($timeLogsData['logs'] as $log)
                                                <tr class="{{ $log['is_active'] ? 'table-warning' : '' }}">
                                                    <td>{{ $log['task_name'] }}</td>
                                                    <td>
                                                        @if(isset($log['project_name']) && $log['project_name'])
                                                        <small class="text-muted">
                                                            {{ $log['project_name'] }}
                                                            @if(isset($log['project_delivered_at']) && $log['project_delivered_at'])
                                                            <i class="fas fa-check-circle text-success ml-1"
                                                                data-toggle="tooltip"
                                                                title="تم التسليم في {{ \Carbon\Carbon::parse($log['project_delivered_at'])->format('d/m/Y H:i') }}"></i>
                                                            @endif
                                                        </small>
                                                        @else
                                                        <small class="text-muted">-</small>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-{{ $log['task_type'] === 'regular' ? 'primary' : 'warning' }}">
                                                            {{ $log['task_type'] === 'regular' ? 'عادية' : 'قالب' }}
                                                        </span>
                                                    </td>
                                                    <td>{{ $log['start_time'] }}</td>
                                                    <td>{{ $log['end_time'] ?? 'مستمر' }}</td>
                                                    <td>{{ $log['duration_formatted'] }}</td>
                                                    <td>
                                                        @if($log['is_active'])
                                                        <span class="badge badge-success">
                                                            <i class="fas fa-play"></i> نشط
                                                        </span>
                                                        @else
                                                        <span class="badge badge-secondary">
                                                            <i class="fas fa-stop"></i> منتهي
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
                        </div>
                    </div>
                </div>
                @else
                <!-- رسالة عدم وجود time logs -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card border-warning">
                            <div class="card-header text-white" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); border-bottom: 2px solid #f67280;">
                                <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> لا توجد جلسات عمل مسجلة</h5>
                            </div>
                            <div class="card-body text-center">
                                <i class="fas fa-clock fa-3x text-muted mb-3"></i>
                                <h5>لم يتم تسجيل أي أوقات عمل لهذا اليوم</h5>
                                <p class="text-muted mb-0">
                                    سيتم تسجيل الأوقات تلقائياً عند بدء العمل على المهام
                                    <br>
                                    <small>تأكد من تغيير حالة المهمة إلى "قيد التنفيذ" لبدء تسجيل الوقت</small>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Absence and Permission Requests Section -->
                @if($absenceData || $permissionData)
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header text-white" style="background: linear-gradient(135deg, #ff7b7b 0%, #667eea 100%); border-bottom: 2px solid #ff6b6b;">
                                <h5 class="mb-0"><i class="fas fa-calendar-times"></i> الإجازات وطلبات الأذون</h5>
                            </div>
                            <div class="card-body">

                                @if($absenceData)
                                <!-- Absence Request -->
                                <div class="alert alert-warning border-left-warning shadow" style="border-left: 4px solid #f4a261; background: linear-gradient(135deg, rgba(244, 162, 97, 0.1) 0%, rgba(244, 162, 97, 0.05) 100%);">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0 ml-3">
                                            <i class="fas fa-calendar-times fa-2x" style="color: #f4a261;"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-2" style="color: #d4761a; font-weight: bold;">
                                                <i class="fas fa-exclamation-circle ml-2"></i>إجازة متوافق عليها
                                            </h6>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <p class="mb-1"><strong>السبب:</strong> {{ $absenceData['reason'] }}</p>
                                                    <p class="mb-1"><strong>التاريخ:</strong> {{ \Carbon\Carbon::parse($absenceData['date'])->format('d/m/Y') }}</p>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="d-flex justify-content-end">
                                                        <div class="badge-group">
                                                            @if($absenceData['manager_status'] === 'approved')
                                                            <span class="badge badge-success ml-1">موافقة المدير</span>
                                                            @endif
                                                            @if($absenceData['hr_status'] === 'approved')
                                                            <span class="badge badge-success">موافقة الموارد البشرية</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div class="text-right mt-2">
                                                        <small class="text-muted">تم الطلب: {{ $absenceData['created_at']->format('d/m/Y - H:i') }}</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                @if($permissionData)
                                <!-- Permission Requests -->
                                <div class="permission-requests-section">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="mb-0" style="color: #667eea; font-weight: bold;">
                                            <i class="fas fa-clock ml-2"></i>طلبات الأذون ({{ $permissionData['total_requests'] }})
                                        </h6>
                                        <div class="badge badge-info" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                            إجمالي الوقت: {{ $permissionData['total_time_formatted'] }}
                                        </div>
                                    </div>

                                    @foreach($permissionData['requests'] as $permission)
                                    <div class="permission-item border rounded p-3 mb-3" style="border-left: 4px solid #667eea; background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.02) 100%);">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <div class="permission-info">
                                                    <h6 class="mb-2" style="color: #4c63d2;">
                                                        <i class="fas fa-sign-out-alt ml-2"></i>إذن من {{ $permission['departure_time_formatted'] }} إلى {{ $permission['return_time_formatted'] }}
                                                    </h6>
                                                    <p class="mb-2"><strong>السبب:</strong> {{ $permission['reason'] }}</p>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <span class="badge badge-primary">{{ $permission['minutes_formatted'] }}</span>
                                                        </div>
                                                        <div class="col-md-6">
                                                            @if($permission['returned_on_time'] === 1 || $permission['returned_on_time'] === true)
                                                            <span class="badge badge-success">عاد في الوقت المحدد</span>
                                                            @elseif($permission['returned_on_time'] === 2)
                                                            <span class="badge badge-danger">لم يعد في الوقت المحدد</span>
                                                            @else
                                                            <span class="badge badge-warning">غير محدد</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="text-right">
                                                    <div class="approval-badges mb-2">
                                                        @if($permission['manager_status'] === 'approved')
                                                        <span class="badge badge-success badge-sm">موافقة المدير</span><br>
                                                        @endif
                                                        @if($permission['hr_status'] === 'approved')
                                                        <span class="badge badge-success badge-sm">موافقة الموارد البشرية</span>
                                                        @endif
                                                    </div>
                                                    <small class="text-muted">طُلب: {{ $permission['created_at']->format('H:i') }}</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                @endif

                            </div>
                        </div>
                    </div>
                </div>
                @endif

                @if($dailyReport)
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card" style="border-left: 4px solid #667eea;">
                            <div class="card-header text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-bottom: 2px solid #5a6fd8;">
                                <h5 class="mb-0"><i class="fas fa-clipboard-list"></i> التقرير اليومي للموظف</h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info mb-0" style="background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.05) 100%); border: 1px solid #667eea;">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 style="color: #667eea;"><i class="fas fa-user-edit"></i> ما قام به الموظف:</h6>
                                        <small class="text-muted">
                                            <i class="fas fa-clock"></i> تم التحديث: {{ $dailyReport->updated_at->format('d/m/Y H:i') }}
                                        </small>
                                    </div>
                                    <div style="white-space: pre-wrap; padding: 15px; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                                        {{ $dailyReport->daily_work }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Task Revisions Section -->
                @if($revisionsData && count($revisionsData['revisions']) > 0)
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header text-white" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); border-bottom: 2px solid #f093fb;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><i class="fas fa-edit"></i> التعديلات</h5>
                                    <div>
                                        <span class="badge badge-light">{{ $revisionsData['total_revisions'] }} تعديل</span>
                                        @if($revisionsData['active_revision'])
                                        <span class="badge badge-warning badge-pulse">جلسة نشطة</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <!-- Revisions Summary -->
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <div class="text-center p-3 rounded" style="background: linear-gradient(135deg, rgba(240, 147, 251, 0.1) 0%, rgba(245, 87, 108, 0.1) 100%);">
                                            <div class="h4 mb-1" style="color: #f5576c;">{{ $revisionsData['total_revisions'] }}</div>
                                            <div class="small text-muted">إجمالي التعديلات</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="text-center p-3 rounded" style="background: linear-gradient(135deg, rgba(240, 147, 251, 0.1) 0%, rgba(245, 87, 108, 0.1) 100%);">
                                            <div class="h4 mb-1" style="color: #f093fb;">{{ $revisionsData['total_time_formatted'] }}</div>
                                            <div class="small text-muted">إجمالي الوقت</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="text-center p-3 rounded" style="background: linear-gradient(135deg, rgba(240, 147, 251, 0.1) 0%, rgba(245, 87, 108, 0.1) 100%);">
                                            <div class="h4 mb-1" style="color: #9b51e0;">{{ $selectedDate->format('d/m/Y') }}</div>
                                            <div class="small text-muted">التاريخ</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Revisions List -->
                                <div class="revisions-list mt-4">
                                    @foreach($revisionsData['revisions'] as $revision)
                                    <div class="revision-item border rounded p-3 mb-3"
                                        style="border-right: 4px solid {{ $revision['is_active'] ? '#28a745' : '#f5576c' }};
                                                            background: linear-gradient(135deg, rgba(240, 147, 251, 0.03) 0%, rgba(245, 87, 108, 0.01) 100%);">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <div class="revision-header mb-2">
                                                    <h6 class="mb-1" style="color: #f5576c;">
                                                        <i class="fas fa-edit ml-2"></i>{{ $revision['title'] }}
                                                        @if($revision['is_active'])
                                                        <span class="badge badge-success badge-sm badge-pulse mr-2">نشط</span>
                                                        @endif
                                                    </h6>
                                                    @if($revision['description'])
                                                    <p class="text-muted small mb-2">{{ $revision['description'] }}</p>
                                                    @endif
                                                </div>
                                                <div class="revision-details">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <small><strong>المهمة:</strong> {{ $revision['task_name'] }}</small>
                                                        </div>
                                                        @if($revision['project'])
                                                        <div class="col-md-6">
                                                            <small><strong>المشروع:</strong> {{ $revision['project']->name }}</small>
                                                        </div>
                                                        @endif
                                                    </div>
                                                    <div class="row mt-2">
                                                        <div class="col-md-6">
                                                            <span class="badge badge-{{ $revision['status_color'] }}">{{ $revision['status_text'] }}</span>
                                                            <span class="badge badge-info">{{ $revision['revision_type_text'] }}</span>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <span class="badge badge-secondary">{{ $revision['revision_source_text'] }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="text-left">
                                                    <div class="mb-2">
                                                        <div class="h5 mb-0" style="color: #f5576c;">{{ $revision['actual_time_formatted'] }}</div>
                                                        <small class="text-muted">الوقت المستغرق</small>
                                                    </div>
                                                    @if($revision['started_at'])
                                                    <div class="mb-1">
                                                        <small class="text-muted">
                                                            <i class="fas fa-play"></i> بدأ: {{ \Carbon\Carbon::parse($revision['started_at'])->format('H:i') }}
                                                        </small>
                                                    </div>
                                                    @endif
                                                    @if($revision['completed_at'])
                                                    <div>
                                                        <small class="text-muted">
                                                            <i class="fas fa-check"></i> اكتمل: {{ \Carbon\Carbon::parse($revision['completed_at'])->format('H:i') }}
                                                        </small>
                                                    </div>
                                                    @endif
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
                @endif

                <!-- Meetings Section -->
                @if($meetingsData && count($meetingsData['meetings']) > 0)
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-bottom: 2px solid #5a6fd8;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><i class="fas fa-users"></i> الاجتماعات</h5>
                                    <div>
                                        <span class="badge badge-light">{{ $meetingsData['total_meetings'] }} اجتماع</span>
                                        @if($meetingsData['current_count'] > 0)
                                        <span class="badge badge-warning badge-pulse">{{ $meetingsData['current_count'] }} جارية</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <!-- Meetings Summary -->
                                <div class="row mb-3">
                                    <div class="col-md-3">
                                        <div class="text-center p-3 rounded" style="background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);">
                                            <div class="h4 mb-1" style="color: #667eea;">{{ $meetingsData['total_meetings'] }}</div>
                                            <div class="small text-muted">إجمالي الاجتماعات</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center p-3 rounded" style="background: linear-gradient(135deg, rgba(23, 162, 184, 0.1) 0%, rgba(23, 162, 184, 0.05) 100%);">
                                            <div class="h4 mb-1" style="color: #17a2b8;">{{ $meetingsData['upcoming_count'] }}</div>
                                            <div class="small text-muted">قادمة</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center p-3 rounded" style="background: linear-gradient(135deg, rgba(255, 193, 7, 0.1) 0%, rgba(255, 193, 7, 0.05) 100%);">
                                            <div class="h4 mb-1" style="color: #ffc107;">{{ $meetingsData['current_count'] }}</div>
                                            <div class="small text-muted">جارية الآن</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center p-3 rounded" style="background: linear-gradient(135deg, rgba(40, 167, 69, 0.1) 0%, rgba(40, 167, 69, 0.05) 100%);">
                                            <div class="h4 mb-1" style="color: #28a745;">{{ $meetingsData['completed_count'] }}</div>
                                            <div class="small text-muted">مكتملة</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Meetings List -->
                                <div class="meetings-list mt-4">
                                    @foreach($meetingsData['meetings'] as $meeting)
                                    <div class="meeting-item border rounded p-3 mb-3"
                                        style="border-right: 4px solid {{ $meeting['status'] === 'current' ? '#ffc107' : ($meeting['status'] === 'completed' ? '#28a745' : '#17a2b8') }};
                                                            background: linear-gradient(135deg, rgba(102, 126, 234, 0.03) 0%, rgba(118, 75, 162, 0.01) 100%);">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <div class="meeting-header mb-2">
                                                    <h6 class="mb-1" style="color: #667eea;">
                                                        <i class="fas fa-video ml-2"></i>{{ $meeting['title'] }}
                                                        @if($meeting['status'] === 'current')
                                                        <span class="badge badge-warning badge-sm badge-pulse mr-2">جارية الآن</span>
                                                        @endif
                                                        @if($meeting['is_creator'])
                                                        <span class="badge badge-primary badge-sm mr-2">منشئ</span>
                                                        @endif
                                                    </h6>
                                                    @if($meeting['description'])
                                                    <p class="text-muted small mb-2">{{ $meeting['description'] }}</p>
                                                    @endif
                                                </div>
                                                <div class="meeting-details">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <small><strong>النوع:</strong>
                                                                <span class="badge badge-{{ $meeting['type'] === 'client' ? 'success' : 'info' }}">
                                                                    {{ $meeting['type_text'] }}
                                                                </span>
                                                            </small>
                                                        </div>
                                                        @if($meeting['client'])
                                                        <div class="col-md-6">
                                                            <small><strong>العميل:</strong> {{ $meeting['client']->name }}</small>
                                                        </div>
                                                        @endif
                                                    </div>
                                                    <div class="row mt-2">
                                                        <div class="col-md-6">
                                                            <small><strong>الوقت:</strong> {{ $meeting['start_time_formatted'] }} - {{ $meeting['end_time_formatted'] }}</small>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <small><strong>المدة:</strong> {{ $meeting['duration_formatted'] }}</small>
                                                        </div>
                                                    </div>
                                                    @if($meeting['location'])
                                                    <div class="row mt-1">
                                                        <div class="col-md-12">
                                                            <small><strong>المكان:</strong> <i class="fas fa-map-marker-alt"></i> {{ $meeting['location'] }}</small>
                                                        </div>
                                                    </div>
                                                    @endif
                                                    <div class="row mt-2">
                                                        <div class="col-md-6">
                                                            <span class="badge badge-{{ $meeting['status_badge'] }}">{{ $meeting['status_text'] }}</span>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <small><i class="fas fa-users"></i> {{ $meeting['participants_count'] }} مشارك</small>
                                                        </div>
                                                    </div>
                                                    @if($meeting['attended'])
                                                    <div class="mt-2">
                                                        <span class="badge badge-success"><i class="fas fa-check-circle"></i> حضر</span>
                                                    </div>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="text-left">
                                                    <div class="mb-2">
                                                        <div class="h5 mb-0" style="color: #667eea;">{{ $meeting['duration_formatted'] }}</div>
                                                        <small class="text-muted">مدة الاجتماع</small>
                                                    </div>
                                                    <div class="mb-1">
                                                        <small class="text-muted">
                                                            <i class="fas fa-clock"></i> {{ $meeting['start_time_formatted'] }} - {{ $meeting['end_time_formatted'] }}
                                                        </small>
                                                    </div>
                                                    @if($meeting['creator'])
                                                    <div>
                                                        <small class="text-muted">
                                                            <i class="fas fa-user"></i> منشئ: {{ $meeting['creator']->name }}
                                                        </small>
                                                    </div>
                                                    @endif
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
                @endif

                @if(count($taskData) > 0)
                <div class="row mb-4">
                    <div class="col-md-12">
                        <h5 class="mb-4" style="color: #4a90e2; border-bottom: 2px solid #4a90e2; padding-bottom: 8px; background: linear-gradient(135deg, rgba(74, 144, 226, 0.1) 0%, rgba(74, 144, 226, 0.05) 100%); padding: 12px; border-radius: 8px;">
                            <i class="fas fa-chart-bar" style="margin-left: 8px;"></i>ملخص المهام
                        </h5>
                    </div>
                    @php
                    $totalHours = 0;
                    $totalMinutes = 0;
                    $regularTasksCount = 0;
                    $templateTasksCount = 0;

                    foreach ($taskData as $data) {
                    $totalHours += $data['totalTime']['hours'];
                    $totalMinutes += $data['totalTime']['minutes'];

                    if (isset($data['type']) && $data['type'] == 'template') {
                    $templateTasksCount++;
                    } else {
                    $regularTasksCount++;
                    }
                    }

                    $totalHours += intdiv($totalMinutes, 60);
                    $totalMinutes = $totalMinutes % 60;

                    $formattedTotal = ($totalHours > 0 ? $totalHours . 'h ' : '') . ($totalMinutes > 0 ? $totalMinutes . 'm' : ($totalHours > 0 ? '' : '0m'));

                    $completedTasks = 0;
                    $inProgressTasks = 0;
                    $pausedTasks = 0;
                    $newTasks = 0;
                    $cancelledTasks = 0;

                    foreach ($taskData as $data) {
                    switch ($data['status']) {
                    case 'completed':
                    $completedTasks++;
                    break;
                    case 'in_progress':
                    $inProgressTasks++;
                    break;
                    case 'paused':
                    $pausedTasks++;
                    break;
                    case 'cancelled':
                    $cancelledTasks++;
                    break;
                    default:
                    $newTasks++;
                    }
                    }
                    @endphp
                    <div class="col-md-3">
                        <div class="time-summary-card">
                            <h2>{{ $formattedTotal }}</h2>
                            <div class="text-muted">إجمالي الوقت المسجل</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="time-summary-card">
                            <h2>{{ count($taskData) }}</h2>
                            <div class="text-muted">عدد المهام</div>
                            <div class="mt-2">
                                <span class="badge badge-primary">{{ $regularTasksCount }} مهمة عادية</span>
                                <span class="badge badge-warning">{{ $templateTasksCount }} مهمة قالب</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="time-summary-card">
                            <h2>{{ $completedTasks }}</h2>
                            <div class="text-muted">المهام المكتملة</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="time-summary-card">
                            <h2>{{ $inProgressTasks }}</h2>
                            <div class="text-muted">المهام قيد التنفيذ</div>
                        </div>
                    </div>
                </div>



                <!-- الرسوم البيانية -->
                <div class="row mt-5 mb-5">
                    <div class="col-md-6">
                        <h5 class="mb-3" style="color: #28a745; border-bottom: 2px solid #28a745; padding-bottom: 8px; background: linear-gradient(135deg, rgba(40, 167, 69, 0.1) 0%, rgba(40, 167, 69, 0.05) 100%); padding: 12px; border-radius: 8px;">
                            <i class="fas fa-pie-chart" style="margin-left: 8px;"></i>توزيع المهام حسب الحالة
                        </h5>
                        <div class="chart-container" style="position: relative; height: 250px;">
                            <canvas id="taskStatusChart"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h5 class="mb-3" style="color: #17a2b8; border-bottom: 2px solid #17a2b8; padding-bottom: 8px; background: linear-gradient(135deg, rgba(23, 162, 184, 0.1) 0%, rgba(23, 162, 184, 0.05) 100%); padding: 12px; border-radius: 8px;">
                            <i class="fas fa-chart-line" style="margin-left: 8px;"></i>توزيع الوقت على مدار الفترة
                        </h5>
                        <div class="chart-container" style="position: relative; height: 250px;">
                            <canvas id="timeDistributionChart"></canvas>
                        </div>
                    </div>
                </div>



                <!-- قائمة المهام الشغالة فقط -->
                <div class="task-list mt-5">
                    <h5 class="mb-4" style="color: #6f42c1; border-bottom: 2px solid #6f42c1; padding-bottom: 8px; background: linear-gradient(135deg, rgba(111, 66, 193, 0.1) 0%, rgba(111, 66, 193, 0.05) 100%); padding: 12px; border-radius: 8px;">
                        <i class="fas fa-tasks" style="color: #6f42c1; margin-left: 8px;"></i>
                        المهام الشغالة في {{ $selectedDate ? $selectedDate->format('Y-m-d') : \Carbon\Carbon::today()->format('Y-m-d') }}
                    </h5>

                    @php
                    $activeTasks = array_filter($taskData, function($data) {
                    return in_array($data['status'], ['in_progress', 'completed']) &&
                    ($data['totalTime']['hours'] > 0 || $data['totalTime']['minutes'] > 0);
                    });
                    @endphp

                    @if(count($activeTasks) > 0)
                    <div class="row">
                        @foreach($activeTasks as $data)
                        @php
                        $taskType = $data['type'] ?? 'regular';
                        if ($taskType == 'regular') {
                        $estimatedMinutes = $data['taskUser']->total_estimated_minutes ?? 0;
                        $actualMinutes = $data['totalTime']['hours'] * 60 + $data['totalTime']['minutes'];
                        } else {
                        $templateTask = $data['taskUser']->templateTask ?? null;
                        $estimatedMinutes = $templateTask ? ($templateTask->estimated_hours * 60 + $templateTask->estimated_minutes) : 0;
                        $actualMinutes = $data['totalTime']['hours'] * 60 + $data['totalTime']['minutes'];
                        }
                        $progress = $estimatedMinutes > 0 ? min(100, round(($actualMinutes / $estimatedMinutes) * 100)) : 0;
                        if ($data['status'] == 'completed') {
                        $progress = 100;
                        }
                        @endphp

                        <div class="col-md-6 mb-4">
                            <div class="card task-card h-100 {{ $data['status'] == 'completed' ? 'border-success' : 'border-info' }}">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <h6 class="card-title mb-0">{{ $data['task']->name }}</h6>
                                        <span class="badge badge-{{ $taskType == 'regular' ? 'primary' : 'warning' }}">
                                            {{ $taskType == 'regular' ? 'عادية' : 'قالب' }}
                                        </span>
                                    </div>

                                    <div class="mb-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <small class="text-muted">
                                                <i class="fas fa-project-diagram"></i>
                                                {{ $data['project'] ? $data['project']->name : 'بدون مشروع' }}
                                            </small>
                                            @if(isset($data['project_delivered_at']) && $data['project_delivered_at'])
                                            <div>
                                                <span class="badge badge-{{ $data['project_delivered_today'] ? 'success' : 'info' }} badge-sm"
                                                    data-toggle="tooltip"
                                                    title="تم التسليم في {{ \Carbon\Carbon::parse($data['project_delivered_at'])->format('d/m/Y H:i') }}">
                                                    <i class="fas fa-check-circle"></i>
                                                    @if($data['project_delivered_today'])
                                                    تم التسليم اليوم
                                                    @else
                                                    مسلّم
                                                    @endif
                                                </span>
                                            </div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between">
                                            <span class="small">التقدم:</span>
                                            <span class="small font-weight-bold">{{ $progress }}%</span>
                                        </div>
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar bg-{{ $data['status'] == 'completed' ? 'success' : 'info' }}"
                                                style="width: {{ $progress }}%"></div>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge badge-{{
                                                                $data['status'] == 'completed' ? 'success' :
                                                                ($data['status'] == 'in_progress' ? 'info' : 'secondary')
                                                            }}">
                                            {{ $data['status'] == 'completed' ? 'مكتملة' :
                                                                   ($data['status'] == 'in_progress' ? 'قيد التنفيذ' : 'أخرى') }}
                                        </span>
                                        <small class="text-muted">
                                            <i class="fas fa-clock"></i>
                                            {{ $data['totalTime']['formatted'] }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle fa-2x mb-3"></i>
                        <h5>لا توجد مهام شغالة في هذا اليوم</h5>
                        <p class="mb-0">لم يتم العمل على أي مهام في التاريخ المحدد</p>
                    </div>
                    @endif
                </div>
                @else
                <!-- Delivered Projects Section (when no tasks) -->
                @if($deliveredProjectsData && count($deliveredProjectsData['projects']) > 0)
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header text-white" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); border-bottom: 2px solid #11998e;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><i class="fas fa-check-circle"></i> المشاريع المسلمة اليوم</h5>
                                    <div>
                                        <span class="badge badge-light">{{ $deliveredProjectsData['total_projects'] }} مشروع</span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    @foreach($deliveredProjectsData['projects'] as $deliveredProject)
                                    <div class="col-md-6 mb-3">
                                        <div class="card border-success h-100">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-3">
                                                    <h6 class="card-title mb-0">
                                                        <i class="fas fa-project-diagram text-success"></i>
                                                        {{ $deliveredProject['project']->name }}
                                                    </h6>
                                                    <span class="badge badge-success">
                                                        <i class="fas fa-check"></i> مسلّم
                                                    </span>
                                                </div>

                                                <div class="mb-2">
                                                    <small class="text-muted">
                                                        <strong>الخدمة:</strong> {{ $deliveredProject['service']->name ?? 'غير محدد' }}
                                                    </small>
                                                </div>

                                                <div class="mb-2">
                                                    <small class="text-muted">
                                                        <strong>نسبة المشاركة:</strong>
                                                        <span class="badge badge-info">{{ $deliveredProject['project_share_label'] }}</span>
                                                    </small>
                                                </div>

                                                <!-- الاعتمادات المطلوبة -->
                                                <div class="mb-3 mt-3">
                                                    <small class="d-block mb-2"><strong>الاعتمادات المطلوبة:</strong></small>
                                                    <div class="d-flex flex-wrap gap-2">
                                                        @if($deliveredProject['needs_administrative'])
                                                        <span class="badge {{ $deliveredProject['administrative_approval'] ? 'badge-success' : 'badge-warning' }}"
                                                            @if($deliveredProject['administrative_approval'] && $deliveredProject['administrative_approval_at'])
                                                            data-toggle="tooltip"
                                                            title="تم الاعتماد في {{ \Carbon\Carbon::parse($deliveredProject['administrative_approval_at'])->format('d/m/Y H:i') }}"
                                                            @endif>
                                                            <i class="fas {{ $deliveredProject['administrative_approval'] ? 'fa-check-circle' : 'fa-clock' }}"></i>
                                                            إداري
                                                            @if($deliveredProject['administrative_approval'])
                                                            <small>({{ $deliveredProject['administrative_approver']->name ?? '' }})</small>
                                                            @else
                                                            <small>(بانتظار الاعتماد)</small>
                                                            @endif
                                                        </span>
                                                        @endif

                                                        @if($deliveredProject['needs_technical'])
                                                        <span class="badge {{ $deliveredProject['technical_approval'] ? 'badge-success' : 'badge-warning' }}"
                                                            @if($deliveredProject['technical_approval'] && $deliveredProject['technical_approval_at'])
                                                            data-toggle="tooltip"
                                                            title="تم الاعتماد في {{ \Carbon\Carbon::parse($deliveredProject['technical_approval_at'])->format('d/m/Y H:i') }}"
                                                            @endif>
                                                            <i class="fas {{ $deliveredProject['technical_approval'] ? 'fa-check-circle' : 'fa-clock' }}"></i>
                                                            فني
                                                            @if($deliveredProject['technical_approval'])
                                                            <small>({{ $deliveredProject['technical_approver']->name ?? '' }})</small>
                                                            @else
                                                            <small>(بانتظار الاعتماد)</small>
                                                            @endif
                                                        </span>
                                                        @endif

                                                        @if(!$deliveredProject['needs_administrative'] && !$deliveredProject['needs_technical'])
                                                        <span class="badge badge-secondary">
                                                            <i class="fas fa-info-circle"></i> لا يحتاج اعتماد
                                                        </span>
                                                        @endif

                                                        @if($deliveredProject['has_all_approvals'])
                                                        <span class="badge badge-success">
                                                            <i class="fas fa-trophy"></i> مكتمل الاعتمادات
                                                        </span>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="mt-3 pt-2 border-top">
                                                    <small class="text-success">
                                                        <i class="fas fa-clock"></i> تم التسليم الساعة: {{ $deliveredProject['delivered_time'] }}
                                                    </small>
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
                @else
                <!-- رسالة عندما لا توجد بيانات على الإطلاق -->
                <div class="card">
                    <div class="card-body text-center p-5">
                        @if($absenceData)
                        <i class="fas fa-calendar-times fa-4x mb-4 text-warning"></i>
                        <h4>الموظف في إجازة</h4>
                        <p class="text-muted mb-4">
                            الموظف لديه إجازة متوافق عليها في هذا التاريخ
                        </p>
                        <div class="alert alert-info">
                            <strong>سبب الإجازة:</strong> {{ $absenceData['reason'] }}
                        </div>
                        @elseif($meetingsData && count($meetingsData['meetings']) > 0)
                        <!-- اجتماعات فقط بدون مهام -->
                        <i class="fas fa-users fa-4x mb-4" style="color: #667eea;"></i>
                        <h4>لا توجد مهام مسجلة لهذا اليوم</h4>
                        <p class="text-muted mb-4">
                            الموظف لديه {{ $meetingsData['total_meetings'] }} اجتماع{{ $meetingsData['total_meetings'] > 1 ? 'ات' : '' }} في هذا التاريخ، لكن لم يتم تسجيل أي وقت عمل على المهام
                        </p>
                        <div class="alert alert-info">
                            <h6 class="alert-heading">📅 ملاحظة:</h6>
                            <p class="mb-0">يمكنك عرض تفاصيل الاجتماعات في قسم "الاجتماعات" أعلاه</p>
                        </div>
                        @else
                        <i class="fas fa-chart-line fa-4x text-muted mb-4"></i>
                        <h4>لا توجد بيانات متاحة</h4>
                        <p class="text-muted mb-4">
                            لم يتم العثور على أي مهام، جلسات عمل، تعديلات، أو اجتماعات للموظف المحدد في هذا التاريخ
                        </p>
                        <div class="alert alert-light">
                            <h6 class="alert-heading">💡 نصائح:</h6>
                            <ul class="list-unstyled mb-0 text-left">
                                <li>• تأكد من تعيين مهام للموظف</li>
                                <li>• تأكد من بدء العمل على المهام (تغيير الحالة إلى "قيد التنفيذ")</li>
                                <li>• تحقق من وجود اجتماعات مسجلة للموظف في هذا التاريخ</li>
                                <li>• جرب تاريخاً مختلفاً</li>
                                <li>• تأكد من وجود نظام time tracking فعال</li>
                            </ul>
                        </div>
                        @endif
                    </div>
                </div>
                @endif
                @endif
                @endif
            </div>
        </div>
        @else
        <div class="card">
            <div class="card-body text-center p-5">
                <i class="fas fa-user-clock fa-4x mb-4 text-muted"></i>
                <h4>اختر موظفاً لعرض تقريره</h4>
                <p class="text-muted">قم باختيار موظف من القائمة على اليمين لعرض تقرير الوقت الخاص به</p>
            </div>
        </div>
        @endif
    </div>
</div>
</div>
@endsection

@push('scripts')
<!-- External Libraries -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>

<!-- Employee Reports Modules -->
<script src="{{ asset('js/employee-reports/timeline-manager.js') }}"></script>
<script src="{{ asset('js/employee-reports/real-time-updates.js') }}"></script>
<script src="{{ asset('js/employee-reports/ui-interactions.js') }}"></script>
<script src="{{ asset('js/employee-reports/chart-visualizations.js') }}"></script>
<script src="{{ asset('js/employee-reports/reports-main.js') }}"></script>
<script src="{{ asset('js/employee-reports/employee-reports-main.js') }}"></script>

@if($selectedEmployeeId && count($taskData) > 0)
@include('employee-reports.partials.chart-data')
@endif

<!-- Configuration Data -->
<script>
    // بيانات التكوين للتقرير
    window.employeeReportConfig = {
        employeeId: {
            {
                $selectedEmployeeId ?? 'null'
            }
        },
        selectedDate: '{{ $selectedDate ? $selectedDate->format("Y-m-d") : '
        ' }}',
        isToday: {
            {
                $selectedDate && $selectedDate - > isToday() ? 'true' : 'false'
            }
        },
        hasActiveSessions: {
            {
                ($timeLogsData && count($timeLogsData['active_sessions']) > 0) ? 'true' : 'false'
            }
        },
        activeSessionsCount: {
            {
                ($timeLogsData && count($timeLogsData['active_sessions']) > 0) ? count($timeLogsData['active_sessions']): '0'
            }
        },
        taskData: @json($taskData ?? [])
        @if($shiftData && isset($shiftData['shift_start']) && isset($shiftData['shift_end'])),
        shiftData: {
            timeline: @json($shiftData['timeline'] ?? []),
            shiftStart: '{{ $shiftData['
            shift_start '] ?? '
            ' }}',
            shiftEnd: '{{ $shiftData['
            shift_end '] ?? '
            ' }}',
            breakStart: '{{ $shiftData['
            break_start '] ?? '
            ' }}',
            breakEnd: '{{ $shiftData['
            break_end '] ?? '
            ' }}',
            workingTime: @json($shiftData['working_time'] ?? []),
            breakTime: @json($shiftData['break_time'] ?? []),
            idleTime: @json($shiftData['idle_time'] ?? []),
            totalDuration: @json($shiftData['total_shift_duration'] ?? []),
            hasBreak: {
                {
                    isset($shiftData['has_break']) && $shiftData['has_break'] ? 'true' : 'false'
                }
            }
        }
        @endif
    };
</script>

<!-- Initialization Script -->
<script src="{{ asset('js/employee-reports/employee-reports-init.js') }}"></script>

<script>
    $(document).ready(function() {
        $('#daily_work').on('input', function() {
            var length = $(this).val().length;
            $('#charCount').text(length);

            if (length > 4500) {
                $('#charCount').css('color', '#dc3545');
            } else if (length > 4000) {
                $('#charCount').css('color', '#ffc107');
            } else {
                $('#charCount').css('color', '#667eea');
            }
        });

        $('#dailyReportForm').on('submit', function(e) {
            e.preventDefault();

            var submitBtn = $(this).find('button[type="submit"]');
            var originalBtnText = submitBtn.html();
            submitBtn.html('<i class="fas fa-spinner fa-spin"></i> جاري الحفظ...').prop('disabled', true);

            $.ajax({
                url: '{{ route("employee-reports.save-daily") }}',
                method: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    $('#reportMessage')
                        .removeClass('alert-danger')
                        .addClass('alert alert-success')
                        .css({
                            'border-radius': '8px',
                            'border': '2px solid #28a745',
                            'background': 'linear-gradient(135deg, rgba(40, 167, 69, 0.1) 0%, rgba(40, 167, 69, 0.05) 100%)'
                        })
                        .html('<i class="fas fa-check-circle"></i> ' + response.message)
                        .show()
                        .css('animation', 'slideInDown 0.5s ease');

                    setTimeout(function() {
                        $('#reportMessage').fadeOut(500);
                    }, 4000);
                },
                error: function(xhr) {
                    var errorMessage = 'حدث خطأ أثناء حفظ التقرير';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                        var errors = xhr.responseJSON.errors;
                        errorMessage = Object.values(errors).flat().join('<br>');
                    }

                    $('#reportMessage')
                        .removeClass('alert-success')
                        .addClass('alert alert-danger')
                        .css({
                            'border-radius': '8px',
                            'border': '2px solid #dc3545',
                            'background': 'linear-gradient(135deg, rgba(220, 53, 69, 0.1) 0%, rgba(220, 53, 69, 0.05) 100%)'
                        })
                        .html('<i class="fas fa-exclamation-circle"></i> ' + errorMessage)
                        .show()
                        .css('animation', 'shake 0.5s ease');
                },
                complete: function() {
                    submitBtn.html(originalBtnText).prop('disabled', false);
                }
            });
        });
    });
</script>

<style>
    @keyframes slideInDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes shake {

        0%,
        100% {
            transform: translateX(0);
        }

        10%,
        30%,
        50%,
        70%,
        90% {
            transform: translateX(-5px);
        }

        20%,
        40%,
        60%,
        80% {
            transform: translateX(5px);
        }
    }
</style>
@endpush
