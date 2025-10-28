
// Task Transfer Functions
let currentTaskData = null;
let availableUsers = [];

function openTransferModal(taskType, taskId, taskName, currentUserName, mode = 'transfer') {
    console.log('🔓 openTransferModal called', {taskType, taskId, taskName, currentUserName, mode});

    currentTaskData = {
        type: taskType,
        id: taskId,
        name: taskName,
        currentUser: currentUserName,
        mode: mode
    };

    console.log('✅ currentTaskData set:', currentTaskData);

    $('#taskType').val(taskType);
    $('#taskId').val(taskId);
    $('#taskName').text(taskName);
    $('#currentUser').text(currentUserName);

    $('#transferTaskForm')[0].reset();
    $('#taskType').val(taskType);
    $('#taskId').val(taskId);
    $('#transferError, #transferSuccess, #transferCheck').hide();

    loadAvailableUsers();

    loadCurrentUserPoints();

    if (mode === 'reassign') {
        // ✅ وضع تعديل المستلم: المهمة منقولة سابقاً ونريد فقط تغيير المستلم
        $('#positiveTransfer').prop('checked', true);
        $('#negativeTransfer').prop('checked', false);
        $('#transferPointsSection').hide();
        $('#transferPoints').removeAttr('required').val('0');
        $('#transferTypeSection').hide(); // إخفاء قسم نوع النقل بالكامل
        $('#transferSidebarSubtitle').text('📝 تعديل مستلم المهمة المنقولة');
        $('#confirmTransferBtn .btn-text').text('✅ تأكيد التعديل');

        // إضافة رسالة توضيحية
        if (!$('#reassignNote').length) {
            $('#transferSidebarSubtitle').after(`
                <div id="reassignNote" class="alert alert-info mt-2" style="font-size: 0.9rem;">
                    <i class="fas fa-info-circle me-2"></i>
                    هذه المهمة منقولة سابقاً، يمكنك فقط تغيير المستلم الحالي
                </div>
            `);
        }
    } else {
        $('#transferTypeSection').show(); // إظهار قسم نوع النقل
        $('#transferSidebarSubtitle').text('🔄 نقل المهمة لمستخدم آخر');
        $('#confirmTransferBtn .btn-text').text('✅ نقل المهمة');
        $('#reassignNote').remove(); // إزالة رسالة التوضيح إن وجدت
    }

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
    $('#selectedUserInfo').hide();
    $('#transferError, #transferSuccess, #transferWarning, #transferCheck').hide();
    $('#confirmTransferBtn').prop('disabled', false);

    // Reset transfer type to positive and hide points section
    $('#positiveTransfer').prop('checked', true);
    $('#transferPointsSection').hide();
    $('#transferPoints').removeAttr('required').val('0');
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

    // Populate datalist
    availableUsers.forEach(user => {
        const displayText = user.display_name || user.name;
        datalist.append(`<option value="${displayText}" data-user-id="${user.id}" data-employee-id="${user.employee_id}" data-points="${user.current_points || 0}"></option>`);
    });

    // ✅ إضافة: إذا كان هناك select dropdown، نملأها أيضاً
    const userSelect = $('#userSelect');
    if (userSelect.length) {
        userSelect.empty();
        userSelect.append('<option value="">اختر المستخدم...</option>');
        availableUsers.forEach(user => {
            const displayText = user.display_name || user.name;
            const optionText = `${user.name} (${user.employee_id}) - ${user.current_points || 0} نقطة`;
            userSelect.append(`<option value="${user.id}" data-name="${user.name}" data-employee-id="${user.employee_id}" data-points="${user.current_points || 0}">${optionText}</option>`);
        });
    }
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
        data.task_user_id = currentTaskData.id;
    }

    $.get('/task-transfer/check-transferability', data)
        .done(function(response) {
            $('#transferCheck').hide();
            if (response.success && response.data.can_transfer) {
                $('#confirmTransferBtn').prop('disabled', false);

                if (response.data.will_be_negative) {
                    showWarning(`تحذير: النقاط ستصبح ${response.data.points_after_transfer} (سالبة) بعد النقل`);
                } else {
                    showSuccess(`يمكن نقل المهمة. النقاط بعد النقل: ${response.data.points_after_transfer}`);
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
    console.log('🚀 executeTransfer called');
    console.log('currentTaskData:', currentTaskData);

    if (!currentTaskData) {
        console.error('❌ No currentTaskData');
        showError('خطأ في بيانات المهمة');
        return;
    }

    const transferType = $('input[name="transferType"]:checked').val();
    const toUserId = $('#toUserId').val();

    console.log('transferType:', transferType);
    console.log('toUserId:', toUserId);
    console.log('userInput value:', $('#userInput').val());

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
            showError('يرجى اختيار المستخدم المستقبل من القائمة');
        }
        return;
    }

    const formData = {
        to_user_id: toUserId,
        transfer_type: transferType,
        transfer_points: 0, // سيتم حسابها من نقاط المهمة في الباك اند
        reason: $('#transferReason').val(),
        _token: $('meta[name="csrf-token"]').attr('content')
    };

    let url;
    if (currentTaskData.type === 'template') {
        formData.template_task_user_id = currentTaskData.id;
        url = '/task-transfer/transfer-template-task';
    } else {
        formData.task_user_id = currentTaskData.id;
        url = '/task-transfer/transfer-task';
    }

    $('#confirmTransferBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>جاري النقل...');

    console.log('📤 Sending POST request to:', url);
    console.log('📤 Form data:', formData);

    $.post(url, formData)
        .done(function(response) {
            console.log('📥 Response received:', response);
            console.log('📥 response.success =', response.success);
            console.log('📥 response.error_type =', response.error_type);

            // حفظ بيانات المهمة قبل إغلاق السايد بار
            const taskData = {
                name: currentTaskData?.name || 'مهمة غير معروفة',
                type: currentTaskData?.type || 'عادية'
            };

                            if (response.success) {
                console.log('✅ Transfer successful, showing success SweetAlert');
                closeTransferSidebar();

                // عرض SweetAlert للنجاح
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'تم النقل بنجاح! ✅',
                        text: response.message,
                        icon: 'success',
                        confirmButtonText: 'تمام',
                        confirmButtonColor: '#28a745'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    showSuccess(response.message);
                    setTimeout(() => location.reload(), 2000);
                }
            } else {
                console.log('❌ Transfer failed, preparing error SweetAlert');
                console.log('❌ Error details:', {
                    message: response.message,
                    error_type: response.error_type,
                    from_role: response.from_role,
                    to_role: response.to_role
                });

                        closeTransferSidebar();

                // عرض SweetAlert للفشل مع تفاصيل إضافية
                if (typeof Swal !== 'undefined') {
                    console.log('✅ Swal is defined, showing error alert');
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

                    console.log('🔔 Calling Swal.fire with error details');
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
                    console.log('✅ Swal.fire called successfully');
                } else {
                    console.warn('⚠️ Swal is undefined, using fallback');
                    showError(response.message);
                }
                }
        })
        .fail(function(xhr) {
            let message = 'حدث خطأ أثناء النقل';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }

            // حفظ بيانات المهمة قبل إغلاق السايد بار
            const taskData = {
                name: currentTaskData?.name || 'مهمة غير معروفة',
                type: currentTaskData?.type || 'عادية'
            };

            closeTransferSidebar();

            // عرض SweetAlert للخطأ
            if (typeof Swal !== 'undefined') {
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
            showError(message);
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

$(document).ready(function() {

    // ✅ Event handler للـ select dropdown
    $('#userSelect').on('change', function() {
        const userId = $(this).val();
        console.log('🔽 User selected from dropdown, ID:', userId);

        if (userId) {
            $('#toUserId').val(userId);
            console.log('✅ Set toUserId to:', userId);

            const selectedOption = $(this).find('option:selected');
            const userName = selectedOption.data('name');
            const employeeId = selectedOption.data('employee-id');
            const points = selectedOption.data('points');

            const userDetails = `${userName} (${employeeId}) - ${points} نقطة`;
            $('#selectedUserDetails').text(userDetails);
            $('#selectedUserInfo').fadeIn(300);

            console.log('✅ User selected:', userDetails);

            // التحقق من إمكانية النقل
            if ($('#toUserId').val()) {
                setTimeout(checkTransferability, 500);
            }
        } else {
            $('#toUserId').val('');
            $('#selectedUserInfo').fadeOut(300);
        }
    });

    // ✅ Event handler للـ input مع datalist - مع دعم blur أيضاً
    $('#userInput').on('input change blur', function() {
        const selectedName = $(this).val().trim();
        console.log('🔍 Searching for user:', selectedName);
        console.log('📋 Available users count:', availableUsers.length);

        // طباعة أول 3 مستخدمين للـ debugging
        if (availableUsers.length > 0) {
            console.log('👥 Sample users:', availableUsers.slice(0, 3).map(u => ({
                name: u.name,
                display_name: u.display_name
            })));
        }

        const user = findUserByName(selectedName);
        console.log('👤 Found user:', user);

        if (user) {
            $('#toUserId').val(user.id);
            console.log('✅ Set toUserId to:', user.id);

            const userDetails = `${user.name} (${user.employee_id}) - ${user.current_points || 0} نقطة`;
            $('#selectedUserDetails').text(userDetails);
            $('#selectedUserInfo').fadeIn(300);

            console.log(`تم اختيار: ${userDetails}`);

            // التحقق من إمكانية النقل
            if ($('#toUserId').val()) {
                setTimeout(checkTransferability, 500);
            }
        } else {
            console.warn('❌ User not found for name:', selectedName);
            $('#toUserId').val('');
            $('#selectedUserInfo').fadeOut(300);
        }
    });

    $('#transferPoints').on('change input', function() {
        const points = parseInt($(this).val());

        if (points && points < 1) {
            $(this).val(1);
        }

        if ($('#toUserId').val() && $(this).val() && parseInt($(this).val()) >= 1) {
            setTimeout(checkTransferability, 500);
        }
    });

    // Transfer type change handler
    $('input[name="transferType"]').on('change', function() {
        const transferType = $(this).val();
        const transferPointsSection = $('#transferPointsSection');
        const transferPointsInput = $('#transferPoints');

        if (transferType === 'positive') {
            // Positive transfer - hide points section
            transferPointsSection.hide();
            transferPointsInput.removeAttr('required');
            transferPointsInput.val('0');
        } else {
            // Negative transfer - show points section
            transferPointsSection.show();
            transferPointsInput.attr('required', 'required');
            transferPointsInput.val('1');
        }

        // Check transferability when type changes
        if ($('#toUserId').val()) {
            setTimeout(checkTransferability, 500);
        }
    });

    $('#acknowledgeProjectBtn').on('click', function() {
        const projectId = $(this).data('project-id');
        const button = $(this);

        button.prop('disabled', true);
        button.html('<i class="fas fa-spinner fa-spin me-2"></i>جاري التأكيد...');

        $.ajax({
            url: `/projects/${projectId}/acknowledge`,
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, 'نجح التأكيد ✅');

                    $('.alert-warning').fadeOut(500, function() {
                        $(this).remove();
                    });

                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                } else {
                    toastr.error(response.message, 'فشل التأكيد ❌');
                    button.prop('disabled', false);
                    button.html('<i class="fas fa-check me-2"></i>تأكيد الاستلام');
                }
            },
            error: function(xhr) {
                const errorMessage = xhr.responseJSON?.message || 'حدث خطأ غير متوقع';
                toastr.error(errorMessage, 'فشل التأكيد ❌');
                button.prop('disabled', false);
                button.html('<i class="fas fa-check me-2"></i>تأكيد الاستلام');
            }
        });
    });

    // Transfer type change handler
    $('input[name="transferType"]').on('change', function() {
        const transferType = $(this).val();
        const transferPointsSection = $('#transferPointsSection');
        const transferPointsInput = $('#transferPoints');

        if (transferType === 'positive') {
            transferPointsSection.hide();
            transferPointsInput.removeAttr('required');
            transferPointsInput.val('0');
        } else {
            transferPointsSection.show();
            transferPointsInput.attr('required', 'required');
            transferPointsInput.val('1');
        }

        if ($('#toUserId').val()) {
            setTimeout(checkTransferability, 500);
        }
    });

    $('#confirmTransferBtn').on('click', function() {
        console.log('🔘 Confirm transfer button clicked');
        executeTransfer();
    });

    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            const sidebar = document.getElementById('transferSidebar');
            if (sidebar && sidebar.classList.contains('show')) {
                closeTransferSidebar();
            }
        }
    });

    // ✅ فتح Task Sidebar عند الضغط على الـ task card
    $(document).on('click', '.task-clickable', function(e) {
        // التأكد إن الضغط مش على زر إلغاء النقل أو أي زر تاني
        if ($(e.target).closest('.cancel-transfer-task, .transfer-btn, button').length > 0) {
            return; // لو الضغط على زر، متفتحش الـ sidebar
        }

        const taskType = $(this).data('sidebar-task-type');
        const taskUserId = $(this).data('sidebar-task-user-id');

        if (taskType && taskUserId) {
            openTaskSidebar(taskType, taskUserId);
        }
    });

    // ✅ زر إلغاء النقل
    $(document).on('click', '.cancel-transfer-task', function(e) {
        e.preventDefault();      // منع السلوك الافتراضي
        e.stopPropagation();     // منع فتح الـ task sidebar
        e.stopImmediatePropagation(); // منع أي event handlers تانية

        const taskType = $(this).data('task-type');
        const taskId = $(this).data('task-id');
        const taskName = $(this).data('task-name');

        if (typeof Swal !== 'undefined') {
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
                    executeCancelTransfer(taskType, taskId, taskName, cancelReason);
                }
            });
        } else {
            console.warn('SweetAlert غير متوفر');
            if (confirm(`هل تريد إلغاء نقل المهمة: ${taskName}?`)) {
                executeCancelTransfer(taskType, taskId, taskName, '');
            }
        }

        return false; // ✅ منع انتشار الـ event بشكل نهائي
    });
});

