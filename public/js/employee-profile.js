/**
 * Employee Profile JavaScript Functions
 * Handles profile interactions, animations, and data export
 */

document.addEventListener('DOMContentLoaded', function() {
    initializeProfile();
});

/**
 * Initialize profile functionality
 */
function initializeProfile() {
    setupTabAnimations();
    setupCharts();
    setupProfileActions();
    setupResponsiveFeatures();
    setupDateFilter();
    applyAnimations();
}

/**
 * Setup tab animations and transitions
 */
function setupTabAnimations() {
    const tabButtons = document.querySelectorAll('#profileTabs button[data-bs-toggle="tab"]');

    tabButtons.forEach(button => {
        button.addEventListener('shown.bs.tab', function(event) {
            const targetPane = document.querySelector(event.target.getAttribute('data-bs-target'));

            // Add fade-in animation to the active tab content
            if (targetPane) {
                targetPane.classList.add('fade-in');

                // Animate cards within the tab
                const cards = targetPane.querySelectorAll('.card');
                cards.forEach((card, index) => {
                    setTimeout(() => {
                        card.classList.add('slide-up');
                    }, index * 100);
                });
            }

            // Update URL hash without triggering scroll
            const tabId = event.target.getAttribute('data-bs-target').substring(1);
            if (history.pushState) {
                history.pushState(null, null, '#' + tabId);
            }
        });
    });

    // Handle direct hash navigation
    if (window.location.hash) {
        const targetTab = document.querySelector(`button[data-bs-target="${window.location.hash}"]`);
        if (targetTab) {
            const tab = new bootstrap.Tab(targetTab);
            tab.show();
        }
    }
}

/**
 * Setup charts and visualizations
 */
function setupCharts() {
    setupAttendanceChart();
    setupPerformanceChart();
    setupRequestsChart();
}

/**
 * Attendance chart visualization
 */
function setupAttendanceChart() {
    const attendanceChartContainer = document.getElementById('attendanceChart');
    if (!attendanceChartContainer) return;

    // Get attendance data from the page
    const attendanceData = getAttendanceDataFromDOM();

    if (window.Chart) {
        const ctx = attendanceChartContainer.getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['أيام الحضور', 'أيام الغياب', 'أيام التأخير'],
                datasets: [{
                    data: [
                        attendanceData.presentDays,
                        attendanceData.absentDays,
                        attendanceData.lateDays
                    ],
                    backgroundColor: [
                        '#28a745',
                        '#dc3545',
                        '#ffc107'
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                }
            }
        });
    }
}

/**
 * Performance chart visualization
 */
function setupPerformanceChart() {
    const performanceChartContainer = document.getElementById('performanceChart');
    if (!performanceChartContainer || !window.Chart) return;

    const performanceData = getPerformanceDataFromDOM();

    const ctx = performanceChartContainer.getContext('2d');
    new Chart(ctx, {
        type: 'radar',
        data: {
            labels: ['المشاريع', 'المهام', 'النقاط', 'الحضور', 'التفاعل'],
            datasets: [{
                label: 'الأداء',
                data: [
                    performanceData.projects,
                    performanceData.tasks,
                    performanceData.points,
                    performanceData.attendance,
                    performanceData.social
                ],
                backgroundColor: 'rgba(0, 123, 255, 0.2)',
                borderColor: '#007bff',
                borderWidth: 2,
                pointBackgroundColor: '#007bff',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                r: {
                    beginAtZero: true,
                    max: 100
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
}

/**
 * Requests status chart
 */
function setupRequestsChart() {
    const requestsChartContainer = document.getElementById('requestsChart');
    if (!requestsChartContainer || !window.Chart) return;

    const requestsData = getRequestsDataFromDOM();

    const ctx = requestsChartContainer.getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['طلبات الغياب', 'طلبات الأذونات', 'طلبات العمل الإضافي'],
            datasets: [
                {
                    label: 'موافق عليها',
                    data: [
                        requestsData.absence.approved,
                        requestsData.permission.approved,
                        requestsData.overtime.approved
                    ],
                    backgroundColor: '#28a745'
                },
                {
                    label: 'قيد المراجعة',
                    data: [
                        requestsData.absence.pending,
                        requestsData.permission.pending,
                        requestsData.overtime.pending
                    ],
                    backgroundColor: '#ffc107'
                },
                {
                    label: 'مرفوضة',
                    data: [
                        requestsData.absence.rejected,
                        requestsData.permission.rejected,
                        requestsData.overtime.rejected
                    ],
                    backgroundColor: '#dc3545'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top'
                }
            },
            scales: {
                x: {
                    stacked: true
                },
                y: {
                    stacked: true,
                    beginAtZero: true
                }
            }
        }
    });
}

/**
 * Setup profile actions (export, print, etc.)
 */
function setupProfileActions() {
    setupExportActions();
    setupModalActions();
    setupTooltips();
}

/**
 * Setup export functionality
 */
function setupExportActions() {
    // Print functionality
    window.printProfile = function() {
        // Hide elements that shouldn't be printed
        const elementsToHide = document.querySelectorAll('.profile-actions-fixed, .btn, .nav-tabs');
        elementsToHide.forEach(el => el.style.display = 'none');

        // Trigger print
        window.print();

        // Restore hidden elements
        setTimeout(() => {
            elementsToHide.forEach(el => el.style.display = '');
        }, 1000);
    };

    // PDF Export functionality
    window.exportPDF = function() {
        showLoadingSpinner();

        // You can implement PDF export using libraries like jsPDF or send to server
        fetch(`/employee/profile/export-pdf/${getUserId()}`, {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': getCSRFToken(),
                'Accept': 'application/pdf'
            }
        })
        .then(response => response.blob())
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `employee-profile-${getUserId()}.pdf`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        })
        .catch(error => {
            console.error('Error exporting PDF:', error);
            showErrorMessage('حدث خطأ في تصدير PDF');
        })
        .finally(() => {
            hideLoadingSpinner();
        });
    };
}

