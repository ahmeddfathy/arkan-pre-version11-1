// Simple Projects Overview JavaScript

// Global variables to store current context
let currentProjectId = null;
let currentServiceId = null;

function toggleServices(button) {
    const projectId = button.getAttribute("data-project-id");
    const projectName = button.getAttribute("data-project-name");
    const servicesRow = document.getElementById(`services-${projectId}`);

    if (
        servicesRow.style.display === "none" ||
        servicesRow.style.display === ""
    ) {
        // Show services
        servicesRow.style.display = "table-row";
        button.innerHTML = '<i class="fas fa-eye-slash"></i> إخفاء الخدمات';

        // Load services if not already loaded
        const servicesContainer = servicesRow.querySelector(
            ".services-container"
        );
        if (servicesContainer.innerHTML.includes("loading")) {
            loadServices(projectId, projectName, servicesContainer);
        }
    } else {
        // Hide services
        servicesRow.style.display = "none";
        button.innerHTML = '<i class="fas fa-list"></i> عرض الخدمات';
    }
}

function loadServices(projectId, projectName, container) {
    currentProjectId = projectId; // Store for later use

    // Show loading
    container.innerHTML = `
        <div class="services-loading">
            <i class="fas fa-spinner fa-spin"></i>
            جاري تحميل الخدمات...
        </div>
    `;

    // Fetch services
    fetch(`/projects/${projectId}/services-simple`)
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                displayServicesInline(
                    data.services,
                    projectName,
                    container,
                    projectId
                );
            } else {
                container.innerHTML = `
                    <div class="services-error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>${data.message}</p>
                    </div>
                `;
            }
        })
        .catch((error) => {
            console.error("Error:", error);
            container.innerHTML = `
                <div class="services-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>حدث خطأ في تحميل الخدمات</p>
                </div>
            `;
        });
}

function displayServicesInline(
    services,
    projectName,
    container,
    projectId = null
) {
    projectId =
        projectId ||
        currentProjectId ||
        container.closest(".services-row")?.id?.replace("services-", "") ||
        container.parentElement?.parentElement?.id?.replace("services-", "");

    if (services.length === 0) {
        const html = `
            <div class="services-empty">
                <i class="fas fa-list"></i>
                <h4>لا توجد خدمات</h4>
                <p>هذا المشروع لا يحتوي على أي خدمات</p>
            </div>
            <div style="margin-top: 1rem; text-align: center; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                <button data-project-id="${projectId}" class="hide-services-btn"
                    style="background: #ef4444; color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; font-size: 0.875rem; font-weight: 500; transition: all 0.2s ease;"
                    onmouseover="this.style.background='#dc2626'; this.style.transform='translateY(-1px)'"
                    onmouseout="this.style.background='#ef4444'; this.style.transform='translateY(0)'">
                    <i class="fas fa-eye-slash"></i> إخفاء الخدمات
                </button>
            </div>
        `;
        container.innerHTML = html;
        const hideBtn = container.querySelector(".hide-services-btn");
        if (hideBtn) {
            hideBtn.addEventListener("click", function () {
                const projectId = this.getAttribute("data-project-id");
                const btn = document.querySelector(
                    '[data-project-id="' + projectId + '"].services-btn'
                );
                if (btn) toggleServices(btn);
            });
        }
        return;
    }

    let html = `
        <div class="services-header">
            <h4>خدمات المشروع: ${projectName}</h4>
        </div>
        <div class="services-list">
    `;

    services.forEach((service) => {
        const statusClass = getStatusClass(service.status);
        const statusIcon = getStatusIcon(service.status);
        const deliveryStatusClass = service.delivery_status_class || "warning";

        html += `
            <div class="service-item ${statusClass}">
                <div class="service-header">
                    <div class="service-name">
                        <i class="fas fa-cog"></i>
                        ${service.name}
                    </div>
                    <div class="service-status ${statusClass}">
                        ${statusIcon} ${service.status}
                    </div>
                    <div class="service-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${
                                service.progress
                            }%"></div>
                        </div>
                        <span class="progress-text">${service.progress}%</span>
                    </div>
                    <div class="service-participants-count">
                        <i class="fas fa-users"></i>
                        ${service.delivered_participants_count}/${
            service.participants_count
        } مشارك سلم
                    </div>
                    <div class="service-tasks-count">
                        <i class="fas fa-tasks"></i>
                        ${service.completed_tasks}/${
            service.total_tasks
        } مهمة مكتملة
                    </div>
                    <div class="service-delivery-status delivery-status-${deliveryStatusClass}">
                        ${service.delivery_status_text}
                    </div>
                </div>
                ${displayParticipantsList(service.participants)}
            </div>
        `;
    });

    html += "</div>";
    html += `
        <div style="margin-top: 1rem; text-align: center; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
            <button data-project-id="${projectId}" class="hide-services-btn"
                style="background: #ef4444; color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; font-size: 0.875rem; font-weight: 500; transition: all 0.2s ease;"
                onmouseover="this.style.background='#dc2626'; this.style.transform='translateY(-1px)'"
                onmouseout="this.style.background='#ef4444'; this.style.transform='translateY(0)'">
                <i class="fas fa-eye-slash"></i> إخفاء الخدمات
            </button>
        </div>
    `;
    container.innerHTML = html;

    container
        .querySelector(".hide-services-btn")
        ?.addEventListener("click", function () {
            const btn = document.querySelector(
                '[data-project-id="' +
                    this.getAttribute("data-project-id") +
                    '"].services-btn'
            );
            if (btn) toggleServices(btn);
        });
}

