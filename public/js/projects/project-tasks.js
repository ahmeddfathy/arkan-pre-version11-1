
let timers = {};
// ✅ إضافة متغيرات لتتبع المهام النشطة لكل مستخدم
let userActiveTasks = new Map(); // Map<userId, Set<taskId>>
let userTimers = new Map(); // Map<userId, {seconds: number, interval: number}>

document.addEventListener('DOMContentLoaded', function() {
    initTaskButtons();
    setTimeout(initInProgressTasks, 500);

    // ✅ تهيئة Page Visibility Handler لحل مشكلة توقف التايمر
    initializeProjectTasksPageVisibilityHandler();

    // التأكد من تشغيل التايمرز للمهام قيد التنفيذ
    setTimeout(function() {
        const allInProgressTasks = document.querySelectorAll('.kanban-task[data-status="in_progress"]');

        allInProgressTasks.forEach(task => {
            const taskUserId = task.getAttribute('data-task-user-id');
            if (taskUserId) {
                startTimer(taskUserId);
            }
        });
    }, 1000);

    // ✅ مراقب إضافي للتأكد من عمل التايمرات كل 30 ثانية
    setInterval(function() {
        const inProgressTasks = document.querySelectorAll('.kanban-task[data-status="in_progress"]');
        inProgressTasks.forEach(task => {
            const taskUserId = task.getAttribute('data-task-user-id');
            const userId = task.getAttribute('data-user-id');

            // إذا كانت المهمة قيد التنفيذ ولكن التايمر متوقف، أعد تشغيله
            if (taskUserId && userId && !userTimers.has(userId)) {
                startTimer(taskUserId);
            }
        });
    }, 30000);
});

// ✅ تنظيف التايمرز عند إغلاق الصفحة
window.addEventListener('beforeunload', function() {
    userTimers.forEach((userTimer, userId) => {
        if (userTimer && userTimer.interval) {
            clearInterval(userTimer.interval);
        }
    });
    userTimers.clear();
    userActiveTasks.clear();
});

// ✅ إضافة Page Visibility API لحل مشكلة توقف التايمر في صفحة المشاريع
function initializeProjectTasksPageVisibilityHandler() {
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            syncAllProjectTaskTimersWithRealTime();
        }
    });

    // تحديث التايمرات كل 10 ثوان كـ backup عندما التاب نشط
    setInterval(function() {
        if (!document.hidden) {
            syncAllProjectTaskTimersWithRealTime();
        }
    }, 10000);

    // تحديث التايمرات عند النقر على أي مكان في الصفحة
    document.addEventListener('click', function() {
        if (!document.hidden) {
            setTimeout(() => {
                syncAllProjectTaskTimersWithRealTime();
            }, 100);
        }
    });
}

function syncAllProjectTaskTimersWithRealTime() {
    // ✅ تحديث جميع التايمرات النشطة بالوقت الفعلي
    const inProgressTasks = document.querySelectorAll('.kanban-task[data-status="in_progress"]');

    inProgressTasks.forEach(taskElement => {
        const taskUserId = taskElement.getAttribute('data-task-user-id');
        const startedAtTimestamp = taskElement.getAttribute('data-started-at');
        const initialMinutes = parseInt(taskElement.getAttribute('data-initial-minutes') || '0');

        if (taskUserId && startedAtTimestamp && startedAtTimestamp !== 'null' && startedAtTimestamp !== '') {
            // ✅ حساب الوقت الفعلي من البداية
            const elapsedSeconds = calculateElapsedSeconds(startedAtTimestamp);
            const totalSeconds = (initialMinutes * 60) + elapsedSeconds;

            // تحديث التايمر المحلي
            timers[taskUserId] = totalSeconds;
            updateTaskTimerDisplay(taskUserId, totalSeconds);
        }
    });

    // تحديث Total Timer أيضاً إذا كان موجود
    if (typeof window.ProjectTotalTimer !== 'undefined' && window.ProjectTotalTimer.calculateInitialTotalTime) {
        window.ProjectTotalTimer.calculateInitialTotalTime();
    }
}

