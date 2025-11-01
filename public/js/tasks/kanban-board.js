let currentView = "table";

// ⏱️ نظام التايمرات للمهام
let taskTimers = {};
let timerIntervals = {};

window.currentView = currentView;

function generateUserColor(userId) {
    if (!userId) return { bg: "#6b7280", text: "#ffffff", border: "#4b5563" };

    const colorPalettes = [
        { bg: "#3b82f6", text: "#ffffff", border: "#1d4ed8" },
        { bg: "#22c55e", text: "#ffffff", border: "#15803d" },
        { bg: "#f59e0b", text: "#ffffff", border: "#d97706" },
        { bg: "#ec4899", text: "#ffffff", border: "#be185d" },
        { bg: "#8b5cf6", text: "#ffffff", border: "#7c3aed" },
        { bg: "#ef4444", text: "#ffffff", border: "#dc2626" },
        { bg: "#10b981", text: "#ffffff", border: "#059669" },
        { bg: "#0ea5e9", text: "#ffffff", border: "#0369a1" },
        { bg: "#f97316", text: "#ffffff", border: "#ea580c" },
        { bg: "#64748b", text: "#ffffff", border: "#475569" },
        { bg: "#c026d3", text: "#ffffff", border: "#a21caf" },
        { bg: "#14b8a6", text: "#ffffff", border: "#0d9488" },
        { bg: "#eab308", text: "#ffffff", border: "#ca8a04" },
        { bg: "#6366f1", text: "#ffffff", border: "#4f46e5" },
        { bg: "#84cc16", text: "#ffffff", border: "#65a30d" },
        { bg: "#f43f5e", text: "#ffffff", border: "#e11d48" },
        { bg: "#06b6d4", text: "#ffffff", border: "#0891b2" },
        { bg: "#8b5cf6", text: "#ffffff", border: "#7c3aed" },
        { bg: "#fb7185", text: "#ffffff", border: "#f43f5e" },
        { bg: "#34d399", text: "#ffffff", border: "#10b981" },
    ];

    const colorIndex = parseInt(userId) % colorPalettes.length;
    return colorPalettes[colorIndex];
}

function applyUserColors() {
    $(".creator-badge").each(function () {
        const creatorId = $(this).data("creator-id");
        const colors = generateUserColor(creatorId);

        $(this).css({
            background: colors.bg,
            color: colors.text,
            "border-color": colors.border,
            "border-width": "2px",
            "border-style": "solid",
            "box-shadow": "0 2px 4px rgba(0,0,0,0.1)",
        });
    });

    $(".kanban-card-creator").each(function () {
        const creatorId = $(this).data("creator-id");
        const colors = generateUserColor(creatorId);

        $(this).css({
            background: colors.bg,
            color: colors.text,
            "border-color": colors.border,
            "border-width": "2px",
            "border-style": "solid",
            "box-shadow": "0 2px 4px rgba(0,0,0,0.1)",
        });
    });
}

function checkAndApplyShowAllPreference() {
    // يمكن الاحتفاظ بهذا فقط للتسجيل
    const showAllPreference = localStorage.getItem("tasksShowAllPreference");
    const currentShowAll = window.isShowAllView || false;

    console.log(
        "Show All Preference:",
        showAllPreference,
        "Current State:",
        currentShowAll
    );
}

function applyStoredViewPreference() {
    if (currentView === "kanban") {
        window.currentView = currentView;
        $(".table-responsive").hide();
        $("#kanbanBoard").show();
        $("#calendarBoard").hide();
        $("#tableViewBtn").removeClass("active");
        $("#kanbanViewBtn").addClass("active");
        $("#calendarViewBtn").removeClass("active");

        setTimeout(() => {
            loadTasksIntoKanban();
            // ✅ تطبيق الفلاتر الموجودة عند تحميل الصفحة بالكانبان
            if (typeof window.filterTasks === "function") {
                window.filterTasks();
            }
        }, 150);
    } else if (currentView === "calendar") {
        window.currentView = currentView;
        $(".table-responsive").hide();
        $("#kanbanBoard").hide();
        $("#calendarBoard").show();
        $("#tableViewBtn").removeClass("active");
        $("#kanbanViewBtn").removeClass("active");
        $("#calendarViewBtn").addClass("active");

        // ✅ تحميل التقويم عند تحميل الصفحة
        setTimeout(() => {
            if (
                typeof window.tasksIndexCalendar !== "undefined" &&
                window.tasksIndexCalendar.refresh
            ) {
                window.tasksIndexCalendar.refresh();
            }
        }, 150);
    } else {
        window.currentView = currentView;
        $(".table-responsive").show();
        $("#kanbanBoard").hide();
        $("#calendarBoard").hide();
        $("#tableViewBtn").addClass("active");
        $("#kanbanViewBtn").removeClass("active");
        $("#calendarViewBtn").removeClass("active");

        // ✅ تطبيق الفلاتر الموجودة عند تحميل الصفحة بالجدول
        setTimeout(() => {
            if (typeof window.filterTasks === "function") {
                window.filterTasks();
            }
        }, 100);
    }

    updateTaskCountDisplay();
}

