<div class="row mt-4">
    <div class="col-12 px-0">
        <div class="kanban-main-container kanban-main-container-external shadow-sm">
            <!-- Modern Header -->
            <div class="kanban-main-header kanban-main-header-external">
                <div class="d-flex justify-content-between align-items-center">
                    <!-- Header Title Section -->
                    <div class="d-flex align-items-center">
                        <div class="header-icon header-icon-external me-3">
                            <i class="fas fa-columns text-white"></i>
                        </div>
                        <div>
                            <h4 class="mb-1 text-white fw-bold">
                                @if($isHRUser ?? false)
                                📋 جميع مهام المشروع
                                @else
                                🎯 مهامي في المشروع
                                @endif
                            </h4>
                            <p class="mb-0 text-white-50 small">
                                <i class="fas fa-tasks me-1"></i>
                                لوحة إدارة المهام الذكية
                            </p>
                        </div>
                    </div>

                    <!-- Header Actions -->
                    <div class="header-actions-container">
                        <!-- View Toggle Buttons -->
                        <div class="btn-group btn-group-external shadow-sm" role="group">
                            <button type="button" class="btn btn-light btn-light-external btn-sm active" id="kanbanViewBtnShow">
                                <i class="fas fa-columns me-1"></i>
                                كانبان
                            </button>
                            <button type="button" class="btn btn-light btn-light-external btn-sm" id="calendarViewBtnShow">
                                <i class="fas fa-calendar-alt me-1"></i>
                                تقويم
                            </button>
                        </div>

                        <!-- Analytics Buttons -->
                        <div class="analytics-buttons">
                            <a href="{{ route('projects.service-analytics', $project) }}"
                                class="btn btn-success btn-analytics btn-sm d-flex align-items-center shadow-sm">
                                <i class="fas fa-chart-pie me-2"></i>
                                إحصائيات الخدمات
                            </a>

                            <a href="{{ route('projects.analytics', $project->id) }}#task-analytics"
                                class="btn btn-info btn-analytics btn-sm d-flex align-items-center shadow-sm">
                                <i class="fas fa-chart-line me-2"></i>
                                إحصائيات المهام
                            </a>
                        </div>

                        <!-- Timer Widget -->
                        <div class="timer-widget timer-widget-external d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-clock text-white-50 me-2"></i>
                                <small class="text-white-50 fw-medium">الوقت الإجمالي:</small>
                            </div>
                            <div class="timer-display timer-display-external">
                                <span id="kanban-total-timer" class="fw-bold">00:00:00</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Header Decoration -->
                <div class="header-decoration header-decoration-external"></div>
            </div>

            <!-- Kanban Board Body -->
            <div class="kanban-body kanban-body-external">
                <div class="kanban-board kanban-board-external">
                    <div class="kanban-columns">
                        @php
                        $user = auth()->user();

                        $isHRUser = $user && $user->hasRole('hr');

                        if ($isHRUser) {
                        $userTemplateTasks = App\Models\TemplateTaskUser::with(['templateTask.template', 'user'])
                        ->withCount('notes')
                        ->where('project_id', $project->id)
                        ->get();

                        $userRegularTasks = App\Models\TaskUser::with(['task', 'user'])
                        ->withCount('notes')
                        ->whereHas('task', function ($query) use ($project) {
                        $query->where('project_id', $project->id);
                        })
                        ->get();
                        } else {
                        $userTemplateTasks = $user
                        ? App\Models\TemplateTaskUser::with('templateTask.template')
                        ->withCount('notes')
                        ->where('user_id', $user->id)
                        ->where('project_id', $project->id)
                        ->get()
                        : collect();

                        $userRegularTasks = $user
                        ? App\Models\TaskUser::with('task')
                        ->withCount('notes')
                        ->whereHas('task', function ($query) use ($project) {
                        $query->where('project_id', $project->id);
                        })
                        ->where('user_id', $user->id)
                        ->get()
                        : collect();
                        }

                        // حساب عدد المهام الملغاة
                        $cancelledTasksCount = $userTemplateTasks->where('status', 'cancelled')->count() + $userRegularTasks->where('status', 'cancelled')->count();

                        // بناء مصفوفة الحالات (عمود ملغاة يظهر فقط لو فيه مهام ملغاة)
                        $statuses = [
                        'new' => ['name' => 'جديدة', 'icon' => 'fas fa-circle-plus'],
                        'in_progress' => ['name' => 'قيد التنفيذ', 'icon' => 'fas fa-play-circle'],
                        'paused' => ['name' => 'متوقفة', 'icon' => 'fas fa-pause-circle'],
                        'completed' => ['name' => 'مكتملة', 'icon' => 'fas fa-check-circle'],
                        ];

                        // إضافة عمود ملغاة فقط لو فيه مهام ملغاة
                        if ($cancelledTasksCount > 0) {
                        $statuses['cancelled'] = ['name' => 'ملغاة', 'icon' => 'fas fa-times-circle'];
                        }
                        @endphp

                        @foreach($statuses as $statusKey => $statusData)
                        <div class="kanban-column" data-status="{{ $statusKey }}">
                            <div class="kanban-column-header">
                                <h6><i class="{{ $statusData['icon'] }}"></i> {{ $statusData['name'] }}</h6>
                                <span class="task-count" id="count-{{ $statusKey }}">
                                    {{ $userTemplateTasks->where('status', $statusKey)->count() + $userRegularTasks->where('status', $statusKey)->count() }}
                                </span>
                            </div>
                            <div class="kanban-tasks kanban-drop-zone" data-status="{{ $statusKey }}" id="kanban-{{ $statusKey }}">
                                {{-- مهام القوالب --}}
                                @foreach($userTemplateTasks->where('status', $statusKey) as $taskUser)
                                @php
                                $task = $taskUser->templateTask;
                                $actualMinutes = $taskUser->actual_minutes ?? 0;
                                $hours = floor($actualMinutes / 60);
                                $minutes = $actualMinutes % 60;
                                $formattedTime = sprintf('%02d:%02d:00', $hours, $minutes);

                                $startedAt = null;
                                if ($taskUser->status == 'in_progress' && $taskUser->started_at) {
                                $startedAt = $taskUser->started_at->timestamp * 1000;
                                }
                                @endphp
                                @if($task)
                                @php
                                // التحقق من حالة المشروع
                                $projectStatus = $project->status ?? '';
                                $isProjectCancelled = $projectStatus === 'ملغي';

                                $canDragTemplate = $taskUser->canBeDragged() && !$isProjectCancelled;
                                $isTransferredFromTemplate = (bool) ($taskUser->is_transferred ?? false);
                                $isTransferredToTemplate = (bool) ($taskUser->is_additional_task ?? false) || (method_exists($taskUser, 'isTransferredFromAnother') ? $taskUser->isTransferredFromAnother() : false);
                                $canOriginalOwnerTransferTemplate = false;
                                try { $canOriginalOwnerTransferTemplate = ($taskUser->originalTemplateTaskUser && $taskUser->originalTemplateTaskUser->user_id === auth()->id()); } catch (\Throwable $e) { $canOriginalOwnerTransferTemplate = false; }
                                $isApprovedTemplate = $taskUser->hasAdministrativeApproval() || $taskUser->hasTechnicalApproval();
                                @endphp
                                <div class="kanban-task template-task task-clickable {{ $isTransferredFromTemplate ? 'transferred-from' : '' }} {{ $isTransferredToTemplate ? 'transferred-to' : '' }} {{ $isApprovedTemplate ? 'approved-task' : '' }} {{ $isProjectCancelled ? 'project-cancelled' : '' }}"
                                    draggable="{{ $canDragTemplate ? 'true' : 'false' }}"
                                    data-task-id="{{ $task->id }}"
                                    data-task-user-id="{{ $taskUser->id }}"
                                    data-user-id="{{ $taskUser->user_id }}"
                                    data-task-type="template_task"
                                    data-sidebar-task-type="template"
                                    data-sidebar-task-user-id="{{ $taskUser->id }}"
                                    data-initial-minutes="{{ $actualMinutes }}"
                                    data-started-at="{{ $startedAt }}"
                                    data-status="{{ $taskUser->status }}"
                                    data-project-status="{{ $projectStatus }}"
                                    title="@if(!$canDragTemplate) {{ $isProjectCancelled ? 'المشروع تم إلغاؤه - لا يمكنك تغيير حالة المهمة' : ($isApprovedTemplate ? 'تم اعتماد هذه المهمة - لا يمكنك تغيير حالتها' : ($isTransferredFromTemplate ? 'تم نقل هذه المهمة من حسابك - لا يمكنك تغيير حالتها' : 'هذه المهمة مخصصة لمستخدم آخر - لا يمكنك تحريكها')) }} @endif">

                                    <h6>{{ $task->name }}</h6>

                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="d-flex align-items-center gap-1">
                                            <span class="badge bg-success">🏢 قالب</span>
                                            @if($isApprovedTemplate)
                                            <span class="badge bg-success"><i class="fas fa-shield-check me-1"></i>معتمدة</span>
                                            @endif
                                            @if($isTransferredFromTemplate)
                                            <span class="badge bg-danger">تم نقلها</span>
                                            @endif
                                            @if($isTransferredToTemplate)
                                            <span class="badge bg-success">منقولة إليك</span>
                                            <span class="badge bg-info text-dark">إضافية</span>
                                            @endif
                                        </div>
                                        @if($taskUser->notes_count > 0)
                                        <span class="task-notes-indicator" title="{{ $taskUser->notes_count }} ملاحظات">
                                            <i class="fas fa-sticky-note"></i>
                                            <span class="notes-count">{{ $taskUser->notes_count }}</span>
                                        </span>
                                        @endif
                                        @if($taskUser->revisions_count > 0)
                                        @php
                                        $revisionsStatus = 'pending'; // Default
                                        if ($taskUser->pending_revisions_count > 0) {
                                        if (($taskUser->approved_revisions_count ?? 0) > 0 || ($taskUser->rejected_revisions_count ?? 0) > 0) {
                                        $revisionsStatus = 'mixed';
                                        } else {
                                        $revisionsStatus = 'pending';
                                        }
                                        } else {
                                        if (($taskUser->approved_revisions_count ?? 0) > 0 && ($taskUser->rejected_revisions_count ?? 0) == 0) {
                                        $revisionsStatus = 'approved';
                                        } elseif (($taskUser->rejected_revisions_count ?? 0) > 0 && ($taskUser->approved_revisions_count ?? 0) == 0) {
                                        $revisionsStatus = 'rejected';
                                        } else {
                                        $revisionsStatus = 'mixed';
                                        }
                                        }

                                        $tooltipText = $taskUser->revisions_count . ' تعديلات';
                                        if ($taskUser->pending_revisions_count > 0) {
                                        $tooltipText .= ' - ' . $taskUser->pending_revisions_count . ' معلق';
                                        }
                                        if (($taskUser->approved_revisions_count ?? 0) > 0) {
                                        $tooltipText .= ' - ' . $taskUser->approved_revisions_count . ' مقبول';
                                        }
                                        if (($taskUser->rejected_revisions_count ?? 0) > 0) {
                                        $tooltipText .= ' - ' . $taskUser->rejected_revisions_count . ' مرفوض';
                                        }
                                        @endphp
                                        <span class="task-revisions-badge {{ $revisionsStatus }}" title="{{ $tooltipText }}">
                                            <i class="fas fa-edit"></i>
                                            <span class="revisions-count">{{ $taskUser->revisions_count }}</span>
                                        </span>
                                        @endif
                                    </div>

                                    @if($isHRUser && $taskUser->user)
                                    <div class="user-info">
                                        <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center"
                                            class="user-info-circle">
                                            <i class="fas fa-user text-white user-info-icon"></i>
                                        </div>
                                        <span class="text-dark fw-semibold">{{ $taskUser->user->name }}</span>
                                    </div>
                                    @endif

                                    <!-- Time Information -->
                                    <div class="time-info">
                                        <div class="time-row estimated">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-clock me-2"></i>
                                                <span>الوقت المقدر</span>
                                            </div>
                                            <span>{{ $task->estimated_hours }}س {{ sprintf('%02d', $task->estimated_minutes) }}د</span>
                                        </div>
                                        <div class="time-row actual">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-stopwatch me-2"></i>
                                                <span>الوقت الفعلي</span>
                                            </div>
                                            <span>
                                                @php
                                                $actualHours = intval($actualMinutes / 60);
                                                $remainingMinutes = $actualMinutes % 60;
                                                @endphp
                                                {{ $actualHours }}س {{ sprintf('%02d', $remainingMinutes) }}د
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Deadline Information -->
                                    @if($taskUser->deadline)
                                    @php
                                    $now = now();
                                    $isOverdue = $taskUser->deadline->isPast() && $taskUser->status !== 'completed';
                                    $isDueSoon = $taskUser->deadline->isFuture() && $taskUser->deadline->diffInHours($now) <= 24 && $taskUser->status !== 'completed';

                                        if ($taskUser->status === 'completed') {
                                        $deadlineClass = 'success';
                                        } elseif ($isOverdue) {
                                        $deadlineClass = 'danger';
                                        } elseif ($isDueSoon) {
                                        $deadlineClass = 'warning';
                                        } else {
                                        $deadlineClass = 'primary';
                                        }
                                        @endphp
                                        <div class="deadline-info {{ $deadlineClass }}">
                                            <div class="rounded-circle">
                                                <i class="fas fa-calendar"></i>
                                            </div>
                                            <div>
                                                <strong>{{ $taskUser->deadline->format('d/m/Y') }}</strong><br>
                                                <small>
                                                    @if($isOverdue)
                                                    متأخر {{ $taskUser->deadline->diffForHumans() }}
                                                    @elseif($isDueSoon)
                                                    ينتهي {{ $taskUser->deadline->diffForHumans() }}
                                                    @elseif($taskUser->status === 'completed')
                                                    مكتمل في الموعد
                                                    @else
                                                    {{ $taskUser->deadline->diffForHumans() }}
                                                    @endif
                                                </small>
                                            </div>
                                        </div>
                                        @endif

                                        <!-- Timer and Actions -->
                                        <div class="task-actions">
                                            <span class="timer" id="kanban-timer-template-{{ $taskUser->id }}">{{ $formattedTime }}</span>
                                            @php
                                            // ✅ تحديد إمكانية النقل أو التعديل
                                            $canShowTransferBtn = false;
                                            $transferBtnMode = 'transfer';
                                            $transferBtnTitle = 'نقل المهمة';
                                            $transferBtnIcon = 'fas fa-exchange-alt';

                                            // الشروط الأساسية: المهمة ليست مكتملة أو ملغاة والمشروع لم يتم إلغاؤه
                                            if (!in_array($taskUser->status, ['completed', 'cancelled']) && !$isProjectCancelled) {
                                            // ❌ منع ظهور الزر للمهام الأصلية المنقولة
                                            if ($isTransferredFromTemplate) {
                                            $canShowTransferBtn = false;
                                            }
                                            // ✅ السماح بتعديل المستلم للمهام المنقولة (للشخص الحالي أو HR)
                                            elseif ($isTransferredToTemplate && (auth()->user()->hasRole('hr') || $taskUser->user_id === auth()->id())) {
                                            $canShowTransferBtn = true;
                                            $transferBtnMode = 'reassign';
                                            $transferBtnTitle = 'تعديل المستلم';
                                            $transferBtnIcon = 'fas fa-user-edit';
                                            }
                                            // ✅ السماح بالنقل للمهام الأصلية غير المنقولة
                                            elseif (!$isTransferredFromTemplate && (auth()->user()->hasRole('hr') || $taskUser->user_id === auth()->id() || $canOriginalOwnerTransferTemplate)) {
                                            $canShowTransferBtn = true;
                                            $transferBtnMode = 'transfer';
                                            $transferBtnTitle = 'نقل المهمة';
                                            $transferBtnIcon = 'fas fa-exchange-alt';
                                            }
                                            }
                                            @endphp

                                            @if($canShowTransferBtn)
                                            <button class="transfer-btn {{ $transferBtnMode === 'reassign' ? 'reassign-btn' : '' }}"
                                                data-task-type="template"
                                                data-task-id="{{ $taskUser->id }}"
                                                data-task-name="{{ $task->name ?? 'مهمة' }}"
                                                data-user-name="{{ $taskUser->user->name ?? auth()->user()->name }}"
                                                data-mode="{{ $transferBtnMode }}"
                                                onclick="event.stopPropagation(); openTransferModal(this.dataset.taskType, this.dataset.taskId, this.dataset.taskName, this.dataset.userName, this.dataset.mode)"
                                                title="{{ $transferBtnTitle }}">
                                                <i class="{{ $transferBtnIcon }}"></i>
                                            </button>
                                            @elseif($isProjectCancelled)
                                            <button class="transfer-btn" disabled title="لا يمكن نقل المهام التابعة لمشروع ملغي">
                                                <i class="fas fa-exchange-alt"></i>
                                            </button>
                                            @endif

                                            {{-- زر إلغاء النقل للمهام المنقولة --}}
                                            @if($isTransferredToTemplate && !in_array($taskUser->status, ['completed', 'cancelled']) &&
                                            (auth()->user()->hasRole('hr') || auth()->user()->hasRole('admin') || $taskUser->user_id === auth()->id()))
                                            <button class="cancel-transfer-task btn btn-sm btn-danger"
                                                data-task-type="template"
                                                data-task-id="{{ $taskUser->id }}"
                                                data-task-name="{{ $task->name ?? 'مهمة' }}"
                                                title="إلغاء النقل"
                                                style="padding: 4px 8px; font-size: 12px; border-radius: 4px;">
                                                <i class="fas fa-undo"></i>
                                            </button>
                                            @endif
                                        </div>

                                </div>
                                @endif
                                @endforeach

                                {{-- المهام العادية --}}
                                @foreach($userRegularTasks->where('status', $statusKey) as $taskUser)
                                @php
                                $task = $taskUser->task;
                                $actualMinutes = ($taskUser->actual_hours * 60) + $taskUser->actual_minutes;
                                $hours = floor($actualMinutes / 60);
                                $minutes = $actualMinutes % 60;
                                $formattedTime = sprintf('%02d:%02d:00', $hours, $minutes);

                                $startedAt = null;
                                if ($taskUser->status == 'in_progress' && $taskUser->start_date) {
                                $startedAt = $taskUser->start_date->timestamp * 1000;
                                }
                                @endphp
                                @if($task)
                                @php
                                // التحقق من حالة المشروع
                                $projectStatus = $project->status ?? '';
                                $isProjectCancelled = $projectStatus === 'ملغي';

                                $canDragRegular = $taskUser->canBeDragged() && !$isProjectCancelled;
                                $isTransferredFromRegular = (bool) ($taskUser->is_transferred ?? false);
                                $isTransferredToRegular = (method_exists($taskUser, 'isTransferredFromAnother') ? $taskUser->isTransferredFromAnother() : false) || (bool) ($taskUser->is_additional_task ?? false) || (($taskUser->task_source ?? null) === 'transferred');
                                $canOriginalOwnerTransferRegular = false;
                                try { $canOriginalOwnerTransferRegular = ($taskUser->originalTaskUser && $taskUser->originalTaskUser->user_id === auth()->id()); } catch (\Throwable $e) { $canOriginalOwnerTransferRegular = false; }
                                $isApprovedRegular = $taskUser->hasAdministrativeApproval() || $taskUser->hasTechnicalApproval();
                                @endphp
                                <div class="kanban-task regular-task task-clickable {{ $isTransferredFromRegular ? 'transferred-from' : '' }} {{ $isTransferredToRegular ? 'transferred-to' : '' }} {{ $isApprovedRegular ? 'approved-task' : '' }} {{ $isProjectCancelled ? 'project-cancelled' : '' }}"
                                    draggable="{{ $canDragRegular ? 'true' : 'false' }}"
                                    data-task-id="{{ $task->id }}"
                                    data-task-user-id="{{ $taskUser->id }}"
                                    data-user-id="{{ $taskUser->user_id }}"
                                    data-task-type="regular_task"
                                    data-sidebar-task-type="regular"
                                    data-sidebar-task-user-id="{{ $taskUser->id }}"
                                    data-initial-minutes="{{ $actualMinutes }}"
                                    data-started-at="{{ $startedAt }}"
                                    data-status="{{ $taskUser->status }}"
                                    data-project-status="{{ $projectStatus }}"
                                    title="@if(!$canDragRegular) {{ $isProjectCancelled ? 'المشروع تم إلغاؤه - لا يمكنك تغيير حالة المهمة' : ($isApprovedRegular ? 'تم اعتماد هذه المهمة - لا يمكنك تغيير حالتها' : ($isTransferredFromRegular ? 'تم نقل هذه المهمة من حسابك - لا يمكنك تغيير حالتها' : 'هذه المهمة مخصصة لمستخدم آخر - لا يمكنك تحريكها')) }} @endif">

                                    <!-- Task Title -->
                                    <h6>{{ $task->name }}</h6>

                                    <!-- Regular Task Badge and Notes Indicator -->
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="d-flex align-items-center gap-1">
                                            <span class="badge bg-primary">📋 مهمة</span>
                                            @if($isApprovedRegular)
                                            <span class="badge bg-success"><i class="fas fa-shield-check me-1"></i>معتمدة</span>
                                            @endif
                                            @if($isTransferredFromRegular)
                                            <span class="badge bg-danger">تم نقلها</span>
                                            @endif
                                            @if($isTransferredToRegular)
                                            <span class="badge bg-success">منقولة إليك</span>
                                            <span class="badge bg-info text-dark">إضافية</span>
                                            @endif
                                        </div>
                                        @if($taskUser->notes_count > 0)
                                        <span class="task-notes-indicator" title="{{ $taskUser->notes_count }} ملاحظات">
                                            <i class="fas fa-sticky-note"></i>
                                            <span class="notes-count">{{ $taskUser->notes_count }}</span>
                                        </span>
                                        @endif
                                        @if($taskUser->revisions_count > 0)
                                        @php
                                        $revisionsStatus = 'pending'; // Default
                                        if ($taskUser->pending_revisions_count > 0) {
                                        if (($taskUser->approved_revisions_count ?? 0) > 0 || ($taskUser->rejected_revisions_count ?? 0) > 0) {
                                        $revisionsStatus = 'mixed';
                                        } else {
                                        $revisionsStatus = 'pending';
                                        }
                                        } else {
                                        if (($taskUser->approved_revisions_count ?? 0) > 0 && ($taskUser->rejected_revisions_count ?? 0) == 0) {
                                        $revisionsStatus = 'approved';
                                        } elseif (($taskUser->rejected_revisions_count ?? 0) > 0 && ($taskUser->approved_revisions_count ?? 0) == 0) {
                                        $revisionsStatus = 'rejected';
                                        } else {
                                        $revisionsStatus = 'mixed';
                                        }
                                        }

                                        $tooltipText = $taskUser->revisions_count . ' تعديلات';
                                        if ($taskUser->pending_revisions_count > 0) {
                                        $tooltipText .= ' - ' . $taskUser->pending_revisions_count . ' معلق';
                                        }
                                        if (($taskUser->approved_revisions_count ?? 0) > 0) {
                                        $tooltipText .= ' - ' . $taskUser->approved_revisions_count . ' مقبول';
                                        }
                                        if (($taskUser->rejected_revisions_count ?? 0) > 0) {
                                        $tooltipText .= ' - ' . $taskUser->rejected_revisions_count . ' مرفوض';
                                        }
                                        @endphp
                                        <span class="task-revisions-badge {{ $revisionsStatus }}" title="{{ $tooltipText }}">
                                            <i class="fas fa-edit"></i>
                                            <span class="revisions-count">{{ $taskUser->revisions_count }}</span>
                                        </span>
                                        @endif
                                    </div>

                                    <!-- Task Description -->
                                    @if($task->description)
                                    <div class="task-description">
                                        <div class="text-muted fw-semibold">
                                            <i class="fas fa-file-alt me-2"></i>
                                            <span>وصف المهمة</span>
                                        </div>
                                        <p>{{ $task->description }}</p>
                                    </div>
                                    @endif

                                    @if($isHRUser && $taskUser->user)
                                    <!-- User Info -->
                                    <div class="user-info">
                                        <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center"
                                            class="user-info-circle">
                                            <i class="fas fa-user text-white user-info-icon"></i>
                                        </div>
                                        <span class="text-dark fw-semibold">{{ $taskUser->user->name }}</span>
                                    </div>
                                    @endif

                                    <!-- Time Information -->
                                    <div class="time-info">
                                        <div class="time-row estimated">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-clock me-2"></i>
                                                <span>الوقت المقدر</span>
                                            </div>
                                            <span>{{ $taskUser->estimated_hours }}س {{ sprintf('%02d', $taskUser->estimated_minutes) }}د</span>
                                        </div>
                                        <div class="time-row actual">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-stopwatch me-2"></i>
                                                <span>الوقت الفعلي</span>
                                            </div>
                                            <span>
                                                @php
                                                $actualHours = intval($actualMinutes / 60);
                                                $remainingMinutes = $actualMinutes % 60;
                                                @endphp
                                                {{ $actualHours }}س {{ sprintf('%02d', $remainingMinutes) }}د
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Deadline Information -->
                                    @if($taskUser->due_date)
                                    @php
                                    $now = now();
                                    $isOverdue = $taskUser->due_date->isPast() && $taskUser->status !== 'completed';
                                    $isDueSoon = $taskUser->due_date->isFuture() && $taskUser->due_date->diffInHours($now) <= 24 && $taskUser->status !== 'completed';

                                        if ($taskUser->status === 'completed') {
                                        $deadlineClass = 'success';
                                        } elseif ($isOverdue) {
                                        $deadlineClass = 'danger';
                                        } elseif ($isDueSoon) {
                                        $deadlineClass = 'warning';
                                        } else {
                                        $deadlineClass = 'primary';
                                        }
                                        @endphp
                                        <div class="deadline-info {{ $deadlineClass }}">
                                            <div class="rounded-circle">
                                                <i class="fas fa-calendar"></i>
                                            </div>
                                            <div>
                                                <strong>{{ $taskUser->due_date->format('d/m/Y') }}</strong><br>
                                                <small>
                                                    @if($isOverdue)
                                                    متأخر {{ $taskUser->due_date->diffForHumans() }}
                                                    @elseif($isDueSoon)
                                                    ينتهي {{ $taskUser->due_date->diffForHumans() }}
                                                    @elseif($taskUser->status === 'completed')
                                                    مكتمل في الموعد
                                                    @else
                                                    {{ $taskUser->due_date->diffForHumans() }}
                                                    @endif
                                                </small>
                                            </div>
                                        </div>
                                        @endif

                                        <!-- Timer and Actions -->
                                        <div class="task-actions">
                                            <span class="timer" id="kanban-timer-regular-{{ $taskUser->id }}">{{ $formattedTime }}</span>
                                            @php
                                            // ✅ تحديد إمكانية النقل أو التعديل
                                            $canShowTransferBtn = false;
                                            $transferBtnMode = 'transfer';
                                            $transferBtnTitle = 'نقل المهمة';
                                            $transferBtnIcon = 'fas fa-exchange-alt';

                                            // الشروط الأساسية: المهمة ليست مكتملة أو ملغاة والمشروع لم يتم إلغاؤه
                                            if (!in_array($taskUser->status, ['completed', 'cancelled']) && !$isProjectCancelled) {
                                            // ❌ منع ظهور الزر للمهام الأصلية المنقولة
                                            if ($isTransferredFromRegular) {
                                            $canShowTransferBtn = false;
                                            }
                                            // ✅ السماح بتعديل المستلم للمهام المنقولة (للشخص الحالي أو HR)
                                            elseif ($isTransferredToRegular && (auth()->user()->hasRole('hr') || $taskUser->user_id === auth()->id())) {
                                            $canShowTransferBtn = true;
                                            $transferBtnMode = 'reassign';
                                            $transferBtnTitle = 'تعديل المستلم';
                                            $transferBtnIcon = 'fas fa-user-edit';
                                            }
                                            // ✅ السماح بالنقل للمهام الأصلية غير المنقولة
                                            elseif (!$isTransferredFromRegular && (auth()->user()->hasRole('hr') || $taskUser->user_id === auth()->id() || $canOriginalOwnerTransferRegular)) {
                                            $canShowTransferBtn = true;
                                            $transferBtnMode = 'transfer';
                                            $transferBtnTitle = 'نقل المهمة';
                                            $transferBtnIcon = 'fas fa-exchange-alt';
                                            }
                                            }
                                            @endphp

                                            @if($canShowTransferBtn)
                                            <button class="transfer-btn {{ $transferBtnMode === 'reassign' ? 'reassign-btn' : '' }}"
                                                data-task-type="task"
                                                data-task-id="{{ $taskUser->id }}"
                                                data-task-name="{{ $task->name ?? 'مهمة' }}"
                                                data-user-name="{{ $taskUser->user->name ?? auth()->user()->name }}"
                                                data-mode="{{ $transferBtnMode }}"
                                                onclick="event.stopPropagation(); openTransferModal(this.dataset.taskType, this.dataset.taskId, this.dataset.taskName, this.dataset.userName, this.dataset.mode)"
                                                title="{{ $transferBtnTitle }}">
                                                <i class="{{ $transferBtnIcon }}"></i>
                                            </button>
                                            @elseif($isProjectCancelled)
                                            <button class="transfer-btn" disabled title="لا يمكن نقل المهام التابعة لمشروع ملغي">
                                                <i class="fas fa-exchange-alt"></i>
                                            </button>
                                            @endif

                                            {{-- زر إلغاء النقل للمهام المنقولة --}}
                                            @if($isTransferredToRegular && !in_array($taskUser->status, ['completed', 'cancelled']) &&
                                            (auth()->user()->hasRole('hr') || auth()->user()->hasRole('admin') || $taskUser->user_id === auth()->id()))
                                            <button class="cancel-transfer-task btn btn-sm btn-danger"
                                                data-task-type="regular"
                                                data-task-id="{{ $taskUser->id }}"
                                                data-task-name="{{ $task->name ?? 'مهمة' }}"
                                                title="إلغاء النقل"
                                                style="padding: 4px 8px; font-size: 12px; border-radius: 4px;">
                                                <i class="fas fa-undo"></i>
                                            </button>
                                            @endif
                                        </div>

                                </div>
                                @endif
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Calendar View -->
    <div id="calendarViewShow" class="calendar-view hidden">
        <div class="calendar-container">
            <!-- Calendar Header -->
            <div class="calendar-header">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-outline-secondary" id="prevMonthProjectShow">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <h5 id="currentMonthYearProjectShow" class="mb-0 fw-bold"></h5>
                        <button class="btn btn-outline-secondary" id="nextMonthProjectShow">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-sm btn-outline-primary" id="backToKanbanBtn">
                            <i class="fas fa-columns me-1"></i>
                            العودة للكانبان
                        </button>
                        <button class="btn btn-sm btn-primary" id="todayBtnProjectShow">اليوم</button>
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
                <div class="calendar-days" id="calendarDaysProjectShow">
                    <!-- Days will be populated by JavaScript -->
                </div>
            </div>

            <!-- دليل ألوان المهام -->
            <div class="task-status-legend">
                <h6>
                    <i class="fas fa-palette"></i>
                    دليل ألوان المهام
                </h6>
                <div class="legend-items">
                    <div class="legend-item">
                        <div class="legend-color new"></div>
                        <span class="legend-text">مهام جديدة</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color in_progress"></div>
                        <span class="legend-text">مهام شغالة</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color paused"></div>
                        <span class="legend-text">مهام متوقفة</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color completed"></div>
                        <span class="legend-text">مهام مكتملة</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color cancelled"></div>
                        <span class="legend-text">مهام ملغية</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Task Details Sidebar -->
