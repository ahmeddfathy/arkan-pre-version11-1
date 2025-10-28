@extends('layouts.app')

@section('title', 'إحصائيات نقل التعديلات')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<style>
    .stats-card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        color: #333;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        margin-bottom: 20px;
        border: 1px solid #e9ecef;
    }

    .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 30px rgba(0,0,0,0.12);
    }

    .stats-card-title {
        font-size: 14px;
        opacity: 0.8;
        margin-bottom: 10px;
        font-weight: 500;
        color: #6c757d;
    }

    .stats-card-value {
        font-size: 32px;
        font-weight: bold;
        margin: 0;
    }

    .stats-card-icon {
        font-size: 40px;
        opacity: 0.15;
        position: absolute;
        bottom: 10px;
        left: 20px;
    }

    .stats-card.executor {
        border-right: 4px solid #f093fb;
    }

    .stats-card.executor .stats-card-value {
        color: #f093fb;
    }

    .stats-card.executor .stats-card-icon {
        color: #f093fb;
    }

    .stats-card.reviewer {
        border-right: 4px solid #4facfe;
    }

    .stats-card.reviewer .stats-card-value {
        color: #4facfe;
    }

    .stats-card.reviewer .stats-card-icon {
        color: #4facfe;
    }

    .stats-card.total {
        border-right: 4px solid #43e97b;
    }

    .stats-card.total .stats-card-value {
        color: #43e97b;
    }

    .stats-card.total .stats-card-icon {
        color: #43e97b;
    }

    .filter-section {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        margin-bottom: 25px;
    }

    .filter-section h5 {
        color: #667eea;
        font-weight: 600;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .table-container {
        background: white;
        border-radius: 15px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        overflow: hidden;
        margin-bottom: 3rem; /* زيادة المسافة بين الجدول والفوتر */
    }

    .table-responsive {
        /* إخفاء scrollbar مع الحفاظ على الـ scroll */
        scrollbar-width: none; /* Firefox */
        -ms-overflow-style: none; /* IE and Edge */
    }

    .table-responsive::-webkit-scrollbar {
        display: none; /* Chrome, Safari, Opera */
    }

    .table thead {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .table thead th {
        border: none;
        padding: 15px;
        font-weight: 600;
        font-size: 14px;
    }

    .table tbody tr {
        border-bottom: 1px solid #f5f5f5;
        transition: all 0.3s ease;
    }

    .table tbody tr:hover {
        background: #f8f9fa;
        transform: scale(1.001);
    }

    .table tbody td {
        padding: 12px 15px;
        vertical-align: middle;
        font-size: 13px;
    }

    .badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }

    .badge-executor {
        background: #fff0f8;
        color: #f093fb;
        border: 1px solid #f5c6e8;
        font-weight: 600;
    }

    .badge-reviewer {
        background: #e8f7ff;
        color: #4facfe;
        border: 1px solid #c2e7ff;
        font-weight: 600;
    }

    .user-avatar {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        margin-left: 8px;
        font-size: 14px;
    }

    .top-users-section {
        background: white;
        border-radius: 15px;
        padding: 20px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        margin-bottom: 25px;
    }

    .top-users-section h6 {
        color: #667eea;
        font-weight: 600;
        margin-bottom: 15px;
    }

    .top-user-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px;
        border-radius: 8px;
        margin-bottom: 8px;
        background: #f8f9fa;
        transition: all 0.3s ease;
    }

    .top-user-item:hover {
        background: #e9ecef;
        transform: translateX(-5px);
    }

    .top-user-name {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .top-user-count {
        background: #667eea;
        color: white;
        padding: 5px 15px;
        border-radius: 20px;
        font-weight: bold;
        font-size: 13px;
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.25);
    }


    .form-control, .form-select {
        border-radius: 8px;
        border: 1px solid #e0e0e0;
        padding: 10px 15px;
        transition: all 0.3s ease;
    }

    .form-control:focus, .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }

    .btn-primary {
        background: #667eea;
        border: none;
        border-radius: 8px;
        padding: 10px 25px;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.2);
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.35);
        background: #5568d3;
    }

    .btn-secondary {
        background: #6c757d;
        border: none;
        border-radius: 8px;
        padding: 10px 25px;
        font-weight: 500;
    }

    .pagination {
        margin-top: 20px;
    }

    .page-link {
        color: #667eea;
        border: 1px solid #dee2e6;
        border-radius: 5px;
        margin: 0 3px;
    }

    .page-link:hover {
        background-color: #667eea;
        color: white;
    }

    .page-item.active .page-link {
        background: #667eea;
        border-color: #667eea;
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.25);
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #999;
    }

    .empty-state i {
        font-size: 64px;
        margin-bottom: 20px;
        opacity: 0.5;
    }

    .empty-state h5 {
        color: #666;
        margin-bottom: 10px;
    }

    .empty-state p {
        color: #999;
    }

    /* Datalist Enhanced Styling */
    .datalist-input {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 16 16' fill='%23999'%3E%3Cpath d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z'%3E%3C/path%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: left 12px center;
        background-size: 16px 16px;
        padding-left: 40px;
        transition: all 0.3s ease;
    }

    .datalist-input:focus {
        box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
        border-color: #667eea;
        transform: translateY(-1px);
    }

    .datalist-input::placeholder {
        color: #999;
    }

    .datalist-input:hover {
        border-color: #667eea;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-2">
                <i class="fas fa-exchange-alt me-2" style="color: #667eea;"></i>
                إحصائيات نقل التعديلات
            </h2>
            <p class="text-muted mb-0">تتبع وتحليل عمليات نقل التعديلات بين الموظفين</p>
        </div>
        <div>
            <a href="{{ route('revision.page') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-right me-2"></i>
                العودة لصفحة التعديلات
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row" id="statsCards">
        <div class="col-md-4">
            <div class="stats-card total position-relative">
                <div class="stats-card-title">إجمالي عمليات النقل</div>
                <h2 class="stats-card-value" id="totalTransfers">0</h2>
                <i class="fas fa-exchange-alt stats-card-icon"></i>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card executor position-relative">
                <div class="stats-card-title">نقل المنفذين</div>
                <h2 class="stats-card-value" id="executorTransfers">0</h2>
                <i class="fas fa-user-cog stats-card-icon"></i>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card reviewer position-relative">
                <div class="stats-card-title">نقل المراجعين</div>
                <h2 class="stats-card-value" id="reviewerTransfers">0</h2>
                <i class="fas fa-user-check stats-card-icon"></i>
            </div>
        </div>
    </div>

    <!-- Top Users Section -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="top-users-section">
                <h6>
                    <i class="fas fa-arrow-up me-2"></i>
                    أكثر المستخدمين نقلاً منهم
                </h6>
                <div id="topFromUsers">
                    <div class="text-center text-muted py-3">
                        <small>لا توجد بيانات</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="top-users-section">
                <h6>
                    <i class="fas fa-arrow-down me-2"></i>
                    أكثر المستخدمين نقلاً إليهم
                </h6>
                <div id="topToUsers">
                    <div class="text-center text-muted py-3">
                        <small>لا توجد بيانات</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="filter-section">
        <h5>
            <i class="fas fa-filter"></i>
            تصفية البيانات
        </h5>
        <form id="filterForm">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">الشهر</label>
                    <input type="month" class="form-control" id="monthFilter" name="month">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">من تاريخ</label>
                    <input type="date" class="form-control" id="fromDateFilter" name="from_date">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">إلى تاريخ</label>
                    <input type="date" class="form-control" id="toDateFilter" name="to_date">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">نوع النقل</label>
                    <select class="form-select" id="assignmentTypeFilter" name="assignment_type">
                        <option value="">الكل</option>
                        <option value="executor">منفذ</option>
                        <option value="reviewer">مراجع</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">المشروع</label>
                    <input type="text"
                           id="projectFilterSearch"
                           class="form-control datalist-input"
                           list="projectsFilterList"
                           placeholder="ابحث عن المشروع..."
                           autocomplete="off"
                           oninput="handleProjectFilterSelection()">
                    <datalist id="projectsFilterList">
                        <option value="">كل المشاريع</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->name }}" data-project-id="{{ $project->id }}">
                                {{ $project->code ?? '' }} - {{ $project->name }}
                            </option>
                        @endforeach
                    </datalist>
                    <input type="hidden" id="projectFilter" name="project_id">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">المستخدم</label>
                    <select class="form-select" id="userFilter" name="user_id">
                        <option value="">كل المستخدمين</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2 flex-grow-1">
                        <i class="fas fa-search me-2"></i>
                        بحث
                    </button>
                    <button type="button" class="btn btn-secondary" id="resetFilters">
                        <i class="fas fa-redo me-2"></i>
                        إعادة تعيين
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Transfer Records Table -->
    <div class="table-container">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>التعديل</th>
                        <th>المشروع</th>
                        <th>نوع النقل</th>
                        <th>من</th>
                        <th>إلى</th>
                        <th>تم بواسطة</th>
                        <th>السبب</th>
                        <th>التاريخ</th>
                    </tr>
                </thead>
                <tbody id="recordsTableBody">
                    <tr>
                        <td colspan="9" class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="p-3">
            <div id="paginationContainer"></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let currentPage = 1;