// دالة جديدة لعرض قائمة المشاركين تحت كل خدمة
function displayParticipantsList(participants) {
    if (!participants || participants.length === 0) {
        return `
            <div class="service-participants-list">
                <div class="participants-empty-message">
                    <i class="fas fa-user-slash"></i>
                    <span>لا يوجد مشاركين في هذه الخدمة</span>
                </div>
            </div>
        `;
    }

    let html = '<div class="service-participants-list">';
    html +=
        '<div class="participants-header"><i class="fas fa-users"></i> المشاركين:</div>';
    html += '<div class="participants-grid">';

    participants.forEach((participant) => {
        const statusClass = participant.delivery_status; // not_delivered, delivered_on_time, delivered_late
        const statusIcon = participant.delivery_status_icon;
        const statusText = participant.delivery_status_text;

        html += `
            <div class="participant-card ${statusClass}"
>
                <div class="participant-info">
                    <div class="participant-name">
                        <i class="fas fa-user-circle"></i>
                        <span>${participant.name}</span>
                        ${
                            participant.employee_id
                                ? `<span class="employee-id">#${participant.employee_id}</span>`
                                : ""
                        }
                    </div>
                    <div class="participant-status ${statusClass}">
                        <span class="status-icon">${statusIcon}</span>
                        <span class="status-text">${statusText}</span>
                    </div>
                </div>

                <!-- معلومات المهام -->
                <div class="participant-tasks-info">
                    <div class="task-stat">
                        <i class="fas fa-tasks"></i>
                        <span class="task-stat-label">إجمالي المهام:</span>
                        <span class="task-stat-value">${
                            participant.total_tasks || 0
                        }</span>
                    </div>
                    <div class="task-stat completed">
                        <i class="fas fa-check-circle"></i>
                        <span class="task-stat-label">مكتملة:</span>
                        <span class="task-stat-value">${
                            participant.completed_tasks || 0
                        }</span>
                    </div>
                    ${
                        participant.late_tasks > 0
                            ? `
                        <div class="task-stat late">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span class="task-stat-label">متأخرة:</span>
                            <span class="task-stat-value">${participant.late_tasks}</span>
                        </div>
                    `
                            : ""
                    }
                </div>

                ${
                    participant.deadline
                        ? `
                    <div class="participant-deadline-info">
                        <i class="fas fa-calendar-alt"></i>
                        <span>الموعد: ${participant.deadline}</span>
                        ${
                            participant.delivered_at
                                ? `<span class="delivered-date">سلم: ${participant.delivered_at}</span>`
                                : ""
                        }
                    </div>
                `
                        : ""
                }
            </div>
        `;
    });

    html += "</div></div>";
    return html;
}

function displayParticipants(participants) {
    if (!participants || participants.length === 0) {
        return `
            <div class="participants-empty">
                <i class="fas fa-users"></i>
                <span>لا يوجد مشاركين في هذه الخدمة</span>
            </div>
        `;
    }

    let html = '<div class="participants-list">';

    participants.forEach((participant) => {
        const deadlineStatus = getDeadlineStatus(participant);
        const approvalStatus = getApprovalStatus(participant);

        html += `
            <div class="participant-item">
                <div class="participant-header">
                    <div class="participant-info">
                        <div class="participant-name">
                            <i class="fas fa-user"></i>
                            ${participant.name}
                        </div>
                        <div class="participant-role">
                            <i class="fas fa-user-tag"></i>
                            ${participant.role}
                        </div>
                        <div class="participant-share">
                            <i class="fas fa-percentage"></i>
                            ${participant.project_share}
                        </div>
                    </div>
                    <div class="participant-deadline ${deadlineStatus.class}">
                        <i class="fas fa-calendar-alt"></i>
                        ${deadlineStatus.text}
                    </div>
                    <div class="participant-approvals">
                        ${approvalStatus}
                    </div>
                </div>

                <div class="participant-tasks">
                    ${displayParticipantTasks(participant.tasks)}
                </div>
            </div>
        `;
    });

    html += "</div>";
    return html;
}

function displayParticipantTasks(tasks) {
    if (!tasks || tasks.length === 0) {
        return `
            <div class="tasks-empty">
                <i class="fas fa-tasks"></i>
                <span>لا توجد مهام مخصصة</span>
            </div>
        `;
    }

    let html = '<div class="tasks-list">';

    tasks.forEach((task) => {
        const taskStatusClass = getTaskStatusClass(task.status);
        const deadlineStatus = getTaskDeadlineStatus(task);

        html += `
            <div class="task-item ${taskStatusClass}">
                <div class="task-info">
                    <div class="task-name">
                        <i class="fas fa-tasks"></i>
                        ${task.name}
                    </div>
                    <div class="task-status ${taskStatusClass}">
                        <i class="fas fa-circle"></i>
                        ${task.status}
                    </div>
                </div>
                <div class="task-details">
                    <div class="task-deadline ${deadlineStatus.class}">
                        <i class="fas fa-clock"></i>
                        ${deadlineStatus.text}
                    </div>
                    <div class="task-time">
                        <span class="estimated-time">مقدر: ${
                            task.estimated_time
                        }</span>
                        <span class="actual-time">فعلي: ${
                            task.actual_time
                        }</span>
                    </div>
                    <div class="task-approvals">
                        ${getTaskApprovalStatus(task)}
                    </div>
                </div>
            </div>
        `;
    });

    html += "</div>";
    return html;
}

function getDeadlineStatus(participant) {
    if (!participant.deadline) {
        return {
            class: "no-deadline",
            text: "بدون موعد نهائي",
        };
    }

    if (participant.is_overdue) {
        return {
            class: "overdue",
            text: `متأخر ${Math.abs(participant.deadline_status)} يوم`,
        };
    }

    if (participant.is_due_soon) {
        return {
            class: "due-soon",
            text: `ينتهي خلال ${participant.deadline_status} يوم`,
        };
    }

    return {
        class: "on-time",
        text: `باقي ${participant.deadline_status} يوم`,
    };
}

function getTaskDeadlineStatus(task) {
    if (!task.due_date) {
        return {
            class: "no-deadline",
            text: "بدون موعد نهائي",
        };
    }

    if (task.is_overdue) {
        return {
            class: "overdue",
            text: "متأخر",
        };
    }

    if (task.is_due_soon) {
        return {
            class: "due-soon",
            text: "ينتهي قريباً",
        };
    }

    return {
        class: "on-time",
        text: "في الموعد",
    };
}

function getApprovalStatus(participant) {
    let html = '<div class="approval-badges">';

    if (participant.is_acknowledged) {
        html +=
            '<span class="approval-badge acknowledged"><i class="fas fa-check"></i> مؤكد</span>';
    }

    if (participant.is_delivered) {
        html +=
            '<span class="approval-badge delivered"><i class="fas fa-paper-plane"></i> مسلم</span>';
    }

    if (participant.administrative_approval) {
        html +=
            '<span class="approval-badge admin-approved"><i class="fas fa-user-shield"></i> معتمد إدارياً</span>';
    }

    if (participant.technical_approval) {
        html +=
            '<span class="approval-badge tech-approved"><i class="fas fa-cogs"></i> معتمد فنياً</span>';
    }

    html += "</div>";
    return html;
}

function getTaskApprovalStatus(task) {
    let html = '<div class="task-approval-badges">';

    if (task.is_approved) {
        html +=
            '<span class="approval-badge approved"><i class="fas fa-check"></i> معتمد</span>';
    }

    if (task.administrative_approval) {
        html +=
            '<span class="approval-badge admin-approved"><i class="fas fa-user-shield"></i> إداري</span>';
    }

    if (task.technical_approval) {
        html +=
            '<span class="approval-badge tech-approved"><i class="fas fa-cogs"></i> فني</span>';
    }

    html += "</div>";
    return html;
}

function getTaskStatusClass(status) {
    switch (status) {
        case "completed":
            return "completed";
        case "in_progress":
            return "in-progress";
        case "paused":
            return "paused";
        case "new":
        default:
            return "new";
    }
}

function getStatusClass(status) {
    switch (status) {
        case "مكتملة":
            return "completed";
        case "قيد التنفيذ":
            return "in-progress";
        case "لم تبدأ":
        default:
            return "not-started";
    }
}

function getStatusIcon(status) {
    switch (status) {
        case "مكتملة":
            return "✅";
        case "قيد التنفيذ":
            return "⚙️";
        case "لم تبدأ":
        default:
            return "📅";
    }
}

// ============================================
// PROJECT SIDEBAR FUNCTIONS
// ============================================

function openProjectSidebar(button) {
    const projectId = button.getAttribute("data-project-id");
    const projectName = button.getAttribute("data-project-name");

    currentProjectId = projectId;

    // Show sidebar
    const sidebar = document.getElementById("projectDetailsSidebar");
    sidebar.classList.add("active");

    // Update header
    document.getElementById("sidebarProjectName").textContent = projectName;

    // Show loading
    document.getElementById("sidebarLoading").style.display = "block";
    document.getElementById("sidebarContent").style.display = "none";
    document.getElementById("tasksSection").style.display = "none";

    // Load project data
    loadProjectDetails(projectId);
}

function closeProjectSidebar() {
    const sidebar = document.getElementById("projectDetailsSidebar");
    sidebar.classList.remove("active");
    currentProjectId = null;
}

function loadProjectDetails(projectId) {
    fetch(`/projects/${projectId}/details-sidebar`)
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                displayProjectDetails(data);
            } else {
                showSidebarError(data.message);
            }
        })
        .catch((error) => {
            console.error("Error loading project details:", error);
            showSidebarError("حدث خطأ في تحميل البيانات");
        });
}

// متغيرات عامة لحفظ البيانات
let allParticipants = [];
let currentSelectedService = null;
let allProjects = [];
let currentFilters = {
    month: null,
    projectCode: null,
    projectStatus: null,
    hasRevisions: false,
};

// Initialize page
document.addEventListener("DOMContentLoaded", function () {
    loadProjectCodes();
    setCurrentMonth();
});

function loadProjectCodes() {
    // جلب أكواد المشاريع من البيانات الموجودة
    const projectRows = document.querySelectorAll(".project-row");
    const projectCodes = new Set();

    projectRows.forEach((row) => {
        const projectCode = row.dataset.projectCode;
        if (projectCode) {
            projectCodes.add(projectCode);
        }
    });

    const projectCodesDatalist = document.getElementById("projectCodesList");
    // مسح الخيارات الموجودة أولاً
    projectCodesDatalist.innerHTML = "";

    // إضافة جميع الأكواد إلى datalist
    projectCodes.forEach((code) => {
        const option = document.createElement("option");
        option.value = code;
        projectCodesDatalist.appendChild(option);
    });
}

function setCurrentMonth() {
    const currentMonth = new Date().getMonth() + 1;
    const monthString = currentMonth.toString().padStart(2, "0");
    document.getElementById("monthFilter").value = monthString;
}

function filterByMonth() {
    const selectedMonth = document.getElementById("monthFilter").value;
    currentFilters.month = selectedMonth;
    applyFilters();
}

function filterByProjectCode() {
    const selectedCode = document.getElementById("projectCodeFilter").value;
    currentFilters.projectCode = selectedCode;
    applyFilters();
}

function filterByProjectStatus() {
    const selectedStatus = document.getElementById("projectStatusFilter").value;
    currentFilters.projectStatus = selectedStatus;
    applyFilters();
}

function filterByHasRevisions() {
    const checkbox = document.getElementById("hasRevisionsFilter");
    currentFilters.hasRevisions = checkbox.checked;
    applyFilters();
}

function clearAllFilters() {
    document.getElementById("monthFilter").value = "";
    document.getElementById("projectCodeFilter").value = "";
    document.getElementById("projectStatusFilter").value = "";
    document.getElementById("hasRevisionsFilter").checked = false;
    currentFilters.month = null;
    currentFilters.projectCode = null;
    currentFilters.projectStatus = null;
    currentFilters.hasRevisions = false;
    applyFilters();
}

function applyFilters() {
    const projectRows = document.querySelectorAll(".project-row");
    let visibleCount = 0;

    projectRows.forEach((row) => {
        let shouldShow = true;

        // فلتر الشهر
        if (currentFilters.month) {
            const projectDate = row.dataset.projectDate;
            if (projectDate) {
                const projectMonth = projectDate.split("-")[1]; // YYYY-MM-DD format
                if (projectMonth !== currentFilters.month) {
                    shouldShow = false;
                }
            }
        }

        // فلتر كود المشروع
        if (currentFilters.projectCode) {
            const projectCode = row.dataset.projectCode;
            const filterCode = currentFilters.projectCode.trim();
            // دعم البحث الجزئي (يبدأ بـ أو يحتوي على)
            if (
                !projectCode ||
                !projectCode.toLowerCase().includes(filterCode.toLowerCase())
            ) {
                shouldShow = false;
            }
        }

        // فلتر حالة المشروع
        if (currentFilters.projectStatus) {
            const projectStatus = row.dataset.projectStatus;
            if (projectStatus !== currentFilters.projectStatus) {
                shouldShow = false;
            }
        }

        // فلتر المشاريع التي بها تعديلات
        if (currentFilters.hasRevisions) {
            const hasRevisions = row.dataset.hasRevisions;
            if (hasRevisions !== "1") {
                shouldShow = false;
            }
        }

        if (shouldShow) {
            row.style.display = "";
            visibleCount++;
        } else {
            row.style.display = "none";
        }
    });

    // تحديث الإحصائيات
    updateFilteredStats(visibleCount);
}

function updateFilteredStats(visibleCount) {
    const statsCards = document.querySelectorAll(".stat-card");
    if (statsCards.length > 0) {
        const totalProjectsCard = statsCards[0];
        const totalNumber = totalProjectsCard.querySelector(".stat-number");
        if (totalNumber) {
            totalNumber.textContent = visibleCount;
        }
    }
}

function displayProjectDetails(data) {
    // Hide loading
    document.getElementById("sidebarLoading").style.display = "none";
    document.getElementById("sidebarContent").style.display = "block";

    // Handle different response formats
    let projectData, servicesData, participantsData, revisionsData;

    if (data.success !== undefined) {
        // Response from ProjectAnalyticsTrait
        projectData = data.project;
        servicesData = data.services;
        participantsData = data.participants;
        revisionsData = data.revisions || [];
    } else {
        // Response from ProjectParticipantsTrait (if used)
        projectData = data.project;
        servicesData = data.services;
        participantsData = data.participants;
        revisionsData = data.revisions || [];
    }

    // Update project name and code
    document.getElementById("sidebarProjectName").textContent =
        projectData.name;
    document.getElementById("sidebarProjectCode").textContent =
        projectData.code || "غير محدد";

    // حفظ المشاركين للفلترة
    allParticipants = participantsData;

    // Display services
    displaySidebarServices(servicesData);

    // Display participants
    displaySidebarParticipants(participantsData);

    // Display revisions
    displaySidebarRevisions(revisionsData);
}

function displaySidebarServices(services) {
    const container = document.getElementById("sidebarServices");

    if (!services || services.length === 0) {
        container.innerHTML = '<p class="empty-message">لا توجد خدمات</p>';
        return;
    }

    let html = "";
    services.forEach((service) => {
        // Handle different service formats
        const serviceId = service.id;
        const serviceName = service.name;
        const serviceStatus = service.status || "لم تبدأ";
        const serviceProgress = service.progress || 0;

        const statusClass = getStatusClass(serviceStatus);
        const isSelected =
            currentSelectedService === serviceId ? "selected" : "";
        html += `
            <div class="service-chip ${statusClass} ${isSelected} clickable-service"
                 onclick="filterParticipantsByService(${serviceId}, '${serviceName.replace(
            /'/g,
            "\\'"
        )}')">
                <span class="service-chip-name">${serviceName}</span>
                <span class="service-chip-progress">${serviceProgress}%</span>
            </div>
        `;
    });

    container.innerHTML = html;
}

function displaySidebarParticipants(participants) {
    const container = document.getElementById("sidebarParticipants");

    if (!participants || participants.length === 0) {
        container.innerHTML = '<p class="empty-message">لا يوجد مشاركين</p>';
        return;
    }

    // عرض زر "عرض الكل" إذا كان هناك فلتر نشط
    let html = "";
    if (currentSelectedService !== null) {
        html += `
            <div class="filter-info">
                <span>عرض ${participants.length} من ${allParticipants.length} مشارك</span>
                <button class="clear-filter-btn" onclick="clearServiceFilter()">
                    <i class="fas fa-times"></i> عرض الكل
                </button>
            </div>
        `;
    }

    participants.forEach((participant) => {
        html += `
            <div class="participant-card" onclick="loadParticipantTasks(${
                participant.user_id
            }, '${participant.user_name}')">
                <div class="participant-header-card">
                    <div class="participant-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="participant-info-card">
                        <h5>${participant.user_name}</h5>
                        <p>${participant.services.length} خدمة</p>
                    </div>
                </div>
                <div class="participant-services">
                    ${participant.services
                        .map(
                            (s) => `
                        <span class="service-badge">${
                            s.service_name || s.name
                        }</span>
                    `
                        )
                        .join("")}
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}

