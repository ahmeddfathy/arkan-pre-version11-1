<div class="mt-3">
    <h5 class="card-title fw-bold">
        <i class="fas fa-chart-line text-success me-2"></i>تفاصيل التحسن في كل مؤشر
    </h5>
    <div class="table-responsive">
        <table class="table table-sm table-bordered">
            <thead class="table-light">
                <tr>
                    <th>المؤشر</th>
                    <th>الفترة السابقة</th>
                    <th>الفترة الحالية</th>
                    <th>التغيير</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <i class="fas fa-user-check text-primary me-1"></i>الحضور
                    </td>
                    <td>{{ $employee->performance_predictions['metric_predictions']['attendance']['previous'] ?? 0 }}</td>
                    <td>{{ $employee->performance_predictions['metric_predictions']['attendance']['current'] ?? 0 }}</td>
                    <td>
                        @if(isset($employee->performance_predictions['metric_predictions']['attendance']['improvement_percentage']))
                            <span
                                class="badge {{ $employee->performance_predictions['metric_predictions']['attendance']['improvement_percentage'] > 0 ? 'bg-success' : ($employee->performance_predictions['metric_predictions']['attendance']['improvement_percentage'] < 0 ? 'bg-danger' : 'bg-secondary') }}"
                                data-bs-toggle="tooltip"
                                data-bs-html="true"
                                data-bs-placement="top"
                                title="
                                <div class='text-start'>
                                <strong>تفاصيل:</strong><br>
                                - القيمة الحالية: {{ $employee->performance_predictions['metric_predictions']['attendance']['current'] }}%<br>
                                - القيمة السابقة: {{ $employee->performance_predictions['metric_predictions']['attendance']['previous'] }}%<br>
                                - الفرق: {{ $employee->performance_predictions['metric_predictions']['attendance']['improvement'] }}%<br>
                                - نسبة التغيير: {{ $employee->performance_predictions['metric_predictions']['attendance']['improvement_percentage'] }}%
                                </div>
                                "
                            >
                                {{ abs($employee->performance_predictions['metric_predictions']['attendance']['improvement_percentage']) }}%
                                @if($employee->performance_predictions['metric_predictions']['attendance']['improvement_percentage'] > 0)
                                    <i class="fas fa-arrow-up"></i>
                                @elseif($employee->performance_predictions['metric_predictions']['attendance']['improvement_percentage'] < 0)
                                    <i class="fas fa-arrow-down"></i>
                                @endif
                            </span>
                        @else
                            -
                        @endif
                    </td>
                </tr>
                <tr>
                    <td>
                        <i class="fas fa-clock text-warning me-1"></i>الالتزام بالمواعيد
                    </td>
                    <td>{{ $employee->performance_predictions['metric_predictions']['punctuality']['previous'] ?? 0 }}</td>
                    <td>{{ $employee->performance_predictions['metric_predictions']['punctuality']['current'] ?? 0 }}</td>
                    <td>
                        @if(isset($employee->performance_predictions['metric_predictions']['punctuality']['improvement_percentage']))
                            <span
                                class="badge {{ $employee->performance_predictions['metric_predictions']['punctuality']['improvement_percentage'] > 0 ? 'bg-success' : ($employee->performance_predictions['metric_predictions']['punctuality']['improvement_percentage'] < 0 ? 'bg-danger' : 'bg-secondary') }}"
                                data-bs-toggle="tooltip"
                                data-bs-html="true"
                                data-bs-placement="top"
                                title="
                                <div class='text-start'>
                                <strong>تفاصيل:</strong><br>
                                - القيمة الحالية: {{ $employee->performance_predictions['metric_predictions']['punctuality']['current'] }}%<br>
                                - القيمة السابقة: {{ $employee->performance_predictions['metric_predictions']['punctuality']['previous'] }}%<br>
                                - الفرق: {{ $employee->performance_predictions['metric_predictions']['punctuality']['improvement'] }}%<br>
                                - نسبة التغيير: {{ $employee->performance_predictions['metric_predictions']['punctuality']['improvement_percentage'] }}%
                                </div>
                                "
                            >
                                {{ abs($employee->performance_predictions['metric_predictions']['punctuality']['improvement_percentage']) }}%
                                @if($employee->performance_predictions['metric_predictions']['punctuality']['improvement_percentage'] > 0)
                                    <i class="fas fa-arrow-up"></i>
                                @elseif($employee->performance_predictions['metric_predictions']['punctuality']['improvement_percentage'] < 0)
                                    <i class="fas fa-arrow-down"></i>
                                @endif
                            </span>
                        @else
                            -
                        @endif
                    </td>
                </tr>
                <tr>
                    <td>
                        <i class="fas fa-business-time text-info me-1"></i>ساعات العمل
                    </td>
                    <td>{{ $employee->performance_predictions['metric_predictions']['working_hours']['previous'] ?? 0 }}</td>
                    <td>{{ $employee->performance_predictions['metric_predictions']['working_hours']['current'] ?? 0 }}</td>
                    <td>
                        @if(isset($employee->performance_predictions['metric_predictions']['working_hours']['improvement_percentage']))
                            <span
                                class="badge {{ $employee->performance_predictions['metric_predictions']['working_hours']['improvement_percentage'] > 0 ? 'bg-success' : ($employee->performance_predictions['metric_predictions']['working_hours']['improvement_percentage'] < 0 ? 'bg-danger' : 'bg-secondary') }}"
                                data-bs-toggle="tooltip"
                                data-bs-html="true"
                                data-bs-placement="top"
                                title="
                                <div class='text-start'>
                                <strong>تفاصيل:</strong><br>
                                - القيمة الحالية: {{ $employee->performance_predictions['metric_predictions']['working_hours']['current'] }}%<br>
                                - القيمة السابقة: {{ $employee->performance_predictions['metric_predictions']['working_hours']['previous'] }}%<br>
                                - الفرق: {{ $employee->performance_predictions['metric_predictions']['working_hours']['improvement'] }}%<br>
                                - نسبة التغيير: {{ $employee->performance_predictions['metric_predictions']['working_hours']['improvement_percentage'] }}%
                                </div>
                                "
                            >
                                {{ abs($employee->performance_predictions['metric_predictions']['working_hours']['improvement_percentage']) }}%
                                @if($employee->performance_predictions['metric_predictions']['working_hours']['improvement_percentage'] > 0)
                                    <i class="fas fa-arrow-up"></i>
                                @elseif($employee->performance_predictions['metric_predictions']['working_hours']['improvement_percentage'] < 0)
                                    <i class="fas fa-arrow-down"></i>
                                @endif
                            </span>
                        @else
                            -
                        @endif
                    </td>
                </tr>
                <tr class="table-active">
                    <td class="fw-bold">التقييم الإجمالي</td>
                    <td>{{ $employee->performance_predictions['previous_score'] ?? 0 }}</td>
                    <td>{{ $employee->performance_predictions['current_score'] ?? 0 }}</td>
                    <td>
                        @if(isset($employee->performance_predictions['improvement_percentage']))
                            <span
                                class="badge {{ $employee->performance_predictions['improvement_percentage'] > 0 ? 'bg-success' : ($employee->performance_predictions['improvement_percentage'] < 0 ? 'bg-danger' : 'bg-secondary') }}"
                                data-bs-toggle="tooltip"
                                data-bs-html="true"
                                data-bs-placement="top"
                                title="
                                <div class='text-start'>
                                <strong>تفاصيل الحساب:</strong><br>
                                - التقييم الحالي: {{ $employee->performance_predictions['current_score'] }}%<br>
                                - التقييم السابق: {{ $employee->performance_predictions['previous_score'] }}%<br>
                                - الفرق: {{ $employee->performance_predictions['current_score'] - $employee->performance_predictions['previous_score'] }}%<br>
                                - صيغة الحساب: (التقييم الحالي - التقييم السابق) ÷ التقييم السابق × 100<br>
                                - الحساب: ({{ $employee->performance_predictions['current_score'] }} - {{ $employee->performance_predictions['previous_score'] }}) ÷ {{ $employee->performance_predictions['previous_score'] }} × 100 = {{ $employee->performance_predictions['improvement_percentage'] }}%
                                </div>
                                "
                            >
                                {{ abs($employee->performance_predictions['improvement_percentage']) }}%
                                @if($employee->performance_predictions['improvement_percentage'] > 0)
                                    <i class="fas fa-arrow-up"></i>
                                @elseif($employee->performance_predictions['improvement_percentage'] < 0)
                                    <i class="fas fa-arrow-down"></i>
                                @endif
                            </span>
                        @else
                            -
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
