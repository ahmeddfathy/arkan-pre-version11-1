// تجنب إعادة التعريف إذا كان الملف محمّل بالفعل
if (typeof window.MyTasksCoreInitialized === 'undefined') {
    window.MyTasksCoreInitialized = true;

    var myTasksCurrentView = 'table';
    var myTasksData = [];
    var originalMyTasksData = [];

    var timers = {};
    var intervals = {};
    var userActiveTasks = new Map();
    var userTimers = new Map();

    var totalTimerState = {
        activeTasksCount: 0,
        totalSeconds: 0,
        timerInterval: null,
        running: false,
        activeTasks: new Set(),
        initialTime: 0,
        userActiveTasks: new Map(),
        activeUsers: new Set()
    };

    window.myTasksCurrentView = myTasksCurrentView;
} else {
    console.log('⚠️ MyTasksCore already initialized, skipping redeclaration');
}

window.addEventListener('beforeunload', function() {
    userTimers.forEach((userTimer, userId) => {
        if (userTimer && userTimer.interval) {
            clearInterval(userTimer.interval);
        }
    });
    userTimers.clear();
    userActiveTasks.clear();

    if (totalTimerState.timerInterval) {
        clearInterval(totalTimerState.timerInterval);
    }
    totalTimerState.activeTasks.clear();
    totalTimerState.userActiveTasks.clear();
    totalTimerState.activeUsers.clear();
});

function applyMyTasksStoredViewPreference() {
    if (myTasksCurrentView === 'kanban') {
        window.myTasksCurrentView = myTasksCurrentView;

        const kanbanView = $('#myTasksKanbanView');
        const tableView = $('#myTasksTableView');
        const timerContainer = $('#myTasksTotalTimerContainer');

        const alreadyApplied = kanbanView.is(':visible') && tableView.is(':hidden');

        if (!alreadyApplied) {
            tableView.hide();
            kanbanView.show();
            $('#myTasksTableViewBtn').removeClass('active');
            $('#myTasksKanbanViewBtn').addClass('active');
            timerContainer.show();
        }

        window.MyTasksTimers.updateTotalTimerDisplay();

        // تنفيذ فوري بدون انتظار غير ضروري
        window.MyTasksKanban.loadMyTasksIntoKanban();

        // انتظار قصير فقط لضمان تحميل DOM
        setTimeout(() => {
            window.MyTasksDragDrop.initializeDropZones();
            window.MyTasksTimers.calculateInitialTotalTime();
            window.MyTasksTimers.syncTimerWithTasks();
            window.MyTasksTimers.startMyTasksTotalTimer();

            // تطبيق الـ CSS classes فوراً
            const cardBody = document.querySelector('.card-body');
            if (cardBody) {
                cardBody.classList.remove('my-tasks-loading');
                cardBody.classList.add('my-tasks-loaded');
            }
        }, 100); // تم تقليلها من 600ms إلى 100ms فقط!
    } else {
        window.myTasksCurrentView = myTasksCurrentView;
        $('#myTasksTableView').show();
        $('#myTasksKanbanView').hide();
        $('#myTasksTableViewBtn').addClass('active');
        $('#myTasksKanbanViewBtn').removeClass('active');
        $('#myTasksTotalTimerContainer').hide();
        window.MyTasksTimers.stopMyTasksTotalTimer();

        setTimeout(() => {
            const cardBody = document.querySelector('.card-body');
            if (cardBody) {
                cardBody.classList.remove('my-tasks-loading');
                cardBody.classList.add('my-tasks-loaded');
            }
        }, 50);
    }
}

function switchToMyTasksTableView() {
    myTasksCurrentView = 'table';
    window.myTasksCurrentView = myTasksCurrentView;
    $('#myTasksTableView').show();
    $('#myTasksKanbanView').hide();
    $('#myTasksTableViewBtn').addClass('active');
    $('#myTasksKanbanViewBtn').removeClass('active');
    $('#myTasksTotalTimerContainer').hide();
    window.MyTasksTimers.stopMyTasksTotalTimer();
    localStorage.setItem('myTasksViewPreference', 'table');
    setTimeout(() => {
        const cardBody = document.querySelector('.card-body');
        if (cardBody) {
            cardBody.classList.add('my-tasks-loaded');
        }
    }, 50);
}

