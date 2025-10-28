@extends('layouts.app')

@section('title', 'تعديل المهارة')

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
                            تعديل المهارة: <span class="font-weight-normal">{{ $skill->name }}</span>
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

                        <form method="POST" action="{{ route('skills.update', $skill) }}" id="skillEditForm">
                            @csrf
                            @method('PUT')

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
                                           value="{{ old('name', $skill->name) }}"
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
                                            <option value="{{ $id }}" {{ old('category_id', $skill->category_id) == $id ? 'selected' : '' }}>
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
                                        التصنيف الحالي: <strong>{{ optional($skill->category)->name ?? 'بدون تصنيف' }}</strong>
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
                                               value="{{ old('max_points', $skill->max_points) }}"
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
                                              placeholder="وصف تفصيلي للمهارة وأهميتها ومجالات استخدامها...">{{ old('description', $skill->description) }}</textarea>
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
                                               {{ old('is_active', $skill->is_active) == '1' ? 'checked' : '' }}>
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
                                        حفظ التغييرات
                                    </button>
                                    <a href="{{ route('skills.show', $skill) }}" class="btn btn-info btn-lg ml-2">
                                        <i class="fas fa-eye ml-1"></i>
                                        عرض المهارة
                                    </a>
                                    <a href="{{ route('skills.index') }}" class="btn btn-secondary btn-lg">
                                        <i class="fas fa-times ml-1"></i>
                                        إلغاء
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Skill Info Card -->
                <div class="card mt-4 slide-up" style="animation-delay: 0.2s;">
                    <div class="card-header">
                        <h6 class="mb-0 text-white">
                            <i class="fas fa-info-circle ml-1"></i>
                            معلومات المهارة الحالية
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <strong>تاريخ الإنشاء:</strong>
                                        <br>
                                        <small class="text-muted">{{ $skill->created_at->format('d/m/Y H:i') }}</small>
                                    </li>
                                    <li class="mb-2">
                                        <strong>آخر تحديث:</strong>
                                        <br>
                                        <small class="text-muted">{{ $skill->updated_at->format('d/m/Y H:i') }}</small>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <strong>الحالة الحالية:</strong>
                                        <br>
                                        <span class="status-badge {{ $skill->is_active ? 'active' : 'inactive' }}">
                                            <i class="fas fa-{{ $skill->is_active ? 'check-circle' : 'times-circle' }} ml-1"></i>
                                            {{ $skill->is_active ? 'نشط' : 'غير نشط' }}
                                        </span>
                                    </li>
                                    <li class="mb-2">
                                        <strong>النقاط الحالية:</strong>
                                        <br>
                                        <span class="points-badge">
                                            <i class="fas fa-trophy ml-1"></i>
                                            {{ $skill->max_points }} نقطة
                                        </span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Help Card -->
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
                                <li>تعديل اسم المهارة قد يؤثر على التقييمات السابقة</li>
                                <li>تغيير عدد النقاط سيؤثر على حسابات النقاط المستقبلية فقط</li>
                                <li>إلغاء تفعيل المهارة سيمنع استخدامها في التقييمات الجديدة</li>
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
    const form = document.getElementById('skillEditForm');
    const submitBtn = form.querySelector('button[type="submit"]');

    // Store original values
    const originalValues = {
        name: form.querySelector('#name').value,
        category_id: form.querySelector('#category_id').value,
        max_points: form.querySelector('#max_points').value,
        description: form.querySelector('#description').value,
        is_active: form.querySelector('#is_active').checked
    };

    // Check for changes
    function hasChanges() {
        return (
            form.querySelector('#name').value !== originalValues.name ||
            form.querySelector('#category_id').value !== originalValues.category_id ||
            form.querySelector('#max_points').value !== originalValues.max_points ||
            form.querySelector('#description').value !== originalValues.description ||
            form.querySelector('#is_active').checked !== originalValues.is_active
        );
    }

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

    const maxPointsInput = document.getElementById('max_points');
    maxPointsInput.addEventListener('input', function() {
        const value = parseInt(this.value);
        if (value < 1 || value > 100) {
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
