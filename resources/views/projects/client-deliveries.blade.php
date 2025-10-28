@extends('layouts.app')

@section('title', 'تسليم المشاريع للعملاء')

@push('styles')
<style>
/* Container */
.client-deliveries-container {
    padding: 2rem;
    background: #f8f9fa;
    min-height: 100vh;
}

/* Header */
.page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 2rem;
    border-radius: 16px;
    color: white;
    margin-bottom: 2rem;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
}

.page-header h1 {
    font-size: 2rem;
    font-weight: 700;
    margin: 0 0 0.5rem 0;
}

.page-header p {
    margin: 0;
    opacity: 0.9;
    font-size: 1.1rem;
}

/* Statistics Cards */
.stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    background: linear-gradient(135deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: #6c757d;
    font-size: 0.95rem;
    font-weight: 500;
}

.stat-card.success .stat-number {
    background: linear-gradient(135deg, #11998e, #38ef7d);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.stat-card.danger .stat-number {
    background: linear-gradient(135deg, #ee0979, #ff6a00);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

/* Filters */
.filters-card {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    margin-bottom: 2rem;
}

.filters-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    align-items: end;
}

.filter-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #495057;
}

.filter-group select {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.filter-group select:focus {
    border-color: #667eea;
    outline: none;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

/* Table */
.deliveries-table-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    overflow: hidden;
}

.table-header {
    padding: 1.5rem;
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border-bottom: 2px solid #dee2e6;
}

.table-header h2 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
    color: #495057;
}

.deliveries-table {
    width: 100%;
    border-collapse: collapse;
}

.deliveries-table thead {
    background: #f8f9fa;
}

.deliveries-table th {
    padding: 1rem;
    text-align: right;
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
}

.deliveries-table td {
    padding: 1.25rem 1rem;
    border-bottom: 1px solid #e9ecef;
}

.deliveries-table tbody tr {
    transition: background-color 0.2s ease;
}

.deliveries-table tbody tr:hover {
    background-color: #f8f9fa;
}

/* Project Info */
.project-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.project-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.project-details h4 {
    margin: 0 0 0.25rem 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: #212529;
}

.project-code {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    background: #e7f3ff;
    color: #0066cc;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    margin-top: 0.25rem;
}

/* Client Badge */
.client-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: #f8f9fa;
    border-radius: 8px;
    font-weight: 500;
    color: #495057;
}

.client-badge i {
    color: #667eea;
}

/* Status Badges */
.status-badge {
    display: inline-block;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 600;
    text-align: center;
}

.status-badge.on-time {
    background: #d4edda;
    color: #155724;
}

.status-badge.late {
    background: #f8d7da;
    color: #721c24;
}

.status-badge.no-date {
    background: #fff3cd;
    color: #856404;
}