function filterParticipantsByService(serviceId, serviceName) {
    // تحديث الخدمة المختارة
    if (currentSelectedService === serviceId) {
        // إلغاء التحديد - عرض الكل
        currentSelectedService = null;
        displaySidebarParticipants(allParticipants);

        // إعادة رسم الخدمات لإزالة التحديد
        const services = Array.from(
            document.querySelectorAll(".service-chip")
        ).map((chip) => {
            return {
                id: parseInt(
                    chip.onclick
                        .toString()
                        .match(/filterParticipantsByService\((\d+)/)?.[1]
                ),
                name: chip.querySelector(".service-chip-name").textContent,
                progress: parseInt(
                    chip.querySelector(".service-chip-progress").textContent
                ),
                status: chip.classList.contains("status-completed")
                    ? "completed"
                    : chip.classList.contains("status-in-progress")
                    ? "in_progress"
                    : "pending",
            };
        });
        displaySidebarServices(services);
    } else {
        // تحديد خدمة جديدة
        currentSelectedService = serviceId;

        // فلترة المشاركين حسب الخدمة
        const filteredParticipants = allParticipants.filter((participant) =>
            participant.services.some((s) => s.service_id === serviceId)
        );

        // عرض المشاركين المفلترين
        displaySidebarParticipants(filteredParticipants);

        // إعادة رسم الخدمات لتحديث التحديد
        const services = Array.from(
            document.querySelectorAll(".service-chip")
        ).map((chip) => {
            const chipServiceId = parseInt(
                chip.onclick
                    .toString()
                    .match(/filterParticipantsByService\((\d+)/)?.[1]
            );
            return {
                id: chipServiceId,
                name: chip.querySelector(".service-chip-name").textContent,
                progress: parseInt(
                    chip.querySelector(".service-chip-progress").textContent
                ),
                status: chip.classList.contains("status-completed")
                    ? "completed"
                    : chip.classList.contains("status-in-progress")
                    ? "in_progress"
                    : "pending",
            };
        });
        displaySidebarServices(services);
    }

    // إخفاء قسم المهام عند تغيير الفلتر
    document.getElementById("tasksSection").style.display = "none";
}

function clearServiceFilter() {
    currentSelectedService = null;
    displaySidebarParticipants(allParticipants);

    // إعادة رسم الخدمات لإزالة التحديد
    const services = Array.from(document.querySelectorAll(".service-chip")).map(
        (chip) => {
            return {
                id: parseInt(
                    chip.onclick
                        .toString()
                        .match(/filterParticipantsByService\((\d+)/)?.[1]
                ),
                name: chip.querySelector(".service-chip-name").textContent,
                progress: parseInt(
                    chip.querySelector(".service-chip-progress").textContent
                ),
                status: chip.classList.contains("status-completed")
                    ? "completed"
                    : chip.classList.contains("status-in-progress")
                    ? "in_progress"
                    : "pending",
            };
        }
    );
    displaySidebarServices(services);
}

function loadParticipantTasks(userId, userName) {
    if (!currentProjectId) return;

    // Show tasks section with loading
    const tasksSection = document.getElementById("tasksSection");
    tasksSection.style.display = "block";
    document.getElementById("selectedParticipantName").textContent = userName;

    const tasksContainer = document.getElementById("sidebarTasks");
    tasksContainer.innerHTML = `
        <div class="tasks-loading">
            <i class="fas fa-spinner fa-spin"></i>
            <p>جاري تحميل المهام...</p>
        </div>
    `;

    // Scroll to tasks section
    tasksSection.scrollIntoView({ behavior: "smooth", block: "nearest" });

    // Fetch tasks
    fetch(`/projects/${currentProjectId}/participants/${userId}/tasks`)
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                displayParticipantTasks(data.tasks, data.total);
            } else {
                tasksContainer.innerHTML = `
                    <div class="tasks-error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>${data.message}</p>
                    </div>
                `;
            }
        })
        .catch((error) => {
            console.error("Error loading tasks:", error);
            tasksContainer.innerHTML = `
                <div class="tasks-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>حدث خطأ في تحميل المهام</p>
                </div>
            `;
        });
}

