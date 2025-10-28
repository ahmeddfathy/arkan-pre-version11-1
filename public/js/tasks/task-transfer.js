// Task Transfer Functions for Tasks Index Page
// تحسينات النقل: دعم مهام القوالب + Sweet Alert + Logging مفصل
console.log('✅ ملف task-transfer.js تم تحميله');

let currentTaskData = null;
let availableUsers = [];

function openTransferModal(taskType, taskId, taskName, currentUserName, mode = 'transfer', taskUserId = null) {
    console.log('🚀 فتح نافذة النقل:', {
        taskType: taskType,
        taskId: taskId,
        taskUserId: taskUserId,
        taskName: taskName,
        currentUserName: currentUserName,
        mode: mode
    });

    currentTaskData = {
        type: taskType,
        id: taskId,
        taskUserId: taskUserId,
        name: taskName,
        currentUser: currentUserName,
        mode: mode
    };

    console.log('📝 تم حفظ بيانات المهمة:', currentTaskData);

    $('#taskType').val(taskType);
    $('#taskId').val(taskId);
    $('#taskName').text(taskName);
    $('#currentUser').text(currentUserName);

    $('#transferTaskForm')[0].reset();
    $('#taskType').val(taskType);
    $('#taskId').val(taskId);
    $('#transferError, #transferSuccess, #transferCheck').hide();

    // Reset transfer type to positive (no points input needed)
    $('#positiveTransfer').prop('checked', true);

    // تحديث عنوان ال Sidebar حسب الوضع
    if (mode === 'reassign') {
        $('#transferSidebarTitle').text('تعديل المستلم');
        $('#transferSidebarSubtitle').text('تغيير الشخص المخصص له المهمة');
    } else {
        $('#transferSidebarTitle').text('نقل المهمة');
        $('#transferSidebarSubtitle').text('تحويل المهمة لمستخدم آخر');
    }

    loadAvailableUsers();
    loadCurrentUserPoints();
    openTransferSidebar();
}

function openTransferSidebar() {
    const sidebar = document.getElementById('transferSidebar');
    const overlay = document.getElementById('transferSidebarOverlay');

    overlay.classList.add('show');
    sidebar.classList.add('show');
    document.body.classList.add('transfer-sidebar-open');
}

function closeTransferSidebar() {
    const sidebar = document.getElementById('transferSidebar');
    const overlay = document.getElementById('transferSidebarOverlay');

    sidebar.classList.remove('show');
    overlay.classList.remove('show');
    document.body.classList.remove('transfer-sidebar-open');

    currentTaskData = null;
    $('#transferTaskForm')[0].reset();
    $('#userInput').val('');
    $('#toUserId').val('');
    $('#newDeadline').val('');
    $('#selectedUserInfo').hide();
    $('#transferError, #transferSuccess, #transferWarning, #transferCheck').hide();
    $('#confirmTransferBtn').prop('disabled', false);

    // Reset transfer type to positive (no points input needed)
    $('#positiveTransfer').prop('checked', true);
}

function loadAvailableUsers() {
    $.get('/task-transfer/available-users')
        .done(function(response) {
            if (response.success) {
                availableUsers = response.data;
                populateUsersDatalist();
            }
        })
        .fail(function(xhr) {
            console.error('Failed to load users:', xhr);
            showError('فشل في تحميل قائمة المستخدمين: ' + (xhr.responseJSON?.message || 'خطأ غير معروف'));
        });
}

function populateUsersDatalist() {
    const datalist = $('#usersList');
    datalist.empty();

    availableUsers.forEach(user => {
        const displayText = user.display_name || user.name;
        datalist.append(`<option value="${displayText}" data-user-id="${user.id}" data-employee-id="${user.employee_id}" data-points="${user.current_points || 0}"></option>`);
    });
}

