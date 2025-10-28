// Project Revisions Sidebar Functions
let currentProjectId = null;
let isLoadingProjectRevisions = false;

/**
 * 🎯 جلب المراجعين فقط (hierarchy_level = 2) من المشاركين في المشروع
 */
async function loadReviewersForResponsibility() {
    const selectElement = document.getElementById('responsibleUserId');

    if (!selectElement) {
        console.error('❌ عنصر responsibleUserId غير موجود');
        return;
    }

    try {
        if (!currentProjectId) {
            console.error('❌ لا يوجد معرف مشروع');
            return;
        }

        // إظهار حالة التحميل
        selectElement.innerHTML = '<option value="">⏳ جاري التحميل...</option>';
        selectElement.disabled = true;

        const response = await fetch(`/task-revisions/reviewers-only?project_id=${currentProjectId}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        const result = await response.json();

        // مسح حالة التحميل
        selectElement.innerHTML = '';
        selectElement.disabled = false;

        // إضافة الـ option الافتراضي
        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.textContent = '-- اختر الشخص المسؤول --';
        selectElement.appendChild(defaultOption);

        if (result.success && result.reviewers && result.reviewers.length > 0) {
            // إضافة المستخدمين المتاحين
            result.reviewers.forEach(reviewer => {
                const option = document.createElement('option');
                option.value = reviewer.id;
                option.textContent = reviewer.name;
                selectElement.appendChild(option);
            });

            // Log مختلف حسب نوع القائمة
            if (result.is_restricted) {
                console.log(`✅ تم تحميل ${result.reviewers.length} مراجع فقط (لأنك coordination-team-employee أو technical_reviewer)`);
            } else {
                console.log(`✅ تم تحميل ${result.reviewers.length} مشارك من المشروع`);
            }
        } else {
            // لا يوجد مستخدمين متاحين
            const noUsersOption = document.createElement('option');
            noUsersOption.value = '';

            if (result.is_restricted) {
                noUsersOption.textContent = 'لا يوجد مراجعين في المشروع';
                console.warn('⚠️ لا يوجد مراجعين (hierarchy_level = 2) في المشروع');
            } else {
                noUsersOption.textContent = 'لا يوجد مشاركين في المشروع';
                console.warn('⚠️ لا يوجد مشاركين في المشروع');
            }

            noUsersOption.disabled = true;
            selectElement.appendChild(noUsersOption);
        }

    } catch (error) {
        console.error('❌ خطأ في جلب المراجعين:', error);

        // في حالة الخطأ
        selectElement.innerHTML = '';
        selectElement.disabled = false;

        const errorOption = document.createElement('option');
        errorOption.value = '';
        errorOption.textContent = 'حدث خطأ في التحميل';
        errorOption.disabled = true;
        selectElement.appendChild(errorOption);
    }
}

function openProjectRevisionsSidebar(projectId) {
    const sidebar = document.getElementById('projectRevisionsSidebar');
    const overlay = document.getElementById('revisionsOverlay');

    if (!sidebar || !overlay) {
        console.error('Project revisions sidebar elements not found');
        return;
    }

    // Store current project ID
    currentProjectId = projectId;

    // Show overlay
    overlay.style.visibility = 'visible';
    overlay.style.opacity = '1';

    // Show sidebar
    sidebar.style.right = '0';

    // Add sidebar-open class to body to prevent horizontal scrolling
    document.body.classList.add('sidebar-open');
    document.documentElement.classList.add('sidebar-open');

    // Load project revisions
    loadProjectRevisions(projectId);
}

function closeProjectRevisionsSidebar() {
    const sidebar = document.getElementById('projectRevisionsSidebar');
    const overlay = document.getElementById('revisionsOverlay');

    if (!sidebar || !overlay) return;

    // Hide sidebar
    sidebar.style.right = '-500px';

    // Hide overlay
    overlay.style.opacity = '0';
    setTimeout(() => {
        overlay.style.visibility = 'hidden';
    }, 300);

    // Remove sidebar-open class from body
    document.body.classList.remove('sidebar-open');
    document.documentElement.classList.remove('sidebar-open');

    // Reset variables
    currentProjectId = null;
}

async function loadProjectRevisions(projectId, serviceId = null) {
    if (isLoadingProjectRevisions) {
        return;
    }

    isLoadingProjectRevisions = true;
    const container = document.getElementById('projectRevisionsContainer');

    if (!container) {
        isLoadingProjectRevisions = false;
        return;
    }

    // Show loading state
    container.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">جاري التحميل...</span>
            </div>
            <p class="mt-2 text-muted mb-0" style="font-size: 12px;">جاري تحميل تعديلات المشروع...</p>
        </div>
    `;

    try {
        // بناء الـ URL مع الـ service_id filter
        let url = `/project-revisions/${projectId}/all`;
        if (serviceId) {
            url += `?service_id=${serviceId}`;
        }

        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        const result = await response.json();

        if (result.success) {
            displayProjectRevisions(result.revisions);
            updateRevisionCounts(result.revisions, serviceId);
        } else {
            showProjectRevisionsError('حدث خطأ في تحميل تعديلات المشروع');
        }

    } catch (error) {
        console.error('Error loading project revisions:', error);
        showProjectRevisionsError('فشل في تحميل تعديلات المشروع');
    } finally {
        isLoadingProjectRevisions = false;
    }
}

// تحديث عدادات التعديلات
function updateRevisionCounts(revisions, currentServiceId = null) {
    if (!currentServiceId) {
        // عداد الكل - دائماً نحدثه لما نعرض كل التعديلات
        const allRevisionsCount = document.getElementById('allRevisionsCount');
        if (allRevisionsCount) {
            allRevisionsCount.textContent = revisions.length;
        }

        // حساب عدد التعديلات لكل خدمة
        const serviceCounts = {};

        // نعد كل التعديلات حسب الخدمة
        revisions.forEach(revision => {
            if (revision.service_id) {
                serviceCounts[revision.service_id] = (serviceCounts[revision.service_id] || 0) + 1;
            }
        });

        // تحديث كل العدادات (حتى لو 0)
        const allServiceBadges = document.querySelectorAll('[id^="service"][id$="RevisionsCount"]');
        allServiceBadges.forEach(badge => {
            const serviceId = badge.id.replace('service', '').replace('RevisionsCount', '');
            badge.textContent = serviceCounts[serviceId] || 0;
        });
    }
}

function displayProjectRevisions(revisions) {
    const container = document.getElementById('projectRevisionsContainer');
    if (!container) return;

    if (!revisions || revisions.length === 0) {
        container.innerHTML = `
            <div class="text-center py-5">
                <i class="fas fa-clipboard-list text-muted" style="font-size: 48px; opacity: 0.5;"></i>
                <h6 class="mt-3 text-muted">لا توجد تعديلات للمشروع</h6>
                <p class="text-muted mb-0" style="font-size: 14px;">اضغط "إضافة تعديل" لإنشاء أول تعديل</p>
            </div>
        `;
        return;
    }

    const revisionsHtml = revisions.map(revision => {
        const statusColor = getRevisionStatusColor(revision.status);
        const statusText = getRevisionStatusText(revision.status);
        const revisionTypeText = getRevisionTypeText(revision.revision_type);
        const revisionTypeColor = getRevisionTypeColor(revision.revision_type);
        const revisionSourceText = getRevisionSourceText(revision.revision_source);
        const revisionSourceColor = getRevisionSourceColor(revision.revision_source);
        const revisionSourceIcon = getRevisionSourceIcon(revision.revision_source);

        const createdDate = new Date(revision.revision_date).toLocaleDateString('ar-EG', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });

        return `
            <div class="revision-card mb-3" data-revision-id="${revision.id}" data-status="${revision.status}">
                <div class="revision-header">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="revision-info flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-${revisionTypeColor} revision-type-badge">${revisionTypeText}</span>
                                <span class="badge bg-${revisionSourceColor} revision-source-badge">
                                    <i class="${revisionSourceIcon} me-1"></i>${revisionSourceText}
                                </span>
                                <span class="badge bg-${statusColor} revision-status">${statusText}</span>
                            </div>
                            <h6 class="revision-title">${revision.title}</h6>
                            <p class="revision-description">${revision.description}</p>
                            ${revision.notes ? `
                                <div class="revision-notes">
                                    <i class="fas fa-sticky-note me-1"></i>
                                    <small>${revision.notes}</small>
                                </div>
                            ` : ''}
                            ${revision.assigned_user ? `
                                <div class="revision-assigned">
                                    <i class="fas fa-user-tag me-1 text-info"></i>
                                    <small class="text-info">مرتبط بـ: ${revision.assigned_user.name}</small>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>

                ${revision.attachment_path || revision.attachment_link ? `
                    <div class="revision-attachment">
                        ${revision.attachment_type === 'link' && revision.attachment_link ? `
                            <a href="${revision.attachment_link}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-info">
                                <i class="fas fa-external-link-alt me-1"></i>
                                فتح الرابط
                            </a>
                        ` : revision.attachment_path ? `
                            <a href="/task-revisions/${revision.id}/download" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-download me-1"></i>
                                ${revision.attachment_name}
                            </a>
                        ` : ''}
                    </div>
                ` : ''}

                <div class="revision-footer">
                    <div class="revision-meta">
                        <small class="text-muted">
                            <i class="fas fa-user me-1"></i>${revision.creator ? revision.creator.name : 'غير معروف'}
                            <i class="fas fa-clock me-1 ms-2"></i>${createdDate}
                            ${revision.actual_minutes ? `
                                <i class="fas fa-stopwatch me-1 ms-2"></i>
                                ${formatRevisionTime(revision.actual_minutes)}
                            ` : ''}
                        </small>
                    </div>

                    ${isRevisionAssignedToCurrentUser(revision) ? `
                        <div class="revision-actions">
                            ${getRevisionActionButtons(revision)}
                        </div>
                    ` : ''}
                </div>

                ${revision.status !== 'pending' && revision.reviewer ? `
                    <div class="revision-review">
                        <small class="text-muted">
                            <i class="fas fa-user-check me-1"></i>
                            تمت المراجعة بواسطة: ${revision.reviewer.name}
                            ${revision.review_notes ? `<br><i class="fas fa-comment me-1"></i>${revision.review_notes}` : ''}
                        </small>
                    </div>
                ` : ''}
            </div>
        `;
    }).join('');

    container.innerHTML = revisionsHtml;
}

function showAddProjectRevisionForm() {
    const form = document.getElementById('addProjectRevisionForm');
    if (form) {
        // Clear form
        document.getElementById('revisionType').value = 'project';
        document.getElementById('revisionSource').value = 'internal';
        document.getElementById('assignedTo').value = '';
        document.getElementById('projectRevisionTitle').value = '';
        document.getElementById('projectRevisionDescription').value = '';
        document.getElementById('projectRevisionNotes').value = '';
        document.getElementById('projectRevisionAttachment').value = '';

        // تنظيف الحقول الجديدة للمسؤولية ⭐
        document.getElementById('responsibleUserId').value = '';
        document.getElementById('executorUserId').value = '';
        document.getElementById('reviewerUserId').value = '';
        document.getElementById('responsibilityNotes').value = '';
        document.getElementById('serviceId').value = '';

        // 🎯 جلب المراجعين فقط لملء dropdown المسؤول عن الخطأ
        loadReviewersForResponsibility();

        // Show form
        form.style.display = 'block';
        document.getElementById('projectRevisionTitle').focus();
    }
}

// تحميل التعديلات حسب الخدمة
let currentServiceFilter = null;

function loadProjectRevisionsByService(serviceId) {
    currentServiceFilter = serviceId;
    loadProjectRevisions(currentProjectId, serviceId);
}

// تم إزالة function toggleRevisionOptions لأن نوع التعديل دائماً "project"

function hideAddProjectRevisionForm() {
    const form = document.getElementById('addProjectRevisionForm');
    if (form) {
        form.style.display = 'none';
    }
}

// Toggle between file upload and link input for project revisions
function toggleProjectRevisionAttachmentType(type) {
    const fileContainer = document.getElementById('projectRevisionFileUploadContainer');
    const linkContainer = document.getElementById('projectRevisionLinkInputContainer');

    if (type === 'file') {
        fileContainer.style.display = 'block';
        linkContainer.style.display = 'none';
        // Clear link input
        const linkInput = document.getElementById('projectRevisionAttachmentLink');
        if (linkInput) linkInput.value = '';
    } else if (type === 'link') {
        fileContainer.style.display = 'none';
        linkContainer.style.display = 'block';
        // Clear file input
        const fileInput = document.getElementById('projectRevisionAttachment');
        if (fileInput) fileInput.value = '';
    }
}

async function saveProjectRevision() {
    const revisionType = document.getElementById('revisionType').value;
    const revisionSource = document.getElementById('revisionSource').value;
    const assignedTo = document.getElementById('assignedTo').value;
    const serviceId = document.getElementById('serviceId').value;
    const title = document.getElementById('projectRevisionTitle').value.trim();
    const description = document.getElementById('projectRevisionDescription').value.trim();
    const notes = document.getElementById('projectRevisionNotes').value.trim();
    const attachmentInput = document.getElementById('projectRevisionAttachment');

    // الحقول الجديدة للمسؤولية ⭐
    const responsibleUserId = document.getElementById('responsibleUserId').value;
    const executorUserId = document.getElementById('executorUserId').value;
    const reviewerUserId = document.getElementById('reviewerUserId').value;
    const responsibilityNotes = document.getElementById('responsibilityNotes').value.trim();

    if (!title) {
        if (typeof Swal !== 'undefined') {
            Swal.fire('خطأ', 'عنوان التعديل مطلوب', 'error');
        } else {
            alert('عنوان التعديل مطلوب');
        }
        return;
    }

    if (!description) {
        if (typeof Swal !== 'undefined') {
            Swal.fire('خطأ', 'وصف التعديل مطلوب', 'error');
        } else {
            alert('وصف التعديل مطلوب');
        }
        return;
    }

    const formData = new FormData();
    formData.append('revision_type', revisionType);
    formData.append('revision_source', revisionSource);

    // إضافة معرف المشروع فقط إذا كان التعديل خاص بالمشروع
    if (revisionType === 'project') {
        formData.append('project_id', currentProjectId);
    }

    // إضافة الشخص المحدد إذا تم اختياره
    if (assignedTo) {
        formData.append('assigned_to', assignedTo);
    }

    // إضافة الخدمة المرتبطة
    if (serviceId) {
        formData.append('service_id', serviceId);
    }

    // إضافة الحقول الجديدة للمسؤولية ⭐
    if (responsibleUserId) {
        formData.append('responsible_user_id', responsibleUserId);
    }
    if (executorUserId) {
        formData.append('executor_user_id', executorUserId);
    }
    if (reviewerUserId) {
        formData.append('assigned_reviewer_id', reviewerUserId);
    }
    if (responsibilityNotes) {
        formData.append('responsibility_notes', responsibilityNotes);
    }

    formData.append('title', title);
    formData.append('description', description);
    if (notes) {
        formData.append('notes', notes);
    }

    // Check attachment type
    const attachmentType = document.querySelector('input[name="projectRevisionAttachmentType"]:checked').value;

    if (attachmentType === 'file' && attachmentInput.files[0]) {
        formData.append('attachment', attachmentInput.files[0]);
        formData.append('attachment_type', 'file');
    } else if (attachmentType === 'link') {
        const linkInput = document.getElementById('projectRevisionAttachmentLink');
        const link = linkInput ? linkInput.value.trim() : '';

        if (link) {
            // Validate URL
            try {
                new URL(link);
                formData.append('attachment_link', link);
                formData.append('attachment_type', 'link');
            } catch (e) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire('خطأ', 'الرجاء إدخال رابط صحيح يبدأ بـ http:// أو https://', 'error');
                } else {
                    alert('الرجاء إدخال رابط صحيح يبدأ بـ http:// أو https://');
                }
                return;
            }
        }
    }

    try {
        const response = await fetch('/project-revisions', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            hideAddProjectRevisionForm();
            loadProjectRevisions(currentProjectId);

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'تم بنجاح!',
                    text: 'تم إضافة تعديل المشروع بنجاح',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        } else {
            console.error('Error saving project revision:', result.message);
            if (typeof Swal !== 'undefined') {
                Swal.fire('خطأ', result.message || 'حدث خطأ في حفظ التعديل', 'error');
            } else {
                alert(result.message || 'حدث خطأ في حفظ التعديل');
            }
        }

    } catch (error) {
        console.error('Error saving project revision:', error);
        if (typeof Swal !== 'undefined') {
            Swal.fire('خطأ', 'حدث خطأ في حفظ التعديل', 'error');
        } else {
            alert('حدث خطأ في حفظ التعديل');
        }
    }
}

async function deleteProjectRevision(revisionId) {
    const confirmResult = typeof Swal !== 'undefined' ?
        await Swal.fire({
            title: 'تأكيد الحذف',
            text: 'هل أنت متأكد من حذف هذا التعديل؟',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'نعم، احذف',
            cancelButtonText: 'إلغاء'
        }) :
        { isConfirmed: confirm('هل أنت متأكد من حذف هذا التعديل؟') };

    if (!confirmResult.isConfirmed) {
        return;
    }

    try {
        const response = await fetch(`/task-revisions/${revisionId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        const result = await response.json();

        if (result.success) {
            loadProjectRevisions(currentProjectId);

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'تم الحذف!',
                    text: 'تم حذف التعديل بنجاح',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        } else {
            if (typeof Swal !== 'undefined') {
                Swal.fire('خطأ', result.message || 'حدث خطأ في حذف التعديل', 'error');
            } else {
                alert(result.message || 'حدث خطأ في حذف التعديل');
            }
        }

    } catch (error) {
        console.error('Error deleting project revision:', error);
        if (typeof Swal !== 'undefined') {
            Swal.fire('خطأ', 'حدث خطأ في حذف التعديل', 'error');
        } else {
            alert('حدث خطأ في حذف التعديل');
        }
    }
}

function showProjectRevisionsError(message) {
    const container = document.getElementById('projectRevisionsContainer');
    if (container) {
        container.innerHTML = `
            <div class="text-center py-4 text-danger">
                <i class="fas fa-exclamation-triangle" style="font-size: 32px;"></i>
                <h6 class="mt-2">${message}</h6>
                <button class="btn btn-outline-primary btn-sm mt-2" onclick="loadProjectRevisions(currentProjectId)">
                    <i class="fas fa-refresh me-1"></i>إعادة المحاولة
                </button>
            </div>
        `;
    }
}

// Helper functions
function getRevisionStatusColor(status) {
    switch(status) {
        // Work statuses
        case 'new': return 'secondary';
        case 'in_progress': return 'primary';
        case 'paused': return 'warning';
        case 'completed': return 'success';
        // Legacy/Approval statuses
        case 'approved': return 'success';
        case 'rejected': return 'danger';
        case 'pending': return 'warning';
        default: return 'secondary';
    }
}

function getRevisionStatusText(status) {
    switch(status) {
        // Work statuses
        case 'new': return 'جديد';
        case 'in_progress': return 'جاري العمل';
        case 'paused': return 'متوقف';
        case 'completed': return 'مكتمل';
        // Legacy/Approval statuses
        case 'approved': return 'موافق عليه';
        case 'rejected': return 'مرفوض';
        case 'pending': return 'في الانتظار';
        default: return 'غير محدد';
    }
}

function getRevisionTypeText(type) {
    switch(type) {
        case 'project': return 'تعديل مشروع';
        case 'general': return 'تعديل عام';
        case 'task': return 'تعديل مهمة';
        default: return 'غير محدد';
    }
}

function getRevisionTypeColor(type) {
    switch(type) {
        case 'project': return 'primary';
        case 'general': return 'info';
        case 'task': return 'secondary';
        default: return 'light';
    }
}

// Helper functions for revision source
function getRevisionSourceText(source) {
    switch(source) {
        case 'internal': return 'داخلي';
        case 'external': return 'خارجي';
        default: return 'غير محدد';
    }
}

function getRevisionSourceColor(source) {
    switch(source) {
        case 'internal': return 'success';
        case 'external': return 'warning';
        default: return 'secondary';
    }
}

function getRevisionSourceIcon(source) {
    switch(source) {
        case 'internal': return 'fas fa-users';
        case 'external': return 'fas fa-external-link-alt';
        default: return 'fas fa-question';
    }
}

// Get action buttons based on revision status
function getRevisionActionButtons(revision) {
    let buttons = '';

    switch(revision.status) {
        case 'new':
            buttons = `
                <button class="btn btn-sm btn-success" onclick="startRevisionWork(${revision.id})" title="بدء العمل">
                    <i class="fas fa-play"></i>
                    <span class="ms-1">بدء</span>
                </button>
            `;
            break;

        case 'in_progress':
            buttons = `
                <button class="btn btn-sm btn-warning" onclick="pauseRevisionWork(${revision.id})" title="إيقاف مؤقت">
                    <i class="fas fa-pause"></i>
                    <span class="ms-1">إيقاف</span>
                </button>
                <button class="btn btn-sm btn-success" onclick="completeRevisionWork(${revision.id})" title="إكمال">
                    <i class="fas fa-check"></i>
                    <span class="ms-1">إكمال</span>
                </button>
            `;
            break;

        case 'paused':
            buttons = `
                <button class="btn btn-sm btn-primary" onclick="resumeRevisionWork(${revision.id})" title="استئناف">
                    <i class="fas fa-play"></i>
                    <span class="ms-1">استئناف</span>
                </button>
                <button class="btn btn-sm btn-success" onclick="completeRevisionWork(${revision.id})" title="إكمال">
                    <i class="fas fa-check"></i>
                    <span class="ms-1">إكمال</span>
                </button>
            `;
            break;

        case 'completed':
            buttons = `
                <span class="badge bg-success">
                    <i class="fas fa-check-circle me-1"></i>مكتمل
                </span>
            `;
            break;
    }

    return buttons;
}

// Format revision time
function formatRevisionTime(minutes) {
    if (!minutes || minutes < 1) return '0 دقيقة';

    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;

    let result = '';
    if (hours > 0) {
        result += `${hours} ساعة`;
        if (mins > 0) {
            result += ` و ${mins} دقيقة`;
        }
    } else {
        result = `${mins} دقيقة`;
    }

    return result;
}

// Start revision work
async function startRevisionWork(revisionId) {
    try {
        const response = await fetch(`/task-revisions/${revisionId}/start`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        const result = await response.json();

        if (result.success) {
            loadProjectRevisions(currentProjectId);

            if (typeof toastr !== 'undefined') {
                toastr.success('تم بدء العمل على التعديل');
            } else if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'تم!',
                    text: result.message,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        } else {
            if (typeof toastr !== 'undefined') {
                toastr.error(result.message);
            } else if (typeof Swal !== 'undefined') {
                Swal.fire('خطأ', result.message, 'error');
            } else {
                alert(result.message);
            }
        }
    } catch (error) {
        console.error('Error starting revision work:', error);
        if (typeof toastr !== 'undefined') {
            toastr.error('حدث خطأ في بدء العمل');
        }
    }
}

// Pause revision work
async function pauseRevisionWork(revisionId) {
    try {
        const response = await fetch(`/task-revisions/${revisionId}/pause`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        const result = await response.json();

        if (result.success) {
            loadProjectRevisions(currentProjectId);

            if (typeof toastr !== 'undefined') {
                toastr.info(`تم الإيقاف المؤقت. الوقت المستغرق: ${result.session_minutes} دقيقة`);
            } else if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'تم الإيقاف!',
                    text: result.message,
                    icon: 'info',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        } else {
            if (typeof toastr !== 'undefined') {
                toastr.error(result.message);
            }
        }
    } catch (error) {
        console.error('Error pausing revision work:', error);
        if (typeof toastr !== 'undefined') {
            toastr.error('حدث خطأ في الإيقاف المؤقت');
        }
    }
}

// Resume revision work
async function resumeRevisionWork(revisionId) {
    try {
        const response = await fetch(`/task-revisions/${revisionId}/resume`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        const result = await response.json();

        if (result.success) {
            loadProjectRevisions(currentProjectId);

            if (typeof toastr !== 'undefined') {
                toastr.success('تم استئناف العمل');
            } else if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'تم!',
                    text: result.message,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        } else {
            if (typeof toastr !== 'undefined') {
                toastr.error(result.message);
            }
        }
    } catch (error) {
        console.error('Error resuming revision work:', error);
        if (typeof toastr !== 'undefined') {
            toastr.error('حدث خطأ في استئناف العمل');
        }
    }
}

// Complete revision work
async function completeRevisionWork(revisionId) {
    try {
        const response = await fetch(`/task-revisions/${revisionId}/complete`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        const result = await response.json();

        if (result.success) {
            loadProjectRevisions(currentProjectId);

            if (typeof toastr !== 'undefined') {
                toastr.success(`تم إكمال التعديل! الوقت الكلي: ${formatRevisionTime(result.total_minutes)}`);
            } else if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'مبروك!',
                    text: `تم إكمال التعديل. الوقت الكلي: ${formatRevisionTime(result.total_minutes)}`,
                    icon: 'success',
                    timer: 3000,
                    showConfirmButton: false
                });
            }
        } else {
            if (typeof toastr !== 'undefined') {
                toastr.error(result.message);
            }
        }
    } catch (error) {
        console.error('Error completing revision work:', error);
        if (typeof toastr !== 'undefined') {
            toastr.error('حدث خطأ في إكمال التعديل');
        }
    }
}

// Check if revision is assigned to current user
function isRevisionAssignedToCurrentUser(revision) {
    const currentUserId = window.currentUserId;

    if (!currentUserId) return false;

    // Check direct assignment
    if (revision.assigned_to == currentUserId) {
        return true;
    }

    // Check TaskUser assignment
    if (revision.task_user && revision.task_user.user_id == currentUserId) {
        return true;
    }

    // Check TemplateTaskUser assignment
    if (revision.template_task_user && revision.template_task_user.user_id == currentUserId) {
        return true;
    }

    return false;
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Set current user ID for permissions
    const userIdMeta = document.querySelector('meta[name="user-id"]');
    if (userIdMeta) {
        window.currentUserId = userIdMeta.getAttribute('content');
    }
});