function displayParticipantTasks(taskGroups, total) {
    const container = document.getElementById("sidebarTasks");

    if (total === 0) {
        container.innerHTML =
            '<p class="empty-message">لا توجد مهام لهذا المشارك</p>';
        return;
    }

    let html = "";

    // Regular Tasks
    if (taskGroups.regular && taskGroups.regular.length > 0) {
        const lateCount = taskGroups.regular.filter(
            (task) => task.delivery_status === "late"
        ).length;
        const lateBadge =
            lateCount > 0
                ? ` <span class="late-count-badge">${lateCount} متأخرة</span>`
                : "";
        html += `
            <div class="task-group">
                <h5 class="task-group-title">
                    <i class="fas fa-tasks"></i>
                    مهام عادية (${taskGroups.regular.length})${lateBadge}
                </h5>
                <div class="task-list">
                    ${taskGroups.regular
                        .map((task) => createTaskCard(task))
                        .join("")}
                </div>
            </div>
        `;
    }

    // Template Tasks
    if (taskGroups.template && taskGroups.template.length > 0) {
        const lateCount = taskGroups.template.filter(
            (task) => task.delivery_status === "late"
        ).length;
        const lateBadge =
            lateCount > 0
                ? ` <span class="late-count-badge">${lateCount} متأخرة</span>`
                : "";
        html += `
            <div class="task-group">
                <h5 class="task-group-title">
                    <i class="fas fa-file-alt"></i>
                    مهام تمبليت (${taskGroups.template.length})${lateBadge}
                </h5>
                <div class="task-list">
                    ${taskGroups.template
                        .map((task) => createTaskCard(task, true))
                        .join("")}
                </div>
            </div>
        `;
    }

    // Additional Tasks
    if (taskGroups.additional && taskGroups.additional.length > 0) {
        const lateCount = taskGroups.additional.filter(
            (task) => task.delivery_status === "late"
        ).length;
        const lateBadge =
            lateCount > 0
                ? ` <span class="late-count-badge">${lateCount} متأخرة</span>`
                : "";
        html += `
            <div class="task-group">
                <h5 class="task-group-title">
                    <i class="fas fa-plus-circle"></i>
                    مهام إضافية (${taskGroups.additional.length})${lateBadge}
                </h5>
                <div class="task-list">
                    ${taskGroups.additional
                        .map((task) => createTaskCard(task))
                        .join("")}
                </div>
            </div>
        `;
    }

    // Transferred Tasks
    if (taskGroups.transferred && taskGroups.transferred.length > 0) {
        const lateCount = taskGroups.transferred.filter(
            (task) => task.delivery_status === "late"
        ).length;
        const lateBadge =
            lateCount > 0
                ? ` <span class="late-count-badge">${lateCount} متأخرة</span>`
                : "";
        html += `
            <div class="task-group">
                <h5 class="task-group-title">
                    <i class="fas fa-exchange-alt"></i>
                    مهام منقولة (${taskGroups.transferred.length})${lateBadge}
                </h5>
                <div class="task-list">
                    ${taskGroups.transferred
                        .map((task) => createTaskCard(task, false, true))
                        .join("")}
                </div>
            </div>
        `;
    }

    container.innerHTML = html;
}