function findUserByName(name) {
    if (!name) return null;

    // تنظيف الاسم المدخل من المسافات الزائدة
    const cleanName = name.trim();

    // 1️⃣ أولاً: محاولة المطابقة التامة (case-sensitive)
    let user = availableUsers.find(u => {
        const displayName = u.display_name || u.name || '';
        return displayName === cleanName || u.name === cleanName;
    });

    if (user) return user;

    // 2️⃣ ثانياً: محاولة المطابقة بدون حساسية للحروف
    const lowerCleanName = cleanName.toLowerCase();
    user = availableUsers.find(u => {
        const displayName = (u.display_name || u.name || '').toLowerCase();
        const userName = (u.name || '').toLowerCase();
        return displayName === lowerCleanName || userName === lowerCleanName;
    });

    if (user) return user;

    // 3️⃣ ثالثاً: البحث الجزئي (للتأكد)
    user = availableUsers.find(u => {
        const displayName = (u.display_name || u.name || '').toLowerCase();
        const userName = (u.name || '').toLowerCase();
        return displayName.includes(lowerCleanName) || userName.includes(lowerCleanName);
    });

    return user || null;
}

function checkTransferability() {
    const toUserId = $('#toUserId').val();
    const transferType = $('input[name="transferType"]:checked').val();

    if (!toUserId || !currentTaskData) {
        return;
    }

    $('#transferCheck').show().find('.fa-spinner').addClass('fa-spin');
    $('#transferError, #transferSuccess').hide();

    const data = {
        to_user_id: toUserId,
        transfer_type: transferType,
        transfer_points: 0 // سيتم حسابها من نقاط المهمة في الباك اند
    };

    if (currentTaskData.type === 'template') {
        data.template_task_user_id = currentTaskData.id;
    } else {
        // للمهام العادية: استخدام task_user_id إذا كان متوفراً، وإلا استخدام task_id
        if (currentTaskData.taskUserId && currentTaskData.taskUserId !== 'null' && currentTaskData.taskUserId !== null) {
            data.task_user_id = currentTaskData.taskUserId;
        } else {
            data.task_id = currentTaskData.id;
        }
    }

    $.get('/task-transfer/check-transferability', data)
        .done(function(response) {
            $('#transferCheck').hide();
            if (response.success && response.data.can_transfer) {
                $('#confirmTransferBtn').prop('disabled', false);

                if (transferType === 'positive') {
                    showSuccess('يمكن نقل المهمة بنجاح - الموظف الجديد سيحصل على نقاط المهمة');
                } else if (response.data.will_be_negative) {
                    showWarning(`تحذير: النقاط ستصبح ${response.data.points_after_transfer} (سالبة) بعد الخصم`);
                } else {
                    showSuccess(`يمكن نقل المهمة. النقاط بعد الخصم: ${response.data.points_after_transfer}`);
                }
            } else {
                $('#confirmTransferBtn').prop('disabled', true);
                showError(response.data.reason || 'لا يمكن نقل المهمة');
            }
        })
        .fail(function() {
            $('#transferCheck').hide();
            showError('فشل في التحقق من إمكانية النقل');
        });
}

function loadCurrentUserPoints() {
    $.get('/api/user/current-points')
        .done(function(response) {
            if (response.success) {
                $('#currentPoints').text(response.points);
            } else {
                $('#currentPoints').text('0');
            }
        })
        .fail(function() {
            $('#currentPoints').text('غير متاح');
        });
}

