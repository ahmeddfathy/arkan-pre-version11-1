$(document).ready(function() {
    initializeModalHandlers();
});

function initializeModalHandlers() {
    $(document).on('click', '.view-task', function() {
        const taskId = $(this).data('id');
        const taskUserId = $(this).data('task-user-id') || taskId;

        const $row = $(this).closest('tr');
        const $card = $(this).closest('.kanban-card');

        let isTemplate = $row.data('is-template') || $row.attr('data-is-template') ||
                        $card.data('is-template') || $card.attr('data-is-template') ||
                        $(this).data('is-template') || $(this).attr('data-is-template');

        const taskName = $(this).data('task-name');

        // محاولة الحصول على البيانات من العنصر الأب إذا لم تكن متاحة
        const finalTaskId = taskId || $row.data('task-id') || $card.data('task-id');
        const finalTaskUserId = taskUserId || $row.data('task-user-id') || $card.data('task-user-id') || finalTaskId;
        const finalIsTemplate = isTemplate || $row.data('is-template') || $card.data('is-template');

        // استخدام السايد بار للجميع
        const taskType = (finalIsTemplate === 'true' || finalIsTemplate === true) ? 'template' : 'regular';

        // ✅ استخدم TaskUser ID دائماً (سواء عادية أو قالب)
        const targetId = finalTaskUserId;

        console.log('🔍 Opening task sidebar from modals.js:', {
            originalTaskId: taskId,
            originalTaskUserId: taskUserId,
            finalTaskId: finalTaskId,
            finalTaskUserId: finalTaskUserId,
            taskType: taskType,
            targetId: targetId,
            isTemplate: finalIsTemplate,
            parentRow: $row.length > 0,
            parentCard: $card.length > 0
        });

        // التأكد من وجود المعاملات المطلوبة
        if (!targetId) {
            console.error('❌ Missing targetId:', {
                originalTaskId: taskId,
                originalTaskUserId: taskUserId,
                finalTaskId: finalTaskId,
                finalTaskUserId: finalTaskUserId,
                taskType: taskType,
                parentRow: $row.length > 0,
                parentCard: $card.length > 0,
                parentRowData: $row.data(),
                parentCardData: $card.data()
            });
            alert('خطأ: لم يتم العثور على معرف المهمة');
            return;
        }

        // التأكد من أن دالة openTaskSidebar موجودة
        if (typeof openTaskSidebar === 'function') {
            openTaskSidebar(taskType, targetId);
        } else {
            console.error('❌ openTaskSidebar function not found');
        }
    });

    $('.edit-task').click(function() {
        const taskId = $(this).data('id');
        const taskUserId = $(this).data('task-user-id') || taskId;
        const isTemplate = $(this).closest('tr').data('is-template');

        console.log('🔍 Edit Task Clicked:', {
            taskId: taskId,
            taskUserId: taskUserId,
            isTemplate: isTemplate,
            button: $(this)
        });

        // السماح بتعديل مهام القوالب أيضاً
        if (isTemplate === 'true' || isTemplate === true) {
            // للمهام القوالب: استخدم TemplateTaskUser ID
            console.log('➡️ Loading TEMPLATE task:', taskUserId);
            loadTaskForEdit(taskUserId, true);
        } else {
            // للمهام العادية: استخدم Task ID
            console.log('➡️ Loading REGULAR task:', taskId);
            loadTaskForEdit(taskId, false);
        }
    });

    $('.delete-task').click(function() {
        const taskId = $(this).data('id');
        const isTemplate = $(this).closest('tr').data('is-template');

        if (isTemplate === 'true' || isTemplate === true) {
            Swal.fire({
                icon: 'info',
                title: 'مهمة قالب',
                text: 'عذراً، حذف مهام القوالب يتم من خلال نظام القوالب.',
                confirmButtonText: 'موافق'
            });
            return;
        }

        $('#deleteTaskForm').attr('action', '/tasks/' + taskId);
        $('#deleteTaskModal').modal('show');
    });

    $('#createTaskModal').on('hidden.bs.modal', function() {
        resetCreateTaskModal();
    });

    $('#editTaskModal').on('hidden.bs.modal', function() {
        resetEditTaskModal();
    });
}

