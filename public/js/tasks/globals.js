
window.tasksData = window.tasksData || {};
window.allUsers = window.allUsers || [];
window.isGraphicOnlyUser = window.isGraphicOnlyUser || false;
window.currentUserId = window.currentUserId || 0;
window.isShowAllView = window.isShowAllView || false;
function initializeGraphicUserFeatures() {
    if (window.isGraphicOnlyUser) {
        setTimeout(function() {
            if ($('#service_id').val() && typeof handleServiceChange === 'function') {
                if (typeof filterUsersByServiceOrRole === 'function') {
                    filterUsersByServiceOrRole();
                }
            }
        }, 500);
        $('#createTaskModal').on('shown.bs.modal', function() {
            setTimeout(function() {
                if ($('#service_id').val() && typeof handleServiceChange === 'function') {
                    handleServiceChange(false);
                    if (typeof filterUsersByServiceOrRole === 'function') {
                        filterUsersByServiceOrRole();
                    }
                }
            }, 200);
        });

        $('#editTaskModal').on('shown.bs.modal', function() {
            setTimeout(function() {
                if ($('#edit_service_id').val() && typeof handleServiceChange === 'function') {
                    handleServiceChange(true);
                    if (typeof filterUsersByServiceOrRole === 'function') {
                        filterUsersByServiceOrRole();
                    }
                }
            }, 200);
        });
    }

    setTimeout(function() {
        if ($('#service_id').val() && typeof filterUsersByServiceOrRole === 'function') {
            filterUsersByServiceOrRole();
        }
    }, 1000);
}

function initializeTooltips() {
    $('[data-bs-toggle="tooltip"]').tooltip();
}

function formatTime(hours, minutes) {
    return `${hours || 0}:${String(minutes || 0).padStart(2, '0')}`;
}

function getStatusTextArabic(status) {
    const statusMap = {
        'new': 'جديدة',
        'in_progress': 'قيد التنفيذ',
        'paused': 'متوقفة',
        'completed': 'مكتملة',
        'cancelled': 'ملغاة'
    };
    return statusMap[status] || status;
}

function initializeGlobals() {
    initializeTooltips();

    initializeGraphicUserFeatures();

    console.log('Global tasks functionality initialized');
    console.log('Available users:', window.allUsers.length);
    console.log('Is graphic only user:', window.isGraphicOnlyUser);
}

$(document).ready(function() {
    initializeGlobals();
});

window.initializeGlobals = initializeGlobals;
window.formatTime = formatTime;
window.getStatusTextArabic = getStatusTextArabic;
