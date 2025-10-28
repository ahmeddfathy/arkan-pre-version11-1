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
                    <h1><i class="fas fa-edit"></i> تعديل التذكرة</h1>
                    <p>التذكرة: <code class="ticket-number">{{ $clientTicket->ticket_number }}</code></p>
                </div>

                <div class="client-ticket-card" style="margin-top: 2rem;">
                    <div class="card-body">
                        <form method="POST" action="{{ route('client-tickets.update', $clientTicket) }}">
                            @csrf
                            @method('PUT')

                            <div class="row">
                                <!-- Project Selection -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="project_id">المشروع</label>
                                        <select name="project_id" id="project_id" class="form-control @error('project_id') is-invalid @enderror">
                                            <option value="">اختر المشروع (اختياري)</option>
                                            @foreach($projects as $project)
                                                <option value="{{ $project->id }}"
                                                    {{ old('project_id', $clientTicket->project_id) == $project->id ? 'selected' : '' }}>
                                                    {{ $project->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('project_id')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Status -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="status">الحالة *</label>
                                        <select name="status" id="status" class="form-control @error('status') is-invalid @enderror" required>
                                            <option value="open" {{ old('status', $clientTicket->status) == 'open' ? 'selected' : '' }}>مفتوحة</option>
                                            <option value="assigned" {{ old('status', $clientTicket->status) == 'assigned' ? 'selected' : '' }}>معينة</option>
                                            <option value="resolved" {{ old('status', $clientTicket->status) == 'resolved' ? 'selected' : '' }}>محلولة</option>
                                            <option value="closed" {{ old('status', $clientTicket->status) == 'closed' ? 'selected' : '' }}>مغلقة</option>
                                        </select>
                                        @error('status')
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
                                               value="{{ old('title', $clientTicket->title) }}"
                                               placeholder="مثال: مشكلة في تسجيل الدخول"
                                               required>
                                        @error('title')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Priority -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="priority">الأولوية *</label>
                                        <select name="priority" id="priority" class="form-control @error('priority') is-invalid @enderror" required>
                                            <option value="low" {{ old('priority', $clientTicket->priority) == 'low' ? 'selected' : '' }}>منخفضة</option>
                                            <option value="medium" {{ old('priority', $clientTicket->priority) == 'medium' ? 'selected' : '' }}>متوسطة</option>
                                            <option value="high" {{ old('priority', $clientTicket->priority) == 'high' ? 'selected' : '' }}>عالية</option>
                                        </select>
                                        @error('priority')
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
                                                <option value="{{ $department }}" {{ old('department', $clientTicket->department) == $department ? 'selected' : '' }}>
                                                    {{ $department }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('department')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Assigned Users -->
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label><strong>الموظفين المعينين</strong></label>
                                        <div style="background: #f8f9fa; padding: 1rem; border-radius: 10px;">
                                            <small class="text-muted mb-2 d-block">حدد الموظفين المطلوب تعيينهم</small>
                                            <div class="row">
                                                @foreach($employees as $employee)
                                                    <div class="col-md-6 mb-2">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox"
                                                                   name="assigned_users[]" value="{{ $employee->id }}"
                                                                   id="edit_employee_{{ $employee->id }}"
                                                                   {{ $clientTicket->activeAssignments->pluck('user_id')->contains($employee->id) ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="edit_employee_{{ $employee->id }}">
                                                                {{ $employee->name }}
                                                            </label>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Description -->
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="description">وصف المشكلة أو الطلب *</label>
                                        <textarea name="description" id="description" rows="5"
                                                  class="form-control @error('description') is-invalid @enderror"
                                                  placeholder="اكتب وصفاً مفصلاً للمشكلة أو الطلب..." required>{{ old('description', $clientTicket->description) }}</textarea>
                                        @error('description')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Resolution Notes (if resolved) -->
                                @if($clientTicket->status == 'resolved' || $clientTicket->resolution_notes)
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="resolution_notes">ملاحظات الحل</label>
                                            <textarea name="resolution_notes" id="resolution_notes" rows="4"
                                                      class="form-control @error('resolution_notes') is-invalid @enderror"
                                                      placeholder="اكتب ملاحظات الحل...">{{ old('resolution_notes', $clientTicket->resolution_notes) }}</textarea>
                                            @error('resolution_notes')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <div class="action-buttons" style="margin-top: 2rem; text-align: center;">
                                <button type="submit" class="arkan-btn arkan-btn-primary">
                                    <i class="fas fa-save"></i> حفظ التعديلات
                                </button>
                                <a href="{{ route('client-tickets.show', $clientTicket) }}" class="arkan-btn btn-info">
                                    <i class="fas fa-eye"></i> عرض التذكرة
                                </a>
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
    // Auto-update status badge color
    $('#status').change(function() {
        const status = $(this).val();
        const $select = $(this);

        $select.removeClass('border-success border-info border-warning border-danger');

        switch(status) {
            case 'open':
                $select.addClass('border-danger');
                break;
            case 'assigned':
                $select.addClass('border-info');
                break;
            case 'resolved':
                $select.addClass('border-success');
                break;
            case 'closed':
                $select.addClass('border-secondary');
                break;
        }
    });

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

    // Trigger initial changes
    $('#status').trigger('change');
    $('#priority').trigger('change');
});
</script>
@endpush
