<!-- قسم الرسوم البيانية -->
@if(isset($statistics))
<div class="row mb-4">
    <!-- الرسوم البيانية الشخصية -->
    <div class="col-md-6">
        <div class="card shadow-sm chart-card">
            <div class="card-header text-white">
                <h5 class="mb-0"><i class="fas fa-chart-pie"></i> رسوم بيانية لطلباتي</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="chart-container">
                            <canvas id="personalStatusChart" width="100%" height="200"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-container">
                            <canvas id="personalTrendChart" width="100%" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- الرسوم البيانية للفريق -->
    @if(
    (
    Auth::user()->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager']) ||
    Auth::user()->hasRole('hr')
    ) &&
    (
    Auth::user()->ownedTeams()
    ->withCount('users')
    ->having('users_count', '>', 1)
    ->exists() ||
    Auth::user()->teams()
    ->wherePivot('role', 'admin')
    ->withCount('users')
    ->having('users_count', '>', 1)
    ->exists() ||
    (Auth::user()->hasRole(['department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager']) && isset($statistics['team'])) ||
    Auth::user()->hasRole('company_manager')
    ) &&
    isset($statistics['team'])
    )
    <div class="col-md-6">
        <div class="card shadow-sm chart-card">
            <div class="card-header text-white">
                <h5 class="mb-0">
                    <i class="fas fa-chart-bar"></i> رسوم بيانية للفريق
                    @if(isset($statistics['team']['team_name']))
                    <small>({{ $statistics['team']['team_name'] }})</small>
                    @endif
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="chart-container">
                            <canvas id="teamStatusChart" width="100%" height="200"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-container">
                            <canvas id="teamMembersChart" width="100%" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- الرسوم البيانية لـ HR -->
    @if(Auth::user()->hasRole('hr') && isset($statistics['hr']))
    <div class="col-md-12 mt-4">
        <div class="card shadow-sm chart-card">
            <div class="card-header text-white">
                <h5 class="mb-0"><i class="fas fa-chart-line"></i> رسوم بيانية للشركة</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="chart-container">
                            <canvas id="hrStatusChart" width="100%" height="200"></canvas>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="chart-container">
                            <canvas id="hrDepartmentsChart" width="100%" height="200"></canvas>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="chart-container">
                            <canvas id="hrMonthlyTrendChart" width="100%" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endif

