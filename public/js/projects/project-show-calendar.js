class ProjectShowCalendar {
    constructor(projectId) {
        this.projectId = projectId;
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
        const prevBtn = document.getElementById('prevMonthProjectShow');
        const nextBtn = document.getElementById('nextMonthProjectShow');
        const todayBtn = document.getElementById('todayBtnProjectShow');

        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                this.currentDate.setMonth(this.currentDate.getMonth() - 1);
                this.buildCalendar();
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                this.currentDate.setMonth(this.currentDate.getMonth() + 1);
                this.buildCalendar();
            });
        }

        if (todayBtn) {
            todayBtn.addEventListener('click', () => {
                this.currentDate = new Date();
                this.buildCalendar();
            });
        }

        // Back to Kanban button
        const backToKanbanBtn = document.getElementById('backToKanbanBtn');
        if (backToKanbanBtn) {
            backToKanbanBtn.addEventListener('click', () => {
                // Remove fullwidth class immediately
                document.body.classList.remove('project-calendar-fullwidth');
                // Switch back to kanban view
                const kanbanViewBtn = document.getElementById('kanbanViewBtnShow');
                if (kanbanViewBtn) {
                    kanbanViewBtn.click();
                }
            });
        }
    }

    loadTasks() {
        // Get all tasks from kanban board
        this.tasks = [];
        const kanbanCards = document.querySelectorAll('.kanban-task');

        kanbanCards.forEach(card => {
            // Extract task data from kanban card - try multiple selectors for task name
            let taskNameElement = card.querySelector('h6');

            // If h6 not found, try other potential selectors
            if (!taskNameElement) {
                taskNameElement = card.querySelector('.kanban-task-title, .task-title, h5, h4');
            }

            const statusElement = card.closest('.kanban-tasks');
            const userElement = card.querySelector('.text-dark.fw-semibold');
            const serviceElement = card.querySelector('.small.text-muted');
            const pointsElement = card.querySelector('.badge');

            if (!taskNameElement) {
                console.warn('Task name element not found for card:', {
                    card: card,
                    innerHTML: card.innerHTML.substring(0, 200),
                    classList: card.className
                });
                return;
            }

            // Check if this is a template task or regular task
            const isTemplate = card.dataset.taskType === 'template_task' || card.dataset.isTemplate === 'true';

            // Extract due date from deadline sections - different approach for template vs regular tasks
            let dueDate = null;

            // For template tasks, look for deadline-info sections
            if (isTemplate) {
                const deadlineInfo = card.querySelector('.deadline-info');
                if (deadlineInfo) {
                    const strongElement = deadlineInfo.querySelector('strong');
                    if (strongElement) {
                        const dateText = strongElement.textContent || '';
                        const dateMatch = dateText.match(/(\d{1,2}\/\d{1,2}\/\d{4})/);
                        if (dateMatch) {
                            const [day, month, year] = dateMatch[0].split('/');
                            dueDate = `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
                        }
                    }
                }
            } else {
                // For regular tasks, use the existing logic
                const deadlineSelectors = [
                    '.text-danger.fw-bold',     // Overdue dates in red
                    '.text-warning.fw-bold',    // Due soon dates in yellow
                    '.text-primary.fw-bold',    // Normal dates in blue
                    '.text-success.fw-bold',    // Completed dates in green
                    '.deadline-info strong',    // Regular task deadline
                    '[style*="font-size: 12px"]', // Any deadline text
                    '.d-flex .text-danger',
                    '.d-flex .text-warning',
                    '.d-flex .text-primary',
                    '.d-flex .text-success'
                ];

                for (const selector of deadlineSelectors) {
                    const deadlineElement = card.querySelector(selector);
                    if (deadlineElement) {
                        const dateText = deadlineElement.textContent || '';

                        const datePatterns = [
                            /(\d{1,2}\/\d{1,2}\/\d{4})/,    // 23/12/2024
                            /(\d{4}-\d{1,2}-\d{1,2})/,      // 2024-12-23
                            /(\d{1,2}-\d{1,2}-\d{4})/,      // 23-12-2024
                        ];

                        for (const pattern of datePatterns) {
                            const dateMatch = dateText.match(pattern);
                            if (dateMatch) {
                                const foundDate = dateMatch[0];
                                if (foundDate.includes('/')) {
                                    const [day, month, year] = foundDate.split('/');
                                    dueDate = `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
                                } else if (foundDate.includes('-') && foundDate.length === 10) {
                                    dueDate = foundDate;
                                } else if (foundDate.includes('-')) {
                                    const [day, month, year] = foundDate.split('-');
                                    dueDate = `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
                                }
                                break;
                            }
                        }

                        if (dueDate) break;
                    }
                }
            }

            // If no due date found, skip this task for calendar view
            if (!dueDate) {
                console.log(`⚠️ No due date found for task: ${taskNameElement.textContent} (${isTemplate ? 'template' : 'regular'})`);
                return;
            }

            // Extract status from column
            let status = 'new';
            if (statusElement) {
                const columnId = statusElement.id;
                if (columnId.includes('progress')) status = 'in_progress';
                else if (columnId.includes('completed')) status = 'completed';
                else if (columnId.includes('paused')) status = 'paused';
                else if (columnId.includes('cancelled')) status = 'cancelled';
            }

            // Extract assignee from user element
            const assignees = [];
            if (userElement) {
                assignees.push(userElement.textContent.trim());
            }

            let taskId, taskUserId;

            if (isTemplate) {
                taskUserId = card.dataset.taskUserId || card.dataset.id;
                taskId = card.dataset.taskId || card.dataset.id;
            } else {
                taskId = card.dataset.taskId || card.dataset.id;
                taskUserId = card.dataset.taskUserId || card.dataset.taskId;
            }

            const taskName = taskNameElement.textContent.trim();

            const taskData = {
                id: taskId || Math.random().toString(36).substr(2, 9),
                taskUserId: taskUserId || taskId,
                name: taskName,
                status: status,
                dueDate: dueDate,
                service: serviceElement ? serviceElement.textContent.trim() : '',
                points: pointsElement ? pointsElement.textContent.trim() : '',
                assignees: assignees,
                isTemplate: isTemplate,
                projectId: this.projectId
            };

            // Debug log for empty task names
            if (!taskName || taskName.length === 0) {
                console.warn('Empty task name found:', {
                    card: card,
                    taskNameElement: taskNameElement,
                    taskNameContent: taskNameElement.textContent,
                    isTemplate: isTemplate
                });
            }

            try {
                taskData.dueDateObj = new Date(taskData.dueDate);
                if (!isNaN(taskData.dueDateObj.getTime())) {
                    this.tasks.push(taskData);
                }
            } catch (e) {
                console.warn('Invalid date:', taskData.dueDate);
            }
        });

        this.applyFilters();
    }

    applyFilters() {
        this.filteredTasks = [...this.tasks];
        this.buildCalendar();
    }

    buildCalendar() {
        const year = this.currentDate.getFullYear();
        const month = this.currentDate.getMonth();

        const monthNames = [
            'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو',
            'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'
        ];

        const headerElement = document.getElementById('currentMonthYearProjectShow');
        if (headerElement) {
            headerElement.textContent = `${monthNames[month]} ${year}`;
        }

        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const firstCalendarDay = new Date(firstDay);
        firstCalendarDay.setDate(firstCalendarDay.getDate() - firstDay.getDay());

        const calendarDays = document.getElementById('calendarDaysProjectShow');
        if (!calendarDays) return;

        calendarDays.innerHTML = '';
        for (let i = 0; i < 42; i++) {
            const currentDay = new Date(firstCalendarDay);
            currentDay.setDate(firstCalendarDay.getDate() + i);

            const dayElement = this.createDayElement(currentDay, month);
            calendarDays.appendChild(dayElement);
        }
    }

    createDayElement(date, currentMonth) {
        const dayDiv = document.createElement('div');
        dayDiv.className = 'calendar-day';

        if (date.getMonth() !== currentMonth) {
            dayDiv.classList.add('other-month');
        }

        const today = new Date();
        if (date.toDateString() === today.toDateString()) {
            dayDiv.classList.add('today');
        }
        const dayNumber = document.createElement('div');
        dayNumber.className = 'calendar-day-number';
        dayNumber.textContent = date.getDate();
        dayDiv.appendChild(dayNumber);

        const tasksContainer = document.createElement('div');
        tasksContainer.className = 'calendar-tasks';
        const dateString = date.toISOString().split('T')[0];
        const dayTasks = this.filteredTasks.filter(task => {
            return task.dueDateObj.toISOString().split('T')[0] === dateString;
        });

        const maxVisibleTasks = 3;
        dayTasks.slice(0, maxVisibleTasks).forEach(task => {
            const taskElement = this.createTaskElement(task);
            tasksContainer.appendChild(taskElement);
        });

        if (dayTasks.length > maxVisibleTasks) {
            const overflowDiv = document.createElement('div');
            overflowDiv.className = 'calendar-task-overflow';
            overflowDiv.textContent = `+${dayTasks.length - maxVisibleTasks} أخرى`;
            overflowDiv.style.cursor = 'pointer';
            overflowDiv.onclick = () => this.showDayTasks(date, dayTasks);
            tasksContainer.appendChild(overflowDiv);
        }

        dayDiv.appendChild(tasksContainer);
        return dayDiv;
    }

    createTaskElement(task) {
        const taskDiv = document.createElement('div');
        taskDiv.className = `calendar-task status-${task.status}`;

        if (task.isTemplate) {
            taskDiv.classList.add('template-task');
        }

        // Debug log for task creation
        if (!task.name || task.name.trim() === '') {
            console.warn('Creating task element with empty name:', task);
        }

        taskDiv.textContent = task.name || 'مهمة بدون اسم';
        taskDiv.title = `${task.name || 'مهمة بدون اسم'} - ${task.service} - ${task.assignees.join(', ')}`;

        taskDiv.onclick = (e) => {
            e.stopPropagation();
            this.openTaskDetails(task);
        };

        return taskDiv;
    }

    openTaskDetails(task) {
        if (typeof openTaskSidebar === 'function') {
            const taskType = task.isTemplate ? 'template' : 'regular';
            const taskUserId = task.taskUserId || task.id;

            if (!taskUserId || taskUserId === 'undefined' || taskUserId === '') {
                alert('خطأ: لا يمكن تحديد ID المهمة');
                return;
            }

            openTaskSidebar(taskType, taskUserId);
        } else {
            const assigneesText = task.assignees.length > 0 ? task.assignees.join(', ') : 'غير مخصص';
            alert(`مهمة: ${task.name}\nالخدمة: ${task.service}\nالحالة: ${this.getStatusText(task.status)}\nالموعد النهائي: ${task.dueDate}\nالمخصص لـ: ${assigneesText}`);
        }
    }

    showDayTasks(date, tasks) {
        const dateString = date.toLocaleDateString('ar-EG', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });

        let content = `<h6 class="mb-3">مهام يوم ${dateString}</h6>`;

        tasks.forEach(task => {
            const statusText = this.getStatusText(task.status);
            const statusClass = `status-${task.status}`;
            const taskUserId = task.taskUserId || task.id;
            const taskType = task.isTemplate ? 'template' : 'regular';
            const assigneesText = task.assignees.length > 0 ? task.assignees.join(', ') : 'غير مخصص';

            content += `
                <div class="border-bottom pb-2 mb-2">
                    <div class="d-flex justify-content-between align-items-start">
                        <div style="cursor: pointer;" onclick="openTaskSidebar('${taskType}', '${taskUserId}'); Swal.close();">
                            <strong>${task.name}</strong>
                            ${task.isTemplate ? '<span class="badge bg-info ms-1">قالب</span>' : ''}
                            <br>
                            <small class="text-muted">${task.service} - مخصص لـ: ${assigneesText}</small>
                            <br>
                            <small class="text-primary"><i class="fas fa-eye me-1"></i>انقر لعرض التفاصيل</small>
                        </div>
                        <span class="badge bg-${this.getStatusBootstrapClass(task.status)}">${statusText}</span>
                    </div>
                </div>
            `;
        });

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: `مهام اليوم`,
                html: content,
                width: '600px',
                showCloseButton: true,
                showConfirmButton: false
            });
        }
    }

    getStatusText(status) {
        const statusTexts = {
            'new': 'جديدة',
            'in_progress': 'قيد التنفيذ',
            'paused': 'متوقفة',
            'completed': 'مكتملة',
            'cancelled': 'ملغاة'
        };
        return statusTexts[status] || status;
    }

    getStatusBootstrapClass(status) {
        const statusClasses = {
            'new': 'info',
            'in_progress': 'primary',
            'paused': 'warning',
            'completed': 'success',
            'cancelled': 'danger'
        };
        return statusClasses[status] || 'secondary';
    }

    refresh() {
        this.loadTasks();
    }
}

let projectShowCalendar;

function initializeProjectShowCalendar() {
    const projectId = document.querySelector('meta[name="project-id"]')?.content ||
                     document.querySelector('[data-project-id]')?.dataset.projectId;

    if (!projectId) {
        return;
    }

    projectShowCalendar = new ProjectShowCalendar(projectId);
    projectShowCalendar.loadTasks();
    window.projectShowCalendar = projectShowCalendar;
}

function initializeProjectShowViewSwitching() {
    const kanbanViewBtn = document.getElementById('kanbanViewBtnShow');
    const calendarViewBtn = document.getElementById('calendarViewBtnShow');

    const kanbanContainer = document.querySelector('.kanban-container, .main-kanban-container, .kanban-main-container');
    const calendarContainer = document.getElementById('calendarViewShow');

    function switchToView(viewType) {
        [kanbanViewBtn, calendarViewBtn].forEach(btn => {
            if (btn) btn.classList.remove('active');
        });

        if (kanbanContainer) kanbanContainer.style.display = 'none';
        if (calendarContainer) calendarContainer.style.display = 'none';

        const body = document.body;
        switch (viewType) {
            case 'kanban':
                if (kanbanContainer) kanbanContainer.style.display = 'block';
                if (kanbanViewBtn) kanbanViewBtn.classList.add('active');
                body.classList.remove('project-calendar-fullwidth');
                break;
            case 'calendar':
                if (calendarContainer) calendarContainer.style.display = 'block';
                if (calendarViewBtn) calendarViewBtn.classList.add('active');
                body.classList.add('project-calendar-fullwidth');
                setTimeout(() => {
                    if (window.projectShowCalendar) {
                        window.projectShowCalendar.refresh();
                    }
                }, 300);
                break;
        }

        localStorage.setItem('projectShowView', viewType);
    }

    if (kanbanViewBtn) {
        kanbanViewBtn.addEventListener('click', () => switchToView('kanban'));
    }

    if (calendarViewBtn) {
        calendarViewBtn.addEventListener('click', () => switchToView('calendar'));
    }

    const savedView = localStorage.getItem('projectShowView') || 'kanban';
    setTimeout(() => {
        switchToView(savedView);
    }, 100);
}

document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        initializeProjectShowCalendar();
        initializeProjectShowViewSwitching();

        setTimeout(() => {
            if (window.projectShowCalendar) {
                window.projectShowCalendar.refresh();
            }
        }, 500);
    }, 1000);
});

window.ProjectShowCalendar = ProjectShowCalendar;
window.initializeProjectShowCalendar = initializeProjectShowCalendar;
window.initializeProjectShowViewSwitching = initializeProjectShowViewSwitching;