/**
 * Setup modal actions
 */
function setupModalActions() {
    // Badge details modal
    document.querySelectorAll('.badge-item').forEach(item => {
        item.addEventListener('click', function() {
            const badgeId = this.dataset.badgeId;
            if (badgeId) {
                showBadgeDetails(badgeId);
            }
        });
    });

    // Project details modal
    document.querySelectorAll('.project-item').forEach(item => {
        item.addEventListener('click', function() {
            const projectId = this.dataset.projectId;
            if (projectId) {
                showProjectDetails(projectId);
            }
        });
    });
}

/**
 * Setup Bootstrap tooltips
 */
function setupTooltips() {
    if (typeof bootstrap !== 'undefined') {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
}

/**
 * Setup responsive features
 */
function setupResponsiveFeatures() {
    // Handle mobile navigation
    setupMobileNavigation();

    // Handle window resize
    window.addEventListener('resize', debounce(handleWindowResize, 250));

    // Handle scroll events
    window.addEventListener('scroll', throttle(handleScroll, 100));
}

/**
 * Mobile navigation handling
 */
function setupMobileNavigation() {
    const tabContainer = document.querySelector('#profileTabs');
    if (!tabContainer) return;

    // Add touch scrolling for mobile tabs
    let isScrolling = false;
    let scrollLeft = 0;
    let startX = 0;

    tabContainer.addEventListener('touchstart', function(e) {
        isScrolling = true;
        startX = e.touches[0].pageX - tabContainer.offsetLeft;
        scrollLeft = tabContainer.scrollLeft;
    });

    tabContainer.addEventListener('touchend', function() {
        isScrolling = false;
    });

    tabContainer.addEventListener('touchmove', function(e) {
        if (!isScrolling) return;
        e.preventDefault();
        const x = e.touches[0].pageX - tabContainer.offsetLeft;
        const walk = (x - startX) * 2;
        tabContainer.scrollLeft = scrollLeft - walk;
    });
}

/**
 * Handle window resize
 */
function handleWindowResize() {
    // Refresh charts on resize
    if (window.Chart) {
        Chart.helpers.each(Chart.instances, function(instance) {
            instance.resize();
        });
    }

    // Update responsive elements
    updateResponsiveElements();
}

/**
 * Handle scroll events
 */
function handleScroll() {
    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

    // Update fixed elements based on scroll position
    const fixedActions = document.querySelector('.profile-actions-fixed');
    if (fixedActions) {
        if (scrollTop > 200) {
            fixedActions.style.opacity = '1';
            fixedActions.style.transform = 'translateY(0)';
        } else {
            fixedActions.style.opacity = '0.7';
            fixedActions.style.transform = 'translateY(10px)';
        }
    }
}

/**
 * Apply initial animations
 */
function applyAnimations() {
    // Animate profile header
    const profileHeader = document.querySelector('.profile-header');
    if (profileHeader) {
        profileHeader.classList.add('fade-in');
    }

    // Animate stat cards with stagger effect
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach((card, index) => {
        setTimeout(() => {
            card.classList.add('slide-up');
        }, index * 150);
    });
}

/**
 * Data extraction functions
 */
function getAttendanceDataFromDOM() {
    const presentDays = parseInt(document.querySelector('[data-present-days]')?.dataset.presentDays) || 0;
    const absentDays = parseInt(document.querySelector('[data-absent-days]')?.dataset.absentDays) || 0;
    const lateDays = parseInt(document.querySelector('[data-late-days]')?.dataset.lateDays) || 0;

    return { presentDays, absentDays, lateDays };
}

function getPerformanceDataFromDOM() {
    return {
        projects: Math.min(parseInt(document.querySelector('[data-projects-score]')?.dataset.projectsScore) || 0, 100),
        tasks: Math.min(parseInt(document.querySelector('[data-tasks-score]')?.dataset.tasksScore) || 0, 100),
        points: Math.min(parseInt(document.querySelector('[data-points-score]')?.dataset.pointsScore) || 0, 100),
        attendance: Math.min(parseInt(document.querySelector('[data-attendance-score]')?.dataset.attendanceScore) || 0, 100),
        social: Math.min(parseInt(document.querySelector('[data-social-score]')?.dataset.socialScore) || 0, 100)
    };
}

function getRequestsDataFromDOM() {
    return {
        absence: {
            approved: parseInt(document.querySelector('[data-absence-approved]')?.dataset.absenceApproved) || 0,
            pending: parseInt(document.querySelector('[data-absence-pending]')?.dataset.absencePending) || 0,
            rejected: parseInt(document.querySelector('[data-absence-rejected]')?.dataset.absenceRejected) || 0
        },
        permission: {
            approved: parseInt(document.querySelector('[data-permission-approved]')?.dataset.permissionApproved) || 0,
            pending: parseInt(document.querySelector('[data-permission-pending]')?.dataset.permissionPending) || 0,
            rejected: parseInt(document.querySelector('[data-permission-rejected]')?.dataset.permissionRejected) || 0
        },
        overtime: {
            approved: parseInt(document.querySelector('[data-overtime-approved]')?.dataset.overtimeApproved) || 0,
            pending: parseInt(document.querySelector('[data-overtime-pending]')?.dataset.overtimePending) || 0,
            rejected: parseInt(document.querySelector('[data-overtime-rejected]')?.dataset.overtimeRejected) || 0
        }
    };
}

/**
 * Utility functions
 */
function getUserId() {
    return document.querySelector('[data-user-id]')?.dataset.userId || '';
}

function getCSRFToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function showLoadingSpinner() {
    const spinner = document.createElement('div');
    spinner.id = 'loading-spinner';
    spinner.innerHTML = `
        <div class="d-flex justify-content-center align-items-center position-fixed w-100 h-100" style="top: 0; left: 0; background: rgba(0,0,0,0.5); z-index: 9999;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">جاري التحميل...</span>
            </div>
        </div>
    `;
    document.body.appendChild(spinner);
}

function hideLoadingSpinner() {
    const spinner = document.getElementById('loading-spinner');
    if (spinner) {
        spinner.remove();
    }
}

function showErrorMessage(message) {
    if (typeof toastr !== 'undefined') {
        toastr.error(message);
    } else {
        alert(message);
    }
}

function showSuccessMessage(message) {
    if (typeof toastr !== 'undefined') {
        toastr.success(message);
    } else {
        alert(message);
    }
}

/**
 * Performance utilities
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

/**
 * Update responsive elements
 */
function updateResponsiveElements() {
    const isMobile = window.innerWidth <= 768;

    // Update table responsiveness
    const tables = document.querySelectorAll('.table');
    tables.forEach(table => {
        const wrapper = table.closest('.table-responsive');
        if (isMobile && !wrapper) {
            const div = document.createElement('div');
            div.className = 'table-responsive';
            table.parentNode.insertBefore(div, table);
            div.appendChild(table);
        }
    });

    // Update grid layouts for mobile
    const gridContainers = document.querySelectorAll('.points-grid, .requests-summary');
    gridContainers.forEach(container => {
        if (isMobile) {
            container.style.gridTemplateColumns = '1fr';
        } else {
            container.style.gridTemplateColumns = '';
        }
    });
}

/**
 * Modal functions
 */
function showBadgeDetails(badgeId) {
    // Implementation for showing badge details in modal
    console.log('Show badge details for:', badgeId);
}

function showProjectDetails(projectId) {
    // Implementation for showing project details in modal
    console.log('Show project details for:', projectId);
}

/**
 * Additional profile interactions
 */
function toggleSection(sectionId) {
    const section = document.getElementById(sectionId);
    if (section) {
        section.classList.toggle('collapsed');
    }
}

function refreshProfileData() {
    showLoadingSpinner();

    // Refresh profile data via AJAX
    fetch(`/employee/profile/refresh/${getUserId()}`, {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': getCSRFToken(),
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update DOM with new data
            updateProfileData(data.profileData);
            showSuccessMessage('تم تحديث البيانات بنجاح');
        } else {
            showErrorMessage('حدث خطأ في تحديث البيانات');
        }
    })
    .catch(error => {
        console.error('Error refreshing profile data:', error);
        showErrorMessage('حدث خطأ في تحديث البيانات');
    })
    .finally(() => {
        hideLoadingSpinner();
    });
}

