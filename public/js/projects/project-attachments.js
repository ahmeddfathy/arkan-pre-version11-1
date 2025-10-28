

document.addEventListener('DOMContentLoaded', function() {
    initFileUploadFunctionality();
    checkSessionMessages();
    initTaskSelectionFunctionality();
    initAttachmentClickHandlers();
    preventButtonDeformation();
});

// منع تشويه شكل الأزرار عند الضغط
function preventButtonDeformation() {
    // العثور على جميع أزرار رفع الملفات
    const uploadButtons = document.querySelectorAll('.attachment-upload-form button, .upload-form button, button[type="submit"]');

    uploadButtons.forEach(button => {
        // حفظ الستايلات الأصلية
        const originalStyle = {
            transform: button.style.transform || 'none',
            position: button.style.position || 'relative',
            top: button.style.top || '0',
            left: button.style.left || '0',
            scale: button.style.scale || '1',
            boxShadow: button.style.boxShadow || 'none',
            outline: button.style.outline || 'none'
        };

        // إضافة event listeners لمنع تغيير الشكل
        ['mousedown', 'mouseup', 'click', 'focus', 'blur', 'active'].forEach(event => {
            button.addEventListener(event, function(e) {
                // منع تغيير الستايلات
                setTimeout(() => {
                    this.style.transform = 'none';
                    this.style.position = 'relative';
                    this.style.top = '0';
                    this.style.left = '0';
                    this.style.scale = '1';
                    this.style.boxShadow = 'none';
                    this.style.outline = 'none';
                }, 1);
            });
        });

        // مراقبة مستمرة للتأكد من عدم تغيير الستايلات
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                    button.style.transform = 'none';
                    button.style.position = 'relative';
                    button.style.top = '0';
                    button.style.left = '0';
                    button.style.scale = '1';
                    button.style.boxShadow = 'none';
                    button.style.outline = 'none';
                }
            });
        });

        observer.observe(button, {
            attributes: true,
            attributeFilter: ['style', 'class']
        });
    });
}

const selectedFilesMap = {};

function checkSessionMessages() {
    const sessionSuccess = document.getElementById('session-success');
    if (sessionSuccess) {
        toastr.success(sessionSuccess.value, 'نجاح ✅');
    }

    const sessionError = document.getElementById('session-error');
    if (sessionError) {
        toastr.error(sessionError.value, 'خطأ ❌');
    }
}

toastr.options = {
    "closeButton": true,
    "progressBar": true,
    "positionClass": "toast-top-left",
    "showDuration": "300",
    "hideDuration": "1000",
    "timeOut": "5000",
    "extendedTimeOut": "1000",
    "showEasing": "swing",
    "hideEasing": "linear",
    "showMethod": "fadeIn",
    "hideMethod": "fadeOut",
    "rtl": true
};

function addAttachmentToList(attachmentData, serviceType) {
    const attachmentList = document.querySelector(`[data-service-type="${serviceType}"] .list-group`);
    if (!attachmentList) return;

    // التحقق من وجود الملف مسبقاً لتجنب التكرار
    const existingItem = attachmentList.querySelector(`[data-attachment-id="${attachmentData.id}"]`);
    if (existingItem) return;

    const newItem = document.createElement('li');
    newItem.className = 'list-group-item d-flex flex-column align-items-start';
    newItem.setAttribute('data-attachment-id', attachmentData.id);

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    const itemContent = `
        <div class="d-flex w-100 justify-content-between align-items-center">
            <a href="/projects/attachments/view/${attachmentData.id}" target="_blank" class="me-2" title="عرض الملف">
                <i class="fas fa-eye"></i>
            </a>
            <span>${attachmentData.fileName}</span>
            <div>
                <a href="/projects/attachments/download/${attachmentData.id}" class="btn btn-sm btn-outline-primary me-1" title="تحميل">
                    <i class="fas fa-download"></i>
                </a>
            </div>
        </div>
        ${attachmentData.description ? `<small class="text-muted">${attachmentData.description}</small>` : ''}
        ${attachmentData.task_type ? `
            <small class="text-primary">
                <i class="fas fa-tasks"></i>
                ${attachmentData.task_type === 'template_task' ? 'قالب مهمة: ' : 'مهمة: '}
                ${attachmentData.task_name || ''}
            </small>
        ` : ''}
        <small class="text-secondary">بواسطة: ${document.querySelector('meta[name="user-name"]')?.content || 'المستخدم الحالي'}</small>
    `;

    newItem.innerHTML = itemContent;

    newItem.style.opacity = "0";
    attachmentList.insertBefore(newItem, attachmentList.firstChild);
    setTimeout(() => {
        newItem.style.transition = "opacity 0.5s ease";
        newItem.style.opacity = "1";
    }, 100);
}