/* Date Display */
.date-display {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.date-display strong {
    font-size: 0.85rem;
    color: #6c757d;
}

.date-display span {
    font-size: 1rem;
    color: #212529;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: #6c757d;
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.empty-state h4 {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
}

/* Notes Badge */
.notes-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: #fff3cd;
    color: #856404;
    border-radius: 8px;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.notes-badge:hover {
    background: #ffc107;
    color: white;
}

/* Pagination */
.pagination-wrapper {
    padding: 1.5rem;
    display: flex;
    justify-content: center;
}

/* Responsive */
@media (max-width: 768px) {
    .stats-row {
        grid-template-columns: 1fr;
    }

    .filters-row {
        grid-template-columns: 1fr;
    }

    .deliveries-table {
        font-size: 0.9rem;
    }

    .deliveries-table th,
    .deliveries-table td {
        padding: 0.75rem 0.5rem;
    }
}
</style>
@endpush

@section('content')
<div class="client-deliveries-container">
    <!-- Page Header -->
    <div class="page-header">
        <h1>
            <i class="fas fa-calendar-check me-2"></i>
            مواعيد تسليم المشاريع للعملاء
        </h1>
        <p>عرض جميع المشاريع المتفق على تسليمها للعملاء - فريق خدمة العملاء</p>
    </div>

    <!-- Statistics -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-number">{{ $totalProjects }}</div>
            <div class="stat-label">
                <i class="fas fa-box me-1"></i>
                إجمالي المشاريع
            </div>
        </div>
        <div class="stat-card success">
            <div class="stat-number">{{ $deliveredProjects }}</div>
            <div class="stat-label">
                <i class="fas fa-check-circle me-1"></i>
                تم التسليم
            </div>
        </div>
        <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
            <div class="stat-number" style="background: linear-gradient(135deg, #4facfe, #00f2fe); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">{{ $pendingProjects }}</div>
            <div class="stat-label">
                <i class="fas fa-clock me-1"></i>
                قادم
            </div>
        </div>
        <div class="stat-card danger">
            <div class="stat-number">{{ $overdueProjects }}</div>
            <div class="stat-label">
                <i class="fas fa-exclamation-triangle me-1"></i>
                متأخر
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters-card">
        <form method="GET" action="{{ route('projects.client-deliveries') }}" id="filterForm">
            <div class="filters-row">
                <!-- Month Filter -->
                <div class="filter-group">
                    <label for="monthFilter">
                        <i class="fas fa-calendar-alt me-1"></i>
                        الشهر
                    </label>
                    <select name="month" id="monthFilter" onchange="document.getElementById('filterForm').submit()">
                        @for ($i = -3; $i <= 3; $i++)
                            @php
                                $monthDate = now()->addMonths($i);
                                $monthValue = $monthDate->format('Y-m');
                                $monthLabel = $monthDate->locale('ar')->translatedFormat('F Y');
                            @endphp
                            <option value="{{ $monthValue }}" {{ $selectedMonth == $monthValue ? 'selected' : '' }}>
                                {{ $monthLabel }}
                            </option>
                        @endfor
                    </select>
                </div>

                <!-- Week Filter -->
                <div class="filter-group">
                    <label for="weekFilter">
                        <i class="fas fa-calendar-week me-1"></i>
                        الأسبوع
                    </label>
                    <select name="week" id="weekFilter" onchange="document.getElementById('filterForm').submit()">
                        <option value="all" {{ $selectedWeek == 'all' ? 'selected' : '' }}>كل الأسابيع</option>
                        @foreach($weeksInMonth as $week)
                            <option value="{{ $week['number'] }}" {{ $selectedWeek == $week['number'] ? 'selected' : '' }}>
                                {{ $week['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Clear Filters -->
                <div class="filter-group">
                    <a href="{{ route('projects.client-deliveries') }}" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-redo me-1"></i>
                        إعادة تعيين
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Projects Table -->
    <div class="deliveries-table-container">
        <div class="table-header">
            <h2>
                <i class="fas fa-list me-2"></i>
                مواعيد التسليم
                @if($selectedWeek != 'all')
                    @php
                        $selectedWeekData = collect($weeksInMonth)->firstWhere('number', $selectedWeek);
                    @endphp
                    <small class="text-muted" style="font-size: 1rem;">
                        ({{ $selectedWeekData['label'] ?? '' }})
                    </small>
                @endif
            </h2>
        </div>

        <table class="deliveries-table">
            <thead>
                <tr>
                    <th>المشروع</th>
                    <th>العميل</th>
                    <th>تاريخ التسليم المتفق عليه</th>
                    <th>تاريخ التسليم الفعلي</th>
                    <th>الحالة</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($projects as $project)
                <tr>
                    <!-- Project Info -->
                    <td>
                        <div class="project-info">
                            <div class="project-icon">
                                <i class="fas fa-project-diagram"></i>
                            </div>
                            <div class="project-details">
                                <h4>{{ $project->name }}</h4>
                                @if($project->code)
                                    <span class="project-code">
                                        <i class="fas fa-qrcode me-1"></i>
                                        {{ $project->code }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </td>

                    <!-- Client -->
                    <td>
                        <div class="client-badge">
                            <i class="fas fa-user-tie"></i>
                            {{ $project->client->name ?? 'غير محدد' }}
                        </div>
                    </td>

                    <!-- Agreed Delivery Date -->
                    <td>
                        <div class="date-display">
                            <strong>الموعد المحدد:</strong>
                            <span style="font-size: 1.1rem; font-weight: 600;">
                                {{ $project->client_agreed_delivery_date->format('Y-m-d') }}
                            </span>
                            <small class="text-muted">
                                {{ $project->client_agreed_delivery_date->locale('ar')->translatedFormat('l') }}
                            </small>
                        </div>
                    </td>

                    <!-- Actual Delivery Date -->
                    <td>
                        @if($project->lastFinalDelivery)
                            <div class="date-display">
                                <strong>تم التسليم:</strong>
                                <span style="color: #28a745;">{{ $project->lastFinalDelivery->delivery_date->format('Y-m-d') }}</span>
                                <small class="text-muted">{{ $project->lastFinalDelivery->delivery_date->format('h:i A') }}</small>
                            </div>
                        @else
                            <span class="text-muted">
                                <i class="fas fa-hourglass-half me-1"></i>
                                لم يتم التسليم بعد
                            </span>
                        @endif
                    </td>

                    <!-- Status -->
                    <td>
                        @if($project->lastFinalDelivery)
                            @if($project->lastFinalDelivery->delivery_date <= $project->client_agreed_delivery_date)
                                <span class="status-badge on-time">
                                    <i class="fas fa-check-circle me-1"></i>
                                    تم التسليم في الموعد
                                </span>
                            @else
                                @php
                                    $delayDays = $project->lastFinalDelivery->delivery_date->diffInDays($project->client_agreed_delivery_date);
                                @endphp
                                <span class="status-badge late">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    تم التسليم متأخراً ({{ $delayDays }} يوم)
                                </span>
                            @endif
                        @else
                            @if(now()->greaterThan($project->client_agreed_delivery_date))
                                @php
                                    $overdueDays = now()->diffInDays($project->client_agreed_delivery_date);
                                @endphp
                                <span class="status-badge late">
                                    <i class="fas fa-exclamation-circle me-1"></i>
                                    متأخر ({{ $overdueDays }} يوم)
                                </span>
                            @else
                                @php
                                    $daysRemaining = now()->diffInDays($project->client_agreed_delivery_date);
                                @endphp
                                <span class="status-badge" style="background: #e3f2fd; color: #1976d2;">
                                    <i class="fas fa-clock me-1"></i>
                                    متبقي {{ $daysRemaining }} يوم
                                </span>
                            @endif
                        @endif
                    </td>

                    <!-- Actions -->
                    <td>
                        <a href="{{ route('projects.show', $project->id) }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-eye me-1"></i>
                            عرض المشروع
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h4>لا توجد مشاريع</h4>
                        <p>لم يتم العثور على أي مشاريع في هذه الفترة</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Pagination -->
        @if($projects->hasPages())
            <div class="pagination-wrapper">
                {{ $projects->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
console.log('📦 صفحة تسليم المشاريع للعملاء');
</script>
@endpush

