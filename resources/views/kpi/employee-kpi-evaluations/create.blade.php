@extends('layouts.app')

@section('title', 'تقييم KPI للموظفين')

@push('styles')
<link href="{{ asset('css/evaluation-criteria-modern.css') }}" rel="stylesheet">
<link href="{{ asset('css/kpi/kpi-evaluation-create.css') }}" rel="stylesheet">
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
                    <h2 class="mb-1" style="color: white !important;">🏆 تقييم KPI لدور: {{ $selectedRole->display_name ?? $selectedRole->name }}</h2>
                    <p class="mb-0" style="color: white !important; opacity: 0.9;">تقييم أداء الموظف بناءً على مؤشرات KPI المحددة لهذا الدور</p>
                    @if(isset($usersWithRole))
                        <div class="badge-modern mt-2" style="background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3);">
                            <i class="fas fa-users me-1"></i>
                            {{ $usersWithRole->count() }} موظف متاح للتقييم
                        </div>
                    @endif
                </div>
            </div>
            <div>
                <a href="{{ route('kpi-evaluation.create') }}" class="btn btn-modern btn-outline-light" style="color: white; border-color: rgba(255,255,255,0.5);">
                    <i class="fas fa-arrow-left me-2"></i>
                    تغيير الدور
                </a>
            </div>
        </div>
    </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(isset($usersWithRole) && $usersWithRole->count() == 0)
                <!-- No Users Available Message -->
                <div class="alert alert-warning">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                        <div>
                            <h5 class="alert-heading">لا يوجد موظفين متاحين للتقييم</h5>
                            <p class="mb-2">لم يتم العثور على أي موظفين لديهم دور "{{ $selectedRole->display_name ?? $selectedRole->name }}"</p>
                            <p class="mb-0">يرجى التأكد من تعيين الأدوار للموظفين أولاً.</p>
                        </div>
                    </div>
                    <hr>
                    <div class="d-flex gap-2">
                        <a href="{{ route('kpi-evaluation.create') }}" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-2"></i>اختر دور آخر
                        </a>
                        <a href="{{ route('users.index') }}" class="btn btn-outline-info">
                            <i class="fas fa-users me-2"></i>إدارة الموظفين
                        </a>
                    </div>
                </div>
            @elseif($evaluationCriteria && $selectedRoleId)
                <!-- Evaluation Form -->
                <form method="POST" action="{{ route('kpi-evaluation.store') }}" id="evaluationForm">
                    @csrf
                    <input type="hidden" name="role_id" value="{{ $selectedRoleId }}">
                    <input type="hidden" name="evaluation_type" value="{{ $evaluationType ?? 'monthly' }}">

                    <!-- Evaluation Type Selection -->
                    <div class="card mb-4">
                        <div class="card-header bg-warning text-white">
                            <h5 class="mb-0">⚡ نوع التقييم</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">نوع التقييم <span class="text-danger">*</span></label>
                                    <select name="evaluation_type_selector" id="evaluationTypeSelector" class="form-select" onchange="changeEvaluationType()">
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
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">📋 بيانات التقييم الأساسية</h5>
                        </div>
                        <div class="card-body">
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
                        <div class="card mb-4">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">✅ البنود الإيجابية</h5>
                                <small>إجمالي النقاط المتاحة: {{ $evaluationCriteria['positive']->sum('max_points') }} نقطة</small>
                            </div>
                            <div class="card-body">
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
                        <div class="card mb-4">
                            <div class="card-header bg-danger text-white">
                                <h5 class="mb-0">❌ البنود السلبية (خصومات)</h5>
                                <small>إجمالي الخصومات المحتملة: {{ $evaluationCriteria['negative']->sum('max_points') }} نقطة</small>
                            </div>
                            <div class="card-body">
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
                        <div class="card mb-4">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0">🌟 البونص الإضافي</h5>
                                <small>إجمالي البونص المتاح: {{ $evaluationCriteria['bonus']->sum('max_points') }} نقطة</small>
                            </div>
                            <div class="card-body">
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
                        <div class="card mb-4">
                            <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                                <h5 class="mb-0">🚀 تقييم المشاريع</h5>
                                <small>تقييم الأداء في المشاريع التي شارك فيها الموظف</small>
                            </div>
                            <div class="card-body">
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
                    <div class="card mb-4">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0">📊 ملخص التقييم</h5>
                        </div>
                        <div class="card-body">
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
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">📝 ملاحظات</h5>
                        </div>
                        <div class="card-body">
                            <textarea name="notes" class="form-control" rows="4"
                                      placeholder="أضف ملاحظات حول التقييم..."></textarea>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="d-flex justify-content-end gap-2 mb-4">
                        <button type="button" class="btn btn-secondary" onclick="window.history.back()">
                            <i class="fas fa-arrow-left me-1"></i>العودة
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-1"></i>حفظ التقييم
                        </button>
                    </div>
                </form>
            @else
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">اختر دوراً للبدء في التقييم</h5>
                        <p class="text-muted">حدد الدور من القائمة أعلاه لعرض بنود التقييم المخصصة له</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- KPI Evaluation Create JavaScript -->
<script src="{{ asset('js/kpi-evaluation-create.js') }}"></script>

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