function addFileToSelectedList(file, serviceType) {
    if (!selectedFilesMap[serviceType]) {
        selectedFilesMap[serviceType] = [];
    }

    // Check file size before adding
    if (!checkFileSize(file)) {
        return false;
    }

    if (!selectedFilesMap[serviceType].some(f => f.name === file.name && f.size === file.size)) {
        selectedFilesMap[serviceType].push(file);
        updateSelectedFilesList(serviceType);
        return true;
    }
    return false;
}

function updateSelectedFilesList(serviceType) {
    const selectedFilesContainer = document.getElementById('selectedFiles' + serviceType.replace(/\s+/g, ''));
    if (!selectedFilesContainer) return;

    selectedFilesContainer.innerHTML = '';

    if (!selectedFilesMap[serviceType] || selectedFilesMap[serviceType].length === 0) {
        return;
    }

    selectedFilesMap[serviceType].forEach((file, index) => {
        const fileItem = document.createElement('div');
        fileItem.className = 'selected-file-item';
        fileItem.innerHTML = `
            <div class="selected-file-name">${file.name}</div>
            <div class="selected-file-size">${formatFileSize(file.size)}</div>
            <button type="button" class="selected-file-remove" data-index="${index}">
                <i class="fas fa-times"></i>
            </button>
            <div class="selected-file-progress" id="progress-file-${serviceType.replace(/\s+/g, '')}-${index}"></div>
        `;

        selectedFilesContainer.appendChild(fileItem);

        fileItem.querySelector('.selected-file-remove').addEventListener('click', function() {
            const fileIndex = parseInt(this.getAttribute('data-index'));
            selectedFilesMap[serviceType].splice(fileIndex, 1);
            updateSelectedFilesList(serviceType);
        });
    });
}

function formatFileSize(bytes) {
    if (bytes < 1024) return bytes + ' بايت';
    else if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' كيلوبايت';
    else return (bytes / 1048576).toFixed(1) + ' ميجابايت';
}

function updateFileProgress(serviceType, fileIndex, percent) {
    const progressElement = document.getElementById(`progress-file-${serviceType.replace(/\s+/g, '')}-${fileIndex}`);
    const progressBar = document.getElementById('progress-bar-' + serviceType.replace(/\s+/g, ''));
    const percentageDiv = document.getElementById('percentage-' + serviceType.replace(/\s+/g, ''));

    if (progressElement) {
        progressElement.style.width = `${percent}%`;
    }

    // Update main progress bar as well
    if (progressBar) {
        progressBar.style.width = `${percent}%`;
        progressBar.setAttribute('aria-valuenow', percent);
        console.log(`Progress updated: ${percent}%`);
    }

    if (percentageDiv) {
        percentageDiv.textContent = `${percent}%`;
        percentageDiv.classList.add('show');

        // إضافة تأكيد على التنسيقات
        percentageDiv.style.position = 'relative';
        percentageDiv.style.zIndex = '999';
        percentageDiv.style.display = 'inline-block';

        console.log(`Percentage updated: ${percent}% - Element:`, percentageDiv);

        // تغيير لون النسبة حسب التقدم
        if (percent >= 100) {
            percentageDiv.classList.add('completed');
            percentageDiv.classList.remove('error');
        } else if (percent < 0) {
            percentageDiv.classList.add('error');
            percentageDiv.classList.remove('completed');
        } else {
            percentageDiv.classList.remove('completed', 'error');
        }
    }
}