let currentFilters = {};

$(document).ready(function() {
    // Load initial data
    loadTransferStats();
    loadTransferRecords();

    // Filter form submission
    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        currentPage = 1;
        currentFilters = $(this).serialize();
        loadTransferStats();
        loadTransferRecords();
    });

    // Reset filters
    $('#resetFilters').on('click', function() {
        $('#filterForm')[0].reset();
        currentPage = 1;
        currentFilters = {};
        loadTransferStats();
        loadTransferRecords();
    });
});

function loadTransferStats() {
    $.ajax({
        url: '{{ route("revision.transfer-stats") }}',
        method: 'GET',
        data: currentFilters,
        success: function(response) {
            if (response.success) {
                updateStatsCards(response.stats);
                updateTopUsers(response.stats);
            }
        },
        error: function(xhr) {
            console.error('Error loading stats:', xhr);
            Swal.fire({
                icon: 'error',
                title: 'خطأ',
                text: 'حدث خطأ في تحميل الإحصائيات',
                confirmButtonColor: '#667eea'
            });
        }
    });
}

function loadTransferRecords(page = 1) {
    let data = currentFilters ? currentFilters + '&page=' + page : 'page=' + page;

    $.ajax({
        url: '{{ route("revision.transfer-records") }}',
        method: 'GET',
        data: data,
        success: function(response) {
            if (response.success) {
                updateRecordsTable(response.records);
                updatePagination(response.records);
            }
        },
        error: function(xhr) {
            console.error('Error loading records:', xhr);
            Swal.fire({
                icon: 'error',
                title: 'خطأ',
                text: 'حدث خطأ في تحميل السجلات',
                confirmButtonColor: '#667eea'
            });
        }
    });
}

