// Initialize statistics data and provide global access

document.addEventListener('DOMContentLoaded', function() {
    console.log('Loading absenceStatistics data');

    // تحضير بيانات الإحصائيات من HTML
    const statisticsDataElement = document.getElementById('absence-statistics-data');
    if (!statisticsDataElement) {
        console.error('Statistics data element not found!');
        return;
    }

    try {
        // يفترض أن البيانات مخزنة في خاصية data-statistics
        const statisticsJson = statisticsDataElement.getAttribute('data-statistics');
        console.log('Raw statistics JSON:', statisticsJson ? statisticsJson.substring(0, 100) + '...' : 'empty');

        if (statisticsJson) {
            // تحليل البيانات من JSON
            const parsedData = JSON.parse(statisticsJson);
            console.log('Successfully parsed statistics data');

            // تنظيم البيانات بالشكل المطلوب
            window.absenceStatistics = normalizeStatisticsData(parsedData);

            // تحقق من هيكل البيانات
            if (window.absenceStatistics.personal) {
                console.log('Personal stats loaded:',
                    'approved=', window.absenceStatistics.personal.approved,
                    'rejected=', window.absenceStatistics.personal.rejected,
                    'pending=', window.absenceStatistics.personal.pending
                );
            }
        } else {
            console.error('Statistics data attribute is empty');
        }
    } catch (error) {
        console.error('Error parsing absence statistics data:', error);
    }
});

// دالة لتنظيم هيكل البيانات حسب المتوقع في ملفات statistics.js
function normalizeStatisticsData(data) {
    const normalized = {};

    // تنظيم بيانات المستخدم الشخصية
    if (data.personal) {
        normalized.personal = {
            // استخدام الاسماء المتوقعة في statistics.js
            approved: data.personal.approved_requests || 0,
            rejected: data.personal.rejected_requests || 0,
            pending: data.personal.pending_requests || 0,
            total: data.personal.total_requests || 0,
            days: data.personal.total_days || 0
        };
    }

    // تنظيم بيانات الفريق
    if (data.team) {
        normalized.team = {
            approved: data.team.approved_requests || 0,
            rejected: data.team.rejected_requests || 0,
            pending: data.team.pending_requests || 0,
            total: data.team.total_requests || 0,
            days: data.team.total_days || 0,
            exceeded: data.team.employees_exceeded_limit || 0,
            team_name: data.team.team_name || null
        };

        // نسخ بيانات الموظفين إن وجدت
        if (data.team.team_employees) {
            normalized.team.team_employees = data.team.team_employees;
        }

        // نسخ بيانات الموظفين الذين تجاوزوا الحد المسموح
        if (data.team.exceeded_employees) {
            normalized.team.exceeded_employees = data.team.exceeded_employees;
        }
    }

    // تنظيم بيانات HR
    if (data.hr) {
        normalized.hr = {
            approved: data.hr.approved_requests || 0,
            rejected: data.hr.rejected_requests || 0,
            pending: data.hr.pending_requests || 0,
            total: data.hr.total_requests || 0,
            days: data.hr.total_days || 0,
            exceeded: data.hr.employees_exceeded_limit || 0
        };

        // نسخ بيانات الرسوم البيانية المتقدمة إن وجدت
        if (data.hr.charts_data) {
            normalized.hr.charts_data = data.hr.charts_data;
        }
    }

    console.log('Normalized statistics data:', normalized);
    return normalized;
}
