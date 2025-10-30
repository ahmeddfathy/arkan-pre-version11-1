// ====================================
// 🎯 Add Revision Sidebar Functions
// ====================================

// متغير لحفظ ID التعديل قيد التعديل (null = إضافة جديدة)
let editingRevisionId = null;

/**
 * فتح sidebar إضافة تعديل
 */
function showAddRevisionModal() {
    editingRevisionId = null; // Reset editing mode
    window.selectedReviewers = []; // ✅ تنظيف المراجعين

    const sidebar = document.getElementById('addRevisionSidebar');
    const overlay = document.getElementById('addRevisionOverlay');

    if (!sidebar || !overlay) {
        console.error('Sidebar elements not found');
        return;
    }

    // Reset form
    document.getElementById('addRevisionForm').reset();
    document.getElementById('newRevisionType').value = '';
    document.getElementById('projectSelectContainer').classList.add('d-none');
    document.getElementById('attachmentTypeOptions').style.display = 'none';
    document.getElementById('newFileContainer').style.display = 'none';
    document.getElementById('newLinkContainer').style.display = 'none';

    // ✅ إعادة تعيين قائمة المراجعين
    const reviewersList = document.getElementById('reviewersList');
    const noReviewersMsg = document.getElementById('noReviewersMsg');
    if (reviewersList && noReviewersMsg) {
        reviewersList.innerHTML = '';
        reviewersList.appendChild(noReviewersMsg);
        noReviewersMsg.style.display = 'block';
    }

    // Show overlay
    overlay.style.visibility = 'visible';
    overlay.style.opacity = '1';

    // Show sidebar
    sidebar.style.right = '0';

    // Prevent body scroll
    document.body.style.overflow = 'hidden';
}

/**
 * إغلاق sidebar إضافة تعديل
 */
function closeAddRevisionSidebar() {
    const sidebar = document.getElementById('addRevisionSidebar');
    const overlay = document.getElementById('addRevisionOverlay');

    if (!sidebar || !overlay) return;

    // Reset editing mode
    editingRevisionId = null;

    // ✅ تنظيف المراجعين
    window.selectedReviewers = [];

    // Reset sidebar title and button
    const sidebarTitle = sidebar.querySelector('h5');
    if (sidebarTitle) {
        sidebarTitle.innerHTML = '<i class="fas fa-plus me-2"></i>إضافة تعديل جديد';
    }

    const saveButton = sidebar.querySelector('button[onclick*="saveNewRevision"]');
    if (saveButton) {
        saveButton.innerHTML = '<i class="fas fa-save me-1"></i>حفظ التعديل';
    }

    // Hide sidebar
    sidebar.style.right = '-600px';

    // Hide overlay
    overlay.style.opacity = '0';
    setTimeout(() => {
        overlay.style.visibility = 'hidden';
    }, 300);

    // Allow body scroll
    document.body.style.overflow = 'auto';
}

/**
 * Toggle بين أنواع التعديل
 */
function toggleRevisionTypeOptions() {
    const type = document.getElementById('newRevisionType').value;
    const projectContainer = document.getElementById('projectSelectContainer');
    const responsibilitySection = document.getElementById('responsibilitySection');
    const attachmentTypeOptions = document.getElementById('attachmentTypeOptions');
    const fileContainer = document.getElementById('newFileContainer');
    const linkContainer = document.getElementById('newLinkContainer');

    if (type === 'project') {
        // تعديل مشروع: رابط فقط
        projectContainer.classList.remove('d-none');
        document.getElementById('newRevisionProjectId').required = true;

        // إخفاء خيارات نوع المرفق وإظهار حقل الرابط فقط
        attachmentTypeOptions.style.display = 'none';
        fileContainer.style.display = 'none';
        linkContainer.style.display = 'block';

        // تنظيف حقل الملف
        document.getElementById('newRevisionAttachment').value = '';

    } else {
        // لم يتم الاختيار بعد
        projectContainer.classList.add('d-none');
        responsibilitySection.classList.add('d-none');
        attachmentTypeOptions.style.display = 'none';
        fileContainer.style.display = 'none';
        linkContainer.style.display = 'none';
    }
}

/**
 * تحميل مشاركين المشروع بعد اختيار المشروع
 */
async function loadProjectParticipantsForRevision() {
    const projectId = document.getElementById('newRevisionProjectId').value;
    const responsibilitySection = document.getElementById('responsibilitySection');

    if (!projectId) {
        responsibilitySection.classList.add('d-none');
        return;
    }

    try {
        // إظهار قسم المسؤوليات
        responsibilitySection.classList.remove('d-none');

        // جلب المشاركين في المشروع
        const projectResponse = await fetch(`/projects/${projectId}/participants`, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        });

        const projectResult = await projectResponse.json();

        // جلب جميع الموظفين من كل الأقسام
        const allUsersResponse = await fetch('/users/all', {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        });

        const allUsersResult = await allUsersResponse.json();

        if (projectResult.success && allUsersResult.success) {
            const projectParticipantIds = projectResult.participants.map(p => p.id);
            const allUsers = allUsersResult.users || [];

            // ترتيب المستخدمين: اللي في المشروع أولاً
            const usersInProject = allUsers.filter(user => projectParticipantIds.includes(user.id));
            const usersNotInProject = allUsers.filter(user => !projectParticipantIds.includes(user.id));
            const sortedUsers = [...usersInProject, ...usersNotInProject];

            // ملء datalist المنفذ
            const executorDatalist = document.getElementById('executorUsersList');
            executorDatalist.innerHTML = '';

            sortedUsers.forEach(user => {
                const option = document.createElement('option');
                const isInProject = projectParticipantIds.includes(user.id);
                option.value = user.name + (isInProject ? ' ✅ من المشروع' : '');
                option.setAttribute('data-user-id', user.id);
                option.setAttribute('data-user-name', user.name);
                option.setAttribute('data-in-project', isInProject);
                executorDatalist.appendChild(option);
            });

            // تخزين البيانات عالمياً للاستخدام في دوال التحديد
            window.allUsersForRevision = allUsers;
            window.projectParticipantIds = projectParticipantIds;

            // جلب المسؤول والمراجعين (مع فلترة حسب الـ role)
            await loadResponsibleUsersForRevision(projectId);
            await loadReviewerUsersForRevision(projectId, allUsers, projectParticipantIds);
        }
    } catch (error) {
        console.error('Error loading project participants:', error);
    }
}

/**
 * معالجة اختيار المنفذ من datalist
 */
function handleExecutorSelection() {
    const searchInput = document.getElementById('newExecutorUserSearch');
    const hiddenInput = document.getElementById('newExecutorUserId');

    if (!searchInput || !hiddenInput) return;

    const inputValue = searchInput.value;

    // إزالة العلامة إذا كانت موجودة للبحث عن الاسم الصحيح
    const searchName = inputValue.replace(' ✅ من المشروع', '').trim();

    // البحث في المستخدمين المخزنين
    if (window.allUsersForRevision) {
        const selectedUser = window.allUsersForRevision.find(user => user.name === searchName);

        if (selectedUser) {
            hiddenInput.value = selectedUser.id;
        } else {
            hiddenInput.value = '';
        }
    }
}

/**
 * معالجة اختيار المسؤول من datalist
 */
function handleResponsibleSelection() {
    const searchInput = document.getElementById('newResponsibleUserSearch');
    const hiddenInput = document.getElementById('newResponsibleUserId');

    if (!searchInput || !hiddenInput) return;

    const inputValue = searchInput.value;

    // إزالة العلامة إذا كانت موجودة للبحث عن الاسم الصحيح
    const searchName = inputValue.replace(' ✅ من المشروع', '').trim();

    // البحث في المستخدمين المخزنين
    if (window.allUsersForRevision) {
        const selectedUser = window.allUsersForRevision.find(user => user.name === searchName);

        if (selectedUser) {
            hiddenInput.value = selectedUser.id;
        } else {
            hiddenInput.value = '';
        }
    }
}

/**
 * معالجة اختيار المراجع من datalist - DEPRECATED (replaced by multiple reviewers)
 */
function handleReviewerSelection() {
    // Kept for backward compatibility
}

// Global array لتخزين المراجعين
window.selectedReviewers = [];

/**
 * إضافة مراجع جديد
 */
