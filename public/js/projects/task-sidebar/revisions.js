// Task Revisions Functions
let currentTaskType = null;
let currentTaskUserId = null;
let currentTaskTaskUserId = null;
let isLoadingRevisions = false;

async function loadTaskRevisions(taskType, taskId, taskUserId = null) {
    if (isLoadingRevisions) {
        return;
    }

    isLoadingRevisions = true;
    currentTaskType = taskType;
    currentTaskUserId = taskId;
    currentTaskTaskUserId = taskUserId;

    const container = document.getElementById('revisionsContainer');
    if (!container) {
        isLoadingRevisions = false;
        return;
    }

    try {
        let url = `/task-revisions/${taskType}/${taskId}`;
        if (taskUserId) {
            url += `?task_user_id=${taskUserId}`;
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
            displayRevisions(result.revisions);
        } else {
            showRevisionsError('حدث خطأ في تحميل التعديلات');
        }

    } catch (error) {
        console.error('Error loading task revisions:', error);
        showRevisionsError('فشل في تحميل التعديلات');
    } finally {
        isLoadingRevisions = false;
    }
}

function displayRevisions(revisions) {
    const container = document.getElementById('revisionsContainer');
    if (!container) return;

    if (!revisions || revisions.length === 0) {
        container.innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-history text-muted" style="font-size: 20px;"></i>
                <p class="mt-2 mb-0 text-muted" style="font-size: 12px;">لا توجد تعديلات حتى الآن</p>
            </div>
        `;
        return;
    }

    const revisionsHtml = revisions.map(revision => {
        const statusColor = revision.status === 'approved' ? 'success' :
                           revision.status === 'rejected' ? 'danger' : 'warning';
        const statusText = revision.status === 'approved' ? 'موافق عليه' :
                          revision.status === 'rejected' ? 'مرفوض' : 'في الانتظار';

        const createdDate = new Date(revision.revision_date).toLocaleDateString('ar-EG', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });

        return `
            <div class="revision-item mb-3 p-3 border rounded" style="background: #fff;">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="flex-grow-1">
                        <h6 class="mb-1 fw-semibold" style="font-size: 13px;">${revision.title}</h6>
                        <p class="mb-1 text-muted" style="font-size: 12px;">${revision.description}</p>
                        ${revision.notes ? `<p class="mb-1 text-secondary" style="font-size: 11px;"><i class="fas fa-sticky-note me-1"></i>${revision.notes}</p>` : ''}
                    </div>
                    <span class="badge bg-${statusColor}" style="font-size: 10px;">${statusText}</span>
                </div>

                ${revision.attachment_path || revision.attachment_link ? `
                    <div class="revision-attachment mb-2">
                        ${revision.attachment_type === 'link' && revision.attachment_link ? `
                            <a href="${revision.attachment_link}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-info" style="font-size: 11px;">
                                <i class="fas fa-external-link-alt me-1"></i>فتح الرابط
                            </a>
                        ` : revision.attachment_path ? `
                            <a href="/task-revisions/${revision.id}/download" class="btn btn-sm btn-outline-primary" style="font-size: 11px;">
                                <i class="fas fa-download me-1"></i>${revision.attachment_name}
                            </a>
                        ` : ''}
                    </div>
                ` : ''}

                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted" style="font-size: 10px;">
                        <i class="fas fa-user me-1"></i>${revision.creator ? revision.creator.name : 'غير معروف'}
                        <i class="fas fa-clock me-1 ms-2"></i>${createdDate}
                    </small>

                    ${revision.created_by == window.currentUserId && revision.status === 'pending' ? `
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteRevision(${revision.id})" style="font-size: 10px; padding: 2px 6px;">
                            <i class="fas fa-trash"></i>
                        </button>
                    ` : ''}
                </div>

                ${revision.status !== 'pending' && revision.reviewer ? `
                    <div class="mt-2 pt-2 border-top">
                        <small class="text-muted" style="font-size: 10px;">
                            <i class="fas fa-user-check me-1"></i>تمت المراجعة بواسطة: ${revision.reviewer.name}
                            ${revision.review_notes ? `<br><i class="fas fa-comment me-1"></i>${revision.review_notes}` : ''}
                        </small>
                    </div>
                ` : ''}
            </div>
        `;
    }).join('');

    container.innerHTML = revisionsHtml;
}

async function showAddRevisionForm(taskType, taskId, taskUserId = '') {
    const form = document.getElementById('addRevisionForm');
    if (form) {
        // Store current task info
        currentTaskType = taskType;
        currentTaskUserId = taskId;
        currentTaskTaskUserId = taskUserId || null;

        // Clear form
        const revisionSourceElement = document.getElementById('revisionSource');
        if (revisionSourceElement) {
            revisionSourceElement.value = 'internal'; // القيمة الافتراضية
        } else {
            console.error('Revision source element not found!');
        }
        document.getElementById('revisionTitle').value = '';
        document.getElementById('revisionDescription').value = '';
        document.getElementById('revisionNotes').value = '';
        document.getElementById('revisionAttachment').value = '';

        // Clear responsibility fields
        document.getElementById('taskRevisionResponsibilityNotes').value = '';

        // Load users from same role as assigned user
        await loadTaskRevisionUsers(taskType, taskId, taskUserId);

        // Show form
        form.style.display = 'block';
        document.getElementById('revisionTitle').focus();

        // إضافة console.log للتشخيص
        console.log('Setting task info for revision:', {
            currentTaskType,
            currentTaskUserId,
            currentTaskTaskUserId
        });
    }
}

// Load users with same role as the assigned user
async function loadTaskRevisionUsers(taskType, taskId, taskUserId) {
    try {
        let url = `/tasks/get-task-user-role-users?task_type=${taskType}&task_id=${taskId}`;
        if (taskUserId) {
            url += `&task_user_id=${taskUserId}`;
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
            const responsibleInput = document.getElementById('taskRevisionResponsibleUser');
            const executorSelect = document.getElementById('taskRevisionExecutorUser');

            // Set responsible user (assigned user) as readonly
            const assignedUser = result.users.find(user => user.is_assigned);
            if (assignedUser) {
                responsibleInput.value = assignedUser.name;
                responsibleInput.setAttribute('data-user-id', assignedUser.id);
            }

            // Clear and populate executor options
            executorSelect.innerHTML = '<option value="">-- اختر المنفذ --</option>';
            result.users.forEach(user => {
                const option = new Option(user.name, user.id, user.is_assigned, user.is_assigned);
                executorSelect.add(option);
            });

        } else {
            console.error('Failed to load users:', result.message);
        }

    } catch (error) {
        console.error('Error loading task revision users:', error);
    }
}

function hideAddRevisionForm() {
    const form = document.getElementById('addRevisionForm');
    if (form) {
        form.style.display = 'none';
    }
}

// Toggle between file upload and link input for revisions
function toggleRevisionAttachmentType(type) {
    const fileContainer = document.getElementById('revisionFileUploadContainer');
    const linkContainer = document.getElementById('revisionLinkInputContainer');

    if (type === 'file') {
        fileContainer.style.display = 'block';
        linkContainer.style.display = 'none';
        // Clear link input
        const linkInput = document.getElementById('revisionAttachmentLink');
        if (linkInput) linkInput.value = '';
    } else if (type === 'link') {
        fileContainer.style.display = 'none';
        linkContainer.style.display = 'block';
        // Clear file input
        const fileInput = document.getElementById('revisionAttachment');
        if (fileInput) fileInput.value = '';
    }
}

async function saveRevision() {
    const revisionSourceElement = document.getElementById('revisionSource');
    const revisionSource = revisionSourceElement ? revisionSourceElement.value : 'internal';

    const title = document.getElementById('revisionTitle').value.trim();
    const description = document.getElementById('revisionDescription').value.trim();
    const notes = document.getElementById('revisionNotes').value.trim();
    const attachmentInput = document.getElementById('revisionAttachment');

    if (!title) {
        alert('عنوان التعديل مطلوب');
        return;
    }

    if (!description) {
        alert('وصف التعديل مطلوب');
        return;
    }

    // التحقق من وجود البيانات المطلوبة
    if (!currentTaskType || !currentTaskUserId) {
        console.error('Missing task data:', { currentTaskType, currentTaskUserId });
        alert('خطأ: بيانات المهمة غير مكتملة. يرجى إغلاق النموذج وإعادة فتحه.');
        return;
    }

    // Get responsibility fields
    const responsibleInput = document.getElementById('taskRevisionResponsibleUser');
    const responsibleUserId = responsibleInput?.getAttribute('data-user-id');
    const executorUserId = document.getElementById('taskRevisionExecutorUser')?.value;
    const responsibilityNotes = document.getElementById('taskRevisionResponsibilityNotes')?.value;

    const formData = new FormData();
    formData.append('revision_type', 'task'); // إضافة نوع التعديل
    formData.append('revision_source', revisionSource); // إضافة مصدر التعديل
    formData.append('task_type', currentTaskType); // regular أو template
    formData.append('task_id', currentTaskUserId); // معرف المهمة
    if (currentTaskTaskUserId) {
        formData.append('task_user_id', currentTaskTaskUserId); // معرف task_user إذا كان موجود
    }
    formData.append('title', title);
    formData.append('description', description);
    if (notes) {
        formData.append('notes', notes);
    }

    // Add responsibility fields
    if (responsibleUserId) {
        formData.append('responsible_user_id', parseInt(responsibleUserId));
    }
    if (executorUserId) {
        formData.append('executor_user_id', parseInt(executorUserId));
    }
    if (responsibilityNotes) {
        formData.append('responsibility_notes', responsibilityNotes);
    }

    // Check attachment type
    const attachmentType = document.querySelector('input[name="revisionAttachmentType"]:checked').value;

    if (attachmentType === 'file' && attachmentInput.files[0]) {
        formData.append('attachment', attachmentInput.files[0]);
        formData.append('attachment_type', 'file');
    } else if (attachmentType === 'link') {
        const linkInput = document.getElementById('revisionAttachmentLink');
        const link = linkInput ? linkInput.value.trim() : '';

        if (link) {
            // Validate URL
            try {
                new URL(link);
                formData.append('attachment_link', link);
                formData.append('attachment_type', 'link');
            } catch (e) {
                alert('الرجاء إدخال رابط صحيح يبدأ بـ http:// أو https://');
                return;
            }
        }
    }

    try {
        const response = await fetch('/task-revisions', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            // إعادة تعيين النموذج
            document.getElementById('revisionTitle').value = '';
            document.getElementById('revisionDescription').value = '';
            document.getElementById('revisionNotes').value = '';
            document.getElementById('revisionAttachment').value = '';

            hideAddRevisionForm();
            loadTaskRevisions(currentTaskType, currentTaskUserId, currentTaskTaskUserId);

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'تم!',
                    text: 'تم إضافة التعديل بنجاح',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        } else {
            console.error('Error saving revision:', result.message);
            console.error('Validation errors:', result.errors);

            // عرض تفاصيل أكثر للأخطاء
            let errorMessage = result.message || 'حدث خطأ في حفظ التعديل';
            if (result.errors) {
                const errorDetails = Object.values(result.errors).flat().join('\n');
                errorMessage += '\n\nالتفاصيل:\n' + errorDetails;
            }

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'خطأ في الحفظ',
                    text: errorMessage,
                    icon: 'error',
                    confirmButtonText: 'حسناً'
                });
            } else {
                alert(errorMessage);
            }
        }

    } catch (error) {
        console.error('Error saving revision:', error);

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'خطأ في الاتصال',
                text: 'حدث خطأ في الاتصال بالخادم. يرجى المحاولة مرة أخرى.',
                icon: 'error',
                confirmButtonText: 'حسناً'
            });
        } else {
            alert('حدث خطأ في الاتصال بالخادم. يرجى المحاولة مرة أخرى.');
        }
    }
}

async function deleteRevision(revisionId) {
    if (!confirm('هل أنت متأكد من حذف هذا التعديل؟')) {
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
            loadTaskRevisions(currentTaskType, currentTaskUserId, currentTaskTaskUserId);

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'تم!',
                    text: 'تم حذف التعديل بنجاح',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        } else {
            alert(result.message || 'حدث خطأ في حذف التعديل');
        }

    } catch (error) {
        console.error('Error deleting revision:', error);
        alert('حدث خطأ في حذف التعديل');
    }
}

function showRevisionsError(message) {
    const container = document.getElementById('revisionsContainer');
    if (container) {
        container.innerHTML = `
            <div class="text-center py-4 text-danger">
                <i class="fas fa-exclamation-triangle" style="font-size: 20px;"></i>
                <p class="mt-2 mb-0" style="font-size: 12px;">${message}</p>
                <button class="btn btn-outline-primary btn-sm mt-2" onclick="loadTaskRevisions(currentTaskType, currentTaskUserId, currentTaskTaskUserId)" style="font-size: 11px;">
                    <i class="fas fa-refresh me-1"></i>إعادة المحاولة
                </button>
            </div>
        `;
    }
}