function updateProfileData(data) {
    // Implementation for updating DOM with new data
    console.log('Updating profile data:', data);
}

/**
 * Setup date filtering functionality
 */
function setupDateFilter() {
    const dateFilterForm = document.getElementById('dateFilterForm');
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    const resetFilterBtn = document.getElementById('resetFilter');
    // Form submission handler
    if (dateFilterForm) {
        dateFilterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            applyDateFilter();
        });
    }

    // Reset filter handler
    if (resetFilterBtn) {
        resetFilterBtn.addEventListener('click', function() {
            resetDateFilter();
        });
    }

    // Date change handlers
    if (startDateInput && endDateInput) {
        startDateInput.addEventListener('change', validateDateRange);
        endDateInput.addEventListener('change', validateDateRange);
    }
}

/**
 * Apply date filter
 */
function applyDateFilter() {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;

    if (!startDate || !endDate) {
        showErrorMessage('يرجى تحديد تاريخ البداية والنهاية');
        return;
    }

    // Validate date range
    if (new Date(startDate) > new Date(endDate)) {
        showErrorMessage('تاريخ البداية يجب أن يكون قبل تاريخ النهاية');
        return;
    }

    // Update URL with filter parameters
    const url = new URL(window.location);
    url.searchParams.set('start_date', startDate);
    url.searchParams.set('end_date', endDate);

    // Show loading
    showLoadingSpinner();

    // Reload page with new parameters
    window.location.href = url.toString();
}

