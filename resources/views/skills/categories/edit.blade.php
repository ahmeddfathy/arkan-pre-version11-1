@extends('layouts.app')

@section('title', 'تعديل تصنيف المهارات')

@push('styles')
<link href="{{ asset('css/skills.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="skills-container">
    <div class="container">
        <div class="row justify-content-center fade-in">
            <div class="col-md-8">
                <div class="card skills-form">
                    <div class="card-header skills-header">
                        <h5 class="mb-0">
                            <i class="fas fa-edit ml-2"></i>
                            تعديل تصنيف المهارات: <span class="font-weight-normal">{{ $skillCategory->name }}</span>
                        </h5>
                    </div>

                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger slide-up">
                                <h6><i class="fas fa-exclamation-triangle ml-2"></i>يرجى تصحيح الأخطاء التالية:</h6>
                                <ul class="mb-0 mt-2">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if (session('success'))
                            <div class="alert alert-success slide-up">
                                <i class="fas fa-check-circle ml-2"></i>
                                {{ session('success') }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('skill-categories.update', $skillCategory) }}" id="categoryEditForm">
                            @csrf
                            @method('PUT')

                            <div class="form-group row">
                                <label for="name" class="col-md-4 col-form-label text-md-right">
                                    <i class="fas fa-tag text-info ml-1"></i>
                                    اسم التصنيف <span class="text-danger">*</span>
                                </label>
                                <div class="col-md-8">
                                    <input id="name"
                                           type="text"
                                           class="form-control @error('name') is-invalid @enderror"
                                           name="name"
                                           value="{{ old('name', $skillCategory->name) }}"
                                           required
                                           autofocus
                                           placeholder="أدخل اسم التصنيف (مثال: المهارات التقنية، المهارات الشخصية)">
                                    @error('name')
                                        <span class="invalid-feedback" role="alert">
                                            <strong><i class="fas fa-exclamation-circle ml-1"></i>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle ml-1"></i>
                                        على سبيل المثال: مهارات شخصية، مهارات فنية، مهارات إدارية، إلخ.
                                    </small>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="description" class="col-md-4 col-form-label text-md-right">
                                    <i class="fas fa-align-left text-secondary ml-1"></i>
                                    الوصف
                                </label>
                                <div class="col-md-8">
                                    <textarea id="description"
                                              class="form-control @error('description') is-invalid @enderror"
                                              name="description"
                                              rows="4"
                                              placeholder="وصف مختصر لهذا التصنيف وما يشمله من مهارات...">{{ old('description', $skillCategory->description) }}</textarea>
                                    @error('description')
                                        <span class="invalid-feedback" role="alert">
                                            <strong><i class="fas fa-exclamation-circle ml-1"></i>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                    <small class="form-text text-muted">
                                        <i class="fas fa-pen ml-1"></i>
                                        وصف يساعد في فهم أنواع المهارات التي تنتمي لهذا التصنيف
                                    </small>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="form-group row mb-0">
                                <div class="col-md-8 offset-md-4">
                                    <button type="submit" class="btn btn-primary btn-lg ml-2">
                                        <i class="fas fa-save ml-1"></i>
                                        حفظ التغييرات
                                    </button>
                                    <a href="{{ route('skill-categories.show', $skillCategory) }}" class="btn btn-info btn-lg ml-2">
                                        <i class="fas fa-eye ml-1"></i>
                                        عرض التصنيف
                                    </a>
                                    <a href="{{ route('skill-categories.index') }}" class="btn btn-secondary btn-lg">
                                        <i class="fas fa-times ml-1"></i>
                                        إلغاء
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Category Info Card -->
                <div class="card mt-4 slide-up" style="animation-delay: 0.2s;">
                    <div class="card-header">
                        <h6 class="mb-0 text-white">
                            <i class="fas fa-info-circle ml-1"></i>
                            معلومات التصنيف الحالي
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <strong>تاريخ الإنشاء:</strong>
                                        <br>
                                        <small class="text-muted">{{ $skillCategory->created_at->format('d/m/Y H:i') }}</small>
                                    </li>
                                    <li class="mb-2">
                                        <strong>آخر تحديث:</strong>
                                        <br>
                                        <small class="text-muted">{{ $skillCategory->updated_at->format('d/m/Y H:i') }}</small>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <strong>عدد المهارات:</strong>
                                        <br>
                                        <span class="points-badge">
                                            <i class="fas fa-star ml-1"></i>
                                            {{ $skillCategory->skills->count() }} مهارة
                                        </span>
                                    </li>
                                    <li class="mb-2">
                                        <strong>الحالة:</strong>
                                        <br>
                                        <span class="status-badge {{ $skillCategory->skills->count() > 0 ? 'active' : 'inactive' }}">
                                            <i class="fas fa-{{ $skillCategory->skills->count() > 0 ? 'check-circle' : 'times-circle' }} ml-1"></i>
                                            {{ $skillCategory->skills->count() > 0 ? 'مستخدم' : 'غير مستخدم' }}
                                        </span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Warning Card -->
                <div class="card mt-3 slide-up" style="animation-delay: 0.4s;">
                    <div class="card-header">
                        <h6 class="mb-0 text-white">
                            <i class="fas fa-exclamation-triangle ml-1"></i>
                            تنبيهات مهمة
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <ul class="mb-0">
                                <li>تعديل اسم التصنيف قد يؤثر على تنظيم المهارات</li>
                                <li>حذف التصنيف غير ممكن إذا كان يحتوي على مهارات</li>
                                @if($skillCategory->skills->count() > 0)
                                    <li>هذا التصنيف يحتوي على {{ $skillCategory->skills->count() }} مهارة حالياً</li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
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
            alert('يرجى إدخال اسم التصنيف');
            return false;
        }

        if (!hasChanges()) {
            e.preventDefault();
            alert('لم يتم إجراء أي تغييرات على البيانات');
            return false;
        }

        // Add loading state
        submitBtn.classList.add('loading');
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
