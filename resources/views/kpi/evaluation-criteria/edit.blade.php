@extends('layouts.app')

@section('title', 'تعديل بند التقييم')

@push('styles')
    <link href="{{ asset('css/evaluation-criteria-modern.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="container-fluid evaluation-container">
    <div class="row justify-content-center">
        <div class="col-xl-10 col-lg-11">
            <!-- 🎯 Header Section -->
            <div class="modern-card mb-5 fade-in-up">
                <div class="text-center p-5" style="background: linear-gradient(135deg, #ffffff 0%, #f8f9ff 100%); border-radius: 20px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);">
                    <div class="d-inline-block p-3 rounded-circle mb-4 floating" style="background: linear-gradient(135deg, #fa709a, #fee140); box-shadow: 0 8px 20px rgba(250, 112, 154, 0.3);">
                        <i class="fas fa-edit fa-3x text-white"></i>
                    </div>
                    <h1 class="display-6 fw-bold mb-3" style="color: #2c3e50;">✏️ تعديل بند التقييم</h1>
                    <p class="lead mb-4" style="color: #6c757d;">
                        قم بتحديث وتطوير معايير التقييم حسب احتياجاتك
                    </p>

                    <div class="d-flex flex-wrap justify-content-center gap-2">
                        <a href="{{ route('evaluation-criteria.index') }}" class="btn btn-modern btn-primary-modern">
                            <i class="fas fa-arrow-left me-2"></i>العودة للقائمة
                        </a>
                        <a href="{{ route('evaluation-criteria.show', $evaluationCriteria) }}" class="btn btn-modern btn-success-modern">
                            <i class="fas fa-eye me-2"></i>عرض التفاصيل
                        </a>
                    </div>
                </div>
            </div>

            <!-- 📝 Edit Form Card -->
            <div class="modern-card slide-in-right">
                <div class="modern-card-header">
                    <h3 class="mb-0">
                        <i class="fas fa-clipboard-list me-2"></i>
                        نموذج التعديل
                    </h3>
                </div>
                <div class="modern-card-body">
                    <form method="POST" action="{{ route('evaluation-criteria.update', $evaluationCriteria) }}">
                        @csrf
                        @method('PUT')

                        <!-- 🎯 الدور والنوع -->
                        <div class="form-section-modern">
                            <h6><i class="fas fa-user-cog"></i> الدور والنوع</h6>
                            <div class="form-row-modern row">
                                <div class="col-md-6">
                                    <div class="form-floating-modern">
                                        <select name="role_id" id="role_id" class="form-select-modern @error('role_id') is-invalid @enderror" required>
                                            <option value="">-- اختر الدور --</option>
                                            @foreach($roles as $role)
                                                <option value="{{ $role->id }}"
                                                        {{ old('role_id', $evaluationCriteria->role_id) == $role->id ? 'selected' : '' }}>
                                                    {{ $role->display_name ?? $role->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <label for="role_id">👤 الدور <span class="text-danger">*</span></label>
                                        @error('role_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating-modern">
                                        <select name="criteria_type" id="criteria_type" class="form-select-modern @error('criteria_type') is-invalid @enderror" required>
                                            @foreach($criteriaTypes as $key => $label)
                                                <option value="{{ $key }}"
                                                        {{ old('criteria_type', $evaluationCriteria->criteria_type) == $key ? 'selected' : '' }}>
                                                    @if($key == 'positive') ✅ @elseif($key == 'negative') ❌ @elseif($key == 'bonus') 🌟 @elseif($key == 'development') 🎓 @endif {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <label for="criteria_type">🏷️ نوع البند <span class="text-danger">*</span></label>
                                        @error('criteria_type')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 📝 معلومات البند -->
                        <div class="form-section-modern">
                            <h6><i class="fas fa-edit"></i> معلومات البند</h6>

                            <!-- اسم البند والنقاط -->
                            <div class="form-row-modern row">
                                <div class="col-md-8">
                                    <div class="form-floating-modern">
                                        <input type="text" name="criteria_name" id="criteria_name"
                                               class="form-control-modern @error('criteria_name') is-invalid @enderror"
                                               value="{{ old('criteria_name', $evaluationCriteria->criteria_name) }}" required>
                                        <label for="criteria_name">📝 اسم البند <span class="text-danger">*</span></label>
                                        @error('criteria_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-floating-modern">
                                        <input type="number" name="max_points" id="max_points"
                                               class="form-control-modern @error('max_points') is-invalid @enderror"
                                               value="{{ old('max_points', $evaluationCriteria->max_points) }}"
                                               min="0" max="1000" required>
                                        <label for="max_points">🔢 أقصى نقاط <span class="text-danger">*</span></label>
                                        @error('max_points')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- وصف البند -->
                            <div class="form-floating-modern">
                                <textarea name="criteria_description" id="criteria_description" rows="4"
                                          class="form-control-modern @error('criteria_description') is-invalid @enderror">{{ old('criteria_description', $evaluationCriteria->criteria_description) }}</textarea>
                                <label for="criteria_description">📋 وصف البند</label>
                                @error('criteria_description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- 📂 التصنيف والترتيب -->
                        <div class="form-section-modern">
                            <h6><i class="fas fa-tags"></i> التصنيف والترتيب</h6>
                            <div class="form-row-modern row">
                                <div class="col-md-6">
                                    <div class="form-floating-modern">
                                        <select name="evaluation_period" id="evaluation_period" class="form-select-modern @error('evaluation_period') is-invalid @enderror" required>
                                            @foreach($evaluationPeriods as $value => $label)
                                                <option value="{{ $value }}" {{ old('evaluation_period', $evaluationCriteria->evaluation_period ?? 'monthly') == $value ? 'selected' : '' }}>
                                                    @if($value == 'monthly') 📅 @else ⚡ @endif {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <label for="evaluation_period">📅 فترة التقييم <span class="text-danger">*</span></label>
                                        @error('evaluation_period')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating-modern">
                                        <select name="category" id="category" class="form-select-modern @error('category') is-invalid @enderror">
                                            <option value="">اختر الفئة</option>
                                            @foreach($criteriaCategories as $value => $label)
                                                <option value="{{ $value }}" {{ old('category', $evaluationCriteria->category) == $value ? 'selected' : '' }}>
                                                    @if($value == 'بنود إيجابية') ✅ @elseif($value == 'بنود سلبية') ❌ @elseif($value == 'بنود تطويرية') 🎓 @elseif($value == 'بونص') 🌟 @endif {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <label for="category">📂 فئة البند</label>
                                        @error('category')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                            </div>
                        </div>

                        <!-- ✅ حالة البند -->
                        <div class="form-check-modern mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" id="is_active"
                                       class="form-check-input" value="1"
                                       {{ old('is_active', $evaluationCriteria->is_active) ? 'checked' : '' }}>
                                <label for="is_active" class="form-check-label">
                                    <i class="fas fa-toggle-on text-success me-2"></i>
                                    البند نشط ومفعل
                                </label>
                            </div>
                        </div>

                        <!-- 🎯 تقييم لكل مشروع -->
                        <div class="form-check-modern">
                            <div class="form-check">
                                <input type="checkbox" name="evaluate_per_project" id="evaluate_per_project"
                                       class="form-check-input" value="1"
                                       {{ old('evaluate_per_project', $evaluationCriteria->evaluate_per_project) ? 'checked' : '' }}>
                                <label for="evaluate_per_project" class="form-check-label">
                                    <i class="fas fa-project-diagram text-primary me-2"></i>
                                    يتم تقييم هذا البند لكل مشروع على حدة
                                </label>
                                <small class="text-muted d-block mt-1">
                                    إذا تم تفعيل هذا الخيار، سيظهر هذا البند في تقييم كل مشروع يشارك فيه الموظف
                                </small>
                            </div>
                        </div>

                        <!-- 🚀 Action Buttons -->
                        <div class="action-buttons-modern">
                            <div class="row">
                                <div class="col-md-6">
                                    <a href="{{ route('evaluation-criteria.show', $evaluationCriteria) }}"
                                       class="btn btn-modern btn-success-modern w-100">
                                        <i class="fas fa-eye me-2"></i>عرض التفاصيل
                                    </a>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('evaluation-criteria.index') }}"
                                           class="btn btn-modern btn-warning-modern flex-fill">
                                            <i class="fas fa-times me-2"></i>إلغاء
                                        </a>
                                        <button type="submit" class="btn btn-modern btn-primary-modern flex-fill">
                                            <i class="fas fa-save me-2"></i>حفظ التعديلات
                                        </button>
                                    </div>
                                </div>
                            </div>
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
            // 🎨 تأثير تدرجي لظهور العناصر
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('fade-in-up');
                    }
                });
            }, observerOptions);

            // مراقبة جميع عناصر النموذج
            const formElements = document.querySelectorAll('.form-floating-modern');
            formElements.forEach((element, index) => {
                element.style.animationDelay = (index * 0.1) + 's';
                observer.observe(element);
            });

            // 🎯 تأثير النوع على الرؤوس
            const criteriaTypeSelect = document.getElementById('criteria_type');
            criteriaTypeSelect.addEventListener('change', function() {
                const cardHeader = document.querySelector('.modern-card-header');
                const value = this.value;

                if (value === 'positive') {
                    cardHeader.style.background = 'var(--success-gradient)';
                } else if (value === 'negative') {
                    cardHeader.style.background = 'var(--danger-gradient)';
                } else if (value === 'bonus') {
                    cardHeader.style.background = 'var(--warning-gradient)';
                } else {
                    cardHeader.style.background = 'var(--primary-gradient)';
                }
            });
        });
    </script>
@endpush
@endsection
