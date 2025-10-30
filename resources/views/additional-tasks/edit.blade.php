@extends('layouts.app')

@section('title', 'تعديل مهمة إضافية')

@push('styles')
<link href="{{ asset('css/additional-tasks.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="additional-tasks-container">
    <div style="width: 100%; padding: 0 2rem;">
        <!-- Page Header -->
        <div class="page-header-tasks">
            <h1>✏️ تعديل مهمة: {{ $additionalTask->title }}</h1>
            <p>تحديث بيانات المهمة الإضافية • معرف المهمة: #{{ $additionalTask->id }}</p>
        </div>

        <!-- Back Button -->
        <div class="mb-4">
            <a href="{{ route('additional-tasks.show', $additionalTask) }}" class="btn-custom btn-gradient-light">
                <i class="fas fa-arrow-right"></i>
                رجوع للتفاصيل
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

                    <!-- Task Status Warning -->
                    @if($additionalTask->status !== 'active')
                        <div class="alert-modern alert-warning mb-4">
                            <i class="fas fa-exclamation-triangle fa-lg"></i>
                            <div>
                                <strong>تنبيه:</strong> هذه المهمة حالياً
                                @if($additionalTask->status === 'expired')
                                    <strong>منتهية</strong>
                                @elseif($additionalTask->status === 'cancelled')
                                    <strong>ملغية</strong>
                                @endif
                                - التعديلات قد لا تؤثر على المستخدمين
                            </div>
                        </div>
                    @endif

                    <!-- Participants Warning -->
                    @if($additionalTask->taskUsers()->count() > 0)
                        <div class="alert-modern alert-info mb-4">
                            <i class="fas fa-users fa-lg"></i>
                            <div>
                                <strong>تنبيه:</strong> هناك {{ $additionalTask->taskUsers()->count() }} مستخدم مُخصص لهذه المهمة بالفعل.
                                بعض التعديلات قد تؤثر على المشاركين الحاليين.
                            </div>
                        </div>
                    @endif

                    <form action="{{ route('additional-tasks.update', $additionalTask) }}" method="POST">
                        @csrf
                        @method('PUT')

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
                                               value="{{ old('title', $additionalTask->title) }}"
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
                                                  placeholder="وصف تفصيلي للمهمة وما المطلوب من المستخدم">{{ old('description', $additionalTask->description) }}</textarea>
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
                                               value="{{ old('points', $additionalTask->points) }}"
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

                                <!-- الحالة -->
                                <div>
                                    <div class="form-group">
                                        <label for="is_active" class="form-label-modern">
                                            <i class="fas fa-toggle-on"></i>
                                            حالة المهمة <span style="color: #dc3545;">*</span>
                                        </label>
                                        <select id="is_active" name="is_active" required class="form-control-modern form-select @error('is_active') is-invalid @enderror">
                                            <option value="1" {{ old('is_active', $additionalTask->is_active) ? 'selected' : '' }}>✅ نشط</option>
                                            <option value="0" {{ !old('is_active', $additionalTask->is_active) ? 'selected' : '' }}>❌ غير نشط</option>
                                        </select>
                                        @error('is_active')
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
                                            <option value="all" {{ old('target_type', $additionalTask->target_type) === 'all' ? 'selected' : '' }}>جميع الموظفين</option>
                                            <option value="department" {{ old('target_type', $additionalTask->target_type) === 'department' ? 'selected' : '' }}>قسم محدد</option>
                                        </select>
                                        @error('target_type')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- القسم المحدد -->
                                <div id="department_field" style="display: {{ old('target_type', $additionalTask->target_type) === 'department' ? 'block' : 'none' }};">
                                    <div class="form-group">
                                        <label for="target_department" class="form-label-modern">
                                            <i class="fas fa-sitemap"></i>
                                            اسم القسم <span style="color: #dc3545;">*</span>
                                        </label>
                                        <select id="target_department" name="target_department" class="form-control-modern form-select @error('target_department') is-invalid @enderror">
                                            <option value="">اختر القسم</option>
                                            @foreach($departments as $department)
                                                <option value="{{ $department }}" {{ old('target_department', $additionalTask->target_department) === $department ? 'selected' : '' }}>
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
                                    <i class="fas fa-cogs"></i>
                                </div>
                                <h3 class="form-section-title">نوعية المهمة</h3>
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
                                               value="{{ old('max_participants', $additionalTask->max_participants ?? 10) }}"
                                               class="form-control-modern @error('max_participants') is-invalid @enderror"
                                               required>
                                        <small style="display: block; margin-top: 0.5rem; color: #6b7280;">
                                            <i class="fas fa-info-circle"></i>
                                            حدد عدد المستخدمين الذين يمكنهم المشاركة في هذه المهمة
                                        </small>

                                        @if($additionalTask->getApprovedParticipantsCount() > 0)
                                            <small style="display: block; margin-top: 0.5rem; color: #667eea;">
                                                <i class="fas fa-check-circle"></i>
                                                هناك حالياً {{ $additionalTask->getApprovedParticipantsCount() }} مشارك مُوافق عليهم
                                            </small>
                                        @endif
                                        @error('max_participants')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- إعدادات التوقيت -->
                        <div class="form-section">
                            <div class="form-section-header">
                                <div class="form-section-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <h3 class="form-section-title">إعدادات التوقيت</h3>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- المدة الحالية -->
                                <div>
                                    <div class="form-group">
                                        <label class="form-label-modern">
                                            <i class="fas fa-info-circle"></i>
                                            الوضع الحالي
                                        </label>
                                        <div style="background: #f9fafb; padding: 1.5rem; border-radius: 10px; border: 2px solid #e5e7eb;">
                                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                                <span style="color: #6b7280;">المدة الأصلية:</span>
                                                <span style="font-weight: 600;">{{ $additionalTask->duration_hours }} ساعة</span>
                                            </div>
                                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                                <span style="color: #6b7280;">تنتهي في:</span>
                                                <span style="font-weight: 600;">{{ $additionalTask->current_end_time->format('Y-m-d H:i') }}</span>
                                            </div>
                                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                                <span style="color: #6b7280;">مرات التمديد:</span>
                                                <span style="font-weight: 600;">{{ $additionalTask->extensions_count }}</span>
                                            </div>
                                            <div style="display: flex; justify-content: space-between;">
                                                <span style="color: #6b7280;">الوقت المتبقي:</span>
                                                <span style="font-weight: 600; color: {{ $additionalTask->isExpired() ? '#dc3545' : '#28a745' }};">
                                                    @if($additionalTask->isExpired())
                                                        انتهت
                                                    @else
                                                        @php $hoursRemaining = $additionalTask->timeRemainingInHours(); @endphp
                                                        @if($hoursRemaining > 24)
                                                            {{ round($hoursRemaining / 24, 1) }} يوم
                                                        @else
                                                            {{ $hoursRemaining }} ساعة
                                                        @endif
                                                    @endif
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- تمديد الوقت -->
                                @if($additionalTask->canBeExtended())
                                    <div>
                                        <div class="form-group">
                                            <label for="extend_hours" class="form-label-modern">
                                                <i class="fas fa-clock"></i>
                                                تمديد إضافي (بالساعات)
                                            </label>
                                            <input type="number" id="extend_hours" name="extend_hours" min="0" max="168"
                                                   value="{{ old('extend_hours', 0) }}"
                                                   class="form-control-modern @error('extend_hours') is-invalid @enderror">
                                            <small style="display: block; margin-top: 0.5rem; color: #6b7280;">
                                                <i class="fas fa-info-circle"></i>
                                                اتركه 0 لعدم التمديد
                                            </small>
                                            @error('extend_hours')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                @else
                                    <div>
                                        <div class="form-group">
                                            <label class="form-label-modern">
                                                <i class="fas fa-clock"></i>
                                                تمديد الوقت
                                            </label>
                                            <div class="alert-modern alert-info">
                                                @if($additionalTask->isExpired())
                                                    <i class="fas fa-clock fa-lg"></i>
                                                    <span>المهمة منتهية - لا يمكن التمديد</span>
                                                @else
                                                    <i class="fas fa-info-circle fa-lg"></i>
                                                    <span>التمديد متاح فقط للمهام النشطة</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endif
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
                                                <option value="{{ $season->id }}" {{ old('season_id', $additionalTask->season_id) == $season->id ? 'selected' : '' }}>
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
                                            <option value="fas fa-tasks" {{ old('icon', $additionalTask->icon) === 'fas fa-tasks' ? 'selected' : '' }}>📋 مهام</option>
                                            <option value="fas fa-file-alt" {{ old('icon', $additionalTask->icon) === 'fas fa-file-alt' ? 'selected' : '' }}>📄 تقرير</option>
                                            <option value="fas fa-chart-bar" {{ old('icon', $additionalTask->icon) === 'fas fa-chart-bar' ? 'selected' : '' }}>📊 تحليل</option>
                                            <option value="fas fa-users" {{ old('icon', $additionalTask->icon) === 'fas fa-users' ? 'selected' : '' }}>👥 فريق</option>
                                            <option value="fas fa-cog" {{ old('icon', $additionalTask->icon) === 'fas fa-cog' ? 'selected' : '' }}>⚙️ إعدادات</option>
                                            <option value="fas fa-star" {{ old('icon', $additionalTask->icon) === 'fas fa-star' ? 'selected' : '' }}>⭐ مميز</option>
                                            <option value="fas fa-lightbulb" {{ old('icon', $additionalTask->icon) === 'fas fa-lightbulb' ? 'selected' : '' }}>💡 فكرة</option>
                                            <option value="fas fa-trophy" {{ old('icon', $additionalTask->icon) === 'fas fa-trophy' ? 'selected' : '' }}>🏆 إنجاز</option>
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
                                                   value="{{ old('color_code', $additionalTask->color_code) }}"
                                                   class="color-picker">
                                            <input type="text"
                                                   value="{{ old('color_code', $additionalTask->color_code) }}"
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
                            <a href="{{ route('additional-tasks.show', $additionalTask) }}" class="btn-custom" style="background: #6c757d; color: white; padding: 12px 28px;">
                                <i class="fas fa-times"></i>
                                إلغاء
                            </a>
                            <button type="submit" class="btn-custom btn-gradient-primary">
                                <i class="fas fa-save"></i>
                                حفظ التعديلات
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

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleDepartmentField();
    updateIconPreview();
});
</script>
@endpush
@endsection
