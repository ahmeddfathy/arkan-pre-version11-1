$(document).ready(function() {
    initializeFilters();
});

function initializeFilters() {
    // فلتر كود المشروع - Datalist Input
    $('#projectCodeFilter').on('input change', function() {
        const enteredCode = $(this).val().trim();

        // إظهار/إخفاء زر المسح
        if (enteredCode) {
            $('#clearProjectCode').show();
        } else {
            $('#clearProjectCode').hide();
        }

        // تحديث فلتر المشروع بناءً على الكود المُدخل
        if (enteredCode) {
            const $projectFilter = $('#projectFilter');
            const matchingProject = $projectFilter.find('option[data-code="' + enteredCode + '"]').first();
            if (matchingProject.length > 0) {
                $projectFilter.val(matchingProject.val());
            }
        } else {
            // إذا تم مسح الكود، امسح فلتر المشروع أيضاً
            $('#projectFilter').val('');
        }

        filterTasks();
    });

    // زر مسح كود المشروع
    $('#clearProjectCode').click(function() {
        $('#projectCodeFilter').val('').trigger('change');
        $(this).hide();
    });

    $('#projectFilter').change(function() {
        // تحديث فلتر الكود عند اختيار مشروع
        const selectedOption = $(this).find('option:selected');
        const projectCode = selectedOption.data('code');

        if (projectCode) {
            $('#projectCodeFilter').val(projectCode);
        } else {
            $('#projectCodeFilter').val('');
        }

        filterTasks();
    });

    $('#serviceFilter').change(function() {
        filterTasks();
    });

    $('#statusFilter').change(function() {
        filterTasks();
    });

    $('#createdByFilter').change(function() {
        filterTasks();
    });

    // ✅ فلتر المستخدم المعين للمهمة
    $('#assignedUserFilter').change(function() {
        filterTasks();
    });

    $('#searchInput').keyup(function() {
        filterTasks();
    });

    // ✅ فلاتر التاريخ
    $('#dateTypeFilter').change(function() {
        updateDateLabels();
        filterTasks();
    });

    $('#dateFromFilter, #dateToFilter').change(function() {
        filterTasks();
    });

    $('#myCreatedTasksBtn').click(function() {
        const currentUserId = window.currentUserId || $('meta[name="user-id"]').attr('content');
        if (currentUserId) {
            const isCurrentlyFiltered = $('#createdByFilter').val() == currentUserId;

            if (isCurrentlyFiltered) {
                $('#createdByFilter').val('');
                $(this).removeClass('btn-success').addClass('btn-primary')
                    .html('<i class="fas fa-user-plus"></i> مهامي التي أضفتها');
            } else {
                $('#createdByFilter').val(currentUserId);
                $(this).removeClass('btn-primary').addClass('btn-success')
                    .html('<i class="fas fa-check"></i> مهامي المختارة');
            }
            filterTasks();
        }
    });

    $('#clearFiltersBtn').click(function() {
        clearAllFilters();
    });

}

function clearAllFilters() {
    $('#projectFilter').val('');
    $('#serviceFilter').val('');
    $('#statusFilter').val('');
    $('#createdByFilter').val('');
    $('#assignedUserFilter').val(''); // ✅ مسح فلتر المستخدم
    $('#searchInput').val('');
    $('#dateTypeFilter').val('due_date');
    $('#dateFromFilter').val('');
    $('#dateToFilter').val('');

    $('#myCreatedTasksBtn').removeClass('btn-success').addClass('btn-primary')
        .html('<i class="fas fa-user-plus"></i> مهامي التي أضفتها');

    updateDateLabels();
    filterTasks();
}

function filterTasks() {
    const projectId = $('#projectFilter').val();
    const serviceId = $('#serviceFilter').val();
    const status = $('#statusFilter').val();
    const createdBy = $('#createdByFilter').val();
    const assignedUserId = $('#assignedUserFilter').val(); // ✅ فلتر المستخدم المعين
    const searchText = $('#searchInput').val().toLowerCase();
    const dateType = $('#dateTypeFilter').val();
    const dateFrom = $('#dateFromFilter').val();
    const dateTo = $('#dateToFilter').val();

    // ✅ دعم كل من tasks index و my-tasks
    const tableSelector = $('#myTasksTable').length > 0 ? '#myTasksTable tbody tr' : '#tasksTable tbody tr';
    $(tableSelector).each(function() {
        let show = true;

        if (projectId && $(this).data('project-id') != projectId) {
            show = false;
        }

        if (serviceId && $(this).data('service-id') != serviceId) {
            show = false;
        }

        if (status && $(this).data('status') != status) {
            show = false;
        }

        // ✅ فلتر المنشئ - تم إصلاحه لدعم مهام القوالب
        if (createdBy) {
            const taskCreatedBy = $(this).data('created-by');
            const isTemplate = $(this).data('is-template') === 'true';

            // للقوالب: نتحقق من created_by (اللي هو assigned_by)
            // للمهام العادية: نتحقق من created_by
            if (taskCreatedBy === '' || taskCreatedBy === null || taskCreatedBy === undefined) {
                // لو مفيش created_by خالص، نخفي المهمة
                show = false;
            } else if (taskCreatedBy != createdBy) {
                // لو created_by موجود بس مش بتاع المستخدم المختار
                show = false;
            }
            // ✅ لو created_by = createdBy، هيظهر (سواء قالب أو عادي)
        }

        // ✅ فلتر المستخدم المعين للمهمة
        if (assignedUserId) {
            const assignedUsers = $(this).data('assigned-users'); // array of user IDs
            if (!assignedUsers || !assignedUsers.includes(parseInt(assignedUserId))) {
                show = false;
            }
        }

        if (searchText && $(this).text().toLowerCase().indexOf(searchText) === -1) {
            show = false;
        }

        // ✅ فلتر التاريخ
        if (show && (dateFrom || dateTo)) {
            const taskDate = $(this).data(dateType === 'created_at' ? 'created-at' : 'due-date');

            // ✅ فقط فلتر المهام اللي عندها تاريخ (اللي مفيش ليها تاريخ تفضل ظاهرة)
            if (taskDate) {
                if (dateFrom && taskDate < dateFrom) {
                    show = false;
                }
                if (dateTo && taskDate > dateTo) {
                    show = false;
                }
            }
            // ❌ لا نخفي المهام اللي مفيش ليها تاريخ
        }

        $(this).toggle(show);
    });

    if (window.currentView === 'kanban') {
        filterKanbanTasks(projectId, serviceId, status, createdBy, assignedUserId, searchText, dateType, dateFrom, dateTo);
    }

    // ✅ دعم My Tasks Kanban
    if (typeof window.myTasksCurrentView !== 'undefined' &&
        window.myTasksCurrentView === 'kanban') {
        if (typeof window.filterMyTasksKanban === 'function') {
            window.filterMyTasksKanban(projectId, status, searchText);
        }
    }

    // ✅ دعم My Tasks Calendar
    if (typeof window.myTasksCurrentView !== 'undefined' &&
        window.myTasksCurrentView === 'calendar') {
        if (typeof window.myTasksCalendar !== 'undefined' &&
            typeof window.myTasksCalendar.applyFilters === 'function') {
            window.myTasksCalendar.applyFilters();
        }
    }

    $(document).trigger('filtersApplied');
}

function filterKanbanTasks(projectId, serviceId, status, createdBy, assignedUserId, searchText, dateType, dateFrom, dateTo) {
    // ✅ التأكد من أن البيانات موجودة وهي array
    const allTasks = Array.isArray(window.tasksData) ? window.tasksData : [];

    // إذا لم توجد بيانات، نستخدم الكروت الموجودة في DOM
    if (allTasks.length === 0) {
        filterKanbanTasksFromDOM(projectId, serviceId, status, createdBy, assignedUserId, searchText, dateType, dateFrom, dateTo);
        return;
    }

    const filteredTasks = allTasks.filter(task => {
        if (projectId && task.projectId != projectId) {
            return false;
        }

        if (serviceId && task.serviceId != serviceId) {
            return false;
        }

        if (status && task.status != status) {
            return false;
        }

        // ✅ فلتر المنشئ - تم إصلاحه لدعم مهام القوالب
        if (createdBy) {
            const taskCreatedById = task.createdById;
            const isTemplate = task.isTemplate === true || task.isTemplate === 'true';

            // للقوالب: createdById هو assigned_by
            // للمهام العادية: createdById هو created_by
            if (!taskCreatedById || taskCreatedById === '' || taskCreatedById === null) {
                // لو مفيش created_by خالص، نخفي المهمة
                return false;
            } else if (taskCreatedById != createdBy) {
                // لو created_by موجود بس مش بتاع المستخدم المختار
                return false;
            }
            // ✅ لو created_by = createdBy، هيظهر (سواء قالب أو عادي)
        }

        // ✅ فلتر المستخدم المعين
        if (assignedUserId) {
            const assignedUsers = task.assignedUsers || []; // array of user IDs
            if (!assignedUsers.includes(parseInt(assignedUserId))) {
                return false;
            }
        }

        if (searchText) {
            const taskText = (task.name + ' ' + task.description + ' ' + task.project + ' ' + task.service + ' ' + task.createdBy).toLowerCase();
            if (taskText.indexOf(searchText) === -1) {
                return false;
            }
        }

        // ✅ فلتر التاريخ
        if (dateFrom || dateTo) {
            const taskDate = dateType === 'created_at' ? task.createdAt : task.dueDate;

            // ✅ فقط فلتر المهام اللي عندها تاريخ (اللي مفيش ليها تاريخ تفضل ظاهرة)
            if (taskDate) {
                if (dateFrom && taskDate < dateFrom) {
                    return false;
                }
                if (dateTo && taskDate > dateTo) {
                    return false;
                }
            }
            // ❌ لا نخفي المهام اللي مفيش ليها تاريخ
        }

        return true;
    });

    loadFilteredTasksIntoKanban(filteredTasks);
}

function loadFilteredTasksIntoKanban(filteredTasks) {
    $('.kanban-cards').empty();

    $('.task-count').text('0');

    const tasksByStatus = {
        'new': [],
        'in_progress': [],
        'paused': [],
        'completed': [],
        'cancelled': []
    };

    filteredTasks.forEach(task => {
        if (tasksByStatus[task.status]) {
            tasksByStatus[task.status].push(task);
        }
    });

    Object.keys(tasksByStatus).forEach(status => {
        const tasks = tasksByStatus[status];
        const container = $(`#cards-${status}`);
        const counter = $(`#count-${status}`);

        counter.text(tasks.length);

        tasks.forEach(task => {
            let card;
            if (typeof window.createTaskCard === 'function') {
                card = window.createTaskCard(task);
            } else {
                card = createSimpleTaskCard(task);
            }
            container.append(card);
        });
    });

    if (typeof window.applyUserColors === 'function') {
        setTimeout(() => {
            window.applyUserColors();
        }, 100);
    }
}

function createSimpleTaskCard(task) {
    const currentUserId = window.currentUserId || 0;
    const isMyTask = task.createdById == currentUserId;

    return `
        <div class="kanban-card ${isMyTask ? 'my-created-task' : ''}"
             data-task-id="${task.id}"
             data-status="${task.status}"
             data-is-template="${task.isTemplate || false}">
            <div class="kanban-card-title">${task.name}${task.isTemplate ? ' <span class="badge badge-sm bg-info ms-1"><i class="fas fa-layer-group"></i> قالب</span>' : ''}${(task.revisionsCount && task.revisionsCount > 0) ? ` <span class="task-revisions-badge ${task.revisionsStatus || 'pending'} ms-1" title="${getFilterRevisionStatusTooltip(task)}"><i class="fas fa-edit"></i><span class="revisions-count">${task.revisionsCount}</span></span>` : ''}${task.isTransferred ? ' <span class="badge badge-sm bg-warning ms-1" title="تم نقل هذه المهمة"><i class="fas fa-exchange-alt"></i> تم نقلها</span>' : ''}${(task.isAdditionalTask && task.taskSource === 'transferred') ? ' <span class="badge badge-sm bg-success ms-1" title="مهمة منقولة إليك"><i class="fas fa-plus-circle"></i> منقولة إليك</span>' : ''}</div>
            <div class="kanban-card-meta">
                <span class="kanban-card-project">${task.project}</span>
                <span class="kanban-card-service">${task.service}</span>
            </div>
            <div class="kanban-card-meta mb-2">
                <span class="kanban-card-creator" data-creator-id="${task.createdById}">أنشأت بواسطة: ${task.createdBy}</span>
            </div>
            <div class="kanban-card-actions">
                <button class="btn btn-sm btn-outline-primary view-task"
                        data-id="${task.id}"
                        data-is-template="${task.isTemplate || false}"
                        title="عرض التفاصيل">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
        </div>
    `;
}

function getFilterRevisionStatusTooltip(task) {
    const total = task.revisionsCount || 0;
    const pending = task.pendingRevisionsCount || 0;
    const approved = task.approvedRevisionsCount || 0;
    const rejected = task.rejectedRevisionsCount || 0;

    let tooltip = `${total} تعديلات`;

    if (pending > 0) {
        tooltip += ` - ${pending} معلق`;
    }
    if (approved > 0) {
        tooltip += ` - ${approved} مقبول`;
    }
    if (rejected > 0) {
        tooltip += ` - ${rejected} مرفوض`;
    }

    return tooltip;
}

/**
 * ✅ الفلترة المباشرة من DOM بدلاً من البيانات
 * تُستخدم عندما لا تتوفر window.tasksData
 */
function filterKanbanTasksFromDOM(projectId, serviceId, status, createdBy, assignedUserId, searchText, dateType, dateFrom, dateTo) {
    // إخفاء/إظهار الكروت الموجودة في DOM مباشرة
    $('.kanban-card').each(function() {
        const $card = $(this);
        let show = true;

        // ✅ الحصول على البيانات من data attributes أو من محتوى الكارد
        const cardStatus = $card.data('status') || $card.attr('data-status');

        // البحث عن project-id و service-id من الصف المقابل في الجدول
        const taskId = $card.data('task-id') || $card.attr('data-task-id');
        const $tableRow = $(`#tasksTable tbody tr[data-task-id="${taskId}"]`).first();

        let cardProjectId, cardServiceId, cardCreatedBy, cardAssignedUsers, cardDueDate, cardCreatedAt;

        if ($tableRow.length > 0) {
            // إذا وُجد الصف المقابل في الجدول، استخدم بياناته
            cardProjectId = $tableRow.data('project-id');
            cardServiceId = $tableRow.data('service-id');
            cardCreatedBy = $tableRow.data('created-by');
            cardAssignedUsers = $tableRow.data('assigned-users'); // ✅ array of user IDs
            cardDueDate = $tableRow.data('due-date');
            cardCreatedAt = $tableRow.data('created-at');
        } else {
            // البحث في محتوى الكارد نفسه
            cardProjectId = $card.data('project-id') || $card.attr('data-project-id');
            cardServiceId = $card.data('service-id') || $card.attr('data-service-id');
            cardCreatedBy = $card.find('.kanban-card-creator').data('creator-id') ||
                           $card.data('created-by');
            cardAssignedUsers = $card.data('assigned-users') || $card.attr('data-assigned-users');
            cardDueDate = $card.data('due-date') || $card.attr('data-due-date');
            cardCreatedAt = $card.data('created-at') || $card.attr('data-created-at');
        }

        const cardText = $card.text().toLowerCase();

        // تطبيق الفلاتر
        if (projectId && cardProjectId != projectId) {
            show = false;
        }

        if (serviceId && cardServiceId != serviceId) {
            show = false;
        }

        if (status && cardStatus != status) {
            show = false;
        }

        // ✅ فلتر المنشئ - تم إصلاحه لدعم مهام القوالب
        if (createdBy) {
            const isTemplate = $card.data('is-template') === 'true' || $card.data('is-template') === true;

            // للقوالب: cardCreatedBy هو assigned_by
            // للمهام العادية: cardCreatedBy هو created_by
            if (cardCreatedBy === '' || cardCreatedBy === null || cardCreatedBy === undefined) {
                // لو مفيش created_by خالص، نخفي المهمة
                show = false;
            } else if (cardCreatedBy != createdBy) {
                // لو created_by موجود بس مش بتاع المستخدم المختار
                show = false;
            }
            // ✅ لو created_by = createdBy، هيظهر (سواء قالب أو عادي)
        }

        // ✅ فلتر المستخدم المعين
        if (assignedUserId) {
            if (!cardAssignedUsers || !cardAssignedUsers.includes(parseInt(assignedUserId))) {
                show = false;
            }
        }

        if (searchText && cardText.indexOf(searchText) === -1) {
            show = false;
        }

        // ✅ فلتر التاريخ
        if (show && (dateFrom || dateTo)) {
            const taskDate = dateType === 'created_at' ? cardCreatedAt : cardDueDate;

            // ✅ فقط فلتر المهام اللي عندها تاريخ (اللي مفيش ليها تاريخ تفضل ظاهرة)
            if (taskDate) {
                if (dateFrom && taskDate < dateFrom) {
                    show = false;
                }
                if (dateTo && taskDate > dateTo) {
                    show = false;
                }
            }
            // ❌ لا نخفي المهام اللي مفيش ليها تاريخ
        }

        // إخفاء/إظهار الكارد
        $card.toggle(show);
    });

    // تحديث العدادات
    updateKanbanCounters();
}

/**
 * ✅ تحديث عدادات المهام في كل عمود
 */
function updateKanbanCounters() {
    $('.kanban-column').each(function() {
        const $column = $(this);
        const status = $column.data('status') || $column.attr('data-status');
        const visibleCount = $column.find('.kanban-card:visible').length;

        // تحديث العداد
        $column.find('.task-count').text(visibleCount);
        $(`#count-${status}`).text(visibleCount);
    });
}

/**
 * ✅ تحديث تسميات فلتر التاريخ حسب النوع المختار
 */
function updateDateLabels() {
    const dateType = $('#dateTypeFilter').val();

    if (dateType === 'due_date') {
        $('#dateFromLabel').html('📅 من موعد نهائي');
        $('#dateToLabel').html('📅 إلى موعد نهائي');
    } else if (dateType === 'created_at') {
        $('#dateFromLabel').html('🆕 من تاريخ إنشاء');
        $('#dateToLabel').html('🆕 إلى تاريخ إنشاء');
    }
}


