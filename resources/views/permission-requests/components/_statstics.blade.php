<!-- Statistics Section -->
@if(isset($statistics))
<div class="row mb-4">
    <!-- Personal Statistics -->
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
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
                            <h6 class="text-muted mb-2">الدقائق المستخدمة</h6>
                            <h4 class="mb-0">{{ $statistics['personal']['total_minutes'] }}</h4>
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
                    <div class="col-md-6">
                        <div class="border rounded p-3 text-success">
                            <h6 class="mb-2">عودة في الوقت</h6>
                            <h4 class="mb-0">{{ $statistics['personal']['on_time_returns'] }}</h4>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-3 text-danger">
                            <h6 class="mb-2">عودة متأخرة</h6>
                            <h4 class="mb-0">{{ $statistics['personal']['late_returns'] }}</h4>
                        </div>
                    </div>
                </div>

                <!-- Charts for Personal Statistics -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="chart-card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-chart-pie"></i> توزيع حالات الطلبات</h5>
                            </div>
                            <div class="card-body p-3">
                                <div class="chart-container">
                                    <canvas id="personalRequestsChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-chart-bar"></i> توزيع الدقائق</h5>
                            </div>
                            <div class="card-body p-3">
                                <div class="chart-container">
                                    <canvas id="personalMinutesChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- إحصائيات الفريق -->
    @if((Auth::user()->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager']) ||
    (Auth::user()->hasRole('hr') && (Auth::user()->ownedTeams->count() > 0 || Auth::user()->teams()->wherePivot('role', 'admin')->exists())))
    && isset($statistics['team']))
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
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
                            <h6 class="text-muted mb-2">إجمالي طلبات الفريق</h6>
                            <h4 class="mb-0">{{ $statistics['team']['total_requests'] }}</h4>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-3">
                            <h6 class="text-muted mb-2">إجمالي الدقائق</h6>
                            <h4 class="mb-0">{{ $statistics['team']['total_minutes'] }}</h4>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="border rounded p-3">
                            <h6 class="text-muted mb-2">تجاوزوا الحد المسموح</h6>
                            <h4 class="mb-0 text-danger">
                                <a href="#" data-bs-toggle="modal" data-bs-target="#teamExceededLimitModal" class="text-danger text-decoration-none">
                                    {{ $statistics['team']['employees_exceeded_limit'] }} موظفين
                                </a>
                            </h4>
                        </div>
                    </div>
                    @if($statistics['team']['most_requested_employee'])
                    <div class="col-md-6">
                        <div class="border rounded p-3">
                            <h6 class="text-muted mb-2">الأكثر طلباً للاستئذان</h6>
                            <h5 class="mb-1">{{ $statistics['team']['most_requested_employee']['name'] }}</h5>
                            <small class="text-muted">{{ $statistics['team']['most_requested_employee']['count'] }} طلبات</small>
                        </div>
                    </div>
                    @endif
                    @if($statistics['team']['highest_minutes_employee'])
                    <div class="col-md-6">
                        <div class="border rounded p-3">
                            <h6 class="text-muted mb-2">الأكثر استخداماً للدقائق</h6>
                            <h5 class="mb-1">{{ $statistics['team']['highest_minutes_employee']['name'] }}</h5>
                            <small class="text-muted">{{ $statistics['team']['highest_minutes_employee']['minutes'] }} دقيقة</small>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Charts for Team Statistics -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="chart-card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-chart-pie"></i> توزيع طلبات الفريق</h5>
                            </div>
                            <div class="card-body p-3">
                                <div class="chart-container">
                                    <canvas id="teamRequestsChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-chart-bar"></i> توزيع دقائق الفريق</h5>
                            </div>
                            <div class="card-body p-3">
                                <div class="chart-container">
                                    <canvas id="teamMinutesChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endif

