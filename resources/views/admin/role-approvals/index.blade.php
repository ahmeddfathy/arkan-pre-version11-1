@extends('layouts.app')

@section('title', 'إدارة قواعد اعتماد الأدوار')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/role-approvals.css') }}">
@endpush

@section('content')
<div class="simple-container">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>⚙️ إدارة قواعد اعتماد الأدوار</h1>
            <p>تحديد من يعتمد لمن في تسليمات المشاريع والمهام</p>
        </div>

        <!-- Success/Error Messages -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Add New Approval Rule Form -->
        <div class="add-rule-section">
            <div class="section-title">
                <i class="fas fa-plus-circle"></i>
                إضافة قاعدة اعتماد جديدة
            </div>

            <form id="createApprovalForm">
                @csrf
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-user-tag"></i>
                            الرول الذي يحتاج اعتماد
                        </label>
                        <select name="role_id" class="form-select" required>
                            <option value="">اختر الرول</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-user-check"></i>
                            الرول المعتمد
                        </label>
                        <select name="approver_role_id" class="form-select" required>
                            <option value="">اختر المعتمد</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-tag"></i>
                            نوع الاعتماد
                        </label>
                        <select name="approval_type" class="form-select" required>
                            <option value="">اختر النوع</option>
                            <option value="administrative">إداري</option>
                            <option value="technical">فني</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-comment-dots"></i>
                            الوصف (اختياري)
                        </label>
                        <input type="text" name="description" class="form-control" placeholder="أدخل وصف القاعدة">
                    </div>
                </div>

                <div class="form-row" style="margin-top: 1rem;">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-cog"></i>
                            شروط إضافية
                        </label>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" name="requires_same_project" id="requiresSameProject" value="1">
                                <label for="requiresSameProject">
                                    يجب أن يكون في نفس المشروع
                                </label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="requires_team_owner" id="requiresTeamOwner" value="1">
                                <label for="requiresTeamOwner">
                                    يجب أن يكون مالك الفريق
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" style="display: flex; align-items: flex-end;">
                        <button type="submit" class="btn-add" style="width: 100%;">
                            <i class="fas fa-plus"></i>
                            إضافة قاعدة جديدة
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Current Approval Rules -->
        <div class="rules-list-section">
            <div class="list-header">
                <h2>
                    <i class="fas fa-list-check"></i>
                    قواعد الاعتماد الحالية
                </h2>
                <div class="stats-badge">
                    إجمالي القواعد: {{ $roleApprovals->count() }}
                </div>
            </div>

            @if($roleApprovals->count() > 0)
                @foreach($approvalsByDepartment as $departmentName => $departmentApprovals)
                    <div class="department-group">
                        <div class="department-header">
                            <div class="department-title">
                                <i class="fas fa-building"></i>
                                {{ $departmentName }}
                            </div>
                            <div class="department-count">
                                {{ count($departmentApprovals) }} قاعدة
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="approvals-table">
                                <thead>
                                    <tr>
                                        <th>الرول المطلوب اعتماده</th>
                                        <th>المعتمِد</th>
                                        <th>نوع الاعتماد</th>
                                        <th>الوصف</th>
                                        <th>شروط إضافية</th>
                                        <th>الحالة</th>
                                        <th>تاريخ الإنشاء</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($departmentApprovals as $approval)
                                        <tr>
                                            <td>
                                                <span class="role-badge role-badge-primary">
                                                    <i class="fas fa-user-tag"></i>
                                                    {{ $approval->role->name }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="role-badge role-badge-success">
                                                    <i class="fas fa-user-check"></i>
                                                    {{ $approval->approverRole->name }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="approval-type-badge approval-type-{{ $approval->approval_type }}">
                                                    {{ $approval->approval_type == 'administrative' ? '📋 إداري' : '🔧 فني' }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($approval->description)
                                                    <span class="text-muted">{{ $approval->description }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2 align-items-center">
                                                    @if($approval->requires_same_project)
                                                        <span class="condition-badge condition-project">
                                                            <i class="fas fa-project-diagram"></i>
                                                            نفس المشروع
                                                        </span>
                                                    @endif
                                                    @if($approval->requires_team_owner)
                                                        <span class="condition-badge condition-team">
                                                            <i class="fas fa-crown"></i>
                                                            مالك الفريق
                                                        </span>
                                                    @endif
                                                    @if(!$approval->requires_same_project && !$approval->requires_team_owner)
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <span class="status-badge status-{{ $approval->is_active ? 'active' : 'inactive' }}">
                                                    {{ $approval->is_active ? '✓ نشط' : '✕ معطل' }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="date-text">
                                                    {{ $approval->created_at->diffForHumans() }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn-action btn-edit edit-approval"
                                                            data-id="{{ $approval->id }}"
                                                            data-role-id="{{ $approval->role_id }}"
                                                            data-approver-role-id="{{ $approval->approver_role_id }}"
                                                            data-approval-type="{{ $approval->approval_type }}"
                                                            data-description="{{ $approval->description }}"
                                                            data-is-active="{{ $approval->is_active }}"
                                                            data-requires-same-project="{{ $approval->requires_same_project }}"
                                                            data-requires-team-owner="{{ $approval->requires_team_owner }}"
                                                            title="تعديل">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn-action btn-toggle {{ $approval->is_active ? '' : 'active' }} toggle-status"
                                                            data-id="{{ $approval->id }}"
                                                            title="{{ $approval->is_active ? 'إيقاف' : 'تفعيل' }}">
                                                        <i class="fas fa-{{ $approval->is_active ? 'pause' : 'play' }}"></i>
                                                    </button>
                                                    <button class="btn-action btn-delete delete-approval"
                                                            data-id="{{ $approval->id }}"
                                                            title="حذف">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="empty-state">
                    <i class="fas fa-clipboard-list"></i>
                    <h5>لا توجد قواعد اعتماد محددة بعد</h5>
                    <p>ابدأ بإضافة قاعدة اعتماد جديدة باستخدام النموذج أعلاه</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editApprovalModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit"></i>
                    تعديل قاعدة الاعتماد
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editApprovalForm">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <input type="hidden" id="edit_approval_id" name="approval_id">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="fas fa-user-tag"></i>
                                الرول الذي يحتاج اعتماد
                            </label>
                            <select name="role_id" id="edit_role_id" class="form-select" required>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="fas fa-user-check"></i>
                                الرول المعتمد
                            </label>
                            <select name="approver_role_id" id="edit_approver_role_id" class="form-select" required>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="fas fa-tag"></i>
                                نوع الاعتماد
                            </label>
                            <select name="approval_type" id="edit_approval_type" class="form-select" required>
                                <option value="administrative">إداري</option>
                                <option value="technical">فني</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="fas fa-comment-dots"></i>
                                الوصف
                            </label>
                            <input type="text" name="description" id="edit_description" class="form-control">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-cog"></i>
                            الشروط والإعدادات
                        </label>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" name="requires_same_project" id="edit_requires_same_project" value="1">
                                <label for="edit_requires_same_project">
                                    يجب أن يكون في نفس المشروع
                                </label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="requires_team_owner" id="edit_requires_team_owner" value="1">
                                <label for="edit_requires_team_owner">
                                    يجب أن يكون مالك الفريق
                                </label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="is_active" id="edit_is_active" value="1">
                                <label for="edit_is_active">
                                    <strong>نشط</strong>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i>
                        إلغاء
                    </button>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i>
                        حفظ التغييرات
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    // Create new approval
    $('#createApprovalForm').on('submit', function(e) {
        e.preventDefault();

        $.ajax({
            url: '{{ route("admin.role-approvals.store") }}',
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'نجح!',
                        text: response.message,
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });

                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    Swal.fire({
                        title: 'خطأ!',
                        text: response.message,
                        icon: 'error'
                    });
                }
            },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors;
                let errorMessage = 'حدث خطأ أثناء الحفظ';

                if (errors) {
                    errorMessage = Object.values(errors).flat().join('\n');
                }

                Swal.fire({
                    title: 'خطأ!',
                    text: errorMessage,
                    icon: 'error'
                });
            }
        });
    });

    // Edit approval
    $(document).on('click', '.edit-approval', function() {
        const data = $(this).data();

        $('#edit_approval_id').val(data.id);
        $('#edit_role_id').val(data.roleId);
        $('#edit_approver_role_id').val(data.approverRoleId);
        $('#edit_approval_type').val(data.approvalType);
        $('#edit_description').val(data.description);
        $('#edit_requires_same_project').prop('checked', data.requiresSameProject);
        $('#edit_requires_team_owner').prop('checked', data.requiresTeamOwner);
        $('#edit_is_active').prop('checked', data.isActive);

        $('#editApprovalModal').modal('show');
    });

    // Update approval
    $('#editApprovalForm').on('submit', function(e) {
        e.preventDefault();

        const approvalId = $('#edit_approval_id').val();

        $.ajax({
            url: `/admin/role-approvals/${approvalId}`,
            method: 'PUT',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    $('#editApprovalModal').modal('hide');

                    Swal.fire({
                        title: 'نجح!',
                        text: response.message,
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });

                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    Swal.fire({
                        title: 'خطأ!',
                        text: response.message,
                        icon: 'error'
                    });
                }
            },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors;
                let errorMessage = 'حدث خطأ أثناء التحديث';

                if (errors) {
                    errorMessage = Object.values(errors).flat().join('\n');
                }

                Swal.fire({
                    title: 'خطأ!',
                    text: errorMessage,
                    icon: 'error'
                });
            }
        });
    });

    // Toggle status
    $(document).on('click', '.toggle-status', function() {
        const approvalId = $(this).data('id');

        $.ajax({
            url: `/admin/role-approvals/${approvalId}/toggle-status`,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'تم!',
                        text: response.message,
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    });

                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                }
            },
            error: function() {
                Swal.fire({
                    title: 'خطأ!',
                    text: 'حدث خطأ أثناء تغيير الحالة',
                    icon: 'error'
                });
            }
        });
    });

    // Delete approval
    $(document).on('click', '.delete-approval', function() {
        const approvalId = $(this).data('id');

        Swal.fire({
            title: 'هل أنت متأكد؟',
            text: 'لن تتمكن من استرداد قاعدة الاعتماد هذه!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'نعم، احذف!',
            cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/admin/role-approvals/${approvalId}`,
                    method: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'تم الحذف!',
                                text: response.message,
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false
                            });

                            setTimeout(() => {
                                location.reload();
                            }, 2000);
                        }
                    },
                    error: function() {
                        Swal.fire({
                            title: 'خطأ!',
                            text: 'حدث خطأ أثناء الحذف',
                            icon: 'error'
                        });
                    }
                });
            }
        });
    });
});
</script>
@endpush
