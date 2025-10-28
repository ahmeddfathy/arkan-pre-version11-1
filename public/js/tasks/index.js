
window.allUsers = [];

$(document).ready(function() {
    initializeTasksIndex();
});

function initializeTasksIndex() {
    if (typeof tasksData !== 'undefined') {
        window.allUsers = tasksData.users || [];
    } else {
        if (typeof window.tasksData !== 'undefined') {
            window.allUsers = window.tasksData.users || [];
        } else {
            window.allUsers = [];
        }
    }

    $('[data-bs-toggle="tooltip"]').tooltip();
}
