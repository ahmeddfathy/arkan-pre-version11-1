// Task Items Management Functions

let currentTaskItemsType = '';
let currentTaskItemsId = '';
let currentUserType = '';

/**
 * تحميل بنود المهمة
 */
async function loadTaskItems(taskType, taskId, userType = 'viewer') {
    currentTaskItemsType = taskType;
    currentTaskItemsId = taskId;
    currentUserType = userType;

    // ✅ البحث عن الـ container داخل الـ sidebar المفتوح فقط
    const sidebar = document.getElementById('taskSidebar');
    const itemsContainer = sidebar ? sidebar.querySelector('#taskItemsContainer') : document.getElementById('taskItemsContainer');

    if (!itemsContainer) {
        console.warn('⚠️ taskItemsContainer not found!');
        return;
    }

    try {
        let url = '';

        if (userType === 'creator') {
            // منشئ المهمة: يحمل البنود الأساسية
            if (taskType === 'template') {
                url = `/template-tasks/${taskId}/items`;
            } else {
                url = `/tasks/${taskId}/items`;
            }
        } else if (userType === 'assignee') {
            // صاحب المهمة: يحمل البنود مع حالاتها
            if (taskType === 'template') {
                url = `/template-task-users/${taskId}/items`;
            } else {
                url = `/task-users/${taskId}/items`;
            }
        } else {
            // مستخدم عادي: يحمل البنود الأساسية فقط
            if (taskType === 'template') {
                url = `/template-tasks/${taskId}/items`;
            } else {
                url = `/tasks/${taskId}/items`;
            }
        }


        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            credentials: 'same-origin'
        });

        if (!response.ok) {
            const errorText = await response.text();
            console.error('❌ HTTP error response:', errorText);
            throw new Error(`HTTP error! status: ${response.status} - ${response.statusText}`);
        }

        const data = await response.json();

        if (data.success) {
            displayItems(data.items || [], currentUserType);
        } else {
            console.error('❌ Items API error:', data.message);
            throw new Error(data.message || 'فشل في تحميل البنود');
        }

    } catch (error) {
        console.error('❌ Error loading task items:', error);

        itemsContainer.innerHTML = `
            <div class="text-center py-3">
                <i class="fas fa-exclamation-triangle text-warning mb-2" style="font-size: 24px;"></i>
                <p class="text-muted mb-0" style="font-size: 12px;">حدث خطأ في تحميل البنود</p>
                <small class="text-muted" style="font-size: 10px;">${error.message}</small>
                <button class="btn btn-sm btn-outline-primary mt-2" onclick="loadTaskItems('${taskType}', '${taskId}')">
                    <i class="fas fa-refresh me-1"></i>إعادة المحاولة
                </button>
            </div>
        `;
    }
}

/**
 * عرض البنود مع خيارات الحالات حسب نوع المستخدم
 */
function displayItems(items, userType = 'viewer') {
    // ✅ البحث عن الـ container داخل الـ sidebar المفتوح فقط
    const sidebar = document.getElementById('taskSidebar');
    const itemsContainer = sidebar ? sidebar.querySelector('#taskItemsContainer') : document.getElementById('taskItemsContainer');

    if (!itemsContainer) {
        console.error('❌ taskItemsContainer not found in displayItems!');
        return;
    }

    if (!items || items.length === 0) {
        itemsContainer.innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-list-check text-muted mb-2" style="font-size: 32px; opacity: 0.5;"></i>
                <p class="text-muted mb-0" style="font-size: 13px;">لا توجد بنود لهذه المهمة</p>
                <small class="text-muted" style="font-size: 11px;">اضغط "إضافة بند" لإنشاء بند جديد</small>
            </div>
        `;
        return;
    }

    // ترتيب البنود حسب الترتيب
    const sortedItems = items.sort((a, b) => (a.order || 0) - (b.order || 0));

    try {
        const itemsHtml = sortedItems.map((item, index) => {
            // ✅ إضافة ID مؤقت إذا لم يكن موجوداً
            if (!item.id) {
                item.id = `temp_${Date.now()}_${index}`;
            }

            // تحديد لون الحالة
            const statusColors = {
                'pending': { bg: '#f8f9fa', border: '#dee2e6', text: '#6c757d', icon: 'fa-clock' },
                'completed': { bg: '#d4edda', border: '#c3e6cb', text: '#155724', icon: 'fa-check-circle' },
                'not_applicable': { bg: '#fff3cd', border: '#ffeaa7', text: '#856404', icon: 'fa-times-circle' }
            };

            const currentStatus = item.status || 'pending';
            const statusStyle = statusColors[currentStatus];

            return `
                <div class="item-card mb-3" data-item-id="${item.id}">
                    <div class="border rounded p-3" style="background: ${statusStyle.bg}; border-color: ${statusStyle.border} !important;">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="item-content" style="flex: 1;">
                                <div class="d-flex align-items-center mb-1">
                                    <div class="item-order me-2" style="width: 24px; height: 24px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 600; color: #6c757d;">
                                        ${item.order || 1}
                                    </div>
                                    <h6 class="mb-0 fw-semibold" style="font-size: 14px; color: #212529;">${escapeHtml(item.title)}</h6>
                                    <div class="item-status ms-2">
                                        <i class="fas ${statusStyle.icon} text-${currentStatus === 'completed' ? 'success' : currentStatus === 'not_applicable' ? 'warning' : 'secondary'}" style="font-size: 12px;"></i>
                                    </div>
                                </div>
                                ${item.description ? `
                                    <p class="mb-0 text-muted" style="font-size: 12px; line-height: 1.4; margin-top: 4px;">${escapeHtml(item.description)}</p>
                                ` : ''}
                                ${item.note ? `
                                    <div class="mt-2 p-2 rounded" style="background: rgba(0,0,0,0.05); border-left: 3px solid #007bff;">
                                        <small class="text-muted" style="font-size: 11px;"><i class="fas fa-sticky-note me-1"></i>ملاحظة: ${escapeHtml(item.note)}</small>
                                    </div>
                                ` : ''}
                            </div>
                            <div class="item-actions ms-2">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                                            data-bs-toggle="dropdown" aria-expanded="false"
                                            style="font-size: 11px; padding: 2px 6px;">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        ${userType === 'assignee' ? `
                                            <!-- صاحب المهمة: يمكنه تحديث الحالة فقط -->
                                            <li><h6 class="dropdown-header" style="font-size: 11px; font-weight: 600;">تحديث الحالة</h6></li>
                                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="updateItemStatus('${item.id}', 'completed')">
                                                <i class="fas fa-check-circle text-success me-1"></i>تم
                                            </a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="updateItemStatus('${item.id}', 'pending')">
                                                <i class="fas fa-clock text-secondary me-1"></i>لم يتم
                                            </a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="updateItemStatus('${item.id}', 'not_applicable')">
                                                <i class="fas fa-times-circle text-warning me-1"></i>غير موجود
                                            </a></li>
                                        ` : userType === 'creator' ? `
                                            <!-- منشئ المهمة: يمكنه تعديل وحذف البنود -->
                                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="editItem('${item.id}', '${escapeHtml(item.title)}', '${escapeHtml(item.description || '')}')">
                                                <i class="fas fa-edit me-1"></i>تعديل
                                            </a></li>
                                            <li><a class="dropdown-item text-danger" href="javascript:void(0)" onclick="deleteItem('${item.id}')">
                                                <i class="fas fa-trash me-1"></i>حذف
                                            </a></li>
                                        ` : `
                                            <!-- مستخدم عادي: لا يمكنه فعل شيء -->
                                            <li><span class="dropdown-item-text text-muted" style="font-size: 11px;">
                                                <i class="fas fa-info-circle me-1"></i>لا يمكنك تعديل هذه البنود
                                            </span></li>
                                        `}
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        itemsContainer.innerHTML = itemsHtml;
    } catch (error) {
        console.error('❌ Error in displayItems:', error);
        itemsContainer.innerHTML = `
            <div class="text-center py-3">
                <i class="fas fa-exclamation-triangle text-danger mb-2" style="font-size: 24px;"></i>
                <p class="text-danger mb-0" style="font-size: 12px;">حدث خطأ في عرض البنود</p>
            </div>
        `;
    }
}

