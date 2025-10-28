@extends('layouts.app')

@section('title', 'إضافة مشروع جديد')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/projects.css') }}">
<link rel="stylesheet" href="{{ asset('css/projects/project-create-modern.css') }}">
@endpush

@section('content')
<div class="create-modern-container">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-11">
            <div class="create-glass-card">
                <div class="create-modern-header">
                    <div class="create-header-icon">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <h3 class="create-header-title">
                        إنشاء مشروع جديد
                    </h3>
                    <p class="text-white-50 mb-0 mt-2">أضف مشروعك الجديد وابدأ رحلة النجاح معنا</p>
                </div>
                <div class="create-form-body">
                    <form action="{{ route('projects.store') }}" method="POST">
                        @csrf

                        <div class="row">
                            <!-- معلومات المشروع الأساسية -->
                            <div class="col-md-6">
                                <div class="create-section-header info">
                                    <i class="fas fa-info-circle"></i>
                                    <h5>معلومات المشروع الأساسية</h5>
                                </div>

                                <div class="create-form-group">
                                    <label for="name" class="create-form-label">
                                        <i class="fas fa-project-diagram"></i>
                                        اسم المشروع *
                                    </label>
                                    <input type="text"
                                           class="create-form-control @error('name') is-invalid @enderror"
                                           id="name"
                                           name="name"
                                           value="{{ old('name') }}"
                                           placeholder="اكتب اسم مشروعك هنا..."
                                           required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>


                                <div class="create-form-group">
                                    <label for="company_type" class="create-form-label">
                                        <i class="fas fa-building"></i>
                                        نوع الشركة *
                                    </label>
                                    <select class="create-form-control @error('company_type') is-invalid @enderror"
                                            id="company_type"
                                            name="company_type"
                                            required>
                                        <option value="">🏢 اختر نوع الشركة</option>
                                        <option value="A" {{ old('company_type') == 'A' ? 'selected' : '' }}>A - أركان</option>
                                        <option value="K" {{ old('company_type') == 'K' ? 'selected' : '' }}>K - خبراء</option>
                                    </select>
                                    @error('company_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="create-form-group">
                                    <label for="code" class="create-form-label">
                                        <i class="fas fa-qrcode"></i>
                                        كود المشروع
                                    </label>
                                    <div class="create-input-group">
                                        <input type="text"
                                               class="create-form-control @error('code') is-invalid @enderror"
                                               id="code"
                                               name="code"
                                               value="{{ old('code') }}"
                                               placeholder="سيتم توليد الكود تلقائياً...">
                                        <button class="create-generate-btn" type="button" id="generate-code">
                                            <i class="fas fa-magic"></i> توليد تلقائي
                                        </button>
                                        @error('code')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        سيتم توليد كود تلقائي بالشكل: A/K + السنة + الشهر + رقم المشروع
                                        <br>
                                        <strong>مثال:</strong> A20241201 (أركان، 2024، ديسمبر، المشروع الأول)
                                    </small>
                                </div>

                                <div class="create-form-group">
                                    <label for="description" class="create-form-label">
                                        <i class="fas fa-align-left"></i>
                                        وصف المشروع
                                    </label>
                                    <textarea class="create-form-control @error('description') is-invalid @enderror"
                                              id="description"
                                              name="description"
                                              rows="4"
                                              placeholder="اكتب وصفاً مفصلاً عن مشروعك وأهدافه...">{{ old('description') }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="create-form-group">
                                    <label for="client_id" class="create-form-label">
                                        <i class="fas fa-user-tie"></i>
                                        العميل *
                                    </label>
                                    <select class="create-form-control @error('client_id') is-invalid @enderror"
                                            id="client_id"
                                            name="client_id"
                                            required>
                                        <option value="">🏢 اختر العميل المناسب</option>
                                        @foreach($clients as $client)
                                            <option value="{{ $client->id }}"
                                                    {{ old('client_id') == $client->id ? 'selected' : '' }}>
                                                {{ $client->name }} - {{ $client->code }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('client_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="create-form-group">
                                            <label for="start_date" class="create-form-label">
                                                <i class="fas fa-calendar-plus"></i>
                                                تاريخ البداية
                                            </label>
                                            <input type="date"
                                                   class="create-form-control @error('start_date') is-invalid @enderror"
                                                   id="start_date"
                                                   name="start_date"
                                                   value="{{ old('start_date') }}">
                                            @error('start_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="create-form-group">
                                            <label for="client_agreed_delivery_date" class="create-form-label">
                                                <i class="fas fa-handshake"></i>
                                                تاريخ التسليم المتفق عليه
                                            </label>
                                            <input type="date"
                                                   class="create-form-control @error('client_agreed_delivery_date') is-invalid @enderror"
                                                   id="client_agreed_delivery_date"
                                                   name="client_agreed_delivery_date"
                                                   value="{{ old('client_agreed_delivery_date') }}">
                                            @error('client_agreed_delivery_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="create-form-group">
                                    <label for="note" class="create-form-label">
                                        <i class="fas fa-sticky-note"></i>
                                        ملاحظات هامة
                                    </label>
                                    <textarea class="create-form-control @error('note') is-invalid @enderror"
                                              id="note" name="note" rows="3"
                                              placeholder="اكتب أي ملاحظات هامة حول المشروع...">{{ old('note') }}</textarea>
                                    @error('note')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="create-form-group">
                                            <label for="team_delivery_date" class="create-form-label">
                                                <i class="fas fa-users-cog"></i>
                                                تاريخ تسليم الفريق
                                            </label>
                                            <input type="date" class="create-form-control @error('team_delivery_date') is-invalid @enderror"
                                                   id="team_delivery_date" name="team_delivery_date" value="{{ old('team_delivery_date') }}">
                                            @error('team_delivery_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="create-form-group">
                                    <label for="status" class="create-form-label">
                                        <i class="fas fa-chart-line"></i>
                                        حالة المشروع
                                    </label>
                                    <div class="create-status-display">
                                        <div class="status-badge status-new">
                                            <i class="fas fa-plus-circle"></i>
                                            <span>جديد</span>
                                        </div>
                                        <small class="text-muted d-block mt-2">
                                            <i class="fas fa-info-circle me-1"></i>
                                            المشاريع الجديدة تبدأ بحالة "جديد" دائماً. الحالات الأخرى تحدث تلقائياً حسب تقدم العمل.
                                        </small>
                                    </div>
                                    <!-- Hidden input للتأكد من إرسال القيمة -->
                                    <input type="hidden" name="status" value="جديد">
                                </div>

                                <div class="create-form-group">
                                    <div class="create-alert" style="background: rgba(255, 107, 107, 0.1); border-color: rgba(255, 107, 107, 0.3);">
                                        <div class="form-check form-switch" style="display: flex; align-items: center; gap: 1rem; margin: 0;">
                                            <input class="form-check-input @error('is_urgent') is-invalid @enderror"
                                                   type="checkbox"
                                                   id="is_urgent"
                                                   name="is_urgent"
                                                   value="1"
                                                   style="width: 20px; height: 20px; accent-color: #ff6b6b;"
                                                   {{ old('is_urgent') ? 'checked' : '' }}>
                                            <div>
                                                <label class="form-check-label" for="is_urgent" style="color: #dc3545; font-weight: 700; margin: 0; cursor: pointer;">
                                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                                    مشروع مستعجل وعاجل
                                                </label>
                                                <p style="margin: 0.3rem 0 0 0; font-size: 0.85rem; color: #6c757d;">
                                                    سيتم إعطاء هذا المشروع الأولوية العالية في العرض والإشعارات
                                                </p>
                                            </div>
                                        </div>
                                        @error('is_urgent')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- فترة التحضير -->
                                <div class="create-form-group">
                                    <div class="create-alert" style="background: rgba(103, 126, 234, 0.1); border-color: rgba(103, 126, 234, 0.3);">
                                        <div class="form-check form-switch mb-3" style="display: flex; align-items: center; gap: 1rem; margin: 0;">
                                            <input class="form-check-input @error('preparation_enabled') is-invalid @enderror"
                                                   type="checkbox"
                                                   id="preparation_enabled"
                                                   name="preparation_enabled"
                                                   value="1"
                                                   style="width: 20px; height: 20px; accent-color: #667eea;"
                                                   {{ old('preparation_enabled') ? 'checked' : '' }}>
                                            <div>
                                                <label class="form-check-label" for="preparation_enabled" style="color: #667eea; font-weight: 700; margin: 0; cursor: pointer;">
                                                    <i class="fas fa-clock me-2"></i>
                                                    تفعيل فترة التحضير
                                                </label>
                                                <p style="margin: 0.3rem 0 0 0; font-size: 0.85rem; color: #6c757d;">
                                                    تحديد فترة تحضير قبل بدء العمل الفعلي على المشروع (استثناء يوم الجمعة)
                                                </p>
                                            </div>
                                        </div>
                                        @error('preparation_enabled')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror

                                        <div id="preparation-fields" style="display: none; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(103, 126, 234, 0.2);">
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label for="preparation_start_date" class="create-form-label" style="font-size: 0.85rem;">
                                                        <i class="fas fa-calendar-day me-1"></i>
                                                        تاريخ ووقت بداية التحضير
                                                    </label>
                                                    <input type="datetime-local"
                                                           class="create-form-control @error('preparation_start_date') is-invalid @enderror"
                                                           id="preparation_start_date"
                                                           name="preparation_start_date"
                                                           value="{{ old('preparation_start_date') }}"
                                                           style="font-size: 0.85rem;">
                                                    @error('preparation_start_date')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label for="preparation_days" class="create-form-label" style="font-size: 0.85rem;">
                                                        <i class="fas fa-calendar-check me-1"></i>
                                                        عدد أيام التحضير
                                                    </label>
                                                    <input type="number"
                                                           class="create-form-control @error('preparation_days') is-invalid @enderror"
                                                           id="preparation_days"
                                                           name="preparation_days"
                                                           value="{{ old('preparation_days') }}"
                                                           min="1"
                                                           placeholder="مثال: 5"
                                                           style="font-size: 0.85rem;">
                                                    @error('preparation_days')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                    <small class="form-text text-muted" style="font-size: 0.75rem;">
                                                        <i class="fas fa-info-circle me-1"></i>
                                                        سيتم حساب تاريخ النهاية تلقائياً (بدون يوم الجمعة)
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- الخدمات المختارة -->
                            <div class="col-md-6">
                                <div class="create-section-header success">
                                    <i class="fas fa-cogs"></i>
                                    <h5>الخدمات والباقات المطلوبة</h5>
                                </div>
                                <div class="create-form-group">
                                    <label class="create-form-label">
                                        <i class="fas fa-layer-group"></i>
                                        نوع الاختيار:
                                    </label>
                                    <div class="create-selection-type">
                                        <div class="create-radio-option">
                                            <input type="radio" name="selection_type" id="select_package" value="package" checked>
                                            <label for="select_package">🎁 اختيار باقة</label>
                                        </div>
                                        <div class="create-radio-option">
                                            <input type="radio" name="selection_type" id="select_services" value="services">
                                            <label for="select_services">⚙️ اختيار خدمات منفصلة</label>
                                        </div>
                                    </div>
                                </div>
                                <div id="package-section" class="create-package-section">
                                    <div class="create-form-group">
                                        <label class="create-form-label">
                                            <i class="fas fa-gift"></i>
                                            اختر الباقة
                                        </label>
                                        <select name="package_id" id="package_id" class="create-form-control">
                                            <option value="">🎁 اختر باقة مناسبة</option>
                                            @foreach($packages as $package)
                                                <option value="{{ $package['id'] }}" data-services="{{ json_encode($package['services']) }}">{{ $package['name'] }} ({{ $package['total_points'] }} نقطة)</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- عرض خدمات الباقة بعد الاختيار -->
                                    <div id="package-services-section" style="display: none;">
                                        <div class="create-alert info" style="margin-top: 1.5rem;">
                                            <i class="fas fa-box-open"></i>
                                            <span><strong>خدمات الباقة المختارة</strong> - يمكنك إلغاء أي خدمة لا تريدها</span>
                                        </div>
                                        <div class="create-services-container" id="package-services-list">
                                            <!-- سيتم ملؤها ديناميكياً عبر JavaScript -->
                                        </div>

                                        <!-- الخدمات الإضافية (خارج الباقة) -->
                                        <div class="create-alert" style="margin-top: 1.5rem; background: rgba(103, 126, 234, 0.1); border-color: rgba(103, 126, 234, 0.3);">
                                            <i class="fas fa-plus-circle"></i>
                                            <span><strong>خدمات إضافية</strong> - يمكنك إضافة خدمات أخرى غير موجودة في الباقة</span>
                                        </div>
                                        <div class="create-services-container" id="additional-services-list">
                                            <!-- سيتم ملؤها ديناميكياً عبر JavaScript -->
                                        </div>
                                    </div>
                                </div>
                                <div id="services-section" class="create-services-section" style="display:none;">
                                    <div class="create-alert info">
                                        <i class="fas fa-lightbulb"></i>
                                        <span>اختر الخدمات التي يحتاجها العميل وحدد حالة كل خدمة</span>
                                    </div>
                                    <div class="create-services-container">
                                        @foreach($services as $service)
                                            <div class="create-service-card">
                                                <div class="create-service-content">
                                                    <div class="create-service-header">
                                                        <input class="create-service-checkbox" type="checkbox" value="{{ $service->id }}" id="service_{{ $service->id }}" name="selected_services[]">
                                                        <div>
                                                            <h6 class="create-service-title">{{ $service->name }}</h6>
                                                            <p class="create-service-description">{{ $service->description }}</p>
                                                            <span class="create-service-points">
                                                                <i class="fas fa-star me-1"></i>
                                                                {{ $service->points }} نقطة
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="create-service-status">
                                                        <label class="create-form-label">
                                                            <i class="fas fa-tasks"></i>
                                                            حالة الخدمة:
                                                        </label>
                                                        <select class="create-form-control" name="service_statuses[{{ $service->id }}]">
                                                            <option value="لم تبدأ">📅 لم تبدأ</option>
                                                            <option value="قيد التنفيذ">⚙️ قيد التنفيذ</option>
                                                            <option value="مكتملة">✅ مكتملة</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="create-total-points">
                                    <h6>مجموع النقاط المختارة:</h6>
                                    <div class="points-number">
                                        <span id="total-points">0</span> نقطة
                                    </div>
                                    <i class="fas fa-star" style="position: absolute; top: 10px; right: 15px; font-size: 1.5rem; opacity: 0.3; z-index: 1;"></i>
                                </div>
                            </div>
                        </div>

                        <div class="create-actions">
                            <a href="{{ route('projects.index') }}" class="create-btn create-btn-secondary">
                                <i class="fas fa-arrow-right"></i>
                                عودة للقائمة
                            </a>
                            <button type="submit" class="create-btn create-btn-success">
                                <i class="fas fa-rocket"></i>
                                إطلاق المشروع
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>



@push('styles')
<style>
.create-status-display {
    padding: 12px;
    background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(25, 135, 84, 0.1));
    border: 1px solid rgba(40, 167, 69, 0.3);
    border-radius: 8px;
    text-align: center;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 14px;
    color: white;
    box-shadow: none;
}

.status-badge.status-new {
    background: linear-gradient(135deg, #28a745, #20c997);
}

.status-badge i {
    font-size: 16px;
}
</style>
@endpush

@push('scripts')
<script>
// تمرير بيانات الخدمات لـ JavaScript
const allServices = @json($services);

// توليد كود المشروع بناءً على نوع الشركة
document.getElementById('generate-code').addEventListener('click', function() {
    const companyType = document.getElementById('company_type').value;

    if (!companyType) {
        alert('يجب اختيار نوع الشركة أولاً');
        return;
    }

    const originalButton = this;
    const originalContent = this.innerHTML;

    originalButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري التوليد...';
    originalButton.disabled = true;

    fetch('/projects/generate-code', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            company_type: companyType
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const codeInput = document.getElementById('code');
            codeInput.value = data.code;
        } else {
            alert('خطأ: ' + (data.message || 'حدث خطأ في توليد الكود'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('حدث خطأ في الاتصال بالخادم');
    })
    .finally(() => {
        originalButton.innerHTML = originalContent;
        originalButton.disabled = false;
    });
});
</script>
<script>
    function switchSection(showPackage) {
        const packageSection = document.getElementById('package-section');
        const servicesSection = document.getElementById('services-section');

        if (showPackage) {
            packageSection.style.display = 'block';
            servicesSection.style.display = 'none';
        } else {
            packageSection.style.display = 'none';
            servicesSection.style.display = 'block';
        }
        updateTotalPoints();
    }

    document.getElementById('select_package').addEventListener('change', function() {
        if (this.checked) {
            switchSection(true);
        }
    });

    document.getElementById('select_services').addEventListener('change', function() {
        if (this.checked) {
            switchSection(false);
        }
    });

    document.querySelectorAll('.create-service-checkbox').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            const serviceCard = this.closest('.create-service-card');
            const statusContainer = serviceCard.querySelector('.create-service-status');

            if (this.checked) {
                serviceCard.classList.add('selected');
                statusContainer.classList.add('show');
            } else {
                serviceCard.classList.remove('selected');
                statusContainer.classList.remove('show');
            }
            updateTotalPoints();
        });
    });

    function updateTotalPoints() {
        let totalPoints = 0;

        if (document.getElementById('select_package').checked) {
            // حساب النقاط من خدمات الباقة المحددة + الخدمات الإضافية المحددة
            document.querySelectorAll('#package-services-list .create-service-checkbox:checked, #additional-services-list .create-service-checkbox:checked').forEach(function(checkbox) {
                const pointsText = checkbox.closest('.create-service-card').querySelector('.create-service-points').textContent.trim();
                const points = parseInt(pointsText);
                if (!isNaN(points)) {
                    totalPoints += points;
                }
            });
        } else {
            // حساب النقاط من الخدمات المختارة يدوياً
            document.querySelectorAll('#services-section .create-service-checkbox:checked').forEach(function(checkbox) {
                const pointsText = checkbox.closest('.create-service-card').querySelector('.create-service-points').textContent.trim();
                const points = parseInt(pointsText);
                if (!isNaN(points)) {
                    totalPoints += points;
                }
            });
        }

        const pointsElement = document.getElementById('total-points');
        pointsElement.textContent = totalPoints;
    }

    // تحديث النقاط عند تغيير الباقة
    document.getElementById('package_id').addEventListener('change', function() {
        handlePackageChange();
        updateTotalPoints();
    });

    // معالجة تغيير الباقة - عرض خدماتها
    function handlePackageChange() {
        const packageSelect = document.getElementById('package_id');
        const packageServicesSection = document.getElementById('package-services-section');
        const packageServicesList = document.getElementById('package-services-list');
        const additionalServicesList = document.getElementById('additional-services-list');

        if (packageSelect.value) {
            // الحصول على خدمات الباقة المختارة
            const selectedOption = packageSelect.options[packageSelect.selectedIndex];
            const packageServices = JSON.parse(selectedOption.getAttribute('data-services') || '[]');

            // مسح القوائم السابقة
            packageServicesList.innerHTML = '';
            additionalServicesList.innerHTML = '';

            // عرض خدمات الباقة (محددة بعلامة ✓)
            packageServices.forEach(serviceId => {
                const service = allServices.find(s => s.id == serviceId);
                if (service) {
                    const serviceCard = createServiceCard(service, true, true); // true للتحديد، true للباقة
                    packageServicesList.appendChild(serviceCard);
                }
            });

            // عرض الخدمات الإضافية (غير محددة، خارج الباقة)
            allServices.forEach(service => {
                // إذا لم تكن الخدمة ضمن خدمات الباقة
                if (!packageServices.includes(service.id)) {
                    const serviceCard = createServiceCard(service, false, false); // false للتحديد، false للباقة
                    additionalServicesList.appendChild(serviceCard);
                }
            });

            packageServicesSection.style.display = 'block';
        } else {
            packageServicesSection.style.display = 'none';
            packageServicesList.innerHTML = '';
            additionalServicesList.innerHTML = '';
        }
    }

    // إنشاء بطاقة خدمة ديناميكياً
    function createServiceCard(service, isChecked = false, isFromPackage = false) {
        const card = document.createElement('div');
        card.className = 'create-service-card' + (isChecked ? ' selected' : '');

        const badgeHtml = isFromPackage ? '<span class="badge bg-primary me-2" style="font-size: 0.7rem;"><i class="fas fa-box-open me-1"></i>من الباقة</span>' : '';

        card.innerHTML = `
            <div class="create-service-content">
                <div class="create-service-header">
                    <input class="create-service-checkbox" type="checkbox" value="${service.id}"
                           id="service_${service.id}" name="selected_services[]" ${isChecked ? 'checked' : ''}>
                    <div>
                        ${badgeHtml}
                        <h6 class="create-service-title">${service.name}</h6>
                        <p class="create-service-description">${service.description || ''}</p>
                        <span class="create-service-points">
                            <i class="fas fa-star me-1"></i>
                            ${service.points} نقطة
                        </span>
                    </div>
                </div>
            </div>
        `;

        // إضافة event listener للـ checkbox
        const checkbox = card.querySelector('.create-service-checkbox');
        checkbox.addEventListener('change', function() {
            const serviceCard = this.closest('.create-service-card');

            if (this.checked) {
                serviceCard.classList.add('selected');
            } else {
                serviceCard.classList.remove('selected');
            }
            updateTotalPoints();
        });

        return card;
    }

    updateTotalPoints();
    console.log('🎉 مرحباً بك في صفحة إنشاء مشروع جديد! ابدأ رحلتك نحو النجاح 🚀');
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const preparationCheckbox = document.getElementById('preparation_enabled');
        const preparationFields = document.getElementById('preparation-fields');
        const preparationStartDate = document.getElementById('preparation_start_date');

        if (preparationCheckbox && preparationFields) {
            if (preparationCheckbox.checked) {
                preparationFields.style.display = 'block';
            }

            preparationCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    preparationFields.style.display = 'block';

                    if (!preparationStartDate.value) {
                        const now = new Date();
                        const year = now.getFullYear();
                        const month = String(now.getMonth() + 1).padStart(2, '0');
                        const day = String(now.getDate()).padStart(2, '0');
                        const hours = String(now.getHours()).padStart(2, '0');
                        const minutes = String(now.getMinutes()).padStart(2, '0');

                        preparationStartDate.value = `${year}-${month}-${day}T${hours}:${minutes}`;
                    }
                } else {
                    preparationFields.style.display = 'none';
                    document.getElementById('preparation_start_date').value = '';
                    document.getElementById('preparation_days').value = '';
                }
            });
        }
    });
</script>

@endpush
@endsection