function createTaskCard(task, isTemplate = false, isTransferred = false) {
    const statusClass = getTaskStatusClass(task.status);
    const workStatusText = task.work_status
        ? getWorkStatusText(task.work_status)
        : null;
    const workStatusClass = task.work_status
        ? getWorkStatusClass(task.work_status)
        : null;

    let badges = "";
    if (isTemplate) {
        badges +=
            '<span class="task-badge template-badge"><i class="fas fa-file-alt"></i> تمبليت</span>';
    }
    if (task.is_additional) {
        badges +=
            '<span class="task-badge additional-badge"><i class="fas fa-plus-circle"></i> إضافية</span>';
    }

    // المهام المنقولة من الشخص (في قسم المنقولة)
    if (isTransferred || task.is_transferred_from) {
        badges +=
            '<span class="task-badge transfer-badge"><i class="fas fa-exchange-alt"></i> منقولة</span>';
    }

    // المهام المنقولة للشخص (تظهر في الإضافية مع badge منقولة من)
    if (task.is_transferred_to && task.transferred_from_user) {
        badges += `<span class="task-badge transferred-from-badge" title="منقولة من ${task.transferred_from_user}">
                      <i class="fas fa-arrow-left"></i> من: ${task.transferred_from_user}
                   </span>`;
    }

    // حالة التسليم
    let deliveryBadge = "";
    if (task.delivery_status === "on_time") {
        deliveryBadge =
            '<span class="delivery-badge on-time"><i class="fas fa-check-circle"></i> تسليم في الموعد</span>';
    } else if (task.delivery_status === "late") {
        const daysText = task.days_late ? ` (${task.days_late} يوم تأخير)` : "";
        const icon = task.completed_date
            ? "fas fa-exclamation-triangle"
            : "fas fa-clock";
        const text = task.completed_date ? "تسليم متأخر" : "مهمة متأخرة";
        deliveryBadge = `<span class="delivery-badge late"><i class="${icon}"></i> ${text}${daysText}</span>`;
    } else if (task.status === "new") {
        // المهمة جديدة - لا تظهر badge للتسليم
        deliveryBadge = "";
    } else {
        // المهمة مبدية أو في تقدم - تظهر حالة التسليم العادية
        deliveryBadge =
            '<span class="delivery-badge in-progress"><i class="fas fa-play-circle"></i> في التقدم</span>';
    }

    // إحصائيات قابلة للضغط
    let statsHtml = "";
    if (task.errors_count > 0 || task.revisions_count > 0) {
        statsHtml = '<div class="task-stats">';
        if (task.errors_count > 0) {
            const taskType = isTemplate ? "template" : "regular";
            statsHtml += `<span class="stat-item error-stat clickable"
                               onclick="showTaskErrors('${taskType}', ${
                task.task_user_id || task.template_task_user_id
            }, '${task.name}')"
                               title="اضغط لعرض تفاصيل الأخطاء">
                               <i class="fas fa-exclamation-circle"></i> ${
                                   task.errors_count
                               } خطأ
                         </span>`;
        }
        if (task.revisions_count > 0) {
            const taskType = isTemplate ? "template" : "regular";
            statsHtml += `<span class="stat-item revision-stat clickable"
                               onclick="showTaskRevisions('${taskType}', ${
                task.task_user_id || task.template_task_user_id
            }, '${task.name}')"
                               title="اضغط لعرض تفاصيل التعديلات">
                               <i class="fas fa-edit"></i> ${
                                   task.revisions_count
                               } تعديل
                         </span>`;
        }
        statsHtml += "</div>";
    }

    // معلومات الوقت (المقدر والفعلي)
    let timeInfo = "";
    if (task.estimated_time || task.actual_time) {
        timeInfo = '<div class="task-time-info">';
        if (task.estimated_time) {
            timeInfo += `<div class="time-item estimated-time">
                            <i class="fas fa-hourglass-start"></i>
                            <span>المقدر: ${task.estimated_time}</span>
                         </div>`;
        }
        if (task.actual_time) {
            timeInfo += `<div class="time-item actual-time">
                            <i class="fas fa-hourglass-end"></i>
                            <span>المستخدم: ${task.actual_time}</span>
                         </div>`;
        }
        timeInfo += "</div>";
    }

    // إضافة class للمهام المتأخرة
    const lateClass = task.delivery_status === "late" ? "late-task" : "";

    return `
        <div class="task-card ${statusClass} ${lateClass}">
            <div class="task-card-header">
                <div class="task-card-title">
                    <i class="fas fa-check-circle"></i>
                    ${task.name}
                </div>
                <div class="task-badges">
                    ${badges}
                </div>
            </div>

            ${
                task.description
                    ? `
                <div class="task-card-description">
                    ${task.description}
                </div>
            `
                    : ""
            }

            ${
                deliveryBadge
                    ? `<div class="task-card-delivery">${deliveryBadge}</div>`
                    : ""
            }

            ${timeInfo}

            ${statsHtml}

            <div class="task-card-footer">
                <div class="task-card-status">
                    <span class="status-badge ${statusClass}">${
        task.status || "جديدة"
    }</span>
                    ${
                        workStatusText
                            ? `<span class="work-status-badge ${workStatusClass}">${workStatusText}</span>`
                            : ""
                    }
                </div>
                ${
                    task.deadline
                        ? `
                    <div class="task-card-deadline">
                        <i class="fas fa-clock"></i>
                        ${formatDate(task.deadline)}
                    </div>
                `
                        : ""
                }
            </div>

            ${
                task.transfer_reason
                    ? `
                <div class="task-card-transfer-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>سبب النقل:</strong> ${task.transfer_reason}
                </div>
            `
                    : ""
            }
        </div>
    `;
}

