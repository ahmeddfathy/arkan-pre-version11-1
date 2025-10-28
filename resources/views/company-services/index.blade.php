@extends('layouts.app')

@section('title', 'إدارة خدمات الشركة')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/company-services-design.css') }}">
@endpush

@section('content')
<div class="simple-container">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>⚙️ إدارة خدمات الشركة</h1>
            <p>إدارة وتنظيم جميع خدمات الشركة بطريقة احترافية</p>
        </div>

        <!-- Success/Error Messages -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Filters Section -->
        <div class="filters-section">
            <form method="GET" action="{{ route('company-services.index') }}" id="filterForm">
                <div class="filters-row">
                    <!-- Department Filter -->
                    <div class="filter-group">
                        <label for="departmentFilter" class="filter-label">
                            <i class="fas fa-building"></i>
                            القسم
                        </label>
                        <select id="departmentFilter" name="department" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                            <option value="">جميع الأقسام</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept }}" {{ request('department') == $dept ? 'selected' : '' }}>{{ $dept }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div class="filter-group">
                        <label for="statusFilter" class="filter-label">
                            <i class="fas fa-toggle-on"></i>
                            الحالة
                        </label>
                        <select id="statusFilter" name="status" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                            <option value="">جميع الحالات</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>نشطة</option>
                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>معطلة</option>
                        </select>
                    </div>

                    <!-- Clear Filters -->
                    @if(request()->hasAny(['department', 'status']))
                        <div class="filter-group">
                            <label class="filter-label" style="opacity: 0;">مسح</label>
                            <a href="{{ route('company-services.index') }}" class="services-btn btn-danger">
                                <i class="fas fa-times"></i>
                                مسح الفلاتر
                            </a>
                        </div>
                    @endif

                    <!-- Add New Service Button -->
                    <div class="filter-group" style="margin-right: auto;">
                        <label class="filter-label" style="opacity: 0;">إضافة</label>
                        <a href="{{ route('company-services.create') }}" class="services-btn">
                            <i class="fas fa-plus"></i>
                            إضافة خدمة جديدة
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Statistics Row -->
        @php
            $activeCount = $services->filter(function($service) {
                return $service->is_active;
            })->count();
            $inactiveCount = $services->total() - $activeCount;
        @endphp

        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-number">{{ $services->total() }}</div>
                <div class="stat-label">إجمالي الخدمات</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $activeCount }}</div>
                <div class="stat-label">خدمات نشطة</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $inactiveCount }}</div>
                <div class="stat-label">خدمات معطلة</div>
            </div>
        </div>

        <!-- Services Table -->
        <div class="services-table-container">
            <div class="table-header">
                <h2>📋 قائمة خدمات الشركة</h2>
            </div>

            <table class="services-table">
                <thead>
                    <tr>
                        <th>الخدمة</th>
                        <th>الوصف</th>
                        <th>النقاط</th>
                        <th>حد المشروع</th>
                        <th>القسم</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($services as $service)
                    <tr>
                        <td>
                            <div class="service-info">
                                <div class="service-avatar">
                                    <i class="fas fa-cogs"></i>
                                </div>
                                <div class="service-details">
                                    <h4>{{ $service->name }}</h4>
                                    <p>ID: {{ $service->id }}</p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div style="color: #6b7280;">
                                {{ \Illuminate\Support\Str::limit($service->description, 50) ?: 'لا يوجد وصف' }}
                            </div>
                        </td>
                        <td>
                            <div class="points-badge">
                                <i class="fas fa-star"></i>
                                {{ $service->points }}
                            </div>
                        </td>
                        <td>
                            @if($service->max_points_per_project > 0)
                                <div class="limit-badge">
                                    <i class="fas fa-shield-alt"></i>
                                    {{ $service->max_points_per_project }}
                                </div>
                            @else
                                <div class="unlimited-badge">
                                    <i class="fas fa-infinity"></i>
                                    بلا حدود
                                </div>
                            @endif
                        </td>
                        <td>
                            <div style="color: #374151; font-weight: 500;">
                                {{ $service->department ?? 'كل الأقسام' }}
                            </div>
                        </td>
                        <td>
                            <form action="{{ route('company-services.toggle-status', $service) }}" method="POST" style="display: inline;">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="status-badge {{ $service->is_active ? 'active' : 'inactive' }}" style="border: none; cursor: pointer;">
                                    @if($service->is_active)
                                        <i class="fas fa-check-circle"></i> مفعلة
                                    @else
                                        <i class="fas fa-times-circle"></i> معطلة
                                    @endif
                                </button>
                            </form>
                        </td>
                        <td>
                            <div class="action-group">
                                <a href="{{ route('company-services.show', $service) }}" class="services-btn btn-info" title="عرض">
                                    <i class="fas fa-eye"></i>
                                    عرض
                                </a>
                                <a href="{{ route('company-services.edit', $service) }}" class="services-btn btn-warning" title="تعديل">
                                    <i class="fas fa-edit"></i>
                                    تعديل
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <h4>لا توجد خدمات</h4>
                            <p>لم يتم العثور على خدمات مطابقة للفلاتر المحددة</p>
                            <a href="{{ route('company-services.create') }}" class="services-btn">
                                <i class="fas fa-plus"></i>
                                إضافة خدمة جديدة
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- Pagination -->
            @if($services->hasPages())
                <div class="d-flex justify-content-center mt-4">
                    {{ $services->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- FAB Button -->
<a href="{{ route('company-services.create') }}" class="fab" title="إضافة خدمة جديدة">
    <i class="fas fa-plus"></i>
</a>
@endsection
