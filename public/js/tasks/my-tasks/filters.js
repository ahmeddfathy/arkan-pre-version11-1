/**
 * My Tasks Filters Script
 * Handles date filtering and all filter combinations
 */

(function() {
    'use strict';

    // Wait for jQuery if it's not loaded yet
    if (typeof $ === 'undefined') {
        console.error('jQuery is required for my-tasks filters');
        return;
    }

    $(document).ready(function() {
        // تحديث التسميات عند تغيير نوع التاريخ
        $('#dateTypeFilter').on('change', function() {
            updateDateLabels();
            filterMyTasksByDate();
        });

        // Event listeners لحقول التاريخ
        $('#dateFrom, #dateTo').on('change', function() {
            filterMyTasksByDate();
        });

        // زر مسح فلتر التاريخ
        $('#clearDateFilter').on('click', function() {
            $('#dateFrom').val('');
            $('#dateTo').val('');
            filterMyTasksByDate();
        });

        // تحديث التسميات عند التحميل
        updateDateLabels();

        // ✅ دمج فلترة التاريخ مع الفلاتر الأخرى
        $('#projectFilter, #statusFilter, #searchInput').on('change keyup', function() {
            filterMyTasksByDate();
        });
    });

    function updateDateLabels() {
        const dateType = $('#dateTypeFilter').val();
        if (dateType === 'deadline') {
            $('#dateFromLabel').html('<i class="fas fa-calendar-alt"></i> من Deadline');
            $('#dateToLabel').html('<i class="fas fa-calendar-alt"></i> إلى Deadline');
        } else if (dateType === 'created_at') {
            $('#dateFromLabel').html('<i class="fas fa-calendar-plus"></i> من تاريخ الإنشاء');
            $('#dateToLabel').html('<i class="fas fa-calendar-plus"></i> إلى تاريخ الإنشاء');
        }
    }

    function filterMyTasksByDate() {
        const dateFrom = $('#dateFrom').val();
        const dateTo = $('#dateTo').val();
        const dateType = $('#dateTypeFilter').val();
        const projectId = $('#projectFilter').val();
        const status = $('#statusFilter').val();
        const searchText = $('#searchInput').val().toLowerCase();

        // فلترة العرض الجدولي
        filterMyTasksTableView(dateFrom, dateTo, dateType, projectId, status, searchText);

        // فلترة عرض الكانبان
        if (window.myTasksCurrentView === 'kanban') {
            filterMyTasksKanbanView(dateFrom, dateTo, dateType, projectId, status, searchText);
        }
    }

    function filterMyTasksTableView(dateFrom, dateTo, dateType, projectId, status, searchText) {
        $('#myTasksTable tbody tr').each(function() {
            const $row = $(this);
            let show = true;

            // فلتر المشروع
            if (projectId && $row.data('project-id') != projectId) {
                show = false;
            }

            // فلتر الحالة
            if (status && $row.data('status') != status) {
                show = false;
            }

            // فلتر البحث
            if (searchText && $row.text().toLowerCase().indexOf(searchText) === -1) {
                show = false;
            }

            // ✅ فلتر التاريخ (deadline أو created_at)
            if (show && (dateFrom || dateTo)) {
                const taskDate = dateType === 'deadline' ? $row.data('due-date') : $row.data('created-at');

                // فقط فلتر المهام التي لها تاريخ
                if (taskDate && taskDate !== 'غير محدد') {
                    if (dateFrom && taskDate < dateFrom) {
                        show = false;
                    }
                    if (dateTo && taskDate > dateTo) {
                        show = false;
                    }
                }
            }

            $row.toggle(show);
        });
    }

    function filterMyTasksKanbanView(dateFrom, dateTo, dateType, projectId, status, searchText) {
        $('.kanban-card').each(function() {
            const $card = $(this);
            let show = true;

            // الحصول على البيانات من الكارد
            const taskId = $card.data('task-id');
            const $tableRow = $(`#myTasksTable tbody tr[data-task-id="${taskId}"]`);

            if ($tableRow.length > 0) {
                const cardProjectId = $tableRow.data('project-id');
                const cardStatus = $tableRow.data('status');
                const taskDate = dateType === 'deadline' ? $tableRow.data('due-date') : $tableRow.data('created-at');
                const cardText = $card.text().toLowerCase();

                // فلتر المشروع
                if (projectId && cardProjectId != projectId) {
                    show = false;
                }

                // فلتر الحالة
                if (status && cardStatus != status) {
                    show = false;
                }

                // فلتر البحث
                if (searchText && cardText.indexOf(searchText) === -1) {
                    show = false;
                }

                // ✅ فلتر التاريخ (deadline أو created_at)
                if (show && (dateFrom || dateTo)) {
                    if (taskDate && taskDate !== 'غير محدد') {
                        if (dateFrom && taskDate < dateFrom) {
                            show = false;
                        }
                        if (dateTo && taskDate > dateTo) {
                            show = false;
                        }
                    }
                }
            }

            $card.toggle(show);
        });

        // تحديث عدادات الكانبان
        updateMyTasksKanbanCounters();
    }

    function updateMyTasksKanbanCounters() {
        $('.kanban-column').each(function() {
            const $column = $(this);
            const visibleCount = $column.find('.kanban-card:visible').length;
            $column.find('.task-count').text(visibleCount);
        });
    }

    // Export functions to global scope if needed
    window.filterMyTasksByDate = filterMyTasksByDate;
    window.updateDateLabels = updateDateLabels;
    window.updateMyTasksKanbanCounters = updateMyTasksKanbanCounters;

})();
