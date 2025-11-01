<!-- Kanban Board View -->
<div id="kanbanBoard" class="kanban-board">
    <div class="kanban-columns">
        @php
        // تجميع المهام حسب الحالة
        $tasksByStatus = [
        'new' => [],
        'in_progress' => [],
        'paused' => [],
        'completed' => [],
        'cancelled' => []
        ];

        foreach($tasks as $task) {
        $displayStatus = isset($task->user_status) && $task->user_status !== 'not_assigned'
        ? $task->user_status
        : (isset($task->pivot->status) ? $task->pivot->status : $task->status);

        if (isset($tasksByStatus[$displayStatus])) {
        $tasksByStatus[$displayStatus][] = $task;
        }
        }
        @endphp

        @foreach(['new' => 'جديدة', 'in_progress' => 'قيد التنفيذ', 'paused' => 'متوقفة', 'completed' => 'مكتملة', 'cancelled' => 'ملغاة'] as $status => $statusText)
        <div class="kanban-column" data-status="{{ $status }}">
            <div class="kanban-header {{ $status }}">
                <h6>
                    @if($status == 'new')<i class="fas fa-circle-plus"></i>
                    @elseif($status == 'in_progress')<i class="fas fa-play-circle"></i>
                    @elseif($status == 'paused')<i class="fas fa-pause-circle"></i>
                    @elseif($status == 'completed')<i class="fas fa-check-circle"></i>
                    @elseif($status == 'cancelled')<i class="fas fa-times-circle"></i>
                    @endif
                    {{ $statusText }}
                </h6>
                <span class="task-count" id="count-{{ $status }}">{{ count($tasksByStatus[$status]) }}</span>
            </div>
            <div class="kanban-cards" id="cards-{{ $status }}">
                @foreach($tasksByStatus[$status] as $task)
                @php
                $displayStatus = isset($task->user_status) && $task->user_status !== 'not_assigned'
                ? $task->user_status
                : (isset($task->pivot->status) ? $task->pivot->status : $task->status);

                $currentUserId = Auth::id();
                $isMyTask = $task->created_by == $currentUserId;

                // بيانات المستخدمين المعينين
                $assignees = [];
                if(isset($task->is_template) && $task->is_template) {
                if($task->users && $task->users->count() > 0) {
                $user = $task->users->first();
                $assignees[] = [
                'name' => $user->name,
                'role' => 'منفذ قالب',
                'avatar' => $user->profile_photo_url
                ];
                }
                } else {
                foreach($task->users as $user) {
                if($user) {
                $assignees[] = [
                'name' => $user->name,
                'role' => $user->pivot?->role ?? 'غير محدد',
                'avatar' => $user->profile_photo_url
                ];
                }
                }
                }

                // الوقت المقدر والفعلي
                $estimatedTime = ($task->is_flexible_time ?? false) ? 'مرن' : $task->estimated_hours . ':' . str_pad($task->estimated_minutes, 2, '0', STR_PAD_LEFT);
                $actualTime = isset($task->is_template) && $task->is_template
                ? $task->actual_hours . ':' . str_pad($task->actual_minutes, 2, '0', STR_PAD_LEFT)
                : (isset($task->pivot) && $task->pivot
                ? $task->pivot->actual_hours . ':' . str_pad($task->pivot->actual_minutes, 2, '0', STR_PAD_LEFT)
                : '0:00');

                // تاريخ الاستحقاق
                // ✅ دعم Template Tasks: due_date أو deadline
                $dueDate = '';
                if (isset($task->is_template) && $task->is_template) {
                // Template Tasks: أول due_date، لو مش موجود استخدم deadline
                if (isset($task->due_date) && $task->due_date) {
                $dueDate = $task->due_date->format('Y-m-d H:i');
                } elseif (isset($task->deadline) && $task->deadline) {
                $dueDate = $task->deadline->format('Y-m-d H:i');
                }
                } else {
                // Regular Tasks
                $dueDate = (isset($task->due_date) && $task->due_date) ? $task->due_date->format('Y-m-d H:i') : '';
                }

                // بداية المهمة للتايمر
                $startedAt = '';
                if($displayStatus === 'in_progress') {
                if(isset($task->is_template) && $task->is_template) {
                $startedAt = ($task->status === 'in_progress' && isset($task->started_at) && $task->started_at) ? strtotime($task->started_at) * 1000 : '';
                } else {
                $startedAt = (((isset($task->user_status) && $task->user_status === 'in_progress') || (isset($task->pivot->status) && $task->pivot->status === 'in_progress')) ? (isset($task->pivot->start_date) && $task->pivot->start_date ? strtotime($task->pivot->start_date) * 1000 : (isset($task->pivot->started_at) && $task->pivot->started_at ? strtotime($task->pivot->started_at) * 1000 : '')) : '');
                }
                }

                // الدقائق الأولية
                $initialMinutes = isset($task->is_template) && $task->is_template
                ? (($task->actual_hours * 60) + $task->actual_minutes)
                : (isset($task->pivot) && $task->pivot
                ? (($task->pivot->actual_hours * 60) + $task->pivot->actual_minutes)
                : 0);
                @endphp

                @php
                // التحقق من الاعتماد للمهام المعتمدة
                $isTaskApproved = false;
                if (isset($task->is_template) && $task->is_template) {
                $isTaskApproved = ($task->administrative_approval ?? false) || ($task->technical_approval ?? false);
                } else {
                $isTaskApproved = ($task->pivot->administrative_approval ?? false) || ($task->pivot->technical_approval ?? false);
                }

                // التحقق من حالة المشروع
                $projectStatus = isset($task->project) && isset($task->project->status) ? $task->project->status : '';
                $isProjectCancelled = $projectStatus === 'ملغي';
                @endphp

                <div class="kanban-card {{ $isMyTask ? 'my-created-task' : '' }} {{ $isTaskApproved ? 'task-approved' : '' }} {{ $isProjectCancelled ? 'project-cancelled' : '' }}"
                    data-task-id="{{ $task->id }}"
                    data-project-id="{{ $task->project_id ?? 0 }}"
                    data-project-status="{{ $projectStatus }}"
                    data-status="{{ $displayStatus }}"
                    data-is-template="{{ isset($task->is_template) && $task->is_template ? 'true' : 'false' }}"
                    data-is-approved="{{ $isTaskApproved ? 'true' : 'false' }}"
                    data-initial-minutes="{{ $initialMinutes }}"
                    data-started-at="{{ $startedAt }}"
                    data-due-date="{{
                            isset($task->is_template) && $task->is_template
                            ? ((isset($task->due_date) && $task->due_date) ? $task->due_date->format('Y-m-d') : ((isset($task->deadline) && $task->deadline) ? $task->deadline->format('Y-m-d') : ''))
                            : ((isset($task->due_date) && $task->due_date) ? $task->due_date->format('Y-m-d') : ((isset($task->pivot) && $task->pivot && isset($task->pivot->due_date) && $task->pivot->due_date) ? \Carbon\Carbon::parse($task->pivot->due_date)->format('Y-m-d') : ''))
                        }}"
                    data-created-at="{{ $task->created_at ? $task->created_at->format('Y-m-d') : '' }}">

                    <div class="kanban-card-title">{{ $task->name }}</div>

                    @php
                    // التحقق من الاعتماد الإداري والفني
                    $hasAdministrativeApproval = false;
                    $hasTechnicalApproval = false;
                    $approvalDate = null;
                    $approverName = null;

                    if (isset($task->is_template) && $task->is_template) {
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
                    @endphp

                    @if(isset($task->is_template) && $task->is_template || isset($task->notes_count) && $task->notes_count > 0 || isset($task->revisions_count) && $task->revisions_count > 0 || isset($task->is_transferred) && $task->is_transferred || isset($task->is_additional_task) && $task->is_additional_task || $isApproved)
                    <div class="kanban-card-badges mb-2">
                        @if(isset($task->is_template) && $task->is_template)
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
                        @if(isset($task->is_transferred) && $task->is_transferred)
                        <span class="badge badge-sm bg-warning ms-1"><i class="fas fa-exchange-alt"></i> منقول</span>
                        @endif
                        @if(isset($task->is_additional_task) && $task->is_additional_task)
                        <span class="badge badge-sm bg-success ms-1"><i class="fas fa-plus"></i> إضافي</span>
                        @endif
                    </div>
                    @endif

                    <div class="kanban-card-meta">
                        <span class="kanban-card-project">
                            @if($task->project)
                            <strong>{{ $task->project->code ?? '' }}</strong> {{ $task->project->name }}
                            @else
                            غير محدد
                            @endif
                        </span>
                        <span class="kanban-card-service">{{ $task->service->name ?? 'غير محدد' }}</span>
                    </div>

                    <div class="kanban-card-meta mb-2">
                        <span class="kanban-card-creator" data-creator-id="{{ $task->created_by }}">
                            أنشأت بواسطة: {{ $task->createdBy->name ?? 'غير محدد' }}
                        </span>
                    </div>

                    {{-- معلومات النقل للمهمة الأصلية (المنقولة من) --}}
                    @if(isset($task->is_transferred) && $task->is_transferred)
                    <div class="kanban-card-transfer-info" style="background: #fee; padding: 8px; border-radius: 4px; margin-bottom: 8px;">
                        <i class="fas fa-exchange-alt text-danger"></i>
                        <strong class="text-danger">تم النقل إلى:</strong> {{ $task->transferred_to_user->name ?? 'غير محدد' }}
                        @if(isset($task->transferred_at))
                        <small class="d-block text-muted">في: {{ $task->transferred_at->format('Y-m-d H:i') }}</small>
                        @endif
                    </div>
                    @endif

                    {{-- معلومات النقل للمهمة المنقولة (منقولة إلى) --}}
                    @if(isset($task->is_additional_task) && $task->is_additional_task && isset($task->task_source) && $task->task_source === 'transferred')
                    <div class="kanban-card-transfer-info" style="background: #efe; padding: 8px; border-radius: 4px; margin-bottom: 8px;">
                        <i class="fas fa-user-plus text-success"></i>
                        <strong class="text-success">منقولة من:</strong> {{ $task->original_user->name ?? 'غير محدد' }}
                        @if(isset($task->transferred_at))
                        <small class="d-block text-muted">في: {{ $task->transferred_at->format('Y-m-d H:i') }}</small>
                        @endif
                    </div>
                    @endif

                    @if(count($assignees) > 0)
                    <div class="kanban-card-assignees">
                        <div class="avatar-group">
                            @foreach(array_slice($assignees, 0, 3) as $assignee)
                            <div class="avatar" title="{{ $assignee['name'] }} ({{ $assignee['role'] }})">
                                <img src="{{ $assignee['avatar'] }}" alt="{{ $assignee['name'] }}">
                            </div>
                            @endforeach
                            @if(count($assignees) > 3)
                            <div class="avatar" style="background: #6b7280; color: white; display: flex; align-items: center; justify-content: center; font-size: 10px;">
                                +{{ count($assignees) - 3 }}
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    <div class="kanban-card-time">
                        <span>مقدر: {{ $estimatedTime }}</span>
                        <span>فعلي: {{ $actualTime }}</span>
                    </div>

                    <div class="kanban-card-points">
                        <span class="badge bg-warning text-dark">
                            <i class="fas fa-star"></i> {{ $task->points ?? 10 }} نقطة
                        </span>
                    </div>

                    @if($displayStatus === 'in_progress')
                    <div class="kanban-card-timer">
                        <i class="fas fa-clock"></i>
                        <span id="kanban-timer-{{ $task->id }}">00:00:00</span>
                    </div>
                    @endif

                    @if($dueDate && $dueDate !== '')
                    <div class="kanban-card-due-date">
                        <i class="fas fa-calendar"></i> {{ $dueDate }}
                    </div>
                    @endif

                    <div class="kanban-card-actions">
                        @php
                        // ✅ التحقق من كون المهمة منقولة منه فقط (للتحكم في زر العين في الكانبان)
                        // المهمة الأصلية المنقولة (is_transferred = true) → مقفول
                        // المهمة المنقولة إليه (is_additional_task) → مفتوح عادي
                        $kanbanViewBtnIsTransferredFrom = (isset($task->is_transferred) && $task->is_transferred) ||
                        (isset($task->pivot->is_transferred) && $task->pivot->is_transferred);

                        $isKanbanViewDisabled = $kanbanViewBtnIsTransferredFrom; // مقفول للمنقول منه فقط
                        @endphp

                        <button class="btn btn-sm {{ $isKanbanViewDisabled ? 'btn-outline-secondary' : 'btn-outline-primary' }} view-task"
                            data-id="{{ $task->id }}"
                            data-task-user-id="{{ isset($task->is_template) && $task->is_template ? $task->id : (isset($task->pivot->id) ? $task->pivot->id : $task->id) }}"
                            data-is-template="{{ isset($task->is_template) && $task->is_template ? 'true' : 'false' }}"
                            {{ $isKanbanViewDisabled ? 'disabled' : '' }}
                            title="{{ $isKanbanViewDisabled ? '🔒 المهمة تم نقلها - العرض معطل' : 'عرض التفاصيل' }}">
                            <i class="fas fa-{{ $isKanbanViewDisabled ? 'eye-slash' : 'eye' }}"></i>
                        </button>
                        @if(!(isset($task->is_template) && $task->is_template))
                        {{-- زر التعديل للمهام العادية فقط --}}
                        <button class="btn btn-sm btn-outline-success edit-task"
                            data-id="{{ $task->id }}"
                            data-task-user-id="{{ isset($task->pivot->id) ? $task->pivot->id : $task->id }}"
                            data-is-template="false"
                            title="تعديل">
                            <i class="fas fa-edit"></i>
                        </button>
                        @else
                        {{-- زر معطل لمهام القوالب --}}
                        <button class="btn btn-sm btn-outline-secondary"
                            disabled
                            title="لا يمكن تعديل مهام القوالب">
                            <i class="fas fa-lock"></i>
                        </button>
                        @endif

                        @php
                        // التحقق من كون المهمة منقولة (للتمييز بين نقل وتعديل المستلم)
                        $kanbanTaskSource = $task->task_source ?? ($task->pivot->task_source ?? null);
                        $kanbanIsAdditional = (isset($task->is_additional_task) && $task->is_additional_task) ||
                        (isset($task->pivot->is_additional_task) && $task->pivot->is_additional_task);

                        $isTransferredTaskForBtnKanban = $kanbanIsAdditional && $kanbanTaskSource === 'transferred';

                        $transferBtnModeKanban = $isTransferredTaskForBtnKanban ? 'reassign' : 'transfer';
                        $transferBtnTitleKanban = $isTransferredTaskForBtnKanban ? 'تعديل المستلم' : 'نقل المهمة';
                        $transferBtnIconKanban = $isTransferredTaskForBtnKanban ? 'fas fa-user-edit' : 'fas fa-exchange-alt';
                        $transferBtnClassKanban = $isTransferredTaskForBtnKanban ? 'btn-outline-info' : 'btn-outline-warning';
                        @endphp

                        @if(!in_array($displayStatus, ['completed', 'cancelled']) && !(isset($task->is_transferred) && $task->is_transferred) && !$isProjectCancelled && (auth()->user()->hasRole('hr') || auth()->user()->hasRole('admin') || $task->created_by == Auth::id()))
                        <button class="btn btn-sm {{ $transferBtnClassKanban }} transfer-task"
                            data-task-type="{{ isset($task->is_template) && $task->is_template ? 'template' : 'regular' }}"
                            data-task-id="{{ $task->id }}"
                            data-task-user-id="{{ isset($task->pivot->id) ? $task->pivot->id : (isset($task->task_user_id) ? $task->task_user_id : $task->id) }}"
                            data-task-name="{{ $task->name }}"
                            data-current-user="{{ count($assignees) > 0 ? $assignees[0]['name'] : 'غير محدد' }}"
                            data-mode="{{ $transferBtnModeKanban }}"
                            title="{{ $transferBtnTitleKanban }}">
                            <i class="{{ $transferBtnIconKanban }}"></i>
                        </button>
                        @elseif($isProjectCancelled)
                        <button class="btn btn-sm btn-outline-secondary"
                            disabled
                            title="لا يمكن نقل المهام التابعة لمشروع ملغي">
                            <i class="fas fa-exchange-alt"></i>
                        </button>
                        @endif

                        @php
                        // التحقق من كون المهمة منقولة (لزر إلغاء النقل)
                        $kanbanCancelTaskSource = $task->task_source ?? ($task->pivot->task_source ?? null);
                        $kanbanCancelIsAdditional = (isset($task->is_additional_task) && $task->is_additional_task) ||
                        (isset($task->pivot->is_additional_task) && $task->pivot->is_additional_task);

                        $isTransferredTaskKanban = $kanbanCancelIsAdditional && $kanbanCancelTaskSource === 'transferred';
                        @endphp
                        @if($isTransferredTaskKanban && !in_array($displayStatus, ['completed', 'cancelled']) &&
                        (auth()->user()->hasRole('hr') || auth()->user()->hasRole('admin') ||
                        (isset($assignees[0]) && isset($assignees[0]['name']))))
                        <button class="btn btn-sm btn-outline-danger cancel-transfer-task"
                            data-task-type="{{ isset($task->is_template) && $task->is_template ? 'template' : 'regular' }}"
                            data-task-id="{{ isset($task->pivot->id) ? $task->pivot->id : $task->id }}"
                            data-task-name="{{ $task->name }}"
                            title="إلغاء النقل">
                            <i class="fas fa-undo"></i>
                        </button>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
</div>