/**
 * نظام مشاركة المرفقات
 * يدير عمليات مشاركة الملفات مع المستخدمين
 */

class AttachmentSharing {
    constructor() {
        this.selectedAttachments = new Set();
        this.selectedUsers = new Set();
        this.projectId = null;
        this.allUsers = [];
        this.projectUsers = [];
        this.currentSearchUser = null; // المستخدم المختار من البحث

        this.init();
    }

    init() {
        this.bindEvents();
        this.loadProjectData();
    }

    bindEvents() {
        $(document).on('change', '.attachment-select', this.handleAttachmentSelection.bind(this));

        $(document).on('click', '.share-attachment-btn', this.openSingleShareModal.bind(this));
        $(document).on('click', '.view-shares-btn', this.viewAttachmentShares.bind(this));
        $(document).on('click', '.delete-attachment-btn', this.deleteAttachment.bind(this));

        $('#shareSelectedHeaderBtn').on('click', this.openMultiShareModal.bind(this));
        $('#clearSelectionHeaderBtn').on('click', this.clearSelection.bind(this));

        $('#userSearchInput').on('input', this.searchUsers.bind(this));
        $('#addUserBtn').on('click', this.addSelectedUser.bind(this));
        $(document).on('click', '.search-user-item', this.selectSearchUser.bind(this));
        $('#confirmShareBtn').on('click', this.confirmShare.bind(this));

        $('#copyShareUrlBtn').on('click', this.copyShareUrl.bind(this));


        $(document).on('keydown', (e) => {
            if (e.key === 'Escape') {
                const sidebar = document.getElementById('shareSidebar');
                if (sidebar && sidebar.classList.contains('show')) {
                    this.closeShareSidebar();
                }
            }
        });
    }

    loadProjectData() {
        this.projectId = window.location.pathname.split('/')[2];

        if (this.projectId) {
            this.loadUsersForSharing();
        }
    }

    async loadUsersForSharing() {
        try {
            const response = await fetch(`/projects/${this.projectId}/users-for-sharing`);
            const data = await response.json();

            if (data.success) {
                this.allUsers = data.all_users;
                this.projectUsers = data.project_users;
                this.canShareWithAll = data.can_share_with_all;
            }
        } catch (error) {
            console.error('خطأ في تحميل المستخدمين:', error);
            this.showToast('حدث خطأ في تحميل قائمة المستخدمين', 'error');
        }
    }

    handleAttachmentSelection(e) {
        const attachmentId = parseInt(e.target.value);
        const isChecked = e.target.checked;

        if (isChecked) {
            this.selectedAttachments.add(attachmentId);
        } else {
            this.selectedAttachments.delete(attachmentId);
        }

        this.updateMultiShareToolbar();
    }

    updateMultiShareToolbar() {
        const header = $('#multiShareHeader');
        const count = this.selectedAttachments.size;

        if (count > 0) {
            header.removeClass('d-none').addClass('show');
            $('#selectedCountText').text(`${count} ملف محدد`);
        } else {
            header.addClass('d-none').removeClass('show');
        }
    }

    openSingleShareModal(e) {
        e.preventDefault();
        const attachmentId = parseInt($(e.target).closest('.share-attachment-btn').data('attachment-id'));
        const attachmentName = $(e.target).closest('.share-attachment-btn').data('attachment-name');

        this.selectedAttachments.clear();
        this.selectedAttachments.add(attachmentId);

        this.openShareModal([{id: attachmentId, name: attachmentName}]);
    }

    openMultiShareModal() {
        const selectedFiles = [];

        this.selectedAttachments.forEach(id => {
            const fileElement = $(`.attachment-select[value="${id}"]`).closest('li');
            const fileName = fileElement.find('.attachment-name').text();
            selectedFiles.push({id: id, name: fileName});
        });

        this.openShareModal(selectedFiles);
    }

    openShareModal(files) {
        this.displaySelectedFiles(files);
        this.resetSearch();
        this.openShareSidebar();
    }

    openShareSidebar() {
        const sidebar = document.getElementById('shareSidebar');
        const overlay = document.getElementById('shareSidebarOverlay');

        overlay.classList.add('show');

        sidebar.classList.add('show');

        document.body.classList.add('share-sidebar-open');
    }