async function confirmUpload(attachmentId, serviceType, fileName, description, taskType, taskName) {
    try {
        const response = await fetch(`/projects/attachments/${attachmentId}/confirm-upload`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        if (!response.ok) {
            throw new Error('حدث خطأ أثناء تأكيد الرفع');
        }

        const data = await response.json();

        if (data.success) {
            addAttachmentToList({
                id: data.attachment.id,
                fileName: fileName,
                description: description,
                task_type: taskType,
                task_name: taskName
            }, serviceType);
            return true;
        } else {
            throw new Error('فشل تأكيد الرفع');
        }
    } catch (error) {
        console.error('Error confirming upload:', error);
        return false;
    }
}

async function uploadMultipleFiles(formElement, serviceType, description) {
    // منع الرفع المتعدد - إضافة علامة للتحقق من أن الرفع جاري
    const uploadInProgressKey = `uploading_${serviceType}`;
    if (window[uploadInProgressKey]) {
        toastr.warning('جاري رفع ملفات أخرى، يرجى الانتظار...', 'تنبيه ⚠️');
        return;
    }

    if (!selectedFilesMap[serviceType] || selectedFilesMap[serviceType].length === 0) {
        toastr.warning('يرجى اختيار ملف واحد على الأقل للرفع', 'تنبيه ⚠️');
        return;
    }

    // تعيين علامة أن الرفع جاري
    window[uploadInProgressKey] = true;

    const projectId = formElement.closest('[data-project-id]')?.dataset.projectId || document.querySelector('meta[name="project-id"]')?.content;

    if (!projectId) {
        toastr.error('لم يتم العثور على معرف المشروع', 'خطأ ❌');
        // إزالة علامة الرفع الجاري في حالة الخطأ
        window[uploadInProgressKey] = false;
        return;
    }

    // الحصول على بيانات المهمة المختارة
    const taskType = formElement.querySelector('.task-type-selector')?.value || '';
    let taskId = null;
    let taskName = '';

    if (taskType === 'template_task') {
        const taskSelect = formElement.querySelector('select[name="template_task_user_id"]');
        taskId = taskSelect?.value || null;
        taskName = taskSelect?.options[taskSelect.selectedIndex]?.text || '';
    } else if (taskType === 'regular_task') {
        const taskSelect = formElement.querySelector('select[name="task_user_id"]');
        taskId = taskSelect?.value || null;
        taskName = taskSelect?.options[taskSelect.selectedIndex]?.text || '';
    }

    const progressContainer = document.getElementById('progress-container-' + serviceType.replace(/\s+/g, ''));
    const progressBar = document.getElementById('progress-bar-' + serviceType.replace(/\s+/g, ''));
    const statusDiv = document.getElementById('status-' + serviceType.replace(/\s+/g, ''));
    const percentageDiv = document.getElementById('percentage-' + serviceType.replace(/\s+/g, ''));
    const submitBtn = formElement.querySelector('button[type="submit"]');

    console.log('Progress elements found:', {
        progressContainer: !!progressContainer,
        progressBar: !!progressBar,
        statusDiv: !!statusDiv,
        percentageDiv: !!percentageDiv,
        serviceType: serviceType
    });

    if (progressContainer) {
        progressContainer.classList.add('show');
        progressContainer.style.display = 'block';
        progressContainer.style.visibility = 'visible';
    }
    if (statusDiv) {
        statusDiv.classList.add('show');
        statusDiv.style.display = 'block';
        statusDiv.style.visibility = 'visible';
    }
    if (percentageDiv) {
        percentageDiv.classList.add('show');
        percentageDiv.style.visibility = 'visible';
    }

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري الرفع...';

    const totalFiles = selectedFilesMap[serviceType].length;
    let uploadedFiles = 0;
    let failedFiles = 0;

    if (statusDiv) {
        statusDiv.className = 'upload-status uploading';
        statusDiv.innerHTML = `<span class="upload-icon pulse"><i class="fas fa-cloud-upload-alt"></i></span> جاري رفع الملفات (${uploadedFiles}/${totalFiles})...`;
    }

    for (let i = 0; i < selectedFilesMap[serviceType].length; i++) {
        const file = selectedFilesMap[serviceType][i];

        try {
            updateFileProgress(serviceType, i, 10);

            const formData = new FormData();
            formData.append('file_name', file.name);
            formData.append('service_type', serviceType);
            formData.append('description', description || '');

            // إضافة معلومات المهمة إذا كانت متاحة
            if (taskType && taskId) {
                formData.append('task_type', taskType);
                if (taskType === 'template_task') {
                    formData.append('template_task_user_id', taskId);
                } else if (taskType === 'regular_task') {
                    formData.append('task_user_id', taskId);
                }
            }

            // إضافة معلومات الرد على الملف إذا كانت محددة
            const isReplyCheckbox = formElement.querySelector('input[name="is_reply"]');
            const parentAttachmentSelect = formElement.querySelector('select[name="parent_attachment_id"]');

            if (isReplyCheckbox && isReplyCheckbox.checked && parentAttachmentSelect && parentAttachmentSelect.value) {
                formData.append('is_reply', '1');
                formData.append('parent_attachment_id', parentAttachmentSelect.value);
            }

            // Add timeout to avoid hanging requests
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 30000); // 30 second timeout

            const response = await fetch(`/projects/${projectId}/presigned-url`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: formData,
                signal: controller.signal
            });

            clearTimeout(timeoutId);

            if (!response.ok) {
                throw new Error('فشل في الحصول على رابط الرفع: ' + response.status);
            }

            const data = await response.json();

            updateFileProgress(serviceType, i, 30);

            const uploadPromise = new Promise((resolve, reject) => {
                updateFileProgress(serviceType, i, 50);

                // Create a more robust upload with progress tracking
                const xhr = new XMLHttpRequest();

                xhr.upload.addEventListener('progress', (e) => {
                    if (e.lengthComputable) {
                        const percentComplete = Math.round(50 + (e.loaded / e.total) * 40); // 50-90%
                        updateFileProgress(serviceType, i, percentComplete);
                    }
                });

                xhr.onload = function() {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        updateFileProgress(serviceType, i, 95);
                        resolve(xhr);
                    } else {
                        reject(new Error(`فشل في رفع الملف: ${xhr.status} ${xhr.statusText}`));
                    }
                };

                xhr.onerror = function() {
                    reject(new Error('خطأ في الشبكة أثناء رفع الملف'));
                };

                xhr.ontimeout = function() {
                    reject(new Error('انتهت مهلة رفع الملف'));
                };

                // Set timeout for upload (5 minutes for large files)
                xhr.timeout = 300000;

                try {
                    xhr.open('PUT', data.upload_url);
                    xhr.send(file);
                } catch (error) {
                    reject(error);
                }
            });

            // Después de cargar الملف بنجاح
            const uploadResponse = await uploadPromise;
            if (uploadResponse && data.attachment_id) {
                const confirmSuccess = await confirmUpload(data.attachment_id, serviceType, file.name, description, taskType, taskName);
                if (confirmSuccess) {
                    uploadedFiles++;
                } else {
                    failedFiles++;
                }
            } else {
                failedFiles++;
            }

            const totalProgress = Math.round((uploadedFiles / totalFiles) * 100);
            if (progressBar) progressBar.style.width = `${totalProgress}%`;
            if (percentageDiv) percentageDiv.textContent = `${totalProgress}%`;

            if (statusDiv) {
                statusDiv.innerHTML = `<span class="upload-icon pulse"><i class="fas fa-cloud-upload-alt"></i></span> جاري رفع الملفات (${uploadedFiles}/${totalFiles})...`;
            }

            toastr.success(`تم رفع الملف ${file.name} بنجاح!`, 'نجاح ✅');

        } catch (error) {
            console.error('خطأ في رفع الملف:', error);
            failedFiles++;

            const progressElement = document.getElementById(`progress-file-${serviceType.replace(/\s+/g, '')}-${i}`);
            if (progressElement) {
                progressElement.style.width = '100%';
                progressElement.style.backgroundColor = 'var(--danger-color)';
            }

            toastr.error(`فشل في رفع الملف ${file.name}: ${error.message}`, 'خطأ ❌');
        }
    }

    if (statusDiv) {
        if (failedFiles === 0) {
            statusDiv.className = 'upload-status success';
            statusDiv.innerHTML = `<span class="upload-icon"><i class="fas fa-check-circle"></i></span> تم رفع جميع الملفات بنجاح!`;
            if (percentageDiv) {
                percentageDiv.classList.add('completed');
                percentageDiv.classList.remove('error');
            }
        } else if (uploadedFiles === 0) {
            statusDiv.className = 'upload-status error';
            statusDiv.innerHTML = `<span class="upload-icon"><i class="fas fa-exclamation-circle"></i></span> فشل رفع جميع الملفات`;
            if (percentageDiv) {
                percentageDiv.classList.add('error');
                percentageDiv.classList.remove('completed');
            }
        } else {
            statusDiv.className = 'upload-status warning';
            statusDiv.innerHTML = `<span class="upload-icon"><i class="fas fa-exclamation-triangle"></i></span> تم رفع ${uploadedFiles} ملف بنجاح، وفشل ${failedFiles} ملف`;
            if (percentageDiv) {
                percentageDiv.classList.add('error');
                percentageDiv.classList.remove('completed');
            }
        }
    }

    submitBtn.disabled = false;
    submitBtn.innerHTML = 'رفع الملفات';

    selectedFilesMap[serviceType] = [];
    updateSelectedFilesList(serviceType);

    // إزالة علامة الرفع الجاري
    window[uploadInProgressKey] = false;

    if (uploadedFiles > 0) {
        toastr.success(`تم رفع ${uploadedFiles} ملف من أصل ${totalFiles} بنجاح!`, 'اكتمل الرفع ✅');
    }
}