function getWorkStatusText(status) {
    const statuses = {
        pending: "معلقة",
        in_progress: "قيد التنفيذ",
        completed: "مكتملة",
        review: "مراجعة",
        approved: "معتمدة",
    };
    return statuses[status] || status;
}

function getWorkStatusClass(status) {
    switch (status) {
        case "completed":
        case "approved":
            return "work-completed";
        case "in_progress":
            return "work-in-progress";
        case "review":
            return "work-review";
        case "pending":
        default:
            return "work-pending";
    }
}

function formatDate(dateString) {
    if (!dateString) return "";
    const date = new Date(dateString);
    return date.toLocaleDateString("ar-EG", {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
}

function showSidebarError(message) {
    document.getElementById("sidebarLoading").style.display = "none";
    document.getElementById("sidebarContent").innerHTML = `
        <div class="sidebar-error">
            <i class="fas fa-exclamation-triangle"></i>
            <p>${message}</p>
        </div>
    `;
    document.getElementById("sidebarContent").style.display = "block";
}

// ==================== Task Errors & Revisions ====================

function showTaskErrors(taskType, taskId, taskName) {
    // إنشاء modal للأخطاء
    const modalHtml = `
        <div class="task-details-modal" id="taskErrorsModal">
            <div class="task-details-overlay" onclick="closeTaskDetailsModal()"></div>
            <div class="task-details-content">
                <div class="task-details-header">
                    <h4><i class="fas fa-exclamation-circle"></i> أخطاء المهمة: ${taskName}</h4>
                    <button class="task-details-close" onclick="closeTaskDetailsModal()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="task-details-body">
                    <div class="task-details-loading">
                        <i class="fas fa-spinner fa-spin"></i>
                        <p>جاري تحميل الأخطاء...</p>
                    </div>
                </div>
            </div>
        </div>
    `;

    // إضافة modal للصفحة
    const existingModal = document.getElementById("taskErrorsModal");
    if (existingModal) {
        existingModal.remove();
    }
    document.body.insertAdjacentHTML("beforeend", modalHtml);

    // جلب بيانات الأخطاء
    fetch(`/tasks/${taskType}/${taskId}/errors`)
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                displayTaskErrors(data.errors);
            } else {
                showTaskDetailsError(data.message || "فشل تحميل الأخطاء");
            }
        })
        .catch((error) => {
            console.error("Error loading task errors:", error);
            showTaskDetailsError("حدث خطأ في تحميل الأخطاء");
        });
}

