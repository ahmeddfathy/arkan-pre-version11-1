@extends('layouts.app')

@section('title', 'إضافة مهارة جديدة')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/skills.css') }}">
<style>
    .form-container {
        background: white;
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        overflow: hidden;
        margin-bottom: 2rem;
    }

    .form-header {
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        color: white;
        padding: 1.5rem 2rem;
        text-align: center;
    }

    .form-header h2 {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 600;
    }

    .form-body {
        padding: 2rem;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-label {
        display: block;
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.5rem;
        font-size: 0.95rem;
    }

    .form-label i {
        color: #667eea;
        margin-left: 0.25rem;
    }

    .form-control, .form-select {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        font-size: 0.95rem;
        transition: all 0.2s;
    }

    .form-control:focus, .form-select:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .form-control.is-invalid, .form-select.is-invalid {
        border-color: #ef4444;
    }

    .invalid-feedback {
        color: #ef4444;
        font-size: 0.85rem;
        margin-top: 0.25rem;
        display: block;
    }

    .form-text {
        color: #6b7280;
        font-size: 0.85rem;
        margin-top: 0.25rem;
        display: block;
    }

    .custom-control-input:checked ~ .custom-control-label::before {
        background-color: #667eea;
        border-color: #667eea;
    }

    .form-actions {
        display: flex;
        gap: 1rem;
        justify-content: center;
        margin-top: 2rem;
        padding-top: 2rem;
        border-top: 2px solid #e5e7eb;
    }

    .help-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        border-right: 4px solid #667eea;
    }

    .help-card h3 {
        color: #1f2937;
        font-size: 1.1rem;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .help-card h3 i {
        color: #667eea;
    }

    .help-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .help-list li {
        padding: 0.5rem 0;
        color: #6b7280;
        display: flex;
        align-items: flex-start;
        gap: 0.5rem;
    }

    .help-list li i {
        margin-top: 0.25rem;
        flex-shrink: 0;
        color: #10b981;
    }

    .custom-control {
        position: relative;
        display: block;
        padding-left: 2rem;
    }

    .custom-control-input {
        position: absolute;
        left: 0;
        z-index: -1;
        width: 1.25rem;
        height: 1.25rem;
        opacity: 0;
    }

    .custom-control-label {
        position: relative;
        margin-bottom: 0;
        vertical-align: top;
    }

    .custom-control-label::before {
        position: absolute;
        top: 0.25rem;
        right: -2rem;
        display: block;
        width: 1.25rem;
        height: 1.25rem;
        pointer-events: none;
        content: "";
        background-color: #fff;
        border: 2px solid #adb5bd;
        border-radius: 0.25rem;
    }

    .custom-control-input:checked ~ .custom-control-label::before {
        background-color: #667eea;
        border-color: #667eea;
    }

    .custom-control-label::after {
        position: absolute;
        top: 0.25rem;
        right: -2rem;
        display: block;
        width: 1.25rem;
        height: 1.25rem;
        content: "";
        background: no-repeat 50% / 50% 50%;
    }

    .custom-control-input:checked ~ .custom-control-label::after {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8' viewBox='0 0 8 8'%3e%3cpath fill='%23fff' d='M6.564.75l-3.59 3.612-1.538-1.55L0 4.26l2.974 2.99L8 2.193z'/%3e%3c/svg%3e");
    }
</style>
@endpush

@section('content')
<div class="simple-container">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>➕ إضافة مهارة جديدة</h1>
            <p>أضف مهارة جديدة لنظام التقييم</p>
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
                        <h2>📝 بيانات المهارة</h2>
                    </div>

                    <div class="form-body">
                        <form method="POST" action="{{ route('skills.store') }}" id="skillForm">
                            @csrf

                            <div class="form-group">
                                <label for="name" class="form-label">
                                    <i class="fas fa-star"></i>
                                    اسم المهارة <span style="color: #ef4444;">*</span>
                                </label>
                                <input id="name"
                                       type="text"
                                       class="form-control @error('name') is-invalid @enderror"
                                       name="name"
                                       value="{{ old('name') }}"
                                       required
                                       autofocus
                                       placeholder="أدخل اسم المهارة (مثال: البرمجة، التصميم، إدارة المشاريع)">
                                @error('name')
                                    <span class="invalid-feedback">
                                        <i class="fas fa-exclamation-circle ml-1"></i>{{ $message }}
                                    </span>
                                @enderror
                                <small class="form-text">
                                    <i class="fas fa-info-circle ml-1"></i>
                                    اسم واضح ومميز للمهارة (يجب أن يكون فريداً)
                                </small>
                            </div>

                            <div class="form-group">
                                <label for="category_id" class="form-label">
                                    <i class="fas fa-tags"></i>
                                    التصنيف <span style="color: #ef4444;">*</span>
                                </label>
                                <select id="category_id"
                                        class="form-select @error('category_id') is-invalid @enderror"
                                        name="category_id"
                                        required>
                                    <option value="">-- اختر التصنيف المناسب --</option>
                                    @foreach($categories as $id => $name)
                                        <option value="{{ $id }}" {{ old('category_id') == $id ? 'selected' : '' }}>
                                            {{ $name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                    <span class="invalid-feedback">
                                        <i class="fas fa-exclamation-circle ml-1"></i>{{ $message }}
                                    </span>
                                @enderror
                                <div style="margin-top: 0.5rem;">
                                    <a href="{{ route('skill-categories.create') }}"
                                       style="color: #667eea; font-weight: 600; text-decoration: none;"
                                       target="_blank">
                                        <i class="fas fa-plus-circle ml-1"></i>
                                        إضافة تصنيف جديد
                                    </a>
                                </div>
                                <small class="form-text">
                                    <i class="fas fa-lightbulb ml-1"></i>
                                    اختر التصنيف الذي تنتمي إليه هذه المهارة
                                </small>
                            </div>

                            <div class="form-group">
                                <label for="max_points" class="form-label">
                                    <i class="fas fa-trophy"></i>
                                    الحد الأقصى للنقاط <span style="color: #ef4444;">*</span>
                                </label>
                                <input id="max_points"
                                       type="number"
                                       min="1"
                                       max="100"
                                       class="form-control @error('max_points') is-invalid @enderror"
                                       name="max_points"
                                       value="{{ old('max_points', 10) }}"
                                       required
                                       placeholder="10">
                                @error('max_points')
                                    <span class="invalid-feedback">
                                        <i class="fas fa-exclamation-circle ml-1"></i>{{ $message }}
                                    </span>
                                @enderror
                                <small class="form-text">
                                    <i class="fas fa-calculator ml-1"></i>
                                    أقصى عدد من النقاط (من 1 إلى 100 نقطة)
                                </small>
                            </div>

                            <div class="form-group">
                                <label for="description" class="form-label">
                                    <i class="fas fa-align-left"></i>
                                    الوصف
                                </label>
                                <textarea id="description"
                                          class="form-control @error('description') is-invalid @enderror"
                                          name="description"
                                          rows="4"
                                          placeholder="وصف تفصيلي للمهارة وأهميتها ومجالات استخدامها...">{{ old('description') }}</textarea>
                                @error('description')
                                    <span class="invalid-feedback">
                                        <i class="fas fa-exclamation-circle ml-1"></i>{{ $message }}
                                    </span>
                                @enderror
                                <small class="form-text">
                                    <i class="fas fa-pen ml-1"></i>
                                    وصف مفصل يوضح طبيعة المهارة (اختياري)
                                </small>
                            </div>

                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox"
                                           class="custom-control-input"
                                           name="is_active"
                                           id="is_active"
                                           value="1"
                                           {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="is_active">
                                        <i class="fas fa-toggle-on ml-1" style="color: #10b981;"></i>
                                        <strong>تفعيل المهارة</strong>
                                    </label>
                                    @error('is_active')
                                        <span class="invalid-feedback d-block">
                                            <i class="fas fa-exclamation-circle ml-1"></i>{{ $message }}
                                        </span>
                                    @enderror
                                    <small class="form-text d-block" style="margin-top: 0.5rem;">
                                        <i class="fas fa-info-circle ml-1"></i>
                                        المهارات النشطة فقط يمكن استخدامها في التقييمات
                                    </small>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="services-btn" style="background: linear-gradient(135deg, #10b981, #059669);">
                                    <i class="fas fa-save ml-1"></i>
                                    حفظ المهارة
                                </button>
                                <a href="{{ route('skills.index') }}" class="services-btn" style="background: linear-gradient(135deg, #6b7280, #4b5563);">
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
                            <span>اختر اسماً واضحاً ومحدداً للمهارة</span>
                        </li>
                        <li>
                            <i class="fas fa-check"></i>
                            <span>حدد التصنيف المناسب بعناية</span>
                        </li>
                        <li>
                            <i class="fas fa-check"></i>
                            <span>اكتب وصفاً يوضح أهمية المهارة</span>
                        </li>
                        <li>
                            <i class="fas fa-check"></i>
                            <span>حدد عدد النقاط بناءً على أهمية المهارة</span>
                        </li>
                        <li>
                            <i class="fas fa-check"></i>
                            <span>تأكد من تفعيل المهارة إذا كانت جاهزة</span>
                        </li>
                        <li>
                            <i class="fas fa-check"></i>
                            <span>راجع البيانات قبل الحفظ</span>
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
    const form = document.getElementById('skillForm');
    const submitBtn = form.querySelector('button[type="submit"]');

    // Form validation
    form.addEventListener('submit', function(e) {
        const name = form.querySelector('#name').value.trim();
        const categoryId = form.querySelector('#category_id').value;
        const maxPoints = form.querySelector('#max_points').value;

        if (!name || !categoryId || !maxPoints) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'خطأ',
                text: 'يرجى ملء جميع الحقول المطلوبة',
                confirmButtonColor: '#ef4444'
            });
            return false;
        }

        // Add loading state
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin ml-1"></i> جاري الحفظ...';
        submitBtn.disabled = true;
    });

    // Real-time validation
    const nameInput = document.getElementById('name');
    nameInput.addEventListener('input', function() {
        if (this.value.length > 0 && this.value.length < 3) {
            this.classList.add('is-invalid');
        } else {
            this.classList.remove('is-invalid');
        }
    });

    const maxPointsInput = document.getElementById('max_points');
    maxPointsInput.addEventListener('input', function() {
        const value = parseInt(this.value);
        if (value < 1 || value > 100) {
            this.classList.add('is-invalid');
        } else {
            this.classList.remove('is-invalid');
        }
    });
});
</script>
@endpush
