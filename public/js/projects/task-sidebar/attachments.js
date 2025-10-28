
async function loadTaskAttachments(taskUserId) {
    console.log('🔍 loadTaskAttachments called with taskUserId:', taskUserId);

    const attachmentsList = document.getElementById('attachmentsList');
    const attachmentsCount = document.querySelector('.task-attachments-count');

    console.log('📋 attachmentsList element:', !!attachmentsList);
    console.log('🔢 attachmentsCount element:', !!attachmentsCount);

    if (!attachmentsList || !taskUserId) {
        console.log('❌ Missing attachmentsList or taskUserId, returning');
        return;
    }

    try {
        const url = `/task-attachments/task/${taskUserId}`;
        console.log('🌐 Fetching URL:', url);

        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        console.log('📡 Response status:', response.status);
        console.log('📡 Response ok:', response.ok);

        if (!response.ok) {
            const errorText = await response.text();
            console.error('❌ Response error text:', errorText);
            throw new Error(`HTTP ${response.status}: ${errorText}`);
        }

        const result = await response.json();
        console.log('📄 Response data:', result);

        if (result.success) {
            const attachments = result.data.attachments || [];
            console.log('📎 Attachments found:', attachments.length);

            // Check if task is unassigned
            const isUnassigned = result.data.is_unassigned || result.is_unassigned;

            // Update count
            if (attachmentsCount) {
                attachmentsCount.textContent = attachments.length;
            }

            // Display attachments or unassigned message
            if (isUnassigned) {
                attachmentsList.innerHTML = `
                    <div class="text-center py-3">
                        <i class="fas fa-info-circle text-warning mb-2" style="font-size: 24px;"></i>
                        <p class="text-warning mb-0" style="font-size: 12px;">هذه المهمة غير مُعيَّنة لأي مستخدم</p>
                        <small class="text-muted" style="font-size: 10px;">لا توجد مرفقات للمهام غير المُعيَّنة</small>
                    </div>
                `;
            } else if (attachments.length === 0) {
                attachmentsList.innerHTML = `
                    <div class="text-center py-3">
                        <i class="fas fa-paperclip text-muted mb-2" style="font-size: 24px;"></i>
                        <p class="text-muted mb-0" style="font-size: 12px;">لا توجد مرفقات حتى الآن</p>
                    </div>
                `;
            } else {
                attachmentsList.innerHTML = attachments.map(attachment => `
                    <div class="attachment-item d-flex align-items-center justify-content-between p-2 mb-2 rounded border">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-file text-muted me-2"></i>
                            <div>
                                <div class="fw-semibold" style="font-size: 12px;">${attachment.file_name}</div>
                                <small class="text-muted" style="font-size: 10px;">
                                    ${attachment.file_size ? formatFileSize(attachment.file_size) : ''} •
                                    ${formatTimeAgo(new Date(attachment.created_at))}
                                </small>
                            </div>
                        </div>
                        <div class="d-flex gap-1">
                            <button class="btn btn-outline-primary btn-sm" style="font-size: 10px; padding: 4px 8px;"
                                    onclick="viewAttachment(${attachment.id})">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-outline-success btn-sm" style="font-size: 10px; padding: 4px 8px;"
                                    onclick="downloadAttachment(${attachment.id})">
                                <i class="fas fa-download"></i>
                            </button>
                            <button class="btn btn-outline-danger btn-sm" style="font-size: 10px; padding: 4px 8px;"
                                    onclick="deleteAttachment(${attachment.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `).join('');
            }
        } else {
            // Check if the error is about unassigned task
            if (result.message && result.message.includes('غير مُعيَّنة')) {
                attachmentsList.innerHTML = `
                    <div class="text-center py-3">
                        <i class="fas fa-info-circle text-warning mb-2" style="font-size: 24px;"></i>
                        <p class="text-warning mb-0" style="font-size: 12px;">${result.message}</p>
                        <small class="text-muted" style="font-size: 10px;">لا توجد مرفقات للمهام غير المُعيَّنة</small>
                    </div>
                `;
            } else {
                attachmentsList.innerHTML = `
                    <div class="text-center py-3">
                        <i class="fas fa-exclamation-triangle text-danger mb-2" style="font-size: 24px;"></i>
                        <p class="text-muted mb-0" style="font-size: 12px;">حدث خطأ في تحميل المرفقات</p>
                    </div>
                `;
            }
        }
    } catch (error) {
        console.error('❌ Error loading attachments:', error);
        console.error('💥 Error stack:', error.stack);
        console.error('🌐 Error message:', error.message);

        attachmentsList.innerHTML = `
            <div class="text-center py-3">
                <i class="fas fa-exclamation-triangle text-danger mb-2" style="font-size: 24px;"></i>
                <p class="text-muted mb-0" style="font-size: 12px;">فشل في تحميل المرفقات</p>
                <small class="text-danger" style="font-size: 10px;">${error.message}</small>
            </div>
        `;
    }
}

/**
 * Initialize attachment handlers
 */
function initializeAttachmentHandlers(taskUserId) {
    const dropZone = document.getElementById('attachmentDropZone');
    const fileInput = document.getElementById('attachmentFileInput');

    if (!dropZone || !fileInput) return;

    // Click to select files
    dropZone.addEventListener('click', () => {
        fileInput.click();
    });

    // File input change
    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            handleFileUpload(Array.from(e.target.files), taskUserId);
        }
    });

    // Drag and drop
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.style.borderColor = '#007bff';
        dropZone.style.backgroundColor = '#e3f2fd';
    });

    dropZone.addEventListener('dragleave', (e) => {
        e.preventDefault();
        dropZone.style.borderColor = '#dee2e6';
        dropZone.style.backgroundColor = '#f8f9fa';
    });

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.style.borderColor = '#dee2e6';
        dropZone.style.backgroundColor = '#f8f9fa';

        const files = Array.from(e.dataTransfer.files);
        if (files.length > 0) {
            handleFileUpload(files, taskUserId);
        }
    });
}

