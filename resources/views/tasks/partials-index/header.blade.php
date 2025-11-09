<div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">إدارة المهام</h5>
    <div class="d-flex align-items-center">
        <!-- View Toggle Buttons -->
        <div class="btn-group me-3" role="group" aria-label="View Mode">
            <button type="button" class="btn btn-outline-primary active" id="tableViewBtn">
                <i class="fas fa-table"></i> جدول
            </button>
            <button type="button" class="btn btn-outline-primary" id="kanbanViewBtn">
                <i class="fas fa-columns"></i> كانبان
            </button>
            <button type="button" class="btn btn-outline-primary" id="calendarViewBtn">
                <i class="fas fa-calendar-alt"></i> تقويم
            </button>
        </div>

        <!-- Show All / Paginated Toggle -->
        <div class="btn-group me-3" role="group" aria-label="Pagination Mode">
            @if(request('show_all') == '1')
            <a href="{{ route('tasks.index', array_merge(request()->except('show_all'), [])) }}"
                class="btn btn-outline-secondary" id="showPaginatedBtn">
                <i class="fas fa-list"></i> عرض مقسم
                <span class="badge bg-secondary ms-1">15/صفحة</span>
            </a>
            @else
            <a href="{{ route('tasks.index', array_merge(request()->all(), ['show_all' => '1'])) }}"
                class="btn btn-outline-success" id="showAllBtn">
                <i class="fas fa-expand-arrows-alt"></i> عرض الكل
                <span class="badge bg-success ms-1">{{ $tasks->total() ?? 0 }}</span>
            </a>
            @endif
        </div>

        <a href="{{ route('tasks.my-tasks') }}" class="btn btn-info me-2">
            <i class="fas fa-user"></i> مهامي
        </a>

        <!-- زر نظام الموافقة مع مؤشر -->
        @php
        $user = Auth::user();
        // التحقق من كون المستخدم HR أو Admin
        $userRoles = $user->roles->pluck('name')->toArray();
        $isHrOrAdmin = !empty(array_intersect(['super-admin', 'admin', 'hr'], $userRoles));

        // حساب المهام المنتظرة للموافقة
        $regularTasksQuery = \App\Models\TaskUser::where('status', 'completed')
        ->where('is_approved', false);

        $templateTasksQuery = \App\Models\TemplateTaskUser::where('status', 'completed')
        ->where('is_approved', false);

        // إذا لم يكن HR أو Admin، قيد النتائج للمشاريع التي يديرها فقط
        if (!$isHrOrAdmin) {
        $regularTasksQuery->whereHas('task', function ($query) use ($user) {
        $query->whereHas('project', function ($projectQuery) use ($user) {
        $projectQuery->where('manager', $user->name);
        });
        });

        $templateTasksQuery->whereHas('project', function ($query) use ($user) {
        $query->where('manager', $user->name);
        });
        }

        $pendingRegularTasks = $regularTasksQuery->count();
        $pendingTemplateTasks = $templateTasksQuery->count();
        $totalPendingApproval = $pendingRegularTasks + $pendingTemplateTasks;
        @endphp

        <div class="position-relative me-2">
            <a href="{{ route('task-deliveries.index') }}" class="btn btn-success">
                <i class="fas fa-check-circle"></i> تسليمات التاسكات
            </a>
            @if($totalPendingApproval > 0)
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                {{ $totalPendingApproval }}
            </span>
            @endif
        </div>

        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTaskModal">
            <i class="fas fa-plus"></i> إضافة مهمة جديدة
        </button>
    </div>
</div>

@if($isGraphicOnlyUser)
<div class="card-header">
    <div class="alert alert-info mb-0">
        <i class="fas fa-info-circle"></i>
        <strong>ملاحظة:</strong> يمكنك إنشاء وإدارة مهام الجرافيك والتصميم فقط.
    </div>
</div>
@endif

<!-- مهام المستخدم المنشأة -->
<div class="card-header">
    <div class="alert alert-info mb-0" style="border-left: 4px solid #3b82f6;">
        <i class="fas fa-info-circle"></i>
        <strong>ملاحظة:</strong> المهام ذات الحدود الزرقاء هي المهام التي أنشأتها أنت. يمكنك رؤية هذه المهام حتى لو كانت معينة لأشخاص خارج فريقك.
    </div>
</div>