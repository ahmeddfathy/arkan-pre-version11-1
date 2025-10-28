@extends('layouts.app')

@section('title', 'تعديل البيانات الإضافية - ' . $project->name)

@section('content')
<div class="container-fluid py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('projects.index') }}">المشاريع</a></li>
            <li class="breadcrumb-item"><a href="{{ route('projects.show', $project) }}">{{ $project->name }}</a></li>
            <li class="breadcrumb-item active">البيانات الإضافية</li>
        </ol>
    </nav>

    <!-- Page Header -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="h4 mb-2">
                        <i class="fas fa-database text-purple me-2"></i>
                        البيانات الإضافية
                    </h2>
                    <p class="text-muted mb-0">
                        <i class="fas fa-project-diagram me-1"></i>
                        المشروع: <strong>{{ $project->name }}</strong>
                    </p>
                </div>
                <a href="{{ route('projects.show', $project) }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-right me-1"></i>
                    رجوع للمشروع
                </a>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Custom Fields Form -->
    <div class="card shadow-sm">
        <div class="card-body">
            @if($fields->isEmpty())
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">لا توجد حقول إضافية متاحة</h5>
                    <p class="text-muted">يمكنك إضافة حقول جديدة من صفحة <a href="{{ route('project-fields.index') }}">إدارة الحقول</a></p>
                </div>
            @else
                <form action="{{ route('projects.custom-fields.update', $project) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        @foreach($fields as $field)
                            @php
                                $currentValue = $project->custom_fields_data[$field->field_key] ?? old('custom_field_' . $field->id);
                            @endphp

                            <div class="col-md-6 mb-4">
                                <label for="custom_field_{{ $field->id }}" class="form-label">
                                    {{ $field->name }}
                                    @if($field->is_required)
                                        <span class="text-danger">*</span>
                                    @endif
                                </label>

                                @switch($field->field_type)
                                    @case('text')
                                        <input
                                            type="text"
                                            name="custom_field_{{ $field->id }}"
                                            id="custom_field_{{ $field->id }}"
                                            class="form-control"
                                            value="{{ $currentValue }}"
                                            {{ $field->is_required ? 'required' : '' }}
                                        >
                                        @break

                                    @case('number')
                                        <input
                                            type="number"
                                            name="custom_field_{{ $field->id }}"
                                            id="custom_field_{{ $field->id }}"
                                            class="form-control"
                                            value="{{ $currentValue }}"
                                            {{ $field->is_required ? 'required' : '' }}
                                        >
                                        @break

                                    @case('date')
                                        <input
                                            type="date"
                                            name="custom_field_{{ $field->id }}"
                                            id="custom_field_{{ $field->id }}"
                                            class="form-control"
                                            value="{{ $currentValue }}"
                                            {{ $field->is_required ? 'required' : '' }}
                                        >
                                        @break

                                    @case('select')
                                        <select
                                            name="custom_field_{{ $field->id }}"
                                            id="custom_field_{{ $field->id }}"
                                            class="form-select"
                                            {{ $field->is_required ? 'required' : '' }}
                                        >
                                            <option value="">-- اختر --</option>
                                            @foreach($field->field_options ?? [] as $option)
                                                <option value="{{ $option }}" {{ $currentValue == $option ? 'selected' : '' }}>
                                                    {{ $option }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @break

                                    @case('textarea')
                                        <textarea
                                            name="custom_field_{{ $field->id }}"
                                            id="custom_field_{{ $field->id }}"
                                            class="form-control"
                                            rows="4"
                                            {{ $field->is_required ? 'required' : '' }}
                                        >{{ $currentValue }}</textarea>
                                        @break
                                @endswitch

                                @if($field->description)
                                    <div class="form-text">{{ $field->description }}</div>
                                @endif

                                @error('custom_field_' . $field->id)
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        @endforeach
                    </div>

                    <hr class="my-4">

                    <div class="d-flex gap-2 justify-content-end">
                        <a href="{{ route('projects.show', $project) }}" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i>
                            إلغاء
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>
                            حفظ البيانات
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>

<style>
.text-purple {
    color: #9c27b0;
}

.breadcrumb {
    background-color: transparent;
    padding: 0;
}

.breadcrumb-item + .breadcrumb-item::before {
    content: "←";
}

.card {
    border: none;
}

.form-label {
    font-weight: 500;
    color: #495057;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
}
</style>
@endsection

