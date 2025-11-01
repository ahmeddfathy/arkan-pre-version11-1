/**
 * Tasks Index Calendar View
 * Displays tasks based on their due dates in a calendar format for index page
 */
class TasksIndexCalendar {
    constructor() {
        this.currentDate = new Date();
        this.tasks = [];
        this.filteredTasks = [];
        this.init();
    }

    init() {
        this.bindEvents();
        this.buildCalendar();
    }

    bindEvents() {
        // Calendar navigation
        const prevBtn = document.getElementById("prevMonthIndex");
        const nextBtn = document.getElementById("nextMonthIndex");
        const todayBtn = document.getElementById("todayBtnIndex");

        if (prevBtn) {
            prevBtn.addEventListener("click", () => {
                this.currentDate.setMonth(this.currentDate.getMonth() - 1);
                this.buildCalendar();
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener("click", () => {
                this.currentDate.setMonth(this.currentDate.getMonth() + 1);
                this.buildCalendar();
            });
        }

        if (todayBtn) {
            todayBtn.addEventListener("click", () => {
                this.currentDate = new Date();
                this.buildCalendar();
            });
        }

        // Back to Table button
        const backToTableBtn = document.getElementById("backToTableBtnIndex");
        if (backToTableBtn) {
            backToTableBtn.addEventListener("click", () => {
                // Switch back to table view
                const tableViewBtn = document.getElementById("tableViewBtn");
                if (tableViewBtn) {
                    tableViewBtn.click();
                }
            });
        }
    }

    loadTasks() {
        // Get all tasks from table rows
        this.tasks = [];
        const tableRows = document.querySelectorAll(
            "#tasksTable tbody tr[data-project-id]"
        );

        tableRows.forEach((row) => {
            // Skip rows without task data or empty rows
            if (!row.dataset.projectId && !row.querySelector("td[colspan]")) {
                return;
            }

            // Extract task data from row
            const taskNameElement = row.querySelector("td:first-child h6");
            const projectElement = row.querySelector("td:nth-child(2)");
            const serviceElement = row.querySelector("td:nth-child(3)");
            const createdByElement = row.querySelector(
                "td:nth-child(4) .badge"
            );
            const statusElement = row.querySelector("td:nth-child(9) .badge");
            const dueDateElement = row.querySelector("td:nth-child(10)");
            const viewButton = row.querySelector("button.view-task");

            if (!taskNameElement || !dueDateElement) {
                return;
            }

            const taskData = {
                id: viewButton?.dataset.id || "",
                taskUserId: viewButton?.dataset.taskUserId || "",
                name: taskNameElement.textContent.trim(),
                projectId: row.dataset.projectId,
                projectName: projectElement?.textContent.trim() || "غير محدد",
                projectStatus: row.dataset.projectStatus || "", // ✅ حالة المشروع
                serviceName: serviceElement?.textContent.trim() || "غير محدد",
                status: row.dataset.status || "new",
                dueDate: dueDateElement?.textContent.trim(),
                isTemplate: row.dataset.isTemplate === "true",
                createdBy: createdByElement?.textContent.trim() || "غير محدد",
                createdById: row.dataset.createdBy || "",
                isMyCreated: row.classList.contains("my-created-task"),
            };

            // Only include tasks with valid due dates
            if (taskData.dueDate && taskData.dueDate !== "غير محدد") {
                try {
                    taskData.dueDateObj = new Date(taskData.dueDate);
                    if (!isNaN(taskData.dueDateObj.getTime())) {
                        // Ensure taskUserId is properly set for sidebar
                        if (
                            !taskData.taskUserId ||
                            taskData.taskUserId === "undefined"
                        ) {
                            taskData.taskUserId = taskData.id;
                        }
                        this.tasks.push(taskData);
                    }
                } catch (e) {
                    console.warn("Invalid date:", taskData.dueDate);
                }
            } else {
            }
        });

        this.applyFilters();
    }

    applyFilters() {
        const projectFilter = document.getElementById("projectFilter");
        const serviceFilter = document.getElementById("serviceFilter");
        const statusFilter = document.getElementById("statusFilter");
        const createdByFilter = document.getElementById("createdByFilter");
        const searchFilter = document.getElementById("searchInput");

        const projectValue = projectFilter ? projectFilter.value : "";
        const serviceValue = serviceFilter ? serviceFilter.value : "";
        const statusValue = statusFilter ? statusFilter.value : "";
        const createdByValue = createdByFilter ? createdByFilter.value : "";
        const searchValue = searchFilter
            ? searchFilter.value.toLowerCase()
            : "";

        this.filteredTasks = this.tasks.filter((task) => {
            let matches = true;

            if (projectValue && task.projectId !== projectValue) {
                matches = false;
            }

            if (serviceValue && !task.serviceName.includes(serviceValue)) {
                matches = false;
            }

            if (statusValue && task.status !== statusValue) {
                matches = false;
            }

            if (createdByValue && task.createdById !== createdByValue) {
                matches = false;
            }

            if (searchValue && !task.name.toLowerCase().includes(searchValue)) {
                matches = false;
            }

            return matches;
        });

        this.buildCalendar();
    }

    buildCalendar() {
        const year = this.currentDate.getFullYear();
        const month = this.currentDate.getMonth();

        // Update header
        const monthNames = [
            "يناير",
            "فبراير",
            "مارس",
            "أبريل",
            "مايو",
            "يونيو",
            "يوليو",
            "أغسطس",
            "سبتمبر",
            "أكتوبر",
            "نوفمبر",
            "ديسمبر",
        ];

        const headerElement = document.getElementById("currentMonthYearIndex");
        if (headerElement) {
            headerElement.textContent = `${monthNames[month]} ${year}`;
        }

        // Calculate calendar days
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const firstCalendarDay = new Date(firstDay);
        firstCalendarDay.setDate(
            firstCalendarDay.getDate() - firstDay.getDay()
        );

        const calendarDays = document.getElementById("calendarDaysIndex");
        if (!calendarDays) return;

        calendarDays.innerHTML = "";

        // Generate 42 days (6 weeks)
        for (let i = 0; i < 42; i++) {
            const currentDay = new Date(firstCalendarDay);
            currentDay.setDate(firstCalendarDay.getDate() + i);

            const dayElement = this.createDayElement(currentDay, month);
            calendarDays.appendChild(dayElement);
        }
    }

    createDayElement(date, currentMonth) {
        const dayDiv = document.createElement("div");
        dayDiv.className = "calendar-day";

        // Add classes for styling
        if (date.getMonth() !== currentMonth) {
            dayDiv.classList.add("other-month");
        }

        const today = new Date();
        if (date.toDateString() === today.toDateString()) {
            dayDiv.classList.add("today");
        }

        // Day number
        const dayNumber = document.createElement("div");
        dayNumber.className = "calendar-day-number";
        dayNumber.textContent = date.getDate();
        dayDiv.appendChild(dayNumber);

        // Tasks container
        const tasksContainer = document.createElement("div");
        tasksContainer.className = "calendar-tasks";

        // Find tasks for this date
        const dateString = date.toISOString().split("T")[0];
        const dayTasks = this.filteredTasks.filter((task) => {
            return task.dueDateObj.toISOString().split("T")[0] === dateString;
        });

        // Add tasks (limit to show max 3, then show "more" indicator)
        const maxVisibleTasks = 3;
        dayTasks.slice(0, maxVisibleTasks).forEach((task) => {
            const taskElement = this.createTaskElement(task);
            tasksContainer.appendChild(taskElement);
        });

        // Show overflow indicator if there are more tasks
        if (dayTasks.length > maxVisibleTasks) {
            const overflowDiv = document.createElement("div");
            overflowDiv.className = "calendar-task-overflow";
            overflowDiv.textContent = `+${
                dayTasks.length - maxVisibleTasks
            } أخرى`;
            overflowDiv.style.cursor = "pointer";
            overflowDiv.onclick = () => this.showDayTasks(date, dayTasks);
            tasksContainer.appendChild(overflowDiv);
        }

        dayDiv.appendChild(tasksContainer);
        return dayDiv;
    }

    createTaskElement(task) {
        const taskDiv = document.createElement("div");
        taskDiv.className = `calendar-task status-${task.status}`;

        if (task.isTemplate) {
            taskDiv.classList.add("template-task");
        }

        // ✅ إضافة class خاص للمهام المرتبطة بمشاريع ملغاة
        if (task.projectStatus === "ملغي") {
            taskDiv.classList.add("cancelled-project-task");
        }

        // Add special styling for tasks created by current user
        if (task.isMyCreated) {
            taskDiv.style.borderLeft = "3px solid #3b82f6";
        }

        taskDiv.textContent = task.name;
        // ✅ إضافة حالة المشروع في tooltip
        const projectStatusText =
            task.projectStatus === "ملغي" ? " [مشروع ملغى]" : "";
        taskDiv.title = `${task.name} - ${task.projectName}${projectStatusText} (${task.serviceName}) - منشئ: ${task.createdBy}`;

        // Click to open task details
        taskDiv.onclick = (e) => {
            e.stopPropagation();

            // Ensure task has valid IDs before opening sidebar
            const taskToOpen = {
                ...task,
                taskUserId: task.taskUserId || task.id,
                id: task.id,
            };

            this.openTaskDetails(taskToOpen);
        };

        return taskDiv;
    }

    openTaskDetails(task) {
        // Use existing task sidebar functionality - same pattern as modal-handlers.js
        if (typeof openTaskSidebar === "function") {
            const taskType = task.isTemplate ? "template" : "regular";
            const taskUserId = task.taskUserId || task.id;

            console.log(
                "🔍 Index Calendar view task clicked - Opening Sidebar:",
                {
                    taskId: task.id,
                    taskUserId: taskUserId,
                    taskType: taskType,
                    isTemplate: task.isTemplate,
                }
            );

            openTaskSidebar(taskType, taskUserId);
        } else {
            // Fallback to showing task info
            alert(
                `مهمة: ${task.name}\nالمشروع: ${task.projectName}\nالخدمة: ${
                    task.serviceName
                }\nالحالة: ${this.getStatusText(
                    task.status
                )}\nالموعد النهائي: ${task.dueDate}\nمنشئ: ${task.createdBy}`
            );
        }
    }

    showDayTasks(date, tasks) {
        const dateString = date.toLocaleDateString("ar-EG", {
            weekday: "long",
            year: "numeric",
            month: "long",
            day: "numeric",
        });

        let content = `<h6 class="mb-3">مهام يوم ${dateString}</h6>`;

        tasks.forEach((task) => {
            const statusText = this.getStatusText(task.status);
            const statusClass = `status-${task.status}`;
            const taskUserId = task.taskUserId || task.id;
            const taskType = task.isTemplate ? "template" : "regular";
            // ✅ التحقق من حالة المشروع
            const isCancelledProject = task.projectStatus === "ملغي";

            content += `
                <div class="border-bottom pb-2 mb-2">
                    <div class="d-flex justify-content-between align-items-start">
                        <div style="cursor: pointer;" onclick="openTaskSidebar('${taskType}', '${taskUserId}'); Swal.close();">
                            <strong${
                                isCancelledProject
                                    ? ' style="text-decoration: line-through; opacity: 0.7;"'
                                    : ""
                            }>${task.name}</strong>
                            ${
                                task.isTemplate
                                    ? '<span class="badge bg-info ms-1">قالب</span>'
                                    : ""
                            }
                            ${
                                task.isMyCreated
                                    ? '<span class="badge bg-primary ms-1">مهمتي</span>'
                                    : ""
                            }
                            ${
                                isCancelledProject
                                    ? '<span class="badge bg-secondary ms-1" title="المشروع ملغى"><i class="fas fa-ban"></i> مشروع ملغى</span>'
                                    : ""
                            }
                            <br>
                            <small class="text-muted">${task.projectName} - ${
                task.serviceName
            }</small>
                            <br>
                            <small class="text-muted">منشئ: ${
                                task.createdBy
                            }</small>
                            <br>
                            <small class="text-primary"><i class="fas fa-eye me-1"></i>انقر لعرض التفاصيل</small>
                        </div>
                        <span class="badge bg-${this.getStatusBootstrapClass(
                            task.status
                        )}">${statusText}</span>
                    </div>
                </div>
            `;
        });

        // Show in SweetAlert
        if (typeof Swal !== "undefined") {
            Swal.fire({
                title: `مهام اليوم`,
                html: content,
                width: "600px",
                showCloseButton: true,
                showConfirmButton: false,
            });
        }
    }

    getStatusText(status) {
        const statusTexts = {
            new: "جديدة",
            in_progress: "قيد التنفيذ",
            paused: "متوقفة",
            completed: "مكتملة",
            cancelled: "ملغاة",
        };
        return statusTexts[status] || status;
    }

    getStatusBootstrapClass(status) {
        const statusClasses = {
            new: "info",
            in_progress: "primary",
            paused: "warning",
            completed: "success",
            cancelled: "danger",
        };
        return statusClasses[status] || "secondary";
    }

    refresh() {
        this.loadTasks();
    }
}

// Calendar initialization and management
let tasksIndexCalendar;

function initializeTasksIndexCalendar() {
    tasksIndexCalendar = new TasksIndexCalendar();

    // Load tasks initially
    tasksIndexCalendar.loadTasks();

    // Refresh calendar when filters change
    [
        "projectFilter",
        "serviceFilter",
        "statusFilter",
        "createdByFilter",
        "searchInput",
    ].forEach((filterId) => {
        const element = document.getElementById(filterId);
        if (element) {
            element.addEventListener("change", () =>
                tasksIndexCalendar.applyFilters()
            );
            element.addEventListener("input", () =>
                tasksIndexCalendar.applyFilters()
            );
        }
    });

    // Make calendar globally accessible for view switching
    window.tasksIndexCalendar = tasksIndexCalendar;

    console.log("✅ Tasks Index Calendar initialized successfully");
}

// Initialize when DOM is ready
document.addEventListener("DOMContentLoaded", function () {
    initializeTasksIndexCalendar();
});

// Export for global access
window.TasksIndexCalendar = TasksIndexCalendar;
window.initializeTasksIndexCalendar = initializeTasksIndexCalendar;