function addReviewerRow() {
    const reviewersList = document.getElementById('reviewersList');
    const noReviewersMsg = document.getElementById('noReviewersMsg');

    // إخفاء رسالة "لا يوجد مراجعين"
    if (noReviewersMsg) {
        noReviewersMsg.style.display = 'none';
    }

    const order = window.selectedReviewers.length + 1;
    const reviewerId = `reviewer_${Date.now()}`;

    const reviewerRow = document.createElement('div');
    reviewerRow.className = 'reviewer-row mb-2 p-2 bg-white border rounded';
    reviewerRow.id = reviewerId;
    reviewerRow.innerHTML = `
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary" style="min-width: 30px;">${order}</span>
            <input type="text"
                   class="form-control form-control-sm reviewer-search"
                   list="reviewerUsersList"
                   placeholder="ابحث عن المراجع..."
                   data-reviewer-id="${reviewerId}"
                   oninput="handleReviewerInputChange('${reviewerId}')">
            <input type="hidden" class="reviewer-user-id" data-reviewer-id="${reviewerId}">
            <button type="button" class="btn btn-sm btn-danger" onclick="removeReviewer('${reviewerId}')">
                <i class="fas fa-trash"></i>
            </button>
            ${order > 1 ? `<button type="button" class="btn btn-sm btn-secondary" onclick="moveReviewerUp('${reviewerId}')">
                <i class="fas fa-arrow-up"></i>
            </button>` : ''}
            ${window.selectedReviewers.length > 0 ? `<button type="button" class="btn btn-sm btn-secondary" onclick="moveReviewerDown('${reviewerId}')">
                <i class="fas fa-arrow-down"></i>
            </button>` : ''}
        </div>
    `;

    reviewersList.appendChild(reviewerRow);

    window.selectedReviewers.push({
        id: reviewerId,
        userId: null,
        userName: null,
        order: order
    });
}

/**
 * معالجة تغيير input المراجع
 */
function handleReviewerInputChange(reviewerId) {
    const searchInput = document.querySelector(`.reviewer-search[data-reviewer-id="${reviewerId}"]`);
    const hiddenInput = document.querySelector(`.reviewer-user-id[data-reviewer-id="${reviewerId}"]`);

    if (!searchInput || !hiddenInput) return;

    const inputValue = searchInput.value;
    const searchName = inputValue.replace(' ✅ من المشروع', '').trim();

    if (window.allUsersForRevision) {
        const selectedUser = window.allUsersForRevision.find(user => user.name === searchName);

        if (selectedUser) {
            // ✅ Validation: تحقق إن المستخدم ده مش المنفذ
            const executorUserId = document.getElementById('newExecutorUserId')?.value;
            if (executorUserId && selectedUser.id == executorUserId) {
                Swal.fire('تحذير', 'لا يمكن أن يكون المنفذ مراجعاً. اختر شخصاً آخر.', 'warning');
                searchInput.value = '';
                hiddenInput.value = '';
                const reviewer = window.selectedReviewers.find(r => r.id === reviewerId);
                if (reviewer) {
                    reviewer.userId = null;
                    reviewer.userName = null;
                }
                return;
            }

            // ✅ Validation: تحقق إن المستخدم ده مش مضاف كمراجع قبل كده
            const alreadyAdded = window.selectedReviewers.some(r => r.id !== reviewerId && r.userId == selectedUser.id);
            if (alreadyAdded) {
                Swal.fire('تحذير', 'هذا المراجع مضاف بالفعل. اختر شخصاً آخر.', 'warning');
                searchInput.value = '';
                hiddenInput.value = '';
                const reviewer = window.selectedReviewers.find(r => r.id === reviewerId);
                if (reviewer) {
                    reviewer.userId = null;
                    reviewer.userName = null;
                }
                return;
            }

            hiddenInput.value = selectedUser.id;

            // تحديث في المصفوفة العالمية
            const reviewer = window.selectedReviewers.find(r => r.id === reviewerId);
            if (reviewer) {
                reviewer.userId = selectedUser.id;
                reviewer.userName = selectedUser.name;
            }
        } else {
            hiddenInput.value = '';
            const reviewer = window.selectedReviewers.find(r => r.id === reviewerId);
            if (reviewer) {
                reviewer.userId = null;
                reviewer.userName = null;
            }
        }
    }
}

/**
 * حذف مراجع
 */
function removeReviewer(reviewerId) {
    const reviewerRow = document.getElementById(reviewerId);
    if (reviewerRow) {
        reviewerRow.remove();
    }

    // حذف من المصفوفة
    window.selectedReviewers = window.selectedReviewers.filter(r => r.id !== reviewerId);

    // إعادة ترقيم المراجعين
    reorderReviewers();

    // إظهار رسالة "لا يوجد مراجعين" إذا فارغة
    if (window.selectedReviewers.length === 0) {
        const noReviewersMsg = document.getElementById('noReviewersMsg');
        if (noReviewersMsg) {
            noReviewersMsg.style.display = 'block';
        }
    }
}