/**
 * Reset date filter to defaults
 */
function resetDateFilter() {
    const today = new Date();
    const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);

    document.getElementById('start_date').value = formatDate(firstDayOfMonth);
    document.getElementById('end_date').value = formatDate(today);

    // Update display
    updateFilterDisplay(formatDate(firstDayOfMonth), formatDate(today));

    // Apply the reset
    applyDateFilter();
}



/**
 * Validate date range
 */
function validateDateRange() {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;

    if (startDate && endDate) {
        if (new Date(startDate) > new Date(endDate)) {
            showErrorMessage('تاريخ البداية يجب أن يكون قبل تاريخ النهاية');
            return false;
        }
        updateFilterDisplay(startDate, endDate);
    }
    return true;
}

/**
 * Update filter display
 */
function updateFilterDisplay(startDate, endDate) {
    const displayStartDate = document.getElementById('displayStartDate');
    const displayEndDate = document.getElementById('displayEndDate');

    if (displayStartDate && displayEndDate) {
        displayStartDate.textContent = startDate;
        displayEndDate.textContent = endDate;
    }
}

/**
 * Format date to YYYY-MM-DD
 */
function formatDate(date) {
    return date.toISOString().split('T')[0];
}

/**
 * Scroll to filter section
 */
function scrollToFilter() {
    const filterSection = document.querySelector('.date-filter-section');
    if (filterSection) {
        filterSection.scrollIntoView({ behavior: 'smooth', block: 'start' });

        // Highlight the filter section briefly
        filterSection.style.boxShadow = '0 0 20px rgba(0, 123, 255, 0.5)';
        setTimeout(() => {
            filterSection.style.boxShadow = '';
        }, 2000);
    }
}

/**
 * Live update data without page reload (AJAX)
 */
