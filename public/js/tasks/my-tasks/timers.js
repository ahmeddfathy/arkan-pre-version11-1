function startTimer(taskId) {
    const taskElement = document.querySelector(`.my-kanban-card[data-task-user-id="${taskId}"]`);
    if (!taskElement) {
        const timers = window.MyTasksCore.getTimers();
        timers[taskId] = 0;
        return;
    }
    const userId = taskElement.getAttribute('data-user-id') || window.currentUserId || 'current_user';
    const userActiveTasks = window.MyTasksCore.getUserActiveTasks();
    const timers = window.MyTasksCore.getTimers();
    if (!userActiveTasks.has(userId)) {
        userActiveTasks.set(userId, new Set());
    }
    userActiveTasks.get(userId).add(taskId);
    const initialMinutes = parseInt(taskElement.getAttribute('data-initial-minutes') || '0');
    let totalSeconds = initialMinutes * 60;
    let startedAtTimestamp = taskElement.getAttribute('data-started-at');
    const taskStatus = taskElement.getAttribute('data-status');

    if (taskStatus === 'in_progress') {
        if (!startedAtTimestamp || startedAtTimestamp === 'null' || startedAtTimestamp === '') {
            startedAtTimestamp = new Date().getTime().toString();
            taskElement.setAttribute('data-started-at', startedAtTimestamp);
        }
        const elapsedSeconds = window.MyTasksUtils.calculateElapsedSecondsForTask(startedAtTimestamp);
        totalSeconds += elapsedSeconds;
    }
    timers[taskId] = totalSeconds;
    updateTaskTimerDisplay(taskId, timers[taskId]);
    taskElement.classList.remove('task-new', 'task-paused', 'task-completed');
    taskElement.classList.add('task-in-progress');
    const userTimers = window.MyTasksCore.getUserTimers();
    if (!userTimers.has(userId)) {
        const userTimer = {
            seconds: 0,
            interval: setInterval(() => {
                const userTimerData = userTimers.get(userId);
                if (userTimerData) {
                    userTimerData.seconds++;
                    const activeTasks = userActiveTasks.get(userId);
                    if (activeTasks) {
                        activeTasks.forEach(activeTaskId => {
                            if (timers[activeTaskId] !== undefined) {
                                timers[activeTaskId]++;
                                updateTaskTimerDisplay(activeTaskId, timers[activeTaskId]);
                            }
                        });
                    }
                }
            }, 1000)
        };
        userTimers.set(userId, userTimer);
    }
}

function pauseTimer(taskId) {
    const taskElement = document.querySelector(`.my-kanban-card[data-task-user-id="${taskId}"]`);
    if (!taskElement) return;
    const userId = taskElement.getAttribute('data-user-id') || window.currentUserId || 'current_user';
    const userActiveTasks = window.MyTasksCore.getUserActiveTasks();
    const userTimers = window.MyTasksCore.getUserTimers();
    if (userActiveTasks.has(userId)) {
        userActiveTasks.get(userId).delete(taskId);
        if (userActiveTasks.get(userId).size === 0) {
            const userTimer = userTimers.get(userId);
            if (userTimer && userTimer.interval) {
                clearInterval(userTimer.interval);
                userTimers.delete(userId);
            }
            userActiveTasks.delete(userId);
        }
    }
}

function finishTimer(taskId) {
    pauseTimer(taskId);
    const timers = window.MyTasksCore.getTimers();
    timers[taskId] = 0;
    updateTaskTimerDisplay(taskId, 0);
}

function updateTaskTimerDisplay(taskId, seconds) {
    let kanbanTimerElement = document.getElementById('my-kanban-timer-' + taskId);
    if (!kanbanTimerElement) {
        kanbanTimerElement = document.getElementById('my-timer-' + taskId);
    }
    if (!kanbanTimerElement) {
        kanbanTimerElement = document.querySelector(`.my-kanban-card[data-task-user-id="${taskId}"] .my-kanban-card-timer`);
    }
    if (kanbanTimerElement) {
        kanbanTimerElement.textContent = window.MyTasksUtils.formatTime(seconds);
    }
}

function initializeMyTasksTimers() {
    setTimeout(() => {
        const allInProgressTasks = document.querySelectorAll('.my-kanban-card[data-status="in_progress"]');
        allInProgressTasks.forEach(task => {
            const taskUserId = task.getAttribute('data-task-user-id');
            const startedAt = task.getAttribute('data-started-at');
            if (taskUserId) {
                if (startedAt && startedAt !== '' && startedAt !== 'null') {
                    const startedAtTimestamp = parseInt(startedAt);
                    if (!isNaN(startedAtTimestamp)) {
                        task.setAttribute('data-started-at', startedAtTimestamp);
                    }
                }
                startTimer(taskUserId);
            }
        });
    }, 1000);
}

function initMyTasksTotalTimer() {
    calculateInitialTotalTime();
    document.addEventListener('task-timer-start', function(e) {
        const taskId = e.detail.taskId;
        addActiveTask(taskId);
    });
    document.addEventListener('task-timer-pause', function(e) {
        const taskId = e.detail.taskId;
        removeActiveTask(taskId);
    });
    document.addEventListener('task-timer-complete', function(e) {
        const taskId = e.detail.taskId;
        removeActiveTask(taskId);
    });
    updateTotalTimerDisplay();
}

function calculateInitialTotalTime() {
    const tableRows = document.querySelectorAll('#myTasksTable tbody tr[data-task-id]');
    let totalSeconds = 0;
    const totalTimerState = window.MyTasksCore.getTotalTimerState();
    const userTasksMap = new Map();
    tableRows.forEach(row => {
        const minutes = parseInt(row.dataset.initialMinutes || '0');
        const status = row.dataset.status;
        const taskUserId = row.dataset.taskUserId;
        const startedAtTimestamp = row.dataset.startedAt;
        const userId = window.currentUserId || 'current_user';
        if (status === 'in_progress') {
            const taskId = window.MyTasksUtils.getMyTaskIdentifier(row);
            if (taskId && userId) {
                totalTimerState.activeTasks.add(taskId);
                if (!totalTimerState.userActiveTasks.has(userId)) {
                    totalTimerState.userActiveTasks.set(userId, new Set());
                }
                totalTimerState.userActiveTasks.get(userId).add(taskId);
                totalTimerState.activeUsers.add(userId);
                if (!userTasksMap.has(userId)) {
                    userTasksMap.set(userId, {
                        tasks: new Set(),
                        earliestStart: null,
                        totalInitialMinutes: 0
                    });
                }
                const userData = userTasksMap.get(userId);
                userData.tasks.add(taskId);
                userData.totalInitialMinutes += minutes;
                if (startedAtTimestamp && startedAtTimestamp !== 'null' && startedAtTimestamp !== '') {
                    const startTimestamp = parseInt(startedAtTimestamp);
                    if (!isNaN(startTimestamp)) {
                        if (!userData.earliestStart || startTimestamp < userData.earliestStart) {
                            userData.earliestStart = startTimestamp;
                        }
                    }
                }
            }
        } else {
            const taskSeconds = minutes * 60;
            totalSeconds += taskSeconds;
        }
    });
    userTasksMap.forEach((userData, userId) => {
        let userSeconds = userData.totalInitialMinutes * 60;
        if (userData.earliestStart) {
            const elapsedSeconds = window.MyTasksUtils.calculateElapsedSecondsForTask(userData.earliestStart);
            userSeconds += elapsedSeconds;
        }
        totalSeconds += userSeconds;
    });
    totalTimerState.totalSeconds = totalSeconds;
    totalTimerState.initialTime = totalTimerState.totalSeconds;
    totalTimerState.activeTasksCount = totalTimerState.activeUsers.size;
    if (totalTimerState.activeTasksCount > 0 && !totalTimerState.running) {
        startTotalTimer();
    }
}

function addActiveTask(taskId) {
    const totalTimerState = window.MyTasksCore.getTotalTimerState();
    if (!totalTimerState.activeTasks.has(taskId)) {
        totalTimerState.activeTasks.add(taskId);
        const taskElement = document.querySelector(`.my-kanban-card[data-task-user-id="${taskId}"]`);
        if (taskElement) {
            const userId = taskElement.getAttribute('data-user-id') || window.currentUserId || 'current_user';
            if (userId) {
                if (!totalTimerState.userActiveTasks.has(userId)) {
                    totalTimerState.userActiveTasks.set(userId, new Set());
                }
                totalTimerState.userActiveTasks.get(userId).add(taskId);
                totalTimerState.activeUsers.add(userId);
                totalTimerState.activeTasksCount = totalTimerState.activeUsers.size;
            }
        }
        if (!totalTimerState.running) {
            startTotalTimer();
        }
    }
}

function removeActiveTask(taskId) {
    const totalTimerState = window.MyTasksCore.getTotalTimerState();
    if (totalTimerState.activeTasks.has(taskId)) {
        totalTimerState.activeTasks.delete(taskId);
        const taskElement = document.querySelector(`.my-kanban-card[data-task-user-id="${taskId}"]`);
        if (taskElement) {
            const userId = taskElement.getAttribute('data-user-id') || window.currentUserId || 'current_user';
            if (userId) {
                if (totalTimerState.userActiveTasks.has(userId)) {
                    totalTimerState.userActiveTasks.get(userId).delete(taskId);
                    if (totalTimerState.userActiveTasks.get(userId).size === 0) {
                        totalTimerState.userActiveTasks.delete(userId);
                        totalTimerState.activeUsers.delete(userId);
                    }
                }
                totalTimerState.activeTasksCount = totalTimerState.activeUsers.size;
            }
        }
        if (totalTimerState.activeTasksCount === 0 && totalTimerState.running) {
            pauseTotalTimer();
        }
    }
}

function startTotalTimer() {
    const totalTimerState = window.MyTasksCore.getTotalTimerState();
    if (!totalTimerState.running) {
        totalTimerState.running = true;
        totalTimerState.timerInterval = setInterval(function() {
            const activeUsersCount = totalTimerState.activeUsers.size;
            totalTimerState.totalSeconds += activeUsersCount;
            updateTotalTimerDisplay();
        }, 1000);
    }
}

function pauseTotalTimer() {
    const totalTimerState = window.MyTasksCore.getTotalTimerState();
    if (totalTimerState.running) {
        totalTimerState.running = false;
        clearInterval(totalTimerState.timerInterval);
    }
}

function updateTotalTimerDisplay() {
    const totalTimerElement = document.getElementById('myTasksTotalTimer');
    const totalTimerState = window.MyTasksCore.getTotalTimerState();
    if (totalTimerElement) {
        const hours = Math.floor(totalTimerState.totalSeconds / 3600);
        const minutes = Math.floor((totalTimerState.totalSeconds % 3600) / 60);
        const seconds = totalTimerState.totalSeconds % 60;
        const timeDisplay = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        const activeTasksCount = totalTimerState.activeTasks.size;
        const tasksDisplay = activeTasksCount > 0 ? ` (${activeTasksCount} مهمة)` : '';
        totalTimerElement.textContent = timeDisplay + tasksDisplay;
    }
}

