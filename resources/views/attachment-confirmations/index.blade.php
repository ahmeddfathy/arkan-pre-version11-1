@extends('layouts.app')

@section('content')
@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.20/dist/sweetalert2.min.css">
<link rel="stylesheet" href="{{ asset('css/attachment-confirmations.css') }}">
<link rel="stylesheet" href="{{ asset('css/attachment-confirmations-index.css') }}?v={{ time() }}">
@endpush
<div class="full-width-content">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card card-modern">
                <div class="card-header card-header-modern text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-check-circle me-2"></i>
                            طلبات تأكيد المرفقات
                        </h4>
                        <a href="{{ route('attachment-confirmations.my-requests') }}" class="btn btn-light btn-sm" style="border-radius: 8px; font-weight: 600;">
                            <i class="fas fa-list me-1"></i>
                            طلباتي المرسلة
                        </a>
                    </div>
                </div>
                <div class="card-body" style="padding: 2rem;">
                    <!-- الإحصائيات -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="stats-card bg-warning bg-opacity-10" style="border-color: #ffc107;">
                                <div class="text-center">
                                    <i class="fas fa-clock text-warning mb-2" style="font-size: 2rem;"></i>
                                    <h3 class="text-warning mb-0">{{ $statistics['pending_count'] }}</h3>
                                    <small class="text-muted">قيد الانتظار</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stats-card bg-success bg-opacity-10" style="border-color: #28a745;">
                                <div class="text-center">
                                    <i class="fas fa-check-circle text-success mb-2" style="font-size: 2rem;"></i>
                                    <h3 class="text-success mb-0">{{ $statistics['confirmed_count'] }}</h3>
                                    <small class="text-muted">مؤكدة</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stats-card bg-danger bg-opacity-10" style="border-color: #dc3545;">
                                <div class="text-center">
                                    <i class="fas fa-times-circle text-danger mb-2" style="font-size: 2rem;"></i>
                                    <h3 class="text-danger mb-0">{{ $statistics['rejected_count'] }}</h3>
                                    <small class="text-muted">مرفوضة</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stats-card bg-info bg-opacity-10" style="border-color: #17a2b8;">
                                <div class="text-center">
                                    <i class="fas fa-list-alt text-info mb-2" style="font-size: 2rem;"></i>
                                    <h3 class="text-info mb-0">{{ $statistics['total_count'] }}</h3>
                                    <small class="text-muted">الإجمالي</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- الفلاتر -->
                    <form method="GET" action="{{ route('attachment-confirmations.index') }}" class="filter-section">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-tasks text-primary me-1"></i>
                                    الحالة
                                </label>
                                <select name="status" class="form-select">
                                    <option value="">الكل</option>
                                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>⏳ قيد الانتظار</option>
                                    <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>✅ مؤكدة</option>
                                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>❌ مرفوضة</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-project-diagram text-primary me-1"></i>
                                    المشروع
                                </label>
                                <input
                                    type="text"
                                    name="project_search"
                                    class="form-control"
                                    list="projects-list"
                                    placeholder="ابحث عن مشروع..."
                                    value="{{ request('project_id') ? $projects->firstWhere('id', request('project_id'))?->name : '' }}"
                                    autocomplete="off">
                                <input type="hidden" name="project_id" id="project_id" value="{{ request('project_id') }}">
                                <datalist id="projects-list">
                                    <option value="">جميع المشاريع</option>
                                    @foreach($projects as $project)
                                        <option
                                            value="{{ $project->name }}{{ $project->code ? ' (' . $project->code . ')' : '' }}"
                                            data-id="{{ $project->id }}">
                                        </option>
                                    @endforeach
                                </datalist>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-calendar-alt text-primary me-1"></i>
                                    الشهر
                                </label>
                                <input type="month" name="month" class="form-control" value="{{ request('month') }}" placeholder="اختر الشهر">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2" style="border-radius: 8px; padding: 0.6rem 1.5rem; font-weight: 600;">
                                    <i class="fas fa-filter me-1"></i>
                                    تطبيق
                                </button>
                                <a href="{{ route('attachment-confirmations.index') }}" class="btn btn-secondary" style="border-radius: 8px; padding: 0.6rem 1.5rem; font-weight: 600;" title="إزالة جميع الفلاتر">
                                    <i class="fas fa-redo me-1"></i>
                                    إعادة
                                </a>
                            </div>
                        </div>
                    </form>

                    <!-- تنبيه عن الفلتر النشط -->
                    @if(request('status') && request('status') != '')
                        <div class="alert alert-info alert-dismissible fade show mb-3" role="alert" style="border-radius: 10px; border: none; background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%); border-right: 5px solid #2196f3;">
                            <div class="d-flex align-items-center">
                                <div style="width: 40px; height: 40px; background: #2196f3; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-left: 1rem;">
                                    <i class="fas fa-filter text-white"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <strong>تنبيه:</strong> يتم عرض الطلبات بحالة
                                    <strong>
                                        @if(request('status') == 'pending')
                                            "قيد الانتظار"
                                        @elseif(request('status') == 'confirmed')
                                            "مؤكدة"
                                        @elseif(request('status') == 'rejected')
                                            "مرفوضة"
                                        @endif
                                    </strong>
                                    فقط.
                                    <a href="{{ route('attachment-confirmations.index') }}" class="alert-link fw-bold">إزالة الفلتر</a>
                                    لعرض جميع الطلبات.
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <!-- جدول الطلبات -->
                    @if($confirmations->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-modern">
                                <thead>
                                    <tr>
                                        <th>المرفق</th>
                                        <th>نوع الملف</th>
                                        <th>المشروع</th>
                                        <th>مقدم الطلب</th>
                                        <th>تم التأكيد بواسطة</th>
                                        <th>التاريخ</th>
                                        <th>تسليم نفس اليوم</th>
                                        <th>الحالة</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($confirmations as $confirmation)
                                        <tr class="{{ $confirmation->status == 'pending' ? 'table-warning-subtle' : '' }}">
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="file-icon me-3">
                                                        <i class="fas fa-file"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold" style="font-size: 0.95rem;">{{ $confirmation->attachment?->file_name ?? 'ملف محذوف' }}</div>
                                                        @if($confirmation->attachment?->description)
                                                            <small class="text-muted">{{ Str::limit($confirmation->attachment->description, 50) }}</small>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                @if($confirmation->file_type)
                                                    <span class="badge badge-modern bg-primary">
                                                        <i class="fas fa-tag me-1"></i>
                                                        {{ $confirmation->file_type }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($confirmation->project)
                                                <a href="{{ route('projects.show', $confirmation->project->id) }}" class="text-decoration-none">
                                                    <div style="display: inline-block; padding: 0.5rem 1rem; background: #f8f9fa; border-radius: 8px;">
                                                        <div class="fw-bold text-dark" style="font-size: 0.9rem;">{{ $confirmation->project->name }}</div>
                                                        @if($confirmation->project->code)
                                                            <small class="text-muted">
                                                                <i class="fas fa-hashtag"></i>
                                                                {{ $confirmation->project->code }}
                                                            </small>
                                                        @endif
                                                    </div>
                                                </a>
                                                @else
                                                <span class="text-muted">مشروع محذوف</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($confirmation->requester)
                                                <div class="user-info">
                                                    <div class="user-avatar">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold" style="font-size: 0.9rem;">{{ $confirmation->requester->name }}</div>
                                                        <small class="text-muted">طلب التأكيد</small>
                                                    </div>
                                                </div>
                                                @else
                                                <span class="text-muted">مستخدم محذوف</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($confirmation->confirmedBy)
                                                    <div class="user-info">
                                                        <div class="user-avatar" style="background: {{ $confirmation->status == 'confirmed' ? 'linear-gradient(135deg, #28a745 0%, #20c997 100%)' : 'linear-gradient(135deg, #dc3545 0%, #e74c3c 100%)' }};">
                                                            <i class="fas fa-user-check"></i>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold" style="font-size: 0.9rem;">{{ $confirmation->confirmedBy->name }}</div>
                                                            <small class="text-muted">
                                                                @if($confirmation->status == 'confirmed')
                                                                    أكد الطلب
                                                                @else
                                                                    رفض الطلب
                                                                @endif
                                                            </small>
                                                        </div>
                                                    </div>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <div style="background: #f8f9fa; padding: 0.5rem; border-radius: 8px; display: inline-block;">
                                                    <div class="fw-bold" style="font-size: 0.9rem;">{{ $confirmation->created_at->format('Y-m-d') }}</div>
                                                    <small class="text-muted">{{ $confirmation->created_at->format('h:i A') }}</small>
                                                    @if($confirmation->confirmed_at)
                                                        <hr class="my-1">
                                                        <small class="text-success fw-bold">
                                                            <i class="fas fa-check"></i>
                                                            {{ $confirmation->confirmed_at->format('Y-m-d h:i A') }}
                                                        </small>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                @if($confirmation->confirmed_at && $confirmation->status == 'confirmed')
                                                    @php
                                                        $confirmedDate = $confirmation->confirmed_at->format('Y-m-d');
                                                        $createdDate = $confirmation->created_at->format('Y-m-d');
                                                        $isSameDay = $confirmedDate === $createdDate;
                                                    @endphp

                                                    @if($isSameDay)
                                                        <div style="display: inline-block; padding: 0.5rem 1rem; background: #d4edda; border-radius: 8px; border: 2px solid #28a745;">
                                                            <i class="fas fa-check-circle text-success" style="font-size: 1.2rem;"></i>
                                                            <div class="fw-bold text-success" style="font-size: 0.85rem;">نفس اليوم</div>
                                                            <small class="text-muted">{{ $confirmation->confirmed_at->diffForHumans($confirmation->created_at) }}</small>
                                                        </div>
                                                    @else
                                                        <div style="display: inline-block; padding: 0.5rem 1rem; background: #fff3cd; border-radius: 8px; border: 2px solid #ffc107;">
                                                            <i class="fas fa-clock text-warning" style="font-size: 1.2rem;"></i>
                                                            <div class="fw-bold text-warning" style="font-size: 0.85rem;">يوم مختلف</div>
                                                            <small class="text-muted">{{ $confirmation->confirmed_at->diffForHumans($confirmation->created_at) }}</small>
                                                        </div>
                                                    @endif
                                                @elseif($confirmation->status == 'rejected')
                                                    <div style="display: inline-block; padding: 0.5rem 1rem; background: #f8d7da; border-radius: 8px; border: 2px solid #dc3545;">
                                                        <i class="fas fa-times-circle text-danger" style="font-size: 1.2rem;"></i>
                                                        <div class="fw-bold text-danger" style="font-size: 0.85rem;">مرفوض</div>
                                                    </div>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($confirmation->status == 'pending')
                                                    <span class="badge badge-modern bg-warning text-dark">
                                                        <i class="fas fa-clock"></i> قيد الانتظار
                                                    </span>
                                                @elseif($confirmation->status == 'confirmed')
                                                    <span class="badge badge-modern bg-success">
                                                        <i class="fas fa-check-circle"></i> مؤكد
                                                    </span>
                                                @else
                                                    <span class="badge badge-modern bg-danger">
                                                        <i class="fas fa-times-circle"></i> مرفوض
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                {{-- أزرار التأكيد والرفض تظهر فقط للطلبات بحالة "قيد الانتظار" --}}
                                                @if($confirmation->status == 'pending')
                                                    <div class="d-flex flex-wrap justify-content-center gap-1">
                                                        <button type="button"
                                                                class="btn btn-sm btn-action btn-success confirm-attachment-btn"
                                                                data-confirmation-id="{{ $confirmation->id }}"
                                                                data-attachment-name="{{ $confirmation->attachment?->file_name ?? 'ملف محذوف' }}"
                                                                title="تأكيد المرفق وإرسال إشعار للموظف">
                                                            <i class="fas fa-check"></i>
                                                            تأكيد
                                                        </button>
                                                        <button type="button"
                                                                class="btn btn-sm btn-action btn-danger reject-attachment-btn"
                                                                data-confirmation-id="{{ $confirmation->id }}"
                                                                data-attachment-name="{{ $confirmation->attachment?->file_name ?? 'ملف محذوف' }}"
                                                                title="رفض المرفق وإرسال إشعار للموظف">
                                                            <i class="fas fa-times"></i>
                                                            رفض
                                                        </button>
                                                        @if($confirmation->attachment)
                                                        <a href="{{ route('projects.attachments.view', $confirmation->attachment->id) }}"
                                                           target="_blank"
                                                           class="btn btn-sm btn-outline-primary"
                                                           style="border-radius: 8px; border-width: 2px;">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="{{ route('projects.attachments.download', $confirmation->attachment->id) }}"
                                                           class="btn btn-sm btn-outline-success"
                                                           style="border-radius: 8px; border-width: 2px;">
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                        @else
                                                        <span class="badge bg-secondary">ملف محذوف</span>
                                                        @endif
                                                    </div>
                                                @else
                                                    <div class="d-flex flex-wrap justify-content-center gap-1">
                                                        @if($confirmation->attachment)
                                                        <a href="{{ route('projects.attachments.view', $confirmation->attachment->id) }}"
                                                           target="_blank"
                                                           class="btn btn-sm btn-outline-primary"
                                                           style="border-radius: 8px; border-width: 2px;">
                                                            <i class="fas fa-eye"></i>
                                                            عرض
                                                        </a>
                                                        <a href="{{ route('projects.attachments.download', $confirmation->attachment->id) }}"
                                                           class="btn btn-sm btn-outline-success"
                                                           style="border-radius: 8px; border-width: 2px;">
                                                            <i class="fas fa-download"></i>
                                                            تحميل
                                                        </a>
                                                        @else
                                                        <span class="badge bg-secondary">ملف محذوف</span>
                                                        @endif
                                                        @if($confirmation->notes)
                                                            <button type="button"
                                                                    class="btn btn-sm btn-outline-info"
                                                                    style="border-radius: 8px; border-width: 2px;"
                                                                    data-bs-toggle="tooltip"
                                                                    title="{{ $confirmation->notes }}">
                                                                <i class="fas fa-comment"></i>
                                                            </button>
                                                        @endif
                                                    </div>
                                                @endif

                                                {{-- زر إعادة التعيين - يظهر فقط إذا لم يمر 24 ساعة على التأكيد --}}
                                                @php
                                                    $canReset = true;
                                                    if ($confirmation->confirmed_at && $confirmation->status != 'pending') {
                                                        $hoursSinceConfirmation = $confirmation->confirmed_at->diffInHours(now());
                                                        $canReset = $hoursSinceConfirmation < 24;
                                                    }
                                                @endphp

                                                @if($canReset)
                                                    <div class="d-flex justify-content-center mt-1">
                                                        <button type="button"
                                                                class="btn btn-sm btn-action btn-warning reset-confirmation-btn"
                                                                data-confirmation-id="{{ $confirmation->id }}"
                                                                data-attachment-name="{{ $confirmation->attachment?->file_name ?? 'ملف محذوف' }}"
                                                                title="إعادة الطلب إلى حالة 'قيد الانتظار' لإعادة المراجعة">
                                                            <i class="fas fa-undo"></i>
                                                            إعادة تعيين
                                                        </button>
                                                    </div>
                                                @else
                                                    <div class="d-flex justify-content-center mt-1">
                                                        <small class="text-muted" style="background: #f8f9fa; padding: 0.4rem 0.8rem; border-radius: 6px;">
                                                            <i class="fas fa-lock"></i>
                                                            مر أكثر من 24 ساعة
                                                        </small>
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-center mt-4">
                            {{ $confirmations->appends(request()->query())->links() }}
                        </div>
                    @else
                        <div class="text-center py-5" style="background: white; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                            <div style="width: 100px; height: 100px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; margin: 0 auto 1.5rem; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-inbox fa-3x text-white"></i>
                            </div>
                            <h5 class="text-muted mb-2">لا توجد طلبات تأكيد</h5>
                            <p class="text-muted small">لم يتم العثور على أي طلبات تأكيد للمرفقات حاليًا</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- تضمين SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.20/dist/sweetalert2.all.min.js"></script>

@if(session('success'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            toastr.success('{{ session('success') }}', 'نجاح ✅');
        });
    </script>
@endif

@if(session('error'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            toastr.error('{{ session('error') }}', 'خطأ ❌');
        });
    </script>
@endif

<!-- JavaScript للتعامل مع طلبات التأكيد -->
<script src="{{ asset('js/projects/attachment-confirmation.js') }}?v={{ time() }}"></script>

<script>
// تأكيد تحميل الصفحة وتشخيص الأزرار
document.addEventListener('DOMContentLoaded', function() {
    console.log('=== Attachment Confirmation Page Loaded ===');

    // عرض إحصائيات الطلبات
    const allRows = document.querySelectorAll('tbody tr');
    console.log(`Total rows: ${allRows.length}`);

    allRows.forEach((row, index) => {
        const statusBadge = row.querySelector('.badge');
        const confirmBtn = row.querySelector('.confirm-attachment-btn');
        const rejectBtn = row.querySelector('.reject-attachment-btn');
        const resetBtn = row.querySelector('.reset-confirmation-btn');

        let status = 'unknown';
        if (statusBadge) {
            if (statusBadge.classList.contains('bg-warning')) status = 'pending';
            else if (statusBadge.classList.contains('bg-success')) status = 'confirmed';
            else if (statusBadge.classList.contains('bg-danger')) status = 'rejected';
        }

        console.log(`Row ${index + 1}: Status=${status}, ConfirmBtn=${!!confirmBtn}, RejectBtn=${!!rejectBtn}, ResetBtn=${!!resetBtn}`);
    });

    // معالجة datalist للمشاريع
    const projectInput = document.querySelector('input[name="project_search"]');
    const projectIdInput = document.getElementById('project_id');
    const projectsList = document.getElementById('projects-list');

    if (projectInput && projectIdInput && projectsList) {
        projectInput.addEventListener('input', function() {
            const selectedValue = this.value;

            // البحث عن المشروع في الـ datalist
            const options = projectsList.querySelectorAll('option');
            let foundId = '';

            options.forEach(option => {
                if (option.value === selectedValue) {
                    foundId = option.getAttribute('data-id') || '';
                }
            });

            // إذا كانت القيمة فارغة أو "جميع المشاريع"
            if (selectedValue === '' || selectedValue === 'جميع المشاريع') {
                foundId = '';
            }

            projectIdInput.value = foundId;
        });

        // مسح الـ input عند الضغط على زر إعادة التعيين
        projectInput.addEventListener('blur', function() {
            if (projectIdInput.value === '') {
                this.value = '';
            }
        });
    }
});
</script>
@endsection