function loadTaskDetails(taskUserId, isTemplate = false) {
    taskUserId = String(taskUserId).trim();
    if (!taskUserId || taskUserId === 'undefined' || taskUserId === 'null') {
        return;
    }

    // عرض مودال التفاصيل للمهام العادية
    $('#viewTaskModalLabel').text('جاري تحميل تفاصيل المهمة...');
    $('#viewTaskModal').modal('show');

    $.ajax({
        url: '/tasks/' + taskUserId,
        method: 'GET',
        dataType: 'json',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        success: function(data) {
            let titleText = 'تفاصيل المهمة: ' + (data.task?.name || data.name || '');

            // إضافة تنبيه للمهام غير المُعيَّنة
            if (data.warning) {
                titleText += ' (غير مُعيَّنة)';
            }

            $('#viewTaskModalLabel').text(titleText);
            populateViewModal(data.task || data, data.warning);
        },
        error: function(xhr, status, error) {
            console.error('خطأ في تحميل تفاصيل المهمة:', {
                status: xhr.status,
                responseText: xhr.responseText,
                error: error,
                taskUserId: taskUserId,
                isTemplate: isTemplate
            });

            const errorMessage = xhr.status === 500 ? 'خطأ في الخادم - ' + (error || 'Internal Server Error') : 'حدث خطأ في تحميل بيانات المهمة';
            $('#viewTaskModalLabel').text(errorMessage);
        }
    });
}

function populateViewModal(data, warning = null) {
    $('#view-task-name').text(data.name || 'غير محدد');
    $('#view-project-name').text(data.project ? data.project.name : 'غير محدد');
    $('#view-service-name').text(data.service ? data.service.name : 'غير محدد');

    // إضافة تنبيه للمهام غير المُعيَّنة
    const $warningDiv = $('#view-task-warning');
    if (warning) {
        if ($warningDiv.length === 0) {
            $('#view-task-name').after(`
                <div id="view-task-warning" class="alert alert-warning mt-2">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    ${warning}
                </div>
            `);
        } else {
            $warningDiv.html(`<i class="fas fa-exclamation-triangle me-2"></i>${warning}`).show();
        }
    } else {
        $warningDiv.hide();
    }

    let statusText = '';
    switch(data.status) {
        case 'new': statusText = 'جديدة'; break;
        case 'in_progress': statusText = 'قيد التنفيذ'; break;
        case 'paused': statusText = 'متوقفة'; break;
        case 'completed': statusText = 'مكتملة'; break;
        case 'cancelled': statusText = 'ملغاة'; break;
        default: statusText = data.status || 'غير محدد';
    }

    $('#view-status').text(statusText);

    if (data.is_template) {
        $('#view-due-date').text('غير محدد (مهمة قالب)');
    } else {
        $('#view-due-date').text(data.due_date || 'غير محدد');
    }

    $('#view-estimated-time').text(`${data.estimated_hours || 0}:${String(data.estimated_minutes || 0).padStart(2, '0')}`);
    $('#view-actual-time').text(`${data.actual_hours || 0}:${String(data.actual_minutes || 0).padStart(2, '0')}`);
    $('#view-created-at').text(data.created_at || 'غير محدد');
    $('#view-updated-at').text(data.updated_at || 'غير محدد');
    $('#view-description').text(data.description || 'لا يوجد وصف');

    let usersHtml = '';

    // التحقق من وجود مستخدم مُعيَّن للمهمة
    if (data.is_unassigned) {
        usersHtml = `
            <tr>
                <td colspan="5" class="text-center text-muted">
                    <i class="fas fa-user-slash me-2"></i>
                    هذه المهمة غير مُعيَّنة لأي مستخدم حالياً
                </td>
            </tr>
        `;
    } else if (data.user) {
        // للمهام العادية التي لها مستخدم واحد
        const userStatusText = data.status || 'غير محدد';
        usersHtml = `
            <tr>
                <td>${data.user.name || 'غير محدد'}</td>
                <td>مُعيَّن</td>
                <td>${userStatusText}</td>
                <td>${data.estimated_hours || 0}:${String(data.estimated_minutes || 0).padStart(2, '0')}</td>
                <td>${Math.floor((data.actual_minutes || 0) / 60)}:${String((data.actual_minutes || 0) % 60).padStart(2, '0')}</td>
            </tr>
        `;
    } else if (data.users && data.users.length > 0) {
        data.users.forEach(function(user) {
            if (!user || !user.pivot) {
                return;
            }

            let userStatusText = '';
            switch(user.pivot.status) {
                case 'new': userStatusText = 'جديدة'; break;
                case 'in_progress': userStatusText = 'قيد التنفيذ'; break;
                case 'paused': userStatusText = 'متوقفة'; break;
                case 'completed': userStatusText = 'مكتملة'; break;
                case 'cancelled': userStatusText = 'ملغاة'; break;
                default: userStatusText = user.pivot.status || 'غير محدد';
            }

            usersHtml += `
                <tr>
                    <td>${user.name || 'غير محدد'}</td>
                    <td>${user.pivot.role || 'غير محدد'}</td>
                    <td>${userStatusText}</td>
                    <td>${user.pivot.estimated_hours || 0}:${String(user.pivot.estimated_minutes || 0).padStart(2, '0')}</td>
                    <td>${user.pivot.actual_hours || 0}:${String(user.pivot.actual_minutes || 0).padStart(2, '0')}</td>
                </tr>
            `;
        });
    } else {
        usersHtml = `
            <tr>
                <td colspan="5" class="text-center text-muted">
                    <i class="fas fa-user-slash me-2"></i>
                    لا يوجد مستخدمون مُعيَّنون لهذه المهمة
                </td>
            </tr>
        `;
    }
    $('#view-users-table tbody').html(usersHtml);
}

function loadTaskForEdit(taskId, isTemplate = false) {
    const modalTitle = isTemplate ? 'تعديل مهمة القالب' : 'تعديل المهمة';
    $('#editTaskModalLabel').text(modalTitle + ' - جاري التحميل...');
    $('#editTaskModal').modal('show');

    $('#edit_assignUsersContainer').empty();
    resetEditTaskModal();

    // بناء URL مع تمرير معلمة is_template للقوالب
    // استخدام /edit endpoint بدلاً من show
    const url = '/tasks/' + taskId + '/edit' + (isTemplate ? '?is_template=true' : '');

    console.log('📡 AJAX Request:', {
        url: url,
        taskId: taskId,
        isTemplate: isTemplate
    });

    $.ajax({
        url: url,
        method: 'GET',
        dataType: 'json',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        success: function(data) {
            console.log('✅ Task loaded successfully:', data);
            populateEditModal(data, taskId, isTemplate);
        },
        error: function(xhr, status, error) {
            console.error('❌ خطأ في تحميل المهمة:', {
                status: xhr.status,
                statusText: xhr.statusText,
                responseText: xhr.responseText.substring(0, 500),
                error: error,
                taskId: taskId,
                isTemplate: isTemplate,
                url: url
            });

            // محاولة parse الـ response إذا كان JSON
            let errorDetails = '';
            try {
                const jsonResponse = JSON.parse(xhr.responseText);
                errorDetails = jsonResponse.message || jsonResponse.error || '';
                console.error('📋 Error details:', jsonResponse);
            } catch (e) {
                errorDetails = 'خطأ غير متوقع';
            }

            const errorTitle = isTemplate ? 'حدث خطأ في تحميل بيانات مهمة القالب: ' : 'حدث خطأ في تحميل بيانات المهمة: ';
            const errorMessage = errorDetails || (xhr.status === 404 ? 'المهمة غير موجودة' : (xhr.status === 500 ? 'خطأ في الخادم' : (error || 'خطأ غير معروف')));
            $('#editTaskModalLabel').text(errorTitle + errorMessage);
        }
    });
}

