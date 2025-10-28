@extends('layouts.app')

@section('title', 'تفاصيل بند التقييم')

@push('styles')
    <link href="{{ asset('css/evaluation-criteria-modern.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="container-fluid evaluation-container">
    <div class="row justify-content-center">
        <div class="col-xl-10 col-lg-11">
            <!-- 🎯 Header Section -->
            <div class="modern-card mb-5 fade-in-up">
                <div class="text-center p-5" style="background: linear-gradient(135deg, #ffffff 0%, #f8f9ff 100%); border-radius: 20px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);">
                    <div class="d-inline-block p-3 rounded-circle mb-4 floating" style="background: linear-gradient(135deg, #4facfe, #00f2fe); box-shadow: 0 8px 20px rgba(79, 172, 254, 0.3);">
                        <i class="fas fa-search-plus fa-3x text-white"></i>
                    </div>
                    <h1 class="display-6 fw-bold mb-3" style="color: #2c3e50;">🔍 تفاصيل بند التقييم</h1>
                    <p class="lead mb-4" style="color: #6c757d;">
                        عرض شامل لجميع تفاصيل ومعلومات البند
                    </p>

                    <div class="d-flex flex-wrap justify-content-center gap-2">
                        <a href="{{ route('evaluation-criteria.index') }}" class="btn btn-modern btn-primary-modern">
                            <i class="fas fa-arrow-left me-2"></i>العودة للقائمة
                        </a>
                        <a href="{{ route('evaluation-criteria.edit', $evaluationCriteria) }}" class="btn btn-modern btn-warning-modern">
                            <i class="fas fa-edit me-2"></i>تعديل البند
                        </a>
                    </div>
                </div>
            </div>

            <!-- 📋 Details Card -->
            <div class="modern-card slide-in-right">
                <div class="modern-card-header">
                    <h3 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        معلومات البند
                    </h3>
                </div>
                <div class="modern-card-body">
                    <!-- 🎯 Main Info Grid -->
                    <div class="row g-4 mb-4">
                        <div class="col-lg-6">
                            <div class="stats-card h-100">
                                <div class="mb-3">
                                    <div class="d-inline-block p-2 rounded-circle" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                                        <i class="fas fa-user-tie text-white"></i>
                                    </div>
                                </div>
                                <h6 class="fw-bold text-muted mb-2">👤 الدور المسؤول</h6>
                                <h5 class="gradient-text">{{ $evaluationCriteria->role->display_name ?? $evaluationCriteria->role->name }}</h5>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="stats-card h-100">
                                <div class="mb-3">
                                    <div class="d-inline-block p-2 rounded-circle" style="background: linear-gradient(135deg, #4facfe, #00f2fe);">
                                        <i class="fas fa-tags text-white"></i>
                                    </div>
                                </div>
                                <h6 class="fw-bold text-muted mb-2">🏷️ نوع البند</h6>
                                @switch($evaluationCriteria->criteria_type)
                                    @case('positive')
                                        <span class="badge badge-modern badge-success-modern">✅ بند إيجابي</span>
                                        @break
                                    @case('negative')
                                        <span class="badge badge-modern" style="background: var(--danger-gradient);">❌ خصم/سالب</span>
                                        @break
                                    @case('bonus')
                                        <span class="badge badge-modern badge-warning-modern">🌟 بونص إضافي</span>
                                        @break
                                @endswitch
                            </div>
                        </div>
                    </div>

                    <!-- 📝 Content Section -->
                    <div class="p-4 rounded-4 mb-4" style="background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.05));">
                        <div class="row">
                            <div class="col-lg-8">
                                <h6 class="fw-bold text-muted mb-2">📝 اسم البند</h6>
                                <h4 class="fw-bold mb-3">{{ $evaluationCriteria->criteria_name }}</h4>

                                @if($evaluationCriteria->criteria_description)
                                    <h6 class="fw-bold text-muted mb-2">📋 وصف البند</h6>
                                    <p class="text-muted mb-0">{{ $evaluationCriteria->criteria_description }}</p>
                                @endif
                            </div>

                            <div class="col-lg-4">
                                <div class="text-center">
                                    <div class="stats-number">{{ $evaluationCriteria->max_points }}</div>
                                    <h6 class="fw-bold text-muted">🔢 أقصى نقاط</h6>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 📊 Additional Details -->
                    <div class="row g-4 mb-4">
                        @if($evaluationCriteria->category)
                        <div class="col-md-4">
                            <div class="stats-card h-100">
                                <div class="mb-2">
                                    <i class="fas fa-folder-open fa-2x text-primary"></i>
                                </div>
                                <h6 class="fw-bold text-muted mb-1">📂 فئة البند</h6>
                                <p class="mb-0">{{ $evaluationCriteria->category }}</p>
                            </div>
                        </div>
                        @endif

                        <div class="col-md-4">
                            <div class="stats-card h-100">
                                <div class="mb-2">
                                    <i class="fas fa-sort-numeric-up fa-2x text-warning"></i>
                                </div>
                                <h6 class="fw-bold text-muted mb-1">🔢 ترتيب العرض</h6>
                                <span class="badge badge-modern badge-warning-modern">{{ $currentOrder }}</span>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="stats-card h-100">
                                <div class="mb-2">
                                    <i class="fas fa-{{ $evaluationCriteria->is_active ? 'toggle-on' : 'toggle-off' }} fa-2x text-{{ $evaluationCriteria->is_active ? 'success' : 'secondary' }}"></i>
                                </div>
                                <h6 class="fw-bold text-muted mb-1">⚡ الحالة</h6>
                                @if($evaluationCriteria->is_active)
                                    <span class="badge badge-modern badge-success-modern">✅ نشط</span>
                                @else
                                    <span class="badge badge-modern" style="background: #6c757d;">⏸️ غير نشط</span>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="stats-card h-100">
                                <div class="mb-2">
                                    <i class="fas fa-{{ $evaluationCriteria->evaluate_per_project ? 'project-diagram' : 'calendar-alt' }} fa-2x text-{{ $evaluationCriteria->evaluate_per_project ? 'primary' : 'info' }}"></i>
                                </div>
                                <h6 class="fw-bold text-muted mb-1">🎯 نوع التقييم</h6>
                                @if($evaluationCriteria->evaluate_per_project)
                                    <span class="badge badge-modern badge-primary-modern">🚀 لكل مشروع</span>
                                @else
                                    <span class="badge badge-modern badge-warning-modern">📅 دوري</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- 📅 Timeline -->
                    <div class="p-4 rounded-4" style="background: linear-gradient(135deg, rgba(168, 237, 234, 0.3), rgba(254, 214, 227, 0.3));">
                        <h6 class="fw-bold mb-3">📅 الجدول الزمني</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">
                                    <div class="p-2 rounded-circle bg-primary me-3">
                                        <i class="fas fa-plus text-white"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold mb-0">تاريخ الإنشاء</h6>
                                        <small class="text-muted">{{ $evaluationCriteria->created_at->format('Y-m-d H:i') }}</small>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="d-flex align-items-center">
                                    <div class="p-2 rounded-circle bg-warning me-3">
                                        <i class="fas fa-edit text-white"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold mb-0">آخر تحديث</h6>
                                        <small class="text-muted">{{ $evaluationCriteria->updated_at->format('Y-m-d H:i') }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 🎨 تأثير تدرجي لظهور العناصر
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('fade-in-up');
                    }
                });
            }, observerOptions);

            // مراقبة جميع العناصر القابلة للرؤية
            const animatedElements = document.querySelectorAll('.stats-card');
            animatedElements.forEach((element, index) => {
                element.style.animationDelay = (index * 0.1) + 's';
                observer.observe(element);
            });
        });
    </script>
@endpush
@endsection
