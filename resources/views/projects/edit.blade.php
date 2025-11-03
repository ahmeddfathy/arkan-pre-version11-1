@extends('layouts.app')

@section('title', 'تعديل المشروع')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/projects.css') }}">
<link rel="stylesheet" href="{{ asset('css/projects/project-create-modern.css') }}">
@endpush

@section('content')
@php
$createdDate = $project->created_at instanceof \Carbon\Carbon ? $project->created_at : \Carbon\Carbon::parse($project->created_at);
$now = \Carbon\Carbon::now();
$daysAgo = (int)$createdDate->diffInDays($now);
$isOldProject = $daysAgo >= 2;


Log::info("Project Age Debug", [
'created_at' => $createdDate->format('Y-m-d H:i:s'),
'days_ago' => $daysAgo,
'is_old_project' => $isOldProject
]);
@endphp

<div class="create-modern-container">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-11">
            <div class="create-glass-card">
                <div class="create-modern-header">
                    <div class="create-header-icon">
                        <i class="fas fa-edit"></i>
                    </div>
                    <h3 class="create-header-title">
                        تعديل المشروع: {{ $project->name }}
                    </h3>
                    <p class="text-white-50 mb-0 mt-2">حدّث بيانات مشروعك بسهولة واحترافية</p>
                </div>
                <div class="create-form-body">
                    <form action="{{ route('projects.update', $project) }}" method="POST">
                        @csrf
                        @method('PUT')

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
                                    @if($isOldProject)
                                    <input type="hidden" name="name" value="{{ old('name', $project->name) }}">
                                    @endif
                                    <input type="text"
                                        class="create-form-control @error('name') is-invalid @enderror"
                                        id="name"
                                        name="name"
                                        value="{{ old('name', $project->name) }}"
                                        placeholder="اكتب اسم مشروعك هنا..."
                                        required
                                        {{ $isOldProject ? 'disabled' : '' }}>
                                    @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    @if($isOldProject)
                                    <small class="form-text text-muted"><i class="fas fa-lock me-1"></i>مقفول</small>
                                    @endif
                                </div>

                                <div class="create-form-group">
                                    <label for="code" class="create-form-label">
                                        <i class="fas fa-qrcode"></i>
                                        كود المشروع
                                    </label>
                                    <div class="create-input-group" style="position: relative;">
                                        <!-- Hidden input للإرسال مع الفورم -->
                                        <input type="hidden" name="code" value="{{ $project->code }}">
                                        <!-- حقل القراءة فقط للعرض -->
                                        <input type="text"
                                            class="create-form-control"
                                            id="code_display"
                                            value=""
                                            readonly
                                            style="background-color: #f8f9fa !important; color: #212529 !important; cursor: not-allowed; padding-right: 120px; padding-left: 80px; font-weight: 600; border: 1px solid #dee2e6; font-size: 14px;"
                                            title="كود المشروع لا يمكن تعديله"
                                            onfocus="this.blur()"
                                            onkeydown="return false">
                                        <!-- عرض كود المشروع على الشمال -->
                                        <div class="project-code-display" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); background: #28a745; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">
                                            <i class="fas fa-hashtag"></i> {{ $project->code }}
                                        </div>
                                        <!-- غير قابل للتعديل على اليمين -->
                                        <div class="create-readonly-label" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: #6c757d; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem;">
                                            <i class="fas fa-lock"></i> غير قابل للتعديل
                                        </div>
                                        @error('code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        كود المشروع ثابت ولا يمكن تعديله بعد إنشاء المشروع
                                    </small>
                                </div>

                                <div class="create-form-group">
                                    <label for="company_type" class="create-form-label">
                                        <i class="fas fa-building"></i>
                                        نوع الشركة
                                    </label>
                                    @if($isOldProject)
                                    <input type="hidden" name="company_type" value="{{ old('company_type', $project->company_type ?? '') }}">
                                    @endif
                                    <select class="create-form-control @error('company_type') is-invalid @enderror"
                                        id="company_type"
                                        name="company_type"
                                        {{ $isOldProject ? 'disabled' : '' }}>
                                        <option value="">🏢 اختر نوع الشركة</option>
                                        <option value="A" {{ old('company_type', $project->company_type) == 'A' ? 'selected' : '' }}>A - أركان</option>
                                        <option value="K" {{ old('company_type', $project->company_type) == 'K' ? 'selected' : '' }}>K - خبراء</option>
                                    </select>
                                    @error('company_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    @if($isOldProject)
                                    <small class="form-text text-muted"><i class="fas fa-lock me-1"></i>مقفول</small>
                                    @endif
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
                                        placeholder="اكتب وصفاً مفصلاً عن مشروعك وأهدافه...">{{ old('description', $project->description ?? '') }}</textarea>
                                    @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="create-form-group">
                                    <label for="client_id" class="create-form-label">
                                        <i class="fas fa-user-tie"></i>
                                        العميل *
                                    </label>
                                    @if($isOldProject)
                                    <input type="hidden" name="client_id" value="{{ old('client_id', $project->client_id) }}">
                                    @endif
                                    <select class="create-form-control @error('client_id') is-invalid @enderror"
                                        id="client_id"
                                        name="client_id"
                                        required
                                        {{ $isOldProject ? 'disabled' : '' }}>
                                        <option value="">🏢 اختر العميل المناسب</option>
                                        @foreach($clients as $client)
                                        <option value="{{ $client->id }}"
                                            {{ old('client_id', $project->client_id) == $client->id ? 'selected' : '' }}>
                                            {{ $client->name }} - {{ $client->code }}
                                        </option>
                                        @endforeach
                                    </select>
                                    @error('client_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    @if($isOldProject)
                                    <small class="form-text text-muted"><i class="fas fa-lock me-1"></i>مقفول</small>
                                    @endif
                                </div>

                                <div class="create-form-group">
                                    <label for="manager" class="create-form-label">
                                        <i class="fas fa-user-cog"></i>
                                        مدير المشروع
                                    </label>
                                    <input type="hidden" name="manager" value="{{ old('manager', $project->manager) }}">
                                    <input type="text" class="create-form-control @error('manager') is-invalid @enderror"
                                        id="manager" value="{{ old('manager', $project->manager) }}"
                                        placeholder="اسم مدير المشروع..."
                                        disabled
                                        style="background-color: #f8f9fa; cursor: not-allowed;">
                                    @error('manager')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted"><i class="fas fa-lock me-1"></i><strong>هذا الحقل لا يمكن تعديله نهائياً - تم تعيينه عند إنشاء المشروع</strong></small>
                                </div>
                                <div class="create-form-group">
                                    <label for="note" class="create-form-label">
                                        <i class="fas fa-sticky-note"></i>
                                        ملاحظات هامة
                                    </label>
                                    <textarea class="create-form-control @error('note') is-invalid @enderror"
                                        id="note" name="note" rows="3"
                                        placeholder="اكتب أي ملاحظات هامة حول المشروع...">{{ old('note', $project->note ?? '') }}</textarea>
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
                                                <button type="button" class="btn btn-sm btn-outline-info ms-2" onclick="showDateHistory('team_delivery_date')" title="عرض تاريخ التعديلات">
                                                    <i class="fas fa-history"></i>
                                                </button>
                                            </label>
                                            <input type="date" class="create-form-control @error('team_delivery_date') is-invalid @enderror"
                                                id="team_delivery_date" name="team_delivery_date"
                                                value="{{ old('team_delivery_date', $project->team_delivery_date?->format('Y-m-d')) }}">
                                            @error('team_delivery_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
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
                                                value="{{ old('start_date', $project->start_date?->format('Y-m-d')) }}">
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
                                                value="{{ old('client_agreed_delivery_date', $project->client_agreed_delivery_date?->format('Y-m-d')) }}">
                                            @error('client_agreed_delivery_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="create-form-group">
                                    <label for="status" class="create-form-label">
                                        <i class="fas fa-chart-line"></i>
                                        حالة المشروع *
                                    </label>
                                    @if($isOldProject)
                                    <input type="hidden" name="status" value="{{ old('status', $project->status) }}">
                                    @endif
                                    <select class="create-form-control @error('status') is-invalid @enderror"
                                        id="status"
                                        name="status"
                                        required
                                        {{ $isOldProject ? 'disabled' : '' }}>
                                        <option value="جديد" {{ old('status', $project->status) == 'جديد' ? 'selected' : '' }}>🆕 جديد</option>
                                        <option value="جاري التنفيذ" {{ old('status', $project->status) == 'جاري التنفيذ' ? 'selected' : '' }}>⚙️ جاري التنفيذ</option>
                                        <option value="مكتمل" {{ old('status', $project->status) == 'مكتمل' ? 'selected' : '' }}>✅ مكتمل</option>
                                        <option value="ملغي" {{ old('status', $project->status) == 'ملغي' ? 'selected' : '' }}>❌ ملغي</option>
                                    </select>
                                    @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    @if($isOldProject)
                                    <small class="form-text text-muted"><i class="fas fa-lock me-1"></i>مقفول</small>
                                    @endif
                                </div>

                                <div class="create-form-group">
                                    <div class="create-alert urgent-alert">
                                        <div class="urgent-switch-wrapper">
                                            @if($isOldProject)
                                            <input type="hidden" name="is_urgent" value="{{ old('is_urgent', $project->is_urgent) ? '1' : '0' }}">
                                            @endif
                                            <input class="urgent-checkbox @error('is_urgent') is-invalid @enderror"
                                                type="checkbox"
                                                id="is_urgent"
                                                name="is_urgent"
                                                value="1"
                                                {{ old('is_urgent', $project->is_urgent) ? 'checked' : '' }}
                                                {{ $isOldProject ? 'disabled' : '' }}>
                                            <div>
                                                <label class="urgent-label" for="is_urgent">
                                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                                    مشروع مستعجل وعاجل
                                                </label>
                                                <p class="urgent-description">
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
                                        <div class="urgent-switch-wrapper mb-3">
                                            <input class="urgent-checkbox @error('preparation_enabled') is-invalid @enderror"
                                                type="checkbox"
                                                id="preparation_enabled"
                                                name="preparation_enabled"
                                                value="1"
                                                style="accent-color: #667eea;"
                                                {{ old('preparation_enabled', $project->preparation_enabled) ? 'checked' : '' }}>
                                            <div>
                                                <label class="urgent-label" for="preparation_enabled" style="color: #667eea;">
                                                    <i class="fas fa-clock me-2"></i>
                                                    تفعيل فترة التحضير
                                                </label>
                                                <p class="urgent-description">
                                                    تحديد فترة تحضير قبل بدء العمل الفعلي على المشروع (استثناء يوم الجمعة)
                                                </p>
                                            </div>
                                        </div>
                                        @error('preparation_enabled')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror

                                        <div id="preparation-fields" style="display: {{ old('preparation_enabled', $project->preparation_enabled) ? 'block' : 'none' }}; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(103, 126, 234, 0.2);">
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
                                                        value="{{ old('preparation_start_date', $project->preparation_start_date?->format('Y-m-d\TH:i')) }}"
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
                                                        value="{{ old('preparation_days', $project->preparation_days) }}"
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
                                            @if($project->preparation_enabled && $project->preparation_start_date && $project->preparation_days)
                                            <div class="alert alert-info" style="font-size: 0.8rem;">
                                                <i class="fas fa-info-circle me-1"></i>
                                                <strong>تاريخ ووقت النهاية المحسوب:</strong> {{ $project->preparation_end_date?->format('Y-m-d H:i') ?? 'غير محدد' }}
                                                @if($project->isInPreparationPeriod())
                                                <br><span class="badge bg-primary mt-1">المشروع في فترة التحضير حالياً - باقي {{ $project->remaining_preparation_days }} يوم</span>
                                                @elseif($project->hasPreparationPeriodEnded())
                                                <br><span class="badge bg-success mt-1">انتهت فترة التحضير</span>
                                                @else
                                                <br><span class="badge bg-warning mt-1">لم تبدأ فترة التحضير بعد</span>
                                                @endif
                                            </div>
                                            @endif
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
                                            <input type="radio" name="selection_type" id="select_package" value="package" {{ $project->package_id ? 'checked' : '' }}>
                                            <label for="select_package">🎁 اختيار باقة</label>
                                        </div>
                                        <div class="create-radio-option">
                                            <input type="radio" name="selection_type" id="select_services" value="services" {{ $project->package_id ? '' : 'checked' }}>
                                            <label for="select_services">⚙️ اختيار خدمات منفصلة</label>
                                        </div>
                                    </div>
                                </div>
                                <div id="package-section" class="create-package-section {{ $project->package_id ? 'show' : 'hide' }}">
                                    <div class="create-form-group">
                                        <label class="create-form-label">
                                            <i class="fas fa-gift"></i>
                                            اختر الباقة
                                        </label>
                                        <select name="package_id" id="package_id" class="create-form-control" {{ $isOldProject ? 'disabled' : '' }}>
                                            <option value="">🎁 اختر باقة مناسبة</option>
                                            @foreach($packages as $package)
                                            <option value="{{ $package['id'] }}" data-services="{{ json_encode($package['services']) }}" {{ $project->package_id == $package['id'] ? 'selected' : '' }}>{{ $package['name'] }} ({{ $package['total_points'] }} نقطة)</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- عرض خدمات الباقة بعد الاختيار -->
                                    <div id="package-services-section" style="display: {{ $project->package_id ? 'block' : 'none' }};">
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
                                <div id="services-section" class="create-services-section {{ $project->package_id ? 'hide' : 'show' }}">
                                    <div class="create-alert info">
                                        <i class="fas fa-lightbulb"></i>
                                        <span>اختر الخدمات التي يحتاجها العميل وحدد حالة كل خدمة</span>
                                    </div>
                                    <div class="create-services-container">
                                        @foreach($services as $service)
                                        @php
                                        $isSelected = $project->services->contains($service->id);
                                        $serviceStatus = $isSelected ? $project->services->find($service->id)->pivot->service_status : 'لم تبدأ';
                                        @endphp
                                        <div class="create-service-card {{ $isSelected ? 'selected' : '' }}">
                                            <div class="create-service-content">
                                                <div class="create-service-header">
                                                    <input class="create-service-checkbox" type="checkbox" value="{{ $service->id }}" id="service_{{ $service->id }}" name="selected_services[]" {{ $isSelected ? 'checked' : '' }} {{ $isOldProject ? 'disabled' : '' }}>
                                                    <div>
                                                        <h6 class="create-service-title">{{ $service->name }}</h6>
                                                        <p class="create-service-description">{{ $service->description }}</p>
                                                        <span class="create-service-points">
                                                            <i class="fas fa-star me-1"></i>
                                                            {{ $service->points }} نقطة
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="create-service-status {{ $isSelected ? 'show' : '' }}">
                                                    <label class="create-form-label">
                                                        <i class="fas fa-tasks"></i>
                                                        حالة الخدمة:
                                                    </label>
                                                    <select class="create-form-control" name="service_statuses[{{ $service->id }}]">
                                                        <option value="لم تبدأ" {{ $serviceStatus == 'لم تبدأ' ? 'selected' : '' }}>📅 لم تبدأ</option>
                                                        <option value="قيد التنفيذ" {{ $serviceStatus == 'قيد التنفيذ' ? 'selected' : '' }}>⚙️ قيد التنفيذ</option>
                                                        <option value="مكتملة" {{ $serviceStatus == 'مكتملة' ? 'selected' : '' }}>✅ مكتملة</option>
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
                                        <span id="total-points">{{ $project->total_points }}</span> نقطة
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
                                <i class="fas fa-sync-alt"></i>
                                تحديث المشروع
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // تمرير بيانات الخدمات والمشروع لـ JavaScript
    const allServices = @json($services);
    const currentProject = @json($project);
    const isOldProject = @json($isOldProject);

    // كود المشروع غير قابل للتعديل في صفحة التعديل

    function switchSection(showPackage) {
        const packageSection = document.getElementById('package-section');
        const servicesSection = document.getElementById('services-section');

        if (showPackage) {
            packageSection.classList.remove('hide');
            packageSection.classList.add('show');
            servicesSection.classList.remove('show');
            servicesSection.classList.add('hide');
        } else {
            packageSection.classList.remove('show');
            packageSection.classList.add('hide');
            servicesSection.classList.remove('hide');
            servicesSection.classList.add('show');
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
                    // التحقق إذا كانت الخدمة محددة في المشروع الحالي
                    const isChecked = currentProject.services.some(s => s.id == serviceId);
                    const serviceCard = createServiceCard(service, isChecked, true); // true للباقة
                    packageServicesList.appendChild(serviceCard);
                }
            });

            // عرض الخدمات الإضافية (غير محددة، خارج الباقة)
            allServices.forEach(service => {
                // إذا لم تكن الخدمة ضمن خدمات الباقة
                if (!packageServices.includes(service.id)) {
                    // التحقق إذا كانت الخدمة محددة في المشروع الحالي (ربما أضافها المستخدم سابقاً)
                    const isChecked = currentProject.services.some(s => s.id == service.id);
                    const serviceCard = createServiceCard(service, isChecked, false); // false للباقة
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
                       id="service_${service.id}" name="selected_services[]" ${isChecked ? 'checked' : ''}
                       ${isOldProject ? 'disabled' : ''}>
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

        // إضافة event listener للـ checkbox (فقط إذا لم يكن المشروع قديم)
        if (!isOldProject) {
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
        }

        return card;
    }

    // تحميل خدمات الباقة عند تحميل الصفحة إذا كانت باقة محددة
    if (currentProject.package_id) {
        handlePackageChange();
    }

    updateTotalPoints();
    console.log('🚀 مرحباً بك في صفحة تعديل المشروع! حدّث بياناتك بسهولة ✨');

    const preparationCheckbox = document.getElementById('preparation_enabled');
    const preparationFields = document.getElementById('preparation-fields');
    const preparationStartDate = document.getElementById('preparation_start_date');

    if (preparationCheckbox && preparationFields) {
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
</script>
@endpush