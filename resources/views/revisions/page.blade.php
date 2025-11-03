@extends('layouts.app')

@section('title', 'التعديلات')

@push('styles')
<!-- SweetAlert2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<!-- Revisions Kanban CSS -->
<link rel="stylesheet" href="{{ asset('css/revisions/revisions-kanban.css') }}">
<!-- Revisions Modern CSS -->
<link rel="stylesheet" href="{{ asset('css/revisions/revisions-modern.css') }}?v={{ time() }}">
<style>
    /* إخفاء scrollbar من الصفحة الرئيسية */
    html,
    body {
        scrollbar-width: none;
        /* Firefox */
        -ms-overflow-style: none;
        /* IE and Edge */
    }

    html::-webkit-scrollbar,
    body::-webkit-scrollbar {
        display: none;
        /* Chrome, Safari, Opera */
    }

    /* SweetAlert RTL Support */
    .rtl-swal {
        direction: rtl;
        text-align: right;
    }

    .swal2-html-container {
        text-align: right !important;
    }

    .revisions-table {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        border: 1px solid #eee;
        overflow: hidden;
        margin-bottom: 3rem;
        /* زيادة المسافة بين الجدول والفوتر */
    }

    .revisions-table table {
        margin: 0;
        border: none;
    }

    .table-responsive {
        border-radius: 12px;
        overflow-x: auto;
        /* Hide scrollbar for Chrome, Safari and Opera */
        scrollbar-width: none;
        /* Firefox */
        -ms-overflow-style: none;
        /* Internet Explorer 10+ */
    }

    .table-responsive::-webkit-scrollbar {
        display: none;
        /* Chrome, Safari and Opera */
    }

    .revisions-table thead {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .revisions-table thead th {
        border: none;
        padding: 15px 12px;
        font-weight: 600;
        font-size: 14px;
    }

    .revisions-table tbody tr {
        border-bottom: 1px solid #f5f5f5;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .revisions-table tbody tr:hover {
        background: #f8f9fa;
        transform: scale(1.001);
    }

    .revisions-table tbody tr.active {
        background: #e3f2fd;
        border-left: 4px solid #2196f3;
    }

    .revisions-table tbody td {
        border: none;
        padding: 12px;
        vertical-align: middle;
        font-size: 13px;
    }

    .revision-sidebar {
        position: fixed;
        top: 0;
        right: -500px;
        width: 480px;
        height: 100vh;
        background: white;
        box-shadow: -5px 0 25px rgba(0, 0, 0, 0.15);
        z-index: 1050;
        overflow-y: auto;
        transition: right 0.3s ease;
        /* إخفاء scrollbar مع الحفاظ على الـ scroll */
        scrollbar-width: none;
        /* Firefox */
        -ms-overflow-style: none;
        /* IE and Edge */
    }

    .revision-sidebar::-webkit-scrollbar {
        display: none;
        /* Chrome, Safari, Opera */
    }

    @media (max-width: 768px) {
        .revision-sidebar {
            width: 90%;
            right: -90%;
        }
    }

    .revision-sidebar.active {
        right: 0;
    }

    .sidebar-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1040;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }

    .sidebar-overlay.active {
        opacity: 1;
        visibility: visible;
    }

    .sidebar-header {
        padding: 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .sidebar-content {
        padding: 20px;
        direction: rtl;
        text-align: right;
    }

    /* ضمان محاذاة جميع العناصر لليمين في الشايد بار */
    .sidebar-content * {
        text-align: right !important;
        direction: rtl !important;
    }

    .sidebar-content .form-control,
    .sidebar-content .form-select,
    .sidebar-content .form-check,
    .sidebar-content .btn,
    .sidebar-content .alert,
    .sidebar-content .badge,
    .sidebar-content .list-group,
    .sidebar-content .list-group-item {
        text-align: right !important;
        direction: rtl !important;
    }

    .sidebar-content .d-flex {
        justify-content: flex-end !important;
    }

    .sidebar-content .row {
        direction: rtl;
    }

    .sidebar-content .col,
    .sidebar-content .col-md,
    .sidebar-content .col-sm,
    .sidebar-content .col-lg {
        text-align: right !important;
        direction: rtl !important;
    }

    .sidebar-close {
        background: none;
        border: none;
        color: white;
        font-size: 20px;
        cursor: pointer;
        float: right;
        line-height: 1;
    }

    .detail-section {
        margin-bottom: 25px;
        padding: 20px;
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        border: 1px solid #e9ecef;
        transition: all 0.3s ease;
    }

    .detail-section:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .detail-section h6 {
        margin-bottom: 15px;
        color: #495057;
        font-weight: 700;
        font-size: 1rem;
        display: flex;
        align-items: center;
        gap: 8px;
        padding-bottom: 10px;
        border-bottom: 2px solid #e9ecef;
        text-align: right;
        direction: rtl;
    }

    .detail-section h6 i {
        color: #667eea;
    }

    .detail-section p {
        margin: 0;
        color: #666;
        line-height: 1.5;
        text-align: right;
        direction: rtl;
    }

    .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
    }

    .status-pending {
        background: #fff3cd;
        color: #856404;
        border: 1px solid #ffecb5;
    }

    .status-approved {
        background: #d1eddc;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .status-rejected {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    /* Work status styles */
    .status-new {
        background: #e9ecef;
        color: #495057;
        border: 1px solid #dee2e6;
    }

    .status-in_progress {
        background: #cfe2ff;
        color: #084298;
        border: 1px solid #b6d4fe;
    }

    .status-paused {
        background: #fff3cd;
        color: #856404;
        border: 1px solid #ffecb5;
    }

    .status-completed {
        background: #d1eddc;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    /* Revision actions styling */
    .revision-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 8px;
        justify-content: flex-end;
        direction: rtl;
    }

    .revision-actions .btn {
        font-size: 13px;
        padding: 6px 12px;
        border-radius: 6px;
        transition: all 0.3s ease;
    }

    .revision-actions .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    /* Table action buttons */
    .revisions-table tbody td .btn-sm {
        font-size: 12px;
        padding: 4px 8px;
        min-width: 32px;
        transition: all 0.2s ease;
    }

    .revisions-table tbody td .btn-sm:hover {
        transform: scale(1.1);
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
    }

    .revisions-table tbody tr:hover {
        background: #f8f9fa;
    }

    /* Revision timer styling */
    .revision-timer {
        font-family: 'Courier New', monospace !important;
        font-weight: bold !important;
        color: #059669 !important;
        padding: 4px 8px !important;
        background: #dcfce7 !important;
        border-radius: 6px !important;
        font-size: 12px !important;
        text-align: center !important;
        border: 1px solid #bbf7d0 !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1) !important;
        transition: all 0.3s ease !important;
    }

    .revision-timer:hover {
        background: #bbf7d0 !important;
        transform: scale(1.05) !important;
    }

    /* Timer animation for active revisions */
    @keyframes timerPulse {
        0% {
            opacity: 1;
        }

        50% {
            opacity: 0.7;
        }

        100% {
            opacity: 1;
        }
    }

    .revision-timer {
        animation: timerPulse 2s infinite;
    }

    .source-badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 500;
    }

    .source-internal {
        background: #e3f2fd;
        color: #1976d2;
    }

    .source-external {
        background: #fff8e1;
        color: #f57c00;
    }

    .stats-card {
        background: white;
        color: #333;
        border-radius: 15px;
        padding: 25px;
        margin-bottom: 20px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        border: 1px solid #e9ecef;
        transition: all 0.3s ease;
    }



    .stats-item {
        text-align: center;
        padding: 18px;

        border-radius: 12px;
        margin: 5px;
        border: 1px solid #e9ecef;
        transition: all 0.3s ease;
        background: #fff;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        transform: translateY(-2px);
    }


    .stats-number {
        font-size: 2em;
        font-weight: bold;
        margin-bottom: 5px;
        color: #667eea;
    }

    .filter-section {
        background: white;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .nav-pills .nav-link {
        border-radius: 25px;
        padding: 12px 24px;
        margin-right: 8px;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .nav-pills .nav-link.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
    }

    .attachment-info {
        background: linear-gradient(135deg, #e3f2fd 0%, #f8f9fa 100%);
        border-radius: 10px;
        padding: 12px 15px;
        margin-top: 10px;
        border: 1px solid #bbdefb;
        box-shadow: 0 1px 4px rgba(0, 123, 255, 0.08);
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .user-avatar {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 14px;
        font-weight: bold;
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.4);
        border: 2px solid white;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 12px;
        margin: 20px 0;
    }

    .empty-state i {
        font-size: 4em;
        color: #ddd;
        margin-bottom: 20px;
    }

    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    }

    .spinner-border {
        width: 3rem;
        height: 3rem;
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

    /* Projects Status Table Styles */
    .projects-table-wrapper {
        margin-bottom: 20px;
    }

    .projects-status-table {
        background: white;
    }

    .projects-filter-tabs-row {
        background: #f8f9fa !important;
    }

    .projects-filter-tabs-row td {
        padding: 0 !important;
        border: none !important;
    }

    .projects-tab-btn {
        background: white !important;
        color: #495057 !important;
        border: 2px solid #e9ecef !important;
        font-weight: 500;
    }

    .projects-tab-btn:hover {
        background: #f8f9fa !important;
        border-color: #667eea !important;
        transform: translateY(-2px);
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.2);
    }

    .projects-tab-btn.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        color: white !important;
        border-color: #667eea !important;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    .projects-tab-btn.active .badge {
        background-color: rgba(255, 255, 255, 0.3) !important;
        color: white !important;
        border-color: rgba(255, 255, 255, 0.5) !important;
    }

    .projects-status-table tbody tr:not(.projects-filter-tabs-row):hover {
        background: #f8f9fa !important;
        transform: scale(1.01);
    }

    .projects-status-table tbody tr:not(.projects-filter-tabs-row) {
        border-bottom: 1px solid #e9ecef;
    }

    @media (max-width: 768px) {
        .projects-tab-btn {
            font-size: 12px;
            padding: 8px 10px !important;
        }

        .projects-status-table {
            font-size: 12px;
        }
    }
