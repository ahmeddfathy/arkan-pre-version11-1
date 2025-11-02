@extends('layouts.app')

@section('title', 'تفاصيل التقييم - ' . $user->name)

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h3 mb-1">
                <i class="fas fa-chart-line text-primary me-2"></i>
                تفاصيل التقييم
            </h2>
            <p class="text-muted mb-0">
                {{ $user->name }}
                @if(isset($roles) && $roles->count() > 1)
                - <span class="badge bg-info">{{ $roles->count() }} أدوار</span>
                @foreach($roles as $r)
                <span class="badge bg-secondary ms-1">{{ $r->display_name ?? $r->name }}</span>
                @endforeach
                @else
                - {{ $role->display_name ?? $role->name }}
                @endif
            </p>
        </div>
        <a href="{{ route('kpi-evaluation.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-right me-2"></i>
            العودة للقائمة
        </a>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="stats-card">
                <div class="stats-icon bg-primary">
                    <i class="fas fa-user"></i>
                </div>
                <div class="stats-content">
                    <h3 class="stats-number text-truncate">{{ $user->name }}</h3>
                    <p class="stats-label">الموظف</p>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="stats-card">
                <div class="stats-icon bg-secondary">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stats-content">
                    <h3 class="stats-number small">
                        @php
                        $monthDate = \Carbon\Carbon::createFromFormat('Y-m', $month);
                        echo $monthDate->locale('ar')->translatedFormat('M Y');
                        @endphp
                    </h3>
                    <p class="stats-label">شهر التقييم</p>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="stats-card">
                <div class="stats-icon bg-success">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stats-content">
                    <h3 class="stats-number">{{ $totalGeneralScore }}</h3>
                    <p class="stats-label">النقاط العامة</p>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="stats-card">
                <div class="stats-icon bg-warning">
                    <i class="fas fa-project-diagram"></i>
                </div>
                <div class="stats-content">
                    <h3 class="stats-number">{{ $totalProjectScore }}</h3>
                    <p class="stats-label">نقاط المشاريع</p>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="stats-card">
                <div class="stats-icon bg-info">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="stats-content">
                    <h3 class="stats-number">{{ $totalDevelopmentScore }}</h3>
                    <p class="stats-label">النقاط التطويرية</p>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="stats-card">
                <div class="stats-icon bg-dark">
                    <i class="fas fa-trophy"></i>
                </div>
                <div class="stats-content">
                    <h3 class="stats-number">{{ $finalTotal }}</h3>
                    <p class="stats-label">المجموع الكلي</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Final Score -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="modern-card text-center">
                <div class="modern-card-body py-4">
                    <div class="final-score-display">
                        <h1 class="display-3 mb-2
                            @if($finalTotal >= 80) text-success
                            @elseif($finalTotal >= 60) text-warning
                            @else text-danger
                            @endif">
                            {{ $finalTotal }}
                        </h1>
                        <h4 class="text-muted mb-3">النقاط الإجمالية النهائية</h4>
                        <div class="score-breakdown mb-3">
                            <span class="badge bg-primary me-2">{{ $totalGeneralScore }} عام</span>
                            <span class="text-muted mx-2">+</span>
                            <span class="badge bg-success me-2">{{ $totalProjectScore }} مشاريع</span>
                            <span class="text-muted mx-2">+</span>
                            <span class="badge bg-info">{{ $totalDevelopmentScore }} تطويرية</span>
                        </div>
                        <!-- زرار عرض التفاصيل -->
                        <button class="btn btn-outline-primary btn-lg" id="detailsBtn" data-user-id="{{ $user->id }}" data-month="{{ $month }}">
                            <i class="fas fa-chart-bar me-2"></i>
                            عرض التفاصيل الكاملة
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- عرض التقييمات مجمعة حسب الدور (إذا كان أكثر من دور) -->
    @if(isset($roles) && $roles->count() > 1 && isset($evaluationsByRole))
    <div class="row mb-4">
        <div class="col-12">
            <div class="modern-card">
                <div class="modern-card-header bg-gradient-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-layer-group me-2"></i>
                        التقييمات مجمعة حسب الدور
                    </h5>
                </div>
                <div class="modern-card-body">
                    @foreach($evaluationsByRole as $roleId => $roleEvaluations)
                    @php
                    $roleObj = $roles->firstWhere('id', $roleId);
                    $roleProjects = isset($projectsByRole[$roleId]) ? $projectsByRole[$roleId] : collect([]);
                    $roleTotalGeneral = $roleEvaluations->sum('total_after_deductions');
                    $roleTotalProjects = $roleProjects->sum('total_project_score');
                    $roleGrandTotal = $roleTotalGeneral + $roleTotalProjects;
                    @endphp

                    <div class="role-evaluation-group mb-4 p-4 border rounded" style="background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);">
                        <!-- Role Header -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h4 class="mb-1">
                                    <span class="badge bg-primary me-2" style="font-size: 1.1rem;">
                                        {{ $roleObj->display_name ?? $roleObj->name }}
                                    </span>
                                </h4>
                                <small class="text-muted">
                                    {{ $roleEvaluations->count() }} تقييم عام | {{ $roleProjects->count() }} تقييم مشروع
                                </small>
                            </div>
                            <div class="text-end">
                                <h2 class="mb-0 text-primary">{{ $roleGrandTotal }}</h2>
                                <small class="text-muted">إجمالي النقاط</small>
                            </div>
                        </div>

                        <!-- Role Summary -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="p-3 bg-white rounded text-center">
                                    <h5 class="text-success">{{ $roleTotalGeneral }}</h5>
                                    <small class="text-muted">نقاط عامة</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 bg-white rounded text-center">
                                    <h5 class="text-warning">{{ $roleTotalProjects }}</h5>
                                    <small class="text-muted">نقاط المشاريع</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 bg-white rounded text-center">
                                    <h5 class="text-info">{{ $roleEvaluations->sum('total_development') }}</h5>
                                    <small class="text-muted">نقاط تطويرية</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="row">
        <!-- General Evaluations -->
        <div class="col-lg-6 mb-4">
            <div class="modern-card">
                <div class="modern-card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-clipboard-list text-primary me-2"></i>
                        التقييمات العامة ({{ $generalEvaluations->count() }})
                    </h5>
                </div>
                <div class="modern-card-body">
                    @if($generalEvaluations->count() > 0)
                    @foreach($generalEvaluations as $evaluation)
                    <div class="evaluation-card mb-4 clickable-card" data-evaluation-id="{{ $evaluation->id }}" style="cursor: pointer; transition: all 0.3s ease;"
                        onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 8px 25px rgba(0,0,0,0.15)';"
                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 10px rgba(0,0,0,0.1)';"
                        onclick="openEvaluationSidebar({{ intval($evaluation->id) }})">
                        <!-- Card Header -->
                        <div class="evaluation-card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="evaluation-info">
                                    <h5 class="evaluation-title mb-1">
                                        <i class="fas fa-clipboard-check me-2"></i>
                                        تقييم رقم {{ $loop->iteration }}
                                        @if(isset($roles) && $roles->count() > 1)
                                        <span class="badge bg-info ms-2">{{ $evaluation->role->display_name ?? $evaluation->role->name }}</span>
                                        @endif
                                    </h5>
                                    <div class="evaluator-info">
                                        <i class="fas fa-user-tie me-1"></i>
                                        <span class="evaluator-name">{{ $evaluation->reviewer->name ?? 'غير محدد' }}</span>
                                        <span class="evaluation-date ms-3">
                                            <i class="fas fa-calendar-alt me-1"></i>
                                            {{ $evaluation->created_at->format('Y-m-d H:i') }}
                                        </span>
                                    </div>
                                </div>
                                <div class="total-score-badge">
                                    <div class="score-number">{{ $evaluation->total_after_deductions }}</div>
                                    <div class="score-label">النقاط النهائية</div>
                                </div>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="evaluation-card-body">

                            <!-- Quick Stats -->
                            <div class="quick-stats-row mb-4">
                                <div class="quick-stat-item success">
                                    <div class="stat-icon">
                                        <i class="fas fa-thumbs-up"></i>
                                    </div>
                                    <div class="stat-content">
                                        <div class="stat-value">{{ $evaluation->total_score }}</div>
                                        <div class="stat-title">النقاط الأساسية</div>
                                    </div>
                                </div>

                                @if($evaluation->total_bonus > 0)
                                <div class="quick-stat-item warning">
                                    <div class="stat-icon">
                                        <i class="fas fa-star"></i>
                                    </div>
                                    <div class="stat-content">
                                        <div class="stat-value">+{{ $evaluation->total_bonus }}</div>
                                        <div class="stat-title">بونص</div>
                                    </div>
                                </div>
                                @endif

                                @if($evaluation->total_deductions > 0)
                                <div class="quick-stat-item danger">
                                    <div class="stat-icon">
                                        <i class="fas fa-minus-circle"></i>
                                    </div>
                                    <div class="stat-content">
                                        <div class="stat-value">-{{ $evaluation->total_deductions }}</div>
                                        <div class="stat-title">خصومات</div>
                                    </div>
                                </div>
                                @endif
                            </div>

                            <!-- Detailed Criteria -->
                            @if($evaluation->criteria_scores)
                            @php
                            $criteriaData = is_string($evaluation->criteria_scores)
                            ? json_decode($evaluation->criteria_scores, true)
                            : $evaluation->criteria_scores;
                            @endphp

                            @if($criteriaData && is_array($criteriaData))
                            <div class="criteria-breakdown">
                                <h6 class="mb-3">
                                    <i class="fas fa-list-check me-2"></i>تفاصيل البنود
                                </h6>
                                <div class="row">
                                    @foreach($criteriaData as $criteria)
                                    <div class="col-md-6 mb-2">
                                        <div class="criteria-detail p-2 rounded">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="flex-grow-1">
                                                    <span class="criteria-name">
                                                        {{ $criteria['name'] ?? 'غير محدد' }}
                                                        @if(str_contains($criteria['name'] ?? '', 'محذوف'))
                                                        <span class="badge bg-warning text-dark ms-1" style="font-size: 9px;">
                                                            <i class="fas fa-exclamation-triangle me-1"></i>محذوف
                                                        </span>
                                                        @endif
                                                    </span>
                                                    @if(isset($criteria['description']) && $criteria['description'])
                                                    <br><small class="text-muted">{{ Str::limit($criteria['description'], 50) }}</small>
                                                    @endif
                                                    @if(isset($criteria['note']) && !empty($criteria['note']))
                                                    <br>
                                                    <div class="mt-1 p-1 rounded" style="background-color: #f8f9fa; border-left: 3px solid #007bff; font-size: 11px;">
                                                        <i class="fas fa-sticky-note me-1 text-primary"></i>
                                                        <span style="color: #495057;">{{ Str::limit($criteria['note'], 80) }}</span>
                                                    </div>
                                                    @endif
                                                </div>
                                                <div class="criteria-score">
                                                    <div class="d-flex flex-column align-items-end">
                                                        <span class="badge
                                                                            @if(($criteria['criteria_type'] ?? '') === 'positive') bg-success
                                                                            @elseif(($criteria['criteria_type'] ?? '') === 'negative') bg-danger
                                                                            @elseif(($criteria['criteria_type'] ?? '') === 'bonus') bg-warning
                                                                            @elseif(($criteria['criteria_type'] ?? '') === 'development') bg-info
                                                                            @else bg-primary
                                                                            @endif mb-1">
                                                            {{ $criteria['score'] ?? 0 }}/{{ $criteria['max_points'] ?? 0 }}
                                                        </span>
                                                        @if(isset($criteria['category']) && $criteria['category'])
                                                        <small class="text-muted" style="font-size: 0.65rem;">
                                                            @if($criteria['category'] == 'بنود إيجابية') ✅ @elseif($criteria['category'] == 'بنود سلبية') ❌ @elseif($criteria['category'] == 'بنود تطويرية') 🎓 @elseif($criteria['category'] == 'بونص') 🌟 @endif
                                                            {{ $criteria['category'] }}
                                                        </small>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                            @endif

                            <!-- Notes -->
                            @if($evaluation->notes)
                            <div class="evaluation-notes">
                                <div class="notes-header">
                                    <i class="fas fa-comment-alt me-2"></i>
                                    <span>ملاحظات المُقيِّم</span>
                                </div>
                                <div class="notes-content">
                                    {{ $evaluation->notes }}
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                    @else
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-3"></i>
                        <p>لا توجد تقييمات عامة</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Project Evaluations -->
        <div class="col-lg-6 mb-4">
            <div class="modern-card">
                <div class="modern-card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-project-diagram text-success me-2"></i>
                        تقييمات المشاريع ({{ $projectEvaluations->count() }})
                    </h5>
                </div>
                <div class="modern-card-body">
                    @if($projectEvaluations->count() > 0)
                    @foreach($projectEvaluations as $projectEval)
                    <div class="evaluation-item mb-3 p-3 border rounded">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <span class="fw-bold">
                                    @if($projectEval->project)
                                    {{ $projectEval->project->name }}
                                    @else
                                    <span class="text-muted">
                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                        مشروع محذوف (ID: {{ $projectEval->project_id }})
                                    </span>
                                    @endif
                                </span>
                                @if(isset($roles) && $roles->count() > 1)
                                <span class="badge bg-info ms-2">{{ $projectEval->role->display_name ?? $projectEval->role->name }}</span>
                                @endif
                            </div>
                            <span class="badge bg-success">{{ $projectEval->total_project_score }} نقطة</span>
                        </div>
                        <div class="row text-small mb-2">
                            <div class="col-12">
                                <small class="text-muted">المُقيِّم:</small>
                                <strong>
                                    @if($projectEval->evaluator)
                                    {{ $projectEval->evaluator->name }}
                                    @else
                                    <span class="text-muted">مُقيِّم محذوف</span>
                                    @endif
                                </strong>
                            </div>
                            <div class="col-12">
                                <small class="text-muted">تاريخ التقييم:</small>
                                <strong>{{ $projectEval->created_at->format('Y-m-d H:i') }}</strong>
                            </div>
                        </div>

                        <!-- Project Criteria Details -->
                        @if($projectEval->criteria_scores)
                        <div class="criteria-details mt-2">
                            <small class="text-muted d-block mb-1">تفاصيل النقاط:</small>
                            @foreach($projectEval->criteria_scores as $criteriaId => $details)
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <small class="text-truncate me-2" title="{{ $details['name'] }}">
                                    {{ Str::limit($details['name'], 25) }}
                                </small>
                                <span class="badge badge-sm bg-light text-dark">
                                    {{ $details['score'] }}/{{ $details['max_points'] }}
                                </span>
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    @endforeach
                    @else
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-project-diagram fa-2x mb-3"></i>
                        <p>لا توجد تقييمات مشاريع</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .stats-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        border: none;
        height: 100%;
        display: flex;
        align-items: center;
    }

    .stats-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
        margin-right: 1rem;
    }

    .stats-content {
        flex: 1;
    }

    .stats-number {
        font-size: 1.5rem;
        font-weight: bold;
        margin: 0;
        color: #2d3748;
    }

    .stats-label {
        color: #718096;
        margin: 0;
        font-size: 0.9rem;
    }

    .modern-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        border: none;
        overflow: hidden;
    }

    .modern-card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1.5rem;
        border: none;
    }

    .modern-card-body {
        padding: 1.5rem;
    }

    .evaluation-item {
        background: #f8f9fa;
        transition: all 0.3s ease;
    }

    .evaluation-item:hover {
        background: #e9ecef;
        transform: translateY(-1px);
    }

    .final-score-display {
        padding: 2rem;
    }

    .score-breakdown {
        font-size: 1.1rem;
    }

    .badge-sm {
        font-size: 0.7rem;
        padding: 0.25rem 0.5rem;
    }

    .criteria-details {
        background: rgba(255, 255, 255, 0.7);
        border-radius: 8px;
        padding: 0.5rem;
    }

    .stat-box {
        background: rgba(255, 255, 255, 0.8);
        border-radius: 10px;
        padding: 1rem;
        margin-bottom: 0.5rem;
    }

    .stat-number {
        font-size: 1.5rem;
        font-weight: bold;
        margin-bottom: 0.25rem;
    }

    .stat-label {
        font-size: 0.8rem;
        color: #6c757d;
        margin: 0;
    }

    .criteria-breakdown {
        background: rgba(248, 249, 250, 0.8);
        border-radius: 10px;
        padding: 1rem;
        margin-top: 1rem;
    }

    .criteria-detail {
        background: white;
        border: 1px solid #e9ecef;
        transition: all 0.3s ease;
    }

    .criteria-detail:hover {
        background: #f8f9fa;
        border-color: #dee2e6;
    }

    .criteria-name {
        font-weight: 500;
        color: #495057;
    }

    .criteria-score .d-flex {
        min-width: 80px;
    }

    .criteria-score .badge {
        font-size: 0.8rem;
        padding: 0.4rem 0.6rem;
    }

    .criteria-score small {
        line-height: 1.2;
        text-align: center;
        display: block;
        margin-top: 2px;
    }

    /* Evaluation Card Styles */
    .evaluation-card {
        background: #ffffff;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        border: 1px solid #e9ecef;
        margin-bottom: 1rem;
    }

    .evaluation-card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1rem;
    }

    .evaluation-title {
        color: white;
        font-size: 1rem;
        font-weight: 600;
        margin: 0;
    }

    .evaluator-info {
        color: rgba(255, 255, 255, 0.9);
        font-size: 0.8rem;
        margin-top: 0.25rem;
    }

    .evaluator-name {
        font-weight: 500;
        color: #ffd700;
    }

    .evaluation-date {
        color: rgba(255, 255, 255, 0.8);
    }

    .total-score-badge {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 6px;
        padding: 0.75rem;
        text-align: center;
        min-width: 80px;
    }

    .total-score-badge .score-number {
        font-size: 1.5rem;
        font-weight: bold;
        color: #ffd700;
        line-height: 1;
    }

    .total-score-badge .score-label {
        font-size: 0.7rem;
        color: rgba(255, 255, 255, 0.9);
        margin-top: 0.25rem;
    }

    .evaluation-card-body {
        padding: 1rem;
    }

    /* Quick Stats */
    .quick-stats-row {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        justify-content: center;
        margin-bottom: 1rem;
    }

    .quick-stat-item {
        display: flex;
        align-items: center;
        background: #f8f9fa;
        border-radius: 6px;
        padding: 0.5rem 0.75rem;
        flex: 1;
        min-width: 100px;
    }

    .quick-stat-item.success {
        background: #d4edda;
    }

    .quick-stat-item.warning {
        background: #fff3cd;
    }

    .quick-stat-item.danger {
        background: #f8d7da;
    }

    .stat-icon {
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-left: 0.5rem;
        font-size: 0.9rem;
    }

    .quick-stat-item.success .stat-icon {
        color: #155724;
    }

    .quick-stat-item.warning .stat-icon {
        color: #856404;
    }

    .quick-stat-item.danger .stat-icon {
        color: #721c24;
    }

    .stat-content {
        flex: 1;
    }

    .stat-value {
        font-size: 1.1rem;
        font-weight: bold;
        color: #2c3e50;
        line-height: 1;
    }

    .stat-title {
        font-size: 0.7rem;
        color: #6c757d;
        margin-top: 0.1rem;
    }

    /* Criteria Breakdown */
    .criteria-breakdown {
        background: #f8f9fa;
        border-radius: 6px;
        padding: 1rem;
        margin-top: 1rem;
    }

    .criteria-breakdown h6 {
        color: #495057;
        font-weight: 600;
        margin-bottom: 0.75rem;
        font-size: 0.9rem;
    }

    .criteria-detail {
        background: white;
        border-radius: 4px;
        margin-bottom: 0.5rem;
        padding: 0.5rem;
    }

    .criteria-name {
        font-weight: 500;
        color: #495057;
        font-size: 0.9rem;
    }

    /* Notes */
    .evaluation-notes {
        background: #e3f2fd;
        border-radius: 6px;
        overflow: hidden;
        margin-top: 1rem;
    }

    .notes-header {
        background: #bbdefb;
        padding: 0.75rem 1rem;
        font-weight: 600;
        color: #1976d2;
        font-size: 0.9rem;
    }

    .notes-content {
        padding: 1rem;
        color: #37474f;
        line-height: 1.5;
        background: white;
        font-size: 0.85rem;
    }

    @media (max-width: 768px) {
        .stats-card {
            margin-bottom: 1rem;
            text-align: center;
            flex-direction: column;
        }

        .stats-icon {
            margin-right: 0;
            margin-bottom: 1rem;
        }

        .final-score-display {
            padding: 1rem;
        }

        .display-3 {
            font-size: 2.5rem;
        }
    }