/**
 * تحريك المراجع لأعلى
 */
function moveReviewerUp(reviewerId) {
    const index = window.selectedReviewers.findIndex(r => r.id === reviewerId);
    if (index > 0) {
        // تبديل في المصفوفة
        [window.selectedReviewers[index], window.selectedReviewers[index - 1]] =
        [window.selectedReviewers[index - 1], window.selectedReviewers[index]];

        // إعادة ترتيب الـ DOM
        const reviewersList = document.getElementById('reviewersList');
        const currentRow = document.getElementById(reviewerId);
        const previousRow = currentRow.previousElementSibling;

        if (previousRow && previousRow.className.includes('reviewer-row')) {
            reviewersList.insertBefore(currentRow, previousRow);
        }

        reorderReviewers();
    }
}

/**
 * تحريك المراجع لأسفل
 */
function moveReviewerDown(reviewerId) {
    const index = window.selectedReviewers.findIndex(r => r.id === reviewerId);
    if (index < window.selectedReviewers.length - 1) {
        // تبديل في المصفوفة
        [window.selectedReviewers[index], window.selectedReviewers[index + 1]] =
        [window.selectedReviewers[index + 1], window.selectedReviewers[index]];

        // إعادة ترتيب الـ DOM
        const reviewersList = document.getElementById('reviewersList');
        const currentRow = document.getElementById(reviewerId);
        const nextRow = currentRow.nextElementSibling;

        if (nextRow && nextRow.className.includes('reviewer-row')) {
            reviewersList.insertBefore(nextRow, currentRow);
        }

        reorderReviewers();
    }
}

/**
 * إعادة ترقيم المراجعين
 */
function reorderReviewers() {
    const reviewerRows = document.querySelectorAll('.reviewer-row');
    reviewerRows.forEach((row, index) => {
        const badge = row.querySelector('.badge');
        if (badge) {
            badge.textContent = index + 1;
        }

        const reviewerId = row.id;
        const reviewer = window.selectedReviewers.find(r => r.id === reviewerId);
        if (reviewer) {
            reviewer.order = index + 1;
        }
    });
}

/**
 * تحميل المسؤولين - يعرض كل الموظفين مع إشارة للموجودين في المشروع
 * مثل المنفذ والمراجعين
 */
async function loadResponsibleUsersForRevision(projectId) {
    try {
        const responsibleDatalist = document.getElementById('responsibleUsersList');
        responsibleDatalist.innerHTML = '';

        // استخدام البيانات المخزنة عالمياً من loadProjectParticipantsForRevision
        const allUsers = window.allUsersForRevision || [];
        const projectParticipantIds = window.projectParticipantIds || [];

        // ترتيب المستخدمين: اللي في المشروع أولاً
        const usersInProject = allUsers.filter(user => projectParticipantIds.includes(user.id));
        const usersNotInProject = allUsers.filter(user => !projectParticipantIds.includes(user.id));
        const sortedUsers = [...usersInProject, ...usersNotInProject];

        // ملء datalist المسؤول
        sortedUsers.forEach(user => {
            const option = document.createElement('option');
            const isInProject = projectParticipantIds.includes(user.id);
            option.value = user.name + (isInProject ? ' ✅ من المشروع' : '');
            option.setAttribute('data-user-id', user.id);
            option.setAttribute('data-user-name', user.name);
            option.setAttribute('data-in-project', isInProject);
            responsibleDatalist.appendChild(option);
        });
    } catch (error) {
        console.error('Error loading responsible users:', error);
    }
}

