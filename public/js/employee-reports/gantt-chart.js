
class TaskGanttChart {
    constructor(taskData, containerId, options = {}) {
        this.containerId = containerId;
        this.taskData = taskData;
        this.options = Object.assign({
            startDate: null,
            endDate: null,
            defaultViewMode: 'Day',
            viewModeButtonsSelector: '.gantt-view-modes button',
            direction: 'rtl'
        }, options);

        this.init();
    }

    init() {
        this.createCustomGanttChart();
    }

    createCustomGanttChart() {
        const container = document.getElementById(this.containerId);
        if (!container) return;

        container.innerHTML = '';

        if (!this.taskData || this.taskData.length === 0) {
            container.innerHTML = `
                <div class="custom-gantt-empty">
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle fa-2x mb-3"></i>
                        <h5>لا توجد مهام لعرضها</h5>
                        <p class="mb-0">لا توجد مهام في الفترة المحددة لعرض مخطط جانت.</p>
                    </div>
                </div>
            `;
            return;
        }

        try {
            this.buildCustomGantt(container);
        } catch (error) {
            console.error('خطأ في إنشاء مخطط جانت المخصص:', error);
            container.innerHTML = `
                <div class="alert alert-warning text-center">
                    <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                    <h5>تعذر عرض مخطط جانت</h5>
                    <p class="mb-0">حدث خطأ أثناء تحميل المخطط. سيتم عرض قائمة المهام بدلاً من ذلك.</p>
                </div>
                <div class="gantt-fallback">
                    ${this.createTasksList()}
                </div>
            `;
        }
    }

    buildCustomGantt(container) {
        const startDate = new Date(this.options.startDate);
        const endDate = new Date(this.options.endDate);

        // حساب عدد الأيام
        const timeDiff = endDate.getTime() - startDate.getTime();
        const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1;

        // إنشاء هيكل HTML للـ Gantt Chart
        const ganttHTML = `
            <div class="custom-gantt-chart">
                <div class="gantt-header">
                    <div class="gantt-task-names">
                        <div class="header-cell">المهام</div>
                    </div>
                    <div class="gantt-timeline">
                        ${this.createTimelineHeaders(startDate, daysDiff)}
                    </div>
                </div>
                <div class="gantt-body">
                    ${this.createTaskRows(startDate, daysDiff)}
                </div>
            </div>
        `;

        container.innerHTML = ganttHTML;
        this.addEventListeners();
    }

    createTimelineHeaders(startDate, daysDiff) {
        let headers = '';

        for (let i = 0; i < daysDiff; i++) {
            const currentDate = new Date(startDate);
            currentDate.setDate(startDate.getDate() + i);

            const dayName = currentDate.toLocaleDateString('ar-EG', { weekday: 'short' });
            const dayNumber = currentDate.getDate();
            const monthName = currentDate.toLocaleDateString('ar-EG', { month: 'short' });

            headers += `
                <div class="timeline-header-cell" data-date="${currentDate.toISOString().split('T')[0]}">
                    <div class="day-name">${dayName}</div>
                    <div class="day-number">${dayNumber}</div>
                    <div class="month-name">${monthName}</div>
                </div>
            `;
        }

        return headers;
    }

    createTaskRows(startDate, daysDiff) {
        let rows = '';

        this.taskData.forEach((task, index) => {
            rows += `
                <div class="gantt-row" data-task-id="${task.id || index}">
                    <div class="gantt-task-name">
                        <div class="task-name-cell">
                            <div class="task-title" title="${task.name}">
                                <i class="fas fa-${task.type === 'template' ? 'layer-group' : 'tasks'} task-icon"></i>
                                ${task.name}
                            </div>
                            <div class="task-meta">
                                <span class="task-status-badge status-${task.status}">${this.getStatusText(task.status)}</span>
                                <span class="task-time">${task.time_spent || '0h 0m'}</span>
                            </div>
                        </div>
                    </div>
                    <div class="gantt-timeline-row">
                        ${this.createTaskBar(task, startDate, daysDiff)}
                    </div>
                </div>
            `;
        });

        return rows;
    }