function updateTaskCountDisplay() {
    const totalTasks = $("#tasksTable tbody tr").length - 1;
    const isShowAll = window.isShowAllView || false;
}

function switchToTableView() {
    currentView = "table";
    window.currentView = currentView;
    $(".table-responsive").show();
    $("#kanbanBoard").hide();
    $("#calendarBoard").hide();
    $("#tableViewBtn").addClass("active");
    $("#kanbanViewBtn").removeClass("active");
    $("#calendarViewBtn").removeClass("active");

    localStorage.setItem("tasksViewPreference", "table");

    // ✅ تطبيق الفلاتر الموجودة على الجدول بعد التبديل
    setTimeout(() => {
        applyUserColors();
        if (typeof window.filterTasks === "function") {
            window.filterTasks();
        }
    }, 100);
}

function switchToKanbanView() {
    currentView = "kanban";
    window.currentView = currentView;
    $(".table-responsive").hide();
    $("#kanbanBoard").hide();
    $("#calendarBoard").hide();
    $("#kanbanBoard").show();
    $("#tableViewBtn").removeClass("active");
    $("#kanbanViewBtn").addClass("active");
    $("#calendarViewBtn").removeClass("active");

    localStorage.setItem("tasksViewPreference", "kanban");

    loadTasksIntoKanban();

    // ✅ تطبيق الفلاتر الموجودة على الكانبان بعد التبديل
    setTimeout(() => {
        if (typeof window.filterTasks === "function") {
            window.filterTasks();
        }
    }, 150);
}

function switchToCalendarView() {
    currentView = "calendar";
    window.currentView = currentView;
    $(".table-responsive").hide();
    $("#kanbanBoard").hide();
    $("#calendarBoard").show();
    $("#tableViewBtn").removeClass("active");
    $("#kanbanViewBtn").removeClass("active");
    $("#calendarViewBtn").addClass("active");

    localStorage.setItem("tasksViewPreference", "calendar");

    // ✅ تحميل وتحديث التقويم
    if (
        typeof window.tasksIndexCalendar !== "undefined" &&
        window.tasksIndexCalendar.refresh
    ) {
        window.tasksIndexCalendar.refresh();
    }

    console.log("📅 Switched to Calendar View");
}

function initializeKanbanBoard() {
    console.log("🚀 Initializing Kanban Board...");

    // البيانات الآن تُعرض مباشرة من PHP في HTML
    // لا نحتاج لتحميل البيانات من JavaScript

    // فقط تهيئة التايمرات للمهام الموجودة
    initializeTaskTimers();

    console.log("✅ Kanban Board initialized successfully");
}

function loadTasksIntoKanban() {
    // البيانات الآن تُعرض مباشرة من PHP في HTML
    // هذه الدالة لم تعد مطلوبة، لكن نتركها للتوافق مع الكود الموجود

    console.log("📋 Kanban data is now loaded directly from PHP in HTML");

    setTimeout(() => {
        applyUserColors();
        // بدء التايمرات للمهام قيد التنفيذ
        initializeTaskTimers();
    }, 100);
}

// 🔄 تهيئة التايمرات للمهام قيد التنفيذ
function initializeTaskTimers() {
    // البحث عن جميع المهام قيد التنفيذ في الكانبان
    const inProgressCards = document.querySelectorAll(
        '.kanban-card[data-status="in_progress"]'
    );

    inProgressCards.forEach((card) => {
        const taskId = card.getAttribute("data-task-id");
        if (taskId) {
            startTaskTimer(taskId);
        }
    });
}