function populateEditModal(data, taskId, isTemplate = false) {
    const modalTitle = isTemplate ? 'تعديل مهمة القالب' : 'تعديل المهمة';
    $('#editTaskModalLabel').text(modalTitle + ': ' + (data.name || ''));

    // تحديد action URL حسب نوع المهمة
    const formAction = isTemplate ? '/tasks/' + taskId + '?is_template=true' : '/tasks/' + taskId;
    $('#editTaskForm').attr('action', formAction);

    $('#edit_name').val(data.name || '');
    $('#edit_project_id').val(data.project_id || '');
    $('#edit_service_id').val(data.service_id || '');
    $('#edit_description').val(data.description || '');

    let estimatedHours = data.estimated_hours || 0;
    let estimatedMinutes = data.estimated_minutes || 0;
    let dueDate = data.due_date;
    let isFlexibleTime = data.is_flexible_time || false;
    let isAdditionalTask = data.is_additional_task || false;

    // للمهام العادية، جرب تاخد البيانات من pivot
    if (data.users && data.users.length > 0 && data.users[0].pivot) {
        const firstUserPivot = data.users[0].pivot;

        estimatedHours = firstUserPivot.estimated_hours !== undefined ? firstUserPivot.estimated_hours : estimatedHours;
        estimatedMinutes = firstUserPivot.estimated_minutes !== undefined ? firstUserPivot.estimated_minutes : estimatedMinutes;

        dueDate = firstUserPivot.due_date || dueDate;

        isFlexibleTime = firstUserPivot.is_flexible_time !== undefined ? firstUserPivot.is_flexible_time : isFlexibleTime;
        isAdditionalTask = firstUserPivot.is_additional_task !== undefined ? firstUserPivot.is_additional_task : isAdditionalTask;
    }

    // للمهام الجرافيكية، استخدم البيانات من المهمة نفسها
    const isGraphicTask = data.graphic_task_types && data.graphic_task_types.length > 0;
    if (isGraphicTask) {
        estimatedHours = data.estimated_hours || 0;
        estimatedMinutes = data.estimated_minutes || 0;
        isFlexibleTime = data.is_flexible_time || false;
    }

    $('#edit_estimated_hours').val(estimatedHours);
    $('#edit_estimated_minutes').val(estimatedMinutes);

    // لـ lock الحقول للمهام الجرافيكية
    if (isGraphicTask && !isFlexibleTime) {
        $('#edit_estimated_hours, #edit_estimated_minutes').attr('readonly', true);
        $('#edit_estimated_hours, #edit_estimated_minutes').addClass('bg-light text-muted');

        // إضافة رسالة توضيحية
        if ($('#edit_graphic_time_lock_notice').length === 0) {
            $('#edit_time_estimation_section').after(`
                <div class="alert alert-warning mt-2" id="edit_graphic_time_lock_notice">
                    <i class="fas fa-lock me-1"></i>
                    <strong>مهمة جرافيكية:</strong> الوقت المقدر محدد تلقائياً من نوع المهمة الجرافيكية ولا يمكن تعديله.
                </div>
            `);
        }
    } else {
        $('#edit_estimated_hours, #edit_estimated_minutes').attr('readonly', false);
        $('#edit_estimated_hours, #edit_estimated_minutes').removeClass('bg-light text-muted');
        $('#edit_graphic_time_lock_notice').remove();
    }

    if (isFlexibleTime) {
        $('#edit_is_flexible_time').prop('checked', true);
        $('#edit_is_flexible_time_hidden').val('1');
        $('#edit_time_estimation_section').hide();
        if ($('#edit_flexible_time_notice').length === 0) {
            $('#edit_time_estimation_section').after(`
                <div class="alert alert-info mt-2" id="edit_flexible_time_notice">
                    <i class="fas fa-info-circle"></i>
                    <strong>مهمة مرنة:</strong> هذه المهمة لا تحتاج لوقت مقدر محدد.
                </div>
            `);
        }
    } else {
        $('#edit_is_flexible_time').prop('checked', false);
        $('#edit_is_flexible_time_hidden').val('0');
        $('#edit_time_estimation_section').show();
        $('#edit_flexible_time_notice').remove();
    }

    // تعيين قيمة المهمة الإضافية
    if (isAdditionalTask) {
        $('#edit_is_additional_task').prop('checked', true);
        $('#edit_is_additional_task_hidden').val('1');
    } else {
        $('#edit_is_additional_task').prop('checked', false);
        $('#edit_is_additional_task_hidden').val('0');
    }

    if (dueDate) {
        let formattedDate = '';
        try {

            const date = new Date(dueDate);
            if (!isNaN(date.getTime())) {
                formattedDate = new Date(date.getTime() - (date.getTimezoneOffset() * 60000))
                    .toISOString()
                    .slice(0, 16);
            }
        } catch (e) {
            console.error('Error formatting due date:', e);
        }
        $('#edit_due_date').val(formattedDate);
    } else {
        $('#edit_due_date').val('');
    }

    $('#edit_status').val(data.status || 'new');
    $('#edit_points').val(data.points || 10);

    // ✅ مسح الـ container الأول قبل الإضافة عشان متتكررش
    $('#edit_assignUsersContainer').empty();

    if (data.users && data.users.length > 0) {
        const user = data.users[0];
        addUserRowToEdit(user);
    } else {
        addUserRowToEdit(null);
    }

    if (data.graphic_task_types && data.graphic_task_types.length > 0) {
        const graphicTaskType = data.graphic_task_types[0];
        $('#edit_graphic_task_type_id').val(graphicTaskType.id);
        $('#edit_graphic_task_type_section').show();
        const details = `النقاط: ${graphicTaskType.points} | الوقت المتوقع: ${graphicTaskType.min_minutes}-${graphicTaskType.max_minutes} دقيقة | المتوسط: ${graphicTaskType.average_minutes} دقيقة`;
        $('#edit_task_details').text(details);
        $('#edit_graphic_task_info').show();
    } else {
        $('#edit_graphic_task_type_section').hide();
        $('#edit_graphic_task_type_id').val('');
        $('#edit_graphic_task_info').hide();
    }

    const selectedService = $('#edit_service_id option:selected');
    const serviceName = selectedService.data('service-name') || selectedService.text();
    if (serviceName && (serviceName.includes('جرافيك') || serviceName.includes('تصميم') || serviceName.includes('graphic') || serviceName.includes('design'))) {
        $('#edit_graphic_task_type_section').show();
        $('#edit_estimated_hours, #edit_estimated_minutes').closest('.col-md-3').hide();
        $('#edit_estimated_hours, #edit_estimated_minutes').attr('required', false);
        if ($('#edit_flexible_time_notice').length === 0) {
            $('#edit_graphic_task_type_section').after('<div id="edit_flexible_time_notice" class="alert alert-info mt-2"><i class="fas fa-info-circle"></i> الوقت المقدر سيتم تحديده تلقائياً من نوع المهمة الجرافيكية المحدد</div>');
        }
    } else {
        $('#edit_estimated_hours, #edit_estimated_minutes').closest('.col-md-3').show();
        $('#edit_estimated_hours, #edit_estimated_minutes').attr('required', true);
        $('#edit_flexible_time_notice').remove();
    }
}

