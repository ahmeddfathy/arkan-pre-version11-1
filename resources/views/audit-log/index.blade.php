@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/audit-log.css') }}">
@endpush

@section('content')
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="text-dark fw-bold mb-0">
                    <i class="fas fa-history text-primary me-2"></i>سجل التغييرات
                </h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">الرئيسية</a></li>
                        <li class="breadcrumb-item active" aria-current="page">سجل التغييرات</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card audit-filter-card">
                <div class="card-header">
                    <h6 class="text-lg fw-bold mb-0">
                        <i class="fas fa-filter me-2"></i>تصفية النتائج
                    </h6>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('audit-log.index') }}" class="row g-4">
                        <!-- Date Filter -->
                        <div class="col-md-3">
                            <label class="filter-label">تاريخ محدد</label>
                            <div class="date-filter">
                                <input type="date" name="date" class="form-control shadow-sm" value="{{ request('date') }}">
                            </div>
                        </div>

                        <!-- Month and Year Filter -->
                        <div class="col-md-2">
                            <label class="filter-label">الشهر</label>
                            <select name="month" class="form-select shadow-sm">
                                <option value="">اختر الشهر</option>
                                @for($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}" {{ request('month') == $i ? 'selected' : '' }}>
                                    {{ date('F', mktime(0, 0, 0, $i, 1)) }}
                                    </option>
                                    @endfor
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="filter-label">السنة</label>
                            <select name="year" class="form-select shadow-sm">
                                <option value="">اختر السنة</option>
                                @for($i = $currentYear - 2; $i <= $currentYear; $i++)
                                    <option value="{{ $i }}" {{ request('year') == $i ? 'selected' : '' }}>
                                    {{ $i }}
                                    </option>
                                    @endfor
                            </select>
                        </div>

                        <!-- Employee Filter -->
                        <div class="col-md-3">
                            <label class="filter-label">الموظف</label>
                            <select name="user_id" class="form-select shadow-sm">
                                <option value="">اختر الموظف</option>
                                @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Action Type Filter -->
                        <div class="col-md-2">
                            <label class="filter-label">نوع الإجراء</label>
                            <select name="action" class="form-select shadow-sm">
                                <option value="">الكل</option>
                                <option value="created" {{ request('action') == 'created' ? 'selected' : '' }}>إنشاء</option>
                                <option value="updated" {{ request('action') == 'updated' ? 'selected' : '' }}>تحديث</option>
                                <option value="deleted" {{ request('action') == 'deleted' ? 'selected' : '' }}>حذف</option>
                            </select>
                        </div>

                        <!-- Request Type Filter -->
                        <div class="col-md-3">
                            <label class="filter-label">نوع الطلب/السجل</label>
                            <select name="request_type" class="form-select shadow-sm">
                                <option value="">الكل</option>
                                <optgroup label="الطلبات">
                                    <option value="{{ App\Models\AbsenceRequest::class }}" {{ request('request_type') == App\Models\AbsenceRequest::class ? 'selected' : '' }}>طلب غياب</option>
                                    <option value="{{ App\Models\PermissionRequest::class }}" {{ request('request_type') == App\Models\PermissionRequest::class ? 'selected' : '' }}>طلب إذن</option>
                                    <option value="{{ App\Models\OverTimeRequests::class }}" {{ request('request_type') == App\Models\OverTimeRequests::class ? 'selected' : '' }}>طلب وقت إضافي</option>
                                </optgroup>
                                <optgroup label="المشاريع والمهام">
                                    <option value="{{ App\Models\Project::class }}" {{ request('request_type') == App\Models\Project::class ? 'selected' : '' }}>مشروع</option>
                                    <option value="{{ App\Models\ProjectAttachment::class }}" {{ request('request_type') == App\Models\ProjectAttachment::class ? 'selected' : '' }}>مرفق مشروع</option>
                                    <option value="{{ App\Models\Task::class }}" {{ request('request_type') == App\Models\Task::class ? 'selected' : '' }}>مهمة</option>
                                    <option value="{{ App\Models\TaskUser::class }}" {{ request('request_type') == App\Models\TaskUser::class ? 'selected' : '' }}>تخصيص مهمة</option>
                                    <option value="{{ App\Models\TemplateTask::class }}" {{ request('request_type') == App\Models\TemplateTask::class ? 'selected' : '' }}>قالب مهمة</option>
                                    <option value="{{ App\Models\TemplateTaskUser::class }}" {{ request('request_type') == App\Models\TemplateTaskUser::class ? 'selected' : '' }}>تخصيص قالب مهمة</option>
                                </optgroup>
                                <optgroup label="خدمة العملاء">
                                    <option value="{{ App\Models\TicketAssignment::class }}" {{ request('request_type') == App\Models\TicketAssignment::class ? 'selected' : '' }}>تخصيص تذكرة</option>
                                </optgroup>
                            </select>
                        </div>

                        <!-- Submit and Reset Buttons -->
                        <div class="col-md-3">
                            <label class="filter-label">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-filter">
                                    <i class="fas fa-search me-2"></i>تطبيق الفلتر
                                </button>
                                <a href="{{ route('audit-log.index') }}" class="btn btn-secondary btn-filter">
                                    <i class="fas fa-redo me-2"></i>إعادة تعيين
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Stats -->
    @if(count($audits) > 0)
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-white shadow-sm border-0 h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="background: rgba(71, 118, 230, 0.1); width: 50px; height: 50px;">
                        <i class="fas fa-history text-primary" style="font-size: 1.2rem;"></i>
                    </div>
                    <div>
                        <span class="d-block text-sm text-muted">إجمالي السجلات</span>
                        <h5 class="fw-bold mb-0">{{ count($audits) }}</h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-white shadow-sm border-0 h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="background: rgba(12, 206, 107, 0.1); width: 50px; height: 50px;">
                        <i class="fas fa-plus-circle" style="color: var(--success-color); font-size: 1.2rem;"></i>
                    </div>
                    <div>
                        <span class="d-block text-sm text-muted">سجلات الإنشاء</span>
                        <h5 class="fw-bold mb-0">{{ $audits->where('action', 'إنشاء')->count() }}</h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-white shadow-sm border-0 h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="background: rgba(45, 156, 219, 0.1); width: 50px; height: 50px;">
                        <i class="fas fa-edit" style="color: var(--info-color); font-size: 1.2rem;"></i>
                    </div>
                    <div>
                        <span class="d-block text-sm text-muted">سجلات التحديث</span>
                        <h5 class="fw-bold mb-0">{{ $audits->where('action', 'تحديث')->count() }}</h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-white shadow-sm border-0 h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="background: rgba(255, 94, 98, 0.1); width: 50px; height: 50px;">
                        <i class="fas fa-trash-alt" style="color: var(--danger-color); font-size: 1.2rem;"></i>
                    </div>
                    <div>
                        <span class="d-block text-sm text-muted">سجلات الحذف</span>
                        <h5 class="fw-bold mb-0">{{ $audits->where('action', 'حذف')->count() }}</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="row">
        <div class="col-12">
            @if(request('model_id') && request('request_type'))
            <div class="alert alert-info mb-4">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    @if(request('request_type') == App\Models\AbsenceRequest::class)
                    عرض سجل التغييرات لطلب الغياب رقم <strong>#{{ request('model_id') }}</strong>
                    @elseif(request('request_type') == App\Models\PermissionRequest::class)
                    عرض سجل التغييرات لطلب الإذن رقم <strong>#{{ request('model_id') }}</strong>
                    @elseif(request('request_type') == App\Models\OverTimeRequests::class)
                    عرض سجل التغييرات لطلب الوقت الإضافي رقم <strong>#{{ request('model_id') }}</strong>
                    @elseif(request('request_type') == App\Models\Project::class)
                    عرض سجل التغييرات للمشروع رقم <strong>#{{ request('model_id') }}</strong>
                    @elseif(request('request_type') == App\Models\ProjectAttachment::class)
                    عرض سجل التغييرات لمرفق المشروع رقم <strong>#{{ request('model_id') }}</strong>
                    @elseif(request('request_type') == App\Models\Task::class)
                    عرض سجل التغييرات للمهمة رقم <strong>#{{ request('model_id') }}</strong>
                    @elseif(request('request_type') == App\Models\TaskUser::class)
                    عرض سجل التغييرات لتخصيص المهمة رقم <strong>#{{ request('model_id') }}</strong>
                    @elseif(request('request_type') == App\Models\TemplateTask::class)
                    عرض سجل التغييرات لقالب المهمة رقم <strong>#{{ request('model_id') }}</strong>
                    @elseif(request('request_type') == App\Models\TemplateTaskUser::class)
                    عرض سجل التغييرات لتخصيص قالب المهمة رقم <strong>#{{ request('model_id') }}</strong>
                    @elseif(request('request_type') == App\Models\TicketAssignment::class)
                    عرض سجل التغييرات لتخصيص التذكرة رقم <strong>#{{ request('model_id') }}</strong>
                    @endif
                </h5>
            </div>
            @endif
            @if(request('model_id') && request('request_type') == App\Models\AbsenceRequest::class)
            <div class="mb-3">
                <a href="{{ route('absence-requests.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>العودة إلى طلبات الغياب
                </a>
            </div>
            @endif
            @if(request('model_id') && request('request_type') == App\Models\PermissionRequest::class)
            <div class="mb-3">
                <a href="{{ route('permission-requests.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>العودة إلى طلبات الإذن
                </a>
            </div>
            @endif
            @if(request('model_id') && request('request_type') == App\Models\OverTimeRequests::class)
            <div class="mb-3">
                <a href="{{ route('overtime-requests.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>العودة إلى طلبات الوقت الإضافي
                </a>
            </div>
            @endif
            <div class="card audit-table-card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="text-lg fw-bold mb-0">
                            <i class="fas fa-history me-2"></i>سجل التغييرات
                        </h6>
                        <span class="badge bg-light text-primary">{{ count($audits) }} سجل</span>
                    </div>
                </div>
                <div class="card-body px-0 pt-0 pb-2">
                    @if(count($audits) > 0)
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th class="text-center">التاريخ والوقت</th>
                                    <th class="text-center">صاحب الطلب</th>
                                    <th class="text-center">من قام بالإجراء</th>
                                    <th class="text-center">نوع الطلب</th>
                                    <th class="text-center">الإجراء</th>
                                    <th class="text-center">وصف الإجراء</th>
                                    <th class="text-center">عنوان IP</th>
                                    <th class="text-center">التفاصيل</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($audits as $audit)
                                <tr>
                                    <td class="text-center">
                                        <div class="d-flex flex-column">
                                            <span class="text-dark fw-bold mb-1">{{ \Carbon\Carbon::parse($audit['created_at'])->format('Y-m-d') }}</span>
                                            <small class="text-muted">{{ \Carbon\Carbon::parse($audit['created_at'])->format('h:i A') }}</small>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="text-dark fw-medium">{{ $audit['request_owner'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="text-dark fw-medium">{{ $audit['user'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="text-dark fw-medium">{{ $audit['model_type'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="audit-badge status-badge-{{ strtolower($audit['action']) }}">
                                            {{ $audit['action'] }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="text-dark fw-medium">{{ $audit['action_description'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="text-dark fw-medium">{{ $audit['ip_address'] ?? 'غير متوفر' }}</span>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-details" data-bs-toggle="modal" data-bs-target="#changesModal{{ $audit['id'] }}">
                                            <i class="fas fa-eye me-1"></i>عرض التفاصيل
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-5">
                        <img src="{{ asset('img/empty-data.svg') }}" alt="لا توجد بيانات" style="max-width: 200px; opacity: 0.5" class="mb-3">
                        <h5 class="text-muted fw-normal">لا توجد تغييرات لعرضها</h5>
                        <p class="text-muted">جرب تغيير معايير البحث أو العودة لاحقاً</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals for Changes -->
@foreach($audits as $audit)
<div class="modal fade" id="changesModal{{ $audit['id'] }}" tabindex="-1" aria-labelledby="changesModalLabel{{ $audit['id'] }}" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="changesModalLabel{{ $audit['id'] }}">
                    <i class="fas fa-info-circle me-2"></i>تفاصيل التغييرات
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-4 p-4 bg-light rounded">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-3">
                                <div class="icon-circle bg-primary-light me-3">
                                    <i class="fas fa-user text-primary"></i>
                                </div>
                                <div>
                                    <span class="d-block text-xs text-muted">صاحب الطلب</span>
                                    <span class="text-dark fw-bold">{{ $audit['request_owner'] }}</span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <div class="icon-circle bg-info-light me-3">
                                    <i class="fas fa-user-edit text-info"></i>
                                </div>
                                <div>
                                    <span class="d-block text-xs text-muted">قام بالإجراء</span>
                                    <span class="text-dark fw-bold">{{ $audit['user'] }}</span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <div class="icon-circle bg-success-light me-3">
                                    <i class="fas fa-file-alt text-success"></i>
                                </div>
                                <div>
                                    <span class="d-block text-xs text-muted">نوع الطلب</span>
                                    <span class="text-dark fw-bold">{{ $audit['model_type'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-3">
                                <div class="icon-circle bg-warning-light me-3">
                                    <i class="fas fa-calendar text-warning"></i>
                                </div>
                                <div>
                                    <span class="d-block text-xs text-muted">التاريخ والوقت</span>
                                    <span class="text-dark fw-bold">{{ $audit['created_at'] }}</span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <div class="icon-circle bg-dark-light me-3">
                                    <i class="fas fa-network-wired text-dark"></i>
                                </div>
                                <div>
                                    <span class="d-block text-xs text-muted">عنوان IP</span>
                                    <span class="text-dark fw-bold">{{ $audit['ip_address'] ?? 'غير متوفر' }}</span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <div class="icon-circle bg-secondary-light me-3">
                                    <i class="fas fa-link text-secondary"></i>
                                </div>
                                <div>
                                    <span class="d-block text-xs text-muted">الرابط</span>
                                    <span class="text-dark fw-bold">{{ $audit['url'] ?? 'غير متوفر' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold border-bottom pb-2 mb-3">تفاصيل التغييرات</h6>

                @if(count($audit['changes']) > 0)
                <div class="table-responsive">
                    <table class="table change-table">
                        <thead>
                            <tr>
                                <th class="text-center">الحقل</th>
                                <th class="text-center">القيمة القديمة</th>
                                <th class="text-center">القيمة الجديدة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($audit['changes'] as $change)
                            <tr>
                                <td class="text-center fw-bold">{{ $change['field'] }}</td>
                                <td class="text-center {{ empty($change['old']) ? 'text-muted' : 'text-danger' }}">{{ $change['old'] ?? '-' }}</td>
                                <td class="text-center {{ empty($change['new']) ? 'text-muted' : 'text-success' }}">{{ $change['new'] ?? '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="alert alert-light border">
                    <i class="fas fa-info-circle me-2"></i> لا توجد تغييرات مسجلة
                </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>
@endforeach
@endsection

@push('scripts')
<script>
    // Add Font Awesome if not already included
    if (!document.querySelector('link[href*="font-awesome"]')) {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css';
        document.head.appendChild(link);
    }

    // Add some more styling for modal icons
    document.head.insertAdjacentHTML('beforeend', `
        <style>
            .icon-circle {
                width: 36px;
                height: 36px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .bg-primary-light { background-color: rgba(71, 118, 230, 0.1); }
            .bg-info-light { background-color: rgba(45, 156, 219, 0.1); }
            .bg-success-light { background-color: rgba(12, 206, 107, 0.1); }
            .bg-warning-light { background-color: rgba(255, 170, 0, 0.1); }
            .bg-danger-light { background-color: rgba(255, 94, 98, 0.1); }
            .bg-dark-light { background-color: rgba(52, 58, 64, 0.1); }
            .bg-secondary-light { background-color: rgba(108, 117, 125, 0.1); }
        </style>
    `);
</script>
@endpush