async function loadReviewerUsersForRevision(projectId, allUsers, projectParticipantIds) {
    try {
        const response = await fetch(`/task-revisions/reviewers-only?project_id=${projectId}`, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        });

        const result = await response.json();

        const reviewerDatalist = document.getElementById('reviewerUsersList');
        reviewerDatalist.innerHTML = '';

        if (result.success && result.reviewers && result.reviewers.length > 0) {
            // إذا كان المستخدم له قيود (restricted)، استخدم المراجعين المفلترين
            if (result.is_restricted) {

                // ترتيب: المراجعين اللي في المشروع أولاً، ثم الباقي
                const reviewersInProject = result.reviewers.filter(user => projectParticipantIds.includes(user.id));
                const reviewersNotInProject = result.reviewers.filter(user => !projectParticipantIds.includes(user.id));
                const sortedReviewers = [...reviewersInProject, ...reviewersNotInProject];

                sortedReviewers.forEach(user => {
                    const option = document.createElement('option');
                    const isInProject = projectParticipantIds.includes(user.id);
                    option.value = user.name + (isInProject ? ' ✅ من المشروع' : '');
                    option.setAttribute('data-user-id', user.id);
                    option.setAttribute('data-user-name', user.name);
                    option.setAttribute('data-in-project', isInProject);
                    reviewerDatalist.appendChild(option);
                });

            } else {
                // إذا لم يكن محدوداً، اعرض كل المستخدمين
                console.log('🔓 Normal mode: showing all users');

                const usersInProject = allUsers.filter(user => projectParticipantIds.includes(user.id));
                const usersNotInProject = allUsers.filter(user => !projectParticipantIds.includes(user.id));
                const sortedUsers = [...usersInProject, ...usersNotInProject];

                sortedUsers.forEach(user => {
                    const option = document.createElement('option');
                    const isInProject = projectParticipantIds.includes(user.id);
                    option.value = user.name + (isInProject ? ' ✅ من المشروع' : '');
                    option.setAttribute('data-user-id', user.id);
                    option.setAttribute('data-user-name', user.name);
                    option.setAttribute('data-in-project', isInProject);
                    reviewerDatalist.appendChild(option);
                });
            }
        } else {
            // في حالة عدم وجود نتائج، اعرض كل المستخدمين (fallback)
            console.log('⚠️ No results from API, showing all users as fallback');

            const usersInProject = allUsers.filter(user => projectParticipantIds.includes(user.id));
            const usersNotInProject = allUsers.filter(user => !projectParticipantIds.includes(user.id));
            const sortedUsers = [...usersInProject, ...usersNotInProject];

            sortedUsers.forEach(user => {
                const option = document.createElement('option');
                const isInProject = projectParticipantIds.includes(user.id);
                option.value = user.name + (isInProject ? ' ✅ من المشروع' : '');
                option.setAttribute('data-user-id', user.id);
                option.setAttribute('data-user-name', user.name);
                option.setAttribute('data-in-project', isInProject);
                reviewerDatalist.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading reviewer users:', error);
        // في حالة الخطأ، اعرض كل المستخدمين (fallback)
        const usersInProject = allUsers.filter(user => projectParticipantIds.includes(user.id));
        const usersNotInProject = allUsers.filter(user => !projectParticipantIds.includes(user.id));
        const sortedUsers = [...usersInProject, ...usersNotInProject];

        const reviewerDatalist = document.getElementById('reviewerUsersList');
        reviewerDatalist.innerHTML = '';

        sortedUsers.forEach(user => {
            const option = document.createElement('option');
            const isInProject = projectParticipantIds.includes(user.id);
            option.value = user.name + (isInProject ? ' ✅ من المشروع' : '');
            option.setAttribute('data-user-id', user.id);
            option.setAttribute('data-user-name', user.name);
            option.setAttribute('data-in-project', isInProject);
            reviewerDatalist.appendChild(option);
        });
    }
}

/**
 * Toggle بين ملف ورابط
 */
function toggleNewAttachmentType(type) {
    const fileContainer = document.getElementById('newFileContainer');
    const linkContainer = document.getElementById('newLinkContainer');

    if (type === 'file') {
        fileContainer.style.display = 'block';
        linkContainer.style.display = 'none';
        document.getElementById('newRevisionAttachmentLink').value = '';
    } else {
        fileContainer.style.display = 'none';
        linkContainer.style.display = 'block';
        document.getElementById('newRevisionAttachment').value = '';
    }
}

/**
 * حفظ تعديل جديد
 */
async function saveNewRevision() {
    const revisionType = document.getElementById('newRevisionType').value;
    const revisionSource = document.getElementById('newRevisionSource').value;
    const title = document.getElementById('newRevisionTitle').value.trim();
    const description = document.getElementById('newRevisionDescription').value.trim();
    const notes = document.getElementById('newRevisionNotes').value.trim();

    if (!revisionType || !title || !description) {
        Swal.fire('خطأ', 'الرجاء ملء جميع الحقول المطلوبة', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('revision_type', revisionType);
    formData.append('revision_source', revisionSource);
    formData.append('title', title);
    formData.append('description', description);

    if (notes) {
        formData.append('notes', notes);
    }

    // إضافة المشروع إذا كان النوع "project"
    if (revisionType === 'project') {
        const projectId = document.getElementById('newRevisionProjectId').value;
        if (!projectId) {
            Swal.fire('خطأ', 'الرجاء اختيار المشروع', 'error');
            return;
        }
        formData.append('project_id', projectId);

        // إضافة المسؤوليات
        const responsibleUserId = document.getElementById('newResponsibleUserId').value;
        const executorUserId = document.getElementById('newExecutorUserId').value;
        const responsibilityNotes = document.getElementById('newResponsibilityNotes').value.trim();

        if (responsibleUserId) {
            formData.append('responsible_user_id', responsibleUserId);
        }
        if (executorUserId) {
            formData.append('executor_user_id', executorUserId);
        }

        // إضافة المراجعين المتعددين
        if (window.selectedReviewers && window.selectedReviewers.length > 0) {
            const reviewersData = window.selectedReviewers
                .filter(r => r.userId) // فقط المراجعين المحددين
                .map((r, index) => ({
                    reviewer_id: r.userId,
                    order: index + 1, // ✅ إعادة حساب الترتيب من جديد بناءً على الموقع الفعلي
                    status: r.status || 'pending', // ✅ الحفاظ على الحالة الأصلية في التعديل
                    completed_at: r.completed_at || null // ✅ الحفاظ على تاريخ الإكمال
                }));

            // ✅ Validation: تأكد إن المراجعين مش نفس المنفذ
            const duplicateReviewers = reviewersData.filter(r => r.reviewer_id == executorUserId);
            if (duplicateReviewers.length > 0) {
                Swal.fire('تحذير', 'لا يمكن أن يكون المنفذ مراجعاً في نفس التعديل. الرجاء اختيار مراجعين مختلفين.', 'warning');
                return;
            }

            // ✅ Validation: تأكد إن مفيش مراجع متكرر
            const reviewerIds = reviewersData.map(r => r.reviewer_id);
            const uniqueReviewerIds = [...new Set(reviewerIds)];
            if (reviewerIds.length !== uniqueReviewerIds.length) {
                Swal.fire('تحذير', 'لا يمكن إضافة نفس المراجع أكثر من مرة. الرجاء اختيار مراجعين مختلفين.', 'warning');
                return;
            }

            console.log('📊 Reviewers being sent to backend:', reviewersData);

            if (reviewersData.length > 0) {
                formData.append('reviewers', JSON.stringify(reviewersData));
            }
        }

        if (responsibilityNotes) {
            formData.append('responsibility_notes', responsibilityNotes);
        }
    }

    // المرفق
    const attachmentType = document.querySelector('input[name="newAttachmentType"]:checked').value;

    if (attachmentType === 'file') {
        const fileInput = document.getElementById('newRevisionAttachment');
        if (fileInput.files[0]) {
            formData.append('attachment', fileInput.files[0]);
            formData.append('attachment_type', 'file');
        }
    } else {
        const link = document.getElementById('newRevisionAttachmentLink').value.trim();
        if (link) {
            formData.append('attachment_link', link);
            formData.append('attachment_type', 'link');
        }
    }

    try {
        let url, method;

        if (editingRevisionId) {
            // وضع التعديل
            url = `/general-revisions/${editingRevisionId}`;
            method = 'POST';
            formData.append('_method', 'PUT');
        } else {
            // وضع الإضافة
            url = '/general-revisions';
            method = 'POST';
        }

        const response = await fetch(url, {
            method: method,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            Swal.fire({
                title: 'تم بنجاح!',
                text: editingRevisionId ? 'تم تحديث التعديل بنجاح' : 'تم إضافة التعديل بنجاح',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });

            closeAddRevisionSidebar();
            refreshData();
        } else {
            Swal.fire('خطأ', result.message || 'حدث خطأ في حفظ التعديل', 'error');
        }
    } catch (error) {
        console.error('Error saving revision:', error);
        Swal.fire('خطأ', 'حدث خطأ في الاتصال بالخادم', 'error');
    }
}

/**
 * فتح form التعديل مع تحميل البيانات الموجودة
 */
async function openEditRevisionForm(revisionId) {
    try {
        // جلب بيانات التعديل
        const response = await fetch(`/revision-page/revision/${revisionId}`, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        });

        const result = await response.json();

        if (!result.success || !result.revision) {
            Swal.fire('خطأ', 'لم يتم العثور على التعديل', 'error');
            return;
        }

        const revision = result.revision;

        // ✅ Log للتأكد من البيانات
        console.log('📝 Revision data loaded for editing:', {
            id: revision.id,
            type: revision.revision_type,
            project_id: revision.project_id,
            project_name: revision.project?.name,
            project: revision.project,
            responsible_user_id: revision.responsible_user_id,
            responsible_user: revision.responsible_user || revision.responsibleUser, // ✅ Laravel returns camelCase
            executor_user_id: revision.executor_user_id,
            executor_user: revision.executor_user || revision.executorUser, // ✅ Laravel returns camelCase
            reviewers_count: revision.reviewers?.length || 0,
            reviewers: revision.reviewers,
            reviewers_with_data: revision.reviewers_with_data || revision.reviewersWithData // ✅ Laravel returns camelCase
        });

        // التحقق من أن المستخدم هو المنشئ
        const currentUserId = typeof AUTH_USER_ID !== 'undefined' ? AUTH_USER_ID : '';
        if (revision.created_by != currentUserId) {
            Swal.fire('غير مصرح', 'فقط منشئ التعديل يمكنه تعديله', 'error');
            return;
        }

        // تفعيل وضع التعديل
        editingRevisionId = revisionId;

        // إغلاق الـ sidebar التفاصيل
        closeSidebar();

        // ملء البيانات في الـ form
        document.getElementById('newRevisionType').value = revision.revision_type || '';
        document.getElementById('newRevisionSource').value = revision.revision_source || '';
        document.getElementById('newRevisionTitle').value = revision.title || '';
        document.getElementById('newRevisionDescription').value = revision.description || '';
        document.getElementById('newRevisionNotes').value = revision.notes || '';

        // إظهار/إخفاء حقل المشروع
        toggleRevisionTypeOptions();

        // إذا كان نوع المشروع
        if (revision.revision_type === 'project' && revision.project_id) {
            // ✅ تعبئة المشروع في الحقلين (hidden و search)
            document.getElementById('newRevisionProjectId').value = revision.project_id;

            // تعبئة اسم المشروع في الـ search input
            if (revision.project && revision.project.name) {
                document.getElementById('newRevisionProjectSearch').value = revision.project.name;
            }

            // تحميل المشاركين في المشروع
            await loadProjectParticipantsForRevision();

            console.log('✅ Project participants loaded, now filling form fields...');

            // ✅ ملء بيانات المسؤوليات بعد تحميل المشاركين
            setTimeout(() => {
                console.log('⏱️ Filling responsibility fields after timeout...');
                // ✅ المسؤول (Responsible User)
                if (revision.responsible_user_id) {
                    document.getElementById('newResponsibleUserId').value = revision.responsible_user_id;
                    // ملء الاسم في الـ search input
                    // ✅ Laravel returns camelCase (responsibleUser) not snake_case (responsible_user)
                    const responsibleUserData = revision.responsible_user || revision.responsibleUser;
                    if (responsibleUserData && responsibleUserData.name) {
                        const responsibleSearch = document.getElementById('newResponsibleUserSearch');
                        if (responsibleSearch) {
                            // ✅ إضافة العلامة إذا كان الشخص في المشروع
                            const isInProject = window.projectParticipantIds?.includes(revision.responsible_user_id);
                            responsibleSearch.value = responsibleUserData.name + (isInProject ? ' ✅ من المشروع' : '');
                            console.log('✅ Responsible user name set:', responsibleUserData.name);
                        }
                    }
                }

                // ✅ المنفذ (Executor User)
                if (revision.executor_user_id) {
                    document.getElementById('newExecutorUserId').value = revision.executor_user_id;
                    // ملء الاسم في الـ search input
                    // ✅ Laravel returns camelCase (executorUser) not snake_case (executor_user)
                    const executorUserData = revision.executor_user || revision.executorUser;
                    if (executorUserData && executorUserData.name) {
                        const executorSearch = document.getElementById('newExecutorUserSearch');
                        if (executorSearch) {
                            executorSearch.value = executorUserData.name;
                            console.log('✅ Executor user name set:', executorUserData.name);
                        }
                    } else {
                        console.warn('⚠️ Executor user data not found!', {
                            executor_user_id: revision.executor_user_id,
                            executor_user: revision.executor_user,
                            executorUser: revision.executorUser
                        });
                    }
                }

                // ✅ ملء المراجعين المتعددين
                // استخدام reviewers_with_data إذا متاحة (فيها بيانات المستخدمين)
                const reviewersData = revision.reviewers_with_data || revision.reviewersWithData || revision.reviewers;

                if (reviewersData && Array.isArray(reviewersData) && reviewersData.length > 0) {
                    // مسح القائمة الحالية
                    const reviewersList = document.getElementById('reviewersList');
                    const noReviewersMsg = document.getElementById('noReviewersMsg');

                    if (reviewersList && noReviewersMsg) {
                        // إخفاء رسالة "لا يوجد مراجعين"
                        noReviewersMsg.style.display = 'none';

                        // مسح المراجعين القدامى من window.selectedReviewers
                        window.selectedReviewers = [];

                        console.log('📋 Loading reviewers:', reviewersData);

                        // إضافة كل مراجع
                        reviewersData.forEach((reviewer, index) => {
                            // البحث عن المستخدم في القائمة
                            let userName = 'مستخدم غير معروف';
                            let userId = reviewer.reviewer_id;

                            // إذا كان reviewer فيه بيانات المستخدم
                            // ✅ Laravel يمكن يرجع reviewers_with_data أو reviewersWithData
                            if (reviewer.user && reviewer.user.name) {
                                userName = reviewer.user.name;
                            } else {
                                // البحث في window.allUsers (اللي اتحملت في loadAllUsersForReviewers)
                                const user = window.allUsers?.find(u => u.id == reviewer.reviewer_id);
                                if (user) {
                                    userName = user.name;
                                } else {
                                    console.warn('⚠️ User not found for reviewer:', reviewer.reviewer_id);
                                }
                            }

                            // ✅ إضافة للـ window.selectedReviewers عشان الـ save يحتفظ بيهم
                            const reviewerId = `reviewer_edit_${userId}_${reviewer.order}`;
                            window.selectedReviewers.push({
                                id: reviewerId,
                                userId: userId,
                                userName: userName,
                                order: reviewer.order,
                                status: reviewer.status, // ✅ الحفاظ على الحالة الأصلية
                                completed_at: reviewer.completed_at
                            });

                            // إنشاء عنصر المراجع للعرض فقط (read-only)
                            const reviewerItem = document.createElement('div');
                            reviewerItem.className = 'reviewer-item d-flex align-items-center justify-content-between p-2 mb-2 border rounded';
                            reviewerItem.id = reviewerId;
                            reviewerItem.innerHTML = `
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-primary">${reviewer.order}</span>
                                    <span>${userName}</span>
                                    ${reviewer.status === 'completed' ? '<i class="fas fa-check-circle text-success ms-2" title="مكتمل"></i>' : ''}
                                    ${reviewer.status === 'in_progress' ? '<i class="fas fa-spinner text-warning ms-2" title="جاري المراجعة"></i>' : ''}
                                    ${reviewer.status === 'pending' ? '<i class="fas fa-clock text-secondary ms-2" title="في الانتظار"></i>' : ''}
                                </div>
                                <div class="d-flex gap-1">
                                    ${reviewer.status !== 'completed' ? `
                                        <button type="button" class="btn btn-sm btn-danger" onclick="removeReviewer('${reviewerId}')" title="حذف">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    ` : '<small class="text-success"><i class="fas fa-lock me-1"></i>مكتمل</small>'}
                                </div>
                            `;

                            reviewersList.appendChild(reviewerItem);
                        });
                    }
                }

                if (revision.responsibility_notes) {
                    document.getElementById('newResponsibilityNotes').value = revision.responsibility_notes;
                }
            }, 300); // انتظار قليل لتحميل البيانات
        }

        // تحديث عنوان الـ sidebar
        const sidebarTitle = document.querySelector('#addRevisionSidebar h5');
        if (sidebarTitle) {
            sidebarTitle.innerHTML = '<i class="fas fa-edit me-2"></i>تعديل التعديل';
        }

        // تحديث نص زر الحفظ
        const saveButton = document.querySelector('#addRevisionSidebar button[onclick*="saveNewRevision"]');
        if (saveButton) {
            saveButton.innerHTML = '<i class="fas fa-save me-1"></i>حفظ التعديلات';
        }

        // فتح الـ sidebar
        const sidebar = document.getElementById('addRevisionSidebar');
        const overlay = document.getElementById('addRevisionOverlay');

        overlay.style.visibility = 'visible';
        overlay.style.opacity = '1';
        sidebar.style.right = '0';
        document.body.style.overflow = 'hidden';

    } catch (error) {
        console.error('Error loading revision for edit:', error);
        Swal.fire('خطأ', 'حدث خطأ في تحميل بيانات التعديل', 'error');
    }
}