</style>
@endpush

@section('content')
<div class="revisions-modern-container">
    <!-- Page Header -->
    <div class="revisions-page-header slide-up">
        <i class="fas fa-edit header-icon"></i>
        <h1>
            <i class="fas fa-edit"></i>
            التعديلات
        </h1>
        <p>إدارة ومتابعة جميع التعديلات</p>

        <!-- Header Actions -->
        <div class="d-flex gap-2 mt-3" style="position: relative; z-index: 5;">
            <!-- أزرار التبديل بين Table و Kanban -->
            <div class="btn-group kanban-view-toggle" role="group">
                <button type="button" class="btn btn-outline-primary active" id="tableViewBtn">
                    <i class="fas fa-table me-1"></i>
                    جدول
                </button>
                <button type="button" class="btn btn-outline-primary" id="kanbanViewBtn">
                    <i class="fas fa-columns me-1"></i>
                    كانبان
                </button>
            </div>

            <a href="{{ route('revision.my-revisions-page') }}" class="btn btn-primary">
                <i class="fas fa-tasks me-1"></i>
                تعديلاتي
            </a>
            <a href="{{ route('revision.transfer-statistics') }}" class="btn btn-info">
                <i class="fas fa-exchange-alt me-1"></i>
                إحصائيات النقل
            </a>
            <button class="btn btn-success" onclick="showAddRevisionModal()">
                <i class="fas fa-plus me-1"></i>
                إضافة تعديل جديد
            </button>
            <button class="btn btn-primary" onclick="refreshData()">
                <i class="fas fa-sync-alt me-1"></i>
                تحديث
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row stats-row fade-in mb-4" id="statsContainer">
        <!-- Will be populated by JavaScript -->
    </div>

    {{-- إحصائيات نقل التعديلات --}}
    <div class="row stats-row fade-in mb-4" id="transferStatsContainer" style="display: none;">
        <!-- Will be populated by JavaScript if user has transfers -->
    </div>

    <!-- Revision Transfer Statistics Section -->
    @if(isset($revisionTransferStats) && $revisionTransferStats['has_transfers'])
    <div class="mb-4">
        <div class="stats-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0" style="color: #495057;">
                    <i class="fas fa-exchange-alt me-2" style="color: #667eea;"></i>
                    إحصائيات نقل التعديلات (مهام إضافية)
                </h4>
            </div>

            <div class="row g-3">
                <!-- Received Revisions Card -->
                <div class="col-md-4">
                    <div class="stats-item">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div>
                                <i class="fas fa-arrow-down" style="font-size: 2em; color: #28a745;"></i>
                            </div>
                            <div class="text-end">
                                <div class="stats-number">{{ $revisionTransferStats['transferred_to_me'] }}</div>
                                <div style="font-size: 0.9rem; color: #6c757d; font-weight: 500;">تعديلات منقولة إليّ</div>
                            </div>
                        </div>
                        <div class="mt-2" style="font-size: 0.85rem; color: #6c757d;">
                            <i class="fas fa-wrench" style="color: #f093fb;"></i> منفذ: {{ $revisionTransferStats['executor_transferred_to_me'] }} |
                            <i class="fas fa-check-circle" style="color: #4facfe;"></i> مراجع: {{ $revisionTransferStats['reviewer_transferred_to_me'] }}
                        </div>
                    </div>
                </div>

                <!-- Sent Revisions Card -->
                <div class="col-md-4">
                    <div class="stats-item">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div>
                                <i class="fas fa-arrow-up" style="font-size: 2em; color: #dc3545;"></i>
                            </div>
                            <div class="text-end">
                                <div class="stats-number">{{ $revisionTransferStats['transferred_from_me'] }}</div>
                                <div style="font-size: 0.9rem; color: #6c757d; font-weight: 500;">تعديلات منقولة مني</div>
                            </div>
                        </div>
                        <div class="mt-2" style="font-size: 0.85rem; color: #6c757d;">
                            <i class="fas fa-wrench" style="color: #f093fb;"></i> منفذ: {{ $revisionTransferStats['executor_transferred_from_me'] }} |
                            <i class="fas fa-check-circle" style="color: #4facfe;"></i> مراجع: {{ $revisionTransferStats['reviewer_transferred_from_me'] }}
                        </div>
                    </div>
                </div>

                <!-- Additional Tasks Card -->
                <div class="col-md-4">
                    <div class="stats-item">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div>
                                <i class="fas fa-plus-circle" style="font-size: 2em; color: #ffc107;"></i>
                            </div>
                            <div class="text-end">
                                <div class="stats-number">{{ $revisionTransferStats['transferred_to_me'] }}</div>
                                <div style="font-size: 0.9rem; color: #6c757d; font-weight: 500;">تعديلات إضافية</div>
                            </div>
                        </div>
                        <div class="mt-2" style="font-size: 0.85rem; color: #6c757d;">
                            <i class="fas fa-medal" style="color: #ffc107;"></i> تعديلات لم تكن من نصيبك الأصلي
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Tabs Navigation -->
    <div class="row">
        <div class="col-12">
            <ul class="nav nav-pills nav-justified mb-4" id="revisionTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="all-revisions-tab" data-bs-toggle="pill"
                        data-bs-target="#all-revisions" type="button" role="tab">
                        <i class="fas fa-list me-2"></i>
                        جميع التعديلات
                        <span class="badge bg-light text-primary ms-2" id="allRevisionsCount">0</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="my-created-revisions-tab" data-bs-toggle="pill"
                        data-bs-target="#my-created-revisions" type="button" role="tab">
                        <i class="fas fa-user-edit me-2"></i>
                        التعديلات التي أضفتها
                        <span class="badge bg-light text-primary ms-2" id="myCreatedRevisionsCount">0</span>
                    </button>
                </li>
            </ul>
        </div>
    </div>

    <!-- Tab Content -->
    <div class="tab-content" id="revisionTabsContent">
        <!-- All Revisions Tab -->
        <div class="tab-pane fade show active" id="all-revisions" role="tabpanel">
            <!-- Filters -->
            <div class="filter-section">
                <div class="row">
                    <div class="col-md-2 mb-3">
                        <label class="form-label">البحث</label>
                        <input type="text" class="form-control" id="allSearchInput"
                            placeholder="البحث في العنوان والوصف...">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">المشروع</label>
                        <input type="text" class="form-control" id="allProjectCodeFilter"
                            list="allProjectsList" placeholder="اختر المشروع...">
                        <datalist id="allProjectsList">
                            <!-- Will be populated by JavaScript -->
                        </datalist>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">الشهر</label>
                        <input type="month" class="form-control" id="allMonthFilter">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">نوع التعديل</label>
                        <select class="form-select" id="allRevisionTypeFilter">
                            <option value="">الكل</option>
                            <option value="project">مشروع</option>
                            <option value="task">مهمة</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">المصدر</label>
                        <select class="form-select" id="allRevisionSourceFilter">
                            <option value="">الكل</option>
                            <option value="internal">داخلي</option>
                            <option value="external">خارجي</option>
                        </select>
                    </div>
                    <div class="col-md-1 mb-3">
                        <label class="form-label">الحالة</label>
                        <select class="form-select" id="allStatusFilter">
                            <option value="">الكل</option>
                            <optgroup label="حالة العمل">
                                <option value="new">جديد</option>
                                <option value="in_progress">جاري العمل</option>
                                <option value="paused">متوقف</option>
                                <option value="completed">مكتمل</option>
                            </optgroup>
                            <optgroup label="حالة الموافقة">
                                <option value="pending">في الانتظار</option>
                                <option value="approved">موافق عليه</option>
                                <option value="rejected">مرفوض</option>
                            </optgroup>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">
                            <i class="fas fa-calendar-alt me-1"></i>
                            الديدلاين العام للتعديل
                        </label>
                        <div class="alert alert-warning p-2 mb-2" style="font-size: 0.85rem; line-height: 1.5; border-right: 3px solid #ffc107;">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            <strong>ملاحظة مهمة:</strong> هذا الفلتر يفلتر حسب <strong>التاريخ النهائي للديدلاين العام</strong> للتعديل ككل (revision_deadline)، وليس الديدلاين الشخصي (ديدلاينك كمنفذ أو مراجع).
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="date" class="form-control" id="allDeadlineFrom"
                                    placeholder="من تاريخ"
                                    title="تاريخ البداية للديدلاين العام">
                                <small class="text-muted d-block mt-1">من</small>
                            </div>
                            <div class="col-6">
                                <input type="date" class="form-control" id="allDeadlineTo"
                                    placeholder="إلى تاريخ"
                                    title="تاريخ النهاية للديدلاين العام">
                                <small class="text-muted d-block mt-1">إلى</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-1 mb-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex flex-column gap-2">
                            <button class="btn btn-primary w-100" onclick="applyFilters('all')" title="تطبيق الفلتر">
                                <i class="fas fa-filter"></i>
                            </button>
                            <button class="btn btn-outline-secondary w-100" onclick="clearFilters('all')" title="مسح">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Revisions List (Table View) -->
            <div id="allRevisionsContainer">
                <!-- Will be populated by JavaScript -->
            </div>

            <!-- Kanban Board -->
            <div id="revisionsKanbanBoard">
                <div class="kanban-columns">
                    <!-- New Column -->
                    <div class="kanban-column status-new" id="kanban-column-new">
                        <div class="kanban-column-header">
                            <div class="kanban-column-title">
                                <i class="fas fa-plus-circle"></i>
                                جديد
                            </div>
                            <span class="kanban-column-count">0</span>
                        </div>
                        <div class="kanban-column-cards">
                            <!-- Will be populated by JavaScript -->
                        </div>
                    </div>

                    <!-- In Progress Column -->
                    <div class="kanban-column status-in-progress" id="kanban-column-in_progress">
                        <div class="kanban-column-header">
                            <div class="kanban-column-title">
                                <i class="fas fa-spinner"></i>
                                جاري العمل
                            </div>
                            <span class="kanban-column-count">0</span>
                        </div>
                        <div class="kanban-column-cards">
                            <!-- Will be populated by JavaScript -->
                        </div>
                    </div>

                    <!-- Paused Column -->
                    <div class="kanban-column status-paused" id="kanban-column-paused">
                        <div class="kanban-column-header">
                            <div class="kanban-column-title">
                                <i class="fas fa-pause-circle"></i>
                                متوقف مؤقتاً
                            </div>
                            <span class="kanban-column-count">0</span>
                        </div>
                        <div class="kanban-column-cards">
                            <!-- Will be populated by JavaScript -->
                        </div>
                    </div>

                    <!-- Completed Column -->
                    <div class="kanban-column status-completed" id="kanban-column-completed">
                        <div class="kanban-column-header">
                            <div class="kanban-column-title">
                                <i class="fas fa-check-circle"></i>
                                مكتمل
                            </div>
                            <span class="kanban-column-count">0</span>
                        </div>
                        <div class="kanban-column-cards">
                            <!-- Will be populated by JavaScript -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div id="allRevisionsPagination" class="d-flex justify-content-center mt-4">
                <!-- Will be populated by JavaScript -->
            </div>
        </div>

        <!-- My Created Revisions Tab -->
        <div class="tab-pane fade" id="my-created-revisions" role="tabpanel">
            <!-- Filters -->
            <div class="filter-section">
                <div class="row">
                    <div class="col-md-2 mb-3">
                        <label class="form-label">البحث</label>
                        <input type="text" class="form-control" id="myCreatedSearchInput"
                            placeholder="البحث في العنوان والوصف...">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">المشروع</label>
                        <input type="text" class="form-control" id="myCreatedProjectCodeFilter"
                            list="myCreatedProjectsList" placeholder="اختر المشروع...">
                        <datalist id="myCreatedProjectsList">
                            <!-- Will be populated by JavaScript -->
                        </datalist>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">الشهر</label>
                        <input type="month" class="form-control" id="myCreatedMonthFilter">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">نوع التعديل</label>
                        <select class="form-select" id="myCreatedRevisionTypeFilter">
                            <option value="">الكل</option>
                            <option value="project">مشروع</option>
                            <option value="task">مهمة</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">المصدر</label>
                        <select class="form-select" id="myCreatedRevisionSourceFilter">
                            <option value="">الكل</option>
                            <option value="internal">داخلي</option>
                            <option value="external">خارجي</option>
                        </select>
                    </div>
                    <div class="col-md-1 mb-3">
                        <label class="form-label">الحالة</label>
                        <select class="form-select" id="myCreatedStatusFilter">
                            <option value="">الكل</option>
                            <optgroup label="حالة العمل">
                                <option value="new">جديد</option>
                                <option value="in_progress">جاري العمل</option>
                                <option value="paused">متوقف</option>
                                <option value="completed">مكتمل</option>
                            </optgroup>
                            <optgroup label="حالة الموافقة">
                                <option value="pending">في الانتظار</option>
                                <option value="approved">موافق عليه</option>
                                <option value="rejected">مرفوض</option>
                            </optgroup>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">ديدلاين من</label>
                        <input type="date" class="form-control" id="myCreatedDeadlineFrom"
                            title="فلتر حسب الديدلاين العام للتعديلات">
                        <small class="text-success d-block mt-1">
                            <i class="fas fa-calendar-check"></i> الديدلاين العام للتعديل
                        </small>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">ديدلاين إلى</label>
                        <input type="date" class="form-control" id="myCreatedDeadlineTo"
                            title="فلتر حسب الديدلاين العام للتعديلات">
                    </div>
                    <div class="col-md-1 mb-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex flex-column gap-2">
                            <button class="btn btn-primary w-100" onclick="applyFilters('myCreated')" title="تطبيق الفلتر">
                                <i class="fas fa-filter"></i>
                            </button>
                            <button class="btn btn-outline-secondary w-100" onclick="clearFilters('myCreated')" title="مسح">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Revisions List -->
            <div id="myCreatedRevisionsContainer">
                <!-- Will be populated by JavaScript -->
            </div>

            <!-- Pagination -->
            <div id="myCreatedRevisionsPagination" class="d-flex justify-content-center mt-4">
                <!-- Will be populated by JavaScript -->
            </div>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div class="loading-overlay d-none" id="loadingOverlay">
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">جاري التحميل...</span>
    </div>
