@extends('layouts.app')

@section('title', 'مواعيد تسليم المشاريع - الفريق التنفيذي')

@push('styles')

<link rel="stylesheet" href="{{ asset('css/client-deliveries.css') }}">
@endpush

@section('content')
<div class="simple-container">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>📅 مواعيد تسليم المشاريع للعملاء</h1>
            <p>عرض جميع المشاريع المتفق على تسليمها للعملاء - الفريق التنفيذي</p>
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
            <form method="GET" action="{{ route('projects.client-deliveries') }}" id="filterForm">
                <div class="filters-row">
                    <!-- Month Filter -->
                    <div class="filter-group">
                        <label for="monthFilter" class="filter-label">
                            <i class="fas fa-calendar-alt"></i>
                            الشهر
                        </label>
                        <select name="month" id="monthFilter" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                            @for ($i = -3; $i <= 3; $i++)
                                @php
                                $monthDate=now()->addMonths($i);
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
                        <label for="weekFilter" class="filter-label">
                            <i class="fas fa-calendar-week"></i>
                            الأسبوع
                        </label>
                        <select name="week" id="weekFilter" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                            <option value="all" {{ $selectedWeek == 'all' ? 'selected' : '' }}>كل الأسابيع</option>
                            @foreach($weeksInMonth as $week)
                            <option value="{{ $week['number'] }}" {{ $selectedWeek == $week['number'] ? 'selected' : '' }}>
                                {{ $week['label'] }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Clear Filters -->
                    @if(request()->hasAny(['month', 'week']))
                    <div class="filter-group">
                        <label class="filter-label" style="opacity: 0;">مسح</label>
                        <a href="{{ route('projects.client-deliveries') }}" class="clear-filters-btn">
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
                <div class="stat-number">{{ $totalProjects }}</div>
                <div class="stat-label">إجمالي المشاريع</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $deliveredProjects }}</div>
                <div class="stat-label">تم التسليم</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $pendingProjects }}</div>
                <div class="stat-label">قادم</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $overdueProjects }}</div>
                <div class="stat-label">متأخر</div>
            </div>
        </div>

        <!-- Projects Table -->
        <div class="projects-table-container">
            <div class="table-header">
                <h2>
                    مواعيد التسليم
                    @if($selectedWeek != 'all')
                    @php
                    $selectedWeekData = collect($weeksInMonth)->firstWhere('number', $selectedWeek);
                    @endphp
                    <small style="font-size: 1rem; opacity: 0.9;">
                        ({{ $selectedWeekData['label'] ?? '' }})
                    </small>
                    @endif
                </h2>
            </div>

            <table class="projects-table">
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
                    <tr class="project-row">
                        <!-- Project Info -->
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
                                </div>
                            </div>
                        </td>

                        <!-- Client -->
                        <td>
                            <div class="client-info">
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
                            <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
                                <a href="{{ route('projects.show', $project->id) }}" class="services-btn">
                                    <i class="fas fa-eye"></i>
                                    عرض
                                </a>
                            </div>
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
            <div class="d-flex justify-content-center mt-4">
                {{ $projects->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    console.log('📦 صفحة مواعيد تسليم المشاريع - الفريق التنفيذي');
</script>
@endpush