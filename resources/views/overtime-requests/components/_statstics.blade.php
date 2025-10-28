    <!-- Personal Statistics -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-user-clock"></i> إحصائياتي
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <div class="card statistics-card">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">إجمالي طلباتي</h6>
                            <h2 class="card-title mb-0">{{ $personalStatistics['total_requests'] }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card statistics-card">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">الطلبات المعتمدة</h6>
                            <h2 class="card-title mb-0">{{ $personalStatistics['approved_requests'] }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card statistics-card">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">الطلبات المعلقة</h6>
                            <h2 class="card-title mb-0">{{ $personalStatistics['pending_requests'] }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card statistics-card">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">إجمالي ساعات العمل الإضافي</h6>
                            <h2 class="card-title mb-0">{{ number_format($personalStatistics['total_hours'], 1) }}</h2>
                        </div>
                    </div>
                </div>
            </div>
            <div class="chart-section">
                <h6 class="chart-section-title">رسم بياني للإحصائيات الشخصية</h6>
                <div class="chart-container bar-chart-container chart-animate">
                    <canvas id="personalStatsChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Team Statistics for Managers -->
    @if((Auth::user()->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager', 'hr'])) && !empty($teamStatistics))
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-users"></i> إحصائيات الفريق
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <div class="card statistics-card">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">إجمالي طلبات الفريق</h6>
                            <h2 class="card-title mb-0">{{ $teamStatistics['total_requests'] }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card statistics-card">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">الطلبات المعتمدة</h6>
                            <h2 class="card-title mb-0">{{ $teamStatistics['approved_requests'] }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card statistics-card">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">الطلبات المعلقة</h6>
                            <h2 class="card-title mb-0">{{ $teamStatistics['pending_requests'] }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card statistics-card">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">إجمالي ساعات الفريق</h6>
                            <h2 class="card-title mb-0">{{ number_format($teamStatistics['total_hours'], 1) }}</h2>
                        </div>
                    </div>
                </div>
            </div>

            <div class="chart-section">
                <h6 class="chart-section-title">رسم بياني لتوزيع حالات طلبات الفريق</h6>
                <div class="chart-container pie-chart-container chart-animate">
                    <canvas id="teamStatsChart"></canvas>
                </div>
            </div>

            @if($teamStatistics['most_active_employee'])
            <div class="alert alert-info mt-3">
                <h6 class="alert-heading">الموظف الأكثر نشاطاً</h6>
                <p class="mb-0">
                    {{ $teamStatistics['most_active_employee']->name }}
                    ({{ $teamStatistics['most_active_employee']->overtime_requests_count }} طلب)
                </p>
            </div>
            @endif

            <div class="mt-4">
                <button type="button" class="btn btn-info mb-3"
                    data-bs-toggle="modal"
                    data-bs-target="#teamDetailsModal">
                    <i class="fas fa-users"></i> عرض تفاصيل موظفي الفريق
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- HR Statistics -->
    @if(Auth::user()->hasRole('hr') && !empty($hrStatistics))
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-chart-pie"></i> إحصائيات الشركة
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <div class="card statistics-card">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">إجمالي طلبات الشركة</h6>
                            <h2 class="card-title mb-0">{{ $hrStatistics['total_company_requests'] }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card statistics-card">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">إجمالي الساعات المعتمدة</h6>
                            <h2 class="card-title mb-0">{{ number_format($hrStatistics['total_approved_hours'], 1) }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card statistics-card">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">الطلبات المعلقة</h6>
                            <h2 class="card-title mb-0">{{ $hrStatistics['pending_requests'] }}</h2>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 mb-3">
                    <div class="card statistics-card">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">معدل الموافقة</h6>
                            <h2 class="card-title mb-0">{{ number_format($hrStatistics['approval_rate'], 1) }}%</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card statistics-card">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">الطلبات المرفوضة</h6>
                            <h2 class="card-title mb-0">{{ $hrStatistics['rejected_requests'] }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card statistics-card">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">متوسط الساعات لكل طلب</h6>
                            <h2 class="card-title mb-0">{{ number_format($hrStatistics['average_hours_per_request'], 1) }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card statistics-card">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">مقارنة بالفترة السابقة</h6>
                            @php
                            $percentChange = $hrStatistics['comparative_analysis']['previous_period']['approved_hours'] > 0
                            ? (($hrStatistics['comparative_analysis']['current_period']['approved_hours'] - $hrStatistics['comparative_analysis']['previous_period']['approved_hours']) / $hrStatistics['comparative_analysis']['previous_period']['approved_hours']) * 100
                            : 100;
                            @endphp
                            <h2 class="card-title mb-0 d-flex align-items-center">
                                {{ number_format($percentChange, 1) }}%
                                @if($percentChange > 0)
                                <i class="fas fa-arrow-up text-success ms-2"></i>
                                @elseif($percentChange < 0)
                                    <i class="fas fa-arrow-down text-danger ms-2"></i>
                                    @else
                                    <i class="fas fa-minus text-secondary ms-2"></i>
                                    @endif
                            </h2>
                        </div>
                    </div>
                </div>

                <div class="col-12 mt-4">
                    <h4 class="mb-3 fw-bold">تحليلات الوقت الإضافي</h4>
                </div>

                <div class="col-md-6 mb-4">
                    <div class="card chart-card">
                        <div class="card-body">
                            <h5 class="chart-title">توزيع حالات الطلبات</h5>
                            <div class="chart-container">
                                <canvas id="hrStatsChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mb-4">
                    <div class="card chart-card">
                        <div class="card-body">
                            <h5 class="chart-title">تحليل الأقسام</h5>
                            <div class="chart-container">
                                <canvas id="departmentsStatsChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mb-4">
                    <div class="card chart-card">
                        <div class="card-body">
                            <h5 class="chart-title">تحليل أيام الأسبوع</h5>
                            <div class="chart-container">
                                <canvas id="dayOfWeekChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mb-4">
                    <div class="card chart-card">
                        <div class="card-body">
                            <h5 class="chart-title">الاتجاهات الشهرية</h5>
                            <div class="chart-container">
                                <canvas id="monthlyTrendsChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Department Efficiency Section -->
                <div class="col-12 mt-2">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-chart-line"></i> كفاءة الأقسام
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>القسم</th>
                                            <th>الساعات المعتمدة</th>
                                            <th>إجمالي الساعات المطلوبة</th>
                                            <th>معدل الكفاءة</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($hrStatistics['department_efficiency'] as $dept)
                                        <tr>
                                            <td>{{ $dept->department }}</td>
                                            <td>{{ number_format($dept->approved_hours, 1) }}</td>
                                            <td>{{ number_format($dept->total_requested_hours, 1) }}</td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="progress flex-grow-1" style="height: 10px;">
                                                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $dept->efficiency_rate }}%;" aria-valuenow="{{ $dept->efficiency_rate }}" aria-valuemin="0" aria-valuemax="100"></div>
                                                    </div>
                                                    <span class="ms-2">{{ number_format($dept->efficiency_rate, 1) }}%</span>
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

                <!-- Top Overtime Users -->
                <div class="col-12 mt-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-user-clock"></i> أكثر الموظفين استخداما للعمل الإضافي
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>اسم الموظف</th>
                                            <th>القسم</th>
                                            <th>عدد الطلبات</th>
                                            <th>إجمالي الساعات المعتمدة</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($hrStatistics['top_employees'] as $key => $employee)
                                        <tr>
                                            <td>{{ $key + 1 }}</td>
                                            <td>{{ $employee->name }}</td>
                                            <td>{{ $employee->department }}</td>
                                            <td>{{ $employee->total_requests }}</td>
                                            <td>{{ number_format($employee->approved_hours, 1) }}</td>
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
    </div>
    @endif
