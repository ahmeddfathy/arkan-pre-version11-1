@extends('layouts.app')

@section('title', 'إدارة الأدوار')

@push('styles')
    <link href="{{ asset('css/admin/roles-management.css') }}?v={{ time() }}" rel="stylesheet">
@endpush

@section('content')
<div class="roles-management">
    <div class="roles-container">
        {{-- Header Section --}}
        <div class="roles-header fade-in">
            <h1><i class="fas fa-user-tag"></i> إدارة الأدوار</h1>
            <p>إدارة شاملة لجميع أدوار النظام بتصميم عصري ومتقدم</p>

            <button type="button" class="btn-modern btn-primary" onclick="openCreateModal()">
                <i class="fas fa-plus"></i> إضافة دور جديد
            </button>
        </div>

        {{-- Search & Controls --}}
        <div class="roles-controls slide-up">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="ابحث عن دور معين..."
                       value="{{ request('search') }}">
            </div>

            <select class="filter-select" id="filterSelect">
                <option value="">جميع الأدوار</option>
                <option value="active" {{ request('has_users') == '1' ? 'selected' : '' }}>
                    الأدوار النشطة
                </option>
                <option value="inactive" {{ request('has_users') == '0' ? 'selected' : '' }}>
                    الأدوار غير المستخدمة
                </option>
            </select>


            <button type="button" class="btn-modern btn-info" onclick="refreshData(event)">
                <i class="fas fa-sync-alt"></i> تحديث
            </button>
        </div>

        {{-- Statistics Cards --}}
        <div class="stats-grid slide-up">
            <div class="stat-card hover-lift">
                <div class="stat-header">
                    <span class="stat-title">إجمالي الأدوار</span>
                    <div class="stat-icon primary">
                        <i class="fas fa-user-tag"></i>
                    </div>
                </div>
                <div class="stat-value">{{ $stats['total_roles'] }}</div>
            </div>

            <div class="stat-card hover-lift">
                <div class="stat-header">
                    <span class="stat-title">الأدوار النشطة</span>
                    <div class="stat-icon success">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <div class="stat-value">{{ $stats['active_roles'] }}</div>
            </div>

            <div class="stat-card hover-lift">
                <div class="stat-header">
                    <span class="stat-title">أدوار غير مستخدمة</span>
                    <div class="stat-icon warning">
                        <i class="fas fa-user-slash"></i>
                    </div>
                </div>
                <div class="stat-value">{{ $stats['inactive_roles'] }}</div>
            </div>

            <div class="stat-card hover-lift">
                <div class="stat-header">
                    <span class="stat-title">إجمالي المستخدمين</span>
                    <div class="stat-icon info">
                        <i class="fas fa-user-check"></i>
                    </div>
                </div>
                <div class="stat-value">{{ $stats['total_users'] }}</div>
            </div>
        </div>

        {{-- Roles Container --}}
        <div id="rolesContainer">
            @if($roles->count() > 0)
                {{-- Table View --}}
                <div id="tableView" class="table-view">
                    <div class="table-container">
                        <table class="roles-table">
                            <thead>
                                <tr>
                                    <th>اسم الدور</th>
                                    <th>الاسم المعروض</th>
                                    <th>الحالة</th>
                                    <th>عدد المستخدمين</th>
                                    <th>الأقسام</th>
                                    <th>تاريخ الإنشاء</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($roles as $role)
                                <tr class="role-row" data-role-name="{{ strtolower($role->name) }}" data-status="{{ $role->users->count() > 0 ? 'active' : 'inactive' }}">
                                    <td>
                                        <div class="role-name-cell">
                                            <strong>{{ $role->name }}</strong>
                                            @if($role->description)
                                                <small class="text-muted d-block">{{ Str::limit($role->description, 50) }}</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <span class="role-display-name">{{ $role->display_name ?: '-' }}</span>
                                    </td>
                                    <td>
                                        <span class="role-status-badge {{ $role->users->count() > 0 ? 'status-active' : 'status-inactive' }}">
                                            {{ $role->users->count() > 0 ? 'نشط' : 'غير مستخدم' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="user-count">
                                            <span class="count-number">{{ $role->users->count() }}</span>
                                            @if($role->users->count() > 0)
                                                <button type="button" class="btn-link" onclick="showRole({{ $role->id }})">
                                                    <small>عرض المستخدمين</small>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="departments-cell">
                                            @if($role->users->pluck('department')->filter()->unique()->count() > 0)
                                                @foreach($role->users->pluck('department')->filter()->unique()->take(2) as $department)
                                                    <span class="department-tag-small">{{ $department }}</span>
                                                @endforeach
                                                @if($role->users->pluck('department')->filter()->unique()->count() > 2)
                                                    <span class="more-departments">+{{ $role->users->pluck('department')->filter()->unique()->count() - 2 }}</span>
                                                @endif
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="date-cell">
                                            <div>{{ $role->created_at ? $role->created_at->format('Y-m-d') : '-' }}</div>
                                            <small class="text-muted">{{ $role->created_at ? $role->created_at->diffForHumans() : '' }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <button type="button" class="btn-modern btn-info btn-sm" onclick="showRole({{ $role->id }})" title="عرض">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button type="button" class="btn-modern btn-warning btn-sm" onclick="editRole({{ $role->id }})" title="تعديل">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn-modern btn-success btn-sm" onclick="duplicateRole({{ $role->id }})" title="نسخ">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                            @if($role->users->count() == 0)
                                                <button type="button" class="btn-modern btn-danger btn-sm" onclick="confirmDelete({{ $role->id }}, '{{ $role->name }}')" title="حذف">
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
                </div>
            @else
                <div class="empty-state">
                    <i class="fas fa-user-tag"></i>
                    <h3>لا توجد أدوار</h3>
                    <p>لم يتم العثور على أي أدوار بالمعايير المحددة</p>
                    <button type="button" class="btn-modern btn-primary" onclick="openCreateModal()">
                        <i class="fas fa-plus"></i> إضافة أول دور
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Create/Edit Modal --}}
<div class="modal fade modal-modern" id="roleModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="roleForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="roleModalTitle">
                        <i class="fas fa-plus"></i> إضافة دور جديد
                    </h5>
                    <button type="button" class="close" data-bs-dismiss="modal" style="background: rgba(255,255,255,0.2); border: none; color: white; border-radius: 50%; width: 35px; height: 35px;">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="roleFormErrors" class="alert alert-danger" style="display: none; border-radius: 10px;"></div>

                    <div class="form-group">
                        <label class="form-label" for="roleName">
                            اسم الدور <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="roleName" name="name" required>
                        <small class="form-text">
                            يجب أن يكون اسم الدور باللغة الإنجليزية ولا يحتوي على مسافات
                        </small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="roleDisplayName">الاسم المعروض</label>
                        <input type="text" class="form-control" id="roleDisplayName" name="display_name">
                        <small class="form-text">
                            الاسم الذي سيظهر للمستخدمين (اختياري)
                        </small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="roleDescription">الوصف</label>
                        <textarea class="form-control" id="roleDescription" name="description" rows="3" style="resize: vertical;"></textarea>
                        <small class="form-text">وصف مختصر لهذا الدور (اختياري)</small>
                    </div>
                </div>
                <div class="modal-footer" style="background: #f8fafc; border: none; padding: 1.5rem;">
                    <button type="button" class="btn-modern" data-bs-dismiss="modal" style="background: #e5e7eb; color: #374151;">
                        <i class="fas fa-times"></i> إلغاء
                    </button>
                    <button type="submit" class="btn-modern btn-primary">
                        <span id="roleFormSubmitText">
                            <i class="fas fa-save"></i> حفظ
                        </span>
                        <span id="roleFormSpinner" class="spinner-border spinner-border-sm" style="display: none;"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- View Role Modal --}}
