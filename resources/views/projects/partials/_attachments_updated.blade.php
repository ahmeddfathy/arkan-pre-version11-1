<link rel="stylesheet" href="{{ asset('css/projects/attachments.css') }}">

<style>
    /* تنسيق checkbox التأكيد */
    .confirmation-checkbox-container {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 8px;
        background-color: #f8f9fa;
        border: 1.5px solid #dee2e6;
        white-space: nowrap;
        min-width: 95px;
        justify-content: center;
    }

    .confirmation-checkbox-container:hover {
        background-color: #e9ecef;
        border-color: #adb5bd;
    }

    .confirmation-checkbox-container.pending-confirmation {
        background-color: #fff3cd;
        border: 1.5px solid #ffc107;
    }

    .confirmation-checkbox-container.confirmed {
        background-color: #d1e7dd;
        border: 1.5px solid #28a745;
    }

    .confirmation-checkbox-container.rejected {
        background-color: #f8d7da;
        border: 1.5px solid #dc3545;
    }

    .confirmation-checkbox {
        width: 18px !important;
        height: 18px !important;
        cursor: pointer;
        margin: 0 !important;
        flex-shrink: 0;
    }

    .confirmation-checkbox:checked {
        background-color: #28a745;
        border-color: #28a745;
    }

    .confirmation-checkbox-container.pending-confirmation .confirmation-checkbox:checked {
        background-color: #ffc107;
        border-color: #ffc107;
    }

    .confirmation-checkbox:disabled {
        opacity: 0.7;
        cursor: not-allowed;
    }

    .confirmation-checkbox-container label {
        margin-bottom: 0;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        user-select: none;
        color: #495057;
        flex-shrink: 0;
        line-height: 1;
    }

    .confirmation-checkbox-container.confirmed label {
        color: #155724;
    }

    .confirmation-checkbox-container.pending-confirmation label {
        color: #856404;
    }

    .confirmation-checkbox-container.rejected label {
        color: #721c24;
    }
</style>

{{-- البيانات تأتي جاهزة من الـ Controller --}}

<!-- شريط المشاركة المتعددة في الهيدر -->
<div id="multiShareHeader" class="multi-share-header d-none">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center">
            <div class="selected-info">
                <i class="fas fa-check-circle me-2"></i>
                <span id="selectedCountText">0 ملف محدد</span>
            </div>
            <div class="header-actions">
                <button type="button" id="shareSelectedHeaderBtn" class="share-btn me-2">
                    <i class="fas fa-share me-1"></i>
                    مشاركة المحدد
                </button>
                <button type="button" id="clearSelectionHeaderBtn" class="clear-btn">
                    <i class="fas fa-times me-1"></i>
                    إلغاء التحديد
                </button>
            </div>
        </div>
    </div>
</div>

