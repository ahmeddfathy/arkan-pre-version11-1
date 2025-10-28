

// Global variables
let deliveriesTable;
let currentFilters = {};


window.deliveriesTableInitialized = false;
window.myDeliveriesTableInitialized = false;


function initializeDeliveriesPage() {

    initializeFilters();
    initializeEventHandlers();

    setInterval(refreshData, 300000);
}


function initializeFilters() {
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

    // Store current filters
    currentFilters = getFormFilters();
}

/**
 * Initialize event handlers
 * تهيئة معالجات الأحداث
 */
function initializeEventHandlers() {
    // Filter form submission
    $('#filtersForm').on('submit', function(e) {
        e.preventDefault();
        applyFilters();
    });

    // Real-time filter changes
    $('#filtersForm select, #filtersForm input').on('change', function() {
        if ($(this).attr('name') !== 'date_filter') {
            applyFilters();
        }
    });

    // Keyboard shortcuts
    $(document).keydown(function(e) {
        // Ctrl+R or F5 for refresh
        if ((e.ctrlKey && e.keyCode === 82) || e.keyCode === 116) {
            e.preventDefault();
            refreshData();
        }

        // Ctrl+E for export
        if (e.ctrlKey && e.keyCode === 69) {
            e.preventDefault();
            exportData();
        }
    });

    // Auto-save filter preferences
    $('#filtersForm').on('change', 'select, input', function() {
        saveFilterPreferences();
    });

    // Load saved filter preferences
    loadFilterPreferences();
}

/**
 * Apply filters
 * تطبيق الفلاتر
 */
