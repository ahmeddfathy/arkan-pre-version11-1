// تهيئة تقارير الموظفين
document.addEventListener('DOMContentLoaded', function() {
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

        // إضافة بيانات الشيفت إذا كانت متوفرة
        if (window.shiftDataExists && window.shiftData) {
            console.log('بيانات الشيفت:', window.shiftData);
        }

        console.log('إعداد التقرير:', reportConfig);
        initReportVisualizations(reportConfig);
    } else {
        console.warn('لا توجد بيانات تقارير متاحة');

        // عرض رسالة في حالة عدم وجود بيانات
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
