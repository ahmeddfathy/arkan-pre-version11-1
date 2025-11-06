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
    // Note: startTimer and pauseTimer are part of window API or may be needed by other scripts
    const oldFunctionsToRemove = [
        "initializeTimers",
        "loadTimeLogs",
        "loadTaskTimeLogs",
        "taskTimers",
        "intervals",
    ];

    oldFunctionsToRemove.forEach((funcName) => {
        if (typeof window[funcName] !== "undefined") {
            try {
                // Check if property is configurable before deleting
                const descriptor = Object.getOwnPropertyDescriptor(
                    window,
                    funcName
                );
                if (descriptor && descriptor.configurable) {
                    console.warn(
                        `⚠️ Removing old cached function: ${funcName}`
                    );
                    delete window[funcName];
                } else {
                    // If not configurable, try to set it to undefined instead
                    try {
                        window[funcName] = undefined;
                        console.warn(
                            `⚠️ Set ${funcName} to undefined (non-configurable)`
                        );
                    } catch (e2) {
                        console.warn(
                            `⚠️ Could not modify ${funcName}:`,
                            e2.message
                        );
                    }
                }
            } catch (e) {
                console.warn(`⚠️ Could not delete ${funcName}:`, e.message);
            }
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
                    console.log("📅 Switching to calendar view");
                    if (calendarView) {
                        calendarView.style.display = "block";
                        console.log("✅ Calendar view shown");
                    } else {
                        console.error("❌ Calendar view element not found");
                        return; // Exit if view not found
                    }
                    if (calendarViewBtn) {
                        calendarViewBtn.classList.add("active");
                    }
                    // Initialize or refresh calendar when switching to it
                    // Small delay to ensure DOM is ready
                    setTimeout(() => {
                        console.log("🔍 Checking calendar initialization...");
                        console.log(
                            "initializeMyTasksCalendar exists:",
                            typeof initializeMyTasksCalendar
                        );
                        console.log(
                            "window.myTasksCalendar exists:",
                            !!window.myTasksCalendar
                        );

                        if (typeof initializeMyTasksCalendar === "function") {
                            if (!window.myTasksCalendar) {
                                // Initialize calendar for the first time
                                console.log(
                                    "🚀 Initializing calendar for the first time"
                                );
                                try {
                                    initializeMyTasksCalendar();
                                    console.log(
                                        "✅ Calendar initialized successfully"
                                    );
                                } catch (e) {
                                    console.error(
                                        "❌ Error initializing calendar:",
                                        e
                                    );
                                }
                            } else {
                                // Refresh existing calendar
                                console.log("🔄 Refreshing existing calendar");
                                try {
                                    window.myTasksCalendar.refresh();
                                    console.log(
                                        "✅ Calendar refreshed successfully"
                                    );
                                } catch (e) {
                                    console.error(
                                        "❌ Error refreshing calendar:",
                                        e
                                    );
                                }
                            }
                        } else {
                            console.warn(
                                "⚠️ initializeMyTasksCalendar function not found. Available functions:",
                                Object.keys(window).filter((k) =>
                                    k.includes("Calendar")
                                )
                            );
                        }
                    }, 100);
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
            calendarViewBtn.addEventListener("click", function (e) {
                e.preventDefault();
                e.stopPropagation();
                console.log("📅 Calendar button clicked");
                switchToView("calendar");
            });
        } else {
            console.error("❌ Calendar view button not found");
        }

        // Apply saved preference or default to table
        const savedPreference =
            localStorage.getItem("myTasksViewPreference") || "table";

        // Small delay to ensure all elements are loaded
        setTimeout(() => {
            switchToView(savedPreference);
        }, 100);
    });

    // ✅ تهيئة Page Visibility Handler لضمان عمل التايمر عند تبديل التبويب
    document.addEventListener("DOMContentLoaded", function () {
        setTimeout(() => {
            if (
                window.MyTasksTimers &&
                typeof window.MyTasksTimers.initializePageVisibilityHandler ===
                    "function"
            ) {
                window.MyTasksTimers.initializePageVisibilityHandler();
                console.log("✅ Page Visibility Handler initialized for My Tasks");
            } else {
                console.warn(
                    "⚠️ MyTasksTimers.initializePageVisibilityHandler not available"
                );
            }
        }, 500);
    });

    // Handle kanban card click events for task sidebar
    document.addEventListener("click", function (e) {
        if (e.target.closest(".kanban-card .view-task")) {
            e.preventDefault();
            e.stopPropagation();

            const button = e.target.closest(".view-task");
            const taskId = button.getAttribute("data-id");
            const rawTaskUserId = button.getAttribute("data-task-user-id");
            let isTemplateAttr = button.getAttribute("data-is-template");

            if (isTemplateAttr === null) {
                const parent = button.closest(".kanban-card, .my-kanban-card");
                if (parent) {
                    isTemplateAttr = parent.getAttribute("data-is-template");
                }
            }

            const isTemplate =
                isTemplateAttr === "true" || isTemplateAttr === true;
            const taskType = isTemplate ? "template" : "regular";

            let targetId;
            if (taskType === "template") {
                targetId = rawTaskUserId || taskId;
            } else {
                const parsedTaskUserId =
                    rawTaskUserId && !isNaN(parseInt(rawTaskUserId, 10))
                        ? parseInt(rawTaskUserId, 10)
                        : null;
                targetId = parsedTaskUserId || taskId;
            }

            console.log("🔍 Opening task sidebar:", {
                taskId: taskId,
                taskUserId: rawTaskUserId,
                resolvedTargetId: targetId,
                isTemplate: isTemplateAttr,
            });

            if (typeof openTaskSidebar === "function") {
                openTaskSidebar(taskType, targetId);
            } else {
                console.error("❌ openTaskSidebar function not found");
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
