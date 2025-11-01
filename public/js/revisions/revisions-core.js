
let revisionTimers = {};
let revisionTimerIntervals = {};


let reviewTimers = {};
let reviewTimerIntervals = {};

window.allUsers = [];


async function loadAllUsersForReviewers() {
    try {
        const response = await fetch('/users/all', {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        });
        const result = await response.json();
        if (result.success) {
            window.allUsers = result.users || [];
        }
    } catch (error) {
        console.error('Error loading users:', error);
    }
}


function getCurrentReviewer(revision) {
    if (!revision.reviewers || !Array.isArray(revision.reviewers) || revision.reviewers.length === 0) {
        return null;
    }

    const currentReviewer = revision.reviewers.find(r => r.status === 'pending' || r.status === 'in_progress');
    return currentReviewer || null;
}

function isCurrentReviewer(revision, userId) {
    const currentReviewer = getCurrentReviewer(revision);
    return currentReviewer && currentReviewer.reviewer_id == userId;
}


function getAllReviewers(revision) {
    if (revision.reviewers_with_data && Array.isArray(revision.reviewers_with_data)) {
        return revision.reviewers_with_data;
    }
    if (revision.reviewers && Array.isArray(revision.reviewers)) {
        return revision.reviewers;
    }
    return [];
}

$(document).ready(function() {
    loadAllUsersForReviewers();
    loadStats();
    loadAllRevisions();
    loadProjectsList();

    if (typeof initializeRevisionsKanban === 'function') {
        initializeRevisionsKanban();
    }

    $('#revisionTabs button[data-bs-toggle="pill"]').on('shown.bs.tab', function (e) {
        const target = $(e.target).attr('data-bs-target');
        if (target === '#my-revisions') {
            loadMyRevisions();
        } else if (target === '#my-created-revisions') {
            loadMyCreatedRevisions();
        } else if (target === '#all-revisions') {
            loadAllRevisions();
        }

        if (typeof updateKanbanOnTabChange === 'function') {
            updateKanbanOnTabChange();
        }
    });

    $('#allSearchInput, #mySearchInput, #myCreatedSearchInput').on('keyup', debounce(function() {
        let tabType = 'all';
        if ($(this).attr('id').includes('myCreated')) {
            tabType = 'myCreated';
        } else if ($(this).attr('id').includes('my')) {
            tabType = 'my';
        }
        applyFilters(tabType);
    }, 500));

    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $('#revisionSidebar').hasClass('active')) {
            closeSidebar();
        }
    });
});

function loadStats() {
    $.get('/revision-page/stats')
        .done(function(response) {
            if (response.success) {
                renderStats(response.stats);
            }
        })
        .fail(function() {
            toastr.error('حدث خطأ في تحميل الإحصائيات');
        });

    loadTransferStats();
}

function loadTransferStats() {
    $.get('/task-revisions/user-transfer-stats')
        .done(function(response) {
            if (response.success && response.stats.has_transfers) {
                renderTransferStats(response.stats);
            }
        })
        .fail(function() {
            console.error('حدث خطأ في تحميل إحصائيات النقل');
        });
}

function renderTransferStats(stats) {
    const html = `
        <div class="col-12">
            <div class="alert alert-info shadow-sm border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="mb-2">
                            <i class="fas fa-exchange-alt me-2"></i>
                            إحصائيات نقل التعديلات
                        </h5>
                    </div>
                    <div class="text-end">
                        <div class="row g-3">
                            <div class="col-auto">
                                <div class="card bg-white bg-opacity-25 border-0 text-white" style="min-width: 150px;">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <div class="small mb-1">منقول ليك</div>
                                                <div class="h4 mb-0">${stats.transferred_to_me}</div>
                                            </div>
                                            <div>
                                                <i class="fas fa-arrow-down fa-2x opacity-75"></i>
                                            </div>
                                        </div>
                                        <div class="mt-2 pt-2 border-top border-white border-opacity-25">
                                            <small>
                                                <i class="fas fa-hammer me-1"></i>منفذ: ${stats.executor_transferred_to_me}
                                                <span class="mx-1">|</span>
                                                <i class="fas fa-user-check me-1"></i>مراجع: ${stats.reviewer_transferred_to_me}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="card bg-white bg-opacity-25 border-0 text-white" style="min-width: 150px;">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <div class="small mb-1">منقول منك</div>
                                                <div class="h4 mb-0">${stats.transferred_from_me}</div>
                                            </div>
                                            <div>
                                                <i class="fas fa-arrow-up fa-2x opacity-75"></i>
                                            </div>
                                        </div>
                                        <div class="mt-2 pt-2 border-top border-white border-opacity-25">
                                            <small>
                                                <i class="fas fa-hammer me-1"></i>منفذ: ${stats.executor_transferred_from_me}
                                                <span class="mx-1">|</span>
                                                <i class="fas fa-user-check me-1"></i>مراجع: ${stats.reviewer_transferred_from_me}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

    $('#transferStatsContainer').html(html).show();
}

function loadAllRevisions(page = 1, filters = {}) {
    showLoading();

    const params = {
        page: page,
        ...filters
    };

    $.get('/revision-page/all-revisions', params)
        .done(function(response) {
            if (response.success) {
                renderRevisions(response.revisions, 'all');
                $('#allRevisionsCount').text(response.revisions.total || 0);

                setTimeout(() => {
                    console.log('🚀 بدء تهيئة التايمرات بعد تحميل البيانات...');
                    initializeRevisionTimers();
                }, 500);
            }
        })
        .fail(function() {
            toastr.error('حدث خطأ في تحميل التعديلات');
        })
        .always(function() {
            hideLoading();
        });
}

function loadMyRevisions(page = 1, filters = {}) {
    showLoading();

    const params = {
        page: page,
        ...filters
    };

    $.get('/revision-page/my-revisions', params)
        .done(function(response) {
            if (response.success) {
                renderRevisions(response.revisions, 'my');
                $('#myRevisionsCount').text(response.revisions.total || 0);

                setTimeout(() => {
                    console.log('🚀 بدء تهيئة التايمرات بعد تحميل البيانات...');
                    initializeRevisionTimers();
                }, 500);
            }
        })
        .fail(function() {
            toastr.error('حدث خطأ في تحميل تعديلاتك');
        })
        .always(function() {
            hideLoading();
        });
}

function loadMyCreatedRevisions(page = 1, filters = {}) {
    showLoading();

    const params = {
        page: page,
        ...filters
    };

    $.get('/revision-page/my-created-revisions', params)
        .done(function(response) {
            if (response.success) {
                renderRevisions(response.revisions, 'myCreated');
                $('#myCreatedRevisionsCount').text(response.revisions.total || 0);

                setTimeout(() => {
                    console.log('🚀 بدء تهيئة التايمرات بعد تحميل البيانات...');
                    initializeRevisionTimers();
                }, 500);
            }
        })
        .fail(function() {
            toastr.error('حدث خطأ في تحميل التعديلات التي أضفتها');
        })
        .always(function() {
            hideLoading();
        });
}

function renderStats(stats) {
    const html = `
        <div class="col-md-6 mb-4">
            <div class="stats-card">
                <h5 class="mb-3"><i class="fas fa-chart-bar me-2"></i>الإحصائيات العامة</h5>
                <div class="row">
                    <div class="col-6">
                        <div class="stats-item">
                            <div class="stats-number">${stats.general.total}</div>
                            <div>إجمالي التعديلات</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stats-item">
                            <div class="stats-number">${stats.general.new || 0}</div>
                            <div>في الانتظار</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stats-item">
                            <div class="stats-number">${stats.general.in_progress || 0}</div>
                            <div>جاري العمل</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stats-item">
                            <div class="stats-number">${stats.general.paused || 0}</div>
                            <div>متوقفة</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stats-item">
                            <div class="stats-number">${stats.general.completed || 0}</div>
                            <div>مكتملة</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="stats-card">
                <h5 class="mb-3"><i class="fas fa-user-edit me-2"></i>التعديلات التي أضفتها</h5>
                <div class="row">
                    <div class="col-6">
                        <div class="stats-item">
                            <div class="stats-number">${stats.my_created_revisions.total}</div>
                            <div>إجمالي أضفتها</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stats-item">
                            <div class="stats-number">${stats.my_created_revisions.new || 0}</div>
                            <div>في الانتظار</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stats-item">
                            <div class="stats-number">${stats.my_created_revisions.in_progress || 0}</div>
                            <div>جاري العمل</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stats-item">
                            <div class="stats-number">${stats.my_created_revisions.paused || 0}</div>
                            <div>متوقفة</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stats-item">
                            <div class="stats-number">${stats.my_created_revisions.completed || 0}</div>
                            <div>مكتملة</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

    $('#statsContainer').html(html);
}

function groupProjectsByStatus(revisions) {
    const projectsMap = {};

    revisions.forEach(revision => {
        if (!revision.project) return;

        const projectId = revision.project.id;
        const projectCode = revision.project.code || revision.project.name;
        const projectName = revision.project.name;

        if (!projectsMap[projectId]) {
            projectsMap[projectId] = {
                id: projectId,
                code: projectCode,
                name: projectName,
                revisions: [],
                statuses: new Set()
            };
        }

        projectsMap[projectId].revisions.push(revision);
        projectsMap[projectId].statuses.add(revision.status);
    });

    const projects = Object.values(projectsMap);
    const grouped = {
        all: projects,
        new: [],
        in_progress: [],
        paused: [],
        completed: []
    };

    projects.forEach(project => {
        if (project.statuses.has('new')) grouped.new.push(project);
        if (project.statuses.has('in_progress')) grouped.in_progress.push(project);
        if (project.statuses.has('paused')) grouped.paused.push(project);
        if (project.statuses.has('completed')) grouped.completed.push(project);
    });

    return grouped;
}

