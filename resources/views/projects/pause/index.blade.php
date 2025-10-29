@extends('layouts.app')

@section('title', 'إدارة توقيف المشاريع')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/projects-pause.css') }}?v={{ time() }}">
@endpush

@section('content')
<div class="pause-container">
    <!-- Header Section -->
    <div class="pause-page-header slide-up">
        <i class="fas fa-pause-circle header-icon"></i>
        <h1>
            <i class="fas fa-pause-circle"></i>
            إدارة توقيف المشاريع
        </h1>
        <p>متابعة وإدارة حالات توقيف المشاريع وأسبابها مع إمكانية الاستئناف</p>
    </div>

    <!-- Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade-in">
            <i class="fas fa-check-circle"></i>
            <span>{{ session('success') }}</span>
            <button type="button" class="btn-close" onclick="this.parentElement.remove()">×</button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade-in">
            <i class="fas fa-exclamation-circle"></i>
            <span>{{ session('error') }}</span>
            <button type="button" class="btn-close" onclick="this.parentElement.remove()">×</button>
        </div>
    @endif

    <!-- Statistics Cards -->
    <div class="row stats-row fade-in">
        <div class="col-md-3 mb-3">
            <div class="stat-card bg-gradient-primary">
                <div class="stat-icon">
                    <i class="fas fa-project-diagram"></i>
                </div>
                <div class="stat-details">
                    <h3>{{ $projects->count() }}</h3>
                    <p>إجمالي المشاريع</p>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="stat-card bg-gradient-danger">
                <div class="stat-icon">
                    <i class="fas fa-pause-circle"></i>
                </div>
                <div class="stat-details">
                    <h3>{{ $projects->where('status', 'موقوف')->count() }}</h3>
                    <p>مشاريع موقوفة</p>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="stat-card bg-gradient-success">
                <div class="stat-icon">
                    <i class="fas fa-play-circle"></i>
                </div>
                <div class="stat-details">
                    <h3>{{ $projects->whereIn('status', ['جديد', 'جاري'])->count() }}</h3>
                    <p>مشاريع نشطة</p>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="stat-card bg-gradient-info">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-details">
                    <h3>{{ $projects->where('status', 'مكتمل')->count() }}</h3>
                    <p>مشاريع مكتملة</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="filters-container slide-up">
        <div class="filters-header">
            <h3>
                <i class="fas fa-filter"></i>
                فلترة المشاريع
            </h3>
        </div>

        <form method="GET" action="{{ route('projects.pause.index') }}">
            <div class="filters-grid">
                <div class="filter-group">
                    <label>
                        <i class="fas fa-search"></i>
                        بحث
                    </label>
                    <input type="text"
                           name="search"
                           class="filter-input"
                           placeholder="كود أو اسم المشروع..."
                           value="{{ request('search') }}">
                </div>

                <div class="filter-group">
                    <label>
                        <i class="fas fa-tasks"></i>
                        الحالة
                    </label>
                    <select name="status" class="filter-select">
                        <option value="">الكل</option>
                        <option value="جديد" {{ request('status') == 'جديد' ? 'selected' : '' }}>جديد</option>
                        <option value="جاري" {{ request('status') == 'جاري' ? 'selected' : '' }}>جاري</option>
                        <option value="موقوف" {{ request('status') == 'موقوف' ? 'selected' : '' }}>موقوف</option>
                        <option value="مكتمل" {{ request('status') == 'مكتمل' ? 'selected' : '' }}>مكتمل</option>
                        <option value="ملغي" {{ request('status') == 'ملغي' ? 'selected' : '' }}>ملغي</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label>
                        <i class="fas fa-exclamation-triangle"></i>
                        سبب التوقيف
                    </label>
                    <select name="pause_reason" class="filter-select">
                        <option value="">الكل</option>
                        @foreach($pauseReasons as $key => $label)
                            <option value="{{ $key }}" {{ request('pause_reason') == $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-group">
                    <label>
                        <i class="fas fa-user-tie"></i>
                        العميل
                    </label>
                    <input type="text"
                           name="client"
                           class="filter-input"
                           placeholder="اسم العميل..."
                           value="{{ request('client') }}">
                </div>
            </div>

            <div class="filter-actions">
                @if(request()->hasAny(['search', 'status', 'pause_reason', 'client']))
                    <a href="{{ route('projects.pause.index') }}" class="btn-filter-reset">
                        <i class="fas fa-undo"></i>
                        إعادة تعيين
                    </a>
                @endif
                <button type="submit" class="btn-filter-search">
                    <i class="fas fa-search"></i>
                    بحث
                </button>
                <button type="button"
                        class="btn-pause-selected"
                        id="pauseSelectedBtn"
                        onclick="openPauseMultipleModal()"
                        style="display: none;">
                    <i class="fas fa-pause-circle"></i>
                    توقيف المحددة
                </button>
            </div>
        </form>
    </div>

    <!-- Projects Table -->
    <div class="projects-table-container slide-up">
        <div class="table-header">
            <h3>
                <i class="fas fa-list"></i>
                قائمة المشاريع
                <span style="font-size: 0.9rem; color: #6c757d; font-weight: normal;">
                    ({{ $projects->count() }} مشروع)
                </span>
            </h3>
            <div>
                <a href="{{ route('projects.pause.stats') }}" class="btn-filter-reset">
                    <i class="fas fa-chart-bar"></i>
                    عرض الإحصائيات
                </a>
            </div>
        </div>

        @if($projects->count() > 0)
            <div class="table-responsive">
                <table class="projects-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">
                                <input type="checkbox"
                                       id="selectAll"
                                       class="project-checkbox"
                                       onchange="toggleSelectAll()">
                            </th>
                            <th><i class="fas fa-qrcode"></i> الكود</th>
                            <th><i class="fas fa-project-diagram"></i> اسم المشروع</th>
                            <th><i class="fas fa-user-tie"></i> العميل</th>
                            <th><i class="fas fa-tasks"></i> الحالة</th>
                            <th><i class="fas fa-calendar-alt"></i> تسليم العميل</th>
                            <th><i class="fas fa-calendar-check"></i> تسليم الفريق</th>
                            <th><i class="fas fa-cogs"></i> الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($projects as $project)
                        <tr>
                            <td>
                                @if($project->status != 'موقوف' && $project->status != 'مكتمل' && $project->status != 'ملغي')
                                    <input type="checkbox"
                                           class="project-checkbox"
                                           value="{{ $project->id }}"
                                           onchange="updatePauseButton()">
                                @endif
                            </td>
                            <td>
                                <strong style="color: #007bff;">{{ $project->code ?? '-' }}</strong>
                            </td>
                            <td class="project-name-cell">
                                {{ $project->name }}
                                @if($project->activePause && $project->status == 'موقوف')
                                    <div class="pause-reason-badge">
                                        <i class="fas fa-pause"></i>
                                        {{ $project->activePause->pause_reason }}
                                    </div>
                                @endif
                            </td>
                            <td>{{ $project->client->name ?? '-' }}</td>
                            <td>
                                <span class="status-badge {{ $project->status }}">
                                    @if($project->status == 'جديد')
                                        <i class="fas fa-plus-circle"></i>
                                    @elseif($project->status == 'جاري')
                                        <i class="fas fa-spinner"></i>
                                    @elseif($project->status == 'مكتمل')
                                        <i class="fas fa-check-circle"></i>
                                    @elseif($project->status == 'موقوف')
                                        <i class="fas fa-pause-circle"></i>
                                    @elseif($project->status == 'ملغي')
                                        <i class="fas fa-times-circle"></i>
                                    @endif
                                    {{ $project->status }}
                                </span>
                            </td>
                            <td>
                                @if($project->client_agreed_delivery_date)
                                    <div style="font-size: 0.9rem;">
                                        <i class="fas fa-calendar-alt" style="color: #dc3545;"></i>
                                        {{ \Carbon\Carbon::parse($project->client_agreed_delivery_date)->format('Y-m-d') }}
                                    </div>
                                @else
                                    <span style="color: #6c757d;">غير محدد</span>
                                @endif
                            </td>
                            <td>
                                @if($project->team_delivery_date)
                                    <div style="font-size: 0.9rem;">
                                        <i class="fas fa-calendar-check" style="color: #28a745;"></i>
                                        {{ \Carbon\Carbon::parse($project->team_delivery_date)->format('Y-m-d') }}
                                    </div>
                                @else
                                    <span style="color: #6c757d;">غير محدد</span>
                                @endif
                            </td>
                            <td class="actions-cell">
                                <a href="{{ route('projects.show', $project->id) }}" class="btn-action btn-view">
                                    <i class="fas fa-eye"></i>
                                </a>

                                @if($project->status == 'موقوف')
                                    <button class="btn-action btn-resume"
                                            data-project-id="{{ $project->id }}">
                                        <i class="fas fa-play"></i>
                                    </button>
                                @elseif($project->status != 'مكتمل' && $project->status != 'ملغي')
                                    <button class="btn-action btn-pause"
                                            data-project-id="{{ $project->id }}"
                                            data-project-name="{{ $project->name }}">
                                        <i class="fas fa-pause"></i>
                                    </button>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if(method_exists($projects, 'links'))
                <div class="pagination-container">
                    {{ $projects->links() }}
                </div>
            @endif
        @else
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-inbox"></i>
                </div>
                <h3>لا توجد مشاريع</h3>
                <p>لم يتم العثور على أي مشاريع تطابق معايير البحث</p>
            </div>
        @endif
    </div>
</div>

<!-- Pause Modal -->
<div class="modal-overlay" id="pauseModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>
                <i class="fas fa-pause-circle"></i>
                توقيف مشروع
            </h3>
        </div>
        <form id="pauseForm" method="POST">
            @csrf
            <div class="modal-body">
                <div class="alert alert-warning" style="margin-bottom: 20px;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span id="pauseProjectName" style="font-weight: 600;"></span>
                </div>

                <div class="form-group">
                    <label>
                        <i class="fas fa-exclamation-triangle"></i>
                        سبب التوقيف *
                    </label>
                    <select name="pause_reason" id="pause_reason" class="form-control" required>
                        <option value="">اختر السبب...</option>
                        @foreach($pauseReasons as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label>
                        <i class="fas fa-comment-alt"></i>
                        ملاحظات إضافية
                    </label>
                    <textarea name="pause_notes"
                              id="pause_notes"
                              class="form-control"
                              placeholder="أضف أي ملاحظات أو تفاصيل إضافية حول سبب التوقيف..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal-cancel" onclick="closePauseModal()">
                    <i class="fas fa-times"></i>
                    إلغاء
                </button>
                <button type="submit" class="btn-modal-confirm">
                    <i class="fas fa-pause"></i>
                    توقيف المشروع
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Pause Multiple Modal -->
<div class="modal-overlay" id="pauseMultipleModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>
                <i class="fas fa-pause-circle"></i>
                توقيف مشاريع متعددة
            </h3>
        </div>
        <form id="pauseMultipleForm" method="POST" action="{{ route('projects.pause.multiple') }}">
            @csrf
            <div class="modal-body">
                <div class="alert alert-warning" style="margin-bottom: 20px;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span id="selectedProjectsCount" style="font-weight: 600;"></span>
                </div>

                <div class="form-group">
                    <label>
                        <i class="fas fa-exclamation-triangle"></i>
                        سبب التوقيف *
                    </label>
                    <select name="pause_reason" id="pause_reason_multiple" class="form-control" required>
                        <option value="">اختر السبب...</option>
                        @foreach($pauseReasons as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label>
                        <i class="fas fa-comment-alt"></i>
                        ملاحظات إضافية
                    </label>
                    <textarea name="pause_notes"
                              id="pause_notes_multiple"
                              class="form-control"
                              placeholder="أضف أي ملاحظات أو تفاصيل إضافية حول سبب التوقيف..."></textarea>
                </div>

                <input type="hidden" name="project_ids" id="project_ids_input">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal-cancel" onclick="closePauseMultipleModal()">
                    <i class="fas fa-times"></i>
                    إلغاء
                </button>
                <button type="submit" class="btn-modal-confirm">
                    <i class="fas fa-pause"></i>
                    توقيف المشاريع
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Open Pause Modal
function openPauseModal(projectId, projectName) {
    document.getElementById('pauseProjectName').textContent = 'المشروع: ' + projectName;
    document.getElementById('pauseForm').action = '/projects/' + projectId + '/pause';
    document.getElementById('pauseModal').classList.add('active');
}

// Close Pause Modal
function closePauseModal() {
    document.getElementById('pauseModal').classList.remove('active');
    document.getElementById('pauseForm').reset();
}

// Resume Project
function resumeProject(projectId) {
    if (confirm('هل أنت متأكد من استئناف هذا المشروع؟')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/projects/' + projectId + '/resume';

        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        form.appendChild(csrfToken);

        document.body.appendChild(form);
        form.submit();
    }
}

// Select All Checkboxes
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.project-checkbox:not(#selectAll)');
    checkboxes.forEach(cb => cb.checked = selectAll.checked);
    updatePauseButton();
}

// Update Pause Button Visibility
function updatePauseButton() {
    const checkboxes = document.querySelectorAll('.project-checkbox:not(#selectAll):checked');
    const pauseBtn = document.getElementById('pauseSelectedBtn');

    if (checkboxes.length > 0) {
        pauseBtn.style.display = 'flex';
    } else {
        pauseBtn.style.display = 'none';
    }
}

// Open Pause Multiple Modal
function openPauseMultipleModal() {
    const checkboxes = document.querySelectorAll('.project-checkbox:not(#selectAll):checked');
    const projectIds = Array.from(checkboxes).map(cb => cb.value);

    if (projectIds.length === 0) {
        alert('يرجى اختيار مشروع واحد على الأقل');
        return;
    }

    document.getElementById('selectedProjectsCount').textContent =
        'عدد المشاريع المحددة: ' + projectIds.length;
    document.getElementById('project_ids_input').value = JSON.stringify(projectIds);
    document.getElementById('pauseMultipleModal').classList.add('active');
}

// Close Pause Multiple Modal
function closePauseMultipleModal() {
    document.getElementById('pauseMultipleModal').classList.remove('active');
    document.getElementById('pauseMultipleForm').reset();
}

// Close modals on overlay click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.remove('active');
        }
    });
});

// Event delegation for pause buttons
document.addEventListener('click', function(e) {
    // Pause button
    if (e.target.closest('.btn-pause')) {
        const btn = e.target.closest('.btn-pause');
        const projectId = btn.dataset.projectId;
        const projectName = btn.dataset.projectName;
        openPauseModal(projectId, projectName);
    }

    // Resume button
    if (e.target.closest('.btn-resume')) {
        const btn = e.target.closest('.btn-resume');
        const projectId = btn.dataset.projectId;
        resumeProject(projectId);
    }
});

// Auto-hide alerts after 5 seconds
setTimeout(function() {
    document.querySelectorAll('.alert-dismissible').forEach(alert => {
        alert.style.transition = 'opacity 0.5s';
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 500);
    });
}, 5000);
</script>
@endpush
