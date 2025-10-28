@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="{{ asset('css/task-deliveries.css') }}">
@endpush

@section('content')
<div class="task-deliveries-container">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card mb-4">
                    <div class="task-deliveries-header">
                        <div class="d-flex justify-content-between align-items-center flex-wrap">
                            <div>
                                <h1 class="header-title">
                                    <i class="fas fa-check-circle text-success"></i>
                                    تسليمات التاسكات
                                </h1>
                                <p class="header-subtitle">مراجعة وموافقة المهام المكتملة</p>
                            </div>
                            <div class="header-actions">
                                <a href="{{ route('task-deliveries.index') }}" class="header-btn header-btn-primary">
                                    <i class="fas fa-sync-alt"></i> تحديث
                                </a>
                                <a href="{{ route('tasks.index') }}" class="header-btn header-btn-secondary">
                                    <i class="fas fa-arrow-left"></i> العودة للمهام
                                </a>
                            </div>
                        </div>
                    </div>

                <div class="card-body">
                    <!-- عرض رسائل النجاح والخطأ -->
                    @if(session('success'))
                        <div class="alert-enhanced alert-success-enhanced alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert-enhanced alert-danger-enhanced alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <!-- تنبيه للـ HR -->
                    @if(isset($pendingTasks['is_hr_or_admin']) && $pendingTasks['is_hr_or_admin'])
                        <div class="alert-enhanced alert-info-enhanced alert-dismissible fade show" role="alert">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>أنت ترى جميع المهام في النظام</strong> - كونك HR أو Admin، يمكنك رؤية واعتماد جميع المهام من جميع المشاريع ومديري المشاريع.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <!-- إحصائيات سريعة -->
                    <div class="approval-stats">
                        <div class="row">
                            <div class="col-lg-3 col-md-6 mb-3">
                                <div class="stat-card warning">
                                    <div class="stat-number">{{ $stats['pending_approval'] }}</div>
                                    <div class="stat-label">منتظرة للموافقة</div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-3">
                                <div class="stat-card success">
                                    <div class="stat-number">{{ $stats['approved_today'] }}</div>
                                    <div class="stat-label">تم اعتمادها اليوم</div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-3">
                                <div class="stat-card info">
                                    <div class="stat-number">{{ $stats['approved_this_week'] }}</div>
                                    <div class="stat-label">تم اعتمادها هذا الأسبوع</div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-3">
                                <div class="stat-card primary">
                                    <div class="stat-number">{{ $stats['approved_regular_tasks'] + $stats['approved_template_tasks'] }}</div>
                                    <div class="stat-label">إجمالي المهام المعتمدة</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- التبويبات -->
                    <ul class="nav nav-tabs-enhanced mb-4" id="approvalTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="all-tasks-tab" data-bs-toggle="tab"
                                    data-bs-target="#all-tasks" type="button" role="tab">
                                <i class="fas fa-list text-primary"></i>
                                جميع المهام
                                <span class="tab-badge">{{ $allTasksData['total_tasks'] }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="projects-tab" data-bs-toggle="tab"
                                    data-bs-target="#projects" type="button" role="tab">
                                <i class="fas fa-building text-info"></i>
                                المشاريع
                                <span class="tab-badge">{{ $projectsData['projects']->count() }}</span>
                            </button>
                        </li>
                    </ul>

                    <!-- محتوى التبويبات -->
                    <div class="tab-content" id="approvalTabsContent">
                        <!-- جميع المهام -->
                        <div class="tab-pane fade show active" id="all-tasks" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="fw-bold text-dark">
                                    <i class="fas fa-list text-primary me-2"></i>
                                    جميع المهام
                                </h4>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="badge bg-info px-3 py-2">
                                        <i class="fas fa-tasks me-1"></i>
                                        {{ $allTasksData['total_tasks'] }} مهمة إجمالي
                                    </span>
                                    <span class="badge bg-warning px-3 py-2">
                                        <i class="fas fa-hourglass-half me-1"></i>
                                        {{ $allTasksData['pending_approval'] }} منتظرة للموافقة
                                    </span>
                                </div>
                            </div>

                    <!-- جدول المهام الموحد -->
                    <div class="all-tasks-section">
                        @if($allTasksData['all_tasks']->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col" width="8%" class="text-center">
                                                <i class="fas fa-tag text-info me-2"></i>
                                                النوع
                                            </th>
                                            <th scope="col" width="20%">
                                                <i class="fas fa-user text-primary me-2"></i>
                                                المهمة والموظف
                                            </th>
                                            <th scope="col" width="12%">
                                                <i class="fas fa-building text-secondary me-2"></i>
                                                المشروع
                                            </th>
                                            <th scope="col" width="8%" class="text-center">
                                                <i class="fas fa-star text-warning me-2"></i>
                                                النقاط
                                            </th>
                                            <th scope="col" width="8%" class="text-center">
                                                <i class="fas fa-traffic-light text-secondary me-2"></i>
                                                الحالة
                                            </th>
                                            <th scope="col" width="10%" class="text-center">
                                                <i class="fas fa-user-tie text-primary me-2"></i>
                                                الاعتماد الإداري
                                            </th>
                                            <th scope="col" width="10%" class="text-center">
                                                <i class="fas fa-cogs text-success me-2"></i>
                                                الاعتماد الفني
                                            </th>
                                            <th scope="col" width="10%" class="text-center">
                                                <i class="fas fa-calendar-alt text-danger me-2"></i>
                                                الموعد النهائي
                                            </th>
                                            <th scope="col" width="8%" class="text-center">
                                                <i class="fas fa-clock text-info me-2"></i>
                                                الوقت الفعلي
                                            </th>
                                            <th scope="col" width="10%" class="text-center">
                                                <i class="fas fa-check-circle text-success me-2"></i>
                                                تاريخ الإكمال
                                            </th>
                                            <th scope="col" width="12%" class="text-center">
                                                <i class="fas fa-cog text-secondary me-2"></i>
                                                الإجراءات
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($allTasksData['all_tasks'] as $task)
                                            <tr data-task-type="{{ $task['type'] }}" data-task-id="{{ $task['id'] }}" onclick="openTaskSidebar(this.getAttribute('data-task-type'), this.getAttribute('data-task-id'))" style="cursor: pointer;">
                                                <!-- نوع المهمة -->
                                                <td class="text-center">
                                                    @if($task['type'] == 'regular')
                                                        <span class="badge bg-primary rounded-pill">
                                                            <i class="fas fa-tasks me-1"></i> عادية
                                                        </span>
                                                    @else
                                                        <span class="badge bg-success rounded-pill">
                                                            <i class="fas fa-layer-group me-1"></i> قالب
                                                        </span>
                                                    @endif
                                                </td>

                                                <!-- المهمة والموظف -->
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <img src="{{ $task['user']->profile_photo_url }}"
                                                             class="rounded-circle me-3" width="40" height="40"
                                                             alt="{{ $task['user']->name }}">
                                                        <div>
                                                            <h6 class="mb-1 fw-semibold">{{ $task['task_name'] }}</h6>
                                                            <p class="mb-0 text-muted small">{{ $task['user']->name }}</p>
                                                            <small class="text-success">
                                                                <i class="fas fa-user-plus"></i> {{ $task['created_by'] }}
                                                            </small>
                                                        </div>
                                                    </div>
                                                </td>

                                                <!-- المشروع -->
                                                <td>
                                                    @if($task['project'])
                                                        <div class="d-flex align-items-center">
                                                            <div class="rounded-circle d-flex align-items-center justify-content-center me-2"
                                                                 style="width: 30px; height: 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                                                <i class="fas fa-building text-white" style="font-size: 12px;"></i>
                                                            </div>
                                                            <div>
                                                                <h6 class="mb-0 fw-semibold">{{ $task['project']->name }}</h6>
                                                                @if(isset($task['project']->manager) && $task['project']->manager)
                                                                    <small class="text-muted">{{ $task['project']->manager }}</small>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @else
                                                        <div class="text-center">
                                                            <small class="text-muted">
                                                                <i class="fas fa-minus-circle"></i><br>
                                                                غير مربوط بمشروع
                                                            </small>
                                                        </div>
                                                    @endif
                                                </td>

                                                <!-- النقاط -->
                                                <td class="text-center">
                                                    <div class="fw-bold text-warning">{{ $task['points'] }}</div>
                                                    <small class="text-muted">نقطة</small>
                                                </td>

                                                <!-- حالة المهمة -->
                                                <td class="text-center">
                                                    @php
                                                        $statusColors = [
                                                            'new' => 'secondary',
                                                            'in_progress' => 'primary',
                                                            'paused' => 'warning',
                                                            'completed' => 'success'
                                                        ];
                                                        $statusTexts = [
                                                            'new' => 'جديدة',
                                                            'in_progress' => 'قيد التنفيذ',
                                                            'paused' => 'متوقفة',
                                                            'completed' => 'مكتملة'
                                                        ];
                                                    @endphp
                                                    <span class="badge bg-{{ $statusColors[$task['status']] ?? 'secondary' }}">
                                                        {{ $statusTexts[$task['status']] ?? $task['status'] }}
                                                    </span>
                                                </td>

                                                <!-- الاعتماد الإداري -->
                                                <td class="text-center" onclick="event.stopPropagation()">
                                                    @if($task['project'])
                                                        @php
                                                            $rawTask = $task['raw_data'];
                                                            $requiredApprovals = $rawTask->getRequiredApprovals();
                                                            $canApproveAdmin = $rawTask->canUserApprove(Auth::id(), 'administrative');
                                                        @endphp

                                                        @if($requiredApprovals['needs_administrative'])
                                                            @if($rawTask->hasAdministrativeApproval())
                                                                <span class="badge bg-success">
                                                                    <i class="fas fa-check-circle"></i> معتمد
                                                                </span>
                                                                @if($rawTask->administrative_approval_at)
                                                                    <br><small class="text-muted">{{ $rawTask->administrative_approval_at->format('Y-m-d') }}</small>
                                                                @endif
                                                                @if($canApproveAdmin && $task['status'] === 'completed')
                                                                    <br>
                                                                    <button class="btn btn-outline-warning btn-sm mt-1"
                                                                            onclick="revokeAdministrativeApproval(&apos;{{ $task['type'] }}&apos;, {{ $rawTask->id }})">
                                                                        <i class="fas fa-times"></i> إلغاء
                                                                    </button>
                                                                @endif
                                                            @else
                                                                @if($task['status'] === 'completed')
                                                                    <span class="badge bg-warning text-dark">
                                                                        <i class="fas fa-hourglass-half"></i> في الانتظار
                                                                    </span>
                                                                    @if($canApproveAdmin)
                                                                        <br>
                                                                        <button class="btn btn-outline-primary btn-sm mt-1"
                                                                                onclick="grantAdministrativeApproval(&apos;{{ $task['type'] }}&apos;, {{ $rawTask->id }})">
                                                                            <i class="fas fa-check"></i> اعتماد
                                                                        </button>
                                                                    @endif
                                                                @else
                                                                    <span class="text-muted">
                                                                        <i class="fas fa-lock"></i><br>
                                                                        <small>يتطلب إكمال المهمة أولاً</small>
                                                                    </span>
                                                                @endif
                                                            @endif
                                                        @else
                                                            <span class="text-muted">
                                                                <i class="fas fa-minus-circle"></i><br>
                                                                <small>غير مطلوب</small>
                                                            </span>
                                                        @endif
                                                    @else
                                                        <span class="text-muted">
                                                            <i class="fas fa-minus-circle"></i><br>
                                                            <small>غير مطلوب</small>
                                                        </span>
                                                    @endif
                                                </td>

                                                <!-- الاعتماد الفني -->
                                                <td class="text-center" onclick="event.stopPropagation()">
                                                    @if($task['project'])
                                                        @php
                                                            $rawTask = $task['raw_data'];
                                                            $requiredApprovals = $rawTask->getRequiredApprovals();
                                                            $canApproveTech = $rawTask->canUserApprove(Auth::id(), 'technical');
                                                        @endphp

                                                        @if($requiredApprovals['needs_technical'])
                                                            @if($rawTask->hasTechnicalApproval())
                                                                <span class="badge bg-success">
                                                                    <i class="fas fa-check-circle"></i> معتمد
                                                                </span>
                                                                @if($rawTask->technical_approval_at)
                                                                    <br><small class="text-muted">{{ $rawTask->technical_approval_at->format('Y-m-d') }}</small>
                                                                @endif
                                                                @if($canApproveTech && $task['status'] === 'completed')
                                                                    <br>
                                                                    <button class="btn btn-outline-danger btn-sm mt-1"
                                                                            onclick="revokeTechnicalApproval(&apos;{{ $task['type'] }}&apos;, {{ $rawTask->id }})">
                                                                        <i class="fas fa-times"></i> إلغاء
                                                                    </button>
                                                                @endif
                                                            @else
                                                                @if($task['status'] === 'completed')
                                                                    <span class="badge bg-warning text-dark">
                                                                        <i class="fas fa-hourglass-half"></i> في الانتظار
                                                                    </span>
                                                                    @if($canApproveTech)
                                                                        <br>
                                                                        <button class="btn btn-outline-success btn-sm mt-1"
                                                                                onclick="grantTechnicalApproval(&apos;{{ $task['type'] }}&apos;, {{ $rawTask->id }})">
                                                                            <i class="fas fa-check"></i> اعتماد
                                                                        </button>
                                                                    @endif
                                                                @else
                                                                    <span class="text-muted">
                                                                        <i class="fas fa-lock"></i><br>
                                                                        <small>يتطلب إكمال المهمة أولاً</small>
                                                                    </span>
                                                                @endif
                                                            @endif
                                                        @else
                                                            <span class="text-muted">
                                                                <i class="fas fa-minus-circle"></i><br>
                                                                <small>غير مطلوب</small>
                                                            </span>
                                                        @endif
                                                    @else
                                                        <span class="text-muted">
                                                            <i class="fas fa-minus-circle"></i><br>
                                                            <small>غير مطلوب</small>
                                                        </span>
                                                    @endif
                                                </td>

                                                <!-- الموعد النهائي -->
                                                <td class="text-center">
                                                    @if($task['deadline'])
                                                        @php
                                                            $deadline = \Carbon\Carbon::parse($task['deadline']);
                                                            $now = now();
                                                            $isOverdue = $deadline->isPast() && $task['status'] !== 'completed';
                                                            $isDueSoon = $deadline->isFuture() && $deadline->diffInHours($now) <= 24 && $task['status'] !== 'completed';
                                                        @endphp
                                                        <div class="fw-semibold {{ $isOverdue ? 'text-danger' : ($isDueSoon ? 'text-warning' : 'text-success') }}">
                                                            {{ $deadline->format('Y-m-d') }}
                                </div>
                                                        <small class="text-muted">{{ $deadline->format('H:i') }}</small>
                                                        @if($isOverdue)
                                                            <br><small class="text-danger"><i class="fas fa-exclamation-triangle"></i> متأخرة</small>
                                                        @elseif($isDueSoon)
                                                            <br><small class="text-warning"><i class="fas fa-clock"></i> قريبة الانتهاء</small>
                                                        @endif
                            @else
                                                        <span class="text-muted">غير محدد</span>
                            @endif
                                                </td>

                                                <!-- الوقت الفعلي -->
                                                <td class="text-center">
                                                    <div class="fw-bold text-info">
                                                        {{ $task['actual_time']['hours'] }}:{{ str_pad($task['actual_time']['minutes'], 2, '0', STR_PAD_LEFT) }}
                                                        </div>
                                                    <small class="text-muted">ساعة:دقيقة</small>
                                                </td>

                                                <!-- تاريخ الإكمال -->
                                                <td class="text-center">
                                                    @if($task['completed_date'])
                                                        <div class="fw-semibold text-success">
                                                            {{ \Carbon\Carbon::parse($task['completed_date'])->format('Y-m-d') }}
                                                    </div>
                                                        <small class="text-muted">
                                                            {{ \Carbon\Carbon::parse($task['completed_date'])->format('H:i') }}
                                                    </small>
                                                    @else
                                                        <span class="text-muted">غير مكتملة</span>
                                            @endif
                                                </td>

                                                <!-- الإجراءات -->
                                                <td class="text-center">
                                                    @if(!$task['project'])
                                                        {{-- تاسكات بدون مشروع: تحتاج الاعتماد العادي فقط --}}
                                                        @if($task['status'] == 'completed' && !$task['is_approved'])
                                                            <button class="btn btn-success btn-sm" onclick="event.stopPropagation(); toggleApprovalForm('{{ $task['type'] }}-{{ $task['id'] }}')">
                                                                <i class="fas fa-check"></i> اعتماد
                                                            </button>
                                                        @elseif($task['is_approved'])
                                                            <span class="badge bg-success">
                                                                <i class="fas fa-check-circle"></i> معتمدة
                                                            </span>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    @else
                                                        {{-- تاسكات المشاريع: تحتاج اعتمادات إدارية/فنية فقط --}}
                                                        <span class="text-muted">
                                                            <i class="fas fa-project-diagram"></i><br>
                                                            <small>تاسك مشروع</small>
                                                        </span>
                                                    @endif
                                                </td>
                                            </tr>

                                            <!-- صف نموذج الموافقة (للتاسكات الغير مرتبطة بمشاريع فقط) -->
                                            @if(!$task['project'] && $task['status'] == 'completed' && !$task['is_approved'])
                                                <tr class="approval-form-row" id="approval-form-{{ $task['type'] }}-{{ $task['id'] }}" style="display: none;">
                                                    <td colspan="11">
                                                        <div class="p-4 bg-light border rounded">
                                                            <form action="{{ $task['type'] == 'regular' ? route('task-deliveries.approve-regular', $task['raw_data']) : route('task-deliveries.approve-template', $task['raw_data']) }}" method="POST">
                                                    @csrf
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold">
                                                                    <i class="fas fa-star text-warning me-1"></i>
                                                                    النقاط المستحقة
                                                                </label>
                                                                <input type="number" class="form-control" name="awarded_points"
                                                                                   min="0" max="1000" value="{{ $task['points'] }}"
                                                                       placeholder="أدخل النقاط المستحقة">
                                                                <small class="text-muted">اتركه كما هو لاستخدام النقاط الأصلية</small>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold">
                                                                    <i class="fas fa-comment text-info me-1"></i>
                                                                    ملاحظة الاعتماد
                                                                </label>
                                                                <textarea class="form-control" name="approval_note"
                                                                          rows="2" placeholder="أضف ملاحظة (اختياري)"></textarea>
                                                            </div>
                                                        </div>
                                                    </div>
                                                                <div class="d-flex gap-2">
                                                                    <button type="button" class="btn btn-secondary"
                                                                            onclick="toggleApprovalForm('{{ $task['type'] }}-{{ $task['id'] }}')">
                                                            إلغاء
                                                        </button>
                                                                    <button type="submit" class="btn btn-success">
                                                            <i class="fas fa-check"></i> تأكيد الاعتماد
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                                    </td>
                                                </tr>
                                            @endif
                                @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @else
                                <div class="empty-state">
                                <i class="fas fa-tasks"></i>
                                <h5>لا توجد مهام</h5>
                                <p class="text-muted">لا توجد مهام مُعيَّنة أو منشأة بواسطتك.</p>
                                </div>
                            @endif
                        </div>
                                            </div>

                        <!-- المشاريع -->
                        <div class="tab-pane fade" id="projects" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="fw-bold text-dark">
                                    <i class="fas fa-building text-info me-2"></i>
                                    المشاريع
                                </h4>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="badge bg-info px-3 py-2">
                                        <i class="fas fa-building me-1"></i>
                                        {{ $projectsData['projects']->count() }} مشروع
                                            </span>
                                        </div>
                                    </div>

                            <!-- جدول المشاريع -->
                            <div class="projects-section">
                                @if($projectsData['projects']->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th scope="col" width="25%">
                                                        <i class="fas fa-building text-primary me-2"></i>
                                                        المشروع
                                                    </th>
                                                    <th scope="col" width="15%" class="text-center">
                                                        <i class="fas fa-tasks text-info me-2"></i>
                                                        إجمالي المهام
                                                    </th>
                                                    <th scope="col" width="15%" class="text-center">
                                                        <i class="fas fa-check-circle text-success me-2"></i>
                                                        مكتملة
                                                    </th>
                                                    <th scope="col" width="15%" class="text-center">
                                                        <i class="fas fa-hourglass-half text-warning me-2"></i>
                                                        منتظرة موافقة
                                                    </th>
                                                    <th scope="col" width="15%" class="text-center">
                                                        <i class="fas fa-thumbs-up text-info me-2"></i>
                                                        معتمدة
                                                    </th>
                                                    <th scope="col" width="15%" class="text-center">
                                                        <i class="fas fa-cog text-secondary me-2"></i>
                                                        الإجراءات
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($projectsData['projects'] as $project)
                                                    <tr onclick="loadProjectTasks({{ $project['id'] }}, '{{ $project['name'] }}')" style="cursor: pointer;">
                                                        <!-- معلومات المشروع -->
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <div class="rounded-circle d-flex align-items-center justify-content-center me-3"
                                                                     style="width: 45px; height: 45px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                                                    <i class="fas fa-building text-white"></i>
                                </div>
                                                                <div>
                                                                    <h6 class="mb-1 fw-semibold">{{ $project['name'] }}</h6>
                                                                    @if($project['description'])
                                                                        <p class="mb-0 text-muted small">{{ Str::limit($project['description'], 60) }}</p>
                                                                    @endif
                                                                    @if($project['manager'])
                                                                        <small class="text-success">
                                                                            <i class="fas fa-user-tie me-1"></i>
                                                                            {{ $project['manager'] }}
                                                                        </small>
                                                                    @endif
                                                        </div>
                                                    </div>
                                                        </td>

                                                        <!-- إجمالي المهام -->
                                                        <td class="text-center">
                                                            <div class="fw-bold text-primary fs-5">{{ $project['stats']['total_tasks'] }}</div>
                                                            <small class="text-muted">مهمة</small>
                                                        </td>

                                                        <!-- المهام المكتملة -->
                                                        <td class="text-center">
                                                            <div class="fw-bold text-success fs-5">{{ $project['stats']['completed_tasks'] }}</div>
                                                            <small class="text-muted">مكتملة</small>
                                                            @if($project['stats']['total_tasks'] > 0)
                                                                <div class="mt-1">
                                                                    <div class="progress" style="height: 4px;">
                                                                        <div class="progress-bar bg-success" role="progressbar"
                                                                             style="width: {{ $project['stats']['completion_percentage'] }}%"></div>
                                                </div>
                                                                    <small class="text-muted">{{ $project['stats']['completion_percentage'] }}%</small>
                                                        </div>
                                                            @endif
                                                        </td>

                                                        <!-- المهام المنتظرة للموافقة -->
                                                        <td class="text-center">
                                                            <div class="fw-bold text-warning fs-5">{{ $project['stats']['pending_approval'] }}</div>
                                                            <small class="text-muted">منتظرة</small>
                                                        </td>

                                                        <!-- المهام المعتمدة -->
                                                        <td class="text-center">
                                                            <div class="fw-bold text-info fs-5">{{ $project['stats']['approved_tasks'] }}</div>
                                                            <small class="text-muted">معتمدة</small>
                                                            @if($project['stats']['completed_tasks'] > 0)
                                                                <div class="mt-1">
                                                                    <div class="progress" style="height: 4px;">
                                                                        <div class="progress-bar bg-info" role="progressbar"
                                                                             style="width: {{ $project['stats']['approval_percentage'] }}%"></div>
                                                            </div>
                                                                    <small class="text-muted">{{ $project['stats']['approval_percentage'] }}%</small>
                                                        </div>
                                                            @endif
                                                        </td>

                                                        <!-- الإجراءات -->
                                                        <td class="text-center">
                                                            <button class="btn btn-primary btn-sm" onclick="event.stopPropagation(); loadProjectTasks({{ $project['id'] }}, '{{ $project['name'] }}')">
                                                                <i class="fas fa-eye me-1"></i> عرض المهام
                                                        </button>
                                                        </td>
                                                    </tr>
                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                            @else
                                <div class="empty-state">
                                        <i class="fas fa-building"></i>
                                        <h5>لا توجد مشاريع</h5>
                                        <p class="text-muted">لا توجد مشاريع تحتوي على مهام مُنشأة بواسطتك.</p>
                                </div>
                            @endif
                        </div>

                            <!-- عرض مهام المشروع المختار -->
                            <div id="project-tasks-container" style="display: none;">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h4 class="fw-bold text-dark">
                                        <button class="btn btn-outline-secondary btn-sm me-2" onclick="backToProjects()">
                                            <i class="fas fa-arrow-right"></i>
                                            </button>
                                        <span id="project-tasks-title">مهام المشروع</span>
                                    </h4>
                                    <div class="d-flex align-items-center gap-3" id="project-tasks-stats">
                                        <!-- سيتم تحديث الإحصائيات هنا -->
                                </div>
                            </div>

                                <div id="project-tasks-table">
                                    <!-- سيتم تحميل جدول المهام هنا -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
</div>

<!-- Task Details Sidebar -->
<div id="taskSidebar" class="task-sidebar" style="
    position: fixed;
    top: 0;
    left: -480px;
    width: 460px;
    height: 100vh;
    background: #ffffff;
    box-shadow: 0 8px 40px rgba(0,0,0,0.12);
    z-index: 1050;
    transition: left 0.4s cubic-bezier(0.4, 0.0, 0.2, 1);
    overflow-y: auto;
    border-right: 1px solid #e1e5e9;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    scrollbar-width: none;
    -ms-overflow-style: none;
">
<style>
#taskSidebar::-webkit-scrollbar {
    display: none;
}
</style>
    <!-- Sidebar Header -->
    <div class="sidebar-header" style="
        background: #ffffff;
        padding: 24px 24px 16px 24px;
        border-bottom: 1px solid #e1e5e9;
        position: sticky;
        top: 0;
        z-index: 10;
    ">
        <div class="d-flex justify-content-between align-items-start">
            <div class="flex-grow-1">
                <h4 class="mb-2 fw-bold text-dark" id="taskSidebarTitle" style="font-size: 20px; line-height: 1.2;">تفاصيل المهمة</h4>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge rounded-pill px-3 py-1" id="taskSidebarBadge" style="background: #f0f1f3; color: #626f86; font-size: 12px; font-weight: 500;">جاري التحميل...</span>
                    <small class="text-muted" id="taskSidebarSubtitle">جاري التحميل...</small>
                </div>
            </div>
            <button onclick="closeTaskSidebar()" class="btn btn-light rounded-circle p-2" style="width: 36px; height: 36px; border: none; background: #f6f7f8;">
                <i class="fas fa-times text-muted" style="font-size: 14px;"></i>
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
<div id="sidebarOverlay" class="sidebar-overlay" style="
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0,0,0,0.5);
    z-index: 1040;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
" onclick="closeTaskSidebar()"></div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// إظهار/إخفاء نموذج الموافقة
function toggleApprovalForm(taskId) {
    const form = document.getElementById('approval-form-' + taskId);
    if (form) {
        if (form.style.display === 'none' || form.style.display === '') {
        // إخفاء جميع النماذج الأخرى أولاً
            document.querySelectorAll('.approval-form-row').forEach(f => f.style.display = 'none');
            form.style.display = 'table-row';
        } else {
            form.style.display = 'none';
        }
    }
}

// تأكيد الإرسال
document.querySelectorAll('form[action*="approve"]').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const points = this.querySelector('input[name="awarded_points"]').value;
        const note = this.querySelector('textarea[name="approval_note"]').value;

        Swal.fire({
            title: 'تأكيد الاعتماد',
            html: `
                <p>هل أنت متأكد من اعتماد هذه المهمة؟</p>
                <p><strong>النقاط المستحقة:</strong> ${points} نقطة</p>
                ${note ? `<p><strong>الملاحظة:</strong> ${note}</p>` : ''}
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'نعم، اعتماد',
            cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (result.isConfirmed) {
                this.submit();
            }
        });
    });
});

// التحكم في إظهار/إخفاء حقول الشهور
document.getElementById('filterType').addEventListener('change', function() {
    const filterType = this.value;
    const monthFromGroup = document.getElementById('monthFromGroup');
    const monthToGroup = document.getElementById('monthToGroup');

    if (filterType === 'custom') {
        monthFromGroup.style.display = 'block';
        monthToGroup.style.display = 'block';
    } else {
        monthFromGroup.style.display = 'none';
        monthToGroup.style.display = 'none';
    }
});

// تطبيق الفلتر
document.getElementById('applyFilter').addEventListener('click', function() {
    const filterType = document.getElementById('filterType').value;
    const monthFrom = document.getElementById('monthFrom').value;
    const yearFrom = document.getElementById('yearFrom').value;
    const monthTo = document.getElementById('monthTo').value;
    const yearTo = document.getElementById('yearTo').value;

    // إظهار مؤشر التحميل
    const container = document.getElementById('approvedTasksContainer');
    container.innerHTML = `
        <div class="d-flex justify-content-center align-items-center" style="height: 200px;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">جاري التحميل...</span>
            </div>
        </div>
    `;

    // إرسال طلب AJAX
    const params = new URLSearchParams({
        filter_type: filterType,
        month_from: monthFrom,
        year_from: yearFrom,
        month_to: monthTo,
        year_to: yearTo
    });

    fetch(`{{ route('task-deliveries.filter-approved') }}?${params}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            container.innerHTML = data.html;

            // تحديث الإحصائيات في التبويب
            const badge = document.querySelector('#approved-tasks-tab .tab-badge');
            if (badge) {
                badge.textContent = data.stats.total_approved;
            }
        } else {
            container.innerHTML = `
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle"></i>
                    حدث خطأ في تحميل البيانات: ${data.message || 'خطأ غير معروف'}
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        container.innerHTML = `
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-triangle"></i>
                حدث خطأ في الاتصال بالخادم
            </div>
        `;
    });
});

// عرض ملاحظة الاعتماد
function showApprovalNote(note) {
    Swal.fire({
        title: 'ملاحظة الاعتماد',
        text: note,
        icon: 'info',
        confirmButtonText: 'إغلاق'
    });
}

// تعديل نقاط المهمة
function editTaskPoints(taskType, taskUserId, currentPoints) {
    Swal.fire({
        title: 'تعديل النقاط',
        html: `
            <div class="mb-3">
                <label class="form-label fw-bold">النقاط الجديدة:</label>
                <input type="number" id="newPoints" class="form-control" value="${currentPoints}" min="0" max="1000">
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">سبب التعديل (اختياري):</label>
                <textarea id="updateReason" class="form-control" rows="3" placeholder="أضف سبب التعديل..."></textarea>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'تحديث',
        cancelButtonText: 'إلغاء',
        preConfirm: () => {
            const newPoints = document.getElementById('newPoints').value;
            const updateReason = document.getElementById('updateReason').value;

            if (!newPoints || newPoints < 0 || newPoints > 1000) {
                Swal.showValidationMessage('يرجى إدخال نقاط صحيحة (0-1000)');
                return false;
            }

            return { newPoints, updateReason };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const { newPoints, updateReason } = result.value;

            // إرسال طلب التحديث
            fetch('{{ route('task-deliveries.update-points') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    task_type: taskType,
                    task_user_id: taskUserId,
                    new_points: newPoints,
                    update_reason: updateReason
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'تم التحديث!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonText: 'موافق'
                    }).then(() => {
                        // إعادة تحميل الصفحة أو تحديث المحتوى
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'خطأ!',
                        text: data.message,
                        icon: 'error',
                        confirmButtonText: 'موافق'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'خطأ!',
                    text: 'حدث خطأ في الاتصال بالخادم',
                    icon: 'error',
                    confirmButtonText: 'موافق'
                });
            });
        }
    });
}

