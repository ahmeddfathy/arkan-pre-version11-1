<div class="table-responsive">
    <table class="table align-items-center mb-0" id="tasksTable">
        <thead>
            <tr>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">المهمة</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">المشروع</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">الخدمة</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">أنشأت بواسطة</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">الموظفين</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">النقاط</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">الوقت المقدر</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">الوقت الفعلي</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">الحالة</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">تاريخ الاستحقاق</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tasks as $task)
            @php
            $projectStatus = isset($task->project) && isset($task->project->status) ? $task->project->status : '';
            $isProjectCancelled = $projectStatus === 'ملغي';
            @endphp
            <tr data-task-id="{{ isset($task->task_user_id) ? $task->task_user_id : $task->id }}"
                data-task-user-id="{{ isset($task->is_template) && $task->is_template ? $task->id : (isset($task->pivot->id) ? $task->pivot->id : $task->id) }}"
                data-project-id="{{ $task->project_id }}"
                data-project-status="{{ $projectStatus }}"
                data-service-id="{{ $task->service_id }}"
                data-status="{{ isset($task->user_status) && $task->user_status !== 'not_assigned' ? $task->user_status : (isset($task->pivot->status) ? $task->pivot->status : $task->status) }}"
                data-created-by="{{ $task->created_by ?? '' }}"
                data-assigned-users="{{ json_encode($task->users->pluck('id')->toArray()) }}"
                data-is-template="{{ isset($task->is_template) && $task->is_template ? 'true' : 'false' }}"
                data-initial-minutes="{{ isset($task->is_template) && $task->is_template ? (($task->actual_hours * 60) + $task->actual_minutes) : (isset($task->pivot) && $task->pivot ? (($task->pivot->actual_hours * 60) + $task->pivot->actual_minutes) : 0) }}"
                data-revisions-count="{{ $task->revisions_count ?? 0 }}"
                data-pending-revisions-count="{{ $task->pending_revisions_count ?? 0 }}"
                data-approved-revisions-count="{{ $task->approved_revisions_count ?? 0 }}"
                data-rejected-revisions-count="{{ $task->rejected_revisions_count ?? 0 }}"
                data-revisions-status="{{ $task->revisions_status ?? 'none' }}"
                data-is-transferred="{{ $task->is_transferred ?? false }}"
                data-transferred-to-user-id="{{ $task->transferred_to_user_id ?? null }}"
                data-original-task-user-id="{{ $task->original_task_user_id ?? null }}"
                data-task-source="{{ $task->task_source ?? 'original' }}"
                data-is-additional-task="{{ $task->is_additional_task ?? false }}"
                data-due-date="{{
                    isset($task->is_template) && $task->is_template
                    ? ((isset($task->due_date) && $task->due_date) ? $task->due_date->format('Y-m-d') : ((isset($task->deadline) && $task->deadline) ? $task->deadline->format('Y-m-d') : ''))
                    : ((isset($task->due_date) && $task->due_date) ? $task->due_date->format('Y-m-d') : ((isset($task->pivot) && isset($task->pivot->due_date) && $task->pivot->due_date) ? \Carbon\Carbon::parse($task->pivot->due_date)->format('Y-m-d') : ''))
                }}"
                data-created-at="{{ $task->created_at ? $task->created_at->format('Y-m-d') : '' }}"
                data-started-at="{{
                    // 🔧 مهام التمبليت: استخدام started_at من TemplateTaskUser
                    (isset($task->is_template) && $task->is_template) ?
                        (($task->status === 'in_progress' && isset($task->started_at) && $task->started_at) ? strtotime($task->started_at) * 1000 : '') :
                    // المهام العادية: استخدام pivot
                    (((isset($task->user_status) && $task->user_status === 'in_progress') || (isset($task->pivot->status) && $task->pivot->status === 'in_progress')) ? (isset($task->pivot->start_date) && $task->pivot->start_date ? strtotime($task->pivot->start_date) * 1000 : (isset($task->pivot->started_at) && $task->pivot->started_at ? strtotime($task->pivot->started_at) * 1000 : '')) : '')
                }}"
                data-is-flexible="{{ ($task->is_flexible_time ?? false) ? 'true' : 'false' }}"
                data-points="{{ $task->points ?? 10 }}"
                class="{{ $task->created_by == Auth::id() ? 'my-created-task' : '' }} {{ $isProjectCancelled ? 'project-cancelled-row' : '' }}">
                <td>
                    <div class="d-flex px-2 py-1">
                        <div class="d-flex flex-column justify-content-center">
                            <h6 class="mb-0 text-sm">
                                {{ $task->name }}

                                <!-- Task Type Badge -->
                                @if(isset($task->is_template) && $task->is_template)
                                <span class="badge bg-success">🏢 قالب</span>
                                @else
                                <span class="badge bg-primary">📋 مهمة</span>
                                @endif

                                <!-- Transfer Status Badges -->
                                @php
                                // للمهام العادية: نتحقق من task نفسه
                                // للمهام القوالب: نتحقق من task (القوالب مختلفة)
                                $isTransferredFrom = (isset($task->is_transferred) && $task->is_transferred) ||
                                (isset($task->pivot->is_transferred) && $task->pivot->is_transferred);

                                $taskSource = $task->task_source ?? ($task->pivot->task_source ?? null);
                                $isAdditional = (isset($task->is_additional_task) && $task->is_additional_task) ||
                                (isset($task->pivot->is_additional_task) && $task->pivot->is_additional_task);

                                $isTransferredTo = $isAdditional && $taskSource === 'transferred';
                                @endphp

                                @if($isTransferredFrom)
                                <span class="badge bg-danger">تم نقلها</span>
                                @endif

                                @if($isTransferredTo)
                                <span class="badge bg-success">منقولة إليك</span>
                                <span class="badge bg-info text-dark">إضافية</span>
                                @endif

                                @if(isset($task->notes_count) && $task->notes_count > 0)
                                <span class="task-notes-indicator ms-1" title="{{ $task->notes_count }} ملاحظات">
                                    <i class="fas fa-sticky-note"></i>
                                    <span class="notes-count">{{ $task->notes_count }}</span>
                                </span>
                                @endif
                            </h6>
                            <p class="text-xs text-secondary mb-0">
                                {{ Str::limit($task->description, 50) }}
                                @if(isset($task->is_template) && $task->is_template)
                                <small class="text-info">(من: {{ $task->template_name ?? 'قالب غير محدد' }})</small>
                                @endif

                                <!-- Transfer Information -->
                                @if($isTransferredFrom && isset($task->transferred_to_user))
                                <small class="text-danger d-block">تم النقل إلى: {{ $task->transferred_to_user->name }}</small>
                                @endif

                                @if($isTransferredTo && isset($task->original_user))
                                <small class="text-success d-block">منقولة من: {{ $task->original_user->name }}</small>
                                @endif
                            </p>
                        </div>
                    </div>
                </td>
                <td>
                    @if($task->project)
                    <strong>{{ $task->project->code ?? '' }}</strong> {{ $task->project->name }}
                    @if($isProjectCancelled)
                    <span class="badge bg-danger ms-1">(المشروع ملغي)</span>
                    @endif
                    @else
                    غير محدد
                    @endif
                </td>
                <td>{{ $task->service->name ?? 'غير محدد' }}</td>
                <td>
                    @if(isset($task->is_template) && $task->is_template)
                    @if(isset($task->createdBy) && $task->createdBy)
                    <span class="badge bg-info" style="font-size: 11px;"
                        data-creator-id="{{ $task->created_by }}"
                        data-bs-toggle="tooltip" data-bs-placement="top"
                        title="تم تعيينها بواسطة: {{ $task->createdBy->name }}">
                        <i class="fas fa-user-plus me-1"></i>{{ $task->createdBy->name }}
                    </span>
                    @else
                    <span class="badge bg-secondary" style="font-size: 11px;"
                        data-bs-toggle="tooltip" data-bs-placement="top"
                        title="مهمة من قالب - لا يوجد منشئ محدد">
                        <i class="fas fa-layer-group me-1"></i>قالب
                    </span>
                    @endif
                    @else
                    <span class="badge creator-badge" style="font-size: 11px;"
                        data-creator-id="{{ $task->created_by }}"
                        data-bs-toggle="tooltip" data-bs-placement="top"
                        title="الشخص الذي أنشأ هذه المهمة">
                        <i class="fas fa-user me-1"></i>{{ $task->createdBy->name ?? 'غير محدد' }}
                    </span>
                    @endif
                </td>
                <td>
                    <div class="avatar-group">
                        @if(isset($task->is_template) && $task->is_template)
                        {{-- مهام القوالب لها مستخدم واحد فقط --}}
                        @if($task->users && $task->users->count() > 0)
                        @php $user = $task->users->first(); @endphp
                        <span class="avatar avatar-sm rounded-circle" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $user->name }} (منفذ قالب)">
                            <img src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}">
                        </span>
                        @else
                        <span class="text-muted small">غير مخصص</span>
                        @endif
                        @else
                        {{-- المهام العادية --}}
                        @foreach($task->users as $user)
                        @if($user)
                        <span class="avatar avatar-sm rounded-circle" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $user->name }} ({{ $user->pivot?->role ?? 'غير محدد' }})">
                            <img src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}">
                        </span>
                        @endif
                        @endforeach
                        @if($task->users && $task->users->count() > 3)
                        <span class="avatar avatar-sm rounded-circle bg-secondary">
                            <span class="text-white">+{{ $task->users->count() - 3 }}</span>
                        </span>
                        @endif
                        @endif
                    </div>
                </td>
                <td>
                    <span class="badge bg-warning text-dark">
                        <i class="fas fa-star"></i>
                        {{ $task->points ?? 0 }}
                    </span>
                </td>
                <td>
                    @if($task->is_flexible_time ?? false)
                    <span class="badge bg-info">مرن</span>
                    @else
                    {{ $task->estimated_hours }}:{{ str_pad($task->estimated_minutes, 2, '0', STR_PAD_LEFT) }}
                    @endif
                </td>
                <td>{{ isset($task->is_template) && $task->is_template ? $task->actual_hours : (isset($task->pivot) && $task->pivot ? $task->pivot->actual_hours : 0) }}:{{ str_pad(isset($task->is_template) && $task->is_template ? $task->actual_minutes : (isset($task->pivot) && $task->pivot ? $task->pivot->actual_minutes : 0), 2, '0', STR_PAD_LEFT) }}</td>
                <td>
                    @php
                    $displayStatus = isset($task->user_status) && $task->user_status !== 'not_assigned'
                    ? $task->user_status
                    : (isset($task->pivot->status) ? $task->pivot->status : $task->status);
                    @endphp
                    <span class="badge badge-sm
                        @if($displayStatus == 'new') bg-info
                        @elseif($displayStatus == 'in_progress') bg-primary
                        @elseif($displayStatus == 'paused') bg-warning
                        @elseif($displayStatus == 'completed') bg-success
                        @elseif($displayStatus == 'cancelled') bg-danger
                        @endif">
                        @if($displayStatus == 'new') جديدة
                        @elseif($displayStatus == 'in_progress') قيد التنفيذ
                        @elseif($displayStatus == 'paused') متوقفة
                        @elseif($displayStatus == 'completed') مكتملة
                        @elseif($displayStatus == 'cancelled') ملغاة
                        @endif
                    </span>
                </td>
                <td>
                    @if(isset($task->is_template) && $task->is_template)
                    {{-- Template Tasks: استخدام due_date أو deadline --}}
                    {{ (isset($task->due_date) && $task->due_date ? $task->due_date->format('Y-m-d H:i') : (isset($task->deadline) && $task->deadline ? $task->deadline->format('Y-m-d H:i') : 'غير محدد')) }}
                    @else
                    {{-- Regular Tasks --}}
                    {{ isset($task->due_date) && $task->due_date ? $task->due_date->format('Y-m-d H:i') : 'غير محدد' }}
                    @endif
                </td>
                <td>
                    @php
                    // ✅ التحقق من كون المهمة منقولة منه فقط (للتحكم في زر العين)
                    // المهمة الأصلية المنقولة (is_transferred = true) → مقفول
                    // المهمة المنقولة إليه (is_additional_task) → مفتوح عادي
                    $viewBtnIsTransferredFrom = (isset($task->is_transferred) && $task->is_transferred) ||
                    (isset($task->pivot->is_transferred) && $task->pivot->is_transferred);

                    $isViewDisabled = $viewBtnIsTransferredFrom; // مقفول للمنقول منه فقط
                    @endphp

                    <button class="btn btn-sm {{ $isViewDisabled ? 'btn-secondary' : 'btn-info' }} view-task"
                        data-id="{{ $task->id }}"
                        data-task-user-id="{{ isset($task->is_template) && $task->is_template ? $task->id : (isset($task->pivot->id) ? $task->pivot->id : $task->id) }}"
                        data-task-name="{{ substr($task->name ?? 'بدون اسم', 0, 20) }}"
                        data-is-template="{{ isset($task->is_template) && $task->is_template ? 'true' : 'false' }}"
                        {{ $isViewDisabled ? 'disabled' : '' }}
                        title="{{ $isViewDisabled ? '🔒 المهمة تم نقلها - العرض معطل' : 'عرض التفاصيل' }}">
                        <i class="fas fa-{{ $isViewDisabled ? 'eye-slash' : 'eye' }}"></i>
                    </button>

                    @if(!(isset($task->is_template) && $task->is_template))
                    {{-- زر التعديل للمهام العادية فقط --}}
                    <button class="btn btn-sm btn-primary edit-task"
                        data-id="{{ $task->id }}"
                        data-task-user-id="{{ isset($task->pivot->id) ? $task->pivot->id : $task->id }}"
                        data-is-template="false">
                        <i class="fas fa-edit"></i>
                    </button>
                    @else
                    {{-- زر معطل لمهام القوالب --}}
                    <button class="btn btn-sm btn-secondary"
                        disabled
                        title="لا يمكن تعديل مهام القوالب من هنا">
                        <i class="fas fa-lock"></i>
                    </button>
                    @endif

                    <!-- زر نقل/تعديل المهمة -->
                    @php
                    // التحقق من كون المهمة منقولة (للتمييز بين نقل وتعديل المستلم)
                    $btnTaskSource = $task->task_source ?? ($task->pivot->task_source ?? null);
                    $btnIsAdditional = (isset($task->is_additional_task) && $task->is_additional_task) ||
                    (isset($task->pivot->is_additional_task) && $task->pivot->is_additional_task);

                    $isTransferredTaskForBtn = $btnIsAdditional && $btnTaskSource === 'transferred';

                    $transferBtnMode = $isTransferredTaskForBtn ? 'reassign' : 'transfer';
                    $transferBtnTitle = $isTransferredTaskForBtn ? 'تعديل المستلم' : 'نقل المهمة';
                    $transferBtnIcon = $isTransferredTaskForBtn ? 'fas fa-user-edit' : 'fas fa-exchange-alt';
                    $transferBtnClass = $isTransferredTaskForBtn ? 'btn-info' : 'btn-warning';
                    @endphp

                    @if(!in_array($displayStatus, ['completed', 'cancelled']) && !(isset($task->is_transferred) && $task->is_transferred) && !$isProjectCancelled && (auth()->user()->hasRole('hr') || auth()->user()->hasRole('admin') || $task->created_by == Auth::id()))
                    <button class="btn btn-sm {{ $transferBtnClass }} transfer-task"
                        data-task-type="{{ isset($task->is_template) && $task->is_template ? 'template' : 'regular' }}"
                        data-task-id="{{ $task->id }}"
                        data-task-user-id="{{ isset($task->pivot->id) ? $task->pivot->id : (isset($task->task_user_id) ? $task->task_user_id : $task->id) }}"
                        data-task-name="{{ $task->name }}"
                        data-current-user="{{ isset($task->assigned_user) ? $task->assigned_user->name : (isset($task->is_template) && $task->is_template ? ($task->users->first()->name ?? 'غير محدد') : ($task->users->first()->name ?? 'غير محدد')) }}"
                        data-mode="{{ $transferBtnMode }}"
                        title="{{ $transferBtnTitle }}">
                        <i class="{{ $transferBtnIcon }}"></i>
                    </button>
                    @elseif($isProjectCancelled)
                    <button class="btn btn-sm btn-secondary"
                        disabled
                        title="لا يمكن نقل المهام التابعة لمشروع ملغي">
                        <i class="fas fa-exchange-alt"></i>
                    </button>
                    @endif

                    <!-- زر إلغاء النقل (للمهام المنقولة) -->
                    @php
                    // التحقق من كون المهمة منقولة (سواء عادية أو قالب)
                    $checkTaskSource = $task->task_source ?? ($task->pivot->task_source ?? null);
                    $checkIsAdditional = (isset($task->is_additional_task) && $task->is_additional_task) ||
                    (isset($task->pivot->is_additional_task) && $task->pivot->is_additional_task);

                    $isTransferredTaskBtn = $checkIsAdditional && $checkTaskSource === 'transferred';
                    @endphp
                    @if($isTransferredTaskBtn && !in_array($displayStatus, ['completed', 'cancelled']) &&
                    (auth()->user()->hasRole('hr') || auth()->user()->hasRole('admin') ||
                    $task->users->first()->id == Auth::id()))
                    <button class="btn btn-sm btn-danger cancel-transfer-task"
                        data-task-type="{{ isset($task->is_template) && $task->is_template ? 'template' : 'regular' }}"
                        data-task-id="{{ isset($task->pivot->id) ? $task->pivot->id : $task->id }}"
                        data-task-name="{{ $task->name }}"
                        title="إلغاء النقل">
                        <i class="fas fa-undo"></i>
                    </button>
                    @endif

                    {{-- تم إزالة زر الحذف --}}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="11" class="text-center">لا توجد مهام</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>