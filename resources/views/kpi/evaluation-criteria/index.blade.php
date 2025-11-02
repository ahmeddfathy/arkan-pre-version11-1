@extends('layouts.app')

@section('title', 'إدارة بنود التقييم')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/kpi/evaluation-criteria-index.css') }}">
@endpush

@section('content')
<div class="simple-container">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1>📋 بنود التقييم</h1>
            <p>إدارة وتنظيم بنود تقييم الأداء للموظفين</p>
            @if(request('role_id'))
            @php $selectedRole = $roles->first(); @endphp
            @if($selectedRole)
            <div style="margin-top: 1rem;">
                <a href="{{ route('evaluation-criteria.index') }}" class="services-btn" style="background: rgba(255,255,255,0.3); border: 1px solid white;">
                    <i class="fas fa-arrow-right ml-1"></i>
                    العودة للقائمة الكاملة
                </a>
            </div>
            @endif
            @endif
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

        <!-- Quick Actions Section -->
        <div class="filters-section">
            <div class="filters-row">
                <div class="filter-group">
                    <a href="{{ route('evaluation-criteria.select-role') }}" class="search-btn" style="width: 100%; text-decoration: none; text-align: center;">
                        <i class="fas fa-plus-circle ml-1"></i>
                        إضافة بند جديد
                    </a>
                </div>
                <div class="filter-group">
                    <a href="{{ route('evaluation-criteria.create') }}" class="clear-filters-btn" style="width: 100%; text-decoration: none; text-align: center; background: linear-gradient(135deg, #667eea, #764ba2);">
                        <i class="fas fa-plus ml-1"></i>
                        إضافة مباشرة
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistics Row -->
        @if($roles->count() > 0)
        @php
        $totalCriteria = 0;
        $activeCriteria = 0;
        foreach($roles as $role) {
        $totalCriteria += $role->criteria_count;
        $roleCriteria = $criteria->where('role_id', $role->id);
        $activeCriteria += $roleCriteria->where('is_active', true)->count();
        }
        @endphp
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-number">{{ $roles->count() }}</div>
                <div class="stat-label">إجمالي الأدوار</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $totalCriteria }}</div>
                <div class="stat-label">إجمالي البنود</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $activeCriteria }}</div>
                <div class="stat-label">البنود النشطة</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $totalCriteria - $activeCriteria }}</div>
                <div class="stat-label">البنود المعطلة</div>
            </div>
        </div>
        @endif

        <!-- Criteria Table -->
        <div class="projects-table-container">
            <div class="table-header">
                <h2>📋 قائمة البنود</h2>
            </div>

            @if($roles->count() > 0)
            <table class="projects-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الدور</th>
                        <th>عدد البنود</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($roles as $index => $role)
                    @php
                    $roleCriteria = $criteria->where('role_id', $role->id);
                    $roleCriteria = $roleCriteria->values();
                    @endphp
                    <tr class="project-row">
                        <td>
                            <div class="project-avatar">
                                <i class="fas fa-user-tie"></i>
                            </div>
                        </td>
                        <td>
                            <div class="project-info">
                                <div class="project-details" style="width: 100%;">
                                    <h4>{{ $role->display_name ?? $role->name }}</h4>
                                    @if($roleCriteria->count() > 0)
                                    <p style="color: #6b7280; margin-top: 0.5rem;">
                                        <strong>البنود:</strong>
                                        @foreach($roleCriteria->take(3) as $idx => $item)
                                        <span style="margin-left: 0.5rem;">
                                            {{ $idx + 1 }}. {{ Str::limit($item->criteria_name, 30) }}
                                            @if($item->criteria_type == 'positive')
                                            <span class="status-badge" style="background: #10b981;">✅</span>
                                            @elseif($item->criteria_type == 'negative')
                                            <span class="status-badge" style="background: #ef4444;">❌</span>
                                            @elseif($item->criteria_type == 'bonus')
                                            <span class="status-badge" style="background: #f59e0b;">🌟</span>
                                            @endif
                                        </span>
                                        @endforeach
                                        @if($roleCriteria->count() > 3)
                                        <span class="status-badge">و {{ $roleCriteria->count() - 3 }} أخرى...</span>
                                        @endif
                                    </p>
                                    @else
                                    <p style="color: #9ca3af; font-style: italic;">لا توجد بنود تقييم</p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <div style="text-align: center;">
                                @if($role->criteria_count > 0)
                                <span class="status-badge status-completed">
                                    <i class="fas fa-check-circle ml-1"></i>
                                    {{ $role->criteria_count }} بند
                                </span>
                                @else
                                <span class="status-badge status-cancelled">
                                    <i class="fas fa-inbox ml-1"></i>
                                    فارغ
                                </span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
                                <a href="{{ route('evaluation-criteria.create', ['role_id' => $role->id]) }}"
                                    class="services-btn"
                                    style="background: linear-gradient(135deg, #10b981, #059669);"
                                    title="إضافة بند جديد">
                                    <i class="fas fa-plus"></i>
                                    إضافة
                                </a>
                                @if($role->criteria_count > 0)
                                <a href="{{ route('evaluation-criteria.index', ['role_id' => $role->id]) }}"
                                    class="services-btn"
                                    style="background: linear-gradient(135deg, #3b82f6, #2563eb);"
                                    title="عرض البنود">
                                    <i class="fas fa-eye"></i>
                                    عرض
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <tr>
                <td colspan="4" class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h4>لا توجد أدوار</h4>
                    <p>لم يتم العثور على أي أدوار في النظام. تأكد من وجود أدوار في النظام.</p>
                    <a href="{{ route('evaluation-criteria.select-role') }}" class="services-btn" style="margin-top: 1rem;">
                        <i class="fas fa-plus-circle ml-1"></i>
                        إضافة بند جديد
                    </a>
                </td>
            </tr>
            @endif
        </div>

        <!-- Display Criteria Details when role_id is selected -->
        @if(request('role_id') && $criteria->count() > 0)
        <div class="projects-table-container" style="margin-top: 2rem;">
            <div class="table-header">
                <h2>📋 تفاصيل البنود</h2>
            </div>
            <div style="padding: 2rem;">
                <div class="row g-4">
                    @foreach($criteria as $itemIndex => $item)
                    <div class="col-md-6 col-lg-4">
                        <div class="service-item">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="d-flex align-items-center">
                                    <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.1rem; margin-left: 1rem;">
                                        {{ $itemIndex + 1 }}
                                    </div>
                                    <div>
                                        <h6 class="service-name mb-0">{{ Str::limit($item->criteria_name, 40) }}</h6>
                                        @if($item->criteria_description)
                                        <p style="color: #6b7280; font-size: 0.85rem; margin: 0;">{{ Str::limit($item->criteria_description, 50) }}</p>
                                        @endif
                                    </div>
                                </div>
                                @switch($item->criteria_type)
                                @case('positive')
                                <span class="status-badge status-completed">✅ إيجابي</span>
                                @break
                                @case('negative')
                                <span class="status-badge" style="background: #ef4444;">❌ سلبي</span>
                                @break
                                @case('bonus')
                                <span class="status-badge" style="background: #f59e0b;">🌟 بونص</span>
                                @break
                                @endswitch
                            </div>

                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <i class="fas fa-star" style="color: #f59e0b;"></i>
                                    <span style="font-weight: 600;">{{ $item->max_points }} نقطة</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    @if($item->is_active)
                                    <i class="fas fa-check-circle" style="color: #10b981;"></i>
                                    <span style="font-size: 0.85rem; color: #10b981;">نشط</span>
                                    @else
                                    <i class="fas fa-pause-circle" style="color: #6b7280;"></i>
                                    <span style="font-size: 0.85rem; color: #6b7280;">غير نشط</span>
                                    @endif
                                </div>
                            </div>

                            <div class="d-flex gap-2 justify-content-end">
                                <a href="{{ route('evaluation-criteria.show', $item) }}"
                                    class="services-btn"
                                    style="background: linear-gradient(135deg, #3b82f6, #2563eb); padding: 0.5rem 1rem; font-size: 0.9rem;"
                                    title="عرض">
                                    <i class="fas fa-eye"></i>
                                    عرض
                                </a>
                                <a href="{{ route('evaluation-criteria.edit', $item) }}"
                                    class="services-btn"
                                    style="background: linear-gradient(135deg, #8b5cf6, #7c3aed); padding: 0.5rem 1rem; font-size: 0.9rem;"
                                    title="تعديل">
                                    <i class="fas fa-edit"></i>
                                    تعديل
                                </a>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        @if(request('role_id') && $criteria->count() == 0)
        <div class="projects-table-container" style="margin-top: 2rem;">
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h4>لا توجد بنود</h4>
                <p>لم يتم إضافة أي بنود تقييم لهذا الدور بعد.</p>
                @php $selectedRole = $roles->first(); @endphp
                @if($selectedRole)
                <a href="{{ route('evaluation-criteria.create', ['role_id' => $selectedRole->id]) }}" class="services-btn" style="margin-top: 1rem;">
                    <i class="fas fa-plus-circle ml-1"></i>
                    إضافة بند جديد
                </a>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>
@endsection