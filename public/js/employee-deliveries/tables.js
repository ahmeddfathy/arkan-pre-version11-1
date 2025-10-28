/**
 * Employee Deliveries Tables Management
 * إدارة جداول تسليمات الموظفين
 */

/**
 * Initialize DataTable
 * تهيئة جدول البيانات
 */
function initializeDataTable() {
    // تجنب التهيئة المزدوجة
    if (window.deliveriesTableInitialized) {
        console.log('DataTable already initialized, skipping...');
        return;
    }

    if ($.fn.DataTable.isDataTable('#deliveriesTable')) {
        $('#deliveriesTable').DataTable().destroy();
    }

    // التحقق من وجود بيانات قبل تهيئة DataTable
    const hasData = $('#deliveriesTable tbody tr').length > 0 &&
                   !$('#deliveriesTable tbody tr:first td').attr('colspan');

    if (!hasData) {
        console.log('No data available, skipping DataTable initialization');
        return;
    }

    deliveriesTable = $('#deliveriesTable').DataTable({
        responsive: true,
        pageLength: 25,
        language: {
            emptyTable: "لا توجد تسليمات متاحة",
            zeroRecords: "لا توجد نتائج مطابقة للبحث",
            info: "عرض _START_ إلى _END_ من _TOTAL_ تسليمة",
            infoEmpty: "عرض 0 إلى 0 من 0 تسليمات",
            infoFiltered: "(مفلتر من _MAX_ إجمالي التسليمات)",
            lengthMenu: "عرض _MENU_ تسليمات",
            search: "بحث:",
            paginate: {
                first: "الأول",
                last: "الأخير",
                next: "التالي",
                previous: "السابق"
            },
            loadingRecords: "جاري التحميل...",
            processing: "جاري المعالجة...",
            searchPlaceholder: "ابحث هنا..."
        },
        order: [[5, 'desc']], // ترتيب حسب موعد التسليم
        columnDefs: [
            {
                orderable: false,
                targets: [9] // عمود الإجراءات غير قابل للترتيب (10 أعمدة، آخر عمود index 9)
            },
            {
                targets: [0, 1, 2], // أعمدة المشروع والخدمة والموظف
                render: function(data, type, row) {
                    if (type === 'display' && data && data.length > 30) {
                        return '<span title="' + data + '">' + data.substr(0, 30) + '...</span>';
                    }
                    return data;
                }
            }
        ],
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
         drawCallback: function() {
             // إعادة تفعيل tooltips بعد إعادة رسم الجدول
             $('[data-bs-toggle="tooltip"]').tooltip();
         }
     });

     // تعيين المتغير لتجنب التهيئة المزدوجة
     window.deliveriesTableInitialized = true;
}

/**
 * Initialize My Deliveries DataTable
 * تهيئة جدول التسليمات الشخصية
 */
function initializeMyDeliveriesTable() {
    if (window.myDeliveriesTableInitialized) {
        console.log('My Deliveries DataTable already initialized, skipping...');
        return;
    }

    const hasMyData = $('#myDeliveriesTable tbody tr').length > 0 &&
                     !$('#myDeliveriesTable tbody tr:first td').attr('colspan');

    if (!hasMyData) {
        console.log('No my deliveries data available, skipping DataTable initialization');
        return;
    }

    if ($.fn.DataTable.isDataTable('#myDeliveriesTable')) {
        $('#myDeliveriesTable').DataTable().destroy();
    }

    try {
        $('#myDeliveriesTable').DataTable({
            responsive: true,
            pageLength: 10,
            language: {
                emptyTable: "لا توجد تسليمات متاحة",
                zeroRecords: "لا توجد نتائج مطابقة للبحث",
                info: "عرض _START_ إلى _END_ من _TOTAL_ تسليمة",
                infoEmpty: "عرض 0 إلى 0 من 0 تسليمات",
                infoFiltered: "(مفلتر من _MAX_ إجمالي التسليمات)",
                lengthMenu: "عرض _MENU_ تسليمات",
                search: "بحث:",
                paginate: {
                    first: "الأول",
                    last: "الأخير",
                    next: "التالي",
                    previous: "السابق"
                },
                loadingRecords: "جاري التحميل...",
                processing: "جاري المعالجة...",
                searchPlaceholder: "ابحث هنا..."
            },
            order: [[4, 'desc']], // ترتيب حسب موعد التسليم
            columnDefs: [
                { orderable: false, targets: [-1] } // عمود الإجراءات غير قابل للترتيب
            ],
            drawCallback: function() {
                $('[data-bs-toggle="tooltip"]').tooltip();
            }
        });
        window.myDeliveriesTableInitialized = true;
    } catch (error) {
        console.warn('My Deliveries DataTable initialization failed:', error);
    }
}

