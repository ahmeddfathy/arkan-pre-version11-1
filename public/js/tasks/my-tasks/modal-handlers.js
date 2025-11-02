$(document).ready(function() {
    $(document).on('click', '.view-task', function() {
        const taskId = $(this).data('id');
        const taskUserId = $(this).data('task-user-id') || taskId;
        const isTemplate = $(this).closest('tr, .kanban-card').data('is-template');
        const taskType = (isTemplate === 'true' || isTemplate === true) ? 'template' : 'regular';
        openTaskSidebar(taskType, taskUserId);
    });

    $('#taskActionForm').submit(function(e) {
        e.preventDefault();

        const form = $(this);
        const url = form.attr('action');
        const notes = $('#notes').val();

        const submitBtn = form.find('button[type="submit"]');
        const originalBtnText = submitBtn.html();
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> جاري المعالجة...');
        submitBtn.prop('disabled', true);

        $.ajax({
            url: url,
            method: 'POST',
            data: {
                '_token': $('meta[name="csrf-token"]').attr('content'),
                'notes': notes
            },
            success: function(response) {
                $('#addNotesModal').modal('hide');
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            },
            error: function(xhr) {
                let errorMessage = 'حدث خطأ أثناء تنفيذ العملية';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                alert(errorMessage);

                submitBtn.html(originalBtnText);
                submitBtn.prop('disabled', false);
            }
        });
    });
});

function getStatusText(status) {
    switch(status) {
        case 'new': return 'جديدة';
        case 'in_progress': return 'قيد التنفيذ';
        case 'paused': return 'متوقفة';
        case 'completed': return 'مكتملة';
        case 'cancelled': return 'ملغاة';
        default: return 'غير محدد';
    }
}

function fillUsersTable(users) {
    let usersHtml = '';

    if (users && Array.isArray(users)) {
        users.forEach(function(user) {
            if (!user || !user.pivot) {
                return;
            }

            const userStatusText = getStatusText(user.pivot.status);
            const name = user.name || 'غير محدد';
            const role = user.pivot.role || 'غير محدد';
            const estimatedHours = user.pivot.estimated_hours || 0;
            const estimatedMinutes = user.pivot.estimated_minutes || 0;
            const actualHours = user.pivot.actual_hours || 0;
            const actualMinutes = user.pivot.actual_minutes || 0;

            usersHtml += '<tr>' +
                '<td>' + name + '</td>' +
                '<td>' + role + '</td>' +
                '<td>' + userStatusText + '</td>' +
                '<td>' + estimatedHours + ':' + String(estimatedMinutes).padStart(2, '0') + '</td>' +
                '<td>' + actualHours + ':' + String(actualMinutes).padStart(2, '0') + '</td>' +
                '</tr>';
        });
    }

    if (usersHtml === '') {
        usersHtml = '<tr><td colspan="5" class="text-center">لا يوجد موظفين معينين</td></tr>';
    }

    $('#view-users-table tbody').html(usersHtml);
}

function showNotesModal(taskId, action) {
    $('#taskActionForm').attr('action', `/tasks/${taskId}/${action}`);
    $('#addNotesModalLabel').text(getActionTitle(action));
    $('#notes').val('');
    $('#addNotesModal').modal('show');
}

function getActionTitle(action) {
    switch(action) {
        case 'start': return 'بدء المهمة';
        case 'pause': return 'إيقاف المهمة مؤقتاً';
        case 'resume': return 'استئناف المهمة';
        case 'complete': return 'إنهاء المهمة';
        default: return 'إضافة ملاحظات';
    }
}

window.MyTasksModals = {
    showNotesModal,
    getActionTitle,
    getStatusText,
    fillUsersTable
};