// دالة createTaskCard لم تعد مطلوبة - البيانات تُعرض مباشرة من PHP

function getStatusText(status) {
    const statusMap = {
        new: "جديدة",
        in_progress: "قيد التنفيذ",
        paused: "متوقفة",
        completed: "مكتملة",
        cancelled: "ملغاة",
    };
    return statusMap[status] || status;
}

function getRevisionStatusTooltip(task) {
    const total = task.revisionsCount || 0;
    const pending = task.pendingRevisionsCount || 0;
    const approved = task.approvedRevisionsCount || 0;
    const rejected = task.rejectedRevisionsCount || 0;

    let tooltip = `${total} تعديلات`;

    if (pending > 0) {
        tooltip += ` - ${pending} معلق`;
    }
    if (approved > 0) {
        tooltip += ` - ${approved} مقبول`;
    }
    if (rejected > 0) {
        tooltip += ` - ${rejected} مرفوض`;
    }

    return tooltip;
}

function getDueDateClass(dueDate) {
    if (!dueDate || dueDate === "غير محدد") return "";

    const today = new Date();
    const due = new Date(dueDate);
    const diffTime = due - today;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays < 0) return "text-danger";
    if (diffDays <= 2) return "text-warning";
    return "text-muted";
}

function initializeKanbanSystem() {
    // إيقاف جميع التايمرات السابقة
    stopAllTimers();

    // ✅ دعم ثلاث عروض: table, kanban, calendar
    currentView = localStorage.getItem("tasksViewPreference") || "table";

    checkAndApplyShowAllPreference();

    applyUserColors();
    initializeKanbanBoard();
    applyStoredViewPreference();

    // ✅ تهيئة Page Visibility Handler لحل مشكلة توقف التايمر
    initializeTasksPageVisibilityHandler();

    $(".modal").on("hidden.bs.modal", function () {
        setTimeout(() => {
            applyUserColors();
        }, 200);
    });

    $(document).on("click", "#showAllBtn", function (e) {
        e.preventDefault();
        localStorage.setItem("tasksShowAllPreference", "true");
        window.location.href = $(this).attr("href");
    });

    $(document).on("click", "#showPaginatedBtn", function (e) {
        e.preventDefault();
        localStorage.setItem("tasksShowAllPreference", "false");
        window.location.href = $(this).attr("href");
    });

    $("#tableViewBtn").click(function () {
        if (currentView !== "table") {
            switchToTableView();
        }
    });

    $("#kanbanViewBtn").click(function () {
        if (currentView !== "kanban") {
            switchToKanbanView();
        }
    });

    $("#calendarViewBtn").click(function () {
        if (currentView !== "calendar") {
            switchToCalendarView();
        }
    });

    $(document).on("filtersApplied", function () {
        setTimeout(() => {
            applyUserColors();
            // إعادة تهيئة التايمرات بعد الفلترة
            if (currentView === "kanban") {
                stopAllTimers();
                initializeTaskTimers();
            }
        }, 50);
    });

    $(document).on("click", ".kanban-card .view-task", function (e) {
        e.stopPropagation();
        const taskId = $(this).data("id");
        const taskUserId = $(this).data("task-user-id") || taskId;
        const isTemplate =
            $(this).attr("data-is-template") ||
            $(this).data("is-template") ||
            $(this).closest(".kanban-card").attr("data-is-template") ||
            $(this).closest(".kanban-card").data("is-template");

        // استخدم نفس الطريقة الموجودة في modals.js
        const taskType =
            isTemplate === "true" || isTemplate === true
                ? "template"
                : "regular";

        // محاولة الحصول على البيانات من العنصر الأب إذا لم تكن متاحة
        const $card = $(this).closest(".kanban-card");
        const finalTaskId = taskId || $card.data("task-id");
        const finalTaskUserId =
            taskUserId || $card.data("task-user-id") || finalTaskId;
        const finalIsTemplate = isTemplate || $card.data("is-template");

        // ✅ استخدم TaskUser ID دائماً (سواء عادية أو قالب)
        const targetId = finalTaskUserId;

        console.log("🔍 Opening task sidebar from kanban-board.js:", {
            originalTaskId: taskId,
            originalTaskUserId: taskUserId,
            finalTaskId: finalTaskId,
            finalTaskUserId: finalTaskUserId,
            taskType: taskType,
            targetId: targetId,
            isTemplate: finalIsTemplate,
            parentCard: $card.length > 0,
            parentCardData: $card.data(),
        });

        // التأكد من وجود المعاملات المطلوبة
        if (!targetId) {
            console.error("❌ Missing targetId:", {
                originalTaskId: taskId,
                originalTaskUserId: taskUserId,
                finalTaskId: finalTaskId,
                finalTaskUserId: finalTaskUserId,
                taskType: taskType,
                parentCard: $card.length > 0,
                parentCardData: $card.data(),
            });
            alert("خطأ: لم يتم العثور على معرف المهمة");
            return;
        }

        if (typeof openTaskSidebar === "function") {
            openTaskSidebar(taskType, targetId);
        } else {
            console.error("❌ openTaskSidebar function not found");
        }
    });

    $(document).on("click", ".kanban-card .edit-task", function (e) {
        e.stopPropagation();
        const taskId = $(this).data("id");
        if (typeof loadTaskForEdit === "function") {
            loadTaskForEdit(taskId);
        }
    });

    $(document).on("click", ".kanban-card .transfer-task", function (e) {
        e.stopPropagation();
        const taskType = $(this).data("task-type");
        const taskId = $(this).data("task-id");
        const taskUserId = $(this).data("task-user-id");
        const taskName = $(this).data("task-name");
        const currentUser = $(this).data("current-user");
        const mode = $(this).data("mode") || "transfer";

        console.log("📤 نقل مهمة من kanban:", {
            taskType,
            taskId,
            taskUserId,
            taskName,
            currentUser,
            mode,
        });

        if (typeof openTransferModal === "function") {
            openTransferModal(
                taskType,
                taskId,
                taskName,
                currentUser,
                mode,
                taskUserId
            );
        } else {
            console.error("openTransferModal function not found");
        }
    });

    $(document).on("click", ".kanban-card", function (e) {
        if (!$(e.target).closest(".kanban-card-actions").length) {
            const taskId = $(this).data("task-id");
            const isTemplate = $(this).data("is-template");

            if (typeof loadTaskDetails === "function") {
                loadTaskDetails(taskId, isTemplate);
            }
        }
    });
}