function addUserRowToEdit(user) {

    const userRow = $('<div class="col-12 mb-2 user-assignment-row"></div>');
    const userSelect = $('<select class="form-control user-select" name="users[]"></select>');
    userSelect.append('<option value="">اختر موظف</option>');

    let userOptions = window.allUsers || [];

    if (!userOptions.length) {
        if (window.tasksData && window.tasksData.users) {
            userOptions = window.tasksData.users;
            window.allUsers = userOptions;
        }
        else if (typeof tasksData !== 'undefined' && tasksData.users) {
            userOptions = tasksData.users;
            window.allUsers = userOptions;
        }
    }

    if (!userOptions.length && user) {
        userOptions = [user];
        loadAllUsersAsync(userSelect, user.id);
    }

    let userFound = false;
    userOptions.forEach(function(dbUser) {
        const option = $('<option></option>')
            .val(dbUser.id)
            .text(dbUser.name);

        if (user && user.id == dbUser.id) {
            option.prop('selected', true);
            userFound = true;
        }

        userSelect.append(option);
    });

    if (user && !userFound) {
        const missingUserOption = $('<option></option>')
            .val(user.id)
            .text(user.name)
            .prop('selected', true);
        userSelect.append(missingUserOption);
    }

    userRow.append(userSelect);
    $('#edit_assignUsersContainer').append(userRow);
}

