@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/employee-errors.css') }}">
@endpush

@section('content')
<div class="simple-container">
    <div class="container-fluid px-4 employee-errors-page">
        <!-- Header - Inspired Design -->
        <div class="employee-errors-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>⚠️ إدارة الأخطاء</h2>
                    <p>عرض وإدارة جميع أخطاء الموظفين بشكل احترافي</p>
                </div>

                @php
                $user = Auth::user();
                $globalLevel = \App\Models\RoleHierarchy::getUserMaxHierarchyLevel($user);
                $departmentLevel = \App\Models\DepartmentRole::getUserDepartmentHierarchyLevel($user);
                $canReportErrors = ($globalLevel && $globalLevel >= 2) || ($departmentLevel && $departmentLevel >= 2);
                @endphp

                @if($canReportErrors)
                <button onclick="openCreateModal()" class="btn btn-danger">
                    <i class="fas fa-plus"></i>
                    تسجيل خطأ جديد
                </button>
                @endif
            </div>
        </div>

        <!-- Tabs Navigation -->
        <ul class="nav nav-tabs employee-errors-tabs mb-4" id="errorTabs" role="tablist">
            @if($user->hasRole(['admin', 'super-admin', 'hr', 'project_manager']))
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="all-errors-tab" data-bs-toggle="tab" data-bs-target="#all-errors" type="button" role="tab">
                    <i class="fas fa-list"></i>
                    جميع الأخطاء
                </button>
            </li>
            @endif
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $user->hasRole(['admin', 'super-admin', 'hr', 'project_manager']) ? '' : 'active' }}" id="my-errors-tab" data-bs-toggle="tab" data-bs-target="#my-errors" type="button" role="tab">
                    <i class="fas fa-user-times"></i>
                    أخطائي
                </button>
            </li>
            @if($canReportErrors)
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="reported-errors-tab" data-bs-toggle="tab" data-bs-target="#reported-errors" type="button" role="tab">
                    <i class="fas fa-exclamation-circle"></i>
                    الأخطاء التي أضفتها
                </button>
            </li>
            @endif
            <!-- Tab الأخطاء الجوهرية (متاح للجميع) -->
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="critical-errors-tab" data-bs-toggle="tab" data-bs-target="#critical-errors" type="button" role="tab">
                    <i class="fas fa-exclamation-triangle"></i>
                    الأخطاء الجوهرية
                    @if($criticalErrors->count() > 0)
                    <span class="badge bg-danger ms-2">{{ $criticalErrors->count() }}</span>
                    @endif
                </button>
            </li>
        </ul>

        <!-- إحصائيات الأخطاء - Enhanced Style (تتغير حسب التاب) -->
        <div class="row mb-4" id="stats-container">
            <!-- إجمالي الأخطاء -->
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card total-errors">
                    <div class="d-flex align-items-center">
                        <div class="icon-wrapper">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="ms-3">
                            <p class="mb-1">إجمالي الأخطاء</p>
                            <h3 id="stat-total-errors">{{ $myErrorsStats['total_errors'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- الأخطاء الجوهرية -->
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card critical-errors">
                    <div class="d-flex align-items-center">
                        <div class="icon-wrapper">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="ms-3">
                            <p class="mb-1">أخطاء جوهرية</p>
                            <h3 id="stat-critical-errors">{{ $myErrorsStats['critical_errors'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- الأخطاء العادية -->
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card normal-errors">
                    <div class="d-flex align-items-center">
                        <div class="icon-wrapper">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <div class="ms-3">
                            <p class="mb-1">أخطاء عادية</p>
                            <h3 id="stat-normal-errors">{{ $myErrorsStats['normal_errors'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- أخطاء الجودة -->
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card quality-errors">
                    <div class="d-flex align-items-center">
                        <div class="icon-wrapper">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="ms-3">
                            <p class="mb-1">أخطاء جودة</p>
                            <h3 id="stat-quality-errors">{{ $myErrorsStats['by_category']['quality'] ?? 0 }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- الفلاتر - Enhanced Layout -->
        <div class="filters-card">
            <form method="GET" action="{{ route('employee-errors.index') }}" class="row g-3">
                <!-- الشهر -->
                <div class="col-md-2">
                    <label class="form-label">
                        <i class="fas fa-calendar-alt"></i>
                        الشهر
                    </label>
                    <input type="month" name="month" class="form-control" value="{{ request('month') }}" onchange="this.form.submit()">
                </div>

                <!-- كود المشروع -->
                <div class="col-md-2">
                    <label class="form-label">
                        <i class="fas fa-project-diagram"></i>
                        كود المشروع
                    </label>
                    <div class="input-group">
                        <input type="text" name="project_code" class="form-control" value="{{ request('project_code') }}" placeholder="أدخل كود المشروع..." list="projectCodesList" id="projectCodeInput">
                        <button type="button" class="btn btn-primary" onclick="this.form.submit()">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <datalist id="projectCodesList">
                        @php
                        $projects = \App\Models\Project::select('code', 'name')->whereNotNull('code')->distinct()->get();
                        @endphp
                        @foreach($projects as $project)
                        <option value="{{ $project->code }}">{{ $project->name }}</option>
                        @endforeach
                    </datalist>
                </div>

                <!-- نوع الخطأ -->
                <div class="col-md-2">
                    <label class="form-label">
                        <i class="fas fa-exclamation-circle"></i>
                        نوع الخطأ
                    </label>
                    <select name="error_type" class="form-select" onchange="this.form.submit()">
                        <option value="">الكل</option>
                        <option value="normal" {{ request('error_type') == 'normal' ? 'selected' : '' }}>عادي</option>
                        <option value="critical" {{ request('error_type') == 'critical' ? 'selected' : '' }}>جوهري</option>
                    </select>
                </div>

                <!-- تصنيف الخطأ -->
                <div class="col-md-2">
                    <label class="form-label">
                        <i class="fas fa-tags"></i>
                        التصنيف
                    </label>
                    <select name="error_category" class="form-select" onchange="this.form.submit()">
                        <option value="">الكل</option>
                        <option value="quality" {{ request('error_category') == 'quality' ? 'selected' : '' }}>جودة</option>
                        <option value="deadline" {{ request('error_category') == 'deadline' ? 'selected' : '' }}>موعد نهائي</option>
                        <option value="communication" {{ request('error_category') == 'communication' ? 'selected' : '' }}>تواصل</option>
                        <option value="technical" {{ request('error_category') == 'technical' ? 'selected' : '' }}>فني</option>
                        <option value="procedural" {{ request('error_category') == 'procedural' ? 'selected' : '' }}>إجرائي</option>
                        <option value="other" {{ request('error_category') == 'other' ? 'selected' : '' }}>أخرى</option>
                    </select>
                </div>

                <!-- نوع المصدر -->
                <div class="col-md-2">
                    <label class="form-label">
                        <i class="fas fa-layer-group"></i>
                        المصدر
                    </label>
                    <select name="errorable_type" class="form-select" onchange="this.form.submit()">
                        <option value="">الكل</option>
                        <option value="App\Models\TaskUser" {{ request('errorable_type') == 'App\Models\TaskUser' ? 'selected' : '' }}>مهام عادية</option>
                        <option value="App\Models\TemplateTaskUser" {{ request('errorable_type') == 'App\Models\TemplateTaskUser' ? 'selected' : '' }}>مهام قوالب</option>
                        <option value="App\Models\ProjectServiceUser" {{ request('errorable_type') == 'App\Models\ProjectServiceUser' ? 'selected' : '' }}>مشاريع</option>
                    </select>
                </div>

                <!-- زر مسح الفلاتر -->
                <div class="col-md-2 d-flex align-items-end">
                    <a href="{{ route('employee-errors.index') }}" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-times"></i>
                        مسح الفلاتر
                    </a>
                </div>
            </form>
        </div>

        <!-- Tab Content -->
        <div class="tab-content" id="errorTabsContent">
            @if($user->hasRole(['admin', 'super-admin', 'hr', 'project_manager']))
            <!-- Tab 1: جميع الأخطاء -->
            <div class="tab-pane fade show active" id="all-errors" role="tabpanel">
                <div class="errors-table-card">
                    <div class="card-body p-0">
                        @if($allErrors->isEmpty())
                        <div class="empty-state">
                            <i class="fas fa-check-circle fa-4x"></i>
                            <h5>لا توجد أخطاء</h5>
                            <p>لا توجد أخطاء في النظام</p>
                        </div>
                        @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 5%">#</th>
                                        <th style="width: 12%">الموظف</th>
                                        <th style="width: 20%">عنوان الخطأ</th>
                                        <th style="width: 25%">الوصف</th>
                                        <th style="width: 8%">النوع</th>
                                        <th style="width: 10%">التصنيف</th>
                                        <th style="width: 10%">المصدر</th>
                                        <th style="width: 10%">سجله</th>
                                        <th style="width: 12%">التاريخ</th>
                                        <th style="width: 15%" class="text-center">إجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($allErrors as $index => $error)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <div class="d-flex align-items-center user-info">
                                                <div class="user-avatar me-2">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                                <strong>{{ $error->user->name }}</strong>
                                            </div>
                                        </td>
                                        <td><strong>{{ $error->title }}</strong></td>
                                        <td><span class="text-muted">{{ Str::limit($error->description, 60) }}</span></td>
                                        <td>
                                            @if($error->error_type === 'critical')
                                            <span class="badge badge-critical"><i class="fas fa-exclamation-circle"></i> جوهري</span>
                                            @else
                                            <span class="badge badge-normal"><i class="fas fa-exclamation-triangle"></i> عادي</span>
                                            @endif
                                        </td>
                                        <td><span class="badge badge-category">{{ $error->error_category_text }}</span></td>
                                        <td>
                                            @if($error->errorable_type === 'App\Models\TaskUser')
                                            <span class="badge badge-source-task">📋 مهمة</span>
                                            @elseif($error->errorable_type === 'App\Models\TemplateTaskUser')
                                            <span class="badge badge-source-template">📝 قالب</span>
                                            @elseif($error->errorable_type === 'App\Models\ProjectServiceUser')
                                            <span class="badge badge-source-project">🗂️ مشروع</span>
                                            @endif
                                        </td>
                                        <td><small>{{ $error->reportedBy->name }}</small></td>
                                        <td>
                                            <small class="text-muted">
                                                <i class="fas fa-calendar-alt"></i>
                                                {{ $error->created_at->format('Y-m-d') }}<br>
                                                <span class="text-muted" style="font-size: 0.75rem;">{{ $error->created_at->diffForHumans() }}</span>
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group-modern">
                                                <a href="{{ route('employee-errors.show', $error->id) }}" class="btn btn-outline-secondary" title="عرض التفاصيل">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <button class="btn btn-outline-primary edit-error-btn" title="تعديل"
                                                    data-error-id="{{ $error->id }}"
                                                    data-error-title="{{ $error->title }}"
                                                    data-error-description="{{ $error->description }}"
                                                    data-error-category="{{ $error->error_category }}"
                                                    data-error-type="{{ $error->error_type }}">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-outline-danger delete-error-btn" title="حذف"
                                                    data-error-id="{{ $error->id }}"
                                                    data-error-title="{{ $error->title }}">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            <!-- Tab 2: أخطائي -->
            <div class="tab-pane fade {{ $user->hasRole(['admin', 'super-admin', 'hr', 'project_manager']) ? '' : 'show active' }}" id="my-errors" role="tabpanel">
                <div class="errors-table-card">
                    <div class="card-body p-0">
                        @if($myErrors->isEmpty())
                        <div class="empty-state">
                            <i class="fas fa-check-circle fa-4x"></i>
                            <h5>لا توجد أخطاء</h5>
                            <p>رائع! لم يتم تسجيل أي أخطاء</p>
                        </div>
                        @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 5%">#</th>
                                        @if($canReportErrors)
                                        <th style="width: 12%">الموظف</th>
                                        @endif
                                        <th style="width: 20%">عنوان الخطأ</th>
                                        <th style="width: 30%">الوصف</th>
                                        <th style="width: 8%">النوع</th>
                                        <th style="width: 10%">التصنيف</th>
                                        <th style="width: 10%">المصدر</th>
                                        @if(!$canReportErrors)
                                        <th style="width: 10%">سجله</th>
                                        @endif
                                        <th style="width: 12%">التاريخ</th>
                                        <th style="width: 15%" class="text-center">إجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($myErrors as $index => $error)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>

                                        @if($canReportErrors)
                                        <td>
                                            <div class="d-flex align-items-center user-info">
                                                <div class="user-avatar me-2">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                                <strong>{{ $error->user->name }}</strong>
                                            </div>
                                        </td>
                                        @endif

                                        <td>
                                            <strong>{{ $error->title }}</strong>
                                        </td>

                                        <td>
                                            <span class="text-muted">{{ Str::limit($error->description, 80) }}</span>
                                        </td>

                                        <td>
                                            @if($error->error_type === 'critical')
                                            <span class="badge badge-critical">
                                                <i class="fas fa-exclamation-circle"></i> جوهري
                                            </span>
                                            @else
                                            <span class="badge badge-normal">
                                                <i class="fas fa-exclamation-triangle"></i> عادي
                                            </span>
                                            @endif
                                        </td>

                                        <td>
                                            <span class="badge badge-category">{{ $error->error_category_text }}</span>
                                        </td>

                                        <td>
                                            @if($error->errorable_type === 'App\Models\TaskUser')
                                            <span class="badge badge-source-task">📋 مهمة</span>
                                            @elseif($error->errorable_type === 'App\Models\TemplateTaskUser')
                                            <span class="badge badge-source-template">📝 قالب</span>
                                            @elseif($error->errorable_type === 'App\Models\ProjectServiceUser')
                                            <span class="badge badge-source-project">🗂️ مشروع</span>
                                            @endif
                                        </td>

                                        @if(!$canReportErrors)
                                        <td>
                                            <small>{{ $error->reportedBy->name }}</small>
                                        </td>
                                        @endif

                                        <td>
                                            <small class="text-muted">
                                                <i class="fas fa-calendar-alt"></i>
                                                {{ $error->created_at->format('Y-m-d') }}<br>
                                                <span class="text-muted" style="font-size: 0.75rem;">{{ $error->created_at->diffForHumans() }}</span>
                                            </small>
                                        </td>

                                        <td class="text-center">
                                            <div class="btn-group-modern">
                                                <a href="{{ route('employee-errors.show', $error->id) }}"
                                                    class="btn btn-outline-secondary"
                                                    title="عرض">
                                                    <i class="fas fa-eye"></i>
                                                </a>

                                                @php
                                                $canEdit = $error->reported_by === Auth::id();
                                                if (!$canEdit) {
                                                $globalLevel = \App\Models\RoleHierarchy::getUserMaxHierarchyLevel(Auth::user());
                                                $departmentLevel = \App\Models\DepartmentRole::getUserDepartmentHierarchyLevel(Auth::user());
                                                $canEdit = ($globalLevel && $globalLevel >= 3) || ($departmentLevel && $departmentLevel >= 3);
                                                }
                                                @endphp

                                                @if($canEdit)
                                                <button class="btn btn-outline-primary edit-error-btn"
                                                    title="تعديل"
                                                    data-error-id="{{ $error->id }}"
                                                    data-error-title="{{ $error->title }}"
                                                    data-error-description="{{ $error->description }}"
                                                    data-error-category="{{ $error->error_category }}"
                                                    data-error-type="{{ $error->error_type }}">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                @endif

                                                @php
                                                $canDelete = $error->reported_by === Auth::id();
                                                if (!$canDelete) {
                                                $globalLevel = \App\Models\RoleHierarchy::getUserMaxHierarchyLevel(Auth::user());
                                                $departmentLevel = \App\Models\DepartmentRole::getUserDepartmentHierarchyLevel(Auth::user());
                                                $canDelete = ($globalLevel && $globalLevel >= 4) || ($departmentLevel && $departmentLevel >= 4);
                                                }
                                                @endphp

                                                @if($canDelete)
                                                <button class="btn btn-outline-danger delete-error-btn"
                                                    title="حذف"
                                                    data-error-id="{{ $error->id }}">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            @if($canReportErrors)
            <!-- Tab 2: الأخطاء التي أضفتها -->
            <div class="tab-pane fade" id="reported-errors" role="tabpanel">
                <div class="errors-table-card">
                    <div class="card-body p-0">
                        @if($reportedErrors->isEmpty())
                        <div class="empty-state">
                            <i class="fas fa-info-circle fa-4x"></i>
                            <h5>لم تسجل أي أخطاء</h5>
                            <p>لم تسجل أي أخطاء على الموظفين بعد</p>
                        </div>
                        @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 5%">#</th>
                                        <th style="width: 12%">الموظف</th>
                                        <th style="width: 20%">عنوان الخطأ</th>
                                        <th style="width: 30%">الوصف</th>
                                        <th style="width: 8%">النوع</th>
                                        <th style="width: 10%">التصنيف</th>
                                        <th style="width: 10%">المصدر</th>
                                        <th style="width: 12%">التاريخ</th>
                                        <th style="width: 15%" class="text-center">إجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reportedErrors as $index => $error)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <div class="d-flex align-items-center user-info">
                                                <div class="user-avatar me-2">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                                <strong>{{ $error->user->name }}</strong>
                                            </div>
                                        </td>
                                        <td><strong>{{ $error->title }}</strong></td>
                                        <td><span class="text-muted">{{ Str::limit($error->description, 80) }}</span></td>
                                        <td>
                                            @if($error->error_type === 'critical')
                                            <span class="badge badge-critical"><i class="fas fa-exclamation-circle"></i> جوهري</span>
                                            @else
                                            <span class="badge badge-normal"><i class="fas fa-exclamation-triangle"></i> عادي</span>
                                            @endif
                                        </td>
                                        <td><span class="badge badge-category">{{ $error->error_category_text }}</span></td>
                                        <td>
                                            @if($error->errorable_type === 'App\Models\TaskUser')
                                            <span class="badge badge-source-task">📋 مهمة</span>
                                            @elseif($error->errorable_type === 'App\Models\TemplateTaskUser')
                                            <span class="badge badge-source-template">📝 قالب</span>
                                            @elseif($error->errorable_type === 'App\Models\ProjectServiceUser')
                                            <span class="badge badge-source-project">🗂️ مشروع</span>
                                            @endif
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <i class="fas fa-calendar-alt"></i>
                                                {{ $error->created_at->format('Y-m-d') }}<br>
                                                <span class="text-muted" style="font-size: 0.75rem;">{{ $error->created_at->diffForHumans() }}</span>
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group-modern">
                                                <a href="{{ route('employee-errors.show', $error->id) }}" class="btn btn-outline-secondary" title="عرض التفاصيل">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <button class="btn btn-outline-primary edit-error-btn" title="تعديل"
                                                    data-error-id="{{ $error->id }}"
                                                    data-error-title="{{ $error->title }}"
                                                    data-error-description="{{ $error->description }}"
                                                    data-error-category="{{ $error->error_category }}"
                                                    data-error-type="{{ $error->error_type }}">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-outline-danger delete-error-btn" title="حذف"
                                                    data-error-id="{{ $error->id }}"
                                                    data-error-title="{{ $error->title }}">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            <!-- Tab: الأخطاء الجوهرية - متاح لجميع الموظفين للتعلم والوعي -->
            <div class="tab-pane fade" id="critical-errors" role="tabpanel">
                <div class="alert alert-danger mb-3" style="border-radius: 12px; border-left: 4px solid #dc3545;">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-info-circle fa-2x me-3"></i>
                        <div>
                            <h6 class="mb-1">⚠️ الأخطاء الجوهرية - للتعلم والوعي</h6>
                            <p class="mb-0 small">هذه الأخطاء مرئية لجميع الموظفين للتعلم من تجارب الآخرين وتجنب تكرار نفس الأخطاء</p>
                        </div>
                    </div>
                </div>

                <div class="errors-table-card">
                    <div class="card-body p-0">
                        @if($criticalErrors->isEmpty())
                        <div class="empty-state">
                            <i class="fas fa-check-circle fa-4x" style="color: #10b981;"></i>
                            <h5>ممتاز! لا توجد أخطاء جوهرية</h5>
                            <p>لا توجد أخطاء جوهرية مسجلة في النظام حالياً</p>
                        </div>
                        @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 5%">#</th>
                                        <th style="width: 12%">الموظف</th>
                                        <th style="width: 18%">عنوان الخطأ</th>
                                        <th style="width: 25%">الوصف</th>
                                        <th style="width: 10%">التصنيف</th>
                                        <th style="width: 10%">المصدر</th>
                                        <th style="width: 10%">سجله</th>
                                        <th style="width: 12%">التاريخ</th>
                                        <th style="width: 10%" class="text-center">إجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($criticalErrors as $index => $error)
                                    <tr style="background-color: rgba(220, 53, 69, 0.02);">
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <div class="d-flex align-items-center user-info">
                                                <div class="user-avatar me-2" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                                <strong>{{ $error->user->name }}</strong>
                                            </div>
                                        </td>
                                        <td>
                                            <strong style="color: #dc3545;">{{ $error->title }}</strong>
                                            <br>
                                            <span class="badge badge-critical mt-1">
                                                <i class="fas fa-exclamation-circle"></i> جوهري
                                            </span>
                                        </td>
                                        <td><span class="text-muted">{{ Str::limit($error->description, 100) }}</span></td>
                                        <td><span class="badge badge-category">{{ $error->error_category_text }}</span></td>
                                        <td>
                                            @if($error->errorable_type === 'App\Models\TaskUser')
                                            <span class="badge badge-source-task">📋 مهمة</span>
                                            @elseif($error->errorable_type === 'App\Models\TemplateTaskUser')
                                            <span class="badge badge-source-template">📝 قالب</span>
                                            @elseif($error->errorable_type === 'App\Models\ProjectServiceUser')
                                            <span class="badge badge-source-project">🗂️ مشروع</span>
                                            @endif
                                        </td>
                                        <td><small>{{ $error->reportedBy->name }}</small></td>
                                        <td>
                                            <small class="text-muted">
                                                <i class="fas fa-calendar-alt"></i>
                                                {{ $error->created_at->format('Y-m-d') }}<br>
                                                <span class="text-muted" style="font-size: 0.75rem;">{{ $error->created_at->diffForHumans() }}</span>
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('employee-errors.show', $error->id) }}"
                                                class="btn btn-outline-danger btn-sm"
                                                title="عرض التفاصيل">
                                                <i class="fas fa-eye"></i>
                                                عرض
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- إحصائيات سريعة للأخطاء الجوهرية -->
                        <div class="p-3" style="background: #f8f9fa; border-top: 1px solid #e9ecef;">
                            <div class="row text-center">
                                <div class="col-md-4">
                                    <small class="text-muted">إجمالي الأخطاء الجوهرية</small>
                                    <h5 class="mb-0 mt-1 text-danger">{{ $criticalErrors->count() }}</h5>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted">أكثر تصنيف شيوعاً</small>
                                    <h5 class="mb-0 mt-1">
                                        @php
                                        $topCategory = $criticalErrors->groupBy('error_category')->sortByDesc(fn($group) => $group->count())->keys()->first();
                                        $categoryText = match($topCategory) {
                                        'quality' => 'جودة',
                                        'deadline' => 'موعد نهائي',
                                        'communication' => 'تواصل',
                                        'technical' => 'فني',
                                        'procedural' => 'إجرائي',
                                        'other' => 'أخرى',
                                        default => 'غير محدد'
                                        };
                                        @endphp
                                        {{ $topCategory ? $categoryText : '-' }}
                                    </h5>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted">آخر خطأ جوهري</small>
                                    <h5 class="mb-0 mt-1 text-muted small">
                                        {{ $criticalErrors->first()?->created_at?->diffForHumans() ?? '-' }}
                                    </h5>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- Sidebar: Create Error -->
<div class="sidebar-panel" id="createErrorSidebar">
    <div class="sidebar-header">
        <h5 class="sidebar-title">
            <i class="fas fa-exclamation-triangle me-2"></i>
            تسجيل خطأ جديد
        </h5>
        <button type="button" class="sidebar-close" onclick="closeSidebar()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <form id="createErrorForm" onsubmit="submitCreateError(event)">
        <div class="sidebar-body">
            <!-- اختيار الموظف -->
            <div class="mb-3">
                <label class="form-label">الموظف <span class="text-danger">*</span></label>
                <input type="text"
                    id="user_name_search"
                    class="form-control datalist-input"
                    list="usersList"
                    placeholder="ابحث عن الموظف..."
                    autocomplete="off"
                    oninput="handleUserSelection()"
                    required>
                <datalist id="usersList"></datalist>
                <input type="hidden" name="user_id" id="user_id_create" required>
                <small class="text-muted d-block mt-1">
                    <i class="fas fa-search me-1"></i>
                    ابدأ بكتابة اسم الموظف للبحث
                </small>
            </div>

            <!-- نوع المصدر -->
            <div class="mb-3">
                <label class="form-label">مصدر الخطأ <span class="text-danger">*</span></label>
                <select name="errorable_type" id="errorable_type_create" required class="form-select" onchange="handleErrorableTypeChange('create')">
                    <option value="">اختر المصدر</option>
                    <option value="TaskUser">مهمة عادية</option>
                    <option value="TemplateTaskUser">مهمة قالب</option>
                    <option value="ProjectServiceUser">مشروع</option>
                </select>
            </div>

            <!-- كود المشروع (اختياري) -->
            <div id="project_code_container_create" class="mb-3 d-none">
                <label class="form-label">كود المشروع (اختياري)</label>
                <input type="text"
                    id="project_code_create"
                    class="form-control datalist-input"
                    list="projectCodesList"
                    placeholder="اختر أو اكتب كود المشروع..."
                    autocomplete="off"
                    oninput="handleProjectCodeSelection()">
                <datalist id="projectCodesList"></datalist>
                <small class="text-muted d-block mt-1">
                    <i class="fas fa-project-diagram me-1"></i>
                    اختر من مشاريع الموظف أو اكتب كود المشروع للتصفية
                </small>
                <div class="mt-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="loadErrorableOptions('create')">
                        <i class="fas fa-filter"></i> تطبيق الفلتر
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearProjectCodeFilter('create')">
                        <i class="fas fa-times"></i> مسح
                    </button>
                </div>
            </div>

            <!-- اختيار المهمة/المشروع -->
            <div id="errorable_select_create" class="mb-3 d-none">
                <label class="form-label">اختر المهمة/المشروع <span class="text-danger">*</span></label>
                <select name="errorable_id" id="errorable_id_create" class="form-select">
                    <option value="">اختر...</option>
                </select>
            </div>

            <!-- عنوان الخطأ -->
            <div class="mb-3">
                <label class="form-label">عنوان الخطأ <span class="text-danger">*</span></label>
                <input type="text" name="title" required class="form-control" placeholder="مثال: تأخر في التسليم">
            </div>

            <!-- وصف الخطأ -->
            <div class="mb-3">
                <label class="form-label">وصف الخطأ <span class="text-danger">*</span></label>
                <textarea name="description" required rows="4" class="form-control" placeholder="وصف تفصيلي للخطأ..."></textarea>
            </div>

            <!-- تصنيف الخطأ -->
            <div class="mb-3">
                <label class="form-label">تصنيف الخطأ <span class="text-danger">*</span></label>
                <select name="error_category" required class="form-select">
                    <option value="">اختر التصنيف</option>
                    <option value="quality">جودة</option>
                    <option value="deadline">موعد نهائي</option>
                    <option value="communication">تواصل</option>
                    <option value="technical">فني</option>
                    <option value="procedural">إجرائي</option>
                    <option value="other">أخرى</option>
                </select>
            </div>

            <!-- نوع الخطأ -->
            <div class="mb-3">
                <label class="form-label">نوع الخطأ <span class="text-danger">*</span></label>
                <div class="row">
                    <div class="col-6">
                        <div class="error-type-card">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="error_type" value="normal" required id="type_normal">
                                <label class="form-check-label" for="type_normal">
                                    <strong>عادي</strong>
                                    <p>خطأ بسيط قابل للتصحيح</p>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="error-type-card critical-type">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="error_type" value="critical" required id="type_critical">
                                <label class="form-check-label" for="type_critical">
                                    <strong class="text-danger">جوهري</strong>
                                    <p>خطأ خطير يؤثر على العمل</p>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="sidebar-footer">
            <button type="button" class="btn btn-secondary" onclick="closeSidebar()">إلغاء</button>
            <button type="submit" class="btn btn-danger">
                <i class="fas fa-save me-2"></i>
                تسجيل الخطأ
            </button>
        </div>
    </form>
</div>

<!-- Sidebar: Edit Error -->
<div class="sidebar-panel" id="editErrorSidebar">
    <div class="sidebar-header">
        <h5 class="sidebar-title">
            <i class="fas fa-edit me-2"></i>
            تعديل الخطأ
        </h5>
        <button type="button" class="sidebar-close" onclick="closeSidebar()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <form id="editErrorForm" onsubmit="submitEditError(event)">
        <input type="hidden" name="error_id" id="edit_error_id">
        <div class="sidebar-body">
            <!-- عنوان الخطأ -->
            <div class="mb-3">
                <label class="form-label">عنوان الخطأ</label>
                <input type="text" name="title" id="edit_title" required class="form-control">
            </div>

            <!-- وصف الخطأ -->
            <div class="mb-3">
                <label class="form-label">وصف الخطأ</label>
                <textarea name="description" id="edit_description" required rows="4" class="form-control"></textarea>
            </div>

            <!-- تصنيف الخطأ -->
            <div class="mb-3">
                <label class="form-label">تصنيف الخطأ</label>
                <select name="error_category" id="edit_category" required class="form-select">
                    <option value="quality">جودة</option>
                    <option value="deadline">موعد نهائي</option>
                    <option value="communication">تواصل</option>
                    <option value="technical">فني</option>
                    <option value="procedural">إجرائي</option>
                    <option value="other">أخرى</option>
                </select>
            </div>

            <!-- نوع الخطأ -->
            <div class="mb-3">
                <label class="form-label">نوع الخطأ</label>
                <div class="row">
                    <div class="col-6">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="error_type" id="edit_type_normal" value="normal" required>
                            <label class="form-check-label" for="edit_type_normal">
                                عادي
                            </label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="error_type" id="edit_type_critical" value="critical" required>
                            <label class="form-check-label text-danger" for="edit_type_critical">
                                جوهري
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="sidebar-footer">
            <button type="button" class="btn btn-secondary" onclick="closeSidebar()">إلغاء</button>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-2"></i>
                حفظ التعديلات
            </button>
        </div>
    </form>
</div>

<style>
    /* Sidebar Overlay */
    .sidebar-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1040;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }

    .sidebar-overlay.active {
        opacity: 1;
        visibility: visible;
    }

    /* Sidebar Panel */
    .sidebar-panel {
        position: fixed;
        top: 0;
        right: -500px;
        width: 500px;
        height: 100vh;
        max-height: 100vh;
        background: #fff;
        box-shadow: -2px 0 20px rgba(0, 0, 0, 0.1);
        z-index: 1050;
        transition: right 0.3s ease;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .sidebar-panel.active {
        right: 0;
    }

    /* Sidebar Form */
    .sidebar-panel form {
        display: flex;
        flex-direction: column;
        height: 100%;
        flex: 1;
        min-height: 0;
    }

    /* Sidebar Header */
    .sidebar-header {
        padding: 1.5rem;
        border-bottom: 1px solid #e0e0e0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        flex-shrink: 0;
        z-index: 10;
    }

    .sidebar-title {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 600;
        color: white;
    }

    .sidebar-close {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        color: white;
        width: 35px;
        height: 35px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .sidebar-close:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: rotate(90deg);
    }

    /* Sidebar Body */
    .sidebar-body {
        flex: 1;
        padding: 1.5rem;
        overflow-y: auto;
        overflow-x: hidden;
        max-height: calc(100vh - 160px);
        min-height: 0;
        scroll-behavior: smooth;
        -webkit-overflow-scrolling: touch;

        /* إخفاء scrollbar لكن الاحتفاظ بالوظيفة */
        scrollbar-width: none;
        /* Firefox */
        -ms-overflow-style: none;
        /* IE and Edge */
    }

    /* إخفاء scrollbar في Chrome, Safari و Opera */
    .sidebar-body::-webkit-scrollbar {
        display: none;
        width: 0;
        height: 0;
    }

    /* Sidebar Footer */
    .sidebar-footer {
        padding: 1.5rem;
        border-top: 1px solid #e0e0e0;
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        background: #f8f9fa;
        flex-shrink: 0;
        position: sticky;
        bottom: 0;
        z-index: 10;
    }

    .sidebar-footer .btn {
        min-width: 120px;
        font-weight: 500;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .sidebar-panel {
            width: 100%;
            right: -100%;
        }
    }

    /* Datalist Enhanced Styling */
    .datalist-input {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 16 16' fill='%23999'%3E%3Cpath d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z'%3E%3C/path%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: left 12px center;
        background-size: 16px 16px;
        padding-left: 40px;
        transition: all 0.3s ease;
    }

    .datalist-input:focus {
        box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
        border-color: #667eea;
        transform: translateY(-1px);
    }

    .datalist-input::placeholder {
        color: #999;
    }

    .datalist-input:hover {
        border-color: #667eea;
    }

    /* Project code buttons */
    #project_code_container_create .btn-sm {
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
    }

    #project_code_container_create .btn-outline-primary:hover {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-color: #667eea;
        color: white;
    }

    #project_code_container_create .btn-outline-danger:hover {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        border-color: #f5576c;
        color: white;
    }

    /* User selected badge */
    .user-selected-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.5rem 1rem;
        margin-top: 0.5rem;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 25px;
        font-size: 0.875rem;
        animation: slideIn 0.3s ease;
    }

    .user-selected-badge i {
        margin-right: 0.5rem;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>
@endsection

@push('scripts')
<script>
    // تمرير الإحصائيات من PHP إلى JavaScript
    window.employeeErrorsStats = {
        myErrors: @json($myErrorsStats),
        allErrors: @json($allErrorsStats),
        reportedErrors: @json($reportedErrorsStats),
        criticalErrors: @json($criticalErrorsStats)
    };

    // تحديث الإحصائيات حسب التاب النشط
    function updateStats(statsType) {
        const stats = window.employeeErrorsStats[statsType];

        if (!stats || Object.keys(stats).length === 0) {
            // لو الإحصائيات فاضية، نعرض أصفار
            document.getElementById('stat-total-errors').textContent = '0';
            document.getElementById('stat-critical-errors').textContent = '0';
            document.getElementById('stat-normal-errors').textContent = '0';
            document.getElementById('stat-quality-errors').textContent = '0';
            return;
        }

        document.getElementById('stat-total-errors').textContent = stats.total_errors || 0;
        document.getElementById('stat-critical-errors').textContent = stats.critical_errors || 0;
        document.getElementById('stat-normal-errors').textContent = stats.normal_errors || 0;
        document.getElementById('stat-quality-errors').textContent = (stats.by_category && stats.by_category.quality) || 0;
    }

    // Event Listeners
    document.addEventListener('DOMContentLoaded', function() {
        // إضافة event listeners للـ tabs
        const tabs = document.querySelectorAll('[data-bs-toggle="tab"]');
        tabs.forEach(tab => {
            tab.addEventListener('shown.bs.tab', function(event) {
                const targetId = event.target.getAttribute('data-bs-target');

                // تحديد نوع الإحصائيات حسب التاب
                let statsType = 'myErrors'; // افتراضي

                if (targetId === '#all-errors') {
                    statsType = 'allErrors';
                } else if (targetId === '#my-errors') {
                    statsType = 'myErrors';
                } else if (targetId === '#reported-errors') {
                    statsType = 'reportedErrors';
                } else if (targetId === '#critical-errors') {
                    statsType = 'criticalErrors';
                }

                // تحديث الإحصائيات
                updateStats(statsType);
            });
        });

        // تحديث الإحصائيات للتاب النشط عند تحميل الصفحة
        const activeTab = document.querySelector('.nav-link.active[data-bs-toggle="tab"]');
        if (activeTab) {
            const targetId = activeTab.getAttribute('data-bs-target');
            let statsType = 'myErrors';

            if (targetId === '#all-errors') {
                statsType = 'allErrors';
            } else if (targetId === '#my-errors') {
                statsType = 'myErrors';
            } else if (targetId === '#reported-errors') {
                statsType = 'reportedErrors';
            } else if (targetId === '#critical-errors') {
                statsType = 'criticalErrors';
            }

            updateStats(statsType);
        }
        // Edit button listeners
        document.querySelectorAll('.edit-error-btn').forEach(button => {
            button.addEventListener('click', function() {
                const errorId = this.dataset.errorId;
                const errorTitle = this.dataset.errorTitle;
                const errorDescription = this.dataset.errorDescription;
                const errorCategory = this.dataset.errorCategory;
                const errorType = this.dataset.errorType;

                openEditModal(errorId, errorTitle, errorDescription, errorCategory, errorType);
            });
        });

        // Delete button listeners
        document.querySelectorAll('.delete-error-btn').forEach(button => {
            button.addEventListener('click', function() {
                const errorId = this.dataset.errorId;
                deleteError(errorId);
            });
        });
    });
    // Sidebar Functions
    function openCreateModal() {
        loadAvailableUsers();
        openSidebar('createErrorSidebar');
    }

    function openSidebar(sidebarId) {
        const sidebar = document.getElementById(sidebarId);
        const overlay = document.getElementById('sidebarOverlay');

        if (sidebar && overlay) {
            sidebar.classList.add('active');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeSidebar() {
        const sidebars = document.querySelectorAll('.sidebar-panel');
        const overlay = document.getElementById('sidebarOverlay');

        sidebars.forEach(sidebar => {
            sidebar.classList.remove('active');
        });

        if (overlay) {
            overlay.classList.remove('active');
        }

        document.body.style.overflow = '';

        // Reset forms when closing
        const createForm = document.getElementById('createErrorForm');
        const editForm = document.getElementById('editErrorForm');
        if (createForm) createForm.reset();
        if (editForm) editForm.reset();

        // Reset datalist search fields
        const userSearchInput = document.getElementById('user_name_search');
        const userIdHidden = document.getElementById('user_id_create');
        const projectCodeInput = document.getElementById('project_code_create');
        const projectDatalist = document.getElementById('projectCodesList');

        if (userSearchInput) userSearchInput.value = '';
        if (userIdHidden) userIdHidden.value = '';
        if (projectCodeInput) projectCodeInput.value = '';
        if (projectDatalist) projectDatalist.innerHTML = '';

        // Hide errorable containers
        const errorableSelectContainer = document.getElementById('errorable_select_create');
        const projectCodeContainer = document.getElementById('project_code_container_create');
        if (errorableSelectContainer) errorableSelectContainer.classList.add('d-none');
        if (projectCodeContainer) projectCodeContainer.classList.add('d-none');
    }

    // Store users data globally
    let availableUsers = [];

    function loadAvailableUsers() {
        const datalistElement = document.getElementById('usersList');
        const searchInput = document.getElementById('user_name_search');

        searchInput.placeholder = 'جاري التحميل...';
        searchInput.disabled = true;

        fetch('/employee-errors/get-available-users', {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    availableUsers = data.users;
                    datalistElement.innerHTML = '';

                    data.users.forEach(user => {
                        const option = document.createElement('option');
                        option.value = user.name;
                        option.setAttribute('data-user-id', user.id);
                        datalistElement.appendChild(option);
                    });

                    searchInput.placeholder = 'ابحث عن الموظف...';
                    searchInput.disabled = false;

                    if (data.users.length === 0) {
                        searchInput.placeholder = 'لا توجد موظفين متاحين';
                    }
                } else {
                    searchInput.placeholder = 'خطأ في التحميل';
                    searchInput.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                searchInput.placeholder = 'خطأ في التحميل';
                searchInput.disabled = false;
            });
    }

    function handleUserSelection() {
        const searchInput = document.getElementById('user_name_search');
        const hiddenInput = document.getElementById('user_id_create');

        // Find user by name
        const selectedUser = availableUsers.find(user => user.name === searchInput.value);

        if (selectedUser) {
            hiddenInput.value = selectedUser.id;

            // Load employee projects
            loadEmployeeProjects(selectedUser.id);

            // Reset errorable fields when user changes
            const errorableTypeSelect = document.getElementById('errorable_type_create');
            const errorableSelectContainer = document.getElementById('errorable_select_create');
            const errorableIdSelect = document.getElementById('errorable_id_create');

            errorableSelectContainer.classList.add('d-none');
            errorableIdSelect.innerHTML = '<option value="">اختر...</option>';

            // Reload errorables if type is already selected
            if (errorableTypeSelect.value) {
                loadErrorableOptions('create');
            }
        } else {
            hiddenInput.value = '';
            // Clear project codes list
            const projectDatalist = document.getElementById('projectCodesList');
            if (projectDatalist) projectDatalist.innerHTML = '';
        }
    }

    // Store employee projects globally
    let employeeProjects = [];

    function loadEmployeeProjects(userId) {
        const datalistElement = document.getElementById('projectCodesList');
        const projectCodeInput = document.getElementById('project_code_create');

        if (!datalistElement || !userId) return;

        datalistElement.innerHTML = '';
        employeeProjects = [];

        fetch(`/employee-errors/get-employee-projects?user_id=${userId}`, {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    employeeProjects = data.projects;

                    data.projects.forEach(project => {
                        const option = document.createElement('option');
                        option.value = project.code;
                        option.setAttribute('data-project-id', project.id);
                        option.setAttribute('data-project-name', project.name);
                        option.textContent = `${project.code} - ${project.name}`;
                        datalistElement.appendChild(option);
                    });

                    console.log(`تم تحميل ${data.projects.length} مشروع للموظف`);
                }
            })
            .catch(error => {
                console.error('Error loading employee projects:', error);
            });
    }

    function handleProjectCodeSelection() {
        const projectCodeInput = document.getElementById('project_code_create');
        const errorableTypeSelect = document.getElementById('errorable_type_create');

        // Auto-reload errorables if type is selected
        if (errorableTypeSelect && errorableTypeSelect.value) {
            // Debounce the reload
            clearTimeout(window.projectCodeTimeout);
            window.projectCodeTimeout = setTimeout(() => {
                loadErrorableOptions('create');
            }, 500);
        }
    }

    function handleErrorableTypeChange(type) {
        const selectType = document.getElementById(`errorable_type_${type}`).value;
        const projectCodeContainer = document.getElementById(`project_code_container_${type}`);
        const projectCodeInput = document.getElementById(`project_code_${type}`);

        // إظهار حقل كود المشروع فقط للمهام العادية والقوالب
        if (selectType === 'TaskUser' || selectType === 'TemplateTaskUser') {
            projectCodeContainer.classList.remove('d-none');
        } else {
            projectCodeContainer.classList.add('d-none');
            if (projectCodeInput) {
                projectCodeInput.value = '';
            }
        }

        // تحميل الخيارات
        loadErrorableOptions(type);
    }

    function clearProjectCodeFilter(type) {
        const projectCodeInput = document.getElementById(`project_code_${type}`);
        if (projectCodeInput) {
            projectCodeInput.value = '';
            loadErrorableOptions(type);
        }
    }

    function loadErrorableOptions(type) {
        const selectType = document.getElementById(`errorable_type_${type}`).value;
        const userId = document.getElementById(`user_id_${type}`).value;
        const selectContainer = document.getElementById(`errorable_select_${type}`);
        const selectElement = document.getElementById(`errorable_id_${type}`);
        const projectCodeInput = document.getElementById(`project_code_${type}`);

        if (!selectType) {
            selectContainer.classList.add('d-none');
            return;
        }

        if (!userId) {
            Toast.error('اختر الموظف أولاً');
            return;
        }

        selectContainer.classList.remove('d-none');
        selectElement.innerHTML = '<option value="">جاري التحميل...</option>';

        // بناء URL مع كود المشروع إذا كان موجوداً
        let url = `/employee-errors/get-errorables?type=${selectType}&user_id=${userId}`;

        // إضافة كود المشروع للمهام العادية والقوالب فقط
        if ((selectType === 'TaskUser' || selectType === 'TemplateTaskUser') && projectCodeInput && projectCodeInput.value.trim()) {
            url += `&project_code=${encodeURIComponent(projectCodeInput.value.trim())}`;
        }

        // AJAX لجلب المهام/المشاريع بناءً على الموظف وكود المشروع
        fetch(url, {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    selectElement.innerHTML = '<option value="">اختر...</option>';
                    data.errorables.forEach(item => {
                        const option = document.createElement('option');
                        option.value = item.id;
                        option.textContent = item.name;
                        selectElement.appendChild(option);
                    });

                    if (data.errorables.length === 0) {
                        const noDataMessage = projectCodeInput && projectCodeInput.value.trim() ?
                            'لا توجد مهام مرتبطة بهذا المشروع' :
                            'لا توجد بيانات';
                        selectElement.innerHTML = `<option value="">${noDataMessage}</option>`;
                    }
                } else {
                    Toast.error('حدث خطأ أثناء جلب البيانات');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                selectElement.innerHTML = '<option value="">خطأ في التحميل</option>';
            });
    }

    function submitCreateError(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        const errorableType = data.errorable_type;
        const errorableId = data.errorable_id;

        let url = '';
        if (errorableType === 'TaskUser') {
            url = `/employee-errors/task/${errorableId}`;
        } else if (errorableType === 'TemplateTaskUser') {
            url = `/employee-errors/template-task/${errorableId}`;
        } else if (errorableType === 'ProjectServiceUser') {
            url = `/employee-errors/project/${errorableId}`;
        }

        fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    title: data.title,
                    description: data.description,
                    error_category: data.error_category,
                    error_type: data.error_type,
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Toast.success('تم تسجيل الخطأ بنجاح');
                    closeSidebar();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    Toast.error(data.message || 'حدث خطأ أثناء التسجيل');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Toast.error('حدث خطأ أثناء التسجيل');
            });
    }

    // Edit Sidebar Functions
    function openEditModal(id, title, description, category, type) {
        document.getElementById('edit_error_id').value = id;
        document.getElementById('edit_title').value = title;
        document.getElementById('edit_description').value = description;
        document.getElementById('edit_category').value = category;

        if (type === 'normal') {
            document.getElementById('edit_type_normal').checked = true;
        } else {
            document.getElementById('edit_type_critical').checked = true;
        }

        openSidebar('editErrorSidebar');
    }

    function submitEditError(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        const errorId = data.error_id;

        fetch(`/employee-errors/${errorId}`, {
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    title: data.title,
                    description: data.description,
                    error_category: data.error_category,
                    error_type: data.error_type,
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Toast.success('تم تحديث الخطأ بنجاح');
                    closeSidebar();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    Toast.error(data.message || 'حدث خطأ أثناء التحديث');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Toast.error('حدث خطأ أثناء التحديث');
            });
    }

    // Delete Function
    function deleteError(errorId) {
        if (!confirm('هل أنت متأكد من حذف هذا الخطأ؟')) {
            return;
        }

        fetch(`/employee-errors/${errorId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Toast.success('تم حذف الخطأ بنجاح');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    Toast.error(data.message || 'حدث خطأ أثناء الحذف');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Toast.error('حدث خطأ أثناء الحذف');
            });
    }

    // ✅ إضافة event listener لفلتر كود المشروع
    document.addEventListener('DOMContentLoaded', function() {
        const projectCodeInput = document.getElementById('projectCodeInput');
        if (projectCodeInput) {
            // لما المستخدم يختار من القائمة
            projectCodeInput.addEventListener('input', function(e) {
                // التحقق من أن القيمة موجودة في الـ datalist
                const datalist = document.getElementById('projectCodesList');
                const options = datalist.querySelectorAll('option');
                const value = this.value.trim();

                for (let option of options) {
                    if (option.value === value) {
                        // القيمة موجودة في القائمة، نعمل submit
                        this.form.submit();
                        break;
                    }
                }
            });

            // لما المستخدم يضغط Enter
            projectCodeInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.form.submit();
                }
            });
        }
    });
</script>
@endpush