<div class="modal fade modal-modern" id="viewRoleModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-eye"></i> تفاصيل الدور
                </h5>
                <button type="button" class="close" data-bs-dismiss="modal" style="background: rgba(255,255,255,0.2); border: none; color: white; border-radius: 50%; width: 35px; height: 35px;">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="viewRoleContent">
                <div class="text-center" style="padding: 3rem;">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p style="margin-top: 1rem; color: #64748b;">جاري التحميل...</p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Duplicate Role Modal --}}
<div class="modal fade modal-modern" id="duplicateRoleModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="duplicateRoleForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-copy"></i> نسخ الدور
                    </h5>
                    <button type="button" class="close" data-bs-dismiss="modal" style="background: rgba(255,255,255,0.2); border: none; color: white; border-radius: 50%; width: 35px; height: 35px;">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="duplicateFormErrors" class="alert alert-danger" style="display: none; border-radius: 10px;"></div>

                    <div style="padding: 1rem; background: #f0f9ff; border-radius: 10px; margin-bottom: 1.5rem; border-left: 4px solid var(--info-color);">
                        <p style="margin: 0; color: #0369a1;">
                            <i class="fas fa-info-circle"></i>
                            سيتم نسخ الدور "<strong id="originalRoleName"></strong>" بجميع خصائصه
                        </p>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="duplicateRoleName">
                            اسم الدور الجديد <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="duplicateRoleName" name="name" required>
                        <small class="form-text">يجب أن يكون اسم الدور فريداً</small>
                    </div>
                </div>
                <div class="modal-footer" style="background: #f8fafc; border: none; padding: 1.5rem;">
                    <button type="button" class="btn-modern" data-bs-dismiss="modal" style="background: #e5e7eb; color: #374151;">
                        <i class="fas fa-times"></i> إلغاء
                    </button>
                    <button type="submit" class="btn-modern btn-success">
                        <span id="duplicateFormSubmitText">
                            <i class="fas fa-copy"></i> نسخ الدور
                        </span>
                        <span id="duplicateFormSpinner" class="spinner-border spinner-border-sm" style="display: none;"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Ensure Bootstrap 5 is available
