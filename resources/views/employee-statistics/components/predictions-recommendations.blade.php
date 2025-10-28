<div class="col-md-6">
    <div class="predictions-section p-4 bg-light rounded-3">
        <h6 class="fw-bold mb-4">التنبؤات والتوصيات</h6>

        <!-- الفترات الزمنية للتقييم والتنبؤ -->
        <div class="periods-info mb-4 p-3 border-start border-info border-3 bg-light">
            <h6 class="mb-3">الفترات الزمنية</h6>
            <div class="d-flex flex-column gap-2">
                <div class="period-item">
                    <div class="fw-bold text-muted">الفترة السابقة:</div>
                    <div>{{ $employee->performance_predictions['previous_period']['label'] }}</div>
                </div>
                <div class="period-item">
                    <div class="fw-bold text-muted">الفترة الحالية:</div>
                    <div>{{ $employee->performance_predictions['current_period']['label'] }}</div>
                </div>
                <div class="period-item">
                    <div class="fw-bold text-primary">التنبؤ للفترة:</div>
                    <div class="text-primary">{{ $employee->performance_predictions['prediction_period']['label'] }}</div>
                </div>
            </div>
        </div>

        <!-- إجمالي الفترتين -->
        <div class="total-periods mb-4 p-3 border rounded bg-light">
            <h6 class="mb-3">إجمالي الفترتين (الحالية والسابقة)</h6>

            <div class="row g-3">
                <div class="col-6">
                    <div class="text-center">
                        <div class="text-muted mb-2">إجمالي أيام العمل</div>
                        <span class="badge bg-secondary">
                            {{ $employee->total_periods_stats['total_working_days'] ?? 0 }} يوم
                        </span>
                    </div>
                </div>

                <div class="col-6">
                    <div class="text-center">
                        <div class="text-muted mb-2">إجمالي أيام الحضور</div>
                        <span class="badge bg-primary">
                            {{ $employee->total_periods_stats['total_attendance_days'] ?? 0 }} يوم
                        </span>
                    </div>
                </div>

                <div class="col-12">
                    <div class="text-center">
                        <div class="text-muted mb-2">نسبة الحضور الإجمالية</div>
                        <div class="progress" style="height: 25px;">
                            <div class="progress-bar {{ ($employee->total_periods_stats['total_attendance_percentage'] ?? 0) >= 90 ? 'bg-success' : (($employee->total_periods_stats['total_attendance_percentage'] ?? 0) >= 75 ? 'bg-warning' : 'bg-danger') }}"
                            role="progressbar"
                            style="width: {{ $employee->total_periods_stats['total_attendance_percentage'] ?? 0 }}%">
                                {{ $employee->total_periods_stats['total_attendance_percentage'] ?? 0 }}%
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6">
                    <div class="text-center">
                        <div class="text-muted mb-2">إجمالي دقائق التأخير</div>
                        <span class="badge {{ ($employee->total_periods_stats['total_delays'] ?? 0) <= 240 ? 'bg-success' : 'bg-danger' }}">
                            {{ $employee->total_periods_stats['total_delays'] ?? 0 }} دقيقة
                        </span>
                    </div>
                </div>

                <div class="col-6">
                    <div class="text-center">
                        <div class="text-muted mb-2">متوسط ساعات العمل</div>
                        <span class="badge {{ ($employee->total_periods_stats['average_working_hours'] ?? 0) >= 7.5 ? 'bg-success' : (($employee->total_periods_stats['average_working_hours'] ?? 0) >= 6.5 ? 'bg-warning' : 'bg-danger') }}">
                            {{ $employee->total_periods_stats['average_working_hours'] ?? 0 }} ساعة
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="prediction-card mb-4 p-3 border rounded">
            <h6 class="mb-3">التنبؤ بالأداء للشهر القادم</h6>
            <div class="d-flex align-items-center mb-3">
                <div class="prediction-score me-3">
                    <span class="h4 mb-0">{{ $employee->performance_predictions['predicted_attendance'] }}%</span>
                </div>
                <div class="prediction-trend">
                    <span class="badge bg-{{ $employee->performance_predictions['trend_direction'] == 'تحسن' ? 'success' : ($employee->performance_predictions['trend_direction'] == 'ثابت' ? 'info' : 'warning') }}">
                        <i class="fas fa-{{ $employee->performance_predictions['trend_direction'] == 'تحسن' ? 'arrow-up' : ($employee->performance_predictions['trend_direction'] == 'ثابت' ? 'equals' : 'arrow-down') }} me-1"></i>
                        {{ $employee->performance_predictions['trend_direction'] }}
                        @if($employee->performance_predictions['trend_percentage'] > 0)
                            ({{ $employee->performance_predictions['trend_percentage'] }}%)
                        @endif
                    </span>
                </div>
            </div>
            <div class="prediction-info mt-3 small">
                <div class="fw-bold mb-2">طريقة حساب التنبؤ:</div>
                <p class="mb-1">
                    {{ $employee->performance_predictions['metric_predictions']['summary']['calculation_method']['description'] ?? 'تم الحساب بناءً على تحليل أداء الفترة الحالية مقارنة بالفترة السابقة' }}
                </p>
                <div class="mt-2">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span>الأداء الحالي:</span>
                        <span class="fw-bold">{{ $employee->performance_predictions['current_score'] }}%</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span>الأداء السابق:</span>
                        <span class="fw-bold">{{ $employee->performance_predictions['previous_score'] }}%</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>نسبة التغيير:</span>
                        <span class="fw-bold {{ $employee->performance_predictions['improvement_percentage'] > 0 ? 'text-success' : ($employee->performance_predictions['improvement_percentage'] < 0 ? 'text-danger' : '') }}">
                            {{ $employee->performance_predictions['improvement_percentage'] > 0 ? '+' : '' }}{{ $employee->performance_predictions['improvement_percentage'] }}%
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- مجالات التحسين -->
        @if(count($employee->performance_metrics['areas_for_improvement']) > 0)
        <div class="improvement-areas mb-4">
            <h6 class="mb-3">مجالات تحتاج إلى تحسين</h6>
            <ul class="list-unstyled">
                @foreach($employee->performance_metrics['areas_for_improvement'] as $area)
                <li class="mb-2">
                    <i class="fas fa-exclamation-circle text-warning me-2"></i>
                    {{ $area }}
                </li>
                @endforeach
            </ul>
        </div>
        @endif

        <!-- التوصيات -->
        <div class="recommendations">
            <h6 class="mb-3">التوصيات</h6>
            <ul class="list-unstyled">
                @foreach($employee->performance_predictions['recommendations'] as $recommendation)
                <li class="mb-2">
                    <i class="fas fa-lightbulb text-primary me-2"></i>
                    {{ $recommendation }}
                </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
