@extends('layouts.app')

@section('title', 'إضافة تصنيف مهارات جديد')

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

    .form-control {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        font-size: 0.95rem;
        transition: all 0.2s;
    }

    .form-control:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .form-control.is-invalid {
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
    }

    .help-list li i.fa-check {
        color: #10b981;
    }

    .help-list li i.fa-lightbulb {
        color: #f59e0b;
    }
</style>
@endpush

@section('content')
<div class="simple-container">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>➕ إضافة تصنيف جديد</h1>
            <p>أضف تصنيفاً جديداً لتنظيم المهارات</p>
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
                        <h2>📝 بيانات التصنيف</h2>
                    </div>

                    <div class="form-body">
                        <form method="POST" action="{{ route('skill-categories.store') }}" id="categoryForm">
                            @csrf

                            <div class="form-group">
                                <label for="name" class="form-label">
                                    <i class="fas fa-tag"></i>
                                    اسم التصنيف <span style="color: #ef4444;">*</span>
                                </label>
                                <input id="name"
                                       type="text"
                                       class="form-control @error('name') is-invalid @enderror"
                                       name="name"
                                       value="{{ old('name') }}"
                                       required
                                       autofocus
                                       placeholder="أدخل اسم التصنيف (مثال: المهارات التقنية، المهارات الشخصية)">
                                @error('name')
                                    <span class="invalid-feedback">
                                        <i class="fas fa-exclamation-circle ml-1"></i>{{ $message }}
                                    </span>
                                @enderror
                                <small class="form-text">
                                    <i class="fas fa-info-circle ml-1"></i>
                                    على سبيل المثال: مهارات شخصية، مهارات فنية، مهارات إدارية
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
                                          placeholder="وصف مختصر لهذا التصنيف وما يشمله من مهارات...">{{ old('description') }}</textarea>
                                @error('description')
                                    <span class="invalid-feedback">
                                        <i class="fas fa-exclamation-circle ml-1"></i>{{ $message }}
                                    </span>
                                @enderror
                                <small class="form-text">
                                    <i class="fas fa-pen ml-1"></i>
                                    وصف يساعد في فهم أنواع المهارات التي تنتمي لهذا التصنيف
                                </small>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="services-btn" style="background: linear-gradient(135deg, #10b981, #059669);">
                                    <i class="fas fa-save ml-1"></i>
                                    حفظ التصنيف
                                </button>
                                <a href="{{ route('skill-categories.index') }}" class="services-btn" style="background: linear-gradient(135deg, #6b7280, #4b5563);">
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
                            <span>اختر اسماً واضحاً ومحدداً للتصنيف</span>
                        </li>
                        <li>
                            <i class="fas fa-check"></i>
                            <span>اكتب وصفاً يوضح نطاق التصنيف</span>
                        </li>
                        <li>
                            <i class="fas fa-check"></i>
                            <span>فكر في المهارات التي ستنضم لهذا التصنيف</span>
                        </li>
                        <li>
                            <i class="fas fa-lightbulb"></i>
                            <span>أمثلة: المهارات التقنية، القيادة، التواصل</span>
                        </li>
                    </ul>
                </div>

                <!-- Stats Card -->
                <div class="help-card" style="border-right-color: #3b82f6;">
                    <h3>
                        <i class="fas fa-info-circle"></i>
                        معلومة
                    </h3>
                    <ul class="help-list">
                        <li>
                            <i class="fas fa-check" style="color: #3b82f6;"></i>
                            <span>يمكن تعديل التصنيف لاحقاً حسب الحاجة</span>
                        </li>
                        <li>
                            <i class="fas fa-check" style="color: #3b82f6;"></i>
                            <span>التصنيف الجيد يسهل إدارة المهارات</span>
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
    const form = document.getElementById('categoryForm');
    const submitBtn = form.querySelector('button[type="submit"]');

    // Form validation
    form.addEventListener('submit', function(e) {
        const name = form.querySelector('#name').value.trim();

        if (!name) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'خطأ',
                text: 'يرجى إدخال اسم التصنيف',
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
});
</script>
@endpush
