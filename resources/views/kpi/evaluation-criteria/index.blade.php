@extends('layouts.app')

@section('title', 'إدارة بنود التقييم')

@push('styles')
    <link href="{{ asset('css/evaluation-criteria-modern.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="container-fluid evaluation-container">
    <div class="row">
        <div class="col-12">
            <!-- 🎯 Header Section -->
            <div class="modern-card mb-5 fade-in-up">
                <div class="text-center p-5" style="background: linear-gradient(135deg, #ffffff 0%, #f8f9ff 100%); border-radius: 20px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);">
                    <div class="d-inline-block p-3 rounded-circle mb-4 floating" style="background: linear-gradient(135deg, #fa709a, #fee140); box-shadow: 0 8px 20px rgba(250, 112, 154, 0.3);">
                        <i class="fas fa-list-check fa-3x text-white"></i>
                    </div>
                    <h1 class="display-5 fw-bold mb-3" style="color: #2c3e50;">📋 إدارة بنود التقييم</h1>
                    <p class="lead mb-4" style="color: #6c757d;">
                        نظام شامل لإدارة وتنظيم جميع بنود التقييم
                    </p>

                    <div class="d-flex flex-wrap justify-content-center gap-2">
                        <div class="btn-group">
                            <a href="{{ route('evaluation-criteria.select-role') }}" class="btn btn-modern btn-primary-modern">
                                <i class="fas fa-plus me-2"></i>إضافة بند جديد
                            </a>
                            <button type="button" class="btn btn-modern btn-primary-modern dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
                                <span class="visually-hidden">تبديل القائمة المنسدلة</span>
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="{{ route('evaluation-criteria.select-role') }}">
                                        <i class="fas fa-magic text-primary me-2"></i>
                                        اختيار الدور أولاً
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('evaluation-criteria.create') }}">
                                        <i class="fas fa-plus text-secondary me-2"></i>
                                        إضافة مباشرة
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 📊 Main Content Card -->
            <div class="modern-card slide-in-right">
                <div class="modern-card-header">
                    <h3 class="mb-0">
                        <i class="fas fa-table me-2"></i>
                        قائمة البنود
                    </h3>
                </div>
                <div class="modern-card-body">
                    <!-- 🔍 Filters Section -->
                    <div class="p-4 rounded-4 mb-4" style="background: linear-gradient(135deg, rgba(168, 237, 234, 0.1), rgba(254, 214, 227, 0.1));">
                        <h6 class="fw-bold mb-3">
                            <i class="fas fa-filter me-2"></i>
                            🔍 أدوات البحث والفلترة
                        </h6>
                        <div class="row g-3">
                            <div class="col-lg-3">
                                <form method="GET" action="{{ route('evaluation-criteria.index') }}">
                                    <div class="form-floating-modern">
                                        <select name="role_id" class="form-select-modern" onchange="this.form.submit()">
                                            <option value="">جميع الأدوار</option>
                                            @foreach($roles as $role)
                                                <option value="{{ $role->id }}" {{ request('role_id') == $role->id ? 'selected' : '' }}>
                                                    {{ $role->display_name ?? $role->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <label>👤 فلترة حسب الدور</label>
                                    </div>
                                </form>
                            </div>
                            <div class="col-lg-3">
                                <form method="GET" action="{{ route('evaluation-criteria.index') }}">
                                    <div class="form-floating-modern">
                                        <select name="criteria_type" class="form-select-modern" onchange="this.form.submit()">
                                            <option value="">جميع الأنواع</option>
                                            <option value="positive" {{ request('criteria_type') == 'positive' ? 'selected' : '' }}>✅ إيجابي</option>
                                            <option value="negative" {{ request('criteria_type') == 'negative' ? 'selected' : '' }}>❌ سلبي</option>
                                            <option value="bonus" {{ request('criteria_type') == 'bonus' ? 'selected' : '' }}>🌟 بونص</option>
                                        </select>
                                        <label>🏷️ فلترة حسب النوع</label>
                                    </div>
                                </form>
                            </div>
                            <div class="col-lg-3">
                                <form method="GET" action="{{ route('evaluation-criteria.index') }}">
                                    <div class="form-floating-modern">
                                        <select name="evaluation_period" class="form-select-modern" onchange="this.form.submit()">
                                            <option value="">جميع الفترات</option>
                                            <option value="monthly" {{ request('evaluation_period') == 'monthly' ? 'selected' : '' }}>📅 شهري</option>
                                            <option value="bi_weekly" {{ request('evaluation_period') == 'bi_weekly' ? 'selected' : '' }}>⚡ نصف شهري</option>
                                        </select>
                                        <label>📅 فلترة حسب فترة التقييم</label>
                                    </div>
                                </form>
                            </div>
                            <div class="col-lg-3 d-flex align-items-end">
                                <a href="{{ route('evaluation-criteria.index') }}" class="btn btn-modern btn-warning-modern w-100">
                                    <i class="fas fa-undo me-2"></i>
                                    إعادة تعيين الفلاتر
                                </a>
                            </div>
                        </div>
                    </div>

                    @if($roles->count() > 0)
                        <!-- 📊 Modern Table -->
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr style="background: linear-gradient(135deg, #667eea, #764ba2); color: white;">
                                        <th class="border-0">👤 الدور</th>
                                        <th class="border-0">📊 عدد البنود</th>
                                        <th class="border-0">📝 البنود</th>
                                        <th class="border-0">⚙️ الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($roles as $role)
                                        @php
                                            $roleCriteria = $criteria->where('role_id', $role->id);
                                            $roleCriteria = $roleCriteria->values(); // إعادة ترقيم المصفوفة
                                        @endphp
                                        <tr>
                                            <td class="align-middle">
                                                <div class="d-flex align-items-center">
                                                    <div class="me-3">
                                                        <div class="d-inline-block p-2 rounded-circle" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                                                            <i class="fas fa-user-tie text-white"></i>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <h6 class="fw-bold mb-1">{{ $role->display_name ?? $role->name }}</h6>
                                                        <small class="text-muted">ID: {{ $role->id }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="align-middle">
                                                <div class="text-center">
                                                    <span class="badge badge-modern badge-primary-modern fs-4 px-3 py-2">
                                                        {{ $role->criteria_count }}
                                                    </span>
                                                    <div class="mt-1">
                                                        <small class="text-muted">بند تقييم</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="align-middle">
                                                @if($roleCriteria->count() > 0)
                                                    <div class="criteria-list">
                                                        @foreach($roleCriteria->take(3) as $index => $item)
                                                            <div class="d-flex align-items-center mb-2 p-2 rounded" style="background: rgba(102, 126, 234, 0.1);">
                                                                <div class="me-2">
                                                                    <span class="badge badge-modern badge-primary-modern">{{ $index + 1 }}</span>
                                                                </div>
                                                                <div class="me-2">
                                                @switch($item->criteria_type)
                                                    @case('positive')
                                                                            <span class="badge badge-modern badge-success-modern">✅</span>
                                                        @break
                                                    @case('negative')
                                                                            <span class="badge badge-modern" style="background: var(--danger-gradient);">❌</span>
                                                        @break
                                                    @case('bonus')
                                                                            <span class="badge badge-modern badge-warning-modern">🌟</span>
                                                        @break
                                                @endswitch
                                                                </div>
                                                                <div class="flex-grow-1">
                                                                    <h6 class="mb-0 fw-bold">{{ $item->criteria_name }}</h6>
                                                                    <small class="text-muted">{{ $item->max_points }} نقطة</small>
                                                                </div>
                                                                <div class="ms-2">
                                                                    @if($item->evaluate_per_project)
                                                                        <span class="badge badge-modern badge-primary-modern">🚀 مشروع</span>
                                                                    @endif
                                                @if($item->is_active)
                                                                        <span class="badge badge-modern badge-success-modern">نشط</span>
                                                                    @else
                                                                        <span class="badge badge-modern" style="background: #6c757d;">غير نشط</span>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                        @if($roleCriteria->count() > 3)
                                                            <div class="text-center">
                                                                <small class="text-muted">و {{ $roleCriteria->count() - 3 }} بنود أخرى...</small>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @else
                                                    <div class="text-center text-muted">
                                                        <i class="fas fa-inbox fa-2x mb-2"></i>
                                                        <p class="mb-0">لا توجد بنود</p>
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="align-middle">
                                                <div class="d-flex gap-1" role="group">
                                                    <a href="{{ route('evaluation-criteria.create', ['role_id' => $role->id]) }}"
                                                       class="btn btn-modern btn-primary-modern btn-sm" title="إضافة بند جديد">
                                                        <i class="fas fa-plus"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-modern btn-info-modern btn-sm"
                                                            onclick="toggleCriteriaDetails({{ $role->id }})" title="عرض التفاصيل">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <a href="{{ route('evaluation-criteria.index', ['role_id' => $role->id]) }}"
                                                       class="btn btn-modern btn-success-modern btn-sm" title="فلترة البنود">
                                                        <i class="fas fa-filter"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- تفاصيل البنود (مخفية افتراضياً) -->
                                        <tr id="criteria-details-{{ $role->id }}" class="criteria-details-row" style="display: none;">
                                            <td colspan="4" class="p-0">
                                                <div class="criteria-details-content p-4" style="background: linear-gradient(135deg, #f8f9ff 0%, #ffffff 100%);">
                                                    <div class="d-flex align-items-center mb-4">
                                                        <div class="me-3">
                                                            <div class="d-inline-block p-3 rounded-circle" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                                                                <i class="fas fa-list-check fa-2x text-white"></i>
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <h5 class="fw-bold mb-1" style="color: #2c3e50;">📋 تفاصيل البنود للدور: {{ $role->display_name ?? $role->name }}</h5>
                                                            <p class="text-muted mb-0">إجمالي {{ $roleCriteria->count() }} بند تقييم</p>
                                                        </div>
                                                    </div>

                                                    <div class="row g-4">
                                                        @foreach($roleCriteria as $index => $item)
                                                            <div class="col-md-6 col-lg-4">
                                                                <div class="cv-style-card">
                                                                    <div class="cv-card-header">
                                                                        <div class="d-flex justify-content-between align-items-start">
                                                                            <div class="d-flex align-items-center">
                                                                                <div class="cv-number-badge">{{ $index + 1 }}</div>
                                                                                <div class="ms-3">
                                                                                    <h6 class="cv-title">{{ $item->criteria_name }}</h6>
                                                                                    @if($item->criteria_description)
                                                                                        <p class="cv-subtitle">{{ Str::limit($item->criteria_description, 50) }}</p>
                                                                                    @endif
                                                                                </div>
                                                                            </div>
                                                                            @switch($item->criteria_type)
                                                                                @case('positive')
                                                                                    <span class="cv-type-badge cv-positive">✅ إيجابي</span>
                                                                                    @break
                                                                                @case('negative')
                                                                                    <span class="cv-type-badge cv-negative">❌ سلبي</span>
                                                                                    @break
                                                                                @case('bonus')
                                                                                    <span class="cv-type-badge cv-bonus">🌟 بونص</span>
                                                                                    @break
                                                                            @endswitch
                                                                        </div>
                                                                    </div>

                                                                    <div class="cv-card-body">
                                                                        <div class="cv-stats-row">
                                                                            <div class="cv-stat-item">
                                                                                <i class="fas fa-star text-warning"></i>
                                                                                <span class="cv-stat-value">{{ $item->max_points }}</span>
                                                                                <span class="cv-stat-label">نقطة</span>
                                                                            </div>
                                                                            @if($item->category)
                                                                            <div class="cv-stat-item">
                                                                                <i class="fas fa-folder text-info"></i>
                                                                                <span class="cv-stat-label">{{ $item->category }}</span>
                                                                            </div>
                                                                            @endif
                                                                            <div class="cv-stat-item">
                                                                                @if($item->is_active)
                                                                                    <i class="fas fa-check-circle text-success"></i>
                                                                                    <span class="cv-stat-label">نشط</span>
                                                                                @else
                                                                                    <i class="fas fa-pause-circle text-secondary"></i>
                                                                                    <span class="cv-stat-label">غير نشط</span>
                                                                                @endif
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    <div class="cv-card-footer">
                                                                        <div class="cv-actions">
                                                                            <a href="{{ route('evaluation-criteria.show', $item) }}" class="cv-action-btn cv-view" title="عرض">
                                                                                <i class="fas fa-eye"></i>
                                                                            </a>
                                                                            <a href="{{ route('evaluation-criteria.edit', $item) }}" class="cv-action-btn cv-edit" title="تعديل">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                                            <form method="POST" action="{{ route('evaluation-criteria.destroy', $item) }}" style="display: inline;" onsubmit="return confirm('هل أنت متأكد من حذف هذا البند؟')">
                                                        @csrf
                                                        @method('DELETE')
                                                                                <button type="submit" class="cv-action-btn cv-delete" title="حذف">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <!-- 😢 Empty State -->
                        <div class="text-center py-5">
                            <div class="d-inline-block p-4 rounded-circle mb-4" style="background: linear-gradient(135deg, #a8edea, #fed6e3);">
                                <i class="fas fa-inbox fa-3x text-white"></i>
                            </div>
                            <h4 class="fw-bold mb-3">🤷‍♂️ لا توجد أدوار</h4>
                            <p class="text-muted mb-4">لم يتم العثور على أي أدوار. تأكد من وجود أدوار في النظام.</p>
                            <div class="d-flex justify-content-center gap-2 flex-wrap">
                                <a href="{{ route('evaluation-criteria.select-role') }}" class="btn btn-modern btn-primary-modern">
                                    <i class="fas fa-magic me-2"></i>
                                    اختيار الدور وإضافة بنود
                                </a>
                                <a href="{{ route('evaluation-criteria.create') }}" class="btn btn-modern btn-success-modern">
                                    <i class="fas fa-plus me-2"></i>
                                    إضافة مباشرة
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        // 🔍 وظيفة تبديل تفاصيل البنود
        function toggleCriteriaDetails(roleId) {
            const detailsRow = document.getElementById('criteria-details-' + roleId);
            const button = event.target.closest('button');
            const icon = button.querySelector('i');

            if (detailsRow.style.display === 'none' || detailsRow.style.display === '') {
                detailsRow.style.display = 'table-row';
                icon.className = 'fas fa-eye-slash';
                button.title = 'إخفاء التفاصيل';
            } else {
                detailsRow.style.display = 'none';
                icon.className = 'fas fa-eye';
                button.title = 'عرض التفاصيل';
            }
        }
    </script>
@endpush
@endsection
