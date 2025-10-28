@extends('layouts.app')

@section('title', 'إدارة توقيف المشاريع')

@push('styles')
<style>
.pause-page-container {
    padding: 2rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
}

.pause-header {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.pause-header h1 {
    color: #667eea;
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.pause-header p {
    color: #6b7280;
    margin: 0;
}

/* إحصائيات */
.stats-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.stat-card.total {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.stat-card.reason {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
}

.stat-number {
    font-size: 2.5rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
}

.stat-label {
    font-size: 0.9rem;
    opacity: 0.9;
}

/* الفلاتر */
.filters-section {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.filters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.filter-label {
    font-weight: 600;
    color: #374151;
    font-size: 0.9rem;
}

.filter-input {
    padding: 0.75rem;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    font-size: 0.9rem;
    transition: border-color 0.3s ease;
}

.filter-input:focus {
    outline: none;
    border-color: #667eea;
}

.filter-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
}

.btn-search, .btn-clear, .btn-pause-selected {
    padding: 0.75rem 2rem;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-search {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-search:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.btn-clear {
    background: #f3f4f6;
    color: #374151;
}

.btn-clear:hover {
    background: #e5e7eb;
}

.btn-pause-selected {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
}

.btn-pause-selected:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(245, 87, 108, 0.4);
}

/* جدول المشاريع */
.projects-table-container {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    overflow: hidden;
}

.table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.table-header h2 {
    color: #374151;
    font-size: 1.5rem;
    font-weight: bold;
    margin: 0;
}

.projects-table {
    width: 100%;
    border-collapse: collapse;
}

.projects-table thead {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.projects-table th {
    padding: 1rem;
    text-align: right;
    font-weight: 600;
}

.projects-table tbody tr {
    border-bottom: 1px solid #e5e7eb;
    transition: background-color 0.3s ease;
}

.projects-table tbody tr:hover {
    background-color: #f9fafb;
}

.projects-table td {
    padding: 1rem;
}

.status-badge {
    display: inline-block;
    padding: 0.4rem 1rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}

.status-badge.جديد {
    background: #dbeafe;
    color: #1e40af;
}

.status-badge.جاري {
    background: #fef3c7;
    color: #92400e;
}

.status-badge.مكتمل {
    background: #d1fae5;
    color: #065f46;
}

.status-badge.موقوف {
    background: #fee2e2;
    color: #991b1b;
}

.status-badge.ملغي {
    background: #f3f4f6;
    color: #6b7280;
}

.pause-reason-badge {
    display: inline-block;
    padding: 0.3rem 0.8rem;
    border-radius: 15px;
    font-size: 0.8rem;
    background: #fef3c7;
    color: #92400e;
    margin-top: 0.25rem;
}

.btn-action {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    margin: 0 0.25rem;
}

.btn-pause {
    background: #fecaca;
    color: #991b1b;
}

.btn-pause:hover {
    background: #fee2e2;
    transform: translateY(-2px);
}

.btn-resume {
    background: #bbf7d0;
    color: #166534;
}

.btn-resume:hover {
    background: #d1fae5;
    transform: translateY(-2px);
}

.btn-view {
    background: #dbeafe;
    color: #1e40af;
}

.btn-view:hover {
    background: #bfdbfe;
    transform: translateY(-2px);
}

/* Modal */
.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal-overlay.active {
    display: flex;
}

.modal-content {
    background: white;
    border-radius: 20px;
    padding: 2rem;
    max-width: 500px;
    width: 90%;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    animation: modalSlideIn 0.3s ease;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.modal-header {
    margin-bottom: 1.5rem;
}

.modal-header h3 {
    color: #374151;
    font-size: 1.5rem;
    font-weight: bold;
    margin: 0;
}

.modal-body {
    margin-bottom: 1.5rem;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.5rem;
}

.form-group select,
.form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    font-size: 0.9rem;
}

.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #667eea;
}

.modal-footer {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
}

.btn-modal-cancel {
    padding: 0.75rem 1.5rem;
    background: #f3f4f6;
    color: #374151;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
}

.btn-modal-confirm {
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
}

.checkbox-container {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}

.checkbox-container input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: #9ca3af;
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.empty-state h3 {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
}

/* Alerts */
.alert {
    padding: 1rem 1.5rem;
    border-radius: 10px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.alert-success {
    background: #d1fae5;
    color: #065f46;
}

.alert-error {
    background: #fee2e2;
    color: #991b1b;
}

/* Tabs */
.tabs-container {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.tab-btn {
    padding: 1rem 2rem;
    border: none;
    background: #f3f4f6;
    color: #374151;
    border-radius: 10px 10px 0 0;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.tab-btn:hover {
    background: #e5e7eb;
}

.tab-btn.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

/* Date Badges */
.date-badge {
    display: inline-block;
    padding: 0.4rem 0.8rem;
    border-radius: 15px;
    font-size: 0.85rem;
    font-weight: 600;
    background: #dbeafe;
    color: #1e40af;
}

.date-badge.overdue {
    background: #fee2e2;
    color: #991b1b;
}

.date-badge.no-date {
    background: #f3f4f6;
    color: #9ca3af;
}

.date-badge i {
    margin-left: 0.25rem;
}

/* Duration Badge */
.duration-badge {
    display: inline-block;
    padding: 0.4rem 0.8rem;
    border-radius: 15px;
    font-size: 0.85rem;
    font-weight: 600;
    background: #fef3c7;
    color: #92400e;
}

.duration-badge i {
    margin-left: 0.25rem;
}
</style>
@endpush

@section('content')
<div class="pause-page-container">
    <!-- Header -->
    <div class="pause-header">
        <h1>
            <i class="fas fa-pause-circle"></i>
            إدارة توقيف المشاريع
        </h1>
        <p>بحث وتوقيف واستئناف المشاريع</p>
    </div>

    <!-- Alerts -->
    @if(session('success'))
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            {{ session('error') }}
        </div>
    @endif

    <!-- Statistics -->
    <div class="stats-container">
        <div class="stat-card total">
            <div class="stat-number">{{ $stats['total_paused'] ?? 0 }}</div>
            <div class="stat-label">مشروع موقوف حالياً</div>
        </div>

        @foreach(($stats['by_reason'] ?? []) as $key => $data)
        <div class="stat-card reason">
            <div class="stat-number">{{ $data['count'] }}</div>
            <div class="stat-label">{{ $data['label'] }}</div>
        </div>
        @endforeach
    </div>

    <!-- Filters -->
    <div class="filters-section">
        <form method="GET" action="{{ route('projects.pause.index') }}" id="filtersForm">
            <div class="filters-grid">
                <div class="filter-group">
                    <label class="filter-label">
                        <i class="fas fa-search"></i>
                        بحث بالاسم أو الكود
                    </label>
                    <input type="text" name="search" class="filter-input"
                           placeholder="ابحث..." value="{{ $filters['search'] ?? '' }}">
                </div>

                <div class="filter-group">
                    <label class="filter-label">
                        <i class="fas fa-flag"></i>
                        الحالة
                    </label>
                    <select name="status" class="filter-input">
                        <option value="">جميع الحالات</option>
                        <option value="جديد" {{ ($filters['status'] ?? '') == 'جديد' ? 'selected' : '' }}>جديد</option>
                        <option value="جاري التنفيذ" {{ ($filters['status'] ?? '') == 'جاري التنفيذ' ? 'selected' : '' }}>جاري التنفيذ</option>
                        <option value="مكتمل" {{ ($filters['status'] ?? '') == 'مكتمل' ? 'selected' : '' }}>مكتمل</option>
                        <option value="موقوف" {{ ($filters['status'] ?? '') == 'موقوف' ? 'selected' : '' }}>موقوف</option>
                        <option value="ملغي" {{ ($filters['status'] ?? '') == 'ملغي' ? 'selected' : '' }}>ملغي</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">
                        <i class="fas fa-user"></i>
                        العميل
                    </label>
                    <select name="client_id" class="filter-input">
                        <option value="">جميع العملاء</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" {{ ($filters['client_id'] ?? '') == $client->id ? 'selected' : '' }}>
                                {{ $client->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">
                        <i class="fas fa-pause"></i>
                        سبب التوقيف
                    </label>
                    <select name="pause_reason" class="filter-input">
                        <option value="">جميع الأسباب</option>
                        @foreach($pauseReasons as $key => $label)
                            <option value="{{ $key }}" {{ ($filters['pause_reason'] ?? '') == $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">
                        <i class="fas fa-calendar-alt"></i>
                        تسليم العميل من
                    </label>
                    <input type="date" name="client_date_from" class="filter-input"
                           value="{{ $filters['client_date_from'] ?? '' }}">
                </div>

                <div class="filter-group">
                    <label class="filter-label">
                        <i class="fas fa-calendar-alt"></i>
                        تسليم العميل إلى
                    </label>
                    <input type="date" name="client_date_to" class="filter-input"
                           value="{{ $filters['client_date_to'] ?? '' }}">
                </div>

                <div class="filter-group">
                    <label class="filter-label">
                        <i class="fas fa-calendar-check"></i>
                        تسليم الفريق من
                    </label>
                    <input type="date" name="team_date_from" class="filter-input"
                           value="{{ $filters['team_date_from'] ?? '' }}">
                </div>

                <div class="filter-group">
                    <label class="filter-label">
                        <i class="fas fa-calendar-check"></i>
                        تسليم الفريق إلى
                    </label>
                    <input type="date" name="team_date_to" class="filter-input"
                           value="{{ $filters['team_date_to'] ?? '' }}">
                </div>
            </div>

            <div class="filter-actions">
                <button type="submit" class="btn-search">
                    <i class="fas fa-search"></i>
                    بحث
                </button>
                <a href="{{ route('projects.pause.index') }}" class="btn-clear">
                    <i class="fas fa-times"></i>
                    مسح الفلاتر
                </a>
                <button type="button" class="btn-pause-selected" onclick="openPauseMultipleModal()" id="pauseSelectedBtn" style="display: none;">
                    <i class="fas fa-pause-circle"></i>
                    توقيف المحدد
                </button>
            </div>
        </form>
    </div>

    <!-- Projects Table with Tabs -->
    <div class="projects-table-container">
        <div class="table-header">
            <div class="tabs-container">
                <button class="tab-btn active" data-tab="all">
                    <i class="fas fa-list"></i>
                    كل المشاريع ({{ $projects->total() }})
                </button>
                <button class="tab-btn" data-tab="paused">
                    <i class="fas fa-pause-circle"></i>
                    المشاريع الموقوفة ({{ $stats['total_paused'] ?? 0 }})
                </button>
            </div>
        </div>

        <!-- Tab: All Projects -->
        <div class="tab-content active" id="tab-all">
            @if($projects->count() > 0)
            <table class="projects-table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll" onchange="toggleSelectAll()"></th>
                        <th>الكود</th>
                        <th>اسم المشروع</th>
                        <th>العميل</th>
                        <th>الحالة</th>
                        <th>تسليم العميل</th>
                        <th>تسليم الفريق</th>
                        <th>المدير</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($projects as $project)
                    <tr>
                        <td>
                            @if($project->status !== 'موقوف')
                                <input type="checkbox" class="project-checkbox" value="{{ $project->id }}"
                                       onchange="updatePauseButton()">
                            @endif
                        </td>
                        <td><strong>{{ $project->code ?? '-' }}</strong></td>
                        <td>
                            <div>{{ $project->name }}</div>
                            @if($project->isPaused() && $project->activePause)
                                <div class="pause-reason-badge">
                                    <i class="fas fa-pause"></i>
                                    {{ $project->activePause->pause_reason }}
                                </div>
                            @endif
                        </td>
                        <td>{{ $project->client->name ?? '-' }}</td>
                        <td>
                            <span class="status-badge {{ $project->status }}">
                                {{ $project->status }}
                            </span>
                        </td>
                        <td>
                            @if($project->client_agreed_delivery_date)
                                <span class="date-badge {{ \Carbon\Carbon::parse($project->client_agreed_delivery_date)->isPast() && $project->status !== 'مكتمل' ? 'overdue' : '' }}">
                                    <i class="fas fa-calendar-alt"></i>
                                    {{ \Carbon\Carbon::parse($project->client_agreed_delivery_date)->format('Y-m-d') }}
                                </span>
                            @else
                                <span class="date-badge no-date">غير محدد</span>
                            @endif
                        </td>
                        <td>
                            @if($project->team_delivery_date)
                                <span class="date-badge {{ \Carbon\Carbon::parse($project->team_delivery_date)->isPast() && $project->status !== 'مكتمل' ? 'overdue' : '' }}">
                                    <i class="fas fa-calendar-check"></i>
                                    {{ \Carbon\Carbon::parse($project->team_delivery_date)->format('Y-m-d') }}
                                </span>
                            @else
                                <span class="date-badge no-date">غير محدد</span>
                            @endif
                        </td>
                        <td>{{ $project->manager ?? '-' }}</td>
                        <td>
                            <a href="{{ route('projects.show', $project->id) }}" class="btn-action btn-view">
                                <i class="fas fa-eye"></i>
                                عرض
                            </a>

                            @if($project->status === 'موقوف')
                                <button class="btn-action btn-resume" data-project-id="{{ $project->id }}">
                                    <i class="fas fa-play"></i>
                                    استئناف
                                </button>
                            @else
                                <button class="btn-action btn-pause" data-project-id="{{ $project->id }}" data-project-name="{{ $project->name }}">
                                    <i class="fas fa-pause"></i>
                                    توقيف
                                </button>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Pagination -->
            <div style="margin-top: 2rem; display: flex; justify-content: center;">
                {{ $projects->withQueryString()->links() }}
            </div>
            @else
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>لا توجد مشاريع</h3>
                <p>لم يتم العثور على مشاريع مطابقة للفلاتر المحددة</p>
            </div>
            @endif
        </div>

        <!-- Tab: Paused Projects -->
        <div class="tab-content" id="tab-paused">
            @php
                $pausedProjects = $projects->where('status', 'موقوف');
            @endphp

            @if($pausedProjects->count() > 0)
            <table class="projects-table">
                <thead>
                    <tr>
                        <th>الكود</th>
                        <th>اسم المشروع</th>
                        <th>العميل</th>
                        <th>سبب التوقيف</th>
                        <th>تاريخ التوقيف</th>
                        <th>تسليم العميل</th>
                        <th>تسليم الفريق</th>
                        <th>مدة التوقيف</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pausedProjects as $project)
                    <tr>
                        <td><strong>{{ $project->code ?? '-' }}</strong></td>
                        <td>{{ $project->name }}</td>
                        <td>{{ $project->client->name ?? '-' }}</td>
                        <td>
                            @if($project->activePause)
                                <span class="pause-reason-badge">
                                    <i class="fas fa-pause"></i>
                                    {{ $project->activePause->pause_reason }}
                                </span>
                                @if($project->activePause->pause_notes)
                                    <div style="font-size: 0.85rem; color: #6b7280; margin-top: 0.25rem;">
                                        {{ \Str::limit($project->activePause->pause_notes, 50) }}
                                    </div>
                                @endif
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @if($project->activePause)
                                <span class="date-badge">
                                    <i class="fas fa-clock"></i>
                                    {{ $project->activePause->paused_at->format('Y-m-d H:i') }}
                                </span>
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @if($project->client_agreed_delivery_date)
                                <span class="date-badge overdue">
                                    <i class="fas fa-calendar-alt"></i>
                                    {{ \Carbon\Carbon::parse($project->client_agreed_delivery_date)->format('Y-m-d') }}
                                </span>
                            @else
                                <span class="date-badge no-date">غير محدد</span>
                            @endif
                        </td>
                        <td>
                            @if($project->team_delivery_date)
                                <span class="date-badge overdue">
                                    <i class="fas fa-calendar-check"></i>
                                    {{ \Carbon\Carbon::parse($project->team_delivery_date)->format('Y-m-d') }}
                                </span>
                            @else
                                <span class="date-badge no-date">غير محدد</span>
                            @endif
                        </td>
                        <td>
                            @if($project->activePause)
                                <span class="duration-badge">
                                    <i class="fas fa-hourglass-half"></i>
                                    @php
                                        $durationText = $project->activePause->duration_short ?? 'غير محدد';
                                        // تأكد من أنها نص وليس رقم عشري
                                        if (is_numeric($durationText) && $durationText < 1) {
                                            $durationText = 'أقل من يوم';
                                        }
                                    @endphp
                                    {{ $durationText }}
                                </span>
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('projects.show', $project->id) }}" class="btn-action btn-view">
                                <i class="fas fa-eye"></i>
                                عرض
                            </a>

                            <button class="btn-action btn-resume" data-project-id="{{ $project->id }}">
                                <i class="fas fa-play"></i>
                                استئناف
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="empty-state">
                <i class="fas fa-check-circle"></i>
                <h3>لا توجد مشاريع موقوفة</h3>
                <p>جميع المشاريع تعمل بشكل طبيعي</p>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Pause Modal -->
<div class="modal-overlay" id="pauseModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-pause-circle"></i> توقيف مشروع</h3>
        </div>
        <form id="pauseForm" method="POST">
            @csrf
            <div class="modal-body">
                <p id="pauseProjectName" style="margin-bottom: 1rem; font-weight: bold;"></p>

                <div class="form-group">
                    <label for="pause_reason">سبب التوقيف *</label>
                    <select name="pause_reason" id="pause_reason" required>
                        <option value="">اختر السبب...</option>
                        @foreach($pauseReasons as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="pause_notes">ملاحظات</label>
                    <textarea name="pause_notes" id="pause_notes" rows="4"
                              placeholder="أضف ملاحظات إضافية..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal-cancel" onclick="closePauseModal()">إلغاء</button>
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
            <h3><i class="fas fa-pause-circle"></i> توقيف مشاريع متعددة</h3>
        </div>
        <form id="pauseMultipleForm" method="POST" action="{{ route('projects.pause.multiple') }}">
            @csrf
            <div class="modal-body">
                <p id="selectedProjectsCount" style="margin-bottom: 1rem; font-weight: bold;"></p>

                <div class="form-group">
                    <label for="pause_reason_multiple">سبب التوقيف *</label>
                    <select name="pause_reason" id="pause_reason_multiple" required>
                        <option value="">اختر السبب...</option>
                        @foreach($pauseReasons as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="pause_notes_multiple">ملاحظات</label>
                    <textarea name="pause_notes" id="pause_notes_multiple" rows="4"
                              placeholder="أضف ملاحظات إضافية..."></textarea>
                </div>

                <input type="hidden" name="project_ids" id="project_ids_input">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal-cancel" onclick="closePauseMultipleModal()">إلغاء</button>
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
    const checkboxes = document.querySelectorAll('.project-checkbox');
    checkboxes.forEach(cb => cb.checked = selectAll.checked);
    updatePauseButton();
}

// Update Pause Button Visibility
function updatePauseButton() {
    const checkboxes = document.querySelectorAll('.project-checkbox:checked');
    const pauseBtn = document.getElementById('pauseSelectedBtn');

    if (checkboxes.length > 0) {
        pauseBtn.style.display = 'flex';
    } else {
        pauseBtn.style.display = 'none';
    }
}

// Open Pause Multiple Modal
function openPauseMultipleModal() {
    const checkboxes = document.querySelectorAll('.project-checkbox:checked');
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

// Tabs functionality
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const tab = this.dataset.tab;

        // Remove active class from all tabs
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

        // Add active class to clicked tab
        this.classList.add('active');
        document.getElementById('tab-' + tab).classList.add('active');
    });
});
</script>
@endpush

