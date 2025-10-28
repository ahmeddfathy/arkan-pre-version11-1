@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/service-data.css') }}">
@endpush

@section('content')
<div class="container-fluid px-4 py-4 service-data-container">
    <div class="d-flex justify-content-between align-items-center mb-4 service-data-header">
        <div>
            <h2 class="mb-1">بيانات خدمات المشروع: {{ $project->name }}</h2>
            <p class="text-muted mb-0">
                <i class="fas fa-user-tie me-1"></i> {{ $project->client->name ?? 'لا يوجد عميل' }}
                @if($project->code)
                    <span class="ms-3"><i class="fas fa-hashtag me-1"></i> {{ $project->code }}</span>
                @endif
            </p>
        </div>
        <a href="{{ route('projects.show', $project->id) }}" class="btn btn-outline-primary">
            <i class="fas fa-arrow-right me-2"></i> العودة للمشروع
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($servicesWithData && count($servicesWithData) > 0)
        <div class="card shadow-sm service-data-card">
            <div class="card-body p-0">
                <!-- Nav Tabs -->
                <ul class="nav nav-tabs px-3 pt-3" id="serviceDataTabs" role="tablist">
                    @foreach($servicesWithData as $index => $serviceData)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $index === 0 ? 'active' : '' }}"
                                    id="service-{{ $serviceData['service']->id }}-tab"
                                    data-bs-toggle="tab"
                                    data-bs-target="#service-{{ $serviceData['service']->id }}"
                                    type="button"
                                    role="tab">
                                <i class="fas fa-concierge-bell me-2"></i>
                                {{ $serviceData['service']->name }}
                                @if(count($serviceData['service_data']) > 0)
                                    <i class="fas fa-check-circle text-success ms-2" title="تم ملء البيانات"></i>
                                @else
                                    <i class="fas fa-exclamation-circle text-warning ms-2" title="لم يتم ملء البيانات"></i>
                                @endif
                            </button>
                        </li>
                    @endforeach
                </ul>

                <!-- Tab Content -->
                <div class="tab-content p-4" id="serviceDataTabsContent">
                    @foreach($servicesWithData as $index => $serviceData)
                        <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}"
                             id="service-{{ $serviceData['service']->id }}"
                             role="tabpanel">

                            <!-- معلومات الخدمة -->
                            <div class="row mb-4 service-info-section">
                                <div class="col-md-8">
                                    <h4 class="mb-3">{{ $serviceData['service']->name }}</h4>
                                    @if($serviceData['service']->description)
                                        <p class="text-muted">{{ $serviceData['service']->description }}</p>
                                    @endif
                                </div>
                                <div class="col-md-4 text-end">
                                    <div class="mb-2 info-stat">
                                        <strong>حالة الخدمة:</strong>
                                        <span class="badge bg-primary">{{ $serviceData['service_status'] }}</span>
                                    </div>
                                    <div class="mb-2 info-stat">
                                        <strong>النقاط:</strong>
                                        <span class="badge bg-info">{{ $serviceData['service']->points }}</span>
                                    </div>
                                    <div class="info-stat">
                                        <strong>عدد الحقول:</strong>
                                        <span class="badge bg-secondary">{{ $serviceData['service']->dataFields->count() }}</span>
                                    </div>
                                </div>
                            </div>

                            @if($serviceData['service']->dataFields->count() > 0)
                                @if(!$canEdit && $canViewAll)
                                    <!-- عرض البيانات فقط للمستخدمين من level 5+ -->
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>ملاحظة:</strong> أنت تشاهد البيانات فقط. لا يمكنك التعديل.
                                    </div>
                                @endif

                                <form id="serviceDataForm-{{ $serviceData['service']->id }}"
                                      class="service-data-form"
                                      data-service-id="{{ $serviceData['service']->id }}">
                                    @csrf

                                    <div class="row">
                                        @foreach($serviceData['service']->dataFields()->active()->ordered()->get() as $field)
                                            <div class="col-md-6 mb-3">
                                                @if($field->field_type === 'boolean')
                                                    <div class="form-check">
                                                        <input class="form-check-input"
                                                               type="checkbox"
                                                               name="service_data[{{ $field->field_name }}]"
                                                               id="field-{{ $field->id }}"
                                                               value="1"
                                                               {{ isset($serviceData['service_data'][$field->field_name]) && $serviceData['service_data'][$field->field_name] ? 'checked' : '' }}
                                                               {{ $field->is_required ? 'required' : '' }}
                                                               {{ !$canEdit ? 'disabled' : '' }}>
                                                        <label class="form-check-label" for="field-{{ $field->id }}">
                                                            {{ $field->field_label }}
                                                            @if($field->is_required && $canEdit)
                                                                <span class="text-danger">*</span>
                                                            @endif
                                                        </label>
                                                        @if($field->description)
                                                            <small class="form-text text-muted d-block">{{ $field->description }}</small>
                                                        @endif
                                                    </div>

                                                @elseif($field->field_type === 'date')
                                                    <label for="field-{{ $field->id }}" class="form-label">
                                                        {{ $field->field_label }}
                                                        @if($field->is_required && $canEdit)
                                                            <span class="text-danger">*</span>
                                                        @endif
                                                    </label>
                                                    <input type="date"
                                                           class="form-control"
                                                           name="service_data[{{ $field->field_name }}]"
                                                           id="field-{{ $field->id }}"
                                                           value="{{ $serviceData['service_data'][$field->field_name] ?? '' }}"
                                                           {{ $field->is_required ? 'required' : '' }}
                                                           {{ !$canEdit ? 'readonly' : '' }}>
                                                    @if($field->description)
                                                        <small class="form-text text-muted">{{ $field->description }}</small>
                                                    @endif

                                                @elseif($field->field_type === 'dropdown')
                                                    <label for="field-{{ $field->id }}" class="form-label">
                                                        {{ $field->field_label }}
                                                        @if($field->is_required && $canEdit)
                                                            <span class="text-danger">*</span>
                                                        @endif
                                                    </label>
                                                    <select class="form-select"
                                                            name="service_data[{{ $field->field_name }}]"
                                                            id="field-{{ $field->id }}"
                                                            {{ $field->is_required ? 'required' : '' }}
                                                            {{ !$canEdit ? 'disabled' : '' }}>
                                                        <option value="">-- اختر --</option>
                                                        @foreach($field->field_options as $option)
                                                            <option value="{{ $option }}"
                                                                    {{ (isset($serviceData['service_data'][$field->field_name]) && $serviceData['service_data'][$field->field_name] === $option) ? 'selected' : '' }}>
                                                                {{ $option }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    @if($field->description)
                                                        <small class="form-text text-muted">{{ $field->description }}</small>
                                                    @endif

                                                @elseif($field->field_type === 'text')
                                                    <label for="field-{{ $field->id }}" class="form-label">
                                                        {{ $field->field_label }}
                                                        @if($field->is_required && $canEdit)
                                                            <span class="text-danger">*</span>
                                                        @endif
                                                    </label>
                                                    <input type="text"
                                                           class="form-control"
                                                           name="service_data[{{ $field->field_name }}]"
                                                           id="field-{{ $field->id }}"
                                                           value="{{ $serviceData['service_data'][$field->field_name] ?? '' }}"
                                                           placeholder="أدخل {{ $field->field_label }}"
                                                           {{ $field->is_required ? 'required' : '' }}
                                                           {{ !$canEdit ? 'readonly' : '' }}>
                                                    @if($field->description)
                                                        <small class="form-text text-muted">{{ $field->description }}</small>
                                                    @endif
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>

                                    <hr class="my-4">

                                    @if($canEdit)
                                        <div class="d-flex justify-content-end gap-2">
                                            <button type="button"
                                                    class="btn btn-secondary reset-form-btn"
                                                    data-service-id="{{ $serviceData['service']->id }}">
                                                <i class="fas fa-undo me-2"></i> إعادة تعيين
                                            </button>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save me-2"></i> حفظ البيانات
                                            </button>
                                        </div>
                                    @else
                                        <div class="alert alert-warning text-center">
                                            <i class="fas fa-lock me-2"></i>
                                            <strong>لا يمكنك تعديل هذه البيانات.</strong>
                                            @if($userHierarchyLevel >= 5)
                                                صلاحيتك تسمح بالعرض فقط.
                                            @else
                                                يجب أن يكون لديك صلاحية مناسبة وأن تكون عضواً في المشروع.
                                            @endif
                                        </div>
                                    @endif
                                </form>
                            @else
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    لا توجد حقول بيانات معرفة لهذه الخدمة.
                                    <a href="{{ route('service-data.manage', $serviceData['service']->id) }}" class="alert-link">
                                        إضافة حقول الآن
                                    </a>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @else
        <div class="card shadow-sm service-data-card">
            <div class="card-body text-center py-5 empty-state">
                <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">لا توجد خدمات في هذا المشروع</h5>
                <p class="text-muted">يرجى إضافة خدمات للمشروع أولاً</p>
                <a href="{{ route('projects.edit', $project->id) }}" class="btn btn-primary mt-3">
                    <i class="fas fa-edit me-2"></i> تعديل المشروع
                </a>
            </div>
        </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // تحديد صلاحية التعديل
    @if($canEdit)
        const canEdit = true;
    @else
        const canEdit = false;
    @endif

    // حفظ بيانات الخدمة
    document.querySelectorAll('.service-data-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            // منع الإرسال إذا لم يكن لديه صلاحية التعديل
            if (!canEdit) {
                showToast('error', 'غير مسموح لك بتعديل البيانات');
                return;
            }

            const serviceId = this.dataset.serviceId;
            const formData = new FormData(this);
            const data = {};

            // تحويل FormData إلى Object
            for (let [key, value] of formData.entries()) {
                if (key.startsWith('service_data[')) {
                    const fieldName = key.match(/service_data\[(.*?)\]/)[1];
                    data[fieldName] = value;
                }
            }

            // إضافة الحقول الـ checkbox غير المحددة وتحويل القيم
            this.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                const fieldName = checkbox.name.match(/service_data\[(.*?)\]/)?.[1];
                if (fieldName && !checkbox.disabled) {
                    data[fieldName] = checkbox.checked;
                }
            });

            // تحويل القيم النصية للـ boolean إذا لزم الأمر
            Object.keys(data).forEach(key => {
                const value = data[key];
                if (value === 'true' || value === '1') {
                    data[key] = true;
                } else if (value === 'false' || value === '0') {
                    data[key] = false;
                }
            });

            // حفظ البيانات
            saveServiceData(serviceId, data, this);
        });
    });

    // إعادة تعيين النموذج
    document.querySelectorAll('.reset-form-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!canEdit) {
                showToast('error', 'غير مسموح لك بإعادة تعيين النموذج');
                return;
            }

            const serviceId = this.dataset.serviceId;
            const form = document.getElementById(`serviceDataForm-${serviceId}`);
            if (form && confirm('هل أنت متأكد من إعادة تعيين النموذج؟')) {
                form.reset();
            }
        });
    });
});