/**
 * إظهار نموذج إضافة بند (للمنشئ فقط)
 */
function showAddItemForm(taskType, taskId) {
    // التحقق من أن المستخدم هو منشئ المهمة
    if (currentUserType !== 'creator') {
        alert('فقط منشئ المهمة يمكنه إضافة البنود');
        return;
    }

    currentTaskItemsType = taskType;
    currentTaskItemsId = taskId;

    const addItemForm = document.getElementById('addItemForm');
    if (addItemForm) {
        addItemForm.style.display = 'block';
        document.getElementById('itemTitle').focus();
    }
}

/**
 * إخفاء نموذج إضافة بند
 */
function hideAddItemForm() {
    const addItemForm = document.getElementById('addItemForm');
    if (addItemForm) {
        addItemForm.style.display = 'none';
        document.getElementById('itemTitle').value = '';
        document.getElementById('itemDescription').value = '';
    }
}

/**
 * حفظ بند جديد (للمنشئ فقط)
 */
async function saveItem() {
    // التحقق من أن المستخدم هو منشئ المهمة
    if (currentUserType !== 'creator') {
        alert('فقط منشئ المهمة يمكنه إضافة البنود');
        return;
    }

    const title = document.getElementById('itemTitle').value.trim();
    const description = document.getElementById('itemDescription').value.trim();

    if (!title) {
        alert('يرجى إدخال عنوان البند');
        return;
    }

    try {
        let url = '';
        if (currentTaskItemsType === 'template') {
            url = `/template-tasks/${currentTaskItemsId}/items`;
        } else {
            url = `/tasks/${currentTaskItemsId}/items`;
        }

        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                title: title,
                description: description
            })
        });

        const data = await response.json();

        if (data.success) {
            hideAddItemForm();
            // ✅ إعادة تحميل البنود مع الحفاظ على userType الصحيح
            loadTaskItems(currentTaskItemsType, currentTaskItemsId, currentUserType);

            // إظهار رسالة نجاح
            if (typeof toastr !== 'undefined') {
                toastr.success('تم إضافة البند بنجاح');
            }
        } else {
            throw new Error(data.message || 'فشل في إضافة البند');
        }

    } catch (error) {
        console.error('Error saving item:', error);
        alert('حدث خطأ أثناء إضافة البند: ' + error.message);
    }
}

/**
 * تعديل بند (للمنشئ فقط)
 */
function editItem(itemId, currentTitle, currentDescription) {
    // التحقق من أن المستخدم هو منشئ المهمة
    if (currentUserType !== 'creator') {
        alert('فقط منشئ المهمة يمكنه تعديل البنود');
        return;
    }

    const newTitle = prompt('عنوان البند:', currentTitle);
    if (newTitle === null) return; // المستخدم ألغى

    const newDescription = prompt('وصف البند:', currentDescription);
    if (newDescription === null) return; // المستخدم ألغى

    updateItem(itemId, newTitle, newDescription);
}

/**
 * تحديث بند (للمنشئ فقط)
 */
async function updateItem(itemId, title, description) {
    // التحقق من أن المستخدم هو منشئ المهمة
    if (currentUserType !== 'creator') {
        alert('فقط منشئ المهمة يمكنه تعديل البنود');
        return;
    }

    try {
        let url = '';
        if (currentTaskItemsType === 'template') {
            url = `/template-tasks/${currentTaskItemsId}/items/${itemId}`;
        } else {
            url = `/tasks/${currentTaskItemsId}/items/${itemId}`;
        }

        const response = await fetch(url, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                title: title,
                description: description
            })
        });

        const data = await response.json();

        if (data.success) {
            // ✅ إعادة تحميل البنود مع الحفاظ على userType الصحيح
            loadTaskItems(currentTaskItemsType, currentTaskItemsId, currentUserType);

            // إظهار رسالة نجاح
            if (typeof toastr !== 'undefined') {
                toastr.success('تم تحديث البند بنجاح');
            }
        } else {
            throw new Error(data.message || 'فشل في تحديث البند');
        }

    } catch (error) {
        console.error('Error updating item:', error);
        alert('حدث خطأ أثناء تحديث البند: ' + error.message);
    }
}

