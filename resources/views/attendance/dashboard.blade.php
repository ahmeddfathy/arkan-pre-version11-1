@push('styles')
<link href="{{ asset('css/attendance-system.css') }}" rel="stylesheet">

@endpush
<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 fw-bold text-dark mb-0">
            {{ __('لوحة الحضور والانصراف') }}
        </h2>
    </x-slot>

    <div class="py-4">
        <div class="container">
            <img src="{{ asset('assets/images/arkan.png') }}" alt="Arkan Logo" class="img-fluid mb-4" style="max-height: 50px;">

            <div class="attendance-card animate-fade-in">
                <div class="attendance-header">
                    <h3 class="attendance-title">
                        <i class="fas fa-calendar-check"></i>
                        {{ __('الحضور ليوم') }} {{ \Carbon\Carbon::parse($date)->format('d M, Y') }}
                    </h3>
                    <div class="filter-controls">
                        <form action="{{ route('attendance.dashboard') }}" method="GET" class="d-flex gap-2">
                            <a href="{{ route('attendance.dashboard', ['date' => \Carbon\Carbon::parse($date)->subDay()->toDateString()]) }}" class="filter-btn btn-light">
                                <i class="fas fa-chevron-left"></i> {{ __('السابق') }}
                            </a>
                            <input type="date" name="date" id="date" value="{{ $date }}" class="form-control">
                            <button type="submit" class="filter-btn btn-primary">
                                <i class="fas fa-filter"></i> {{ __('تصفية') }}
                            </button>
                            <a href="{{ route('attendance.dashboard') }}" class="filter-btn btn-primary">
                                {{ __('اليوم') }}
                            </a>
                            <a href="{{ route('attendance.dashboard', ['date' => \Carbon\Carbon::parse($date)->addDay()->toDateString()]) }}" class="filter-btn btn-light">
                                {{ __('التالي') }} <i class="fas fa-chevron-right"></i>
                            </a>
                            <a href="{{ route('attendance.index') }}" class="filter-btn btn-primary">
                                <i class="fas fa-list"></i> {{ __('جميع السجلات') }}
                            </a>
                        </form>
                    </div>
                </div>

                <div class="p-4">
                    <div class="table-responsive">
                        <table class="attendance-table">
                            <thead>
                                <tr>
                                    <th>{{ __('الموظف') }}</th>
                                    <th>{{ __('القسم') }}</th>
                                    <th>{{ __('نوبة العمل') }}</th>
                                    <th>{{ __('الحضور') }}</th>
                                    <th>{{ __('الانصراف') }}</th>
                                    <th>{{ __('الحالة') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($employees as $employee)
                                    @php
                                        $attendance = $employee->attendances->first();
                                        $workShift = $employee->workShift;
                                        $hasCheckedIn = $attendance && $attendance->check_in;
                                    @endphp
                                    <tr class="{{ $hasCheckedIn ? 'checked-in' : 'not-checked-in' }}">
                                        <td>
                                            <div class="employee-card">
                                                <div class="employee-avatar">
                                                    <img src="{{ $employee->profile_photo_url }}" alt="{{ $employee->name }}">
                                                </div>
                                                <div class="employee-info">
                                                    <h4>{{ $employee->name }}</h4>
                                                    <p>{{ $employee->employee_id }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $employee->department ?? 'غير محدد' }}</td>
                                        <td>
                                            @if ($workShift)
                                                {{ $workShift->name }} ({{ $workShift->check_in_time->format('h:i A') }} - {{ $workShift->check_out_time->format('h:i A') }})
                                            @else
                                                {{ __('غير معين') }}
                                            @endif
                                        </td>
                                        <td>
                                            @if ($attendance && $attendance->check_in)
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
                                            @if ($attendance && $attendance->check_out)
                                                <div>{{ $attendance->check_out->format('h:i:s A') }}</div>
                                                <div class="text-warning small {{ $attendance->early_minutes > 0 ? '' : 'd-none' }}">
                                                    {{ $attendance->early_minutes }} {{ __('دقيقة انصراف مبكر') }}
                                                </div>
                                                <div class="text-info small {{ $attendance->getLateDepartureMinutes() > 0 ? '' : 'd-none' }}">
                                                    {{ $attendance->getLateDepartureMinutes() }} {{ __('دقيقة انصراف متأخر') }}
                                                </div>
                                            @else
                                                <span class="status-badge status-absent">
                                                    {{ __('لم يسجل الانصراف') }}
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($attendance)
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
                                            @else
                                                <span class="status-badge status-absent">
                                                    {{ __('لا يوجد سجل') }}
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
</x-app-layout>