</div>

<!-- Revision Details Sidebar -->
<div class="revision-sidebar" id="revisionSidebar">
    <div class="sidebar-header">
        <button class="sidebar-close" onclick="closeSidebar()">
            <i class="fas fa-times"></i>
        </button>
        <h5 class="mb-0">تفاصيل التعديل</h5>
    </div>
    <div class="sidebar-content" id="sidebarContent">
        <!-- Will be populated by JavaScript -->
    </div>
</div>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- Add Revision Sidebar -->
<div class="revision-sidebar" id="addRevisionSidebar" style="right: -600px; width: 580px;">
    <div class="sidebar-header">
        <button class="sidebar-close" onclick="closeAddRevisionSidebar()">
            <i class="fas fa-times"></i>
        </button>
        <h5 class="mb-0">
            <i class="fas fa-plus-circle me-2"></i>
            إضافة تعديل جديد
        </h5>
    </div>
    <div class="sidebar-content" style="padding: 20px;">
        <!-- Add Revision Form -->
        <form id="addRevisionForm" onsubmit="event.preventDefault(); saveNewRevision();">

            <!-- نوع التعديل -->
            <div class="mb-3">
                <label class="form-label fw-bold">
                    <i class="fas fa-tag me-1"></i>
                    نوع التعديل <span class="text-danger">*</span>
                </label>
                <select id="newRevisionType" class="form-control" required onchange="toggleRevisionTypeOptions()">
                    <option value="">-- اختر النوع --</option>
                    <option value="project">مشروع</option>
                </select>
            </div>

            <!-- مصدر التعديل -->
            <div class="mb-3">
                <label class="form-label fw-bold">
                    <i class="fas fa-compass me-1"></i>
                    مصدر التعديل <span class="text-danger">*</span>
                </label>
                <select id="newRevisionSource" class="form-control" required>
                    <option value="internal">داخلي</option>
                    <option value="external">خارجي</option>
                </select>
            </div>

            <!-- ديدلاين التعديل العام -->
            <div class="mb-3">
                <label class="form-label fw-bold">
                    <i class="fas fa-calendar-times me-1"></i>
                    ديدلاين التعديل <span class="text-muted" style="font-size: 11px;">(اختياري)</span>
                </label>
                <input type="datetime-local"
                    id="newRevisionDeadline"
                    class="form-control"
                    min=""
                    onchange="validateRevisionDeadlineOrder()">
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    تاريخ ووقت الانتهاء المتوقع للتعديل ككل (يجب أن يكون بعد جميع الديدلاينات الأخرى)
                </small>
            </div>

            <!-- اختيار المشروع (يظهر للمشروعات فقط) -->
            <div class="mb-3 d-none" id="projectSelectContainer">
                <label class="form-label fw-bold">
                    <i class="fas fa-project-diagram me-1"></i>
                    المشروع <span class="text-danger">*</span>
                </label>
                <input type="text"
                    id="newRevisionProjectSearch"
                    class="form-control datalist-input"
                    list="projectsList"
                    placeholder="ابحث عن المشروع..."
                    autocomplete="off"
                    oninput="handleProjectSelection()">
                <datalist id="projectsList">
                    @if(isset($projects))
                    @foreach($projects as $project)
                    <option value="{{ ($project->code ? $project->code . ' - ' : '') . $project->name }}" data-project-id="{{ $project->id }}">
                    </option>
                    @endforeach
                    @endif
                </datalist>
                <input type="hidden" id="newRevisionProjectId">
                <small class="text-muted d-block mt-1">
                    <i class="fas fa-search me-1"></i>
                    ابدأ بكتابة اسم المشروع للبحث
                </small>
            </div>

            <!-- قسم المسؤوليات (يظهر للمشروعات فقط) -->
            <div class="mb-3 d-none" id="responsibilitySection">
                <!-- المسؤول عن الخطأ -->
                <div class="mb-3">
                    <label class="form-label">
                        <span class="text-danger">⚠️ المسؤول</span>
                        <span class="text-muted" style="font-size: 11px;">(اللي هيتحاسب)</span>
                    </label>
                    <input type="text"
                        id="newResponsibleUserSearch"
                        class="form-control datalist-input"
                        list="responsibleUsersList"
                        placeholder="ابحث عن المسؤول..."
                        autocomplete="off"
                        oninput="handleResponsibleSelection()">
                    <datalist id="responsibleUsersList">
                        <!-- Will be populated by JavaScript -->
                    </datalist>
                    <input type="hidden" id="newResponsibleUserId">
                    <small class="text-muted">
                        <i class="fas fa-search me-1"></i>
                        ابدأ بكتابة اسم الموظف - الموجودون في المشروع معلمون بـ
                        <span class="badge bg-success" style="font-size: 10px;">من المشروع</span>
                    </small>
                </div>

                <!-- المنفذ (اللي هيصلح) -->
                <div class="mb-3">
                    <label class="form-label">
                        <span class="text-primary">🔨 المنفذ</span>
                        <span class="text-muted" style="font-size: 11px;">(اللي هيصلح الغلط)</span>
                    </label>
                    <input type="text"
                        id="newExecutorUserSearch"
                        class="form-control datalist-input"
                        list="executorUsersList"
                        placeholder="ابحث عن المنفذ..."
                        autocomplete="off"
                        oninput="handleExecutorSelection()">
                    <datalist id="executorUsersList">
                        <!-- Will be populated by JavaScript -->
                    </datalist>
                    <input type="hidden" id="newExecutorUserId">
                    <small class="text-muted">
                        <i class="fas fa-search me-1"></i>
                        ابدأ بكتابة اسم الموظف - الموجودون في المشروع معلمون بـ
                        <span class="badge bg-success" style="font-size: 10px;">من المشروع</span>
                    </small>
                </div>

                <!-- ديدلاين المنفذ -->
                <div class="mb-3" id="executorDeadlineContainer" style="display: none;">
                    <label class="form-label">
                        <span class="text-primary">⏰ ديدلاين المنفذ</span>
                        <span class="text-muted" style="font-size: 11px;">(اختياري)</span>
                    </label>
                    <input type="datetime-local"
                        id="newExecutorDeadline"
                        class="form-control"
                        min=""
                        onchange="validateExecutorDeadlineOrder()">
                    <small class="text-muted">
                        <i class="fas fa-clock me-1"></i>
                        تاريخ ووقت الانتهاء المتوقع للمنفذ (يجب أن يكون قبل المراجع الأول)
                    </small>
                </div>

                <div class="mb-3">
                    <label class="form-label d-flex justify-content-between align-items-center">
                        <span>
                            <span class="text-success">✅ المراجعين</span>
                            <span class="text-muted" style="font-size: 11px;">(مراجعة متسلسلة حسب الترتيب)</span>
                        </span>
                        <button type="button" class="btn btn-sm btn-primary" onclick="addReviewerRow()">
                            <i class="fas fa-plus me-1"></i>
                            إضافة مراجع
                        </button>
                    </label>

                    <!-- قائمة المراجعين -->
                    <div id="reviewersList" class="border rounded p-2 bg-light" style="max-height: 300px; overflow-y: auto;">
                        <div class="text-center text-muted py-3" id="noReviewersMsg">
                            <i class="fas fa-user-check fa-2x mb-2"></i>
                            <p class="mb-0">لم يتم إضافة مراجعين بعد</p>
                            <small>اضغط "إضافة مراجع" لبدء إضافة المراجعين</small>
                        </div>
                    </div>

                    <!-- Datalist للبحث -->
                    <datalist id="reviewerUsersList">
                        <!-- Will be populated by JavaScript -->
                    </datalist>

                    <small class="text-muted d-block mt-2">
                        <i class="fas fa-info-circle me-1"></i>
                        المراجع الأول سيتم إشعاره عند انتهاء المنفذ، ثم المراجع الثاني بعد انتهاء الأول، وهكذا...
                        <br>
                        <i class="fas fa-search me-1"></i>
                        الموجودون في المشروع معلمون بـ <span class="badge bg-success" style="font-size: 10px;">من المشروع</span>
                    </small>
                </div>

                <!-- ملاحظات المسؤولية -->
                <div class="mb-3">
                    <label class="form-label">
                        <span>📝 ملاحظات المسؤولية</span>
                        <span class="text-muted" style="font-size: 11px;">(سبب التعديل)</span>
                    </label>
                    <textarea id="newResponsibilityNotes" class="form-control" rows="2"
                        placeholder="اذكر سبب التعديل والخطأ الذي حدث..." maxlength="2000"></textarea>
                    <small class="text-muted">توثيق سبب الخطأ الذي أدى للتعديل</small>
                </div>
            </div>

            <!-- العنوان -->
            <div class="mb-3">
                <label class="form-label fw-bold">
                    <i class="fas fa-heading me-1"></i>
                    عنوان التعديل <span class="text-danger">*</span>
                </label>
                <input type="text" id="newRevisionTitle" class="form-control"
                    placeholder="مثال: تعديل ألوان الشعار" required maxlength="255">
                <small class="text-muted">الحد الأقصى 255 حرف</small>
            </div>

            <!-- الوصف -->
            <div class="mb-3">
                <label class="form-label fw-bold">
                    <i class="fas fa-align-left me-1"></i>
                    وصف التعديل <span class="text-danger">*</span>
                </label>
                <textarea id="newRevisionDescription" class="form-control" rows="4"
                    placeholder="اكتب وصف تفصيلي للتعديل المطلوب..." required maxlength="5000"></textarea>
                <small class="text-muted">الحد الأقصى 5000 حرف</small>
            </div>

            <!-- ملاحظات إضافية -->
            <div class="mb-3">
                <label class="form-label fw-bold">
                    <i class="fas fa-sticky-note me-1"></i>
                    ملاحظات إضافية
                </label>
                <textarea id="newRevisionNotes" class="form-control" rows="3"
                    placeholder="أي ملاحظات أخرى..." maxlength="2000"></textarea>
            </div>

            <!-- المرفقات -->
            <div class="mb-3">
                <label class="form-label fw-bold">
                    <i class="fas fa-paperclip me-1"></i>
                    المرفق
                </label>

                <!-- خيارات نوع المرفق (تظهر فقط لتعديلات المهام) -->
                <div class="mb-2" id="attachmentTypeOptions" style="display: none;">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="newAttachmentType"
                            id="newAttachmentTypeFile" value="file" checked onchange="toggleNewAttachmentType('file')">
                        <label class="form-check-label" for="newAttachmentTypeFile">
                            ملف
                        </label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="newAttachmentType"
                            id="newAttachmentTypeLink" value="link" onchange="toggleNewAttachmentType('link')">
                        <label class="form-check-label" for="newAttachmentTypeLink">
                            رابط
                        </label>
                    </div>
                </div>

                <!-- File Upload (للمهام فقط) -->
                <div id="newFileContainer">
                    <input type="file" id="newRevisionAttachment" class="form-control"
                        accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip,.rar">
                    <small class="text-muted">الحد الأقصى: 10 ميجابايت</small>
                </div>

                <!-- Link Input (للمشاريع فقط) -->
                <div id="newLinkContainer" style="display: none;">
                    <input type="url" id="newRevisionAttachmentLink" class="form-control"
                        placeholder="https://example.com/file">
                    <small class="text-muted">أدخل رابط خارجي للمرفق</small>
                </div>
            </div>

            <!-- أزرار الحفظ -->
            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-success flex-fill" id="saveRevisionBtn">
                    <i class="fas fa-save me-1"></i>
                    <span id="saveRevisionBtnText">حفظ التعديل</span>
                    <span id="saveRevisionBtnSpinner" class="spinner-border spinner-border-sm ms-1 d-none" role="status">
                        <span class="visually-hidden">جاري الحفظ...</span>
                    </span>
                </button>
                <button type="button" class="btn btn-secondary" onclick="closeAddRevisionSidebar()">
                    <i class="fas fa-times me-1"></i>
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Add Revision Sidebar Overlay -->
<div class="sidebar-overlay" id="addRevisionOverlay" onclick="closeAddRevisionSidebar()" style="visibility: hidden; opacity: 0;"></div>