/**
 * تحديث حالة البند (لصاحب المهمة فقط)
 */
async function updateItemStatus(itemId, status) {
    // التحقق من أن المستخدم هو صاحب المهمة
    if (currentUserType !== 'assignee') {
        alert('فقط صاحب المهمة يمكنه تحديث حالة البنود');
        return;
    }

    try {
        // إذا كانت الحالة "غير موجود"، اطلب ملاحظة
        let note = null;
        if (status === 'not_applicable') {
            note = prompt('يرجى إدخال سبب عدم وجود هذا البند:');
            if (note === null) return; // المستخدم ألغى
            if (!note.trim()) {
                alert('يرجى إدخال سبب عدم وجود البند');
                return;
            }
        }

        let url = '';
        if (currentTaskItemsType === 'template') {
            // لمهام القوالب، نحتاج template_task_user_id
            url = `/template-task-users/${currentTaskItemsId}/items/${itemId}/status`;
        } else {
            // للمهام العادية، نحتاج task_user_id
            url = `/task-users/${currentTaskItemsId}/items/${itemId}/status`;
        }

        const response = await fetch(url, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                status: status,
                note: note
            })
        });

        const data = await response.json();

        if (data.success) {
            // ✅ إعادة تحميل البنود مع الحفاظ على userType الصحيح
            loadTaskItems(currentTaskItemsType, currentTaskItemsId, currentUserType);

            // إظهار رسالة نجاح
            if (typeof toastr !== 'undefined') {
                const statusTexts = {
                    'completed': 'تم',
                    'pending': 'لم يتم',
                    'not_applicable': 'غير موجود'
                };
                toastr.success(`تم تحديث حالة البند إلى: ${statusTexts[status]}`);
            }
        } else {
            throw new Error(data.message || 'فشل في تحديث حالة البند');
        }

    } catch (error) {
        console.error('Error updating item status:', error);
        alert('حدث خطأ أثناء تحديث حالة البند: ' + error.message);
    }
}

/**
 * حذف بند (للمنشئ فقط)
 */
async function deleteItem(itemId) {
    // التحقق من أن المستخدم هو منشئ المهمة
    if (currentUserType !== 'creator') {
        alert('فقط منشئ المهمة يمكنه حذف البنود');
        return;
    }

    if (!confirm('هل أنت متأكد من حذف هذا البند؟')) {
        return;
    }

    try {
        let url = '';
        if (currentTaskItemsType === 'template') {
            url = `/template-tasks/${currentTaskItemsId}/items/${itemId}`;
        } else {
            url = `/tasks/${currentTaskItemsId}/items/${itemId}`;
        }

        const response = await fetch(url, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            credentials: 'same-origin'
        });

        const data = await response.json();

        if (data.success) {
            // ✅ إعادة تحميل البنود مع الحفاظ على userType الصحيح
            loadTaskItems(currentTaskItemsType, currentTaskItemsId, currentUserType);

            // إظهار رسالة نجاح
            if (typeof toastr !== 'undefined') {
                toastr.success('تم حذف البند بنجاح');
            }
        } else {
            throw new Error(data.message || 'فشل في حذف البند');
        }

    } catch (error) {
        console.error('Error deleting item:', error);
        alert('حدث خطأ أثناء حذف البند: ' + error.message);
    }
}

/**
 * Utility function to escape HTML
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Export functions to global scope
window.loadTaskItems = loadTaskItems;
window.showAddItemForm = showAddItemForm;
window.hideAddItemForm = hideAddItemForm;
window.saveItem = saveItem;
window.editItem = editItem;
window.updateItem = updateItem;
window.deleteItem = deleteItem;
window.updateItemStatus = updateItemStatus;
