/**
 * Performance Calculations JavaScript
 * حساب مؤشرات الأداء ونسب التقييم للموظفين
 */

// دالة تحديد عدد الشهور بين تاريخين
function getMonthsDifference(startDate, endDate) {
    // تحويل التواريخ إلى كائنات Date
    const start = new Date(startDate);
    const end = new Date(endDate);

    // حساب الفرق بالشهور
    let months = (end.getFullYear() - start.getFullYear()) * 12;
    months += end.getMonth() - start.getMonth();

    // التعامل مع حالة الشهر الواحد المقسم على شهرين ميلاديين
    if (months < 1 && start.getMonth() !== end.getMonth()) {
        months = 1;
    }

    // التأكد من أن القيمة هي على الأقل 1
    return Math.max(1, months);
}

// دالة حساب النتيجة النهائية للأداء
function calculateOverallScore(data) {
    // استخراج تواريخ البداية والنهاية
    const startDate = document.getElementById('start_date')?.value;
    const endDate = document.getElementById('end_date')?.value;

    // حساب عدد الشهور
    const monthsDifference = startDate && endDate ? getMonthsDifference(startDate, endDate) : 1;

    // تعيين الحد الأقصى للتأخير بناءً على عدد الشهور (120 دقيقة لكل شهر)
    const baseMaxDelays = 120;
    const maxAcceptableDelays = baseMaxDelays * monthsDifference;

    let attendanceScore = data.statistics.attendance_percentage;

    let punctualityScore = 100;
    if (data.statistics.delays > maxAcceptableDelays) {
        let excessDelays = data.statistics.delays - maxAcceptableDelays;
        punctualityScore = Math.max(0, 100 - ((excessDelays / maxAcceptableDelays) * 100));
    }

    let workingHoursScore = 100;
    if (typeof data.statistics.average_working_hours !== 'undefined') {
        workingHoursScore = Math.min(100, Math.round((data.statistics.average_working_hours / 8) * 100));
    }

    // استخدام النسب الجديدة للتقييم (الحضور 45%، الانضباط 20%، ساعات العمل 35%)
    let overallScore = Math.round((attendanceScore * 0.45) + (punctualityScore * 0.2) + (workingHoursScore * 0.35));
    return overallScore;
}