function updateStatsCards(stats) {
    $('#totalTransfers').text(stats.total_transfers || 0);
    $('#executorTransfers').text(stats.executor_transfers || 0);
    $('#reviewerTransfers').text(stats.reviewer_transfers || 0);
}

function updateTopUsers(stats) {
    // Top From Users
    let topFromHtml = '';
    if (stats.top_from_users && stats.top_from_users.length > 0) {
        stats.top_from_users.forEach((item, index) => {
            const userName = item.from_user ? item.from_user.name : 'غير معروف';
            const initial = userName.charAt(0);
            topFromHtml += `
                <div class="top-user-item">
                    <div class="top-user-name">
                        <div class="user-avatar">${initial}</div>
                        <span>${userName}</span>
                    </div>
                    <div class="top-user-count">${item.transfer_count}</div>
                </div>
            `;
        });
    } else {
        topFromHtml = '<div class="text-center text-muted py-3"><small>لا توجد بيانات</small></div>';
    }
    $('#topFromUsers').html(topFromHtml);

    // Top To Users
    let topToHtml = '';
    if (stats.top_to_users && stats.top_to_users.length > 0) {
        stats.top_to_users.forEach((item, index) => {
            const userName = item.to_user ? item.to_user.name : 'غير معروف';
            const initial = userName.charAt(0);
            topToHtml += `
                <div class="top-user-item">
                    <div class="top-user-name">
                        <div class="user-avatar">${initial}</div>
                        <span>${userName}</span>
                    </div>
                    <div class="top-user-count">${item.transfer_count}</div>
                </div>
            `;
        });
    } else {
        topToHtml = '<div class="text-center text-muted py-3"><small>لا توجد بيانات</small></div>';
    }
    $('#topToUsers').html(topToHtml);
}