    closeShareSidebar() {
        const sidebar = document.getElementById('shareSidebar');
        const overlay = document.getElementById('shareSidebarOverlay');


        sidebar.classList.remove('show');


        overlay.classList.remove('show');


        document.body.classList.remove('share-sidebar-open');


        this.resetShareModal();
    }

    displaySelectedFiles(files) {
        const container = $('.selected-files-list');
        container.empty();

        files.forEach(file => {
            container.append(`
                <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-white rounded border">
                    <span><i class="fas fa-file me-2"></i>${file.name}</span>
                    <small class="text-muted">ID: ${file.id}</small>
                </div>
            `);
        });
    }

    resetSearch() {
        $('#userSearchInput').val('');
        $('#searchResults').hide().empty();
        $('#addUserBtn').prop('disabled', true);
        this.currentSearchUser = null;
    }

    searchUsers() {
        const searchTerm = $('#userSearchInput').val().toLowerCase().trim();
        const resultsContainer = $('#searchResults');

        if (searchTerm.length < 2) {
            resultsContainer.hide().empty();
            $('#addUserBtn').prop('disabled', true);
            this.currentSearchUser = null;
            return;
        }

        const allUsersToSearch = [...this.projectUsers, ...this.allUsers];
        const filteredUsers = allUsersToSearch.filter(user => {
            const userName = user.name.toLowerCase();
            const userEmail = user.email ? user.email.toLowerCase() : '';
            const userDept = user.department ? user.department.toLowerCase() : '';

            return userName.includes(searchTerm) ||
                   userEmail.includes(searchTerm) ||
                   userDept.includes(searchTerm);
        });

        if (filteredUsers.length > 0) {
            resultsContainer.empty();
            filteredUsers.slice(0, 5).forEach(user => {
                if (!this.selectedUsers.has(user.id)) {
                    resultsContainer.append(this.createSearchUserItem(user));
                }
            });
            resultsContainer.show();
        } else {
            resultsContainer.hide().empty();
        }

        $('#addUserBtn').prop('disabled', true);
        this.currentSearchUser = null;
    }

    createSearchUserItem(user) {
        const isProjectMember = this.projectUsers.find(pu => pu.id === user.id);
        const badgeClass = isProjectMember ? 'bg-primary' : 'bg-secondary';
        const badgeText = isProjectMember ? 'عضو' : 'مستخدم';

        return `
            <div class="search-user-item p-2 border rounded mb-2" data-user-id="${user.id}" style="cursor: pointer;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>${user.name}</strong>
                        <br><small class="text-muted">${user.email || ''}</small>
                        ${user.department ? `<br><small class="text-info">${user.department}</small>` : ''}
                    </div>
                    <span class="badge ${badgeClass}">${badgeText}</span>
                </div>
            </div>
        `;
    }

    selectSearchUser(e) {
        const userItem = $(e.currentTarget);
        const userId = parseInt(userItem.data('user-id'));


        $('.search-user-item').removeClass('selected');
        userItem.addClass('selected');


        const allUsersToSearch = [...this.projectUsers, ...this.allUsers];
        this.currentSearchUser = allUsersToSearch.find(user => user.id === userId);

        $('#addUserBtn').prop('disabled', false);
    }

        addSelectedUser() {
        if (!this.currentSearchUser) return;

        if (this.selectedUsers.has(this.currentSearchUser.id)) {
            this.showToast(`${this.currentSearchUser.name} مضاف بالفعل`, 'warning');
            return;
        }

        this.selectedUsers.add(this.currentSearchUser.id);

        this.showToast(`تم إضافة ${this.currentSearchUser.name}`, 'success');

        this.updateSelectedUsersDisplay();

        this.resetSearch();
    }



    updateSelectedUsersDisplay() {
        const container = $('.selected-users-container');
        const listContainer = $('.selected-users-list');

        if (this.selectedUsers.size > 0) {
            container.removeClass('d-none').show();
            listContainer.empty();

            this.selectedUsers.forEach(userId => {
                const user = [...this.allUsers, ...this.projectUsers].find(u => u.id === userId);
                if (user) {
                    listContainer.append(`
                        <span class="badge bg-primary user-badge" data-user-id="${userId}">
                            ${user.name}
                            <button type="button" class="btn-close btn-close-white ms-2"
                                    onclick="attachmentSharing.removeUserSelection(${userId})"></button>
                        </span>
                    `);
                }
            });
        } else {
            container.addClass('d-none').hide();
        }
    }

