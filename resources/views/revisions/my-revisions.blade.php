@extends('layouts.app')

@section('title', 'تعديلاتي')

@push('styles')
<!-- SweetAlert2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<!-- Revisions Kanban CSS -->
<link rel="stylesheet" href="{{ asset('css/revisions/revisions-kanban.css') }}">
<!-- Revisions Modern CSS -->
<link rel="stylesheet" href="{{ asset('css/revisions/revisions-modern.css') }}?v={{ time() }}">
<!-- My Revisions CSS -->
<link rel="stylesheet" href="{{ asset('css/revisions/my-revisions.css') }}?v={{ time() }}">
@endpush

@section('content')
<div class="revisions-modern-container">
    <!-- Page Header -->
    <div class="revisions-page-header slide-up">
        <i class="fas fa-tasks header-icon"></i>
        <h1>
            <i class="fas fa-tasks"></i>
            تعديلاتي
        </h1>
        <p>التعديلات المسندة لي (كمنفذ أو مراجع)</p>

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
            <a href="{{ route('revision.page') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-right me-1"></i>
                جميع التعديلات
            </a>
            <button class="btn btn-primary" onclick="refreshMyRevisions()">
                <i class="fas fa-sync-alt me-1"></i>
                تحديث
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row stats-row fade-in mb-4" id="myStatsContainer">
        <!-- Will be populated by JavaScript -->
    </div>

    {{-- إحصائيات نقل التعديلات --}}
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

    <!-- Filters -->
    <div class="filter-section">
        <div class="row">
            <div class="col-md-2 mb-3">
                <label class="form-label">البحث</label>
                <input type="text" class="form-control" id="mySearchInput"
                       placeholder="البحث في العنوان والوصف...">
            </div>
            <div class="col-md-2 mb-3">
                <label class="form-label">المشروع</label>
                <input type="text" class="form-control" id="myProjectCodeFilter"
                       list="myProjectsList" placeholder="اختر المشروع...">
                <datalist id="myProjectsList">
                    <!-- Will be populated by JavaScript -->
                </datalist>
            </div>
            <div class="col-md-2 mb-3">
                <label class="form-label">الشهر</label>
                <input type="month" class="form-control" id="myMonthFilter">
            </div>
            <div class="col-md-2 mb-3">
                <label class="form-label">نوع التعديل</label>
                <select class="form-select" id="myRevisionTypeFilter">
                    <option value="">الكل</option>
                    <option value="project">مشروع</option>
                    <option value="task">مهمة</option>
                </select>
            </div>
            <div class="col-md-2 mb-3">
                <label class="form-label">المصدر</label>
                <select class="form-select" id="myRevisionSourceFilter">
                    <option value="">الكل</option>
                    <option value="internal">داخلي</option>
                    <option value="external">خارجي</option>
                </select>
            </div>
            <div class="col-md-1 mb-3">
                <label class="form-label">الحالة</label>
                <select class="form-select" id="myStatusFilter">
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
                <input type="date" class="form-control" id="myDeadlineFrom"
                       title="فلتر حسب ديدلاينك الشخصي (كمنفذ أو مراجع)">
                <small class="text-primary d-block mt-1">
                    <i class="fas fa-user-clock"></i> ديدلاينك الشخصي (منفذ/مراجع)
                </small>
            </div>
            <div class="col-md-2 mb-3">
                <label class="form-label">ديدلاين إلى</label>
                <input type="date" class="form-control" id="myDeadlineTo"
                       title="فلتر حسب ديدلاينك الشخصي (كمنفذ أو مراجع)">
            </div>
            <div class="col-md-1 mb-3">
                <label class="form-label">&nbsp;</label>
                <div class="d-flex flex-column gap-2">
                    <button class="btn btn-primary w-100" onclick="applyMyFilters()" title="تطبيق الفلتر">
                        <i class="fas fa-filter"></i>
                    </button>
                    <button class="btn btn-outline-secondary w-100" onclick="clearMyFilters()" title="مسح">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Projects Revisions Section -->
    <div class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="mb-0">
                <i class="fas fa-project-diagram me-2 text-info"></i>
                تعديلات المشاريع
            </h3>
            <span class="badge bg-info" id="projectsRevisionsCount">0</span>
        </div>

        <!-- Projects Revisions Table -->
        <div id="projectsRevisionsContainer">
            <!-- Will be populated by JavaScript -->
        </div>

        <!-- Projects Kanban Board -->
        <div id="projectsRevisionsKanbanBoard" style="display: none;">
            <!-- Will be populated by JavaScript -->
        </div>

        <!-- Projects Pagination -->
        <div id="projectsRevisionsPagination" class="d-flex justify-content-center mt-3">
            <!-- Will be populated by JavaScript -->
        </div>
    </div>

    <!-- Tasks Revisions Section -->
    <div class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="mb-0">
                <i class="fas fa-tasks me-2 text-primary"></i>
                تعديلات المهام
            </h3>
            <span class="badge bg-primary" id="tasksRevisionsCount">0</span>
        </div>

        <!-- Tasks Revisions Table -->
        <div id="tasksRevisionsContainer">
            <!-- Will be populated by JavaScript -->
        </div>

        <!-- Tasks Kanban Board -->
        <div id="tasksRevisionsKanbanBoard" style="display: none;">
            <!-- Will be populated by JavaScript -->
        </div>

        <!-- Tasks Pagination -->
        <div id="tasksRevisionsPagination" class="d-flex justify-content-center mt-3">
            <!-- Will be populated by JavaScript -->
        </div>
    </div>

    <!-- Kanban Board (Old - for backward compatibility) -->
    <div id="myRevisionsKanbanBoard" style="display: none;">
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
    <div id="myRevisionsPagination" class="d-flex justify-content-center mt-4">
        <!-- Will be populated by JavaScript -->
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

@endsection

@push('scripts')
<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Revisions JS Files -->
<script>
    // تعريف متغير AUTH_USER_ID للاستخدام في الـ JavaScript
    var AUTH_USER_ID = '{{ Auth::id() }}';
</script>
<script src="{{ asset('js/revisions/revisions-core.js') }}"></script>
<script src="{{ asset('js/revisions/revisions-work.js') }}"></script>
<!-- My Revisions JS -->
<script src="{{ asset('js/revisions/my-revisions.js') }}?v={{ time() }}"></script>
@endpush