function resetCreateTaskModal() {
    $('#createTaskModal form')[0].reset();
    $('#time_estimation_section').show();
    $('#flexible_time_notice').remove();
    $('#estimated_hours, #estimated_minutes').attr('required', true);
    $('#role_filter').val('');
    if (typeof resetUserSelects === 'function') {
        resetUserSelects();
    }
    $('#graphic_task_type_section').hide();
    $('#graphic_task_type_id').attr('required', false).val('');
    $('#graphic_task_info').hide();
}

function loadAllUsersAsync(selectElement, selectedUserId) {
    $.ajax({
        url: '/tasks/users',
        method: 'GET',
        dataType: 'json',
        success: function(users) {
            // التأكد من وجود المستخدم المطلوب في النتيجة
            let selectedUser = users.find(u => u.id == selectedUserId);

            // إذا لم يكن موجود، أضفه للقائمة
            if (!selectedUser) {
                // البحث عن المستخدم في القائمة الحالية
                const currentOptions = selectElement.find('option');
                let existingUser = null;
                currentOptions.each(function() {
                    if ($(this).val() == selectedUserId) {
                        existingUser = {
                            id: $(this).val(),
                            name: $(this).text()
                        };
                        return false;
                    }
                });

                if (existingUser) {
                    users.push(existingUser);
                    selectedUser = existingUser;
                }
            }

            // تحديث window.allUsers وإعادة تعبئة القائمة
            window.allUsers = users;

            selectElement.empty();
            selectElement.append('<option value="">اختر موظف</option>');

            users.forEach(function(user) {
                const option = $('<option></option>')
                    .val(user.id)
                    .text(user.name);

                if (user.id == selectedUserId) {
                    option.prop('selected', true);
                }

                selectElement.append(option);
            });
        },
        error: function(xhr, status, error) {
            // في حالة الخطأ، لا تغير شيء في القائمة
        }
    });
}

function resetEditTaskModal() {
    $('#editTaskForm')[0].reset();
    $('#edit_role_filter').val('');

    $('#edit_is_flexible_time').prop('checked', false);
    $('#edit_is_flexible_time_hidden').val('0');

    $('#edit_assignUsersContainer').empty();

    if (typeof resetUserSelects === 'function') {
        resetUserSelects();
    }

    $('#edit_time_estimation_section').show();
    $('#edit_estimated_hours, #edit_estimated_minutes').attr('required', true);

    // إزالة جميع الـ locks والـ notices
    $('#edit_estimated_hours, #edit_estimated_minutes').attr('readonly', false);
    $('#edit_estimated_hours, #edit_estimated_minutes').removeClass('bg-light text-muted');
    $('#edit_flexible_time_notice').remove();
    $('#edit_graphic_time_lock_notice').remove();
    $('#edit_graphic_task_info').hide();
    $('#edit_graphic_task_type_section').hide();
}
