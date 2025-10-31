
let currentRevisionsView = 'table';


function initializeRevisionsKanban() {
    console.log('🚀 Initializing Revisions Kanban Board...');


    const savedView = localStorage.getItem('revisionsViewPreference');
    if (savedView === 'kanban') {
        switchToKanbanView();
    }


    $('#tableViewBtn').on('click', function() {
        switchToTableView();
    });

    $('#kanbanViewBtn').on('click', function() {
        switchToKanbanView();
    });

    console.log('✅ Revisions Kanban Board initialized successfully');
}


function switchToTableView() {
    currentRevisionsView = 'table';

    $('.revisions-table').show();
    $('#revisionsKanbanBoard').hide();
    $('#tableViewBtn').addClass('active');
    $('#kanbanViewBtn').removeClass('active');

    localStorage.setItem('revisionsViewPreference', 'table');

    console.log('📊 Switched to table view');
}


function switchToKanbanView() {
    currentRevisionsView = 'kanban';

    $('.revisions-table').hide();
    $('#revisionsKanbanBoard').show();
    $('#tableViewBtn').removeClass('active');
    $('#kanbanViewBtn').addClass('active');

    localStorage.setItem('revisionsViewPreference', 'kanban');

    setTimeout(() => {
        loadRevisionsIntoKanban();
    }, 100);

    console.log('🎯 Switched to kanban view');
}

function loadRevisionsIntoKanban() {
    console.log('📋 Loading revisions into kanban...');

    const activeTab = $('#revisionTabs .nav-link.active').attr('data-bs-target');
    let endpoint = '/revision-page/all-revisions';

    if (activeTab === '#my-revisions') {
        endpoint = '/revision-page/my-revisions';
    } else if (activeTab === '#my-created-revisions') {
        endpoint = '/revision-page/my-created-revisions';
    }

    const filters = getCurrentFilters();
    const queryString = $.param(filters);
    const fullEndpoint = queryString ? `${endpoint}?${queryString}` : endpoint;

    $.get(fullEndpoint)
        .done(function(response) {
            if (response.success) {
                renderKanbanBoard(response.revisions.data);
            } else {
                console.error('Failed to load revisions:', response.message);
            }
        })
        .fail(function(error) {
            console.error('Error loading revisions:', error);
            toastr.error('حدث خطأ في تحميل التعديلات');
        });
}

function getCurrentFilters() {
    return {
        revision_type: $('#revisionTypeFilter').val(),
        revision_source: $('#revisionSourceFilter').val(),
        status: $('#statusFilter').val(),
        project_id: $('#projectFilter').val(),
        search: $('#searchInput').val()
    };
}

function renderKanbanBoard(revisions) {
    console.log('🎨 Rendering kanban board with', revisions.length, 'revisions');

    const currentUserId = typeof AUTH_USER_ID !== 'undefined' ? AUTH_USER_ID : '';

    const groupedRevisions = {
        new: [],
        in_progress: [],
        paused: [],
        completed: []
    };

    revisions.forEach(revision => {
        let userStatus = 'new';

        const isExecutor = revision.executor_user_id == currentUserId || revision.assigned_to == currentUserId;
        const isReviewer = isCurrentReviewer ? isCurrentReviewer(revision, currentUserId) : false;

        if (isExecutor) {
            userStatus = revision.status || 'new';
        } else if (isReviewer) {
            userStatus = revision.review_status || 'new';
        } else if (revision.responsible_user_id == currentUserId) {
            const executorStatus = revision.status || 'new';
            const reviewerStatus = revision.review_status || 'new';

            if (executorStatus === 'paused' || reviewerStatus === 'paused') {
                userStatus = 'paused';
            }
            else if (executorStatus === 'in_progress' || reviewerStatus === 'in_progress') {
                userStatus = 'in_progress';
            }
            else if (executorStatus === 'completed' && reviewerStatus === 'completed') {
                userStatus = 'completed';
            }
            else {
                userStatus = 'new';
            }
        } else {
            const executorStatus = revision.status || 'new';
            const reviewerStatus = revision.review_status || 'new';

            if (executorStatus === 'paused' || reviewerStatus === 'paused') {
                userStatus = 'paused';
            } else if (executorStatus === 'in_progress' || reviewerStatus === 'in_progress') {
                userStatus = 'in_progress';
            } else if (executorStatus === 'completed' && reviewerStatus === 'completed') {
                userStatus = 'completed';
            } else {
                userStatus = 'new';
            }
        }

        if (groupedRevisions[userStatus]) {
            groupedRevisions[userStatus].push(revision);
        }
    });

    renderKanbanColumn('new', groupedRevisions.new);
    renderKanbanColumn('in_progress', groupedRevisions.in_progress);
    renderKanbanColumn('paused', groupedRevisions.paused);
    renderKanbanColumn('completed', groupedRevisions.completed);

    setTimeout(() => {
        initializeKanbanTimers();
    }, 100);
}

function renderKanbanColumn(status, revisions) {
    const columnSelector = `#kanban-column-${status} .kanban-column-cards`;
    const countSelector = `#kanban-column-${status} .kanban-column-count`;

    $(countSelector).text(revisions.length);

    if (revisions.length === 0) {
        $(columnSelector).html(`
            <div class="kanban-empty-state">
                <i class="fas fa-inbox"></i>
                <p>لا توجد تعديلات</p>
            </div>
        `);
        return;
    }

    const cardsHtml = revisions.map(revision => createRevisionKanbanCard(revision)).join('');
    $(columnSelector).html(cardsHtml);
}

function createRevisionKanbanCard(revision) {
    const currentUserId = typeof AUTH_USER_ID !== 'undefined' ? AUTH_USER_ID : '';
    const isExecutor = revision.executor_user_id == currentUserId || revision.assigned_to == currentUserId;
    const isReviewer = revision.assigned_reviewer_id == currentUserId;

    let workType = '';
    let workStatus = 'new';
    if (isExecutor) {
        workType = 'executor';
        workStatus = revision.status || 'new';
    } else if (isReviewer) {
        workType = 'reviewer';
        workStatus = revision.review_status || 'new';
    }

    const sourceIcons = {
        'internal': 'fa-building',
        'client': 'fa-user',
        'external': 'fa-globe'
    };

    const sourceIcon = sourceIcons[revision.revision_source] || 'fa-question';

    let timerHtml = '';
    if (workType === 'executor' && workStatus === 'in_progress') {
        const initialSeconds = calculateInitialRevisionTime(revision);
        timerHtml = `
            <div class="revision-kanban-card-timer" id="kanban-timer-${revision.id}">
                <i class="fas fa-clock"></i>
                <span>${formatRevisionTime(initialSeconds)}</span>
            </div>
        `;
    }

    if (workType === 'reviewer' && workStatus === 'in_progress') {
        const initialSeconds = calculateInitialReviewTime(revision);
        timerHtml = `
            <div class="revision-kanban-card-timer review-timer" id="kanban-review-timer-${revision.id}">
                <i class="fas fa-clock"></i>
                <span>${formatRevisionTime(initialSeconds)}</span>
            </div>
        `;
    }

    let usersHtml = '';
    if (revision.responsible_user) {
        usersHtml += `
            <div class="revision-kanban-user-badge responsible" style="border-color: #dc2626; background: #fef2f2; border-width: 3px;">
                <div style="display: flex; align-items: center; gap: 0.4rem; width: 100%; font-size: 0.8rem;">
                    <i class="fas fa-exclamation-triangle" style="color: #dc2626; font-size: 1rem;"></i>
                    <strong style="color: #991b1b;">المسؤول:</strong>
                    <span style="color: #1e293b;">${revision.responsible_user.name}</span>
                </div>
            </div>
        `;
    }

    if (revision.executor_user) {
        const executorStatus = revision.status || 'new';
        let executorStatusText = '';
        let executorStatusIcon = '';
        let executorStatusColor = '';
        let executorBgColor = '';

        if (executorStatus === 'new') {
            executorStatusText = 'لم يبدأ';
            executorStatusIcon = 'fa-clock';
            executorStatusColor = '#6366f1';
            executorBgColor = '#eef2ff';
        } else if (executorStatus === 'in_progress') {
            executorStatusText = 'جاري العمل';
            executorStatusIcon = 'fa-spinner fa-spin';
            executorStatusColor = '#f59e0b';
            executorBgColor = '#fef3c7';
        } else if (executorStatus === 'paused') {
            executorStatusText = 'متوقف';
            executorStatusIcon = 'fa-pause';
            executorStatusColor = '#8b5cf6';
            executorBgColor = '#f3e8ff';
        } else if (executorStatus === 'completed') {
            executorStatusText = 'مكتمل';
            executorStatusIcon = 'fa-check';
            executorStatusColor = '#10b981';
            executorBgColor = '#d1fae5';
        }

        usersHtml += `
            <div class="revision-kanban-user-badge executor" style="border-color: ${executorStatusColor}; background: ${executorBgColor}; border-width: 3px;">
                <div style="display: flex; align-items: center; gap: 0.4rem; width: 100%; font-size: 0.8rem;">
                    <i class="fas fa-hammer" style="font-size: 1rem;"></i>
                    <strong style="color: #1e40af;">المنفذ:</strong>
                    <span style="color: #1e293b;">${revision.executor_user.name}</span>
                </div>
                <div style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 0.4rem; padding: 0.35rem 0.5rem; background: ${executorStatusColor}15; border: 2px solid ${executorStatusColor}; border-radius: 6px; margin-top: 0.4rem;">
                    <i class="fas ${executorStatusIcon}" style="color: ${executorStatusColor}; font-size: 1rem;"></i>
                    <strong style="color: ${executorStatusColor}; font-size: 0.85rem;">${executorStatusText}</strong>
                </div>
            </div>
        `;
    }

    const reviewers = (typeof getAllReviewers === 'function') ? getAllReviewers(revision) : [];
    if (reviewers && reviewers.length > 0) {
        reviewers.forEach((reviewer, index) => {
            const reviewerName = reviewer.user ? reviewer.user.name :
                (window.allUsers?.find(u => u.id == reviewer.reviewer_id)?.name || ('مراجع ' + reviewer.order));

            let reviewerStatusText = '';
            let reviewerStatusIcon = '';
            let reviewerStatusColor = '';
            let reviewerBgColor = '';

            if (reviewer.status === 'pending') {
                reviewerStatusText = 'في الانتظار';
                reviewerStatusIcon = 'fa-clock';
                reviewerStatusColor = '#6b7280';
                reviewerBgColor = '#f3f4f6';
            } else if (reviewer.status === 'in_progress') {
                reviewerStatusText = 'قيد المراجعة';
                reviewerStatusIcon = 'fa-spinner fa-spin';
                reviewerStatusColor = '#f59e0b';
                reviewerBgColor = '#fef3c7';
            } else if (reviewer.status === 'completed') {
                reviewerStatusText = 'تمت المراجعة';
                reviewerStatusIcon = 'fa-check-circle';
                reviewerStatusColor = '#10b981';
                reviewerBgColor = '#d1fae5';
            }

            const isCurrent = reviewer.status === 'in_progress' || reviewer.status === 'pending';

            usersHtml += `
                <div class="revision-kanban-user-badge reviewer" style="border-color: ${reviewerStatusColor}; background: ${reviewerBgColor}; border-width: ${isCurrent ? '3px' : '2px'};">
                    <div style="display: flex; align-items: center; justify-content: space-between; width: 100%; font-size: 0.75rem; margin-bottom: 0.3rem;">
                        <div style="display: flex; align-items: center; gap: 0.3rem;">
                            <div style="background: #1e293b; color: white; width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: bold;">
                                ${index + 1}
                            </div>
                            <strong style="color: #15803d;">مراجع:</strong>
                        </div>
                        ${isCurrent ? '<span class="badge bg-success" style="font-size: 8px; padding: 2px 5px;">الحالي</span>' : ''}
                    </div>
                    <div style="width: 100%; font-size: 0.8rem; margin-bottom: 0.3rem;">
                        <span style="color: #1e293b; font-weight: 600;">${reviewerName}</span>
                    </div>
                    <div style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 0.4rem; padding: 0.35rem 0.5rem; background: ${reviewerStatusColor}15; border: 2px solid ${reviewerStatusColor}; border-radius: 6px;">
                        <i class="fas ${reviewerStatusIcon}" style="color: ${reviewerStatusColor}; font-size: 0.9rem;"></i>
                        <strong style="color: ${reviewerStatusColor}; font-size: 0.8rem;">${reviewerStatusText}</strong>
                    </div>
                </div>
            `;
        });
    }

    let actionsHtml = '';
    if (workType === 'executor') {
        if (['new', 'in_progress', 'paused'].includes(workStatus)) {
            actionsHtml = getRevisionActionButtonsCompact(revision);
        } else if (workStatus === 'completed') {
            actionsHtml = `
                <button class="btn btn-sm btn-warning" onclick="event.stopPropagation(); reopenWork(${revision.id});" title="إعادة فتح العمل">
                    <i class="fas fa-undo"></i>
                </button>
            `;
        }
    } else if (workType === 'reviewer') {
        if (['new', 'in_progress', 'paused'].includes(workStatus)) {
            actionsHtml = getReviewActionButtonsCompact(revision);
        } else if (workStatus === 'completed') {
            actionsHtml = `
                <button class="btn btn-sm btn-warning" onclick="event.stopPropagation(); reopenReview(${revision.id});" title="إعادة فتح المراجعة">
                    <i class="fas fa-undo"></i>
                </button>
            `;
        }
    }

    let workTypeBadge = '';
    if (workType === 'executor') {
        workTypeBadge = `
            <span class="badge bg-primary" style="font-size: 0.7rem;">
                <i class="fas fa-hammer"></i> تنفيذ
            </span>
        `;
    } else if (workType === 'reviewer') {
        workTypeBadge = `
            <span class="badge bg-success" style="font-size: 0.7rem;">
                <i class="fas fa-check-circle"></i> مراجعة
            </span>
        `;
    }

    return `
        <div class="revision-kanban-card"
             data-revision-id="${revision.id}"
             data-status="${revision.status}"
             data-review-status="${revision.review_status || 'new'}"
             data-work-type="${workType}"
             onclick="showRevisionDetails(${revision.id})">
            <div class="revision-kanban-card-header">
                <div class="revision-kanban-card-title">
                    ${workTypeBadge}
                    ${revision.title}
                </div>
                <span class="revision-kanban-card-source source-${revision.revision_source}">
                    <i class="fas ${sourceIcon}"></i>
                </span>
            </div>

            <div class="revision-kanban-card-body">
                <div class="revision-kanban-card-description">${revision.description}</div>
            </div>

            <div class="revision-kanban-card-meta">
                <div class="revision-kanban-card-meta-item">
                    <i class="fas fa-user"></i>
                    ${revision.creator ? revision.creator.name : 'غير محدد'}
                </div>
                ${revision.project ? `
                    <div class="revision-kanban-card-meta-item" title="${revision.project.name}">
                        <i class="fas fa-project-diagram"></i>
                        ${revision.project.code || revision.project.name}
                    </div>
                ` : ''}
            </div>

            ${usersHtml ? `<div class="revision-kanban-card-users">${usersHtml}</div>` : ''}

            ${timerHtml}

            <div class="revision-kanban-card-footer">
                <div class="revision-kanban-card-date">
                    <i class="fas fa-calendar"></i>
                    ${formatDate(revision.revision_date)}
                </div>
                ${actionsHtml ? `
                    <div class="revision-kanban-card-actions" onclick="event.stopPropagation()">
                        ${actionsHtml}
                    </div>
                ` : ''}
            </div>
        </div>
    `;
}

function initializeKanbanTimers() {
    console.log('⏱️ Initializing kanban timers...');

    $('.revision-kanban-card[data-status="in_progress"]').each(function() {
        const revisionId = $(this).data('revision-id');
        if (revisionTimers[revisionId]) {
            startKanbanTimer(revisionId, 'execution');
        }
    });

    $('.revision-kanban-card[data-review-status="in_progress"]').each(function() {
        const revisionId = $(this).data('revision-id');
        if (reviewTimers[revisionId]) {
            startKanbanTimer(revisionId, 'review');
        }
    });
}

function startKanbanTimer(revisionId, type) {
    const timerId = type === 'review' ? `kanban-review-timer-${revisionId}` : `kanban-timer-${revisionId}`;
    const timers = type === 'review' ? reviewTimers : revisionTimers;
    const intervals = type === 'review' ? reviewTimerIntervals : revisionTimerIntervals;

    if (intervals[revisionId]) {
        return;
    }

    const intervalId = setInterval(() => {
        if (timers[revisionId]) {
            timers[revisionId].seconds++;
            const element = document.getElementById(timerId);
            if (element) {
                const span = element.querySelector('span');
                if (span) {
                    span.textContent = formatRevisionTime(timers[revisionId].seconds);
                }
            }
        } else {
            clearInterval(intervalId);
            delete intervals[revisionId];
        }
    }, 1000);

    intervals[revisionId] = intervalId;
}

function updateKanbanOnTabChange() {
    if (currentRevisionsView === 'kanban') {
        loadRevisionsIntoKanban();
    }
}

function updateKanbanOnFilter() {
    if (currentRevisionsView === 'kanban') {
        loadRevisionsIntoKanban();
    }
}
    
window.initializeRevisionsKanban = initializeRevisionsKanban;
window.switchToTableView = switchToTableView;
window.switchToKanbanView = switchToKanbanView;
window.loadRevisionsIntoKanban = loadRevisionsIntoKanban;
window.updateKanbanOnTabChange = updateKanbanOnTabChange;
window.updateKanbanOnFilter = updateKanbanOnFilter;
window.currentRevisionsView = () => currentRevisionsView;

