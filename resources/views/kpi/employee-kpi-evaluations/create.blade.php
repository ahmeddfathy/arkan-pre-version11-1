@extends('layouts.app')

@section('title', 'تقييم KPI للموظفين')

@push('styles')
<link href="{{ asset('css/kpi/kpi-evaluation-create.css') }}" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/projects-services.css') }}">
@endpush

@section('content')
<div class="simple-container">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h1>🏆 تقييم KPI لدور: {{ $selectedRole->display_name ?? $selectedRole->name }}</h1>
                    <p>تقييم أداء الموظف بناءً على مؤشرات KPI المحددة لهذا الدور</p>
                    @if(isset($usersWithRole))
                    <div style="margin-top: 0.5rem;">
                        <span style="background: rgba(255,255,255,0.2); color: white; padding: 0.4rem 0.8rem; border-radius: 12px; font-size: 0.85rem; border: 1px solid rgba(255,255,255,0.3);">
                            <i class="fas fa-users me-1"></i>
                            {{ $usersWithRole->count() }} موظف متاح للتقييم
                        </span>
                    </div>
                    @endif
                </div>
                <div class="mt-2 mt-md-0">
                    <a href="{{ route('kpi-evaluation.create') }}" class="btn btn-light btn-sm" style="color: white; background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3);">
                        <i class="fas fa-arrow-left me-2"></i>
                        تغيير الدور
                    </a>
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if(isset($usersWithRole) && $usersWithRole->count() == 0)
        <!-- No Users Available Message -->
        <div class="projects-table-container">
            <div class="table-header">
                <h2>لا يوجد موظفين متاحين</h2>
            </div>
            <div style="padding: 2rem; text-align: center;">
                <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: #f59e0b; margin-bottom: 1rem;"></i>
                <h5>لا يوجد موظفين متاحين للتقييم</h5>
                <p class="text-muted mb-3">لم يتم العثور على أي موظفين لديهم دور "{{ $selectedRole->display_name ?? $selectedRole->name }}"</p>
                <p class="text-muted mb-4">يرجى التأكد من تعيين الأدوار للموظفين أولاً.</p>
                <div class="d-flex justify-content-center gap-2 flex-wrap">
                    <a href="{{ route('kpi-evaluation.create') }}" class="services-btn" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
                        <i class="fas fa-arrow-left"></i>
                        اختر دور آخر
                    </a>
                    <a href="{{ route('users.index') }}" class="services-btn" style="background: linear-gradient(135deg, #06b6d4, #0891b2);">
                        <i class="fas fa-users"></i>
                        إدارة الموظفين
                    </a>
                </div>
            </div>
        </div>
        @elseif($evaluationCriteria && $selectedRoleId)
        <!-- Evaluation Form -->
        <form method="POST" action="{{ route('kpi-evaluation.store') }}" id="evaluationForm">
            @csrf
            <input type="hidden" name="role_id" value="{{ $selectedRoleId }}">
            <input type="hidden" name="evaluation_type" value="{{ $evaluationType ?? 'monthly' }}">

            <!-- Evaluation Type Selection -->
            <div class="projects-table-container mb-4">
                <div class="table-header">
                    <h2>⚡ نوع التقييم</h2>
                </div>
                <div style="padding: 1.5rem;">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">نوع التقييم <span class="text-danger">*</span></label>
                            <select name="evaluation_type_selector" id="evaluationTypeSelector" class="filter-select" onchange="changeEvaluationType()">
                                @foreach($evaluationTypes as $key => $label)
                                <option value="{{ $key }}" {{ ($evaluationType ?? 'monthly') == $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                                @endforeach
                            </select>
                            <div class="text-muted small mt-1">
                                <i class="fas fa-info-circle"></i>
                                سيتم عرض البنود المناسبة لنوع التقييم المختار
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center h-100">
                                <div class="alert alert-info mb-0 w-100">
                                    <i class="fas fa-lightbulb me-2"></i>
                                    <strong>{{ $evaluationTypes[$evaluationType ?? 'monthly'] }}</strong> محدد حالياً
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Basic Info -->
            <div class="projects-table-container mb-4">
                <div class="table-header">
                    <h2>📋 بيانات التقييم الأساسية</h2>
                </div>
                <div style="padding: 1.5rem;">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">الموظف المُراد تقييمه <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <select name="user_id" id="userSelect" class="form-select" required
                                    data-role-id="{{ $selectedRoleId }}"
                                    data-ajax-url="{{ route('kpi-evaluation.ajax.user-projects') }}">
                                    <option value="">اختر الموظف</option>
                                    @if(isset($usersWithRole) && $usersWithRole->count() > 0)
                                    @foreach($usersWithRole as $userWithRole)
                                    <option value="{{ $userWithRole->id }}">
                                        {{ $userWithRole->name }}
                                        @if($userWithRole->department)
                                        - {{ $userWithRole->department }}
                                        @endif
                                    </option>
                                    @endforeach
                                    @else
                                    <option value="" disabled>لا يوجد موظفين بهذا الدور</option>
                                    @endif
                                </select>
                                <button type="button" class="btn btn-outline-primary" id="viewDetailsBtn" disabled>
                                    <i class="fas fa-chart-bar me-1"></i>عرض التفاصيل
                                </button>
                            </div>
                            @if(isset($usersWithRole) && $usersWithRole->count() == 0)
                            <div class="text-muted small mt-1">
                                <i class="fas fa-info-circle"></i>
                                لم يتم العثور على موظفين لديهم دور "{{ $selectedRole->display_name ?? $selectedRole->name }}"
                            </div>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">شهر التقييم <span class="text-danger">*</span></label>
                            <input type="month" name="review_month" id="reviewMonthInput" class="form-control"
                                value="{{ now()->format('Y-m') }}" required>
                            <div class="text-muted small mt-1" id="evaluationPeriodHint">
                                <i class="fas fa-calendar-alt"></i>
                                <span id="periodText"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Positive Criteria -->
            @if($evaluationCriteria->has('positive'))
            <div class="projects-table-container mb-4">
                <div class="table-header" style="background: linear-gradient(135deg, #10b981, #059669);">
                    <h2>✅ البنود الإيجابية</h2>
                    <small style="opacity: 0.9;">إجمالي النقاط المتاحة: {{ $evaluationCriteria['positive']->sum('max_points') }} نقطة</small>
                </div>
                <div style="padding: 1.5rem;">
                    <div class="row">
                        @foreach($evaluationCriteria['positive'] as $criterion)
                        <div class="col-md-6 mb-3">
                            <div class="criteria-card">
                                <label class="form-label fw-bold">
                                    {{ $criterion->criteria_name }}
                                    <span class="badge bg-primary">{{ $criterion->max_points }} نقطة</span>
                                    <span class="badge bg-info ms-1" title="الدور المرتبط بهذا البند">
                                        <i class="fas fa-user-tag me-1"></i>{{ $criterion->role->display_name ?? $criterion->role->name }}
                                    </span>
                                </label>
                                @if($criterion->criteria_description)
                                <p class="text-muted small">{{ $criterion->criteria_description }}</p>
                                @endif
                                <div class="input-group mb-2">
                                    <input type="number"
                                        name="criteria_scores[{{ $criterion->id }}]"
                                        class="form-control criteria-input"
                                        min="0"
                                        max="{{ $criterion->max_points }}"
                                        value="{{ $criterion->max_points }}"
                                        data-max="{{ $criterion->max_points }}"
                                        data-type="positive">
                                    <span class="input-group-text">/ {{ $criterion->max_points }}</span>
                                </div>
                                <textarea name="criteria_notes[{{ $criterion->id }}]"
                                    class="form-control form-control-sm mb-2"
                                    rows="2"
                                    placeholder="ملاحظة اختيارية لهذا المعيار..."
                                    style="font-size: 13px; resize: vertical;"></textarea>
                                @if($criterion->category)
                                <small class="text-info">فئة: {{ $criterion->category }}</small>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Negative Criteria -->
            @if($evaluationCriteria->has('negative'))
            <div class="projects-table-container mb-4">
                <div class="table-header" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                    <h2>❌ البنود السلبية (خصومات)</h2>
                    <small style="opacity: 0.9;">إجمالي الخصومات المحتملة: {{ $evaluationCriteria['negative']->sum('max_points') }} نقطة</small>
                </div>
                <div style="padding: 1.5rem;">
                    <div class="row">
                        @foreach($evaluationCriteria['negative'] as $criterion)
                        <div class="col-md-6 mb-3">
                            <div class="criteria-card">
                                <label class="form-label fw-bold">
                                    {{ $criterion->criteria_name }}
                                    <span class="badge bg-danger">-{{ $criterion->max_points }} نقطة</span>
                                    <span class="badge bg-info ms-1" title="الدور المرتبط بهذا البند">
                                        <i class="fas fa-user-tag me-1"></i>{{ $criterion->role->display_name ?? $criterion->role->name }}
                                    </span>
                                </label>
                                @if($criterion->criteria_description)
                                <p class="text-muted small">{{ $criterion->criteria_description }}</p>
                                @endif
                                <div class="input-group mb-2">
                                    <input type="number"
                                        name="criteria_scores[{{ $criterion->id }}]"
                                        class="form-control criteria-input"
                                        min="0"
                                        max="{{ $criterion->max_points }}"
                                        value="0"
                                        data-max="{{ $criterion->max_points }}"
                                        data-type="negative">
                                    <span class="input-group-text">/ {{ $criterion->max_points }}</span>
                                </div>
                                <textarea name="criteria_notes[{{ $criterion->id }}]"
                                    class="form-control form-control-sm mb-2"
                                    rows="2"
                                    placeholder="ملاحظة اختيارية لهذا الخصم..."
                                    style="font-size: 13px; resize: vertical;"></textarea>
                                @if($criterion->category)
                                <small class="text-info">فئة: {{ $criterion->category }}</small>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Bonus Criteria -->
            @if($evaluationCriteria->has('bonus'))
            <div class="projects-table-container mb-4">
                <div class="table-header" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                    <h2>🌟 البونص الإضافي</h2>
                    <small style="opacity: 0.9;">إجمالي البونص المتاح: {{ $evaluationCriteria['bonus']->sum('max_points') }} نقطة</small>
                </div>
                <div style="padding: 1.5rem;">
                    <div class="row">
                        @foreach($evaluationCriteria['bonus'] as $criterion)
                        <div class="col-md-6 mb-3">
                            <div class="criteria-card">
                                <label class="form-label fw-bold">
                                    {{ $criterion->criteria_name }}
                                    <span class="badge bg-warning text-dark">+{{ $criterion->max_points }} نقطة</span>
                                    <span class="badge bg-info ms-1" title="الدور المرتبط بهذا البند">
                                        <i class="fas fa-user-tag me-1"></i>{{ $criterion->role->display_name ?? $criterion->role->name }}
                                    </span>
                                </label>
                                @if($criterion->criteria_description)
                                <p class="text-muted small">{{ $criterion->criteria_description }}</p>
                                @endif
                                <div class="input-group mb-2">
                                    <input type="number"
                                        name="criteria_scores[{{ $criterion->id }}]"
                                        class="form-control criteria-input"
                                        min="0"
                                        max="{{ $criterion->max_points }}"
                                        value="0"
                                        data-max="{{ $criterion->max_points }}"
                                        data-type="bonus">
                                    <span class="input-group-text">/ {{ $criterion->max_points }}</span>
                                </div>
                                <textarea name="criteria_notes[{{ $criterion->id }}]"
                                    class="form-control form-control-sm mb-2"
                                    rows="2"
                                    placeholder="ملاحظة اختيارية للبونص..."
                                    style="font-size: 13px; resize: vertical;"></textarea>
                                @if($criterion->category)
                                <small class="text-info">فئة: {{ $criterion->category }}</small>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Project-Based Evaluation Section -->
            <div id="projectEvaluationSection" style="display: none;">
                <div class="projects-table-container mb-4">
                    <div class="table-header">
                        <h2>🚀 تقييم المشاريع</h2>
                        <small style="opacity: 0.9;">تقييم الأداء في المشاريع التي شارك فيها الموظف</small>
                    </div>
                    <div style="padding: 1.5rem;">
                        <div id="userProjectsContainer">
                            <div class="text-center text-muted">
                                <i class="fas fa-project-diagram fa-3x mb-3"></i>
                                <p>يرجى اختيار موظف لعرض مشاريعه</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary -->
            <div class="projects-table-container mb-4">
                <div class="table-header" style="background: linear-gradient(135deg, #6b7280, #4b5563);">
                    <h2>📊 ملخص التقييم</h2>
                </div>
                <div style="padding: 1.5rem;">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <div class="summary-item">
                                <h4 class="text-success" id="positiveTotal">0</h4>
                                <small>النقاط الإيجابية</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="summary-item">
                                <h4 class="text-danger" id="negativeTotal">0</h4>
                                <small>الخصومات</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="summary-item">
                                <h4 class="text-warning" id="bonusTotal">0</h4>
                                <small>البونص</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="summary-item">
                                <h4 class="text-primary" id="finalTotal">0</h4>
                                <small>الإجمالي النهائي</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="projects-table-container mb-4">
                <div class="table-header">
                    <h2>📝 ملاحظات</h2>
                </div>
                <div style="padding: 1.5rem;">
                    <textarea name="notes" class="filter-select" rows="4"
                        placeholder="أضف ملاحظات حول التقييم..." style="min-height: 100px;"></textarea>
                </div>
            </div>

            <!-- Submit -->
            <div class="d-flex justify-content-end gap-2 mb-4">
                <button type="button" class="services-btn" style="background: linear-gradient(135deg, #6b7280, #4b5563);" onclick="window.history.back()">
                    <i class="fas fa-arrow-left"></i>
                    العودة
                </button>
                <button type="submit" class="services-btn" style="background: linear-gradient(135deg, #10b981, #059669);">
                    <i class="fas fa-save"></i>
                    حفظ التقييم
                </button>
            </div>
        </form>
        @else
        <div class="projects-table-container">
            <div class="table-header">
                <h2>اختر دوراً للبدء</h2>
            </div>
            <div class="empty-state" style="padding: 3rem 2rem;">
                <i class="fas fa-clipboard-list"></i>
                <h4>اختر دوراً للبدء في التقييم</h4>
                <p>حدد الدور من القائمة أعلاه لعرض بنود التقييم المخصصة له</p>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Details Sidebar -->
<div class="details-sidebar" id="detailsSidebar" data-ajax-url="{{ url('/kpi-evaluation/user-details') }}">
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
        <div class="period-info-banner" id="periodInfoBanner" style="display: none;">
            <div class="d-flex align-items-center justify-content-between p-3 bg-light border-bottom">
                <div class="flex-grow-1">
                    <small class="text-muted d-block mb-1">فترة التقييم:</small>
                    <strong class="text-primary" id="sidebarPeriodText">
                        <i class="fas fa-calendar-alt me-1"></i>
                        <span id="sidebarPeriodDates"></span>
                    </strong>
                </div>
                <div>
                    <span class="badge bg-info" id="sidebarEvaluationType"></span>
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
                <button class="nav-link" id="transferred-tasks-tab" data-bs-toggle="pill" data-bs-target="#transferred-tasks" type="button" role="tab">
                    <i class="fas fa-exchange-alt me-2"></i>المهام المنقولة
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
                    <div id="revisionsContent"></div>
                </div>
            </div>

            <!-- المشاريع المسلّمة -->
            <div class="tab-pane fade" id="delivered-projects" role="tabpanel">
                <div class="tab-content-wrapper">
                    <div id="deliveredProjectsContent"></div>
                </div>
            </div>

            <!-- الأخطاء -->
            <div class="tab-pane fade" id="errors" role="tabpanel">
                <div class="tab-content-wrapper">
                    <div id="errorsContent"></div>
                </div>
            </div>

            <!-- المشاريع المتأخرة -->
            <div class="tab-pane fade" id="delayed-projects" role="tabpanel">
                <div class="tab-content-wrapper">
                    <div id="delayedProjectsContent"></div>
                </div>
            </div>

            <!-- المهام المتأخرة -->
            <div class="tab-pane fade" id="delayed-tasks" role="tabpanel">
                <div class="tab-content-wrapper">
                    <div id="delayedTasksContent"></div>
                </div>
            </div>

            <!-- المهام المنقولة -->
            <div class="tab-pane fade" id="transferred-tasks" role="tabpanel">
                <div class="tab-content-wrapper">
                    <div id="transferredTasksContent"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- KPI Evaluation Create JavaScript -->
<script src="{{ asset('js/kpi-evaluation-create.js') }}"></script>
@endpush