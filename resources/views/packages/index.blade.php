@extends('layouts.app')

@section('title', 'إدارة الباقات')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/packages.css') }}">
@endpush

@section('content')
<div class="simple-container">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>📦 إدارة الباقات</h1>
            <p>إدارة وتنظيم جميع باقات الخدمات بطريقة احترافية</p>
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

        <!-- Statistics Row -->
        @php
            $totalPackages = $packages->total();
            $totalServices = $packages->sum(function($package) {
                return count($package->services);
            });
            $totalPoints = $packages->sum('total_points');
        @endphp

        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-number">{{ $totalPackages }}</div>
                <div class="stat-label">إجمالي الباقات</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $totalServices }}</div>
                <div class="stat-label">إجمالي الخدمات</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $totalPoints }}</div>
                <div class="stat-label">إجمالي النقاط</div>
            </div>
        </div>

        <!-- Packages Table -->
        <div class="packages-table-container">
            <div class="table-header">
                <h2>📋 قائمة الباقات</h2>
            </div>

            <table class="packages-table">
                <thead>
                    <tr>
                        <th>الباقة</th>
                        <th>الوصف</th>
                        <th>عدد الخدمات</th>
                        <th>مجموع النقاط</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($packages as $package)
                    <tr>
                        <td>
                            <div class="package-info">
                                <div class="package-avatar">
                                    <i class="fas fa-box"></i>
                                </div>
                                <div class="package-details">
                                    <h4>{{ $package->name }}</h4>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div style="color: #6b7280;">
                                {{ \Illuminate\Support\Str::limit($package->description, 50) ?: 'لا يوجد وصف' }}
                            </div>
                        </td>
                        <td>
                            <div class="service-count-badge">
                                <i class="fas fa-cogs"></i>
                                {{ count($package->services) }}
                            </div>
                        </td>
                        <td>
                            <div class="points-badge">
                                <i class="fas fa-star"></i>
                                {{ $package->total_points }}
                            </div>
                        </td>
                        <td>
                            <div class="action-group">
                                <a href="{{ route('packages.show', $package) }}" class="packages-btn btn-info" title="عرض">
                                    <i class="fas fa-eye"></i>
                                    عرض
                                </a>
                                <a href="{{ route('packages.edit', $package) }}" class="packages-btn btn-warning" title="تعديل">
                                    <i class="fas fa-edit"></i>
                                    تعديل
                                </a>
                                <form action="{{ route('packages.destroy', $package) }}" method="POST" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="packages-btn btn-danger"
                                            onclick="return confirm('هل أنت متأكد من حذف هذه الباقة؟')"
                                            title="حذف">
                                        <i class="fas fa-trash"></i>
                                        حذف
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <h4>لا توجد باقات</h4>
                            <p>لم يتم إنشاء أي باقات حتى الآن</p>
                            <a href="{{ route('packages.create') }}" class="packages-btn">
                                <i class="fas fa-plus"></i>
                                إضافة باقة جديدة
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- Pagination -->
            @if($packages->hasPages())
                <div class="d-flex justify-content-center mt-4">
                    {{ $packages->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- FAB Button -->
<a href="{{ route('packages.create') }}" class="fab" title="إضافة باقة جديدة">
    <i class="fas fa-plus"></i>
</a>
@endsection