$(document).ready(function () {
    initializeKanbanSystem();
});

// 🧹 تنظيف التايمرات عند مغادرة الصفحة
$(window).on("beforeunload", function () {
    stopAllTimers();
});

// ⏰ دوال التايمر المساعدة
function formatTime(seconds) {
    const h = String(Math.floor(seconds / 3600)).padStart(2, "0");
    const m = String(Math.floor((seconds % 3600) / 60)).padStart(2, "0");
    const s = String(seconds % 60).padStart(2, "0");
    return `${h}:${m}:${s}`;
}

function calculateElapsedSeconds(startedAtTimestamp) {
    if (!startedAtTimestamp || startedAtTimestamp === "null") return 0;
    const startedAt = parseInt(startedAtTimestamp);
    if (isNaN(startedAt)) return 0;
    const now = new Date().getTime();
    const elapsedMilliseconds = now - startedAt;
    return Math.floor(elapsedMilliseconds / 1000);
}

// ✅ إضافة Page Visibility API لحل مشكلة توقف التايمر في صفحة Tasks Index
function initializeTasksPageVisibilityHandler() {
    // الكشف عن تغيير حالة الصفحة (نشطة/غير نشطة)
    document.addEventListener("visibilitychange", function () {
        if (!document.hidden) {
            // المستخدم عاد للتاب - نحديث جميع التايمرات
            syncAllTaskTimersWithRealTime();
        }
    });

    // تحديث التايمرات كل 10 ثوان كـ backup عندما التاب نشط
    setInterval(function () {
        if (!document.hidden) {
            syncAllTaskTimersWithRealTime();
        }
    }, 10000);

    // تحديث التايمرات عند النقر على أي مكان في الصفحة
    document.addEventListener("click", function () {
        if (!document.hidden) {
            setTimeout(() => {
                syncAllTaskTimersWithRealTime();
            }, 100);
        }
    });
}

