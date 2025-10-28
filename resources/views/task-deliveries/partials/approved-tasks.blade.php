<!-- إحصائيات سريعة للمهام المعتمدة -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="stat-card-small success">
            <div class="stat-number">{{ $approvedTasks['regular_tasks']->count() }}</div>
            <div class="stat-label">مهام عادية معتمدة</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card-small info">
            <div class="stat-number">{{ $approvedTasks['template_tasks']->count() }}</div>
            <div class="stat-label">مهام قوالب معتمدة</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card-small primary">
            <div class="stat-number">{{ $approvedTasks['total_approved'] }}</div>
            <div class="stat-label">إجمالي المهام المعتمدة</div>
        </div>
    </div>
</div>

<!-- التبويبات الفرعية للمهام المعتمدة -->
<ul class="nav nav-pills mb-4" id="approvedSubTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="approved-regular-tab" data-bs-toggle="pill"
                data-bs-target="#approved-regular" type="button" role="tab">
            <i class="fas fa-tasks text-success"></i>
            المهام العادية المعتمدة
            <span class="badge bg-success">{{ $approvedTasks['regular_tasks']->count() }}</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="approved-template-tab" data-bs-toggle="pill"
                data-bs-target="#approved-template" type="button" role="tab">
            <i class="fas fa-layer-group text-info"></i>
            مهام القوالب المعتمدة
            <span class="badge bg-info">{{ $approvedTasks['template_tasks']->count() }}</span>
        </button>
    </li>
</ul>

