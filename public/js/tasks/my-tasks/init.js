/**
 * My Tasks Initialization Script
 * Handles view preferences, cleanup, and initialization
 */

(function () {
    "use strict";

    // 🚀 Apply view preference IMMEDIATELY before content renders
    (function () {
        const savedPreference =
            localStorage.getItem("myTasksViewPreference") || "table";
        const isKanban = savedPreference === "kanban";
        const isCalendar = savedPreference === "calendar";

        if (isKanban) {
            // Set Kanban as default BEFORE content loads
            document.addEventListener("DOMContentLoaded", function () {
                // Update buttons immediately
                const tableBtn = document.getElementById("myTasksTableViewBtn");
                const kanbanBtn = document.getElementById(
                    "myTasksKanbanViewBtn"
                );
                const calendarBtn = document.getElementById(
                    "myTasksCalendarViewBtn"
                );
                if (tableBtn) tableBtn.classList.remove("active");
                if (kanbanBtn) kanbanBtn.classList.add("active");
                if (calendarBtn) calendarBtn.classList.remove("active");

                // Show/hide views immediately
                const tableView = document.getElementById("myTasksTableView");
                const kanbanView = document.getElementById("myTasksKanbanView");
                const calendarView = document.getElementById(
                    "myTasksCalendarView"
                );
                const timerContainer = document.getElementById(
                    "myTasksTotalTimerContainer"
                );

                if (tableView) tableView.style.display = "none";
                if (kanbanView) kanbanView.style.display = "block";
                if (calendarView) calendarView.style.display = "none";
                if (timerContainer) timerContainer.style.display = "block";

                // 🚀 Mark as loaded to enable smooth transitions
                setTimeout(() => {
                    const cardBody = document.querySelector(".card-body");
                    if (cardBody) {
                        cardBody.classList.remove("my-tasks-loading");
                        cardBody.classList.add("my-tasks-loaded");
                    }
                }, 100);

                console.log(
                    "✅ Applied Kanban preference immediately on DOM ready"
                );
            });
        } else if (isCalendar) {
            // Set Calendar as default BEFORE content loads
            document.addEventListener("DOMContentLoaded", function () {
                // Update buttons immediately
                const tableBtn = document.getElementById("myTasksTableViewBtn");
                const kanbanBtn = document.getElementById(
                    "myTasksKanbanViewBtn"
                );
                const calendarBtn = document.getElementById(
                    "myTasksCalendarViewBtn"
                );
                if (tableBtn) tableBtn.classList.remove("active");
                if (kanbanBtn) kanbanBtn.classList.remove("active");
                if (calendarBtn) calendarBtn.classList.add("active");

                // Show/hide views immediately
                const tableView = document.getElementById("myTasksTableView");
                const kanbanView = document.getElementById("myTasksKanbanView");
                const calendarView = document.getElementById(
                    "myTasksCalendarView"
                );
                const timerContainer = document.getElementById(
                    "myTasksTotalTimerContainer"
                );

                if (tableView) tableView.style.display = "none";
                if (kanbanView) kanbanView.style.display = "none";
                if (calendarView) calendarView.style.display = "block";
                if (timerContainer) timerContainer.style.display = "none";

                // 🚀 Mark as loaded to enable smooth transitions
                setTimeout(() => {
                    const cardBody = document.querySelector(".card-body");
                    if (cardBody) {
                        cardBody.classList.remove("my-tasks-loading");
                        cardBody.classList.add("my-tasks-loaded");
                    }
                }, 100);

                console.log(
                    "✅ Applied Calendar preference immediately on DOM ready"
                );
            });
        } else {
            // Table view is default - mark as loaded after short delay
            document.addEventListener("DOMContentLoaded", function () {
                setTimeout(() => {
                    const cardBody = document.querySelector(".card-body");
                    if (cardBody) {
                        cardBody.classList.remove("my-tasks-loading");
                        cardBody.classList.add("my-tasks-loaded");
                    }
                }, 200);
            });
        }
    })();

    // Cleanup old functions
    const oldFunctionsToRemove = [
        "initializeTimers",
        "loadTimeLogs",
        "startTimer",
        "pauseTimer",
        "loadTaskTimeLogs",
        "taskTimers",
        "intervals",
    ];

    oldFunctionsToRemove.forEach((funcName) => {
        if (typeof window[funcName] !== "undefined") {
            console.warn(`⚠️ Removing old cached function: ${funcName}`);
            delete window[funcName];
        }
    });

    // Cleanup old intervals
    ["taskTimerInterval", "timerInterval", "intervals"].forEach(
        (intervalName) => {
            if (window[intervalName]) {
                if (typeof window[intervalName] === "object") {
                    Object.values(window[intervalName]).forEach((interval) => {
                        if (interval && typeof interval === "number") {
                            clearInterval(interval);
                        }
                    });
                } else if (typeof window[intervalName] === "number") {
                    clearInterval(window[intervalName]);
                }
                delete window[intervalName];
            }
        }
    );

    // Block old AJAX calls to /logs endpoints
    if (typeof $ !== "undefined" && $.ajax) {
        const originalAjax = $.ajax;
        $.ajax = function (options) {
            if (options && options.url && options.url.includes("/logs")) {
                console.warn("🛡️ Blocked old task logs call to:", options.url);
                return {
                    done: function () {
                        return this;
                    },
                    fail: function () {
                        return this;
                    },
                    always: function () {
                        return this;
                    },
                };
            }
            return originalAjax.apply(this, arguments);
        };
    }

    // Set global flag
    window.NEW_MY_TASKS_SYSTEM = true;

    // ⚡ Performance Optimization: Detect scrolling and disable animations
    (function () {
        let scrollTimer;
        const body = document.body;

        window.addEventListener(
            "scroll",
            function () {
                clearTimeout(scrollTimer);

                // Add scrolling class to disable animations
                if (!body.classList.contains("scrolling")) {
                    body.classList.add("scrolling");
                }

                // Remove scrolling class after scroll stops
                scrollTimer = setTimeout(function () {
                    body.classList.remove("scrolling");
                }, 150);
            },
            {
                passive: true,
            }
        );
    })();

    // View switching functionality
    document.addEventListener("DOMContentLoaded", function () {
        const tableViewBtn = document.getElementById("myTasksTableViewBtn");
        const kanbanViewBtn = document.getElementById("myTasksKanbanViewBtn");
        const calendarViewBtn = document.getElementById(
            "myTasksCalendarViewBtn"
        );

        const tableView = document.getElementById("myTasksTableView");
        const kanbanView = document.getElementById("myTasksKanbanView");
        const calendarView = document.getElementById("myTasksCalendarView");
        const timerContainer = document.getElementById(
            "myTasksTotalTimerContainer"
        );

        function switchToView(viewType) {
            // Remove active class from all buttons
            [tableViewBtn, kanbanViewBtn, calendarViewBtn].forEach((btn) => {
                if (btn) btn.classList.remove("active");
            });

            // Hide all views
            if (tableView) tableView.style.display = "none";
            if (kanbanView) kanbanView.style.display = "none";
            if (calendarView) calendarView.style.display = "none";

            // Show/hide timer container
            if (timerContainer) {
                timerContainer.style.display =
                    viewType === "kanban" ? "block" : "none";
            }

            // Show selected view and activate button
            switch (viewType) {
                case "table":
                    if (tableView) tableView.style.display = "block";
                    if (tableViewBtn) tableViewBtn.classList.add("active");
                    break;
                case "kanban":
                    if (kanbanView) kanbanView.style.display = "block";
                    if (kanbanViewBtn) kanbanViewBtn.classList.add("active");
                    break;
                case "calendar":
                    if (calendarView) calendarView.style.display = "block";
                    if (calendarViewBtn)
                        calendarViewBtn.classList.add("active");
                    // Refresh calendar when switching to it
                    if (
                        typeof initializeMyTasksCalendar === "function" &&
                        !window.myTasksCalendar
                    ) {
                        initializeMyTasksCalendar();
                    } else if (window.myTasksCalendar) {
                        window.myTasksCalendar.refresh();
                    }
                    break;
            }

            // Save preference
            localStorage.setItem("myTasksViewPreference", viewType);
            window.myTasksCurrentView = viewType;

            console.log(`✅ Switched to ${viewType} view`);
        }

        // Add event listeners
        if (tableViewBtn) {
            tableViewBtn.addEventListener("click", () => switchToView("table"));
        }

        if (kanbanViewBtn) {
            kanbanViewBtn.addEventListener("click", () =>
                switchToView("kanban")
            );
        }

        if (calendarViewBtn) {
            calendarViewBtn.addEventListener("click", () =>
                switchToView("calendar")
            );
        }

        // Apply saved preference or default to table
        const savedPreference =
            localStorage.getItem("myTasksViewPreference") || "table";

        // Small delay to ensure all elements are loaded
        setTimeout(() => {
            switchToView(savedPreference);
        }, 100);
    });

    // Handle kanban card click events for task sidebar
    document.addEventListener("click", function (e) {
        if (e.target.closest(".kanban-card .view-task")) {
            e.preventDefault();
            e.stopPropagation();

            const button = e.target.closest(".view-task");
            const taskId = button.getAttribute("data-id");
            const taskUserId =
                button.getAttribute("data-task-user-id") || taskId;
            const isTemplate = button.getAttribute("data-is-template");

            console.log("🔍 Opening task sidebar:", {
                taskId: taskId,
                taskUserId: taskUserId,
                isTemplate: isTemplate,
            });

            // استخدم نفس الطريقة الموجودة في صفحة المهام الرئيسية
            const taskType =
                isTemplate === "true" || isTemplate === true
                    ? "template"
                    : "regular";

            // للمهام العادية: استخدم Task ID
            // لمهام القوالب: استخدم TaskUser ID
            const targetId = taskType === "regular" ? taskId : taskUserId;

            if (typeof openTaskSidebar === "function") {
                openTaskSidebar(taskType, targetId);
            } else {
                console.error("❌ openTaskSidebar function not found");
                // Fallback: محاولة فتح السايد بار بطريقة أخرى
                if (typeof window.openTaskSidebar === "function") {
                    window.openTaskSidebar(taskType, targetId);
                } else {
                    console.error(
                        "❌ window.openTaskSidebar function not found either"
                    );
                }
            }
        }
    });
})();
