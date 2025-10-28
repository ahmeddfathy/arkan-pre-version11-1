<tr class="performance-analysis">
    <td colspan="14">
        <div class="card border-0 shadow-sm mt-3">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-line me-2"></i>
                    تحليل الأداء والتنبؤات المستقبلية
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <!-- مؤشرات الأداء -->
                    <div class="col-md-6">
                        <div class="performance-metrics p-4 bg-light rounded-3">
                            <h6 class="fw-bold mb-4">مؤشرات الأداء الحالية</h6>

                            <div class="metric-item mb-4">
                                <label class="d-flex justify-content-between mb-2">
                                    <span>الدرجة الكلية</span>
                                    <span class="badge bg-{{ $employee->performance_metrics['overall_score'] >= 80 ? 'success' : ($employee->performance_metrics['overall_score'] >= 60 ? 'warning' : 'danger') }}">
                                        {{ $employee->performance_metrics['overall_score'] }}%
                                    </span>
                                </label>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-{{ $employee->performance_metrics['overall_score'] >= 80 ? 'success' : ($employee->performance_metrics['overall_score'] >= 60 ? 'warning' : 'danger') }}"
                                        role="progressbar"
                                        style="width: {{ $employee->performance_metrics['overall_score'] }}%"></div>
                                </div>
                            </div>

                            <!-- حالة التأخير -->
                            <div class="metric-item mb-4">
                                <label class="d-flex justify-content-between mb-2">
                                    <span>دقائق التأخير</span>
                                    <span class="badge bg-{{ $employee->performance_metrics['delay_status']['is_good'] ? 'success' : 'danger' }}">
                                        {{ $employee->performance_metrics['delay_status']['minutes'] }} دقيقة
                                    </span>
                                </label>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-{{ $employee->performance_metrics['delay_status']['is_good'] ? 'success' : 'danger' }}"
                                        role="progressbar"
                                        style="width: {{ $employee->performance_metrics['delay_status']['percentage'] }}%"></div>
                                </div>
                                @php
                                    // Calculate the months difference between start and end date
                                    $startDateObj = \Carbon\Carbon::parse($startDate);
                                    $endDateObj = \Carbon\Carbon::parse($endDate);
                                    // حساب الفرق بالشهور بدقة، بدون إضافة 1
                                    $monthsDifference = max(1, $startDateObj->diffInMonths($endDateObj));

                                    // التعامل مع حالة الشهر الواحد المقسم على شهرين ميلاديين
                                    if ($monthsDifference < 1 && $startDateObj->format('m') != $endDateObj->format('m')) {
                                        $monthsDifference = 1;
                                    }

                                    // تقريب قيمة الشهور إلى رقم صحيح للعرض
                                    $displayMonthsDifference = round($monthsDifference);

                                    $maxAcceptableDelays = 120 * $monthsDifference;
                                    $maxAcceptablePermissions = 180 * $monthsDifference;

                                    // تقريب قيم الحدود المسموحة إلى أرقام صحيحة
                                    $displayMaxAcceptableDelays = round($maxAcceptableDelays);
                                    $displayMaxAcceptablePermissions = round($maxAcceptablePermissions);
                                @endphp
                                <small class="text-muted">الحد المسموح: {{ $displayMaxAcceptableDelays }} دقيقة ({{ $displayMonthsDifference }} شهر)</small>
                            </div>

                            <!-- حالة الأذونات -->
                            <div class="metric-item mb-4">
                                <label class="d-flex justify-content-between mb-2">
                                    <span>دقائق الأذونات</span>
                                    <span class="badge bg-{{ $employee->performance_metrics['permissions_status']['is_good'] ? 'success' : 'danger' }}">
                                        {{ $employee->performance_metrics['permissions_status']['minutes'] }} دقيقة
                                    </span>
                                </label>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-{{ $employee->performance_metrics['permissions_status']['is_good'] ? 'success' : 'danger' }}"
                                        role="progressbar"
                                        style="width: {{ $employee->performance_metrics['permissions_status']['percentage'] }}%"></div>
                                </div>
                                <small class="text-muted">الحد المسموح: {{ $displayMaxAcceptablePermissions }} دقيقة ({{ $displayMonthsDifference }} شهر)</small>
                            </div>

                            <!-- مؤشرات الأداء التفصيلية -->
                            <div class="metric-details">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <div class="metric-card text-center p-3 border rounded">
                                            <div class="metric-value text-primary h4 mb-1">
                                                {{ $employee->performance_metrics['attendance_score'] }}%
                                            </div>
                                            <div class="metric-label small">الحضور</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="metric-card text-center p-3 border rounded">
                                            <div class="metric-value text-success h4 mb-1">
                                                {{ $employee->performance_metrics['punctuality_score'] }}%
                                            </div>
                                            <div class="metric-label small">الانضباط</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="metric-card text-center p-3 border rounded">
                                            <div class="metric-value text-info h4 mb-1">
                                                {{ $employee->performance_metrics['working_hours_score'] }}%
                                            </div>
                                            <div class="metric-label small">ساعات العمل</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- تفاصيل النقاط المخصومة -->
                            <div class="mt-3">
                                <h5 class="card-title fw-bold">
                                    <i class="fas fa-chart-line text-info me-2"></i>تفاصيل النقاط المخصومة
                                </h5>
                                @if(!isset($employee->performance_metrics['show_deductions']) || !$employee->performance_metrics['show_deductions'])
                                <div class="text-center text-muted p-3">
                                    <i class="fas fa-info-circle me-1"></i>
                                    لا توجد خصومات مسجلة
                                </div>
                                @else
                                <ul class="list-unstyled mb-0">
                                    @if($employee->performance_metrics['attendance_score'] < 100)
                                    <li class="mb-2">
                                        <i class="fas fa-minus-circle text-danger me-1"></i>
                                        <span>
                                            خصم
                                            <span class="badge bg-danger deduction-badge"
                                                  data-bs-toggle="tooltip"
                                                  data-bs-html="true"
                                                  data-bs-placement="top"
                                                  title="
                                                  <div class='text-start'>
                                                    <strong>تفاصيل الخصم:</strong><br>
                                                    - نسبة الغياب: {{ round(100 - $employee->performance_metrics['attendance_score'], 1) }}%<br>
                                                    - وزن مؤشر الحضور: 45% من التقييم الكلي<br>
                                                    - حساب الخصم: {{ round(100 - $employee->performance_metrics['attendance_score'], 1) }}% × 45% = {{ round((100 - $employee->performance_metrics['attendance_score']) * 0.45, 1) }}%<br>
                                                    - أيام الغياب: {{ $employee->absences }} {{ $employee->absences > 1 ? 'أيام' : 'يوم' }}<br>
                                                    - الغياب يؤثر سلباً على أدائك الكلي!
                                                  </div>
                                                  ">
                                                {{ round((100 - $employee->performance_metrics['attendance_score']) * 0.45, 1) }}%
                                            </span>
                                            من التقييم النهائي بسبب الغياب
                                        </span>
                                    </li>
                                    @endif

                                    @if($employee->performance_metrics['punctuality_score'] < 100)
                                    <li class="mb-2">
                                        <i class="fas fa-minus-circle text-danger me-1"></i>
                                        <span>
                                            خصم
                                            <span class="badge bg-danger deduction-badge"
                                                  data-bs-toggle="tooltip"
                                                  data-bs-html="true"
                                                  data-bs-placement="top"
                                                  title="
                                                  <div class='text-start'>
                                                    <strong>تفاصيل الخصم:</strong><br>
                                                    - دقائق التأخير: {{ $employee->delays }} دقيقة<br>
                                                    @php
                                                        // Calculate the months difference between start and end date
                                                        $startDateObj = \Carbon\Carbon::parse($startDate);
                                                        $endDateObj = \Carbon\Carbon::parse($endDate);
                                                        // حساب الفرق بالشهور بدقة، بدون إضافة 1
                                                        $monthsDifference = max(1, $startDateObj->diffInMonths($endDateObj));

                                                        // التعامل مع حالة الشهر الواحد المقسم على شهرين ميلاديين
                                                        if ($monthsDifference < 1 && $startDateObj->format('m') != $endDateObj->format('m')) {
                                                            $monthsDifference = 1;
                                                        }

                                                        // تقريب قيمة الشهور إلى رقم صحيح للعرض
                                                        $displayMonthsDifference = round($monthsDifference);

                                                        $maxAcceptableDelays = 120 * $monthsDifference;
                                                        $maxAcceptablePermissions = 180 * $monthsDifference;

                                                        // تقريب قيم الحدود المسموحة إلى أرقام صحيحة
                                                        $displayMaxAcceptableDelays = round($maxAcceptableDelays);
                                                        $displayMaxAcceptablePermissions = round($maxAcceptablePermissions);

                                                        // تقريب قيمة تجاوز الحد
                                                        $excessDelay = max(0, $employee->delays - $maxAcceptableDelays);
                                                        $displayExcessDelay = round($excessDelay);
                                                    @endphp
                                                    - الحد المسموح: {{ $displayMaxAcceptableDelays }} دقيقة لفترة {{ $displayMonthsDifference }} شهر<br>
                                                    - تجاوز الحد بـ: {{ $displayExcessDelay }} دقيقة<br>
                                                    - نسبة الخصم من مؤشر الالتزام: {{ round(100 - $employee->performance_metrics['punctuality_score'], 1) }}%<br>
                                                    - وزن مؤشر الالتزام: 20% من التقييم الكلي<br>
                                                    - حساب الخصم: {{ round(100 - $employee->performance_metrics['punctuality_score'], 1) }}% × 20% = {{ round((100 - $employee->performance_metrics['punctuality_score']) * 0.2, 1) }}%
                                                  </div>
                                                  ">
                                                {{ round((100 - $employee->performance_metrics['punctuality_score']) * 0.2, 1) }}%
                                            </span>
                                            من التقييم النهائي بسبب التأخير
                                        </span>
                                    </li>
                                    @endif

                                    @if($employee->performance_metrics['working_hours_score'] < 100)
                                    <li class="mb-2">
                                        <i class="fas fa-minus-circle text-danger me-1"></i>
                                        <span>
                                            خصم
                                            <span class="badge bg-danger deduction-badge"
                                                  data-bs-toggle="tooltip"
                                                  data-bs-html="true"
                                                  data-bs-placement="top"
                                                  title="
                                                  <div class='text-start'>
                                                    <strong>تفاصيل الخصم:</strong><br>
                                                    - متوسط ساعات العمل: {{ $employee->average_working_hours ?? 0 }} ساعة<br>
                                                    - المعيار المطلوب: 8 ساعات يومياً<br>
                                                    - نسبة ساعات العمل: {{ round(($employee->average_working_hours ?? 0) / 8 * 100, 1) }}%<br>
                                                    - نسبة الخصم من مؤشر ساعات العمل: {{ round(100 - $employee->performance_metrics['working_hours_score'], 1) }}%<br>
                                                    - وزن مؤشر ساعات العمل: 35% من التقييم الكلي<br>
                                                    - حساب الخصم: {{ round(100 - $employee->performance_metrics['working_hours_score'], 1) }}% × 35% = {{ round((100 - $employee->performance_metrics['working_hours_score']) * 0.35, 1) }}%
                                                  </div>
                                                  ">
                                                {{ round((100 - $employee->performance_metrics['working_hours_score']) * 0.35, 1) }}%
                                            </span>
                                            من التقييم النهائي بسبب قلة ساعات العمل
                                        </span>
                                    </li>
                                    @endif

                                    @if($employee->performance_metrics['attendance_score'] == 100 &&
                                        $employee->performance_metrics['punctuality_score'] == 100 &&
                                        $employee->performance_metrics['working_hours_score'] == 100)
                                    <li class="mb-1">
                                        <i class="fas fa-check-circle text-success me-1"></i>
                                        <span class="text-success">لا توجد نقاط مخصومة. مستوى الأداء ممتاز في جميع المؤشرات!</span>
                                    </li>
                                    @endif
                                </ul>
                                @endif
                            </div>

                            <!-- معلومات فترات المقارنة -->
                            <div class="mt-4">
                                <h5 class="card-title fw-bold">
                                    <i class="fas fa-calendar-alt text-primary me-2"></i>فترات المقارنة للتقييم
                                </h5>
                                <div class="alert alert-info py-2">
                                    <div class="row">
                                        <div class="col-12 mb-2">
                                            <span class="fw-bold">الفترة الحالية:</span>
                                            {{ $employee->performance_predictions['current_period']['label'] ?? 'غير متوفر' }}
                                        </div>
                                        <div class="col-12 mb-2">
                                            <span class="fw-bold">الفترة السابقة:</span>
                                            {{ $employee->performance_predictions['previous_period']['label'] ?? 'غير متوفر' }}
                                        </div>
                                        <div class="col-12">
                                            <span class="fw-bold">نسبة التحسن:</span>
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
                                                        <i class="fas fa-arrow-up"></i> تحسن
                                                    @elseif($employee->performance_predictions['improvement_percentage'] < 0)
                                                        <i class="fas fa-arrow-down"></i> تراجع
                                                    @else
                                                        <i class="fas fa-minus"></i> ثابت
                                                    @endif
                                                </span>
                                            @else
                                                غير متوفر
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- تفاصيل التحسن في كل مؤشر -->
                            @include('employee-statistics.components.improvement-details', ['employee' => $employee])
                        </div>
                    </div>

                    <!-- التنبؤات والتوصيات -->
                    @include('employee-statistics.components.predictions-recommendations', ['employee' => $employee])
                </div>
            </div>
        </div>
    </td>
</tr>
