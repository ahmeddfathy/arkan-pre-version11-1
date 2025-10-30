@extends('layouts.app')

@section('title', 'إضافة مهمة إضافية جديدة')

@push('styles')
<link href="{{ asset('css/additional-tasks.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="additional-tasks-container">
    <div style="width: 100%; padding: 0 2rem;">
        <!-- Page Header -->
        <div class="page-header-tasks">
            <h1>➕ إضافة مهمة إضافية جديدة</h1>
            <p>أضف مهمة جديدة لتحفيز الموظفين وكسب النقاط</p>
        </div>

        <!-- Back Button -->
        <div class="mb-4">
            <a href="{{ route('additional-tasks.index') }}" class="btn-custom btn-gradient-light">
                <i class="fas fa-arrow-right"></i>
                رجوع للقائمة
            </a>
        </div>

        <!-- Form -->
        <div class="row">
            <div class="col-lg-9 mx-auto">
                <div class="modern-form-card">
                    @if($errors->any())
                        <div class="alert-modern alert-error mb-4">
                            <i class="fas fa-exclamation-circle fa-lg"></i>
                            <div>
                                <ul style="list-style: disc; padding-right: 1.5rem; margin: 0;">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    <form action="{{ route('additional-tasks.store') }}" method="POST">
                        @csrf

                        <!-- معلومات المهمة الأساسية -->
                        <div class="form-section">
                            <div class="form-section-header">
                                <div class="form-section-icon">
                                    <i class="fas fa-info-circle"></i>
                                </div>
                                <h3 class="form-section-title">معلومات المهمة الأساسية</h3>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- عنوان المهمة -->
                                <div class="md:col-span-2">
                                    <div class="form-group">
                                        <label for="title" class="form-label-modern">
                                            <i class="fas fa-heading"></i>
                                            عنوان المهمة <span style="color: #dc3545;">*</span>
                                        </label>
                                        <input type="text" id="title" name="title" required
                                               value="{{ old('title') }}"
                                               class="form-control-modern @error('title') is-invalid @enderror"
                                               placeholder="مثال: مراجعة التقارير اليومية">
                                        @error('title')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- وصف المهمة -->
                                <div class="md:col-span-2">
                                    <div class="form-group">
                                        <label for="description" class="form-label-modern">
                                            <i class="fas fa-align-left"></i>
                                            وصف المهمة
                                        </label>
                                        <textarea id="description" name="description" rows="3"
                                                  class="form-control-modern @error('description') is-invalid @enderror"
                                                  placeholder="وصف تفصيلي للمهمة وما المطلوب من المستخدم">{{ old('description') }}</textarea>
                                        @error('description')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- النقاط -->
                                <div>
                                    <div class="form-group">
                                        <label for="points" class="form-label-modern">
                                            <i class="fas fa-star"></i>
                                            النقاط المكتسبة <span style="color: #dc3545;">*</span>
                                        </label>
                                        <input type="number" id="points" name="points" min="1" max="1000" required
                                               value="{{ old('points', 10) }}"
                                               class="form-control-modern @error('points') is-invalid @enderror"
                                               style="font-size: 1.2rem; font-weight: 600;">
                                        <small style="display: block; margin-top: 0.5rem; color: #6b7280;">
                                            <i class="fas fa-info-circle"></i>
                                            عدد النقاط التي سيحصل عليها المستخدم عند إكمال المهمة
                                        </small>
                                        @error('points')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- خيار المهمة المرنة -->
                                <div class="md:col-span-2">
                                    <div class="form-group">
                                        <div style="background: #f9fafb; padding: 1rem; border-radius: 10px; border: 2px solid #e5e7eb;">
                                            <div class="form-check form-switch">
                                                <input type="hidden" name="is_flexible_time" value="0">
                                                <input class="form-check-input"
                                                       type="checkbox"
                                                       id="is_flexible_time"
                                                       name="is_flexible_time"
                                                       value="1"
                                                       style="width: 3rem; height: 1.5rem; cursor: pointer;"
                                                       {{ old('is_flexible_time') ? 'checked' : '' }}>
                                                <label class="form-check-label" for="is_flexible_time" style="margin-right: 1rem; cursor: pointer;">
                                                    <strong style="font-size: 1.1rem;">🕐 مهمة مرنة (بدون مدة محددة)</strong>
                                                    <small class="d-block" style="color: #6b7280; margin-top: 0.5rem;">
                                                        <i class="fas fa-info-circle"></i>
                                                        عند تفعيل هذا الخيار، ستصبح المهمة مرنة ولن تحتاج لتحديد مدة زمنية محددة
                                                    </small>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- المدة بالساعات -->
                                <div id="duration_section">
                                    <div class="form-group">
                                        <label for="duration_hours" class="form-label-modern">
                                            <i class="fas fa-clock"></i>
                                            مدة المهمة (بالساعات) <span style="color: #dc3545;">*</span>
                                        </label>
                                        <input type="number" id="duration_hours" name="duration_hours" min="1" max="8760" required
                                               value="{{ old('duration_hours', 24) }}"
                                               class="form-control-modern @error('duration_hours') is-invalid @enderror">
                                        <small style="display: block; margin-top: 0.5rem; color: #6b7280;">
                                            <i class="fas fa-info-circle"></i>
                                            كم ساعة متاحة لإكمال المهمة (1 ساعة - سنة كاملة)
                                        </small>
                                        @error('duration_hours')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- إعدادات الاستهداف -->
                        <div class="form-section">
                            <div class="form-section-header">
                                <div class="form-section-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <h3 class="form-section-title">إعدادات الاستهداف</h3>
                            </div>

                            @if(isset($createCheck))
                                <!-- معلومات عن الصلاحيات -->
                                <div class="alert-modern alert-info mb-4">
                                    <i class="fas fa-info-circle fa-lg"></i>
                                    <div>
                                        <h4 style="font-weight: 600; margin-bottom: 0.5rem;">من سيرى هذه المهمة؟</h4>
                                        <div style="font-size: 0.875rem;">
                                            @if($createCheck['can_target_all'])
                                                @if(isset($createCheck['team_level']))
                                                    <p style="margin: 0;"><i class="fas fa-check" style="color: #28a745;"></i> <strong>مستواك (2):</strong> أعضاء فريقك فقط يمكنهم رؤية والتقديم على مهامك</p>
                                                @else
                                                    <p style="margin: 0;"><i class="fas fa-check" style="color: #28a745;"></i> <strong>مستواك العالي:</strong> يمكن لجميع الموظفين رؤية والتقديم على مهامك</p>
                                                @endif
                                            @else
                                                <p style="margin: 0;"><i class="fas fa-info" style="color: #ffc107;"></i> <strong>مستواك:</strong> يمكن فقط لأعضاء قسمك رؤية والتقديم على مهامك</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- نوع الاستهداف -->
                                <div>
                                    <div class="form-group">
                                        <label for="target_type" class="form-label-modern">
                                            <i class="fas fa-bullseye"></i>
                                            الاستهداف <span style="color: #dc3545;">*</span>
                                        </label>
                                        <select id="target_type" name="target_type" required
                                                class="form-control-modern form-select @error('target_type') is-invalid @enderror"
                                                onchange="toggleDepartmentField()">
                                            <option value="all" {{ old('target_type') === 'all' ? 'selected' : '' }}>جميع الموظفين</option>
                                            <option value="department" {{ old('target_type') === 'department' ? 'selected' : '' }}>قسم محدد</option>
                                        </select>
                                        @error('target_type')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- القسم المحدد -->
                                <div id="department_field" style="display: none;">
                                    <div class="form-group">
                                        <label for="target_department" class="form-label-modern">
                                            <i class="fas fa-sitemap"></i>
                                            اسم القسم <span style="color: #dc3545;">*</span>
                                        </label>
                                        <select id="target_department" name="target_department"
                                                class="form-control-modern form-select @error('target_department') is-invalid @enderror">
                                            <option value="">اختر القسم</option>
                                            @foreach($departments as $department)
                                                <option value="{{ $department }}" {{ old('target_department') === $department ? 'selected' : '' }}>
                                                    {{ $department }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('target_department')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- نوعية المهمة -->
                        <div class="form-section">
                            <div class="form-section-header">
                                <div class="form-section-icon">
                                    <i class="fas fa-hand-paper"></i>
                                </div>
                                <h3 class="form-section-title">إعدادات التقديم</h3>
                            </div>

                            <div class="alert-modern alert-info mb-4">
                                <i class="fas fa-info-circle"></i>
                                <div>
                                    <strong>ملحوظة:</strong> جميع المهام الإضافية تتطلب تقديم من المستخدمين وموافقة من الإدارة
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-6">
                                <!-- الحد الأقصى للمشاركين -->
                                <div>
                                    <div class="form-group">
                                        <label for="max_participants" class="form-label-modern">
                                            <i class="fas fa-users"></i>
                                            الحد الأقصى للمشاركين <span style="color: #dc3545;">*</span>
                                        </label>
                                        <input type="number" id="max_participants" name="max_participants" min="1" max="1000"
                                               value="{{ old('max_participants', 10) }}"
                                               class="form-control-modern @error('max_participants') is-invalid @enderror"
                                               required>
                                        <small style="display: block; margin-top: 0.5rem; color: #6b7280;">
                                            <i class="fas fa-info-circle"></i>
                                            حدد عدد المستخدمين الذين يمكنهم المشاركة في هذه المهمة
                                        </small>
                                        @error('max_participants')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- إعدادات إضافية -->
                        <div class="form-section">
                            <div class="form-section-header">
                                <div class="form-section-icon">
                                    <i class="fas fa-palette"></i>
                                </div>
                                <h3 class="form-section-title">إعدادات إضافية</h3>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- الموسم -->
                                <div>
                                    <div class="form-group">
                                        <label for="season_id" class="form-label-modern">
                                            <i class="fas fa-calendar-alt"></i>
                                            الموسم
                                        </label>
                                        <select id="season_id" name="season_id" class="form-control-modern form-select">
                                            <option value="">الموسم الحالي</option>
                                            @foreach($seasons as $season)
                                                <option value="{{ $season->id }}" {{ old('season_id') == $season->id ? 'selected' : '' }}>
                                                    {{ $season->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <!-- أيقونة المهمة -->
                                <div>
                                    <div class="form-group">
                                        <label for="icon" class="form-label-modern">
                                            <i class="fas fa-icons"></i>
                                            أيقونة المهمة
                                        </label>
                                        <select id="icon" name="icon" class="form-control-modern form-select" onchange="updateIconPreview()">
                                            <option value="">بدون أيقونة</option>
                                            <option value="fas fa-tasks" {{ old('icon') === 'fas fa-tasks' ? 'selected' : '' }}>📋 مهام</option>
                                            <option value="fas fa-file-alt" {{ old('icon') === 'fas fa-file-alt' ? 'selected' : '' }}>📄 تقرير</option>
                                            <option value="fas fa-chart-bar" {{ old('icon') === 'fas fa-chart-bar' ? 'selected' : '' }}>📊 تحليل</option>
                                            <option value="fas fa-users" {{ old('icon') === 'fas fa-users' ? 'selected' : '' }}>👥 فريق</option>
                                            <option value="fas fa-cog" {{ old('icon') === 'fas fa-cog' ? 'selected' : '' }}>⚙️ إعدادات</option>
                                            <option value="fas fa-star" {{ old('icon') === 'fas fa-star' ? 'selected' : '' }}>⭐ مميز</option>
                                            <option value="fas fa-lightbulb" {{ old('icon') === 'fas fa-lightbulb' ? 'selected' : '' }}>💡 فكرة</option>
                                            <option value="fas fa-trophy" {{ old('icon') === 'fas fa-trophy' ? 'selected' : '' }}>🏆 إنجاز</option>
                                        </select>
                                        <div id="icon_preview" style="margin-top: 0.5rem; font-size: 2rem; text-align: center;"></div>
                                    </div>
                                </div>

                                <!-- لون المهمة -->
                                <div>
                                    <div class="form-group">
                                        <label for="color_code" class="form-label-modern">
                                            <i class="fas fa-palette"></i>
                                            لون المهمة
                                        </label>
                                        <div class="color-input-group">
                                            <input type="color" id="color_code" name="color_code"
                                                   value="{{ old('color_code', '#667eea') }}"
                                                   class="color-picker">
                                            <input type="text"
                                                   value="{{ old('color_code', '#667eea') }}"
                                                   class="form-control-modern"
                                                   style="flex: 1;"
                                                   readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- أزرار الإجراءات -->
                        <div class="d-flex gap-3 justify-content-end mt-4 pt-3" style="border-top: 2px solid #e5e7eb;">
                            <a href="{{ route('additional-tasks.index') }}" class="btn-custom" style="background: #6c757d; color: white; padding: 12px 28px;">
                                <i class="fas fa-times"></i>
                                إلغاء
                            </a>
                            <button type="submit" class="btn-custom btn-gradient-primary">
                                <i class="fas fa-save"></i>
                                إنشاء المهمة
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Toggle department field based on target type
function toggleDepartmentField() {
    const targetType = document.getElementById('target_type').value;
    const departmentField = document.getElementById('department_field');
    const departmentSelect = document.getElementById('target_department');

    if (targetType === 'department') {
        departmentField.style.display = 'block';
        departmentSelect.required = true;
    } else {
        departmentField.style.display = 'none';
        departmentSelect.required = false;
        departmentSelect.value = '';
    }
}

// Toggle max participants field based on assignment type
// Update icon preview
function updateIconPreview() {
    const iconSelect = document.getElementById('icon');
    const iconPreview = document.getElementById('icon_preview');

    if (iconSelect.value) {
        iconPreview.innerHTML = `<i class="${iconSelect.value}" style="color: #667eea;"></i>`;
    } else {
        iconPreview.innerHTML = '';
    }
}

// Update color input when color picker changes
document.getElementById('color_code').addEventListener('change', function() {
    const textInput = this.nextElementSibling;
    textInput.value = this.value;
});

// Toggle flexible time duration field
function toggleFlexibleTime() {
    const isFlexible = document.getElementById('is_flexible_time').checked;
    const durationSection = document.getElementById('duration_section');
    const durationInput = document.getElementById('duration_hours');

    if (isFlexible) {
        durationSection.style.display = 'none';
        durationInput.removeAttribute('required');
        durationInput.value = '';
    } else {
        durationSection.style.display = 'block';
        durationInput.setAttribute('required', 'required');
        durationInput.value = 24;
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleDepartmentField();
    updateIconPreview();

    // Add event listener for flexible time toggle
    document.getElementById('is_flexible_time').addEventListener('change', toggleFlexibleTime);
});
</script>
@endpush
@endsection
