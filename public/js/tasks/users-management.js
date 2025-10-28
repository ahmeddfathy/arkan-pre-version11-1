$(document).ready(function() {
    initializeUsersManagement();

    if (typeof tasksData !== 'undefined' && tasksData.users) {
        window.allUsers = tasksData.users;
    } else {
        if (typeof window.allUsers !== 'undefined' && Array.isArray(window.allUsers)) {
            // do nothing
        } else {
            window.allUsers = [];
        }
    }

    if (!Array.isArray(window.allUsers)) {
        window.allUsers = [];
    }
});

function initializeUsersManagement() {
    $('#service_id, #edit_service_id').change(function() {
        const isEditModal = $(this).attr('id') === 'edit_service_id';
        const roleFilterId = isEditModal ? '#edit_role_filter' : '#role_filter';

        if ($(this).val()) {
            if ($(roleFilterId).length > 0) {
                $(roleFilterId).val('');
            }
        }

        setTimeout(function() {
            filterUsersByServiceOrRole();
        }, 100);
    });

    $('#role_filter, #edit_role_filter').change(function() {
        const isEditModal = $(this).attr('id') === 'edit_role_filter';
        const serviceFilterId = isEditModal ? '#edit_service_id' : '#service_id';

        if ($(this).val()) {
            $(serviceFilterId).val('');
        }

        filterUsersByServiceOrRole();
    });

    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1 && $(node).hasClass('user-assignment-row')) {
                        const isEditModal = $('#editTaskModal').hasClass('show');
                        const selectedServiceId = isEditModal ? $('#edit_service_id').val() : $('#service_id').val();
                        const selectedRole = isEditModal ? $('#edit_role_filter').val() : $('#role_filter').val();

                        if (selectedRole || selectedServiceId) {
                            setTimeout(function() {
                                filterUsersByServiceOrRole();
                            }, 100);
                        }
                    }
                });
            }
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
}

function applyInitialUserFiltering() {
    const serviceId = $('#service_id').val();
    const roleName = $('#role_filter').val();

    if (serviceId || roleName) {
        filterUsersByServiceOrRole();
    }
}

function filterUsersByServiceOrRole() {
    const isEditModal = $('#editTaskModal').hasClass('show');
    const serviceId = isEditModal ? $('#edit_service_id').val() : $('#service_id').val();
    const roleName = isEditModal ? $('#edit_role_filter').val() : $('#role_filter').val();
    const containerSelector = isEditModal ? '#edit_assignUsersContainer' : '#assignUsersContainer';

    if (roleName && roleName !== '') {
        $.ajax({
            url: '/role/' + roleName + '/users',
            method: 'GET',
            dataType: 'json',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(data) {
                if (data.success && data.users) {
                    updateUserSelects(data.users, containerSelector);
                } else {
                    if (data.message) {
                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ!',
                            text: data.message,
                            confirmButtonText: 'موافق',
                            confirmButtonColor: '#e74c3c'
                        });
                    }
                    resetUserSelects(containerSelector);
                }
            },
            error: function(xhr, status, error) {
                resetUserSelects(containerSelector);
            }
        });
    } else if (serviceId && serviceId !== '') {
        $.ajax({
            url: '/service/' + serviceId + '/users',
            method: 'GET',
            dataType: 'json',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(data) {
                if (data.success && data.users) {
                    updateUserSelects(data.users, containerSelector);
                } else {
                    if (data.message) {
                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ!',
                            text: data.message,
                            confirmButtonText: 'موافق',
                            confirmButtonColor: '#e74c3c'
                        });
                    }
                    resetUserSelects(containerSelector);
                }
            },
            error: function(xhr, status, error) {
                resetUserSelects(containerSelector);
            }
        });
    } else {
        resetUserSelects(containerSelector);
    }
}

function updateUserSelects(users, containerSelector = null) {
    if (!Array.isArray(users)) {
        resetUserSelects(containerSelector);
        return;
    }

    if (users.length === 0) {
        resetUserSelects(containerSelector);
        return;
    }

    const selectors = containerSelector ?
        [containerSelector + ' .user-select'] :
        ['#assignUsersContainer .user-select', '#edit_assignUsersContainer .user-select'];

    selectors.forEach(selector => {
        $(selector).each(function() {
            const currentValue = $(this).val();
            $(this).empty();
            $(this).append('<option value="">اختر موظف</option>');

            users.forEach(function(user) {
                const displayName = user.display_name || user.name;
                const option = $('<option></option>')
                    .val(user.id)
                    .text(displayName);
                $(selector).append(option);
            });

            if (currentValue && $(this).find('option[value="' + currentValue + '"]').length > 0) {
                $(this).val(currentValue);
            }
        });
    });
}

function resetUserSelects(containerSelector = null) {
    let originalUsers = window.allUsers || [];
    if (!Array.isArray(originalUsers)) {
        originalUsers = [];
    }

    const selectors = containerSelector ?
        [containerSelector + ' .user-select'] :
        ['#assignUsersContainer .user-select', '#edit_assignUsersContainer .user-select'];

    selectors.forEach(selector => {
        $(selector).each(function() {
            const currentValue = $(this).val();
            $(this).empty();
            $(this).append('<option value="">اختر موظف</option>');

            if (originalUsers.length > 0) {
                originalUsers.forEach(function(user) {
                    const option = $('<option></option>')
                        .val(user.id)
                        .text(user.display_name || user.name);
                    $(selector).append(option);
                });
            }

            if (currentValue && $(this).find('option[value="' + currentValue + '"]').length > 0) {
                $(this).val(currentValue);
            }
        });
    });
}