function syncTimerWithTasks() {
    const inProgressTasks = document.querySelectorAll('.my-kanban-card[data-status="in_progress"]');
    const totalTimerState = window.MyTasksCore.getTotalTimerState();
    totalTimerState.activeTasks.clear();
    totalTimerState.userActiveTasks.clear();
    totalTimerState.activeUsers.clear();
    inProgressTasks.forEach(task => {
        const taskId = window.MyTasksUtils.getMyTaskIdentifier(task);
        const userId = task.getAttribute('data-user-id') || window.currentUserId || 'current_user';
        if (taskId && userId) {
            totalTimerState.activeTasks.add(taskId);
            if (!totalTimerState.userActiveTasks.has(userId)) {
                totalTimerState.userActiveTasks.set(userId, new Set());
            }
            totalTimerState.userActiveTasks.get(userId).add(taskId);
            totalTimerState.activeUsers.add(userId);
        }
    });
    totalTimerState.activeTasksCount = totalTimerState.activeUsers.size;
    if (totalTimerState.activeTasksCount > 0 && !totalTimerState.running) {
        startTotalTimer();
    } else if (totalTimerState.activeTasksCount === 0 && totalTimerState.running) {
        pauseTotalTimer();
    }
}

function startMyTasksTotalTimer() {
    startTotalTimer();
}

function stopMyTasksTotalTimer() {
    pauseTotalTimer();
}

function updateMyTasksTotalTimerDisplay() {
    updateTotalTimerDisplay();
}

function initializePageVisibilityHandler() {
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            syncAllTimersWithRealTime();
        }
    });

    setInterval(function() {
        if (!document.hidden) {
            syncAllTimersWithRealTime();
        }
    }, 10000);

    document.addEventListener('click', function() {
        if (!document.hidden) {
            setTimeout(() => {
                syncAllTimersWithRealTime();
            }, 100);
        }
    });
}

function syncAllTimersWithRealTime() {
    const timers = window.MyTasksCore.getTimers();

    const inProgressKanbanTasks = document.querySelectorAll('.kanban-card[data-status="in_progress"]');
    inProgressKanbanTasks.forEach(taskElement => {
        updateSingleTaskTimer(taskElement, timers);
    });

    const inProgressTableRows = document.querySelectorAll('#myTasksTable tbody tr[data-status="in_progress"]');
    inProgressTableRows.forEach(rowElement => {
        updateSingleTaskTimer(rowElement, timers);
    });

    if (window.MyTasksTimers && window.MyTasksTimers.calculateInitialTotalTime) {
        window.MyTasksTimers.calculateInitialTotalTime();
    }
}

function updateSingleTaskTimer(taskElement, timers) {
    const taskId = taskElement.getAttribute('data-task-id');
    const startedAtTimestamp = taskElement.getAttribute('data-started-at');
    const initialMinutes = parseInt(taskElement.getAttribute('data-initial-minutes') || '0');

    if (taskId && startedAtTimestamp && startedAtTimestamp !== 'null' && startedAtTimestamp !== '') {
        const elapsedSeconds = window.MyTasksUtils.calculateElapsedSecondsForTask(startedAtTimestamp);
        const totalSeconds = (initialMinutes * 60) + elapsedSeconds;

        timers[taskId] = totalSeconds;
        updateTaskTimerDisplay(taskId, totalSeconds);

        const tableTimerElement = document.querySelector(`.task-timer[data-task-id="${taskId}"]`);
        if (tableTimerElement) {
            tableTimerElement.textContent = window.MyTasksUtils.formatTime(totalSeconds);
        }
    }
}

window.MyTasksTimers = {
    startTimer,
    pauseTimer,
    finishTimer,
    updateTaskTimerDisplay,
    initializeMyTasksTimers,
    initMyTasksTotalTimer,
    calculateInitialTotalTime,
    addActiveTask,
    removeActiveTask,
    startTotalTimer,
    pauseTotalTimer,
    updateTotalTimerDisplay,
    syncTimerWithTasks,
    startMyTasksTotalTimer,
    stopMyTasksTotalTimer,
    updateMyTasksTotalTimerDisplay,
    initializePageVisibilityHandler,
    syncAllTimersWithRealTime,
    updateSingleTaskTimer
};
