function formatTime(seconds) {
    const h = String(Math.floor(seconds / 3600)).padStart(2, '0');
    const m = String(Math.floor((seconds % 3600) / 60)).padStart(2, '0');
    const s = String(seconds % 60).padStart(2, '0');
    return `${h}:${m}:${s}`;
}

function calculateElapsedSecondsForTask(startedAtTimestamp) {
    if (!startedAtTimestamp || startedAtTimestamp === 'null') return 0;
    const startedAt = parseInt(startedAtTimestamp);
    if (isNaN(startedAt)) return 0;
    const now = new Date().getTime();
    const elapsedMilliseconds = now - startedAt;
    return Math.floor(elapsedMilliseconds / 1000);
}

function calculateElapsedSeconds(startedAtTimestamp) {
    return calculateElapsedSecondsForTask(startedAtTimestamp);
}

function getMyTaskIdentifier(taskElement) {
    const taskUserId = taskElement.dataset.taskUserId;
    if (taskUserId) {
        return taskUserId;
    }
    return null;
}

function getMyTaskStatusText(status) {
    const statusMap = {
        'new': 'جديدة',
        'in_progress': 'قيد التنفيذ',
        'paused': 'متوقفة',
        'completed': 'مكتملة',
        'cancelled': 'ملغاة'
    };
    return statusMap[status] || status;
}

function getMyTaskDueDateClass(dueDate) {
    if (!dueDate || dueDate === 'غير محدد') return '';

    const today = new Date();
    const due = new Date(dueDate);
    const diffTime = due - today;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays < 0) return 'text-danger';
    if (diffDays <= 2) return 'text-warning';
    return 'text-muted';
}

function updateMyTasksCounters() {
    const statuses = ['new', 'in_progress', 'paused', 'completed', 'cancelled'];

    statuses.forEach(status => {
        const count = $(`#my-cards-${status} .my-kanban-card`).length;
        $(`#my-count-${status}`).text(count);
    });
}

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

function getTableTimerData() {
    const tableRows = document.querySelectorAll('#myTasksTable tbody tr[data-task-id]');
    const data = [];

    tableRows.forEach(row => {
        const taskData = {
            taskId: row.dataset.taskId,
            taskUserId: row.dataset.taskUserId,
            status: row.dataset.status,
            initialMinutes: parseInt(row.dataset.initialMinutes || '0'),
            startedAt: row.dataset.startedAt,
            userId: window.currentUserId || 'current_user'
        };
        data.push(taskData);
    });

    return data;
}

window.MyTasksUtils = {
    formatTime,
    calculateElapsedSecondsForTask,
    calculateElapsedSeconds,
    getMyTaskIdentifier,
    getMyTaskStatusText,
    getMyTaskDueDateClass,
    updateMyTasksCounters,
    dispatchTimerEvent,
    getTableTimerData
};
