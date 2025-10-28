function initializeMyTasksKanbanBoard() {
    console.log('🚀 Initializing My Tasks Kanban Board...');

    // ✅ البيانات معروضة مباشرة من PHP في HTML - لا داعي لتحميلها من JavaScript
    // الكاردات موجودة بالفعل في الصفحة، فقط نحتاج تهيئة Drag & Drop

    console.log('✅ My Tasks Kanban Board initialized - Cards already rendered from PHP');
    console.log('📋 Kanban cards are already in the DOM, no data loading needed');
}

function loadMyTasksIntoKanban() {
    // ✅ الكاردات معروضة من PHP - لا نحتاج لإعادة تحميلها
    console.log('ℹ️ loadMyTasksIntoKanban: Cards already in DOM, skipping reload');

    // تحديث العدادات فقط بناءً على الكاردات الموجودة
    updateCardCounters();
    return; // ✅ إيقاف التنفيذ هنا لأن الكاردات موجودة

    const tasksByStatus = {
        'new': [],
        'in_progress': [],
        'paused': [],
        'completed': [],
        'cancelled': [],
        'transferred': []
    };

    const myTasksData = window.MyTasksCore.getData();

    // تجميع المهام بحسب الحالة
    myTasksData.forEach(task => {
        // تصنيف المهام حسب الحالة
        if (task.isTransferred) {
            tasksByStatus['transferred'].push(task);
        } else if (tasksByStatus[task.status]) {
            tasksByStatus[task.status].push(task);
        }
    });

    // إنشاء وعرض الكروت فوراً بدون انتظار
    Object.keys(tasksByStatus).forEach(status => {
        const tasks = tasksByStatus[status];
        const container = $(`#my-cards-${status}`);
        const counter = $(`#my-count-${status}`);

        // تحديث العدادات فوراً
        counter.text(tasks.length);

        // إضافة الكروت مباشرة كـ jQuery objects بدلاً من HTML strings
        if (tasks.length > 0) {
            // تنظيف الـ container أولاً
            container.empty();

            console.log(`🎴 إضافة ${tasks.length} كاردات في حالة "${status}"`);

            // إضافة كل كارد وتطبيق drag & drop عليه مباشرة
            tasks.forEach(task => {
                const card = createMyTaskCard(task);
                container.append(card);

                // إضافة drag & drop للكارد فوراً
                if (window.MyTasksDragDrop && window.MyTasksDragDrop.addDragDropToCard) {
                    window.MyTasksDragDrop.addDragDropToCard(card);
                }
            });

            console.log(`✅ تم إضافة Drag & Drop لـ ${tasks.length} كاردات في "${status}"`);
        }
    });

    // ✅ تهيئة الـ Drop Zones فوراً بعد تحميل الكاردات
    // تأخير صغير جداً للتأكد من أن DOM جاهز تماماً
    setTimeout(() => {
        if (window.MyTasksDragDrop && window.MyTasksDragDrop.initializeDropZones) {
            console.log('🚀 تهيئة Drop Zones بعد تحميل الكاردات في My Tasks');
            window.MyTasksDragDrop.initializeDropZones();

            // التحقق من جاهزية الكاردات
            const draggableCards = document.querySelectorAll('.my-kanban-card[draggable="true"]');
            console.log(`✅ عدد الكاردات القابلة للسحب: ${draggableCards.length}`);
        } else {
            console.error('❌ MyTasksDragDrop غير متاح!');
        }
    }, 50);
}

function createMyTaskCard(task) {
    const statusText = window.MyTasksUtils.getMyTaskStatusText(task.status);
    const priorityClass = window.MyTasksUtils.getMyTaskDueDateClass(task.dueDate);

    // 🎨 تحسين الألوان والأيقونات حسب الحالة
    const statusConfig = {
        'new': { icon: 'fas fa-plus-circle', color: '#3b82f6', bgColor: '#eff6ff' },
        'in_progress': { icon: 'fas fa-play-circle', color: '#f59e0b', bgColor: '#fffbeb' },
        'paused': { icon: 'fas fa-pause-circle', color: '#6b7280', bgColor: '#f9fafb' },
        'completed': { icon: 'fas fa-check-circle', color: '#10b981', bgColor: '#ecfdf5' },
        'cancelled': { icon: 'fas fa-times-circle', color: '#ef4444', bgColor: '#fef2f2' }
    };

    const currentStatus = statusConfig[task.status] || statusConfig['new'];
    const templateBadge = task.isTemplate ? '<span class="my-task-template-badge"><i class="fas fa-layer-group"></i> قالب</span>' : '';
    const transferBadge = task.isTransferred ? '<span class="my-task-transfer-badge"><i class="fas fa-exchange-alt"></i> منقول</span>' : '';
    const additionalBadge = task.isAdditionalTask ? '<span class="my-task-additional-badge"><i class="fas fa-plus"></i> إضافي</span>' : '';
    const approvedBadge = task.isApproved ? '<span class="my-task-approved-badge"><i class="fas fa-lock"></i> معتمد</span>' : '';
    const administrativeBadge = task.hasAdministrativeApproval ? '<span class="my-task-admin-badge"><i class="fas fa-user-tie"></i> إداري</span>' : '';
    const technicalBadge = task.hasTechnicalApproval ? '<span class="my-task-tech-badge"><i class="fas fa-cogs"></i> فني</span>' : '';
    const cardClass = `my-kanban-card status-${task.status} ${task.isTemplate ? 'template-task-card' : ''} ${task.isTransferred ? 'transferred-task-card' : ''} ${task.isAdditionalTask ? 'additional-task-card' : ''}`;

    // 🏆 تحسين عرض النقاط
    const pointsColor = task.points >= 20 ? 'bg-success' : task.points >= 10 ? 'bg-warning' : 'bg-secondary';

    // ✅ تحديد إمكانية السحب بناءً على حالة النقل والاعتماد
    const isDraggable = !(task.isTransferred || task.isAdditionalTask || task.isApproved);
    const draggableAttr = isDraggable ? 'true' : 'false';

    const card = $(`
        <div class="${cardClass}"
             data-task-id="${task.id}"
             data-task-user-id="${task.taskUserId || task.id}"
             data-status="${task.status}"
             data-is-template="${task.isTemplate}"
             data-is-transferred="${task.isTransferred || false}"
             data-is-additional-task="${task.isAdditionalTask || false}"
             data-is-approved="${task.isApproved || false}"
             data-user-id="${task.userId || window.currentUserId || 'current_user'}"
             data-initial-minutes="${task.initialMinutes || 0}"
             data-started-at="${task.startedAt || ''}"
             draggable="${draggableAttr}"
             style="${!isDraggable ? 'cursor: not-allowed;' : ''}"

            <!-- 📌 شريط الحالة العلوي -->
            <div class="my-card-status-bar" style="background: ${currentStatus.color}"></div>

            <!-- 📋 رأس الكارد -->
            <div class="my-card-header">
                <div class="my-card-status-indicator">
                    <i class="${currentStatus.icon}" style="color: ${currentStatus.color}"></i>
                    <span class="status-text">${statusText}</span>
                </div>
                ${templateBadge}
                ${approvedBadge}
                ${administrativeBadge}
                ${technicalBadge}
                ${transferBadge}
                ${additionalBadge}
            </div>

            <!-- 📝 عنوان المهمة -->
            <div class="my-kanban-card-title">
                ${task.name}
                ${task.notesCount && task.notesCount > 0 ? `<span class="task-notes-indicator ms-1" title="${task.notesCount} ملاحظات"><i class="fas fa-sticky-note"></i><span class="notes-count">${task.notesCount}</span></span>` : ''}
                ${(task.revisionsCount && task.revisionsCount > 0) ? `<span class="task-revisions-badge ${task.revisionsStatus} ms-1" title="${getMyTaskRevisionStatusTooltip(task)}"><i class="fas fa-edit"></i><span class="revisions-count">${task.revisionsCount}</span></span>` : ''}
            </div>

            <!-- 🏢 معلومات المشروع والدور -->
            <div class="my-kanban-card-meta">
                <div class="meta-item">
                    <span class="my-kanban-card-project">${task.project}</span>
                </div>
                <div class="meta-item">
                    <span class="my-kanban-card-role">${task.userRole}</span>
                </div>
            </div>

            <!-- ⏰ معلومات الوقت -->
            <div class="my-kanban-card-time">
                <span style="font-size: 10px; color: #6b7280;">مقدر: ${task.estimatedTime}</span>
                <span style="font-size: 10px; color: #6b7280;">فعلي: ${task.actualTime}</span>
            </div>

            ${task.status === 'in_progress' ? `
            <div class="my-kanban-card-timer" style="font-family: 'Courier New', monospace; font-weight: bold; color: #059669; padding: 4px 8px; background: #dcfce7; border-radius: 4px; font-size: 11px; text-align: center; margin-bottom: 8px;">
                <i class="fas fa-clock"></i> <span id="my-kanban-timer-${task.taskUserId || task.id}">${task.timer}</span>
            </div>
            ` : ''}

            <div class="my-kanban-card-points" style="text-align: center; margin-bottom: 8px;">
                <span class="badge ${pointsColor} text-dark" style="font-size: 9px; padding: 3px 6px;">
                    <i class="fas fa-star"></i> ${task.points} نقطة
                </span>
            </div>

            ${task.dueDate && task.dueDate !== 'غير محدد' ? `
            <div class="my-kanban-card-due-date ${priorityClass}" style="font-size: 10px; font-weight: 500; text-align: center; margin-bottom: 8px;">
                <i class="fas fa-calendar"></i> ${task.dueDate}
            </div>
            ` : ''}

        </div>
    `);
    window.MyTasksDragDrop.addDragDropToCard(card);
    return card;
}

function filterMyTasksKanban(projectId, status, searchText) {
    console.log('🔍 Filtering My Tasks Kanban:', {projectId, status, searchText});

    // ✅ الفلترة من DOM بدلاً من JavaScript array
    $('#myTasksKanbanView .kanban-card').each(function() {
        const card = $(this);
        let show = true;

        // فلتر المشروع
        if (projectId) {
            const cardProjectId = card.data('project-id');
            if (cardProjectId != projectId) {
                show = false;
            }
        }

        // فلتر الحالة
        if (status) {
            const cardStatus = card.data('status');
            if (cardStatus != status) {
                show = false;
            }
        }

        // فلتر البحث
        if (searchText) {
            const cardText = card.text().toLowerCase();
            if (cardText.indexOf(searchText) === -1) {
                show = false;
            }
        }

        // إظهار/إخفاء الكارد
        if (show) {
            card.show();
        } else {
            card.hide();
        }
    });

    // ✅ تحديث العدادات
    updateCardCounters();

    console.log('✅ My Tasks Kanban filtered');
}

function loadFilteredMyTasksIntoKanban(filteredTasks) {
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
        const container = $(`#my-cards-${status}`);
        const counter = $(`#my-count-${status}`);
        counter.text(tasks.length);
        tasks.forEach(task => {
            const card = createMyTaskCard(task);
            container.append(card);
        });
    });
}

function getMyTaskRevisionStatusTooltip(task) {
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

// ✅ تحديث عدادات الكاردات بناءً على DOM (الظاهرة فقط)
function updateCardCounters() {
    const statuses = ['new', 'in_progress', 'paused', 'completed', 'cancelled', 'transferred'];

    statuses.forEach(status => {
        const container = $(`#my-cards-${status}`);
        const counter = $(`#my-count-${status}`);

        // ✅ عد الكاردات الظاهرة فقط (مش المخفية)
        const visibleCardsCount = container.find('.kanban-card:visible').length;
        counter.text(visibleCardsCount);
    });

    console.log('✅ تم تحديث عدادات الكاردات');
}

window.MyTasksKanban = {
    initializeMyTasksKanbanBoard,
    loadMyTasksIntoKanban,
    createMyTaskCard,
    filterMyTasksKanban,
    loadFilteredMyTasksIntoKanban,
    updateCardCounters
};

// ✅ إتاحة filterMyTasksKanban globally للاستخدام من filters.js
window.filterMyTasksKanban = filterMyTasksKanban;