function setupDragDropArea(dragArea, serviceType) {
    const fileInput = dragArea.querySelector('input[type="file"]');
    const fileSelectButton = dragArea.querySelector('.file-select-button');

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dragArea.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        dragArea.addEventListener(eventName, () => {
            dragArea.classList.add('drag-over');
        }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dragArea.addEventListener(eventName, () => {
            dragArea.classList.remove('drag-over');
        }, false);
    });

    dragArea.addEventListener('drop', handleDrop, false);

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        handleFiles(files);
    }

    if (fileSelectButton) {
        fileSelectButton.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            fileInput.click();
        });
    } else {
        dragArea.addEventListener('click', (e) => {
            if (e.target !== fileInput) {
                e.preventDefault();
                fileInput.click();
            }
        });
    }

    fileInput.addEventListener('change', function() {
        handleFiles(this.files);
    });

    function handleFiles(files) {
        if (files.length === 0) return;
        Array.from(files).forEach(file => {
            addFileToSelectedList(file, serviceType);
        });
    }
}

function initFileUploadFunctionality() {
    const uploadForms = document.querySelectorAll('.attachment-upload-form');

    uploadForms.forEach(form => {
        const serviceTypeInput = form.querySelector('input[name="service_type"]');
        if (!serviceTypeInput) return;

        const serviceType = serviceTypeInput.value;
        const parentCard = form.closest('.card');

        // إزالة أي event listeners سابقة لتجنب التكرار
        const existingListener = form.getAttribute('data-listener-attached');
        if (existingListener) {
            return; // تم إرفاق listener بالفعل
        }

        // إضافة علامة لتجنب إضافة listeners متعددة
        form.setAttribute('data-listener-attached', 'true');

        if (parentCard) {
            parentCard.setAttribute('data-service-type', serviceType);
        }

        const dragDropArea = form.querySelector('.drag-drop-area');
        if (dragDropArea) {
            const fileInput = dragDropArea.querySelector('input[type="file"]');
            if (fileInput) {
                fileInput.style.position = 'absolute';
                fileInput.style.opacity = '0';
                fileInput.style.zIndex = '-1';
                fileInput.style.pointerEvents = 'none';
            }

            setupDragDropArea(dragDropArea, serviceType);
        }

        const progressWrapperDiv = document.createElement('div');
        progressWrapperDiv.className = 'progress-with-percentage';

        const percentageDiv = document.createElement('div');
        percentageDiv.className = 'progress-percentage';
        percentageDiv.id = 'percentage-' + serviceType.replace(/\s+/g, '');
        percentageDiv.textContent = '0%';

        const fileSizeBadge = document.createElement('div');
        fileSizeBadge.className = 'small-file-badge';
        fileSizeBadge.id = 'file-size-' + serviceType.replace(/\s+/g, '');

        const progressContainer = document.createElement('div');
        progressContainer.className = 'upload-progress-container';
        progressContainer.id = 'progress-container-' + serviceType.replace(/\s+/g, '');

        const progressBar = document.createElement('div');
        progressBar.className = 'upload-progress-bar';
        progressBar.id = 'progress-bar-' + serviceType.replace(/\s+/g, '');

        progressContainer.appendChild(progressBar);
        progressWrapperDiv.appendChild(percentageDiv);
        progressWrapperDiv.appendChild(fileSizeBadge);
        progressWrapperDiv.appendChild(progressContainer);
        form.appendChild(progressWrapperDiv);

        const statusDiv = document.createElement('div');
        statusDiv.className = 'upload-status';
        statusDiv.id = 'status-' + serviceType.replace(/\s+/g, '');
        form.appendChild(statusDiv);

        form.addEventListener('submit', function(event) {
            event.preventDefault();
            const description = this.querySelector('input[name="description"]').value;
            uploadMultipleFiles(this, serviceType, description);
        });
    });
}


