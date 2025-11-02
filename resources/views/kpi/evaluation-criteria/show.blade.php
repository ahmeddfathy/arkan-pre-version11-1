@extends('layouts.app')

@section('title', 'تفاصيل بند التقييم')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/kpi/evaluation-criteria-show.css') }}">
@endpush

@section('content')
<div class="simple-container">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1>🔍 تفاصيل البند</h1>
            <p>عرض شامل لجميع تفاصيل ومعلومات البند</p>
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

        <div class="row">
            <div class="col-md-8">
                <!-- Main Info Card -->
                <div class="projects-table-container" style="margin-bottom: 2rem;">
                    <div class="table-header">
                        <h2>📋 معلومات البند</h2>
                    </div>

                    <div class="p-4">
                        <!-- Name and Points -->
                        <div class="mb-4">
                            <h3 style="color: #1f2937; margin-bottom: 1rem;">{{ $evaluationCriteria->criteria_name }}</h3>
                            @if($evaluationCriteria->criteria_description)
                            <p style="color: #6b7280; line-height: 1.6;">{{ $evaluationCriteria->criteria_description }}</p>
                            @endif
                        </div>

                        <!-- Stats -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div style="background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1)); padding: 1.5rem; border-radius: 12px; text-align: center;">
                                    <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                                        <i class="fas fa-star text-white fa-xl"></i>
                                    </div>
                                    <div style="font-size: 2rem; font-weight: 700; color: #667eea; margin-bottom: 0.5rem;">{{ $evaluationCriteria->max_points }}</div>
                                    <div style="color: #6b7280; font-weight: 600;">أقصى نقاط</div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div style="background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1)); padding: 1.5rem; border-radius: 12px; text-align: center;">
                                    <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                                        <i class="fas fa-user-tie text-white fa-xl"></i>
                                    </div>
                                    <div style="font-size: 1.1rem; font-weight: 600; color: #667eea; margin-bottom: 0.5rem;">{{ $evaluationCriteria->role->display_name ?? $evaluationCriteria->role->name }}</div>
                                    <div style="color: #6b7280; font-weight: 600;">الدور المسؤول</div>
                                </div>
                            </div>
                        </div>

                        <!-- Type Badge -->
                        <div class="mb-4 text-center">
                            @switch($evaluationCriteria->criteria_type)
                            @case('positive')
                            <span class="status-badge status-completed" style="font-size: 1.1rem; padding: 0.75rem 1.5rem;">
                                <i class="fas fa-check-circle ml-1"></i>
                                ✅ بند إيجابي
                            </span>
                            @break
                            @case('negative')
                            <span class="status-badge" style="background: #ef4444; font-size: 1.1rem; padding: 0.75rem 1.5rem;">
                                <i class="fas fa-times-circle ml-1"></i>
                                ❌ بند سلبي / خصم
                            </span>
                            @break
                            @case('bonus')
                            <span class="status-badge" style="background: #f59e0b; font-size: 1.1rem; padding: 0.75rem 1.5rem;">
                                <i class="fas fa-star ml-1"></i>
                                🌟 بونص إضافي
                            </span>
                            @break
                            @endswitch
                        </div>
                    </div>
                </div>

                <!-- Additional Details -->
                <div class="projects-table-container">
                    <div class="table-header">
                        <h2>📊 تفاصيل إضافية</h2>
                    </div>

                    <div class="p-4">
                        <div class="row">
                            @if($evaluationCriteria->category)
                            <div class="col-md-6 mb-3">
                                <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                                    <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-folder text-white"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight: 600; color: #1f2937;">📂 الفئة</div>
                                        <div style="color: #6b7280;">{{ $evaluationCriteria->category }}</div>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <div class="col-md-6 mb-3">
                                <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                                    <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-toggle-{{ $evaluationCriteria->is_active ? 'on' : 'off' }} text-white"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight: 600; color: #1f2937;">⚡ الحالة</div>
                                        <div>
                                            @if($evaluationCriteria->is_active)
                                            <span class="status-badge status-completed">✅ نشط</span>
                                            @else
                                            <span class="status-badge" style="background: #6b7280;">⏸️ غير نشط</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                                    <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-{{ $evaluationCriteria->evaluate_per_project ? 'project-diagram' : 'calendar-alt' }} text-white"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight: 600; color: #1f2937;">🎯 نوع التقييم</div>
                                        <div>
                                            @if($evaluationCriteria->evaluate_per_project)
                                            <span class="status-badge" style="background: #3b82f6;">🚀 لكل مشروع</span>
                                            @else
                                            <span class="status-badge" style="background: #f59e0b;">📅 دوري</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                                    <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-calendar text-white"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight: 600; color: #1f2937;">📅 فترة التقييم</div>
                                        <div style="color: #6b7280;">
                                            @if($evaluationCriteria->evaluation_period == 'monthly')
                                            📅 شهري
                                            @else
                                            ⚡ نصف شهري
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Actions Card -->
                <div class="help-card">
                    <h3>
                        <i class="fas fa-cog"></i>
                        الإجراءات
                    </h3>
                    <div class="d-flex flex-column gap-2">
                        <a href="{{ route('evaluation-criteria.index') }}" class="services-btn" style="background: linear-gradient(135deg, #667eea, #764ba2); text-decoration: none; text-align: center;">
                            <i class="fas fa-arrow-left ml-1"></i>
                            العودة للقائمة
                        </a>
                        <a href="{{ route('evaluation-criteria.edit', $evaluationCriteria) }}" class="services-btn" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed); text-decoration: none; text-align: center;">
                            <i class="fas fa-edit ml-1"></i>
                            تعديل البند
                        </a>
                    </div>
                </div>

                <!-- Timeline Card -->
                <div class="info-card">
                    <h3>
                        <i class="fas fa-clock"></i>
                        الجدول الزمني
                    </h3>
                    <ul class="info-list">
                        <li>
                            <span class="info-label">
                                <i class="fas fa-plus-circle text-primary ml-1"></i>
                                تاريخ الإنشاء
                            </span>
                            <span class="info-value">{{ $evaluationCriteria->created_at->format('Y-m-d H:i') }}</span>
                        </li>
                        <li>
                            <span class="info-label">
                                <i class="fas fa-edit text-warning ml-1"></i>
                                آخر تحديث
                            </span>
                            <span class="info-value">{{ $evaluationCriteria->updated_at->format('Y-m-d H:i') }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection