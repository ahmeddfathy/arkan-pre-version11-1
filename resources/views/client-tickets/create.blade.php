@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/client-tickets.css') }}">
@endpush

@section('content')
<div class="client-tickets-container">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <!-- Page Header -->
                <div class="page-header">
                    <h1><i class="fas fa-ticket-alt"></i> إضافة تذكرة جديدة</h1>
                    <p>قم بإنشاء تذكرة دعم جديدة للعملاء</p>
                </div>

                <div class="client-ticket-card" style="margin-top: 2rem;">
                    <div class="card-body">
                        <form method="POST" action="{{ route('client-tickets.store') }}">
                            @csrf

                            <div class="row">
                                <!-- Project Selection -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="project_id">المشروع</label>
                                        <select name="project_id" id="project_id" class="form-control @error('project_id') is-invalid @enderror">
                                            <option value="">اختر المشروع (اختياري)</option>
                                            @foreach($projects as $project)
                                                <option value="{{ $project->id }}"
                                                    {{ (old('project_id') == $project->id || ($selectedProject && $selectedProject->id == $project->id)) ? 'selected' : '' }}>
                                                    {{ $project->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('project_id')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Priority -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="priority">الأولوية *</label>
                                        <select name="priority" id="priority" class="form-control @error('priority') is-invalid @enderror" required>
                                            <option value="">اختر الأولوية</option>
                                            <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>منخفضة</option>
                                            <option value="medium" {{ old('priority', 'medium') == 'medium' ? 'selected' : '' }}>متوسطة</option>
                                            <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>عالية</option>
                                        </select>
                                        @error('priority')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Title -->
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="title">عنوان التذكرة *</label>
                                        <input type="text" name="title" id="title"
                                               class="form-control @error('title') is-invalid @enderror"
                                               value="{{ old('title') }}"
                                               placeholder="مثال: مشكلة في تسجيل الدخول"
                                               required>
                                        @error('title')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Department -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="department">القسم المختص</label>
                                        <select name="department" id="department" class="form-control @error('department') is-invalid @enderror">
                                            <option value="">اختر القسم (اختياري)</option>
                                            @foreach($departments as $department)
                                                <option value="{{ $department }}" {{ old('department') == $department ? 'selected' : '' }}>
                                                    {{ $department }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('department')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Client Selection -->
                                @if(isset($clients) && $clients->count() > 0)
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="client_id">العميل</label>
                                        <select name="client_id" id="client_id" class="form-control @error('client_id') is-invalid @enderror">
                                            <option value="">اختر العميل (اختياري)</option>
                                            @foreach($clients as $client)
                                                <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>
                                                    {{ $client->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('client_id')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                @endif

                                <!-- Assigned Users -->
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label><strong>تعيين الموظفين</strong></label>
                                        <div style="background: #f8f9fa; padding: 1rem; border-radius: 10px;">
                                            <small class="text-muted mb-2 d-block">اختر الموظفين المطلوب تعيينهم (اختياري)</small>
                                            <div class="row">
                                                @foreach($employees as $employee)
                                                    <div class="col-md-6 mb-2">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox"
                                                                   name="assigned_users[]" value="{{ $employee->id }}"
                                                                   id="create_employee_{{ $employee->id }}"
                                                                   {{ (old('assigned_users') && in_array($employee->id, old('assigned_users'))) ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="create_employee_{{ $employee->id }}">
                                                                {{ $employee->name }}
                                                            </label>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                            @error('assigned_users')
                                                <span class="text-danger small">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Description -->
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="description">وصف المشكلة أو الطلب *</label>
                                        <textarea name="description" id="description" rows="5"
                                                  class="form-control @error('description') is-invalid @enderror"
                                                  placeholder="اكتب وصفاً مفصلاً للمشكلة أو الطلب..." required>{{ old('description') }}</textarea>
                                        @error('description')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                        <small class="form-text text-muted">كلما كان الوصف أكثر تفصيلاً، كلما كان الحل أسرع</small>
                                    </div>
                                </div>
                            </div>

                            <div class="action-buttons" style="margin-top: 2rem; text-align: center;">
                                <button type="submit" class="arkan-btn arkan-btn-primary">
                                    <i class="fas fa-save"></i> إنشاء التذكرة
                                </button>
                                <a href="{{ route('client-tickets.index') }}" class="arkan-btn btn-secondary">
                                    <i class="fas fa-list"></i> العودة للقائمة
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Auto-update priority color
    $('#priority').change(function() {
        const priority = $(this).val();
        const $select = $(this);

        $select.removeClass('border-success border-warning border-danger');

        switch(priority) {
            case 'low':
                $select.addClass('border-success');
                break;
            case 'medium':
                $select.addClass('border-warning');
                break;
            case 'high':
                $select.addClass('border-danger');
                break;
        }
    });

    // Load project info when selected
    $('#project_id').change(function() {
        const projectId = $(this).val();
        if (projectId) {
            // Could load recent tickets for this project to show context
            // This is optional functionality
        }
    });

    // Trigger initial priority change
    $('#priority').trigger('change');
});
</script>
@endpush