function initTaskSelectionFunctionality() {
    const taskTypeSelectors = document.querySelectorAll('.task-type-selector');

    taskTypeSelectors.forEach(selector => {
        selector.addEventListener('change', function() {
            const selectedType = this.value;
            const form = this.closest('form');
            const templateSelector = form.querySelector('.template-task-selector');
            const regularSelector = form.querySelector('.regular-task-selector');

            // إخفاء جميع القوائم أولاً
            if (templateSelector) templateSelector.classList.add('d-none');
            if (regularSelector) regularSelector.classList.add('d-none');

            // إظهار القائمة المناسبة حسب النوع المختار
            if (selectedType === 'template_task' && templateSelector) {
                templateSelector.classList.remove('d-none');
            } else if (selectedType === 'regular_task' && regularSelector) {
                regularSelector.classList.remove('d-none');
            }
        });
    });

        // إدارة إظهار/إخفاء قائمة الملفات للرد عليها
    const replyCheckboxes = document.querySelectorAll('input[name="is_reply"]');

    replyCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const form = this.closest('form');
            const replySelector = form.querySelector('.reply-file-selector');

            if (this.checked) {
                replySelector.classList.remove('d-none');

                // التحقق من وجود ملفات للرد عليها
                const selectElement = replySelector.querySelector('select');
                const options = selectElement.querySelectorAll('option:not([value=""])');

                if (options.length === 0 || (options.length === 1 && options[0].disabled)) {
                    toastr.warning('لا توجد ملفات للرد عليها في هذا المجلد', 'تنبيه ⚠️');
                    this.checked = false;
                    replySelector.classList.add('d-none');
                }
            } else {
                replySelector.classList.add('d-none');
                // إعادة تعيين اختيار الملف
                const selectElement = replySelector.querySelector('select');
                if (selectElement) {
                    selectElement.value = '';
                }
            }
        });
    });
}





