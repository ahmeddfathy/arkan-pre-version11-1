// Task Display Functions

function displayTaskDetails(task) {
    const content = document.getElementById('taskSidebarContent');
    const title = document.getElementById('taskSidebarTitle');
    const subtitle = document.getElementById('taskSidebarSubtitle');
    const badge = document.getElementById('taskSidebarBadge');

    // Update header
    title.textContent = task.name || task.title || 'مهمة بدون عنوان';
    subtitle.textContent = task.project ? task.project.name : 'مشروع غير معروف';

    // Update badge
    const badgeColors = {
        'template': {bg: '#e8f5e8', color: '#2d7d2d', icon: 'fa-layer-group'},
        'regular': {bg: '#e8f0ff', color: '#0066cc', icon: 'fa-tasks'}
    };
    const badgeStyle = badgeColors[task.type] || badgeColors.regular;
    badge.style.background = badgeStyle.bg;
    badge.style.color = badgeStyle.color;
    badge.innerHTML = `<i class="fas ${badgeStyle.icon} me-1"></i>${task.type === 'template' ? 'مهمة قالب' : 'مهمة عادية'}`;

    // Generate deadline HTML
    let deadlineHtml = '';
    if (task.deadline || task.due_date) {
        const deadline = task.deadline || task.due_date;
        const deadlineDate = new Date(deadline);
        const now = new Date();
        const isOverdue = deadlineDate < now && task.status !== 'completed';
        const isDueSoon = deadlineDate > now && (deadlineDate - now) <= 24*60*60*1000 && task.status !== 'completed';

        let badgeClass = 'primary';
        let iconClass = 'calendar-check';
        let statusText = 'في الموعد';

        if (task.status === 'completed') {
            badgeClass = 'success';
            iconClass = 'check-circle';
            statusText = 'مكتملة';
        } else if (isOverdue) {
            badgeClass = 'danger';
            iconClass = 'exclamation-triangle';
            statusText = 'متأخرة';
        } else if (isDueSoon) {
            badgeClass = 'warning';
            iconClass = 'hourglass-half';
            statusText = 'تنتهي قريباً';
        }

        deadlineHtml = `
            <div class="mb-4">
                <label class="form-label fw-semibold text-muted mb-2" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">الموعد النهائي</label>
                <div class="d-flex align-items-center p-3 rounded" style="background: rgba(${badgeClass === 'success' ? '25, 135, 84' : badgeClass === 'danger' ? '220, 53, 69' : badgeClass === 'warning' ? '255, 193, 7' : '13, 110, 253'}, 0.1); border: 1px solid rgba(${badgeClass === 'success' ? '25, 135, 84' : badgeClass === 'danger' ? '220, 53, 69' : badgeClass === 'warning' ? '255, 193, 7' : '13, 110, 253'}, 0.2);">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; background: rgba(${badgeClass === 'success' ? '25, 135, 84' : badgeClass === 'danger' ? '220, 53, 69' : badgeClass === 'warning' ? '255, 193, 7' : '13, 110, 253'}, 0.2);">
                        <i class="fas fa-${iconClass} text-${badgeClass}" style="font-size: 16px;"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-semibold text-dark mb-1" style="font-size: 14px;">
                            ${deadlineDate.toLocaleDateString('ar-EG', {weekday: 'short', month: 'short', day: 'numeric'})} - ${deadlineDate.toLocaleTimeString('ar-EG', {hour: '2-digit', minute: '2-digit'})}
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="status-dot me-2" style="width: 8px; height: 8px; border-radius: 50%; background: var(--bs-${badgeClass});"></div>
                            <small class="text-${badgeClass} fw-semibold" style="font-size: 12px;">${statusText}</small>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    // Define status colors and texts
    const statusColors = {
        'new': 'secondary',
        'in_progress': 'primary',
        'paused': 'warning',
        'completed': 'success',
        'cancelled': 'danger'
    };
    const statusTexts = {
        'new': 'جديدة',
        'in_progress': 'قيد التنفيذ',
        'paused': 'متوقفة مؤقتاً',
        'completed': 'مكتملة',
        'cancelled': 'ملغية'
    };

    content.innerHTML = `
        <div style="padding: 24px; background: #ffffff;">

            <!-- Status Section -->
            <div class="mb-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <label class="form-label fw-semibold text-muted mb-0" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">الحالة</label>
                </div>
                <div class="status-dropdown-wrapper">
                    <div class="d-flex align-items-center p-3 rounded" style="background: rgba(${statusColors[task.status] === 'primary' ? '13, 110, 253' : statusColors[task.status] === 'success' ? '25, 135, 84' : statusColors[task.status] === 'danger' ? '220, 53, 69' : statusColors[task.status] === 'warning' ? '255, 193, 7' : '108, 117, 125'}, 0.1); border: 1px solid rgba(${statusColors[task.status] === 'primary' ? '13, 110, 253' : statusColors[task.status] === 'success' ? '25, 135, 84' : statusColors[task.status] === 'danger' ? '220, 53, 69' : statusColors[task.status] === 'warning' ? '255, 193, 7' : '108, 117, 125'}, 0.2);">
                        <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; background: rgba(${statusColors[task.status] === 'primary' ? '13, 110, 253' : statusColors[task.status] === 'success' ? '25, 135, 84' : statusColors[task.status] === 'danger' ? '220, 53, 69' : statusColors[task.status] === 'warning' ? '255, 193, 7' : '108, 117, 125'}, 0.2);">
                            <i class="fas fa-${task.status === 'in_progress' ? 'play' : task.status === 'completed' ? 'check' : task.status === 'cancelled' ? 'times' : task.status === 'paused' ? 'pause' : 'circle'} text-${statusColors[task.status]}" style="font-size: 16px;"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold text-dark mb-1" style="font-size: 14px;">
                                ${statusTexts[task.status] || task.status}
                            </div>
                            ${task.status === 'new' ? `
                                <div class="d-flex align-items-center">
                                    <div class="status-dot me-2" style="width: 8px; height: 8px; border-radius: 50%; background: #6c757d;"></div>
                                    <small class="text-secondary fw-semibold" style="font-size: 12px;">
                                        ${canUserStartTask(task, window.currentUserId) ? 'جاهزة للبدء - اضغط بدء لتشغيلها' : 'مخصصة لمستخدم آخر'}
                                    </small>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            </div>

            ${deadlineHtml}

            <!-- Description Section -->
            ${task.description ? `
                <div class="mb-4">
                    <label class="form-label fw-semibold text-muted mb-2" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">الوصف</label>
                    <div class="p-3 rounded" style="background: #f8f9fa; border: 1px solid #e9ecef; color: #495057; line-height: 1.5;">
                        ${task.description}
                    </div>
                </div>
            ` : ''}

            <!-- Time Tracking Section -->
            <div class="mb-4">
                <label class="form-label fw-semibold text-muted mb-2" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">تتبع الوقت</label>
                <div class="row g-2">
                    ${task.estimated_hours !== undefined ? `
                        <div class="col-6">
                            <div class="p-3 rounded text-center" style="background: #f8f9fa; border: 1px solid #e9ecef;">
                                <div class="fw-bold text-primary mb-1" style="font-size: 18px; font-family: monospace;">
                                    ${task.estimated_hours || 0}:${(task.estimated_minutes || 0).toString().padStart(2, '0')}
                                </div>
                                <small class="text-muted" style="font-size: 11px;">الوقت المقدر</small>
                            </div>
                        </div>
                    ` : ''}
                    <div class="col-6">
                        <div class="p-3 rounded text-center" style="background: #e8f5e8; border: 1px solid #c3e6c3;">
                            <div class="fw-bold text-success mb-1" style="font-size: 18px; font-family: monospace;">
                                ${Math.floor((task.actual_minutes || 0) / 60)}:${((task.actual_minutes || 0) % 60).toString().padStart(2, '0')}
                            </div>
                            <small class="text-muted" style="font-size: 11px;">الوقت الفعلي</small>
                        </div>
                    </div>
                    ${task.status === 'in_progress' ? `
                        <div class="col-12 mt-3">
                            <div class="p-3 rounded text-center" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: 1px solid #5a67d8;">
                                <div class="d-flex align-items-center justify-content-center mb-2">
                                    <i class="fas fa-stopwatch text-white me-2" style="font-size: 14px;"></i>
                                    <small class="text-white" style="font-size: 11px; font-weight: 500;">المؤقت النشط</small>
                                </div>
                                <div class="fw-bold text-white mb-1" style="font-size: 24px; font-family: monospace;" id="sidebar-timer-${task.id}">
                                    00:00:00
                                </div>
                                <div class="d-flex align-items-center justify-content-center">
                                    <div class="spinner-grow text-white" style="width: 8px; height: 8px; animation-duration: 1.5s;" role="status">
                                        <span class="visually-hidden">جاري العمل...</span>
                                    </div>
                                    <small class="text-white ms-2" style="font-size: 10px; opacity: 0.9;">جاري العمل</small>
                                </div>
                            </div>
                        </div>
                    ` : task.status === 'new' ? `
                        <div class="col-12 mt-3">
                            <div class="p-3 rounded text-center" style="background: linear-gradient(135deg, #6c757d 0%, #495057 100%); border: 1px solid #6c757d;">
                                <div class="d-flex align-items-center justify-content-center mb-2">
                                    <i class="fas fa-play text-white me-2" style="font-size: 14px;"></i>
                                    <small class="text-white" style="font-size: 11px; font-weight: 500;">جاهز للبدء</small>
                                </div>
                                <div class="fw-bold text-white mb-1" style="font-size: 18px; font-family: monospace;">
                                    00:00:00
                                </div>
                                <div class="d-flex align-items-center justify-content-center">
                                    <i class="fas fa-clock text-white me-1" style="font-size: 8px; opacity: 0.9;"></i>
                                    <small class="text-white" style="font-size: 10px; opacity: 0.9;">اضغط بدء لتشغيل المؤقت</small>
                                </div>
                            </div>
                        </div>
                    ` : task.status === 'paused' ? `
                        <div class="col-12 mt-3">
                            <div class="p-3 rounded text-center" style="background: linear-gradient(135deg, #fd7e14 0%, #ffc107 100%); border: 1px solid #fd7e14;">
                                <div class="d-flex align-items-center justify-content-center mb-2">
                                    <i class="fas fa-pause text-white me-2" style="font-size: 14px;"></i>
                                    <small class="text-white" style="font-size: 11px; font-weight: 500;">متوقف مؤقتاً</small>
                                </div>
                                <div class="fw-bold text-white mb-1" style="font-size: 18px; font-family: monospace;">
                                    ${Math.floor((task.actual_minutes || 0) / 60)}:${((task.actual_minutes || 0) % 60).toString().padStart(2, '0')}:00
                                </div>
                                <div class="d-flex align-items-center justify-content-center">
                                    <i class="fas fa-play text-white me-1" style="font-size: 8px; opacity: 0.9;"></i>
                                    <small class="text-white" style="font-size: 10px; opacity: 0.9;">اضغط استئناف لمتابعة العمل</small>
                                </div>
                            </div>
                        </div>
                    ` : ''}
                </div>
            </div>

            <!-- Assignee Section -->
            ${task.user ? `
                <div class="mb-4">
                    <label class="form-label fw-semibold text-muted mb-2" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">المُعين للمهمة</label>
                    <div class="d-flex align-items-center p-3 rounded" style="background: #f8f9fa; border: 1px solid #e9ecef;">
                        <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <span class="text-white fw-bold" style="font-size: 14px;">${task.user.name.charAt(0).toUpperCase()}</span>
                        </div>
                        <div>
                            <div class="fw-semibold text-dark mb-1" style="font-size: 14px;">${task.user.name}</div>
                            <small class="text-muted" style="font-size: 12px;">${task.user.email}</small>
                        </div>
                    </div>
                </div>
            ` : ''}

            <!-- Project & Service Info -->
            <div class="mb-4">
                <label class="form-label fw-semibold text-muted mb-2" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">معلومات إضافية</label>
                <div class="p-3 rounded" style="background: #f8f9fa; border: 1px solid #e9ecef;">
                    ${task.project ? `
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-folder text-primary me-2" style="font-size: 14px;"></i>
                            <span class="fw-semibold text-dark" style="font-size: 13px;">${task.project.name}</span>
                        </div>
                    ` : ''}
                    ${task.service ? `
                        <div class="d-flex align-items-center">
                            <i class="fas fa-cogs text-info me-2" style="font-size: 14px;"></i>
                            <span class="text-muted" style="font-size: 13px;">${task.service.name}</span>
                        </div>
                    ` : ''}
                </div>
            </div>

            <!-- Task Items Section -->
            <div class="task-items-section mb-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <label class="form-label fw-semibold text-muted mb-0" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-list-check me-1"></i>بنود المهمة
                    </label>
                    ${(task.created_by && task.created_by == window.currentUserId) || (task.created_by_user && task.created_by_user.id == window.currentUserId) ? `
                        <button class="btn btn-sm btn-outline-primary" onclick="showAddItemForm('${task.type}', '${task.id}')" style="font-size: 11px; padding: 4px 8px;">
                            <i class="fas fa-plus me-1"></i>إضافة بند
                        </button>
                    ` : ''}
                </div>

                <div id="taskItemsContainer" class="task-items-container">
                    <div class="text-center py-3">
                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                            <span class="visually-hidden">جاري تحميل البنود...</span>
                        </div>
                        <p class="mt-2 text-muted mb-0" style="font-size: 12px;">جاري تحميل البنود...</p>
                    </div>
                </div>

                <!-- Add Item Form (Hidden by default) -->
                <div id="addItemForm" class="add-item-form mt-3" style="display: none;">
                    <div class="border rounded p-3" style="background: #f8f9fa;">
                        <div class="mb-2">
                            <label class="form-label mb-1" style="font-size: 12px; font-weight: 600;">عنوان البند</label>
                            <input type="text" id="itemTitle" class="form-control" placeholder="أدخل عنوان البند..." style="font-size: 13px;">
                        </div>
                        <div class="mb-2">
                            <label class="form-label mb-1" style="font-size: 12px; font-weight: 600;">وصف البند (اختياري)</label>
                            <textarea id="itemDescription" class="form-control" rows="2" placeholder="أدخل وصف البند..." style="font-size: 13px; resize: none;"></textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary btn-sm" onclick="saveItem()" style="font-size: 11px;">
                                <i class="fas fa-save me-1"></i>حفظ
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" onclick="hideAddItemForm()" style="font-size: 11px;">
                                إلغاء
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes Section - Only show for assigned users -->
            ${canUserStartTask(task, window.currentUserId) || (task.user && task.user.id == window.currentUserId) ? `
                <div class="notes-section mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <label class="form-label fw-semibold text-muted mb-0" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">
                            <i class="fas fa-sticky-note me-1"></i>ملاحظاتي
                        </label>
                        <button class="btn btn-sm btn-outline-primary" onclick="showAddNoteForm('${task.type}', '${task.pivot_id || task.id}')" style="font-size: 11px; padding: 4px 8px;">
                            <i class="fas fa-plus me-1"></i>إضافة ملاحظة
                        </button>
                    </div>

                    <div id="notesContainer" class="notes-container">
                        <div class="text-center py-3">
                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                <span class="visually-hidden">جاري تحميل الملاحظات...</span>
                            </div>
                            <p class="mt-2 text-muted mb-0" style="font-size: 12px;">جاري تحميل الملاحظات...</p>
                        </div>
                    </div>

                    <!-- Add Note Form (Hidden by default) -->
                    <div id="addNoteForm" class="add-note-form mt-3" style="display: none;">
                        <div class="border rounded p-3" style="background: #f8f9fa;">
                            <div class="mb-2">
                                <label class="form-label mb-1" style="font-size: 12px; font-weight: 600;">ملاحظة جديدة</label>
                                <textarea id="noteContent" class="form-control" rows="3" placeholder="اكتب ملاحظتك هنا..." style="font-size: 13px; resize: none;"></textarea>
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-primary btn-sm" onclick="saveNote()" style="font-size: 11px;">
                                    <i class="fas fa-save me-1"></i>حفظ
                                </button>
                                <button class="btn btn-outline-secondary btn-sm" onclick="hideAddNoteForm()" style="font-size: 11px;">
                                    إلغاء
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            ` : ''}

            <!-- Task Revisions Section -->
            <div class="revisions-section mb-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <label class="form-label fw-semibold text-muted mb-0" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-history me-1"></i>تعديلات المهمة
                    </label>
                    ${window.location.pathname.includes('/tasks') ? `
                        <button class="btn btn-sm btn-outline-success" onclick="showAddRevisionForm('${task.type}', '${task.pivot_id || task.id}', '${task.task_user_id || ''}')" style="font-size: 11px; padding: 4px 8px;">
                            <i class="fas fa-plus me-1"></i>إضافة تعديل
                        </button>
                    ` : '<!-- زر إضافة التعديل متاح فقط في صفحة المهام -->'}
                </div>

                <div id="revisionsContainer" class="revisions-container">
                    <div class="text-center py-3">
                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                            <span class="visually-hidden">جاري تحميل التعديلات...</span>
                        </div>
                        <p class="mt-2 text-muted mb-0" style="font-size: 12px;">جاري تحميل التعديلات...</p>
                    </div>
                </div>

                <!-- Add Revision Form (Hidden by default) -->
                <div id="addRevisionForm" class="add-revision-form mt-3" style="display: none;">
                    <div class="border rounded p-3" style="background: #f8f9fa;">
                        <div class="mb-2">
                            <label class="form-label mb-1" style="font-size: 12px; font-weight: 600;">مصدر التعديل</label>
                            <select id="revisionSource" class="form-control" style="font-size: 13px;">
                                <option value="internal">تعديل داخلي (من الفريق)</option>
                                <option value="external">تعديل خارجي (من العميل)</option>
                            </select>
                            <small class="text-muted" style="font-size: 11px;">حدد ما إذا كان التعديل من الفريق الداخلي أم من العميل</small>
                        </div>

                        <!-- المسؤول (اللي غلط) - مقفول -->
                        <div class="mb-2">
                            <label class="form-label mb-1" style="font-size: 12px; font-weight: 600;">
                                <span class="text-danger">⚠️ المسؤول</span>
                                <span class="text-muted" style="font-size: 11px;">(اللي غلط وسبب التعديل)</span>
                            </label>
                            <input type="text" id="taskRevisionResponsibleUser" class="form-control" style="font-size: 13px;" readonly>
                            <small class="text-muted" style="font-size: 11px;">المسند إليه المهمة (مقفول - هو المسؤول عن الخطأ)</small>
                        </div>

                        <!-- المنفذ (اللي هيصلح) -->
                        <div class="mb-2">
                            <label class="form-label mb-1" style="font-size: 12px; font-weight: 600;">
                                <span class="text-primary">🔨 المنفذ</span>
                                <span class="text-muted" style="font-size: 11px;">(اللي هيصلح الغلط)</span>
                            </label>
                            <select id="taskRevisionExecutorUser" class="form-control" style="font-size: 13px;">
                                <option value="">-- اختر من سينفذ التعديل --</option>
                            </select>
                            <small class="text-muted" style="font-size: 11px;">الشخص اللي هيصلح الخطأ (يمكن يكون نفس المسند إليه أو شخص آخر من نفس الـ Role)</small>
                        </div>

                        <!-- ملاحظات المسؤولية -->
                        <div class="mb-2">
                            <label class="form-label mb-1" style="font-size: 12px; font-weight: 600;">
                                📝 سبب التعديل
                                <span class="text-muted">(اختياري)</span>
                            </label>
                            <textarea id="taskRevisionResponsibilityNotes" class="form-control" rows="2" placeholder="اذكر سبب التعديل والخطأ الذي حدث..." style="font-size: 13px; resize: none;" maxlength="2000"></textarea>
                            <small class="text-muted" style="font-size: 11px;">توثيق سبب الخطأ الذي أدى للتعديل</small>
                        </div>

                        <div class="mb-2">
                            <label class="form-label mb-1" style="font-size: 12px; font-weight: 600;">عنوان التعديل</label>
                            <input type="text" id="revisionTitle" class="form-control" placeholder="عنوان التعديل..." style="font-size: 13px;" maxlength="255">
                        </div>
                        <div class="mb-2">
                            <label class="form-label mb-1" style="font-size: 12px; font-weight: 600;">وصف التعديل</label>
                            <textarea id="revisionDescription" class="form-control" rows="3" placeholder="اكتب وصف التعديل هنا..." style="font-size: 13px; resize: none;" maxlength="5000"></textarea>
                        </div>
                        <div class="mb-2">
                            <label class="form-label mb-1" style="font-size: 12px; font-weight: 600;">ملاحظات إضافية <span class="text-muted">(اختياري)</span></label>
                            <textarea id="revisionNotes" class="form-control" rows="2" placeholder="أي ملاحظات إضافية..." style="font-size: 13px; resize: none;" maxlength="2000"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label mb-1" style="font-size: 12px; font-weight: 600;">المرفق <span class="text-muted">(اختياري)</span></label>

                            <!-- Attachment Type Selection -->
                            <div class="btn-group w-100 mb-2" role="group">
                                <input type="radio" class="btn-check" name="revisionAttachmentType" id="revisionAttachmentTypeFile" value="file" checked onclick="toggleRevisionAttachmentType('file')">
                                <label class="btn btn-outline-primary btn-sm" for="revisionAttachmentTypeFile" style="font-size: 12px;">
                                    <i class="fas fa-file-upload me-1"></i>رفع ملف
                                </label>

                                <input type="radio" class="btn-check" name="revisionAttachmentType" id="revisionAttachmentTypeLink" value="link" onclick="toggleRevisionAttachmentType('link')">
                                <label class="btn btn-outline-primary btn-sm" for="revisionAttachmentTypeLink" style="font-size: 12px;">
                                    <i class="fas fa-link me-1"></i>إضافة لينك
                                </label>
                            </div>

                            <!-- File Upload Option -->
                            <div id="revisionFileUploadContainer">
                                <input type="file" id="revisionAttachment" class="form-control" style="font-size: 13px;" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip,.rar">
                                <small class="text-muted" style="font-size: 11px;">الحد الأقصى: 10 ميجابايت</small>
                            </div>

                            <!-- Link Input Option (Hidden by default) -->
                            <div id="revisionLinkInputContainer" style="display: none;">
                                <input type="url" id="revisionAttachmentLink" class="form-control" placeholder="https://example.com/file.pdf" style="font-size: 13px;">
                                <small class="text-muted" style="font-size: 11px;">أدخل رابط المرفق (يجب أن يبدأ بـ http:// أو https://)</small>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-success btn-sm" onclick="saveRevision()" style="font-size: 11px;">
                                <i class="fas fa-save me-1"></i>حفظ التعديل
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" onclick="hideAddRevisionForm()" style="font-size: 11px;">
                                إلغاء
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attachments Section for Standalone Tasks -->
            ${!task.project ? `
                <div class="mb-4" id="attachmentsSection">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <label class="form-label fw-semibold text-muted mb-0" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">
                            <i class="fas fa-paperclip me-1"></i>المرفقات
                        </label>
                        <span class="badge bg-light text-dark task-attachments-badge" style="font-size: 10px;">
                            <span class="task-attachments-count">0</span> ملف
                        </span>
                    </div>

                    ${!task.is_unassigned ? `
                        <!-- Upload Area -->
                        <div class="mb-3 p-3 rounded border-2 border-dashed text-center"
                             id="attachmentDropZone"
                             style="background: #f8f9fa; border-color: #dee2e6; cursor: pointer; transition: all 0.3s ease;">
                            <i class="fas fa-cloud-upload-alt text-muted mb-2" style="font-size: 24px;"></i>
                            <div class="fw-semibold text-dark mb-1" style="font-size: 13px;">اسحب الملفات هنا أو اضغط للاختيار</div>
                            <small class="text-muted" style="font-size: 11px;">يدعم جميع أنواع الملفات</small>
                            <input type="file" id="attachmentFileInput" multiple style="display: none;">
                        </div>

                        <!-- Upload Progress -->
                        <div id="uploadProgressArea" style="display: none;">
                            <div class="upload-queue"></div>
                        </div>
                    ` : ''}

                    <!-- Existing Attachments -->
                    <div id="attachmentsList">
                        <div class="text-center py-3">
                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                <span class="visually-hidden">جاري تحميل المرفقات...</span>
                            </div>
                            <p class="mt-2 text-muted mb-0" style="font-size: 12px;">جاري تحميل المرفقات...</p>
                        </div>
                    </div>
                </div>
            ` : ''}

            <!-- Action Buttons -->
            <div class="d-flex gap-2 pt-3" style="border-top: 1px solid #e9ecef;">
                ${task.is_unassigned ? `
                    <div class="alert alert-warning py-2 px-3 mb-0" style="font-size: 12px; border-radius: 6px;">
                        <i class="fas fa-info-circle me-1"></i>
                        هذه المهمة غير مُعيَّنة لأي مستخدم ولا يمكن العمل عليها
                    </div>
                ` : task.status === 'new' && canUserStartTask(task, window.currentUserId) ? `
                    <button class="btn btn-primary px-4 py-2" onclick="startTask(event, '${task.type}', '${task.task_user_id || task.id}')" style="border-radius: 6px; font-weight: 500; font-size: 13px;">
                        <i class="fas fa-play me-1"></i>
                        بدء المهمة
                    </button>
                ` : task.status === 'new' && !canUserStartTask(task, window.currentUserId) ? `
                    <div class="alert alert-info py-2 px-3 mb-0" style="font-size: 12px; border-radius: 6px;">
                        <i class="fas fa-info-circle me-1"></i>
                        ${task.user ? `هذه المهمة مخصصة لـ ${task.user.name}` : 'هذه المهمة غير مخصصة لك'}
                    </div>
                ` : task.status === 'paused' && canUserStartTask(task, window.currentUserId) ? `
                    <button class="btn btn-warning px-4 py-2" onclick="resumeTask(event, '${task.type}', '${task.task_user_id || task.id}')" style="border-radius: 6px; font-weight: 500; font-size: 13px;">
                        <i class="fas fa-play me-1"></i>
                        استئناف المهمة
                    </button>
                ` : task.status === 'paused' && !canUserStartTask(task, window.currentUserId) ? `
                    <div class="alert alert-warning py-2 px-3 mb-0" style="font-size: 12px; border-radius: 6px;">
                        <i class="fas fa-pause-circle me-1"></i>
                        ${task.user ? `هذه المهمة متوقفة ومخصصة لـ ${task.user.name}` : 'هذه المهمة متوقفة وغير مخصصة لك'}
                    </div>
                ` : ''}

                <button class="btn btn-outline-secondary px-4 py-2" onclick="closeTaskSidebar()" style="border-radius: 6px; font-weight: 500; font-size: 13px;">
                    إغلاق
                </button>
            </div>
        </div>
    `;

    // Start sidebar timer if task is in progress
    if (task.status === 'in_progress') {
        startSidebarTimer(task);
    }

    // Load task items (only if items container exists)
    setTimeout(() => {
        const itemsContainer = document.getElementById('taskItemsContainer');
        if (itemsContainer) {
            // تحديد نوع المستخدم
            const isTaskCreator = (task.created_by && task.created_by == window.currentUserId) ||
                                 (task.created_by_user && task.created_by_user.id == window.currentUserId);
            const isTaskAssignee = (task.user && task.user.id == window.currentUserId) ||
                                  canUserStartTask(task, window.currentUserId);

            let taskId, userType;

            if (isTaskCreator) {
                // منشئ المهمة: يحمل البنود الأساسية من المهمة
                taskId = task.id;
                userType = 'creator';
            } else if (isTaskAssignee) {
                // صاحب المهمة: يحمل البنود مع حالاتها
                taskId = task.pivot_id || task.task_user_id || task.id;
                userType = 'assignee';
            } else {
                // مستخدم عادي: يحمل البنود الأساسية فقط
                taskId = task.id;
                userType = 'viewer';
            }

            console.log('🔍 Loading task items for:', {
                taskType: task.type,
                taskId,
                userType,
                isTaskCreator,
                isTaskAssignee,
                taskData: task
            });
            loadTaskItems(task.type, taskId, userType);
        }
    }, 100); // تأخير صغير لضمان إنشاء العناصر

    // Load task notes (only if notes container exists)
    setTimeout(() => {
        const notesContainer = document.getElementById('notesContainer');
        if (notesContainer) {
            loadTaskNotes(task.type, task.pivot_id || task.id);
        }
    }, 100); // تأخير صغير لضمان إنشاء العناصر

    // Load task revisions (only if revisions container exists)
    setTimeout(() => {
        const revisionsContainer = document.getElementById('revisionsContainer');
        if (revisionsContainer) {
            loadTaskRevisions(task.type, task.pivot_id || task.id, task.task_user_id);
        }
    }, 100); // تأخير صغير لضمان إنشاء العناصر

    // Load task attachments if it's a standalone task
    if (!task.project && !task.is_unassigned) {
        loadTaskAttachments(task.pivot_id || task.id);
        initializeAttachmentHandlers(task.pivot_id || task.id);
    } else if (!task.project && task.is_unassigned) {
        // Load attachments anyway for unassigned tasks to show the appropriate message
        loadTaskAttachments(task.pivot_id || task.id);
    }
}

/**
 * Utility functions
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDateTime(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);

    if (diffInSeconds < 60) {
        return 'الآن';
    } else if (diffInSeconds < 3600) {
        const minutes = Math.floor(diffInSeconds / 60);
        return `منذ ${minutes} دقيقة`;
    } else if (diffInSeconds < 86400) {
        const hours = Math.floor(diffInSeconds / 3600);
        return `منذ ${hours} ساعة`;
    } else {
        return date.toLocaleDateString('ar-EG', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
}
