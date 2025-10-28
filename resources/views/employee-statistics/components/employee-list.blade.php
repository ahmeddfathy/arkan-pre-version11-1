@php
use Carbon\Carbon;
@endphp
<div class="table-responsive employee-stats-table">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>الموظف</th>
                <th>القسم</th>
                <th>الفريق</th>
                <th>أيام العمل</th>
                <th>أيام الحضور</th>
                <th>نسبة الحضور</th>
                <th>الغياب</th>
                <th>الأذونات</th>
                <th>الوقت الإضافي</th>
                <th>التأخير</th>
                <th>الإجازات المأخوذة</th>
                <th>الإجازات المتبقية</th>
                <th>إجازات الشهر الحالي</th>
                <th>التفاصيل</th>
            </tr>
        </thead>
        <tbody>
            @forelse($employees as $employee)
            <tr class="request-row">
                <td>
                    <div>{{ $employee->name }}</div>
                    <small class="text-muted">{{ $employee->employee_id }}</small>
                </td>
                <td>{{ $employee->department ?? 'غير محدد' }}</td>
                <td>{{ $employee->currentTeam ? $employee->currentTeam->name : 'بدون فريق' }}</td>
                <td>
                    <span class="badge bg-secondary days-badge">{{ $employee->total_working_days }} يوم</span>
                </td>
                <td>
                    <span class="badge bg-primary days-badge">{{ $employee->actual_attendance_days }} يوم</span>
                </td>
                <td>
                    <div class="progress" style="height: 20px;">
                        <div class="progress-bar
                            {{ $employee->attendance_percentage >= 90 ? 'bg-success' :
                               ($employee->attendance_percentage >= 75 ? 'bg-warning' : 'bg-danger') }}"
                            role="progressbar"
                            style="width: {{ $employee->attendance_percentage }}%;"
                            aria-valuenow="{{ $employee->attendance_percentage }}"
                            aria-valuemin="0"
                            aria-valuemax="100">
                            {{ $employee->attendance_percentage }}%
                        </div>
                    </div>
                </td>
                <td>
                    <span class="badge bg-{{ $employee->absences > 0 ? 'danger' : 'success' }} days-badge"
                        style="cursor: pointer;"
                        onclick="showAbsenceDetails('{{ $employee->employee_id }}', '{{ $startDate }}', '{{ $endDate }}')">
                        {{ $employee->absences }} أيام
                    </span>
                </td>
                <td>
                    <span class="badge bg-info days-badge"
                        style="cursor: pointer;"
                        onclick="showPermissionDetails('{{ $employee->employee_id }}', '{{ $startDate }}', '{{ $endDate }}')">
                        {{ $employee->permissions }} مرات
                    </span>
                </td>
                <td>
                    <span class="badge bg-primary days-badge"
                        style="cursor: pointer;"
                        onclick="showOvertimeDetails('{{ $employee->employee_id }}', '{{ $startDate }}', '{{ $endDate }}')">
                        {{ $employee->overtimes }} ساعات
                    </span>
                </td>
                <td>
                    <span class="badge bg-{{ $employee->delays > 0 ? 'warning' : 'success' }} days-badge">
                        {{ $employee->delays }} دقيقة
                    </span>
                </td>
                <td>
                    <div class="d-flex flex-column align-items-center">
                        <span class="badge bg-info days-badge"
                            style="cursor: pointer;"
                            onclick="showLeaveDetails('{{ $employee->employee_id }}', '{{ $startDate }}', '{{ $endDate }}')">
                            {{ $employee->taken_leaves }} يوم
                        </span>
                        <small class="text-muted mt-1">من أصل {{ $employee->getMaxAllowedAbsenceDays() }} يوم</small>
                    </div>
                </td>
                <td>
                    <div class="d-flex flex-column align-items-center">
                        <span class="badge {{ $employee->remaining_leaves > 0 ? 'bg-success' : 'bg-danger' }} days-badge">
                            {{ $employee->remaining_leaves }} يوم
                        </span>
                    </div>
                </td>
                <td>
                    <div class="d-flex flex-column align-items-center">
                        <span class="badge bg-purple days-badge"
                            style="cursor: pointer;"
                            onclick="showCurrentMonthLeaves('{{ $employee->employee_id }}', '{{ $startDate }}', '{{ $endDate }}')">
                            {{ $employee->current_month_leaves }} يوم
                        </span>
                        <small class="text-muted mt-1 date-range">
                            {{ Carbon::parse($startDate)->format('d/m') }} - {{ Carbon::parse($endDate)->format('d/m') }}
                        </small>
                    </div>
                </td>
                <td>
                    <button class="eye-icon-button"
                        onclick="showDetails('{{ $employee->employee_id }}')">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>

                <!-- تحليل الأداء والتنبؤات -->
                @if(auth()->user()->hasRole('hr'))
                    @include('employee-statistics.components.performance-analysis', ['employee' => $employee])
                @endif
            </tr>
            @empty
            <tr>
                <td colspan="14" class="text-center py-4">
                    <div class="text-muted">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p>لا توجد بيانات متاحة</p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Pagination -->
<div class="d-flex justify-content-center mt-4">
    {{ $employees->links() }}
</div>
