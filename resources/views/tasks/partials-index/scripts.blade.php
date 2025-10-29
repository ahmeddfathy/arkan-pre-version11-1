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

<!-- Tasks Statistics JavaScript -->
<script>
$(document).ready(function() {
    // ✅ تفاعل كاردات الإحصائيات مع فلترة الجدول
    $('.stats-card[data-filter]').on('click', function() {
        const filterValue = $(this).data('filter');

        // إزالة active من جميع الكاردات
        $('.stats-card').removeClass('active-filter');

        // إضافة active للكارد المحدد
        $(this).addClass('active-filter');

        // تطبيق الفلتر
        if (filterValue === 'all') {
            // إعادة تعيين فلتر الحالة
            $('#statusFilter').val('').trigger('change');
        } else {
            // تطبيق فلتر الحالة المحدد
            $('#statusFilter').val(filterValue).trigger('change');
        }

        // تحديث الإحصائيات بعد الفلترة
        updateTasksStatistics();
    });

    // ✅ تحديث الإحصائيات عند تطبيق أي فلتر
    $('#projectFilter, #serviceFilter, #statusFilter, #createdByFilter, #assignedUserFilter, #searchInput, #dateFromFilter, #dateToFilter').on('change keyup', function() {
        updateTasksStatistics();
    });

    // ✅ دالة تحديث الإحصائيات بناءً على المهام الظاهرة
    function updateTasksStatistics() {
        const stats = {
            total: 0,
            new: 0,
            in_progress: 0,
            paused: 0,
            completed: 0,
            cancelled: 0
        };

        // حساب المهام الظاهرة فقط
        $('#tasksTable tbody tr:visible').each(function() {
            stats.total++;
            const status = $(this).data('status');
            if (stats.hasOwnProperty(status)) {
                stats[status]++;
            }
        });

        // تحديث القيم في الكاردات
        $('#stat-total').text(stats.total);
        $('#stat-new').text(stats.new);
        $('#stat-in-progress').text(stats.in_progress);
        $('#stat-paused').text(stats.paused);
        $('#stat-completed').text(stats.completed);

        // تحديث النسب المئوية
        const totalTasks = stats.total || 1; // تجنب القسمة على صفر

        // New tasks percentage
        const newPercentage = Math.round((stats.new / totalTasks) * 100);
        $('.new-tasks .stats-progress-fill').css('width', newPercentage + '%');
        $('.new-tasks .stats-percentage').text(newPercentage + '%');

        // In progress percentage
        const inProgressPercentage = Math.round((stats.in_progress / totalTasks) * 100);
        $('.in-progress-tasks .stats-progress-fill').css('width', inProgressPercentage + '%');
        $('.in-progress-tasks .stats-percentage').text(inProgressPercentage + '%');

        // Completed percentage
        const completedPercentage = Math.round((stats.completed / totalTasks) * 100);
        $('.completed-tasks .stats-progress-fill').css('width', completedPercentage + '%');
        $('.completed-tasks .stats-percentage').text(completedPercentage + '%');

        // Paused percentage
        const pausedPercentage = Math.round((stats.paused / totalTasks) * 100);
        $('.paused-tasks .stats-progress-fill').css('width', pausedPercentage + '%');
        $('.paused-tasks .stats-percentage').text(pausedPercentage + '%');
    }

    // ✅ تحديث الإحصائيات عند تحميل الصفحة
    updateTasksStatistics();

    // ✅ إضافة تأثيرات hover على الكاردات
    $('.stats-card[data-filter]').hover(
        function() {
            $(this).css({
                'transform': 'translateY(-8px) translateZ(0)',
                'box-shadow': '0 8px 30px rgba(0, 0, 0, 0.15)'
            });
        },
        function() {
            if (!$(this).hasClass('active-filter')) {
                $(this).css({
                    'transform': 'translateZ(0)',
                    'box-shadow': '0 4px 15px rgba(0, 0, 0, 0.08)'
                });
            }
        }
    );
});
</script>
