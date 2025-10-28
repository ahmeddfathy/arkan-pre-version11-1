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
<!-- Task Revisions CSS -->
<link rel="stylesheet" href="{{ asset('css/task-revisions.css') }}">
<!-- Project Revisions Sidebar CSS -->
<link rel="stylesheet" href="{{ asset('css/project-revisions-sidebar.css') }}">
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

<!-- Project Revisions Sidebar -->
<div id="projectRevisionsSidebar" class="project-revisions-sidebar">
    <!-- Sidebar Header -->
    <div class="revisions-sidebar-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="revisions-sidebar-title">
                    <i class="fas fa-clipboard-list"></i>
                    تعديلات المشروع
                </h5>
                <p class="revisions-sidebar-subtitle">إدارة تعديلات وتحديثات المشروع</p>
            </div>
            <button class="revisions-close-btn" onclick="closeProjectRevisionsSidebar()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <!-- Sidebar Content -->
    <div class="revisions-sidebar-content">
        <!-- Add Revision Button -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 text-muted" style="font-size: 14px; font-weight: 600;">التعديلات</h6>
            <button class="revisions-add-btn" onclick="showAddProjectRevisionForm()">
                <i class="fas fa-plus"></i>
                إضافة تعديل
            </button>
        </div>

        <!-- Service Tabs -->
        <ul class="nav nav-pills mb-3" id="serviceRevisionTabs" role="tablist" style="border-bottom: 1px solid #e0e0e0; padding-bottom: 10px;">
            <li class="nav-item" role="presentation">
                <button class="nav-link active service-tab-btn" id="all-revisions-tab" data-bs-toggle="pill" data-bs-target="#all-revisions-content"
                        type="button" role="tab" onclick="loadProjectRevisionsByService(null)">
                    الكل
                    <span class="badge bg-primary ms-1" id="allRevisionsCount">0</span>
                </button>
            </li>
            @if($project->services && $project->services->count() > 0)
                @foreach($project->services as $service)
                <li class="nav-item" role="presentation">
                    <button class="nav-link service-tab-btn" id="service-{{ $service->id }}-tab" data-bs-toggle="pill"
                            data-bs-target="#service-{{ $service->id }}-content" type="button" role="tab"
                            onclick="loadProjectRevisionsByService({{ $service->id }})">
                        {{ $service->name }}
                        <span class="badge bg-secondary ms-1" id="service{{ $service->id }}RevisionsCount">0</span>
                    </button>
                </li>
                @endforeach
            @endif
        </ul>

        <style>
            #serviceRevisionTabs {
                display: flex;
                flex-wrap: wrap;
                gap: 5px;
                overflow-x: auto;
                white-space: nowrap;
            }
            .service-tab-btn {
                font-size: 13px;
                padding: 8px 15px;
                border-radius: 20px;
                margin-right: 5px;
                transition: all 0.3s ease;
                border: 1px solid #e0e0e0;
                background: #fff;
                color: #666;
                white-space: nowrap;
                display: inline-flex;
                align-items: center;
                gap: 5px;
            }
            .service-tab-btn:hover {
                background: #f8f9fa;
                border-color: #667eea;
            }
            .service-tab-btn.active {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border-color: #667eea;
            }
            .service-tab-btn .badge {
                font-size: 10px;
                padding: 3px 7px;
                border-radius: 10px;
            }
            .service-tab-btn.active .badge {
                background: rgba(255,255,255,0.3) !important;
            }
        </style>

        <!-- Add Revision Form (Hidden by default) -->
        <div id="addProjectRevisionForm" class="add-project-revision-form" style="display: none;">
            <!-- نوع التعديل (مخفي - دائماً project) -->
            <input type="hidden" id="revisionType" value="project">

            <!-- مصدر التعديل -->
            <div class="mb-3">
                <label class="form-label">مصدر التعديل</label>
                <select id="revisionSource" class="form-control">
                    <option value="internal">تعديل داخلي (من الفريق)</option>
                    <option value="external">تعديل خارجي (من العميل)</option>
                </select>
                <small class="text-muted">حدد ما إذا كان التعديل من الفريق الداخلي أم من العميل</small>
            </div>

            <!-- الخدمة المرتبطة -->
            <div class="mb-3" id="serviceContainer">
                <label class="form-label">الخدمة المرتبطة <span class="text-muted">(اختياري)</span></label>
                <select id="serviceId" class="form-control">
                    <option value="">-- اختر الخدمة --</option>
                    @if($project->services && $project->services->count() > 0)
                        @foreach($project->services as $service)
                            <option value="{{ $service->id }}">{{ $service->name }}</option>
                        @endforeach
                    @endif
                </select>
                <small class="text-muted">حدد الخدمة المرتبطة بهذا التعديل</small>
            </div>

            <!-- اختيار شخص معين (اختياري) -->
            <div class="mb-3" id="assignedUserContainer">
                <label class="form-label">تعديل مرتبط بشخص معين <span class="text-muted">(اختياري)</span></label>
                <select id="assignedTo" class="form-control">
                    <option value="">-- اختر شخص (اختياري) --</option>
                    @if($project->participants && $project->participants->count() > 0)
                        @foreach($project->participants as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    @endif
                </select>
                <small class="text-muted">يمكن ترك هذا الحقل فارغ للتعديلات من العميل</small>
            </div>

            <!-- الحقول الجديدة للمسؤولية ⭐ -->
            <div class="mb-3 p-3" style="background: #fff8f0; border-radius: 8px; border-left: 4px solid #ff9800;">
                <h6 class="text-warning mb-3">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    تتبع المسؤولية
                    <span class="text-muted" style="font-size: 12px; font-weight: normal;">(اختياري)</span>
                </h6>

                <!-- المسؤول (اللي غلط) -->
                <div class="mb-3">
                    <label class="form-label">
                        <span class="text-danger">⚠️ المسؤول عن الغلط</span>
                        <span class="text-muted" style="font-size: 11px;">(اللي هيتحاسب)</span>
                    </label>
                    <select id="responsibleUserId" class="form-control">
                        <option value="">-- اختر الشخص المسؤول --</option>
                        {{-- سيتم ملؤه تلقائياً عبر JavaScript حسب role المستخدم --}}
                    </select>
                    <small class="text-muted">
                        القائمة تعتمد على دورك: المراجعين فقط لبعض الأدوار، أو كل المشاركين للأدوار الأخرى
                    </small>
                </div>

                <!-- المنفذ (اللي هيصلح) -->
                <div class="mb-3">
                    <label class="form-label">
                        <span class="text-primary">🔨 المنفذ</span>
                        <span class="text-muted" style="font-size: 11px;">(اللي هيصلح الغلط)</span>
                    </label>
                    <select id="executorUserId" class="form-control">
                        <option value="">-- اختر من سيقوم بالتصليح --</option>
                        @if($project->participants && $project->participants->count() > 0)
                            @foreach($project->participants as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        @endif
                    </select>
                    <small class="text-muted">الشخص الذي سيقوم بتنفيذ التعديل وإصلاح الخطأ</small>
                </div>

                <!-- المراجع (اللي هيراجع) -->
                <div class="mb-3">
                    <label class="form-label">
                        <span class="text-success">✅ المراجع</span>
                        <span class="text-muted" style="font-size: 11px;">(اللي هيراجع التعديل)</span>
                    </label>
                    <select id="reviewerUserId" class="form-control">
                        <option value="">-- اختر من سيراجع التعديل --</option>
                        @if($project->participants && $project->participants->count() > 0)
                            @foreach($project->participants as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        @endif
                    </select>
                    <small class="text-muted">الشخص المسؤول عن مراجعة التعديل والموافقة عليه</small>
                </div>

                <!-- ملاحظات المسؤولية -->
                <div class="mb-0">
                    <label class="form-label">
                        <span>📝 ملاحظات المسؤولية</span>
                        <span class="text-muted" style="font-size: 11px;">(سبب التعديل)</span>
                    </label>
                    <textarea id="responsibilityNotes" class="form-control" rows="2" placeholder="اذكر سبب التعديل والخطأ الذي حدث..." maxlength="2000"></textarea>
                    <small class="text-muted">توثيق سبب الخطأ الذي أدى للتعديل</small>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">عنوان التعديل</label>
                <input type="text" id="projectRevisionTitle" class="form-control" placeholder="عنوان التعديل..." maxlength="255">
            </div>
            <div class="mb-3">
                <label class="form-label">وصف التعديل</label>
                <textarea id="projectRevisionDescription" class="form-control" rows="3" placeholder="اكتب وصف التعديل هنا..." maxlength="5000"></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">ملاحظات إضافية <span class="text-muted">(اختياري)</span></label>
                <textarea id="projectRevisionNotes" class="form-control" rows="2" placeholder="أي ملاحظات إضافية..." maxlength="2000"></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">المرفق <span class="text-muted">(اختياري)</span></label>

                <!-- Attachment Type Selection -->
                <div class="btn-group w-100 mb-2" role="group">
                    <input type="radio" class="btn-check" name="projectRevisionAttachmentType" id="projectRevisionAttachmentTypeFile" value="file" checked onclick="toggleProjectRevisionAttachmentType('file')">
                    <label class="btn btn-outline-primary btn-sm" for="projectRevisionAttachmentTypeFile">
                        <i class="fas fa-file-upload me-1"></i>رفع ملف
                    </label>

                    <input type="radio" class="btn-check" name="projectRevisionAttachmentType" id="projectRevisionAttachmentTypeLink" value="link" onclick="toggleProjectRevisionAttachmentType('link')">
                    <label class="btn btn-outline-primary btn-sm" for="projectRevisionAttachmentTypeLink">
                        <i class="fas fa-link me-1"></i>إضافة لينك
                    </label>
                </div>

                <!-- File Upload Option -->
                <div id="projectRevisionFileUploadContainer">
                    <input type="file" id="projectRevisionAttachment" class="form-control" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip,.rar">
                    <small class="text-muted">الحد الأقصى: 10 ميجابايت</small>
                </div>

                <!-- Link Input Option (Hidden by default) -->
                <div id="projectRevisionLinkInputContainer" style="display: none;">
                    <input type="url" id="projectRevisionAttachmentLink" class="form-control" placeholder="https://example.com/file.pdf">
                    <small class="text-muted">أدخل رابط المرفق (يجب أن يبدأ بـ http:// أو https://)</small>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-success" onclick="saveProjectRevision()">
                    <i class="fas fa-save me-1"></i>حفظ التعديل
                </button>
                <button class="btn btn-outline-secondary" onclick="hideAddProjectRevisionForm()">
                    إلغاء
                </button>
            </div>
        </div>

        <!-- Revisions Container -->
        <div id="projectRevisionsContainer">
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">جاري التحميل...</span>
                </div>
                <p class="mt-2 text-muted mb-0" style="font-size: 12px;">جاري تحميل التعديلات...</p>
            </div>
        </div>
    </div>
</div>

<!-- Revisions Overlay -->
<div id="revisionsOverlay" class="revisions-overlay" onclick="closeProjectRevisionsSidebar()"></div>

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

<script src="{{ asset('js/projects/project-revisions-sidebar.js') }}?v={{ time() }}"></script>

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

