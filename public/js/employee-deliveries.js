/**
 * Employee Deliveries JavaScript Functions
 * نظام تسليمات الموظفين - الملف الرئيسي
 */

// استيراد الملفات المقسمة
// Import the split files

$(document).ready(function() {
    // تهيئة الصفحة الرئيسية
    initializeDeliveriesPage();

    // تهيئة الجداول
    initializeTablesOnLoad();

    // تهيئة معالجات التابز
    handleTabSwitching();

    // تهيئة معالجات تاب المشاريع
    initializeProjectsTableOnTabSwitch();

    // تهيئة إجراءات التسليمات
    initializeDeliveryActions();

    // Handle date filter change
    $('#date_filter').change(function() {
        const customDateRange = $('#custom-date-range');
        if ($(this).val() === 'custom') {
            customDateRange.show();
        } else {
            customDateRange.hide();
        }
    });

    // Initialize custom date range visibility
    if ($('#date_filter').val() === 'custom') {
        $('#custom-date-range').show();
    }
});

// Initialize tooltips when document is ready
$(document).ready(function() {
    initializeTooltips();
});

// Re-initialize tooltips after dynamic content changes
$(document).on('DOMNodeInserted', function() {
    initializeTooltips();
});