<!-- محتوى التبويبات الفرعية -->
<div class="tab-content" id="approvedSubTabsContent">
    <!-- المهام العادية المعتمدة -->
    <div class="tab-pane fade show active" id="approved-regular" role="tabpanel">
        @if($approvedTasks['regular_tasks']->count() > 0)
            @foreach($approvedTasks['regular_tasks'] as $taskUser)
                <div class="card approval-card regular-task" onclick="openTaskSidebar('regular', {{ $taskUser->id }})" style="cursor: pointer;">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <div class="user-info-section">
                                    <img src="{{ $taskUser->user->profile_photo_url }}"
                                         class="user-avatar" alt="{{ $taskUser->user->name }}">
                                    <div class="user-details">
                                        <h6>{{ $taskUser->task->name }}</h6>
                                        <p class="user-name">{{ $taskUser->user->name }}</p>
                                        <div class="task-creator-info mb-2">
                                            <small class="text-success">
                                                <i class="fas fa-user-plus"></i> أنشأ بواسطة: {{ $taskUser->task->createdBy->name ?? 'غير محدد' }}
                                            </small>
                                        </div>
                                        @if(isset($approvedTasks['is_hr_or_admin']) && $approvedTasks['is_hr_or_admin'])
                                            <div class="project-info">
                                                <small class="text-info">
                                                    <i class="fas fa-building"></i> {{ $taskUser->task->project->name ?? 'غير محدد' }}
                                                </small>
                                                <small class="text-primary">
                                                    <i class="fas fa-user-tie"></i> {{ $taskUser->task->project->manager ?? 'غير محدد' }}
                                                </small>
                                            </div>
                                        @endif
                                        <span class="task-meta-badge regular">
                                            <i class="fas fa-tasks"></i> مهمة عادية - معتمدة
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="points-section">
                                    <div class="points-display">{{ $taskUser->awarded_points ?? $taskUser->task->points }} نقطة</div>
                                    <div class="points-label">النقاط المعتمدة</div>
                                    <div class="task-timer">
                                        <i class="fas fa-clock"></i>
                                        {{ $taskUser->actual_hours }}:{{ str_pad($taskUser->actual_minutes, 2, '0', STR_PAD_LEFT) }}
                                    </div>
                                    <div class="mt-2">
                                        <small class="text-success">
                                            <i class="fas fa-user-check"></i> {{ $taskUser->approvedBy->name ?? 'غير محدد' }}
                                        </small>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-calendar-check"></i> {{ $taskUser->approved_at ? $taskUser->approved_at->format('Y-m-d H:i') : 'غير محدد' }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="action-section">
                                    @if($taskUser->approval_note)
                                        <button class="btn btn-sm btn-outline-info mb-2" onclick="showApprovalNote('{{ addslashes($taskUser->approval_note) }}')">
                                            <i class="fas fa-comment"></i> عرض الملاحظة
                                        </button>
                                    @endif
                                    <button class="btn btn-sm btn-outline-warning" onclick="editTaskPoints('regular', {{ $taskUser->id }}, {{ $taskUser->awarded_points ?? $taskUser->task->points }})">
                                        <i class="fas fa-edit"></i> تعديل النقاط
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        @else
            <div class="empty-state">
                <i class="fas fa-check-circle text-success"></i>
                <h5>لا توجد مهام عادية معتمدة</h5>
                <p class="text-muted">لا توجد مهام عادية تم اعتمادها في الفترة المحددة.</p>
            </div>
        @endif
    </div>

    <!-- مهام القوالب المعتمدة -->
    <div class="tab-pane fade" id="approved-template" role="tabpanel">
        @if($approvedTasks['template_tasks']->count() > 0)
            @foreach($approvedTasks['template_tasks'] as $templateTaskUser)
                <div class="card approval-card template-task" onclick="openTaskSidebar('template', {{ $templateTaskUser->id }})" style="cursor: pointer;">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <div class="user-info-section">
                                    <img src="{{ $templateTaskUser->user->profile_photo_url }}"
                                         class="user-avatar" alt="{{ $templateTaskUser->user->name }}">
                                    <div class="user-details">
                                        <h6>{{ $templateTaskUser->templateTask->name }}</h6>
                                        <p class="user-name">{{ $templateTaskUser->user->name }}</p>
                                        <div class="task-creator-info mb-2">
                                            <small class="text-warning">
                                                <i class="fas fa-user-plus"></i> أضيف بواسطة: {{ $templateTaskUser->assignedBy->name ?? 'غير محدد' }}
                                            </small>
                                        </div>
                                        @if(isset($approvedTasks['is_hr_or_admin']) && $approvedTasks['is_hr_or_admin'])
                                            <div class="project-info">
                                                <small class="text-info">
                                                    <i class="fas fa-building"></i> {{ $templateTaskUser->project->name ?? 'غير محدد' }}
                                                </small>
                                                <small class="text-primary">
                                                    <i class="fas fa-user-tie"></i> {{ $templateTaskUser->project->manager ?? 'غير محدد' }}
                                                </small>
                                            </div>
                                        @endif
                                        <span class="task-meta-badge template">
                                            <i class="fas fa-layer-group"></i> قالب - معتمد
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="points-section">
                                    <div class="points-display">{{ $templateTaskUser->awarded_points ?? $templateTaskUser->templateTask->points }} نقطة</div>
                                    <div class="points-label">النقاط المعتمدة</div>
                                    <div class="task-timer">
                                        <i class="fas fa-clock"></i>
                                        {{ floor($templateTaskUser->actual_minutes / 60) }}:{{ str_pad($templateTaskUser->actual_minutes % 60, 2, '0', STR_PAD_LEFT) }}
                                    </div>
                                    <div class="mt-2">
                                        <small class="text-success">
                                            <i class="fas fa-user-check"></i> {{ $templateTaskUser->approvedBy->name ?? 'غير محدد' }}
                                        </small>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-calendar-check"></i> {{ $templateTaskUser->approved_at ? $templateTaskUser->approved_at->format('Y-m-d H:i') : 'غير محدد' }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="action-section">
                                    @if($templateTaskUser->approval_note)
                                        <button class="btn btn-sm btn-outline-info mb-2" onclick="showApprovalNote('{{ addslashes($templateTaskUser->approval_note) }}')">
                                            <i class="fas fa-comment"></i> عرض الملاحظة
                                        </button>
                                    @endif
                                    <button class="btn btn-sm btn-outline-warning" onclick="editTaskPoints('template', {{ $templateTaskUser->id }}, {{ $templateTaskUser->awarded_points ?? $templateTaskUser->templateTask->points }})">
                                        <i class="fas fa-edit"></i> تعديل النقاط
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        @else
            <div class="empty-state">
                <i class="fas fa-layer-group text-info"></i>
                <h5>لا توجد مهام قوالب معتمدة</h5>
                <p class="text-muted">لا توجد مهام قوالب تم اعتمادها في الفترة المحددة.</p>
            </div>
        @endif
    </div>
</div>