/**
 * Initialize All Deliveries DataTable
 * تهيئة جدول جميع التسليمات
 */
function initializeAllDeliveriesTable() {
    if (window.deliveriesTableInitialized) {
        console.log('All Deliveries DataTable already initialized, skipping...');
        return;
    }

    const hasData = $('#deliveriesTable tbody tr').length > 0 &&
                   !$('#deliveriesTable tbody tr:first td').attr('colspan');

    if (!hasData) {
        console.log('No all deliveries data available, skipping DataTable initialization');
        return;
    }

    if ($.fn.DataTable.isDataTable('#deliveriesTable')) {
        $('#deliveriesTable').DataTable().destroy();
    }

    try {
        deliveriesTable = $('#deliveriesTable').DataTable({
            responsive: true,
            pageLength: 25,
            language: {
                emptyTable: "لا توجد تسليمات متاحة",
                info: "عرض _START_ إلى _END_ من أصل _TOTAL_ تسليمة",
                infoEmpty: "عرض 0 إلى 0 من أصل 0 تسليمة",
                infoFiltered: "(مفلتر من _MAX_ إجمالي التسليمات)",
                lengthMenu: "عرض _MENU_ تسليمات",
                loadingRecords: "جاري التحميل...",
                processing: "جاري المعالجة...",
                search: "البحث:",
                zeroRecords: "لم يتم العثور على تسليمات مطابقة",
                searchPlaceholder: "ابحث هنا...",
                paginate: {
                    first: "الأول",
                    last: "الأخير",
                    next: "التالي",
                    previous: "السابق"
                }
            },
            order: [[5, 'desc']], // ترتيب حسب موعد التسليم
            columnDefs: [
                { orderable: false, targets: [9] } // عمود الإجراءات غير قابل للترتيب (10 أعمدة، آخر عمود index 9)
            ],
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                 '<"row"<"col-sm-12"tr>>' +
                 '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            drawCallback: function() {
                $('[data-bs-toggle="tooltip"]').tooltip();
            }
        });
        window.deliveriesTableInitialized = true;
    } catch (error) {
        console.warn('All Deliveries DataTable initialization failed:', error);
    }
}

/**
 * Initialize tables on page load
 * تهيئة الجداول عند تحميل الصفحة
 */
function initializeTablesOnLoad() {
    // التحقق من وجود بيانات قبل تهيئة DataTable
    const hasData = $('#deliveriesTable tbody tr').length > 0 &&
                   !$('#deliveriesTable tbody tr:first td').attr('colspan');

    if (hasData && !window.deliveriesTableInitialized) {
        // تدمير DataTable إذا كان موجوداً مسبقاً
        if ($.fn.DataTable.isDataTable('#deliveriesTable')) {
            $('#deliveriesTable').DataTable().destroy();
        }

        // تهيئة DataTable مرة واحدة فقط
        try {
            $('#deliveriesTable').DataTable({
                responsive: true,
                pageLength: 25,
                language: {
                    emptyTable: "لا توجد تسليمات متاحة",
                    zeroRecords: "لا توجد نتائج مطابقة للبحث",
                    info: "عرض _START_ إلى _END_ من _TOTAL_ تسليمة",
                    infoEmpty: "عرض 0 إلى 0 من 0 تسليمات",
                    infoFiltered: "(مفلتر من _MAX_ إجمالي التسليمات)",
                    lengthMenu: "عرض _MENU_ تسليمات",
                    search: "بحث:",
                    paginate: {
                        first: "الأول",
                        last: "الأخير",
                        next: "التالي",
                        previous: "السابق"
                    },
                    loadingRecords: "جاري التحميل...",
                    processing: "جاري المعالجة...",
                    searchPlaceholder: "ابحث هنا..."
                },
                order: [[5, 'desc']], // ترتيب حسب موعد التسليم
                columnDefs: [
                    { orderable: false, targets: [9] } // عمود الإجراءات غير قابل للترتيب (10 أعمدة، آخر عمود index 9)
                ]
            });
            window.deliveriesTableInitialized = true;
        } catch (error) {
            console.warn('DataTable initialization failed:', error);
        }
    }

    // تهيئة جدول التسليمات الشخصية
    initializeMyDeliveriesTable();
}

