@extends('layouts.app')

@section('title', 'لوحة تحكم المشاريع')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/projects-dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('css/project-dashboard/dashboard/main.css') }}">
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
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="title-text">
                        <h1>لوحة تحكم المشاريع</h1>
                        <p>نظرة شاملة على جميع مشاريعك وأدائها</p>
                        @if(isset($periodDescription) && $periodDescription != 'جميع الفترات')
                        <div class="filter-indicator">
                            <i class="fas fa-filter text-primary"></i>
                            <span class="filter-text">فلتر مطبق: {{ $periodDescription }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="header-actions">
                <button class="action-btn secondary" onclick="window.location.reload()">
                    <i class="fas fa-sync-alt"></i>
                    تحديث
                </button>
                <a href="{{ route('projects.index') }}" class="action-btn outline">
                    <i class="fas fa-list"></i>
                    قائمة المشاريع
                </a>
                <a href="{{ route('projects.create') }}" class="action-btn primary">
                    <i class="fas fa-plus"></i>
                    مشروع جديد
                </a>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="filters-section">
        <div class="filters-container">
            <div class="filters-header">
                <h3><i class="fas fa-filter"></i> فلتر البيانات</h3>
                <button type="button" class="toggle-filters-btn" id="toggleFiltersBtn">
                    <i class="fas fa-chevron-down"></i>
                    عرض/إخفاء الفلاتر
                </button>
            </div>
            <div class="filters-content" id="filtersContent">
                <form method="GET" action="{{ route('company-projects.dashboard') }}" id="dashboardFiltersForm">
                    <div class="filters-grid">
                        <!-- Date Range Filter -->
                        <div class="filter-group">
                            <label><i class="fas fa-calendar"></i> الفترة الزمنية</label>
                            <div class="date-filter-options">
                                <div class="date-option">
                                    <input type="radio" id="quick_filter" name="filter_type" value="quick"
                                           {{ request('filter_type', 'quick') == 'quick' ? 'checked' : '' }}>
                                    <label for="quick_filter">فلتر سريع</label>
                                </div>
                                <div class="date-option">
                                    <input type="radio" id="custom_filter" name="filter_type" value="custom"
                                           {{ request('filter_type') == 'custom' ? 'checked' : '' }}>
                                    <label for="custom_filter">تاريخ مخصص</label>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Filter (Month/Period) -->
                        <div class="filter-group quick-filter" id="quickFilterGroup">
                            <label>الفترة</label>
                            <select name="quick_period" id="quickPeriod" class="form-select">
                                <option value="">جميع الفترات</option>
                                <option value="this_month" {{ request('quick_period') == 'this_month' ? 'selected' : '' }}>الشهر الحالي</option>
                                <option value="last_month" {{ request('quick_period') == 'last_month' ? 'selected' : '' }}>الشهر الماضي</option>
                                <option value="this_quarter" {{ request('quick_period') == 'this_quarter' ? 'selected' : '' }}>الربع الحالي</option>
                                <option value="last_quarter" {{ request('quick_period') == 'last_quarter' ? 'selected' : '' }}>الربع الماضي</option>
                                <option value="this_year" {{ request('quick_period') == 'this_year' ? 'selected' : '' }}>السنة الحالية</option>
                                <option value="last_year" {{ request('quick_period') == 'last_year' ? 'selected' : '' }}>السنة الماضية</option>
                                <option value="last_7_days" {{ request('quick_period') == 'last_7_days' ? 'selected' : '' }}>آخر 7 أيام</option>
                                <option value="last_30_days" {{ request('quick_period') == 'last_30_days' ? 'selected' : '' }}>آخر 30 يوم</option>
                            </select>
                        </div>

                        <!-- Custom Date Range -->
                        <div class="filter-group custom-filter" id="customFilterGroup" style="display: none;">
                            <label>من تاريخ</label>
                            <input type="date" name="from_date" id="fromDate" value="{{ request('from_date') }}" class="form-input">
                        </div>

                        <div class="filter-group custom-filter" id="customFilterGroup2" style="display: none;">
                            <label>إلى تاريخ</label>
                            <input type="date" name="to_date" id="toDate" value="{{ request('to_date') }}" class="form-input">
                        </div>

                        <!-- Apply Filters Button -->
                        <div class="filter-actions">
                            <button type="submit" class="btn-apply-filters">
                                <i class="fas fa-search"></i>
                                تطبيق الفلتر
                            </button>
                            <a href="{{ route('company-projects.dashboard') }}" class="btn-reset-filters">
                                <i class="fas fa-undo"></i>
                                إعادة تعيين
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Main Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card-modern primary">
            <div class="stat-icon">
                <i class="fas fa-briefcase"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number">{{ $totalProjects }}</div>
                <div class="stat-label">إجمالي المشاريع</div>
                <div class="stat-trend positive">
                    <i class="fas fa-arrow-up"></i>
                    +{{ round(($newProjects / max($totalProjects, 1)) * 100) }}% جديدة
                </div>
            </div>
        </div>

        <div class="stat-card-modern success">
            <div class="stat-icon">
                <i class="fas fa-play-circle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number">{{ $inProgressProjects }}</div>
                <div class="stat-label">قيد التنفيذ</div>
                <div class="stat-trend">
                    {{ round(($inProgressProjects / max($totalProjects, 1)) * 100) }}% من الإجمالي
                </div>
            </div>
        </div>

        <div class="stat-card-modern info">
            <div class="stat-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number">{{ $completedProjects }}</div>
                <div class="stat-label">مكتملة</div>
                <div class="stat-trend positive">
                    <i class="fas fa-trophy"></i>
                    {{ round(($completedProjects / max($totalProjects, 1)) * 100) }}% معدل الإنجاز
                </div>
            </div>
        </div>

        <div class="stat-card-modern warning">
            <div class="stat-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number">{{ $overdueProjects->count() }}</div>
                <div class="stat-label">متأخرة</div>
                <div class="stat-trend">
                    <i class="fas fa-clock"></i>
                    تحتاج متابعة فورية
                </div>
            </div>
        </div>

        <div class="stat-card-modern delivery-card" style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); border: none; box-shadow: 0 6px 25px rgba(231, 76, 60, 0.4);">
            <div class="stat-icon" style="background: rgba(255, 255, 255, 0.25); backdrop-filter: blur(10px); box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);">
                <i class="fas fa-pause-circle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number" style="color: white; -webkit-text-fill-color: white; background: none;">{{ $pausedProjects ?? 0 }}</div>
                <div class="stat-label" style="color: rgba(255, 255, 255, 0.95);">موقوفة</div>
                <div class="stat-trend" style="background: rgba(255, 255, 255, 0.2); color: white; backdrop-filter: blur(10px);">
                    <i class="fas fa-pause"></i>
                    {{ $pausedProjects > 0 ? round(($pausedProjects / max($totalProjects, 1)) * 100) : 0 }}% من الإجمالي
                </div>
            </div>
        </div>
    </div>

    <!-- Deliveries Stats Section -->
    <div class="section-modern" style="margin-top: 2rem;">
        <div class="section-header">
            <h2>
                <i class="fas fa-shipping-fast"></i>
                إحصائيات التسليمات
            </h2>
        </div>

        <div class="stats-grid">
            <div class="stat-card-modern delivery-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); border: none; box-shadow: 0 6px 25px rgba(240, 147, 251, 0.4);">
                <div class="stat-icon" style="background: rgba(255, 255, 255, 0.25); backdrop-filter: blur(10px); box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number" style="color: white; -webkit-text-fill-color: white; background: none;">{{ $projectsWithDraft ?? 0 }}</div>
                    <div class="stat-label" style="color: rgba(255, 255, 255, 0.95); font-size: 1rem;">مشاريع بمسودة</div>
                    <div class="stat-trend" style="background: rgba(255, 255, 255, 0.2); color: white; backdrop-filter: blur(10px);">
                        <i class="fas fa-file"></i>
                        {{ $projectsWithDraft > 0 ? round(($projectsWithDraft / max($totalProjects, 1)) * 100) : 0 }}% من الإجمالي
                    </div>
                </div>
            </div>

            <div class="stat-card-modern delivery-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); border: none; box-shadow: 0 6px 25px rgba(79, 172, 254, 0.4);">
                <div class="stat-icon" style="background: rgba(255, 255, 255, 0.25); backdrop-filter: blur(10px); box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);">
                    <i class="fas fa-check-double"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number" style="color: white; -webkit-text-fill-color: white; background: none;">{{ $projectsWithFinal ?? 0 }}</div>
                    <div class="stat-label" style="color: rgba(255, 255, 255, 0.95); font-size: 1rem;">مشاريع بتسليم نهائي</div>
                    <div class="stat-trend" style="background: rgba(255, 255, 255, 0.2); color: white; backdrop-filter: blur(10px);">
                        <i class="fas fa-trophy"></i>
                        {{ $projectsWithFinal > 0 ? round(($projectsWithFinal / max($totalProjects, 1)) * 100) : 0 }}% من الإجمالي
                    </div>
                </div>
            </div>

            <div class="stat-card-modern delivery-card" style="background: linear-gradient(135deg, #ffeaa7 0%, #fdcb6e 100%); border: none; box-shadow: 0 6px 25px rgba(253, 203, 110, 0.4);">
                <div class="stat-icon" style="background: rgba(45, 52, 54, 0.2); backdrop-filter: blur(10px); box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number" style="color: #2d3436; -webkit-text-fill-color: #2d3436; background: none;">{{ $projectsWithoutDelivery ?? 0 }}</div>
                    <div class="stat-label" style="color: #2d3436; font-size: 1rem;">بدون تسليم</div>
                    <div class="stat-trend" style="background: rgba(45, 52, 54, 0.15); color: #2d3436; backdrop-filter: blur(10px);">
                        <i class="fas fa-hourglass-half"></i>
                        تحتاج تسليم
                    </div>
                </div>
            </div>

            <div class="stat-card-modern delivery-card" style="background: linear-gradient(135deg, #a8e6cf 0%, #56ab2f 100%); border: none; box-shadow: 0 6px 25px rgba(168, 230, 207, 0.4);">
                <div class="stat-icon" style="background: rgba(255, 255, 255, 0.25); backdrop-filter: blur(10px); box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);">
                    <i class="fas fa-clipboard-check"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number" style="color: white; -webkit-text-fill-color: white; background: none;">
                        @php
                            $totalDelivered = ($projectsWithDraft ?? 0) + ($projectsWithFinal ?? 0);
                        @endphp
                        {{ $totalDelivered }}
                    </div>
                    <div class="stat-label" style="color: rgba(255, 255, 255, 0.95); font-size: 1rem;">إجمالي المسلمة</div>
                    <div class="stat-trend" style="background: rgba(255, 255, 255, 0.2); color: white; backdrop-filter: blur(10px);">
                        <i class="fas fa-check"></i>
                        {{ $totalDelivered > 0 ? round(($totalDelivered / max($totalProjects, 1)) * 100) : 0 }}% معدل التسليم
                    </div>
                </div>
            </div>
        </div>

        <div style="margin-top: 1.5rem; text-align: center;">
            <a href="{{ route('projects.deliveries.index') }}" class="action-btn primary">
                <i class="fas fa-shipping-fast"></i>
                إدارة التسليمات
            </a>
        </div>
    </div>

    <!-- Delays and Modifications Section -->
    <div class="section-modern">
        <div class="section-header">
            <h2>
                <i class="fas fa-exclamation-triangle"></i>
                التاخيرات والتعديلات
            </h2>
        </div>

        <div class="row">
            <!-- Delays Overview -->
            <div class="col-lg-6 col-md-6 mb-4">
                <div class="simple-white-board">
                    <div class="simple-white-board-header">
                        <div class="simple-white-board-icon danger">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h3 class="simple-white-board-title">التاخيرات</h3>
                    </div>
                    <div class="simple-white-board-stats">
                        <div class="simple-white-board-stat">
                            <span class="simple-white-board-stat-number">{{ $revisionStats['pending'] ?? 0 }}</span>
                            <span class="simple-white-board-stat-label">في الانتظار</span>
                        </div>
                        <div class="simple-white-board-stat">
                            <span class="simple-white-board-stat-number">{{ $revisionStats['rejected'] ?? 0 }}</span>
                            <span class="simple-white-board-stat-label">مرفوضة</span>
                        </div>
                    </div>
                    <div class="simple-white-board-list">
                        <div class="simple-white-board-list-item">
                            <div class="simple-white-board-list-icon warning">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="simple-white-board-list-content">
                                <div class="simple-white-board-list-title">تأخيرات المهام</div>
                                <div class="simple-white-board-list-meta">{{ $revisionStats['pending'] ?? 0 }} مهمة متأخرة</div>
                            </div>
                        </div>
                        <div class="simple-white-board-list-item">
                            <div class="simple-white-board-list-icon danger">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="simple-white-board-list-content">
                                <div class="simple-white-board-list-title">تأخيرات المشاريع</div>
                                <div class="simple-white-board-list-meta">{{ $revisionStats['rejected'] ?? 0 }} مشروع متأخر</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modifications Overview -->
            <div class="col-lg-6 col-md-6 mb-4">
                <div class="simple-white-board">
                    <div class="simple-white-board-header">
                        <div class="simple-white-board-icon info">
                            <i class="fas fa-edit"></i>
                        </div>
                        <h3 class="simple-white-board-title">التعديلات</h3>
                    </div>
                    <div class="simple-white-board-stats">
                        <div class="simple-white-board-stat">
                            <span class="simple-white-board-stat-number">{{ $revisionStats['total'] ?? 0 }}</span>
                            <span class="simple-white-board-stat-label">إجمالي التعديلات</span>
                        </div>
                        <div class="simple-white-board-stat">
                            <span class="simple-white-board-stat-number">{{ $revisionStats['approved'] ?? 0 }}</span>
                            <span class="simple-white-board-stat-label">موافق عليها</span>
                        </div>
                    </div>
                    <div class="simple-white-board-list">
                        <div class="simple-white-board-list-item">
                            <div class="simple-white-board-list-icon success">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="simple-white-board-list-content">
                                <div class="simple-white-board-list-title">تعديلات المهام</div>
                                <div class="simple-white-board-list-meta">{{ $revisionsByCategory['task_revisions']['total'] ?? 0 }} تعديل</div>
                            </div>
                        </div>
                        <div class="simple-white-board-list-item">
                            <div class="simple-white-board-list-icon primary">
                                <i class="fas fa-project-diagram"></i>
                            </div>
                            <div class="simple-white-board-list-content">
                                <div class="simple-white-board-list-title">تعديلات المشاريع</div>
                                <div class="simple-white-board-list-meta">{{ $revisionsByCategory['project_revisions']['total'] ?? 0 }} تعديل</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Task Transfer Statistics -->
    @if(isset($globalTransferStats) && $globalTransferStats['has_transfers'])
    <div class="section-modern">
        <div class="section-header">
            <h2>
                <i class="fas fa-exchange-alt"></i>
                إحصائيات نقل المهام
            </h2>
        </div>

        <div class="row">
            <!-- Transfer Overview -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="simple-white-board">
                    <div class="simple-white-board-header">
                        <div class="simple-white-board-icon primary">
                            <i class="fas fa-exchange-alt"></i>
                        </div>
                        <h3 class="simple-white-board-title">إجمالي النقل</h3>
                    </div>
                    <div class="simple-white-board-stats">
                        <div class="simple-white-board-stat">
                            <span class="simple-white-board-stat-number">{{ $globalTransferStats['total_transfers'] }}</span>
                            <span class="simple-white-board-stat-label">إجمالي المهام</span>
                        </div>
                    </div>
                    <div class="simple-white-board-list">
                        <div class="simple-white-board-list-item">
                            <div class="simple-white-board-list-icon success">
                                <i class="fas fa-tasks"></i>
                            </div>
                            <div class="simple-white-board-list-content">
                                <div class="simple-white-board-list-title">مهام عادية</div>
                                <div class="simple-white-board-list-meta">{{ $globalTransferStats['regular_transfers'] }} مهمة</div>
                            </div>
                        </div>
                        <div class="simple-white-board-list-item">
                            <div class="simple-white-board-list-icon info">
                                <i class="fas fa-layer-group"></i>
                            </div>
                            <div class="simple-white-board-list-content">
                                <div class="simple-white-board-list-title">مهام قوالب</div>
                                <div class="simple-white-board-list-meta">{{ $globalTransferStats['template_transfers'] }} قالب</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Tasks -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="simple-white-board">
                    <div class="simple-white-board-header">
                        <div class="simple-white-board-icon success">
                            <i class="fas fa-plus-circle"></i>
                        </div>
                        <h3 class="simple-white-board-title">المهام الإضافية</h3>
                    </div>
                    <div class="simple-white-board-stats">
                        <div class="simple-white-board-stat">
                            <span class="simple-white-board-stat-number">{{ $globalTransferStats['additional_tasks'] }}</span>
                            <span class="simple-white-board-stat-label">مهام إضافية</span>
                        </div>
                    </div>
                    <div class="simple-white-board-list">
                        <div class="simple-white-board-list-item">
                            <div class="simple-white-board-list-icon success">
                                <i class="fas fa-tasks"></i>
                            </div>
                            <div class="simple-white-board-list-content">
                                <div class="simple-white-board-list-title">عادية إضافية</div>
                                <div class="simple-white-board-list-meta">{{ $globalTransferStats['additional_regular'] }} مهمة</div>
                            </div>
                        </div>
                        <div class="simple-white-board-list-item">
                            <div class="simple-white-board-list-icon info">
                                <i class="fas fa-layer-group"></i>
                            </div>
                            <div class="simple-white-board-list-content">
                                <div class="simple-white-board-list-title">قوالب إضافية</div>
                                <div class="simple-white-board-list-meta">{{ $globalTransferStats['additional_template'] }} قالب</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transfer History -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="simple-white-board">
                    <div class="simple-white-board-header">
                        <div class="simple-white-board-icon warning">
                            <i class="fas fa-history"></i>
                        </div>
                        <h3 class="simple-white-board-title">سجلات النقل</h3>
                    </div>
                    <div class="simple-white-board-content">
                        <p>عرض جميع عمليات النقل المسجلة في النظام</p>
                    </div>
                    <div class="simple-white-board-list">
                        <div class="simple-white-board-list-item">
                            <div class="simple-white-board-list-icon primary">
                                <i class="fas fa-eye"></i>
                            </div>
                            <div class="simple-white-board-list-content">
                                <div class="simple-white-board-list-title">
                                    <a href="{{ route('task-transfers.history') }}" class="text-decoration-none">
                                        عرض السجلات
                                    </a>
                                </div>
                                <div class="simple-white-board-list-meta">جميع عمليات النقل</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Transfers -->
        @if(count($globalTransferStats['recent_transfers']) > 0)
        <div class="row">
            <div class="col-12">
                <div class="simple-white-board">
                    <div class="simple-white-board-header">
                        <div class="simple-white-board-icon info">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h3 class="simple-white-board-title">آخر عمليات النقل</h3>
                    </div>
                    <div class="simple-white-board-list">
                        @foreach($globalTransferStats['recent_transfers'] as $transfer)
                        <div class="simple-white-board-list-item">
                            <div class="simple-white-board-list-icon {{ $transfer->task_type == 'regular' ? 'success' : 'info' }}">
                                <i class="fas {{ $transfer->task_type == 'regular' ? 'fa-tasks' : 'fa-layer-group' }}"></i>
                            </div>
                            <div class="simple-white-board-list-content">
                                <div class="simple-white-board-list-title">{{ $transfer->task_name }}</div>
                                <div class="simple-white-board-list-meta">
                                    من: {{ $transfer->from_user_name }} → إلى: {{ $transfer->to_user_name }}
                                    <br>
                                    <small>{{ \Carbon\Carbon::parse($transfer->transferred_at)->diffForHumans() }}</small>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
    @endif

    <!-- Revision Transfer Statistics -->
    @if(isset($globalRevisionTransferStats) && $globalRevisionTransferStats['has_transfers'])
    <div class="section-modern">
        <div class="section-header">
            <h2>
                <i class="fas fa-exchange-alt"></i>
                إحصائيات نقل التعديلات (مهام إضافية)
            </h2>
        </div>

        <div class="row">
            <!-- Transfer Overview -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="simple-white-board">
                    <div class="simple-white-board-header">
                        <div class="simple-white-board-icon primary">
                            <i class="fas fa-exchange-alt"></i>
                        </div>
                        <h3 class="simple-white-board-title">إجمالي النقل</h3>
                    </div>
                    <div class="simple-white-board-stats">
                        <div class="simple-white-board-stat">
                            <span class="simple-white-board-stat-number">{{ $globalRevisionTransferStats['transferred_to_me'] + $globalRevisionTransferStats['transferred_from_me'] }}</span>
                            <span class="simple-white-board-stat-label">إجمالي التعديلات</span>
                        </div>
                    </div>
                    <div class="simple-white-board-list">
                        <div class="simple-white-board-list-item">
                            <div class="simple-white-board-list-icon success">
                                <i class="fas fa-arrow-down"></i>
                            </div>
                            <div class="simple-white-board-list-content">
                                <div class="simple-white-board-list-title">منقولة إليّ</div>
                                <div class="simple-white-board-list-meta">{{ $globalRevisionTransferStats['transferred_to_me'] }} تعديل</div>
                            </div>
                        </div>
                        <div class="simple-white-board-list-item">
                            <div class="simple-white-board-list-icon info">
                                <i class="fas fa-arrow-up"></i>
                            </div>
                            <div class="simple-white-board-list-content">
                                <div class="simple-white-board-list-title">منقولة مني</div>
                                <div class="simple-white-board-list-meta">{{ $globalRevisionTransferStats['transferred_from_me'] }} تعديل</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Executor Transfers -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="simple-white-board">
                    <div class="simple-white-board-header">
                        <div class="simple-white-board-icon warning">
                            <i class="fas fa-wrench"></i>
                        </div>
                        <h3 class="simple-white-board-title">المنفذين</h3>
                    </div>
                    <div class="simple-white-board-stats">
                        <div class="simple-white-board-stat">
                            <span class="simple-white-board-stat-number">{{ $globalRevisionTransferStats['executor_transferred_to_me'] }}</span>
                            <span class="simple-white-board-stat-label">منقولة كمنفذ</span>
                        </div>
                    </div>
                    <div class="simple-white-board-list">
                        <div class="simple-white-board-list-item">
                            <div class="simple-white-board-list-icon success">
                                <i class="fas fa-wrench"></i>
                            </div>
                            <div class="simple-white-board-list-content">
                                <div class="simple-white-board-list-title">منقولة إليّ كمنفذ</div>
                                <div class="simple-white-board-list-meta">{{ $globalRevisionTransferStats['executor_transferred_to_me'] }} تعديل</div>
                            </div>
                        </div>
                        <div class="simple-white-board-list-item">
                            <div class="simple-white-board-list-icon info">
                                <i class="fas fa-wrench"></i>
                            </div>
                            <div class="simple-white-board-list-content">
                                <div class="simple-white-board-list-title">منقولة مني كمنفذ</div>
                                <div class="simple-white-board-list-meta">{{ $globalRevisionTransferStats['executor_transferred_from_me'] }} تعديل</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reviewer Transfers -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="simple-white-board">
                    <div class="simple-white-board-header">
                        <div class="simple-white-board-icon success">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h3 class="simple-white-board-title">المراجعين</h3>
                    </div>
                    <div class="simple-white-board-stats">
                        <div class="simple-white-board-stat">
                            <span class="simple-white-board-stat-number">{{ $globalRevisionTransferStats['reviewer_transferred_to_me'] }}</span>
                            <span class="simple-white-board-stat-label">منقولة كمراجع</span>
                        </div>
                    </div>
                    <div class="simple-white-board-list">
                        <div class="simple-white-board-list-item">
                            <div class="simple-white-board-list-icon success">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="simple-white-board-list-content">
                                <div class="simple-white-board-list-title">منقولة إليّ كمراجع</div>
                                <div class="simple-white-board-list-meta">{{ $globalRevisionTransferStats['reviewer_transferred_to_me'] }} تعديل</div>
                            </div>
                        </div>
                        <div class="simple-white-board-list-item">
                            <div class="simple-white-board-list-icon info">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="simple-white-board-list-content">
                                <div class="simple-white-board-list-title">منقولة مني كمراجع</div>
                                <div class="simple-white-board-list-meta">{{ $globalRevisionTransferStats['reviewer_transferred_from_me'] }} تعديل</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Revision Transfers -->
        @if($globalRevisionTransferStats['transferred_to_me_details']->count() > 0)
        <div class="row">
            <div class="col-12">
                <div class="simple-white-board">
                    <div class="simple-white-board-header">
                        <div class="simple-white-board-icon info">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h3 class="simple-white-board-title">آخر التعديلات المنقولة إليّ</h3>
                    </div>
                    <div class="simple-white-board-list">
                        @foreach($globalRevisionTransferStats['transferred_to_me_details'] as $transfer)
                        <div class="simple-white-board-list-item">
                            <div class="simple-white-board-list-icon {{ $transfer->assignment_type == 'executor' ? 'warning' : 'success' }}">
                                <i class="fas {{ $transfer->assignment_type == 'executor' ? 'fa-wrench' : 'fa-check-circle' }}"></i>
                            </div>
                            <div class="simple-white-board-list-content">
                                <div class="simple-white-board-list-title">{{ $transfer->revision->title ?? 'تعديل محذوف' }}</div>
                                <div class="simple-white-board-list-meta">
                                    @if($transfer->fromUser)
                                        من: {{ $transfer->fromUser->name }}
                                    @else
                                        تعيين جديد
                                    @endif
                                    | {{ $transfer->assignment_type == 'executor' ? 'منفذ' : 'مراجع' }}
                                    @if($transfer->reason)
                                        | السبب: {{ \Str::limit($transfer->reason, 30) }}
                                    @endif
                                    <br>
                                    <small>{{ $transfer->created_at->diffForHumans() }}</small>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
    @endif

    <!-- Error Statistics Section -->
    @if(isset($globalErrorStats) && $globalErrorStats['has_errors'])
    <div class="section-modern">
        <div class="section-header">
            <h2>
                <i class="fas fa-exclamation-triangle"></i>
                إحصائيات الأخطاء
            </h2>
        </div>

        <div class="row">
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="simple-white-board">
                    <div class="simple-white-board-header">
                        <div class="simple-white-board-icon danger">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <h3 class="simple-white-board-title">إجمالي الأخطاء</h3>
                    </div>
                    <div class="simple-white-board-body">
                        <div class="stat-big-number">{{ $globalErrorStats['total_errors'] }}</div>
                        <div class="stat-breakdown">
                            <span class="stat-item"><i class="fas fa-fire"></i> جوهرية: {{ $globalErrorStats['critical_errors'] }}</span>
                            <span class="stat-item"><i class="fas fa-info-circle"></i> عادية: {{ $globalErrorStats['normal_errors'] }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6 mb-4">
                <div class="simple-white-board">
                    <div class="simple-white-board-header">
                        <div class="simple-white-board-icon warning">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <h3 class="simple-white-board-title">أخطاء الجودة</h3>
                    </div>
                    <div class="simple-white-board-body">
                        <div class="stat-big-number">{{ $globalErrorStats['by_category']['quality'] + $globalErrorStats['by_category']['technical'] }}</div>
                        <div class="stat-breakdown">
                            <span class="stat-item">جودة: {{ $globalErrorStats['by_category']['quality'] }}</span>
                            <span class="stat-item">فنية: {{ $globalErrorStats['by_category']['technical'] }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6 mb-4">
                <div class="simple-white-board">
                    <div class="simple-white-board-header">
                        <div class="simple-white-board-icon info">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h3 class="simple-white-board-title">أخطاء المواعيد</h3>
                    </div>
                    <div class="simple-white-board-body">
                        <div class="stat-big-number">{{ $globalErrorStats['by_category']['deadline'] }}</div>
                        <div class="stat-breakdown">
                            <span class="stat-item">تواصل: {{ $globalErrorStats['by_category']['communication'] }}</span>
                            <span class="stat-item">إجرائية: {{ $globalErrorStats['by_category']['procedural'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Overdue Statistics Section -->
    @if((isset($dashboardProjectOverdueStats) && $dashboardProjectOverdueStats['combined']['has_overdue']) || (isset($dashboardTaskOverdueStats) && $dashboardTaskOverdueStats['total_overdue'] > 0))
    <div class="section-modern">
        <div class="section-header">
            <h2>
                <i class="fas fa-exclamation-triangle"></i>
                إحصائيات التأخير
            </h2>
        </div>

        <!-- Projects Overdue Stats -->
        @if(isset($dashboardProjectOverdueStats) && $dashboardProjectOverdueStats['combined']['has_overdue'])
        <div class="overdue-main-section">
            <h3 class="overdue-section-title">
                <i class="fas fa-project-diagram"></i>
                المشاريع المتأخرة
            </h3>

            <!-- Client Date Stats -->
            @if($dashboardProjectOverdueStats['client_date']['total_overdue'] > 0)
            <div class="overdue-category-section">
                <h4 class="overdue-category-title">
                    <i class="fas fa-handshake"></i>
                    بناءً على التاريخ المتفق مع العميل
                </h4>
                <div class="overdue-cards-grid">
                    <div class="overdue-card warning">
                        <div class="overdue-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="overdue-content">
                            <div class="overdue-number">{{ $dashboardProjectOverdueStats['client_date']['overdue_active'] }}</div>
                            <div class="overdue-label">متأخرة ولم تكتمل</div>
                            <div class="overdue-description">مشاريع تجاوزت موعد العميل</div>
                        </div>
                    </div>

                    <div class="overdue-card info">
                        <div class="overdue-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="overdue-content">
                            <div class="overdue-number">{{ $dashboardProjectOverdueStats['client_date']['completed_late'] }}</div>
                            <div class="overdue-label">مكتملة بتأخير</div>
                            <div class="overdue-description">اكتملت بعد موعد العميل</div>
                        </div>
                    </div>

                    <div class="overdue-card danger">
                        <div class="overdue-icon">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <div class="overdue-content">
                            <div class="overdue-number">{{ $dashboardProjectOverdueStats['client_date']['total_overdue'] }}</div>
                            <div class="overdue-label">إجمالي المتأخرة</div>
                            <div class="overdue-description">حسب تاريخ العميل</div>
                        </div>
                    </div>
                </div>

                <!-- Details for Client Date -->
                @if(count($dashboardProjectOverdueStats['client_date']['overdue_projects']) > 0)
                <div class="overdue-details-section">
                    <h5 class="overdue-details-title">
                        <i class="fas fa-list"></i>
                        تفاصيل المشاريع المتأخرة (تاريخ العميل)
                    </h5>
                    <div class="overdue-projects-list">
                        @foreach($dashboardProjectOverdueStats['client_date']['overdue_projects'] as $item)
                        <div class="overdue-project-item">
                            <div class="overdue-project-header">
                                <h6 class="overdue-project-name">
                                    <i class="fas fa-project-diagram"></i>
                                    {{ $item['project']->name }}
                                </h6>
                                <span class="overdue-badge danger">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    متأخر {{ $item['days_overdue'] }} يوم
                                </span>
                            </div>
                            <div class="overdue-project-participants">
                                <strong>الموعد المحدد:</strong>
                                <span class="participant-badge">
                                    <i class="fas fa-calendar"></i>
                                    {{ $item['deadline_date'] }}
                                </span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
            @endif

            <!-- Team Date Stats -->
            @if($dashboardProjectOverdueStats['team_date']['total_overdue'] > 0)
            <div class="overdue-category-section">
                <h4 class="overdue-category-title">
                    <i class="fas fa-users"></i>
                    بناءً على التاريخ المحدد من الفريق
                </h4>
                <div class="overdue-cards-grid">
                    <div class="overdue-card warning">
                        <div class="overdue-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="overdue-content">
                            <div class="overdue-number">{{ $dashboardProjectOverdueStats['team_date']['overdue_active'] }}</div>
                            <div class="overdue-label">متأخرة ولم تكتمل</div>
                            <div class="overdue-description">مشاريع تجاوزت موعد الفريق</div>
                        </div>
                    </div>

                    <div class="overdue-card info">
                        <div class="overdue-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="overdue-content">
                            <div class="overdue-number">{{ $dashboardProjectOverdueStats['team_date']['completed_late'] }}</div>
                            <div class="overdue-label">مكتملة بتأخير</div>
                            <div class="overdue-description">اكتملت بعد موعد الفريق</div>
                        </div>
                    </div>

                    <div class="overdue-card danger">
                        <div class="overdue-icon">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <div class="overdue-content">
                            <div class="overdue-number">{{ $dashboardProjectOverdueStats['team_date']['total_overdue'] }}</div>
                            <div class="overdue-label">إجمالي المتأخرة</div>
                            <div class="overdue-description">حسب تاريخ الفريق</div>
                        </div>
                    </div>
                </div>

                <!-- Details for Team Date -->
                @if(count($dashboardProjectOverdueStats['team_date']['overdue_projects']) > 0)
                <div class="overdue-details-section">
                    <h5 class="overdue-details-title">
                        <i class="fas fa-list"></i>
                        تفاصيل المشاريع المتأخرة (تاريخ الفريق)
                    </h5>
                    <div class="overdue-projects-list">
                        @foreach($dashboardProjectOverdueStats['team_date']['overdue_projects'] as $item)
                        <div class="overdue-project-item">
                            <div class="overdue-project-header">
                                <h6 class="overdue-project-name">
                                    <i class="fas fa-project-diagram"></i>
                                    {{ $item['project']->name }}
                                </h6>
                                <span class="overdue-badge danger">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    متأخر {{ $item['days_overdue'] }} يوم
                                </span>
                            </div>
                            <div class="overdue-project-participants">
                                <strong>الموعد المحدد:</strong>
                                <span class="participant-badge">
                                    <i class="fas fa-calendar"></i>
                                    {{ $item['deadline_date'] }}
                                </span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
            @endif
        </div>
        @endif

        <!-- Tasks Overdue Stats -->
        @if(isset($dashboardTaskOverdueStats) && $dashboardTaskOverdueStats['total_overdue'] > 0)
        <div class="overdue-main-section">
            <h3 class="overdue-section-title">
                <i class="fas fa-tasks"></i>
                المهام المتأخرة
            </h3>

            <div class="overdue-cards-grid">
                <div class="overdue-card warning">
                    <div class="overdue-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="overdue-content">
                        <div class="overdue-number">{{ $dashboardTaskOverdueStats['overdue_pending'] }}</div>
                        <div class="overdue-label">متأخرة ولم تكتمل</div>
                        <div class="overdue-description">
                            عادية: {{ $dashboardTaskOverdueStats['regular_overdue_pending'] }} |
                            قوالب: {{ $dashboardTaskOverdueStats['template_overdue_pending'] }}
                        </div>
                    </div>
                </div>

                <div class="overdue-card info">
                    <div class="overdue-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="overdue-content">
                        <div class="overdue-number">{{ $dashboardTaskOverdueStats['completed_late'] }}</div>
                        <div class="overdue-label">مكتملة بتأخير</div>
                        <div class="overdue-description">
                            عادية: {{ $dashboardTaskOverdueStats['regular_completed_late'] }} |
                            قوالب: {{ $dashboardTaskOverdueStats['template_completed_late'] }}
                        </div>
                    </div>
                </div>

                <div class="overdue-card danger">
                    <div class="overdue-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="overdue-content">
                        <div class="overdue-number">{{ $dashboardTaskOverdueStats['total_overdue'] }}</div>
                        <div class="overdue-label">إجمالي المتأخرة</div>
                        <div class="overdue-description">جميع المهام المتأخرة</div>
                    </div>
                </div>
            </div>

            <!-- Task Details -->
            @if(isset($dashboardTaskOverdueDetails) && ($dashboardTaskOverdueDetails['overdue_pending']->count() > 0 || $dashboardTaskOverdueDetails['completed_late']->count() > 0))
            <div class="overdue-details-section">
                <h5 class="overdue-details-title">
                    <i class="fas fa-list"></i>
                    تفاصيل المهام المتأخرة
                </h5>
                <div class="overdue-projects-list">
                    @if($dashboardTaskOverdueDetails['overdue_pending']->count() > 0)
                    <div class="overdue-project-item">
                        <div class="overdue-project-header">
                            <h6 class="overdue-project-name">
                                <i class="fas fa-clock"></i>
                                مهام متأخرة ولم تكتمل
                            </h6>
                            <span class="overdue-badge danger">
                                <i class="fas fa-exclamation-triangle"></i>
                                {{ $dashboardTaskOverdueDetails['overdue_pending']->count() }} مهمة
                            </span>
                        </div>
                        <div class="overdue-project-participants">
                            <div class="participants-list">
                                @foreach($dashboardTaskOverdueDetails['overdue_pending']->take(10) as $task)
                                <span class="participant-badge">
                                    <i class="fas fa-{{ $task->task_type == 'template' ? 'layer-group' : 'tasks' }}"></i>
                                    {{ \Str::limit($task->task_name, 30) }}
                                    <small>({{ $task->user_name }} - {{ $task->task_type == 'template' ? 'قالب' : 'عادية' }} - متأخرة {{ $task->days_overdue }} يوم)</small>
                                </span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($dashboardTaskOverdueDetails['completed_late']->count() > 0)
                    <div class="overdue-project-item completed">
                        <div class="overdue-project-header">
                            <h6 class="overdue-project-name">
                                <i class="fas fa-check-circle"></i>
                                مهام مكتملة بتأخير
                            </h6>
                            <span class="overdue-badge info">
                                <i class="fas fa-check-circle"></i>
                                {{ $dashboardTaskOverdueDetails['completed_late']->count() }} مهمة
                            </span>
                        </div>
                        <div class="overdue-project-participants">
                            <div class="participants-list">
                                @foreach($dashboardTaskOverdueDetails['completed_late']->take(10) as $task)
                                <span class="participant-badge">
                                    <i class="fas fa-{{ $task->task_type == 'template' ? 'layer-group' : 'tasks' }}"></i>
                                    {{ \Str::limit($task->task_name, 30) }}
                                    <small>({{ $task->user_name }} - {{ $task->task_type == 'template' ? 'قالب' : 'عادية' }} - اكتملت بتأخير {{ $task->days_late }} يوم)</small>
                                </span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>
        @endif
    </div>
    @endif

    <!-- Tasks Overview -->
    <div class="section-modern">
        <div class="section-header">
            <h2>
                <i class="fas fa-tasks"></i>
                نظرة عامة على المهام
            </h2>
        </div>

        <div class="tasks-overview-grid">
            <div class="task-summary-card">
                <div class="task-icon all">
                    <i class="fas fa-list"></i>
                </div>
                <div class="task-info">
                    <div class="task-number">{{ $allTotalTasks }}</div>
                    <div class="task-label">إجمالي المهام</div>
                </div>
            </div>

            <div class="task-summary-card">
                <div class="task-icon new">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <div class="task-info">
                    <div class="task-number">{{ $allNewTasks }}</div>
                    <div class="task-label">جديدة</div>
                </div>
            </div>

            <div class="task-summary-card">
                <div class="task-icon progress">
                    <i class="fas fa-spinner"></i>
                </div>
                <div class="task-info">
                    <div class="task-number">{{ $allInProgressTasks }}</div>
                    <div class="task-label">قيد التنفيذ</div>
                </div>
            </div>

            <div class="task-summary-card">
                <div class="task-icon completed">
                    <i class="fas fa-check"></i>
                </div>
                <div class="task-info">
                    <div class="task-number">{{ $allCompletedTasks }}</div>
                    <div class="task-label">مكتملة</div>
                </div>
            </div>

            <div class="task-summary-card">
                <div class="task-icon paused">
                    <i class="fas fa-pause"></i>
                </div>
                <div class="task-info">
                    <div class="task-number">{{ $allPausedTasks }}</div>
                    <div class="task-label">متوقفة</div>
                </div>
            </div>

            <!-- المهام الأصلية -->
            <div class="task-summary-card" style="border-top: 4px solid #3498db;">
                <div class="task-icon" style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="task-info">
                    <div class="task-number">{{ $allOriginalTasks }}</div>
                    <div class="task-label">مهام أصلية</div>
                </div>
            </div>

            <!-- المهام الإضافية -->
            <div class="task-summary-card" style="border-top: 4px solid #e67e22;">
                <div class="task-icon" style="background: linear-gradient(135deg, #e67e22 0%, #d35400 100%);">
                    <i class="fas fa-plus-square"></i>
                </div>
                <div class="task-info">
                    <div class="task-number">{{ $allAdditionalTasks }}</div>
                    <div class="task-label">مهام إضافية</div>
                </div>
            </div>

            <div class="task-summary-card completion-rate">
                <div class="completion-circle">
                    <svg viewBox="0 0 36 36" class="circular-chart">
                        <path class="circle-bg" d="M18 2.0845
                            a 15.9155 15.9155 0 0 1 0 31.831
                            a 15.9155 15.9155 0 0 1 0 -31.831"/>
                        <path class="circle" stroke-dasharray="{{ $allTotalTasks > 0 ? round(($allCompletedTasks / $allTotalTasks) * 100) : 0 }}, 100" d="M18 2.0845
                            a 15.9155 15.9155 0 0 1 0 31.831
                            a 15.9155 15.9155 0 0 1 0 -31.831"/>
                        <text x="18" y="20.35" class="percentage">{{ $allTotalTasks > 0 ? round(($allCompletedTasks / $allTotalTasks) * 100) : 0 }}%</text>
                    </svg>
                </div>
                <div class="task-info">
                    <div class="task-label">معدل الإنجاز</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Today's Meetings -->
    @if($todayMeetings->count() > 0)
    <div class="section-modern">
        <div class="section-header">
            <h2>
                <i class="fas fa-calendar-day"></i>
            اجتماعات اليوم
            </h2>
        </div>

        <div class="meetings-grid">
            @foreach($todayMeetings->take(4) as $meeting)
            <div class="meeting-card-modern">
                <div class="meeting-time">
                    <i class="fas fa-clock"></i>
                    {{ \Carbon\Carbon::parse($meeting->start_time)->format('H:i') }}
                </div>
                <div class="meeting-title">{{ $meeting->title }}</div>
                <div class="meeting-client">
                    <i class="fas fa-user"></i>
                    {{ optional($meeting->client)->name ?? 'اجتماع داخلي' }}
                </div>
                <div class="meeting-participants">
                    <i class="fas fa-users"></i>
                    {{ $meeting->participants->count() }} مشارك
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif


    <!-- Charts Section -->
    <div class="charts-section mt-5">
        <div class="chart-card">
            <div class="chart-header">
                <h3>
                    <i class="fas fa-chart-pie"></i>
                    توزيع حالات المشاريع
                </h3>
            </div>
            <div class="chart-container">
                <canvas id="projectStatusChart"></canvas>
            </div>
        </div>

        <div class="chart-card">
            <div class="chart-header">
                <h3>
                    <i class="fas fa-chart-bar"></i>
                    إحصائيات المهام
                </h3>
            </div>
            <div class="chart-container">
                <canvas id="tasksChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Top Employees -->
    <div class="section-modern">
        <div class="section-header">
            <h2>
                <i class="fas fa-star"></i>
            أفضل الموظفين
            </h2>
        </div>

        <div class="employees-grid">
            @foreach($topEmployees->take(5) as $index => $employee)
            <div class="employee-card-modern">
                <div class="employee-avatar">
                    @if($index == 0)
                        <i class="fas fa-crown"></i>
                    @elseif($index == 1)
                        <i class="fas fa-medal"></i>
                    @elseif($index == 2)
                        <i class="fas fa-award"></i>
                    @else
                        {{ $index + 1 }}
                    @endif
                </div>
                <div class="employee-name">{{ $employee->name }}</div>
                <div class="employee-projects">
                    <span class="project-count">{{ $employee->project_count }}</span>
                    <span class="project-label">مشروع</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Departments & Teams -->
    @if(isset($departmentsData) && is_object($departmentsData) && $departmentsData->count() > 0)
    @php
        $currentUserHierarchyLevel = \App\Models\RoleHierarchy::getUserMaxHierarchyLevel(Auth::user());
    @endphp
    <div class="section-modern">
        <div class="section-header">
            <h2>
                <i class="fas fa-users-cog"></i>
                الأقسام والفرق
            </h2>
            <span class="section-count">{{ isset($departmentsData) && is_object($departmentsData) ? $departmentsData->count() : 0 }}</span>
        </div>

        <div class="departments-grid">
            @if(isset($departmentsData) && is_object($departmentsData))
            @foreach($departmentsData as $department)
            @php
                $canAccessDepartment = $currentUserHierarchyLevel >= 5 ||
                    ($currentUserHierarchyLevel == 4 && Auth::user()->department == $department->department);
            @endphp
            <div class="department-card-modern {{ !$canAccessDepartment ? 'opacity-50' : '' }}" data-department="{{ $department->department }}">
                <div class="department-header">
                    <div class="department-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="department-info">
                        <h4 class="department-name">{{ $department->department }}</h4>
                        <p class="department-members">{{ $department->employees_count }} موظف</p>
                    </div>
                </div>

                <div class="department-stats">
                    <div class="stat-item">
                        <div class="stat-icon projects">
                            <i class="fas fa-project-diagram"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-number">{{ $department->active_projects }}</span>
                            <span class="stat-label">مشروع نشط</span>
                        </div>
                    </div>

                    <div class="stat-item">
                        <div class="stat-icon tasks">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-number">{{ $department->completed_tasks }}</span>
                            <span class="stat-label">مهمة مكتملة</span>
                        </div>
                    </div>

                    <div class="stat-item">
                        <div class="stat-icon rate">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-number">{{ $department->completion_rate }}%</span>
                            <span class="stat-label">معدل الإنجاز</span>
                        </div>
                    </div>
                </div>

                <div class="department-footer">
                    @if($canAccessDepartment)
                        <a href="{{ route('departments.show', ['department' => urlencode($department->department)]) }}" class="view-team-btn">
                            <i class="fas fa-users"></i>
                            عرض القسم
                        </a>
                    @else
                        <button class="view-team-btn" disabled title="غير مسموح لك بالوصول">
                            <i class="fas fa-lock"></i>
                            غير متاح
                        </button>
                    @endif
                </div>
            </div>
            @endforeach
            @endif
        </div>
    </div>
    @endif



    <!-- Active Projects Table -->
    <div class="section-modern">
        <div class="section-header">
            <h2>
                <i class="fas fa-play-circle"></i>
                        المشاريع النشطة
            </h2>
            <span class="section-count">{{ $activeProjects->count() }}</span>
                </div>

        <div class="modern-table-container">
                <div class="table-responsive">
                <table class="modern-table">
                        <thead>
                            <tr>
                                <th>المشروع</th>
                                <th>العميل</th>
                                <th>الحالة</th>
                                <th>التقدم</th>
                                <th>الموعد النهائي</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($activeProjects as $project)
                        <tr>
                                <td>
                                <div class="project-info">
                                    <div class="project-avatar">
                                            {{ substr($project->name, 0, 1) }}
                                        </div>
                                    <div class="project-details">
                                        <div class="project-name">
                                            <a href="{{ route('projects.show', $project) }}">{{ $project->name }}</a>
                                        </div>
                                        <div class="project-description">{{ Str::limit($project->description, 50) }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                <span class="client-name">{{ optional($project->client)->name ?? '-' }}</span>
                                </td>
                                <td>
                                <span class="status-badge status-{{ str_replace(' ', '-', $project->status) }}">
                                    {{ $project->status }}
                                </span>
                                </td>
                                <td>
                                <div class="progress-container">
                                    <div class="progress-bar">
                                        @php $progressValue = $project->progress ?? 0; @endphp
                                        <div class="progress-fill" style="width: {{ $progressValue }}%"></div>
                                    </div>
                                    <span class="progress-text">{{ $progressValue }}%</span>
                                </div>
                                </td>
                                <td>
                                    @php
                                        $deliveryDate = $project->client_agreed_delivery_date ?? $project->team_delivery_date;
                                    @endphp
                                    @if($deliveryDate)
                                    <span class="date {{ \Carbon\Carbon::parse($deliveryDate)->isPast() ? 'overdue' : '' }}">
                                            {{ \Carbon\Carbon::parse($deliveryDate)->format('Y/m/d') }}
                                        </span>
                                    @else
                                    <span class="date no-date">غير محدد</span>
                                    @endif
                                </td>
                                <td>
                                <div class="table-actions">
                                    <a href="{{ route('projects.show', $project) }}" class="action-btn-small primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    <a href="{{ route('projects.edit', $project) }}" class="action-btn-small secondary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                            <td colspan="6" class="empty-state">
                                <div class="empty-icon">
                                    <i class="fas fa-inbox"></i>
                                </div>
                                <div class="empty-text">لا توجد مشاريع نشطة حالياً</div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
            </div>
        </div>
    </div>

    <!-- Latest Projects -->
    <div class="section-modern">
        <div class="section-header">
            <h2>
                <i class="fas fa-clock"></i>
                أحدث المشاريع
            </h2>
        </div>

        <div class="projects-grid">
            @foreach($latestProjects as $project)
            <div class="project-card-modern">
                <div class="project-header">
                    <div class="project-icon">
                        <i class="fas fa-project-diagram"></i>
                    </div>
                    <div class="project-info">
                        <h4 class="project-name">{{ $project->name }}</h4>
                        <p class="project-client">
                            <i class="fas fa-user"></i>
                            {{ optional($project->client)->name ?? 'بدون عميل' }}
                        </p>
                    </div>
                </div>

                <div class="project-stats">
                    <div class="stat-item">
                        <div class="stat-icon tasks">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-number">{{ $project->tasks->count() }}</span>
                            <span class="stat-label">المهام</span>
                        </div>
                    </div>

                    <div class="stat-item">
                        <div class="stat-icon rate">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-number">{{ $project->progress ?? 0 }}%</span>
                            <span class="stat-label">التقدم</span>
                        </div>
                    </div>

                    <div class="stat-item">
                        <div class="stat-icon projects">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-number">{{ $project->services->count() }}</span>
                            <span class="stat-label">الخدمات</span>
                        </div>
                    </div>
                </div>

                <div class="project-footer">
                    <a href="{{ route('projects.show', $project) }}" class="view-team-btn">
                        <i class="fas fa-eye"></i>
                        عرض المشروع
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- ✅ آخر النشاطات - Recent Activities Section -->
    <div class="row">
        <!-- آخر المهام -->
        <div class="col-lg-6 col-md-6 mb-4">
            <div class="section-modern">
                <div class="section-header">
                    <h2>
                        <i class="fas fa-tasks"></i>
                        آخر المهام
                    </h2>
                    <span class="section-count">{{ $recentTasks->count() }}</span>
                </div>

                @if($recentTasks->count() > 0)
                <div class="recent-activities-list">
                    @foreach($recentTasks->take(6) as $recentTask)
                    <div class="activity-item" data-task-type="{{ $recentTask->task_type }}" data-status="{{ $recentTask->status }}">
                        <div class="activity-icon">
                            @if($recentTask->task_type === 'template')
                                <i class="fas fa-layer-group"></i>
                            @else
                                @switch($recentTask->status)
                                    @case('completed')
                                        <i class="fas fa-check-circle"></i>
                                        @break
                                    @case('in_progress')
                                        <i class="fas fa-play-circle"></i>
                                        @break
                                    @case('paused')
                                        <i class="fas fa-pause-circle"></i>
                                        @break
                                    @default
                                        <i class="fas fa-tasks"></i>
                                @endswitch
                            @endif
                        </div>
                        <div class="activity-content">
                            <div class="activity-title">
                                <span class="task-name">{{ Str::limit($recentTask->task_name, 40) }}</span>
                                @if($recentTask->task_type === 'template')
                                    <span class="task-type-badge template">
                                        <i class="fas fa-layer-group"></i>
                                        قالب
                                    </span>
                                @else
                                    <span class="task-type-badge regular">
                                        <i class="fas fa-tasks"></i>
                                        عادية
                                    </span>
                                @endif
                            </div>
                            <div class="activity-meta">
                                <span class="user-name">
                                    <i class="fas fa-user"></i>
                                    {{ $recentTask->user_name }}
                                </span>
                                @if($recentTask->project_name)
                                <span class="project-name">
                                    <i class="fas fa-project-diagram"></i>
                                    {{ Str::limit($recentTask->project_name, 20) }}
                                </span>
                                @endif
                            </div>
                            <div class="activity-time">
                                <i class="fas fa-clock"></i>
                                {{ \Carbon\Carbon::parse($recentTask->last_updated)->diffForHumans() }}
                            </div>
                        </div>
                        <div class="activity-status status-{{ $recentTask->status }}">
                            @switch($recentTask->status)
                                @case('completed')
                                    <i class="fas fa-check"></i>
                                    مكتملة
                                    @break
                                @case('in_progress')
                                    <i class="fas fa-play"></i>
                                    قيد التنفيذ
                                    @break
                                @case('paused')
                                    <i class="fas fa-pause"></i>
                                    متوقفة
                                    @break
                                @case('new')
                                    <i class="fas fa-plus"></i>
                                    جديدة
                                    @break
                                @default
                                    {{ $recentTask->status }}
                            @endswitch
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="empty-state">
                    <i class="fas fa-tasks"></i>
                    <p>لا توجد مهام حديثة</p>
                </div>
                @endif
            </div>
        </div>

        <!-- آخر المشاريع -->
        <div class="col-lg-6 col-md-6 mb-4">
            <div class="section-modern">
                <div class="section-header">
                    <h2>
                        <i class="fas fa-project-diagram"></i>
                        آخر المشاريع المحدثة
                    </h2>
                    <span class="section-count">{{ $recentProjects->count() }}</span>
                </div>

                @if($recentProjects->count() > 0)
                <div class="recent-projects-list">
                    @foreach($recentProjects->take(6) as $recentProject)
                    <div class="project-item-recent" data-status="{{ str_replace(' ', '-', $recentProject->status) }}">
                        <div class="project-icon">
                            <i class="fas fa-folder"></i>
                        </div>
                        <div class="project-content">
                            <div class="project-title">
                                <span class="project-name">
                                    <a href="{{ route('projects.show', $recentProject) }}">
                                        {{ Str::limit($recentProject->name, 40) }}
                                    </a>
                                </span>
                                <span class="project-type-badge">
                                    <i class="fas fa-project-diagram"></i>
                                    مشروع
                                </span>
                            </div>
                            <div class="project-meta">
                                <span class="project-client">
                                    <i class="fas fa-user"></i>
                                    {{ $recentProject->client->name ?? 'بدون عميل' }}
                                </span>
                                <span class="project-participants">
                                    <i class="fas fa-users"></i>
                                    {{ $recentProject->participants->count() }} مشارك
                                </span>
                            </div>
                            <div class="project-time">
                                <i class="fas fa-clock"></i>
                                {{ $recentProject->updated_at->diffForHumans() }}
                            </div>
                        </div>
                        <div class="project-status status-{{ str_replace(' ', '-', $recentProject->status) }}">
                            @switch($recentProject->status)
                                @case('جديد')
                                    <i class="fas fa-plus"></i>
                                    جديد
                                    @break
                                @case('جاري التنفيذ')
                                    <i class="fas fa-play"></i>
                                    جاري التنفيذ
                                    @break
                                @case('مكتمل')
                                    <i class="fas fa-check"></i>
                                    مكتمل
                                    @break
                                @case('ملغي')
                                    <i class="fas fa-times"></i>
                                    ملغي
                                    @break
                                @default
                                    {{ $recentProject->status }}
                            @endswitch
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="empty-state">
                    <i class="fas fa-project-diagram"></i>
                    <p>لا توجد مشاريع حديثة</p>
                </div>
                @endif
            </div>
        </div>

        <!-- آخر الاجتماعات -->
        <div class="col-lg-6 col-md-6 mb-4">
            <div class="section-modern">
                <div class="section-header">
                    <h2>
                        <i class="fas fa-calendar-alt"></i>
                        آخر الاجتماعات
                    </h2>
                    <span class="section-count">{{ $recentMeetings->count() }}</span>
                </div>

                @if($recentMeetings->count() > 0)
                <div class="recent-meetings-list">
                    @foreach($recentMeetings->take(6) as $recentMeeting)
                    <div class="meeting-item-recent" data-meeting-type="{{ $recentMeeting->client ? 'client' : 'internal' }}">
                        <div class="meeting-icon">
                            <i class="fas fa-video"></i>
                        </div>
                        <div class="meeting-content">
                            <div class="meeting-title">
                                <span class="meeting-name">{{ Str::limit($recentMeeting->title, 40) }}</span>
                                <span class="meeting-type-badge">
                                    <i class="fas fa-calendar-alt"></i>
                                    اجتماع
                                </span>
                            </div>
                            <div class="meeting-meta">
                                <span class="meeting-creator">
                                    <i class="fas fa-user-tie"></i>
                                    {{ $recentMeeting->creator->name }}
                                </span>
                                @if($recentMeeting->client)
                                <span class="meeting-client">
                                    <i class="fas fa-building"></i>
                                    {{ $recentMeeting->client->name }}
                                </span>
                                @endif
                                <span class="meeting-participants">
                                    <i class="fas fa-users"></i>
                                    {{ $recentMeeting->participants->count() }} مشارك
                                </span>
                            </div>
                            <div class="meeting-time">
                                <i class="fas fa-clock"></i>
                                @if($recentMeeting->start_time)
                                    {{ \Carbon\Carbon::parse($recentMeeting->start_time)->format('Y/m/d H:i') }}
                                @else
                                    {{ $recentMeeting->created_at->diffForHumans() }}
                                @endif
                            </div>
                        </div>
                        <div class="meeting-status">
                            @if($recentMeeting->start_time)
                                @if(\Carbon\Carbon::parse($recentMeeting->start_time)->isFuture())
                                    <i class="fas fa-clock"></i>
                                    قادم
                                @else
                                    <i class="fas fa-check"></i>
                                    منتهي
                                @endif
                            @else
                                <i class="fas fa-calendar-plus"></i>
                                مجدول
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="empty-state">
                    <i class="fas fa-calendar-alt"></i>
                    <p>لا توجد اجتماعات حديثة</p>
                </div>
                @endif
            </div>
        </div>

        <!-- آخر التعديلات -->
        @if($latestRevisions->count() > 0)
        <div class="col-lg-6 col-md-6 mb-4">
            <div class="section-modern">
                <div class="section-header">
                    <h2>
                        <i class="fas fa-history"></i>
                        آخر التعديلات
                    </h2>
                </div>

                <div class="recent-updates-list">
                    @if($latestRevisions->count() > 0)
                        @foreach($latestRevisions->take(6) as $revision)
                        <div class="alert-item" data-revision-type="{{ $revision->revision_type }}" data-status="{{ $revision->status }}">
                            <div class="alert-icon">
                                @if($revision->status == 'approved')
                                    <i class="fas fa-check-circle"></i>
                                @elseif($revision->status == 'rejected')
                                    <i class="fas fa-times-circle"></i>
                                @else
                                    <i class="fas fa-clock"></i>
                                @endif
                            </div>
                            <div class="alert-content">
                                <div class="alert-title">
                                    <span class="revision-name">{{ Str::limit($revision->title, 35) }}</span>
                                    <div class="revision-type-badge">
                                        <i class="fas fa-{{ $revision->revision_type == 'project' ? 'project-diagram' : ($revision->revision_type == 'general' ? 'globe' : 'tasks') }}"></i>
                                        {{ $revision->revision_type == 'project' ? 'مشروع' : ($revision->revision_type == 'general' ? 'عام' : 'مهمة') }}
                                    </div>
                                </div>
                                <div class="alert-meta">
                                    <div class="revision-creator">
                                        <i class="fas fa-user"></i>
                                        {{ $revision->creator->name }}
                                    </div>
                                    <div class="revision-time">
                                        <i class="fas fa-clock"></i>
                                        {{ $revision->revision_date->diffForHumans() }}
                                    </div>
                                </div>
                            </div>
                            <div class="alert-status status-{{ $revision->status }}">
                                @if($revision->status == 'approved')
                                    <i class="fas fa-check-circle"></i>
                                    موافق عليه
                                @elseif($revision->status == 'rejected')
                                    <i class="fas fa-times-circle"></i>
                                    مرفوض
                                @else
                                    <i class="fas fa-clock"></i>
                                    في الانتظار
                                @endif
                            </div>
                        </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Pass data to JavaScript
    window.projectData = {
        totalProjects: @json($totalProjects),
        newProjects: @json($newProjects),
        inProgressProjects: @json($inProgressProjects),
        completedProjects: @json($completedProjects),
        pausedProjects: @json($pausedProjects ?? 0),
        overdueProjectsCount: @json($overdueProjects->count())
    };

    window.taskData = {
        allNewTasks: {{ $allNewTasks }},
        allInProgressTasks: {{ $allInProgressTasks }},
        allPausedTasks: {{ $allPausedTasks }},
        allCompletedTasks: {{ $allCompletedTasks }}
    };
</script>
<script src="{{ asset('js/project-dashboard/dashboard/main.js') }}"></script>
@endpush
