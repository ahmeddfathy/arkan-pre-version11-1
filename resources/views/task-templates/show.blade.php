@extends('layouts.app')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/my-tasks.css') }}">
@endpush
@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">تفاصيل قالب المهام: {{ $taskTemplate->name }}</h5>
                    <div>
                        <a href="{{ route('task-templates.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-right"></i> العودة للقائمة
                        </a>

                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-uppercase text-body text-xs font-weight-bolder">معلومات القالب</h6>
                            <div class="table-responsive">
                                <table class="table">
                                    <tbody>
                                        <tr>
                                            <th>اسم القالب:</th>
                                            <td>{{ $taskTemplate->name }}</td>
                                        </tr>
                                        <tr>
                                            <th>الخدمة:</th>
                                            <td>{{ $taskTemplate->service->name ?? 'غير محدد' }}</td>
                                        </tr>
                                        <tr>
                                            <th>الوقت المقدر للقالب:</th>
                                            <td>
                                                @if(is_null($taskTemplate->estimated_hours) && is_null($taskTemplate->estimated_minutes))
                                                    <span class="badge bg-info">مرن</span>
                                                @else
                                                    {{ $taskTemplate->estimated_hours }}:{{ str_pad($taskTemplate->estimated_minutes, 2, '0', STR_PAD_LEFT) }}
                                                @endif
                                            </td>
                                        </tr>
                                        @if($taskTemplate->hasEstimatedTime())
                                        <tr>
                                            <th>تفصيل الوقت:</th>
                                            <td>
                                                @php
                                                    $breakdown = $taskTemplate->getTimeUsageBreakdown();
                                                    $totalMinutes = $taskTemplate->getTotalEstimatedMinutesAttribute();
                                                @endphp

                                                <div class="mb-2">
                                                    <small class="text-muted">الوقت الإجمالي للقالب: {{ $taskTemplate->estimated_hours }}س {{ $taskTemplate->estimated_minutes }}د</small>
                                                </div>

                                                <!-- المهام العامة (للجميع) -->
                                                @if(count($breakdown['general_tasks']) > 0)
                                                    <div class="mb-3">
                                                        <h6 class="mb-2"><i class="fas fa-users text-secondary"></i> المهام العامة:</h6>
                                                        @foreach($breakdown['general_tasks'] as $task)
                                                            @php
                                                                $progressPercentage = ($task['minutes'] / $totalMinutes) * 100;
                                                            @endphp
                                                            <div class="d-flex align-items-center mb-1">
                                                                <span class="small me-2">{{ $task['name'] }}:</span>
                                                                <span class="small me-2 text-{{ $progressPercentage > 100 ? 'danger' : 'info' }}">
                                                                    {{ $task['formatted'] }}
                                                                </span>
                                                                <div class="progress flex-grow-1" style="height: 6px;">
                                                                    <div class="progress-bar {{ $progressPercentage > 100 ? 'bg-danger' : 'bg-info' }}"
                                                                         style="width: {{ min($progressPercentage, 100) }}%"></div>
                                                                </div>
                                                                <small class="ms-2 text-{{ $progressPercentage > 100 ? 'danger' : 'muted' }}">
                                                                    {{ number_format($progressPercentage, 1) }}%
                                                                </small>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif

                                                <!-- مهام الأدوار -->
                                                @if(count($breakdown['roles']) > 0)
                                                    @foreach($breakdown['roles'] as $roleData)
                                                        @php
                                                            $roleProgressPercentage = ($roleData['total_minutes'] / $totalMinutes) * 100;
                                                        @endphp
                                                        <div class="mb-3">
                                                            <h6 class="mb-2">
                                                                <i class="fas fa-user-tag text-primary"></i>
                                                                {{ $roleData['name'] }}:
                                                                <span class="text-{{ $roleProgressPercentage > 100 ? 'danger' : 'primary' }}">
                                                                    {{ $roleData['formatted'] }}
                                                                </span>
                                                                <small class="text-muted">({{ number_format($roleProgressPercentage, 1) }}%)</small>
                                                            </h6>
                                                            <div class="progress mb-2" style="height: 8px;">
                                                                <div class="progress-bar {{ $roleProgressPercentage > 100 ? 'bg-danger' : 'bg-primary' }}"
                                                                     style="width: {{ min($roleProgressPercentage, 100) }}%"></div>
                                                            </div>
                                                            <div class="ps-3">
                                                                @foreach($roleData['tasks'] as $task)
                                                                    <div class="small text-muted mb-1">
                                                                        • {{ $task['name'] }}: {{ $task['formatted'] }}
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                @endif

                                                @if(count($breakdown['general_tasks']) === 0 && count($breakdown['roles']) === 0)
                                                    <div class="text-center text-muted py-2">
                                                        <i class="fas fa-info-circle"></i> لم يتم إضافة مهام بعد
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                        @endif
                                        <tr>
                                            <th>الترتيب:</th>
                                            <td>{{ $taskTemplate->order }}</td>
                                        </tr>
                                        <tr>
                                            <th>الحالة:</th>
                                            <td>
                                                <span class="badge {{ $taskTemplate->is_active ? 'bg-success' : 'bg-danger' }}">
                                                    {{ $taskTemplate->is_active ? 'نشط' : 'غير نشط' }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-uppercase text-body text-xs font-weight-bolder">الوصف</h6>
                            <div class="p-3 bg-light rounded mb-4">
                                {!! nl2br(e($taskTemplate->description ?? 'لا يوجد وصف')) !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">مهام القالب</h5>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTaskModal">
                        <i class="fas fa-plus"></i> إضافة مهمة للقالب
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-items-center mb-0" id="tasksTable">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">الترتيب</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">اسم المهمة</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">الدور</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">الوصف</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">الوقت المقدر</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">النقاط</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">الحالة</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($taskTemplate->templateTasks as $task)
                                <tr data-task-id="{{ $task->id }}">
                                    <td>{{ $task->order }}</td>
                                    <td>
                                        <div class="d-flex px-2 py-1">
                                            <div class="d-flex flex-column justify-content-center">
                                                <h6 class="mb-0 text-sm">{{ $task->name }}</h6>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($task->role)
                                            <span class="badge bg-primary">{{ $task->role->name }}</span>
                                        @else
                                            <span class="badge bg-secondary">للجميع</span>
                                        @endif
                                    </td>
                                    <td>{{ Str::limit($task->description, 50) }}</td>
                                    <td>
                                        @if(is_null($task->estimated_hours) && is_null($task->estimated_minutes))
                                            <span class="badge bg-info">مرن</span>
                                        @else
                                            {{ $task->estimated_hours }}:{{ str_pad($task->estimated_minutes, 2, '0', STR_PAD_LEFT) }}
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-warning text-dark">
                                            <i class="fas fa-star me-1"></i>{{ $task->points ?? 0 }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $task->is_active ? 'bg-success' : 'bg-danger' }}">
                                            {{ $task->is_active ? 'نشط' : 'غير نشط' }}
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info edit-task"
                                                data-task-id="{{ $task->id }}"
                                                data-name="{{ $task->name }}"
                                                data-description="{{ $task->description }}"
                                                data-role="{{ $task->role_id }}"
                                                data-order="{{ $task->order }}"
                                                data-hours="{{ $task->estimated_hours }}"
                                                data-minutes="{{ $task->estimated_minutes }}"
                                                data-points="{{ $task->points ?? 0 }}"
                                                data-active="{{ $task->is_active }}">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger delete-task" data-task-id="{{ $task->id }}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr id="no-tasks-row">
                                    <td colspan="8" class="text-center">
                                        <div class="py-4">
                                            <img src="{{ asset('img/empty-data.svg') }}" alt="لا توجد بيانات" style="width:150px;">
                                            <p class="mt-3">لا توجد مهام في هذا القالب بعد</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal إضافة مهمة -->
<div class="modal fade" id="addTaskModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">إضافة مهمة جديدة للقالب</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addTaskForm">
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label for="name">اسم المهمة</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="form-group mb-3">
                        <label for="description">الوصف</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>

                    <!-- ✨ قسم البنود (Task Items) -->
                    <div class="card mb-3 border">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                            <h6 class="mb-0"><i class="fas fa-list-check text-primary"></i> بنود المهمة</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="addItemBtn">
                                <i class="fas fa-plus"></i> إضافة بند
                            </button>
                        </div>
                        <div class="card-body p-2">
                            <!-- نموذج إضافة بند (مخفي افتراضياً) -->
                            <div id="itemFormContainer" style="display: none;" class="mb-3 p-3 border rounded bg-light">
                                <div class="form-group mb-2">
                                    <label class="form-label small fw-bold">عنوان البند <span class="text-danger">*</span></label>
                                    <input type="text" id="newItemTitleInput" class="form-control form-control-sm" placeholder="مثال: مراجعة التصميم">
                                </div>
                                <div class="form-group mb-2">
                                    <label class="form-label small fw-bold">تفاصيل البند</label>
                                    <textarea id="newItemDescInput" class="form-control form-control-sm" rows="2" placeholder="تفاصيل إضافية (اختياري)"></textarea>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-primary" id="saveItemBtn">
                                        <i class="fas fa-check"></i> حفظ
                                    </button>
                                    <button type="button" class="btn btn-sm btn-secondary" id="cancelItemBtn">
                                        <i class="fas fa-times"></i> إلغاء
                                    </button>
                                </div>
                            </div>

                            <!-- قائمة البنود -->
                            <div id="taskItemsContainer">
                                <p class="text-muted text-center py-2 mb-0 small" id="noItemsMessage">
                                    <i class="fas fa-info-circle"></i> لم يتم إضافة بنود بعد
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- خيار المهمة المرنة -->
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="is_flexible_time" name="is_flexible_time">
                        <label class="form-check-label" for="is_flexible_time">
                            <strong>🕐 مهمة مرنة (بدون وقت محدد)</strong>
                            <small class="text-muted d-block">عند تفعيل هذا الخيار، ستصبح المهمة مرنة ولن تحتاج لتحديد وقت مقدر</small>
                        </label>
                    </div>

                    <div class="row mb-3" id="time_fields_section">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="role_id">الدور المخصص</label>
                                <select class="form-control" id="role_id" name="role_id">
                                    <option value="">للجميع</option>
                                    @foreach($departmentRoles as $deptRole)
                                        <option value="{{ $deptRole->role->id }}">{{ $deptRole->role->name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">اختر دور معين أو اتركه للجميع</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="order">الترتيب</label>
                                <input type="number" class="form-control" id="order" name="order" min="0" value="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="points"><i class="fas fa-star text-warning"></i> النقاط</label>
                                <input type="number" class="form-control" id="points" name="points" min="0" max="1000" value="10">
                                <small class="text-muted">النقاط الافتراضية: 10</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="estimated_hours">الساعات</label>
                                <input type="number" class="form-control" id="estimated_hours" name="estimated_hours" min="0" value="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="estimated_minutes">الدقائق</label>
                                <input type="number" class="form-control" id="estimated_minutes" name="estimated_minutes" min="0" max="59" value="0">
                            </div>
                        </div>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                        <label class="form-check-label" for="is_active">نشط</label>
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

<!-- Modal تعديل مهمة -->
<div class="modal fade" id="editTaskModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تعديل المهمة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editTaskForm">
                <input type="hidden" id="edit_task_id">
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label for="edit_name">اسم المهمة</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="form-group mb-3">
                        <label for="edit_description">الوصف</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>

                    <!-- ✨ قسم البنود (Task Items) - التعديل -->
                    <div class="card mb-3 border">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                            <h6 class="mb-0"><i class="fas fa-list-check text-primary"></i> بنود المهمة</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="editAddItemBtn">
                                <i class="fas fa-plus"></i> إضافة بند
                            </button>
                        </div>
                        <div class="card-body p-2">
                            <!-- نموذج إضافة بند للتعديل (مخفي افتراضياً) -->
                            <div id="editItemFormContainer" style="display: none;" class="mb-3 p-3 border rounded bg-light">
                                <div class="form-group mb-2">
                                    <label class="form-label small fw-bold">عنوان البند <span class="text-danger">*</span></label>
                                    <input type="text" id="editNewItemTitleInput" class="form-control form-control-sm" placeholder="مثال: مراجعة التصميم">
                                </div>
                                <div class="form-group mb-2">
                                    <label class="form-label small fw-bold">تفاصيل البند</label>
                                    <textarea id="editNewItemDescInput" class="form-control form-control-sm" rows="2" placeholder="تفاصيل إضافية (اختياري)"></textarea>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-primary" id="editSaveItemBtn">
                                        <i class="fas fa-check"></i> حفظ
                                    </button>
                                    <button type="button" class="btn btn-sm btn-secondary" id="editCancelItemBtn">
                                        <i class="fas fa-times"></i> إلغاء
                                    </button>
                                </div>
                            </div>

                            <!-- قائمة البنود -->
                            <div id="editTaskItemsContainer">
                                <p class="text-muted text-center py-2 mb-0 small" id="editNoItemsMessage">
                                    <i class="fas fa-info-circle"></i> لم يتم إضافة بنود بعد
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- خيار المهمة المرنة -->
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="edit_is_flexible_time" name="is_flexible_time">
                        <label class="form-check-label" for="edit_is_flexible_time">
                            <strong>🕐 مهمة مرنة (بدون وقت محدد)</strong>
                            <small class="text-muted d-block">عند تفعيل هذا الخيار، ستصبح المهمة مرنة ولن تحتاج لتحديد وقت مقدر</small>
                        </label>
                    </div>

                    <div class="row mb-3" id="edit_time_fields_section">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_role_id">الدور المخصص</label>
                                <select class="form-control" id="edit_role_id" name="role_id">
                                    <option value="">للجميع</option>
                                    @foreach($departmentRoles as $deptRole)
                                        <option value="{{ $deptRole->role->id }}">{{ $deptRole->role->name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">اختر دور معين أو اتركه للجميع</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_order">الترتيب</label>
                                <input type="number" class="form-control" id="edit_order" name="order" min="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_points"><i class="fas fa-star text-warning"></i> النقاط</label>
                                <input type="number" class="form-control" id="edit_points" name="points" min="0" max="1000">
                                <small class="text-muted">النقاط الافتراضية: 10</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_estimated_hours">الساعات</label>
                                <input type="number" class="form-control" id="edit_estimated_hours" name="estimated_hours" min="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_estimated_minutes">الدقائق</label>
                                <input type="number" class="form-control" id="edit_estimated_minutes" name="estimated_minutes" min="0" max="59">
                            </div>
                        </div>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active">
                        <label class="form-check-label" for="edit_is_active">نشط</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('styles')
<!-- SweetAlert2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.28/dist/sweetalert2.min.css">
@endpush

@push('scripts')
<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.28/dist/sweetalert2.all.min.js"></script>

<script>
    $(document).ready(function() {

        // ==========================================
        // 📋 إدارة البنود (Task Items)
        // ==========================================

        let taskItems = []; // مصفوفة البنود للـ modal الإضافة
        let editTaskItems = []; // مصفوفة البنود للـ modal التعديل

        // دالة لإضافة بند جديد
        function addTaskItem(title = '', description = '', isEditMode = false) {
            const itemId = 'item_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            const item = {
                id: itemId,
                title: title,
                description: description,
                order: isEditMode ? editTaskItems.length + 1 : taskItems.length + 1
            };

            if (isEditMode) {
                editTaskItems.push(item);
            } else {
                taskItems.push(item);
            }

            renderTaskItems(isEditMode);
        }

        // دالة لحذف بند
        function removeTaskItem(itemId, isEditMode = false) {
            if (isEditMode) {
                editTaskItems = editTaskItems.filter(item => item.id !== itemId);
            } else {
                taskItems = taskItems.filter(item => item.id !== itemId);
            }
            renderTaskItems(isEditMode);
        }

        // دالة لعرض البنود
        function renderTaskItems(isEditMode = false) {
            const items = isEditMode ? editTaskItems : taskItems;
            const container = isEditMode ? '#editTaskItemsContainer' : '#taskItemsContainer';
            const noItemsMsg = isEditMode ? '#editNoItemsMessage' : '#noItemsMessage';

            if (items.length === 0) {
                $(container).html(`
                    <p class="text-muted text-center py-2 mb-0 small" id="${noItemsMsg.substring(1)}">
                        <i class="fas fa-info-circle"></i> لم يتم إضافة بنود بعد
                    </p>
                `);
                return;
            }

            let html = '<div class="list-group list-group-flush">';
            items.forEach((item, index) => {
                html += `
                    <div class="list-group-item px-2 py-2" data-item-id="${item.id}">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center mb-1">
                                    <i class="fas fa-check-circle text-success me-2" style="font-size: 14px;"></i>
                                    <strong class="text-dark" style="font-size: 13px;">${item.title || '(بدون عنوان)'}</strong>
                                </div>
                                ${item.description ? `
                                    <p class="text-muted mb-0 ms-4" style="font-size: 12px;">
                                        ${item.description}
                                    </p>
                                ` : ''}
                            </div>
                            <button type="button"
                                    class="btn btn-sm btn-outline-danger remove-item-btn ms-2"
                                    data-item-id="${item.id}"
                                    title="حذف البند">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>
                `;
            });
            html += '</div>';

            $(container).html(html);
        }

        // إضافة بند - modal الإضافة (inline form)
        $('#addItemBtn').click(function(e) {
            e.preventDefault();
            e.stopPropagation();
            $('#itemFormContainer').slideDown(300);
            $('#newItemTitleInput').focus();
        });

        // زر إلغاء
        $('#cancelItemBtn').click(function() {
            $('#newItemTitleInput').val('');
            $('#newItemDescInput').val('');
            $('#itemFormContainer').slideUp(300);
        });

        // زر حفظ البند
        $('#saveItemBtn').click(function() {
            const title = $('#newItemTitleInput').val().trim();
            const description = $('#newItemDescInput').val().trim();

            if (!title) {
                Swal.fire({
                    icon: 'warning',
                    title: 'تنبيه',
                    text: 'يرجى إدخال عنوان البند',
                    confirmButtonText: 'حسناً',
                    toast: true,
                    position: 'top-end',
                    timer: 2000
                });
                return;
            }

            addTaskItem(title, description, false);
            $('#newItemTitleInput').val('');
            $('#newItemDescInput').val('');
            $('#itemFormContainer').slideUp(300);
        });

        // إضافة بند - modal التعديل (inline form)
        $('#editAddItemBtn').click(function(e) {
            e.preventDefault();
            e.stopPropagation();
            $('#editItemFormContainer').slideDown(300);
            $('#editNewItemTitleInput').focus();
        });

        // زر إلغاء التعديل
        $('#editCancelItemBtn').click(function() {
            $('#editNewItemTitleInput').val('');
            $('#editNewItemDescInput').val('');
            $('#editItemFormContainer').slideUp(300);
        });

        // زر حفظ البند في التعديل
        $('#editSaveItemBtn').click(function() {
            const title = $('#editNewItemTitleInput').val().trim();
            const description = $('#editNewItemDescInput').val().trim();

            if (!title) {
                Swal.fire({
                    icon: 'warning',
                    title: 'تنبيه',
                    text: 'يرجى إدخال عنوان البند',
                    confirmButtonText: 'حسناً',
                    toast: true,
                    position: 'top-end',
                    timer: 2000
                });
                return;
            }

            addTaskItem(title, description, true);
            $('#editNewItemTitleInput').val('');
            $('#editNewItemDescInput').val('');
            $('#editItemFormContainer').slideUp(300);
        });

        // حذف بند
        $(document).on('click', '.remove-item-btn', function() {
            const itemId = $(this).data('item-id');
            const isEditMode = $(this).closest('#editTaskItemsContainer').length > 0;
            removeTaskItem(itemId, isEditMode);
        });

        // ==========================================
        // إضافة مهمة جديدة
        // ==========================================
        $('#addTaskForm').submit(function(e) {
            e.preventDefault();

            const isFlexible = $('#is_flexible_time').is(':checked');
            const formData = {
                name: $('#name').val(),
                description: $('#description').val(),
                role_id: $('#role_id').val() || null,
                order: $('#order').val(),
                points: $('#points').val() || 10,
                is_active: $('#is_active').is(':checked') ? 1 : 0,
                is_flexible_time: isFlexible,
                items: taskItems // 📋 إضافة البنود
            };

            // إضافة حقول الوقت إذا لم تكن المهمة مرنة
            if (!isFlexible) {
                formData.estimated_hours = $('#estimated_hours').val();
                formData.estimated_minutes = $('#estimated_minutes').val();
            } else {
                formData.estimated_hours = null;
                formData.estimated_minutes = null;
            }

            // تسجيل البيانات للتشخيص
            console.log('📤 إرسال بيانات المهمة الجديدة:', {
                role_selected: $('#role_id option:selected').text(),
                role_id: formData.role_id,
                estimated_hours: formData.estimated_hours,
                estimated_minutes: formData.estimated_minutes,
                is_flexible: isFlexible,
                full_data: formData
            });

            $.ajax({
                url: "{{ route('task-templates.tasks.store', $taskTemplate) }}",
                type: "POST",
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    console.log('✅ استجابة الخادم لإضافة المهمة:', response);

                    if (response.success) {
                        const task = response.task;

                        // إضافة الصف الجديد للجدول
                        const newRow = `
                            <tr data-task-id="${task.id}">
                                <td>${task.order}</td>
                                <td>
                                    <div class="d-flex px-2 py-1">
                                        <div class="d-flex flex-column justify-content-center">
                                            <h6 class="mb-0 text-sm">${task.name}</h6>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    ${task.role ? `<span class="badge bg-primary">${task.role.name}</span>` : '<span class="badge bg-secondary">للجميع</span>'}
                                </td>
                                <td>${task.description ? (task.description.length > 50 ? task.description.substring(0, 50) + '...' : task.description) : ''}</td>
                                <td>
                                    ${task.estimated_hours === null && task.estimated_minutes === null ?
                                        '<span class="badge bg-info">مرن</span>' :
                                        `${task.estimated_hours}:${String(task.estimated_minutes).padStart(2, '0')}`
                                    }
                                </td>
                                <td>
                                    <span class="badge bg-warning text-dark">
                                        <i class="fas fa-star me-1"></i>${task.points || 0}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge ${task.is_active ? 'bg-success' : 'bg-danger'}">
                                        ${task.is_active ? 'نشط' : 'غير نشط'}
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-info edit-task"
                                            data-task-id="${task.id}"
                                            data-name="${task.name}"
                                            data-description="${task.description || ''}"
                                            data-role="${task.role_id || ''}"
                                            data-order="${task.order}"
                                            data-hours="${task.estimated_hours}"
                                            data-minutes="${task.estimated_minutes}"
                                            data-points="${task.points || 0}"
                                            data-active="${task.is_active}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger delete-task" data-task-id="${task.id}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        `;

                        // إزالة صف "لا توجد مهام" إذا كان موجوداً
                        $('#no-tasks-row').remove();

                        // إضافة الصف الجديد
                        $('#tasksTable tbody').append(newRow);

                        // إغلاق النافذة المنبثقة وتفريغ النموذج
                        $('#addTaskModal').modal('hide');
                        $('#addTaskForm')[0].reset();
                        taskItems = []; // تفريغ البنود
                        renderTaskItems(false);

                        // عرض رسالة نجاح
                        Swal.fire({
                            icon: 'success',
                            title: '✅ تم بنجاح!',
                            text: `تم إضافة المهمة "${task.name}" بنجاح`,
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true
                        });
                    }
                },
                error: function(xhr) {
                    const errors = xhr.responseJSON.errors;
                    let errorMessage = 'حدث خطأ أثناء تحديث المهمة:';

                    // عرض خطأ توزيع الوقت بشكل خاص
                    if (errors.time_distribution) {
                        errorMessage = '⚠️ خطأ في توزيع الوقت:\n' + errors.time_distribution[0];
                    } else {
                        // عرض باقي الأخطاء
                        for (let field in errors) {
                            errorMessage += '\n' + errors[field][0];
                        }
                    }

                    Swal.fire({
                        icon: 'error',
                        title: '❌ خطأ في إضافة المهمة!',
                        text: errorMessage,
                        confirmButtonText: 'موافق',
                        confirmButtonColor: '#d33'
                    });
                }
            });
        });

        // تعبئة نموذج تعديل المهمة
        $(document).on('click', '.edit-task', function() {
            const taskId = $(this).data('task-id');
            const name = $(this).data('name');
            const description = $(this).data('description');
            const roleId = $(this).data('role');
            const order = $(this).data('order');
            const hours = $(this).data('hours');
            const minutes = $(this).data('minutes');
            const points = $(this).data('points');
            const active = $(this).data('active');

            $('#edit_task_id').val(taskId);
            $('#edit_name').val(name);
            $('#edit_description').val(description);
            $('#edit_role_id').val(roleId);
            $('#edit_order').val(order);
            $('#edit_points').val(points || 10);

            // 📋 تحميل البنود من الخادم
            editTaskItems = [];
            $.ajax({
                url: `/template-tasks/${taskId}/items`,
                type: 'GET',
                success: function(response) {
                    if (response.success && response.items) {
                        editTaskItems = response.items.map(item => ({
                            id: item.id,
                            title: item.title,
                            description: item.description || '',
                            order: item.order || 0
                        }));
                        renderTaskItems(true);
                    }
                },
                error: function() {
                    console.log('تعذر تحميل البنود');
                }
            });

            // التحقق من كون المهمة مرنة (بدون وقت محدد)
            const isFlexible = (hours === null || hours === '' || hours === undefined) &&
                             (minutes === null || minutes === '' || minutes === undefined);

            $('#edit_is_flexible_time').prop('checked', isFlexible);

            if (isFlexible) {
                // إخفاء حقول الوقت للمهام المرنة
                $('#edit_time_fields_section .col-md-4:not(:first-child)').hide();
                $('#edit_estimated_hours').val('').removeAttr('required');
                $('#edit_estimated_minutes').val('').removeAttr('required');

                // إضافة رسالة توضيحية
                if (!$('#edit_flexible_notice').length) {
                    $('#edit_time_fields_section').after(`
                        <div class="alert alert-info mb-3" id="edit_flexible_notice">
                            <i class="fas fa-info-circle"></i>
                            <strong>مهمة مرنة:</strong> لن تحتاج لتحديد وقت مقدر. الوقت الفعلي الذي يقضيه المستخدم سيكون هو وقت المهمة.
                        </div>
                    `);
                }
            } else {
                // عرض حقول الوقت للمهام العادية
                $('#edit_time_fields_section .col-md-4:not(:first-child)').show();
                $('#edit_estimated_hours').val(hours).attr('required', true);
                $('#edit_estimated_minutes').val(minutes).attr('required', true);
                $('#edit_flexible_notice').remove();
            }

            $('#edit_is_active').prop('checked', active == 1 || active === true || active === 'true' || active === '1');

            $('#editTaskModal').modal('show');
        });

        // تحديث المهمة
        $('#editTaskForm').submit(function(e) {
            e.preventDefault();

            const taskId = $('#edit_task_id').val();
            const isFlexible = $('#edit_is_flexible_time').is(':checked');
            const formData = {
                name: $('#edit_name').val(),
                description: $('#edit_description').val(),
                role_id: $('#edit_role_id').val() || null,
                order: $('#edit_order').val(),
                points: $('#edit_points').val() || 10,
                is_active: $('#edit_is_active').is(':checked') ? 1 : 0,
                is_flexible_time: isFlexible,
                items: editTaskItems // 📋 إضافة البنود المعدلة
            };

            // إضافة حقول الوقت إذا لم تكن المهمة مرنة
            if (!isFlexible) {
                formData.estimated_hours = $('#edit_estimated_hours').val();
                formData.estimated_minutes = $('#edit_estimated_minutes').val();
            } else {
                formData.estimated_hours = null;
                formData.estimated_minutes = null;
            }

            // تسجيل البيانات للتشخيص
            console.log('📤 تحديث بيانات المهمة:', {
                task_id: taskId,
                role_selected: $('#edit_role_id option:selected').text(),
                role_id: formData.role_id,
                estimated_hours: formData.estimated_hours,
                estimated_minutes: formData.estimated_minutes,
                is_flexible: isFlexible,
                full_data: formData
            });

            $.ajax({
                url: `/template-tasks/${taskId}`,
                type: "PUT",
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    console.log('✅ استجابة الخادم لتحديث المهمة:', response);

                    if (response.success) {
                        const task = response.task;

                        // تحديث الصف في الجدول
                        const row = $(`tr[data-task-id="${task.id}"]`);
                        row.find('td:eq(0)').text(task.order);
                        row.find('td:eq(1) h6').text(task.name);

                        // تحديث عرض الدور
                        const roleDisplay = task.role ? `<span class="badge bg-primary">${task.role.name}</span>` : '<span class="badge bg-secondary">للجميع</span>';
                        row.find('td:eq(2)').html(roleDisplay);

                        row.find('td:eq(3)').text(task.description ? (task.description.length > 50 ? task.description.substring(0, 50) + '...' : task.description) : '');

                        // تحديث عرض الوقت
                        const timeDisplay = (task.estimated_hours === null && task.estimated_minutes === null) ?
                            '<span class="badge bg-info">مرن</span>' :
                            `${task.estimated_hours}:${String(task.estimated_minutes).padStart(2, '0')}`;
                        row.find('td:eq(4)').html(timeDisplay);

                        // تحديث عرض النقاط
                        const pointsDisplay = `<span class="badge bg-warning text-dark"><i class="fas fa-star me-1"></i>${task.points || 0}</span>`;
                        row.find('td:eq(5)').html(pointsDisplay);

                        const badgeClass = task.is_active ? 'bg-success' : 'bg-danger';
                        const badgeText = task.is_active ? 'نشط' : 'غير نشط';
                        row.find('td:eq(6) span').removeClass('bg-success bg-danger').addClass(badgeClass).text(badgeText);

                        // تحديث بيانات الزر
                        const editBtn = row.find('.edit-task');
                        editBtn.data('name', task.name);
                        editBtn.data('description', task.description || '');
                        editBtn.data('role', task.role_id);
                        editBtn.data('order', task.order);
                        editBtn.data('hours', task.estimated_hours);
                        editBtn.data('minutes', task.estimated_minutes);
                        editBtn.data('points', task.points || 0);
                        editBtn.data('active', task.is_active);

                        // إغلاق النافذة المنبثقة
                        $('#editTaskModal').modal('hide');

                        // عرض رسالة نجاح
                        Swal.fire({
                            icon: 'success',
                            title: '✅ تم التحديث!',
                            text: `تم تحديث المهمة "${task.name}" بنجاح`,
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true
                        });
                    }
                },
                error: function(xhr) {
                    const errors = xhr.responseJSON.errors;
                    let errorMessage = 'حدث خطأ أثناء تحديث المهمة:';

                    // عرض خطأ توزيع الوقت بشكل خاص
                    if (errors.time_distribution) {
                        errorMessage = '⚠️ خطأ في توزيع الوقت:\n' + errors.time_distribution[0];
                    } else {
                        // عرض باقي الأخطاء
                        for (let field in errors) {
                            errorMessage += '\n' + errors[field][0];
                        }
                    }

                    Swal.fire({
                        icon: 'error',
                        title: '❌ خطأ في تحديث المهمة!',
                        text: errorMessage,
                        confirmButtonText: 'موافق',
                        confirmButtonColor: '#d33'
                    });
                }
            });
        });

        // حذف المهمة
        $(document).on('click', '.delete-task', function() {
            const taskId = $(this).data('task-id');
            const taskName = $(this).closest('tr').find('h6').text();

            Swal.fire({
                title: '🗑️ حذف المهمة',
                html: `هل أنت متأكد من حذف المهمة:<br><strong>"${taskName}"</strong>؟<br><br><small class="text-muted">لا يمكن التراجع عن هذا الإجراء</small>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'نعم، احذف!',
                cancelButtonText: 'إلغاء',
                focusCancel: true
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                    url: `/template-tasks/${taskId}`,
                    type: "DELETE",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            // حذف الصف من الجدول
                            $(`tr[data-task-id="${taskId}"]`).remove();

                            // إضافة صف "لا توجد مهام" إذا كان الجدول فارغاً
                            if ($('#tasksTable tbody tr').length === 0) {
                                const noTasksRow = `
                                    <tr id="no-tasks-row">
                                        <td colspan="8" class="text-center">
                                            <div class="py-4">
                                                <img src="{{ asset('img/empty-data.svg') }}" alt="لا توجد بيانات" style="width:150px;">
                                                <p class="mt-3">لا توجد مهام في هذا القالب بعد</p>
                                            </div>
                                        </td>
                                    </tr>
                                `;
                                $('#tasksTable tbody').append(noTasksRow);
                            }

                            // عرض رسالة نجاح
                            Swal.fire({
                                icon: 'success',
                                title: '🗑️ تم الحذف!',
                                text: 'تم حذف المهمة بنجاح',
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 3000,
                                timerProgressBar: true
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: '❌ خطأ!',
                            text: 'حدث خطأ أثناء حذف المهمة',
                            confirmButtonText: 'موافق',
                            confirmButtonColor: '#d33'
                        });
                    }
                });
                }
            });
        });

        // التحكم في المهام المرنة - modal الإضافة
        $('#is_flexible_time').change(function() {
            const isFlexible = $(this).is(':checked');
            const timeSection = $('#time_fields_section .col-md-4:not(:first-child)'); // الساعات والدقائق فقط، ليس الترتيب

            if (isFlexible) {
                // إخفاء حقول الوقت وتصفير قيمها
                timeSection.hide();
                $('#estimated_hours').val('').removeAttr('required');
                $('#estimated_minutes').val('').removeAttr('required');

                // إضافة رسالة توضيحية
                if (!$('#flexible_notice').length) {
                    $('#time_fields_section').after(`
                        <div class="alert alert-info mb-3" id="flexible_notice">
                            <i class="fas fa-info-circle"></i>
                            <strong>مهمة مرنة:</strong> لن تحتاج لتحديد وقت مقدر. الوقت الفعلي الذي يقضيه المستخدم سيكون هو وقت المهمة.
                        </div>
                    `);
                }
            } else {
                // إظهار حقول الوقت
                timeSection.show();
                $('#estimated_hours').val('0').attr('required', true);
                $('#estimated_minutes').val('0').attr('required', true);

                // إزالة الرسالة التوضيحية
                $('#flexible_notice').remove();
            }
        });

        // التحكم في المهام المرنة - modal التعديل
        $('#edit_is_flexible_time').change(function() {
            const isFlexible = $(this).is(':checked');
            const timeSection = $('#edit_time_fields_section .col-md-4:not(:first-child)'); // الساعات والدقائق فقط، ليس الترتيب

            if (isFlexible) {
                // إخفاء حقول الوقت وتصفير قيمها
                timeSection.hide();
                $('#edit_estimated_hours').val('').removeAttr('required');
                $('#edit_estimated_minutes').val('').removeAttr('required');

                // إضافة رسالة توضيحية
                if (!$('#edit_flexible_notice').length) {
                    $('#edit_time_fields_section').after(`
                        <div class="alert alert-info mb-3" id="edit_flexible_notice">
                            <i class="fas fa-info-circle"></i>
                            <strong>مهمة مرنة:</strong> لن تحتاج لتحديد وقت مقدر. الوقت الفعلي الذي يقضيه المستخدم سيكون هو وقت المهمة.
                        </div>
                    `);
                }
            } else {
                // إظهار حقول الوقت
                timeSection.show();
                $('#edit_estimated_hours').attr('required', true);
                $('#edit_estimated_minutes').attr('required', true);

                // إزالة الرسالة التوضيحية
                $('#edit_flexible_notice').remove();
            }
        });

        // تنظيف النماذج عند إغلاق modals
        $('#addTaskModal').on('hidden.bs.modal', function() {
            $(this).find('form')[0].reset();
            $('#time_fields_section .col-md-4:not(:first-child)').show();
            $('#flexible_notice').remove();
            $('#estimated_hours, #estimated_minutes').attr('required', true);

            // إخفاء وتنظيف form البنود
            $('#itemFormContainer').hide();
            $('#newItemTitleInput').val('');
            $('#newItemDescInput').val('');
            taskItems = [];
            renderTaskItems(false);
        });

        $('#editTaskModal').on('hidden.bs.modal', function() {
            $('#edit_time_fields_section .col-md-4:not(:first-child)').show();
            $('#edit_flexible_notice').remove();

            // إخفاء وتنظيف form البنود
            $('#editItemFormContainer').hide();
            $('#editNewItemTitleInput').val('');
            $('#editNewItemDescInput').val('');
        });
    });
</script>
@endpush

@endsection
