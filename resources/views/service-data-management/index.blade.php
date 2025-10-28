@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/service-data-management.css') }}?v={{ time() }}">
@endpush

@section('content')
<div class="full-width-content">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-1">
                    <i class="fas fa-database me-2"></i>
                    إدارة بيانات الخدمات
                </h2>
                <p class="mb-0" style="opacity: 0.9;">يمكنك إضافة حقول مخصصة لكل خدمة تظهر عند إضافة المشروع</p>
            </div>
            <div style="font-size: 3rem; opacity: 0.2;">
                <i class="fas fa-cogs"></i>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4">
        @forelse($services as $service)
            <div class="col-md-6 col-lg-4">
                <div class="service-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="flex-grow-1">
                                <h5 class="card-title">{{ $service->name }}</h5>
                                @if($service->department)
                                    <small class="text-muted">
                                        <i class="fas fa-building me-1"></i>
                                        {{ $service->department }}
                                    </small>
                                @endif
                            </div>
                            @if($service->is_active)
                                <span class="badge badge-modern bg-success">نشط</span>
                            @else
                                <span class="badge badge-modern bg-secondary">غير نشط</span>
                            @endif
                        </div>

                        @if($service->description)
                            <p class="card-text text-muted small mb-3" style="min-height: 60px;">
                                {{ Str::limit($service->description, 100) }}
                            </p>
                        @endif

                        <div class="row mb-3">
                            <div class="col-6">
                                <div style="background: #fff3cd; padding: 0.75rem; border-radius: 10px; text-align: center;">
                                    <i class="fas fa-star text-warning"></i>
                                    <div class="fw-bold text-warning">{{ $service->points }}</div>
                                    <small class="text-muted">النقاط</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div style="background: #d1ecf1; padding: 0.75rem; border-radius: 10px; text-align: center;">
                                    <i class="fas fa-tasks text-info"></i>
                                    <div class="fw-bold text-info">{{ $service->task_templates_count }}</div>
                                    <small class="text-muted">القوالب</small>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                @php
                                    $fieldsCount = $service->dataFields()->count();
                                @endphp
                                <span class="text-muted small">الحقول المخصصة:</span>
                                <strong class="{{ $fieldsCount > 0 ? 'text-primary' : 'text-muted' }}" style="font-size: 1.2rem;">
                                    {{ $fieldsCount }}
                                </strong>
                            </div>

                            <a href="{{ route('service-data.manage', $service->id) }}"
                               class="btn btn-primary btn-action btn-sm">
                                <i class="fas fa-cog me-1"></i> إدارة
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h5>لا توجد خدمات متاحة</h5>
                    <p>لا توجد خدمات مضافة في النظام حالياً</p>
                </div>
            </div>
        @endforelse
    </div>
</div>
@endsection
