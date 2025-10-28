

$(document).ready(function() {
    initializeFormHandlers();
});

function initializeFormHandlers() {
    // ✅ ربط كود المشروع بالـ select في فورم الإنشاء
    $('#create_project_code').on('input change', function() {
        const enteredCode = $(this).val().trim().toUpperCase();

        // إظهار/إخفاء زر المسح
        if (enteredCode) {
            $('#clear_create_project_code').show();
        } else {
            $('#clear_create_project_code').hide();
        }

        // البحث عن المشروع وتحديده تلقائياً
        let foundProject = false;
        $('#project_id option').each(function() {
            if ($(this).data('code') === enteredCode) {
                $('#project_id').val($(this).val()).trigger('change');
                foundProject = true;
                return false; // break loop
            }
        });

        // إذا لم يتم العثور على المشروع، امسح التحديد
        if (!foundProject && enteredCode) {
            $('#project_id').val('');
        }
    });

    // زر مسح كود المشروع
    $('#clear_create_project_code').click(function() {
        $('#create_project_code').val('').trigger('change');
        $('#project_id').val('');
        $(this).hide();
    });

    // عند اختيار مشروع من الـ select، تحديث الكود
    $('#project_id').change(function() {
        const selectedOption = $(this).find('option:selected');
        const projectCode = selectedOption.data('code');

        if (projectCode) {
            $('#create_project_code').val(projectCode);
            $('#clear_create_project_code').show();
        } else {
            $('#create_project_code').val('');
            $('#clear_create_project_code').hide();
        }
    });

    // تم إزالة أزرار إضافة/إزالة المستخدمين - الآن موظف واحد فقط

    $(document).on('submit', '#createTaskModal form', function(e) {
        handleCreateTaskSubmission(e);
    });

    $('#editTaskForm').submit(function(e) {
        handleEditTaskSubmission(e);
    });

    $('#is_flexible_time').change(function() {
        handleFlexibleTimeToggle();
        // تحديث hidden input
        $('#is_flexible_time_hidden').val($(this).is(':checked') ? '1' : '0');
    });

    // معالج المهام المرنة في نموذج التعديل
    $('#edit_is_flexible_time').change(function() {
        handleEditFlexibleTimeToggle();
        // تحديث hidden input
        $('#edit_is_flexible_time_hidden').val($(this).is(':checked') ? '1' : '0');
    });

    // معالج المهام الإضافية
    $('#is_additional_task').change(function() {
        // تحديث hidden input
        $('#is_additional_task_hidden').val($(this).is(':checked') ? '1' : '0');
    });

    // معالج المهام الإضافية في نموذج التعديل
    $('#edit_is_additional_task').change(function() {
        // تحديث hidden input
        $('#edit_is_additional_task_hidden').val($(this).is(':checked') ? '1' : '0');
    });

    // Initialize hidden input on page load
    if ($('#is_flexible_time_hidden').length > 0) {
        $('#is_flexible_time_hidden').val($('#is_flexible_time').is(':checked') ? '1' : '0');
    }

    if ($('#edit_is_flexible_time_hidden').length > 0) {
        $('#edit_is_flexible_time_hidden').val($('#edit_is_flexible_time').is(':checked') ? '1' : '0');
    }

    // Initialize additional task hidden inputs
    if ($('#is_additional_task_hidden').length > 0) {
        $('#is_additional_task_hidden').val($('#is_additional_task').is(':checked') ? '1' : '0');
    }

    if ($('#edit_is_additional_task_hidden').length > 0) {
        $('#edit_is_additional_task_hidden').val($('#edit_is_additional_task').is(':checked') ? '1' : '0');
    }
}

// تم إزالة دوال addUserRow و removeUserRow - الآن موظف واحد فقط

function handleCreateTaskSubmission(e) {
    e.preventDefault();

    $(e.target).find('input[name^="assigned_users"]').remove();

    const isFlexible = $('#is_flexible_time').is(':checked');
    const isAdditionalTask = $('#is_additional_task').is(':checked');

    // تحديث hidden input للتأكد من القيمة الصحيحة
    $('#is_flexible_time_hidden').val(isFlexible ? '1' : '0');
    $('#is_additional_task_hidden').val(isAdditionalTask ? '1' : '0');

    const userRows = $('#assignUsersContainer .user-assignment-row');
    let index = 0;

    userRows.each(function() {
        const userId = $(this).find('select[name="users[]"]').val();

        if (userId) {
            $(e.target).append('<input type="hidden" name="assigned_users[' + index + '][user_id]" value="' + userId + '">');
            $(e.target).append('<input type="hidden" name="assigned_users[' + index + '][role]" value="موظف">');

            if (!isFlexible) {
                $(e.target).append('<input type="hidden" name="assigned_users[' + index + '][estimated_hours]" value="0">');
                $(e.target).append('<input type="hidden" name="assigned_users[' + index + '][estimated_minutes]" value="0">');
            }

            index++;
        }
    });

    // ✅ إضافة البنود للفورم
    if (typeof createTaskItems !== 'undefined' && createTaskItems.length > 0) {
        $(e.target).append('<input type="hidden" name="items" value=\'' + JSON.stringify(createTaskItems) + '\'>');
    }

    // ✅ إرسال الفورم بـ AJAX عشان نعرض الأخطاء بـ SweetAlert
    const formData = new FormData(e.target);
    const submitBtn = $(e.target).find('button[type="submit"]');
    const originalBtnText = submitBtn.html();

    submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> جاري الحفظ...');

    $.ajax({
        url: $(e.target).attr('action'),
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        success: function(response) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'نجح!',
                    text: 'تم إنشاء المهمة بنجاح',
                    confirmButtonText: 'حسناً'
                }).then(() => {
                    window.location.href = response.redirect || '/tasks';
                });
            } else {
                window.location.href = response.redirect || '/tasks';
            }
        },
        error: function(xhr) {
            submitBtn.prop('disabled', false).html(originalBtnText);

            let errorMessage = 'حدث خطأ أثناء إنشاء المهمة';

            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            } else if (xhr.responseJSON && xhr.responseJSON.error) {
                errorMessage = xhr.responseJSON.error;
            } else if (xhr.responseText) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    errorMessage = response.message || response.error || errorMessage;
                } catch (e) {
                    // إذا فشل الـ parse، نستخدم الرسالة الافتراضية
                }
            }

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ!',
                    text: errorMessage,
                    confirmButtonText: 'حسناً'
                });
            } else {
                alert(errorMessage);
            }
        }
    });
}

