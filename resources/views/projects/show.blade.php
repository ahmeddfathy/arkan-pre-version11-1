@extends('layouts.app')

@section('title', 'تفاصيل المشروع')

@section('content')
@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.20/dist/sweetalert2.min.css">
<link rel="stylesheet" href="{{ asset('css/projects.css') }}">
<link rel="stylesheet" href="{{ asset('css/project-acknowledgment.css') }}">
<!-- Projects Calendar CSS -->
<link rel="stylesheet" href="{{ asset('css/tasks/my-tasks-calendar.css') }}">
@endpush

    <meta name="project-id" content="{{ $project->id }}">
    <meta name="user-name" content="{{ Auth::user()->name }}">
    <meta name="user-id" content="{{ Auth::id() }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<div class="container-fluid px-1 project-show-page" data-project-id="{{ $project->id }}">
    @include('projects.partials._project_header')

    @if($project->hasUnacknowledgedParticipation())
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-danger acknowledgment-alert border-0 shadow-sm d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <i class="fas fa-exclamation-circle fa-2x text-danger"></i>
                    </div>
                    <div>
                        <h5 class="alert-heading mb-1 text-danger">⚠️ مشروع غير مستلم - يتطلب تأكيد الاستلام</h5>
                        <p class="mb-0 text-dark">يرجى تأكيد استلام هذا المشروع للمتابعة والعمل عليه</p>
                    </div>
                </div>
                <div>
                    <button type="button" class="btn btn-danger btn-lg" id="acknowledgeProjectBtn" data-project-id="{{ $project->id }}">
                        <i class="fas fa-check me-2"></i>
                        تأكيد الاستلام
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- إحصائيات المشروع السريعة -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm bg-gradient-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div class="d-flex align-items-center">
                            <div class="icon-shape icon-sm bg-white text-primary rounded-circle me-3">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div>
                                <h5 class="text-dark mb-1">إحصائيات المشروع المتقدمة</h5>
                                <p class="text-muted mb-0">احصل على تحليل شامل لأداء المشروع والفريق</p>
                            </div>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="{{ route('projects.service-data', $project->id) }}" class="btn btn-primary btn-sm d-flex align-items-center">
                                <i class="fas fa-database me-2"></i>
                                بيانات الخدمات
                            </a>

                            <a href="{{ route('projects.analytics', $project->id) }}" class="btn btn-dark btn-sm d-flex align-items-center">
                                <i class="fas fa-chart-bar me-2"></i>
                                لوحة الإحصائيات
                            </a>

                            <div class="dropdown">
                                <button class="btn btn-outline-dark btn-sm dropdown-toggle d-flex align-items-center" type="button" id="employeeAnalyticsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-user-chart me-2"></i>
                                    تحليل الموظفين
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="employeeAnalyticsDropdown">
                                    @if(isset($projectUsers) && $projectUsers->count() > 0)
                                        @foreach($projectUsers as $user)
                                            <li>
                                                <a class="dropdown-item" href="{{ route('projects.employee-analytics', ['project' => $project->id, 'user' => $user->id]) }}">
                                                    <i class="fas fa-user me-2"></i>
                                                    {{ $user->name }}
                                                </a>
                                            </li>
                                        @endforeach
                                    @else
                                        <li><span class="dropdown-item-text text-muted">لا يوجد مشاركين في المشروع</span></li>
                                    @endif
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

                                                                <div class="card-body">
        @include('projects.partials._project_info')

        @include('projects.partials._kanban_board')

        @include('projects.partials._attachments_updated')

        <!-- قسم الملاحظات -->
        @include('projects.partials._project_notes')

        @include('projects.partials._participants')
    </div>
</div>