function switchToMyTasksKanbanView() {
    myTasksCurrentView = 'kanban';
    window.myTasksCurrentView = myTasksCurrentView;
    $('#myTasksTableView').hide();
    $('#myTasksKanbanView').show();
    $('#myTasksTableViewBtn').removeClass('active');
    $('#myTasksKanbanViewBtn').addClass('active');
    $('#myTasksTotalTimerContainer').show();
    window.MyTasksTimers.updateTotalTimerDisplay();
    localStorage.setItem('myTasksViewPreference', 'kanban');
    window.MyTasksKanban.loadMyTasksIntoKanban();

    // تنفيذ فوري بدلاً من انتظار 300ms
    setTimeout(() => {
        window.MyTasksDragDrop.initializeDropZones();
        window.MyTasksTimers.syncTimerWithTasks();
        window.MyTasksTimers.startMyTasksTotalTimer();

        const cardBody = document.querySelector('.card-body');
        if (cardBody) {
            cardBody.classList.add('my-tasks-loaded');
        }
    }, 50); // تم تقليلها من 300ms إلى 50ms فقط!
}

function initializeMyTasksKanban() {
    myTasksCurrentView = localStorage.getItem('myTasksViewPreference') || 'table';
    window.MyTasksKanban.initializeMyTasksKanbanBoard();
    window.MyTasksTimers.initializeMyTasksTimers();
    window.MyTasksTimers.initMyTasksTotalTimer();

    // ✅ تهيئة Page Visibility Handler لحل مشكلة توقف التايمر
    window.MyTasksTimers.initializePageVisibilityHandler();

    applyMyTasksStoredViewPreference();

    $('#myTasksTableViewBtn').click(function() {
        if (myTasksCurrentView !== 'table') {
            switchToMyTasksTableView();
        }
    });

    $('#myTasksKanbanViewBtn').click(function() {
        if (myTasksCurrentView !== 'kanban') {
            switchToMyTasksKanbanView();
        }
    });

    const originalFilterTasks = window.filterTasks;
    window.filterTasks = function() {
        if (typeof originalFilterTasks === 'function') {
            originalFilterTasks();
        }
        if (myTasksCurrentView === 'kanban') {
            const projectId = $('#projectFilter').val();
            const status = $('#statusFilter').val();
            const searchText = $('#searchInput').val().toLowerCase();
            window.MyTasksKanban.filterMyTasksKanban(projectId, status, searchText);
        }
    };

    $(document).on('click', '.my-kanban-card .view-task', function(e) {
        e.stopPropagation();
    });

    $(document).on('click', '.my-kanban-card', function(e) {
        if (!$(this).hasClass('dragging')) {
            const taskId = $(this).data('task-id');
            const isTemplate = $(this).data('is-template');
            const viewButton = $(`.view-task[data-id="${taskId}"]`);
            if (viewButton.length) {
                viewButton.click();
            } else if (typeof loadTaskDetails === 'function') {
                loadTaskDetails(taskId, isTemplate);
            }
        }
    });

    setInterval(function() {
        if (myTasksCurrentView === 'kanban') {
            $('.my-kanban-card[data-status="in_progress"]').each(function() {
                const taskId = $(this).data('task-id');
                const timerElement = $(`.task-timer[data-task-id="${taskId}"]`);
                const cardTimerElement = $(this).find('.my-kanban-card-timer');
                if (timerElement.length && cardTimerElement.length) {
                    const currentTimer = timerElement.text().trim();
                    cardTimerElement.html(`<i class="fas fa-clock"></i> ${currentTimer}`);
                }
            });
        }
    }, 30000);
}

window.MyTasksCore = {
    getCurrentView: () => myTasksCurrentView,
    getData: () => myTasksData,
    getOriginalData: () => originalMyTasksData,
    getTimers: () => timers,
    getTotalTimerState: () => totalTimerState,
    getUserActiveTasks: () => userActiveTasks,
    getUserTimers: () => userTimers,
    initializeMyTasksKanban,
    switchToMyTasksTableView,
    switchToMyTasksKanbanView,
    applyMyTasksStoredViewPreference,
    setData: (data) => { myTasksData = data; },
    setOriginalData: (data) => { originalMyTasksData = data; },
    setCurrentView: (view) => {
        myTasksCurrentView = view;
        window.myTasksCurrentView = view;
    }
};
