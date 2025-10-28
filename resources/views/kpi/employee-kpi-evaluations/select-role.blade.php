@extends('layouts.app')

@section('title', 'اختيار الدور لتقييم KPI')

@push('styles')
<link href="{{ asset('css/evaluation-criteria-modern.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="evaluation-container">
    <!-- Header Section -->
    <div class="modern-card modern-card-header-white mb-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px; box-shadow: 0 8px 32px rgba(0,0,0,0.1);">
        <div class="modern-card-header d-flex justify-content-between align-items-center" style="color: white;">
            <div class="d-flex align-items-center">
                <div class="icon-container me-3">
                    <i class="fas fa-magic floating" style="font-size: 2rem; color: white;"></i>
                </div>
                <div>
                    <h2 class="mb-1" style="color: white !important;">🎯 اختيار الدور لتقييم KPI</h2>
                    <p class="mb-0" style="color: white !important; opacity: 0.9;">اختر الدور الذي تريد تقييم أدائه بناءً على مؤشرات KPI</p>
                </div>
            </div>
            <div>
                <a href="{{ route('dashboard') }}" class="btn btn-modern btn-outline-light" style="color: white; border-color: rgba(255,255,255,0.5);">
                    <i class="fas fa-arrow-left me-2"></i>
                    العودة للوحة التحكم
                </a>
            </div>
        </div>
    </div>

    @if($rolesCanEvaluate->count() > 0)
        <!-- Info Section -->
        <div class="modern-card mb-4">
            <div class="modern-card-body">
                <div class="d-flex align-items-center">
                    <div class="stats-card me-4">
                        <div class="stats-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stats-content">
                            <h3 class="stats-number">{{ $rolesCanEvaluate->count() }}</h3>
                            <p class="stats-label">أدوار متاحة للتقييم</p>
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="mb-2">📋 تعليمات التقييم</h5>
                                                    <p class="text-muted mb-0">
                            اختر الدور المناسب من القائمة أدناه. سيتم توجيهك إلى صفحة تقييم KPI المخصصة لهذا الدور
                            مع جميع مؤشرات الأداء المطلوبة.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Roles Grid -->
        <div class="role-selection-grid">
            @foreach($rolesCanEvaluate as $mapping)
                <div class="role-card-modern">
                    <div class="role-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="role-content">
                        <h4 class="role-title">{{ $mapping->roleToEvaluate->display_name ?? $mapping->roleToEvaluate->name }}</h4>
                        <div class="role-details">
                            <div class="detail-item">
                                <i class="fas fa-building text-primary"></i>
                                <span>{{ $mapping->department_name ?? 'غير محدد' }}</span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-layer-group text-success"></i>
                                <span>المستوى: {{ $mapping->hierarchy_level ?? 'غير محدد' }}</span>
                            </div>
                        </div>
                        <div class="role-actions">
                            <a href="{{ route('kpi-evaluation.create', ['role_id' => $mapping->roleToEvaluate->id]) }}"
                               class="btn btn-modern btn-primary">
                                <i class="fas fa-play me-2"></i>
                                بدء التقييم
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <!-- Empty State -->
        <div class="modern-card">
            <div class="modern-card-body text-center py-5">
                <div class="empty-state">
                    <i class="fas fa-info-circle mb-3" style="font-size: 4rem; color: var(--color-muted);"></i>
                    <h4 class="text-muted">لا توجد أدوار متاحة للتقييم</h4>
                    <p class="text-muted mb-4">لا يمكنك تقييم أي أدوار حالياً. يرجى التواصل مع الإدارة.</p>

                    <div class="d-flex justify-content-center gap-3">
                        <a href="{{ route('dashboard') }}" class="btn btn-modern btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>
                            العودة للوحة التحكم
                        </a>
                        <a href="{{ route('role-evaluation-mapping.index') }}" class="btn btn-modern btn-primary">
                            <i class="fas fa-cog me-2"></i>
                            إعدادات ربط الأدوار
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<style>
.empty-state {
    max-width: 500px;
    margin: 0 auto;
}

.role-details {
    margin: 1rem 0;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
    color: var(--color-muted);
}

.detail-item i {
    width: 16px;
}

.role-actions {
    margin-top: 1.5rem;
}

@media (max-width: 768px) {
    .modern-card-header {
        flex-direction: column;
        text-align: center;
    }

    .modern-card-header > div {
        margin-bottom: 1rem;
    }

    .modern-card-header > div:last-child {
        margin-bottom: 0;
    }

    .stats-card {
        margin-bottom: 1rem;
    }
}
</style>
@endsection