@endsection

@push('scripts')
<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Revisions JS Files -->
<script>
    // تعريف متغير AUTH_USER_ID للاستخدام في الـ JavaScript
    var AUTH_USER_ID = '{{ Auth::id() }}';

    // Store projects data globally
    let availableProjects = [];

    // Load projects list on page load
    document.addEventListener('DOMContentLoaded', function() {
        const datalist = document.getElementById('projectsList');
        if (datalist) {
            const options = datalist.querySelectorAll('option');
            options.forEach(option => {
                availableProjects.push({
                    id: option.getAttribute('data-project-id'),
                    name: option.value, // الكود + الاسم
                    displayValue: option.value
                });
            });
        }
    });

    // Handle project selection from datalist
    function handleProjectSelection() {
        const searchInput = document.getElementById('newRevisionProjectSearch');
        const hiddenInput = document.getElementById('newRevisionProjectId');

        if (!searchInput || !hiddenInput) return;

        // Find project by name (الكود + الاسم)
        const selectedProject = availableProjects.find(project => project.name === searchInput.value);

        if (selectedProject) {
            hiddenInput.value = selectedProject.id;

            // Call the original function to load participants
            if (typeof loadProjectParticipantsForRevision === 'function') {
                loadProjectParticipantsForRevision();
            }
        } else {
            hiddenInput.value = '';
        }
    }
</script>
<script src="{{ asset('js/revisions/revisions-core.js') }}"></script>
<script src="{{ asset('js/revisions/revisions-work.js') }}"></script>
<script src="{{ asset('js/revisions/revisions-add.js') }}"></script>
<script src="{{ asset('js/revisions/revisions-kanban.js') }}"></script>

@endpush