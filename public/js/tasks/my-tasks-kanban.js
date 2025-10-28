
function loadMyTasksModules() {
    const basePath = '/js/tasks/my-tasks/';
    const modules = [
        'utils.js',
        'timers.js',
        'kanban.js',
        'drag-drop.js',
        'modal-handlers.js',
        'core.js',
        'page-init.js'
    ];

    const promises = modules.map(module => {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = basePath + module;
            script.onload = () => resolve(module);
            script.onerror = () => reject(module);
            document.head.appendChild(script);
        });
    });

    return Promise.all(promises);
}

async function initializeMyTasksSystem() {
    try {
        console.log('📦 Loading My Tasks Modules...');

        await loadMyTasksModules();

        // انتظار للتأكد من تحميل جميع الملفات
        await new Promise(resolve => setTimeout(resolve, 150));

        const requiredModules = {
            MyTasksCore: window.MyTasksCore,
            MyTasksKanban: window.MyTasksKanban,
            MyTasksTimers: window.MyTasksTimers,
            MyTasksDragDrop: window.MyTasksDragDrop,
            MyTasksUtils: window.MyTasksUtils
        };

        console.log('✅ Modules loaded:', Object.keys(requiredModules).filter(key => requiredModules[key]));

        if (window.MyTasksCore && typeof window.MyTasksCore.initializeMyTasksKanban === 'function') {
            window.MyTasksCore.initializeMyTasksKanban();
        } else {
            // fallback: تهيئة مباشرة
            console.log('⚠️ Using fallback initialization');
            initializeMyTasksKanban();
        }
    } catch (error) {
        console.warn('Error loading MyTasks modules:', error);
        if (typeof initializeMyTasksKanban === 'function') {
            initializeMyTasksKanban();
        }
    }
}

function initializeMyTasksKanban() {
    console.log('🚀 Initializing My Tasks Kanban Board...');

    // البيانات الآن تُعرض مباشرة من PHP في HTML
    // لا نحتاج لتحميل البيانات من JavaScript

    // ✅ تهيئة Drag & Drop للكاردات الموجودة
    initializeDragDropForExistingCards();

    // فقط تهيئة التايمرات للمهام الموجودة
    initializeMyTasksTimers();

    console.log('✅ My Tasks Kanban Board initialized successfully');
}

// ✅ تهيئة Drag & Drop للكاردات الموجودة في HTML
function initializeDragDropForExistingCards() {
    console.log('🎯 Initializing Drag & Drop for existing kanban cards...');

    // التأكد من وجود MyTasksDragDrop
    if (!window.MyTasksDragDrop) {
        console.error('❌ MyTasksDragDrop module not found!');
        // محاولة مرة أخرى بعد delay
        setTimeout(initializeDragDropForExistingCards, 200);
        return;
    }

    // تهيئة جميع Drop Zones
    if (typeof window.MyTasksDragDrop.initializeDropZones === 'function') {
        window.MyTasksDragDrop.initializeDropZones();
        console.log('✅ Drop Zones initialized');
    } else {
        console.error('❌ initializeDropZones function not found');
    }

    // تهيئة Drag للكاردات الموجودة
    const existingCards = document.querySelectorAll('.kanban-card[draggable="true"]');
    console.log(`📋 Found ${existingCards.length} draggable cards`);

    if (existingCards.length === 0) {
        console.warn('⚠️ No draggable cards found in DOM');
    }

    existingCards.forEach(card => {
        if (typeof window.MyTasksDragDrop.addDragDropToCard === 'function') {
            window.MyTasksDragDrop.addDragDropToCard($(card));
        } else {
            console.error('❌ addDragDropToCard function not found');
        }
    });

    console.log('✅ Drag & Drop initialized for all cards');
}

// 🔄 تهيئة التايمرات للمهام قيد التنفيذ في صفحة مهامي
function initializeMyTasksTimers() {
    // البحث عن جميع المهام قيد التنفيذ في الكانبان
    const inProgressCards = document.querySelectorAll('.kanban-card[data-status="in_progress"]');

    inProgressCards.forEach(card => {
        // ✅ استخدام data-task-id لأنه ده المستخدم في HTML id للتايمر
        const taskId = card.getAttribute('data-task-id');
        if (taskId) {
            startMyTaskTimer(taskId);
        }
    });
}