if (typeof bootstrap === 'undefined') {
    console.error('Bootstrap 5 is not loaded. Please include Bootstrap 5 JavaScript.');
}

let currentRoleId = null;
let currentView = 'table'; // Default view

// Helper function to close modals
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (typeof bootstrap !== 'undefined') {
        const bsModal = bootstrap.Modal.getInstance(modal);
        if (bsModal) {
            bsModal.hide();
        }
    } else {
        modal.style.display = 'none';
        modal.classList.remove('show');
        document.body.classList.remove('modal-open');
    }
}

// Search and Filter functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const filterSelect = document.getElementById('filterSelect');

    searchInput.addEventListener('input', filterRoles);
    filterSelect.addEventListener('change', filterRoles);

    // Initialize view
    switchView('table');

    // Add event listeners for modal close buttons
    document.addEventListener('click', function(e) {
        if (e.target.matches('[data-bs-dismiss="modal"]') || e.target.closest('[data-bs-dismiss="modal"]')) {
            const modal = e.target.closest('.modal');
            if (modal) {
                closeModal(modal.id);
            }
        }

        // Close modal when clicking on backdrop
        if (e.target.classList.contains('modal')) {
            closeModal(e.target.id);
        }
    });
});

// Initialize table view
function switchView(view) {
    const tableView = document.getElementById('tableView');
    if (tableView) {
        tableView.style.display = 'block';
    }
    currentView = 'table';

    // Reapply filters
    filterRoles();
}