function saveServiceData(serviceId, data, form) {
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;

    // تعطيل الزر وتغيير النص
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> جاري الحفظ...';

    fetch(`/projects/{{ $project->id }}/services/${serviceId}/data`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ service_data: data })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            // عرض رسالة نجاح
            showToast('success', result.message);

            // تحديث أيقونة التبويب
            updateTabIcon(serviceId, Object.keys(data).length > 0);
        } else {
            showToast('error', result.message || 'حدث خطأ أثناء الحفظ');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('error', 'حدث خطأ أثناء الحفظ');
    })
    .finally(() => {
        // إعادة تفعيل الزر
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
    });
}

function updateTabIcon(serviceId, hasData) {
    const tab = document.querySelector(`#service-${serviceId}-tab`);
    const oldIcon = tab.querySelector('.fa-check-circle, .fa-exclamation-circle');

    if (oldIcon) {
        oldIcon.remove();
    }

    const icon = document.createElement('i');
    icon.className = hasData ? 'fas fa-check-circle text-success ms-2' : 'fas fa-exclamation-circle text-warning ms-2';
    icon.title = hasData ? 'تم ملء البيانات' : 'لم يتم ملء البيانات';
    tab.appendChild(icon);
}

function showToast(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';

    const toast = document.createElement('div');
    toast.className = `alert ${alertClass} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
    toast.style.zIndex = '9999';
    toast.innerHTML = `
        <i class="fas ${icon} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    document.body.appendChild(toast);

    // إزالة التنبيه بعد 3 ثواني
    setTimeout(() => {
        toast.remove();
    }, 3000);
}
</script>
@endpush

