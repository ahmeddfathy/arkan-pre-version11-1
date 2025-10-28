$(document).ready(function() {
    if (!window.NEW_MY_TASKS_SYSTEM) {
        return;
    }

    window.currentUserId = $('.card-body').data('current-user-id') || 0;

    const interferingFunctions = ['initializeTimers', 'loadTimeLogs', 'loadTaskTimeLogs'];
    interferingFunctions.forEach(funcName => {
        if (typeof window[funcName] !== 'undefined') {
            delete window[funcName];
        }
    });

    if (typeof initializeMyTasksKanban === 'function') {
        initializeMyTasksKanban();
    }
});