    removeUserSelection(userId) {
        this.selectedUsers.delete(userId);
        $(`.user-card[data-user-id="${userId}"]`).removeClass('selected').find('.user-select').prop('checked', false);
        this.updateSelectedUsersDisplay();
    }

    async confirmShare() {
        if (this.selectedUsers.size === 0) {
            this.showToast('يرجى اختيار مستخدم واحد على الأقل للمشاركة معه', 'warning');
            return;
        }

        const btn = $('#confirmShareBtn');
        const btnText = btn.find('.btn-text');
        const spinner = btn.find('.spinner-border');

        // إظهار التحميل
        btn.prop('disabled', true);
        btnText.text('جاري المشاركة...');
        spinner.show();

        try {
            const shareData = {
                attachment_ids: Array.from(this.selectedAttachments),
                user_ids: Array.from(this.selectedUsers),
                expires_in_hours: $('#shareExpiration').val() || null,
                description: $('#shareDescription').val()
            };

            const response = await fetch('/projects/attachments/share', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                body: JSON.stringify(shareData)
            });

            const result = await response.json();

            if (result.success) {
                this.closeShareSidebar();
                this.showShareResult(result.data);
                this.clearSelection();
                this.showToast('تم إنشاء المشاركة بنجاح', 'success');
            } else {
                this.showToast(result.message || 'حدث خطأ أثناء المشاركة', 'error');
            }

        } catch (error) {
            console.error('خطأ في المشاركة:', error);
            this.showToast('حدث خطأ في الاتصال بالخادم', 'error');
        } finally {
            // إخفاء التحميل
            btn.prop('disabled', false);
            btnText.text('مشاركة الملفات');
            spinner.hide();
        }
    }

    showShareResult(data) {
        $('#shareUrlInput').val(data.share_url);
        $('#sharedFilesCount').text(data.shared_attachments_count);
        $('#sharedWithCount').text(data.shared_with_count);
        $('#shareExpiryDate').text(data.expires_at ?
            new Date(data.expires_at).toLocaleString('ar-SA') : 'بلا انتهاء');

        $('#shareResultModal').modal('show');
    }

    copyShareUrl() {
        const input = document.getElementById('shareUrlInput');
        input.select();
        input.setSelectionRange(0, 99999);
        document.execCommand('copy');

        const btn = $('#copyShareUrlBtn');
        const originalIcon = btn.html();
        btn.html('<i class="fas fa-check"></i>');

        setTimeout(() => {
            btn.html(originalIcon);
        }, 2000);

        this.showToast('تم نسخ الرابط', 'success');
    }

    async viewAttachmentShares(e) {
        e.preventDefault();
        const attachmentId = $(e.target).closest('.view-shares-btn').data('attachment-id');

        try {
            const response = await fetch(`/projects/attachments/${attachmentId}/shares`);
            const data = await response.json();

            if (data.success) {
                this.displayAttachmentShares(data.shares);
                this.openViewSharesSidebar();
            } else {
                this.showToast('حدث خطأ في جلب المشاركات', 'error');
            }
        } catch (error) {
            console.error('خطأ في جلب المشاركات:', error);
            this.showToast('حدث خطأ في الاتصال بالخادم', 'error');
        }
    }

    openViewSharesSidebar() {
        const sidebar = document.getElementById('viewSharesSidebar');
        const overlay = document.getElementById('viewSharesSidebarOverlay');

        if (sidebar && overlay) {
            sidebar.style.right = '0px';
            overlay.style.visibility = 'visible';
            overlay.style.opacity = '1';

            // منع scroll للـ body
            document.body.style.overflow = 'hidden';
        }
    }

    closeViewSharesSidebar() {
        const sidebar = document.getElementById('viewSharesSidebar');
        const overlay = document.getElementById('viewSharesSidebarOverlay');

        if (sidebar && overlay) {
            sidebar.style.right = '-650px';
            overlay.style.visibility = 'hidden';
            overlay.style.opacity = '0';

            // إعادة تفعيل scroll للـ body
            document.body.style.overflow = '';
        }
    }

    displayAttachmentShares(shares) {
        const container = $('.shares-list-container');
        container.empty();

        if (shares.length === 0) {
            container.html(`
                <div class="text-center text-muted py-4">
                    <i class="fas fa-share fa-3x mb-3"></i>
                    <p>لم يتم مشاركة هذا الملف مع أي شخص</p>
                </div>
            `);
            return;
        }

        shares.forEach(share => {
            const expiryText = share.expires_at ?
                `ينتهي: ${new Date(share.expires_at).toLocaleString('ar-SA')}` :
                'بلا انتهاء';

            const statusBadge = share.is_active ?
                '<span class="badge bg-success">نشط</span>' :
                '<span class="badge bg-danger">منتهي</span>';

            container.append(`
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="card-title">${share.shared_by.name}</h6>
                                <p class="card-text">
                                    <small class="text-muted">
                                        شارك في: ${new Date(share.created_at).toLocaleString('ar-SA')}<br>
                                        ${expiryText}<br>
                                        مشاهدات: ${share.view_count}
                                    </small>
                                </p>
                                ${share.description ? `<p class="text-info">${share.description}</p>` : ''}
                            </div>
                            <div class="text-end">
                                ${statusBadge}
                                ${share.is_active ? `
                                    <button class="btn btn-sm btn-outline-danger mt-2 cancel-share-btn"
                                            data-share-id="${share.id}">
                                        إلغاء
                                    </button>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                </div>
            `);
        });

        $('.cancel-share-btn').on('click', this.cancelShare.bind(this));
    }

    async cancelShare(e) {
        const shareId = $(e.target).data('share-id');

        if (!confirm('هل أنت متأكد من إلغاء هذه المشاركة؟')) {
            return;
        }

        try {
            const response = await fetch(`/projects/shares/${shareId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            const result = await response.json();

            if (result.success) {
                $(e.target).closest('.card').fadeOut();
                this.showToast('تم إلغاء المشاركة', 'success');
            } else {
                this.showToast(result.message || 'حدث خطأ أثناء الإلغاء', 'error');
            }
        } catch (error) {
            console.error('خطأ في إلغاء المشاركة:', error);
            this.showToast('حدث خطأ في الاتصال بالخادم', 'error');
        }
    }

    async deleteAttachment(e) {
        e.preventDefault();
        const attachmentId = $(e.target).closest('.delete-attachment-btn').data('attachment-id');
        const attachmentName = $(e.target).closest('.delete-attachment-btn').data('attachment-name');

        if (!confirm(`هل أنت متأكد من حذف الملف "${attachmentName}"؟`)) {
            return;
        }

        try {
            const response = await fetch(`/projects/attachments/${attachmentId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            if (response.ok) {
                $(`[data-attachment-id="${attachmentId}"]`).fadeOut(() => {
                    $(this).remove();
                });
                this.showToast('تم حذف الملف بنجاح', 'success');
            } else {
                this.showToast('حدث خطأ أثناء حذف الملف', 'error');
            }
        } catch (error) {
            console.error('خطأ في حذف الملف:', error);
            this.showToast('حدث خطأ في الاتصال بالخادم', 'error');
        }
    }

    clearSelection() {
        this.selectedAttachments.clear();
        this.selectedUsers.clear();
        $('.attachment-select').prop('checked', false);
        this.updateMultiShareToolbar();

        // إخفاء الهيدر عند إلغاء التحديد
        $('#multiShareHeader').addClass('d-none').removeClass('show');
    }

    resetShareModal() {
        this.selectedUsers.clear();
        this.currentSearchUser = null;
        $('#userSearchInput').val('');
        $('#shareExpiration').val('');
        $('#shareDescription').val('');
        $('#searchResults').hide().empty();
        $('#addUserBtn').prop('disabled', true);
        $('.selected-users-container').addClass('d-none').hide();
    }

    showToast(message, type = 'info') {
        const alertClass = {
            'success': 'alert-success',
            'error': 'alert-danger',
            'warning': 'alert-warning',
            'info': 'alert-info'
        };

        const toast = $(`
            <div class="alert ${alertClass[type]} alert-dismissible fade show toast-notification" role="alert"
                 style="position: fixed; top: 20px; left: 20px; z-index: 9999; min-width: 300px;">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `);

        $('body').append(toast);

        setTimeout(() => {
            toast.alert('close');
        }, 5000);
    }
}

$(document).ready(function() {
    window.attachmentSharing = new AttachmentSharing();

    window.closeShareSidebar = () => {
        if (window.attachmentSharing) {
            window.attachmentSharing.closeShareSidebar();
        }
    };

    window.closeViewSharesSidebar = () => {
        if (window.attachmentSharing) {
            window.attachmentSharing.closeViewSharesSidebar();
        }
    };
});