function updateRecordsTable(records) {
    let html = '';

    if (records.data && records.data.length > 0) {
        records.data.forEach((record, index) => {
            const revisionTitle = record.revision ? record.revision.title : 'غير محدد';
            const projectCode = record.revision && record.revision.project && record.revision.project.code ? record.revision.project.code : null;
            const projectName = record.revision && record.revision.project ? record.revision.project.name : 'غير محدد';
            const projectDisplay = projectCode || projectName;
            const fromUser = record.from_user ? record.from_user.name : 'غير محدد';
            const toUser = record.to_user ? record.to_user.name : 'غير محدد';
            const assignedBy = record.assigned_by ? record.assigned_by.name : 'غير محدد';
            const reason = record.reason || '-';
            const date = new Date(record.created_at).toLocaleDateString('ar-EG', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });

            const typeBadge = record.assignment_type === 'executor'
                ? '<span class="badge badge-executor">منفذ</span>'
                : '<span class="badge badge-reviewer">مراجع</span>';

            html += `
                <tr>
                    <td>${(records.current_page - 1) * records.per_page + index + 1}</td>
                    <td><strong>${revisionTitle}</strong></td>
                    <td title="${projectName}">${projectDisplay}</td>
                    <td>${typeBadge}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="user-avatar" style="width: 30px; height: 30px; font-size: 12px;">${fromUser.charAt(0)}</div>
                            <span>${fromUser}</span>
                        </div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="user-avatar" style="width: 30px; height: 30px; font-size: 12px;">${toUser.charAt(0)}</div>
                            <span>${toUser}</span>
                        </div>
                    </td>
                    <td>${assignedBy}</td>
                    <td><small>${reason}</small></td>
                    <td><small>${date}</small></td>
                </tr>
            `;
        });
    } else {
        html = `
            <tr>
                <td colspan="9">
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h5>لا توجد سجلات نقل</h5>
                        <p>لم يتم العثور على أي عمليات نقل بالفلاتر المحددة</p>
                    </div>
                </td>
            </tr>
        `;
    }

    $('#recordsTableBody').html(html);
}

function updatePagination(records) {
    let html = '';

    if (records.last_page > 1) {
        html = '<nav><ul class="pagination justify-content-center mb-0">';

        // Previous button
        if (records.current_page > 1) {
            html += `<li class="page-item"><a class="page-link" href="#" onclick="loadTransferRecords(${records.current_page - 1}); return false;">السابق</a></li>`;
        }

        // Page numbers
        for (let i = 1; i <= records.last_page; i++) {
            if (i === records.current_page) {
                html += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
            } else if (i === 1 || i === records.last_page || (i >= records.current_page - 2 && i <= records.current_page + 2)) {
                html += `<li class="page-item"><a class="page-link" href="#" onclick="loadTransferRecords(${i}); return false;">${i}</a></li>`;
            } else if (i === records.current_page - 3 || i === records.current_page + 3) {
                html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
        }

        // Next button
        if (records.current_page < records.last_page) {
            html += `<li class="page-item"><a class="page-link" href="#" onclick="loadTransferRecords(${records.current_page + 1}); return false;">التالي</a></li>`;
        }

        html += '</ul></nav>';
    }

    $('#paginationContainer').html(html);
}

// Store projects data globally for filter
let availableProjectsFilter = [];

// Load projects list on page load
document.addEventListener('DOMContentLoaded', function() {
    const datalist = document.getElementById('projectsFilterList');
    if (datalist) {
        const options = datalist.querySelectorAll('option');
        options.forEach(option => {
            const projectId = option.getAttribute('data-project-id');
            if (projectId) {
                availableProjectsFilter.push({
                    id: projectId,
                    name: option.value
                });
            }
        });
    }
});

// Handle project filter selection from datalist
function handleProjectFilterSelection() {
    const searchInput = document.getElementById('projectFilterSearch');
    const hiddenInput = document.getElementById('projectFilter');

    if (!searchInput || !hiddenInput) return;

    // Find project by name
    const selectedProject = availableProjectsFilter.find(project => project.name === searchInput.value);

    if (selectedProject) {
        hiddenInput.value = selectedProject.id;
    } else {
        // If empty or "كل المشاريع", clear the filter
        hiddenInput.value = '';
    }
}
</script>
@endpush