/**
 * Handle file upload
 */
async function handleFileUpload(files, taskUserId) {
    const uploadProgressArea = document.getElementById('uploadProgressArea');
    const uploadQueue = uploadProgressArea.querySelector('.upload-queue');

    uploadProgressArea.style.display = 'block';
    uploadQueue.innerHTML = '';

    let successCount = 0;
    let errorCount = 0;

    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        const fileCard = document.createElement('div');
        fileCard.className = 'file-upload-card p-2 mb-2 border rounded';
        fileCard.innerHTML = `
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <i class="fas fa-file text-muted me-2"></i>
                    <div>
                        <div class="fw-semibold" style="font-size: 12px;">${file.name}</div>
                        <small class="text-muted progress-status" style="font-size: 10px;">جاري الإعداد...</small>
                    </div>
                </div>
                <div class="progress" style="width: 100px; height: 6px;">
                    <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                </div>
            </div>
        `;
        uploadQueue.appendChild(fileCard);

        try {
            await uploadSingleFile(file, taskUserId, fileCard);
            successCount++;
        } catch (error) {
            console.error('Upload error:', error);
            errorCount++;
            const progressBar = fileCard.querySelector('.progress-bar');
            const status = fileCard.querySelector('.progress-status');
            progressBar.className = 'progress-bar bg-danger';
            progressBar.style.width = '100%';
            status.textContent = 'فشل';
        }
    }

    // Hide upload area after completion
    setTimeout(() => {
        uploadProgressArea.style.display = 'none';
        // Reload attachments
        loadTaskAttachments(taskUserId);
    }, 2000);

    // Show result message
    if (successCount > 0) {
        showToast(`تم رفع ${successCount} ملف بنجاح`, 'success');
    }
    if (errorCount > 0) {
        showToast(`فشل في رفع ${errorCount} ملف`, 'error');
    }
}

/**
 * Upload single file
 */
async function uploadSingleFile(file, taskUserId, fileCard) {
    const progressBar = fileCard.querySelector('.progress-bar');
    const status = fileCard.querySelector('.progress-status');

    // Update progress
    function updateProgress(percent, message) {
        progressBar.style.width = percent + '%';
        status.textContent = message;
    }

    updateProgress(10, 'طلب رابط الرفع...');

    // Get presigned URL
    const presignedResponse = await fetch('/task-attachments/presigned-url', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            task_user_id: taskUserId,
            file_name: file.name,
            file_size: file.size,
            mime_type: file.type
        })
    });

    const presignedData = await presignedResponse.json();
    if (!presignedData.success) {
        throw new Error(presignedData.message);
    }

    updateProgress(30, 'جاري الرفع...');

    // Upload to S3
    const uploadResponse = await fetch(presignedData.data.upload_url, {
        method: 'PUT',
        body: file
    });

    if (!uploadResponse.ok) {
        throw new Error('فشل في رفع الملف');
    }

    updateProgress(90, 'جاري التأكيد...');

    // Confirm upload
    const confirmResponse = await fetch(`/task-attachments/${presignedData.data.attachment_id}/confirm`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    });

    const confirmData = await confirmResponse.json();
    if (!confirmData.success) {
        throw new Error(confirmData.message);
    }

    updateProgress(100, 'اكتمل');
    progressBar.className = 'progress-bar bg-success';
}

/**
 * View attachment
 */
function viewAttachment(attachmentId) {
    window.open(`/task-attachments/${attachmentId}/view`, '_blank');
}

/**
 * Download attachment
 */
function downloadAttachment(attachmentId) {
    window.location.href = `/task-attachments/${attachmentId}/download`;
}

/**
 * Delete attachment
 */
async function deleteAttachment(attachmentId) {
    if (!confirm('هل أنت متأكد من حذف هذا المرفق؟')) {
        return;
    }

    try {
        const response = await fetch(`/task-attachments/${attachmentId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        const result = await response.json();
        if (result.success) {
            showToast('تم حذف المرفق بنجاح', 'success');
            // Reload attachments for current task
            if (currentTaskUserId) {
                loadTaskAttachments(currentTaskUserId);
            }
        } else {
            showToast(result.message || 'فشل في حذف المرفق', 'error');
        }
    } catch (error) {
        console.error('Delete error:', error);
        showToast('حدث خطأ أثناء حذف المرفق', 'error');
    }
}

/**
 * Format file size
 */
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

/**
 * Format time ago
 */
function formatTimeAgo(date) {
    if (!date) return '';

    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);

    if (diffInSeconds < 60) {
        return 'منذ لحظات';
    } else if (diffInSeconds < 3600) {
        const minutes = Math.floor(diffInSeconds / 60);
        return `منذ ${minutes} دقيقة`;
    } else if (diffInSeconds < 86400) {
        const hours = Math.floor(diffInSeconds / 3600);
        return `منذ ${hours} ساعة`;
    } else if (diffInSeconds < 2592000) {
        const days = Math.floor(diffInSeconds / 86400);
        return `منذ ${days} يوم`;
    } else {
        return date.toLocaleDateString('ar-EG', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }
}

/**
 * Show toast notification
 */
function showToast(message, type = 'info') {
    // Simple toast implementation
    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} position-fixed`;
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    toast.textContent = message;

    document.body.appendChild(toast);

    setTimeout(() => {
        toast.remove();
    }, 3000);
}
