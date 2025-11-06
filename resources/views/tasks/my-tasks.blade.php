@extends('layouts.app')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/my-tasks.css') }}">
<!-- My Tasks Kanban CSS -->
<link rel="stylesheet" href="{{ asset('css/tasks/my-tasks-kanban.css') }}">
<!-- Projects Kanban CSS for Notes Indicator -->
<link rel="stylesheet" href="{{ asset('css/projects/projects-kanban.css') }}">
<!-- My Tasks Calendar CSS -->
<link rel="stylesheet" href="{{ asset('css/tasks/my-tasks-calendar.css') }}">
<!-- My Tasks Statistics CSS -->
<link rel="stylesheet" href="{{ asset('css/tasks/my-tasks-stats.css') }}">
<!-- SweetAlert2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<!-- Task Revisions CSS -->
<link rel="stylesheet" href="{{ asset('css/task-revisions.css') }}">
@endpush
@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">مهامي</h5>
                    <div class="d-flex align-items-center">
                        <!-- Total Timer Display (only visible in Kanban view) -->
                        <div id="myTasksTotalTimerContainer" class="me-4" style="display: none;">
                            <div class="d-flex align-items-center bg-primary text-white px-3 py-1 rounded-pill">
                                <div class="me-2">
                                    <small>الوقت الإجمالي:</small>
                                </div>
                                <div>
                                    <span id="myTasksTotalTimer" class="fs-6 fw-bold">00:00:00</span>
                                </div>
                            </div>
                        </div>

                        <!-- View Toggle Buttons -->
                        <div class="btn-group me-3" role="group" aria-label="View Mode">
                            <button type="button" class="btn btn-outline-primary active" id="myTasksTableViewBtn">
                                <i class="fas fa-table"></i> جدول
                            </button>
                            <button type="button" class="btn btn-outline-primary" id="myTasksKanbanViewBtn">
                                <i class="fas fa-columns"></i> كانبان
                            </button>
                            <button type="button" class="btn btn-outline-primary" id="myTasksCalendarViewBtn">
                                <i class="fas fa-calendar-alt"></i> تقويم
                            </button>
                        </div>

                        <a href="{{ route('tasks.index') }}" class="btn btn-secondary">
                            <i class="fas fa-list"></i> جميع المهام
                        </a>
                    </div>
                </div>
                <div class="card-body my-tasks-loading" data-current-user-id="{{ auth()->id() }}">
                    @php
                    // حساب الإحصائيات
                    $statsData = [
                    'total' => 0,
                    'new' => 0,
                    'in_progress' => 0,
                    'paused' => 0,
                    'completed' => 0,
                    'cancelled' => 0,
                    'transferred' => 0,
                    'estimated_hours' => 0,
                    'actual_hours' => 0,
                    'total_points' => 0
                    ];

                    foreach($allTasks as $task) {
                    if (is_array($task)) {
                    $task = (object) $task;
                    if (isset($task->pivot) && is_array($task->pivot)) {
                    $task->pivot = (object) $task->pivot;
                    }
                    }

                    $isTemplate = isset($task->is_template) && $task->is_template;
                    $status = $isTemplate ? ($task->pivot->status ?? $task->status ?? 'new') : (isset($task->pivot->status) ? $task->pivot->status : 'new');

                    $statsData['total']++;

                    // عد المهام حسب الحالة
                    if (isset($task->is_transferred) && $task->is_transferred) {
                    $statsData['transferred']++;
                    } elseif (isset($statsData[$status])) {
                    $statsData[$status]++;
                    }

                    // حساب الوقت
                    $estimatedHours = $isTemplate ? ($task->pivot->estimated_hours ?? 0) : (isset($task->pivot->estimated_hours) ? $task->pivot->estimated_hours : 0);
                    $estimatedMinutes = $isTemplate ? ($task->pivot->estimated_minutes ?? 0) : (isset($task->pivot->estimated_minutes) ? $task->pivot->estimated_minutes : 0);
                    $actualHours = $isTemplate ? ($task->pivot->actual_hours ?? 0) : (isset($task->pivot->actual_hours) ? $task->pivot->actual_hours : 0);
                    $actualMinutes = $isTemplate ? ($task->pivot->actual_minutes ?? 0) : (isset($task->pivot->actual_minutes) ? $task->pivot->actual_minutes : 0);

                    // تحويل لساعات عشرية
                    $statsData['estimated_hours'] += $estimatedHours + ($estimatedMinutes / 60);
                    $statsData['actual_hours'] += $actualHours + ($actualMinutes / 60);

                    // حساب النقاط
                    $statsData['total_points'] += $task->points ?? 10;
                    }

                    // حساب النسب المئوية
                    $completionPercentage = $statsData['total'] > 0 ? round(($statsData['completed'] / $statsData['total']) * 100) : 0;
                    $inProgressPercentage = $statsData['total'] > 0 ? round(($statsData['in_progress'] / $statsData['total']) * 100) : 0;
                    $timeEfficiencyPercentage = $statsData['estimated_hours'] > 0 ? round(($statsData['actual_hours'] / $statsData['estimated_hours']) * 100) : 0;
                    @endphp

                    <!-- Statistics Cards -->
                    <div class="stats-cards-container">
                        <!-- Total Tasks Card -->
                        <div class="stats-card total-tasks" data-filter="all">
                            <div class="stats-card-header">
                                <div class="stats-card-icon">
                                    <i class="fas fa-tasks"></i>
                                </div>
                                <div class="stats-card-badge">الكل</div>
                            </div>
                            <div class="stats-card-body">
                                <div class="stats-card-title">إجمالي المهام</div>
                                <div class="stats-card-value" id="stat-total">{{ $statsData['total'] }}</div>
                            </div>
                            <div class="stats-card-footer">
                                <div class="stats-progress">
                                    <div class="stats-progress-bar">
                                        <div class="stats-progress-fill" style="width: 100%;"></div>
                                    </div>
                                </div>
                                <span class="stats-percentage">100%</span>
                            </div>
                        </div>

                        <!-- New Tasks Card -->
                        <div class="stats-card new-tasks" data-filter="new">
                            <div class="stats-card-header">
                                <div class="stats-card-icon">
                                    <i class="fas fa-circle-plus"></i>
                                </div>
                                <div class="stats-card-badge">جديدة</div>
                            </div>
                            <div class="stats-card-body">
                                <div class="stats-card-title">مهام جديدة</div>
                                <div class="stats-card-value" id="stat-new">{{ $statsData['new'] }}</div>
                            </div>
                            <div class="stats-card-footer">
                                <div class="stats-progress">
                                    <div class="stats-progress-bar">
                                        <div class="stats-progress-fill" style="width: {{ $statsData['total'] > 0 ? round(($statsData['new'] / $statsData['total']) * 100) : 0 }}%;"></div>
                                    </div>
                                </div>
                                <span class="stats-percentage">{{ $statsData['total'] > 0 ? round(($statsData['new'] / $statsData['total']) * 100) : 0 }}%</span>
                            </div>
                        </div>

                        <!-- In Progress Tasks Card -->
                        <div class="stats-card in-progress-tasks" data-filter="in_progress">
                            <div class="stats-card-header">
                                <div class="stats-card-icon">
                                    <i class="fas fa-play-circle"></i>
                                </div>
                                <div class="stats-card-badge">قيد التنفيذ</div>
                            </div>
                            <div class="stats-card-body">
                                <div class="stats-card-title">قيد التنفيذ</div>
                                <div class="stats-card-value" id="stat-in-progress">{{ $statsData['in_progress'] }}</div>
                            </div>
                            <div class="stats-card-footer">
                                <div class="stats-progress">
                                    <div class="stats-progress-bar">
                                        <div class="stats-progress-fill" style="width: {{ $inProgressPercentage }}%;"></div>
                                    </div>
                                </div>
                                <span class="stats-percentage">{{ $inProgressPercentage }}%</span>
                            </div>
                        </div>

                        <!-- Completed Tasks Card -->
                        <div class="stats-card completed-tasks" data-filter="completed">
                            <div class="stats-card-header">
                                <div class="stats-card-icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="stats-card-badge">مكتملة</div>
                            </div>
                            <div class="stats-card-body">
                                <div class="stats-card-title">مهام مكتملة</div>
                                <div class="stats-card-value" id="stat-completed">{{ $statsData['completed'] }}</div>
                            </div>
                            <div class="stats-card-footer">
                                <div class="stats-progress">
                                    <div class="stats-progress-bar">
                                        <div class="stats-progress-fill" style="width: {{ $completionPercentage }}%;"></div>
                                    </div>
                                </div>
                                <span class="stats-percentage">{{ $completionPercentage }}%</span>
                            </div>
                        </div>

                        <!-- Paused Tasks Card -->
                        <div class="stats-card paused-tasks" data-filter="paused">
                            <div class="stats-card-header">
                                <div class="stats-card-icon">
                                    <i class="fas fa-pause-circle"></i>
                                </div>
                                <div class="stats-card-badge">متوقفة</div>
                            </div>
                            <div class="stats-card-body">
                                <div class="stats-card-title">مهام متوقفة</div>
                                <div class="stats-card-value" id="stat-paused">{{ $statsData['paused'] }}</div>
                            </div>
                            <div class="stats-card-footer">
                                <div class="stats-progress">
                                    <div class="stats-progress-bar">
                                        <div class="stats-progress-fill" style="width: {{ $statsData['total'] > 0 ? round(($statsData['paused'] / $statsData['total']) * 100) : 0 }}%;"></div>
                                    </div>
                                </div>
                                <span class="stats-percentage">{{ $statsData['total'] > 0 ? round(($statsData['paused'] / $statsData['total']) * 100) : 0 }}%</span>
                            </div>
                        </div>

                        <!-- Time Tracking Card -->
                        <div class="stats-card time-tracking">
                            <div class="stats-card-header">
                                <div class="stats-card-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="stats-card-badge">الوقت</div>
                            </div>
                            <div class="stats-card-body">
                                <div class="stats-card-title">متابعة الوقت</div>
                                <div class="time-comparison">
                                    <div class="time-item">
                                        <span class="time-label">مقدر:</span>
                                        <span class="time-value">{{ floor($statsData['estimated_hours']) }}س {{ round(($statsData['estimated_hours'] - floor($statsData['estimated_hours'])) * 60) }}د</span>
                                    </div>
                                    <div class="time-item">
                                        <span class="time-label">فعلي:</span>
                                        <span class="time-value">{{ floor($statsData['actual_hours']) }}س {{ round(($statsData['actual_hours'] - floor($statsData['actual_hours'])) * 60) }}د</span>
                                    </div>
                                </div>
                            </div>
                            <div class="stats-card-footer">
                                <div class="stats-progress">
                                    <div class="stats-progress-bar">
                                        <div class="stats-progress-fill" style="width: {{ min($timeEfficiencyPercentage, 100) }}%;"></div>
                                    </div>
                                </div>
                                <span class="stats-percentage">{{ $timeEfficiencyPercentage }}%</span>
                            </div>
                        </div>

                        <!-- Transferred Tasks Card (إذا كان هناك مهام منقولة) -->
                        @if($statsData['transferred'] > 0)
                        <div class="stats-card transferred-tasks" data-filter="transferred">
                            <div class="stats-card-header">
                                <div class="stats-card-icon">
                                    <i class="fas fa-exchange-alt"></i>
                                </div>
                                <div class="stats-card-badge">منقولة</div>
                            </div>
                            <div class="stats-card-body">
                                <div class="stats-card-title">مهام منقولة</div>
                                <div class="stats-card-value" id="stat-transferred">{{ $statsData['transferred'] }}</div>
                            </div>
                            <div class="stats-card-footer">
                                <div class="stats-progress">
                                    <div class="stats-progress-bar">
                                        <div class="stats-progress-fill" style="width: {{ $statsData['total'] > 0 ? round(($statsData['transferred'] / $statsData['total']) * 100) : 0 }}%;"></div>
                                    </div>
                                </div>
                                <span class="stats-percentage">{{ $statsData['total'] > 0 ? round(($statsData['transferred'] / $statsData['total']) * 100) : 0 }}%</span>
                            </div>
                        </div>
                        @endif
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="projectCodeFilter">
                                    <i class="fas fa-hashtag"></i> كود المشروع
                                </label>
                                <div class="input-group">
                                    <input type="text"
                                        class="form-control"
                                        id="projectCodeFilter"
                                        list="projectCodesList"
                                        placeholder="اكتب أو اختر..."
                                        autocomplete="off">
                                    <button class="btn btn-outline-secondary"
                                        type="button"
                                        id="clearProjectCode"
                                        style="display: none;"
                                        title="مسح">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <datalist id="projectCodesList">
                                    @foreach($projects->whereNotNull('code') as $project)
                                    <option value="{{ $project->code }}">{{ $project->name }}</option>
                                    @endforeach
                                </datalist>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="projectFilter">اسم المشروع</label>
                                <select class="form-control" id="projectFilter">
                                    <option value="">جميع المشاريع</option>
                                    @foreach($projects as $project)
                                    <option value="{{ $project->id }}"
                                        data-code="{{ $project->code ?? '' }}"
                                        data-name="{{ $project->name }}">
                                        {{ $project->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="statusFilter">الحالة</label>
                                <select class="form-control" id="statusFilter">
                                    <option value="">جميع الحالات</option>
                                    <option value="new">جديدة</option>
                                    <option value="in_progress">قيد التنفيذ</option>
                                    <option value="paused">متوقفة</option>
                                    <option value="completed">مكتملة</option>
                                    <option value="cancelled">ملغاة</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="searchInput">بحث</label>
                                <input type="text" class="form-control" id="searchInput" placeholder="ابحث...">
                            </div>
                        </div>
                    </div>

                    <!-- ✅ صف جديد لفلترة التاريخ -->
                    <div class="row mb-3">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="dateTypeFilter">
                                    <i class="fas fa-filter"></i> نوع التاريخ
                                </label>
                                <select class="form-control" id="dateTypeFilter">
                                    <option value="deadline">Deadline</option>
                                    <option value="created_at">تاريخ الإنشاء</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="dateFrom" id="dateFromLabel">
                                    <i class="fas fa-calendar-alt"></i> من Deadline
                                </label>
                                <input type="date" class="form-control" id="dateFrom">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="dateTo" id="dateToLabel">
                                    <i class="fas fa-calendar-alt"></i> إلى Deadline
                                </label>
                                <input type="date" class="form-control" id="dateTo">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="button" class="btn btn-outline-secondary btn-block w-100" id="clearDateFilter">
                                    <i class="fas fa-times"></i> مسح التاريخ
                                </button>
                            </div>
                        </div>
                    </div>


                    <!-- Table View -->
                    <div id="myTasksTableView" class="table-responsive">
                        <table class="table align-items-center mb-0" id="myTasksTable">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">المهمة</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">المشروع</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">دوري</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">الوقت المقدر</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">الوقت المستغرق</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">الحالة</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">تاريخ الاستحقاق</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($allTasks as $task)
                                @php
                                // تحويل array إلى object إذا لزم الأمر
                                if (is_array($task)) {
                                $task = (object) $task;
                                if (isset($task->pivot) && is_array($task->pivot)) {
                                $task->pivot = (object) $task->pivot;
                                }
                                if (isset($task->project) && is_array($task->project)) {
                                $task->project = (object) $task->project;
                                }
                                if (isset($task->service) && is_array($task->service)) {
                                $task->service = (object) $task->service;
                                }
                                }

                                // تحديد نوع المهمة والحصول على المعرف الصحيح
                                $isTemplate = isset($task->is_template) && $task->is_template;

                                // تحديد المعرف الصحيح حسب نوع المهمة
                                if ($isTemplate) {
                                // For template tasks: use the TemplateTaskUser ID
                                $taskUserId = $task->id ?? 0;
                                $originalTaskId = isset($task->templateTask->id) ? $task->templateTask->id : ($task->id ?? 0);
                                } else {
                                // For regular tasks: use pivot ID if available, otherwise use a fallback
                                $taskUserId = (isset($task->pivot->id) && $task->pivot->id) ? $task->pivot->id : ($task->id ?? 0);
                                $originalTaskId = $task->id ?? 0;

                                // إذا لم يوجد pivot ID، نحاول إنشاء معرف بديل
                                if (!isset($task->pivot->id) || !$task->pivot->id) {
                                // استخدام معرف المهمة مع معرف المستخدم كمعرف مؤقت
                                $taskUserId = 'task_' . ($task->id ?? 0) . '_user_' . auth()->id();
                                }
                                }

                                $status = $isTemplate ? ($task->pivot->status ?? $task->status ?? 'new') : (isset($task->pivot->status) ? $task->pivot->status : 'new');
                                $userRole = $isTemplate ? ($task->pivot->role ?? 'منفذ') : (isset($task->pivot->role) ? $task->pivot->role : 'غير محدد');
                                $estimatedHours = $isTemplate ? ($task->pivot->estimated_hours ?? 0) : (isset($task->pivot->estimated_hours) ? $task->pivot->estimated_hours : 0);
                                $estimatedMinutes = $isTemplate ? ($task->pivot->estimated_minutes ?? 0) : (isset($task->pivot->estimated_minutes) ? $task->pivot->estimated_minutes : 0);
                                $actualHours = $isTemplate ? ($task->pivot->actual_hours ?? 0) : (isset($task->pivot->actual_hours) ? $task->pivot->actual_hours : 0);
                                $actualMinutes = $isTemplate ? ($task->pivot->actual_minutes ?? 0) : (isset($task->pivot->actual_minutes) ? $task->pivot->actual_minutes : 0);

                                // الحصول على started_at للتايمر (نفس نظام المشاريع)
                                $startedAt = null;
                                if ($status === 'in_progress') {
                                if ($isTemplate) {
                                // Template tasks: check started_at in TemplateTaskUser
                                if (isset($task->started_at) && $task->started_at) {
                                $startedAt = strtotime($task->started_at) * 1000; // Convert to milliseconds
                                }
                                } else {
                                // Regular tasks: check start_date in TaskUser
                                if (isset($task->pivot->start_date) && $task->pivot->start_date) {
                                $startedAt = strtotime($task->pivot->start_date) * 1000; // Convert to milliseconds
                                }
                                }
                                }

                                // الحصول على الدقائق المحفوظة في الداتابيز (نفس نظام المشاريع)
                                // ✅ استخدام المتغيرات المحسوبة بالفعل بدلاً من إعادة الحساب
                                $dbMinutes = ($actualHours * 60) + $actualMinutes;

                                // تحضير تاريخ الاستحقاق
                                $dueDate = 'غير محدد';

                                // ✅ TemplateTasks تستخدم 'deadline' بينما المهام العادية تستخدم 'due_date'
                                $deadlineField = $isTemplate ? 'deadline' : 'due_date';

                                if (isset($task->{$deadlineField}) && $task->{$deadlineField}) {
                                $deadlineValue = $task->{$deadlineField};

                                if (is_string($deadlineValue)) {
                                // إذا كان string، نحاول تحويله إلى تاريخ
                                try {
                                $dueDate = date('Y-m-d', strtotime($deadlineValue));
                                } catch (Exception $e) {
                                $dueDate = $deadlineValue;
                                }
                                } elseif (is_object($deadlineValue) && method_exists($deadlineValue, 'format')) {
                                $dueDate = $deadlineValue->format('Y-m-d');
                                } elseif (is_object($deadlineValue) && method_exists($deadlineValue, 'toDateString')) {
                                $dueDate = $deadlineValue->toDateString();
                                }
                                }

                                // ✅ تحضير تاريخ الإنشاء
                                $createdAt = 'غير محدد';
                                if (isset($task->created_at) && $task->created_at) {
                                if (is_string($task->created_at)) {
                                try {
                                $createdAt = date('Y-m-d', strtotime($task->created_at));
                                } catch (Exception $e) {
                                $createdAt = $task->created_at;
                                }
                                } elseif (is_object($task->created_at) && method_exists($task->created_at, 'format')) {
                                $createdAt = $task->created_at->format('Y-m-d');
                                } elseif (is_object($task->created_at) && method_exists($task->created_at, 'toDateString')) {
                                $createdAt = $task->created_at->toDateString();
                                }
                                }
                                @endphp
                                @php
                                $projectStatus = isset($task->project) && isset($task->project->status) ? $task->project->status : '';
                                $isProjectCancelled = $projectStatus === 'ملغي';
                                @endphp
                                <tr data-project-id="{{ $task->project_id ?? 0 }}"
                                    data-project-status="{{ $projectStatus }}"
                                    data-status="{{ $status }}"
                                    data-task-id="{{ $originalTaskId }}"
                                    data-task-user-id="{{ $taskUserId }}"
                                    data-task-name="{{ $task->name ?? '' }}"
                                    data-task-description="{{ $task->description ?? '' }}"
                                    data-project-name="{{ isset($task->project->name) ? $task->project->name : 'غير محدد' }}"
                                    data-service-name="{{ isset($task->service->name) ? $task->service->name : 'غير محدد' }}"
                                    data-user-role="{{ $userRole }}"
                                    data-estimated-time="{{ (isset($task->is_flexible_time) && $task->is_flexible_time) ? 'مرن' : $estimatedHours . ':' . str_pad($estimatedMinutes, 2, '0', STR_PAD_LEFT) }}"
                                    data-actual-time="{{ $actualHours }}:{{ str_pad($actualMinutes, 2, '0', STR_PAD_LEFT) }}"
                                    data-due-date="{{ $dueDate }}"
                                    data-created-at="{{ $createdAt }}"
                                    data-is-template="{{ $isTemplate ? 'true' : 'false' }}"
                                    data-is-flexible="{{ (isset($task->is_flexible_time) && $task->is_flexible_time) ? 'true' : 'false' }}"
                                    data-points="{{ $task->points ?? 10 }}"
                                    data-started-at="{{ $startedAt ?? '' }}"
                                    data-initial-minutes="{{ $dbMinutes }}"
                                    data-revisions-count="{{ $task->revisions_count ?? 0 }}"
                                    data-pending-revisions-count="{{ $task->pending_revisions_count ?? 0 }}"
                                    data-approved-revisions-count="{{ $task->approved_revisions_count ?? 0 }}"
                                    data-rejected-revisions-count="{{ $task->rejected_revisions_count ?? 0 }}"
                                    data-revisions-status="{{ $task->revisions_status ?? 'none' }}"
                                    data-is-transferred="{{ $task->is_transferred ?? false }}"
                                    class="{{ $isProjectCancelled ? 'project-cancelled-row' : '' }}"
                                    data-transferred-to-user-id="{{ $task->transferred_to_user_id ?? null }}"
                                    data-original-task-user-id="{{ $task->original_task_user_id ?? null }}"
                                    data-task-source="{{ $task->task_source ?? 'original' }}"
                                    data-is-additional-task="{{ $task->is_additional_task ?? false }}">
                                    <td>
                                        <div class="d-flex px-2 py-1">
                                            <div class="d-flex flex-column justify-content-center">
                                                <h6 class="mb-0 text-sm">
                                                    {{ $task->name ?? 'مهمة بدون اسم' }}
                                                    @if(isset($task->is_template) && $task->is_template)
                                                    <span class="badge badge-sm bg-info ms-1">
                                                        <i class="fas fa-layer-group"></i> قالب
                                                    </span>
                                                    @endif
                                                    @if(isset($task->is_transferred) && $task->is_transferred)
                                                    <span class="badge badge-sm bg-warning ms-1">
                                                        <i class="fas fa-exchange-alt"></i> منقول
                                                    </span>
                                                    @endif
                                                    @if(isset($task->is_additional_task) && $task->is_additional_task)
                                                    <span class="badge badge-sm bg-success ms-1">
                                                        <i class="fas fa-plus"></i> إضافي
                                                    </span>
                                                    @endif
                                                    @if(isset($task->notes_count) && $task->notes_count > 0)
                                                    <span class="task-notes-indicator ms-1" title="{{ $task->notes_count }} ملاحظات">
                                                        <i class="fas fa-sticky-note"></i>
                                                        <span class="notes-count">{{ $task->notes_count }}</span>
                                                    </span>
                                                    @endif
                                                </h6>
                                                <p class="text-xs text-secondary mb-0">
                                                    {{ isset($task->description) ? Str::limit($task->description, 50) : '' }}
                                                    @if(isset($task->is_template) && $task->is_template)
                                                    <small class="text-info">(من: {{ $task->template_name ?? 'قالب غير محدد' }})</small>
                                                    @endif
                                                    @if(isset($task->is_transferred) && $task->is_transferred)
                                                    <small class="text-warning d-block">تم النقل إلى: {{ $task->transferredToUser->name ?? 'غير محدد' }}</small>
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if(isset($task->project))
                                        <strong>{{ $task->project->code ?? '' }}</strong> {{ $task->project->name }}
                                        @if($isProjectCancelled)
                                        <span class="badge bg-danger ms-1">(المشروع ملغي)</span>
                                        @endif
                                        @else
                                        غير محدد
                                        @endif
                                    </td>
                                    <td>{{ $userRole }}</td>
                                    <td>
                                        @if(isset($task->is_flexible_time) && $task->is_flexible_time)
                                        <span class="badge bg-info">مرن</span>
                                        @else
                                        {{ $estimatedHours }}:{{ str_pad($estimatedMinutes, 2, '0', STR_PAD_LEFT) }}
                                        @endif
                                    </td>
                                    <td>{{ $actualHours }}:{{ str_pad($actualMinutes, 2, '0', STR_PAD_LEFT) }}</td>
                                    <td>
                                        @php
                                        // استخدام المتغير المحسوب مسبقاً
                                        // $status تم حسابه بالفعل أعلاه
                                        @endphp
                                        <span class="badge badge-sm
                                            @if($status == 'new') bg-info
                                            @elseif($status == 'in_progress') bg-primary
                                            @elseif($status == 'paused') bg-warning
                                            @elseif($status == 'completed') bg-success
                                            @elseif($status == 'cancelled') bg-danger
                                            @endif">
                                            @if($status == 'new') جديدة
                                            @elseif($status == 'in_progress') قيد التنفيذ
                                            @elseif($status == 'paused') متوقفة
                                            @elseif($status == 'completed') مكتملة
                                            @elseif($status == 'cancelled') ملغاة
                                            @endif
                                        </span>
                                    </td>
                                    <td>{{ $dueDate }}</td>
                                    <td class="text-center">
                                        @php
                                        $myTaskViewDisabled = (isset($task->is_transferred) && $task->is_transferred);
                                        @endphp

                                        <div class="btn-group">
                                            <button class="btn btn-sm {{ $myTaskViewDisabled ? 'btn-secondary' : 'btn-info' }} view-task"
                                                data-id="{{ $originalTaskId }}"
                                                data-task-user-id="{{ $taskUserId }}"
                                                data-is-template="{{ $isTemplate ? 'true' : 'false' }}"
                                                {{ $myTaskViewDisabled ? 'disabled' : '' }}
                                                title="{{ $myTaskViewDisabled ? '🔒 المهمة تم نقلها - العرض معطل' : 'عرض التفاصيل' }}">
                                                <i class="fas fa-{{ $myTaskViewDisabled ? 'eye-slash' : 'eye' }}"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center">لا توجد مهام مخصصة لك</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>

                        <div class="mt-4">
                            {{-- Pagination removed for My Tasks - showing all tasks --}}
                        </div>
                    </div>

                    <!-- Kanban Board View -->
                    <div id="myTasksKanbanView" class="kanban-board" style="display: none;">
                        <div class="kanban-columns">
                            @php
                            // تجميع المهام حسب الحالة في صفحة مهامي
                            $myTasksByStatus = [
                            'new' => [],
                            'in_progress' => [],
                            'paused' => [],
                            'completed' => [],
                            'cancelled' => [],
                            'transferred' => []
                            ];

                            foreach($allTasks as $task) {
                            // تحويل array إلى object إذا لزم الأمر
                            if (is_array($task)) {
                            $task = (object) $task;
                            if (isset($task->pivot) && is_array($task->pivot)) {
                            $task->pivot = (object) $task->pivot;
                            }
                            if (isset($task->project) && is_array($task->project)) {
                            $task->project = (object) $task->project;
                            }
                            if (isset($task->service) && is_array($task->service)) {
                            $task->service = (object) $task->service;
                            }
                            }

                            $isTemplate = isset($task->is_template) && $task->is_template;
                            $displayStatus = $isTemplate ? ($task->pivot->status ?? $task->status ?? 'new') : (isset($task->pivot->status) ? $task->pivot->status : 'new');

                            if (isset($myTasksByStatus[$displayStatus])) {
                            $myTasksByStatus[$displayStatus][] = $task;
                            }
                            }
                            @endphp

                            @foreach(['new' => 'جديدة', 'in_progress' => 'قيد التنفيذ', 'paused' => 'متوقفة', 'completed' => 'مكتملة', 'cancelled' => 'ملغاة', 'transferred' => 'منقولة'] as $status => $statusText)
                            <div class="kanban-column" data-status="{{ $status }}">
                                <div class="kanban-header {{ $status }}">
                                    <h6>
                                        @if($status == 'new')<i class="fas fa-circle-plus"></i>
                                        @elseif($status == 'in_progress')<i class="fas fa-play-circle"></i>
                                        @elseif($status == 'paused')<i class="fas fa-pause-circle"></i>
                                        @elseif($status == 'completed')<i class="fas fa-check-circle"></i>
                                        @elseif($status == 'cancelled')<i class="fas fa-times-circle"></i>
                                        @elseif($status == 'transferred')<i class="fas fa-exchange-alt"></i>
                                        @endif
                                        {{ $statusText }}
                                    </h6>
                                    <span class="task-count" id="my-count-{{ $status }}">{{ count($myTasksByStatus[$status]) }}</span>
                                </div>
                                <div class="kanban-cards kanban-drop-zone" id="my-cards-{{ $status }}" data-status="{{ $status }}">
                                    @foreach($myTasksByStatus[$status] as $task)
                                    @php
                                    // تحويل array إلى object إذا لزم الأمر
                                    if (is_array($task)) {
                                    $task = (object) $task;
                                    if (isset($task->pivot) && is_array($task->pivot)) {
                                    $task->pivot = (object) $task->pivot;
                                    }
                                    if (isset($task->project) && is_array($task->project)) {
                                    $task->project = (object) $task->project;
                                    }
                                    if (isset($task->service) && is_array($task->service)) {
                                    $task->service = (object) $task->service;
                                    }
                                    }

                                    $isTemplate = isset($task->is_template) && $task->is_template;
                                    $displayStatus = $isTemplate ? ($task->pivot->status ?? $task->status ?? 'new') : (isset($task->pivot->status) ? $task->pivot->status : 'new');

                                    // تحديد المعرف الصحيح حسب نوع المهمة
                                    if ($isTemplate) {
                                    $taskUserId = $task->id ?? 0;
                                    $originalTaskId = isset($task->templateTask->id) ? $task->templateTask->id : ($task->id ?? 0);
                                    } else {
                                    $taskUserId = (isset($task->pivot->id) && $task->pivot->id) ? $task->pivot->id : ($task->id ?? 0);
                                    $originalTaskId = $task->id ?? 0;
                                    if (!isset($task->pivot->id) || !$task->pivot->id) {
                                    $taskUserId = 'task_' . ($task->id ?? 0) . '_user_' . auth()->id();
                                    }
                                    }

                                    $userRole = $isTemplate ? ($task->pivot->role ?? 'منفذ') : (isset($task->pivot->role) ? $task->pivot->role : 'غير محدد');
                                    $estimatedHours = $isTemplate ? ($task->pivot->estimated_hours ?? 0) : (isset($task->pivot->estimated_hours) ? $task->pivot->estimated_hours : 0);
                                    $estimatedMinutes = $isTemplate ? ($task->pivot->estimated_minutes ?? 0) : (isset($task->pivot->estimated_minutes) ? $task->pivot->estimated_minutes : 0);
                                    $actualHours = $isTemplate ? ($task->pivot->actual_hours ?? 0) : (isset($task->pivot->actual_hours) ? $task->pivot->actual_hours : 0);
                                    $actualMinutes = $isTemplate ? ($task->pivot->actual_minutes ?? 0) : (isset($task->pivot->actual_minutes) ? $task->pivot->actual_minutes : 0);

                                    // الحصول على الدقائق المحفوظة في الداتابيز (نفس نظام المشاريع)
                                    $dbMinutes = ($actualHours * 60) + $actualMinutes;

                                    // الحصول على started_at للتايمر
                                    $startedAt = null;
                                    if ($displayStatus === 'in_progress') {
                                    if ($isTemplate) {
                                    if (isset($task->started_at) && $task->started_at) {
                                    $startedAt = strtotime($task->started_at) * 1000;
                                    }
                                    } else {
                                    if (isset($task->pivot->start_date) && $task->pivot->start_date) {
                                    $startedAt = strtotime($task->pivot->start_date) * 1000;
                                    }
                                    }
                                    }

                                    // التحقق من الاعتماد الإداري والفني
                                    $hasAdministrativeApproval = false;
                                    $hasTechnicalApproval = false;
                                    $approvalDate = null;
                                    $approverName = null;

                                    if ($isTemplate) {
                                    // Template Task
                                    $hasAdministrativeApproval = $task->administrative_approval ?? false;
                                    $hasTechnicalApproval = $task->technical_approval ?? false;
                                    $approvalDate = null;
                                    $approverName = null;

                                    // الحصول على تاريخ الاعتماد
                                    if ($hasAdministrativeApproval && isset($task->administrative_approval_at)) {
                                    $approvalDate = $task->administrative_approval_at;
                                    } elseif ($hasTechnicalApproval && isset($task->technical_approval_at)) {
                                    $approvalDate = $task->technical_approval_at;
                                    }

                                    // الحصول على اسم المعتمد
                                    if ($hasAdministrativeApproval && isset($task->administrativeApprover)) {
                                    $approverName = $task->administrativeApprover->name;
                                    } elseif ($hasTechnicalApproval && isset($task->technicalApprover)) {
                                    $approverName = $task->technicalApprover->name;
                                    }
                                    } else {
                                    // Regular Task
                                    $hasAdministrativeApproval = $task->pivot->administrative_approval ?? false;
                                    $hasTechnicalApproval = $task->pivot->technical_approval ?? false;
                                    $approvalDate = null;
                                    $approverName = null;

                                    // الحصول على تاريخ الاعتماد
                                    if ($hasAdministrativeApproval && isset($task->pivot->administrative_approval_at)) {
                                    $approvalDate = $task->pivot->administrative_approval_at;
                                    } elseif ($hasTechnicalApproval && isset($task->pivot->technical_approval_at)) {
                                    $approvalDate = $task->pivot->technical_approval_at;
                                    }

                                    // الحصول على اسم المعتمد
                                    if ($hasAdministrativeApproval && isset($task->pivot->administrativeApprover)) {
                                    $approverName = $task->pivot->administrativeApprover->name;
                                    } elseif ($hasTechnicalApproval && isset($task->pivot->technicalApprover)) {
                                    $approverName = $task->pivot->technicalApprover->name;
                                    }
                                    }

                                    $isApproved = $hasAdministrativeApproval || $hasTechnicalApproval;

                                    // تحضير تاريخ الاستحقاق
                                    $dueDate = 'غير محدد';

                                    // ✅ TemplateTasks تستخدم 'deadline' بينما المهام العادية تستخدم 'due_date'
                                    $deadlineField = $isTemplate ? 'deadline' : 'due_date';

                                    if (isset($task->{$deadlineField}) && $task->{$deadlineField}) {
                                    $deadlineValue = $task->{$deadlineField};

                                    if (is_string($deadlineValue)) {
                                    try {
                                    $dueDate = date('Y-m-d', strtotime($deadlineValue));
                                    } catch (Exception $e) {
                                    $dueDate = $deadlineValue;
                                    }
                                    } elseif (is_object($deadlineValue) && method_exists($deadlineValue, 'format')) {
                                    $dueDate = $deadlineValue->format('Y-m-d');
                                    } elseif (is_object($deadlineValue) && method_exists($deadlineValue, 'toDateString')) {
                                    $dueDate = $deadlineValue->toDateString();
                                    }
                                    }
                                    @endphp

                                    @php
                                    $projectStatus = isset($task->project) && isset($task->project->status) ? $task->project->status : '';
                                    $isProjectCancelled = $projectStatus === 'ملغي';
                                    $canDrag = !(isset($task->is_transferred) && $task->is_transferred)
                                    && !(isset($task->is_additional_task) && $task->is_additional_task)
                                    && !$isApproved
                                    && !$isProjectCancelled;
                                    @endphp
                                    <div class="kanban-card {{ $isApproved ? 'task-approved' : '' }} {{ $isProjectCancelled ? 'project-cancelled' : '' }}"
                                        data-task-id="{{ $originalTaskId }}"
                                        data-task-user-id="{{ $taskUserId }}"
                                        data-project-id="{{ $task->project_id ?? 0 }}"
                                        data-project-status="{{ $projectStatus }}"
                                        data-status="{{ $displayStatus }}"
                                        data-is-template="{{ $isTemplate ? 'true' : 'false' }}"
                                        data-is-approved="{{ $isApproved ? 'true' : 'false' }}"
                                        data-initial-minutes="{{ $dbMinutes }}"
                                        data-started-at="{{ $startedAt ?? '' }}"
                                        data-is-transferred="{{ isset($task->is_transferred) && $task->is_transferred ? 'true' : 'false' }}"
                                        data-is-additional-task="{{ isset($task->is_additional_task) && $task->is_additional_task ? 'true' : 'false' }}"
                                        draggable="{{ $canDrag ? 'true' : 'false' }}">

                                        <div class="kanban-card-title">{{ $task->name ?? 'مهمة بدون اسم' }}</div>

                                        @if($isTemplate || isset($task->is_transferred) && $task->is_transferred || isset($task->is_additional_task) && $task->is_additional_task || isset($task->notes_count) && $task->notes_count > 0 || isset($task->revisions_count) && $task->revisions_count > 0 || $isApproved)
                                        <div class="kanban-card-badges mb-2">
                                            @if($isTemplate)
                                            <span class="badge badge-sm bg-info ms-1"><i class="fas fa-layer-group"></i> قالب</span>
                                            @endif

                                            {{-- Badges الاعتماد --}}
                                            @if($isApproved)
                                            <span class="badge badge-sm bg-success ms-1" title="مهمة معتمدة{{ $approverName ? ' بواسطة ' . $approverName : '' }}{{ $approvalDate ? ' في ' . \Carbon\Carbon::parse($approvalDate)->format('Y-m-d H:i') : '' }}">
                                                <i class="fas fa-lock"></i> معتمد
                                            </span>
                                            @if($hasAdministrativeApproval)
                                            <span class="badge badge-sm bg-primary ms-1" title="اعتماد إداري">
                                                <i class="fas fa-user-tie"></i> إداري
                                            </span>
                                            @endif
                                            @if($hasTechnicalApproval)
                                            <span class="badge badge-sm bg-warning text-dark ms-1" title="اعتماد فني">
                                                <i class="fas fa-cogs"></i> فني
                                            </span>
                                            @endif
                                            @endif

                                            @if(isset($task->is_transferred) && $task->is_transferred)
                                            <span class="badge badge-sm bg-warning ms-1"><i class="fas fa-exchange-alt"></i> منقول</span>
                                            @endif
                                            @if(isset($task->is_additional_task) && $task->is_additional_task)
                                            <span class="badge badge-sm bg-success ms-1"><i class="fas fa-plus"></i> إضافي</span>
                                            @endif
                                            @if(isset($task->notes_count) && $task->notes_count > 0)
                                            <span class="task-notes-indicator ms-1" title="{{ $task->notes_count }} ملاحظات">
                                                <i class="fas fa-sticky-note"></i>
                                                <span class="notes-count">{{ $task->notes_count }}</span>
                                            </span>
                                            @endif
                                            @if(isset($task->revisions_count) && $task->revisions_count > 0)
                                            <span class="task-revisions-badge {{ $task->revisions_status ?? 'none' }} ms-1"
                                                title="{{ $task->revisions_count }} تعديلات{{ isset($task->pending_revisions_count) && $task->pending_revisions_count > 0 ? ' - ' . $task->pending_revisions_count . ' معلق' : '' }}{{ isset($task->approved_revisions_count) && $task->approved_revisions_count > 0 ? ' - ' . $task->approved_revisions_count . ' مقبول' : '' }}{{ isset($task->rejected_revisions_count) && $task->rejected_revisions_count > 0 ? ' - ' . $task->rejected_revisions_count . ' مرفوض' : '' }}">
                                                <i class="fas fa-edit"></i>
                                                <span class="revisions-count">{{ $task->revisions_count }}</span>
                                            </span>
                                            @endif
                                        </div>
                                        @endif

                                        @if(isset($task->project) || (isset($task->service) && $task->service->name))
                                        <div class="kanban-card-meta">
                                            @if(isset($task->project))
                                            <span class="kanban-card-project">
                                                <strong>{{ $task->project->code ?? '' }}</strong> {{ $task->project->name }}
                                            </span>
                                            @endif
                                            @if(isset($task->service) && $task->service->name)
                                            <span class="kanban-card-service">{{ $task->service->name }}</span>
                                            @endif
                                        </div>
                                        @endif

                                        <div class="kanban-card-meta mb-2">
                                            <span class="kanban-card-role">{{ $userRole }}</span>
                                        </div>

                                        @if(isset($task->is_transferred) && $task->is_transferred && isset($task->transferredToUser) && $task->transferredToUser->name)
                                        <div class="kanban-card-transfer-info">
                                            <i class="fas fa-exchange-alt"></i>
                                            <strong>تم النقل إلى:</strong> {{ $task->transferredToUser->name }}
                                            @if(isset($task->transferred_at))
                                            <small class="d-block">في: {{ $task->transferred_at->format('Y-m-d H:i') }}</small>
                                            @endif
                                        </div>
                                        @endif

                                        <div class="kanban-card-time">
                                            <span>مقدر: {{ (isset($task->is_flexible_time) && $task->is_flexible_time) ? 'مرن' : $estimatedHours . ':' . str_pad($estimatedMinutes, 2, '0', STR_PAD_LEFT) }}</span>
                                            <span>فعلي: {{ $actualHours }}:{{ str_pad($actualMinutes, 2, '0', STR_PAD_LEFT) }}</span>
                                        </div>

                                        <div class="kanban-card-points">
                                            <span class="badge bg-warning text-dark">
                                                <i class="fas fa-star"></i> {{ $task->points ?? 10 }} نقطة
                                            </span>
                                        </div>

                                        @if($displayStatus === 'in_progress')
                                        <div class="kanban-card-timer">
                                            <i class="fas fa-clock"></i>
                                            <span id="my-kanban-timer-{{ $originalTaskId }}">00:00:00</span>
                                        </div>
                                        @endif

                                        @if($dueDate && $dueDate !== 'غير محدد')
                                        <div class="kanban-card-due-date">
                                            <i class="fas fa-calendar"></i> {{ $dueDate }}
                                        </div>
                                        @endif

                                        <div class="kanban-card-actions">
                                            @php
                                            // ✅ التحقق من كون المهمة منقولة منه فقط (للتحكم في زر العين)
                                            // المهمة الأصلية المنقولة (is_transferred = true) → مقفول
                                            // المهمة المنقولة إليه (is_additional_task) → مفتوح عادي
                                            $myKanbanViewDisabled = (isset($task->is_transferred) && $task->is_transferred);
                                            @endphp

                                            <button class="btn btn-sm {{ $myKanbanViewDisabled ? 'btn-outline-secondary' : 'btn-outline-primary' }} view-task"
                                                data-id="{{ $originalTaskId }}"
                                                data-task-user-id="{{ $taskUserId }}"
                                                data-is-template="{{ $isTemplate ? 'true' : 'false' }}"
                                                {{ $myKanbanViewDisabled ? 'disabled' : '' }}
                                                title="{{ $myKanbanViewDisabled ? '🔒 المهمة تم نقلها - العرض معطل' : 'عرض التفاصيل' }}">
                                                <i class="fas fa-{{ $myKanbanViewDisabled ? 'eye-slash' : 'eye' }}"></i>
                                            </button>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Calendar View -->
                    <div id="myTasksCalendarView" class="calendar-view" style="display: none;">
                        <div class="calendar-container">
                            <!-- Calendar Header -->
                            <div class="calendar-header">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <button class="btn btn-outline-secondary" id="prevMonth">
                                            <i class="fas fa-chevron-left"></i>
                                        </button>
                                        <h5 id="currentMonthYear" class="mb-0 fw-bold"></h5>
                                        <button class="btn btn-outline-secondary" id="nextMonth">
                                            <i class="fas fa-chevron-right"></i>
                                        </button>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <button class="btn btn-sm btn-outline-primary" id="backToTableBtn">
                                            <i class="fas fa-table me-1"></i>
                                            العودة للجدول
                                        </button>
                                        <button class="btn btn-sm btn-primary" id="todayBtn">اليوم</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Calendar Grid -->
                            <div class="calendar-grid">
                                <!-- Days of Week Header -->
                                <div class="calendar-weekdays">
                                    <div class="calendar-weekday">الأحد</div>
                                    <div class="calendar-weekday">الاثنين</div>
                                    <div class="calendar-weekday">الثلاثاء</div>
                                    <div class="calendar-weekday">الأربعاء</div>
                                    <div class="calendar-weekday">الخميس</div>
                                    <div class="calendar-weekday">الجمعة</div>
                                    <div class="calendar-weekday">السبت</div>
                                </div>

                                <!-- Calendar Days -->
                                <div class="calendar-days" id="calendarDays">
                                    <!-- Days will be populated by JavaScript -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="viewTaskModal" tabindex="-1" aria-labelledby="viewTaskModalLabel" aria-hidden="true" style="display: none !important;">
</div>

<div class="modal fade" id="addNotesModal" tabindex="-1" aria-labelledby="addNotesModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addNotesModalLabel">إضافة ملاحظات</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="taskActionForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="notes">ملاحظات (اختياري)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ</button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- Task Sidebar (مثل Asana) - Note: Sidebar HTML was already below, keeping it -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeTaskSidebar()" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1040; visibility: hidden; opacity: 0; transition: all 0.3s ease;"></div>

<div class="task-sidebar" id="taskSidebar" style="position: fixed; top: 0; left: -480px; width: 460px; height: 100vh; background: #ffffff; z-index: 1050; transition: left 0.4s cubic-bezier(0.4, 0.0, 0.2, 1); box-shadow: 0 8px 40px rgba(0,0,0,0.12); overflow-y: auto; border-right: 1px solid #e1e5e9; scrollbar-width: none; -ms-overflow-style: none;">
    <!-- Sidebar Header -->
    <div class="sidebar-header" style="background: #ffffff; color: #333; padding: 24px 32px 16px 32px; border-bottom: 1px solid #e9ecef; position: sticky; top: 0; z-index: 10;">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="flex-grow-1">
                <h4 id="taskSidebarTitle" class="mb-2" style="font-weight: 600; color: #2c3e50; font-size: 1.5rem; line-height: 1.3;">تفاصيل المهمة</h4>
                <p id="taskSidebarSubtitle" class="mb-0" style="font-size: 14px; color: #6c757d; margin: 0;">المشروع</p>
            </div>
            <button onclick="closeTaskSidebar()" class="btn btn-link p-0 ms-3" style="color: #6c757d; opacity: 0.7; font-size: 20px; background: none; border: none;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span id="taskSidebarBadge" class="badge" style="background: #e8f5e8; color: #2d7d2d; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                <i class="fas fa-layer-group me-1"></i>مهمة قالب
            </span>
        </div>
    </div>

    <!-- Sidebar Content -->
    <div id="taskSidebarContent" style="padding: 0; min-height: calc(100vh - 140px);">
        <!-- Content will be loaded here -->
    </div>
</div>


@push('scripts')

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- ⚠️ Set current user ID FIRST (needed by all scripts) -->
<script>
    window.currentUserId = {
        {
            Auth::id()
        }
    };
    console.log('✅ Current User ID:', window.currentUserId);
</script>

<script src="{{ asset('js/projects/task-sidebar.js') }}?v={{ time() }}"></script>

<!-- My Tasks Scripts -->
<script src="{{ asset('js/tasks/filters.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/tasks/my-tasks/utils.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/tasks/my-tasks/core.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/tasks/my-tasks/timers.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/tasks/my-tasks/drag-drop.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/tasks/my-tasks/kanban.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/tasks/my-tasks/modal-handlers.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/tasks/my-tasks-kanban.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/tasks/my-tasks-calendar.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/tasks/my-tasks-stats.js') }}?v={{ time() }}"></script>

<!-- 🚀 Initialization Script (MUST BE LAST) -->
<script src="{{ asset('js/tasks/my-tasks/init.js') }}?v={{ time() }}"></script>




@endpush
@endsection