</style>

<!-- Beautiful Evaluation Sidebar -->
<div class="evaluation-sidebar" id="evaluationSidebar">
    <div class="sidebar-overlay" onclick="closeSidebar()"></div>
    <div class="sidebar-content">
        <!-- Sidebar Header -->
        <div class="sidebar-header">
            <div class="sidebar-title">
                <i class="fas fa-clipboard-list me-2"></i>
                <span>تفاصيل التقييم</span>
            </div>
            <button class="sidebar-close-btn" onclick="closeSidebar()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Loading State -->
        <div class="sidebar-loading" id="sidebarLoading">
            <div class="loading-spinner">
                <div class="spinner"></div>
                <p>جاري تحميل التفاصيل...</p>
            </div>
        </div>

        <!-- Sidebar Body -->
        <div class="sidebar-body" id="sidebarBody" style="display: none;">
            <div id="sidebarContent">
                <!-- Content will be loaded here via AJAX -->
            </div>
        </div>
    </div>
</div>

<!-- Details Sidebar -->
<div class="details-sidebar" id="detailsSidebar">
    <div class="sidebar-overlay" onclick="closeDetailsSidebar()"></div>
    <div class="sidebar-content">
        <!-- Sidebar Header -->
        <div class="sidebar-header">
            <div class="sidebar-title">
                <i class="fas fa-chart-line me-2"></i>
                <span>التفاصيل الكاملة</span>
            </div>
            <button class="sidebar-close-btn" onclick="closeDetailsSidebar()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Period Info Banner -->
        <div class="period-info-banner" id="detailsPeriodInfoBanner" style="display: none;">
            <div class="d-flex align-items-center justify-content-between p-3 bg-light border-bottom">
                <div class="flex-grow-1">
                    <small class="text-muted d-block mb-1">فترة التقييم:</small>
                    <strong class="text-primary" id="detailsSidebarPeriodText">
                        <i class="fas fa-calendar-alt me-1"></i>
                        <span id="detailsSidebarPeriodDates"></span>
                    </strong>
                </div>
                <div>
                    <span class="badge bg-info" id="detailsSidebarEvaluationType"></span>
                </div>
            </div>
        </div>

        <!-- Tabs Navigation -->
        <div class="tabs-navigation">
            <div class="nav nav-pills nav-fill" id="details-tabs" role="tablist">
                <button class="nav-link active" id="revisions-tab" data-bs-toggle="pill" data-bs-target="#revisions" type="button" role="tab">
                    <i class="fas fa-edit me-2"></i>التعديلات
                </button>
                <button class="nav-link" id="delivered-projects-tab" data-bs-toggle="pill" data-bs-target="#delivered-projects" type="button" role="tab">
                    <i class="fas fa-check-circle me-2"></i>المشاريع المسلّمة
                </button>
                <button class="nav-link" id="errors-tab" data-bs-toggle="pill" data-bs-target="#errors" type="button" role="tab">
                    <i class="fas fa-exclamation-triangle me-2"></i>الأخطاء
                </button>
                <button class="nav-link" id="delayed-projects-tab" data-bs-toggle="pill" data-bs-target="#delayed-projects" type="button" role="tab">
                    <i class="fas fa-clock me-2"></i>المشاريع المتأخرة
                </button>
                <button class="nav-link" id="delayed-tasks-tab" data-bs-toggle="pill" data-bs-target="#delayed-tasks" type="button" role="tab">
                    <i class="fas fa-tasks me-2"></i>المهام المتأخرة
                </button>
            </div>
        </div>

        <!-- Loading State -->
        <div class="sidebar-loading" id="detailsLoading">
            <div class="loading-spinner">
                <div class="spinner"></div>
                <p>جاري تحميل التفاصيل...</p>
            </div>
        </div>

        <!-- Tabs Content -->
        <div class="tab-content sidebar-body" id="detailsContent" style="display: none;">
            <!-- التعديلات -->
            <div class="tab-pane fade show active" id="revisions" role="tabpanel">
                <div class="tab-content-wrapper">
                    <div id="revisionsContent">
                        <!-- محتوى التعديلات سيتم تحميله هنا -->
                    </div>
                </div>
            </div>

            <!-- المشاريع المسلّمة -->
            <div class="tab-pane fade" id="delivered-projects" role="tabpanel">
                <div class="tab-content-wrapper">
                    <div id="deliveredProjectsContent">
                        <!-- محتوى المشاريع المسلّمة سيتم تحميله هنا -->
                    </div>
                </div>
            </div>

            <!-- الأخطاء -->
            <div class="tab-pane fade" id="errors" role="tabpanel">
                <div class="tab-content-wrapper">
                    <div id="errorsContent">
                        <!-- محتوى الأخطاء سيتم تحميله هنا -->
                    </div>
                </div>
            </div>

            <!-- المشاريع المتأخرة -->
            <div class="tab-pane fade" id="delayed-projects" role="tabpanel">
                <div class="tab-content-wrapper">
                    <div id="delayedProjectsContent">
                        <!-- محتوى المشاريع المتأخرة سيتم تحميله هنا -->
                    </div>
                </div>
            </div>

            <!-- المهام المتأخرة -->
            <div class="tab-pane fade" id="delayed-tasks" role="tabpanel">
                <div class="tab-content-wrapper">
                    <div id="delayedTasksContent">
                        <!-- محتوى المهام المتأخرة سيتم تحميله هنا -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Global variable to track sidebar state
    let sidebarOpen = false;
    let detailsSidebarOpen = false;

    // Open evaluation sidebar
    function openEvaluationSidebar(evaluationId) {
        const sidebar = document.getElementById('evaluationSidebar');
        const loading = document.getElementById('sidebarLoading');
        const body = document.getElementById('sidebarBody');

        // Show sidebar
        sidebar.classList.add('active');
        sidebarOpen = true;

        // Show loading state
        loading.style.display = 'flex';
        body.style.display = 'none';

        // Fetch evaluation details
        const url = `{{ url('/kpi-evaluation') }}/${evaluationId}/sidebar-details`;

        fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                credentials: 'same-origin'
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error('الاستجابة ليست JSON صحيح');
                }

                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);

                if (data.success) {
                    displayEvaluationDetails(data.data);
                    loading.style.display = 'none';
                    body.style.display = 'block';
                } else {
                    throw new Error(data.message || 'خطأ في تحميل البيانات');
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                loading.innerHTML = `
            <div class="error-state">
                <i class="fas fa-exclamation-triangle text-warning mb-3" style="font-size: 3rem;"></i>
                <h5>خطأ في التحميل</h5>
                <p class="text-muted">${error.message}</p>
                <div class="mt-3">
                    <button class="btn btn-primary btn-sm me-2" onclick="openEvaluationSidebar(${evaluationId})">
                        <i class="fas fa-redo me-2"></i>إعادة المحاولة
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" onclick="closeSidebar()">
                        <i class="fas fa-times me-1"></i>إغلاق
                    </button>
                </div>
            </div>
        `;
            });
    }

    // Close sidebar
    function closeSidebar() {
        const sidebar = document.getElementById('evaluationSidebar');
        sidebar.classList.remove('active');
        sidebarOpen = false;
    }

    // Helper function: حساب فترة التقييم
    function calculateEvaluationPeriod(reviewMonth, evaluationType) {
        // reviewMonth بصيغة Y-m مثل "2025-01"
        const [year, month] = reviewMonth.split('-').map(Number);

        let startDate, endDate;

        if (evaluationType === 'bi_weekly') {
            // نصف شهري: من يوم 15 الشهر السابق إلى يوم 14 الشهر الحالي
            const prevMonth = month === 1 ? 12 : month - 1;
            const prevYear = month === 1 ? year - 1 : year;
            startDate = new Date(prevYear, prevMonth - 1, 15);
            endDate = new Date(year, month - 1, 14);
        } else {
            // شهري: من يوم 26 الشهر السابق إلى يوم 25 الشهر الحالي
            const prevMonth = month === 1 ? 12 : month - 1;
            const prevYear = month === 1 ? year - 1 : year;
            startDate = new Date(prevYear, prevMonth - 1, 26);
            endDate = new Date(year, month - 1, 25);
        }

        // تنسيق التواريخ
        const options = {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        };
        const arabicLocale = 'ar-EG';

        const startDateStr = startDate.toLocaleDateString(arabicLocale, options);
        const endDateStr = endDate.toLocaleDateString(arabicLocale, options);

        return {
            start: startDateStr,
            end: endDateStr,
            full: `من ${startDateStr} إلى ${endDateStr}`
        };
    }

    // Open details sidebar
    function openDetailsSidebar(userId, month) {
        const sidebar = document.getElementById('detailsSidebar');
        const loading = document.getElementById('detailsLoading');
        const content = document.getElementById('detailsContent');

        // Show sidebar
        sidebar.classList.add('active');
        detailsSidebarOpen = true;

        // Show loading state
        loading.style.display = 'flex';
        content.style.display = 'none';


        // Fetch details
        const url = `{{ url('/kpi-evaluation/user-details') }}/${userId}/${month}`;

        fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    displayUserDetails(data.data, month);
                    loading.style.display = 'none';
                    content.style.display = 'block';
                    // Reset tabs to first tab after content is loaded
                    setTimeout(() => {
                        const firstTab = document.getElementById('revisions-tab');
                        if (firstTab) firstTab.click();
                    }, 100);
                } else {
                    throw new Error(data.message || 'خطأ في تحميل البيانات');
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                loading.innerHTML = `
            <div class="error-state">
                <i class="fas fa-exclamation-triangle text-warning mb-3" style="font-size: 3rem;"></i>
                <h5>خطأ في التحميل</h5>
                <p class="text-muted">${error.message}</p>
                <div class="mt-3">
                    <button class="btn btn-primary btn-sm me-2" onclick="openDetailsSidebar(${userId}, '${month}')">
                        <i class="fas fa-redo me-2"></i>إعادة المحاولة
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" onclick="closeDetailsSidebar()">
                        <i class="fas fa-times me-1"></i>إغلاق
                    </button>
                </div>
            </div>
        `;
            });
    }

    // Close details sidebar
    function closeDetailsSidebar() {
        const sidebar = document.getElementById('detailsSidebar');
        sidebar.classList.remove('active');
        detailsSidebarOpen = false;
    }

    // Display evaluation details
    function displayEvaluationDetails(evaluation) {
        const content = document.getElementById('sidebarContent');

        let criteriaHtml = '';
        if (evaluation.criteria && evaluation.criteria.length > 0) {
            evaluation.criteria.forEach(criterion => {
                const progressWidth = criterion.max_points > 0 ? (criterion.score / criterion.max_points) * 100 : 0;
                const typeClass = criterion.criteria_type === 'positive' ? 'success' :
                    criterion.criteria_type === 'negative' ? 'danger' :
                    criterion.criteria_type === 'bonus' ? 'warning' :
                    criterion.criteria_type === 'development' ? 'info' : 'primary';

                criteriaHtml += `
                <div class="criterion-item mb-4">
                    <div class="criterion-header d-flex justify-content-between align-items-start mb-2">
                        <div class="criterion-info flex-grow-1">
                            <h6 class="criterion-name mb-1">${criterion.criteria_name}
                                ${criterion.criteria_name.includes('محذوف') ? '<span class="badge bg-warning text-dark ms-1"><i class="fas fa-exclamation-triangle me-1"></i>محذوف</span>' : ''}
                            </h6>
                            ${criterion.criteria_description ? `<small class="text-muted">${criterion.criteria_description}</small>` : ''}
                        </div>
                        <div class="criterion-score">
                            <div class="d-flex flex-column align-items-end">
                                <span class="badge bg-${typeClass} mb-1">${criterion.score}/${criterion.max_points}</span>
                                ${criterion.category ? `
                                    <small class="text-muted" style="font-size: 0.65rem;">
                                        ${criterion.category === 'بنود إيجابية' ? '✅' :
                                          criterion.category === 'بنود سلبية' ? '❌' :
                                          criterion.category === 'بنود تطويرية' ? '🎓' :
                                          criterion.category === 'بونص' ? '🌟' : ''}
                                        ${criterion.category}
                                    </small>
                                ` : ''}
                            </div>
                        </div>
                    </div>

                    <div class="progress mb-2" style="height: 8px;">
                        <div class="progress-bar bg-${typeClass}" style="width: ${progressWidth}%"></div>
                    </div>

                    ${criterion.note ? `
                        <div class="criterion-note mt-2">
                            <div class="note-box p-2">
                                <small class="text-muted d-block mb-1">
                                    <i class="fas fa-sticky-note me-1"></i>ملاحظة:
                                </small>
                                <span style="font-size: 13px;">${criterion.note}</span>
                            </div>
                        </div>
                    ` : ''}
                </div>
            `;
            });
        }

        content.innerHTML = `
        <!-- Evaluation Overview -->
        <div class="evaluation-overview mb-4">
            <div class="overview-card">
                <div class="overview-header text-center mb-4">
                    <div class="score-circle ${evaluation.total_after_deductions >= 80 ? 'excellent' :
                                               evaluation.total_after_deductions >= 60 ? 'good' : 'needs-improvement'}">
                        <span class="score-number">${evaluation.total_after_deductions}</span>
                        <span class="score-label">النقاط النهائية</span>
                    </div>
                </div>

                <div class="overview-stats">
                    <div class="stat-row">
                        <div class="stat-item">
                            <div class="stat-icon bg-success">
                                <i class="fas fa-plus"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-value">${evaluation.total_score}</div>
                                <div class="stat-label">النقاط الأساسية</div>
                            </div>
                        </div>

                        ${evaluation.total_bonus > 0 ? `
                            <div class="stat-item">
                                <div class="stat-icon bg-warning">
                                    <i class="fas fa-star"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-value">+${evaluation.total_bonus}</div>
                                    <div class="stat-label">بونص</div>
                                </div>
                            </div>
                        ` : ''}

                        ${evaluation.total_development > 0 ? `
                            <div class="stat-item">
                                <div class="stat-icon bg-info">
                                    <i class="fas fa-graduation-cap"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-value">+${evaluation.total_development}</div>
                                    <div class="stat-label">تطويرية</div>
                                </div>
                            </div>
                        ` : ''}

                        ${evaluation.total_deductions > 0 ? `
                            <div class="stat-item">
                                <div class="stat-icon bg-danger">
                                    <i class="fas fa-minus"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-value">-${evaluation.total_deductions}</div>
                                    <div class="stat-label">خصومات</div>
                                </div>
                            </div>
                        ` : ''}
                    </div>
                </div>
            </div>
        </div>

        <!-- Evaluation Info -->
        <div class="evaluation-info-section mb-4">
            <h5 class="section-title">
                <i class="fas fa-info-circle me-2"></i>معلومات التقييم
            </h5>
            <div class="info-grid">
                <div class="info-item">
                    <label>المُقيِّم:</label>
                    <span>${evaluation.reviewer_name}</span>
                </div>
                <div class="info-item">
                    <label>التاريخ:</label>
                    <span>${evaluation.created_at}</span>
                </div>
                <div class="info-item">
                    <label>الدور:</label>
                    <span>${evaluation.role_name}</span>
                </div>
            </div>
        </div>

        <!-- Criteria Details -->
        ${criteriaHtml ? `
            <div class="criteria-section">
                <h5 class="section-title mb-3">
                    <i class="fas fa-list-check me-2"></i>تفاصيل بنود التقييم
                </h5>
                <div class="criteria-list">
                    ${criteriaHtml}
                </div>
            </div>
        ` : ''}

        <!-- Notes Section -->
        ${evaluation.notes ? `
            <div class="notes-section">
                <h5 class="section-title">
                    <i class="fas fa-comment-alt me-2"></i>ملاحظات إضافية
                </h5>
                <div class="notes-content">
                    <p>${evaluation.notes}</p>
                </div>
            </div>
        ` : ''}

        <!-- Action Buttons -->
        <div class="sidebar-actions mt-4 pt-3 border-top">
            <a href="/kpi-evaluation/${evaluation.id}" class="btn btn-primary btn-sm me-2">
                <i class="fas fa-eye me-1"></i>عرض التفاصيل الكاملة
            </a>
            <button class="btn btn-outline-secondary btn-sm" onclick="closeSidebar()">
                <i class="fas fa-times me-1"></i>إغلاق
            </button>
        </div>
    `;
    }

    // Display user details in tabs
    function displayUserDetails(data, month) {
        // عرض معلومات فترة التقييم
        const periodBanner = document.getElementById('detailsPeriodInfoBanner');
        const periodDates = document.getElementById('detailsSidebarPeriodDates');
        const evaluationTypeSpan = document.getElementById('detailsSidebarEvaluationType');

        // افتراض نوع التقييم (شهري) إذا لم يكن محدد في البيانات
        const evaluationType = data.evaluation_type || 'monthly';
        const period = calculateEvaluationPeriod(month, evaluationType);

        if (periodBanner && periodDates && evaluationTypeSpan) {
            periodDates.textContent = period.full;
            evaluationTypeSpan.textContent = evaluationType === 'bi_weekly' ? '⚡ نصف شهري' : '📅 شهري';
            periodBanner.style.display = 'block';
        }

        // Display Revisions
        const revisionsContent = document.getElementById('revisionsContent');
        if (data.revisions && data.revisions.length > 0) {
            let revisionsHtml = '';
            data.revisions.forEach(revision => {
                revisionsHtml += `
                <div class="revision-item mb-3">
                    <div class="revision-header d-flex justify-content-between align-items-start">
                        <div class="revision-info">
                            <h6 class="revision-title mb-1">${revision.title}</h6>
                            <div class="revision-meta">
                                <span class="badge bg-${revision.status_color} me-2">${revision.status_text}</span>
                                <small class="text-muted">
                                    <i class="fas fa-user me-1"></i>${revision.creator_name} |
                                    <i class="fas fa-calendar-alt me-1"></i>${revision.revision_date}
                                </small>
                            </div>
                        </div>
                        <div class="revision-type">
                            <span class="badge bg-${revision.revision_source_color}">
                                <i class="${revision.revision_source_icon} me-1"></i>
                                ${revision.revision_source_text}
                            </span>
                        </div>
                    </div>
                    <div class="revision-body mt-2">
                        <p class="revision-description mb-2">${revision.description || 'لا يوجد وصف'}</p>
                        ${revision.attachment_name ? `
                            <div class="revision-attachment">
                                <i class="${revision.attachment_icon} me-1"></i>
                                <a href="${revision.attachment_url}" target="_blank">${revision.attachment_name}</a>
                                <span class="text-muted ms-2">(${revision.formatted_attachment_size})</span>
                            </div>
                        ` : ''}
                        ${revision.notes ? `<div class="revision-notes mt-2 p-2 bg-light rounded"><small>${revision.notes}</small></div>` : ''}
                    </div>
                </div>
            `;
            });
            revisionsContent.innerHTML = revisionsHtml;
        } else {
            revisionsContent.innerHTML = `
            <div class="empty-state text-center py-4">
                <i class="fas fa-edit fa-3x text-muted mb-3"></i>
                <h6 class="text-muted">لا توجد تعديلات</h6>
            </div>
        `;
        }

        // Display Delayed Projects
        const delayedProjectsContent = document.getElementById('delayedProjectsContent');
        if (data.delayed_projects && data.delayed_projects.length > 0) {
            let projectsHtml = '';
            data.delayed_projects.forEach(project => {
                projectsHtml += `
                <div class="delayed-project-item mb-3">
                    <div class="project-header d-flex justify-content-between align-items-center">
                        <div class="project-info">
                            <h6 class="project-name mb-1">${project.project_name}</h6>
                            <div class="project-details">
                                <small class="text-muted">
                                    <i class="fas fa-user me-1"></i>${project.client_name} |
                                    <i class="fas fa-calendar-check me-1"></i>الموعد: ${project.deadline} |
                                    <i class="fas fa-calendar-times text-danger me-1"></i>تم التسليم: ${project.delivery_date}
                                </small>
                            </div>
                        </div>
                        <div class="delay-info">
                            <span class="badge bg-danger">${project.delay_days} يوم تأخير</span>
                        </div>
                    </div>
                    <div class="project-status mt-2">
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-danger" style="width: 100%"></div>
                        </div>
                        <small class="text-danger mt-1 d-block">متأخر ${project.delay_days} أيام</small>
                    </div>
                </div>
            `;
            });
            delayedProjectsContent.innerHTML = projectsHtml;
        } else {
            delayedProjectsContent.innerHTML = `
            <div class="empty-state text-center py-4">
                <i class="fas fa-clock fa-3x text-success mb-3"></i>
                <h6 class="text-success">لا توجد مشاريع متأخرة</h6>
                <p class="text-muted">جميع المشاريع تم تسليمها في الوقت المحدد</p>
            </div>
        `;
        }

        // Display Employee Errors
        const errorsContent = document.getElementById('errorsContent');
        if (data.employee_errors && data.employee_errors.length > 0) {
            let errorsHtml = '';
            data.employee_errors.forEach(error => {
                const errorTypeColor = error.error_type === 'critical' ? 'danger' : 'warning';
                const errorIcon = error.error_type === 'critical' ? 'fa-skull-crossbones' : 'fa-exclamation-circle';

                errorsHtml += `
                <div class="error-item mb-3 border border-${errorTypeColor} rounded p-3" style="background: ${error.error_type === 'critical' ? 'rgba(220, 53, 69, 0.05)' : 'rgba(255, 193, 7, 0.05)'};">
                    <div class="error-header d-flex justify-content-between align-items-start mb-2">
                        <div class="error-info flex-grow-1">
                            <h6 class="error-title mb-1 text-${errorTypeColor}">
                                <i class="fas ${errorIcon} me-1"></i>
                                ${error.title}
                            </h6>
                            <p class="error-description mb-2 small text-muted">${error.description}</p>
                        </div>
                        <span class="badge bg-${errorTypeColor}">${error.error_type_text}</span>
                    </div>
                    <div class="error-meta border-top pt-2">
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">
                                    <i class="fas fa-tag me-1"></i>${error.error_category_text}
                                </small>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">
                                    <i class="fas fa-user me-1"></i>${error.reported_by}
                                </small>
                            </div>
                            <div class="col-12 mt-1">
                                <small class="text-muted">
                                    <i class="fas fa-calendar me-1"></i>${error.created_at}
                                    <span class="ms-2">(${error.created_at_human})</span>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            });
            errorsContent.innerHTML = errorsHtml;
        } else {
            errorsContent.innerHTML = `
            <div class="empty-state text-center py-4">
                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                <h6 class="text-success">لا توجد أخطاء مسجلة</h6>
                <p class="text-muted">أداء ممتاز بدون أخطاء في هذا الشهر</p>
            </div>
        `;
        }

        // Display Delayed Tasks
        const delayedTasksContent = document.getElementById('delayedTasksContent');
        if (data.delayed_tasks && data.delayed_tasks.length > 0) {
            let tasksHtml = '';
            data.delayed_tasks.forEach(task => {
                tasksHtml += `
                <div class="delayed-task-item mb-3">
                    <div class="task-header d-flex justify-content-between align-items-center">
                        <div class="task-info">
                            <h6 class="task-name mb-1">${task.task_name}</h6>
                            <div class="task-details">
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>الموعد النهائي: ${task.due_date} |
                                    <i class="fas fa-calendar-check me-1"></i>تم الإنجاز: ${task.completed_date}
                                </small>
                            </div>
                        </div>
                        <div class="delay-info">
                            <span class="badge bg-warning text-dark">${task.delay_hours} ساعة تأخير</span>
                        </div>
                    </div>
                    <div class="task-type mt-2">
                        <span class="badge bg-${task.task_type === 'regular' ? 'primary' : 'secondary'} me-2">
                            ${task.task_type === 'regular' ? 'مهمة عادية' : 'مهمة قالب'}
                        </span>
                        ${task.project_name ? `<span class="badge bg-info">${task.project_name}</span>` : ''}
                    </div>
                </div>
            `;
            });
            delayedTasksContent.innerHTML = tasksHtml;
        } else {
            delayedTasksContent.innerHTML = `
            <div class="empty-state text-center py-4">
                <i class="fas fa-tasks fa-3x text-success mb-3"></i>
                <h6 class="text-success">لا توجد مهام متأخرة</h6>
                <p class="text-muted">جميع المهام تم إنجازها في الوقت المحدد</p>
            </div>
        `;
        }

        // Display Delivered Projects
        const deliveredProjectsContent = document.getElementById('deliveredProjectsContent');
        if (data.delivered_projects && data.delivered_projects.length > 0) {
            let projectsHtml = `<div class="mb-3">
            <span class="badge bg-success fs-6">
                <i class="fas fa-check-circle me-1"></i>عدد المشاريع المسلّمة: ${data.delivered_projects.length}
            </span>
        </div>`;

            data.delivered_projects.forEach(project => {
                projectsHtml += `
                <div class="delivered-project-item mb-3 border rounded p-3" style="background: rgba(40, 167, 69, 0.05);">
                    <div class="project-header d-flex justify-content-between align-items-start mb-2">
                        <div class="project-info flex-grow-1">
                            <h6 class="project-name mb-1">
                                <i class="fas fa-project-diagram text-success me-2"></i>${project.project_name}
                                ${project.project_code ? `<span class="badge bg-secondary ms-2">${project.project_code}</span>` : ''}
                            </h6>
                            <div class="project-details">
                                <small class="text-muted">
                                    <i class="fas fa-user me-1"></i>${project.client_name} |
                                    <i class="fas fa-cog me-1"></i>${project.service_name} |
                                    <i class="fas fa-calendar-check me-1"></i>${project.delivered_at_formatted || project.delivered_at}
                                </small>
                            </div>
                        </div>
                        <div class="approval-status">
                            ${project.has_all_approvals ?
                                '<span class="badge bg-success"><i class="fas fa-check-double me-1"></i>مكتمل الاعتماد</span>' :
                                '<span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>في انتظار الاعتماد</span>'
                            }
                        </div>
                    </div>

                    <!-- Approval Notes Section -->
                    <div class="approval-notes mt-3 pt-2 border-top">
                        ${project.needs_administrative ? `
                            <div class="approval-note-item mb-2">
                                <div class="d-flex align-items-start">
                                    <i class="fas fa-user-tie text-primary me-2 mt-1"></i>
                                    <div class="flex-grow-1">
                                        <strong class="d-block mb-1">ملاحظة الاعتماد الإداري:</strong>
                                        <div class="note-content p-2 bg-light rounded">
                                            ${project.administrative_note || 'لا يوجد'}
                                        </div>
                                        ${project.has_administrative_approval && project.administrative_approver_name ? `
                                            <small class="text-muted d-block mt-1">
                                                اعتمدها: ${project.administrative_approver_name}
                                                ${project.administrative_approval_at ? ` - ${project.administrative_approval_at}` : ''}
                                            </small>
                                        ` : ''}
                                    </div>
                                </div>
                            </div>
                        ` : ''}

                        ${project.needs_technical ? `
                            <div class="approval-note-item mb-2">
                                <div class="d-flex align-items-start">
                                    <i class="fas fa-tools text-info me-2 mt-1"></i>
                                    <div class="flex-grow-1">
                                        <strong class="d-block mb-1">ملاحظة الاعتماد الفني:</strong>
                                        <div class="note-content p-2 bg-light rounded">
                                            ${project.technical_note || 'لا يوجد'}
                                        </div>
                                        ${project.has_technical_approval && project.technical_approver_name ? `
                                            <small class="text-muted d-block mt-1">
                                                اعتمدها: ${project.technical_approver_name}
                                                ${project.technical_approval_at ? ` - ${project.technical_approval_at}` : ''}
                                            </small>
                                        ` : ''}
                                    </div>
                                </div>
                            </div>
                        ` : ''}

                        ${!project.needs_administrative && !project.needs_technical ? `
                            <div class="text-muted text-center py-2">
                                <small><i class="fas fa-info-circle me-1"></i>لا يتطلب هذا المشروع اعتمادات إدارية أو فنية</small>
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;
            });
            deliveredProjectsContent.innerHTML = projectsHtml;
        } else {
            deliveredProjectsContent.innerHTML = `
            <div class="empty-state text-center py-4">
                <i class="fas fa-project-diagram fa-3x text-muted mb-3"></i>
                <h6 class="text-muted">لا توجد مشاريع مسلّمة</h6>
                <p class="text-muted">لم يتم تسليم أي مشاريع في هذه الفترة</p>
            </div>
        `;
        }
    }

    // Add event listener for details button
    document.addEventListener('DOMContentLoaded', function() {
        const detailsBtn = document.getElementById('detailsBtn');
        if (detailsBtn) {
            detailsBtn.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                const month = this.getAttribute('data-month');
                openDetailsSidebar(userId, month);
            });
        }
    });

    // Close sidebar on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebarOpen) {
            closeSidebar();
        }
        if (e.key === 'Escape' && detailsSidebarOpen) {
            closeDetailsSidebar();
        }
    });
</script>
@endpush

@push('styles')
<style>
    /* Sidebar Styles */
    .evaluation-sidebar {
        position: fixed;
        top: 0;
        right: -100%;
        width: 100%;
        height: 100vh;
        z-index: 10000;
        transition: right 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
    }

    .evaluation-sidebar.active {
        right: 0;
    }

    .sidebar-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(5px);
    }

    .sidebar-content {
        position: absolute;
        top: 0;
        right: 0;
        width: 450px;
        height: 100vh;
        background: white;
        box-shadow: -10px 0 30px rgba(0, 0, 0, 0.2);
        display: flex;
        flex-direction: column;
    }

    .sidebar-header {
        padding: 20px 25px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .sidebar-title {
        font-size: 1.2rem;
        font-weight: 600;
        display: flex;
        align-items: center;
    }

    .sidebar-close-btn {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        color: white;
        width: 35px;
        height: 35px;
        border-radius: 50%;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .sidebar-close-btn:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: scale(1.1);
    }

    .sidebar-loading {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        padding: 2rem;
    }

    .loading-spinner .spinner {
        width: 50px;
        height: 50px;
        border: 4px solid #f3f3f3;
        border-top: 4px solid #667eea;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin-bottom: 1rem;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    .sidebar-body {
        flex: 1;
        overflow-y: auto;
        padding: 0;
    }

    #sidebarContent {
        padding: 25px;
    }

    /* Overview Card */
    .evaluation-overview {
        background: linear-gradient(135deg, #f8f9ff 0%, #e8f2ff 100%);
        border-radius: 15px;
        padding: 20px;
        border: 1px solid rgba(102, 126, 234, 0.1);
    }

    .score-circle {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
        position: relative;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    .score-circle.excellent {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
    }

    .score-circle.good {
        background: linear-gradient(135deg, #ffc107, #fd7e14);
        color: white;
    }

    .score-circle.needs-improvement {
        background: linear-gradient(135deg, #dc3545, #e83e8c);
        color: white;
    }

    .score-number {
        font-size: 2.5rem;
        font-weight: bold;
        line-height: 1;
    }

    .score-label {
        font-size: 0.8rem;
        opacity: 0.9;
    }

    .stat-row {
        display: flex;
        gap: 15px;
        margin-top: 20px;
    }

    .stat-item {
        flex: 1;
        display: flex;
        align-items: center;
        padding: 15px;
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    .stat-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        margin-right: 10px;
    }

    .stat-content {
        flex: 1;
    }

    .stat-value {
        font-size: 1.2rem;
        font-weight: bold;
        color: #333;
    }

    .stat-label {
        font-size: 0.8rem;
        color: #666;
    }

    /* Info Section */
    .evaluation-info-section {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
    }

    .section-title {
        color: #495057;
        font-size: 1.1rem;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid #e9ecef;
    }

    .info-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 10px;
    }

    .info-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
    }

    .info-item label {
        font-weight: 600;
        color: #6c757d;
        margin: 0;
    }

    .info-item span {
        color: #495057;
        font-weight: 500;
    }

    /* Criteria Styles */
    .criterion-item {
        background: #ffffff;
        border: 1px solid #e9ecef;
        border-radius: 10px;
        padding: 15px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    }

    .criterion-name {
        color: #495057;
        font-weight: 600;
        margin: 0;
    }

    .note-box {
        background: #f8f9ff;
        border: 1px solid #e3e8ff;
        border-radius: 6px;
        border-left: 4px solid #667eea;
    }

    /* Notes Section */
    .notes-section {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 10px;
        padding: 20px;
    }

    .notes-content p {
        margin: 0;
        color: #856404;
        line-height: 1.6;
    }

    /* Action Buttons */
    .sidebar-actions {
        padding: 0 25px 25px 25px;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .sidebar-content {
            width: 100vw;
        }

        .stat-row {
            flex-direction: column;
            gap: 10px;
        }
    }

    /* Error State */
    .error-state {
        text-align: center;
        padding: 2rem;
        color: #6c757d;
    }

    /* Clickable Card Enhancement */
    .clickable-card {
        position: relative;
        overflow: hidden;
    }

    /* Right border on hover - REMOVED per user request */
    /*
.clickable-card::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 4px;
    height: 100%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.clickable-card:hover::before {
    opacity: 1;
}
*/

    .clickable-card:hover .total-score-badge {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        transform: scale(1.05);
    }

    .total-score-badge {
        transition: all 0.3s ease;
    }

    /* إخفاء Scrollbars مع الاحتفاظ بوظيفة التمرير */

    /* للسايدبار */
    .sidebar-body {
        /* Firefox */
        scrollbar-width: none;
        /* IE and Edge */
        -ms-overflow-style: none;
    }

    /* WebKit browsers (Chrome, Safari, Edge) */
    .sidebar-body::-webkit-scrollbar {
        display: none;
    }

    #sidebarContent {
        /* Firefox */
        scrollbar-width: none;
        /* IE and Edge */
        -ms-overflow-style: none;
    }

    #sidebarContent::-webkit-scrollbar {
        display: none;
    }

    body {
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    body::-webkit-scrollbar {
        display: none;
    }

    html {
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    html::-webkit-scrollbar {
        display: none;
    }


    .modern-card-body {

        scrollbar-width: none;
        /* IE and Edge */
        -ms-overflow-style: none;
    }

    .modern-card-body::-webkit-scrollbar {
        display: none;
    }

    /* للـ containers مع overflow */
    .container-fluid,
    .evaluation-card,
    .criteria-breakdown {
        /* Firefox */
        scrollbar-width: none;
        /* IE and Edge */
        -ms-overflow-style: none;
    }

    .container-fluid::-webkit-scrollbar,
    .evaluation-card::-webkit-scrollbar,
    .criteria-breakdown::-webkit-scrollbar {
        display: none;
    }

    /* لضمان التمرير السلس */
    * {
        scroll-behavior: smooth;
    }

    /* Custom scrollbar للعناصر التي تحتاج scrollbar مرئي (اختياري) */
    .show-scrollbar {
        scrollbar-width: thin;
        scrollbar-color: rgba(102, 126, 234, 0.3) transparent;
    }

    .show-scrollbar::-webkit-scrollbar {
        width: 8px;
    }

    .show-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }

    .show-scrollbar::-webkit-scrollbar-thumb {
        background-color: rgba(102, 126, 234, 0.3);
        border-radius: 10px;
        border: 2px solid transparent;
    }

    .show-scrollbar::-webkit-scrollbar-thumb:hover {
        background-color: rgba(102, 126, 234, 0.5);
    }

    /* Details Sidebar Styles */
    .details-sidebar {
        position: fixed;
        top: 0;
        right: -100%;
        width: 100%;
        height: 100vh;
        z-index: 10001;
        transition: right 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
    }

    .details-sidebar.active {
        right: 0;
    }

    .details-sidebar .sidebar-content {
        width: 600px;
        background: white;
        box-shadow: -10px 0 30px rgba(0, 0, 0, 0.2);
        border-left: 3px solid #667eea;
    }

    /* Period Info Banner */
    .period-info-banner {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
        border-bottom: 2px solid rgba(102, 126, 234, 0.1);
    }

    .period-info-banner .badge {
        font-size: 0.85rem;
        padding: 0.4rem 0.8rem;
        border-radius: 20px;
    }

    /* Tabs Navigation */
    .tabs-navigation {
        padding: 0 25px 20px 25px;
        background: white;
        border-bottom: 1px solid #e9ecef;
    }

    .tabs-navigation .nav-pills .nav-link {
        background: #f8f9fa;
        color: #6c757d;
        border: 1px solid #dee2e6;
        margin: 0 2px;
        font-size: 0.9rem;
        padding: 10px 15px;
        border-radius: 8px;
        transition: all 0.3s ease;
    }

    .tabs-navigation .nav-pills .nav-link:hover {
        background: #e9ecef;
        color: #495057;
        transform: translateY(-2px);
    }

    .tabs-navigation .nav-pills .nav-link.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-color: #667eea;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }

    /* Tab Content */
    .tab-content-wrapper {
        padding: 25px;
        height: calc(100vh - 180px);
        overflow-y: auto;
        scrollbar-width: thin;
        scrollbar-color: rgba(102, 126, 234, 0.3) transparent;
    }

    .tab-content-wrapper::-webkit-scrollbar {
        width: 6px;
    }

    .tab-content-wrapper::-webkit-scrollbar-track {
        background: transparent;
    }

    .tab-content-wrapper::-webkit-scrollbar-thumb {
        background-color: rgba(102, 126, 234, 0.3);
        border-radius: 10px;
    }

    .tab-content-wrapper::-webkit-scrollbar-thumb:hover {
        background-color: rgba(102, 126, 234, 0.5);
    }

    /* Revision Items */
    .revision-item {
        background: white;
        border: 1px solid #e9ecef;
        border-radius: 10px;
        padding: 15px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
    }

    .revision-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.12);
    }

    .revision-title {
        color: #495057;
        font-weight: 600;
        margin: 0;
    }

    .revision-meta {
        margin-top: 5px;
    }

    .revision-description {
        color: #6c757d;
        line-height: 1.5;
        margin-bottom: 0;
    }

    .revision-attachment {
        background: #f8f9fa;
        padding: 8px 12px;
        border-radius: 6px;
        border-left: 3px solid #17a2b8;
    }

    .revision-notes {
        font-style: italic;
        color: #495057;
    }

    /* Delayed Project Items */
    .delayed-project-item {
        background: white;
        border: 1px solid #ffc107;
        border-radius: 10px;
        padding: 15px;
        box-shadow: 0 2px 8px rgba(255, 193, 7, 0.1);
        position: relative;
    }

    .delayed-project-item::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 4px;
        height: 100%;
        background: linear-gradient(135deg, #ffc107, #fd7e14);
        border-radius: 0 10px 10px 0;
    }

    .project-name {
        color: #495057;
        font-weight: 600;
        margin: 0;
    }

    /* Delayed Task Items */
    .delayed-task-item {
        background: white;
        border: 1px solid #dc3545;
        border-radius: 10px;
        padding: 15px;
        box-shadow: 0 2px 8px rgba(220, 53, 69, 0.1);
        position: relative;
    }

    .delayed-task-item::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 4px;
        height: 100%;
        background: linear-gradient(135deg, #dc3545, #e83e8c);
        border-radius: 0 10px 10px 0;
    }

    .task-name {
        color: #495057;
        font-weight: 600;
        margin: 0;
    }

    /* Empty State */
    .empty-state {
        color: #6c757d;
        padding: 2rem;
    }

    .empty-state i {
        opacity: 0.7;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .details-sidebar .sidebar-content {
            width: 100vw;
        }

        .tabs-navigation .nav-pills .nav-link {
            font-size: 0.8rem;
            padding: 8px 12px;
        }

        .tab-content-wrapper {
            padding: 15px;
        }
    }
</style>
@endpush