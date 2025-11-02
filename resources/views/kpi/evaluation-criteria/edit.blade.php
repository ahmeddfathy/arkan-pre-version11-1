@extends('layouts.app')

@section('title', 'تعديل بند التقييم')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/kpi/evaluation-criteria-edit.css') }}">
@endpush

@section('content')
<div class="simple-container">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1>✏️ تعديل البند</h1>
            <p>تحديث بيانات بند "{{ $evaluationCriteria->criteria_name }}"</p>
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

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <div class="row">
            <div class="col-md-8">
                <!-- Form Container -->
                <div class="form-container">
                    <div class="form-header">
                        <h2>📝 تحديث بيانات البند</h2>
                    </div>

                    <div class="form-body">
                        <form method="POST" action="{{ route('evaluation-criteria.update', $evaluationCriteria) }}" id="criteriaEditForm">
                            @csrf
                            @method('PUT')

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="role_id" class="form-label">
                                            <i class="fas fa-user-tie"></i>
                                            الدور <span style="color: #ef4444;">*</span>
                                        </label>
                                        <select id="role_id"
                                            class="form-control @error('role_id') is-invalid @enderror"
                                            name="role_id"
                                            required>
                                            @foreach($roles as $role)
                                            <option value="{{ $role->id }}" {{ old('role_id', $evaluationCriteria->role_id) == $role->id ? 'selected' : '' }}>
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
                                            @foreach($criteriaTypes as $key => $label)
                                            <option value="{{ $key }}" {{ old('criteria_type', $evaluationCriteria->criteria_type) == $key ? 'selected' : '' }}>
                                                @if($key == 'positive') ✅ @elseif($key == 'negative') ❌ @elseif($key == 'bonus') 🌟 @endif {{ $label }}
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

                            <div class="form-group">
                                <label for="criteria_name" class="form-label">
                                    <i class="fas fa-tag"></i>
                                    اسم البند <span style="color: #ef4444;">*</span>
                                </label>
                                <input id="criteria_name"
                                    type="text"
                                    class="form-control @error('criteria_name') is-invalid @enderror"
                                    name="criteria_name"
                                    value="{{ old('criteria_name', $evaluationCriteria->criteria_name) }}"
                                    required
                                    autofocus>
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
                                    rows="4">{{ old('criteria_description', $evaluationCriteria->criteria_description) }}</textarea>
                                @error('criteria_description')
                                <span class="invalid-feedback">
                                    <i class="fas fa-exclamation-circle ml-1"></i>{{ $message }}
                                </span>
                                @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="max_points" class="form-label">
                                            <i class="fas fa-star"></i>
                                            أقصى نقاط <span style="color: #ef4444;">*</span>
                                        </label>
                                        <input id="max_points"
                                            type="number"
                                            class="form-control @error('max_points') is-invalid @enderror"
                                            name="max_points"
                                            value="{{ old('max_points', $evaluationCriteria->max_points) }}"
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

                                <div class="col-md-4">
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
                                            <option value="{{ $value }}" {{ old('evaluation_period', $evaluationCriteria->evaluation_period ?? 'monthly') == $value ? 'selected' : '' }}>
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

                                <div class="col-md-4">
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
                                            <option value="{{ $value }}" {{ old('category', $evaluationCriteria->category) == $value ? 'selected' : '' }}>
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
                                        {{ old('is_active', $evaluationCriteria->is_active) ? 'checked' : '' }}>
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
                                        {{ old('evaluate_per_project', $evaluationCriteria->evaluate_per_project) ? 'checked' : '' }}>
                                    <label for="evaluate_per_project" class="form-check-label">
                                        <i class="fas fa-project-diagram text-primary ml-1"></i>
                                        يتم تقييم هذا البند لكل مشروع على حدة
                                    </label>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="services-btn" style="background: linear-gradient(135deg, #10b981, #059669);">
                                    <i class="fas fa-save ml-1"></i>
                                    حفظ التغييرات
                                </button>
                                <a href="{{ route('evaluation-criteria.show', $evaluationCriteria) }}" class="services-btn" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
                                    <i class="fas fa-eye ml-1"></i>
                                    عرض البند
                                </a>
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
                <!-- Info Card -->
                <div class="info-card">
                    <h3>
                        <i class="fas fa-info-circle"></i>
                        معلومات البند
                    </h3>
                    <ul class="info-list">
                        <li>
                            <span class="info-label">الدور:</span>
                            <span class="info-value">{{ $evaluationCriteria->role->display_name ?? $evaluationCriteria->role->name }}</span>
                        </li>
                        <li>
                            <span class="info-label">تاريخ الإنشاء:</span>
                            <span class="info-value">{{ $evaluationCriteria->created_at->format('d/m/Y') }}</span>
                        </li>
                        <li>
                            <span class="info-label">آخر تحديث:</span>
                            <span class="info-value">{{ $evaluationCriteria->updated_at->diffForHumans() }}</span>
                        </li>
                        <li>
                            <span class="info-label">الحالة:</span>
                            <span class="info-value">
                                @if($evaluationCriteria->is_active)
                                <span class="status-badge status-in-progress">نشط</span>
                                @else
                                <span class="status-badge status-new">غير نشط</span>
                                @endif
                            </span>
                        </li>
                    </ul>
                </div>

                <!-- Warning Card -->
                <div class="warning-card">
                    <h3>
                        <i class="fas fa-exclamation-triangle"></i>
                        تنبيهات مهمة
                    </h3>
                    <ul class="warning-list">
                        <li>
                            <i class="fas fa-info-circle"></i>
                            <span>تعديل البيانات قد يؤثر على التقييمات السابقة</span>
                        </li>
                        <li>
                            <i class="fas fa-ban"></i>
                            <span>تأكد من صحة البيانات قبل الحفظ</span>
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
        const form = document.getElementById('criteriaEditForm');
        const submitBtn = form.querySelector('button[type="submit"]');

        // Store original values
        const originalValues = {
            name: form.querySelector('#criteria_name').value,
            description: form.querySelector('#criteria_description').value
        };

        // Check for changes
        function hasChanges() {
            return (
                form.querySelector('#criteria_name').value !== originalValues.name ||
                form.querySelector('#criteria_description').value !== originalValues.description
            );
        }

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

            if (!hasChanges()) {
                e.preventDefault();
                Swal.fire({
                    icon: 'info',
                    title: 'لا توجد تغييرات',
                    text: 'لم يتم إجراء أي تغييرات على البيانات',
                    confirmButtonColor: '#3b82f6'
                });
                return false;
            }

            // Add loading state
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin ml-1"></i> جاري الحفظ...';
            submitBtn.disabled = true;
        });

        // Warn before leaving if there are unsaved changes
        window.addEventListener('beforeunload', function(e) {
            if (hasChanges()) {
                e.preventDefault();
                e.returnValue = 'لديك تغييرات غير محفوظة. هل أنت متأكد من رغبتك في المغادرة؟';
            }
        });
    });
</script>
@endpush