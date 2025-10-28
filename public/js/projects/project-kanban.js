
document.addEventListener('DOMContentLoaded', function() {
    initKanbanDragDrop();
});

function initKanbanDragDrop() {
    const kanbanTasks = document.querySelectorAll('.kanban-task');
    const kanbanColumns = document.querySelectorAll('.kanban-tasks');

    // إضافة tooltip للمهام غير القابلة للسحب
    kanbanTasks.forEach(task => {
        if (task.getAttribute('draggable') === 'false') {
            task.setAttribute('title', 'هذه المهمة مخصصة لمستخدم آخر - لا يمكنك تحريكها');
        }
    });

    kanbanTasks.forEach(task => {
        task.addEventListener('dragstart', function(e) {
            // التحقق من إمكانية سحب المهمة
            if (task.getAttribute('draggable') !== 'true') {
                e.preventDefault();
                toastr.warning('لا يمكنك تحريك هذه المهمة - ليست مخصصة لك', 'تحذير');
                return false;
            }

            // نخزن معرف المهمة ونوعها ومعرف علاقة المستخدم
            const data = {
                taskId: task.getAttribute('data-task-id'),
                taskType: task.getAttribute('data-task-type'),
                taskUserId: task.getAttribute('data-task-user-id')
            };
            e.dataTransfer.setData('text/plain', JSON.stringify(data));
            task.classList.add('dragging');
        });

        task.addEventListener('dragend', function() {
            task.classList.remove('dragging');
        });
    });

    kanbanColumns.forEach(column => {
        column.addEventListener('dragover', function(e) {
            e.preventDefault();
            column.classList.add('drag-over');
        });

        column.addEventListener('dragleave', function() {
            column.classList.remove('drag-over');
        });

        column.addEventListener('drop', function(e) {
            e.preventDefault();
            column.classList.remove('drag-over');

            try {
                // استخراج بيانات المهمة
                const data = JSON.parse(e.dataTransfer.getData('text/plain'));
                const taskId = data.taskId;
                const taskType = data.taskType;
                const taskUserId = data.taskUserId;

                const task = document.querySelector(`.kanban-task[data-task-id="${taskId}"][data-task-type="${taskType}"][data-task-user-id="${taskUserId}"]`);
                const newStatus = column.getAttribute('data-status');

                if (task) {
                    const currentStatus = task.getAttribute('data-status');
                    if (currentStatus === newStatus) {
                        toastr.info('المهمة بالفعل في هذه الحالة', 'تنبيه');
                        return;
                    }

                    // منع أي تحديث حالة من السجل الأصلي الذي تم نقل المهمة منه
                    if (task.classList.contains('transferred-from')) {
                        toastr.warning('تم نقل هذه المهمة من حسابك - لا يمكنك تغيير حالتها', 'تحذير');
                        return;
                    }

                    updateTaskStatus(taskId, taskUserId, taskType, newStatus).then((result) => {
                        if (result.no_change) {
                            return;
                        }

                        column.appendChild(task);

                        if (result.minutesSpent !== undefined) {
                            task.setAttribute('data-initial-minutes', result.minutesSpent);
                        }

                        task.setAttribute('data-status', newStatus);

                        if (newStatus === 'in_progress' && result.task) {
                            // للتاسكات العادية نستخدم start_date، للتمبليت نستخدم started_at
                            const startTimeField = taskType === 'regular_task' ? result.task.start_date : result.task.started_at;

                            if (startTimeField) {
                                const startedAtDate = new Date(startTimeField);
                                const startedAtTimestamp = startedAtDate.getTime();
                                task.setAttribute('data-started-at', startedAtTimestamp);
                            } else {
                                task.setAttribute('data-started-at', '');
                            }
                        } else {
                            task.setAttribute('data-started-at', '');
                        }

                        handleTimerStatusChange(taskType, taskUserId, newStatus);

                        toastr.success('تم تحديث حالة المهمة بنجاح', 'نجاح');
                    }).catch(error => {
                        toastr.error('حدث خطأ أثناء تحديث حالة المهمة', 'خطأ');
                        console.error('Error updating task status:', error);
                    });
                }
            } catch (error) {
                console.error('Error parsing drag data:', error);
                toastr.error('حدث خطأ أثناء معالجة البيانات', 'خطأ');
            }
        });
    });
}


async function updateTaskStatus(taskId, taskUserId, taskType, newStatus) {
    try {
        let url, requestData;

        // تحديد عنوان URL ومعلومات الطلب بناءً على نوع المهمة
        if (taskType === 'template_task') {
            url = `/template-tasks/${taskUserId}/update-status`;
            requestData = {
                status: newStatus
            };
        } else if (taskType === 'regular_task') {
            url = `/task-users/${taskUserId}/update-status`;
            requestData = {
                status: newStatus
            };
        } else {
            throw new Error('نوع المهمة غير معروف');
        }

        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify(requestData)
        });

        if (!response.ok) {
            throw new Error(`HTTP error: ${response.status}`);
        }

        return await response.json();
    } catch (error) {
        console.error('Error updating task status:', error);
        throw error;
    }
}

function handleTimerStatusChange(taskType, taskUserId, newStatus) {
    // استخدام taskUserId مباشرة بدلاً من دمجه مع taskType
    const timerId = taskUserId;
    const task = document.querySelector(`.kanban-task[data-task-type="${taskType}"][data-task-user-id="${taskUserId}"]`);
    if (!task) return;

    task.classList.remove('task-in-progress', 'task-paused', 'task-completed', 'task-new');
    task.classList.add(`task-${newStatus}`);

    // إطلاق حدث تغيير حالة المهمة للتايمر الإجمالي
    dispatchTimerEvent(newStatus, timerId);

    // إذا كانت وظائف تايمر المهمة متاحة، استخدمها
    if (typeof startTimer === 'function' && typeof pauseTimer === 'function' && typeof finishTimer === 'function') {
        switch (newStatus) {
            case 'in_progress':
                startTimer(timerId);
                break;
            case 'paused':
                pauseTimer(timerId);
                break;
            case 'completed':
                finishTimer(timerId);
                break;
            default:
                pauseTimer(timerId);
                break;
        }
    }
}

/**
 * إرسال حدث تغيير حالة المهمة للتايمر الإجمالي
 */
function dispatchTimerEvent(status, taskId) {
    let eventName;

    switch (status) {
        case 'in_progress':
            eventName = 'task-timer-start';
            break;
        case 'paused':
            eventName = 'task-timer-pause';
            break;
        case 'completed':
            eventName = 'task-timer-complete';
            break;
        default:
            eventName = 'task-timer-pause';
    }

    document.dispatchEvent(new CustomEvent(eventName, {
        detail: {
            taskId: taskId
        }
    }));
}
