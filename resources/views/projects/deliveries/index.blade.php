@extends('layouts.app')

@section('title', 'إدارة تسليمات المشاريع')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/projects-services.css') }}">
<style>
/* Tabs */
.tabs-container {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}

.tab-btn {
    padding: 1rem 2rem;
    border: none;
    background: white;
    color: #374151;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.tab-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
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

.delivery-badge {
    display: inline-block;
    padding: 0.3rem 0.8rem;
    border-radius: 15px;
    font-size: 0.8rem;
    margin: 0.25rem;
    font-weight: 600;
}

.delivery-badge.مسودة {
    background: linear-gradient(135deg, #fef3c7, #fde68a);
    color: #92400e;
}

.delivery-badge.نهائي {
    background: linear-gradient(135deg, #d1fae5, #a7f3d0);
    color: #065f46;
}

.date-badge {
    display: inline-block;
    padding: 0.4rem 0.8rem;
    border-radius: 15px;
    font-size: 0.85rem;
    font-weight: 600;
    background: linear-gradient(135deg, #dbeafe, #bfdbfe);
    color: #1e40af;
}

.date-badge.no-date {
    background: #f3f4f6;
    color: #9ca3af;
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
    transition: all 0.3s ease;
}

.btn-modal-cancel:hover {
    background: #e5e7eb;
}

.btn-modal-confirm {
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-modal-confirm:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.btn-deliver {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.btn-deliver:hover {
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

@media (max-width: 768px) {
    .tabs-container {
        flex-direction: column;
    }

    .tab-btn {
        width: 100%;
        justify-content: center;
    }
}
</style>
@endpush

@section('content')
<div class="simple-container">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>🚚 إدارة تسليمات المشاريع (خدمة العملاء)</h1>
            <p>تسليم المشاريع (مسودة ونهائي) ومتابعة حالتها</p>
    </div>

        <!-- Success/Error Messages -->
    @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
            {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

        <!-- Filters Section -->
    <div class="filters-section">
        <form method="GET" action="{{ route('projects.deliveries.index') }}" id="filtersForm">
                <div class="filters-row">
                    <!-- Search Filter -->
                <div class="filter-group">
                        <label for="searchInput" class="filter-label">
                        <i class="fas fa-search"></i>
                        بحث بالاسم أو الكود
                    </label>
                        <input type="text"
                               id="searchInput"
                               name="search"
                               class="filter-select search-input"
                               placeholder="ابحث بالاسم أو الكود..."
                               value="{{ $filters['search'] ?? '' }}"
                               list="projectsList"
                               autocomplete="off">
                        <datalist id="projectsList">
                            @foreach($allProjects as $proj)
                                @if($proj->code)
                                    <option value="{{ $proj->code }}">{{ $proj->code }} - {{ $proj->name }}</option>
                                @endif
                                <option value="{{ $proj->name }}">{{ $proj->name }} @if($proj->code)({{ $proj->code }})@endif</option>
                            @endforeach
                        </datalist>
                </div>

                    <!-- Status Filter -->
                <div class="filter-group">
                        <label for="statusFilter" class="filter-label">
                        <i class="fas fa-flag"></i>
                        الحالة
                    </label>
                        <select id="statusFilter" name="status" class="filter-select" onchange="document.getElementById('filtersForm').submit()">
                        <option value="">جميع الحالات</option>
                        <option value="جديد" {{ ($filters['status'] ?? '') == 'جديد' ? 'selected' : '' }}>جديد</option>
                        <option value="جاري التنفيذ" {{ ($filters['status'] ?? '') == 'جاري التنفيذ' ? 'selected' : '' }}>جاري التنفيذ</option>
                        <option value="مكتمل" {{ ($filters['status'] ?? '') == 'مكتمل' ? 'selected' : '' }}>مكتمل</option>
                    </select>
                </div>

                    <!-- Client Filter -->
                <div class="filter-group">
                        <label for="clientFilter" class="filter-label">
                        <i class="fas fa-user"></i>
                        العميل
                    </label>
                        <select id="clientFilter" name="client_id" class="filter-select" onchange="document.getElementById('filtersForm').submit()">
                        <option value="">جميع العملاء</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" {{ ($filters['client_id'] ?? '') == $client->id ? 'selected' : '' }}>
                                {{ $client->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                    <!-- Delivery Type Filter -->
                <div class="filter-group">
                        <label for="deliveryTypeFilter" class="filter-label">
                        <i class="fas fa-truck"></i>
                        نوع التسليم
                    </label>
                        <select id="deliveryTypeFilter" name="delivery_type" class="filter-select" onchange="document.getElementById('filtersForm').submit()">
                        <option value="">جميع الأنواع</option>
                        @foreach($deliveryTypes as $key => $label)
                            <option value="{{ $key }}" {{ ($filters['delivery_type'] ?? '') == $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                    <!-- Date From Filter -->
                <div class="filter-group">
                        <label for="dateFromFilter" class="filter-label">
                        <i class="fas fa-calendar-alt"></i>
                        تاريخ التسليم من
                    </label>
                        <input type="date"
                               id="dateFromFilter"
                               name="delivery_date_from"
                               class="filter-select"
                           value="{{ $filters['delivery_date_from'] ?? '' }}">
                </div>

                    <!-- Date To Filter -->
                <div class="filter-group">
                        <label for="dateToFilter" class="filter-label">
                        <i class="fas fa-calendar-alt"></i>
                        تاريخ التسليم إلى
                    </label>
                        <input type="date"
                               id="dateToFilter"
                               name="delivery_date_to"
                               class="filter-select"
                           value="{{ $filters['delivery_date_to'] ?? '' }}">
            </div>

                    <!-- Search Button -->
                    <div class="filter-group">
                        <label class="filter-label" style="opacity: 0;">بحث</label>
                        <button type="submit" class="search-btn">
                    <i class="fas fa-search"></i>
                    بحث
                </button>
                    </div>

                    <!-- Clear Filters -->
                    @if(request()->hasAny(['search', 'status', 'client_id', 'delivery_type', 'delivery_date_from', 'delivery_date_to']))
                        <div class="filter-group">
                            <label class="filter-label" style="opacity: 0;">مسح</label>
                            <a href="{{ route('projects.deliveries.index') }}" class="clear-filters-btn">
                    <i class="fas fa-times"></i>
                    مسح الفلاتر
                </a>
                        </div>
                    @endif
            </div>
        </form>
    </div>

        <!-- Statistics Row -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-number">{{ $stats['total_deliveries'] ?? 0 }}</div>
                <div class="stat-label">إجمالي التسليمات</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $stats['draft_deliveries'] ?? 0 }}</div>
                <div class="stat-label">تسليمات مسودة</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $stats['final_deliveries'] ?? 0 }}</div>
                <div class="stat-label">تسليمات نهائية</div>
            </div>
        </div>

        <!-- Projects Table Container -->
    <div class="projects-table-container">
        <div class="table-header">
            <div class="tabs-container">
                <button class="tab-btn active" data-tab="all">
                    <i class="fas fa-list"></i>
                    كل المشاريع ({{ $projects->total() }})
                </button>
                <button class="tab-btn" data-tab="draft">
                    <i class="fas fa-file"></i>
                    المسودات ({{ $stats['draft_deliveries'] ?? 0 }})
                </button>
                <button class="tab-btn" data-tab="final">
                    <i class="fas fa-check-double"></i>
                    النهائي ({{ $stats['final_deliveries'] ?? 0 }})
                </button>
            </div>
        </div>

        <!-- Tab: All Projects -->
        <div class="tab-content active" id="tab-all">
            @if($projects->count() > 0)
            <table class="projects-table">
                <thead>
                    <tr>
                            <th>المشروع</th>
                        <th>العميل</th>
                        <th>الحالة</th>
                        <th>آخر تسليم مسودة</th>
                        <th>آخر تسليم نهائي</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($projects as $project)
                        <tr class="project-row">
                            <td>
                                <div class="project-info">
                                    <div class="project-avatar">
                                        <i class="fas fa-project-diagram"></i>
                                    </div>
                                    <div class="project-details">
                                        @if($project->code)
                                            <div class="project-code-display">{{ $project->code }}</div>
                                        @endif
                                        <h4>{{ $project->name }}</h4>
                                        @if($project->description)
                                            <p>{{ Str::limit($project->description, 50) }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="client-info">
                                    {{ $project->client->name ?? 'غير محدد' }}
                                </div>
                            </td>
                        <td>
                            <span class="status-badge {{ $project->status }}">
                                {{ $project->status }}
                            </span>
                        </td>
                        <td>
                            @if($project->lastDraftDelivery)
                                <div class="delivery-badge مسودة">
                                    <i class="fas fa-file"></i>
                                    {{ $project->lastDraftDelivery->delivery_date->format('Y-m-d') }}
                                </div>
                                    <div style="font-size: 0.75rem; color: #6b7280; margin-top: 4px;">
                                    بواسطة: {{ $project->lastDraftDelivery->deliveredBy->name ?? '-' }}
                                </div>
                            @else
                                <span class="date-badge no-date">لم يسلم</span>
                            @endif
                        </td>
                        <td>
                            @if($project->lastFinalDelivery)
                                <div class="delivery-badge نهائي">
                                    <i class="fas fa-check-double"></i>
                                    {{ $project->lastFinalDelivery->delivery_date->format('Y-m-d') }}
                                </div>
                                    <div style="font-size: 0.75rem; color: #6b7280; margin-top: 4px;">
                                    بواسطة: {{ $project->lastFinalDelivery->deliveredBy->name ?? '-' }}
                                </div>
                            @else
                                <span class="date-badge no-date">لم يسلم</span>
                            @endif
                        </td>
                        <td>
                                <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
                                    <button class="services-btn btn-view-services"
                                            data-project-id="{{ $project->id }}"
                                            data-project-name="{{ $project->name }}"
                                            data-project-code="{{ $project->code }}"
                                            style="background: linear-gradient(135deg, #3b82f6, #2563eb);"
                                            title="عرض خدمات المشروع">
                                <i class="fas fa-eye"></i>
                                عرض
                                    </button>

                                    <button class="services-btn btn-deliver"
                                            data-project-id="{{ $project->id }}"
                                            data-project-name="{{ $project->name }}"
                                            title="تسليم المشروع">
                                <i class="fas fa-truck"></i>
                                تسليم
                            </button>

                                    <button class="services-btn btn-history"
                                            data-project-id="{{ $project->id }}"
                                            data-project-name="{{ $project->name }}"
                                            style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);"
                                            title="سجل التسليمات">
                                        <i class="fas fa-history"></i>
                                        سجل التسليمات
                                    </button>
                                </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Pagination -->
                @if($projects->hasPages())
                    <div class="d-flex justify-content-center mt-4">
                {{ $projects->withQueryString()->links() }}
            </div>
                @endif
            @else
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                    <h4>لا توجد مشاريع</h4>
                <p>لم يتم العثور على مشاريع مطابقة للفلاتر المحددة</p>
            </div>
            @endif
        </div>

        <!-- Tab: Draft Deliveries -->
        <div class="tab-content" id="tab-draft">
            @php
                $draftProjects = $projects->filter(function($project) {
                    return $project->lastDraftDelivery !== null;
                });
            @endphp

            @if($draftProjects->count() > 0)
            <table class="projects-table">
                <thead>
                    <tr>
                            <th>المشروع</th>
                        <th>العميل</th>
                        <th>تاريخ التسليم</th>
                        <th>سلم بواسطة</th>
                        <th>ملاحظات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($draftProjects as $project)
                    @if($project->lastDraftDelivery)
                        <tr class="project-row">
                            <td>
                                <div class="project-info">
                                    <div class="project-avatar">
                                        <i class="fas fa-file"></i>
                                    </div>
                                    <div class="project-details">
                                        @if($project->code)
                                            <div class="project-code-display">{{ $project->code }}</div>
                                        @endif
                                        <h4>{{ $project->name }}</h4>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="client-info">
                                    {{ $project->client->name ?? 'غير محدد' }}
                                </div>
                            </td>
                            <td>
                                <div style="color: #6b7280; font-size: 0.9rem;">
                                <i class="fas fa-calendar"></i>
                                    {{ $project->lastDraftDelivery->delivery_date->format('Y/m/d') }}
                                    <div style="font-size: 0.8rem; color: #6b7280; margin-top: 4px;">
                                        {{ $project->lastDraftDelivery->delivery_date->format('h:i A') }}
                                    </div>
                                </div>
                        </td>
                            <td>
                                <div style="font-weight: 600;">
                                    {{ $project->lastDraftDelivery->deliveredBy->name ?? 'غير محدد' }}
                                </div>
                            </td>
                            <td>
                                {{ \Str::limit($project->lastDraftDelivery->notes, 50) ?? '-' }}
                            </td>
                    </tr>
                    @endif
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="empty-state">
                <i class="fas fa-file"></i>
                    <h4>لا توجد مسودات</h4>
                <p>لا توجد تسليمات مسودة حتى الآن</p>
            </div>
            @endif
        </div>

        <!-- Tab: Final Deliveries -->
        <div class="tab-content" id="tab-final">
            @php
                $finalProjects = $projects->filter(function($project) {
                    return $project->lastFinalDelivery !== null;
                });
            @endphp

            @if($finalProjects->count() > 0)
            <table class="projects-table">
                <thead>
                    <tr>
                            <th>المشروع</th>
                        <th>العميل</th>
                        <th>تاريخ التسليم</th>
                        <th>سلم بواسطة</th>
                        <th>ملاحظات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($finalProjects as $project)
                    @if($project->lastFinalDelivery)
                        <tr class="project-row">
                            <td>
                                <div class="project-info">
                                    <div class="project-avatar">
                                        <i class="fas fa-check-double"></i>
                                    </div>
                                    <div class="project-details">
                                        @if($project->code)
                                            <div class="project-code-display">{{ $project->code }}</div>
                                        @endif
                                        <h4>{{ $project->name }}</h4>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="client-info">
                                    {{ $project->client->name ?? 'غير محدد' }}
                                </div>
                            </td>
                            <td>
                                <div style="color: #6b7280; font-size: 0.9rem;">
                                <i class="fas fa-calendar"></i>
                                    {{ $project->lastFinalDelivery->delivery_date->format('Y/m/d') }}
                                    <div style="font-size: 0.8rem; color: #6b7280; margin-top: 4px;">
                                        {{ $project->lastFinalDelivery->delivery_date->format('h:i A') }}
                                    </div>
                                </div>
                        </td>
                            <td>
                                <div style="font-weight: 600;">
                                    {{ $project->lastFinalDelivery->deliveredBy->name ?? 'غير محدد' }}
                                </div>
                            </td>
                            <td>
                                {{ \Str::limit($project->lastFinalDelivery->notes, 50) ?? '-' }}
                            </td>
                    </tr>
                    @endif
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="empty-state">
                <i class="fas fa-check-double"></i>
                    <h4>لا توجد تسليمات نهائية</h4>
                <p>لا توجد تسليمات نهائية حتى الآن</p>
            </div>
            @endif
            </div>
        </div>
    </div>
</div>

<!-- Delivery Modal -->
<div class="modal-overlay" id="deliveryModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-shipping-fast"></i> تسليم مشروع</h3>
        </div>
        <form id="deliveryForm" method="POST">
            @csrf
            <div class="modal-body">
                <p id="deliveryProjectName" style="margin-bottom: 1rem; font-weight: bold;"></p>

                <div class="form-group">
                    <label for="delivery_type">نوع التسليم *</label>
                    <select name="delivery_type" id="delivery_type" required>
                        <option value="">اختر النوع...</option>
                        @foreach($deliveryTypes as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="notes">ملاحظات</label>
                    <textarea name="notes" id="notes" rows="4"
                              placeholder="أضف ملاحظات التسليم..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal-cancel" onclick="closeDeliveryModal()">إلغاء</button>
                <button type="submit" class="btn-modal-confirm">
                    <i class="fas fa-truck"></i>
                    تسليم المشروع
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delivery History Modal -->
<div class="modal-overlay" id="deliveryHistoryModal">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h3><i class="fas fa-history"></i> سجل التسليمات</h3>
        </div>
        <div class="modal-body">
            <p id="historyProjectName" style="margin-bottom: 1.5rem; font-weight: bold; font-size: 1.1rem;"></p>

            <div id="deliveryHistoryContent" style="max-height: 500px; overflow-y: auto;">
                <!-- سيتم ملئه عبر JavaScript -->
                <div class="text-center" style="padding: 2rem;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #667eea;"></i>
                    <p style="margin-top: 1rem; color: #6b7280;">جاري تحميل السجل...</p>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-modal-cancel" onclick="closeHistoryModal()">إغلاق</button>
        </div>
    </div>
</div>

<!-- Project Services Modal -->
<div class="modal-overlay" id="projectServicesModal">
    <div class="modal-content" style="max-width: 900px;">
        <div class="modal-header">
            <h3><i class="fas fa-list-check"></i> خدمات المشروع</h3>
        </div>
        <div class="modal-body">
            <div style="margin-bottom: 1.5rem;">
                <p id="servicesProjectName" style="font-weight: bold; font-size: 1.1rem; margin-bottom: 0.5rem;"></p>
                <p id="servicesProjectCode" style="color: #6b7280; font-size: 0.9rem;"></p>
            </div>

            <div id="projectServicesContent" style="max-height: 500px; overflow-y: auto;">
                <!-- سيتم ملئه عبر JavaScript -->
                <div class="text-center" style="padding: 2rem;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #667eea;"></i>
                    <p style="margin-top: 1rem; color: #6b7280;">جاري تحميل الخدمات...</p>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-modal-cancel" onclick="closeServicesModal()">إغلاق</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
    /* Additional styles for status badges */
    .status-badge {
        display: inline-block;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 700;
        text-align: center;
        white-space: nowrap;
    }

    .status-badge.جديد {
        background: linear-gradient(135deg, #dbeafe, #bfdbfe);
        color: #1e40af;
    }

    .status-badge.جاري {
        background: linear-gradient(135deg, #fef3c7, #fde68a);
        color: #92400e;
    }

    .status-badge.مكتمل {
        background: linear-gradient(135deg, #d1fae5, #a7f3d0);
        color: #065f46;
    }

    .search-input {
        background: white;
        border: 2px solid #e5e7eb;
        padding: 10px 15px;
        border-radius: 8px;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        width: 100%;
        position: relative;
    }

    .search-input:focus {
        border-color: #3b82f6;
        outline: none;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    /* Datalist dropdown styling */
    datalist {
        display: none;
    }

    input[list]::-webkit-calendar-picker-indicator {
        display: none;
    }

    /* Custom dropdown arrow for datalist */
    .filter-group {
        position: relative;
    }

    #searchInput {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M10.293 3.293L6 7.586 1.707 3.293A1 1 0 00.293 4.707l5 5a1 1 0 001.414 0l5-5a1 1 0 10-1.414-1.414z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: left 10px center;
        padding-left: 35px;
    }

    .search-btn {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: white;
        border: none;
        padding: 10px 25px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s ease;
        display: inline-block;
        width: 100%;
    }

    .search-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }

    .clear-filters-btn {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
        width: 100%;
    }

    .clear-filters-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        color: white;
    }

    /* Delivery History Timeline */
    .delivery-history-timeline {
        position: relative;
        padding: 1rem 0;
    }

    .delivery-history-timeline::before {
        content: '';
        position: absolute;
        top: 0;
        right: 30px;
        bottom: 0;
        width: 3px;
        background: linear-gradient(to bottom, #667eea, #764ba2);
        border-radius: 2px;
    }

    .history-item {
        position: relative;
        padding-right: 70px;
        margin-bottom: 2rem;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(-20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    .history-marker {
        position: absolute;
        right: 10px;
        top: 0;
        width: 45px;
        height: 45px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 3px solid white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }

    .history-content {
        background: white;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        padding: 1.25rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
    }

    .history-content:hover {
        border-color: #667eea;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
        transform: translateX(-5px);
    }

    .history-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.75rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid #f3f4f6;
    }

    .history-details {
        margin-top: 0.5rem;
    }

    /* Project Services Styles */
    .services-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1rem;
    }

    .service-card {
        background: white;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        padding: 1.25rem;
        transition: all 0.3s ease;
    }

    .service-card:hover {
        border-color: #3b82f6;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
        transform: translateY(-2px);
    }

    .service-header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid #f3f4f6;
    }

    .service-icon {
        font-size: 1.5rem;
        width: 45px;
        height: 45px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #667eea, #764ba2);
        border-radius: 10px;
        color: white;
    }

    .service-name {
        font-weight: 700;
        font-size: 1rem;
        color: #374151;
        flex: 1;
    }

    .service-status-badge {
        display: inline-block;
        padding: 0.4rem 0.8rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 700;
        text-align: center;
    }

    .service-progress {
        margin-top: 1rem;
    }

    .progress-label {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.5rem;
        font-size: 0.85rem;
        color: #6b7280;
    }

    .progress-bar-container {
        width: 100%;
        height: 8px;
        background: #e5e7eb;
        border-radius: 10px;
        overflow: hidden;
    }

    .progress-bar-fill {
        height: 100%;
        background: linear-gradient(90deg, #667eea, #764ba2);
        border-radius: 10px;
        transition: width 0.3s ease;
    }

    .service-dates {
        display: flex;
        justify-content: space-between;
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid #f3f4f6;
        font-size: 0.8rem;
        color: #6b7280;
    }

    .service-notes {
        margin-top: 1rem;
        padding: 0.75rem;
        background: #f9fafb;
        border-radius: 8px;
        font-size: 0.85rem;
        color: #6b7280;
    }

    @media (max-width: 768px) {
        .filters-row {
            flex-direction: column;
        }

        .filter-group {
            width: 100%;
        }

        .stats-row {
            grid-template-columns: repeat(2, 1fr);
        }

        .delivery-history-timeline::before {
            right: 20px;
        }

        .history-marker {
            right: 0;
            width: 40px;
            height: 40px;
        }

        .history-item {
            padding-right: 60px;
        }

        .history-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
        }
    }
</style>

<script>
// Datalist auto-complete styling
const searchInput = document.getElementById('searchInput');
if (searchInput) {
    searchInput.addEventListener('input', function() {
        // Add visual feedback when typing
        if (this.value.length > 0) {
            this.style.borderColor = '#3b82f6';
        } else {
            this.style.borderColor = '#e5e7eb';
        }
    });

    // Auto-submit on selection
    searchInput.addEventListener('change', function() {
        if (this.value.trim() !== '') {
            // Optional: auto-submit when selecting from datalist
            // document.getElementById('filtersForm').submit();
        }
    });
}

// Open Delivery Modal
document.querySelectorAll('.btn-deliver').forEach(btn => {
    btn.addEventListener('click', function() {
        const projectId = this.dataset.projectId;
        const projectName = this.dataset.projectName;

        document.getElementById('deliveryProjectName').textContent = 'المشروع: ' + projectName;
        document.getElementById('deliveryForm').action = '/projects/' + projectId + '/deliver';
        document.getElementById('deliveryModal').classList.add('active');
    });
});

// Close Delivery Modal
function closeDeliveryModal() {
    document.getElementById('deliveryModal').classList.remove('active');
    document.getElementById('deliveryForm').reset();
}

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

// Close modal on overlay click
document.querySelectorAll('.modal-overlay').forEach(modal => {
    modal.addEventListener('click', function(e) {
    if (e.target === this) {
            if (this.id === 'deliveryModal') closeDeliveryModal();
            if (this.id === 'deliveryHistoryModal') closeHistoryModal();
            if (this.id === 'projectServicesModal') closeServicesModal();
        }
    });
});

// Close modal on ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeDeliveryModal();
        closeHistoryModal();
        closeServicesModal();
    }
});

// Open Delivery History Modal
document.querySelectorAll('.btn-history').forEach(btn => {
    btn.addEventListener('click', function() {
        const projectId = this.dataset.projectId;
        const projectName = this.dataset.projectName;

        document.getElementById('historyProjectName').textContent = 'المشروع: ' + projectName;
        document.getElementById('deliveryHistoryModal').classList.add('active');

        // Load delivery history via AJAX
        loadDeliveryHistory(projectId);
    });
});

// Close History Modal
function closeHistoryModal() {
    document.getElementById('deliveryHistoryModal').classList.remove('active');
    document.getElementById('deliveryHistoryContent').innerHTML = `
        <div class="text-center" style="padding: 2rem;">
            <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #667eea;"></i>
            <p style="margin-top: 1rem; color: #6b7280;">جاري تحميل السجل...</p>
        </div>
    `;
}

// Load Delivery History
function loadDeliveryHistory(projectId) {
    fetch(`/projects/${projectId}/deliveries-history`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.deliveries.length > 0) {
                displayDeliveryHistory(data.deliveries);
            } else {
                displayNoHistory();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            displayErrorHistory();
        });
}

// Display Delivery History
function displayDeliveryHistory(deliveries) {
    const deliveryTypeIcons = {
        'مسودة': '📄',
        'كامل': '✅',
        'خدمات': '🔧',
        'تعديل_على_الدراسة': '📝',
        'تقييم_مالي': '💰',
        'ترجمة': '🌐',
        'ملخص': '📋',
        'عروض_اسعار': '💵',
        'خطة_عمل': '📊',
        'خطة_تشغيل': '⚙️',
        'باور_بوينت': '🎯'
    };

    const deliveryTypeColors = {
        'مسودة': '#fef3c7',
        'كامل': '#d1fae5',
        'خدمات': '#dbeafe',
        'تعديل_على_الدراسة': '#fce7f3',
        'تقييم_مالي': '#fef3c7',
        'ترجمة': '#e0e7ff',
        'ملخص': '#dbeafe',
        'عروض_اسعار': '#fef3c7',
        'خطة_عمل': '#ddd6fe',
        'خطة_تشغيل': '#e0e7ff',
        'باور_بوينت': '#fce7f3'
    };

    const deliveryTypeLabels = {
        'مسودة': 'مسودة',
        'كامل': 'كامل (نهائي)',
        'خدمات': 'خدمات',
        'تعديل_على_الدراسة': 'تعديل على الدراسة',
        'تقييم_مالي': 'تقييم مالي',
        'ترجمة': 'ترجمة',
        'ملخص': 'ملخص',
        'عروض_اسعار': 'عروض أسعار',
        'خطة_عمل': 'خطة عمل',
        'خطة_تشغيل': 'خطة تشغيل',
        'باور_بوينت': 'باور بوينت'
    };

    let html = '<div class="delivery-history-timeline">';

    deliveries.forEach((delivery, index) => {
        const icon = deliveryTypeIcons[delivery.delivery_type] || '📦';
        const bgColor = deliveryTypeColors[delivery.delivery_type] || '#f3f4f6';
        const displayType = deliveryTypeLabels[delivery.delivery_type] || delivery.delivery_type;

        html += `
            <div class="history-item" style="animation: slideIn 0.3s ease ${index * 0.1}s both;">
                <div class="history-marker" style="background: ${bgColor};">
                    <span style="font-size: 1.5rem;">${icon}</span>
                </div>
                <div class="history-content">
                    <div class="history-header">
                        <h4 style="margin: 0; color: #374151; font-size: 1rem;">
                            ${displayType}
                        </h4>
                        <span style="color: #6b7280; font-size: 0.85rem;">
                            <i class="fas fa-calendar"></i> ${delivery.delivery_date}
                        </span>
                    </div>
                    <div class="history-details">
                        <p style="margin: 0.5rem 0; color: #6b7280; font-size: 0.9rem;">
                            <i class="fas fa-user"></i>
                            <strong>سلم بواسطة:</strong> ${delivery.delivered_by}
                        </p>
                        ${delivery.notes ? `
                            <p style="margin: 0.5rem 0; color: #6b7280; font-size: 0.9rem;">
                                <i class="fas fa-sticky-note"></i>
                                <strong>ملاحظات:</strong> ${delivery.notes}
                            </p>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
    });

    html += '</div>';

    document.getElementById('deliveryHistoryContent').innerHTML = html;
}

// Display No History
function displayNoHistory() {
    document.getElementById('deliveryHistoryContent').innerHTML = `
        <div class="empty-state" style="padding: 3rem; text-align: center;">
            <i class="fas fa-inbox" style="font-size: 4rem; color: #d1d5db;"></i>
            <h4 style="color: #6b7280; margin-top: 1rem;">لا يوجد سجل تسليمات</h4>
            <p style="color: #9ca3af;">لم يتم تسليم أي شيء لهذا المشروع بعد</p>
        </div>
    `;
}

// Display Error History
function displayErrorHistory() {
    document.getElementById('deliveryHistoryContent').innerHTML = `
        <div class="empty-state" style="padding: 3rem; text-align: center;">
            <i class="fas fa-exclamation-triangle" style="font-size: 4rem; color: #ef4444;"></i>
            <h4 style="color: #ef4444; margin-top: 1rem;">حدث خطأ</h4>
            <p style="color: #9ca3af;">لم نتمكن من تحميل سجل التسليمات</p>
        </div>
    `;
}

// ==========================================
// Project Services Modal Functions
// ==========================================

// Open Project Services Modal
document.querySelectorAll('.btn-view-services').forEach(btn => {
    btn.addEventListener('click', function() {
        const projectId = this.dataset.projectId;
        const projectName = this.dataset.projectName;
        const projectCode = this.dataset.projectCode;

        document.getElementById('servicesProjectName').textContent = 'المشروع: ' + projectName;
        document.getElementById('servicesProjectCode').textContent = 'الكود: ' + projectCode;
        document.getElementById('projectServicesModal').classList.add('active');

        // Load project services via AJAX
        loadProjectServices(projectId);
    });
});

// Close Services Modal
function closeServicesModal() {
    document.getElementById('projectServicesModal').classList.remove('active');
    document.getElementById('projectServicesContent').innerHTML = `
        <div class="text-center" style="padding: 2rem;">
            <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #667eea;"></i>
            <p style="margin-top: 1rem; color: #6b7280;">جاري تحميل الخدمات...</p>
        </div>
    `;
}

// Load Project Services
function loadProjectServices(projectId) {
    fetch(`/projects/${projectId}/services-status`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.services.length > 0) {
                displayProjectServices(data.services);
            } else {
                displayNoServices();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            displayServicesError();
        });
}

// Display Project Services
function displayProjectServices(services) {
    const statusColors = {
        'لم تبدأ': 'background: linear-gradient(135deg, #e5e7eb, #d1d5db); color: #374151;',
        'جاري العمل': 'background: linear-gradient(135deg, #fef3c7, #fde68a); color: #92400e;',
        'مكتملة': 'background: linear-gradient(135deg, #d1fae5, #a7f3d0); color: #065f46;',
        'معلقة': 'background: linear-gradient(135deg, #fee2e2, #fecaca); color: #991b1b;',
        'جديد': 'background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #1e40af;',
        'جاري التنفيذ': 'background: linear-gradient(135deg, #fef3c7, #fde68a); color: #92400e;',
        'مكتمل': 'background: linear-gradient(135deg, #d1fae5, #a7f3d0); color: #065f46;',
        'معلق': 'background: linear-gradient(135deg, #fee2e2, #fecaca); color: #991b1b;',
        'ملغي': 'background: linear-gradient(135deg, #f3f4f6, #e5e7eb); color: #6b7280;'
    };

    let html = '<div class="services-grid">';

    services.forEach((service, index) => {
        const statusStyle = statusColors[service.status] || 'background: #f3f4f6; color: #6b7280;';
        const progress = service.progress || 0;

        html += `
            <div class="service-card" style="animation: slideIn 0.3s ease ${index * 0.1}s both;">
                <div class="service-header">
                    <div class="service-icon">
                        ${service.icon}
                    </div>
                    <div class="service-name">${service.name}</div>
                </div>

                <div style="text-align: center; margin-bottom: 1rem;">
                    <span class="service-status-badge" style="${statusStyle}">
                        ${service.status}
                    </span>
                </div>

                ${service.start_date || service.end_date ? `
                    <div class="service-dates">
                        ${service.start_date ? `
                            <div>
                                <i class="fas fa-play-circle"></i>
                                البداية: ${service.start_date}
                            </div>
                        ` : ''}
                        ${service.end_date ? `
                            <div>
                                <i class="fas fa-flag-checkered"></i>
                                النهاية: ${service.end_date}
                            </div>
                        ` : ''}
                    </div>
                ` : ''}

                ${service.notes ? `
                    <div class="service-notes">
                        <i class="fas fa-sticky-note"></i>
                        ${service.notes}
                    </div>
                ` : ''}
            </div>
        `;
    });

    html += '</div>';

    document.getElementById('projectServicesContent').innerHTML = html;
}

// Display No Services
function displayNoServices() {
    document.getElementById('projectServicesContent').innerHTML = `
        <div class="empty-state" style="padding: 3rem; text-align: center;">
            <i class="fas fa-box-open" style="font-size: 4rem; color: #d1d5db;"></i>
            <h4 style="color: #6b7280; margin-top: 1rem;">لا توجد خدمات</h4>
            <p style="color: #9ca3af;">لم يتم إضافة أي خدمات لهذا المشروع بعد</p>
        </div>
    `;
}

// Display Services Error
function displayServicesError() {
    document.getElementById('projectServicesContent').innerHTML = `
        <div class="empty-state" style="padding: 3rem; text-align: center;">
            <i class="fas fa-exclamation-triangle" style="font-size: 4rem; color: #ef4444;"></i>
            <h4 style="color: #ef4444; margin-top: 1rem;">حدث خطأ</h4>
            <p style="color: #9ca3af;">لم نتمكن من تحميل خدمات المشروع</p>
        </div>
    `;
}
</script>
@endpush