<!-- Transfer Task Sidebar -->
<div id="transferSidebar" class="transfer-sidebar">
    <div class="sidebar-overlay" id="transferSidebarOverlay" onclick="closeTransferSidebar()"></div>
    <div class="sidebar-content">
        <!-- Sidebar Header -->
        <div class="sidebar-header">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="sidebar-icon bg-warning">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <div class="ms-3">
                        <h5 class="mb-0" id="transferSidebarTitle">نقل المهمة</h5>
                        <small class="text-muted" id="transferSidebarSubtitle">تحويل المهمة لمستخدم آخر</small>
                    </div>
                </div>
                <button class="btn btn-sm btn-outline-secondary" onclick="closeTransferSidebar()" style="border-radius: 8px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <!-- Sidebar Content -->
        <div class="sidebar-body" id="transferSidebarContent">
            <form id="transferTaskForm">
                <input type="hidden" id="taskType" name="taskType">
                <input type="hidden" id="taskId" name="taskId">

                <!-- Task Info -->
                <div class="mb-4">
                    <label class="form-label fw-semibold text-muted mb-2" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">معلومات المهمة</label>
                    <div class="p-3 rounded" style="background: #f8f9fa; border: 1px solid #e9ecef;">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-tasks text-primary me-2"></i>
                            <span class="fw-semibold" id="taskName">-</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-user text-info me-2"></i>
                            <span class="text-muted" id="currentUser">-</span>
                        </div>
                    </div>
                </div>

                <!-- Transfer To User -->
                <div class="mb-4">
                    <label for="userInput" class="form-label fw-semibold text-muted mb-2" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-user me-1"></i>
                        نقل إلى المستخدم
                    </label>
                    <div class="position-relative">
                        <!-- ✅ Input مع Datalist (Autocomplete) -->
                        <input type="text" class="form-control" id="userInput" name="userInput"
                               list="usersList" placeholder="اكتب اسم المستخدم..." required autocomplete="off"
                               style="border-radius: 8px; padding: 12px 16px;">
                        <datalist id="usersList">
                            <!-- سيتم ملؤها بـ JavaScript -->
                        </datalist>

                        <div id="selectedUserInfo" class="position-absolute user-info-dropdown d-none mt-2">
                            <div class="alert alert-info alert-sm mb-0" style="border-radius: 8px; font-size: 12px;">
                                <i class="fas fa-user me-2"></i>
                                <span id="selectedUserDetails"></span>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" id="toUserId" name="toUserId">
                    <div class="form-text mt-2" style="font-size: 11px;">اكتب اسم المستخدم واختر من القائمة</div>
                </div>

                <!-- Transfer Type -->
                <div class="mb-4">
                    <label class="form-label fw-semibold text-muted mb-2" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-exchange-alt me-1"></i>
                        نوع النقل
                    </label>
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="transferType" id="positiveTransfer" value="positive" checked>
                                <label class="form-check-label" for="positiveTransfer">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-thumbs-up text-success me-2"></i>
                                        <div>
                                            <strong>نقل إيجابي</strong>
                                            <small class="d-block text-muted">لا يخصم نقاط - يحسب إنجاز</small>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="transferType" id="negativeTransfer" value="negative">
                                <label class="form-check-label" for="negativeTransfer">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-thumbs-down text-danger me-2"></i>
                                        <div>
                                            <strong>نقل سلبي</strong>
                                            <small class="d-block text-muted">يخصم نقاط - تحفيز للتحسن</small>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- New Deadline -->
                <div class="mb-4">
                    <label for="newDeadline" class="form-label fw-semibold text-muted mb-2" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-calendar-alt me-1"></i>
                        موعد نهائي جديد (اختياري)
                    </label>
                    <input type="date" class="form-control" id="newDeadline" name="newDeadline"
                           style="border-radius: 8px; padding: 12px 16px;">
                    <div class="form-text mt-2" style="font-size: 11px;">
                        إذا تُرك فارغاً، سيتم الاحتفاظ بنفس الموعد النهائي الأصلي
                    </div>
                </div>

                <!-- Transfer Reason -->
                <div class="mb-4">
                    <label for="transferReason" class="form-label fw-semibold text-muted mb-2" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-comment me-1"></i>
                        سبب النقل (اختياري)
                    </label>
                    <textarea class="form-control" id="transferReason" name="transferReason"
                              rows="3" placeholder="اكتب سبب نقل المهمة..."
                              style="border-radius: 8px; padding: 12px 16px; resize: vertical;"></textarea>
                </div>

                <!-- Status Messages -->
                <div id="transferCheck" class="alert alert-warning d-none mb-3" style="border-radius: 8px; font-size: 13px;">
                    <i class="fas fa-spinner fa-spin me-2"></i>
                    جاري التحقق من إمكانية النقل...
                </div>

                <div id="transferError" class="alert alert-danger d-none mb-3" style="border-radius: 8px; font-size: 13px;"></div>
                <div id="transferSuccess" class="alert alert-success d-none mb-3" style="border-radius: 8px; font-size: 13px;"></div>

                <!-- Action Buttons -->
                <div class="d-flex gap-2 pt-3" style="border-top: 1px solid #e9ecef;">
                    <button type="button" class="btn btn-warning px-4 py-2" id="confirmTransferBtn" style="border-radius: 8px; font-weight: 500; flex: 1;">
                        <i class="fas fa-exchange-alt me-1"></i>
                        نقل المهمة
                    </button>
                    <button type="button" class="btn btn-outline-secondary px-4 py-2" onclick="closeTransferSidebar()" style="border-radius: 8px; font-weight: 500;">
                        <i class="fas fa-times me-1"></i>
                        إلغاء
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal لعرض تاريخ التعديلات -->
<div class="modal fade" id="dateHistoryModal" tabindex="-1" aria-labelledby="dateHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dateHistoryModalLabel">
                    <i class="fas fa-history me-2"></i>
                    تاريخ التعديلات - <span id="dateTypeLabel"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="dateHistoryContent">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">جاري التحميل...</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.20/dist/sweetalert2.all.min.js"></script>

<!-- تم نقل CSS و JavaScript إلى ملفات خارجية -->
<script>
    // مشاركة متغير project ID مع ملفات JavaScript الخارجية
    var projectId = "{{ $project->id }}";
</script>