/**
 * Handle tab switching for tables
 * التعامل مع تبديل التابز للجداول
 */
function handleTabSwitching() {
    // معالج تبديل التابز
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        const targetTab = $(e.target).attr('data-bs-target');

        if (targetTab === '#all-deliveries') {
            // تهيئة جدول جميع التسليمات عند التبديل إليه
            setTimeout(function() {
                initializeAllDeliveriesTable();
            }, 100);
        }
    });
}

/**
 * Destroy all tables
 * تدمير جميع الجداول
 */
function destroyAllTables() {
    if ($.fn.DataTable.isDataTable('#deliveriesTable')) {
        $('#deliveriesTable').DataTable().destroy();
    }

    if ($.fn.DataTable.isDataTable('#myDeliveriesTable')) {
        $('#myDeliveriesTable').DataTable().destroy();
    }

    // Reset initialization flags
    window.deliveriesTableInitialized = false;
    window.myDeliveriesTableInitialized = false;
}

/**
 * Initialize Projects DataTable
 * تهيئة جدول المشاريع
 */
function initializeProjectsTable() {
    if (window.projectsTableInitialized) {
        console.log('Projects DataTable already initialized, skipping...');
        return;
    }

    const hasData = $('#projectsTable tbody tr').length > 0 &&
                   !$('#projectsTable tbody tr:first td').attr('colspan');

    if (!hasData) {
        console.log('No projects data available, skipping DataTable initialization');
        return;
    }

    if ($.fn.DataTable.isDataTable('#projectsTable')) {
        $('#projectsTable').DataTable().destroy();
    }

    try {
        $('#projectsTable').DataTable({
            responsive: true,
            pageLength: 10,
            language: {
                emptyTable: "لا توجد مشاريع متاحة",
                info: "عرض _START_ إلى _END_ من أصل _TOTAL_ مشروع",
                infoEmpty: "عرض 0 إلى 0 من أصل 0 مشروع",
                infoFiltered: "(مفلتر من _MAX_ إجمالي المشاريع)",
                lengthMenu: "عرض _MENU_ مشروع",
                loadingRecords: "جاري التحميل...",
                processing: "جاري المعالجة...",
                search: "البحث:",
                zeroRecords: "لم يتم العثور على مشاريع مطابقة",
                searchPlaceholder: "ابحث هنا...",
                paginate: {
                    first: "الأول",
                    last: "الأخير",
                    next: "التالي",
                    previous: "السابق"
                }
            },
            order: [[2, 'desc']], // ترتيب حسب تاريخ بداية المشروع
            columnDefs: [
                { orderable: false, targets: [7] } // عمود الإجراءات غير قابل للترتيب
            ],
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                 '<"row"<"col-sm-12"tr>>' +
                 '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            drawCallback: function() {
                $('[data-bs-toggle="tooltip"]').tooltip();
            }
        });
        window.projectsTableInitialized = true;
    } catch (error) {
        console.warn('Projects DataTable initialization failed:', error);
    }
}

/**
 * Refresh table data
 * تحديث بيانات الجدول
 */
function refreshTableData() {
    destroyAllTables();
    setTimeout(() => {
        initializeTablesOnLoad();
    }, 100);
}
