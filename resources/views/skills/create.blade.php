@extends('layouts.app')

@section('title', 'إضافة مهارة جديدة')

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
                            إضافة مهارة جديدة
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

                        <form method="POST" action="{{ route('skills.store') }}" id="skillForm">
                            @csrf

                            <div class="form-group row">
                                <label for="name" class="col-md-4 col-form-label text-md-right">
                                    <i class="fas fa-star text-warning ml-1"></i>
                                    اسم المهارة <span class="text-danger">*</span>
                                </label>
                                <div class="col-md-8">
                                    <input id="name"
                                           type="text"
                                           class="form-control @error('name') is-invalid @enderror"
                                           name="name"
                                           value="{{ old('name') }}"
                                           required
                                           autofocus
                                           placeholder="أدخل اسم المهارة (مثال: البرمجة، التصميم، إدارة المشاريع)">
                                    @error('name')
                                        <span class="invalid-feedback" role="alert">
                                            <strong><i class="fas fa-exclamation-circle ml-1"></i>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle ml-1"></i>
                                        اسم واضح ومميز للمهارة (يجب أن يكون فريداً)
                                    </small>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="category_id" class="col-md-4 col-form-label text-md-right">
                                    <i class="fas fa-tags text-info ml-1"></i>
                                    التصنيف <span class="text-danger">*</span>
                                </label>
                                <div class="col-md-8">
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
                                        <span class="invalid-feedback" role="alert">
                                            <strong><i class="fas fa-exclamation-circle ml-1"></i>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                    <div class="mt-2">
                                        <a href="{{ route('skill-categories.create') }}"
                                           class="text-primary font-weight-bold"
                                           target="_blank">
                                            <i class="fas fa-plus-circle ml-1"></i>
                                            إضافة تصنيف جديد
                                        </a>
                                    </div>
                                    <small class="form-text text-muted">
                                        <i class="fas fa-lightbulb ml-1"></i>
                                        اختر التصنيف الذي تنتمي إليه هذه المهارة
                                    </small>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="max_points" class="col-md-4 col-form-label text-md-right">
                                    <i class="fas fa-trophy text-warning ml-1"></i>
                                    الحد الأقصى للنقاط <span class="text-danger">*</span>
                                </label>
                                <div class="col-md-8">
                                    <div class="input-group">
                                        <input id="max_points"
                                               type="number"
                                               min="1"
                                               max="100"
                                               class="form-control @error('max_points') is-invalid @enderror"
                                               name="max_points"
                                               value="{{ old('max_points', 10) }}"
                                               required
                                               placeholder="10">
                                        <div class="input-group-append">
                                            <span class="input-group-text">
                                                <i class="fas fa-star text-warning"></i>
                                            </span>
                                        </div>
                                    </div>
                                    @error('max_points')
                                        <span class="invalid-feedback" role="alert">
                                            <strong><i class="fas fa-exclamation-circle ml-1"></i>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                    <small class="form-text text-muted">
                                        <i class="fas fa-calculator ml-1"></i>
                                        أقصى عدد من النقاط التي يمكن منحها لهذه المهارة (من 1 إلى 100 نقطة)
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
                                              placeholder="وصف تفصيلي للمهارة وأهميتها ومجالات استخدامها...">{{ old('description') }}</textarea>
                                    @error('description')
                                        <span class="invalid-feedback" role="alert">
                                            <strong><i class="fas fa-exclamation-circle ml-1"></i>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                    <small class="form-text text-muted">
                                        <i class="fas fa-pen ml-1"></i>
                                        وصف مفصل يوضح طبيعة المهارة ومتطلباتها (اختياري)
                                    </small>
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-md-8 offset-md-4">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox"
                                               class="custom-control-input"
                                               name="is_active"
                                               id="is_active"
                                               value="1"
                                               {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="is_active">
                                            <i class="fas fa-toggle-on text-success ml-1"></i>
                                            <strong>تفعيل المهارة</strong>
                                        </label>
                                        @error('is_active')
                                            <span class="invalid-feedback d-block" role="alert">
                                                <strong><i class="fas fa-exclamation-circle ml-1"></i>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                        <small class="form-text text-muted d-block mt-1">
                                            <i class="fas fa-info-circle ml-1"></i>
                                            المهارات النشطة فقط يمكن استخدامها في التقييمات
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="form-group row mb-0">
                                <div class="col-md-8 offset-md-4">
                                    <button type="submit" class="btn btn-primary btn-lg ml-2">
                                        <i class="fas fa-save ml-1"></i>
                                        حفظ المهارة
                                    </button>
                                    <a href="{{ route('skills.index') }}" class="btn btn-secondary btn-lg">
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
                            نصائح لإضافة مهارة فعالة
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success ml-1"></i>
                                        اختر اسماً واضحاً ومحدداً للمهارة
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success ml-1"></i>
                                        حدد التصنيف المناسب بعناية
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success ml-1"></i>
                                        اكتب وصفاً يوضح أهمية المهارة
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success ml-1"></i>
                                        حدد عدد النقاط بناءً على أهمية المهارة
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success ml-1"></i>
                                        تأكد من تفعيل المهارة إذا كانت جاهزة للاستخدام
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success ml-1"></i>
                                        راجع البيانات قبل الحفظ
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
    const form = document.getElementById('skillForm');
    const submitBtn = form.querySelector('button[type="submit"]');

    // Form validation
    form.addEventListener('submit', function(e) {
        const name = form.querySelector('#name').value.trim();
        const categoryId = form.querySelector('#category_id').value;
        const maxPoints = form.querySelector('#max_points').value;

        if (!name || !categoryId || !maxPoints) {
            e.preventDefault();
            alert('يرجى ملء جميع الحقول المطلوبة');
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
