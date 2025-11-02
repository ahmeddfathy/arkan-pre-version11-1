@extends('layouts.app')

@section('title', 'إضافة بند تقييم جديد')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/kpi/evaluation-criteria-create.css') }}">
@endpush

@section('content')
<div class="simple-container">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1>➕ إضافة بند تقييم جديد</h1>
            <p>أضف بنداً جديداً لتقييم أداء الموظفين</p>
        </div>

        @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>يرجى تصحيح الأخطاء التالية:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <div class="row">
            <div class="col-md-8">
                <!-- Form Container -->
                <div class="form-container">
                    <div class="form-header">
                        <h2>📝 بيانات البند</h2>
                    </div>

                    <div class="form-body">
                        <form method="POST" action="{{ route('evaluation-criteria.store') }}" id="criteriaForm">
                            @csrf

                            <!-- Role Selection -->
                            @if(isset($selectedRole))
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-user-tie"></i>
                                    الدور
                                </label>
                                <div style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 1.5rem; border-radius: 8px; display: flex; align-items: center; justify-content: space-between;">
                                    <div style="display: flex; align-items: center; gap: 1rem;">
                                        <div style="width: 50px; height: 50px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-user-tie fa-2x"></i>
                                        </div>
                                        <div>
                                            <h4 style="margin: 0; color: white;">{{ $selectedRole->display_name ?? $selectedRole->name }}</h4>
                                            @php
                                            $currentCount = \App\Models\EvaluationCriteria::where('role_id', $selectedRole->id)->count();
                                            @endphp
                                            <small style="opacity: 0.9;">{{ $currentCount }} بند موجود</small>
                                        </div>
                                    </div>
                                    <a href="{{ route('evaluation-criteria.select-role') }}" class="services-btn" style="background: rgba(255,255,255,0.2); border: 1px solid white;">
                                        <i class="fas fa-exchange-alt ml-1"></i>
                                        تغيير
                                    </a>
                                </div>
                                <input type="hidden" name="role_id" value="{{ $selectedRole->id }}">
                            </div>
                            @else
                            <div class="form-group">
                                <label for="role_id" class="form-label">
                                    <i class="fas fa-user-tie"></i>
                                    الدور <span style="color: #ef4444;">*</span>
                                </label>
                                <select id="role_id"
                                    class="form-control @error('role_id') is-invalid @enderror"
                                    name="role_id"
                                    required>
                                    <option value="">اختر الدور</option>
                                    @foreach($roles as $role)
                                    <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                                        {{ $role->display_name ?? $role->name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('role_id')
                                <span class="invalid-feedback">
                                    <i class="fas fa-exclamation-circle ml-1"></i>{{ $message }}
                                </span>
                                @enderror
                            </div>
                            @endif

                            <div class="form-group">
                                <label for="criteria_name" class="form-label">
                                    <i class="fas fa-tag"></i>
                                    اسم البند <span style="color: #ef4444;">*</span>
                                </label>
                                <input id="criteria_name"
                                    type="text"
                                    class="form-control @error('criteria_name') is-invalid @enderror"
                                    name="criteria_name"
                                    value="{{ old('criteria_name') }}"
                                    required
                                    autofocus
                                    placeholder="أدخل اسم البند">
                                @error('criteria_name')
                                <span class="invalid-feedback">
                                    <i class="fas fa-exclamation-circle ml-1"></i>{{ $message }}
                                </span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="criteria_description" class="form-label">
                                    <i class="fas fa-align-left"></i>
                                    الوصف
                                </label>
                                <textarea id="criteria_description"
                                    class="form-control @error('criteria_description') is-invalid @enderror"
                                    name="criteria_description"
                                    rows="4"
                                    placeholder="وصف مختصر للبند...">{{ old('criteria_description') }}</textarea>
                                @error('criteria_description')
                                <span class="invalid-feedback">
                                    <i class="fas fa-exclamation-circle ml-1"></i>{{ $message }}
                                </span>
                                @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="max_points" class="form-label">
                                            <i class="fas fa-star"></i>
                                            أقصى نقاط <span style="color: #ef4444;">*</span>
                                        </label>
                                        <input id="max_points"
                                            type="number"
                                            class="form-control @error('max_points') is-invalid @enderror"
                                            name="max_points"
                                            value="{{ old('max_points', 10) }}"
                                            min="0"
                                            max="1000"
                                            required>
                                        @error('max_points')
                                        <span class="invalid-feedback">
                                            <i class="fas fa-exclamation-circle ml-1"></i>{{ $message }}
                                        </span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="criteria_type" class="form-label">
                                            <i class="fas fa-tags"></i>
                                            نوع البند <span style="color: #ef4444;">*</span>
                                        </label>
                                        <select id="criteria_type"
                                            class="form-control @error('criteria_type') is-invalid @enderror"
                                            name="criteria_type"
                                            required>
                                            <option value="">اختر النوع</option>
                                            @foreach($criteriaTypes as $value => $label)
                                            <option value="{{ $value }}" {{ old('criteria_type') == $value ? 'selected' : '' }}>
                                                @if($value == 'positive') ✅ @elseif($value == 'negative') ❌ @elseif($value == 'bonus') 🌟 @endif {{ $label }}
                                            </option>
                                            @endforeach
                                        </select>
                                        @error('criteria_type')
                                        <span class="invalid-feedback">
                                            <i class="fas fa-exclamation-circle ml-1"></i>{{ $message }}
                                        </span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="evaluation_period" class="form-label">
                                            <i class="fas fa-calendar"></i>
                                            فترة التقييم <span style="color: #ef4444;">*</span>
                                        </label>
                                        <select id="evaluation_period"
                                            class="form-control @error('evaluation_period') is-invalid @enderror"
                                            name="evaluation_period"
                                            required>
                                            @foreach($evaluationPeriods as $value => $label)
                                            <option value="{{ $value }}" {{ old('evaluation_period', 'monthly') == $value ? 'selected' : '' }}>
                                                @if($value == 'monthly') 📅 @else ⚡ @endif {{ $label }}
                                            </option>
                                            @endforeach
                                        </select>
                                        @error('evaluation_period')
                                        <span class="invalid-feedback">
                                            <i class="fas fa-exclamation-circle ml-1"></i>{{ $message }}
                                        </span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="category" class="form-label">
                                            <i class="fas fa-folder"></i>
                                            الفئة
                                        </label>
                                        <select id="category"
                                            class="form-control @error('category') is-invalid @enderror"
                                            name="category">
                                            <option value="">اختر الفئة</option>
                                            @foreach($criteriaCategories as $value => $label)
                                            <option value="{{ $value }}" {{ old('category') == $value ? 'selected' : '' }}>
                                                @if($value == 'بنود إيجابية') ✅ @elseif($value == 'بنود سلبية') ❌ @elseif($value == 'بونص') 🌟 @endif {{ $label }}
                                            </option>
                                            @endforeach
                                        </select>
                                        @error('category')
                                        <span class="invalid-feedback">
                                            <i class="fas fa-exclamation-circle ml-1"></i>{{ $message }}
                                        </span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="form-check">
                                    <input type="checkbox"
                                        name="is_active"
                                        id="is_active"
                                        class="form-check-input"
                                        value="1"
                                        {{ old('is_active', true) ? 'checked' : '' }}>
                                    <label for="is_active" class="form-check-label">
                                        <i class="fas fa-toggle-on text-success ml-1"></i>
                                        البند نشط ومفعل
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="form-check">
                                    <input type="checkbox"
                                        name="evaluate_per_project"
                                        id="evaluate_per_project"
                                        class="form-check-input"
                                        value="1"
                                        {{ old('evaluate_per_project', false) ? 'checked' : '' }}>
                                    <label for="evaluate_per_project" class="form-check-label">
                                        <i class="fas fa-project-diagram text-primary ml-1"></i>
                                        يتم تقييم هذا البند لكل مشروع على حدة
                                    </label>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="services-btn" style="background: linear-gradient(135deg, #10b981, #059669);">
                                    <i class="fas fa-save ml-1"></i>
                                    حفظ البند
                                </button>
                                <a href="{{ route('evaluation-criteria.index') }}" class="services-btn" style="background: linear-gradient(135deg, #6b7280, #4b5563);">
                                    <i class="fas fa-times ml-1"></i>
                                    إلغاء
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Help Card -->
                <div class="help-card">
                    <h3>
                        <i class="fas fa-question-circle"></i>
                        نصائح مفيدة
                    </h3>
                    <ul class="help-list">
                        <li>
                            <i class="fas fa-check"></i>
                            <span>اختر اسماً واضحاً ومحدداً للبند</span>
                        </li>
                        <li>
                            <i class="fas fa-check"></i>
                            <span>حدد النوع المناسب (إيجابي، سلبي، بونص)</span>
                        </li>
                        <li>
                            <i class="fas fa-check"></i>
                            <span>ضع النقاط المناسبة للبند</span>
                        </li>
                        <li>
                            <i class="fas fa-lightbulb"></i>
                            <span>استخدم الوصف لشرح كيفية التقييم</span>
                        </li>
                    </ul>
                </div>

                <!-- Stats Card -->
                <div class="help-card">
                    <h3>
                        <i class="fas fa-info-circle"></i>
                        معلومة
                    </h3>
                    <ul class="help-list">
                        <li>
                            <i class="fas fa-check"></i>
                            <span>يمكن تعديل البند لاحقاً</span>
                        </li>
                        <li>
                            <i class="fas fa-check"></i>
                            <span>يمكن تعطيل البند دون حذفه</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('criteriaForm');
        const submitBtn = form.querySelector('button[type="submit"]');

        // Form validation
        form.addEventListener('submit', function(e) {
            const name = form.querySelector('#criteria_name').value.trim();

            if (!name) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ',
                    text: 'يرجى إدخال اسم البند',
                    confirmButtonColor: '#ef4444'
                });
                return false;
            }

            // Add loading state
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin ml-1"></i> جاري الحفظ...';
            submitBtn.disabled = true;
        });

        // Real-time validation
        const nameInput = document.getElementById('criteria_name');
        nameInput.addEventListener('input', function() {
            if (this.value.length > 0 && this.value.length < 3) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
    });
</script>
@endpush