function showTaskRevisions(taskType, taskId, taskName) {
    // إنشاء modal للتعديلات
    const modalHtml = `
        <div class="task-details-modal" id="taskRevisionsModal">
            <div class="task-details-overlay" onclick="closeTaskDetailsModal()"></div>
            <div class="task-details-content">
                <div class="task-details-header">
                    <h4><i class="fas fa-edit"></i> تعديلات المهمة: ${taskName}</h4>
                    <button class="task-details-close" onclick="closeTaskDetailsModal()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="task-details-body">
                    <div class="task-details-loading">
                        <i class="fas fa-spinner fa-spin"></i>
                        <p>جاري تحميل التعديلات...</p>
                    </div>
                </div>
            </div>
        </div>
    `;

    // إضافة modal للصفحة
    const existingModal = document.getElementById("taskRevisionsModal");
    if (existingModal) {
        existingModal.remove();
    }
    document.body.insertAdjacentHTML("beforeend", modalHtml);

    // جلب بيانات التعديلات
    fetch(`/tasks/${taskType}/${taskId}/revisions`)
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                displayTaskRevisions(data.revisions);
            } else {
                showTaskDetailsError(data.message || "فشل تحميل التعديلات");
            }
        })
        .catch((error) => {
            console.error("Error loading task revisions:", error);
            showTaskDetailsError("حدث خطأ في تحميل التعديلات");
        });
}

function displayTaskErrors(errors) {
    const modalBody = document.querySelector(
        ".task-details-modal .task-details-body"
    );

    if (!errors || errors.length === 0) {
        modalBody.innerHTML = `
            <div class="empty-message">
                <i class="fas fa-info-circle"></i>
                <p>لا توجد أخطاء لهذه المهمة</p>
            </div>
        `;
        return;
    }

    let html = '<div class="errors-list">';

    errors.forEach((error) => {
        const errorTypeClass =
            error.error_type === "critical" ? "error-critical" : "error-normal";
        html += `
            <div class="error-item ${errorTypeClass}">
                <div class="error-header">
                    <div class="error-title">
                        <i class="fas fa-exclamation-circle"></i>
                        <h5>${error.title}</h5>
                        <span class="error-type-badge ${errorTypeClass}">${
            error.error_type_text
        }</span>
                    </div>
                    <div class="error-date">${formatDate(
                        error.created_at
                    )}</div>
                </div>

                ${
                    error.description
                        ? `
                    <div class="error-description">
                        <p>${error.description}</p>
                    </div>
                `
                        : ""
                }

                <div class="error-meta">
                    <div class="error-meta-item">
                        <i class="fas fa-tag"></i>
                        <span>التصنيف: ${error.error_category_text}</span>
                    </div>
                    ${
                        error.reporter_name
                            ? `
                        <div class="error-meta-item">
                            <i class="fas fa-user"></i>
                            <span>المُبلغ: ${error.reporter_name}</span>
                        </div>
                    `
                            : ""
                    }
                </div>
            </div>
        `;
    });

    html += "</div>";
    modalBody.innerHTML = html;
}

