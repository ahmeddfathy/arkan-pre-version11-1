<!-- Create Task Modal -->
<div class="modal fade" id="createTaskModal" tabindex="-1" aria-labelledby="createTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createTaskModalLabel">إضافة مهمة جديدة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('tasks.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <div class="form-group">
                                <label for="task_type">نوع المهمة</label>
                                <select class="form-control" id="task_type" name="task_type" required>
                                    <option value="regular" selected>مهمة عادية</option>
                                    <option value="project">مهمة مشروع</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name">اسم المهمة</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6 project-task-fields" style="display: none;">
                            <div class="form-group">
                                <label for="create_project_code">
                                    <i class="fas fa-hashtag"></i> كود المشروع (اختياري)
                                </label>
                                <div class="input-group">
                                    <input type="text"
                                        class="form-control"
                                        id="create_project_code"
                                        list="createProjectCodesList"
                                        placeholder="اكتب كود المشروع..."
                                        autocomplete="off"
                                        style="font-family: 'Courier New', monospace; font-weight: 600; text-transform: uppercase; color: #3b82f6;">
                                    <button class="btn btn-outline-secondary"
                                        type="button"
                                        id="clear_create_project_code"
                                        style="display: none;"
                                        title="مسح">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <datalist id="createProjectCodesList">
                                    @foreach($projects->whereNotNull('code')->unique('code') as $project)
                                    <option value="{{ $project->code }}" data-project-id="{{ $project->id }}">
                                        {{ $project->name }}
                                    </option>
                                    @endforeach
                                </datalist>
                                <small class="text-muted">أدخل كود المشروع لاختياره تلقائياً</small>
                            </div>
                        </div>
                        <div class="col-md-6 project-task-fields" style="display: none;">
                            <div class="form-group">
                                <label for="project_id">المشروع (اختياري)</label>
                                <select class="form-control" id="project_id" name="project_id">
                                    <option value="">اختر المشروع (اختياري)</option>
                                    @foreach($projects as $project)
                                    <option value="{{ $project->id }}" data-code="{{ $project->code ?? '' }}">
                                        {{ $project->name }}
                                    </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">أو اختر المشروع من القائمة</small>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="{{ $isGraphicOnlyUser ? 'col-md-12' : 'col-md-6' }} project-task-fields" style="display: none;">
                            <div class="form-group">
                                <label for="service_id">الخدمة</label>
                                <select class="form-control" id="service_id" name="service_id">
                                    @if(!$isGraphicOnlyUser)
                                    <option value="">اختر الخدمة</option>
                                    @endif
                                    @foreach($services as $service)
                                    <option value="{{ $service->id }}" data-service-name="{{ $service->name }}"
                                        @if($isGraphicOnlyUser && $loop->first) selected @endif>
                                        {{ $service->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        @if(!$isGraphicOnlyUser)
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="role_filter">تصفية حسب الدور</label>
                                <select class="form-control" id="role_filter">
                                    <option value="">جميع الأدوار</option>
                                    @foreach($roles as $role)
                                    <option value="{{ $role->name }}">{{ $role->name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">اختياري: لعرض الموظفين حسب دور معين</small>
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- نوع المهمة الجرافيكية - يظهر فقط عند اختيار خدمة جرافيك -->
                    <div class="row mt-3" id="graphic_task_type_section" style="display: none;">
                        <div class="col-12">
                            <div class="form-group">
                                <label for="graphic_task_type_id">
                                    <i class="fas fa-palette text-primary"></i>
                                    نوع المهمة الجرافيكية
                                </label>
                                <select class="form-control" id="graphic_task_type_id" name="graphic_task_type_id">
                                    <option value="">اختر نوع المهمة الجرافيكية</option>
                                    @foreach($graphicTaskTypes as $graphicType)
                                    <option value="{{ $graphicType->id }}"
                                        data-points="{{ $graphicType->points }}"
                                        data-min-time="{{ $graphicType->min_minutes }}"
                                        data-max-time="{{ $graphicType->max_minutes }}"
                                        data-avg-time="{{ $graphicType->average_minutes }}">
                                        {{ $graphicType->name }}
                                        ({{ $graphicType->points }} نقطة - {{ $graphicType->average_time_formatted }})
                                    </option>
                                    @endforeach
                                </select>
                                <div class="mt-2">
                                    <small class="text-info" id="graphic_task_info" style="display: none;">
                                        <i class="fas fa-info-circle"></i>
                                        <span id="task_details"></span>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group mt-3">
                        <label for="description">وصف المهمة</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>

                    <!-- ✨ قسم البنود (Task Items) -->
                    <div class="card mt-3 border">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                            <h6 class="mb-0"><i class="fas fa-list-check text-primary"></i> بنود المهمة</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="addTaskItemBtn">
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
                                <p class="text-muted text-center py-2 mb-0 small" id="noItemsMsg">
                                    <i class="fas fa-info-circle"></i> لم يتم إضافة بنود بعد
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- خيار المهمة المرنة -->
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_flexible_time" name="is_flexible_time_checkbox" value="1">
                                <input type="hidden" id="is_flexible_time_hidden" name="is_flexible_time" value="0">
                                <label class="form-check-label" for="is_flexible_time">
                                    <strong>🕐 مهمة مرنة (بدون وقت محدد)</strong>
                                    <small class="text-muted d-block">عند تفعيل هذا الخيار، ستصبح المهمة مرنة ولن تحتاج لتحديد وقت مقدر</small>
                                </label>
                            </div>
                        </div>
                        @php
                        $userMaxLevel = \App\Models\RoleHierarchy::getUserMaxHierarchyLevel(Auth::user());
                        $canMarkAsAdditional = $userMaxLevel && $userMaxLevel >= 3;
                        @endphp
                        @if($canMarkAsAdditional)
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_additional_task" name="is_additional_task_checkbox" value="1">
                                <input type="hidden" id="is_additional_task_hidden" name="is_additional_task" value="0">
                                <label class="form-check-label" for="is_additional_task">
                                    <strong>✅ مهمة إضافية</strong>
                                    <small class="text-muted d-block">حدد إذا كانت هذه المهمة إضافية للموظف</small>
                                </label>
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- حقل تاريخ الاستحقاق - يظهر دائماً -->
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="due_date">تاريخ ووقت الاستحقاق</label>
                                <input type="datetime-local" class="form-control" id="due_date" name="due_date">
                                <small class="text-muted">اختر التاريخ والوقت لاستحقاق المهمة</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="points">
                                    <i class="fas fa-star text-warning"></i>
                                    النقاط
                                </label>
                                <input type="number" class="form-control" id="points" name="points" min="0" max="1000" value="10" placeholder="نقاط المهمة">
                                <small class="text-muted">النقاط التي يحصل عليها المنفذ</small>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3" id="time_estimation_section">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="estimated_hours">الوقت المقدر (ساعات)</label>
                                <input type="number" class="form-control" id="estimated_hours" name="estimated_hours" min="0" value="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="estimated_minutes">الوقت المقدر (دقائق)</label>
                                <input type="number" class="form-control" id="estimated_minutes" name="estimated_minutes" min="0" max="59" value="0">
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="status">الحالة</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="new" selected>جديدة</option>
                                </select>
                                <small class="text-muted">المهام الجديدة تبدأ بحالة "جديدة" دائماً</small>
                            </div>
                        </div>
                    </div>
                    <div class="form-group mt-3">
                        <label>تعيين الموظف</label>
                        <div class="row" id="assignUsersContainer">
                            <div class="col-12 mb-2 user-assignment-row">
                                <input type="text"
                                    class="form-control user-select"
                                    id="user_name_input"
                                    list="usersList"
                                    placeholder="اكتب أو اختر موظف..."
                                    autocomplete="off">
                                <input type="hidden" id="user_id_hidden" name="users[]" value="">
                                <datalist id="usersList">
                                    @foreach($users as $user)
                                    <option value="{{ $user->name }}" data-user-id="{{ $user->id }}">
                                        {{ $user->name }}
                                    </option>
                                    @endforeach
                                </datalist>
                            </div>
                        </div>
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