<div id="taskSidebar" class="task-sidebar">
    <!-- Sidebar Header -->
    <div class="sidebar-header">
        <div class="d-flex justify-content-between align-items-start">
            <div class="flex-grow-1">
                <h4 class="mb-2 fw-bold text-dark sidebar-title" id="taskSidebarTitle">تفاصيل المهمة</h4>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge rounded-pill px-3 py-1 sidebar-badge" id="taskSidebarBadge">جاري التحميل...</span>
                    <small class="text-muted" id="taskSidebarSubtitle">جاري التحميل...</small>
                </div>
            </div>
            <button onclick="closeTaskSidebar()" class="btn btn-light rounded-circle p-2 sidebar-close-btn">
                <i class="fas fa-times text-muted"></i>
            </button>
        </div>
    </div>

    <!-- Sidebar Content -->
    <div class="sidebar-content p-4" id="taskSidebarContent">
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">جاري التحميل...</span>
            </div>
            <p class="mt-3 text-muted">جاري تحميل تفاصيل المهمة...</p>
        </div>
    </div>
</div>

<!-- Sidebar Overlay -->
<div id="sidebarOverlay" class="sidebar-overlay" onclick="closeTaskSidebar()"></div>

<script src="{{ asset('js/projects/task-sidebar.js') }}"></script>