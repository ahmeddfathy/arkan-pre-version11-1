/**
 * Date Utilities JavaScript
 * توفر وظائف للتعامل مع التواريخ والفترات الزمنية
 */

// إضافة دالة لتعيين التواريخ الافتراضية
function setDefaultDates() {
    const now = new Date();
    const saturday = new Date(now);
    saturday.setDate(now.getDate() - now.getDay() + 6); // السبت الماضي

    const thursday = new Date(saturday);
    thursday.setDate(saturday.getDate() + 5); // الخميس القادم

    document.getElementById('start_date').value = saturday.toISOString().split('T')[0];
    document.getElementById('end_date').value = thursday.toISOString().split('T')[0];
}

// تعيين التواريخ الافتراضية عند تحميل الصفحة إذا لم يتم تحديد تواريخ
document.addEventListener('DOMContentLoaded', function() {
    if (!document.getElementById('start_date').value || !document.getElementById('end_date').value) {
        setDefaultDates();
    }
});
