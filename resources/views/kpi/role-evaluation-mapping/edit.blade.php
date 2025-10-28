@extends('layouts.app')

@section('title', 'تعديل ربط الدور بالتقييم')

@push('styles')
<link href="{{ asset('css/role-evaluation-mapping.css') }}" rel="stylesheet">
@endpush

@push('scripts')
<script>
    // Store all available roles globally
    window.allAvailableRoles = @json($roles->map(function($role) {
        return [
            'id' => $role->id,
            'name' => $role->name,
            'display_name' => $role->display_name ?? $role->name
        ];
    }));
    
    // Store linked criteria IDs for pre-selection
    window.linkedCriteriaIds = @json(isset($linkedCriteria) ? $linkedCriteria->pluck('criteria_id')->toArray() : []);
</script>
<script src="{{ asset('js/role-evaluation-mapping-edit.js') }}"></script>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-edit"></i>
                        تعديل ربط الدور بالتقييم
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('role-evaluation-mapping.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i>
                            العودة للقائمة
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('role-evaluation-mapping.update', $roleEvaluationMapping) }}">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="role_to_evaluate_id" class="form-label">الدور المراد تقييمه <span class="text-danger">*</span></label>
                                    <select name="role_to_evaluate_id" id="role_to_evaluate_id" class="form-select @error('role_to_evaluate_id') is-invalid @enderror" required>
                                        <option value="">-- اختر الدور المراد تقييمه --</option>
                                        @foreach($roles as $role)
                                            <option value="{{ $role->id }}"
                                                    {{ old('role_to_evaluate_id', $roleEvaluationMapping->role_to_evaluate_id) == $role->id ? 'selected' : '' }}>
                                                {{ $role->display_name ?? $role->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('role_to_evaluate_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="evaluator_role_id" class="form-label">الدور المقيم <span class="text-danger">*</span></label>
                                    <select name="evaluator_role_id" id="evaluator_role_id" class="form-select @error('evaluator_role_id') is-invalid @enderror" required>
                                        <option value="">-- اختر الدور المقيم --</option>
                                        @foreach($roles as $role)
                                            <option value="{{ $role->id }}"
                                                    {{ old('evaluator_role_id', $roleEvaluationMapping->evaluator_role_id) == $role->id ? 'selected' : '' }}>
                                                {{ $role->display_name ?? $role->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('evaluator_role_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="department_name" class="form-label">اسم القسم <span class="text-danger">*</span></label>
                                    <select name="department_name" id="department_name" class="form-select @error('department_name') is-invalid @enderror" required>
                                        <option value="">-- اختر القسم --</option>
                                        @foreach($departments as $department)
                                            <option value="{{ $department }}"
                                                    {{ old('department_name', $roleEvaluationMapping->department_name) == $department ? 'selected' : '' }}>
                                                {{ $department }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('department_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" name="can_evaluate" id="can_evaluate"
                                               class="form-check-input" value="1"
                                               {{ old('can_evaluate', $roleEvaluationMapping->can_evaluate) ? 'checked' : '' }}>
                                        <label for="can_evaluate" class="form-check-label">
                                            يمكن التقييم
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" name="can_view" id="can_view"
                                               class="form-check-input" value="1"
                                               {{ old('can_view', $roleEvaluationMapping->can_view) ? 'checked' : '' }}>
                                        <label for="can_view" class="form-check-label">
                                            يمكن المشاهدة فقط
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Criteria Selection -->
                        <div class="mb-4" id="criteriaSection">
                            <label class="form-label">البنود التي سيتم تقييمها</label>
                            <div class="preview-section">
                                <h6 class="preview-title">اختر البنود التي يمكن لهذا الدور تقييمها:</h6>
                                <div id="criteriaContainer">
                                    @if(isset($linkedCriteria) && count($linkedCriteria) > 0)
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle"></i>
                                            سيتم تحميل البنود المربوطة حالياً بعد اختيار الدور المراد تقييمه
                                        </div>
                                    @else
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            لا توجد بنود مربوطة حالياً - اختر الدور المراد تقييمه لربط البنود
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="action-buttons">
                            <a href="{{ route('role-evaluation-mapping.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i>
                                إلغاء
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                حفظ التعديلات
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
