/**
 * Projects Calendar View
 * Displays projects on their delivery dates in a calendar format
 */
class ProjectsCalendar {
    constructor() {
        this.currentDate = new Date();
        this.projects = [];
        this.filteredProjects = [];
        this.init();
    }

    init() {
        this.bindEvents();
        this.buildCalendar();
    }

    bindEvents() {
        // Calendar navigation
        const prevBtn = document.getElementById('prevMonthProjects');
        const nextBtn = document.getElementById('nextMonthProjects');
        const todayBtn = document.getElementById('todayBtnProjects');

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

        // Back to Table button
        const backToTableBtn = document.getElementById('backToTableBtnProjects');
        if (backToTableBtn) {
            backToTableBtn.addEventListener('click', () => {
                // Switch back to table view
                const tableViewBtn = document.getElementById('tableViewBtn');
                if (tableViewBtn) {
                    tableViewBtn.click();
                }
            });
        }
    }

    loadProjects() {
        // Get all projects from table rows
        this.projects = [];
        const tableRows = document.querySelectorAll('#tableView .project-row');

        // Get selected date type filter
        const dateTypeFilter = document.getElementById('dateTypeFilter');
        const selectedDateType = dateTypeFilter ? dateTypeFilter.value : 'client_agreed';

        tableRows.forEach(row => {
            // Skip empty rows
            if (!row.cells || row.cells.length === 0) {
                return;
            }

            // Extract project data from row
            const projectNameElement = row.cells[0].querySelector('h6');
            const projectCodeElement = row.cells[1].querySelector('.badge');
            const clientElement = row.cells[2].querySelector('span');
            const managerElement = row.cells[3].querySelector('span');
            const statusElement = row.cells[4].querySelector('.badge');

            if (!projectNameElement) {
                return;
            }

            // Extract dates from the correct columns based on date type selection
            const startDateElement = row.cells[5]; // تاريخ البداية (column 6)
            const endDateElement = row.cells[6];   // تاريخ التسليم (column 7) - changes based on date type

            let startDate = null;
            let endDate = null;

            if (startDateElement) {
                const startDateText = startDateElement.textContent.trim();
                if (startDateText && startDateText !== 'غير محدد') {
                    const dateMatch = startDateText.match(/\d{4}-\d{2}-\d{2}/);
                    if (dateMatch) {
                        startDate = dateMatch[0];
                    }
                }
            }

            if (endDateElement) {
                const endDateText = endDateElement.textContent.trim();
                if (endDateText && endDateText !== 'غير محدد') {
                    const dateMatch = endDateText.match(/\d{4}-\d{2}-\d{2}/);
                    if (dateMatch) {
                        endDate = dateMatch[0];
                    }
                }
            }

            // If no end date, use start date as end date
            if (startDate && !endDate) {
                endDate = startDate;
            }

            const projectData = {
                id: Math.random().toString(36).substr(2, 9), // Generate temporary ID
                name: projectNameElement.textContent.trim(),
                code: projectCodeElement ? projectCodeElement.textContent.trim() : '',
                client: clientElement ? clientElement.textContent.trim() : 'غير محدد',
                manager: managerElement ? managerElement.textContent.trim() : 'غير محدد',
                status: this.extractStatus(statusElement),
                startDate: startDate,
                endDate: endDate,
                dateType: selectedDateType,
                isUrgent: row.classList.contains('urgent'),
                isUnacknowledged: row.classList.contains('unacknowledged-project')
            };

            // Only include projects with valid dates
            if (projectData.endDate) {
                try {
                    projectData.startDateObj = new Date(projectData.startDate);
                    projectData.endDateObj = new Date(projectData.endDate);

                    if (!isNaN(projectData.endDateObj.getTime())) {
                        this.projects.push(projectData);
                    }
                } catch (e) {
                    console.warn('Invalid date format:', projectData.endDate);
                }
            }
        });

        this.applyFilters();
    }

    extractStatus(statusElement) {
        if (!statusElement) return 'غير محدد';

        const statusText = statusElement.textContent.trim();
        const classList = statusElement.classList;

        if (classList.contains('bg-primary')) return 'جديد';
        if (classList.contains('bg-warning')) return 'جاري التنفيذ';
        if (classList.contains('bg-success')) return 'مكتمل';
        if (classList.contains('bg-secondary')) return 'ملغي';

        return statusText;
    }

