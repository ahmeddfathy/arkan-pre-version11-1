@extends('layouts.app')

@section('title', 'تفاصيل الباقة')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/packages.css') }}">
@endpush

@section('content')
<div class="simple-container">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>🔍 تفاصيل الباقة</h1>
            <p>عرض معلومات تفصيلية عن الباقة</p>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-10 col-md-12">
                <div class="package-card">
                    <div class="package-card-header">
                        <h2>{{ $package->name }}</h2>
                    </div>

                    <div class="package-card-body">
                        <!-- Package Information -->
                        <div class="info-grid">
                            <div class="info-item">
                                <h5><i class="fas fa-align-left"></i> الوصف</h5>
                                <p>{{ $package->description ?: 'لا يوجد وصف' }}</p>
                            </div>

                            <div class="info-item">
                                <h5><i class="fas fa-cogs"></i> عدد الخدمات</h5>
                                <p>
                                    <span class="service-count-badge">
                                        <i class="fas fa-cogs"></i>
                                        {{ count($services) }} خدمة
                                    </span>
                                </p>
                            </div>
                        </div>

                        <!-- Total Points Display -->
                        <div class="total-points-display">
                            <h5>مجموع النقاط الكلي</h5>
                            <div class="total-points-number">
                                <i class="fas fa-star"></i>
                                {{ $package->total_points }}
                            </div>
                        </div>

                        <!-- Services List -->
                        <div class="services-list-section">
                            <h5><i class="fas fa-list"></i> الخدمات المضمنة في الباقة</h5>

                            @if(count($services) > 0)
                                <table class="services-table">
                                    <thead>
                                        <tr>
                                            <th>اسم الخدمة</th>
                                            <th>النقاط</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($services as $service)
                                            <tr>
                                                <td>
                                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                                        <i class="fas fa-cog" style="color: #667eea;"></i>
                                                        {{ $service->name }}
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="service-points-badge">
                                                        <i class="fas fa-star"></i>
                                                        {{ $service->points }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <p style="color: #6b7280; text-align: center; padding: 2rem;">
                                    <i class="fas fa-info-circle"></i> لا توجد خدمات مضافة في هذه الباقة
                                </p>
                            @endif
                        </div>

                        <!-- Action Buttons -->
                        <div style="margin-top: 2rem; display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                <form action="{{ route('packages.destroy', $package) }}" method="POST" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="packages-btn btn-danger"
                                            onclick="return confirm('هل أنت متأكد من حذف هذه الباقة؟')">
                                        <i class="fas fa-trash"></i>
                                        حذف الباقة
                                    </button>
                                </form>
                            </div>

                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                <a href="{{ route('packages.edit', $package) }}" class="packages-btn btn-warning">
                                    <i class="fas fa-edit"></i>
                                    تعديل
                                </a>
                                <a href="{{ route('packages.index') }}" class="packages-btn btn-primary">
                                    <i class="fas fa-list"></i>
                                    قائمة الباقات
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