// تنفيذ النقل
function executeTransfer() {
    console.log('بدء تنفيذ النقل...', {currentTaskData, Swal: typeof Swal});

    if (!currentTaskData) {
        console.error('المتغير currentTaskData غير موجود!');
        showError('خطأ في بيانات المهمة');
        return;
    }

    const transferType = $('input[name="transferType"]:checked').val();
    const toUserId = $('#toUserId').val();

    if (!toUserId) {
        console.error('❌ No toUserId');
        console.error('🔍 Debug info:', {
            userInputValue: $('#userInput').val(),
            toUserIdValue: $('#toUserId').val(),
            availableUsersCount: availableUsers.length
        });

        // عرض رسالة SweetAlert
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'خطأ!',
                html: `
                    <div class="text-center">
                        <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                        <p>يرجى اختيار المستخدم من القائمة</p>
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-info-circle me-2"></i>
                            تأكد من اختيار الاسم من القائمة المنسدلة
                        </div>
                    </div>
                `,
                icon: 'warning',
                confirmButtonText: 'حسناً',
                confirmButtonColor: '#ffc107'
            });
        } else {
            showError('يرجى اختيار المستخدم المستقبل');
        }
        return;
    }

    const formData = {
        to_user_id: toUserId,
        transfer_type: transferType,
        transfer_points: 0, // سيتم حسابها من نقاط المهمة في الباك اند
        reason: $('#transferReason').val(),
        new_deadline: $('#newDeadline').val() || null,
        _token: $('meta[name="csrf-token"]').attr('content')
    };

    let url;
    if (currentTaskData.type === 'template') {
        formData.template_task_user_id = currentTaskData.id;
        url = '/task-transfer/transfer-template-task';
        console.log('🔄 نقل مهمة قالب:', {
            type: 'template',
            template_task_user_id: currentTaskData.id,
            url: url,
            formData: formData
        });
    } else {
        // للمهام العادية: استخدام task_user_id إذا كان متوفراً، وإلا استخدام task_id
        if (currentTaskData.taskUserId && currentTaskData.taskUserId !== 'null' && currentTaskData.taskUserId !== null) {
            formData.task_user_id = currentTaskData.taskUserId;
        } else {
            // للمهام غير المعينة - استخدام task_id
            formData.task_id = currentTaskData.id;
        }
        url = '/task-transfer/transfer-task';
        console.log('🔄 نقل مهمة عادية:', {
            type: 'regular',
            task_user_id: formData.task_user_id || null,
            task_id: formData.task_id || null,
            url: url,
            formData: formData
        });
    }

    $('#confirmTransferBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>جاري النقل...');

    console.log('🚀 إرسال الطلب:', {
        url: url,
        formData: formData,
        taskType: currentTaskData.type
    });

    $.post(url, formData)
        .done(function(response) {
            console.log('✅ تم استقبال الـ response');
            console.log('📥 استجابة النقل:', {
                taskType: currentTaskData?.type,
                taskId: currentTaskData?.id,
                response: response,
                success: response.success,
                message: response.message
            });

            // التحقق الصريح من success
            if (response.success === false) {
                console.log('❌ الـ Backend أرجع success: false');
            }

            // معالجة خاصة لمهام القوالب - أحياناً ترجع response مختلف
            const isSuccess = response.success === true ||
                (response.success !== false &&
                 currentTaskData?.type === 'template' &&
                 response.message &&
                 (response.message.includes('تم النقل') || response.message.includes('نجح')));

            console.log('🔍 isSuccess =', isSuccess);

            if (isSuccess) {
                const transferTypeText = transferType === 'positive' ? 'إيجابي' : 'سلبي';
                const targetUserName = $('#userInput').val();

                // حفظ بيانات المهمة قبل إغلاق الـ sidebar
                const taskData = {
                    name: currentTaskData?.name || 'مهمة غير معروفة',
                    type: currentTaskData?.type || 'عادية',
                    currentUser: currentTaskData?.currentUser || 'مستخدم غير معروف'
                };

                // إغلاق الـ sidebar أولاً
                closeTransferSidebar();

                console.log('✅ تم النقل بنجاح', {
                    taskType: taskData.type,
                    response: response,
                    isTemplateTask: taskData.type === 'template'
                });

                // التحقق من وجود SweetAlert
                if (typeof Swal !== 'undefined') {
                    // عرض Sweet Alert للتأكيد
                    const taskTypeText = taskData.type === 'template' ? ' (مهمة قالب)' : ' (مهمة عادية)';
                    Swal.fire({
                    title: `تم النقل بنجاح! ✅${taskTypeText}`,
                    html: `
                        <div class="text-center">
                            <div class="mb-3">
                                <i class="fas fa-exchange-alt fa-3x text-success mb-3"></i>
                            </div>
                            <p class="mb-2"><strong>المهمة:</strong> ${taskData.name}</p>
                            <p class="mb-2"><strong>نُقلت من:</strong> ${taskData.currentUser}</p>
                            <p class="mb-2"><strong>إلى:</strong> ${targetUserName}</p>
                            <p class="mb-2"><strong>نوع النقل:</strong> <span class="badge ${transferType === 'positive' ? 'bg-success' : 'bg-warning'}">${transferTypeText}</span></p>
                            ${transferType === 'negative' ? `<p class="mb-2"><strong>ملاحظة:</strong> سيتم خصم نقاط المهمة من الموظف الحالي</p>` : `<p class="mb-2"><strong>ملاحظة:</strong> سيحصل الموظف الجديد على نقاط المهمة</p>`}
                            <div class="mt-3">
                                <small class="text-muted">${response.message}</small>
                            </div>
                        </div>
                    `,
                    icon: 'success',
                    confirmButtonText: 'تمام',
                    confirmButtonColor: '#28a745',
                    width: '450px'
                }).then(() => {
                        // إعادة تحميل الصفحة بعد إغلاق الـ Sweet Alert
                        location.reload();
                    });
                } else {
                    // Fallback إذا SweetAlert غير متوفر
                    console.warn('SweetAlert غير متوفر، سيتم استخدام alert عادي');
                    const taskTypeText = taskData.type === 'template' ? ' (مهمة قالب)' : ' (مهمة عادية)';
                    alert(`تم النقل ${transferTypeText} بنجاح!${taskTypeText}\nالمهمة: ${taskData.name}\nنُقلت من: ${taskData.currentUser}\nإلى: ${targetUserName}`);
                setTimeout(() => {
                    location.reload();
                    }, 1000);
                }

            } else {
                // حفظ بيانات المهمة في حالة الفشل أيضاً
                const taskData = {
                    name: currentTaskData?.name || 'مهمة غير معروفة',
                    type: currentTaskData?.type || 'عادية',
                    currentUser: currentTaskData?.currentUser || 'مستخدم غير معروف'
                };

                console.log('❌ فشل النقل', {
                    taskType: taskData.type,
                    taskId: currentTaskData?.id,
                    response: response,
                    message: response.message
                });

                // إغلاق الـ sidebar قبل عرض الرسالة
                closeTransferSidebar();

                // التحقق من وجود SweetAlert
                if (typeof Swal !== 'undefined') {
                    let errorDetails = '';

                    // إضافة تفاصيل إضافية حسب نوع الخطأ
                    if (response.error_type === 'same_user') {
                        errorDetails = `
                            <div class="alert alert-info mt-3">
                                <i class="fas fa-info-circle me-2"></i>
                                المهمة بالفعل مع هذا الموظف، لا يمكن نقلها لنفس الشخص
                            </div>
                        `;
                    } else if (response.error_type === 'role_mismatch' && response.from_role && response.to_role) {
                        errorDetails = `
                            <div class="alert alert-info mt-3">
                                <p class="mb-1"><i class="fas fa-user-tag me-2"></i><strong>دور المستخدم الأصلي:</strong> ${response.from_role}</p>
                                <p class="mb-0"><i class="fas fa-user-tag me-2"></i><strong>دور المستخدم المستهدف:</strong> ${response.to_role}</p>
                            </div>
                        `;
                    } else if (response.error_type === 'user_not_in_project') {
                        errorDetails = `
                            <div class="alert alert-info mt-3">
                                <i class="fas fa-info-circle me-2"></i>
                                المستخدم المستهدف غير مشارك في هذا المشروع
                            </div>
                        `;
                    } else if (response.error_type === 'return_to_original_owner') {
                        errorDetails = `
                            <div class="alert alert-info mt-3">
                                <i class="fas fa-info-circle me-2"></i>
                                لا يمكن إرجاع المهمة للموظف الذي تم نقلها منه أصلاً
                            </div>
                        `;
                    }

                    // عرض Sweet Alert لفشل النقل (response success = false)
                    Swal.fire({
                    title: 'فشل النقل! ❌',
                    html: `
                        <div class="text-center">
                            <div class="mb-3">
                                <i class="fas fa-times-circle fa-3x text-warning mb-3"></i>
                            </div>
                            <p class="mb-2"><strong>المهمة:</strong> ${taskData.name}</p>
                            <p class="mb-3"><strong>السبب:</strong></p>
                            <div class="alert alert-warning">
                                ${response.message || 'حدث خطأ غير محدد'}
                            </div>
                            ${errorDetails}
                        </div>
                    `,
                    icon: 'warning',
                    confirmButtonText: 'حسناً',
                    confirmButtonColor: '#ffc107',
                    width: '550px'
                    });
                } else {
                    // Fallback إذا SweetAlert غير متوفر
                    console.warn('SweetAlert غير متوفر، سيتم استخدام alert عادي');
                    alert(`فشل النقل!\nالمهمة: ${taskData.name}\nالسبب: ${response.message || 'حدث خطأ غير محدد'}`);
                }
            }
        })
        .fail(function(xhr) {
            console.log('❌ فشل الطلب - دخل الـ fail handler');
            console.log('xhr:', xhr);
            let message = 'حدث خطأ أثناء النقل';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }

            // حفظ بيانات المهمة في حالة الخطأ أيضاً
            const taskData = {
                name: currentTaskData?.name || 'مهمة غير معروفة',
                type: currentTaskData?.type || 'عادية',
                currentUser: currentTaskData?.currentUser || 'مستخدم غير معروف'
            };

            console.log('🚫 خطأ في طلب النقل', {
                taskType: taskData.type,
                taskId: currentTaskData?.id,
                status: xhr.status,
                statusText: xhr.statusText,
                responseJSON: xhr.responseJSON,
                message: message
            });

            // إغلاق الـ sidebar قبل عرض الرسالة
            closeTransferSidebar();

            // التحقق من وجود SweetAlert
            if (typeof Swal !== 'undefined') {
                // عرض Sweet Alert للخطأ
                Swal.fire({
                title: 'فشل النقل! ❌',
                html: `
                    <div class="text-center">
                        <div class="mb-3">
                            <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                        </div>
                        <p class="mb-2"><strong>المهمة:</strong> ${taskData.name}</p>
                        <p class="mb-3"><strong>سبب الفشل:</strong></p>
                        <div class="alert alert-danger">
                            ${message}
                        </div>
                    </div>
                `,
                icon: 'error',
                confirmButtonText: 'حسناً',
                confirmButtonColor: '#dc3545',
                width: '450px'
                });
            } else {
                // Fallback إذا SweetAlert غير متوفر
                console.warn('SweetAlert غير متوفر، سيتم استخدام alert عادي');
                alert(`فشل النقل!\nالمهمة: ${taskData.name}\nسبب الفشل: ${message}`);
            }
        })
        .always(function() {
            $('#confirmTransferBtn').prop('disabled', false).html('<i class="fas fa-exchange-alt me-2"></i>نقل المهمة');
        });
}

