@extends('layouts.app')

@section('title', 'إضافة حد شهري جديد')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/project-limits.css') }}">
@endpush

@section('content')
<div class="limits-container">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header-limits">
            <h1>➕ إضافة حد شهري جديد</h1>
            <p>تحديد الحد الأقصى للمشاريع الشهرية</p>
        </div>

        <!-- Back Button -->
        <div class="mb-4">
            <a href="{{ route('project-limits.index') }}" class="btn btn-custom btn-gradient-light">
                <i class="fas fa-arrow-right"></i>
                رجوع للقائمة
            </a>
        </div>

        <!-- Form -->
        <div class="row">
            <div class="col-lg-9 mx-auto">
                <div class="modern-form-card">
                    <form action="{{ route('project-limits.store') }}" method="POST" id="limitForm">
                        @csrf

                        <!-- نوع الحد -->
                        <div class="mb-4">
                            <label class="form-label-modern">
                                <i class="fas fa-tags"></i>
                                نوع الحد <span class="text-danger">*</span>
                            </label>
                            <select name="limit_type" id="limitType" class="form-control-modern form-select @error('limit_type') is-invalid @enderror" required>
                                <option value="">-- اختر نوع الحد --</option>
                                <option value="company" {{ old('limit_type') === 'company' ? 'selected' : '' }}>🏢 الشركة بالكامل</option>
                                <option value="department" {{ old('limit_type') === 'department' ? 'selected' : '' }}>🏛️ قسم</option>
                                <option value="team" {{ old('limit_type') === 'team' ? 'selected' : '' }}>👥 فريق</option>
                                <option value="user" {{ old('limit_type') === 'user' ? 'selected' : '' }}>👤 موظف</option>
                            </select>
                            @error('limit_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- الشهر -->
                        <div class="mb-4">
                            <label class="form-label-modern">
                                <i class="fas fa-calendar"></i>
                                الشهر
                            </label>
                            <select name="month" class="form-control-modern form-select @error('month') is-invalid @enderror">
                                <option value="">📅 جميع الشهور (حد عام)</option>
                                <option value="1" {{ old('month') == 1 ? 'selected' : '' }}>يناير</option>
                                <option value="2" {{ old('month') == 2 ? 'selected' : '' }}>فبراير</option>
                                <option value="3" {{ old('month') == 3 ? 'selected' : '' }}>مارس</option>
                                <option value="4" {{ old('month') == 4 ? 'selected' : '' }}>أبريل</option>
                                <option value="5" {{ old('month') == 5 ? 'selected' : '' }}>مايو</option>
                                <option value="6" {{ old('month') == 6 ? 'selected' : '' }}>يونيو</option>
                                <option value="7" {{ old('month') == 7 ? 'selected' : '' }}>يوليو</option>
                                <option value="8" {{ old('month') == 8 ? 'selected' : '' }}>أغسطس</option>
                                <option value="9" {{ old('month') == 9 ? 'selected' : '' }}>سبتمبر</option>
                                <option value="10" {{ old('month') == 10 ? 'selected' : '' }}>أكتوبر</option>
                                <option value="11" {{ old('month') == 11 ? 'selected' : '' }}>نوفمبر</option>
                                <option value="12" {{ old('month') == 12 ? 'selected' : '' }}>ديسمبر</option>
                            </select>
                            <small class="text-muted" style="display: block; margin-top: 0.5rem;">
                                <i class="fas fa-info-circle"></i>
                                اترك فارغاً لتطبيق الحد على جميع الشهور
                            </small>
                            @error('month')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- اختيار الكيان (حسب النوع) -->
                        <div id="entitySelection" style="display: none;">
                            <!-- للقسم -->
                            <div id="departmentField" class="mb-4" style="display: none;">
                                <label class="form-label-modern">
                                    <i class="fas fa-sitemap"></i>
                                    القسم <span class="text-danger">*</span>
                                </label>
                                <select name="entity_name_dept" id="departmentSelect" class="form-control-modern form-select">
                                    <option value="">-- اختر القسم --</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept }}">{{ $dept }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- للفريق -->
                            <div id="teamField" class="mb-4" style="display: none;">
                                <label class="form-label-modern">
                                    <i class="fas fa-users"></i>
                                    الفريق <span class="text-danger">*</span>
                                </label>
                                <select name="entity_id_team" id="teamSelect" class="form-control-modern form-select">
                                    <option value="">-- اختر الفريق --</option>
                                    @foreach($teams as $team)
                                        <option value="{{ $team->id }}" data-name="{{ $team->name }}">{{ $team->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- للموظف -->
                            <div id="userField" class="mb-4" style="display: none;">
                                <label class="form-label-modern">
                                    <i class="fas fa-user"></i>
                                    الموظف <span class="text-danger">*</span>
                                </label>
                                <select name="entity_id_user" id="userSelect" class="form-control-modern form-select">
                                    <option value="">-- اختر الموظف --</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" data-name="{{ $user->name }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Hidden fields للـ entity_id و entity_name -->
                        <input type="hidden" name="entity_id" id="entityIdHidden">
                        <input type="hidden" name="entity_name" id="entityNameHidden">

                        <!-- الحد الشهري -->
                        <div class="mb-4">
                            <label class="form-label-modern">
                                <i class="fas fa-chart-line"></i>
                                الحد الشهري للمشاريع <span class="text-danger">*</span>
                            </label>
                            <input type="number"
                                   name="monthly_limit"
                                   class="form-control-modern form-control @error('monthly_limit') is-invalid @enderror"
                                   placeholder="مثال: 60"
                                   min="0"
                                   value="{{ old('monthly_limit') }}"
                                   style="font-size: 1.2rem; font-weight: 600;"
                                   required>
                            <small class="text-muted" style="display: block; margin-top: 0.5rem;">
                                <i class="fas fa-info-circle"></i>
                                عدد المشاريع المسموح بها شهرياً
                            </small>
                            @error('monthly_limit')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- ملاحظات -->
                        <div class="mb-4">
                            <label class="form-label-modern">
                                <i class="fas fa-sticky-note"></i>
                                ملاحظات
                            </label>
                            <textarea name="notes"
                                      class="form-control-modern form-control @error('notes') is-invalid @enderror"
                                      rows="4"
                                      placeholder="ملاحظات إضافية (اختياري)">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- تفعيل/تعطيل -->
                        <div class="mb-4" style="background: #f9fafb; padding: 1.5rem; border-radius: 12px; border: 2px solid #e5e7eb;">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_active" value="0">
                                <input class="form-check-input"
                                       type="checkbox"
                                       name="is_active"
                                       id="isActive"
                                       value="1"
                                       style="width: 3rem; height: 1.5rem; cursor: pointer;"
                                       {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="isActive" style="margin-right: 1rem; cursor: pointer;">
                                    <strong style="font-size: 1.1rem;">✅ تفعيل هذا الحد</strong>
                                    <small class="d-block text-muted" style="margin-top: 0.5rem;">
                                        <i class="fas fa-info-circle"></i>
                                        سيتم تطبيق الحد مباشرة بعد الحفظ
                                    </small>
                                </label>
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="d-flex gap-3 justify-content-end mt-4 pt-3" style="border-top: 2px solid #e5e7eb;">
                            <a href="{{ route('project-limits.index') }}" class="btn btn-custom" style="background: #6c757d; color: white; padding: 12px 28px;">
                                <i class="fas fa-times"></i>
                                إلغاء
                            </a>
                            <button type="submit" class="btn btn-custom btn-gradient-primary">
                                <i class="fas fa-save"></i>
                                حفظ الحد الشهري
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
document.addEventListener('DOMContentLoaded', function() {
    const limitType = document.getElementById('limitType');
    const entitySelection = document.getElementById('entitySelection');
    const departmentField = document.getElementById('departmentField');
    const teamField = document.getElementById('teamField');
    const userField = document.getElementById('userField');

    const departmentSelect = document.getElementById('departmentSelect');
    const teamSelect = document.getElementById('teamSelect');
    const userSelect = document.getElementById('userSelect');

    const entityIdHidden = document.getElementById('entityIdHidden');
    const entityNameHidden = document.getElementById('entityNameHidden');

    limitType.addEventListener('change', function() {
        // إخفاء كل الحقول أولاً
        entitySelection.style.display = 'none';
        departmentField.style.display = 'none';
        teamField.style.display = 'none';
        userField.style.display = 'none';

        // إعادة تعيين القيم
        entityIdHidden.value = '';
        entityNameHidden.value = '';

        const selectedType = this.value;

        if (selectedType === 'company') {
            // لا حاجة لاختيار entity
            entitySelection.style.display = 'none';
        } else if (selectedType === 'department') {
            entitySelection.style.display = 'block';
            departmentField.style.display = 'block';
        } else if (selectedType === 'team') {
            entitySelection.style.display = 'block';
            teamField.style.display = 'block';
        } else if (selectedType === 'user') {
            entitySelection.style.display = 'block';
            userField.style.display = 'block';
        }
    });

    // عند اختيار قسم
    departmentSelect.addEventListener('change', function() {
        entityIdHidden.value = '';
        entityNameHidden.value = this.value;
    });

    // عند اختيار فريق
    teamSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        entityIdHidden.value = this.value;
        entityNameHidden.value = selectedOption.dataset.name || '';
    });

    // عند اختيار موظف
    userSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        entityIdHidden.value = this.value;
        entityNameHidden.value = selectedOption.dataset.name || '';
    });
});
</script>
@endpush
@endsection