function syncAllTaskTimersWithRealTime() {
    // ✅ تحديث جميع التايمرات النشطة بالوقت الفعلي
    const inProgressCards = document.querySelectorAll(
        '.kanban-card[data-status="in_progress"]'
    );

    inProgressCards.forEach((card) => {
        const taskId = card.getAttribute("data-task-id");
        const startedAtTimestamp = card.getAttribute("data-started-at");
        const initialMinutes = parseInt(
            card.getAttribute("data-initial-minutes") || "0"
        );

        if (
            taskId &&
            startedAtTimestamp &&
            startedAtTimestamp !== "null" &&
            startedAtTimestamp !== ""
        ) {
            // ✅ حساب الوقت الفعلي من البداية
            const elapsedSeconds = calculateElapsedSeconds(startedAtTimestamp);
            const totalSeconds = initialMinutes * 60 + elapsedSeconds;

            // تحديث التايمر المحلي
            taskTimers[taskId] = totalSeconds;
            updateTaskTimerDisplay(taskId, totalSeconds);
        }
    });
}

// 🚀 إدارة التايمرات
function startTaskTimer(taskId) {
    const taskElement = document.querySelector(
        `.kanban-card[data-task-id="${taskId}"]`
    );
    if (!taskElement) return;

    // الحصول على البيانات المحفوظة
    const initialMinutes = parseInt(taskElement.dataset.initialMinutes || "0");
    let totalSeconds = initialMinutes * 60;

    // ✅ التأكد من وجود timestamp صحيح عند بدء المهمة
    let startedAt = taskElement.dataset.startedAt;
    if (!startedAt || startedAt === "null" || startedAt === "") {
        // إذا لم يوجد timestamp، نضعه الآن
        startedAt = new Date().getTime().toString();
        taskElement.dataset.startedAt = startedAt;
        taskElement.setAttribute("data-started-at", startedAt);
    }

    const elapsedSeconds = calculateElapsedSeconds(startedAt);
    totalSeconds += elapsedSeconds;

    // حفظ الوقت وبدء التايمر
    taskTimers[taskId] = totalSeconds;
    updateTaskTimerDisplay(taskId, totalSeconds);

    // بدء العد التصاعدي
    if (timerIntervals[taskId]) {
        clearInterval(timerIntervals[taskId]);
    }

    timerIntervals[taskId] = setInterval(() => {
        taskTimers[taskId]++;
        updateTaskTimerDisplay(taskId, taskTimers[taskId]);
    }, 1000);
}

function stopTaskTimer(taskId) {
    if (timerIntervals[taskId]) {
        clearInterval(timerIntervals[taskId]);
        delete timerIntervals[taskId];
    }
}

function stopAllTimers() {
    Object.keys(timerIntervals).forEach((taskId) => {
        clearInterval(timerIntervals[taskId]);
    });
    timerIntervals = {};
    taskTimers = {};
}

function updateTaskTimerDisplay(taskId, seconds) {
    const timerElement = document.querySelector(`#kanban-timer-${taskId}`);
    if (timerElement) {
        timerElement.textContent = formatTime(seconds);
    }
}

// 🔄 تحديث حالة المهمة مع التايمر
function updateTaskStatus(taskId, newStatus) {
    const taskElement = document.querySelector(
        `.kanban-card[data-task-id="${taskId}"]`
    );
    if (!taskElement) return;

    if (newStatus === "in_progress") {
        // بدء التايمر عند بدء العمل
        taskElement.dataset.startedAt = new Date().getTime();
        startTaskTimer(taskId);
        taskElement.classList.add("task-in-progress");
    } else {
        // إيقاف التايمر عند التوقف/الانتهاء
        stopTaskTimer(taskId);
        taskElement.classList.remove("task-in-progress");

        if (newStatus === "completed") {
            taskElement.classList.add("task-completed");
        } else if (newStatus === "paused") {
            taskElement.classList.add("task-paused");
        }
    }

    taskElement.dataset.status = newStatus;
}

window.initializeKanbanSystem = initializeKanbanSystem;
window.applyUserColors = applyUserColors;
window.loadTasksIntoKanban = loadTasksIntoKanban;
window.switchToTableView = switchToTableView;
window.switchToKanbanView = switchToKanbanView;
window.switchToCalendarView = switchToCalendarView;
window.currentView = () => currentView;
window.startTaskTimer = startTaskTimer;
window.stopTaskTimer = stopTaskTimer;
window.stopAllTimers = stopAllTimers;
window.updateTaskStatus = updateTaskStatus;
window.formatTime = formatTime;
window.taskTimers = taskTimers;