function showError(message) {
    $('#transferError').text(message).show();
    $('#transferSuccess').hide();
}

function showSuccess(message) {
    $('#transferSuccess').text(message).show();
    $('#transferError, #transferWarning').hide();
}

function showWarning(message) {
    if ($('#transferWarning').length === 0) {
        $('#transferSuccess').after('<div id="transferWarning" class="alert alert-warning d-none"></div>');
    }
    $('#transferWarning').text(message).show();
    $('#transferError, #transferSuccess').hide();
}

// Initialize event listeners when DOM is ready
$(document).ready(function() {
    console.log('🚀 بدء تحضير أحداث نقل المهام...', {
        'jQuery متوفر': typeof $ !== 'undefined',
        'SweetAlert متوفر': typeof Swal !== 'undefined',
        'الصفحة': window.location.pathname
    });
    // User input change handler
    $('#userInput').on('input change', function() {
        const selectedName = $(this).val().trim();
        const user = findUserByName(selectedName);

        if (user) {
            $('#toUserId').val(user.id);
            const userDetails = `${user.name} (${user.employee_id}) - ${user.current_points || 0} نقطة`;
            $('#selectedUserDetails').text(userDetails);
            $('#selectedUserInfo').fadeIn(300);
        } else {
            $('#toUserId').val('');
            $('#selectedUserInfo').fadeOut(300);
        }

        if ($('#toUserId').val()) {
            setTimeout(checkTransferability, 500);
        }
    });

    // Transfer type change handler
    $('input[name="transferType"]').on('change', function() {
        if ($('#toUserId').val()) {
            setTimeout(checkTransferability, 500);
        }
    });

    // Confirm transfer button
    $('#confirmTransferBtn').on('click', function() {
        executeTransfer();
    });

    // Escape key handler
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            const sidebar = document.getElementById('transferSidebar');
            if (sidebar && sidebar.classList.contains('show')) {
                closeTransferSidebar();
            }
        }
    });

    // ✅ زر نقل المهمة (table view)
    $(document).on('click', '.transfer-task:not(.kanban-card .transfer-task)', function(e) {
        e.preventDefault();
        e.stopPropagation();

        const taskType = $(this).data('task-type');
        const taskId = $(this).data('task-id');
        const taskUserId = $(this).data('task-user-id');
        const taskName = $(this).data('task-name');
        const currentUser = $(this).data('current-user');
        const mode = $(this).data('mode') || 'transfer';

        console.log('📤 نقل مهمة من table:', {taskType, taskId, taskUserId, taskName, currentUser, mode});

        if (typeof openTransferModal === 'function') {
            openTransferModal(taskType, taskId, taskName, currentUser, mode, taskUserId);
        } else {
            console.error('openTransferModal function not found');
        }
    });

    // ✅ زر إلغاء النقل
    $(document).on('click', '.cancel-transfer-task', function(e) {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        const taskType = $(this).data('task-type');
        const taskId = $(this).data('task-id');
        const taskName = $(this).data('task-name');

        console.log('🔙 طلب إلغاء نقل:', {taskType, taskId, taskName});

        Swal.fire({
            title: 'تأكيد إلغاء النقل',
            html: `
                <div class="text-center">
                    <i class="fas fa-undo fa-3x text-warning mb-3"></i>
                    <p class="mb-3">هل أنت متأكد من إلغاء نقل المهمة؟</p>
                    <p class="mb-2"><strong>المهمة:</strong> ${taskName}</p>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        سيتم إرجاع المهمة للموظف الأصلي وتعديل النقاط تلقائياً
                    </div>
                    <div class="form-group text-start mt-3">
                        <label for="cancelReason" class="form-label">سبب الإلغاء (اختياري):</label>
                        <textarea id="cancelReason" class="form-control" rows="3" placeholder="اكتب سبب إلغاء النقل..."></textarea>
                    </div>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'نعم، إلغاء النقل',
            cancelButtonText: 'تراجع',
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            width: '500px',
            preConfirm: () => {
                return $('#cancelReason').val();
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const cancelReason = result.value;
                cancelTransfer(taskType, taskId, taskName, cancelReason);
            }
        });

        return false;
    });
});

/**
 * إلغاء نقل مهمة
 */
function cancelTransfer(taskType, taskId, taskName, cancelReason) {
    const url = taskType === 'template' ?
        '/task-transfer/cancel-template-task-transfer' :
        '/task-transfer/cancel-task-transfer';

    const data = {
        [taskType === 'template' ? 'template_task_user_id' : 'task_user_id']: taskId,
        cancel_reason: cancelReason,
        _token: $('meta[name="csrf-token"]').attr('content')
    };

    console.log('📤 إرسال طلب إلغاء النقل:', {url, data});

    // عرض loader
    Swal.fire({
        title: 'جاري إلغاء النقل...',
        html: '<i class="fas fa-spinner fa-spin fa-3x text-primary"></i>',
        showConfirmButton: false,
        allowOutsideClick: false
    });

    $.post(url, data)
        .done(function(response) {
            console.log('✅ استجابة إلغاء النقل:', response);

            if (response.success) {
                Swal.fire({
                    title: 'تم إلغاء النقل! ✅',
                    html: `
                        <div class="text-center">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <p class="mb-2">${response.message}</p>
                            <div class="alert alert-success">
                                <i class="fas fa-info-circle me-2"></i>
                                تم إرجاع المهمة إلى <strong>${response.original_user}</strong>
                            </div>
                        </div>
                    `,
                    icon: 'success',
                    confirmButtonText: 'حسناً',
                    confirmButtonColor: '#28a745'
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    title: 'فشل إلغاء النقل! ❌',
                    text: response.message || 'حدث خطأ غير معروف',
                    icon: 'error',
                    confirmButtonText: 'حسناً',
                    confirmButtonColor: '#dc3545'
                });
            }
        })
        .fail(function(xhr) {
            console.error('❌ فشل طلب إلغاء النقل:', xhr);

            let message = 'حدث خطأ أثناء إلغاء النقل';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }

            Swal.fire({
                title: 'فشل إلغاء النقل! ❌',
                text: message,
                icon: 'error',
                confirmButtonText: 'حسناً',
                confirmButtonColor: '#dc3545'
            });
        });
}