function renderProjectsByStatusTabs(projectsByStatus) {
    const statusConfig = {
        all: { label: 'الكل', icon: 'fa-list', color: '#667eea', count: projectsByStatus.all.length },
        new: { label: 'جديد', icon: 'fa-plus-circle', color: '#6c757d', count: projectsByStatus.new.length },
        in_progress: { label: 'جاري العمل', icon: 'fa-spinner', color: '#0d6efd', count: projectsByStatus.in_progress.length },
        paused: { label: 'متوقف', icon: 'fa-pause-circle', color: '#ffc107', count: projectsByStatus.paused.length },
        completed: { label: 'مكتمل', icon: 'fa-check-circle', color: '#198754', count: projectsByStatus.completed.length }
    };

    let tabsRow = '<tr class="projects-filter-tabs-row">';
    tabsRow += '<td colspan="8" style="padding: 0; background: #f8f9fa; border-bottom: 2px solid #e9ecef;">';
    tabsRow += '<div class="d-flex gap-2 p-3" id="projectsStatusTabs">';

    Object.keys(statusConfig).forEach((status, index) => {
        const config = statusConfig[status];
        const activeClass = index === 0 ? 'active' : '';

        tabsRow += `
            <button class="btn projects-tab-btn ${activeClass}"
                    id="projects-${status}-tab"
                    data-status="${status}"
                    onclick="filterProjectsByStatus('${status}')"
                    style="flex: 1; border-radius: 8px; padding: 10px 15px; border: 2px solid transparent; transition: all 0.3s ease;">
                <i class="fas ${config.icon} me-2"></i>
                ${config.label}
                <span class="badge ms-2" style="background-color: ${config.color}15; color: ${config.color}; border: 1px solid ${config.color}40;">
                    ${config.count}
                </span>
            </button>
        `;
    });

    tabsRow += '</div></td></tr>';

    let tableHtml = `
        <div class="projects-table-wrapper mb-4">
            <div class="revisions-table">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 projects-status-table">
                        <thead>
                            <tr>
                                <th style="width: 15%;">كود المشروع</th>
                                <th style="width: 20%;">اسم المشروع</th>
                                <th style="width: 10%;">إجمالي التعديلات</th>
                                <th style="width: 12%;">جديد</th>
                                <th style="width: 12%;">جاري العمل</th>
                                <th style="width: 12%;">متوقف</th>
                                <th style="width: 12%;">مكتمل</th>
                                <th style="width: 7%;">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${tabsRow}
    `;

    Object.keys(statusConfig).forEach((status, index) => {
        const config = statusConfig[status];
        const projects = projectsByStatus[status];
        const activeClass = index === 0 ? '' : 'd-none';

        if (projects.length === 0) {
            tableHtml += `
                <tr class="projects-rows-${status} ${activeClass}">
                    <td colspan="8" class="text-center py-4 text-muted">
                        <i class="fas fa-folder-open fa-2x mb-2 d-block"></i>
                        لا توجد مشاريع بهذه الحالة
                    </td>
                </tr>
            `;
        } else {
            projects.forEach(project => {
                const statusCounts = {
                    new: 0,
                    in_progress: 0,
                    paused: 0,
                    completed: 0
                };

                project.revisions.forEach(rev => {
                    if (statusCounts.hasOwnProperty(rev.status)) {
                        statusCounts[rev.status]++;
                    }
                });

                tableHtml += `
                    <tr class="projects-rows-${status} ${activeClass}"
                        onclick="filterRevisionsByProject(${project.id}, '${project.code}')"
                        style="cursor: pointer; transition: all 0.3s ease;">
                        <td>
                            <strong style="color: ${config.color};">
                                <i class="fas fa-project-diagram me-1"></i>
                                ${project.code}
                            </strong>
                        </td>
                        <td>${project.name}</td>
                        <td>
                            <span class="badge" style="background-color: ${config.color}15; color: ${config.color};">
                                ${project.revisions.length} تعديل
                            </span>
                        </td>
                        <td>
                            ${statusCounts.new > 0 ? `
                                <span class="badge status-new">${statusCounts.new}</span>
                            ` : '<small class="text-muted">-</small>'}
                        </td>
                        <td>
                            ${statusCounts.in_progress > 0 ? `
                                <span class="badge status-in_progress">${statusCounts.in_progress}</span>
                            ` : '<small class="text-muted">-</small>'}
                        </td>
                        <td>
                            ${statusCounts.paused > 0 ? `
                                <span class="badge status-paused">${statusCounts.paused}</span>
                            ` : '<small class="text-muted">-</small>'}
                        </td>
                        <td>
                            ${statusCounts.completed > 0 ? `
                                <span class="badge status-completed">${statusCounts.completed}</span>
                            ` : '<small class="text-muted">-</small>'}
                        </td>
                        <td>
                            <button class="btn btn-sm btn-primary"
                                    onclick="event.stopPropagation(); filterRevisionsByProject(${project.id}, '${project.code}')"
                                    title="عرض التعديلات">
                                <i class="fas fa-filter"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
        }
    });

    tableHtml += `
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;

    return tableHtml;
}

function renderRevisions(data, type) {
    let container, paginationContainer;

    if (type === 'all') {
        container = '#allRevisionsContainer';
        paginationContainer = '#allRevisionsPagination';
    } else if (type === 'my') {
        container = '#myRevisionsContainer';
        paginationContainer = '#myRevisionsPagination';
    } else if (type === 'myCreated') {
        container = '#myCreatedRevisionsContainer';
        paginationContainer = '#myCreatedRevisionsPagination';
    }

    if (!data.data || data.data.length === 0) {
        $(container).html(`
            <div class="empty-state">
                <i class="fas fa-edit"></i>
                <h4>لا توجد تعديلات</h4>
                <p class="text-muted">لم يتم العثور على تعديلات بالمعايير المحددة</p>
            </div>
        `);
        $(paginationContainer).empty();
        return;
    }

    const projectRevisions = data.data.filter(r => r.revision_type === 'project');
    const taskRevisions = data.data.filter(r => r.revision_type === 'task');

    let html = '';

    if (projectRevisions.length > 0) {
        const projectsByStatus = groupProjectsByStatus(projectRevisions);

        html += `
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0" style="color: #667eea; font-weight: 600;">
                        <i class="fas fa-project-diagram me-2"></i>
                        تعديلات المشاريع (${projectRevisions.length})
                    </h5>
                    <button class="btn btn-sm btn-outline-primary"
                            onclick="toggleProjectsTable()"
                            id="toggleProjectsBtn"
                            title="عرض/إخفاء جدول المشاريع">
                        <i class="fas fa-table me-1"></i>
                        المشاريع
                        <i class="fas fa-chevron-down ms-1" id="toggleProjectsIcon"></i>
                    </button>
                </div>

                <div id="projectsTableContainer" style="display: none;">
                    ${renderProjectsByStatusTabs(projectsByStatus)}
                </div>

                <div class="revisions-table">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 11%; min-width: 120px;">العنوان</th>
                                    <th style="width: 6%; min-width: 70px;">الحالة</th>
                                    <th style="width: 5%; min-width: 55px;">المصدر</th>
                                    <th style="width: 9%; min-width: 95px;">المنشئ</th>
                                    <th style="width: 9%; min-width: 95px;"><span class="text-danger">⚠️ المسؤول</span></th>
                                    <th style="width: 9%; min-width: 95px;"><span class="text-primary">🔨 المنفذ</span></th>
                                    <th style="width: 9%; min-width: 95px;"><span class="text-success">✅ المراجع</span></th>
                                    <th style="width: 6%; min-width: 70px;">التاريخ</th>
                                    <th style="width: 5%; min-width: 60px;">وقت التنفيذ</th>
                                    <th style="width: 5%; min-width: 60px;">وقت المراجعة</th>
                                    <th style="width: 6%; min-width: 70px;">تايمر التنفيذ</th>
                                    <th style="width: 6%; min-width: 70px;">تايمر المراجعة</th>
                                    <th style="width: 7%; min-width: 80px;">المشروع</th>
                                    <th style="width: 7%; min-width: 95px;">إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
        `;

        projectRevisions.forEach(revision => {
            html += renderRevisionRow(revision);
        });

        html += `
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
    }

    // ✅ جدول تعديلات المهام (بدون مشروع)
    if (taskRevisions.length > 0) {
        html += `
            <div class="mb-4">
                <h5 class="mb-3" style="color: #f59e0b; font-weight: 600;">
                    <i class="fas fa-tasks me-2"></i>
                    تعديلات المهام (${taskRevisions.length})
                </h5>
                <div class="revisions-table">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 12%; min-width: 130px;">العنوان</th>
                                    <th style="width: 7%; min-width: 75px;">الحالة</th>
                                    <th style="width: 6%; min-width: 60px;">المصدر</th>
                                    <th style="width: 10%; min-width: 100px;">المنشئ</th>
                                    <th style="width: 10%; min-width: 100px;"><span class="text-info">👤 المسند إليه</span></th>
                                    <th style="width: 10%; min-width: 100px;"><span class="text-danger">⚠️ المسؤول</span></th>
                                    <th style="width: 10%; min-width: 100px;"><span class="text-primary">🔨 المنفذ</span></th>
                                    <th style="width: 7%; min-width: 75px;">التاريخ</th>
                                    <th style="width: 6%; min-width: 65px;">وقت التنفيذ</th>
                                    <th style="width: 7%; min-width: 75px;">تايمر التنفيذ</th>
                                    <th style="width: 8%; min-width: 100px;">إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
        `;

        taskRevisions.forEach(revision => {
            html += renderTaskRevisionRow(revision);
        });

        html += `
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
    }

    $(container).html(html);
    renderPagination(data, paginationContainer, type);

    // logging للتأكد من البيانات
    console.log('🏁 انتهى من رسم التعديلات للـ tab:', type);
    console.log('📊 revisionTimers الحالي:', revisionTimers);
    console.log('⏱️ revisionTimerIntervals الحالي:', revisionTimerIntervals);

    // logging لعدد التعديلات قيد التنفيذ
    const inProgressRevisions = Object.values(revisionTimers).filter(timer => timer.status === 'in_progress');
    console.log('🎯 عدد التعديلات قيد التنفيذ:', inProgressRevisions.length);
    inProgressRevisions.forEach(timer => {
        console.log('   - التعديل:', timer.revision.id, 'الوقت:', timer.seconds, 'ثانية');
    });
}

// Render single revision row
function renderRevisionRow(revision) {
    const statusClass = `status-${revision.status}`;
    const sourceClass = `source-${revision.revision_source}`;
    const currentUserId = typeof AUTH_USER_ID !== 'undefined' ? AUTH_USER_ID : '';

    // التحقق من أن المستخدم هو المسند له التعديل (منفذ)
    const isAssignedToMe = revision.assigned_to == currentUserId ||
                          revision.executor_user_id == currentUserId ||
                          (revision.task_user && revision.task_user.user_id == currentUserId) ||
                          (revision.template_task_user && revision.template_task_user.user_id == currentUserId);

    // التحقق من أن المستخدم هو المراجع الحالي
    const isReviewer = isCurrentReviewer(revision, currentUserId);

    // حساب الوقت الأولي للتايمر (التنفيذ)
    let initialTimerSeconds = 0;
    if (revision.status === 'in_progress') {
        initialTimerSeconds = calculateInitialRevisionTime(revision);
        console.log('🕐 حساب الوقت الأولي للتعديل', revision.id, ':', initialTimerSeconds, 'ثانية');

        // إضافة البيانات للـ timers object (مع الحفاظ على الـ seconds الحالية لو موجودة)
        if (!revisionTimers[revision.id]) {
            revisionTimers[revision.id] = {
                status: revision.status,
                seconds: initialTimerSeconds,
                revision: revision
            };
            console.log('📝 إضافة التعديل للـ timers object:', revision.id);
        } else {
            // التعديل موجود بالفعل - نحدث البيانات بس مش الـ seconds
            revisionTimers[revision.id].status = revision.status;
            revisionTimers[revision.id].revision = revision;
            console.log('♻️ تحديث بيانات التعديل (بدون تغيير الـ seconds):', revision.id);
        }
    }

    // حساب الوقت الأولي لتايمر المراجعة
    let initialReviewTimerSeconds = 0;
    if (revision.review_status === 'in_progress') {
        initialReviewTimerSeconds = calculateInitialReviewTime(revision);
        console.log('🕐 حساب الوقت الأولي للمراجعة', revision.id, ':', initialReviewTimerSeconds, 'ثانية');

        // إضافة البيانات للـ review timers object
        if (!reviewTimers[revision.id]) {
            reviewTimers[revision.id] = {
                status: revision.review_status,
                seconds: initialReviewTimerSeconds,
                revision: revision
            };
            console.log('📝 إضافة المراجعة للـ review timers object:', revision.id);
        } else {
            reviewTimers[revision.id].status = revision.review_status;
            reviewTimers[revision.id].revision = revision;
            console.log('♻️ تحديث بيانات المراجعة (بدون تغيير الـ seconds):', revision.id);
        }
    }

    return `
        <tr data-revision-id="${revision.id}">
            <td onclick="showRevisionDetails(${revision.id})" style="cursor: pointer;">
                <div class="fw-bold text-truncate" style="max-width: 200px;" title="${revision.title}">
                    ${revision.title}
                </div>
                ${revision.project && revision.project.code ? `
                    <small class="text-primary d-block text-truncate" style="max-width: 200px; font-weight: 500;" title="${revision.project.code}">
                        <i class="fas fa-project-diagram me-1"></i>
                        ${revision.project.code}
                    </small>
                ` : ''}
                <small class="text-muted d-block text-truncate" style="max-width: 200px;" title="${revision.description}">
                    ${revision.description}
                </small>
            </td>
            <td onclick="showRevisionDetails(${revision.id})" style="cursor: pointer;">
                <span class="status-badge ${statusClass}">${getStatusText(revision.status)}</span>
            </td>
            <td onclick="showRevisionDetails(${revision.id})" style="cursor: pointer;">
                <span class="source-badge ${sourceClass}">
                    <i class="${getSourceIcon(revision.revision_source)} me-1"></i>
                    ${getSourceText(revision.revision_source)}
                </span>
            </td>
            <td onclick="showRevisionDetails(${revision.id})" style="cursor: pointer;">
                <div class="user-info" title="${revision.creator ? revision.creator.name : 'غير محدد'}">
                    <div class="user-avatar">
                        ${revision.creator ? revision.creator.name.charAt(0) : 'غ'}
                    </div>
                    <div style="max-width: 100%; overflow: hidden;">
                        <div class="fw-bold small text-truncate">${revision.creator ? revision.creator.name : 'غير محدد'}</div>
                    </div>
                </div>
            </td>
            <td onclick="showRevisionDetails(${revision.id})" style="cursor: pointer;">
                ${revision.responsible_user ? `
                    <div class="user-info" title="${revision.responsible_user.name}">
                        <div class="user-avatar" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);">
                            ${revision.responsible_user.name.charAt(0)}
                        </div>
                        <div style="max-width: 100%; overflow: hidden;">
                            <div class="fw-bold small text-danger text-truncate">${revision.responsible_user.name}</div>
                        </div>
                    </div>
                ` : '<small class="text-muted">-</small>'}
            </td>
            <td onclick="showRevisionDetails(${revision.id})" style="cursor: pointer;">
                ${revision.executor_user ? `
                    <div class="user-info" title="${revision.executor_user.name}">
                        <div class="user-avatar" style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);">
                            ${revision.executor_user.name.charAt(0)}
                        </div>
                        <div style="max-width: 100%; overflow: hidden;">
                            <div class="fw-bold small text-primary text-truncate">${revision.executor_user.name}</div>
                        </div>
                    </div>
                ` : '<small class="text-muted">-</small>'}
            </td>
            <td onclick="showRevisionDetails(${revision.id})" style="cursor: pointer;">
                ${(() => {
                    const reviewers = getAllReviewers(revision);
                    if (!reviewers || reviewers.length === 0) {
                        return '<small class="text-muted">-</small>';
                    }

                    // جلب اسم أول مراجع
                    const firstReviewer = reviewers[0];
                    const firstReviewerName = firstReviewer.user ? firstReviewer.user.name :
                        (window.allUsers?.find(u => u.id == firstReviewer.reviewer_id)?.name || `مراجع ${firstReviewer.order}`);

                    const moreCount = reviewers.length - 1;
                    const allNames = reviewers.map(r => {
                        return r.user ? r.user.name :
                            (window.allUsers?.find(u => u.id == r.reviewer_id)?.name || `مراجع ${r.order}`);
                    }).join('، ');

                    return `
                    <div class="user-info" title="${allNames}">
                        <div class="user-avatar" style="background: linear-gradient(135deg, #198754 0%, #157347 100%);">
                            ${firstReviewerName.charAt(0)}
                        </div>
                        <div style="max-width: 100%; overflow: hidden;">
                            <div class="fw-bold small text-success text-truncate">
                                ${firstReviewerName}${moreCount > 0 ? ` <span class="badge bg-success" style="font-size: 9px;">+${moreCount}</span>` : ''}
                            </div>
                        </div>
                    </div>`;
                })()}
            </td>
            <td onclick="showRevisionDetails(${revision.id})" style="cursor: pointer;">
                <small>${formatDate(revision.revision_date)}</small>
            </td>
            <td onclick="showRevisionDetails(${revision.id})" style="cursor: pointer;">
                ${revision.actual_minutes ? `
                    <small class="text-primary fw-bold">
                        <i class="fas fa-stopwatch me-1"></i>
                        ${formatRevisionTime(revision.actual_minutes * 60)}
                    </small>
                ` : '<small class="text-muted">-</small>'}
            </td>
            <td onclick="showRevisionDetails(${revision.id})" style="cursor: pointer;">
                ${revision.review_actual_minutes ? `
                    <small class="text-success fw-bold">
                        <i class="fas fa-stopwatch me-1"></i>
                        ${formatRevisionTime(revision.review_actual_minutes * 60)}
                    </small>
                ` : '<small class="text-muted">-</small>'}
            </td>
            <td onclick="showRevisionDetails(${revision.id})" style="cursor: pointer;">
                ${revision.status === 'in_progress' ? `
                    <div class="revision-timer" style="font-family: 'Courier New', monospace; font-weight: bold; color: #059669; padding: 2px 6px; background: #dcfce7; border-radius: 4px; font-size: 11px; text-align: center;">
                        <i class="fas fa-clock"></i>
                        <span id="revision-timer-${revision.id}">${formatRevisionTime(revisionTimers[revision.id] ? revisionTimers[revision.id].seconds : initialTimerSeconds)}</span>
                    </div>
                ` : '<small class="text-muted">-</small>'}
            </td>
            <td onclick="showRevisionDetails(${revision.id})" style="cursor: pointer;">
                ${revision.review_status === 'in_progress' ? `
                    <div class="revision-timer" style="font-family: 'Courier New', monospace; font-weight: bold; color: #198754; padding: 2px 6px; background: #d1e7dd; border-radius: 4px; font-size: 11px; text-align: center;">
                        <i class="fas fa-clock"></i>
                        <span id="review-timer-${revision.id}">${formatRevisionTime(reviewTimers[revision.id] ? reviewTimers[revision.id].seconds : initialReviewTimerSeconds)}</span>
                    </div>
                ` : '<small class="text-muted">-</small>'}
            </td>
            <td onclick="showRevisionDetails(${revision.id})" style="cursor: pointer;">
                ${revision.project ? `
                    <small class="text-truncate d-block" style="max-width: 150px;" title="${revision.project.name}">
                        <i class="fas fa-project-diagram me-1"></i>
                        ${revision.project.code || revision.project.name}
                    </small>
                ` : '<small class="text-muted">-</small>'}
            </td>
            <td onclick="event.stopPropagation();">
                ${isAssignedToMe || isReviewer ? `
                    <div class="d-flex gap-1 flex-wrap" style="min-width: 120px;">
                        ${isAssignedToMe && ['new', 'in_progress', 'paused'].includes(revision.status) ? getRevisionActionButtonsCompact(revision) : ''}
                        ${isReviewer && ['new', 'in_progress', 'paused'].includes(revision.review_status) ? getReviewActionButtonsCompact(revision) : ''}
                        ${isAssignedToMe && revision.status === 'completed' ? `
                            <button class="btn btn-sm btn-warning" onclick="event.stopPropagation(); reopenWork(${revision.id});" title="إعادة فتح العمل">
                                <i class="fas fa-undo"></i>
                            </button>
                        ` : ''}
                        ${isReviewer && revision.review_status === 'completed' ? `
                            <button class="btn btn-sm btn-warning" onclick="event.stopPropagation(); reopenReview(${revision.id});" title="إعادة فتح المراجعة">
                                <i class="fas fa-undo"></i>
                            </button>
                        ` : ''}
                    </div>
                ` : '<small class="text-muted">-</small>'}
            </td>
        </tr>
    `;
}

// Render single task revision row (for task revisions without project)
function renderTaskRevisionRow(revision) {
    const statusClass = `status-${revision.status}`;
    const sourceClass = `source-${revision.revision_source}`;
    const currentUserId = typeof AUTH_USER_ID !== 'undefined' ? AUTH_USER_ID : '';

    // الشخص المسند إليه (من task_user أو template_task_user)
    let assignedUser = null;
    if (revision.task_user && revision.task_user.user) {
        assignedUser = revision.task_user.user;
    } else if (revision.template_task_user && revision.template_task_user.user) {
        assignedUser = revision.template_task_user.user;
    }

    // التحقق من أن المستخدم هو المسند له التعديل (منفذ)
    const isAssignedToMe = revision.assigned_to == currentUserId ||
                          revision.executor_user_id == currentUserId ||
                          (revision.task_user && revision.task_user.user_id == currentUserId) ||
                          (revision.template_task_user && revision.template_task_user.user_id == currentUserId);

    // حساب الوقت الأولي للتايمر (التنفيذ)
    let initialTimerSeconds = 0;
    if (revision.status === 'in_progress') {
        initialTimerSeconds = calculateInitialRevisionTime(revision);

        // إضافة البيانات للـ timers object
        if (!revisionTimers[revision.id]) {
            revisionTimers[revision.id] = {
                status: revision.status,
                seconds: initialTimerSeconds,
                revision: revision
            };
        } else {
            revisionTimers[revision.id].status = revision.status;
            revisionTimers[revision.id].revision = revision;
        }
    }

    return `
        <tr data-revision-id="${revision.id}">
            <td onclick="showRevisionDetails(${revision.id})" style="cursor: pointer;">
                <div class="fw-bold text-truncate" style="max-width: 200px;" title="${revision.title}">
                    ${revision.title}
                </div>
                ${revision.project && revision.project.code ? `
                    <small class="text-primary d-block text-truncate" style="max-width: 200px; font-weight: 500;" title="${revision.project.code}">
                        <i class="fas fa-project-diagram me-1"></i>
                        ${revision.project.code}
                    </small>
                ` : ''}
                <small class="text-muted d-block text-truncate" style="max-width: 200px;" title="${revision.description}">
                    ${revision.description}
                </small>
            </td>
            <td onclick="showRevisionDetails(${revision.id})" style="cursor: pointer;">
                <span class="status-badge ${statusClass}">${getStatusText(revision.status)}</span>
            </td>
            <td onclick="showRevisionDetails(${revision.id})" style="cursor: pointer;">
                <span class="source-badge ${sourceClass}">
                    <i class="${getSourceIcon(revision.revision_source)} me-1"></i>
                    ${getSourceText(revision.revision_source)}
                </span>
            </td>
            <td onclick="showRevisionDetails(${revision.id})" style="cursor: pointer;">
                <div class="user-info" title="${revision.creator ? revision.creator.name : 'غير محدد'}">
                    <div class="user-avatar">
                        ${revision.creator ? revision.creator.name.charAt(0) : 'غ'}
                    </div>
                    <div style="max-width: 100%; overflow: hidden;">
                        <div class="fw-bold small text-truncate">${revision.creator ? revision.creator.name : 'غير محدد'}</div>
                    </div>
                </div>
            </td>
            <td onclick="showRevisionDetails(${revision.id})" style="cursor: pointer;">
                ${assignedUser ? `
                    <div class="user-info" title="${assignedUser.name}">
                        <div class="user-avatar" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);">
                            ${assignedUser.name.charAt(0)}
                        </div>
                        <div style="max-width: 100%; overflow: hidden;">
                            <div class="fw-bold small text-info text-truncate">${assignedUser.name}</div>
                        </div>
                    </div>
                ` : '<small class="text-muted">-</small>'}
            </td>
            <td onclick="showRevisionDetails(${revision.id})" style="cursor: pointer;">
                ${revision.responsible_user ? `
                    <div class="user-info" title="${revision.responsible_user.name}">
                        <div class="user-avatar" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);">
                            ${revision.responsible_user.name.charAt(0)}
                        </div>
                        <div style="max-width: 100%; overflow: hidden;">
                            <div class="fw-bold small text-danger text-truncate">${revision.responsible_user.name}</div>
                        </div>
                    </div>
                ` : '<small class="text-muted">-</small>'}
            </td>
            <td onclick="showRevisionDetails(${revision.id})" style="cursor: pointer;">
                ${revision.executor_user ? `
                    <div class="user-info" title="${revision.executor_user.name}">
                        <div class="user-avatar" style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);">
                            ${revision.executor_user.name.charAt(0)}
                        </div>
                        <div style="max-width: 100%; overflow: hidden;">
                            <div class="fw-bold small text-primary text-truncate">${revision.executor_user.name}</div>
                        </div>
                    </div>
                ` : '<small class="text-muted">-</small>'}
            </td>
            <td onclick="showRevisionDetails(${revision.id})" style="cursor: pointer;">
                <small>${formatDate(revision.revision_date)}</small>
            </td>
            <td onclick="showRevisionDetails(${revision.id})" style="cursor: pointer;">
                ${revision.actual_minutes ? `
                    <small class="text-primary fw-bold">
                        <i class="fas fa-stopwatch me-1"></i>
                        ${formatRevisionTime(revision.actual_minutes * 60)}
                    </small>
                ` : '<small class="text-muted">-</small>'}
            </td>
            <td onclick="showRevisionDetails(${revision.id})" style="cursor: pointer;">
                ${revision.status === 'in_progress' ? `
                    <div class="revision-timer" style="font-family: 'Courier New', monospace; font-weight: bold; color: #059669; padding: 2px 6px; background: #dcfce7; border-radius: 4px; font-size: 11px; text-align: center;">
                        <i class="fas fa-clock"></i>
                        <span id="revision-timer-${revision.id}">${formatRevisionTime(revisionTimers[revision.id] ? revisionTimers[revision.id].seconds : initialTimerSeconds)}</span>
                    </div>
                ` : '<small class="text-muted">-</small>'}
            </td>
            <td onclick="event.stopPropagation();">
                ${isAssignedToMe && ['new', 'in_progress', 'paused'].includes(revision.status) ? `
                    <div class="d-flex gap-1 flex-wrap" style="min-width: 120px;">
                        ${getRevisionActionButtonsCompact(revision)}
                        ${revision.status === 'completed' ? `
                            <button class="btn btn-sm btn-warning" onclick="event.stopPropagation(); reopenWork(${revision.id});" title="إعادة فتح العمل">
                                <i class="fas fa-undo"></i>
                            </button>
                        ` : ''}
                    </div>
                ` : '<small class="text-muted">-</small>'}
            </td>
        </tr>
    `;
}

// Render pagination
function renderPagination(data, container, type) {
    if (data.last_page <= 1) {
        $(container).empty();
        return;
    }

    let html = '<nav><ul class="pagination">';

    // Previous button
    if (data.current_page > 1) {
        html += `<li class="page-item">
            <a class="page-link" href="#" onclick="loadPage(${data.current_page - 1}, '${type}')">السابق</a>
        </li>`;
    }

    // Page numbers
    for (let i = 1; i <= data.last_page; i++) {
        if (i === data.current_page) {
            html += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
        } else {
            html += `<li class="page-item">
                <a class="page-link" href="#" onclick="loadPage(${i}, '${type}')">${i}</a>
            </li>`;
        }
    }

    // Next button
    if (data.current_page < data.last_page) {
        html += `<li class="page-item">
            <a class="page-link" href="#" onclick="loadPage(${data.current_page + 1}, '${type}')">التالي</a>
        </li>`;
    }

    html += '</ul></nav>';
    $(container).html(html);
}

// Apply filters
function applyFilters(type) {
    const filters = {};

    if (type === 'all') {
        filters.search = $('#allSearchInput').val();
        filters.project_code = $('#allProjectCodeFilter').val();
        filters.month = $('#allMonthFilter').val();
        filters.revision_type = $('#allRevisionTypeFilter').val();
        filters.revision_source = $('#allRevisionSourceFilter').val();
        filters.status = $('#allStatusFilter').val();
        filters.deadline_from = $('#allDeadlineFrom').val();
        filters.deadline_to = $('#allDeadlineTo').val();
        loadAllRevisions(1, filters);
    } else if (type === 'my') {
        filters.search = $('#mySearchInput').val();
        filters.project_code = $('#myProjectCodeFilter').val();
        filters.month = $('#myMonthFilter').val();
        filters.revision_type = $('#myRevisionTypeFilter').val();
        filters.revision_source = $('#myRevisionSourceFilter').val();
        filters.status = $('#myStatusFilter').val();
        filters.deadline_from = $('#myDeadlineFrom').val();
        filters.deadline_to = $('#myDeadlineTo').val();
        loadMyRevisions(1, filters);
    } else if (type === 'myCreated') {
        filters.search = $('#myCreatedSearchInput').val();
        filters.project_code = $('#myCreatedProjectCodeFilter').val();
        filters.month = $('#myCreatedMonthFilter').val();
        filters.revision_type = $('#myCreatedRevisionTypeFilter').val();
        filters.revision_source = $('#myCreatedRevisionSourceFilter').val();
        filters.status = $('#myCreatedStatusFilter').val();
        filters.deadline_from = $('#myCreatedDeadlineFrom').val();
        filters.deadline_to = $('#myCreatedDeadlineTo').val();
        loadMyCreatedRevisions(1, filters);
    }

    // Update Kanban if in Kanban view
    if (typeof updateKanbanOnFilter === 'function') {
        setTimeout(() => {
            updateKanbanOnFilter();
        }, 100);
    }
}

// Clear filters
function clearFilters(type) {
    if (type === 'all') {
        $('#allSearchInput').val('');
        $('#allProjectCodeFilter').val('');
        $('#allMonthFilter').val('');
        $('#allRevisionTypeFilter').val('');
        $('#allRevisionSourceFilter').val('');
        $('#allStatusFilter').val('');
        $('#allDeadlineFrom').val('');
        $('#allDeadlineTo').val('');
        loadAllRevisions();
    } else if (type === 'my') {
        $('#mySearchInput').val('');
        $('#myProjectCodeFilter').val('');
        $('#myMonthFilter').val('');
        $('#myRevisionTypeFilter').val('');
        $('#myRevisionSourceFilter').val('');
        $('#myStatusFilter').val('');
        $('#myDeadlineFrom').val('');
        $('#myDeadlineTo').val('');
        loadMyRevisions();
    } else if (type === 'myCreated') {
        $('#myCreatedSearchInput').val('');
        $('#myCreatedProjectCodeFilter').val('');
        $('#myCreatedMonthFilter').val('');
        $('#myCreatedRevisionTypeFilter').val('');
        $('#myCreatedRevisionSourceFilter').val('');
        $('#myCreatedStatusFilter').val('');
        $('#myCreatedDeadlineFrom').val('');
        $('#myCreatedDeadlineTo').val('');
        loadMyCreatedRevisions();
    }
}

// Load specific page
function loadPage(page, type) {
    const filters = {};

    if (type === 'all') {
        filters.search = $('#allSearchInput').val();
        filters.project_code = $('#allProjectCodeFilter').val();
        filters.month = $('#allMonthFilter').val();
        filters.revision_type = $('#allRevisionTypeFilter').val();
        filters.revision_source = $('#allRevisionSourceFilter').val();
        filters.status = $('#allStatusFilter').val();
        filters.deadline_from = $('#allDeadlineFrom').val();
        filters.deadline_to = $('#allDeadlineTo').val();
        loadAllRevisions(page, filters);
    } else if (type === 'my') {
        filters.search = $('#mySearchInput').val();
        filters.project_code = $('#myProjectCodeFilter').val();
        filters.month = $('#myMonthFilter').val();
        filters.revision_type = $('#myRevisionTypeFilter').val();
        filters.revision_source = $('#myRevisionSourceFilter').val();
        filters.status = $('#myStatusFilter').val();
        filters.deadline_from = $('#myDeadlineFrom').val();
        filters.deadline_to = $('#myDeadlineTo').val();
        loadMyRevisions(page, filters);
    } else if (type === 'myCreated') {
        filters.search = $('#myCreatedSearchInput').val();
        filters.project_code = $('#myCreatedProjectCodeFilter').val();
        filters.month = $('#myCreatedMonthFilter').val();
        filters.revision_type = $('#myCreatedRevisionTypeFilter').val();
        filters.revision_source = $('#myCreatedRevisionSourceFilter').val();
        filters.status = $('#myCreatedStatusFilter').val();
        filters.deadline_from = $('#myCreatedDeadlineFrom').val();
        filters.deadline_to = $('#myCreatedDeadlineTo').val();
        loadMyCreatedRevisions(page, filters);
    }
}

// Refresh data
function refreshData(showToast = false) {
    loadStats();

    const activeTab = $('#revisionTabs .nav-link.active').attr('data-bs-target');
    if (activeTab === '#my-revisions') {
        loadMyRevisions();
    } else if (activeTab === '#my-created-revisions') {
        loadMyCreatedRevisions();
    } else {
        loadAllRevisions();
    }

    // تحديث التايمرات بعد تحديث البيانات
    setTimeout(() => {
        refreshRevisionTimers();
    }, 1000);

    // Update Kanban if in Kanban view
    if (typeof updateKanbanOnTabChange === 'function') {
        setTimeout(() => {
            updateKanbanOnTabChange();
        }, 500);
    }

    if (showToast) {
        toastr.success('تم تحديث البيانات');
    }
}

// Utility functions
function showLoading() {
    $('#loadingOverlay').removeClass('d-none');
}

function hideLoading() {
    $('#loadingOverlay').addClass('d-none');
}

function getStatusText(status) {
    const statuses = {
        'new': 'جديد',
        'in_progress': 'جاري العمل',
        'paused': 'متوقف',
        'completed': 'مكتمل',
        // Legacy/Approval statuses
        'pending': 'في الانتظار',
        'approved': 'موافق عليه',
        'rejected': 'مرفوض'
    };
    return statuses[status] || 'غير محدد';
}

function getSourceText(source) {
    const sources = {
        'internal': 'داخلي',
        'external': 'خارجي',
        'canceled': 'ملغي'
    };
    return sources[source] || 'غير محدد';
}

function getSourceIcon(source) {
    const icons = {
        'internal': 'fas fa-users',
        'external': 'fas fa-external-link-alt',
        'canceled': 'fas fa-times'
    };
    return icons[source] || 'fas fa-question';
}

function getAttachmentIcon(type) {
    if (!type) return 'fas fa-file';

    const lowerType = type.toLowerCase();
    if (lowerType.includes('image')) return 'fas fa-image';
    if (lowerType.includes('pdf')) return 'fas fa-file-pdf';
    if (lowerType.includes('word') || lowerType.includes('document')) return 'fas fa-file-word';
    if (lowerType.includes('excel') || lowerType.includes('spreadsheet')) return 'fas fa-file-excel';
    if (lowerType.includes('zip') || lowerType.includes('rar')) return 'fas fa-file-archive';
    if (lowerType.includes('canceled')) return 'fas fa-times';
    return 'fas fa-file';
}

function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('ar-EG', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function formatFileSize(bytes) {
    if (!bytes) return '';
    const units = ['B', 'KB', 'MB', 'GB'];
    let size = bytes;
    let unitIndex = 0;

    while (size > 1024 && unitIndex < units.length - 1) {
        size /= 1024;
        unitIndex++;
    }

    return Math.round(size * 100) / 100 + ' ' + units[unitIndex];
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Sidebar functions
function showRevisionDetails(revisionId) {
    // Remove active class from all rows
    $('.revisions-table tbody tr').removeClass('active');

    // Add active class to clicked row
    $(`.revisions-table tbody tr[data-revision-id="${revisionId}"]`).addClass('active');

    // Show loading in sidebar
    $('#sidebarContent').html(`
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">جاري التحميل...</span>
            </div>
            <p class="mt-2 text-muted">جاري تحميل التفاصيل...</p>
        </div>
    `);

    // Show sidebar
    $('#revisionSidebar').addClass('active');
    $('#sidebarOverlay').addClass('active');

    // Fetch revision details
    $.get(`/revision-page/revision/${revisionId}`)
        .done(function(response) {
            if (response.success) {
                console.log('📅 Revision deadline:', {
                    revision_deadline: response.revision.revision_deadline,
                    revisionDeadline: response.revision.revisionDeadline,
                    hasDeadline: !!(response.revision.revision_deadline || response.revision.revisionDeadline)
                });
                renderSidebarContent(response.revision);
            } else {
                $('#sidebarContent').html(`
                    <div class="text-center py-5">
                        <i class="fas fa-exclamation-triangle text-warning fa-3x mb-3"></i>
                        <h5>خطأ في التحميل</h5>
                        <p class="text-muted">${response.message}</p>
                        <button class="btn btn-primary" onclick="showRevisionDetails(${revisionId})">
                            <i class="fas fa-sync-alt me-1"></i>
                            إعادة المحاولة
                        </button>
                    </div>
                `);
            }
        })
        .fail(function() {
            $('#sidebarContent').html(`
                <div class="text-center py-5">
                    <i class="fas fa-exclamation-triangle text-danger fa-3x mb-3"></i>
                    <h5>خطأ في الاتصال</h5>
                    <p class="text-muted">تعذر تحميل تفاصيل التعديل</p>
                    <button class="btn btn-primary" onclick="showRevisionDetails(${revisionId})">
                        <i class="fas fa-sync-alt me-1"></i>
                        إعادة المحاولة
                    </button>
                </div>
            `);
        });
}

function closeSidebar() {
    $('#revisionSidebar').removeClass('active');
    $('#sidebarOverlay').removeClass('active');
    $('.revisions-table tbody tr').removeClass('active');
}

function renderSidebarContent(revision) {
    const currentUserId = typeof AUTH_USER_ID !== 'undefined' ? AUTH_USER_ID : '';
    const isCreator = revision.created_by == currentUserId;

    const html = `
        <div class="detail-section">
            <h6><i class="fas fa-info-circle me-2"></i>معلومات أساسية</h6>
            <p><strong>العنوان:</strong> ${revision.title}</p>
            <p><strong>النوع:</strong> ${getRevisionTypeText(revision.revision_type)}</p>
            <p><strong>المصدر:</strong> ${getSourceText(revision.revision_source)}</p>
            <p><strong>الحالة:</strong>
                <span class="status-badge status-${revision.status}">${getStatusText(revision.status)}</span>
            </p>
            <p><strong>تاريخ الإنشاء:</strong> ${formatDate(revision.revision_date)}</p>
            ${revision.actual_minutes ? `
                <p><strong>الوقت المستغرق:</strong> ${formatRevisionTimeInMinutes(revision.actual_minutes)}</p>
            ` : ''}
            ${(() => {
                const revisionDeadline = revision.revision_deadline || revision.revisionDeadline;
                if (revisionDeadline) {
                    const deadlineDate = new Date(revisionDeadline);
                    const now = new Date();
                    const diffMs = deadlineDate - now;
                    const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
                    const diffDays = Math.floor(diffHours / 24);

                    let deadlineColor = '#10b981';
                    let deadlineIcon = 'fa-calendar-check';
                    let deadlineText = '';
                    let deadlineBg = '#f0fdf4';
                    let deadlineBorder = '#10b981';

                    if (diffMs < 0) {
                        deadlineColor = '#ef4444';
                        deadlineIcon = 'fa-exclamation-triangle';
                        deadlineText = `⚠️ التعديل متأخر بـ ${Math.abs(diffDays)} يوم`;
                        deadlineBg = '#fef2f2';
                        deadlineBorder = '#ef4444';
                    } else if (diffHours < 24) {
                        deadlineColor = '#f59e0b';
                        deadlineIcon = 'fa-hourglass-half';
                        deadlineText = `⏰ متبقي ${diffHours} ساعة فقط`;
                        deadlineBg = '#fffbeb';
                        deadlineBorder = '#f59e0b';
                    } else if (diffDays <= 3) {
                        deadlineColor = '#f59e0b';
                        deadlineIcon = 'fa-calendar-times';
                        deadlineText = `⏰ متبقي ${diffDays} يوم`;
                        deadlineBg = '#fffbeb';
                        deadlineBorder = '#f59e0b';
                    } else {
                        deadlineText = `متبقي ${diffDays} يوم`;
                    }

                    let deadlineFormatted;
                    try {
                        deadlineFormatted = deadlineDate.toLocaleDateString('ar-EG', {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                    } catch (e) {
                        const year = deadlineDate.getFullYear();
                        const month = deadlineDate.getMonth() + 1;
                        const day = deadlineDate.getDate();
                        const hours = deadlineDate.getHours().toString().padStart(2, '0');
                        const minutes = deadlineDate.getMinutes().toString().padStart(2, '0');
                        deadlineFormatted = `${day}/${month}/${year} ${hours}:${minutes}`;
                    }

                    return `
                    <div style="margin-top: 1rem; padding: 1rem; background: ${deadlineBg}; border: 3px solid ${deadlineBorder}; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        <div style="display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.8rem;">
                            <i class="fas ${deadlineIcon}" style="color: ${deadlineColor}; font-size: 1.4rem;"></i>
                            <strong style="color: ${deadlineColor}; font-size: 1.1rem; font-weight: 700;">⏰ ديدلاين التعديل:</strong>
                        </div>
                        <div style="padding: 0.8rem; background: white; border-radius: 8px; margin-bottom: 0.6rem; border: 2px solid ${deadlineBorder}20;">
                            <div style="display: flex; align-items: center; gap: 0.4rem;">
                                <i class="fas fa-calendar-alt" style="color: ${deadlineColor}; font-size: 1rem;"></i>
                                <strong style="color: #1e293b; font-size: 1.05rem; font-weight: 600;">${deadlineFormatted}</strong>
                            </div>
                        </div>
                        ${deadlineText ? `
                        <div style="display: flex; align-items: center; justify-content: center; gap: 0.4rem; padding: 0.6rem; background: ${deadlineBg}; border: 2px solid ${deadlineBorder}; border-radius: 6px;">
                            <i class="fas ${deadlineIcon}" style="color: ${deadlineColor}; font-size: 1rem;"></i>
                            <span style="color: ${deadlineColor}; font-size: 0.95rem; font-weight: 700;">
                                ${deadlineText}
                            </span>
                        </div>
                        ` : `
                        <div style="display: flex; align-items: center; justify-content: center; gap: 0.4rem; padding: 0.6rem; background: ${deadlineBg}; border: 2px solid ${deadlineBorder}; border-radius: 6px;">
                            <i class="fas fa-check-circle" style="color: ${deadlineColor}; font-size: 1rem;"></i>
                            <span style="color: ${deadlineColor}; font-size: 0.95rem; font-weight: 600;">
                                في الموعد
                            </span>
                        </div>
                        `}
                    </div>
                    `;
                }
                return `
                <div style="margin-top: 1rem; padding: 1rem; background: #f8f9fa; border: 2px dashed #dee2e6; border-radius: 10px;">
                    <div style="display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.5rem;">
                        <i class="fas fa-info-circle" style="color: #6c757d; font-size: 1.2rem;"></i>
                        <strong style="color: #6c757d; font-size: 1rem;">ديدلاين التعديل:</strong>
                    </div>
                    <p style="color: #6c757d; margin: 0; font-size: 0.9rem;">
                        <i class="fas fa-exclamation-circle me-1"></i>
                        لم يتم تحديد ديدلاين للتعديل. يمكنك إضافته عند التعديل.
                    </p>
                </div>
                `;
            })()}
        </div>

        ${isCreator ? `
            <div class="detail-section bg-light">
                <h6><i class="fas fa-edit me-2"></i>إدارة التعديل</h6>
                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-primary" onclick="openEditRevisionForm(${revision.id})">
                        <i class="fas fa-edit me-1"></i>تعديل البيانات
                    </button>
                </div>
            </div>
        ` : ''}

        ${isRevisionAssignedToCurrentUser(revision) && ['new', 'in_progress', 'paused'].includes(revision.status) ? `
            <div class="detail-section bg-light">
                <h6><i class="fas fa-tasks me-2"></i>إدارة العمل (المنفذ)</h6>
                <div class="d-flex gap-2 flex-wrap">
                    ${getRevisionActionButtons(revision)}
                </div>
            </div>
        ` : ''}

        ${isRevisionAssignedToCurrentUser(revision) && revision.status === 'completed' ? `
            <div class="detail-section bg-warning bg-opacity-10">
                <h6><i class="fas fa-undo me-2 text-warning"></i>إعادة فتح التنفيذ</h6>
                <p class="small text-muted mb-2">تم إكمال العمل بالغلط؟ يمكنك إعادة فتحه للتعديل</p>
                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-warning" onclick="reopenWork(${revision.id})">
                        <i class="fas fa-undo me-1"></i>إعادة فتح العمل
                    </button>
                </div>
            </div>
        ` : ''}

        ${isCurrentUserReviewer(revision) && ['new', 'in_progress', 'paused'].includes(revision.review_status) ? `
            <div class="detail-section bg-success bg-opacity-10">
                <h6><i class="fas fa-check-circle me-2 text-success"></i>إدارة المراجعة</h6>
                <div class="d-flex gap-2 flex-wrap">
                    ${getReviewActionButtons(revision)}
                </div>
                ${revision.review_actual_minutes ? `
                    <p class="mt-2 mb-0"><strong>وقت المراجعة:</strong> ${formatRevisionTimeInMinutes(revision.review_actual_minutes)}</p>
                ` : ''}
            </div>
        ` : ''}

        ${isCurrentUserReviewer(revision) && revision.review_status === 'completed' ? `
            <div class="detail-section bg-warning bg-opacity-10">
                <h6><i class="fas fa-undo me-2 text-warning"></i>إعادة فتح المراجعة</h6>
                <p class="small text-muted mb-2">تمت المراجعة بالغلط؟ يمكنك إعادة فتحها للتعديل</p>
                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-warning" onclick="reopenReview(${revision.id})">
                        <i class="fas fa-undo me-1"></i>إعادة فتح المراجعة
                    </button>
                </div>
            </div>
        ` : ''}

        <div class="detail-section">
            <h6><i class="fas fa-align-left me-2"></i>الوصف</h6>
            <p>${revision.description}</p>
        </div>

        ${revision.notes ? `
            <div class="detail-section">
                <h6><i class="fas fa-sticky-note me-2"></i>ملاحظات</h6>
                <p>${revision.notes}</p>
            </div>
        ` : ''}

        <div class="detail-section">
            <h6><i class="fas fa-users me-2"></i>الأشخاص والمسؤوليات</h6>
            <p><strong>من طلب التعديل:</strong> ${revision.creator ? revision.creator.name : 'غير محدد'}</p>

            ${revision.responsible_user ? `
                <div style="margin: 1rem 0; padding: 0.8rem; background: #fef2f2; border: 3px solid #dc2626; border-radius: 8px;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.3rem;">
                        <i class="fas fa-exclamation-triangle" style="color: #dc2626; font-size: 1.2rem;"></i>
                        <strong style="color: #991b1b;">⚠️ المسؤول (اللي غلط):</strong>
                    </div>
                    <div style="padding: 0.4rem 0.6rem; background: white; border-radius: 6px;">
                        <strong style="color: #1e293b; font-size: 1rem;">${revision.responsible_user.name}</strong>
                    </div>
                </div>
            ` : ''}

            ${revision.executor_user ? `
                <div style="margin: 1rem 0; padding: 0.8rem; background: ${getExecutorBgColor(revision.status)}; border: 3px solid ${getExecutorBorderColor(revision.status)}; border-radius: 8px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-hammer" style="color: #1e40af; font-size: 1.2rem;"></i>
                            <strong style="color: #1e40af;">🔨 المنفذ (اللي هيصلح):</strong>
                        </div>
                        ${(isCreator || revision.executor_user_id == currentUserId) && revision.revision_type === 'project' ? `
                            <button class="btn btn-sm btn-outline-primary" onclick="reassignExecutor(${revision.id}, ${revision.project_id})" title="إعادة تعيين المنفذ">
                                <i class="fas fa-exchange-alt"></i>
                            </button>
                        ` : ''}
                    </div>
                    <div style="padding: 0.4rem 0.6rem; background: white; border-radius: 6px; margin-bottom: 0.5rem;">
                        <strong style="color: #1e293b; font-size: 1rem;">${revision.executor_user.name}</strong>
                    </div>
                    ${(() => {
                        const executorDeadline = revision.executor_deadline ||
                            (revision.deadlines && revision.deadlines.find(d => d.deadline_type === 'executor'));

                        if (executorDeadline) {
                            const deadlineDate = new Date(executorDeadline.deadline_date || executorDeadline);
                            const now = new Date();
                            const diffMs = deadlineDate - now;
                            const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
                            const diffDays = Math.floor(diffHours / 24);

                            let deadlineColor = '#10b981';
                            let deadlineIcon = 'fa-clock';
                            let deadlineText = '';
                            let deadlineBg = '#f0fdf4';
                            let deadlineBorder = '#10b981';

                            if (diffMs < 0) {
                                deadlineColor = '#ef4444';
                                deadlineIcon = 'fa-exclamation-triangle';
                                deadlineText = `فات الموعد بـ ${Math.abs(diffDays)} يوم`;
                                deadlineBg = '#fef2f2';
                                deadlineBorder = '#ef4444';
                            } else if (diffHours < 24) {
                                deadlineColor = '#f59e0b';
                                deadlineIcon = 'fa-hourglass-half';
                                deadlineText = `متبقي ${diffHours} ساعة`;
                                deadlineBg = '#fffbeb';
                                deadlineBorder = '#f59e0b';
                            } else if (diffDays <= 3) {
                                deadlineColor = '#f59e0b';
                                deadlineIcon = 'fa-clock';
                                deadlineText = `متبقي ${diffDays} يوم`;
                                deadlineBg = '#fffbeb';
                                deadlineBorder = '#f59e0b';
                            } else {
                                deadlineText = `متبقي ${diffDays} يوم`;
                            }

                            const deadlineFormatted = deadlineDate.toLocaleDateString('ar-EG', {
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit'
                            });

                            return `
                            <div style="margin-bottom: 0.5rem; padding: 0.5rem; background: ${deadlineBg}; border: 2px solid ${deadlineBorder}; border-radius: 6px;">
                                <div style="display: flex; align-items: center; gap: 0.4rem; margin-bottom: 0.3rem;">
                                    <i class="fas ${deadlineIcon}" style="color: ${deadlineColor}; font-size: 0.9rem;"></i>
                                    <span style="color: #1e293b; font-size: 0.85rem; font-weight: 600;">⏰ الديدلاين:</span>
                                    <span style="color: ${deadlineColor}; font-size: 0.85rem; font-weight: 700;">${deadlineFormatted}</span>
                                </div>
                                ${deadlineText ? `
                                <div style="display: flex; align-items: center; gap: 0.3rem;">
                                    <span style="color: ${deadlineColor}; font-size: 0.75rem; font-weight: 600;">
                                        <i class="fas fa-info-circle me-1"></i>${deadlineText}
                                    </span>
                                </div>
                                ` : ''}
                            </div>
                            `;
                        }
                        return '';
                    })()}
                    <div style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.5rem; background: ${getExecutorStatusBg(revision.status)}; border: 2px solid ${getExecutorBorderColor(revision.status)}; border-radius: 6px;">
                        <i class="fas ${getExecutorStatusIcon(revision.status)}" style="color: ${getExecutorBorderColor(revision.status)}; font-size: 1.1rem;"></i>
                        <strong style="color: ${getExecutorBorderColor(revision.status)}; font-size: 0.95rem;">${getExecutorStatusText(revision.status)}</strong>
                    </div>
                </div>
            ` : ''}

            ${(() => {
                const reviewers = getAllReviewers(revision);
                if (!reviewers || reviewers.length === 0) return '';

                const reviewersHtml = reviewers.map((reviewer, index) => {
                        const reviewerName = reviewer.user ? reviewer.user.name :
                        (window.allUsers?.find(u => u.id == reviewer.reviewer_id)?.name || ('مراجع ' + reviewer.order));
                    const isCurrent = reviewer.status === 'in_progress' || reviewer.status === 'pending';
                    const isCompleted = reviewer.status === 'completed';

                    let statusBg = '#f3f4f6';
                    let statusBorder = '#9ca3af';
                    let statusIcon = 'fa-clock';
                    let statusText = 'في الانتظار';
                    let statusColor = '#6b7280';

                    if (reviewer.status === 'in_progress') {
                        statusBg = '#fef3c7';
                        statusBorder = '#f59e0b';
                        statusIcon = 'fa-spinner fa-spin';
                        statusText = 'قيد المراجعة';
                        statusColor = '#d97706';
                    } else if (reviewer.status === 'completed') {
                        statusBg = '#d1fae5';
                        statusBorder = '#10b981';
                        statusIcon = 'fa-check-circle';
                        statusText = 'تمت المراجعة';
                        statusColor = '#059669';
                    }

                    const borderColor = isCurrent ? '#22c55e' : (isCompleted ? '#10b981' : '#e5e7eb');
                    const boxShadow = isCurrent ? 'box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);' : '';
                    const currentBadge = isCurrent ? '<span class="badge bg-success ms-2" style="font-size: 10px;">الدور الحالي</span>' : '';

                    return `
                    <div style="margin-bottom: 0.6rem; padding: 0.6rem; background: white; border: 2px solid ${borderColor}; border-radius: 6px; ${boxShadow} position: relative;">
                        <div style="position: absolute; top: 0.4rem; left: 0.4rem; background: #1e293b; color: white; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: bold;">
                            ${index + 1}
                        </div>
                        <div style="padding-right: 2rem;">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.4rem;">
                                <div>
                                    <strong style="color: #1e293b; font-size: 1rem;">${reviewerName}</strong>
                                    ${currentBadge}
                                </div>
                                ${(isCreator || reviewer.reviewer_id == currentUserId) && revision.revision_type === 'project' ? `
                                    <button class="btn btn-sm btn-outline-success" onclick="reassignReviewer(${revision.id}, ${revision.project_id}, ${reviewer.order})" title="إعادة تعيين المراجع رقم ${reviewer.order}" style="font-size: 11px; padding: 2px 6px;">
                                        <i class="fas fa-exchange-alt"></i>
                                    </button>
                                ` : ''}
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.4rem; padding: 0.4rem; background: ${statusBg}; border: 1px solid ${statusBorder}; border-radius: 4px; margin-bottom: 0.4rem;">
                                <i class="fas ${statusIcon}" style="color: ${statusColor}; font-size: 0.9rem;"></i>
                                <span style="color: ${statusColor}; font-size: 0.85rem; font-weight: 600;">${statusText}</span>
                            </div>
                            ${(() => {
                                const reviewerDeadline = revision.deadlines && revision.deadlines.find(d =>
                                    d.deadline_type === 'reviewer' && d.reviewer_order === reviewer.order
                                );

                                if (reviewerDeadline) {
                                    const deadlineDate = new Date(reviewerDeadline.deadline_date);
                                    const now = new Date();
                                    const diffMs = deadlineDate - now;
                                    const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
                                    const diffDays = Math.floor(diffHours / 24);

                                    let deadlineColor = '#10b981';
                                    let deadlineIcon = 'fa-clock';
                                    let deadlineText = '';
                                    let deadlineBg = '#f0fdf4';
                                    let deadlineBorder = '#10b981';

                                    if (diffMs < 0) {
                                        deadlineColor = '#ef4444';
                                        deadlineIcon = 'fa-exclamation-triangle';
                                        deadlineText = `فات الموعد بـ ${Math.abs(diffDays)} يوم`;
                                        deadlineBg = '#fef2f2';
                                        deadlineBorder = '#ef4444';
                                    } else if (diffHours < 24) {
                                        deadlineColor = '#f59e0b';
                                        deadlineIcon = 'fa-hourglass-half';
                                        deadlineText = `متبقي ${diffHours} ساعة`;
                                        deadlineBg = '#fffbeb';
                                        deadlineBorder = '#f59e0b';
                                    } else if (diffDays <= 3) {
                                        deadlineColor = '#f59e0b';
                                        deadlineIcon = 'fa-clock';
                                        deadlineText = `متبقي ${diffDays} يوم`;
                                        deadlineBg = '#fffbeb';
                                        deadlineBorder = '#f59e0b';
                                    } else {
                                        deadlineText = `متبقي ${diffDays} يوم`;
                                    }

                                    const deadlineFormatted = deadlineDate.toLocaleDateString('ar-EG', {
                                        year: 'numeric',
                                        month: 'long',
                                        day: 'numeric',
                                        hour: '2-digit',
                                        minute: '2-digit'
                                    });

                                    return `
                                    <div style="padding: 0.4rem; background: ${deadlineBg}; border: 1px solid ${deadlineBorder}; border-radius: 4px;">
                                        <div style="display: flex; align-items: center; gap: 0.3rem; margin-bottom: 0.2rem;">
                                            <i class="fas ${deadlineIcon}" style="color: ${deadlineColor}; font-size: 0.75rem;"></i>
                                            <span style="color: #1e293b; font-size: 0.75rem; font-weight: 600;">⏰ الديدلاين:</span>
                                            <span style="color: ${deadlineColor}; font-size: 0.75rem; font-weight: 700;">${deadlineFormatted}</span>
                                        </div>
                                        ${deadlineText ? `
                                        <div style="display: flex; align-items: center; gap: 0.2rem;">
                                            <span style="color: ${deadlineColor}; font-size: 0.7rem; font-weight: 600;">
                                                <i class="fas fa-info-circle me-1"></i>${deadlineText}
                                            </span>
                                        </div>
                                        ` : ''}
                                    </div>
                                    `;
                                }
                                return '';
                            })()}
                        </div>
                    </div>`;
                }).join('');

                return `
                <div style="margin: 1rem 0; padding: 0.8rem; background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 3px solid #22c55e; border-radius: 8px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.8rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-users-cog" style="color: #15803d; font-size: 1.2rem;"></i>
                            <strong style="color: #15803d;">✅ المراجعين المتسلسلين (${reviewers.length}):</strong>
                        </div>
                    </div>
                    ${reviewersHtml}
                </div>`;
            })()}

            ${revision.reviewer ? `
                <p class="text-info">
                    <strong>✔️ راجع فعلياً:</strong>
                    ${revision.reviewer.name}
                    ${revision.reviewed_at ? `<small class="text-muted">(${formatDate(revision.reviewed_at)})</small>` : ''}
                </p>
            ` : ''}

            ${!revision.responsible_user && !revision.executor_user ? `
                <p><strong>المسند إليه:</strong> ${getAssignedUserName(revision)}</p>
            ` : ''}
        </div>

        ${revision.responsibility_notes ? `
            <div class="detail-section">
                <h6><i class="fas fa-exclamation-triangle me-2 text-warning"></i>ملاحظات المسؤولية</h6>
                <div class="alert alert-warning mb-0">
                    <small>${revision.responsibility_notes}</small>
                </div>
            </div>
        ` : ''}

        ${revision.project ? `
            <div class="detail-section">
                <h6><i class="fas fa-project-diagram me-2"></i>المشروع</h6>
                <p><strong>${revision.project.code || revision.project.name}</strong></p>
                ${revision.project.code ? `<small class="text-muted">${revision.project.name}</small>` : ''}
            </div>
        ` : ''}

        ${revision.attachment_path || revision.attachment_link ? `
            <div class="detail-section">
                <h6><i class="fas fa-paperclip me-2"></i>المرفق</h6>
                ${revision.attachment_type === 'link' && revision.attachment_link ? `
                    <div class="d-flex align-items-center gap-3">
                        <div>
                            <i class="fas fa-link fa-2x text-info"></i>
                        </div>
                        <div class="flex-grow-1">
                            <p class="mb-1"><strong>رابط خارجي</strong></p>
                            <small class="text-muted text-truncate d-block" style="max-width: 300px;" title="${revision.attachment_link}">${revision.attachment_link}</small>
                        </div>
                        <div>
                            <a href="${revision.attachment_link}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-info btn-sm">
                                <i class="fas fa-external-link-alt me-1"></i>
                                فتح الرابط
                            </a>
                        </div>
                    </div>
                ` : revision.attachment_path ? `
                    <div class="d-flex align-items-center gap-3">
                        <div>
                            <i class="${getAttachmentIcon(revision.attachment_type)} fa-2x text-primary"></i>
                        </div>
                        <div class="flex-grow-1">
                            <p class="mb-1"><strong>${revision.attachment_name}</strong></p>
                            <small class="text-muted">${formatFileSize(revision.attachment_size)}</small>
                        </div>
                        <div class="btn-group" role="group">
                            <a href="/task-revisions/${revision.id}/view" target="_blank" class="btn btn-outline-info btn-sm" title="عرض">
                                <i class="fas fa-eye me-1"></i>
                                عرض
                            </a>
                            <a href="/task-revisions/${revision.id}/download" class="btn btn-outline-primary btn-sm" title="تنزيل">
                                <i class="fas fa-download me-1"></i>
                                تحميل
                            </a>
                        </div>
                    </div>
                ` : ''}
            </div>
        ` : ''}

        ${revision.review_notes ? `
            <div class="detail-section">
                <h6><i class="fas fa-comment-dots me-2"></i>ملاحظات المراجعة</h6>
                <p>${revision.review_notes}</p>
                ${revision.reviewed_at ? `<small class="text-muted">تمت المراجعة في: ${formatDate(revision.reviewed_at)}</small>` : ''}
            </div>
        ` : ''}

        <div class="detail-section" id="transfer-history-section-${revision.id}">
            <h6><i class="fas fa-exchange-alt me-2"></i>سجل نقل التعديل</h6>
            <div id="transfer-history-content-${revision.id}">
                <div class="text-center text-muted">
                    <div class="spinner-border spinner-border-sm" role="status">
                        <span class="visually-hidden">جاري التحميل...</span>
                    </div>
                </div>
            </div>
        </div>
    `;

    $('#sidebarContent').html(html);

    // تحميل سجل النقل
    loadTransferHistory(revision.id);
}

function getRevisionTypeText(type) {
    const types = {
        'task': 'مهمة',
        'project': 'مشروع',
        'general': 'عام'
    };
    return types[type] || 'غير محدد';
}

// دالة للحصول على اسم المسند إليه
function getAssignedUserName(revision) {
    // إذا كان مسند مباشرة لمستخدم
    if (revision.assigned_user) {
        return revision.assigned_user.name;
    }

    // إذا كان مرتبط بـ TaskUser
    if (revision.task_user && revision.task_user.user) {
        return revision.task_user.user.name;
    }

    // إذا كان مرتبط بـ TemplateTaskUser
    if (revision.template_task_user && revision.template_task_user.user) {
        return revision.template_task_user.user.name;
    }

    return 'غير محدد';
}

// التحقق من أن التعديل مسند للمستخدم الحالي (كمنفذ)
function isRevisionAssignedToCurrentUser(revision) {
    const currentUserId = typeof AUTH_USER_ID !== 'undefined' ? AUTH_USER_ID : '';

    return revision.assigned_to == currentUserId ||
           revision.executor_user_id == currentUserId ||
           (revision.task_user && revision.task_user.user_id == currentUserId) ||
           (revision.template_task_user && revision.template_task_user.user_id == currentUserId);
}

// التحقق من أن المستخدم الحالي هو المراجع
function isCurrentUserReviewer(revision) {
    const currentUserId = typeof AUTH_USER_ID !== 'undefined' ? AUTH_USER_ID : '';
    return isCurrentReviewer(revision, currentUserId);
}

// Format revision time for display (in minutes)
function formatRevisionTimeInMinutes(minutes) {
    if (!minutes || minutes < 1) return '0 دقيقة';

    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;

    let result = '';
    if (hours > 0) {
        result += `${hours} ساعة`;
        if (mins > 0) {
            result += ` و ${mins} دقيقة`;
        }
    } else {
        result = `${mins} دقيقة`;
    }

    return result;
}

// Helper functions للألوان والحالات - المنفذ
function getExecutorBgColor(status) {
    const colors = {
        'new': '#eef2ff',
        'in_progress': '#fef3c7',
        'paused': '#f3e8ff',
        'completed': '#d1fae5'
    };
    return colors[status] || '#eef2ff';
}

function getExecutorBorderColor(status) {
    const colors = {
        'new': '#6366f1',
        'in_progress': '#f59e0b',
        'paused': '#8b5cf6',
        'completed': '#10b981'
    };
    return colors[status] || '#6366f1';
}

function getExecutorStatusBg(status) {
    const color = getExecutorBorderColor(status);
    return `${color}15`;
}

function getExecutorStatusIcon(status) {
    const icons = {
        'new': 'fa-clock',
        'in_progress': 'fa-spinner fa-spin',
        'paused': 'fa-pause',
        'completed': 'fa-check'
    };
    return icons[status] || 'fa-clock';
}

function getExecutorStatusText(status) {
    const texts = {
        'new': 'لم يبدأ',
        'in_progress': 'جاري العمل',
        'paused': 'متوقف',
        'completed': 'مكتمل'
    };
    return texts[status] || 'لم يبدأ';
}

// Helper functions للألوان والحالات - المراجع
function getReviewerBgColor(status) {
    const colors = {
        'new': '#eef2ff',
        'in_progress': '#fef3c7',
        'paused': '#f3e8ff',
        'completed': '#d1fae5'
    };
    return colors[status] || '#eef2ff';
}

function getReviewerBorderColor(status) {
    const colors = {
        'new': '#6366f1',
        'in_progress': '#f59e0b',
        'paused': '#8b5cf6',
        'completed': '#10b981'
    };
    return colors[status] || '#6366f1';
}

function getReviewerStatusBg(status) {
    const color = getReviewerBorderColor(status);
    return `${color}15`;
}

function getReviewerStatusIcon(status) {
    const icons = {
        'new': 'fa-clock',
        'in_progress': 'fa-spinner fa-spin',
        'paused': 'fa-pause',
        'completed': 'fa-check'
    };
    return icons[status] || 'fa-clock';
}

function getReviewerStatusText(status) {
    const texts = {
        'new': 'لم يبدأ',
        'in_progress': 'جاري المراجعة',
        'paused': 'متوقف',
        'completed': 'مكتمل'
    };
    return texts[status] || 'لم يبدأ';
}

// Load projects list for filtering
function loadProjectsList() {
    $.ajax({
        url: '/revision-page/projects-list',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const datalists = ['#allProjectsList', '#myProjectsList', '#myCreatedProjectsList'];

                datalists.forEach(listId => {
                    let options = '';
                    response.projects.forEach(project => {
                        options += `<option value="${project.code}">${project.display}</option>`;
                    });
                    $(listId).html(options);
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading projects list:', error);
        }
    });
}

// Load transfer history for revision
async function loadTransferHistory(revisionId) {
    const contentId = `#transfer-history-content-${revisionId}`;

    try {
        const response = await fetch(`/task-revisions/${revisionId}/transfer-history`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        const result = await response.json();

        if (result.success) {
            if (!result.history || result.history.length === 0) {
                $(contentId).html('<p class="text-muted text-center mb-0"><small>لم يتم نقل هذا التعديل من قبل</small></p>');
                return;
            }

            let html = '<div class="timeline">';
            result.history.forEach((transfer, index) => {
                const typeIcon = transfer.assignment_type === 'المنفذ' ? 'fa-hammer' : 'fa-user-check';
                const typeColor = transfer.assignment_type === 'المنفذ' ? 'primary' : 'success';

                html += `
                    <div class="timeline-item mb-3" style="padding: 0.8rem; background: #f8f9fa; border-right: 3px solid var(--bs-${typeColor}); border-radius: 6px;">
                        <div class="d-flex align-items-start gap-2">
                            <div>
                                <i class="fas ${typeIcon} text-${typeColor}"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <strong class="text-${typeColor}">${transfer.assignment_type}</strong>
                                    <small class="text-muted">${transfer.transferred_at}</small>
                                </div>
                                <div class="small">
                                    <div class="mb-1">
                                        <span class="badge bg-danger bg-opacity-75">من:</span>
                                        <strong>${transfer.from_user}</strong>
                                    </div>
                                    <div class="mb-1">
                                        <span class="badge bg-success bg-opacity-75">إلى:</span>
                                        <strong>${transfer.to_user}</strong>
                                    </div>
                                    <div class="text-muted">
                                        <i class="fas fa-user-shield me-1"></i>
                                        بواسطة: ${transfer.assigned_by}
                                    </div>
                                    ${transfer.reason ? `
                                        <div class="mt-2 p-2 bg-white rounded">
                                            <i class="fas fa-comment-alt me-1"></i>
                                            <small><strong>السبب:</strong> ${transfer.reason}</small>
                                        </div>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            html += '</div>';

            $(contentId).html(html);
        } else {
            $(contentId).html('<p class="text-danger text-center mb-0"><small>حدث خطأ في تحميل سجل النقل</small></p>');
        }
    } catch (error) {
        console.error('Error loading transfer history:', error);
        $(contentId).html('<p class="text-danger text-center mb-0"><small>حدث خطأ في الاتصال بالخادم</small></p>');
    }
}

function filterProjectsByStatus(status) {

    $('.projects-status-table tbody tr').not('.projects-filter-tabs-row').addClass('d-none');


    $(`.projects-rows-${status}`).removeClass('d-none');


    $('#projectsStatusTabs .projects-tab-btn').removeClass('active');
    $(`#projects-${status}-tab`).addClass('active');
}

function toggleProjectsTable() {
    const container = $('#projectsTableContainer');
    const icon = $('#toggleProjectsIcon');

    if (container.is(':visible')) {
        container.slideUp(300);
        icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
    } else {
        container.slideDown(300);
        icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
    }
}

function filterRevisionsByProject(projectId, projectCode) {
    const activeTab = $('#revisionTabs .nav-link.active').attr('id');
    let tabType = 'all';

    if (activeTab && activeTab.includes('my')) {
        tabType = 'my';
    } else if (activeTab && activeTab.includes('created')) {
        tabType = 'myCreated';
    }


    let projectFilterInput;
    if (tabType === 'all') {
        projectFilterInput = $('#allProjectCodeFilter');
    } else if (tabType === 'my') {
        projectFilterInput = $('#myProjectCodeFilter');
    } else if (tabType === 'myCreated') {
        projectFilterInput = $('#myCreatedProjectCodeFilter');
    }


    if (projectFilterInput) {
        projectFilterInput.val(projectCode);

        setTimeout(() => {
            applyFilters(tabType);
        }, 100);
    }


    $('#projectsTableContainer').slideUp(300);
    $('#toggleProjectsIcon').removeClass('fa-chevron-up').addClass('fa-chevron-down');
}