/**
 * تنفيذ إلغاء نقل مهمة
 */
function executeCancelTransfer(taskType, taskId, taskName, cancelReason) {
    // تحديد الـ URL والبيانات حسب نوع المهمة
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
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'جاري إلغاء النقل...',
            html: '<i class="fas fa-spinner fa-spin fa-3x text-primary"></i>',
            showConfirmButton: false,
            allowOutsideClick: false
        });
    }

    $.post(url, data)
        .done(function(response) {
            console.log('✅ استجابة إلغاء النقل:', response);

            if (response.success) {
                if (typeof Swal !== 'undefined') {
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
                    alert(`تم إلغاء النقل بنجاح!\n${response.message}`);
                    location.reload();
                }
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'فشل إلغاء النقل! ❌',
                        text: response.message || 'حدث خطأ غير معروف',
                        icon: 'error',
                        confirmButtonText: 'حسناً',
                        confirmButtonColor: '#dc3545'
                    });
                } else {
                    alert(`فشل إلغاء النقل!\n${response.message || 'حدث خطأ غير معروف'}`);
                }
            }
        })
        .fail(function(xhr) {
            console.error('❌ فشل طلب إلغاء النقل:', xhr);

            let message = 'حدث خطأ أثناء إلغاء النقل';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'فشل إلغاء النقل! ❌',
                    text: message,
                    icon: 'error',
                    confirmButtonText: 'حسناً',
                    confirmButtonColor: '#dc3545'
                });
            } else {
                alert(`فشل إلغاء النقل!\n${message}`);
            }
        });
}
