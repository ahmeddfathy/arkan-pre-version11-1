
let totalTimerState = {
    activeTasksCount: 0,
    totalSeconds: 0,
    timerInterval: null,
    running: false,
    activeTasks: new Set(),
    initialTime: 0,
    userActiveTasks: new Map(),
    activeUsers: new Set()
};

document.addEventListener('DOMContentLoaded', function() {
    initTotalTimer();
    syncTimerWithTasks();

    // ✅ تهيئة Page Visibility Handler لحل مشكلة توقف التايمر الكبير
    initializeProjectTotalTimerPageVisibilityHandler();
});

window.addEventListener('beforeunload', function() {
    if (totalTimerState.timerInterval) {
        clearInterval(totalTimerState.timerInterval);
    }
    totalTimerState.activeTasks.clear();
    totalTimerState.userActiveTasks.clear();
    totalTimerState.activeUsers.clear();
});

function initTotalTimer() {
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

// ✅ إضافة Page Visibility API لحل مشكلة توقف التايمر الكبير في صفحة المشاريع
function initializeProjectTotalTimerPageVisibilityHandler() {
    // الكشف عن تغيير حالة الصفحة (نشطة/غير نشطة)
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            // المستخدم عاد للتاب - نحديث التايمر الكبير
            calculateInitialTotalTime();
        }
    });

    // تحديث التايمر الكبير كل 10 ثوان كـ backup عندما التاب نشط
    setInterval(function() {
        if (!document.hidden) {
            calculateInitialTotalTime();
        }
    }, 10000);

    // تحديث التايمر عند النقر على أي مكان في الصفحة
    document.addEventListener('click', function() {
        if (!document.hidden) {
            setTimeout(() => {
                calculateInitialTotalTime();
            }, 100);
        }
    });
}




function calculateElapsedSecondsForTask(startedAtTimestamp) {
    if (!startedAtTimestamp || startedAtTimestamp === 'null') return 0;
    const startedAt = parseInt(startedAtTimestamp);
    if (isNaN(startedAt)) return 0;
    const now = new Date().getTime();
    const elapsedMilliseconds = now - startedAt;
    return Math.floor(elapsedMilliseconds / 1000);
}

function calculateInitialTotalTime() {
    const tasks = document.querySelectorAll('.kanban-task');
    let totalSeconds = 0;

    // 🔥 تجميع المهام حسب المستخدم لتجنب العد المضاعف
    const userTasksMap = new Map(); // Map<userId, {tasks: Set, earliestStart: timestamp}>

    tasks.forEach(task => {
        const minutes = parseInt(task.dataset.initialMinutes || 0);
        let taskSeconds = minutes * 60;

        if (task.dataset.status === 'in_progress') {
            const taskId = getTaskIdentifier(task);
            const userId = task.getAttribute('data-user-id');
            const startedAtTimestamp = task.getAttribute('data-started-at');

            if (taskId && userId) {
                totalTimerState.activeTasks.add(taskId);

                if (!totalTimerState.userActiveTasks.has(userId)) {
                    totalTimerState.userActiveTasks.set(userId, new Set());
                }
                totalTimerState.userActiveTasks.get(userId).add(taskId);
                totalTimerState.activeUsers.add(userId);

                // 🔥 تجميع المهام النشطة لكل مستخدم
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

                // احتساب أقدم وقت بداية للمستخدم
                if (startedAtTimestamp && startedAtTimestamp !== 'null') {
                    const startTimestamp = parseInt(startedAtTimestamp);
                    if (!isNaN(startTimestamp)) {
                        if (!userData.earliestStart || startTimestamp < userData.earliestStart) {
                            userData.earliestStart = startTimestamp;
                        }
                    }
                }
            }
        } else {
            // المهام غير النشطة تضاف كما هي
            totalSeconds += taskSeconds;
        }
    });

    // 🔥 حساب الوقت لكل مستخدم مرة واحدة فقط
    userTasksMap.forEach((userData, userId) => {
        let userSeconds = userData.totalInitialMinutes * 60;

        // إضافة الوقت المنقضي للمستخدم (مرة واحدة فقط)
        if (userData.earliestStart) {
            const elapsedSeconds = calculateElapsedSecondsForTask(userData.earliestStart);
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
    if (!totalTimerState.activeTasks.has(taskId)) {
        totalTimerState.activeTasks.add(taskId);

        const taskElement = document.querySelector(`.kanban-task[data-task-user-id="${taskId}"]`);
        if (taskElement) {
            const userId = taskElement.getAttribute('data-user-id');
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
    if (totalTimerState.activeTasks.has(taskId)) {
        totalTimerState.activeTasks.delete(taskId);

        const taskElement = document.querySelector(`.kanban-task[data-task-user-id="${taskId}"]`);
        if (taskElement) {
            const userId = taskElement.getAttribute('data-user-id');
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


function getTaskIdentifier(taskElement) {
    const taskType = taskElement.dataset.taskType;
    const taskUserId = taskElement.dataset.taskUserId;

    if (taskType && taskUserId) {
        return taskUserId;
    }

    return null;
}


function startTotalTimer() {
    if (!totalTimerState.running) {
        totalTimerState.running = true;

        totalTimerState.timerInterval = setInterval(function() {
            // 🔥 زيادة الوقت حسب عدد المستخدمين النشطين (مش عدد المهام!)
            const activeUsersCount = totalTimerState.activeUsers.size;
            totalTimerState.totalSeconds += activeUsersCount;
            updateTotalTimerDisplay();
        }, 1000);
    }
}


function pauseTotalTimer() {
    if (totalTimerState.running) {
        totalTimerState.running = false;
        clearInterval(totalTimerState.timerInterval);
    }
}

function updateTotalTimerDisplay() {
    const totalTimerElement = document.getElementById('kanban-total-timer');
    if (totalTimerElement) {
        const hours = Math.floor(totalTimerState.totalSeconds / 3600);
        const minutes = Math.floor((totalTimerState.totalSeconds % 3600) / 60);
        const seconds = totalTimerState.totalSeconds % 60;

        const timeDisplay = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

        // إضافة عدد المهام النشطة
        const activeTasksCount = totalTimerState.activeTasks.size;
        const tasksDisplay = activeTasksCount > 0 ? ` (${activeTasksCount} مهمة)` : '';

        totalTimerElement.textContent = timeDisplay + tasksDisplay;
    }
}


function syncTimerWithTasks() {
    const inProgressTasks = document.querySelectorAll('.kanban-task[data-status="in_progress"]');

    totalTimerState.activeTasks.clear();
    totalTimerState.userActiveTasks.clear();
    totalTimerState.activeUsers.clear();

    inProgressTasks.forEach(task => {
        const taskId = getTaskIdentifier(task);
        const userId = task.getAttribute('data-user-id');

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

// ✅ جعل الدوال متاحة للملفات الأخرى
window.ProjectTotalTimer = {
    calculateInitialTotalTime,
    updateTotalTimerDisplay,
    startTotalTimer,
    pauseTotalTimer,
    initializeProjectTotalTimerPageVisibilityHandler
};