function formatTime(seconds) {
    const h = String(Math.floor(seconds / 3600)).padStart(2, '0');
    const m = String(Math.floor((seconds % 3600) / 60)).padStart(2, '0');
    const s = String(seconds % 60).padStart(2, '0');
    return `${h}:${m}:${s}`;
}

function calculateElapsedSeconds(startedAtTimestamp) {
    if (!startedAtTimestamp) return 0;
    const startedAt = parseInt(startedAtTimestamp);
    if (isNaN(startedAt)) return 0;
    const now = new Date().getTime();
    const elapsedMilliseconds = now - startedAt;
    return Math.floor(elapsedMilliseconds / 1000);
}

function startTimer(taskId) {
    const taskElement = document.querySelector(`.kanban-task[data-task-user-id="${taskId}"]`);
    if (!taskElement) {
        timers[taskId] = 0;
        return;
    }

    // ✅ الحصول على معرف المستخدم من المهمة
    const userId = taskElement.getAttribute('data-user-id');

    // ✅ إضافة المهمة لقائمة المهام النشطة للمستخدم
    if (!userActiveTasks.has(userId)) {
        userActiveTasks.set(userId, new Set());
    }
    userActiveTasks.get(userId).add(taskId);

    // ✅ حساب الوقت الأولي للمهمة
    const initialMinutes = parseInt(taskElement.getAttribute('data-initial-minutes') || '0');
    let initialSeconds = initialMinutes * 60;
    let startedAtTimestamp = taskElement.getAttribute('data-started-at');
    const taskStatus = taskElement.getAttribute('data-status');

    // ✅ التأكد من وجود timestamp صحيح عند بدء المهمة
    if (taskStatus === 'in_progress') {
        if (!startedAtTimestamp || startedAtTimestamp === 'null' || startedAtTimestamp === '') {
            // إذا لم يوجد timestamp، نضعه الآن
            startedAtTimestamp = new Date().getTime().toString();
            taskElement.setAttribute('data-started-at', startedAtTimestamp);
        }
        const elapsedSeconds = calculateElapsedSeconds(startedAtTimestamp);
        initialSeconds += elapsedSeconds;
    }

    timers[taskId] = initialSeconds;

    // ✅ تحديث عرض التايمر
    updateTaskTimerDisplay(taskId, timers[taskId]);

    // ✅ تحديث حالة المهمة
    taskElement.classList.remove('task-new', 'task-paused', 'task-completed');
    taskElement.classList.add('task-in-progress');

    // ✅ بدء تايمر المستخدم إذا لم يكن موجود
    if (!userTimers.has(userId)) {
        const userTimer = {
            seconds: 0,
            interval: setInterval(() => {
                // ✅ التحقق من وجود البيانات قبل الوصول إليها
                if (!userTimers.has(userId) || !userActiveTasks.has(userId)) {
                    return; // إيقاف التنفيذ إذا تم حذف البيانات
                }

                const userTimerData = userTimers.get(userId);
                const activeTasks = userActiveTasks.get(userId);

                if (userTimerData && activeTasks && activeTasks.size > 0) {
                    userTimerData.seconds++;

                    // ✅ تحديث جميع المهام النشطة للمستخدم مع فحص إضافي
                    activeTasks.forEach(activeTaskId => {
                        if (timers[activeTaskId] !== undefined) {
                            timers[activeTaskId]++;
                            updateTaskTimerDisplay(activeTaskId, timers[activeTaskId]);
                        }
                    });
                } else {
                    // ✅ إيقاف التايمر إذا لم تعد هناك مهام نشطة
                    const timer = userTimers.get(userId);
                    if (timer && timer.interval) {
                        clearInterval(timer.interval);
                        userTimers.delete(userId);
                    }
                }
            }, 1000)
        };
        userTimers.set(userId, userTimer);
    }
}

