<script>
    // Global data for tasks - Setting variables from PHP
    window.currentUserId = {{ Auth::id() ?? 'null' }};
    window.isGraphicOnlyUser = {{ isset($isGraphicOnlyUser) && $isGraphicOnlyUser ? 'true' : 'false' }};
    window.isShowAllView = {{ request('show_all') == '1' ? 'true' : 'false' }};

    // Store all users globally for user management functions
    try {
        window.allUsers = {!! isset($users) ? json_encode($users) : '[]' !!};
        if (!Array.isArray(window.allUsers)) {
            window.allUsers = [];
        }
    } catch (error) {
        console.error('Error setting allUsers:', error);
        window.allUsers = [];
    }
</script>

<!-- SweetAlert2 JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Tasks JavaScript Modules -->
<script src="{{ asset('js/tasks/globals.js') }}"></script>
<script src="{{ asset('js/tasks/index.js') }}"></script>
<script src="{{ asset('js/tasks/filters.js') }}"></script>
<script src="{{ asset('js/tasks/modals.js') }}"></script>
<script src="{{ asset('js/tasks/forms.js') }}"></script>
<script src="{{ asset('js/tasks/graphic-tasks.js') }}"></script>
<script src="{{ asset('js/tasks/users-management.js') }}"></script>
<script src="{{ asset('js/tasks/kanban-board.js') }}"></script>
<script src="{{ asset('js/tasks/task-items-management.js') }}?v={{ time() }}"></script>

<!-- Tasks Index Calendar JavaScript -->
<script src="{{ asset('js/tasks/tasks-index-calendar.js') }}?v={{ time() }}"></script>

<!-- SweetAlert2 JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Task Transfer JavaScript -->
<script src="{{ asset('js/tasks/task-transfer.js') }}?v={{ time() }}"></script>

<!-- Task Transfer Error Handler -->
<script src="{{ asset('js/tasks/transfer-error-handler.js') }}?v={{ time() }}"></script>

<!-- Task Sidebar Core - Load first -->
<script src="{{ asset('js/projects/task-sidebar/core.js') }}?v={{ time() }}"></script>

<!-- Task Sidebar (مثل Asana) - Load after core -->
<script src="{{ asset('js/projects/task-sidebar.js') }}?v={{ time() }}"></script>