function applyFilters() {
    showLoadingState();

    const filters = getFormFilters();
    currentFilters = filters;

    $.ajax({
        url: '/deliveries/data',
        method: 'GET',
        data: filters,
        success: function(response) {
            if (response.success) {
                updateDeliveriesTable(response.data);
                updateStatistics(response.data);
                clearProjectFilterFromTab();

                // مسح تمييز المشاريع إذا تم تطبيق فلاتر جديدة
                if (typeof clearProjectsTabFilter === 'function') {
                    clearProjectsTabFilter();
                }

                // الباك إند دلوقتي بيرجع المشاريع مفلترة أصلاً

                showSuccessMessage('تم تطبيق الفلاتر بنجاح');
            } else {
                showErrorMessage('حدث خطأ أثناء تطبيق الفلاتر');
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            showErrorMessage(response?.message || 'حدث خطأ أثناء تطبيق الفلاتر');
        },
        complete: function() {
            hideLoadingState();
        }
    });
}

/**
 * Get form filters
 * الحصول على فلاتر النموذج
 */
function getFormFilters() {
    const formData = new FormData(document.getElementById('filtersForm'));
    const filters = {};

    for (let [key, value] of formData.entries()) {
        if (value && value.trim() !== '') {
            filters[key] = value.trim();
        }
    }

    return filters;
}

/**
 * Update deliveries table
 * تحديث جدول التسليمات
 */
function updateDeliveriesTable(data) {
    // إعادة تحميل الصفحة لتجنب مشاكل DataTables
    const params = new URLSearchParams(currentFilters);
    const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
    window.location.href = newUrl;
}

/**
 * Update deliveries table data without reload
 * تحديث بيانات جدول التسليمات بدون إعادة تحميل
 */
function updateDeliveriesTableData(data) {
    console.log('Updating deliveries table with data:', data);

    // التحقق من وجود الجدول
    const tableBody = document.querySelector('#deliveriesTable tbody');
    if (!tableBody) {
        console.warn('Deliveries table body not found');
        return;
    }

    try {
        // تدمير DataTable إذا كان موجوداً
        if ($.fn.DataTable.isDataTable('#deliveriesTable')) {
            $('#deliveriesTable').DataTable().destroy();
            window.deliveriesTableInitialized = false;
        }

        // مسح المحتوى الحالي
        tableBody.innerHTML = '';

        if (data && data.length > 0) {
            // إضافة البيانات الجديدة
            data.forEach(delivery => {
                try {
                    const row = createDeliveryRow(delivery);
                    tableBody.appendChild(row);
                } catch (error) {
                    console.error('Error creating delivery row:', error, delivery);
                }
            });

            // إعادة تهيئة DataTable
            setTimeout(() => {
                try {
                    initializeAllDeliveriesTable();
                    console.log('DataTable reinitialized successfully');
                } catch (error) {
                    console.error('Error reinitializing DataTable:', error);
                }
            }, 200);
        } else {
            // عرض رسالة عدم وجود بيانات
            tableBody.innerHTML = `
                <tr>
                    <td colspan="11" class="text-center py-4">
                        <div class="text-muted">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>لا توجد تسليمات متاحة للمشروع المحدد</p>
                        </div>
                    </td>
                </tr>
            `;
            console.log('No data found, showing empty message');
        }
    } catch (error) {
        console.error('Error updating deliveries table:', error);
        showErrorMessage('حدث خطأ أثناء تحديث جدول التسليمات');
    }
}

/**
 * Create delivery row HTML
 * إنشاء صف تسليمة HTML
 */
function createDeliveryRow(delivery) {
    const row = document.createElement('tr');
    row.className = `delivery-row`;

    row.innerHTML = `
        <td>
            <div class="d-flex flex-column">
                <strong>${delivery.project?.name || 'غير محدد'}</strong>
                ${delivery.project?.code ? `<small class="text-muted">#${delivery.project.code}</small>` : ''}
                ${delivery.project?.client ? `<small class="text-info">${delivery.project.client.name}</small>` : ''}
            </div>
        </td>
        <td>
            <div class="d-flex flex-column">
                <span>${delivery.service?.name || 'غير محدد'}</span>
                ${delivery.service?.department ? `<small class="text-muted">${delivery.service.department}</small>` : ''}
            </div>
        </td>
        <td>
            <div class="d-flex flex-column">
                <strong>${delivery.user?.name || 'غير محدد'}</strong>
                ${delivery.user?.hierarchy_title ? `<span class="hierarchy-badge">${delivery.user.hierarchy_title}</span>` : ''}
            </div>
        </td>
        <td>${delivery.user?.department || 'غير محدد'}</td>
        <td>
            ${delivery.team?.name || (delivery.user?.team_info?.name || 'غير محدد')}
        </td>
        <td>
            ${delivery.delivered_at ?
                `<div class="d-flex flex-column">
                    <span class="badge bg-primary">
                        <i class="fas fa-calendar-check"></i>
                        ${new Date(delivery.delivered_at).toLocaleDateString('ar-EG')}
                    </span>
                    <small class="text-muted mt-1">
                        ${new Date(delivery.delivered_at).toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit' })}
                    </small>
                </div>` :
                '<span class="badge bg-secondary"><i class="fas fa-clock"></i> لم يسلم بعد</span>'
            }
        </td>
        <td>
            ${delivery.administrative_approval ?
                `<span class="badge bg-success">
                    <i class="fas fa-check-circle"></i> معتمد
                </span>
                ${delivery.administrative_approval_at ?
                    `<br><small class="text-muted">${new Date(delivery.administrative_approval_at).toLocaleString('ar-EG', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' })}</small>` : ''
                }
                ${delivery.administrative_approver_name ?
                    `<br><small class="text-info">${delivery.administrative_approver_name}</small>` : ''
                }` :
                (delivery.required_approvals && delivery.required_approvals.needs_administrative ?
                    '<span class="badge bg-warning text-dark"><i class="fas fa-hourglass-half"></i> في الانتظار</span>' :
                    '<span class="text-muted">غير مطلوب</span>')
            }
        </td>
        <td>
            ${delivery.technical_approval ?
                `<span class="badge bg-success">
                    <i class="fas fa-check-circle"></i> معتمد
                </span>
                ${delivery.technical_approval_at ?
                    `<br><small class="text-muted">${new Date(delivery.technical_approval_at).toLocaleString('ar-EG', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' })}</small>` : ''
                }
                ${delivery.technical_approver_name ?
                    `<br><small class="text-info">${delivery.technical_approver_name}</small>` : ''
                }` :
                (delivery.required_approvals && delivery.required_approvals.needs_technical ?
                    '<span class="badge bg-warning text-dark"><i class="fas fa-hourglass-half"></i> في الانتظار</span>' :
                    '<span class="text-muted">غير مطلوب</span>')
            }
        </td>
        <td>
            ${delivery.deadline ?
                `${new Date(delivery.deadline).toLocaleDateString('ar-EG')}${delivery.days_remaining !== null ?
                    `<br><small class="text-muted">${delivery.days_remaining > 0 ?
                        `باقي ${delivery.days_remaining} يوم` :
                        delivery.days_remaining < 0 ?
                            `متأخر ${Math.abs(delivery.days_remaining)} يوم` :
                            'اليوم'
                    }</small>` : ''
                }` :
                '<span class="text-muted">غير محدد</span>'
            }
        </td>
        <td>
            <div class="d-flex flex-wrap gap-1">
                ${delivery.can_approve_administrative && !delivery.administrative_approval ?
                    `<button type="button" class="btn btn-outline-primary btn-action" onclick="grantAdministrativeApproval(${delivery.id})">
                        <i class="fas fa-user-check"></i> اعتماد إداري
                    </button>` : ''
                }
                ${delivery.can_approve_administrative && delivery.administrative_approval ?
                    `<button type="button" class="btn btn-outline-warning btn-action" onclick="revokeAdministrativeApproval(${delivery.id})">
                        <i class="fas fa-user-times"></i> إلغاء اعتماد إداري
                    </button>` : ''
                }
                ${delivery.can_approve_technical && !delivery.technical_approval ?
                    `<button type="button" class="btn btn-outline-success btn-action" onclick="grantTechnicalApproval(${delivery.id})">
                        <i class="fas fa-cogs"></i> اعتماد فني
                    </button>` : ''
                }
                ${delivery.can_approve_technical && delivery.technical_approval ?
                    `<button type="button" class="btn btn-outline-danger btn-action" onclick="revokeTechnicalApproval(${delivery.id})">
                        <i class="fas fa-times-circle"></i> إلغاء اعتماد فني
                    </button>` : ''
                }
                <button type="button" class="btn btn-info btn-action" onclick="viewDeliveryDetails(${delivery.id})">
                    <i class="fas fa-eye"></i> عرض
                </button>
            </div>
        </td>
    `;

    return row;
}

/**
 * Update statistics
 * تحديث الإحصائيات
 */
function updateStatistics(data) {
    if (!Array.isArray(data)) return;

    const total = data.length;
    const overdue = data.filter(d => d.deadline_status === 'overdue').length;

    $('#total-deliveries').text(total);
    $('#overdue-deliveries').text(overdue);
}

/**
 * Refresh data
 * تحديث البيانات
 */
function refreshData() {
    showLoadingState('جاري تحديث البيانات...');

    // Add a small delay to show the loading state
    setTimeout(() => {
        location.reload();
    }, 500);
}

/**
 * Export data
 * تصدير البيانات
 */
function exportData() {
    showLoadingState('جاري تحضير الملف للتصدير...');

    const params = new URLSearchParams(currentFilters);
    const exportUrl = `/deliveries/export?${params.toString()}`;

    // Create a temporary link and click it
    const link = document.createElement('a');
    link.href = exportUrl;
    link.download = `employee-deliveries-${new Date().toISOString().split('T')[0]}.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    setTimeout(() => {
        hideLoadingState();
        showSuccessMessage('تم تصدير البيانات بنجاح');
    }, 1000);
}

/**
 * Clear filters
 * مسح الفلاتر
 */
function clearFilters() {
    Swal.fire({
        title: 'مسح الفلاتر',
        text: 'هل تريد مسح جميع الفلاتر والعودة للعرض الافتراضي؟',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#6c757d',
        cancelButtonColor: '#007bff',
        confirmButtonText: '<i class="fas fa-eraser"></i> نعم، امسح الفلاتر',
        cancelButtonText: '<i class="fas fa-times"></i> إلغاء'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('filtersForm').reset();
            $('#custom-date-range').hide();
            clearFilterPreferences();
            clearProjectFilterFromTab();

            // مسح فلاتر تاب المشاريع
            if (typeof clearProjectsTabFilter === 'function') {
                clearProjectsTabFilter();
            }

            window.location.href = window.location.pathname;
        }
    });
}

/**
 * Save filter preferences
 * حفظ تفضيلات الفلاتر
 */
function saveFilterPreferences() {
    try {
        const filters = getFormFilters();
        localStorage.setItem('deliveries_filters', JSON.stringify(filters));
    } catch (error) {
        console.warn('Could not save filter preferences:', error);
    }
}

/**
 * Load filter preferences
 * تحميل تفضيلات الفلاتر
 */
function loadFilterPreferences() {
    try {
        const saved = localStorage.getItem('deliveries_filters');
        if (saved) {
            const filters = JSON.parse(saved);

            // Apply saved filters to form
            Object.keys(filters).forEach(key => {
                const element = document.querySelector(`[name="${key}"]`);
                if (element) {
                    element.value = filters[key];

                    // Trigger change event for date filter
                    if (key === 'date_filter') {
                        $(element).trigger('change');
                    }
                }
            });
        }
    } catch (error) {
        console.warn('Could not load filter preferences:', error);
    }
}

/**
 * Clear filter preferences
 * مسح تفضيلات الفلاتر
 */
function clearFilterPreferences() {
    try {
        localStorage.removeItem('deliveries_filters');
    } catch (error) {
        console.warn('Could not clear filter preferences:', error);
    }
}

/**
 * Show loading state
 * عرض حالة التحميل
 */
function showLoadingState(message = 'جاري التحميل...') {
    Swal.fire({
        title: message,
        html: '<div class="loading-spinner mx-auto"></div>',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
}

/**
 * Hide loading state
 * إخفاء حالة التحميل
 */
function hideLoadingState() {
    Swal.close();
}

/**
 * Show success message
 * عرض رسالة نجاح
 */
function showSuccessMessage(message) {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });

    Toast.fire({
        icon: 'success',
        title: message
    });
}

/**
 * Show error message
 * عرض رسالة خطأ
 */
function showErrorMessage(message) {
    Swal.fire({
        icon: 'error',
        title: 'خطأ!',
        text: message,
        confirmButtonText: 'حسناً',
        confirmButtonColor: '#dc3545'
    });
}

/**
 * Show info message
 * عرض رسالة معلومات
 */
function showInfoMessage(message) {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 4000,
        timerProgressBar: true
    });

    Toast.fire({
        icon: 'info',
        title: message
    });
}

/**
 * Initialize tooltips
 * تهيئة التلميحات
 */
function initializeTooltips() {
    $('[data-bs-toggle="tooltip"]').tooltip({
        trigger: 'hover',
        placement: 'auto'
    });
}

/**
 * Format date for display
 * تنسيق التاريخ للعرض
 */
function formatDate(dateString, includeTime = false) {
    if (!dateString) return 'غير محدد';

    const date = new Date(dateString);
    const options = {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    };

    if (includeTime) {
        options.hour = '2-digit';
        options.minute = '2-digit';
    }

    return date.toLocaleDateString('ar-EG', options);
}

/**
 * Get relative time
 * الحصول على الوقت النسبي
 */
function getRelativeTime(dateString) {
    if (!dateString) return '';

    const date = new Date(dateString);
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);

    if (diffInSeconds < 60) return 'منذ لحظات';
    if (diffInSeconds < 3600) return `منذ ${Math.floor(diffInSeconds / 60)} دقيقة`;
    if (diffInSeconds < 86400) return `منذ ${Math.floor(diffInSeconds / 3600)} ساعة`;

    const diffInDays = Math.floor(diffInSeconds / 86400);
    if (diffInDays === 1) return 'منذ يوم واحد';
    if (diffInDays < 7) return `منذ ${diffInDays} أيام`;
    if (diffInDays < 30) return `منذ ${Math.floor(diffInDays / 7)} أسابيع`;

    return formatDate(dateString);
}

/**
 * Initialize projects table on tab switch
 * تهيئة جدول المشاريع عند التبديل للتاب
 */
function initializeProjectsTableOnTabSwitch() {
    // تهيئة جدول المشاريع عند التبديل للتاب
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        const targetTab = $(e.target).attr('data-bs-target');

        if (targetTab === '#projects-deadlines') {
            // تهيئة جدول المشاريع عند التبديل إليه
            setTimeout(function() {
                initializeProjectsTable();
            }, 100);
        }
    });
}

/**
 * Update tab title with project name
 * تحديث عنوان التاب ليعكس اسم المشروع
 */
function updateTabTitleWithProject(projectId) {
    try {
        // البحث عن اسم المشروع في الجدول
        const projectRow = document.querySelector(`button[onclick="viewProjectDeliveries(${projectId})"]`);
        let projectName = '';

        if (projectRow) {
            const strongElement = projectRow.closest('tr').querySelector('strong');
            projectName = strongElement ? strongElement.textContent.trim() : `مشروع ${projectId}`;
        } else {
            // محاولة الحصول على اسم المشروع من الفلتر المحدد
            const projectFilter = document.querySelector('select[name="project_id"]');
            if (projectFilter && projectFilter.value == projectId) {
                const selectedOption = projectFilter.querySelector(`option[value="${projectId}"]`);
                projectName = selectedOption ? selectedOption.textContent.trim() : `مشروع ${projectId}`;
            } else {
                projectName = `مشروع ${projectId}`;
            }
        }

        // مسح أي أيقون مشروع سابق من جميع التابز أولاً
        clearProjectFilterFromTab();

        // مسح إضافي باستخدام DOM manipulation (أكثر أماناً)
        document.querySelectorAll('.project-filter-badge').forEach(badge => {
            console.log('Removing badge via DOM:', badge);
            badge.remove();
        });

        // تحديد التاب النشط أو التاب المناسب لإضافة الأيقون
        const activeTab = document.querySelector('.nav-link.active');
        const activeTabId = activeTab ? activeTab.id : null;

        // إضافة الأيقون للتاب النشط فقط، أو للتاب المناسب
        let targetTabId = activeTabId;

        // إذا لم يكن هناك تاب نشط، استخدم التاب المناسب حسب السياق
        if (!targetTabId) {
            const allDeliveriesTab = document.getElementById('all-deliveries-tab');
            const myDeliveriesTab = document.getElementById('my-deliveries-tab');

            if (allDeliveriesTab) {
                targetTabId = 'all-deliveries-tab';
            } else if (myDeliveriesTab) {
                targetTabId = 'my-deliveries-tab';
            }
        }

        if (targetTabId) {
            const tab = document.getElementById(targetTabId);
            if (tab) {
                const originalText = tab.innerHTML;
                console.log(`Adding badge to tab ${targetTabId}, original:`, originalText);

                // التأكد من عدم وجود badge سابقة بطريقة قوية
                let cleanText = originalText;

                // مسح كل الـ badges بطرق مختلفة للتأكد
                cleanText = cleanText.replace(/<span[^>]*class="[^"]*project-filter-badge[^"]*"[^>]*>.*?<\/span>/g, '');
                cleanText = cleanText.replace(/<span[^>]*project-filter-badge[^>]*>.*?<\/span>/g, '');
                cleanText = cleanText.replace(/<span class="project-filter-badge.*?<\/span>/g, '');
                cleanText = cleanText.trim();

                console.log(`Clean text:`, cleanText);

                // إضافة الأيقون الجديد
                const newBadge = `<span class="project-filter-badge badge bg-info ms-2" title="مفلتر حسب المشروع: ${projectName}">
                    <i class="fas fa-filter"></i> ${projectName}
                </span>`;

                tab.innerHTML = cleanText + newBadge;
                console.log(`Added new badge:`, newBadge);
            }
        }

        console.log('Updated tab title with project:', projectName);
    } catch (error) {
        console.error('Error updating tab title with project:', error);
    }
}

/**
 * Clear project filter from tab title
 * مسح فلتر المشروع من عنوان التاب
 */
function clearProjectFilterFromTab() {
    try {
        // مسح باستخدام DOM manipulation أولاً (الطريقة الأكثر أماناً)
        const existingBadges = document.querySelectorAll('.project-filter-badge');
        if (existingBadges.length > 0) {
            console.log(`Found ${existingBadges.length} existing badges to remove`);
            existingBadges.forEach(badge => {
                console.log('Removing badge via DOM:', badge.outerHTML);
                badge.remove();
            });
        }

        // مسح الأيقون من جميع التابز (طريقة إضافية)
        const tabs = ['all-deliveries-tab', 'my-deliveries-tab', 'projects-deadlines-tab'];
        let clearedCount = 0;

        tabs.forEach(tabId => {
            const tab = document.getElementById(tabId);
            if (tab) {
                const originalText = tab.innerHTML;
                console.log(`Original text for ${tabId}:`, originalText);

                // إزالة أي عنوان مشروع سابق - استخدام regex أقوى
                let cleanText = originalText;

                // مسح كل الـ badges بطرق مختلفة للتأكد
                cleanText = cleanText.replace(/<span[^>]*class="[^"]*project-filter-badge[^"]*"[^>]*>.*?<\/span>/g, '');
                cleanText = cleanText.replace(/<span[^>]*project-filter-badge[^>]*>.*?<\/span>/g, '');
                cleanText = cleanText.replace(/<span class="project-filter-badge.*?<\/span>/g, '');

                // مسح مسافات إضافية
                cleanText = cleanText.trim();

                console.log(`Clean text for ${tabId}:`, cleanText);

                // تحديث فقط إذا كان هناك تغيير
                if (originalText !== cleanText) {
                    tab.innerHTML = cleanText;
                    clearedCount++;
                    console.log(`Cleared filter badge from tab: ${tabId}`);
                }
            }
        });

        if (clearedCount > 0) {
            console.log(`Cleared project filter badges from ${clearedCount} tabs`);
        }
    } catch (error) {
        console.error('Error clearing project filter from tabs:', error);
    }
}

/**
 * View deliveries for a specific project
 * عرض التسليمات لمشروع محدد
 */
function viewProjectDeliveries(projectId) {
    console.log('Viewing deliveries for project:', projectId);

    // تطبيق فلتر المشروع
    const projectFilter = document.querySelector('select[name="project_id"]');
    if (projectFilter) {
        projectFilter.value = projectId;
    }

    // التبديل إلى تاب "جميع التسليمات"
    const allDeliveriesTab = document.querySelector('button[data-bs-target="#all-deliveries"]');
    if (allDeliveriesTab) {
        const tab = new bootstrap.Tab(allDeliveriesTab);
        tab.show();
    }

    // تحديث عنوان التاب
    updateTabTitleWithProject(projectId);

    // تطبيق الفلتر
    applyFilters();

    // عرض رسالة
    showInfoMessage('تم فلترة التسليمات حسب المشروع المحدد');
}