function handleEditTaskSubmission(e) {
    e.preventDefault();

    $(e.target).find('input[name^="assigned_users"]').remove();

    const isFlexible = $('#edit_is_flexible_time').is(':checked');
    const isAdditionalTask = $('#edit_is_additional_task').is(':checked');

    $('#edit_is_flexible_time_hidden').val(isFlexible ? '1' : '0');
    $('#edit_is_additional_task_hidden').val(isAdditionalTask ? '1' : '0');

    const userRows = $('#edit_assignUsersContainer .user-assignment-row');
    let index = 0;

    userRows.each(function() {
        const userId = $(this).find('select[name="users[]"]').val();

        if (userId) {
            $(e.target).append('<input type="hidden" name="assigned_users[' + index + '][user_id]" value="' + userId + '">');
            $(e.target).append('<input type="hidden" name="assigned_users[' + index + '][role]" value="موظف">');

            if (!isFlexible) {
                $(e.target).append('<input type="hidden" name="assigned_users[' + index + '][estimated_hours]" value="0">');
                $(e.target).append('<input type="hidden" name="assigned_users[' + index + '][estimated_minutes]" value="0">');
            }

            index++;
        }
    });

    // ✅ إرسال الفورم بـ AJAX
    const formData = new FormData(e.target);
    const submitBtn = $(e.target).find('button[type="submit"]');
    const originalBtnText = submitBtn.html();

    submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> جاري التحديث...');

    $.ajax({
        url: $(e.target).attr('action'),
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        success: function(response) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'نجح!',
                    text: 'تم تحديث المهمة بنجاح',
                    confirmButtonText: 'حسناً'
                }).then(() => {
                    window.location.href = response.redirect || '/tasks';
                });
            } else {
                window.location.href = response.redirect || '/tasks';
            }
        },
        error: function(xhr) {
            submitBtn.prop('disabled', false).html(originalBtnText);

            let errorMessage = 'حدث خطأ أثناء تحديث المهمة';

            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            } else if (xhr.responseJSON && xhr.responseJSON.error) {
                errorMessage = xhr.responseJSON.error;
            }

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ!',
                    text: errorMessage,
                    confirmButtonText: 'حسناً'
                });
            } else {
                alert(errorMessage);
            }
        }
    });
}

function handleFlexibleTimeToggle() {
    const isFlexible = $('#is_flexible_time').is(':checked');
    const timeSection = $('#time_estimation_section');

    if (isFlexible) {
        timeSection.hide();
        $('#estimated_hours').val('').removeAttr('required');
        $('#estimated_minutes').val('').removeAttr('required');

        if (!$('#flexible_time_notice').length) {
            timeSection.after(`
                <div class="alert alert-info mt-2" id="flexible_time_notice">
                    <i class="fas fa-info-circle"></i>
                    <strong>مهمة مرنة:</strong> لن تحتاج لتحديد وقت مقدر. الوقت الفعلي الذي يقضيه المستخدم سيكون هو وقت المهمة.
                </div>
            `);
        }
    } else {
        const selectedService = $('#service_id option:selected');
        const serviceName = selectedService.data('service-name') || selectedService.text();
        const isGraphicService = serviceName && (serviceName.includes('جرافيك') || serviceName.includes('تصميم') || serviceName.includes('graphic') || serviceName.includes('design'));

        if (!isGraphicService) {
            timeSection.show();
            $('#estimated_hours').val('0').attr('required', true);
            $('#estimated_minutes').val('0').attr('required', true);
        }

        $('#flexible_time_notice').remove();
    }
}

function handleEditFlexibleTimeToggle() {
    const isFlexible = $('#edit_is_flexible_time').is(':checked');
    const timeSection = $('#edit_time_estimation_section');

    if (isFlexible) {
        timeSection.hide();
        $('#edit_estimated_hours').val('').removeAttr('required');
        $('#edit_estimated_minutes').val('').removeAttr('required');

        if (!$('#edit_flexible_time_notice').length) {
            timeSection.after(`
                <div class="alert alert-info mt-2" id="edit_flexible_time_notice">
                    <i class="fas fa-info-circle"></i>
                    <strong>مهمة مرنة:</strong> لن تحتاج لتحديد وقت مقدر. الوقت الفعلي الذي يقضيه المستخدم سيكون هو وقت المهمة.
                </div>
            `);
        }
    } else {
        const selectedService = $('#edit_service_id option:selected');
        const serviceName = selectedService.data('service-name') || selectedService.text();
        const isGraphicService = serviceName && (serviceName.includes('جرافيك') || serviceName.includes('تصميم') || serviceName.includes('graphic') || serviceName.includes('design'));

        if (!isGraphicService) {
            timeSection.show();
            $('#edit_estimated_hours').val('0').attr('required', true);
            $('#edit_estimated_minutes').val('0').attr('required', true);
        }

        $('#edit_flexible_time_notice').remove();
    }
}