/**
 * تهيئة معالجات النقر على المرفقات
 */
function initAttachmentClickHandlers() {

    document.addEventListener('click', function(e) {
        // التحقق من أن e.target موجود وهو عنصر DOM صحيح
        if (e.target && typeof e.target.closest === 'function') {
            const attachmentLink = e.target.closest('.attachment-name, .attachment-info a');
            if (attachmentLink) {

                attachmentLink.style.transform = 'scale(0.98)';
                attachmentLink.style.opacity = '0.7';
                setTimeout(() => {
                    attachmentLink.style.transform = '';
                    attachmentLink.style.opacity = '';
                }, 150);

                // إظهار رسالة تحميل
                if (typeof toastr !== 'undefined') {
                    toastr.info('جاري فتح الملف...', 'تحميل 📁', {
                        timeOut: 2000,
                        progressBar: true
                    });
                }
            }
        }
    });

    // إضافة تأثير hover محسن
    document.addEventListener('mouseenter', function(e) {
        // التحقق من أن e.target موجود وهو عنصر DOM صحيح
        if (e.target && typeof e.target.closest === 'function') {
            const attachmentLink = e.target.closest('.attachment-name, .attachment-info a');
            if (attachmentLink) {
                attachmentLink.style.transition = 'all 0.2s ease';
            }
        }
    }, true);

    // إضافة تأثير للنقر على المرفق كاملاً
    document.addEventListener('click', function(e) {
        // التحقق من أن e.target موجود وهو عنصر DOM صحيح
        if (e.target && typeof e.target.closest === 'function') {
            const attachmentItem = e.target.closest('.attachment-item, .list-group-item');
            if (attachmentItem && !e.target.closest('.dropdown, .btn, .form-check')) {
                const attachmentLink = attachmentItem.querySelector('.attachment-name, .attachment-info a');
                if (attachmentLink) {
                    attachmentLink.click();
                }
            }
        }
    });

    // إضافة معالج أحداث لأزرار نسخ الرابط
    initCopyLinkHandlers();
}

