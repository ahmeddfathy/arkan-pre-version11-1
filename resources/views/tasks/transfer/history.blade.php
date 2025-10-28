@extends('layouts.app')

@section('title', 'تاريخ نقل المهام')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/projects-dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('css/transfer-history.css') }}">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
@endpush

@section('content')
<div class="modern-dashboard">
    <!-- Header Section -->
    <div class="dashboard-header-modern">
        <div class="header-content">
            <div class="header-left">
                <div class="page-title">
                    <div class="title-icon">
                        <i class="fas fa-history"></i>
                    </div>
                    <div class="title-text">
                        <h1>تاريخ نقل المهام</h1>
                        <p>جميع عمليات نقل المهام في النظام مع إمكانية الفلترة والبحث</p>
                    </div>
                </div>
            </div>
            <div class="header-actions">
                <button class="action-btn secondary" onclick="refreshData()">
                    <i class="fas fa-sync-alt"></i>
                    تحديث
                </button>
                <button class="action-btn outline" onclick="exportData()">
                    <i class="fas fa-download"></i>
                    تصدير
                </button>
                <a href="{{ route('company-projects.dashboard') }}" class="action-btn primary">
                    <i class="fas fa-chart-line"></i>
                    العودة للوحة التحكم
                </a>
            </div>
        </div>
    </div>

    <!-- Transfer Statistics -->
    <div class="stats-grid">
        <div class="stat-card-modern primary">
            <div class="stat-icon">
                <i class="fas fa-exchange-alt"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number">{{ isset($totalTransfers) ? $totalTransfers['total'] : 0 }}</div>
                <div class="stat-label">إجمالي عمليات النقل</div>
                <div class="stat-trend">
                    <i class="fas fa-arrow-up"></i>
                    {{ isset($transfersByType) ? ($transfersByType['positive_count'] ?? 0) : 0 }} إيجابي
                </div>
            </div>
        </div>

        <div class="stat-card-modern success">
            <div class="stat-icon">
                <i class="fas fa-tasks"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number">{{ isset($totalTransfers) ? $totalTransfers['regular'] : 0 }}</div>
                <div class="stat-label">المهام العادية</div>
                <div class="stat-trend">
                    <i class="fas fa-check-circle"></i>
                    مهام منقولة
                </div>
            </div>
        </div>

        <div class="stat-card-modern info">
            <div class="stat-icon">
                <i class="fas fa-layer-group"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number">{{ isset($totalTransfers) ? $totalTransfers['template'] : 0 }}</div>
                <div class="stat-label">مهام القوالب</div>
                <div class="stat-trend">
                    <i class="fas fa-copy"></i>
                    قوالب منقولة
                </div>
            </div>
        </div>

        <div class="stat-card-modern warning">
            <div class="stat-icon">
                <i class="fas fa-star"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number">{{ isset($transfersByType) ? $transfersByType['total_points'] : 0 }}</div>
                <div class="stat-label">إجمالي النقاط</div>
                <div class="stat-trend">
                    <i class="fas fa-trophy"></i>
                    نقاط النقل
                </div>
            </div>
        </div>
    </div>


    <!-- فلاتر البحث المتقدمة -->
    <div class="section-modern glow-section">
        <div class="section-header glow-header">
            <h2>
                <i class="fas fa-filter"></i>
                فلاتر البحث المتقدمة
            </h2>
        </div>
        <div class="filters-container glow-container">
            <form method="GET" action="{{ route('task-transfers.history') }}" id="filterForm">
                <div class="filters-grid">
                    <div class="filter-group glow-group">
                        <label for="from_date" class="glow-label">
                            <i class="fas fa-calendar-alt"></i>
                            من تاريخ
                        </label>
                        <input type="date" class="form-input glow-input" id="from_date" name="from_date" value="{{ request('from_date') }}">
                    </div>
                    <div class="filter-group glow-group">
                        <label for="to_date" class="glow-label">
                            <i class="fas fa-calendar-alt"></i>
                            إلى تاريخ
                        </label>
                        <input type="date" class="form-input glow-input" id="to_date" name="to_date" value="{{ request('to_date') }}">
                    </div>
                    <div class="filter-group glow-group">
                        <label for="from_user_id" class="glow-label">
                            <i class="fas fa-user"></i>
                            من مستخدم
                        </label>
                        <select class="form-select glow-select" id="from_user_id" name="from_user_id">
                            <option value="">جميع المستخدمين</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ request('from_user_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }} @if($user->employee_id)({{ $user->employee_id }})@endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-group glow-group">
                        <label for="to_user_id" class="glow-label">
                            <i class="fas fa-user-plus"></i>
                            إلى مستخدم
                        </label>
                        <select class="form-select glow-select" id="to_user_id" name="to_user_id">
                            <option value="">جميع المستخدمين</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ request('to_user_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }} @if($user->employee_id)({{ $user->employee_id }})@endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-group glow-group">
                        <label for="task_type" class="glow-label">
                            <i class="fas fa-layer-group"></i>
                            نوع المهمة
                        </label>
                        <select class="form-select glow-select" id="task_type" name="task_type">
                            <option value="">جميع الأنواع</option>
                            <option value="task" {{ request('task_type') == 'task' ? 'selected' : '' }}>مهام عادية</option>
                            <option value="template_task" {{ request('task_type') == 'template_task' ? 'selected' : '' }}>مهام القوالب</option>
                        </select>
                    </div>
                    <!-- فلتر الموسم تم إزالته مؤقتاً -->
                    <div class="filter-group glow-group">
                        <label for="per_page" class="glow-label">
                            <i class="fas fa-list-ol"></i>
                            عدد العناصر
                        </label>
                        <select class="form-select glow-select" id="per_page" name="per_page">
                            <option value="all" {{ request('per_page', 'all') == 'all' ? 'selected' : '' }}>الجميع</option>
                            <option value="10" {{ request('per_page') == '10' ? 'selected' : '' }}>10</option>
                            <option value="15" {{ request('per_page') == '15' ? 'selected' : '' }}>15</option>
                            <option value="25" {{ request('per_page') == '25' ? 'selected' : '' }}>25</option>
                            <option value="50" {{ request('per_page') == '50' ? 'selected' : '' }}>50</option>
                        </select>
                    </div>
                </div>
                <div class="filter-actions glow-actions">
                    <button type="submit" class="btn-apply-filters glow-btn glow-btn-primary">
                        <i class="fas fa-search"></i>
                        تطبيق الفلتر
                    </button>
                    <a href="{{ route('task-transfers.history') }}" class="btn-reset-filters glow-btn glow-btn-secondary">
                        <i class="fas fa-undo"></i>
                        إعادة تعيين
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Transfer Results -->
    <div class="section-modern">
        <div class="section-header">
            <h2>
                <i class="fas fa-list"></i>
                نتائج البحث
            </h2>
            @if($transfers->count() > 0)
            <span class="section-count">{{ $transfers->total() }}</span>
            @endif
        </div>

        @if($transfers->count() > 0)
            <div class="results-info">
                <span class="results-text">
                    عرض {{ $transfers->firstItem() }} إلى {{ $transfers->lastItem() }} من أصل {{ $transfers->total() }} نتيجة
                </span>
            </div>

            <div class="table-responsive">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th>النقاط</th>
                            <th>اسم المهمة</th>
                            <th>نوع المهمة</th>
                            <th>الاتجاه</th>
                            <th>المشروع</th>
                            <th>من</th>
                            <th>إلى</th>
                            <th>تاريخ النقل</th>
                            <th>السبب</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transfers as $transfer)
                        <tr>
                            <td>
                                <div class="points-cell">
                                    <span class="points-number">{{ $transfer->transfer_points }}</span>
                                    <span class="points-label">نقطة</span>
                                </div>
                            </td>
                            <td>
                                <div class="task-name-cell">
                                    <strong>{{ $transfer->task_name }}</strong>
                                </div>
                            </td>
                            <td>
                                @if($transfer->transfer_type == 'regular')
                                    <span class="task-type-badge regular">
                                        <i class="fas fa-tasks"></i>
                                        مهمة عادية
                                    </span>
                                @else
                                    <span class="task-type-badge template">
                                        <i class="fas fa-layer-group"></i>
                                        مهمة قالب
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($transfer->transfer_direction == 'positive')
                                    <span class="transfer-direction-badge positive">
                                        <i class="fas fa-plus"></i>
                                        إيجابي
                                    </span>
                                @else
                                    <span class="transfer-direction-badge negative">
                                        <i class="fas fa-minus"></i>
                                        سلبي
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($transfer->project_name)
                                    <span class="project-badge">
                                        <i class="fas fa-project-diagram"></i>
                                        {{ $transfer->project_name }}
                                    </span>
                                @else
                                    <span class="text-muted">غير محدد</span>
                                @endif
                            </td>
                            <td>
                                <div class="user-cell">
                                    <div class="user-avatar-small">
                                        {{ substr($transfer->from_user_name, 0, 1) }}
                                    </div>
                                    <div class="user-details">
                                        <div class="user-name">{{ $transfer->from_user_name }}</div>
                                        @if($transfer->from_user_employee_id)
                                            <small class="text-muted">{{ $transfer->from_user_employee_id }}</small>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="user-cell">
                                    <div class="user-avatar-small to-user">
                                        {{ substr($transfer->to_user_name, 0, 1) }}
                                    </div>
                                    <div class="user-details">
                                        <div class="user-name">{{ $transfer->to_user_name }}</div>
                                        @if($transfer->to_user_employee_id)
                                            <small class="text-muted">{{ $transfer->to_user_employee_id }}</small>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="date-cell">
                                    <div class="date-main">{{ \Carbon\Carbon::parse($transfer->transferred_at)->format('Y-m-d') }}</div>
                                    <small class="text-muted">{{ \Carbon\Carbon::parse($transfer->transferred_at)->format('H:i') }}</small>
                                </div>
                            </td>
                            <td>
                                @if($transfer->reason)
                                    <div class="reason-cell" title="{{ $transfer->reason }}">
                                        {{ Str::limit($transfer->reason, 30) }}
                                    </div>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="pagination-container">
                {{ $transfers->appends(request()->query())->links() }}
            </div>
        @else
            <div class="empty-state-modern">
                <div class="empty-icon">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <h3>لا توجد عمليات نقل مهام</h3>
                <p>جرب تعديل فلاتر البحث للحصول على نتائج أخرى</p>
                <a href="{{ route('task-transfers.history') }}" class="action-btn primary">
                    <i class="fas fa-undo"></i>
                    إعادة تعيين الفلاتر
                </a>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
function refreshData() {
    window.location.reload();
}

function exportData() {
    const currentUrl = new URL(window.location);
    const params = new URLSearchParams(currentUrl.search);

    // إضافة معاملات التصدير
    const exportUrl = "{{ route('task-transfers.export') }}?" + params.toString();

    // فتح رابط التصدير
    window.open(exportUrl, '_blank');
}

// تحسين UX للفلاتر
$(document).ready(function() {
    // إضافة Select2 للقوائم المنسدلة إذا كان متاحاً
    if (typeof $.fn.select2 !== 'undefined') {
        $('#from_user_id, #to_user_id').select2({
            placeholder: 'اختر...',
            allowClear: true
        });
    }

    // إرسال النموذج تلقائياً عند تغيير التاريخ
    $('#from_date, #to_date').on('change', function() {
        if ($(this).val()) {
            $('#filterForm').submit();
        }
    });
});
</script>
@endpush
