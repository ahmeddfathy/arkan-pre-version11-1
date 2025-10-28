@extends('layouts.app')

@section('title', 'تعديل تصنيف المهارات')

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
        flex-wrap: wrap;
    }

    .info-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        border-right: 4px solid #3b82f6;
    }

    .info-card h3 {
        color: #1f2937;
        font-size: 1.1rem;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .info-card h3 i {
        color: #3b82f6;
    }

    .info-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .info-list li {
        padding: 0.75rem 0;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .info-list li:last-child {
        border-bottom: none;
    }

    .info-label {
        color: #6b7280;
        font-weight: 500;
    }

    .info-value {
        color: #1f2937;
        font-weight: 600;
    }

    .warning-card {
        background: #fffbeb;
        border-radius: 15px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        border-right: 4px solid #f59e0b;
    }

    .warning-card h3 {
        color: #92400e;
        font-size: 1.1rem;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .warning-card h3 i {
        color: #f59e0b;
    }

    .warning-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .warning-list li {
        padding: 0.5rem 0;
        color: #92400e;
        display: flex;
        align-items: flex-start;
        gap: 0.5rem;
    }

    .warning-list li i {
        color: #f59e0b;
        margin-top: 0.25rem;
        flex-shrink: 0;
    }
</style>
@endpush

@section('content')
<div class="simple-container">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>✏️ تعديل التصنيف</h1>
            <p>تحديث بيانات تصنيف "{{ $skillCategory->name }}"</p>
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
                        <h2>📝 تحديث بيانات التصنيف</h2>
                    </div>

                    <div class="form-body">
                        <form method="POST" action="{{ route('skill-categories.update', $skillCategory) }}" id="categoryEditForm">
                            @csrf
                            @method('PUT')

                            <div class="form-group">
                                <label for="name" class="form-label">
                                    <i class="fas fa-tag"></i>
                                    اسم التصنيف <span style="color: #ef4444;">*</span>
                                </label>
                                <input id="name"
                                       type="text"
                                       class="form-control @error('name') is-invalid @enderror"
                                       name="name"
                                       value="{{ old('name', $skillCategory->name) }}"
                                       required
                                       autofocus
                                       placeholder="أدخل اسم التصنيف">
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
                                          placeholder="وصف مختصر لهذا التصنيف...">{{ old('description', $skillCategory->description) }}</textarea>
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
                                    حفظ التغييرات
                                </button>
                                <a href="{{ route('skill-categories.show', $skillCategory) }}" class="services-btn" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
                                    <i class="fas fa-eye ml-1"></i>
                                    عرض التصنيف
                                </a>
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
                <!-- Info Card -->
                <div class="info-card">
                    <h3>
                        <i class="fas fa-info-circle"></i>
                        معلومات التصنيف
                    </h3>
                    <ul class="info-list">
                        <li>
                            <span class="info-label">عدد المهارات:</span>
                            <span class="info-value">
                                @if($skillCategory->skills->count() > 0)
                                    <span class="status-badge status-completed">{{ $skillCategory->skills->count() }} مهارة</span>
                                @else
                                    <span class="status-badge status-cancelled">فارغ</span>
                                @endif
                            </span>
                        </li>
                        <li>
                            <span class="info-label">تاريخ الإنشاء:</span>
                            <span class="info-value">{{ $skillCategory->created_at->format('d/m/Y') }}</span>
                        </li>
                        <li>
                            <span class="info-label">آخر تحديث:</span>
                            <span class="info-value">{{ $skillCategory->updated_at->diffForHumans() }}</span>
                        </li>
                        <li>
                            <span class="info-label">الحالة:</span>
                            <span class="info-value">
                                @if($skillCategory->skills->count() > 0)
                                    <span class="status-badge status-in-progress">مستخدم</span>
                                @else
                                    <span class="status-badge status-new">غير مستخدم</span>
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
                            <span>تعديل اسم التصنيف قد يؤثر على تنظيم المهارات</span>
                        </li>
                        <li>
                            <i class="fas fa-ban"></i>
                            <span>حذف التصنيف غير ممكن إذا كان يحتوي على مهارات</span>
                        </li>
                        @if($skillCategory->skills->count() > 0)
                        <li>
                            <i class="fas fa-star"></i>
                            <span>هذا التصنيف يحتوي على {{ $skillCategory->skills->count() }} مهارة حالياً</span>
                        </li>
                        @endif
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
    const form = document.getElementById('categoryEditForm');
    const submitBtn = form.querySelector('button[type="submit"]');

    // Store original values
    const originalValues = {
        name: form.querySelector('#name').value,
        description: form.querySelector('#description').value
    };

    // Check for changes
    function hasChanges() {
        return (
            form.querySelector('#name').value !== originalValues.name ||
            form.querySelector('#description').value !== originalValues.description
        );
    }

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

    // Real-time validation
    const nameInput = document.getElementById('name');
    nameInput.addEventListener('input', function() {
        if (this.value.length > 0 && this.value.length < 3) {
            this.classList.add('is-invalid');
        } else {
            this.classList.remove('is-invalid');
        }
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