function filterRoles() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const filterValue = document.getElementById('filterSelect').value;
    const roleRows = document.querySelectorAll('.role-row');

    let visibleCount = 0;

    // Filter table view
    roleRows.forEach(row => {
        const roleName = row.getAttribute('data-role-name');
        const roleStatus = row.getAttribute('data-status');

        let showBySearch = roleName.includes(searchTerm);
        let showByFilter = filterValue === '' ||
                          (filterValue === 'active' && roleStatus === 'active') ||
                          (filterValue === 'inactive' && roleStatus === 'inactive');

        if (showBySearch && showByFilter) {
            row.style.display = 'table-row';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    // Show/hide empty state
    const emptyState = document.querySelector('.empty-state');
    if (emptyState) {
        emptyState.style.display = visibleCount === 0 ? 'block' : 'none';
    }
}

function refreshData(event) {
    const refreshBtn = event ? event.target.closest('button') : null;

    if (refreshBtn) {
        const originalHTML = refreshBtn.innerHTML;
        refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري التحديث...';
        refreshBtn.disabled = true;
    }

    setTimeout(() => {
        location.reload();
    }, 1000);
}

// Open create modal
function openCreateModal() {
    currentRoleId = null;
    const modal = document.getElementById('roleModal');
    const title = document.getElementById('roleModalTitle');

    title.innerHTML = '<i class="fas fa-plus"></i> إضافة دور جديد';
    document.getElementById('roleForm').reset();
    document.getElementById('roleFormErrors').style.display = 'none';

    // Show modal using Bootstrap 5
    if (typeof bootstrap !== 'undefined') {
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    } else {
        // Fallback for older Bootstrap versions
        modal.style.display = 'block';
        modal.classList.add('show');
        document.body.classList.add('modal-open');
    }
}

// Edit role
function editRole(roleId) {
    currentRoleId = roleId;
    const modal = document.getElementById('roleModal');
    const title = document.getElementById('roleModalTitle');

    title.innerHTML = '<i class="fas fa-edit"></i> تعديل الدور';
    document.getElementById('roleFormErrors').style.display = 'none';

    // Show loading in modal
    const modalBody = modal.querySelector('.modal-body');
    const originalContent = modalBody.innerHTML;
    modalBody.innerHTML = `
        <div class="text-center" style="padding: 3rem;">
            <div class="spinner-border text-primary"></div>
            <p style="margin-top: 1rem;">جاري تحميل البيانات...</p>
        </div>
    `;

    if (typeof bootstrap !== 'undefined') {
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    } else {
        modal.style.display = 'block';
        modal.classList.add('show');
        document.body.classList.add('modal-open');
    }

    // Fetch role data
    $.get(`/roles/${roleId}`)
        .done(function(response) {
            modalBody.innerHTML = originalContent;
            if (response.status === 'success') {
                document.getElementById('roleName').value = response.data.role.name;
                document.getElementById('roleDisplayName').value = response.data.role.display_name || '';
                document.getElementById('roleDescription').value = response.data.role.description || '';
            }
        })
        .fail(function() {
            modalBody.innerHTML = originalContent;
            showToast('حدث خطأ أثناء جلب بيانات الدور', 'error');
        });
}

// Show role details
function showRole(roleId) {
    const modal = document.getElementById('viewRoleModal');
    const content = document.getElementById('viewRoleContent');

    content.innerHTML = `
        <div class="text-center" style="padding: 3rem;">
            <div class="spinner-border text-primary"></div>
            <p style="margin-top: 1rem;">جاري التحميل...</p>
        </div>
    `;

    if (typeof bootstrap !== 'undefined') {
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    } else {
        modal.style.display = 'block';
        modal.classList.add('show');
        document.body.classList.add('modal-open');
    }

    $.get(`/roles/${roleId}`)
        .done(function(response) {
            if (response.status === 'success') {
                const role = response.data.role;
                const stats = response.data.stats;

                content.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <div class="stat-card">
                                <h6 style="color: var(--primary-color); margin-bottom: 1rem;">
                                    <i class="fas fa-info-circle"></i> معلومات الدور
                                </h6>
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>اسم الدور:</strong></td>
                                        <td><span class="badge" style="background: var(--primary-color); color: white; padding: 0.5rem 1rem; border-radius: 20px;">${role.name}</span></td>
                                    </tr>
                                    <tr>
                                        <td><strong>الاسم المعروض:</strong></td>
                                        <td>${role.display_name || '<span style="color: #94a3b8;">غير محدد</span>'}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>الوصف:</strong></td>
                                        <td>${role.description || '<span style="color: #94a3b8;">غير محدد</span>'}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>تاريخ الإنشاء:</strong></td>
                                        <td>${new Date(role.created_at).toLocaleDateString('ar-EG', {
                                            year: 'numeric',
                                            month: 'long',
                                            day: 'numeric',
                                            hour: '2-digit',
                                            minute: '2-digit'
                                        })}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="stat-card">
                                <h6 style="color: var(--success-color); margin-bottom: 1rem;">
                                    <i class="fas fa-chart-bar"></i> الإحصائيات
                                </h6>
                                <div class="stats-grid" style="grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                                    <div class="text-center">
                                        <div style="font-size: 2rem; font-weight: 700; color: var(--primary-color);">${stats.users_count}</div>
                                        <div style="color: #64748b; font-size: 0.9rem;">مستخدم</div>
                                    </div>
                                    <div class="text-center">
                                        <div style="font-size: 2rem; font-weight: 700; color: var(--info-color);">${stats.departments.length}</div>
                                        <div style="color: #64748b; font-size: 0.9rem;">قسم</div>
                                    </div>
                                </div>
                                ${stats.departments.length > 0 ? `
                                    <div style="margin-top: 1rem;">
                                        <strong style="color: #374151;">الأقسام:</strong>
                                        <div style="margin-top: 0.5rem;">
                                            ${stats.departments.map(dept => `<span class="department-tag" style="margin: 0.25rem;">${dept}</span>`).join('')}
                                        </div>
                                    </div>
                                ` : '<p style="color: #94a3b8; margin-top: 1rem; text-align: center;">لا توجد أقسام</p>'}
                            </div>
                        </div>
                    </div>

                    ${role.users.length > 0 ? `
                        <div class="stat-card" style="margin-top: 1.5rem;">
                            <h6 style="color: var(--info-color); margin-bottom: 1rem;">
                                <i class="fas fa-users"></i> المستخدمون (${role.users.length})
                            </h6>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead style="background: #f8fafc;">
                                        <tr>
                                            <th>الاسم</th>
                                            <th>البريد الإلكتروني</th>
                                            <th>القسم</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${role.users.map(user => `
                                            <tr>
                                                <td>
                                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                                        <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--primary-color); color: white; display: flex; align-items: center; justify-content: center; font-weight: 600;">
                                                            ${user.name.charAt(0)}
                                                        </div>
                                                        ${user.name}
                                                    </div>
                                                </td>
                                                <td>${user.email || '<span style="color: #94a3b8;">غير محدد</span>'}</td>
                                                <td>
                                                    ${user.department ? `<span class="department-tag">${user.department}</span>` : '<span style="color: #94a3b8;">غير محدد</span>'}
                                                </td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    ` : `
                        <div class="text-center" style="padding: 2rem; background: #f8fafc; border-radius: 10px; margin-top: 1.5rem;">
                            <i class="fas fa-users" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 1rem;"></i>
                            <h5 style="color: #64748b;">لا يوجد مستخدمين</h5>
                            <p style="color: #94a3b8;">لم يتم تعيين أي مستخدم لهذا الدور بعد</p>
                        </div>
                    `}
                `;
            }
        })
        .fail(function() {
            content.innerHTML = `
                <div class="alert alert-danger" style="border-radius: 10px;">
                    <i class="fas fa-exclamation-triangle"></i>
                    حدث خطأ أثناء جلب بيانات الدور
                </div>
            `;
        });
}

// Duplicate role
function duplicateRole(roleId) {
    const modal = document.getElementById('duplicateRoleModal');

    // Get original role name first
    $.get(`/roles/${roleId}`)
        .done(function(response) {
            if (response.status === 'success') {
                document.getElementById('originalRoleName').textContent = response.data.role.name;
                document.getElementById('duplicateRoleName').value = response.data.role.name + '_copy';
                document.getElementById('duplicateRoleForm').setAttribute('data-role-id', roleId);
                if (typeof bootstrap !== 'undefined') {
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    } else {
        modal.style.display = 'block';
        modal.classList.add('show');
        document.body.classList.add('modal-open');
    }
            }
        })
        .fail(function() {
            showToast('حدث خطأ أثناء تحميل بيانات الدور', 'error');
        });
}

// Confirm delete
function confirmDelete(roleId, roleName) {
    const confirmModal = document.createElement('div');
    confirmModal.innerHTML = `
        <div class="modal fade modal-modern" tabindex="-1" style="z-index: 9999; display: block;">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header" style="background: linear-gradient(135deg, var(--danger-color), #e53e3e);">
                        <h5 class="modal-title text-white">
                            <i class="fas fa-exclamation-triangle"></i> تأكيد الحذف
                        </h5>
                    </div>
                    <div class="modal-body" style="padding: 2rem;">
                        <div class="text-center">
                            <div style="width: 80px; height: 80px; border-radius: 50%; background: rgba(245, 101, 101, 0.1); margin: 0 auto 1rem; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-trash-alt" style="font-size: 2rem; color: var(--danger-color);"></i>
                            </div>
                            <h5 style="color: #1e293b; margin-bottom: 1rem;">هل أنت متأكد من حذف هذا الدور؟</h5>
                            <p style="color: #64748b; margin-bottom: 1.5rem;">
                                سيتم حذف الدور "<strong style="color: var(--danger-color);">${roleName}</strong>" نهائياً
                                <br><small>لا يمكن التراجع عن هذا الإجراء</small>
                            </p>
                        </div>
                    </div>
                    <div class="modal-footer" style="background: #f8fafc; border: none; padding: 1.5rem;">
                        <button type="button" class="btn-modern" data-bs-dismiss="modal" style="background: #e5e7eb; color: #374151;">
                            <i class="fas fa-times"></i> إلغاء
                        </button>
                        <button type="button" class="btn-modern btn-danger" onclick="deleteRole(${roleId}, event)">
                            <i class="fas fa-trash"></i> حذف نهائي
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Add backdrop
    const backdrop = document.createElement('div');
    backdrop.className = 'modal-backdrop-manual';
    backdrop.id = 'confirmModalBackdrop';

    document.body.appendChild(backdrop);
    document.body.appendChild(confirmModal);

    if (typeof bootstrap !== 'undefined') {
        const confirmBsModal = new bootstrap.Modal(confirmModal.firstElementChild);
        confirmBsModal.show();
    } else {
        confirmModal.firstElementChild.style.display = 'block';
        confirmModal.firstElementChild.classList.add('show');
        document.body.classList.add('modal-open');
    }

    // Remove modal after hide
    confirmModal.firstElementChild.addEventListener('hidden.bs.modal', function() {
        document.body.removeChild(confirmModal);
        const backdrop = document.getElementById('confirmModalBackdrop');
        if (backdrop) {
            document.body.removeChild(backdrop);
        }
    });

    // Also remove backdrop when clicking on it
    backdrop.addEventListener('click', function() {
        document.body.removeChild(confirmModal);
        document.body.removeChild(backdrop);
    });
}

// Delete role
function deleteRole(roleId, event) {
    const deleteBtn = event.target;
    const originalHTML = deleteBtn.innerHTML;

    deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري الحذف...';
    deleteBtn.disabled = true;

    $.ajax({
        url: `/roles/${roleId}`,
        type: 'DELETE',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.status === 'success') {
                showToast('تم حذف الدور بنجاح', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast('حدث خطأ أثناء حذف الدور', 'error');
                deleteBtn.innerHTML = originalHTML;
                deleteBtn.disabled = false;
            }
        },
        error: function() {
            showToast('حدث خطأ أثناء حذف الدور', 'error');
            deleteBtn.innerHTML = originalHTML;
            deleteBtn.disabled = false;
        }
    });
}

// Handle role form submission
document.getElementById('roleForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const submitButton = this.querySelector('button[type="submit"]');
    const submitText = document.getElementById('roleFormSubmitText');
    const spinner = document.getElementById('roleFormSpinner');
    const errorsDiv = document.getElementById('roleFormErrors');

    // Show loading
    if (submitButton) submitButton.disabled = true;
    if (submitText) submitText.style.display = 'none';
    if (spinner) spinner.style.display = 'inline-block';
    if (errorsDiv) errorsDiv.style.display = 'none';

    const url = currentRoleId ? `/roles/${currentRoleId}` : '/roles';
    const method = currentRoleId ? 'PUT' : 'POST';

    let formData = new FormData(this);
    if (currentRoleId) {
        formData.append('_method', 'PUT');
    }

    $.ajax({
        url: url,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.status === 'success') {
                showToast(currentRoleId ? 'تم تحديث الدور بنجاح' : 'تم إنشاء الدور بنجاح', 'success');
                setTimeout(() => location.reload(), 1000);
            }
        },
        error: function(xhr) {
            let errorMessage = 'حدث خطأ أثناء حفظ الدور';

            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                const errors = Object.values(xhr.responseJSON.errors).flat();
                errorMessage = errors.join('<br>');
            }

            if (errorsDiv) {
                errorsDiv.innerHTML = errorMessage;
                errorsDiv.style.display = 'block';
            }
        },
        complete: function() {
            if (submitButton) submitButton.disabled = false;
            if (submitText) submitText.style.display = 'inline';
            if (spinner) spinner.style.display = 'none';
        }
    });
});

// Handle duplicate form submission
document.getElementById('duplicateRoleForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const roleId = this.getAttribute('data-role-id');
    const submitButton = this.querySelector('button[type="submit"]');
    const submitText = document.getElementById('duplicateFormSubmitText');
    const spinner = document.getElementById('duplicateFormSpinner');
    const errorsDiv = document.getElementById('duplicateFormErrors');

    // Show loading
    if (submitButton) submitButton.disabled = true;
    if (submitText) submitText.style.display = 'none';
    if (spinner) spinner.style.display = 'inline-block';
    if (errorsDiv) errorsDiv.style.display = 'none';

    const formData = new FormData(this);

    $.ajax({
        url: `/roles/${roleId}/duplicate`,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.status === 'success') {
                showToast('تم نسخ الدور بنجاح', 'success');
                setTimeout(() => location.reload(), 1000);
            }
        },
        error: function(xhr) {
            let errorMessage = 'حدث خطأ أثناء نسخ الدور';

            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                const errors = Object.values(xhr.responseJSON.errors).flat();
                errorMessage = errors.join('<br>');
            }

            if (errorsDiv) {
                errorsDiv.innerHTML = errorMessage;
                errorsDiv.style.display = 'block';
            }
        },
        complete: function() {
            if (submitButton) submitButton.disabled = false;
            if (submitText) submitText.style.display = 'inline';
            if (spinner) spinner.style.display = 'none';
        }
    });
});

// Toast notification function
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast-notification toast-${type}`;
    toast.innerHTML = `
        <div style="padding: 1rem 1.5rem; background: ${type === 'success' ? 'var(--success-color)' : type === 'error' ? 'var(--danger-color)' : 'var(--info-color)'}; color: white; border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            ${message}
        </div>
    `;

    toast.style.position = 'fixed';
    toast.style.top = '20px';
    toast.style.right = '20px';
    toast.style.zIndex = '10000';
    toast.style.animation = 'slideInRight 0.3s ease-out';

    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'slideOutRight 0.3s ease-in';
        setTimeout(() => {
            if (document.body.contains(toast)) {
                document.body.removeChild(toast);
            }
        }, 300);
    }, 4000);
}

// Add keyframe animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(style);
</script>
@endpush
