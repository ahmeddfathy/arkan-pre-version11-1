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
                @foreach($projects->whereNotNull('code')->unique('code') as $project)
                    <option value="{{ $project->code }}">{{ $project->name }}</option>
                @endforeach
            </datalist>
        </div>
    </div>
    <div class="col-md-2">
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
            <label for="serviceFilter">الخدمة</label>
            <select class="form-control" id="serviceFilter">
                <option value="">جميع الخدمات</option>
                @foreach($services as $service)
                    <option value="{{ $service->id }}">{{ $service->name }}</option>
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
            <label for="createdByFilter">المنشئ</label>
            <select class="form-control" id="createdByFilter">
                <option value="">الكل</option>
                @foreach($taskCreators as $creator)
                    <option value="{{ $creator->id }}">{{ $creator->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-2">
        <div class="form-group">
            <label for="dateTypeFilter">⚙️ نوع التاريخ</label>
            <select class="form-control" id="dateTypeFilter">
                <option value="due_date">📅 الموعد النهائي</option>
                <option value="created_at">🆕 تاريخ الإنشاء</option>
            </select>
        </div>
    </div>
    <div class="col-md-2">
        <div class="form-group">
            <label for="dateFromFilter" id="dateFromLabel">📅 من تاريخ</label>
            <input type="date" class="form-control" id="dateFromFilter">
        </div>
    </div>
    <div class="col-md-2">
        <div class="form-group">
            <label for="dateToFilter" id="dateToLabel">📅 إلى تاريخ</label>
            <input type="date" class="form-control" id="dateToFilter">
        </div>
    </div>
    <div class="col-md-2">
        <div class="form-group">
            <label for="searchInput">بحث</label>
            <input type="text" class="form-control" id="searchInput" placeholder="ابحث...">
        </div>
    </div>
</div>

<!-- صف إضافي للفلاتر الجديدة -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="form-group">
            <label for="assignedUserFilter">
                <i class="fas fa-user"></i> فلتر حسب الموظف
            </label>
            <select class="form-control" id="assignedUserFilter">
                <option value="">جميع الموظفين</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>
            <small class="text-muted">عرض مهام موظف معين فقط</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label>&nbsp;</label>
            <button type="button" class="btn btn-primary btn-block" id="myCreatedTasksBtn">
                <i class="fas fa-user-plus"></i> مهامي التي أضفتها
            </button>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>&nbsp;</label>
            <button type="button" class="btn btn-secondary btn-block" id="clearFiltersBtn">
                <i class="fas fa-times"></i> مسح جميع الفلاتر
            </button>
        </div>
    </div>
</div>
