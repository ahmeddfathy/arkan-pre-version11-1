
function acknowledgeDelivery(deliveryId) {
    Swal.fire({
        title: 'تأكيد الاستلام',
        text: 'هل تريد تأكيد استلام هذه التسليمة؟',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-check"></i> نعم، أكد الاستلام',
        cancelButtonText: '<i class="fas fa-times"></i> إلغاء',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return $.ajax({
                url: `/deliveries/${deliveryId}/acknowledge`,
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            const response = result.value;
            if (response.success) {
                Swal.fire({
                    title: 'تم بنجاح!',
                    text: response.message,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    refreshData();
                });
            } else {
                showErrorMessage(response.message);
            }
        }
    }).catch((error) => {
        if (error.dismiss !== Swal.DismissReason.cancel) {
            const response = error.responseJSON;
            showErrorMessage(response?.message || 'حدث خطأ أثناء تأكيد الاستلام');
        }
    });
}


function viewDeliveryDetails(deliveryId) {
    $('#deliveryDetailsContent').html(`
        <div class="text-center">
            <div class="loading-spinner"></div>
            <p class="mt-2">جاري تحميل التفاصيل...</p>
        </div>
    `);

    $('#deliveryDetailsModal').modal('show');

    // Simulate loading - يمكن استبداله بـ AJAX call حقيقي
    setTimeout(() => {
        $('#deliveryDetailsContent').html(`
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-primary mb-3">
                        <i class="fas fa-info-circle"></i>
                        معلومات التسليمة
                    </h6>
                    <div class="mb-3">
                        <strong>رقم التسليمة:</strong> ${deliveryId}
                    </div>
                    <div class="mb-3">
                        <strong>تاريخ الإنشاء:</strong> ${new Date().toLocaleDateString('ar-EG')}
                    </div>
                    <div class="mb-3">
                        <strong>آخر تحديث:</strong> ${new Date().toLocaleString('ar-EG')}
                    </div>
                </div>
                <div class="col-md-6">
                    <h6 class="text-success mb-3">
                        <i class="fas fa-chart-line"></i>
                        إحصائيات سريعة
                    </h6>
                    <div class="mb-3">
                        <strong>حالة التأكيد:</strong>
                        <span class="badge bg-info">قيد المراجعة</span>
                    </div>
                    <div class="mb-3">
                        <strong>الأولوية:</strong>
                        <span class="badge bg-warning">متوسطة</span>
                    </div>
                </div>
            </div>
            <hr>
            <div class="alert alert-info">
                <i class="fas fa-lightbulb"></i>
                <strong>ملاحظة:</strong> يمكن إضافة المزيد من التفاصيل هنا حسب الحاجة، مثل تاريخ المهام المرتبطة، التعليقات، والمرفقات.
            </div>
        `);
    }, 1500);
}

/**
 * View delivery details (Alternative implementation)
 * عرض تفاصيل التسليمة (تنفيذ بديل)
 */
function viewDeliveryDetailsAlternative(deliveryId) {
    $('#deliveryDetailsContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> جاري التحميل...</div>');
    $('#deliveryDetailsModal').modal('show');

    // هنا يمكن إضافة AJAX call لجلب تفاصيل التسليمة
    // للآن سنعرض رسالة بسيطة
    setTimeout(() => {
        $('#deliveryDetailsContent').html(`
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                تفاصيل التسليمة رقم ${deliveryId}
                <br><br>
                <small>يمكن إضافة المزيد من التفاصيل هنا حسب الحاجة</small>
            </div>
        `);
    }, 1000);
}

/**
 * Bulk acknowledge deliveries
 * تأكيد استلام متعدد للتسليمات
 */
function bulkAcknowledgeDeliveries() {
    const selectedDeliveries = getSelectedDeliveries();

    if (selectedDeliveries.length === 0) {
        showErrorMessage('يرجى اختيار تسليمات للتأكيد');
        return;
    }

    Swal.fire({
        title: 'تأكيد الاستلام المتعدد',
        text: `هل تريد تأكيد استلام ${selectedDeliveries.length} تسليمة؟`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-check"></i> نعم، أكد الاستلام',
        cancelButtonText: '<i class="fas fa-times"></i> إلغاء',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return $.ajax({
                url: '/deliveries/bulk-acknowledge',
                method: 'POST',
                data: {
                    delivery_ids: selectedDeliveries,
                    _token: $('meta[name="csrf-token"]').attr('content')
                }
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            const response = result.value;
            if (response.success) {
                Swal.fire({
                    title: 'تم بنجاح!',
                    text: response.message,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    refreshData();
                });
            } else {
                showErrorMessage(response.message);
            }
        }
    }).catch((error) => {
        if (error.dismiss !== Swal.DismissReason.cancel) {
            const response = error.responseJSON;
            showErrorMessage(response?.message || 'حدث خطأ أثناء تأكيد الاستلام المتعدد');
        }
    });
}

/**
 * Get selected deliveries
 * الحصول على التسليمات المختارة
 */
function getSelectedDeliveries() {
    const selectedDeliveries = [];
    $('input[name="delivery_ids[]"]:checked').each(function() {
        selectedDeliveries.push($(this).val());
    });
    return selectedDeliveries;
}

/**
 * Select all deliveries
 * اختيار جميع التسليمات
 */
function selectAllDeliveries() {
    const isChecked = $('#selectAll').prop('checked');
    $('input[name="delivery_ids[]"]').prop('checked', isChecked);
    updateBulkActionsVisibility();
}

/**
 * Update bulk actions visibility
 * تحديث رؤية الإجراءات المتعددة
 */
function updateBulkActionsVisibility() {
    const selectedCount = getSelectedDeliveries().length;
    const bulkActionsContainer = $('#bulkActionsContainer');

    if (selectedCount > 0) {
        bulkActionsContainer.show();
        $('#selectedCount').text(selectedCount);
    } else {
        bulkActionsContainer.hide();
    }
}

/**
 * Handle delivery selection change
 * التعامل مع تغيير اختيار التسليمة
 */
function handleDeliverySelectionChange() {
    updateBulkActionsVisibility();

    // Update select all checkbox state
    const totalDeliveries = $('input[name="delivery_ids[]"]').length;
    const selectedDeliveries = getSelectedDeliveries().length;

    if (selectedDeliveries === 0) {
        $('#selectAll').prop('indeterminate', false).prop('checked', false);
    } else if (selectedDeliveries === totalDeliveries) {
        $('#selectAll').prop('indeterminate', false).prop('checked', true);
    } else {
        $('#selectAll').prop('indeterminate', true);
    }
}

/**
 * Initialize delivery actions
 * تهيئة إجراءات التسليمات
 */
function initializeDeliveryActions() {
    // Handle select all checkbox
    $('#selectAll').on('change', selectAllDeliveries);

    // Handle individual delivery selection
    $(document).on('change', 'input[name="delivery_ids[]"]', handleDeliverySelectionChange);

    // Initialize bulk actions visibility
    updateBulkActionsVisibility();
}

/**
 * Show delivery status modal
 * عرض نافذة حالة التسليمة
 */
function showDeliveryStatusModal(deliveryId, currentStatus) {
    $('#deliveryStatusModal').modal('show');
    $('#deliveryIdInput').val(deliveryId);
    $('#currentStatus').text(currentStatus);
}

/**
 * Update delivery status
 * تحديث حالة التسليمة
 */
function updateDeliveryStatus() {
    const deliveryId = $('#deliveryIdInput').val();
    const newStatus = $('#statusSelect').val();
    const notes = $('#statusNotes').val();

    if (!newStatus) {
        showErrorMessage('يرجى اختيار حالة جديدة');
        return;
    }

    showLoadingState('جاري تحديث الحالة...');

    $.ajax({
        url: `/deliveries/${deliveryId}/status`,
        method: 'POST',
        data: {
            status: newStatus,
            notes: notes,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                $('#deliveryStatusModal').modal('hide');
                showSuccessMessage(response.message);
                refreshData();
            } else {
                showErrorMessage(response.message);
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            showErrorMessage(response?.message || 'حدث خطأ أثناء تحديث الحالة');
        },
        complete: function() {
            hideLoadingState();
        }
    });
}

/**
 * Print delivery report
 * طباعة تقرير التسليمة
 */
function printDeliveryReport(deliveryId) {
    const printUrl = `/deliveries/${deliveryId}/print`;
    window.open(printUrl, '_blank');
}

/**
 * Download delivery attachment
 * تحميل مرفق التسليمة
 */
function downloadDeliveryAttachment(attachmentId, fileName) {
    const downloadUrl = `/deliveries/attachments/${attachmentId}/download`;

    // Create a temporary link and click it
    const link = document.createElement('a');
    link.href = downloadUrl;
    link.download = fileName;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

/**
 * Add delivery comment
 * إضافة تعليق للتسليمة
 */
function addDeliveryComment(deliveryId) {
    const comment = $('#deliveryComment').val().trim();

    if (!comment) {
        showErrorMessage('يرجى إدخال تعليق');
        return;
    }

    showLoadingState('جاري إضافة التعليق...');

    $.ajax({
        url: `/deliveries/${deliveryId}/comments`,
        method: 'POST',
        data: {
            comment: comment,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                $('#deliveryComment').val('');
                showSuccessMessage(response.message);
                loadDeliveryComments(deliveryId);
            } else {
                showErrorMessage(response.message);
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            showErrorMessage(response?.message || 'حدث خطأ أثناء إضافة التعليق');
        },
        complete: function() {
            hideLoadingState();
        }
    });
}

/**
 * Load delivery comments
 * تحميل تعليقات التسليمة
 */
function loadDeliveryComments(deliveryId) {
    $.ajax({
        url: `/deliveries/${deliveryId}/comments`,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                $('#commentsContainer').html(response.html);
            }
        },
        error: function(xhr) {
            console.error('Error loading comments:', xhr);
        }
    });
}

/**
 * View project deliveries
 * عرض تسليمات المشروع - فلترة الجدول الحالي
 */
function viewProjectDeliveries(projectId) {
    // عرض حالة التحميل
    showLoadingState('جاري فلترة التسليمات حسب المشروع...');

    try {
        // مسح أي فلاتر مشاريع سابقة من التابز أولاً
        clearProjectFilterFromTab();

        // مسح إضافي للتأكد 100%
        document.querySelectorAll('.project-filter-badge').forEach(badge => {
            console.log('Force removing badge:', badge.outerHTML);
            badge.remove();
        });

    // إضافة فلتر المشروع إلى النموذج
    const projectFilter = document.querySelector('select[name="project_id"]');
    if (projectFilter) {
        projectFilter.value = projectId;
    }

        // التحقق من وجود تاب "جميع التسليمات" والتبديل إليه
    const allDeliveriesTab = document.getElementById('all-deliveries-tab');
        if (allDeliveriesTab) {
            // التبديل إلى التاب إذا لم يكن نشطاً
            if (!allDeliveriesTab.classList.contains('active')) {
        allDeliveriesTab.click();
    }

            // انتظار قليل للتأكد من التبديل
    setTimeout(() => {
                applyProjectFilter(projectId);
            }, 150);
        } else {
            // إذا لم يكن التاب موجود، استخدم الطريقة البديلة
            applyProjectFilterToCurrentTab(projectId);
        }
    } catch (error) {
        console.error('Error in viewProjectDeliveries:', error);
        hideLoadingState();
        showErrorMessage('حدث خطأ أثناء تطبيق فلتر المشروع');
    }
}

/**
 * Apply project filter to deliveries
 * تطبيق فلتر المشروع على التسليمات
 */
function applyProjectFilter(projectId) {
        // إنشاء فلاتر جديدة مع المشروع المحدد
        const filters = {
            project_id: projectId
        };

    // إضافة الفلاتر الأخرى من النموذج (إن وجدت)
    try {
        const formFilters = getFormFilters();
        Object.assign(filters, formFilters);
        // التأكد من أن project_id هو المشروع المحدد
        filters.project_id = projectId;
    } catch (error) {
        console.warn('Could not get form filters, using project filter only');
    }

        // تحديث الفلاتر الحالية
        currentFilters = filters;

    // جلب البيانات المفلترة
        $.ajax({
            url: '/deliveries/data',
            method: 'GET',
            data: filters,
            success: function(response) {
            try {
                if (response.success) {
                    // تحديث البيانات في الجدول
                    updateDeliveriesTableData(response.data);

                    // تحديث الإحصائيات الرئيسية والخاصة بالتاب
                    updateStatistics(response.data);
                    updateActiveTabStatistics(response.data);

                    // تحديث عنوان التاب ليعكس المشروع المحدد
                    updateTabTitleWithProject(projectId);

                    // تحديث رقم badge التاب
                    updateTabBadgeCount(response.data.length);

                    // تحديث تاب المشاريع ليعكس الفلتر المطبق
                    updateProjectsTabForFilter(projectId);

                    showSuccessMessage(`تم فلترة التسليمات حسب المشروع المحدد (${response.data.length} تسليمة)`);
                } else {
                    showErrorMessage(response.message || 'حدث خطأ أثناء تطبيق الفلتر');
                }
            } catch (error) {
                console.error('Error processing response:', error);
                showErrorMessage('حدث خطأ أثناء معالجة البيانات');
                }
            },
            error: function(xhr) {
            console.error('AJAX Error:', xhr);
                const response = xhr.responseJSON;
                showErrorMessage(response?.message || 'حدث خطأ أثناء تطبيق الفلتر');
            },
            complete: function() {
                hideLoadingState();
            }
        });
}

/**
 * Apply project filter to current tab (fallback method)
 * تطبيق فلتر المشروع على التاب الحالي (طريقة بديلة)
 */
function applyProjectFilterToCurrentTab(projectId) {
    console.log('Applying project filter to current tab:', projectId);

    try {
        // التحقق من التاب الحالي النشط
        const activeTab = document.querySelector('.nav-link.active');
        const activeTabTarget = activeTab ? activeTab.getAttribute('data-bs-target') : null;

        console.log('Active tab:', activeTabTarget);

        // إضافة المشروع للفلتر
        const projectFilter = document.querySelector('select[name="project_id"]');
        if (projectFilter) {
            projectFilter.value = projectId;
        }

        // تحديد الطريقة المناسبة حسب التاب النشط
        if (activeTabTarget === '#my-deliveries') {
            // إذا كان في تاب التسليمات الشخصية، نحدث الجدول مباشرة
            applyProjectFilterToMyDeliveries(projectId);
        } else {
            // للتابز الأخرى، استخدم إعادة تحميل الصفحة
            const form = document.getElementById('filtersForm');
            if (form) {
                form.submit();
            } else {
                // إعادة تحميل مع المعاملات
                const params = new URLSearchParams(window.location.search);
                params.set('project_id', projectId);
                window.location.href = window.location.pathname + '?' + params.toString();
            }
        }
    } catch (error) {
        console.error('Error in applyProjectFilterToCurrentTab:', error);
        // طريقة احتياطية - إعادة تحميل الصفحة
        const params = new URLSearchParams(window.location.search);
        params.set('project_id', projectId);
        window.location.href = window.location.pathname + '?' + params.toString();
    }
}

/**
 * Apply project filter to my deliveries tab
 * تطبيق فلتر المشروع على تاب التسليمات الشخصية
 */
function applyProjectFilterToMyDeliveries(projectId) {
    console.log('Filtering my deliveries by project:', projectId);

    const myDeliveriesTable = document.getElementById('myDeliveriesTable');
    if (!myDeliveriesTable) {
        console.warn('My deliveries table not found');
        return;
    }

    // إخفاء الصفوف التي لا تنتمي للمشروع المحدد
    const rows = myDeliveriesTable.querySelectorAll('tbody tr');
    let visibleRows = 0;

    rows.forEach(row => {
        // تجاهل صف "لا توجد بيانات"
        if (row.querySelector('td[colspan]')) {
            return;
        }

        // البحث عن زرار عرض التسليمات في نفس الصف للمقارنة
        const projectCell = row.querySelector('td:first-child strong');
        const projectButtons = document.querySelectorAll(`button[onclick="viewProjectDeliveries(${projectId})"]`);

        let shouldShow = false;

        // مقارنة اسم المشروع
        if (projectCell) {
            const projectName = projectCell.textContent.trim();

            projectButtons.forEach(button => {
                const buttonRow = button.closest('tr');
                const buttonProjectName = buttonRow.querySelector('strong').textContent.trim();

                if (projectName === buttonProjectName) {
                    shouldShow = true;
                }
            });
        }

        if (shouldShow) {
            row.style.display = '';
            visibleRows++;
        } else {
            row.style.display = 'none';
        }
    });

    // إذا لم توجد صفوف مرئية، عرض رسالة
    if (visibleRows === 0) {
        const tbody = myDeliveriesTable.querySelector('tbody');
        const noDataRow = document.createElement('tr');
        noDataRow.innerHTML = `
            <td colspan="100%" class="text-center py-4 no-project-data">
                <div class="text-muted">
                    <i class="fas fa-filter fa-3x mb-3"></i>
                    <p>لا توجد تسليمات شخصية لهذا المشروع</p>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="clearProjectFilter()">
                        <i class="fas fa-times"></i> إزالة الفلتر
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(noDataRow);
    }

    // تحديث الإحصائيات
    updateMyDeliveriesStatistics(projectId, visibleRows);

    // تحديث عنوان التاب
    updateTabTitleWithProject(projectId);

    // تحديث رقم badge التاب
    updateTabBadgeCount(visibleRows);

    // تحديث تاب المشاريع ليعكس الفلتر المطبق
    updateProjectsTabForFilter(projectId);

    showSuccessMessage(`تم فلترة التسليمات الشخصية حسب المشروع المحدد (${visibleRows} تسليمة)`);
    hideLoadingState();
}

/**
 * Update projects tab to reflect applied filter
 * تحديث تاب المشاريع ليعكس الفلتر المطبق
 */
function updateProjectsTabForFilter(projectId) {
    try {
        const projectsTable = document.getElementById('projectsTable');
        if (!projectsTable) {
            console.log('Projects table not found');
            return;
        }

        // مسح أي highlighting سابق
        projectsTable.querySelectorAll('.project-filtered').forEach(row => {
            row.classList.remove('project-filtered');
        });

        projectsTable.querySelectorAll('.table-info').forEach(row => {
            row.classList.remove('table-info');
        });

        // البحث عن صف المشروع المحدد وتميييزه
        const projectButton = projectsTable.querySelector(`button[onclick="viewProjectDeliveries(${projectId})"]`);
        if (projectButton) {
            const projectRow = projectButton.closest('tr');
            if (projectRow) {
                // إضافة class للتمييز
                projectRow.classList.add('project-filtered', 'table-info');

                // إضافة أيقون فلتر للمشروع المحدد
                const projectNameCell = projectRow.querySelector('td:first-child');
                if (projectNameCell && !projectNameCell.querySelector('.filter-icon')) {
                    const filterIcon = document.createElement('span');
                    filterIcon.className = 'filter-icon badge bg-success ms-2';
                    filterIcon.innerHTML = '<i class="fas fa-filter"></i> مفلتر';
                    filterIcon.title = 'هذا المشروع مفلتر حالياً';
                    projectNameCell.appendChild(filterIcon);
                }

                // التمرير للمشروع المحدد
                projectRow.scrollIntoView({ behavior: 'smooth', block: 'center' });

                console.log(`Highlighted project ${projectId} in projects tab`);
            }
        }

        // تحديث badge تاب المشاريع إذا كان موجود
        const projectsTab = document.getElementById('projects-deadlines-tab');
        if (projectsTab) {
            const badge = projectsTab.querySelector('.badge');
            if (badge) {
                // إضافة إشارة للفلتر
                const originalText = badge.textContent;
                if (!originalText.includes('(مفلتر)')) {
                    badge.textContent = '1 (مفلتر)';
                    badge.classList.add('bg-success');
                    badge.classList.remove('badge-info');
                }
            }
        }

    } catch (error) {
        console.error('Error updating projects tab for filter:', error);
    }
}

/* الفانكشنز دي اتمسحت لأن الباك إند دلوقتي بيرجع المشاريع مفلترة أصلاً */

/**
 * Clear projects tab filter highlighting
 * مسح تمييز الفلتر من تاب المشاريع
 */
function clearProjectsTabFilter() {
    try {
        const projectsTable = document.getElementById('projectsTable');
        if (!projectsTable) return;

        // مسح كل التمييز والأيقونات من المشاريع
        projectsTable.querySelectorAll('.project-filtered').forEach(row => {
            row.classList.remove('project-filtered', 'table-info');
        });

        projectsTable.querySelectorAll('.filter-icon').forEach(icon => {
            icon.remove();
        });

        console.log('Cleared projects tab filter highlighting');
    } catch (error) {
        console.error('Error clearing projects tab filter:', error);
    }
}

/**
 * Update tab badge count
 * تحديث رقم العداد في badge التاب
 */
function updateTabBadgeCount(count) {
    try {
        // تحديد التاب النشط
        const activeTab = document.querySelector('.nav-link.active');
        if (!activeTab) return;

        // البحث عن الـ badge في التاب النشط
        const badge = activeTab.querySelector('.badge');
        if (badge) {
            // تحديث الرقم في الـ badge
            badge.textContent = count;
            console.log(`Updated tab badge count to: ${count}`);
        }
    } catch (error) {
        console.error('Error updating tab badge count:', error);
    }
}

/**
 * Update my deliveries statistics after filtering
 * تحديث إحصائيات التسليمات الشخصية بعد الفلترة
 */
function updateMyDeliveriesStatistics(projectId, totalVisible) {
    try {
        // حساب الإحصائيات للتسليمات الشخصية المرئية
        const myDeliveriesTable = document.getElementById('myDeliveriesTable');
        if (!myDeliveriesTable) return;

        const visibleRows = myDeliveriesTable.querySelectorAll('tbody tr:not([style*="display: none"]):not(.no-project-data)');

        let acknowledged = 0;
        let unacknowledged = 0;
        let overdue = 0;

        visibleRows.forEach(row => {
            // تجاهل صفوف "لا توجد بيانات"
            if (row.querySelector('td[colspan]')) return;

            // حالة التأكيد
            const statusCell = row.querySelector('.status-acknowledged');
            if (statusCell) {
                acknowledged++;
            } else {
                unacknowledged++;
            }

            // حالة التأخير
            const overdueCell = row.querySelector('.deadline-overdue');
            if (overdueCell) {
                overdue++;
            }
        });

        // تحديث الإحصائيات في الصفحة الرئيسية
        const totalElement = document.querySelector('#total-deliveries');
        const acknowledgedElement = document.querySelector('#acknowledged-deliveries');
        const unacknowledgedElement = document.querySelector('#unacknowledged-deliveries');
        const overdueElement = document.querySelector('#overdue-deliveries');

        if (totalElement) totalElement.textContent = totalVisible;
        if (acknowledgedElement) acknowledgedElement.textContent = acknowledged;
        if (unacknowledgedElement) unacknowledgedElement.textContent = unacknowledged;
        if (overdueElement) overdueElement.textContent = overdue;

        // تحديث إحصائيات التاب إذا كانت موجودة
        const tabStatsContainers = document.querySelectorAll('#my-deliveries .row .col-md-3');
        if (tabStatsContainers.length >= 4) {
            const tabStats = [
                { element: tabStatsContainers[0].querySelector('.h6'), value: totalVisible },
                { element: tabStatsContainers[1].querySelector('.h6'), value: acknowledged },
                { element: tabStatsContainers[2].querySelector('.h6'), value: unacknowledged },
                { element: tabStatsContainers[3].querySelector('.h6'), value: overdue }
            ];

            tabStats.forEach(stat => {
                if (stat.element) {
                    stat.element.textContent = stat.value;
                }
            });
        }

        console.log('Statistics updated:', { total: totalVisible, acknowledged, unacknowledged, overdue });
    } catch (error) {
        console.error('Error updating my deliveries statistics:', error);
    }
}

/**
 * Clear project filter
 * مسح فلتر المشروع
 */
function clearProjectFilter() {
    try {
        // إزالة الفلتر من النموذج
        const projectFilter = document.querySelector('select[name="project_id"]');
        if (projectFilter) {
            projectFilter.value = '';
        }

        // إعادة عرض جميع الصفوف
        const allTables = document.querySelectorAll('#myDeliveriesTable, #deliveriesTable');
        allTables.forEach(table => {
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                if (row.classList.contains('no-project-data') || row.querySelector('.no-project-data')) {
                    row.remove();
                } else {
                    row.style.display = '';
                }
            });
        });

        // إعادة حساب الإحصائيات بعد إظهار جميع الصفوف
        recalculateStatistics();

        // مسح عنوان الفلتر من التاب
        clearProjectFilterFromTab();

        // مسح تمييز المشروع من تاب المشاريع
        clearProjectsTabFilter();

        // إعادة تعيين badge التاب للرقم الأصلي
        resetTabBadgeToOriginal();

        showSuccessMessage('تم إزالة فلتر المشروع');
    } catch (error) {
        console.error('Error clearing project filter:', error);
        showErrorMessage('حدث خطأ أثناء إزالة فلتر المشروع');
    }
}

/**
 * Reset tab badge to original count
 * إعادة تعيين badge التاب للرقم الأصلي
 */
function resetTabBadgeToOriginal() {
    try {
        // تحديد التاب النشط
        const activeTab = document.querySelector('.nav-link.active');
        if (!activeTab) return;

        const activeTabTarget = activeTab.getAttribute('data-bs-target');
        let originalCount = 0;

        // حساب العدد الأصلي حسب التاب
        if (activeTabTarget === '#my-deliveries') {
            const myDeliveriesTable = document.getElementById('myDeliveriesTable');
            if (myDeliveriesTable) {
                const allRows = myDeliveriesTable.querySelectorAll('tbody tr:not(.no-project-data)');
                originalCount = allRows.length;

                // تجاهل صفوف "لا توجد بيانات"
                allRows.forEach(row => {
                    if (row.querySelector('td[colspan]')) {
                        originalCount--;
                    }
                });
            }
        } else if (activeTabTarget === '#all-deliveries') {
            const deliveriesTable = document.getElementById('deliveriesTable');
            if (deliveriesTable) {
                const allRows = deliveriesTable.querySelectorAll('tbody tr:not(.no-project-data)');
                originalCount = allRows.length;

                // تجاهل صفوف "لا توجد بيانات"
                allRows.forEach(row => {
                    if (row.querySelector('td[colspan]')) {
                        originalCount--;
                    }
                });
            }
        } else if (activeTabTarget === '#projects-deadlines') {
            const projectsTable = document.getElementById('projectsTable');
            if (projectsTable) {
                const allRows = projectsTable.querySelectorAll('tbody tr');
                originalCount = allRows.length;

                // تجاهل صفوف "لا توجد بيانات"
                allRows.forEach(row => {
                    if (row.querySelector('td[colspan]')) {
                        originalCount--;
                    }
                });
            }
        }

        // تحديث الـ badge
        updateTabBadgeCount(originalCount);
        console.log(`Reset tab badge to original count: ${originalCount}`);
    } catch (error) {
        console.error('Error resetting tab badge to original:', error);
    }
}

/**
 * Update active tab statistics
 * تحديث إحصائيات التاب النشط
 */
function updateActiveTabStatistics(data) {
    try {
        const activeTab = document.querySelector('.nav-link.active');
        const activeTabTarget = activeTab ? activeTab.getAttribute('data-bs-target') : null;

        if (!activeTabTarget || !data || !Array.isArray(data)) return;

        // حساب الإحصائيات
        const total = data.length;
        const acknowledged = data.filter(d => d.is_acknowledged).length;
        const unacknowledged = total - acknowledged;
        const overdue = data.filter(d => d.deadline_status === 'overdue').length;

        // تحديث إحصائيات التاب النشط
        const tabStatsContainers = document.querySelectorAll(`${activeTabTarget} .row .col-md-3`);
        if (tabStatsContainers.length >= 4) {
            const tabStats = [
                { element: tabStatsContainers[0].querySelector('.h6'), value: total },
                { element: tabStatsContainers[1].querySelector('.h6'), value: acknowledged },
                { element: tabStatsContainers[2].querySelector('.h6'), value: unacknowledged },
                { element: tabStatsContainers[3].querySelector('.h6'), value: overdue }
            ];

            tabStats.forEach(stat => {
                if (stat.element) {
                    stat.element.textContent = stat.value;
                }
            });
        }

        console.log('Updated active tab statistics:', { tab: activeTabTarget, total, acknowledged, unacknowledged, overdue });
    } catch (error) {
        console.error('Error updating active tab statistics:', error);
    }
}

/**
 * Recalculate statistics for visible rows
 * إعادة حساب الإحصائيات للصفوف المرئية
 */
function recalculateStatistics() {
    try {
        // التحقق من التاب النشط
        const activeTab = document.querySelector('.nav-link.active');
        const activeTabTarget = activeTab ? activeTab.getAttribute('data-bs-target') : null;

        let visibleRows = [];

        if (activeTabTarget === '#my-deliveries') {
            // حساب إحصائيات التسليمات الشخصية
            const myDeliveriesTable = document.getElementById('myDeliveriesTable');
            if (myDeliveriesTable) {
                visibleRows = myDeliveriesTable.querySelectorAll('tbody tr:not([style*="display: none"]):not(.no-project-data)');
            }
        } else if (activeTabTarget === '#all-deliveries') {
            // حساب إحصائيات جميع التسليمات
            const deliveriesTable = document.getElementById('deliveriesTable');
            if (deliveriesTable) {
                visibleRows = deliveriesTable.querySelectorAll('tbody tr:not([style*="display: none"]):not(.no-project-data)');
            }
        }

        let total = 0;
        let acknowledged = 0;
        let unacknowledged = 0;
        let overdue = 0;

        visibleRows.forEach(row => {
            // تجاهل صفوف "لا توجد بيانات"
            if (row.querySelector('td[colspan]')) return;

            total++;

            // حالة التأكيد
            const statusCell = row.querySelector('.status-acknowledged');
            if (statusCell) {
                acknowledged++;
            } else {
                unacknowledged++;
            }

            // حالة التأخير
            const overdueCell = row.querySelector('.deadline-overdue');
            if (overdueCell) {
                overdue++;
            }
        });

        // تحديث الإحصائيات الرئيسية
        const totalElement = document.querySelector('#total-deliveries');
        const acknowledgedElement = document.querySelector('#acknowledged-deliveries');
        const unacknowledgedElement = document.querySelector('#unacknowledged-deliveries');
        const overdueElement = document.querySelector('#overdue-deliveries');

        if (totalElement) totalElement.textContent = total;
        if (acknowledgedElement) acknowledgedElement.textContent = acknowledged;
        if (unacknowledgedElement) unacknowledgedElement.textContent = unacknowledged;
        if (overdueElement) overdueElement.textContent = overdue;

        // تحديث إحصائيات التاب النشط
        if (activeTabTarget) {
            const tabStatsContainers = document.querySelectorAll(`${activeTabTarget} .row .col-md-3`);
            if (tabStatsContainers.length >= 4) {
                const tabStats = [
                    { element: tabStatsContainers[0].querySelector('.h6'), value: total },
                    { element: tabStatsContainers[1].querySelector('.h6'), value: acknowledged },
                    { element: tabStatsContainers[2].querySelector('.h6'), value: unacknowledged },
                    { element: tabStatsContainers[3].querySelector('.h6'), value: overdue }
                ];

                tabStats.forEach(stat => {
                    if (stat.element) {
                        stat.element.textContent = stat.value;
                    }
                });
            }
        }

        // تحديث badge التاب
        updateTabBadgeCount(total);

        console.log('Recalculated statistics:', { total, acknowledged, unacknowledged, overdue });
    } catch (error) {
        console.error('Error recalculating statistics:', error);
    }
}

// ===============================
// دوال الاعتماد الجديدة
// ===============================

/**
 * Grant administrative approval
 * إعطاء اعتماد إداري
 */
function grantAdministrativeApproval(deliveryId) {
    Swal.fire({
        title: 'اعتماد إداري',
        html: `
            <div class="text-start">
                <label for="admin-notes" class="form-label">ملاحظات الاعتماد (اختيارية)</label>
                <textarea id="admin-notes" class="form-control" placeholder="أضف ملاحظاتك هنا..." rows="3"></textarea>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0d6efd',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-user-check"></i> اعتماد إداري',
        cancelButtonText: '<i class="fas fa-times"></i> إلغاء',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            const notes = document.getElementById('admin-notes').value;
            return $.ajax({
                url: `/deliveries/${deliveryId}/grant-administrative-approval`,
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    notes: notes
                }
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            const response = result.value;
            if (response.success) {
                Swal.fire({
                    title: 'تم بنجاح!',
                    text: response.message,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    refreshData();
                });
            } else {
                showErrorMessage(response.message);
            }
        }
    }).catch((error) => {
        if (error.dismiss !== Swal.DismissReason.cancel) {
            const response = error.responseJSON;
            showErrorMessage(response?.message || 'حدث خطأ أثناء الاعتماد الإداري');
        }
    });
}

