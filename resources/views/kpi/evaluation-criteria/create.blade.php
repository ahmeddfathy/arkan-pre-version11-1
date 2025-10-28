@extends('layouts.app')

@section('title', 'إضافة بند تقييم جديد')

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
                    @if(isset($selectedRole))
                        <div class="d-inline-block p-3 rounded-circle mb-4 floating" style="background: linear-gradient(135deg, #667eea, #764ba2); box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);">
                            <i class="fas fa-plus-circle fa-3x text-white"></i>
                        </div>
                        <h1 class="display-6 fw-bold mb-3" style="color: #2c3e50;">✨ إضافة بند تقييم جديد</h1>
                        <p class="lead mb-4" style="color: #6c757d;">
                            للدور: <span class="badge badge-modern badge-primary-modern fs-6">{{ $selectedRole->display_name ?? $selectedRole->name }}</span>
                        </p>
                    @else
                        <div class="d-inline-block p-3 rounded-circle mb-4 floating" style="background: linear-gradient(135deg, #667eea, #764ba2); box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);">
                            <i class="fas fa-plus-circle fa-3x text-white"></i>
                        </div>
                        <h1 class="display-6 fw-bold mb-3" style="color: #2c3e50;">✨ إضافة بند تقييم جديد</h1>
                    @endif

                    <div class="d-flex flex-wrap justify-content-center gap-2">
                        @if(isset($selectedRole))
                            <a href="{{ route('evaluation-criteria.select-role') }}" class="btn btn-modern btn-warning-modern">
                                <i class="fas fa-exchange-alt me-2"></i>تغيير الدور
                            </a>
                        @endif
                        <a href="{{ route('evaluation-criteria.index') }}" class="btn btn-modern btn-primary-modern">
                    <i class="fas fa-arrow-left me-2"></i>العودة للقائمة
                </a>
                    </div>
                </div>
            </div>

            <!-- 🎉 Success Toast -->
            @if(session('success') && session('toast'))
                <div class="position-fixed top-0 end-0 p-3" style="z-index: 1055;">
                    <div id="successToast" class="toast-modern show" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="false">
                        <div class="toast-header">
                            <div class="d-flex align-items-center">
                                <div class="p-2 rounded-circle me-2" style="background: linear-gradient(135deg, #28a745, #20c997);">
                                    <i class="fas fa-check text-white"></i>
                                </div>
                                <strong class="me-auto">🎉 تم بنجاح!</strong>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
                        </div>
                        <div class="toast-body">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-sparkles text-success me-2"></i>
                                <span>{{ session('success') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- 📋 Main Form Card -->
            <div class="modern-card slide-in-right">
                <div class="modern-card-header">
                    <h3 class="mb-0">
                        <i class="fas fa-edit me-2"></i>
                        نموذج إضافة بند التقييم
                    </h3>
                </div>

                <div class="modern-card-body">
                    <form method="POST" action="{{ route('evaluation-criteria.store') }}">
                        @csrf

                        <!-- 👤 Role Selection -->
                        @if(isset($selectedRole))
                            <!-- عرض الدور المحدد مسبقاً -->
                            <div class="role-display-modern">
                                <h6>🎯 الدور المحدد</h6>
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <div class="role-icon me-3">
                                            <i class="fas fa-user-tie"></i>
                                        </div>
                                        <div>
                                            <h5 class="fw-bold mb-1">{{ $selectedRole->display_name ?? $selectedRole->name }}</h5>
                                            @if($selectedRole->description)
                                                <p class="text-muted small mb-2">{{ $selectedRole->description }}</p>
                                            @endif
                                            <div>
                                                <span class="badge badge-modern badge-primary-modern badge-pulse" id="criteriaCounter">
                                                    <i class="fas fa-list me-1"></i>
                                                    @php
                                                        $currentCount = \App\Models\EvaluationCriteria::where('role_id', $selectedRole->id)->count();
                                                    @endphp
                                                    {{ $currentCount }} بند موجود
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <a href="{{ route('evaluation-criteria.select-role') }}" class="btn btn-modern btn-warning-modern">
                                        <i class="fas fa-exchange-alt me-2"></i>
                                        تغيير الدور
                                    </a>
                                </div>
                                <input type="hidden" name="role_id" value="{{ $selectedRole->id }}">
                            </div>
                        @else
                            <!-- اختيار الدور يدوياً -->
                            <div class="form-section-modern">
                                <h6><i class="fas fa-user-tie"></i> اختيار الدور</h6>
                                <div class="form-floating-modern">
                                    <select name="role_id" id="role_id" class="form-select-modern @error('role_id') is-invalid @enderror" required>
                                        <option value="">اختر الدور المطلوب</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                                        {{ $role->display_name ?? $role->name }}
                                    </option>
                                @endforeach
                            </select>
                                    <label for="role_id">👤 الدور <span class="text-danger">*</span></label>
                            @error('role_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                                <!-- رابط لاختيار الدور بالطريقة الجديدة -->
                                <div class="mt-3 p-3 rounded-3" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-magic text-primary me-2"></i>
                                        <span class="text-dark">
                                            أو <a href="{{ route('evaluation-criteria.select-role') }}" class="fw-bold text-decoration-none">اختر الدور بطريقة مرئية</a>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- 📝 معلومات البند الأساسية -->
                        <div class="form-section-modern">
                            <h6><i class="fas fa-edit"></i> معلومات البند الأساسية</h6>

                            <div class="form-row-modern row">
                                <!-- اسم البند -->
                                <div class="col-md-6">
                                    <div class="form-floating-modern">
                                        <input type="text"
                                               name="criteria_name"
                                               id="criteria_name"
                                               class="form-control-modern @error('criteria_name') is-invalid @enderror"
                                               value="{{ old('criteria_name') }}"
                                               placeholder="مثال: نسبة التفاعل مع العملاء"
                                               required>
                                        <label for="criteria_name">📝 اسم البند <span class="text-danger">*</span></label>
                                        @error('criteria_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- وصف البند -->
                                <div class="col-md-6">
                                    <div class="form-floating-modern">
                                        <textarea name="criteria_description"
                                                  id="criteria_description"
                                                  class="form-control-modern @error('criteria_description') is-invalid @enderror"
                                                  rows="4"
                                                  placeholder="وصف تفصيلي للبند وكيفية تقييمه">{{ old('criteria_description') }}</textarea>
                                        <label for="criteria_description">📋 وصف البند</label>
                                        @error('criteria_description')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 🔢 النقاط والنوع -->
                        <div class="form-section-modern">
                            <h6><i class="fas fa-chart-line"></i> النقاط والنوع</h6>
                            <div class="form-row-modern row">
                            <!-- Max Points -->
                            <div class="col-md-6">
                                    <div class="form-floating-modern">
                                    <input type="number"
                                           name="max_points"
                                           id="max_points"
                                               class="form-control-modern @error('max_points') is-invalid @enderror"
                                               value="{{ old('max_points', 10) }}"
                                           min="0"
                                           max="1000"
                                           required>
                                        <label for="max_points">🔢 أقصى نقاط <span class="text-danger">*</span></label>
                                    @error('max_points')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Criteria Type -->
                            <div class="col-md-6">
                                    <div class="form-floating-modern">
                                        <select name="criteria_type" id="criteria_type" class="form-select-modern @error('criteria_type') is-invalid @enderror" required>
                                        <option value="">اختر النوع</option>
                                        @foreach($criteriaTypes as $value => $label)
                                            <option value="{{ $value }}" {{ old('criteria_type') == $value ? 'selected' : '' }}>
                                                    @if($value == 'positive') ✅ @elseif($value == 'negative') ❌ @elseif($value == 'bonus') 🌟 @elseif($value == 'development') 🎓 @endif {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                        <label for="criteria_type">🎯 نوع البند <span class="text-danger">*</span></label>
                                    @error('criteria_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 📂 التصنيف والترتيب -->
                        <div class="form-section-modern">
                            <h6><i class="fas fa-tags"></i> التصنيف والترتيب</h6>
                            <div class="form-row-modern row">
                                <!-- Evaluation Period -->
                                <div class="col-md-6">
                                    <div class="form-floating-modern">
                                        <select name="evaluation_period" id="evaluation_period" class="form-select-modern @error('evaluation_period') is-invalid @enderror" required>
                                            <option value="">اختر فترة التقييم</option>
                                            @foreach($evaluationPeriods as $value => $label)
                                                <option value="{{ $value }}" {{ old('evaluation_period', 'monthly') == $value ? 'selected' : '' }}>
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
                            <!-- Category -->
                            <div class="col-md-6">
                                    <div class="form-floating-modern">
                                        <select name="category" id="category" class="form-select-modern @error('category') is-invalid @enderror">
                                            <option value="">اختر الفئة</option>
                                            @foreach($criteriaCategories as $value => $label)
                                                <option value="{{ $value }}" {{ old('category') == $value ? 'selected' : '' }}>
                                                    @if($value == 'بنود إيجابية') ✅ @elseif($value == 'بنود سلبية') ❌ @elseif($value == 'بنود تطويرية') 🎓 @elseif($value == 'بونص') 🌟 @endif {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <label for="category">📂 الفئة</label>
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
                                <input type="checkbox"
                                       name="is_active"
                                       id="is_active"
                                       class="form-check-input"
                                       value="1"
                                       {{ old('is_active', true) ? 'checked' : '' }}>
                                <label for="is_active" class="form-check-label">
                                    <i class="fas fa-toggle-on text-success me-2"></i>
                                    البند نشط ومفعل
                                </label>
                            </div>
                        </div>

                        <!-- 🎯 تقييم لكل مشروع -->
                        <div class="form-check-modern">
                            <div class="form-check">
                                <input type="checkbox"
                                       name="evaluate_per_project"
                                       id="evaluate_per_project"
                                       class="form-check-input"
                                       value="1"
                                       {{ old('evaluate_per_project', false) ? 'checked' : '' }}>
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
                                    @if(isset($selectedRole))
                                        <a href="{{ route('evaluation-criteria.index', ['role_id' => $selectedRole->id]) }}"
                                           class="btn btn-modern btn-success-modern w-100">
                                            <i class="fas fa-eye me-2"></i>مراجعة البنود الموجودة
                                        </a>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('evaluation-criteria.index') }}"
                                           class="btn btn-modern btn-warning-modern flex-fill">
                                            <i class="fas fa-times me-2"></i>إلغاء
                                        </a>
                                        <button type="submit" class="btn btn-modern btn-primary-modern flex-fill">
                                            <i class="fas fa-save me-2"></i>حفظ البند
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
    <script src="{{ asset('js/evaluation-criteria-create.js') }}"></script>
    @if(session('success') && session('toast'))
<script>
            setSuccessState(true);
</script>
    @endif
@endpush
@endsection