// ✅ دالة مساعدة لتحديث عرض التايمر
function updateTaskTimerDisplay(taskId, seconds) {
    // التحقق من صحة المدخلات
    if (!taskId || seconds < 0) {
        return;
    }

    const formattedTime = formatTime(seconds);
    let timerUpdated = false;

    // ✅ البحث الشامل عن عناصر التايمر بكل الطرق الممكنة
    const possibleIds = [
        'kanban-timer-' + taskId,
        'kanban-timer-template-' + taskId,
        'kanban-timer-regular-' + taskId,
        'timer-' + taskId,
        'my-kanban-timer-' + taskId,
        'my-timer-' + taskId
    ];

    // جرب كل الـ IDs الممكنة
    possibleIds.forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = formattedTime;
            element.classList.add('timer-active');
            timerUpdated = true;
        }
    });

    // ✅ البحث بـ CSS selector كـ backup
    if (!timerUpdated) {
        const timerElements = document.querySelectorAll(`[id*="${taskId}"]`);
        timerElements.forEach(element => {
            if (element.id.includes('timer') || element.classList.contains('timer')) {
                element.textContent = formattedTime;
                element.classList.add('timer-active');
                timerUpdated = true;
            }
        });
    }

    // ✅ البحث في المهمة نفسها
    if (!timerUpdated) {
        const taskElement = document.querySelector(`[data-task-user-id="${taskId}"]`);
        if (taskElement) {
            const timerInTask = taskElement.querySelector('.timer, [class*="timer"], [id*="timer"]');
            if (timerInTask) {
                timerInTask.textContent = formattedTime;
                timerInTask.classList.add('timer-active');
                timerUpdated = true;
            }
        }
    }
}

