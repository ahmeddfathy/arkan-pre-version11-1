// Employee Reports Custom JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Employee search functionality
    initEmployeeSearch();

    // Initialize report visualizations if data is available
    if (window.employeeReportData) {
        console.log('البيانات المستلمة:', window.employeeReportData);

        const reportConfig = {
            taskData: window.employeeReportData.taskData || [],
            ganttTasks: window.employeeReportData.ganttTasks || [],
            startDate: window.employeeReportData.startDate,
            endDate: window.employeeReportData.endDate,
            taskStatusData: window.employeeReportData.taskStatusData,
            timeData: window.employeeReportData.timeData
        };

        // Add shift data if available
        if (window.shiftData) {
            console.log('بيانات الشيفت:', window.shiftData);
        }

        console.log('إعداد التقرير:', reportConfig);
        initCustomReportVisualizations(reportConfig);
    } else {
        console.warn('لا توجد بيانات تقارير متاحة');

        // Display message if no data available
        const ganttContainer = document.getElementById('ganttChart');
        if (ganttContainer) {
            ganttContainer.innerHTML = `
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle fa-2x mb-2"></i>
                    <h5>لا توجد بيانات لعرضها</h5>
                    <p class="mb-0">يرجى اختيار موظف ونطاق زمني لعرض مخطط جانت المخصص.</p>
                </div>
            `;
        }
    }
});

/**
 * Initialize employee search functionality
 */
function initEmployeeSearch() {
    const employeeSearch = document.getElementById('employeeSearch');
    if (employeeSearch) {
        employeeSearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const employeeItems = document.querySelectorAll('.employee-item');

            employeeItems.forEach(function(item) {
                const employeeName = item.getAttribute('data-employee-name');
                if (employeeName && employeeName.includes(searchTerm)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }
}

/**
 * Initialize report visualizations
 * @param {Object} config - Report configuration
 */
function initCustomReportVisualizations(config) {
    // Check if the main initialization function exists
    if (typeof window.initReportVisualizations === 'function') {
        window.initReportVisualizations(config);
    } else {
        console.warn('initReportVisualizations function not found in main reports file');

        // Fallback: just log the config
        console.log('Report config ready:', config);
    }
}

/**
 * Update Gantt chart with loading state
 */
function showGanttLoading() {
    const ganttContainer = document.getElementById('ganttChart');
    if (ganttContainer) {
        ganttContainer.innerHTML = `
            <div class="alert alert-info text-center" id="ganttLoadingMessage">
                <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                <h5>جاري تحميل المهام...</h5>
                <p class="mb-0">يرجى الانتظار قليلاً</p>
            </div>
        `;
    }
}

/**
 * Clear Gantt chart loading state
 */
function hideGanttLoading() {
    const loadingMessage = document.getElementById('ganttLoadingMessage');
    if (loadingMessage) {
        loadingMessage.remove();
    }
}

// Global functions for external access
window.EmployeeReports = {
    showGanttLoading,
    hideGanttLoading,
    initEmployeeSearch,
    initCustomReportVisualizations
};