function updateDataWithFilter() {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    const userId = getUserId();

    if (!startDate || !endDate) {
        showErrorMessage('يرجى تحديد تاريخ البداية والنهاية');
        return;
    }

    showLoadingSpinner();

    fetch(`/employee/profile/refresh/${userId}?start_date=${startDate}&end_date=${endDate}`, {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': getCSRFToken(),
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateProfileDataInDOM(data.profileData);
            updateFilterDisplay(startDate, endDate);
            showSuccessMessage('تم تحديث البيانات بنجاح');
        } else {
            showErrorMessage(data.message || 'حدث خطأ في تحديث البيانات');
        }
    })
    .catch(error => {
        console.error('Error updating data:', error);
        showErrorMessage('حدث خطأ في تحديث البيانات');
    })
    .finally(() => {
        hideLoadingSpinner();
    });
}

/**
 * Update profile data in DOM without page reload
 */
function updateProfileDataInDOM(profileData) {
    // Update statistics cards
    updateStatCards(profileData);

    // Update charts
    updateCharts(profileData);

    // Update tables
    updateTables(profileData);

    // Update any other dynamic content
    updateDynamicContent(profileData);
}

/**
 * Update statistics cards
 */
function updateStatCards(profileData) {
    // Update performance stats
    const performanceStats = profileData.performance_stats;
    updateCardValue('[data-stat="total-projects"]', performanceStats.total_projects);
    updateCardValue('[data-stat="total-tasks"]', performanceStats.total_tasks);
    updateCardValue('[data-stat="total-points"]', performanceStats.total_points);

    // Update attendance stats
    const attendanceData = profileData.attendance_data.period_summary;
    updateCardValue('[data-stat="attendance-rate"]', attendanceData.attendance_rate + '%');
}

/**
 * Update card value helper
 */
function updateCardValue(selector, value) {
    const element = document.querySelector(selector);
    if (element) {
        element.textContent = value;

        // Add animation effect
        element.style.transform = 'scale(1.1)';
        element.style.color = '#007bff';
        setTimeout(() => {
            element.style.transform = 'scale(1)';
            element.style.color = '';
        }, 300);
    }
}

/**
 * Update charts with new data
 */
function updateCharts(profileData) {
    // This would update existing charts with new data
    // Implementation depends on the charting library used
    if (window.Chart && Chart.instances.length > 0) {
        Chart.instances.forEach(chart => {
            // Update chart data based on chart type and new profileData
            chart.update();
        });
    }
}

/**
 * Update tables with new data
 */
function updateTables(profileData) {
    // Update recent projects table
    updateRecentProjectsTable(profileData.projects_tasks.recent_projects);

    // Update recent tasks table
    updateRecentTasksTable(profileData.projects_tasks.recent_tasks);

    // Update requests tables
    updateRequestsTables(profileData.requests_history);
}

/**
 * Update recent projects table
 */
function updateRecentProjectsTable(projects) {
    const tableBody = document.querySelector('#recent-projects-table tbody');
    if (tableBody && projects) {
        tableBody.innerHTML = '';
        projects.forEach(project => {
            const row = createProjectRow(project);
            tableBody.appendChild(row);
        });
    }
}

/**
 * Create project table row
 */
function createProjectRow(project) {
    const row = document.createElement('tr');
    row.innerHTML = `
        <td>${project.name}</td>
        <td>${project.client ? project.client.name : 'غير محدد'}</td>
        <td><span class="badge badge-${getStatusBadgeClass(project.status)}">${project.status}</span></td>
        <td>${project.season ? project.season.name : 'غير محدد'}</td>
        <td>${formatDate(new Date(project.created_at))}</td>
    `;
    return row;
}

/**
 * Get status badge class
 */
function getStatusBadgeClass(status) {
    switch(status) {
        case 'مكتمل': return 'success';
        case 'جاري التنفيذ': return 'warning';
        case 'ملغي': return 'danger';
        default: return 'secondary';
    }
}

/**
 * Update dynamic content
 */
function updateDynamicContent(profileData) {
    // Update any other dynamic content based on the new data
    const dateRange = profileData.date_range;
    if (dateRange) {
        document.querySelector('.period-info')?.innerHTML =
            `الفترة: ${dateRange.days_count} يوم`;
    }
}

// Export functions for global access
window.ProfileManager = {
    refreshData: refreshProfileData,
    exportPDF: () => window.exportPDF(),
    print: () => window.printProfile(),
    toggleSection: toggleSection,
    applyDateFilter: applyDateFilter,
    resetDateFilter: resetDateFilter,
    updateDataWithFilter: updateDataWithFilter
};

// Global functions for inline calls
window.scrollToFilter = scrollToFilter;