/**
 * تهيئة معالجات نسخ الرابط
 */
function initCopyLinkHandlers() {
    document.addEventListener('click', function(e) {
        if (e.target && typeof e.target.closest === 'function') {
            const copyButton = e.target.closest('.copy-attachment-link');
            if (copyButton) {
                e.preventDefault();
                e.stopPropagation();

                const attachmentUrl = copyButton.dataset.attachmentUrl;
                const attachmentId = copyButton.dataset.attachmentId;

                if (attachmentUrl) {
                    copyAttachmentLinkToClipboard(attachmentUrl, attachmentId, copyButton);
                }
            }
        }
    });
}

/**
 * نسخ رابط المرفق إلى الحافظة
 */
async function copyAttachmentLinkToClipboard(url, attachmentId, buttonElement) {
    try {
        // إنشاء الرابط الكامل - التحقق من أنه ليس رابط كامل بالفعل
        const fullUrl = url.startsWith('http') ? url : window.location.origin + url;

        // محاولة نسخ الرابط إلى الحافظة
        if (navigator.clipboard && window.isSecureContext) {
            // الطريقة الحديثة - تعمل فقط في HTTPS
            await navigator.clipboard.writeText(fullUrl);
        } else {
            // الطريقة التقليدية للمتصفحات القديمة أو HTTP
            const textArea = document.createElement('textarea');
            textArea.value = fullUrl;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();

            const successful = document.execCommand('copy');
            document.body.removeChild(textArea);

            if (!successful) {
                throw new Error('فشل في نسخ الرابط');
            }
        }

        // إظهار رسالة نجاح
        if (typeof toastr !== 'undefined') {
            toastr.success('تم نسخ الرابط إلى الحافظة بنجاح! 📋', 'نسخ ناجح ✅', {
                timeOut: 3000,
                progressBar: true
            });
        } else {
            alert('تم نسخ الرابط إلى الحافظة بنجاح!');
        }

        // تأثير بصري للزر
        buttonElement.classList.add('copying');
        const icon = buttonElement.querySelector('i');
        if (icon) {
            const originalClass = icon.className;
            icon.className = 'fas fa-check text-success me-2';
            setTimeout(() => {
                icon.className = originalClass;
                buttonElement.classList.remove('copying');
            }, 2000);
        }

    } catch (error) {
        console.error('خطأ في نسخ الرابط:', error);

        // إظهار رسالة خطأ
        if (typeof toastr !== 'undefined') {
            toastr.error('فشل في نسخ الرابط. جرب مرة أخرى', 'خطأ ❌');
        } else {
            alert('فشل في نسخ الرابط. جرب مرة أخرى');
        }
    }
}
