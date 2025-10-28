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
                    <i class="fas fa-edit me-2"></i>
                    تعديل الحقل
                </h2>
                <p class="mb-0" style="opacity: 0.9;">
                    <i class="fas fa-layer-group me-1"></i>
                    خدمة: {{ $field->service->name }}
                </p>
            </div>
            <a href="{{ route('service-data.manage', $field->service_id) }}" class="btn btn-light btn-action">
                <i class="fas fa-arrow-right me-2"></i> إلغاء والعودة
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card-modern">
                <div class="card-body p-4">
                    <form action="{{ route('service-data.update-field', $field->id) }}" method="POST" id="fieldForm">
                        @csrf
                        @method('PUT')

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="field_name" class="form-label">اسم الحقل (بالإنجليزية) <span class="text-danger">*</span></label>
                                <input type="text"
                                       class="form-control @error('field_name') is-invalid @enderror"
                                       id="field_name"
                                       name="field_name"
                                       value="{{ old('field_name', $field->field_name) }}"
                                       placeholder="مثال: contract_received"
                                       required>
                                <div class="form-text">سيُستخدم في قاعدة البيانات (حروف وأرقام و _ فقط)</div>
                                @error('field_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="field_label" class="form-label">التسمية (بالعربية) <span class="text-danger">*</span></label>
                                <input type="text"
                                       class="form-control @error('field_label') is-invalid @enderror"
                                       id="field_label"
                                       name="field_label"
                                       value="{{ old('field_label', $field->field_label) }}"
                                       placeholder="مثال: تم استلام العقد"
                                       required>
                                <div class="form-text">سيظهر للمستخدمين</div>
                                @error('field_label')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="field_type" class="form-label">نوع الحقل <span class="text-danger">*</span></label>
                            <select class="form-select @error('field_type') is-invalid @enderror"
                                    id="field_type"
                                    name="field_type"
                                    required>
                                <option value="">-- اختر نوع الحقل --</option>
                                <option value="boolean" {{ old('field_type', $field->field_type) === 'boolean' ? 'selected' : '' }}>
                                    نعم/لا (Boolean)
                                </option>
                                <option value="date" {{ old('field_type', $field->field_type) === 'date' ? 'selected' : '' }}>
                                    تاريخ (Date)
                                </option>
                                <option value="dropdown" {{ old('field_type', $field->field_type) === 'dropdown' ? 'selected' : '' }}>
                                    قائمة منسدلة (Dropdown)
                                </option>
                                <option value="text" {{ old('field_type', $field->field_type) === 'text' ? 'selected' : '' }}>
                                    نص (Text)
                                </option>
                            </select>
                            @error('field_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- حقل الخيارات -->
                        <div class="mb-3" id="optionsContainer" @if($field->field_type !== 'dropdown') style="display: none;" @endif>
                            <label class="form-label">خيارات القائمة المنسدلة <span class="text-danger">*</span></label>
                            <div id="optionsList">
                                @if($field->field_type === 'dropdown' && $field->field_options)
                                    @foreach($field->field_options as $index => $option)
                                        <div class="input-group mb-2">
                                            <input type="text"
                                                   class="form-control"
                                                   name="field_options[]"
                                                   value="{{ $option }}"
                                                   placeholder="الخيار {{ $index + 1 }}">
                                            <button type="button" class="btn btn-danger" onclick="removeOption(this)" {{ $index === 0 ? 'disabled' : '' }}>
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="input-group mb-2">
                                        <input type="text"
                                               class="form-control"
                                               name="field_options[]"
                                               placeholder="الخيار 1">
                                        <button type="button" class="btn btn-danger" onclick="removeOption(this)" disabled>
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                @endif
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
                                      placeholder="وصف إضافي للحقل">{{ old('description', $field->description) }}</textarea>
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
                                       {{ old('is_required', $field->is_required) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_required">
                                    حقل إلزامي
                                </label>
                                <div class="form-text">سيُطلب من المستخدم ملء هذا الحقل عند إضافة المشروع</div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('service-data.manage', $field->service_id) }}" class="btn btn-secondary btn-action">
                                <i class="fas fa-times me-2"></i>
                                إلغاء
                            </a>
                            <button type="submit" class="btn btn-primary btn-action">
                                <i class="fas fa-save me-2"></i> حفظ التغييرات
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
                    <!-- سيتم ملؤها ديناميكياً -->
                </div>
            </div>

            <div class="card-modern">
                <div class="card-body p-3" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                    <h6 class="mb-0 text-white">
                        <i class="fas fa-info-circle me-2"></i>
                        معلومات الحقل
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3 p-3" style="background: #f8f9fa; border-radius: 8px;">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-calendar-plus text-primary me-2"></i>
                            <strong>تاريخ الإنشاء</strong>
                        </div>
                        <span class="text-muted">{{ $field->created_at->format('Y-m-d H:i') }}</span>
                    </div>
                    <div class="mb-3 p-3" style="background: #f8f9fa; border-radius: 8px;">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-sync-alt text-info me-2"></i>
                            <strong>آخر تحديث</strong>
                        </div>
                        <span class="text-muted">{{ $field->updated_at->format('Y-m-d H:i') }}</span>
                    </div>
                    <div class="mb-0 p-3" style="background: #f8f9fa; border-radius: 8px;">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-toggle-on text-success me-2"></i>
                            <strong>الحالة</strong>
                        </div>
                        @if($field->is_active)
                            <span class="badge badge-modern bg-success">
                                <i class="fas fa-check-circle"></i>
                                نشط
                            </span>
                        @else
                            <span class="badge badge-modern bg-secondary">
                                <i class="fas fa-times-circle"></i>
                                غير نشط
                            </span>
                        @endif
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
            // حذف جميع الخيارات عند تغيير النوع من dropdown لنوع آخر
            const optionsList = document.getElementById('optionsList');
            const currentOptions = optionsList.querySelectorAll('.input-group');
            currentOptions.forEach(opt => opt.remove());
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

    // عرض المعاينة عند تحميل الصفحة
    updatePreview();
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

