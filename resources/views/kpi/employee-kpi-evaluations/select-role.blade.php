@extends('layouts.app')

@section('title', 'اختيار الدور لتقييم KPI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/projects-services.css') }}">
@endpush

@section('content')
<div class="simple-container">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h1>🎯 اختيار الدور لتقييم KPI</h1>
                    <p>اختر الدور الذي تريد تقييم أدائه بناءً على مؤشرات KPI</p>
                </div>
                <div class="mt-2 mt-md-0">
                    <a href="{{ route('dashboard') }}" class="btn btn-light btn-sm" style="color: white; background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3);">
                        <i class="fas fa-arrow-left me-2"></i>
                        العودة للوحة التحكم
                    </a>
                </div>
            </div>
        </div>

        @if($rolesCanEvaluate->count() > 0)
        <!-- Info Section -->
        <div class="stats-row mb-4">
            <div class="stat-card">
                <div class="stat-number">{{ $rolesCanEvaluate->count() }}</div>
                <div class="stat-label">أدوار متاحة للتقييم</div>
            </div>
        </div>

        <!-- Info Card -->
        <div class="projects-table-container mb-4">
            <div class="table-header">
                <h2>📋 تعليمات التقييم</h2>
            </div>
            <div style="padding: 1.5rem;">
                <p class="mb-0" style="color: #6b7280;">
                    اختر الدور المناسب من القائمة أدناه. سيتم توجيهك إلى صفحة تقييم KPI المخصصة لهذا الدور
                    مع جميع مؤشرات الأداء المطلوبة.
                </p>
            </div>
        </div>

        <!-- Roles Grid -->
        <div class="stats-row" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
            @foreach($rolesCanEvaluate as $mapping)
            <div class="stat-card" style="text-align: center;">
                <div style="font-size: 3rem; color: #667eea; margin-bottom: 1rem;">
                    <i class="fas fa-user-tie"></i>
                </div>
                <h4 style="margin: 0.5rem 0; color: #1f2937; font-weight: 600;">{{ $mapping->roleToEvaluate->display_name ?? $mapping->roleToEvaluate->name }}</h4>
                @if($mapping->department_name)
                <div style="color: #6b7280; font-size: 0.9rem; margin-bottom: 1rem;">
                    <i class="fas fa-building"></i> {{ $mapping->department_name }}
                </div>
                @endif
                <a href="{{ route('kpi-evaluation.create', ['role_id' => $mapping->roleToEvaluate->id]) }}"
                    class="services-btn" style="margin-top: 1rem;">
                    <i class="fas fa-play"></i>
                    بدء التقييم
                </a>
            </div>
            @endforeach
        </div>
        @else
        <!-- Empty State -->
        <div class="projects-table-container">
            <div class="table-header">
                <h2>لا توجد أدوار متاحة</h2>
            </div>
            <div class="empty-state" style="padding: 3rem 2rem;">
                <i class="fas fa-info-circle"></i>
                <h4>لا توجد أدوار متاحة للتقييم</h4>
                <p>لا يمكنك تقييم أي أدوار حالياً. يرجى التواصل مع الإدارة.</p>
                <div class="d-flex justify-content-center gap-2 flex-wrap" style="margin-top: 2rem;">
                    <a href="{{ route('dashboard') }}" class="services-btn" style="background: linear-gradient(135deg, #6b7280, #4b5563);">
                        <i class="fas fa-arrow-left"></i>
                        العودة للوحة التحكم
                    </a>
                    <a href="{{ route('role-evaluation-mapping.index') }}" class="services-btn">
                        <i class="fas fa-cog"></i>
                        إعدادات ربط الأدوار
                    </a>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection