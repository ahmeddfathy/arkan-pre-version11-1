/**
 * My Revisions Page JavaScript
 * Handles all functionality for the "My Revisions" page
 */

// Store current revisions data
let currentMyRevisionsData = [];

// Custom functions for My Revisions page
function refreshMyRevisions() {
    loadMyStats(); // تحديث الإحصائيات
    loadMyRevisionsPage();

    // Update kanban if visible
    if ($("#myRevisionsKanbanBoard").is(":visible")) {
        loadMyRevisionsKanban();
    }
}

function applyMyFilters() {
    const filters = {
        search: $("#mySearchInput").val(),
        project_code: $("#myProjectCodeFilter").val(),
        month: $("#myMonthFilter").val(),
        revision_type: $("#myRevisionTypeFilter").val(), // This filter will be ignored since we load separately
        revision_source: $("#myRevisionSourceFilter").val(),
        status: $("#myStatusFilter").val(),
        deadline_from: $("#myDeadlineFrom").val(),
        deadline_to: $("#myDeadlineTo").val(),
    };

    // Remove revision_type from filters since we load separately
    delete filters.revision_type;

    loadMyRevisionsPage(1, filters);

    // Update kanban if visible
    if ($("#myRevisionsKanbanBoard").is(":visible")) {
        loadMyRevisionsKanban(filters);
    }
}

function clearMyFilters() {
    $("#mySearchInput").val("");
    $("#myProjectCodeFilter").val("");
    $("#myMonthFilter").val("");
    $("#myRevisionTypeFilter").val("");
    $("#myRevisionSourceFilter").val("");
    $("#myStatusFilter").val("");
    $("#myDeadlineFrom").val("");
    $("#myDeadlineTo").val("");
    loadMyRevisionsPage();
}

function loadMyRevisionsPage(page = 1, filters = {}) {
    // Copy filters for both
    const projectsFilters = { ...filters };
    const tasksFilters = { ...filters };

    // Load Projects Revisions (without loading overlay)
    loadProjectsRevisions(1, projectsFilters);

    // Load Tasks Revisions (without loading overlay)
    loadTasksRevisions(1, tasksFilters);
}

// Store filters for pagination (separate for projects and tasks)
let currentProjectsFilters = {};
let currentTasksFilters = {};

// Custom pagination renderer for projects revisions
function renderMyRevisionsPagination(data, containerId) {
    const container = $("#" + containerId);

    if (data.last_page <= 1) {
        container.empty();
        return;
    }

    let html = '<nav><ul class="pagination">';

    // Previous button
    if (data.current_page > 1) {
        const prevPage = data.current_page - 1;
        html += `<li class="page-item">
            <a class="page-link" href="#" onclick="event.preventDefault(); loadProjectsRevisions(${prevPage}); return false;">السابق</a>
        </li>`;
    }

    // Page numbers
    for (let i = 1; i <= data.last_page; i++) {
        if (i === data.current_page) {
            html += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
        } else {
            html += `<li class="page-item">
                <a class="page-link" href="#" onclick="event.preventDefault(); loadProjectsRevisions(${i}); return false;">${i}</a>
            </li>`;
        }
    }

    // Next button
    if (data.current_page < data.last_page) {
        const nextPage = data.current_page + 1;
        html += `<li class="page-item">
            <a class="page-link" href="#" onclick="event.preventDefault(); loadProjectsRevisions(${nextPage}); return false;">التالي</a>
        </li>`;
    }

    html += "</ul></nav>";
    container.html(html);
}

// Custom pagination renderer for tasks revisions
function renderTasksRevisionsPagination(data, containerId) {
    const container = $("#" + containerId);

    if (data.last_page <= 1) {
        container.empty();
        return;
    }

    let html = '<nav><ul class="pagination">';

    // Previous button
    if (data.current_page > 1) {
        const prevPage = data.current_page - 1;
        html += `<li class="page-item">
            <a class="page-link" href="#" onclick="event.preventDefault(); loadTasksRevisions(${prevPage}); return false;">السابق</a>
        </li>`;
    }

    // Page numbers
    for (let i = 1; i <= data.last_page; i++) {
        if (i === data.current_page) {
            html += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
        } else {
            html += `<li class="page-item">
                <a class="page-link" href="#" onclick="event.preventDefault(); loadTasksRevisions(${i}); return false;">${i}</a>
            </li>`;
        }
    }

    // Next button
    if (data.current_page < data.last_page) {
        const nextPage = data.current_page + 1;
        html += `<li class="page-item">
            <a class="page-link" href="#" onclick="event.preventDefault(); loadTasksRevisions(${nextPage}); return false;">التالي</a>
        </li>`;
    }

    html += "</ul></nav>";
    container.html(html);
}

// Load Projects Revisions
function loadProjectsRevisions(page = 1, filters = {}) {
    if (filters && Object.keys(filters).length > 0) {
        currentProjectsFilters = filters;
    }

    // Show table structure immediately with loading message
    $("#projectsRevisionsContainer").html(`
        <div class="revisions-table"><div class="table-responsive"><table class="table mb-0">
            <thead><tr>
                <th style="width: 5%;">#</th>
                <th style="width: 25%;">العنوان</th>
                <th style="width: 15%;">المشروع</th>
                <th style="width: 10%;">النوع</th>
                <th style="width: 10%;">المصدر</th>
                <th style="width: 10%;">الحالة</th>
                <th style="width: 10%;">دوري</th>
                <th style="width: 10%;">الديدلاين</th>
                <th style="width: 5%;">الإجراءات</th>
            </tr></thead>
            <tbody><tr><td colspan="9" class="text-center py-3"><i class="fas fa-spinner fa-spin me-2"></i>جاري التحميل...</td></tr></tbody>
        </table></div></div>
    `);

    $.ajax({
        url: "/revision-page/my-revisions",
        method: "GET",
        data: {
            page: page,
            revision_type: "project",
            ...currentProjectsFilters,
        },
        success: function (response) {
            if (response.success) {
                $("#projectsRevisionsCount").text(
                    response.revisions.total || 0
                );
                renderMyRevisionsList(
                    response.revisions.data,
                    "projectsRevisionsContainer"
                );
                renderMyRevisionsPagination(
                    response.revisions,
                    "projectsRevisionsPagination"
                );
            }
        },
        error: function (xhr) {
            console.error("Error loading projects revisions:", xhr);
            $("#projectsRevisionsContainer").html(`
                <div class="empty-state">
                    <i class="fas fa-exclamation-circle"></i>
                    <h4>حدث خطأ</h4>
                    <p class="text-muted">حدث خطأ في تحميل تعديلات المشاريع</p>
                </div>
            `);
        },
    });
}

// Load Tasks Revisions
function loadTasksRevisions(page = 1, filters = {}) {
    if (filters && Object.keys(filters).length > 0) {
        currentTasksFilters = filters;
    }

    // Show table structure immediately with loading message
    $("#tasksRevisionsContainer").html(`
        <div class="revisions-table"><div class="table-responsive"><table class="table mb-0">
            <thead><tr>
                <th style="width: 5%;">#</th>
                <th style="width: 30%;">العنوان</th>
                <th style="width: 10%;">المصدر</th>
                <th style="width: 10%;">الحالة</th>
                <th style="width: 10%;">دوري</th>
                <th style="width: 10%;">الديدلاين</th>
                <th style="width: 5%;">الإجراءات</th>
            </tr></thead>
            <tbody><tr><td colspan="7" class="text-center py-3"><i class="fas fa-spinner fa-spin me-2"></i>جاري التحميل...</td></tr></tbody>
        </table></div></div>
    `);

    $.ajax({
        url: "/revision-page/my-revisions",
        method: "GET",
        data: {
            page: page,
            revision_type: "task",
            ...currentTasksFilters,
        },
        success: function (response) {
            if (response.success) {
                $("#tasksRevisionsCount").text(response.revisions.total || 0);
                renderMyRevisionsList(
                    response.revisions.data,
                    "tasksRevisionsContainer"
                );
                renderTasksRevisionsPagination(
                    response.revisions,
                    "tasksRevisionsPagination"
                );
            }
        },
        error: function (xhr) {
            console.error("Error loading tasks revisions:", xhr);
            $("#tasksRevisionsContainer").html(`
                <div class="empty-state">
                    <i class="fas fa-exclamation-circle"></i>
                    <h4>حدث خطأ</h4>
                    <p class="text-muted">حدث خطأ في تحميل تعديلات المهام</p>
                </div>
            `);
        },
    });
}

function renderMyRevisionsList(
    revisions,
    containerId = "myRevisionsContainer"
) {
    const container = $("#" + containerId);

    if (!revisions || revisions.length === 0) {
        container.html(`
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h4>لا توجد تعديلات</h4>
                <p class="text-muted">لا توجد تعديلات مسندة لك حالياً</p>
            </div>
        `);
        return;
    }

    // تحديد إذا كان جدول المهام (لا نعرض عمود المشروع والنوع)
    const isTasksTable = containerId === "tasksRevisionsContainer";

    let html =
        '<div class="revisions-table"><div class="table-responsive"><table class="table mb-0">';
    html += "<thead><tr>";
    html += '<th style="width: 5%;">#</th>';
    html +=
        '<th style="width: ' +
        (isTasksTable ? "30%" : "25%") +
        ';">العنوان</th>';
    if (!isTasksTable) {
        html += '<th style="width: 15%;">المشروع</th>';
        html += '<th style="width: 10%;">النوع</th>';
    }
    html += '<th style="width: 10%;">المصدر</th>';
    html += '<th style="width: 10%;">الحالة</th>';
    html += '<th style="width: 10%;">دوري</th>';
    html += '<th style="width: 10%;">الديدلاين</th>';
    html += '<th style="width: 5%;">الإجراءات</th>';
    html += "</tr></thead><tbody>";

    revisions.forEach((revision, index) => {
        const projectName = revision.project ? revision.project.name : "-";
        const projectCode =
            revision.project && revision.project.code
                ? revision.project.code
                : "";

        // تحديد دور المستخدم
        let userRole = "";

        // التحقق من المسؤول (responsible_user_id)
        if (revision.responsible_user_id == AUTH_USER_ID) {
            userRole =
                '<span class="badge bg-danger"><i class="fas fa-user-tie"></i> مسؤول</span>';
        }

        // التحقق من المنفذ (executor_user_id)
        if (revision.executor_user_id == AUTH_USER_ID) {
            userRole +=
                (userRole ? " " : "") +
                '<span class="badge bg-primary"><i class="fas fa-wrench"></i> منفذ</span>';
        }

        // Check reviewers
        if (revision.reviewers_json) {
            try {
                const reviewers = JSON.parse(revision.reviewers_json);
                const isReviewer = reviewers.some(
                    (r) => r.user_id == AUTH_USER_ID
                );
                if (isReviewer) {
                    userRole +=
                        (userRole ? " " : "") +
                        '<span class="badge bg-success"><i class="fas fa-check-circle"></i> مراجع</span>';
                }
            } catch (e) {}
        }

        // إذا لم يكن لديه أي دور محدد، عرض "-"
        if (!userRole) {
            userRole = '<span class="text-muted">-</span>';
        }

        html += `<tr onclick="showRevisionDetails(${revision.id})" style="cursor: pointer;" data-revision-id="${revision.id}">`;
        html += `<td>${index + 1}</td>`;
        html += `<td><strong>${revision.title || "-"}</strong></td>`;
        if (!isTasksTable) {
            html += `<td>${
                projectCode ? projectCode + " - " : ""
            }${projectName}</td>`;
            html += `<td><span class="badge bg-info">${
                revision.revision_type === "project" ? "مشروع" : "مهمة"
            }</span></td>`;
        }
        html += `<td><span class="source-badge source-${
            revision.revision_source
        }">${
            revision.revision_source === "internal" ? "داخلي" : "خارجي"
        }</span></td>`;
        html += `<td><span class="status-badge status-${
            revision.status
        }">${getStatusText(revision.status)}</span></td>`;
        html += `<td>${userRole}</td>`;
        html += `<td>${
            revision.revision_deadline
                ? new Date(revision.revision_deadline).toLocaleDateString(
                      "ar-EG"
                  )
                : "-"
        }</td>`;
        html += `<td><button class="btn btn-sm btn-primary" onclick="event.stopPropagation(); showRevisionDetails(${revision.id})"><i class="fas fa-eye"></i></button></td>`;
        html += "</tr>";
    });

    html += "</tbody></table></div></div>";
    container.html(html);
}

function loadMyStats() {
    $.ajax({
        url: "/revision-page/stats",
        method: "GET",
        success: function (response) {
            if (response.success) {
                const container = $("#myStatsContainer");
                let html = "";

                // التعديلات المسندة إلي
                if (response.stats.my_assigned_revisions) {
                    const stats = response.stats.my_assigned_revisions;
                    html += `
                        <div class="col-md-6 mb-4">
                            <div class="stats-card">
                                <h5 class="mb-3">
                                    <i class="fas fa-tasks me-2" style="color: #667eea;"></i>
                                    التعديلات المسندة إلي
                                </h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="stats-item">
                                            <div class="stats-number">${
                                                stats.total || 0
                                            }</div>
                                            <div>إجمالي المسندة</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="stats-item">
                                            <div class="stats-number">${
                                                stats.new || 0
                                            }</div>
                                            <div>جديد</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="stats-item">
                                            <div class="stats-number">${
                                                stats.in_progress || 0
                                            }</div>
                                            <div>جاري العمل</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="stats-item">
                                            <div class="stats-number">${
                                                stats.paused || 0
                                            }</div>
                                            <div>متوقف</div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="stats-item">
                                            <div class="stats-number">${
                                                stats.completed || 0
                                            }</div>
                                            <div>مكتمل</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                }

                // التعديلات التي أنا مسؤول عنها (غلطتي)
                if (response.stats.my_responsible_revisions) {
                    const responsibleStats =
                        response.stats.my_responsible_revisions;
                    html += `
                        <div class="col-md-6 mb-4">
                            <div class="stats-card">
                                <h5 class="mb-3">
                                    <i class="fas fa-user-tie me-2" style="color: #dc3545;"></i>
                                    التعديلات المسؤول عنها (غلتك)
                                </h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="stats-item">
                                            <div class="stats-number" style="color: #dc3545;">${
                                                responsibleStats.total || 0
                                            }</div>
                                            <div>إجمالي المسؤول عنها</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="stats-item">
                                            <div class="stats-number" style="color: #dc3545;">${
                                                responsibleStats.new || 0
                                            }</div>
                                            <div>جديد</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="stats-item">
                                            <div class="stats-number" style="color: #dc3545;">${
                                                responsibleStats.in_progress ||
                                                0
                                            }</div>
                                            <div>جاري العمل</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="stats-item">
                                            <div class="stats-number" style="color: #dc3545;">${
                                                responsibleStats.paused || 0
                                            }</div>
                                            <div>متوقف</div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="stats-item">
                                            <div class="stats-number" style="color: #dc3545;">${
                                                responsibleStats.completed || 0
                                            }</div>
                                            <div>مكتمل</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                }

                container.html(html);
            }
        },
        error: function () {
            console.error("Error loading stats");
        },
    });
}

function getStatusText(status) {
    const statusMap = {
        new: "جديد",
        in_progress: "جاري العمل",
        paused: "متوقف",
        completed: "مكتمل",
        pending: "في الانتظار",
        approved: "موافق عليه",
        rejected: "مرفوض",
    };
    return statusMap[status] || status;
}

// Toggle between Table and Kanban view
function toggleMyRevisionsView(viewType) {
    if (viewType === "table") {
        $("#myRevisionsContainer").show();
        $("#myRevisionsKanbanBoard").hide();
        $("#tableViewBtn").addClass("active");
        $("#kanbanViewBtn").removeClass("active");
    } else {
        $("#myRevisionsContainer").hide();
        $("#myRevisionsKanbanBoard").show();
        $("#kanbanViewBtn").addClass("active");
        $("#tableViewBtn").removeClass("active");

        // Get current filters
        const filters = {
            search: $("#mySearchInput").val(),
            project_code: $("#myProjectCodeFilter").val(),
            month: $("#myMonthFilter").val(),
            revision_type: $("#myRevisionTypeFilter").val(),
            revision_source: $("#myRevisionSourceFilter").val(),
            status: $("#myStatusFilter").val(),
            deadline_from: $("#myDeadlineFrom").val(),
            deadline_to: $("#myDeadlineTo").val(),
        };

        // Always reload kanban with current filters to ensure we have all data
        loadMyRevisionsKanban(filters);
    }
}

// Load Kanban board
function loadMyRevisionsKanban(filters = {}) {
    // Show loading
    $("#myRevisionsKanbanBoard").html(
        '<div class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-2">جاري تحميل الكانبان...</p></div>'
    );

    $.ajax({
        url: "/revision-page/my-revisions",
        method: "GET",
        data: {
            page: 1,
            per_page: 1000, // Load many records for kanban
            ...filters,
        },
        success: function (response) {
            console.log("Kanban response:", response); // Debug
            if (
                response.success &&
                response.revisions &&
                response.revisions.data
            ) {
                const revisions = response.revisions.data;
                console.log("Revisions count:", revisions.length); // Debug

                if (revisions.length === 0) {
                    $("#myRevisionsKanbanBoard").html(
                        '<div class="alert alert-info text-center">لا توجد تعديلات لعرضها</div>'
                    );
                } else {
                    // Restore kanban HTML structure
                    restoreKanbanStructure();
                    renderMyRevisionsKanban(revisions);
                }
            } else {
                console.error("Invalid response format:", response);
                $("#myRevisionsKanbanBoard").html(
                    '<div class="alert alert-warning text-center">حدث خطأ في تحميل البيانات</div>'
                );
            }
        },
        error: function (xhr, status, error) {
            console.error("Error loading kanban:", error, xhr);
            $("#myRevisionsKanbanBoard").html(
                '<div class="alert alert-danger text-center">حدث خطأ في تحميل الكانبان</div>'
            );
        },
    });
}

// Restore kanban HTML structure if it was replaced
function restoreKanbanStructure() {
    const kanbanHTML = `
        <div class="kanban-columns">
            <!-- New Column -->
            <div class="kanban-column status-new" id="kanban-column-new">
                <div class="kanban-column-header">
                    <div class="kanban-column-title">
                        <i class="fas fa-plus-circle"></i>
                        جديد
                    </div>
                    <span class="kanban-column-count">0</span>
                </div>
                <div class="kanban-column-cards"></div>
            </div>

            <!-- In Progress Column -->
            <div class="kanban-column status-in-progress" id="kanban-column-in_progress">
                <div class="kanban-column-header">
                    <div class="kanban-column-title">
                        <i class="fas fa-spinner"></i>
                        جاري العمل
                    </div>
                    <span class="kanban-column-count">0</span>
                </div>
                <div class="kanban-column-cards"></div>
            </div>

            <!-- Paused Column -->
            <div class="kanban-column status-paused" id="kanban-column-paused">
                <div class="kanban-column-header">
                    <div class="kanban-column-title">
                        <i class="fas fa-pause-circle"></i>
                        متوقف مؤقتاً
                    </div>
                    <span class="kanban-column-count">0</span>
                </div>
                <div class="kanban-column-cards"></div>
            </div>

            <!-- Completed Column -->
            <div class="kanban-column status-completed" id="kanban-column-completed">
                <div class="kanban-column-header">
                    <div class="kanban-column-title">
                        <i class="fas fa-check-circle"></i>
                        مكتمل
                    </div>
                    <span class="kanban-column-count">0</span>
                </div>
                <div class="kanban-column-cards"></div>
            </div>
        </div>
    `;

    $("#myRevisionsKanbanBoard").html(kanbanHTML);
}

// Render Kanban board
function renderMyRevisionsKanban(revisions) {
    if (!revisions || revisions.length === 0) {
        console.log("No revisions to render");
        return;
    }

    console.log("Rendering kanban with", revisions.length, "revisions");

    // Group revisions by status
    const grouped = {
        new: [],
        in_progress: [],
        paused: [],
        completed: [],
    };

    revisions.forEach((revision) => {
        const status = revision.status || "new";
        console.log("Revision:", revision.title, "Status:", status); // Debug
        if (grouped[status]) {
            grouped[status].push(revision);
        } else {
            // If status not in our list, put it in 'new' as default
            console.warn(
                "Unknown status:",
                status,
                "for revision:",
                revision.id
            );
            grouped["new"].push(revision);
        }
    });

    console.log("Grouped revisions:", grouped); // Debug

    // Render each column
    ["new", "in_progress", "paused", "completed"].forEach((status) => {
        const columnId = `kanban-column-${status}`;
        const column = $(`#${columnId}`);

        if (column.length === 0) {
            console.error("Column not found:", columnId);
            return;
        }

        const cardsContainer = column.find(".kanban-column-cards");
        const countSpan = column.find(".kanban-column-count");

        if (countSpan.length === 0 || cardsContainer.length === 0) {
            console.error(
                "Count span or cards container not found for:",
                columnId
            );
            return;
        }

        countSpan.text(grouped[status].length);

        if (grouped[status].length === 0) {
            cardsContainer.html(
                '<div class="text-center text-muted py-3">لا توجد تعديلات</div>'
            );
        } else {
            let html = "";
            grouped[status].forEach((revision) => {
                const projectName = revision.project
                    ? revision.project.name
                    : "-";
                const projectCode =
                    revision.project && revision.project.code
                        ? revision.project.code
                        : "";
                const creatorName = revision.creator
                    ? revision.creator.name
                    : "غير محدد";
                const description = revision.description
                    ? revision.description.length > 100
                        ? revision.description.substring(0, 100) + "..."
                        : revision.description
                    : "";

                // Determine work type (responsible, executor, or reviewer)
                let workType = "";
                let workTypeBadge = "";

                // Check if responsible
                if (revision.responsible_user_id == AUTH_USER_ID) {
                    workType = "responsible";
                    workTypeBadge =
                        '<span class="badge bg-danger" style="font-size: 0.7rem;"><i class="fas fa-user-tie"></i> مسؤول</span>';
                }

                // Check if executor
                if (revision.executor_user_id == AUTH_USER_ID) {
                    workType = workType ? "both" : "executor";
                    workTypeBadge +=
                        (workTypeBadge ? " " : "") +
                        '<span class="badge bg-primary" style="font-size: 0.7rem;"><i class="fas fa-wrench"></i> منفذ</span>';
                }

                // Check if reviewer
                if (revision.reviewers_json) {
                    try {
                        const reviewers = JSON.parse(revision.reviewers_json);
                        const isReviewer = reviewers.some(
                            (r) => r.user_id == AUTH_USER_ID
                        );
                        if (isReviewer) {
                            workType = workType ? "both" : "reviewer";
                            workTypeBadge +=
                                (workTypeBadge ? " " : "") +
                                '<span class="badge bg-success" style="font-size: 0.7rem;"><i class="fas fa-check-circle"></i> مراجع</span>';
                        }
                    } catch (e) {}
                }

                // Source icon
                const sourceIcons = {
                    internal: "fa-building",
                    external: "fa-globe",
                };
                const sourceIcon =
                    sourceIcons[revision.revision_source] || "fa-question";

                // Format date
                const revisionDate = revision.revision_date
                    ? new Date(revision.revision_date).toLocaleDateString(
                          "ar-EG"
                      )
                    : "-";

                html += `
                    <div class="revision-kanban-card"
                         data-revision-id="${revision.id}"
                         data-status="${revision.status}"
                         data-work-type="${workType}"
                         onclick="showRevisionDetails(${revision.id})">
                        <div class="revision-kanban-card-header">
                            <div class="revision-kanban-card-title">
                                ${workTypeBadge}
                                ${revision.title || "-"}
                            </div>
                            <span class="revision-kanban-card-source source-${
                                revision.revision_source
                            }">
                                <i class="fas ${sourceIcon}"></i>
                            </span>
                        </div>

                        <div class="revision-kanban-card-body">
                            ${
                                description
                                    ? `<div class="revision-kanban-card-description">${description}</div>`
                                    : ""
                            }
                        </div>

                        <div class="revision-kanban-card-meta">
                            <div class="revision-kanban-card-meta-item">
                                <i class="fas fa-user"></i>
                                ${creatorName}
                            </div>
                            ${
                                projectCode || projectName !== "-"
                                    ? `
                                <div class="revision-kanban-card-meta-item" title="${projectName}">
                                    <i class="fas fa-project-diagram"></i>
                                    ${projectCode || projectName}
                                </div>
                            `
                                    : ""
                            }
                        </div>

                        <div class="revision-kanban-card-footer">
                            <div class="revision-kanban-card-date">
                                <i class="fas fa-calendar"></i>
                                ${revisionDate}
                            </div>
                        </div>
                    </div>
                `;
            });
            cardsContainer.html(html);
            console.log(
                "Rendered",
                grouped[status].length,
                "cards in",
                status,
                "column"
            );
        }
    });
}

// Load data on page load
$(document).ready(function () {
    loadMyStats(); // تحميل الإحصائيات أولاً
    loadMyRevisionsPage();
    loadProjectsList("myProjectsList");

    // Kanban/Table toggle buttons
    $("#tableViewBtn").on("click", function () {
        toggleMyRevisionsView("table");
    });

    $("#kanbanViewBtn").on("click", function () {
        toggleMyRevisionsView("kanban");
    });
});