    applyFilters() {
        const searchFilter = document.getElementById('searchProject');
        const statusFilter = document.getElementById('statusFilter');
        const clientFilter = document.getElementById('clientFilter');

        const searchValue = searchFilter ? searchFilter.value.toLowerCase() : '';
        const statusValue = statusFilter ? statusFilter.value : '';
        const clientValue = clientFilter ? clientFilter.value.toLowerCase() : '';

        this.filteredProjects = this.projects.filter(project => {
            let matches = true;

            if (searchValue &&
                !project.name.toLowerCase().includes(searchValue) &&
                !project.client.toLowerCase().includes(searchValue) &&
                !project.manager.toLowerCase().includes(searchValue)) {
                matches = false;
            }

            if (statusValue && project.status !== statusValue) {
                matches = false;
            }

            if (clientValue && !project.client.toLowerCase().includes(clientValue)) {
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
            'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو',
            'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'
        ];

        const headerElement = document.getElementById('currentMonthYearProjects');
        if (headerElement) {
            headerElement.textContent = `${monthNames[month]} ${year}`;
        }

        // Calculate calendar days
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const firstCalendarDay = new Date(firstDay);
        firstCalendarDay.setDate(firstCalendarDay.getDate() - firstDay.getDay());

        const calendarDays = document.getElementById('calendarDaysProjects');
        if (!calendarDays) return;

        calendarDays.innerHTML = '';

        // Generate 42 days (6 weeks)
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

        // Add classes for styling
        if (date.getMonth() !== currentMonth) {
            dayDiv.classList.add('other-month');
        }

        const today = new Date();
        if (date.toDateString() === today.toDateString()) {
            dayDiv.classList.add('today');
        }

        // Day number
        const dayNumber = document.createElement('div');
        dayNumber.className = 'calendar-day-number';
        dayNumber.textContent = date.getDate();
        dayDiv.appendChild(dayNumber);

        // Projects container
        const projectsContainer = document.createElement('div');
        projectsContainer.className = 'calendar-tasks';

        // Find projects for this date
        const dateString = date.toISOString().split('T')[0];
        const dayProjects = this.findProjectsForDate(dateString);

        // Add projects (limit to show max 3, then show "more" indicator)
        const maxVisibleProjects = 3;
        dayProjects.slice(0, maxVisibleProjects).forEach(project => {
            const projectElement = this.createProjectElement(project);
            projectsContainer.appendChild(projectElement);
        });

        // Show overflow indicator if there are more projects
        if (dayProjects.length > maxVisibleProjects) {
            const overflowDiv = document.createElement('div');
            overflowDiv.className = 'calendar-task-overflow';
            overflowDiv.textContent = `+${dayProjects.length - maxVisibleProjects} أخرى`;
            overflowDiv.style.cursor = 'pointer';
            overflowDiv.onclick = () => this.showDayProjects(date, dayProjects);
            projectsContainer.appendChild(overflowDiv);
        }

        dayDiv.appendChild(projectsContainer);
        return dayDiv;
    }

    findProjectsForDate(dateString) {
        return this.filteredProjects.filter(project => {
            const projectEndDate = project.endDateObj.toISOString().split('T')[0];

            // Show project only on delivery date
            return dateString === projectEndDate;
        });
    }

    createProjectElement(project) {
        const projectDiv = document.createElement('div');
        projectDiv.className = `calendar-task status-${this.getStatusClass(project.status)}`;

        if (project.isUrgent) {
            projectDiv.classList.add('urgent-project');
        }

        if (project.isUnacknowledged) {
            projectDiv.style.borderLeft = '3px solid #ffc107';
        }

        projectDiv.textContent = project.name;
        projectDiv.title = `${project.name} - ${project.client} - ${project.manager}`;

        // Click to show project details
        projectDiv.onclick = (e) => {
            e.stopPropagation();
            this.showProjectDetails(project);
        };

        return projectDiv;
    }

    getStatusClass(status) {
        const statusMap = {
            'جديد': 'new',
            'جاري التنفيذ': 'in_progress',
            'مكتمل': 'completed',
            'ملغي': 'cancelled'
        };
        return statusMap[status] || 'new';
    }

    showProjectDetails(project) {
        const statusText = project.status;
        const urgentBadge = project.isUrgent ? '<span class="badge bg-danger ms-1">مستعجل</span>' : '';
        const unackBadge = project.isUnacknowledged ? '<span class="badge bg-warning ms-1">غير مستلم</span>' : '';

        // Show project details in SweetAlert
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: project.name,
                html: `
                    <div class="text-start">
                        <p><strong>الكود:</strong> ${project.code || 'غير محدد'}</p>
                        <p><strong>العميل:</strong> ${project.client}</p>
                        <p><strong>المسؤول:</strong> ${project.manager}</p>
                        <p><strong>الحالة:</strong> ${statusText} ${urgentBadge} ${unackBadge}</p>
                        <p><strong>تاريخ البدء:</strong> ${project.startDate}</p>
                        ${project.endDate !== project.startDate ? `<p><strong>تاريخ التسليم:</strong> ${project.endDate}</p>` : ''}
                    </div>
                `,
                width: '500px',
                showCloseButton: true,
                showConfirmButton: false,
                customClass: {
                    popup: 'text-start'
                }
            });
        } else {
            // Fallback to alert
            alert(`مشروع: ${project.name}\nالعميل: ${project.client}\nالمسؤول: ${project.manager}\nالحالة: ${statusText}`);
        }
    }

    showDayProjects(date, projects) {
        const dateString = date.toLocaleDateString('ar-EG', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });

        let content = `<h6 class="mb-3">مشاريع يوم ${dateString}</h6>`;

        projects.forEach(project => {
            const statusText = project.status;
            const urgentBadge = project.isUrgent ? '<span class="badge bg-danger ms-1">مستعجل</span>' : '';
            const unackBadge = project.isUnacknowledged ? '<span class="badge bg-warning ms-1">غير مستلم</span>' : '';

            content += `
                <div class="border-bottom pb-2 mb-2">
                    <div class="d-flex justify-content-between align-items-start">
                        <div style="cursor: pointer;" onclick="projectsCalendar.showProjectDetails(${JSON.stringify(project).replace(/"/g, '&quot;')})">
                            <strong>${project.name}</strong>
                            ${urgentBadge} ${unackBadge}
                            <br>
                            <small class="text-muted">${project.client} - ${project.manager}</small>
                            <br>
                            <small class="text-primary"><i class="fas fa-eye me-1"></i>انقر لعرض التفاصيل</small>
                        </div>
                        <span class="badge bg-${this.getStatusBootstrapClass(project.status)}">${statusText}</span>
                    </div>
                </div>
            `;
        });

        // Show in SweetAlert
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: `مشاريع اليوم`,
                html: content,
                width: '600px',
                showCloseButton: true,
                showConfirmButton: false
            });
        }
    }

    getStatusBootstrapClass(status) {
        const statusClasses = {
            'جديد': 'primary',
            'جاري التنفيذ': 'warning',
            'مكتمل': 'success',
            'ملغي': 'secondary'
        };
        return statusClasses[status] || 'secondary';
    }

    refresh() {
        this.loadProjects();
    }
}

// Calendar initialization and management
let projectsCalendar;

function initializeProjectsCalendar() {
    projectsCalendar = new ProjectsCalendar();

    // Load projects initially
    projectsCalendar.loadProjects();

    // Refresh calendar when filters change
    ['searchProject', 'statusFilter', 'clientFilter', 'dateTypeFilter'].forEach(filterId => {
        const element = document.getElementById(filterId);
        if (element) {
            element.addEventListener('change', () => {
                // Reload projects when date type changes
                if (filterId === 'dateTypeFilter') {
                    projectsCalendar.loadProjects();
                } else {
                    projectsCalendar.applyFilters();
                }
            });
            element.addEventListener('input', () => projectsCalendar.applyFilters());
            element.addEventListener('keyup', () => projectsCalendar.applyFilters());
        }
    });

    // Make calendar globally accessible for view switching
    window.projectsCalendar = projectsCalendar;

    console.log('✅ Projects Calendar initialized successfully');
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Small delay to ensure table is rendered first
    setTimeout(() => {
        initializeProjectsCalendar();
    }, 500);
});

// Export for global access
window.ProjectsCalendar = ProjectsCalendar;
window.initializeProjectsCalendar = initializeProjectsCalendar;