<!-- قسم الإحصائيات -->
@if(isset($statistics))
<div class="row mb-4">
    <!-- الإحصائيات الشخصية -->
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header text-white">
                <h5 class="mb-0"><i class="fas fa-user-clock"></i> إحصائيات طلباتي</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="border rounded p-3">
                            <h6 class="text-muted mb-2">إجمالي الطلبات</h6>
                            <h4 class="mb-0">{{ $statistics['personal']['total_requests'] }}</h4>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-3">
                            <h6 class="text-muted mb-2">أيام الاجازة</h6>
                            <h4 class="mb-0">{{ $statistics['personal']['total_days'] }}</h4>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3 text-success">
                            <h6 class="mb-2">تمت الموافقة</h6>
                            <h4 class="mb-0">{{ $statistics['personal']['approved_requests'] }}</h4>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3 text-danger">
                            <h6 class="mb-2">مرفوضة</h6>
                            <h4 class="mb-0">{{ $statistics['personal']['rejected_requests'] }}</h4>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3 text-warning">
                            <h6 class="mb-2">معلقة</h6>
                            <h4 class="mb-0">{{ $statistics['personal']['pending_requests'] }}</h4>
                        </div>
                    </div>
                    @if($statistics['personal']['most_common_reason'])
                    <div class="col-12">
                        <div class="border rounded p-3">
                            <h6 class="text-muted mb-2">السبب الأكثر تكراراً</h6>
                            <p class="mb-0">{{ $statistics['personal']['most_common_reason']['reason'] }}
                                ({{ $statistics['personal']['most_common_reason']['count'] }} مرات)</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- إحصائيات الفريق -->
    @if(
    (
    Auth::user()->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager']) ||
    Auth::user()->hasRole('hr')
    ) &&
    (
    Auth::user()->ownedTeams()
    ->withCount('users')
    ->having('users_count', '>', 1)
    ->exists() ||
    Auth::user()->teams()
    ->wherePivot('role', 'admin')
    ->withCount('users')
    ->having('users_count', '>', 1)
    ->exists() ||
    (Auth::user()->hasRole(['department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager']) && isset($statistics['team'])) ||
    Auth::user()->hasRole('company_manager')
    ) &&
    isset($statistics['team'])
    )
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header text-white">
                <h5 class="mb-0">
                    <i class="fas fa-users"></i> إحصائيات الفريق
                    @if(isset($statistics['team']['team_name']))
                    <small>({{ $statistics['team']['team_name'] }})</small>
                    @endif
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="border rounded p-3">
                            <h6 class="text-muted mb-2">إجمالي الطلبات</h6>
                            <h4 class="mb-0">{{ $statistics['team']['total_requests'] }}</h4>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-3">
                            <h6 class="text-muted mb-2">أيام الاجازة</h6>
                            <h4 class="mb-0">{{ $statistics['team']['total_days'] }}</h4>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3 text-success">
                            <h6 class="mb-2">تمت الموافقة</h6>
                            <h4 class="mb-0">{{ $statistics['team']['approved_requests'] }}</h4>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3 text-danger">
                            <h6 class="mb-2">مرفوضة</h6>
                            <h4 class="mb-0">{{ $statistics['team']['rejected_requests'] }}</h4>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3 text-warning">
                            <h6 class="mb-2">معلقة</h6>
                            <h4 class="mb-0">{{ $statistics['team']['pending_requests'] }}</h4>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="border rounded p-3">
                            <h6 class="text-muted mb-2">تجاوزوا الحد المسموح</h6>
                            <h4 class="mb-0 text-danger">
                                <a href="#" data-bs-toggle="modal" data-bs-target="#exceededLimitModal" class="text-danger text-decoration-none">
                                    {{ $statistics['team']['employees_exceeded_limit'] }} موظفين
                                </a>
                            </h4>
                        </div>
                    </div>
                    @if($statistics['team']['most_absent_employee'])
                    <div class="col-md-12">
                        <div class="border rounded p-3">
                            <h6 class="text-muted mb-2">الموظف الأكثر اجازةاً</h6>
                            <h5 class="mb-1">{{ $statistics['team']['most_absent_employee']['name'] }}</h5>
                            <small class="text-muted">{{ $statistics['team']['most_absent_employee']['count'] }} أيام</small>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- إحصائيات HR -->
    @if(Auth::user()->hasRole('hr'))
    <div class="col-md-12 mt-4">
        <div class="card shadow-sm">
            <div class="card-header text-white">
                <h5 class="mb-0"><i class="fas fa-chart-pie"></i> إحصائيات الشركة</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="border rounded p-3">
                            <h6 class="text-muted mb-2">إجمالي الطلبات</h6>
                            <h4 class="mb-0">{{ $statistics['hr']['total_requests'] }}</h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3">
                            <h6 class="text-muted mb-2">أيام الاجازة</h6>
                            <h4 class="mb-0">{{ $statistics['hr']['total_days'] }}</h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3">
                            <h6 class="text-muted mb-2">تجاوزوا الحد المسموح</h6>
                            <h4 class="mb-0 text-danger">
                                <a href="#" data-bs-toggle="modal" data-bs-target="#hrExceededLimitModal" class="text-danger text-decoration-none">
                                    {{ $statistics['hr']['employees_exceeded_limit'] }} موظفين
                                </a>
                            </h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3">
                            <h6 class="text-muted mb-2">الطلبات المعلقة</h6>
                            <h4 class="mb-0 text-warning">{{ $statistics['hr']['pending_requests'] }}</h4>
                        </div>
                    </div>
                    @if($statistics['hr']['most_absent_employee'])
                    <div class="col-md-6">
                        <div class="border rounded p-3">
                            <h6 class="text-muted mb-2">الموظف الأكثر اجازةاً</h6>
                            <h5 class="mb-1">{{ $statistics['hr']['most_absent_employee']['name'] }}</h5>
                            <small class="text-muted">{{ $statistics['hr']['most_absent_employee']['count'] }} أيام</small>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal للموظفين المتجاوزين -->
    <div class="modal fade" id="exceededLimitModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">الموظفين المتجاوزين للحد المسموح</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>اسم الموظف</th>
                                    <th>الحد المسموح</th>
                                    <th>أيام الاجازة</th>
                                    <th>تجاوز الحد بـ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($statistics['team']['exceeded_employees'] ?? [] as $index => $employee)
                                @php
                                $maxDays = $employee->date_of_birth && \Carbon\Carbon::parse($employee->date_of_birth)->age >= 50 ? 45 : 21;
                                @endphp
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $employee->name }}</td>
                                    <td>{{ $maxDays }} يوم</td>
                                    <td>{{ $employee->total_days }} يوم</td>
                                    <td class="text-danger">{{ $employee->total_days - $maxDays }} يوم</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- جدول طلبات HR -->
    @if(Auth::user()->hasRole('hr'))
    <!-- Modal للموظفين المتجاوزين (HR) -->
    <div class="modal fade" id="hrExceededLimitModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">الموظفين المتجاوزين للحد المسموح (الشركة)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>اسم الموظف</th>
                                    <th>أيام الاجازة</th>
                                    <th>تجاوز الحد بـ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($statistics['hr']['exceeded_employees'] ?? [] as $index => $employee)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $employee->name }}</td>
                                    <td>{{ $employee->total_days }} يوم</td>
                                    <td class="text-danger">{{ $employee->total_days - 21 }} يوم</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endif

@if(Auth::user()->hasRole('hr'))
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header text-white">
                <h5 class="mb-0">إحصائيات تفصيلية للاجازة</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Monthly Trend Chart -->
                    <div class="col-md-6 mb-4">
                        <div class="chart-container">
                            <h6 class="text-center mb-3">تحليل الاجازة الشهري</h6>
                            <canvas id="hrMonthlyChart"></canvas>
                        </div>
                    </div>

                    <!-- Department Statistics Chart -->
                    <div class="col-md-6 mb-4">
                        <div class="chart-container">
                            <h6 class="text-center mb-3">معدل الاجازة حسب القسم</h6>
                            <canvas id="hrDepartmentChart"></canvas>
                        </div>
                    </div>

                    <!-- Top Reasons Chart -->
                    <div class="col-md-6 mb-4">
                        <div class="chart-container">
                            <h6 class="text-center mb-3">أكثر أسباب الاجازة شيوعاً</h6>
                            <canvas id="hrReasonsChart"></canvas>
                        </div>
                    </div>

                    <!-- Weekday Statistics Chart -->
                    <div class="col-md-6 mb-4">
                        <div class="chart-container">
                            <h6 class="text-center mb-3">معدل الاجازة حسب أيام الأسبوع</h6>
                            <canvas id="hrWeekdayChart"></canvas>
                        </div>
                    </div>

                    <!-- Age Group Statistics Chart -->
                    <div class="col-md-6 mb-4">
                        <div class="chart-container">
                            <h6 class="text-center mb-3">معدل الاجازة حسب الفئة العمرية</h6>
                            <canvas id="hrAgeGroupChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
