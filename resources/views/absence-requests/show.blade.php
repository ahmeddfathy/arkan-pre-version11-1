@extends('layouts.app')

@push('styles')
<link href="{{ asset('css/absence-management.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">
                    <i class="fas fa-calendar-times text-primary me-2"></i>
                    تفاصيل طلب الغياب
                </h2>
                <a href="{{ route('absence-requests.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>العودة
                </a>
            </div>

            <!-- Request Details Card -->
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        معلومات الطلب
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Employee Info -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">الموظف:</label>
                            <div class="d-flex align-items-center">
                                @if($absenceRequest->user->profile_photo_path)
                                    <img src="{{ asset($absenceRequest->user->profile_photo_path) }}"
                                         alt="صورة الموظف" class="rounded-circle me-2"
                                         style="width: 40px; height: 40px; object-fit: cover;">
                                @else
                                    <img src="{{ asset('avatars/man.gif') }}"
                                         alt="صورة افتراضية" class="rounded-circle me-2"
                                         style="width: 40px; height: 40px; object-fit: cover;">
                                @endif
                                <div>
                                    <div class="fw-bold">{{ $absenceRequest->user->name }}</div>
                                    <small class="text-muted">{{ $absenceRequest->user->employee_id }}</small>
                                </div>
                            </div>
                        </div>

                        <!-- Absence Date -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">تاريخ الغياب:</label>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-calendar-alt text-primary me-2"></i>
                                <span class="fs-6">{{ \Carbon\Carbon::parse($absenceRequest->absence_date)->format('Y-m-d') }}</span>
                            </div>
                        </div>

                        <!-- Reason -->
                        <div class="col-12 mb-3">
                            <label class="form-label fw-bold">السبب:</label>
                            <div class="bg-light p-3 rounded">
                                {{ $absenceRequest->reason }}
                            </div>
                        </div>

                        <!-- Request Status -->
                        <div class="col-12 mb-3">
                            <label class="form-label fw-bold">الحالة العامة:</label>
                            <div>
                                <span class="badge fs-6 bg-{{
                                    $absenceRequest->status === 'approved' ? 'success' :
                                    ($absenceRequest->status === 'rejected' ? 'danger' : 'warning')
                                }}">
                                    {{
                                        $absenceRequest->status === 'approved' ? 'موافق عليه' :
                                        ($absenceRequest->status === 'rejected' ? 'مرفوض' : 'قيد الانتظار')
                                    }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Manager Response -->
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-user-tie me-2"></i>
                        رد المدير
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">الحالة:</label>
                            <div>
                                <span class="badge fs-6 bg-{{
                                    $absenceRequest->manager_status === 'approved' ? 'success' :
                                    ($absenceRequest->manager_status === 'rejected' ? 'danger' : 'warning')
                                }}">
                                    {{
                                        $absenceRequest->manager_status === 'approved' ? 'موافق عليه' :
                                        ($absenceRequest->manager_status === 'rejected' ? 'مرفوض' : 'قيد الانتظار')
                                    }}
                                </span>
                            </div>
                        </div>
                        @if($absenceRequest->manager_rejection_reason)
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">سبب الرفض:</label>
                            <div class="text-danger">{{ $absenceRequest->manager_rejection_reason }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- HR Response -->
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-users me-2"></i>
                        رد الموارد البشرية
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">الحالة:</label>
                            <div>
                                <span class="badge fs-6 bg-{{
                                    $absenceRequest->hr_status === 'approved' ? 'success' :
                                    ($absenceRequest->hr_status === 'rejected' ? 'danger' : 'warning')
                                }}">
                                    {{
                                        $absenceRequest->hr_status === 'approved' ? 'موافق عليه' :
                                        ($absenceRequest->hr_status === 'rejected' ? 'مرفوض' : 'قيد الانتظار')
                                    }}
                                </span>
                            </div>
                        </div>
                        @if($absenceRequest->hr_rejection_reason)
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">سبب الرفض:</label>
                            <div class="text-danger">{{ $absenceRequest->hr_rejection_reason }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Actions -->
            @if($canRespondAsManager || $canModifyManagerResponse || $canRespondAsHR || $canModifyHRResponse || $canUpdateRequest || $canDelete)
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-cogs me-2"></i>
                        الإجراءات المتاحة
                    </h5>
                </div>

                {{-- رسالة توضيحية --}}
                @if(Auth::user()->id === $absenceRequest->user_id && (!$canUpdateRequest || !$canDelete) && ($absenceRequest->manager_status !== 'pending' || $absenceRequest->hr_status !== 'pending'))
                <div class="alert alert-info mb-0 border-0 border-radius-0">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>ملاحظة:</strong> لا يمكن تعديل أو حذف الطلب بعد صدور رد من المدير أو الموارد البشرية
                </div>
                @endif
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        {{-- رد المدير الأولي --}}
                        @if($canRespondAsManager && $absenceRequest->manager_status === 'pending')
                        <button class="btn btn-primary" onclick="openResponseModal('manager', {{ $absenceRequest->id }})">
                            <i class="fas fa-user-tie me-2"></i>رد المدير
                        </button>
                        @endif

                        {{-- تعديل رد المدير --}}
                        @if($canModifyManagerResponse)
                        <button class="btn btn-info" onclick="openModifyResponseModal('manager', {{ $absenceRequest->id }}, '{{ $absenceRequest->manager_status }}', '{{ addslashes($absenceRequest->manager_rejection_reason ?? '') }}')">
                            <i class="fas fa-edit me-2"></i>تعديل رد المدير
                        </button>
                        @endif

                        {{-- رد HR الأولي --}}
                        @if($canRespondAsHR && $absenceRequest->hr_status === 'pending')
                        <button class="btn btn-success" onclick="openResponseModal('hr', {{ $absenceRequest->id }})">
                            <i class="fas fa-users me-2"></i>رد HR
                        </button>
                        @endif

                        {{-- تعديل رد HR --}}
                        @if($canModifyHRResponse)
                        <button class="btn btn-success" onclick="openModifyResponseModal('hr', {{ $absenceRequest->id }}, '{{ $absenceRequest->hr_status }}', '{{ addslashes($absenceRequest->hr_rejection_reason ?? '') }}')">
                            <i class="fas fa-edit me-2"></i>تعديل رد HR
                        </button>
                        @endif

                        {{-- تعديل الطلب (صاحب الطلب فقط وقبل أي رد) --}}
                        @if($canUpdateRequest)
                        <button class="btn btn-warning" onclick="openEditModal({{ $absenceRequest->id }}, '{{ $absenceRequest->absence_date }}', '{{ addslashes($absenceRequest->reason) }}')">
                            <i class="fas fa-edit me-2"></i>تعديل الطلب
                        </button>
                        @endif

                        {{-- حذف الطلب (صاحب الطلب فقط) --}}
                        @if($canDelete)
                        <button class="btn btn-danger" onclick="confirmDelete({{ $absenceRequest->id }})">
                            <i class="fas fa-trash me-2"></i>حذف الطلب
                        </button>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            <!-- Timeline -->
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i>
                        التسلسل الزمني
                    </h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-marker bg-primary"></div>
                            <div class="timeline-content">
                                <h6 class="timeline-title">تم إنشاء الطلب</h6>
                                <p class="timeline-text text-muted">
                                    {{ $absenceRequest->created_at->format('Y-m-d H:i') }}
                                </p>
                            </div>
                        </div>

                        @if($absenceRequest->manager_status !== 'pending')
                        <div class="timeline-item">
                            <div class="timeline-marker bg-{{
                                $absenceRequest->manager_status === 'approved' ? 'success' : 'danger'
                            }}"></div>
                            <div class="timeline-content">
                                <h6 class="timeline-title">
                                    {{ $absenceRequest->manager_status === 'approved' ? 'موافقة المدير' : 'رفض المدير' }}
                                </h6>
                                <p class="timeline-text text-muted">
                                    {{ $absenceRequest->updated_at->format('Y-m-d H:i') }}
                                </p>
                            </div>
                        </div>
                        @endif

                        @if($absenceRequest->hr_status !== 'pending')
                        <div class="timeline-item">
                            <div class="timeline-marker bg-{{
                                $absenceRequest->hr_status === 'approved' ? 'success' : 'danger'
                            }}"></div>
                            <div class="timeline-content">
                                <h6 class="timeline-title">
                                    {{ $absenceRequest->hr_status === 'approved' ? 'موافقة HR' : 'رفض HR' }}
                                </h6>
                                <p class="timeline-text text-muted">
                                    {{ $absenceRequest->updated_at->format('Y-m-d H:i') }}
                                </p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