    createTaskBar(task, startDate, daysDiff) {
        const taskStart = new Date(task.start);
        const taskEnd = new Date(task.end);
        const rangeStart = new Date(startDate);

        // حساب موقع وعرض شريط المهمة
        const startOffset = Math.max(0, Math.floor((taskStart - rangeStart) / (1000 * 60 * 60 * 24)));
        const taskDuration = Math.max(1, Math.ceil((taskEnd - taskStart) / (1000 * 60 * 60 * 24)) + 1);
        const endOffset = Math.min(daysDiff, startOffset + taskDuration);
        const actualWidth = endOffset - startOffset;

        let timelineCells = '';

        for (let i = 0; i < daysDiff; i++) {
            if (i >= startOffset && i < endOffset) {
                const isFirst = i === startOffset;
                const isLast = i === endOffset - 1;
                const cellClass = `timeline-cell task-bar task-status-${task.status} task-type-${task.type || 'regular'}`;

                timelineCells += `
                    <div class="${cellClass} ${isFirst ? 'bar-start' : ''} ${isLast ? 'bar-end' : ''}"
                         data-task-name="${task.name}"
                         data-progress="${task.progress || 0}"
                         title="${task.name} - ${task.time_spent || '0h 0m'}">
                        ${isFirst ? `<span class="task-bar-label">${this.truncateText(task.name, 15)}</span>` : ''}
                        <div class="progress-fill" style="width: ${task.progress || 0}%"></div>
                    </div>
                `;
            } else {
                timelineCells += `<div class="timeline-cell"></div>`;
            }
        }

        return timelineCells;
    }

    addEventListeners() {
        // إضافة tooltips للمهام
        document.querySelectorAll('.task-bar').forEach(bar => {
            bar.addEventListener('mouseenter', (e) => {
                this.showTooltip(e, bar);
            });

            bar.addEventListener('mouseleave', () => {
                this.hideTooltip();
            });
        });
    }

    showTooltip(event, element) {
        const taskName = element.getAttribute('data-task-name');
        const progress = element.getAttribute('data-progress');

        const tooltip = document.createElement('div');
        tooltip.className = 'gantt-tooltip';
        tooltip.innerHTML = `
            <strong>${taskName}</strong><br>
            التقدم: ${progress}%<br>
            الوقت المسجل: ${element.title.split(' - ')[1] || '0h 0m'}
        `;

        document.body.appendChild(tooltip);

        const rect = element.getBoundingClientRect();
        tooltip.style.left = rect.left + 'px';
        tooltip.style.top = (rect.top - tooltip.offsetHeight - 10) + 'px';
    }

    hideTooltip() {
        const tooltip = document.querySelector('.gantt-tooltip');
        if (tooltip) {
            tooltip.remove();
        }
    }

    getStatusText(status) {
        const statusMap = {
            'completed': 'مكتملة',
            'in_progress': 'جاري العمل',
            'paused': 'متوقفة',
            'cancelled': 'ملغاة',
            'new': 'جديدة'
        };
        return statusMap[status] || 'غير محدد';
    }

    truncateText(text, maxLength) {
        if (text.length <= maxLength) return text;
        return text.substring(0, maxLength) + '...';
    }

    createTasksList() {
        // Fallback: عرض قائمة المهام العادية
        let tasksHTML = '<div class="tasks-list-fallback"><h6>قائمة المهام:</h6><ul>';

        this.taskData.forEach(task => {
            tasksHTML += `
                <li class="task-list-item">
                    <span class="task-status-badge status-${task.status}">${this.getStatusText(task.status)}</span>
                    <strong>${task.name}</strong>
                    <small>(${task.time_spent || '0h 0m'})</small>
                </li>
            `;
        });

        tasksHTML += '</ul></div>';
        return tasksHTML;
    }
}

// ✅ دالة مساعدة لتحويل البيانات (إن احتجناها)
function convertPhpTaskDataToJs(phpTaskData) {
    return phpTaskData.map(task => {
        return {
            name: task.task.name,
            status: task.status,
            start_date: task.startDate,
            end_date: task.dueDate || (task.status === 'completed' ? task.completedDate : null),
            type: task.type || 'regular',
            time_spent: task.totalTime.formatted,
            project: task.project ? task.project.name : null
        };
    });
}