<div class="modal fade" id="templateTasksModal" tabindex="-1" aria-labelledby="templateTasksModalLabel" aria-hidden="true" style="display: none !important;">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="templateTasksModalLabel">
                    <i class="fas fa-clipboard-list me-2"></i>
                    اختيار مهام القوالب للمستخدم
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info mb-3">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-info-circle me-2"></i>
                        <div>
                            <strong>المستخدم:</strong> <span id="selectedUserName">-</span><br>
                            <strong>الخدمة:</strong> <span id="selectedServiceName">-</span>
                        </div>
                    </div>
                </div>

                <div id="templateTasksContainer">
                    <div class="d-flex justify-content-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">جاري التحميل...</span>
                        </div>
                    </div>
                </div>



                <div class="mt-3 border-top pt-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="selectAllTasks">
                        <label class="form-check-label fw-bold" for="selectAllTasks">
                            تحديد جميع المهام
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>
                    إلغاء
                </button>
                <button type="button" class="btn btn-primary" id="assignSelectedTasksBtn" disabled>
                    <i class="fas fa-plus me-1"></i>
                    إضافة المهام المختارة (<span id="selectedTasksCount">0</span>)
                </button>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/projects/file-size-warning.js') }}"></script>
<script src="{{ asset('js/projects/project-attachments.js') }}"></script>
<script src="{{ asset('js/projects/project-tasks.js') }}"></script>
<script src="{{ asset('js/projects/project-kanban.js') }}"></script>
<script src="{{ asset('js/projects/project-participants.js') }}"></script>
<script src="{{ asset('js/projects/project-total-timer.js') }}"></script>
<script src="{{ asset('js/projects/project-notes.js') }}"></script>
<script src="{{ asset('js/projects/project-show-calendar.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/projects/attachment-sharing.js') }}"></script>
<script src="{{ asset('js/projects/project-show-interactions.js') }}?v={{ time() }}"></script>

<!-- Task Transfer Error Handler (only) - main logic is in project-show-interactions.js -->
<script src="{{ asset('js/tasks/transfer-error-handler.js') }}?v={{ time() }}"></script>

<!-- Attachment Confirmation System -->
<script src="{{ asset('js/projects/attachment-confirmation.js') }}?v={{ time() }}"></script>


<script>
// دالة عرض تاريخ التعديلات
function showDateHistory(dateType) {
    const dateLabels = {
        'start_date': 'تاريخ البداية',
        'team_delivery_date': 'تاريخ تسليم الفريق',
        'client_agreed_delivery_date': 'تاريخ التسليم المتفق عليه',
        'actual_delivery_date': 'تاريخ التسليم الفعلي'
    };

    $('#dateTypeLabel').text(dateLabels[dateType] || 'تاريخ غير معروف');
    $('#dateHistoryContent').html(`
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">جاري التحميل...</span>
            </div>
        </div>
    `);

    // عرض Modal
    $('#dateHistoryModal').modal('show');

    // جلب البيانات
    fetch(`/projects/${projectId}/dates/history/${dateType}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.dates.length > 0) {
                let html = '<div class="timeline">';

                data.dates.forEach((dateEntry, index) => {
                    const isLast = index === data.dates.length - 1;
                    const dateValue = new Date(dateEntry.date_value).toLocaleDateString('ar-EG');
                    const effectiveFrom = new Date(dateEntry.effective_from).toLocaleDateString('ar-EG');
                    const userName = dateEntry.user ? dateEntry.user.name : 'غير معروف';

                    html += `
                        <div class="timeline-item ${isLast ? 'last' : ''}">
                            <div class="timeline-marker">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="mb-1 text-primary">${dateValue}</h6>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            ${effectiveFrom}
                                        </small>
                                    </div>
                                    <span class="badge bg-info">${userName}</span>
                                </div>
                                ${dateEntry.notes ? `
                                    <div class="mt-2">
                                        <small class="text-muted">الملاحظات:</small>
                                        <p class="mb-0 small">${dateEntry.notes}</p>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    `;
                });

                html += '</div>';
                $('#dateHistoryContent').html(html);
            } else {
                $('#dateHistoryContent').html(`
                    <div class="text-center py-4">
                        <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">لا توجد تعديلات على هذا التاريخ</h6>
                        <p class="text-muted small">لم يتم إجراء أي تعديلات على هذا التاريخ حتى الآن</p>
                    </div>
                `);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            $('#dateHistoryContent').html(`
                <div class="text-center py-4">
                    <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                    <h6 class="text-danger">حدث خطأ</h6>
                    <p class="text-muted small">لم يتمكن من تحميل تاريخ التعديلات</p>
                </div>
            `);
        });
}
</script>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
    padding-bottom: 20px;
    border-left: 2px solid #e9ecef;
}

.timeline-item.last {
    border-left: none;
}

.timeline-marker {
    position: absolute;
    left: -11px;
    top: 0;
    width: 20px;
    height: 20px;
    background: #007bff;
    border: 2px solid #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    color: white;
}

.timeline-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-left: 20px;
    border: 1px solid #e9ecef;
}

.timeline-content h6 {
    font-weight: 600;
}
</style>

