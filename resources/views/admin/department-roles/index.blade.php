@extends('layouts.app')

@section('title', 'إدارة أدوار الأقسام')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="hero-section mb-4">
                <div class="hero-content">
                    <div class="hero-text">
                        <h1 class="hero-title">
                            <i class="fas fa-sitemap"></i>
                            إدارة أدوار الأقسام
                        </h1>
                        <p class="hero-subtitle">ربط الأقسام بالأدوار المتاحة بطريقة ذكية ومرنة</p>
                    </div>
                    <div class="hero-actions">
                        <a href="{{ route('department-roles.manage-hierarchy') }}" class="btn btn-warning btn-lg me-2">
                            <i class="fas fa-sort-amount-up"></i>
                            إدارة ترتيب الأدوار
                        </a>
                        <a href="{{ route('department-roles.create') }}" class="btn btn-primary btn-lg">
                            <i class="fas fa-plus-circle"></i>
                            ربط أدوار جديدة
                        </a>
                    </div>
                </div>
            </div>

            <!-- Success/Error Messages -->
            @if(session('success'))
                <div class="alert alert-success alert-modern fade show" role="alert">
                    <div class="alert-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="alert-content">
                        <strong>تم بنجاح!</strong>
                        {{ session('success') }}
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-modern fade show" role="alert">
                    <div class="alert-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="alert-content">
                        <strong>خطأ!</strong>
                        {{ session('error') }}
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Statistics Cards -->
            <div class="stats-grid mb-4">
                <div class="stat-card stat-primary">
                    <div class="stat-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="stat-content">
                        <h3>{{ $departments->count() }}</h3>
                        <p>إجمالي الأقسام</p>
                    </div>
                </div>
                <div class="stat-card stat-success">
                    <div class="stat-icon">
                        <i class="fas fa-user-tag"></i>
                    </div>
                    <div class="stat-content">
                        <h3>{{ $roles->count() }}</h3>
                        <p>إجمالي الأدوار</p>
                    </div>
                </div>
                <div class="stat-card stat-warning">
                    <div class="stat-icon">
                        <i class="fas fa-link"></i>
                    </div>
                    <div class="stat-content">
                        <h3>{{ $departmentRoles->flatten()->count() }}</h3>
                        <p>الروابط النشطة</p>
                    </div>
                </div>
                <div class="stat-card stat-info">
                    <div class="stat-icon">
                        <i class="fas fa-check-double"></i>
                    </div>
                    <div class="stat-content">
                        <h3>{{ $departmentRoles->count() }}</h3>
                        <p>أقسام مربوطة</p>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="main-card">
                <div class="card-header-modern">
                    <div class="header-content">
                        <h2 class="card-title">
                            <i class="fas fa-table"></i>
                            الأقسام والأدوار
                        </h2>
                        <p class="card-subtitle">إدارة ربط الأقسام مع الأدوار المختلفة</p>
                    </div>
                </div>

                <div class="card-body-modern">
                    @if($departmentRoles->isEmpty())
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-inbox"></i>
                            </div>
                            <h3>لا توجد روابط</h3>
                            <p>لم يتم ربط أي قسم بالأدوار بعد</p>
                            <a href="{{ route('department-roles.create') }}" class="btn btn-primary btn-lg">
                                <i class="fas fa-plus"></i>
                                ابدأ الربط الآن
                            </a>
                        </div>
                    @else
                        <div class="departments-grid">
                            @foreach($departmentRoles as $departmentName => $roles)
                                <div class="department-card">
                                    <div class="department-header">
                                        <div class="department-info">
                                            <h4 class="department-name">
                                                <i class="fas fa-building"></i>
                                                {{ $departmentName }}
                                            </h4>
                                            <span class="department-count">
                                                {{ $roles->count() }} {{ $roles->count() == 1 ? 'دور' : 'أدوار' }}
                                            </span>
                                        </div>
                                        <div class="department-actions">
                                            <a href="{{ route('department-roles.show', ['department_role' => $departmentName]) }}" class="btn btn-sm btn-outline-primary" title="عرض التفاصيل">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('department-roles.edit', ['department_role' => $departmentName]) }}" class="btn btn-sm btn-outline-warning" title="تعديل أدوار القسم">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </div>
                                    </div>

                                    <div class="department-roles" id="dept-{{ Str::slug($departmentName) }}">
                                        @foreach($roles as $role)
                                            <div class="role-item">
                                                <div class="role-info">
                                                    <span class="role-badge">
                                                        <i class="fas fa-user-tag"></i>
                                                        {{ $role->role->name }}
                                                    </span>
                                                </div>
                                                <div class="role-actions">
                                                    <span class="hierarchy-level-badge" title="المستوى الهرمي">
                                                        <i class="fas fa-layer-group"></i>
                                                        {{ $role->hierarchy_level ?? 1 }}
                                                    </span>
                                                    <button type="button"
                                                            class="btn btn-sm btn-outline-danger delete-role-btn"
                                                            data-id="{{ $role->id }}"
                                                            data-department="{{ $departmentName }}"
                                                            data-role="{{ $role->role->name }}"
                                                            title="حذف الربط">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- Departments without Roles -->
            @php
                $departmentsWithoutRoles = $departments->diff($departmentRoles->keys());
            @endphp

            @if($departmentsWithoutRoles->isNotEmpty())
                <div class="alert-card mt-4">
                    <div class="alert-header">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>أقسام تحتاج ربط ({{ $departmentsWithoutRoles->count() }})</span>
                    </div>
                    <div class="alert-body">
                        <div class="departments-list">
                            @foreach($departmentsWithoutRoles as $department)
                                <div class="department-item">
                                    <span class="department-name">{{ $department }}</span>
                                    <a href="{{ route('department-roles.create', ['department' => $department]) }}"
                                       class="btn btn-sm btn-primary">
                                        <i class="fas fa-plus"></i>
                                        ربط الآن
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-modern">
            <div class="modal-header">
                <div class="modal-icon">
                    <i class="fas fa-trash-alt"></i>
                </div>
                <h5 class="modal-title">تأكيد الحذف</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>هل أنت متأكد من حذف ربط الدور <strong id="role-name"></strong> مع القسم <strong id="department-name"></strong>؟</p>
                <div class="warning-note">
                    <i class="fas fa-info-circle"></i>
                    هذا الإجراء لا يمكن التراجع عنه
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i>
                    إلغاء
                </button>
                <form id="delete-form" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i>
                        حذف نهائي
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Delete confirmation
document.querySelectorAll('.delete-role-btn').forEach(button => {
    button.addEventListener('click', function() {
        const id = this.dataset.id;
        const department = this.dataset.department;
        const role = this.dataset.role;

        document.getElementById('department-name').textContent = department;
        document.getElementById('role-name').textContent = role;
        document.getElementById('delete-form').action = `{{ route('department-roles.index') }}/${id}`;

        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    });
});



// Add animation on page load
document.addEventListener('DOMContentLoaded', function() {
    // Animate stats cards
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach((card, index) => {
        setTimeout(() => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'all 0.5s ease';

            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 100);
        }, index * 100);
    });

    // Animate department cards
    const deptCards = document.querySelectorAll('.department-card');
    deptCards.forEach((card, index) => {
        setTimeout(() => {
            card.style.opacity = '0';
            card.style.transform = 'translateX(-20px)';
            card.style.transition = 'all 0.6s ease';

            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateX(0)';
            }, 100);
        }, index * 150);
    });
});
</script>
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/department-roles.css') }}">
@endpush