// ⏰ بدء تايمر مهمة في صفحة مهامي
function startMyTaskTimer(taskId) {
    // ✅ البحث عن الكارد باستخدام task-id أو task-user-id
    let taskElement = document.querySelector(`.kanban-card[data-task-id="${taskId}"]`);
    if (!taskElement) {
        taskElement = document.querySelector(`.kanban-card[data-task-user-id="${taskId}"]`);
    }
    if (!taskElement) return;

    // الحصول على البيانات المحفوظة
    const initialMinutes = parseInt(taskElement.dataset.initialMinutes || '0');
    let totalSeconds = initialMinutes * 60;

    // التأكد من وجود timestamp صحيح عند بدء المهمة
    let startedAt = taskElement.dataset.startedAt;
    if (!startedAt || startedAt === 'null' || startedAt === '') {
        startedAt = new Date().getTime().toString();
        taskElement.dataset.startedAt = startedAt;
        taskElement.setAttribute('data-started-at', startedAt);
    }

    const elapsedSeconds = calculateElapsedSeconds(startedAt);
    totalSeconds += elapsedSeconds;

    // ✅ استخدام data-task-id للتايمر (كده في الـ blade)
    const timerId = taskElement.dataset.taskId || taskId;

    // تحديث التايمر المحلي
    if (!window.myTaskTimers) window.myTaskTimers = {};
    window.myTaskTimers[timerId] = totalSeconds;
    updateMyTaskTimerDisplay(timerId, totalSeconds);

    // بدء العد التصاعدي
    if (!window.myTimerIntervals) window.myTimerIntervals = {};
    if (window.myTimerIntervals[timerId]) {
        clearInterval(window.myTimerIntervals[timerId]);
    }

    window.myTimerIntervals[timerId] = setInterval(() => {
        window.myTaskTimers[timerId]++;
        updateMyTaskTimerDisplay(timerId, window.myTaskTimers[timerId]);
    }, 1000);
}

// 🔄 تحديث عرض التايمر في صفحة مهامي
function updateMyTaskTimerDisplay(taskId, seconds) {
    const timerElement = document.querySelector(`#my-kanban-timer-${taskId}`);
    if (timerElement) {
        timerElement.textContent = formatTime(seconds);
    }
}

// ⏰ تنسيق الوقت
function formatTime(seconds) {
    const h = String(Math.floor(seconds / 3600)).padStart(2, '0');
    const m = String(Math.floor((seconds % 3600) / 60)).padStart(2, '0');
    const s = String(seconds % 60).padStart(2, '0');
    return `${h}:${m}:${s}`;
}

// ⏰ حساب الثواني المنقضية
function calculateElapsedSeconds(startedAtTimestamp) {
    if (!startedAtTimestamp || startedAtTimestamp === 'null') return 0;
    const startedAt = parseInt(startedAtTimestamp);
    if (isNaN(startedAt)) return 0;
    const now = new Date().getTime();
    const elapsedMilliseconds = now - startedAt;
    return Math.floor(elapsedMilliseconds / 1000);
}

// ✅ تشغيل النظام عند تحميل الصفحة
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        console.log('📄 DOM loaded, initializing My Tasks System...');
        initializeMyTasksSystem();
    });
} else {
    console.log('📄 DOM already loaded, initializing My Tasks System immediately...');
    initializeMyTasksSystem();
}

function loadMyTasksIntoKanban() {
    // البيانات الآن تُعرض مباشرة من PHP في HTML
    // هذه الدالة لم تعد مطلوبة، لكن نتركها للتوافق مع الكود الموجود

    console.log('📋 My Tasks Kanban data is now loaded directly from PHP in HTML');

    setTimeout(() => {
        // بدء التايمرات للمهام قيد التنفيذ
        initializeMyTasksTimers();
    }, 100);
}

function filterMyTasksKanban(projectId, status, searchText) {
    if (window.MyTasksKanban && window.MyTasksKanban.filterMyTasksKanban) {
        return window.MyTasksKanban.filterMyTasksKanban(projectId, status, searchText);
    }
}

function createMyTaskCard(task) {
    if (window.MyTasksKanban && window.MyTasksKanban.createMyTaskCard) {
        return window.MyTasksKanban.createMyTaskCard(task);
    }
}

window.initializeMyTasksKanban = initializeMyTasksKanban;
window.loadMyTasksIntoKanban = loadMyTasksIntoKanban;
window.filterMyTasksKanban = filterMyTasksKanban;
window.createMyTaskCard = createMyTaskCard;
window.initializeMyTasksTimers = initializeMyTasksTimers;
window.startMyTaskTimer = startMyTaskTimer;
window.updateMyTaskTimerDisplay = updateMyTaskTimerDisplay;
window.formatTime = formatTime;
window.calculateElapsedSeconds = calculateElapsedSeconds;

$(document).ready(function() {
    if (window.myTasksCurrentView === 'kanban' || localStorage.getItem('myTasksViewPreference') === 'kanban') {
        $('#myTasksTableView').hide();
        $('#myTasksKanbanView').show();
        $('#myTasksTableViewBtn').removeClass('active');
        $('#myTasksKanbanViewBtn').addClass('active');
        $('#myTasksTotalTimerContainer').show();
    }

    // تهيئة الكانبان مباشرة بدلاً من تحميل النظام المعقد
    initializeMyTasksKanban();
});