function pauseTimer(taskId) {
    const taskElement = document.querySelector(`.kanban-task[data-task-user-id="${taskId}"]`);
    if (!taskElement) return;

    // ✅ الحصول على معرف المستخدم
    const userId = taskElement.getAttribute('data-user-id');

    // ✅ إزالة المهمة من قائمة المهام النشطة للمستخدم
    if (userActiveTasks.has(userId)) {
        userActiveTasks.get(userId).delete(taskId);

        // ✅ إذا لم يتبق مهام نشطة للمستخدم، إيقاف تايمر المستخدم
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
    timers[taskId] = 0;
    updateTaskTimerDisplay(taskId, 0);
}

async function updateTaskStatus(taskId, newStatus) {
    const projectId = document.querySelector('meta[name="project-id"]')?.content;
    if (!projectId) {
        toastr.error('لم يتم العثور على معرف المشروع', 'خطأ ❌');
        return { minutesSpent: 0, no_change: true };
    }
    const taskElement = document.querySelector(`.kanban-task[data-task-user-id="${taskId}"]`);
    if (taskElement) {
        const currentStatus = taskElement.getAttribute('data-status');
        if (currentStatus === newStatus) {
            toastr.info('المهمة بالفعل في هذه الحالة', 'تنبيه');
            return { minutesSpent: 0, no_change: true };
        }
    }
    const response = await fetch(`/template-tasks/${taskId}/update-status`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            status: newStatus,
            project_id: projectId
        })
    });
    if (!response.ok) {
        throw new Error('فشل في تحديث حالة المهمة');
    }
    const result = await response.json();
    if (result.no_change) {
        toastr.info(result.message, 'تنبيه');
        return result;
    }
    if (result.minutesSpent > 0) {
        const timerElement = document.getElementById(`kanban-timer-${taskId}`);
        if (timerElement) {
            const hours = Math.floor(result.minutesSpent / 60);
            const minutes = result.minutesSpent % 60;
            const formattedTime = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:00`;
            timerElement.textContent = formattedTime;
            timerElement.classList.add('text-success');
            setTimeout(() => {
                timerElement.classList.remove('text-success');
            }, 3000);
        }
    }
    if (taskElement) {
        taskElement.classList.remove('task-in-progress', 'task-paused', 'task-completed', 'task-new');
        taskElement.classList.add(`task-${newStatus}`);
        taskElement.setAttribute('data-status', newStatus);
        if (newStatus === 'in_progress' && result.task && result.task.started_at) {
            const startedAtDate = new Date(result.task.started_at);
            const startedAtTimestamp = startedAtDate.getTime();
            taskElement.setAttribute('data-started-at', startedAtTimestamp);
        }
    }
    return result;
}

function initTaskButtons() {
    document.querySelectorAll('.start-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const taskId = this.getAttribute('data-task-id');
            const taskElement = document.querySelector(`.kanban-task[data-task-user-id="${taskId}"]`);
            if (taskElement && taskElement.getAttribute('data-status') === 'in_progress') {
                toastr.info('المهمة قيد التنفيذ بالفعل', 'تنبيه');
                return;
            }
            startTimer(taskId);
            updateTaskStatus(taskId, 'in_progress').then((result) => {
                if (!result.no_change) {
                    toastr.success('تم بدء المهمة بنجاح', 'نجاح');
                }
            }).catch(error => {
                toastr.error('حدث خطأ أثناء بدء المهمة', 'خطأ');
                console.error('Error starting task:', error);
            });
        });
    });
    document.querySelectorAll('.pause-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const taskId = this.getAttribute('data-task-id');
            const taskElement = document.querySelector(`.kanban-task[data-task-user-id="${taskId}"]`);
            if (taskElement && taskElement.getAttribute('data-status') === 'paused') {
                toastr.info('المهمة متوقفة مؤقتًا بالفعل', 'تنبيه');
                return;
            }
            pauseTimer(taskId);
            updateTaskStatus(taskId, 'paused').then((result) => {
                if (!result.no_change) {
                    toastr.success('تم إيقاف المهمة مؤقتًا', 'نجاح');
                }
            }).catch(error => {
                toastr.error('حدث خطأ أثناء إيقاف المهمة', 'خطأ');
                console.error('Error pausing task:', error);
            });
        });
    });
    document.querySelectorAll('.finish-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const taskId = this.getAttribute('data-task-id');
            const taskElement = document.querySelector(`.kanban-task[data-task-user-id="${taskId}"]`);
            if (taskElement && taskElement.getAttribute('data-status') === 'completed') {
                toastr.info('المهمة مكتملة بالفعل', 'تنبيه');
                return;
            }
            finishTimer(taskId);
            updateTaskStatus(taskId, 'completed').then((result) => {
                if (!result.no_change) {
                    toastr.success(`تم إنهاء المهمة بنجاح! الوقت المسجل: ${result.minutesSpent} دقيقة`, 'نجاح');
                }
            }).catch(error => {
                toastr.error('حدث خطأ أثناء إنهاء المهمة', 'خطأ');
                console.error('Error completing task:', error);
            });
        });
    });
}

function initInProgressTasks() {
    const inProgressColumn = document.getElementById('kanban-in_progress');
    if (inProgressColumn) {
        const inProgressTasks = inProgressColumn.querySelectorAll('.kanban-task');
        inProgressTasks.forEach(task => {
            const taskUserId = task.getAttribute('data-task-user-id');
            if (taskUserId) {
                task.classList.add('task-in-progress');
                startTimer(taskUserId);
            }
        });
    }

    const pausedColumn = document.getElementById('kanban-paused');
    if (pausedColumn) {
        pausedColumn.querySelectorAll('.kanban-task').forEach(task => {
            task.classList.add('task-paused');
        });
    }

    const completedColumn = document.getElementById('kanban-completed');
    if (completedColumn) {
        completedColumn.querySelectorAll('.kanban-task').forEach(task => {
            task.classList.add('task-completed');
        });
    }
}
