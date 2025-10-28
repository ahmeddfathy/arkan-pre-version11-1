@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/service-data-management.css') }}?v={{ time() }}">
@endpush

@section('content')
<div class="full-width-content">
    <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-1">
                    <i class="fas fa-cog me-2"></i>
                    {{ $service->name }}
                </h2>
                <p class="mb-0" style="opacity: 0.9;">
                    <i class="fas fa-building me-1"></i>
                    {{ $service->department }}
                </p>
            </div>
            <a href="{{ route('service-data.index') }}" class="btn btn-light btn-action">
                <i class="fas fa-arrow-right me-2"></i> عودة للخدمات
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- إحصائيات -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stats-card bg-info bg-opacity-10" style="border: 2px solid #17a2b8;">
                <div class="text-center">
                    <i class="fas fa-file-alt text-info"></i>
                    <h3 class="text-info">{{ $statistics['total'] }}</h3>
                    <h6 class="text-muted">إجمالي الحقول</h6>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stats-card bg-success bg-opacity-10" style="border: 2px solid #28a745;">
                <div class="text-center">
                    <i class="fas fa-check-circle text-success"></i>
                    <h3 class="text-success">{{ $statistics['active'] }}</h3>
                    <h6 class="text-muted">حقول نشطة</h6>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stats-card bg-danger bg-opacity-10" style="border: 2px solid #dc3545;">
                <div class="text-center">
                    <i class="fas fa-exclamation-triangle text-danger"></i>
                    <h3 class="text-danger">{{ $statistics['required'] }}</h3>
                    <h6 class="text-muted">حقول إلزامية</h6>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stats-card bg-primary bg-opacity-10" style="border: 2px solid #667eea;">
                <div class="text-center">
                    <i class="fas fa-list text-primary"></i>
                    <h3 class="text-primary">{{ $statistics['by_type']['dropdown'] }}</h3>
                    <h6 class="text-muted">قوائم منسدلة</h6>
                </div>
            </div>
        </div>
    </div>

    <!-- الجدول -->
    <div class="card-modern">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0">
                    <i class="fas fa-table me-2 text-primary"></i>
                    حقول البيانات
                </h5>
                <div class="d-flex gap-2">
                    @if($otherServices->count() > 0)
                        <button type="button" class="btn btn-secondary btn-action" data-bs-toggle="modal" data-bs-target="#copyModal">
                            <i class="fas fa-copy me-2"></i> نسخ من خدمة
                        </button>
                    @endif

                    <a href="{{ route('service-data.create-field', $service->id) }}" class="btn btn-primary btn-action">
                        <i class="fas fa-plus me-2"></i> إضافة حقل جديد
                    </a>
                </div>
            </div>

            @if($fields->count() > 0)
                <div class="table-responsive">
                    <table class="table table-modern">
                        <thead>
                            <tr>
                                <th>الترتيب</th>
                                <th>الاسم</th>
                                <th>التسمية</th>
                                <th>النوع</th>
                                <th>الخيارات</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($fields as $field)
                                <tr>
                                    <td class="text-center">
                                        <span class="badge badge-modern bg-secondary" style="font-size: 1rem; min-width: 40px;">
                                            {{ $field->order }}
                                        </span>
                                    </td>
                                    <td>
                                        <strong style="color: #2d3748;">{{ $field->field_name }}</strong>
                                    </td>
                                    <td>
                                        <div style="display: inline-block; background: #f8f9fa; padding: 0.5rem 1rem; border-radius: 8px;">
                                            {{ $field->field_label }}
                                            @if($field->is_required)
                                                <span class="badge bg-danger ms-1">إلزامي</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @if($field->field_type === 'boolean')
                                            <span class="badge badge-modern bg-info">
                                                <i class="fas fa-toggle-on me-1"></i>
                                                نعم/لا
                                            </span>
                                        @elseif($field->field_type === 'date')
                                            <span class="badge badge-modern bg-success">
                                                <i class="fas fa-calendar-alt me-1"></i>
                                                تاريخ
                                            </span>
                                        @elseif($field->field_type === 'text')
                                            <span class="badge badge-modern bg-warning text-dark">
                                                <i class="fas fa-font me-1"></i>
                                                نص
                                            </span>
                                        @else
                                            <span class="badge badge-modern bg-primary">
                                                <i class="fas fa-list me-1"></i>
                                                قائمة منسدلة
                                            </span>
                                        @endif
                                    </td>
                                    <td class="small">
                                        @if($field->field_type === 'dropdown' && $field->field_options)
                                            <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;" title="{{ implode(', ', $field->field_options) }}">
                                                {{ implode(', ', $field->field_options) }}
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($field->is_active)
                                            <span class="badge badge-modern bg-success">
                                                <i class="fas fa-check-circle"></i>
                                                نشط
                                            </span>
                                        @else
                                            <span class="badge badge-modern bg-secondary">
                                                <i class="fas fa-times-circle"></i>
                                                غير نشط
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            <a href="{{ route('service-data.edit-field', $field->id) }}"
                                               class="btn btn-sm btn-outline-primary"
                                               style="border-radius: 8px; border-width: 2px;"
                                               title="تعديل">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('service-data.delete-field', $field->id) }}"
                                                  method="POST"
                                                  onsubmit="return confirm('هل أنت متأكد من حذف هذا الحقل؟')"
                                                  class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="btn btn-sm btn-outline-danger"
                                                        style="border-radius: 8px; border-width: 2px;"
                                                        title="حذف">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-state">
                    <i class="fas fa-file-alt"></i>
                    <h5>لا توجد حقول مخصصة</h5>
                    <p>ابدأ بإضافة حقل جديد لهذه الخدمة</p>
                    <a href="{{ route('service-data.create-field', $service->id) }}" class="btn btn-primary btn-action mt-3">
                        <i class="fas fa-plus me-2"></i> إضافة حقل جديد
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Copy Modal -->
<div class="modal fade" id="copyModal" tabindex="-1" aria-labelledby="copyModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('service-data.copy-fields', $service->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="copyModalLabel">نسخ الحقول من خدمة أخرى</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="source_service_id" class="form-label">اختر الخدمة</label>
                        <select name="source_service_id" id="source_service_id" class="form-select" required>
                            <option value="">-- اختر خدمة --</option>
                            @foreach($otherServices as $otherService)
                                <option value="{{ $otherService->id }}">
                                    {{ $otherService->name }} ({{ $otherService->dataFields()->count() }} حقل)
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        سيتم نسخ جميع الحقول من الخدمة المختارة إلى هذه الخدمة
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-copy me-2"></i> نسخ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
