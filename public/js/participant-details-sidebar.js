// Participant Details Sidebar - عرض تفاصيل الموظف مع المهام والتعديلات

function showParticipantDetails(userId, serviceId, participantName) {
    if (!currentProjectId) {
        console.error('No project ID available');
        return;
    }

    // فتح الـ sidebar
    const sidebar = document.getElementById('projectSidebar');
    if (sidebar) {
        sidebar.classList.add('active');
    }

    // تحديث العنوان
    document.getElementById('sidebarProjectName').textContent = `تفاصيل: ${participantName}`;
    document.getElementById('sidebarProjectCode').textContent = `الموظف في الخدمة`;

    // إظهار حالة التحميل
    document.getElementById('sidebarLoading').style.display = 'flex';
    document.getElementById('sidebarContent').style.display = 'none';

    // جلب البيانات من API
    fetch(`/projects/${currentProjectId}/services/${serviceId}/participants/${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayParticipantFullDetails(data.data);
            } else {
                showSidebarError(data.message);
            }
        })
        .catch(error => {
            console.error('Error fetching participant details:', error);
            showSidebarError('حدث خطأ في تحميل البيانات');
        });
}

function displayParticipantFullDetails(data) {
    // إخفاء التحميل
    document.getElementById('sidebarLoading').style.display = 'none';
    document.getElementById('sidebarContent').style.display = 'block';

    // عرض معلومات الموظف
    const userInfo = `
        <div class="participant-full-info">
            <div class="info-card">
                <i class="fas fa-user"></i>
                <div>
                    <strong>الموظف:</strong> ${data.user.name}
                    ${data.user.employee_id ? `<span class="employee-id">#${data.user.employee_id}</span>` : ''}
                </div>
            </div>
            <div class="info-card">
                <i class="fas fa-cog"></i>
                <div><strong>الخدمة:</strong> ${data.service.name}</div>
            </div>
            ${data.participation ? `
                <div class="info-card">
                    <i class="fas fa-calendar"></i>
                    <div>
                        <strong>الموعد النهائي:</strong> ${data.participation.deadline || 'غير محدد'}
                    </div>
                </div>
                ${data.participation.delivered ? `
                    <div class="info-card delivered">
                        <i class="fas fa-check-circle"></i>
                        <div><strong>تاريخ التسليم:</strong> ${data.participation.delivered_at}</div>
                    </div>
                ` : ''}
            ` : ''}
        </div>
    `;

    // عرض المهام
    const tasksHTML = displayParticipantTasksList(data.tasks);

    // عرض التعديلات بحسب الأدوار
    const revisionsHTML = displayParticipantRevisions(data.revisions);

    // دمج كل المحتوى
    document.getElementById('sidebarContent').innerHTML = `
        ${userInfo}
        
        <div class="sidebar-section">
            <h4 class="section-title">
                <i class="fas fa-tasks"></i>
                المهام (${data.tasks.length})
            </h4>
            ${tasksHTML}
        </div>

        <div class="sidebar-section">
            <h4 class="section-title">
                <i class="fas fa-edit"></i>
                التعديلات (${data.revisions.all.length})
            </h4>
            ${revisionsHTML}
        </div>
    `;
}

function displayParticipantTasksList(tasks) {
    if (!tasks || tasks.length === 0) {
        return `
            <div class="empty-message">
                <i class="fas fa-inbox"></i>
                <p>لا توجد مهام لهذا الموظف في هذه الخدمة</p>
            </div>
        `;
    }

    let html = '<div class="tasks-grid">';

    tasks.forEach(task => {
        const statusClass = task.status === 'completed' ? 'completed' : 
                          task.status === 'in_progress' ? 'in-progress' : 'pending';
        const typeLabel = task.type === 'template' ? 'قالب' : 'عادية';
        const typeClass = task.type === 'template' ? 'template' : 'regular';

        html += `
            <div class="task-card ${statusClass}">
                <div class="task-header">
                    <div class="task-name">
                        <i class="fas fa-check-square"></i>
                        ${task.name}
                    </div>
                    <span class="task-type-badge ${typeClass}">${typeLabel}</span>
                </div>
                <div class="task-meta">
                    <div class="task-status">
                        <i class="fas fa-circle"></i>
                        ${getTaskStatusText(task.status)}
                    </div>
                    ${task.deadline ? `
                        <div class="task-deadline">
                            <i class="fas fa-calendar"></i>
                            ${task.deadline}
                        </div>
                    ` : ''}
                    ${task.completed_at ? `
                        <div class="task-completed">
                            <i class="fas fa-check"></i>
                            اكتمل: ${task.completed_at}
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
    });

    html += '</div>';
    return html;
}

function displayParticipantRevisions(revisions) {
    if (!revisions || revisions.all.length === 0) {
        return `
            <div class="empty-message">
                <i class="fas fa-inbox"></i>
                <p>لا توجد تعديلات لهذا الموظف في هذه الخدمة</p>
            </div>
        `;
    }

    // تجميع التعديلات بحسب الدور
    const responsible = revisions.responsible || [];
    const executor = revisions.executor || [];
    const reviewer = revisions.reviewer || [];

    let html = '<div class="revisions-container">';

    // التعديلات كمسؤول
    if (responsible.length > 0) {
        html += `
            <div class="revision-group responsible-group">
                <h5 class="group-title">
                    <i class="fas fa-user-tie"></i>
                    مسؤول عنها (${responsible.length})
                </h5>
                <div class="revisions-list">
        `;
        
        responsible.forEach(revision => {
            html += createRevisionCard(revision, 'responsible');
        });
        
        html += '</div></div>';
    }

    // التعديلات كمنفذ
    if (executor.length > 0) {
        html += `
            <div class="revision-group executor-group">
                <h5 class="group-title">
                    <i class="fas fa-tools"></i>
                    منفذ لها (${executor.length})
                </h5>
                <div class="revisions-list">
        `;
        
        executor.forEach(revision => {
            html += createRevisionCard(revision, 'executor');
        });
        
        html += '</div></div>';
    }

    // التعديلات كمراجع
    if (reviewer.length > 0) {
        html += `
            <div class="revision-group reviewer-group">
                <h5 class="group-title">
                    <i class="fas fa-clipboard-check"></i>
                    مراجع لها (${reviewer.length})
                </h5>
                <div class="revisions-list">
        `;
        
        reviewer.forEach(revision => {
            html += createRevisionCard(revision, 'reviewer');
        });
        
        html += '</div></div>';
    }

    html += '</div>';
    return html;
}

function createRevisionCard(revision, roleType) {
    const roleColors = {
        'responsible': 'role-responsible',
        'executor': 'role-executor',
        'reviewer': 'role-reviewer'
    };

    const roleIcons = {
        'responsible': 'fa-user-tie',
        'executor': 'fa-tools',
        'reviewer': 'fa-clipboard-check'
    };

    return `
        <div class="revision-card ${roleColors[roleType]}">
            <div class="revision-header">
                <div class="revision-title">
                    <i class="fas ${roleIcons[roleType]}"></i>
                    ${revision.title}
                </div>
                <span class="revision-role-badge ${roleColors[roleType]}">
                    ${revision.role_text}
                </span>
            </div>
            ${revision.description ? `
                <div class="revision-description">
                    ${revision.description}
                </div>
            ` : ''}
            <div class="revision-team">
                ${revision.responsible_name ? `
                    <span class="team-member">
                        <i class="fas fa-user-tie"></i>
                        مسؤول: ${revision.responsible_name}
                    </span>
                ` : ''}
                ${revision.executor_name ? `
                    <span class="team-member">
                        <i class="fas fa-tools"></i>
                        منفذ: ${revision.executor_name}
                    </span>
                ` : ''}
                ${revision.reviewer_name ? `
                    <span class="team-member">
                        <i class="fas fa-clipboard-check"></i>
                        مراجع: ${revision.reviewer_name}
                    </span>
                ` : ''}
            </div>
            <div class="revision-status">
                ${revision.status}
            </div>
        </div>
    `;
}

function getTaskStatusText(status) {
    const statuses = {
        'pending': 'قيد الانتظار',
        'in_progress': 'جاري التنفيذ',
        'completed': 'مكتمل',
        'on_hold': 'متوقف'
    };
    return statuses[status] || status;
}

function showSidebarError(message) {
    document.getElementById('sidebarLoading').style.display = 'none';
    document.getElementById('sidebarContent').style.display = 'block';
    document.getElementById('sidebarContent').innerHTML = `
        <div class="error-message">
            <i class="fas fa-exclamation-triangle"></i>
            <p>${message}</p>
        </div>
    `;
}

function closeProjectSidebar() {
    const sidebar = document.getElementById('projectSidebar');
    if (sidebar) {
        sidebar.classList.remove('active');
    }
}