// Custom function to display task details with approval actions for task-deliveries page
function displayTaskDetailsWithApproval(task) {
    const content = document.getElementById('taskSidebarContent');
    const title = document.getElementById('taskSidebarTitle');
    const subtitle = document.getElementById('taskSidebarSubtitle');
    const badge = document.getElementById('taskSidebarBadge');

    // Update header
    title.textContent = task.name || task.title || 'مهمة بدون عنوان';
    subtitle.textContent = task.project ? task.project.name : 'مشروع غير معروف';

    // Update badge
    const badgeColors = {
        'template': {bg: '#e8f5e8', color: '#2d7d2d', icon: 'fa-layer-group'},
        'regular': {bg: '#e8f0ff', color: '#0066cc', icon: 'fa-tasks'}
    };
    const badgeStyle = badgeColors[task.type] || badgeColors.regular;
    badge.style.background = badgeStyle.bg;
    badge.style.color = badgeStyle.color;
    badge.innerHTML = `<i class="fas ${badgeStyle.icon} me-1"></i>${task.type === 'template' ? 'مهمة قالب' : 'مهمة عادية'}`;

    // Generate deadline HTML
    let deadlineHtml = '';
    if (task.deadline || task.due_date) {
        const deadline = task.deadline || task.due_date;
        const deadlineDate = new Date(deadline);
        const now = new Date();
        const isOverdue = deadlineDate < now && task.status !== 'completed';
        const isDueSoon = deadlineDate > now && (deadlineDate - now) <= 24*60*60*1000 && task.status !== 'completed';

        let badgeClass = 'primary';
        let iconClass = 'calendar-check';
        let statusText = 'في الموعد';

        if (task.status === 'completed') {
            badgeClass = 'success';
            iconClass = 'check-circle';
            statusText = 'مكتملة';
        } else if (isOverdue) {
            badgeClass = 'danger';
            iconClass = 'exclamation-triangle';
            statusText = 'متأخرة';
        } else if (isDueSoon) {
            badgeClass = 'warning';
            iconClass = 'hourglass-half';
            statusText = 'تنتهي قريباً';
        }

        deadlineHtml = `
            <div class="mb-4">
                <label class="form-label fw-semibold text-muted mb-2" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">الموعد النهائي</label>
                <div class="d-flex align-items-center p-3 rounded" style="background: rgba(${badgeClass === 'success' ? '25, 135, 84' : badgeClass === 'danger' ? '220, 53, 69' : badgeClass === 'warning' ? '255, 193, 7' : '13, 110, 253'}, 0.1); border: 1px solid rgba(${badgeClass === 'success' ? '25, 135, 84' : badgeClass === 'danger' ? '220, 53, 69' : badgeClass === 'warning' ? '255, 193, 7' : '13, 110, 253'}, 0.2);">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; background: rgba(${badgeClass === 'success' ? '25, 135, 84' : badgeClass === 'danger' ? '220, 53, 69' : badgeClass === 'warning' ? '255, 193, 7' : '13, 110, 253'}, 0.2);">
                        <i class="fas fa-${iconClass} text-${badgeClass}" style="font-size: 16px;"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-semibold text-dark mb-1" style="font-size: 14px;">
                            ${deadlineDate.toLocaleDateString('ar-EG', {weekday: 'short', month: 'short', day: 'numeric'})} - ${deadlineDate.toLocaleTimeString('ar-EG', {hour: '2-digit', minute: '2-digit'})}
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="status-dot me-2" style="width: 8px; height: 8px; border-radius: 50%; background: var(--bs-${badgeClass});"></div>
                            <small class="text-${badgeClass} fw-semibold" style="font-size: 12px;">${statusText}</small>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    // Define status colors and texts
    const statusColors = {
        'new': 'secondary',
        'in_progress': 'primary',
        'paused': 'warning',
        'completed': 'success',
        'cancelled': 'danger'
    };
    const statusTexts = {
        'new': 'جديدة',
        'in_progress': 'قيد التنفيذ',
        'paused': 'متوقفة مؤقتاً',
        'completed': 'مكتملة',
        'cancelled': 'ملغية'
    };

    // Generate approval actions HTML for pending tasks
    let approvalActionsHtml = '';
    if (task.approval_status === 'pending') {
        approvalActionsHtml = `
            <div class="mb-4">
                <label class="form-label fw-semibold text-muted mb-2" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">إجراءات الاعتماد</label>
                <div class="p-3 rounded" style="background: #fff8e1; border: 1px solid #ffd54f;">
                    <div class="d-flex align-items-center mb-3">
                        <i class="fas fa-hourglass-half text-warning me-2"></i>
                        <span class="fw-semibold text-warning">منتظرة للاعتماد</span>
                    </div>
                    <div class="approval-form-sidebar">
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="fas fa-star text-warning me-1"></i>
                                النقاط المستحقة
                            </label>
                            <input type="number" class="form-control" id="sidebar-awarded-points"
                                   min="0" max="1000" value="${task.points || 0}"
                                   placeholder="أدخل النقاط المستحقة">
                            <small class="text-muted">اتركه كما هو لاستخدام النقاط الأصلية</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="fas fa-comment text-info me-1"></i>
                                ملاحظة الاعتماد
                            </label>
                            <textarea class="form-control" id="sidebar-approval-note"
                                      rows="2" placeholder="أضف ملاحظة (اختياري)"></textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-success btn-sm px-3" onclick="approveSidebarTask('${task.type}', ${task.user_id})">
                                <i class="fas fa-check me-1"></i> اعتماد
                            </button>
                            <button class="btn btn-outline-secondary btn-sm px-3" onclick="closeTaskSidebar()">
                                إلغاء
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    } else if (task.approval_status === 'approved') {
        approvalActionsHtml = `
            <div class="mb-4">
                <label class="form-label fw-semibold text-muted mb-2" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">حالة الاعتماد</label>
                <div class="p-3 rounded" style="background: #e8f5e8; border: 1px solid #4caf50;">
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-check-circle text-success me-2"></i>
                        <span class="fw-semibold text-success">معتمدة</span>
                    </div>
                    ${task.approved_by ? `<small class="text-muted">اعتمدت بواسطة: ${task.approved_by}</small>` : ''}
                    ${task.approved_at ? `<br><small class="text-muted">في: ${task.approved_at}</small>` : ''}
                    ${task.approval_note ? `<div class="mt-2 p-2 rounded" style="background: #f5f5f5;"><small>${task.approval_note}</small></div>` : ''}
                </div>
            </div>
        `;
    }

    content.innerHTML = `
        <div style="padding: 24px; background: #ffffff;">

            <!-- Status Section -->
            <div class="mb-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <label class="form-label fw-semibold text-muted mb-0" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">الحالة</label>
                </div>
                <div class="status-dropdown-wrapper">
                    <span class="badge px-3 py-2 d-inline-flex align-items-center bg-${statusColors[task.status]}" style="font-size: 13px; font-weight: 500; border-radius: 6px;">
                        <div class="status-dot me-2" style="width: 8px; height: 8px; border-radius: 50%; background: currentColor; opacity: 0.8;"></div>
                        ${statusTexts[task.status] || task.status}
                    </span>
                </div>
            </div>

            ${deadlineHtml}

            ${approvalActionsHtml}

            <!-- Description Section -->
            ${task.description ? `
                <div class="mb-4">
                    <label class="form-label fw-semibold text-muted mb-2" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">الوصف</label>
                    <div class="p-3 rounded" style="background: #f8f9fa; border: 1px solid #e9ecef; color: #495057; line-height: 1.5;">
                        ${task.description}
                    </div>
                </div>
            ` : ''}

            <!-- Time Tracking Section -->
            <div class="mb-4">
                <label class="form-label fw-semibold text-muted mb-2" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">تتبع الوقت</label>
                <div class="row g-2">
                    ${task.estimated_hours !== undefined ? `
                        <div class="col-6">
                            <div class="p-3 rounded text-center" style="background: #f8f9fa; border: 1px solid #e9ecef;">
                                <div class="fw-bold text-primary mb-1" style="font-size: 18px; font-family: monospace;">
                                    ${task.estimated_hours || 0}:${(task.estimated_minutes || 0).toString().padStart(2, '0')}
                                </div>
                                <small class="text-muted" style="font-size: 11px;">الوقت المقدر</small>
                            </div>
                        </div>
                    ` : ''}
                    <div class="col-6">
                        <div class="p-3 rounded text-center" style="background: #e8f5e8; border: 1px solid #c3e6c3;">
                            <div class="fw-bold text-success mb-1" style="font-size: 18px; font-family: monospace;">
                                ${Math.floor((task.actual_minutes || 0) / 60)}:${((task.actual_minutes || 0) % 60).toString().padStart(2, '0')}
                            </div>
                            <small class="text-muted" style="font-size: 11px;">الوقت الفعلي</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Assignee Section -->
            ${task.user ? `
                <div class="mb-4">
                    <label class="form-label fw-semibold text-muted mb-2" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">المُعين للمهمة</label>
                    <div class="d-flex align-items-center p-3 rounded" style="background: #f8f9fa; border: 1px solid #e9ecef;">
                        <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <span class="text-white fw-bold" style="font-size: 14px;">${task.user.name.charAt(0).toUpperCase()}</span>
                        </div>
                        <div>
                            <div class="fw-semibold text-dark mb-1" style="font-size: 14px;">${task.user.name}</div>
                            <small class="text-muted" style="font-size: 12px;">${task.user.email}</small>
                        </div>
                    </div>
                </div>
            ` : ''}

            <!-- Project & Service Info -->
            <div class="mb-4">
                <label class="form-label fw-semibold text-muted mb-2" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">معلومات إضافية</label>
                <div class="p-3 rounded" style="background: #f8f9fa; border: 1px solid #e9ecef;">
                    ${task.project ? `
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-folder text-primary me-2" style="font-size: 14px;"></i>
                            <span class="fw-semibold text-dark" style="font-size: 13px;">${task.project.name}</span>
                        </div>
                    ` : ''}
                    ${task.service ? `
                        <div class="d-flex align-items-center">
                            <i class="fas fa-cogs text-info me-2" style="font-size: 14px;"></i>
                            <span class="text-muted" style="font-size: 13px;">${task.service.name}</span>
                        </div>
                    ` : ''}
                    <div class="d-flex align-items-center mt-2">
                        <i class="fas fa-star text-warning me-2" style="font-size: 14px;"></i>
                        <span class="text-muted" style="font-size: 13px;">النقاط: ${task.points || 0}</span>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="d-flex gap-2 pt-3" style="border-top: 1px solid #e9ecef;">
                <button class="btn btn-outline-secondary px-4 py-2" onclick="closeTaskSidebar()" style="border-radius: 6px; font-weight: 500; font-size: 13px;">
                    إغلاق
                </button>
            </div>
        </div>
    `;
}