/**
 * Grant technical approval
 * إعطاء اعتماد فني
 */
function grantTechnicalApproval(deliveryId) {
    Swal.fire({
        title: 'اعتماد فني',
        html: `
            <div class="text-start">
                <label for="tech-notes" class="form-label">ملاحظات الاعتماد (اختيارية)</label>
                <textarea id="tech-notes" class="form-control" placeholder="أضف ملاحظاتك الفنية هنا..." rows="3"></textarea>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-cogs"></i> اعتماد فني',
        cancelButtonText: '<i class="fas fa-times"></i> إلغاء',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            const notes = document.getElementById('tech-notes').value;
            return $.ajax({
                url: `/deliveries/${deliveryId}/grant-technical-approval`,
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    notes: notes
                }
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            const response = result.value;
            if (response.success) {
                Swal.fire({
                    title: 'تم بنجاح!',
                    text: response.message,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    refreshData();
                });
            } else {
                showErrorMessage(response.message);
            }
        }
    }).catch((error) => {
        if (error.dismiss !== Swal.DismissReason.cancel) {
            const response = error.responseJSON;
            showErrorMessage(response?.message || 'حدث خطأ أثناء الاعتماد الفني');
        }
    });
}

/**
 * Revoke administrative approval
 * إلغاء اعتماد إداري
 */
function revokeAdministrativeApproval(deliveryId) {
    Swal.fire({
        title: 'إلغاء الاعتماد الإداري',
        text: 'هل تريد إلغاء الاعتماد الإداري لهذه التسليمة؟',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-user-times"></i> نعم، ألغ الاعتماد',
        cancelButtonText: '<i class="fas fa-times"></i> إلغاء',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return $.ajax({
                url: `/deliveries/${deliveryId}/revoke-administrative-approval`,
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            const response = result.value;
            if (response.success) {
                Swal.fire({
                    title: 'تم بنجاح!',
                    text: response.message,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    refreshData();
                });
            } else {
                showErrorMessage(response.message);
            }
        }
    }).catch((error) => {
        if (error.dismiss !== Swal.DismissReason.cancel) {
            const response = error.responseJSON;
            showErrorMessage(response?.message || 'حدث خطأ أثناء إلغاء الاعتماد الإداري');
        }
    });
}

/**
 * Revoke technical approval
 * إلغاء اعتماد فني
 */
function revokeTechnicalApproval(deliveryId) {
    Swal.fire({
        title: 'إلغاء الاعتماد الفني',
        text: 'هل تريد إلغاء الاعتماد الفني لهذه التسليمة؟',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-times-circle"></i> نعم، ألغ الاعتماد',
        cancelButtonText: '<i class="fas fa-times"></i> إلغاء',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return $.ajax({
                url: `/deliveries/${deliveryId}/revoke-technical-approval`,
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            const response = result.value;
            if (response.success) {
                Swal.fire({
                    title: 'تم بنجاح!',
                    text: response.message,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    refreshData();
                });
            } else {
                showErrorMessage(response.message);
            }
        }
    }).catch((error) => {
        if (error.dismiss !== Swal.DismissReason.cancel) {
            const response = error.responseJSON;
            showErrorMessage(response?.message || 'حدث خطأ أثناء إلغاء الاعتماد الفني');
        }
    });
}

