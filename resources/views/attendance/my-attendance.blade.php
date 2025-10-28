@push('styles')
<link href="{{ asset('css/attendance-system.css') }}" rel="stylesheet">

@endpush

<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 fw-bold text-dark mb-0">
            {{ __('الحضور والانصراف الخاص بي') }}
        </h2>
    </x-slot>

    <div class="py-4">
        <div class="container">
            <div class="attendance-card">
                <div class="attendance-header">
                    <h3 class="attendance-title">
                        <i class="fas fa-history"></i>
                        {{ __('سجل الحضور والانصراف') }}
                    </h3>
                    <div class="d-flex gap-2">
                        @php
                            // Get today's date from server-side
                            $today = now()->toDateString();
                            $todayAttendance = $attendances->first(function($attendance) use ($today) {
                                return $attendance->date->toDateString() === $today;
                            });
                            $hasCheckedIn = $todayAttendance && $todayAttendance->check_in;
                            $hasCheckedOut = $todayAttendance && $todayAttendance->check_out;
                            $workShift = auth()->user()->workShift;
                            $shiftEndTime = $workShift ? \Carbon\Carbon::parse($today . ' ' . $workShift->check_out_time->format('H:i:s')) : null;
                            $isWithinWorkHours = $workShift && now()->lt($shiftEndTime);
                        @endphp

                        @if($hasCheckedOut && $isWithinWorkHours)
                            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#cancelCheckOutModal">
                                <i class="fas fa-undo"></i> {{ __('إلغاء تسجيل الانصراف') }}
                            </button>
                        @endif

                        @if($hasCheckedIn && !$hasCheckedOut && $isWithinWorkHours)
                            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#cancelCheckInModal">
                                <i class="fas fa-undo"></i> {{ __('إلغاء تسجيل الحضور') }}
                            </button>
                        @endif

                        @if(!$hasCheckedIn)
                            <form method="POST" action="{{ route('attendance.check-in') }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-sign-in-alt"></i> {{ __('تسجيل الحضور') }}
                                </button>
                            </form>
                        @elseif(!$hasCheckedOut)
                            <form method="POST" action="{{ route('attendance.check-out') }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-sign-out-alt"></i> {{ __('تسجيل الانصراف') }}
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show m-4" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show m-4" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <div class="p-4">
                    <form action="{{ route('attendance.my') }}" method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="start_date" class="form-label">{{ __('تاريخ البداية') }}</label>
                            <input type="date" name="start_date" id="start_date" value="{{ $startDate }}" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label for="end_date" class="form-label">{{ __('تاريخ النهاية') }}</label>
                            <input type="date" name="end_date" id="end_date" value="{{ $endDate }}" class="form-control">
                        </div>
                        <div class="col-md-4 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> {{ __('تصفية') }}
                            </button>
                            <a href="{{ route('attendance.my') }}" class="btn btn-light">
                                {{ __('إعادة ضبط') }}
                            </a>
                        </div>
                    </form>

                    <div class="table-responsive mt-4">
                        <table class="attendance-table">
                            <thead>
                                <tr>
                                    <th>{{ __('التاريخ') }}</th>
                                    <th>{{ __('الحضور') }}</th>
                                    <th>{{ __('الانصراف') }}</th>
                                    <th>{{ __('ساعات العمل') }}</th>
                                    <th>{{ __('الحالة') }}</th>
                                    <th>{{ __('ملاحظات') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($attendances as $attendance)
                                    <tr>
                                        <td>{{ $attendance->date->format('d M, Y') }}</td>
                                        <td>
                                            @if ($attendance->check_in)
                                                <div>{{ $attendance->check_in->format('h:i:s A') }}</div>
                                                @if ($attendance->late_minutes > 0)
                                                    <div class="text-danger small">
                                                        <i class="fas fa-clock"></i> {{ $attendance->late_minutes }} {{ __('دقيقة تأخير') }}
                                                    </div>
                                                @elseif ($attendance->early_minutes > 0)
                                                    <div class="text-success small">
                                                        <i class="fas fa-clock"></i> {{ $attendance->early_minutes }} {{ __('دقيقة حضور مبكر') }}
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
                                                @if ($attendance->status == 'early_leave' && $attendance->getEarlyDepartureMinutes() > 0)
                                                    <div class="text-warning small">
                                                        <i class="fas fa-clock"></i> {{ $attendance->getEarlyDepartureMinutes() }} {{ __('دقيقة انصراف مبكر') }}
                                                    </div>
                                                @elseif ($attendance->getLateDepartureMinutes() > 0)
                                                    <div class="text-primary small">
                                                        <i class="fas fa-clock"></i> {{ $attendance->getLateDepartureMinutes() }} {{ __('دقيقة عمل إضافي') }}
                                                    </div>
                                                @endif
                                            @else
                                                <span class="badge bg-secondary">{{ __('لم يسجل الانصراف') }}</span>
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
                                        <td>{{ $attendance->notes ?? '-' }}</td>
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

<!-- Cancel Check In Modal -->
<div class="modal fade attendance-modal" id="cancelCheckInModal" tabindex="-1" aria-labelledby="cancelCheckInModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cancelCheckInModalLabel">
                    <i class="fas fa-undo-alt modal-icon"></i>
                    {{ __('تأكيد إلغاء تسجيل الحضور') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    {{ __('تحذير: سيؤدي هذا إلى إلغاء تسجيل حضورك لليوم. ستحتاج إلى تسجيل الحضور مرة أخرى. هل أنت متأكد من المتابعة؟') }}
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> {{ __('إغلاق') }}
                </button>
                <form method="POST" action="{{ route('attendance.cancel-check-in') }}">
                    @csrf
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-undo me-1"></i> {{ __('تأكيد إلغاء تسجيل الحضور') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Check Out Modal -->
<div class="modal fade attendance-modal" id="cancelCheckOutModal" tabindex="-1" aria-labelledby="cancelCheckOutModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cancelCheckOutModalLabel">
                    <i class="fas fa-undo-alt modal-icon"></i>
                    {{ __('تأكيد إلغاء تسجيل الانصراف') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    {{ __('تحذير: سيؤدي هذا إلى إلغاء تسجيل انصرافك لليوم. ستحتاج إلى تسجيل الانصراف مرة أخرى. هل أنت متأكد من المتابعة؟') }}
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> {{ __('إغلاق') }}
                </button>
                <form method="POST" action="{{ route('attendance.cancel-check-out') }}">
                    @csrf
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-undo me-1"></i> {{ __('تأكيد إلغاء تسجيل الانصراف') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize all modals
        var modals = document.querySelectorAll('.modal');
        modals.forEach(function(modal) {
            new bootstrap.Modal(modal);
        });

        // Add click handlers for the cancel buttons
        document.querySelector('[data-bs-target="#cancelCheckInModal"]')?.addEventListener('click', function() {
            var modal = new bootstrap.Modal(document.getElementById('cancelCheckInModal'));
            modal.show();
        });

        document.querySelector('[data-bs-target="#cancelCheckOutModal"]')?.addEventListener('click', function() {
            var modal = new bootstrap.Modal(document.getElementById('cancelCheckOutModal'));
            modal.show();
        });
    });
</script>
@endpush
