@extends('layouts.app')

@section('title', 'تفاصيل قسم ' . $departmentName)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="department-header">
                <div class="header-content">
                    <div class="header-info">
                        <div class="department-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="department-details">
                            <h1 class="department-title">{{ $departmentName }}</h1>
                            <div class="department-stats">
                                <span class="stat-item">
                                    <i class="fas fa-user-tag"></i>
                                    {{ $roles->count() }} {{ $roles->count() == 1 ? 'دور' : 'أدوار' }}
                                </span>
                                <span class="stat-item">
                                    <i class="fas fa-users"></i>
                                    {{ $users->count() }} {{ $users->count() == 1 ? 'موظف' : 'موظفين' }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="header-actions">
                        <a href="{{ route('department-roles.index') }}" class="btn btn-outline-light">
                            <i class="fas fa-arrow-left"></i>
                            العودة للقائمة
                        </a>
                        <a href="{{ route('department-roles.create', ['department' => $departmentName]) }}" class="btn btn-light">
                            <i class="fas fa-plus"></i>
                            إضافة أدوار
                        </a>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Department Roles -->
                <div class="col-lg-6 mb-4">
                    <div class="info-card">
                        <div class="card-header-modern">
                            <div class="header-icon">
                                <i class="fas fa-user-tag"></i>
                            </div>
                            <div class="header-content">
                                <h3>الأدوار المربوطة</h3>
                                <p>الأدوار المتاحة لهذا القسم</p>
                            </div>
                        </div>

                        <div class="card-body-modern">
                            @if($roles->isEmpty())
                                <div class="empty-state-small">
                                    <i class="fas fa-user-tag"></i>
                                    <h4>لا توجد أدوار</h4>
                                    <p>لم يتم ربط أي أدوار بهذا القسم بعد</p>
                                    <a href="{{ route('department-roles.create', ['department' => $departmentName]) }}" class="btn btn-primary">
                                        <i class="fas fa-plus"></i>
                                        إضافة أدوار
                                    </a>
                                </div>
                            @else
                                <div class="roles-list">
                                    @foreach($roles as $role)
                                        <div class="role-item-detailed">
                                            <div class="role-info">
                                                <div class="role-badge">
                                                    <i class="fas fa-user-tag"></i>
                                                    {{ $role->role_name }}
                                                </div>
                                                <div class="role-meta">
                                                    <small class="text-muted">
                                                        <i class="fas fa-calendar"></i>
                                                        مربوط منذ {{ \Carbon\Carbon::parse($role->created_at)->diffForHumans() }}
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="role-actions">
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-danger delete-role-btn"
                                                        data-id="{{ $role->id }}"
                                                        data-department="{{ $departmentName }}"
                                                        data-role="{{ $role->role_name }}"
                                                        title="إلغاء الربط">
                                                    <i class="fas fa-unlink"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Department Employees -->
                <div class="col-lg-6 mb-4">
                    <div class="info-card">
                        <div class="card-header-modern">
                            <div class="header-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="header-content">
                                <h3>موظفو القسم</h3>
                                <p>الموظفين العاملين في هذا القسم</p>
                            </div>
                        </div>

                        <div class="card-body-modern">
                            @if($users->isEmpty())
                                <div class="empty-state-small">
                                    <i class="fas fa-users"></i>
                                    <h4>لا يوجد موظفين</h4>
                                    <p>لا يوجد موظفين مسجلين في هذا القسم</p>
                                </div>
                            @else
                                <div class="users-list">
                                    @foreach($users as $user)
                                        <div class="user-item">
                                            <div class="user-avatar">
                                                @if($user->profile_photo_url)
                                                    <img src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}">
                                                @else
                                                    <div class="avatar-placeholder">
                                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="user-info">
                                                <h5>{{ $user->name }}</h5>
                                                <p>{{ $user->email }}</p>
                                                @if($user->employee_id)
                                                    <small class="employee-id">ID: {{ $user->employee_id }}</small>
                                                @endif
                                            </div>
                                            <div class="user-roles">
                                                @foreach($user->roles as $userRole)
                                                    <span class="role-tag {{ $roles->where('role_name', $userRole->name)->isNotEmpty() ? 'role-tag-department' : '' }}">
                                                        {{ $userRole->name }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Department Overview -->
            <div class="row">
                <div class="col-12">
                    <div class="overview-card">
                        <div class="overview-header">
                            <h3>
                                <i class="fas fa-chart-pie"></i>
                                نظرة عامة على القسم
                            </h3>
                        </div>
                        <div class="overview-content">
                            <div class="overview-grid">
                                <div class="overview-item">
                                    <div class="overview-icon bg-primary">
                                        <i class="fas fa-user-tag"></i>
                                    </div>
                                    <div class="overview-details">
                                        <h4>{{ $roles->count() }}</h4>
                                        <p>أدوار مربوطة</p>
                                    </div>
                                </div>

                                <div class="overview-item">
                                    <div class="overview-icon bg-success">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div class="overview-details">
                                        <h4>{{ $users->count() }}</h4>
                                        <p>إجمالي الموظفين</p>
                                    </div>
                                </div>

                                <div class="overview-item">
                                    <div class="overview-icon bg-info">
                                        <i class="fas fa-user-check"></i>
                                    </div>
                                    <div class="overview-details">
                                        <h4>{{ $users->filter(function($user) use ($roles) { return $user->roles->whereIn('name', $roles->pluck('role_name'))->isNotEmpty(); })->count() }}</h4>
                                        <p>موظفين بأدوار القسم</p>
                                    </div>
                                </div>

                                <div class="overview-item">
                                    <div class="overview-icon bg-warning">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </div>
                                    <div class="overview-details">
                                        <h4>{{ $users->filter(function($user) use ($roles) { return $user->roles->whereIn('name', $roles->pluck('role_name'))->isEmpty(); })->count() }}</h4>
                                        <p>موظفين بدون أدوار القسم</p>
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

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-modern">
            <div class="modal-header">
                <div class="modal-icon">
                    <i class="fas fa-unlink"></i>
                </div>
                <h5 class="modal-title">إلغاء ربط الدور</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>هل أنت متأكد من إلغاء ربط الدور <strong id="role-name"></strong> مع القسم <strong id="department-name"></strong>؟</p>
                <div class="warning-note">
                    <i class="fas fa-info-circle"></i>
                    سيؤثر هذا على الموظفين الذين لديهم هذا الدور في القسم
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
                        <i class="fas fa-unlink"></i>
                        إلغاء الربط
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

// Add animations on page load
document.addEventListener('DOMContentLoaded', function() {
    // Animate overview items
    const overviewItems = document.querySelectorAll('.overview-item');
    overviewItems.forEach((item, index) => {
        setTimeout(() => {
            item.style.opacity = '0';
            item.style.transform = 'translateY(20px)';
            item.style.transition = 'all 0.5s ease';

            setTimeout(() => {
                item.style.opacity = '1';
                item.style.transform = 'translateY(0)';
            }, 100);
        }, index * 100);
    });
});
</script>
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/department-roles.css') }}">
@endpush