<!-- HR Statistics Section -->
@if(Auth::user()->hasRole('hr') && isset($statistics['hr']))
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-chart-pie"></i> إحصائيات جميع الموظفين</h5>
            </div>
            <div class="card-body p-4">
                <!-- بطاقات الإحصائيات الرئيسية -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="border rounded p-3 h-100 shadow-sm">
                            <h6 class="text-muted mb-2">إجمالي الطلبات</h6>
                            <h4 class="mb-0">{{ $statistics['hr']['total_requests'] }}</h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3 h-100 shadow-sm">
                            <h6 class="text-muted mb-2">إجمالي الدقائق</h6>
                            <h4 class="mb-0">{{ $statistics['hr']['total_minutes'] }}</h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3 h-100 shadow-sm">
                            <h6 class="text-muted mb-2">تجاوزوا الحد المسموح</h6>
                            <h4 class="mb-0 text-danger">
                                <a href="#" data-bs-toggle="modal" data-bs-target="#exceededLimitModal" class="text-danger text-decoration-none">
                                    {{ $statistics['hr']['employees_exceeded_limit'] }} موظفين
                                </a>
                            </h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3 h-100 shadow-sm">
                            <h6 class="text-muted mb-2">الطلبات المعلقة</h6>
                            <h4 class="mb-0 text-warning">{{ $statistics['hr']['pending_requests'] }}</h4>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    @if($statistics['hr']['most_requested_employee'])
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100 shadow-sm">
                            <h6 class="text-muted mb-2">الأكثر طلباً للاستئذان</h6>
                            <h5 class="mb-1">{{ $statistics['hr']['most_requested_employee']['name'] }}</h5>
                            <small class="text-muted">{{ $statistics['hr']['most_requested_employee']['count'] }} طلبات</small>
                        </div>
                    </div>
                    @endif
                    @if($statistics['hr']['highest_minutes_employee'])
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100 shadow-sm">
                            <h6 class="text-muted mb-2">الأكثر استخداماً للوقت</h6>
                            <h5 class="mb-1">{{ $statistics['hr']['highest_minutes_employee']['name'] }}</h5>
                            <small class="text-muted">{{ $statistics['hr']['highest_minutes_employee']['minutes'] }} دقيقة</small>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- مقارنة مع الفترة السابقة -->
                <div class="row mt-4 mb-4">
                    <div class="col-12">
                        <h5 class="border-bottom pb-2 mb-3"><i class="fas fa-exchange-alt"></i> مقارنة مع الفترة السابقة</h5>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="comparison-card">
                            <div class="card-body text-center p-3">
                                <h6 class="text-muted mb-3">الطلبات</h6>
                                <h3 class="mb-2">{{ $statistics['hr']['comparison_with_previous']['current_period']['total_requests'] }}</h3>
                                <div class="d-flex justify-content-center">
                                    @php
                                    $requestsChange = $statistics['hr']['comparison_with_previous']['percentage_change']['total_requests'];
                                    $requestsChangeClass = $requestsChange > 0 ? 'text-success' : ($requestsChange < 0 ? 'text-danger' : 'text-muted' );
                                        $requestsChangeIcon=$requestsChange> 0 ? 'fa-arrow-up' : ($requestsChange < 0 ? 'fa-arrow-down' : 'fa-equals' );
                                            @endphp
                                            <span class="{{ $requestsChangeClass }}">
                                            <i class="fas {{ $requestsChangeIcon }}"></i>
                                            {{ abs($requestsChange) }}%
                                            </span>
                                            <span class="text-muted ms-2">({{ $statistics['hr']['comparison_with_previous']['previous_period']['total_requests'] }})</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="comparison-card">
                            <div class="card-body text-center p-3">
                                <h6 class="text-muted mb-3">الدقائق</h6>
                                <h3 class="mb-2">{{ $statistics['hr']['comparison_with_previous']['current_period']['total_minutes'] }}</h3>
                                <div class="d-flex justify-content-center">
                                    @php
                                    $minutesChange = $statistics['hr']['comparison_with_previous']['percentage_change']['total_minutes'];
                                    $minutesChangeClass = $minutesChange > 0 ? 'text-success' : ($minutesChange < 0 ? 'text-danger' : 'text-muted' );
                                        $minutesChangeIcon=$minutesChange> 0 ? 'fa-arrow-up' : ($minutesChange < 0 ? 'fa-arrow-down' : 'fa-equals' );
                                            @endphp
                                            <span class="{{ $minutesChangeClass }}">
                                            <i class="fas {{ $minutesChangeIcon }}"></i>
                                            {{ abs($minutesChange) }}%
                                            </span>
                                            <span class="text-muted ms-2">({{ $statistics['hr']['comparison_with_previous']['previous_period']['total_minutes'] }})</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="comparison-card">
                            <div class="card-body text-center p-3">
                                <h6 class="text-muted mb-3">العودة في الوقت</h6>
                                <h3 class="mb-2">{{ $statistics['hr']['comparison_with_previous']['current_period']['on_time_returns'] }}</h3>
                                <div class="d-flex justify-content-center">
                                    @php
                                    $onTimeChange = $statistics['hr']['comparison_with_previous']['percentage_change']['on_time_returns'];
                                    $onTimeChangeClass = $onTimeChange > 0 ? 'text-success' : ($onTimeChange < 0 ? 'text-danger' : 'text-muted' );
                                        $onTimeChangeIcon=$onTimeChange> 0 ? 'fa-arrow-up' : ($onTimeChange < 0 ? 'fa-arrow-down' : 'fa-equals' );
                                            @endphp
                                            <span class="{{ $onTimeChangeClass }}">
                                            <i class="fas {{ $onTimeChangeIcon }}"></i>
                                            {{ abs($onTimeChange) }}%
                                            </span>
                                            <span class="text-muted ms-2">({{ $statistics['hr']['comparison_with_previous']['previous_period']['on_time_returns'] }})</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="comparison-card">
                            <div class="card-body text-center p-3">
                                <h6 class="text-muted mb-3">التأخير عن العودة</h6>
                                <h3 class="mb-2">{{ $statistics['hr']['comparison_with_previous']['current_period']['late_returns'] }}</h3>
                                <div class="d-flex justify-content-center">
                                    @php
                                    $lateChange = $statistics['hr']['comparison_with_previous']['percentage_change']['late_returns'];
                                    $lateChangeClass = $lateChange < 0 ? 'text-success' : ($lateChange> 0 ? 'text-danger' : 'text-muted');
                                        $lateChangeIcon = $lateChange > 0 ? 'fa-arrow-up' : ($lateChange < 0 ? 'fa-arrow-down' : 'fa-equals' );
                                            @endphp
                                            <span class="{{ $lateChangeClass }}">
                                            <i class="fas {{ $lateChangeIcon }}"></i>
                                            {{ abs($lateChange) }}%
                                            </span>
                                            <span class="text-muted ms-2">({{ $statistics['hr']['comparison_with_previous']['previous_period']['late_returns'] }})</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- الرسومات البيانية -->
                <div class="stats-grid">
                    <div class="hr-chart-wrapper">
                        <div class="hr-chart-card">
                            <div class="hr-chart-header">
                                <span><i class="fas fa-chart-pie"></i> توزيع حالات الطلبات</span>
                            </div>
                            <div class="hr-chart-body">
                                <canvas id="hrRequestsChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="hr-chart-wrapper">
                        <div class="hr-chart-card">
                            <div class="hr-chart-header">
                                <span><i class="fas fa-clock"></i> حالة العودة للطلبات المعتمدة</span>
                            </div>
                            <div class="hr-chart-body">
                                <canvas id="hrReturnStatusChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="hr-chart-wrapper">
                        <div class="hr-chart-card">
                            <div class="hr-chart-header">
                                <span><i class="fas fa-building"></i> إحصائيات الأقسام (الدقائق)</span>
                            </div>
                            <div class="hr-chart-body">
                                <canvas id="hrDepartmentMinutesChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="hr-chart-wrapper">
                        <div class="hr-chart-card">
                            <div class="hr-chart-header">
                                <span><i class="fas fa-calendar-day"></i> الاتجاه اليومي للطلبات</span>
                            </div>
                            <div class="hr-chart-body">
                                <canvas id="hrDailyTrendChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="hr-chart-wrapper">
                        <div class="hr-chart-card">
                            <div class="hr-chart-header">
                                <span><i class="fas fa-calendar-week"></i> أكثر أيام الأسبوع ازدحاماً</span>
                            </div>
                            <div class="hr-chart-body">
                                <canvas id="hrBusiestDaysChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="hr-chart-wrapper">
                        <div class="hr-chart-card">
                            <div class="hr-chart-header">
                                <span><i class="fas fa-hourglass-half"></i> أكثر ساعات العمل ازدحاماً</span>
                            </div>
                            <div class="hr-chart-body">
                                <canvas id="hrBusiestHoursChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