function displayTaskRevisions(revisions) {
    const modalBody = document.querySelector(
        ".task-details-modal .task-details-body"
    );

    if (!revisions || revisions.length === 0) {
        modalBody.innerHTML = `
            <div class="empty-message">
                <i class="fas fa-info-circle"></i>
                <p>لا توجد تعديلات لهذه المهمة</p>
            </div>
        `;
        return;
    }

    let html = '<div class="revisions-list">';

    revisions.forEach((revision) => {
        const statusClass = getRevisionStatusClass(revision.status);

        html += `
            <div class="revision-item">
                <div class="revision-header">
                    <div class="revision-title">
                        <i class="fas fa-edit"></i>
                        <h5>${revision.title}</h5>
                    </div>
                    <div class="revision-date">${formatDate(
                        revision.created_at
                    )}</div>
                </div>

                ${
                    revision.description
                        ? `
                    <div class="revision-description">
                        <p>${revision.description}</p>
                    </div>
                `
                        : ""
                }

                <div class="revision-badges">
                    <span class="revision-status-badge ${statusClass}">${
            revision.status_text
        }</span>
                    ${
                        revision.revision_source_text
                            ? `
                        <span class="revision-source-badge">${revision.revision_source_text}</span>
                    `
                            : ""
                    }
                </div>

                <div class="revision-meta">
                    ${
                        revision.actual_time
                            ? `
                        <div class="revision-meta-item">
                            <i class="fas fa-clock"></i>
                            <span>الوقت الفعلي: ${revision.actual_time}</span>
                        </div>
                    `
                            : ""
                    }
                    ${
                        revision.creator_name
                            ? `
                        <div class="revision-meta-item">
                            <i class="fas fa-user-plus"></i>
                            <span>المُنشئ: ${revision.creator_name}</span>
                        </div>
                    `
                            : ""
                    }
                    ${
                        revision.assigned_name
                            ? `
                        <div class="revision-meta-item">
                            <i class="fas fa-user-check"></i>
                            <span>المُكلف: ${revision.assigned_name}</span>
                        </div>
                    `
                            : ""
                    }
                    ${
                        revision.responsible_name
                            ? `
                        <div class="revision-meta-item">
                            <i class="fas fa-user-tie"></i>
                            <span>المسؤول: ${revision.responsible_name}</span>
                        </div>
                    `
                            : ""
                    }
                </div>
            </div>
        `;
    });

    html += "</div>";
    modalBody.innerHTML = html;
}

function getRevisionStatusClass(status) {
    switch (status) {
        case "new":
            return "status-new";
        case "in_progress":
            return "status-in-progress";
        case "paused":
            return "status-paused";
        case "completed":
            return "status-completed";
        default:
            return "";
    }
}

function showTaskDetailsError(message) {
    const modalBody = document.querySelector(
        ".task-details-modal .task-details-body"
    );
    modalBody.innerHTML = `
        <div class="task-details-error">
            <i class="fas fa-exclamation-triangle"></i>
            <p>${message}</p>
        </div>
    `;
}

function closeTaskDetailsModal() {
    const modals = document.querySelectorAll(".task-details-modal");
    modals.forEach((modal) => modal.remove());
}

function displaySidebarRevisions(revisions) {
    const container = document.getElementById("sidebarRevisions");

    if (!revisions || revisions.length === 0) {
        container.innerHTML = '<p class="empty-message">لا توجد تعديلات</p>';
        return;
    }

    let html = "";
    revisions.forEach((revision) => {
        // تحديد لون الحالة
        const statusClass = revision.status_color || "secondary";
        const statusText = revision.status_text || "غير محدد";

        // تحديد لون المصدر
        const sourceClass = revision.source_color || "secondary";
        const sourceText = revision.source_text || "غير محدد";

        // أيقونة المصدر
        const sourceIcon = revision.source === "internal" ? "🏢" : "🌐";

        // تحديد لون البطاقة حسب الحالة
        let cardBgColor = "white";
        let cardBorderColor = "#e5e7eb";

        if (revision.status === "completed") {
            cardBgColor = "#f0fdf4";
            cardBorderColor = "#10b981";
        } else if (revision.status === "paused") {
            cardBgColor = "#fef2f2";
            cardBorderColor = "#ef4444";
        } else if (revision.status === "in_progress") {
            cardBgColor = "#eff6ff";
            cardBorderColor = "#3b82f6";
        } else if (revision.status === "new") {
            cardBgColor = "#fffbeb";
            cardBorderColor = "#f59e0b";
        }

        html += `
            <div class="revision-card" style="background: ${cardBgColor}; border: 2px solid ${cardBorderColor}; border-radius: 8px; padding: 0.75rem; margin-bottom: 0.5rem; transition: all 0.2s ease;" onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'; this.style.transform='translateY(-2px)'" onmouseout="this.style.boxShadow='none'; this.style.transform='translateY(0)'">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                    <div style="flex: 1;">
                        <div style="font-weight: 600; color: #1f2937; margin-bottom: 0.25rem; font-size: 0.9rem;">
                            ${revision.title || "بدون عنوان"}
                        </div>
                        ${
                            revision.revision_code
                                ? `<div style="font-size: 0.75rem; color: #6b7280; margin-bottom: 0.25rem;">كود: ${revision.revision_code}</div>`
                                : ""
                        }
                    </div>
                    <div style="display: flex; flex-direction: column; align-items: end; gap: 0.25rem;">
                        <span class="badge badge-${statusClass}" style="font-size: 0.7rem; padding: 0.2rem 0.5rem;">
                            ${statusText}
                        </span>
                        <span class="badge badge-${sourceClass}" style="font-size: 0.7rem; padding: 0.2rem 0.5rem;">
                            ${sourceIcon} ${sourceText}
                        </span>
                    </div>
                </div>

                ${
                    revision.description
                        ? `<div style="font-size: 0.85rem; color: #4b5563; margin-bottom: 0.5rem; line-height: 1.5;">
                    ${
                        revision.description.length > 100
                            ? revision.description.substring(0, 100) + "..."
                            : revision.description
                    }
                </div>`
                        : ""
                }

                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.75rem; color: #6b7280; padding-top: 0.5rem; border-top: 1px solid #f3f4f6;">
                    <div>
                        ${
                            revision.service_name
                                ? `<span style="margin-left: 0.5rem;">📋 ${revision.service_name}</span>`
                                : ""
                        }
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        ${
                            revision.creator_name
                                ? `<span>👤 ${revision.creator_name}</span>`
                                : ""
                        }
                        <span>🕒 ${
                            revision.created_at_diff || revision.created_at
                        }</span>
                    </div>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}

// ===================================
// Revision Guide Sidebar Functions
// ===================================

function openRevisionGuide() {
    const sidebar = document.getElementById("revisionGuideSidebar");
    if (sidebar) {
        sidebar.classList.add("active");
        document.body.style.overflow = "hidden"; // منع Scroll في الخلفية
    }
}

function closeRevisionGuide() {
    const sidebar = document.getElementById("revisionGuideSidebar");
    if (sidebar) {
        sidebar.classList.remove("active");
        document.body.style.overflow = ""; // إرجاع Scroll
    }
}