@include('absence-requests.components._modals')

@endsection

@push('scripts')
<script src="{{ asset('js/absence-requests/common.js') }}?t={{ time() }}"></script>
<script src="{{ asset('js/absence-requests/modals.js') }}?t={{ time() }}"></script>

<script>
function confirmDelete(requestId) {
    if (confirm('هل أنت متأكد من حذف هذا الطلب؟')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/absence-requests/${requestId}`;

        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'DELETE';

        const tokenInput = document.createElement('input');
        tokenInput.type = 'hidden';
        tokenInput.name = '_token';
        tokenInput.value = '{{ csrf_token() }}';

        form.appendChild(methodInput);
        form.appendChild(tokenInput);
        document.body.appendChild(form);
        form.submit();
    }
}

function openEditModal(requestId, absenceDate, reason) {
    // تحديد action للـ form
    document.getElementById('editAbsenceForm').action = `/absence-requests/${requestId}`;

    // ملء البيانات
    document.getElementById('edit_absence_date').value = absenceDate;
    document.getElementById('edit_reason').value = reason;

    // فتح الـ modal
    new bootstrap.Modal(document.getElementById('editAbsenceModal')).show();
}

function openResponseModal(responseType, requestId) {
    // تحديد نوع الرد
    document.getElementById('response_type').value = responseType;

    // تحديد action للـ form
    document.getElementById('respondForm').action = `/absence-requests/${requestId}/status`;

    // تحديد عنوان الـ modal
    const title = responseType === 'manager' ? 'رد المدير' : 'رد الموارد البشرية';
    document.getElementById('responseTitle').textContent = title;

    // إعادة تعيين الـ form
    document.getElementById('response_status').value = 'approved';
    document.getElementById('response_reason').value = '';
    document.getElementById('response_reason_container').style.display = 'none';

    // فتح الـ modal
    new bootstrap.Modal(document.getElementById('respondModal')).show();
}

function openModifyResponseModal(responseType, requestId, currentStatus, currentReason) {
    // تحديد نوع الرد
    document.getElementById('modify_response_type').value = responseType;

    // تحديد action للـ form
    document.getElementById('modifyResponseForm').action = `/absence-requests/${requestId}/modify`;

    // تحديد عنوان الـ modal
    const title = responseType === 'manager' ? 'تعديل رد المدير' : 'تعديل رد الموارد البشرية';
    document.querySelector('#modifyResponseModal .modal-title').textContent = title;

    // ملء البيانات الحالية
    document.getElementById('modify_status').value = currentStatus;
    document.getElementById('modify_reason').value = currentReason || '';

    // إظهار/إخفاء حقل سبب الرفض
    const reasonContainer = document.getElementById('modify_reason_container');
    if (currentStatus === 'rejected') {
        reasonContainer.style.display = 'block';
        document.getElementById('modify_reason').required = true;
    } else {
        reasonContainer.style.display = 'none';
        document.getElementById('modify_reason').required = false;
    }

    // فتح الـ modal
    new bootstrap.Modal(document.getElementById('modifyResponseModal')).show();
}

// إظهار/إخفاء حقل سبب الرفض في modal الرد الأولي
document.getElementById('response_status').addEventListener('change', function() {
    const reasonContainer = document.getElementById('response_reason_container');
    if (this.value === 'rejected') {
        reasonContainer.style.display = 'block';
        document.getElementById('response_reason').required = true;
    } else {
        reasonContainer.style.display = 'none';
        document.getElementById('response_reason').required = false;
    }
});

// إظهار/إخفاء حقل سبب الرفض في modal التعديل
document.getElementById('modify_status').addEventListener('change', function() {
    const reasonContainer = document.getElementById('modify_reason_container');
    if (this.value === 'rejected') {
        reasonContainer.style.display = 'block';
        document.getElementById('modify_reason').required = true;
    } else {
        reasonContainer.style.display = 'none';
        document.getElementById('modify_reason').required = false;
    }
});
</script>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline:before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #dee2e6;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -22px;
    top: 0;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 0 0 3px #dee2e6;
}

.timeline-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.timeline-title {
    margin-bottom: 5px;
    color: #495057;
}

.timeline-text {
    margin-bottom: 0;
    font-size: 0.9em;
}
</style>
@endpush
