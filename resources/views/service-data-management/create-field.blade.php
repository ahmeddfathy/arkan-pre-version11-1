@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/service-data-management.css') }}?v={{ time() }}">
@endpush

@section('content')
<div class="full-width-content">
    <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-1">
                    <i class="fas fa-plus-circle me-2"></i>
                    إضافة حقل جديد
                </h2>
                <p class="mb-0" style="opacity: 0.9;">
                    <i class="fas fa-layer-group me-1"></i>
                    خدمة: {{ $service->name }}
                </p>
            </div>
            <a href="{{ route('service-data.manage', $service->id) }}" class="btn btn-light btn-action">
                <i class="fas fa-arrow-right me-2"></i> إلغاء والعودة
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card-modern">
                <div class="card-body p-4">
                    <form action="{{ route('service-data.store-field', $service->id) }}" method="POST" id="fieldForm">
                        @csrf

                        <div class="mb-3">
                            <label for="field_label" class="form-label">اسم الحقل <span class="text-danger">*</span></label>
                            <input type="text"
                                   class="form-control @error('field_label') is-invalid @enderror"
                                   id="field_label"
                                   name="field_label"
                                   value="{{ old('field_label') }}"
                                   placeholder="مثال: تم استلام العقد"
                                   required>
                            <div class="form-text">سيظهر للمستخدمين وسيتم إنشاء اسم الحقل تلقائياً في قاعدة البيانات</div>
                            @error('field_label')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="field_type" class="form-label">نوع الحقل <span class="text-danger">*</span></label>
                            <select class="form-select @error('field_type') is-invalid @enderror"
                                    id="field_type"
                                    name="field_type"
                                    required>
                                <option value="">-- اختر نوع الحقل --</option>
                                <option value="boolean" {{ old('field_type') === 'boolean' ? 'selected' : '' }}>
                                    نعم/لا (Boolean)
                                </option>
                                <option value="date" {{ old('field_type') === 'date' ? 'selected' : '' }}>
                                    تاريخ (Date)
                                </option>
                                <option value="dropdown" {{ old('field_type') === 'dropdown' ? 'selected' : '' }}>
                                    قائمة منسدلة (Dropdown)
                                </option>
                                <option value="text" {{ old('field_type') === 'text' ? 'selected' : '' }}>
                                    نص (Text)
                                </option>
                            </select>
                            @error('field_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- حقل الخيارات (يظهر فقط عند اختيار dropdown) -->
                        <div class="mb-3" id="optionsContainer" style="display: none;">
                            <label class="form-label">خيارات القائمة المنسدلة <span class="text-danger">*</span></label>
                            <div id="optionsList">
                                <!-- سيتم إضافة الخيارات ديناميكياً -->
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addOption()">
                                <i class="fas fa-plus me-1"></i> إضافة خيار
                            </button>
                            <div class="form-text">أضف الخيارات التي ستظهر في القائمة المنسدلة</div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">الوصف (اختياري)</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="description"
                                      name="description"
                                      rows="3"
                                      placeholder="وصف إضافي للحقل">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input"
                                       type="checkbox"
                                       id="is_required"
                                       name="is_required"
                                       {{ old('is_required') ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_required">
                                    حقل إلزامي
                                </label>
                                <div class="form-text">سيُطلب من المستخدم ملء هذا الحقل عند إضافة المشروع</div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('service-data.manage', $service->id) }}" class="btn btn-secondary btn-action">
                                <i class="fas fa-times me-2"></i>
                                إلغاء
                            </a>
                            <button type="submit" class="btn btn-primary btn-action">
                                <i class="fas fa-save me-2"></i> حفظ الحقل
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- معاينة -->
        <div class="col-lg-4">
            <div class="card-modern mb-3">
                <div class="card-body p-3" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <h6 class="mb-0 text-white">
                        <i class="fas fa-eye me-2"></i>
                        معاينة الحقل
                    </h6>
                </div>
                <div class="card-body" id="previewContainer" style="min-height: 150px;">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        اختر نوع الحقل لعرض المعاينة
                    </div>
                </div>
            </div>

            <div class="card-modern">
                <div class="card-body p-3" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);">
                    <h6 class="mb-0 text-white">
                        <i class="fas fa-question-circle me-2"></i>
                        أنواع الحقول
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3 p-3" style="background: #f8f9fa; border-radius: 8px; border-right: 4px solid #17a2b8;">
                        <div class="d-flex align-items-center mb-2">
                            <div class="field-type-icon field-type-boolean me-2">
                                <i class="fas fa-toggle-on"></i>
                            </div>
                            <strong>نعم/لا (Boolean)</strong>
                        </div>
                        <p class="small text-muted mb-0">حقل اختيار بسيط (Checkbox) - مثل: تم استلام العقد؟</p>
                    </div>
                    <div class="mb-3 p-3" style="background: #f8f9fa; border-radius: 8px; border-right: 4px solid #28a745;">
                        <div class="d-flex align-items-center mb-2">
                            <div class="field-type-icon field-type-date me-2">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <strong>تاريخ (Date)</strong>
                        </div>
                        <p class="small text-muted mb-0">حقل تاريخ - مثل: تاريخ التسليم</p>
                    </div>
                    <div class="mb-3 p-3" style="background: #f8f9fa; border-radius: 8px; border-right: 4px solid #667eea;">
                        <div class="d-flex align-items-center mb-2">
                            <div class="field-type-icon field-type-dropdown me-2">
                                <i class="fas fa-list"></i>
                            </div>
                            <strong>قائمة منسدلة (Dropdown)</strong>
                        </div>
                        <p class="small text-muted mb-0">قائمة خيارات محددة - مثل: تم / لم يتم / غير موجود</p>
                    </div>
                    <div class="mb-0 p-3" style="background: #f8f9fa; border-radius: 8px; border-right: 4px solid #ffc107;">
                        <div class="d-flex align-items-center mb-2">
                            <div class="field-type-icon field-type-text me-2">
                                <i class="fas fa-font"></i>
                            </div>
                            <strong>نص (Text)</strong>
                        </div>
                        <p class="small text-muted mb-0">حقل نصي - مثل: ملاحظات إضافية</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fieldTypeSelect = document.getElementById('field_type');
    const optionsContainer = document.getElementById('optionsContainer');
    const previewContainer = document.getElementById('previewContainer');
    const fieldLabelInput = document.getElementById('field_label');

    // عرض/إخفاء حقل الخيارات
    fieldTypeSelect.addEventListener('change', function() {
        if (this.value === 'dropdown') {
            optionsContainer.style.display = 'block';
            // إضافة خيار واحد افتراضي إذا لم يكن هناك خيارات
            if (document.querySelectorAll('input[name="field_options[]"]').length === 0) {
                addOption();
            }
        } else {
            optionsContainer.style.display = 'none';
            // إزالة جميع الخيارات عند تغيير النوع
            document.getElementById('optionsList').innerHTML = '';
        }
        updatePreview();
    });

    // تحديث المعاينة عند تغيير التسمية
    fieldLabelInput.addEventListener('input', updatePreview);

    function updatePreview() {
        const fieldType = fieldTypeSelect.value;
        const fieldLabel = fieldLabelInput.value || 'مثال على الحقل';

        let previewHTML = '';

        switch(fieldType) {
            case 'boolean':
                previewHTML = `
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="preview" disabled>
                        <label class="form-check-label" for="preview">
                            ${fieldLabel}
                        </label>
                    </div>
                `;
                break;
            case 'date':
                previewHTML = `
                    <label class="form-label">${fieldLabel}</label>
                    <input type="date" class="form-control" disabled>
                `;
                break;
            case 'dropdown':
                const options = Array.from(document.querySelectorAll('input[name="field_options[]"]'))
                    .map(input => input.value)
                    .filter(val => val.trim() !== '');

                let optionsHTML = '<option value="">اختر...</option>';
                options.forEach(opt => {
                    optionsHTML += `<option>${opt}</option>`;
                });

                previewHTML = `
                    <label class="form-label">${fieldLabel}</label>
                    <select class="form-select" disabled>
                        ${optionsHTML}
                    </select>
                `;
                break;
            default:
                previewHTML = `
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        اختر نوع الحقل لعرض المعاينة
                    </div>
                `;
        }

        previewContainer.innerHTML = previewHTML;
    }

    // تحديث المعاينة عند تغيير الخيارات
    document.getElementById('optionsList').addEventListener('input', updatePreview);
});

function addOption() {
    const optionsList = document.getElementById('optionsList');
    const optionsCount = optionsList.children.length;
    const newOption = document.createElement('div');
    newOption.className = 'input-group mb-2';
    newOption.innerHTML = `
        <input type="text" class="form-control" name="field_options[]" placeholder="الخيار ${optionsCount + 1}">
        <button type="button" class="btn btn-danger" onclick="removeOption(this)" ${optionsCount === 0 ? 'disabled' : ''}>
            <i class="fas fa-times"></i>
        </button>
    `;
    optionsList.appendChild(newOption);
}

function removeOption(button) {
    button.closest('.input-group').remove();
    // تحديث المعاينة
    const event = new Event('input', { bubbles: true });
    document.getElementById('optionsList').dispatchEvent(event);
}
</script>
@endsection