<!-- قسم المجلدات الثابتة -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header attachment-section-header">
                <h5 class="mb-0">
                    <i class="fas fa-folder"></i>
                    المجلدات العامة
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    @foreach($fixedTypes ?? [] as $type)
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-header text-center" style="background: linear-gradient(135deg, #6c757d, #545b62); color: white; padding: 12px 20px; font-weight: 600; font-size: 14px; border-radius: 12px 12px 0 0; border: none;">
                                <i class="fas fa-folder-open me-2" style="opacity: 0.9;"></i>{{ $type }}
                            </div>
                            <div class="card-body">
                                <form class="attachment-upload-form upload-form mb-3" data-service-type="{{ $type }}" action="{{ route('projects.attachments.store', $project->id) }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <input type="hidden" name="service_type" value="{{ $type }}">

                                    <div class="drag-drop-upload-container">
                                        <div class="drag-drop-area" id="dragDropArea{{ str_replace(' ', '', $type) }}">
                                            <div class="drag-drop-message">
                                                <i class="fas fa-cloud-upload-alt mb-2"></i>
                                                <p>اسحب الملفات وأفلتها هنا</p>
                                                <p class="small text-muted">أو</p>
                                                <button type="button" class="file-select-button">اختر ملفات</button>
                                                <input type="file" name="attachment" id="fileInput{{ str_replace(' ', '', $type) }}" class="file-input-hidden">
                                            </div>
                                        </div>

                                        <div class="selected-files-container" id="selectedFiles{{ str_replace(' ', '', $type) }}"></div>
                                    </div>

                                    <div class="mb-2 mt-2">
                                        <input type="text" name="description" class="form-control form-control-sm" placeholder="وصف (اختياري)">
                                    </div>

                                    <!-- Progress Bar Elements -->
                                    <div class="upload-progress-container" id="progress-container-{{ str_replace(' ', '', $type) }}">
                                        <div class="progress-bar-container">
                                            <div class="progress-bar-animated"
                                                id="progress-bar-{{ str_replace(' ', '', $type) }}"
                                                role="progressbar"
                                                aria-valuenow="0"
                                                aria-valuemin="0"
                                                aria-valuemax="100">
                                            </div>
                                        </div>
                                        <div class="progress-text-container">
                                            <span id="percentage-{{ str_replace(' ', '', $type) }}" class="progress-percentage">0%</span>
                                        </div>
                                    </div>

                                    <div class="upload-status alert alert-info" id="status-{{ str_replace(' ', '', $type) }}">
                                        جاري التحضير...
                                    </div>

                                    <!-- الفولدرات الثابتة بدون ربط بمهام محددة -->
                                    @php
                                    $isFixedFolder = in_array($type, $fixedTypes ?? []);
                                    @endphp

                                    @if(!$isFixedFolder)
                                    <!-- اختيار نوع المهمة فقط في فولدرات الخدمات -->
                                    <div class="mb-2">
                                        <label class="form-label small">ربط بمهمة (اختياري):</label>
                                        <select name="task_type" class="form-control form-control-sm task-type-selector" id="taskType{{ str_replace(' ', '', $type) }}">
                                            <option value="">-- بدون ربط بمهمة --</option>
                                            <option value="template_task">مهمة قالب</option>
                                            <option value="regular_task">مهمة عادية</option>
                                        </select>
                                    </div>

                                    <!-- اختيار المهمة القالب -->
                                    <div class="mb-2 template-task-selector d-none" id="templateTaskSelector{{ str_replace(' ', '', $type) }}">
                                        <select name="template_task_id" class="form-control form-control-sm">
                                            <option value="">-- اختر مهمة القالب --</option>
                                            @if(isset($userTasks[auth()->id()]) && $userTasks[auth()->id()]->count() > 0)
                                            @foreach($userTasks[auth()->id()] as $taskUser)
                                            <option value="{{ $taskUser->id }}" data-service="{{ optional($taskUser->templateTask->template->service)->name }}">
                                                {{ optional($taskUser->templateTask)->name }}
                                            </option>
                                            @endforeach
                                            @endif
                                        </select>
                                    </div>

                                    <!-- اختيار المهمة العادية -->
                                    <div class="mb-2 regular-task-selector d-none" id="regularTaskSelector{{ str_replace(' ', '', $type) }}">
                                        <select name="task_user_id" class="form-control form-control-sm">
                                            <option value="">-- اختر المهمة العادية --</option>
                                            @if(isset($userActualTasks[auth()->id()]) && $userActualTasks[auth()->id()]->count() > 0)
                                            @foreach($userActualTasks[auth()->id()] as $taskUser)
                                            <option value="{{ $taskUser->id }}" data-service="{{ optional($taskUser->task->service)->name }}">
                                                {{ optional($taskUser->task)->title }}
                                            </option>
                                            @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    @else
                                    <!-- الفولدرات الثابتة - عامة بدون مهام محددة -->
                                    <div class="mb-2">
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle me-1"></i>
                                            هذا مجلد عام للمرفقات
                                        </small>
                                    </div>
                                    @endif

                                    <div class="mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="isReply{{ str_replace(' ', '', $type) }}" name="is_reply">
                                            <label class="form-check-label small" for="isReply{{ str_replace(' ', '', $type) }}">
                                                رد على ملف موجود
                                            </label>
                                        </div>
                                    </div>

                                    <div class="mb-2 reply-file-selector d-none">
                                        <select name="parent_attachment_id" class="form-control form-control-sm">
                                            <option value="">-- اختر الملف للرد عليه --</option>
                                            @php
                                            $typeAttachments = $project->attachments->where('service_type', $type)->where('parent_attachment_id', null);
                                            @endphp
                                            @if($typeAttachments->count() > 0)
                                            @foreach($typeAttachments as $attachment)
                                            <option value="{{ $attachment->id }}">
                                                {{ $attachment->file_name }}
                                                @if($attachment->replies && $attachment->replies->count() > 0)
                                                ({{ $attachment->replies->count() }} رد)
                                                @endif
                                            </option>
                                            @endforeach
                                            @else
                                            <option value="" disabled>لا توجد ملفات للرد عليها</option>
                                            @endif
                                        </select>
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-upload me-2"></i>رفع الملفات
                                    </button>
                                </form>

                                @php
                                // استخدام المرفقات المفلترة من الـ Controller
                                $typeAttachments = isset($filteredAttachments) ?
                                $filteredAttachments->where('service_type', $type) :
                                collect();

                                $groupedAttachments = $typeAttachments->groupBy(function($attachment) {
                                if ($attachment->task_type == 'template_task' && $attachment->templateTaskUser) {
                                return 'template_task_' . $attachment->template_task_user_id;
                                } elseif ($attachment->task_type == 'regular_task' && $attachment->taskUser) {
                                return 'regular_task_' . $attachment->task_user_id;
                                } else {
                                return 'no_task';
                                }
                                });
                                @endphp

                                @foreach($groupedAttachments as $taskKey => $attachments)
                                @php
                                $firstAttachment = $attachments->first();
                                $taskName = 'بدون مهمة';
                                $taskIcon = 'fas fa-folder';
                                $taskColor = 'text-secondary';
                                $taskClass = 'no-task';

                                if ($taskKey !== 'no_task') {
                                if ($firstAttachment->task_type == 'template_task') {
                                $taskName = 'قالب مهمة: ' . optional(optional($firstAttachment->templateTaskUser)->templateTask)->name;
                                $taskIcon = 'fas fa-clipboard-list';
                                $taskColor = 'text-info';
                                $taskClass = 'template-task';
                                } elseif ($firstAttachment->task_type == 'regular_task') {
                                $taskName = 'مهمة: ' . optional(optional($firstAttachment->taskUser)->task)->title;
                                $taskIcon = 'fas fa-tasks';
                                $taskColor = 'text-success';
                                $taskClass = 'regular-task';
                                }
                                }
                                @endphp

                                <div class="task-group {{ $taskClass }} mb-3">
                                    <div class="task-group-header">
                                        <h6 class="mb-2 {{ $taskColor }}">
                                            <i class="{{ $taskIcon }} me-2"></i>
                                            {{ $taskName }}
                                            <span class="badge bg-primary text-white ms-2">{{ $attachments->count() }} ملف</span>
                                        </h6>
                                    </div>

                                    <div class="attachment-list-container attachment-list-container-400">
                                        <ul class="list-group">
                                            @foreach($attachments as $attachment)
                                            <li class="list-group-item" data-attachment-id="{{ $attachment->id }}">
                                                <div class="d-flex w-100 justify-content-between align-items-start mb-2">
                                                    <div class="d-flex align-items-center flex-grow-1">
                                                        <a href="{{ route('projects.attachments.view', $attachment->id) }}" target="_blank" class="me-2" title="عرض الملف">
                                                            <i class="fas fa-eye text-info"></i>
                                                        </a>
                                                        <a href="{{ route('projects.attachments.view', $attachment->id) }}" target="_blank" class="attachment-name text-decoration-none" title="عرض الملف">
                                                            {{ $attachment->file_name }}
                                                        </a>
                                                    </div>
                                                    <div class="d-flex flex-column align-items-end gap-2" style="min-width: fit-content;">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <!-- Checkbox للتأكيد -->
                                                            <div class="confirmation-checkbox-container"
                                                                data-attachment-id="{{ $attachment->id }}"
                                                                title="تأكيد المرفق">
                                                                <input class="confirmation-checkbox"
                                                                    type="checkbox"
                                                                    id="confirm_{{ $attachment->id }}"
                                                                    data-attachment-id="{{ $attachment->id }}"
                                                                    data-attachment-name="{{ $attachment->file_name }}">
                                                                <label class="m-0"
                                                                    for="confirm_{{ $attachment->id }}">
                                                                    <i class="fas fa-check-circle me-1"></i>تأكيد
                                                                </label>
                                                            </div>

                                                            <div class="dropdown">
                                                                <button class="btn btn-sm btn-link text-muted p-1" type="button"
                                                                    data-bs-toggle="dropdown" aria-expanded="false" title="خيارات المشاركة">
                                                                    <i class="fas fa-ellipsis-v"></i>
                                                                </button>
                                                                <ul class="dropdown-menu dropdown-menu-start">
                                                                    <li>
                                                                        <a class="dropdown-item share-attachment-btn" href="#"
                                                                            data-attachment-id="{{ $attachment->id }}"
                                                                            data-attachment-name="{{ $attachment->file_name }}">
                                                                            <i class="fas fa-share text-primary me-2"></i>
                                                                            مشاركة الملف
                                                                        </a>
                                                                    </li>
                                                                    <li>
                                                                        <a class="dropdown-item view-shares-btn" href="#"
                                                                            data-attachment-id="{{ $attachment->id }}">
                                                                            <i class="fas fa-users text-info me-2"></i>
                                                                            عرض المشاركات
                                                                        </a>
                                                                    </li>
                                                                    <li>
                                                                        <hr class="dropdown-divider">
                                                                    </li>
                                                                    <li>
                                                                        <a class="dropdown-item copy-attachment-link"
                                                                            href="javascript:void(0);"
                                                                            data-attachment-id="{{ $attachment->id }}"
                                                                            data-attachment-url="{{ route('projects.attachments.view', $attachment->id) }}">
                                                                            <i class="fas fa-copy text-warning me-2"></i>
                                                                            نسخ الرابط
                                                                        </a>
                                                                    </li>
                                                                    <li>
                                                                        <a class="dropdown-item" href="{{ route('projects.attachments.download', $attachment->id) }}">
                                                                            <i class="fas fa-download text-success me-2"></i>
                                                                            تحميل
                                                                        </a>
                                                                    </li>

                                                                </ul>
                                                            </div>
                                                        </div>

                                                        <div class="form-check" style="margin-right: 0;">
                                                            <input class="form-check-input attachment-select" type="checkbox"
                                                                value="{{ $attachment->id }}"
                                                                id="attachment_{{ $attachment->id }}">
                                                            <label class="form-check-label" for="attachment_{{ $attachment->id }}"></label>
                                                        </div>
                                                    </div>
                                                </div>
                                                @if($attachment->description)
                                                <small class="text-muted mt-1">{{ $attachment->description }}</small>
                                                @endif
                                                @if($attachment->uploaded_by)
                                                <small class="text-secondary mt-1">
                                                    <i class="fas fa-user"></i>
                                                    رفع بواسطة: {{ optional(\App\Models\User::find($attachment->uploaded_by))->name }}
                                                </small>
                                                @endif
                                                <small class="text-muted mt-1">
                                                    <i class="fas fa-calendar-alt"></i>
                                                    تاريخ الرفع: {{ $attachment->created_at->format('d/m/Y - H:i') }}
                                                    <span class="badge bg-light text-dark ms-1">{{ $attachment->created_at->diffForHumans() }}</span>
                                                </small>

                                                @if($attachment->replies && $attachment->replies->count() > 0)
                                                <small class="text-info mt-1">
                                                    <i class="fas fa-reply"></i>
                                                    {{ $attachment->replies->count() }} رد
                                                </small>
                                                @endif

                                                @if($attachment->replies && $attachment->replies->count() > 0)
                                                <div class="attachment-replies mt-2">
                                                    @foreach($attachment->replies as $reply)
                                                    <div class="reply-item">
                                                        <div class="reply-header">
                                                            <span class="reply-author">
                                                                <i class="fas fa-user me-1"></i>
                                                                {{ optional($reply->user)->name ?? 'مستخدم غير معروف' }}
                                                            </span>
                                                            <span class="reply-date">
                                                                <i class="fas fa-calendar-alt me-1"></i>
                                                                {{ $reply->created_at->format('d/m/Y - H:i') }}
                                                                <span class="badge bg-secondary text-white ms-1" class="badge bg-secondary text-white ms-1 badge-small-text">{{ $reply->created_at->diffForHumans() }}</span>
                                                            </span>
                                                        </div>
                                                        @if($reply->description)
                                                        <div class="reply-description">{{ $reply->description }}</div>
                                                        @endif
                                                        <div class="reply-file">
                                                            <i class="fas fa-paperclip"></i>
                                                            <a href="{{ route('projects.attachments.view', $reply->id) }}" target="_blank">
                                                                {{ $reply->file_name }}
                                                            </a>
                                                            <a href="javascript:void(0);"
                                                                class="btn btn-sm btn-outline-warning copy-attachment-link me-1"
                                                                data-attachment-id="{{ $reply->id }}"
                                                                data-attachment-url="{{ route('projects.attachments.view', $reply->id) }}"
                                                                title="نسخ الرابط">
                                                                <i class="fas fa-copy"></i>
                                                            </a>
                                                            <a href="{{ route('projects.attachments.download', $reply->id) }}" class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-download"></i>
                                                            </a>
                                                        </div>
                                                    </div>
                                                    @endforeach
                                                </div>
                                                @endif
                                            </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                                @endforeach

                                @if($typeAttachments->isEmpty())
                                <div class="text-center text-muted py-3">
                                    <i class="fas fa-folder-open fa-2x mb-2"></i>
                                    <p>لا توجد مرفقات</p>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<!-- قسم مجلدات الخدمات -->
@if(isset($userServices) && count($userServices) > 0)
<div class="row mt-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header services-section-header">
                <h5 class="mb-0">
                    <i class="fas fa-briefcase"></i>
                    مرفقات خدماتي
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    @foreach($userServices ?? [] as $serviceName)
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-header text-center" style="background: linear-gradient(135deg, #17a2b8, #138496); color: white; padding: 12px 20px; font-weight: 600; font-size: 14px; border-radius: 12px 12px 0 0; border: none;">
                                <i class="fas fa-cog me-2" style="opacity: 0.9;"></i>{{ $serviceName }}
                            </div>
                            <div class="card-body">
                                <form class="attachment-upload-form upload-form mb-3" data-service-type="{{ $serviceName }}" action="{{ route('projects.attachments.store', $project->id) }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <input type="hidden" name="service_type" value="{{ $serviceName }}">

                                    <div class="drag-drop-upload-container">
                                        <div class="drag-drop-area" id="dragDropArea{{ str_replace(' ', '', $serviceName) }}">
                                            <div class="drag-drop-message">
                                                <i class="fas fa-cloud-upload-alt mb-2"></i>
                                                <p>اسحب الملفات وأفلتها هنا</p>
                                                <p class="small text-muted">أو</p>
                                                <button type="button" class="file-select-button">اختر ملفات</button>
                                                <input type="file" name="attachment" id="fileInput{{ str_replace(' ', '', $serviceName) }}" class="file-input-hidden">
                                            </div>
                                        </div>

                                        <div class="selected-files-container" id="selectedFiles{{ str_replace(' ', '', $serviceName) }}"></div>
                                    </div>

                                    <div class="mb-2 mt-2">
                                        <input type="text" name="description" class="form-control form-control-sm" placeholder="وصف (اختياري)">
                                    </div>

                                    <!-- Progress Bar Elements for Services -->
                                    <div class="upload-progress-container" id="progress-container-{{ str_replace(' ', '', $serviceName) }}">
                                        <div class="progress-bar-container">
                                            <div class="progress-bar-animated"
                                                id="progress-bar-{{ str_replace(' ', '', $serviceName) }}"
                                                role="progressbar"
                                                aria-valuenow="0"
                                                aria-valuemin="0"
                                                aria-valuemax="100">
                                            </div>
                                        </div>
                                        <div class="progress-text-container">
                                            <span id="percentage-{{ str_replace(' ', '', $serviceName) }}" class="progress-percentage">0%</span>
                                        </div>
                                    </div>

                                    <div class="upload-status alert alert-info" id="status-{{ str_replace(' ', '', $serviceName) }}">
                                        جاري التحضير...
                                    </div>

                                    <!-- اختيار نوع المهمة في فولدرات الخدمات -->
                                    <div class="mb-2">
                                        <label class="form-label small">ربط بمهمة (اختياري):</label>
                                        <select name="task_type" class="form-control form-control-sm task-type-selector" id="taskType{{ str_replace(' ', '', $serviceName) }}">
                                            <option value="">-- بدون ربط بمهمة --</option>
                                            <option value="template_task">مهمة قالب</option>
                                            <option value="regular_task">مهمة عادية</option>
                                        </select>
                                    </div>

                                    <!-- اختيار المهمة القالب - فقط مهام هذه الخدمة -->
                                    <div class="mb-2 template-task-selector d-none" id="templateTaskSelector{{ str_replace(' ', '', $serviceName) }}">
                                        <select name="template_task_id" class="form-control form-control-sm">
                                            <option value="">-- اختر مهمة القالب --</option>
                                            @if(isset($userTasks[auth()->id()]) && $userTasks[auth()->id()]->count() > 0)
                                            @foreach($userTasks[auth()->id()] as $taskUser)
                                            @if(optional($taskUser->templateTask->template->service)->name === $serviceName)
                                            <option value="{{ $taskUser->id }}">
                                                {{ optional($taskUser->templateTask)->name }}
                                            </option>
                                            @endif
                                            @endforeach
                                            @else
                                            <option value="" disabled>لا توجد مهام قوالب لهذه الخدمة</option>
                                            @endif
                                        </select>
                                    </div>

                                    <!-- اختيار المهمة العادية - فقط مهام هذه الخدمة -->
                                    <div class="mb-2 regular-task-selector d-none" id="regularTaskSelector{{ str_replace(' ', '', $serviceName) }}">
                                        <select name="task_user_id" class="form-control form-control-sm">
                                            <option value="">-- اختر المهمة العادية --</option>
                                            @if(isset($userActualTasks[auth()->id()]) && $userActualTasks[auth()->id()]->count() > 0)
                                            @foreach($userActualTasks[auth()->id()] as $taskUser)
                                            @if(optional($taskUser->task->service)->name === $serviceName)
                                            <option value="{{ $taskUser->id }}">
                                                {{ optional($taskUser->task)->title }}
                                            </option>
                                            @endif
                                            @endforeach
                                            @else
                                            <option value="" disabled>لا توجد مهام عادية لهذه الخدمة</option>
                                            @endif
                                        </select>
                                    </div>

                                    <div class="mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="isReply{{ str_replace(' ', '', $serviceName) }}" name="is_reply">
                                            <label class="form-check-label small" for="isReply{{ str_replace(' ', '', $serviceName) }}">
                                                رد على ملف موجود
                                            </label>
                                        </div>
                                    </div>

                                    <div class="mb-2 reply-file-selector d-none">
                                        <select name="parent_attachment_id" class="form-control form-control-sm">
                                            <option value="">-- اختر الملف للرد عليه --</option>
                                            @php
                                            $serviceAttachmentsForReply = $project->attachments->where('service_type', $serviceName)->where('parent_attachment_id', null);
                                            @endphp
                                            @if($serviceAttachmentsForReply->count() > 0)
                                            @foreach($serviceAttachmentsForReply as $attachment)
                                            <option value="{{ $attachment->id }}">
                                                {{ $attachment->file_name }}
                                                @if($attachment->replies && $attachment->replies->count() > 0)
                                                ({{ $attachment->replies->count() }} رد)
                                                @endif
                                            </option>
                                            @endforeach
                                            @else
                                            <option value="" disabled>لا توجد ملفات للرد عليها في هذه الخدمة</option>
                                            @endif
                                        </select>
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-upload me-2"></i>رفع الملفات
                                    </button>
                                </form>

                                @php
                                // استخدام المرفقات المفلترة من الـ Controller
                                $serviceAttachments = isset($filteredAttachments) ?
                                $filteredAttachments->where('service_type', $serviceName) :
                                collect();

                                $groupedServiceAttachments = $serviceAttachments->groupBy(function($attachment) {
                                if ($attachment->task_type == 'template_task' && $attachment->templateTaskUser) {
                                return 'template_task_' . $attachment->template_task_user_id;
                                } elseif ($attachment->task_type == 'regular_task' && $attachment->taskUser) {
                                return 'regular_task_' . $attachment->task_user_id;
                                } else {
                                return 'no_task';
                                }
                                });
                                @endphp

                                @if($serviceAttachments->count() > 0)
                                <div class="attachments-list">
                                    @foreach($groupedServiceAttachments as $taskKey => $attachments)
                                    @php
                                    $firstAttachment = $attachments->first();
                                    $taskName = null;
                                    $taskUser = null;

                                    if ($taskKey !== 'no_task') {
                                    if ($firstAttachment->task_type == 'template_task' && $firstAttachment->templateTaskUser) {
                                    $taskName = optional($firstAttachment->templateTaskUser->templateTask)->name;
                                    $taskUser = optional($firstAttachment->templateTaskUser->user)->name;
                                    } elseif ($firstAttachment->task_type == 'regular_task' && $firstAttachment->taskUser) {
                                    $taskName = optional($firstAttachment->taskUser->task)->title;
                                    $taskUser = optional($firstAttachment->taskUser->user)->name;
                                    }
                                    }
                                    @endphp

                                    @if($taskName)
                                    <div class="task-section mb-3">
                                        <div class="task-header bg-light p-2 rounded">
                                            <small class="text-muted">
                                                <i class="fas fa-tasks me-1"></i>
                                                المهمة: <strong>{{ $taskName }}</strong>
                                                @if($taskUser)
                                                | المسؤول: <strong>{{ $taskUser }}</strong>
                                                @endif
                                            </small>
                                        </div>
                                        @endif

                                        <div class="service-attachment-list-container service-attachment-list-container-350">
                                            <ul class="list-unstyled mt-2">
                                                @foreach($attachments->where('parent_attachment_id', null) as $attachment)
                                                <li class="attachment-item mb-2 p-2 border rounded">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div class="attachment-info">
                                                            <a href="{{ route('projects.attachments.view', $attachment->id) }}" target="_blank" class="text-decoration-none" title="عرض الملف">
                                                                <strong>{{ $attachment->file_name }}</strong>
                                                            </a>
                                                            @if($attachment->description)
                                                            <br><small class="text-muted">{{ $attachment->description }}</small>
                                                            @endif
                                                            <br><small class="text-muted">
                                                                <i class="fas fa-user me-1"></i>بواسطة: {{ optional($attachment->user)->name ?? 'غير معروف' }}
                                                            </small>
                                                            <br><small class="text-muted">
                                                                <i class="fas fa-calendar-alt me-1"></i>{{ $attachment->created_at->format('d/m/Y - H:i') }}
                                                                <span class="badge bg-light text-dark ms-1">{{ $attachment->created_at->diffForHumans() }}</span>
                                                            </small>
                                                        </div>
                                                        <div class="attachment-actions d-flex align-items-center">
                                                            <div class="dropdown me-1">
                                                                <button class="btn btn-sm btn-link text-muted p-1" type="button"
                                                                    data-bs-toggle="dropdown" aria-expanded="false" title="خيارات المشاركة">
                                                                    <i class="fas fa-ellipsis-v"></i>
                                                                </button>
                                                                <ul class="dropdown-menu dropdown-menu-start">
                                                                    <li>
                                                                        <a class="dropdown-item share-attachment-btn" href="#"
                                                                            data-attachment-id="{{ $attachment->id }}"
                                                                            data-attachment-name="{{ $attachment->file_name }}">
                                                                            <i class="fas fa-share text-primary me-2"></i>
                                                                            مشاركة الملف
                                                                        </a>
                                                                    </li>
                                                                    <li>
                                                                        <a class="dropdown-item view-shares-btn" href="#"
                                                                            data-attachment-id="{{ $attachment->id }}">
                                                                            <i class="fas fa-users text-info me-2"></i>
                                                                            عرض المشاركات
                                                                        </a>
                                                                    </li>
                                                                    <li>
                                                                        <hr class="dropdown-divider">
                                                                    </li>
                                                                    <li>
                                                                        <a class="dropdown-item copy-attachment-link"
                                                                            href="javascript:void(0);"
                                                                            data-attachment-id="{{ $attachment->id }}"
                                                                            data-attachment-url="{{ route('projects.attachments.view', $attachment->id) }}">
                                                                            <i class="fas fa-copy text-warning me-2"></i>
                                                                            نسخ الرابط
                                                                        </a>
                                                                    </li>
                                                                    <li>
                                                                        <a class="dropdown-item" href="{{ route('projects.attachments.download', $attachment->id) }}">
                                                                            <i class="fas fa-download text-success me-2"></i>
                                                                            تحميل
                                                                        </a>
                                                                    </li>
                                                                </ul>
                                                            </div>

                                                            <div class="form-check">
                                                                <input class="form-check-input attachment-select" type="checkbox"
                                                                    value="{{ $attachment->id }}"
                                                                    id="service_attachment_{{ $attachment->id }}">
                                                                <label class="form-check-label" for="service_attachment_{{ $attachment->id }}"></label>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- الردود على هذا المرفق -->
                                                    @if($attachment->replies->count() > 0)
                                                    <div class="replies-section mt-2 ps-3 border-start">
                                                        @foreach($attachment->replies as $reply)
                                                        <div class="reply-item mb-1 p-1 bg-light rounded">
                                                            <small>
                                                                <strong>رد:</strong> {{ $reply->file_name }}
                                                                @if($reply->description)
                                                                - {{ $reply->description }}
                                                                @endif
                                                                <br>
                                                                <i class="fas fa-user me-1"></i>بواسطة: {{ optional($reply->user)->name ?? 'غير معروف' }}
                                                                <br><i class="fas fa-calendar-alt me-1"></i>{{ $reply->created_at->format('d/m/Y - H:i') }}
                                                                <span class="badge bg-secondary text-white ms-1" class="badge bg-secondary text-white ms-1 badge-small-text">{{ $reply->created_at->diffForHumans() }}</span>
                                                            </small>
                                                            <div class="reply-actions d-flex align-items-center">
                                                                <div class="dropdown me-1">
                                                                    <button class="btn btn-xs btn-link text-muted p-1" type="button"
                                                                        data-bs-toggle="dropdown" aria-expanded="false" title="خيارات المشاركة">
                                                                        <i class="fas fa-ellipsis-v"></i>
                                                                    </button>
                                                                    <ul class="dropdown-menu dropdown-menu-start">
                                                                        <li>
                                                                            <a class="dropdown-item share-attachment-btn" href="#"
                                                                                data-attachment-id="{{ $reply->id }}"
                                                                                data-attachment-name="{{ $reply->file_name }}">
                                                                                <i class="fas fa-share text-primary me-2"></i>
                                                                                مشاركة الملف
                                                                            </a>
                                                                        </li>
                                                                        <li>
                                                                            <a class="dropdown-item view-shares-btn" href="#"
                                                                                data-attachment-id="{{ $reply->id }}">
                                                                                <i class="fas fa-users text-info me-2"></i>
                                                                                عرض المشاركات
                                                                            </a>
                                                                        </li>
                                                                        <li>
                                                                            <hr class="dropdown-divider">
                                                                        </li>
                                                                        <li>
                                                                            <a class="dropdown-item copy-attachment-link"
                                                                                href="javascript:void(0);"
                                                                                data-attachment-id="{{ $reply->id }}"
                                                                                data-attachment-url="{{ route('projects.attachments.view', $reply->id) }}">
                                                                                <i class="fas fa-copy text-warning me-2"></i>
                                                                                نسخ الرابط
                                                                            </a>
                                                                        </li>
                                                                        <li>
                                                                            <a class="dropdown-item" href="{{ route('projects.attachments.download', $reply->id) }}">
                                                                                <i class="fas fa-download text-success me-2"></i>
                                                                                تحميل
                                                                            </a>
                                                                        </li>
                                                                    </ul>
                                                                </div>

                                                                <div class="form-check">
                                                                    <input class="form-check-input attachment-select" type="checkbox"
                                                                        value="{{ $reply->id }}"
                                                                        id="service_reply_{{ $reply->id }}">
                                                                    <label class="form-check-label" for="service_reply_{{ $reply->id }}"></label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        @endforeach
                                                    </div>
                                                    @endif
                                                </li>
                                                @endforeach
                                            </ul>
                                        </div>

                                        @if($taskName)
                                    </div>
                                    @endif
                                    @endforeach
                                </div>
                                @else
                                <div class="text-center text-muted py-3">
                                    <i class="fas fa-folder-open fa-2x mb-2"></i>
                                    <p>لا توجد مرفقات لهذه الخدمة</p>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endif



<!-- File Sharing Sidebar -->
<div id="shareSidebar" class="share-sidebar">
    <div class="sidebar-overlay" id="shareSidebarOverlay" onclick="closeShareSidebar()"></div>
    <div class="sidebar-content">
        <!-- Sidebar Header -->
        <div class="sidebar-header">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="sidebar-icon bg-primary">
                        <i class="fas fa-share"></i>
                    </div>
                    <div class="ms-3">
                        <h5 class="mb-0" id="shareSidebarTitle">مشاركة الملفات</h5>
                        <small class="text-muted" id="shareSidebarSubtitle">شارك الملفات مع المستخدمين</small>
                    </div>
                </div>
                <button class="btn btn-sm btn-outline-secondary" onclick="closeShareSidebar()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <!-- Sidebar Content -->
        <div class="sidebar-body" id="shareSidebarContent">
            <!-- Selected Files -->
            <div class="mb-4">
                <label class="form-label fw-semibold text-muted mb-2">
                    <i class="fas fa-files-o me-1"></i>
                    الملفات المختارة
                </label>
                <div class="selected-files-list p-3 rounded">
                    <!-- سيتم ملؤها بـ JavaScript -->
                </div>
            </div>

            <!-- Users Selection -->
            <div class="mb-4">
                <label class="form-label fw-semibold text-muted mb-2">
                    <i class="fas fa-users me-1"></i>
                    اختر المستخدمين للمشاركة
                </label>
                <div class="mb-3">
                    <div class="d-flex gap-2">
                        <input type="text" class="form-control" id="userSearchInput" placeholder="ابحث عن مستخدم...">
                        <button type="button" class="btn btn-outline-primary" id="addUserBtn" disabled>
                            <i class="fas fa-plus me-1"></i>
                            إضافة
                        </button>
                    </div>
                    <div class="search-results mt-2" id="searchResults">
                        <!-- نتائج البحث -->
                    </div>
                </div>
                <div class="selected-users-container mt-3 d-none">
                    <h6 class="text-primary">المستخدمين المختارين:</h6>
                    <div class="selected-users-list d-flex flex-wrap gap-2">
                        <!-- سيتم ملؤها بـ JavaScript -->
                    </div>
                </div>
            </div>

            <!-- Share Settings -->
            <div class="mb-4">
                <label class="form-label fw-semibold text-muted mb-2">
                    <i class="fas fa-cog me-1"></i>
                    خيارات المشاركة
                </label>
                <div class="row g-3">

                    <div class="col-12">
                        <label for="shareExpiration" class="form-label">مدة الصلاحية:</label>
                        <select class="form-select" id="shareExpiration">
                            <option value="">بلا انتهاء</option>
                            <option value="1">ساعة واحدة</option>
                            <option value="6">6 ساعات</option>
                            <option value="24">يوم واحد</option>
                            <option value="168">أسبوع واحد</option>
                            <option value="720">شهر واحد</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label for="shareDescription" class="form-label">وصف المشاركة (اختياري):</label>
                        <input type="text" class="form-control" id="shareDescription"
                            placeholder="مثال: مستندات المشروع الأساسية">
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="d-flex gap-2 pt-3">
                <button type="button" class="btn btn-primary px-4 py-2" id="confirmShareBtn">
                    <i class="fas fa-share me-1"></i>
                    <span class="btn-text">مشاركة الملفات</span>
                    <span class="spinner-border spinner-border-sm ms-2 d-none"></span>
                </button>
                <button type="button" class="btn btn-outline-secondary px-4 py-2" onclick="closeShareSidebar()">
                    <i class="fas fa-times me-1"></i>
                    إلغاء
                </button>
            </div>
        </div>
    </div>
</div>

<!-- File Shares Sidebar -->
<div id="viewSharesSidebar" class="view-shares-sidebar" style="
    position: fixed;
    top: 0;
    right: -650px;
    width: 630px;
    height: 100vh;
    background: #ffffff;
    z-index: 1050;
    transition: right 0.4s cubic-bezier(0.4, 0.0, 0.2, 1);
    box-shadow: 0 8px 40px rgba(0,0,0,0.12);
    overflow-y: auto;
    border-left: 1px solid #e1e5e9;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    scrollbar-width: none;
    -ms-overflow-style: none;
">
    <!-- Sidebar Header -->
    <div class="sidebar-header" style="
        background: linear-gradient(135deg, #17a2b8, #138496);
        color: white;
        padding: 24px 32px 16px 32px;
        border-bottom: 1px solid #e9ecef;
        position: sticky;
        top: 0;
        z-index: 10;
    ">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="flex-grow-1">
                <h4 id="viewSharesSidebarTitle" class="mb-2" style="font-weight: 600; color: white; font-size: 1.6rem; line-height: 1.3;">
                    <i class="fas fa-users me-2"></i>
                    مشاركات الملف
                </h4>
                <p id="viewSharesSidebarSubtitle" class="mb-0" style="font-size: 14px; color: rgba(255,255,255,0.8); margin: 0;">عرض جميع مشاركات الملف مع المستخدمين</p>
            </div>
            <button onclick="closeViewSharesSidebar()" class="btn rounded-circle p-2" style="
                width: 40px;
                height: 40px;
                border: none;
                background: rgba(255,255,255,0.2);
                color: white;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                backdrop-filter: blur(10px);
            ">
                <i class="fas fa-times" style="font-size: 16px;"></i>
            </button>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span id="viewSharesSidebarBadge" class="badge" style="background: linear-gradient(135deg, #6c757d, #495057); color: white; padding: 8px 16px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                <i class="fas fa-share-alt me-1"></i>مشاركات نشطة
            </span>
        </div>
    </div>

    <!-- Sidebar Content -->
    <div id="viewSharesSidebarContent" style="padding: 0 24px 24px 24px; min-height: calc(100vh - 180px);">
        <div class="shares-list-container">
            <!-- سيتم ملؤها بـ JavaScript -->
        </div>
    </div>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer" style="
        background: #f8f9fa;
        border-top: 1px solid #e9ecef;
        padding: 20px 32px;
        position: sticky;
        bottom: 0;
        z-index: 10;
    ">
        <div class="d-flex gap-3 justify-content-end">
            <button type="button" class="btn btn-secondary" onclick="closeViewSharesSidebar()">
                <i class="fas fa-times me-2"></i>إغلاق
            </button>
        </div>
    </div>
</div>

<!-- Sidebar Overlay -->
<div id="viewSharesSidebarOverlay" class="view-shares-sidebar-overlay" style="
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0,0,0,0.5);
    z-index: 1040;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
" onclick="closeViewSharesSidebar()"></div>

<div class="modal fade" id="shareResultModal" tabindex="-1" aria-labelledby="shareResultModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="shareResultModalLabel">
                    <i class="fas fa-check-circle me-2"></i>
                    تم إنشاء المشاركة بنجاح
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <i class="fas fa-link fa-3x text-success mb-3"></i>
                    <h6>تم إنشاء رابط المشاركة بنجاح</h6>
                </div>

                <div class="share-link-container mb-3">
                    <label class="form-label">رابط المشاركة:</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="shareUrlInput" readonly>
                        <button class="btn btn-outline-primary" type="button" id="copyShareUrlBtn">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>

                <div class="share-details">
                    <small class="text-muted">
                        <div><strong>عدد الملفات:</strong> <span id="sharedFilesCount">-</span></div>
                        <div><strong>مشارك مع:</strong> <span id="sharedWithCount">-</span> مستخدم</div>
                        <div><strong>ينتهي في:</strong> <span id="shareExpiryDate">-</span></div>
                    </small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                    <i class="fas fa-check me-1"></i>
                    تم
                </button>
            </div>
        </div>
    </div>
</div>