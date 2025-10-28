@extends('layouts.app')

@section('title', 'إضافة تصنيف مهارات جديد')

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
                            <i class="fas fa-plus-circle ml-2"></i>
                            إضافة تصنيف مهارات جديد
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

                        <form method="POST" action="{{ route('skill-categories.store') }}" id="categoryForm">
                            @csrf

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
                                           value="{{ old('name') }}"
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
                                              placeholder="وصف مختصر لهذا التصنيف وما يشمله من مهارات...">{{ old('description') }}</textarea>
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
                                        حفظ التصنيف
                                    </button>
                                    <a href="{{ route('skill-categories.index') }}" class="btn btn-secondary btn-lg">
                                        <i class="fas fa-times ml-1"></i>
                                        إلغاء
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Help Card -->
                <div class="card mt-4 slide-up" style="animation-delay: 0.2s;">
                    <div class="card-header">
                        <h6 class="mb-0 text-white">
                            <i class="fas fa-question-circle ml-1"></i>
                            نصائح لإنشاء تصنيف فعال
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success ml-1"></i>
                                        اختر اسماً واضحاً ومحدداً للتصنيف
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success ml-1"></i>
                                        اكتب وصفاً يوضح نطاق التصنيف
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success ml-1"></i>
                                        فكر في المهارات التي ستنضم لهذا التصنيف
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="fas fa-lightbulb text-warning ml-1"></i>
                                        أمثلة: المهارات التقنية، القيادة، التواصل
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-info-circle text-info ml-1"></i>
                                        يمكن تعديل التصنيف لاحقاً حسب الحاجة
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-star text-success ml-1"></i>
                                        التصنيف الجيد يسهل إدارة المهارات
                                    </li>
                                </ul>
                            </div>
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
    const form = document.getElementById('categoryForm');
    const submitBtn = form.querySelector('button[type="submit"]');

    // Form validation
    form.addEventListener('submit', function(e) {
        const name = form.querySelector('#name').value.trim();

        if (!name) {
            e.preventDefault();
            alert('يرجى إدخال اسم التصنيف');
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
});
</script>
@endpush