// Function to approve task from sidebar
function approveSidebarTask(taskType, taskUserId) {
    const points = document.getElementById('sidebar-awarded-points').value;
    const note = document.getElementById('sidebar-approval-note').value;

    Swal.fire({
        title: 'تأكيد الاعتماد',
        html: `
            <p>هل أنت متأكد من اعتماد هذه المهمة؟</p>
            <p><strong>النقاط المستحقة:</strong> ${points} نقطة</p>
            ${note ? `<p><strong>الملاحظة:</strong> ${note}</p>` : ''}
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'نعم، اعتماد',
        cancelButtonText: 'إلغاء'
    }).then((result) => {
        if (result.isConfirmed) {
            // Create form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = taskType === 'template'
                ? `/task-deliveries/approve-template/${taskUserId}`
                : `/task-deliveries/approve-regular/${taskUserId}`;

            // Add CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = csrfToken;
            form.appendChild(csrfInput);

            // Add points
            const pointsInput = document.createElement('input');
            pointsInput.type = 'hidden';
            pointsInput.name = 'awarded_points';
            pointsInput.value = points;
            form.appendChild(pointsInput);

            // Add note if provided
            if (note) {
                const noteInput = document.createElement('input');
                noteInput.type = 'hidden';
                noteInput.name = 'approval_note';
                noteInput.value = note;
                form.appendChild(noteInput);
            }

            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>
<script src="{{ asset('js/projects/task-sidebar.js') }}"></script>
<script>
// Override the displayTaskDetails function for task-deliveries context
window.originalDisplayTaskDetails = window.displayTaskDetails;
window.displayTaskDetails = function(task) {
    // Check if we're on task-deliveries page by checking URL or page context
    if (window.location.pathname.includes('task-deliveries')) {
        displayTaskDetailsWithApproval(task);
    } else {
        window.originalDisplayTaskDetails(task);
    }
};

// دالة لتحميل مهام مشروع محدد
function loadProjectTasks(projectId, projectName) {
    // إظهار مؤشر التحميل
    const projectTasksContainer = document.getElementById('project-tasks-container');
    const projectsSection = document.querySelector('.projects-section');
    const projectTasksTitle = document.getElementById('project-tasks-title');
    const projectTasksStats = document.getElementById('project-tasks-stats');
    const projectTasksTable = document.getElementById('project-tasks-table');

    // إخفاء قائمة المشاريع وإظهار مؤشر التحميل
    projectsSection.style.display = 'none';
    projectTasksContainer.style.display = 'block';
    projectTasksTitle.textContent = `مهام مشروع: ${projectName}`;

    // مؤشر التحميل
    projectTasksTable.innerHTML = `
        <div class="d-flex justify-content-center align-items-center" style="height: 300px;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">جاري التحميل...</span>
            </div>
        </div>
    `;

    // جلب مهام المشروع
    fetch(`/task-deliveries/project/${projectId}/tasks`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // تحديث الإحصائيات
            projectTasksStats.innerHTML = `
                <span class="badge bg-info px-3 py-2">
                    <i class="fas fa-tasks me-1"></i>
                    ${data.total_tasks} مهمة إجمالي
                </span>
                <span class="badge bg-warning px-3 py-2">
                    <i class="fas fa-hourglass-half me-1"></i>
                    ${data.pending_approval} منتظرة للموافقة
                </span>
            `;

            // عرض جدول المهام
            if (data.tasks.length > 0) {
                projectTasksTable.innerHTML = generateTasksTable(data.tasks, data.is_hr_or_admin);
            } else {
                projectTasksTable.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-tasks"></i>
                        <h5>لا توجد مهام في هذا المشروع</h5>
                        <p class="text-muted">لا توجد مهام مُعيَّنة في هذا المشروع أو لا تملك صلاحية لعرضها.</p>
                    </div>
                `;
            }
        } else {
            projectTasksTable.innerHTML = `
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle"></i>
                    حدث خطأ في تحميل مهام المشروع: ${data.message || 'خطأ غير معروف'}
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        projectTasksTable.innerHTML = `
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-triangle"></i>
                حدث خطأ في الاتصال بالخادم
            </div>
        `;
    });
}

// دالة للعودة لقائمة المشاريع
function backToProjects() {
    const projectTasksContainer = document.getElementById('project-tasks-container');
    const projectsSection = document.querySelector('.projects-section');

    projectTasksContainer.style.display = 'none';
    projectsSection.style.display = 'block';
}

// دالة لإنتاج جدول المهام
function generateTasksTable(tasks, isHrOrAdmin) {
    const statusColors = {
        'new': 'secondary',
        'in_progress': 'primary',
        'paused': 'warning',
        'completed': 'success'
    };
    const statusTexts = {
        'new': 'جديدة',
        'in_progress': 'قيد التنفيذ',
        'paused': 'متوقفة',
        'completed': 'مكتملة'
    };

    let tableHtml = `
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th scope="col" width="8%" class="text-center">
                            <i class="fas fa-tag text-info me-2"></i>
                            النوع
                        </th>
                        <th scope="col" width="20%">
                            <i class="fas fa-user text-primary me-2"></i>
                            المهمة والموظف
                        </th>
                        <th scope="col" width="12%">
                            <i class="fas fa-building text-secondary me-2"></i>
                            المشروع
                        </th>
                        <th scope="col" width="8%" class="text-center">
                            <i class="fas fa-star text-warning me-2"></i>
                            النقاط
                        </th>
                        <th scope="col" width="8%" class="text-center">
                            <i class="fas fa-traffic-light text-secondary me-2"></i>
                            الحالة
                        </th>
                        <th scope="col" width="12%" class="text-center">
                            <i class="fas fa-calendar-alt text-danger me-2"></i>
                            الموعد النهائي
                        </th>
                        <th scope="col" width="8%" class="text-center">
                            <i class="fas fa-clock text-info me-2"></i>
                            الوقت الفعلي
                        </th>
                        <th scope="col" width="12%" class="text-center">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            تاريخ الإكمال
                        </th>
                        <th scope="col" width="12%" class="text-center">
                            <i class="fas fa-cog text-secondary me-2"></i>
                            الإجراءات
                        </th>
                    </tr>
                </thead>
                <tbody>
    `;

    tasks.forEach(task => {
        // معالجة الديدلاين
        let deadlineHtml = '<span class="text-muted">غير محدد</span>';
        if (task.deadline) {
            const deadline = new Date(task.deadline);
            const now = new Date();
            const isOverdue = deadline < now && task.status !== 'completed';
            const isDueSoon = deadline > now && (deadline - now) <= 24*60*60*1000 && task.status !== 'completed';

            let colorClass = 'text-success';
            let statusText = '';

            if (isOverdue) {
                colorClass = 'text-danger';
                statusText = '<br><small class="text-danger"><i class="fas fa-exclamation-triangle"></i> متأخرة</small>';
            } else if (isDueSoon) {
                colorClass = 'text-warning';
                statusText = '<br><small class="text-warning"><i class="fas fa-clock"></i> قريبة الانتهاء</small>';
            }

            deadlineHtml = `
                <div class="fw-semibold ${colorClass}">
                    ${deadline.toLocaleDateString('ar-SA')}
                </div>
                <small class="text-muted">${deadline.toLocaleTimeString('ar-SA', {hour: '2-digit', minute: '2-digit'})}</small>
                ${statusText}
            `;
        }

        // معالجة تاريخ الإكمال
        let completedDateHtml = '<span class="text-muted">غير مكتملة</span>';
        if (task.completed_date) {
            const completedDate = new Date(task.completed_date);
            completedDateHtml = `
                <div class="fw-semibold text-success">
                    ${completedDate.toLocaleDateString('ar-SA')}
                </div>
                <small class="text-muted">
                    ${completedDate.toLocaleTimeString('ar-SA', {hour: '2-digit', minute: '2-digit'})}
                </small>
            `;
        }

        // معالجة الإجراءات
        let actionsHtml = '<span class="text-muted">-</span>';
        if (task.status === 'completed' && !task.is_approved) {
            actionsHtml = `
                <button class="btn btn-success btn-sm" onclick="event.stopPropagation(); toggleApprovalForm('${task.type}-${task.id}')">
                    <i class="fas fa-check"></i> اعتماد
                </button>
            `;
        } else if (task.is_approved) {
            actionsHtml = `
                <span class="badge bg-success">
                    <i class="fas fa-check-circle"></i> معتمدة
                </span>
            `;
        }

        tableHtml += `
            <tr data-task-type="${task.type}" data-task-id="${task.id}" onclick="openTaskSidebar(this.getAttribute('data-task-type'), this.getAttribute('data-task-id'))" style="cursor: pointer;">
                <!-- نوع المهمة -->
                <td class="text-center">
                    ${task.type === 'regular' ?
                        '<span class="badge bg-primary rounded-pill"><i class="fas fa-tasks me-1"></i> عادية</span>' :
                        '<span class="badge bg-success rounded-pill"><i class="fas fa-layer-group me-1"></i> قالب</span>'
                    }
                </td>

                <!-- المهمة والموظف -->
                <td>
                    <div class="d-flex align-items-center">
                        <img src="${task.user.profile_photo_url || '/avatars/default.png'}"
                             class="rounded-circle me-3" width="40" height="40"
                             alt="${task.user.name}">
                        <div>
                            <h6 class="mb-1 fw-semibold">${task.task_name}</h6>
                            <p class="mb-0 text-muted small">${task.user.name}</p>
                            <small class="text-success">
                                <i class="fas fa-user-plus"></i> ${task.created_by}
                            </small>
                        </div>
                    </div>
                </td>

                <!-- المشروع -->
                <td>
                    ${task.project ? `
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle d-flex align-items-center justify-content-center me-2"
                                 style="width: 30px; height: 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                <i class="fas fa-building text-white" style="font-size: 12px;"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-semibold">${task.project.name}</h6>
                                ${task.project.manager ? `<small class="text-muted">${task.project.manager}</small>` : ''}
                            </div>
                        </div>
                    ` : `
                        <div class="text-center">
                            <small class="text-muted">
                                <i class="fas fa-minus-circle"></i><br>
                                غير مربوط بمشروع
                            </small>
                        </div>
                    `}
                </td>

                <!-- النقاط -->
                <td class="text-center">
                    <div class="fw-bold text-warning">${task.points}</div>
                    <small class="text-muted">نقطة</small>
                </td>

                <!-- حالة المهمة -->
                <td class="text-center">
                    <span class="badge bg-${statusColors[task.status] || 'secondary'}">
                        ${statusTexts[task.status] || task.status}
                    </span>
                </td>

                <!-- الموعد النهائي -->
                <td class="text-center">
                    ${deadlineHtml}
                </td>

                <!-- الوقت الفعلي -->
                <td class="text-center">
                    <div class="fw-bold text-info">
                        ${task.actual_time.hours}:${task.actual_time.minutes.toString().padStart(2, '0')}
                    </div>
                    <small class="text-muted">ساعة:دقيقة</small>
                </td>

                <!-- تاريخ الإكمال -->
                <td class="text-center">
                    ${completedDateHtml}
                </td>

                <!-- الإجراءات -->
                <td class="text-center">
                    ${actionsHtml}
                </td>
            </tr>
        `;

        // إضافة صف نموذج الموافقة إذا كانت المهمة مكتملة وغير معتمدة
        if (task.status === 'completed' && !task.is_approved) {
            const routeName = task.type === 'regular' ? 'task-deliveries.approve-regular' : 'task-deliveries.approve-template';
            tableHtml += `
                            <tr class="approval-form-row" id="approval-form-${task.type}-${task.id}" style="display: none;">
                                <td colspan="9">
                        <div class="p-4 bg-light border rounded">
                            <form action="/${routeName}/${task.raw_data.id}" method="POST">
                                <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').getAttribute('content')}">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">
                                                <i class="fas fa-star text-warning me-1"></i>
                                                النقاط المستحقة
                                            </label>
                                            <input type="number" class="form-control" name="awarded_points"
                                                   min="0" max="1000" value="${task.points}"
                                                   placeholder="أدخل النقاط المستحقة">
                                            <small class="text-muted">اتركه كما هو لاستخدام النقاط الأصلية</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">
                                                <i class="fas fa-comment text-info me-1"></i>
                                                ملاحظة الاعتماد
                                            </label>
                                            <textarea class="form-control" name="approval_note"
                                                      rows="2" placeholder="أضف ملاحظة (اختياري)"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-secondary"
                                            onclick="toggleApprovalForm('${task.type}-${task.id}')">
                                        إلغاء
                                    </button>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-check"></i> تأكيد الاعتماد
                                    </button>
                                </div>
                            </form>
                        </div>
                    </td>
                </tr>
            `;
        }
    });

    tableHtml += `
                </tbody>
            </table>
        </div>
    `;

    return tableHtml;
}

// ==================== نظام الاعتماد الإداري والفني للمشاريع ====================

/**
 * منح اعتماد إداري لتاسك
 */
function grantAdministrativeApproval(taskType, taskId) {
    Swal.fire({
        title: 'منح الاعتماد الإداري',
        text: 'هل أنت متأكد من منح الاعتماد الإداري لهذه المهمة؟',
        icon: 'question',
        input: 'textarea',
        inputLabel: 'ملاحظات الاعتماد (اختياري)',
        inputPlaceholder: 'أضف ملاحظاتك هنا...',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-check"></i> تأكيد الاعتماد',
        cancelButtonText: '<i class="fas fa-times"></i> إلغاء',
        confirmButtonColor: '#0d6efd',
        cancelButtonColor: '#6c757d',
        showLoaderOnConfirm: true,
        preConfirm: (notes) => {
            const endpoint = taskType === 'regular'
                ? `/task-deliveries/tasks/${taskId}/grant-administrative-approval`
                : `/task-deliveries/template-tasks/${taskId}/grant-administrative-approval`;

            return fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ notes: notes || null })
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => {
                        throw new Error(err.message || 'حدث خطأ أثناء منح الاعتماد');
                    });
                }
                return response.json();
            })
            .catch(error => {
                Swal.showValidationMessage(`فشل الطلب: ${error.message}`);
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'نجح!',
                text: 'تم منح الاعتماد الإداري بنجاح',
                icon: 'success',
                confirmButtonText: 'حسناً',
                confirmButtonColor: '#198754'
            }).then(() => {
                location.reload(); // إعادة تحميل الصفحة
            });
        }
    });
}

/**
 * منح اعتماد فني لتاسك
 */
function grantTechnicalApproval(taskType, taskId) {
    Swal.fire({
        title: 'منح الاعتماد الفني',
        text: 'هل أنت متأكد من منح الاعتماد الفني لهذه المهمة؟',
        icon: 'question',
        input: 'textarea',
        inputLabel: 'ملاحظات الاعتماد (اختياري)',
        inputPlaceholder: 'أضف ملاحظاتك هنا...',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-check"></i> تأكيد الاعتماد',
        cancelButtonText: '<i class="fas fa-times"></i> إلغاء',
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d',
        showLoaderOnConfirm: true,
        preConfirm: (notes) => {
            const endpoint = taskType === 'regular'
                ? `/task-deliveries/tasks/${taskId}/grant-technical-approval`
                : `/task-deliveries/template-tasks/${taskId}/grant-technical-approval`;

            return fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ notes: notes || null })
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => {
                        throw new Error(err.message || 'حدث خطأ أثناء منح الاعتماد');
                    });
                }
                return response.json();
            })
            .catch(error => {
                Swal.showValidationMessage(`فشل الطلب: ${error.message}`);
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'نجح!',
                text: 'تم منح الاعتماد الفني بنجاح',
                icon: 'success',
                confirmButtonText: 'حسناً',
                confirmButtonColor: '#198754'
            }).then(() => {
                location.reload(); // إعادة تحميل الصفحة
            });
        }
    });
}

/**
 * إلغاء اعتماد إداري لتاسك
 */
function revokeAdministrativeApproval(taskType, taskId) {
    Swal.fire({
        title: 'إلغاء الاعتماد الإداري',
        text: 'هل أنت متأكد من إلغاء الاعتماد الإداري لهذه المهمة؟',
        icon: 'warning',
        input: 'textarea',
        inputLabel: 'سبب الإلغاء (اختياري)',
        inputPlaceholder: 'أضف سبب الإلغاء هنا...',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-times"></i> تأكيد الإلغاء',
        cancelButtonText: '<i class="fas fa-ban"></i> إلغاء',
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#6c757d',
        showLoaderOnConfirm: true,
        preConfirm: (notes) => {
            const endpoint = taskType === 'regular'
                ? `/task-deliveries/tasks/${taskId}/revoke-administrative-approval`
                : `/task-deliveries/template-tasks/${taskId}/revoke-administrative-approval`;

            return fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ notes: notes || null })
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => {
                        throw new Error(err.message || 'حدث خطأ أثناء إلغاء الاعتماد');
                    });
                }
                return response.json();
            })
            .catch(error => {
                Swal.showValidationMessage(`فشل الطلب: ${error.message}`);
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'تم الإلغاء!',
                text: 'تم إلغاء الاعتماد الإداري بنجاح',
                icon: 'success',
                confirmButtonText: 'حسناً',
                confirmButtonColor: '#198754'
            }).then(() => {
                location.reload(); // إعادة تحميل الصفحة
            });
        }
    });
}

/**
 * إلغاء اعتماد فني لتاسك
 */
function revokeTechnicalApproval(taskType, taskId) {
    Swal.fire({
        title: 'إلغاء الاعتماد الفني',
        text: 'هل أنت متأكد من إلغاء الاعتماد الفني لهذه المهمة؟',
        icon: 'warning',
        input: 'textarea',
        inputLabel: 'سبب الإلغاء (اختياري)',
        inputPlaceholder: 'أضف سبب الإلغاء هنا...',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-times"></i> تأكيد الإلغاء',
        cancelButtonText: '<i class="fas fa-ban"></i> إلغاء',
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        showLoaderOnConfirm: true,
        preConfirm: (notes) => {
            const endpoint = taskType === 'regular'
                ? `/task-deliveries/tasks/${taskId}/revoke-technical-approval`
                : `/task-deliveries/template-tasks/${taskId}/revoke-technical-approval`;

            return fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ notes: notes || null })
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => {
                        throw new Error(err.message || 'حدث خطأ أثناء إلغاء الاعتماد');
                    });
                }
                return response.json();
            })
            .catch(error => {
                Swal.showValidationMessage(`فشل الطلب: ${error.message}`);
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'تم الإلغاء!',
                text: 'تم إلغاء الاعتماد الفني بنجاح',
                icon: 'success',
                confirmButtonText: 'حسناً',
                confirmButtonColor: '#198754'
            }).then(() => {
                location.reload(); // إعادة تحميل الصفحة
            });
        }
    });
}

</script>
@endpush
@endsection
