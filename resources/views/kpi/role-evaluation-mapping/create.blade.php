@extends('layouts.app')

@section('title', 'إضافة ربط جديد بين الأدوار')

@push('styles')
<link href="{{ asset('css/role-evaluation-mapping.css') }}" rel="stylesheet">
@endpush

@push('scripts')
<script>
    // Store all available roles globally before any filtering
    window.allAvailableRoles = @json($roles->map(function($role) {
        return [
            'id' => $role->id,
            'name' => $role->name,
            'display_name' => $role->display_name ?? $role->name
        ];
    }));
</script>
<script src="{{ asset('js/role-evaluation-mapping.js') }}"></script>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="page-title">إضافة ربط جديد بين الأدوار</h2>
                <a href="{{ route('role-evaluation-mapping.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>العودة للقائمة
                </a>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">نموذج إضافة ربط التقييم</h5>
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route('role-evaluation-mapping.store') }}">
                        @csrf

                        <!-- Department Selection -->
                        <div class="mb-3">
                            <label for="department_name" class="form-label required">القسم <span class="text-danger">*</span></label>
                            <select name="department_name" id="department_name" class="form-select @error('department_name') is-invalid @enderror" required>
                                <option value="">اختر القسم</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department }}" {{ old('department_name') == $department ? 'selected' : '' }}>
                                        {{ $department }}
                                    </option>
                                @endforeach
                            </select>
                            @error('department_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Role to Evaluate -->
                        <div class="mb-3">
                            <label for="role_to_evaluate_id" class="form-label required">الدور المُراد تقييمه <span class="text-danger">*</span></label>
                            <select name="role_to_evaluate_id" id="role_to_evaluate_id" class="form-select @error('role_to_evaluate_id') is-invalid @enderror" required>
                                <option value="">اختر الدور</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}" {{ old('role_to_evaluate_id') == $role->id ? 'selected' : '' }}>
                                        {{ $role->display_name ?? $role->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('role_to_evaluate_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>


                        <!-- Multiple Evaluators Section -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="form-label mb-0">الأدوار المُقيمة <span class="text-danger">*</span></label>
                                <button type="button" id="addEvaluatorBtn" class="btn btn-success btn-sm">
                                    <i class="fas fa-plus"></i> إضافة مقيم جديد
                                </button>
                            </div>

                            <div id="evaluatorsContainer">
                                <!-- سيتم إضافة المقيمين هنا ديناميكياً -->
                            </div>

                            <div id="noEvaluatorsMessage" class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                اضغط على "إضافة مقيم جديد" لإضافة الأدوار التي ستقوم بالتقييم
                            </div>
                        </div>

                        <!-- Preview Section -->
                        <div class="mb-4">
                            <div class="preview-section">
                                <h6 class="preview-title">معاينة الربط</h6>
                                <div id="preview-content" class="preview-content">
                                    اختر القسم والدور المراد تقييمه، ثم أضف المقيمين
                                </div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="action-buttons">
                            <a href="{{ route('role-evaluation-mapping.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>إلغاء
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-1"></i>حفظ الربط
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>


@endsection
