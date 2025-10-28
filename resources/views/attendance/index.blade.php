@push('styles')
<link href="{{ asset('css/attendance-system.css') }}" rel="stylesheet">

@endpush

<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 fw-bold text-dark mb-0">
            {{ __('سجلات الحضور والانصراف') }}
        </h2>
    </x-slot>

    <div class="py-4">
        <div class="container">
            <div class="attendance-card">
                <div class="attendance-header">
                    <h3 class="attendance-title">
                        <i class="fas fa-filter"></i>
                        {{ __('تصفية السجلات') }}
                    </h3>
                </div>

                <div class="p-4">
                    <form action="{{ route('attendance.index') }}" method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="date" class="form-label">{{ __('التاريخ') }}</label>
                            <div class="d-flex gap-2">
                                <a href="{{ route('attendance.index', ['date' => \Carbon\Carbon::parse($date)->subDay()->toDateString(), 'employee_id' => $employeeId, 'status' => $status]) }}" class="btn btn-light">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                                <input type="date" name="date" id="date" value="{{ $date }}" class="form-control">
                                <a href="{{ route('attendance.index', ['date' => \Carbon\Carbon::parse($date)->addDay()->toDateString(), 'employee_id' => $employeeId, 'status' => $status]) }}" class="btn btn-light">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                                <a href="{{ route('attendance.index', ['date' => \Carbon\Carbon::today()->toDateString(), 'employee_id' => $employeeId, 'status' => $status]) }}" class="btn btn-primary">
                                    {{ __('اليوم') }}
                                </a>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label for="employee_id" class="form-label">{{ __('الموظف') }}</label>
                            <select name="employee_id" id="employee_id" class="form-select">
                                <option value="">{{ __('جميع الموظفين') }}</option>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->employee_id }}" {{ $employeeId == $employee->employee_id ? 'selected' : '' }}>
                                        {{ $employee->name }} ({{ $employee->employee_id }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="status" class="form-label">{{ __('الحالة') }}</label>
                            <select name="status" id="status" class="form-select">
                                <option value="">{{ __('جميع الحالات') }}</option>
                                <option value="present" {{ $status == 'present' ? 'selected' : '' }}>{{ __('حاضر') }}</option>
                                <option value="late" {{ $status == 'late' ? 'selected' : '' }}>{{ __('متأخر') }}</option>
                                <option value="early_leave" {{ $status == 'early_leave' ? 'selected' : '' }}>{{ __('انصراف مبكر') }}</option>
                                <option value="absent" {{ $status == 'absent' ? 'selected' : '' }}>{{ __('غائب') }}</option>
                            </select>
                        </div>

                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> {{ __('تصفية') }}
                            </button>
                            <a href="{{ route('attendance.index') }}" class="btn btn-light">
                                {{ __('إعادة ضبط') }}
                            </a>
                        </div>
                    </form>

                    <div class="table-responsive mt-4">
                        <table class="attendance-table">
                            <thead>
                                <tr>
                                    <th>{{ __('الموظف') }}</th>
                                    <th>{{ __('التاريخ') }}</th>
                                    <th>{{ __('نوبة العمل') }}</th>
                                    <th>{{ __('الحضور') }}</th>
                                    <th>{{ __('الانصراف') }}</th>
                                    <th>{{ __('ساعات العمل') }}</th>
                                    <th>{{ __('الحالة') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($attendances as $attendance)
                                    <tr class="{{ $attendance->check_in ? 'checked-in' : 'not-checked-in' }}">
                                        <td>
                                            <div class="employee-card">
                                                <div class="employee-avatar">
                                                    <img src="{{ $attendance->employee->profile_photo_url }}" alt="{{ $attendance->employee->name }}">
                                                </div>
                                                <div class="employee-info">
                                                    <h4>{{ $attendance->employee->name }}</h4>
                                                    <p>{{ $attendance->employee->employee_id }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $attendance->date->format('d M, Y') }}</td>
                                        <td>
                                            @if ($attendance->workShift)
                                                {{ $attendance->workShift->name }} ({{ $attendance->workShift->check_in_time->format('h:i A') }} - {{ $attendance->workShift->check_out_time->format('h:i A') }})
                                            @else
                                                {{ __('غير معين') }}
                                            @endif
                                        </td>
                                        <td>
                                            @if ($attendance->check_in)
                                                <div>{{ $attendance->check_in->format('h:i:s A') }}</div>
                                                @if ($attendance->late_minutes > 0)
                                                    <div class="text-danger small">
                                                        <i class="fas fa-clock"></i> {{ $attendance->late_minutes }} {{ __('دقيقة تأخير') }}
                                                    </div>
                                                @elseif ($attendance->early_minutes > 0)
                                                    <div class="text-success small">
                                                        <i class="fas fa-clock"></i> {{ $attendance->early_minutes }} {{ __('دقيقة مبكر') }}
                                                    </div>
                                                @endif
                                            @else
                                                <span class="status-badge status-absent">
                                                    {{ __('لم يسجل الحضور') }}
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($attendance->check_out)
                                                <div>{{ $attendance->check_out->format('h:i:s A') }}</div>
                                                @if ($attendance->getEarlyDepartureMinutes() > 0)
                                                    <div class="text-warning small">
                                                        <i class="fas fa-clock"></i> {{ $attendance->getEarlyDepartureMinutes() }} {{ __('دقيقة انصراف مبكر') }}
                                                    </div>
                                                @endif
                                                @if ($attendance->getLateDepartureMinutes() > 0)
                                                    <div class="text-info small">
                                                        <i class="fas fa-clock"></i> {{ $attendance->getLateDepartureMinutes() }} {{ __('دقيقة انصراف متأخر') }}
                                                    </div>
                                                @endif
                                            @else
                                                <span class="status-badge status-absent">
                                                    {{ __('لم يسجل الانصراف') }}
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($attendance->check_in && $attendance->check_out)
                                                <div>
                                                    {{ $attendance->check_in->diffInHours($attendance->check_out) }} ساعة
                                                    {{ $attendance->check_in->diffInMinutes($attendance->check_out) % 60 }} دقيقة
                                                </div>
                                            @else
                                                <span class="status-badge status-absent">
                                                    {{ __('غير متاح') }}
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($attendance->status == 'present')
                                                <span class="status-badge status-present">
                                                    {{ __('حاضر') }}
                                                </span>
                                            @elseif ($attendance->status == 'late')
                                                <span class="status-badge status-late">
                                                    {{ __('متأخر') }}
                                                </span>
                                            @elseif ($attendance->status == 'early_leave')
                                                <span class="status-badge status-early-leave">
                                                    {{ __('انصراف مبكر') }}
                                                </span>
                                            @elseif ($attendance->status == 'absent')
                                                <span class="status-badge status-absent">
                                                    {{ __('غائب') }}
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $attendances->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
